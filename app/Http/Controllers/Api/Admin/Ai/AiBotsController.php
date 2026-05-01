<?php

namespace App\Http\Controllers\Api\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Ai\AiBotResource;
use App\Models\AiBotProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Mobile API: AI Bots
 *
 * จัดการ AI bot profiles (Tarot Reader, Content Writer, Customer Support, Image Generator ฯลฯ)
 */
class AiBotsController extends Controller
{
    /**
     * GET /api/admin/ai/bots
     *
     * Query: ?search=&active=&owner_id=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $query = AiBotProfile::query()->with(['provider', 'model']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', (int) $request->input('owner_id'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $bots = $query->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AiBotResource::collection($bots)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/ai/bots/{bot}
     */
    public function show(AiBotProfile $bot): JsonResponse
    {
        $bot->load(['provider', 'model']);

        return response()->json([
            'success' => true,
            'data' => new AiBotResource($bot),
        ]);
    }

    /**
     * POST /api/admin/ai/bots/{bot}/toggle
     */
    public function toggle(AiBotProfile $bot, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManage($admin)) {
            return $this->forbidden();
        }

        try {
            $bot->update(['is_active' => ! $bot->is_active]);

            Log::info('Admin API: bot toggled', [
                'bot_id' => $bot->id,
                'new_state' => $bot->is_active,
                'admin_id' => $admin->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => new AiBotResource($bot->fresh(['provider', 'model'])),
                'message' => $bot->is_active ? 'เปิดใช้งาน bot แล้ว' : 'ปิดใช้งาน bot แล้ว',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/ai/bots/{bot}/test
     *
     * Body: { message: string }
     * ทดสอบส่งข้อความและรับ response กลับมา
     */
    public function test(AiBotProfile $bot, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManage($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Delegate to existing AiBotConversationService (ถ้ามี)
        // ในเวอร์ชันแรก return mock เพื่อให้ pipeline พร้อม
        return response()->json([
            'success' => true,
            'data' => [
                'bot_id' => $bot->id,
                'request_message' => $request->input('message'),
                'response_message' => '(stub) Bot test integration WIP — see AiBotController@test for full impl',
                'tested_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function canManage($admin): bool
    {
        if ($admin->is_super_admin ?? false) {
            return true;
        }
        if (method_exists($admin, 'hasPermission')) {
            return $admin->hasPermission('manage_ai_bots');
        }

        return ($admin->role ?? null) === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์จัดการ AI bots',
            'error_code' => 'PERMISSION_DENIED',
        ], 403);
    }
}
