<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\AstroAspects;
use App\Services\Fortune\DailyAstroBrief;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneChartService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * ผูกดวงกำเนิด + ดาวจรรายวัน ให้แอพจันทรา
 *
 * 🪐 (2026-11-07) เลิกสูตรปลอม — เดิมพุธ/ศุกร์/อังคาร/พฤหัส/เสาร์ คือ "ลองจิจูดอาทิตย์ ± ค่าคงที่"
 *    (+25/−35/+110/+200/+250) ลัคนา = LST×15 + ละติจูด×0.5 และคะแนนรายวัน = 60 + sin(JD)×30
 *    ตอนนี้ใช้เครื่องเดียวกับบอทแม่หมอทุกตัว:
 *      - PlanetEphemeris — ตำแหน่งดาวจริง 10 ดวง (นิรายนะ ลาหิรี · เกตุไทยสุริยยาตร์)
 *      - ThaiAstrologyService::siderealLagnaAtJd — ลัคนาจริงจากเวลา + พิกัด
 *      - FortuneChartService::CHAOCHANA — ดาวเจ้าชะตา มิตร ศัตรู ตามตำรา (ย่ำรุ่ง 06:00 · พุธกลางคืน = ราหู)
 *      - DailyAstroBrief — ดวงรายวันของคนเกิดวันนั้น (ชุดเดียวกับโพสดวงรายวันของเพจ)
 *    รูปทรง response เดิมยังอยู่ครบ (sun/moon/ascendant/planets/ascendant_lon · date/score/sun/moon/reading)
 *    ราศีเป็นแบบไทย (นิรายนะ) — ฟิลด์ใหม่ `zodiac_frame` บอกไว้ และมี `lon_tropical` ให้ใครที่ต้องการแบบสากล
 */
