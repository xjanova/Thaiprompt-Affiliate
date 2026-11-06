<?php

namespace App\Services\Fortune;

/**
 * 🔈 (2026-11-04) แผนเสียงของคำตอบแชทในโหมดคุยด้วยเสียง — แยกบล็อก [[VOICE]] ออกจากคำตอบ แล้วตรวจก่อนใช้
 *
 * AI ปิดท้ายคำตอบด้วย
 *   [[VOICE]]
 *   pre: think_1          ← คลิปเปิดจากคลังของห้อง (ไม่บังคับ)
 *   say: <ประโยคที่ต้องสร้างเสียงใหม่ — เว้นว่างได้>
 *   post: ask_more_1      ← คลิปปิด (ไม่บังคับ)
 *   [[/VOICE]]
 *
 * ไม่เชื่อ AI 100% — ด่านฝั่งเซิร์ฟเวอร์:
 *   - pre/post ที่ไม่อยู่ในคลังของห้องนี้ (หรือเว็บไม่มีไฟล์) → ทิ้ง
 *   - say ที่มีแต่คำเติม หรือตรงกับคลิปในคลัง → ใช้คลิปแทน ไม่เสียค่าสร้างเสียง
 *   - say สั้นมาก (< 8 ตัว) และไม่มีตัวเลข → ทิ้ง (ใช้คลิปรับรู้แทน)
 *   - วลีราคา/ยอดเงิน → ลอกออก (ราคาอยู่ในแชท/การ์ดเท่านั้น)
 *   - แท็กอารมณ์เกิน 1 / แท็กที่ไม่ได้ทดสอบ → ลอกออก · ยาวเกิน 220 → ตัดที่ขอบประโยค
 *   - ไม่มีบล็อก = ทางถอย: ตัดจากคำตอบเต็มที่ขอบประโยค ≤ 260 ตัว (ไม่ยิง AI ซ้ำ)
 *   - คำตอบวิกฤต → คลิปปลอบที่อัดไว้เท่านั้น ไม่สร้างเสียงใหม่ (JuntraVoiceLibrary::crisisClip — เฉพาะบทที่ไม่พูดถึงสายด่วน)
 *     เทิร์นปลอบของด่านวิกฤตเรียก crisis() ตรง ๆ · คำตอบของ AI ที่มีเบอร์ (ลูกค้าถามหาที่ขอความช่วยเหลือเอง) จับจาก CRISIS_PATTERN
 */
final class VoicePlanParser
{
    public const SAY_MAX_CHARS = 220;

    public const FALLBACK_MAX_CHARS = 260;

    /** say ยาวกว่านี้ → แบ่งสองท่อน ขอเสียงพร้อมกัน ท่อนแรกเล่นได้เร็วขึ้น */
    public const SPLIT_OVER_CHARS = 140;

    public const MIN_SAY_CHARS = 8;

    /** เบอร์สายด่วนในคำตอบของ AI (ให้เฉพาะเมื่อลูกค้าถามหาที่ขอความช่วยเหลือ) = คำตอบนี้เป็นการดูแลวิกฤต */
    private const CRISIS_PATTERN = '/(?<!\d)(?:1323|1669)(?!\d)/u';

    /** วลีราคา/ยอดเงิน — ตัวเลขคู่กับ บาท/฿/เครดิต (คำนำ "ราคา/เพียง/แค่" ไปด้วย) */
    public const PRICE_PATTERN = '/(?:(?:ราคา|เพียง|แค่|ค่า\S{0,10})\s*)?(?:฿\s*\d[\d,\.]*|\d[\d,\.]*\s*(?:บาท|฿|เครดิต))/u';

    /**
     * แยกบล็อกออกจากคำตอบ (ข้อความที่แสดงในแชทต้องไม่มีบล็อกเสมอ แม้ AI ตอบถูกตัดกลางบล็อก)
     *
     * @return array{0:string,1:?string} [คำตอบที่แสดง, เนื้อในบล็อก (null = ไม่มีบล็อก)]
     */
    public static function extract(string $reply): array
    {
        if (preg_match('/\[\[\s*VOICE\s*\]\](.*?)(?:\[\[\s*\/\s*VOICE\s*\]\]|$)/isu', $reply, $m, PREG_OFFSET_CAPTURE)) {
            $inner = $m[1][0];
            $display = substr($reply, 0, $m[0][1]).substr($reply, $m[0][1] + strlen($m[0][0]));

            return [self::tidy($display), $inner];
        }
        // AI ถูกตัดก่อนพิมพ์ป้ายเปิดครบ ("[[VOI") — ลบเศษป้ายท้ายข้อความ
        $display = (string) preg_replace('/\[\[\s*\/?\s*V(?:O(?:I(?:C(?:E)?)?)?)?\s*\]?\]?\s*$/iu', '', $reply);

        return [self::tidy($display), null];
    }

