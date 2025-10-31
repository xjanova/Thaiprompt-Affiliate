<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddonService
{
    protected string $apiUrl;
    protected ?string $licenseKey;

    public function __construct()
    {
        $this->apiUrl = config('license.api_url');
        $this->licenseKey = config('license.license_key');
    }

    /**
     * รายการ Add-ons ทั้งหมดที่มี
     */
    public function getAvailableAddons(): array
    {
        $cacheKey = 'available_addons';

        return Cache::remember($cacheKey, 86400, function () {
            try {
                $response = Http::timeout(10)->get($this->apiUrl . '/addons');

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['addons'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('Failed to fetch available addons', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * รายการ Add-ons ที่เปิดใช้งาน
     */
    public function getActivatedAddons(): array
    {
        if (empty($this->licenseKey)) {
            return [];
        }

        $cacheKey = 'activated_addons_' . md5($this->licenseKey);

        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = Http::timeout(10)->post($this->apiUrl . '/addons/activated', [
                    'license_key' => $this->licenseKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['addons'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('Failed to fetch activated addons', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * ตรวจสอบว่า Add-on เปิดใช้งานหรือไม่
     */
    public function isAddonActivated(string $addonSlug): bool
    {
        // ตรวจสอบจาก config ก่อน
        $addons = config('license.addons');

        if (isset($addons[$addonSlug]) && $addons[$addonSlug]['enabled']) {
            // ตรวจสอบกับ server
            return $this->validateAddon($addonSlug);
        }

        return false;
    }

    /**
     * ตรวจสอบ Add-on License
     */
    public function validateAddon(string $addonSlug): bool
    {
        if (empty($this->licenseKey)) {
            return false;
        }

        // Developer mode
        if (config('license.developer_mode')) {
            return true;
        }

        $addons = config('license.addons');
        $addonLicenseKey = $addons[$addonSlug]['license_key'] ?? null;

        $cacheKey = "addon_validation_{$addonSlug}_" . md5($this->licenseKey);

        return Cache::remember($cacheKey, 86400, function () use ($addonSlug, $addonLicenseKey) {
            try {
                $response = Http::timeout(10)->post($this->apiUrl . '/addons/validate', [
                    'license_key' => $this->licenseKey,
                    'addon_slug' => $addonSlug,
                    'addon_license_key' => $addonLicenseKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['success'] ?? false;
                }

                return false;
            } catch (\Exception $e) {
                Log::error('Failed to validate addon', [
                    'addon' => $addonSlug,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * เปิดใช้งาน Add-on
     */
    public function enableAddon(string $addonSlug, string $addonLicenseKey): array
    {
        $validation = $this->validateAddon($addonSlug);

        if (!$validation) {
            return [
                'success' => false,
                'error' => 'invalid_addon_license',
                'message' => 'Invalid add-on license key',
            ];
        }

        // อัปเดต .env
        $envKey = 'ADDON_' . strtoupper(str_replace('-', '_', $addonSlug)) . '_LICENSE_KEY';
        $this->updateEnvFile($envKey, $addonLicenseKey);

        $enabledKey = 'ADDON_' . strtoupper(str_replace('-', '_', $addonSlug)) . '_ENABLED';
        $this->updateEnvFile($enabledKey, 'true');

        // ล้าง cache
        $this->clearAddonCache($addonSlug);

        return [
            'success' => true,
            'message' => 'Add-on enabled successfully',
            'addon' => $addonSlug,
        ];
    }

    /**
     * ปิดการใช้งาน Add-on
     */
    public function disableAddon(string $addonSlug): array
    {
        $enabledKey = 'ADDON_' . strtoupper(str_replace('-', '_', $addonSlug)) . '_ENABLED';
        $this->updateEnvFile($enabledKey, 'false');

        $this->clearAddonCache($addonSlug);

        return [
            'success' => true,
            'message' => 'Add-on disabled successfully',
            'addon' => $addonSlug,
        ];
    }

    /**
     * ล้าง Cache ของ Add-on
     */
    public function clearAddonCache(string $addonSlug = null): void
    {
        if ($addonSlug) {
            $cacheKey = "addon_validation_{$addonSlug}_" . md5($this->licenseKey);
            Cache::forget($cacheKey);
        } else {
            Cache::forget('available_addons');
            Cache::forget('activated_addons_' . md5($this->licenseKey));
        }
    }

    /**
     * ตรวจสอบสถานะ Add-ons ทั้งหมด
     */
    public function checkAllAddons(): array
    {
        $configAddons = config('license.addons');
        $status = [];

        foreach ($configAddons as $slug => $config) {
            $status[$slug] = [
                'name' => $config['name'],
                'slug' => $slug,
                'enabled' => $config['enabled'],
                'activated' => $this->isAddonActivated($slug),
                'has_license' => !empty($config['license_key']),
            ];
        }

        return $status;
    }

    /**
     * อัปเดต .env file
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        if (preg_match("/^{$key}=.*/m", $envContent)) {
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            $envContent .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $envContent);
    }
}
