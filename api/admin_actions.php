<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    Utils::jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'delete_user') {
    $username = $input['username'] ?? '';
    // Prevent deleting self (admin) if logic allows, but admin is hardcoded not in data/users usually?
    // Actually our admin login uses hardcoded logic, separate from data/users. 
    // BUT if 'teluguscripter' exists as a normal user too, we might want to be careful.
    
    if (!$username) Utils::jsonResponse(false, 'Username required');

    $userDir = __DIR__ . '/../data/users/' . $username;
    if (is_dir($userDir)) {
        
        // 1. Cleanup Cronicle Events for this user
        $accFile = "$userDir/accounts.json";
        require_once __DIR__ . '/../lib/Cronicle.php'; // Ensure Cronicle lib is loaded
        
        if (file_exists($accFile)) {
            $accounts = json_decode(file_get_contents($accFile), true) ?: [];
            foreach ($accounts as $acc) {
                // Delete Miner Schedule
                if (isset($acc['cronId'])) {
                    Cronicle::deleteEvent($acc['cronId']);
                }
                // Delete Group Miner Schedule
                if (isset($acc['groupCronId'])) {
                    Cronicle::deleteEvent($acc['groupCronId']);
                }
            }
        }

        // 2. Recursive Delete Directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($userDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        if (rmdir($userDir)) {
             Utils::jsonResponse(true, "User $username and all associated data/schedules deleted successfully");
        } else {
             Utils::jsonResponse(false, "Failed to delete user directory");
        }
    } else {
        Utils::jsonResponse(false, "User not found");
    }

} elseif ($action === 'delete_account') {
    $owner = $input['owner'] ?? '';
    $email = $input['email'] ?? '';

    if (!$owner || !$email) Utils::jsonResponse(false, 'Owner and Email required');

    // Use the comprehensive deleteAccount utility which cleans up:
    // 1. Cronicle events (both miner & group miner crons)
    // 2. accounts.json entry
    // 3. tokens.json entry
    // 4. history.json entries
    // Only the specified account is affected — no other accounts are touched.
    try {
        $result = Utils::deleteAccount($email, $owner);
        if ($result) {
            Utils::log('ADMIN', "Deleted account $email (owner: $owner) — all crons, tokens, history cleaned");
            Utils::jsonResponse(true, "Account $email fully deleted (crons, tokens, history removed)");
        } else {
            Utils::jsonResponse(false, "Account not found for user $owner");
        }
    } catch (Exception $e) {
        Utils::log('ADMIN_ERROR', "Failed to delete $email: " . $e->getMessage());
        Utils::jsonResponse(false, 'Deletion failed: ' . $e->getMessage());
    }

} elseif ($action === 'toggle_mining') {
    $owner = $input['owner'] ?? '';
    $email = $input['email'] ?? '';
    $enable = !empty($input['enable']);

    if (!$owner || !$email) Utils::jsonResponse(false, 'Owner and Email required');

    $accFile = __DIR__ . '/../data/users/' . $owner . '/accounts.json';
    if (!file_exists($accFile)) Utils::jsonResponse(false, 'User data not found');

    require_once __DIR__ . '/../lib/Cronicle.php';
    $accounts = json_decode(file_get_contents($accFile), true) ?: [];
    $found = false;

    foreach ($accounts as &$acc) {
        if ($acc['email'] !== $email) continue;
        $found = true;

        if ($enable) {
            $acc['isActive'] = true;
        } else {
            if (!empty($acc['cronId'])) {
                Cronicle::deleteEvent($acc['cronId']);
                Utils::log('ADMIN', "Removed miner cron {$acc['cronId']} for $email");
            }
            if (!empty($acc['groupCronId'])) {
                Cronicle::deleteEvent($acc['groupCronId']);
                Utils::log('ADMIN', "Removed group cron {$acc['groupCronId']} for $email");
            }
            $acc['isActive'] = false;
            unset($acc['cronId'], $acc['groupCronId']);
            $acc['activeGroupMiner'] = null;
        }
        break;
    }

    if ($found) {
        file_put_contents($accFile, json_encode(array_values($accounts), JSON_PRETTY_PRINT));
        $msg = $enable ? "Auto-mining enabled for $email" : "Auto-mining disabled for $email (all crons removed)";
        Utils::jsonResponse(true, $msg);
    } else {
        Utils::jsonResponse(false, 'Account not found');
    }

} else {
    Utils::jsonResponse(false, 'Invalid action');
}