class NatalController extends Controller
{
    private const SIGNS_EN = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];

    private const GLYPH = ['Sun' => '☉', 'Moon' => '☽', 'Mars' => '♂', 'Mercury' => '☿', 'Jupiter' => '♃', 'Venus' => '♀', 'Saturn' => '♄', 'Rahu' => '☊', 'Ketu' => '☋', 'Uranus' => '♅'];

    public function compute(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'year' => 'required|integer|min:1900|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'day' => 'required|integer|min:1|max:31',
            'hour' => 'required|integer|min:0|max:23',
            'minute' => 'required|integer|min:0|max:59',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'tz_offset_hours' => 'required|numeric|between:-12,14',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }
        if (! checkdate((int) $request->input('month'), (int) $request->input('day'), (int) $request->input('year'))) {
            return response()->json(['message' => 'วันที่ไม่มีอยู่จริง'], 422);
        }

        // เวลาท้องถิ่นของสถานที่เกิด → เวลาไทย (PlanetEphemeris รับเวลาไทย UTC+7)
        $local = Carbon::create(
            (int) $request->input('year'), (int) $request->input('month'), (int) $request->input('day'),
            (int) $request->input('hour'), (int) $request->input('minute'), 0, 'UTC',
        );
        $thai = $local->copy()->addMinutes((int) round((7.0 - (float) $request->input('tz_offset_hours')) * 60));

        $eph = new PlanetEphemeris;
        $astro = new ThaiAstrologyService;
        $pos = $eph->positions($thai);
        $jdUT = $eph->julianDay($thai) - 7.0 / 24.0;
        $asc = $astro->siderealLagnaAtJd($jdUT, (float) $request->input('lat'), (float) $request->input('lng'), $eph);
        $ascSign = (int) floor($asc / 30.0) % 12;

        $planets = [];
        foreach ($pos as $key => $p) {
            $planets[] = [
                'key' => strtolower($key),
                'glyph' => self::GLYPH[$key] ?? '',
                'label' => $p['th'],
                'num' => $p['num'],
                'lon' => round($p['lon'], 2),
                'lon_tropical' => round($p['lon_tropical'], 2),
                'sign' => $this->signOf($p['lon']),
                'house' => ((int) $p['sign_index'] - $ascSign + 12) % 12 + 1,
                'retro' => (bool) $p['retro'],
            ];
        }

        // ดาวเจ้าชะตาตามตำรา — วันไทยเริ่มย่ำรุ่ง 06:00 · พุธหลัง 18:00 = พุธกลางคืน (ราหู)
        $birthHour = $thai->hour + $thai->minute / 60;
        $dayIndex = $astro->thaiWeekday($thai->dayOfWeek, $birthHour);
        if ($astro->isWednesdayNight($dayIndex, $birthHour)) {
            $dayIndex = FortuneChartService::WEDNESDAY_NIGHT;
        }
        $chaochana = FortuneChartService::chaochanaFor($dayIndex);

        return response()->json(['data' => [
            'sun' => $this->signOf($pos['Sun']['lon']),
            'moon' => $this->signOf($pos['Moon']['lon']),
            'ascendant' => $this->signOf($asc),
            'planets' => $planets,
            'ascendant_lon' => round($asc, 2),
            'zodiac_frame' => 'sidereal_lahiri',
            'thai' => $chaochana === null ? null : [
                'day_index' => $dayIndex,
                'ruler' => $this->planetTh($chaochana['planet']),
                'friends' => array_map(fn ($k) => $this->planetTh($k), $chaochana['friends'] ?? []),
                'enemies' => array_map(fn ($k) => $this->planetTh($k), $chaochana['enemies'] ?? []),
            ],
            'aspects' => array_slice(array_values(array_filter(
                AstroAspects::withinChart($pos),
                fn ($a) => $a['tight'],
            )), 0, 8),
        ]]);
    }

    /**
     * ดาวจรวันนี้ — ส่ง birth_date (Y-m-d) และ birth_hour (ไม่บังคับ) มาด้วย = ดวงรายวันของคนเกิดวันนั้นจริง
     * ไม่ส่ง = ภาพรวมท้องฟ้าวันนี้ (คะแนนจากมุมดาวจริง ไม่ใช่สุ่ม)
     */
    public function dailyTransit(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'birth_date' => 'nullable|date_format:Y-m-d|before:tomorrow|after:1900-01-01',
            'birth_hour' => 'nullable|numeric|between:0,23.99',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $now = Carbon::now('Asia/Bangkok');
        $pos = (new PlanetEphemeris)->positions(Carbon::parse($now->format('Y-m-d').' 12:00:00', 'Asia/Bangkok'));
        $sun = $this->signOf($pos['Sun']['lon']);
        $moon = $this->signOf($pos['Moon']['lon']);
        $retro = array_values(array_map(fn ($p) => $p['th'], array_filter($pos, fn ($p) => $p['retro'])));

        $brief = null;
        if ($request->filled('birth_date')) {
            $astro = new ThaiAstrologyService;
            $birth = Carbon::parse($request->input('birth_date'));
            $hour = $request->filled('birth_hour') ? (float) $request->input('birth_hour') : null;
            $day = $astro->thaiWeekday((int) $birth->dayOfWeek, $hour);
            if ($astro->isWednesdayNight($day, $hour)) {
                $day = FortuneChartService::WEDNESDAY_NIGHT;
            }
            $brief = (new DailyAstroBrief)->build($day, $now->copy()->startOfDay());
            if (! ($brief['ok'] ?? false)) {
                $brief = null;
            }
        }

        if ($brief !== null) {
            $score = (int) max(0, min(100, ($brief['score_hint'] ?? 3) * 20));
            $reading = $this->personalReading($brief);
        } else {
            $tight = array_filter(AstroAspects::withinChart($pos), fn ($a) => $a['tight']);
            $good = count(array_filter($tight, fn ($a) => $a['tone'] === 'ดี'));
            $bad = count(array_filter($tight, fn ($a) => $a['tone'] === 'ร้าย'));
            $score = (int) max(0, min(100, 60 + ($good - $bad) * 8 - count($retro) * 3));
            $first = reset($tight) ?: null;
            $reading = "วันนี้พระอาทิตย์สถิตราศี{$sun['th']} จันทร์อยู่ราศี{$moon['th']}"
                .($first !== null ? " · {$first['a']}{$first['name']}{$first['b']} ({$first['nature']})" : '')
                .($retro !== [] ? ' · ดาวที่พักร: '.implode(', ', $retro) : '');
        }

        return response()->json(['data' => [
            'date' => $now->toDateString(),
            'score' => $score,
            'sun' => $sun,
            'moon' => $moon,
            'retrogrades' => $retro,
            'reading' => $reading,
            'personal' => $brief !== null,
        ]]);
    }

    private function personalReading(array $b): string
    {
        $parts = ["คนเกิดวัน{$b['day_name']}"];
        $lord = (string) ($b['lord']['th'] ?? '');
        if ($lord !== '') {
            $parts[] = "ดาวเจ้าชะตา{$lord}".(! empty($b['lord']['sign']) ? "สถิตราศี{$b['lord']['sign']}" : '').(! empty($b['lord']['retro']) ? ' (พักร)' : '');
        }
        foreach (array_slice($b['aspects'] ?? [], 0, 2) as $a) {
            if (! empty($a['aspect']) && ! empty($a['other'])) {
                $parts[] = "{$lord} {$a['aspect']} {$a['other']}".(! empty($a['nature']) ? " ({$a['nature']})" : '');
            }
        }

        return implode(' · ', $parts);
    }

    private function planetTh(string $key): string
    {
        return FortuneChartService::PLANETS[$key]['name'] ?? $key;
    }

    /** @return array{idx:int, th:string, en:string, lon:float} */
    private function signOf(float $lon): array
    {
        $lon = fmod($lon, 360.0);
        if ($lon < 0) {
            $lon += 360.0;
        }
        $idx = (int) floor($lon / 30.0) % 12;

        return ['idx' => $idx, 'th' => PlanetEphemeris::SIGNS[$idx], 'en' => self::SIGNS_EN[$idx], 'lon' => round($lon, 2)];
    }
}
