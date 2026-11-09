<?php

namespace Tests\Feature;

use App\Jobs\SendFacebookCommentReply;
use App\Models\FortuneCommentEngagement;
use App\Models\FortuneCommentReply;
use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\CommentReplyComposer;
use App\Services\Fortune\CommentReplyPacer;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * 🗨️ (2026-11-09) คำตอบใต้คอมเมนต์ต้องหลากหลาย ไม่ซ้ำติดกัน + ทยอยตอบ ไม่ยิงรัว
 *
 * ต้นเรื่อง: เปิดธง facebook_web_only แล้วเพจหลักตอบ "แม่หมอทักไปใน inbox แล้วนะคะ 🌙"
 * 177 จาก 184 คอมเมนต์ในวันเดียว (prod 2026-11-09) = สัญญาณสแปมของ Facebook
 */
class CommentReplyVarietyTest extends TestCase
{
    use RefreshDatabase;

    /** คำที่บอกว่าแม่หมอทักไปแล้ว — ห้ามโผล่เมื่อ DM ไม่ได้ส่ง */
    private const INBOX_CLAIM = '/(inbox|Messenger|ทักไป|ส่งข้อความ|แม่หมอฝากข้อความ|กล่องข้อความ|คำขอข้อความ|ข้อความจากแม่หมอ|รออยู่ในแชท|ในแชท)/u';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        \Carbon\Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    // ------------------------------------------------------------
    // ความหลากหลาย
    // ------------------------------------------------------------

    public function test_คอมเมนต์เดียวกันซ้ำ60ครั้งบนเพจเดียว_คำตอบไม่ซ้ำติดกันและแทบไม่ซ้ำเลย(): void
    {
        $replies = [];
        for ($i = 0; $i < 60; $i++) {
            $replies[] = CommentReplyComposer::compose('สาธุ รับโชคค่ะ', 'สมศรี', true, 'page:1', 'psid-'.$i);
        }

        for ($i = 1; $i < count($replies); $i++) {
            $this->assertNotSame($replies[$i - 1], $replies[$i], "คำตอบที่ {$i} ซ้ำกับอันก่อนหน้า");
        }

        // ภายใน 40 อันล่าสุดต้องไม่ซ้ำกันเลย (ความจำข้อความเต็มต่อเพจ = 40)
        $window = array_slice($replies, 0, 40);
        $this->assertCount(40, array_unique($window), 'ใน 40 คำตอบติดกันมีข้อความซ้ำ: '.json_encode(array_count_values($window), JSON_UNESCAPED_UNICODE));
        $this->assertGreaterThanOrEqual(55, count(array_unique($replies)));
        $this->assertNotContains(\App\Services\Fortune\WebHandoffCopy::COMMENT_REPLY, $replies, 'ห้ามกลับไปใช้ประโยคตายตัว');
    }

    public function test_คอมเมนต์หลากหลายแบบที่เจอบนprod_ได้คำตอบไม่ซ้ำติดกัน(): void
    {
        // ข้อความจริงจาก fortune_comment_engagements 2026-11-09 (ตัดชื่อออก)
        $comments = ['', 'รับชีวิด', 'ขอน้อมรับค่ะขอให้มาจริงๆนะ', 'น้อมรับพรครับสาธุๆๆ', '', 'สาธุๆรับโชคให่ญสาธุๆค่ะ', 'ชั้นต้องรวย', '🙏🙏', 'อยากมีแฟน', 'ขอให้หายป่วย', 'สอบผ่านด้วยเถิด'];
        $prev = null;
        $all = [];
        for ($round = 0; $round < 4; $round++) {
            foreach ($comments as $i => $text) {
                $reply = CommentReplyComposer::compose($text, null, true, 'page:1', 'u'.$round.$i);
                $this->assertNotSame($prev, $reply);
                $this->assertTrue(FortuneInviteMessage::isWebSafeText($reply), $reply);
                $this->assertLessThanOrEqual(CommentReplyComposer::MAX_LENGTH, mb_strlen($reply));
                $prev = $reply;
                $all[] = $reply;
            }
        }

        $this->assertGreaterThan(40, count(array_unique($all)));
    }

