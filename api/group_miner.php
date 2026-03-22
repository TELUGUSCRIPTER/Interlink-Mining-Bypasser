<?php
// Prevent HTML error output which breaks JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/logs/php-error.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

try {
    // Auth similar to miner.php for Cronicle webhooks
    if (!isset($_SESSION['username']) && isset($_GET['username']) && isset($_GET['code'])) {
        $u = $_GET['username'];
        $c = $_GET['code'];
        if ($c === $u . "1") {
            $_SESSION['username'] = $u; // Set session for Utils to work
            $isCron = true;
        } else {
            throw new Exception('Invalid credentials');
        }
    }

    // Release session lock
    session_write_close();

    require_once __DIR__ . '/../lib/Utils.php';
    require_once __DIR__ . '/../lib/InterlinkClient.php';
    require_once __DIR__ . '/../lib/SchedulerService.php';

    $targetEmail = $_GET['email'] ?? null;
    if (!$targetEmail) throw new Exception("Email required");

    Utils::log('DEBUG', "Group Miner started for $targetEmail");
    set_time_limit(300);

    // Get Account & Token
    $token = Utils::getTokenByEmail($targetEmail);
    if (!$token) throw new Exception("Token not found for $targetEmail");

    $account = Utils::getAccountByEmail($targetEmail);
    $activeGroup = $account['activeGroupMiner'] ?? null;

    if (!$activeGroup) {
        Utils::log('DEBUG', "Group Miner stopped for $targetEmail: No active group set.");
        // Should we delete the cron job here if it exists? Maybe safer to just exit.
        // It might be turned off but job wasn't deleted for some reason.
        exit;
    }

    if (isset($isCron)) {
        // Anti-bot: randomize initial trigger timing (10-60s)
        sleep(rand(10, 60));
    }

    // Get Device Info
    $deviceInfo = Utils::getOrGenerateDeviceInfo($targetEmail);
    Utils::migrateDeviceInfo($targetEmail);
    $proxyConfig = Utils::getProxyConfigForAccount($targetEmail);
    $client = new InterlinkClient($deviceInfo, $proxyConfig);
    
    // Perform Claim
    $res = $client->claimGroupMining($token, $activeGroup);
    
    $status = 'Success';
    $nextClaim = 0;
    
    if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
        $status = 'Error';
        $msg = $res['message'] ?? 'Unknown Error';
        Utils::log('GROUP_MINER', "Claim failed for $targetEmail (Group: $activeGroup): $msg");
        
        $listRes = $client->getGroupList($token);
        $nextClaim = $listRes['data']['nextTimeClaim'] ?? 0;
        
    } else {
        // Success
        $reward = $res['data']['totalReward'] ?? 'Unknown';
        Utils::log('GROUP_MINER', "Claim Success for $targetEmail (Group: $activeGroup). Reward: $reward");
        Utils::logHistory($targetEmail, 'Group Claimed', "Group: $activeGroup", $reward, $_SESSION['username']);
        
        $listRes = $client->getGroupList($token);
        $nextClaim = $listRes['data']['nextTimeClaim'] ?? 0;
    }
    
    // Reschedule
    // Determine Base URL
    if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
        $baseUrl = rtrim(APP_BASE_URL, '/');
    } else {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https://';
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        }
        $host = $_SERVER['HTTP_HOST'];
        $currentDir = dirname($_SERVER['PHP_SELF']); 
        $rootDir = dirname(dirname($currentDir)); 
        $baseUrl = "$protocol$host$rootDir";
    }
    
    if (substr($baseUrl, -1) === '/') $baseUrl = rtrim($baseUrl, '/');

    // Webhook URL
    $webhookUrl = "$baseUrl/InterLink/api/group_miner.php?username=" . $_SESSION['username'] . "&code=" . $_SESSION['username'] . "1&email=" . urlencode($targetEmail);

    $cronId = $account['groupCronId'] ?? null;
    
    // Schedule
    SchedulerService::scheduleNextRun(
        $targetEmail, 
        $cronId, 
        $nextClaim, 
        $webhookUrl, 
        $_SESSION['username'],
        "Group Miner - $targetEmail",
        "group" // Type specified
    );

    // Output for Cronicle log
    echo "Group Mining Completed. Status: $status. Next Run: " . date('Y-m-d H:i:s', $nextClaim/1000);
    
} catch (Throwable $e) {
    Utils::log('GROUP_MINER', "Critical Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
