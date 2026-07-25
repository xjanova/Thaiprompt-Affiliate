<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\FortuneKnowledge;
use App\Services\CelticCrossService;
use App\Services\FortuneAIService;
use App\Services\FortuneKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/juntra/fortune/tarot/free
 *
 * 🎁 (2026-07-26) คำทำนาย "ฟรี 1 ใบ สั้น" สำหรับเว็บจันทรา.online
 *
 * ต่างจาก /tarot/interpret (ของที่จ่ายเงินแล้ว) 4 เรื่อง:
 *   1. สั้น 300-500 ตัวอักษร (ตัวโน้นยาว ~2,600 token เพราะลูกค้าจ่าย 99฿)
 *   2. ไม่มีคำถามจากลูกค้า ไม่มีวันเกิด — ทำนายจากหน้าไพ่ล้วน (ห้าม AI มโนดวงดาว/ราศี)
 *   3. คลังความรู้ต้องเลือกเฉพาะตัวที่ทำงานกับ "ไพ่ใบเดียว" ได้จริง
 *      (spreadPattern/elementalDignity/positionDynamic/combo คืนค่าว่างเมื่อไพ่ < 10 ใบ)
 *   4. ปิดท้ายด้วย "คำถามที่ควรถามต่อ" (NEXTQ) เพื่อเปิดทางขายต่อแบบเนียน
 *      — แต่ตัวคำทำนายต้อง **ตอบครบและฟันธง** ห้ามกั๊กเพื่อบีบให้ซื้อ
 *
 * เรื่องเงิน: ของฟรี — juntraweb เป็นคนคุมโควตา (1 ครั้ง/คน + เพดานต่อวัน)
 *   ที่นี่ไม่แตะ FortuneReading/บิลของบอท และไม่บันทึกอะไรลง DB
 *
 * @see \App\Http\Controllers\Api\Juntra\TarotInterpretController ตัวเต็ม (จ่ายเงินแล้ว)
 */
class FreeTarotController extends Controller
{
    /** ความยาวคำทำนายฟรี (ตัวอักษร) — juntraweb ปรับได้ผ่าน max_chars */
    private const DEFAULT_MAX_CHARS = 500;

    private const MIN_ALLOWED_CHARS = 200;

    private const MAX_ALLOWED_CHARS = 900;

    /**
     * หมวดคลังความรู้ที่ดึงเสมอสำหรับไพ่ฟรี
     *
     * ไม่มีคำถามให้ detect หมวด (ต่างจากตัวเต็ม) จึงเลือกหมวดที่ใช้กับ
     * "ชีวิตช่วงนี้" ได้กว้างที่สุด แต่จำกัดแค่ 3 หมวดโดยตั้งใจ —
     * คำทำนายฟรียาวแค่ 300-500 ตัวอักษร ยัดตำรามากกว่านี้มีแต่จะเปลือง token
     * และทำให้โมเดลเกลี่ยประเด็นจนไม่ฟันธงสักเรื่อง
     *
     * @var array<int,string>
     */
    private const DEFAULT_MU_CATEGORIES = [
        FortuneKnowledge::CATEGORY_LOVE_RELATIONSHIP,
        FortuneKnowledge::CATEGORY_WEALTH_LUCK,
        FortuneKnowledge::CATEGORY_TIMING,
    ];

