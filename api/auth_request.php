<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();
require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/InterlinkClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
// Treat ID and Passcode as strings to preserve leading zeros
$passcode = trim($input['passcode'] ?? '');
$interlinkId = trim($input['interlinkId'] ?? '');

if (!$email || !$passcode || !$interlinkId) {
    Utils::jsonResponse(false, 'Missing required fields');
}

// Log the attempt for debugging
Utils::log('AUTH_DEBUG', "Requesting OTP for $email", "Passcode: $passcode, ID: $interlinkId");

// Load or generate device info for this account (unique fingerprint)
$deviceInfo = Utils::getOrGenerateDeviceInfo($email);
Utils::migrateDeviceInfo($email); // Auto-fix old Chrome UAs
$proxyConfig = Utils::getProxyConfigForAccount($email);
$client = new InterlinkClient($deviceInfo, $proxyConfig);

try {
    // Step 1: Pre-flight - Check loginId exists (matches real app flow)
    $existCheck = $client->checkLoginIdExists($interlinkId);
    Utils::log('AUTH_DEBUG', "LoginId exist check for $email", json_encode($existCheck));
    
    if (isset($existCheck['data']) && $existCheck['data'] !== true) {
        Utils::jsonResponse(false, 'Interlink ID not found. Please check your ID.');
    }

    // Step 2: Pre-flight - Check passcode validity
    $passcodeCheck = $client->checkPasscode($interlinkId, $passcode);
    Utils::log('AUTH_DEBUG', "Passcode check for $email", json_encode($passcodeCheck));
    
    if (isset($passcodeCheck['statusCode']) && $passcodeCheck['statusCode'] !== 200) {
        $pcMsg = $passcodeCheck['message'] ?? 'Invalid passcode';
        Utils::jsonResponse(false, "Passcode Error: $pcMsg");
    }

    // Small delay between pre-flight and OTP request (mimic real app)
    usleep(rand(300000, 800000)); // 0.3 - 0.8 seconds

    // Step 3: Request OTP
    $response = $client->requestOtp($email, $passcode, $interlinkId);
    
    // Log the response
    Utils::log('AUTH_DEBUG', "OTP Response for $email", json_encode($response));

    $msg = $response['message'] ?? 'No message from API';
    
    if ((isset($response['code']) && $response['code'] == '00') || $msg === 'EMAIL_SEND' || stripos($msg, 'Email has been sent') !== false) {
        // Success
        $displayMsg = 'OTP Sent Successfully';
        Utils::jsonResponse(true, $displayMsg, $response);
    } else {
        // Failure or unknown state
        Utils::jsonResponse(false, "API Error: $msg", $response);
    }
} catch (Exception $e) {
    Utils::log('AUTH_ERROR', "Exception for $email", $e->getMessage());
    Utils::jsonResponse(false, 'Exception: ' . $e->getMessage());
}
