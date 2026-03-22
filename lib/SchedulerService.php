<?php
require_once __DIR__ . '/Cronicle.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/Utils.php';

class SchedulerService {
    
    // Schedule the next run based on next claim timestamp
    // Type: 'main' (default) or 'group'
    public static function scheduleNextRun($email, $cronId, $nextTs, $webhookUrlBase, $owner = 'System', $customTitle = null, $type = 'main') {
        $isFallback = false;
        
        // If nextTs is 0 or invalid, use Fallback (1 hour)
        if ($nextTs <= 0) {
            $nextTs = (time() + 3600) * 1000;
            $isFallback = true;
        }

        if ($nextTs > 1000000000000) $nextTs = intval($nextTs / 1000);
        
        // Anti-Ban Jitter (5 - 20 minutes) — adds random variation to avoid bot detection
        // Keeps total claim cycle well under 30 minutes
        $jitter = rand(300, 1200);
        $nextTs += $jitter;
        
        $jitterMins = round($jitter / 60);
        Utils::log('SCHEDULER', "Applied $jitterMins min jitter to $email ($type)");
        
        $startHour = (int)date('G', $nextTs);
        $startMinute = (int)date('i', $nextTs);
        
        // Single time slot for the NEXT run only
        $timing = ['hours' => [$startHour], 'minutes' => [$startMinute]];
        
        // Construct Webhook URL
        $username = ($owner === 'System') ? 'admin' : $owner; // fallback
        
        $eventTitle = $customTitle ?? "Miner - $email";
        $updated = false;
        
        // 1. Try UPDATE
        if ($cronId) {
            $updateRes = Cronicle::updateEvent($cronId, $eventTitle, $webhookUrlBase, $timing, APP_TIMEZONE);
            if ($updateRes['success']) {
                $updated = true;
                $scheduleMsg = $isFallback ? "Using FALLBACK schedule (1h from now)" : "Updated schedule";
                Utils::log('SCHEDULER', "$scheduleMsg for $email ($type). Event ID: $cronId. Starting at " . date('h:i A', $nextTs));

                // Telegram
                $tgMsg = "📅 <b>Schedule Updated ($type)</b>\n" .
                         "📧 Account: <code>$email</code>\n" .
                         "ℹ️ Info: $scheduleMsg\n" .
                         "🆔 Event ID: <code>$cronId</code>\n" .
                         "⏰ Start: " . date('h:i A', $nextTs);
                TelegramBot::send($tgMsg);
            } else {
               Utils::log('SCHEDULER', "Warning: Update failed for event $cronId: " . ($updateRes['message'] ?? 'Unknown'));
               $cronId = null; 
            }
        }
        
        // 2. SEARCH & RECOVER
        if (!$updated) {
            $existingIds = Cronicle::findEventIdsByTitle($eventTitle);
            if (!empty($existingIds)) {
                $cronId = $existingIds[0];
                $updateRes = Cronicle::updateEvent($cronId, $eventTitle, $webhookUrlBase, $timing, APP_TIMEZONE);
                
                if ($updateRes['success']) {
                    $updated = true;
                    
                    // Save recovered ID
                    if ($type === 'group') {
                        Utils::updateGroupCronId($email, $cronId);
                    } else {
                        Utils::updateAccountStatus($email, true, $cronId);
                    }
                    
                    $scheduleMsg = $isFallback ? "Using FALLBACK schedule (1h from now)" : "Updated recovered schedule";
                    Utils::log('SCHEDULER', "$scheduleMsg for $email. Event ID: $cronId. Starting at " . date('h:i A', $nextTs));

                    // Telegram
                    $tgMsg = "♻️ <b>Schedule Recovered & Updated</b>\n" .
                             "📧 Account: <code>$email</code>\n" .
                             "ℹ️ Info: $scheduleMsg\n" .
                             "🆔 Event ID: <code>$cronId</code>\n" .
                             "⏰ Start: " . date('h:i A', $nextTs);
                    TelegramBot::send($tgMsg);
                    
                    // Cleanup
                    if (count($existingIds) > 1) {
                        for ($i = 1; $i < count($existingIds); $i++) {
                            Cronicle::deleteEvent($existingIds[$i]);
                        }
                    }
                }
            }
        }
        
        // 3. CREATE
        if (!$updated) {
             $createRes = Cronicle::createEvent($eventTitle, $webhookUrlBase, $timing, APP_TIMEZONE);
             if ($createRes['success']) {
                $newCronId = $createRes['data']['id'];
                
                // Save New ID
                if ($type === 'group') {
                    Utils::updateGroupCronId($email, $newCronId);
                } else {
                    Utils::updateAccountStatus($email, true, $newCronId);
                }
                
                $scheduleMsg = $isFallback ? "Using FALLBACK schedule (1h from now)" : "Created schedule";
                Utils::log('SCHEDULER', "$scheduleMsg for $email. New Event ID: $newCronId. Starting at " . date('h:i A', $nextTs));

                // Telegram
                $tgMsg = "🆕 <b>Schedule Created</b>\n" .
                         "📧 Account: <code>$email</code>\n" .
                         "ℹ️ Info: $scheduleMsg\n" .
                         "🆔 Event ID: <code>$newCronId</code>\n" .
                         "⏰ Start: " . date('h:i A', $nextTs);
                TelegramBot::send($tgMsg);
             } else {
                Utils::log('SCHEDULER', "Error: Failed to create new event for $email: " . ($createRes['message'] ?? 'Unknown'));
             }
        }
    }
}
