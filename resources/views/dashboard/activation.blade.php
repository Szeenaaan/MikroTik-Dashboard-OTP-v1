<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Activation</title>
    <link rel="stylesheet" href="{{ asset('css/activate.css') }}">
</head>

<body>
    <form id="activation-form">
        @csrf

        <div class="form-group">
            <label for="application-key">Application Key</label>
            <input type="password" id="application-key" name="application_key" required>
        </div>

        <button type="submit" class="activation-button">
            Activate
        </button>

        <div id="activation-message" class="activation-message"></div>
    </form>
    </div>
    </div>
    <script src="{{ asset('js/activate.js') }}"></script>

</body>

</html>