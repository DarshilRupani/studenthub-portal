<?php
session_start();
header('Content-Type: application/json');

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo json_encode(['token' => $_SESSION['csrf_token']]);