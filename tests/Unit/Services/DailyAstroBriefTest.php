<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\DailyAstroBrief;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ทดสอบว่าดวงรายวัน "อิงดาวจริง" ไม่ใช่มโน
 *
 * ต้อง extend Tests\TestCase เพราะ DailyAstroBrief อ่าน config() (thai_astrology_knowledge)
 */
class DailyAstroBriefTest extends TestCase
{
    protected DailyAstroBrief $brief;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brief = new DailyAstroBrief;
    }

    /**
     * 🎯 หัวใจของงานนี้: วันต่างกัน ข้อเท็จจริงต้องต่างกันจริง
     *
     * ของเดิมส่งให้ AI แต่ข้อมูลคงที่ (ดาวประจำวันเกิด/ธาตุ/มิตร-ศัตรู) ซึ่งเหมือนกัน
     * ทุกวันตลอดปี → AI ไม่มีอะไรให้ยึดว่าวันนี้ต่างจากเมื่อวาน เลยแต่งเอง
     *
     * @test
     */
    public function ข้อเท็จจริงต้องเปลี่ยนตามวันจริง(): void
    {
        $jan = $this->brief->build(1, Carbon::create(2026, 1, 15));
        $jul = $this->brief->build(1, Carbon::create(2026, 7, 15));

        $this->assertTrue($jan['ok']);
        $this->assertTrue($jul['ok']);

        // ดาวเจ้าเรือนคนเกิดวันจันทร์ = จันทร์ เหมือนกันทั้งสองวัน (ค่าคงที่ ถูกต้อง)
        $this->assertSame('จันทร์', $jan['lord']['th']);
        $this->assertSame('จันทร์', $jul['lord']['th']);

        // แต่ "ตำแหน่งจริง" ต้องต่างกัน — นี่คือสิ่งที่เดิมไม่มี
        $this->assertNotSame(
            $jan['text'],
            $jul['text'],
            'ข้อเท็จจริงคนละวันต้องไม่เหมือนกัน ไม่งั้นเท่ากับกลับไปมโนเหมือนเดิม'
        );
    }

    /**
     * ต้อง deterministic — วันเดียวกันรันกี่ครั้งก็ได้ผลเท่ากัน (ห้ามมี rand)
     *
     * @test
     */
    public function วันเดียวกันต้องได้ผลเดิมเสมอ(): void
    {
        $date = Carbon::create(2026, 8, 2);

        $a = $this->brief->build(4, $date);
        $b = $this->brief->build(4, $date->copy());

        $this->assertSame($a['text'], $b['text']);
        $this->assertSame($a['score_hint'], $b['score_hint']);
    }

    /**
     * ดาวเจ้าเรือนของทั้ง 7 วันต้องถูกตามตำรา + มีกำลังพระเคราะห์จริงจาก config
     *
     * @test
     */
    public function ดาวเจ้าเรือนและกำลังพระเคราะห์ต้องถูกตามตำรา(): void
    {
        $expect = [
            0 => ['อาทิตย์', 6],
            1 => ['จันทร์', 15],
            2 => ['อังคาร', 8],
            3 => ['พุธ', 17],
            4 => ['พฤหัสบดี', 19],
            5 => ['ศุกร์', 21],
            6 => ['เสาร์', 10],
        ];

        $date = Carbon::create(2026, 8, 2);

        foreach ($expect as $birthDay => [$planet, $power]) {
            $b = $this->brief->build($birthDay, $date);

            $this->assertSame($planet, $b['lord']['th'], "วันเกิด {$birthDay}");
            $this->assertSame($power, $b['lord']['power'], "กำลังพระเคราะห์ของ {$planet}");
            $this->assertNotNull($b['lord']['trait'], "ต้องมีจุดเด่นของ {$planet} ให้ AI ใช้");
        }
    }

    /**
     * ราศีที่รายงานต้องเป็นราศีจริงจาก ephemeris ไม่ใช่ค่าที่แต่งขึ้น
     *
     * @test
     */
    public function ราศีต้องตรงกับที่ephemerisคำนวณจริง(): void
    {
        $date = Carbon::create(2026, 8, 2);
        $positions = (new PlanetEphemeris)->positions($date->copy()->setTime(12, 0));

        $b = $this->brief->build(0, $date);   // อาทิตย์เป็นดาวเจ้าเรือน

        $this->assertSame($positions['Sun']['sign'], $b['lord']['sign']);
        $this->assertContains($b['lord']['sign'], PlanetEphemeris::SIGNS);
        $this->assertSame($positions['Sun']['retro'], $b['lord']['retro']);
    }

    /**
     * บล็อกข้อเท็จจริงต้องมีของครบสำหรับให้ AI ยึด และต้องไม่มีอีโมจิปน
     *
     * @test
     */
    public function บล็อกข้อเท็จจริงต้องครบและไม่มีอีโมจิ(): void
    {
        $b = $this->brief->build(2, Carbon::create(2026, 8, 2));
        $text = $b['text'];

        $this->assertStringContainsString('ดาวเจ้าเรือน', $text);
        $this->assertStringContainsString('กำลังพระเคราะห์', $text);
        $this->assertStringContainsString('ดาวเจ้าการของวันนี้', $text);
        $this->assertStringContainsString('ราศี', $text);

        // คำทำนายลง FB ห้ามมีอีโมจิ — ข้อเท็จจริงที่ป้อนเข้าไปก็ต้องไม่สอนโมเดลผิดทาง
        // (dignity_label ใน config มี ⭐🌟💧 จึงต้องกันไว้ตรงนี้)
        $this->assertSame(
            $text,
            \App\Services\Fortune\FacebookContentPolicy::stripEmoji($text),
            'บล็อกข้อเท็จจริงต้องไม่มีอีโมจิ'
        );
    }

    /**
     * คะแนน fallback ต้องอยู่ในช่วง 1-5 และสะท้อนของจริง (ไม่ใช่ rand)
     *
     * @test
     */
    public function คะแนนต้องอยู่ในช่วงและมาจากข้อเท็จจริง(): void
    {
        foreach (range(0, 6) as $day) {
            $b = $this->brief->build($day, Carbon::create(2026, 8, 2));

            $this->assertGreaterThanOrEqual(1, $b['score_hint']);
            $this->assertLessThanOrEqual(5, $b['score_hint']);
        }
    }

    /**
     * 🕐 ช่วงเวลาของวันต้องคำนวณจากดาวจริง ไม่ใช่แบ่งเวลาลอย ๆ
     *
     * จันทร์เดิน ~0.5 องศา/ชม. → ตำแหน่งแต่ละช่วงต้องต่างกันจริง
     * และ orb ของมุมต้องขยับตาม (นี่คือสิ่งที่ทำให้บอกได้ว่าช่วงไหนแรง)
     *
     * @test
     */
    public function ช่วงเวลาของวันต้องมาจากตำแหน่งดาวจริง(): void
    {
        $b = $this->brief->build(1, Carbon::create(2026, 8, 2));

        $this->assertCount(5, $b['periods'], 'ต้องครบ เช้า/เที่ยง/บ่าย/เย็น/กลางคืน');

        $labels = array_column($b['periods'], 'label');
        $this->assertSame(['เช้า', 'เที่ยง', 'บ่าย', 'เย็น', 'กลางคืน'], $labels);

        // จันทร์ต้องขยับจริงระหว่างช่วง (ถ้าเท่ากันหมด = ไม่ได้คำนวณใหม่รายช่วง)
        $degrees = array_column($b['periods'], 'moon_deg');
        $this->assertSame(
            count($degrees),
            count(array_unique($degrees)),
            'ตำแหน่งจันทร์ต้องต่างกันทุกช่วง ไม่งั้นแปลว่าใช้ค่าเที่ยงวันซ้ำ'
        );
        // เดินไปข้างหน้าเสมอ (จันทร์ไม่พักร)
        //
        // ⚠️ `moon_deg` = องศา *ภายในราศี* (fmod 30) → **ไม่ monotonic**
        //    จันทร์เดิน ~13°/วัน ⇒ ข้ามราศีทุก ~2.3 วัน พอข้ามแล้วค่าจะวนกลับ 0
        //    (เคสจริง 2026-08-02 หลังใส่อายนางศ: 25.4° → 3.0° = ข้ามราศี ไม่ใช่เดินถอยหลัง)
        //    การเทียบ `$degrees[4] > $degrees[0]` ตรง ๆ จึงพังราว 40% ของวันในปฏิทิน
        //    ที่ผ่านมารอดเพราะตำแหน่งแบบสายนะของวันนั้นบังเอิญไม่ข้ามราศีพอดี
        // ⇒ วัดระยะแบบวนรอบ 30° แทน แล้วเช็คว่าอยู่ในอัตราการเดินจริงของจันทร์
        $delta = fmod($degrees[4] - $degrees[0] + 30.0, 30.0);
        $this->assertGreaterThan(0.0, $delta, 'จันทร์ต้องเดินหน้าตลอดวัน');
        $this->assertLessThan(15.0, $delta, 'ระยะที่จันทร์เดินใน 14 ชม. ต้องสมเหตุสมผล (~6-9°)');

        // ต้องระบุช่วงที่แรงที่สุดได้ และ orb ต้องเล็กที่สุดจริง
        $tightestOrbs = array_filter(array_map(
            fn ($p) => $p['tightest']['orb'] ?? null,
            $b['periods']
        ));
        $this->assertNotEmpty($tightestOrbs);
        $this->assertStringContainsString('ช่วงที่ดาวเจ้าเรือนออกฤทธิ์แรงที่สุด', $b['text']);
        $this->assertStringContainsString('ช่วงเวลาของวัน', $b['text']);

        // แนวโน้มต้องเป็นค่าที่รู้จักเท่านั้น
        foreach ($b['periods'] as $p) {
            foreach ($p['aspects'] as $a) {
                $this->assertContains($a['trend'], ['กำลังเข้า', 'กำลังคลาย', 'คงที่']);
            }
        }
    }

    /**
     * 🧹 ตัดหางน้ำท้ายคำทำนาย — เคสจริงจากการรันบน prod รอบแรก
     *
     * โมเดลต่อท้ายด้านสุขภาพด้วยข้อคิดกลวง + ประโยคขายของ ซึ่งหลุดเข้าฟิลด์
     * เพราะ regex ของด้านสุดท้ายกวาดถึงจบสตริง
     *
     * @test
     */
    public function ตัดหางน้ำท้ายคำทำนายแต่ไม่แตะเนื้อจริง(): void
    {
        $service = (new \ReflectionClass(HoroscopeDailyService::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod($service, 'stripTrailingFiller');
        $m->setAccessible(true);

        // ✅ เคสจริงที่หลุดมา — ต้องเหลือแต่เนื้อ
        $real = "จันทร์จตุโกณอังคารทำให้ร่างกายตึง เครียดง่าย ปวดหัว\n\n"
            ."ดวงเป็นเครื่องชี้ทาง แต่การคุมอารมณ์ของเจ้าชะตาจะเปลี่ยนผลได้จริง\n\n"
            .'ถ้าต้องการ หมอจันทราทำนายต่อแบบเจาะลึกเรื่องงานได้อีกนะ';

        $this->assertSame(
            'จันทร์จตุโกณอังคารทำให้ร่างกายตึง เครียดง่าย ปวดหัว',
            $m->invoke($service, $real)
        );

        // ❌ ห้ามตัดเนื้อคำทำนายจริงที่มีหลายย่อหน้า
        $keep = "จันทร์ตรีโกณพุธส่งผลชัด งานสื่อสารเดินดี\n\nแต่จตุโกณอังคารทำให้แรงกดดันสูง อย่าปะทะ";
        $this->assertSame($keep, $m->invoke($service, $keep));

        // ✅ [จบ] เป็นตัวปิด — ทุกอย่างหลังจากนั้นทิ้ง
        $this->assertSame(
            'เนื้อคำทำนาย',
            $m->invoke($service, "เนื้อคำทำนาย\n\n[จบ]\n\nขายของต่อท้าย")
        );

        // ⚠️ มีย่อหน้าเดียวที่เข้าข่าย filler ต้องไม่ลบจนว่าง (ยอมให้เหลือดีกว่าไม่มีอะไรเลย)
        $this->assertNotSame('', $m->invoke($service, 'ขอให้โชคดี'));
    }

    /**
     * 🔗 ลิงก์ท้ายดวงฟรี = โพสดวงรายวันบนเพจ (ไม่ใช่หน้าเว็บเรา)
     *
     * เจ้าของสั่ง: "ถ้าอยากดูของวันอื่น ให้อ่านบนเพจ แล้วแนบลิงก์คำทำนายรายวัน
     * ของเพจที่โพสไป" — โพสเป็นแบบ combined มีครบ 7 วันเกิดในโพสเดียว
     *
     * ⚠️ เทสต์นี้กันเคส "ยังไม่ได้โพส" — ต้องไม่แนบลิงก์เปล่า/ลิงก์เสียให้ลูกค้า
     *
     * @test
     */
    public function ยังไม่มีโพสของวันนี้ต้องไม่แนบลิงก์(): void
    {
        $greeting = app(\App\Services\Fortune\FortuneGreetingService::class);
        $m = new ReflectionMethod($greeting, 'buildReadMoreLine');
        $m->setAccessible(true);

        // สภาพแวดล้อมเทสต์ไม่มีโพสของวันนี้ → ต้องคืนค่าว่าง ไม่ throw
        $this->assertSame('', $m->invoke($greeting));

        // หน้าเว็บดวงรายวันยังต้องแสดงช่วงเวลาของวัน (job สร้างไว้แล้ว เว็บต้องไม่ขาด)
        $blade = file_get_contents(resource_path('views/frontend/horoscope/daily/birth-day-detail.blade.php'));
        $this->assertStringContainsString('time_prediction_th', $blade);
    }

    /**
     * 📋 กล่องดวงฟรีต้องส่งครบทุกด้าน ไม่ใช่แค่ภาพรวม
     *
     * เจ้าของทัก: "ในบทความมีการทำนายครบทุกด้าน แต่ทำไมบอทส่งคำทำนายฟรีให้สั้น ๆ เอง"
     * job 6 โมงสร้างครบ 5 ด้านเก็บใน DB — จ่ายค่า AI ครบแต่เคยให้ลูกค้าเห็นแค่ 1 ใน 5
     *
     * @test
     */
    public function กล่องดวงฟรีต้องมีครบทุกด้าน(): void
    {
        $greeting = app(\App\Services\Fortune\FortuneGreetingService::class);
        $m = new ReflectionMethod($greeting, 'buildSectionsBlock');
        $m->setAccessible(true);

        $prediction = new \App\Models\HoroscopeDailyPrediction([
            'love_prediction_th' => 'เนื้อความรัก',
            'career_prediction_th' => 'เนื้อการงาน',
            'finance_prediction_th' => 'เนื้อการเงิน',
            'health_prediction_th' => 'เนื้อสุขภาพ',
        ]);

        $out = $m->invoke($greeting, $prediction);

        foreach (['ความรัก', 'การงาน', 'การเงิน', 'สุขภาพ'] as $title) {
            $this->assertStringContainsString($title, $out, "ต้องมีหัวข้อ {$title}");
        }
        foreach (['เนื้อความรัก', 'เนื้อการงาน', 'เนื้อการเงิน', 'เนื้อสุขภาพ'] as $body) {
            $this->assertStringContainsString($body, $out);
        }

        // ด้านที่ไม่มีข้อมูล ต้องไม่โผล่หัวข้อเปล่า (ดวงเก่าที่ parse ไม่ครบ)
        $partial = new \App\Models\HoroscopeDailyPrediction(['love_prediction_th' => 'มีแค่รัก']);
        $partialOut = $m->invoke($greeting, $partial);

        $this->assertStringContainsString('ความรัก', $partialOut);
        $this->assertStringNotContainsString('การงาน', $partialOut);
        $this->assertStringNotContainsString('สุขภาพ', $partialOut);
    }

    /**
     * 🔢 เลขนำโชค — ให้เฉพาะวันที่ 15 และ 29 เท่านั้น (เจ้าของสั่ง)
     *
     * @test
     */
    public function เลขนำโชคให้เฉพาะวันที่สิบห้าและยี่สิบเก้า(): void
    {
        $this->assertSame([15, 29], HoroscopeDailyService::LUCKY_NUMBER_DAYS);

        // ⚠️ ห้าม app(HoroscopeDailyService::class) — constructor ดึง FortuneAIService
        //    ที่ไปอ่าน settings จาก DB ทำให้ unit test พังเมื่อไม่มี MySQL
        //    เมธอดที่ทดสอบเป็น pure function จึงไม่ต้องการ constructor
        $service = (new \ReflectionClass(HoroscopeDailyService::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod($service, 'luckyNumberForDate');
        $m->setAccessible(true);

        // ✅ วันแจก
        foreach ([15, 29] as $day) {
            $date = Carbon::create(2026, 8, $day);
            $number = $m->invoke($service, 1, $date, $this->brief->build(1, $date));

            $this->assertNotNull($number, "วันที่ {$day} ต้องมีเลขนำโชค");
            $this->assertMatchesRegularExpression('/^\d{2}(, \d{2})*$/', $number);

            // deterministic — ห้ามมี rand
            $again = $m->invoke($service, 1, $date, $this->brief->build(1, $date));
            $this->assertSame($number, $again, 'เลขนำโชควันเดิมต้องเท่าเดิม');
        }

        // ❌ วันอื่นต้องไม่มีเลย
        foreach ([1, 14, 16, 28, 30, 31] as $day) {
            $date = Carbon::create(2026, 8, $day);
            $this->assertNull(
                $m->invoke($service, 1, $date, $this->brief->build(1, $date)),
                "วันที่ {$day} ต้องไม่มีเลขนำโชค"
            );
        }
    }
}
