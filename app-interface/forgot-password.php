<?php
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../app-database-configuration/db_conn.php';
require_once __DIR__ . '/../app-utils/mail.php';

$message = '';
$error = '';
$debugResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = createDbConnection();

        // Ensure reset table exists.
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

        $stmt = $conn->prepare('SELECT id, username, email FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $insert = $conn->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, used) VALUES (?, ?, ?, 0)');
            $insert->bind_param('iss', $user['id'], $tokenHash, $expiresAt);
            $insert->execute();
            $insert->close();

            $resetLink = app_url('?page=reset-password&token=' . urlencode($rawToken));
            $fullResetLink = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $resetLink;

            $subject = 'Reset your Mediasystem password';
            $body = 'Hi ' . htmlspecialchars($user['username']) . ',<br><br>' .
                'Click this link to reset your password:<br>' .
                '<a href="' . htmlspecialchars($fullResetLink) . '">' . htmlspecialchars($fullResetLink) . '</a><br><br>' .
                'This link expires in 1 hour.';

            $sent = app_send_mail($user['email'], $subject, $body);

            if (!$sent) {
                // Useful for local development when mail is not configured.
                $debugResetLink = $fullResetLink;
            }
        }

        $conn->close();
        $message = 'If your email exists in our system, a reset link has been sent.';
    }
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard" class="auth-layout">
    <section class="dash-section auth-section">
        <h2>Forgot Password</h2>

        <?php if ($message !== ''): ?>
            <p class="status-ok"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="status-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=forgot-password')) ?>" class="settings-form">
            <label class="field-label" for="email">Email</label>
            <input class="field-input" type="email" name="email" id="email" required>
            <button type="submit" class="btn-upload">Send Reset Link</button>
        </form>

        <?php if ($debugResetLink !== ''): ?>
            <p class="settings-note">
                Mail could not be sent from this environment. Local fallback reset link:<br>
                <a href="<?= htmlspecialchars($debugResetLink) ?>"><?= htmlspecialchars($debugResetLink) ?></a>
            </p>
        <?php endif; ?>

        <p class="auth-links">
            <a href="<?= htmlspecialchars(app_url('?page=login')) ?>">Back to login</a>
        </p>
    </section>
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
