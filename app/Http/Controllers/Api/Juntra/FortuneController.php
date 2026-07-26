<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * FortuneController — Mobile API for the Juntra app
 *
 * Wraps the existing FortuneAIService (with its full key-pool + self-heal
 * + purpose-filter behavior) so the Flutter client can request tarot
 * readings without holding any AI provider keys directly.
 *
 * Storage: writes to fortune_readings with platform='juntra' so analytics
 * can distinguish Juntra mobile traffic from Facebook/LINE webhooks.
 */
class FortuneController extends Controller
{
    /**
     * GET /v1/fortune/categories — static list mirroring lib/shared/data/fortune_categories.dart
     */
    public function categories(): JsonResponse
    {
        return response()->json(['data' => [
            ['id' => 'love',    'name' => 'ความรัก',    'en' => 'Love',     'icon' => '♥', 'color' => '#E91E63'],
            ['id' => 'money',   'name' => 'การเงิน',    'en' => 'Wealth',   'icon' => '◈', 'color' => '#FFD700'],
            ['id' => 'work',    'name' => 'การงาน',     'en' => 'Career',   'icon' => '✦', 'color' => '#7C4DFF'],
            ['id' => 'health',  'name' => 'สุขภาพ',     'en' => 'Health',   'icon' => '✚', 'color' => '#00E5A0'],
            ['id' => 'family',  'name' => 'ครอบครัว',   'en' => 'Family',   'icon' => '⌂', 'color' => '#FF8A65'],
            ['id' => 'fortune', 'name' => 'โชคลาภ',     'en' => 'Luck',     'icon' => '✧', 'color' => '#FFAB00'],
            ['id' => 'general', 'name' => 'ภาพรวม',     'en' => 'Overall',  'icon' => '☾', 'color' => '#C39BD3'],
            ['id' => 'past',    'name' => 'อดีตชาติ',   'en' => 'Past Life', 'icon' => '꩜', 'color' => '#9575CD'],
        ]]);
    }

    /**
     * GET /v1/fortune/spreads — spread definitions (mirrors lib/shared/data/spreads.dart)
     */
    public function spreads(): JsonResponse
    {
        return response()->json(['data' => [
            ['id' => '3card',     'name' => '3 ใบ',       'desc' => 'อดีต · ปัจจุบัน · อนาคต', 'cards' => 3,  'price' => 0,   'badge' => 'ฟรี', 'popular' => true],
            ['id' => 'love',      'name' => 'ผังคู่รัก',  'desc' => 'ใจฉัน · ใจเขา · ความสัมพันธ์ · ผลลัพธ์', 'cards' => 4, 'price' => 49],
            ['id' => 'celtic',    'name' => 'เซลติกครอส', 'desc' => 'การวิเคราะห์เชิงลึก 10 ใบ', 'cards' => 10, 'price' => 199, 'badge' => 'ลึกซึ้ง'],
            ['id' => 'year',      'name' => 'ดวง 12 เดือน', 'desc' => 'พยากรณ์รายเดือนตลอดปี',     'cards' => 12, 'price' => 299, 'badge' => 'ใหม่'],
            ['id' => 'horseshoe', 'name' => 'เกือกม้า',   'desc' => '7 ใบ ครอบคลุมทุกแง่มุม',    'cards' => 7,  'price' => 99],
            ['id' => 'yes-no',    'name' => 'ใช่ / ไม่ใช่', 'desc' => 'ใบเดียว ตอบคำถามเร่งด่วน', 'cards' => 1,  'price' => 19],
        ]]);
    }

