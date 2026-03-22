<?php

class InterlinkClient {
    private $baseUrl = "https://prod.interlinklabs.ai/api/v1";
    private $appVersion = "4.0.5";
    private $bundleId = "org.ai.interlinklabs.interlinkId";
    
    // Device identity (per-account)
    private $deviceId;
    private $model;
    private $brand;
    private $systemName;
    private $userAgent;
    
    // IP rotation
    private $forwardedIp;

    // Real proxy routing (per-account)
    private $proxyConfig = null;

    // Persistent cURL handle for connection pooling / keep-alive
    private $curlHandle = null;

    // Anti-replay: track used timestamps to prevent duplicates
    private $usedTimestamps = [];

    /**
     * @param array $deviceInfo  Per-account device fingerprint
     * @param array|null $proxyConfig  Proxy config: ['type','host','port','username','password']
     */
    public function __construct($deviceInfo = [], $proxyConfig = null) {
        // Set device identity from provided info or fallback
        $this->deviceId   = $deviceInfo['x-device-id']   ?? $deviceInfo['x-unique-id'] ?? bin2hex(random_bytes(8));
        $this->model      = $deviceInfo['x-model']       ?? 'Redmi Note 5 Pro';
        $this->brand      = $deviceInfo['x-brand']       ?? 'xiaomi';
        $this->systemName = $deviceInfo['x-system-name'] ?? 'Android';
        $this->userAgent  = $deviceInfo['user-agent']    ?? 'okhttp/4.12.0';
        
        // Generate a random forwarded IP for this client instance
        $this->forwardedIp = self::generateRandomIP();

        // Store proxy config for real IP routing
        $this->proxyConfig = $proxyConfig;

        // Initialize persistent cURL handle
        $this->curlHandle = curl_init();
    }

    // #12 — Cleanup persistent cURL handle
    public function __destruct() {
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }

    // ================================================================
    //  SIGNATURE & HASH GENERATION (Anti-Bot)
    // ================================================================

    /**
     * Generate x-content-hash for request body
     * SHA256 base64 of the JSON body string
     */
    private function generateContentHash($bodyString) {
        return base64_encode(hash('sha256', $bodyString, true));
    }

    /**
     * Generate x-signature for the request
     * HMAC-SHA256 based on: method + path + timestamp + content-hash  
     * Using deviceId as the signing key (matches pattern from captured data)
     */
    private function generateSignature($method, $endpoint, $timestamp, $contentHash = '') {
        $payload = $method . $endpoint . $timestamp . $contentHash;
        return base64_encode(hash_hmac('sha256', $payload, $this->deviceId, true));
    }

    /**
     * #17 — Generate a unique millisecond timestamp (anti-replay)
     * Ensures no two requests from this client instance share the same timestamp
     */
    private function generateUniqueTimestamp() {
        $timestamp = (string)(intval(microtime(true) * 1000));
        
        // If this timestamp was already used, increment until unique
        while (in_array($timestamp, $this->usedTimestamps)) {
            $timestamp = (string)(intval($timestamp) + 1);
        }
        
        $this->usedTimestamps[] = $timestamp;
        
        // Keep only last 100 timestamps to prevent memory growth
        if (count($this->usedTimestamps) > 100) {
            $this->usedTimestamps = array_slice($this->usedTimestamps, -50);
        }
        
        return $timestamp;
    }

