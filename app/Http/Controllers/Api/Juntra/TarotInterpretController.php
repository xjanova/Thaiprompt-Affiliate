<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\FortuneModelRouter;
use App\Services\Fortune\JuntraSpreadProfiles;
use App\Services\Fortune\ThaiAstrologyService;
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
            // juntraweb รุ่นก่อน 2026-09-15 ส่งชื่อนี้ — เดิมไม่อยู่ใน validate จึงหล่นหายเงียบ ๆ
            'cards.*.position_asks' => 'nullable|string|max:500',
            // 🔮 (2026-09-15) โปรไฟล์ต่อแพ็กเกจ — ชื่อเดือนจริงของ 12 เดือน / วันเกิด (ไม่บังคับ) / ชื่อที่ใช้ใน log
            'months' => 'nullable|array|max:'.self::MAX_CARDS,
            'months.*' => 'nullable|string|max:40',
            'birth_date' => 'nullable|date_format:Y-m-d|before:today|after:1900-01-01',
            'customer_name' => 'nullable|string|max:80',
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

        // 🔮 (2026-09-15) แพ็กเกจที่มีโปรไฟล์ → อ่านตามวิธีของแพ็กเกจนั้น บนเลนทำนายเดียวกับบอท 39/99
        //    (แพ็กเกจที่ไม่รู้จัก / juntraweb รุ่นเก่า → ทางเดิมด้านล่าง ไม่พัง)
        $profile = JuntraSpreadProfiles::get($data['spread_key'] ?? null);
        if ($profile !== null) {
            return $this->profileReading($profile, $data, $cards, $ai, $kb);
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
     * 🔮 (2026-09-15) ทำนายตามโปรไฟล์ของแพ็กเกจ — เลนทำนายเดียวกับบอท (system role ของ 39/99 + override ไพ่เว็บ)
     *
     * @param  array<string,mixed>  $profile  JuntraSpreadProfiles::get()
     * @param  array<int,array<string,mixed>>  $cards
     */
    private function profileReading(array $profile, array $data, array $cards, FortuneAIService $ai, FortuneKnowledgeService $kb): JsonResponse
    {
        $question = trim((string) ($data['question'] ?? ''));

        // ด่านกันเจลเบรค — ทางนี้ไม่ผ่าน chatWithCustomSystemPrompt จึงต้องตรวจเอง (เฉพาะคำถามดิบของลูกค้า)
        //   ปฏิเสธ (ไม่ใช่คำตอบสำเร็จรูป) — juntraweb จะคืนเงิน ลูกค้าไม่ต้องจ่ายเพื่อได้ข้อความปัด
        if ($question !== '' && ($attack = FortuneAIService::detectAdversarialInput($question)) !== null) {
            Log::warning('Juntra tarot profile: question rejected by adversarial guard', ['attack' => $attack, 'profile' => $profile['key']]);

            return response()->json(['message' => 'คำถามนี้แม่หมอทำนายให้ไม่ได้', 'reason_code' => 'question_rejected'], 422);
        }

        $blocks = ['knowledge' => $this->knowledgeBlock($kb, $cards, $question)];

        if (in_array('black_magic', $profile['knowledge'], true)) {
            $bm = '';
            try {
                $bm = trim((string) $kb->blackMagicSignalLinesForCards($cards));
            } catch (\Throwable $e) {
                Log::debug('Juntra tarot profile: black magic signals skipped', ['err' => $e->getMessage()]);
            }
            // ว่าง = ไม่มีใบไหนชี้ว่ามีของ — ต้องบอกโมเดลตรง ๆ ไม่งั้นมันปั้นเอง (fear-anchoring)
            $blocks['knowledge'] = trim($blocks['knowledge']."\n\n🪬 คลังสัญญาณไสยศาสตร์ (เฉพาะใบที่ส่งสัญญาณจริงตามทิศที่เปิด):\n"
                .($bm !== '' ? $bm : 'ไม่มีไพ่ใบไหนในสำรับนี้ส่งสัญญาณของ/คุณไสย์ → ผลวินิจฉัยต้องเป็น "พบของ: ไม่ใช่" หรือ "ไม่ชัด" ห้ามปั้น'));
        }

        $multipliers = JuntraSpreadProfiles::yesNoMultipliers($profile);
        if ($multipliers !== null && $question !== '' && self::looksYesNo($question)) {
            $yn = $kb->yesNoVerdictFor($cards, $multipliers);
            if ($yn !== '') {
                $blocks['yes_no'] = "🎯 ตาชั่งใช่/ไม่ใช่ (น้ำหนักไพ่ × ตำแหน่ง — เข็มทิศสำหรับบรรทัด \"ผล:\" ❌ ห้ามพิมพ์ตัวเลขให้ลูกเห็น):\n".$yn;
            }
        }

        if ($profile['birth'] && ! empty($data['birth_date'])) {
            [$y, $m, $d] = array_map('intval', explode('-', $data['birth_date']));
            try {
                $birth = trim((new ThaiAstrologyService)->buildCelticBirthAstrologyBlock("เกิด {$d}/{$m}/{$y}"));
            } catch (\Throwable $e) {
                $birth = '';
                Log::info('Juntra tarot profile: birth chart skipped', ['err' => $e->getMessage()]);
            }
            if ($birth !== '') {
                $blocks['birth'] = "🌠 ดวงดาวจากวันเกิดของลูก (ใช้เสริมจังหวะเวลา/จุดแข็ง-จุดอ่อน ห้ามให้ดวงกลบคำตอบของไพ่):\n".$birth;
            }
        }

        $months = $profile['key'] === 'year'
            ? array_values(array_filter(array_map(fn ($m) => trim((string) $m), (array) ($data['months'] ?? [])), fn ($m) => $m !== ''))
            : [];
        if ($profile['key'] === 'year' && count($months) !== count($cards)) {
            // ไม่มีชื่อเดือนจริงมาครบ — ใช้ "เดือนที่ N" ดีกว่าเดาปฏิทินเอง
            $months = array_map(fn ($i) => 'เดือนที่ '.$i, range(1, count($cards)));
        }

        $prompt = JuntraSpreadProfiles::userPrompt($profile, $cards, $question, $months, $blocks);

        try {
            $ai->withReadingOverride(JuntraSpreadProfiles::systemOverride($profile, count($cards)))
                // หน้าเว็บรอได้ ~55 วิ — หยุดวนลอง key ก่อนนั้น (บอทรอได้ 150 วิ)
                ->withConfigOverride(['max_tokens' => $profile['max_tokens'], 'temperature' => 0.6, 'budget_sec' => 50])
                ->withCustomerContext(array_filter(['customer_name' => $data['customer_name'] ?? null]));

            $result = $ai->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: 'jw_tarot:'.$profile['key'],
                purpose: $profile['purpose'],
                // 🪬 คุณไสย์ → โมเดลตัวแรงทุกครั้ง (นโยบายเดียวกับบอท — FortuneModelRouter)
                modelOverrides: $profile['strong_model'] ? ['openai' => FortuneModelRouter::MODEL_STRONG] : null,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra tarot profile reading failed', ['profile' => $profile['key'], 'err' => $e->getMessage()]);

            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        $text = self::cleanReading((string) ($result['response'] ?? ''));
        if ($text === '') {
            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }

        return response()->json(['data' => [
            'interpretation' => $text,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? null,
            'knowledge_used' => trim($blocks['knowledge']) !== '',
            'profile' => $profile['key'],
            // หัวข้อครบตามสัญญาไหม — เว็บใช้จัดการ์ด (ไม่ครบ = เว็บแสดงเป็นข้อความธรรมดา)
            'format_ok' => str_contains($text, '## '.JuntraSpreadProfiles::HEADINGS[$profile['key'] === 'kunsai' ? 'diagnosis' : 'verdict']),
        ]]);
    }

    /** คำถามใช่/ไม่ใช่ไหม — คำชี้ชุดเดียวกับ CelticCrossService::buildYesNoDirective */
    private static function looksYesNo(string $q): bool
    {
        foreach (['หรือเปล่า', 'รึเปล่า', 'หรือไม่', 'ใช่ไหม', 'ใช่มั้ย', 'ได้ไหม', 'ได้มั้ย', 'ดีไหม', 'ดีมั้ย', 'ควรไหม', 'ควรมั้ย',
            'เขาจะ', 'เค้าจะ', 'เธอจะ', 'จะกลับ', 'จะรัก', 'จะแต่ง', 'จะเลิก', 'จะได้', 'จะรวย', 'จะติด', 'จะผ่าน', 'ไหม', 'มั้ย'] as $kw) {
            if (mb_strpos($q, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตัดของที่โมเดลชอบแถมมา: ป้าย [TYPE:x] และคำเกริ่นก่อนหัวข้อแรก (ถ้าสั้น — ยาวอาจเป็นเนื้อหาจริง ไม่ตัด)
     */
    private static function cleanReading(string $text): string
    {
        $text = trim((string) preg_replace('/[`*]{0,3}[\[\【\［]?\s*TYPE\s*[:：]\s*[A-E]\s*[\]\】\］]?[`*]{0,3}/iu', '', $text));
        $first = mb_strpos($text, '## ');
        if ($first !== false && $first > 0 && $first < 300) {
            $text = mb_substr($text, $first);
        }

        return trim($text);
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
                'position_description' => (string) ($c['position_description'] ?? $c['asks'] ?? $c['position_asks'] ?? ''),
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
