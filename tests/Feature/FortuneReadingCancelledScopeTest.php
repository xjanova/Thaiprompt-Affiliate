<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🏷️ ตัวกรองบิลยกเลิกหน้ารายการคำทำนาย (2026-09-13)
 *
 * บิลยกเลิกไม่มีสถานะของตัวเอง = completed + ยังไม่จ่าย + มี cancellation_reason เป็นข้อความ
 * เดิมตัวกรอง "cancelled" / "expired" ค้นคอลัมน์ตรง ๆ ⇒ 0 แถวเสมอ ทั้งที่ prod มี 1,410 ใบ
 *
 * ล็อก 3 อย่าง:
 *   1. scopeCancelled() ต้องได้ชุดเดียวกับ isCancelled() เป๊ะ (prod ตรวจแล้ว 1,410 = 1,410)
 *   2. คีย์ที่ค่าเป็น JSON null / บิลที่จ่ายแล้ว / สถานะอื่น ต้องไม่นับเป็นบิลยกเลิก (กับดักจริงบน prod)
 *   3. completed + notCancelled ต้องไม่ทำแถวหาย — NOT (...) บนแถว state ว่างต้องไม่กลายเป็น NULL
 */
class FortuneReadingCancelledScopeTest extends TestCase
{
    use RefreshDatabase;

    /** สร้างบิลทดสอบ — คืน id */
    private function makeReading(string $status, bool $paid, ?array $state): int
    {
        $reading = FortuneReading::create([
            'platform' => 'facebook',
            'platform_user_id' => '61550000000099',
            'facebook_user_id' => '61550000000099',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => $status,
            'questions' => [],
            'is_paid' => $paid,
            'amount_paid' => 39,
        ]);

        // เขียน state ตรงลงคอลัมน์ — เลี่ยง setConversationState() ที่อาจเติมคีย์อื่นให้
        $reading->forceFill(['conversation_state' => $state])->save();

        return $reading->id;
    }

    #[Test]
    public function cancelled_scope_matches_is_cancelled_exactly(): void
    {
        $expired = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => 'auto_expired']);
        $byCustomer = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => 'user_cancelled']);
        $grace = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => 'auto_expired_grace']);
        // ⬇️ กับดัก — ต้องไม่นับเป็นบิลยกเลิก
        $jsonNull = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => null]);
        $emptyReason = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => '']);
        $paidLater = $this->makeReading(FortuneReading::STATUS_COMPLETED, true, ['cancellation_reason' => 'user_cancelled']);
        $noState = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, null);
        $otherStatus = $this->makeReading(FortuneReading::STATUS_PENDING_PAYMENT, false, ['cancellation_reason' => 'auto_expired']);

        $cancelled = FortuneReading::cancelled()->orderBy('id')->pluck('id')->all();
        $this->assertSame([$expired, $byCustomer, $grace], $cancelled);

        // ชุดเดียวกับ isCancelled() ของ PHP ทุกใบ
        $byPhp = FortuneReading::orderBy('id')->get()->filter(fn (FortuneReading $r) => $r->isCancelled())->pluck('id')->values()->all();
        $this->assertSame($byPhp, $cancelled);

        // หมดเวลา = เฉพาะที่ระบบยกเลิกเพราะไม่จ่ายในเวลา
        $this->assertSame([$expired, $grace], FortuneReading::cancelled(['auto_expired', 'auto_expired_grace'])->orderBy('id')->pluck('id')->all());

        // completed ที่ไม่ใช่บิลยกเลิก — แถว state ว่าง/JSON null ต้องอยู่ครบ ไม่หายเพราะ NULL
        $completedNotCancelled = FortuneReading::where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->notCancelled()->orderBy('id')->pluck('id')->all();
        $this->assertSame([$jsonNull, $emptyReason, $paidLater, $noState], $completedNotCancelled);

        // completed ทั้งหมด = ยกเลิก + ไม่ยกเลิก (ไม่มีแถวตกหล่น)
        $this->assertSame(
            FortuneReading::where('conversation_status', FortuneReading::STATUS_COMPLETED)->count(),
            count($cancelled) + count($completedNotCancelled)
        );
        $this->assertNotContains($otherStatus, $cancelled);
    }

    /**
     * ตัวกรองหน้ารายการ: บิลยกเลิกแบบ completed + สถานะดิบ 'cancelled' (แอป SMS Checker ปฏิเสธบิล)
     * + สถานะดิบ 'expired' ของโค้ดเก่า ต้องค้นเจอครบ — ตัวกรองเดิมค้นเจอแถวสถานะดิบ ห้ามทำหาย
     */
    #[Test]
    public function admin_filter_values_find_every_cancelled_shape(): void
    {
        $expired = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => 'auto_expired']);
        $byCustomer = $this->makeReading(FortuneReading::STATUS_COMPLETED, false, ['cancellation_reason' => 'user_cancelled']);
        $rejectedFromApp = $this->makeReading('cancelled', false, null);
        $legacyExpired = $this->makeReading('expired', true, null);
        $delivered = $this->makeReading(FortuneReading::STATUS_COMPLETED, true, null);
        $pending = $this->makeReading(FortuneReading::STATUS_PENDING_PAYMENT, false, null);

        $ids = fn (string $value) => FortuneReading::filterConversationStatus($value)->orderBy('id')->pluck('id')->all();

        $this->assertSame([$expired, $byCustomer, $rejectedFromApp], $ids('cancelled'));
        $this->assertSame([$expired, $legacyExpired], $ids('expired'));
        $this->assertSame([$delivered], $ids(FortuneReading::STATUS_COMPLETED));
        $this->assertSame([$pending], $ids(FortuneReading::STATUS_PENDING_PAYMENT));
    }
}
