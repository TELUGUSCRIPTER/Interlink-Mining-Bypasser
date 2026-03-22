<?php
// Disable Time Limit
set_time_limit(0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx

require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

// Auth Check
if (!isset($_SESSION['username'])) {
    echo "data: Unauthorized\n\n";
    flush();
    exit;
}

// Release session lock to allow other scripts (like miner.php) to run concurrently
session_write_close();

$currentUser = $_SESSION['username'];
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$userEmails = [];

if (!$isAdmin) {
    // Fetch user emails to filter logs
    $tokens = Utils::getTokens($currentUser);
    foreach ($tokens as $t) {
        $userEmails[] = $t['email'];
    }
    // Also add username execution key just in case
    $userEmails[] = $currentUser;
}

// Turn off output buffering
while (ob_get_level() > 0) {
    ob_end_clean();
}

$logFile = __DIR__ . '/../data/logs/system.log'; 

if (!file_exists($logFile)) {
    // Create it if missing to prevent hang
    file_put_contents($logFile, "");
}

$latestLog = $logFile;

if (!$latestLog || !file_exists($latestLog)) {
    echo "data: Waiting for logs...\n\n";
    flush();
    exit;
}

// Padding to force flush on some servers
echo ":" . str_repeat(" ", 2048) . "\n\n";
flush();

// Start from 2KB before end, or 0
$fileSize = filesize($latestLog);
$lastPos = max(0, $fileSize - 4096); // Load last 4KB

while (true) {
    clearstatcache(false, $latestLog);
    $currentSize = filesize($latestLog);
    
    if ($currentSize < $lastPos) {
        $lastPos = 0; // File truncated
    }
    
    if ($currentSize > $lastPos) {
        $fh = fopen($latestLog, 'r');
        fseek($fh, $lastPos);
        
        while ($line = fgets($fh)) {
            $line = trim($line);
            if ($line) {
                
                // Filter Logic
                if (!$isAdmin) {
                    $found = false;
                    foreach ($userEmails as $uEmail) {
                        if (stripos($line, $uEmail) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    // Also allow generic system messages if needed? 
                    // No, "shows all logs" was the complaint. Strict filter is better.
                    if (!$found) continue;
                }

                echo "data: " . $line . "\n\n";
                flush();
            }
        }
        
        $lastPos = ftell($fh);
        fclose($fh);
    }
    
    // Heartbeat
    echo ": heartbeat\n\n";
    flush();
    
    sleep(1);
    
    // Break connection if client disconnects (optional but good practice)
    if (connection_aborted()) break;
}

