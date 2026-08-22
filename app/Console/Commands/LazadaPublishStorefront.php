<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MlmGlobalSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        {--fresh : ล้างสินค้า Lazada เดิมในร้านก่อน (คัดใหม่)}
        {--max-price= : เผยแพร่เฉพาะราคา ≤ นี้ (บาท) — คัดของใช้ทั่วไป}
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
        $maxPrice = $this->option('max-price') !== null && $this->option('max-price') !== ''
            ? (float) $this->option('max-price') : 0.0;

        $this->info("🏪 ร้าน: {$store->store_name} (id={$store->id}) | PV = ค่าคอม × {$dividendPercent}% ÷ {$commissionPerPv}".($maxPrice > 0 ? " | ราคา ≤ {$maxPrice}฿" : ''));
        $this->info($visible ? '👁️  โหมด: เปิดให้เห็นหน้าร้าน' : '🙈 โหมด: stage (ซ่อนไว้ก่อน ใช้ --visible เพื่อเปิด)');

        // --fresh: ล้างสินค้า Lazada เดิมในร้านก่อนคัดใหม่
        // ⚠️ ต้อง forceDelete (Product ใช้ SoftDeletes) ไม่งั้น row เก่าค้าง → re-create ชน unique slug/sku
        //
        // 🚨 (2026-08-23) กันข้อมูลออเดอร์ลูกค้าหาย
        //   `order_items.product_id` เป็น FK `constrained('products')->onDelete('cascade')`
        //   ⇒ forceDelete สินค้า = **ลบรายการในออเดอร์ที่ลูกค้าสั่งไปแล้วทิ้งด้วย**
        //     ประวัติการสั่งซื้อจะกลายเป็นออเดอร์เปล่า กู้ไม่ได้ ไม่มี soft delete มารองรับ
        //   ⇒ ถ้ามีสินค้าชิ้นไหนเคยถูกสั่ง ให้หยุดทันที ให้คนตัดสินใจเอง
        if ($this->option('fresh')) {
            $targets = Product::where('store_id', $store->id)->where('external_platform', 'lazada');
            $targetIds = (clone $targets)->pluck('id');

            $orderedCount = $targetIds->isEmpty() ? 0 : DB::table('order_items')->whereIn('product_id', $targetIds)->count();

            if ($orderedCount > 0) {
                $this->error("⛔ ยกเลิก --fresh: มีสินค้าที่ลูกค้าเคยสั่งไปแล้ว {$orderedCount} รายการในออเดอร์");
                $this->error('   ลบตอนนี้ = รายการในออเดอร์ลูกค้าหายตาม FK cascade กู้ไม่ได้');
                $this->line('   ทางออก: รันโดยไม่ใส่ --fresh (คำสั่งนี้อัปเดตของเดิมอยู่แล้ว)');

                return self::FAILURE;
            }

            $del = $targets->forceDelete();
            $this->warn("🧹 ล้างสินค้า Lazada เดิม {$del} ชิ้น (ตรวจแล้วไม่มีชิ้นไหนอยู่ในออเดอร์)");
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        // ⚠️ ต้องครอบทุก source ที่ตัวนำเข้าเขียนจริง ไม่ใช่แค่ 'affiliate_feed'
        //    lazada:mu-import เขียน 'mu_curated' และตัว scrape เขียน 'scrape'
        //    เดิมกรองแค่ affiliate_feed → สินค้าส่วนใหญ่บนร้านจริงไม่เคยถูกอัพเดตจากคำสั่งนี้เลย
        //    (รวมถึงคำบรรยาย/สเปกที่เพิ่งเพิ่ม ก็จะไม่ไปถึงหน้ารายละเอียด)
        MarketplaceProduct::whereIn('source', ['affiliate_feed', 'mu_curated', 'scrape'])
            ->where('is_active', true)
            ->whereNotNull('affiliate_url')
            ->when($maxPrice > 0, fn ($q) => $q->where('price', '<=', $maxPrice))
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
                            // ⚠️ products.name เป็น varchar(255) แต่ marketplace_products.name เป็น varchar(500)
                            //    ถ้าไม่ตัด แถวที่ชื่อยาวจะ insert ไม่ผ่านแล้วหายเงียบ (ไม่มีอะไรฟ้องบนจอ)
                            'name' => mb_substr((string) $mp->name, 0, 255),
                            'sku' => $sku,
                            'brand' => $mp->brand ?: null,
                            'price' => (float) $mp->price,
                            'compare_at_price' => $mp->original_price ?: null,
                            'main_image_url' => $mp->main_image_url,
                            'image_urls' => is_array($mp->images) ? $mp->images : [],
                            // 📝 รายละเอียด/สเปก — ต้องยกมาด้วย ไม่งั้นหน้ารายละเอียดสินค้าว่างเปล่า
                            //    (ตัวนำเข้าดึงมาเก็บใน marketplace_products ครบอยู่แล้ว แค่ไม่เคยถูกก็อปต่อ)
                            'description' => $mp->description ?: null,
                            'short_description' => $this->shortDescriptionFrom($mp),
                            'attributes' => is_array($mp->attributes) ? $mp->attributes : null,
                            'sales_count' => (int) ($mp->sales_count ?? 0),
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
     * คำโปรยสั้นสำหรับหน้ารายละเอียด/SEO
     *
     * สินค้าจากฟีด affiliate มักไม่มี description เลย (ฟีดให้มาแค่ชื่อ+รูป+ราคา)
     * จึงประกอบจากข้อมูลที่มีจริง ไม่ปล่อยให้หน้ารายละเอียดว่าง
     *
     * ⚠️ ห้ามแต่งข้อมูลที่ไม่มีจริง (เช่น "ของแท้ 100%" หรือคะแนนรีวิวที่ไม่ได้ดึงมา)
     */
    private function shortDescriptionFrom(MarketplaceProduct $mp): string
    {
        $plain = trim(strip_tags((string) ($mp->description ?? '')));
        if ($plain !== '') {
            return mb_substr($plain, 0, 480);
        }

        $parts = array_filter([
            $mp->brand ? 'แบรนด์ '.$mp->brand : null,
            $mp->category ? 'หมวด '.$mp->category : null,
            'จัดส่งโดย Lazada',
        ]);

        return mb_substr(trim((string) $mp->name).' — '.implode(' · ', $parts), 0, 480);
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
