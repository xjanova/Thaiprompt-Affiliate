<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Admin\AiApiKeyController as WebAiApiKeyController;
use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\AiApiKeySetting;
use App\Services\AiApiKeyPoolService;
use App\Support\SafeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: คลังคีย์ AI ของบอทแม่หมอ (ตาราง ai_api_keys) — แอปไทยพร้อม แอดมิน
 *
 * ใช้ service/รูทีนตัวเดียวกับหน้าเว็บ admin/ai-api-keys:
 *   - เปิด/ปิด  → AiApiKeyPoolService::toggleKey()
 *   - ทดสอบ    → Admin\AiApiKeyController::test() (ตั้ง last_test_passed_at / last_test_failed_at เหมือนกดบนเว็บ)
 *   - โหมด     → AiApiKeyPoolService::updateProviderSettings() (โหมดราย provider)
 *
 * 🔐 ไม่ส่งคีย์จริงออกไปเด็ดขาด — key_masked = 4 ตัวท้ายเท่านั้น · ข้อความ error ผ่าน SafeLog ก่อนส่ง
 * 🔐 ทางเขียนทุกตัว: super admin หรือสิทธิ์ manage_api_keys
 */
class AiPoolController extends Controller
{
    public function __construct(
        private readonly AiApiKeyPoolService $poolService,
    ) {}

