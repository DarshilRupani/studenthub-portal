<?php
// Practical 8: database connection test.

ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = require __DIR__ . '/db.php';

    $info = $pdo->query(
        "SELECT DATABASE() AS database_name,
                CURRENT_USER() AS database_user"
    )->fetch();

    $students = $pdo->query(
        "SELECT COUNT(*) FROM students"
    )->fetchColumn();

    $events = $pdo->query(
        "SELECT COUNT(*) FROM events"
    )->fetchColumn();

    $registrations = $pdo->query(
        "SELECT COUNT(*) FROM registrations"
    )->fetchColumn();

    echo "Practical 8 - StudentHub Database Test\n";
    echo "=====================================\n";
    echo "PDO connection successful!\n\n";

    echo "Database: {$info['database_name']}\n";
    echo "Database user: {$info['database_user']}\n\n";

    echo "Students: {$students}\n";
    echo "Events: {$events}\n";
    echo "Registrations: {$registrations}\n";

} catch (Throwable $e) {
    http_response_code(500);
    error_log('StudentHub database test: ' . $e->getMessage());

    echo "Database test failed.\n";
    echo "Check your database settings and ensure MySQL is running.\n";
    echo "Technical details are recorded in the PHP/Apache error log.\n";
}