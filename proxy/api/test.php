<?php
require_once __DIR__ . '/../../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../../lib/Utils.php';
require_once __DIR__ . '/../../lib/ProxyTester.php';

// Session Check Removed for Global Access
// if (!isset($_SESSION['username'])) { Utils::jsonResponse(false, 'Unauthorized'); }

// Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    Utils::jsonResponse(false, 'Invalid JSON Input');
}

$type = $input['type'] ?? 'HTTP';
$host = $input['host'] ?? '';
$port = $input['port'] ?? '';
$user = $input['username'] ?? null;
$pass = $input['password'] ?? null;

// Basic Validation
if (empty($host) || empty($port)) {
    Utils::jsonResponse(false, 'Host and Port are required');
}

// Test Proxy
$result = ProxyTester::test($type, $host, $port, $user, $pass);

if ($result['success']) {
    Utils::jsonResponse(true, $result['message'], [
        'latency' => $result['latency'],
        'ip_data' => $result['data'] ?? []
    ]);
} else {
    Utils::jsonResponse(false, $result['message']);
}
