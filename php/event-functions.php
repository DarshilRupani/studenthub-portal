<?php
// Practical 12: event validation, queries and poster uploads.
const EVENT_CATEGORIES = ['technical'=>'Technical', 'academic'=>'Academic', 'cultural'=>'Cultural', 'sports'=>'Sports', 'other'=>'Other'];
const POSTER_LIMIT = 2097152;
function eventText($values, $key) {
    return isset($values[$key]) && is_string($values[$key]) ? trim($values[$key]) : '';
}
function eventId($value) {
    return is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : false;
}
function eventQuery($sql, $values=[], $types='') {
    $statement=authDb()->prepare($sql);
    if ($values) $statement->bind_param($types ?: str_repeat('s',count($values)), ...$values);
    $statement->execute();
    return $statement;
}
function eventInput($values) {
    $data=[]; $errors=[];
    foreach(['title','description','event_date','venue','category','status'] as $field) $data[$field]=eventText($values,$field);
    foreach(['title'=>150,'venue'=>150,'description'=>2000] as $field=>$limit) {
        $length=function_exists('mb_strlen') ? mb_strlen($data[$field],'UTF-8') : strlen($data[$field]);
        if ($length>$limit || ($field!=='description' && $length<2)) $errors[$field]='Enter ' . ($field==='description' ? 'up to ' : '2-') . $limit . ' characters.';
    }
    $date=DateTime::createFromFormat('!Y-m-d\TH:i',$data['event_date']);
    if (!$date || $date->format('Y-m-d\TH:i')!==$data['event_date']) $errors['event_date']='Select a valid event date and time.';
    if (!isset(EVENT_CATEGORIES[$data['category']])) $errors['category']='Select a valid category.';
    if (!in_array($data['status'],['open','closed'],true)) $errors['status']='Select open or closed.';
    return [$data,$errors];
}
function validatePoster($file) {
    if (!$file || ($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return [null,''];
    if (!is_array($file) || !is_int($file['error']??null) || !is_string($file['tmp_name']??null) || !is_string($file['name']??null)) return [null,'Invalid poster upload.'];
    if (in_array($file['error'],[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true)) return [null,'Poster must be 2 MB or smaller.'];
    if ($file['error']!==UPLOAD_ERR_OK) return [null,'Poster upload failed. Please try again.'];
    if (!is_uploaded_file($file['tmp_name'])) return [null,'Invalid poster upload.'];
    if (filesize($file['tmp_name'])>POSTER_LIMIT) return [null,'Poster must be 2 MB or smaller.'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $extension=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if (!isset($types[$mime]) || !in_array($extension,['jpg','jpeg','png','webp'],true)
        || ($mime==='image/jpeg' ? !in_array($extension,['jpg','jpeg'],true) : $extension!==$types[$mime])) return [null,'Upload a JPEG, PNG or WebP image. The file content and extension must match.'];
    $size=@getimagesize($file['tmp_name']);
    if (!$size || ($size['mime']??'')!==$mime || $size[0]>4096 || $size[1]>4096) return [null,'Upload a valid image no larger than 4096 by 4096 pixels.'];
    return [['temporary'=>$file['tmp_name'],'extension'=>$types[$mime]],''];
}
function posterDirectory() { return __DIR__ . '/storage/event-posters'; }
function storePoster($poster) {
    $directory=posterDirectory();
    if (!is_dir($directory) || !is_file($directory.'/.htaccess')) throw new RuntimeException('Protected upload directory is missing.');
    $name=bin2hex(random_bytes(16)).'.'.$poster['extension'];
    if (!move_uploaded_file($poster['temporary'],$directory.'/'.$name)) throw new RuntimeException('Could not save poster.');
    return $name;
}
function removePoster($name) {
    if (is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$name)) {
        $path=posterDirectory().'/'.$name;
        if (is_file($path) && !@unlink($path)) error_log('StudentHub: could not remove replaced event poster.');
    }
}
function eventAudit($eventId,$title,$action,$before,$after) {
    $actor=(int)$_SESSION['user_id'];
    $old=$before ? json_encode($before,JSON_UNESCAPED_UNICODE) : null;
    $new=$after ? json_encode($after,JSON_UNESCAPED_UNICODE) : null;
    eventQuery('INSERT INTO event_audit (event_id,event_title,actor_user_id,action,before_data,after_data) VALUES (?,?,?,?,?,?)',[$eventId,$title,$actor,$action,$old,$new],'isisss');
    require_once __DIR__ . '/admin-audit.php';
    adminAudit('event_'.$action,'event',$eventId,ucfirst($action).' event: '.$title);
}
function eventFilters($values) {
    $category=eventText($values,'category');$status=eventText($values,'status');
    return ['q'=>substr(eventText($values,'q'),0,150),'category'=>isset(EVENT_CATEGORIES[$category])?$category:'','status'=>in_array($status,['open','closed'],true)?$status:''];
}
function eventWhere($filters) {
    $clauses=[];$values=[];
    if ($filters['q']!=='') {$term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$filters['q']).'%';$clauses[]="(title LIKE ? ESCAPE '!' OR venue LIKE ? ESCAPE '!')";array_push($values,$term,$term);}
    foreach(['category','status'] as $field) if ($filters[$field]!=='') {$clauses[]=$field.'=?';$values[]=$filters[$field];}
    return [$clauses?' WHERE '.implode(' AND ',$clauses):'',$values];
}
