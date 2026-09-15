<?php

namespace App\Http\Middleware;

use App\Models\ApplicationActivation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApplicationActivationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('application_activation');

        if (!$token) {
            return redirect()->route('application.activation');
        }

        $activations = ApplicationActivation::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->get();

        foreach ($activations as $activation) {
            if (Hash::check($token, $activation->token)) {
                $activation->update([
                    'last_seen_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
                ]);

                return $next($request);
            }
        }

        return redirect()->route('application.activation');
    }
}