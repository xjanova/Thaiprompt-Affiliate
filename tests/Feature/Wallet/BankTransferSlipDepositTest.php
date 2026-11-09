<?php

namespace Tests\Feature\Wallet;

use App\Models\PaymentTransaction;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletSetting;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentService;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🏦 ฝากเงินแบบโอนธนาคาร + แนบสลิป (บั๊กที่แก้ 9 พ.ย. 2026)
 *
 * เดิม processBankTransfer() เก็บไฟล์สลิปแล้วตอบ "รอแอดมินตรวจ 1-24 ชม." แต่ไม่สร้างแถวใดใน DB
 * → แอดมินไม่มีอะไรให้อนุมัติ ลูกค้าโอนเงินแล้วเงินไม่มีวันเข้า
 * และฟอร์มนี้โชว์บน prod อยู่ เพราะ getAvailablePaymentMethods() เปิด bank_transfer แบบ hardcode
 *
 * ที่ถูก:
 * - ปิด bank_transfer_enabled → ไม่โชว์ฟอร์ม + POST ถูกปฏิเสธ ไม่มีไฟล์ค้าง
 * - เปิด → สร้างบิล wallet_topup/bank_transfer_slip สถานะ pending ยอดตรงตามที่แจ้ง ไม่หมดอายุ สลิปอยู่ disk ส่วนตัว
 * - สลิปใบเดิมส่งซ้ำไม่ได้
 * - SMS ยอดเท่ากันที่มาทีหลังต้องไม่อนุมัติบิลนี้อัตโนมัติ (กันแนบสลิปปลอมแล้วกินเงินโอนของคนอื่น)
 * - แอดมินอนุมัติ (service หรือแอป SMS Checker) = เงินเข้าครั้งเดียว กดซ้ำไม่เข้าซ้ำ
 * - บิลเติมเงินที่ถูกยกเลิก/ล้มเหลว เรียก completePayment ซ้ำ ๆ ต้องไม่เติมเงิน
 *
 * @group wallet
 */
class BankTransferSlipDepositTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
    }

    /** เปิด/ปิดช่องทางโอนธนาคาร (แถวมาจาก migration ใหม่ ค่าเริ่มต้นปิด) */
    private function setBankTransferEnabled(bool $on): void
    {
        WalletSetting::updateOrCreate(
            ['key' => 'bank_transfer_enabled'],
            ['group' => 'payment_methods', 'value' => $on ? '1' : '0', 'type' => 'boolean', 'label' => 'โอนธนาคาร', 'is_active' => true]
        );
        Cache::forget('wallet_settings');
    }

    private function member(float $balance = 0): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => $balance, 'status' => 'active']);

        return [$user, $wallet];
    }

    private function slip(string $name = 'slip.jpg', ?string $seed = null): UploadedFile
    {
        // เนื้อไฟล์ต่างกันตาม seed → sha256 ต่างกัน
        $file = UploadedFile::fake()->image($name, 400, 600);
        file_put_contents($file->getRealPath(), file_get_contents($file->getRealPath()).($seed ?? Str::random(16)));

        return $file;
    }

    private function submit(User $user, float $amount, UploadedFile $slip)
    {
        return $this->actingAs($user)->post(route('user.wallet.deposit.bank-transfer'), [
            'amount' => $amount,
            'slip' => $slip,
            'note' => 'โอนจากบัญชีกสิกร',
        ]);
    }

    private function adminDevice(): void
    {
        $this->apiKey = SmsCheckerDevice::generateApiKey();
        SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-BANKSLIP',
            'device_name' => 'มือถือแอดมิน',
            'api_key' => $this->apiKey,
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    public function test_migration_adds_the_switch_turned_off(): void
    {
        $this->assertDatabaseHas('wallet_settings', ['key' => 'bank_transfer_enabled', 'value' => '0']);
        $this->assertFalse(WalletSetting::isPaymentMethodEnabled('bank_transfer'));
    }

    public function test_disabled_bank_transfer_is_hidden_and_rejected_without_leaving_a_file(): void
    {
        $this->setBankTransferEnabled(false);
        [$user, $wallet] = $this->member();

        $methods = app(PaymentGatewayService::class)->getAvailablePaymentMethods();
        $this->assertFalse($methods['bank_transfer']['enabled'], 'ปิดอยู่ต้องไม่โชว์ฟอร์มโอนธนาคาร');

        $this->submit($user, 500, $this->slip())->assertSessionHas('error');

        $this->assertSame(0, PaymentTransaction::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }

    public function test_submitting_a_slip_records_a_pending_topup_for_admin_review(): void
    {
        $this->setBankTransferEnabled(true);
        [$user, $wallet] = $this->member();

        $res = $this->submit($user, 500, $this->slip());

        $txn = PaymentTransaction::sole();
        $res->assertRedirect(route('user.wallet.index'));
        $res->assertSessionHas('success', fn ($msg) => str_contains($msg, $txn->transaction_id));

        $this->assertSame($user->id, $txn->user_id);
        $this->assertSame('wallet_topup', $txn->type);
        $this->assertSame(PaymentGatewayService::BANK_SLIP_METHOD, $txn->payment_method);
        $this->assertSame('pending', $txn->status);
        $this->assertSame('500.00', (string) $txn->amount, 'ยอดต้องตรงที่ลูกค้าโอน ไม่ถูกเติมทศนิยม unique amount');
        $this->assertNull($txn->expired_at, 'บิลที่โอนแล้วต้องรอแอดมิน ไม่หมดอายุเอง');
        $this->assertTrue($txn->metadata['requires_admin_review']);
        $this->assertSame('โอนจากบัญชีกสิกร', $txn->metadata['note']);
        $this->assertArrayNotHasKey('unique_amount_id', $txn->metadata);

        // สลิปอยู่ disk ส่วนตัวเท่านั้น (PDPA)
        Storage::disk('local')->assertExists($txn->metadata['slip_path']);
        $this->assertSame([], Storage::disk('public')->allFiles());

        // ยังไม่เติมเงินจนกว่าแอดมินอนุมัติ
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }

    public function test_the_same_slip_cannot_be_submitted_twice(): void
    {
        $this->setBankTransferEnabled(true);
        [$user] = $this->member();
        [$other] = $this->member();

        $this->submit($user, 500, $this->slip('a.jpg', 'same-bytes'))->assertSessionHas('success');
        $this->submit($user, 500, $this->slip('a.jpg', 'same-bytes'))->assertSessionHas('error');
        $this->submit($other, 500, $this->slip('b.jpg', 'same-bytes'))->assertSessionHas('error');

        $this->assertSame(1, PaymentTransaction::count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_a_later_sms_with_the_same_round_amount_does_not_auto_approve_the_slip_bill(): void
    {
        $this->setBankTransferEnabled(true);
        $this->adminDevice();
        [$user, $wallet] = $this->member();

        $this->submit($user, 500, $this->slip());
        $txn = PaymentTransaction::sole();

        // เงินโอน ฿500.00 ของคนอื่นเข้ามาหลังบิลถูกสร้าง
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 500.00,
            'sender_or_receiver' => 'X-1234',
            'sms_timestamp' => now()->addMinute(),
            'device_id' => 'SMSCHK-BANKSLIP',
            'nonce' => Str::random(24),
            'status' => 'pending',
        ]);
        $sms->attemptMatch(true);

        $this->assertSame('pending', $txn->fresh()->status, 'บิลแนบสลิปต้องรอแอดมินเท่านั้น');
        $this->assertNull($sms->fresh()->matched_transaction_id);
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }

    public function test_admin_approval_credits_exactly_once(): void
    {
        $this->setBankTransferEnabled(true);
        [$user, $wallet] = $this->member(100);
        [$admin] = $this->member();

        $this->submit($user, 500, $this->slip());
        $txn = PaymentTransaction::sole();
        $service = app(PaymentGatewayService::class);

        $this->assertTrue($service->approveBankTransfer($txn, $admin));
        $this->assertTrue($service->approveBankTransfer($txn->fresh(), $admin));
        app(PaymentService::class)->completePayment($txn->fresh());

        $txn->refresh();
        $this->assertSame('completed', $txn->status);
        $this->assertSame($admin->id, $txn->metadata['approved_by']);
        $this->assertEquals(600.0, (float) $wallet->fresh()->balance, 'เดิม 100 + ฝาก 500 ครั้งเดียว');
        $this->assertSame(1, WalletTransaction::where('wallet_id', $wallet->id)->where('type', 'deposit')->count());

        $wt = WalletTransaction::where('wallet_id', $wallet->id)->where('type', 'deposit')->sole();
        $this->assertSame($txn->wallet_transaction_id, $wt->id);
        $this->assertEquals(100.0, (float) $wt->balance_before);
        $this->assertEquals(600.0, (float) $wt->balance_after);
        $this->assertSame('เติมเงินผ่าน โอนธนาคาร (แนบสลิป)', $wt->description);
    }

    public function test_approve_bank_transfer_refuses_other_bill_types(): void
    {
        [$user] = $this->member();
        $promptpay = PaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'wallet_topup',
            'payment_method' => 'wallet',
            'status' => 'pending',
            'amount' => 300,
            'currency' => 'THB',
        ]);

        $this->expectExceptionMessage('ไม่ใช่คำขอฝากเงินแบบโอนธนาคาร');
        app(PaymentGatewayService::class)->approveBankTransfer($promptpay);
    }

    public function test_sms_checker_app_lists_the_bill_with_its_slip_and_approves_it_once(): void
    {
        $this->setBankTransferEnabled(true);
        $this->adminDevice();
        [$user, $wallet] = $this->member();

        $this->submit($user, 750, $this->slip());
        $txn = PaymentTransaction::sole();
        $headers = ['X-Api-Key' => $this->apiKey];

        $list = $this->getJson('/api/v1/sms-payment/orders', $headers)->assertOk();
        $row = collect($list->json('data.data'))->firstWhere('order_details_json.order_number', $txn->transaction_id);
        $this->assertNotNull($row, 'แอดมินต้องเห็นบิลในคิวรออนุมัติ');
        $this->assertSame('pending_review', $row['approval_status']);
        $this->assertStringEndsWith('/slip-image', $row['order_details_json']['slip']['image_path']);

        $this->get('/api/v1/sms-payment/orders/'.$txn->transaction_id.'/slip-image', $headers)->assertOk();

        $this->postJson('/api/v1/sms-payment/orders/'.$txn->transaction_id.'/approve', [], $headers)
            ->assertOk()->assertJsonPath('success', true);
        $this->postJson('/api/v1/sms-payment/orders/'.$txn->transaction_id.'/approve', [], $headers)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame('completed', $txn->fresh()->status);
        $this->assertEquals(750.0, (float) $wallet->fresh()->balance);
    }

    public function test_cancelled_wallet_topup_is_never_credited_even_when_retried(): void
    {
        [$user, $wallet] = $this->member(0);
        $txn = PaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'wallet_topup',
            'payment_method' => 'wallet',
            'status' => 'cancelled',
            'amount' => 1000,
            'currency' => 'THB',
        ]);

        $service = app(PaymentService::class);
        $this->assertFalse($service->completePayment($txn));
        $this->assertFalse($service->completePayment($txn->fresh()));

        $this->assertSame('cancelled', $txn->fresh()->status);
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance, 'เดิม: retry ทีละครั้ง = เติม 1,000 ทุกครั้ง');
        $this->assertSame(0, WalletTransaction::where('wallet_id', $wallet->id)->count());
    }
}
