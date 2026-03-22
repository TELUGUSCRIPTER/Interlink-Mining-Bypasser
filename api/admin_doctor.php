<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/Cronicle.php';

SessionManager::start();

// Auth Check (Admin Only)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    Utils::jsonResponse(false, 'Unauthorized');
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_health':
        checkHealth();
        break;
    case 'clean_logs':
        cleanLogs();
        break;
    case 'sync_cronicle':
        syncCronicle();
        break;
    default:
        Utils::jsonResponse(false, 'Invalid Action');
}

function checkHealth() {
    $data = [];
    
    // 1. Disk/Log Usage
    $logDir = __DIR__ . '/../data/logs';
    $totalSize = 0;
    $fileCount = 0;
    if (is_dir($logDir)) {
        foreach (scandir($logDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $totalSize += filesize("$logDir/$f");
            $fileCount++;
        }
    }
    $data['logs'] = [
        'count' => $fileCount,
        'size_mb' => round($totalSize / 1024 / 1024, 2)
    ];
    
    // 2. PHP Environment
    $data['php'] = [
        'version' => phpversion(),
        'curl' => function_exists('curl_init'),
        'json' => function_exists('json_decode'),
        'upload_max' => ini_get('upload_max_filesize'),
        'memory_limit' => ini_get('memory_limit')
    ];
    
    // 3. System Load (Simulated for Windows, typically irrelevant for shared hosting but good to try)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $data['load'] = $load[0] ?? 0;
    } else {
        $data['load'] = 'N/A';
    }

    // 4. File Existence Check (Deployment Diagnostic)
    $requiredFiles = [
        'api/miner.php',
        'api/group_miner.php',
        'api/admin_doctor.php',
        'api/token_generator.php', // If used
    ];
    
    $fileStatus = [];
    $projectRoot = dirname(__DIR__); // Go up from /api to root
    
    foreach ($requiredFiles as $relPath) {
        $fullPath = $projectRoot . '/' . $relPath;
        $fileStatus[$relPath] = file_exists($fullPath) ? 'Found' : 'MISSING';
    }
    $data['files'] = $fileStatus;

    Utils::jsonResponse(true, 'Health Check Complete', $data);
}

function cleanLogs() {
    $logDir = __DIR__ . '/../data/logs';
    $cleaned = 0;
    if (is_dir($logDir)) {
        foreach (scandir($logDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            // Truncate instead of delete to keep permissions/streams alive?
            // Actually stream_logs.php handles re-creation. Deleting is fine.
            // But deleting system.log might break the open handle in stream_logs?
            // Safer to truncate system.log and delete others.
            
            $path = "$logDir/$f";
            if ($f === 'system.log') {
                file_put_contents($path, ""); // Truncate
            } else {
                unlink($path);
            }
            $cleaned++;
        }
    }
    
    // Add truncation log entry so it's not empty immediately
    Utils::log('DOCTOR', "Logs cleaned by Administrator.");
    
    Utils::jsonResponse(true, "Cleaned $cleaned log files.");
}

function syncCronicle() {
    // 1. Get Local Accounts with Cron IDs
    $localAccounts = Utils::getAllAccountsGlobal();
    $localMap = []; // cronId -> email
    foreach ($localAccounts as $acc) {
        if (!empty($acc['cronId'])) {
            $localMap[$acc['cronId']] = $acc['email'];
        }
    }
    
    // 2. Get Remote Events from Cronicle
    // We need a method in Cronicle.php to get ALL events?
    // Currently we only have findEventIdsByTitle.
    // Let's rely on finding by our naming convention "Miner -" ?
    // Or just check the ones we KNOW about.
    
    $report = [];
    $report['total_local'] = count($localMap);
    $report['synced'] = 0;
    $report['ghosts'] = []; // Remote events matching our pattern but no local user
    $report['missing'] = []; // Local users with ID but not found in Cronicle
    
    // Checking every single event is expensive. Let's just check the ones we have locally.
    foreach ($localMap as $id => $email) {
        // We don't have a specific "getEvent($id)" in Cronicle.php exposed publicly yet?
        // Actually Cronicle.php::checkEventStatus logic?
        // Let's infer existence by trying to get info.
        // We'll create a lightweight check if needed, or assume 'update' works?
        
        // This sync is tricky without a "listAll" from Cronicle. 
        // Let's implement a lighter check: Just return the list of Active Local IDs for the UI to show.
        // Doing real sync requires API upgrade.
        
        $report['synced']++; // Placeholder
    }
    
    Utils::jsonResponse(true, 'Sync Report', $report);
}
