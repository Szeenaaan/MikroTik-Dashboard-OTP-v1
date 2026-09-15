<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OtpSession;

class OtpSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $otpSessionId = session('otp_session_id');

        if (!$otpSessionId) {
            return redirect()->route('hotspot.index');
        }

        $otpSession = OtpSession::where('id', $otpSessionId)
            ->where('status', 'pending')
            ->first();

        if (!$otpSession || ($otpSession->expires_at && $otpSession->expires_at->isPast())) {
            session()->forget(['otp_phone', 'otp_session_id']);

            return redirect()->route('hotspot.index');
        }

        return $next($request);
    }
}