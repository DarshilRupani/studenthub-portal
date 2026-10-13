<?php
// Practical 10: shared session and authentication functions.
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https,
    'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Cache-Control: no-store');

function authDb() {
    static $db;
    if (!$db) $db = require __DIR__ . '/db-mysqli.php';
    return $db;
}
function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function authToken() {
    if (empty($_SESSION['auth_csrf'])) $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['auth_csrf'];
}
function validAuthToken() {
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && isset($_SESSION['auth_csrf']) && hash_equals($_SESSION['auth_csrf'], $_POST['csrf_token']);
}
function rememberCookie($value, $expires) {
    global $https;
    setcookie('studenthub_remember', $value, ['expires' => $expires, 'path' => '/',
        'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
}
function startLogin($user) {
    session_regenerate_id(true);
    $_SESSION = ['user_id' => (int) $user['user_id'], 'fullname' => $user['fullname'],
        'role' => $user['role'], 'last_activity' => time(), 'auth_csrf' => bin2hex(random_bytes(32))];
    $statement = authDb()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?');
    $statement->bind_param('i', $_SESSION['user_id']);
    $statement->execute();
}
function issueRemember($userId) {
    $selector = bin2hex(random_bytes(12));
    $secret = bin2hex(random_bytes(32));
    $hash = hash('sha256', $secret);
    $expires = date('Y-m-d H:i:s', time() + 30 * 86400);
    $statement = authDb()->prepare('INSERT INTO remember_tokens (selector,user_id,token_hash,expires_at) VALUES (?,?,?,?)');
    $statement->bind_param('siss', $selector, $userId, $hash, $expires);
    $statement->execute();
    rememberCookie($selector . ':' . $secret, time() + 30 * 86400);
}
function revokeRemember() {
    $cookie = $_COOKIE['studenthub_remember'] ?? '';
    if (is_string($cookie) && preg_match('/^([a-f0-9]{24}):[a-f0-9]{64}$/D', $cookie, $matches)) {
        try {
            $statement = authDb()->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $statement->bind_param('s', $matches[1]);
            $statement->execute();
        } finally {
            rememberCookie('', time() - 3600);
        }
    }
    rememberCookie('', time() - 3600);
}
function endLogin() {
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'],
        'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
}
function redirectDashboard() {
    $pages = ['student' => 'dashboard.php', 'admin' => 'admin-dashboard.php', 'faculty' => 'faculty-dashboard.php'];
    header('Location: ' . ($pages[$_SESSION['role']] ?? 'login.php'), true, 303);
    exit;
}
function currentUser() {
    // Local override is used only to demonstrate timeout in the lab.
    $timeout = 900;
    if (is_file(__DIR__ . '/auth-config.local.php')) {
        $config = require __DIR__ . '/auth-config.local.php';
        $timeout = max(1, (int) ($config['timeout'] ?? 900));
    }
    if (isset($_SESSION['user_id'])) {
        if (time() - ($_SESSION['last_activity'] ?? 0) >= $timeout) {
            revokeRemember();
            endLogin();
            header('Location: login.php?message=timeout', true, 303);
            exit;
        }
        // Read the current role from the database, not from client input.
        $statement = authDb()->prepare('SELECT user_id,fullname,role FROM users WHERE user_id = ?');
        $statement->bind_param('i', $_SESSION['user_id']);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        if ($user) {
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            return $user;
        }
        revokeRemember();
        endLogin();
        header('Location: login.php?message=unauthorized', true, 303);
        exit;
    }
    $cookie = $_COOKIE['studenthub_remember'] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/D', $cookie, $parts)) return null;
    $db = authDb();
    $db->begin_transaction();
    try {
        $statement = $db->prepare('SELECT t.token_hash,u.user_id,u.fullname,u.role FROM remember_tokens t JOIN users u ON u.user_id=t.user_id WHERE t.selector=? AND t.expires_at>NOW() FOR UPDATE');
        $statement->bind_param('s', $parts[1]);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        if (!$user || !hash_equals($user['token_hash'], hash('sha256', $parts[2]))) {
            $db->rollback();
            rememberCookie('', time() - 3600);
            return null;
        }
        $delete = $db->prepare('DELETE FROM remember_tokens WHERE selector=?');
        $delete->bind_param('s', $parts[1]);
        $delete->execute();
        issueRemember((int) $user['user_id']);
        $db->commit();
        startLogin($user);
        return $user;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
function requireRole($role) {
    try { $user = currentUser(); }
    catch (Throwable $e) {
        error_log('StudentHub authentication: ' . $e->getMessage());
        http_response_code(503);
        exit('Sign-in is temporarily unavailable. Please try again later.');
    }
    if (!$user) {
        header('Location: login.php?message=unauthorized', true, 303);
        exit;
    }
    if ($user['role'] !== $role) {
        http_response_code(403);
        require __DIR__ . '/access-denied.php';
        exit;
    }
    return $user;
}
