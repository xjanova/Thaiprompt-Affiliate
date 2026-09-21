<?php

namespace App\Console\Commands;

use App\Models\JuntraAccount;
use App\Services\Juntra\JuntraAccountMerger;
use Illuminate\Console\Command;

/**
 * 🌙 (2026-09-21) เก็บตกหลังรวมบัญชีลูกค้าจันทรา
 *
 * ตอนรวมบัญชี บิลของลูกทีมที่กำลังแจกค่าแนะนำพร้อมกัน (ต่างล็อกกัน) อาจเขียนค่าแนะนำ/ยอดกระเป๋า
 *   ลงผู้ใช้เงาหลังรวมเสร็จไม่กี่มิลลิวินาที — รอบนี้ย้ายของที่ค้างไปบัญชีจริงให้
 *   ดูเฉพาะบัญชีที่เพิ่งรวม (ช่องที่ชนกันได้สั้นมาก 2 วันเหลือเฟือ)
 */
class JuntraSweepMergedAccounts extends Command
{
    protected $signature = 'juntra:sweep-merged-accounts {--days=2 : ดูบัญชีที่รวมในกี่วันล่าสุด}';

    protected $description = '🌙 ย้ายค่าแนะนำ/ยอดกระเป๋าที่ค้างในผู้ใช้เงาหลังรวมบัญชีจันทรา ไปบัญชี Thaiprompt';

    public function handle(JuntraAccountMerger $merger): int
    {
        $moved = 0;

        JuntraAccount::whereNotNull('merged_from_user_id')
            ->where('merged_at', '>=', now()->subDays(max(1, (int) $this->option('days'))))
            ->orderBy('id')
            ->each(function (JuntraAccount $account) use ($merger, &$moved) {
                try {
                    $moved += $merger->sweep($account) ? 1 : 0;
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        $this->info("เก็บตก {$moved} บัญชี");

        return self::SUCCESS;
    }
}
