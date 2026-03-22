<?php

require_once __DIR__ . '/../api/config.php';

class Cronicle {
    
    public static function createEvent($title, $webhookUrl, $timing = null, $timezone = null) {
        // Default timing: every 4 hours if not provided
        if (!$timing) {
            $timing = [
                'hours' => [0, 4, 8, 12, 16, 20],
                'minutes' => [0]
            ];
        }

        $payload = [
            'api_key' => CRONICLE_API_KEY,
            'title' => $title,
            'category' => 'general',
            'target' => defined('CRONICLE_TARGET') ? CRONICLE_TARGET : 'primary',
            'plugin' => defined('CRONICLE_PLUGIN_ID') ? CRONICLE_PLUGIN_ID : 'url',
            'params' => [
                'url' => $webhookUrl,
                'method' => 'GET',
                'timeout' => 120
            ],
            'timing' => $timing,
            'enabled' => 1,
            'multiplex' => 0,
            'retries' => 3
        ];
        
        if ($timezone) {
            $payload['timezone'] = $timezone;
        }
        
        // Debug
        // error_log("Cronicle Create Payload: " . json_encode($payload));

        return self::request('create_event/v1', $payload);
    }
    
    public static function deleteEvent($eventId) {
        $payload = [
            'api_key' => CRONICLE_API_KEY,
            'id' => $eventId
        ];
        
        return self::request('delete_event/v1', $payload);
    }

    public static function getEvent($eventId) {
        $payload = [
            'api_key' => CRONICLE_API_KEY,
            'id' => $eventId
        ];
        return self::request('get_event/v1', $payload);
    }

    public static function updateEvent($eventId, $title, $webhookUrl, $timing, $timezone = null) {
        $payload = [
            'api_key' => CRONICLE_API_KEY,
            'id' => $eventId,
            'title' => $title,
            'category' => 'general',
            'target' => defined('CRONICLE_TARGET') ? CRONICLE_TARGET : 'primary',
            'params' => [
                'url' => $webhookUrl,
                'method' => 'GET',
                'timeout' => 120
            ],
            'timing' => $timing,
            'enabled' => 1,
            'multiplex' => 0,
            'retries' => 3
        ];

        if ($timezone) {
            $payload['timezone'] = $timezone;
        }
        
        return self::request('update_event/v1', $payload);
    }
    
    public static function getSchedule() {
        $payload = [
            'api_key' => CRONICLE_API_KEY
        ];
        return self::request('get_schedule/v1', $payload);
    }

    public static function findEventIdsByTitle($title) {
        $schedule = self::getSchedule();
        if (!$schedule['success']) return [];
        
        $ids = [];
        $rows = $schedule['data']['rows'] ?? [];
        
        foreach ($rows as $row) {
            if (isset($row['title']) && $row['title'] === $title) {
                $ids[] = $row['id'];
            }
        }
        return $ids;
    }

    private static function request($endpoint, $data) {
        // Clean Base URL Logic
        $baseUrl = 'http://YOUR_SERVER_IP:3012/api/app'; 
        if (defined('CRONICLE_API_URL')) {
            $parts = explode('/create_event/', CRONICLE_API_URL);
            if (count($parts) > 0) {
                 $baseUrl = $parts[0];
            }
        }
        
        $url = "$baseUrl/$endpoint";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Debug Log
        // file_put_contents(__DIR__ . '/../data/logs/cronicle_debug.log', "REQ: $url -> " . json_encode($data) . "\nRES: $response\nERR: $error\n\n", FILE_APPEND);

        if ($error) {
            return ['success' => false, 'message' => $error];
        }
        
        $json = json_decode($response, true);
        if (isset($json['code']) && $json['code'] == 0) {
            return ['success' => true, 'data' => $json]; 
        }
        
        return ['success' => false, 'message' => $json['description'] ?? 'Unknown Cronicle Error', 'debug' => $json];
    }
}
