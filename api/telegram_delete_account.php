<?php
/**
 * Telegram Delete Account API
 * This endpoint is called when user clicks "Delete Account" button in Telegram
 */

require_once __DIR__ . '/../lib/Utils.php';
require_once __DIR__ . '/../lib/TelegramBot.php';

header('Content-Type: text/html; charset=utf-8');

// Get email from URL parameter
$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
            .error { background: #fee; border: 2px solid #f88; padding: 20px; border-radius: 10px; color: #c00; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Error</h2>
            <p>No email address provided.</p>
        </div>
    </body>
    </html>';
    exit;
}

// Decode email (in case it's URL encoded)
$email = urldecode($email);

// Log the deletion attempt
Utils::log('TELEGRAM_DELETE', 'Delete account request from Telegram', $email);

// Find and delete the account globally (search all users)
$success = deleteAccountGlobally($email);

function deleteAccountGlobally($email) {
    $usersDir = __DIR__ . '/../data/users';
    if (!is_dir($usersDir)) return false;
    
    $users = scandir($usersDir);
    
    foreach ($users as $user) {
        if ($user === '.' || $user === '..') continue;
        if (is_dir("$usersDir/$user")) {
            // Try to delete from this user's data
            $result = Utils::deleteAccount($email, $user);
            if ($result) {
                return true; // Found and deleted
            }
        }
    }
    
    return false; // Not found in any user directory
}

if ($success) {
    // Send confirmation to Telegram
    $confirmMsg = "✅ <b>Account Deleted Successfully</b>\n" .
                 "📧 Email: <code>$email</code>\n" .
                 "🗑️ All related data has been removed:\n" .
                 "   • Account credentials\n" .
                 "   • Authentication tokens\n" .
                 "   • Mining history\n" .
                 "   • Scheduled tasks\n" .
                 "⏰ Time: " . date('h:i A');
    
    TelegramBot::send($confirmMsg);
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Success</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
            .success { background: #efe; border: 2px solid #8f8; padding: 20px; border-radius: 10px; color: #060; }
            .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: left; }
            .details ul { margin: 10px 0; }
            .email { background: #e0e0e0; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="success">
            <h2>✅ Account Deleted Successfully</h2>
            <p>The account <span class="email">' . htmlspecialchars($email) . '</span> has been permanently removed.</p>
            
            <div class="details">
                <strong>🗑️ Deleted Data:</strong>
                <ul>
                    <li>Account credentials</li>
                    <li>Authentication tokens</li>
                    <li>Mining history</li>
                    <li>Scheduled tasks (Cronicle events)</li>
                </ul>
            </div>
            
            <p style="margin-top: 20px; color: #666;">
                <small>⏰ Deleted at: ' . date('h:i:s A') . '</small>
            </p>
            
            <p style="margin-top: 20px;">
                <small>You can now close this window.</small>
            </p>
        </div>
    </body>
    </html>';
    
} else {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
            .error { background: #fee; border: 2px solid #f88; padding: 20px; border-radius: 10px; color: #c00; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Deletion Failed</h2>
            <p>Could not delete account: <strong>' . htmlspecialchars($email) . '</strong></p>
            <p>The account may not exist or has already been deleted.</p>
        </div>
    </body>
    </html>';
}
