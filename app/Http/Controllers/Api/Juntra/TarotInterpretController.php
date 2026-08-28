<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\FortuneAIService;
use App\Services\FortuneKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/juntra/fortune/tarot/interpret
 *
 * คำทำนายไพ่สำหรับเว็บ จันทรา.online (juntraweb) — ให้ได้คุณภาพชุดเดียวกับ
 * บอทแม่หมอใน Facebook/LINE
 *
 * ทำไมต้องอยู่ฝั่งนี้: "คลังความรู้ไพ่" ที่ทำให้คำทำนายของบอทลึกและฟันธง
 * (config/fortune_card_* 16 หมวด + spread pattern + elemental dignity +
 * position dynamics) อยู่ในรีโปนี้ทั้งหมด ถ้าก๊อปไปไว้ juntraweb จะ drift
 * ทันทีที่แก้ prompt ฝั่งใดฝั่งหนึ่ง — บทเรียนเดียวกับเพดานคุยฟรีที่เคย
 * มีเฉพาะฝั่งเว็บจนผู้ใช้แอพยิงฟรีไม่จำกัด
 *
 * juntraweb ส่ง "ไพ่ที่เปิดได้ + ตำแหน่ง + คำถาม + prompt ตาม Card-First
 * Mandate" มาให้ ฝั่งนี้เติมคลังความรู้ตามไพ่ที่เปิดจริงแล้วยิงผ่าน
 * FortuneAIService (คีย์พูลเดียวกับบอท — juntra ไม่ถือคีย์ AI เอง)
 *
 * หมายเหตุเรื่องเงิน: juntraweb หักเครดิตลูกค้าไปแล้วก่อนเรียกมา endpoint นี้
 * จึงไม่คิดเงินซ้ำและไม่แตะ FortuneReading/บิลของบอท
 *
 * juntraweb เรียกที่ FortuneBotClient::interpretTarot() ซึ่งเขียนรออยู่แล้ว
 * และถ้า endpoint นี้ล่ม/ยังไม่ deploy มันจะ fall back ไป chat pipeline เอง
 */
class TarotInterpretController extends Controller
{
    /** จำนวนไพ่สูงสุดที่รับ — กันคนยิง payload ยักษ์ใส่ */
    private const MAX_CARDS = 16;

