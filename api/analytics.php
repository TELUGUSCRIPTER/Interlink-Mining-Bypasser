<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/config.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

if (!isset($_SESSION['username'])) {
    Utils::jsonResponse(false, 'Unauthorized');
}

$history = Utils::getHistory();

// Prepare 24h Data Buckets
$buckets = [];
$now = time();

// Create 24 buckets for the last 24 hours (0 to 23 hours ago)
for ($i = 23; $i >= 0; $i--) {
    $time = $now - ($i * 3600);
    $label = date('H:00', $time); // Label like 14:00
    // Key needs to handle day rollover, so maybe full date or just hour for simple view
    // Let's use hour as key for simplicity in grouping
    $key = date('d-H', $time); 
    $buckets[$key] = [
        'label' => $label,
        'count' => 0
    ];
}

foreach ($history as $entry) {
    if (!isset($entry['timestamp']) || !isset($entry['status'])) continue;

    // Parse timestamp (Format: Y-m-d h:i:s A)
    $ts = strtotime($entry['timestamp']);
    if (!$ts) continue;

    // Filter last 24h
    if ($ts < ($now - 24 * 3600)) continue;

    // Check success status
    $status = $entry['status'];
    if ($status === 'Success' || $status === 'Claimed') {
        $key = date('d-H', $ts);
        if (isset($buckets[$key])) {
            $buckets[$key]['count']++;
        }
    }
}

Utils::jsonResponse(true, 'Analytics Data', array_values($buckets));
