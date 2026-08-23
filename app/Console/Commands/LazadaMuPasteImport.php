<?php

namespace App\Console\Commands;

use App\Models\LazadaMuKeyword;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;

/**
 * 📥 นำเข้าสินค้าจาก "รายการที่คนก็อปมาวาง" — ทางเข้าที่ใช้ได้จริงโดยไม่ต้องมีคุกกี้พอร์ทัล
 *
 * 🚨 ทำไมต้องมีคำสั่งนี้ (พิสูจน์บนพร็อด 2026-08-23 แล้ว 4 ทาง)
 *   API ทางการ (`/marketing/product/feed`) **ให้ของถูกไม่ได้เลย**:
 *     - สแกนฟีดรวม 1,180 ชิ้น → ผ่านเกณฑ์ (≥9% · 25-990฿ · มีสต็อก · มีรูป) = **4 ชิ้น**
 *     - ราคากลางของฟีด = **192,857฿** · ถูกสุด 10% แรก = 40,000฿ · แพงสุด 1.1 พันล้าน
 *     - ตัวฆ่าคือ **ราคา ไม่ใช่ค่าคอม** — ผ่านค่าคอม ≥9% ถึง 18% แต่ผ่านช่วงราคาแค่ 1%
 *     - กรองราย `categoryL1` ก็ไม่ช่วย: beauty/pets/books ไม่มีของต่ำกว่า 990฿ **แม้แต่ชิ้นเดียว**
 *   ⇒ ของถูกที่ขายได้จริงในแชท (25-1,859฿) หาจากฟีดทางการไม่ได้ ต้องมาจากพอร์ทัล affiliate
 *
 *   พอร์ทัลค้นด้วยคำได้ แต่ auth เป็น session cookie ⇒ ต้องให้เจ้าของเอาคุกกี้มาให้
 *   **คำสั่งนี้คือทางที่เลี่ยงคุกกี้ทั้งหมด** — เจ้าของก็อป "อะไรก็ได้ที่มีเลขสินค้า" มาวาง
 *   คลิกเท่ากัน แต่ไม่ต้องเก็บ credential ระดับบัญชี ไม่มีวันหมดอายุ ไม่เสี่ยงถูกระงับบัญชี
 *
 * รับได้ทุกแบบ (ไม่ต้องจัดรูปแบบให้):
 *   - เลขสินค้าเปล่าๆ คั่นด้วยอะไรก็ได้      1234567890, 987654321
 *   - ลิงก์ Lazada                          https://www.lazada.co.th/products/xxx-i1234567890-s99.html
 *   - JSON ที่ก็อปจาก searchSkuOffer.json    {"data":{"reportItem":[{"itemId":123,...}]}}
 *
 * Usage:
 *   php artisan lazada:mu-paste --group=pichong --file=/tmp/ids.txt
 *   php artisan lazada:mu-paste --group=charm --ids="123,456,789" --dry
 */
class LazadaMuPasteImport extends Command
{
    protected $signature = 'lazada:mu-paste
                            {--account=2 : id ของ MarketplaceAccount (Lazada Affiliate)}
                            {--group= : กลุ่มสายมู pixiu|pichong|zodiac|pyramid|charm (ว่าง = ไม่ใช่ของสายมู)}
                            {--ids= : เลขสินค้า/ลิงก์ คั่นด้วยจุลภาค หรือขึ้นบรรทัดใหม่}
                            {--file= : อ่านจากไฟล์แทน (รองรับ JSON ที่ก็อปจากพอร์ทัล)}
                            {--min-commission=0 : ค่าคอมขั้นต่ำ %% (0 = รับหมด)}
                            {--max-price=0 : ราคาสูงสุด (0 = ไม่จำกัด)}
                            {--no-link : ไม่ต้องดึงลิงก์ค่าคอม (เร็วขึ้นมาก ค่อยเติมทีหลัง)}
                            {--dry : แสดงผลอย่างเดียว ไม่เขียนฐาน}';

    protected $description = '📥 นำเข้าสินค้า Lazada จากเลข/ลิงก์/JSON ที่ก็อปมาวาง (ไม่ต้องใช้คุกกี้พอร์ทัล)';

    /** ยิง feed ได้ทีละกี่ id (วัดจริง: 100 id ต่อ 1 คอล ใช้ 6.1 วินาที) */
    private const ENRICH_CHUNK = 100;

    /** หน่วงระหว่างขอลิงก์แต่ละชิ้น (คอขวด 1.2 วิ/ชิ้นอยู่แล้ว) */
    private const LINK_SLEEP_US = 180000;

    public function handle(): int
    {
        $raw = $this->readInput();
        if ($raw === null) {
            return self::FAILURE;
        }

        $ids = $this->extractIds($raw);
        if (empty($ids)) {
            $this->error('❌ ไม่พบเลขสินค้าในข้อมูลที่วางมา');
            $this->line('   รองรับ: เลขเปล่าๆ · ลิงก์ Lazada (…-i1234567890-s…) · JSON จากพอร์ทัล');

            return self::FAILURE;
        }

        $this->info('📥 พบเลขสินค้า '.count($ids).' รายการ (ตัดซ้ำแล้ว)');

        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี Lazada id='.$this->option('account'));

            return self::FAILURE;
        }

