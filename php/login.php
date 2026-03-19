<?php
/**
 * login.php
 * Login + Logout handler.
 * Session stored in Redis only — no PHP Sessions.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
}

require_once 'config.php';

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) { echo json_encode(['success' => false, 'message' => 'Invalid JSON.']); exit; }

// ── LOGOUT ──
if (isset($data['action']) && $data['action'] === 'logout') {
    $token = $data['token'] ?? '';
    if ($token) {
        $redis = getRedis();
        $redis->del('session:' . $token);
    }
    echo json_encode(['success' => true]); exit;
}

// ── LOGIN ──
$identifier = trim($data['identifier'] ?? '');
$password   = $data['password']        ?? '';

if (!$identifier || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields required.']); exit;
}

$pdo = getDB();

// Fetch user — Prepared Statement
$stmt = $pdo->prepare('SELECT id, name, email, username, password FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']); exit;
}

// Generate token
$token = bin2hex(random_bytes(32));

// Store in Redis — TTL 7 days
$redis = getRedis();
$redis->setex(
    'session:' . $token,
    604800,
    json_encode([
        'user_id'  => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'name'     => $user['name']
    ])
);

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'user_id'  => $user['id'],
    'username' => $user['username'],
    'name'     => $user['name'],
    'email'    => $user['email']
]);
