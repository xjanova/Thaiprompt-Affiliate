<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 💸 ทดสอบเส้นทางถอนเงินก่อนเปิดการตลาด affiliate จันทรา (2026-07-16)
 *
 * กติกาที่ต้องเป็นจริง:
 * - ไม่ผ่าน KYC = ถอนไม่ได้ (ทั้ง GET และ POST — gate อยู่ใน service)
 * - กระเป๋าที่ตั้ง PIN ต้องกรอก PIN ถูกจึงถอนได้ / ไม่ตั้ง PIN ไม่ต้องกรอก
 *   (บั๊กเดิม: ฟอร์มเก็บ PIN แต่ controller ทิ้งค่า — service ได้ค่าว่างเสมอ
 *    → คนตั้ง PIN ถอนไม่ได้เลย)
 * - สร้างคำขอแล้วเงินถูกกันออกจากกระเป๋าทันที / ปฏิเสธแล้วเงินคืน
 *
 * @group wallet
 */
class WithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $kycApproved = true): User
    {
        $user = User::create([
            'name' => 'ลูกค้าทดสอบ',
            'email' => 'fb_1234500001@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
            'facebook_psid' => '1234500001',
        ]);

        if ($kycApproved) {
            $user->forceFill(['kyc_status' => 'approved'])->save();
        }

        return $user;
    }

    /**
     * เตรียมกระเป๋า (ใส่ยอดเงิน) + ช่องทางรับเงิน
     */
    private function fundWallet(User $user, float $balance, ?string $pin = null): array
    {
        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $fill = ['balance' => $balance];
        if ($pin !== null) {
            $fill['pin_hash'] = password_hash($pin, PASSWORD_DEFAULT);
        }
        $wallet->forceFill($fill)->save();

        $method = $user->paymentMethods()->create([
            'type' => 'bank_transfer',
            'name' => 'บัญชีหลัก',
            'account_name' => 'ลูกค้าทดสอบ',
            'account_number' => '1234567890',
            'bank_name' => 'กสิกรไทย',
            'is_active' => true,
        ]);

        return [$wallet->fresh(), $method];
    }

    /**
     * ไม่ผ่าน KYC → ห้ามสร้างคำขอถอน (gate ระดับ service กัน POST ตรงด้วย)
     */
    public function test_withdrawal_is_blocked_without_kyc(): void
    {
        $user = $this->makeUser(kycApproved: false);
        [, $method] = $this->fundWallet($user, 500);

        $this->expectExceptionMessage('KYC');
        app(WithdrawalService::class)->createWithdrawalRequest($user, 200, $method->id);
    }

    /**
     * ผ่าน KYC + มียอด → สร้างคำขอสำเร็จ และเงินถูกกันออกทันที
     */
    public function test_kyc_verified_user_can_request_withdrawal_and_balance_is_reserved(): void
    {
        $user = $this->makeUser();
        [$wallet, $method] = $this->fundWallet($user, 500);

        $request = app(WithdrawalService::class)->createWithdrawalRequest($user, 200, $method->id);

        $this->assertSame('pending', $request->status, 'ต้องรอแอดมินอนุมัติ (auto_approve_threshold=0)');
        $this->assertEquals(300.0, (float) $wallet->fresh()->balance, 'เงินต้องถูกกันออกทันทีตอนขอถอน');
        $this->assertNotEmpty($request->request_id);
    }

    /**
     * กระเป๋าไม่มี PIN → ไม่ต้องกรอก PIN ก็ถอนได้ (ลูกค้าบอททุกคนอยู่กลุ่มนี้)
     */
    public function test_wallet_without_pin_needs_no_pin(): void
    {
        $user = $this->makeUser();
        [, $method] = $this->fundWallet($user, 500);

        $request = app(WithdrawalService::class)->createWithdrawalRequest($user, 150, $method->id, null, null);

        $this->assertSame('pending', $request->status);
    }

    /**
     * กระเป๋าตั้ง PIN + กรอกถูก → ถอนได้ (บั๊กเดิม: ส่งค่าว่างเสมอ = ถอนไม่ได้)
     */
    public function test_wallet_with_pin_accepts_correct_pin(): void
    {
        $user = $this->makeUser();
        [, $method] = $this->fundWallet($user, 500, pin: '123456');

        $request = app(WithdrawalService::class)->createWithdrawalRequest($user, 150, $method->id, null, '123456');

        $this->assertSame('pending', $request->status, 'PIN ถูกต้องต้องถอนได้ — เดิมพังเพราะ service ได้ค่าว่างเสมอ');
    }

    /**
     * กระเป๋าตั้ง PIN + กรอกผิด → ห้ามสร้างคำขอ เงินห้ามขยับ
     */
    public function test_wallet_with_pin_rejects_wrong_pin(): void
    {
        $user = $this->makeUser();
        [$wallet, $method] = $this->fundWallet($user, 500, pin: '123456');

        try {
            app(WithdrawalService::class)->createWithdrawalRequest($user, 150, $method->id, null, '000000');
            $this->fail('PIN ผิดต้อง throw');
        } catch (\Exception $e) {
            $this->assertStringContainsString('PIN', $e->getMessage());
        }

        $this->assertSame(0, WithdrawalRequest::count(), 'PIN ผิดห้ามมีคำขอเกิดขึ้น');
        $this->assertEquals(500.0, (float) $wallet->fresh()->balance, 'เงินห้ามขยับ');
    }

    /**
     * แอดมินปฏิเสธ → เงินคืนเข้ากระเป๋าเต็มจำนวน
     */
    public function test_rejecting_withdrawal_refunds_balance(): void
    {
        $user = $this->makeUser();
        [$wallet, $method] = $this->fundWallet($user, 500);
        $admin = User::create([
            'name' => 'แอดมิน',
            'email' => 'admin-test@example.com',
            'password' => bcrypt(str()->random(20)),
        ]);

        $svc = app(WithdrawalService::class);
        $request = $svc->createWithdrawalRequest($user, 200, $method->id);
        $this->assertEquals(300.0, (float) $wallet->fresh()->balance);

        $svc->rejectWithdrawal($request, $admin, 'ข้อมูลบัญชีไม่ถูกต้อง');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertEquals(500.0, (float) $wallet->fresh()->balance, 'ปฏิเสธแล้วเงินต้องคืนเต็มจำนวน');
    }
}
