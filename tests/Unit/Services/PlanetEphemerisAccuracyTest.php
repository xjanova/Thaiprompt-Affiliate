<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\AstroAspects;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ThaiAstrologyService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🔭 ความแม่นของ PlanetEphemeris เทียบแหล่งอ้างอิงภายนอก (2026-09-11)
 *
 * ข้อบกพร่อง 2 จุดที่เจอใน GenLotto (โปรเจกต์พี่น้องที่พอร์ตคลาสนี้ไป) แล้วยกกลับมาแก้ที่นี่:
 *   1. ดาวเคราะห์ พุธ–มฤตยู ไม่ได้บวกค่าส่ายของแกนโลก (precession) จาก J2000 มาถึงวันนั้น
 *      ⇒ ช้ากว่าฟ้าจริง ~0.37° ในปี 2569 · ~0.49° เร็วไปในปี 2508
 *   2. เกตุเป็นแบบอินเดีย/สากล (ราหู + 180°) ไม่ใช่เกตุไทยตามคัมภีร์สุริยยาตร์
 *
 * ค่าอ้างอิง (ไม่ได้มาจากโค้ดเราเอง — สคริปต์ตรวจตัวเองก็โกหกได้):
 *   - JPL Horizons: observer ecliptic longitude of date (quantity 31) ดึงเมื่อ 2026-09-11
 *   - ปฏิทินโหรสุริยยาตร์ myhora.com: เวลาเกตุยกราศี 19 ครั้ง ปี 2567–2569
 *
 * ใช้ PHPUnit TestCase ตรง ๆ — คลาสที่ทดสอบไม่แตะ config/DB ⇒ รันได้ทุกเครื่อง ไม่ต้อง boot แอป
 */
class PlanetEphemerisAccuracyTest extends TestCase
{
    /** ดาวที่คำนวณจากธาตุวงโคจร Keplerian (ตัวที่ต้องบวก precession) */
    private const KEPLERIAN = ['Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus'];

    /**
     * ลองจิจูดสุริยวิถีของวันนั้นจาก JPL Horizons (องศา) · คีย์ = เวลาไทย (UT+7)
     *
     * @return array<string, array{0:string, 1:array<string,float>}>
     */
    public static function jplHorizons(): array
    {
        return [
            '2508' => ['1965-03-10 12:00', ['Sun' => 349.4173, 'Moon' => 72.4903, 'Mercury' => 2.0735, 'Venus' => 341.0750, 'Mars' => 168.4576, 'Jupiter' => 51.4386, 'Saturn' => 339.0272, 'Uranus' => 162.5142]],
            '2518' => ['1975-08-20 12:00', ['Sun' => 146.5744, 'Moon' => 308.5856, 'Mercury' => 163.9077, 'Venus' => 157.9409, 'Mars' => 63.2589, 'Jupiter' => 24.6556, 'Saturn' => 116.9905, 'Uranus' => 209.1863]],
            '2528' => ['1985-11-05 12:00', ['Sun' => 222.7222, 'Moon' => 125.2542, 'Mercury' => 245.4735, 'Venus' => 204.4699, 'Mars' => 185.3713, 'Jupiter' => 308.8527, 'Saturn' => 238.6023, 'Uranus' => 256.1436]],
            '2533' => ['1990-05-15 08:30', ['Sun' => 53.9742, 'Moon' => 291.7782, 'Mercury' => 38.0723, 'Venus' => 12.3194, 'Mars' => 348.0089, 'Jupiter' => 99.4591, 'Saturn' => 295.2566, 'Uranus' => 279.1946]],
            '2538' => ['1995-02-14 12:00', ['Sun' => 325.0303, 'Moon' => 129.3176, 'Mercury' => 305.8666, 'Venus' => 280.6069, 'Mars' => 142.0663, 'Jupiter' => 252.2228, 'Saturn' => 342.5974, 'Uranus' => 298.0305]],
            '2543' => ['2000-06-21 12:00', ['Sun' => 90.1274, 'Moon' => 316.5735, 'Mercury' => 109.7861, 'Venus' => 92.8028, 'Mars' => 93.1403, 'Jupiter' => 58.0546, 'Saturn' => 55.5640, 'Uranus' => 320.5398]],
            '2548' => ['2005-10-01 12:00', ['Sun' => 188.1191, 'Moon' => 163.1020, 'Mercury' => 198.0768, 'Venus' => 232.3514, 'Mars' => 53.3685, 'Jupiter' => 204.6145, 'Saturn' => 128.9197, 'Uranus' => 337.6559]],
            '2553' => ['2010-03-01 15:30', ['Sun' => 340.6448, 'Moon' => 169.9897, 'Mercury' => 329.6450, 'Venus' => 352.3142, 'Mars' => 120.8487, 'Jupiter' => 339.9537, 'Saturn' => 182.8516, 'Uranus' => 355.6685]],
            '2563' => ['2020-04-01 15:30', ['Sun' => 12.0782, 'Moon' => 101.1462, 'Mercury' => 345.7441, 'Venus' => 57.8427, 'Mars' => 301.0652, 'Jupiter' => 294.4406, 'Saturn' => 300.6964, 'Uranus' => 35.1826]],
            '2569' => ['2026-09-16 15:30', ['Sun' => 173.5060, 'Moon' => 235.8503, 'Mercury' => 189.2996, 'Venus' => 213.5905, 'Mars' => 112.9511, 'Jupiter' => 136.8319, 'Saturn' => 12.6879, 'Uranus' => 65.6837]],
        ];
    }

