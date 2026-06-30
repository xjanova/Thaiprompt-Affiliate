<?php

namespace App\Http\Controllers;

use App\Models\EveProductWish;
use App\Models\Product;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * น้อง Eve — ผู้ช่วย AI หน้าเว็บ (ฝั่งลูกค้า/หน้าร้าน)
 *
 * คุยกับลูกค้าด้วย AI Pool เดียวกับระบบทำนาย (FortuneAIService::chatWithCustomSystemPrompt)
 * ใช้ pattern เดียวกับ Eve ฝั่งแอดมิน (Api\Admin\EveController) — provider override + keyless fallback
 *
 * Phase 2: คุยทั่วไป + แนะนำ/ช่วยหาสินค้าเชิงสนทนา
 * Phase 3 (ถัดไป): ต่อ tool ค้นหาสินค้าจริง (local catalog → affiliate API)
 *
 * 🔒 ความปลอดภัย: Eve ห้ามเปิดเผยข้อมูลอ่อนไหว (รหัส/คีย์/ข้อมูลส่วนตัวคนอื่น/การเงินภายใน)
 *    — บังคับใน system prompt + controller นี้ไม่ query ข้อมูลอ่อนไหวใดๆ
 */
class EveAssistantController extends Controller
{
    /**
     * POST /eve/chat
     */
    public function chat(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:500',
            'history' => 'nullable|array|max:16',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1000',
        ]);

        $userName = $request->user()?->name;
        $systemPrompt = $this->buildSystemPrompt($userName);
        $userMessage = $this->buildUserMessage($data['history'] ?? [], $data['message']);

        $config = ['temperature' => 0.6, 'max_tokens' => 320];

        try {
            try {
                // ใช้ gemini (pool มีหลายคีย์ ราคาถูก) — re-resolve คีย์ตาม provider
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                    providerOverride: 'gemini',
                );
            } catch (Throwable $inner) {
                // provider ที่ขอไม่มีคีย์ → fall back ไป default pool (เหมือน Eve แอดมิน)
                if (stripos($inner->getMessage(), 'API Key') === false) {
                    throw $inner;
                }
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                );
            }

            $reply = is_array($result)
                ? trim((string) ($result['response'] ?? $result['content'] ?? $result['text'] ?? ''))
                : trim((string) $result);

            if ($reply === '') {
                $reply = 'ขออภัยค่ะ ตอนนี้น้อง Eve ตอบไม่ได้ ลองพิมพ์ใหม่อีกครั้งนะคะ 🙏';
            }

            // 🔎 ค้นหาสินค้า: ใช้แท็ก [FIND:] จาก AI ถ้ามี — ไม่งั้นดึงเจตนาจาก "ข้อความลูกค้า" เอง
            //    (กันเคสโมเดลเล็กไม่ปล่อยแท็ก → เดิมตอบ "หาให้ค่ะ" แต่ไม่เคยค้นจริง = ลูกค้าเห็นเป็นค้าง)
            [$reply, $products, $mood] = $this->runProductSearch($reply, $data['message'], $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'mood' => $mood,
                    'products' => $products,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('Eve (storefront): chat failed', [
                'error' => $e->getMessage(),
                'message_preview' => mb_substr($data['message'], 0, 80),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ขออภัยค่ะ ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งนะคะ 🙏',
                'data' => ['mood' => 'concerned'],
            ], 200); // ตอบ 200 + ข้อความสุภาพ ให้ widget แสดงได้เลย
        }
    }

    /**
     * สร้าง system prompt — persona น้อง Eve + guardrails ข้อมูลอ่อนไหว
     */
    private function buildSystemPrompt(?string $userName): string
    {
        $nameLine = $userName ? "ลูกค้าชื่อ \"{$userName}\" (เรียกชื่อได้เป็นกันเอง). " : '';

        return "คุณคือ \"น้อง Eve\" ผู้ช่วย AI สาวน่ารักประจำเว็บ ThaiPrompt (ร้านค้าออนไลน์/แพลตฟอร์มคนไทย). "
            ."{$nameLine}"
            ."หน้าที่: ต้อนรับ ช่วยลูกค้าหาสินค้า แนะนำ ตอบคำถามทั่วไปเกี่ยวกับร้าน และพาไปยังหน้าที่ต้องการ. "
            ."ลักษณะการพูด: ภาษาไทยล้วน สุภาพ สดใส เป็นกันเอง ลงท้าย 'ค่ะ/นะคะ' เสมอ (คุณเป็นผู้หญิง ห้ามใช้ครับ/ผม). "
            ."ตอบสั้นกระชับ 1-3 ประโยค ใช้อิโมจิได้บ้างเล็กน้อย (ไม่เกิน 1 ตัวต่อข้อความ). "
            ."ถ้าลูกค้าอยากได้สินค้าบางอย่าง ให้ถามรายละเอียดสั้นๆ (งบประมาณ/สี/รุ่น) เพื่อช่วยหาให้ตรงใจ และบอกว่ากำลังช่วยหาให้นะคะ. "
            ."ถ้ายังหาไม่เจอทันที บอกลูกค้าได้ว่า 'เดี๋ยวน้อง Eve ให้ทีมงานช่วยหาให้ รอสักครู่นะคะ' อย่างสุภาพ. "
            ."\n\n🔎 เครื่องมือค้นหาสินค้า: ถ้าลูกค้าอยากได้/หา/ซื้อสินค้าอะไรสักอย่าง ให้ตอบสั้นๆ ว่ากำลังหาให้ (1 ประโยค) "
            ."แล้วลงท้ายข้อความด้วยแท็กพิเศษ [FIND: คำค้นสั้นๆเป็นคีย์เวิร์ด | งบสูงสุดเป็นตัวเลขบาทหรือ 0] "
            ."เช่น [FIND: หูฟังบลูทูธ | 500] หรือ [FIND: กระเป๋าสะพาย | 0]. "
            ."⚠️ ลูกค้าจะไม่เห็นแท็กนี้ (ระบบเอาไปค้นให้แล้วลบทิ้ง) ห้ามอธิบายแท็ก ใส่ได้สูงสุด 1 อันต่อข้อความ "
            ."และห้ามแต่งรายชื่อ/ราคาสินค้าเอง — ระบบจะแสดงสินค้าจริงให้เอง. ถ้าเป็นการคุยทั่วไป/ทักทาย/ถามข้อมูล ไม่ต้องใส่แท็ก. "
            ."\n\n🔒 กฎความปลอดภัย (สำคัญมาก ห้ามฝ่าฝืน): "
            ."ห้ามเปิดเผยหรือพูดถึงข้อมูลอ่อนไหวเด็ดขาด ได้แก่ รหัสผ่าน, API key, โทเคน, ข้อมูลบัตร/บัญชีธนาคาร, "
            ."ข้อมูลส่วนตัวของลูกค้าคนอื่น, ข้อมูลการเงิน/คอมมิชชั่นภายใน, โครงสร้างระบบหรือช่องโหว่ความปลอดภัย. "
            ."ถ้าถูกถามเรื่องเหล่านี้ ให้ปฏิเสธสุภาพว่าช่วยเรื่องนี้ไม่ได้ และชวนกลับมาคุยเรื่องสินค้า/บริการแทน. "
            ."ห้ามแต่งราคา/สต็อก/โปรโมชั่นที่ไม่รู้จริง — ถ้าไม่แน่ใจให้บอกว่าจะตรวจสอบให้.";
    }

    /**
     * รวมประวัติสนทนา + ข้อความล่าสุด เป็น user message เดียว
     */
    private function buildUserMessage(array $history, string $latest): string
    {
        if (empty($history)) {
            return $latest;
        }
        $lines = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'Eve' : 'ลูกค้า';
            $lines[] = $role.': '.($turn['content'] ?? '');
        }
        $lines[] = 'ลูกค้า: '.$latest;
        $lines[] = 'Eve:';

        return implode("\n", $lines);
    }

    /**
     * เดาอารมณ์จากคำตอบ เพื่อให้ widget เปลี่ยนสีหน้า Eve
     */
    private function guessMood(string $reply): string
    {
        $t = mb_strtolower($reply);
        if (preg_match('/(ขออภัย|เสียใจ|ขัดข้อง|ไม่ได้|ไม่สามารถ)/u', $t)) {
            return 'concerned';
        }
        if (preg_match('/(เจอแล้ว|ได้เลย|เยี่ยม|ดีจัง|ยินดี|จัดให้|ขอบคุณ)/u', $t)) {
            return 'happy';
        }
        if (preg_match('/(\?|งบ|รุ่นไหน|แบบไหน|สีอะไร|รอสักครู่|กำลังหา|ขอเช็ก|ขอตรวจ)/u', $t)) {
            return 'thinking';
        }

        return 'happy';
    }

    /**
     * ตรวจแท็ก [FIND: คำค้น | งบ] จากคำตอบ AI → ค้นสินค้าจริง → คืน [reply, products, mood]
     */
    private function runProductSearch(string $reply, string $userMessage, ?int $userId): array
    {
        // ดึงคำค้น + งบจากแท็ก (ทนทานต่อ noise: "500 บาท", "1,500", "~500", case-insensitive, เว้นวรรครอบ :|)
        $query = null;
        $budget = null;
        if (preg_match('/\[\s*FIND\s*:\s*([^\]|]+?)\s*(?:\|\s*([^\]]*?))?\s*\]/iu', $reply, $m)) {
            $query = trim($m[1]);
            if (isset($m[2])) {
                $num = preg_replace('/[^0-9.]/', '', $m[2]);   // '500 บาท'/'1,500' → 500/1500 · 'ไม่จำกัด' → ''
                $budget = ($num !== '' && (float) $num > 0) ? (float) $num : null;
            }
        }

        // ⚠️ ลบแท็ก [FIND...] "เสมอ" ก่อน return — ไม่ว่าจะ parse งบได้หรือไม่ กันแท็กดิบหลุดให้ลูกค้าเห็น
        $reply = trim((string) preg_replace('/\[\s*FIND\s*:[^\]]*\]/iu', '', $reply));

        // ⭐ Fallback กันค้าง: ถ้า AI ไม่ปล่อยแท็ก แต่ลูกค้าสื่อว่าอยากได้/หาสินค้า
        //    → ดึงคำค้นจาก "ข้อความลูกค้า" เองแล้วค้นให้เสมอ (โมเดลเล็กมักไม่ใส่แท็ก จึงต้องไม่พึ่งมัน)
        if ($query === null || $query === '') {
            [$intentQuery, $intentBudget] = $this->extractSearchIntent($userMessage);
            if ($intentQuery !== null) {
                $query = $intentQuery;
                if ($budget === null) {
                    $budget = $intentBudget;
                }
            }
        }

        $products = [];
        $mood = $this->guessMood($reply);

        if ($query !== null && $query !== '') {
            {
                $products = $this->searchCatalog($query, $budget);

                if (empty($products)) {
                    $this->recordWish($query, $budget, $userId);
                    // 🙏 จริงใจ + ชวนปรับคำค้น (เลี่ยงสัญญา "รอ 10 นาที" ที่ยังไม่มีระบบส่งผลกลับจริง = ค้าง)
                    $reply = trim($reply."\n\nตอนนี้ยังไม่เจอสินค้านี้ในร้านค่ะ 🙏 ลองบอกยี่ห้อ/รุ่น หรือพิมพ์คำค้นอื่นดูได้นะคะ เดี๋ยว Eve หาให้ใหม่ค่ะ");
                    $mood = 'thinking';
                } else {
                    $reply = trim($reply."\n\nเจอ ".count($products)." รายการที่น่าจะใช่ค่ะ 😊 ลองดูเลยนะคะ — ถ้าใช่กด \"ดูสินค้า\" ได้เลย หรืออยากให้หาแบบอื่นก็บอกได้ค่ะ");
                    $mood = 'happy';
                }
            }
        }

        if ($reply === '') {
            $reply = 'ได้เลยค่ะ 😊';
        }

        return [$reply, $products, $mood];
    }

    /**
     * ดึง "เจตนาหาสินค้า + คำค้น + งบ" จากข้อความลูกค้าเอง (ไม่พึ่งแท็ก [FIND:] ของ AI)
     *
     * ⚠️ เหตุผล: โมเดล chat เล็ก (เช่น gemini-flash-lite) มักไม่ปล่อยแท็ก →
     *    เดิม Eve ตอบ "กำลังหาให้ค่ะ" แต่ไม่เคยค้นจริง → ลูกค้าเห็นเป็น "ค้างไปเลย"
     *    เมธอดนี้การันตีว่าถ้าลูกค้าสื่อว่าอยากได้ของ ระบบจะค้น catalog ให้เสมอ
     *
     * 🇹🇭 ความปลอดภัยภาษาไทย: ตัดคำสั่ง/คำเติม "เฉพาะหัว/ท้ายประโยค" (anchored ^ $) เท่านั้น
     *    ห้าม str_replace ทั้งสตริง — "หา" เป็น substring ของ "อาหาร", "มี" ของ "มีด", "ขอ" ของ "ของ"
     *    จึงไม่ใส่ มี/ขอ/ดู ในรายการตัดหัว (อันตราย) — ใช้คำประสมที่ชัดแทน เช่น "ขอดู"
     *
     * @return array{0: ?string, 1: ?float} [คำค้น (null = ไม่ใช่การหาสินค้า), งบ]
     */
    private function extractSearchIntent(string $message): array
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', $message));
        if ($q === '') {
            return [null, null];
        }

        // ต้องมีสัญญาณว่ากำลังหา/อยากได้สินค้า ไม่งั้นถือเป็นคุยเล่น/ทักทาย (ไม่ค้น)
        if (! preg_match('/(หา|อยากได้|อยากซื้อ|หาซื้อ|ซื้อ|ขาย|มีขาย|มี.{0,8}(ไหม|มั้ย|ป่าว|รึเปล่า)|แนะนำ|อยากหา|ช่วยหา|ขอดู|เอา|สั่ง|รุ่นไหน|ยี่ห้อ|ราคา|งบ)/u', $q)) {
            return [null, null];
        }

        // งบประมาณ: "งบ 500", "ไม่เกิน 500", "ราคา 500", "500 บาท"
        $budget = null;
        if (preg_match('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคา)\s*([0-9][0-9,.]*)/u', $q, $b)
            || preg_match('/([0-9][0-9,.]*)\s*บาท/u', $q, $b)) {
            $num = (float) preg_replace('/[^0-9.]/', '', $b[1]);
            $budget = $num > 0 ? $num : null;
        }

        // ตัดวลีงบ/ราคา (มีตัวเลข = ปลอดภัย ไม่ทับชื่อสินค้า)
        $q = preg_replace('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคาไม่เกิน|ราคา)\s*[0-9][0-9,.]*\s*(?:บาท)?/u', ' ', $q);
        $q = preg_replace('/[0-9][0-9,.]*\s*บาท/u', ' ', $q);

        // ตัดคำสั่ง "หัวประโยค" (anchored ^) ทีละชั้น — คำประสมที่ยาว/ชัดก่อน
        $q = preg_replace('/^\s*(ช่วย|รบกวน)\s*/u', '', (string) $q);
        $q = preg_replace('/^\s*(ขอดู|อยากได้|อยากซื้อ|อยากหา|หาซื้อ|หาให้|ช่วยหา|แนะนำ|สั่งซื้อ|หา|ซื้อ|สั่ง|เอา)\s*/u', '', (string) $q);

        // ตัดคำลงท้าย/คำถาม "ท้ายประโยค" (anchored $)
        $q = preg_replace('/\s*(ราคาถูกๆ|ราคาถูก|ราคาประหยัด|เท่าไหร่|เท่าไร|ถูกๆ|ถูก|ดีๆ|สวยๆ|หน่อยค่ะ|หน่อยครับ|หน่อย|ด้วยค่ะ|ด้วยครับ|ด้วย|ให้หน่อย|ให้ที|ทีนะ|นะคะ|นะครับ|จ้า|ค่ะ|คะ|ครับ|มีไหม|มีมั้ย|ไหม|มั้ย|ป่าว|รึเปล่า|บ้าง|อะ|อ่ะ)+\s*$/u', '', (string) $q);

        $q = trim((string) preg_replace('/\s+/u', ' ', (string) $q));

        // เหลือสั้นไป หรือเหลือแต่คำถามล้วน → ไม่ค้น (กันค้นมั่ว)
        if (mb_strlen($q) < 2 || preg_match('/^(อะไร|เท่าไหร่|เท่าไร|ยังไง|ที่ไหน|อันไหน|แบบไหน|ไง|ดี|มี|ของ|ราคา)$/u', $q)) {
            return [null, null];
        }

        return [$q, $budget];
    }

    /**
     * ค้นสินค้าใน catalog ของเรา (ตาราง products ที่เผยแพร่ขายได้จริง) — ข้อมูลสาธารณะเท่านั้น
     *
     * @return array<int,array>
     */
    private function searchCatalog(string $query, ?float $budget): array
    {
        $tokens = collect(preg_split('/\s+/u', trim($query)) ?: [])
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t) >= 2)
            ->take(6)
            ->values();

        if ($tokens->isEmpty()) {
            return [];
        }

        try {
            $q = Product::query()
                ->where('is_active', true)
                ->where('is_hidden', false)
                ->where('is_public_approved', true)
                ->where(function ($w) {
                    $w->whereNull('is_blocked')->orWhere('is_blocked', false);
                });

            if ($budget && $budget > 0) {
                $q->where('price', '<=', $budget);
            }

            $q->where(function ($w) use ($tokens) {
                foreach ($tokens as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                        ->orWhere('brand', 'like', "%{$t}%")
                        ->orWhere('short_description', 'like', "%{$t}%");
                }
            });

            return $q->orderByDesc('is_featured')
                ->orderByDesc('sales_count')
                ->limit(6)
                ->get(['id', 'name', 'slug', 'price', 'main_image_url'])
                ->map(fn (Product $p) => [
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'image' => $p->main_image_url,
                    'url' => $p->slug ? route('shop.show', $p->slug) : url('/storefront'),
                ])
                ->all();
        } catch (Throwable $e) {
            Log::warning('Eve: searchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * บันทึก "ของที่ลูกค้าอยากได้แต่ยังไม่มี" — best-effort (ตารางอาจยังไม่ migrate)
     */
    private function recordWish(string $query, ?float $budget, ?int $userId): void
    {
        try {
            if (Schema::hasTable('eve_product_wishes')) {
                EveProductWish::create([
                    'user_id' => $userId,
                    'query' => mb_substr($query, 0, 255),
                    'budget' => $budget,
                    'results_found' => 0,
                    'status' => 'pending',
                    'source' => 'eve_chat',
                ]);
            }
        } catch (Throwable $e) {
            // best-effort — ไม่บล็อกการตอบ
        }
    }
}
