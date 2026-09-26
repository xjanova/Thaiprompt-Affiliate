<?php

namespace Tests\Feature\SellerApp;

use App\Models\AccountingActivityLog;
use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ตั้งค่าร้านจากแอป — /api/v1/seller/store (กติกาเดียวกับเว็บ /seller/store/settings)
 *
 * ใช้ MySQL (RefreshDatabase)
 */
class SellerStoreApiTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();
        Storage::fake('public');
    }

    /**
     * @return array{0: User, 1: VendorStore}
     */
    private function readySeller(array $storeOverrides = []): array
    {
        [$seller, $store] = $this->makeSellerWithStore($storeOverrides);
        $seller->forceFill(['kyc_status' => 'approved'])->save();

        return [$seller->fresh(), $store];
    }

    public function test_seller_reads_and_partially_updates_own_store_only(): void
    {
        [$seller, $store] = $this->readySeller(['enable_cod' => true, 'enable_reviews' => true]);
        [, $otherStore] = $this->readySeller();
        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/seller/store')
            ->assertOk()
            ->assertJsonPath('data.id', $store->id)
            ->assertJsonPath('data.enable_cod', true)
            ->assertJsonPath('data.has_pickup_location', true);

        $oldSlug = $store->store_slug;
        $this->putJson('/api/v1/seller/store', [
            'store_name' => 'ร้านใหม่ของฉัน',
            'store_description' => 'ขายของดี',
            'store_phone' => '0899999999',
            'minimum_order_amount' => null,
            'free_shipping_threshold' => 500,
            'facebook_url' => 'https://facebook.com/myshop',
        ])
            ->assertOk()
            ->assertJsonPath('data.store_name', 'ร้านใหม่ของฉัน')
            ->assertJsonPath('data.minimum_order_amount', 0)
            // ช่องที่ไม่ได้ส่งต้องคงค่าเดิม (เว็บจะปิด COD ถ้าไม่ส่ง — แอปห้ามเป็นแบบนั้น)
            ->assertJsonPath('data.enable_cod', true)
            ->assertJsonPath('data.enable_reviews', true);

        $store->refresh();
        $this->assertNotSame($oldSlug, $store->store_slug, 'เปลี่ยนชื่อร้านต้องได้ slug ใหม่');
        $this->assertSame('0899999999', $store->store_phone);
        $this->assertSame(500.0, (float) $store->free_shipping_threshold);

        // ร้านคนอื่นไม่ถูกแตะ (ไม่มี id ใน URL — แก้ได้เฉพาะร้านของตัวเอง)
        $this->assertNotSame('ร้านใหม่ของฉัน', $otherStore->fresh()->store_name);
    }

    public function test_rider_delivery_requires_pickup_pin_and_links_must_be_https(): void
    {
        [$seller, $store] = $this->readySeller(['pickup_latitude' => null, 'pickup_longitude' => null]);
        Sanctum::actingAs($seller);

        $this->putJson('/api/v1/seller/store', ['rider_delivery_enabled' => true])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'เปิดส่งด้วยไรเดอร์ต้องปักหมุดจุดรับของก่อน');

        $this->putJson('/api/v1/seller/store', ['pickup_latitude' => 13.75])
            ->assertStatus(422);

        $this->putJson('/api/v1/seller/store', [
            'rider_delivery_enabled' => true,
            'pickup_latitude' => 13.7563309,
            'pickup_longitude' => 100.5017651,
            'pickup_address' => 'หน้าร้าน ถนนสีลม',
        ])
            ->assertOk()
            ->assertJsonPath('data.rider_delivery_enabled', true)
            ->assertJsonPath('data.has_pickup_location', true);

        $this->putJson('/api/v1/seller/store', ['facebook_url' => 'javascript:alert(1)'])
            ->assertStatus(422);
        $this->putJson('/api/v1/seller/store', ['business_type' => 'alien'])
            ->assertStatus(422);
        $this->putJson('/api/v1/seller/store', ['store_name' => ''])
            ->assertStatus(422);

        $this->assertTrue((bool) $store->fresh()->rider_delivery_enabled);
    }

    public function test_vat_registration_needs_13_digit_tax_id_and_is_audited(): void
    {
        [$seller, $store] = $this->readySeller();
        Sanctum::actingAs($seller);

        $this->putJson('/api/v1/seller/store', ['vat_registered' => true, 'tax_id' => '123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.tax_id.0', 'ร้านที่จดทะเบียน VAT ต้องกรอกเลขประจำตัวผู้เสียภาษี 13 หลัก');
        $this->assertFalse((bool) $store->fresh()->vat_registered);

        $this->putJson('/api/v1/seller/store', ['vat_registered' => true, 'tax_id' => '0105555555555'])
            ->assertOk()
            ->assertJsonPath('data.vat_registered', true);

        $this->assertTrue(AccountingActivityLog::where('loggable_id', $store->id)
            ->where('action', 'store.vat_registered_changed')
            ->exists());
    }

    public function test_logo_and_banner_upload_validate_files(): void
    {
        [$seller, $store] = $this->readySeller();
        Sanctum::actingAs($seller);

        $first = $this->post('/api/v1/seller/store/logo', [
            'store_logo' => UploadedFile::fake()->image('logo.png', 600, 600),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertNotNull($first->json('data.logo_url'));

        $oldPath = $store->fresh()->getRawOriginal('store_logo');
        $this->assertStringStartsWith('storage/stores/logos/', $oldPath);
        Storage::disk('public')->assertExists(substr($oldPath, strlen('storage/')));

        // เปลี่ยนโลโก้ → ไฟล์เดิมถูกลบ
        $this->post('/api/v1/seller/store/logo', [
            'store_logo' => UploadedFile::fake()->image('logo2.jpg', 300, 300),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing(substr($oldPath, strlen('storage/')));

        $this->post('/api/v1/seller/store/logo', [
            'store_logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->post('/api/v1/seller/store/banner', [
            'store_banner' => UploadedFile::fake()->image('banner.jpg')->size(5000),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->post('/api/v1/seller/store/banner', [
            'store_banner' => UploadedFile::fake()->image('banner.jpg', 1600, 500),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertStringStartsWith('storage/stores/banners/', $store->fresh()->getRawOriginal('store_banner'));
    }

    public function test_non_sellers_and_suspended_stores_are_denied(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($member);
        $this->getJson('/api/v1/seller/store')->assertForbidden()->assertJsonPath('code', 'NOT_A_SELLER');
        $this->putJson('/api/v1/seller/store', ['store_name' => 'แอบแก้'])->assertForbidden();

        [$seller, $store] = $this->readySeller();
        $store->forceFill(['status' => 'suspended', 'is_active' => false])->save();
        Sanctum::actingAs($seller);
        $this->putJson('/api/v1/seller/store', ['store_name' => 'ร้านที่ถูกระงับ'])
            ->assertForbidden()
            ->assertJsonPath('code', 'STORE_SUSPENDED');
        $this->assertNotSame('ร้านที่ถูกระงับ', $store->fresh()->store_name);

        $this->getJson('/api/v1/seller/store')->assertForbidden();
    }
}