        $svc = new LazadaAffiliateService($account);

        // ── ดึงข้อมูลจริงจาก API ทางการ (ยิงด้วย id อะไรก็ได้ ไม่จำกัดเฉพาะของในฟีด) ──
        $items = $this->enrich($svc, $ids);
        $this->info('🔍 ดึงข้อมูลจริงได้ '.count($items).' ชิ้น'.(count($items) < count($ids) ? ' · ไม่มีข้อมูล '.(count($ids) - count($items)).' ชิ้น (ถูกถอด/ไม่อยู่ในโปรแกรม)' : ''));

        if (empty($items)) {
            return self::FAILURE;
        }

        $result = $this->importAll($svc, $account, $items);

        $this->newLine();
        $this->info(sprintf(
            '✅ นำเข้า %d ชิ้น (ใหม่ %d · อัปเดต %d) · ตีกลับ %d · ได้ลิงก์ %d',
            $result['ok'], $result['created'], $result['updated'], $result['rejected'], $result['linked']
        ));

        if (! empty($result['rejectReasons'])) {
            $this->newLine();
            $this->warn('รายการที่ตีกลับ:');
            foreach ($result['rejectReasons'] as $reason => $count) {
                $this->line("   {$reason} — {$count} ชิ้น");
            }
        }

        return self::SUCCESS;
    }

    /**
     * อ่านข้อมูลดิบจาก --file หรือ --ids
     */
    private function readInput(): ?string
    {
        $file = (string) $this->option('file');
        if ($file !== '') {
            if (! is_file($file)) {
                $this->error("❌ ไม่พบไฟล์: {$file}");

                return null;
            }

            return (string) file_get_contents($file);
        }

        $ids = (string) $this->option('ids');
        if (trim($ids) === '') {
            $this->error('❌ ต้องระบุ --ids หรือ --file');

            return null;
        }

        return $ids;
    }

    /**
     * แกะเลขสินค้าออกจากข้อความดิบ — รับได้ทั้งเลขเปล่า ลิงก์ และ JSON
     *
     * ⚠️ ลำดับสำคัญ: แกะจากลิงก์ (`-i123-s456`) **ก่อน** แกะเลขลอย
     *    ไม่งั้นเลข skuId (`-s456`) กับเลขในพาธจะปนเข้ามาเป็นสินค้าปลอม
     *
     * @return array<int,string>
     */
    private function extractIds(string $raw): array
    {
        $ids = [];

        // 1) ลิงก์ Lazada — รูปแบบ …-i{itemId}-s{skuId}.html
        if (preg_match_all('/-i(\d{6,})(?:-s\d+)?/i', $raw, $m)) {
            $ids = array_merge($ids, $m[1]);
            // ตัดส่วนที่แกะไปแล้วออก กันเลขในลิงก์ถูกนับซ้ำเป็นเลขลอย
            $raw = preg_replace('/https?:\/\/\S+/i', ' ', $raw) ?? $raw;
        }

        // 2) JSON จากพอร์ทัล — คีย์ itemId / productId
        if (preg_match_all('/"(?:itemId|productId)"\s*:\s*"?(\d{6,})"?/i', $raw, $m)) {
            $ids = array_merge($ids, $m[1]);
        }

        // 3) เลขลอยๆ (คั่นด้วยอะไรก็ได้) — เอาเฉพาะที่ยาวพอจะเป็นเลขสินค้า
        if (preg_match_all('/(?<!\d)(\d{6,})(?!\d)/', $raw, $m)) {
            $ids = array_merge($ids, $m[1]);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * ดึงข้อมูลจริงของแต่ละ id จาก API ทางการ
     *
     * @param  array<int,string>  $ids
     * @return array<string,array<string,mixed>> [productId => item]
     */
    private function enrich(LazadaAffiliateService $svc, array $ids): array
    {
        $out = [];
        $chunks = array_chunk($ids, self::ENRICH_CHUNK);

        foreach ($chunks as $i => $chunk) {
            $r = $svc->getProductFeed(1, 1, self::ENRICH_CHUNK, null, $chunk);

            if (! $r['ok']) {
                $this->warn('   ⚠️ ก้อนที่ '.($i + 1).' ล้มเหลว: '.($r['error'] ?? '-'));

                continue;
            }

            foreach ($r['items'] as $it) {
                $out[(string) $it['product_id']] = $it;
            }
        }

        return $out;
    }

    /**
     * คัดกรอง + เขียนลงฐาน + ดึงลิงก์ค่าคอม
     *
     * @param  array<string,array<string,mixed>>  $items
     * @return array<string,mixed>
     */
    private function importAll(LazadaAffiliateService $svc, MarketplaceAccount $account, array $items): array
    {
        $dry = (bool) $this->option('dry');
        $withLink = ! $this->option('no-link');
        $group = trim((string) $this->option('group')) ?: null;
        $minCom = (float) $this->option('min-commission');
        $maxPrice = (float) $this->option('max-price');

        $platformId = MarketplacePlatform::where('slug', 'lazada')->value('id')
            ?? MarketplacePlatform::where('name', 'like', '%lazada%')->value('id');

        $keywordId = $group
            ? LazadaMuKeyword::where('mu_group', $group)->value('id')
            : null;

        $r = ['ok' => 0, 'created' => 0, 'updated' => 0, 'rejected' => 0, 'linked' => 0, 'rejectReasons' => []];

        foreach ($items as $pid => $it) {
            $price = (float) $it['price'];
            $com = round((float) $it['commission_rate'] * 100, 2);

            // ── ด่านคัดกรอง — บันทึกเหตุผลไว้ให้ตรวจย้อนหลังได้ ──
            $reject = match (true) {
                empty($it['image']) => 'ไม่มีรูป (ส่งเข้าแชทไม่ได้)',
                $it['out_of_stock'] => 'ของหมดสต็อก',
                $minCom > 0 && $com < $minCom => "ค่าคอมต่ำกว่า {$minCom}%",
                $maxPrice > 0 && $price > $maxPrice => "ราคาเกิน {$maxPrice}฿",
                $price <= 0 => 'ไม่มีราคา',
                default => null,
            };

            if ($reject !== null) {
                $r['rejected']++;
                $r['rejectReasons'][$reject] = ($r['rejectReasons'][$reject] ?? 0) + 1;
                $this->line(sprintf('   ⛔ %6sB %3s%% | %-40s → %s', round($price), round($com), mb_substr($it['name'], 0, 40), $reject));

                continue;
            }

            $this->line(sprintf('   ✅ %6sB %3s%% | %s', round($price), round($com), mb_substr($it['name'], 0, 46)));
            $r['ok']++;

            if ($dry) {
                continue;
            }

            $existing = MarketplaceProduct::where('platform_id', $platformId)
                ->where('external_product_id', (string) $pid)
                ->first();

            $payload = [
                'account_id' => $account->id,
                'platform_id' => $platformId,
                'fulfillment_mode' => 'affiliate',
                'external_product_id' => (string) $pid,
                'name' => mb_substr((string) $it['name'], 0, 500),
                'brand' => $it['brand'] ?: null,
                'seller_id' => $it['seller_id'] ?: null,
                'seller_name' => $it['seller'] ?: null,
                'category_l1_id' => $it['category_l1'] ?: null,
                'price' => $price,
                'currency' => $it['currency'] ?: 'THB',
                // ⚠️ stock_quantity เป็น int(11) — Lazada เคยส่งค่าเกิน 2.1 พันล้าน
                //    ไม่ clamp = SQLSTATE 22003 แล้วสินค้าชิ้นนั้นหายเงียบ
                'stock_quantity' => max(0, min((int) $it['stock'], 2147483647)),
                'is_available' => ! $it['out_of_stock'],
                'main_image_url' => $it['image'],
                'images' => $it['images'],
                'commission_rate' => $com,
                'commission_amount' => $it['commission_amount'],
                'sales_7d' => $it['sales7d'],
                'offer_type' => 1,
                // เก็บ raw เดิมไว้ด้วย — ฟีดแต่ละรอบส่งฟิลด์ไม่เท่ากัน
                'attributes' => $existing
                    ? array_merge((array) $existing->attributes, (array) $it['raw'])
                    : $it['raw'],
                'source' => 'mu_paste',
                'sync_status' => 'synced',
                'is_active' => true,
                'last_synced_at' => now(),
                // 🚨 ต้องเขียน mu_group ไม่งั้น scopeMu() มองไม่เห็น = บอทไม่มีวันเสนอ
                'mu_group' => $group,
                'mu_keyword_id' => $keywordId,
                // เสนอในแชทได้ทันที (offerable) แต่ยังไม่ขึ้นหน้าร้านจนกว่าคนจะอนุมัติ
                'approval_status' => MarketplaceProduct::APPROVAL_PENDING,
            ];

            if ($existing) {
                $existing->update($payload);
                $mp = $existing;
                $r['updated']++;
            } else {
                $mp = MarketplaceProduct::create($payload);
                $r['created']++;
            }

            // ── ลิงก์ค่าคอม (คอขวด 1.2 วิ/ชิ้น) ──
            if ($withLink && empty($mp->affiliate_url)) {
                try {
                    $link = $svc->getProductLink((string) $pid);
                    if ($link) {
                        $mp->update([
                            'affiliate_url' => $link,
                            'can_get_link' => true,
                            'affiliate_link_fetched_at' => now(),
                        ]);
                        $r['linked']++;
                    }
                } catch (\Throwable $e) {
                    $this->warn('      ⚠️ ดึงลิงก์ไม่ได้: '.$e->getMessage());
                }
                usleep(self::LINK_SLEEP_US);
            }
        }

        return $r;
    }
}