    public function test_คำตอบสอดคล้องกับเรื่องที่คอมเมนต์(): void
    {
        $this->assertSame(CommentReplyComposer::MOOD_BLESSING, CommentReplyComposer::detectMood('น้อมรับพรครับสาธุๆๆ'));
        $this->assertSame(CommentReplyComposer::MOOD_LUCK, CommentReplyComposer::detectMood('ชั้นต้องรวย'));
        $this->assertSame(CommentReplyComposer::MOOD_LOVE, CommentReplyComposer::detectMood('อยากมีแฟนค่ะ'));
        $this->assertSame(CommentReplyComposer::MOOD_HEALTH, CommentReplyComposer::detectMood('แม่ป่วยอยู่'));
        $this->assertSame(CommentReplyComposer::MOOD_STICKER, CommentReplyComposer::detectMood(''));
        $this->assertSame(CommentReplyComposer::MOOD_STICKER, CommentReplyComposer::detectMood('🙏🙏✨'));
        $this->assertSame(CommentReplyComposer::MOOD_CALM, CommentReplyComposer::detectMood('อโหสิกรรมให้หมด'));
        $this->assertSame(CommentReplyComposer::MOOD_NEUTRAL, CommentReplyComposer::detectMood('เพจหลอกลวง'));

        $reply = CommentReplyComposer::compose('อยากมีแฟนค่ะ', null, true, 'page:2');
        $starts = array_filter(CommentReplyComposer::OPENERS[CommentReplyComposer::MOOD_LOVE], fn ($o) => str_starts_with($reply, $o));
        $this->assertNotEmpty($starts, "คอมเมนต์เรื่องความรักต้องได้คำขึ้นต้นเรื่องความรัก: {$reply}");
    }

