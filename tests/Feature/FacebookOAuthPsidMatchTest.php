<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\FacebookLoginController;
use App\Models\FortuneTellingSetting;
use App\Models\MlmPlan;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * 🔗 ทดสอบการ map FB OAuth (App-Scoped ID) → บัญชีที่บอทสมัครให้ (Messenger PSID)
 *
 * ปัญหาเดิม (2026-07-16): ลูกค้าดูดวงทาง FB Messenger 648 คนบน prod
 * เข้าบัญชี/วอลเลตตัวเองไม่ได้เลย — บัญชีที่บอทสร้างระบุตัวตนด้วย PSID
 * (ฝังในอีเมล fb_{PSID}@thaiprompt.local) แต่ FB OAuth ให้ ASID คนละ ID space
 * → กด login = ได้บัญชีเปล่าใบใหม่ ถ้าเป็น affiliate จะถอนคอมไม่ได้ตลอดกาล
 *
 * การแก้: OAuth callback เรียก Graph API ids_for_pages map ASID→PSID
 * แล้วหาบัญชีเดิมจากคอลัมน์ facebook_psid (backfill จากอีเมลใน migration)
 *
 * @group security
 */
class FacebookOAuthPsidMatchTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_ID = '107173337600346';

    private const APP_ID = '664172615253513';

    private const APP_SECRET = 'test-app-secret';

    private const PSID = '9876543210987654';

    private const ASID = '1234567890123456';

    protected function setUp(): void
    {
        parent::setUp();

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
            'facebook_page_id' => self::PAGE_ID,
            'facebook_page_token' => 'test-page-token',
            'is_enabled' => true,
        ]);

        // 🔑 (2026-07-28) ids_for_pages ใช้ App Access Token (app_id|app_secret)
        //    ไม่ใช่ page token — ต้องมีแถวนี้ ไม่งั้น resolve ไม่ได้เลย
        \App\Models\FacebookOAuthSetting::create([
            'app_id' => self::APP_ID,
            'app_secret' => self::APP_SECRET,
            'redirect_uri' => '/auth/facebook/callback',
            'is_enabled' => true,
        ]);
        \App\Models\FacebookOAuthSetting::clearCache();
    }

    // ============================================================
    // Migration backfill
    // ============================================================

    /**
     * Backfill ต้องดึง PSID จากอีเมล fb_{PSID}@thaiprompt.local มาใส่คอลัมน์
     */
    public function test_migration_backfills_psid_from_bot_email(): void
    {
        // แถวก่อน backfill — ไม่ส่ง facebook_psid = คอลัมน์เป็น null ตาม default
        $botUser = User::create([
            'name' => 'ลูกค้าบอท',
            'email' => 'fb_'.self::PSID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
        ]);

        $this->runPsidMigration();

        $this->assertSame(
            self::PSID,
            $botUser->fresh()->facebook_psid,
            'ต้องดึง PSID จากอีเมลมาใส่คอลัมน์ ไม่งั้น OAuth map กลับมาไม่เจอ'
        );
    }

    /**
     * Backfill ต้องเว้นบัญชีที่ OAuth สร้าง (fb_{ASID}@... — คนละ ID space)
     * และอีเมลที่ไม่ใช่ตัวเลขล้วน
     */
    public function test_migration_backfill_skips_oauth_created_and_non_numeric(): void
    {
        // บัญชีที่ FB OAuth สร้างเอง — อีเมลสูตรเดียวกันแต่ข้างในเป็น ASID
        $oauthUser = User::create([
            'name' => 'OAuth User',
            'email' => 'fb_'.self::ASID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
            'facebook_user_id' => self::ASID,
        ]);

        // อีเมลไม่ใช่ตัวเลขล้วน (ข้อมูลทดสอบ/แปลกปลอม)
        $weird = User::create([
            'name' => 'Weird',
            'email' => 'fb_ORPHAN@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
        ]);

        $this->runPsidMigration();

        $this->assertNull(
            $oauthUser->fresh()->facebook_psid,
            'บัญชีที่ OAuth สร้าง อีเมลข้างในเป็น ASID — ห้ามเอาไปใส่คอลัมน์ PSID'
        );
        $this->assertNull(
            $weird->fresh()->facebook_psid,
            'PSID เป็นตัวเลขล้วนเท่านั้น — ค่าอื่นห้าม backfill'
        );
    }

    // ============================================================
    // OAuth callback → match บัญชีบอท
    // ============================================================

    /**
     * เคสหลัก: ลูกค้าเคยดูดวงทาง Messenger → กด FB login
     * ต้องเข้าบัญชีเดิม ไม่ใช่ได้บัญชีเปล่าใบใหม่
     */
    public function test_oauth_matches_bot_account_via_ids_for_pages(): void
    {
        $botUser = $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($botUser->id, $matched->id, 'ต้องได้บัญชีที่บอทสมัครให้ ไม่ใช่บัญชีใหม่');
        $this->assertSame(self::ASID, $matched->fresh()->facebook_user_id, 'ต้อง link ASID ให้บัญชีเดิม');
        $this->assertSame(1, User::count(), 'ห้ามสร้างบัญชีซ้ำ');
    }

    /**
     * 🔑 (2026-07-28) คำตอบจริงของ Facebook คือ {"data":[{"id":"<PSID>"}]} — **ไม่มี page**
     *
     * ขอ fields=id,page ไม่ได้ Facebook ต้องการสิทธิ์ pages_read_engagement ซึ่ง
     * app token ไม่มี → เทสต์เดิมทุกตัว fake โดยใส่ page เข้าไปเอง จึงเขียวมาตลอด
     * ทั้งที่ prod ล้ม 100% (โค้ดกรอง $row['page']['id'] ที่ไม่มีวันมี)
     */
    public function test_oauth_matches_when_facebook_returns_psid_without_page_field(): void
    {
        $botUser = $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => self::PSID],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($botUser->id, $matched->id, 'ต้อง match บัญชีเดิมแม้ Facebook ไม่ส่ง page มาด้วย');
        $this->assertSame(1, User::count(), 'ห้ามสร้างบัญชีซ้ำ');
    }

    /**
     * 🔑 ต้องยิงด้วย **App Access Token** (app_id|app_secret) เท่านั้น
     *
     * เดิมส่ง page token → Facebook ตอบ "(#100) Invalid Access Token used" ทุกครั้ง
     * = ขั้น map ASID→PSID ล้มเหลว 100% ตั้งแต่วันแรก ไม่มีใครเคย match ได้เลย
     */
    public function test_ids_for_pages_uses_app_access_token_not_page_token(): void
    {
        $this->createBotUser();
        $this->fakeIdsForPages([['id' => self::PSID]]);

        $this->callFindOrCreateUser($this->socialiteUser());

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'ids_for_pages')) {
                return true; // ไม่ใช่ call ที่สนใจ
            }

            $token = $request['access_token'] ?? '';

            $this->assertSame(
                self::APP_ID.'|'.self::APP_SECRET,
                $token,
                'ต้องใช้ App Access Token — page token จะได้ (#100) Invalid Access Token'
            );
            $this->assertStringNotContainsString('test-page-token', $token);

            return true;
        });
    }

    /**
     * มีหลาย PSID ตอบกลับมาและไม่มี page ให้กรอง → ต้องเลือกใบที่ระบบเรารู้จัก
     * ห้ามหยิบมั่ว เพราะจะพาลูกค้าเข้าบัญชีของคนอื่น
     */
    public function test_picks_the_psid_that_exists_in_our_database(): void
    {
        $botUser = $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => '1111111111111111'],   // เพจอื่นของธุรกิจเดียวกัน
            ['id' => self::PSID],           // ของเพจเรา
            ['id' => '2222222222222222'],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($botUser->id, $matched->id, 'ต้องเลือก PSID ที่ตรงกับบัญชีในระบบเรา');
    }

    /**
     * หลาย PSID + ไม่มีใบไหนอยู่ในระบบเรา → ห้ามเดา ให้สร้างบัญชีใหม่ตามปกติ
     */
    public function test_does_not_guess_when_multiple_unknown_psids(): void
    {
        $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => '1111111111111111'],
            ['id' => '2222222222222222'],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame(2, User::count(), 'เดาไม่ได้ → สร้างใหม่ ห้ามจับคู่มั่ว');
        $this->assertNotSame('fb_'.self::PSID.'@thaiprompt.local', $matched->email);
    }

    /**
     * แถวที่ยังไม่มีค่าในคอลัมน์ (เกิดช่วงคาบเกี่ยว deploy) → match ผ่านอีเมล
     * แล้ว backfill คอลัมน์ให้ครั้งหน้าเจอตั้งแต่ขั้นแรก
     */
    public function test_oauth_matches_via_email_fallback_and_backfills_psid_column(): void
    {
        $botUser = $this->createBotUser(withPsidColumn: false);
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($botUser->id, $matched->id);
        $this->assertSame(self::PSID, $matched->fresh()->facebook_psid, 'ต้อง backfill คอลัมน์หลัง match ผ่านอีเมล');
    }

    /**
     * Graph API ล่ม → ห้าม 500 ใส่ลูกค้า — fallback สร้างบัญชีใหม่ตาม flow เดิม
     */
    public function test_oauth_falls_back_to_new_account_when_graph_api_fails(): void
    {
        $this->createBotUser();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame(2, User::count(), 'API ล่ม → สร้างใหม่ (พฤติกรรมเดิม) ไม่ crash');
        $this->assertNotSame(
            'fb_'.self::PSID.'@thaiprompt.local',
            $matched->email,
            'ห้ามเดา match โดยไม่มีคำตอบจาก Graph API'
        );
    }

    /**
     * ids_for_pages คืน PSID ของเพจอื่น → ไม่ใช่ลูกค้าเพจเรา ห้าม match
     */
    public function test_oauth_ignores_psid_of_other_pages(): void
    {
        $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => 'another-page-id', 'name' => 'เพจอื่น']],
        ]);

        $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame(2, User::count(), 'PSID ของเพจอื่น ห้ามเอามา match บัญชีเพจเรา');
    }

    /**
     * ลูกค้าใหม่ (map PSID ได้ แต่ยังไม่มีบัญชีบอท) → บัญชีใหม่ต้องเก็บ PSID
     * เพื่อให้บอทหาเจอ ไม่สร้างซ้ำตอนลูกค้ามาดูดวงทีหลัง
     */
    public function test_new_oauth_account_stores_resolved_psid(): void
    {
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $created = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame(self::PSID, $created->facebook_psid);
    }

    // ============================================================
    // Reconcile — เติม PSID ให้บัญชีที่ match ขั้น 1/2 (กันบัญชีซ้ำถาวร)
    // ============================================================

    /**
     * ลูกค้ามีบัญชีเว็บอยู่แล้ว (อีเมลตรงกับ FB) → step 2 match
     * ต้องเติม PSID ให้ด้วย ไม่งั้นบอทจะสร้างบัญชีซ้ำตอนมาดูดวงทีหลัง
     */
    public function test_oauth_email_match_backfills_psid_via_reconcile(): void
    {
        $existing = User::create([
            'name' => 'Web User',
            'email' => 'somchai@example.com',
            'password' => bcrypt(str()->random(20)),
        ]);
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser('somchai@example.com'));

        $this->assertSame($existing->id, $matched->id);
        $this->assertSame(
            self::PSID,
            $matched->fresh()->facebook_psid,
            'step 2 ต้องเติม PSID ด้วย — ไม่งั้นบอทหาบัญชีนี้ไม่เจอแล้วสร้างซ้ำ'
        );
    }

    /**
     * บัญชีที่เคย link แล้วแต่ไม่มี PSID (เกิดตอน Graph ล่ม) → login ครั้งถัดไป
     * step 1 match ต้องลอง map ใหม่ ไม่ใช่ปล่อยให้ไร้ PSID ตลอดกาล
     */
    public function test_oauth_step1_match_reconciles_missing_psid(): void
    {
        $linked = User::create([
            'name' => 'Linked User',
            'email' => 'linked@example.com',
            'password' => bcrypt(str()->random(20)),
            'facebook_user_id' => self::ASID,
        ]);
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($linked->id, $matched->id);
        $this->assertSame(
            self::PSID,
            $matched->fresh()->facebook_psid,
            'step 1 ต้อง retry map PSID — self-heal เคส Graph ล่มตอน login ครั้งแรก'
        );
    }

    /**
     * ถ้า PSID ชี้ไปบัญชีบอทใบอื่น (บัญชีซ้ำเกิดแล้ว) — ห้ามแย่ง PSID มา
     * เพราะวอลเลต/คอมสะสมอยู่ใบโน้น ต้องรวมบัญชีด้วยมือเท่านั้น
     */
    public function test_reconcile_never_steals_psid_from_existing_bot_account(): void
    {
        $duplicate = User::create([
            'name' => 'Duplicate',
            'email' => 'dup@example.com',
            'password' => bcrypt(str()->random(20)),
            'facebook_user_id' => self::ASID,
        ]);
        $botUser = $this->createBotUser();
        $this->fakeIdsForPages([
            ['id' => self::PSID, 'page' => ['id' => self::PAGE_ID, 'name' => 'เพจดูดวง']],
        ]);

        $matched = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($duplicate->id, $matched->id, 'step 1 ยัง match บัญชีซ้ำตามเดิม');
        $this->assertNull(
            $matched->fresh()->facebook_psid,
            'ห้ามย้าย PSID มาบัญชีซ้ำ — บัญชีบอทต้องคงเป็นเจ้าของ'
        );
        $this->assertSame(self::PSID, $botUser->fresh()->facebook_psid);
    }

    // ============================================================
    // ฝั่งบอท (FortuneAffiliateService)
    // ============================================================

    /**
     * id ที่ไม่ใช่ตัวเลขล้วน (reading เก่า platform=null default เป็น facebook)
     * ห้ามหลุดเข้า unique column facebook_psid
     */
    public function test_bot_does_not_write_non_numeric_id_into_psid_column(): void
    {
        $svc = new FortuneAffiliateService(FortuneTellingSetting::getSettings());
        $m = new \ReflectionMethod($svc, 'createUserFromPlatform');
        $m->setAccessible(true);

        $user = $m->invoke($svc, 'web-session-xyz', 'facebook', ['name' => 'ลูกค้าเว็บ'], null);

        $this->assertNull($user->facebook_psid, 'id ไม่ใช่ตัวเลขล้วน = ไม่ใช่ PSID ห้ามเก็บ');
        $this->assertSame('fb_web-session-xyz@thaiprompt.local', $user->email, 'สูตรอีเมลต้องคงเดิม');
    }

    /**
     * บอทสร้างบัญชีลูกค้า FB → ต้องเก็บ PSID เป็นคอลัมน์ ไม่ใช่แค่ฝังในอีเมล
     */
    public function test_bot_stores_psid_when_creating_facebook_account(): void
    {
        $svc = new FortuneAffiliateService(FortuneTellingSetting::getSettings());
        $m = new \ReflectionMethod($svc, 'createUserFromPlatform');
        $m->setAccessible(true);

        $user = $m->invoke($svc, self::PSID, 'facebook', ['name' => 'ลูกค้า FB'], null);

        $this->assertSame(self::PSID, $user->facebook_psid);
    }

    /**
     * ลูกค้า login เว็บก่อน (OAuth สร้างบัญชี + เก็บ PSID) แล้วค่อยมาดูดวง
     * → บอทต้องหาบัญชีนั้นเจอ ไม่สร้างซ้ำอีกใบ
     */
    public function test_bot_finds_oauth_created_account_by_psid(): void
    {
        $oauthUser = User::create([
            'name' => 'OAuth First',
            'email' => 'real-email@example.com',
            'password' => bcrypt(str()->random(20)),
            'facebook_user_id' => self::ASID,
            'facebook_psid' => self::PSID,
        ]);

        $svc = new FortuneAffiliateService(FortuneTellingSetting::getSettings());
        $m = new \ReflectionMethod($svc, 'findExistingUser');
        $m->setAccessible(true);

        $found = $m->invoke($svc, self::PSID, 'facebook', new \App\Models\FortuneReading);

        $this->assertNotNull($found, 'ต้องหาบัญชีที่ OAuth สร้างไว้เจอจาก PSID');
        $this->assertSame($oauthUser->id, $found->id);
    }

    /**
     * บัญชี OAuth ที่ FB ไม่ส่งอีเมลมา ต้องใช้ namespace แยก (fboauth_)
     * — ห้ามปนกับ fb_{PSID}@ ของบอท เพราะข้างในเป็น ASID คนละ ID space
     */
    public function test_new_oauth_account_without_email_uses_fboauth_namespace(): void
    {
        // Graph ตอบสำเร็จแต่ไม่มี mapping (ลูกค้าไม่เคยคุยกับเพจ)
        $this->fakeIdsForPages([]);

        $created = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame(
            'fboauth_'.self::ASID.'@thaiprompt.local',
            $created->email,
            'ASID ต้องอยู่ namespace แยกจาก PSID — สูตร fb_ สงวนให้บัญชีบอท'
        );
        $this->assertNull($created->facebook_psid);
    }

    // ============================================================
    // คำสั่งแชท "แชร์" (FortuneConversationService — ใช้ทั้ง LINE และ FB)
    // ============================================================

    /**
     * ลูกค้า FB พิมพ์ "แชร์" → helper ค้นบัญชีต้องหาเจอจาก PSID
     * (เดิมค้นเฉพาะแบบ LINE — ลูกค้า FB หาบัญชีตัวเองไม่เจอ)
     */
    public function test_share_lookup_finds_facebook_customer_by_psid(): void
    {
        $botUser = $this->createBotUser();

        $svc = new \App\Services\FortuneConversationService(FortuneTellingSetting::getSettings());
        $m = new \ReflectionMethod($svc, 'findUserByPlatformId');
        $m->setAccessible(true);

        $found = $m->invoke($svc, self::PSID);

        $this->assertNotNull($found, 'ต้องหาบัญชีลูกค้า FB เจอจาก PSID');
        $this->assertSame($botUser->id, $found->id);
    }

    /**
     * ลูกค้า FB จ่ายแล้วแต่ยังไม่มีบัญชี พิมพ์ "แชร์" → บัญชีที่สร้างต้องเป็น
     * identity แบบ FB (fb_ email + facebook_psid) ไม่ใช่ยัด PSID ใส่ line_user_id
     */
    public function test_share_request_from_facebook_creates_facebook_identity_account(): void
    {
        \App\Models\FortuneReading::create([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => 'การงานจะเป็นอย่างไร',
            'is_paid' => true,
        ]);

        $svc = (new \App\Services\FortuneConversationService(FortuneTellingSetting::getSettings()))
            ->setPlatform('facebook');
        $m = new \ReflectionMethod($svc, 'handleShareRequest');
        $m->setAccessible(true);
        $m->invoke($svc, self::PSID);

        $user = User::where('email', 'fb_'.self::PSID.'@thaiprompt.local')->first();
        $this->assertNotNull($user, 'ต้องสร้างบัญชีด้วยสูตรอีเมลฝั่ง FB ไม่ใช่ line_');
        $this->assertSame(self::PSID, $user->facebook_psid);
        $this->assertNull($user->line_user_id, 'ห้ามยัด PSID ใส่ line_user_id — identity คนละ platform');
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * สร้างบัญชีแบบที่บอทสมัครให้ (email fb_{PSID}@thaiprompt.local)
     */
    private function createBotUser(bool $withPsidColumn = true): User
    {
        return User::create([
            'name' => 'ลูกค้าบอท',
            'email' => 'fb_'.self::PSID.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
            'facebook_psid' => $withPsidColumn ? self::PSID : null,
        ]);
    }

    /**
     * Fake คำตอบ Graph API ids_for_pages
     */
    private function fakeIdsForPages(array $data): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => $data], 200),
        ]);
    }

    /**
     * Socialite user จำลอง (ข้อมูลที่ FB OAuth ส่งกลับ)
     */
    private function socialiteUser(?string $email = null): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => self::ASID,
            'name' => 'สมชาย ทดสอบ',
            'email' => $email,
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    }

    /**
     * เรียก findOrCreateUser ผ่าน reflection (เป็น protected)
     */
    private function callFindOrCreateUser(SocialiteUser $fbUser): User
    {
        $controller = new FacebookLoginController;
        $m = new \ReflectionMethod($controller, 'findOrCreateUser');
        $m->setAccessible(true);

        return $m->invoke($controller, $fbUser);
    }

    /**
     * รัน migration เพิ่ม facebook_psid ซ้ำ — idempotent ด้วย SafeMigration
     * (คอลัมน์/index มีแล้วจะข้าม แต่ backfill ทำงานกับแถว whereNull เสมอ)
     */
    private function runPsidMigration(): void
    {
        $migration = include database_path('migrations/2026_07_16_100000_add_facebook_psid_to_users_table.php');
        $migration->up();
    }
}
