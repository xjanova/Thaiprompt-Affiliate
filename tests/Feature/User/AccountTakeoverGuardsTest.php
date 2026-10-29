<?php

namespace Tests\Feature\User;

use App\Mail\AccountEmailChangedMail;
use App\Mail\VerifyAccountEmailMail;
use App\Models\TwoFactorUserSetting;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\AccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * 🔒 ทางยึดบัญชีด้วย session ที่ถูกขโมย 2 ทาง ที่ปิดเมื่อ 29 ต.ค. 2026
 *
 *  1. เปลี่ยนอีเมล (PUT /user/profile) — เดิมเปลี่ยนทันที ไม่ถามรหัสผ่าน ไม่รีเซ็ตสถานะยืนยัน ไม่แจ้งอีเมลเดิม
 *  2. ลบบัญชี — เดิมบัญชีที่ผูก LINE/Facebook พิมพ์ "ลบบัญชี" อย่างเดียวก็ลบได้
 *
 * รหัส OTP อ่านจาก cache (EmailOtpService เก็บที่ otp:{email}) — อีเมล OTP ส่งผ่าน Mail::raw ซึ่ง Mail::fake ไม่เก็บไว้
 */
class AccountTakeoverGuardsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Secret-pass-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // 2FA ของ route profile_change ไม่ใช่เรื่องที่เทสต์นี้ดู
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Mail::fake();
        Http::fake();
    }

    // ── เปลี่ยนอีเมล: บัญชีที่มีอีเมลจริง ─────────────────────────────

    public function test_email_change_without_password_is_rejected(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com', 'name' => 'ชื่อเดิม']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'ชื่อใหม่',
            'email' => 'attacker@gmail.com',
        ])->assertSessionHasErrors('email_password');

        $fresh = $user->fresh();
        $this->assertSame('owner@gmail.com', $fresh->email);
        $this->assertSame('ชื่อเดิม', $fresh->name, 'ไม่ผ่านการยืนยัน = ไม่บันทึกอะไรเลย');
        $this->assertStringContainsString('รหัสผ่าน', session('errors')->first('email_password'));
        Mail::assertNothingSent();
    }

    public function test_email_change_with_wrong_password_is_rejected(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'attacker@gmail.com',
            'email_password' => 'wrong-password',
        ])->assertSessionHasErrors(['email_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);

        $this->assertSame('owner@gmail.com', $user->fresh()->email);
    }

    public function test_email_change_with_password_resets_verification_and_notifies_the_old_address(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com', 'email_verified_at' => now()]);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'owner.new@gmail.com',
            'email_password' => self::PASSWORD,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertSame('owner.new@gmail.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at, 'อีเมลใหม่ยังไม่ได้พิสูจน์ — ต้องไม่ถือว่ายืนยันแล้ว');

        Mail::assertSent(VerifyAccountEmailMail::class, fn ($mail) => $mail->hasTo('owner.new@gmail.com'));
        Mail::assertSent(AccountEmailChangedMail::class, function ($mail) {
            return $mail->hasTo('owner@gmail.com') && $mail->envelope()->subject === 'อีเมลบัญชีของคุณถูกเปลี่ยน';
        });
    }

    public function test_code_for_a_real_email_account_goes_to_the_old_address_not_the_new_one(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'attacker@gmail.com'])
            ->assertOk()->assertJsonPath('success', true);

        // คนขโมย session เลือกทางรหัสแทนรหัสผ่านได้ แต่รหัสไปที่กล่องเดิมของเจ้าของ ไม่ใช่กล่องของคนร้าย
        $this->assertNotNull(Cache::get('otp:owner@gmail.com'));
        $this->assertNull(Cache::get('otp:attacker@gmail.com'));
    }

    public function test_real_email_account_without_a_known_password_can_confirm_with_the_old_address_code(): void
    {
        $user = $this->member(['email' => 'google.user@gmail.com', 'password' => Hash::make('random-unknown-'.uniqid())]);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'google.new@gmail.com'])->assertOk();
        $code = Cache::get('otp:google.user@gmail.com')['otp'];

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'google.new@gmail.com',
            'email_code' => $code,
        ])->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('google.new@gmail.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
        Mail::assertSent(AccountEmailChangedMail::class, fn ($mail) => $mail->hasTo('google.user@gmail.com'));
    }

    public function test_a_code_requested_for_one_new_email_cannot_confirm_another(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'first@gmail.com'])->assertOk();
        $code = Cache::get('otp:owner@gmail.com')['otp'];

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'second@gmail.com',
            'email_code' => $code,
        ])->assertSessionHasErrors('email_code');

        $this->assertSame('owner@gmail.com', $user->fresh()->email);
    }

    public function test_password_guessing_through_the_profile_form_is_rate_limited(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->put(route('user.profile.update'), [
                'name' => $user->name, 'email' => 'attacker@gmail.com', 'email_password' => 'guess-'.$i,
            ]);
        }

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name, 'email' => 'attacker@gmail.com', 'email_password' => self::PASSWORD,
        ])->assertSessionHasErrors('email_password');

        $this->assertStringContainsString('ผิดหลายครั้ง', session('errors')->first('email_password'));
        $this->assertSame('owner@gmail.com', $user->fresh()->email);
    }

    // ── เปลี่ยนอีเมล: บัญชี LINE/FB ที่ระบบตั้งอีเมลให้ (ไม่มีรหัสผ่านที่ใช้ได้) ─────────────

    public function test_passwordless_account_needs_a_code_sent_to_the_new_email(): void
    {
        $user = $this->member(['email' => 'line_U'.str_repeat('a', 32).'@thaiprompt.local', 'line_user_id' => 'U'.str_repeat('a', 32), 'email_verified_at' => null]);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'line.member@gmail.com',
        ])->assertSessionHasErrors('email_code');
        $this->assertStringEndsWith('@thaiprompt.local', $user->fresh()->email);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'line.member@gmail.com'])->assertOk();
        $code = Cache::get('otp:line.member@gmail.com')['otp'];

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'line.member@gmail.com',
            'email_code' => $code,
        ])->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('line.member@gmail.com', $fresh->email);
        $this->assertNotNull($fresh->email_verified_at, 'กรอกรหัสจากกล่องใหม่ถูก = พิสูจน์กล่องใหม่แล้ว');
        // อีเมลเดิมเป็นอีเมลระบบ ไม่มีกล่องจริง → ไม่ส่งแจ้งเตือน
        Mail::assertNotSent(AccountEmailChangedMail::class);
        Http::assertNothingSent();
    }

    public function test_wrong_code_does_not_change_the_email(): void
    {
        $user = $this->member(['email' => 'fb_123456@thaiprompt.local', 'facebook_psid' => '123456']);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'fb.member@gmail.com'])->assertOk();
        $code = Cache::get('otp:fb.member@gmail.com')['otp'];

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'fb.member@gmail.com',
            'email_code' => $code === '000000' ? '111111' : '000000',
        ])->assertSessionHasErrors('email_code');

        $this->assertSame('fb_123456@thaiprompt.local', $user->fresh()->email);
    }

    public function test_system_email_account_can_save_its_profile_without_changing_the_email(): void
    {
        $user = $this->member(['email' => 'line_Ubbb@thaiprompt.local', 'line_user_id' => 'Ubbb']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'ชื่อใหม่จาก LINE',
            'email' => 'line_Ubbb@thaiprompt.local',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ชื่อใหม่จาก LINE', $user->fresh()->name);
    }

    public function test_send_code_rejects_the_current_or_a_reserved_email_in_thai(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'OWNER@gmail.com'])
            ->assertStatus(422)->assertJsonPath('message', 'อีเมลใหม่ต้องไม่ซ้ำกับอีเมลปัจจุบัน');

        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'fb_1@thaiprompt.local'])
            ->assertStatus(422)->assertJsonValidationErrors('new_email');
    }

    public function test_new_email_must_be_a_real_mailbox(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);

        foreach (['someone@line.user', 'someone@line.local'] as $systemEmail) {
            $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => $systemEmail])
                ->assertStatus(422)->assertJsonPath('errors.new_email.0', AccountEmail::NOT_DELIVERABLE_MESSAGE);
        }

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'someone@line.user',
            'email_password' => self::PASSWORD,
        ])->assertSessionHasErrors(['email' => AccountEmail::NOT_DELIVERABLE_MESSAGE]);

        $this->assertSame('owner@gmail.com', $user->fresh()->email);
    }

    public function test_mobile_line_placeholder_email_counts_as_a_system_email(): void
    {
        // MobileApiController::lineLoginCallback สร้างบัญชีด้วย {LINE userId}@line.user + รหัสผ่านสุ่ม
        $lineId = 'U'.str_repeat('c', 32);
        $user = $this->member(['email' => $lineId.'@line.user', 'line_user_id' => $lineId]);

        // ลบบัญชี: ไม่มีอีเมลจริง ไม่มี 2FA → ต้องเพิ่มอีเมลก่อน (ไม่ส่งรหัสไปกล่องที่ไม่มีใครรับ)
        $this->assertSame(AccountDeletionService::METHOD_ADD_EMAIL, app(AccountDeletionService::class)->confirmationMethod($user));

        // เพิ่มอีเมลจริง: รหัสไปที่อีเมลใหม่ ไม่ใช่ @line.user
        $this->actingAs($user)->postJson(route('user.profile.email.send-code'), ['new_email' => 'mobile.member@gmail.com'])->assertOk();
        $this->assertNull(Cache::get('otp:'.strtolower($lineId).'@line.user'));
        $code = Cache::get('otp:mobile.member@gmail.com')['otp'];

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => $user->name,
            'email' => 'mobile.member@gmail.com',
            'email_code' => $code,
        ])->assertSessionHasNoErrors();

        $this->assertSame('mobile.member@gmail.com', $user->fresh()->email);
        Mail::assertNotSent(AccountEmailChangedMail::class);
        $this->assertSame(AccountDeletionService::METHOD_EMAIL_CODE, app(AccountDeletionService::class)->confirmationMethod($user->fresh()));
    }

    public function test_profile_page_offers_the_right_confirmation_for_each_account(): void
    {
        $real = $this->member(['email' => 'owner@gmail.com', 'email_verified_at' => null]);
        $this->actingAs($real)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('emailChangeBox', false)
            ->assertSee('name="email_password"', false)
            ->assertSee('อีเมลนี้ยังไม่ได้ยืนยัน');

        $line = $this->member(['email' => 'line_Ukkk@thaiprompt.local', 'line_user_id' => 'Ukkk']);
        $this->actingAs($line)->get(route('user.profile'))
            ->assertOk()
            ->assertSee('ยืนยันด้วยรหัส 6 หลักที่ส่งไปที่อีเมลใหม่')
            ->assertDontSee('name="email_password"', false);
    }

    // ── ลิงก์ยืนยันอีเมล ───────────────────────────────────────────

    public function test_verification_link_verifies_and_stops_working_after_another_change(): void
    {
        $user = $this->member(['email' => 'new.owner@gmail.com', 'email_verified_at' => null]);
        $url = (new VerifyAccountEmailMail($user))->verifyUrl();

        $this->get($url)->assertRedirect(route('login'))->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->email_verified_at);

        // อีเมลเปลี่ยนอีกรอบ → ลิงก์เก่าต้องใช้ไม่ได้
        $user->refresh()->forceFill(['email' => 'other@gmail.com', 'email_verified_at' => null])->save();
        $this->get($url)->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertNull($user->fresh()->email_verified_at);

        // ลายเซ็นถูกแก้ → ข้อความไทย ไม่ใช่หน้า 403
        $this->get($url.'x')->assertRedirect(route('login'))->assertSessionHas('error');
    }

    // ── หน้าโปรไฟล์ผู้ขาย ──────────────────────────────────────────

    public function test_seller_profile_cannot_change_the_email(): void
    {
        $seller = $this->member(['email' => 'seller@gmail.com', 'role' => 'seller']);

        // หน้าผู้ขายต้อง KYC + มีร้าน — ไม่ใช่เรื่องที่เทสต์นี้ดู
        $this->withoutMiddleware([\App\Http\Middleware\EnsureKycVerified::class, \App\Http\Middleware\EnsureHasVendorStore::class]);

        $this->actingAs($seller)->put(route('seller.profile.update'), [
            'name' => $seller->name,
            'email' => 'attacker@gmail.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame('seller@gmail.com', $seller->fresh()->email);
    }

    // ── ลบบัญชี ───────────────────────────────────────────────────

    public function test_line_linked_account_cannot_delete_with_the_phrase_alone(): void
    {
        [$user, $token] = $this->apiUser(['email' => 'line.real@gmail.com', 'line_user_id' => 'U'.str_repeat('c', 32)]);

        $this->withToken($token)->getJson('/api/v1/account/deletion-check')
            ->assertOk()
            ->assertJsonPath('data.confirmation_method', AccountDeletionService::METHOD_EMAIL_CODE)
            ->assertJsonPath('data.requires_password', false);

        $this->withToken($token)->deleteJson('/api/v1/account', ['confirm_text' => AccountDeletionService::CONFIRM_TEXT])
            ->assertStatus(422)
            ->assertJsonPath('code', 'EMAIL_CODE_REQUIRED');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_line_linked_account_deletes_with_a_code_sent_to_its_email(): void
    {
        [$user, $token] = $this->apiUser(['email' => 'line.real@gmail.com', 'line_user_id' => 'U'.str_repeat('d', 32)]);

        $this->withToken($token)->postJson('/api/v1/account/delete/send-code')->assertOk()->assertJsonPath('code', 'EMAIL_CODE_SENT');
        $code = Cache::get('otp:line.real@gmail.com')['otp'];

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'email_code' => $code === '000000' ? '111111' : '000000',
        ])->assertStatus(422)->assertJsonPath('code', 'EMAIL_CODE_INVALID');
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'email_code' => $code,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        Http::assertNothingSent();
    }

    public function test_correct_password_still_works_for_a_line_linked_account(): void
    {
        [$user, $token] = $this->apiUser(['email' => 'line.real@gmail.com', 'line_user_id' => 'U'.str_repeat('e', 32)]);

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_system_email_account_without_2fa_must_add_a_real_email_first(): void
    {
        [$user, $token] = $this->apiUser(['email' => 'line_Ufff@thaiprompt.local', 'line_user_id' => 'Ufff']);

        $this->withToken($token)->getJson('/api/v1/account/deletion-check')
            ->assertJsonPath('data.confirmation_method', AccountDeletionService::METHOD_ADD_EMAIL)
            ->assertJsonPath('data.can_use_email_code', false);

        $this->withToken($token)->postJson('/api/v1/account/delete/send-code')->assertStatus(422);

        $this->withToken($token)->deleteJson('/api/v1/account', ['confirm_text' => AccountDeletionService::CONFIRM_TEXT])
            ->assertStatus(422)
            ->assertJsonPath('code', 'REAL_EMAIL_REQUIRED');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        // ห้ามส่งอะไรทาง LINE push (โควต้าทั้งธุรกิจ 300/เดือน)
        Http::assertNothingSent();
    }

    public function test_system_email_account_with_authenticator_2fa_deletes_with_its_code(): void
    {
        [$user, $token] = $this->apiUser(['email' => 'line_Uggg@line.local', 'line_user_id' => 'Uggg']);
        $settings = TwoFactorUserSetting::getOrCreateForUser($user);
        $settings->enabled = true;
        $settings->save();
        $secret = $settings->generateGoogle2FASecret();

        $this->withToken($token)->getJson('/api/v1/account/deletion-check')
            ->assertJsonPath('data.confirmation_method', AccountDeletionService::METHOD_TWO_FACTOR);

        $this->withToken($token)->deleteJson('/api/v1/account', ['confirm_text' => AccountDeletionService::CONFIRM_TEXT])
            ->assertStatus(422)->assertJsonPath('code', 'TWO_FACTOR_CODE_REQUIRED');

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'two_factor_code' => (new Google2FA)->getCurrentOtp($secret),
        ])->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        Http::assertNothingSent();
    }

    public function test_deletion_proof_guessing_is_rate_limited(): void
    {
        $user = $this->member(['email' => 'owner@gmail.com']);
        $service = app(AccountDeletionService::class);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('PASSWORD_INCORRECT', $service->confirmationError($user, AccountDeletionService::CONFIRM_TEXT, 'guess-'.$i)['code']);
        }

        $this->assertSame('TOO_MANY_ATTEMPTS', $service->confirmationError($user, AccountDeletionService::CONFIRM_TEXT, self::PASSWORD)['code']);
    }

    public function test_web_delete_page_shows_the_right_confirmation_for_each_account(): void
    {
        $lineReal = $this->member(['email' => 'line.web@gmail.com', 'line_user_id' => 'Uhhh']);
        $this->actingAs($lineReal)->get(route('account.delete'))
            ->assertOk()
            ->assertSee('name="email_code"', false)
            ->assertSee('ส่งรหัสไปที่อีเมล');

        $lineOnly = $this->member(['email' => 'line_Uiii@thaiprompt.local', 'line_user_id' => 'Uiii']);
        $this->actingAs($lineOnly)->get(route('account.delete'))
            ->assertOk()
            ->assertSee('ต้องเพิ่มอีเมลจริงก่อนลบบัญชี')
            ->assertDontSee('name="confirm_text"', false);
    }

    public function test_web_form_deletes_a_line_linked_account_with_the_email_code(): void
    {
        $user = $this->member(['email' => 'line.web@gmail.com', 'line_user_id' => 'Ujjj']);

        $this->actingAs($user)->postJson(route('account.delete.send-code'))->assertOk()->assertJsonPath('success', true);
        $code = Cache::get('otp:line.web@gmail.com')['otp'];

        $this->actingAs($user)->post(route('account.delete.destroy'), [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
        ])->assertSessionHasErrors('email_code');
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);

        $this->actingAs($user)->post(route('account.delete.destroy'), [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'email_code' => $code,
        ])->assertRedirect(route('account.delete'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    // ── helpers ───────────────────────────────────────────────────

    private function member(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'is_super_admin' => false,
            'password' => Hash::make(self::PASSWORD),
        ], $attrs));
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function apiUser(array $attrs = []): array
    {
        $user = $this->member($attrs);

        return [$user, $user->createToken('mobile-app')->plainTextToken];
    }
}
