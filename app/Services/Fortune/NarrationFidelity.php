<?php

namespace App\Services\Fortune;

/**
 * 🎬 (2026-11-04) ตัวตรวจว่าบทเล่าคำทำนาย "ไม่แต่งเพิ่ม" — ตรวจก่อนส่งสร้างเสียง (ไม่เสียเงินกับบทที่ผิด)
 *
 * หลัก: คำทำนายคือความจริง · สิ่งที่บทพูดต้องหาเจอในคำทำนาย ถ้าไม่เจอ = AI แต่ง
 *   - บท card:n เอ่ยไพ่ใบอื่นของคำทำนาย / พูดหัว-กลับหัวสลับ
 *   - บท month:n เอ่ยเดือนอื่น · บทอื่นเอ่ยเดือนที่ไม่มีในคำทำนาย
 *   - ตัวเลข ทิศ สี ราศี วัน ธาตุ ที่ไม่มีในคำทำนาย (บทพื้นดวงต้องอยู่ในหัวข้อ 🌠 เท่านั้น)
 *   - ฟันธงกลับข้าง (ใช่/ไม่ใช่ · ทางเลือกที่ไพ่เลือก)
 *   - คุณไสย: ผลพบของ/ไม่พบ/ไม่ชัด ไม่ตรงตารางวินิจฉัย
 *   - ราคา/ยอดเงิน · บุษบาเรียกลูกค้าว่า "ลูก"
 *
 * ⚠️ ชื่อไพ่ไทยหลายใบเป็นคำทั่วไป (พลัง โลก ดวงดาว คู่รัก) → ตรวจเฉพาะไพ่ "ในคำทำนายนี้"
 *    และคำที่หัวข้อนั้นพูดเองได้เสมอ — ไม่ไล่จับชื่อไพ่ทั้งสำรับ (จับผิดเยอะกว่าจับถูก)
 */
final class NarrationFidelity
{
    /** ต้นคำของเดือน (พูดสั้น "พฤษภา" หรือเต็ม "พฤษภาคม" ก็เจอ) */
    public const MONTH_STEMS = ['มกรา', 'กุมภา', 'มีนา', 'เมษา', 'พฤษภา', 'มิถุนา', 'กรกฎา', 'สิงหา', 'กันยา', 'ตุลา', 'พฤศจิกา', 'ธันวา'];

    private const DIRECTIONS = 'ตะวันออกเฉียงเหนือ|ตะวันออกเฉียงใต้|ตะวันตกเฉียงเหนือ|ตะวันตกเฉียงใต้|ตะวันออก|ตะวันตก|อีสาน|อาคเนย์|พายัพ|หรดี|เหนือ|ใต้';

    private const COLORS = 'น้ำเงิน|น้ำตาล|บานเย็น|กรมท่า|เหลือง|เขียว|ม่วง|ชมพู|แดง|ส้ม|ฟ้า|ขาว|ดำ|เทา|ทอง|เงิน|ครีม|แสด|คราม';

    private const ZODIACS = 'เมษ|พฤษภ|เมถุน|กรกฎ|สิงห์|กันย์|ตุลย์|พิจิก|ธนู|มังกร|กุมภ์|มีน';

    private const DAYS = 'อาทิตย์|จันทร์|อังคาร|พุธ|พฤหัส|ศุกร์|เสาร์';

    private const ELEMENTS = 'ดิน|น้ำ|ไฟ|ลม';

