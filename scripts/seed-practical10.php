<?php
// Run from the terminal: set STUDENTHUB_DEMO_PASSWORD first.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$password = getenv('STUDENTHUB_DEMO_PASSWORD');
if (!$password || strlen($password) < 8 || strlen($password) > 72) {
    exit("Set STUDENTHUB_DEMO_PASSWORD to an 8-72 character lab password.\n");
}
try {
    $db = require dirname(__DIR__) . '/php/db-mysqli.php';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    foreach (['student', 'admin', 'faculty'] as $role) {
        $email = 'lab10.' . $role . '@example.com';
        $name = 'Lab ' . ucfirst($role);
        $statement = $db->prepare("INSERT INTO users (fullname,email,mobile,password_hash,course,year_of_study,gender,role) VALUES (?,?,'9876543210',?,'bca',1,'other',?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),role=VALUES(role)");
        $statement->bind_param('ssss', $name, $email, $hash, $role);
        $statement->execute();
        echo "Ready: {$email} ({$role})\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Seed failed: " . $e->getMessage() . "\n");
    exit(1);
}
