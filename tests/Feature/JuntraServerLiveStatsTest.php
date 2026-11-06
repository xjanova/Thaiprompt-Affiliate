<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Services\Fortune\FortuneLiveActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🔴 (2026-11-04) /api/v1/juntra/server/stats/live — ตัวเลขสดหน้าห้องของเว็บจันทรา ต้องเป็นของจริงทุกตัว
 *
 * ตรึงไว้: คนเดิมทักรัว ๆ นับ 1 คน · เกินหน้าต่างเวลาไม่นับ · บิลคืนเงิน (is_paid=false) ไม่นับ ·
 * บิลค้างสถานะนานไม่ใช่คิว
 */
class JuntraServerLiveStatsTest extends TestCase
{
    use BuildsJuntraServerSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require base_path('database/migrations/2026_11_04_130000_create_fortune_live_activity_table.php'))->up();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_same_customer_counts_once_and_old_activity_drops_out(): void
    {
        $live = app(FortuneLiveActivity::class);

        $live->touch('facebook', 'psid-1');
        $live->touch('facebook', 'psid-1');
        $live->touch('line', 'U-2');
        $live->touch('web', '42');

        $this->assertSame(3, $live->chattingNow());
        $this->assertSame(3, DB::table('fortune_live_activity')->count());

        Carbon::setTestNow(now()->addMinutes(FortuneLiveActivity::WINDOW_MINUTES + 1));
        $this->assertSame(0, $live->chattingNow());

        // ทักใหม่หลังพ้นช่วงกันเขียนซ้ำ (60 วิ) → กลับมานับ ไม่สร้างแถวใหม่
        $live->touch('line', 'U-2');
        $this->assertSame(1, $live->chattingNow());
        $this->assertSame(3, DB::table('fortune_live_activity')->count());
    }

    public function test_blank_ids_are_ignored(): void
    {
        $live = app(FortuneLiveActivity::class);
        $live->touch('facebook', '  ');
        $live->touch('', 'x');

        $this->assertSame(0, $live->chattingNow());
    }

    public function test_bills_total_counts_paid_bills_only_and_queue_skips_stale_ones(): void
    {
        $now = now();
        DB::table('fortune_readings')->insert([
            // บิลจ่ายแล้วที่ทำนายเสร็จ (บอท + เว็บจันทรา)
            ['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COMPLETED, 'created_at' => $now, 'updated_at' => $now],
            ['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COMPLETED, 'created_at' => $now, 'updated_at' => $now],
            // จ่ายแล้ว กำลังเปิดไพ่อยู่ = คิว
            ['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING, 'created_at' => $now, 'updated_at' => $now],
            // จ่ายแล้วแต่ค้างสถานะมานาน = ไม่ใช่คิว (แต่ยังเป็นบิลจริง)
            ['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING, 'created_at' => $now, 'updated_at' => $now->copy()->subHours(3)],
            // ยังไม่จ่าย / คืนเงินแล้ว (voidApproval พลิก is_paid=false)
            ['is_paid' => false, 'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'created_at' => $now, 'updated_at' => $now],
            ['is_paid' => false, 'conversation_status' => FortuneReading::STATUS_COMPLETED, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $live = app(FortuneLiveActivity::class);
        $this->assertSame(4, $live->billsTotal());
        $this->assertSame(1, $live->readingQueue());
    }

    public function test_endpoint_returns_real_numbers_for_the_juntra_server_client(): void
    {
        $client = app(ClientRepository::class)->create(
            null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users'
        );
        Passport::actingAsClient($client, [], 'api-oauth');
        config(['services.juntra.server_client_ids' => []]);

        app(FortuneLiveActivity::class)->touch('facebook', 'psid-9');
        DB::table('fortune_readings')->insert([
            ['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COMPLETED, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withToken('server-token')
            ->getJson('/api/v1/juntra/server/stats/live')
            ->assertOk()
            ->assertJsonPath('data.chatting_now', 1)
            ->assertJsonPath('data.bills_total', 1)
            ->assertJsonPath('data.reading_queue', 0)
            ->assertJsonPath('data.window_minutes', FortuneLiveActivity::WINDOW_MINUTES);
    }
}
