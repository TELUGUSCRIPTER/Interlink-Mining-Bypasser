<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/Cronicle.php';
require_once __DIR__ . '/../lib/TelegramBot.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['username'])) {
    Utils::jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$isActive = $input['isActive'] ?? false;
$username = $_SESSION['username'];
$code = $username . "1";

if (!$email) {
    Utils::jsonResponse(false, 'Email mismatch or missing');
}

// Get current account to see if it has a cronId
$account = Utils::getAccountByEmail($email);
if (!$account) {
    Utils::jsonResponse(false, 'Account not found');
}

$currentCronId = $account['cronId'] ?? null;

// Detect Host for Webhook
if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
    $baseUrl = rtrim(APP_BASE_URL, '/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $apiDir = dirname($_SERVER['PHP_SELF']); // /WebPanel/api
    $basePath = dirname($apiDir); // /WebPanel
    $basePath = str_replace('\\', '/', $basePath);
    $baseUrl = "$protocol$host$basePath";
}

$webhookUrl = "$baseUrl/api/miner.php?username=$username&code=$code&email=" . urlencode($email);

if ($isActive) {
    // -- ENABLE: set isActive = true --
    // We NO LONGER create the event here immediately.
    // The frontend will trigger api/miner.php next.
    
    // Just update local status
    Utils::updateAccountStatus($email, true); 
    
    // Telegram Alert: ON
    $tgMsg = "🟢 <b>Auto-Mining Enabled</b>\n" .
             "📧 Account: <code>$email</code>\n" .
             "ℹ️ Status: Active (Schedule creation pending)\n" .
             "👤 User: $username";
    TelegramBot::send($tgMsg); 
    Utils::jsonResponse(true, 'Mining initialized. Starting run...', ['init' => true]);

} else {
    // -- DISABLE: Delete Event --
    if ($currentCronId) {
        $del = Cronicle::deleteEvent($currentCronId);
        Utils::log('SYSTEM', "Mining Toggled OFF for $email. Deleting Event ID: $currentCronId. Result: " . json_encode($del));
        
        // Telegram Alert: OFF
        $tgMsg = "🛑 <b>Auto-Mining Disabled</b>\n" .
                 "📧 Account: <code>$email</code>\n" .
                 "ℹ️ Schedule Removed (ID: $currentCronId)\n" .
                 "👤 User: $username";
        TelegramBot::send($tgMsg);
    } else {
        Utils::log('SYSTEM', "Mining Toggled OFF for $email. No Cron ID found to delete.");
    }

    Utils::updateAccountStatus($email, false, 'CLEAR');
    Utils::jsonResponse(true, 'Mining disabled & schedule removed');
}
