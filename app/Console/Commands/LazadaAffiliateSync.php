<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ดึงสินค้า Lazada Affiliate feed เข้าแคตตาล็อก (marketplace_products) — ครบทุกหมวด หมวดละ N
 *
 * แนวทาง (feed ไม่มี keyword + ไม่มีรายชื่อหมวดจาก API):
 *  1) กวาด /marketing/product/feed หลายหน้า (offerType=1) — ผลลัพธ์คละหมวดอยู่แล้ว
 *  2) จัดกลุ่มตาม categoryL1 → เก็บหมวดละ --per-category (เรียงตามยอดขาย 7 วัน)
 *  3) ข้ามสินค้าที่ "ไม่ได้ค่าคอม" (commissionRate<=0) ตาม owner สั่ง ("ไม่ได้ค่า affiliate ปิดไปก่อน")
 *  4) ดึงลิงก์ค่าคอมต่อชิ้น (/marketing/product/link) → affiliate_url
 *  5) upsert เข้า marketplace_products (fulfillment_mode=affiliate) + เก็บ raw ทั้งก้อนใน attributes
 *
 *   php artisan lazada:affiliate-sync 2 --per-category=10 --pages=40
 *   php artisan lazada:affiliate-sync 2 --dry            # ดูจำนวน/หมวด ไม่บันทึก ไม่ยิงลิงก์
 *   php artisan lazada:affiliate-sync 2 --no-link        # เร็ว (ไม่ดึงลิงก์ค่าคอม) เทสก่อน
 */
class LazadaAffiliateSync extends Command
{
    protected $signature = 'lazada:affiliate-sync
        {account : ID บัญชี affiliate_native}
        {--per-category=10 : จำนวนสินค้าต่อหมวด}
        {--categories= : ระบุ categoryL1 เอง คั่นด้วย comma (ไม่ระบุ = ค้นหาหมวดจาก featured feed)}
        {--cat-pages=4 : จำนวนหน้าสูงสุดที่ยิงต่อหมวด (ตอนเก็บให้ครบ per-category)}
        {--max-price= : ราคาสูงสุด (บาท) — ข้ามสินค้าแพงกว่านี้ (คัดของใช้ทั่วไป)}
        {--limit=50 : สินค้าต่อหน้า (1-100)}
        {--offer=1 : offerType 1=Regular 2=MM 3=DM}
        {--no-link : ไม่ดึงลิงก์ค่าคอม (เร็วขึ้น)}
        {--dry : ทดสอบ ไม่บันทึก ไม่ยิงลิงก์}';