    /**
     * ctx.section = หัวข้อของบทนี้ (card/month/birth) · null = บทภาพรวม
     *
     * @param  array{anchor:string,say:string}  $chapter
     * @param  array{persona:string,reading:string,outline:array,cards:array<int,array{name:string,reversed:bool}>,section:?array}  $ctx
     * @return list<string> เหตุที่ไม่ผ่าน (ว่าง = ผ่าน)
     */
    public static function check(array $chapter, array $ctx): array
    {
        $say = JuntraVoiceLibrary::plain((string) ($chapter['say'] ?? ''));
        if (mb_strlen($say) < 4) {
            return ['บทว่าง'];
        }

        $anchor = (string) $chapter['anchor'];
        $reading = self::normalize($ctx['reading']);
        $section = $ctx['section'] ?? null;
        $own = $section !== null ? self::normalize($section['title']."\n".$section['body']) : $reading;
        $sayN = self::normalize($say);
        $reasons = [];

        if (preg_match(VoicePlanParser::PRICE_PATTERN, $sayN)) {
            $reasons[] = 'พูดราคา/ยอดเงิน';
        }

        // ตัวเลขทุกชุดต้องมีในคำทำนาย
        preg_match_all('/\d+/u', $sayN, $nums);
        foreach (array_unique($nums[0]) as $n) {
            if (! preg_match('/(?<!\d)'.$n.'(?!\d)/u', $reading)) {
                $reasons[] = "ตัวเลข {$n} ไม่มีในคำทำนาย";
            }
        }

        foreach (self::mentions('/ทิศ\s*('.self::DIRECTIONS.')/u', $sayN) as $dir) {
            if (! str_contains($reading, $dir)) {
                $reasons[] = "ทิศ{$dir} ไม่มีในคำทำนาย";
            }
        }
        foreach (self::mentions('/สี\s*('.self::COLORS.')/u', $sayN) as $color) {
            if (! preg_match('/สี\s*'.$color.'/u', $reading)) {
                $reasons[] = "สี{$color} ไม่มีในคำทำนาย";
            }
        }

        // พื้นดวง: ราศี/วัน/ธาตุ ต้องอยู่ในหัวข้อของบทนั้น (บทอื่น: ในคำทำนาย)
        foreach ([['ราศี', self::ZODIACS], ['วัน', self::DAYS], ['ธาตุ', self::ELEMENTS]] as [$prefix, $list]) {
            foreach (self::mentions('/'.$prefix.'\s*('.$list.')/u', $sayN) as $word) {
                if (! preg_match('/'.$prefix.'\s*'.$word.'/u', $own)) {
                    $reasons[] = "{$prefix}{$word} ไม่ตรงคำทำนาย";
                }
            }
        }

        // เดือน: บท month:n ต้องเป็นเดือนของบทนั้น · บทอื่นต้องเป็นเดือนที่มีในคำทำนาย
        foreach (self::mentions('/('.implode('|', self::MONTH_STEMS).')/u', $sayN) as $month) {
            $scope = str_starts_with($anchor, 'month:') ? $own : $reading;
            if (! str_contains($scope, $month)) {
                $reasons[] = "เดือน{$month} ไม่ตรงคำทำนาย";
            }
        }

        if (str_starts_with($anchor, 'card:')) {
            $reasons = [...$reasons, ...self::checkCard((int) substr($anchor, 5), $sayN, $own, $ctx['cards'])];
        }

        if ($anchor === 'summary') {
            $reasons = [...$reasons, ...self::checkVerdict($sayN, $ctx['outline'])];
        }

        $reasons = [...$reasons, ...self::checkKunsai($sayN, $ctx['outline']['kunsai'] ?? null)];

        // บุษบาเรียกลูกค้าว่า "คุณ" — "ลูก" ที่ไม่ใช่ ลูกค้า/ลูกของคุณ/ลูกชาย/ลูกสาว = ผิดห้อง
        if (($ctx['persona'] ?? '') === 'busaba' && preg_match('/ลูก(?!ค้า|ของ|ชาย|สาว|หลาน|น้อง)/u', $sayN)) {
            $reasons[] = 'บุษบาเรียกลูกค้าว่าลูก';
        }

        return array_values(array_unique($reasons));
    }