    public function __invoke(Request $request, FortuneAIService $ai, FortuneKnowledgeService $kb): JsonResponse
    {
        $data = $request->validate([
            'spread' => 'nullable|string|max:64',
            'spread_key' => 'nullable|string|max:64',
            'spread_name' => 'nullable|string|max:128',
            'question' => 'nullable|string|max:2000',
            'prompt' => 'required|string|max:20000',
            'cards' => 'required|array|min:1|max:'.self::MAX_CARDS,
            'cards.*.position' => 'nullable|integer|min:1|max:'.self::MAX_CARDS,
            'cards.*.position_label' => 'nullable|string|max:128',
            'cards.*.position_name' => 'nullable|string|max:128',
            'cards.*.position_description' => 'nullable|string|max:500',
            'cards.*.asks' => 'nullable|string|max:500',
            'cards.*.name_th' => 'nullable|string|max:128',
            'cards.*.card_name_th' => 'nullable|string|max:128',
            'cards.*.name_en' => 'nullable|string|max:128',
            'cards.*.card_name_en' => 'nullable|string|max:128',
            'cards.*.reversed' => 'nullable|boolean',
            'cards.*.is_reversed' => 'nullable|boolean',
            'cards.*.meaning' => 'nullable|string|max:2000',
        ]);

        $cards = $this->normaliseCards($data['cards']);
        if (empty($cards)) {
            return response()->json(['message' => 'ไพ่ที่ส่งมาไม่ครบข้อมูล'], 422);
        }

        $knowledge = $this->knowledgeBlock($kb, $cards, (string) ($data['question'] ?? ''));

        $spreadName = $data['spread_name'] ?? 'ไพ่ยิปซี';
        $userMessage = trim($data['prompt']);
        if ($knowledge !== '') {
            $userMessage .= "\n\n".$knowledge;
        }

        try {
            $result = $ai->chatWithCustomSystemPrompt(
                $this->systemPrompt($spreadName, count($cards)),
                $userMessage,
                // คำทำนายเต็มใบต้องยาวกว่าแชททั่วไปมาก — 600 token ของ default
                // จะตัดกลางประโยคแล้วลูกค้าที่จ่ายเงินได้คำทำนายค้าง
                ['temperature' => 0.8, 'max_tokens' => 2600],
                // 🛡 (2026-08-28) ด่านกันเจลเบรค — ตรวจเฉพาะ "คำถามที่ลูกค้าพิมพ์เอง"
                //    ห้ามตรวจ $userMessage เพราะมันรวมบล็อกความรู้ไพ่ที่ระบบต่อท้ายเข้าไป
                guardText: (string) ($data['question'] ?? ''),
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra tarot interpret failed', ['err' => $e->getMessage()]);

            // ให้ juntraweb ตกไป chat pipeline / AiOracle ของตัวเองต่อ
            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        // FortuneAIService คืนข้อความไว้ที่คีย์ 'response' (ดู sanitizeChatResult)
        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '') {
            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }

        return response()->json(['data' => [
            'interpretation' => $text,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? null,
            'knowledge_used' => $knowledge !== '',
        ]]);
    }

    /**
     * แปลงไพ่จาก juntraweb ให้อยู่ในรูปที่ FortuneKnowledgeService อ่านได้
     * (key = ตำแหน่ง 1..N, ค่าใช้ชื่อฟิลด์เดียวกับที่บอทเก็บใน celtic_cards)
     *
     * @param  array<int,array<string,mixed>>  $raw
     * @return array<int,array<string,mixed>>
     */
    private function normaliseCards(array $raw): array
    {
        $cards = [];
        $i = 0;
        foreach ($raw as $c) {
            $i++;
            $pos = (int) ($c['position'] ?? $i);
            if ($pos < 1 || $pos > self::MAX_CARDS) {
                $pos = $i;
            }

            $nameEn = trim((string) ($c['card_name_en'] ?? $c['name_en'] ?? ''));
            $nameTh = trim((string) ($c['card_name_th'] ?? $c['name_th'] ?? ''));
            if ($nameEn === '' && $nameTh === '') {
                continue;   // ไพ่ไม่มีชื่อ = ใช้เทียบคลังความรู้ไม่ได้
            }

            $cards[$pos] = [
                'position_name' => (string) ($c['position_name'] ?? $c['position_label'] ?? "ใบที่ {$pos}"),
                'position_description' => (string) ($c['position_description'] ?? $c['asks'] ?? ''),
                'card_name_en' => $nameEn,
                'card_name_th' => $nameTh !== '' ? $nameTh : $nameEn,
                'is_reversed' => (bool) ($c['is_reversed'] ?? $c['reversed'] ?? false),
                'meaning' => (string) ($c['meaning'] ?? ''),
            ];
        }

        ksort($cards);

        return $cards;
    }

    /**
     * ประกอบคลังความรู้ตามไพ่ที่เปิดจริง + หมวดที่ตรวจจับได้จากคำถาม
     *
     * ส่วนที่ผูกกับ "ไพ่" ใส่เสมอ (โครงสำรับ ธาตุ ตำแหน่ง ไพ่คู่)
     * ส่วนที่ผูกกับ "เรื่องที่ถาม" ใส่เฉพาะหมวดที่ตรวจเจอ — ไม่งั้นเปลือง
     * token และเจือจางประเด็นที่ลูกค้าถามจริง
     */
    private function knowledgeBlock(FortuneKnowledgeService $kb, array $cards, string $question): string
    {
        $blocks = [];

        foreach ([
            'spreadPatternLines',
            'elementalDignityLines',
            'positionDynamicLines',
            'comboLinesForCards',
            'healthLinesForCards',
        ] as $method) {
            try {
                $line = trim((string) $kb->{$method}($cards));
                if ($line !== '') {
                    $blocks[] = $line;
                }
            } catch (\Throwable $e) {
                Log::debug("Juntra interpret: knowledge {$method} skipped", ['err' => $e->getMessage()]);
            }
        }

        // หมวดตามเรื่องที่ถาม (ความรัก/การเงิน/ฤกษ์/เลข/ของมงคล/ใจ/ครอบครัว/
        // เดินทาง/กฎหมาย/การเยียวยา) — ดึงเฉพาะที่ตรวจเจอในคำถาม
        if (trim($question) !== '') {
            foreach ([
                'detectLoveCategories', 'detectWealthCategories', 'detectAuspiciousCategories',
                'detectNumerologyCategories', 'detectLuckyItemsCategories', 'detectMentalCategories',
                'detectFamilyCategories', 'detectTravelCategories', 'detectLegalCategories',
                'detectRemedyCategories', 'detectLifeCategories', 'detectDestinyCategories',
            ] as $detector) {
                try {
                    $cats = $kb->{$detector}($question);
                    if (! empty($cats)) {
                        $line = trim((string) $kb->muLinesForCards($cards, $cats));
                        if ($line !== '') {
                            $blocks[] = $line;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::debug("Juntra interpret: detector {$detector} skipped", ['err' => $e->getMessage()]);
                }
            }
        }

        if (empty($blocks)) {
            return '';
        }

        return "📚 ตำราประกอบ (ใช้เสริมการอ่านไพ่ ห้ามลอกมาตรง ๆ):\n".implode("\n\n", $blocks);
    }

    private function systemPrompt(string $spreadName, int $cardCount): string
    {
        $brand = 'แม่หมอจันทรา';

        return <<<PROMPT
คุณคือ{$brand} หมอดูไพ่ยิปซีที่แม่นและกล้าฟันธง กำลังอ่านไพ่ "{$spreadName}" {$cardCount} ใบ
ที่เจ้าชะตาเพิ่งเปิดบนเว็บจันทราพยากรณ์ (เจ้าชะตาชำระค่าบริการเรียบร้อยแล้ว)

หลักการ:
1. อ่านจาก "หน้าไพ่ที่เปิดได้จริง × ตำแหน่ง" เท่านั้น — ห้ามทำนายลอย ๆ แบบที่ใช้กับไพ่ชุดไหนก็ได้
2. ฟันธงให้ชัด บอกว่าจะเกิดอะไร ควรทำอะไร ช่วงไหน — ห้ามเซฟตัวด้วยคำว่า "อาจจะ/แล้วแต่ตัวคุณ" ทั้งบท
3. อ้างชื่อไพ่และตำแหน่งเวลาสรุปประเด็นสำคัญ เพื่อให้เจ้าชะตาเห็นที่มา
4. ไพ่กลับหัวให้อ่านเป็นด้านกลับจริง ๆ ไม่ใช่แค่ "อ่อนลง"
5. ปิดท้ายด้วยคำแนะนำที่ลงมือทำได้จริง 2-3 ข้อ และกำลังใจอย่างอบอุ่น
6. ห้ามทำนายเรื่องความตาย การฆ่าตัวตาย หรือโรคร้ายแบบชี้ชัด — ให้แนะนำให้ดูแลตัวเอง/พบแพทย์แทน
7. ห้ามขายบริการอื่นต่อท้าย เจ้าชะตาจ่ายแล้ว
8. ตอบเป็นภาษาไทยล้วน จัดหัวข้อให้อ่านง่ายด้วย markdown (หัวข้อ **ตัวหนา** และ bullet)
PROMPT;
    }
}
