<?php

namespace App\Console\Commands;

use App\Services\TpixPay\TpixPaymentService;
use Illuminate\Console\Command;

/**
 * 🪙 สแกนการชำระด้วยเหรียญ TPIX ทุกนาที
 *
 * - บันทึกราคาจากพูล DEX (ใช้คิดค่าเฉลี่ยกันปั่นราคา)
 * - ตรวจรายการที่รอ confirmation / ลูกค้าส่งเลขธุรกรรมตอนเชนล่ม
 * - หาการโอนเข้ากระเป๋ารับเงินที่ลูกค้าไม่ได้ส่งเลขธุรกรรม (จับคู่ด้วยยอด wei ตรงตัว)
 * - ใบหมดเวลา / ปล่อยยอดที่พ้นช่วงกันยอด / ยกเลิกออเดอร์ TPIX ที่ไม่มีการโอน
 */
class TpixPayScan extends Command
{
    protected $signature = 'tpix:pay-scan';

    protected $description = 'ตรวจการชำระด้วยเหรียญ TPIX บนเชน 4289 + บันทึกราคาจากพูล DEX';

    public function handle(TpixPaymentService $payments): int
    {
        $stats = $payments->scan();

        $this->line(sprintf(
            'TPIX pay scan: price=%s confirmed=%d matched=%d expired=%d released=%d duplicate=%d cancelled_orders=%d',
            $stats['snapshot'] ? 'ok' : 'unavailable',
            $stats['confirmed'],
            $stats['matched'],
            $stats['expired'],
            $stats['released'],
            $stats['stray'],
            $stats['cancelled_orders']
        ));

        return self::SUCCESS;
    }
}
