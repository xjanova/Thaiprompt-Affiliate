<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GET /api/v1/juntra/payment/account
 *
 * บัญชีรับเงินที่เว็บ จันทรา.online ดึงไปสร้าง QR ให้ลูกค้าสแกนจ่าย
 *
 * ทำไมต้องมีเทสต์: ระบบมีบัญชีรับเงินหลายใบ (บัญชีร้าน + บัญชีดูดวง) และแอดมิน
 * "ติ๊ก" เลือกไว้ว่าระบบดูดวงใช้ใบไหน (fortune_bank_account_ids) ถ้า endpoint นี้
 * ไปหยิบบัญชี active ตัวแรกของทั้งระบบแทน ลูกค้าเว็บจะโอนเข้าคนละบัญชีกับลูกค้า
 * บอท FB/LINE → SlipOK/ตัวจับ SMS ที่ผูกกับบัญชีดูดวงจะตีสลิปกลับว่า "ปลายทาง
 * ไม่ใช่บัญชีเรา" ทั้งที่ลูกค้าโอนถูก (บั๊กชุดเดียวกับที่เคยหลุดไปกับ LINE 2026-07-24)
 */
class JuntraPayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/juntra/payment/account';

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /** บัญชีร้าน — เป็น default ของทั้งระบบ และมาก่อนตาม sort_order */
    private function shopAccount(): PaymentBankAccount
    {
        return PaymentBankAccount::create([
            'bank_code' => 'SCB',
            'bank_name' => 'ธนาคารไทยพาณิชย์',
            'account_number' => '1111111111',
            'account_name' => 'ร้านค้า ทดสอบ',
            'promptpay_id' => '0800000001',
            'promptpay_type' => 'phone',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }

    /** บัญชีดูดวง — ใบที่แอดมินติ๊กไว้ให้แม่หมอใช้ */
    private function fortuneAccount(): PaymentBankAccount
    {
        return PaymentBankAccount::create([
            'bank_code' => 'KBANK',
            'bank_name' => 'ธนาคารกสิกรไทย',
            'account_number' => '2222222222',
            'account_name' => 'จันทราพยากรณ์ ทดสอบ',
            'promptpay_id' => '0900000002',
            'promptpay_type' => 'phone',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 9,
        ]);
    }

    private function tickAccounts(array $ids): void
    {
        FortuneTellingSetting::getSettings()->update(['fortune_bank_account_ids' => $ids]);
        FortuneTellingSetting::clearSettingsCache();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_returns_the_account_ticked_for_the_fortune_bot_not_the_system_default(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->shopAccount();
        $fortune = $this->fortuneAccount();
        $this->tickAccounts([$fortune->id]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.promptpay_id', '0900000002')
            ->assertJsonPath('data.account_number', '2222222222')
            ->assertJsonPath('data.bank_code', 'KBANK')
            ->assertJsonPath('data.account_name', 'จันทราพยากรณ์ ทดสอบ');
    }

    /** ติ๊กไว้แต่ปิดใช้งานบัญชีนั้นทีหลัง → ห้ามส่งบัญชีที่ปิดไปให้เว็บเก็บเงิน */
    public function test_skips_a_ticked_account_that_is_no_longer_active(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $shop = $this->shopAccount();
        $fortune = $this->fortuneAccount();
        $this->tickAccounts([$fortune->id]);

        $fortune->update(['is_active' => false]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.promptpay_id', $shop->promptpay_id);
    }

    /** ยังไม่เคยติ๊ก → ใช้บัญชีหลักของระบบตามเดิม (พฤติกรรมเดิม ไม่ให้พัง) */
    public function test_falls_back_to_the_system_account_when_nothing_is_ticked(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $shop = $this->shopAccount();
        $this->fortuneAccount();
        $this->tickAccounts([]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.promptpay_id', $shop->promptpay_id);
    }

    public function test_returns_404_when_no_account_is_configured_at_all(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(self::URL)->assertNotFound();
    }

    /**
     * 🏦 (2026-11-09) เลขบัญชีให้โอนตรง = บัญชีที่ติ๊กไว้ให้ดูดวง (ใบเดียวกับ QR) ไม่ใช่บัญชีหลักของระบบ
     *   เจ้าของ: "ต้องเป็นบัญชีกสิกร ... ไม่ใช่ไทยพาณิชย์"
     */
    public function test_transfer_account_is_the_ticked_fortune_account_not_the_system_default(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $shop = $this->shopAccount();
        $shop->update(['sms_checker_enabled' => true]);
        $fortune = $this->fortuneAccount();
        $fortune->update(['sms_checker_enabled' => true]);
        $this->tickAccounts([$fortune->id]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.promptpay_id', '0900000002')
            ->assertJsonPath('data.transfer_account.bank_code', 'KBANK')
            ->assertJsonPath('data.transfer_account.account_number', '2222222222')
            ->assertJsonPath('data.transfer_account.account_name', 'จันทราพยากรณ์ ทดสอบ');
    }

    /** บัญชีที่ทำ QR ไม่มีเลขบัญชี → ใช้บัญชีดูดวงใบถัดไปที่ติ๊กไว้และเปิด SMS */
    public function test_transfer_account_falls_to_another_ticked_account_with_sms(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $qr = $this->fortuneAccount();
        $qr->update(['sms_checker_enabled' => true, 'account_number' => '', 'sort_order' => 0]);
        $second = $this->shopAccount();
        $second->update(['sms_checker_enabled' => true, 'is_default' => false, 'sort_order' => 5]);
        $this->tickAccounts([$qr->id, $second->id]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.promptpay_id', '0900000002')
            ->assertJsonPath('data.transfer_account.account_number', '1111111111');
    }

    /** บัญชีดูดวงไม่ได้เปิด SMS = โอนแล้วบิลไม่ตัดเอง → ไม่ส่งเลขบัญชี (ไม่หยิบบัญชีหลักของระบบมาแทน) */
    public function test_transfer_account_is_null_when_the_fortune_account_has_no_sms_checking(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $shop = $this->shopAccount();
        $shop->update(['sms_checker_enabled' => true]);
        $fortune = $this->fortuneAccount();
        $this->tickAccounts([$fortune->id]);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.transfer_account', null);
    }
}
