<?php
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../app-utils/mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_debug';

    if ($action === 'save_theme') {
        if (!validate_csrf($_POST['csrf_token'] ?? null, 'settings_theme')) {
            header('Location: ' . app_url('?page=settings&theme=csrf'));
            exit();
        }

        $selectedTheme = trim($_POST['theme'] ?? 'normal');
        set_theme($selectedTheme);
        header('Location: ' . app_url('?page=settings&theme=saved'));
        exit();
    }

    if ($action === 'save_debug') {
        if (!validate_csrf($_POST['csrf_token'] ?? null, 'settings_debug')) {
            header('Location: ' . app_url('?page=settings&saved=0&csrf=invalid'));
            exit();
        }

        $showDebug = isset($_POST['show_debug_panels']) && $_POST['show_debug_panels'] === '1';
        set_debug_enabled($showDebug);
        header('Location: ' . app_url('?page=settings&saved=1'));
        exit();
    }

    if ($action === 'send_test_mail') {
        if (!validate_csrf($_POST['csrf_token'] ?? null, 'settings_mail')) {
            header('Location: ' . app_url('?page=settings&mailtest=csrf'));
            exit();
        }

        $target = trim($_POST['test_email'] ?? '');
        if ($target !== '' && filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $ok = app_send_mail(
                $target,
                'Mediasystem SMTP test',
                'This is a test email from Mediasystem settings.'
            );
            header('Location: ' . app_url('?page=settings&mailtest=' . ($ok ? 'ok' : 'fail')));
            exit();
        }

        header('Location: ' . app_url('?page=settings&mailtest=invalid'));
        exit();
    }
}

$debugEnabled = is_debug_enabled();
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$runDbTest = isset($_GET['testdb']) && $_GET['testdb'] === '1';
$dbStatus = null;
$mailTest = $_GET['mailtest'] ?? '';
$csrfError = isset($_GET['csrf']) && $_GET['csrf'] === 'invalid';
$themeStatus = $_GET['theme'] ?? '';
$activeTheme = function_exists('current_theme') ? current_theme() : 'normal';

$smtpConfigured = (
    (getenv('APP_SMTP_HOST') ?: '') !== '' &&
    (getenv('APP_SMTP_USER') ?: '') !== '' &&
    (getenv('APP_SMTP_PASS') ?: '') !== ''
);

if ($debugEnabled && $runDbTest) {
    require_once __DIR__ . '/../app-database-configuration/db_conn.php';
    $dbStatus = getDbConnectionStatus();
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard" class="settings-layout">
    <section class="dash-section settings-section">
        <h2>Settings</h2>

        <?php if ($saved): ?>
            <p class="status-ok">Settings saved.</p>
        <?php endif; ?>

        <?php if ($csrfError): ?>
            <p class="status-error">Security validation failed. Please try again.</p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=settings')) ?>" class="settings-form">
            <input type="hidden" name="action" value="save_debug">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('settings_debug')) ?>">
            <label class="settings-toggle">
                <input type="checkbox" name="show_debug_panels" value="1" <?= $debugEnabled ? 'checked' : '' ?>>
                Enable debug panels (header info + DB diagnostics)
            </label>
            <button type="submit" class="btn-upload">Save Settings</button>
        </form>

        <p class="settings-note">
            Debug panels are for local troubleshooting only. Keep this disabled in normal usage.
        </p>
    </section>

    <section class="dash-section settings-section">
        <h2>Theme</h2>

        <?php if ($themeStatus === 'saved'): ?>
            <p class="status-ok">Theme updated.</p>
        <?php elseif ($themeStatus === 'csrf'): ?>
            <p class="status-error">Security validation failed. Please try again.</p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=settings')) ?>" class="settings-form">
            <input type="hidden" name="action" value="save_theme">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('settings_theme')) ?>">

            <label class="field-label" for="theme">Choose theme</label>
            <select class="field-input" name="theme" id="theme" required>
                <option value="normal" <?= $activeTheme === 'normal' ? 'selected' : '' ?>>Normal</option>
                <option value="dark" <?= $activeTheme === 'dark' ? 'selected' : '' ?>>Dark</option>
                <option value="grey" <?= $activeTheme === 'grey' ? 'selected' : '' ?>>Grey</option>
                <option value="extreme-contrast" <?= $activeTheme === 'extreme-contrast' ? 'selected' : '' ?>>Extreme Contrast</option>
            </select>

            <button type="submit" class="btn-upload">Save Theme</button>
        </form>
    </section>

    <?php if ($debugEnabled): ?>
        <section class="dash-section settings-section">
            <h2>Database Connection Diagnostics</h2>
            <p>
                <a class="btn-link" href="<?= htmlspecialchars(app_url('?page=settings&testdb=1')) ?>">Run Connection Test</a>
            </p>

            <?php if (is_array($dbStatus)): ?>
                <div class="db-panel <?= $dbStatus['ok'] ? 'db-ok' : 'db-fail' ?>">
                    <p><strong>Status:</strong> <?= htmlspecialchars($dbStatus['message']) ?></p>
                    <p><strong>Host:</strong> <?= htmlspecialchars($dbStatus['host']) ?></p>
                    <p><strong>Database:</strong> <?= htmlspecialchars($dbStatus['database']) ?></p>
                    <?php if (!$dbStatus['ok']): ?>
                        <p><strong>Error Code:</strong> <?= htmlspecialchars((string)$dbStatus['code']) ?></p>
                    <?php else: ?>
                        <p><strong>Server Version:</strong> <?= htmlspecialchars($dbStatus['server_version']) ?></p>
                        <p><strong>Thread ID:</strong> <?= htmlspecialchars((string)$dbStatus['thread_id']) ?></p>
                        <p><strong>Protocol:</strong> <?= htmlspecialchars((string)$dbStatus['protocol']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="dash-section settings-section">
        <h2>Mail Settings / Diagnostics</h2>
        <p class="settings-note">
            SMTP configured: <strong><?= $smtpConfigured ? 'Yes' : 'No' ?></strong><br>
            Host: <strong><?= htmlspecialchars((string)(getenv('APP_SMTP_HOST') ?: '-')) ?></strong><br>
            From: <strong><?= htmlspecialchars((string)(getenv('APP_MAIL_FROM') ?: 'no-reply@localhost')) ?></strong>
        </p>

        <?php if ($mailTest === 'ok'): ?>
            <p class="status-ok">Test email sent successfully.</p>
        <?php elseif ($mailTest === 'fail'): ?>
            <p class="status-error">Could not send test email. Check SMTP credentials and server settings.</p>
        <?php elseif ($mailTest === 'invalid'): ?>
            <p class="status-error">Please enter a valid email address for test mail.</p>
        <?php elseif ($mailTest === 'csrf'): ?>
            <p class="status-error">Security validation failed. Please try again.</p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=settings')) ?>" class="settings-form">
            <input type="hidden" name="action" value="send_test_mail">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('settings_mail')) ?>">
            <label class="field-label" for="test_email">Send test email to</label>
            <input class="field-input" type="email" name="test_email" id="test_email" required>
            <button type="submit" class="btn-upload">Send Test Email</button>
        </form>

        <p class="settings-note">
            Recommended: install PHPMailer with <code>composer require phpmailer/phpmailer</code>
            and set env vars: APP_SMTP_HOST, APP_SMTP_PORT, APP_SMTP_USER, APP_SMTP_PASS, APP_MAIL_FROM.
        </p>
    </section>
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
