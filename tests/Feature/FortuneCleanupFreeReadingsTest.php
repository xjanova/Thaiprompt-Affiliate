<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🧹 (2026-09-27) fortune:cleanup-free — รันคำสั่งจริงกับฐานข้อมูลจริง
 *
 * บั๊ก: ขั้นที่ 1 สั่งล้างคอลัมน์ conversation_data ซึ่ง fortune_readings ไม่มี
 *   → SQLSTATE[42S22] ทุกคืน 03:00 ตั้งแต่ 2026-02-20 คำสั่งล้มกลางทาง ขั้นที่ 2-3 ไม่เคยรัน
 *   (prod ก่อนแก้: ค้างล้างข้อความ 78 · ค้างลบ 214 · ค้างสถานะ 2 — ทั้งหมดเป็นชนิด basic ฟรี)
 */
class FortuneCleanupFreeReadingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * สร้างคำทำนายแล้วถอย created_at ไปตามจำนวนวัน (ผ่าน query builder ไม่ให้ timestamps ทับ)
     */
    private function reading(array $attrs, int $daysAgo): FortuneReading
    {
        $reading = FortuneReading::create(array_merge([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'cleanup_'.uniqid(),
            'reading_type' => 'basic',
            'questions' => json_encode(['ทดสอบ']),
            'ai_provider' => 'test',
            'is_paid' => false,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'platform' => 'test',
        ], $attrs));

        DB::table('fortune_readings')->where('id', $reading->id)->update(['created_at' => now()->subDays($daysAgo)]);

        return $reading;
    }

    public function test_nightly_cleanup_no_longer_crashes_and_runs_all_three_steps(): void
    {
        $clearText = $this->reading([
            'basic_response' => 'คำทำนายฟรีเก่า',
            'conversation_state' => ['cancellation_reason' => 'user_cancelled'],
        ], 40);
        $deleteOld = $this->reading(['basic_response' => 'เก่ามาก'], 120);
        $stuck = $this->reading(['conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE], 10);

        $this->artisan('fortune:cleanup-free')->assertSuccessful();

        // ขั้นที่ 1: ล้างข้อความ แต่เก็บแถวและ conversation_state ไว้ (ด่านนับบิลยกเลิกยังอ่านอยู่)
        $clearText->refresh();
        $this->assertNull($clearText->basic_response);
        $this->assertSame('user_cancelled', $clearText->conversation_state['cancellation_reason'] ?? null);
        $this->assertNull($clearText->deleted_at);

        // ขั้นที่ 2-3: ลบแบบ soft delete (กู้คืนได้)
        $this->assertSoftDeleted('fortune_readings', ['id' => $deleteOld->id]);
        $this->assertSoftDeleted('fortune_readings', ['id' => $stuck->id]);
    }

    public function test_paid_recent_and_non_basic_readings_are_never_touched(): void
    {
        $paid = $this->reading(['basic_response' => 'จ่ายแล้ว', 'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()], 200);
        $recent = $this->reading(['basic_response' => 'เพิ่งดู'], 5);
        $deep = $this->reading(['basic_response' => 'ดวงเชิงลึกฟรี', 'reading_type' => 'deep'], 200);
        $stuckPaid = $this->reading(['conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE, 'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()], 30);

        $this->artisan('fortune:cleanup-free')->assertSuccessful();

        $this->assertSame('จ่ายแล้ว', $paid->fresh()->basic_response);
        $this->assertSame('เพิ่งดู', $recent->fresh()->basic_response);
        $this->assertSame('ดวงเชิงลึกฟรี', $deep->fresh()->basic_response);
        $this->assertNotSoftDeleted('fortune_readings', ['id' => $stuckPaid->id]);
    }

    public function test_dry_run_counts_without_changing_anything(): void
    {
        $old = $this->reading(['basic_response' => 'คำทำนายฟรีเก่า'], 120);

        $this->artisan('fortune:cleanup-free', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('คำทำนายฟรีเก่า', $old->fresh()->basic_response);
        $this->assertNotSoftDeleted('fortune_readings', ['id' => $old->id]);
    }
}
