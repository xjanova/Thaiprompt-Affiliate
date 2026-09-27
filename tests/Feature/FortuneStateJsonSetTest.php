<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🧩 (2026-09-27) JSON_SET ทีละคีย์บน conversation_state ต้องเขียนได้จริง
 *
 * - state ว่างถูกเก็บเป็น "[]" (cast 'array') → JSON_SET คีย์ชื่อบนอาร์เรย์ = ไม่ทำอะไรเงียบ ๆ
 *   (ยิง SELECT อ่านอย่างเดียวบน prod MariaDB 10.6 แล้ว: แบบ COALESCE เดิมได้ "[]" กลับมาเหมือนเดิม)
 * - CAST(? AS JSON) = syntax error บน MariaDB → ตาข่ายคำถามค้างเลน 99 ไม่เคยเขียนได้ (log 2026-09-20 reading 13489)
 *
 * เทสต์นี้รันกับ MySQL (CI) — ฝั่ง MariaDB ยืนยันด้วยการยิง SELECT นิพจน์เดียวกันบน prod แล้ว
 *
 * @group fortune-billing
 */
class FortuneStateJsonSetTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_ID = 'U6343edd96a0797d37cea6bfada5afc25';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน
        FortuneTellingSetting::clearSettingsCache();
    }

    /**
     * บิลที่ conversation_state เป็นข้อความดิบตามที่ระบุ ("[]" = state ว่างที่ผ่าน cast 'array')
     */
    private function reading(string $type, string $status, bool $paid, string $rawState): FortuneReading
    {
        $reading = FortuneReading::create([
            'facebook_user_id' => self::LINE_ID,
            'facebook_user_name' => 'ลูกค้าทดสอบ',
            'questions' => [],
            'reading_type' => $type,
            'conversation_status' => $status,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'line',
            'platform_user_id' => self::LINE_ID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => $paid,
            'paid_at' => $paid ? now() : null,
            // ⚠️ amount_paid เป็น NOT NULL ในสคีมา → ใช้ 0 ไม่ใช่ null
            'amount_paid' => $paid ? 99.00 : 0,
        ]);

        DB::table($reading->getTable())->where('id', $reading->id)->update(['conversation_state' => $rawState]);

        return $reading->fresh();
    }

    public function test_stated_birth_date_is_saved_even_when_the_state_is_an_empty_array(): void
    {
        $reading = $this->reading(FortuneReading::READING_TYPE_DEEP, FortuneReading::STATUS_PENDING_PAYMENT, false, '[]');

        $this->assertTrue($reading->rememberStatedBirthDate('1978-06-27'));

        $fresh = $reading->fresh();
        $this->assertSame('1978-06-27', $fresh->getConversationState('stated_birth_date'), 'บอกลูกค้าว่าบันทึกแล้ว ต้องมีจริงใน DB');
        $this->assertNull($fresh->birth_date, 'บิล 39 จ่ายก่อน: ห้ามแตะคอลัมน์ก่อนจ่าย');
    }

    public function test_stated_birth_date_keeps_every_other_state_key(): void
    {
        $reading = $this->reading(
            FortuneReading::READING_TYPE_DEEP,
            FortuneReading::STATUS_PENDING_PAYMENT,
            false,
            json_encode(['pay_first_mode' => true, 'หมายเหตุ' => 'ทดสอบ'])
        );

        $reading->rememberStatedBirthDate('1978-06-27');

        $fresh = $reading->fresh();
        $this->assertTrue($fresh->getConversationState('pay_first_mode'));
        $this->assertSame('ทดสอบ', $fresh->getConversationState('หมายเหตุ'));
        $this->assertSame('1978-06-27', $fresh->getConversationState('stated_birth_date'));
    }

    public function test_quiet_pending_question_backup_is_written_as_a_list(): void
    {
        $reading = $this->reading(
            FortuneReading::READING_TYPE_CELTIC_CROSS,
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            true,
            json_encode(['celtic_generating' => true])
        );

        $service = new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            /** เปิดเมธอด protected ให้เทสต์เรียกตรง */
            public function callRememberCelticPendingQuietly(FortuneReading $reading, string $text): void
            {
                $this->rememberCelticPendingQuietly($reading, $text);
            }
        };

        $service->callRememberCelticPendingQuietly($reading, 'งานปีหน้าจะดีขึ้นไหมคะ');
        $service->callRememberCelticPendingQuietly($reading, 'แล้วเรื่องเงินล่ะคะ');

        $fresh = $reading->fresh();
        $this->assertSame(['งานปีหน้าจะดีขึ้นไหมคะ', 'แล้วเรื่องเงินล่ะคะ'], $fresh->getConversationState('celtic_pending_q'));
        $this->assertNotEmpty($fresh->getConversationState('celtic_pending_q_at'));
        $this->assertTrue($fresh->getConversationState('celtic_generating'), 'คีย์อื่นต้องอยู่ครบ');
    }

    public function test_expired_bill_gets_its_reason_even_when_the_state_is_an_empty_array(): void
    {
        $reading = $this->reading(FortuneReading::READING_TYPE_CELTIC_CROSS, FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, false, '[]');
        DB::table($reading->getTable())->where('id', $reading->id)->update(['updated_at' => now()->subDays(2)]);

        FortuneReading::expireOldConversations(self::LINE_ID);

        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $fresh->conversation_status);
        $this->assertSame('auto_expired', $fresh->getConversationState('cancellation_reason'));
    }
}
