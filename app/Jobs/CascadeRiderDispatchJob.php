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
 * เมื่อเสนองานให้ไรเดอร์ ถ้าไม่ตอบรับภายใน timeout (2 นาที)
 * จะเสนอให้ไรเดอร์คนถัดไปในคิวอัตโนมัติ
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
    public function handle(): void
    {
        $riderJob = RiderJob::find($this->jobId);

        if (! $riderJob) {
            Log::debug('CascadeDispatch: ไม่พบ RiderJob', ['job_id' => $this->jobId]);

            return;
        }

        // ถ้างานถูกรับแล้ว ไม่ต้องทำอะไร
        if ($riderJob->status !== 'pending') {
            Log::debug('CascadeDispatch: งานไม่ได้อยู่ในสถานะ pending', [
                'job_id' => $this->jobId,
                'status' => $riderJob->status,
            ]);

            return;
        }

        $dispatchService = new RiderDispatchService();
        $dispatchService->handleOfferTimeout($riderJob);
    }
}
