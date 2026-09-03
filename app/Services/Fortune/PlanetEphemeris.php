<?php

namespace App\Services\Fortune;

use Carbon\Carbon;

/**
 * 🪐 PlanetEphemeris — เครื่องคำนวณตำแหน่งดาวเอง (ไม่พึ่ง library/API)
 * ────────────────────────────────────────────────────────────────────
 * User directive (2026-06-03): "ทำให้ดี ไม่ต้องอาศัยใคร เราต้องรู้หมด คำนวณได้
 *   เพื่อประกอบการทำนายไพ่ทาโร่ 99 บาท"
 *
 * คำนวณลองจิจูดสุริยวิถี (geocentric ecliptic longitude) ของดาวพระเคราะห์ไทยครบ 9:
 *   ☉ อาทิตย์ · ☽ จันทร์ · ♂ อังคาร · ☿ พุธ · ♃ พฤหัสบดี · ♀ ศุกร์ · ♄ เสาร์ · ☊ ราหู · ☋ เกตุ
 *
 * วิธีคำนวณ (pure PHP — ไม่มี dependency):
 *   - JD จากปฏิทินเกรกอเรียน (Meeus)
 *   - อาทิตย์/ดาวเคราะห์: Keplerian orbital elements (JPL 1800–2050) → แก้สมการเคปเลอร์
 *     → พิกัดเฮลิโอเซนทริก → ลบโลก → geocentric ecliptic longitude
 *   - จันทร์: อนุกรมคาบหลัก ~20 พจน์ (Meeus ch.47) → แม่น ~±0.3°
 *   - ราหู/เกตุ: จุดโหนดจันทร์เฉลี่ย (mean node)
 *   - ตรวจพักร (retrograde): เทียบลองจิจูด JD vs JD+1
 *
 * 🎯 ความแม่น: ระดับ "ราศี" (sign) แม่นเกือบ 100% + ตรวจพักรได้
 *   (ไม่ระบุองศา-ลิปดาเป๊ะระดับโหรมืออาชีพ แต่พอสำหรับประกอบการทำนายไพ่)
 *
 * 🗺️ ระบบราศี: **นิรายนะ (sidereal)** ตามโหราศาสตร์ไทย/สุริยยาตร์
 *   ────────────────────────────────────────────────────────────────
 *   🚨 (2026-09-03) เดิมคลาสนี้คืนราศีแบบ **สายนะ (tropical/สากล)** ซึ่ง **ผิดสำหรับ
 *      โหราศาสตร์ไทย** — ต่างกันเกือบเต็มราศี (อายนางศ ~24° ในปี 2569)
 *      ตัวอย่างที่โหรจริงจับได้ทันที: 3 ก.ย. 2569 ระบบเดิมบอก "อาทิตย์สถิตราศีกันย์"
 *      แต่ปฏิทินโหรไทยทุกเล่มบอก **ราศีสิงห์** (ราศีสิงห์ = 17 ส.ค. – 16 ก.ย.)
 *      เนื้อหานี้โพสสาธารณะบนเพจ ⇒ ผิดราศี = โดนดิสเครดิตทั้งเพจ
 *
 *   วิธีแก้: ลบ "อายนางศ" (ayanamsa) ออกจากลองจิจูดสายนะ → ได้ลองจิจูดนิรายนะ
 *   ใช้ค่าแบบ Lahiri (มาตรฐานที่ปฏิทินโหรไทยสมัยใหม่และ Swiss Ephemeris ใช้)
 *   ตรวจแล้ว: ขอบราศีที่ได้ตรงกับช่วงวันราศีไทย (16–17 ของเดือน) ทั้ง 12 ราศี
 *
 *   - `lon`           = ลองจิจูด **นิรายนะ** (ใช้คู่กับ `sign` เสมอ)
 *   - `lon_tropical`  = ลองจิจูดสายนะดิบ เก็บไว้ตรวจสอบ/เทียบกับโปรแกรมสากล
 *   - มุมสัมพันธ์ (กุม/เล็ง/ตรีโกณ) ใช้ผลต่างลองจิจูด จึงเท่ากันทั้งสองระบบ
 */
class PlanetEphemeris
{
    /** องศา/ราศี */
    private const DEG_PER_SIGN = 30.0;

    /** 12 ราศี (เริ่มเมษ = 0°) */
    public const SIGNS = ['เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์', 'ตุลย์', 'พิจิก', 'ธนู', 'มังกร', 'กุมภ์', 'มีน'];

