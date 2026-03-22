<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../lib/SessionManager.php';
require_once __DIR__ . '/../../lib/Utils.php';
require_once __DIR__ . '/../../lib/InterlinkClient.php';

SessionManager::start();

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Get Token for the specific email
$token = Utils::getTokenByEmail($email);
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Account not found or logic error']);
    exit;
}

// Get/Generate Device Info
$deviceInfo = Utils::getOrGenerateDeviceInfo($email);
$proxyConfig = Utils::getProxyConfigForAccount($email);
$client = new InterlinkClient($deviceInfo, $proxyConfig);
$data = json_decode(file_get_contents('php://input'), true);

try {
    switch ($action) {
        case 'list':
            $res = $client->getGroupList($token);
            // Get local account data for Auto Miner status
            $account = Utils::getAccountByEmail($email);
            $activeGroup = $account ? ($account['activeGroupMiner'] ?? null) : null;
            
            echo json_encode([
                'success' => true, 
                'data' => $res, 
                'activeGroupMiner' => $activeGroup
            ]);
            break;

        case 'create':
            $groupId = $data['groupId'] ?? '';
            if (empty($groupId)) throw new Exception("Group ID empty");
            $res = $client->createGroup($token, $groupId);
            
            // Forward API error message if status is not success
            if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
                echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Failed to create group']);
            } else {
                echo json_encode(['success' => true, 'data' => $res, 'message' => $res['message'] ?? 'Group created']);
            }
            break;

        case 'detail':
            $groupId = $_GET['groupId'] ?? '';
            if (empty($groupId)) throw new Exception("Group ID empty");
            $res = $client->getGroupDetail($token, $groupId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'claim':
            $groupId = $data['groupId'] ?? '';
            if (empty($groupId)) throw new Exception("Group ID empty");
            $res = $client->claimGroupMining($token, $groupId);
             if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
                echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Claim failed']);
            } else {
                echo json_encode(['success' => true, 'data' => $res]);
            }
            break;

        case 'set_auto':
            $groupId = $data['groupId'] ?? '';
            $status = $data['status'] ?? false; // true/false
            
            if (empty($groupId)) throw new Exception("Group ID empty");
            
            // 1. Update Preference
            Utils::setActiveGroupMiner($email, $groupId, $status);
            
            // 2. Handle Scheduler
            require_once __DIR__ . '/../../lib/SchedulerService.php';
            require_once __DIR__ . '/../../lib/Cronicle.php';
            require_once __DIR__ . '/../../lib/TelegramBot.php';
            
            $account = Utils::getAccountByEmail($email);
            $cronId = $account['groupCronId'] ?? null;
            
            if ($status) {
                // Determine Webhook URL (similar logic to miner/group_miner)
                if (defined('APP_BASE_URL') && !empty(APP_BASE_URL)) {
                    $baseUrl = rtrim(APP_BASE_URL, '/');
                } else {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $currentDir = dirname($_SERVER['PHP_SELF']); // .../group-mine/api
                    $rootDir = dirname(dirname($currentDir)); // .../
                    $baseUrl = "$protocol$host$rootDir";
                }
                if (substr($baseUrl, -1) === '/') $baseUrl = rtrim($baseUrl, '/');
                
                $webhookUrl = "$baseUrl/api/group_miner.php?username=" . $_SESSION['username'] . "&code=" . $_SESSION['username'] . "1&email=" . urlencode($email);
                
                // Fetch group details to get accurate Next Claim Time
                try {
                    // Use getGroupList because it provides the global nextTimeClaim for the account
                    $groupListRes = $client->getGroupList($token);
                    $nextClaimTime = $groupListRes['data']['nextTimeClaim'] ?? 0;
                } catch (Exception $e) {
                    $nextClaimTime = 0; // Fallback to immediate on error
                }

                $nowMs = time() * 1000;
                // Default: Immediate (1 minute from now)
                $nextTs = $nowMs + 60000;

                // If next claim is in the future, schedule for then + jitter
                if ($nextClaimTime > $nowMs) {
                    // Add 30-180s jitter to avoid exact-time bot detection
                    $jitter = rand(30000, 180000); 
                    $nextTs = $nextClaimTime + $jitter;
                } 
                
                SchedulerService::scheduleNextRun(
                    $email, 
                    $cronId, 
                    $nextTs, 
                    $webhookUrl, 
                    $_SESSION['username'],
                    "Group Miner - $email",
                    "group"
                );
                
                $msg = 'Auto Miner Enabled & Scheduled';
                TelegramBot::send("🟢 <b>Group Auto Miner ENABLED</b>\n\n📧 Email: <code>$email</code>\n🆔 Group ID: <code>$groupId</code>\n⏰ Next Run: " . date('Y-m-d H:i:s', $nextTs/1000));
                
            } else {
                // Turn OFF
                if ($cronId) {
                    Cronicle::deleteEvent($cronId);
                    Utils::updateGroupCronId($email, null);
                }
                 $msg = 'Auto Miner Disabled';
                 TelegramBot::send("🔴 <b>Group Auto Miner DISABLED</b>\n\n📧 Email: <code>$email</code>\n🆔 Group ID: <code>$groupId</code>");
            }

            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'create_invite':
            $groupId = $data['groupId'] ?? '';
            $targetLoginId = $data['loginId'] ?? ''; // Target User ID
            
            if (empty($groupId)) throw new Exception("Group ID empty");
            if (empty($targetLoginId)) throw new Exception("Target User ID (Interlink ID) is required");
            
            $res = $client->createInvite($token, $groupId, $targetLoginId);
            
            if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
                echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Failed to create invite']);
            } else {
                echo json_encode(['success' => true, 'data' => $res]);
            }
            break;

        case 'join':
            $code = $data['code'] ?? '';
            if (empty($code)) throw new Exception("Invite code empty");
            
            $res = $client->joinGroup($token, $code);
            
            if (isset($res['statusCode']) && $res['statusCode'] >= 400) {
                echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Failed to join group']);
            } else {
                echo json_encode(['success' => true, 'data' => $res]);
            }
            break;

        case 'get_members':
            $groupId = $_GET['groupId'] ?? '';
            if (empty($groupId)) {
                throw new Exception("Group ID is required");
            }
            // Group Detail contains the member list
            $res = $client->getGroupDetail($token, $groupId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;
                
        default:
            throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
