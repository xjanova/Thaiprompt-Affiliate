<?php

namespace App\Jobs;

use App\Models\RiderJob;
use App\Services\RiderDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cascade Rider Dispatch Job
 *
 * โหมด cascade (rider.dispatch_mode = cascade): เสนองานให้ไรเดอร์ทีละคน
 * ถ้าไม่ตอบรับภายใน rider.offer_timeout_seconds จะเสนอคนถัดไปอัตโนมัติ
 *
 * ถ้าคิวไม่ได้รัน คำสั่ง rider:sweep-pending (ทุกนาที) จะเลื่อน offer ที่หมดเวลาให้แทน
 */
class CascadeRiderDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * จำนวนครั้งที่ลองใหม่
     */
    public int $tries = 1;

    /**
     * Timeout (วินาที)
     */
    public int $timeout = 30;

    /**
     * RiderJob ID ที่จะตรวจสอบ
     */
    protected int $jobId;

    /**
     * สร้าง job instance
     */
    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * ตรวจสอบว่า offer หมดเวลาหรือยัง แล้วเสนอให้คนถัดไป
     */
    public function handle(RiderDispatchService $dispatchService): void
    {
        $riderJob = RiderJob::find($this->jobId);

        if (! $riderJob) {
            Log::debug('CascadeDispatch: RiderJob not found', ['job_id' => $this->jobId]);

            return;
        }

        // ถ้างานถูกรับแล้ว/ถูกยกเลิก ไม่ต้องทำอะไร
        if (! $riderJob->isOpen()) {
            return;
        }

        $dispatchService->handleOfferTimeout($riderJob);
    }
}
