<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify OTP</title>

    <link rel="stylesheet" href="{{ asset('css/otp.css') }}">
</head>

<body>

    <div class="card">

        <div class="logo">🔐</div>

        <h1>Enter Verification Code</h1>

        <p class="subtitle">
            We've sent a one-time password to<br>
            your mobile number
        </p>

        @if (session('rate_limit_error'))
            <div class="alert alert-error">
                {{ session('rate_limit_error') }}
            </div>
        @endif

        @php
            $otpLength = config('hotspot.otp_length', 6);
        @endphp

        <form
            method="POST"
            action="{{ route('hotspot.verify-otp') }}"
            id="verify-form"
        >
            @csrf

            <label>
                Enter {{ $otpLength }}-digit OTP
            </label>

            <div
                class="otp-inputs"
                id="otp-boxes"
            >
                @for ($i = 0; $i < $otpLength; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                        class="otp-box"
                        {{ $i === 0 ? 'autofocus' : '' }}
                    >
                @endfor
            </div>

            <input
                type="hidden"
                name="otp"
                id="otp-real"
            >

            <button
                type="submit"
                id="submit-btn"
                class="action-btn"
                disabled
            >
                Verify & Connect
            </button>

        </form>

        <div class="resend-row">

            <form
                method="POST"
                action="{{ route('hotspot.resend-otp') }}"
                id="resend-form"
            >
                @csrf

                <button
                    type="submit"
                    class="link-btn"
                    id="resend-btn"
                >
                    Resend OTP
                </button>

            </form>

        </div>

    </div>

    <script src="{{ asset('js/otp.js') }}?v={{ filemtime(public_path('js/otp.js')) }}"></script>

</body>

</html>