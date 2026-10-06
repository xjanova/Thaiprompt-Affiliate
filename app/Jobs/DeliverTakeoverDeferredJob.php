<?php

namespace App\Jobs;

use App\Services\Fortune\TakeoverResumeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 🤫 (2026-10-06) ส่งของที่ลูกค้าจ่ายแล้วแต่ถูกพักไว้ระหว่างแอดมินเทคโอเวอร์ — หลังเทคโอเวอร์จบ
 *
 * แยกเป็นคิวเพราะคำทำนาย Deep พร้อมรูปไพ่/ผังดวงใช้เวลาหลายวินาที ห้ามถ่วง request คืนงานของแอดมิน
 * ครั้งเดียวแน่นอน: TakeoverResumeService::claim() ล็อกแถวแล้วลบรายการพักในธุรกรรมเดียว
 */
class DeliverTakeoverDeferredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ห้าม retry — ของที่หยิบไปแล้วถูกลบจากรายการพักแล้ว (ตัวส่งแต่ละชิ้นมีทางสำรองของตัวเอง) */
    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public string $platform,
        public string $userId,
    ) {}

    public function handle(): void
    {
        TakeoverResumeService::deliverDeferred($this->platform, $this->userId);
    }
}
