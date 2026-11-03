<?php

namespace Tests\Feature\Fortune;

use App\Http\Controllers\LineFortuneWebhookController;
use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\MlmMember;
use App\Models\MlmProspect;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * F15 + F34: หน้าต่างการผูกลิงก์เชิญดูดวง
 *
 * - 30 วันนับจากเวลาที่เพื่อนกดลิงก์ (followed_at) ไม่ใช่ 24 ชม. นับจากตอนสร้างลิงก์
 * - แถว followed ที่หมดอายุแล้วต้องไม่บล็อกลิงก์ใหม่
 * - เจ้าของลิงก์กดเอง / ลูกค้าที่เป็นสมาชิกของคนอื่นอยู่แล้ว → ไม่สร้างแถว followed (ไม่เปลี่ยนผู้แนะนำ)
 */
class FortuneReferralWindowTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake();
        $this->setUpFortuneChain();
    }

    private function sendToken(string $lineUserId, string $token): void
    {
        $ctrl = app(LineFortuneWebhookController::class);
        $m = new \ReflectionMethod($ctrl, 'handleReferralTokenMessage');
        $m->setAccessible(true);
        // replyToken = null → ไม่ส่งข้อความหาลูกค้า
        $m->invoke($ctrl, $lineUserId, $token, null);
    }

    private function linkFor(User $user, MlmMember $member): string
    {
        return basename(app(FortuneAffiliateService::class)->generateReferralLink($user, $member));
    }

    private function lineId(): string
    {
        return 'U'.Str::lower(Str::random(32));
    }

    /** ลูกค้ากดลิงก์ชั่วโมงที่ 23 แล้วจ่ายวันที่ 25 → ยังต้องลงใต้ผู้เชิญ */
    public function test_follow_in_time_then_pay_days_later_still_attributes_to_referrer(): void
    {
        [$aUser, $a] = $this->makeChainMember();
        $token = $this->linkFor($aUser, $a);

        $this->travel(23)->hours();
        $friend = $this->lineId();
        $this->sendToken($friend, $token);

        $ref = FortuneReferral::where('referral_token', $token)->first();
        $this->assertSame(FortuneReferral::STATUS_FOLLOWED, $ref->status);
        $this->assertTrue($ref->expires_at->equalTo($ref->followed_at->copy()->addDays(30)), 'หมดอายุ = followed_at + 30 วัน');

        $this->travel(25)->days();
        $reading = FortuneReading::create([
            'facebook_user_id' => $friend, 'platform_user_id' => $friend, 'platform' => 'line',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS, 'questions' => ['x'], 'ai_provider' => 'test',
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now(), 'conversation_status' => FortuneReading::STATUS_PAID,
        ]);
        $user = app(FortuneAffiliateService::class)->autoRegisterFromFortune($reading, $friend, null, 'line');

        $this->assertNotNull($user);
        $this->assertSame($a->id, MlmMember::where('user_id', $user->id)->value('unilevel_sponsor_id'));
        $this->assertSame(FortuneReferral::STATUS_CONVERTED, $ref->fresh()->status);
    }

    /** เกิน 30 วันนับจาก follow → ไม่ผูกแล้ว */
    public function test_attribution_ends_thirty_days_after_follow(): void
    {
        [$aUser, $a] = $this->makeChainMember();
        $token = $this->linkFor($aUser, $a);
        $friend = $this->lineId();
        $this->sendToken($friend, $token);

        $this->travel(29)->days();
        $this->assertNotNull(FortuneReferral::findActiveByPlatformUserId($friend));

        $this->travel(2)->days();
        $this->assertNull(FortuneReferral::findActiveByPlatformUserId($friend));
    }

    /** แถว followed ที่หมดอายุแล้ว ต้องไม่บล็อกลิงก์ใหม่ */
    public function test_expired_followed_row_does_not_block_a_new_link(): void
    {
        [$aUser, $a] = $this->makeChainMember();
        [$bUser, $b] = $this->makeChainMember();
        $friend = $this->lineId();

        $this->sendToken($friend, $this->linkFor($aUser, $a));
        $this->travel(31)->days();

        $tokenB = $this->linkFor($bUser, $b);
        $this->sendToken($friend, $tokenB);

        $refB = FortuneReferral::where('referral_token', $tokenB)->first();
        $this->assertSame(FortuneReferral::STATUS_FOLLOWED, $refB->status);
        $this->assertSame($friend, $refB->referred_line_user_id);
        $this->assertSame($refB->id, FortuneReferral::findActiveByPlatformUserId($friend)?->id);
    }

    /** แถว followed ที่ยังไม่หมดอายุ ยังบล็อกลิงก์ของคนอื่นเหมือนเดิม (ผู้เชิญคนแรกไม่โดนแย่ง) */
    public function test_unexpired_followed_row_still_blocks_another_link(): void
    {
        [$aUser, $a] = $this->makeChainMember();
        [$bUser, $b] = $this->makeChainMember();
        $friend = $this->lineId();

        $tokenA = $this->linkFor($aUser, $a);
        $this->sendToken($friend, $tokenA);
        $this->travel(3)->days();

        $tokenB = $this->linkFor($bUser, $b);
        $this->sendToken($friend, $tokenB);

        $this->assertSame(FortuneReferral::STATUS_PENDING, FortuneReferral::where('referral_token', $tokenB)->value('status'));
        $this->assertSame($tokenA, FortuneReferral::findActiveByPlatformUserId($friend)?->referral_token);
    }

    /** เจ้าของลิงก์กดลิงก์ของตัวเอง → ไม่สร้างแถว followed */
    public function test_referrer_clicking_own_link_creates_no_followed_row(): void
    {
        $ownLine = $this->lineId();
        [$aUser, $a] = $this->makeChainMember(null, ['line_user_id' => $ownLine]);
        $token = $this->linkFor($aUser, $a);

        $this->sendToken($ownLine, $token);

        $ref = FortuneReferral::where('referral_token', $token)->first();
        $this->assertSame(FortuneReferral::STATUS_PENDING, $ref->status);
        $this->assertNull($ref->referred_line_user_id);
        $this->assertSame(0, FortuneReferral::where('referred_line_user_id', $ownLine)->count());
    }

    /** สมาชิกเดิม (อยู่ใต้ A) กดลิงก์ของ B → ไม่สร้างแถว followed, prospect ของ B ไม่ค้าง in_progress, ผู้แนะนำไม่เปลี่ยน */
    public function test_existing_member_clicking_another_members_link_creates_no_followed_row(): void
    {
        [$aUser, $a] = $this->makeChainMember();
        [$bUser, $b] = $this->makeChainMember();
        $nLine = $this->lineId();
        [$nUser, $n] = $this->makeChainMember($a, ['line_user_id' => $nLine]);

        $tokenB = $this->linkFor($bUser, $b);
        $this->sendToken($nLine, $tokenB);

        $refB = FortuneReferral::where('referral_token', $tokenB)->first();
        $this->assertSame(FortuneReferral::STATUS_PENDING, $refB->status);
        $this->assertNull($refB->referred_line_user_id);
        $this->assertNotSame('in_progress', MlmProspect::find($refB->mlm_prospect_id)?->status);
        $this->assertSame($a->id, $n->fresh()->unilevel_sponsor_id);
    }
}
