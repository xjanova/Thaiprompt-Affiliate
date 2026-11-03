<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\FacebookLoginController;
use App\Models\FacebookOAuthSetting;
use App\Models\FortuneCommission;
use App\Models\FortuneCustomerPersona;
use App\Models\FortuneMessengerPsid;
use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneAffiliateService;
use App\Services\FortuneCommissionService;
use App\Services\FortuneWebLinkService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Concerns\BuildsJuntraAffiliateWorld;
use Tests\TestCase;

/**
 * 🪪 (2026-11-03) T14 — ตัวตนและสายงานของลูกค้า FB ที่ย้ายไปเว็บจันทรา (FB web-only)
 *
 * กรณีตัวตน (identity) ของ FortuneWebMigrationLineageTest:
 *   - PSID ใหม่ได้ user เดียว (กดซ้ำ/ชน unique ไม่เกิดใบที่สอง) และไม่ต่อสายงานที่ gateway (M7)
 *   - (รีวิว) ไม่มี session alias แล้ว: PSID ที่ไม่รู้จัก = บัญชีใหม่เสมอ (session ไม่ใช่หลักฐานว่าเป็นคนเดียวกัน)
 *     และเบราว์เซอร์ที่ล็อกอินเป็นลูกค้าคนอื่นอยู่ต้องกดยืนยันสลับบัญชีเอง
 *   - บัญชีเดิมที่หาเจอ → ผูก PSID ลงตาราง alias พร้อมเพจ (เติมเพจให้แถว backfill)
 *   - FB OAuth + hint wf_psid = อ่อน: ไม่ผูก ASID เข้าบัญชีของ PSID ใน session (กันยึดบัญชี) แค่ไม่ต่อสายงานตอนสมัคร
 *   - PSID เพจหลักที่ Graph ยืนยัน → ลงตาราง alias ของบัญชีที่ผูก FB แล้ว (ไม่เกิดบัญชีใบที่สอง)
 *   - findByMessengerPsid ค้นตาราง alias · findExistingUser ขั้น 3 เชื่อเฉพาะบิล facebook (LINE คงเดิม)
 *   - migration backfill alias + ชื่อ 'User'
 *
 * กรณีสายงานของบิลเว็บ (T15 — JuntraAffiliateService):
 *   - parity: บิล FB static กับบิลจันทราของสมาชิกคนเดียวกันจ่าย L1/L2 คนเดียวกัน (M3)
 *   - บิลเว็บ+ค่าแนะนำติดป้ายสาขาต้นทาง / page_code (M6)
 *   - ผู้ใช้ Thaiprompt ที่ถูกบัญชีจันทราอื่นผูก → 409 ไม่สร้างเงา · release แล้ว relink ชี้แถวเดิม (M4)
 *   - ส่งซ้ำจ่ายครั้งเดียว · void ดึงคืนทุกชั้น · ผู้เชิญจาก FortuneReferral ของ PSID/alias (M7)
 *
 * 🔁 (รีวิว) ทุกข้อข้างบน = ธง facebook_web_only เปิด (setUp) · ชุด "ธงปิด" ท้ายไฟล์พิสูจน์ว่าปิดแล้วกลับเป็นพฤติกรรม e2a9e0868
 *
 * @group security
 */
class FortuneWebMigrationLineageTest extends TestCase
{
    use BuildsJuntraAffiliateWorld;
    use RefreshDatabase;

    private const PSID_A = '24680135792468013';

    private const PSID_B = '13579246801357924';

    private const PSID_C = '11223344556677889';

    private const ASID = '5550001112223334';

    private const SSO = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/redirect';

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraAffiliateWorld();
        // Passport::actingAsClient สลับ guard หลักไป api-oauth — เส้นเว็บ (gateway/OAuth) ใช้ guard web
        $this->app['auth']->shouldUse('web');
        $this->withoutVite();
        Cache::flush();
        Http::fake(['*' => Http::response([], 200)]);