    protected $description = 'ดึงสินค้า Lazada Affiliate feed ครบทุกหมวด (หมวดละ N) + ลิงก์ค่าคอม เข้าแคตตาล็อก (ข้ามที่ไม่ได้ค่าคอม)';

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->argument('account'));
        if (! $account) {
            $this->error('ไม่พบบัญชี marketplace_accounts id='.$this->argument('account'));

            return self::FAILURE;
        }

        $perCat = max(1, (int) $this->option('per-category'));
        $catPages = max(1, (int) $this->option('cat-pages'));
        $limit = max(1, min(100, (int) $this->option('limit')));
        $offer = (int) $this->option('offer');
        $dry = (bool) $this->option('dry');
        $withLink = ! $this->option('no-link') && ! $dry;
        $catsOpt = trim((string) $this->option('categories'));
        $maxPrice = $this->option('max-price') !== null && $this->option('max-price') !== ''
            ? (float) $this->option('max-price') : 0.0;

        $svc = new LazadaAffiliateService($account);
        $platformId = MarketplacePlatform::firstOrCreate(['slug' => 'lazada'], ['name' => 'Lazada', 'is_active' => true])->id;

        // ── 1) หาว่าจะดึงหมวดไหนบ้าง ──
        //    feed แบบไม่ใส่หมวด = ชุด "แนะนำ" ~98 ชิ้น (หน้าเดียว) → ใช้ค้นหารายชื่อหมวด
        //    feed แบบใส่ categoryL1 = แบ่งหน้าลึกจริง → ใช้เก็บหมวดละ N
        $categories = [];
        if ($catsOpt !== '') {
            $categories = array_values(array_filter(array_map('trim', explode(',', $catsOpt))));
            $this->info('📂 ใช้หมวดที่ระบุ: '.implode(', ', $categories));
        } else {
            $this->info("🔎 ค้นหาหมวดจาก featured feed (offerType={$offer})...");
            $seen = [];
            foreach ([1, 2] as $fp) { // 2 หน้าเผื่อ featured หมุน
                $res = $svc->getProductFeed(offerType: $offer, page: $fp, limit: 100);
                if (! $res['ok']) {
                    if ($fp === 1) {
                        $this->error('ดึง featured feed ไม่ได้: '.$res['error']);

                        return self::FAILURE;
                    }
                    break;
                }
                foreach ($res['items'] as $it) {
                    if (($it['category_l1'] ?? '') !== '' && ($it['commission_rate'] ?? 0) > 0) {
                        $seen[$it['category_l1']] = true;
                    }
                }
                if (! $res['hasMore']) {
                    break;
                }
                usleep(150000);
            }
            $categories = array_keys($seen);
            $this->info('  พบ '.count($categories).' หมวด: '.implode(', ', $categories));
        }

        if (empty($categories)) {
            $this->error('ไม่พบหมวด — ระบุเองด้วย --categories=id1,id2 ได้');

            return self::FAILURE;
        }

        // ── 2) เก็บสินค้าหมวดละ perCat (ยิง feed ต่อหมวด แบ่งหน้าลึก) ──
        $byCat = [];
        $noComm = 0;
        foreach ($categories as $cat) {
            $picked = [];
            for ($p = 1; $p <= $catPages && count($picked) < $perCat; $p++) {
                $res = $svc->getProductFeed(offerType: $offer, page: $p, limit: max($perCat, $limit), categoryL1: (int) $cat);
                if (! $res['ok'] || empty($res['items'])) {
                    break;
                }
                // เรียงยอดขาย 7 วันในหน้านั้นก่อนหยิบ
                $items = $res['items'];
                usort($items, fn ($a, $b) => ($b['sales7d'] <=> $a['sales7d']) ?: ($b['commission_amount'] <=> $a['commission_amount']));
                foreach ($items as $it) {
                    if (count($picked) >= $perCat) {
                        break;
                    }
                    if (($it['commission_rate'] ?? 0) <= 0) {
                        $noComm++;

                        continue;
                    }
                    // คัดของใช้ทั่วไป: ข้ามราคาเกินเพดาน (ถ้ากำหนด) + ข้ามราคา 0
                    if ($maxPrice > 0 && (float) $it['price'] > $maxPrice) {
                        continue;
                    }
                    if ((float) $it['price'] <= 0) {
                        continue;
                    }
                    $picked[$it['product_id']] = $it;
                }
                if (! $res['hasMore']) {
                    break;
                }
                usleep(150000);
            }
            if (! empty($picked)) {
                $byCat[$cat] = array_values($picked);
            }
            $this->line('  หมวด '.$cat.': '.count($picked).' ชิ้น');
        }
        ksort($byCat);

        $this->info('📦 เก็บได้ '.count($byCat).' หมวด | ข้ามไม่มีค่าคอม: '.$noComm.' | นำเข้าสูงสุด '.array_sum(array_map('count', $byCat)).' ชิ้น');

        // ── 3) นำเข้า (ดึงลิงก์ค่าคอมต่อชิ้น) ──
        $imported = 0;
        $updated = 0;
        $linked = 0;
        $failed = 0;

        foreach ($byCat as $cat => $list) {
            foreach ($list as $it) {
                if ($dry) {
                    continue;
                }
                try {
                    $affUrl = $withLink ? $svc->getProductLink($it['product_id']) : null;
                    if ($affUrl) {
                        $linked++;
                    }
                    if ($withLink) {
                        usleep(180000);
                    }

                    $payload = [
                        'account_id' => $account->id,
                        'platform_id' => $platformId,
                        'fulfillment_mode' => 'affiliate',
                        'external_product_id' => $it['product_id'],
                        'name' => $it['name'],
                        'brand' => $it['brand'] ?: null,
                        'brand_id' => $it['brand_id'] ?: null,
                        'seller_id' => $it['seller_id'] ?: null,
                        'seller_name' => $it['seller'] ?: null,
                        'category' => $it['category_l1'] ?: null,
                        'category_l1_id' => $it['category_l1'] ?: null,
                        'price' => $it['price'],
                        'currency' => $it['currency'] ?: 'THB',
                        'stock_quantity' => $it['stock'],
                        'is_available' => ! $it['out_of_stock'],
                        'main_image_url' => $it['image'] ?: null,
                        'images' => $it['images'],
                        'commission_rate' => round(($it['commission_rate'] ?? 0) * 100, 2), // เศษส่วน→เปอร์เซ็นต์
                        'commission_amount' => $it['commission_amount'],
                        'hyper_commission_rate' => $it['hyper_commission_rate'],
                        'cps_commission_rate' => $it['cps_commission_rate'],
                        'cps_commission_amount' => $it['cps_commission_amount'],
                        'bonus_offer_flag' => $it['bonus_offer_flag'],
                        'sales_7d' => $it['sales7d'],
                        'offer_type' => $offer,
                        'attributes' => $it['raw'],
                        'source' => 'affiliate_feed',
                        'sync_status' => 'synced',
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ];
                    if ($affUrl) {
                        $payload['affiliate_url'] = $affUrl;
                        $payload['can_get_link'] = true;
                        $payload['affiliate_link_fetched_at'] = now();
                    }

                    $existing = MarketplaceProduct::where('platform_id', $platformId)
                        ->where('external_product_id', $it['product_id'])
                        ->first();

                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        MarketplaceProduct::create($payload);
                        $imported++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('lazada:affiliate-sync นำเข้าล้มเหลว', ['product_id' => $it['product_id'], 'error' => $e->getMessage()]);
                }
            }
            $this->line("  หมวด {$cat}: ".count($list).' ชิ้น');
        }

        $this->newLine();
        $this->info(sprintf(
            '✅ เสร็จ — สร้าง %d, อัพเดท %d, ได้ลิงก์ค่าคอม %d, พลาด %d %s',
            $imported,
            $updated,
            $linked,
            $failed,
            $dry ? '(DRY-RUN: ไม่บันทึก)' : ''
        ));

        return self::SUCCESS;
    }
}
