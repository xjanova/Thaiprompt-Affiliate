<?php

namespace App\Console\Commands;

use App\Services\Fortune\FortunePredictionMenu;
use Illuminate\Console\Command;

/**
 * 🔮 (2026-10-24) คืนเมนู LINE หลักให้ลูกค้าที่ทำนายจบแล้วแต่ยังค้างเมนูช่วงทำนาย
 */
class FortunePredictionMenuSweep extends Command
{
    protected $signature = 'fortune:prediction-menu-sweep {--hours=6 : ดูบิลที่ขยับภายในกี่ชั่วโมง}';

    protected $description = 'สลับเมนู LINE กลับเป็นเมนูหลักให้คนที่ทำนายจบแล้ว';

    public function handle(FortunePredictionMenu $menu): int
    {
        $checked = $menu->sweep(max(1, (int) $this->option('hours')));
        $this->info("ตรวจเมนูลูกค้า LINE {$checked} คน");

        return self::SUCCESS;
    }
}
