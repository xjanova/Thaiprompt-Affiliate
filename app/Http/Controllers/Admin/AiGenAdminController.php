<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenProvider;
use App\Models\AiGenPackage;
use App\Models\AiGenQuota;
use App\Models\AiGenSubscription;
use App\Models\AiGenUsageLog;
use App\Services\AiGen\AiGenProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AiGenAdminController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(): JsonResponse
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

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all providers.
     */
    public function providers(): JsonResponse
    {
        try {
            $providers = AiGenProvider::with('configs')->get();

            return response()->json([
                'success' => true,
                'data' => $providers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
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
            if (!isset($validated['slug'])) {
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
            'slug' => 'nullable|string|max:255|unique:ai_gen_providers,slug,' . $providerId,
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

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider implementation not found',
                ], 404);
            }

            if (!method_exists($instance, 'testConnection')) {
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
     * Get all packages.
     */
    public function packages(): JsonResponse
    {
        try {
            $packages = AiGenPackage::orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'data' => $packages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
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
            if (!isset($validated['slug'])) {
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
            'slug' => 'nullable|string|max:255|unique:ai_gen_packages,slug,' . $packageId,
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
    public function quotas(): JsonResponse
    {
        try {
            $quotas = AiGenQuota::all();

            return response()->json([
                'success' => true,
                'data' => $quotas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update quota.
     */
    public function saveQuota(Request $request, int $quotaId = null): JsonResponse
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
    public function usageLogs(Request $request): JsonResponse
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

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
