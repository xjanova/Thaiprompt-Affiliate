<?php

namespace Tests\Feature;

use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ⏰ ช่วงเวลาส่งของข้อความชวนดูดวง (hour_from / hour_to)
 *
 * ที่มา (prod 2026-08-08): engagement #429488 ส่งตอน 11:57 น. ด้วยข้อความ
 * "🌙 ดึกแล้วแต่แม่หมอยังเปิดตำราอยู่ค่ะ ... เปิดให้ดูฟรีก่อนนอนนะคะ"
 * — pickActive() สุ่มล้วน ไม่รู้จักเวลา
 *
 * กติกาที่ล็อกไว้:
 *  1. ข้อความที่ตั้งช่วงเวลา ต้องไม่ถูกสุ่มนอกช่วงนั้น
 *  2. ช่วงคร่อมเที่ยงคืน (21 ถึง 2) ต้องนับทั้งฝั่งหัวค่ำและฝั่งเช้ามืด
 *  3. NULL = ส่งได้ทุกเวลา — แถวเดิมทุกแถวต้องพฤติกรรมเหมือนเดิม 100%
 *  4. ตั้งมาข้างเดียว = ถือว่าไม่ได้ตั้ง (ห้ามเดาแล้วบล็อกข้อความทิ้ง)
 *  5. ชั่วโมงนี้ไม่เหลือข้อความเลย → ต้องตกไปสุ่มทั้งกอง ห้ามคืน null
 *     (DM เงียบ แย่กว่า DM ที่โทนเวลาเพี้ยน)
 */
class FortuneInviteMessageTimeWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FortuneTellingSetting::clearSettingsCache();
        FortuneTellingSetting::getSettings()->forceFill([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
        ])->save();
        FortuneTellingSetting::clearSettingsCache();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** ตรึงเวลาไว้ที่ชั่วโมงที่ต้องการ (โซนเวลาแอป) */
    private function atHour(int $hour): void
    {
        Carbon::setTestNow(Carbon::now()->startOfDay()->addHours($hour));
    }

    private function make(string $message, ?int $from = null, ?int $to = null): FortuneInviteMessage
    {
        return FortuneInviteMessage::create([
            'message' => $message,
            'category' => 'test',
            'mode' => FortuneInviteMessage::MODE_ALL,
            'hour_from' => $from,
            'hour_to' => $to,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /** สุ่มหลายรอบแล้วรวมผลที่ได้ — กัน inRandomOrder หลอกให้ผ่านโดยบังเอิญ */
    private function pickedMessages(int $times = 12): array
    {
        $seen = [];

        for ($i = 0; $i < $times; $i++) {
            $seen[] = FortuneInviteMessage::pickActive()?->message;
        }

        return array_values(array_unique($seen));
    }

    public function test_ข้อความช่วงเช้าต้องไม่ถูกสุ่มตอนเที่ยง(): void
    {
        $this->make('อรุณสวัสดิ์ค่ะเจ้าชะตา', 5, 9);
        $this->make('ทั่วไปส่งได้ทุกเวลา');

        $this->atHour(12);

        $this->assertSame(['ทั่วไปส่งได้ทุกเวลา'], $this->pickedMessages());
    }

    public function test_ข้อความช่วงเช้าถูกสุ่มได้ในช่วงเช้า(): void
    {
        $this->make('อรุณสวัสดิ์ค่ะเจ้าชะตา', 5, 9);

        // ต้นช่วง / กลางช่วง / ปลายช่วง (ปลายรวมทั้งชั่วโมง → 09:59 ยังอยู่)
        foreach ([5, 7, 9] as $hour) {
            $this->atHour($hour);
            $this->assertSame('อรุณสวัสดิ์ค่ะเจ้าชะตา', FortuneInviteMessage::pickActive()?->message, "ชั่วโมง {$hour} ต้องได้ข้อความเช้า");
        }
    }

    public function test_ช่วงคร่อมเที่ยงคืนนับทั้งหัวค่ำและเช้ามืด(): void
    {
        $this->make('ดึกแล้วแต่แม่หมอยังเปิดตำราอยู่ค่ะ', 21, 2);
        $this->make('ทั่วไปส่งได้ทุกเวลา');

        // อยู่ในช่วง — ต้องมีโอกาสถูกสุ่ม
        foreach ([21, 23, 0, 2] as $hour) {
            $this->atHour($hour);
            $this->assertContains(
                'ดึกแล้วแต่แม่หมอยังเปิดตำราอยู่ค่ะ',
                $this->pickedMessages(),
                "ชั่วโมง {$hour} ต้องอยู่ในช่วงกลางคืน"
            );
        }

        // นอกช่วง — ห้ามหลุดออกมาเด็ดขาด (เคสจริงที่ prod: 11:57 น.)
        foreach ([3, 11, 12, 20] as $hour) {
            $this->atHour($hour);
            $this->assertSame(
                ['ทั่วไปส่งได้ทุกเวลา'],
                $this->pickedMessages(),
                "ชั่วโมง {$hour} ต้องอยู่นอกช่วงกลางคืน"
            );
        }
    }

    public function test_ไม่ตั้งช่วงเวลา_ส่งได้ทุกเวลาเหมือนเดิม(): void
    {
        $this->make('ทั่วไปส่งได้ทุกเวลา');

        foreach ([0, 6, 12, 18, 23] as $hour) {
            $this->atHour($hour);
            $this->assertSame('ทั่วไปส่งได้ทุกเวลา', FortuneInviteMessage::pickActive()?->message, "ชั่วโมง {$hour} ต้องยังส่งได้");
        }
    }

    public function test_ตั้งช่วงเวลามาข้างเดียวถือว่าส่งได้ทุกเวลา(): void
    {
        $this->make('ตั้งแต่ต้นข้างเดียว', 5, null);
        $this->make('ตั้งปลายข้างเดียว', null, 9);

        $this->atHour(23);

        $picked = $this->pickedMessages(20);
        sort($picked);

        $this->assertSame(['ตั้งปลายข้างเดียว', 'ตั้งแต่ต้นข้างเดียว'], $picked);
    }

    public function test_ชั่วโมงนี้ไม่เหลือข้อความเลย_ต้องไม่เงียบ(): void
    {
        // มีแต่ข้อความช่วงเช้า แต่ตอนนี้เป็นเวลาดึก
        $this->make('อรุณสวัสดิ์ค่ะเจ้าชะตา', 5, 9);

        $this->atHour(23);

        $this->assertSame(
            'อรุณสวัสดิ์ค่ะเจ้าชะตา',
            FortuneInviteMessage::pickActive()?->message,
            'ตัวกรองเวลาห้ามทำให้ DM เงียบ — ต้องตกไปสุ่มทั้งกอง'
        );
    }

    public function test_ปิดข้อความอยู่_ตัวกรองเวลาต้องไม่ปลุกกลับมา(): void
    {
        $off = $this->make('ปิดอยู่', 5, 9);
        $off->update(['is_active' => false]);

        $this->atHour(23);

        $this->assertNull(FortuneInviteMessage::pickActive(), 'fallback ต้องไม่ข้าม is_active');
    }

    public function test_เติมช่วงเวลาเริ่มต้นให้ข้อความที่ผูกเวลา_และไม่ทับของเดิม(): void
    {
        $night = $this->make('🌙 ดึกแล้วแต่แม่หมอยังเปิดตำราอยู่ค่ะ พิมพ์วันเกิดมานะคะ');
        $morning = $this->make('อรุณสวัสดิ์ค่ะเจ้าชะตา ☀️ เกิดวันอะไรคะ', 10, 11);
        $plain = $this->make('ทั่วไปส่งได้ทุกเวลา');

        FortuneInviteMessage::applyDefaultTimeWindows();

        // เติมให้ข้อความที่ยังว่าง
        $this->assertSame(21, $night->fresh()->hour_from);
        $this->assertSame(2, $night->fresh()->hour_to);

        // ห้ามทับค่าที่แอดมินตั้งเอง
        $this->assertSame(10, $morning->fresh()->hour_from);
        $this->assertSame(11, $morning->fresh()->hour_to);

        // ข้อความที่ไม่ได้ผูกเวลา ต้องไม่ถูกแตะ
        $this->assertNull($plain->fresh()->hour_from);
        $this->assertNull($plain->fresh()->hour_to);
    }

    public function test_ป้ายช่วงเวลาสำหรับหน้าแอดมิน(): void
    {
        $this->assertNull($this->make('ทุกเวลา')->timeWindowLabel());
        $this->assertSame('05:00–09:59', $this->make('เช้า', 5, 9)->timeWindowLabel());
        $this->assertSame('21:00–02:59 (ข้ามคืน)', $this->make('ดึก', 21, 2)->timeWindowLabel());
    }
}
