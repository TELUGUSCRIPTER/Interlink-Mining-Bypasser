<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

// Parses app.log to find recent successful claims
// Returns simple JSON list: { time, email, reward }

$logFile = Utils::getLogPath();
if (!file_exists($logFile)) {
    Utils::jsonResponse(true, 'No logs', []);
}

// Read last 10KB (enough for many logs)
$size = filesize($logFile);
$readSize = 10240; 
$fp = fopen($logFile, 'r');

if ($size > $readSize) {
    fseek($fp, -$readSize, SEEK_END);
    fgets($fp); // discard partial
}

$claims = [];
$lines = [];
while (($line = fgets($fp)) !== false) {
    // We are looking for MINER success
    // Format: [2024...] [MINER] Claimed for email ...
    // OR: [MINER] Claim Success ...
    if (strpos($line, '[MINER]') !== false) {
        if (strpos($line, 'Claimed for') !== false || strpos($line, 'Claim Success') !== false) {
            $lines[] = trim($line);
        }
    }
}
fclose($fp);

// Process found lines (newest at bottom, so traverse reverse)
$lines = array_reverse($lines);
$max = 5;
$count = 0;

foreach ($lines as $line) {
    if ($count >= $max) break;
    
    // Parse: [2024-12-21 04:30:00 PM] [MINER] Claimed for user@email.com {"interlinkGoldTokenAmount":"...","..."}
    // Regex to extract parts
    if (preg_match('/^\[(.*?)\] \[MINER\] (?:Claimed for|Claim Success(?:\s\(.*?\))? for) ([^\s]+)(?:\s(.*))?$/', $line, $matches)) {
        $time = $matches[1];
        $email = $matches[2];
        $details = $matches[3] ?? '';
        
        $reward = 'Unknown';
        // Try to parse json in details
        if (!empty($details)) {
             $json = json_decode($details, true);
             if ($json && isset($json['interlinkGoldTokenAmount'])) {
                 $reward = $json['interlinkGoldTokenAmount'] . ' ITLG';
             } else {
                 $reward = 'Success'; // Just success msg
             }
        }
        
        $claims[] = [
            'time' => $time,
            'email' => $email,
            'reward' => $reward
        ];
        $count++;
    }
}

Utils::jsonResponse(true, 'Recent claims', $claims);
