<?php

namespace Tests\Feature;

use App\Models\FortuneInviteMessage;
use Database\Seeders\FortuneDailyInviteMessageBatch2Seeder;
use Database\Seeders\FortuneDailyInviteMessageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🌙 กฎเนื้อหาของคลังข้อความชวนรับดวงรายวันฟรี (mode=daily, 100 ข้อความ)
 *
 * ข้อความชุดนี้ถูกยิงหาลูกค้าจริงโดยไม่มีคนตรวจรายข้อ — เขียนพลาดข้อเดียว
 * ก็ออกไปหาลูกค้าหลายร้อยคนได้ เทสต์นี้จึงล็อกกติกาที่ "ผิดแล้วเสียลูกค้า":
 *
 *  1. ต้องขอวันเกิด — ไม่งั้นลูกค้าไม่รู้ว่าต้องตอบอะไร บอทก็ทำนายไม่ได้
 *  2. ต้องบอกว่าฟรี — เหตุผลเดียวที่คนยอมตอบ DM คือของฟรี
 *  3. เสียงแม่หมอเป็นผู้หญิงเสมอ ห้าม ครับ/ผม/ดิฉัน/หนู/เรา
 *  4. ช่วงเวลาต้องอยู่ในกรอบ 0-23 และตั้งครบทั้งคู่หรือไม่ตั้งเลย
 *  5. ทุกชั่วโมงต้องมีข้อความให้สุ่มเหลือเสมอ (DM เงียบ = เสียลูกค้า)
 *  6. ห้ามชวนด้วยของที่ระบบไม่ได้ส่งให้ (สีมงคล/เลขนำโชค)
 */
class FortuneDailyInviteMessageContentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ⚠️ ครอบทั้ง "วันเกิด" / "เกิดวันอะไร" / "วันเดือนปีเกิด"
     *    ชุดแรก 2 ข้อใช้คำว่า "วันเดือนปีเกิดเต็ม" ซึ่งก็คือการขอวันเกิดเหมือนกัน
     */
    private const ASKS_BIRTHDAY = '/วันเกิด|เกิดวัน|ปีเกิด/u';

    private const SAYS_FREE = '/ฟรี|ไม่คิดค่า|ไม่มีค่าใช้จ่าย/u';

    /** rule: แม่หมอเป็นผู้หญิงเสมอ */
    private const FORBIDDEN_PRONOUNS = '/ครับ|ผม|ดิฉัน|หนู|เรา/u';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FortuneDailyInviteMessageSeeder::class);
        $this->seed(FortuneDailyInviteMessageBatch2Seeder::class);
    }

    /** @return \Illuminate\Support\Collection<int, FortuneInviteMessage> */
    private function dailyMessages()
    {
        return FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_DAILY)->get();
    }

    public function test_มีข้อความชุดดวงรายวันครบ_100_ข้อความ(): void
    {
        $this->assertCount(100, $this->dailyMessages());
    }

    public function test_รัน_seeder_ซ้ำต้องไม่เพิ่มข้อความซ้ำ(): void
    {
        $this->seed(FortuneDailyInviteMessageSeeder::class);
        $this->seed(FortuneDailyInviteMessageBatch2Seeder::class);

        $this->assertCount(100, $this->dailyMessages());
    }

    public function test_ไม่มีข้อความซ้ำกันในคลัง(): void
    {
        $all = $this->dailyMessages()->pluck('message');

        $this->assertSame(
            $all->count(),
            $all->unique()->count(),
            'มีข้อความซ้ำกันในคลัง — ลูกค้าจะได้ข้อความเดิมบ่อยผิดปกติ'
        );
    }

    public function test_ทุกข้อความต้องขอวันเกิดและบอกว่าฟรี(): void
    {
        foreach ($this->dailyMessages() as $msg) {
            $this->assertMatchesRegularExpression(
                self::ASKS_BIRTHDAY,
                $msg->message,
                "ข้อความ #{$msg->id} ไม่ได้ขอวันเกิด: {$msg->message}"
            );

            $this->assertMatchesRegularExpression(
                self::SAYS_FREE,
                $msg->message,
                "ข้อความ #{$msg->id} ไม่ได้บอกว่าฟรี: {$msg->message}"
            );
        }
    }

    public function test_ทุกข้อความต้องเป็นเสียงแม่หมอผู้หญิง(): void
    {
        foreach ($this->dailyMessages() as $msg) {
            $this->assertDoesNotMatchRegularExpression(
                self::FORBIDDEN_PRONOUNS,
                $msg->message,
                "ข้อความ #{$msg->id} มีสรรพนามที่แม่หมอห้ามใช้: {$msg->message}"
            );
        }
    }

    public function test_ช่วงเวลาที่ตั้งไว้ต้องถูกต้อง(): void
    {
        foreach ($this->dailyMessages() as $msg) {
            // ตั้งครบทั้งคู่ หรือไม่ตั้งเลย — ตั้งข้างเดียวคือหน้าต่างครึ่งใบ (ระบบจะมองว่าไม่ได้ตั้ง)
            $this->assertSame(
                $msg->hour_from === null,
                $msg->hour_to === null,
                "ข้อความ #{$msg->id} ตั้งช่วงเวลามาข้างเดียว"
            );

            foreach (['hour_from', 'hour_to'] as $column) {
                if ($msg->$column !== null) {
                    $this->assertGreaterThanOrEqual(0, $msg->$column, "ข้อความ #{$msg->id} {$column} ต่ำกว่า 0");
                    $this->assertLessThanOrEqual(23, $msg->$column, "ข้อความ #{$msg->id} {$column} เกิน 23");
                }
            }
        }
    }

    /**
     * 🚦 ทุกชั่วโมงต้องมีของให้สุ่มเหลือเสมอ
     *
     * pickActive() มี fallback ไปสุ่มทั้งกองอยู่แล้ว แต่ fallback = ยอมส่งข้อความ
     * ผิดโทนเวลา ซึ่งเป็นสิ่งที่ทั้งฟีเจอร์นี้พยายามเลี่ยง → กองที่ไม่ผูกเวลา
     * ต้องหนาพอที่จะไม่ต้องพึ่ง fallback เลยไม่ว่าชั่วโมงไหน
     */
    public function test_ทุกชั่วโมงต้องมีข้อความให้สุ่มโดยไม่ต้องพึ่ง_fallback(): void
    {
        $anytime = $this->dailyMessages()
            ->where('is_active', true)
            ->whereNull('hour_from')
            ->count();

        $this->assertGreaterThanOrEqual(
            25,
            $anytime,
            'ข้อความที่ส่งได้ทุกเวลาเหลือน้อยเกินไป — บางชั่วโมงจะต้องพึ่ง fallback'
        );
    }

    /**
     * ❌ ห้ามชวนด้วยของที่บทความรายวันไม่ได้ส่งให้
     *
     * ตรวจ `horoscope_daily_predictions` บน prod แล้ว (2026-08-08):
     * `lucky_color_th` ว่างเสมอ (สีมงคลงดถาวร) และ `lucky_number` ว่าง
     * ยกเว้นวันที่ 15/29 → ลูกค้าตอบมาเพราะอยากได้สีเสื้อ/เลข แล้วไม่ได้ = เสียความเชื่อใจ
     *
     * ⚠️ จำกัดเฉพาะชุดที่ 2 โดยตั้งใจ — ชุดแรกมีข้อความของเจ้าของที่ยังชวนเรื่อง
     *    "สีเสื้อกับเลขนำโชค" อยู่ (หมวด daily-curious) รอเจ้าของตัดสินใจว่าจะแก้หรือปิด
     *    ห้ามแก้ข้อความที่เจ้าของเขียนเองโดยพลการ
     */
    public function test_ชุดที่_2_ต้องไม่ชวนด้วยสีมงคลหรือเลขนำโชค(): void
    {
        $batch2 = $this->dailyMessages()
            ->whereIn('category', ['daily-morning', 'daily-evening', 'daily-night', 'daily-gentle', 'daily-detail']);

        $this->assertCount(50, $batch2, 'ชุดที่ 2 ต้องมี 50 ข้อความ');

        foreach ($batch2 as $msg) {
            $this->assertDoesNotMatchRegularExpression(
                '/สีมงคล|สีเสื้อ|สีประจำวัน|เลขนำโชค|เลขมงคล/u',
                $msg->message,
                "ข้อความ #{$msg->id} ชวนด้วยของที่ระบบไม่ได้ส่งให้: {$msg->message}"
            );
        }
    }
}
