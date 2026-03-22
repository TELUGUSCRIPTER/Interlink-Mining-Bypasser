<?php
require_once __DIR__ . '/../../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../../lib/Utils.php';

// Session Check Removed

// Use Global Methods
$proxies = Utils::getGlobalProxies();
$accounts = Utils::getAllAccountsGlobal();

// Map assignments
// Structure: [ ...proxyData, 'assigned_count' => 0, 'accounts' => [] ]

foreach ($proxies as &$p) {
    $p['assigned_count'] = 0;
    $p['accounts'] = [];
    
    foreach ($accounts as $acc) {
        if (isset($acc['proxyId']) && $acc['proxyId'] === $p['id']) {
            $p['assigned_count']++;
            $p['accounts'][] = $acc['email'];
        }
    }
}

Utils::jsonResponse(true, 'Proxies List', array_values($proxies));
