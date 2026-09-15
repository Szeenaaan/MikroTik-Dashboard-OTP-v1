<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('hotspot.send-otp', function ($request) {
            $phone = (string) $request->input('phone');

            return Limit::perMinutes(3, 3)
                ->by('send-otp:' . $request->ip() . '|' . $phone);
        });

        RateLimiter::for('hotspot.verify-otp', function ($request) {
            $phone = (string) $request->session()->get('otp_phone');

            return Limit::perMinutes(3, 3)
                ->by('verify-otp:' . $request->ip() . '|' . $phone);
        });

        RateLimiter::for('hotspot.resend-otp', function ($request) {
            $phone = (string) $request->session()->get('otp_phone');

            return Limit::perMinutes(3, 3)
                ->by('resend-otp:' . $request->ip() . '|' . $phone);
        });
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(3, 3)->by(
                strtolower($request->input('username')) . '|' . $request->ip()
            );
        });

        RateLimiter::for('signup', function (Request $request) {
            return Limit::perMinutes(3, 3)->by(
                $request->ip()
            );
        });
    }
}