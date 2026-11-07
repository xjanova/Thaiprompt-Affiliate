<?php

namespace App\Services\Fortune;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🪐 (2026-11-07) ข้อเท็จจริงจากตำแหน่งดาวจริงของ "ราศี" หนึ่งในวันหนึ่ง — ตัวคู่ของ DailyAstroBrief (ดวงวันเกิด)
 *
 * ทำไมต้องมี: ดวงราศีรายวันเดิม (HoroscopeDailyService::buildZodiacPrompt / juntraweb DailyHoroscopeWriter)
 *   ส่งให้ AI แค่ "ธาตุ + ดาวประจำราศี + ช่วงวันเกิด" ซึ่งเหมือนกันทุกวันตลอดปี ⇒ AI แต่งเอง 100%
 *   (บทเรียนเดียวกับ 2026-08-02: "AI มโน" คือ prompt ไม่มีข้อเท็จจริงให้ยึด)
 *
 * กรอบราศี: **สายนะ (tropical)** — ราศีบนเว็บจันทราแบ่งตามวันที่แบบสากล (เมษ 21 มี.ค. – 19 เม.ย.)
 *   ถ้าเอาลองจิจูดนิรายนะมาวางบนราศีสากล ดาวจะเพี้ยนไปเกือบเต็มราศี (อายนางศ ~24°)
 *   ตำแหน่งดาวยังมาจาก PlanetEphemeris ตัวเดียวกับบอท (ใช้ lon_tropical) — ไม่ได้คำนวณใหม่
 *   ดวงวันเกิดแบบไทย (DailyAstroBrief) ยังใช้นิรายนะเหมือนเดิม
 *
 * ภพ = นับจากราศีนั้นเป็นภพที่ 1 (whole-sign จากราศีอาทิตย์ — วิธีมาตรฐานของดวงราศีรายวัน)
 * เกตุไทยกับมฤตยูไม่ใช้ — เกตุไทยนิยามในกรอบนิรายนะ (ค่าสายนะเป็นค่าแปลงกลับ) และมฤตยูไม่มีในตำราดวงราศี
 */
final class ZodiacAstroBrief
{
    /** slug ของราศี (เว็บจันทรา) → ลำดับราศี 0=เมษ … 11=มีน */
    public const SLUGS = [
        'aries' => 0, 'taurus' => 1, 'gemini' => 2, 'cancer' => 3, 'leo' => 4, 'virgo' => 5,
        'libra' => 6, 'scorpio' => 7, 'sagittarius' => 8, 'capricorn' => 9, 'aquarius' => 10, 'pisces' => 11,
    ];

    public const SIGN_TH = ['เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์', 'ตุลย์', 'พิจิก', 'ธนู', 'มังกร', 'กุมภ์', 'มีน'];

