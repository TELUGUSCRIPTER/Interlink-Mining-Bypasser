<?php
// ============================================
// Configuration for Interlink Auto Claimer
// ============================================
// INSTRUCTIONS:
// 1. Copy this file to config.php
// 2. Fill in your actual values below
// 3. NEVER commit config.php to Git
// ============================================

// Timezone
define('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(APP_TIMEZONE);

// Your Public Domain URL (e.g., https://yourdomain.com/WebPanel)
// Leave empty to attempt auto-detection
define('APP_BASE_URL', ''); 

// Cronicle Configuration (Job Scheduler)
// Get these from your Cronicle instance: http://your-server:3012
define('CRONICLE_API_URL', 'http://YOUR_SERVER_IP:3012/api/app/create_event/v1');
define('CRONICLE_API_KEY', 'YOUR_CRONICLE_API_KEY_HERE');
define('CRONICLE_PLUGIN_ID', 'urlplug'); 
define('CRONICLE_TARGET', 'maingrp'); // Target ID for 'Primary Group'

// Telegram Bot Configuration (for notifications)
// Create a bot via @BotFather and get your chat ID from @userinfobot
define('TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN_HERE');
define('TELEGRAM_USER_ID', 'YOUR_TELEGRAM_CHAT_ID_HERE');
