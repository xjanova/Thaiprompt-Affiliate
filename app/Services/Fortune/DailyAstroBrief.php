<?php

namespace App\Services\Fortune;

use App\Services\FortuneChartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🪐 DailyAstroBrief — ข้อเท็จจริงทางโหราศาสตร์ของ "วันนั้นจริง ๆ" สำหรับคนเกิดวันหนึ่ง ๆ
 *
 * เจ้าของสั่ง (2026-08-02): "อยากให้ทำนายดวงตามหลักดวงดาวโหรที่เรามีจริง ๆ ให้ถูกต้อง
 *   ไม่ใช่การมโน ตามกำลังวันเกิด จันทร์ถึงอาทิตย์ อิงตามหลักดวงดาวของวันที่ปัจจุบันจริง ๆ"
 *
 * ⚠️ ปัญหาเดิม: `buildBirthDayPrompt()` ส่งให้ AI แค่ข้อมูล **คงที่** (ดาวประจำวันเกิด/ธาตุ/
 *    ดาวมิตร-ศัตรู) ซึ่งเหมือนกันทุกวันตลอดปี → AI ไม่มีอะไรให้ยึดว่า "วันนี้" ต่างจากเมื่อวาน
 *    ยังไง เลยแต่งเอาเองล้วน ๆ ทั้งที่ในระบบมีเครื่องคำนวณตำแหน่งดาวจริงอยู่แล้ว
 *
 * คลาสนี้ประกอบ "ข้อเท็จจริงที่ตรวจสอบได้" จากของที่มีอยู่จริงในระบบ 3 แหล่ง:
 *   1. [[PlanetEphemeris]] — ตำแหน่งดาวจริง 9 ดวง ณ วันนั้น (ราศี + พักร) คำนวณเอง ไม่พึ่ง API
 *   2. `FortuneChartService::CHAOCHANA` — ดาวเจ้าเรือนวันเกิด + ดาวมิตร/ศัตรู (ตำราเจ้าชนะ)
 *   3. `config/thai_astrology_knowledge.php` — กำลังพระเคราะห์ (`period_years`),
 *      จุดเด่นรายดาว (`trait`), ศักดิ์ดาวเกษตร/อุจ/นิจ (`planet_dignity`)
 *
 * แล้วคำนวณเพิ่มอีก 1 ชั้นที่ต้องใช้ตำแหน่งจริงเท่านั้น: **มุมสัมพันธ์** (กุม/เล็ง/ตรีโกณ/จตุโกณ)
 *
 * 🎯 ผลลัพธ์ = บล็อกข้อความที่ยัดเข้า prompt ให้ AI "ทำนายจากข้อเท็จจริงชุดนี้เท่านั้น"
 *    ไม่ใช่ให้ AI นึกเอาว่าวันนี้ดาวอะไรเด่น
 */
class DailyAstroBrief
{
    /**
     * มุมสัมพันธ์ที่ใช้ + ระยะคลาดเคลื่อนที่ยอมรับ (orb องศา)
     *
     * ใช้ศัพท์ไทยตามตำรา — กุม/เล็ง/ตรีโกณ/จตุโกณ
     * orb กว้างกว่าโหรสากลเล็กน้อยเพราะ ephemeris ของเราแม่นระดับราศี (~±0.3°)
     * และดวงรายวันไม่ต้องการความละเอียดระดับลิปดา
     */
    private const ASPECTS = [
        ['angle' => 0,   'orb' => 8, 'name' => 'กุม',     'nature' => 'ผสานพลัง'],
        ['angle' => 60,  'orb' => 5, 'name' => 'สัมพันธ์', 'nature' => 'เกื้อกูลเบา ๆ'],
        ['angle' => 90,  'orb' => 6, 'name' => 'จตุโกณ',  'nature' => 'ขัดแย้ง-ต้องออกแรง'],
        ['angle' => 120, 'orb' => 7, 'name' => 'ตรีโกณ',  'nature' => 'ส่งเสริม-ไหลลื่น'],
        ['angle' => 180, 'orb' => 8, 'name' => 'เล็ง',     'nature' => 'ดึงกันคนละทาง'],
    ];

