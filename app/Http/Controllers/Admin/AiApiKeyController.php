<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiKey;
use App\Models\AiApiKeySetting;
use App\Models\AiApiKeyUsageLog;
use App\Services\AiApiKeyHealthProbeService;
use App\Services\AiApiKeyPoolService;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI API Key Controller
 *
 * จัดการ API Keys สำหรับ AI Providers ในหน้า Admin
 */
class AiApiKeyController extends Controller
{
    protected AiApiKeyPoolService $poolService;

    public function __construct(AiApiKeyPoolService $poolService)
    {
        $this->poolService = $poolService;
    }

    /**
     * หน้า Dashboard แสดงสถานะ API Keys ทั้งหมด
     */
    public function index(): View
    {
        $dashboardData = $this->poolService->getDashboardData();

        return view('admin.ai-api-keys.index', [
            'summary' => $dashboardData['summary'],
            'providers' => $dashboardData['providers'],
            'rotationModes' => $dashboardData['rotation_modes'],
            'allProviders' => AiApiKey::PROVIDERS,
            'pageTitle' => 'จัดการ AI API Keys',
        ]);
    }

    /**
     * แสดง keys ของ provider ที่ระบุ
     */
    public function provider(string $provider): View
    {
        $providerName = AiApiKey::PROVIDERS[$provider] ?? $provider;
        $keys = $this->poolService->getKeysWithStats($provider);
        $settings = AiApiKeySetting::forProvider($provider);
        $stats = $this->poolService->getProviderStats($provider);

        return view('admin.ai-api-keys.provider', [
            'provider' => $provider,
            'providerName' => $providerName,
            'keys' => $keys,
            'settings' => $settings,
            'stats' => $stats,
            'rotationModes' => AiApiKey::ROTATION_MODES,
            'pageTitle' => "จัดการ {$providerName} API Keys",
        ]);
    }

    /**
     * ฟอร์มสร้าง key ใหม่
     */
    public function create(?string $provider = null): View
    {
        return view('admin.ai-api-keys.create', [
            'provider' => $provider,
            'providers' => AiApiKey::PROVIDERS,
            'modelsByProvider' => AiApiKey::MODELS_BY_PROVIDER,
            'defaultBaseUrls' => AiApiKey::DEFAULT_BASE_URLS,
            'pageTitle' => 'เพิ่ม API Key ใหม่',
        ]);
    }

