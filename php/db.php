<?php
// Practical 8: reusable PDO database connection.

$configFile = __DIR__ . '/db-config.php';

if (!is_file($configFile)) {
    throw new RuntimeException('Database configuration file is missing.');
}

$config = require $configFile;

$dsn = "mysql:host={$config['host']};port={$config['port']};"
     . "dbname={$config['database']};charset=utf8mb4";

return new PDO(
    $dsn,
    $config['username'],
    $config['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);