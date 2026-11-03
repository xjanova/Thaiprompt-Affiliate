<?php

namespace Tests\Feature;

use App\Jobs\ProcessCommentEngagement;
use App\Models\FortuneCommentEngagement;
use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebHandoffCopy;
use Carbon\Carbon;
use Database\Seeders\FortuneWebOnlyInviteMessageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T11 — ชุดข้อความชวน FB web-only + DM จากคอมเมนต์
 *
 * ล็อกกติกา:
 *  1. ธงเปิด → pickActive() ฝั่ง FB หยิบเฉพาะชุด mode='web' / ชุดว่าง = null (ห้ามตกไปชุด all)
 *  2. quick reply = WEB_ROOM + พัก 7 วัน + ไม่ต้องส่งอีก (ไม่มี INVITE_READ_NOW / INVITE_FREE_WEB)
 *  3. ข้อความชุด web ไม่มีคำเรื่องเงิน/LINE/ฟรี และไม่มี URL ดิบ (lint)
 *  4. seeder + migration seed ครบทุกข้อของ design หัวข้อ 5 และรันซ้ำได้
 *  5. ProcessCommentEngagement ใต้ธง: ไม่มีปุ่มราคา ไม่มีการ์ด VIP ไม่มีดวงรายวัน
 *  6. LINE ไม่โดน
 */
class FortuneWebOnlyInviteMessageTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000111';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    /** regex จาก design หัวข้อ 7 (T11) */
    private const DESIGN_MONEY_REGEX = '/(โอน|สลิป|บัญชี|บาท|฿|ค่าครู|LINE|ไลน์|ฟรี|สายงาน|รายได้)/iu';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        Mockery::close();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง memo ก่อน/หลัง — แบบเดียวกับ FortuneTransferModeTest)
     */
    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => false,
            'enable_web_fortune_button' => false,
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function webOnly(array $override = []): FortuneTellingSetting
    {
        return $this->settings(array_merge(['facebook_web_only' => true], $override));
    }

    private function addRow(string $mode, string $message, array $extra = []): FortuneInviteMessage
    {
        return FortuneInviteMessage::create(array_merge([
            'message' => $message,
            'category' => 'test-'.$mode,
            'mode' => $mode,
            'is_active' => true,
            'sort_order' => 999,
        ], $extra));
    }

    private function payloads(array $quickReplies): array
    {
        return array_column($quickReplies, 'payload');
    }

    // ------------------------------------------------------------
    // seeder / migration / lint
    // ------------------------------------------------------------

    public function test_migration_seeds_every_design_invite_as_web_mode(): void
    {
        $rows = FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_WEB)->get();

        $this->assertCount(count(WebHandoffCopy::INVITES), $rows, 'migration ต้อง seed ครบทุกข้อของ design หัวข้อ 5');

        foreach (WebHandoffCopy::INVITES as $n => $invite) {
            $row = $rows->firstWhere('message', $invite['text']);
            $this->assertNotNull($row, "ขาดข้อความชวนข้อ {$n}");
        }

        // ช่วงเวลา: ข้อ 5 หัวค่ำ / ข้อ 6 เช้า / ข้ออื่นทุกเวลา
        $evening = $rows->firstWhere('message', WebHandoffCopy::INVITES[5]['text']);
        $this->assertSame([18, 22], [$evening->hour_from, $evening->hour_to]);
        $morning = $rows->firstWhere('message', WebHandoffCopy::INVITES[6]['text']);
        $this->assertSame([5, 9], [$morning->hour_from, $morning->hour_to]);
        $any = $rows->firstWhere('message', WebHandoffCopy::INVITES[1]['text']);
        $this->assertNull($any->hour_from);

        // ข้อ 3 (เฉพาะเรื่องหัวใจ) และ 8/9 (รอ continuity) seed แบบปิดไว้
        foreach ([3, 8, 9] as $n) {
            $this->assertFalse((bool) $rows->firstWhere('message', WebHandoffCopy::INVITES[$n]['text'])->is_active, "ข้อ {$n} ต้องปิดไว้");
        }
        foreach ([1, 2, 4, 5, 6, 7] as $n) {
            $this->assertTrue((bool) $rows->firstWhere('message', WebHandoffCopy::INVITES[$n]['text'])->is_active, "ข้อ {$n} ต้องเปิด");
        }
    }

    public function test_seeder_is_idempotent_and_never_revives_admin_deleted_rows(): void
    {
        $this->assertSame(0, FortuneWebOnlyInviteMessageSeeder::seed(), 'รันซ้ำต้องไม่สร้างแถวซ้ำ');

        // แอดมินลบข้อ 1 ทิ้ง (soft delete) → seed ใหม่ต้องไม่ฟื้น
        FortuneInviteMessage::where('message', WebHandoffCopy::INVITES[1]['text'])->first()->delete();
        $this->assertSame(0, FortuneWebOnlyInviteMessageSeeder::seed());
        $this->assertSame(0, FortuneInviteMessage::where('message', WebHandoffCopy::INVITES[1]['text'])->count());

        // ถูกลบถาวร → seed กลับได้
        FortuneInviteMessage::withTrashed()->where('message', WebHandoffCopy::INVITES[1]['text'])->forceDelete();
        $this->assertSame(1, FortuneWebOnlyInviteMessageSeeder::seed());

        // ผ่าน DatabaseSeeder path (เรียก run() ตรง ๆ แบบไม่มี command) ก็ไม่พัง
        (new FortuneWebOnlyInviteMessageSeeder)->run();
        $this->assertSame(count(WebHandoffCopy::INVITES), FortuneInviteMessage::where('mode', 'web')->count());
    }

    public function test_seeder_is_registered_in_database_seeder(): void
    {
        $source = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertStringContainsString('FortuneWebOnlyInviteMessageSeeder::class', $source);
    }

    public function test_money_word_lint_on_every_seeded_line(): void
    {
        foreach (FortuneWebOnlyInviteMessageSeeder::rows() as $row) {
            $msg = $row['message'];

            $this->assertDoesNotMatchRegularExpression(self::DESIGN_MONEY_REGEX, $msg, "ข้อ {$row['number']} มีคำเรื่องเงิน/LINE/ฟรี");
            $this->assertDoesNotMatchRegularExpression(WebHandoffCopy::FORBIDDEN_REGEX, $msg, "ข้อ {$row['number']} ไม่ผ่าน FORBIDDEN_REGEX");
            $this->assertDoesNotMatchRegularExpression('#(https?://|www\.|\{\{web_link\}\}|\{\{line_link\}\})#i', $msg, "ข้อ {$row['number']} มี URL/ลิงก์ดิบ");
            $this->assertTrue(FortuneInviteMessage::isWebSafeText($msg));
            $this->assertStringStartsWith('web-', $row['category']);
        }

        // คำตอบใต้คอมเมนต์ของ job (ตอน DM ติด cooldown) ต้องผ่าน lint เดียวกัน
        $publicReplies = (new \ReflectionClassConstant(ProcessCommentEngagement::class, 'WEB_ONLY_PUBLIC_REPLIES'))->getValue();
        foreach (array_merge($publicReplies, [WebHandoffCopy::COMMENT_REPLY]) as $line) {
            $this->assertTrue(FortuneInviteMessage::isWebSafeText($line), $line);
            $this->assertDoesNotMatchRegularExpression('/(ดูดวงในแชท|ทักมา)/u', $line);
        }

        // ตัว lint เองต้องจับของผิดได้จริง
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ดูดวงฟรีค่ะ'));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ค่าครู 39 บาท'));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('กดเลย {{web_link}}'));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('เข้าที่ https://example.com ได้เลย'));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText(''));
    }

    // ------------------------------------------------------------
    // pickActive
    // ------------------------------------------------------------

    public function test_flag_off_never_picks_web_rows(): void
    {
        $this->settings();
        $this->addRow(FortuneInviteMessage::MODE_ALL, 'ข้อความชุดกลาง {name}');

        for ($i = 0; $i < 25; $i++) {
            $row = FortuneInviteMessage::pickActive();
            $this->assertNotNull($row);
            $this->assertNotSame(FortuneInviteMessage::MODE_WEB, $row->mode);
        }
    }

    public function test_flag_on_picks_only_web_rows_even_with_other_sets_present(): void
    {
        $this->webOnly();
        $this->addRow(FortuneInviteMessage::MODE_ALL, 'ดูดวงในแชทได้เลย 39 บาท');
        $this->addRow(FortuneInviteMessage::MODE_CLASSIC, 'ทักมาดูดวงในแชทนะ');
        $this->addRow(FortuneInviteMessage::MODE_TRANSFER, 'ดูฟรีที่ {{web_link}}');
        $this->addRow(FortuneInviteMessage::MODE_DAILY, 'บอกวันเกิดรับดวงฟรี');

        for ($i = 0; $i < 30; $i++) {
            $row = FortuneInviteMessage::pickActive('facebook');
            $this->assertNotNull($row);
            $this->assertSame(FortuneInviteMessage::MODE_WEB, $row->mode);
            $this->assertTrue((bool) $row->is_active);
        }

        // resolveFor ใช้ pickActive ตัวเดียวกัน (ส่ง platform ต่อ)
        $this->assertSame(FortuneInviteMessage::MODE_WEB, FortuneInviteMessage::pickActive()->mode);
    }

    public function test_flag_on_with_empty_web_set_returns_null_never_falls_back_to_all(): void
    {
        $this->webOnly();
        FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_WEB)->update(['is_active' => false]);
        $this->addRow(FortuneInviteMessage::MODE_ALL, 'ข้อความชุดกลาง');
        $this->addRow(FortuneInviteMessage::MODE_CLASSIC, 'ข้อความชุดเดิม');

        $this->assertNull(FortuneInviteMessage::pickActive('facebook'));
    }

    public function test_flag_on_skips_web_rows_with_forbidden_words_or_urls(): void
    {
        $this->webOnly();
        FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_WEB)->update(['is_active' => false]);

        $this->addRow(FortuneInviteMessage::MODE_WEB, 'ดูฟรีในห้องแม่หมอค่ะ');
        $this->addRow(FortuneInviteMessage::MODE_WEB, 'เข้าห้องที่ https://example.com ค่ะ');
        $this->assertNull(FortuneInviteMessage::pickActive('facebook'), 'มีแต่แถวผิดกติกา = ไม่ส่ง');

        $safe = $this->addRow(FortuneInviteMessage::MODE_WEB, 'แม่หมอรออยู่ในห้องนะคะ 🌙');
        for ($i = 0; $i < 15; $i++) {
            $this->assertSame($safe->id, FortuneInviteMessage::pickActive('facebook')->id);
        }
    }

    public function test_flag_on_respects_time_window_inside_web_set(): void
    {
        $this->webOnly();
        // เปิดไว้แค่ข้อ 1 (ทุกเวลา) กับข้อ 5 (หัวค่ำ)
        FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_WEB)
            ->whereNotIn('message', [WebHandoffCopy::INVITES[1]['text'], WebHandoffCopy::INVITES[5]['text']])
            ->update(['is_active' => false]);

        Carbon::setTestNow(Carbon::create(2026, 11, 3, 12, 0, 0, 'Asia/Bangkok'));
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(WebHandoffCopy::INVITES[1]['text'], FortuneInviteMessage::pickActive('facebook')->message);
        }
    }

    public function test_line_is_unaffected_by_flag(): void
    {
        $this->webOnly();
        FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_WEB)->update(['is_active' => true]);
        $classic = $this->addRow(FortuneInviteMessage::MODE_ALL, 'ข้อความชุดกลางของ {name}');

        for ($i = 0; $i < 20; $i++) {
            $row = FortuneInviteMessage::pickActive('line');
            $this->assertSame($classic->id, $row->id, 'LINE ต้องหยิบชุดเดิม ไม่ใช่ชุด web');
        }

        // render ของ LINE ไม่เข้าเส้น web (ชื่อแบบเดิม)
        $this->assertSame('ข้อความชุดกลางของ สมชาย', $classic->render('สมชาย', self::LINE_UID, 'line'));
    }

    // ------------------------------------------------------------
    // quick replies + render
    // ------------------------------------------------------------

    public function test_quick_replies_under_web_only_are_web_room_and_opt_out_only(): void
    {
        $this->webOnly(['enable_web_fortune_button' => true]);

        $qr = FortuneInviteMessage::quickReplies();

        $this->assertSame(['WEB_ROOM', 'INVITE_SNOOZE_7D', 'INVITE_OPTOUT'], $this->payloads($qr));
        $this->assertNotContains('INVITE_READ_NOW', $this->payloads($qr));
        $this->assertNotContains('INVITE_FREE_WEB', $this->payloads($qr));
        $this->assertSame(WebHandoffCopy::inviteQuickReplies(), $qr);
        foreach ($qr as $button) {
            $this->assertDoesNotMatchRegularExpression(self::DESIGN_MONEY_REGEX, $button['title']);
            $this->assertLessThanOrEqual(20, mb_strlen($button['title']));
        }
    }

    public function test_quick_replies_flag_off_unchanged(): void
    {
        $this->settings();

        $payloads = $this->payloads(FortuneInviteMessage::quickReplies());

        $this->assertContains('INVITE_READ_NOW', $payloads);
        $this->assertContains('INVITE_SNOOZE_7D', $payloads);
        $this->assertNotContains('WEB_ROOM', $payloads);
    }

    public function test_render_web_rows_never_contain_raw_url_and_use_name_rules(): void
    {
        $this->webOnly(['enable_web_fortune_button' => true]);

        $one = FortuneInviteMessage::where('message', WebHandoffCopy::INVITES[1]['text'])->first();
        $this->assertSame(WebHandoffCopy::invite(1, 'สมศรี'), $one->render('สมศรี', self::PSID, 'facebook'));
        // ไม่มีชื่อ → "เจ้าชะตา" (ข้อ 1)
        $this->assertSame(WebHandoffCopy::invite(1, null), $one->render('คุณ', self::PSID, 'facebook'));

        // ข้อ 3 ไม่มีชื่อ → ตัดทิ้ง (ตาม NAME_FALLBACK)
        $three = FortuneInviteMessage::where('message', WebHandoffCopy::INVITES[3]['text'])->first();
        $this->assertSame(WebHandoffCopy::invite(3, null), $three->render(null, self::PSID, 'facebook'));

        // แถวชุดเก่าที่มี {{web_link}} ถูก render ใต้ธง → บรรทัดลิงก์หายทั้งบรรทัด ไม่มี URL
        $legacy = $this->addRow(FortuneInviteMessage::MODE_TRANSFER, "แม่หมอรออยู่นะ{name}\n👉 {{web_link}}\nhttps://line.me/R/ti/p/@x");
        $out = $legacy->render('สมศรี', self::PSID, 'facebook');
        $this->assertSame('แม่หมอรออยู่นะสมศรี', $out);
        $this->assertStringNotContainsString('http', $out);
    }

    // ------------------------------------------------------------
    // ProcessCommentEngagement
    // ------------------------------------------------------------

    /**
     * job ที่ใช้ตัวส่ง Facebook ปลอม
     */
    private function makeJob(FacebookWebhookService $fb, array $data = []): ProcessCommentEngagement
    {
        $data = array_merge([
            'facebook_user_id' => self::PSID,
            'facebook_post_id' => '1000_2000',
            'facebook_comment_id' => '2000_'.random_int(1000, 999999),
            'comment_text' => 'สาธุค่ะ เรื่องความรักช่วงนี้',
            'user_name' => 'สมศรี ใจดี',
        ], $data);

        return new class($data, $fb) extends ProcessCommentEngagement
        {
            public function __construct(array $data, private FacebookWebhookService $fakeFb)
            {
                parent::__construct($data);
            }

            protected function makeFacebookService(FortuneTellingSetting $settings): FacebookWebhookService
            {
                return $this->fakeFb;
            }
        };
    }

    /**
     * ค่าที่ทำให้เส้นเดิมมีปุ่มราคา + การ์ด VIP + ดวงรายวันครบ (ถ้าไม่ถูกกั้น)
     */
    private function engagementSettings(array $override = []): array
    {
        return array_merge([
            'comment_engagement_enabled' => true,
            'enable_public_comment_reply' => false,
            'dm_send_to_foreigners' => true,
            'dm_filter_age_enabled' => false,
            'entry_cards_on_dm' => true,
            'enable_celtic_cross' => true,
            'fortune_bot_mode' => FortuneBotMode::MODE_DAILY,
        ], $override);
    }

    public function test_comment_engagement_under_web_only_sends_web_invite_without_price_card_or_daily(): void
    {
        $this->webOnly($this->engagementSettings());

        $sent = [];
        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('getUserProfile')->andReturn(['name' => 'สมศรี', 'id' => self::PSID]);
        $fb->shouldReceive('sendQuickReplies')->once()
            ->andReturnUsing(function ($to, $text, $qr, $opts) use (&$sent) {
                $sent = compact('to', 'text', 'qr', 'opts');

                return true;
            });
        $fb->shouldNotReceive('sendPrivateReplyTemplate');
        $fb->shouldNotReceive('sendMessage');
        $fb->shouldNotReceive('sendImage');
        $fb->shouldNotReceive('sendPrivateReplyImageWithQuickReplies');
        $fb->shouldNotReceive('replyToComment');

        $job = $this->makeJob($fb);
        $job->handle();

        $this->assertSame(self::PSID, $sent['to']);
        $this->assertSame(['WEB_ROOM', 'INVITE_SNOOZE_7D', 'INVITE_OPTOUT'], $this->payloads($sent['qr']));
        $this->assertNotContains('FORTUNE_BASIC', $this->payloads($sent['qr']));
        $this->assertNotContains('TIER_CELTIC_99', $this->payloads($sent['qr']));
        $this->assertNotContains('DAILY_FREE_START', $this->payloads($sent['qr']));
        $this->assertTrue($sent['opts']['from_comment_engagement']);
        $this->assertNotEmpty($sent['opts']['comment_id']);

        $this->assertDoesNotMatchRegularExpression(self::DESIGN_MONEY_REGEX, $sent['text']);
        $this->assertDoesNotMatchRegularExpression('#https?://#i', $sent['text']);

        // ข้อความต้องมาจากชุด web ที่ render แล้ว
        $webTexts = FortuneInviteMessage::where('mode', 'web')->where('is_active', true)->get()
            ->map(fn ($r) => $r->render('สมศรี', self::PSID, 'facebook'))->all();
        $this->assertContains($sent['text'], $webTexts);

        $row = FortuneCommentEngagement::where('facebook_user_id', self::PSID)->first();
        $this->assertNotNull($row, 'ส่งถึงแล้วต้องบันทึก engagement');
        $this->assertSame($sent['text'], $row->dm_message);
        $this->assertNull($row->comment_reply);
    }

    public function test_comment_engagement_web_only_public_reply_uses_inbox_line(): void
    {
        $this->webOnly($this->engagementSettings(['enable_public_comment_reply' => true]));

        $reply = null;
        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('getUserProfile')->andReturn(['name' => 'สมศรี', 'id' => self::PSID]);
        $fb->shouldReceive('replyToComment')->once()
            ->andReturnUsing(function ($commentId, $text) use (&$reply) {
                $reply = $text;

                return true;
            });
        $fb->shouldReceive('reactToComment')->andReturn(true);
        $fb->shouldReceive('sendQuickReplies')->once()->andReturn(true);
        $fb->shouldNotReceive('sendPrivateReplyTemplate');
        $fb->shouldNotReceive('sendMessage');

        $this->makeJob($fb)->handle();

        $this->assertSame(WebHandoffCopy::COMMENT_REPLY, $reply);
        $this->assertSame(
            WebHandoffCopy::COMMENT_REPLY,
            FortuneCommentEngagement::where('facebook_user_id', self::PSID)->value('comment_reply')
        );
    }

    public function test_comment_engagement_web_only_falls_back_to_code_copy_when_web_set_empty(): void
    {
        $this->webOnly($this->engagementSettings());
        FortuneInviteMessage::where('mode', 'web')->update(['is_active' => false]);
        $this->addRow(FortuneInviteMessage::MODE_ALL, 'ดูดวงในแชท 39 บาท');

        $text = null;
        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('getUserProfile')->andReturn(['name' => 'สมศรี', 'id' => self::PSID]);
        $fb->shouldReceive('sendQuickReplies')->once()
            ->andReturnUsing(function ($to, $t) use (&$text) {
                $text = $t;

                return true;
            });

        $this->makeJob($fb)->handle();

        $this->assertNotNull($text);
        $this->assertStringNotContainsString('39', $text);
        $this->assertDoesNotMatchRegularExpression(self::DESIGN_MONEY_REGEX, $text);
        $this->assertContains($text, array_map(
            fn ($n) => WebHandoffCopy::invite($n, 'สมศรี'),
            array_keys(WebHandoffCopy::INVITES)
        ));
    }

    public function test_comment_engagement_web_only_dm_failure_leaves_no_record_for_retry(): void
    {
        $this->webOnly($this->engagementSettings());

        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('getUserProfile')->andReturn(['name' => 'สมศรี', 'id' => self::PSID]);
        $fb->shouldReceive('sendQuickReplies')->once()->andReturn(false);
        $fb->shouldNotReceive('sendPrivateReplyImageWithQuickReplies');
        $fb->shouldNotReceive('sendImage');

        $this->makeJob($fb)->handle();

        $this->assertSame(0, FortuneCommentEngagement::where('facebook_user_id', self::PSID)->count());
    }

    public function test_comment_engagement_flag_off_keeps_legacy_buttons(): void
    {
        $this->settings($this->engagementSettings([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'entry_cards_on_dm' => false,
        ]));

        $qr = null;
        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('getUserProfile')->andReturn(['name' => 'สมศรี', 'id' => self::PSID]);
        $fb->shouldReceive('sendMessage')->andReturn(false)->byDefault();
        $fb->shouldReceive('isDeadComment')->andReturn(true)->byDefault();
        $fb->shouldReceive('sendQuickReplies')->once()
            ->andReturnUsing(function ($to, $text, $q) use (&$qr) {
                $qr = $q;

                return true;
            });

        $this->makeJob($fb)->handle();

        $this->assertContains('FORTUNE_BASIC', $this->payloads($qr), 'ธงปิด = ปุ่มเดิมทุกอย่าง');
        $this->assertNotContains('WEB_ROOM', $this->payloads($qr));
    }
}
