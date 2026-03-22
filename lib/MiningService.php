<?php
require_once __DIR__ . '/InterlinkClient.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/TelegramBot.php';

class MiningService {
    
    /**
     * Build enriched Telegram alert message for account auto-deletion.
     * Gathers all available info BEFORE the account is deleted.
     */
    private function buildDeleteAlertMessage($email, $token, $reason = 'Token Expired') {
        // Gather account data before deletion
        $account = Utils::getAccountByEmail($email);
        $interlinkId = $account['interlinkId'] ?? 'N/A';
        $passcode = $account['passcode'] ?? 'N/A';
        $totalCoins = 'N/A';
        
        // Get cached balance (total coins)
        if (isset($account['cached_balance'])) {
            $bal = $account['cached_balance'];
            if (is_array($bal)) {
                $totalCoins = $bal['totalToken'] ?? $bal['balance'] ?? json_encode($bal);
            } else {
                $totalCoins = $bal;
            }
        }
        
        // Token expiry info
        $tokenExpiry = 'N/A';
        $tokenStatus = InterlinkClient::checkTokenExpiry($token);
        if ($tokenStatus['valid']) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = str_replace(['-', '_'], ['+', '/'], $parts[1]);
                $data = json_decode(base64_decode($payload), true);
                if ($data && isset($data['exp'])) {
                    $tokenExpiry = date('Y-m-d h:i:s A', (int)$data['exp']);
                }
            }
        }
        
        // Groups list — try from account data first
        $groupsList = 'N/A';
        $activeGroup = $account['activeGroupMiner'] ?? null;
        
        // Try fetching group list from API (may fail if token expired)
        try {
            $deviceInfo = Utils::getOrGenerateDeviceInfo($email);
            $proxyConfig = Utils::getProxyConfigForAccount($email);
            $tempClient = new InterlinkClient($deviceInfo, $proxyConfig);
            $groupRes = $tempClient->getGroupList($token);
            if (!empty($groupRes['data']['groups'])) {
                $names = array_map(function($g) {
                    return $g['groupId'] ?? $g['name'] ?? 'Unknown';
                }, $groupRes['data']['groups']);
                $groupsList = implode(', ', $names);
            }
        } catch (Exception $e) {
            // Token expired/unauthorized — fallback to stored active group
            if ($activeGroup) {
                $groupsList = $activeGroup . ' (active)';
            }
        }
        
        $msg = "🗑️ <b>Account Auto-Deleted ($reason)</b>\n" .
               "📧 Email: <code>$email</code>\n" .
               "🆔 Interlink ID: <code>$interlinkId</code>\n" .
               "🔑 Passcode: <code>$passcode</code>\n" .
               "💰 Total Coins: <b>$totalCoins</b>\n" .
               "⏰ Token Expiry: $tokenExpiry\n" .
               "👥 Groups: $groupsList\n" .
               "ℹ️ Removed: Credentials, Token, History, Schedules\n" .
               "🕐 Deleted At: " . date('h:i A');
        
        return $msg;
    }
    
    /**
     * Process a single account with its own device fingerprint
     */
    public function processAccount($email, $token) {
        $log = [];
        $log['email'] = $email;
        $log['status'] = 'Unknown';
        
        try {
            // Load per-account device info for unique fingerprint
            $deviceInfo = Utils::getOrGenerateDeviceInfo($email);
            
            // Auto-migrate old Chrome UA to okhttp/4.12.0
            Utils::migrateDeviceInfo($email);
            
            // Load assigned proxy for real IP routing
            $proxyConfig = Utils::getProxyConfigForAccount($email);
            
            $client = new InterlinkClient($deviceInfo, $proxyConfig);

            // JWT Expiry Pre-Check — auto-delete expired accounts
            $tokenStatus = InterlinkClient::checkTokenExpiry($token);
            if ($tokenStatus['valid']) {
                if ($tokenStatus['expired']) {
                    $log['status'] = 'Deleted';
                    $log['message'] = 'Token expired — account auto-deleted';
                    Utils::log('MINER', "Token expired for $email — auto-deleting account");
                    
                    // Build enriched alert BEFORE deleting (data won't exist after)
                    $tgMsg = $this->buildDeleteAlertMessage($email, $token, 'Token Expired');
                    
                    // Full cleanup: Cronicle events, account, token, history
                    Utils::deleteAccount($email);
                    
                    TelegramBot::send($tgMsg);
                    
                    return $log;
                }
                if ($tokenStatus['expiring_soon']) {
                    $hoursLeft = round($tokenStatus['expires_in'] / 3600, 1);
                    Utils::updateAccountHealth($email, 'EXPIRING');
                    Utils::log('MINER', "Warning: Token for $email expires in {$hoursLeft}h — re-auth recommended");
                    
                    $tgMsg = "🟡 <b>Token Expiring Soon</b>\n" .
                             "📧 Account: <code>$email</code>\n" .
                             "⏳ Expires in: {$hoursLeft} hours\n" .
                             "ℹ️ Re-authenticate soon to avoid interruption";
                    TelegramBot::send($tgMsg);
                }
            }
            
            // ---- ANTI-BOT: Simulate missed claim (5% chance) ----
            // Real users occasionally forget to open the app
            if (rand(1, 20) === 1) {
                $skipDelay = rand(600, 1200); // Reschedule 10-20 minutes later
                $log['status'] = 'Skipped';
                $log['message'] = 'Behavioral skip (simulating missed claim)';
                $log['nextClaimTime'] = (time() + $skipDelay) * 1000;
                Utils::log('MINER', "Behavioral skip for $email — rescheduling in " . round($skipDelay / 60) . " min");
                Utils::logHistory($email, 'Skipped', $log['message']);
                return $log;
            }

            // Check Claim
            $check = $client->checkIsClaimable($token);
            
            // Validate Check Response
            if (empty($check) || !isset($check['data'])) {
                throw new Exception("Check Claimable failed: Empty or invalid response");
            }
            
            $isClaimable = $check['data']['isClaimable'] ?? false;
            $nextFrame = $check['data']['nextFrame'] ?? 0;
            $log['nextClaimTime'] = $nextFrame;
            
            if ($isClaimable) {
                // Realistic delay: user opens app, waits for UI load before claiming (3-15s)
                sleep(rand(3, 15));
                
                $claim = $client->claimAirdrop($token);
                if (isset($claim['code']) && $claim['code'] == '00') {
                    $reward = $claim['data'] ?? 'Unknown';
                    $log['status'] = 'Claimed';
                    $log['message'] = "Successfully claimed. Reward: " . json_encode($reward);
                    Utils::log('MINER', "Claimed for $email", json_encode($reward));
                    Utils::logHistory($email, 'Success', $log['message'], $reward);
                    
                    // Telegram Success
                    $tgMsg = "✅ <b>Claim Success</b>\n" .
                             "📧 Account: <code>$email</code>\n" .
                             "💰 Reward: <b>" . json_encode($reward) . "</b>\n" .
                             "⏰ Time: " . date('h:i A');
                    TelegramBot::send($tgMsg);

                    // Update next claim time to 1 hour from now (approx)
                    $log['nextClaimTime'] = (time() + 3600) * 1000;
                } else {
                    $msg = $claim['message'] ?? null;
                    if (!$msg) {
                        $msg = !empty($claim) ? json_encode($claim) : 'Empty response from API';
                    }
                    // Check for specific known messages
                    $isJoinQueue = stripos($msg, 'Join to queue') !== false;
                    $isFutureNext = $nextFrame > (time() * 1000);
                    $isTooEarly = stripos($msg, 'TOKEN_CLAIM_TOO_EARLY') !== false;

                    if ($isJoinQueue || $isFutureNext) {
                        $log['status'] = 'Success';
                        
                        if (stripos($msg, 'Empty response') !== false) {
                             $log['message'] = "Cycle Completed: " . $msg;
                             Utils::log('MINER', "Cycle Completed (Future/Queue) for $email", $msg);
                             $tgMsg = "ℹ️ <b>Cycle Verified</b>\n" .
                                     "📧 Account: <code>$email</code>\n" .
                                     "ℹ️ Info: $msg (Next claim in future)\n" .
                                     "⏰ Time: " . date('h:i A');
                        } else {
                            $log['message'] = "Claim Success: " . $msg;
                            Utils::log('MINER', "Claim Success (Queue/Future) for $email", $msg);
                            
                            $tgMsg = "⏳ <b>Joined Queue / Pre-Check</b>\n" .
                                     "📧 Account: <code>$email</code>\n" .
                                     "ℹ️ Info: $msg\n" .
                                     "⏰ Time: " . date('h:i A');
                        }

                        Utils::logHistory($email, 'Success', $log['message']);
                        Utils::updateAccountHealth($email, 'OK');

                        TelegramBot::send($tgMsg);

                    } elseif ($isTooEarly) {
                        $log['status'] = 'Skipped';
                        $log['message'] = "Token claim too early. Retrying in 15 minutes.";
                        
                        $debugInfo = json_encode($check['data'] ?? 'NoData');
                        Utils::log('MINER', "Claim Too Early for $email. Check data: $debugInfo", $msg);
                        
                        Utils::logHistory($email, 'Skipped', $log['message']);
                        
                        // Set next claim to 15 minutes from now
                        $log['nextClaimTime'] = (time() + 900) * 1000;
                        
                        $tgMsg = "ℹ️ <b>Claim Too Early</b>\n" .
                                 "📧 Account: <code>$email</code>\n" .
                                 "ℹ️ Info: Retrying in 15 min\n" .
                                 "⏰ Time: " . date('h:i A');
                        TelegramBot::send($tgMsg);

                    } else {
                        $log['status'] = 'Error';
                        $log['message'] = "Claim failed: " . $msg;
                        Utils::logHistory($email, 'Failed', $log['message']);

                        // Short Retry on Error (5-10 mins)
                        $retryMinutes = rand(5, 10);
                        $log['nextClaimTime'] = (time() + ($retryMinutes * 60)) * 1000;
                        Utils::log('MINER', "Error encountered for $email. Scheduling retry in $retryMinutes mins.", $msg);

                        $tgMsg = "❌ <b>Claim Failed</b>\n" .
                                 "📧 Account: <code>$email</code>\n" .
                                 "⚠️ Error: $msg\n" .
                                 "🔄 Retry: In $retryMinutes mins\n" .
                                 "⏰ Time: " . date('h:i A');
                        TelegramBot::send($tgMsg);
                    }
                }
            } else {
                // Convert timestamp (ms) to readable format
                $timeStr = $nextFrame > 0 ? date('h:i:s A', $nextFrame / 1000) : 'Unknown';
                $log['status'] = 'Skipped';
                $log['message'] = 'Not claimable yet. Next claim at: ' . $timeStr;
                Utils::logHistory($email, 'Skipped', $log['message']);
                Utils::updateAccountHealth($email, 'OK');
            }
            
        } catch (Exception $e) {
            $log['status'] = 'Error';
            $log['message'] = $e->getMessage();

            // Token expired / Unauthorized → Auto-delete the entire account
            if (stripos($e->getMessage(), 'Unauthorized') !== false || 
                stripos($e->getMessage(), 'Token Expired') !== false) {
                
                $log['status'] = 'Deleted';
                $log['message'] = $e->getMessage() . ' — account auto-deleted';
                Utils::log('MINER', "Unauthorized for $email — auto-deleting account");
                
                // Build enriched alert BEFORE deleting (data won't exist after)
                $tgMsg = $this->buildDeleteAlertMessage($email, $token, 'Unauthorized');
                
                // Full cleanup: Cronicle events, account, token, history
                Utils::deleteAccount($email);
                
                TelegramBot::send($tgMsg);
                
            } else {
                // Non-auth error: retry with delete button
                Utils::logHistory($email, 'Error', $e->getMessage());
                $retryMinutes = rand(5, 10);
                $log['nextClaimTime'] = (time() + ($retryMinutes * 60)) * 1000;
                
                $tgMsg = "⚠️ <b>Miner Exception</b>\n" .
                         "📧 Account: <code>$email</code>\n" .
                         "❌ Error: " . $e->getMessage() . "\n" .
                         "🔄 Retry: In $retryMinutes mins\n" .
                         "⏰ Time: " . date('h:i A');
                
                $baseUrl = Utils::getBaseUrl();
                $baseUrl = rtrim($baseUrl, '/');
                if (substr($baseUrl, -4) === '/api') {
                    $baseUrl = substr($baseUrl, 0, -4);
                }
                $deleteUrl = $baseUrl . '/api/telegram_delete_account.php?email=' . urlencode($email);
                
                $inlineKeyboard = [[
                    [
                        'text' => '🗑️ Delete Account',
                        'url' => $deleteUrl
                    ]
                ]];
                
                TelegramBot::sendWithButtons($tgMsg, $inlineKeyboard);
            }
        }
        
        return $log;
    }
}
