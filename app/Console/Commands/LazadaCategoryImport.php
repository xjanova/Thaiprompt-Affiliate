<?php

namespace App\Console\Commands;

use App\Models\LazadaCategoryMap;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Models\ProductCategory;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;

/**
 * 🗂️ นำเข้าสินค้าหมวดทั่วไปจากฟีดทางการ — หมวดละ N ชิ้น อนุมัติขึ้นเว็บอัตโนมัติ
 *
 * 🚦 กฎที่เจ้าของวางไว้ (2026-08-23):
 *   - **หมวดทั่วไป** → นำเข้าหมวดละ 200 ชิ้น · **อนุมัติขึ้นเว็บอัตโนมัติ**
 *   - **สายมู** → ใช้ `lazada:mu-paste` แทน · ค้างเป็น pending · ใช้ส่งทางแชทเป็นหลัก
 *     เจ้าของอนุมัติขึ้นเว็บเอง
 *
 * ⚠️ ทำไมคำสั่งนี้ **ไม่ตั้งเพดานราคา** เป็นค่าเริ่มต้น (ต่างจากของสายมู)
 *   วัดฟีดจริงบนพร็อด 2026-08-23: ราคากลาง = 192,857฿ · ถูกสุด 10% แรก = 40,000฿
 *   หมวด beauty/pets/books **ไม่มีของต่ำกว่า 990฿ แม้แต่ชิ้นเดียว**
 *   ⇒ ถ้าตั้งเพดานราคาแบบสายมู จะนำเข้าได้ 0 ชิ้นทุกหมวด
 *   ของพวกนี้มีไว้ขึ้น **หน้าร้าน** ซึ่งไม่มีข้อจำกัดเรื่องราคาเหมือนการ์ดในแชท
 *   (การ์ดในแชทกรองราคาเองอยู่แล้วผ่าน `lazada_mu_max_price`)
 *
 * Usage:
 *   php artisan lazada:category-import --dry
 *   php artisan lazada:category-import --per-category=200
 *   php artisan lazada:category-import --category=3838 --per-category=50 --no-link
 */
class LazadaCategoryImport extends Command
{
    protected $signature = 'lazada:category-import
                            {--account=2 : id ของ MarketplaceAccount}
                            {--per-category=200 : นำเข้าหมวดละกี่ชิ้น}
                            {--category= : ทำเฉพาะเลขหมวด Lazada นี้ (ว่าง = ทุกหมวดในแผนที่)}
                            {--min-commission=5 : ค่าคอมขั้นต่ำ %%}
                            {--max-price=0 : เพดานราคา (0 = ไม่จำกัด — ดูเหตุผลใน docblock)}
                            {--no-link : ข้ามการดึงลิงก์ค่าคอม (เร็วขึ้นมาก ค่อยเติมทีหลัง)}
                            {--link-budget=40 : ดึงลิงก์สูงสุดกี่ชิ้นต่อการรัน 1 ครั้ง}
                            {--dry : แสดงผลอย่างเดียว ไม่เขียนฐาน}';

    protected $description = '🗂️ นำเข้าสินค้าหมวดทั่วไปจากฟีด Lazada หมวดละ N ชิ้น (อนุมัติขึ้นเว็บอัตโนมัติ)';

    /** ฟีดคืนได้สูงสุดกี่ชิ้นต่อ 1 คอล (วัดจริง: 100 ชิ้น ใช้ ~6 วินาที) */
    private const PAGE_SIZE = 100;

    /** กันวนไม่จบถ้าฟีดคืนของซ้ำ */
    private const MAX_PAGES_PER_CATEGORY = 12;

    /** หน่วงระหว่างขอลิงก์ (คอขวด ~1.2 วิ/ชิ้น) */
    private const LINK_SLEEP_US = 180000;

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี Lazada id='.$this->option('account'));

