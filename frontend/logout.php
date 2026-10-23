<?php
require __DIR__ . '/includes/api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (api_logged_in()) {
        api_request('POST', '/api/auth/logout', []);
    }
    api_forget_login();
    session_regenerate_id(true);
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | CRMPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page-auth">

<div class="logout-card">

    <div class="logout-icon">
        <span>&#10140;</span>
    </div>

    <span class="eyebrow">SESSION ENDED</span>

    <h1>You're Leaving?</h1>

    <p class="logout-description">
        Are you sure you want to log out?
        You can always sign in again whenever you're ready.
    </p>

    <form class="logout-actions" method="post">
        <button type="submit" class="btn-primary logout-button">
            Yes, Log Out <span>&rarr;</span>
        </button>

        <a href="homepage.php" class="btn-secondary">
            Cancel
        </a>
    </form>

    <div class="logout-divider"></div>

    <p class="footer-text">
        &copy; 2026 CRMPro. All rights reserved.
    </p>

</div>

</body>
</html>
