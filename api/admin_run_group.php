<?php
require_once __DIR__ . '/../lib/SessionManager.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/InterlinkClient.php';
require_once __DIR__ . '/../lib/SchedulerService.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    Utils::jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$owner = $input['owner'] ?? '';
$email = $input['email'] ?? '';

if (!$owner || !$email) {
    Utils::jsonResponse(false, 'Missing owner or email');
}

// 1. Get Token
$tokens = Utils::getTokens($owner);
$token = null;
foreach ($tokens as $t) {
    if ($t['email'] === $email) {
        $token = $t['token'];
        break;
    }
}

if (!$token) {
    Utils::jsonResponse(false, 'Token not found');
}

// 2. Check Active Group
$account = null;
$userAccounts = Utils::getUserPath('accounts.json', $owner);
if (file_exists($userAccounts)) {
    $accs = json_decode(file_get_contents($userAccounts), true) ?: [];
    foreach ($accs as $a) {
        if ($a['email'] === $email) {
            $account = $a;
            break;
        }
    }
}

$activeGroup = $account['activeGroupMiner'] ?? null;
if (!$activeGroup) {
    Utils::jsonResponse(false, 'No active group miner scheduled for this account');
}

try {
    // 3. Process Group Mining
    // Use InterlinkClient directly
    $deviceInfo = $account['device_info'] ?? Utils::getOrGenerateDeviceInfo($email);
    
    if (!$deviceInfo || empty($deviceInfo)) {
         $deviceInfo = [];
    }

    // Load proxy for this account
    $proxyConfig = Utils::getProxyConfigForAccount($email);

    $client = new InterlinkClient($deviceInfo, $proxyConfig);
    $res = $client->claimGroupMining($token, $activeGroup);
    
    $message = '';
    $nextClaim = 0;
    
    if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
        $msg = $res['message'] ?? 'Unknown Error';
        
        // Try to decode if it is a JSON string
        if (is_string($msg)) {
             $decoded = json_decode($msg, true);
             if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
                 $msg = $decoded['message'];
             }
        } elseif (is_array($msg) || is_object($msg)) {
             // If array, try to get message field
             $msgArray = (array)$msg;
             $msg = $msgArray['message'] ?? json_encode($msg);
        }
        
        $message = "Error: $msg";
        
        // Refresh list to get next claim
        $listRes = $client->getGroupList($token);
        $nextClaim = $listRes['data']['nextTimeClaim'] ?? 0;
        
        // Schedule Next Run even on failure
         // Construct Webhook (reused logic)
        if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
            $baseUrl = rtrim(APP_BASE_URL, '/');
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $apiDir = dirname($_SERVER['PHP_SELF']); 
            $basePath = dirname($apiDir);
            $basePath = str_replace('\\', '/', $basePath);
            $baseUrl = "$protocol$host$basePath";
        }
        $userCode = $owner . "1"; 
        $webhookUrl = "$baseUrl/api/group_miner.php?username=$owner&code=$userCode&email=" . urlencode($email);

        SchedulerService::scheduleNextRun(
            $email, $cronId, $nextClaim, $webhookUrl, $owner, "Group Miner - $email", "group"
        );
        
        // RETURN FALSE for failure
        Utils::jsonResponse(false, $message, ['nextClaim' => $nextClaim]);
        
    } else {
        $reward = $res['data']['totalReward'] ?? 'Unknown';
        $message = "Reward: $reward"; // Just show reward
        
        Utils::logHistory($email, 'Group Claimed (Admin)', "$message", $reward, $owner);
        
        $listRes = $client->getGroupList($token);
        $nextClaim = $listRes['data']['nextTimeClaim'] ?? 0;
        
        // Schedule Logic (Success Case)
         // Construct Webhook
        if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
            $baseUrl = rtrim(APP_BASE_URL, '/');
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $apiDir = dirname($_SERVER['PHP_SELF']); 
            $basePath = dirname($apiDir);
            $basePath = str_replace('\\', '/', $basePath);
            $baseUrl = "$protocol$host$basePath";
        }
        $userCode = $owner . "1"; 
        $webhookUrl = "$baseUrl/api/group_miner.php?username=$owner&code=$userCode&email=" . urlencode($email);

        SchedulerService::scheduleNextRun(
            $email, $cronId, $nextClaim, $webhookUrl, $owner, "Group Miner - $email", "group"
        );

        Utils::jsonResponse(true, $message, ['nextClaim' => $nextClaim]);
    }

} catch (Exception $e) {
    Utils::jsonResponse(false, $e->getMessage());
}
