<?php
// Returns stored registrations (never passwords) for the admin-style view page.
header('Content-Type: application/json');

$JSON_FILE = __DIR__ . '/storage/registrations.json';

if (!file_exists($JSON_FILE)) {
    echo json_encode([]);
    exit;
}

$content = file_get_contents($JSON_FILE);
$records = json_decode($content, true);
echo json_encode(is_array($records) ? array_reverse($records) : []);