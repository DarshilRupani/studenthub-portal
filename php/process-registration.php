<?php
// Practical 7: PHP validation and CSV/JSON storage.
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$STORAGE_DIR = __DIR__ . '/storage';
$JSON_FILE = $STORAGE_DIR . '/registrations.json';
$CSV_FILE = $STORAGE_DIR . '/registrations.csv';

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

function closeLockedFile($handle) {
    flock($handle, LOCK_UN);
    fclose($handle);
}

// fwrite can write fewer bytes than requested.
function writeAll($handle, $content) {
    $offset = 0;
    $length = strlen($content);
    while ($offset < $length) {
        $written = @fwrite($handle, substr($content, $offset));
        if ($written === false || $written === 0) return false;
        $offset += $written;
    }
    return true;
}

function replaceContents($handle, $content) {
    return rewind($handle)
        && writeAll($handle, $content)
        && @ftruncate($handle, strlen($content))
        && @fflush($handle);
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

$fullname = trim(strip_tags(postText('fullname')));
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

$nameLength = function_exists('mb_strlen')
    ? mb_strlen($fullname, 'UTF-8') : strlen($fullname);
if ($fullname === '' || $nameLength < 2 || $nameLength > 50) {
    $errors['fullname'] = 'Enter a name between 2 and 50 characters.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    $errors['mobile'] = 'Mobile number must be 10 digits, starting with 6, 7, 8 or 9.';
}
if (strlen($password) < 8
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^A-Za-z0-9\s]/', $password)) {
    $errors['password'] = 'Password needs 8+ characters, an uppercase letter, a lowercase letter, a number and a symbol.';
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
if (!in_array($role, $allowedRoles, true)) $role = 'student';
if (!$terms) {
    $errors['terms'] = 'You must accept the terms and conditions.';
}
if (!empty($errors)) {
    respond(false, 'Please fix the errors below and try again.', $errors);
}

if (!is_dir($STORAGE_DIR)
    && !@mkdir($STORAGE_DIR, 0755, true)
    && !is_dir($STORAGE_DIR)) {
    storageError('Could not create the storage folder.');
}

$record = [
    'id' => uniqid('reg_', true),
    'fullname' => htmlspecialchars($fullname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    'email' => $email,
    'mobile' => $mobile,
    'course' => $course,
    'year' => $year,
    'gender' => $gender,
    'role' => $role,
    // This practical collects requests; it does not save login passwords.
    'password_set' => true,
    'registered' => date('c')
];

$jsonHandle = @fopen($JSON_FILE, 'c+b');
if ($jsonHandle === false) storageError('Could not open the JSON storage file.');
if (!flock($jsonHandle, LOCK_EX)) {
    fclose($jsonHandle);
    storageError('Could not lock the JSON storage file.');
}

$existingContent = stream_get_contents($jsonHandle);
if ($existingContent === false) {
    closeLockedFile($jsonHandle);
    storageError('Could not read the JSON storage file.');
}

// A damaged file must not be replaced with an empty list.
$records = [];
if (trim($existingContent) !== '') {
    $records = json_decode($existingContent, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($records)
        || substr(ltrim($existingContent), 0, 1) !== '[') {
        closeLockedFile($jsonHandle);
        storageError('The JSON storage file is invalid. Existing data was not overwritten.');
    }
}

foreach ($records as $existing) {
    if (!is_array($existing) || !isset($existing['email'])
        || !is_string($existing['email'])) {
        closeLockedFile($jsonHandle);
        storageError('The JSON storage file contains an invalid record. Existing data was not overwritten.');
    }
    if (strcasecmp($existing['email'], $email) === 0) {
        closeLockedFile($jsonHandle);
        respond(false, 'This email is already registered.', [
            'email' => 'This email is already registered.'
        ]);
    }
}

$records[] = $record;
$json = json_encode($records, JSON_PRETTY_PRINT);
if ($json === false) {
    closeLockedFile($jsonHandle);
    storageError('Could not encode the registration data.');
}

// Open and lock both files before changing either one.
$csvHandle = @fopen($CSV_FILE, 'c+b');
if ($csvHandle === false) {
    closeLockedFile($jsonHandle);
    storageError('Could not open the CSV storage file. No registration was added.');
}
if (!flock($csvHandle, LOCK_EX)) {
    fclose($csvHandle);
    closeLockedFile($jsonHandle);
    storageError('Could not lock the CSV storage file. No registration was added.');
}

$csvInfo = fstat($csvHandle);
if ($csvInfo === false || fseek($csvHandle, 0, SEEK_END) !== 0) {
    closeLockedFile($csvHandle);
    closeLockedFile($jsonHandle);
    storageError('Could not inspect the CSV storage file. No registration was added.');
}

// Write CSV first; truncate the new rows if JSON saving fails.
$csvOk = true;
if ($csvInfo['size'] === 0) {
    $csvOk = @fputcsv($csvHandle, [
        'id', 'fullname', 'email', 'mobile', 'course',
        'year', 'gender', 'role', 'registered'
    ], ',', '"', '') !== false;
}
if ($csvOk) {
    $csvOk = @fputcsv($csvHandle, [
        $record['id'], $record['fullname'], $record['email'],
        $record['mobile'], $record['course'], $record['year'],
        $record['gender'], $record['role'], $record['registered']
    ], ',', '"', '') !== false;
}
$csvOk = @fflush($csvHandle) && $csvOk;

if (!$csvOk) {
    $restored = @ftruncate($csvHandle, $csvInfo['size']) && @fflush($csvHandle);
    closeLockedFile($csvHandle);
    closeLockedFile($jsonHandle);
    storageError($restored
        ? 'CSV saving failed. No registration was added.'
        : 'CSV saving failed and cleanup failed. Check storage before retrying.');
}

if (!replaceContents($jsonHandle, $json)) {
    $jsonRestored = replaceContents($jsonHandle, $existingContent);
    $csvRestored = @ftruncate($csvHandle, $csvInfo['size']) && @fflush($csvHandle);
    closeLockedFile($csvHandle);
    closeLockedFile($jsonHandle);
    storageError($jsonRestored && $csvRestored
        ? 'JSON saving failed. The previous contents were restored.'
        : 'Saving failed and restoration was incomplete. Check both storage files before retrying.');
}

closeLockedFile($csvHandle);
closeLockedFile($jsonHandle);
unset($_SESSION['csrf_token']);
respond(true, "Welcome, {$fullname}! Your StudentHub account request has been received.");
