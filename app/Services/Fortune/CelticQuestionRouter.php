<?php

namespace App\Services\Fortune;

/**
 * 🎯 (2026-08-07) Celtic Question Router — เลือกไพ่ให้ตรงคำถาม
 *
 * owner directive: "ต้องฉลาดในการดึงไพ่ที่เกี่ยวข้องกับคำถาม เช่นถ้าถามอนาคต
 *   ต้องดึงไพ่ตำแหน่งอนาคตมาเป็นหลัก และดึงไพ่เสริมใบอื่นๆ มาทำนายเสริม"
 *
 * ก่อนหน้านี้ระบบ *ไม่มี* การ map คำถาม → ตำแหน่งไพ่เลย มีแค่ประโยคใน prompt
 *   ว่า "เจาะไพ่ตำแหน่งที่ตอบคำถาม" แล้วปล่อยให้โมเดลเลือกเอง
 *   (อัลกอริทึมเดียวที่มีคือ yesNoVerdict ซึ่งถ่วงน้ำหนักตำแหน่งอยู่แล้ว —
 *    ตัวนี้คือการขยายแนวคิดเดียวกันไปครอบทุกประเภทคำถาม)
 *
 * วิธีจับหมวด: keyword substring + ให้คะแนนตามความยาวคำ
 *   (คำยาว = เฉพาะเจาะจงกว่า เช่น "กลับมาไหม" ต้องชนะ "ไหม")
 *   รองรับคำถามที่ถาม 2 เรื่องพร้อมกัน (พบบ่อยมากในข้อมูลจริง)
 *
 * ⚠️ ไม่ throw — config หาย/พัง ต้องคืน default เสมอ (อยู่ใน path ที่ลูกค้าจ่ายเงินแล้ว)
 */
class CelticQuestionRouter
{
    /** @var array<string, mixed>|null */
    protected ?array $config = null;

    /**
     * วิเคราะห์คำถาม → คืนไพ่หลัก/ไพ่เสริม/สิ่งที่คำตอบต้องมี
     *
     * @return array{
     *   matched: bool,
     *   types: array<int, array{key:string,label:string,score:float,focus:string}>,
     *   primary: array<int>,
     *   support: array<int>,
     *   positions: array<int>
     * }
     */
    public function route(string $questionText): array
    {
        $cfg = $this->config();

        if (empty($cfg['enabled']) || empty($cfg['types'])) {
            return $this->fallback($cfg);
        }

        $haystack = $this->normalizeThai($questionText);
        if ($haystack === '') {
            return $this->fallback($cfg);
        }

        // ให้คะแนนทุกหมวด — แยก "หัวข้อ" ออกจาก "modifier" (รูปแบบคำถาม)
        $scored = [];
        $modifiers = [];
        foreach ($cfg['types'] as $key => $type) {
            $score = 0.0;
            foreach ((array) ($type['keywords'] ?? []) as $kw) {
                $kw = mb_strtolower((string) $kw);
                if ($kw === '' || mb_strpos($haystack, $kw) === false) {
                    continue;
                }
                // คำยาว = เฉพาะเจาะจงกว่า → คะแนนสูงกว่า
                // นับซ้ำได้ถ้าคำโผล่หลายครั้ง (ย้ำเรื่องเดิม = มั่นใจขึ้น) แต่ทอนน้ำหนักลง
                $occurrences = mb_substr_count($haystack, $kw);
                $score += mb_strlen($kw) * (1 + (min($occurrences, 3) - 1) * 0.3);
            }
            if ($score <= 0) {
                continue;
            }
            if (! empty($type['modifier'])) {
                $modifiers[$key] = $score;
            } else {
                $scored[$key] = $score;
            }
        }

        // เจอแต่ modifier (เช่น "เมื่อไหร่" เดี่ยวๆ) → ยกขึ้นเป็นหัวข้อแทน ไม่งั้นตกไป default
        if (empty($scored) && ! empty($modifiers)) {
            $scored = $modifiers;
            $modifiers = [];
        }

        if (empty($scored)) {
            return $this->fallback($cfg);
        }

        arsort($scored);
        arsort($modifiers);

        $maxTypes = max(1, (int) ($cfg['max_types_per_question'] ?? 2));
        $ratio = (float) ($cfg['secondary_score_ratio'] ?? 0.6);
        $best = (float) reset($scored);

        $types = [];
        $primary = [];
        $support = [];

        foreach ($scored as $key => $score) {
            if (count($types) >= $maxTypes) {
                break;
            }
            // หมวดรองต้องใกล้เคียงหมวดแรกพอ ไม่งั้นถือว่าเป็นคำพ้องบังเอิญ
            if (count($types) > 0 && $best > 0 && ($score / $best) < $ratio) {
                break;
            }

            $type = $cfg['types'][$key];
            $types[] = [
                'key' => (string) $key,
                'label' => (string) ($type['label'] ?? $key),
                'score' => round($score, 1),
                'focus' => (string) ($type['focus'] ?? ''),
            ];
            $primary = array_merge($primary, (array) ($type['primary'] ?? []));
            $support = array_merge($support, (array) ($type['support'] ?? []));
        }

        // modifier ผสมเข้าเสมอ (ไม่กินสล็อต) — เติมได้แค่ "ไพ่เสริม" + focus
        //   ไม่ยัดเข้า primary เพราะหัวข้อจริงต้องเป็นแกน ไม่ใช่รูปแบบคำถาม
        foreach (array_slice($modifiers, 0, 2, true) as $key => $score) {
            $type = $cfg['types'][$key];
            $types[] = [
                'key' => (string) $key,
                'label' => (string) ($type['label'] ?? $key),
                'score' => round($score, 1),
                'focus' => (string) ($type['focus'] ?? ''),
                'modifier' => true,
            ];
            $support = array_merge($support, (array) ($type['primary'] ?? []), (array) ($type['support'] ?? []));
        }

        return $this->normalize($types, $primary, $support, true);
    }

