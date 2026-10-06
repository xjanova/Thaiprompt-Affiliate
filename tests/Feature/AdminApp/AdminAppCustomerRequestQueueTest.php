<?php

namespace Tests\Feature\AdminApp;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Services\FacebookWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🙋 (v3) คิว "ลูกค้าขอคุยกับคน" อ่านจาก fortune_takeover_logs
 *
 * ตั้งแต่ 2026-05-17 webhook (LINE/FB/Telegram) แค่เขียน log action=message · reason=customer_request · user_id=null
 * แล้วแจ้งแอดมิน — ไม่เทคโอเวอร์ให้ ⇒ คิวเดิม (ดู admin_takeover_reason) ว่างตลอด
 *
 * ล็อกไว้:
 *   - คำขอใน 24 ชม. ที่ยังไม่มี log ใหม่กว่าที่แปลว่าแอดมินลงมือ (ข้อความแอดมิน / เทคโอเวอร์ / ต่อเวลา / คืนงาน) = ค้าง
 *   - log ระบบ (user_id null) และหมดเวลาอัตโนมัติ ไม่นับเป็นการรับเรื่อง
 *   - ops/summary · takeover/conversations?status=requested · takeover/stats ใช้นิยามเดียวกัน
 *   - แอดมินตอบผ่าน chat/send ของแอป = รับเรื่องแล้ว (บันทึก log แบบเดียวกับแผงเว็บ)
 */
