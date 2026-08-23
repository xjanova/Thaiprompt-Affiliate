<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;

/**
 * 🔗 เติมลิงก์ค่าคอมให้สินค้าที่นำเข้ามาแล้วแต่ยังไม่มีลิงก์
 *
 * 🚨 ทำไมต้องมี — บทเรียน 2026-08-23
 *   นำเข้าสินค้าหมวดทั่วไปสำเร็จ 6,495 ชิ้น แต่รันด้วย `--no-link` เพื่อความเร็ว
 *   ⇒ ทุกชิ้นมี `affiliate_url` ว่าง ⇒ `scopeSendableInChat()` กรองออกหมด
 *   ⇒ **บอทส่งของใหม่ไม่ได้เลยสักชิ้น** ทั้งที่นำเข้าสำเร็จ ไม่มี error ให้เห็น
 *   ⇒ ลิงก์ไม่ใช่ของเสริม แต่เป็น **เงื่อนไขขั้นต่ำ** ที่ทำให้สินค้ามีค่า
 *     (ไม่มีลิงก์ = ส่งไปก็ไม่ได้เงิน = เท่ากับไม่มีของ)
 *
 * ⏱️ ลิงก์เป็นคอขวดเดียวของทั้งระบบ: `/marketing/product/link` = **1 ชิ้น/คอล ~1.2 วินาที**
 *   6,495 ชิ้น = ~2.5 ชั่วโมง ⇒ ต้องทยอย และต้อง **เรียงลำดับความสำคัญ**
 *   ค่าเริ่มต้นจึงเติมให้ของที่ "ส่งเข้าแชทได้จริง" ก่อน (ในช่วงราคา + ค่าคอมสูง)
 *   ของแพงที่ขึ้นเว็บอย่างเดียวรอทีหลังได้
 *
 * Usage:
 *   php artisan lazada:fill-links --limit=200
 *   php artisan lazada:fill-links --limit=500 --min-price=25 --max-price=990
 *   php artisan lazada:fill-links --all --limit=1000   (ไม่สนช่วงราคา)
 */
class LazadaFillLinks extends Command
{
    protected $signature = 'lazada:fill-links
                            {--account=2 : id ของ MarketplaceAccount}
                            {--limit=200 : เติมกี่ชิ้นต่อการรัน 1 ครั้ง}
                            {--min-price=25 : ราคาต่ำสุดที่สนใจ}
                            {--max-price=990 : ราคาสูงสุดที่สนใจ (การ์ดในแชทใช้ช่วงนี้)}
                            {--min-commission=0 : ค่าคอมขั้นต่ำ %%}
                            {--all : ไม่สนช่วงราคา เติมทุกชิ้นที่ยังไม่มีลิงก์}
                            {--max-seconds=0 : หยุดเมื่อใช้เวลาเกินกี่วินาที (0 = ไม่จำกัด)}';

    protected $description = '🔗 เติมลิงก์ค่าคอมให้สินค้าที่ยังไม่มี (เรียงของที่ส่งเข้าแชทได้ก่อน)';

    /** หน่วงระหว่างคอล — ลิงก์ใช้ ~1.2 วิอยู่แล้ว บวกอีกนิดกัน rate limit */
    private const SLEEP_US = 180000;

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี Lazada id='.$this->option('account'));

