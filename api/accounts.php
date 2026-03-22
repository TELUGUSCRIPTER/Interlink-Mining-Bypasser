<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/InterlinkClient.php';

// Allows fetching basic list of accounts for the dashboard
// Optionally, if ?refresh=1 is passed, it could cycle through and update something, 
// but we'll stick to just reading the tokens.json for the list.

$tokens = Utils::getTokens();
$accounts = Utils::getAccounts();

// Merge data
$list = [];
foreach ($tokens as $t) {
    $email = $t['email'];
    $acc = Utils::getAccountByEmail($email);
    $list[] = [
        'email' => $email,
        'has_token' => true,
        'passcode' => $acc['passcode'] ?? '???',
        'interlinkId' => $acc['interlinkId'] ?? '???',
        'isActive' => $acc['isActive'] ?? false,
        'totalClaims' => $acc['totalClaims'] ?? 0,
        'proxyId' => $acc['proxyId'] ?? null,
        'cached_balance' => $acc['cached_balance'] ?? null,
        'next_claim' => $acc['next_claim'] ?? null
    ];
}

Utils::jsonResponse(true, 'Accounts retrieved', $list);
