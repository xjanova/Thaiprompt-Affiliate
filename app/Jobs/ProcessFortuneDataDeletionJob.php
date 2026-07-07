<?php

namespace App\Jobs;

use App\Models\FortuneDataDeletionRequest;
use App\Services\Fortune\FortunePdpaDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🗑️ (2026-07-07) ProcessFortuneDataDeletionJob — ลบข้อมูลลูกค้าตาม PDPA แบบ async
 *
 * Trigger: Facebook Data Deletion Callback (signed_request) → controller ตอบ FB ทันที
 *          แล้ว enqueue งานนี้ลบข้อมูลจริงเบื้องหลัง (FB ต้องได้ response เร็ว)
 *
 * อ้างอิงคำขอด้วย confirmation_code (row ใน fortune_data_deletion_requests สร้างไว้แล้ว)
 */
class ProcessFortuneDataDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public string $confirmationCode,
        public string $platform,
        public string $platformUserId,
    ) {}

    public function handle(): void
    {
        $request = FortuneDataDeletionRequest::where('confirmation_code', $this->confirmationCode)->first();

        // idempotent — ลบไปแล้วก็ไม่ทำซ้ำ
        if ($request && $request->status === FortuneDataDeletionRequest::STATUS_COMPLETED) {
            return;
        }

        $request?->update(['status' => FortuneDataDeletionRequest::STATUS_PROCESSING]);

        $result = app(FortunePdpaDeletionService::class)
            ->deleteForCustomer($this->platform, $this->platformUserId);

        $request?->update([
            'status' => $result['ok']
                ? FortuneDataDeletionRequest::STATUS_COMPLETED
                : FortuneDataDeletionRequest::STATUS_FAILED,
            'summary' => $result['counts'] ?? [],
            'error' => $result['ok'] ? null : mb_substr((string) ($result['error'] ?? 'unknown'), 0, 500),
            'completed_at' => now(),
        ]);

        Log::info('🗑️ PDPA(FB callback): ประมวลผลลบข้อมูลเสร็จ', [
            'confirmation_code' => $this->confirmationCode,
            'platform' => $this->platform,
            'ok' => $result['ok'],
        ]);
    }

    /**
     * งานล้มเหลวถาวร → mark failed (ไม่ throw ต่อ)
     */
    public function failed(\Throwable $e): void
    {
        try {
            FortuneDataDeletionRequest::where('confirmation_code', $this->confirmationCode)
                ->update([
                    'status' => FortuneDataDeletionRequest::STATUS_FAILED,
                    'error' => mb_substr($e->getMessage(), 0, 500),
                    'completed_at' => now(),
                ]);
        } catch (\Throwable $ignore) {
            // ignore
        }
    }
}