            return self::FAILURE;
        }

        $svc = new LazadaAffiliateService($account);
        $limit = max(1, (int) $this->option('limit'));
        $maxSeconds = max(0, (int) $this->option('max-seconds'));
        $startedAt = microtime(true);

        $rows = $this->targets($limit);

        if ($rows->isEmpty()) {
            $this->info('✅ ไม่มีสินค้าที่รอเติมลิงก์แล้ว');

            return self::SUCCESS;
        }

        $remaining = $this->remainingCount();
        $this->info("🔗 เติมลิงก์ {$rows->count()} ชิ้น (ค้างทั้งหมด {$remaining} ชิ้น) · ~1.4 วิ/ชิ้น");
        $this->newLine();

        $ok = 0;
        $fail = 0;
        $stopped = false;

        foreach ($rows as $i => $mp) {
            if ($maxSeconds > 0 && (microtime(true) - $startedAt) >= $maxSeconds) {
                $stopped = true;
                break;
            }

            try {
                $link = $svc->getProductLink((string) $mp->external_product_id);

                if ($link) {
                    $mp->update([
                        'affiliate_url' => $link,
                        'can_get_link' => true,
                        'affiliate_link_fetched_at' => now(),
                    ]);
                    $ok++;
                } else {
                    // ขอลิงก์ไม่ได้ = ของชิ้นนี้ไม่อยู่ในโปรแกรม affiliate แล้ว
                    // บันทึกไว้ไม่ให้วนมาขอซ้ำทุกรอบจนกินโควตาของชิ้นอื่น
                    $mp->update([
                        'can_get_link' => false,
                        'affiliate_link_fetched_at' => now(),
                    ]);
                    $fail++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $this->warn('   ⚠️ '.$mp->external_product_id.': '.$e->getMessage());
            }

            if (($i + 1) % 25 === 0) {
                $this->line(sprintf('   ...%d/%d (สำเร็จ %d · ไม่ได้ %d)', $i + 1, $rows->count(), $ok, $fail));
            }

            usleep(self::SLEEP_US);
        }

        $this->newLine();
        $this->info("✅ ได้ลิงก์ {$ok} ชิ้น · ขอไม่ได้ {$fail} ชิ้น".($stopped ? ' (หยุดเพราะครบเวลา)' : ''));

        $left = $this->remainingCount();
        if ($left > 0) {
            $this->line("   เหลือค้างอีก {$left} ชิ้น — รันซ้ำได้เลย");
        }

        $this->reportSendablePool();

        return self::SUCCESS;
    }

    /**
     * สินค้าที่รอเติมลิงก์ — เรียงของที่ "ส่งเข้าแชทได้จริง" ก่อน
     *
     * @return \Illuminate\Support\Collection<int,MarketplaceProduct>
     */
    private function targets(int $limit)
    {
        return $this->baseQuery()
            // ค่าคอมเป็นบาทสูงสุดก่อน — ของที่ทำเงินได้จริงควรมีลิงก์ก่อน
            ->orderByRaw('COALESCE(NULLIF(commission_amount, 0), price * commission_rate / 100) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * จำนวนที่ยังค้างทั้งหมดตามเงื่อนไขปัจจุบัน
     */
    private function remainingCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * เงื่อนไขร่วม — ของที่ยังไม่มีลิงก์ และยังไม่เคยถูกตอบว่าขอไม่ได้
     *
     * @return \Illuminate\Database\Eloquent\Builder<MarketplaceProduct>
     */
    private function baseQuery()
    {
        $q = MarketplaceProduct::query()
            ->offerable()
            ->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('affiliate_url')->orWhere('affiliate_url', ''))
            // เคยขอแล้วไม่ได้ = ข้ามไป ไม่วนมาเผาโควตาซ้ำ
            ->where(fn ($w) => $w->whereNull('can_get_link')->orWhere('can_get_link', true));

        if (! $this->option('all')) {
            $min = (float) $this->option('min-price');
            $max = (float) $this->option('max-price');
            if ($min > 0) {
                $q->where('price', '>=', $min);
            }
            if ($max > 0) {
                $q->where('price', '<=', $max);
            }
        }

        $minCom = (float) $this->option('min-commission');
        if ($minCom > 0) {
            $q->where('commission_rate', '>=', $minCom);
        }

        return $q;
    }

    /**
     * รายงานพูลที่บอทใช้ได้จริงหลังเติมลิงก์
     *
     * สำคัญกว่าจำนวนลิงก์ที่ได้ — ลิงก์เพิ่มแต่พูลไม่ขยับ = มีอย่างอื่นกรองอยู่
     */
    private function reportSendablePool(): void
    {
        $mu = MarketplaceProduct::query()->mu()->offerable()->sendableInChat()->count();
        $all = MarketplaceProduct::query()->offerable()->sendableInChat()->count();
        $inRange = MarketplaceProduct::query()->offerable()->sendableInChat()
            ->whereBetween('price', [25, 990])->count();

        $this->newLine();
        $this->info('📊 พูลที่บอทส่งได้จริงตอนนี้');
        $this->line("   ของสายมู          {$mu} ชิ้น");
        $this->line("   ทั้งคลัง           {$all} ชิ้น");
        $this->line("   ในช่วงราคา 25-990  {$inRange} ชิ้น");
    }
}
