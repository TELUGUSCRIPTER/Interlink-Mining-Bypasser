<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

// Destroy Session
SessionManager::destroy();

// Redirect to Admin Login
header("Location: login.php");
exit;
