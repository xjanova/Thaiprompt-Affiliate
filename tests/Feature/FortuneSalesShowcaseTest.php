<?php

namespace Tests\Feature;

use App\Contracts\FortuneMessengerSender;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneSalesShowcase;
use App\Services\FortuneChannelManager;
use App\Services\LineFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * 🖼️ (2026-10-12) ชุดตัวอย่างตอนปิดการขาย — owner: "ส่งภาพกราฟชีวิต ภาพไพ่ 10 ใบ ภาพดวงดาว
 *   แล้วบอกว่าที่นี่ไม่ได้ยกเมฆ … ยกเว้นลูกค้าเก่า (เคยดูดวงจ่ายสำเร็จ 1 บิล)"
 *
 * ล็อกไว้:
 *   1. ลูกค้าเก่าไม่ได้ · ลูกค้าใหม่ได้ครั้งเดียวต่อ 7 วัน · บิลค้างจ่ายไม่ทำให้เป็นลูกค้าเก่า
 *   2. ข้อความไม่อ้างไพ่ 10 ใบเมื่อปิดแพคเกจ Celtic · มีประโยค "ไม่ได้ยกเมฆ" ครบ
 *   3. FB: ภาพ → ข้อความ → ข้อความนำ → การ์ด (การ์ดท้ายสุด)
 *   4. LINE: reply เดียว ≤ 5 กล่อง ภาพก่อน การ์ดท้ายสุด (เกิน 5 = การ์ดถูกตัดทิ้งเงียบ ๆ)
 *
 * @group fortune-sales
 */
class FortuneSalesShowcaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function paidReading(string $psid, bool $paid): void
    {
        FortuneReading::create([
            'facebook_user_id' => $psid,
            'facebook_user_name' => 'ทดสอบ',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => $paid ? FortuneReading::STATUS_COMPLETED : FortuneReading::STATUS_PENDING_PAYMENT,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => $psid,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => $paid,
            'paid_at' => $paid ? now() : null,
            'amount_paid' => $paid ? 39.00 : 0,
        ]);
    }

    private function settings(): FortuneTellingSetting
    {
        $s = new FortuneTellingSetting;
        $s->forceFill(['enable_deep_reading' => true, 'enable_celtic_cross' => true]);
        $s->exists = true;

        return $s;
    }

    private function tierResult(): array
    {
        return [
            'action' => 'tier_choice',
            'message' => 'เลือกแพคเกจค่ะ',
            'deep_price' => 39,
            'celtic_price' => 99,
            'offer_free' => false,
            'celtic_only_intro' => false,
            'black_magic_enabled' => false,
            'tier_meta' => ['deep_window' => 7, 'qa_window' => 15, 'q_limit_text' => 'ไม่จำกัด', 'welcome_line' => 'สวัสดีค่ะ'],
        ];
    }

    // ── 1. ใครได้ ──────────────────────────────────────────────────

    public function test_ลูกค้าใหม่ได้ครั้งเดียวตลอดไป_ลูกค้าเก่าไม่ได้(): void
    {
        $this->assertTrue(FortuneSalesShowcase::claim('psid-new'));
        $this->assertFalse(FortuneSalesShowcase::claim('psid-new'), 'เคยได้แล้ว ห้ามส่งซ้ำ');
        Cache::flush(); // deploy.sh รัน cache:clear ทุกรอบ — ธงต้องไม่หายไปกับ cache
        $this->assertFalse(FortuneSalesShowcase::claim('psid-new', 'cancel'), 'หลัง deploy ก็ห้ามส่งซ้ำ · จังหวะยกเลิกก็นับรวม');

        $this->paidReading('psid-old', true);
        $this->assertFalse(FortuneSalesShowcase::claim('psid-old'), 'เคยจ่ายสำเร็จ 1 บิล = ลูกค้าเก่า');

        $this->paidReading('psid-unpaid', false);
        $this->assertTrue(FortuneSalesShowcase::claim('psid-unpaid'), 'บิลค้างจ่ายยังไม่นับเป็นลูกค้าเก่า');

        $this->assertFalse(FortuneSalesShowcase::claim(''));
    }

    // ── 2. ข้อความ + ภาพ ─────────────────────────────────────────

    public function test_ข้อความบอกตรงว่าแพคเกจไหนได้อะไร(): void
    {
        $both = FortuneSalesShowcase::text(true, true, 39, 99);
        $this->assertStringContainsString('ไพ่ทาโรต์ 10 ใบ (แพคเกจ 99 บาท · แพคเกจ 39 บาท ได้ไพ่ 1 ใบ)', $both);
        $this->assertStringContainsString('ที่นี่ไม่ได้ยกเมฆ', $both);
        $this->assertStringContainsString('ใช้ไพ่ทาโรต์เป็นตัวยืน', $both);
        $this->assertStringContainsString('ถ้าคิดว่าเหมือนที่อื่น ไม่ใช่แบบนั้นแน่นอน', $both);

        $deepOnly = FortuneSalesShowcase::text(true, false, 39, null);
        $this->assertStringNotContainsString('10 ใบ', $deepOnly, 'ปิด Celtic แล้วห้ามอ้างไพ่ 10 ใบ');

        $this->assertCount(3, FortuneSalesShowcase::imageUrls(true));
        $this->assertCount(2, FortuneSalesShowcase::imageUrls(false));
        foreach (FortuneSalesShowcase::imageUrls(true) as $u) {
            $this->assertStringStartsWith('https://', $u);
        }
        foreach (['lifegraph', 'celtic', 'chart'] as $f) {
            $this->assertLessThan(950 * 1024, filesize(public_path("images/fortune/showcase/$f.jpg")), "$f.jpg ต้อง ≤ 1MB สำหรับ LINE");
        }
    }

    // ── 3. FB ────────────────────────────────────────────────────

    public function test_facebook_ภาพก่อน_การ์ดท้ายสุด_รอบสองไม่ส่งซ้ำ(): void
    {
        $sender = $this->recordingSender();
        $manager = new class($this->settings()) extends FortuneChannelManager
        {
            public function fb(FortuneMessengerSender $s, string $uid, array $r): bool
            {
                return $this->sendFacebookResponse($s, $uid, $r);
            }
        };

        $this->assertTrue($manager->fb($sender, 'psid-fb', $this->tierResult()));
        $this->assertSame(['image', 'image', 'image', 'text', 'text', 'template'], array_column($sender->sent, 0));
        $this->assertStringContainsString('ไม่ได้ยกเมฆ', $sender->sent[3][1]);

        $sender->sent = [];
        $this->assertTrue($manager->fb($sender, 'psid-fb', $this->tierResult()));
        $this->assertSame(['text', 'template'], array_column($sender->sent, 0), 'ภายใน 7 วันได้แค่การ์ดตามเดิม');

        $this->paidReading('psid-fb-old', true);
        $sender->sent = [];
        $this->assertTrue($manager->fb($sender, 'psid-fb-old', $this->tierResult()));
        $this->assertSame(['text', 'template'], array_column($sender->sent, 0), 'ลูกค้าเก่าได้แค่การ์ดตามเดิม');
    }

    // ── 5. กำลังจะยกเลิก: ชุดตัวอย่าง "แทน" คำถามว่ายกเลิกเพราะอะไร ──

    public function test_กำลังจะยกเลิก_ส่งชุดตัวอย่างแทนคำถาม_เคยได้แล้วถามตามเดิม(): void
    {
        $sender = $this->recordingSender();
        $manager = new class($this->settings()) extends FortuneChannelManager
        {
            public function fb(FortuneMessengerSender $s, string $uid, array $r): bool
            {
                return $this->sendFacebookResponse($s, $uid, $r);
            }
        };
        $ask = ['action' => 'cancel_dialog_ask', 'message' => '🙏 ก่อนยกเลิก แม่หมอขอถามได้ไหมคะ เกิดอะไรขึ้น?'];

        $this->assertTrue($manager->fb($sender, 'psid-cancel', $ask));
        $this->assertSame(['image', 'image', 'image', 'text'], array_column($sender->sent, 0), 'ไม่มีคำถามว่ายกเลิกทำไม');
        $this->assertStringContainsString('ไม่ควรพลาด', $sender->sent[3][1]);
        $this->assertStringContainsString('ไม่ได้ยกเมฆ', $sender->sent[3][1]);
        $this->assertStringContainsString('ยืนยันยกเลิก', $sender->sent[3][1], 'ยังต้องบอกทางยืนยันยกเลิก');

        $sender->sent = [];
        $this->assertTrue($manager->fb($sender, 'psid-cancel', $ask));
        $this->assertSame([['text', $ask['message']]], $sender->sent, 'เคยได้แล้ว = คำถามเดิม');

        // เคยได้จากการ์ดเมนูแล้ว → ตอนยกเลิกไม่ได้ซ้ำ
        $manager->fb($sender, 'psid-menu-first', $this->tierResult());
        $sender->sent = [];
        $manager->fb($sender, 'psid-menu-first', $ask);
        $this->assertSame(['text'], array_column($sender->sent, 0));

        $this->paidReading('psid-cancel-old', true);
        $sender->sent = [];
        $manager->fb($sender, 'psid-cancel-old', $ask);
        $this->assertSame(['text'], array_column($sender->sent, 0), 'ลูกค้าเก่าไม่ได้');
    }

    // ── 6. เปิดบิลแล้วไม่โอน · ลูกค้านิ่งนาน (prepare + send ตัวกลาง) ──

    public function test_ทวงบิล_ข้อความทวงแล้วชุดตัวอย่าง_line_รวมเป็นpushเดียว(): void
    {
        $settings = $this->settings();
        $first = FortuneSalesShowcase::prepare('U-bill', 'bill', $settings);
        $this->assertCount(3, $first['urls']);
        $this->assertNull(FortuneSalesShowcase::prepare('U-bill', 'idle', $settings), 'จังหวะถัดไปไม่ส่งซ้ำ');
        $this->paidReading('U-bill-old', true);
        $this->assertNull(FortuneSalesShowcase::prepare('U-bill-old', 'bill', $settings), 'ลูกค้าเก่าไม่ได้');

        $captured = [];
        $line = Mockery::mock(LineFortuneService::class)->makePartial();
        $line->shouldReceive('buildImageObject')->andReturnUsing(fn ($u) => ['type' => 'image', 'url' => $u]);
        $line->shouldReceive('buildTextObject')->andReturnUsing(fn ($t) => ['type' => 'text', 'text' => $t]);
        $line->shouldReceive('sendMessage')->never();
        $line->shouldReceive('sendMessagesWithReplyFallback')->once()->andReturnUsing(function ($uid, $msgs, $token) use (&$captured) {
            $captured = [$msgs, $token];

            return true;
        });
        $this->app->instance(LineFortuneService::class, $line);

        $job = new \App\Jobs\SendBillReminderJob(1);
        $send = new \ReflectionMethod($job, 'sendMessage');
        $send->setAccessible(true);
        $send->invoke($job, 'line', 'U-bill', '🌙 บิลยังรอโอนอยู่นะคะ', $first);

        $this->assertSame(['text', 'image', 'image', 'image', 'text'], array_column($captured[0], 'type'));
        $this->assertNull($captured[1], 'ทวงบิล = push ไม่มี reply token');
        $this->assertSame('🌙 บิลยังรอโอนอยู่นะคะ', $captured[0][0]['text']);
    }

    public function test_ส่งชุดตัวอย่างบน_facebook_ภาพก่อนข้อความ_ต่อท้ายได้(): void
    {
        $show = FortuneSalesShowcase::prepare('psid-idle', 'idle', $this->settings());
        $sender = $this->recordingSender();
        $this->assertTrue(FortuneSalesShowcase::send($sender, 'psid-idle', $show, 'ก่อนภาพ', 'ท้ายกล่อง'));
        $this->assertSame(['text', 'image', 'image', 'image', 'text'], array_column($sender->sent, 0));
        $this->assertSame('ก่อนภาพ', $sender->sent[0][1]);
        $this->assertStringEndsWith('ท้ายกล่อง', $sender->sent[4][1]);
    }

    // ── 4. LINE ──────────────────────────────────────────────────

    public function test_line_reply_เดียวไม่เกินห้ากล่อง_การ์ดท้ายสุด(): void
    {
        $captured = [];
        $line = Mockery::mock(LineFortuneService::class)->makePartial();
        $line->shouldReceive('buildImageObject')->andReturnUsing(fn ($u) => ['type' => 'image', 'url' => $u]);
        $line->shouldReceive('buildTextObject')->andReturnUsing(fn ($t) => ['type' => 'text', 'text' => $t]);
        $line->shouldReceive('buildFlexObject')->andReturn(['type' => 'flex']);
        $line->shouldReceive('buildTierChoiceFlexMessage')->andReturn(['type' => 'bubble']);
        $line->shouldReceive('sendMessagesWithReplyFallback')->andReturnUsing(function ($uid, $msgs) use (&$captured) {
            $captured[] = $msgs;

            return true;
        });

        $manager = new class($this->settings()) extends FortuneChannelManager
        {
            public function line(LineFortuneService $s, string $uid, array $r): bool
            {
                return $this->sendLineResponse($s, $uid, $r, ['reply_token' => 'tok']);
            }
        };

        $this->assertTrue($manager->line($line, 'U-new', $this->tierResult()));
        $msgs = $captured[0];
        $this->assertLessThanOrEqual(5, count($msgs));
        $this->assertSame(['image', 'image', 'image', 'text', 'flex'], array_column($msgs, 'type'));
        $this->assertStringContainsString('ไม่ได้ยกเมฆ', $msgs[3]['text']);
        $this->assertStringContainsString('สวัสดีค่ะ', $msgs[3]['text'], 'คำต้อนรับเดิมต้องอยู่ครบในกล่องเดียวกัน');

        $this->assertTrue($manager->line($line, 'U-new', $this->tierResult()));
        $this->assertSame(['text', 'flex'], array_column($captured[1], 'type'));
    }

    private function recordingSender(): object
    {
        return new class implements FortuneMessengerSender
        {
            public array $sent = [];

            public function sendMessage(string $recipientId, string $message, array $options = []): bool
            {
                $this->sent[] = ['text', $message];

                return true;
            }

            public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
            {
                $this->sent[] = ['quick_replies', $message];

                return true;
            }

            public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
            {
                $this->sent[] = ['image', $imageUrl];

                return true;
            }

            public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
            {
                $this->sent[] = ['template', ''];

                return true;
            }

            public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
            {
                $this->sent[] = ['generic', ''];

                return true;
            }

            public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
            {
                return true;
            }

            public function sendTypingIndicator(string $recipientId, bool $on = true): void {}

            public function getPlatformName(): string
            {
                return 'facebook';
            }
        };
    }
}