    /**
     * GET /api/admin/fortune/ai-pool
     */
    public function index(Request $request): JsonResponse
    {
        $keys = AiApiKey::query()
            ->orderBy('provider')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $usage = $this->usageToday($keys->pluck('id')->all());
        $presented = $keys->map(fn (AiApiKey $k) => $this->presentKey($k, $usage[(int) $k->id] ?? null))->values();

        $providerModes = AiApiKeySetting::query()
            ->whereIn('provider', $keys->pluck('provider')->unique()->values()->all())
            ->pluck('rotation_mode', 'provider')
            ->all();

        $providers = $presented->groupBy('provider')->map(fn (Collection $group, string $provider) => [
            'provider' => $provider,
            'name' => AiApiKey::PROVIDERS[$provider] ?? $provider,
            // ยังไม่เคยตั้ง = ค่าเริ่มต้นของ AiApiKeySetting (round_robin) — อ่านอย่างเดียว ไม่สร้างแถวใหม่
            'rotation_mode' => $providerModes[$provider] ?? 'round_robin',
            'keys_total' => $group->count(),
            'keys_healthy' => $group->where('healthy', true)->count(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'global_mode' => self::globalMode(),
                // โหมดรวมข้าม provider ตั้งจาก env AI_CROSS_PROVIDER_ROTATION (config/ai.php — ตั้งใจไม่เก็บใน DB)
                'global_mode_source' => 'env',
                'global_mode_writable' => false,
                'modes' => collect(AiApiKey::ROTATION_MODES)
                    ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                    ->values(),
                'providers' => $providers,
                'summary' => [
                    'total' => $presented->count(),
                    'healthy' => $presented->where('healthy', true)->count(),
                    'active' => $presented->where('is_active', true)->count(),
                ],
                'keys' => $presented,
                'can_manage' => self::canManage($request->user()),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/admin/fortune/ai-pool/keys/{key}/toggle
     */
    public function toggle(Request $request, AiApiKey $key): JsonResponse
    {
        if (! self::canManage($request->user())) {
            return $this->forbidden();
        }

        $fresh = $this->poolService->toggleKey((int) $key->id);

        Log::info('Admin API: AI key toggled', [
            'key_id' => $fresh->id,
            'provider' => $fresh->provider,
            'is_active' => $fresh->is_active,
            'admin_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['key' => $this->presentKey($fresh, $this->usageToday([(int) $fresh->id])[(int) $fresh->id] ?? null)],
            'message' => $fresh->is_active ? 'เปิดใช้งาน API Key แล้ว' : 'ปิดใช้งาน API Key แล้ว',
        ]);
    }

    /**
     * POST /api/admin/fortune/ai-pool/keys/{key}/test
     *
     * เรียกรูทีนทดสอบของหน้าเว็บตรง ๆ — ผลลัพธ์ (last_test_*) จึงเหมือนกดปุ่ม "ทดสอบ" บนเว็บทุกประการ
     */
    public function test(Request $request, AiApiKey $key, WebAiApiKeyController $web): JsonResponse
    {
        if (! self::canManage($request->user())) {
            return $this->forbidden();
        }

        try {
            $result = $web->test((int) $key->id)->getData(true);
            $passed = (bool) ($result['success'] ?? false);
            $message = (string) ($result['message'] ?? '');
            $detail = is_array($result['data'] ?? null) ? $result['data'] : [];
        } catch (\Throwable $e) {
            // รูทีนเว็บดักแค่ \Exception — Error อื่นมาตกที่นี่ (ข้อความผ่าน SafeLog กันคีย์หลุด)
            $passed = false;
            $message = '❌ API Key ไม่สามารถใช้งานได้: '.SafeLog::exceptionMessage($e);
            $detail = [];
        }

        $fresh = $key->fresh();

        Log::info('Admin API: AI key tested', [
            'key_id' => $key->id,
            'provider' => $key->provider,
            'passed' => $passed,
            'admin_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => $passed,
            'data' => [
                'passed' => $passed,
                'response_time_ms' => $detail['response_time_ms'] ?? null,
                'model' => $detail['model'] ?? null,
                'model_known' => $detail['model_known'] ?? null,
                'model_warning' => isset($detail['model_warning']) ? $this->scrub((string) $detail['model_warning'], $fresh) : null,
                'key' => $this->presentKey($fresh, $this->usageToday([(int) $fresh->id])[(int) $fresh->id] ?? null),
            ],
            'message' => $this->scrub($message, $fresh),
        ]);
    }

    /**
     * POST /api/admin/fortune/ai-pool/mode   body {provider, mode}
     *
     * ตั้งโหมดวนคีย์ "ราย provider" (ชุดเดียวกับหน้าเว็บ ai-api-keys/provider/{provider}/settings)
     * โหมดรวม (provider '*') มาจาก env เท่านั้น — เขียนลง DB ก็ไม่มีผล (config ชนะเสมอ) จึงปฏิเสธ
     */
    public function mode(Request $request): JsonResponse
    {
        if (! self::canManage($request->user())) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'provider' => 'nullable|string|max:32',
            'mode' => 'required|string|in:'.implode(',', array_keys(AiApiKey::ROTATION_MODES)),
        ]);

        $provider = trim((string) ($data['provider'] ?? ''));
        if ($provider === '' || $provider === '*') {
            return response()->json([
                'success' => false,
                'message' => 'โหมดรวมของทุก provider ตั้งจาก env AI_CROSS_PROVIDER_ROTATION เท่านั้น — เลือก provider ที่จะตั้งโหมด',
                'error_code' => 'GLOBAL_MODE_ENV_ONLY',
            ], 422);
        }

        if (! array_key_exists($provider, AiApiKey::PROVIDERS)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่รู้จัก provider นี้',
                'errors' => ['provider' => ['ไม่รู้จัก provider นี้']],
            ], 422);
        }

        $settings = $this->poolService->updateProviderSettings($provider, ['rotation_mode' => $data['mode']]);

        Log::info('Admin API: AI provider rotation mode changed', [
            'provider' => $provider,
            'mode' => $settings->rotation_mode,
            'admin_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['provider' => $provider, 'rotation_mode' => $settings->rotation_mode],
            'message' => "ตั้งโหมดของ {$provider} แล้ว",
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // ภายใน
    // ────────────────────────────────────────────────────────────

    /**
     * โหมดรวมที่ AiApiKeyPoolService ใช้จริง (ลำดับเดียวกับในบริการ)
     */
    public static function globalMode(): string
    {
        $mode = config('ai.cross_provider_rotation_mode');
        if (is_string($mode) && $mode !== '') {
            return $mode;
        }

        return (string) (AiApiKeySetting::query()->where('provider', '*')->value('rotation_mode') ?? 'smart');
    }

    /**
     * สิทธิ์เขียนคลังคีย์ — super admin หรือสิทธิ์ manage_api_keys (แพตเทิร์นเดียวกับ Ai\AiProvidersController)
     */
    public static function canManage($user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->is_super_admin ?? false) {
            return true;
        }

        return method_exists($user, 'hasPermission') && $user->hasPermission('manage_api_keys');
    }

    /**
     * คีย์หนึ่งตัว — ไม่มีค่าคีย์จริงในผลลัพธ์
     *
     * @param  array{requests: int, tokens: int, errors: int}|null  $usage
     * @return array<string, mixed>
     */
    private function presentKey(AiApiKey $k, ?array $usage): array
    {
        $purpose = $k->purpose !== null && $k->purpose !== '' ? (string) $k->purpose : null;

        return [
            'id' => (int) $k->id,
            'provider' => $k->provider,
            'provider_name' => AiApiKey::PROVIDERS[$k->provider] ?? $k->provider,
            'label' => $k->name,
            'model' => $k->resolveModel(),
            'priority' => (int) $k->priority,
            'purpose' => $purpose,
            'purposes' => $purpose !== null ? [$purpose] : [],
            'purpose_label' => $purpose !== null ? (AiApiKey::PURPOSES[$purpose] ?? $purpose) : null,
            'is_active' => (bool) $k->is_active,
            'is_critical' => (bool) ($k->is_critical ?? false),
            'healthy' => self::isHealthy($k),
            'disabled_until' => $k->disabled_until?->toIso8601String(),
            'last_test_passed_at' => $k->last_test_passed_at?->toIso8601String(),
            'last_test_failed_at' => $k->last_test_failed_at?->toIso8601String(),
            'last_test_message' => $k->last_test_message !== null ? $this->scrub((string) $k->last_test_message, $k) : null,
            'last_error' => $k->last_error !== null ? $this->scrub((string) $k->last_error, $k) : null,
            'last_error_at' => $k->last_error_at?->toIso8601String(),
            'consecutive_errors' => (int) ($k->consecutive_errors ?? 0),
            'last_used_at' => $k->last_used_at?->toIso8601String(),
            'usage_today' => $usage ?? ['requests' => 0, 'tokens' => 0, 'errors' => 0],
            'key_masked' => self::maskKey($k),
        ];
    }

    /**
     * พร้อมลงสนามไหม — เงื่อนไขเดียวกับ AiApiKey::scopeAvailable()
     */
    public static function isHealthy(AiApiKey $k): bool
    {
        return (bool) $k->is_active
            && ! (bool) ($k->is_critical ?? false)
            && ($k->disabled_until === null || $k->disabled_until->lessThanOrEqualTo(now()))
            && $k->last_test_passed_at !== null;
    }

    /**
     * 4 ตัวท้ายของคีย์เท่านั้น
     */
    public static function maskKey(AiApiKey $k): string
    {
        try {
            $raw = (string) $k->api_key;
        } catch (\Throwable $e) {
            $raw = '';
        }

        return mb_strlen($raw) >= 12 ? '••••'.mb_substr($raw, -4) : '••••';
    }

    /**
     * ปิดความลับในข้อความ — ทั้งรูปแบบที่ SafeLog รู้จัก และค่าคีย์ของตัวนี้เอง
     */
    private function scrub(string $text, ?AiApiKey $k): string
    {
        $text = SafeLog::redactSecrets($text);

        if ($k !== null) {
            try {
                $raw = (string) $k->api_key;
                if (mb_strlen($raw) >= 8) {
                    $text = str_replace($raw, '••••'.mb_substr($raw, -4), $text);
                }
            } catch (\Throwable $e) {
                // ถอดรหัสไม่ได้ = ไม่มีค่าให้ซ่อน
            }
        }

        return $text;
    }

    /**
     * การเรียก AI วันนี้ต่อคีย์ จาก ai_api_key_usage_logs (log ทุกครั้งที่บอทเรียก AI)
     *
     * @param  array<int, int>  $ids
     * @return array<int, array{requests: int, tokens: int, errors: int}>
     */
    private function usageToday(array $ids): array
    {
        if ($ids === [] || ! Schema::hasTable('ai_api_key_usage_logs')) {
            return [];
        }

        $out = [];
        DB::table('ai_api_key_usage_logs')
            ->whereIn('ai_api_key_id', $ids)
            ->where('created_at', '>=', now()->startOfDay())
            ->selectRaw('ai_api_key_id, COUNT(*) AS req, COALESCE(SUM(total_tokens), 0) AS tok,'
                .' COALESCE(SUM(CASE WHEN is_success = 0 THEN 1 ELSE 0 END), 0) AS err')
            ->groupBy('ai_api_key_id')
            ->get()
            ->each(function ($row) use (&$out) {
                $out[(int) $row->ai_api_key_id] = [
                    'requests' => (int) $row->req,
                    'tokens' => (int) $row->tok,
                    'errors' => (int) $row->err,
                ];
            });

        return $out;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์จัดการคีย์ AI (ต้องเป็น super admin หรือมีสิทธิ์ manage_api_keys)',
            'error_code' => 'PERMISSION_DENIED',
        ], 403);
    }
}
