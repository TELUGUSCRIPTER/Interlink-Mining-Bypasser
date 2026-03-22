<?php
require_once __DIR__ . '/../../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../../lib/Utils.php';

// Session Check Removed

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    Utils::jsonResponse(false, 'Invalid Input');
}

// 1. Save Proxy
$proxyData = [
    'type' => $input['type'] ?? 'HTTP',
    'host' => $input['host'] ?? '',
    'port' => $input['port'] ?? '',
    'username' => $input['username'] ?? '',
    'password' => $input['password'] ?? '',
    'country' => $input['country'] ?? 'Unknown'
];

if (empty($proxyData['host']) || empty($proxyData['port'])) {
    Utils::jsonResponse(false, 'Host and Port required');
}

// Global Save
$proxyId = Utils::saveGlobalProxy($proxyData);

// 2. Assign to Accounts (Global)
$assignEmails = $input['assignTo'] ?? [];
if (!empty($assignEmails)) {
    Utils::assignGlobalProxy($proxyId, $assignEmails);
}

Utils::jsonResponse(true, 'Proxy Saved & Assigned', ['proxyId' => $proxyId]);
