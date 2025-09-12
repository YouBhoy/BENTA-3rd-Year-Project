<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/utils.php';

function current_user_id(): ?int {
    start_app_session();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function ensure_authenticated(): void {
    if (!current_user_id()) {
        redirect('login.php');
    }
}

function signup_user(string $email, string $password, string $businessName): bool {
    $pdo = get_pdo();
    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (strlen($password) < 8) {
        return false;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, business_name) VALUES (?, ?, ?)');
        return $stmt->execute([$email, $hash, $businessName]);
    } catch (PDOException $e) {
        return false;
    }
}

function login_user(string $email, string $password): bool {
    $pdo = get_pdo();
    $email = trim(strtolower($email));
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    start_app_session();
    $_SESSION['user_id'] = (int)$user['id'];
    return true;
}

function logout_user(): void {
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}


