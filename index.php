<?php
/**
 * Front-controller / router
 * All requests enter here. Add new pages to $allowed and create
 * the matching file in app-interface/.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_BOOTSTRAPPED', true);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}

define('APP_BASE_PATH', $basePath);

function app_url(string $path = ''): string {
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return APP_BASE_PATH === '' ? '/' : APP_BASE_PATH . '/';
    }

    return (APP_BASE_PATH === '' ? '' : APP_BASE_PATH) . '/' . $trimmedPath;
}

function auth_mode(): string {
    $mode = strtolower((string)(getenv('APP_AUTH_MODE') ?: 'local'));
    return in_array($mode, ['local', 'sso'], true) ? $mode : 'local';
}

function sso_enabled(): bool {
    return auth_mode() === 'sso';
}

function csrf_token(string $form): string {
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (empty($_SESSION['csrf_tokens'][$form])) {
        $_SESSION['csrf_tokens'][$form] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$form];
}

function validate_csrf(?string $token, string $form): bool {
    if (!isset($_SESSION['csrf_tokens'][$form]) || !is_string($_SESSION['csrf_tokens'][$form])) {
        return false;
    }

    return is_string($token) && hash_equals($_SESSION['csrf_tokens'][$form], $token);
}

function is_debug_enabled(): bool {
    return !empty($_SESSION['show_debug_panels']);
}

function set_debug_enabled(bool $enabled): void {
    $_SESSION['show_debug_panels'] = $enabled;
}

function is_logged_in(): bool {
    return !empty($_SESSION['auth_user']);
}

function current_user(): string {
    return $_SESSION['auth_user'] ?? '';
}

function log_in_user(string $username): void {
    $_SESSION['auth_user'] = $username;
}

function log_out_user(): void {
    unset($_SESSION['auth_user']);
    unset($_SESSION['show_debug_panels']);
}

$page    = $_GET['page'] ?? (is_logged_in() ? 'dashboard' : 'login');
$allowed = ['dashboard', 'upload', 'settings', 'login', 'logout', 'register', 'forgot-password', 'reset-password'];

if (!in_array($page, $allowed, true)) {
    $page = is_logged_in() ? 'dashboard' : 'login';
}

if ($page === 'logout') {
    log_out_user();
    header('Location: ' . app_url('?page=login&loggedout=1'));
    exit();
}

$protectedPages = ['dashboard', 'upload', 'settings'];
if (in_array($page, $protectedPages, true) && !is_logged_in()) {
    header('Location: ' . app_url('?page=login&next=' . urlencode($page)));
    exit();
}

if (in_array($page, ['login', 'register', 'forgot-password', 'reset-password'], true) && is_logged_in()) {
    header('Location: ' . app_url());
    exit();
}

require __DIR__ . '/app-interface/' . $page . '.php';
