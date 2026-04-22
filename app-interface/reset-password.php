<?php
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../app-database-configuration/db_conn.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

if ($token === '') {
    $error = 'Invalid or missing reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null, 'reset_password')) {
        $error = 'Security validation failed. Please refresh and try again.';
    }

    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($error === '' && $token === '') {
        $error = 'Invalid token.';
    } elseif ($error === '' && ($password === '' || $confirmPassword === '')) {
        $error = 'Please fill in both password fields.';
    } elseif ($error === '' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($error === '' && $password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($error === '') {
        $conn = createDbConnection();

        $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(token_hash),
            INDEX(expires_at)
        )");

        $tokenHash = hash('sha256', $token);
        $lookup = $conn->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
        $lookup->bind_param('s', $tokenHash);
        $lookup->execute();
        $result = $lookup->get_result();
        $resetRow = $result ? $result->fetch_assoc() : null;
        $lookup->close();

        if (!$resetRow) {
            $error = 'This reset link is invalid or expired.';
        } else {
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $updateUser = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateUser->bind_param('si', $newHash, $resetRow['user_id']);
            $updateUser->execute();
            $updateUser->close();

            $markUsed = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
            $markUsed->bind_param('i', $resetRow['id']);
            $markUsed->execute();
            $markUsed->close();

            $success = 'Password has been reset. You can now login.';
        }

        $conn->close();
    }
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard" class="auth-layout">
    <section class="dash-section auth-section">
        <h2>Reset Password</h2>

        <?php if ($success !== ''): ?>
            <p class="status-ok"><?= htmlspecialchars($success) ?></p>
            <p class="auth-links">
                <a href="<?= htmlspecialchars(app_url('?page=login')) ?>">Go to login</a>
            </p>
        <?php else: ?>
            <?php if ($error !== ''): ?>
                <p class="status-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($token !== ''): ?>
                <form method="post" action="<?= htmlspecialchars(app_url('?page=reset-password&token=' . urlencode($token))) ?>" class="settings-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('reset_password')) ?>">

                    <label class="field-label" for="password">New Password</label>
                    <input class="field-input" type="password" name="password" id="password" required>

                    <label class="field-label" for="confirm_password">Confirm New Password</label>
                    <input class="field-input" type="password" name="confirm_password" id="confirm_password" required>

                    <button type="submit" class="btn-upload">Reset Password</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
