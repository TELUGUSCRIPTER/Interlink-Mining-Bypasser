<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Utils.php';

SessionManager::start();

// Auth check
if (!isset($_SESSION['username'])) {
    Utils::jsonResponse(false, 'Unauthorized');
}

// Clean buffer to prevent JSON breakage
while (ob_get_level()) ob_end_clean();

$accounts = Utils::getAccounts();
$schedule = [];

foreach ($accounts as $acc) {
    if (empty($acc['isActive'])) continue; // Only show active schedules

    $email = $acc['email'];
    $nextClaim = $acc['next_claim'] ?? '';
    $timestamp = 0;

    // Parse 'h:i A' (Today) or Unix timestamp
    if (preg_match('/^\d+$/', $nextClaim)) {
        // It's a timestamp
        $timestamp = (int)$nextClaim;
    } elseif (!empty($nextClaim)) {
        // It's a string like "04:30 PM"
        // Assume it's today's date if possible, or tomorrow if time passed?
        // Actually Utils usually saves it as date('h:i A'). 
        $ts = strtotime($nextClaim);
        $now = time();
        
        // Smart Rollover Logic
        // If Timestamp is in the past:
        if ($ts < $now) {
            // Calculate how long ago
            $diff = $now - $ts;
            
            // If it was more than 12 hours ago, it likely means the time is for "Tomorrow"
            // (e.g. It is 8 PM, Next Claim is 2 AM. strtotime("2 AM") = Today 2 AM (18h ago). Logic -> Tomorrow 2 AM)
            if ($diff > 43200) { 
                $ts += 86400; 
            }
            // If it is < 12 hours ago (e.g. 30 mins ago), we keep it as "Overdue" because the miner probably missed it.
        }
        
        $timestamp = $ts;
    } else {
        continue;
    }
    
    // Fallback if timestamp is invalid
    if ($timestamp <= 0) continue;

    $schedule[] = [
        'email' => $email,
        'timeStr' => date('h:i A', $timestamp),
        'timestamp' => $timestamp,
        'proxy' => $acc['proxyId'] ? 'Yes' : 'No'
    ];
}

// Sort by timestamp ASC
usort($schedule, function($a, $b) {
    return $a['timestamp'] - $b['timestamp'];
});

Utils::jsonResponse(true, 'Schedule Fetched', $schedule);
