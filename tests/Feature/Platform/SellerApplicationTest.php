<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * สมัครเปิดร้านค้าจากสมาชิกทั่วไป → แอดมินอนุมัติ (SELLER-07)
 *
 * เดิมไม่มีทางไหนให้สมาชิกได้ role seller นอกจากแอดมินแก้มือ และ seller เข้า /user/kyc ไม่ได้ (วนลูป)
 *
 * ใช้ DB (MySQL บน CI)
 */
class SellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_member_can_apply_and_admin_can_approve(): void
    {
        $member = User::factory()->create(['role' => 'user']);

        $this->actingAs($member, 'web')->get(route('user.seller-apply.index'))
            ->assertOk()
            ->assertSee('เปิดร้านค้าบนไทยพร๊อมท์');

        $this->actingAs($member, 'web')
            ->post(route('user.seller-apply.store'), $this->form())
            ->assertRedirect(route('user.seller-apply.index'))
            ->assertSessionHas('success');

        $store = VendorStore::where('user_id', $member->id)->firstOrFail();
        $this->assertSame('pending', $store->status);
        $this->assertFalse((bool) $store->is_active);

        // ยื่นซ้ำระหว่างรอไม่ได้ (ไม่สร้างร้านซ้อน)
        $this->actingAs($member, 'web')->post(route('user.seller-apply.store'), $this->form());
        $this->assertSame(1, VendorStore::where('user_id', $member->id)->count());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'web')->get(route('admin.seller-applications.index'))
            ->assertOk()
            ->assertSee('ร้านผักป้าแดง');

        $this->actingAs($admin, 'web')
            ->from(route('admin.seller-applications.index'))
            ->post(route('admin.seller-applications.approve', $store))
            ->assertRedirect(route('admin.seller-applications.index'))
            ->assertSessionHas('success');

        $store->refresh();
        $this->assertSame('active', $store->status);
        $this->assertTrue((bool) $store->is_active);
        $this->assertSame('active', $store->subscription_status);
        $this->assertSame('seller', $member->fresh()->role);

        // ผู้ขายยังเข้าพื้นที่สมาชิก (KYC) ได้ — เดิมโดนเด้งวนลูป
        $this->actingAs($member->fresh(), 'web')->get(route('user.seller-apply.index'))
            ->assertOk()
            ->assertSee('คุณเป็นผู้ขายแล้ว');
    }

    public function test_rejected_application_can_be_resubmitted(): void
    {
        $member = User::factory()->create(['role' => 'affiliate']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($member, 'web')->post(route('user.seller-apply.store'), $this->form());
        $store = VendorStore::where('user_id', $member->id)->firstOrFail();

        $this->actingAs($admin, 'web')
            ->from(route('admin.seller-applications.index'))
            ->post(route('admin.seller-applications.reject', $store), ['reason' => 'ที่อยู่ไม่ครบ'])
            ->assertSessionHas('success');

        $store->refresh();
        $this->assertSame('closed', $store->status);
        $this->assertSame('ที่อยู่ไม่ครบ', $store->suspension_reason);
        $this->assertSame('affiliate', $member->fresh()->role);

        $this->actingAs($member, 'web')->get(route('user.seller-apply.index'))
            ->assertOk()
            ->assertSee('ที่อยู่ไม่ครบ');

        $this->actingAs($member, 'web')
            ->post(route('user.seller-apply.store'), $this->form(['store_address' => '99/1 หมู่ 2 ต.ในเมือง']))
            ->assertSessionHas('success');

        $this->assertSame('pending', $store->fresh()->status);
        $this->assertSame(1, VendorStore::where('user_id', $member->id)->count());
    }

    public function test_validation_messages_are_thai(): void
    {
        $member = User::factory()->create(['role' => 'user']);

        $this->actingAs($member, 'web')
            ->from(route('user.seller-apply.index'))
            ->post(route('user.seller-apply.store'), ['business_type' => 'company'])
            ->assertSessionHasErrors(['store_name', 'store_phone', 'company_name', 'tax_id', 'accept_terms']);

        $this->assertSame(0, VendorStore::where('user_id', $member->id)->count());
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'ร้านผักป้าแดง',
            'business_type' => 'individual',
            'store_phone' => '0812345678',
            'store_description' => 'ผักสดจากสวน',
            'store_address' => '12/3 ถ.มิตรภาพ',
            'store_city' => 'เมืองขอนแก่น',
            'store_state' => 'ขอนแก่น',
            'store_postal_code' => '40000',
            'accept_terms' => '1',
        ], $overrides);
    }
}
