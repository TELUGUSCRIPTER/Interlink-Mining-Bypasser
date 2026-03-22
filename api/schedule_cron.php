<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/Cronicle.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../lib/TelegramBot.php';

// Endpoint Security
if (!isset($_SESSION['username'])) {
    Utils::jsonResponse(false, 'Unauthorized. Please login.');
}

$username = $_SESSION['username'];
$code = $username . "1"; // Security code logic

// Detect or Use Configured Host
if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
    $baseUrl = rtrim(APP_BASE_URL, '/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $currentUri = $_SERVER['REQUEST_URI'];
    $apiDir = dirname($_SERVER['PHP_SELF']); // /WebPanel/api
    $basePath = dirname($apiDir); // /WebPanel
    $basePath = str_replace('\\', '/', $basePath);
    $baseUrl = "$protocol$host$basePath";
}

$webhookUrl = "$baseUrl/api/miner.php?username=$username&code=$code";

// Payload
$timing = [
    'hours' => [0, 4, 8, 12, 16, 20],
    'minutes' => [0]
];

$result = Cronicle::createEvent("Interlink Miner - $username", $webhookUrl, $timing, APP_TIMEZONE);

if ($result['success']) {
    Utils::jsonResponse(true, 'Auto Miner Scheduled Successfully!', [
        'ref' => $result['data']['id'] ?? 'unknown',
        'url' => $webhookUrl 
    ]);
    
    // Telegram Alert: Setup Success
    $tgMsg = "🚀 <b>Miner Setup Successful</b>\n" .
             "👤 User: $username\n" .
             "🆔 Event ID: " . ($result['data']['id'] ?? 'unknown') . "\n" .
             "⏰ Initial Schedule Created";
    TelegramBot::send($tgMsg);
} else {
    Utils::jsonResponse(false, 'Cronicle Error: ' . $result['message'], $result['debug'] ?? []);
}
