<?php
// Prevent HTML error output which breaks JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/logs/php-error.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

try {
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

    // Release session lock so we don't block other requests (like log streaming)
    session_write_close();

    require_once __DIR__ . '/../lib/Utils.php';
    require_once __DIR__ . '/../lib/InterlinkClient.php'; // Ensure Client is loaded
    require_once __DIR__ . '/../lib/MiningService.php';
    require_once __DIR__ . '/../lib/SchedulerService.php';

    Utils::log('DEBUG', "Miner started for " . ($_GET['email'] ?? 'Unknown'));

    // Increase time limit
    set_time_limit(300);

    if (isset($isCron) && $isCron) {
        // Anti-bot: randomize initial cron trigger timing (10-60s)
        sleep(rand(10, 60));
    }

    $tokens = Utils::getTokens();
    $accounts = Utils::getAccounts();
    $miningService = new MiningService();
    $results = [];

    // Index active status
    $accountStatus = [];
    foreach ($accounts as $acc) {
        $accountStatus[$acc['email']] = $acc['isActive'] ?? false;
    }

    $targetEmail = $_GET['email'] ?? null;

    // Determine Base URL for Webhooks
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

    foreach ($tokens as $t) {
        $email = $t['email'];
        $isActive = $accountStatus[$email] ?? false;

        // Filter Logic
        if ($isCron) {
             if (!$isActive) continue;
             if ($targetEmail && $email !== $targetEmail) continue;
        } else {
            if ($targetEmail && $email !== $targetEmail) continue;
        }
        
        $tokenStr = $t['token'];
        
        // Anti-bot: random delay between accounts (human-like pacing)
        static $accountCount = 0;
        if ($accountCount > 0) {
            $delay = rand(2, 5); // 2-5 seconds between accounts
            sleep($delay);
        }
        $accountCount++;
        
        // 1. Process Mining (each account uses its own device fingerprint + proxy internally)
        $log = $miningService->processAccount($email, $tokenStr);
        $log['isAutoMinerActive'] = $isActive;
        
        // Update Next Claim in Local File
        if (isset($log['nextClaimTime']) && $log['nextClaimTime'] > 0) {
            $nextTs = $log['nextClaimTime'];
            if ($nextTs > 1000000000000) $nextTs = intval($nextTs / 1000);
            $nextStr = date('h:i A', $nextTs);
            Utils::updateAccountNextClaim($email, $nextStr);
        }
        
        // 2. Schedule Next Run
        $shouldSchedule = ($log['status'] === 'Claimed' || $log['status'] === 'Success' || $log['status'] === 'Skipped' || $log['status'] === 'Error');
        
        Utils::log('DEBUG', "Miner finished for $email. Active: " . ($isActive ? 'YES' : 'NO') . ", Schedule Trigger: " . ($shouldSchedule ? 'YES' : 'NO'));

        if ($isActive && $shouldSchedule) {
            $accInfo = Utils::getAccountByEmail($email);
            $cronId = $accInfo['cronId'] ?? null;
            
            $nextTs = $log['nextClaimTime'] ?? 0;
            
            $webhookUrl = "$baseUrl/api/miner.php?username=" . $_SESSION['username'] . "&code=" . $_SESSION['username'] . "1&email=" . urlencode($email);
            
            SchedulerService::scheduleNextRun($email, $cronId, $nextTs, $webhookUrl, $_SESSION['username']);
        }

        $results[] = $log;
        // Anti-bot: random delay between accounts to avoid burst patterns (5-30s)
        sleep(rand(5, 30));
    }

    Utils::jsonResponse(true, 'Mining cycle completed', $results);

} catch (Throwable $e) {
    // Catch any Fatal Error or Exception
    if (!class_exists('Utils')) {
        // Fallback JSON if Utils failed to load
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Critical Error: ' . $e->getMessage()]);
        exit;
    }
    Utils::jsonResponse(false, 'Miner Error: ' . $e->getMessage());
}
