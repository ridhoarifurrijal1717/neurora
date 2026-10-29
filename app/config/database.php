<?php

$local = is_file(__DIR__ . '/database.local.php') ? require __DIR__ . '/database.local.php' : [];
if (!is_array($local)) $local = [];
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
    $value = getenv($key);
    define($key, $value !== false ? $value : (string)($local[$key] ?? ''));
}
unset($local, $key, $value);


function db(): ?PDO
{
    static $pdo = null;
    static $tried = false;
    if ($pdo || $tried) return $pdo;
    $tried = true;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        $pdo = null;
    }
    return $pdo;
}
