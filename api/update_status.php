<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    Utils::jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$isActive = $input['isActive'] ?? false;

if (!$email) {
    Utils::jsonResponse(false, 'Email mismatch or missing');
}

if (Utils::updateAccountStatus($email, $isActive)) {
    Utils::jsonResponse(true, 'Status updated');
} else {
    Utils::jsonResponse(false, 'Account not found');
}