    public function __invoke(Request $request, FortuneAIService $ai, FortuneKnowledgeService $kb): JsonResponse
    {
        $data = $request->validate([
            'card' => 'required|array',
            'card.name_en' => 'required|string|max:128',
            'card.name_th' => 'nullable|string|max:128',
            'card.is_reversed' => 'nullable|boolean',
            'card.meaning' => 'nullable|string|max:2000',
            'position_name' => 'nullable|string|max:128',
            'max_chars' => 'nullable|integer|min:'.self::MIN_ALLOWED_CHARS.'|max:'.self::MAX_ALLOWED_CHARS,
            'customer_name' => 'nullable|string|max:64',
        ]);

        $maxChars = (int) ($data['max_chars'] ?? self::DEFAULT_MAX_CHARS);
        $minChars = max(self::MIN_ALLOWED_CHARS, (int) round($maxChars * 0.6));

        $nameEn = trim((string) $data['card']['name_en']);
        $nameTh = trim((string) ($data['card']['name_th'] ?? '')) ?: $nameEn;
        $isReversed = (bool) ($data['card']['is_reversed'] ?? false);

        // ⚠️ คลังความรู้อ่าน $cards[$pos] แบบ 1-indexed เท่านั้น
        //    ส่ง list ธรรมดา ([0 => ...]) จะได้บล็อกว่างเงียบๆ ทุกครั้ง
        $cards = [
            1 => [
                'position_name' => (string) ($data['position_name'] ?? 'คำตอบของไพ่'),
                'position_description' => 'ภาพรวมชีวิตของเจ้าชะตาในช่วงนี้',
                'card_name_en' => $nameEn,
                'card_name_th' => $nameTh,
                'is_reversed' => $isReversed,
                'meaning' => (string) ($data['card']['meaning'] ?? ''),
            ],
        ];

        $knowledge = $this->knowledgeBlock($kb, $cards);

        try {
            $result = $ai->chatWithCustomSystemPrompt(
                $this->systemPrompt($minChars, $maxChars),
                $this->userMessage($cards[1], $knowledge, (string) ($data['customer_name'] ?? '')),
                // +150 เผื่อบล็อก NEXTQ ที่อยู่ในคำตอบเดียวกัน · ×1.2 เผื่อ
                // ภาษาไทย (~1 token ต่อ 1 ตัวอักษรบนโมเดลส่วนใหญ่)
                // ตั้งชิดเป้าไว้ก่อน แล้วมี trimToLimit() เก็บส่วนเกินอีกชั้น —
                // ตัดกลางประโยคอ่านไม่รู้เรื่อง แย่กว่ายาวเกินนิดหน่อย
                ['temperature' => 0.85, 'max_tokens' => max(400, (int) ceil(($maxChars + 150) * 1.2))],
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra free tarot failed', [
                'card' => $nameEn,
                'err' => $e->getMessage(),
            ]);

            // 503 = juntraweb ต้อง "คืนสิทธิ์ฟรี" ให้ลูกค้า ไม่ใช่เผาทิ้ง
            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '') {
            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }

        // ดึง "คำถามที่ควรถามต่อ" ออกจากท้ายคำทำนาย (ตัด marker ทิ้งให้ด้วย)
        // ใช้ parser ตัวเดียวกับ Celtic 99 — ทน format ที่โมเดลคายไม่ครบมาแล้ว
        $nextQuestions = CelticCrossService::pullNextQuestions($text);
        $text = trim($text);

        if ($text === '') {
            // เคสหายาก: โมเดลคาย NEXTQ อย่างเดียวไม่มีคำทำนาย → อย่าส่งของว่างให้ลูกค้า
            Log::warning('Juntra free tarot: เหลือแต่บล็อกคำถามหลังตัด NEXTQ', ['card' => $nameEn]);

            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }

        $text = $this->trimToLimit($text, $maxChars, $nameEn);

