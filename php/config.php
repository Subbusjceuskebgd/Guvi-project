<?php

require_once __DIR__ . '/../vendor/autoload.php';

// ── MySQL Config ──
define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'guvi_auth');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');

// ── MongoDB Config ──
define('MONGO_URI', getenv('MONGO_URL') ?: 'mongodb://localhost:27017');
define('MONGO_DB',  'guvi_profiles');
define('MONGO_COL', 'profiles');

// ── Redis Config ──
define('REDIS_HOST', getenv('REDISHOST')     ?: '127.0.0.1');
define('REDIS_PORT', getenv('REDISPORT')     ?: 6379);
define('REDIS_PASS', getenv('REDISPASSWORD') ?: null);

// ── PDO Connection (MySQL) ──
function getDB() {
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]);

        // Create users table if not exists
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

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }
}

// ── Redis Connection (Predis) ──
function getRedis() {
    try {
        $params = [
            'scheme' => 'tcp',
            'host'   => REDIS_HOST,
            'port'   => REDIS_PORT,
        ];
        if (REDIS_PASS) {
            $params['password'] = REDIS_PASS;
        }
        return new Predis\Client($params);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Session service unavailable.']);
        exit;
    }
}

// ── MongoDB Connection ──
function getMongo() {
    try {
        $client = new MongoDB\Client(MONGO_URI);
        return $client->selectDatabase(MONGO_DB);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Profile database unavailable.']);
        exit;
    }
}