<?php
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/SessionManager.php';

// Ensure config is loaded (via Utils or directly)
Utils::loadConfig();

$check = SessionManager::checkSession();
if ($check !== true) {
    Utils::jsonResponse(false, 'Unauthorized');
}

$user = SessionManager::getUser();
$username = $user['username'] ?? $user['email'];

$accounts = Utils::getAccounts();
$totalDailyClaims = 0;
$totalLifetimeClaims = 0;
$totalITLG = 0.0;
$activeMiners = 0;
$today = date('Y-m-d');

foreach ($accounts as $acc) {
    if ($acc['isActive'] ?? false) {
        $activeMiners++;
    }

    // Daily Claims
    if (isset($acc['lastClaimDate']) && $acc['lastClaimDate'] === $today) {
        $totalDailyClaims += ($acc['dailyClaims'] ?? 0);
    }
    
    // Lifetime Claims
    $totalLifetimeClaims += ($acc['totalClaims'] ?? 0);

    // ITLG Coins (Gold)
    if (isset($acc['cached_balance']) && isset($acc['cached_balance']['interlinkGoldTokenAmount'])) {
        // Remove commas if any, though usually float/int
        $val = str_replace(',', '', $acc['cached_balance']['interlinkGoldTokenAmount']);
        $totalITLG += (float)$val;
    }
}

Utils::jsonResponse(true, 'Stats fetched', [
    'totalDailyClaims' => $totalDailyClaims, // Keep for backward compat if needed
    'activeMiners' => $activeMiners,
    'totalLifetimeClaims' => $totalLifetimeClaims,
    'totalITLG' => number_format($totalITLG, 2)
]);
