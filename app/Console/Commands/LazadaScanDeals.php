<?php

namespace App\Console\Commands;

use App\Http\Controllers\StorefrontController;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Models\MlmGlobalSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Marketplace\LazadaAffiliateService;
use App\Services\Marketplace\LazadaDealScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ⚡ กวาด "สินค้าที่ Lazada จัดโปรจริง" ขึ้นแถบ Flash Deals หน้าแรก
 *
 * ทำไมต้องมีคำสั่งนี้ (บันทึกไว้กันลืม):
 *   Flash Deals เดิมคัดจาก `compare_at_price > price OR is_featured` ซึ่งวัดจริงบนพร็อด
 *   2026-09-09 ได้ 85 ชิ้น **เป็นสินค้า seeder เดโมทั้ง 85 ชิ้น** (iPhone/คอร์สเรียน/โซฟา IKEA)
 *   ส่วนสินค้า affiliate จริง 2,104 ชิ้น ไม่มี compare_at_price สักชิ้นเดียว
 *   เพราะฟีด affiliate ทางการคืนแค่ `discountPrice` — ไม่มีราคาก่อนลด
 *
 * เส้นทางข้อมูล 3 ทอด (แต่ละทอดตัดของที่เชื่อไม่ได้ทิ้ง):
 *   1) หน้ารายการ Lazada  → ได้ราคาก่อนลด + ราคาโปร + % ส่วนลด  (LazadaDealScanner)
 *   2) ฟีด affiliate      → ยืนยันว่า "กินค่าคอมได้" + ได้เรตค่าคอมจริง
 *   3) API สร้างลิงก์      → ได้ลิงก์ที่ track ค่าคอมของเรา (ไม่ได้ลิงก์ = ไม่เอาขึ้นหน้าแรก)
 *
 * ✅ วัดจริงบนพร็อด 2026-09-09: ของบนหน้ารายการ ~90% ลดราคาจริง · 36/40 กินค่าคอมได้
 *    และราคาจากหน้ารายการ **ตรงกับราคาในฟีดเป๊ะ** ⇒ สองแหล่งไม่ขัดกัน
 *
 * Usage:
 *   php artisan lazada:scan-deals --dry
 *   php artisan lazada:scan-deals --limit=24
 *   php artisan lazada:scan-deals --keyword="หม้อทอดไร้น้ำมัน" --min-discount=40
 */
class LazadaScanDeals extends Command
{
    protected $signature = 'lazada:scan-deals
        {--account=2 : id ของ MarketplaceAccount (program_type=affiliate_native)}
        {--keyword= : ทำเฉพาะคำค้นนี้คำเดียว (ว่าง = ใช้ทุกคำใน config/lazada-deals.php)}
        {--pages= : กวาดกี่หน้าต่อคำค้น (ว่าง = ตาม config)}
        {--limit= : เอาขึ้นหน้าแรกสูงสุดกี่ชิ้น (ว่าง = ตาม config)}
        {--min-discount= : ส่วนลดขั้นต่ำ % (ว่าง = ตาม config)}
        {--min-commission= : ค่าคอมขั้นต่ำ % (ว่าง = ตาม config)}
        {--max-price= : ราคาสูงสุด (ว่าง = ตาม config)}
        {--dry : กวาดและรายงานอย่างเดียว ไม่เขียนฐานข้อมูล}';

    protected $description = '⚡ กวาดสินค้าที่ Lazada จัดโปรจริง (มีราคาก่อนลด + กินค่าคอมได้) ขึ้นแถบ Flash Deals';

    /** ยิงฟีดยืนยันค่าคอมทีละกี่ id (ฟีดรับ limit สูงสุด 100) */
    private const FEED_CHUNK = 20;

    public function handle(LazadaDealScanner $scanner): int
    {
        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี marketplace_accounts id='.$this->option('account'));

            return self::FAILURE;
        }

        $filters = config('lazada-deals.filters');
        $limits = config('lazada-deals.limits');

