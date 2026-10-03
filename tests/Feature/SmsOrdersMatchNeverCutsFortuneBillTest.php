<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\SmsPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🚨 (2026-10-03, บิล FTU-261002-X6634) /orders/match ต้องไม่เอา SMS เก่ามาเป็นหลักฐานของบิลใหม่
 *
 * แอพ SmsChecker เรียก /orders/match ก่อน /notify ราว 2 วิ — เส้นนี้ตัดบิลทันที (ตัวตัดบิลอัตโนมัติตัวจริง)
 * แต่เคยหยิบ "SMS ยอดเดียวกันที่ยังไม่ผูก ตัวล่าสุด" (SMS กำพร้าของ 21 ก.ค.) มาผูกบิลวันที่ 2 ต.ค.
 * → หน้า billing โชว์ SMS ผิดใบ แอดมินยกเลิกบิลที่ลูกค้าจ่ายจริง / SMS ใบจริงกลายเป็นเงินกำพร้า
 *
 * ที่ถูก: /orders/match ผูกได้เฉพาะ SMS ที่มาหลังเปิดบิล · SMS ใบจริงที่มาทาง /notify ทีหลังถูกผูกเข้าบิลนั้น
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

        // คอลัมน์ที่ /orders/match + confirmPayment + ตัวแปลงบิลให้แอพอ่าน (ตาราง fortune_readings ของ trait มีแค่ขั้นต่ำ)
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

    private function sms(float $amount, $createdAt, string $status = 'requires_admin_review'): SmsPaymentNotification
    {
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'sender_or_receiver' => 'X-4572',
            'sms_timestamp' => $createdAt,
            'device_id' => 'SMSCHK-MATCH01',
            'nonce' => Str::random(24),
            'status' => $status,
        ]);
        $sms->forceFill(['created_at' => $createdAt])->save();

        return $sms;
    }

    /** SMS กำพร้าเก่าเป็นเดือน ยอดเดียวกับบิลใหม่ — แบบ id 1871 บน prod */
    private function staleOrphanSms(float $amount): SmsPaymentNotification
    {
        return $this->sms($amount, now()->subDays(73));
    }

    /**
     * บิลรอจ่าย — ไม่ใส่ user id เพื่อให้ dispatchFortuneApprovalFlow ไม่ไปปลุกงานทำนาย (process แยก) ในเทสต์
     */
    private function pendingBill(float $amount): FortuneReading
    {
        $openedAt = now()->subMinutes(2);
        $upa = UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create([
            'base_amount' => floor($amount),
            'unique_amount' => $amount,
            'decimal_suffix' => (int) round(($amount - floor($amount)) * 100),
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'created_at' => $openedAt,
            'expires_at' => now()->addHours(3),
        ]));

        $id = DB::table('fortune_readings')->insertGetId([
            'bill_reference' => 'FTU-261002-X6634',
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'is_paid' => false,
            'unique_payment_amount_id' => $upa->id,
            'amount_paid' => $amount,
            'platform' => 'facebook',
            'facebook_user_name' => 'ลูกค้าทดสอบ',
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ]);
        $upa->forceFill(['transaction_id' => $id])->save();

        return FortuneReading::findOrFail($id);
    }

    private function match(float $amount)
    {
        return $this->getJson('/api/v1/sms-payment/orders/match?amount='.$amount, ['X-Api-Key' => $this->apiKey]);
    }

    public function test_orders_match_cuts_the_bill_but_never_attaches_an_old_sms(): void
    {
        $stale = $this->staleOrphanSms(39.34);
        $bill = $this->pendingBill(39.34);

        $this->match(39.34)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.order.order_details_json.order_number', 'FTU-261002-X6634')
            ->assertJsonPath('data.order.approval_status', 'auto_approved');

        $fresh = $bill->fresh();
        $this->assertTrue((bool) $fresh->is_paid, 'การตัดบิลอัตโนมัติต้องทำงานเหมือนเดิม');
        $this->assertNull($fresh->sms_notification_id, 'SMS ของ 73 วันก่อนห้ามเป็นหลักฐานของบิลนี้');
        $this->assertNull($stale->fresh()->matched_transaction_id);
        $this->assertSame('requires_admin_review', $stale->fresh()->status);
    }

    public function test_orders_match_attaches_an_sms_that_already_arrived_after_the_bill(): void
    {
        $stale = $this->staleOrphanSms(39.34);
        $bill = $this->pendingBill(39.34);
        $real = $this->sms(39.34, now(), 'pending');

        $this->match(39.34)->assertOk()->assertJsonPath('data.order.approval_status', 'auto_approved');

        $this->assertSame($real->id, (int) $bill->fresh()->sms_notification_id);
        $this->assertSame('matched', $real->fresh()->status);
        $this->assertSame($bill->id, (int) $real->fresh()->matched_transaction_id);
        $this->assertNull($stale->fresh()->matched_transaction_id);
    }

    public function test_real_sms_arriving_after_orders_match_is_attached_to_that_bill_not_orphaned(): void
    {
        $this->staleOrphanSms(39.34);
        $bill = $this->pendingBill(39.34);

        // 1) แอพเรียก /orders/match ก่อน → ตัดบิล (ยังไม่มี SMS ใบจริง)
        $this->match(39.34)->assertOk();
        $this->assertTrue((bool) $bill->fresh()->is_paid);

        // 2) อีก ~2 วิ SMS ใบจริงมาทาง /notify
        $result = app(SmsPaymentService::class)->processNotification([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 39.34,
            'account_number' => '',
            'sender_or_receiver' => '',
            'reference_number' => '',
            'sms_timestamp' => now()->getTimestampMs(),
            'device_id' => 'SMSCHK-MATCH01',
            'nonce' => Str::random(24),
        ], SmsCheckerDevice::where('device_id', 'SMSCHK-MATCH01')->firstOrFail(), '127.0.0.1');

        $this->assertTrue($result['data']['matched']);
        $this->assertTrue($result['data']['fortune_reading']);
        $this->assertSame('matched', $result['data']['status'], 'ห้ามตกเป็นเงินกำพร้า');
        $this->assertSame($bill->id, (int) $result['data']['matched_transaction_id']);
        $this->assertSame((int) $result['data']['notification_id'], (int) $bill->fresh()->sms_notification_id);
    }
}
