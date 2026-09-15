<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private string $authKey;
    private string $templateId;
    private string $apiUrl;
    private int $otpLength;

    public function __construct()
    {
        $this->authKey = config('services.msg91.auth_key');
        $this->templateId = config('services.msg91.template_id');
        $this->apiUrl = config('services.msg91.api_url');
        $this->otpLength = config('hotspot.otp_length', 6);
    }

    public function generate(): string
    {
        $max = (10 ** $this->otpLength) - 1;

        return str_pad(
            (string) random_int(0, $max),
            $this->otpLength,
            '0',
            STR_PAD_LEFT
        );
    }

    public function send(string $phone, string $otp): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(
                    [
                        'accept' => 'application/json',
                        'authkey' => $this->authKey,
                        'content-type' => 'application/json',
                    ]
                )
                ->post($this->apiUrl, [
                    'template_id' => $this->templateId,
                    'recipients' => [
                        [
                            'mobiles' => '91' . $phone,
                            'VAR1' => $otp,
                        ],
                    ],
                ]);

            if (
                $response->successful() &&
                $response->json('type') === 'success'
            ) {
                return ['success' => true, 'statuscode' => 200, 'message' => 'OTP sent successfully.',];
            }

            Log::channel('otp')->error(
                'MSG91 OTP send failed',
                [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]
            );

            return ['success' => false, 'statuscode' => $response->status(), 'message' => 'Unable to send OTP.',];
        } catch (\Throwable $e) {
            Log::channel('otp')->error(
                'MSG91 OTP send exception',
                [
                    'phone' => $phone,
                    'exception' => $e->getMessage(),
                ]
            );

            return ['success' => false, 'statuscode' => 503, 'message' => 'OTP service is currently unavailable.',];
        }
    }
}