    /**
     * แผนเสียงที่ตรวจแล้ว
     *
     * @param  string  $display  คำตอบที่แสดงในแชท (ไม่มีบล็อกแล้ว)
     * @param  string|null  $block  เนื้อในบล็อก [[VOICE]] (null = AI ไม่ได้ส่งมา)
     * @param  array<int,string>|null  $available  id คลิปที่เว็บมีไฟล์ (null = ถือว่ามีครบ)
     * @param  string  $kind  kind ของคำตอบ (guard = ด่านกันใช้แม่หมอเป็น AI ฟรี)
     * @return array{source:string,pre:?string,say:?string,say_parts:array<int,string>,post:?string}
     */
    public static function plan(?string $persona, string $display, ?string $block, ?array $available, string $kind = 'reply'): array
    {
        if ($kind === 'guard') {
            // ข้อความปฏิเสธอยู่ในแชทแล้ว — เสียงแค่ชวนถามเรื่องดวงต่อ ไม่สร้างเสียงใหม่
            return self::result('guard', null, null, JuntraVoiceLibrary::first($persona, $available, 'ask_more'));
        }
        if (preg_match(self::CRISIS_PATTERN, $display)) {
            return self::crisis($persona, $available);
        }

        if ($block !== null) {
            $fields = self::fields($block);
            $pre = self::clip($persona, $available, $fields['pre']);
            $post = self::clip($persona, $available, $fields['post']);
            $say = $fields['say'] !== null ? self::cleanSay($fields['say'], self::SAY_MAX_CHARS) : null;

            if ($say !== null && $say !== '') {
                $plain = JuntraVoiceLibrary::plain($say);
                $match = JuntraVoiceLibrary::matchText($persona, $available, $plain);
                if ($match !== null) {
                    // บทตรงกับคลิปในคลัง — ใช้คลิป (ช่องว่างก่อน: pre แล้วค่อย post)
                    if ($pre === null) {
                        $pre = $match;
                    } elseif ($post === null) {
                        $post = $match;
                    }
                    $say = null;
                } elseif (JuntraVoiceLibrary::isFillerOnly($plain)) {
                    $pre ??= JuntraVoiceLibrary::first($persona, $available, 'ack');
                    $say = null;
                } elseif (mb_strlen($plain) < self::MIN_SAY_CHARS && ! preg_match('/\d/u', $plain)) {
                    $say = null;
                }
            } else {
                $say = null;
            }

            if ($pre !== null || $say !== null || $post !== null) {
                return self::result('ai', $pre, $say, $post);
            }
            // บล็อกว่างทั้งหมด = เหมือนไม่มีบล็อก
        }

        $say = self::cleanSay($display, self::FALLBACK_MAX_CHARS);
        if ($say === '' || JuntraVoiceLibrary::isFillerOnly(JuntraVoiceLibrary::plain($say))) {
            return self::result('fallback', JuntraVoiceLibrary::first($persona, $available, 'ack'), null, null);
        }

        return self::result('fallback', null, $say, null);
    }

    /** @return array{source:string,pre:?string,say:?string,say_parts:array<int,string>,post:?string} */
    private static function result(string $source, ?string $pre, ?string $say, ?string $post): array
    {
        return [
            'source' => $source,
            'pre' => $pre,
            'say' => $say,
            'say_parts' => $say !== null ? self::split($say) : [],
            'post' => $post,
        ];
    }

    /**
     * 🤍 (2026-11-04) แผนเสียงของเทิร์นที่ลูกค้าบอกว่าอยากทำร้ายตัวเอง — คลิปปลอบที่อัดไว้เท่านั้น ไม่สร้างเสียงใหม่
     *
     * คำตอบปลอบไม่มีเบอร์สายด่วนแล้ว (กฎเจ้าของ: ปลอบเบา ๆ ไม่ไล่ไปโทรหาใคร) → จับจาก CRISIS_PATTERN ไม่ได้
     * 🤍 (2026-11-06) เล่นเฉพาะคลิปปลอบที่ไม่พูดถึงสายด่วน (soothe_2 เดิม "…โทรสายด่วนที่…พิมพ์ไว้ให้" ถูกข้าม)
     * ผู้เรียกที่รู้ว่าเป็นเทิร์นวิกฤตจึงเรียกตัวนี้ตรง ๆ แทน plan(..., 'guard') ที่เล่นคลิป "ถามอะไรต่อไหม"
     *
     * @param  array<int,string>|null  $available  id คลิปที่เว็บมีไฟล์ (null = ถือว่ามีครบ)
     * @return array{source:string,pre:?string,say:?string,say_parts:array<int,string>,post:?string}
     */
    public static function crisis(?string $persona, ?array $available): array
    {
        return self::result('crisis', JuntraVoiceLibrary::crisisClip($persona, $available), null, null);
    }

    /** @return array{pre:?string,say:?string,post:?string} */
    private static function fields(string $block): array
    {
        $out = ['pre' => null, 'say' => null, 'post' => null];
        $current = null;
        foreach (preg_split('/\R/u', $block) as $line) {
            if (preg_match('/^\s*(pre|say|post)\s*[:：]\s*(.*)$/iu', $line, $m)) {
                $current = strtolower($m[1]);
                $out[$current] = trim($m[2]);
            } elseif ($current === 'say' && trim($line) !== '') {
                // บทพูดหลายบรรทัด — ต่อกันจนเจอ post:
                $out['say'] .= ' '.trim($line);
            }
        }

        return $out;
    }

