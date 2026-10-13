<?php
require __DIR__ . '/php/auth.php';
try {
    if (currentUser()) redirectDashboard();
    header('Location: login.php?message=unauthorized', true, 303);
} catch (Throwable $e) {
    error_log('StudentHub dashboard: ' . $e->getMessage());
    http_response_code(503);
    echo 'Sign-in is temporarily unavailable. Please try again later.';
}