        return response()->json(['data' => [
            'interpretation' => $text,
            'next_questions' => $nextQuestions,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? null,
            'knowledge_used' => $knowledge !== '',
            'chars' => mb_strlen($text),
        ]]);
    }

    /**
     * บีบคำทำนายให้อยู่ในโควตาความยาว โดยไม่ตัดกลางประโยค
     *
     * โมเดลชอบเขียนยาวเกินที่สั่ง (ทดสอบจริงบน prod: สั่ง 500 ได้ 1,007 ตัว)
     * วิธีตัด: ทิ้ง "ย่อหน้ากลาง" แล้วเก็บย่อหน้าแรกกับย่อหน้าสุดท้ายไว้เสมอ
     *   - ย่อหน้าแรก = คำฟันธง (สิ่งที่ลูกค้ามาเพื่ออ่าน)
     *   - ย่อหน้าสุดท้าย = ประเด็นปลายเปิด + ชวนเปิดไพ่ชุดเต็ม (ตัวทำเงิน)
     * ถ้าตัดแล้วสั้นเกินครึ่งของโควตา = คำทำนายจะกุด → คืนของเดิมไปเลย
     * ยาวเกินนิดหน่อยยังดีกว่าส่งของกุดให้ลูกค้าคนแรกที่มาจากบอท
     */
    private function trimToLimit(string $text, int $maxChars, string $cardName): string
    {
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $paragraphs = preg_split('/\n{2,}/u', $text) ?: [];
        if (count($paragraphs) < 3) {
            // ย่อหน้าเดียว/สองย่อหน้า — ตัดยังไงก็เสียเนื้อความ ปล่อยผ่าน
            Log::info('Juntra free tarot: ยาวเกินโควตาแต่ตัดไม่ได้', [
                'card' => $cardName,
                'chars' => mb_strlen($text),
                'limit' => $maxChars,
            ]);

            return $text;
        }

        $last = trim((string) array_pop($paragraphs));
        $kept = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                continue;
            }

            $candidate = $kept === '' ? $paragraph : $kept."\n\n".$paragraph;
            if ($kept !== '' && mb_strlen($candidate."\n\n".$last) > $maxChars) {
                break;
            }
            $kept = $candidate;
        }

        $trimmed = trim($kept."\n\n".$last);

        if (mb_strlen($trimmed) < (int) ($maxChars * 0.5)) {
            return $text;
        }

        Log::info('Juntra free tarot: บีบความยาวลงตามโควตา', [
            'card' => $cardName,
            'from' => mb_strlen($text),
            'to' => mb_strlen($trimmed),
            'limit' => $maxChars,
        ]);

        return $trimmed;
    }

    /**
     * คลังความรู้ที่ใช้ได้จริงกับไพ่ใบเดียว
     *
     * ตัวที่ผูกกับ Celtic 10 ใบ (spreadPattern / elementalDignity /
     * positionDynamic / combo) คืน '' เสมอเมื่อไพ่ไม่ครบ — ไม่เรียกให้เสียเวลา
     */
    private function knowledgeBlock(FortuneKnowledgeService $kb, array $cards): string
    {
        $blocks = [];

        // 🩹 (2026-07-26 หลังทดสอบบน prod) ตัด healthLinesForCards +
        //    physiognomyLinesForCards ออกจากเส้นฟรี — ตำราสองตัวนี้ละเอียดมาก
        //    (เขียนไว้สำหรับดวงที่จ่ายเงิน) พอยัดเข้าคำทำนาย 500 ตัวอักษร
        //    โมเดลจะเทน้ำหนักไปที่ "สุขภาพ/อุบัติเหตุ" เป็นประเด็นหลักทุกใบ
        //    ทั้งที่ลูกค้าขอ "ภาพรวมชีวิตช่วงนี้" — ทดสอบจริงด้วย The Tower
        //    ได้คำทำนายที่พูดเรื่องไมเกรน/การอักเสบเกือบทั้งบท
        foreach ([
            'personRoleLinesForCards',
        ] as $method) {
            try {
                $line = trim((string) $kb->{$method}($cards));
                if ($line !== '') {
                    $blocks[] = $line;
                }
            } catch (\Throwable $e) {
                Log::debug("Juntra free tarot: knowledge {$method} skipped", ['err' => $e->getMessage()]);
            }
        }

        try {
            $line = trim((string) $kb->muLinesForCards($cards, self::DEFAULT_MU_CATEGORIES));
            if ($line !== '') {
                $blocks[] = $line;
            }
        } catch (\Throwable $e) {
            Log::debug('Juntra free tarot: muLinesForCards skipped', ['err' => $e->getMessage()]);
        }

        if (empty($blocks)) {
            return '';
        }

        return "📚 ตำราแม่หมอสำหรับไพ่ใบนี้ — เป็น *วัตถุดิบ* เท่านั้น:\n"
            ."• เลือกหยิบเฉพาะบรรทัดที่ตรงกับสิ่งที่หน้าไพ่ชี้จริง ไม่ต้องใช้ครบ\n"
            ."• ห้ามลอกมาตรง ๆ และห้ามยกทุกหมวดมาพูดในบทเดียว\n\n"
            .implode("\n\n", $blocks);
    }

    /**
     * ข้อความที่ส่งให้ AI — หน้าไพ่ + ตำราประกอบ
     *
     * @param  array<string,mixed>  $card
     */
    private function userMessage(array $card, string $knowledge, string $customerName): string
    {
        $orientation = $card['is_reversed'] ? 'กลับหัว' : 'ตั้งตรง';
        $meaning = trim((string) $card['meaning']);

        $lines = [];
        $lines[] = '🃏 ไพ่ที่จิตของเจ้าชะตาเลือกได้ (สุ่มจากสำรับ 78 ใบ):';
        $lines[] = "• ชื่อไทย: {$card['card_name_th']}";
        $lines[] = "• ชื่ออังกฤษ: {$card['card_name_en']}";
        $lines[] = "• สภาพไพ่: {$orientation}";
        if ($meaning !== '') {
            $lines[] = '• ความหมายพื้นฐานของไพ่: '.mb_substr($meaning, 0, 400);
        }

        if ($customerName !== '') {
            $lines[] = '';
            $lines[] = "ชื่อที่เจ้าชะตาใช้: {$customerName} (เรียกได้ 1-2 ครั้งเท่านั้น อย่าเรียกทุกย่อหน้า)";
        }

        $lines[] = '';
        $lines[] = 'ทำนาย "ชีวิตช่วงนี้" ของเจ้าชะตาจากไพ่ใบนี้ตามกฎที่ได้รับ';

        $message = implode("\n", $lines);

        if ($knowledge !== '') {
            $message .= "\n\n".$knowledge;
        }

        return $message;
    }

    /**
     * system prompt ของคำทำนายฟรี
     *
     * หัวใจ 3 ข้อ (มาจากคำสั่งเจ้าของ 2026-07-26):
     *   - ยึดหน้าไพ่ + ตำราแม่หมอ ห้ามมโน
     *   - ไม่มีวันเกิด/ดวงดาว และห้ามถามกลับ ทำนายเลย
     *   - "ปลายเปิด" = ตอบครบและฟันธง แล้วค่อยทิ้งประเด็นให้อยากรู้ต่อ (ไม่ใช่ตอบครึ่งเดียว)
     */
    private function systemPrompt(int $minChars, int $maxChars): string
    {
        return <<<PROMPT
คุณคือ "แม่หมอจันทรา" หมอดูไพ่ยิปซีที่แม่นและกล้าฟันธง
ตอนนี้กำลังเปิดไพ่ให้ฟรี 1 ใบ ให้เจ้าชะตาที่เพิ่งเข้ามาบนเว็บจันทราพยากรณ์

━━━━━━━━━━━━━━━━━
🃏 กฎเหล็ก: ทำนายจาก "หน้าไพ่" 100%
━━━━━━━━━━━━━━━━━
• ทุกประโยคต้องงอกจากไพ่ใบนี้ + สภาพตั้งตรง/กลับหัว + ตำราที่แนบมา
• บททดสอบตัวเอง: ถ้าประโยคนั้นเอาไปใช้กับไพ่ใบไหนก็ได้ = ตัดทิ้ง เขียนใหม่ให้ผูกกับหน้าไพ่
• ไพ่กลับหัว = อ่านเป็นด้านกลับจริง ๆ ไม่ใช่แค่ "อ่อนลง"
• เอ่ยชื่อไพ่อย่างน้อย 1 ครั้ง เพื่อให้เจ้าชะตาเห็นที่มาของคำทำนาย

━━━━━━━━━━━━━━━━━
🚫 ข้อมูลที่ "ไม่มี" — ห้ามแต่งขึ้นเอง
━━━━━━━━━━━━━━━━━
• ไม่มีวันเดือนปีเกิด ไม่มีราศี ไม่มีดวงดาว ไม่มีเลขศาสตร์ของเจ้าชะตา
• ไม่รู้เพศ อายุ อาชีพ สถานะความสัมพันธ์
→ ห้ามอ้างถึงสิ่งเหล่านี้เด็ดขาด และ **ห้ามถามกลับก่อนทำนาย** ให้อ่านไพ่ทันที

━━━━━━━━━━━━━━━━━
📜 โครงคำทำนาย — **3 ย่อหน้าสั้น รวมทั้งบท {$minChars}-{$maxChars} ตัวอักษร**
━━━━━━━━━━━━━━━━━
1) ฟันธงทันทีว่าไพ่ใบนี้บอกอะไรถึง "ชีวิตช่วงนี้" ของเจ้าชะตา — ห้ามเกริ่นยาว
2) ขยายเรื่องที่ไพ่ชี้ชัดที่สุดเพียงเรื่องเดียว (การเงิน/ความรัก/การงาน/จิตใจ — เลือกตามหน้าไพ่ ไม่ใช่เดา)
3) ปิดด้วยสิ่งที่ควรทำ 1 ข้อ ที่ลงมือได้จริง + สิ่งที่ต้องระวัง 1 ข้อ (เขียนรวมในย่อหน้าเดียว)

