<?php
/**
 * Front-controller / router
 * All requests enter here. Add new pages to $allowed and create
 * the matching file in app-interface/.
 */

$page    = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'upload'];

if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}

require __DIR__ . '/app-interface/' . $page . '.php';
