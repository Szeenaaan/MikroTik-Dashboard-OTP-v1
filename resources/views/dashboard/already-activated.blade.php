<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Already Activated</title>
 <link rel="stylesheet" href="{{ asset('css/already-activated.css') }}">
</head>

<body>
    <div class="activation-container">
        <div class="activation-card">
            <h1>Dashboard Already Activated</h1>

            <p class="activation-description">
                You have already activated this dashboard on this device.
            </p>

            <a href="{{ route('login') }}" class="activation-button">
                Go to Login
            </a>
        </div>
    </div>
</body>

</html>