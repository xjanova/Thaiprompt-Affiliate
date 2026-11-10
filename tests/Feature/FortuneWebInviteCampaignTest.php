<?php

namespace Tests\Feature;

use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\WebHandoffCopy;
use Carbon\Carbon;
use Database\Seeders\FortuneWebInviteCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\RunsDataMigrations;
use Tests\TestCase;

/**
 * 🎉 (2026-11-11) หมวด "เชิญมาเว็บ" 150 แบบ — ฉลองเปิดระบบดูดวงผ่านเว็บ
 *
 * ล็อกกติกา:
 *  1. 150 ข้อความไม่ซ้ำ มี "ฟรี" + "คุณ{name}" ทุกข้อ ไม่มีคำเรื่องเงิน/LINE/ลิงก์ดิบ/ภาษาผู้ชาย/"ลูก"
 *  2. "ฟรี" ปลดเฉพาะหมวดนี้ — หมวดอื่นของชุด web ยังโดนกรองเหมือนเดิม
 *  3. seed รันซ้ำได้ และไม่ฟื้นแถวที่แอดมินลบ
 *  4. focus() ปิดหมวดอื่นทั้งหมด (ไม่ลบ) และ unfocus() คืนค่าเดิมจาก backup
 *  5. ใต้ธง FB web-only หลัง focus → DM หยิบเฉพาะหมวดเชิญมาเว็บ · ชื่อเรียก "คุณ" ไม่มี "คุณคุณ"
 */
class FortuneWebInviteCampaignTest extends TestCase
{
    use RefreshDatabase;
    use RunsDataMigrations;

    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // CI โหลด schema dump ไม่มีข้อมูล → seed ชุด web เดิมเองก่อน (หมวด web-room ฯลฯ)
        $this->runDataMigration('2026_11_03_151100_seed_fortune_web_only_invite_messages.php');
        FortuneWebInviteCampaignSeeder::seed();