        $minDiscount = (int) ($this->option('min-discount') ?: $filters['min_discount_percent']);
        $maxDiscount = (int) $filters['max_discount_percent'];
        $minCommission = (float) ($this->option('min-commission') ?: $filters['min_commission_percent']);
        $minPrice = (float) $filters['min_price'];
        $maxPrice = (float) ($this->option('max-price') ?: $filters['max_price']);
        $minRating = (float) ($filters['min_rating'] ?? 0);
        $minReviews = (int) ($filters['min_reviews'] ?? 0);
        $minSold = (int) ($filters['min_sold'] ?? 0);
        $pages = max(1, (int) ($this->option('pages') ?: $limits['pages_per_keyword']));
        $publishLimit = max(1, (int) ($this->option('limit') ?: $limits['publish_limit']));
        $maxPerSeed = max(1, (int) ($limits['max_per_seed'] ?? 99));
        $linkBudget = max(1, (int) $limits['link_budget']);
        $dry = (bool) $this->option('dry');

        $seeds = $this->resolveSeeds();
        if (empty($seeds)) {
            $this->error('❌ ไม่มีคำค้นให้กวาด — ตรวจ config/lazada-deals.php');

            return self::FAILURE;
        }

        $this->info('⚡ กวาดโปร Lazada '.count($seeds).' คำค้น × '.$pages.' หน้า'.($dry ? ' (dry-run)' : ''));
        $this->line(sprintf('   เกณฑ์: ลด %d-%d%% · ค่าคอม ≥ %.1f%% · ราคา %s-%s฿ · เอาขึ้นไม่เกิน %d ชิ้น (คำค้นละไม่เกิน %d)',
            $minDiscount, $maxDiscount, $minCommission,
            number_format($minPrice), number_format($maxPrice), $publishLimit, $maxPerSeed));
        $this->line(sprintf('   ด่านคุณภาพ: เรตติ้ง ≥ %.1f · รีวิว ≥ %d · ขายแล้ว ≥ %d',
            $minRating, $minReviews, $minSold));
        $this->newLine();

        // ── 1) กวาดหน้ารายการ → ได้ผู้เข้าชิงพร้อมราคาก่อนลด ──────────────────
        $candidates = [];
        $seenTotal = 0;

        foreach ($seeds as $seedIndex => $seed) {
            $keptForSeed = 0;

            for ($page = 1; $page <= $pages; $page++) {
                $rows = $scanner->search($seed['keyword'], $page);
                $seenTotal += count($rows);

                foreach ($rows as $row) {
                    if (! $row['in_stock'] || $row['original_price'] === null) {
                        continue;
                    }
                    if ($row['discount_percent'] < $minDiscount || $row['discount_percent'] > $maxDiscount) {
                        continue;
                    }
                    if ($row['price'] < $minPrice || $row['price'] > $maxPrice) {
                        continue;
                    }

                    // ด่านคุณภาพ — ส่วนลดอย่างเดียวเชื่อไม่ได้ (ผู้ขายตั้งราคาก่อนลดเองได้)
                    // ต้องมีร่องรอยว่ามีคนซื้อจริงและพอใจจริงประกอบด้วย
                    if ($row['rating'] < $minRating || $row['review_count'] < $minReviews || $row['sold_count'] < $minSold) {
                        continue;
                    }

                    // ชิ้นเดียวกันอาจโผล่หลายคำค้น — เก็บครั้งแรก (คำค้นแรกให้หมวดที่ตรงกว่า)
                    if (isset($candidates[$row['item_id']])) {
                        continue;
                    }

                    $row['category_slug'] = $seed['category'];
                    $row['seed_index'] = $seedIndex;
                    $candidates[$row['item_id']] = $row;
                    $keptForSeed++;
                }

                if (empty($rows)) {
                    break;
                }
                usleep((int) $limits['listing_sleep_us']);
            }

            $this->line(sprintf('  %-28s → เข้าเกณฑ์ %d ชิ้น', mb_substr($seed['keyword'], 0, 28), $keptForSeed));
        }

        if (empty($candidates)) {
            $this->warn('⚠️  ไม่พบสินค้าที่เข้าเกณฑ์เลย (ดูจากหน้ารายการ '.$seenTotal.' ชิ้น)');
            $this->expireStaleDeals($dry);

            return self::SUCCESS;
        }

        // ลดมากอยู่หน้าสุด
        uasort($candidates, fn ($a, $b) => $b['discount_percent'] <=> $a['discount_percent']);

