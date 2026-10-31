<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🏦 บัญชีรับเงิน (/user/wallet/payment-methods) — แก้ 29 ต.ค. 2026
 *
 * บั๊กเดิม: ฟอร์มส่งช่องของประเภทอื่นมาเป็นค่าว่าง (ซ่อนด้วย CSS ไม่ได้ disable)
 * แต่ rule เป็น string/email ไม่มี nullable → ค่าว่างกลายเป็น null แล้วตก validation
 * → เพิ่มพร้อมเพย์/บัญชีธนาคารไม่ได้เลย และหน้าเดิมไม่แสดง error ให้เห็นด้วย
 *
 * @group wallet
 */
class WalletPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /** ฟอร์มส่งทุกช่องมาเสมอ (ช่องที่ไม่ใช้เป็นค่าว่าง) — จำลองแบบเดียวกับเบราว์เซอร์ */
    private function formPayload(array $override): array
    {
        return array_merge([
            'type' => '', 'name' => '', 'account_name' => '', 'account_number' => '',
            'bank_name' => '', 'paypal_email' => '',
        ], $override);
    }

    public function test_member_can_add_a_promptpay_account_from_the_web_form(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->from(route('user.wallet.payment-methods'))
            ->post(route('user.wallet.payment-method.store'), $this->formPayload([
                'type' => 'promptpay', 'name' => 'พร้อมเพย์หลัก', 'account_name' => 'สมชาย ใจดี', 'account_number' => '081-234-5678',
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $pm = PaymentMethod::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('promptpay', $pm->type);
        $this->assertNull($pm->bank_name, 'พร้อมเพย์ต้องไม่เก็บชื่อธนาคารค้าง');
        $this->assertTrue((bool) $pm->is_active, 'บัญชีใหม่ต้องใช้งานได้ทันที (หน้าถอนกรองเฉพาะ active)');
    }

    public function test_member_can_add_a_bank_account_and_make_it_default(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $old = $user->paymentMethods()->create(['type' => 'promptpay', 'name' => 'เดิม', 'account_name' => 'ก', 'account_number' => '0812345678', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($user)->post(route('user.wallet.payment-method.store'), $this->formPayload([
            'type' => 'bank_transfer', 'name' => 'บัญชีหลัก', 'account_name' => 'สมชาย ใจดี',
            'account_number' => '123-4-56789-0', 'bank_name' => 'ธนาคารกสิกรไทย', 'is_default' => '1',
        ]))->assertSessionHasNoErrors();

        $new = PaymentMethod::where('user_id', $user->id)->where('type', 'bank_transfer')->firstOrFail();
        $this->assertTrue((bool) $new->is_default);
        $this->assertFalse((bool) $old->fresh()->is_default, 'ค่าเริ่มต้นต้องมีบัญชีเดียว');
    }

    public function test_bank_account_without_bank_name_is_refused_in_thai(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post(route('user.wallet.payment-method.store'), $this->formPayload([
            'type' => 'bank_transfer', 'name' => 'บัญชีหลัก', 'account_name' => 'สมชาย', 'account_number' => '1234567890',
        ]))->assertSessionHasErrors(['bank_name' => 'กรุณาเลือกธนาคาร']);
    }

    public function test_deleting_the_default_account_promotes_another_one(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $keep = $user->paymentMethods()->create(['type' => 'promptpay', 'name' => 'สำรอง', 'account_name' => 'ก', 'account_number' => '0812345678', 'is_active' => true]);
        $default = $user->paymentMethods()->create(['type' => 'promptpay', 'name' => 'หลัก', 'account_name' => 'ก', 'account_number' => '0899999999', 'is_default' => true, 'is_active' => true]);

        $this->actingAs($user)->delete(route('user.wallet.payment-method.delete', $default->id))->assertSessionHas('success');

        $this->assertTrue((bool) $keep->fresh()->is_default, 'ลบบัญชีค่าเริ่มต้นแล้วต้องมีบัญชีอื่นขึ้นมาแทน');
    }

    public function test_member_cannot_delete_someone_elses_account(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $pm = $owner->paymentMethods()->create(['type' => 'promptpay', 'name' => 'ของเจ้าของ', 'account_name' => 'ก', 'account_number' => '0812345678', 'is_active' => true]);

        $this->actingAs($other)->delete(route('user.wallet.payment-method.delete', $pm->id));

        $this->assertNotNull($pm->fresh(), 'ลบบัญชีคนอื่นต้องไม่ได้');
    }
}
