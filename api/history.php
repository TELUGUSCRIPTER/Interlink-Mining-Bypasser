<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

if (!isset($_GET['email'])) {
    Utils::jsonResponse(false, 'Email parameter is required');
}

$email = $_GET['email'];
$history = Utils::getHistory($email);

// Re-index array to be clean JSON list in case array_filter messed up keys
$history = array_values($history);

Utils::jsonResponse(true, 'History fetched', $history);
