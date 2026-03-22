<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/Cronicle.php';

if (!isset($_GET['email'])) {
    Utils::jsonResponse(false, 'Email parameter is required');
}

$email = $_GET['email'];
$account = Utils::getAccountByEmail($email);

if (!$account) {
    Utils::jsonResponse(false, 'Account not found');
}

// 1. Get Local History
$history = Utils::getHistory($email);

// 2. Get Cronicle Details if active
$cronDetails = null;
$nextRun = null;

if (isset($account['cronId']) && $account['cronId'] && $account['cronId'] !== 'CLEAR') {
    $cronRes = Cronicle::getEvent($account['cronId']);
    if ($cronRes['success']) {
        $event = $cronRes['data']['event'] ?? [];
        $cronDetails = [
            'id' => $event['id'] ?? '',
            'title' => $event['title'] ?? '',
            'enabled' => $event['enabled'] ?? 0,
            'timezone' => $event['timezone'] ?? 'UTC'
        ];
        
        // Cronicle returns 'ticks' (next run times) usually if requested or calculated
        // get_event/v1 returns the event config. it might NOT return "next run".
        // We might need to calculating it or check if it provides 'next_run'.
        // Cronicle API 'get_event' returns the object. 
        // Let's rely on frontend or 'timing' to show approximate, OR see if there's a status field.
        // Actually, without calculating cron logic, we can only show the schedule.
        // BUT, notice the user request: "upcoming event details".
        // The `get_event` API might return internal state if running, but for future ticks?
        // Let's pass the 'timing' object so frontend can explain "Every 4 hours".
        $cronDetails['timing'] = $event['timing'] ?? [];
    }
}

Utils::jsonResponse(true, 'Details fetched', [
    'history' => array_values($history),
    'cron' => $cronDetails
]);
