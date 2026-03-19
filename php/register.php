<?php
/**
 * register.php
 * Registers a new user into MySQL using Prepared Statements.
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

$name     = trim($data['name']     ?? '');
$email    = trim($data['email']    ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password']      ?? '';

// Validate
if (strlen($name) < 2)                               { echo json_encode(['success'=>false,'message'=>'Name too short.']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      { echo json_encode(['success'=>false,'message'=>'Invalid email.']); exit; }
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)){ echo json_encode(['success'=>false,'message'=>'Invalid username.']); exit; }
if (strlen($password) < 8)                           { echo json_encode(['success'=>false,'message'=>'Password too short.']); exit; }

$pdo = getDB();

// Check duplicate — Prepared Statement
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email or username already taken.']); exit;
}

// Insert — Prepared Statement
$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt   = $pdo->prepare('INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)');
try {
    $stmt->execute([$name, $email, $username, $hashed]);
    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not create account.']);
}