<?php

namespace Tests\Feature\SellerApp;

use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * สมัครเปิดร้านจากแอป — /api/v1/seller/application (ตรรกะเดียวกับเว็บ /user/seller-apply)
 *
 * ใช้ MySQL (RefreshDatabase)
 */
class SellerApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();
    }

    public function test_member_can_apply_and_see_pending_state(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/seller/application')
            ->assertOk()
            ->assertJsonPath('data.state', 'can_apply')
            ->assertJsonPath('data.can_submit', true)
            ->assertJsonPath('data.application', null);

        $this->postJson('/api/v1/seller/application', $this->form())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.state', 'pending')
            ->assertJsonPath('data.application.store_name', 'ร้านผักป้าแดง');

        $store = VendorStore::where('user_id', $member->id)->firstOrFail();
        $this->assertSame('pending', $store->status);
        $this->assertFalse((bool) $store->is_active);
        $this->assertSame($member->email, $store->store_email);
        $this->assertNull($store->tax_id, 'บุคคลธรรมดาไม่เก็บเลขภาษี');

        // ยื่นซ้ำระหว่างรอไม่ได้ (ไม่สร้างร้านซ้อน)
        $this->postJson('/api/v1/seller/application', $this->form())
            ->assertStatus(409)
            ->assertJsonPath('code', 'APPLICATION_NOT_ALLOWED');
        $this->assertSame(1, VendorStore::where('user_id', $member->id)->count());

        // แอปจัดการร้านยังเข้าไม่ได้ระหว่างรอ — บอกสถานะรออนุมัติ ไม่ใช่ "ร้านถูกระงับ"
        $this->getJson('/api/v1/seller/products')
            ->assertForbidden()
            ->assertJsonPath('code', 'STORE_PENDING');
    }

    public function test_rejected_application_shows_reason_and_can_be_resubmitted(): void
    {
        $member = User::factory()->create(['role' => 'affiliate']);
        $store = VendorStore::create([
            'user_id' => $member->id,
            'store_name' => 'ร้านเดิม',
            'store_slug' => 'old-shop-'.$member->id,
            'status' => 'closed',
            'is_active' => false,
            'suspension_reason' => 'ที่อยู่ไม่ครบ',
        ]);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/seller/application')
            ->assertOk()
            ->assertJsonPath('data.state', 'rejected')
            ->assertJsonPath('data.can_submit', true)
            ->assertJsonPath('data.rejection_reason', 'ที่อยู่ไม่ครบ')
            ->assertJsonPath('data.application.store_name', 'ร้านเดิม');

        $this->postJson('/api/v1/seller/application', $this->form([
            'business_type' => 'company',
            'company_name' => 'บริษัท ผักสด จำกัด',
            'tax_id' => '0105555555555',
        ]))
            ->assertOk()
            ->assertJsonPath('data.state', 'pending')
            ->assertJsonPath('data.rejection_reason', null);

        $store->refresh();
        $this->assertSame('pending', $store->status);
        $this->assertNull($store->suspension_reason);
        $this->assertSame('0105555555555', $store->tax_id);
        $this->assertSame(1, VendorStore::where('user_id', $member->id)->count());
    }

    public function test_validation_errors_are_thai_and_nothing_is_saved(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/seller/application', [
            'business_type' => 'company',
            'store_phone' => '12345',
            'store_postal_code' => '1234',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $errors = $response->json('errors');
        foreach (['store_name', 'store_phone', 'company_name', 'tax_id', 'accept_terms', 'store_postal_code'] as $field) {
            $this->assertArrayHasKey($field, $errors);
            $this->assertMatchesRegularExpression('/[ก-๙]/u', $errors[$field][0], "ข้อความของ {$field} ต้องเป็นภาษาไทย");
        }
        $this->assertMatchesRegularExpression('/[ก-๙]/u', (string) $response->json('message'));
        $this->assertSame(0, VendorStore::where('user_id', $member->id)->count());
    }

    public function test_ineligible_role_and_existing_seller_cannot_apply(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        Sanctum::actingAs($provider);

        $this->getJson('/api/v1/seller/application')->assertOk()->assertJsonPath('data.state', 'role_not_eligible');
        $this->postJson('/api/v1/seller/application', $this->form())
            ->assertStatus(409)
            ->assertJsonPath('code', 'APPLICATION_NOT_ALLOWED');
        $this->assertSame(0, VendorStore::where('user_id', $provider->id)->count());

        $seller = User::factory()->create(['role' => 'seller']);
        Sanctum::actingAs($seller);
        $this->postJson('/api/v1/seller/application', $this->form())->assertStatus(409);
        $this->assertSame(0, VendorStore::where('user_id', $seller->id)->count());
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/seller/application')->assertUnauthorized();
        $this->postJson('/api/v1/seller/application', $this->form())->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'ร้านผักป้าแดง',
            'business_type' => 'individual',
            'store_phone' => '0812345678',
            'store_description' => 'ผักสดจากสวน',
            'store_address' => '12/3 หมู่ 4',
            'store_city' => 'เมือง',
            'store_state' => 'เชียงใหม่',
            'store_postal_code' => '50000',
            'tax_id' => '1234567890123',
            'accept_terms' => true,
        ], $overrides);
    }
}
