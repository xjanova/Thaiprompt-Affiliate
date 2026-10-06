<?php

namespace App\Jobs;

use App\Services\Fortune\TakeoverResumeService;
use App\Services\Fortune\TakeoverSendGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🤫 (2026-10-06) ส่งของที่ลูกค้าจ่ายแล้วแต่ถูกพักไว้ระหว่างแอดมินเทคโอเวอร์ — หลังเทคโอเวอร์จบ
 *
 * แยกเป็นคิวเพราะคำทำนาย Deep พร้อมรูปไพ่/ผังดวงใช้เวลาหลายวินาที ห้ามถ่วง request คืนงานของแอดมิน
 *
 * ## ไม่หาย ไม่ซ้ำ (bug-hunt C2)
 * - ลบรายการพัก "ทีละชิ้น หลังส่งถึง" (TakeoverResumeService::deliverDeferred) — job ตาย/deploy ฆ่ากลางทาง
 *   ของที่ยังไม่ส่งยังอยู่ในรายการ ตัวกวาดใน fortune:expire-conversations ตามส่งให้
 * - ชิ้นที่ส่งไม่ออก (Graph 5xx ฯลฯ) → release ลองใหม่ 60 / 180 / 600 วิ (ครบ 3 รอบ = ตัวกวาดรับช่วง
 *   ชิ้นละไม่เกิน TakeoverResumeService::MAX_ATTEMPTS ครั้ง แล้วแจ้งแอดมิน)
 * - แอดมินเทคโอเวอร์ซ้ำระหว่างส่ง → หยุด เก็บที่เหลือไว้ส่งตอนจบรอบหน้า
 * - ล็อกต่อลูกค้า (90 วิ) กันสอง job ส่งให้คนเดียวกันพร้อมกัน
 *
 * ⏱️ timeout 80 < retry_after ของคิว (90) — ไม่เริ่มชิ้นใหม่หลัง 50 วิ ที่เหลือต่อใน job ถัดไป (M4)
 */
class DeliverTakeoverDeferredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 80;

    /** ต่อ job กี่ทอดสำหรับ "ยังไม่ถึงเวลา/ทำไม่ทันในรอบเดียว" (กันวนไม่จบ — ที่เหลือตัวกวาดรับช่วง) */
    private const MAX_HOPS = 5;

    public function __construct(
        public string $platform,
        public string $userId,
        public int $hop = 0,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(): void
    {
        // ทุกการเช็คเทคโอเวอร์ใน job นี้ต้องสด — แอดมินกดเทคโอเวอร์ซ้ำได้ทุกเมื่อ
        TakeoverSendGuard::endMemoScope();

        try {
            $report = TakeoverResumeService::deliverDeferred(
                $this->platform,
                $this->userId,
                microtime(true) + TakeoverResumeService::TIME_BUDGET_SECONDS
            );
        } finally {
            TakeoverResumeService::releaseDispatchKey($this->userId);
        }

        if ($report['locked'] || $report['blocked']) {
            return; // อีก job กำลังส่ง / แอดมินเทคโอเวอร์ซ้ำ — ของที่เหลือรอรอบหน้า
        }

        if ($report['failed'] !== []) {
            $attempt = max(1, $this->attempts());
            if ($attempt < $this->tries && $this->job !== null) {
                $delay = $this->backoff()[$attempt - 1] ?? 600;
                Log::warning('🤫 DeliverTakeoverDeferredJob: บางชิ้นส่งไม่ออก — ลองใหม่', [
                    'platform' => $this->platform,
                    'user_id' => $this->userId,
                    'failed' => $report['failed'],
                    'attempt' => $attempt,
                    'retry_in' => $delay,
                ]);
                $this->release($delay);
            }

            return;
        }

        if (($report['incomplete'] || $report['later'] !== []) && $this->hop < self::MAX_HOPS) {
            TakeoverResumeService::dispatchDelivery(
                $this->platform,
                $this->userId,
                $report['incomplete'] ? 1 : (int) ($report['retry_in'] ?? 60),
                $this->hop + 1
            );
        }
    }
}
