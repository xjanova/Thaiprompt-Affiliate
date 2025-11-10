<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Installation Authorization Service
 * Manages installation and update authorization through License Server
 */
class InstallationAuthService
{
    protected string $apiUrl;
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->apiUrl = config('license.api_url');
        $this->licenseService = $licenseService;
    }

    /**
     * ตรวจสอบสิทธิ์การติดตั้งใหม่
     * Check if installation is authorized
     */
    public function checkInstallationAuth(?string $licenseKey = null): array
    {
        // Developer Mode Bypass - ให้ dev ติดตั้งได้เสมอ
        if ($this->isDevModeBypass()) {
            return [
                'authorized' => true,
                'method' => 'dev_mode',
                'message' => 'Installation authorized via Developer Mode',
                'bypass' => true,
            ];
        }

        // ไม่มี License Key
        if (empty($licenseKey)) {
            return [
                'authorized' => false,
                'error' => 'no_license',
                'message' => 'License key is required for installation',
            ];
        }

        // ตรวจสอบกับ License Server
        try {
            $installationId = $this->getOrCreateInstallationId();

            $response = Http::timeout(30)->post($this->apiUrl . '/installation/authorize', [
                'license_key' => $licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'installation_id' => $installationId,
                'action' => 'install',
                'server_info' => $this->getServerInfo(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    // บันทึก installation_id และ license key ไว้
                    $this->saveInstallationCredentials($licenseKey, $installationId);

                    return [
                        'authorized' => true,
                        'method' => 'license_server',
                        'message' => $data['message'] ?? 'Installation authorized',
                        'license_info' => $data['license'] ?? null,
                        'installation_id' => $installationId,
                    ];
                }

                return [
                    'authorized' => false,
                    'error' => $data['code'] ?? 'unauthorized',
                    'message' => $data['message'] ?? 'Installation not authorized',
                ];
            }

            // Server ไม่ตอบกลับถูกต้อง
            return [
                'authorized' => false,
                'error' => 'server_error',
                'message' => 'License server returned invalid response',
            ];

        } catch (\Exception $e) {
            Log::error('Installation authorization check failed', [
                'error' => $e->getMessage(),
                'license_key' => substr($licenseKey, 0, 8) . '...',
            ]);

            return [
                'authorized' => false,
                'error' => 'connection_error',
                'message' => 'Cannot connect to license server: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบสิทธิ์การอัพเดท
     * Check if update is authorized
     */
    public function checkUpdateAuth(?string $version = null): array
    {
        // Developer Mode Bypass
        if ($this->isDevModeBypass()) {
            return [
                'authorized' => true,
                'method' => 'dev_mode',
                'message' => 'Update authorized via Developer Mode',
                'bypass' => true,
            ];
        }

        // ตรวจสอบว่าติดตั้งแล้วหรือยัง
        if (!$this->isInstalled()) {
            return [
                'authorized' => false,
                'error' => 'not_installed',
                'message' => 'System not installed yet',
            ];
        }

        // ดึง License และ Installation ID ที่บันทึกไว้
        $licenseKey = config('license.license_key');
        $installationId = $this->getInstallationId();

        if (empty($licenseKey)) {
            return [
                'authorized' => false,
                'error' => 'no_license',
                'message' => 'License key not found',
            ];
        }

        // ตรวจสอบกับ License Server
        try {
            $response = Http::timeout(30)->post($this->apiUrl . '/installation/authorize', [
                'license_key' => $licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'installation_id' => $installationId,
                'action' => 'update',
                'version' => $version,
                'current_version' => config('version.current'),
                'server_info' => $this->getServerInfo(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    return [
                        'authorized' => true,
                        'method' => 'license_server',
                        'message' => $data['message'] ?? 'Update authorized',
                        'license_info' => $data['license'] ?? null,
                        'update_info' => $data['update_info'] ?? null,
                    ];
                }

                return [
                    'authorized' => false,
                    'error' => $data['code'] ?? 'unauthorized',
                    'message' => $data['message'] ?? 'Update not authorized',
                ];
            }

            return [
                'authorized' => false,
                'error' => 'server_error',
                'message' => 'License server returned invalid response',
            ];

        } catch (\Exception $e) {
            Log::error('Update authorization check failed', [
                'error' => $e->getMessage(),
                'version' => $version,
            ]);

            // Offline Mode - อนุญาตให้อัพเดทถ้าเคยผ่านมาแล้ว
            if (config('license.allow_offline')) {
                $cacheKey = 'update_auth_cache';
                $cached = Cache::get($cacheKey);

                if ($cached && ($cached['authorized'] ?? false)) {
                    return [
                        'authorized' => true,
                        'method' => 'offline_cache',
                        'message' => 'Update authorized via offline cache',
                        'offline' => true,
                    ];
                }
            }

            return [
                'authorized' => false,
                'error' => 'connection_error',
                'message' => 'Cannot connect to license server',
            ];
        }
    }

    /**
     * ตรวจสอบว่าเป็น Dev Mode หรือไม่ (bypass license)
     */
    protected function isDevModeBypass(): bool
    {
        // Check DevMode middleware
        if (class_exists(\App\Http\Middleware\DevMode::class)) {
            return \App\Http\Middleware\DevMode::isDevMode();
        }

        return false;
    }

    /**
     * ตรวจสอบว่าระบบติดตั้งแล้วหรือยัง
     */
    protected function isInstalled(): bool
    {
        // ตรวจสอบว่ามีไฟล์ .setup_completed
        if (file_exists(storage_path('app/.setup_completed'))) {
            return true;
        }

        // ตรวจสอบว่ามี super admin ในระบบ
        try {
            return \App\Models\User::where('is_super_admin', true)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ดึงหรือสร้าง Installation ID
     */
    protected function getOrCreateInstallationId(): string
    {
        $storagePath = storage_path('app/installation_id.txt');

        if (file_exists($storagePath)) {
            return trim(file_get_contents($storagePath));
        }

        $installationId = (string) Str::uuid();

        // สร้าง directory ถ้ายังไม่มี
        $storageDir = dirname($storagePath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        file_put_contents($storagePath, $installationId);

        return $installationId;
    }

    /**
     * ดึง Installation ID ที่มีอยู่
     */
    protected function getInstallationId(): ?string
    {
        $storagePath = storage_path('app/installation_id.txt');

        if (file_exists($storagePath)) {
            return trim(file_get_contents($storagePath));
        }

        return config('license.installation_id');
    }

    /**
     * บันทึก Installation Credentials
     */
    protected function saveInstallationCredentials(string $licenseKey, string $installationId): void
    {
        // บันทึก installation_id ในไฟล์
        $storagePath = storage_path('app/installation_id.txt');
        $storageDir = dirname($storagePath);

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        file_put_contents($storagePath, $installationId);

        // บันทึกใน .env (จะทำตอน setup จริง)
        // updateEnvFile() จะถูกเรียกใน SetupController
    }

    /**
     * รวบรวมข้อมูล Server สำหรับส่งไปยัง License Server
     */
    protected function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_version' => config('version.current'),
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'db_driver' => config('database.default'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * ตรวจสอบ License สำหรับระบบที่ติดตั้งแล้ว
     */
    public function validateInstalledLicense(): array
    {
        // Developer Mode Bypass
        if ($this->isDevModeBypass()) {
            return [
                'valid' => true,
                'method' => 'dev_mode',
                'message' => 'License validation bypassed via Developer Mode',
            ];
        }

        // ใช้ LicenseService validate
        return $this->licenseService->validate();
    }

    /**
     * รีเซ็ตการติดตั้ง (สำหรับ dev เท่านั้น)
     */
    public function resetInstallation(): bool
    {
        // ต้องเป็น Dev Mode เท่านั้น
        if (!$this->isDevModeBypass()) {
            return false;
        }

        // ลบไฟล์ .setup_completed
        $setupFile = storage_path('app/.setup_completed');
        if (file_exists($setupFile)) {
            unlink($setupFile);
        }

        // ลบ installation_id (ถ้าต้องการ)
        // $installationFile = storage_path('app/installation_id.txt');
        // if (file_exists($installationFile)) {
        //     unlink($installationFile);
        // }

        return true;
    }
}
