<?php
require __DIR__ . '/php/auth.php';
$user = requireRole('faculty');
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub | Faculty Dashboard</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Faculty navigation"><ul><li><a href="home.html">Home</a></li><li><a href="dashboard-router.php">Faculty dashboard</a></li><li><a href="events.html">Campus events</a></li><li><a href="faq.html">Help</a></li></ul></nav></header>
<main class="dashboard-main">
<section class="dashboard-welcome"><div><span class="dashboard-label">Faculty DASHBOARD</span><h2>Welcome, <?= escapeHtml($user['fullname']) ?>!</h2><p>You are logged in as a faculty.</p></div>
<form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><button type="submit" class="login-button">Logout</button></form></section>
<section class="dashboard-panel"><h3>Teaching information</h3><p>View your teaching notices and academic information here.</p></section>
</main></body></html>
