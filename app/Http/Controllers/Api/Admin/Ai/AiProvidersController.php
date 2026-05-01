<?php

namespace App\Http\Controllers\Api\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Ai\AiProviderResource;
use App\Models\AiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Mobile API: AI Providers
 *
 * จัดการ AI provider list (OpenAI, Anthropic, Google, Local Llama ฯลฯ)
 */
class AiProvidersController extends Controller
{
    /**
     * GET /api/admin/ai/providers
     */
    public function index(Request $request): JsonResponse
    {
        $providers = AiProvider::query()
            ->orderByDesc('is_active')
            ->orderBy('display_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AiProviderResource::collection($providers),
        ]);
    }

    /**
     * GET /api/admin/ai/providers/{provider}
     */
    public function show(AiProvider $provider): JsonResponse
    {
        $provider->load('models');

        return response()->json([
            'success' => true,
            'data' => new AiProviderResource($provider),
        ]);
    }

    /**
     * POST /api/admin/ai/providers/{provider}/toggle
     *
     * Toggle is_active state
     */
    public function toggle(AiProvider $provider, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManage($admin)) {
            return $this->forbidden();
        }

        try {
            $provider->update([
                'is_active' => ! $provider->is_active,
            ]);

            Log::info('Admin API: provider toggled', [
                'provider_id' => $provider->id,
                'new_state' => $provider->is_active,
                'admin_id' => $admin->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => new AiProviderResource($provider->fresh()),
                'message' => $provider->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/ai/providers/{provider}/test-connection
     *
     * ทดสอบเชื่อมต่อ provider (ตรวจ API key, endpoint)
     */
    public function testConnection(AiProvider $provider, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManage($admin)) {
            return $this->forbidden();
        }

        // Note: ในเวอร์ชันแรก return mock response
        // ในอนาคตจะ delegate ให้ AiProviderConnectionTester service
        // ที่อาจจะ call provider's models endpoint
        return response()->json([
            'success' => true,
            'data' => [
                'provider_id' => $provider->id,
                'test_at' => now()->toIso8601String(),
                'reachable' => (bool) $provider->is_available,
                'message' => 'Connection test feature in progress',
            ],
        ]);
    }

    private function canManage($admin): bool
    {
        if ($admin->is_super_admin ?? false) {
            return true;
        }
        if (method_exists($admin, 'hasPermission')) {
            return $admin->hasPermission('manage_ai_providers');
        }

        return ($admin->role ?? null) === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์จัดการ AI providers',
            'error_code' => 'PERMISSION_DENIED',
        ], 403);
    }
}
