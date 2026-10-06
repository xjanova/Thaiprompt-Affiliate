<?php

namespace App\Services\Fcm;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 📨 ตัวส่ง Firebase Cloud Messaging (HTTP v1) แบบใช้ซ้ำได้
 *
 * แยกออกมาจาก FcmNotificationService (แอป SMS Checker) โดยคงพฤติกรรมเดิมทุกอย่าง:
 *   - credentials: Setting fcm_credentials_path → config services.firebase.credentials (storage/app/firebase-credentials.json)
 *   - project: Setting fcm_project_id → config services.firebase.project_id → project_id ในไฟล์ credentials (plptdb)
 *   - OAuth2: JWT (RS256) แลก access token จาก oauth2.googleapis.com — เก็บในหน่วยความจำของอินสแตนซ์เท่านั้น
 *     (ไม่ลง cache/DB — เป็นความลับ) หมดอายุก่อน 60 วิค่อยขอใหม่
 *   - ข้อความ: data ทุกค่าเป็น string · android priority high · ttl 24 ชม.
 *
 * ⚠️ ห้าม log FCM registration token / access token — log แค่รหัส error จาก FCM
 */
class FcmHttpV1Client
{
    private ?string $accessToken = null;

    private ?int $tokenExpiry = null;

    /**
     * path ไฟล์ service account (อาจยังไม่มีไฟล์จริง)
     */
    public function credentialsPath(): ?string
    {
        $path = Setting::get('fcm_credentials_path') ?: config('services.firebase.credentials');

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * พร้อมส่งไหม — มีไฟล์ credentials ที่อ่านได้ + หา project id ได้ (ไม่ยิงเครือข่าย)
     */
    public function isConfigured(): bool
    {
        $path = $this->credentialsPath();

        return $path !== null && is_file($path) && is_readable($path) && $this->projectId() !== null;
    }

    /**
     * Firebase project id — DB → config → ไฟล์ credentials
     */
    public function projectId(): ?string
    {
        $projectId = Setting::get('fcm_project_id') ?: config('services.firebase.project_id');
        if (! $projectId) {
            $projectId = $this->projectIdFromCredentials();
        }

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    /**
     * อ่าน project_id จากไฟล์ credentials JSON (fallback เมื่อไม่ได้ตั้ง env/DB)
     */
    public function projectIdFromCredentials(): ?string
    {
        $credentialsPath = $this->credentialsPath();
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            return $credentials['project_id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * OAuth2 access token สำหรับ FCM API (cache ในอินสแตนซ์)
     */
    public function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        // Read credentials path from database first, fallback to config
        $credentialsPath = $this->credentialsPath();
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            Log::error('FCM: Firebase credentials file not found', ['path' => $credentialsPath]);

            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            // Create JWT
            $now = time();
            $jwt = $this->createJwt([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $credentials['private_key']);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600);

                return $this->accessToken;
            }

            Log::error('FCM: Failed to get access token', ['response' => $response->json()]);

            return null;
        } catch (\Exception $e) {
            Log::error('FCM: Exception getting access token', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ส่งข้อความไปเครื่องเดียว
     *
     * @param  array<string, mixed>  $data  payload (แปลงเป็น string ทุกค่า)
     * @param  array{title?: string, body?: string}|null  $notification  null = data-only (ไม่เด้งบนจอ)
     * @param  array<string, mixed>|null  $androidNotification  ค่า android.notification (ใช้เมื่อมี notification)
     * @param  array<string, mixed>  $extraMessage  ฟิลด์ระดับ message เพิ่มเติม เช่น apns (merge ทับ)
     */
    public function send(
        string $token,
        array $data,
        ?array $notification = null,
        ?array $androidNotification = null,
        array $extraMessage = [],
    ): FcmSendResult {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::error('FCM: Failed to get access token');

            return new FcmSendResult(false, false, 'NO_ACCESS_TOKEN', 0);
        }

        // Read project ID from database first, fallback to config, then from credentials file
        $projectId = $this->projectId();
        if (! $projectId) {
            Log::error('FCM: Firebase project ID not configured');

            return new FcmSendResult(false, false, 'NO_PROJECT_ID', 0);
        }

        $message = [
            'token' => $token,
            'data' => array_map('strval', $data), // FCM data must be strings
            'android' => [
                'priority' => 'high',
                'ttl' => '86400s', // 24 hours
            ],
        ];

        // Add notification if provided (visible push)
        if ($notification) {
            $message['notification'] = $notification;
            if ($androidNotification) {
                $message['android']['notification'] = $androidNotification;
            }
        }

        if ($extraMessage !== []) {
            $message = array_replace_recursive($message, $extraMessage);
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    ['message' => $message]
                );

            if ($response->successful()) {
                return new FcmSendResult(true, false, null, $response->status());
            }

            $error = $response->json('error.details.0.errorCode') ?? $response->json('error.message');
            Log::warning('FCM: Send failed', [
                'error' => $error,
                'status' => $response->status(),
            ]);

            return new FcmSendResult(false, $this->isInvalidTokenResponse($response->json() ?? []), is_string($error) ? $error : null, $response->status());
        } catch (\Exception $e) {
            Log::error('FCM: Exception during send', ['error' => $e->getMessage()]);

            return new FcmSendResult(false, false, 'EXCEPTION', 0);
        }
    }

    /**
     * FCM บอกว่า token นี้ใช้ไม่ได้แล้ว (ควรลบทิ้ง) ไหม
     *
     * - UNREGISTERED (404) = แอปถูกถอน/token หมดอายุ
     * - SENDER_ID_MISMATCH (403) = token เป็นของ Firebase project อื่น
     * - INVALID_ARGUMENT (400) **เฉพาะ** เมื่อ FCM ระบุว่าผิดที่ token — INVALID_ARGUMENT ทั่วไปอาจเป็นเพราะ payload ของเราเอง
     *   (ถ้าลบตามหมด payload พังครั้งเดียว = token ทุกเครื่องหาย)
     *
     * @param  array<string, mixed>  $body
     */
    public function isInvalidTokenResponse(array $body): bool
    {
        $codes = [];
        foreach ((array) data_get($body, 'error.details', []) as $detail) {
            if (is_array($detail) && isset($detail['errorCode'])) {
                $codes[] = (string) $detail['errorCode'];
            }
        }
        $status = (string) data_get($body, 'error.status', '');

        if (array_intersect($codes, ['UNREGISTERED', 'SENDER_ID_MISMATCH']) !== []) {
            return true;
        }

        if (in_array('INVALID_ARGUMENT', $codes, true) || $status === 'INVALID_ARGUMENT') {
            $message = strtolower((string) data_get($body, 'error.message', ''));
            if (str_contains($message, 'registration token')) {
                return true;
            }

            foreach ((array) data_get($body, 'error.details', []) as $detail) {
                foreach ((array) ($detail['fieldViolations'] ?? []) as $violation) {
                    if (($violation['field'] ?? null) === 'message.token') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Create JWT for Google OAuth2
     */
    private function createJwt(array $payload, string $privateKey): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
