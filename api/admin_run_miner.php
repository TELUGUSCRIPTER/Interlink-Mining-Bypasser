<?php
require_once __DIR__ . '/../lib/SessionManager.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
set_time_limit(120); // Prevent PHP timeout during mining (sleep + cURL can exceed 30s default)

// Catch fatal errors and return as JSON instead of empty 500
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => "PHP Fatal: {$error['message']} in {$error['file']}:{$error['line']}"
        ]);
    }
});

SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/MiningService.php';
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
    Utils::log('Error', "Admin Miner: Token not found for $email", '', $owner);
    Utils::jsonResponse(false, 'Token not found');
}

$miningService = new MiningService();

try {
    // 2. Process Mining
    // Note: MiningService uses current session/env. 
    // Admin run should strictly use Admin logging? 
    // MiningService uses Utils::logHistory which defaults to file. 
    // But Admin run specifically asks for owner-based logging in previous code.
    // For V1 refactor, we accept that MiningService logs to GLOBAL history for now 
    // OR we modify MiningService to accept an 'owner' param.
    // Let's keep it simple: MiningService logs to the standard flow. 
    // If we need custom Admin logs, we add them here *in addition*.
    
    $log = $miningService->processAccount($email, $token);
    
    // 3. Schedule Logic
    
    // Update Next Claim in Local File (Admin Context)
    if (isset($log['nextClaimTime']) && $log['nextClaimTime'] > 0) {
        $nextTs = $log['nextClaimTime'];
        if ($nextTs > 1000000000000) $nextTs = intval($nextTs / 1000);
        $nextStr = date('h:i A', $nextTs);
        Utils::updateAccountNextClaim($email, $nextStr, $owner);
    }

    // Need to get User's account file manually since Utils::getAccountByEmail defaults to global/session
    $accountsFile = Utils::getUserPath('accounts.json', $owner);
    $cronId = null;
    $isActive = false;
    
    if (file_exists($accountsFile)) {
        $userAccounts = json_decode(file_get_contents($accountsFile), true) ?: [];
        foreach ($userAccounts as $acc) {
            if ($acc['email'] === $email) {
                $cronId = $acc['cronId'] ?? null;
                $isActive = $acc['isActive'] ?? false;
                break;
            }
        }
    }
    
    if ($isActive) {
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
         $userCode = $owner . "1"; // Weak auth, but legacy compatible
         $webhookUrl = "$baseUrl/api/miner.php?username=$owner&code=$userCode&email=" . urlencode($email);
         
         $nextTs = $log['nextClaimTime'] ?? 0;
         
         // Use SchedulerService
         SchedulerService::scheduleNextRun($email, $cronId, $nextTs, $webhookUrl, $owner);
    }
    
    Utils::jsonResponse(true, $log['message'], $log);

} catch (Exception $e) {
    Utils::jsonResponse(false, $e->getMessage());
}
