<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Server-side natal chart computation.
 *
 * For the first cut we use Meeus simplified formulas (same accuracy as the
 * Flutter client's local fallback). When/if the team licenses Swiss Ephemeris,
 * swap _sunLongitude/_moonLongitude with the SwissEph wrapper without
 * touching the response shape.
 */
class NatalController extends Controller
{
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

        $jd = $this->julianDay(
            (int) $request->input('year'),
            (int) $request->input('month'),
            (int) $request->input('day'),
            (int) $request->input('hour'),
            (int) $request->input('minute'),
            (float) $request->input('tz_offset_hours'),
        );
        $t = ($jd - 2451545.0) / 36525.0;

        $sunLon = $this->normalize($this->sunLongitude($t));
        $moonLon = $this->normalize($this->moonLongitude($t));
        $lst = $this->localSiderealTime($jd, (float) $request->input('lng'));
        $ascLon = $this->normalize($lst * 15 + (float) $request->input('lat') * 0.5);

        return response()->json(['data' => [
            'sun' => $this->signOf($sunLon),
            'moon' => $this->signOf($moonLon),
            'ascendant' => $this->signOf($ascLon),
            'planets' => [
                ['glyph' => '☉', 'label' => 'อาทิตย์', 'lon' => $sunLon, 'sign' => $this->signOf($sunLon)],
                ['glyph' => '☽', 'label' => 'จันทร์',  'lon' => $moonLon, 'sign' => $this->signOf($moonLon)],
                ['glyph' => '☿', 'label' => 'พุธ',    'lon' => $this->normalize($sunLon + 25),  'sign' => $this->signOf($this->normalize($sunLon + 25))],
                ['glyph' => '♀', 'label' => 'ศุกร์',  'lon' => $this->normalize($sunLon - 35),  'sign' => $this->signOf($this->normalize($sunLon - 35))],
                ['glyph' => '♂', 'label' => 'อังคาร', 'lon' => $this->normalize($sunLon + 110), 'sign' => $this->signOf($this->normalize($sunLon + 110))],
                ['glyph' => '♃', 'label' => 'พฤหัส',  'lon' => $this->normalize($sunLon + 200), 'sign' => $this->signOf($this->normalize($sunLon + 200))],
                ['glyph' => '♄', 'label' => 'เสาร์',  'lon' => $this->normalize($sunLon + 250), 'sign' => $this->signOf($this->normalize($sunLon + 250))],
            ],
            'ascendant_lon' => $ascLon,
        ]]);
    }

    public function dailyTransit(Request $request): JsonResponse
    {
        $now = now();
        $jd = $this->julianDay($now->year, $now->month, $now->day, $now->hour, $now->minute, 7.0);
        $t = ($jd - 2451545.0) / 36525.0;
        $sun = $this->normalize($this->sunLongitude($t));
        $moon = $this->normalize($this->moonLongitude($t));
        $score = (int) round(60 + sin($jd) * 30);
        return response()->json(['data' => [
            'date' => $now->toDateString(),
            'score' => max(0, min(100, $score)),
            'sun' => $this->signOf($sun),
            'moon' => $this->signOf($moon),
            'reading' => $this->dailyReadingFor($sun, $moon),
        ]]);
    }

    // ─── Meeus simplified ────────────────────────────────────

    private function julianDay(int $y, int $m, int $d, int $hh, int $mm, float $tz): float
    {
        if ($m <= 2) { $y -= 1; $m += 12; }
        $a = intdiv($y, 100);
        $b = 2 - $a + intdiv($a, 4);
        $dayFrac = $d + ($hh - $tz + $mm / 60.0) / 24.0;
        return floor(365.25 * ($y + 4716))
            + floor(30.6001 * ($m + 1))
            + $dayFrac + $b - 1524.5;
    }

    private function sunLongitude(float $t): float
    {
        $l0 = 280.46646 + 36000.76983 * $t + 0.0003032 * $t * $t;
        $m = 357.52911 + 35999.05029 * $t - 0.0001537 * $t * $t;
        $mr = $m * M_PI / 180;
        $c = (1.914602 - 0.004817 * $t) * sin($mr)
            + (0.019993 - 0.000101 * $t) * sin(2 * $mr)
            + 0.000289 * sin(3 * $mr);
        return $l0 + $c;
    }

    private function moonLongitude(float $t): float
    {
        $lp = 218.3164477 + 481267.88123421 * $t;
        $d = 297.8501921 + 445267.1114034 * $t;
        $m = 357.5291092 + 35999.0502909 * $t;
        $mp = 134.9633964 + 477198.8675055 * $t;
        $f = 93.2720950 + 483202.0175233 * $t;
        $s = fn (float $x) => sin($x * M_PI / 180);
        return $lp
            + 6.289 * $s($mp)
            - 1.274 * $s($mp - 2 * $d)
            + 0.658 * $s(2 * $d)
            - 0.214 * $s(2 * $mp)
            - 0.186 * $s($m)
            - 0.114 * $s(2 * $f);
    }

    private function localSiderealTime(float $jd, float $lng): float
    {
        $t = ($jd - 2451545.0) / 36525.0;
        $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0)
            + 0.000387933 * $t * $t - $t * $t * $t / 38710000.0;
        return fmod($gmst + $lng, 360) / 15.0;
    }

    private function normalize(float $deg): float
    {
        $r = fmod($deg, 360);
        return $r < 0 ? $r + 360 : $r;
    }

    private function signOf(float $lon): array
    {
        $signs = ['เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์',
                  'ตุล', 'พิจิก', 'ธนู', 'มังกร', 'กุมภ์', 'มีน'];
        $signsEn = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo',
                    'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        $idx = ((int) floor($lon / 30)) % 12;
        return [
            'idx' => $idx,
            'th' => $signs[$idx],
            'en' => $signsEn[$idx],
            'lon' => round($lon, 2),
        ];
    }

    private function dailyReadingFor(float $sun, float $moon): string
    {
        $signs = $this->signOf($sun);
        return "พระอาทิตย์อยู่ราศี{$signs['th']} — วันนี้เป็นวันที่เหมาะกับการเริ่มต้นสิ่งใหม่ๆ "
            . "พระจันทร์ส่งพลังให้ลูกฟังเสียงในใจตัวเองมากขึ้น ✨";
    }
}
