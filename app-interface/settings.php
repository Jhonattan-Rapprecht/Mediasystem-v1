<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showDebug = isset($_POST['show_debug_panels']) && $_POST['show_debug_panels'] === '1';
    set_debug_enabled($showDebug);
    header('Location: ' . app_url('?page=settings&saved=1'));
    exit();
}

$debugEnabled = is_debug_enabled();
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$runDbTest = isset($_GET['testdb']) && $_GET['testdb'] === '1';
$dbStatus = null;

if ($debugEnabled && $runDbTest) {
    require_once __DIR__ . '/../app-database-configuration/db_conn.php';
    $dbStatus = getDbConnectionStatus();
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard">
    <section class="dash-section settings-section">
        <h2>Settings</h2>

        <?php if ($saved): ?>
            <p class="status-ok">Settings saved.</p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=settings')) ?>" class="settings-form">
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
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
