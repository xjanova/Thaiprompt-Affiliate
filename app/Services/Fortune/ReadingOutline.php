<?php

namespace App\Services\Fortune;

/**
 * 🎬 (2026-11-04) แยกคำทำนายของเว็บจันทราเป็นหัวข้อ — ใช้ทำบทเล่าเสียง (โรงละครคำทำนาย)
 *
 * คำทำนายใช้หัวข้อตายตัวของ JuntraSpreadProfiles::HEADINGS ขึ้นต้นด้วย "## " เสมอ
 * (ตัวเดียวกับที่ juntraweb ReadingSections แยกเป็นการ์ด) — ตัวนี้อ่านอย่างเดียว ไม่แก้คำทำนาย
 *
 * ได้:
 *   sections  = [{type, title, body, pos?, month?, tone?}] ตามลำดับในคำทำนาย
 *   verdict   = ข้อความหลัง "ผล:" ในหัวข้อฟันธง
 *   choice    = ทางเลือกที่ไพ่เลือก (แบบตัดสินใจ)
 *   kunsai    = ใช่ | ไม่ใช่ | ไม่ชัด จากบรรทัด "พบของ:" (แบบคุณไสย)
 */
final class ReadingOutline
{
    /** ป้ายโทนในหัวข้อเดือน → ค่าที่กราฟใช้ */
    public const MONTH_TONES = ['ดี' => 'good', 'กลาง' => 'neutral', 'ระวัง' => 'caution'];

    /**
     * @return array{sections:array<int,array{type:string,title:string,body:string,pos?:int,month?:string,tone?:?string}>,verdict:?string,choice:?int,kunsai:?string}
     */
    public static function parse(string $text): array
    {
        $sections = [];
        $current = null;
        foreach (preg_split('/\R/u', $text) as $line) {
            if (preg_match('/^\s*#{1,4}\s*(.+?)\s*$/u', $line, $m)) {
                if ($current !== null) {
                    $sections[] = self::finish($current);
                }
                $current = ['title' => $m[1], 'lines' => []];

                continue;
            }
            if ($current !== null) {
                $current['lines'][] = $line;
            }
        }
        if ($current !== null) {
            $sections[] = self::finish($current);
        }

        $verdict = null;
        $choice = null;
        $kunsai = null;
        foreach ($sections as $s) {
            if ($s['type'] === 'verdict' && $verdict === null && preg_match('/^\s*ผล\s*[:：]\s*(.+)$/mu', $s['body'], $m)) {
                $verdict = trim($m[1]);
                if (preg_match('/ทางเลือกที่\s*(\d)/u', $verdict, $c)) {
                    $choice = (int) $c[1];
                }
            }
            if ($s['type'] === 'choice' && preg_match('/^\s*เลือก\s*[:：]\s*ทางเลือกที่\s*(\d)/mu', $s['body'], $m)) {
                $choice = (int) $m[1];
            }
            if ($s['type'] === 'diagnosis' && preg_match('/^\s*พบของ\s*[:：]\s*(ไม่ชัด|ไม่ใช่|ใช่)/mu', $s['body'], $m)) {
                $kunsai = $m[1];
            }
        }

        return ['sections' => $sections, 'verdict' => $verdict, 'choice' => $choice, 'kunsai' => $kunsai];
    }

    /** ผลฟันธงแบบใช่/ไม่ — 'yes' | 'no' | 'not_yet' | null (ผลแบบสรุปวลี) */
    public static function polarity(?string $verdict): ?string
    {
        $v = trim((string) $verdict);

        return match (true) {
            $v === '' => null,
            str_starts_with($v, 'ยังไม่ใช่') => 'not_yet',
            str_starts_with($v, 'ไม่ใช่') => 'no',
            str_starts_with($v, 'ใช่') => 'yes',
            default => null,
        };
    }

    private static function finish(array $raw): array
    {
        // ⚠️/☎️ มี/ไม่มีตัวเลือกรูปแบบ (U+FE0F) แล้วแต่ AI — เทียบแบบไม่สนตัวนี้
        $title = str_replace("\u{FE0F}", '', $raw['title']);
        $body = trim(implode("\n", $raw['lines']));
        $h = array_map(fn ($heading) => str_replace("\u{FE0F}", '', $heading), JuntraSpreadProfiles::HEADINGS);

        $section = ['type' => 'other', 'title' => $title, 'body' => $body];

        if (str_starts_with($title, $h['card'])) {
            $section['type'] = 'card';
            if (preg_match('/^'.preg_quote($h['card'], '/').'\s*(\d+)/u', $title, $m)) {
                $section['pos'] = (int) $m[1];
            }
        } elseif (str_starts_with($title, $h['month'])) {
            $section['type'] = 'month';
            $rest = trim(mb_substr($title, mb_strlen($h['month'])));
            $parts = preg_split('/\s*·\s*/u', $rest);
            $section['month'] = trim((string) ($parts[0] ?? ''));
            $toneWord = trim((string) ($parts[1] ?? ''));
            $section['tone'] = self::MONTH_TONES[$toneWord] ?? null;
        } elseif (str_starts_with($title, $h['option'])) {
            $section['type'] = 'option';
        } else {
            foreach ($h as $type => $heading) {
                if (! in_array($type, ['card', 'month', 'option'], true) && str_starts_with($title, $heading)) {
                    $section['type'] = $type;
                    break;
                }
            }
        }

        return $section;
    }
}
