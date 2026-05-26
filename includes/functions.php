<?php
function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function h(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

function get(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $default);
}

function calcGrade(float $total): string {
    if ($total >= 75) return 'A';
    if ($total >= 65) return 'B';
    if ($total >= 55) return 'C';
    if ($total >= 45) return 'D';
    return 'F';
}

function paginate(int $total, int $perPage, int $current): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $current = max(1, min($current, $pages));
    return [
        'total'   => $total,
        'pages'   => $pages,
        'current' => $current,
        'offset'  => ($current - 1) * $perPage,
        'limit'   => $perPage,
    ];
}

function activeLink(string $file): string {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $file ? 'active' : '';
}

function activeModule(string $module): string {
    $path = $_SERVER['REQUEST_URI'];
    return strpos($path, '/modules/' . $module . '/') !== false ? 'active' : '';
}