    public function test_ไม่ได้ส่ง_d_m_ห้ามอ้างว่าทักไปในinboxแล้ว(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $reply = CommentReplyComposer::compose('สาธุค่ะ', 'สมศรี', false, 'page:1', 'u'.$i);
            $this->assertDoesNotMatchRegularExpression(self::INBOX_CLAIM, $reply, $reply);
        }
    }

    public function test_คอมเมนต์ปล่อยวางหรือตำหนิ_ไม่ตอบร่าเริงและไม่พูดเรื่องinbox(): void
    {
        foreach (['อโหสิกรรมให้หมด ไม่แคร์แล้ว', 'เพจหลอกลวง โกหกทั้งนั้น'] as $text) {
            for ($i = 0; $i < 10; $i++) {
                $reply = CommentReplyComposer::compose($text, 'สมศรี', true, 'page:1', 'u'.$i);
                $this->assertDoesNotMatchRegularExpression(self::INBOX_CLAIM, $reply, $reply);
                $this->assertDoesNotMatchRegularExpression('/(ใจฟู|ยิ้ม|ร่ำรวย|โชคลาภ)/u', $reply, $reply);
            }
        }
    }

    public function test_ชื่อ_ไม่รู้ชื่อไม่เหลือคุณลอยๆ_และไม่มีคุณคุณ(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $noName = CommentReplyComposer::compose('สาธุ', null, true, 'page:n', 'a'.$i);
            $this->assertStringNotContainsString('{name}', $noName);
            $this->assertDoesNotMatchRegularExpression('/คุณ\s*(แม่หมอ|คะ|ลอง|เช็ค|$)/u', $noName, $noName);

            $withPrefix = CommentReplyComposer::compose('สาธุ', 'คุณสมศรี', true, 'page:p', 'b'.$i);
            $this->assertStringNotContainsString('คุณคุณ', $withPrefix);
        }
    }

    public function test_ทุกบรรทัดในคลังผ่านlintเว็บและด่านเงินของ_fb(): void
    {
        foreach (CommentReplyComposer::allLines() as $line) {
            $this->assertTrue(FortuneInviteMessage::isWebSafeText($line), "ไม่ผ่าน lint: {$line}");
            $this->assertFalse(FacebookWebhookService::containsMoneyText($line), "มีคำเรื่องเงิน: {$line}");
            $this->assertDoesNotMatchRegularExpression('/(ดูดวงในแชท|ทักมา|https?:|m\.me)/u', $line, $line);
        }

        $this->assertGreaterThanOrEqual(20, count(CommentReplyComposer::INBOX_NOTICES));
        foreach (CommentReplyComposer::OPENERS as $mood => $openers) {
            $this->assertGreaterThanOrEqual(4, count($openers), "หมวด {$mood} น้อยเกินไป");
            $this->assertSame(count($openers), count(array_unique($openers)), "หมวด {$mood} มีบรรทัดซ้ำ");
        }
    }

    public function test_คลังใน_d_b_กันซ้ำต่อเพจ_คนละคนก็ไม่ได้ข้อความเดิมติดกัน(): void
    {
        FortuneCommentReply::query()->where('category', 'test_scope')->delete();
        foreach (['ขอบคุณค่ะ ก', 'ขอบคุณค่ะ ข', 'ขอบคุณค่ะ ค'] as $m) {
            FortuneCommentReply::create(['message' => $m, 'category' => 'test_scope', 'locale' => 'th', 'is_active' => true]);
        }

        $prev = null;
        for ($i = 0; $i < 30; $i++) {
            // ผู้ใช้ใหม่ทุกครั้ง ⇒ ความจำต่อคนช่วยไม่ได้ ต้องอาศัยความจำต่อเพจ
            $msg = FortuneCommentReply::pick('test_scope', 'user-'.$i, 'th', 'page:9');
            $this->assertNotNull($msg);
            $this->assertNotSame($prev, $msg, "รอบ {$i} ซ้ำกับรอบก่อน");
            $prev = $msg;
        }
    }

    // ------------------------------------------------------------
    // จังหวะ / เพดาน
    // ------------------------------------------------------------

    public function test_pacer_คำตอบแรกหน่วงสุ่ม_และคำตอบถัดไปเว้นระยะต่อเพจ(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-09 12:00:00');

        $delays = [];
        for ($i = 0; $i < 6; $i++) {
            $delays[] = CommentReplyPacer::reserve('page:1', 'post_'.$i);
        }

        $this->assertGreaterThanOrEqual(CommentReplyPacer::FIRST_DELAY_MIN, $delays[0]);
        $this->assertLessThanOrEqual(CommentReplyPacer::FIRST_DELAY_MAX, $delays[0]);
        for ($i = 1; $i < count($delays); $i++) {
            $gap = $delays[$i] - $delays[$i - 1];
            $this->assertGreaterThanOrEqual(CommentReplyPacer::GAP_MIN, $gap, 'คำตอบบนเพจเดียวกันต้องห่างกันอย่างน้อย GAP_MIN');
            $this->assertLessThanOrEqual(CommentReplyPacer::GAP_MAX, $gap);
        }

        // เพจอื่นมีคิวของตัวเอง ไม่ต้องรอเพจแรก
        $other = CommentReplyPacer::reserve('page:2', 'post_x');
        $this->assertLessThanOrEqual(CommentReplyPacer::FIRST_DELAY_MAX, $other);
    }

    public function test_pacer_เพดานต่อโพสต์ต่อชั่วโมง(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-09 12:00:00');

        for ($i = 0; $i < CommentReplyPacer::POST_CAP_PER_HOUR; $i++) {
            // เลื่อนเวลาไปเรื่อย ๆ ให้คิวไม่ยาวเกิน MAX_DEFER (วัดเพดานโพสต์อย่างเดียว)
            \Carbon\Carbon::setTestNow(now()->addSeconds(60));
            $this->assertNotNull(CommentReplyPacer::reserve('page:1', 'post_hot'), "คำตอบที่ {$i} ต้องได้คิว");
        }

        \Carbon\Carbon::setTestNow(now()->addSeconds(60));
        $this->assertNull(CommentReplyPacer::reserve('page:1', 'post_hot'), 'เกินเพดานต่อโพสต์แล้วต้องข้าม');
        $this->assertNotNull(CommentReplyPacer::reserve('page:1', 'post_other'), 'โพสต์อื่นยังตอบได้');
    }

    public function test_pacer_คอมเมนต์ท่วม_คิวยาวเกินกำหนดแล้วหยุดตอบ(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-09 12:00:00');

        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = CommentReplyPacer::reserve('page:flood', 'post_'.$i);
        }

        $queued = array_filter($results, fn ($d) => $d !== null);
        $this->assertNotEmpty($queued);
        $this->assertLessThan(100, count($queued), 'คอมเมนต์ท่วม 100 อันในวินาทีเดียว ต้องไม่รับทั้งหมด');
        foreach ($queued as $d) {
            $this->assertLessThanOrEqual(CommentReplyPacer::MAX_DEFER, $d);
        }
    }

    // ------------------------------------------------------------
    // job ตอบแบบหน่วงเวลา
    // ------------------------------------------------------------

    private function makeReplyJob(FacebookWebhookService $fb, array $data): SendFacebookCommentReply
    {
        return new class($data, $fb) extends SendFacebookCommentReply
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

    private function enableReplies(): void
    {
        FortuneTellingSetting::clearSettingsCache();
        FortuneTellingSetting::getSettings()->forceFill([
            'comment_engagement_enabled' => true,
            'enable_public_comment_reply' => true,
        ])->save();
        FortuneTellingSetting::clearSettingsCache();
    }

    public function test_job_ส่งสำเร็จ_บันทึกคำตอบลงแถวengagement_และไม่ส่งซ้ำ(): void
    {
        $this->enableReplies();
        $row = FortuneCommentEngagement::create([
            'facebook_user_id' => '6155', 'facebook_post_id' => '1_2', 'facebook_comment_id' => '2_3',
            'comment_text' => 'สาธุ', 'comment_reply' => null, 'dm_message' => 'dm', 'engaged_at' => now(),
        ]);

        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('replyToComment')->once()->with('2_3', 'สาธุค่ะ 🙏 แม่หมอทักไปใน inbox แล้วนะคะ')->andReturn(true);

        $data = ['comment_id' => '2_3', 'post_id' => '1_2', 'user_id' => '6155', 'comment_text' => 'สาธุ', 'reply' => 'สาธุค่ะ 🙏 แม่หมอทักไปใน inbox แล้วนะคะ'];
        $this->makeReplyJob($fb, $data)->handle();
        $this->assertSame('สาธุค่ะ 🙏 แม่หมอทักไปใน inbox แล้วนะคะ', $row->fresh()->comment_reply);

        // รันซ้ำ (queue retry) → ไม่ยิงซ้ำ
        $this->makeReplyJob($fb, $data)->handle();
    }

    public function test_job_สวิตช์ถูกปิดระหว่างรอคิว_ไม่ตอบ(): void
    {
        FortuneTellingSetting::clearSettingsCache();
        FortuneTellingSetting::getSettings()->forceFill([
            'comment_engagement_enabled' => true,
            'enable_public_comment_reply' => false,
        ])->save();
        FortuneTellingSetting::clearSettingsCache();

        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldNotReceive('replyToComment');

        $this->makeReplyJob($fb, ['comment_id' => '9_9', 'post_id' => null, 'user_id' => '1', 'comment_text' => '', 'reply' => 'ขอบคุณค่ะ'])->handle();
        $this->assertSame(0, FortuneCommentEngagement::where('facebook_comment_id', '9_9')->count());
    }

    public function test_job_f_bปฏิเสธ_ไม่บันทึกว่าตอบแล้ว(): void
    {
        $this->enableReplies();

        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldReceive('replyToComment')->twice()->andReturn(false);

        $this->makeReplyJob($fb, ['comment_id' => '7_7', 'post_id' => '1_7', 'user_id' => '1', 'comment_text' => 'x', 'reply' => 'ขอบคุณค่ะ'])->handle();

        $this->assertFalse(Cache::has('fcr:replied:7_7'));
        $this->assertSame(0, FortuneCommentEngagement::where('facebook_comment_id', '7_7')->count());
    }
}
