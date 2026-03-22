<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

// Allows fetching logs for "Matrix Terminal"
// Mode: Tail (last N bytes or lines)

// Release session lock to prevent blocking
session_write_close();


// Access Control: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    Utils::jsonResponse(false, 'Unauthorized');
}

// Global System Log for Admin
$logFile = __DIR__ . '/../data/logs/system.log';

if (!file_exists($logFile)) {
    // Return empty but success so polling continues
    Utils::jsonResponse(true, 'No logs', ['logs' => [], 'newPos' => 0]);
}

$lastPos = isset($_GET['last_pos']) ? (int)$_GET['last_pos'] : 0;
$currentSize = filesize($logFile);

// Handle Log Rotation or Reset
if ($lastPos > $currentSize) {
    $lastPos = 0;
}

// If no new data
if ($lastPos === $currentSize) {
    Utils::jsonResponse(true, 'No new logs', ['logs' => [], 'newPos' => $currentSize]);
}

// Open and Seek
$fp = fopen($logFile, 'r');
if (!$fp) {
    Utils::jsonResponse(false, 'Unable to read log');
}

// If initial load (0), and file is huge, maybe only load last 10KB?
// For now, if 0, load last 2KB to avoid flooding terminal
if ($lastPos === 0 && $currentSize > 2048) {
    fseek($fp, -2048, SEEK_END);
    fgets($fp); // discard partial
} else {
    fseek($fp, $lastPos);
}

$lines = [];
while (($line = fgets($fp)) !== false) {
    $line = trim($line);
    if ($line) $lines[] = $line;
}

$newPos = ftell($fp);
fclose($fp);

Utils::jsonResponse(true, 'Logs retrieved', ['logs' => $lines, 'newPos' => $newPos]);
