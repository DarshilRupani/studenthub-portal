<?php
require __DIR__.'/php/auth.php';
$user=requireRole('admin');
require __DIR__.'/php/event-functions.php';
$data=['title'=>'','description'=>'','event_date'=>'','venue'=>'','category'=>'technical','status'=>'open'];
$errors=[];$editId=null;$confirmation=null;$newPoster=null;$transaction=false;
$messages=['added'=>'Event added successfully.','updated'=>'Event updated successfully.','deleted'=>'Event deleted successfully.'];
$message=$messages[eventText($_GET,'message')]??'';$messageType='success';
$filters=eventFilters($_GET);
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH']??0)>POSTER_LIMIT) {
            http_response_code(413);$message='Request too large. Poster must be 2 MB or smaller.';$messageType='error';
        } elseif (!validAuthToken()) {
            http_response_code(403);$message='Your form expired. Reload the page and try again.';$messageType='error';
        } else {
            $action=eventText($_POST,'action');$id=eventId($_POST['event_id']??'');
            if (in_array($action,['add','edit'],true)) {
                [$data,$errors]=eventInput($_POST);
                [$poster,$posterError]=validatePoster($_FILES['poster']??null);
                if ($posterError!=='') $errors['poster']=$posterError;
                if ($action==='edit') $editId=$id?:null;
                if ($action==='edit' && !$id) $errors['record']='Invalid event ID.';
                if ($errors) {http_response_code(422);$message='Please correct the errors below.';$messageType='error';}
                else {
                    $db=authDb();$db->begin_transaction();$transaction=true;
                    $old=$action==='edit' ? eventQuery('SELECT * FROM events WHERE event_id=? FOR UPDATE',[$id],'i')->get_result()->fetch_assoc() : null;
                    if ($action==='edit' && !$old) throw new OutOfBoundsException('Event not found.');
                    $newPoster=$poster ? storePoster($poster) : null;
                    $filename=$newPoster ?: ($old['poster_filename']??null);
                    $date=DateTime::createFromFormat('!Y-m-d\TH:i',$data['event_date'])->format('Y-m-d H:i:s');
                    $values=[$data['title'],$data['description'],$date,$data['venue'],$data['category'],$data['status'],$filename];
                    if ($action==='add') {
                        eventQuery('INSERT INTO events (title,description,event_date,venue,category,status,poster_filename) VALUES (?,?,?,?,?,?,?)',$values);
                        $id=$db->insert_id;
                    } else { $values[]=$id;eventQuery('UPDATE events SET title=?,description=?,event_date=?,venue=?,category=?,status=?,poster_filename=? WHERE event_id=?',$values,'sssssssi'); }
                    $after=eventQuery('SELECT * FROM events WHERE event_id=?',[$id],'i')->get_result()->fetch_assoc();
                    eventAudit($id,$data['title'],$action==='add'?'added':'updated',$old,$after);
                    $db->commit();$transaction=false;
                    if ($newPoster && $old) removePoster($old['poster_filename']);
                    header('Location: events-manage.php?message='.($action==='add'?'added':'updated'),true,303);exit;
                }
            } elseif ($action==='delete' && $id) {
                if (eventText($_POST,'confirm_delete')!=='1') {http_response_code(422);$message='Please confirm before deleting the event.';$messageType='error';}
                else {
                    $db=authDb();$db->begin_transaction();$transaction=true;
                    $old=eventQuery('SELECT * FROM events WHERE event_id=? FOR UPDATE',[$id],'i')->get_result()->fetch_assoc();
                    if (!$old) throw new OutOfBoundsException('Event not found.');
                    $count=eventQuery('SELECT COUNT(*) total FROM registrations WHERE event_id=?',[$id],'i')->get_result()->fetch_assoc()['total'];
                    if ($count>0) throw new DomainException('This event has registrations and cannot be deleted. Set its status to closed instead.');
                    eventAudit($id,$old['title'],'deleted',$old,null);
                    eventQuery('DELETE FROM events WHERE event_id=?',[$id],'i');
                    $db->commit();$transaction=false;removePoster($old['poster_filename']);
                    header('Location: events-manage.php?message=deleted',true,303);exit;
                }
            } else {http_response_code(400);$message='Invalid event action.';$messageType='error';}
        }
    } else {
        foreach(['edit','delete'] as $action) if (isset($_GET[$action])) {
            $id=eventId($_GET[$action]);
            $record=$id ? eventQuery('SELECT * FROM events WHERE event_id=?',[$id],'i')->get_result()->fetch_assoc() : null;
            if (!$record) throw new OutOfBoundsException('Event not found.');
            if ($action==='edit') {$data=$record;$data['event_date']=str_replace(' ','T',substr($record['event_date'],0,16));$editId=$id;}
            else $confirmation=$record;
        }
    }
} catch (Throwable $e) {
    if ($transaction) authDb()->rollback();
    if ($newPoster) removePoster($newPoster);
    if ($e instanceof OutOfBoundsException) {http_response_code(404);$message=$e->getMessage();}
    elseif ($e instanceof DomainException || ($e instanceof mysqli_sql_exception && $e->getCode()===1451)) {http_response_code(409);$message='This event has registrations and cannot be deleted. Set its status to closed instead.';}
    else {http_response_code(500);$message='Could not save the event. Please try again later.';error_log('StudentHub event changes: '.$e->getMessage());}
    $messageType='error';
}
try {
    [$where,$values]=eventWhere($filters);
    $rows=eventQuery('SELECT * FROM events'.$where.' ORDER BY event_date,event_id',$values)->get_result()->fetch_all(MYSQLI_ASSOC);
    $audit=eventQuery('SELECT event_title,action,created_at FROM event_audit ORDER BY audit_id DESC LIMIT 10')->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {http_response_code(500);$message='Could not load events. Please try again later.';$messageType='error';$rows=[];$audit=[];error_log('StudentHub event list: '.$e->getMessage());}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub | Event Management</title><link rel="stylesheet" href="css/vendor/bootstrap.min.css"><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/event-management.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Main navigation"><ul><li><a href="admin-dashboard.php">Admin Dashboard</a></li><li><a href="events-manage.php" class="active">Event Management</a></li><li><a href="events.html">Campus Events</a></li></ul></nav></header>
<main class="container py-4">
<h2>Event Management</h2><p>Add and manage campus events and posters.</p>
<?php if($message!==''): ?><div class="alert <?= $messageType==='error'?'alert-danger':'alert-success' ?>" role="status"><?= escapeHtml($message) ?></div><?php endif; ?>
<?php if($confirmation): ?>
<section class="card p-3 mb-4"><h3>Confirm event deletion</h3><p>Delete <strong><?= escapeHtml($confirmation['title']) ?></strong>? This removes the event and its poster. Events with registrations cannot be deleted.</p><form action="events-manage.php" method="post"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$confirmation['event_id'] ?>"><label><input type="checkbox" name="confirm_delete" value="1" required> I confirm this deletion</label><p class="mt-3"><button class="btn btn-danger" type="submit">Delete event</button> <a href="events-manage.php">Cancel</a></p></form></section>
<?php else: ?>
<section class="card p-3 mb-4"><h3><?= $editId?'Edit event':'Add event' ?></h3>
<form action="events-manage.php" method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><input type="hidden" name="action" value="<?= $editId?'edit':'add' ?>"><input type="hidden" name="event_id" value="<?= (int)$editId ?>">
<?php foreach(['title'=>'Event Title','venue'=>'Venue','event_date'=>'Date and Time'] as $field=>$label): ?><div class="col-md-4"><label class="form-label" for="<?= $field ?>"><?= $label ?></label><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" type="<?= $field==='event_date'?'datetime-local':'text' ?>" value="<?= escapeHtml($data[$field]) ?>" <?= $field==='event_date'?'':'minlength="2" maxlength="150"' ?> required><?php if(isset($errors[$field])): ?><p class="text-danger small"><?= escapeHtml($errors[$field]) ?></p><?php endif; ?></div><?php endforeach; ?>
<div class="col-md-4"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category"><?php foreach(EVENT_CATEGORIES as $key=>$label): ?><option value="<?= $key ?>" <?= $data['category']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><?php if(isset($errors['category'])): ?><p class="text-danger small"><?= escapeHtml($errors['category']) ?></p><?php endif; ?></div>
<div class="col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><?php foreach(['open'=>'Open','closed'=>'Closed'] as $key=>$label): ?><option value="<?= $key ?>" <?= $data['status']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><?php if(isset($errors['status'])): ?><p class="text-danger small"><?= escapeHtml($errors['status']) ?></p><?php endif; ?></div>
<div class="col-md-4"><label class="form-label" for="poster">Poster (optional)</label><input class="form-control" type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG or WebP. Maximum 2 MB and 4096 × 4096 pixels. Leave empty to keep the current poster.</small><?php if(isset($errors['poster'])): ?><p class="text-danger small"><?= escapeHtml($errors['poster']) ?></p><?php endif; ?><?php if($editId && !empty($data['poster_filename'])): ?><img class="poster-preview mt-2" src="event-poster.php?id=<?= (int)$editId ?>" alt="Current event poster"><?php endif; ?></div>
<div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="2" maxlength="2000"><?= escapeHtml($data['description']) ?></textarea><?php if(isset($errors['description'])): ?><p class="text-danger small"><?= escapeHtml($errors['description']) ?></p><?php endif; ?></div>
<div class="col-12"><button class="btn btn-primary" type="submit"><?= $editId?'Save changes':'Add event' ?></button><?php if($editId): ?> <a href="events-manage.php">Cancel</a><?php endif; ?><?php if(isset($errors['record'])): ?><p class="text-danger"><?= escapeHtml($errors['record']) ?></p><?php endif; ?></div>
</form></section>
<?php endif; ?>
<section class="card p-3 mb-4"><h3>Event list</h3>
<form method="get" action="events-manage.php" class="row g-3 align-items-end mb-3"><div class="col-md-4"><label class="form-label" for="event-search">Search title or venue</label><input class="form-control" id="event-search" name="q" value="<?= escapeHtml($filters['q']) ?>"></div><div class="col-md-3"><label class="form-label" for="category-filter">Category filter</label><select class="form-select" id="category-filter" name="category"><option value="">All categories</option><?php foreach(EVENT_CATEGORIES as $key=>$label): ?><option value="<?= $key ?>" <?= $filters['category']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label" for="status-filter">Status filter</label><select class="form-select" id="status-filter" name="status"><option value="">Open and closed</option><?php foreach(['open'=>'Open','closed'=>'Closed'] as $key=>$label): ?><option value="<?= $key ?>" <?= $filters['status']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary" type="submit">Filter</button> <a href="events-manage.php">Reset</a></div></form>
<p><?= count($rows) ?> matching event<?= count($rows)===1?'':'s' ?></p>
<?php foreach($rows as $row): ?><article class="event-admin-row"><div><?php if($row['poster_filename']): ?><img class="poster-preview" src="event-poster.php?id=<?= (int)$row['event_id'] ?>" alt="Poster for <?= escapeHtml($row['title']) ?>"><?php else: ?><span class="poster-placeholder">No poster</span><?php endif; ?></div><div class="event-admin-details"><h4><?= escapeHtml($row['title']) ?></h4><p><?= escapeHtml($row['description']??'') ?></p><p><?= escapeHtml($row['event_date']) ?> · <?= escapeHtml($row['venue']) ?></p><p><?= escapeHtml(EVENT_CATEGORIES[$row['category']]) ?> · <?= escapeHtml(ucfirst($row['status'])) ?></p><a href="events-manage.php?edit=<?= (int)$row['event_id'] ?>">Edit</a> · <a href="events-manage.php?delete=<?= (int)$row['event_id'] ?>">Delete</a></div></article><?php endforeach; ?>
<?php if(!$rows): ?><p>No events found.</p><?php endif; ?></section>
<section class="card p-3"><h3>Recent event changes</h3><div class="table-responsive"><table class="table"><thead><tr><th>Event</th><th>Action</th><th>Time</th></tr></thead><tbody><?php foreach($audit as $row): ?><tr><td><?= escapeHtml($row['event_title']) ?></td><td><?= escapeHtml($row['action']) ?></td><td><?= escapeHtml($row['created_at']) ?></td></tr><?php endforeach; ?><?php if(!$audit): ?><tr><td colspan="3">No changes recorded yet.</td></tr><?php endif; ?></tbody></table></div></section>
</main></body></html>