    /** ป้ายราศีแบบ "ไทย (อังกฤษ)" — เรียงตรงกับ SIGNS */
    public const SIGN_LABELS = [
        'เมษ (Aries)', 'พฤษภ (Taurus)', 'เมถุน (Gemini)', 'กรกฎ (Cancer)',
        'สิงห์ (Leo)', 'กันย์ (Virgo)', 'ตุลย์ (Libra)', 'พิจิก (Scorpio)',
        'ธนู (Sagittarius)', 'มังกร (Capricorn)', 'กุมภ์ (Aquarius)', 'มีน (Pisces)',
    ];

    /**
     * อายนางศ (ayanamsa) แบบ Lahiri ณ J2000.0 — องศา
     *
     * = 23°51'10.53" ค่าอ้างอิงเดียวกับ Swiss Ephemeris SE_SIDM_LAHIRI
     */
    private const AYANAMSA_J2000 = 23.85292472;

    /**
     * อัตราเพิ่มของอายนางศ = อัตราพรีเซสชัน 50.2888 พิลิปดา/ปี → องศา/ปีจูเลียน
     *
     * โมเดลเชิงเส้นนี้คลาดจากค่าจริงไม่ถึง 0.001° ในช่วง ±100 ปีรอบ J2000
     * ซึ่งละเอียดเกินพอสำหรับงานระดับ "ราศี" (30° ต่อราศี)
     */
    private const AYANAMSA_RATE = 50.2888 / 3600.0;

    /**
     * Keplerian elements (JPL, ใช้ช่วง 1800–2050 AD), epoch J2000
     * [a, e, I, L, ϖ(peri), Ω(node)] + อัตราต่อศตวรรษ (cy)
     */
    private const ELEMENTS = [
        'Mercury' => [0.38709927, 0.20563593, 7.00497902, 252.25032350, 77.45779628, 48.33076593,
            0.00000037, 0.00001906, -0.00594749, 149472.67411175, 0.16047689, -0.12534081],
        'Venus' => [0.72333566, 0.00677672, 3.39467605, 181.97909950, 131.60246718, 76.67984255,
            0.00000390, -0.00004107, -0.00078890, 58517.81538729, 0.00268329, -0.27769418],
        'Earth' => [1.00000261, 0.01671123, -0.00001531, 100.46457166, 102.93768193, 0.0,
            0.00000562, -0.00004392, -0.01294668, 35999.37244981, 0.32327364, 0.0],
        'Mars' => [1.52371034, 0.09339410, 1.84969142, -4.55343205, -23.94362959, 49.55953891,
            0.00001847, 0.00007882, -0.00813131, 19140.30268499, 0.44441088, -0.29257343],
        'Jupiter' => [5.20288700, 0.04838624, 1.30439695, 34.39644051, 14.72847983, 100.47390909,
            -0.00011607, -0.00013253, -0.00183714, 3034.74612775, 0.21252668, 0.20469106],
        'Saturn' => [9.53667594, 0.05386179, 2.48599187, 49.95424423, 92.59887831, 113.66242448,
            -0.00125060, -0.00050991, 0.00193609, 1222.49362201, -0.41897216, -0.28867794],
    ];

    /** Planet (en) → ไทย + เลขดาว + สัญลักษณ์ */
    public const PLANET_TH = [
        'Sun' => ['th' => 'อาทิตย์', 'num' => 1, 'sym' => '☉'],
        'Moon' => ['th' => 'จันทร์', 'num' => 2, 'sym' => '☽'],
        'Mars' => ['th' => 'อังคาร', 'num' => 3, 'sym' => '♂'],
        'Mercury' => ['th' => 'พุธ', 'num' => 4, 'sym' => '☿'],
        'Jupiter' => ['th' => 'พฤหัสบดี', 'num' => 5, 'sym' => '♃'],
        'Venus' => ['th' => 'ศุกร์', 'num' => 6, 'sym' => '♀'],
        'Saturn' => ['th' => 'เสาร์', 'num' => 7, 'sym' => '♄'],
        'Rahu' => ['th' => 'ราหู', 'num' => 8, 'sym' => '☊'],
        'Ketu' => ['th' => 'เกตุ', 'num' => 9, 'sym' => '☋'],
    ];

