<?php

namespace App\Console\Commands;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use App\Models\LineRichMenu;
use App\Services\FortuneRichMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ผลักดัน Rich Menu ปัจจุบันให้ลูกค้า LINE ทุกคนทันที
 *
 * 🔗 (2026-07-25) เจ้าของรายงาน: "บางคนเมนูไม่เปลี่ยนเป็นที่เราเพิ่งทำใหม่"
 *
 * สาเหตุ: การ deploy rich menu ใช้ `setDefaultRichMenu` (endpoint user/all) ซึ่ง LINE
 * ถือว่าเป็น "เมนูสำรองของ OA" — มีผลเฉพาะคนที่ **ไม่มีเมนูผูกรายคน** เท่านั้น
 * ใครเคยถูกผูกเมนูไว้ (LINE OA Manager / ระบบเก่า / A-B test) จะค้างเมนูเดิมตลอดไป
 *
 * คำสั่งนี้ใช้ `richmenu/bulk/link` (500 คน/ครั้ง) เขียนทับ per-user link ของทุกคน
 *
 * ใช้:
 *   php artisan fortune:rich-menu-sync             # ผลักเมนู active ปัจจุบันให้ทุกคน
 *   php artisan fortune:rich-menu-sync --dry-run   # ดูจำนวนคนก่อน ไม่ยิงจริง
 *   php artisan fortune:rich-menu-sync --check=U... # ตรวจว่าคนนี้ผูกเมนูตัวไหนอยู่
 */
class FortuneRichMenuSync extends Command
{
    protected $signature = 'fortune:rich-menu-sync
                            {--dry-run : นับจำนวนผู้ใช้อย่างเดียว ไม่ยิง LINE API}
                            {--menu= : ระบุ rich menu id เอง (ไม่ระบุ = ตัว active ใน DB)}
                            {--check= : ตรวจว่า LINE userId นี้ผูกเมนูตัวไหนอยู่}';

    protected $description = 'ผลักดัน Rich Menu ปัจจุบันให้ลูกค้า LINE ทุกคนทันที (แก้เคสเมนูไม่อัปเดต)';

    /**
     * รันการ sync
     */
    public function handle(): int
    {
        $service = new FortuneRichMenuService;
        $lineService = $service->getLineService();

        // ── โหมดตรวจสอบรายคน ──
        $checkUser = (string) ($this->option('check') ?? '');
        if ($checkUser !== '') {
            $linked = $lineService->getUserRichMenuId($checkUser);
            $this->info('👤 '.$checkUser);
            $this->line('   เมนูที่ผูกรายคน: '.($linked ?: '— ไม่มี (ใช้ default ของ OA)'));

            return self::SUCCESS;
        }

        // ── หา rich menu ปลายทาง ──
        $richMenuId = (string) ($this->option('menu') ?? '');
        if ($richMenuId === '') {
            $active = LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->latest()
                ->first();

            if (! $active || empty($active->rich_menu_id)) {
                $this->error('❌ ไม่พบ Rich Menu ที่ active ใน DB — deploy เมนูก่อน (Admin → Fortune → Rich Menu)');

                return self::FAILURE;
            }

            $richMenuId = $active->rich_menu_id;
            $this->info("📋 เมนูปลายทาง: {$richMenuId} (deploy เมื่อ {$active->created_at})");
        }

        // ── รวบรวม LINE userId จากทุกแหล่ง ──
        $userIds = $this->collectLineUserIds();
        $total = count($userIds);

        if ($total === 0) {
            $this->warn('ไม่พบลูกค้า LINE ในระบบ');

            return self::SUCCESS;
        }

        $this->info("👥 พบลูกค้า LINE ทั้งหมด {$total} คน");

        if ($this->option('dry-run')) {
            $this->warn('🔍 dry-run — ไม่ได้ยิง LINE API');
            $this->line('   ตัวอย่าง 3 คนแรก: '.implode(', ', array_slice($userIds, 0, 3)));

            return self::SUCCESS;
        }

        // ── ยิงทีละ 500 (ลิมิตของ LINE bulk/link) ──
        $batches = array_chunk($userIds, 500);
        $okBatches = 0;
        $failBatches = 0;

        $bar = $this->output->createProgressBar(count($batches));
        $bar->start();

        foreach ($batches as $batch) {
            if ($lineService->bulkLinkRichMenu($batch, $richMenuId)) {
                $okBatches++;
            } else {
                $failBatches++;
            }
            $bar->advance();
            usleep(200000); // 0.2s — กัน rate limit
        }

        $bar->finish();
        $this->newLine(2);

        $syncedCount = $okBatches * 500;
        $this->info("✅ สำเร็จ {$okBatches} ชุด (~{$syncedCount} คน)".($failBatches > 0 ? " · ล้มเหลว {$failBatches} ชุด" : ''));

        Log::info('fortune:rich-menu-sync: ผลักดัน Rich Menu ให้ลูกค้า LINE', [
            'rich_menu_id' => $richMenuId,
            'total_users' => $total,
            'batches_ok' => $okBatches,
            'batches_fail' => $failBatches,
        ]);

        if ($failBatches > 0) {
            $this->warn('⚠️ มีชุดที่ล้มเหลว — ดูรายละเอียดใน storage/logs/laravel.log แล้วรันซ้ำได้ (idempotent)');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('💡 ลูกค้าบางคนอาจต้องปิด-เปิดห้องแชทหนึ่งครั้ง เมนูถึงจะรีเฟรช (LINE cache ฝั่งเครื่อง)');

        return self::SUCCESS;
    }

    /**
     * รวบรวม LINE userId จากทุกตารางที่เก็บลูกค้าไว้ (unique)
     *
     * @return array<string>
     */
    protected function collectLineUserIds(): array
    {
        $ids = [];

        // 1. บทสนทนา LINE (แหล่งใหญ่สุด — ครอบคนที่เคยทักมาทั้งหมด)
        try {
            $ids = array_merge($ids, DB::table('line_bot_conversations')
                ->whereNotNull('line_user_id')
                ->distinct()
                ->pluck('line_user_id')
                ->all());
        } catch (\Throwable $e) {
            $this->warn('  ⚠️ อ่าน line_bot_conversations ไม่ได้: '.$e->getMessage());
        }

        // 2. บิลดูดวง
        try {
            $ids = array_merge($ids, FortuneReading::where('platform', 'line')
                ->whereNotNull('platform_user_id')
                ->distinct()
                ->pluck('platform_user_id')
                ->all());
        } catch (\Throwable $e) {
            // ข้ามได้
        }

        // 3. เครดิต/สิทธิ์ดูดวง
        try {
            $ids = array_merge($ids, FortuneUserCredit::where('platform', 'line')
                ->whereNotNull('facebook_user_id')
                ->distinct()
                ->pluck('facebook_user_id')
                ->all());
        } catch (\Throwable $e) {
            // ข้ามได้
        }

        // 4. persona ลูกค้า
        try {
            $ids = array_merge($ids, FortuneCustomerPersona::where('platform', 'line')
                ->whereNotNull('platform_user_id')
                ->distinct()
                ->pluck('platform_user_id')
                ->all());
        } catch (\Throwable $e) {
            // ข้ามได้
        }

        // เก็บเฉพาะรูปแบบ LINE userId จริง (U + hex 32) — กัน PSID ของ FB ปนมา
        return array_values(array_unique(array_filter(
            $ids,
            fn ($id) => is_string($id) && preg_match('/^U[0-9a-f]{32}$/i', $id)
        )));
    }
}