    /**
     * 🪐 ทุกดาวต้องตรงกับ JPL Horizons — เพดาน 0.2° คือขีดจำกัดของธาตุวงโคจร JPL แบบประมาณ
     *    (พฤหัส/เสาร์ไม่รวมแรงรบกวนกันเอง · วัดได้จริงสูงสุด 0.14° เสาร์ปี 2553)
     *    ก่อนแก้ precession คลาด 0.2–0.6° ในปีส่วนใหญ่ ⇒ เทสต์นี้ตก 7 จาก 10 ปี
     */
    #[Test]
    #[DataProvider('jplHorizons')]
    public function every_planet_matches_jpl_horizons(string $thaiTime, array $jpl): void
    {
        $positions = (new PlanetEphemeris)->positions(Carbon::parse($thaiTime));

        foreach ($jpl as $planet => $expected) {
            $limit = match ($planet) {
                'Sun' => 0.02,
                'Moon' => 0.05,
                default => 0.2,
            };
            $err = $this->angDiff($positions[$planet]['lon_tropical'], $expected);

            $this->assertLessThanOrEqual(
                $limit,
                abs($err),
                sprintf('%s @ %s คลาดจาก JPL %+.3f° (เพดาน %.2f°)', $planet, $thaiTime, $err, $limit)
            );
        }
    }

    /**
     * 🎯 ปีปัจจุบัน (ช่วงที่ดาวจรถูกใช้ทำนายทุกวัน) ต้องแม่นกว่านั้นอีก — GenLotto วัดได้ ≤0.08°
     *    ⇒ ถ้าใครลบ precession ออก เทสต์นี้ตกทุกดวงทันที ไม่ใช่แค่บางดวง
     */
    #[Test]
    public function current_year_transits_are_within_a_tenth_of_a_degree(): void
    {
        [$thaiTime, $jpl] = self::jplHorizons()['2569'];
        $positions = (new PlanetEphemeris)->positions(Carbon::parse($thaiTime));

        foreach (self::KEPLERIAN as $planet) {
            $err = $this->angDiff($positions[$planet]['lon_tropical'], $jpl[$planet]);
            $this->assertLessThanOrEqual(0.1, abs($err), sprintf('%s 16 ก.ย. 2569 คลาด %+.3f°', $planet, $err));
        }
    }

