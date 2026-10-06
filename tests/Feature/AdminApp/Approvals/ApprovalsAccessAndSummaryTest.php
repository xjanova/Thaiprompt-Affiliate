<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\ApprovalsSummaryController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Concerns\BuildsApprovalsWorld;
use Tests\TestCase;

/**
 * 🔐 คิวอนุมัติของแอปแอดมิน: ไม่มี token = 401 · ไม่ใช่แอดมิน = 403 NOT_ADMIN ทุก endpoint (อ่าน + เขียน)
 * + ตัวเลขป้ายรวม (approvals/summary) ตรงกับแต่ละคิว และ cache 20 วินาที
 */
#[Group('admin-app')]
class ApprovalsAccessAndSummaryTest extends TestCase
{
    use BuildsAdminAppWorld;
    use BuildsApprovalsWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    /**
     * @return array{reads: array<int, string>, writes: array<int, array{0: string, 1: array<string, mixed>}>}
     */
    private function endpoints(): array
    {
        $kyc = $this->makeEkycReview();
        $store = $this->makePendingStore();
        $rider = $this->makeRiderApplicant();
        $ticket = $this->makeTicket(User::factory()->create());
        $commission = $this->makeCommission();
        $target = User::factory()->create();

        return [
            'reads' => [
                '/api/admin/approvals/summary',
                '/api/admin/approvals/ekyc',
                "/api/admin/approvals/ekyc/{$kyc->id}",
                '/api/admin/approvals/seller-applications',
                "/api/admin/approvals/seller-applications/{$store->id}",
                '/api/admin/approvals/riders',
                "/api/admin/approvals/riders/{$rider->id}",
                '/api/admin/approvals/rider-jobs',
                '/api/admin/approvals/tickets',
                "/api/admin/approvals/tickets/{$ticket->id}",
                '/api/admin/approvals/mlm-commissions',
            ],
            'writes' => [
                ["/api/admin/approvals/ekyc/{$kyc->id}/approve", []],
                ["/api/admin/approvals/ekyc/{$kyc->id}/reject", ['reason' => 'บัตรไม่ชัด']],
                ["/api/admin/approvals/ekyc/{$kyc->id}/request-retake", ['reason' => 'ถ่ายใหม่']],
                ["/api/admin/approvals/seller-applications/{$store->id}/approve", []],
                ["/api/admin/approvals/seller-applications/{$store->id}/reject", ['reason' => 'ข้อมูลไม่ครบ']],
                ["/api/admin/approvals/riders/{$rider->id}/approve", []],
                ["/api/admin/approvals/riders/{$rider->id}/reject", ['reason' => 'เอกสารไม่ชัด']],
                ["/api/admin/approvals/riders/{$rider->id}/documents-reviewed", []],
                ["/api/admin/approvals/tickets/{$ticket->id}/reply", ['message' => 'สวัสดีค่ะ']],
                ["/api/admin/approvals/tickets/{$ticket->id}/status", ['status' => 'closed']],
                ["/api/admin/approvals/mlm-commissions/{$commission->id}/approve", []],
                ["/api/admin/approvals/mlm-commissions/{$commission->id}/pay", []],
                ["/api/admin/users/{$target->id}/suspend", ['reason' => 'สแปม']],
                ["/api/admin/users/{$target->id}/unsuspend", []],
                ["/api/admin/users/{$target->id}/reset-wallet-pin", []],
            ],
        ];
    }

    public function test_guests_get_401_and_members_get_403_everywhere_and_nothing_changes(): void
    {
        ['reads' => $reads, 'writes' => $writes] = $this->endpoints();

        foreach ($reads as $uri) {
            $this->getJson($uri)->assertStatus(401);
        }
        foreach ($writes as [$uri, $body]) {
            $this->postJson($uri, $body)->assertStatus(401);
        }

        $this->actAs($this->makeMember());
        foreach ($reads as $uri) {
            $this->getJson($uri)->assertStatus(403)->assertJsonPath('error_code', 'NOT_ADMIN');
        }
        foreach ($writes as [$uri, $body]) {
            $this->postJson($uri, $body)->assertStatus(403)->assertJsonPath('error_code', 'NOT_ADMIN');
        }

        // ไม่มีอะไรถูกเปลี่ยน
        $this->assertSame(1, \App\Models\KycVerification::where('status', 'pending')->count());
        $this->assertSame(1, \App\Models\VendorStore::where('status', 'pending')->count());
        $this->assertSame(1, \App\Models\Rider::where('status', 'pending')->count());
        $this->assertSame(1, \App\Models\MlmCommission::where('status', 'pending')->count());
        $this->assertSame(0, User::whereNotNull('blocked_at')->count());
        $this->assertSame(0, \App\Models\TicketReply::count());
    }

