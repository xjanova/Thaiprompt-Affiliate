<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MlmGlobalSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * เผยแพร่สินค้า Lazada affiliate (marketplace_products) → หน้าร้าน (ตาราง products)
 * ภายใต้ร้าน "Lazada Affiliate" — ลูกค้าเห็นเป็นสินค้าปกติ + ป้ายค่าคอม/PV + ปุ่มวิ่งไป Lazada
 *
 * PV = (ค่าคอมยืนยัน × ปันผล%) ÷ commission_per_pv  (ใช้ค่ากลาง MLM เดิม ไม่ขัดกัน)
 *
 *   php artisan lazada:publish-storefront 2            # stage (is_hidden=true) — ยังไม่โชว์
 *   php artisan lazada:publish-storefront 2 --visible  # โชว์หน้าร้านเลย
 */
class LazadaPublishStorefront extends Command
{
    protected $signature = 'lazada:publish-storefront
        {account : ID บัญชี affiliate_native}
        {--visible : เปิดให้เห็นหน้าร้านทันที (ไม่งั้น stage ซ่อนไว้ก่อน)}
        {--dividend= : เปอร์เซ็นต์ปันผลเข้ากอง PV (ไม่ระบุ = ค่ากลาง MLM หรือ 50)}';

    protected $description = 'เผยแพร่สินค้า Lazada affiliate เข้าหน้าร้าน (ร้าน Lazada Affiliate) + คำนวณ PV จากค่าคอม';

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->argument('account'));
        if (! $account) {
            $this->error('ไม่พบบัญชี marketplace_accounts id='.$this->argument('account'));

            return self::FAILURE;
        }

        $visible = (bool) $this->option('visible');
        $commissionPerPv = max(0.01, (float) MlmGlobalSetting::get('commission_per_pv', 1));
        $dividendPercent = $this->option('dividend') !== null
            ? (float) $this->option('dividend')
            : (float) MlmGlobalSetting::get('lazada_affiliate_dividend_percent', 50);

        $store = $this->ensureLazadaStore($account);
        $categoryId = $this->ensureLazadaCategory()->id;

        $this->info("🏪 ร้าน: {$store->store_name} (id={$store->id}) | PV = ค่าคอม × {$dividendPercent}% ÷ {$commissionPerPv}");
        $this->info($visible ? '👁️  โหมด: เปิดให้เห็นหน้าร้าน' : '🙈 โหมด: stage (ซ่อนไว้ก่อน ใช้ --visible เพื่อเปิด)');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        MarketplaceProduct::where('source', 'affiliate_feed')
            ->where('is_active', true)
            ->whereNotNull('affiliate_url')
            ->chunkById(100, function ($rows) use (&$created, &$updated, &$skipped, $store, $categoryId, $commissionPerPv, $dividendPercent, $visible) {
                foreach ($rows as $mp) {
                    try {
                        $commissionAmount = (float) ($mp->commission_amount ?? 0);
                        $pv = round($commissionAmount * ($dividendPercent / 100) / $commissionPerPv, 2);

                        $sku = 'LZDAFF-'.$mp->external_product_id;
                        $existing = Product::where('store_id', $store->id)
                            ->where('external_platform', 'lazada')
                            ->where('external_product_id', $mp->external_product_id)
                            ->first();

                        $payload = [
                            'seller_id' => $store->user_id,
                            'store_id' => $store->id,
                            'category_id' => $categoryId,
                            'name' => $mp->name,
                            'sku' => $sku,
                            'brand' => $mp->brand ?: null,
                            'price' => (float) $mp->price,
                            'compare_at_price' => $mp->original_price ?: null,
                            'main_image_url' => $mp->main_image_url,
                            'image_urls' => is_array($mp->images) ? $mp->images : [],
                            'commission_rate' => (float) ($mp->commission_rate ?? 0),
                            'pv_value' => $pv,
                            'stock_status' => 'in_stock',
                            'track_inventory' => false,
                            'stock_quantity' => 99,
                            'is_affiliate' => true,
                            'affiliate_url' => $mp->affiliate_url,
                            'external_platform' => 'lazada',
                            'external_product_id' => $mp->external_product_id,
                            'shipping_speed' => 'fast', // Lazada = ในไทย ส่งไว
                            'is_active' => true,
                            'is_hidden' => ! $visible,
                            'is_public_approved' => true,
                            'public_approved_at' => now(),
                            'published_at' => now(),
                            'is_virtual' => false,
                        ];

                        if ($existing) {
                            // เว้น slug เดิม (กันลิงก์เปลี่ยน) — อัพเดตฟิลด์ที่เหลือ
                            $existing->update($payload);
                            $updated++;
                        } else {
                            $payload['slug'] = mb_substr(Str::slug($mp->name) ?: 'lazada', 0, 180).'-lzd-'.$mp->external_product_id;
                            Product::create($payload);
                            $created++;
                        }
                    } catch (\Throwable $e) {
                        $skipped++;
                        Log::warning('lazada:publish-storefront ล้มเหลว', ['pid' => $mp->external_product_id, 'error' => $e->getMessage()]);
                    }
                }
            });

        // อัพเดตจำนวนสินค้าในร้าน
        $store->update(['total_products' => Product::where('store_id', $store->id)->count()]);

        $this->newLine();
        $this->info(sprintf('✅ เสร็จ — สร้าง %d, อัพเดท %d, ข้าม %d | รวมในร้าน %d ชิ้น %s',
            $created, $updated, $skipped, $store->total_products, $visible ? '(โชว์แล้ว)' : '(ซ่อนไว้ รอ --visible)'));

        return self::SUCCESS;
    }

    /**
     * หา/สร้างร้าน "Lazada Affiliate"
     */
    private function ensureLazadaStore(MarketplaceAccount $account): VendorStore
    {
        $store = VendorStore::where('store_slug', VendorStore::LAZADA_STORE_SLUG)->first();
        if ($store) {
            // อัพเดตชนิด/แพลตฟอร์มเผื่อร้านมีอยู่ก่อน
            $store->update(['store_type' => VendorStore::STORE_TYPE_LAZADA, 'platform_slug' => 'lazada']);

            return $store;
        }

        return VendorStore::create([
            'user_id' => $account->user_id ?? \App\Models\User::query()->min('id'),
            'store_name' => 'Lazada Affiliate',
            'store_slug' => VendorStore::LAZADA_STORE_SLUG,
            'store_description' => 'สินค้าคัดสรรจาก Lazada — กดซื้อผ่านเรารับ PV/ปันผล ส่งไวในไทย 🟢',
            'store_type' => VendorStore::STORE_TYPE_LAZADA,
            'platform_slug' => 'lazada',
            'primary_color' => '#0f156d',
            'secondary_color' => '#f57224',
            'is_active' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * หา/สร้างหมวด "Lazada Affiliate"
     */
    private function ensureLazadaCategory(): ProductCategory
    {
        return ProductCategory::firstOrCreate(
            ['slug' => 'lazada-affiliate'],
            [
                'name' => 'สินค้า Lazada',
                'description' => 'สินค้าแนะนำจาก Lazada (affiliate)',
                'is_active' => true,
                'sort_order' => 50,
            ]
        );
    }
}
