<?php
// Export event records only, without login accounts or authentication tokens.
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
try {
    $db=require dirname(__DIR__).'/php/db-mysqli.php';
    $create=$db->query('SHOW CREATE TABLE events')->fetch_row()[1];
    $sql="-- Practical 12 events table dump\n-- Import into an empty database for inspection.\nSET NAMES utf8mb4;\n\n".$create.";\n\n";
    $rows=$db->query('SELECT * FROM events ORDER BY event_id');$count=0;
    while($row=$rows->fetch_assoc()) {
        $columns='`'.implode('`,`',array_keys($row)).'`';
        $values=array_map(fn($v)=>$v===null?'NULL':"'".$db->real_escape_string($v)."'",array_values($row));
        $sql.='INSERT INTO events ('.$columns.') VALUES ('.implode(',',$values).");\n";$count++;
    }
    file_put_contents(dirname(__DIR__).'/sql/practical12-events-dump.sql',$sql);
    $audit=$db->query('SELECT action,COUNT(*) total FROM event_audit GROUP BY action')->fetch_all(MYSQLI_ASSOC);
    $posters=$db->query('SELECT COUNT(*) total FROM events WHERE poster_filename IS NOT NULL')->fetch_assoc()['total'];
    file_put_contents(dirname(__DIR__).'/docs/Practical12-database-results.json',json_encode(['event_records'=>$count,'events_with_posters'=>(int)$posters,'audit_actions'=>$audit],JSON_PRETTY_PRINT));
    echo "Exported {$count} events; {$posters} event posters.\n";
} catch(Throwable $e) {fwrite(STDERR,$e->getMessage()."\n");exit(1);}
