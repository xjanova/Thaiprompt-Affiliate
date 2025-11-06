<?php

namespace App\Services;

use App\Models\OtpSetting;
use App\Models\OtpVerification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private ?OtpSetting $settings;
    private LineService $lineService;

    public function __construct(LineService $lineService)
    {
        $this->settings = OtpSetting::getActive();
        $this->lineService = $lineService;
    }

    /**
     * Send OTP to phone number
     */
    public function sendOTP(string $phone, string $purpose = 'phone_verification'): array
    {
        if (!$this->settings || !$this->settings->enabled) {
            return [
                'success' => false,
                'message' => 'OTP service is not enabled',
            ];
        }

        // Check rate limit
        if (!OtpVerification::checkRateLimit($phone)) {
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please try again later.',
            ];
        }

        // Create OTP
        $otp = OtpVerification::createForPhone(
            $phone,
            $purpose,
            $this->settings->otp_expiry_minutes
        );

        // Send SMS based on provider
        $sent = false;
        $message = $this->settings->getFormattedMessage($otp->otp_code);

        try {
            switch ($this->settings->provider) {
                case 'twilio':
                    $sent = $this->sendViaTwilio($phone, $message);
                    break;

                case 'nexmo':
                    $sent = $this->sendViaNexmo($phone, $message);
                    break;

                case 'custom':
                    $sent = $this->sendViaCustomApi($phone, $message, $otp->otp_code);
                    break;

                default:
                    throw new Exception('Invalid OTP provider');
            }

            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'expires_at' => $otp->expires_at->toDateTimeString(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP',
            ];

        } catch (Exception $e) {
            Log::error('OTP sending failed', [
                'phone' => $phone,
                'provider' => $this->settings->provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error sending OTP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send OTP to user (supports SMS and LINE)
     */
    public function sendOTPToUser(User $user, string $purpose = '2fa_verification', ?string $channel = null): array
    {
        if (!$this->settings || !$this->settings->enabled) {
            return [
                'success' => false,
                'message' => 'OTP service is not enabled',
            ];
        }

        // Check rate limit
        if (!OtpVerification::checkRateLimitForUser($user->id)) {
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please try again later.',
            ];
        }

        // Create OTP for user
        $otp = OtpVerification::createForUser(
            $user,
            $purpose,
            $this->settings->otp_expiry_minutes,
            $channel
        );

        // Send via appropriate channel
        $sent = false;

        try {
            if ($otp->channel === 'line') {
                $sent = $this->sendViaLine($user, $otp->otp_code);
            } else {
                // Default to SMS
                $message = $this->settings->getFormattedMessage($otp->otp_code, 'sms');
                $sent = $this->sendSMS($otp->phone, $message, $otp->otp_code);
            }

            if ($sent) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully via ' . $otp->channel,
                    'channel' => $otp->channel,
                    'expires_at' => $otp->expires_at->toDateTimeString(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP',
            ];

        } catch (Exception $e) {
            Log::error('OTP sending failed for user', [
                'user_id' => $user->id,
                'channel' => $otp->channel,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error sending OTP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP(string $phone, string $code, string $purpose = 'phone_verification'): array
    {
        $verified = OtpVerification::verifyOTP($phone, $code, $purpose);

        if ($verified) {
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid or expired OTP code',
        ];
    }

    /**
     * Verify OTP code for user
     */
    public function verifyOTPForUser(User $user, string $code, string $purpose = '2fa_verification'): array
    {
        $verified = OtpVerification::verifyForUser($user, $code, $purpose);

        if ($verified) {
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid or expired OTP code',
        ];
    }

    /**
     * Send SMS based on configured provider
     */
    private function sendSMS(string $phone, string $message, string $code): bool
    {
        switch ($this->settings->provider) {
            case 'twilio':
                return $this->sendViaTwilio($phone, $message);

            case 'nexmo':
                return $this->sendViaNexmo($phone, $message);

            case 'custom':
                return $this->sendViaCustomApi($phone, $message, $code);

            default:
                throw new Exception('Invalid SMS provider');
        }
    }

    /**
     * Send OTP via LINE messaging
     */
    private function sendViaLine(User $user, string $code): bool
    {
        if (!$this->settings->isLineOtpEnabled()) {
            return false;
        }

        if (!$user->line_user_id) {
            return false;
        }

        $message = $this->settings->getFormattedMessage($code, 'line');

        return $this->lineService->sendPushMessage($user->line_user_id, $message);
    }

    /**
     * Send SMS via Twilio
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        $response = Http::withBasicAuth(
            $this->settings->twilio_sid,
            $this->settings->twilio_token
        )->asForm()->post(
            "https://api.twilio.com/2010-04-01/Accounts/{$this->settings->twilio_sid}/Messages.json",
            [
                'From' => $this->settings->twilio_from,
                'To' => $this->formatPhoneNumber($phone),
                'Body' => $message,
            ]
        );

        return $response->successful();
    }

    /**
     * Send SMS via Nexmo/Vonage
     */
    private function sendViaNexmo(string $phone, string $message): bool
    {
        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $this->settings->nexmo_key,
            'api_secret' => $this->settings->nexmo_secret,
            'from' => $this->settings->nexmo_from,
            'to' => $this->formatPhoneNumber($phone),
            'text' => $message,
        ]);

        $data = $response->json();
        return $response->successful() && isset($data['messages'][0]['status']) && $data['messages'][0]['status'] === '0';
    }

    /**
     * Send SMS via Custom API
     */
    private function sendViaCustomApi(string $phone, string $message, string $code): bool
    {
        $headers = [];
        if ($this->settings->custom_api_headers) {
            $headers = json_decode($this->settings->custom_api_headers, true) ?? [];
        }

        if ($this->settings->custom_api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->settings->custom_api_key;
        }

        $response = Http::withHeaders($headers)->post($this->settings->custom_api_url, [
            'phone' => $this->formatPhoneNumber($phone),
            'message' => $message,
            'code' => $code,
        ]);

        return $response->successful();
    }

    /**
     * Format phone number (add country code if needed)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add Thailand country code if not present
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '66' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '66' . $phone;
        }

        // Add + prefix
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Check if OTP is configured
     */
    public function isConfigured(): bool
    {
        if (!$this->settings || !$this->settings->enabled) {
            return false;
        }

        switch ($this->settings->provider) {
            case 'twilio':
                return !empty($this->settings->twilio_sid) && !empty($this->settings->twilio_token);

            case 'nexmo':
                return !empty($this->settings->nexmo_key) && !empty($this->settings->nexmo_secret);

            case 'custom':
                return !empty($this->settings->custom_api_url);

            default:
                return false;
        }
    }
}
