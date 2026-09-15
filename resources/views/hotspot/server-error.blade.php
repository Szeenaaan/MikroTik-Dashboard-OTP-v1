<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Server Error</title>

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

        .error-card {
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

        .error-code {
            margin-bottom: 12px;

            font-size: 48px;
            line-height: 1;

            font-weight: 600;

            color: #555;
        }

        h1 {
            margin-bottom: 10px;

            font-size: 20px;
            font-weight: 600;

            color: #444;
        }

        .message {
            font-size: 13px;
            line-height: 1.5;

            color: #777;
        }

        .retry-message {
            margin-top: 8px;
        }

        @media (max-width: 400px) {
            body {
                padding: 16px;
            }

            .error-card {
                padding: 30px 20px;
            }

            .error-code {
                font-size: 42px;
            }
        }
    </style>
</head>

<body>

    <div class="error-card">

        <div class="error-code">
            500
        </div>

        <h1 class="message">
            {{ $error ?? 'Something went wrong.' }}
        </h1>

        <p class="message retry-message">
            Please try again later.
        </p>

    </div>

</body>

</html>