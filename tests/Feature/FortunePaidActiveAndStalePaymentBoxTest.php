<?php

namespace Tests\Feature;

use App\Contracts\FortuneMessengerSender;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookRichMessageService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🛡️ (2026-09-16) งานต่อจากเคส FTU-260916-C5482
 *
 * 1. hasPaidActiveReading() — จ่ายแล้วค้างกลางขั้นตอน + ลูกค้าเงียบ > 2 ชม. ต้องยังนับว่า "กำลังทำนาย"
 *    (เดิมตัดที่ updated_at 2 ชม. ทั้งที่ findActiveConversation ยังเห็นบิลนั้น)
 * 2. กล่องจ่ายเงิน FB ส่งทีละชิ้น — บิลถูกปิดระหว่างส่ง ต้องหยุดชิ้นที่เหลือ
 *    (เดิม QR + หมายเหตุบิลของบิลซ้อนที่ reconcile ปิดไปแล้ว ยังส่งตามออกไป)
 *
 * @group fortune-billing
 */
class FortunePaidActiveAndStalePaymentBoxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน
        FortuneTellingSetting::clearSettingsCache();
    }

    private function reading(string $psid, string $status, bool $paid, array $state = []): FortuneReading
    {
        $reading = FortuneReading::create([
            'facebook_user_id' => $psid,
            'facebook_user_name' => 'ทดสอบ',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => $status,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => $psid,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => $paid,
            'paid_at' => $paid ? now() : null,
            // ⚠️ amount_paid เป็น NOT NULL ในสคีมา → ใช้ 0 ไม่ใช่ null
            'amount_paid' => $paid ? 39.00 : 39.65,
        ]);

        foreach ($state as $k => $v) {
            $reading->setConversationState($k, $v);
        }

        return $reading->fresh();
    }

    /** ย้อนเวลาแถวโดยไม่ให้ Eloquent ทับ updated_at */
    private function backdate(FortuneReading $reading, array $columns): void
    {
        DB::table('fortune_readings')->where('id', $reading->id)->update($columns);
    }

    private function paidActive(string $psid): bool
    {
        Cache::flush();

        return (new FortuneConversationService(FortuneTellingSetting::getSettings()))->hasPaidActiveReading($psid);
    }

    /** 1️⃣ จ่ายแล้วรอวันเกิด ลูกค้าเงียบ 5 ชม. — ยังต้องนับว่ากำลังทำนาย */
    public function test_จ่ายแล้วค้างกลางขั้นตอน_เงียบเกิน2ชม_ยังนับว่ากำลังทำนาย(): void
    {
        $r = $this->reading('psid-idle', FortuneReading::STATUS_COLLECTING_BIRTHDATE, true);
        $this->backdate($r, ['paid_at' => now()->subHours(5), 'updated_at' => now()->subHours(5)]);

        $this->assertTrue($this->paidActive('psid-idle'));
    }

    /** 2️⃣ บิลค้างประวัติศาสตร์ (จ่ายเกิน 30 วัน) ไม่นับ — ต่อให้ updated_at เพิ่งถูกงานกวาดข้อมูลแตะ */
    public function test_บิลจ่ายนานเกิน30วัน_ไม่นับแม้updated_atใหม่(): void
    {
        $r = $this->reading('psid-ancient', FortuneReading::STATUS_CELTIC_PICKING, true);
        $this->backdate($r, ['paid_at' => now()->subDays(40), 'updated_at' => now()->subMinutes(5)]);

        $this->assertFalse($this->paidActive('psid-ancient'));
    }

    /** 3️⃣ แอดมินรับเรื่องแล้ว (admin_review_alerted) ไม่นับ */
    public function test_แอดมินรับเรื่องแล้ว_ไม่นับ(): void
    {
        $this->reading('psid-review', FortuneReading::STATUS_COLLECTING_TAROT, true, ['admin_review_alerted' => true]);

        $this->assertFalse($this->paidActive('psid-review'));
    }

    /** 4️⃣ สาขา Pro Session (completed + pro_session_active) ยังคงเพดาน 2 ชม. เดิม */
    public function test_pro_sessionค้าง_ยังใช้เพดาน2ชม(): void
    {
        $fresh = $this->reading('psid-pro', FortuneReading::STATUS_COMPLETED, true, ['pro_session_active' => true]);
        $this->assertTrue($this->paidActive('psid-pro'), 'Pro Session ที่เพิ่งคุย ต้องนับ');

        $this->backdate($fresh, ['updated_at' => now()->subHours(3)]);
        $this->assertFalse($this->paidActive('psid-pro'), 'ธง pro_session_active ค้างเกิน 2 ชม. ต้องไม่นับ');
    }

    /** 5️⃣ ยังไม่จ่าย ไม่นับ */
    public function test_ยังไม่จ่าย_ไม่นับ(): void
    {
        $this->reading('psid-unpaid', FortuneReading::STATUS_PENDING_PAYMENT, false);

        $this->assertFalse($this->paidActive('psid-unpaid'));
    }

    /**
     * ตัวส่งปลอม — จดทุกชิ้นที่ส่ง · ตั้ง $closeAfterFirstText = true เพื่อจำลอง
     * "อีกโปรเซส reconcile ปิดบิลนี้" ทันทีหลังข้อความชิ้นแรกออกไป
     */
    private function recordingSender(FortuneReading $reading, bool $closeAfterFirstText): object
    {
        return new class($reading, $closeAfterFirstText) implements FortuneMessengerSender
        {
            public array $sent = [];

            public function __construct(private FortuneReading $reading, private bool $closeAfterFirstText) {}

            public function sendMessage(string $recipientId, string $message, array $options = []): bool
            {
                $this->sent[] = 'text';
                if ($this->closeAfterFirstText && count($this->sent) === 1) {
                    FortuneReading::whereKey($this->reading->id)
                        ->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                }

                return true;
            }

            public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
            {
                $this->sent[] = 'quick_replies';

                return true;
            }

            public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
            {
                $this->sent[] = 'image';

                return true;
            }

            public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
            {
                $this->sent[] = 'template';

                return true;
            }

            public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
            {
                $this->sent[] = 'generic';

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

    private function sendPaymentBox(FortuneMessengerSender $sender, FortuneReading $reading): bool
    {
        $manager = new class(FortuneTellingSetting::getSettings()) extends FortuneChannelManager
        {
            public function callPaymentBox(FortuneMessengerSender $s, array $result): bool
            {
                return $this->sendFacebookPaymentResponse($s, new FacebookRichMessageService($this->settings), 'psid-box', $result);
            }
        };

        return $manager->callPaymentBox($sender, [
            'action' => 'pending_payment',
            'message' => '💎 ค่าบูชาครู ฿39.65',
            'copyable_account' => '232-3-77534-9',
            'payment_qr_url' => 'https://example.test/qr.png',
            'reading' => $reading,
        ]);
    }

    /** 6️⃣ บิลถูกปิดระหว่างส่ง → หยุดชิ้นที่เหลือ (ไม่มี QR ตามออกไป) */
    public function test_บิลถูกปิดระหว่างส่งกล่องจ่ายเงิน_หยุดส่ง_q_rและชิ้นที่เหลือ(): void
    {
        $reading = $this->reading('psid-box', FortuneReading::STATUS_PENDING_PAYMENT, false);
        $sender = $this->recordingSender($reading, true);

        $this->assertTrue($this->sendPaymentBox($sender, $reading));

        $this->assertSame(['text'], $sender->sent, 'หลังบิลถูกปิด ต้องไม่ส่งเลขบัญชี/QR/หมายเหตุบิลตามออกไป');
    }

    /** 7️⃣ บิลยังเปิดอยู่ตลอด → ส่งครบทุกชิ้นตามเดิม */
    public function test_บิลยังเปิดอยู่_ส่งกล่องจ่ายเงินครบตามเดิม(): void
    {
        $reading = $this->reading('psid-box', FortuneReading::STATUS_PENDING_PAYMENT, false);
        $sender = $this->recordingSender($reading, false);

        $this->sendPaymentBox($sender, $reading);

        $this->assertContains('image', $sender->sent, 'บิลเปิดอยู่ต้องได้ QR ตามปกติ');
        $this->assertGreaterThanOrEqual(2, count(array_keys($sender->sent, 'text')), 'ต้องได้ข้อความบิล + เลขบัญชี');
    }
}
