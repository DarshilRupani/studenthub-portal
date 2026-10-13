<?php
// Posters are served as images; direct access to the storage folder is denied.
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
$id=isset($_GET['id']) && is_string($_GET['id']) ? filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) : false;
if (!$id) {http_response_code(404);exit;}
try {
    $db=require __DIR__.'/php/db-mysqli.php';
    $statement=$db->prepare('SELECT poster_filename FROM events WHERE event_id=?');
    $statement->bind_param('i',$id);$statement->execute();
    $name=$statement->get_result()->fetch_assoc()['poster_filename']??'';
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$name,$matches)) {http_response_code(404);exit;}
    $path=__DIR__.'/php/storage/event-posters/'.$name;
    if (!is_file($path)) {http_response_code(404);exit;}
    $types=['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    header('Content-Type: '.$types[$matches[1]]);
    header('Content-Disposition: inline');
    header('Content-Length: '.filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
} catch (Throwable $e) {error_log('StudentHub poster: '.$e->getMessage());http_response_code(500);}
