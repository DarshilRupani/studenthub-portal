<?php
// Practical 11: validation and prepared student queries.
const STUDENT_COURSES = ['btech-ce' => 'B.Tech CE', 'btech-it' => 'B.Tech IT', 'bca' => 'BCA', 'mca' => 'MCA', 'other' => 'Other'];
function studentText($values, $key) {
    return isset($values[$key]) && is_string($values[$key]) ? trim($values[$key]) : '';
}
function studentId($value) {
    return is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
}
function studentInput($values) {
    $data = [];
    foreach (['fullname','email','mobile','course','year_of_study'] as $field) $data[$field] = studentText($values, $field);
    $data['email'] = strtolower($data['email']);
    $errors = [];
    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/D", $data['fullname'])) $errors['fullname'] = 'Enter a name of 2-100 letters.';
    if (strlen($data['email']) > 150 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($data['mobile'] !== '' && !preg_match('/^[6-9][0-9]{9}$/D', $data['mobile'])) $errors['mobile'] = 'Enter 10 digits starting with 6, 7, 8 or 9.';
    if (!isset(STUDENT_COURSES[$data['course']])) $errors['course'] = 'Select a valid course.';
    if (!in_array($data['year_of_study'], ['1','2','3','4'], true)) $errors['year_of_study'] = 'Select a year from 1 to 4.';
    return [$data, $errors];
}
function studentFilters($values) {
    $q = substr(studentText($values, 'q'), 0, 150);
    $course = studentText($values, 'course');
    $year = studentText($values, 'year');
    $status = studentText($values, 'status');
    return ['q' => $q, 'course' => isset(STUDENT_COURSES[$course]) ? $course : '',
        'year' => in_array($year, ['1','2','3','4'], true) ? $year : '',
        'status' => in_array($status, ['active','deleted','all'], true) ? $status : 'active'];
}
function studentWhere($filters) {
    $clauses = []; $params = [];
    if ($filters['status'] !== 'all') { $clauses[] = 'status=?'; $params[] = $filters['status']; }
    if ($filters['q'] !== '') {
        $term = '%' . str_replace(['!','%','_'], ['!!','!%','!_'], $filters['q']) . '%';
        $clauses[] = "(fullname LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!' OR course LIKE ? ESCAPE '!' OR CASE course WHEN 'btech-ce' THEN 'B.Tech CE' WHEN 'btech-it' THEN 'B.Tech IT' ELSE course END LIKE ? ESCAPE '!')";
        array_push($params, $term, $term, $term, $term);
    }
    if ($filters['course'] !== '') { $clauses[] = 'course=?'; $params[] = $filters['course']; }
    if ($filters['year'] !== '') { $clauses[] = 'year_of_study=?'; $params[] = $filters['year']; }
    return [$clauses ? ' WHERE ' . implode(' AND ', $clauses) : '', $params];
}
function studentQuery($sql, $params = [], $types = '') {
    $statement = authDb()->prepare($sql);
    if ($params) $statement->bind_param($types ?: str_repeat('s', count($params)), ...$params);
    $statement->execute();
    return $statement;
}
function findStudent($id) {
    return studentQuery('SELECT * FROM students WHERE student_id=?', [$id], 'i')->get_result()->fetch_assoc();
}
function csvStudentValue($value) {
    $value = (string) $value;
    return preg_match('/^\s*[=+\-@]/', $value) ? "'" . $value : $value;
}
