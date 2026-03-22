<?php
require_once __DIR__ . '/../lib/SessionManager.php';
// Disable output of errors to avoid breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    Utils::jsonResponse(false, 'Unauthorized');
}

// Global Stats
$stats = [
    'total_accounts' => 0,
    'active_miners' => 0,
    'total_claims_today' => 0,
    'graph_24h' => [],
    'interlink_accounts' => [],
    'users' => []
];

// Initialize Graph Buckets (24h)
$buckets = [];
$now = time();
for ($i = 23; $i >= 0; $i--) {
    $t = $now - ($i * 3600);
    $key = date('d-H', $t); 
    $buckets[$key] = ['label' => date('H:00', $t), 'count' => 0];
}

$usersDir = __DIR__ . '/../data/users';
if (is_dir($usersDir)) {
    $userFolders = scandir($usersDir);
    
    foreach ($userFolders as $uDir) {
        if ($uDir === '.' || $uDir === '..') continue;
        $path = $usersDir . '/' . $uDir;
        if (!is_dir($path)) continue;

        $username = $uDir;
        
        // 1. Get Accounts
        $accFile = $path . '/accounts.json';
        $userAccounts = [];
        if (file_exists($accFile)) {
            $userAccounts = json_decode(file_get_contents($accFile), true) ?: [];
        }
        
        // Stats
        $stats['total_accounts'] += count($userAccounts);
        foreach ($userAccounts as $acc) {
            if (isset($acc['isActive']) && $acc['isActive']) {
                $stats['active_miners']++;
            }
            
            // Add to Global List
            $stats['interlink_accounts'][] = [
                'owner' => $username,
                'email' => $acc['email'],
                'isActive' => $acc['isActive'] ?? false,
                'hasGroupMiner' => !empty($acc['activeGroupMiner']),
                'health' => $acc['health'] ?? 'UNKNOWN',
                'interlinkId' => $acc['interlinkId'] ?? 'N/A'
            ];
        }

        // 2. Get History (Claims)
        $histFile = $path . '/history.json';
        if (file_exists($histFile)) {
            $history = json_decode(file_get_contents($histFile), true) ?: [];
            foreach ($history as $entry) {
                if (!isset($entry['timestamp']) || !isset($entry['status'])) continue;
                
                $status = $entry['status'];
                if ($status !== 'Success' && $status !== 'Claimed') continue;

                $ts = strtotime($entry['timestamp']);
                if (!$ts) continue;

                // Total Claims Today (Since midnight)
                if ($ts >= strtotime('today midnight')) {
                    $stats['total_claims_today']++;
                }

                // Graph Data (Last 24h)
                if ($ts >= ($now - 24 * 3600)) {
                    $key = date('d-H', $ts);
                    if (isset($buckets[$key])) {
                        $buckets[$key]['count']++;
                    }
                }
            }
        }
        
        // 3. User Info
        $stats['users'][] = [
            'username' => $username,
            'accounts_count' => count($userAccounts),
            'joined' => date('Y-m-d', filectime($path)) // Approx creation time
        ];
    }
}

$stats['graph_24h'] = array_values($buckets);

Utils::jsonResponse(true, 'Admin Data', $stats);
