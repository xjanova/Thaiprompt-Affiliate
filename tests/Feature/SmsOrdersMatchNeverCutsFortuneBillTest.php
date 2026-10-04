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
 * 🚨 (2026-10-03, บิล FTU-261002-X6634) GET /orders/match ห้ามตัดบิลดูดวง
 *
 * แอพ SmsChecker เรียก /orders/match ก่อน /notify ราว 2 วิ — เส้นนี้เคยตัดบิลเองทุกใบ และหยิบ
 * "SMS ยอดเดียวกันที่ยังไม่ผูก ตัวล่าสุด" (SMS กำพร้าของ 21 ก.ค.) มาผูกบิลวันที่ 2 ต.ค.
 * → หน้า billing โชว์ SMS ผิดใบ แอดมินยกเลิกบิลที่ลูกค้าจ่ายจริง / SMS ใบจริงกลายเป็นเงินกำพร้า
 *
 * ที่ถูก (2026-10-04): /orders/match แค่หาบิลให้แอพดู · ตัดบิลที่ /notify ทางเดียว
 * (เส้นตัดบิลของ /notify ทดสอบครบเส้นบน MySQL ใน SmsNotifyCutsFortuneBillTest)
 * · SMS ใบจริงที่มาถึงหลังบิลถูกอนุมัติมือไปก่อน ต้องถูกผูกเข้าบิลนั้น ไม่ใช่เงินกำพร้า
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

    public function test_orders_match_shows_the_bill_but_never_cuts_it(): void
    {
        $stale = $this->staleOrphanSms(39.34);
        $bill = $this->pendingBill(39.34);
        // SMS ใบที่มาหลังเปิดบิลก็ห้ามใช้ตัดบิลที่นี่ — ตัดบิลได้ทาง /notify อย่างเดียว
        $fresh = $this->sms(39.34, now(), 'pending');

        $this->match(39.34)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.order.order_details_json.order_number', 'FTU-261002-X6634')
            ->assertJsonPath('data.order.approval_status', 'pending_review');

        $after = $bill->fresh();
        $this->assertFalse((bool) $after->is_paid, 'GET ที่ส่งมาแค่ยอดเงินห้ามตัดบิล');
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $after->conversation_status);
        $this->assertNull($after->sms_notification_id);
        $this->assertSame('reserved', UniquePaymentAmount::find($after->unique_payment_amount_id)->status);

        foreach ([$stale, $fresh] as $sms) {
            $this->assertNull($sms->fresh()->matched_transaction_id);
        }
        $this->assertSame('requires_admin_review', $stale->fresh()->status);

        // /notify จะไม่รับ SMS เก่า (มาก่อนเปิดบิล) แต่รับ SMS ใบที่มาหลังเปิดบิล
        $this->assertNull(FortuneReading::findByUniqueAmount(39.34, $stale->sms_timestamp));
        $this->assertSame($bill->id, FortuneReading::findByUniqueAmount(39.34, now())?->id);
    }

    public function test_orders_match_does_not_reopen_a_bill_the_cleanup_already_closed(): void
    {
        $this->staleOrphanSms(39.21);
        $bill = $this->pendingBill(39.21);
        // บิลหมดเวลาแล้ว (cleanup ปิดเป็น completed + ยอดหมดอายุ แต่ยังอยู่ในช่วง grace ของ /orders/match)
        $bill->forceFill(['conversation_status' => FortuneReading::STATUS_COMPLETED])->save();
        UniquePaymentAmount::whereKey($bill->unique_payment_amount_id)
            ->update(['status' => 'expired', 'expires_at' => now()->subMinutes(10)]);

        $this->match(39.21)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.order.approval_status', 'cancelled');

        $after = $bill->fresh();
        $this->assertFalse((bool) $after->is_paid);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $after->conversation_status,
            'กู้บิลกลับมาเป็นรอชำระได้เฉพาะตอน /notify เจอเงินเข้าจริง');
    }

    public function test_real_sms_arriving_after_a_manual_approval_is_attached_to_that_bill_not_orphaned(): void
    {
        $this->staleOrphanSms(39.34);
        $bill = $this->pendingBill(39.34);

        // 1) แอดมิน/แอพกดอนุมัติไปก่อน SMS ใบจริงมาถึง (ยังไม่มี SMS ให้ผูก)
        $bill->confirmPayment(null);
        $this->assertTrue((bool) $bill->fresh()->is_paid);
        $this->assertNull($bill->fresh()->sms_notification_id);

        // 2) อีกไม่กี่วิ SMS ใบจริงมาทาง /notify
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
