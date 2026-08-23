<?php

namespace App\Console\Commands;

use App\Models\LazadaMuKeyword;
use App\Models\MarketplaceAccount;
use App\Services\Marketplace\LazadaPortalSearch;
use Illuminate\Console\Command;

/**
 * 🔮 ไล่เก็บของสายมูจากพอร์ทัล affiliate ตามคีย์เวิร์ดใน `lazada_mu_keywords`
 *
 * ทำไมต้องผ่านพอร์ทัล (พิสูจน์บนพร็อด 2026-08-23):
 *   API ทางการไม่มีการค้นด้วยคำ และของในฟีดแพงเกินใช้ (ราคากลาง 192,857฿)
 *   กวาดคลัง 7,631 ชิ้นหาของสายมูที่ยังไม่ติดป้าย → เจอ 25 ชิ้นซึ่งเกือบทั้งหมดเป็นของหลอก
 *   (เลนส์ถ่ายพระเครื่อง · ไม้กอล์ฟ Zodiac · ถ้วยชา Zodiac)
 *   ⇒ ของสายมูราคา 25-1,859฿ มีอยู่ที่เดียวคือพอร์ทัล
 *
 * ท่อ: พอร์ทัล (ได้ itemId + ค่าคอม) → กรองค่าคอม/ราคา → ส่งต่อให้ `lazada:mu-paste`
 *      ซึ่งดึงข้อมูลจริงจาก API ทางการ + ขอลิงก์ + เขียนฐานพร้อม mu_group
 *
 * ⚠️ หยุดทันทีเมื่อคุกกี้ตาย — ไม่ยิงคำที่เหลือให้เปลืองและเสี่ยงโดนกันบอท
 *
 * Usage:
 *   php artisan lazada:mu-scan --target=1200
 *   php artisan lazada:mu-scan --keyword="บ่วงนาคบาศ" --pages=5 --dry
 */
class LazadaMuScan extends Command
{
    protected $signature = 'lazada:mu-scan
                            {--account=2 : id ของ MarketplaceAccount}
                            {--keyword= : ทำเฉพาะคำนี้ (ว่าง = ทุกคำที่เปิดใช้อยู่)}
                            {--pages=4 : ดึงกี่หน้าต่อคำ (หน้าละ 50 ชิ้น)}
                            {--min-commission=5 : ค่าคอมขั้นต่ำ %% (กรองตั้งแต่ขั้นค้น)}
                            {--max-price=2000 : ราคาสูงสุด}
                            {--target=0 : หยุดเมื่อของสายมูในคลังครบกี่ชิ้น (0 = ไม่จำกัด)}
                            {--dry : แสดงผลอย่างเดียว ไม่นำเข้า}';

    protected $description = '🔮 ไล่เก็บของสายมูจากพอร์ทัล Lazada ตามคีย์เวิร์ดที่ตั้งไว้';

    /** หน่วงระหว่างคำ — พอร์ทัลไม่ใช่ API สาธารณะ ยิงรัวเสี่ยงโดนกันบอท */
    private const SLEEP_BETWEEN_KEYWORDS_US = 1500000;

    /** หน่วงระหว่างหน้า */
    private const SLEEP_BETWEEN_PAGES_US = 800000;

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี Lazada id='.$this->option('account'));

