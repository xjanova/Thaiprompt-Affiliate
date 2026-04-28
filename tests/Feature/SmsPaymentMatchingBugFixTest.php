<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ทดสอบ Bug Fix: SMS Payment Matching (2026-04-28)
 *
 * บั๊กเก่า: SMS ที่มาก่อนบิลถูกสร้าง → match กับบิลใหม่ผิด
 *
 * Fix:
 *   1. Temporal binding — SMS ต้องมาหลัง bill (UPA.created_at <= sms_timestamp)
 *   2. SMS dedup — notification ที่ matched_transaction_id ไม่ null ห้าม reuse
 *   3. FIFO — bill เก่าสุด match ก่อน
 *   4. ลบ amount_paid fallback ที่อันตราย
 */
class SmsPaymentMatchingBugFixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario 1: Bill A paid → Bill B รอ → SMS เก่าของ A reprocess
     * ก่อนแก้: B ได้ตัด (ผิด)
     * หลังแก้: skip — ไม่ match B เพราะ SMS timestamp < B.created_at
     */
    public function test_old_sms_does_not_match_new_bill_with_same_amount(): void
    {
        // 10:00 — Bill A สร้าง + จ่ายแล้ว
        Carbon::setTestNow('2026-04-28 10:00:00');
        $upaA = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.47,
            'decimal_suffix' => 47,
            'transaction_type' => 'fortune_reading',
            'status' => 'used',
            'expires_at' => now()->addMinutes(60),
        ]);

        // 10:25 — SMS ของ A เข้า
        Carbon::setTestNow('2026-04-28 10:25:00');
        $smsA = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 39.47,
            'sms_timestamp' => now(),
            'device_id' => 'test-device',
            'nonce' => 'nonce-old-A',
            'status' => 'matched',
            'matched_transaction_id' => 999, // matched ไป reading A แล้ว
            'raw_payload' => '{}',
        ]);

        // 11:00 — Bill B สร้าง (ลูกค้าใหม่ ยังไม่ได้จ่าย)
        Carbon::setTestNow('2026-04-28 11:00:00');
        $upaB = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.47,
            'decimal_suffix' => 47,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        // ✅ ใช้ SMS timestamp ของ A (10:25) — ต้องไม่ match Bill B (created 11:00)
        $smsTimestamp = Carbon::parse('2026-04-28 10:25:00');
        $found = UniquePaymentAmount::findMatch(39.47, 'fortune_reading', $smsTimestamp);

        $this->assertNull($found, 'SMS เก่ากว่า Bill ห้าม match');
    }

    /**
     * Scenario 2: ใช้ SMS ใหม่ที่มาหลัง Bill B → ควร match Bill B ปกติ
     */
    public function test_new_sms_matches_new_bill_correctly(): void
    {
        // 11:00 — Bill B สร้าง
        Carbon::setTestNow('2026-04-28 11:00:00');
        $upaB = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.47,
            'decimal_suffix' => 47,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        // 11:05 — SMS ใหม่เข้า
        $smsTimestamp = Carbon::parse('2026-04-28 11:05:00');
        $found = UniquePaymentAmount::findMatch(39.47, 'fortune_reading', $smsTimestamp);

        $this->assertNotNull($found, 'SMS ใหม่ต้อง match Bill B');
        $this->assertEquals($upaB->id, $found->id);
    }

    /**
     * Scenario 3: FIFO — bill เก่าสุด match ก่อน
     * ถ้ามี Bill X (10:00 reserved) + Bill Y (10:30 reserved) ยอดเดียวกัน
     * → SMS ใหม่ควร match Bill X ก่อน (FIFO)
     */
    public function test_fifo_matches_older_bill_first(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');
        $upaX = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.99,
            'decimal_suffix' => 99,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        Carbon::setTestNow('2026-04-28 10:30:00');
        $upaY = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.99,
            'decimal_suffix' => 99,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        Carbon::setTestNow('2026-04-28 10:45:00');
        $found = UniquePaymentAmount::findMatch(39.99, 'fortune_reading', now());

        $this->assertEquals($upaX->id, $found->id, 'FIFO: Bill เก่าสุดต้อง match ก่อน');
    }

    /**
     * Scenario 4: SMS ก่อน bill ทั้ง 2 ตัว → ไม่ match อะไรเลย
     */
    public function test_sms_before_all_bills_matches_nothing(): void
    {
        Carbon::setTestNow('2026-04-28 11:00:00');
        UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.50,
            'decimal_suffix' => 50,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        // SMS มาก่อน bill 30 นาที
        $smsTimestamp = Carbon::parse('2026-04-28 10:30:00');
        $found = UniquePaymentAmount::findMatch(39.50, 'fortune_reading', $smsTimestamp);

        $this->assertNull($found, 'SMS ที่มาก่อน bill ทั้งหมดต้องไม่ match');
    }

    /**
     * Scenario 5: ไม่ส่ง smsTimestamp → behavior เดิม (backward compat)
     */
    public function test_without_sms_timestamp_works_as_before(): void
    {
        Carbon::setTestNow('2026-04-28 11:00:00');
        $upa = UniquePaymentAmount::create([
            'base_amount' => 39,
            'unique_amount' => 39.50,
            'decimal_suffix' => 50,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ]);

        $found = UniquePaymentAmount::findMatch(39.50, 'fortune_reading');

        $this->assertNotNull($found);
        $this->assertEquals($upa->id, $found->id);
    }
}
