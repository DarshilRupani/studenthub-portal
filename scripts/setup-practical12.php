<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
$directory=dirname(__DIR__).'/php/storage/event-posters';
if (!is_dir($directory) && !mkdir($directory,0755,true)) exit("Could not create upload directory.\n");
file_put_contents($directory.'/.htaccess',"Require all denied\n");
file_put_contents($directory.'/index.html','');
echo "Protected event-poster directory ready.\n";
// Restore the included demo poster after importing an events-only dump.
if (in_array('--restore-sample',$argv,true)) {
    $db=require dirname(__DIR__).'/php/db-mysqli.php';
    $row=$db->query("SELECT poster_filename FROM events WHERE title='Web Development Workshop Demo' ORDER BY event_id DESC LIMIT 1")->fetch_assoc();
    $name=$row['poster_filename']??'';
    if (preg_match('/^[a-f0-9]{32}\.png$/D',$name) && !is_file($directory.'/'.$name)) {
        if (!copy(dirname(__DIR__).'/images/practical12/sample-poster.png',$directory.'/'.$name)) exit("Could not restore demo poster.\n");
        echo "Demo poster restored.\n";
    }
}
