<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Models\MlmGlobalSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use Database\Seeders\MuCategorySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * นำเข้าสินค้า "สายมู" ที่คัดสรรไว้ จาก Lazada Affiliate → แคตตาล็อก + หน้าร้าน
 *
 * ทำไมต้องมีคำสั่งนี้แยกจาก lazada:affiliate-sync
 * ─────────────────────────────────────────────────
 * Open API ของ Lazada Affiliate **ไม่มีการค้นด้วยคำ (keyword)** — `/marketing/product/feed`
 * กรองได้แค่ categoryL1 และฟีดที่ได้เอียงไปทางสินค้าราคาสูงมาก (ของสายมูในฟีด = 30,000-750,000฿)
 * → หา "ปี่เซี้ยะ 39 บาท" จากฟีดไม่ได้เลย
 *
 * วิธีที่ใช้จริง (hybrid):
 *   1) คัดรายการด้วยการค้นคำในพอร์ทัล affiliate (searchSkuOffer) → ได้แค่ "รหัสสินค้า"
 *      เก็บเป็นลิสต์ถาวรที่ database/data/lazada-mu-products.json
 *   2) คำสั่งนี้เอารหัสไปดึง "ข้อมูลจริง" จาก Open API ทางการทุกครั้ง
 *      - `/marketing/product/feed?productIds=[...]` → ชื่อ/ราคา/รูป/สต็อก/ค่าคอม (ข้อมูลสด ไม่ค้าง)
 *      - `/marketing/product/link?productId=` → ลิงก์ติดตามที่ได้ค่าคอมจริง (s.lazada.co.th)
 *   3) upsert เข้า marketplace_products แล้วเผยแพร่เข้า products ใต้หมวดสายมูที่ถูกต้อง
 *
 * PV = (ค่าคอมยืนยัน × ปันผล%) ÷ commission_per_pv — สูตรเดียวกับ lazada:publish-storefront
 *
 *   php artisan lazada:mu-import 2 --dry        # ดูว่าจะได้อะไรบ้าง ไม่บันทึก
 *   php artisan lazada:mu-import 2              # นำเข้า + โชว์หน้าร้านเลย
 *   php artisan lazada:mu-import 2 --hidden     # นำเข้าแบบซ่อนไว้ก่อน (stage)
 *   php artisan lazada:mu-import 2 --only=pixiu # เฉพาะบางหมวด
 */
class LazadaMuImport extends Command
{
    protected $signature = 'lazada:mu-import
        {account : ID บัญชี affiliate_native (prod = 2)}
        {--file= : ไฟล์ลิสต์สินค้า (ไม่ระบุ = database/data/lazada-mu-products.json)}
        {--only= : เฉพาะกลุ่ม คั่นด้วย comma (pixiu,pyramid,pichong,zodiac,charm)}
        {--max-price= : ข้ามสินค้าที่แพงกว่านี้ (บาท)}
        {--hidden : นำเข้าแบบซ่อนไว้ก่อน (ไม่ระบุ = โชว์หน้าร้านทันที)}
        {--no-link : ไม่ดึงลิงก์ค่าคอม (เร็วขึ้น ใช้ตอนเทส)}
        {--dividend= : เปอร์เซ็นต์ปันผลเข้ากอง PV (ไม่ระบุ = ค่ากลาง MLM หรือ 50)}
        {--dry : ทดสอบ ไม่บันทึกอะไรเลย}';

    protected $description = 'นำเข้าสินค้าสายมูที่คัดไว้จาก Lazada Affiliate เข้าหมวดสายมู + หน้าร้าน (ดึงข้อมูลจริงจาก Open API)';

    /** ดึงข้อมูลทีละกี่รหัสต่อการยิง feed 1 ครั้ง (กัน URL ยาวเกิน) */
    private const ENRICH_BATCH = 40;

