<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-header">
                <h1 id="form-title">Login</h1>
                <p id="form-subtitle">Sign in to continue</p>
            </div>

            <div id="response-message" class="response-message"></div>

            <form id="login-form">
                @csrf

                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input
                        type="text"
                        id="login-username"
                        name="username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input
                        type="password"
                        id="login-password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" id="login-button">Login</button>
            </form>

            <form id="signup-form" hidden>
                @csrf

                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input
                        type="text"
                        id="signup-username"
                        name="username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input
                        type="password"
                        id="signup-password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" id="signup-button">Sign Up</button>
            </form>

            <div class="form-switch">
                <span id="switch-label">Don't have an account?</span>
                <button type="button" id="switch-button">Sign Up</button>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>