            return self::FAILURE;
        }

        $portal = new LazadaPortalSearch($account);
        $keywords = $this->keywords();

        if ($keywords->isEmpty()) {
            $this->error('❌ ไม่มีคีย์เวิร์ดให้ทำ — เปิดใช้งานใน lazada_mu_keywords ก่อน');

            return self::FAILURE;
        }

        $target = max(0, (int) $this->option('target'));
        $pages = max(1, (int) $this->option('pages'));
        $minCom = (float) $this->option('min-commission');
        $maxPrice = (float) $this->option('max-price');
        $dry = (bool) $this->option('dry');

        $this->info('🔮 ไล่เก็บของสายมู '.$keywords->count().' คำ · หน้าละ 50 × '.$pages.' หน้า');
        $this->line('   เกณฑ์: ค่าคอม ≥'.$minCom.'% · ราคา ≤'.number_format($maxPrice).'฿'.($target > 0 ? ' · เป้า '.number_format($target).' ชิ้น' : ''));
        $this->newLine();

        $allIds = [];
        $stats = ['found' => 0, 'passed' => 0];

        foreach ($keywords as $kw) {
            if ($target > 0 && $this->muCount() + count($allIds) >= $target) {
                $this->info('🎯 ครบเป้าแล้ว — หยุดค้นต่อ');
                break;
            }

            $kwIds = [];
            $kwFound = 0;

            for ($p = 1; $p <= $pages; $p++) {
                $r = $portal->search($kw->keyword, $p, 50);

                if ($r['status'] === 'auth_dead') {
                    $this->error('🔒 คุกกี้พอร์ทัลใช้ไม่ได้: '.$r['error']);
                    $this->line('   ตั้งคุกกี้ใหม่แล้วรันซ้ำ — ของที่เก็บได้แล้วจะถูกนำเข้าก่อนจบ');
                    $kw->update(['last_error' => $r['error'], 'last_scanned_at' => now()]);
                    $this->flush($allIds, $dry);

                    return self::FAILURE;
                }

                if ($r['status'] !== 'ok') {
                    $this->warn('   ⚠️ '.$kw->keyword.' หน้า '.$p.': '.$r['error']);
                    $kw->update(['last_error' => $r['error'], 'last_scanned_at' => now()]);
                    break;
                }

                if (empty($r['items'])) {
                    break;
                }

                foreach ($r['items'] as $it) {
                    $kwFound++;
                    if ($it['commission'] < $minCom) {
                        continue;
                    }
                    if ($maxPrice > 0 && $it['price'] > $maxPrice) {
                        continue;
                    }
                    if (! $it['canGetLink']) {
                        continue;
                    }
                    $kwIds[$it['itemId']] = $kw->mu_group;
                }

                usleep(self::SLEEP_BETWEEN_PAGES_US);
            }

            $stats['found'] += $kwFound;
            $stats['passed'] += count($kwIds);
            $allIds += $kwIds;

            $kw->update([
                'last_scanned_at' => now(),
                'last_found_count' => count($kwIds),
                'last_error' => null,
            ]);

            $this->line(sprintf('  %-24s เจอ %3d · ผ่านเกณฑ์ %3d', mb_substr($kw->keyword, 0, 24), $kwFound, count($kwIds)));

            usleep(self::SLEEP_BETWEEN_KEYWORDS_US);
        }

        $this->newLine();
        $this->info("🔍 รวม: เจอ {$stats['found']} ชิ้น · ผ่านเกณฑ์ ".count($allIds).' ชิ้น (ตัดซ้ำแล้ว)');

        $this->flush($allIds, $dry);

        return self::SUCCESS;
    }

    /**
     * ส่ง id ที่เก็บได้ต่อให้ตัวนำเข้า — แยกตามกลุ่มสายมู
     *
     * @param  array<string,string|null>  $ids  [itemId => mu_group]
     */
    private function flush(array $ids, bool $dry): void
    {
        if (empty($ids)) {
            $this->warn('ไม่มีสินค้าให้นำเข้า');

            return;
        }

        if ($dry) {
            $this->line('(dry-run) จะนำเข้า '.count($ids).' ชิ้น');

            return;
        }

        $byGroup = [];
        foreach ($ids as $id => $group) {
            $byGroup[(string) $group][] = $id;
        }

        foreach ($byGroup as $group => $list) {
            $this->newLine();
            $this->info('📥 นำเข้ากลุ่ม '.($group ?: '(ไม่ระบุ)').' — '.count($list).' ชิ้น');

            $this->call('lazada:mu-paste', [
                '--account' => $this->option('account'),
                '--group' => $group ?: null,
                '--ids' => implode(',', $list),
                '--max-price' => $this->option('max-price'),
            ]);
        }
    }

    /**
     * ของสายมูในคลังตอนนี้กี่ชิ้น
     */
    private function muCount(): int
    {
        return \App\Models\MarketplaceProduct::query()->whereNotNull('mu_group')->count();
    }

    /**
     * คีย์เวิร์ดที่จะทำ
     *
     * @return \Illuminate\Support\Collection<int,LazadaMuKeyword>
     */
    private function keywords()
    {
        $one = trim((string) $this->option('keyword'));

        if ($one !== '') {
            $kw = LazadaMuKeyword::where('keyword', $one)->first();

            // คำที่ยังไม่มีในตาราง — สร้างชั่วคราวในหน่วยความจำ ไม่เขียนฐาน
            return collect([$kw ?: new LazadaMuKeyword(['keyword' => $one, 'mu_group' => null])]);
        }

        return LazadaMuKeyword::query()
            ->whereNotNull('mu_group')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();
    }
}