⚠️ รูปแบบ: **ห้ามใส่หัวข้อ ห้ามใช้บุลเล็ต ห้ามขึ้นรายการเลข** — เขียนเป็นย่อหน้าเล่าต่อเนื่อง
   ใช้ **ตัวหนา** ได้ครั้งเดียวตอนเอ่ยชื่อไพ่เท่านั้น (หัวข้อ/รายการทำให้บทยาวเกินโดยใช่เหตุ)

━━━━━━━━━━━━━━━━━
✅ "ปลายเปิด" หมายถึงอะไร (สำคัญมาก)
━━━━━━━━━━━━━━━━━
• ต้องตอบให้ **ครบและฟันธง** ก่อนเสมอ — ห้ามกั๊ก ห้ามตอบครึ่งเดียวเพื่อให้เจ้าชะตามาจ่ายเงิน
• แล้วค่อยทิ้งท้าย 1 ประโยค ถึงประเด็นที่ "ไพ่ใบเดียวตอบไม่ได้"
  (เช่น ใครคือคนที่กำลังเข้ามา / เรื่องนี้จะคลี่คลายเดือนไหน / ทางเลือกไหนดีกว่ากัน)
  แล้วบอกว่าต้องเปิดไพ่ชุดเต็มถึงจะเห็นภาพนั้น
