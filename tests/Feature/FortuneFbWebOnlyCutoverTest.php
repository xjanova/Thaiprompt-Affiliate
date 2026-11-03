<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FortuneSettingsController;
use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\LineBotKeyword;
use App\Models\User;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneWebLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\RunsDataMigrations;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T18 — หน้าแอดมิน + preflight + cutover ของ FB web-only
 *
 * ล็อกกติกา:
 *  1. preflight ต้อง FAIL ในแต่ละ blocker (ลิงก์เว็บ, SSO, ชุดข้อความ web ว่าง, page override,
 *     commission_mode=pv, throttle ต่ำ, คีย์เวิร์ดเงิน, respond_in_comment, บิล JW-, เมนูราคา) และผ่านเมื่อครบ
 *  2. cutover ค่าเริ่มต้น dry-run = ไม่เขียนอะไรเลย
 *  3. cutover --execute ไม่แตะ is_paid=1 / takeover / บิลค้างจ่าย / LINE
 *  4. หน้าแอดมิน: เปิดธงได้เมื่อลิงก์เว็บใช้ได้ (fb_web_only_since ประทับเอง) · ลิงก์พัง = บังคับปิด
 */
class FortuneFbWebOnlyCutoverTest extends TestCase
{
    use RefreshDatabase;
    use RunsDataMigrations;

    private const PSID = '61550000000077';

    private const PSID_2 = '61550000000088';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    private const SSO = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/redirect';

    /** สถานะที่ SSO ปลอมตอบ */
    private int $ssoStatus = 302;

    /** เมนูถาวรที่ Graph ปลอมคืน (null = ตอบ success เฉย ๆ) */
    private ?array $graphMenu = null;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing ของเครื่อง dev มี APP_KEY ความยาวไม่ถูก (ฟิลด์ encrypted ของ settings บันทึกไม่ได้)
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $this->app->forgetInstance('encrypter');

        Cache::flush();

        // CI โหลด schema dump ที่ไม่มีข้อมูล → ชุดข้อความชวน web ที่ migration seed ไว้หายไป ต้องรันซ้ำเอง
        $this->runDataMigration('2026_11_03_151100_seed_fortune_web_only_invite_messages.php');
        $this->runDataMigration('2026_11_03_160100_retire_old_web_only_invite_copy.php');