    /** ดาวเจ้าเรือนราศีตามตำราคลาสสิก (ไม่ใช้ดาวนอก) */
    public const RULER = ['Mars', 'Venus', 'Mercury', 'Moon', 'Sun', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Saturn', 'Jupiter'];

    /** ดาวที่ใช้ในดวงราศี */
    private const USED = ['Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Rahu'];

    /** ศุภเคราะห์ / บาปเคราะห์ (อาทิตย์นับเป็นบาปอ่อน — ร้อนแต่ไม่ร้าย) */
    private const BENEFIC = ['Jupiter', 'Venus', 'Moon', 'Mercury'];

    private const MALEFIC = ['Saturn', 'Mars', 'Rahu'];

    public const HOUSE_TH = [
        1 => 'ตัวตนและบุคลิก', 2 => 'การเงินและทรัพย์', 3 => 'การสื่อสารและพี่น้อง', 4 => 'บ้านและครอบครัว',
        5 => 'ความรักและโชค', 6 => 'งานประจำและสุขภาพ', 7 => 'คู่ครองและหุ้นส่วน', 8 => 'หนี้สินและเรื่องลึก',
        9 => 'การเดินทางและผู้ใหญ่', 10 => 'การงานและชื่อเสียง', 11 => 'รายได้เสริมและมิตร', 12 => 'การพักและรายจ่ายแฝง',
    ];

    /** ภพที่แต่ละด้านอ่าน */
    public const AREA_HOUSES = [
        'love' => [5, 7],
        'career' => [10, 6],
        'finance' => [2, 11, 8],
        'health' => [1, 6],
    ];

    public function __construct(private ?PlanetEphemeris $eph = null)
    {
        $this->eph ??= new PlanetEphemeris;
    }

    /**
     * @return array{ok:bool, slug:string, sign:string, thai_date:string, ruler:?array, moon:?array,
     *   planets:array, houses:array, aspects:array, retrogrades:array, scores:array, text:string}
     */
    public function build(string $slug, Carbon $date): array
    {
        $idx = self::SLUGS[$slug] ?? null;
        $thaiDate = $date->copy()->locale('th')->translatedFormat('l j F').' '.($date->year + 543);
        $empty = ['ok' => false, 'slug' => $slug, 'sign' => $idx !== null ? self::SIGN_TH[$idx] : '', 'thai_date' => $thaiDate,
            'ruler' => null, 'moon' => null, 'planets' => [], 'houses' => [], 'aspects' => [], 'retrogrades' => [],
            'scores' => ['overall' => 3, 'love' => 3, 'career' => 3, 'finance' => 3, 'health' => 3], 'text' => ''];
        if ($idx === null) {
            return $empty;
        }

        try {
            // เที่ยงวันเวลาไทย — ตัวแทนของทั้งวัน (ตามแบบ DailyAstroBrief)
            $pos = $this->eph->positions(Carbon::parse($date->format('Y-m-d').' 12:00:00', 'Asia/Bangkok'));
        } catch (\Throwable $e) {
            Log::warning('ZodiacAstroBrief: คำนวณดาวไม่สำเร็จ', ['slug' => $slug, 'err' => $e->getMessage()]);

            return $empty;
        }

        $planets = [];
        $houses = array_fill(1, 12, []);
        foreach (self::USED as $key) {
            $p = $pos[$key] ?? null;
            if ($p === null) {
                continue;
            }
            $lon = (float) $p['lon_tropical'];
            $sign = (int) floor($lon / 30.0) % 12;
            $house = (($sign - $idx + 12) % 12) + 1;
            $planets[$key] = [
                'key' => $key, 'th' => $p['th'], 'num' => $p['num'], 'lon' => $lon,
                'sign' => self::SIGN_TH[$sign], 'deg' => round(fmod($lon, 30.0), 1), 'house' => $house,
                'retro' => (bool) $p['retro'],
            ];
            $houses[$house][] = $key;
        }

        $rulerKey = self::RULER[$idx];
        $ruler = $planets[$rulerKey] ?? null;

        // มุมของดาวเจ้าเรือนราศีกับดาวดวงอื่น — นับเฉพาะมุมที่แน่น (≤ 8°) ให้ AI อ้างได้แต่ของที่มีน้ำหนักจริง
        $aspects = [];
        if ($ruler !== null) {
            foreach ($planets as $key => $p) {
                if ($key === $rulerKey) {
                    continue;
                }
                $a = AstroAspects::between($ruler['lon'], $p['lon'], $ruler['num'], $p['num']);
                if ($a !== null && $a['tight']) {
                    $aspects[] = ['with' => $p['th'], 'with_key' => $key] + $a;
                }
            }
            usort($aspects, fn ($x, $y) => $x['orb'] <=> $y['orb']);
        }

        $retro = array_values(array_map(fn ($p) => $p['th'], array_filter($planets, fn ($p) => $p['retro'])));

        $brief = [
            'ok' => true, 'slug' => $slug, 'sign' => self::SIGN_TH[$idx], 'thai_date' => $thaiDate,
            'ruler' => $ruler, 'moon' => $planets['Moon'] ?? null, 'planets' => $planets, 'houses' => $houses,
            'aspects' => array_slice($aspects, 0, 4), 'retrogrades' => $retro,
        ];
        $brief['scores'] = $this->scores($brief);
        $brief['text'] = $this->toPromptBlock($brief);

        return $brief;
    }

    /**
     * คะแนน 1-5 ต่อด้าน จากดาวในภพของด้านนั้น + เจ้าเรือนราศี — ตัวเลขชุดเดียวกันทุกครั้งที่คำนวณวันเดิม
     * (เดิม rand(2,5) / ค่าคงที่ 3 — ดู HoroscopeDailyService)
     *
     * @return array{overall:int, love:int, career:int, finance:int, health:int}
     */
    private function scores(array $b): array
    {
        $tone = function (array $houseNums) use ($b): float {
            $s = 0.0;
            foreach ($houseNums as $h) {
                foreach ($b['houses'][$h] as $key) {
                    $p = $b['planets'][$key];
                    $w = in_array($key, self::BENEFIC, true) ? 1.0 : (in_array($key, self::MALEFIC, true) ? -1.0 : -0.25);
                    // ดาวพักร = ให้ผลช้า/ย้อนกลับ — ลดน้ำหนักฝั่งดี เพิ่มน้ำหนักฝั่งร้าย
                    $s += $p['retro'] ? ($w > 0 ? $w * 0.5 : $w * 1.25) : $w;
                }
            }

            return $s;
        };

        $rulerTone = 0.0;
        foreach ($b['aspects'] as $a) {
            $rulerTone += $a['tone'] === 'ดี' ? 0.5 : ($a['tone'] === 'ร้าย' ? -0.5 : 0.0);
        }
        if (($b['ruler']['retro'] ?? false) === true) {
            $rulerTone -= 0.5;
        }

        $clamp = fn (float $v): int => (int) max(1, min(5, round(3 + $v)));
        $out = [];
        foreach (self::AREA_HOUSES as $area => $hs) {
            $out[$area] = $clamp($tone($hs) * 0.8 + $rulerTone * 0.5);
        }
        $out['overall'] = $clamp($tone([1]) * 0.6 + $rulerTone + (array_sum($out) - 12) / 8);

        return ['overall' => $out['overall'], 'love' => $out['love'], 'career' => $out['career'], 'finance' => $out['finance'], 'health' => $out['health']];
    }

    /** บล็อกข้อเท็จจริงสำหรับ prompt — ทุกบรรทัดสาวกลับไปหาตำแหน่งดาวจริงได้ */
    private function toPromptBlock(array $b): string
    {
        $lines = ["ราศี{$b['sign']} · {$b['thai_date']} (ราศีสากลแบบสายนะ · ตำแหน่งดาวตอนเที่ยงวันเวลาไทย)"];
        if ($b['ruler'] !== null) {
            $r = $b['ruler'];
            $lines[] = "ดาวเจ้าเรือนราศี: {$r['th']} สถิตราศี{$r['sign']} {$r['deg']}° = ภพที่ {$r['house']} (".self::HOUSE_TH[$r['house']].')'
                .($r['retro'] ? ' · กำลังพักร' : '');
        }
        if ($b['moon'] !== null) {
            $m = $b['moon'];
            $lines[] = "จันทร์วันนี้: ราศี{$m['sign']} = ภพที่ {$m['house']} (".self::HOUSE_TH[$m['house']].') — อารมณ์และเรื่องที่ใจจดจ่อของวันนี้';
        }
        $lines[] = 'ดาวในภพสำคัญ:';
        foreach ($b['houses'] as $h => $keys) {
            if ($keys === []) {
                continue;
            }
            $names = array_map(fn ($k) => $b['planets'][$k]['th'].($b['planets'][$k]['retro'] ? '(พักร)' : ''), $keys);
            $lines[] = "- ภพที่ {$h} ".self::HOUSE_TH[$h].': '.implode(', ', $names);
        }
        if ($b['aspects'] !== []) {
            $lines[] = 'มุมของดาวเจ้าเรือนราศี (แน่น ≤ 8°):';
            foreach ($b['aspects'] as $a) {
                $lines[] = "- {$b['ruler']['th']} {$a['name']} {$a['with']} — {$a['nature']} ({$a['tone']}) · คลาด {$a['orb']}°";
            }
        }
        if ($b['retrogrades'] !== []) {
            $lines[] = 'ดาวที่พักรอยู่วันนี้: '.implode(', ', $b['retrogrades']);
        }
        $s = $b['scores'];
        $lines[] = "คะแนนจากตำแหน่งดาว (ใช้ตามนี้): ภาพรวม {$s['overall']}/5 · ความรัก {$s['love']}/5 · การงาน {$s['career']}/5 · การเงิน {$s['finance']}/5 · สุขภาพ {$s['health']}/5";

        return implode("\n", $lines);
    }
}
