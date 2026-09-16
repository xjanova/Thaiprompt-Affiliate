<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookRichMessageService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🔒 (2026-09-16) ดูดวง 39 จ่ายแล้วกลางขั้นตอน — "เริ่มใหม่ / ยกเลิก / ดูดวง" ห้ามปิดบิล ห้ามพาไปออกบิลใหม่
 *
 * เคสจริง Yee Luangsarre (FTU-260916-Z0948 / R13317 จ่าย 39 แล้ว):
 *   19:04 พิมพ์วันเกิดเพี้ยน → กล่อง invalid_birthdate แนบปุ่ม "🔄 เริ่มใหม่ / ❌ ยกเลิก"
 *   19:05 กด "เริ่มใหม่" → escape hatch ปิดบิลที่จ่ายแล้วเป็น completed + ส่งปุ่ม "💎 ดูดวง"
 *   19:05 กด "ดูดวง" → ออกบิลใหม่ FTU-260916-C5482 + QR ทวงเงิน ทั้งที่จ่ายไปแล้ว
 *   owner: "ระหว่างการทำนายต้องไม่มีปุ่ม หรือขั้นตอนอื่นมาขัดจังหวะ"
 *          "พิมพ์ ยกเลิก เริ่มใหม่เองก็ต้องไม่มีผล เพราะต้องไปตามขั้นตอนขณะทำนาย"
 *
 * @group fortune-billing
 */
class FortunePaidBirthdateNoRestartTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '26909359275322602';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน
        FortuneTellingSetting::clearSettingsCache();
    }

    private function service(): object
    {
        return new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            /** เปิดเมธอด protected ให้เทสต์เรียกตรง */
            public function callHandleBirthdateInput(FortuneReading $reading, string $text): array
            {
                return $this->handleBirthdateInput($reading, $text);
            }

            public function callHandleTarotCardDraw(FortuneReading $reading, string $text): array
            {
                return $this->handleTarotCardDraw($reading, $text);
            }

            public function callContinueConversation(FortuneReading $reading, string $text): array
            {
                return $this->continueConversation($reading, $text);
            }

            public function callCloseAllActiveConversations(string $userId): int
            {
                return $this->closeAllActiveConversations($userId);
            }
        };
    }

    /** บิล Deep 39 จ่ายแล้ว (Pay-First) ตามสถานะที่ระบุ */
    private function paidReading(string $status, array $state = [], bool $paid = true): FortuneReading
    {
        $reading = FortuneReading::create([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'Yee Luangsarre',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => $status,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => $paid,
            'paid_at' => $paid ? now() : null,
            // ⚠️ amount_paid เป็น NOT NULL ในสคีมา → ใช้ 0 ไม่ใช่ null
            'amount_paid' => $paid ? 39.00 : 0,
        ]);

        foreach (array_merge(['pay_first_mode' => true], $state) as $k => $v) {
            $reading->setConversationState($k, $v);
        }

        return $reading->fresh();
    }

    /**
     * 1️⃣ ขั้นวันเกิด — ทุกคำ "เริ่มใหม่/ยกเลิก/ดูดวง" (ทั้งพิมพ์เองและที่ปุ่ม RESTART/CANCEL แปลงมา)
     *    → บิลเดิมยังรอวันเกิด ไม่ปิด ไม่มีบิลใหม่
     */
    public function test_จ่ายแล้วรอวันเกิด_พิมพ์เริ่มใหม่หรือยกเลิก_บิลเดิมไม่ถูกปิด(): void
    {
        foreach (['เริ่มใหม่', 'ยกเลิก', 'ดูดวง', 'restart', 'cancel', 'ยกเลิก ค่ะ'] as $text) {
            FortuneReading::query()->forceDelete();
            $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_BIRTHDATE);

            $result = $this->service()->callHandleBirthdateInput($reading, $text);

            $this->assertSame('collecting_birthdate', $result['action'], "[{$text}] ต้องขอวันเกิดต่อ ไม่ใช่ออกจาก flow");
            $this->assertStringContainsString('ชำระแล้ว', $result['message'], "[{$text}] ต้องบอกลูกค้าว่าจ่ายแล้ว ไม่ต้องเริ่มใหม่");

            $fresh = $reading->fresh();
            $this->assertSame(
                FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                $fresh->conversation_status,
                "[{$text}] ห้ามปิดบิลที่จ่ายแล้ว"
            );
            $this->assertTrue((bool) $fresh->is_paid);
            $this->assertSame(1, FortuneReading::where('facebook_user_id', self::PSID)->count(), "[{$text}] ห้ามมีบิลใหม่");
        }
    }

    /** 2️⃣ "เริ่มใหม่" = เริ่มกรอกวันเกิดใหม่ — ล้างสถานะกรอกค้าง ไม่นับเป็นวันเกิดผิด */
    public function test_เริ่มใหม่_ล้างวันเกิดที่กรอกค้าง_ไม่นับครั้งผิด(): void
    {
        $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_BIRTHDATE, [
            'birthdate_step_mode' => true,
            'birthdate_partial' => ['year' => 2505],
            'birthdate_attempts' => 1,
        ]);

        $this->service()->callHandleBirthdateInput($reading, 'เริ่มใหม่');

        $fresh = $reading->fresh();
        $this->assertFalse((bool) $fresh->getConversationState('birthdate_step_mode', false));
        $this->assertSame([], $fresh->getConversationState('birthdate_partial', []));
        $this->assertSame(0, (int) $fresh->getConversationState('birthdate_attempts', 0));
    }

    /** 3️⃣ หลังเริ่มใหม่ พิมพ์วันเกิดจริง ต้องรับได้ตามปกติ (บิลเดิมเดินต่อ) */
    public function test_หลังเริ่มใหม่_พิมพ์วันเกิดจริง_รับได้(): void
    {
        $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_BIRTHDATE);
        $svc = $this->service();

        $svc->callHandleBirthdateInput($reading, 'เริ่มใหม่');
        $svc->callHandleBirthdateInput($reading->fresh(), '21/6/2505');

        $this->assertSame('1962-06-21', optional($reading->fresh()->birth_date)->format('Y-m-d'));
    }

    /** 4️⃣ กล่องขั้นวันเกิดต้องไม่มีปุ่มใด ๆ — ทั้งตาราง FB หลัก/สำรอง และตารางกลาง */
    public function test_กล่องขั้นวันเกิด_ไม่มีปุ่มเริ่มใหม่หรือยกเลิก(): void
    {
        $manager = new class(FortuneTellingSetting::getSettings()) extends FortuneChannelManager
        {
            public function fbFallbackQuickReplies(string $action): array
            {
                return $this->getFacebookFallbackQuickReplies($action, [], false);
            }

            public function genericQuickReplies(string $action): array
            {
                return $this->getQuickReplies($action);
            }
        };

        $rich = app(FacebookRichMessageService::class);

        foreach (['invalid_birthdate', 'retry_birthdate', 'collecting_birthdate'] as $action) {
            $this->assertEmpty($rich->getQuickRepliesForAction($action), "[{$action}] ตาราง FB หลักต้องไม่มีปุ่ม");
            $this->assertSame([], $manager->fbFallbackQuickReplies($action), "[{$action}] ตาราง FB สำรองต้องไม่มีปุ่ม");
            $this->assertSame([], $manager->genericQuickReplies($action), "[{$action}] ตารางกลางต้องไม่มีปุ่ม");
        }
    }

    /** 5️⃣ ขั้นตั้งจิต/เปิดไพ่ — "ยกเลิก/เริ่มใหม่" ไม่ปิดบิล พากลับขั้นเดิม */
    public function test_จ่ายแล้วขั้นตั้งจิต_พิมพ์ยกเลิก_พากลับขั้นเดิม(): void
    {
        foreach (['ยกเลิก', 'เริ่มใหม่'] as $text) {
            FortuneReading::query()->forceDelete();
            $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_TAROT, [
                'tarot_intention_prompted_at' => now()->toIso8601String(),
            ]);

            $result = $this->service()->callHandleTarotCardDraw($reading, $text);

            $this->assertSame('awaiting_tarot_intention', $result['action'], "[{$text}] ต้องพากลับขั้นตั้งจิต");
            $this->assertSame(FortuneReading::STATUS_COLLECTING_TAROT, $reading->fresh()->conversation_status, "[{$text}] ห้ามปิดบิล");
        }

        // ตั้งจิตแล้ว → พากลับขั้นเปิดไพ่
        FortuneReading::query()->forceDelete();
        $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_TAROT, ['tarot_intention_confirmed' => true]);
        $this->assertSame('awaiting_tarot_draw', $this->service()->callHandleTarotCardDraw($reading, 'ยกเลิก')['action']);
    }

    /** 6️⃣ ด่านยกเลิกใน continueConversation (isCancelRequest) ก็ต้องไม่ปิดบิลที่จ่ายแล้ว */
    public function test_ด่านยกเลิกหลัก_จ่ายแล้วกลางขั้นตอน_ไม่ปิดบิล(): void
    {
        $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_TAROT);

        $result = $this->service()->callContinueConversation($reading, 'ยกเลิกค่ะ ไม่เอาแล้ว');

        $this->assertNotSame('cancelled', $result['action']);
        $this->assertSame(FortuneReading::STATUS_COLLECTING_TAROT, $reading->fresh()->conversation_status);
    }

    /** 7️⃣ บิลยังไม่จ่าย (เส้น legacy) — "ยกเลิก" ยังปิดได้ตามเดิม ไม่ได้ล็อกทุกคน */
    public function test_ยังไม่จ่าย_ขั้นเปิดไพ่_ยกเลิกได้ตามเดิม(): void
    {
        $reading = $this->paidReading(FortuneReading::STATUS_COLLECTING_TAROT, [], false);

        $result = $this->service()->callHandleTarotCardDraw($reading, 'ยกเลิก');

        $this->assertSame('cancelled', $result['action']);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $reading->fresh()->conversation_status);
    }

    /** 8️⃣ ตาข่ายชั้นสุดท้าย — closeAllActiveConversations ไม่แตะบิลที่จ่ายแล้ว แต่ยังปิดแถวที่ไม่จ่าย */
    public function test_ปิดบทสนทนาค้างทั้งหมด_ไม่แตะบิลที่จ่ายแล้ว(): void
    {
        $paid = $this->paidReading(FortuneReading::STATUS_COLLECTING_BIRTHDATE);
        $menu = $this->paidReading(FortuneReading::STATUS_TIER_CHOICE, [], false);

        $closed = $this->service()->callCloseAllActiveConversations(self::PSID);

        $this->assertSame(1, $closed);
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $paid->fresh()->conversation_status, 'บิลที่จ่ายแล้วต้องรอดเสมอ');
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $menu->fresh()->conversation_status, 'แถวเมนูที่ไม่จ่ายยังต้องถูกปิดตามเดิม');
    }
}
