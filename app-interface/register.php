<?php
require_once __DIR__ . '/../app-database-configuration/db_conn.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $conn = createDbConnection();

        $checkStmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $checkStmt->bind_param('ss', $username, $email);
        $checkStmt->execute();
        $exists = $checkStmt->get_result();

        if ($exists && $exists->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare('INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
            $insertStmt->bind_param('sss', $username, $hashedPassword, $email);

            if ($insertStmt->execute()) {
                log_in_user($username);
                header('Location: ' . app_url());
                exit();
            }

            $error = 'Could not create account. Please try again.';
            $insertStmt->close();
        }

        $checkStmt->close();
        $conn->close();
    }
}

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard" class="auth-layout">
    <section class="dash-section auth-section">
        <h2>Create Account</h2>

        <?php if ($error !== ''): ?>
            <p class="status-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(app_url('?page=register')) ?>" class="settings-form">
            <label class="field-label" for="username">Username</label>
            <input class="field-input" type="text" name="username" id="username" required>

            <label class="field-label" for="email">Email</label>
            <input class="field-input" type="email" name="email" id="email" required>

            <label class="field-label" for="password">Password</label>
            <input class="field-input" type="password" name="password" id="password" required>

            <label class="field-label" for="confirm_password">Confirm Password</label>
            <input class="field-input" type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit" class="btn-upload">Register</button>
        </form>

        <p class="auth-links">
            <a href="<?= htmlspecialchars(app_url('?page=login')) ?>">Already have an account? Login</a>
        </p>
    </section>
</div>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
