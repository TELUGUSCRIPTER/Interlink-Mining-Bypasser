<?php
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/InterlinkClient.php';
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$interlinkId = trim($input['interlinkId'] ?? '');
$otp = trim($input['otp'] ?? '');
$passcode = trim($input['passcode'] ?? '');

if (!$email || !$interlinkId || !$otp) {
    Utils::jsonResponse(false, 'Missing required fields');
}

// Load device info for this account (same fingerprint used during OTP request)
$deviceInfo = Utils::getOrGenerateDeviceInfo($email);
Utils::migrateDeviceInfo($email);
$proxyConfig = Utils::getProxyConfigForAccount($email);
$client = new InterlinkClient($deviceInfo, $proxyConfig);

try {
    // Step 1: Verify OTP (now includes deviceId)
    $response = $client->verifyOtp($interlinkId, $otp);
    
    if (isset($response['data']['jwtToken'])) {
        $token = $response['data']['jwtToken'];
        
        // Save Token
        Utils::saveToken($email, $token);
        
        // Save Account Info
        Utils::saveAccount($email, $passcode, $interlinkId);

        // Step 2: Post-login - Fetch full user context (matches real app flow)
        try {
            usleep(rand(200000, 500000)); // 0.2 - 0.5 second delay
            $userContext = $client->getCurrentUserFull($token);
            Utils::log('AUTH_DEBUG', "User context for $email", json_encode($userContext));
            
            // Cache useful data from user context
            if (isset($userContext['data'])) {
                $tokenData = $userContext['data']['token'] ?? null;
                if ($tokenData) {
                    Utils::updateAccountBalance($email, $tokenData);
                }
                // Cache claimable info
                $claimInfo = $userContext['data']['isClaimable'] ?? null;
                if ($claimInfo && isset($claimInfo['nextFrame'])) {
                    $nextTs = $claimInfo['nextFrame'];
                    if ($nextTs > 1000000000000) $nextTs = intval($nextTs / 1000);
                    $nextStr = date('h:i A', $nextTs);
                    Utils::updateAccountNextClaim($email, $nextStr);
                }
            }
        } catch (Exception $ctxErr) {
            // Non-critical - just log and continue
            Utils::log('AUTH_DEBUG', "User context fetch failed for $email (non-critical)", $ctxErr->getMessage());
        }
        
        Utils::jsonResponse(true, 'Account verified and saved', ['token' => $token]);
    } else {
        $msg = $response['message'] ?? 'Verification failed';
        Utils::jsonResponse(false, $msg, $response);
    }
} catch (Exception $e) {
    Utils::jsonResponse(false, 'Exception: ' . $e->getMessage());
}