        // ตัวปลอม HTTP ตัวเดียวทั้งเทสต์ — Http::fake ซ้อนกันจะต่อท้ายและตัวแรกชนะ จึงอ่านค่าจาก property แทน
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'graph.facebook.com')) {
                return $this->graphMenu === null
                    ? Http::response(['result' => 'success'], 200)
                    : Http::response(['data' => [['persistent_menu' => [[
                        'locale' => 'default',
                        'call_to_actions' => $this->graphMenu,
                    ]]]]], 200);
            }

            return Http::response('', $this->ssoStatus);
        });

        // คีย์เวิร์ดจาก migration/seed อาจมีคำเรื่องเงิน — ปิดหมดให้ baseline นิ่ง (เทสต์คีย์เวิร์ดสร้างเอง)
        LineBotKeyword::query()->update(['is_active' => false]);

        $this->settings();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    // ── ตัวช่วย ──────────────────────────────────────────────────

    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'facebook_web_only' => false,
            'enable_web_fortune_button' => false,
            'respond_in_comment' => false,
            'fortune_commission_mode' => 'static',
            'web_fortune_sso_url' => self::SSO,
        ], $override))->save();
        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function reading(array $attrs = []): FortuneReading
    {
        return FortuneReading::create(array_merge([
            'platform' => 'facebook',
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้า FB',
            'questions' => 'การงานจะเป็นอย่างไร',
            'is_paid' => false,
        ], $attrs));
    }

    /** บิลเว็บจันทราภายใน 24 ชม. (หลักฐานว่า sync-bills ทำงาน) */
    private function juntraBill(int $hoursAgo = 1): FortuneReading
    {
        $r = $this->reading([
            'facebook_user_id' => 'jw-1',
            'platform_user_id' => 'jw-1',
            'reading_type' => FortuneReading::READING_TYPE_JUNTRA,
            'bill_reference' => FortuneReading::JUNTRA_BILL_PREFIX.random_int(1000, 99999),
            'is_paid' => true,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
        ]);
        DB::table('fortune_readings')->where('id', $r->id)->update(['created_at' => now()->subHours($hoursAgo)]);

        return $r;
    }

    /** baseline ที่ทุก blocker ผ่าน */
    private function green(): void
    {
        $this->juntraBill();
    }

    private function preflight(array $args = []): array
    {
        $code = Artisan::call('fortune:fb-web-only-preflight', array_merge(['--peak-per-minute' => 10], $args));

        return [$code, Artisan::output()];
    }

    private function cutover(array $args = []): array
    {
        $code = Artisan::call('fortune:web-only-cutover', $args);

        return [$code, Artisan::output()];
    }

    // ── preflight ────────────────────────────────────────────────

    public function test_preflight_ผ่านเมื่อครบทุกข้อ(): void
    {
        $this->green();

        [$code, $out] = $this->preflight();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('0 blocker', $out);
        $this->assertStringNotContainsString('BLOCKER', $out);
    }

    public function test_preflight_fail_ลิงก์เว็บของ_fb_สร้างไม่ได้(): void
    {
        $this->green();
        $fake = new class extends FortuneWebLinkService
        {
            public function isEnabledFor(?string $platform): bool
            {
                return false;
            }
        };
        $this->app->bind(FortuneWebLinkService::class, fn () => $fake);

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER ลิงก์เว็บของ FB', $out);
    }

    public function test_preflight_ลิงก์เว็บผ่านแม้ปุ่มเว็บ_line_ปิดและธงยังปิด(): void
    {
        // LINE ต้องไม่ถูกบังคับเปิด enable_web_fortune_button เพื่อให้ preflight ของ FB ผ่าน
        $this->green();
        $this->settings(['enable_web_fortune_button' => false, 'facebook_web_only' => false]);

        [$code, $out] = $this->preflight();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('ใช้ได้เมื่อเปิดธง', $out);
        $this->assertFalse((bool) FortuneTellingSetting::getSettings()->enable_web_fortune_button, 'preflight ห้ามแก้ค่า LINE');
    }

    public function test_preflight_fail_sso_ไม่ตอบ(): void
    {
        $this->green();
        $this->ssoStatus = 503;

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER SSO เว็บจันทรา', $out);
    }

    public function test_preflight_fail_sso_ไม่ใช่_https(): void
    {
        $this->green();
        $this->settings(['web_fortune_sso_url' => 'http://example.test/sso']);

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('ต้องเป็น https', $out);
    }

    public function test_preflight_fail_ชุดข้อความ_web_ว่าง(): void
    {
        $this->green();
        DB::table('fortune_invite_messages')->where('mode', 'web')->delete();

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER ข้อความ DM ชุด web', $out);
    }

    public function test_preflight_ข้อความ_web_ที่มีเรื่องเงินไม่นับ(): void
    {
        $this->green();
        DB::table('fortune_invite_messages')->where('mode', 'web')->delete();
        foreach (['ค่าครู 99 บาท นะคะ', 'โอนเงินมาได้เลยค่ะ', 'ดูที่ https://x.test'] as $i => $text) {
            DB::table('fortune_invite_messages')->insert([
                'message' => $text, 'mode' => 'web', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('ใช้ได้ 0 ข้อความ', $out);
        $this->assertStringContainsString('ที่มีเรื่องเงิน/ลิงก์', $out);
    }

    public function test_preflight_fail_เพจสาขาเขียนทับค่าคอม(): void
    {
        $this->green();
        FortunePage::create([
            'code' => 'branch-b', 'name' => 'สาขา B', 'platform' => 'facebook',
            'external_page_id' => '1111', 'is_active' => true,
            'settings_override' => ['fortune_level1_commission_amount' => 20, 'fortune_affiliate_enabled' => false],
        ]);

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER เพจสาขาเขียนทับค่าคอม/affiliate', $out);
        $this->assertStringContainsString('fortune_level1_commission_amount', $out);
    }

    public function test_preflight_เพจสาขาที่ปิดใช้งานไม่นับ_และคีย์ธงเป็นแค่คำเตือน(): void
    {
        $this->green();
        FortunePage::create([
            'code' => 'branch-off', 'name' => 'สาขาปิด', 'platform' => 'facebook',
            'external_page_id' => '2222', 'is_active' => false,
            'settings_override' => ['fortune_commission_mode' => 'pv'],
        ]);
        FortunePage::create([
            'code' => 'branch-flag', 'name' => 'สาขาธง', 'platform' => 'facebook',
            'external_page_id' => '3333', 'is_active' => true,
            'settings_override' => ['facebook_web_only' => false],
        ]);

        [$code, $out] = $this->preflight();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('คีย์โหมด/ธงที่เพจสาขาเขียนทับ', $out);
    }

    public function test_preflight_fail_commission_mode_pv_เว้นแต่_ack(): void
    {
        $this->green();
        $this->settings(['fortune_commission_mode' => 'pv']);

        [$code, $out] = $this->preflight();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER โหมดค่าคอม', $out);

        [$code2, $out2] = $this->preflight(['--ack-commission-mode' => true]);
        $this->assertSame(0, $code2, $out2);
        $this->assertStringContainsString('เจ้าของรับทราบแล้ว', $out2);
    }

    public function test_preflight_fail_throttle_ต่ำกว่า_peak(): void
    {
        $this->green();

        // named limiter jwchat: เพดานรวมแชท 300 → peak 250 × 1.5 = 375 > 300 (ด่านนอก jwsrv สูงกว่าเสมอ)
        config(['ratelimit.juntra_server.chat_total' => 300]);

        [$code, $out] = $this->preflight(['--peak-per-minute' => 250]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER เพดานแชทเว็บ', $out);
        $this->assertStringContainsString('เพดาน 300/นาที', $out);

        // เพดานรวมพอ → ผ่าน (preflight อ่านเพดานรวม ไม่ใช่เพดานต่อลูกค้า 60/นาที)
        config(['ratelimit.juntra_server.chat_total' => 1200]);
        [$code2, $out2] = $this->preflight(['--peak-per-minute' => 250]);
        $this->assertSame(0, $code2, $out2);
        $this->assertStringContainsString('เพดาน 1,200/นาที', $out2);
    }

    public function test_preflight_fail_คีย์เวิร์ดเงิน_เว้นแต่_ack(): void
    {
        $this->green();
        LineBotKeyword::create([
            'keyword' => 'ราคาดูดวง',
            'trigger_words' => ['ราคา'],
            'response_type' => 'text',
            'response_text' => 'ดูดวงละเอียด 39 บาท โอนเข้าพร้อมเพย์ได้เลยค่ะ',
            'category' => 'fortune',
            'priority' => 50,
            'is_active' => true,
        ]);

        [$code, $out] = $this->preflight();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER คีย์เวิร์ด fortune/faq ที่มีคำเรื่องเงิน', $out);

        [$code2] = $this->preflight(['--ack-money-keywords' => true]);
        $this->assertSame(0, $code2);
    }

    public function test_preflight_fail_respond_in_comment(): void
    {
        $this->green();
        $this->settings(['respond_in_comment' => true]);

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER ตอบคำทำนายใต้คอมเมนต์', $out);
    }

    public function test_preflight_fail_ไม่เห็นบิล_jw_ใน_24_ชม(): void
    {
        $this->juntraBill(30);

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER บิลเว็บจันทรา (JW-)', $out);
    }

    public function test_preflight_fail_เมนู_messenger_บน_fb_ยังมีปุ่มราคา_หลังเปิดธง(): void
    {
        $this->green();
        $this->settings(['facebook_web_only' => true, 'facebook_page_token' => 'page-token-test']);
        $this->graphMenu = [
            ['type' => 'postback', 'title' => '💎 ดูดวงละเอียด', 'payload' => 'MENU_DEEP_FORTUNE'],
            ['type' => 'postback', 'title' => '🔍 รู้จักเรา', 'payload' => 'MENU_ABOUT_US'],
        ];

        [$code, $out] = $this->preflight();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('BLOCKER เมนู Messenger บน FB — เพจหลัก', $out);

        // เมนูแบบเว็บแล้ว → ผ่าน
        $this->graphMenu = [
            ['type' => 'postback', 'title' => '🌙 เข้าห้องแม่หมอ', 'payload' => 'WEB_ROOM'],
            ['type' => 'postback', 'title' => '🔍 รู้จักเรา', 'payload' => 'MENU_ABOUT_US'],
        ];

        [$code2, $out2] = $this->preflight();
        $this->assertSame(0, $code2, $out2);
    }

    public function test_preflight_นับงานค้างเป็นคำเตือน_ไม่บล็อก(): void
    {
        $this->green();
        $this->reading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $this->reading(['facebook_user_id' => self::PSID_2, 'is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COLLECTING_QUESTIONS]);

        [$code, $out] = $this->preflight();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('Q1 บิล FB ค้างจ่าย', $out);
        $this->assertMatchesRegularExpression('/Q1 บิล FB ค้างจ่าย[^\n]*: 1 รายการ/u', $out);
        $this->assertMatchesRegularExpression('/Q2 จ่ายแล้วแต่ยังส่งของไม่ครบ[^\n]*: 1 รายการ/u', $out);
    }

    public function test_transfer_preflight_พิมพ์โหมดจริง_ไม่ขึ้น_classic_ตอนเป็น_daily(): void
    {
        $this->settings(['fortune_bot_mode' => 'daily', 'facebook_web_only' => true]);

        Artisan::call('fortune:transfer-preflight');
        $out = Artisan::output();

        $this->assertStringContainsString('daily (DM ดูดวงรายวัน)', $out);
        $this->assertStringNotContainsString('classic — ยังไม่เปิด', $out);
        $this->assertStringContainsString('fortune:fb-web-only-preflight', $out);
    }

    // ── cutover ──────────────────────────────────────────────────

    /**
     * ชุดข้อมูลผสม: soft state ที่ปิดได้ + แถวที่ห้ามแตะ
     *
     * @return array<string, FortuneReading|FortuneUserCredit>
     */
    private function cutoverFixture(): array
    {
        $soft = $this->reading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);
        $paid = $this->reading(['facebook_user_id' => self::PSID_2, 'is_paid' => true, 'conversation_status' => FortuneReading::STATUS_COLLECTING_QUESTIONS]);
        $paidSoftStatus = $this->reading(['facebook_user_id' => '61550000000099', 'is_paid' => true, 'conversation_status' => FortuneReading::STATUS_BASIC_DONE]);
        $takeover = $this->reading([
            'facebook_user_id' => '61550000000111',
            'conversation_status' => FortuneReading::STATUS_AWAITING_CONFIRMATION,
            'admin_takeover_until' => now()->addMinutes(30),
        ]);
        $pending = $this->reading(['facebook_user_id' => '61550000000122', 'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $line = $this->reading([
            'platform' => 'line',
            'facebook_user_id' => self::LINE_UID,
            'platform_user_id' => self::LINE_UID,
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
        ]);
        $old = $this->reading(['facebook_user_id' => '61550000000133', 'conversation_status' => FortuneReading::STATUS_BASIC_DONE]);
        DB::table('fortune_readings')->where('id', $old->id)->update(['updated_at' => now()->subDays(30)]);

        $fbCredit = FortuneUserCredit::create(['facebook_user_id' => self::PSID, 'platform' => 'facebook']);
        DB::table('fortune_user_credits')->where('id', $fbCredit->id)->update(['fb_fallback_granted_at' => now()->subDay(), 'transfer_attempts' => 2]);
        $lineCredit = FortuneUserCredit::create(['facebook_user_id' => self::LINE_UID, 'platform' => 'line']);
        DB::table('fortune_user_credits')->where('id', $lineCredit->id)->update(['fb_fallback_granted_at' => now()->subDay(), 'transfer_attempts' => 2]);

        Cache::put('fortune:force_tier:'.self::PSID, 'celtic', 600);
        Cache::put('fortune:consent_ok:'.self::PSID, true, 600);

        return compact('soft', 'paid', 'paidSoftStatus', 'takeover', 'pending', 'line', 'old', 'fbCredit', 'lineCredit');
    }

    /** ภาพรวมทั้งตาราง (ไว้เทียบว่า dry-run ไม่เขียนอะไร) */
    private function snapshot(): array
    {
        return [
            DB::table('fortune_readings')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            DB::table('fortune_user_credits')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        ];
    }

    public function test_cutover_ค่าเริ่มต้นเป็น_dry_run_ไม่เขียนอะไรเลย(): void
    {
        $this->cutoverFixture();
        $before = $this->snapshot();

        [$code, $out] = $this->cutover();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('DRY-RUN', $out);
        $this->assertSame($before, $this->snapshot(), 'dry-run ห้ามเขียน DB');
        $this->assertSame('celtic', Cache::get('fortune:force_tier:'.self::PSID), 'dry-run ห้ามล้าง cache');
    }

    public function test_cutover_dry_run_ชนะ_execute(): void
    {
        $this->cutoverFixture();
        $before = $this->snapshot();

        [$code, $out] = $this->cutover(['--execute' => true, '--dry-run' => true, '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('DRY-RUN', $out);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_cutover_execute_ปิด_soft_state_และเพิกถอน_fallback_โดยไม่แตะของห้ามแตะ(): void
    {
        $f = $this->cutoverFixture();
        $paidBefore = DB::table('fortune_readings')->where('id', $f['paid']->id)->first();

        [$code, $out] = $this->cutover(['--execute' => true, '--force' => true]);

        $this->assertSame(0, $code, $out);

        // ✅ soft state ที่ยังไม่จ่าย → completed + moved_to_web
        $soft = $f['soft']->fresh();
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $soft->conversation_status);
        $this->assertSame('moved_to_web', $soft->getConversationState('cancellation_reason'));
        $this->assertFalse((bool) $soft->is_paid);

        // ⛔ is_paid=1 ห้ามแตะ (ทั้งที่อยู่ใน collecting_* และที่สถานะดูเหมือน soft)
        $this->assertEquals($paidBefore, DB::table('fortune_readings')->where('id', $f['paid']->id)->first());
        $this->assertSame(FortuneReading::STATUS_BASIC_DONE, $f['paidSoftStatus']->fresh()->conversation_status);

        // ⛔ takeover / บิลค้างจ่าย / LINE / แถวเก่ากว่า --days ห้ามแตะ
        $this->assertSame(FortuneReading::STATUS_AWAITING_CONFIRMATION, $f['takeover']->fresh()->conversation_status);
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $f['pending']->fresh()->conversation_status);
        $this->assertSame(FortuneReading::STATUS_TIER_CHOICE, $f['line']->fresh()->conversation_status);
        $this->assertSame(FortuneReading::STATUS_BASIC_DONE, $f['old']->fresh()->conversation_status);

        // ✅ เพิกถอน fallback เฉพาะ FB · LINE ไม่โดน
        $fb = DB::table('fortune_user_credits')->where('id', $f['fbCredit']->id)->first();
        $this->assertNull($fb->fb_fallback_granted_at);
        $this->assertSame(0, (int) $fb->transfer_attempts);
        $line = DB::table('fortune_user_credits')->where('id', $f['lineCredit']->id)->first();
        $this->assertNotNull($line->fb_fallback_granted_at);
        $this->assertSame(2, (int) $line->transfer_attempts);

        // ✅ cache ที่จะพาไปออกบิลถูกล้าง
        $this->assertNull(Cache::get('fortune:force_tier:'.self::PSID));
        $this->assertNull(Cache::get('fortune:consent_ok:'.self::PSID));
    }

    public function test_cutover_execute_ไม่แตะลูกค้าที่ถูกเทคโอเวอร์บนแถวอื่น(): void
    {
        // แถว soft ไม่มี takeover เอง แต่บิลอื่นของคนเดียวกันถูกเทคโอเวอร์อยู่
        $soft = $this->reading(['conversation_status' => FortuneReading::STATUS_DISCOVERY_CHAT]);
        $this->reading(['conversation_status' => FortuneReading::STATUS_COMPLETED, 'admin_takeover_until' => now()->addHour()]);

        [$code] = $this->cutover(['--execute' => true, '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(FortuneReading::STATUS_DISCOVERY_CHAT, $soft->fresh()->conversation_status);
    }

    public function test_cutover_execute_ไม่แตะลูกค้าที่มีของจ่ายแล้วกำลังส่ง(): void
    {
        $soft = $this->reading(['conversation_status' => FortuneReading::STATUS_FREE_PREDICTED]);
        $this->reading(['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);

        [$code] = $this->cutover(['--execute' => true, '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(FortuneReading::STATUS_FREE_PREDICTED, $soft->fresh()->conversation_status);
    }

    public function test_cutover_execute_ไม่ยืนยัน_ไม่เขียน(): void
    {
        $this->cutoverFixture();
        $before = $this->snapshot();

        $this->artisan('fortune:web-only-cutover', ['--execute' => true])
            ->expectsConfirmation('ยืนยันเพิกถอน 1 สิทธิ์ ปิด 1 soft state และเติมชื่อ 0 บัญชี ใช่ไหม? (ย้อนกลับไม่ได้)', 'no')
            ->assertExitCode(1);

        $this->assertSame($before, $this->snapshot());
    }

    /** บัญชีบอท FB ชื่อ 'User' + บิลที่มีชื่อจริง (ของเดิมที่ migration เคยเปลี่ยนชื่อให้) */
    private function placeholderBotUser(string $psid, string $billName): \App\Models\User
    {
        $user = \App\Models\User::createBotProvisioned([
            'name' => 'User',
            'email' => 'fb_'.$psid.'@thaiprompt.local',
            'password' => bcrypt(\Illuminate\Support\Str::random(32)),
        ]);
        $user->forceFill(['facebook_psid' => $psid])->save();
        $this->reading(['facebook_user_id' => $psid, 'facebook_user_name' => $billName, 'conversation_status' => FortuneReading::STATUS_COMPLETED]);

        return $user->fresh();
    }

    /**
     * 🔁 เติมชื่อบัญชี 'User' ย้ายจาก migration มาที่ cutover — ธงปิด = ไม่แตะ users.name แม้ --execute
     */
    public function test_cutover_execute_ไม่เติมชื่อบัญชีเมื่อธงยังปิด(): void
    {
        $user = $this->placeholderBotUser('61550000000201', 'สมศรี ทดสอบ');

        [$code, $out] = $this->cutover(['--execute' => true, '--force' => true]);

        $this->assertSame(0, $code, $out);
        $this->assertSame('User', $user->fresh()->name, 'ธงปิด = ห้ามเขียนทับ users.name');
    }

    public function test_cutover_execute_เติมชื่อบัญชีบอท_user_เมื่อธงเปิด_dry_run_ไม่เขียน(): void
    {
        $this->settings(['facebook_web_only' => true]);
        $user = $this->placeholderBotUser('61550000000202', 'สมศรี ทดสอบ');
        $named = $this->placeholderBotUser('61550000000203', 'ชื่อในบิล');
        $named->forceFill(['name' => 'ชื่อที่ลูกค้าตั้งเอง'])->save();

        [$code, $out] = $this->cutover();
        $this->assertSame(0, $code, $out);
        $this->assertSame('User', $user->fresh()->name, 'dry-run ห้ามเขียน');

        [$code, $out] = $this->cutover(['--execute' => true, '--force' => true]);
        $this->assertSame(0, $code, $out);
        $this->assertSame('สมศรี ทดสอบ', $user->fresh()->name);
        $this->assertSame('ชื่อที่ลูกค้าตั้งเอง', $named->fresh()->name, 'ไม่แตะชื่อที่ไม่ใช่ User');
    }

    public function test_cutover_refresh_profile_ไม่ทำเมื่อธงปิด(): void
    {
        $this->settings(['facebook_page_token' => 'page-token-test']);

        [$code, $out] = $this->cutover(['--refresh-profile' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('ยังปิด', $out);
        Http::assertNothingSent();
    }

    public function test_cutover_refresh_profile_ตั้งเมนูแบบเว็บเมื่อธงเปิด(): void
    {
        $this->settings(['facebook_web_only' => true, 'facebook_page_token' => 'page-token-test']);

        [$code, $out] = $this->cutover(['--refresh-profile' => true]);

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('ตั้งเมนู Messenger ใหม่: เพจหลัก', $out);
        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return str_contains($request->url(), 'messenger_profile')
                && str_contains($body, 'WEB_ROOM')
                && ! str_contains($body, 'MENU_DEEP_FORTUNE');
        });
    }

    // ── หน้าแอดมิน ───────────────────────────────────────────────

    private function guard(array $validated, FortuneTellingSetting $settings): array
    {
        $ref = new \ReflectionMethod(FortuneSettingsController::class, 'guardFacebookWebOnly');
        $ref->setAccessible(true);
        $controller = $this->app->make(FortuneSettingsController::class);
        $args = [&$validated, $settings];
        $ref->invokeArgs($controller, $args);

        return $validated;
    }

    public function test_admin_เปิดธงได้เมื่อลิงก์เว็บใช้ได้_และประทับ_fb_web_only_since(): void
    {
        $settings = $this->settings();

        $validated = $this->guard(['facebook_web_only' => '1'], $settings);
        $this->assertTrue($validated['facebook_web_only']);

        $settings->update($validated);
        FortuneTellingSetting::clearSettingsCache();
        $fresh = FortuneTellingSetting::getSettings();

        $this->assertTrue((bool) $fresh->facebook_web_only);
        $this->assertNotNull($fresh->fb_web_only_since);
        $this->assertFalse((bool) $fresh->enable_web_fortune_button, 'เปิดธง FB ต้องไม่แตะปุ่มเว็บของ LINE');
        $this->assertStringContainsString('--refresh-profile', (string) session('warning'));
    }

    public function test_admin_ลิงก์เว็บพัง_บังคับไม่เปิดธง(): void
    {
        $settings = $this->settings();
        $fake = new class extends FortuneWebLinkService
        {
            public function isEnabledFor(?string $platform): bool
            {
                return false;
            }
        };
        $this->app->bind(FortuneWebLinkService::class, fn () => $fake);

        $validated = $this->guard(['facebook_web_only' => true], $settings);

        $this->assertFalse($validated['facebook_web_only']);
        $this->assertStringContainsString('ยังไม่เปิด', (string) session('warning'));
    }

    public function test_admin_sso_ที่แก้มาพร้อมกันต้องเป็น_https(): void
    {
        $settings = $this->settings();

        $validated = $this->guard(['facebook_web_only' => true, 'web_fortune_sso_url' => 'http://insecure.test/sso'], $settings);

        $this->assertFalse($validated['facebook_web_only']);
    }

    public function test_admin_ไม่ส่งคีย์ธงมา_ไม่แตะค่าเดิม(): void
    {
        $settings = $this->settings(['facebook_web_only' => true]);

        $validated = $this->guard(['reading_price' => 39], $settings);

        $this->assertArrayNotHasKey('facebook_web_only', $validated);
    }

    public function test_admin_ปิดธงได้_พร้อมคำเตือน(): void
    {
        $settings = $this->settings(['facebook_web_only' => true]);

        $validated = $this->guard(['facebook_web_only' => '0'], $settings);

        $this->assertFalse($validated['facebook_web_only']);
        $this->assertStringContainsString('ปิด', (string) session('warning'));
    }

    public function test_admin_หน้าตั้งค่ามีการ์ดธงพร้อม_hidden_0(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.fortune.settings.index'));

        $response->assertOk();
        $response->assertSee('ย้ายลูกค้า Facebook ไปเว็บจันทราทั้งหมด');
        $response->assertSee('<input type="hidden" name="facebook_web_only" value="0">', false);
        $response->assertSee('fortune:fb-web-only-preflight');
    }
}