    /**
     * คำนวณตำแหน่งดาวทั้ง 9 ณ วันเวลาที่กำหนด
     *
     * คีย์ผลลัพธ์เรียงตามเลขดาว (อาทิตย์→เกตุ) · `lon` เป็นนิรายนะ · `lon_tropical` เป็นสายนะ
     *
     * @param  Carbon  $dt  วันเวลาเกิด (ถือเป็นเวลาไทย UTC+7)
     * @return array<string, array{lon:float, lon_tropical:float, ayanamsa:float, sign:string, sign_index:int, retro:bool, th:string, num:int, sym:string}>
     */
    public function positions(Carbon $dt): array
    {
        // เวลาไทย → UT (ลบ 7 ชม.)
        $jd = $this->julianDay($dt) - (7.0 / 24.0);

        $order = ['Sun', 'Moon', 'Mars', 'Mercury', 'Jupiter', 'Venus', 'Saturn', 'Rahu', 'Ketu'];
        $out = [];

        // อายนางศของวันนั้น — ใช้ค่าเดียวกับทุกดาว (เป็นการหมุนกรอบราศีทั้งวง)
        $ayanamsa = $this->ayanamsa($jd);

        foreach ($order as $planet) {
            $lon = $this->longitude($planet, $jd);
            $lonNext = $this->longitude($planet, $jd + 1.0);
            // พักร: ลองจิจูดถอยหลัง (จัดการ wrap 360°)
            // ⚠️ ตรวจจากลองจิจูดสายนะได้เลย — ลบอายนางศคือลบค่าคงที่ออกจากทั้งสองตัว
            $delta = $this->normalize360($lonNext - $lon + 180.0) - 180.0;
            $retro = $delta < 0;

            $lonTropical = $this->normalize360($lon);
            $lonSidereal = $this->normalize360($lonTropical - $ayanamsa);

            $signIndex = (int) floor($lonSidereal / self::DEG_PER_SIGN) % 12;
            $meta = self::PLANET_TH[$planet];

            $out[$planet] = [
                // 🇹🇭 lon = นิรายนะ (ตรงกับ sign เสมอ) — ผู้เรียกที่ทำ fmod(lon,30)
                //    เพื่อหา "องศาในราศี" จึงได้ค่าที่สอดคล้องกับชื่อราศีที่รายงาน
                'lon' => $lonSidereal,
                'lon_tropical' => $lonTropical,
                'ayanamsa' => $ayanamsa,
                'sign' => self::SIGNS[$signIndex],
                'sign_index' => $signIndex,
                'retro' => $retro,
                'th' => $meta['th'],
                'num' => $meta['num'],
                'sym' => $meta['sym'],
            ];
        }

        return $out;
    }

    /**
     * 🇹🇭 อายนางศ (ayanamsa) ณ วันที่กำหนด — ผลต่างระหว่างราศีสายนะกับนิรายนะ
     *
     * ใช้ค่าแบบ Lahiri: 23°51'10.53" ที่ J2000 แล้วเพิ่มตามอัตราพรีเซสชัน
     * ปี 2569 (2026) ได้ ~24.22° = 24°13' ตรงกับตารางอายนางศมาตรฐาน
     *
     * @param  float  $jd  Julian Day (UT)
     * @return float องศา
     */
    public function ayanamsa(float $jd): float
    {
        $years = ($jd - 2451545.0) / 365.25;

        return self::AYANAMSA_J2000 + self::AYANAMSA_RATE * $years;
    }

    /**
     * 🇹🇭 ราศีเกิด (ราศีที่ดวงอาทิตย์สถิตในวันนั้น) แบบนิรายนะ
     *
     * 🚨 (2026-09-03) ใช้แทนตารางช่วงวันแบบสากล (มังกร 22 ธ.ค.–19 ม.ค. ฯลฯ) ที่เดิม
     *    ใช้อยู่ใน ThaiAstrologyService/FortuneAIService — ตารางนั้นเป็น **ราศีฝรั่ง**
     *    ต่างจากราศีไทยเกือบครึ่งเดือน (เช่น เกิด 1 พ.ค. ฝรั่งว่าพฤษภ ไทยว่าเมษ)
     *    และยังขัดกับบรรทัด "ตำแหน่งดาวอาทิตย์" ในผังดวงเดียวกันด้วย = โหรจับได้ทันที
     *
     *    คำนวณจากดวงอาทิตย์จริง จึงถูกต้องทุกปี ไม่ต้องคอยขยับตารางตามอายนางศ
     *    (ขอบราศีเลื่อนราว 1 วันต่อ 72 ปี — ตารางตายตัวจะเริ่มเพี้ยนกับคนเกิดยุคก่อน)
     *
     * @param  Carbon  $date  วันเกิด (เวลาไม่ทราบ = ใช้เที่ยงวัน)
     * @return string เช่น "สิงห์ (Leo)"
     */
    public function zodiacSignLabel(Carbon $date): string
    {
        $idx = $this->positions($date->copy()->setTime(12, 0, 0))['Sun']['sign_index'] ?? 0;

        return self::SIGN_LABELS[$idx] ?? self::SIGN_LABELS[0];
    }

