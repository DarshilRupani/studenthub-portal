<?php
// Practical 13: JSON API for event reads and admin CRUD.
require __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function apiReply($status, $body) {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function apiBody() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) apiReply(400, ['error'=>'Request body must be a valid JSON object.']);
    return $data;
}

function apiAdmin() {
    try { $user = currentUser(); }
    catch (Throwable $e) { error_log('StudentHub API auth: '.$e->getMessage()); apiReply(503, ['error'=>'Sign-in is temporarily unavailable.']); }
    if (!$user) apiReply(401, ['error'=>'Sign in as an administrator to make changes.']);
    if ($user['role'] !== 'admin') apiReply(403, ['error'=>'Administrator access is required.']);
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($_SESSION['auth_csrf']) || !hash_equals($_SESSION['auth_csrf'], $token)) apiReply(403, ['error'=>'The security token is invalid. Reload the page and try again.']);
    return $user;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
try {
    $db = authDb();
    require_once __DIR__ . '/event-functions.php';
    if ($method === 'GET') {
        if (isset($_GET['id']) && !$id) apiReply(400, ['error'=>'Event ID must be a positive integer.']);
        if ($id) {
            $stmt = eventQuery('SELECT event_id,title,description,event_date,venue,category,status FROM events WHERE event_id=?', [$id], 'i');
            $event = $stmt->get_result()->fetch_assoc();
            if (!$event) apiReply(404, ['error'=>'Event not found.']);
            $event['event_id'] = (int)$event['event_id'];
            apiReply(200, ['data'=>$event]);
        }
        $result = $db->query('SELECT event_id,title,description,event_date,venue,category,status FROM events ORDER BY event_date,event_id');
        $events=[];
        foreach ($result as $event) { $event['event_id']=(int)$event['event_id']; $events[]=$event; }
        apiReply(200, ['data'=>$events, 'count'=>count($events)]);
    }
    if (!in_array($method, ['POST','PUT','DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE');
        apiReply(405, ['error'=>'Method not allowed.']);
    }
    $actor = apiAdmin();
    if ($method === 'DELETE') {
        if (!$id) apiReply(400, ['error'=>'Provide a positive event ID.']);
        $db->begin_transaction();
        $stmt=eventQuery('SELECT event_id,title,poster_filename FROM events WHERE event_id=? FOR UPDATE',[$id],'i');
        $before=$stmt->get_result()->fetch_assoc();
        if (!$before) { $db->rollback(); apiReply(404,['error'=>'Event not found.']); }
        $stmt=eventQuery('SELECT COUNT(*) AS total FROM registrations WHERE event_id=?',[$id],'i');
        if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) { $db->rollback(); apiReply(409,['error'=>'This event has registrations and cannot be deleted. Close it instead.']); }
        eventAudit($id,$before['title'],'deleted',$before,null);
        eventQuery('DELETE FROM events WHERE event_id=?',[$id],'i');
        $db->commit();
        if ($before['poster_filename']) removePoster($before['poster_filename']);
        apiReply(200,['message'=>'Event deleted.','id'=>(int)$id]);
    }
    $data=apiBody();
    foreach (['title','description','event_date','venue','category','status'] as $field) {
        if (isset($data[$field]) && !is_string($data[$field])) apiReply(422,['error'=>'All event fields must be text values.']);
    }
    $input=[];
    foreach (['title','description','event_date','venue','category','status'] as $field) $input[$field]=(string)($data[$field]??'');
    [$values,$errors]=eventInput($input);
    if ($errors) apiReply(422,['error'=>'Check the event fields.','fields'=>$errors]);
    $values['event_date'] = DateTime::createFromFormat('!Y-m-d\\TH:i', $values['event_date'])->format('Y-m-d H:i:s');
    if ($method === 'POST') {
        $db->begin_transaction();
        eventQuery('INSERT INTO events (title,description,event_date,venue,category,status) VALUES (?,?,?,?,?,?)',[$values['title'],$values['description'],$values['event_date'],$values['venue'],$values['category'],$values['status']],'ssssss');
        $newId=(int)$db->insert_id;
        eventAudit($newId,$values['title'],'added',null,$values);
        $db->commit();
        apiReply(201,['message'=>'Event added.','id'=>$newId,'data'=>$values]);
    }
    if (!$id) apiReply(400,['error'=>'Provide a positive event ID.']);
    $db->begin_transaction();
    $stmt=eventQuery('SELECT event_id,title,description,event_date,venue,category,status,poster_filename FROM events WHERE event_id=? FOR UPDATE',[$id],'i');
    $before=$stmt->get_result()->fetch_assoc();
    if (!$before) { $db->rollback(); apiReply(404,['error'=>'Event not found.']); }
    eventQuery('UPDATE events SET title=?,description=?,event_date=?,venue=?,category=?,status=? WHERE event_id=?',[$values['title'],$values['description'],$values['event_date'],$values['venue'],$values['category'],$values['status'],$id],'ssssssi');
    eventAudit($id,$values['title'],'updated',$before,$values);
    $db->commit();
    apiReply(200,['message'=>'Event updated.','id'=>(int)$id,'data'=>$values]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) { try { $db->rollback(); } catch (Throwable $ignored) {} }
    error_log('StudentHub events API: '.$e->getMessage());
    apiReply(500,['error'=>'The event request could not be completed.']);
}
