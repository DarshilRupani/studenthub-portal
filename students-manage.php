<?php
require __DIR__ . '/php/auth.php';
$user = requireRole('admin');
require __DIR__ . '/php/student-functions.php';
require_once __DIR__ . '/php/admin-audit.php';
$errors = []; $message = ''; $messageType = 'success';
$data = ['fullname'=>'', 'email'=>'', 'mobile'=>'', 'course'=>'', 'year_of_study'=>''];
$editId = null; $confirmation = null;
$messages = ['added'=>'Student added successfully.', 'updated'=>'Student updated successfully.',
    'deleted'=>'Student deleted successfully. The record can be restored.', 'restored'=>'Student restored successfully.'];
$message = $messages[studentText($_GET, 'message')] ?? '';
$filters = studentFilters($_GET);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validAuthToken()) {
            http_response_code(403); $message='Your form expired. Reload the page and try again.'; $messageType='error';
        } else {
            $action = studentText($_POST, 'action');
            $id = studentId($_POST['student_id'] ?? '');
            if (in_array($action, ['add','edit'], true)) {
                [$data, $errors] = studentInput($_POST);
                if ($action === 'edit') {
                    $editId = $id ?: null;
                    $existing = $id ? findStudent($id) : null;
                    if (!$existing || $existing['status'] !== 'active') $errors['record']='The active student record was not found.';
                }
                if ($errors) {
                    http_response_code(422); $message='Please correct the errors below.'; $messageType='error';
                } else {
                    $values = [$data['fullname'], $data['email'], $data['mobile'] ?: null, $data['course'], (int) $data['year_of_study']];
                    $db=authDb(); $db->begin_transaction();
                    try {
                        if ($action === 'add') {
                            studentQuery('INSERT INTO students (fullname,email,mobile,course,year_of_study) VALUES (?,?,?,?,?)', $values, 'ssssi');
                            adminAudit('student_added','student',$db->insert_id,'Added student: '.$data['fullname']);
                        } else {
                            $values[] = $id;
                            studentQuery("UPDATE students SET fullname=?,email=?,mobile=?,course=?,year_of_study=? WHERE student_id=? AND status='active'", $values, 'ssssii');
                            adminAudit('student_updated','student',$id,'Updated student: '.$data['fullname']);
                        }
                        $db->commit();
                    } catch (Throwable $e) { $db->rollback(); throw $e; }
                    header('Location: students-manage.php?message=' . ($action === 'add' ? 'added' : 'updated'), true, 303); exit;
                }
            } elseif (in_array($action, ['delete','restore'], true) && $id) {
                if ($action === 'delete' && studentText($_POST, 'confirm_delete') !== '1') {
                    http_response_code(422); $message='Please confirm before deleting the student.'; $messageType='error';
                } else {
                    $newStatus = $action === 'delete' ? 'deleted' : 'active';
                    $oldStatus = $action === 'delete' ? 'active' : 'deleted';
                    $db=authDb(); $db->begin_transaction();
                    try {
                    $statement = studentQuery('UPDATE students SET status=? WHERE student_id=? AND status=?', [$newStatus, $id, $oldStatus], 'sis');
                    if ($statement->affected_rows !== 1) {
                        $db->rollback();
                        http_response_code(404); $message='Student not found or already changed.'; $messageType='error';
                    } else {
                        adminAudit($action==='delete'?'student_deleted':'student_restored','student',$id,ucfirst($action).' student record #'.$id);
                        $db->commit();
                        header('Location: students-manage.php?message=' . ($action === 'delete' ? 'deleted' : 'restored'), true, 303); exit;
                    }
                    } catch (Throwable $e) { try {$db->rollback();} catch (Throwable $ignored) {} throw $e; }
                }
            } else {
                http_response_code(400); $message='Invalid student action.'; $messageType='error';
            }
        }
    } else {
        foreach (['edit','delete'] as $action) {
            if (!isset($_GET[$action])) continue;
            $id = studentId($_GET[$action]);
            $existing = $id ? findStudent($id) : null;
            if (!$existing || $existing['status'] !== 'active') {
                http_response_code(404); $message='The active student record was not found.'; $messageType='error';
            } elseif ($action === 'edit') { $editId=$id; $data=$existing; }
            else $confirmation=$existing;
        }
    }
    [$where, $params] = studentWhere($filters);
    if (studentText($_GET, 'export') === 'csv') {
        $rows = studentQuery('SELECT student_id,fullname,email,mobile,course,year_of_study,status FROM students' . $where . ' ORDER BY student_id', $params)->get_result();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student-list.csv"');
        $stream = fopen('php://output', 'w');
        fputcsv($stream, ['Student ID','Full Name','Email','Mobile','Course','Year','Status'], ',', '"', '');
        while ($row=$rows->fetch_assoc()) fputcsv($stream, array_map('csvStudentValue', array_values($row)), ',', '"', '');
        fclose($stream); exit;
    }
    $total = (int) studentQuery('SELECT COUNT(*) total FROM students' . $where, $params)->get_result()->fetch_assoc()['total'];
    $pages = max(1, (int) ceil($total / 10));
    $page = min($pages, max(1, (int) studentText($_GET, 'page')));
    $offset = ($page-1)*10;
    $rows = studentQuery('SELECT * FROM students' . $where . ' ORDER BY student_id LIMIT ? OFFSET ?', [...$params, 10, $offset], str_repeat('s', count($params)) . 'ii')->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
        http_response_code(409); $message='This student email already exists.'; $errors['email']=$message;
    } else {
        http_response_code(500); $message='Could not save or load students. Please try again later.';
        error_log('StudentHub students: ' . $e->getMessage());
    }
    $messageType='error'; $rows=[]; $total=0; $pages=1; $page=1;
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
        // Keep the existing list visible when an email is duplicated.
        [$where, $params] = studentWhere($filters);
        $total = (int) studentQuery('SELECT COUNT(*) total FROM students' . $where, $params)->get_result()->fetch_assoc()['total'];
        $pages = max(1, (int) ceil($total / 10));
        $rows = studentQuery('SELECT * FROM students' . $where . ' ORDER BY student_id LIMIT 10', $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
function studentLink($changes) {
    global $filters;
    return 'students-manage.php?' . http_build_query(array_merge($filters, $changes));
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudentHub | Student Management</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/student-management.css"></head>
<body>
<header><h1>StudentHub</h1><nav aria-label="Main navigation"><ul><li><a href="admin-dashboard.php">Admin Dashboard</a></li><li><a href="students-manage.php" class="active">Student Management</a></li><li><a href="home.html">Home</a></li></ul></nav></header>
<main class="management-main">
<h2>Student Management</h2><p>Add, edit and manage student records.</p>
<?php if ($message !== ''): ?><div class="form-status form-status-<?= escapeHtml($messageType) ?>" role="status"><?= escapeHtml($message) ?></div><?php endif; ?>
<?php if ($confirmation): ?>
<section class="management-card"><h3>Confirm deletion</h3><p>Delete <strong><?= escapeHtml($confirmation['fullname']) ?></strong> (<?= escapeHtml($confirmation['email']) ?>)?</p><p>The record will be hidden from the active list and can be restored.</p>
<form method="post" action="students-manage.php"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= (int) $confirmation['student_id'] ?>"><label><input type="checkbox" name="confirm_delete" value="1" required> I confirm this deletion</label><p><button type="submit" class="danger-button">Delete student</button> <a href="students-manage.php">Cancel</a></p></form></section>
<?php else: ?>
<section class="management-card"><h3><?= $editId ? 'Edit student' : 'Add student' ?></h3>
<form action="students-manage.php" method="post" class="student-form">
<input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><input type="hidden" name="action" value="<?= $editId ? 'edit' : 'add' ?>"><input type="hidden" name="student_id" value="<?= (int) $editId ?>">
<?php foreach (['fullname'=>'Full Name','email'=>'Email','mobile'=>'Mobile'] as $field=>$label): ?><div><label for="<?= $field ?>"><?= $label ?></label><input id="<?= $field ?>" name="<?= $field ?>" type="<?= $field==='email' ? 'email' : 'text' ?>" value="<?= escapeHtml($data[$field] ?? '') ?>" maxlength="<?= $field==='fullname' ? 100 : ($field==='email' ? 150 : 10) ?>" <?= $field==='mobile' ? '' : 'required' ?>><?php if(isset($errors[$field])): ?><p class="error-message"><?= escapeHtml($errors[$field]) ?></p><?php endif; ?></div><?php endforeach; ?>
<div><label for="student-course">Course</label><select id="student-course" name="course" required><option value="">Select course</option><?php foreach(STUDENT_COURSES as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['course']??'')===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><?php if(isset($errors['course'])): ?><p class="error-message"><?= escapeHtml($errors['course']) ?></p><?php endif; ?></div>
<div><label for="student-year">Year</label><select id="student-year" name="year_of_study" required><option value="">Select year</option><?php for($i=1;$i<=4;$i++): ?><option value="<?= $i ?>" <?= (string)($data['year_of_study']??'')===(string)$i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select><?php if(isset($errors['year_of_study'])): ?><p class="error-message"><?= escapeHtml($errors['year_of_study']) ?></p><?php endif; ?></div>
<div class="student-form-actions"><button type="submit"><?= $editId ? 'Save changes' : 'Add student' ?></button><?php if($editId): ?> <a href="students-manage.php">Cancel</a><?php endif; ?></div>
<?php if(isset($errors['record'])): ?><p class="error-message"><?= escapeHtml($errors['record']) ?></p><?php endif; ?>
</form></section>
<?php endif; ?>
<section class="management-card"><h3>Student list</h3>
<form action="students-manage.php" method="get" class="student-filters"><div><label for="search">Search name, email or course</label><input type="search" id="search" name="q" value="<?= escapeHtml($filters['q']) ?>" maxlength="150"></div><div><label for="filter-course">Course filter</label><select id="filter-course" name="course"><option value="">All courses</option><?php foreach(STUDENT_COURSES as $key=>$label): ?><option value="<?= $key ?>" <?= $filters['course']===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><div><label for="filter-year">Year filter</label><select id="filter-year" name="year"><option value="">All years</option><?php for($i=1;$i<=4;$i++): ?><option value="<?= $i ?>" <?= $filters['year']===(string)$i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div><div><label for="filter-status">Status filter</label><select id="filter-status" name="status"><?php foreach(['active'=>'Active','deleted'=>'Deleted','all'=>'All records'] as $key=>$label): ?><option value="<?= $key ?>" <?= $filters['status']===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><button type="submit">Search</button><a href="students-manage.php">Reset</a></form>
<div class="student-list-info"><p><?= $total ?> matching student<?= $total===1 ? '' : 's' ?> · Page <?= $page ?> of <?= $pages ?></p><a class="export-link" href="<?= escapeHtml(studentLink(['export'=>'csv'])) ?>">Export CSV</a></div>
<div class="table-scroll"><table class="student-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?= (int)$row['student_id'] ?></td><td><?= escapeHtml($row['fullname']) ?></td><td><?= escapeHtml($row['email']) ?></td><td><?= escapeHtml($row['mobile'] ?: '-') ?></td><td><?= escapeHtml(STUDENT_COURSES[$row['course']] ?? $row['course']) ?></td><td><?= (int)$row['year_of_study'] ?></td><td><?= escapeHtml($row['status']) ?></td><td><?php if($row['status']==='active'): ?><a href="students-manage.php?edit=<?= (int)$row['student_id'] ?>">Edit</a> <a href="students-manage.php?delete=<?= (int)$row['student_id'] ?>">Delete</a><?php else: ?><form method="post" action="students-manage.php"><input type="hidden" name="csrf_token" value="<?= escapeHtml(authToken()) ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>"><button type="submit">Restore</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8">No students found.</td></tr><?php endif; ?></tbody></table></div>
<nav class="pagination" aria-label="Student pages"><?php if($page>1): ?><a href="<?= escapeHtml(studentLink(['page'=>$page-1])) ?>">Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if($page<$pages): ?><a href="<?= escapeHtml(studentLink(['page'=>$page+1])) ?>">Next</a><?php endif; ?></nav>
</section></main>
</body></html>
