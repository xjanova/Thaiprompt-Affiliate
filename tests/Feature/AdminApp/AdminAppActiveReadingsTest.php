<?php

namespace Tests\Feature\AdminApp;

use App\Models\FortuneReading;
use App\Services\CelticCrossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🧊 GET /api/admin/fortune/active-readings — นิยาม "ค้าง" (StuckReadingFinder)
 *
 * ค้าง = ระบบไม่ขยับ ไม่ใช่ลูกค้าเงียบ:
 *   - paid / celtic_generating ไม่ขยับ > 2 นาที = ค้าง (ยกเว้นธง "AI ยังคิดอยู่")
 *   - Deep completed แต่ไม่มีคำทำนาย = งาน AI ล้ม
 *   - เก็บวันเกิด / เลือกไพ่ / รอคำถาม เงียบนานแค่ไหนก็ไม่ใช่ค้าง
 */
#[Group('admin-app')]
class AdminAppActiveReadingsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_stuck_flag_follows_the_system_not_the_customer(): void
    {
        $paid = ['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(20)];

        $deepHung = $this->makeReading($paid + ['conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subMinutes(6)]);
        $deepFresh = $this->makeReading($paid + ['conversation_status' => FortuneReading::STATUS_PAID, 'updated_at' => now()->subSeconds(30)]);
        $deepFailed = $this->makeReading($paid + ['conversation_status' => FortuneReading::STATUS_COMPLETED, 'updated_at' => now()->subMinutes(15)]);
        $deepDone = $this->makeReading($paid + ['conversation_status' => FortuneReading::STATUS_COMPLETED, 'deep_response' => 'คำทำนาย']);
        $waitingBirthdate = $this->makeReading($paid + ['conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE, 'updated_at' => now()->subMinutes(40)]);

        $celtic = ['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS, 'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(30)];
        $celticHung = $this->makeReading($celtic + ['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING, 'updated_at' => now()->subMinutes(5)]);
        $celticThinking = $this->makeReading($celtic + ['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING, 'updated_at' => now()->subMinutes(3)]);
        Cache::put(CelticCrossService::generationInFlightKey((int) $celticThinking->id), 1, 300);
        $celticPicking = $this->makeReading($celtic + ['conversation_status' => FortuneReading::STATUS_CELTIC_PICKING, 'updated_at' => now()->subMinutes(25)]);

        $juntra = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_JUNTRA, 'is_paid' => true,
            'amount_paid' => 99, 'paid_at' => now()->subMinutes(10), 'conversation_status' => FortuneReading::STATUS_PAID,
            'updated_at' => now()->subMinutes(10)]);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/fortune/active-readings?per_page=100')->assertOk();
        $rows = collect($res->json('data.data'))->keyBy('reading_id');

        $this->assertSame('ai_generating_timeout', $rows[$deepHung->id]['stuck_reason']);
        $this->assertFalse($rows[$deepFresh->id]['stuck']);
        $this->assertSame('deep_job_failed', $rows[$deepFailed->id]['stuck_reason']);
        $this->assertFalse($rows->has($deepDone->id), 'จบแล้ว ไม่ใช่กำลังใช้บริการ');
        $this->assertFalse($rows[$waitingBirthdate->id]['stuck'], 'ลูกค้ายังไม่ตอบ ≠ ระบบค้าง');
        $this->assertSame('ai_generating_timeout', $rows[$celticHung->id]['stuck_reason']);
        $this->assertFalse($rows[$celticThinking->id]['stuck'], 'AI ยังคิดอยู่ = ช้า ไม่ใช่ตาย');
        $this->assertFalse($rows[$celticPicking->id]['stuck']);
        $this->assertSame(['picked' => 0, 'questions_used' => 0], $rows[$celticPicking->id]['celtic']);
        $this->assertFalse($rows->has($juntra->id), 'บิลจันทราไม่มีงานของบอท');

        $this->assertSame(['total' => 7, 'stuck' => 3], $res->json('data.summary'));
        $this->assertTrue($rows->first()['stuck'], 'ค้างขึ้นก่อน');
        $this->assertGreaterThanOrEqual(5, $rows[$celticHung->id]['minutes_since_activity']);
        $this->assertSame('predicting', $rows[$celticHung->id]['stage']['key']);

        $onlyStuck = $this->getJson('/api/admin/fortune/active-readings?stuck=1')->assertOk();
        $this->assertSame(3, $onlyStuck->json('data.total'));
        $this->assertSame(1, $onlyStuck->json('data.current_page'));
        $this->assertSame(['total' => 7, 'stuck' => 3], $onlyStuck->json('data.summary'));
    }

    public function test_services_list_is_read_only(): void
    {
        $this->actAs($this->makeAdmin());

        $res = $this->getJson('/api/admin/fortune/services')->assertOk();
        $this->assertSame(['deep', 'celtic', 'free_card'], collect($res->json('data.services'))->pluck('id')->all());
        $this->assertFalse($res->json('data.writable'));
        foreach ($res->json('data.services') as $s) {
            $this->assertArrayHasKey('price_thb', $s);
            $this->assertArrayHasKey('is_active', $s);
            $this->assertArrayHasKey('reading_type', $s);
        }
    }
}
