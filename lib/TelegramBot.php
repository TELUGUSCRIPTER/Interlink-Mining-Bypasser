<?php
require_once __DIR__ . '/../api/config.php';

class TelegramBot {
    
    public static function send($message) {
        if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_USER_ID')) {
            return false;
        }

        $token = TELEGRAM_BOT_TOKEN;
        $chatId = TELEGRAM_USER_ID;
        
        $url = "https://api.telegram.org/bot$token/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        return $response ? true : false;
    }
    
    public static function sendWithButtons($message, $inlineKeyboard) {
        if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_USER_ID')) {
            return false;
        }

        $token = TELEGRAM_BOT_TOKEN;
        $chatId = TELEGRAM_USER_ID;
        
        $url = "https://api.telegram.org/bot$token/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => $inlineKeyboard
            ])
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        return $response ? true : false;
    }

}
