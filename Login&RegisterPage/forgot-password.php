
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | YourBrand</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="forgot-container">

    <!-- Left Brand Panel -->
    <div class="forgot-brand">
        <div class="brand-logo">Y.</div>

        <h2>Don't Worry.</h2>

        <p>
            Forgetting a password happens.
            Let's help you get back into
            your account.
        </p>

        <div class="gold-line"></div>

        <span class="brand-caption">
            YOUR ACCOUNT. YOUR CONTROL.
        </span>
    </div>

    <!-- Right Form Panel -->
    <div class="forgot-form">

        <a href="login.php" class="back-link">
            &larr; Back to Sign In
        </a>

        <div class="forgot-icon">
            &#9993;
        </div>

        <span class="eyebrow">PASSWORD RECOVERY</span>

        <h1>Forgot Password?</h1>

        <p class="description">
            No worries! Enter the email address
            associated with your account and
            we'll help you reset your password.
        </p>

        <form>
            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="name@example.com"
                    autocomplete="email"
                    required
                >
            </div>

            <button type="button" class="btn-primary">
                Send Reset Link <span>&rarr;</span>
            </button>
        </form>

        <div class="forgot-note">
            <span class="note-icon">&#9432;</span>
            <p>
                For your security, never share your
                password or verification code with anyone.
            </p>
        </div>

        <p class="switch-text">
            Remember your password?
            <a href="login.php">Sign in</a>
        </p>

        <p class="footer-text">
            &copy; 2026 YourBrand. All rights reserved.
        </p>

    </div>

</div>

</body>
</html>
