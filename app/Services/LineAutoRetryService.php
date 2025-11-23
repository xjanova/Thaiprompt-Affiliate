<?php

namespace App\Services;

use App\Models\LineFailedMessage;
use App\Models\LineErrorLog;
use App\Jobs\RetryFailedMessagesJob;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * LINE Auto-Retry Service
 *
 * จัดการระบบ Auto-Retry สำหรับข้อความ LINE ที่ส่งล้มเหลว
 * รองรับ:
 * - Exponential backoff retry
 * - Circuit breaker pattern
 * - Error classification
 * - Self-healing recovery
 *
 * @package App\Services
 */
class LineAutoRetryService
{
    /**
     * LineService instance
     *
     * @var LineService
     */
    protected $lineService;

    /**
     * Constructor
     *
     * @param LineService $lineService
     */
    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    /**
     * บันทึกข้อความที่ส่งล้มเหลว
     *
     * @param string $lineUserId LINE User ID
     * @param string $messageType ประเภทข้อความ
     * @param array $messagePayload ข้อมูลข้อความ
     * @param Exception $exception Exception ที่เกิดขึ้น
     * @param int $maxRetries จำนวนครั้งสูงสุดที่จะ retry (default: 5)
     * @return LineFailedMessage
     */
    public function recordFailure(
        string $lineUserId,
        string $messageType,
        array $messagePayload,
        Exception $exception,
        int $maxRetries = 5
    ): LineFailedMessage {
        // สร้าง failed message record
        $failedMessage = LineFailedMessage::createFromException(
            $lineUserId,
            $messageType,
            $messagePayload,
            $exception,
            $maxRetries
        );

        // สร้าง error log
        $this->logError($exception, $failedMessage->id, $lineUserId);

        // Dispatch retry job (ถ้ายังสามารถ retry ได้)
        if ($failedMessage->shouldRetry()) {
            $this->scheduleRetry($failedMessage);
        }

        Log::info('LINE Failed Message recorded', [
            'id' => $failedMessage->id,
            'line_user_id' => $lineUserId,
            'message_type' => $messageType,
            'error_type' => $failedMessage->error_type,
            'retry_count' => $failedMessage->retry_count,
            'max_retries' => $failedMessage->max_retries,
        ]);

        return $failedMessage;
    }