    /** id คลิปจาก AI — ต้องอยู่ในคลังห้องนี้ มีไฟล์ และอยู่ในหมวดที่ AI เลือกได้ */
    private static function clip(?string $persona, ?array $available, ?string $raw): ?string
    {
        $id = strtolower(trim((string) $raw, " \t\"'`<>"));
        if ($id === '' || in_array($id, ['-', 'none', 'null', 'ไม่มี'], true)) {
            return null;
        }

        return JuntraVoiceLibrary::isAiClip($persona, $available, $id) ? $id : null;
    }

    /**
     * ทำความสะอาดบทพูด: ลอก markdown/emoji/ป้าย/ลิงก์ · แท็กอารมณ์ที่ทดสอบแล้วเหลือ 1 แท็กแรก
     * ตัดประโยคที่พูดราคา · ยาวเกิน = ตัดที่ขอบประโยค (วรรค / . ! ? …) ไม่ตัดกลางคำ
     */
    public static function cleanSay(string $text, int $max): string
    {
        $text = (string) preg_replace('/\[\[[^\]]*\]\]/u', ' ', $text);
        $text = (string) preg_replace('#https?://\S+#u', ' ', $text);
        $text = (string) preg_replace('/(\*\*|__|\*|`)/u', '', $text);
        $text = (string) preg_replace('/^\s*(?:#+|[-*•]+|\d+[\.\)])\s*/mu', '', $text);
        $text = (string) preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}☾✨]/u', '', $text);

        // แท็กอารมณ์: เก็บแท็กแรกที่รู้จัก วางหน้าบท · ที่เหลือลอกทิ้ง
        //   AI เขียนเป็น {softly} — ตัวกรองขาออกของ FortuneAIService ลอก [softly] ทิ้งก่อนถึงที่นี่
        //   ([softly] ที่รอดมาได้ก็รับ) ส่งต่อให้ ElevenLabs เป็น [softly] เสมอ
        $tag = null;
        $text = (string) preg_replace_callback('/[\[\{]([a-z ]{2,20})[\]\}]/iu', function ($m) use (&$tag) {
            $name = strtolower(trim($m[1]));
            if ($tag === null && in_array($name, JuntraVoiceLibrary::ALLOWED_TAGS, true)) {
                $tag = $name;
            }

            return ' ';
        }, $text);

        $text = (string) preg_replace(self::PRICE_PATTERN, ' ', $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        $sentences = self::sentences($text);

        $budget = $max - ($tag !== null ? mb_strlen($tag) + 3 : 0);
        $out = '';
        foreach ($sentences as $s) {
            $next = $out === '' ? $s : $out.' '.$s;
            if (mb_strlen($next) > $budget) {
                break;
            }
            $out = $next;
        }
        if ($out === '' && $sentences !== []) {
            // ประโยคแรกยาวเกินงบ — ตัดที่วรรคสุดท้ายในงบ (ไทยไม่มีวรรคระหว่างคำ วรรคคือขอบวลี)
            $cut = mb_substr($sentences[0], 0, $budget);
            $space = mb_strrpos($cut, ' ');
            $out = $space !== false && $space > $budget / 2 ? mb_substr($cut, 0, $space) : $cut;
        }
        $out = trim($out);

        return $out === '' ? '' : ($tag !== null ? "[{$tag}] " : '').$out;
    }

    /** แบ่งเป็นประโยค/วลีตามวรรคและเครื่องหมายจบประโยค */
    private static function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[\.\!\?…])\s+|\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /** say ยาว → สองท่อนที่ขอบวลีใกล้กลางที่สุด (แท็กอารมณ์ติดไปกับทั้งสองท่อน) */
    private static function split(string $say): array
    {
        if (mb_strlen($say) <= self::SPLIT_OVER_CHARS) {
            return [$say];
        }
        $tag = '';
        if (preg_match('/^(\[[a-z]+\]) /u', $say, $m)) {
            $tag = $m[1].' ';
            $say = mb_substr($say, mb_strlen($tag));
        }
        $words = self::sentences($say);
        if (count($words) < 2) {
            return [$tag.$say];
        }
        $half = mb_strlen($say) / 2;
        $best = 1;
        $bestDiff = PHP_INT_MAX;
        $len = 0;
        foreach ($words as $i => $w) {
            $len += mb_strlen($w) + ($i > 0 ? 1 : 0);
            if ($i < count($words) - 1 && abs($len - $half) < $bestDiff) {
                $bestDiff = abs($len - $half);
                $best = $i + 1;
            }
        }

        return [
            $tag.implode(' ', array_slice($words, 0, $best)),
            $tag.implode(' ', array_slice($words, $best)),
        ];
    }

    /** ช่องว่างท้ายบรรทัด/บรรทัดว่างที่เหลือจากการลบบล็อก */
    private static function tidy(string $text): string
    {
        $text = (string) preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = (string) preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }
}
