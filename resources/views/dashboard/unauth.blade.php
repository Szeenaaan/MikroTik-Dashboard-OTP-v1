<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized</title>
    <link rel="stylesheet" href="{{ asset('css/unauth.css') }}">
</head>

<body>
    <div class="unauthorized-container">
        <div class="unauthorized-card">
            <div class="unauthorized-code">401</div>

            <h1>Authentication Required</h1>

            <p class="unauthorized-description">
                You need to log in to access the dashboard.
            </p>

            <a href="{{ route('login') }}" class="unauthorized-button">
                Go to Login
            </a>
        </div>
    </div>
</body>

</html>