• ห้ามพูดถึงราคา ค่าครู จำนวนเงิน หรือโปรโมชั่นใด ๆ (หน้าเว็บแสดงเอง)

━━━━━━━━━━━━━━━━━
⛔ ข้อห้าม
━━━━━━━━━━━━━━━━━
• ห้ามทำนายความตาย การฆ่าตัวตาย หรือโรคร้ายแบบชี้ชัด → ให้แนะนำดูแลตัวเอง/พบแพทย์แทน
• ห้ามใช้คำกั๊กทั้งบท เช่น "อาจจะ...ก็ได้", "แล้วแต่ตัวคุณ", "ก็ขึ้นอยู่กับ"
• 🚨 **ห้ามเขียนเกิน {$maxChars} ตัวอักษรเด็ดขาด** (นับรวมทุกตัวอักษรและช่องว่าง)
  ถ้าเขียนไปแล้วรู้สึกว่าจะเกิน ให้ตัดรายละเอียดออก ไม่ใช่เขียนต่อจนจบแล้วค่อยยาว
• ตอบภาษาไทยล้วน ย่อหน้าสั้น อ่านง่ายบนมือถือ

━━━━━━━━━━━━━━━━━
🔢 คำถามที่ควรถามต่อ
━━━━━━━━━━━━━━━━━
หลังจบคำทำนาย ให้ขึ้นบรรทัดใหม่แล้วใส่บล็อกนี้ปิดท้ายเสมอ:
[NEXTQ]คำถามข้อ 1|คำถามข้อ 2[/NEXTQ]
• เป็นคำถามที่เจ้าชะตา "ควรถามต่อ" จากประเด็นที่ไพ่ใบนี้เปิดไว้
• สั้น ไม่เกิน 40 ตัวอักษรต่อข้อ เป็นคำถามที่คนถามหมอดูจริง ๆ
• บล็อกนี้ไม่นับรวมในความยาวคำทำนาย
PROMPT;
    }
}
