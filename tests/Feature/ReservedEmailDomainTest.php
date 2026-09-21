<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\MlmPlan;
use App\Models\User;
use App\Rules\NotReservedEmailDomain;
use App\Services\FortuneAffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * 🔒 (2026-09-21) โดเมน @thaiprompt.local สงวนให้ระบบ — กันยึดบัญชีลูกค้าบอทด้วยอีเมลที่เดาได้
 *
 * ช่องโหว่เดิม: บอทสร้างบัญชีลูกค้าด้วย fb_{PSID}@thaiprompt.local / line_{uid}@… และหาบัญชีคืน
 *   จากอีเมลรูปแบบนี้ ใครสมัคร/แก้โปรไฟล์เป็นอีเมลนั้นไว้ก่อน = บิล สมาชิก MLM ลิงก์เชิญ และค่าแนะนำ
 *   ทั้งสายของลูกค้าจริงไปผูกกับบัญชีผู้บุกรุก
 * กันสองชั้น: (1) ทุกช่องอีเมลที่ผู้ใช้กำหนดได้ปฏิเสธโดเมนนี้ (2) เส้นหาจากอีเมลเชื่อเฉพาะบัญชี bot_provisioned
 *
 * @group security
 */
class ReservedEmailDomainTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '9876543210987654';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing มี APP_KEY ความยาวผิด — session/cookie ของเว็บต้องใช้ encrypter จริง
        config(['app.key' => 'base64:'.base64_encode(str_repeat('r', 32))]);
        $this->app->forgetInstance('encrypter');
        \Illuminate\Support\Facades\Crypt::clearResolvedInstance('encrypter');

        MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);
        FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app',
            'facebook_page_id' => 'test-page',
            'is_enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // settings ถูก cache แบบ static — ไม่ล้าง = เทสต์ถัดไปได้ page id ของไฟล์นี้ (FB OAuth match พัง)
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    public function test_rule_recognises_the_reserved_domain_in_every_spelling(): void
    {
        foreach ([
            'fb_123@thaiprompt.local',
            'FB_123@ThaiPrompt.LOCAL',
            'x@sub.thaiprompt.local',
            'x@thaiprompt.local.',
            ' x@thaiprompt.local ',
        ] as $email) {
            $this->assertTrue(NotReservedEmailDomain::isReserved($email), $email);
        }

        foreach ([
            'someone@gmail.com',
            'x@thaiprompt.online',
            'x@notthaiprompt.local',
            'thaiprompt.local@gmail.com',
            'no-at-sign',
            null,
        ] as $email) {
            $this->assertFalse(NotReservedEmailDomain::isReserved($email), (string) $email);
        }

        $v = Validator::make(['email' => 'fb_1@thaiprompt.local'], ['email' => ['required', 'email', new NotReservedEmailDomain]]);
        $this->assertTrue($v->fails());
        $this->assertSame(NotReservedEmailDomain::MESSAGE, $v->errors()->first('email'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // ทางเข้าที่ผู้ใช้กำหนดอีเมลเองได้ — ต้องปฏิเสธโดเมนสงวนทุกทาง
    // ─────────────────────────────────────────────────────────────────────

    public function test_web_register_refuses_the_reserved_domain(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCloudfareTurnstile::class)
            ->post('/register', [
                'name' => 'ผู้บุกรุก',
                'email' => 'fb_'.self::PSID.'@thaiprompt.local',
                'password' => 'Str0ng!Passw0rd',
                'password_confirmation' => 'Str0ng!Passw0rd',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'fb_'.self::PSID.'@thaiprompt.local']);
    }

    public function test_mobile_register_refuses_the_reserved_domain(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'ผู้บุกรุก',
            'email' => 'LINE_'.self::LINE_UID.'@ThaiPrompt.Local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_profile_update_refuses_the_reserved_domain(): void
    {
        $member = User::factory()->create(['role' => 'user', 'email' => 'real@example.com']);

        $this->actingAs($member)
            ->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
            ->put('/user/profile', ['name' => 'ชื่อเดิม', 'email' => 'fb_'.self::PSID.'@thaiprompt.local'])
            ->assertSessionHasErrors('email');

        $this->assertSame('real@example.com', $member->fresh()->email);
    }

    public function test_pos_customer_creation_refuses_the_reserved_domain(): void
    {
        // /pos/* เปิดเฉพาะเจ้าของร้าน/แอดมิน (ดู PosCustomerApiTest)
        $this->actingAs(User::factory()->create(['role' => 'seller']))
            ->postJson('/pos/api/customers', ['name' => 'ลูกค้า POS', 'email' => 'x@sub.thaiprompt.local'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** อีเมลจาก Facebook ที่ตรงสูตรบอท ต้องไม่ใช้ผูก FB เข้ากับบัญชีบอทของคนอื่น และไม่ตั้งเป็นอีเมลบัญชีใหม่ */
    public function test_facebook_login_ignores_a_reserved_provider_email(): void
    {
        $bot = User::createBotProvisioned([
            'name' => 'ลูกค้าบอท',
            'email' => 'fb_'.self::PSID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
        ]);
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response([], 500)]);

        $fbUser = (new \Laravel\Socialite\Two\User)->map([
            'id' => '1234567890123456',
            'name' => 'ผู้บุกรุก',
            'email' => 'fb_'.self::PSID.'@thaiprompt.local',
        ]);
        $method = new \ReflectionMethod(\App\Http\Controllers\Auth\FacebookLoginController::class, 'findOrCreateUser');
        $method->setAccessible(true);
        $user = $method->invoke(new \App\Http\Controllers\Auth\FacebookLoginController, $fbUser);

        $this->assertNotSame($bot->id, $user->id);
        $this->assertSame('fboauth_1234567890123456@thaiprompt.local', $user->email);
        $this->assertNull($bot->fresh()->facebook_user_id, 'ต้องไม่ผูก FB เข้ากับบัญชีบอท');
    }

    /** บัญชีหน้าตาเหมือนของบอท แต่ไม่ได้สร้างโดยบอท → บอทต้องไม่ผูกลูกค้าเข้ากับบัญชีนั้น */
    public function test_bot_ignores_a_lookalike_account_it_did_not_create(): void
    {
        $attacker = User::factory()->create(['email' => 'fb_'.self::PSID.'@thaiprompt.local']);

        $resolved = app(FortuneAffiliateService::class)->resolveOrCreateWebUser('facebook', self::PSID);

        $this->assertNotNull($resolved);
        $this->assertNotSame($attacker->id, $resolved->id);
        $this->assertTrue((bool) $resolved->bot_provisioned);
        $this->assertNull(User::findByMessengerPsid('nothing-here'));
    }

    public function test_bot_still_finds_the_accounts_it_created(): void
    {
        $fb = User::createBotProvisioned([
            'name' => 'ลูกค้า FB เก่า',
            'email' => 'fb_'.self::PSID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
        ]);
        $line = User::createBotProvisioned([
            'name' => 'ลูกค้า LINE',
            'email' => 'line_'.self::LINE_UID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
        ]);

        $service = app(FortuneAffiliateService::class);

        $this->assertSame($fb->id, $service->resolveOrCreateWebUser('facebook', self::PSID)->id);
        $this->assertSame($fb->id, User::findByMessengerPsid(self::PSID)->id);
        $this->assertSame($line->id, User::findBotAccountByLocalEmail('line_'.self::LINE_UID.'@thaiprompt.local')->id);
        $this->assertSame(2, User::count(), 'ห้ามสร้างบัญชีซ้ำให้ลูกค้าเดิม');
    }

    public function test_bot_marker_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'พยายามตั้งป้ายเอง',
            'email' => 'someone@example.com',
            'password' => bcrypt('password'),
            'bot_provisioned' => true,
        ]);

        $this->assertFalse((bool) $user->fresh()->bot_provisioned);
    }

    public function test_migration_marks_existing_bot_accounts_only(): void
    {
        $bot = User::factory()->create(['email' => 'fb_'.self::PSID.'@thaiprompt.local']);
        $line = User::factory()->create(['email' => 'line_'.self::LINE_UID.'@thaiprompt.local']);
        $tg = User::factory()->create(['email' => 'tg_123456@thaiprompt.local']);
        $oauth = User::factory()->create(['email' => 'fboauth_555@thaiprompt.local']);
        $human = User::factory()->create(['email' => 'someone@example.com']);

        $migration = include database_path('migrations/2026_09_21_120000_add_bot_provisioned_to_users_table.php');
        $migration->up();

        $this->assertTrue((bool) $bot->fresh()->bot_provisioned);
        $this->assertTrue((bool) $line->fresh()->bot_provisioned);
        $this->assertTrue((bool) $tg->fresh()->bot_provisioned);
        $this->assertFalse((bool) $oauth->fresh()->bot_provisioned, 'บัญชี FB OAuth ไม่ใช่บัญชีบอท');
        $this->assertFalse((bool) $human->fresh()->bot_provisioned);
    }
}