    /**
     * GET /v1/fortune/credits — user's free quota + paid balance
     */
    public function credits(Request $request): JsonResponse
    {
        $user = $request->user();

        // 🐛 (2026-07-26) แก้ 500 ที่ซ่อนอยู่หลัง 401 มาตลอด
        //    เดิมเรียก FortuneUserCredit::firstOrCreate(['user_id' => ...]) และอ่าน
        //    free_credits / paid_credits — **ไม่มีคอลัมน์พวกนี้จริงทั้ง 3 ตัว**
        //    ตาราง fortune_user_credits ของบอทผูกกับคู่ (platform, facebook_user_id)
        //    → ทุก request ตกด้วย SQLSTATE[42S22] Unknown column 'user_id'
        //    (เพิ่งเห็นเพราะก่อนหน้านี้ guard ตีกลับ 401 ก่อนถึงคอนโทรลเลอร์)
        //
        //    สิทธิ์ฟรีของบอทนับ "ต่อ platform_user_id" จึงต้องไล่จากช่องทางที่
        //    บัญชีนี้ผูกไว้ ส่วนยอดเงินอยู่ที่วอลเลตของเว็บจันทรา ไม่ใช่ที่นี่
        $freeUsed = false;
        $bonusRemaining = 0;
        $unlimited = false;

        foreach ($this->platformIdentities($user) as [$platform, $platformUserId]) {
            if (FortuneReading::hasUsedFreeCard($platform, $platformUserId)) {
                $freeUsed = true;
            }

            $credit = FortuneUserCredit::findByUser($platformUserId, $platform);
            if ($credit) {
                $bonusRemaining += max(0, $credit->getRemainingCredits());
                if ($credit->isCurrentlyUnlimited()) {
                    $unlimited = true;
                }
            }
        }

        return response()->json(['data' => [
            'free_remaining' => $unlimited ? 999 : ($freeUsed ? $bonusRemaining : $bonusRemaining + 1),
            'paid_balance' => 0,   // ยอดเงินจริงอยู่ที่วอลเลตของเว็บจันทรา
            'unlimited' => $unlimited,
            'total_readings' => FortuneReading::where('user_id', $user->id)->count(),
        ]]);
    }

    /**
     * ช่องทางที่บัญชีนี้ผูกไว้ — ใช้เทียบสิทธิ์ฟรีของบอทที่นับต่อ platform_user_id
     *
     * @return array<int,array{0:string,1:string}> [[platform, platform_user_id], ...]
     */
    private function platformIdentities($user): array
    {
        $identities = [];

        if (! empty($user->line_user_id)) {
            $identities[] = ['line', (string) $user->line_user_id];
        }

        if (Schema::hasColumn('users', 'facebook_psid') && ! empty($user->facebook_psid)) {
            $identities[] = ['facebook', (string) $user->facebook_psid];
        }

        return $identities;
    }

    /**
     * GET /v1/fortune/history — paginated past readings
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(5, min(50, $perPage));

        $readings = FortuneReading::where('user_id', $user->id)
            ->where('platform', 'juntra')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $readings->map(fn ($r) => $this->serializeReading($r))->values(),
            'meta' => [
                'current_page' => $readings->currentPage(),
                'last_page' => $readings->lastPage(),
                'total' => $readings->total(),
            ],
        ]);
    }

    /**
     * GET /v1/fortune/readings/{id} — full reading detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $reading = FortuneReading::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $reading->increment('view_count');

        return response()->json(['data' => $this->serializeReading($reading, true)]);
    }

    /**
     * POST /v1/fortune/draw — start a new session, deduct credit, return cards
     *
     * Body: { spread: '3card', category: 'love', question: 'string' }
     * Returns: { reading_id, cards: [{tarot_id, position, reversed}], picked_at }
     */
    public function draw(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'spread' => 'required|string|max:32',
            'category' => 'nullable|string|max:32',
            'question' => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $user = $request->user();
        $spread = $request->input('spread');
        $cardCount = $this->cardCountForSpread($spread);

        // Generate the picked cards (Major Arcana 0..21 + reversed flag)
        $deck = range(0, 21);
        shuffle($deck);
        $picked = array_slice($deck, 0, $cardCount);
        $cards = [];
        $positions = $this->positionsForSpread($spread);
        foreach ($picked as $i => $tarotId) {
            $cards[] = [
                'tarot_id' => $tarotId,
                'position' => $positions[$i] ?? 'ใบที่ '.($i + 1),
                // ~20% reversed. The previous one-liner was broken — operator
                // precedence made it always false. Use plain int comparison.
                'reversed' => random_int(0, 4) === 0,
            ];
        }

        $reading = FortuneReading::create([
            'bill_reference' => 'JUN-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'platform' => 'juntra',
            'platform_user_id' => 'mobile-'.$user->id,
            'facebook_user_id' => 'juntra-'.$user->id,  // schema requires non-null
            'questions' => [$request->input('question', '')],
            'categories' => $request->input('category') ? [$request->input('category')] : null,
            'ai_response' => '',  // Filled by /read
            'ai_provider' => 'pending',
            'ai_model' => 'pending',
            'is_paid' => $this->priceForSpread($spread) === 0,
            'amount_paid' => 0,
            'response_type' => 'pending',
            'reading_type' => $cardCount >= 7 ? 'deep' : 'basic',
            'sender_info' => json_encode([
                'spread' => $spread,
                'cards' => $cards,
                'app' => 'juntra',
            ]),
        ]);

        return response()->json(['data' => [
            'reading_id' => $reading->id,
            'spread' => $spread,
            'cards' => $cards,
            'picked_at' => $reading->created_at->toIso8601String(),
            'requires_payment' => $this->priceForSpread($spread) > 0,
            'price' => $this->priceForSpread($spread),
        ]]);
    }

