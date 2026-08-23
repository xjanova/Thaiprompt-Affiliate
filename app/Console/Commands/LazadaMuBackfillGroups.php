<?php

namespace App\Console\Commands;

use App\Models\LazadaMuKeyword;
use App\Models\MarketplaceProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * ติดป้าย `mu_group` ให้สินค้าสายมูที่นำเข้าไว้แล้ว
 *
 * ทำไมต้องมีคำสั่งนี้:
 *   คอลัมน์ `mu_group` เพิ่งถูกสร้าง ⇒ สินค้าเดิมทุกชิ้นเป็น NULL
 *   ⇒ ตัวเลือกของของบอท (`scopeMu()`) จะคืนศูนย์ตลอด บอทเปิดสวิตช์แล้วก็เงียบ
 *
 * 🚨 แหล่งความจริงคือไฟล์ `database/data/lazada-mu-products.json` (105 id ที่คนคัดมาเอง)
 *    **ไม่ใช่** คอลัมน์ `source = 'mu_curated'` — ตรวจพร็อด 2026-08-22 พบว่าป้ายนั้นมี 900 แถว
 *    ส่วนเกิน 795 แถวเป็นแฟ้ม A4 / สายชาร์จ / เวเฟอร์ / เจลลี่ลดน้ำหนัก
 *    ถ้าใช้ source ติดป้าย ลูกค้าถามของแก้ปีชงแล้วแม่หมอจะส่งสายชาร์จไปให้
 *
 * Usage:
 *   php artisan lazada:mu-backfill-groups
 *   php artisan lazada:mu-backfill-groups --dry
 */
class LazadaMuBackfillGroups extends Command
{
    protected $signature = 'lazada:mu-backfill-groups
                            {--dry : แสดงผลอย่างเดียว ไม่เขียนจริง}
                            {--file= : ไฟล์รายการสินค้า (ไม่ระบุ = database/data/lazada-mu-products.json)}';

    protected $description = '🔮 ติดป้าย mu_group ให้สินค้าสายมูที่นำเข้าไว้แล้ว (จากไฟล์รายการคัดสรร)';

    public function handle(): int
    {
        if (! Schema::hasColumn('marketplace_products', 'mu_group')) {
            $this->error('❌ ยังไม่มีคอลัมน์ mu_group — รัน php artisan migrate ก่อน');

            return self::FAILURE;
        }

        $path = (string) ($this->option('file') ?: database_path('data/lazada-mu-products.json'));
        if (! is_file($path)) {
            $this->error("❌ ไม่พบไฟล์รายการสินค้า: {$path}");

            return self::FAILURE;
        }

        $json = json_decode((string) file_get_contents($path), true);
        $products = $json['products'] ?? null;

        if (! is_array($products) || empty($products)) {
            $this->error('❌ ไฟล์รายการสินค้าไม่มีคีย์ products หรือว่างเปล่า');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');
        $this->info('🔮 ติดป้ายกลุ่มสายมูจาก '.count($products).' รายการในไฟล์'.($dry ? ' (dry-run)' : ''));

        // จับคู่ group → คีย์เวิร์ดตัวแทน เพื่อผูก mu_keyword_id ไว้ตามรอยที่มา
        $keywordByGroup = LazadaMuKeyword::query()
            ->whereNotNull('mu_group')
            ->get()
            ->groupBy('mu_group')
            ->map(fn ($rows) => (int) $rows->first()->id)
            ->all();

        $tagged = 0;
        $missing = 0;
        $byGroup = [];

        foreach ($products as $row) {
            $externalId = trim((string) ($row['id'] ?? ''));
            $group = trim((string) ($row['group'] ?? ''));

            if ($externalId === '' || $group === '') {
                continue;
            }

            $product = MarketplaceProduct::where('external_product_id', $externalId)->first();
            if (! $product) {
                $missing++;

                continue;
            }

            $byGroup[$group] = ($byGroup[$group] ?? 0) + 1;
            $tagged++;

            if ($dry) {
                continue;
            }

            $product->update([
                'mu_group' => $group,
                'mu_keyword_id' => $keywordByGroup[$group] ?? null,
                'approval_status' => MarketplaceProduct::APPROVAL_APPROVED,
                // ของชุดนี้ผ่านสายตาคนมาแล้วตอนนำเข้ารอบ 2026-07-26 (dry-run ตีกลับ 13 ชิ้นที่ไม่ใช่สายมู)
                'mu_verified_at' => $product->mu_verified_at ?? now(),
            ]);
        }

        $this->newLine();
        $this->info("✅ ติดป้ายแล้ว {$tagged} ชิ้น".($missing > 0 ? " · ไม่พบในฐาน {$missing} ชิ้น" : ''));

        ksort($byGroup);
        foreach ($byGroup as $group => $count) {
            $this->line(sprintf('   %-10s %d ชิ้น', $group, $count));
        }

        $this->reportSendablePool();

        return self::SUCCESS;
    }

    /**
     * รายงานว่าหลังติดป้ายแล้ว บอทมีของให้ส่งจริงกี่ชิ้น
     *
     * สำคัญกว่าจำนวนที่ติดป้าย — ของที่ไม่มีลิงก์ค่าคอมหรือไม่มีรูป ส่งเข้าแชทไม่ได้
     */
    private function reportSendablePool(): void
    {
        $minCommission = (float) \App\Models\MarketplaceSetting::get('lazada_mu_min_commission', 9);

        $total = MarketplaceProduct::query()->mu()->count();
        $sendable = MarketplaceProduct::query()->mu()->offerable()->sendableInChat()->count();
        $passing = MarketplaceProduct::query()->mu()->offerable()->sendableInChat()
            ->where('commission_rate', '>=', $minCommission)->count();

        $this->newLine();
        $this->info('📊 พูลที่บอทใช้ได้จริง');
        $this->line("   ติดป้ายสายมู           {$total} ชิ้น");
        $this->line("   ส่งเข้าแชทได้ (ลิงก์+รูป) {$sendable} ชิ้น");
        $this->line("   ผ่านค่าคอม ≥{$minCommission}%        {$passing} ชิ้น");

        if ($passing < 2) {
            $this->warn('⚠️  ของที่ผ่านเกณฑ์น้อยกว่า 2 ชิ้น — บอทจะเสนอได้ชิ้นเดียวหรือเงียบ');
            $this->warn('    ทางแก้: ลดค่าคอมขั้นต่ำที่ตั้งค่า หรือนำเข้าของเพิ่ม');
        }
    }
}
