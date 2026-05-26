<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/vtasis/');
}

require_once __DIR__ . '/../includes/functions.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'modules/auth/login.php');
    }
}

function hasRole(string $role): bool {
    return ($_SESSION['role'] ?? '') === $role;
}

function requireRole($roles): void {
    requireLogin();
    $roles = (array)$roles;
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        redirect(BASE_URL . 'modules/dashboard/index.php');
    }
}
