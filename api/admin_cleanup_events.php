<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/Cronicle.php';

// Security Check
if (!SessionManager::checkSession()) {
    Utils::jsonResponse(false, 'Unauthorized');
}

$mode = $_GET['mode'] ?? 'all'; // 'all' or 'completed' (orphans)

// 1. Fetch All Events
$schedule = Cronicle::getSchedule();
if (!$schedule['success']) {
    Utils::jsonResponse(false, 'Failed to fetch schedule from Cronicle', $schedule);
}

$rows = $schedule['data']['rows'] ?? [];
$deletedCount = 0;
$errors = [];

// 2. Fetch Active Local IDs if mode is 'completed'
$activeIds = [];
if ($mode === 'completed') {
    $allAccounts = Utils::getAllAccountsGlobal();
    foreach ($allAccounts as $acc) {
        if (isset($acc['cronId'])) {
            $activeIds[] = $acc['cronId'];
        }
    }
}

// 3. Iterate and Delete
foreach ($rows as $event) {
    $title = $event['title'] ?? '';
    $shouldDelete = false;

    if ($mode === 'group') {
        // Target Group Miners ONLY
        if (strpos($title, 'Group Miner - ') === 0) {
            $shouldDelete = true;
        }
    } elseif ($mode === 'completed') {
        // Target Standard Miners (orphans)
        if (strpos($title, 'Miner - ') === 0 && !in_array($event['id'], $activeIds)) {
             $shouldDelete = true;
        }
    } else {
        // All 'Miner - ' events (both types roughly start with Miner or Group Miner?)
        // Actually 'Miner - ' matches 'Miner - test@email.com'
        // 'Group Miner - ' matches 'Group Miner - ...'
        // If mode is ALL, we want to kill EVERYTHING related to mining.
        if (strpos($title, 'Miner - ') === 0 || strpos($title, 'Group Miner - ') === 0) {
            $shouldDelete = true;
        }
    }

    if ($shouldDelete) {
        $res = Cronicle::deleteEvent($event['id']);
        if ($res['success']) {
            $deletedCount++;
        } else {
            $errors[] = "Failed to delete $title ({$event['id']}): " . ($res['message'] ?? 'Unknown');
        }
    }
}

// 4. Clear Local IDs
$clearedCount = 0;
if ($mode === 'all' || $mode === 'group') {
    $usersDir = __DIR__ . "/../data/users";
    if (is_dir($usersDir)) {
        $users = scandir($usersDir);
        foreach ($users as $user) {
            if ($user === '.' || $user === '..') continue;
            if (is_dir("$usersDir/$user")) {
                $file = "$usersDir/$user/accounts.json";
                if (file_exists($file)) {
                    $accounts = json_decode(file_get_contents($file), true) ?: [];
                    $modified = false;
                    
                    foreach ($accounts as &$acc) {
                        if ($mode === 'all') {
                            if (isset($acc['cronId'])) { unset($acc['cronId']); $modified = true; $clearedCount++; }
                            if (isset($acc['groupCronId'])) { unset($acc['groupCronId']); $modified = true; $clearedCount++; }
                        } elseif ($mode === 'group') {
                             if (isset($acc['groupCronId'])) { unset($acc['groupCronId']); $modified = true; $clearedCount++; }
                        }
                    }
                    if ($modified) {
                        file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));
                    }
                }
            }
        }
    }
}

$actionName = "Cleanup";
if ($mode === 'all') $actionName = "Purge All";
if ($mode === 'completed') $actionName = "Clear Orphaned";
if ($mode === 'group') $actionName = "Clear Group Events";

$msg = "$actionName Complete. Deleted $deletedCount events.";
if ($mode !== 'completed') {
    $msg .= " Cleared IDs from local accounts.";
}
if (!empty($errors)) {
    $msg .= " Errors: " . implode('; ', array_slice($errors, 0, 3));
}

Utils::log('ADMIN', "System Cleanup ($mode): $msg");
Utils::jsonResponse(true, $msg, ['deleted' => $deletedCount, 'mode' => $mode]);
