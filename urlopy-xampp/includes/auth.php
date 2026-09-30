<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

require_once __DIR__ . '/db.php';

function start_app_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function current_user() {
    start_app_session();
    if (empty($_SESSION['user_id'])) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $cached = $user ?: null;
    return $cached;
}

function require_auth() {
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin() {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        render_error('Brak uprawnien administratora.');
        exit;
    }
    return $user;
}

function render_error($message) {
    $currentUser = current_user();
    include __DIR__ . '/../error.php';
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
