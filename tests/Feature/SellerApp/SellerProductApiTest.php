<?php

namespace Tests\Feature\SellerApp;

use App\Models\Product;
use App\Models\ProductImage;
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
 * จัดการสินค้าจากแอป — /api/v1/seller/products/* (กติกาเดียวกับหลังร้านเว็บ)
 *
 * ครอบคลุม: ด่านผู้ขาย (KYC/ร้านระงับ/รออนุมัติ) · IDOR สินค้าร้านอื่น = 404 · validation ภาษาไทย
 *          · สร้าง/แก้/เปิดปิด/สต็อก/ลบ (soft) · รูป (เพิ่ม/ตั้งหลัก/เรียง/ลบ) · สินค้าถูกระงับ/มีตัวเลือกย่อย = อ่านอย่างเดียว
 *          · Idempotency-Key กันสร้างซ้ำ
 *
 * ใช้ MySQL (RefreshDatabase)
 */
class SellerProductApiTest extends TestCase
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
        $this->setGpRate(10);
    }

    /**
     * ผู้ขายที่ผ่าน KYC + ร้านเปิดอยู่
     *
     * @return array{0: User, 1: VendorStore}
     */
    private function readySeller(): array
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $seller->forceFill(['kyc_status' => 'approved'])->save();

        return [$seller->fresh(), $store];
    }

    public function test_gate_blocks_non_sellers_unverified_and_suspended_stores(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($member);
        $this->getJson('/api/v1/seller/products')->assertForbidden()->assertJsonPath('code', 'NOT_A_SELLER');
        $this->postJson('/api/v1/seller/products', $this->productForm())->assertForbidden();

        [$noKyc] = $this->makeSellerWithStore();
        Sanctum::actingAs($noKyc);
        $this->getJson('/api/v1/seller/products')->assertForbidden()->assertJsonPath('code', 'SELLER_KYC_REQUIRED');

        [$suspended, $store] = $this->readySeller();
        $store->forceFill(['status' => 'suspended', 'is_active' => false, 'suspension_reason' => 'ขายของผิดกฎหมาย'])->save();
        Sanctum::actingAs($suspended);
        $this->getJson('/api/v1/seller/products/meta')
            ->assertForbidden()
            ->assertJsonPath('code', 'STORE_SUSPENDED')
            ->assertJsonPath('data.reason', 'ขายของผิดกฎหมาย');

        [$expired, $expiredStore] = $this->readySeller();
        $expiredStore->forceFill(['subscription_status' => 'expired', 'package_id' => null])->save();
        Sanctum::actingAs($expired);
        $this->getJson('/api/v1/seller/products')->assertForbidden()->assertJsonPath('code', 'PACKAGE_REQUIRED');

        $this->assertSame(0, Product::count());
    }

    public function test_seller_creates_product_with_images_and_retry_is_idempotent(): void
    {
        [$seller, $store] = $this->readySeller();
        $category = $this->makeCategory();
        Sanctum::actingAs($seller);

        $form = $this->productForm([
            'category_id' => $category->id,
            'track_inventory' => '1',
            'main_image' => UploadedFile::fake()->image('main.jpg', 800, 800),
            'images' => [
                UploadedFile::fake()->image('g1.png', 400, 400),
                UploadedFile::fake()->image('g2.jpg', 400, 400),
            ],
        ]);

        $response = $this->withHeader('Idempotency-Key', 'create-key-0001')
            ->post('/api/v1/seller/products', $form, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'เสื้อยืดลายไทย')
            ->assertJsonPath('data.read_only', false)
            ->assertJsonCount(3, 'data.images')
            ->assertJsonPath('data.images.0.is_main', true);

        $product = Product::findOrFail($response->json('data.id'));
        $this->assertSame($seller->id, (int) $product->seller_id);
        $this->assertSame($store->id, (int) $product->store_id);
        $this->assertTrue((bool) $product->is_active);
        $this->assertNotNull($product->published_at);
        $this->assertSame('in_stock', $product->stock_status);
        $this->assertSame(10.0, (float) $product->commission_rate, 'GP แสดงผลมาจาก PricingEngine');
        $this->assertSame(0.0, (float) $product->customer_cashback);
        $this->assertSame(2, ProductImage::where('product_id', $product->id)->count());
        Storage::disk('public')->assertExists($product->getRawOriginal('main_image_url'));

        // เน็ตหลุดแล้วกดใหม่ด้วย key เดิม → ได้สินค้าเดิม ไม่สร้างซ้อน
        $this->withHeader('Idempotency-Key', 'create-key-0001')
            ->post('/api/v1/seller/products', $this->productForm(['category_id' => $category->id]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('data.id', $product->id);
        $this->assertSame(1, Product::where('seller_id', $seller->id)->count());

        // key ใหม่ = สินค้าใหม่ (ชื่อซ้ำได้ slug ไม่ชน)
        $this->withHeader('Idempotency-Key', 'create-key-0002')
            ->post('/api/v1/seller/products', $this->productForm(['category_id' => $category->id]), ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertSame(2, Product::where('seller_id', $seller->id)->count());
    }

    public function test_create_validation_is_thai_and_rejects_bad_files(): void
    {
        [$seller] = $this->readySeller();
        Sanctum::actingAs($seller);

        $response = $this->post('/api/v1/seller/products', [
            'price' => -5,
            'stock_quantity' => 'abc',
            'shipping_method' => 'teleport',
            'shipping_fee' => 99999,
            'main_image' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $errors = $response->json('errors');
        foreach (['name', 'category_id', 'price', 'stock_quantity', 'shipping_method', 'shipping_fee', 'main_image'] as $field) {
            $this->assertArrayHasKey($field, $errors, "ต้องมี error ของ {$field}");
            $this->assertMatchesRegularExpression('/[ก-๙]/u', $errors[$field][0], "ข้อความของ {$field} ต้องเป็นภาษาไทย");
        }

        $tooBig = $this->post('/api/v1/seller/products', $this->productForm([
            'category_id' => $this->makeCategory()->id,
            'main_image' => UploadedFile::fake()->image('huge.jpg')->size(6000),
        ]), ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertArrayHasKey('main_image', $tooBig->json('errors'));

        $this->assertSame(0, Product::count());
    }

    public function test_long_thai_description_is_checked_by_bytes_not_characters(): void
    {
        [$seller, $store] = $this->readySeller();
        $category = $this->makeCategory();
        $product = $this->makeProduct($seller, $store, ['category_id' => $category->id, 'description' => 'เดิม']);
        Sanctum::actingAs($seller);

        // 22,000 ตัวอักษรไทย = 66,000 ไบต์ > TEXT (65,535) — ต้องได้ 422 ภาษาไทย ไม่ใช่ 500 "Data too long"
        $tooLong = str_repeat('ก', 22000);

        $create = $this->post('/api/v1/seller/products', $this->productForm([
            'category_id' => $category->id,
            'description' => $tooLong,
        ]), ['Accept' => 'application/json'])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->assertMatchesRegularExpression('/[ก-๙]/u', $create->json('errors.description.0'));
        $this->assertSame(1, Product::where('seller_id', $seller->id)->count());

        $this->putJson("/api/v1/seller/products/{$product->id}", $this->productForm([
            'category_id' => $category->id,
            'description' => $tooLong,
        ]))->assertStatus(422)->assertJsonStructure(['errors' => ['description']]);
        $this->assertSame('เดิม', $product->fresh()->description);

        // 20,000 ตัวอักษรไทย (60,000 ไบต์) ยังบันทึกลงคอลัมน์ได้จริง
        $fits = str_repeat('ข', 20000);
        $this->putJson("/api/v1/seller/products/{$product->id}", $this->productForm([
            'category_id' => $category->id,
            'description' => $fits,
        ]))->assertOk();
        $this->assertSame($fits, $product->fresh()->description);
    }

    public function test_list_shows_only_own_products_with_filters_counts_and_search(): void
    {
        [$seller, $store] = $this->readySeller();
        [$other, $otherStore] = $this->readySeller();
        $this->makeProduct($seller, $store, ['name' => 'กาแฟดอยช้าง']);
        $this->makeProduct($seller, $store, ['name' => 'ชาเขียว', 'is_active' => false]);
        $this->makeProduct($seller, $store, ['name' => 'น้ำผึ้ง', 'stock_quantity' => 0, 'stock_status' => 'out_of_stock']);
        $this->makeProduct($seller, $store, ['name' => 'ของต้องห้าม', 'is_blocked' => true, 'block_reason' => 'ผิดนโยบาย']);
        $this->makeProduct($other, $otherStore, ['name' => 'กาแฟร้านอื่น']);
        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/seller/products')
            ->assertOk()
            ->assertJsonCount(4, 'data.products')
            ->assertJsonPath('data.counts.all', 4)
            ->assertJsonPath('data.counts.active', 2)
            ->assertJsonPath('data.counts.hidden', 1)
            ->assertJsonPath('data.counts.out_of_stock', 1)
            ->assertJsonPath('data.counts.blocked', 1)
            ->assertJsonPath('data.pagination.current_page', 1);

        $this->getJson('/api/v1/seller/products?filter=hidden')
            ->assertOk()
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.name', 'ชาเขียว');

        $this->getJson('/api/v1/seller/products?search='.urlencode('กาแฟ'))
            ->assertOk()
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.name', 'กาแฟดอยช้าง');

        // คำค้นเป็นตัวอักษรธรรมดา (% ไม่ใช่ wildcard)
        $this->getJson('/api/v1/seller/products?search=%25')->assertOk()->assertJsonCount(0, 'data.products');

        $this->getJson('/api/v1/seller/products?filter=blocked')
            ->assertOk()
            ->assertJsonPath('data.products.0.read_only', true)
            ->assertJsonPath('data.products.0.read_only_code', 'PRODUCT_BLOCKED');
    }

    public function test_other_sellers_product_is_not_found_for_every_action(): void
    {
        [$owner, $ownerStore] = $this->readySeller();
        [$intruder] = $this->readySeller();
        $product = $this->makeProduct($owner, $ownerStore, ['name' => 'ของเจ้าของ', 'price' => 100]);
        $image = ProductImage::create(['product_id' => $product->id, 'image_url' => 'products/x.webp', 'sort_order' => 1]);
        Sanctum::actingAs($intruder);

        $this->getJson("/api/v1/seller/products/{$product->id}")->assertNotFound()->assertJsonPath('code', 'PRODUCT_NOT_FOUND');
        $this->putJson("/api/v1/seller/products/{$product->id}", $this->productForm(['category_id' => $product->category_id]))->assertNotFound();
        $this->postJson("/api/v1/seller/products/{$product->id}/active", ['is_active' => false])->assertNotFound();
        $this->postJson("/api/v1/seller/products/{$product->id}/stock", ['stock_quantity' => 0])->assertNotFound();
        $this->post("/api/v1/seller/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ], ['Accept' => 'application/json'])->assertNotFound();
        $this->postJson("/api/v1/seller/products/{$product->id}/images/main", ['image_id' => $image->id])->assertNotFound();
        $this->postJson("/api/v1/seller/products/{$product->id}/images/order", ['order' => [$image->id]])->assertNotFound();
        $this->deleteJson("/api/v1/seller/products/{$product->id}/images/{$image->id}")->assertNotFound();
        $this->deleteJson("/api/v1/seller/products/{$product->id}")->assertNotFound();

        // quote ด้วย product_id ของคนอื่น = คิดแบบสินค้าใหม่ของตัวเอง (ไม่รั่วข้อมูลสินค้าคนอื่น)
        $this->postJson('/api/v1/seller/products/quote', ['price' => 100, 'product_id' => $product->id])->assertOk();

        $product->refresh();
        $this->assertSame('ของเจ้าของ', $product->name);
        $this->assertTrue((bool) $product->is_active);
        $this->assertSame(10, (int) $product->stock_quantity);
        $this->assertNull($product->deleted_at);
        $this->assertDatabaseHas('product_images', ['id' => $image->id, 'product_id' => $product->id]);
    }

    public function test_update_toggle_stock_and_soft_delete(): void
    {
        [$seller, $store] = $this->readySeller();
        $product = $this->makeProduct($seller, $store, ['name' => 'ชื่อเดิม', 'customer_cashback' => 5]);
        $category = $this->makeCategory();
        Sanctum::actingAs($seller);

        $this->getJson("/api/v1/seller/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'ชื่อเดิม')
            ->assertJsonPath('data.web_edit_path', "/seller/products/{$product->id}/edit");

        $this->putJson("/api/v1/seller/products/{$product->id}", $this->productForm([
            'name' => 'ชื่อใหม่',
            'category_id' => $category->id,
            'price' => 250,
            'compare_at_price' => 300,
            'stock_quantity' => 0,
            'shipping_method' => 'flat_rate',
            'shipping_fee' => 40,
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'ชื่อใหม่')
            ->assertJsonPath('data.price', 250)
            ->assertJsonPath('data.shipping_method', 'flat_rate')
            ->assertJsonPath('data.is_out_of_stock', true);

        $product->refresh();
        $this->assertSame('out_of_stock', $product->stock_status);
        $this->assertSame($category->id, (int) $product->category_id);
        $this->assertSame(5.0, (float) $product->customer_cashback, 'เงินคืนลูกค้า (ฟีเจอร์เว็บ) ต้องคงค่าเดิม');

        // ค่าส่งเกินเพดาน
        $this->putJson("/api/v1/seller/products/{$product->id}", $this->productForm([
            'category_id' => $category->id,
            'shipping_method' => 'flat_rate',
            'shipping_fee' => 5001,
        ]))->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->postJson("/api/v1/seller/products/{$product->id}/active", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        // ส่งซ้ำได้ผลเหมือนเดิม (ตั้งค่า ไม่ใช่สลับ)
        $this->postJson("/api/v1/seller/products/{$product->id}/active", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->postJson("/api/v1/seller/products/{$product->id}/stock", ['stock_quantity' => 7])
            ->assertOk()
            ->assertJsonPath('data.stock_quantity', 7)
            ->assertJsonPath('data.stock_status', 'in_stock');
        $this->postJson("/api/v1/seller/products/{$product->id}/stock", ['stock_quantity' => -1])->assertStatus(422);

        $this->deleteJson("/api/v1/seller/products/{$product->id}")->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson("/api/v1/seller/products/{$product->id}")->assertNotFound();
    }

    public function test_blocked_and_variant_products_are_read_only(): void
    {
        [$seller, $store] = $this->readySeller();
        $blocked = $this->makeProduct($seller, $store, ['name' => 'ถูกระงับ', 'is_blocked' => true, 'block_reason' => 'ภาพไม่เหมาะสม']);
        $variant = $this->makeProduct($seller, $store, ['name' => 'มีสีให้เลือก', 'has_variants' => true]);
        Sanctum::actingAs($seller);

        $this->putJson("/api/v1/seller/products/{$blocked->id}", $this->productForm(['category_id' => $blocked->category_id]))
            ->assertStatus(423)
            ->assertJsonPath('code', 'PRODUCT_BLOCKED');
        $this->postJson("/api/v1/seller/products/{$blocked->id}/active", ['is_active' => true])->assertStatus(423);
        $this->deleteJson("/api/v1/seller/products/{$blocked->id}")->assertStatus(423);
        $this->assertStringContainsString('ภาพไม่เหมาะสม', (string) $this->getJson("/api/v1/seller/products/{$blocked->id}")->json('data.read_only_reason'));

        $this->putJson("/api/v1/seller/products/{$variant->id}", $this->productForm(['category_id' => $variant->category_id]))
            ->assertStatus(423)
            ->assertJsonPath('code', 'VARIANTS_WEB_ONLY')
            ->assertJsonPath('data.web_edit_path', "/seller/products/{$variant->id}/edit");
        $this->postJson("/api/v1/seller/products/{$variant->id}/stock", ['stock_quantity' => 1])->assertStatus(423);
        $this->deleteJson("/api/v1/seller/products/{$variant->id}")->assertStatus(423);
        // เปิด/ปิดขายไม่กระทบตัวเลือกย่อย → ทำได้
        $this->postJson("/api/v1/seller/products/{$variant->id}/active", ['is_active' => false])->assertOk();

        $this->assertSame('ถูกระงับ', $blocked->fresh()->name);
        $this->assertNull($variant->fresh()->deleted_at);
    }

    public function test_image_management_add_set_main_reorder_and_delete(): void
    {
        [$seller, $store] = $this->readySeller();
        $product = $this->makeProduct($seller, $store);
        Sanctum::actingAs($seller);

        // สินค้าไม่มีรูปหลัก → รูปแรกเป็นรูปหลัก ที่เหลือเป็นรูปเพิ่มเติม
        $this->post("/api/v1/seller/products/{$product->id}/images", [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(3, 'data.images');

        $product->refresh();
        $oldMain = $product->getRawOriginal('main_image_url');
        $this->assertNotNull($oldMain);
        $gallery = ProductImage::where('product_id', $product->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $gallery);

        // ตั้งรูปหลัก = สลับกับรูปหลักเดิม
        $this->postJson("/api/v1/seller/products/{$product->id}/images/main", ['image_id' => $gallery[1]->id])
            ->assertOk()
            ->assertJsonPath('data.images.0.is_main', true);
        $this->assertSame($gallery[1]->image_url, $product->fresh()->getRawOriginal('main_image_url'));
        $this->assertSame($oldMain, $gallery[1]->fresh()->image_url);

        // เรียงใหม่ต้องส่งครบทุกรูป
        $this->postJson("/api/v1/seller/products/{$product->id}/images/order", ['order' => [$gallery[1]->id]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'IMAGE_ORDER_MISMATCH');
        $this->postJson("/api/v1/seller/products/{$product->id}/images/order", ['order' => [$gallery[1]->id, $gallery[0]->id]])
            ->assertOk();
        $this->assertSame(1, (int) $gallery[1]->fresh()->sort_order);
        $this->assertSame(2, (int) $gallery[0]->fresh()->sort_order);

        // ลบรูปเพิ่มเติม → ไฟล์หายด้วย (ไม่มีออเดอร์ใช้)
        $deletedPath = $gallery[0]->image_url;
        $this->deleteJson("/api/v1/seller/products/{$product->id}/images/{$gallery[0]->id}")->assertOk();
        Storage::disk('public')->assertMissing($deletedPath);

        // ลบรูปหลัก → รูปเพิ่มเติมขึ้นมาแทน
        $this->deleteJson("/api/v1/seller/products/{$product->id}/images/0")->assertOk()->assertJsonCount(1, 'data.images');
        $this->assertSame(0, ProductImage::where('product_id', $product->id)->count());

        // เหลือรูปเดียว → ลบรูปหลักไม่ได้
        $this->deleteJson("/api/v1/seller/products/{$product->id}/images/0")
            ->assertStatus(422)
            ->assertJsonPath('code', 'MAIN_IMAGE_REQUIRED');

        // รูปของสินค้าอื่น (ของตัวเอง) ใช้ข้ามสินค้าไม่ได้
        $otherProduct = $this->makeProduct($seller, $store);
        $foreign = ProductImage::create(['product_id' => $otherProduct->id, 'image_url' => 'products/other.webp', 'sort_order' => 1]);
        $this->postJson("/api/v1/seller/products/{$product->id}/images/main", ['image_id' => $foreign->id])->assertNotFound();
        $this->deleteJson("/api/v1/seller/products/{$product->id}/images/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas('product_images', ['id' => $foreign->id]);
    }

    public function test_gallery_limit_and_quote(): void
    {
        [$seller, $store] = $this->readySeller();
        $product = $this->makeProduct($seller, $store, ['main_image_url' => 'products/main.webp']);
        for ($i = 1; $i <= 10; $i++) {
            ProductImage::create(['product_id' => $product->id, 'image_url' => "products/g{$i}.webp", 'sort_order' => $i]);
        }
        Sanctum::actingAs($seller);

        $this->post("/api/v1/seller/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->image('extra.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'TOO_MANY_IMAGES');
        $this->assertSame(10, ProductImage::where('product_id', $product->id)->count());

        $quote = $this->postJson('/api/v1/seller/products/quote', ['price' => 100, 'product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('data.gp_rate', 10)
            ->json('data');
        $this->assertGreaterThan(0, $quote['seller_net']);
        $this->assertLessThan(100, $quote['seller_net']);
        $this->assertNotEmpty($quote['lines']);

        $this->getJson('/api/v1/seller/products/meta')
            ->assertOk()
            ->assertJsonPath('data.limits.max_shipping_fee', 5000)
            ->assertJsonPath('data.limits.max_gallery_images', 10);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'เสื้อยืดลายไทย',
            'category_id' => 0,
            'price' => 199,
            'stock_quantity' => 25,
            'short_description' => 'ผ้าฝ้ายเนื้อนุ่ม',
            'description' => 'รายละเอียดสินค้า',
            'shipping_method' => 'store_default',
        ], $overrides);
    }
}
