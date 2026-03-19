<?php
/**
 * php/config.php
 * Central configuration for MySQL, MongoDB, and Redis connections.
 * Include this in all PHP files.
 */

// ── MySQL Configuration ──────────────────────────────────
define('MYSQL_HOST',   'localhost');
define('MYSQL_PORT',   3306);
define('MYSQL_DB',     'guvi_auth');
define('MYSQL_USER',   'root');          // Change to your MySQL username
define('MYSQL_PASS',   '');             // Change to your MySQL password

// ── MongoDB Configuration ────────────────────────────────
define('MONGO_URI',    'mongodb://localhost:27017');
define('MONGO_DB',     'guvi_profiles');
define('MONGO_COLL',   'user_profiles');

// ── Redis Configuration ──────────────────────────────────
define('REDIS_HOST',   '127.0.0.1');
define('REDIS_PORT',   6379);
define('REDIS_PASS',   null);            // Set if Redis requires password
define('SESSION_TTL',  86400);           // 24 hours in seconds

// ── App Settings ─────────────────────────────────────────
define('TOKEN_LENGTH',  64);
define('BCRYPT_COST',   12);

/**
 * Get MySQL PDO connection (Prepared Statements only)
 */
function getMysql(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            MYSQL_HOST, MYSQL_PORT, MYSQL_DB
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // Force real prepared statements
        ];
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, $options);
    }
    return $pdo;
}

/**
 * Get Redis connection
 */
function getRedis(): Redis {
    static $redis = null;
    if ($redis === null) {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (REDIS_PASS) {
            $redis->auth(REDIS_PASS);
        }
    }
    return $redis;
}

/**
 * Get MongoDB collection
 */
function getMongoCollection(): MongoDB\Collection {
    static $collection = null;
    if ($collection === null) {
        $client     = new MongoDB\Client(MONGO_URI);
        $collection = $client->{MONGO_DB}->{MONGO_COLL};
    }
    return $collection;
}

/**
 * Generate a cryptographically secure token
 */
function generateToken(): string {
    return bin2hex(random_bytes(TOKEN_LENGTH / 2));
}

/**
 * Send a JSON response and exit
 */
function jsonResponse(array $data, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request body as decoded JSON array
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Get the auth token from request headers
 */
function getAuthToken(): ?string {
    $headers = getallheaders();
    return $headers['X-Auth-Token'] ?? null;
}

/**
 * Validate session token against Redis
 * Returns user_id on success, null on failure
 */
function validateSession(): ?array {
    $token = getAuthToken();
    if (!$token) return null;

    try {
        $redis    = getRedis();
        $key      = 'session:' . $token;
        $userData = $redis->get($key);
        if (!$userData) return null;

        // Refresh TTL on access
        $redis->expire($key, SESSION_TTL);

        return json_decode($userData, true);
    } catch (Exception $e) {
        return null;
    }
}

// ── CORS Headers ─────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
