<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'otp.session' => \App\Http\Middleware\OtpSessionMiddleware::class,
            'application.activated' => \App\Http\Middleware\ApplicationActivationMiddleware::class,
            'application.activation.response' => \App\Http\Middleware\ApplicationActivationResponseMiddleware::class,
            'application.activation.page' => \App\Http\Middleware\ActivationPageMiddleware::class,
        ]);
        $middleware->priority([
            \App\Http\Middleware\ApplicationActivationMiddleware::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->routeIs('hotspot.verify-otp')) {
                return response()->json(
                    [
                        'success' => false,
                        'statuscode' => 429,
                        'message' => 'Too many verification attempts. Please try again later.',
                    ],
                    429
                );
            }

            if ($request->routeIs('hotspot.resend-otp')) {
                return response()->json(
                    [
                        'success' => false,
                        'statuscode' => 429,
                        'message' => 'Too many resend attempts. Please try again later.',
                    ],
                    429
                );
            }

            if ($request->routeIs('hotspot.send-otp')) {
                return response()->json(
                    [
                        'success' => false,
                        'statuscode' => 429,
                        'message' => 'Too many OTP requests. Please try again later.',
                    ],
                    429
                );
            }

            if ($request->routeIs('login.submit')) {
                return response()->json([
                    'success' => false,
                    'statuscode' => 429,
                    'message' => 'Too many login attempts. Please try again later.',
                ], 429);
            }

            if ($request->routeIs('signup')) {
                return response()->json([
                    'success' => false,
                    'statuscode' => 429,
                    'message' => 'Too many signup attempts. Please try again later.',
                ], 429);
            }

            return null;
        });
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->view(
                'dashboard.unauth',
                [],
                401
            );
        });


        $exceptions->render(function (Throwable $e, $request) {

            return response()->view(
                'hotspot.server-error',
                [
                    'response' => [
                        'success' => false,
                        'statuscode' => 500,
                        'message' => $e->getMessage(),
                    ],
                ],
                500
            );
        });


    })
    ->create();