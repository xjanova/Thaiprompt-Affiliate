<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseService
{
    protected string $apiUrl;
    protected ?string $licenseKey;
    protected ?string $installationId;

    public function __construct()
    {
        $this->apiUrl = config('license.api_url');
        $this->licenseKey = config('license.license_key');
        $this->installationId = config('license.installation_id') ?: $this->getOrCreateInstallationId();
    }

    /**
     * ตรวจสอบ License
     */
    public function validate(): array
    {
        // Developer Mode - ข้าม License check
        if (config('license.developer_mode')) {
            return [
                'valid' => true,
                'developer_mode' => true,
                'message' => 'Developer mode enabled',
            ];
        }

        // ไม่มี License Key
        if (empty($this->licenseKey)) {
            return [
                'valid' => false,
                'error' => 'no_license_key',
                'message' => 'License key not found. Please activate your license.',
            ];
        }

        // ตรวจสอบจาก Cache ก่อน
        $cacheKey = 'license_validation_' . md5($this->licenseKey);
        $cacheDuration = config('license.cache_duration');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(10)->post($this->apiUrl . '/validate', [
                'license_key' => $this->licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'installation_id' => $this->installationId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    $result = [
                        'valid' => true,
                        'license' => $data['license'],
                        'checked_at' => now()->toDateTimeString(),
                    ];

                    // Cache ผลลัพธ์
                    Cache::put($cacheKey, $result, $cacheDuration);

                    return $result;
                }
            }

            // License ไม่ถูกต้อง
            $error = $response->json();

            return [
                'valid' => false,
                'error' => $error['code'] ?? 'invalid_license',
                'message' => $error['message'] ?? 'Invalid license',
            ];
        } catch (\Exception $e) {
            Log::error('License validation error', [
                'error' => $e->getMessage(),
            ]);

            // Offline mode - ใช้ cache เก่า
            if (config('license.allow_offline')) {
                $cached = Cache::get($cacheKey . '_backup');

                if ($cached) {
                    return $cached;
                }
            }

            return [
                'valid' => false,
                'error' => 'connection_error',
                'message' => 'Cannot connect to license server',
            ];
        }
    }

    /**
     * เปิดใช้งาน License
     */
    public function activate(string $licenseKey): array
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl . '/activate', [
                'license_key' => $licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'installation_id' => $this->installationId,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'user_agent' => request()->userAgent(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // บันทึก License Key
                    $this->updateEnvFile('LICENSE_KEY', $licenseKey);
                    $this->updateEnvFile('LICENSE_INSTALLATION_ID', $this->installationId);

                    // ล้าง Cache
                    $this->clearCache();

                    return [
                        'success' => true,
                        'message' => 'License activated successfully',
                        'activation' => $data['activation'],
                    ];
                }
            }

            $error = $response->json();

            return [
                'success' => false,
                'error' => $error['code'] ?? 'activation_failed',
                'message' => $error['message'] ?? 'Failed to activate license',
            ];
        } catch (\Exception $e) {
            Log::error('License activation error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'connection_error',
                'message' => 'Cannot connect to license server',
            ];
        }
    }

    /**
     * ปิดการใช้งาน License
     */
    public function deactivate(): array
    {
        if (empty($this->licenseKey)) {
            return [
                'success' => false,
                'error' => 'no_license_key',
                'message' => 'No license to deactivate',
            ];
        }

        try {
            $response = Http::timeout(10)->post($this->apiUrl . '/deactivate', [
                'license_key' => $this->licenseKey,
                'domain' => request()->getHost(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // ลบ License Key
                    $this->updateEnvFile('LICENSE_KEY', '');

                    // ล้าง Cache
                    $this->clearCache();

                    return [
                        'success' => true,
                        'message' => 'License deactivated successfully',
                    ];
                }
            }

            $error = $response->json();

            return [
                'success' => false,
                'error' => $error['code'] ?? 'deactivation_failed',
                'message' => $error['message'] ?? 'Failed to deactivate license',
            ];
        } catch (\Exception $e) {
            Log::error('License deactivation error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'connection_error',
                'message' => 'Cannot connect to license server',
            ];
        }
    }

    /**
     * ตรวจสอบสถานะ License
     */
    public function status(): array
    {
        if (empty($this->licenseKey)) {
            return [
                'activated' => false,
                'message' => 'License not activated',
            ];
        }

        $validation = $this->validate();

        return [
            'activated' => $validation['valid'],
            'license_key' => $this->licenseKey,
            'domain' => request()->getHost(),
            'installation_id' => $this->installationId,
            'validation' => $validation,
        ];
    }

    /**
     * ล้าง Cache
     */
    public function clearCache(): void
    {
        if ($this->licenseKey) {
            $cacheKey = 'license_validation_' . md5($this->licenseKey);
            Cache::forget($cacheKey);
            Cache::forget($cacheKey . '_backup');
        }
    }

    /**
     * สร้างหรือดึง Installation ID
     */
    protected function getOrCreateInstallationId(): string
    {
        $storagePath = storage_path('app/installation_id.txt');

        if (file_exists($storagePath)) {
            return trim(file_get_contents($storagePath));
        }

        $installationId = (string) Str::uuid();
        file_put_contents($storagePath, $installationId);

        return $installationId;
    }

    /**
     * อัปเดตไฟล์ .env
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        // ตรวจสอบว่ามี key อยู่แล้วหรือไม่
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            // แทนที่ค่าเดิม
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            // เพิ่มใหม่
            $envContent .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * ตรวจสอบว่า License ใกล้หมดอายุหรือไม่
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $validation = $this->validate();

        if (!$validation['valid']) {
            return false;
        }

        $expiresAt = $validation['license']['expires_at'] ?? null;

        if (!$expiresAt) {
            return false; // ไม่หมดอายุ
        }

        $expiryDate = \Carbon\Carbon::parse($expiresAt);
        $now = \Carbon\Carbon::now();

        return $expiryDate->diffInDays($now) <= $days;
    }
}
