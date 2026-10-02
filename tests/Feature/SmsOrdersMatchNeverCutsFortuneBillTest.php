<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🚨 (2026-10-03, บิล FTU-261002-X6634) GET /orders/match ห้ามตัดบิลดูดวงเอง
 *
 * แอพ SmsChecker เรียก /orders/match ก่อน /notify ราว 2 วิ — SMS ใบจริงยังไม่ถูกบันทึก
 * เส้นนี้เคยหยิบ "SMS ยอดเดียวกันที่ยังไม่ผูก ตัวล่าสุด" (SMS กำพร้าของ 21 ก.ค.) มาตัดบิลวันที่ 2 ต.ค.
 * → หลักฐานการจ่ายบนหน้า billing เป็นของคนอื่น / SMS ใบจริงตกเป็นเงินกำพร้า
 * บิลดูดวงต้องตัดผ่าน /notify เท่านั้น (SMS ใบจริง + HMAC + กัน SMS ที่มาก่อนเปิดบิล)
 */
class SmsOrdersMatchNeverCutsFortuneBillTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        Cache::flush();

        // คอลัมน์ที่ /orders/match + ตัวแปลงบิลให้แอพอ่าน (ตาราง fortune_readings ของ trait มีแค่ขั้นต่ำ)
        Schema::table('fortune_readings', function (Blueprint $table) {
            $table->string('platform')->nullable();
            $table->string('platform_user_id')->nullable();
            $table->string('facebook_user_id')->nullable();
            $table->string('facebook_user_name')->nullable();
            $table->unsignedBigInteger('sms_notification_id')->nullable();
            $table->string('sender_info')->nullable();
            $table->string('sender_bank')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('conversation_state')->nullable();
        });
        // เครื่องแอดมิน (store_id ว่าง) → resolveDeviceStoreId() ไปหา/สร้างร้าน Platform
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable();
            $table->softDeletes();
        });
        Schema::create('vendor_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('store_slug')->nullable();
            $table->string('store_name')->nullable();
            $table->text('store_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->apiKey = SmsCheckerDevice::generateApiKey();
        SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-MATCH01',
            'device_name' => 'มือถือรับเงินแม่หมอ',
            'api_key' => $this->apiKey,
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /** SMS กำพร้าเก่าเป็นเดือน ยอดเดียวกับบิลใหม่ — แบบ id 1871 บน prod */
    private function staleOrphanSms(float $amount): SmsPaymentNotification
    {
        return SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'sender_or_receiver' => 'X-4572',
            'sms_timestamp' => now()->subDays(73),
            'device_id' => 'SMSCHK-MATCH01',
            'nonce' => Str::random(24),
            'status' => 'requires_admin_review',
        ]);
    }

    private function billWithAmount(float $amount, string $status, string $upaStatus, array $upaTimes): FortuneReading
    {
        $upa = UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create(array_merge([
            'base_amount' => floor($amount),
            'unique_amount' => $amount,
            'decimal_suffix' => (int) round(($amount - floor($amount)) * 100),
            'transaction_type' => 'fortune_reading',
            'status' => $upaStatus,
        ], $upaTimes)));

        $id = DB::table('fortune_readings')->insertGetId([
            'bill_reference' => 'FTU-261002-X6634',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => $status,
            'is_paid' => false,
            'unique_payment_amount_id' => $upa->id,
            'amount_paid' => $amount,
            'platform' => 'facebook',
            'platform_user_id' => '35272642915716630',
            'facebook_user_id' => '35272642915716630',
            'facebook_user_name' => 'ลูกค้าทดสอบ',
            'created_at' => $upaTimes['created_at'],
            'updated_at' => $upaTimes['created_at'],
        ]);
        $upa->forceFill(['transaction_id' => $id])->save();

        return FortuneReading::findOrFail($id);
    }

    private function match(float $amount)
    {
        return $this->getJson('/api/v1/sms-payment/orders/match?amount='.$amount, ['X-Api-Key' => $this->apiKey]);
    }

    public function test_orders_match_shows_the_pending_bill_but_never_cuts_it_with_an_old_sms(): void
    {
        $stale = $this->staleOrphanSms(39.34);
        $bill = $this->billWithAmount(39.34, FortuneReading::STATUS_PENDING_PAYMENT, 'reserved', [
            'created_at' => now()->subMinutes(2),
            'expires_at' => now()->addHours(3),
        ]);

        $this->match(39.34)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.order.order_details_json.order_number', 'FTU-261002-X6634')
            ->assertJsonPath('data.order.approval_status', 'pending_review');

        $fresh = $bill->fresh();
        $this->assertFalse((bool) $fresh->is_paid, 'GET ที่ส่งมาแค่ยอดเงินห้ามตัดบิล');
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $fresh->conversation_status);
        $this->assertNull($fresh->sms_notification_id);
        $this->assertSame('reserved', UniquePaymentAmount::find($fresh->unique_payment_amount_id)->status);

        // SMS เก่าต้องไม่ถูกเอามาเป็นหลักฐานของบิลนี้ — และ /notify ก็ต้องไม่รับมันด้วย (มาก่อนเปิดบิล)
        $this->assertNull($stale->fresh()->matched_transaction_id);
        $this->assertNull(FortuneReading::findByUniqueAmount(39.34, $stale->sms_timestamp));

        // SMS ใบจริงที่ /notify จะได้ (มาหลังเปิดบิล) ยังจับคู่บิลนี้ได้ตามปกติ
        $this->assertSame($bill->id, FortuneReading::findByUniqueAmount(39.34, now())?->id);
    }

    public function test_orders_match_does_not_reopen_a_bill_the_cleanup_already_closed(): void
    {
        // บิลหมดเวลาแล้ว (cleanup ปิดเป็น completed + ยอดหมดอายุ แต่ยังอยู่ในช่วง grace ของ /orders/match)
        $this->staleOrphanSms(39.21);
        $bill = $this->billWithAmount(39.21, FortuneReading::STATUS_COMPLETED, 'expired', [
            'created_at' => now()->subMinutes(40),
            'expires_at' => now()->subMinutes(10),
        ]);

        $this->match(39.21)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.order.approval_status', 'cancelled');

        $fresh = $bill->fresh();
        $this->assertFalse((bool) $fresh->is_paid);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $fresh->conversation_status,
            'กู้บิลกลับมาเป็นรอชำระได้เฉพาะตอน /notify เจอเงินเข้าจริง');
    }
}
