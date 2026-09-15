<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationActivationResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse && $response->isSuccessful()) {
            $data = $response->getData(true);

            if (
                ($data['success'] ?? false) === true &&
                isset($data['token'])
            ) {
                $response = $response->withCookie(
                    cookie(
                        'application_activation',
                        $data['token'],
                        60 * 24 * 365 * 5,
                        '/',
                        null,
                        request()->isSecure(),
                        true,
                        false,
                        'lax'
                    )
                );
            }
        }

        return $response;
    }
}