<?php

class SessionManager {
    // Configuration
    private const SESSION_NAME = 'INTERLINK_SECURE_SESS';
    private const INACTIVITY_TIMEOUT = 1800; // 30 minutes
    private const ABSOLUTE_TIMEOUT = 864000; // 10 days

    /**
     * Start the session securely
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session params
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_set_cookie_params([
                'lifetime' => self::ABSOLUTE_TIMEOUT,
                'path' => '/',
                'domain' => '', // Current domain
                'secure' => isset($_SERVER['HTTPS']), // True if HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_name(self::SESSION_NAME);
            session_start();
        }
    }

    /**
     * Login user and setup security bindings
     */
    public static function login($userData) {
        self::start();
        
        // Regenerate ID to prevent fixation
        session_regenerate_id(true);
        
        // Store user data
        $_SESSION['user_id'] = $userData['email'] ?? 'unknown'; // Using email as ID for now
        $_SESSION['user_data'] = $userData;
        
        // LEGACY COMPATIBILITY: Ensure 'username' is set directly for Utils
        if (isset($userData['username'])) {
            $_SESSION['username'] = $userData['username'];
        } elseif (isset($userData['email'])) {
            // Fallback: Use email as username if not provided
            $_SESSION['username'] = $userData['email'];
        }
        
        $_SESSION['logged_in'] = true;
        
        // Security Bindings
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Timestamps
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verify session validity
     * @return bool|string True if valid, error message string if invalid
     */
    public static function checkSession() {
        self::start();

        if (empty($_SESSION['logged_in'])) {
            // Try Remember Me
            if (self::checkRememberMe()) {
                return true;
            }
            return 'Not logged in';
        }


        // 1. Check IP Binding (Optional: loosen this if users change IPs often, e.g. mobile)
        if (!isset($_SESSION['ip_address']) || $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroy();
            return 'Session IP mismatch (Security Alert)';
        }

        // 2. Check User Agent Binding
        if (!isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')) {
            self::destroy();
            return 'Browser signature changed';
        }

        // 3. Check Inactivity Timeout
        if (time() - $_SESSION['last_activity'] > self::INACTIVITY_TIMEOUT) {
            self::destroy();
            return 'Session timed out due to inactivity';
        }

        // 4. Check Absolute Timeout
        if (time() - $_SESSION['created_at'] > self::ABSOLUTE_TIMEOUT) {
            self::destroy();
            return 'Session expired (Absolute Limit)';
        }

        // Update activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Get current user data
     */
    public static function getUser() {
        self::start();
        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Destroy session
     */
    public static function destroy() {
        // Ensure we are in the correct session context
        self::start();
        
        // Unset all variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session storage
        session_destroy();
        
        // Clear Remember Me
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }

    // --- Remember Me Features ---

    private const REMEMBER_COOKIE = 'INTERLINK_REMEMBER';
    private const REMEMBER_DAYS = 30;

    /**
     * Set Remember Me cookie and store token
     */
    public static function rememberMe($username) {
        $token = bin2hex(random_bytes(32)); // Secure random token
        $hash = hash('sha256', $token); // Store hash, send token
        
        // Save to file (Simple storage for now)
        $file = __DIR__ . '/../data/remember_tokens.json';
        $tokens = [];
        if (file_exists($file)) {
            $tokens = json_decode(file_get_contents($file), true) ?: [];
        }
        
        // Remove old tokens for this user
        $tokens = array_filter($tokens, function($t) use ($username) {
            return $t['username'] !== $username;
        });
        
        $tokens[] = [
            'username' => $username,
            'hash' => $hash,
            'expires' => time() + (self::REMEMBER_DAYS * 86400)
        ];
        
        file_put_contents($file, json_encode(array_values($tokens), JSON_PRETTY_PRINT));
        
        // Set secure cookie
        setcookie(self::REMEMBER_COOKIE, "$username:$token", time() + (self::REMEMBER_DAYS * 86400), '/', '', isset($_SERVER['HTTPS']), true);
    }

    /**
     * Check for Remember Me cookie and restore session
     */
    public static function checkRememberMe() {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) return false;
        
        $parts = explode(':', $_COOKIE[self::REMEMBER_COOKIE]);
        if (count($parts) !== 2) return false;
        
        [$username, $token] = $parts;
        $hash = hash('sha256', $token);
        
        $file = __DIR__ . '/../data/remember_tokens.json';
        if (!file_exists($file)) return false;
        
        $tokens = json_decode(file_get_contents($file), true) ?: [];
        $validIdx = -1;
        
        foreach ($tokens as $idx => $t) {
            if ($t['username'] === $username && hash_equals($t['hash'], $hash)) {
                if (time() < $t['expires']) {
                    $validIdx = $idx;
                }
                break;
            }
        }
        
        if ($validIdx !== -1) {
            // Valid token found! Login the user.
            // Note: In a real app we'd fetch full user object. Here we just need username.
            self::login(['email' => $username, 'username' => $username]);
            
            // Rotate token for security
            self::rememberMe($username);
            return true;
        }
        
        return false;
    }
}
