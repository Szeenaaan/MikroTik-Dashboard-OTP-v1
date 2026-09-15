<?php

return [
    'host' => env('MIKROTIK_HOST'),
    'port' => (int) env('MIKROTIK_PORT', 8728),
    'username' => env('MIKROTIK_USERNAME'),
    'password' => env('MIKROTIK_PASSWORD'),
    'ssl' => filter_var(
        env('MIKROTIK_SSL', false),
        FILTER_VALIDATE_BOOLEAN
    ),
];