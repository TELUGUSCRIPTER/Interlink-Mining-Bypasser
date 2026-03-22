<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/InterlinkClient.php';

$email = $_GET['email'] ?? '';

if (!$email) {
    Utils::jsonResponse(false, 'Email required');
}

$tokens = Utils::getTokens();
$tokenStr = null;
foreach ($tokens as $t) {
    if ($t['email'] === $email) {
        $tokenStr = $t['token'];
        break;
    }
}

if (!$tokenStr) {
    Utils::jsonResponse(false, 'Token not found for email');
}

// Load per-account device info for consistent fingerprint
$deviceInfo = Utils::getOrGenerateDeviceInfo($email);
$proxyConfig = Utils::getProxyConfigForAccount($email);
$client = new InterlinkClient($deviceInfo, $proxyConfig);
try {
    $balance = $client->getTokenBalance($tokenStr);
    $data = $balance['data'] ?? [];
    
    // Cache the balance
    Utils::updateAccountBalance($email, $data);

    Utils::jsonResponse(true, 'Balance retrieved', $data);
} catch (Exception $e) {
    Utils::jsonResponse(false, 'Error: ' . $e->getMessage());
}
