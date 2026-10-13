<?php
// Export only the student table; login accounts and tokens are excluded.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
try {
    $db = require dirname(__DIR__) . '/php/db-mysqli.php';
    $create = $db->query('SHOW CREATE TABLE students')->fetch_row()[1];
    $sql = "-- Practical 11 students table dump\n-- Import into an empty database for inspection.\nSET NAMES utf8mb4;\n\n" . $create . ";\n\n";
    $rows = $db->query('SELECT * FROM students ORDER BY student_id');
    $count = 0;
    while ($row = $rows->fetch_assoc()) {
        $columns = '`' . implode('`,`', array_keys($row)) . '`';
        $values = array_map(fn($value) => $value === null ? 'NULL' : "'" . $db->real_escape_string($value) . "'", array_values($row));
        $sql .= 'INSERT INTO students (' . $columns . ') VALUES (' . implode(',', $values) . ");\n";
        $count++;
    }
    file_put_contents(dirname(__DIR__) . '/sql/practical11-students-dump.sql', $sql);
    $seedCount = $db->query("SELECT COUNT(*) total FROM students WHERE email LIKE '%.lab11@example.com' AND email NOT LIKE 'crud.%' AND email <> 'screenshot.lab11@example.com'")->fetch_assoc()['total'];
    $activeCount = $db->query("SELECT COUNT(*) total FROM students WHERE status='active'")->fetch_assoc()['total'];
    file_put_contents(dirname(__DIR__) . '/docs/Practical11-database-results.json', json_encode(['dump_records' => $count, 'seeded_records' => (int)$seedCount, 'active_students' => (int)$activeCount], JSON_PRETTY_PRINT));
    echo "Exported {$count} students; {$seedCount} lab seed records; {$activeCount} active.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
