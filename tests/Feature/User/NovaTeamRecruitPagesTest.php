<?php

namespace Tests\Feature\User;

use App\Models\LeadLock;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmProspect;
use App\Models\MlmTeamTransferRequest;
use App\Models\RecruitCustomization;
use App\Models\RecruitPageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * หน้าย้ายทีม / หน้าแนะนำสมาชิก (Recruit) / ผู้มุ่งหวัง ของสมาชิก — ธีมโนวาเต็ม
 */
class NovaTeamRecruitPagesTest extends TestCase
{
    use RefreshDatabase;

    private ?MlmPlan $plan = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    /**
     * สร้างสมาชิก MLM ให้ผู้ใช้ (แผนเดียวใช้ร่วมกันทั้งเทสต์)
     */
    private function mlm(User $user, array $attributes = []): MlmMember
    {
        $this->plan ??= MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        return MlmMember::create(array_merge([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'M'.strtoupper(substr(uniqid(), -8)),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
        ], $attributes));
    }

    /**
     * โลกย้ายทีม: แม่ทีมเก่า ← ลูกทีม (ผู้ขอย้าย) → แม่ทีมใหม่
     *
     * @return array{0: User, 1: User, 2: User, 3: MlmTeamTransferRequest}
     */
    private function transferWorld(array $requestAttributes = []): array
    {
        $oldLeader = $this->member(['name' => 'แม่ทีมเก่า']);
        $oldMember = $this->mlm($oldLeader);
        $newLeader = $this->member(['name' => 'แม่ทีมใหม่']);
        $newMember = $this->mlm($newLeader);
        $mover = $this->member(['name' => 'ลูกทีมขอย้าย']);
        $moverMember = $this->mlm($mover, ['unilevel_sponsor_id' => $oldMember->id]);

        $request = MlmTeamTransferRequest::create(array_merge([
            'mlm_member_id' => $moverMember->id,
            'user_id' => $mover->id,
            'old_unilevel_sponsor_id' => $oldMember->id,
            'new_unilevel_sponsor_id' => $newMember->id,
            'status' => MlmTeamTransferRequest::STATUS_PENDING,
            'transfer_fee' => 100,
            'reason' => 'อยากย้ายไปทีมใหม่',
        ], $requestAttributes));

        return [$mover, $oldLeader, $newLeader, $request];
    }

    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('team-recruit.css', $html);
    }

    public function test_smoke_every_get_page_opens(): void
    {
        [$mover, $oldLeader, , $request] = $this->transferWorld();
        RecruitCustomization::create(['user_id' => $oldLeader->id, 'is_active' => true]);
        LeadLock::create([
            'visitor_identifier' => 'v1', 'ip_address' => '203.0.113.45', 'team_leader_id' => $oldLeader->id,
            'locked_at' => now(), 'expires_at' => now()->addDay(), 'status' => 'locked',
        ]);
        RecruitPageVisit::create([
            'team_leader_id' => $oldLeader->id, 'visitor_identifier' => 'v1', 'device_type' => 'mobile',
            'referrer_url' => 'https://facebook.com/x', 'visited_at' => now(), 'is_unique_visit' => true,
        ]);
        $prospect = MlmProspect::create([
            'sponsor_mlm_member_id' => $oldLeader->mlmMember->id, 'sponsor_user_id' => $oldLeader->id,
            'referral_token' => 'tok-'.uniqid(), 'status' => 'pending', 'is_locked' => false,
        ]);

        $pages = [
            [$mover, route('user.team-transfer.index')],
            [$mover, route('user.team-transfer.show', $request)],
            [$oldLeader, route('user.team-transfer.create')],
            [$oldLeader, route('user.team-transfer.approvals')],
            [$oldLeader, route('user.marketing.recruit.index')],
            [$oldLeader, route('user.marketing.recruit.leads')],
            [$oldLeader, route('user.marketing.recruit.analytics')],
            [$oldLeader, route('user.prospects.index')],
            [$oldLeader, route('user.prospects.create')],
            [$oldLeader, route('user.prospects.show', $prospect->id)],
        ];

        foreach ($pages as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);
            $this->assertSame(200, $response->status(), $url.' → '.$response->status().' '.substr(strip_tags((string) $response->exception?->getMessage()), 0, 300));
            $this->assertFullNova($response->getContent());
        }
    }

    private function prospect(User $sponsor, array $attributes = []): MlmProspect
    {
        return MlmProspect::create(array_merge([
            'sponsor_mlm_member_id' => $sponsor->mlmMember->id,
            'sponsor_user_id' => $sponsor->id,
            'referral_token' => 'tok'.bin2hex(random_bytes(8)),
            'status' => 'pending',
            'is_locked' => false,
        ], $attributes));
    }

    // ── ย้ายทีม ─────────────────────────────────────────────

    public function test_team_transfer_index_lists_own_requests(): void
    {
        [$mover, , , $request] = $this->transferWorld();

        $html = $this->actingAs($mover)->get(route('user.team-transfer.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('คำขอ #'.$request->id, $html);
        $this->assertStringContainsString(route('user.team-transfer.cancel', $request), $html);
    }

    public function test_team_transfer_index_empty_state(): void
    {
        $html = $this->actingAs($this->member())->get(route('user.team-transfer.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ยังไม่มีคำขอย้ายทีม', $html);
    }

    public function test_team_transfer_create_keeps_field_names(): void
    {
        $leader = $this->member();
        $leaderMember = $this->mlm($leader);
        $user = $this->member();
        $this->mlm($user, ['unilevel_sponsor_id' => $leaderMember->id]);

        $html = $this->actingAs($user)->get(route('user.team-transfer.create'))->assertOk()->getContent();

        $this->assertFullNova($html);
        foreach (['name="new_sponsor_code"', 'name="reason"', 'name="notes"', 'name="_token"'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString($leaderMember->member_code, $html);
    }

    public function test_team_transfer_create_old_reason_with_quotes_and_newlines_does_not_break_js(): void
    {
        $user = $this->member();
        $this->mlm($user);

        // เหตุผลที่มีเครื่องหมายคำพูด + ขึ้นบรรทัดใหม่ — เดิมฝังลง '...' ของ JS ตรงๆ แล้วสคริปต์พัง
        $html = $this->actingAs($user)
            ->withSession(['_old_input' => ['reason' => "first 'quoted'\nsecond</script>"]])
            ->get(route('user.team-transfer.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString("first 'quoted'", $html);
        $this->assertStringNotContainsString('second</script>', $html);
    }

    public function test_team_transfer_store_without_mlm_membership_redirects_instead_of_500(): void
    {
        $response = $this->actingAs($this->member())->post(route('user.team-transfer.store'), [
            'new_sponsor_code' => 'M00000000',
        ]);

        $response->assertRedirect(route('user.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_team_transfer_show_for_owner(): void
    {
        [$mover, , , $request] = $this->transferWorld([
            'status' => MlmTeamTransferRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $html = $this->actingAs($mover)->get(route('user.team-transfer.show', $request))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('แม่ทีมเดิมอนุมัติ', $html);
        $this->assertStringContainsString(route('user.team-transfer.pay', $request), $html);
    }

    public function test_team_transfer_show_survives_rejecter_without_rejected_at(): void
    {
        [$mover, $oldLeader, , $request] = $this->transferWorld([
            'status' => MlmTeamTransferRequest::STATUS_REJECTED,
            'rejection_reason' => 'ยังไม่ครบสัญญา',
        ]);
        // ข้อมูลเก่า: มีผู้ปฏิเสธแต่ไม่มี rejected_at (หน้าเดิมเรียก ->locale() บน null = 500)
        $request->forceFill(['rejected_by' => $oldLeader->id, 'rejected_at' => null])->save();

        $html = $this->actingAs($mover)->get(route('user.team-transfer.show', $request))->assertOk()->getContent();

        $this->assertStringContainsString('ยังไม่ครบสัญญา', $html);
    }

    public function test_team_transfer_of_another_member_is_forbidden(): void
    {
        [, , , $request] = $this->transferWorld();
        $stranger = $this->member();

        $this->actingAs($stranger)->get(route('user.team-transfer.show', $request))->assertForbidden();
        $this->actingAs($stranger)->post(route('user.team-transfer.cancel', $request))->assertForbidden();
        $this->actingAs($stranger)->post(route('user.team-transfer.pay', $request))->assertForbidden();
        $this->assertSame(MlmTeamTransferRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_team_transfer_approvals_escapes_member_name_in_confirm(): void
    {
        [$mover, $oldLeader, , $request] = $this->transferWorld();
        // ชื่อที่พยายามหลุดออกจากสตริง JS ใน confirm()
        $mover->forceFill(['name' => "x');alert(1);//"])->save();

        $html = $this->actingAs($oldLeader)->get(route('user.team-transfer.approvals'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString(route('user.team-transfer.approve', $request), $html);
        $this->assertStringContainsString('name="rejection_reason"', $html);
        $this->assertStringNotContainsString("x');alert(1);//", $html);
        // เดิม: onclick="return confirm('อนุมัติให้ x&#039;);alert(1);// …')" — เบราว์เซอร์ถอด &#039; กลับเป็น ' ก่อนรัน JS
        $this->assertStringNotContainsString("confirm('อนุมัติให้", $html);
        // ตอนนี้ชื่อเข้าไปในสตริง JS ผ่าน @js (อัญประกาศเดี่ยวถูกเข้ารหัสเป็นยูนิโค้ด u0027)
        $this->assertStringContainsString('u0027);alert(1);', $html);
    }

    public function test_team_transfer_approvals_keeps_processed_tab_open_when_paging(): void
    {
        [, $oldLeader] = $this->transferWorld(['status' => MlmTeamTransferRequest::STATUS_REJECTED, 'rejected_at' => now()]);

        $response = $this->actingAs($oldLeader)->get(route('user.team-transfer.approvals', ['processed_page' => 1]))->assertOk();

        $response->assertViewHas('activeTab', 'processed');
    }

    // ── หน้าแนะนำสมาชิก (Recruit) ───────────────────────────

    public function test_recruit_index_keeps_form_fields(): void
    {
        $user = $this->member();
        $member = $this->mlm($user);

        $html = $this->actingAs($user)->get(route('user.marketing.recruit.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        foreach (['custom_name', 'custom_phone', 'custom_address', 'custom_pitch', 'custom_image'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html);
        }
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('<input type="hidden" name="is_active" value="0">', $html);
        $this->assertStringContainsString($member->member_code, $html);
    }

    public function test_recruit_update_can_switch_page_off(): void
    {
        $user = $this->member();
        RecruitCustomization::create(['user_id' => $user->id, 'is_active' => true]);

        // ฟอร์มส่ง hidden 0 เมื่อไม่ได้ติ๊ก (เดิมไม่ส่งอะไรเลย = ปิดไม่ได้)
        $this->actingAs($user)->put(route('user.marketing.recruit.update'), [
            'custom_name' => 'ทีมสายฟ้า',
            'is_active' => '0',
        ])->assertRedirect();

        $fresh = RecruitCustomization::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $fresh->is_active);
        $this->assertSame('ทีมสายฟ้า', $fresh->custom_name);
    }

    public function test_recruit_leads_masks_visitor_ip(): void
    {
        $user = $this->member();
        LeadLock::create([
            'visitor_identifier' => 'v-active', 'ip_address' => '203.0.113.45', 'team_leader_id' => $user->id,
            'locked_at' => now(), 'expires_at' => now()->addDay(), 'status' => 'locked',
        ]);

        $html = $this->actingAs($user)->get(route('user.marketing.recruit.leads'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('203.0.*.*', $html);
        $this->assertStringNotContainsString('203.0.113.45', $html);
        $this->assertStringContainsString('nvm-table-wrap', $html);
    }

    public function test_recruit_leads_expired_filter_includes_status_expired(): void
    {
        $user = $this->member();
        // ระบบตั้ง status=expired แล้ว (เดิมไม่โผล่ในแท็บไหนเลย)
        LeadLock::create([
            'visitor_identifier' => 'v-exp', 'ip_address' => '198.51.100.7', 'team_leader_id' => $user->id,
            'locked_at' => now()->subDays(3), 'expires_at' => now()->subDay(), 'status' => 'expired',
        ]);
        // ยังล็อกอยู่แต่เลยเวลา
        $late = LeadLock::create([
            'visitor_identifier' => 'v-late', 'ip_address' => '198.51.100.8', 'team_leader_id' => $user->id,
            'locked_at' => now()->subDays(3), 'expires_at' => now()->subHour(), 'status' => 'locked',
        ]);

        $response = $this->actingAs($user)->get(route('user.marketing.recruit.leads', ['status' => 'expired']))->assertOk();

        $this->assertSame(2, $response->viewData('stats')['expired']);
        $this->assertCount(2, $response->viewData('leads')->items());
        $this->assertTrue(collect($response->viewData('leads')->items())->contains('id', $late->id));
    }

    public function test_recruit_analytics_renders_and_rejects_bad_days(): void
    {
        $user = $this->member();
        RecruitPageVisit::create([
            'team_leader_id' => $user->id, 'visitor_identifier' => 'v1', 'device_type' => 'mobile',
            'referrer_url' => 'https://facebook.com/somepost', 'visited_at' => now(), 'is_unique_visit' => true,
        ]);

        $html = $this->actingAs($user)->get(route('user.marketing.recruit.analytics'))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('facebook.com', $html);
        $this->assertStringContainsString('มือถือ', $html);
        $this->assertStringNotContainsString('chart.umd', $html);

        // ค่าแปลกจาก URL — เดิม subDays('abc') = TypeError (500)
        $this->actingAs($user)->get(route('user.marketing.recruit.analytics', ['days' => 'abc']))->assertOk()->assertViewHas('days', 30);
        $this->actingAs($user)->get(route('user.marketing.recruit.analytics', ['days' => 100000]))->assertOk()->assertViewHas('days', 30);
    }

    public function test_recruit_analytics_daily_series_keeps_leads_on_days_without_visits(): void
    {
        $user = $this->member();
        // ลีดเมื่อ 2 วันก่อน ไม่มีการเข้าชมวันนั้นเลย (เดิมจุดนี้หายจากกราฟ)
        LeadLock::create([
            'visitor_identifier' => 'v2', 'team_leader_id' => $user->id,
            'locked_at' => now()->subDays(2), 'expires_at' => now()->addDay(), 'status' => 'locked',
        ]);

        $daily = $this->actingAs($user)->get(route('user.marketing.recruit.analytics', ['days' => 7]))->assertOk()->viewData('daily');

        $this->assertGreaterThanOrEqual(8, count($daily));
        $day = collect($daily)->first(fn ($d) => $d['date']->toDateString() === now()->subDays(2)->toDateString());
        $this->assertNotNull($day);
        $this->assertSame(1, $day['leads']);
        $this->assertSame(0, $day['visits']);
    }

    // ── ผู้มุ่งหวัง (ลิงก์เชิญ) ───────────────────────────────

    public function test_prospects_index_shows_real_picture_column_and_hides_line_user_id(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $this->prospect($user, [
            'line_display_name' => 'น้องฟ้า',
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
            'line_picture_url' => 'https://profile.line-scdn.net/abc123',
            'status' => 'in_progress',
        ]);

        $html = $this->actingAs($user)->get(route('user.prospects.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('น้องฟ้า', $html);
        $this->assertStringContainsString('https://profile.line-scdn.net/abc123', $html);
        $this->assertStringNotContainsString('U1234567890abcdef', $html, 'ไม่ควรโชว์ LINE user ID ของผู้มุ่งหวัง');
    }

    public function test_prospects_index_search_and_date_filters_work(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $this->prospect($user, ['line_display_name' => 'สมชายใจดี']);
        $old = $this->prospect($user, ['line_display_name' => 'สมหญิงเก่า']);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();

        $response = $this->actingAs($user)->get(route('user.prospects.index', ['search' => 'สมชาย']))->assertOk();
        $this->assertCount(1, $response->viewData('prospects')->items());

        $response = $this->actingAs($user)->get(route('user.prospects.index', ['date_from' => now()->subDays(5)->toDateString()]))->assertOk();
        $names = collect($response->viewData('prospects')->items())->pluck('line_display_name');
        $this->assertTrue($names->contains('สมชายใจดี'));
        $this->assertFalse($names->contains('สมหญิงเก่า'));

        // วันที่ผิดรูปแบบ / สถานะแปลก = ไม่กรอง (ไม่ 500)
        $response = $this->actingAs($user)->get(route('user.prospects.index', ['date_from' => 'not-a-date', 'status' => 'hack']))->assertOk();
        $this->assertCount(2, $response->viewData('prospects')->items());
    }

    public function test_prospects_index_without_mlm_membership_shows_notice(): void
    {
        $html = $this->actingAs($this->member())->get(route('user.prospects.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('คุณยังไม่ได้เป็นสมาชิก MLM', $html);
    }

    public function test_prospects_create_for_member_and_non_member(): void
    {
        $user = $this->member();
        $this->mlm($user);

        $html = $this->actingAs($user)->get(route('user.prospects.create'))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString(route('user.prospects.store'), $html);

        $html = $this->actingAs($this->member())->get(route('user.prospects.create'))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('คุณยังไม่ได้เป็นสมาชิก MLM', $html);
    }

    public function test_prospects_show_hides_registered_user_email(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $joined = $this->member(['name' => 'ลูกทีมใหม่', 'email' => 'secret-downline@example.com']);
        $prospect = $this->prospect($user, [
            'status' => 'completed',
            'registered_user_id' => $joined->id,
            'registered_at' => now(),
            'visit_count' => 3,
        ]);

        $html = $this->actingAs($user)->get(route('user.prospects.show', $prospect->id))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ลูกทีมใหม่', $html);
        $this->assertStringNotContainsString('secret-downline@example.com', $html);
        $this->assertStringContainsString('3 ครั้ง', $html);
    }

    public function test_prospects_show_pending_has_share_link_and_qr(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $prospect = $this->prospect($user);

        $html = $this->actingAs($user)->get(route('user.prospects.show', $prospect->id))->assertOk()->getContent();

        $this->assertStringContainsString($prospect->referral_token, $html);
        $this->assertStringContainsString('nvmTrProspectShare', $html);
        $this->assertStringContainsString(route('user.prospects.renew', $prospect->id), $html);
    }

    public function test_prospects_of_another_member_return_404(): void
    {
        $owner = $this->member();
        $this->mlm($owner);
        $prospect = $this->prospect($owner);
        $intruder = $this->member();
        $this->mlm($intruder);

        $this->actingAs($intruder)->get(route('user.prospects.show', $prospect->id))->assertNotFound();
        $this->actingAs($intruder)->post(route('user.prospects.renew', $prospect->id))->assertNotFound();
        $this->actingAs($intruder)->delete(route('user.prospects.destroy', $prospect->id))->assertNotFound();
        $this->assertNotNull($prospect->fresh());
    }

    public function test_prospects_renew_reopens_an_expired_clicked_link(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $prospect = $this->prospect($user, [
            'status' => 'expired',
            'clicked_at' => now()->subDays(3),
            'is_locked' => true,
            'locked_until' => now()->subDay(),
        ]);

        $this->actingAs($user)->post(route('user.prospects.renew', $prospect->id))
            ->assertRedirect(route('user.prospects.show', $prospect->id));

        $fresh = $prospect->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertTrue($fresh->locked_until->isFuture());
        // เปิดหน้าดูแล้วต้องยังไม่กลับเป็นหมดอายุ (isLocked() เดิมเห็น locked_until เก่า)
        $this->assertTrue($fresh->isLocked());
    }

    public function test_prospects_resend_records_follow_up_time(): void
    {
        $user = $this->member();
        $this->mlm($user);
        $prospect = $this->prospect($user);
        $prospect->forceFill(['updated_at' => now()->subDays(2)])->saveQuietly();

        $this->actingAs($user)->post(route('user.prospects.resend', $prospect->id))->assertRedirect();

        $this->assertTrue($prospect->fresh()->updated_at->gt(now()->subMinute()));
    }
}
