<?php
/**
 * login.php
 * Handles login and logout.
 * - Login : validates credentials from MySQL, creates session token in Redis.
 * - Logout: deletes token from Redis.
 * Session is NOT stored in PHP Sessions – only in Redis + browser localStorage.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
    exit;
}

/* ── Redis Config ── */
$redisHost = '127.0.0.1';
$redisPort = 6379;

/* ── DB Config ── */
$dbHost = 'localhost';
$dbName = 'guvi_auth';
$dbUser = 'root';   // Change to your MySQL user
$dbPass = '';       // Change to your MySQL password

/* ── LOGOUT branch ── */
if (isset($data['action']) && $data['action'] === 'logout') {
    $token = $data['token'] ?? '';
    if ($token) {
        try {
            $redis = new Redis();
            $redis->connect($redisHost, $redisPort);
            $redis->del('session:' . $token);
        } catch (Exception $e) { /* silent */ }
    }
    echo json_encode(['success' => true]);
    exit;
}

/* ── LOGIN branch ── */
$identifier = trim($data['identifier'] ?? '');
$password   = $data['password']        ?? '';

if (!$identifier || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

/* ── Connect MySQL ── */
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

/* ── Fetch user by email or username (Prepared Statement) ── */
$stmt = $pdo->prepare('SELECT id, name, email, username, password FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    exit;
}

/* ── Generate session token ── */
$token = bin2hex(random_bytes(32));

/* ── Store in Redis (TTL: 7 days) ── */
try {
    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);
    $redis->setex(
        'session:' . $token,
        604800, // 7 days in seconds
        json_encode([
            'user_id'  => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'name'     => $user['name']
        ])
    );
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Session service unavailable. Please try again.']);
    exit;
}

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'user_id'  => $user['id'],
    'username' => $user['username'],
    'name'     => $user['name'],
    'email'    => $user['email']
]);
