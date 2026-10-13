<?php
require __DIR__ . '/php/auth.php';
$user = requireRole('admin');
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub | Admin Dashboard</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Main navigation"><ul><li><a href="home.html">Home</a></li><li><a href="dashboard-router.php">Dashboard</a></li></ul></nav></header>
<main class="dashboard-main">
<section class="dashboard-welcome"><div><span class="dashboard-label">Admin DASHBOARD</span><h2>Welcome, <?= escapeHtml($user['fullname']) ?>!</h2><p>You are logged in as an administrator.</p></div>
<form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><button type="submit" class="login-button">Logout</button></form></section>
<section class="dashboard-panel"><h3>Portal management</h3><p>Add, search, edit and delete student records here. <a href="students-manage.php">Manage students</a></p></section>
<section class="dashboard-panel"><h3>Event management</h3><p>Add, update and manage event posters. <a href="events-manage.php">Manage events</a></p></section>
</main></body></html>
