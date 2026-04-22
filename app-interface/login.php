<?php
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: ../index.php?page=login');
    exit();
}

$next = $_GET['next'] ?? 'dashboard';
$allowedNext = ['dashboard', 'settings'];
if (!in_array($next, $allowedNext, true)) {
    $next = 'dashboard';
}

$error = '';
$loggedOut = isset($_GET['loggedout']) && $_GET['loggedout'] === '1';
$loginRequired = isset($_GET['next']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null, 'login')) {
        $error = 'Security validation failed. Please refresh and try again.';
    }

    $nextFromPost = $_POST['next'] ?? 'dashboard';
    if (in_array($nextFromPost, $allowedNext, true)) {
        $next = $nextFromPost;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($error === '' && ($username === '' || $password === '')) {
        $error = 'Please enter both username and password.';
    } elseif ($error === '') {
        require_once __DIR__ . '/../app-database-configuration/db_conn.php';
        $conn = createDbConnection();

        $stmt = $conn->prepare('SELECT username, password FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        $isValid = false;
        if (is_array($row)) {
            $storedPassword = (string)($row['password'] ?? '');
            $isValid = hash_equals($storedPassword, $password) || password_verify($password, $storedPassword);
        }

        $stmt->close();
        $conn->close();

        if ($isValid) {
            log_in_user($row['username']);
            header('Location: ' . app_url('?page=' . $next));
            exit();
        }

        $error = 'Invalid credentials.';
    }
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard" class="auth-layout">
    <section class="dash-section auth-section">
        <div class="auth-switch" role="tablist" aria-label="Authentication pages">
            <a class="auth-switch-item active" href="<?= htmlspecialchars(app_url('?page=login')) ?>">Login</a>
            <a class="auth-switch-item" href="<?= htmlspecialchars(app_url('?page=register')) ?>">Register</a>
        </div>

        <h2>Login</h2>

        <?php if ($loggedOut): ?>
            <p class="status-ok">You have been logged out.</p>
        <?php endif; ?>

        <?php if ($loginRequired): ?>
            <p class="settings-note">Please login to access the dashboard.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="status-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (function_exists('sso_enabled') && sso_enabled()): ?>
            <p class="settings-note">Auth mode is set to SSO. Keep this local form as emergency fallback during rollout.</p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=login&next=' . urlencode($next))) ?>" class="settings-form">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('login')) ?>">

            <label class="field-label" for="username">Username</label>
            <input class="field-input" type="text" name="username" id="username" autocomplete="username" required>

            <label class="field-label" for="password">Password</label>
            <input class="field-input" type="password" name="password" id="password" autocomplete="current-password" required>

            <button type="submit" class="btn-upload">Login</button>
        </form>

        <p class="auth-links">
            <a href="<?= htmlspecialchars(app_url('?page=forgot-password')) ?>">Forgot password?</a>
            <a href="<?= htmlspecialchars(app_url('?page=register')) ?>">Create account</a>
        </p>
    </section>
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
