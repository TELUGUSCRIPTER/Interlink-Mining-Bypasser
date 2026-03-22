<?php

class ProxyTester {
    
    /**
     * Test a proxy connection
     * 
     * @param string $type HTTP, HTTPS, SOCKS4, SOCKS5
     * @param string $host IP or Hostname
     * @param int $port Port number
     * @param string $user Username (optional)
     * @param string $pass Password (optional)
     * @return array ['success' => bool, 'message' => string, 'latency' => int]
     */
    public static function test($type, $host, $port, $user = null, $pass = null) {
        // Use IP-API for detailed info (Country, ISP, etc.)
        // Note: ip-api.com is free for non-commercial use (limited rate). 
        // Fallback or alternative: httpbin if strictly for connectivity.
        $url = 'http://ip-api.com/json'; 
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Slightly longer for external API
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Proxy Setup
        $proxyStr = "$host:$port";
        curl_setopt($ch, CURLOPT_PROXY, $proxyStr);
        
        // Proxy Type
        switch (strtoupper($type)) {
            case 'SOCKS4':
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                break;
            case 'SOCKS5':
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                break;
            case 'HTTP':
            case 'HTTPS':
            default:
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                break;
        }
        
        // Auth
        if (!empty($user) && !empty($pass)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$user:$pass");
        }
        
        $start = microtime(true);
        $result = curl_exec($ch);
        $end = microtime(true);
        
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $latency = round(($end - $start) * 1000); // ms
        
        if ($error || $info['http_code'] !== 200) {
            $msg = $error ?: "HTTP Error: " . $info['http_code'];
            return [
                'success' => false,
                'message' => "Connection Failed: $msg",
                'latency' => 0
            ];
        }
        
        return [
            'success' => true,
            'message' => "Connection Successful",
            'latency' => $latency,
            'data' => json_decode($result, true) // Optional: verify IP match
        ];
    }
}
