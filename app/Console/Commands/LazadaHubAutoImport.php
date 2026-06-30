<?php

namespace App\Console\Commands;

use App\Models\LazadaAutoImportQueue;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSetting;
use App\Services\LazadaImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * นำเข้าสินค้า Lazada อัตโนมัติแบบค่อยเป็นค่อยไป (อ่านจากคิว lazada_auto_import_queue)
 *
 * รันโดย scheduler ทุก 5 นาที → ดึงทีละ N รายการ (default 5) เข้า catalog
 * จนกว่าคิวจะหมด — กัน Lazada บล็อก (captcha) + ไม่ต้องนั่งกดเอง
 *
 * เปิด/ปิดด้วย setting `lazada_auto_sync_enabled`
 */
class LazadaHubAutoImport extends Command
{
    protected $signature = 'lazada-hub:auto-import {--limit=5 : จำนวนสูงสุดต่อรอบ} {--force : ข้ามการเช็ค setting เปิด/ปิด}';

    protected $description = 'นำเข้าสินค้า Lazada จากคิวอัตโนมัติ (ค่อยๆ ทีละน้อย)';

    public function handle(LazadaImportService $importer): int
    {
        if (! Schema::hasTable('lazada_auto_import_queue')) {
            $this->warn('ยังไม่มีตาราง lazada_auto_import_queue (รัน migrate ก่อน)');

            return self::SUCCESS;
        }

        // เปิด/ปิดด้วย setting (ยกเว้น --force)
        if (! $this->option('force') && ! filter_var(MarketplaceSetting::get('lazada_auto_sync_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('auto-import ปิดอยู่ (เปิดที่ตั้งค่า Lazada Hub) — ข้าม');

            return self::SUCCESS;
        }

        $limit = max(1, min(20, (int) $this->option('limit')));

        $items = LazadaAutoImportQueue::where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            $this->info('ไม่มีคิวรอนำเข้า');

            return self::SUCCESS;
        }

        $platformId = $this->lazadaPlatformId();
        $defaults = $this->priceDefaults();

        $done = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $data = $importer->fetchProductData($item->url);
                $price = (float) $data['price'];

                // กรองช่วงราคา
                if (($item->price_min !== null && $price < (float) $item->price_min)
                    || ($item->price_max !== null && $price > (float) $item->price_max)) {
                    $item->update(['status' => 'skipped', 'error' => 'นอกช่วงราคา ('.number_format($price).' ฿)']);
                    $skipped++;

                    continue;
                }

                // 🔒 ล็อกต่อ item (key เดียวกับ manual import) กัน race → สินค้าซ้ำ
                $lock = \Illuminate\Support\Facades\Cache::lock('lazada_catalog:'.$platformId.':'.$data['item_id'], 15);
                if (! $lock->get()) {
                    continue; // กระบวนการอื่นกำลังนำเข้า item นี้ — ปล่อย pending ไว้ รอบหน้าค่อยลอง
                }

                try {
                    // กันซ้ำ
                    $existing = MarketplaceProduct::where('platform_id', $platformId)
                        ->where('external_product_id', $data['item_id'])
                        ->first();
                    if ($existing) {
                        $item->update(['status' => 'skipped', 'error' => 'มีในแคตตาล็อกแล้ว', 'marketplace_product_id' => $existing->id]);
                        $skipped++;

                        continue;
                    }

                    $product = $this->createCatalogProduct($data, $item->fulfillment_mode ?: $defaults['mode'], $defaults, $platformId);
                    $item->update(['status' => 'done', 'marketplace_product_id' => $product->id, 'error' => null]);
                    $done++;
                } finally {
                    $lock->release();
                }
            } catch (\Throwable $e) {
                $attempts = $item->attempts + 1;
                $item->update([
                    'attempts' => $attempts,
                    'error' => mb_substr($e->getMessage(), 0, 500),
                    'status' => $attempts >= 3 ? 'failed' : 'pending', // ลองใหม่ได้ถึง 3 ครั้ง
                ]);
                $failed++;
                Log::warning('Lazada auto-import: ดึงล้มเหลว', ['queue_id' => $item->id, 'url' => $item->url, 'error' => $e->getMessage()]);
            }
        }

        $remaining = LazadaAutoImportQueue::where('status', 'pending')->count();
        $this->info("นำเข้า {$done} · ข้าม {$skipped} · ผิดพลาด {$failed} · เหลือในคิว {$remaining}");

        return self::SUCCESS;
    }

    /**
     * สร้าง marketplace_products จากข้อมูล scrape (มิเรอร์ CatalogController::importStore)
     */
    private function createCatalogProduct(array $data, string $mode, array $defaults, int $platformId): MarketplaceProduct
    {
        $cost = (float) $data['price'];

        return MarketplaceProduct::create([
            'account_id' => null,
            'platform_id' => $platformId,
            'fulfillment_mode' => in_array($mode, ['affiliate', 'resell'], true) ? $mode : $defaults['mode'],
            'external_product_id' => $data['item_id'],
            'name' => mb_substr($data['name'], 0, 500),
            'description' => $data['description_html'] ?? null,
            'brand' => $data['brand'],
            'category' => $data['lazada_category'] ?: null,
            'price' => $cost,
            'original_price' => $data['compare_at_price'],
            'cost_price' => $cost,
            'selling_price' => MarketplaceProduct::computeSellingPrice($cost, $defaults['markup'], $defaults['rounding']),
            'markup_percent' => $defaults['markup'],
            'price_is_manual' => false,
            'currency' => 'THB',
            'stock_quantity' => 0,
            'is_available' => true,
            'main_image_url' => $data['main_image'],
            'images' => $data['images'],
            'variants' => $data['variants'],
            'affiliate_url' => $data['source_url'],
            'commission_rate' => $defaults['commission'],
            'source' => 'scrape',
            'source_url' => $data['source_url'],
            'sync_status' => 'synced',
            'is_active' => true,
            'last_synced_at' => now(),
        ]);
    }

    private function priceDefaults(): array
    {
        return [
            'markup' => (float) MarketplaceSetting::get('lazada_default_markup_percent', 30),
            'rounding' => (string) MarketplaceSetting::get('lazada_price_rounding', 'baht'),
            'mode' => in_array($m = (string) MarketplaceSetting::get('lazada_default_fulfillment_mode', 'affiliate'), ['affiliate', 'resell'], true) ? $m : 'affiliate',
            'commission' => (float) MarketplaceSetting::get('affiliate_commission_default', 5),
        ];
    }

    private function lazadaPlatformId(): int
    {
        return MarketplacePlatform::firstOrCreate(
            ['slug' => 'lazada'],
            ['name' => 'Lazada', 'is_active' => true]
        )->id;
    }
}
