
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | YourBrand</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">

    <div class="brand-panel">
        <div class="brand-logo">Y.</div>
        <h2>Welcome Back</h2>
        <p>
            Sign in to access your account
            and continue where you left off.
        </p>
        <div class="gold-line"></div>
        <span class="brand-caption">SIMPLE. MODERN. PROFESSIONAL.</span>
    </div>

    <div class="form-panel">
        <div class="form-header">
            <span class="eyebrow">WELCOME BACK</span>
            <h1>Login to your account</h1>
            <p>Enter your details below to continue.</p>
        </div>

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

                <a href="#" class="text-link">Forgot password?</a>
            </div>

            <button type="button" class="btn-primary">
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
            &copy; 2026 YourBrand. All rights reserved.
        </p>
    </div>

</div>

</body>
</html>
