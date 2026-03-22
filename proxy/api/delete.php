<?php
// Session check removed/optional
require_once __DIR__ . '/../../lib/Utils.php';

$input = json_decode(file_get_contents('php://input'), true);

// DEBUG LOGGING
file_put_contents(__DIR__ . '/delete_debug.log', date('Y-m-d H:i:s') . " - Request: " . print_r($input, true) . PHP_EOL, FILE_APPEND);

if (!$input || empty($input['id'])) {
    Utils::jsonResponse(false, 'Invalid Input or ID missing');
}

$id = $input['id'];
$success = Utils::deleteGlobalProxy($id);

file_put_contents(__DIR__ . '/delete_debug.log', date('Y-m-d H:i:s') . " - Success: " . ($success ? 'YES' : 'NO') . PHP_EOL, FILE_APPEND);

if ($success) {
    Utils::jsonResponse(true, 'Proxy deleted successfully');
} else {
    Utils::jsonResponse(false, 'Proxy not found or could not be deleted');
}