    /**
     * บันทึก key ใหม่
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|string|in:'.implode(',', array_keys(AiApiKey::PROVIDERS)),
            'model' => 'nullable|string|max:100',                  // 🎯 Per-key model
            'base_url' => 'nullable|string|max:255|url',           // 🌐 Per-key base URL
            'api_key' => 'required|string|min:10',
            'priority' => 'nullable|integer|min:0|max:100',
            'purpose' => 'nullable|in:'.implode(',', array_keys(AiApiKey::PURPOSES)),  // 🎯 (2026-05-23) ใช้ PURPOSES const — รวม chat_paid + future-proof
            'tokens_limit_daily' => 'nullable|integer|min:0',
            'tokens_limit_monthly' => 'nullable|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $key = $this->poolService->addKey($validated);
        } catch (QueryException $sqlErr) {
            // 🩹 (2026-05-05) Migration not run — purpose enum ยังไม่มีค่าใหม่
            //    free_card (2026-05-05) / sensitive (2026-05-07) / tts (2026-05-08)
            $needsMigration = ['free_card', 'sensitive', 'tts', 'prediction_deep', 'prediction_celtic', 'chat_paid'];
            if (in_array($validated['purpose'] ?? null, $needsMigration, true)
                && str_contains($sqlErr->getMessage(), 'Data truncated for column')) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Migration ยังไม่รัน — โปรดรัน `php artisan migrate` แล้วลองใหม่ '
                        ."(เพิ่ม '{$validated['purpose']}' ใน enum purpose)",
                ], 422);
            }
            throw $sqlErr;
        }

        // 🛡️ (2026-05-13) Auto-test ทันทีหลัง add — key จะ "ลงสนาม" ก็ต่อเมื่อเทสผ่าน
        //   user spec: "ทุกคีย์ต้องเทสผ่านมาแล้วจึงจะนำมาลงสนาม"
        //   พฤติกรรม:
        //     - test pass → set last_test_passed_at = now() → key ใช้ได้ทันที
        //     - test fail → set last_test_failed_at → key ถูก skip ใน available()
        //                   admin ต้องแก้ key + คลิก "ทดสอบ" ใหม่
        $testResult = ['passed' => false, 'message' => null];
        try {
            $result = $this->testApiKey($key);
            $key->update([
                'last_test_passed_at' => now(),
                'last_test_message' => 'OK ('.($result['response_time_ms'] ?? '-').'ms) — auto-test',
            ]);
            $testResult = ['passed' => true, 'response_time_ms' => $result['response_time_ms'] ?? null];
        } catch (\Throwable $testErr) {
            $key->update([
                'last_test_failed_at' => now(),
                'last_test_message' => mb_substr('Auto-test failed: '.$testErr->getMessage(), 0, 500),
            ]);
            $testResult = ['passed' => false, 'message' => $testErr->getMessage()];
        }

        return response()->json([
            'success' => true,
            'message' => $testResult['passed']
                ? '✅ เพิ่ม API Key สำเร็จ + เทสผ่าน — key พร้อมใช้ใน Pool'
                : '⚠️ เพิ่ม API Key สำเร็จ แต่เทสไม่ผ่าน — key จะไม่ถูกเลือก จนกว่าจะแก้ + ทดสอบใหม่ ('.($testResult['message'] ?? 'unknown error').')',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'provider' => $key->provider,
                'test_passed' => $testResult['passed'],
                'test_message' => $testResult['message'] ?? null,
            ],
        ]);
    }

    /**
     * ฟอร์มแก้ไข key
     */
    public function edit(int $id): View
    {
        $key = AiApiKey::findOrFail($id);

        return view('admin.ai-api-keys.edit', [
            'key' => $key,
            'providers' => AiApiKey::PROVIDERS,
            'modelsByProvider' => AiApiKey::MODELS_BY_PROVIDER,
            'defaultBaseUrls' => AiApiKey::DEFAULT_BASE_URLS,
            'pageTitle' => "แก้ไข {$key->name}",
        ]);
    }

