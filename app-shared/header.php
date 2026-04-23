<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('app-styles/app-style.css')) ?>">
        
    <title>Mediasystem-v1</title>

</head>
<body data-theme="<?= htmlspecialchars(function_exists('current_theme') ? current_theme() : 'normal') ?>">

<nav class="app-nav">
    <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
        <a href="<?= htmlspecialchars(app_url()) ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(app_url('?page=settings')) ?>">Settings</a>
        <span class="nav-user">Signed in as <?= htmlspecialchars(current_user()) ?></span>
        <a href="<?= htmlspecialchars(app_url('?page=logout')) ?>">Logout</a>
    <?php endif; ?>
</nav>

<?php if (function_exists('is_debug_enabled') && is_debug_enabled()): ?>
<div id="header-info-panel">
    <ul>
        <li><b>Root Path:</b> <?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '') ?></li>
        <li><b>Current Script:</b> <?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '') ?></li>
        <li><b>Router Page:</b> <?= htmlspecialchars($_GET['page'] ?? 'dashboard') ?></li>
    </ul>
</div>
<?php endif; ?>