        $this->backup = sys_get_temp_dir().'/web-invite-backup-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->backup);
        Carbon::setTestNow();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function webOnly(array $override = []): void
    {
        FortuneTellingSetting::clearSettingsCache();
        FortuneTellingSetting::getSettings()->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => true,
        ], $override))->save();
        FortuneTellingSetting::clearSettingsCache();
    }

    public function test_has_150_unique_messages_that_follow_the_copy_rules(): void
    {
        $messages = array_column(FortuneWebInviteCampaignSeeder::rows(), 'message');

        $this->assertCount(150, $messages);
        $this->assertCount(150, array_unique($messages), 'ข้อความต้องไม่ซ้ำกัน');

        foreach ($messages as $m) {
            $this->assertStringContainsString('ฟรี', $m, $m);
            $this->assertStringContainsString('คุณ{name}', $m, $m);
            $this->assertTrue(FortuneInviteMessage::isWebSafeText($m, true), "ไม่ผ่าน lint: {$m}");
            $this->assertDoesNotMatchRegularExpression('/(ครับ|ผม|ดิฉัน|หนู|เรา|ลูก|เคราะห์|ซวย|ภัย|อันตราย)/u', $m, $m);
            $this->assertLessThanOrEqual(WebHandoffCopy::BOX_TEXT_MAX, mb_strlen($m));
        }
    }

    public function test_free_word_is_allowed_only_for_the_web_invite_category(): void
    {
        $ours = FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)->first();
        $this->assertTrue($ours->allowsFreeWord());

        $other = new FortuneInviteMessage(['category' => 'web-room', 'message' => 'ดูดวงฟรีค่ะ']);
        $this->assertFalse($other->allowsFreeWord());
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ดูดวงฟรีค่ะ'));
        $this->assertTrue(FortuneInviteMessage::isWebSafeText('ดูดวงฟรีค่ะ', true));

        // คำเรื่องเงิน / ลิงก์ ยังห้ามแม้ในหมวดนี้
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ดูฟรี ค่าครู 39 บาท', true));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ดูฟรีที่ https://example.com', true));
        $this->assertFalse(FortuneInviteMessage::isWebSafeText('ดูฟรีที่ จันทรา.online', true));
    }

    public function test_seed_is_idempotent_and_never_revives_deleted_rows(): void
    {
        $count = fn () => FortuneInviteMessage::withTrashed()
            ->where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)->count();

        $this->assertSame(150, $count());
        $this->assertSame(0, FortuneWebInviteCampaignSeeder::seed());

        FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)->first()->delete();
        $this->assertSame(0, FortuneWebInviteCampaignSeeder::seed());
        $this->assertSame(150, $count());

        $row = FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)->first();
        $this->assertSame(FortuneInviteMessage::MODE_WEB, $row->mode);
    }

    public function test_seeder_is_registered_in_database_seeder(): void
    {
        $this->assertStringContainsString(
            'FortuneWebInviteCampaignSeeder::class',
            file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }

    public function test_focus_disables_every_other_category_and_unfocus_restores(): void
    {
        FortuneTellingSetting::getSettings()->update(['invite_disabled_categories' => ['old-off']]);
        FortuneTellingSetting::clearSettingsCache();

        $disabled = FortuneWebInviteCampaignSeeder::focus($this->backup);

        $this->assertNotContains(FortuneInviteMessage::CATEGORY_WEB_INVITE, $disabled);
        $this->assertContains('old-off', $disabled);
        $this->assertContains('web-room', $disabled);
        $this->assertFileExists($this->backup);

        // รันซ้ำห้ามทับ backup ของค่าเดิม
        FortuneWebInviteCampaignSeeder::focus($this->backup);
        $saved = json_decode(file_get_contents($this->backup), true);
        $this->assertSame(['old-off'], $saved['disabled_before']);

        // ไม่ลบข้อความหมวดอื่น
        $this->assertTrue(FortuneInviteMessage::where('category', 'web-room')->exists());

        FortuneWebInviteCampaignSeeder::unfocus($this->backup);
        FortuneTellingSetting::clearSettingsCache();
        $this->assertSame(['old-off'], FortuneTellingSetting::getSettings()->getDisabledInviteCategories());
        $this->assertFileDoesNotExist($this->backup);
    }

    public function test_web_only_dm_picks_only_web_invite_after_focus(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 11, 13, 0, 0, 'Asia/Bangkok'));
        $this->webOnly();
        FortuneWebInviteCampaignSeeder::focus($this->backup);

        for ($i = 0; $i < 15; $i++) {
            $pick = FortuneInviteMessage::pickActive('facebook');
            $this->assertNotNull($pick);
            $this->assertSame(FortuneInviteMessage::CATEGORY_WEB_INVITE, $pick->category);
            $pick->recordSend();
        }
    }

    public function test_rotation_prefers_least_sent_messages(): void
    {
        $this->webOnly();
        FortuneWebInviteCampaignSeeder::focus($this->backup);

        // ทุกข้อเคยส่งแล้ว ยกเว้นข้อเดียว → ต้องได้ข้อนั้น
        FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)->update(['send_count' => 5]);
        $fresh = FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)
            ->whereNull('hour_from')->first();
        $fresh->update(['send_count' => 0]);

        $this->assertSame($fresh->id, FortuneInviteMessage::pickActive('facebook')?->id);
    }

    public function test_render_calls_customer_khun_without_double_prefix(): void
    {
        $row = FortuneInviteMessage::where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)
            ->where('message', 'like', 'คุณ{name}คะ%')->first();
        $this->assertNotNull($row);

        $this->assertStringStartsWith('คุณสมชายคะ', $row->render('สมชาย', '61550000000111', 'facebook'));
        $this->assertStringStartsWith('คุณสมชายคะ', $row->render('คุณสมชาย', '61550000000111', 'facebook'));

        $noName = $row->render(null, '61550000000111', 'facebook');
        $this->assertStringStartsWith('คุณคะ', $noName);
        $this->assertStringNotContainsString('เจ้าชะตา', $noName);
        $this->assertStringNotContainsString('{name}', $noName);
        $this->assertTrue(FortuneInviteMessage::isWebSafeText($noName, $row->allowsFreeWord()));
    }
}