    /**
     * วิเคราะห์หลายคำถามรวมกัน (ใช้กับบทสรุปสุดท้าย — รวมทุกคำถามในรอบ)
     *
     * รวมคะแนนข้ามคำถาม แล้วคืนหมวดเด่นของทั้งรอบ + ตำแหน่งไพ่ที่ต้องใช้
     *
     * @param  array<int, string>  $questions
     */
    public function routeMany(array $questions): array
    {
        $cfg = $this->config();
        $agg = [];
        $primary = [];
        $support = [];
        $labels = [];
        $focuses = [];

        foreach ($questions as $q) {
            $r = $this->route((string) $q);
            if (! $r['matched']) {
                continue;
            }
            foreach ($r['types'] as $t) {
                $agg[$t['key']] = ($agg[$t['key']] ?? 0) + $t['score'];
                $labels[$t['key']] = $t['label'];
                $focuses[$t['key']] = $t['focus'];
            }
        }

        if (empty($agg)) {
            return $this->fallback($cfg);
        }

        arsort($agg);

        // บทสรุปครอบหลายเรื่อง — ยอมให้กว้างกว่าคำถามเดี่ยว
        $limit = max(1, (int) ($cfg['max_types_per_question'] ?? 2) + 1);
        $types = [];
        foreach (array_slice($agg, 0, $limit, true) as $key => $score) {
            $type = $cfg['types'][$key] ?? null;
            if (! $type) {
                continue;
            }
            $isModifier = ! empty($type['modifier']);
            $types[] = [
                'key' => (string) $key,
                'label' => $labels[$key] ?? $key,
                'score' => round((float) $score, 1),
                'focus' => $focuses[$key] ?? '',
                'modifier' => $isModifier,
            ];
            // modifier ในบทสรุปก็ยังเป็นแค่ตัวเสริม ไม่ใช่แกน
            if ($isModifier) {
                $support = array_merge($support, (array) ($type['primary'] ?? []), (array) ($type['support'] ?? []));
            } else {
                $primary = array_merge($primary, (array) ($type['primary'] ?? []));
                $support = array_merge($support, (array) ($type['support'] ?? []));
            }
        }

        if (empty($types)) {
            return $this->fallback($cfg);
        }

        return $this->normalize($types, $primary, $support, true);
    }

