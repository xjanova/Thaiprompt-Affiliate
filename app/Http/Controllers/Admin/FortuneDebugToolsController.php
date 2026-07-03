<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FortuneAIService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 🐛 (2026-05-17) Fortune Debug Tools — Self-service debugging for admin
 *
 * แก้ปัญหา blind debug — ก่อนหน้านี้ต้อง SSH เข้า server เพื่อดู Laravel log
 * ตอนนี้: admin เปิดหน้า /admin/fortune/debug-tools → tail log + ทดสอบ AI sync ในเบราว์เซอร์
 *
 * Features:
 *   1. Tail laravel.log แบบ realtime (auto-poll 3s) — กรองด้วย keyword ได้
 *   2. ทดสอบ AI ทำนาย (sync) — เลือก reading + พิมพ์คำถาม → เรียก AI ตรงๆ → แสดง response/error
 *   3. ทดสอบ push LINE/FB — ส่ง test message ไปยังลูกค้าจริง
 */
class FortuneDebugToolsController extends Controller
{
    /**
     * แสดงหน้า Debug Tools
     */
    public function index(Request $request)
    {
        // หา readings ล่าสุดของ Celtic + Deep — เพื่อ dropdown ทดสอบ
        $recentReadings = FortuneReading::latest()
            ->limit(20)
            ->get(['id', 'reading_type', 'conversation_status', 'platform', 'facebook_user_name', 'bill_reference', 'is_paid', 'created_at']);

        return view('admin.fortune.debug-tools.index', [
            'pageTitle' => '🐛 Fortune Debug Tools',
            'recentReadings' => $recentReadings,
            'logPath' => $this->getLogPath(),
        ]);
    }

    /**
     * AJAX endpoint — tail laravel.log แบบ realtime
     *
     * Query params:
     *   - lines: จำนวนบรรทัดสุดท้าย (default 100, max 500)
     *   - filter: keyword filter (regex, case-insensitive)
     */
    public function tailLog(Request $request): JsonResponse
    {
        $lines = min(500, max(10, (int) $request->query('lines', 100)));
        $filter = trim((string) $request->query('filter', ''));

        $path = $this->getLogPath();

        if (! File::exists($path)) {
            return response()->json([
                'success' => false,
                'message' => "log file not found: {$path}",
                'lines' => [],
            ]);
        }

        try {
            // อ่านท้ายไฟล์เท่าที่ต้องการ
            $output = $this->tailFile($path, $lines);

            // Filter ถ้ามี
            if ($filter !== '') {
                $output = array_values(array_filter($output, function ($line) use ($filter) {
                    return @preg_match('/'.$filter.'/i', $line)
                        ? true
                        : stripos($line, $filter) !== false;
                }));
            }

            return response()->json([
                'success' => true,
                'count' => count($output),
                'size_bytes' => File::size($path),
                'lines' => $output,
                'fetched_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'lines' => [],
            ]);
        }
    }