    /**
     * ทำการ retry ข้อความที่ล้มเหลว
     *
     * @param LineFailedMessage $failedMessage
     * @return bool สำเร็จหรือไม่
     */
    public function retry(LineFailedMessage $failedMessage): bool
    {
        // ตรวจสอบว่าควร retry หรือไม่
        if (!$failedMessage->shouldRetry()) {
            Log::warning('LINE Failed Message should not retry', [
                'id' => $failedMessage->id,
                'status' => $failedMessage->status,
                'retry_count' => $failedMessage->retry_count,
                'max_retries' => $failedMessage->max_retries,
            ]);

            // ถ้า retry เกินจำนวนครั้งแล้ว ทำเครื่องหมายเป็น abandoned
            if ($failedMessage->retry_count >= $failedMessage->max_retries) {
                $failedMessage->markAsAbandoned();

                // บันทึก error log
                LineErrorLog::log(
                    'retry_abandoned',
                    'Message abandoned after exceeding max retries',
                    LineErrorLog::SEVERITY_HIGH,
                    [
                        'failed_message_id' => $failedMessage->id,
                        'retry_count' => $failedMessage->retry_count,
                        'max_retries' => $failedMessage->max_retries,
                    ]
                );
            }

            return false;
        }

        // อัพเดทสถานะเป็น retrying
        $failedMessage->status = LineFailedMessage::STATUS_RETRYING;
        $failedMessage->save();

        try {
            // ลองส่งข้อความอีกครั้ง
            $success = $this->sendMessage(
                $failedMessage->line_user_id,
                $failedMessage->message_type,
                $failedMessage->message_payload
            );

            if ($success) {
                // ส่งสำเร็จ!
                $failedMessage->markAsSucceeded();

                // บันทึก recovery
                $errorLog = LineErrorLog::where('failed_message_id', $failedMessage->id)
                    ->where('is_recovered', false)
                    ->latest()
                    ->first();

                if ($errorLog) {
                    $errorLog->markAsResolved(
                        LineErrorLog::RECOVERY_AUTO_RETRY,
                        "Recovered after {$failedMessage->retry_count} retries"
                    );
                }

                Log::info('LINE Failed Message retry succeeded', [
                    'id' => $failedMessage->id,
                    'retry_count' => $failedMessage->retry_count,
                ]);

                return true;
            }

            // ส่งไม่สำเร็จ แต่ไม่มี exception (เช่น API คืน false)
            throw new Exception('Failed to send message (returned false)');

        } catch (Exception $e) {
            // ส่งล้มเหลว เพิ่มจำนวนครั้งและ schedule retry ครั้งถัดไป
            $failedMessage->incrementRetryCount();
            $failedMessage->recordError(
                LineFailedMessage::classifyException($e),
                $e->getMessage(),
                ['retry_attempt' => $failedMessage->retry_count]
            );

            // บันทึก error log
            $this->logError($e, $failedMessage->id, $failedMessage->line_user_id);

            // Schedule retry ครั้งถัดไป (ถ้ายังสามารถ retry ได้)
            if ($failedMessage->shouldRetry()) {
                $this->scheduleRetry($failedMessage);
            } else {
                // Retry เกินจำนวนครั้งแล้ว
                $failedMessage->markAsAbandoned();

                LineErrorLog::log(
                    'retry_failed',
                    'Message failed after maximum retries',
                    LineErrorLog::SEVERITY_CRITICAL,
                    [
                        'failed_message_id' => $failedMessage->id,
                        'final_error' => $e->getMessage(),
                    ]
                );
            }

            Log::warning('LINE Failed Message retry failed', [
                'id' => $failedMessage->id,
                'retry_count' => $failedMessage->retry_count,
                'max_retries' => $failedMessage->max_retries,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Schedule retry job สำหรับข้อความที่ล้มเหลว
     *
     * @param LineFailedMessage $failedMessage
     * @return void
     */
    protected function scheduleRetry(LineFailedMessage $failedMessage): void
    {
        // คำนวณเวลา delay ก่อน retry
        $delay = $failedMessage->next_retry_at ?? $failedMessage->calculateNextRetryTime();

        // Dispatch job ด้วย delay
        RetryFailedMessagesJob::dispatch($failedMessage->id)
            ->delay($delay);

        Log::info('LINE Failed Message retry scheduled', [
            'id' => $failedMessage->id,
            'retry_at' => $delay->toDateTimeString(),
            'delay_seconds' => now()->diffInSeconds($delay),
        ]);
    }

    /**
     * ส่งข้อความผ่าน LineService
     *
     * @param string $lineUserId
     * @param string $messageType
     * @param array $messagePayload
     * @return bool
     * @throws Exception
     */
    protected function sendMessage(string $lineUserId, string $messageType, array $messagePayload): bool
    {
        switch ($messageType) {
            case 'text':
                return $this->lineService->sendPushMessage(
                    $lineUserId,
                    $messagePayload['text'] ?? ''
                );

            case 'flex':
                return $this->lineService->sendFlexMessage(
                    $lineUserId,
                    $messagePayload
                );

            case 'template':
                return $this->lineService->sendTemplateMessage(
                    $lineUserId,
                    $messagePayload
                );

            case 'image':
                return $this->lineService->sendImageMessage(
                    $lineUserId,
                    $messagePayload['originalContentUrl'] ?? '',
                    $messagePayload['previewImageUrl'] ?? ''
                );

            default:
                throw new Exception("Unsupported message type: {$messageType}");
        }
    }

    /**
     * บันทึก error log
     *
     * @param Exception $exception
     * @param int|null $failedMessageId
     * @param string|null $lineUserId
     * @return LineErrorLog
     */
    protected function logError(
        Exception $exception,
        ?int $failedMessageId = null,
        ?string $lineUserId = null
    ): LineErrorLog {
        // จำแนกความรุนแรง
        $severity = $this->classifySeverity($exception);

        // สร้าง error log
        return LineErrorLog::createFromException(
            $exception,
            LineFailedMessage::classifyException($exception),
            $severity,
            null,
            $failedMessageId,
            $lineUserId
        );
    }

    /**
     * จำแนกความรุนแรงของ exception
     *
     * @param Exception $exception
     * @return string
     */
    protected function classifySeverity(Exception $exception): string
    {
        $message = strtolower($exception->getMessage());

        // Critical: ปัญหาที่ต้องแก้ไขทันที
        if (str_contains($message, 'invalid token') ||
            str_contains($message, 'unauthorized')) {
            return LineErrorLog::SEVERITY_CRITICAL;
        }

        // High: ปัญหาที่ร้ายแรงแต่อาจแก้ได้เอง
        if (str_contains($message, 'rate limit') ||
            str_contains($message, 'blocked')) {
            return LineErrorLog::SEVERITY_HIGH;
        }

        // Medium: ปัญหาปานกลาง (network, timeout)
        if (str_contains($message, 'network') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'connection')) {
            return LineErrorLog::SEVERITY_MEDIUM;
        }

        // Low: ปัญหาไม่ร้ายแรง
        return LineErrorLog::SEVERITY_LOW;
    }

    /**
     * Retry ข้อความทั้งหมดที่รอการ retry
     *
     * @param int $limit จำนวนข้อความสูงสุดที่จะ retry
     * @return int จำนวนข้อความที่ retry สำเร็จ
     */
    public function retryPendingMessages(int $limit = 100): int
    {
        $successCount = 0;

        // ดึงข้อความที่รอการ retry
        $pendingMessages = LineFailedMessage::pendingRetry()
            ->orderBy('next_retry_at')
            ->limit($limit)
            ->get();

        Log::info('LINE Auto-Retry: Processing pending messages', [
            'count' => $pendingMessages->count(),
            'limit' => $limit,
        ]);

        foreach ($pendingMessages as $failedMessage) {
            if ($this->retry($failedMessage)) {
                $successCount++;
            }

            // หน่วงเวลาเล็กน้อยเพื่อไม่ให้เกิน rate limit
            usleep(100000); // 0.1 second
        }

        Log::info('LINE Auto-Retry: Batch completed', [
            'processed' => $pendingMessages->count(),
            'succeeded' => $successCount,
            'failed' => $pendingMessages->count() - $successCount,
        ]);

        return $successCount;
    }

    /**
     * ดึงสถิติ retry
     *
     * @param int $days จำนวนวันย้อนหลัง
     * @return array
     */
    public function getRetryStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $total = LineFailedMessage::where('created_at', '>=', $startDate)->count();
        $succeeded = LineFailedMessage::where('created_at', '>=', $startDate)
            ->where('status', LineFailedMessage::STATUS_SUCCEEDED)
            ->count();
        $abandoned = LineFailedMessage::where('created_at', '>=', $startDate)
            ->where('status', LineFailedMessage::STATUS_ABANDONED)
            ->count();
        $pending = LineFailedMessage::where('created_at', '>=', $startDate)
            ->whereIn('status', [LineFailedMessage::STATUS_PENDING, LineFailedMessage::STATUS_FAILED])
            ->count();

        $avgRetryCount = LineFailedMessage::where('created_at', '>=', $startDate)
            ->where('status', LineFailedMessage::STATUS_SUCCEEDED)
            ->avg('retry_count');

        $byErrorType = LineFailedMessage::where('created_at', '>=', $startDate)
            ->selectRaw('error_type, count(*) as count')
            ->groupBy('error_type')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'error_type')
            ->toArray();

        return [
            'total' => $total,
            'succeeded' => $succeeded,
            'abandoned' => $abandoned,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($succeeded / $total) * 100, 2) : 0,
            'abandonment_rate' => $total > 0 ? round(($abandoned / $total) * 100, 2) : 0,
            'avg_retry_count' => round($avgRetryCount ?? 0, 2),
            'by_error_type' => $byErrorType,
        ];
    }

    /**
     * ล้างข้อความเก่าที่ resolve แล้ว
     *
     * @param int $days จำนวนวันที่จะเก็บ (default: 30)
     * @return int จำนวนที่ลบ
     */
    public function cleanupOldMessages(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);

        $count = LineFailedMessage::whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoffDate)
            ->delete();

        Log::info('LINE Auto-Retry: Cleaned up old messages', [
            'deleted' => $count,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return $count;
    }
}
