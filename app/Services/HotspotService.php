<?php

namespace App\Services;

use App\Models\HotspotSession;
use App\Models\OtpSession;
use App\Models\WifiUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HotspotService
{
    public function __construct(
        protected OtpService $otpService,
        protected RadiusService $radiusService
    ) {
    }

    public function initializeSession(
        string $mac,
        string $ip,
        string $linkLogin,
        string $linkOrig,
        string $routerName
    ): array {
        try {
            $mac = preg_match(
                '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
                $mac
            )
                ? strtoupper($mac)
                : null;

            $ip = filter_var(
                $ip,
                FILTER_VALIDATE_IP
            )
                ? $ip
                : null;

            $linkLogin = filter_var(
                $linkLogin,
                FILTER_VALIDATE_URL
            )
                ? $linkLogin
                : null;

            $linkOrig = filter_var(
                $linkOrig,
                FILTER_VALIDATE_URL
            )
                ? $linkOrig
                : null;

            $routerName = mb_substr(
                trim($routerName),
                0,
                100
            );

            $routerHost = $linkLogin
                ? parse_url($linkLogin, PHP_URL_HOST)
                : null;

            $routerIp = filter_var(
                $routerHost,
                FILTER_VALIDATE_IP
            )
                ? $routerHost
                : null;

            $routerHost = filter_var(
                $routerHost,
                FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME
            )
                ? strtolower($routerHost)
                : null;

            session([
                'mac' => $mac,
                'ip' => $ip,
                'link_login' => $linkLogin,
                'link_orig' => $linkOrig,
                'router_ip' => $routerIp,
                'router_host' => $routerHost,
                'router_name' => $routerName !== ''
                    ? $routerName
                    : null,
            ]);

            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'Hotspot session initialized successfully.',
            ];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to initialize hotspot session.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    public function sendOtp(Request $request): array
    {
        try {
            if (
                !session('mac') ||
                !session('ip') ||
                !session('link_login')
            ) {
                return [
                    'success' => false,
                    'statuscode' => 400,
                    'message' => 'Your hotspot session has expired. Please reconnect to WiFi and try again.',
                ];
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'phone' => [
                        'required',
                        'digits:10',
                    ],
                ]
            );

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'statuscode' => 422,
                    'message' => $validator->errors()->first('phone'),
                ];
            }

            $phone = $request->phone;

            $wifiUser = WifiUser::where(
                'mac_address',
                session('mac')
            )->first();

            if ($wifiUser && !$wifiUser->is_active) {
                return [
                    'success' => false,
                    'statuscode' => 403,
                    'message' => 'You have been blocked. Please contact support.',
                ];
            }

            $lastOtp = OtpSession::where(
                'phone',
                $phone
            )->latest()->first();

            if (
                $lastOtp &&
                $lastOtp->created_at->diffInSeconds(now())
                < config('hotspot.otp_resend_cooldown')
            ) {
                $remaining = ceil(
                    config('hotspot.otp_resend_cooldown')
                    - $lastOtp->created_at->diffInSeconds(now())
                );

                return [
                    'success' => false,
                    'statuscode' => 429,
                    'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                ];
            }

            OtpSession::where('phone', $phone)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                ]);

            $otp = $this->otpService->generate();

            $otpSession = OtpSession::create([
                'phone' => $phone,
                'otp' => $otp,
                'mac' => session('mac'),
                'ip' => session('ip'),
                'router_name' => session('router_name'),
                'router_ip' => session('router_ip'),
                'router_host' => session('router_host'),
                'link_login' => session('link_login'),
                'link_orig' => session('link_orig'),
                'status' => 'pending',
                'attempts' => 0,
                'resend_count' => 0,
                'last_resend_at' => null,
                'expires_at' => now()->addMinutes(
                    config('hotspot.otp_expiry')
                ),
                'user_agent' => mb_substr(
                    $request->userAgent() ?? '',
                    0,
                    500
                ),
            ]);

            $sendResult = $this->otpService->send(
                $phone,
                $otp
            );

            if (!$sendResult['success']) {
                $otpSession->update([
                    'status' => 'failed',
                ]);

                return [
                    'success' => false,
                    'statuscode' => $sendResult['statuscode'],
                    'message' => $sendResult['message'],
                ];
            }

            session([
                'otp_session_id' => $otpSession->id,
            ]);

            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'OTP sent successfully.',
            ];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to send OTP.',
                [
                    'phone' => $request->input('phone'),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    public function verifyOtp(Request $request): array
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'otp' => [
                        'required',
                        'digits:' . config('hotspot.otp_length'),
                    ],
                ]
            );

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'statuscode' => 422,
                    'message' => $validator->errors()->first('otp'),
                ];
            }

            $otpSessionId = session('otp_session_id');

            if (!$otpSessionId) {
                return [
                    'success' => false,
                    'statuscode' => 400,
                    'message' => 'Invalid OTP session. Please request a new OTP.',
                ];
            }

            $otpSession = OtpSession::where(
                'id',
                $otpSessionId
            )
                ->where('status', 'pending')
                ->first();

            if (!$otpSession) {
                return [
                    'success' => false,
                    'statuscode' => 404,
                    'message' => 'No active OTP found. Please request a new one.',
                ];
            }

            $phone = $otpSession->phone;

            if (
                $otpSession->expires_at !== null &&
                $otpSession->expires_at->isPast()
            ) {
                $otpSession->update([
                    'status' => 'expired',
                ]);

                return [
                    'success' => false,
                    'statuscode' => 410,
                    'message' => 'OTP has expired. Please request a new one.',
                ];
            }

            if (
                $otpSession->attempts >=
                config('hotspot.otp_max_attempts')
            ) {
                $otpSession->update([
                    'status' => 'failed',
                ]);

                return [
                    'success' => false,
                    'statuscode' => 429,
                    'message' => 'Too many wrong attempts. Please request a new OTP.',
                ];
            }

            if ($request->otp !== $otpSession->otp) {
                $otpSession->increment('attempts');

                $remaining = max(
                    0,
                    config('hotspot.otp_max_attempts')
                    - $otpSession->fresh()->attempts
                );

                return [
                    'success' => false,
                    'statuscode' => 401,
                    'message' => "Wrong OTP. {$remaining} attempts remaining.",
                ];
            }

            $wifiUser = WifiUser::where(
                'mac_address',
                $otpSession->mac
            )->first();

            if ($wifiUser && !$wifiUser->is_active) {
                session()->forget('otp_session_id');

                return [
                    'success' => false,
                    'statuscode' => 403,
                    'message' => 'You have been blocked. Please contact support.',
                ];
            }

            $radiusUsername = $phone;
            $radiusPassword = bin2hex(
                random_bytes(16)
            );

            $sessionEnd = now()->addHours(
                config('hotspot.session_hours')
            );

            $radiusCreated = $this->radiusService->createUser(
                $radiusUsername,
                $radiusPassword,
                $sessionEnd
            );

            if (!$radiusCreated) {
                return [
                    'success' => false,
                    'statuscode' => 503,
                    'message' => 'Could not create internet access. Please try again.',
                ];
            }

            try {
                DB::transaction(
                    function () use ($otpSession, $phone, $wifiUser, $radiusUsername, $radiusPassword, $sessionEnd) {
                        $otpSession->update([
                            'status' => 'verified',
                            'verified_at' => now(),
                        ]);

                        if (!$wifiUser) {
                            $wifiUser = WifiUser::create([
                                'mac_address' => $otpSession->mac,
                                'phone_number' => $phone,
                                'ip_address' => $otpSession->ip,
                                'first_login_at' => now(),
                                'last_login_at' => now(),
                                'is_active' => true,
                                'status' => WifiUser::STATUS_ACTIVE,
                                'permanent_access' => false,
                                'session_count' => 1,
                            ]);
                        } else {
                            if (
                                $wifiUser->phone_number !== $phone
                            ) {
                                $wifiUser->old_phone_number =
                                    $wifiUser->phone_number;

                                $wifiUser->phone_number = $phone;
                            }

                            $wifiUser->ip_address =
                                $otpSession->ip;

                            $wifiUser->last_login_at = now();

                            $wifiUser->session_count =
                                $wifiUser->session_count + 1;

                            $wifiUser->save();
                        }

                        HotspotSession::create([
                            'wifi_user_id' => $wifiUser->id,
                            'otp_session_id' => $otpSession->id,
                            'radius_username' => $radiusUsername,
                            'radius_password' => Crypt::encryptString($radiusPassword),
                            'data_limit' => config('hotspot.data_limit'),
                            'mac' => $otpSession->mac,
                            'ip' => $otpSession->ip,
                            'nas_ip' => $otpSession->router_ip,
                            'session_id' => (string) Str::uuid(),
                            'session_start' => now(),
                            'session_end' => $sessionEnd,
                            'download_bytes' => 0,
                            'upload_bytes' => 0,
                            'session_time' => 0,
                            'status' => 'active',
                            'is_active' => true,
                            'terminated_at' => null,
                        ]);
                    }
                );
            } catch (\Throwable $e) {
                Log::channel('error')->error(
                    'Unable to create hotspot database records after RADIUS user creation.',
                    [
                        'radius_username' => $radiusUsername,
                        'mac' => $otpSession->mac,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );

                $this->radiusService->deleteUser(
                    $radiusUsername
                );

                throw $e;
            }

            session([
                'radius_username' => $radiusUsername,
                'radius_password' => $radiusPassword,
                'link_login' => $otpSession->link_login,
                'link_orig' => $otpSession->link_orig,
            ]);

            session()->forget('otp_session_id');

            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'OTP verified successfully.',
            ];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to verify OTP.',
                [
                    'phone' => $phone ?? null,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    public function resendOtp(Request $request): array
    {
        try {
            $otpSessionId = session('otp_session_id');

            if (!$otpSessionId) {
                return [
                    'success' => false,
                    'statuscode' => 400,
                    'message' => 'Invalid OTP session. Please request a new OTP.',
                ];
            }

            $otpSession = OtpSession::where('id', $otpSessionId)->first();

            if (!$otpSession) {
                return [
                    'success' => false,
                    'statuscode' => 404,
                    'message' => 'No active OTP session found.',
                ];
            }

            $phone = $otpSession->phone;

            if (!$otpSession) {
                return [
                    'success' => false,
                    'statuscode' => 404,
                    'message' => 'No active OTP session found.',
                ];
            }

            $wifiUser = WifiUser::where(
                'mac_address',
                $otpSession->mac
            )->first();

            if ($wifiUser && !$wifiUser->is_active) {
                session()->forget('otp_phone');

                return [
                    'success' => false,
                    'statuscode' => 403,
                    'message' => 'You have been blocked. Please contact support.',
                ];
            }

            if (
                $otpSession->last_resend_at &&
                $otpSession->last_resend_at->diffInSeconds(now())
                < config('hotspot.otp_resend_cooldown')
            ) {
                $remaining = ceil(
                    config('hotspot.otp_resend_cooldown')
                    - $otpSession->last_resend_at->diffInSeconds(now())
                );

                return [
                    'success' => false,
                    'statuscode' => 429,
                    'message' => "Please wait {$remaining} seconds before resending.",
                ];
            }

            if (
                $otpSession->resend_count >=
                config('hotspot.otp_max_resend')
            ) {
                return [
                    'success' => false,
                    'statuscode' => 429,
                    'message' => 'Maximum resend limit reached. Please try again later.',
                ];
            }

            $otp = $this->otpService->generate();

            $otpSession->update([
                'otp' => $otp,
                'attempts' => 0,
                'resend_count' => $otpSession->resend_count + 1,
                'last_resend_at' => now(),
                'expires_at' => now()->addMinutes(
                    config('hotspot.otp_expiry')
                ),
            ]);

            $sendResult = $this->otpService->send(
                $phone,
                $otp
            );

            if (!$sendResult['success']) {
                $otpSession->update([
                    'status' => 'failed',
                ]);

                return [
                    'success' => false,
                    'statuscode' => $sendResult['statuscode'],
                    'message' => $sendResult['message'],
                ];
            }

            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'OTP sent successfully.',
            ];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to resend OTP.',
                [
                    'phone' => session('otp_phone'),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    public function showOtp(): array
    {
        if (!session('otp_phone')) {
            return [
                'success' => false,
                'statuscode' => 400,
            ];
        }

        return [
            'success' => true,
            'statuscode' => 200,
        ];
    }

    public function success(): array
    {
        $username = session('radius_username');
        $password = session('radius_password');
        $linkLogin = session('link_login');
        $linkOrig = session('link_orig') ?: 'https://google.com';

        if (!$username || !$password || !$linkLogin) {
            return [
                'success' => false,
                'statuscode' => 400,
            ];
        }

        session()->forget([
            'radius_username',
            'radius_password',
            'link_login',
            'link_orig',
            'mac',
            'ip',
        ]);

        return [
            'success' => true,
            'statuscode' => 200,
            'username' => $username,
            'password' => $password,
            'linkLogin' => $linkLogin,
            'linkOrig' => $linkOrig,
        ];
    }
}