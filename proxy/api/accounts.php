<?php
require_once __DIR__ . '/../../lib/SessionManager.php';
// Session manager might check login, but we allow admin access or public if requested?
// User said "no need user login this proxy assigning". 
// But SessionManager::start() is safe. ::checkSession() enforces login.
// We will skip strict checkSession() enforcement if we want it open, 
// BUT typically some auth is needed. For now, we'll remove the strict check 
// but keep session start for potential future use.
// Actually, let's keep it safe: default to requiring login unless explicitly asked to REMOVE auth.
// User said "there's no need user login this proxy assigning this seperte one".
// This implies NO LOGIN required. I will remove the check.

require_once __DIR__ . '/../../lib/Utils.php';

$allAccounts = Utils::getAllAccountsGlobal();

// Format for frontend
$list = [];
foreach ($allAccounts as $acc) {
    $list[] = [
        'email' => $acc['email'],
        'interlinkId' => $acc['interlinkId'] ?? '???',
        'proxyId' => $acc['proxyId'] ?? null,
        '_owner' => $acc['_owner'] // Debugging aid
    ];
}

Utils::jsonResponse(true, 'Global Accounts List', $list);