        $this->settings(['enable_web_fortune_button' => true, 'facebook_web_only' => true]);
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง static memo ก่อน/หลัง)
     */
    private function settings(array $override): void
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge(['web_fortune_sso_url' => self::SSO], $override))->save();
        FortuneTellingSetting::clearSettingsCache();
    }

    private function token(string $psid, ?int $pg = null): string
    {
        FortuneTellingSetting::clearSettingsCache();

        return (new FortuneWebLinkService)->generateToken('facebook', $psid, null, null, $pg);
    }

    private function service(): FortuneAffiliateService
    {
        FortuneTellingSetting::clearSettingsCache();

        return new FortuneAffiliateService;
    }

    /** จำลองการเปิดจากเบราว์เซอร์อีกเครื่อง (ไม่มี session/auth ติดมา) */
    private function freshBrowser(): void
    {
        Auth::logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    /** บัญชีลูกค้าบอทของ PSID (สร้างผ่านทางเดียวกับบอท) */
    private function botCustomer(string $psid, string $name = 'ลูกค้าเดิม'): User
    {
        return User::createBotProvisioned([
            'name' => $name,
            'email' => 'fb_'.$psid.'@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
            'facebook_psid' => $psid,
        ]);
    }

    // ============================================================
    // PSID ใหม่ → user เดียว
    // ============================================================

    public function test_new_psid_via_gateway_creates_exactly_one_user_without_member(): void
    {
        FortuneCustomerPersona::getOrCreate('facebook', self::PSID_A, 'มาลี ใจดี');
        $before = User::count();
        $membersBefore = MlmMember::count();

        $this->post('/web-fortune/go', ['tk' => $this->token(self::PSID_A, 7)])
            ->assertRedirect();
        $this->assertAuthenticated();
        $user = Auth::user()->fresh();

        $this->assertSame($before + 1, User::count());
        $this->assertSame(self::PSID_A, $user->facebook_psid);
        $this->assertSame('มาลี ใจดี', $user->name, 'ชื่อต้องมาจาก persona ไม่ใช่ User');
        $this->assertSame(7, (int) $user->fortune_origin_page_id);
        $this->assertSame($membersBefore, MlmMember::count(), 'gateway ต้องไม่ต่อสายงาน (ให้บิลแรกตัดสิน — M7)');
        $this->assertDatabaseHas('fortune_messenger_psids', [
            'user_id' => $user->id, 'psid' => self::PSID_A, 'fortune_page_id' => 7,
        ]);

        // ลิงก์ใหม่ของ PSID เดิม (เพจอื่น) จากเบราว์เซอร์ใหม่ → บัญชีเดิม สาขาต้นทางไม่ย้าย
        $this->freshBrowser();
        $this->post('/web-fortune/go', ['tk' => $this->token(self::PSID_A, 9)])->assertRedirect();
        $this->assertSame((int) $user->id, (int) Auth::id());
        $this->assertSame($before + 1, User::count());
        $this->assertSame(7, (int) $user->fresh()->fortune_origin_page_id, 'สาขาต้นทางเขียนครั้งเดียว');
    }

    public function test_name_falls_back_to_latest_reading_then_user(): void
    {
        FortuneReading::create([
            'facebook_user_id' => self::PSID_B,
            'facebook_user_name' => 'สมชาย ทดสอบ',
            'platform' => 'facebook',
            'reading_type' => 'deep',
            'questions' => [],
        ]);

        $named = $this->service()->resolveOrCreateWebUser('facebook', self::PSID_B, null);
        $plain = $this->service()->resolveOrCreateWebUser('facebook', self::PSID_C, null);

        $this->assertSame('สมชาย ทดสอบ', $named->name);
        $this->assertSame('User', $plain->name);

        // บัญชีเดิมที่ยังชื่อ User → ได้ชื่อจริงเมื่อหาได้ภายหลัง
        FortuneCustomerPersona::getOrCreate('facebook', self::PSID_C, 'วิไล ทดสอบ');
        $again = $this->service()->resolveOrCreateWebUser('facebook', self::PSID_C, null);
        $this->assertSame((int) $plain->id, (int) $again->id);
        $this->assertSame('วิไล ทดสอบ', $again->fresh()->name);
    }

    public function test_unique_violation_while_creating_refinds_the_winner(): void
    {
        // จำลองคำขอคู่แข่งที่สร้างบัญชีของ PSID เดียวกันเสร็จ "หลัง" เราค้นแล้วไม่เจอ (ช่องว่างของ race)
        //   → การค้นครั้งแรกมองไม่เห็นผู้ชนะ · การสร้างชน unique ของ facebook_psid · ค้นซ้ำต้องเจอผู้ชนะ
        $service = new class extends FortuneAffiliateService
        {
            public int $lookups = 0;

            public function findExistingUser(string $platformUserId, string $platform, ?FortuneReading $reading = null): ?User
            {
                return $this->lookups++ === 0 ? null : parent::findExistingUser($platformUserId, $platform, $reading);
            }
        };

        // กันการซ้อน: ถ้าไม่มี unique บน facebook_psid เทสต์นี้ไม่มีความหมาย
        $this->assertTrue(
            collect(DB::select("SHOW INDEX FROM users WHERE Column_name = 'facebook_psid' AND Non_unique = 0"))->isNotEmpty()
        );

        $winner = User::createBotProvisioned([
            'name' => 'ผู้ชนะ',
            'email' => 'winner-'.self::PSID_A.'@example.test',
            'password' => bcrypt('x'),
            'facebook_psid' => self::PSID_A,
        ]);
        $before = User::count();

        $user = $service->resolveOrCreateWebUser('facebook', self::PSID_A, null);

        $this->assertSame((int) $winner->id, (int) $user->id, 'ชน unique ต้องคืนบัญชีของผู้ชนะ');
        $this->assertSame($before, User::count(), 'ห้ามมีบัญชีใบที่สอง');
        $this->assertSame(2, $service->lookups);
    }

    public function test_unique_violation_propagates_when_nothing_can_be_refound(): void
    {
        $service = new class extends FortuneAffiliateService
        {
            protected function createUserFromPlatform(string $platformUserId, string $platform, array $profile, ?FortuneReading $reading = null): User
            {
                throw new UniqueConstraintViolationException('mysql', 'insert', [], new \RuntimeException('dup'));
            }
        };

        $this->expectException(UniqueConstraintViolationException::class);
        $service->resolveOrCreateWebUser('facebook', self::PSID_A, null);
    }

    // ============================================================
    // alias ข้ามสาขา (M1)
    // ============================================================

    public function test_second_branch_psid_in_a_logged_in_browser_is_never_silently_aliased(): void
    {
        // ลูกค้าเดิมที่มีสายงานใต้ผู้แนะนำ S แล้ว (จ่ายบิลบน FB เพจหลัก)
        [$sponsorUser, $sponsor] = $this->activeMember('SPON0001');
        $customer = $this->botCustomer(self::PSID_A);
        $member = $this->member($customer, $sponsor, 'CUST0001');

        // ลิงก์จากเพจหลัก (pg = null) → ล็อกอินเป็นลูกค้า + session hint
        $this->post('/web-fortune/go', ['tk' => $this->token(self::PSID_A)])->assertRedirect();
        $this->assertSame((int) $customer->id, (int) Auth::id());
        $before = User::count();

        // ลิงก์ของ PSID อื่นจากเพจสาขา 5 ในเบราว์เซอร์เดิม (เครื่องที่ใช้ร่วมกัน / ลิงก์ที่ถูกส่งต่อ)
        //   → ไม่กิน token ไม่สลับบัญชีเงียบ ๆ แต่แสดงหน้ายืนยัน
        $token = $this->token(self::PSID_B, 5);
        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertOk()
            ->assertSee('confirm_switch', false)
            ->assertSee($customer->name);
        $this->assertSame((int) $customer->id, (int) Auth::id(), 'ยังไม่สลับบัญชี');
        $this->assertSame($before, User::count());

        // กดยืนยัน → บัญชีใหม่ของ PSID นั้น — ห้ามผูก PSID เข้าบัญชีที่ล็อกอินค้างอยู่
        $this->post('/web-fortune/go', ['tk' => $token, 'confirm_switch' => '1'])->assertRedirect();

        $other = User::findByMessengerPsid(self::PSID_B);
        $this->assertNotNull($other);
        $this->assertNotSame((int) $customer->id, (int) $other->id);
        $this->assertSame((int) $other->id, (int) Auth::id());
        $this->assertSame($before + 1, User::count());
        $this->assertDatabaseMissing('fortune_messenger_psids', ['user_id' => $customer->id, 'psid' => self::PSID_B]);
        $this->assertSame((int) $sponsor->id, (int) $member->fresh()->unilevel_sponsor_id, 'ผู้แนะนำต้องไม่เปลี่ยน (M2)');
    }

    public function test_existing_user_gets_its_psid_page_recorded_in_alias_table(): void
    {
        // แถว backfill ไม่มีเพจ → ลิงก์จากสาขา 5 เติมเพจให้ · บัญชีที่บอทสร้างก่อนมีตาราง alias ก็ได้แถว
        $customer = $this->botCustomer(self::PSID_A);
        FortuneMessengerPsid::query()->where('psid', self::PSID_A)->delete();
        FortuneMessengerPsid::query()->create([
            'user_id' => $customer->id, 'psid' => self::PSID_A, 'fortune_page_id' => null, 'source' => 'backfill',
        ]);
        $plain = $this->botCustomer(self::PSID_C, 'ไม่มีแถว');
        FortuneMessengerPsid::query()->where('psid', self::PSID_C)->delete();

        $this->assertSame((int) $customer->id, (int) $this->service()->resolveOrCreateWebUser('facebook', self::PSID_A, 5)->id);
        $this->assertSame((int) $plain->id, (int) $this->service()->resolveOrCreateWebUser('facebook', self::PSID_C, 7)->id);

        $this->assertDatabaseHas('fortune_messenger_psids', ['user_id' => $customer->id, 'psid' => self::PSID_A, 'fortune_page_id' => 5]);
        $this->assertDatabaseHas('fortune_messenger_psids', ['user_id' => $plain->id, 'psid' => self::PSID_C, 'fortune_page_id' => 7]);
    }

    public function test_alias_requires_session_hint_owned_by_logged_in_user(): void
    {
        $customer = $this->botCustomer(self::PSID_A);
        $stranger = $this->botCustomer(self::PSID_C, 'คนอื่น');

        // ล็อกอินเป็น customer แต่ hint ใน session เป็น PSID ของคนอื่น → ไม่ alias
        $user = $this->actingAs($customer)
            ->withSession(['wf_psid' => self::PSID_C, 'wf_page' => null])
            ->app->make(FortuneAffiliateService::class)
            ->resolveOrCreateWebUser('facebook', self::PSID_B, 5);

        $this->assertNotSame((int) $customer->id, (int) $user->id);
        $this->assertNotSame((int) $stranger->id, (int) $user->id);
        $this->assertSame(self::PSID_B, $user->facebook_psid);
    }

    public function test_alias_never_binds_to_a_staff_account(): void
    {
        $staff = $this->botCustomer(self::PSID_A);
        $staff->forceFill(['role' => 'admin'])->save();

        $user = $this->actingAs($staff->fresh())
            ->withSession(['wf_psid' => self::PSID_A, 'wf_page' => null])
            ->app->make(FortuneAffiliateService::class)
            ->resolveOrCreateWebUser('facebook', self::PSID_B, 5);

        $this->assertNotSame((int) $staff->id, (int) $user->id);
    }

    // ============================================================
    // FB OAuth + hint (M5)
    // ============================================================

    public function test_facebook_oauth_with_wf_psid_hint_never_binds_the_session_owner(): void
    {
        $this->oauthSettings();
        // ids_for_pages หา PSID ไม่ได้ (เพจสาขา/Business ต่าง)
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);

        $customer = $this->botCustomer(self::PSID_B);
        FortuneMessengerPsid::bind($customer->id, self::PSID_B, 5, 'web_gateway');

        // ลำดับโจมตี: เริ่ม OAuth ก่อน แล้วใช้ลิงก์ของเจ้าของ PSID (session = เจ้าของ + hint) แล้วค่อยจบ callback
        $this->actingAs($customer)->withSession(['wf_psid' => self::PSID_B, 'wf_page' => 5]);
        $usersBefore = User::count();
        $membersBefore = MlmMember::count();

        $result = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertNotSame((int) $customer->id, (int) $result->id, 'session ไม่ใช่หลักฐานความเป็นเจ้าของ FB');
        $this->assertNull($customer->fresh()->facebook_user_id, 'ห้ามผูก ASID ของคนถือ session เข้าบัญชีเจ้าของลิงก์');
        $this->assertSame($usersBefore + 1, User::count());
        $this->assertSame($membersBefore, MlmMember::count(), 'มี hint → ไม่ต่อสายงานใต้ผู้แนะนำเริ่มต้น');
    }

    public function test_expired_branch_link_hides_facebook_button_and_oauth_skips_eager_member(): void
    {
        $this->oauthSettings();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $this->botCustomer(self::PSID_B);

        // ลิงก์สาขาหมดอายุ (ลายเซ็นถูก) เปิดจากเบราว์เซอร์ที่ไม่มี session
        $this->travel(-2)->days();
        $token = $this->token(self::PSID_B, 5);
        $this->travelBack();

        $this->get(route('web-fortune.expired', ['tk' => $token, 'reason' => 'expired']))
            ->assertOk()
            ->assertDontSee('เข้าด้วย Facebook')
            ->assertSee('ส่งลิงก์ใหม่เข้าแชท')
            ->assertSessionHas('wf_psid_weak', self::PSID_B);

        // ลูกค้าไปล็อกอิน Facebook เอง (ไม่ผ่านปุ่ม) → บัญชีใหม่แต่ไม่ล็อกสายงานใต้ผู้แนะนำเริ่มต้น
        $membersBefore = MlmMember::count();
        $this->withSession(['wf_psid_weak' => self::PSID_B]);
        $this->callFindOrCreateUser($this->socialiteUser());
        $this->assertSame($membersBefore, MlmMember::count());
    }

    public function test_oauth_reconcile_binds_graph_verified_main_page_psid_even_when_column_is_set(): void
    {
        $this->oauthSettings();
        $main = FortunePage::create([
            'code' => 'MAIN', 'name' => 'เพจหลัก', 'platform' => 'facebook',
            'external_page_id' => 'pg-main', 'is_active' => true, 'is_default' => true,
        ]);
        FortuneTellingSetting::getSettings()->forceFill(['facebook_page_id' => 'pg-main'])->save();
        FortuneTellingSetting::clearSettingsCache();

        // บัญชีเกิดจากลิงก์สาขา (PSID_B ในคอลัมน์) และผูก FB แล้ว
        $customer = $this->botCustomer(self::PSID_B);
        $customer->forceFill(['facebook_user_id' => self::ASID])->save();

        // Graph ยืนยันว่า ASID นี้มี PSID เพจหลัก = PSID_C (ยังไม่มีเจ้าของ)
        // setUp fake '*' ไว้ก่อน (stub แรกที่ match ชนะ) → เริ่ม factory ใหม่ให้ Graph ตอบ PSID จริง
        Http::swap(new \Illuminate\Http\Client\Factory(app('events')));
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [['id' => self::PSID_C]]], 200)]);

        $result = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame((int) $customer->id, (int) $result->id);
        $this->assertDatabaseHas('fortune_messenger_psids', [
            'user_id' => $customer->id, 'psid' => self::PSID_C, 'fortune_page_id' => $main->id, 'source' => 'oauth_hint',
        ]);
        $this->assertSame(self::PSID_B, (string) $customer->fresh()->facebook_psid, 'คอลัมน์หลักไม่ถูกเขียนทับ');

        // ลิงก์จากเพจหลักครั้งหน้า (เบราว์เซอร์ใหม่) → บัญชีเดิม ไม่ใช่ใบที่สอง
        $this->assertSame((int) $customer->id, (int) $this->service()->resolveOrCreateWebUser('facebook', self::PSID_C, $main->id)->id);
    }

    public function test_facebook_oauth_with_hint_but_other_session_creates_user_without_eager_member(): void
    {
        $this->oauthSettings();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);

        $this->botCustomer(self::PSID_B);
        // hint ค้างอยู่แต่ไม่ได้ล็อกอินเป็นเจ้าของ PSID (เช่น เครื่องที่คนอื่นใช้ต่อ) → ไม่ผูก
        $this->withSession(['wf_psid' => self::PSID_B, 'wf_page' => 5]);
        $usersBefore = User::count();
        $membersBefore = MlmMember::count();

        $result = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($usersBefore + 1, User::count());
        $this->assertNotSame(self::PSID_B, (string) $result->facebook_psid);
        $this->assertSame($membersBefore, MlmMember::count(), 'มี hint → ข้าม eager createMlmMember');
    }

    public function test_facebook_oauth_without_hint_keeps_eager_member_enrolment(): void
    {
        $this->oauthSettings();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $membersBefore = MlmMember::count();

        $result = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($membersBefore + 1, MlmMember::count(), 'ไม่มี hint = พฤติกรรมเดิม (ต่อสายงานตอนสมัคร)');
        $this->assertSame(
            (int) $this->rootMember->id,
            (int) MlmMember::where('user_id', $result->id)->value('unilevel_sponsor_id')
        );
    }

    // ============================================================
    // lookup
    // ============================================================

    public function test_find_existing_user_step3_trusts_only_facebook_readings_for_psid(): void
    {
        $lineOwner = User::factory()->create();
        FortuneReading::create([
            'user_id' => $lineOwner->id,
            'platform' => 'line',
            'platform_user_id' => self::PSID_A,
            // แถว LINE เก็บ id ไว้ในคอลัมน์ชื่อ facebook ด้วย (ดู LINE_MESSAGING_RULES)
            'facebook_user_id' => self::PSID_A,
            'reading_type' => 'deep',
            'questions' => [],
        ]);

        $this->assertNull(
            $this->service()->findExistingUser(self::PSID_A, 'facebook'),
            'บิล LINE ที่ id บังเอิญตรงต้องไม่ถูกใช้ล็อกอินบัญชี FB'
        );

        $fbOwner = User::factory()->create();
        FortuneReading::create([
            'user_id' => $fbOwner->id,
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID_A,
            'reading_type' => 'deep',
            'questions' => [],
        ]);
        $this->assertSame((int) $fbOwner->id, (int) $this->service()->findExistingUser(self::PSID_A, 'facebook')?->id);

        // LINE คงพฤติกรรมเดิม — ขั้น 3 ยังใช้บิล LINE ได้
        $lineId = 'U0123456789abcdef0123456789abcdef';
        $lineUser = User::factory()->create();
        FortuneReading::create([
            'user_id' => $lineUser->id,
            'platform' => 'line',
            'platform_user_id' => $lineId,
            'facebook_user_id' => $lineId,
            'reading_type' => 'deep',
            'questions' => [],
        ]);
        $this->assertSame((int) $lineUser->id, (int) $this->service()->findExistingUser($lineId, 'line')?->id);
    }

    public function test_bind_refuses_psid_owned_by_someone_else_and_non_numeric(): void
    {
        $a = $this->botCustomer(self::PSID_A);
        $b = $this->botCustomer(self::PSID_C, 'คนที่สอง');

        $this->assertTrue(FortuneMessengerPsid::bind($a->id, self::PSID_B, 5, 'session_alias'));
        $this->assertTrue(FortuneMessengerPsid::bind($a->id, self::PSID_B, 5, 'session_alias'), 'idempotent');
        $this->assertFalse(FortuneMessengerPsid::bind($b->id, self::PSID_B, 6, 'session_alias'), 'ห้ามแย่ง PSID');
        $this->assertFalse(FortuneMessengerPsid::bind($a->id, 'U0123456789abcdef', null, 'x'));
        $this->assertSame((int) $a->id, (int) User::findByMessengerPsid(self::PSID_B)?->id);
    }

    /**
     * 🔁 migration เติมตาราง alias อย่างเดียว — ไม่เขียนทับ users.name แล้ว (ย้ายไป fortune:web-only-cutover)
     */
    public function test_migration_backfills_aliases_but_never_renames_users(): void
    {
        $user = $this->botCustomer(self::PSID_A, 'User');
        FortuneMessengerPsid::query()->delete();
        FortuneReading::create([
            'facebook_user_id' => self::PSID_A,
            'facebook_user_name' => 'นงลักษณ์ ทดสอบ',
            'platform' => 'facebook',
            'reading_type' => 'deep',
            'questions' => [],
        ]);

        $migration = include database_path('migrations/2026_11_03_140000_create_fortune_messenger_psids_table.php');
        $migration->up();
        $migration->up(); // รันซ้ำต้องไม่พัง/ไม่ซ้ำ

        $this->assertSame(1, FortuneMessengerPsid::where('psid', self::PSID_A)->count());
        $this->assertSame((int) $user->id, (int) FortuneMessengerPsid::where('psid', self::PSID_A)->value('user_id'));
        $this->assertSame('User', $user->fresh()->name, 'migration ห้ามเปลี่ยนชื่อผู้ใช้ (ย้อนกลับไม่ได้ และธงอาจยังปิด)');
        // แถว backfill = ค่าเดียวกับคอลัมน์ facebook_psid → ผลค้นหาเท่าเดิม
        $this->assertSame((int) $user->id, (int) User::findByMessengerPsid(self::PSID_A)?->id);
    }

    // ============================================================
    // T15 — สายงานของบิลเว็บจันทรา (JuntraAffiliateService)
    // ============================================================

    /**
     * M3 parity: บิล FB (static) กับบิลจันทราของสมาชิกคนเดียวกัน → L1/L2 เป็น user_id เดียวกัน
     */
    public function test_web_bill_pays_the_same_upline_as_a_fb_static_bill(): void
    {
        [$grandUser, $grand] = $this->activeMember('GRAND001');
        [$inviterUser, $inviter] = $this->activeMember('INVITE01', $grand);
        $customer = $this->botCustomer(self::PSID_A, 'ลูกค้าย้ายเว็บ');
        $customerMember = $this->member($customer, $inviter, 'CUST0001');

        // บิล FB แบบเดิม (static) ผ่านเครื่องแจกค่าแนะนำตัวเดียวกับบอท
        $fbBill = FortuneReading::create([
            'user_id' => $customer->id,
            'facebook_user_id' => self::PSID_A,
            'platform' => 'facebook',
            'reading_type' => 'deep',
            'questions' => [],
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'amount_paid' => 99,
            'paid_at' => now(),
        ]);
        FortuneTellingSetting::clearSettingsCache();
        app(FortuneCommissionService::class)->distributeCommissions($fbBill, $customerMember, FortuneTellingSetting::getSettings());

        // บิลเว็บของคนเดียวกัน (จันทราผูก Thaiprompt คนนี้แล้ว)
        $this->juntraBill(9101, 801, 99, $customer->id)->assertStatus(201);

        $fbUpline = FortuneCommission::where('fortune_reading_id', $fbBill->id)->orderBy('level')->pluck('user_id', 'level')->all();
        $webUpline = FortuneCommission::where('fortune_reading_id', $this->readingId(9101))->orderBy('level')->pluck('user_id', 'level')->all();

        $this->assertSame([1 => $inviterUser->id, 2 => $grandUser->id], array_map('intval', $fbUpline));
        $this->assertSame(array_map('intval', $fbUpline), array_map('intval', $webUpline), 'บิลเว็บต้องจ่ายสายเดียวกับบิล FB');
        $this->assertSame(1, MlmMember::where('user_id', $customer->id)->count(), 'ไม่สร้างตำแหน่งที่สอง');
        $this->assertSame($customerMember->id, (int) MlmMember::where('user_id', $customer->id)->value('id'));
    }

    /**
     * M6: ลูกค้าที่มาจากสาขา B → บิลเว็บและค่าแนะนำติดป้าย page=B · page_code ที่จันทราส่งมาชนะ
     */
    public function test_juntra_bill_and_commissions_are_stamped_with_the_origin_branch(): void
    {
        [$inviterUser, $inviter] = $this->activeMember('INVITE01');
        $pageB = $this->branch('branch-b');
        $pageC = $this->branch('branch-c');

        $customer = $this->botCustomer(self::PSID_B, 'ลูกค้าสาขาบี');
        $this->member($customer, $inviter, 'CUSTB001');
        // gateway จดสาขาต้นทางจาก token (pg) ไว้แล้ว
        User::whereKey($customer->id)->update(['fortune_origin_page_id' => $pageB->id]);

        $this->juntraBill(9201, 811, 99, $customer->id)
            ->assertStatus(201)
            ->assertJsonPath('data.fortune_page_id', $pageB->id);

        $readingId = $this->readingId(9201);
        $this->assertSame($pageB->id, (int) FortuneReading::find($readingId)->fortune_page_id);
        $this->assertGreaterThan(0, FortuneCommission::where('fortune_reading_id', $readingId)->count());
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $readingId)
            ->where(fn ($q) => $q->whereNull('fortune_page_id')->orWhere('fortune_page_id', '!=', $pageB->id))->count(),
            'ค่าแนะนำทุกแถวต้องอยู่สาขาเดียวกับบิล');

        // page_code จากจันทรา (สาขาของลิงก์รอบนี้) ชนะสาขาต้นทาง · สาขาต้นทางไม่ย้าย
        $this->juntraBill(9202, 811, 39, $customer->id, ['page_code' => 'branch-c'])->assertStatus(201);
        $this->assertSame($pageC->id, (int) FortuneReading::find($this->readingId(9202))->fortune_page_id);
        $this->assertSame($pageB->id, (int) User::whereKey($customer->id)->value('fortune_origin_page_id'));

        // page_code ที่ไม่รู้จักไม่ทำให้บิลล้ม — ตกไปสาขาต้นทาง
        $this->juntraBill(9203, 811, 39, $customer->id, ['page_code' => 'no-such-branch'])->assertStatus(201);
        $this->assertSame($pageB->id, (int) FortuneReading::find($this->readingId(9203))->fortune_page_id);

        // หน้าแอดมินสาขานับบิลเว็บแยกรายสาขา · บิลเว็บที่ไม่มีสาขาไม่ปนในคำเตือน "บิลหลงสาขา"
        $this->juntraBill(9204, 813, 99)->assertStatus(201); // ลูกค้าเริ่มบนเว็บเอง
        $orphansBefore = FortuneReading::whereNull('fortune_page_id')->where('reading_type', '!=', FortuneReading::READING_TYPE_JUNTRA)->count();
        $data = app(\App\Http\Controllers\Admin\FortunePagesController::class)->index()->getData();
        $this->assertSame(2, (int) $data['stats'][$pageB->id]->juntra_paid_bills);
        $this->assertEquals(138.0, (float) $data['stats'][$pageB->id]->juntra_revenue);
        $this->assertSame(1, (int) $data['stats'][$pageC->id]->juntra_paid_bills);
        $this->assertSame(1, (int) $data['webOnlyBills']);
        $this->assertSame($orphansBefore, (int) $data['orphanBills']);
    }

    public function test_page_code_on_ensure_account_sets_origin_once(): void
    {
        $pageB = $this->branch('branch-b');
        $pageC = $this->branch('branch-c');
        $customer = $this->botCustomer(self::PSID_C, 'ลูกค้าใหม่');

        $this->ensureJuntra(812, $customer->id, ['page_code' => 'branch-b'])->assertSuccessful();
        $this->assertSame($pageB->id, (int) User::whereKey($customer->id)->value('fortune_origin_page_id'));

        $this->ensureJuntra(812, $customer->id, ['page_code' => 'branch-c'])->assertSuccessful();
        $this->assertSame($pageB->id, (int) User::whereKey($customer->id)->value('fortune_origin_page_id'), 'เขียนครั้งเดียว');
        $this->assertNotNull($pageC->id);
    }

    /**
     * M4: ผู้ใช้ Thaiprompt ที่บัญชีจันทราอื่นผูกไว้ → 409 thaiprompt_taken ไม่สร้างเงา
     */
    public function test_thaiprompt_user_held_by_another_juntra_account_gets_409_not_a_shadow(): void
    {
        [$real] = $this->activeMember('REAL0001');
        JuntraAccount::create(['juntra_user_id' => 700, 'user_id' => $real->id, 'linked_via' => 'sso']);
        $usersBefore = User::count();
        $membersBefore = MlmMember::count();

        $this->juntraBill(9301, 821, 99, $real->id)
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'thaiprompt_taken');
        // จันทรายิงซ้ำ — ยังเป็น 409 และยังไม่สร้างอะไร
        $this->ensureJuntra(821, $real->id)->assertStatus(409)->assertJsonPath('reason_code', 'thaiprompt_taken');

        $this->assertSame($usersBefore, User::count(), 'ห้ามสร้างผู้ใช้เงา');
        $this->assertSame($membersBefore, MlmMember::count());
        $this->assertFalse(JuntraAccount::where('juntra_user_id', 821)->exists());
        $this->assertSame(0, FortuneReading::where('bill_reference', 'JW-9301')->count());
        $this->assertTrue(Cache::has("juntra:affiliate-taken-alert:821:{$real->id}"), 'แจ้งแอดมินแล้ว (และกันแจ้งซ้ำ)');

        // ลูกค้าจันทราที่ไม่ได้ผูก Thaiprompt ยังได้บัญชีเงาตามเดิม
        $this->juntraBill(9302, 822, 99)->assertStatus(201);
        $this->assertTrue(JuntraAccount::where('juntra_user_id', 822)->where('linked_via', 'auto')->exists());
    }

    /**
     * M4: จันทราลบบัญชี → release แล้วบัญชีใหม่ที่ผูกคนเดิม → ชี้แถวเดิม ไม่สร้างเงา สายเดิม
     */
    public function test_release_then_relink_repoints_the_mapping_without_a_shadow(): void
    {
        [$inviterUser, $inviter] = $this->activeMember('INVITE01');
        $customer = $this->botCustomer(self::PSID_A, 'ลูกค้าลบบัญชี');
        $member = $this->member($customer, $inviter, 'CUSTR001');

        $this->juntraBill(9401, 831, 99, $customer->id)->assertStatus(201);
        $accountId = JuntraAccount::where('juntra_user_id', 831)->value('id');

        $this->postJson('/api/v1/juntra/server/affiliate/accounts/831/release', ['reason' => 'ลูกค้าลบบัญชี'])
            ->assertOk()
            ->assertJsonPath('data.released', true)
            ->assertJsonPath('data.already_released', false);
        $this->postJson('/api/v1/juntra/server/affiliate/accounts/831/release')
            ->assertOk()
            ->assertJsonPath('data.already_released', true);
        $this->postJson('/api/v1/juntra/server/affiliate/accounts/999999/release')
            ->assertNotFound()
            ->assertJsonPath('reason_code', 'unknown_account');

        // บิลค้างส่งของบัญชีเก่ายังลงคนเดิม
        $this->juntraBill(9402, 831, 39)->assertStatus(201);
        $this->assertSame($customer->id, (int) FortuneReading::find($this->readingId(9402))->user_id);

        $usersBefore = User::count();
        $this->juntraBill(9403, 832, 99, $customer->id)->assertStatus(201);

        $account = JuntraAccount::findOrFail($accountId);
        $this->assertSame(832, (int) $account->juntra_user_id, 'แถวเดิมชี้ไปบัญชีจันทราใหม่');
        $this->assertSame(831, (int) $account->released_juntra_user_id);
        $this->assertNull($account->released_at);
        $this->assertSame($customer->id, (int) $account->user_id);
        $this->assertSame(1, JuntraAccount::where('user_id', $customer->id)->count());
        $this->assertSame($usersBefore, User::count(), 'ไม่สร้างเงา');
        $this->assertSame($member->id, (int) MlmMember::where('user_id', $customer->id)->value('id'));
        $this->assertSame($inviterUser->id, (int) FortuneCommission::where('fortune_reading_id', $this->readingId(9403))
            ->where('level', 1)->value('user_id'), 'ค่าแนะนำไปสายเดิม');

        // บิลค้างของบัญชีเก่าที่มาหลังชี้ใหม่แล้ว — ยังเป็นคนเดิม ไม่สร้างเงา
        $this->juntraBill(9404, 831, 39)->assertStatus(201);
        $this->assertSame($customer->id, (int) FortuneReading::find($this->readingId(9404))->user_id);
        $this->assertSame($usersBefore, User::count());
    }

    /** บัญชีจันทราใหม่ซื้อก่อนผูก (มีเงา) แล้วผูกคนที่ถูกปลด → รวมเงาเข้าบัญชีจริง */
    public function test_release_lets_a_shadow_account_merge_into_the_released_thaiprompt_user(): void
    {
        [$real] = $this->activeMember('REAL0001');
        JuntraAccount::create(['juntra_user_id' => 840, 'user_id' => $real->id, 'linked_via' => 'sso']);

        $this->juntraBill(9501, 841, 99)->assertStatus(201);
        $shadowId = (int) JuntraAccount::where('juntra_user_id', 841)->value('user_id');

        // ยังไม่ปลด — รวมไม่ได้ (พฤติกรรมเดิม: คงเงา)
        $this->ensureJuntra(841, $real->id)->assertOk()->assertJsonPath('data.linked_via', 'auto');

        $this->postJson('/api/v1/juntra/server/affiliate/accounts/840/release')->assertOk();
        JuntraAccount::where('juntra_user_id', 841)->update(['merge_failed_at' => null]);

        $this->ensureJuntra(841, $real->id)->assertOk()->assertJsonPath('data.linked_via', 'sso');

        $account = JuntraAccount::where('juntra_user_id', 841)->firstOrFail();
        $this->assertSame($real->id, (int) $account->user_id);
        $this->assertSame($shadowId, (int) $account->merged_from_user_id);
        $this->assertSame(840, (int) $account->released_juntra_user_id);
        $this->assertFalse(JuntraAccount::where('juntra_user_id', 840)->exists());
        $this->assertSame($real->id, (int) FortuneReading::find($this->readingId(9501))->user_id, 'บิลของเงาย้ายมาบัญชีจริง');
    }

    /** 🔐 (รีวิว) แถวที่ปลดแล้วเคยรวมเงาไว้ → ประวัติการรวมเดิมต้องไม่หายตอนถูกแทนที่ */
    public function test_replacing_a_released_row_keeps_its_old_merge_history(): void
    {
        [$real] = $this->activeMember('REAL0001');
        // บัญชีจันทราเก่า 850 เคยมีเงา (ผู้ใช้ $oldShadow) แล้วรวมเข้า $real ไปแล้ว
        $oldShadow = User::factory()->create();
        JuntraAccount::create([
            'juntra_user_id' => 850, 'user_id' => $real->id, 'linked_via' => 'sso',
            'merged_from_user_id' => $oldShadow->id, 'merged_at' => now()->subDay(),
        ]);
        $this->postJson('/api/v1/juntra/server/affiliate/accounts/850/release')->assertOk();

        // บัญชีจันทราใหม่ 851 ซื้อก่อนผูก (มีเงาใหม่) แล้วผูก $real → แทนแถว 850
        $this->juntraBill(9601, 851, 99)->assertStatus(201);
        $this->ensureJuntra(851, $real->id)->assertOk()->assertJsonPath('data.linked_via', 'sso');
        $this->assertFalse(JuntraAccount::where('juntra_user_id', 850)->exists());

        // ประวัติเงาเก่ายังอยู่ → ลิงก์เชิญเดิมของเงาเก่ายังพามาที่บัญชีจริง และ sweep ยังเก็บตกได้
        $this->assertDatabaseHas('juntra_account_merge_aliases', [
            'merged_from_user_id' => $oldShadow->id, 'user_id' => $real->id, 'juntra_user_id' => 850,
        ]);
        $this->assertSame($real->id, \App\Services\Juntra\JuntraAffiliateService::mergedIntoUserId($oldShadow->id));

        // ค่าแนะนำที่หลงเข้าเงาเก่าหลังรวม → sweep ย้ายไปบัญชีจริง
        $this->juntraBill(9602, 852, 99)->assertStatus(201);
        FortuneReading::whereKey($this->readingId(9602))->update(['user_id' => $oldShadow->id]);
        $this->artisan('juntra:sweep-merged-accounts')->assertSuccessful();
        $this->assertSame($real->id, (int) FortuneReading::find($this->readingId(9602))->user_id);
    }

    /** 🔐 (รีวิว) 409 thaiprompt_taken มีเครื่องมือแอดมิน: ปลดบัญชีเก่าด้วยคำสั่ง (ต้องมีเหตุผล + ยืนยัน) */
    public function test_admin_release_command_unblocks_thaiprompt_taken(): void
    {
        [$real] = $this->activeMember('REAL0001');
        JuntraAccount::create(['juntra_user_id' => 860, 'user_id' => $real->id, 'linked_via' => 'sso']);
        $this->juntraBill(9701, 861, 99, $real->id)->assertStatus(409);

        // ไม่มีเหตุผล = ไม่ทำ
        $this->artisan('juntra:affiliate-release', ['juntra_user_id' => 860])->assertFailed();
        // ไม่ยืนยัน = ไม่ทำ
        $this->artisan('juntra:affiliate-release', ['juntra_user_id' => 860, '--reason' => 'ลูกค้ายืนยันแล้ว'])
            ->expectsConfirmation('ยืนยันปลดบัญชีจันทรานี้? (บัญชีจันทราใหม่ที่ผูก Thaiprompt คนนี้จะรับแถวเดิมไปทั้งสายงาน)', 'no')
            ->assertFailed();
        $this->assertNull(JuntraAccount::where('juntra_user_id', 860)->value('released_at'));

        $this->artisan('juntra:affiliate-release', ['juntra_user_id' => 860, '--reason' => 'ลูกค้ายืนยันแล้ว', '--by' => 'admin-a'])
            ->expectsConfirmation('ยืนยันปลดบัญชีจันทรานี้? (บัญชีจันทราใหม่ที่ผูก Thaiprompt คนนี้จะรับแถวเดิมไปทั้งสายงาน)', 'yes')
            ->assertSuccessful();

        // บิลที่เคยได้ 409 ผ่านเองรอบถัดไป — แถวเดิมชี้มาที่บัญชีใหม่ ไม่สร้างเงา
        $usersBefore = User::count();
        $this->juntraBill(9701, 861, 99, $real->id)->assertStatus(201);
        $this->assertSame(861, (int) JuntraAccount::where('user_id', $real->id)->value('juntra_user_id'));
        $this->assertSame($usersBefore, User::count());
    }

    /**
     * ส่งบิลซ้ำ = จ่ายรายชั้นครั้งเดียว · คืนเงิน = ดึงคืนทุกชั้น (ลูกค้าที่ย้ายมาจาก FB)
     */
    public function test_resent_web_bill_pays_each_level_once_and_void_claws_back(): void
    {
        [$grandUser, $grand] = $this->activeMember('GRAND001');
        [$inviterUser, $inviter] = $this->activeMember('INVITE01', $grand);
        $customer = $this->botCustomer(self::PSID_A, 'ลูกค้าส่งซ้ำ');
        $this->member($customer, $inviter, 'CUSTV001');

        $this->juntraBill(9601, 851, 99, $customer->id)->assertStatus(201);
        $this->juntraBill(9601, 851, 99, $customer->id)->assertOk()->assertJsonPath('data.duplicate', true);

        $readingId = $this->readingId(9601);
        $this->assertSame(1, FortuneCommission::where('fortune_reading_id', $readingId)->where('level', 1)->count());
        $this->assertSame(1, FortuneCommission::where('fortune_reading_id', $readingId)->where('level', 2)->count());
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));

        $this->postJson('/api/v1/juntra/server/affiliate/bills/9601/void', ['reason' => 'คืนเงิน'])
            ->assertOk()->assertJsonPath('data.voided', true);

        $this->assertSame(2, FortuneCommission::where('fortune_reading_id', $readingId)
            ->where('status', FortuneCommission::STATUS_REJECTED)->count());
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $grandUser->id)->value('balance'));
    }

    /**
     * M7: ลูกค้าที่ยังไม่มีตำแหน่ง → ผู้เชิญจาก FortuneReferral (PSID หลัก หรือ PSID สาขาใน alias) ก่อนผู้แนะนำเริ่มต้น
     */
    public function test_new_member_from_web_bill_honours_fortune_referral_of_psid_and_alias(): void
    {
        [, $inviter] = $this->activeMember('INVITE01');
        [, $inviter2] = $this->activeMember('INVITE02');

        // PSID หลัก
        $a = $this->botCustomer(self::PSID_A, 'ลูกค้าถูกเชิญ');
        $refA = $this->fortuneReferral(self::PSID_A, $inviter);
        $this->juntraBill(9701, 861, 99, $a->id)->assertStatus(201)
            ->assertJsonPath('data.member_code', MlmMember::where('user_id', $a->id)->value('member_code'));
        $this->assertSame($inviter->id, (int) MlmMember::where('user_id', $a->id)->value('unilevel_sponsor_id'));
        $this->assertSame(\App\Models\FortuneReferral::STATUS_CONVERTED, $refA->fresh()->status);

        // PSID สาขาที่สองใน alias
        $b = $this->botCustomer(self::PSID_C, 'ลูกค้าสาขาสอง');
        FortuneMessengerPsid::bind($b->id, self::PSID_B, null, 'session_alias');
        $this->fortuneReferral(self::PSID_B, $inviter2);
        $this->ensureJuntra(862, $b->id)->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'INVITE02')
            ->assertJsonPath('data.referral.sponsor_source', 'fortune_referral');

        // ไม่มีผู้เชิญ → ผู้แนะนำเริ่มต้นเหมือนเดิม
        $c = User::factory()->create();
        $this->ensureJuntra(863, $c->id)->assertStatus(201)->assertJsonPath('data.sponsor.member_code', 'ROOT0001');

        // รหัสเชิญที่ใช้ได้ยังชนะ FortuneReferral
        $d = $this->botCustomer('99887766554433221', 'มีรหัสเชิญ');
        $this->fortuneReferral('99887766554433221', $inviter2);
        $this->ensureJuntra(864, $d->id, ['referral_code' => 'INVITE01'])->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'INVITE01');
    }

    // ============================================================
    // 🔁 ธงปิด = พฤติกรรมเดิมของ e2a9e0868
    // ============================================================

    private function flagOff(): void
    {
        $this->settings(['enable_web_fortune_button' => true, 'facebook_web_only' => false]);
    }

    /** ธงปิด: ผู้ใช้ Thaiprompt ที่ถูกบัญชีจันทราอื่นผูก → สร้างตัวตนแยก (เงา) แบบเดิม ไม่ใช่ 409 */
    public function test_flag_off_thaiprompt_user_held_by_another_juntra_account_gets_a_shadow_like_before(): void
    {
        $this->flagOff();
        [$real] = $this->activeMember('REAL0001');
        JuntraAccount::create(['juntra_user_id' => 700, 'user_id' => $real->id, 'linked_via' => 'sso']);
        $usersBefore = User::count();

        $this->juntraBill(9301, 821, 99, $real->id)->assertStatus(201);
        $this->ensureJuntra(821, $real->id)->assertOk()->assertJsonPath('data.linked_via', 'auto');

        $account = JuntraAccount::where('juntra_user_id', 821)->firstOrFail();
        $this->assertSame('auto', $account->linked_via);
        $this->assertNotSame((int) $real->id, (int) $account->user_id, 'ธงปิด = บัญชีเงาแยก (เดิม)');
        $this->assertSame($usersBefore + 1, User::count());
        $this->assertSame((int) $account->user_id, (int) FortuneReading::where('bill_reference', 'JW-9301')->value('user_id'));
        $this->assertFalse(Cache::has("juntra:affiliate-taken-alert:821:{$real->id}"), 'ธงปิด = ไม่มีแจ้งเตือน thaiprompt_taken');
    }

    /** ธงปิด: ผู้เชิญ = รหัสเชิญ → ผู้แนะนำเริ่มต้น (ไม่อ่าน FortuneReferral ไม่ markAsConverted) */
    public function test_flag_off_pick_sponsor_ignores_fortune_referral(): void
    {
        $this->flagOff();
        [, $inviter] = $this->activeMember('INVITE01');
        $a = $this->botCustomer(self::PSID_A, 'ลูกค้าถูกเชิญ');
        $ref = $this->fortuneReferral(self::PSID_A, $inviter);

        $this->ensureJuntra(861, $a->id)->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'ROOT0001');
        $this->assertSame((int) $this->rootMember->id, (int) MlmMember::where('user_id', $a->id)->value('unilevel_sponsor_id'));
        $this->assertSame(\App\Models\FortuneReferral::STATUS_FOLLOWED, $ref->fresh()->status, 'ธงปิด = ไม่ markAsConverted');

        // รหัสเชิญยังใช้ได้เหมือนเดิม
        $b = $this->botCustomer(self::PSID_C, 'มีรหัสเชิญ');
        $this->ensureJuntra(862, $b->id, ['referral_code' => 'INVITE01'])->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'INVITE01');
    }

    /** ธงปิด: บิลเว็บ/ค่าแนะนำไม่ติดป้ายสาขา · page_code ไม่เขียนสาขาต้นทาง */
    public function test_flag_off_juntra_bills_carry_no_branch_and_origin_is_never_written(): void
    {
        $this->flagOff();
        [, $inviter] = $this->activeMember('INVITE01');
        $pageB = $this->branch('branch-b');
        $this->branch('branch-c');

        $customer = $this->botCustomer(self::PSID_B, 'ลูกค้าสาขาบี');
        $this->member($customer, $inviter, 'CUSTB001');
        User::whereKey($customer->id)->update(['fortune_origin_page_id' => $pageB->id]);

        $this->juntraBill(9201, 811, 99, $customer->id, ['page_code' => 'branch-c'])->assertStatus(201);
        $readingId = $this->readingId(9201);
        $this->assertNull(FortuneReading::find($readingId)->fortune_page_id);
        $this->assertGreaterThan(0, FortuneCommission::where('fortune_reading_id', $readingId)->count());
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $readingId)->whereNotNull('fortune_page_id')->count());

        $fresh = $this->botCustomer(self::PSID_C, 'ลูกค้าใหม่');
        $this->ensureJuntra(812, $fresh->id, ['page_code' => 'branch-b'])->assertSuccessful();
        $this->assertNull(User::whereKey($fresh->id)->value('fortune_origin_page_id'));
    }

    /** ธงปิด: resolveOrCreateWebUser ไม่เขียนตาราง alias และบัญชีใหม่ชื่อ 'User' (ไม่ดึงจาก persona/บิล) */
    public function test_flag_off_web_user_resolution_writes_no_alias_and_no_name(): void
    {
        $this->flagOff();
        FortuneCustomerPersona::getOrCreate('facebook', self::PSID_A, 'มาลี ใจดี');

        $new = $this->service()->resolveOrCreateWebUser('facebook', self::PSID_A, 7);
        $this->assertSame('User', $new->fresh()->name);
        $this->assertNull($new->fresh()->line_display_name);
        $this->assertSame(self::PSID_A, $new->fresh()->facebook_psid);
        $this->assertSame(0, FortuneMessengerPsid::where('psid', self::PSID_A)->count());

        $existing = $this->botCustomer(self::PSID_C, 'ลูกค้าเดิม');
        FortuneMessengerPsid::query()->where('psid', self::PSID_C)->delete();
        $this->assertSame((int) $existing->id, (int) $this->service()->resolveOrCreateWebUser('facebook', self::PSID_C, 5)->id);
        $this->assertSame(0, FortuneMessengerPsid::where('psid', self::PSID_C)->count(), 'ธงปิด = ไม่ bind alias');
        $this->assertSame('ลูกค้าเดิม', $existing->fresh()->name);
    }

    /** findExistingUser ขั้น 3: ธงเปิดเชื่อเฉพาะบิล facebook · ธงปิด = คิวรีเดิม (ไม่กรอง platform) */
    public function test_find_existing_user_step3_filters_platform_only_when_flag_is_on(): void
    {
        $lineOwner = User::factory()->create();
        FortuneReading::create([
            'user_id' => $lineOwner->id,
            'platform' => 'line',
            'platform_user_id' => self::PSID_A,
            'facebook_user_id' => self::PSID_A,
            'reading_type' => 'deep',
            'questions' => [],
        ]);

        $this->assertNull($this->service()->findExistingUser(self::PSID_A, 'facebook'));

        $this->flagOff();
        $this->assertSame((int) $lineOwner->id, (int) $this->service()->findExistingUser(self::PSID_A, 'facebook')?->id,
            'ธงปิด = คิวรีเดิมของ e2a9e0868');
    }

    /** ธงปิด: hint wf_psid ไม่มีผล — OAuth ต่อสายงานตอนสมัครเหมือนเดิม */
    public function test_flag_off_facebook_oauth_ignores_wf_psid_hint_and_enrols_member(): void
    {
        $this->flagOff();
        $this->oauthSettings();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $this->botCustomer(self::PSID_B);
        $this->withSession(['wf_psid' => self::PSID_B, 'wf_page' => 5, 'wf_psid_weak' => self::PSID_B]);
        $membersBefore = MlmMember::count();

        $result = $this->callFindOrCreateUser($this->socialiteUser());

        $this->assertSame($membersBefore + 1, MlmMember::count(), 'ธงปิด = ต่อสายงานตอนสมัคร (เดิม)');
        $this->assertSame((int) $this->rootMember->id, (int) MlmMember::where('user_id', $result->id)->value('unilevel_sponsor_id'));
    }

    /** ธงปิด: reconcile ข้ามเมื่อคอลัมน์มี PSID แล้ว · คอลัมน์ว่าง = เติมคอลัมน์อย่างเดียว ไม่เขียน alias */
    public function test_flag_off_oauth_reconcile_keeps_the_old_skip_and_never_writes_aliases(): void
    {
        $this->flagOff();
        $this->oauthSettings();
        FortunePage::create([
            'code' => 'MAIN', 'name' => 'เพจหลัก', 'platform' => 'facebook',
            'external_page_id' => 'pg-main', 'is_active' => true, 'is_default' => true,
        ]);
        FortuneTellingSetting::getSettings()->forceFill(['facebook_page_id' => 'pg-main'])->save();
        FortuneTellingSetting::clearSettingsCache();
        Http::swap(new \Illuminate\Http\Client\Factory(app('events')));
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [['id' => self::PSID_C]]], 200)]);

        // มี PSID ในคอลัมน์แล้ว → ข้าม (ไม่ลง alias ของ PSID เพจหลัก)
        $customer = $this->botCustomer(self::PSID_B);
        $customer->forceFill(['facebook_user_id' => self::ASID])->save();
        $this->assertSame((int) $customer->id, (int) $this->callFindOrCreateUser($this->socialiteUser())->id);
        $this->assertSame(0, FortuneMessengerPsid::where('psid', self::PSID_C)->count());
        $this->assertSame(self::PSID_B, (string) $customer->fresh()->facebook_psid);

        // คอลัมน์ว่าง → เติมคอลัมน์ (เดิม) แต่ไม่เขียนตาราง alias
        $customer->forceFill(['facebook_user_id' => null])->save();
        $linked = User::factory()->create();
        $linked->forceFill(['facebook_user_id' => self::ASID])->save();
        $this->assertSame((int) $linked->id, (int) $this->callFindOrCreateUser($this->socialiteUser())->id);
        $this->assertSame(self::PSID_C, (string) $linked->fresh()->facebook_psid);
        $this->assertSame(0, FortuneMessengerPsid::where('source', 'oauth_hint')->count());
    }

    // ============================================================
    // helpers ของ T15
    // ============================================================

    private function juntraBill(int $billId, int $userRef, float $amount, ?int $thaipromptUserId = null, array $extra = [])
    {
        return $this->postJson('/api/v1/juntra/server/affiliate/bills', array_filter(array_merge([
            'bill_id' => $billId,
            'user_ref' => $userRef,
            'name' => "ลูกค้า {$userRef}",
            'amount' => $amount,
            'product' => 'tarot_celtic',
            'paid_at' => now()->toIso8601String(),
            'thaiprompt_user_id' => $thaipromptUserId,
        ], $extra), fn ($v) => $v !== null));
    }

    private function ensureJuntra(int $userRef, ?int $thaipromptUserId = null, array $extra = [])
    {
        return $this->postJson('/api/v1/juntra/server/affiliate/accounts', array_filter(array_merge([
            'user_ref' => $userRef,
            'name' => "ลูกค้า {$userRef}",
            'thaiprompt_user_id' => $thaipromptUserId,
        ], $extra), fn ($v) => $v !== null));
    }

    private function branch(string $code): FortunePage
    {
        return FortunePage::create([
            'code' => $code,
            'name' => 'สาขา '.$code,
            'platform' => 'facebook',
            'external_page_id' => 'pg-'.$code,
            'is_active' => true,
        ]);
    }

    private function fortuneReferral(string $psid, MlmMember $referrer): \App\Models\FortuneReferral
    {
        return \App\Models\FortuneReferral::create([
            'referral_token' => 'tk-'.uniqid('', true),
            'referrer_user_id' => $referrer->user_id,
            'referrer_mlm_member_id' => $referrer->id,
            'referred_line_user_id' => $psid,
            'status' => \App\Models\FortuneReferral::STATUS_FOLLOWED,
            'followed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    // ============================================================
    // helpers ของ OAuth
    // ============================================================

    private function oauthSettings(): void
    {
        FacebookOAuthSetting::create([
            'app_id' => '664172615253513',
            'app_secret' => 'test-app-secret',
            'redirect_uri' => '/auth/facebook/callback',
            'is_enabled' => true,
        ]);
        FacebookOAuthSetting::clearCache();
    }

    private function socialiteUser(): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => self::ASID,
            'name' => 'สมหญิง ทดสอบ',
            'email' => null,
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    }

    private function callFindOrCreateUser(SocialiteUser $fbUser): User
    {
        $controller = new FacebookLoginController;
        $m = new \ReflectionMethod($controller, 'findOrCreateUser');
        $m->setAccessible(true);

        return $m->invoke($controller, $fbUser);
    }
}
