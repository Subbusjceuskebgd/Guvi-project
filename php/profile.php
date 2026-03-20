<?php
/**
 * profile.php
 * GET  : fetch user info (MySQL) + profile (MongoDB)
 * POST : save/update profile in MongoDB
 * Session validated via Redis — no PHP Sessions.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'config.php';

// ── Validate Redis session ──
function validateSession($token) {
    if (!$token) return null;
    $redis = getRedis();
    $raw   = $redis->get('session:' . $token);
    if (!$raw) return null;
    return json_decode($raw, true);
}

// ── GET: fetch user info (MySQL) + profile (MongoDB) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';

    $session = validateSession($token);
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.', 'redirect' => true]);
        exit;
    }

    $userId = (string)$session['user_id']; // ← from Redis session

    try {
        // ── Fetch name, email, username from MySQL ──
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT name, email, username FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();

        $user = [
            'name'     => $userRow['name']     ?? '',
            'email'    => $userRow['email']    ?? '',
            'username' => $userRow['username'] ?? ''
        ];

        // ── Fetch profile details from MongoDB ──
        $db         = getMongo();
        $collection = $db->selectCollection(MONGO_COL);
        $profileDoc = $collection->findOne(['user_id' => $userId]);

        $profile = [];
        if ($profileDoc) {
            $profile = [
                'age'           => $profileDoc['age']           ?? '',
                'dob'           => $profileDoc['dob']           ?? '',
                'contact'       => $profileDoc['contact']       ?? '',
                'gender'        => $profileDoc['gender']        ?? '',
                'city'          => $profileDoc['city']          ?? '',
                'qualification' => $profileDoc['qualification'] ?? '',
                'bio'           => $profileDoc['bio']           ?? ''
            ];
        }

        echo json_encode([
            'success' => true,
            'user'    => $user,     // ← from MySQL
            'profile' => $profile   // ← from MongoDB
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not load profile.']);
    }
    exit;
}

// ── POST: save profile in MongoDB ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $token = $data['token'] ?? '';

    $session = validateSession($token);
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.', 'redirect' => true]);
        exit;
    }

    $userId = (string)$session['user_id']; // ← from Redis session

    $profileDoc = [
        'user_id'       => $userId,
        'age'           => (int)($data['age']          ?? 0),
        'dob'           => trim($data['dob']           ?? ''),
        'contact'       => trim($data['contact']       ?? ''),
        'gender'        => trim($data['gender']        ?? ''),
        'city'          => trim($data['city']          ?? ''),
        'qualification' => trim($data['qualification'] ?? ''),
        'bio'           => trim($data['bio']           ?? ''),
        'updated_at'    => new MongoDB\BSON\UTCDateTime()
    ];

    try {
        $db         = getMongo();
        $collection = $db->selectCollection(MONGO_COL);
        $collection->updateOne(
            ['user_id' => $userId],
            ['$set'    => $profileDoc],
            ['upsert'  => true]
        );
        echo json_encode(['success' => true, 'message' => 'Profile updated.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not save profile.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);