<?php

namespace Tests\Feature\PosRider;

use App\Models\Order;
use App\Models\PosTerminalSale;
use PHPUnit\Framework\Attributes\Group;

/**
 * POS sync เดิมที่แก้บั๊ก: ดึงสินค้า (products.store_id) / หมวดหมู่ / อัปโหลดบิลหน้าร้าน (ทีละบิล ข้อความไทย)
 */
#[Group('pos-rider')]
class PosTerminalSyncTest extends PosRiderTestCase
{
    /**
     * sync สินค้าได้เฉพาะสินค้า active ของร้านตัวเอง พร้อม id ของ server ให้ POS จับคู่
     */
    public function test_sync_products_returns_own_store_active_products_with_server_ids(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [$otherSeller, $otherStore] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);

        $active = $this->makeStoreProduct($seller, $store, ['name' => 'ข้าวมันไก่', 'price' => 60, 'stock_quantity' => 7, 'main_image_url' => 'https://cdn.example.com/rice.jpg']);
        $this->makeStoreProduct($seller, $store, ['is_active' => false]);
        $this->makeStoreProduct($otherSeller, $otherStore);

        $response = $this->postJson('/api/pos/sync/products', [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.sku', $active->sku)
            ->assertJsonPath('data.0.name', 'ข้าวมันไก่')
            ->assertJsonPath('data.0.stock', 7)
            ->assertJsonPath('data.0.category_id', $active->category_id)
            ->assertJsonPath('data.0.image_url', 'https://cdn.example.com/rice.jpg')
            ->assertJsonPath('data.0.orderable_online', true);

        $this->assertEqualsWithDelta(60.0, $response->json('data.0.price'), 0.001);
        $this->assertArrayHasKey('barcode', $response->json('data.0'));

        $this->postJson('/api/pos/sync/products', [], array_merge($headers, ['X-API-Key' => 'pk_wrong']))
            ->assertStatus(401);
    }

    /**
     * sync หมวดหมู่ไม่ล้ม (เดิมอ้างคลาสที่ไม่มี) — คืนหมวดที่สินค้าของร้านใช้
     */
    public function test_sync_categories_returns_categories_used_by_store_products(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $this->postJson('/api/pos/sync/categories', [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->category_id);
    }

    /**
     * อัปโหลดบิล: บันทึกทีละบิล · บิลเสียได้ข้อความไทย (ไม่ใช่ exception ดิบ) · อัปโหลดซ้ำไม่บันทึกซ้ำ · ไม่สร้าง orders
     */
    public function test_upload_orders_is_per_order_robust_and_idempotent(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [$terminal, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $payload = ['orders' => [
            [
                'local_id' => 'POS-0001',
                'total' => 120,
                'created_at' => now()->subHour()->toIso8601String(),
                'payment_method' => 'cash',
                'items' => [['product_id' => $product->id, 'name' => 'ข้าวมันไก่', 'quantity' => 2, 'price' => 60]],
            ],
            [
                'local_id' => 'POS-0002',
                'total' => 50,
                'created_at' => now()->toIso8601String(),
                // ไม่มีรายการสินค้า
            ],
        ]];

        $response = $this->postJson('/api/pos/sync/orders', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uploaded', ['POS-0001'])
            ->assertJsonPath('data.errors.0.local_id', 'POS-0002');

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertMatchesRegularExpression('/\p{Thai}/u', (string) $response->json('data.errors.0.error'));

        $sale = PosTerminalSale::where('pos_terminal_id', $terminal->id)->firstOrFail();
        $this->assertSame('POS-0001', $sale->local_id);
        $this->assertSame((int) $store->id, (int) $sale->store_id);
        $this->assertEqualsWithDelta(120.0, (float) $sale->total, 0.001);
        $this->assertSame('ข้าวมันไก่', $sale->items[0]['name']);
        $this->assertSame(0, Order::count(), 'บิลหน้าร้านไม่ใช่ออเดอร์ร้านค้าออนไลน์');

        // อัปโหลดซ้ำ → นับว่าอัปโหลดแล้ว ไม่บันทึกซ้ำ
        $this->postJson('/api/pos/sync/orders', ['orders' => [$payload['orders'][0]]], $headers)
            ->assertOk()
            ->assertJsonPath('data.uploaded', ['POS-0001'])
            ->assertJsonPath('data.errors', []);
        $this->assertSame(1, PosTerminalSale::count());
    }

    /**
     * บิลที่ส่งไรเดอร์ผ่านแอปแล้ว (มีออเดอร์ของเครื่องนี้ด้วย local_id เดียวกัน) → รับว่าอัปโหลดแล้ว ไม่บันทึกเป็นยอดหน้าร้านซ้ำ
     */
    public function test_upload_orders_skips_sales_already_paid_online_via_rider_request(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [$terminal, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);
        $buyer = $this->makeCustomer(0);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]]);
        $order->forceFill(['pos_terminal_id' => $terminal->id, 'pos_local_id' => 'POS-0100'])->saveQuietly();

        $this->postJson('/api/pos/sync/orders', ['orders' => [[
            'local_id' => 'POS-0100',
            'total' => 60,
            'created_at' => now()->toIso8601String(),
            'items' => [['product_id' => $product->id, 'name' => 'ข้าว', 'quantity' => 1, 'price' => 60]],
        ]]], $headers)
            ->assertOk()
            ->assertJsonPath('data.uploaded', ['POS-0100'])
            ->assertJsonPath('data.errors', []);

        $this->assertSame(0, PosTerminalSale::count());
    }
}
