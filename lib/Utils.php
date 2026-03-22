<?php

class Utils {
    
    // Ensure config is loaded for timezone
    private static $configLoaded = false;
    
    public static function loadConfig() {
        if (!self::$configLoaded) {
            $configPath = __DIR__ . '/../api/config.php';
            if (file_exists($configPath)) {
                require_once $configPath;
                self::$configLoaded = true;
            }
        }
    }
    
    public static function getBaseUrl() {
        self::loadConfig();
        
        // Use configured base URL if available
        if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
            return rtrim(APP_BASE_URL, '/');
        }
        
        // Auto-detect base URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Get directory path (remove filename)
        $dir = dirname($scriptName);
        $dir = ($dir === '/' || $dir === '\\') ? '' : $dir;
        
        return $protocol . '://' . $host . $dir;
    }
    
    public static function getUserPath($file, $username = null) {
        if (!$username) {
            if (!isset($_SESSION['username'])) {
                return null; 
            }
            $username = $_SESSION['username'];
        }
        
        $dir = __DIR__ . "/../data/users/$username";
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            if (!file_exists("$dir/accounts.json")) file_put_contents("$dir/accounts.json", "[]");
            if (!file_exists("$dir/tokens.json")) file_put_contents("$dir/tokens.json", "[]");
        }
        
        return "$dir/$file";
    }
    
    public static function getLogPath($username = null) {
        // Consolidated Log Path (System Log)
        $dir = __DIR__ . '/../data/logs';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        return "$dir/system.log";
    }

    public static function log($type, $message, $details = '', $username = null) {
        self::loadConfig(); // Ensure timezone
        $timestamp = date('Y-m-d h:i:s A');
        $logEntry = "[$timestamp] [$type] $message $details" . PHP_EOL;
        
        // Unified Logging: Write ONLY to Global System Log
        $systemLog = self::getLogPath();
        file_put_contents($systemLog, $logEntry, FILE_APPEND);

        // 1% Chance to cleanup old logs (GC)
        if (rand(1, 100) === 1) {
            self::cleanupLogs();
        }
    }

    public static function jsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    public static function getAccounts() {
        $file = self::getUserPath('accounts.json');
        if (!$file || !file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }
    
    public static function getTokens($username = null) {
        $file = self::getUserPath('tokens.json', $username);
        if (!$file || !file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }

    public static function saveToken($email, $token) {
        $file = self::getUserPath('tokens.json');
        if (!$file) return;

        $tokens = self::getTokens();
        
        // Remove existing token for email if exists
        $tokens = array_filter($tokens, function($t) use ($email) {
            return $t['email'] !== $email;
        });
        
        $tokens[] = ['email' => $email, 'token' => $token];
        file_put_contents($file, json_encode(array_values($tokens), JSON_PRETTY_PRINT));
    }

    public static function getTokenByEmail($email) {
        $tokens = self::getTokens();
        foreach ($tokens as $t) {
            if ($t['email'] === $email) return $t['token'];
        }
        return null;
    }
    
    public static function saveAccount($email, $passcode, $interlinkId) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        
        // Check if exists
        foreach ($accounts as $acc) {
            if ($acc['email'] === $email) return;
        }
        
        $accounts[] = ['email' => $email, 'passcode' => $passcode, 'interlinkId' => $interlinkId];
        file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
    }

    public static function getAccountByEmail($email) {
        $accounts = self::getAccounts();
        foreach ($accounts as $acc) {
            if ($acc['email'] === $email) return $acc;
        }
        return null;
    }

    public static function logHistory($email, $status, $message, $reward = null, $username = null) {
        $file = self::getUserPath('history.json', $username);
        if (!$file) return;

        if (!file_exists($file)) file_put_contents($file, "[]");

        $history = json_decode(file_get_contents($file), true) ?: [];
        
        // 1. Prune logs older than 24 hours
        $oneDayAgo = time() - (24 * 60 * 60);
        $history = array_filter($history, function($entry) use ($oneDayAgo) {
            $ts = strtotime($entry['timestamp']);
            // If timestamp parsing fails, remove it or keep it?
            // Safer to remove if really old or invalid
            return $ts !== false && $ts >= $oneDayAgo;
        });

        $entry = [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d h:i:s A'),
            'email' => $email,
            'status' => $status,
            'message' => $message,
            'reward' => $reward
        ];
        
        // Prepend to keep newest first
        array_unshift($history, $entry);
        
        // Limit history size per user
        if (count($history) > 500) {
            $history = array_slice($history, 0, 500);
        }

        file_put_contents($file, json_encode(array_values($history), JSON_PRETTY_PRINT));
        
        // Update Total Claims if success
        if ($status === 'Claimed' || $status === 'Success') {
            self::incrementClaimCount($email, $username);
        }
    }

    public static function incrementClaimCount($email, $username = null) {
        self::loadConfig(); // Ensure timezone
        $file = self::getUserPath('accounts.json', $username);
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                // Total Claims (Lifetime)
                if (!isset($acc['totalClaims'])) $acc['totalClaims'] = 0;
                $acc['totalClaims']++;
                
                // Daily Claims Logic
                $today = date('Y-m-d');
                if (!isset($acc['lastClaimDate']) || $acc['lastClaimDate'] !== $today) {
                    $acc['dailyClaims'] = 1;
                    $acc['lastClaimDate'] = $today;
                } else {
                    if (!isset($acc['dailyClaims'])) $acc['dailyClaims'] = 0;
                    $acc['dailyClaims']++;
                }
                
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    public static function getHistory($email = null) {
        $file = self::getUserPath('history.json');
        if (!$file || !file_exists($file)) return [];
        
        $history = json_decode(file_get_contents($file), true) ?: [];
        
        // Prune logic on read as well
        $oneDayAgo = time() - (24 * 60 * 60);
        $originalCount = count($history);
        $history = array_filter($history, function($entry) use ($oneDayAgo) {
            $ts = strtotime($entry['timestamp']);
            return $ts !== false && $ts >= $oneDayAgo;
        });
        
        // If we pruned something, save it back to keep file clean
        if (count($history) < $originalCount) {
             file_put_contents($file, json_encode(array_values($history), JSON_PRETTY_PRINT));
        }

        if ($email) {
            return array_filter($history, function($h) use ($email) {
                return $h['email'] === $email;
            });
        }
        
        return array_values($history);
    }

    public static function setActiveGroupMiner($email, $groupId, $isActive) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                // Mutual Exclusion: If enabling ($isActive=true), this group becomes the ONLY active one.
                // If disabling, we clear it only if it was this group (optional safety).
                // Simplified: Just set the property.
                if ($isActive) {
                     $acc['activeGroupMiner'] = $groupId;
                } else {
                    // If we are turning off, clear it.
                    // Check if we are turning off the currently active one
                    if (isset($acc['activeGroupMiner']) && $acc['activeGroupMiner'] === $groupId) {
                        $acc['activeGroupMiner'] = null;
                    }
                }
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
        return $updated;
    }

    public static function updateGroupCronId($email, $cronId) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;
        
        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                // If cronId is null, unset/remove it or set to null
                if ($cronId === null) {
                    $acc['groupCronId'] = null;
                } else {
                    $acc['groupCronId'] = $cronId;
                }
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    public static function updateAccountStatus($email, $isActive, $cronId = null) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                $acc['isActive'] = $isActive;
                if ($cronId !== null) {
                    $acc['cronId'] = $cronId;
                } elseif ($isActive === false && isset($acc['cronId'])) {
                    // Start fresh if disabling? Or keep ID? 
                    // Plan says: "Set cronId to null" on OFF.
                    // So if $cronId is passed as null explicitly? 
                    // Let's allow passing null to clear it safely if we want.
                    // But PHP null default implies "no change" usually in updates.
                    // Let's use specific logic: if $isActive is false, clear cronId unless we want to keep it?
                    // Actually, toggle_mining.php will pass null or the new ID. 
                    // Let's just update if provided, or clear if we want to clear.
                    // Let's change signature to be explicit.
                }
                
                // Better approach:
                // If turning ON, we pass new ID.
                // If turning OFF, we pass null (or empty) to clear it.
                // But wait, default $cronId = null prevents clearing if we don't pass it.
                // Let's make it optional but logic inside:
                if ($cronId !== null) {
                     $acc['cronId'] = $cronId;
                }
                // If explicit clear is needed, we might need a special value or just handle it in caller?
                // Actually, if we disable, we probably want to clear it. 
                // But let's stick to explicit:
                // Caller must pass 'false' to clear? No, string 'null'?
                // Let's change param default to 'NO_CHANGE' constant style string if needed, or just add a clear flag.
                // Simplest: pass $cronId. If it's 0 or 'CLEAR', clear it.
                
                if ($cronId === 'CLEAR') {
                    unset($acc['cronId']);
                }
                
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
        return $updated;
    }

    public static function updateAccountHealth($email, $healthStatus) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                $acc['health'] = $healthStatus; // 'OK' or 'EXPIRED'
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    public static function updateAccountBalance($email, $balanceData) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                $acc['cached_balance'] = $balanceData;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    public static function updateAccountNextClaim($email, $nextTs, $username = null) {
        $file = self::getUserPath('accounts.json', $username);
        if (!$file) return;

        $accounts = self::getAccounts(); // This might get WRONG accounts if we don't pass username to getAccounts either!
        // Wait, getAccounts() uses getUserPath('accounts.json') which uses session if no arg.
        // We need to fix that too or just read file manually here.
        // Let's use file_get_contents directly since getAccounts doesn't accept param yet?
        // Checking getAccounts line 74: public static function getAccounts() { ... } - No param!
        
        // Actually, let's fix getAccounts to take username too, it's safer.
        // But for minimal disturbance, let's just manual read here or use getUserPath directly.
        
        if (!file_exists($file)) return;
        $accounts = json_decode(file_get_contents($file), true) ?: [];
        
        $updated = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email) {
                $acc['next_claim'] = $nextTs; // Can be timestamp or string
                $updated = true;
                break;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    // -- Proxy Management --

    public static function getProxies() {
        $file = self::getUserPath('proxies.json');
        if (!$file) return [];
        if (!file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }

    public static function saveProxy($proxyData) {
        $file = self::getUserPath('proxies.json');
        if (!$file) return false;

        $proxies = self::getProxies();
        
        // Generate ID if new
        if (!isset($proxyData['id'])) {
            $proxyData['id'] = uniqid('px_');
        }

        // Update or Add
        $found = false;
        foreach ($proxies as &$p) {
            if ($p['id'] === $proxyData['id']) {
                $p = $proxyData;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $proxies[] = $proxyData;
        }

        file_put_contents($file, json_encode(array_values($proxies), JSON_PRETTY_PRINT));
        return $proxyData['id'];
    }

    public static function deleteProxy($proxyId) {
        $file = self::getUserPath('proxies.json');
        if (!$file) return false;

        $proxies = self::getProxies();
        $initialCount = count($proxies);
        
        $proxies = array_filter($proxies, function($p) use ($proxyId) {
            return $p['id'] !== $proxyId;
        });

        if (count($proxies) !== $initialCount) {
            file_put_contents($file, json_encode(array_values($proxies), JSON_PRETTY_PRINT));
            
            // Also remove from accounts
            self::unassignProxyFromAll($proxyId);
            return true;
        }
        return false;
    }

    public static function assignProxy($proxyId, $emails) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return false;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            // specific assignment
            if (in_array($acc['email'], $emails)) {
                $acc['proxyId'] = $proxyId;
                $updated = true;
            } 
            // If we are assigning to some, do we clear others? 
            // No, strictly assign to selected. Valid workflow.
            // But what if we want to UNASSIGN? 
            // We can pass proxyId = null.
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
        return true;
    }

    public static function unassignProxyFromAll($proxyId) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return;

        $accounts = self::getAccounts();
        $updated = false;

        foreach ($accounts as &$acc) {
            if (isset($acc['proxyId']) && $acc['proxyId'] === $proxyId) {
                unset($acc['proxyId']);
                $updated = true;
            }
        }

        if ($updated) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }
    }

    // -- Global / System-Wide Methods (Proxy Manager) --

    public static function getGlobalPath($file) {
        $dir = __DIR__ . "/../data";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        return "$dir/$file";
    }

    public static function getAllAccountsGlobal() {
        $usersDir = __DIR__ . "/../data/users";
        if (!is_dir($usersDir)) return [];
        
        $allAccounts = [];
        $users = scandir($usersDir);
        
        foreach ($users as $user) {
            if ($user === '.' || $user === '..') continue;
            if (is_dir("$usersDir/$user")) {
                $file = "$usersDir/$user/accounts.json";
                if (file_exists($file)) {
                    $json = json_decode(file_get_contents($file), true);
                    if ($json) {
                        foreach ($json as $acc) {
                            $acc['_owner'] = $user; // Track owner for saving back
                            $allAccounts[] = $acc;
                        }
                    }
                }
            }
        }
        return $allAccounts;
    }

    /**
     * Get proxy connection config for an account (by email).
     * Resolves proxyId → global proxy details.
     * Returns ['type','host','port','username','password'] or null if no proxy assigned.
     */
    public static function getProxyConfigForAccount($email) {
        $account = self::getAccountByEmail($email);
        if (!$account || empty($account['proxyId'])) {
            return null;
        }

        $proxyId = $account['proxyId'];
        $proxies = self::getGlobalProxies();

        foreach ($proxies as $proxy) {
            if (($proxy['id'] ?? '') === $proxyId) {
                return [
                    'type'     => $proxy['type'] ?? 'HTTP',
                    'host'     => $proxy['host'] ?? '',
                    'port'     => $proxy['port'] ?? '',
                    'username' => $proxy['username'] ?? '',
                    'password' => $proxy['password'] ?? '',
                ];
            }
        }

        return null; // proxyId assigned but proxy not found (deleted?)
    }

    /**
     * Migrate an account's device_info to use okhttp/4.12.0 User-Agent
     * if it still has the old Chrome-style UA. Returns true if migrated.
     */
    public static function migrateDeviceInfo($email) {
        $file = self::getUserPath('accounts.json');
        if (!$file || !file_exists($file)) return false;

        $accounts = json_decode(file_get_contents($file), true) ?: [];
        $changed = false;

        foreach ($accounts as &$acc) {
            if ($acc['email'] === $email && isset($acc['device_info'])) {
                $ua = $acc['device_info']['user-agent'] ?? '';
                // Detect old Chrome-style user agents
                if (stripos($ua, 'Mozilla') !== false || stripos($ua, 'Chrome') !== false) {
                    $acc['device_info']['user-agent'] = 'okhttp/4.12.0';
                    $changed = true;
                }
                break;
            }
        }
        unset($acc);

        if ($changed) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }

        return $changed;
    }

    /**
     * Migrate ALL accounts to okhttp/4.12.0 UA (batch migration).
     * Returns count of migrated accounts.
     */
    public static function migrateAllDeviceInfo() {
        $file = self::getUserPath('accounts.json');
        if (!$file || !file_exists($file)) return 0;

        $accounts = json_decode(file_get_contents($file), true) ?: [];
        $count = 0;

        foreach ($accounts as &$acc) {
            if (isset($acc['device_info'])) {
                $ua = $acc['device_info']['user-agent'] ?? '';
                if (stripos($ua, 'Mozilla') !== false || stripos($ua, 'Chrome') !== false) {
                    $acc['device_info']['user-agent'] = 'okhttp/4.12.0';
                    $count++;
                }
            }
        }
        unset($acc);

        if ($count > 0) {
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }

        return $count;
    }

    public static function getGlobalProxies() {
        $file = self::getGlobalPath('proxies.json');
        if (!file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }

    public static function saveGlobalProxy($proxyData) {
        $file = self::getGlobalPath('proxies.json');
        
        $proxies = self::getGlobalProxies();
        
        if (!isset($proxyData['id'])) {
            $proxyData['id'] = uniqid('px_');
        }

        // Update or Add
        $found = false;
        foreach ($proxies as &$p) {
            if ($p['id'] === $proxyData['id']) {
                $p = $proxyData;
                $found = true;
                break;
            }
        }
        
        if (!$found) $proxies[] = $proxyData;

        file_put_contents($file, json_encode(array_values($proxies), JSON_PRETTY_PRINT));
        return $proxyData['id'];
    }

    public static function assignGlobalProxy($proxyId, $emails) {
        $usersDir = __DIR__ . "/../data/users";
        if (!is_dir($usersDir)) return false;
        
        $users = scandir($usersDir);
        
        foreach ($users as $user) {
            if ($user === '.' || $user === '..') continue;
            if (is_dir("$usersDir/$user")) {
                $file = "$usersDir/$user/accounts.json";
                if (!file_exists($file)) continue;

                $accounts = json_decode(file_get_contents($file), true) ?: [];
                $modified = false;
                
                foreach ($accounts as &$acc) {
                    if (in_array($acc['email'], $emails)) {
                        $acc['proxyId'] = $proxyId;
                        $modified = true;
                    }
                }

                if ($modified) {
                    file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
                }
            }
        }
        return true;
    }

    public static function unassignGlobalProxyFromAll($proxyId) {
        $usersDir = __DIR__ . "/../data/users";
        if (!is_dir($usersDir)) return false;
        
        $users = scandir($usersDir);
        foreach ($users as $user) {
            if ($user === '.' || $user === '..') continue;
            if (is_dir("$usersDir/$user")) {
                $file = "$usersDir/$user/accounts.json";
                if (!file_exists($file)) continue;

                $accounts = json_decode(file_get_contents($file), true) ?: [];
                $updated = false;
                
                foreach ($accounts as &$acc) {
                    if (isset($acc['proxyId']) && $acc['proxyId'] === $proxyId) {
                        unset($acc['proxyId']);
                        $updated = true;
                    }
                }

                if ($updated) {
                    file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
                }
            }
        }
        return true;
    }

    public static function deleteGlobalProxy($id) {
        $file = self::getGlobalPath('proxies.json');
        $proxies = self::getGlobalProxies();
        
        $initialCount = count($proxies);
        $proxies = array_filter($proxies, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        
        if (count($proxies) < $initialCount) {
            file_put_contents($file, json_encode(array_values($proxies), JSON_PRETTY_PRINT));
            // Also unassign from all accounts
            self::unassignGlobalProxyFromAll($id);
            return true;
        }
        return false;
    }

    public static function getAllTokensGlobal() {
        $usersDir = __DIR__ . "/../data/users";
        if (!is_dir($usersDir)) return [];
        
        $allTokens = [];
        $users = scandir($usersDir);
        
        foreach ($users as $user) {
            if ($user === '.' || $user === '..') continue;
            if (is_dir("$usersDir/$user")) {
                $file = "$usersDir/$user/tokens.json";
                if (file_exists($file)) {
                    $json = json_decode(file_get_contents($file), true);
                    if ($json) {
                        foreach ($json as $t) {
                            $allTokens[$t['email']] = $t['token']; // Map email -> token
                        }
                    }
                }
            }
        }
        return $allTokens;
    }
    public static function cleanupLogs($username = null) {
        // 1. Cleanup Main App Log
        $logPath = self::getLogPath($username); // This gets current user's log or global if no session
        // If we want to clean GLOBAL logs, maybe we should check global path too? 
        // Current getLogPath heavily depends on session.
        // Let's clean the file returned by getLogPath.
        
        if (file_exists($logPath)) {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                $oneDayAgo = time() - (24 * 60 * 60);
                $newLines = [];
                $modified = false;
                
                foreach ($lines as $line) {
                    // Format: [2024-12-21 04:00:00 PM] ...
                    if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
                        $ts = strtotime($matches[1]);
                        if ($ts !== false && $ts >= $oneDayAgo) {
                            $newLines[] = $line;
                        } else {
                            $modified = true;
                        }
                    } else {
                        // Keep malformed lines or remove? Keep safe.
                        $newLines[] = $line;
                    }
                }
                
                if ($modified) {
                    file_put_contents($logPath, implode(PHP_EOL, $newLines) . PHP_EOL);
                }
            }
        }
        
        // 2. Cleanup History (Current User)
        $historyFile = self::getUserPath('history.json', $username);
        if ($historyFile && file_exists($historyFile)) {
             $history = json_decode(file_get_contents($historyFile), true) ?: [];
             $oneDayAgo = time() - (24 * 60 * 60);
             $initialCount = count($history);
             
             $history = array_filter($history, function($entry) use ($oneDayAgo) {
                $ts = strtotime($entry['timestamp']);
                return $ts !== false && $ts >= $oneDayAgo;
             });
             
             if (count($history) < $initialCount) {
                 file_put_contents($historyFile, json_encode(array_values($history), JSON_PRETTY_PRINT));
             }
        }
    }

    public static function getOrGenerateDeviceInfo($email) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return [];

        $accounts = self::getAccounts();
        $targetAcc = null;
        $idx = -1;

        foreach ($accounts as $i => $acc) {
            if ($acc['email'] === $email) {
                $targetAcc = $acc;
                $idx = $i;
                break;
            }
        }

        if ($targetAcc && isset($targetAcc['device_info'])) {
            return $targetAcc['device_info'];
        }

        // #8 — Expanded device pool (50+ realistic models) for fingerprint diversity
        // All brand↔model pairs are real device identifiers
        $devices = [
            // Xiaomi (12 models)
            ['brand' => 'xiaomi', 'model' => 'Redmi Note 5'],
            ['brand' => 'xiaomi', 'model' => 'Redmi Note 9 Pro'],
            ['brand' => 'xiaomi', 'model' => 'Redmi Note 10'],
            ['brand' => 'xiaomi', 'model' => 'Redmi Note 11'],
            ['brand' => 'xiaomi', 'model' => 'Redmi Note 12'],
            ['brand' => 'xiaomi', 'model' => 'POCO X3'],
            ['brand' => 'xiaomi', 'model' => 'POCO X5 Pro'],
            ['brand' => 'xiaomi', 'model' => 'POCO F4'],
            ['brand' => 'xiaomi', 'model' => 'POCO M5'],
            ['brand' => 'xiaomi', 'model' => 'Mi 11 Lite'],
            ['brand' => 'xiaomi', 'model' => 'Mi 12'],
            ['brand' => 'xiaomi', 'model' => '2201116SG'],
            // Samsung (12 models)
            ['brand' => 'samsung', 'model' => 'SM-G991B'],
            ['brand' => 'samsung', 'model' => 'SM-A525F'],
            ['brand' => 'samsung', 'model' => 'SM-G998B'],
            ['brand' => 'samsung', 'model' => 'SM-A546B'],
            ['brand' => 'samsung', 'model' => 'SM-A146P'],
            ['brand' => 'samsung', 'model' => 'SM-A536B'],
            ['brand' => 'samsung', 'model' => 'SM-S908B'],
            ['brand' => 'samsung', 'model' => 'SM-S911B'],
            ['brand' => 'samsung', 'model' => 'SM-A057F'],
            ['brand' => 'samsung', 'model' => 'SM-A346B'],
            ['brand' => 'samsung', 'model' => 'SM-M146B'],
            ['brand' => 'samsung', 'model' => 'SM-G781B'],
            // Google (5 models)
            ['brand' => 'google', 'model' => 'Pixel 6'],
            ['brand' => 'google', 'model' => 'Pixel 6a'],
            ['brand' => 'google', 'model' => 'Pixel 7'],
            ['brand' => 'google', 'model' => 'Pixel 7a'],
            ['brand' => 'google', 'model' => 'Pixel 8'],
            // OnePlus (5 models)
            ['brand' => 'oneplus', 'model' => 'OnePlus 9'],
            ['brand' => 'oneplus', 'model' => 'OnePlus 9 Pro'],
            ['brand' => 'oneplus', 'model' => 'OnePlus 10 Pro'],
            ['brand' => 'oneplus', 'model' => 'OnePlus Nord CE 3'],
            ['brand' => 'oneplus', 'model' => 'OnePlus 11'],
            // Realme (5 models)
            ['brand' => 'realme', 'model' => 'RMX3085'],
            ['brand' => 'realme', 'model' => 'RMX3511'],
            ['brand' => 'realme', 'model' => 'RMX3630'],
            ['brand' => 'realme', 'model' => 'RMX3710'],
            ['brand' => 'realme', 'model' => 'RMX3686'],
            // Vivo (4 models)
            ['brand' => 'vivo', 'model' => 'V2111'],
            ['brand' => 'vivo', 'model' => 'V2217'],
            ['brand' => 'vivo', 'model' => 'V2244'],
            ['brand' => 'vivo', 'model' => 'V2254'],
            // Oppo (4 models)
            ['brand' => 'oppo', 'model' => 'CPH2185'],
            ['brand' => 'oppo', 'model' => 'CPH2477'],
            ['brand' => 'oppo', 'model' => 'CPH2495'],
            ['brand' => 'oppo', 'model' => 'CPH2565'],
            // Motorola (3 models)
            ['brand' => 'motorola', 'model' => 'moto g82 5G'],
            ['brand' => 'motorola', 'model' => 'moto g73 5G'],
            ['brand' => 'motorola', 'model' => 'moto edge 40'],
        ];
        
        $choice = $devices[array_rand($devices)];
        $deviceId = bin2hex(random_bytes(8)); // 16 chars hex like 'd1ef45f2076a25ef'

        $info = [
            'x-unique-id' => $deviceId,
            'x-device-id' => $deviceId,
            'x-model' => $choice['model'],
            'x-brand' => $choice['brand'],
            'x-system-name' => 'Android',
            'x-bundle-id' => 'org.ai.interlinklabs.interlinkId',
            'user-agent' => 'okhttp/4.12.0',
            // Anti-bot: per-account timezone offset (minutes) for varied claim timing
            // Range: UTC-3 (-180) to UTC+5:30 (+330), in 30-min increments
            'timezone_offset' => array_rand(array_flip(range(-180, 330, 30)))
        ];

        // Save back if account exists
        if ($idx !== -1) {
            $accounts[$idx]['device_info'] = $info;
            file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        }

        return $info;
    }

    public static function rotateDeviceInfo($email) {
        $file = self::getUserPath('accounts.json');
        if (!$file) return false;

        $accounts = self::getAccounts();
        $idx = -1;
        $targetAcc = null;

        foreach ($accounts as $i => $acc) {
            if ($acc['email'] === $email) {
                $targetAcc = $acc;
                $idx = $i;
                break;
            }
        }

        if ($idx === -1 || !isset($targetAcc['device_info'])) {
            // No device info to rotate, generate new one
            return self::getOrGenerateDeviceInfo($email);
        }

        $info = $targetAcc['device_info'];
        
        // 1. Keep Hardware ID (x-unique-id) same to avoid "New Login" triggers if possible
        // 2. Rotate User-Agent to newer Chrome versions
        // Modern Chrome User Agents (Android 13/14)
        $modernUAs = [
            'Mozilla/5.0 (Linux; Android 13; SM-S908B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.101 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; M2101K6G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.6045.193 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; SM-A546B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.230 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; 2201116SG) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.5993.80 Mobile Safari/537.36'
        ];

        // Pick a new UA that is DIFFERENT from current
        $currentUA = $info['user-agent'] ?? '';
        $newUA = $currentUA;
        
        // Try to pick different one
        for ($k = 0; $k < 5; $k++) {
             $candidate = $modernUAs[array_rand($modernUAs)];
             if ($candidate !== $currentUA) {
                 $newUA = $candidate;
                 break;
             }
        }

        $info['user-agent'] = $newUA;
        
        // Update model info if needed based on UA? 
        // For simplicity, we keep the model name consistent (e.g. Pixel 6) but update the "software" (UA).
        // This looks like a software update on the same phone.
        
        $accounts[$idx]['device_info'] = $info;
        file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        
        return $info;
    }
    public static function deleteAccount($email, $username = null) {
        // 1. Get Accounts to find Cron IDs
        $file = self::getUserPath('accounts.json', $username);
        if (!$file || !file_exists($file)) return false;
        
        $accounts = json_decode(file_get_contents($file), true) ?: [];
        
        $targetAcc = null;
        foreach ($accounts as $acc) {
            if ($acc['email'] === $email) {
                $targetAcc = $acc;
                break;
            }
        }
        
        if (!$targetAcc) return false; // Not found
        
        // 2. Delete Cronicle Events
        require_once __DIR__ . '/Cronicle.php';
        
        // Main Miner
        if (isset($targetAcc['cronId']) && $targetAcc['cronId']) {
            Cronicle::deleteEvent($targetAcc['cronId']);
        }
        
        // Group Miner
        if (isset($targetAcc['groupCronId']) && $targetAcc['groupCronId']) {
            Cronicle::deleteEvent($targetAcc['groupCronId']);
        }
        
        // 3. Remove from accounts.json
        $accounts = array_filter($accounts, function($acc) use ($email) {
            return $acc['email'] !== $email;
        });
        // Reset keys
        $accounts = array_values($accounts);
        file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
        
        // 4. Remove from tokens.json
        $tokenFile = self::getUserPath('tokens.json', $username);
        if (file_exists($tokenFile)) {
            $tokens = json_decode(file_get_contents($tokenFile), true) ?: [];
            $tokens = array_filter($tokens, function($t) use ($email) {
                return $t['email'] !== $email;
            });
            file_put_contents($tokenFile, json_encode(array_values($tokens), JSON_PRETTY_PRINT));
        }
        
        // 5. Remove History
        $historyFile = self::getUserPath('history.json', $username);
        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true) ?: [];
            $history = array_filter($history, function($h) use ($email) {
                return $h['email'] !== $email;
            });
            file_put_contents($historyFile, json_encode(array_values($history), JSON_PRETTY_PRINT));
        }
        
        return true;
    }
}