    /**
     * 🚪 วันที่ดาวเพิ่งข้ามขอบราศีปี 2569 — ราศีต้องตรงกับ JPL (หักอายนางศ Lahiri) ไม่ใช่กรอบ J2000
     *
     * โค้ดเดิม (ไม่มี precession) รายงานทั้ง 4 เคสนี้ช้าไป 1 ราศี เช่น พฤหัสบดีย้ายเข้าสิงห์
     * ช่วงต้น พ.ย. 2569 แต่โค้ดเดิมยังบอก "กรกฎ 29.9°" = ประกาศดาวย้ายราศีบนเพจผิดวัน
     * (เลือกเฉพาะเคสที่ JPL ห่างขอบ ≥0.19° — เคส 31 ต.ค. ห่างขอบ 0.001° เป็นเหรียญหัวก้อย จึงไม่ใช้)
     */
    #[Test]
    public function boundary_days_follow_jpl_not_the_j2000_frame(): void
    {
        $cases = [
            // [เวลาไทย, ดาว, ราศีตาม JPL−Lahiri, องศาตาม JPL, ราศีที่โค้ดเดิมรายงาน]
            ['2026-11-02 12:00', 'Jupiter', 'สิงห์', 0.241, 'กรกฎ'],
            ['2026-11-05 12:00', 'Venus', 'ตุลย์', 0.215, 'กันย์'],
            ['2026-11-13 12:00', 'Mars', 'สิงห์', 0.278, 'กรกฎ'],
            ['2026-12-22 12:00', 'Mercury', 'ธนู', 0.192, 'พิจิก'],
        ];

        $eph = new PlanetEphemeris;
        foreach ($cases as [$at, $planet, $sign, $deg, $oldSign]) {
            $p = $eph->positions(Carbon::parse($at))[$planet];

            $this->assertSame($sign, $p['sign'], "{$planet} @ {$at} ต้องอยู่ราศี{$sign} (โค้ดเดิมบอก{$oldSign})");
            $this->assertEqualsWithDelta($deg, fmod($p['lon'], 30.0), 0.1, "{$planet} @ {$at} องศาในราศี");
        }
    }

    /**
     * ค่าส่ายของแกนโลก p = (5029.0966·T + 1.11113·T²)″ — ศูนย์ที่ J2000 และโตตามเวลา
     */
    #[Test]
    public function precession_is_zero_at_j2000_and_grows_about_50_arcseconds_a_year(): void
    {
        $eph = new PlanetEphemeris;

        $this->assertEqualsWithDelta(0.0, $eph->precession(2451545.0), 1e-12);
        // 16 ก.ย. 2569 15:30 น. ไทย = JD 2461299.8541667 (UT)
        $this->assertEqualsWithDelta(0.373116, $eph->precession(2461299.8541667), 1e-5);
        // ก่อน J2000 ต้องติดลบ (ดาวในกรอบ J2000 "เร็วไป" สำหรับคนเกิดยุคก่อน)
        $this->assertLessThan(-0.48, $eph->precession(2438829.708333));
    }

    /**
     * ปฏิทินโหรสุริยยาตร์ (myhora.com) — เวลาที่เกตุยกเข้าราศี (เดินถอยหลัง)
     *
     * สะกดตามคลาสนี้ (มกร→มังกร · มิถุน→เมถุน · ตุล→ตุลย์)
     *
     * 🔧 (2026-09-13) เวลาในตารางคือ **ตามที่ปฏิทินพิมพ์** = เวลาท้องถิ่นกรุงเทพฯ (UTC+06:42)
     *    ไม่ใช่เวลาไทย (UTC+7) — เดิมเราอ่านตรง ๆ จุดอ้างอิงเกตุเลยเร็วไป 18 นาทีทั้งระบบ
     *    ⇒ แปลงเป็นเวลาไทยด้วย +18 นาทีตรงนี้ (ได้นาที :04 ตรงกับหน้ารายการยกราศีแบบลาหิรีของ myhora ที่พิมพ์เวลาไทย)
     *    เก็บตัวเลขดิบตามแหล่งไว้ ไม่แปลงมือ — คนตรวจทีหลังจะเทียบกับหน้าปฏิทินได้ตรง ๆ
     *
     * @return array<string, array{0:string, 1:string}>
     */
    public static function myhoraKetuIngresses(): array
    {
        $rows = [
            ['2024-02-26 00:46', 'มีน'], ['2024-04-22 14:46', 'กุมภ์'], ['2024-06-18 04:46', 'มังกร'],
            ['2024-08-13 18:46', 'ธนู'], ['2024-10-09 08:46', 'พิจิก'], ['2024-12-04 22:46', 'ตุลย์'],
            ['2025-01-30 12:46', 'กันย์'], ['2025-03-28 02:46', 'สิงห์'], ['2025-05-23 16:46', 'กรกฎ'],
            ['2025-07-19 06:46', 'เมถุน'], ['2025-09-13 20:46', 'พฤษภ'], ['2025-11-09 10:46', 'เมษ'],
            ['2026-01-05 00:46', 'มีน'], ['2026-03-02 14:46', 'กุมภ์'], ['2026-04-28 04:46', 'มังกร'],
            ['2026-06-23 18:46', 'ธนู'], ['2026-08-19 08:46', 'พิจิก'], ['2026-10-14 22:46', 'ตุลย์'],
            ['2026-12-10 12:46', 'กันย์'],
        ];

        $out = [];
        foreach ($rows as [$printedBangkokLocal, $sign]) {
            // เวลาท้องถิ่นกรุงเทพฯ (UTC+06:42) → เวลาไทย (UTC+7) = +18 นาที
            $thai = Carbon::parse($printedBangkokLocal)->addMinutes(18)->format('Y-m-d H:i');
            $out["{$thai} เข้า{$sign}"] = [$thai, $sign];
        }

        return $out;
    }