        // 🎯 คัดรายชื่อสั้นโดย "เฉลี่ยตามคำค้น" ก่อนไปยิงฟีด
        //    ⚠️ ต้องทำก่อนยิง ไม่ใช่ตอนเขียนฐาน — ถ้าเอาท็อปตามส่วนลดล้วนไปยิง
        //    โควตาฟีดจะถูกหมวดที่ลดหนักกินหมด แล้วพอมาตัดตอนเขียนก็เหลือของไม่พอเติมหน้าแรก
        $shortlist = [];
        $perSeedCount = [];
        $crowdedSeed = 0;
        foreach ($candidates as $itemId => $row) {
            $seedIndex = $row['seed_index'] ?? 0;
            if (($perSeedCount[$seedIndex] ?? 0) >= $maxPerSeed) {
                $crowdedSeed++;

                continue;
            }
            $perSeedCount[$seedIndex] = ($perSeedCount[$seedIndex] ?? 0) + 1;
            $shortlist[(string) $itemId] = $row;

            if (count($shortlist) >= $linkBudget * 2) {
                break;
            }
        }

        $this->newLine();
        $this->info(sprintf('📋 ผู้เข้าชิง %d ชิ้น (จากที่เห็นทั้งหมด %d) → คัดเหลือ %d ชิ้น (ข้ามเพราะคำค้นเต็มโควตา %d)',
            count($candidates), $seenTotal, count($shortlist), $crowdedSeed));

        // ── 2) ยืนยันกับฟีด affiliate ว่า "กินค่าคอมได้" ────────────────────────
        $service = new LazadaAffiliateService($account);
        $probeIds = array_keys($shortlist);
        $eligible = [];

        foreach (array_chunk($probeIds, self::FEED_CHUNK) as $chunk) {
            $feed = $service->getProductFeed(1, 1, 100, null, $chunk);
            if (! $feed['ok']) {
                $this->warn('  ⚠️  ฟีดตอบไม่สำเร็จ: '.($feed['error'] ?? 'ไม่ทราบสาเหตุ'));

                continue;
            }
            foreach ($feed['items'] as $item) {
                $eligible[(string) $item['product_id']] = $item;
            }
            usleep((int) $limits['link_sleep_us']);
        }

        $this->info('🔗 กินค่าคอมได้ '.count($eligible).'/'.count($probeIds).' ชิ้น');

        if (empty($eligible)) {
            $this->warn('⚠️  ไม่มีชิ้นไหนอยู่ในโปรแกรม affiliate — ไม่เอาขึ้นหน้าแรก (ของที่ไม่ได้ค่าคอม ไม่คุ้มพื้นที่)');
            $this->expireStaleDeals($dry);

            return self::SUCCESS;
        }

        // ── 3) ขอลิงก์ค่าคอม + เขียนลงฐาน ────────────────────────────────────
        $store = $this->ensureLazadaStore($account);
        $platformId = MarketplacePlatform::where('slug', 'lazada')->value('id') ?? $account->platform_id;
        $fallbackCategoryId = $this->ensureLazadaCategory()->id;
        $commissionPerPv = max(0.01, (float) MlmGlobalSetting::get('commission_per_pv', 1));
        $dividendPercent = (float) MlmGlobalSetting::get('lazada_affiliate_dividend_percent', 50);

        $published = 0;
        $noLink = 0;
        $lowCommission = 0;
        $failed = 0;
        $verifiedAt = now();