    /**
     * 🇹🇭 ราศีนิรายนะของลองจิจูดสายนะที่ให้มา
     *
     * ใช้กับตัวเลขที่คำนวณนอกคลาสนี้ (เช่น ลัคนาใน ThaiAstrologyService)
     * ให้แปลงกรอบด้วยสูตรเดียวกัน จะได้ไม่มีสองมาตรฐานในระบบ
     *
     * @param  float  $lonTropical  ลองจิจูดสายนะ (องศา)
     * @return array{lon:float, sign:string, sign_index:int}
     */
    public function toSidereal(float $lonTropical, float $jd): array
    {
        $lon = $this->normalize360($lonTropical - $this->ayanamsa($jd));
        $idx = (int) floor($lon / self::DEG_PER_SIGN) % 12;

        return ['lon' => $lon, 'sign' => self::SIGNS[$idx], 'sign_index' => $idx];
    }

    /**
     * ลองจิจูดสุริยวิถี geocentric (องศา 0–360) ของดาวที่ระบุ
     */
    public function longitude(string $planet, float $jd): float
    {
        return match ($planet) {
            'Sun' => $this->sunLongitude($jd),
            'Moon' => $this->moonLongitude($jd),
            'Rahu' => $this->meanNode($jd),
            'Ketu' => $this->normalize360($this->meanNode($jd) + 180.0),
            default => $this->planetLongitude($planet, $jd),
        };
    }

