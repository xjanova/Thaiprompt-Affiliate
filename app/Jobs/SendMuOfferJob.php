<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Services\Fortune\FortuneMuOfferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🛒 ส่งการ์ดเสนอสินค้าแบบ "หน่วงเวลา" — ไม่ให้ไปทับข้อความที่ลูกค้าเพิ่งได้รับ
 *
 * เจตนา (owner 2026-08-23): "การส่งสินค้าให้กับคนที่รับดวงรายวันไป ให้ส่งห่างกัน 1 ชม."
 * เดิมการ์ดถูกส่งต่อท้ายกล่องดวงฟรีทันที = สองกล่องติดกัน อ่านดวงยังไม่ทันจบก็เจอของขาย
 *
 * ✅ ปลอดภัยกับ deploy — ตรวจแล้วบนพร็อด 2026-08-23:
 *    cache = redis DB 1 · queue = redis DB 0
 *    `deploy.sh` รัน `cache:clear` (= flushdb) ซึ่งล้างเฉพาะ DB 1
 *    ⇒ งานที่หน่วงไว้ 1 ชม. รอดจากการ deploy ระหว่างทาง
 *    (ถ้าวันไหนย้าย cache ไป DB เดียวกับ queue ต้องกลับมาคิดใหม่ทั้งหมด)
 *
 * ⚠️ คิวต้องเป็นชื่อที่มี worker จริงเท่านั้น: `tpix-default`, `default`, `tpix-low`
 *    (`queue:work --queue=tpix-default,default,tpix-low` ตาม deploy.sh)
 *    ตั้งชื่อคิวใหม่ = ไม่มีใครกิน งานค้างเงียบตลอดกาล
 *
 * ⚠️ ด่านทั้งหมด (เพดานรายวัน · คนสั่งเงียบ · คนถูกแบน) ถูกตรวจ **ตอน job ทำงาน**
 *    ไม่ใช่ตอน dispatch — ลูกค้าที่บอก "รำคาญ" ในระหว่าง 1 ชม. นั้นจะไม่ได้การ์ด
 */
class SendMuOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ไม่ retry — ขายของพลาดรอบเดียวไม่เป็นไร แต่ retry แล้วยิงซ้ำ = รบกวนลูกค้า
     */
    public int $tries = 1;

    public int $timeout = 60;

    /**
     * @param  string  $platform  facebook | line
     * @param  string  $platformUserId  FB PSID / LINE userId
     * @param  string  $trigger  FortuneProductOffer::TRIGGER_*
     * @param  int|null  $readingId  ผูกกับการดูดวงใบไหน (ส่ง id ไม่ส่งโมเดล — กัน payload บวม/ข้อมูลเก่า)
     * @param  array<string,mixed>  $options  ส่งต่อให้ FortuneMuOfferService::offer()
     */
    public function __construct(
        public string $platform,
        public string $platformUserId,
        public string $trigger,
        public ?int $readingId = null,
        public array $options = [],
    ) {
        $this->onQueue('tpix-low');
    }

    /**
     * ส่งการ์ด — ด่านทั้งหมดอยู่ใน service ตัวเดียว
     */
    public function handle(FortuneMuOfferService $service): void
    {
        try {
            // อ่าน reading สดตอนนี้ ไม่ใช่ snapshot ตอน dispatch
            // (สถานะอาจเปลี่ยนระหว่าง 1 ชม. เช่นลูกค้ากลับมาจ่ายเงินแล้ว)
            $reading = $this->readingId ? FortuneReading::find($this->readingId) : null;

            $sent = $service->offer(
                $this->platform,
                $this->platformUserId,
                $this->trigger,
                $reading,
                $this->options
            );

            Log::info('MuOffer: ส่งการ์ดแบบหน่วงเวลา', [
                'platform' => $this->platform,
                'user_id' => $this->platformUserId,
                'trigger' => $this->trigger,
                'sent' => $sent,
            ]);
        } catch (\Throwable $e) {
            // ขายของพังห้ามทำให้คิวพัง (tries=1 อยู่แล้ว แต่กัน job ตกลง failed_jobs รัวๆ)
            Log::warning('MuOffer: job ส่งการ์ดแบบหน่วงเวลาล้มเหลว', [
                'user_id' => $this->platformUserId,
                'trigger' => $this->trigger,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
