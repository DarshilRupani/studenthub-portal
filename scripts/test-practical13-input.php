<?php
// Pure input validation checks; these run without Apache or a database.
require __DIR__ . '/../php/event-functions.php';
$checks=[];
function recordCheck(&$checks,$name,$passed) {
    $checks[]=['case'=>$name,'result'=>$passed?'PASS':'FAIL'];
    if (!$passed) throw new RuntimeException('Failed: '.$name);
}
$valid=['title'=>'API Validation Demo','description'=>'Test data','event_date'=>'2027-03-18T11:30','venue'=>'Computer Lab','category'=>'academic','status'=>'open'];
[$data,$errors]=eventInput($valid);
recordCheck($checks,'Valid event fields accepted',!$errors && $data['title']===$valid['title']);
[$data,$errors]=eventInput(array_merge($valid,['event_date'=>'2027-02-30T11:30']));
recordCheck($checks,'Invalid calendar date rejected',isset($errors['event_date']));
[$data,$errors]=eventInput(array_merge($valid,['category'=>'unknown','status'=>'pending']));
recordCheck($checks,'Invalid category and status rejected',isset($errors['category']) && isset($errors['status']));
[$data,$errors]=eventInput(array_merge($valid,['title'=>str_repeat('x',151),'venue'=>'x']));
recordCheck($checks,'Overlong title and short venue rejected',isset($errors['title']) && isset($errors['venue']));
$resultFile=__DIR__ . '/../docs/Practical13-test-results.json';
$existing=is_file($resultFile)?json_decode(file_get_contents($resultFile),true):[];
if (!is_array($existing)) $existing=[];
$runtime=array_values(array_filter($existing,fn($row)=>($row['case']??'')==='Runtime JSON API integration checks'));
file_put_contents($resultFile,json_encode(array_merge($checks,$runtime),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo count($checks).' local event validation checks passed. '.(count($runtime)?'Runtime API status retained.':'Runtime API checks not run.').PHP_EOL;
