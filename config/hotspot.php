<?php

return [
    'session_hours' => (int) env('HOTSPOT_SESSION_HOURS', 1),
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'otp_resend_cooldown' => (int) env('OTP_RESEND_COOLDOWN', 60),
    'otp_max_resend' => (int) env('OTP_MAX_RESEND', 3),
    'otp_expiry' => (int) env('OTP_EXPIRY', 10),
    'otp_length' => (int) env('OTP_LENGTH', 6),
    'data_limit' => 0,
];
