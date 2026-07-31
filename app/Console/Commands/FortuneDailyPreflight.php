<?php

namespace App\Console\Commands;

use App\Models\FortuneInviteMessage;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortuneGreetingService;
use Illuminate\Console\Command;

/**
 * 🩺 ตรวจความพร้อมของ "โหมด DM ดูดวงรายวัน" ก่อนเปิดใช้จริง
 *
 * 🌙 (2026-07-31) คู่กับ fortune:transfer-preflight ของโหมด transfer
 *
 * เหตุผลที่ต้องมี: โหมดนี้ไป "สัญญา" กับลูกค้าว่าจะทำนายให้ฟรี
 * ถ้าเปิดโหมดแล้วของไม่พร้อม (บทความยังไม่ถูกสร้าง / ไม่มีข้อความชวน)
 * ลูกค้าจะตอบกลับมาแล้วเจอความเงียบ = ความเสียหายที่แย่ที่สุดของบอทตัวนี้
 *
 * ใช้: php artisan fortune:daily-preflight
 */
class FortuneDailyPreflight extends Command
{
    protected $signature = 'fortune:daily-preflight';

    protected $description = 'ตรวจความพร้อมของโหมด DM ดูดวงรายวัน (บทความวันนี้ + ข้อความชวน + สวิตช์)';

    public function handle(): int
    {
        $this->info('🩺 ตรวจความพร้อมโหมดดูดวงรายวัน');
        $this->newLine();

        $problems = 0;

        // ── 1. โหมดปัจจุบัน
        $mode = new FortuneBotMode;
        $current = $mode->mode();

        if ($mode->isDaily()) {
            $this->line('  ✅ โหมดปัจจุบัน : <fg=green>daily</> (ทำงานอยู่)');
        } else {
            $this->line("  ℹ️  โหมดปัจจุบัน : {$current} (ยังไม่ได้เปิดโหมด daily)");
            $this->line('     เปิดได้ที่ Admin → ตั้งค่าดูดวง → โหมดบอท');
        }

        // ── 2. บทความดวงรายวันของวันนี้ (ตัวที่ horoscope:generate-daily สร้าง 06:00)
        $pre = app(FortuneGreetingService::class)->dailyPreflight();

        if ($pre['ready']) {
            $this->line("  ✅ บทความวันนี้ : ครบ 7 วันเกิด ({$pre['today']})");
        } else {
            $problems++;
            $this->line("  ❌ บทความวันนี้ : มี {$pre['found']}/7 ({$pre['today']})");
            $this->line('     ขาด: '.implode(', ', array_map(fn ($d) => 'วัน'.$d, $pre['missing'])));
            $this->line('     แก้: <fg=yellow>php artisan horoscope:generate-daily</>');
        }

        // ── 3. ข้อความชวนชุดโหมด daily
        $inviteCount = FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_DAILY)
            ->where('is_active', true)
            ->count();

        if ($inviteCount > 0) {
            $this->line("  ✅ ข้อความชวน  : {$inviteCount} ข้อความ (mode=daily)");
        } else {
            $problems++;
            $this->line('  ❌ ข้อความชวน  : ไม่มีเลย — DM จะไปหยิบชุดกลางที่ไม่ได้ขอวันเกิด');
            $this->line('     แก้: <fg=yellow>php artisan db:seed --class=FortuneDailyInviteMessageSeeder --force</>');
        }

        $this->newLine();

        if ($problems > 0) {
            $this->error("❌ ยังไม่พร้อม — มี {$problems} เรื่องต้องแก้ก่อนเปิดโหมด");

            return self::FAILURE;
        }

        $this->info('✅ พร้อมใช้งาน');

        return self::SUCCESS;
    }
}
