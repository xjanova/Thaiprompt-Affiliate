<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Http\Controllers\Admin\RiderJobController as WebRiderJobController;
use App\Http\Controllers\Api\Admin\Approvals\RiderJobsController;
use App\Models\DeliveryHandover;
use App\Models\Order;
use App\Models\RiderJob;
use App\Models\User;
use App\Services\Rider\HandoverService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Feature\RiderR2\HandoverTestCase;

/**
 * 💸 แอปแอดมิน: งานไรเดอร์ที่รอตัดสิน — เงินเคลื่อนผ่าน Admin\RiderJobController + HandoverService ตัวเดิมเท่านั้น
 *
 * ออเดอร์มาตรฐาน (HandoverTestCase): สินค้า 100 (GP 10%) + ค่าส่ง 40 = 140 · ไรเดอร์ได้ 32 · ร้านได้ 90
 */
#[Group('admin-app')]
class RiderJobsApprovalsTest extends HandoverTestCase
{
    use BuildsAdminAppWorld;

    /**
     * งานที่ผู้ซื้อร้องเรียน (ไรเดอร์ยืนยันด้วยรหัสแล้ว → ผู้ซื้อแจ้งได้ของผิด) — flow จริงของแอป
     *
     * @return array{0: RiderJob, 1: Order, 2: User, 3: User}
     */
    private function disputedJob(): array
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)->assertOk();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'wrong_item', 'note' => 'ได้สีผิด'])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'disputed');

        return [$job->fresh(), $order, $buyer, $seller];
    }

    public function test_queue_lists_disputes_with_amounts_reason_and_actions(): void
    {
        [$job] = $this->disputedJob();

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/approvals/rider-jobs')->assertOk();

        $res->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $job->id)
            ->assertJsonPath('data.data.0.decision_type', 'dispute')
            ->assertJsonPath('data.data.0.reason.code', 'wrong_item')
            ->assertJsonPath('data.data.0.reason.note', 'ได้สีผิด')
            ->assertJsonPath('data.data.0.amounts.order_total_thb', 140.0)
            ->assertJsonPath('data.data.0.amounts.delivery_fee_thb', 40.0)
            ->assertJsonPath('data.data.0.amounts.rider_earnings_thb', 32.0)
            ->assertJsonPath('data.data.0.handover.status', 'disputed')
            ->assertJsonPath('data.data.0.actions.can_release', true)
            ->assertJsonPath('data.data.0.actions.can_refund', true);

        $this->getJson('/api/admin/approvals/rider-jobs?filter=disputed')->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/approvals/rider-jobs?filter=manual_needed')->assertJsonPath('data.total', 0);

        $detail = $this->getJson('/api/admin/approvals/rider-jobs/'.$job->id)->assertOk();
        $detail->assertJsonPath('data.job_number', $job->job_number);
        $this->assertIsArray($detail->json('data.eligible_riders'));
        $this->assertStringNotContainsString('"secret"', $detail->getContent());
        $this->assertStringNotContainsString('code_hash', $detail->getContent());

        $this->getJson('/api/admin/approvals/summary')
            ->assertJsonPath('data.queues.rider_jobs.count', 1)
            ->assertJsonPath('data.queues.rider_jobs.disputed', 1);

        // เพดาน 50 ต่อหน้า (ขอ 100 = ได้ 50 ไม่ใช่ 422)
        $this->getJson('/api/admin/approvals/rider-jobs?per_page=100')
            ->assertOk()
            ->assertJsonPath('data.per_page', RiderJobsController::MAX_PER_PAGE);
    }

    /**
     * สองแอดมินกดปล่อยเงินพร้อมกัน: อีกคนปล่อยไปก่อนระหว่างที่คำขอนี้ผ่านด่านตรวจซ้ำแล้ว
     * → service ตอบ HANDOVER_FINAL แต่ผลตรงกับที่สั่ง = 200 already_decided · เงินขยับครั้งเดียว
     */
    public function test_concurrent_release_loser_gets_already_decided_and_money_moves_once(): void
    {
        [$job, , , $seller] = $this->disputedJob();
        $otherAdmin = $this->makeAdmin();

        // จำลองคำขอของแอดมินอีกคนที่ commit ก่อนเสี้ยววินาที (หลังด่านตรวจซ้ำของ controller แอป แต่ก่อน service ล็อกแถว)
        $this->app->bind(WebRiderJobController::class, function ($app) use ($otherAdmin) {
            return new class($app->make(RiderJobService::class), $app->make(RiderDispatchService::class), $otherAdmin) extends WebRiderJobController
            {
                public function __construct(RiderJobService $jobs, RiderDispatchService $dispatch, private readonly User $racer)
                {
                    parent::__construct($jobs, $dispatch);
                }

                public function handoverRelease(Request $request, RiderJob $job): JsonResponse|RedirectResponse
                {
                    app(HandoverService::class)->adminRelease(RiderJob::findOrFail($job->id), $this->racer, 'แอดมินอีกคนกดก่อน');

                    return parent::handoverRelease($request, $job);
                }
            };
        });

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/release')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.already_decided', true)
            ->assertJsonPath('data.job.status', 'completed');

        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001, 'ไรเดอร์ได้ค่าส่งครั้งเดียว');
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001, 'ร้านได้เงินครั้งเดียว');

        // ผลอื่นแพ้การแข่ง (คืนเงินหลังอีกคนปล่อยเงินไปแล้ว) → คง 409 HANDOVER_FINAL
        $this->app->offsetUnset(WebRiderJobController::class);
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/refund', ['reason' => 'ผู้ซื้อขอคืน'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'HANDOVER_FINAL');
    }

    public function test_release_pays_rider_and_shop_once_and_repeat_is_idempotent(): void
    {
        [$job, , , $seller] = $this->disputedJob();
        $member = User::factory()->create(['role' => 'user']);

        // ไม่ใช่แอดมิน → ไม่มีเงินขยับ
        $this->actAs($member);
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/release')->assertStatus(403);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/release', ['reason' => 'ตรวจรูปแล้ว ของถูกต้อง'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.status', 'completed');

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);

        // กดซ้ำ → สำเร็จเฉย ๆ เงินไม่ขยับซ้ำ
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/release')
            ->assertOk()->assertJsonPath('data.already_decided', true);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);

        // ปล่อยแล้วคืนเงินไม่ได้ (service ปฏิเสธ — การส่งมอบปิดไปแล้ว)
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/refund', ['reason' => 'เปลี่ยนใจ'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'HANDOVER_FINAL');

        // หายจากคิว
        $this->getJson('/api/admin/approvals/rider-jobs')->assertJsonPath('data.total', 0);
    }

    public function test_refund_requires_reason_returns_money_to_buyer_and_rider_gets_nothing(): void
    {
        [$job, , $buyer] = $this->disputedJob();

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/refund', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาระบุเหตุผลที่คืนเงิน');
        $this->assertSame('disputed', $this->handoverOf($job)->status);

        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/refund', ['reason' => 'ผู้ซื้อส่งรูปของผิดมาแล้ว'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $this->handoverOf($job)->status);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/refund', ['reason' => 'ซ้ำ'])
            ->assertOk()->assertJsonPath('data.already_decided', true);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001, 'ห้ามคืนเงินซ้ำ');
    }

    public function test_manual_dispatch_reassign_and_redispatch_go_through_web_checks(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $first = $this->makeRider();
        $job = $this->makeHandoverJob($order, $first, 'pending', [
            'rider_id' => null,
            'dispatch_type' => 'manual_needed',
            'handover_required' => false,
            'accepted_at' => null,
            'picked_up_at' => null,
        ]);
        $newRider = $this->makeRider();

        $this->actAs($this->makeAdmin());
        $this->getJson('/api/admin/approvals/rider-jobs?filter=manual_needed')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.decision_type', 'manual_dispatch')
            ->assertJsonPath('data.data.0.actions.can_reassign', true);

        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/reassign', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาเลือกไรเดอร์');
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/reassign', ['rider_id' => 999999])
            ->assertStatus(422);

        // งานยังไม่ยกเลิก → สร้างงานใหม่ไม่ได้
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/redispatch')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVALID_TRANSITION');

        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/reassign', ['rider_id' => $newRider->id])
            ->assertOk()
            ->assertJsonPath('data.job.rider_id', $newRider->id);
        $this->assertSame((int) $newRider->id, (int) $job->fresh()->rider_id);

        // กดซ้ำด้วยไรเดอร์คนเดิม = สำเร็จเฉย ๆ
        $this->postJson('/api/admin/approvals/rider-jobs/'.$job->id.'/reassign', ['rider_id' => $newRider->id])
            ->assertOk()->assertJsonPath('data.already_decided', true);

        $this->getJson('/api/admin/approvals/rider-jobs?filter=manual_needed')->assertJsonPath('data.total', 0);
    }
}