    public function test_admin_can_read_every_list_and_detail_with_flat_paging(): void
    {
        ['reads' => $reads] = $this->endpoints();

        $this->actAs($this->makeAdmin());
        foreach ($reads as $uri) {
            $this->getJson($uri)->assertOk()->assertJsonPath('success', true);
        }

        foreach (['ekyc', 'seller-applications', 'riders', 'rider-jobs', 'tickets', 'mlm-commissions'] as $queue) {
            $this->getJson('/api/admin/approvals/'.$queue.'?per_page=5')
                ->assertOk()
                ->assertJsonStructure(['success', 'data' => ['data', 'current_page', 'last_page', 'per_page', 'total']])
                ->assertJsonPath('data.per_page', 5);
        }

        // ค่ากรองแปลก = 422 envelope ภาษาไทย
        $this->getJson('/api/admin/approvals/ekyc?status=bogus')->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');
        $this->getJson('/api/admin/approvals/tickets?status=bogus')->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');
        $this->getJson('/api/admin/approvals/rider-jobs?filter=bogus')->assertStatus(422);
        $this->getJson('/api/admin/approvals/riders?per_page=500')->assertStatus(422);
    }

    public function test_summary_counts_each_queue_with_oldest_minutes_and_is_cached(): void
    {
        $this->travelTo(now()->subMinutes(90));
        $this->makeEkycReview(null, ['processed_at' => now(), 'submitted_at' => now()]);
        $this->travelBack();
        $this->makeEkycReview();
        $this->makeEkycReview(null, ['status' => 'approved']);
        $this->makePendingStore();
        $this->makePendingStore(null, ['status' => 'active']);
        $this->makeRiderApplicant();
        $this->makeRiderApplicant(['status' => 'approved', 'documents_changed_at' => now()->subMinutes(10)]);
        $customer = User::factory()->create();
        $this->makeTicket($customer);
        $closed = $this->makeTicket($customer);
        $closed->update(['status' => 'closed']);
        $this->makeCommission(null, 'pending', 100);
        $this->makeCommission(null, 'pending', 50.5);
        $this->makeCommission(null, 'approved', 70);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/approvals/summary')->assertOk();

        $res->assertJsonPath('data.degraded', [])
            ->assertJsonPath('data.queues.ekyc.count', 2)
            ->assertJsonPath('data.queues.seller_applications.count', 1)
            ->assertJsonPath('data.queues.rider_applications.count', 1)
            ->assertJsonPath('data.queues.rider_documents.count', 1)
            ->assertJsonPath('data.queues.rider_jobs.count', 0)
            ->assertJsonPath('data.queues.tickets.count', 1)
            ->assertJsonPath('data.queues.mlm_commissions.count', 2)
            ->assertJsonPath('data.queues.mlm_commissions.amount_thb', 150.5)
            ->assertJsonPath('data.queues.mlm_commissions.approved_unpaid', 1)
            ->assertJsonPath('data.total', 8);

        $this->assertGreaterThanOrEqual(89, $res->json('data.queues.ekyc.oldest_minutes'));
        $this->assertGreaterThanOrEqual(9, $res->json('data.queues.rider_documents.oldest_minutes'));
        $this->assertNull($res->json('data.queues.rider_jobs.oldest_minutes'));

        // cache 20 วินาที — คิวใหม่ยังไม่โผล่จนหมดอายุ แต่ generated_at เป็นเวลาของคำตอบ
        $this->assertTrue(Cache::has(ApprovalsSummaryController::CACHE_KEY));
        $this->makePendingStore();
        $this->getJson('/api/admin/approvals/summary')->assertJsonPath('data.queues.seller_applications.count', 1);
        $this->travel(21)->seconds();
        $this->getJson('/api/admin/approvals/summary')->assertJsonPath('data.queues.seller_applications.count', 2);
    }
}
