<?php
/**
 * Front-controller / router
 * All requests enter here. Add new pages to $allowed and create
 * the matching file in app-interface/.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function is_debug_enabled(): bool {
    return !empty($_SESSION['show_debug_panels']);
}

function set_debug_enabled(bool $enabled): void {
    $_SESSION['show_debug_panels'] = $enabled;
}

$page    = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'upload', 'settings'];

if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}

require __DIR__ . '/app-interface/' . $page . '.php';
