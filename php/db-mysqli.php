<?php
// Practical 9: reuse the private configuration without changing Practical 8 PDO.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$config = require __DIR__ . '/db-config.php';
$db = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], (int) $config['port']);
$db->set_charset('utf8mb4');
return $db;