    /**
     * สร้างบล็อก prompt "ไพ่ที่ต้องอ่านตอบคำถามนี้"
     *
     * @param  array<int, array>  $cards  ไพ่ 10 ใบ (key = ตำแหน่ง 1-10)
     * @param  array  $route  ผลจาก route() / routeMany()
     * @param  bool  $isFinale  บทสรุป = ห้ามเอ่ยชื่อไพ่/ตำแหน่งออกจอ (ใช้คิดในใจ)
     */
    public function buildDirective(array $cards, array $route, bool $isFinale = false): string
    {
        // จับหมวดไม่ได้ (หรือปิดสวิตช์ใน config) → ไม่ต้องฉีดบล็อกกลางๆ ให้เปลือง prompt
        //   ปล่อยให้ prompt เดิมทำงานเหมือนก่อนมีตัวนี้
        if (empty($route['matched']) || empty($route['positions'])) {
            return '';
        }

        $names = (array) ($this->config()['position_names'] ?? []);

        $describe = function (array $positions) use ($cards, $names): string {
            $out = [];
            foreach ($positions as $pos) {
                $card = $cards[$pos] ?? null;
                if (! is_array($card)) {
                    continue;
                }
                $label = trim((string) ($card['card_name_th'] ?? '')) ?: trim((string) ($card['card_name_en'] ?? '')) ?: '?';
                $orient = ! empty($card['is_reversed']) ? 'กลับหัว' : 'ตั้งตรง';
                $posName = (string) ($names[$pos] ?? '?');
                $out[] = "ต.{$pos} [{$posName}] = {$label} ({$orient})";
            }

            return implode("\n     ", $out);
        };

        $typeLabels = implode(' + ', array_map(fn ($t) => $t['label'], $route['types']));
        $focusLines = '';
        foreach ($route['types'] as $t) {
            if (trim($t['focus']) !== '') {
                $focusLines .= "   • {$t['label']} → {$t['focus']}\n";
            }
        }

        $block = "━━━━━━━━━━━━━━━━━\n"
            ."🎯 ไพ่ที่ต้องใช้ตอบคำถามนี้ (คัดมาให้แล้ว — ห้ามอ่านเหวี่ยงแห)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ประเภทคำถาม: *{$typeLabels}*\n\n"
            ."🔴 *ไพ่หลัก — ต้องเป็นแกนของคำตอบ (น้ำหนักมากที่สุด):*\n"
            .'     '.$describe($route['primary'])."\n\n";

        // ไพ่เสริม: เอาเฉพาะที่รอดจาก cap (positions) — คำถามที่ครอบหลายเรื่อง
        //   ไพ่หลักอาจกินโควตาหมดจนไม่เหลือไพ่เสริม → ซ่อนหัวข้อไปเลย ไม่ปล่อยหัวข้อว่าง
        $supportShown = array_values(array_intersect($route['support'], $route['positions']));
        if (! empty($supportShown)) {
            $block .= "🔵 *ไพ่เสริม — ใช้ให้รายละเอียด/เงื่อนไข/ที่มา:*\n"
                .'     '.$describe($supportShown)."\n\n";
        }

        if ($focusLines !== '') {
            $block .= "✅ *คำตอบต้องมีสิ่งเหล่านี้:*\n".$focusLines."\n";
        }

        $block .= "📌 *วิธีใช้:*\n"
            ."• เริ่มจากไพ่หลัก → นั่นคือคำตอบ · ไพ่เสริม → ขยายว่า \"เพราะอะไร / ใครเกี่ยว / เมื่อไหร่\"\n"
            ."• ไพ่นอกรายการนี้ = พูดได้ *เฉพาะเมื่อมันเปลี่ยนคำตอบจริงๆ* — ไม่ใช่ไล่ให้ครบ\n"
            ."• ตั้งตรง/กลับหัว ของไพ่หลัก = ตัวชี้ขาดว่าคำตอบเป็นบวกหรือติดขัด\n";

        if ($isFinale) {
            $block .= "• ⚠️ บทสรุปนี้ *ไม่อธิบายไพ่* — ใช้รายการนี้ \"คิดในใจ\" แล้วพูดออกมาเฉพาะคำตอบ\n"
                ."  ❌ ห้ามเอ่ยชื่อไพ่/เลขตำแหน่งในบทสรุป\n";
        }

        return $block."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * ตำแหน่งไพ่ที่ควรดึงคลังความรู้ (primary + support)
     *
     * @return array<int>
     */
    public function knowledgePositions(array $route): array
    {
        return $route['positions'] ?? [];
    }

    /**
     * ✍️ ปรับข้อความให้เทียบ keyword ติด — ลูกค้าพิมพ์เร็ว/สะกดตามเสียง
     *
     * วัดจากของจริง: คำถามที่จับไม่ได้ 1,235 ข้อ ส่วนใหญ่แค่สะกด "ไหม" ผิด
     *   ("ใหม" / "มั้ย" / "มัย" / "ม้าย") หรือ "กลับมา" → "กับมา"
     *
     * ⚠️ กับดัก: "ใหม่" (new) ก็มี "ใหม" อยู่ข้างใน — ถ้าแปลงมั่วจะได้ "คนไหม่"
     *    ต้อง lookahead กันไม้เอกไว้ · ห้ามใช้ regex ตัดอักขระ (กินสระ/วรรณยุกต์ไทย)
     */
    protected function normalizeThai(string $text): string
    {
        $s = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
        if ($s === '') {
            return '';
        }

        // "ใหม" ที่ไม่ตามด้วยไม้เอก = พิมพ์เพี้ยนของ "ไหม" (ไม่ใช่ "ใหม่" ที่แปลว่า new)
        $s = (string) preg_replace('/ใหม(?!่)/u', 'ไหม', $s);

        return strtr($s, [
            'มั้ย' => 'ไหม',
            'มัย' => 'ไหม',
            'ม้าย' => 'ไหม',
            'ไม๊' => 'ไหม',
            'กับมา' => 'กลับมา',
            'กลัยมา' => 'กลับมา',
        ]);
    }

    /** @return array<string, mixed> */
    protected function config(): array
    {
        if ($this->config === null) {
            $this->config = (array) config('fortune_question_routing', []);
        }

        return $this->config;
    }

    /** @return array<string, mixed> */
    protected function fallback(array $cfg): array
    {
        $d = (array) ($cfg['default'] ?? []);
        $types = [[
            'key' => 'default',
            'label' => (string) ($d['label'] ?? '🔮 คำถามทั่วไป'),
            'score' => 0.0,
            'focus' => (string) ($d['focus'] ?? ''),
        ]];

        return $this->normalize(
            $types,
            (array) ($d['primary'] ?? [1, 10]),
            (array) ($d['support'] ?? [2, 6]),
            false
        );
    }

    /**
     * จัดระเบียบตำแหน่ง: unique + เรียง + ตัดไพ่เสริมที่ซ้ำกับไพ่หลัก
     *
     * @return array<string, mixed>
     */
    protected function normalize(array $types, array $primary, array $support, bool $matched): array
    {
        $primary = array_values(array_unique(array_map('intval', $primary)));
        sort($primary);

        $support = array_values(array_unique(array_map('intval', $support)));
        $support = array_values(array_diff($support, $primary)); // ไม่ซ้ำกับไพ่หลัก
        sort($support);

        // 🎯 เพดานจำนวนไพ่ที่จะดึงคลังความรู้ — ไพ่หลักได้ไปก่อนเสมอ ที่เหลือค่อยเติมด้วยไพ่เสริม
        //    ถ้าไม่ cap บทสรุปที่ถามหลายเรื่องจะกวาดกลับไป 10 ใบ = prompt บวมเหมือนเดิม
        $cap = (int) ($this->config()['max_positions'] ?? 6);
        $positions = $primary;
        foreach ($support as $pos) {
            if (count($positions) >= max($cap, count($primary))) {
                break;
            }
            $positions[] = $pos;
        }
        $positions = array_values(array_unique($positions));
        sort($positions);

        return [
            'matched' => $matched,
            'types' => $types,
            'primary' => $primary,
            'support' => $support,
            'positions' => $positions,
        ];
    }
}
