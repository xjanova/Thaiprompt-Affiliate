<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenProvider;
use App\Models\Setting;
use App\Services\AiGen\AiGenProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiGenAdminController extends Controller
{
    /**
     * Get all providers.
     */
    public function providers(Request $request)
    {
        try {
            $providers = AiGenProvider::with('configs')->get();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $providers,
                ]);
            }

            return view('admin.ai-gen.providers', [
                'providers' => $providers,
            ]);
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create provider.
     */
    public function createProvider(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ai_gen_providers,slug',
            'type' => 'required|in:image,video,both',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'supported_features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        try {
            if (! isset($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $provider = AiGenProvider::create($validated);

            return response()->json([
                'success' => true,
                'data' => $provider,
                'message' => 'Provider created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update provider.
     */
    public function updateProvider(Request $request, int $providerId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ai_gen_providers,slug,'.$providerId,
            'type' => 'nullable|in:image,video,both',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'supported_features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        try {
            $provider = AiGenProvider::findOrFail($providerId);
            $provider->update($validated);

            return response()->json([
                'success' => true,
                'data' => $provider,
                'message' => 'Provider updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update provider config.
     */
    public function updateProviderConfig(Request $request, int $providerId): JsonResponse
    {
        $validated = $request->validate([
            'configs' => 'required|array',
            'configs.*.key' => 'required|string',
            'configs.*.value' => 'nullable|string',
            'configs.*.is_encrypted' => 'nullable|boolean',
        ]);

        try {
            $provider = AiGenProvider::findOrFail($providerId);

            foreach ($validated['configs'] as $config) {
                $provider->setConfig(
                    $config['key'],
                    $config['value'] ?? '',
                    $config['is_encrypted'] ?? false
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Provider configuration updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test provider connection.
     */
    public function testProvider(int $providerId): JsonResponse
    {
        try {
            $provider = AiGenProvider::findOrFail($providerId);
            $instance = AiGenProviderFactory::createFromModel($provider);

            if (! $instance) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider implementation not found',
                ], 404);
            }

            if (! method_exists($instance, 'testConnection')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Test connection not implemented for this provider',
                ], 400);
            }

            $result = $instance->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ provider
     */
    public function deleteProvider(int $providerId): JsonResponse
    {
        try {
            $provider = AiGenProvider::findOrFail($providerId);
            $provider->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบ Provider สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ===================================================================
    // Generations Gallery
    // ===================================================================

    // ===================================================================
    // Subscriptions
    // ===================================================================

    // ===================================================================
    // Settings (Wallet, Pricing, General)
    // ===================================================================

    /**
     * แสดงหน้าตั้งค่า AI Gen (wallet, pricing, promotions)
     */
    public function settings(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $this->getSettingsData(),
            ]);
        }

        return view('admin.ai-gen.settings', [
            'settings' => $this->getSettingsData(),
            'providers' => AiGenProvider::active()->orderBy('priority')->get(),
            'storageInfo' => $this->getStorageInfo(),
        ]);
    }

    /**
     * บันทึกตั้งค่า AI Gen
     */
    public function saveSettings(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'ai_gen_wallet_enabled' => 'nullable|boolean',
                'ai_gen_wallet_cost_image' => 'nullable|numeric|min:0',
                'ai_gen_wallet_cost_video' => 'nullable|numeric|min:0',
                'ai_gen_system_enabled' => 'nullable|boolean',
                'ai_gen_max_daily_generations' => 'nullable|integer|min:0',
                'ai_gen_max_prompt_length' => 'nullable|integer|min:10',
                'ai_gen_allow_nsfw' => 'nullable|boolean',
                'ai_gen_default_provider' => 'nullable|string',
                'ai_gen_max_storage_mb' => 'nullable|integer|min:0',
                'ai_gen_auto_cleanup_days' => 'nullable|integer|min:0',
                // ราคาต่อ provider
                'provider_pricing' => 'nullable|array',
                'provider_pricing.*.wallet_cost_per_image' => 'nullable|numeric|min:0',
                'provider_pricing.*.wallet_cost_per_video' => 'nullable|numeric|min:0',
            ]);

            // บันทึก settings ทั่วไป
            $settingsKeys = [
                'ai_gen_wallet_enabled' => 'boolean',
                'ai_gen_wallet_cost_image' => 'float',
                'ai_gen_wallet_cost_video' => 'float',
                'ai_gen_system_enabled' => 'boolean',
                'ai_gen_max_daily_generations' => 'integer',
                'ai_gen_max_prompt_length' => 'integer',
                'ai_gen_allow_nsfw' => 'boolean',
                'ai_gen_default_provider' => 'string',
                'ai_gen_max_storage_mb' => 'integer',
                'ai_gen_auto_cleanup_days' => 'integer',
            ];

            foreach ($settingsKeys as $key => $type) {
                if (array_key_exists($key, $data)) {
                    Setting::set($key, $data[$key] ?? ($type === 'boolean' ? false : null), $type, 'ai_gen');
                }
            }

            // บันทึก pricing ต่อ provider
            if (isset($data['provider_pricing'])) {
                foreach ($data['provider_pricing'] as $providerId => $pricing) {
                    $provider = AiGenProvider::find($providerId);
                    if ($provider) {
                        $provider->update([
                            'wallet_cost_per_image' => $pricing['wallet_cost_per_image'] ?? null,
                            'wallet_cost_per_video' => $pricing['wallet_cost_per_video'] ?? null,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึกตั้งค่าสำเร็จ',
                'data' => $this->getSettingsData(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลตั้งค่าทั้งหมด
     */
    protected function getSettingsData(): array
    {
        return [
            'wallet_enabled' => Setting::get('ai_gen_wallet_enabled', false),
            'wallet_cost_image' => Setting::get('ai_gen_wallet_cost_image', 5),
            'wallet_cost_video' => Setting::get('ai_gen_wallet_cost_video', 20),
            'system_enabled' => Setting::get('ai_gen_system_enabled', true),
            'max_daily_generations' => Setting::get('ai_gen_max_daily_generations', 100),
            'max_prompt_length' => Setting::get('ai_gen_max_prompt_length', 1000),
            'allow_nsfw' => Setting::get('ai_gen_allow_nsfw', false),
            'default_provider' => Setting::get('ai_gen_default_provider', ''),
            'max_storage_mb' => Setting::get('ai_gen_max_storage_mb', 500),
            'auto_cleanup_days' => Setting::get('ai_gen_auto_cleanup_days', 30),
        ];
    }

    // ===================================================================
    // Promotions Management
    // ===================================================================

    // ===================================================================
    // Storage Management (จัดการพื้นที่เก็บภาพ)
    // ===================================================================

    /**
     * ดึงข้อมูลพื้นที่เก็บภาพ AI Gen
     */
    public function storageInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getStorageInfo(),
        ]);
    }

    /**
     * ลบภาพเก่าเพื่อเพิ่มพื้นที่
     */
    public function storageCleanup(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        try {
            $disk = Storage::disk('public');
            $basePath = 'ai-gen';
            $deletedCount = 0;
            $freedBytes = 0;
            $cutoffDate = now()->subDays($days);

            // ดึงรายชื่อไฟล์ทั้งหมดใน ai-gen/
            $files = $disk->allFiles($basePath);

            foreach ($files as $file) {
                $lastModified = $disk->lastModified($file);

                if ($lastModified < $cutoffDate->timestamp) {
                    $fileSize = $disk->size($file);
                    $disk->delete($file);
                    $deletedCount++;
                    $freedBytes += $fileSize;
                }
            }

            // แสดงขนาดที่เพิ่มขึ้น
            $freedDisplay = $this->formatBytes($freedBytes);

            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'freed_bytes' => $freedBytes,
                'freed_display' => $freedDisplay,
                'message' => "ลบภาพเก่า {$deletedCount} ไฟล์ เพิ่มพื้นที่ {$freedDisplay}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณข้อมูลพื้นที่เก็บภาพ
     *
     * @return array ข้อมูลพื้นที่ (used_bytes, used_display, max_mb, percent, file_count)
     */
    protected function getStorageInfo(): array
    {
        try {
            $disk = Storage::disk('public');
            $basePath = 'ai-gen';
            $totalBytes = 0;
            $fileCount = 0;

            if ($disk->exists($basePath)) {
                $files = $disk->allFiles($basePath);
                $fileCount = count($files);

                foreach ($files as $file) {
                    $totalBytes += $disk->size($file);
                }
            }

            $maxStorageMb = (int) Setting::get('ai_gen_max_storage_mb', 500);
            $usedMb = round($totalBytes / 1024 / 1024, 2);
            $percent = $maxStorageMb > 0 ? min(round(($usedMb / $maxStorageMb) * 100, 1), 100) : 0;

            return [
                'used_bytes' => $totalBytes,
                'used_mb' => $usedMb,
                'used_display' => $this->formatBytes($totalBytes),
                'max_mb' => $maxStorageMb,
                'percent' => $percent,
                'file_count' => $fileCount,
            ];
        } catch (\Exception $e) {
            return [
                'used_bytes' => 0,
                'used_mb' => 0,
                'used_display' => '0 MB',
                'max_mb' => 500,
                'percent' => 0,
                'file_count' => 0,
            ];
        }
    }

    /**
     * แปลง bytes เป็นหน่วยที่อ่านง่าย
     *
     * @param int $bytes จำนวน bytes
     * @return string เช่น "12.5 MB", "1.2 GB"
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