            return self::FAILURE;
        }

        $categories = $this->targetCategories();
        if (empty($categories)) {
            $this->error('❌ ไม่มีหมวดให้ทำ — รัน db:seed --class=LazadaCategoryMapSeeder ก่อน');

            return self::FAILURE;
        }

        $svc = new LazadaAffiliateService($account);
        $dry = (bool) $this->option('dry');
        $perCat = max(1, (int) $this->option('per-category'));

        $this->info('🗂️ นำเข้าหมวดทั่วไป '.count($categories).' หมวด · หมวดละไม่เกิน '.$perCat.' ชิ้น'.($dry ? ' (dry-run)' : ''));
        $this->line('   สถานะอนุมัติ = approved (ขึ้นเว็บอัตโนมัติ) · ของสายมูใช้ lazada:mu-paste แทน');
        $this->newLine();

        $platformId = MarketplacePlatform::where('slug', 'lazada')->value('id')
            ?? MarketplacePlatform::where('name', 'like', '%lazada%')->value('id');

        $linkBudget = $this->option('no-link') ? 0 : max(0, (int) $this->option('link-budget'));
        $totals = ['accepted' => 0, 'created' => 0, 'updated' => 0, 'rejected' => 0, 'linked' => 0];

        foreach ($categories as $lazadaCat => $ourCategoryId) {
            $r = $this->importCategory($svc, $account, $platformId, (string) $lazadaCat, $ourCategoryId, $perCat, $linkBudget, $dry);

            $linkBudget -= $r['linked'];
            foreach (['accepted', 'created', 'updated', 'rejected', 'linked'] as $k) {
                $totals[$k] += $r[$k];
            }

            $this->line($dry
                ? sprintf('  หมวด %-10s จะนำเข้า %3d · ตีกลับ %3d', $lazadaCat, $r['accepted'], $r['rejected'])
                : sprintf('  หมวด %-10s ใหม่ %3d · อัปเดต %3d · ตีกลับ %3d · ลิงก์ %3d',
                    $lazadaCat, $r['created'], $r['updated'], $r['rejected'], $r['linked']));
        }

        $this->newLine();
        $this->info($dry
            ? sprintf('✅ dry-run: จะนำเข้า %d ชิ้น · ตีกลับ %d ชิ้น (ยังไม่เขียนฐาน)', $totals['accepted'], $totals['rejected'])
            : sprintf('✅ รวม: ใหม่ %d · อัปเดต %d · ตีกลับ %d · ได้ลิงก์ %d',
                $totals['created'], $totals['updated'], $totals['rejected'], $totals['linked']));

        if ($linkBudget <= 0 && ! $this->option('no-link')) {
            $this->warn('⚠️ ใช้โควตาลิงก์หมดแล้ว — ของที่เหลือยังไม่มีลิงก์ค่าคอม');
            $this->line('   รันซ้ำอีกรอบเพื่อเติมลิงก์ให้ครบ (ของเดิมจะถูกข้าม ไม่ยิงซ้ำ)');
        }

        return self::SUCCESS;
    }

    /**
     * หมวดที่จะทำ — เลขหมวด Lazada => id หมวดบนเว็บเรา
     *
     * @return array<string,int>
     */
    private function targetCategories(): array
    {
        $one = trim((string) $this->option('category'));

        if ($one !== '') {
            $mapped = LazadaCategoryMap::resolve($one);

            return $mapped ? [$one => $mapped] : [];
        }

        // ⛔ ไม่เอาหมวดสายมู — ของสายมูมาทาง lazada:mu-paste เท่านั้น
        $muCategoryIds = ProductCategory::where('slug', 'like', 'sai-mu%')->pluck('id')->all();

        return LazadaCategoryMap::query()
            ->when(! empty($muCategoryIds), fn ($q) => $q->whereNotIn('product_category_id', $muCategoryIds))
            ->pluck('product_category_id', 'lazada_category_l1')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * นำเข้า 1 หมวด
     *
     * @return array<string,int>
     */
    private function importCategory(
        LazadaAffiliateService $svc,
        MarketplaceAccount $account,
        ?int $platformId,
        string $lazadaCat,
        int $ourCategoryId,
        int $perCat,
        int $linkBudget,
        bool $dry
    ): array {
        $minCom = (float) $this->option('min-commission');
        $maxPrice = (float) $this->option('max-price');

        $r = ['accepted' => 0, 'created' => 0, 'updated' => 0, 'rejected' => 0, 'linked' => 0];
        $accepted = 0;
        $seenIds = [];

        for ($page = 1; $page <= self::MAX_PAGES_PER_CATEGORY && $accepted < $perCat; $page++) {
            $feed = $svc->getProductFeed(1, $page, self::PAGE_SIZE, (int) $lazadaCat);

            if (! $feed['ok']) {
                $this->warn("   ⚠️ หมวด {$lazadaCat} หน้า {$page} ล้มเหลว: ".($feed['error'] ?? '-'));
                break;
            }
            if (empty($feed['items'])) {
                break;
            }

            $fresh = 0;
            foreach ($feed['items'] as $it) {
                $pid = (string) $it['product_id'];

                // ฟีดคืนของซ้ำข้ามหน้าได้ — กันนับซ้ำและกันวนไม่จบ
                if (isset($seenIds[$pid])) {
                    continue;
                }
                $seenIds[$pid] = true;
                $fresh++;

                if ($accepted >= $perCat) {
                    break;
                }

                $price = (float) $it['price'];
                $com = round((float) $it['commission_rate'] * 100, 2);

                if (empty($it['image']) || $it['out_of_stock'] || $price <= 0
                    || $com < $minCom
                    || ($maxPrice > 0 && $price > $maxPrice)) {
                    $r['rejected']++;

                    continue;
                }

                $accepted++;
                $r['accepted']++;

                if ($dry) {
                    continue;
                }

                [$mp, $isNew] = $this->upsert($account, $platformId, $ourCategoryId, $pid, $it, $price, $com);
                $isNew ? $r['created']++ : $r['updated']++;

                // เติมลิงก์เท่าที่งบยังเหลือ — ลิงก์เป็นคอขวด 1.2 วิ/ชิ้น
                if ($r['linked'] < $linkBudget && empty($mp->affiliate_url)) {
                    if ($this->fetchLink($svc, $mp, $pid)) {
                        $r['linked']++;
                    }
                }
            }

            // ทั้งหน้าเป็นของซ้ำ = ฟีดหมดจริง ไม่ต้องยิงต่อ
            if ($fresh === 0) {
                break;
            }
        }

        return $r;
    }

    /**
     * เขียน/อัปเดตสินค้า 1 ชิ้น
     *
     * @param  array<string,mixed>  $it
     * @return array{0:MarketplaceProduct,1:bool} [โมเดล, เป็นของใหม่ไหม]
     */
    private function upsert(
        MarketplaceAccount $account,
        ?int $platformId,
        int $ourCategoryId,
        string $pid,
        array $it,
        float $price,
        float $com
    ): array {
        $existing = MarketplaceProduct::where('platform_id', $platformId)
            ->where('external_product_id', $pid)
            ->first();

        $payload = [
            'account_id' => $account->id,
            'platform_id' => $platformId,
            'fulfillment_mode' => 'affiliate',
            'external_product_id' => $pid,
            'name' => mb_substr((string) $it['name'], 0, 500),
            'brand' => $it['brand'] ?: null,
            'seller_id' => $it['seller_id'] ?: null,
            'seller_name' => $it['seller'] ?: null,
            'category_l1_id' => $it['category_l1'] ?: null,
            'price' => $price,
            'currency' => $it['currency'] ?: 'THB',
            // ⚠️ int(11) — Lazada เคยส่งค่าเกิน 2.1 พันล้าน ไม่ clamp = SQLSTATE 22003 สินค้าหายเงียบ
            'stock_quantity' => max(0, min((int) $it['stock'], 2147483647)),
            'is_available' => ! $it['out_of_stock'],
            'main_image_url' => $it['image'],
            'images' => $it['images'],
            'commission_rate' => $com,
            'commission_amount' => $it['commission_amount'],
            'sales_7d' => $it['sales7d'],
            'offer_type' => 1,
            'attributes' => $existing
                ? array_merge((array) $existing->attributes, (array) $it['raw'])
                : $it['raw'],
            'source' => 'category_feed',
            'sync_status' => 'synced',
            'is_active' => true,
            'last_synced_at' => now(),
            // 🚦 หมวดทั่วไป = อนุมัติขึ้นเว็บอัตโนมัติ (เจ้าของสั่ง)
            //    mu_group เว้นว่างไว้ ⇒ scopeMu() ไม่หยิบไปเป็นของสายมู
            'approval_status' => MarketplaceProduct::APPROVAL_APPROVED,
        ];

        if ($existing) {
            $existing->update($payload);

            return [$existing, false];
        }

        return [MarketplaceProduct::create($payload), true];
    }

    /**
     * ดึงลิงก์ค่าคอม 1 ชิ้น
     */
    private function fetchLink(LazadaAffiliateService $svc, MarketplaceProduct $mp, string $pid): bool
    {
        try {
            $link = $svc->getProductLink($pid);
            usleep(self::LINK_SLEEP_US);

            if (! $link) {
                return false;
            }

            $mp->update([
                'affiliate_url' => $link,
                'can_get_link' => true,
                'affiliate_link_fetched_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->warn('      ⚠️ ดึงลิงก์ไม่ได้ ('.$pid.'): '.$e->getMessage());

            return false;
        }
    }
}