    /**
     * คำที่บ่งชี้ว่า "ไม่ใช่ของสายมู" — กันของอุปโภคบริโภคหลุดเข้าหมวดสายมู
     *
     * ⚠️ บทเรียนจริง (2026-07-26): ตอนคัดรายการด้วยคำค้น "ถุงเงินถุงทอง" มีสินค้าที่ชื่อ
     *    มีคำว่า "ถุงทอง" แต่เป็นคนละเรื่องหลุดมาเพียบ — ทรีตเมนต์ผม LPP, ครีมนวด Shiseido,
     *    ปุ๋ยเกล็ด, เมล็ดฟักทอง — รวมถึงแม่พิมพ์เค้กรูป 12 นักษัตร, ขนตาปลอม, ปากกาเมจิก
     *    → ห้ามเชื่อคำค้นอย่างเดียว ต้องกรองชื่อจริงจาก API อีกชั้นก่อนขึ้นร้าน
     *
     * @var array<int,string>
     */
    private const NON_MU_KEYWORDS = [
        'treatment', 'conditioner', 'shampoo', 'hair', 'เส้นผม', 'ปุ๋ย', 'fertilizer', 'humic',
        'เมล็ดพันธุ์', 'เมล็ดฟักทอง', 'marker', 'ปากกา', 'สมุนไพร', 'ลดระดับน้ำตาล', 'อาหารเสริม',
        'วิตามิน', 'serum', 'เซรั่ม', 'ครีม', 'ผงซักฟอก', 'refill', 'นมผง', 'ขนม', 'กาแฟ', 'coffee',
        'โลชั่น', 'สบู่', 'soap', 'tsubaki', 'artline', 'lpp', 'เสื้อ', 'shirt', 'กางเกง', 'รองเท้า',
        'หมวก', 'ถุงเท้า', 'แม่พิมพ์', 'mold', 'powder', 'snack', 'drink', 'เครื่องดื่ม', 'ขนตา',
        'eyelash', 'cake', 'silicone', 'ถุงสังฆทาน',
    ];