    /**
     * ☋ เกตุไทยต้องยกราศีตรงนาทีเดียวกับปฏิทินสุริยยาตร์ — 2 นาทีก่อนยังอยู่ราศีเดิม
     *    2 นาทีหลังอยู่ราศีใหม่ (เกตุเดิน 0.53°/วัน ⇒ 2 นาที = 0.0007° แยกขอบราศีได้ชัด)
     */
    #[Test]
    #[DataProvider('myhoraKetuIngresses')]
    public function thai_ketu_changes_sign_at_the_suriyayat_minute(string $thaiTime, string $enters): void
    {
        $eph = new PlanetEphemeris;
        $at = Carbon::parse($thaiTime);
        $new = array_search($enters, PlanetEphemeris::SIGNS, true);
        $this->assertIsInt($new);
        // เดินถอยหลัง ⇒ ราศีก่อนหน้าคือราศี "ถัดไป" ในลำดับเมษ→มีน
        $previous = PlanetEphemeris::SIGNS[($new + 1) % 12];

        $before = $eph->positions($at->copy()->subMinutes(2))['Ketu'];
        $after = $eph->positions($at->copy()->addMinutes(2))['Ketu'];

        $this->assertSame($previous, $before['sign'], "2 นาทีก่อน {$thaiTime} เกตุต้องยังอยู่ราศี{$previous}");
        $this->assertSame($enters, $after['sign'], "2 นาทีหลัง {$thaiTime} เกตุต้องอยู่ราศี{$enters}");
    }

    /**
     * 16 ก.ย. 2569: เกตุไทยอยู่พิจิก ~15° — โค้ดเดิมวางไว้สิงห์ตรงข้ามราหู (ผิดตำราไทย)
     */
    #[Test]
    public function thai_ketu_is_not_the_point_opposite_rahu(): void
    {
        $p = (new PlanetEphemeris)->positions(Carbon::create(2026, 9, 16, 15, 30, 0));

        $this->assertSame('พิจิก', $p['Ketu']['sign']);
        $this->assertEqualsWithDelta(15.0, fmod($p['Ketu']['lon'], 30.0), 0.1);
        $this->assertSame('กุมภ์', $p['Rahu']['sign'], 'ราหูไม่ได้ถูกแตะในรอบนี้');

        $sep = AstroAspects::separation($p['Rahu']['lon'], $p['Ketu']['lon']);
        $this->assertGreaterThan(30.0, abs($sep - 180.0), 'เกตุไทยไม่ได้อยู่ตรงข้ามราหู');
    }