    /** ชื่อวันในสัปดาห์ index 0=อาทิตย์ … 6=เสาร์ */
    private const DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    /** key ของ FortuneChartService → key ของ PlanetEphemeris */
    private const EPHEMERIS_KEY = [
        'sun' => 'Sun', 'moon' => 'Moon', 'mars' => 'Mars', 'mercury' => 'Mercury',
        'jupiter' => 'Jupiter', 'venus' => 'Venus', 'saturn' => 'Saturn',
        'rahu' => 'Rahu', 'ketu' => 'Ketu',
    ];

    /**
     * ประกอบข้อเท็จจริงของคนเกิดวัน $birthDay สำหรับวันที่ $date
     *
     * @param  int  $birthDay  0=อาทิตย์ … 6=เสาร์
     * @return array{
     *   ok: bool, day_name: string, thai_date: string,
     *   lord: array, day_lord: array, friends: array, enemies: array,
     *   aspects: array, retrogrades: array, score_hint: int, text: string
     * }
     */
    public function build(int $birthDay, Carbon $date): array
    {
        try {
            // เที่ยงวันของวันเป้าหมาย — ตำแหน่งดาวกลางวันเป็นตัวแทนของทั้งวันได้ดีที่สุด
            // (จันทร์เดินเร็วสุด ~13°/วัน ใช้เที่ยงจึงคลาดจากขอบวันไม่เกินครึ่งราศี)
            $positions = (new PlanetEphemeris)->positions($date->copy()->setTime(12, 0));

            $chaochana = FortuneChartService::CHAOCHANA[$birthDay] ?? null;
            if ($chaochana === null) {
                return $this->emptyBrief($birthDay, $date);
            }

            $lord = $this->planetFact($chaochana['planet'], $positions);

            // ดาวเจ้าการของ "วันที่ทำนาย" — คนละตัวกับดาวเจ้าเรือนวันเกิด
            // (เกิดวันจันทร์ แต่วันนี้เป็นวันศุกร์ → ศุกร์เป็นเจ้าการวัน)
            $dayLordKey = FortuneChartService::CHAOCHANA[$date->dayOfWeek]['planet'] ?? 'sun';
            $dayLord = $this->planetFact($dayLordKey, $positions);

            $friends = array_map(fn ($p) => $this->planetFact($p, $positions), $chaochana['friends'] ?? []);
            $enemies = array_map(fn ($p) => $this->planetFact($p, $positions), $chaochana['enemies'] ?? []);

            $aspects = $this->aspectsTo($chaochana['planet'], $positions, $chaochana);
            $retrogrades = $this->retrogradeNames($positions);

            $brief = [
                'ok' => true,
                'birth_day' => $birthDay,
                'day_name' => self::DAY_NAMES[$birthDay] ?? '?',
                'thai_date' => $date->locale('th')->translatedFormat('l j F').' '.($date->year + 543),
                'lord' => $lord,
                'day_lord' => $dayLord,
                'friends' => $friends,
                'enemies' => $enemies,
                'aspects' => $aspects,
                'retrogrades' => $retrogrades,
            ];

            $brief['score_hint'] = $this->scoreHint($brief);
            $brief['text'] = $this->toPromptBlock($brief);

            return $brief;
        } catch (\Throwable $e) {
            // ⚠️ fail-open — คำนวณดาวพังต้องไม่ทำให้ job 6 โมงล้มทั้งวัน
            //    คืน brief เปล่าแล้วให้ prompt ถอยไปใช้ข้อมูลคงที่แบบเดิม
            Log::warning('DailyAstroBrief: คำนวณดาวไม่สำเร็จ', [
                'birth_day' => $birthDay,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return $this->emptyBrief($birthDay, $date);
        }
    }

    /**
     * ข้อเท็จจริงรายดาว — ตำแหน่งจริง + ศักดิ์ + กำลัง + จุดเด่น
     *
     * @param  array<string, array>  $positions  ผลจาก PlanetEphemeris::positions()
     */
    protected function planetFact(string $planetKey, array $positions): array
    {
        $thName = FortuneChartService::PLANETS[$planetKey]['name'] ?? $planetKey;
        $pos = $positions[self::EPHEMERIS_KEY[$planetKey] ?? ''] ?? null;
        $meta = config("thai_astrology_knowledge.planet_meta.{$thName}", []);

        $sign = $pos['sign'] ?? null;

        // ราหู/เกตุ เดินถอยหลังตลอดโดยธรรมชาติ — รายงานว่า "พักร" เท่ากับบอกข่าวที่จริงทุกวัน
        // ทำให้ AI หยิบไปตีความว่าวันนี้มีอะไรพิเศษ ทั้งที่ไม่มี
        $isNode = in_array($planetKey, ['rahu', 'ketu'], true);

        return [
            'key' => $planetKey,
            'th' => $thName,
            'sign' => $sign,
            'retro' => ! $isNode && (bool) ($pos['retro'] ?? false),
            'lon' => $pos['lon'] ?? null,
            'power' => $meta['period_years'] ?? null,   // = กำลังพระเคราะห์ (มหาทักษา)
            'trait' => $meta['trait'] ?? null,
            'element' => $meta['element'] ?? null,
            'dignity' => $sign !== null ? $this->dignityOf($thName, $sign) : null,
        ];
    }

    /**
     * ศักดิ์ของดาวในราศีที่มันอยู่จริงวันนั้น — เกษตร / อุจ / นิจ / เป็นกลาง
     *
     * ตัดสินจาก `planet_dignity` ใน config (ตำราจริง) ไม่ใช่การเดา
     */
    protected function dignityOf(string $thName, string $sign): array
    {
        $d = config("thai_astrology_knowledge.planet_dignity.{$thName}", []);

        $key = match (true) {
            in_array($sign, $d['rules'] ?? [], true) => 'rules',
            ($d['exalted'] ?? null) === $sign => 'exalted',
            ($d['debilitated'] ?? null) === $sign => 'debilitated',
            default => 'neutral',
        };

        // ⚠️ dignity_label ใน config มีอีโมจิ (⭐🌟💧➖) เพราะเดิมใช้โชว์ในหน้าแอดมิน
        //    บล็อกนี้ถูกยัดเข้า prompt ของคอนเทนต์ที่ "ห้ามมีอีโมจิ" → ต้องถอดออกก่อน
        //    ไม่งั้นเท่ากับสอนโมเดลว่าอีโมจิใช้ได้ ทั้งที่กฎบอกห้าม
        $label = FacebookContentPolicy::stripEmoji(
            (string) config("thai_astrology_knowledge.dignity_label.{$key}", '')
        );

        return [
            'key' => $key,
            'label' => trim($label),
        ];
    }

    /**
     * มุมสัมพันธ์ระหว่างดาวเจ้าเรือน กับดาวอื่นทุกดวง ณ วันนั้น
     *
     * 🎯 นี่คือส่วนที่ "ต้องมีตำแหน่งจริงเท่านั้นถึงคำนวณได้" — เป็นเหตุผลว่าทำไม
     *    วันนี้ต่างจากเมื่อวาน ซึ่งเดิม AI ไม่มีข้อมูลนี้เลยจึงมโนแทน
     */
    protected function aspectsTo(string $lordKey, array $positions, array $chaochana): array
    {
        $lordPos = $positions[self::EPHEMERIS_KEY[$lordKey] ?? ''] ?? null;
        if ($lordPos === null) {
            return [];
        }

        $friends = $chaochana['friends'] ?? [];
        $enemies = $chaochana['enemies'] ?? [];
        $out = [];

        foreach (self::EPHEMERIS_KEY as $key => $ephKey) {
            if ($key === $lordKey || ! isset($positions[$ephKey])) {
                continue;
            }

            $diff = abs($this->angleDiff($lordPos['lon'], $positions[$ephKey]['lon']));

            foreach (self::ASPECTS as $aspect) {
                if (abs($diff - $aspect['angle']) > $aspect['orb']) {
                    continue;
                }

                $relation = match (true) {
                    in_array($key, $friends, true) => 'มิตร',
                    in_array($key, $enemies, true) => 'ศัตรู',
                    default => 'กลาง',
                };

                $out[] = [
                    'other' => FortuneChartService::PLANETS[$key]['name'] ?? $key,
                    'other_key' => $key,
                    'aspect' => $aspect['name'],
                    'nature' => $aspect['nature'],
                    'relation' => $relation,
                    'orb' => round(abs($diff - $aspect['angle']), 1),
                    'benefic' => $this->isBenefic($aspect['name'], $relation),
                ];
                break;   // ดาวคู่หนึ่งจับได้มุมเดียว (มุมใกล้ที่สุดที่เข้าเกณฑ์)
            }
        }

        return $out;
    }

    /**
     * มุมนี้ให้คุณหรือให้โทษ — ตัดสินจาก "ชนิดมุม × ความสัมพันธ์มิตร/ศัตรู"
     *
     * ตรีโกณ/สัมพันธ์ = ให้คุณเสมอ · จตุโกณ/เล็ง = ให้โทษเสมอ
     * กุม = ขึ้นกับว่าดาวที่มากุมเป็นมิตรหรือศัตรู (กุมมิตรดี กุมศัตรูหนัก)
     */
    protected function isBenefic(string $aspectName, string $relation): bool
    {
        return match ($aspectName) {
            'ตรีโกณ', 'สัมพันธ์' => true,
            'จตุโกณ', 'เล็ง' => false,
            'กุม' => $relation !== 'ศัตรู',
            default => true,
        };
    }

    /** ดาวที่พักรอยู่วันนั้น (ชื่อไทย) */
    protected function retrogradeNames(array $positions): array
    {
        $out = [];
        foreach (self::EPHEMERIS_KEY as $key => $ephKey) {
            // ราหู/เกตุ เดินถอยหลังตลอดโดยธรรมชาติ — ไม่นับเป็น "พักร" ที่มีนัย
            if (in_array($key, ['rahu', 'ketu'], true)) {
                continue;
            }
            if (! empty($positions[$ephKey]['retro'])) {
                $out[] = FortuneChartService::PLANETS[$key]['name'] ?? $key;
            }
        }

        return $out;
    }

    /**
     * คะแนนภาพรวม 1-5 จากข้อเท็จจริง — ใช้เป็น fallback แทน rand()
     *
     * ⚠️ เดิมใช้ `rand(2,5)` ซึ่งคือการมโนตรง ๆ ที่เจ้าของสั่งให้เลิก
     *    ตัวนี้คำนวณจากศักดิ์ดาว + มุมสัมพันธ์จริง → วันเดิมได้คะแนนเดิมเสมอ
     */
    protected function scoreHint(array $brief): int
    {
        $score = 3;

        $score += match ($brief['lord']['dignity']['key'] ?? 'neutral') {
            'exalted' => 2,
            'rules' => 1,
            'debilitated' => -2,
            default => 0,
        };

        if (! empty($brief['lord']['retro'])) {
            $score--;
        }

        foreach ($brief['aspects'] as $a) {
            $score += $a['benefic'] ? 1 : -1;
        }

        return max(1, min(5, $score));
    }

    /**
     * แปลงข้อเท็จจริงเป็นบล็อกภาษาไทยสำหรับยัดเข้า prompt
     *
     * เขียนเป็น "ข้อมูลดิบ" ไม่ใช่คำทำนาย — หน้าที่ตีความเป็นของ AI
     * แต่ AI ต้องตีความจาก**ชุดนี้เท่านั้น** (กฎอยู่ในตัว prompt)
     */
    protected function toPromptBlock(array $brief): string
    {
        $l = $brief['lord'];
        $d = $brief['day_lord'];

        $lines = [];
        $lines[] = "ดาวเจ้าเรือนของคนเกิดวัน{$brief['day_name']}: {$l['th']}"
            .($l['power'] !== null ? " (กำลังพระเคราะห์ {$l['power']})" : '')
            .($l['element'] !== null ? " ธาตุ{$l['element']}" : '');

        if ($l['trait'] !== null) {
            $lines[] = "ธรรมชาติเด่นของเจ้าชะตา: {$l['trait']}";
        }

        if ($l['sign'] !== null) {
            $lines[] = trim("วันนี้ {$l['th']} สถิตราศี{$l['sign']} "
                .($l['dignity']['label'] ?? '')
                .($l['retro'] ? ' · กำลังพักร (เดินถอยหลัง)' : ''));
        }

        $lines[] = "ดาวเจ้าการของวันนี้: {$d['th']}"
            .($d['sign'] !== null ? " สถิตราศี{$d['sign']}" : '')
            .($d['power'] !== null ? " (กำลัง {$d['power']})" : '');

        foreach (['friends' => 'ดาวมิตร', 'enemies' => 'ดาวศัตรู'] as $group => $label) {
            $parts = [];
            foreach ($brief[$group] as $p) {
                if ($p['sign'] === null) {
                    continue;
                }
                $parts[] = "{$p['th']} (ราศี{$p['sign']}".($p['retro'] ? ' พักร' : '').')';
            }
            if ($parts !== []) {
                $lines[] = "{$label}วันนี้: ".implode(' · ', $parts);
            }
        }

        if ($brief['aspects'] !== []) {
            $lines[] = 'มุมสัมพันธ์ที่เกิดขึ้นจริงวันนี้ (คำนวณจากตำแหน่งดาว):';
            foreach ($brief['aspects'] as $a) {
                $lines[] = "  - {$l['th']} {$a['aspect']} {$a['other']} ({$a['relation']}) "
                    ."— {$a['nature']} · คลาด {$a['orb']} องศา";
            }
        } else {
            $lines[] = 'วันนี้ไม่มีมุมสัมพันธ์เด่นกับดาวเจ้าเรือน (ดวงเดินเรียบ ไม่มีแรงกระแทก)';
        }

        if ($brief['retrogrades'] !== []) {
            $lines[] = 'ดาวที่พักรอยู่วันนี้: '.implode(', ', $brief['retrogrades']);
        }

        return implode("\n", $lines);
    }

    /** brief เปล่าเมื่อคำนวณไม่ได้ — ผู้เรียกต้องเช็ค ok ก่อนใช้ text */
    protected function emptyBrief(int $birthDay, Carbon $date): array
    {
        return [
            'ok' => false,
            'birth_day' => $birthDay,
            'day_name' => self::DAY_NAMES[$birthDay] ?? '?',
            'thai_date' => $date->locale('th')->translatedFormat('l j F').' '.($date->year + 543),
            'lord' => ['th' => null, 'sign' => null, 'retro' => false, 'power' => null, 'trait' => null, 'dignity' => null],
            'day_lord' => ['th' => null, 'sign' => null, 'power' => null],
            'friends' => [],
            'enemies' => [],
            'aspects' => [],
            'retrogrades' => [],
            'score_hint' => 3,
            'text' => '',
        ];
    }

    /** ผลต่างมุม 2 ลองจิจูด → ช่วง [-180, 180] */
    protected function angleDiff(float $a, float $b): float
    {
        $diff = fmod($a - $b + 540.0, 360.0) - 180.0;

        return $diff;
    }
}
