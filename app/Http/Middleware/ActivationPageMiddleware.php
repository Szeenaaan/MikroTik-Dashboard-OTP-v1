<?php

namespace App\Http\Middleware;

use App\Models\ApplicationActivation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ActivationPageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('application_activation');

        if (!$token) {
            return $next($request);
        }

        $activations = ApplicationActivation::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->get();

        foreach ($activations as $activation) {
            if (Hash::check($token, $activation->token)) {
                return redirect()->route('application.activation.status');
            }
        }

        return $next($request);
    }
}