        foreach ($shortlist as $itemId => $row) {
            if ($published >= $publishLimit) {
                break;
            }

            // ⚠️ PHP แปลงคีย์ที่เป็นสตริงตัวเลข ("5223874171") เป็น int ให้อัตโนมัติ
            //    ต้องแคสต์กลับก่อนใช้ ไม่งั้นเทียบกับคีย์ของ $eligible (สตริง) ไม่ตรง
            $itemId = (string) $itemId;

            $feedItem = $eligible[$itemId] ?? null;
            if ($feedItem === null) {
                continue;
            }

            // ฟีดคืนค่าคอมเป็นเศษส่วน (0.05 = 5%)
            $commissionPercent = round(((float) ($feedItem['commission_rate'] ?? 0)) * 100, 2);
            if ($commissionPercent < $minCommission) {
                $lowCommission++;

                continue;
            }

            $affiliateUrl = $service->getProductLink($itemId);
            usleep((int) $limits['link_sleep_us']);

            if (! $affiliateUrl) {
                $noLink++;

                continue;
            }

            if ($dry) {
                $published++;
                $this->line(sprintf('  [dry] -%d%% | %s฿ ← %s฿ | คอม %.1f%% | ⭐%.1f (%d) | ขาย %s | %s',
                    $row['discount_percent'],
                    number_format($row['price'], 0),
                    number_format($row['original_price'], 0),
                    $commissionPercent,
                    $row['rating'],
                    $row['review_count'],
                    number_format($row['sold_count']),
                    mb_substr($row['name'], 0, 38)));

                continue;
            }

            try {
                $commissionAmount = round($row['price'] * ($commissionPercent / 100), 2);
                $pv = round($commissionAmount * ($dividendPercent / 100) / $commissionPerPv, 2);

                $marketplaceProduct = $this->upsertMarketplaceProduct(
                    $row, $itemId, $account->id, $platformId, $affiliateUrl,
                    $commissionPercent, $commissionAmount, $feedItem, $verifiedAt
                );

                $this->upsertStorefrontProduct(
                    $row, $itemId, $store, $fallbackCategoryId, $affiliateUrl,
                    $commissionPercent, $pv, $verifiedAt, $marketplaceProduct
                );

                $published++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('lazada:scan-deals เขียนสินค้าไม่สำเร็จ', [
                    'item_id' => $itemId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $dry && $published > 0) {
            $store->update(['total_products' => Product::where('store_id', $store->id)->count()]);
            Cache::forget(StorefrontController::FLASH_DEALS_CACHE_KEY);
        }

        $expired = $this->expireStaleDeals($dry);

        $this->newLine();
        $this->info(sprintf('✅ ขึ้นหน้าแรก %d ชิ้น | ไม่อยู่ในโปรแกรม/ไม่มีลิงก์ %d | ค่าคอมต่ำกว่าเกณฑ์ %d | ล้มเหลว %d | ดีลเก่าหมดอายุ %d%s',
            $published, $noLink, $lowCommission, $failed, $expired, $dry ? ' (dry-run ไม่ได้เขียนฐาน)' : ''));

        return self::SUCCESS;
    }

    /**
     * ล้างป้ายลดราคาของดีลที่ "เลยอายุความสด" แล้ว
     *
     * 🚨 ทำไมต้องล้าง ไม่ใช่แค่ปล่อยให้ตกจากหน้าแรก
     *    ตัวคัดหน้าแรกกรองด้วย deal_verified_at อยู่แล้ว ของเก่าจึงไม่ขึ้นหน้าแรก
     *    แต่ `compare_at_price` ยังค้างอยู่บนสินค้า ⇒ **หน้ารายละเอียดสินค้า + การ์ดในหมวด**
     *    จะโชว์ราคาขีดฆ่าของโปรที่จบไปแล้วต่อไปเรื่อย ๆ = ปัญหาเดิมย้ายไปอีกหน้าหนึ่ง
     *
     * ขอบเขตการเขียนแคบมากโดยตั้งใจ: เฉพาะสินค้า affiliate ของ lazada
     * ที่ **เคยถูกตัวกวาดนี้ติดป้ายไว้เอง** (deal_verified_at ไม่ว่าง) เท่านั้น
     * ไม่แตะราคาขาย ไม่แตะสินค้าของผู้ขายรายอื่น ไม่ลบสินค้า
     *
     * @return int จำนวนแถวที่ถูกล้างป้าย
     */
    private function expireStaleDeals(bool $dry): int
    {
        $cutoff = now()->subHours(max(1, (int) config('lazada-deals.fresh_hours', 8)));

        $query = Product::where('is_affiliate', true)
            ->where('external_platform', 'lazada')
            ->whereNotNull('deal_verified_at')
            ->where('deal_verified_at', '<', $cutoff);

        if ($dry) {
            return $query->count();
        }

        $expired = $query->update([
            'compare_at_price' => null,
            'deal_discount_percent' => null,
            'deal_verified_at' => null,
        ]);

        if ($expired > 0) {
            Cache::forget(StorefrontController::FLASH_DEALS_CACHE_KEY);
        }

        return $expired;
    }

    /**
     * รายการคำค้นที่จะกวาด — `--keyword` ทับ config ได้ (ใช้ตอนทดลอง)
     *
     * @return array<int,array{keyword:string,category:?string}>
     */
    private function resolveSeeds(): array
    {
        $single = trim((string) $this->option('keyword'));
        if ($single !== '') {
            return [['keyword' => $single, 'category' => null]];
        }

        $seeds = [];
        foreach ((array) config('lazada-deals.seeds', []) as $seed) {
            $keyword = trim((string) ($seed['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }
            $seeds[] = ['keyword' => $keyword, 'category' => $seed['category'] ?? null];
        }

        return $seeds;
    }

    /**
     * เขียนแถวใน marketplace_products (ตารางกลางของสินค้าแพลตฟอร์มนอก)
     *
     * ⚠️ ถ้าแถวมีอยู่แล้ว (เคยนำเข้าจากฟีด/สายมู) จะอัพเดตเฉพาะฟิลด์ราคา/ลิงก์/ดีล
     *    **ห้ามแตะ** `source`, `mu_group`, `approval_status` — ของพวกนั้นเป็นสถานะที่คนตั้งไว้
     *    (ทับแล้วการ์ดสายมูในแชทจะเปลี่ยนสถานะเงียบ ๆ)
     *
     * @param  array<string,mixed>  $row
     * @param  array<string,mixed>  $feedItem
     */
    private function upsertMarketplaceProduct(
        array $row,
        string $itemId,
        int $accountId,
        ?int $platformId,
        string $affiliateUrl,
        float $commissionPercent,
        float $commissionAmount,
        array $feedItem,
        \Illuminate\Support\Carbon $verifiedAt
    ): MarketplaceProduct {
        $existing = MarketplaceProduct::where('platform_id', $platformId)
            ->where('external_product_id', $itemId)
            ->first();

        $shared = [
            'name' => $row['name'],
            'brand' => $row['brand'] ?: null,
            'brand_id' => $row['brand_id'] ?: null,
            'seller_id' => $row['seller_id'] ?: null,
            'seller_name' => $row['seller_name'] ?: null,
            'price' => $row['price'],
            'original_price' => $row['original_price'],
            'currency' => 'THB',
            'is_available' => true,
            'main_image_url' => $row['image'] ?: null,
            'affiliate_url' => $affiliateUrl,
            'affiliate_link_fetched_at' => $verifiedAt,
            'can_get_link' => true,
            'commission_rate' => $commissionPercent,
            'commission_amount' => $commissionAmount,
            'sales_count' => $row['sold_count'],
            'sales_7d' => (int) ($feedItem['sales7d'] ?? 0),
            'rating' => $row['rating'] ?: null,
            'review_count' => $row['review_count'],
            'deal_verified_at' => $verifiedAt,
            'deal_discount_percent' => $row['discount_percent'],
            'sync_status' => 'synced',
            'is_active' => true,
            'last_synced_at' => $verifiedAt,
        ];

        if ($existing) {
            $existing->update($shared);

            return $existing->refresh();
        }

        return MarketplaceProduct::create($shared + [
            'account_id' => $accountId,
            'platform_id' => $platformId,
            'fulfillment_mode' => 'affiliate',
            'external_product_id' => $itemId,
            'images' => $row['image'] ? [$row['image']] : [],
            'category_l1_id' => (string) ($feedItem['category_l1'] ?? '') ?: null,
            'source' => 'flash_deal',
            'source_url' => $row['url'],
            'offer_type' => 1,
        ]);
    }

    /**
     * เขียนสินค้าลงหน้าร้าน (ตาราง products) ให้ขึ้นแถบ Flash Deals
     *
     * @param  array<string,mixed>  $row
     */
    private function upsertStorefrontProduct(
        array $row,
        string $itemId,
        VendorStore $store,
        int $fallbackCategoryId,
        string $affiliateUrl,
        float $commissionPercent,
        float $pv,
        \Illuminate\Support\Carbon $verifiedAt,
        MarketplaceProduct $marketplaceProduct
    ): void {
        $categoryId = $this->resolveCategoryId($row['category_slug'] ?? null, $fallbackCategoryId);

        $payload = [
            'seller_id' => $store->user_id,
            'store_id' => $store->id,
            'category_id' => $categoryId,
            // ⚠️ products.name เป็น varchar(255) — ไม่ตัดแล้ว insert ไม่ผ่านและหายเงียบ
            'name' => mb_substr($row['name'], 0, 255),
            'brand' => $row['brand'] ?: null,
            'price' => $row['price'],
            'compare_at_price' => $row['original_price'],
            'deal_verified_at' => $verifiedAt,
            'deal_discount_percent' => $row['discount_percent'],
            'main_image_url' => $row['image'] ?: null,
            'image_urls' => $row['image'] ? [$row['image']] : [],
            'short_description' => $this->shortDescription($row),
            'sales_count' => $row['sold_count'],
            'rating_average' => $row['rating'] ?: 0,
            'rating_count' => $row['review_count'],
            'commission_rate' => $commissionPercent,
            'pv_value' => $pv,
            'stock_status' => 'in_stock',
            'track_inventory' => false,
            'stock_quantity' => 99,
            'is_affiliate' => true,
            'affiliate_url' => $affiliateUrl,
            'external_platform' => 'lazada',
            'external_product_id' => $itemId,
            'shipping_speed' => 'fast',
            'is_active' => true,
            'is_hidden' => false,
            'is_blocked' => false,
            'is_public_approved' => true,
            'public_approved_at' => now(),
            'published_at' => now(),
            'is_virtual' => false,
        ];

        // ⚠️ ต้องกรอง store_id ด้วย (เหมือน lazada:publish-storefront)
        //    ถ้าค้นด้วย external_product_id ลอย ๆ แล้ววันหนึ่งมีผู้ขายรายอื่นขายของ Lazada ชิ้นเดียวกัน
        //    การ update จะ "ย้ายร้าน" สินค้าของเขามาเป็นของร้าน Lazada Affiliate เงียบ ๆ
        $existing = Product::withTrashed()
            ->where('store_id', $store->id)
            ->where('external_platform', 'lazada')
            ->where('external_product_id', $itemId)
            ->first();

        if ($existing) {
            // เว้น slug/sku เดิมไว้ (ลิงก์ที่ลูกค้าเคยเปิดต้องไม่ตาย)
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update($payload);
            $product = $existing;
        } else {
            $payload['sku'] = 'LZDAFF-'.$itemId;
            $payload['slug'] = mb_substr(Str::slug($row['name']) ?: 'lazada', 0, 180).'-lzd-'.$itemId;
            $product = Product::create($payload);
        }

        // ผูกกลับให้ marketplace_products รู้ว่าไปโผล่เป็นสินค้าไหนบนหน้าร้าน
        if ((int) $marketplaceProduct->internal_product_id !== (int) $product->id) {
            $marketplaceProduct->update([
                'internal_product_id' => $product->id,
                'published_at' => now(),
            ]);
        }
    }

    /**
     * แปลง slug หมวดของเรา → id (ไม่พบ = ตกไปหมวด "สินค้า Lazada")
     */
    private function resolveCategoryId(?string $slug, int $fallbackId): int
    {
        if (! $slug) {
            return $fallbackId;
        }

        static $cache = [];
        if (! array_key_exists($slug, $cache)) {
            $cache[$slug] = ProductCategory::where('slug', $slug)->where('is_active', true)->value('id');
        }

        return (int) ($cache[$slug] ?: $fallbackId);
    }

    /**
     * คำโปรยสั้น — ประกอบจากข้อมูลที่ดึงมาจริงเท่านั้น ห้ามแต่งเพิ่ม
     *
     * @param  array<string,mixed>  $row
     */
    private function shortDescription(array $row): string
    {
        $parts = array_filter([
            $row['brand'] ? 'แบรนด์ '.$row['brand'] : null,
            $row['seller_name'] ? 'ร้าน '.$row['seller_name'] : null,
            $row['sold_count'] > 0 ? 'ขายแล้ว '.number_format($row['sold_count']).' ชิ้น' : null,
            'ลด '.$row['discount_percent'].'% บน Lazada',
        ]);

        return mb_substr($row['name'].' — '.implode(' · ', $parts), 0, 480);
    }

    /**
     * หา/สร้างร้าน "Lazada Affiliate" (ร้านเดียวกับที่ lazada:publish-storefront ใช้)
     */
    private function ensureLazadaStore(MarketplaceAccount $account): VendorStore
    {
        $store = VendorStore::where('store_slug', VendorStore::LAZADA_STORE_SLUG)->first();
        if ($store) {
            return $store;
        }

        return VendorStore::create([
            'user_id' => $account->user_id ?? User::query()->min('id'),
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
     * หา/สร้างหมวดสำรอง "สินค้า Lazada"
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
