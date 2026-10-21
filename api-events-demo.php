<?php
require __DIR__ . '/php/auth.php';
$user = requireRole('admin');
$token = authToken();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?= escapeHtml($token) ?>"><title>StudentHub | Events API Demo</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Main navigation"><ul><li><a href="admin-dashboard.php">Admin dashboard</a></li><li><a href="events-manage.php">Manage events</a></li></ul></nav></header>
<main class="dashboard-main">
<section class="dashboard-welcome"><div><span class="dashboard-label">PRACTICAL 13</span><h2>JSON Events API</h2><p>Fetch event records without reloading the page.</p></div></section>
<section class="dashboard-panel"><h3>Add an event through Fetch API</h3><form id="api-event-form"><label for="api-title">Title</label><input id="api-title" name="title" required maxlength="150"><label for="api-description">Description</label><textarea id="api-description" name="description" maxlength="2000"></textarea><label for="api-event-date">Date and time</label><input id="api-event-date" name="event_date" type="datetime-local" required><label for="api-venue">Venue</label><input id="api-venue" name="venue" required maxlength="150"><label for="api-category">Category</label><select id="api-category" name="category"><option>technical</option><option>academic</option><option>cultural</option><option>sports</option><option>other</option></select><label for="api-status">Status</label><select id="api-status" name="status"><option>open</option><option>closed</option></select><button class="login-button" type="submit">Add event</button></form><p id="api-message" role="status" aria-live="polite"></p></section>
<section class="dashboard-panel"><h3>Events</h3><p id="api-loading" role="status">Loading events…</p><div class="table-responsive"><table><thead><tr><th>Title</th><th>Date</th><th>Venue</th><th>Category</th><th>Status</th><th>Action</th></tr></thead><tbody id="api-events"></tbody></table></div></section>
</main>
<script src="js/api-demo.js" defer></script>
</body></html>
