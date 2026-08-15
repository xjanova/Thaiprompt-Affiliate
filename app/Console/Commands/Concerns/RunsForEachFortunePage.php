<?php

namespace App\Console\Commands\Concerns;

use App\Models\FortunePage;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-15) วน cron ให้ครบทุกสาขา
 *
 * ปัญหาที่แก้:
 *   scheduled command เริ่มทำงานด้วย FortunePageContext ว่างเปล่าเสมอ
 *   (ไม่มี webhook มา bind ให้เหมือนตอนลูกค้าทัก)
 *   → getSettings() คืนค่ากลาง → โพส/ส่งด้วย token ของเพจหลักเพจเดียว
 *   สาขาที่เปิดใหม่จึงไม่เคยมีดวงรายวัน/คอนเทนต์เลย แบบไม่มี error ให้เห็น
 *
 * ⚠️ ก่อนใช้ trait นี้กับ command ไหน ต้องแน่ใจว่า "สมุดกันโพสซ้ำ" ของงานนั้น
 *    แยกรายสาขาแล้ว ไม่งั้นสาขาแรกโพสเสร็จ สาขาที่เหลือจะเห็นว่า "โพสแล้ว"
 *    แล้วข้ามเงียบๆ (ดู migration add_fortune_page_id_to_post_ledger_tables)
 */
trait RunsForEachFortunePage
{
    /**
     * วนทำงานให้ทุกสาขาที่เปิดอยู่
     *
     * @param  string  $platform  facebook|line
     * @param  callable(FortunePage|null): bool  $callback  คืน true = สำเร็จ
     * @return array{ran: int, ok: int, failed: int}
     */
    protected function forEachActiveFortunePage(string $platform, callable $callback, bool $requireAutoPost = false): array
    {
        $pages = $this->resolveTargetPages($platform, $requireAutoPost);

        if ($pages === []) {
            // 🛑 มีระบบสาขาใช้งานอยู่จริง แต่ไม่มีสาขาไหนติ๊ก "โพสอัตโนมัติ" ไว้
            //    = แอดมินสั่งปิด ต้องไม่ทำอะไรเลย
            //    ⚠️ ถ้าตกลงไป fallback ข้างล่างจะกลายเป็นโพสลงเพจหลักด้วยค่ากลาง
            //       ทั้งที่แอดมินเพิ่งสั่งปิด — พังแบบสวนทางคำสั่งคน
            if ($requireAutoPost && $this->branchSystemInUse($platform)) {
                $this->info('ℹ️  ไม่มีสาขาไหนเปิด “โพสอัตโนมัติ” ไว้ → ข้าม');

                return ['ran' => 0, 'ok' => 0, 'failed' => 0];
            }

            // ไม่มีสาขาให้วน (ติดตั้งใหม่ / ตารางยังไม่ migrate / --page ชี้ผิด)
            // → ทำงานครั้งเดียวแบบไม่มี context = พฤติกรรมเดิมเป๊ะ ห้ามเงียบหาย
            $ok = (bool) $callback(null);

            return ['ran' => 1, 'ok' => $ok ? 1 : 0, 'failed' => $ok ? 0 : 1];
        }

        $ran = 0;
        $ok = 0;
        $failed = 0;

        foreach ($pages as $page) {
            $ran++;
            $this->line("🏬 สาขา: {$page->name} ({$page->code})");

            try {
                // สาขาหนึ่งพังต้องไม่ลากสาขาที่เหลือตายตาม
                $result = FortunePageContext::run($page, fn () => (bool) $callback($page));

                $result ? $ok++ : $failed++;
            } catch (\Throwable $e) {
                $failed++;

                $this->error("   ❌ {$page->code}: {$e->getMessage()}");

                Log::error('🏬 cron สาขาล้มเหลว', [
                    'command' => $this->getName(),
                    'fortune_page_id' => $page->id,
                    'code' => $page->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['ran' => $ran, 'ok' => $ok, 'failed' => $failed];
    }

    /**
     * สาขาที่จะทำงานให้ — ทุกสาขาที่เปิด หรือเฉพาะที่ระบุด้วย --page
     *
     * @return array<int, FortunePage>
     */
    /**
     * ระบบสาขาใช้งานอยู่จริงไหม (มีตาราง + มีคอลัมน์สวิตช์ + มีสาขาที่เปิดอยู่)
     *
     * ใช้แยก "แอดมินสั่งปิดโพสไว้" ออกจาก "ยังไม่มีระบบสาขา" — 2 กรณีนี้ต้องทำคนละอย่าง
     */
    protected function branchSystemInUse(string $platform): bool
    {
        try {
            if (! Schema::hasTable('fortune_pages') || ! Schema::hasColumn('fortune_pages', 'auto_post_enabled')) {
                return false;
            }

            return FortunePage::query()
                ->where('platform', $platform)
                ->where('is_active', true)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function resolveTargetPages(string $platform, bool $requireAutoPost = false): array
    {
        // ⚠️ deploy: โค้ดขึ้นก่อน migration ได้เสมอ — ตารางยังไม่มีต้องไม่พังทั้ง command
        try {
            if (! Schema::hasTable('fortune_pages')) {
                return [];
            }

            $query = FortunePage::query()
                ->where('platform', $platform)
                ->where('is_active', true)
                ->orderByDesc('is_default')   // สาขาหลักก่อนเสมอ — ของสำคัญที่สุดได้คิวแรก
                ->orderBy('id');

            // 🏬 โพสลงหน้าเพจ = opt-in เท่านั้น แอดมินต้องติ๊กเองในหน้าสาขา
            //    (เปิดสาขาใหม่แล้วบอทแอบไปโพสลงเพจเองคือสิ่งที่ต้องไม่เกิด)
            //    คอลัมน์ยังไม่มี = ช่วง deploy ก่อน migration → ไม่กรอง
            //    เพราะตอนนั้นมีแต่สาขาหลักที่โพสอยู่แล้ว พฤติกรรมจึงเหมือนเดิม
            if ($requireAutoPost && Schema::hasColumn('fortune_pages', 'auto_post_enabled')) {
                $query->where('auto_post_enabled', true);
            }

            // --page รับได้ทั้งรหัสสาขา, id ในระบบ, และไอดีเพจของ Facebook
            $only = $this->hasOption('page') ? trim((string) $this->option('page')) : '';

            if ($only !== '') {
                $query->where(function ($q) use ($only) {
                    $q->where('code', $only)
                        ->orWhere('external_page_id', $only);

                    if (ctype_digit($only)) {
                        $q->orWhere('id', (int) $only);
                    }
                });
            }

            return $query->get()->all();
        } catch (\Throwable $e) {
            Log::warning('🏬 หาสาขาสำหรับ cron ไม่ได้ ใช้ค่ากลางแทน', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