    /**
     * เกตุไทยถอยหลังสม่ำเสมอ ราศีละ 56 วัน 14 ชั่วโมง = รอบละ 679 วันพอดี
     */
    #[Test]
    public function thai_ketu_moves_backward_uniformly_on_a_679_day_cycle(): void
    {
        $eph = new PlanetEphemeris;
        $t0 = Carbon::create(2026, 9, 16, 15, 30, 0);

        $lon0 = $eph->positions($t0)['Ketu']['lon'];
        $lon1 = $eph->positions($t0->copy()->addDay())['Ketu']['lon'];
        $this->assertEqualsWithDelta(-360.0 / 679.0, $this->angDiff($lon1, $lon0), 1e-6, 'ถอยหลัง 0.5302°/วัน');

        $lonCycle = $eph->positions($t0->copy()->addDays(679))['Ketu']['lon'];
        $this->assertEqualsWithDelta(0.0, $this->angDiff($lonCycle, $lon0), 1e-6, 'ครบ 679 วันต้องกลับที่เดิม');

        $lonSign = $eph->positions($t0->copy()->addDays(56)->addHours(14))['Ketu']['lon'];
        $this->assertEqualsWithDelta(-30.0, $this->angDiff($lonSign, $lon0), 1e-6, '56 วัน 14 ชม. = 1 ราศี');

        $this->assertEqualsWithDelta(
            $lon0,
            $eph->ketuThaiSidereal($eph->julianDay($t0) - 7.0 / 24.0),
            1e-9,
            'positions() ต้องคืนเกตุไทยตรง ๆ ไม่ถูกอายนางศบิด'
        );
    }

    /**
     * ราหู-เกตุ ไม่ได้ตรงข้ามกันตายตัวแล้ว ⇒ ต้องนับมุมเหมือนดาวคู่อื่น
     *    (เดิม IGNORED_PAIRS ตัดคู่นี้ทิ้งเพราะ "เล็ง 180° ทุกวัน" ซึ่งเป็นจริงเฉพาะเกตุอินเดีย)
     */
    #[Test]
    public function rahu_ketu_pair_is_counted_like_any_other_pair(): void
    {
        foreach (AstroAspects::IGNORED_PAIRS as [$a, $b]) {
            $pair = [$a, $b];
            sort($pair);
            $this->assertNotSame(['Ketu', 'Rahu'], $pair, 'ห้ามตัดราหู-เกตุทิ้งอีก');
        }

        $aspects = AstroAspects::withinChart([
            'Rahu' => ['lon' => 100.0, 'th' => 'ราหู'],
            'Ketu' => ['lon' => 221.0, 'th' => 'เกตุ'],
        ]);
        $this->assertCount(1, $aspects);
        $this->assertSame('ตรีโกณ', $aspects[0]['name']);

        // ตลอด 1 รอบเกตุ ระยะราหู-เกตุต้องแกว่งจริง ไม่ใช่ 180° ค้าง
        $eph = new PlanetEphemeris;
        $seps = [];
        for ($d = 0; $d < 679; $d += 7) {
            $p = $eph->positions(Carbon::create(2026, 1, 1, 12, 0, 0)->addDays($d));
            $seps[] = AstroAspects::separation($p['Rahu']['lon'], $p['Ketu']['lon']);
        }
        $this->assertLessThan(20.0, min($seps), 'ต้องมีช่วงที่ราหูกับเกตุเข้าใกล้กัน');
        $this->assertGreaterThan(160.0, max($seps), 'และมีช่วงที่ห่างเกือบตรงข้าม');
    }

