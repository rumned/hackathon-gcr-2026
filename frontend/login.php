<?php
require __DIR__ . '/includes/api.php';

$error = '';
$userName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['user_name'] ?? '');
    $response = api_request('POST', '/api/auth/login', [
        'user_name' => $userName,
        'password' => (string) ($_POST['password'] ?? ''),
    ]);
    if ($response['status'] === 200) {
        $_SESSION['user'] = $response['data'];
        session_regenerate_id(true);
        header('Location: homepage.php');
        exit;
    }
    api_forget_login();
    $error = $response['status'] === 0
        ? "Can't reach the CRM backend at " . API_URL . '. Check that it is running.'
        : (string) ($response['data']['error'] ?? 'Login failed.');
} elseif (api_logged_in()) {
    header('Location: homepage.php');
    exit;
} elseif (isset($_GET['expired'])) {
    $error = 'Your session has ended. Log in again.';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CRMPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page-auth">

<div class="auth-container">

    <div class="brand-panel">
        <div class="brand-logo">C.</div>
        <h2>Welcome Back</h2>
        <p>
            Sign in to access your account
            and continue where you left off.
        </p>
        <div class="gold-line"></div>
    </div>

    <div class="form-panel">
        <div class="form-header">
            <h1>Login to your account</h1>
            <p>Enter your details below to continue.</p>
        </div>

        <?php if ($error !== ''): ?>
            <p class="form-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="user_name">Username</label>
                <input
                    type="text"
                    id="user_name"
                    name="user_name"
                    placeholder="admin"
                    value="<?= e($userName) ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>

                <a href="forgot-password.php" class="text-link">
    Forgot password?
</a>
            </div>

            <button type="submit" class="btn-primary">
                Login <span>&rarr;</span>
            </button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <p class="switch-text">
            Don't have an account?
            <a href="register.php">Create an account</a>
        </p>

        <p class="footer-text">
            &copy; 2026 CRMPro. All rights reserved.
        </p>
    </div>

</div>

</body>
</html>
