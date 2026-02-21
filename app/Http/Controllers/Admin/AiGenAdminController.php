<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenPackage;
use App\Models\AiGenPromotion;
use App\Models\AiGenProvider;
use App\Models\AiGenQuota;
use App\Models\AiGenSubscription;
use App\Models\AiGenUsageLog;
use App\Models\Setting;
use App\Services\AiGen\AiGenProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiGenAdminController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        try {
            $stats = [
                'total_providers' => AiGenProvider::count(),
                'active_providers' => AiGenProvider::active()->count(),
                'total_packages' => AiGenPackage::count(),
                'active_subscriptions' => AiGenSubscription::active()->count(),
                'total_generations' => AiGenUsageLog::count(),
                'completed_generations' => AiGenUsageLog::completed()->count(),
                'failed_generations' => AiGenUsageLog::failed()->count(),
                'free_quota_usage' => AiGenUsageLog::freeQuota()->count(),
                'paid_usage' => AiGenUsageLog::paid()->count(),
            ];

            // If request wants JSON (AJAX)
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $stats,
                ]);
            }

            // Return Blade view
            return view('admin.ai-gen.dashboard', [
                'stats' => $stats,
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

    /**
     * Get all packages.
     */
    public function packages(Request $request)
    {
        try {
            $packages = AiGenPackage::orderBy('sort_order')->get();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $packages,
                ]);
            }

            return view('admin.ai-gen.packages', ['packages' => $packages]);
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
     * Create package.
     */
    public function createPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ai_gen_packages,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'image_credits' => 'required|integer|min:0',
            'video_credits' => 'required|integer|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'is_recurring' => 'nullable|boolean',
            'recurring_period' => 'nullable|in:monthly,yearly',
            'features' => 'nullable|array',
            'provider_access' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            if (! isset($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $package = AiGenPackage::create($validated);

            return response()->json([
                'success' => true,
                'data' => $package,
                'message' => 'Package created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update package.
     */
    public function updatePackage(Request $request, int $packageId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ai_gen_packages,slug,'.$packageId,
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'image_credits' => 'nullable|integer|min:0',
            'video_credits' => 'nullable|integer|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'is_recurring' => 'nullable|boolean',
            'recurring_period' => 'nullable|in:monthly,yearly',
            'features' => 'nullable|array',
            'provider_access' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_popular' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $package = AiGenPackage::findOrFail($packageId);
            $package->update($validated);

            return response()->json([
                'success' => true,
                'data' => $package,
                'message' => 'Package updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete package.
     */
    public function deletePackage(int $packageId): JsonResponse
    {
        try {
            $package = AiGenPackage::findOrFail($packageId);

            // Check if package has active subscriptions
            if ($package->activeSubscriptions()->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete package with active subscriptions',
                ], 400);
            }

            $package->delete();

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all quotas.
     */
    public function quotas(Request $request)
    {
        try {
            $quotas = AiGenQuota::all();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $quotas,
                ]);
            }

            return view('admin.ai-gen.quotas', ['quotas' => $quotas]);
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
     * Create or update quota.
     */
    public function saveQuota(Request $request, ?int $quotaId = null): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'free_image_daily' => 'required|integer|min:0',
            'free_image_monthly' => 'required|integer|min:0',
            'free_video_daily' => 'required|integer|min:0',
            'free_video_monthly' => 'required|integer|min:0',
            'role' => 'nullable|in:user,admin',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            if ($quotaId) {
                $quota = AiGenQuota::findOrFail($quotaId);
                $quota->update($validated);
                $message = 'Quota updated successfully';
            } else {
                $quota = AiGenQuota::create($validated);
                $message = 'Quota created successfully';
            }

            return response()->json([
                'success' => true,
                'data' => $quota,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage logs.
     */
    public function usageLogs(Request $request)
    {
        try {
            $query = AiGenUsageLog::with(['user', 'provider', 'subscription']);

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('provider_id')) {
                $query->where('provider_id', $request->provider_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('is_free_quota')) {
                $query->where('is_free_quota', $request->boolean('is_free_quota'));
            }

            $logs = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 50));

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $logs,
                ]);
            }

            return view('admin.ai-gen.usage-logs', [
                'logs' => $logs,
                'providers' => AiGenProvider::all(),
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

    // ===================================================================
    // Generations Gallery
    // ===================================================================

    /**
     * แสดงหน้า gallery ผลงานที่สร้างจาก AI
     */
    public function generations(Request $request)
    {
        try {
            $logs = AiGenUsageLog::with(['user', 'provider'])
                ->where('status', 'completed')
                ->latest()
                ->paginate(24);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $logs,
                ]);
            }

            return view('admin.ai-gen.generations', compact('logs'));
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }

            return view('admin.ai-gen.generations', ['logs' => collect()]);
        }
    }

    // ===================================================================
    // Subscriptions
    // ===================================================================

    /**
     * แสดงรายการ subscriptions ของผู้ใช้
     */
    public function subscriptions(Request $request)
    {
        try {
            $subscriptions = AiGenSubscription::with(['user', 'package'])
                ->latest()
                ->paginate(20);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscriptions,
                ]);
            }

            return view('admin.ai-gen.subscriptions', compact('subscriptions'));
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }

            return view('admin.ai-gen.subscriptions', ['subscriptions' => collect()]);
        }
    }

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
            'promotions' => AiGenPromotion::orderBy('created_at', 'desc')->get(),
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
        ];
    }

    // ===================================================================
    // Promotions Management
    // ===================================================================

    /**
     * แสดงรายการโปรโมชั่น
     */
    public function promotions(Request $request): JsonResponse
    {
        try {
            $promotions = AiGenPromotion::with('provider')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $promotions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้างโปรโมชั ่นใหม่
     */
    public function createPromotion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:discount_percent,discount_fixed,free_credits,bonus_credits',
                'value' => 'required|numeric|min:0',
                'code' => 'nullable|string|max:50|unique:ai_gen_promotions,code',
                'applies_to' => 'required|in:all,image,video',
                'max_uses' => 'nullable|integer|min:1',
                'max_uses_per_user' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after_or_equal:starts_at',
                'min_wallet_balance' => 'nullable|numeric|min:0',
                'provider_id' => 'nullable|integer|exists:ai_gen_providers,id',
                'is_active' => 'nullable|boolean',
            ]);

            // สร้างโค้ดอัตโนมัติถ้าไม่ได้กำหนด
            if (empty($validated['code'])) {
                $validated['code'] = null;
            } else {
                $validated['code'] = strtoupper(trim($validated['code']));
            }

            $promotion = AiGenPromotion::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'สร้างโปรโมชั่นสำเร็จ',
                'data' => $promotion->load('provider'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทโปรโมชั่น
     */
    public function updatePromotion(Request $request, int $promotionId): JsonResponse
    {
        try {
            $promotion = AiGenPromotion::findOrFail($promotionId);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|in:discount_percent,discount_fixed,free_credits,bonus_credits',
                'value' => 'sometimes|numeric|min:0',
                'code' => 'nullable|string|max:50|unique:ai_gen_promotions,code,' . $promotionId,
                'applies_to' => 'sometimes|in:all,image,video',
                'max_uses' => 'nullable|integer|min:1',
                'max_uses_per_user' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date',
                'min_wallet_balance' => 'nullable|numeric|min:0',
                'provider_id' => 'nullable|integer|exists:ai_gen_providers,id',
                'is_active' => 'nullable|boolean',
            ]);

            if (isset($validated['code'])) {
                $validated['code'] = $validated['code'] ? strtoupper(trim($validated['code'])) : null;
            }

            $promotion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทโปรโมชั่นสำเร็จ',
                'data' => $promotion->fresh()->load('provider'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบโปรโมชั่น
     */
    public function deletePromotion(int $promotionId): JsonResponse
    {
        try {
            $promotion = AiGenPromotion::findOrFail($promotionId);
            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบโปรโมชั่นสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
