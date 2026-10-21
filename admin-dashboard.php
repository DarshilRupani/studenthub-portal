<?php
require __DIR__ . '/php/auth.php';
$user = requireRole('admin');
$db = authDb();
$stats = [];
foreach ([
    'users' => "SELECT COUNT(*) AS total FROM users",
    'students' => "SELECT COUNT(*) AS total FROM students WHERE status='active'",
    'events' => "SELECT COUNT(*) AS total FROM events",
    'registrations' => "SELECT COUNT(*) AS total FROM registrations",
] as $key => $sql) $stats[$key] = (int)$db->query($sql)->fetch_assoc()['total'];
$latest = $db->query("SELECT r.registered_at,u.fullname AS student_name,e.title AS event_title FROM registrations r JOIN students u ON u.student_id=r.student_id JOIN events e ON e.event_id=r.event_id ORDER BY r.registered_at DESC,r.registration_id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$audit = $db->query("SELECT a.action,a.target_type,a.target_id,a.summary,a.created_at,u.fullname AS actor_name FROM audit_logs a LEFT JOIN users u ON u.user_id=a.actor_user_id ORDER BY a.created_at DESC,a.audit_id DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub | Admin Dashboard</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Main navigation"><ul><li><a href="home.html">Home</a></li><li><a href="dashboard-router.php">Dashboard</a></li><li><a href="students-manage.php">Manage students</a></li><li><a href="events-manage.php">Manage events</a></li><li><a href="api-events-demo.php">API demo</a></li></ul></nav></header>
<main class="dashboard-main">
<section class="dashboard-welcome"><div><span class="dashboard-label">Admin DASHBOARD</span><h2>Welcome, <?= escapeHtml($user['fullname']) ?>!</h2><p>You are logged in as an administrator.</p></div>
<form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><button type="submit" class="login-button">Logout</button></form></section>
<section class="dashboard-stat-grid" aria-label="Portal statistics">
<article class="dashboard-panel"><span class="panel-label">USERS</span><h3><?= $stats['users'] ?></h3><p>Registered accounts</p></article>
<article class="dashboard-panel"><span class="panel-label">STUDENTS</span><h3><?= $stats['students'] ?></h3><p>Active student records</p></article>
<article class="dashboard-panel"><span class="panel-label">EVENTS</span><h3><?= $stats['events'] ?></h3><p>Campus events</p></article>
<article class="dashboard-panel"><span class="panel-label">REGISTRATIONS</span><h3><?= $stats['registrations'] ?></h3><p>Event sign-ups</p></article>
</section>
<section class="dashboard-panel"><h3>Portal management</h3><p>Add, search, edit and delete student records here. <a href="students-manage.php">Manage students</a></p></section>
<section class="dashboard-panel"><h3>Event management</h3><p>Add, update and manage event posters. <a href="events-manage.php">Manage events</a></p></section>
<section class="dashboard-panel"><h3>Latest registrations</h3><?php if (!$latest): ?><p>No registrations yet.</p><?php else: ?><ul><?php foreach ($latest as $row): ?><li><?= escapeHtml($row['student_name']) ?> registered for <?= escapeHtml($row['event_title']) ?> — <?= escapeHtml($row['registered_at']) ?></li><?php endforeach; ?></ul><?php endif; ?></section>
<section class="dashboard-panel"><h3>Recent admin audit log</h3><?php if (!$audit): ?><p>No admin actions recorded yet.</p><?php else: ?><ul><?php foreach ($audit as $row): ?><li><?= escapeHtml($row['summary']) ?> by <?= escapeHtml($row['actor_name'] ?: 'removed account') ?> (<?= escapeHtml($row['target_type']) ?> #<?= (int)$row['target_id'] ?>) — <?= escapeHtml($row['created_at']) ?></li><?php endforeach; ?></ul><?php endif; ?></section>
</main></body></html>
