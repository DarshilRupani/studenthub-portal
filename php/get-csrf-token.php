<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$_SESSION['captcha'] = '';
for ($i = 0; $i < 5; $i++) $_SESSION['captcha'] .= $chars[random_int(0, strlen($chars) - 1)];
echo json_encode(['token' => $_SESSION['csrf_token'], 'captcha' => $_SESSION['captcha']]);
