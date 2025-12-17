<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function current_user(): ?array {
    start_session();
    if (!isset($_SESSION['user_id'])) return null;

    return [
        'user_id'   => (int)$_SESSION['user_id'],
        'user_type' => $_SESSION['user_type'], 
        'role'      => $_SESSION['role'],     
        'name'      => $_SESSION['name'] ?? '',
    ];
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(array $roles): void {
    $u = current_user();
    if (!$u) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        echo "403 Forbidden: недостатньо прав";
        exit;
    }
}

function logout_user(): void {
    start_session();
    $_SESSION = [];
    session_destroy();
}

function can_edit_sales_app(int $author_id): bool {
    $u = current_user();
    if (!$u) return false;
    if ($u['role'] === 'admin' || $u['role'] === 'realtor') return true;
    if ($u['role'] === 'client' && $u['user_type'] === 'client' && $u['user_id'] === $author_id) return true;
    return false;
}
