<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\OtpSetting;
use App\Models\OtpVerification;
use App\Models\UniquePaymentAmount;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ⏱️ (2026-10-04) คอลัมน์เวลาที่ prod MariaDB เคยเขียนทับเป็น "ตอนนี้" ทุกครั้งที่แก้แถว
 *
 * prod: explicit_defaults_for_timestamp=0 → TIMESTAMP ตัวแรกที่ไม่ระบุ default ได้ ON UPDATE CURRENT_TIMESTAMP
 * (MySQL 8 ใน CI ไม่เป็น — เทสต์นี้ล็อก "พฤติกรรมที่ตั้งใจ" ของโค้ด หลัง migration ถอด ON UPDATE แล้ว)
 *
 * - ยอดจอง: ยกเลิก = การจองจบตอนยกเลิก (ช่วงผ่อนผันรับเงินโอนช้าพึ่งค่านี้) — เดิมได้มาโดยบังเอิญจาก ON UPDATE
 * - OTP: พิมพ์ผิดหนึ่งครั้งต้องไม่ทำให้ OTP หมดอายุ (เดิม increment('attempts') = expires_at กลายเป็นตอนนี้)
 *
 * @group fortune-billing
 */
class TimestampColumnsDoNotAutoUpdateTest extends TestCase
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
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function upa(float $amount, $expiresAt, string $status = 'reserved'): UniquePaymentAmount
    {
        return UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create([
            'base_amount' => floor($amount),
            'unique_amount' => $amount,
            'decimal_suffix' => (int) round(($amount - floor($amount)) * 100),
            'transaction_type' => 'fortune_reading',
            'status' => $status,
            'expires_at' => $expiresAt,
            'created_at' => now()->subMinutes(5),
        ]));
    }

    public function test_cancelling_a_reservation_ends_it_now_but_never_extends_an_expired_one(): void
    {
        $future = $this->upa(39.41, now()->addHours(3));
        $future->cancel();
        $this->assertSame('cancelled', $future->fresh()->status);
        $this->assertTrue($future->fresh()->expires_at->lte(now()), 'ยกเลิกก่อนครบเวลา = การจองจบตอนยกเลิก');
        $this->assertTrue($future->fresh()->expires_at->gt(now()->subMinute()));

        $past = $this->upa(39.42, now()->subHours(2), 'expired');
        $past->cancel();
        $this->assertSame(
            now()->subHours(2)->startOfSecond()->toDateTimeString(),
            $past->fresh()->expires_at->toDateTimeString(),
            'หมดอายุไปแล้ว = คงเวลาเดิม ไม่ยืดช่วงผ่อนผัน'
        );
    }

    public function test_money_for_a_bill_cancelled_minutes_ago_is_still_matched_by_the_grace_window(): void
    {
        $upa = $this->upa(39.43, now()->addHours(3));
        $reading = FortuneReading::create([
            'facebook_user_id' => '27466581493002956',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COMPLETED, // บิลถูกปิดไปแล้ว ยังไม่จ่าย
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => '27466581493002956',
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => false,
            'unique_payment_amount_id' => $upa->id,
        ]);
        $upa->forceFill(['transaction_id' => $reading->id])->save();

        $upa->cancel(); // ลูกค้ายกเลิกบิล 5 นาทีหลังเปิด — เวลาจองเดิมยังเหลือเกือบ 3 ชม.
        $this->travel(2)->minutes();

        $grace = new \ReflectionMethod(SmsPaymentService::class, 'findFortuneReadingByExpiredAmount');
        $found = $grace->invoke(app(SmsPaymentService::class), 39.43, now());

        $this->assertSame($reading->id, $found?->id, 'เงินที่โอนตามมาหลังยกเลิกต้องยังจับคู่บิลได้ (ไม่ตกเป็นเงินกำพร้า)');
    }

    public function test_a_wrong_otp_attempt_does_not_expire_the_code(): void
    {
        OtpSetting::query()->delete();
        OtpSetting::unguarded(fn () => OtpSetting::create(['enabled' => true, 'provider' => 'custom', 'max_attempts' => 3, 'otp_length' => 6]));

        $otp = OtpVerification::createForPhone('0812345678', 'phone_verification', 5);
        $expiresAt = $otp->fresh()->expires_at->toDateTimeString();
        $wrong = $otp->otp_code === '000000' ? '111111' : '000000';

        $this->travel(5)->seconds();
        $this->assertFalse(OtpVerification::verifyOTP('0812345678', $wrong));
        $this->assertSame($expiresAt, $otp->fresh()->expires_at->toDateTimeString(), 'พิมพ์ผิดต้องไม่เปลี่ยนเวลาหมดอายุ');

        $this->travel(5)->seconds();
        $this->assertTrue(OtpVerification::verifyOTP('0812345678', $otp->otp_code), 'ยังเหลือสิทธิ์ พิมพ์ถูกต้องผ่าน');
    }
}