    /**
     * ชื่อสินค้านี้ "ไม่ใช่ของสายมู" ใช่ไหม (เทียบแบบไม่สนตัวพิมพ์)
     */
    private function isNotMuProduct(string $name): bool
    {
        foreach (self::NON_MU_KEYWORDS as $kw) {
            if (mb_stripos($name, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->argument('account'));
        if (! $account) {
            $this->error('ไม่พบบัญชี marketplace_accounts id='.$this->argument('account'));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');
        $visible = ! $this->option('hidden');
        $withLink = ! $this->option('no-link') && ! $dry;
        $maxPrice = $this->option('max-price') !== null && $this->option('max-price') !== ''
            ? (float) $this->option('max-price') : 0.0;

        // ── 1) อ่านลิสต์สินค้าที่คัดไว้ ──
        $path = (string) ($this->option('file') ?: database_path('data/lazada-mu-products.json'));
        if (! is_file($path)) {
            $this->error("ไม่พบไฟล์ลิสต์สินค้า: {$path}");

            return self::FAILURE;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['products'])) {
            $this->error("ไฟล์ลิสต์สินค้าอ่านไม่ได้ หรือไม่มีคีย์ products: {$path}");

            return self::FAILURE;
        }

        $onlyOpt = trim((string) $this->option('only'));
        $only = $onlyOpt !== '' ? array_map('trim', explode(',', $onlyOpt)) : [];

        /** @var array<string,string> รหัสสินค้า => กลุ่ม */
        $wanted = [];
        foreach ($json['products'] as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }

            // รองรับ 2 รูปแบบ:
            //   1) {"id": "...", "group": "pixiu"}      — ชุดสายมูเดิม (map ผ่าน MuCategorySeeder)
            //   2) {"id": "...", "category": "slug"}    — หมวดทั่วไป ระบุ slug ของหมวดตรงๆ
            $group = (string) ($row['group'] ?? '');
            $categorySlug = (string) ($row['category'] ?? '');

            if ($group !== '' && isset(MuCategorySeeder::GROUPS[$group])) {
                $key = $group;
                $slug = MuCategorySeeder::GROUPS[$group][1];
                $isMu = true;
            } elseif ($categorySlug !== '') {
                $key = $categorySlug;
                $slug = $categorySlug;
                $isMu = false;
            } else {
                continue; // ไม่ระบุหมวด = ไม่รู้จะเอาไปไว้ไหน ข้าม
            }

            if ($only && ! in_array($key, $only, true)) {
                continue;
            }

            $wanted[$id] = ['key' => $key, 'slug' => $slug, 'is_mu' => $isMu];
        }

        if (empty($wanted)) {
            $this->error('ไม่มีสินค้าให้นำเข้า (ลิสต์ว่าง หรือ --only ไม่ตรงกลุ่มไหนเลย)');

            return self::FAILURE;
        }

        $this->info('📋 รายการที่จะนำเข้า: '.count($wanted).' ชิ้น'.($only ? ' (เฉพาะ '.implode(',', $only).')' : ''));

        // ── 2) ดึงข้อมูลจริงจาก Open API ทางการ (แบ่งชุด) ──
        $svc = new \App\Services\Marketplace\LazadaAffiliateService($account);
        $items = [];
        foreach (array_chunk(array_keys($wanted), self::ENRICH_BATCH) as $i => $chunk) {
            $res = $svc->getProductFeed(offerType: 1, page: 1, limit: 100, productIds: $chunk);
            if (! $res['ok']) {
                $this->error('  ดึงข้อมูลชุดที่ '.($i + 1).' ไม่สำเร็จ: '.$res['error']);

                continue;
            }
            foreach ($res['items'] as $it) {
                $items[(string) $it['product_id']] = $it;
            }
            $this->line('  ชุดที่ '.($i + 1).': ขอ '.count($chunk).' ได้ '.count($res['items']));
            usleep(200000);
        }

        $missing = array_diff(array_keys($wanted), array_keys($items));
        if ($missing) {
            $this->warn('⚠️  ดึงข้อมูลไม่ได้ '.count($missing).' ชิ้น (สินค้าถูกปิด/ออกจากโปรแกรม affiliate แล้ว): '.implode(',', array_slice($missing, 0, 12)).(count($missing) > 12 ? ' ...' : ''));
        }

        if (empty($items)) {
            $this->error('ไม่ได้ข้อมูลสินค้าเลย — ตรวจ User Token ของบัญชี affiliate');

            return self::FAILURE;
        }

        // ── 3) เตรียมร้าน + หมวดสายมู ──
        $commissionPerPv = max(0.01, (float) MlmGlobalSetting::get('commission_per_pv', 1));
        $dividendPercent = $this->option('dividend') !== null
            ? (float) $this->option('dividend')
            : (float) MlmGlobalSetting::get('lazada_affiliate_dividend_percent', 50);

        $store = $this->ensureLazadaStore($account);
        $categoryIds = $dry ? [] : $this->ensureCategories($wanted);
        $platformId = MarketplacePlatform::firstOrCreate(['slug' => 'lazada'], ['name' => 'Lazada', 'is_active' => true])->id;

        $this->info("🏪 ร้าน: {$store->store_name} (id={$store->id}) | PV = ค่าคอม × {$dividendPercent}% ÷ {$commissionPerPv}");
        $this->info($visible ? '👁️  โหมด: โชว์หน้าร้านทันที' : '🙈 โหมด: ซ่อนไว้ก่อน (stage)');

        // ── 4) นำเข้าทีละชิ้น ──
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $linked = 0;
        $byGroup = [];
        /** @var array<int,string> รายการที่ถูกตีกลับเพราะไม่ใช่ของสายมู (รายงานท้ายสุด) */
        $rejected = [];

        foreach ($items as $pid => $it) {
            $meta = $wanted[$pid] ?? ['key' => 'charm', 'slug' => 'sai-mu-charm', 'is_mu' => true];
            $group = $meta['key'];

            // ข้ามของที่ "ไม่ใช่สายมู" (ชื่อไปพ้องกับสินค้าอุปโภคบริโภค) — กันขึ้นร้านผิดหมวด
            // ⚠️ บล็อกลิสต์นี้ใช้กับ "หมวดสายมู" เท่านั้น
            //    คำอย่าง ครีม/ปุ๋ย/กาแฟ/เสื้อ เป็นของที่ "ถูกต้อง" สำหรับหมวด
            //    ความงาม/บ้านสวน/อาหาร/แฟชั่น — ถ้าเอาไปกรองด้วยจะตัดสินค้าดีๆทิ้งหมด
            if ($meta['is_mu'] && $this->isNotMuProduct($it['name'])) {
                $skipped++;
                $rejected[] = $pid.' | '.mb_substr($it['name'], 0, 46);

                continue;
            }

            // ข้ามของหมด/ราคาผิดปกติ/เกินเพดาน
            if ((float) $it['price'] <= 0 || ! empty($it['out_of_stock'])) {
                $skipped++;

                continue;
            }
            if ($maxPrice > 0 && (float) $it['price'] > $maxPrice) {
                $skipped++;

                continue;
            }

            if ($dry) {
                $byGroup[$group] = ($byGroup[$group] ?? 0) + 1;
                $this->line(sprintf('  [%s] %sB คอม %.0f%% | %s', $group, round((float) $it['price']), ($it['commission_rate'] ?? 0) * 100, mb_substr($it['name'], 0, 52)));

                continue;
            }

            // หมวดปลายทางหาไม่เจอ (slug ในไฟล์ผิด/หมวดถูกลบ) → ข้าม ไม่ล้มทั้งรอบ
            if (! isset($categoryIds[$group])) {
                $skipped++;
                $this->warn("  ⚠️  {$pid}: ไม่พบหมวด '{$meta['slug']}' — ข้าม");

                continue;
            }

            try {
                $affUrl = $withLink ? $svc->getProductLink($pid) : null;
                if ($affUrl) {
                    $linked++;
                }
                if ($withLink) {
                    usleep(180000);
                }

                // 4.1 แคตตาล็อกกลาง (marketplace_products)
                $mpPayload = [
                    'account_id' => $account->id,
                    'platform_id' => $platformId,
                    'fulfillment_mode' => 'affiliate',
                    'external_product_id' => $pid,
                    'name' => $it['name'],
                    'brand' => $it['brand'] ?: null,
                    'brand_id' => $it['brand_id'] ?: null,
                    'seller_id' => $it['seller_id'] ?: null,
                    'seller_name' => $it['seller'] ?: null,
                    'category' => $group,
                    'category_l1_id' => $it['category_l1'] ?: null,
                    'price' => $it['price'],
                    'currency' => $it['currency'] ?: 'THB',
                    // ⚠️ คอลัมน์เป็น int(11) (สูงสุด ~2.1 พันล้าน) แต่ Lazada เคยส่งค่ามากกว่านั้นมา
                    //    ทำให้ตกด้วย SQLSTATE[22003] Out of range แล้วสินค้าชิ้นนั้นหายไปเงียบๆ
                    //    สต็อกของร้านนอกเป็นแค่ข้อมูลอ้างอิง ไม่ได้ใช้ตัดสต็อกจริง → clamp ได้ปลอดภัย
                    'stock_quantity' => max(0, min((int) $it['stock'], 2147483647)),
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
                    'offer_type' => 1,
                    'attributes' => $it['raw'],
                    'source' => 'mu_curated',
                    'sync_status' => 'synced',
                    'is_active' => true,
                    'last_synced_at' => now(),

                    // 🚨 (2026-08-23) ต้องเขียน mu_group ที่นี่ ไม่งั้นของที่นำเข้าใหม่ "มองไม่เห็น"
                    //   บอทเลือกของผ่าน `MarketplaceProduct::scopeMu()` ซึ่งกรอง `mu_group IS NOT NULL`
                    //   ก่อนหน้านี้ payload นี้เขียนแค่ `category` (= ชื่อกลุ่ม) แต่ไม่เขียน `mu_group`
                    //   ⇒ ของใหม่ทุกชิ้นได้ mu_group = NULL ⇒ **นำเข้ามาเท่าไหร่บอทก็ไม่เสนอสักชิ้น**
                    //   ⇒ ท่อนำเข้าอัตโนมัติทั้งท่อจะไร้ความหมาย และไม่มี error ให้เห็นเลย
                    //   (ของ 105 ชิ้นแรกใช้ได้เพราะถูกติดป้ายย้อนหลังด้วย lazada:mu-backfill-groups)
                    //
                    //   ⚠️ ห้ามใช้คอลัมน์ `source='mu_curated'` แทน — พร็อดมี 900 แถวป้ายนั้น
                    //     แต่เป็นของสายมูจริงแค่ 105 (ที่เหลือเป็นแฟ้ม A4 / สายชาร์จ / เวเฟอร์)
                    'mu_group' => $group,

                    // รายการนี้มาจากไฟล์ที่คนคัดมาเอง (dry-run ตีกลับของไม่ใช่สายมู 13 ชิ้นตอนนำเข้ารอบแรก)
                    // ⇒ ถือว่าผ่านสายตาคนแล้ว ไม่ต้องเข้าคิวอนุมัติซ้ำ
                    'approval_status' => MarketplaceProduct::APPROVAL_APPROVED,
                ];

                // เก็บ raw ของเดิมไว้ด้วย — ฟีดแต่ละรอบส่งฟิลด์ไม่เท่ากัน
                // เขียนทับทั้งก้อนจะทำให้ข้อมูลที่เคยได้ (rating/reviews/soldCount) หายทุกครั้งที่ sync
                if ($mpExisting = MarketplaceProduct::where('platform_id', $platformId)->where('external_product_id', $pid)->first()) {
                    $mpPayload['attributes'] = array_merge((array) $mpExisting->attributes, (array) $it['raw']);
                }
                if ($affUrl) {
                    $mpPayload['affiliate_url'] = $affUrl;
                    $mpPayload['can_get_link'] = true;
                    $mpPayload['affiliate_link_fetched_at'] = now();
                }

                $mp = MarketplaceProduct::where('platform_id', $platformId)
                    ->where('external_product_id', $pid)
                    ->first();
                if ($mp) {
                    $mp->update($mpPayload);
                } else {
                    $mp = MarketplaceProduct::create($mpPayload);
                }

                // 4.2 หน้าร้าน (products) — ต้องมีลิงก์ค่าคอมเท่านั้น ไม่งั้นกดแล้วเราไม่ได้ค่าคอม
                $linkForStore = $affUrl ?: ($mp->affiliate_url ?: null);
                if (! $linkForStore) {
                    $skipped++;
                    $this->warn("  ⚠️  {$pid}: ไม่ได้ลิงก์ค่าคอม — ไม่เผยแพร่หน้าร้าน");

                    continue;
                }

                $pv = round(((float) ($it['commission_amount'] ?? 0)) * ($dividendPercent / 100) / $commissionPerPv, 2);

                $payload = [
                    'seller_id' => $store->user_id,
                    'store_id' => $store->id,
                    'category_id' => $categoryIds[$group],
                    // ⚠️ ตัดที่ 255 — สองตารางนี้ความยาวไม่เท่ากัน
                    //    marketplace_products.name = varchar(500) (normalizeFeedItem ตัดที่ 500)
                    //    products.name             = varchar(255)
                    //    ชื่อสินค้า Lazada ยาวเกิน 255 บ่อยมาก (ยัดคีย์เวิร์ดท้ายชื่อ)
                    //    ถ้าไม่ตัด จะตกด้วย SQLSTATE[22001] Data too long แล้วสินค้านั้นไม่ขึ้นร้าน
                    'name' => mb_substr((string) $it['name'], 0, 255),
                    'sku' => 'LZDMU-'.$pid,
                    'brand' => $it['brand'] ?: null,
                    'price' => (float) $it['price'],
                    'main_image_url' => $it['image'] ?: null,
                    'image_urls' => is_array($it['images']) ? $it['images'] : [],
                    'commission_rate' => round(($it['commission_rate'] ?? 0) * 100, 2),
                    'pv_value' => $pv,
                    'stock_status' => 'in_stock',
                    'track_inventory' => false,
                    'stock_quantity' => 99,
                    'is_affiliate' => true,
                    'affiliate_url' => $linkForStore,
                    'external_platform' => 'lazada',
                    'external_product_id' => $pid,
                    'shipping_speed' => 'fast', // Lazada ไทย = ส่งไว
                    'is_active' => true,
                    'is_hidden' => ! $visible,
                    'is_public_approved' => true,
                    'public_approved_at' => now(),
                    'published_at' => now(),
                    'is_virtual' => false,
                ];

                $existing = Product::where('store_id', $store->id)
                    ->where('external_platform', 'lazada')
                    ->where('external_product_id', $pid)
                    ->first();

                if ($existing) {
                    // เว้น slug เดิมไว้ (กันลิงก์หน้าสินค้าเปลี่ยน)
                    $existing->update($payload);
                    $updated++;
                } else {
                    $payload['slug'] = mb_substr(Str::slug($it['name']) ?: 'lazada-mu', 0, 180).'-lzd-'.$pid;
                    Product::create($payload);
                    $created++;
                }

                $byGroup[$group] = ($byGroup[$group] ?? 0) + 1;
            } catch (\Throwable $e) {
                $skipped++;
                Log::warning('lazada:mu-import นำเข้าล้มเหลว', ['pid' => $pid, 'error' => $e->getMessage()]);
                $this->warn("  ⚠️  {$pid}: ".mb_substr($e->getMessage(), 0, 120));
            }
        }

        if (! $dry) {
            $store->update(['total_products' => Product::where('store_id', $store->id)->count()]);
        }

        // ── สรุป ──
        $this->newLine();
        foreach (MuCategorySeeder::GROUPS as $g => [$name]) {
            if (isset($byGroup[$g])) {
                $this->line(sprintf('  %-28s %d ชิ้น', $name, $byGroup[$g]));
            }
        }
        if ($rejected) {
            $this->newLine();
            $this->warn('🚫 ตีกลับ '.count($rejected).' ชิ้น (ชื่อบ่งชี้ว่าไม่ใช่ของสายมู — ลบออกจากไฟล์ลิสต์ได้เลย):');
            foreach ($rejected as $r) {
                $this->line('   '.$r);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '✅ เสร็จ — สร้าง %d, อัพเดท %d, ข้าม %d, ได้ลิงก์ค่าคอม %d %s',
            $created,
            $updated,
            $skipped,
            $linked,
            $dry ? '(DRY-RUN: ไม่บันทึก)' : '| รวมในร้าน '.$store->total_products.' ชิ้น'
        ));

        return self::SUCCESS;
    }

    /**
     * หา/สร้างร้าน "Lazada Affiliate" (ร้านเดียวกับ lazada:publish-storefront)
     */
    private function ensureLazadaStore(MarketplaceAccount $account): VendorStore
    {
        $store = VendorStore::where('store_slug', VendorStore::LAZADA_STORE_SLUG)->first();
        if ($store) {
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
     * เตรียมหมวดสายมูให้ครบ (รัน seeder ถ้ายังไม่มี) แล้วคืน map กลุ่ม => category_id
     *
     * @return array<string,int>
     */
    /**
     * เตรียมหมวดปลายทางให้ครบ แล้วคืน map key => category_id
     *
     * @param  array<string,array{key:string,slug:string,is_mu:bool}>  $wanted
     * @return array<string,int>
     */
    private function ensureCategories(array $wanted): array
    {
        // ถ้าลิสต์มีของสายมู → ให้แน่ใจว่าหมวดสายมูถูกสร้างแล้ว
        $needsMu = collect($wanted)->contains(fn ($w) => $w['is_mu']);
        if ($needsMu) {
            $slugs = array_map(fn ($g) => $g[1], MuCategorySeeder::GROUPS);
            if (ProductCategory::whereIn('slug', $slugs)->count() < count($slugs)) {
                $this->warn('🔮 ยังไม่มีหมวดสายมูครบ — กำลังสร้างให้อัตโนมัติ...');
                (new MuCategorySeeder)->setCommand($this)->run();
            }
        }

        $map = [];
        $missing = [];
        foreach ($wanted as $w) {
            if (isset($map[$w['key']])) {
                continue;
            }
            $cat = ProductCategory::where('slug', $w['slug'])->first();
            if (! $cat) {
                // ⚠️ ไม่สร้างหมวดใหม่เองโดยพลการ — หมวดเป็นโครงสร้างที่แอดมินดูแล
                //    ถ้า slug ผิด ให้ฟ้องแล้วข้าม ดีกว่าไปสร้างหมวดขยะทิ้งไว้
                $missing[] = $w['slug'];

                continue;
            }
            $map[$w['key']] = $cat->id;
        }

        if ($missing) {
            $this->error('❌ ไม่พบหมวดเหล่านี้ในระบบ (ข้ามสินค้าของหมวดนั้น): '.implode(', ', array_unique($missing)));
        }

        return $map;
    }
}
