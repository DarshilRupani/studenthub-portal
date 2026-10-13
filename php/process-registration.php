<?php
// Practical 9: MySQLi registration with password hashing and audit logging.
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($success, $message, $errors = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'errors' => $errors
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function storageError($message) {
    http_response_code(500);
    respond(false, $message);
}

// Reject array inputs instead of passing them to string functions.
function postText($key, $default = '') {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? $_POST[$key] : $default;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    respond(false, 'This endpoint only accepts POST requests.');
}

$submittedToken = postText('csrf_token');
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($sessionToken) || $sessionToken === ''
    || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(403);
    respond(false, 'Your session expired. Please reload the page and try again.');
}

$fullname = trim(postText('fullname'));
$email = trim(postText('email'));
$mobile = trim(postText('mobile'));
$password = postText('password');
$confirm = postText('confirm_password');
$course = trim(postText('course'));
$year = trim(postText('year'));
$gender = trim(postText('gender'));
$role = trim(postText('role', 'student'));
$terms = in_array(postText('terms'), ['on', '1', 'true'], true);

$allowedCourses = ['btech-ce', 'btech-it', 'bca', 'mca', 'other'];
$allowedYears = ['1', '2', '3', '4'];
$allowedGenders = ['male', 'female', 'other'];
$allowedRoles = ['student', 'faculty'];
$errors = [];

if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/D", $fullname)) {
    $errors['fullname'] = 'Enter a name between 2 and 50 characters.';
}
if ($email === '' || strlen($email) > 150 || !preg_match('/^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/D', $email)) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    $errors['mobile'] = 'Mobile number must be 10 digits, starting with 6, 7, 8 or 9.';
}
if (strlen($password) < 8 || strlen($password) > 72 || preg_match('/[^\x20-\x7E]/', $password)
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^A-Za-z0-9\s]/', $password)) {
    $errors['password'] = 'Password needs 8-72 printable ASCII characters, an uppercase letter, a lowercase letter, a number and a symbol.';
}
if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
if (!in_array($course, $allowedCourses, true)) {
    $errors['course'] = 'Select a valid course.';
}
if (!in_array($year, $allowedYears, true)) {
    $errors['year'] = 'Select a valid year.';
}
if (!in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Select a gender.';
}
if (!in_array($role, $allowedRoles, true)) $errors['role'] = 'Select a valid role.';
if (strtoupper(trim(postText('captcha'))) !== ($_SESSION['captcha'] ?? '') || empty($_SESSION['captcha'])) {
    $errors['captcha'] = 'The security code does not match. Reload the code and try again.';
}
if (!$terms) {
    $errors['terms'] = 'You must accept the terms and conditions.';
}
if (!empty($errors)) {
    http_response_code(422);
    respond(false, 'Please fix the errors below and try again.', $errors);
}

try {
    $db = require __DIR__ . '/db-mysqli.php';
    $email = strtolower($email);
    $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        http_response_code(409);
        respond(false, 'This email is already registered.', ['email' => 'This email is already registered.']);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->begin_transaction();
    $insert = $db->prepare('INSERT INTO users (fullname,email,mobile,password_hash,course,year_of_study,gender,role) VALUES (?,?,?,?,?,?,?,?)');
    $yearNumber = (int) $year;
    $insert->bind_param('sssssiss', $fullname, $email, $mobile, $hash, $course, $yearNumber, $gender, $role);
    $insert->execute();
    $userId = $db->insert_id;
    $audit = $db->prepare("INSERT INTO registration_audit (user_id,action) VALUES (?, 'account_created')");
    $audit->bind_param('i', $userId);
    $audit->execute();
    $db->commit();
    unset($_SESSION['csrf_token'], $_SESSION['captcha']);
    http_response_code(201);
    respond(true, "Welcome, {$fullname}! Your StudentHub account has been created.");
} catch (Throwable $e) {
    if (isset($db)) { try { $db->rollback(); } catch (Throwable $ignored) {} }
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
        http_response_code(409);
        respond(false, 'This email is already registered.', ['email' => 'This email is already registered.']);
    }
    error_log('StudentHub registration: ' . $e->getMessage());
    storageError('Registration could not be saved. Please try again later.');
}
