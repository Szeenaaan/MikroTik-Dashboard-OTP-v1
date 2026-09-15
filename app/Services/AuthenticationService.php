<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\ApplicationActivation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthenticationService
{
    public function login(string $name, string $password): array
    {
        try {
            $user = User::where('name', $name)->first();

            if (!$user) {
                return
                    ['success' => false, 'statuscode' => 401, 'message' => 'Invalid username or password.',];
            }

            if (!Hash::check($password, $user->password)) {
                return ['success' => false, 'statuscode' => 401, 'message' => 'Invalid username or password.',];
            }

            Auth::login($user);
            request()->session()->regenerate();

            $user->update(
                [
                    'last_login_at' => now(),
                    'failed_login_attempts' => 0,
                ]
            );

            return ['success' => true, 'statuscode' => 200, 'message' => 'Login successful.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to authenticate user.',
                [
                    'email' => $name,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function isAdmin(): bool
    {
        return Auth::check()
            && Auth::user()->role?->name === 'ADMIN';
    }

    public function activateApplication(
        string $applicationKey,
        ?string $userAgent,
        ?string $ipAddress
    ): array {
        try {
            if (!hash_equals((string) config('app.application_key'), $applicationKey)) {
                return [
                    'success' => false,
                    'statuscode' => 403,
                    'message' => 'Invalid application key.',
                ];
            }

            $token = Str::random(64);

            ApplicationActivation::create([
                'token' => Hash::make($token),
                'user_agent' => $userAgent !== null
                    ? mb_substr($userAgent, 0, 500)
                    : null,
                'mac_address' => null,
                'ip_address' => $ipAddress,
                'status' => 'active',
                'is_active' => true,
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);

            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'Application activated successfully.',
                'token' => $token,
            ];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Application activation failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent !== null
                        ? mb_substr($userAgent, 0, 500)
                        : null,
                ]
            );

            return [
                'success' => false,
                'statuscode' => 500,
                'message' => 'Application activation failed. Please try again later.',
            ];
        }
    }

    public function checkActivation(?string $token): array
    {
        if (!$token) {
            return [
                'success' => false,
                'statuscode' => 403,
                'activated' => false,
                'message' => 'Application activation required.',
            ];
        }

        $activations = ApplicationActivation::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->get();

        foreach ($activations as $activation) {
            if (Hash::check($token, $activation->token)) {
                return [
                    'success' => true,
                    'statuscode' => 200,
                    'activated' => true,
                    'message' => 'Application already activated.',
                ];
            }
        }

        return [
            'success' => false,
            'statuscode' => 403,
            'activated' => false,
            'message' => 'Application activation required.',
        ];
    }
    public function createUser(
        string $username,
        string $password
    ): array {
        try {
            $validator = Validator::make(
                [
                    'name' => $username,
                    'password' => $password,
                ],
                [
                    'name' => ['required', 'string', 'max:255'],
                    'password' => ['required', 'string'],
                ]
            );

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'statuscode' => 422,
                    'message' => $validator->errors()->first(),
                ];
            }

            $user = User::create([
                'name' => $username,
                'password' => $password,
                'role_id' => 1
            ]);

            return [
                'success' => true,
                'statuscode' => 201,
                'message' => 'User created successfully.',
                'user' => $user,
            ];

        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'User creation failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return [
                'success' => false,
                'statuscode' => 500,
                'message' => 'User creation failed. Please try again later.',
            ];
        }
    }

}