    /**
     * AJAX endpoint — ทดสอบ AI ทำนาย (sync)
     *
     * Body:
     *   - reading_id: int
     *   - question: string
     *   - push_to_customer: bool (default false) — ถ้า true จะ push คำตอบให้ลูกค้าจริง
     */
    public function testAi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reading_id' => 'required|integer|exists:fortune_readings,id',
            'question' => 'required|string|min:3|max:500',
            'push_to_customer' => 'sometimes|boolean',
        ]);

        $reading = FortuneReading::findOrFail($validated['reading_id']);
        $pushToCustomer = (bool) ($validated['push_to_customer'] ?? false);

        $result = [
            'success' => false,
            'reading_id' => $reading->id,
            'reading_type' => $reading->reading_type,
            'is_paid' => $reading->is_paid,
            'cards_picked' => $reading->getCelticPickedCount(),
            'steps' => [],
        ];

        try {
            // Step 1: ตรวจ pool key
            $result['steps'][] = $this->step('1. ตรวจ AI Pool', function () {
                $pool = new \App\Services\AiApiKeyPoolService;
                $celticKey = $pool->acquireKey('openai', 'prediction_celtic');
                if (! $celticKey) {
                    $celticKey = $pool->acquireKey('openai', 'prediction');
                }

                return [
                    'has_openai_celtic_key' => $celticKey !== null,
                    'key_provider' => $celticKey?->provider,
                    'key_model' => $celticKey?->resolveModel(),
                ];
            });

            // Step 2: เรียก AI sync (ใช้ method askQuestion ของ service)
            $startTime = microtime(true);
            $service = new CelticCrossService(FortuneTellingSetting::getSettings());

            // ใช้ generateWithRetryAndFallback ตรงๆ — bypass quota check
            $cards = $reading->getCelticCards();
            if (count($cards) < 10) {
                $result['steps'][] = [
                    'name' => '2. AI call',
                    'success' => false,
                    'error' => "Cards เปิดยังไม่ครบ 10 ใบ (มี {$reading->getCelticPickedCount()})",
                ];
                throw new \Exception('Cards not picked');
            }

            $prompt = $this->buildTestPrompt($reading, $validated['question'], $cards);
            $aiService = new FortuneAIService(FortuneTellingSetting::getSettings(), 'prediction_celtic', 'openai');

            $aiResult = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: "debug_test:{$reading->id}",
                purpose: 'prediction_celtic',
            );

            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            $result['steps'][] = [
                'name' => '2. AI call (sync)',
                'success' => ! empty($aiResult['response']),
                'elapsed_ms' => $elapsed,
                'provider' => $aiResult['provider'] ?? null,
                'model' => $aiResult['model'] ?? null,
                'tokens_used' => $aiResult['tokens_used'] ?? null,
                'response_len' => mb_strlen($aiResult['response'] ?? ''),
                'response_preview' => mb_substr($aiResult['response'] ?? '', 0, 300),
            ];

            $result['ai_response_full'] = $aiResult['response'] ?? '';

            // Step 3: Push ลูกค้า (ถ้าเลือก)
            if ($pushToCustomer && ! empty($aiResult['response'])) {
                $result['steps'][] = $this->step('3. Push to customer', function () use ($reading, $aiResult) {
                    $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
                    if (empty($userId)) {
                        throw new \Exception('reading ไม่มี user_id');
                    }
                    $platform = $reading->platform
                        ?? (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

                    $channelManager = new FortuneChannelManager(FortuneTellingSetting::getSettings());
                    $sent = $channelManager->sendResponse($platform, (string) $userId, [
                        'action' => 'celtic_question_answered',
                        'message' => '🧪 [DEBUG TEST] '.$aiResult['response'],
                        'reading' => $reading,
                    ], [
                        'from_admin' => true,
                        'message_tag' => 'POST_PURCHASE_UPDATE',
                    ]);

                    return [
                        'platform' => $platform,
                        'user_id' => $userId,
                        'sent' => $sent,
                    ];
                });
            } else {
                $result['steps'][] = [
                    'name' => '3. Push to customer',
                    'success' => true,
                    'skipped' => true,
                    'reason' => $pushToCustomer ? 'no AI response' : 'push_to_customer=false',
                ];
            }

            $result['success'] = true;

            Log::info('🐛 Debug Test AI สำเร็จ', [
                'reading_id' => $reading->id,
                'elapsed_ms' => $elapsed,
                'pushed' => $pushToCustomer,
            ]);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['trace'] = collect(explode("\n", $e->getTraceAsString()))->take(8)->implode("\n");

            Log::error('🐛 Debug Test AI exception', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($result);
    }

    /**
     * 🔀 (2026-07-03) AJAX — โหลดสถานะบิล (preview ก่อนสลับแพ็กเกจ)
     *
     * Query: bill = bill_reference (FTU-...) หรือ reading_id (ตัวเลข)
     */
    public function billInfo(Request $request): JsonResponse
    {
        $bill = trim((string) $request->query('bill', ''));
        if ($bill === '') {
            return response()->json(['success' => false, 'message' => 'กรุณาระบุเลขบิลหรือ reading id']);
        }

        $reading = $this->resolveReading($bill);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => "ไม่พบบิล: {$bill}"]);
        }

        // เงินที่รับจริง (paid → amount_paid, ค้าง → partial_paid_total)
        $moneyIn = $reading->is_paid
            ? (float) ($reading->amount_paid ?? 0)
            : (float) ($reading->partial_paid_total ?? 0);
        $moneyIn = max($moneyIn, (float) ($reading->partial_paid_total ?? 0), (float) ($reading->amount_received ?? 0));

        return response()->json([
            'success' => true,
            'reading' => [
                'id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'reading_type' => $reading->reading_type,
                'conversation_status' => $reading->conversation_status,
                'is_paid' => (bool) $reading->is_paid,
                'amount_paid' => (float) ($reading->amount_paid ?? 0),
                'partial_paid_total' => (float) ($reading->partial_paid_total ?? 0),
                'money_in' => $moneyIn,
                'has_birth_date' => ! empty($reading->birth_date),
                'celtic_cards' => $reading->getCelticPickedCount(),
                'has_deep_response' => ! empty($reading->deep_response),
                'facebook_user_name' => $reading->facebook_user_name,
                'platform' => $reading->platform,
                'created_at' => $reading->created_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * 🔀 (2026-07-03) AJAX — สลับแพ็กเกจบิล Deep 39 ↔ Celtic 99
     *
     * Body:
     *   - bill: bill_reference หรือ reading_id
     *   - target: 'deep' | 'celtic_cross'
     *   - pay_mode: 'charge' (ออกบิลส่วนต่าง) | 'free' (อัปเกรดฟรี)
     *   - force: bool (ยืนยันแม้บิลมีความคืบหน้า)
     *
     * ⚠️ SILENT — เปลี่ยน DB เท่านั้น ไม่ push หาลูกค้า (owner แจ้งเอง)
     */
    public function switchPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill' => 'required|string|max:64',
            'target' => 'required|in:deep,celtic_cross',
            'pay_mode' => 'required|in:charge,free',
            'force' => 'sometimes|boolean',
        ]);

        $reading = $this->resolveReading($validated['bill']);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => "ไม่พบบิล: {$validated['bill']}"]);
        }

        try {
            $service = new FortuneConversationService(FortuneTellingSetting::getSettings());
            $result = $service->adminSwitchPackage(
                $reading,
                $validated['target'],
                $validated['pay_mode'],
                (bool) ($validated['force'] ?? false)
            );

            Log::warning('🔀 Admin สลับแพ็กเกจบิล', [
                'admin_id' => $request->user()?->id,
                'bill_reference' => $reading->bill_reference,
                'reading_id' => $reading->id,
                'target' => $validated['target'],
                'pay_mode' => $validated['pay_mode'],
                'ok' => $result['ok'] ?? false,
            ]);

            return response()->json(array_merge(['success' => (bool) ($result['ok'] ?? false)], $result));
        } catch (\Throwable $e) {
            Log::error('🔀 Admin สลับแพ็กเกจบิล ล้มเหลว', [
                'bill' => $validated['bill'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * หา reading จาก bill_reference (FTU-...) หรือ reading_id (ตัวเลข)
     */
    protected function resolveReading(string $bill): ?FortuneReading
    {
        return is_numeric($bill)
            ? FortuneReading::find((int) $bill)
            : FortuneReading::where('bill_reference', $bill)->first();
    }

    /**
     * Helper — รัน step + capture result/error
     */
    protected function step(string $name, \Closure $closure): array
    {
        $startTime = microtime(true);
        try {
            $data = $closure();
            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            return array_merge([
                'name' => $name,
                'success' => true,
                'elapsed_ms' => $elapsed,
            ], $data);
        } catch (\Throwable $e) {
            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'name' => $name,
                'success' => false,
                'elapsed_ms' => $elapsed,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * อ่านท้ายไฟล์อย่างมีประสิทธิภาพ — ไม่ load all in memory
     */
    protected function tailFile(string $path, int $lines): array
    {
        $handle = @fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = [];

        try {
            while ($linecounter > 0) {
                $t = ' ';
                while ($t !== "\n") {
                    if (fseek($handle, $pos, SEEK_END) == -1) {
                        $beginning = true;
                        break;
                    }
                    $t = fgetc($handle);
                    $pos--;
                }
                $linecounter--;
                if ($beginning) {
                    rewind($handle);
                }
                $text[$lines - $linecounter - 1] = fgets($handle);
                if ($beginning) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return array_reverse(array_map('rtrim', array_filter($text)));
    }

    protected function getLogPath(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * สร้าง prompt สั้นๆ สำหรับทดสอบ (ไม่ทับ flow ลูกค้า)
     */
    protected function buildTestPrompt(FortuneReading $reading, string $question, array $cards): string
    {
        $cardsText = "ไพ่ทั้ง 10 ใบของเจ้าชะตา:\n";
        foreach ($cards as $pos => $card) {
            $cardsText .= "  ตำแหน่ง {$pos}: ".($card['card_name_th'] ?? $card['card_name_en'] ?? '?')."\n";
        }

        return "[DEBUG TEST PROMPT]\n\n"
            ."คุณคือแม่หมอจันทรา — ตอบคำถามด้วยพลังจักรวาลและไพ่ทั้ง 10 ใบ\n\n"
            .$cardsText."\n"
            .'คำถาม: '.$question."\n\n"
            .'ตอบ 150-300 ตัวอักษร ฟันธง ห้ามคำกำกวม';
    }
}
