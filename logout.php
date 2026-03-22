<?php
require_once __DIR__ . '/lib/SessionManager.php';
SessionManager::destroy();
header("Location: login.php");
exit;
