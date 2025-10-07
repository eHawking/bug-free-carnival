<?php
require_once __DIR__ . '/db.php';

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function check_csrf($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token ?? '');
}

function login($username, $password) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id'] = (int)$row['id'];
        $_SESSION['admin_name'] = $row['username'];
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function require_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_BASE . 'admin/login.php');
        exit;
    }
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
?>
