<?php
// verify_target.php - Real Mining Check via Proxy
require_once __DIR__ . '/../../lib/Utils.php';

// Disable error display to assume Clean JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    Utils::jsonResponse(false, 'Invalid Input');
}

// 1. Proxy Setup
$proxyConfig = [
    'type' => $input['type'] ?? 'HTTP',
    'host' => $input['host'] ?? '',
    'port' => $input['port'] ?? '',
    'user' => $input['username'] ?? '',
    'pass' => $input['password'] ?? ''
];

if (empty($proxyConfig['host']) || empty($proxyConfig['port'])) {
    Utils::jsonResponse(false, 'Host and Port required');
}

// 2. Auth Setup
$testEmail = $input['testEmail'] ?? null;
if (!$testEmail) {
    Utils::jsonResponse(false, 'No account selected for verification');
}

$allTokens = Utils::getAllTokensGlobal();
$token = $allTokens[$testEmail] ?? null;

if (!$token) {
    Utils::jsonResponse(false, "No active token found for $testEmail. Please login again.");
}

// --- Helper: Proxy Request Function ---
function proxyRequest($method, $endpoint, $data = [], $token = null, $proxyConfig = []) {
    $baseUrl = "https://prod.interlinklabs.ai/api/v1";
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    
    $headers = [
        "Accept-Encoding: gzip",
        "User-Agent: okhttp/4.12.0",
        "Content-Type: application/json"
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    
    // Proxy Logic
    $proxyStr = "{$proxyConfig['host']}:{$proxyConfig['port']}";
    curl_setopt($ch, CURLOPT_PROXY, $proxyStr);
    
    switch (strtoupper($proxyConfig['type'])) {
        case 'SOCKS4': curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4); break;
        case 'SOCKS5': curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); break;
        default: curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); break;
    }
    
    if (!empty($proxyConfig['user']) && !empty($proxyConfig['pass'])) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyConfig['user']}:{$proxyConfig['pass']}");
    }

    // SSL & Timeouts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) return ['success' => false, 'message' => "Proxy Error: $error"];
    
    // Authorization Check
    if ($httpCode === 401) return ['success' => false, 'message' => "Unauthorized (401). Token may be expired."];

    $json = json_decode($response, true);
    return ['success' => true, 'data' => $json, 'raw' => $response, 'code' => $httpCode];
}

// --- Mining Logic ---
// Mimic miner.php
try {
    // A. Check Claimable
    $checkReq = proxyRequest('GET', "/token/check-is-claimable", [], $token, $proxyConfig);
    
    if (!$checkReq['success']) {
        throw new Exception($checkReq['message']);
    }

    $checkData = $checkReq['data'];
    $isClaimable = $checkData['data']['isClaimable'] ?? false;
    $nextFrame = $checkData['data']['nextFrame'] ?? 0;

    $resultData = [
        'email' => $testEmail,
        'isAutoMinerActive' => true,
        'nextClaimTime' => $nextFrame
    ];

    if ($isClaimable) {
        // B. Claim
        // Note: Sending empty object as body
        $claimReq = proxyRequest('POST', "/token/claim-airdrop", new stdClass(), $token, $proxyConfig);
        
        if (!$claimReq['success']) {
             $resultData['status'] = 'Error';
             $resultData['message'] = "Claim Request Failed: " . $claimReq['message'];
        } else {
            $claimResp = $claimReq['data'];
            
            if (isset($claimResp['code']) && $claimResp['code'] == '00') {
                 $resultData['status'] = 'Success';
                 $resultData['message'] = "Claim Success: Claim airdrop Success";
            } else {
                $msg = $claimResp['message'] ?? 'Unknown Error';
                $isQueue = stripos($msg, 'Join to queue') !== false;
                $isFuture = $nextFrame > (time() * 1000);
                
                if ($isQueue || $isFuture) {
                    $resultData['status'] = 'Success';
                    $resultData['message'] = "Claim Success: $msg";
                } else {
                    $resultData['status'] = 'Error';
                    $resultData['message'] = "Claim Failed: $msg";
                }
            }
        }
    } else {
        // Not Claimable
        $timeStr = $nextFrame > 0 ? date('h:i:s A', $nextFrame / 1000) : 'Unknown';
        $resultData['status'] = 'Skipped';
        $resultData['message'] = "Not claimable yet. Next: $timeStr";
    }
    
    // --- Final Output Formatting ---
    $finalOutput = [
        'success' => true,
        'message' => 'Mining cycle completed',
        'data' => [$resultData]
    ];
    
    // We return this inside 'apiResponse' for the frontend to show raw
    Utils::jsonResponse(true, 'Mining Check Complete', ['apiResponse' => $finalOutput]);

} catch (Exception $e) {
    Utils::jsonResponse(false, $e->getMessage());
}
