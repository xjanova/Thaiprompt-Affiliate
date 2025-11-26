<?php

namespace App\Jobs;

use App\Models\MlmRebuildTask;
use App\Services\MlmTreeRebuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RebuildUnilevelTreeJob - Background job สำหรับ rebuild Unilevel tree
 *
 * ฟีเจอร์:
 * - ทำงานใน background (ไม่ block request)
 * - Retry อัตโนมัติเมื่อล้มเหลว
 * - Progress tracking ผ่าน MlmRebuildTask
 * - ทำงานต่อได้แม้หน้าเว็บปิด
 *
 * การใช้งาน:
 * ```php
 * $task = MlmRebuildTask::createWithLock('unilevel_rebuild', $config, auth()->id());
 * RebuildUnilevelTreeJob::dispatch($task->id);
 * ```
 */
class RebuildUnilevelTreeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * จำนวนครั้งที่ retry เมื่อล้มเหลว
     */
    public int $tries = 3;

    /**
     * เวลาที่รอก่อน retry (วินาที)
     */
    public int $backoff = 60;

    /**
     * Timeout (วินาที) - 30 นาที
     */
    public int $timeout = 1800;

    /**
     * @var int
     */
    protected int $taskId;

    /**
     * Create a new job instance.
     *
     * @param int $taskId
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     */
    public function handle(MlmTreeRebuildService $rebuildService): void
    {
        Log::info('RebuildUnilevelTreeJob started', ['task_id' => $this->taskId]);

        $task = MlmRebuildTask::find($this->taskId);

        if (!$task) {
            Log::error('Task not found', ['task_id' => $this->taskId]);
            return;
        }

        // ตรวจสอบสถานะ
        if (!in_array($task->status, [MlmRebuildTask::STATUS_PENDING, MlmRebuildTask::STATUS_RUNNING])) {
            Log::warning('Task is not in valid state for processing', [
                'task_id' => $this->taskId,
                'status' => $task->status,
            ]);
            return;
        }

        // ถ้า pending → เริ่ม
        if ($task->status === MlmRebuildTask::STATUS_PENDING) {
            $totalMembers = \App\Models\MlmMember::count();
            $task->start($totalMembers);
        }

        // ดำเนินการ rebuild
        $success = $rebuildService->executeRebuild($task);

        if ($success) {
            // อัพเดทสถิติหลัง rebuild
            $rebuildService->updateStatisticsAfterRebuild();

            Log::info('RebuildUnilevelTreeJob completed successfully', ['task_id' => $this->taskId]);
        } else {
            Log::error('RebuildUnilevelTreeJob failed', ['task_id' => $this->taskId]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RebuildUnilevelTreeJob failed with exception', [
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $task = MlmRebuildTask::find($this->taskId);

        if ($task && $task->status === MlmRebuildTask::STATUS_RUNNING) {
            $task->markFailed($exception->getMessage(), [
                'exception_class' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'rebuild_unilevel_' . $this->taskId;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['mlm', 'rebuild', 'unilevel', 'task:' . $this->taskId];
    }
}