    /**
     * อัพเดท key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',                  // 🎯 Per-key model
            'base_url' => 'nullable|string|max:255|url',           // 🌐 Per-key base URL
            'api_key' => 'nullable|string|min:10',  // nullable เพื่อไม่ต้องส่งถ้าไม่เปลี่ยน
            'priority' => 'nullable|integer|min:0|max:100',
            'purpose' => 'nullable|in:'.implode(',', array_keys(AiApiKey::PURPOSES)),  // 🎯 (2026-05-23) ใช้ PURPOSES const — รวม chat_paid + future-proof
            'tokens_limit_daily' => 'nullable|integer|min:0',
            'tokens_limit_monthly' => 'nullable|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // ลบ api_key ถ้าว่าง (ไม่เปลี่ยน)
        $apiKeyChanged = ! empty($validated['api_key']);
        if (! $apiKeyChanged) {
            unset($validated['api_key']);
        }

        try {
            $key = $this->poolService->updateKey($id, $validated);
        } catch (QueryException $sqlErr) {
            // 🩹 (2026-05-05) Migration not run — purpose enum ยังไม่มีค่าใหม่
            //    free_card (2026-05-05) / sensitive (2026-05-07) / tts (2026-05-08)
            $needsMigration = ['free_card', 'sensitive', 'tts', 'prediction_deep', 'prediction_celtic', 'chat_paid'];
            if (in_array($validated['purpose'] ?? null, $needsMigration, true)
                && str_contains($sqlErr->getMessage(), 'Data truncated for column')) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Migration ยังไม่รัน — โปรดรัน `php artisan migrate` แล้วลองใหม่ '
                        ."(เพิ่ม '{$validated['purpose']}' ใน enum purpose)",
                ], 422);
            }
            throw $sqlErr;
        }

        // 🛡️ (2026-05-13) Re-test เมื่อ api_key เปลี่ยน (กัน key ใหม่ broken)
        //   ถ้าแก้แค่ name/priority/purpose/limit → ไม่ต้อง re-test (เร็วกว่า)
        $testResult = null;
        if ($apiKeyChanged) {
            try {
                $result = $this->testApiKey($key);
                $key->update([
                    'last_test_passed_at' => now(),
                    'last_test_failed_at' => null,
                    'last_test_message' => 'OK ('.($result['response_time_ms'] ?? '-').'ms) — auto-test after update',
                ]);
                $testResult = ['passed' => true];
            } catch (\Throwable $testErr) {
                $key->update([
                    'last_test_failed_at' => now(),
                    'last_test_message' => mb_substr('Auto-test (update) failed: '.$testErr->getMessage(), 0, 500),
                ]);
                $testResult = ['passed' => false, 'message' => $testErr->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => $apiKeyChanged
                ? ($testResult['passed']
                    ? '✅ อัพเดท + เทสผ่าน — key พร้อมใช้'
                    : '⚠️ อัพเดทแล้ว แต่ key ใหม่เทสไม่ผ่าน ('.($testResult['message'] ?? 'unknown').') — กรุณาแก้แล้วทดสอบ')
                : 'อัพเดท API Key สำเร็จ',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    /**
     * ลบ key
     */
    public function destroy(int $id): JsonResponse
    {
        $this->poolService->deleteKey($id);

        return response()->json([
            'success' => true,
            'message' => 'ลบ API Key สำเร็จ',
        ]);
    }

    /**
     * Toggle enable/disable key
     */
    public function toggle(int $id): JsonResponse
    {
        $key = $this->poolService->toggleKey($id);

        return response()->json([
            'success' => true,
            'message' => $key->is_active ? 'เปิดใช้งาน API Key แล้ว' : 'ปิดใช้งาน API Key แล้ว',
            'data' => [
                'id' => $key->id,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    /**
     * Reset error counters
     */
    public function resetErrors(int $id): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);
        $key->update([
            'consecutive_errors' => 0,
            'error_check_attempts' => 0,    // 🩺 reset health-check counter
            'last_error' => null,
            'last_error_at' => null,
            'disabled_until' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ต error counters สำเร็จ',
        ]);
    }

    /**
     * 🔴 (2026-05-01) ปลด critical state — admin ตรวจสอบแล้ว
     */
    public function clearCritical(int $id): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);

        if (! $key->is_critical) {
            return response()->json([
                'success' => false,
                'message' => 'Key นี้ไม่ได้อยู่ใน critical state',
            ], 400);
        }

        $key->clearCritical();

        return response()->json([
            'success' => true,
            'message' => '✅ ปลด critical state สำเร็จ — key พร้อมใช้งานอีกครั้ง',
            'data' => [
                'id' => $key->id,
                'is_active' => $key->is_active,
                'is_critical' => $key->is_critical,
            ],
        ]);
    }

