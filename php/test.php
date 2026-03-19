<?php
header('Content-Type: text/plain');

echo "=== Loaded Extensions ===\n";
echo implode("\n", get_loaded_extensions());

echo "\n\n=== PHP INI File ===\n";
echo php_ini_loaded_file() . "\n";

echo "\n\n=== Scanned INI Files ===\n";
$scanned = php_ini_scanned_files();
echo ($scanned ?: 'none') . "\n";

echo "\n\n=== Search mongodb.so in nix store ===\n";
$paths = glob('/nix/store/*/lib/php/extensions/*mongodb*');
if ($paths) {
    foreach ($paths as $p) echo $p . "\n";
} else {
    echo "NOT FOUND in /nix/store\n";
}

echo "\n\n=== Search redis.so in nix store ===\n";
$paths2 = glob('/nix/store/*/lib/php/extensions/*redis*');
if ($paths2) {
    foreach ($paths2 as $p) echo $p . "\n";
} else {
    echo "NOT FOUND in /nix/store\n";
}

echo "\n\n=== All PHP extensions in nix store ===\n";
$all = glob('/nix/store/*/lib/php/extensions/*.so');
if ($all) {
    foreach ($all as $p) echo $p . "\n";
} else {
    echo "NONE FOUND\n";
}

echo "\n\n=== Environment Variables ===\n";
echo "MYSQLHOST: "     . (getenv('MYSQLHOST')     ?: 'NOT SET') . "\n";
echo "REDISHOST: "     . (getenv('REDISHOST')     ?: 'NOT SET') . "\n";
echo "MONGO_URL: "     . (getenv('MONGO_URL')     ? 'SET' : 'NOT SET') . "\n";