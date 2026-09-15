<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>WiFi Login</title>

    <link rel="stylesheet" href="{{ asset('css/hotspot.css') }}">
</head>

<body>

    <div class="card">

        <div class="logo">📶</div>

        <h1>Connect to WiFi</h1>

        <p class="subtitle">
            Verify your mobile number to get internet access
        </p>

        @if (session('router_name'))
            <div class="router-wrap">
                <span class="router-name">
                    {{ session('router_name') }}
                </span>
            </div>
        @endif

        @if (session('rate_limit_error'))
            <div class="alert alert-error">
                {{ session('rate_limit_error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('hotspot.send-otp') }}" id="otp-form">
            @csrf

            <label for="phone">
                Mobile Number
            </label>

            <div class="phone-input-group">

                <span class="country-code">
                    +91
                </span>

                <input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                    placeholder="10-digit mobile number" autofocus required>

            </div>

            <p class="hint">
                We'll send a one-time password to verify this number
            </p>

            <button type="submit" id="submit-btn">
                Send OTP
            </button>

        </form>

        <p class="footer-note">
            By continuing, you agree to the network's terms of use
        </p>

    </div>

    <script src="{{ asset('js/hotspot.js') }}"></script>

</body>

</html>