<?php
/**
 * register.php
 * Accepts JSON POST, validates, inserts user into MySQL using Prepared Statements.
 * No PHP Sessions used.
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

/* ── Read JSON body ── */
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit;
}

$name     = trim($data['name']     ?? '');
$email    = trim($data['email']    ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password']      ?? '';

/* ── Server-side validation ── */
if (strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Name is too short.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Invalid username format.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password too short.']);
    exit;
}

/* ── DB Config ── */
$dbHost = 'localhost';
$dbName = 'guvi_auth';
$dbUser = 'root';        // Change to your MySQL user
$dbPass = '';            // Change to your MySQL password

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
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

/* ── Create table if not exists ── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100)  NOT NULL,
        email      VARCHAR(180)  NOT NULL UNIQUE,
        username   VARCHAR(30)   NOT NULL UNIQUE,
        password   VARCHAR(255)  NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Check duplicate email / username (Prepared Statement) ── */
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email or username already taken.']);
    exit;
}

/* ── Hash password ── */
$hashed = password_hash($password, PASSWORD_BCRYPT);

/* ── Insert user (Prepared Statement) ── */
$stmt = $pdo->prepare('INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)');
try {
    $stmt->execute([$name, $email, $username, $hashed]);
    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not create account. Please try again.']);
}
