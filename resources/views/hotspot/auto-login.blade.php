<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Connecting...</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: #e9e8e3;

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            color: #444;
        }

        .connecting-card {
            width: 100%;
            max-width: 380px;

            padding: 36px 28px;

            background: #faf9f6;

            border: 1px solid #deddd8;
            border-radius: 8px;

            text-align: center;

            box-shadow:
                0 4px 16px rgba(0, 0, 0, 0.06);
        }

        .wifi-icon {
            width: 54px;
            height: 54px;

            margin: 0 auto 18px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #e4e3de;

            border: 1px solid #d6d5d0;
            border-radius: 50%;

            font-size: 24px;
        }

        h1 {
            margin-bottom: 8px;

            font-size: 20px;
            font-weight: 600;

            color: #444;
        }

        .message {
            font-size: 13px;
            line-height: 1.5;

            color: #777;
        }

        .loader {
            width: 24px;
            height: 24px;

            margin: 22px auto 0;

            border: 3px solid #deddd8;
            border-top-color: #666;

            border-radius: 50%;

            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 400px) {
            body {
                padding: 16px;
            }

            .connecting-card {
                padding: 30px 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .loader {
                animation: none;
            }
        }
    </style>
</head>

<body>

    <div class="connecting-card">

        <div class="wifi-icon">
            📶
        </div>

        <h1>Connecting...</h1>

        <p class="message">
            Please wait while we connect you to the Wi-Fi.
        </p>

        <div class="loader"></div>

        <form
            id="mikrotik-login-form"
            method="POST"
            action="{{ $linkLogin }}"
        >
            <input
                type="hidden"
                name="username"
                value="{{ $username }}"
            >

            <input
                type="hidden"
                name="password"
                value="{{ $password }}"
            >

            <input
                type="hidden"
                name="dst"
                value="{{ $linkOrig }}"
            >

            <input
                type="hidden"
                name="popup"
                value="false"
            >
        </form>

    </div>

    <script>
        document
            .getElementById('mikrotik-login-form')
            .submit();
    </script>

</body>

</html>