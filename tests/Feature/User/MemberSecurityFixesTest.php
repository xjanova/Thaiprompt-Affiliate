<?php

namespace Tests\Feature\User;

use App\Models\EmailPreference;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * บั๊กฝั่งสมาชิกกลุ่ม โปรไฟล์ / 2FA / ตั๋ว / แจ้งเตือน / ตั้งค่าอีเมล ที่แก้เมื่อ 29 ต.ค. 2026
 *
 * แต่ละเทสต์ล็อกพฤติกรรมที่ถูกต้องไว้ ไม่ให้ย้อนกลับไปเป็นแบบเดิม
 */
class MemberSecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'is_super_admin' => false], $attrs));
    }

    /** สมาชิกที่เปิด 2FA แล้ว */
    private function memberWith2fa(): array
    {
        $user = $this->member();
        $settings = TwoFactorUserSetting::getOrCreateForUser($user);
        $settings->enabled = true;
        $settings->save();

        return [$user, $settings];
    }

    /** session ที่เพิ่งยืนยัน 2FA สำหรับจัดการ 2FA */
    private function verifiedFor(string $action): array
    {
        return ['2fa_verified_at' => now()->toDateTimeString(), '2fa_verified_for' => $action];
    }

    // ── โปรไฟล์ / รหัสผ่าน ───────────────────────────────────────

    public function test_profile_form_cannot_change_the_password(): void
    {
        $user = $this->member(['password' => Hash::make('old-secret-123')]);

        $res = $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'สมชาย ใจดี',
            'email' => 'member.fix@gmail.com',
            'current_password' => 'old-secret-123',
            'new_password' => 'new-secret-456',
            'new_password_confirmation' => 'new-secret-456',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertTrue(Hash::check('old-secret-123', $user->fresh()->password), 'รหัสผ่านต้องไม่เปลี่ยนผ่านฟอร์มโปรไฟล์');
        $this->assertNotSame('สมชาย ใจดี', $user->fresh()->name, 'ข้อมูลโปรไฟล์ยังไม่ถูกบันทึกเมื่อถูกปฏิเสธ');
    }

    public function test_validation_messages_are_thai(): void
    {
        $user = $this->member();

        $res = $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'สมชาย',
            'email' => 'not-an-email',
        ]);

        $res->assertSessionHasErrors('email');
        $message = session('errors')->first('email');
        $this->assertStringContainsString('อีเมล', $message);
        $this->assertDoesNotMatchRegularExpression('/must be a valid/i', $message);
    }

    public function test_clearing_the_phone_resets_phone_verification(): void
    {
        $user = $this->member(['phone' => '0812345678', 'phone_verified' => true, 'phone_verified_at' => now()]);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'member.phone@gmail.com',
            'phone' => '',
        ])->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertNull($fresh->phone);
        $this->assertFalse((bool) $fresh->phone_verified);
        $this->assertNull($fresh->phone_verified_at);
    }

    // ── 2FA ──────────────────────────────────────────────────────

    public function test_disabling_2fa_requires_a_fresh_code(): void
    {
        [$user, $settings] = $this->memberWith2fa();

        $res = $this->actingAs($user)->post(route('user.two-factor.disable'));

        $res->assertRedirect();
        $this->assertStringContainsString('action=manage_2fa', $res->headers->get('Location'));
        $this->assertTrue($settings->fresh()->enabled, 'ยังไม่ยืนยันรหัส ต้องปิด 2FA ไม่ได้');

        $this->actingAs($user)->withSession($this->verifiedFor('manage_2fa'))
            ->post(route('user.two-factor.disable'))
            ->assertSessionHas('success');
        $this->assertFalse($settings->fresh()->enabled);
    }

    public function test_verification_for_another_action_does_not_unlock_2fa_changes(): void
    {
        [$user, $settings] = $this->memberWith2fa();

        $this->actingAs($user)->withSession($this->verifiedFor('withdrawal'))
            ->post(route('user.two-factor.disable'))
            ->assertRedirect();

        $this->assertTrue($settings->fresh()->enabled);
    }

    public function test_setup_page_renders_for_a_member_with_2fa_enabled(): void
    {
        [$user, $settings] = $this->memberWith2fa();
        $settings->generateRecoveryCodes();

        $this->actingAs($user)->get(route('user.two-factor.setup'))->assertOk();
    }

    public function test_regenerating_recovery_codes_shows_them_once_from_the_session(): void
    {
        [$user] = $this->memberWith2fa();

        $res = $this->actingAs($user)->withSession($this->verifiedFor('manage_2fa'))
            ->post(route('user.two-factor.regenerate-recovery-codes'));

        $res->assertRedirect(route('user.two-factor.recovery-codes'));
        $codes = session('recovery_codes');
        $this->assertIsArray($codes);
        $this->assertCount(10, $codes);

        $this->actingAs($user)->withSession(['recovery_codes' => $codes])
            ->get(route('user.two-factor.recovery-codes'))
            ->assertOk()
            ->assertSee($codes[0]);
    }

    public function test_recovery_codes_page_ignores_codes_in_the_url(): void
    {
        [$user] = $this->memberWith2fa();

        $this->actingAs($user)
            ->get(route('user.two-factor.recovery-codes', ['codes' => ['LEAK-CODE1']]))
            ->assertOk()
            ->assertDontSee('LEAK-CODE1');
    }

    public function test_recovery_codes_accept_lowercase_and_spaces(): void
    {
        [, $settings] = $this->memberWith2fa();
        $codes = $settings->generateRecoveryCodes(2);

        $this->assertTrue($settings->verifyRecoveryCode('  '.strtolower($codes[0]).' '));
        $this->assertFalse($settings->fresh()->verifyRecoveryCode($codes[0]), 'ใช้แล้วต้องใช้ซ้ำไม่ได้');
    }

    // ── แจ้งเตือน ────────────────────────────────────────────────

    public function test_archiving_an_unread_notification_clears_it_from_the_badge(): void
    {
        $user = $this->member();
        $n = Notification::create(['user_id' => $user->id, 'type' => 'system', 'title' => 'ทดสอบ', 'message' => 'ข้อความ']);
        $this->assertSame(1, $user->fresh()->unread_notifications_count);

        $n->archive();

        $this->assertTrue($n->fresh()->is_read);
        $this->assertSame(0, $user->fresh()->unread_notifications_count);
    }

    public function test_notification_links_only_allow_internal_paths_and_http(): void
    {
        $url = fn (string $v) => (new Notification(['action_url' => $v]))->action_url;

        $this->assertNull($url('javascript:alert(1)'));
        $this->assertNull($url(' JavaScript:alert(1)'));
        $this->assertNull($url('data:text/html,hi'));
        $this->assertNull($url('//evil.example'));
        $this->assertNull($url('/\\evil.example'));
        $this->assertSame('/user/wallet', $url('/user/wallet'));
        $this->assertSame('https://main.thaiprompt.online/x', $url('https://main.thaiprompt.online/x'));
    }

    // ── ตั๋ว ─────────────────────────────────────────────────────

    private function ticketFor(User $user, string $status = 'open'): Ticket
    {
        $category = TicketCategory::create(['name' => 'ทั่วไป', 'color' => '#3B82F6', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($user)->post(route('user.tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'ถอนเงินไม่เข้า',
            'description' => 'รายละเอียดปัญหา',
            'priority' => 'medium',
        ])->assertRedirect();

        $ticket = Ticket::where('user_id', $user->id)->latest('id')->firstOrFail();
        $ticket->update(['status' => $status]);

        return $ticket->fresh();
    }

    public function test_customer_reply_notifies_staff_once_until_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $user = $this->member();
        $ticket = $this->ticketFor($user);
        Notification::where('user_id', $admin->id)->update(['is_read' => true]);

        $this->actingAs($user)->post(route('user.tickets.reply', $ticket->id), ['message' => 'ยังไม่ได้รับเงินครับ'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('user.tickets.reply', $ticket->id), ['message' => 'รบกวนด้วยครับ'])->assertSessionHas('success');

        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('title', 'ลูกค้าตอบกลับตั๋ว')->count());
        $this->assertSame(0, Notification::where('user_id', $user->id)->where('title', 'ลูกค้าตอบกลับตั๋ว')->count());
    }

    public function test_resolved_ticket_can_be_replied_to_and_closed_but_closed_ticket_cannot(): void
    {
        $user = $this->member();

        $resolved = $this->ticketFor($user, 'resolved');
        $this->actingAs($user)->get(route('user.tickets.show', $resolved->id))->assertOk()->assertSee('name="message"', false);
        $this->actingAs($user)->post(route('user.tickets.reply', $resolved->id), ['message' => 'ยังไม่หายครับ'])->assertSessionHas('success');
        $this->assertSame('open', $resolved->fresh()->status, 'ตอบตั๋วที่แก้ไขแล้ว = เปิดใหม่');

        $resolved->update(['status' => 'resolved']);
        $this->actingAs($user)->post(route('user.tickets.close', $resolved->id))->assertSessionHas('success');
        $this->assertSame('closed', $resolved->fresh()->status);

        $this->actingAs($user)->post(route('user.tickets.reply', $resolved->id), ['message' => 'อีกเรื่องครับ'])->assertSessionHas('error');
        $this->assertSame('closed', $resolved->fresh()->status);
    }

    public function test_ticket_text_is_length_limited(): void
    {
        $user = $this->member();
        $ticket = $this->ticketFor($user);

        $this->actingAs($user)->post(route('user.tickets.reply', $ticket->id), ['message' => str_repeat('ก', 5001)])
            ->assertSessionHasErrors('message');
    }

    public function test_open_filter_matches_the_open_count(): void
    {
        $user = $this->member();
        $waiting = $this->ticketFor($user, 'waiting_customer');

        $res = $this->actingAs($user)->get(route('user.tickets.index', ['status' => 'active']))->assertOk();

        $res->assertSee($waiting->ticket_number);
        $this->assertSame(1, $res->viewData('stats')['open']);
        $this->assertSame(1, $res->viewData('stats')['waiting']);
    }

    // ── ตั้งค่าอีเมล ─────────────────────────────────────────────

    public function test_unticked_email_switches_are_saved_as_off(): void
    {
        $user = $this->member();
        EmailPreference::getOrCreateForUser($user->id);

        // ฟอร์มจริง: checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมา
        $this->actingAs($user)->put(route('user.email.preferences.update'), [
            'all_emails' => '1',
            'security_alerts' => '1',
            'preferred_language' => 'th',
        ])->assertSessionHas('success', 'บันทึกการตั้งค่าอีเมลแล้ว');

        $pref = EmailPreference::where('user_id', $user->id)->first();
        $this->assertTrue((bool) $pref->security_alerts);
        $this->assertFalse((bool) $pref->marketing_emails);
        $this->assertFalse((bool) $pref->weekly_reports);
    }
}
