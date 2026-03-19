<?php
/**
 * profile.php
 * GET  : fetch profile from MongoDB
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

// ── GET: fetch profile ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token  = $_GET['token']   ?? '';
    $userId = $_GET['user_id'] ?? '';

    $session = validateSession($token);
    if (!$session || (string)$session['user_id'] !== (string)$userId) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized.','redirect'=>true]); exit;
    }

    try {
        $db         = getMongo();
        $collection = $db->selectCollection(MONGO_COL);
        $doc        = $collection->findOne(['user_id' => $userId]);

        $profile = [];
        if ($doc) {
            $profile = [
                'age'           => $doc['age']           ?? '',
                'dob'           => $doc['dob']           ?? '',
                'contact'       => $doc['contact']       ?? '',
                'gender'        => $doc['gender']        ?? '',
                'city'          => $doc['city']          ?? '',
                'qualification' => $doc['qualification'] ?? '',
                'bio'           => $doc['bio']           ?? ''
            ];
        }
        echo json_encode(['success' => true, 'profile' => $profile]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not load profile.']);
    }
    exit;
}

// ── POST: save profile ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!$data) { echo json_encode(['success'=>false,'message'=>'Invalid JSON.']); exit; }

    $token  = $data['token']   ?? '';
    $userId = $data['user_id'] ?? '';

    $session = validateSession($token);
    if (!$session || (string)$session['user_id'] !== (string)$userId) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized.','redirect'=>true]); exit;
    }

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