    /**
     * Julian Day (Meeus) จาก Carbon (รวมเศษเวลา)
     */
    public function julianDay(Carbon $dt): float
    {
        $year = (int) $dt->year;
        $month = (int) $dt->month;
        $day = (int) $dt->day;
        $frac = ($dt->hour + $dt->minute / 60.0 + $dt->second / 3600.0) / 24.0;

        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        $a = (int) floor($year / 100);
        $b = 2 - $a + (int) floor($a / 4);

        return floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1))
            + $day + $frac + $b - 1524.5;
    }

    /** ศตวรรษจูเลียนนับจาก J2000 */
    private function centuries(float $jd): float
    {
        return ($jd - 2451545.0) / 36525.0;
    }

    /**
     * ☉ ลองจิจูดอาทิตย์ (tropical of date — Meeus ch.25 low precision)
     */
    private function sunLongitude(float $jd): float
    {
        $t = $this->centuries($jd);
        $l0 = 280.46646 + 36000.76983 * $t + 0.0003032 * $t * $t;
        $m = 357.52911 + 35999.05029 * $t - 0.0001537 * $t * $t;
        $mr = deg2rad($this->normalize360($m));
        $c = (1.914602 - 0.004817 * $t - 0.000014 * $t * $t) * sin($mr)
            + (0.019993 - 0.000101 * $t) * sin(2 * $mr)
            + 0.000289 * sin(3 * $mr);

        return $this->normalize360($l0 + $c);
    }

    /**
     * ☽ ลองจิจูดจันทร์ (Meeus ch.47 — พจน์คาบหลัก ~ ±0.3°)
     */
    private function moonLongitude(float $jd): float
    {
        $t = $this->centuries($jd);

        $lp = 218.3164477 + 481267.88123421 * $t - 0.0015786 * $t * $t + ($t ** 3) / 538841 - ($t ** 4) / 65194000;
        $d = 297.8501921 + 445267.1114034 * $t - 0.0018819 * $t * $t + ($t ** 3) / 545868;
        $m = 357.5291092 + 35999.0502909 * $t - 0.0001536 * $t * $t;
        $mp = 134.9633964 + 477198.8675055 * $t + 0.0087414 * $t * $t + ($t ** 3) / 69699;
        $f = 93.2720950 + 483202.0175233 * $t - 0.0036539 * $t * $t;

        $d = deg2rad($this->normalize360($d));
        $m = deg2rad($this->normalize360($m));
        $mp = deg2rad($this->normalize360($mp));
        $f = deg2rad($this->normalize360($f));

        // พจน์ longitude หลัก (สัมประสิทธิ์เป็นองศา)
        $sum = 6.288774 * sin($mp)
            + 1.274027 * sin(2 * $d - $mp)
            + 0.658314 * sin(2 * $d)
            + 0.213618 * sin(2 * $mp)
            - 0.185116 * sin($m)
            - 0.114332 * sin(2 * $f)
            + 0.058793 * sin(2 * $d - 2 * $mp)
            + 0.057066 * sin(2 * $d - $m - $mp)
            + 0.053322 * sin(2 * $d + $mp)
            + 0.045758 * sin(2 * $d - $m)
            - 0.040923 * sin($m - $mp)
            - 0.034720 * sin($d)
            - 0.030383 * sin($m + $mp)
            + 0.015327 * sin(2 * $d - 2 * $f)
            - 0.012528 * sin($mp + 2 * $f)
            + 0.010980 * sin($mp - 2 * $f)
            + 0.010675 * sin(4 * $d - $mp)
            + 0.010034 * sin(3 * $mp)
            + 0.008548 * sin(4 * $d - 2 * $mp)
            - 0.007888 * sin(2 * $d + $m - $mp)
            - 0.006766 * sin(2 * $d + $m);

        return $this->normalize360($lp + $sum);
    }

    /**
     * ☊ ลองจิจูดราหู (จุดโหนดขึ้นเฉลี่ยของจันทร์ — mean ascending node)
     */
    private function meanNode(float $jd): float
    {
        $t = $this->centuries($jd);
        $omega = 125.04452 - 1934.136261 * $t + 0.0020708 * $t * $t + ($t ** 3) / 450000;

        return $this->normalize360($omega);
    }

    /**
     * ดาวเคราะห์ (พุธ/ศุกร์/อังคาร/พฤหัส/เสาร์) — Keplerian → geocentric ecliptic longitude
     */
    private function planetLongitude(string $planet, float $jd): float
    {
        $t = $this->centuries($jd);
        [$xp, $yp, $zp] = $this->heliocentric($planet, $t);
        [$xe, $ye, $ze] = $this->heliocentric('Earth', $t);

        $xg = $xp - $xe;
        $yg = $yp - $ye;

        return $this->normalize360(rad2deg(atan2($yg, $xg)));
    }

    /**
     * พิกัดเฮลิโอเซนทริก ecliptic (J2000) ของดาว ณ เวลา T (ศตวรรษ)
     *
     * @return array{0:float,1:float,2:float} [x, y, z] หน่วย AU
     */
    private function heliocentric(string $planet, float $t): array
    {
        $e0 = self::ELEMENTS[$planet];
        $a = $e0[0] + $e0[6] * $t;
        $e = $e0[1] + $e0[7] * $t;
        $inc = deg2rad($e0[2] + $e0[8] * $t);
        $l = $e0[3] + $e0[9] * $t;
        $peri = $e0[4] + $e0[10] * $t;
        $node = deg2rad($e0[5] + $e0[11] * $t);

        // mean anomaly → [-180,180]
        $m = $this->normalize360($l - $peri);
        if ($m > 180) {
            $m -= 360;
        }
        $w = deg2rad(($e0[4] + $e0[10] * $t) - ($e0[5] + $e0[11] * $t)); // argument of perihelion ω = ϖ - Ω
        $mr = deg2rad($m);

        // แก้สมการเคปเลอร์ (Newton-Raphson)
        $ea = $mr + $e * sin($mr);
        for ($i = 0; $i < 8; $i++) {
            $d = ($ea - $e * sin($ea) - $mr) / (1 - $e * cos($ea));
            $ea -= $d;
            if (abs($d) < 1e-9) {
                break;
            }
        }

        // พิกัดในระนาบวงโคจร
        $xv = $a * (cos($ea) - $e);
        $yv = $a * sqrt(1 - $e * $e) * sin($ea);

        // หมุนสู่ ecliptic (ω, Ω=node, i=inc)
        $cosW = cos($w);
        $sinW = sin($w);
        $cosN = cos($node);
        $sinN = sin($node);
        $cosI = cos($inc);
        $sinI = sin($inc);

        $x = ($cosW * $cosN - $sinW * $sinN * $cosI) * $xv + (-$sinW * $cosN - $cosW * $sinN * $cosI) * $yv;
        $y = ($cosW * $sinN + $sinW * $cosN * $cosI) * $xv + (-$sinW * $sinN + $cosW * $cosN * $cosI) * $yv;
        $z = ($sinW * $sinI) * $xv + ($cosW * $sinI) * $yv;

        return [$x, $y, $z];
    }

    /** normalize องศาเข้าช่วง [0,360) */
    private function normalize360(float $deg): float
    {
        $deg = fmod($deg, 360.0);

        return $deg < 0 ? $deg + 360.0 : $deg;
    }
}
