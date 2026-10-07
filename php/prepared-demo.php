<?php
// Practical 8: fetch registrations using a prepared statement.

ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

// Accept a positive integer student ID.
$input = $_GET['student_id'] ?? '1';

$studentId = is_string($input)
    ? filter_var($input, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ])
    : false;

if ($studentId === false) {
    http_response_code(400);
    exit("Invalid student ID. Enter a positive whole number.");
}

try {
    $pdo = require __DIR__ . '/db.php';

    $sql = "
        SELECT
            r.registration_id,
            s.fullname,
            e.title,
            e.event_date,
            e.venue
        FROM registrations AS r
        INNER JOIN students AS s
            ON r.student_id = s.student_id
        INNER JOIN events AS e
            ON r.event_id = e.event_id
        WHERE s.student_id = :student_id
        ORDER BY r.registration_id
    ";

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':student_id', $studentId, PDO::PARAM_INT);
    $statement->execute();

    $registrations = $statement->fetchAll();

    echo "Practical 8 - Prepared Statement Demo\n";
    echo "Student ID: {$studentId}\n";
    echo "Registrations found: " . count($registrations) . "\n\n";

    if (!$registrations) {
        echo "No registrations found for this student ID.\n";
    }

    foreach ($registrations as $registration) {
        echo "Registration ID: {$registration['registration_id']}\n";
        echo "Student: {$registration['fullname']}\n";
        echo "Event: {$registration['title']}\n";
        echo "Date: {$registration['event_date']}\n";
        echo "Venue: {$registration['venue']}\n\n";
    }

} catch (Throwable $e) {
    http_response_code(500);
    error_log('StudentHub prepared statement: ' . $e->getMessage());

    echo "Could not load registrations. Check the PHP/Apache error log.";
}