#[Group('admin-app')]
class AdminAppCustomerRequestQueueTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_requests_come_from_the_log_and_leave_once_an_admin_acts(): void
    {
        $admin = $this->actAs($this->makeAdmin());

        // ✅ ค้าง: ขอ 2 รอบ ไม่มีใครตอบ (keyword = ข้อความล่าสุด · requested_at = รอบแรก)
        $twice = $this->reading('61550000001001', 'สมหญิง');
        $this->request($twice->id, now()->subMinutes(30), 'ขอคุยกับแอดมิน');
        $this->request($twice->id, now()->subMinutes(5), 'ขอคุยกับคนจริงค่ะ');

        // ✅ ค้าง: log ระบบ/หมดเวลาอัตโนมัติหลังคำขอ ไม่ใช่คนตอบ
        $systemOnly = $this->reading('61550000001002', 'สมชาย');
        $this->request($systemOnly->id, now()->subMinutes(10), 'คุยกับคน');
        $this->log($systemOnly->id, FortuneTakeoverLog::ACTION_MESSAGE, 'stuck_paid_over_24hr', null, now()->subMinutes(9));
        $this->log($systemOnly->id, FortuneTakeoverLog::ACTION_AUTO_EXPIRE, null, null, now()->subMinutes(8));

        // ❌ รับแล้ว: แอดมินส่งข้อความ / กดเทคโอเวอร์ / แอดมินพิมพ์ใน Page Inbox (auto_reply) / ต่อเวลา / คืนงาน
        foreach ([
            [FortuneTakeoverLog::ACTION_MESSAGE, null, $admin->id],
            [FortuneTakeoverLog::ACTION_TAKEOVER, FortuneReading::TAKEOVER_REASON_MANUAL, $admin->id],
            [FortuneTakeoverLog::ACTION_TAKEOVER, FortuneReading::TAKEOVER_REASON_AUTO_REPLY, null],
            [FortuneTakeoverLog::ACTION_EXTEND, null, $admin->id],
            [FortuneTakeoverLog::ACTION_RESUME, 'command', $admin->id],
        ] as $i => [$action, $reason, $by]) {
            $r = $this->reading('6155000000200'.$i, 'ตอบแล้ว'.$i);
            $this->request($r->id, now()->subMinutes(20), 'คุยกับคน');
            $this->log($r->id, $action, $reason, $by, now()->subMinutes(15));
        }

        // ❌ เก่ากว่า 24 ชม. · บิลถูกลบ (readings/{id}/cancel)
        $stale = $this->reading('61550000003001', 'เก่า');
        $this->request($stale->id, now()->subHours(25), 'คุยกับคน');
        $deleted = $this->reading('61550000003002', 'ลบแล้ว');
        $this->request($deleted->id, now()->subMinutes(3), 'คุยกับคน');
        $deleted->delete();

        // ── ops/summary ──
        $summary = $this->getJson('/api/admin/ops/summary')->assertOk();
        $box = $summary->json('data.queue.customer_requests');
        $this->assertSame(2, $box['count']);
        $this->assertSame(30, $box['oldest_minutes']);
        $this->assertSame([$twice->id, $systemOnly->id], array_column($box['preview'], 'reading_id'), 'รอนานสุดก่อน');
        $this->assertSame('ขอคุยกับคนจริงค่ะ', $box['preview'][0]['keyword']);
        $this->assertSame(2, $box['preview'][0]['request_count']);
        $this->assertSame('สมหญิง', $box['preview'][0]['customer_name']);

        // ── takeover/conversations?status=requested ──
        $list = $this->getJson('/api/admin/takeover/conversations?status=requested')->assertOk();
        $this->assertSame(2, $list->json('data.total'));
        $rows = $list->json('data.data');
        $this->assertSame([$twice->id, $systemOnly->id], array_column($rows, 'reading_id'));
        $this->assertTrue($rows[0]['requested_by_customer']);
        $this->assertSame('ขอคุยกับคนจริงค่ะ', $rows[0]['request_keyword']);
        $this->assertSame(2, $rows[0]['request_count']);
        $this->assertNotNull($rows[0]['requested_at']);
        $this->assertFalse($rows[0]['is_taken_over'], 'โหมดแจ้งแอดมินอย่างเดียว — บอทยังตอบเองอยู่');
        // log ระบบ (user_id null) ไม่ใช่ "ข้อความแอดมินล่าสุด"
        $this->assertNull($rows[1]['last_message']);

        // ค้นหา + กรองช่องทางยังทำงานร่วมกับคิว
        $this->getJson('/api/admin/takeover/conversations?status=requested&search=สมหญิง')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/takeover/conversations?status=requested&platform=line')->assertOk()->assertJsonPath('data.total', 0);

        // ── takeover/stats ──
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 2);
    }

    public function test_replying_from_the_app_takes_the_request_off_the_queue(): void
    {
        $admin = $this->actAs($this->makeAdmin());
        $reading = $this->reading('61550000004001', 'ลูกค้ารอ');
        $this->request($reading->id, now()->subMinutes(4), 'ขอคุยกับแอดมิน');

        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 1);

        $this->mock(FacebookWebhookService::class, fn ($m) => $m->shouldReceive('sendMessage')->andReturn(true));
        $this->postJson('/api/admin/chat/send', ['reading_id' => $reading->id, 'text' => 'สวัสดีค่ะ แอดมินเองค่ะ'])
            ->assertOk()
            ->assertJsonPath('data.delivered', true);

        $this->assertDatabaseHas('fortune_takeover_logs', [
            'fortune_reading_id' => $reading->id, 'action' => FortuneTakeoverLog::ACTION_MESSAGE, 'user_id' => $admin->id,
        ]);
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 0);
        $this->getJson('/api/admin/takeover/conversations?status=requested')->assertOk()->assertJsonPath('data.total', 0);

        // ลูกค้าขอใหม่อีกรอบหลังแอดมินตอบ = กลับเข้าคิว
        $this->request($reading->id, now()->addSecond(), 'ยังอยู่ไหมคะ');
        $this->travel(2)->seconds();
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 1);
    }

    /**
     * (v3 จับผี) คำขอถูกบันทึกกับ "บิลล่าสุดของลูกค้าตอนขอ" — แอดมินตอบจากบิลอื่นของลูกค้าคนเดียวกัน
     * หรือส่งแบบ platform + platform_user_id (ไม่มี reading_id) ก็ต้องปิดคำขอ
     */
    public function test_reply_closes_requests_found_by_customer_identity_not_just_reading_id(): void
    {
        $admin = $this->actAs($this->makeAdmin());
        $this->mock(FacebookWebhookService::class, fn ($m) => $m->shouldReceive('sendMessage')->andReturn(true));

        // คำขออยู่บนบิลเก่า · แอดมินเปิดบิลใหม่ของลูกค้าคนเดียวกันแล้วตอบ
        $older = $this->reading('61550000006101', 'ลูกค้าสองบิล');
        $this->request($older->id, now()->subMinutes(6), 'ขอคุยกับคน');
        $newer = $this->reading('61550000006101', 'ลูกค้าสองบิล');
        $other = $this->reading('61550000006199', 'ลูกค้าอื่น');
        $this->request($other->id, now()->subMinutes(5), 'ขอคุยด้วยค่ะ');
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 2);

        $this->postJson('/api/admin/chat/send', ['reading_id' => $newer->id, 'text' => 'แอดมินมาแล้วค่ะ'])->assertOk();
        $this->assertDatabaseHas('fortune_takeover_logs', ['fortune_reading_id' => $older->id, 'user_id' => $admin->id,
            'action' => FortuneTakeoverLog::ACTION_MESSAGE]);
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 1, 'เหลือแค่ลูกค้าอีกคน');

        // ส่งแบบไม่มี reading_id (platform + platform_user_id) → หาบิลที่มีคำขอค้างจากตัวตนลูกค้า
        $this->postJson('/api/admin/chat/send', ['platform' => 'facebook', 'platform_user_id' => '61550000006199', 'text' => 'สวัสดีค่ะ'])
            ->assertOk();
        $this->assertDatabaseHas('fortune_takeover_logs', ['fortune_reading_id' => $other->id, 'user_id' => $admin->id]);
        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 0);
    }

    public function test_failed_send_does_not_mark_the_request_answered(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->reading('61550000005001', 'ส่งไม่ออก');
        $this->request($reading->id, now()->subMinutes(4), 'ขอคุยกับแอดมิน');

        $this->mock(FacebookWebhookService::class, fn ($m) => $m->shouldReceive('sendMessage')->andReturn(false));
        $this->postJson('/api/admin/chat/send', ['reading_id' => $reading->id, 'text' => 'ทดสอบ'])->assertStatus(502);

        $this->getJson('/api/admin/takeover/stats')->assertOk()->assertJsonPath('data.requested', 1);
    }

    // ────────────────────────────────────────────────────────────

    private function reading(string $psid, string $name): FortuneReading
    {
        return $this->makeReading([
            'platform_user_id' => $psid,
            'facebook_user_id' => $psid,
            'facebook_user_name' => $name,
            'reading_type' => FortuneReading::READING_TYPE_BASIC,
        ]);
    }

    /**
     * log คำขอแบบที่ webhook เขียนจริง (handleCustomerHandoffRequest)
     */
    private function request(int $readingId, $at, string $text): void
    {
        $this->log($readingId, FortuneTakeoverLog::ACTION_MESSAGE, FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST, null, $at,
            '🙋 ลูกค้าขอคุยกับคน: '.$text);
    }

    private function log(int $readingId, string $action, ?string $reason, ?int $userId, $at, ?string $message = null): void
    {
        $log = new FortuneTakeoverLog;
        $log->timestamps = false;
        $log->forceFill([
            'fortune_reading_id' => $readingId, 'user_id' => $userId, 'action' => $action, 'reason' => $reason,
            'platform' => 'facebook', 'message' => $message ?? 'log '.$action, 'created_at' => $at, 'updated_at' => $at,
        ])->save();
    }
}