    /**
     * หน้า "เปรียบเทียบปฏิทินโหราศาสตร์ สมผุสดาว ลัคนา" ของ myhora.com — ละเอียดระดับฟิลิปดา (″)
     * 12 ดวง ปี 2493–2593 · ทั่วประเทศ (กรุงเทพฯ เชียงใหม่ เชียงราย ภูเก็ต หาดใหญ่ อุบลฯ ขอนแก่น โคราช)
     * ดึงเมื่อ 2026-09-12 ตอน GenLotto ตรวจ (ชุดเดียวกับ GenLotto tests/verify_myhora.mjs)
     *
     * คอลัมน์: [เวลาไทย, ละติจูด, ลองจิจูด, ลัคนา″ (วิธีเวลานักษัตร), ราหู″ (ลาหิรี · ราหูเฉลี่ย)]
     *
     * @return array<string, array{0:string, 1:float, 2:float, 3:int, 4:int}>
     */
    public static function myhoraCharts(): array
    {
        $rows = [
            ['2026-09-12 07:00', 13.752555, 100.494066, 566125, 1096160],
            ['1990-05-15 08:30', 18.7883, 98.9853, 246796, 1035491],
            ['2026-09-16 15:30', 13.8612, 100.512, 1014872, 1095329],
            ['2000-06-21 04:00', 7.8804, 98.3923, 122418, 331581],
            ['2015-12-01 22:10', 19.9105, 99.8406, 386813, 551296],
            ['1950-01-01 12:00', 13.752555, 100.494066, 1237955, 1256196],
            ['1975-11-07 18:30', 15.2287, 104.8564, 136171, 751072],
            ['2010-03-01 00:05', 7.0086, 100.4747, 793432, 952282],
            ['2024-02-29 23:59', 13.752555, 100.494066, 781073, 1272679],
            ['2035-06-15 06:30', 16.4419, 102.836, 255310, 486078],
            ['2050-12-31 12:00', 13.752555, 100.494066, 1230881, 698836],
            ['1985-04-05 05:00', 14.9799, 102.0978, 1189820, 95498],
        ];

        $out = [];
        foreach ($rows as $row) {
            $out["{$row[0]} @ {$row[1]},{$row[2]}"] = $row;
        }

        return $out;
    }

    /**
     * ⬆️ ลัคนาต้องตรง myhora ไม่เกิน 10″ (GenLotto สูตรเดียวกันวัดได้ ≤5″)
     *
     * 🔧 (2026-09-13) ก่อนแก้อายนางศเป็นแบบเฉลี่ย ลัคนาเร็วไปราว 15″ ทุกดวง ⇒ เทสต์นี้ตก
     *    ใครเปลี่ยนอายนางศกลับเป็นค่าเดิม (23.85292472) เทสต์นี้จะจับได้ทันที
     *    ⚠️ 10″ ≈ เวลาเกิดคลาดไม่ถึง 1 วินาที — เทียบวิธีเวลานักษัตรเท่านั้น ไม่ใช่อันโตนาที (ต่างได้ถึง ~5°)
     */
    #[Test]
    #[DataProvider('myhoraCharts')]
    public function lagna_matches_myhora_within_ten_arcseconds(string $thaiTime, float $lat, float $lon, int $lagnaArcsec, int $rahuArcsec): void
    {
        $ours = (new ThaiAstrologyService)->siderealLagnaLongitude(Carbon::parse($thaiTime), $lat, $lon);
        $this->assertNotNull($ours);

        $errArcsec = $this->angDiff($ours, $lagnaArcsec / 3600.0) * 3600.0;
        $this->assertLessThanOrEqual(10.0, abs($errArcsec), sprintf('ลัคนา @ %s คลาดจาก myhora %+.1f″', $thaiTime, $errArcsec));
    }

    /**
     * ☊ ราหู (ราหูเฉลี่ย สูตร Meeus เดียวกับ myhora) ต้องตรง ≤3″ — ตัวแยกค่าอายนางศออกมาดูได้ชัดที่สุด
     *    เพราะสูตรราหูเหมือนกันทุกตัวอักษร ส่วนต่างที่เหลือ = อายนางศล้วน ๆ (ค่าเดิมคลาดคงที่ +15″)
     */
    #[Test]
    #[DataProvider('myhoraCharts')]
    public function rahu_matches_myhora_lahiri_within_three_arcseconds(string $thaiTime, float $lat, float $lon, int $lagnaArcsec, int $rahuArcsec): void
    {
        $rahu = (new PlanetEphemeris)->positions(Carbon::parse($thaiTime))['Rahu']['lon'];

        $errArcsec = $this->angDiff($rahu, $rahuArcsec / 3600.0) * 3600.0;
        $this->assertLessThanOrEqual(3.0, abs($errArcsec), sprintf('ราหู @ %s คลาดจาก myhora %+.1f″', $thaiTime, $errArcsec));
    }

    /** ผลต่างมุม a−b → ช่วง [-180, 180) */
    private function angDiff(float $a, float $b): float
    {
        return fmod($a - $b + 540.0, 360.0) - 180.0;
    }
}