    /**
     * 🩺 (2026-05-07) Manual recheck — admin ลอง probe ทันที (ข้าม backoff)
     *
     * ใช้เมื่อ admin ต้องการ force-probe critical key (เช่น เห็นว่า provider กลับมา)
     * Logic:
     *   - ถ้า key ไม่ใช่ critical → return 400 (ไม่จำเป็น)
     *   - probe ผ่าน AiApiKeyHealthProbeService::recheckCritical
     *     (จะ unban อัตโนมัติถ้า probe ผ่าน + log auto_recovered_at)
     *   - คืนผลลัพธ์ + state ใหม่
     */
    public function recheckNow(int $id, AiApiKeyHealthProbeService $probeService): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);

        if (! $key->is_critical) {
            return response()->json([
                'success' => false,
                'message' => 'Key นี้ไม่ได้อยู่ใน critical state — ไม่จำเป็นต้อง recheck',
            ], 400);
        }

        try {
            $recovered = $probeService->recheckCritical($key);
            $fresh = $key->fresh();

            return response()->json([
                'success' => true,
                'recovered' => $recovered,
                'message' => $recovered
                    ? '🟢 Key recover สำเร็จ! กลับมาใช้งานได้แล้ว'
                    : '⏳ Key ยังใช้ไม่ได้ — schedule recheck ครั้งถัดไป '.($fresh->next_recheck_at?->diffForHumans() ?? 'ไม่ทราบ'),
                'data' => [
                    'id' => $fresh->id,
                    'is_critical' => $fresh->is_critical,
                    'is_active' => $fresh->is_active,
                    'recheck_failure_count' => $fresh->recheck_failure_count,
                    'next_recheck_at' => $fresh->next_recheck_at?->toDateTimeString(),
                    'last_recheck_at' => $fresh->last_recheck_at?->toDateTimeString(),
                    'auto_recovered_at' => $fresh->auto_recovered_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Probe exception: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดท settings ของ provider
     */
    public function updateSettings(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate([
            'rotation_mode' => 'required|string|in:'.implode(',', array_keys(AiApiKey::ROTATION_MODES)),
            'max_consecutive_errors' => 'nullable|integer|min:1|max:10',
            'disable_duration_minutes' => 'nullable|integer|min:1|max:1440',
            'auto_disable_on_limit' => 'nullable|boolean',
            'default_model' => 'nullable|string|max:100',
        ]);

        $settings = $this->poolService->updateProviderSettings($provider, $validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดท settings สำเร็จ',
            'data' => [
                'rotation_mode' => $settings->rotation_mode,
            ],
        ]);
    }

    /**
     * ดึงสถิติแบบ real-time (สำหรับ AJAX refresh)
     */
    public function stats(?string $provider = null): JsonResponse
    {
        if ($provider) {
            $stats = $this->poolService->getProviderStats($provider);
            $keys = $this->poolService->getKeysWithStats($provider);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'keys' => $keys,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->poolService->getDashboardData(),
        ]);
    }

    /**
     * ดึง usage logs
     */
    public function logs(Request $request, ?int $keyId = null): View
    {
        $query = AiApiKeyUsageLog::with('apiKey')
            ->orderByDesc('created_at');

        if ($keyId) {
            $query->where('ai_api_key_id', $keyId);
        }

        if ($request->filled('provider')) {
            $query->whereHas('apiKey', function ($q) use ($request) {
                $q->where('provider', $request->provider);
            });
        }

        if ($request->filled('success')) {
            $query->where('is_success', $request->success === 'true');
        }

        $logs = $query->paginate(50);

        return view('admin.ai-api-keys.logs', [
            'logs' => $logs,
            'keyId' => $keyId,
            'providers' => AiApiKey::PROVIDERS,
            'pageTitle' => 'Usage Logs',
        ]);
    }

    /**
     * 🆕 (2026-05-13) แดชบอร์ดการใช้ AI ทั้งหมด — กราฟเส้นย้อนหลังตามคีย์
     *
     * Features:
     *   - กราฟเส้นแสดง token usage ต่อวัน (filter ตามคีย์)
     *   - Date range picker (default 7 วัน)
     *   - Multi-select key filter
     *   - Summary cards: total tokens, total requests, failed rate
     */
    public function usageDashboard(): View
    {
        $allKeys = AiApiKey::orderBy('provider')->orderBy('name')->get(['id', 'provider', 'name', 'model', 'purpose']);

        return view('admin.ai-api-keys.usage-dashboard', [
            'allKeys' => $allKeys,
            'providers' => AiApiKey::PROVIDERS,
            'pageTitle' => 'AI Usage Dashboard',
        ]);
    }

    /**
     * 🆕 (2026-05-13) JSON endpoint สำหรับ Chart.js
     *
     * Query params:
     *   - start: Y-m-d (default 7 วันก่อน)
     *   - end: Y-m-d (default วันนี้)
     *   - key_ids[]: array of key IDs (optional, default = all)
     *
     * Response:
     *   - labels: ['2026-05-07', '2026-05-08', ...]
     *   - datasets: [{label: 'gemini/thai111', data: [1234, 5678, ...]}, ...]
     *   - summary: {total_tokens, total_requests, failed_count, success_rate}
     *   - keys_breakdown: [{key_id, name, total_tokens, requests, failed}, ...]
     */
    public function usageDashboardData(Request $request): JsonResponse
    {
        $start = $request->input('start')
            ? \Carbon\Carbon::parse($request->input('start'))->startOfDay()
            : now()->subDays(6)->startOfDay();
        $end = $request->input('end')
            ? \Carbon\Carbon::parse($request->input('end'))->endOfDay()
            : now()->endOfDay();
        $keyIds = $request->input('key_ids', []);
        if (! is_array($keyIds)) {
            $keyIds = explode(',', (string) $keyIds);
        }
        $keyIds = array_filter(array_map('intval', $keyIds));

        // ดึง keys ที่จะ plot
        $keysQuery = AiApiKey::query();
        if (! empty($keyIds)) {
            $keysQuery->whereIn('id', $keyIds);
        }
        $keys = $keysQuery->get(['id', 'provider', 'name', 'model']);

        // สร้าง labels (รายวัน)
        $labels = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $labels[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        // ดึง usage logs aggregate ต่อวันต่อ key
        $logsQuery = AiApiKeyUsageLog::query()
            ->selectRaw('ai_api_key_id, DATE(created_at) as day, SUM(total_tokens) as tokens, COUNT(*) as requests, SUM(CASE WHEN is_success = 0 THEN 1 ELSE 0 END) as failed')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('ai_api_key_id', 'day');

        if (! empty($keyIds)) {
            $logsQuery->whereIn('ai_api_key_id', $keyIds);
        }

        $logs = $logsQuery->get();

        // index จาก [key_id][day] → tokens
        $byKey = [];
        foreach ($logs as $log) {
            $byKey[$log->ai_api_key_id][$log->day] = (int) $log->tokens;
        }

        // สร้าง datasets สำหรับ Chart.js
        $colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#f97316', '#84cc16', '#3b82f6'];
        $datasets = [];
        $i = 0;
        foreach ($keys as $key) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = $byKey[$key->id][$label] ?? 0;
            }
            $datasets[] = [
                'label' => "{$key->provider}/{$key->name}".($key->model ? " ({$key->model})" : ''),
                'data' => $data,
                'borderColor' => $colors[$i % count($colors)],
                'backgroundColor' => $colors[$i % count($colors)].'33',
                'tension' => 0.3,
                'fill' => false,
            ];
            $i++;
        }

        // Summary + breakdown
        $totalQuery = AiApiKeyUsageLog::query()
            ->whereBetween('created_at', [$start, $end]);
        if (! empty($keyIds)) {
            $totalQuery->whereIn('ai_api_key_id', $keyIds);
        }
        $totalTokens = (int) $totalQuery->sum('total_tokens');
        $totalRequests = (int) $totalQuery->count();
        $failedCount = (int) (clone $totalQuery)->where('is_success', false)->count();
        $successRate = $totalRequests > 0 ? round((($totalRequests - $failedCount) / $totalRequests) * 100, 1) : 100;

        // Breakdown per key
        $breakdownQuery = AiApiKeyUsageLog::query()
            ->selectRaw('ai_api_key_id, SUM(total_tokens) as tokens, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, COUNT(*) as requests, SUM(CASE WHEN is_success = 0 THEN 1 ELSE 0 END) as failed')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('ai_api_key_id')
            ->orderByDesc('tokens');
        if (! empty($keyIds)) {
            $breakdownQuery->whereIn('ai_api_key_id', $keyIds);
        }
        $breakdownRaw = $breakdownQuery->get();
        $keysIndex = AiApiKey::whereIn('id', $breakdownRaw->pluck('ai_api_key_id'))
            ->get(['id', 'provider', 'name', 'model', 'purpose'])
            ->keyBy('id');

        $breakdown = $breakdownRaw->map(function ($row) use ($keysIndex) {
            $key = $keysIndex->get($row->ai_api_key_id);

            return [
                'key_id' => $row->ai_api_key_id,
                'name' => $key ? "{$key->provider}/{$key->name}" : "Key #{$row->ai_api_key_id}",
                'model' => $key->model ?? '-',
                // 🚫 (2026-05-23) ลบ default 'any' — null = ไม่ระบุ
                'purpose' => $key->purpose ?? null,
                'total_tokens' => (int) $row->tokens,
                'input_tokens' => (int) $row->input_tokens,
                'output_tokens' => (int) $row->output_tokens,
                'requests' => (int) $row->requests,
                'failed' => (int) $row->failed,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'chart' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
            'summary' => [
                'total_tokens' => $totalTokens,
                'total_requests' => $totalRequests,
                'failed_count' => $failedCount,
                'success_rate' => $successRate,
                'date_range' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                    'days' => count($labels),
                ],
            ],
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * ทดสอบ API Key
     *
     * 🛡️ (2026-05-13) บันทึก timestamp ลง DB ตามผลลัพธ์
     *   - pass → set last_test_passed_at = now() → scopeAvailable() จะเลือก key นี้ได้
     *   - fail → set last_test_failed_at + last_test_message (debug)
     */
    public function test(int $id): JsonResponse
    {
        $key = AiApiKey::findOrFail($id);

        try {
            // ทดสอบตาม provider
            $result = $this->testApiKey($key);

            // ✅ บันทึก pass — key จะถูก "ลงสนาม" ใน scopeAvailable
            $key->update([
                'last_test_passed_at' => now(),
                'last_test_message' => 'OK ('.($result['response_time_ms'] ?? '-').'ms)',
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ API Key ใช้งานได้ปกติ — key พร้อมใช้ใน Pool',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            // ❌ บันทึก fail — key จะถูก skip ใน scopeAvailable
            try {
                $key->update([
                    'last_test_failed_at' => now(),
                    'last_test_message' => mb_substr($e->getMessage(), 0, 500),
                ]);
            } catch (\Throwable $logErr) {
                // ignore — เก็บใน response ก็พอ
            }

            return response()->json([
                'success' => false,
                'message' => '❌ API Key ไม่สามารถใช้งานได้: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * ทดสอบ API Key ตาม provider
     */
    protected function testApiKey(AiApiKey $key): array
    {
        $startTime = microtime(true);

        // ทดสอบตาม provider
        switch ($key->provider) {
            case 'grok':
                $response = $this->testGrokApi($key->api_key);
                break;
            case 'groq':
                $response = $this->testGroqApi($key->api_key);
                break;
            case 'openai':
                $response = $this->testOpenAiApi($key->api_key);
                break;
            case 'gemini':
                $response = $this->testGeminiApi($key->api_key);
                break;
            default:
                throw new \Exception('Provider ไม่รองรับการทดสอบ');
        }

        $responseTime = round((microtime(true) - $startTime) * 1000);

        return [
            'provider' => $key->provider,
            'response_time_ms' => $responseTime,
            'status' => 'ok',
        ];
    }

    /**
     * ทดสอบ Grok API
     */
    protected function testGrokApi(string $apiKey): array
    {
        $client = new Client;
        $response = $client->get('https://api.x.ai/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ Groq API
     */
    protected function testGroqApi(string $apiKey): array
    {
        $client = new Client;
        $response = $client->get('https://api.groq.com/openai/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ OpenAI API
     */
    protected function testOpenAiApi(string $apiKey): array
    {
        $client = new Client;
        $response = $client->get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * ทดสอบ Gemini API
     */
    protected function testGeminiApi(string $apiKey): array
    {
        $client = new Client;
        $response = $client->get("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}", [
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }
}
