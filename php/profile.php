<?php
/**
 * profile.php
 * GET  : fetch profile from MongoDB (session validated via Redis).
 * POST : update profile in MongoDB (session validated via Redis).
 * No PHP Sessions used.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* ── Redis Config ── */
$redisHost = '127.0.0.1';
$redisPort = 6379;

/* ── MongoDB Config ── */
$mongoUri    = 'mongodb://localhost:27017';
$mongoDbName = 'guvi_profiles';
$mongoCol    = 'profiles';

/* ── Validate session token via Redis ── */
function validateSession($token, $redisHost, $redisPort) {
    if (!$token) return null;
    try {
        $redis = new Redis();
        $redis->connect($redisHost, $redisPort);
        $raw = $redis->get('session:' . $token);
        if (!$raw) return null;
        return json_decode($raw, true);
    } catch (Exception $e) {
        return null;
    }
}

/* ── GET: Fetch profile ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token   = $_GET['token']   ?? '';
    $userId  = $_GET['user_id'] ?? '';

    $session = validateSession($token, $redisHost, $redisPort);
    if (!$session || (string)$session['user_id'] !== (string)$userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.', 'redirect' => true]);
        exit;
    }

    /* ── Connect MongoDB ── */
    try {
        $manager    = new MongoDB\Driver\Manager($mongoUri);
        $filter     = ['user_id' => $userId];
        $query      = new MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor     = $manager->executeQuery("$mongoDbName.$mongoCol", $query);
        $results    = $cursor->toArray();

        $profile = [];
        if (!empty($results)) {
            $doc = (array)$results[0];
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

/* ── POST: Update profile ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $token  = $data['token']   ?? '';
    $userId = $data['user_id'] ?? '';

    $session = validateSession($token, $redisHost, $redisPort);
    if (!$session || (string)$session['user_id'] !== (string)$userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.', 'redirect' => true]);
        exit;
    }

    /* ── Sanitize profile fields ── */
    $profileDoc = [
        'user_id'       => $userId,
        'age'           => (int)($data['age']           ?? 0),
        'dob'           => trim($data['dob']            ?? ''),
        'contact'       => trim($data['contact']        ?? ''),
        'gender'        => trim($data['gender']         ?? ''),
        'city'          => trim($data['city']           ?? ''),
        'qualification' => trim($data['qualification']  ?? ''),
        'bio'           => trim($data['bio']            ?? ''),
        'updated_at'    => new MongoDB\BSON\UTCDateTime()
    ];

    /* ── Upsert into MongoDB ── */
    try {
        $manager = new MongoDB\Driver\Manager($mongoUri);

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['user_id' => $userId],   // filter
            ['$set'    => $profileDoc], // update
            ['upsert'  => true]         // insert if not exists
        );
        $manager->executeBulkWrite("$mongoDbName.$mongoCol", $bulk);

        echo json_encode(['success' => true, 'message' => 'Profile updated.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not save profile.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
