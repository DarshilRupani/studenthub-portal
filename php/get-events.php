<?php
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $db=require __DIR__.'/db-mysqli.php';
    $rows=$db->query('SELECT event_id,title,description,event_date,venue,category,status,poster_filename FROM events ORDER BY event_date,event_id');
    $events=[];
    foreach($rows as $row) $events[]=['id'=>(int)$row['event_id'],'title'=>$row['title'],'description'=>$row['description']??'',
        'date'=>substr($row['event_date'],0,10),'location'=>$row['venue'],'category'=>$row['category'],'status'=>$row['status'],
        'poster'=>$row['poster_filename'] ? 'event-poster.php?id='.$row['event_id'] : null];
    echo json_encode($events,JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {error_log('StudentHub events: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Could not load events.']);}