    /**
     * POST /v1/fortune/read — generate AI interpretation for a drawn reading
     *
     * Body: { reading_id }
     * Returns: { reading_id, summary, per_card: [...], advice: [...], ai_provider }
     */
    public function read(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'reading_id' => 'required|integer',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $reading = FortuneReading::where('id', $request->input('reading_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $reading->is_paid && $this->priceForSpread($this->spreadFromReading($reading)) > 0) {
            return response()->json(['message' => 'กรุณาชำระเงินก่อน'], 402);
        }

        try {
            $aiService = new FortuneAIService;
            $info = json_decode($reading->sender_info ?? '{}', true);
            $cards = $info['cards'] ?? [];
            $spread = $info['spread'] ?? '3card';

            $questions = [
                'spread' => $spread,
                'category' => is_array($reading->categories) ? ($reading->categories[0] ?? 'general') : 'general',
                'question' => is_array($reading->questions) ? ($reading->questions[0] ?? '') : '',
                'cards' => $cards,
            ];

            // 🪪 (2026-05-24) Tag the AI usage log with customer identity so
            //   warroom /workers can deep-link to /chat?id=r-{reading_id}.
            $aiService->withCustomerContext([
                'reading_id' => $reading->id,
                'user_id' => $request->user()->id,
                'fb_user_id' => $reading->facebook_user_id,
                'customer_name' => $request->user()->name,
            ]);

            $result = $aiService->generateWithRetryAndFallback(
                questions: $questions,
                userProfile: ['name' => $request->user()->name ?? 'ลูก'],
                userPosts: null,
                promptTemplate: $this->buildJuntraPrompt($spread, $cards),
                readingType: $reading->reading_type ?? 'basic',
                userContext: 'juntra-user-'.$request->user()->id,
            );

            $reading->update([
                'ai_response' => $result['response'] ?? '',
                'ai_provider' => $result['provider'] ?? 'unknown',
                'ai_model' => $result['model'] ?? null,
                'tokens_used' => $result['tokens_used'] ?? 0,
                'response_type' => 'completed',
                'responded_at' => now(),
            ]);

            return response()->json(['data' => [
                'reading_id' => $reading->id,
                'response' => $reading->ai_response,
                'summary' => $this->extractSummary($reading->ai_response),
                'cards' => $cards,
                'ai_provider' => $reading->ai_provider,
            ]]);

        } catch (Exception $e) {
            Log::error('Juntra fortune read failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'ขออภัย ระบบดูดวงไม่พร้อมในขณะนี้ กรุณาลองใหม่อีกครู่',
            ], 503);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function serializeReading(FortuneReading $r, bool $full = false): array
    {
        $info = json_decode($r->sender_info ?? '{}', true);
        $base = [
            'id' => $r->id,
            'spread' => $info['spread'] ?? null,
            'category' => is_array($r->categories) ? ($r->categories[0] ?? null) : null,
            'created_at' => $r->created_at?->toIso8601String(),
            'is_paid' => (bool) $r->is_paid,
            'preview' => Str::limit($r->ai_response ?? '', 80, '...'),
            'cards' => $info['cards'] ?? [],
        ];
        if ($full) {
            $base['response'] = $r->ai_response;
            $base['question'] = is_array($r->questions) ? ($r->questions[0] ?? '') : '';
            $base['ai_provider'] = $r->ai_provider;
        }

        return $base;
    }

    private function cardCountForSpread(string $id): int
    {
        return [
            '3card' => 3, 'love' => 4, 'celtic' => 10, 'year' => 12,
            'horseshoe' => 7, 'yes-no' => 1,
        ][$id] ?? 3;
    }

    private function priceForSpread(string $id): int
    {
        return [
            '3card' => 0, 'love' => 49, 'celtic' => 199, 'year' => 299,
            'horseshoe' => 99, 'yes-no' => 19,
        ][$id] ?? 0;
    }

    private function positionsForSpread(string $id): array
    {
        return [
            '3card' => ['อดีต', 'ปัจจุบัน', 'อนาคต'],
            'love' => ['ใจฉัน', 'ใจเขา', 'ความสัมพันธ์', 'ผลลัพธ์'],
            'celtic' => ['แก่นสถานการณ์', 'อุปสรรค', 'ราก-จิตใต้สำนึก', 'อดีตล่าสุด',
                'เป้าหมาย-สติ', 'อนาคตใกล้', 'ตัวเอง', 'สิ่งแวดล้อม',
                'ความหวัง-ความกลัว', 'ผลลัพธ์'],
            'year' => ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
            'horseshoe' => ['อดีต', 'ปัจจุบัน', 'อิทธิพลซ่อน', 'คำแนะนำ',
                'สิ่งแวดล้อม', 'อุปสรรค', 'ผลลัพธ์'],
            'yes-no' => ['คำตอบ'],
        ][$id] ?? [];
    }

    private function spreadFromReading(FortuneReading $r): string
    {
        $info = json_decode($r->sender_info ?? '{}', true);

        return $info['spread'] ?? '3card';
    }

    private function extractSummary(string $response): string
    {
        // First sentence ending with . / ! / ? / Thai เ ๆ
        $first = preg_split('/(?<=[\.\!\?\n])/u', trim($response), 2);

        return Str::limit(trim($first[0] ?? $response), 240, '...');
    }

    private function buildJuntraPrompt(string $spread, array $cards): string
    {
        $cardLines = array_map(function ($c, $i) {
            $rev = ($c['reversed'] ?? false) ? ' (ย้อน)' : '';

            return ($i + 1).". ตำแหน่ง '{$c['position']}' — ไพ่ id={$c['tarot_id']}$rev";
        }, $cards, array_keys($cards));

        return 'คุณคือแม่หมอจันทรา ผู้พยากรณ์ทาโรต์ที่อ่อนโยนและขลัง พูดกับลูก (ผู้ใช้) ด้วยน้ำเสียงอบอุ่นแต่หนักแน่น '
            ."อ่านไพ่ทาโรต์รูปแบบ '$spread' ที่ลูกสุ่มได้:\n"
            .implode("\n", $cardLines)
            ."\n\nให้คำทำนายแยกเป็นแต่ละตำแหน่ง สรุปภาพรวม และแนะนำการปฏิบัติ "
            .'(สีมงคล วันสำคัญ หินมงคล ฯลฯ) — ใช้ภาษาไทยที่ขลังและจริงใจ.';
    }
}
