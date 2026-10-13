<?php
require __DIR__ . '/php/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the logout button on your dashboard.');
}
if (!validAuthToken()) {
    http_response_code(403);
    exit('Invalid logout request. Reload your dashboard and try again.');
}
try {
    revokeRemember();
    endLogin();
    header('Location: login.php?message=logout', true, 303);
} catch (Throwable $e) {
    error_log('StudentHub logout: ' . $e->getMessage());
    endLogin();
    http_response_code(503);
    echo 'Session closed. Remember-me cleanup failed; please try again later.';
}