    /** ไพ่ใบอื่นของคำทำนาย + หัว/กลับหัว · คำที่หัวข้อพูดเองได้เสมอ */
    private static function checkCard(int $pos, string $say, string $own, array $cards): array
    {
        $reasons = [];
        $mine = $cards[$pos] ?? null;
        $mineName = self::normalize((string) ($mine['name'] ?? ''));

        foreach ($cards as $p => $card) {
            $name = self::normalize((string) ($card['name'] ?? ''));
            if ($p === $pos || mb_strlen($name) < 2 || ($mineName !== '' && str_contains($mineName, $name))) {
                continue;
            }
            if (str_contains($say, $name) && ! str_contains($own, $name)) {
                $reasons[] = "เอ่ยไพ่ {$name} ซึ่งเป็นใบที่ {$p}";
            }
        }

        if ($mine !== null) {
            $reversed = (bool) ($mine['reversed'] ?? false);
            if (! $reversed && str_contains($say, 'กลับหัว') && ! str_contains($own, 'กลับหัว')) {
                $reasons[] = 'พูดว่ากลับหัว แต่ไพ่ตั้งตรง';
            }
            if ($reversed && preg_match('/ตั้งตรง|หัวตั้ง/u', $say) && ! preg_match('/ตั้งตรง|หัวตั้ง/u', $own)) {
                $reasons[] = 'พูดว่าตั้งตรง แต่ไพ่กลับหัว';
            }
        }

        return $reasons;
    }

    /** ฟันธงในบทสรุปต้องไปทางเดียวกับคำทำนาย */
    private static function checkVerdict(string $say, array $outline): array
    {
        $reasons = [];
        $truth = ReadingOutline::polarity($outline['verdict'] ?? null);
        $said = null;
        $sayBody = trim((string) preg_replace('/^\s*\[[a-z]+\]\s*/u', '', $say));
        if (preg_match('/(?:คำตอบ|ไพ่ตอบ|ฟันธง|ผล)\S{0,6}?\s*(?:คือ|ว่า)?\s*["“\']?\s*(ยังไม่ใช่|ไม่ใช่|ใช่)/u', $sayBody, $m)
            || preg_match('/^(ยังไม่ใช่|ไม่ใช่|ใช่)(?=\s|ค่ะ|คะ|นะ|จ้ะ|เลย|แล้ว|$)/u', $sayBody, $m)) {
            $said = ReadingOutline::polarity($m[1]);
        }
        if ($truth !== null && $said !== null && $truth !== $said) {
            $reasons[] = 'ฟันธงกลับข้างกับคำทำนาย';
        }

        $choice = $outline['choice'] ?? null;
        if ($choice !== null && preg_match('/เลือก\S{0,8}\s*ทางเลือกที่\s*(\d)/u', $say, $m) && (int) $m[1] !== $choice) {
            $reasons[] = "ไพ่เลือกทางเลือกที่ {$choice} ไม่ใช่ {$m[1]}";
        }

        return $reasons;
    }

    /** คุณไสย: พูดว่าพบ/ไม่พบ ต้องตรงตาราง "พบของ" (ไม่ชัด = ห้ามฟันทั้งสองทาง) */
    private static function checkKunsai(string $say, ?string $found): array
    {
        if ($found === null) {
            return [];
        }
        $object = '(?:ของ|คุณไสย|มนต์ดำ|ไสย)';
        $negative = preg_match_all('/ไม่\S{0,4}\s*(?:พบ|เจอ|โดน)\s*'.$object.'/u', $say);
        $any = preg_match_all('/(?:พบ|เจอ|โดน)\s*'.$object.'/u', $say);
        $positive = $any - $negative;

        return match ($found) {
            'ใช่' => $negative > 0 ? ['คำทำนายพบของ แต่บทบอกว่าไม่พบ'] : [],
            'ไม่ใช่' => $positive > 0 ? ['คำทำนายไม่พบของ แต่บทบอกว่าพบ'] : [],
            default => $positive > 0 || $negative > 0 ? ['คำทำนายยังไม่ชัด แต่บทฟันว่าพบ/ไม่พบ'] : [],
        };
    }

    /** @return list<string> ค่าที่จับได้ใน group 1 (ไม่ซ้ำ) */
    private static function mentions(string $pattern, string $text): array
    {
        preg_match_all($pattern, $text, $m);

        return array_values(array_unique($m[1] ?? []));
    }

    /** เลขไทย → อารบิก · ลบเว้นวรรคซ้ำ — ทั้งบทและคำทำนายต้องเทียบในรูปเดียวกัน */
    private static function normalize(string $text): string
    {
        $text = strtr($text, ['๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4', '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9']);

        return (string) preg_replace('/\s+/u', ' ', $text);
    }
}
