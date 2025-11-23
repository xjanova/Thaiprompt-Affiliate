<?php

namespace App\Jobs;

use App\Models\LineFailedMessage;
use App\Services\LineAutoRetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Retry Failed Messages Job
 *
 * ประมวลผลการ retry ข้อความ LINE ที่ส่งล้มเหลวแบบ asynchronous
 * ทำงานใน queue เพื่อไม่ให้กระทบกับ main application
 *
 * Features:
 * - Asynchronous processing (ไม่ block main thread)
 * - Auto-retry on job failure (3 tries)
 * - Exponential backoff delay
 * - Graceful error handling
 * - Idempotent (ทำซ้ำได้ปลอดภัย)
 *
 * @package App\Jobs
 */
class RetryFailedMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID ของข้อความที่ล้มเหลว
     *
     * @var int
     */
    protected $failedMessageId;

    /**
     * จำนวนครั้งที่ job นี้จะพยายามทำซ้ำถ้าล้มเหลว
     *
     * @var int
     */
    public $tries = 3;

    /**
     * จำนวนวินาทีที่ job สามารถทำงานได้สูงสุด
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * สร้าง job instance ใหม่
     *
     * @param int $failedMessageId ID ของ LINE Failed Message
     * @return void
     */
    public function __construct(int $failedMessageId)
    {
        $this->failedMessageId = $failedMessageId;

        // ตั้งค่า queue เป็น 'line-retry' (แยกจาก default queue)
        $this->onQueue('line-retry');
    }

    /**
     * ประมวลผล job
     *
     * ดึง failed message และทำการ retry ผ่าน LineAutoRetryService
     *
     * @param LineAutoRetryService $retryService
     * @return void
     */
    public function handle(LineAutoRetryService $retryService): void
    {
        Log::info('RetryFailedMessagesJob started', [
            'failed_message_id' => $this->failedMessageId,
            'job_id' => $this->job->getJobId(),
            'attempt' => $this->attempts(),
        ]);

        try {
            // ดึง failed message
            $failedMessage = LineFailedMessage::find($this->failedMessageId);

            // ตรวจสอบว่า record ยังมีอยู่หรือไม่
            if (!$failedMessage) {
                Log::warning('RetryFailedMessagesJob: Failed message not found', [
                    'failed_message_id' => $this->failedMessageId,
                ]);
                return;
            }

            // ตรวจสอบว่ายังสามารถ retry ได้หรือไม่
            if (!$failedMessage->shouldRetry()) {
                Log::info('RetryFailedMessagesJob: Message should not retry', [
                    'failed_message_id' => $this->failedMessageId,
                    'status' => $failedMessage->status,
                    'retry_count' => $failedMessage->retry_count,
                ]);
                return;
            }

            // ทำการ retry
            $success = $retryService->retry($failedMessage);

            if ($success) {
                Log::info('RetryFailedMessagesJob: Retry succeeded', [
                    'failed_message_id' => $this->failedMessageId,
                    'retry_count' => $failedMessage->retry_count,
                ]);
            } else {
                Log::warning('RetryFailedMessagesJob: Retry failed', [
                    'failed_message_id' => $this->failedMessageId,
                    'retry_count' => $failedMessage->retry_count,
                    'max_retries' => $failedMessage->max_retries,
                ]);
            }

        } catch (Exception $e) {
            // Log error แต่ไม่ throw ต่อ (ป้องกันไม่ให้ job ถูก retry อีก)
            Log::error('RetryFailedMessagesJob failed', [
                'failed_message_id' => $this->failedMessageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // ถ้าเป็น attempt สุดท้าย ให้บันทึกว่า job ล้มเหลว
            if ($this->attempts() >= $this->tries) {
                Log::critical('RetryFailedMessagesJob: Max attempts reached', [
                    'failed_message_id' => $this->failedMessageId,
                    'attempts' => $this->attempts(),
                ]);
            }

            // Re-throw exception เพื่อให้ queue system จัดการ retry
            throw $e;
        }
    }

    /**
     * จัดการเมื่อ job ล้มเหลว
     *
     * เมื่อ job ถูก retry ครบตามจำนวนครั้งที่กำหนดแล้วยังล้มเหลว
     * method นี้จะถูกเรียก
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::critical('RetryFailedMessagesJob permanently failed', [
            'failed_message_id' => $this->failedMessageId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // อัพเดทสถานะ failed message ให้เป็น abandoned (ถ้ายังมี record)
        try {
            $failedMessage = LineFailedMessage::find($this->failedMessageId);

            if ($failedMessage && !in_array($failedMessage->status, [
                LineFailedMessage::STATUS_SUCCEEDED,
                LineFailedMessage::STATUS_ABANDONED
            ])) {
                $failedMessage->markAsAbandoned();

                Log::info('Marked failed message as abandoned due to job failure', [
                    'failed_message_id' => $this->failedMessageId,
                ]);
            }
        } catch (Exception $e) {
            // ถ้า failed() method ล้มเหลว ให้ log แต่ไม่ throw
            Log::error('Failed to mark message as abandoned', [
                'failed_message_id' => $this->failedMessageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * คำนวณเวลา delay ก่อนที่จะ retry job นี้ (ถ้า job ล้มเหลว)
     *
     * ใช้ exponential backoff เหมือนกับ message retry
     *
     * @return int จำนวนวินาที
     */
    public function backoff(): int
    {
        // Exponential backoff: 2^attempt seconds
        // 1st retry: 2s, 2nd: 4s, 3rd: 8s
        return min(pow(2, $this->attempts()), 30);
    }

    /**
     * กำหนดว่า job นี้ควรถูก retry หรือไม่เมื่อเกิด exception
     *
     * @return bool
     */
    public function shouldRetry(): bool
    {
        // Retry ได้เฉพาะเมื่อยังไม่ถึง max attempts
        return $this->attempts() < $this->tries;
    }

    /**
     * Tags สำหรับ monitoring และ debugging
     *
     * ใช้โดย queue monitoring tools เช่น Laravel Horizon
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'line',
            'retry',
            'messaging',
            "failed-message:{$this->failedMessageId}",
        ];
    }

    /**
     * คำอธิบาย job สำหรับ monitoring
     *
     * @return string
     */
    public function displayName(): string
    {
        return "Retry LINE Failed Message #{$this->failedMessageId}";
    }
}