    /**
     * Generate a random realistic IP address for X-Forwarded-For
     */
    public static function generateRandomIP() {
        // Avoid reserved ranges: 10.x, 172.16-31.x, 192.168.x, 127.x, 0.x
        $ranges = [
            [1, 9],      // 1.x - 9.x
            [11, 126],   // 11.x - 126.x
            [128, 171],  // 128.x - 171.x
            [173, 191],  // 173.x - 191.x
            [193, 223],  // 193.x - 223.x
        ];
        $range = $ranges[array_rand($ranges)];
        $first = rand($range[0], $range[1]);
        return "$first." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);
    }

    // ================================================================
    //  BUILD HEADERS
    //  #10 — Header order matches captured real app traffic exactly:
    //  accept → version → [authorization] → x-date → x-signature →
    //  [x-content-hash] → x-unique-id → x-model → x-brand →
    //  x-system-name → x-device-id → x-bundle-id → [content-type] →
    //  [content-length] → accept-encoding → user-agent →
    //  [if-modified-since]
    // ================================================================

    private function buildHeaders($method, $endpoint, $bodyString = '', $token = null) {
        // #17 — Use unique timestamp to prevent replay detection
        $timestamp = $this->generateUniqueTimestamp();

        // Content hash (only meaningful for POST with body)
        $contentHash = '';
        if ($method === 'POST' && !empty($bodyString) && $bodyString !== '{}') {
            $contentHash = $this->generateContentHash($bodyString);
        }

        // Signature
        $signature = $this->generateSignature($method, $endpoint, $timestamp, $contentHash);

        // #10 — Build headers in exact order matching real app traffic
        $headers = [
            "accept: */*",
            "version: {$this->appVersion}",
        ];

        // Auth token (position matches captured traffic — before x-date)
        if ($token) {
            $headers[] = "authorization: Bearer $token";
        }

        $headers[] = "x-date: $timestamp";
        $headers[] = "x-signature: $signature";

        // Content hash header only for POST with body
        if (!empty($contentHash)) {
            $headers[] = "x-content-hash: $contentHash";
        }

        // Device identity headers (exact order from captured traffic)
        $headers[] = "x-unique-id: {$this->deviceId}";
        $headers[] = "x-model: {$this->model}";
        $headers[] = "x-brand: {$this->brand}";
        $headers[] = "x-system-name: {$this->systemName}";
        $headers[] = "x-device-id: {$this->deviceId}";
        $headers[] = "x-bundle-id: {$this->bundleId}";

        // Content type and length for POST
        if ($method === 'POST') {
            $headers[] = "content-type: application/json";
            // #18 — Explicit Content-Length matching exact byte count
            $headers[] = "content-length: " . strlen($bodyString);
        }

        // Encoding
        $headers[] = "accept-encoding: gzip";

        // User Agent (mobile app style)
        $headers[] = "user-agent: {$this->userAgent}";

        // #10 — if-modified-since on authenticated GET requests (matches real app, line 223 of raw data)
        if ($method === 'GET' && $token) {
            $headers[] = "if-modified-since: " . gmdate('D, d M Y H:i:s', strtotime('-30 days')) . " GMT";
        }

        // IP rotation headers
        $headers[] = "X-Forwarded-For: {$this->forwardedIp}";
        $headers[] = "X-Real-IP: {$this->forwardedIp}";

        // #12 — Connection keep-alive
        $headers[] = "Connection: keep-alive";

        return $headers;
    }

    // ================================================================
    //  TLS FINGERPRINT SPOOFING
    //  Mimics okhttp 4.12.0 TLS behavior to avoid JA3/JA4 detection
    // ================================================================

    private function applyTLSFingerprint($ch) {
        // Force TLS 1.2 minimum (okhttp default)
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }

        // okhttp 4.12.0 cipher suite order (from OkHttp source ConnectionSpec.MODERN_TLS)
        // This controls the JA3 fingerprint — order matters!
        $ciphers = implode(':', [
            'TLS_AES_128_GCM_SHA256',
            'TLS_AES_256_GCM_SHA384',
            'TLS_CHACHA20_POLY1305_SHA256',
            'ECDHE-ECDSA-AES128-GCM-SHA256',
            'ECDHE-RSA-AES128-GCM-SHA256',
            'ECDHE-ECDSA-AES256-GCM-SHA384',
            'ECDHE-RSA-AES256-GCM-SHA384',
            'ECDHE-ECDSA-CHACHA20-POLY1305',
            'ECDHE-RSA-CHACHA20-POLY1305',
        ]);
        curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $ciphers);

        // Set TLS 1.3 ciphers separately if supported
        if (defined('CURLOPT_TLS13_CIPHERS')) {
            curl_setopt($ch, CURLOPT_TLS13_CIPHERS,
                'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256'
            );
        }

        // Disable session tickets (okhttp doesn't use them by default)
        // and enable false start for performance
        if (defined('CURLOPT_SSL_OPTIONS')) {
            $sslOpts = 0;
            if (defined('CURLSSLOPT_NO_REVOKE')) {
                $sslOpts |= CURLSSLOPT_NO_REVOKE;
            }
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, $sslOpts);
        }

        // Enable ALPN (HTTP/2 negotiation — okhttp uses h2)
        if (defined('CURLOPT_SSL_ENABLE_ALPN')) {
            curl_setopt($ch, CURLOPT_SSL_ENABLE_ALPN, true);
        }

        // Set elliptic curves order (matches okhttp/BoringSSL preference)
        if (defined('CURLOPT_SSL_EC_CURVES')) {
            curl_setopt($ch, CURLOPT_SSL_EC_CURVES, 'X25519:P-256:P-384');
        }
    }

    // ================================================================
    //  REAL PROXY ROUTING
    //  Routes cURL traffic through SOCKS5/HTTP/SOCKS4 proxy
    // ================================================================

    private function applyProxy($ch) {
        if (!$this->proxyConfig || empty($this->proxyConfig['host'])) {
            return;
        }

        $host = $this->proxyConfig['host'];
        $port = $this->proxyConfig['port'] ?? 1080;
        $type = strtoupper($this->proxyConfig['type'] ?? 'HTTP');
        $user = $this->proxyConfig['username'] ?? '';
        $pass = $this->proxyConfig['password'] ?? '';

        curl_setopt($ch, CURLOPT_PROXY, "$host:$port");

        // Proxy type mapping
        switch ($type) {
            case 'SOCKS5':
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
                break;
            case 'SOCKS4':
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                break;
            case 'HTTPS':
            case 'HTTP':
            default:
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                break;
        }

        // Proxy auth
        if (!empty($user) && !empty($pass)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$user:$pass");
        }

        // Tunnel mode for HTTPS through HTTP proxy
        if ($type === 'HTTP' || $type === 'HTTPS') {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        }
    }

    // ================================================================
    //  HTTP REQUEST (persistent cURL + TLS spoofing + proxy routing)
    // ================================================================

    private function request($method, $endpoint, $data = [], $token = null, $timeout = 25) {
        $url = $this->baseUrl . $endpoint;
        
        // Prepare body string
        $bodyString = '';
        if ($method === 'POST') {
            if ($data instanceof stdClass || (is_array($data) && empty($data))) {
                $bodyString = '{}';
            } else {
                $bodyString = json_encode($data);
            }
        }

        // Build headers with anti-bot fields
        $currentHeaders = $this->buildHeaders($method, $endpoint, $bodyString, $token);

        // Reuse persistent cURL handle (connection pooling)
        $ch = $this->curlHandle;
        
        // Reset handle for new request while keeping the connection alive
        curl_reset($ch);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $currentHeaders);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        
        // SSL verification (relaxed for compatibility)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        // Timeouts (longer when proxied)
        $proxyActive = !empty($this->proxyConfig['host']);
        $connectTimeout = $proxyActive ? min(20, $timeout) : min(15, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $proxyActive ? $timeout + 10 : $timeout);

        // HTTP/2 if available (matches real app traffic)
        if (defined('CURL_HTTP_VERSION_2_0')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }

        // Apply TLS fingerprint spoofing (mimic okhttp cipher suites)
        $this->applyTLSFingerprint($ch);

        // Apply real proxy routing if configured
        $this->applyProxy($ch);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        // Note: we do NOT curl_close() here — handle is reused (#12)

        if ($error) {
            throw new Exception("CURL Error: $error");
        }

        if ($httpCode === 401) {
            throw new Exception("Unauthorized: Token Expired");
        }

        // Empty response handling
        if ($httpCode >= 200 && $httpCode < 300 && empty($response)) {
            Utils::log('API_DEBUG', "Empty response from $endpoint with HTTP $httpCode", "DeviceID: {$this->deviceId}");
            return [];
        }

        $decoded = json_decode($response, true);
        
        if ($decoded === null && !empty($response)) {
            $snippet = substr($response, 0, 1000);
            Utils::log('API_ERROR', "Invalid JSON from $endpoint. Code: $httpCode", $snippet);
            throw new Exception("Invalid JSON response from API (Code $httpCode)");
        }

        if (empty($response) && $httpCode != 200) {
            Utils::log('API_ERROR', "Empty error response from $endpoint. Code: $httpCode", $error);
            throw new Exception("Empty response from API (Code $httpCode)");
        }

        return $decoded;
    }

    // ================================================================
    //  AUTH APIs
    // ================================================================

    /**
     * Check if loginId exists (pre-flight)
     */
    public function checkLoginIdExists($loginId) {
        $endpoint = "/auth/loginId-exist-check/$loginId?deviceId=" . urlencode($this->deviceId);
        return $this->request('GET', $endpoint);
    }

    /**
     * Check passcode validity (pre-flight before OTP)
     */
    public function checkPasscode($loginId, $passcode) {
        $endpoint = "/auth/check-passcode";
        $data = [
            "loginId"  => $loginId,
            "passcode" => $passcode,
            "deviceId" => $this->deviceId
        ];
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Request OTP email (now includes deviceId)
     */
    public function requestOtp($email, $passcode, $loginId) {
        $endpoint = "/auth/send-otp-email-verify-login";
        $data = [
            "loginId"  => $loginId,
            "passcode" => $passcode,
            "email"    => $email,
            "deviceId" => $this->deviceId
        ];
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Verify OTP (now includes deviceId)
     */
    public function verifyOtp($loginId, $otp) {
        $endpoint = "/auth/check-otp-email-verify-login";
        $data = [
            "loginId"  => $loginId,
            "otp"      => $otp,
            "deviceId" => $this->deviceId
        ];
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Get full user context after login (new endpoint)
     */
    public function getCurrentUserFull($token) {
        $endpoint = "/auth/current-user-full?include=userInfo%2Ctoken%2CisClaimable";
        return $this->request('GET', $endpoint, [], $token);
    }

    // ================================================================
    //  TOKEN / MINING APIs
    // ================================================================

    public function getTokenBalance($token) {
        return $this->request('GET', "/token/get-token", [], $token);
    }

    public function checkIsClaimable($token) {
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $res = $this->request('GET', "/token/check-is-claimable", [], $token);
                if (!empty($res)) return $res;
                
                if ($attempt < $maxRetries) sleep(2);
            } catch (Exception $e) {
                if ($attempt >= $maxRetries) throw $e;
                sleep(2);
            }
        }
        return [];
    }

    public function claimAirdrop($token) {
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $res = $this->request('POST', "/token/claim-airdrop", new stdClass(), $token);
                if (!empty($res)) return $res;
                
                if ($attempt < $maxRetries) sleep(2);
            } catch (Exception $e) {
                if ($attempt >= $maxRetries) throw $e;
                sleep(2);
            }
        }
        return [];
    }

    // ================================================================
    //  GROUP MINING APIs
    // ================================================================

    public function createGroup($token, $groupId) {
        return $this->request('POST', "/group-mining/create-group", ["groupId" => $groupId], $token);
    }

    public function getGroupList($token) {
        return $this->request('POST', "/group-mining/get-list-group-mining", new stdClass(), $token);
    }

    public function getGroupDetail($token, $groupId) {
        return $this->request('POST', "/group-mining/get-detail-group-mining", ["groupId" => $groupId], $token);
    }

    public function claimGroupMining($token, $groupId) {
        return $this->request('POST', "/group-mining/claim-group-mining", ["groupId" => $groupId], $token);
    }

    public function createInvite($token, $groupId, $loginId) {
        return $this->request('POST', "/group-mining/invite-group", ['groupId' => $groupId, 'loginId' => $loginId], $token);
    }

    public function joinGroup($token, $inviteCode) {
        return $this->request('POST', "/group-mining/accept-invite-group", ['code' => $inviteCode], $token);
    }

    // ================================================================
    //  HELPERS
    // ================================================================

    /**
     * Get the device ID used by this client instance
     */
    public function getDeviceId() {
        return $this->deviceId;
    }

    /**
     * Rotate the forwarded IP (call between accounts)
     */
    public function rotateIP() {
        $this->forwardedIp = self::generateRandomIP();
    }

    /**
     * #11 — Decode JWT and check expiry
     * Returns: ['valid' => bool, 'expires_in' => seconds, 'expired' => bool, 'expiring_soon' => bool]
     */
    public static function checkTokenExpiry($token) {
        $result = ['valid' => false, 'expires_in' => 0, 'expired' => true, 'expiring_soon' => false];
        
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) return $result;
            
            // Decode payload (part 1) — base64url decode
            $payload = $parts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $payload = base64_decode($payload);
            
            if (!$payload) return $result;
            
            $data = json_decode($payload, true);
            if (!$data || !isset($data['exp'])) return $result;
            
            $exp = (int)$data['exp'];
            $now = time();
            $expiresIn = $exp - $now;
            
            $result['valid'] = true;
            $result['expires_in'] = $expiresIn;
            $result['expired'] = $expiresIn <= 0;
            // Expiring soon = within 24 hours
            $result['expiring_soon'] = $expiresIn > 0 && $expiresIn < 86400;
            
        } catch (Exception $e) {
            // Silently fail — treat as expired
        }
        
        return $result;
    }
}
