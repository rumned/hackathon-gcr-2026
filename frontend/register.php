
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CRMPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page-auth">

<div class="auth-container">

    <div class="brand-panel">
        <div class="brand-logo">C.</div>
        <h2>Join Our Community</h2>
        <p>
            Create your account and enjoy
            a simple, seamless experience.
        </p>
        <div class="gold-line"></div>
        <span class="brand-caption">YOUR JOURNEY STARTS HERE.</span>
    </div>

    <div class="form-panel register-panel">
        <div class="form-header">
            <span class="eyebrow">GET STARTED</span>
            <h1>Create an account</h1>
            <p>Fill in your details to get started.</p>
        </div>

        <form>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    autocomplete="name"
                    required
                >
            </div>

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

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm-password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <label class="remember terms">
                <input type="checkbox" name="terms" required>
                <span>I agree to the <a href="#" class="text-link">Terms &amp; Conditions</a></span>
            </label>

            <button type="button" class="btn-primary">
                Create Account <span>&rarr;</span>
            </button>
        </form>

        <p class="switch-text register-switch">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

        <p class="footer-text">
            &copy; 2026 CRMPro. All rights reserved.
        </p>
    </div>

</div>

</body>
</html>
