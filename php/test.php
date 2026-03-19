<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';

$result = [];

// Test 1 - Environment Variables
$result['env'] = [
    'MYSQLHOST'     => getenv('MYSQLHOST')     ?: 'NOT SET',
    'MYSQLDATABASE' => getenv('MYSQLDATABASE') ?: 'NOT SET',
    'MYSQLUSER'     => getenv('MYSQLUSER')     ?: 'NOT SET',
    'MYSQLPORT'     => getenv('MYSQLPORT')     ?: 'NOT SET',
    'REDISHOST'     => getenv('REDISHOST')     ?: 'NOT SET',
    'REDISPORT'     => getenv('REDISPORT')     ?: 'NOT SET',
    'REDISPASSWORD' => getenv('REDISPASSWORD') ? 'SET' : 'NOT SET',
    'MONGO_URL'     => getenv('MONGO_URL')     ? 'SET' : 'NOT SET',
];

// Test 2 - MySQL
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('MYSQLHOST'), getenv('MYSQLPORT'), getenv('MYSQLDATABASE')
    );
    $pdo = new PDO($dsn, getenv('MYSQLUSER'), getenv('MYSQLPASSWORD'));
    $result['mysql'] = 'CONNECTED ✅';
} catch (Exception $e) {
    $result['mysql'] = 'FAILED ❌ ' . $e->getMessage();
}

// Test 3 - Redis
try {
    $redis = new Predis\Client([
        'scheme'   => 'tcp',
        'host'     => getenv('REDISHOST'),
        'port'     => getenv('REDISPORT'),
        'password' => getenv('REDISPASSWORD'),
    ]);
    $redis->ping();
    $result['redis'] = 'CONNECTED ✅';
} catch (Exception $e) {
    $result['redis'] = 'FAILED ❌ ' . $e->getMessage();
}

// Test 4 - MongoDB
try {
    $client = new MongoDB\Client(getenv('MONGO_URL'));
    $client->listDatabases();
    $result['mongodb'] = 'CONNECTED ✅';
} catch (Exception $e) {
    $result['mongodb'] = 'FAILED ❌ ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);