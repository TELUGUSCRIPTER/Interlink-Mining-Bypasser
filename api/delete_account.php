<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';

if (!isset($_GET['email'])) {
    Utils::jsonResponse(false, 'Email parameter is required');
}

$email = $_GET['email'];

// Basic validation?
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Utils::jsonResponse(false, 'Invalid email format');
}

try {
    $result = Utils::deleteAccount($email);
    
    if ($result) {
        Utils::jsonResponse(true, 'Account deleted successfully', ['email' => $email]);
    } else {
        Utils::jsonResponse(false, 'Account not found or could not be deleted');
    }
} catch (Exception $e) {
    Utils::log('ERROR', "Delete Account Failed for $email: " . $e->getMessage());
    Utils::jsonResponse(false, 'System error during deletion');
}
