<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Services\FortuneAIService;
use App\Services\FacebookWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Process Fortune Telling Job
 *
 * ประมวลผลคำทำนายแบบ asynchronous ด้วย Queue
 * - Retry logic with exponential backoff
 * - Error handling และ logging
 * - ส่งผลลัพธ์กลับ Facebook อัตโนมัติ
 */
class ProcessFortuneTelling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * จำนวนครั้งที่ลองใหม่สูงสุด
     *
     * @var int
     */
    public $tries = 3;

    /**
     * จำนวนวินาทีก่อนที่ job จะ timeout
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * ระยะเวลา backoff (วินาที) ระหว่างการ retry
     * Exponential backoff: 10s, 30s, 90s
     *
     * @var array
     */
    public $backoff = [10, 30, 90];

    /**
     * ข้อมูลที่ใช้ในการทำนาย
     */
    protected array $data;

    /**
     * สร้าง job instance ใหม่
     *
     * @param array $data ข้อมูลจาก Facebook webhook
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('fortune-telling'); // ใช้ queue แยก
    }

    /**
     * Execute the job.
     *
     * ประมวลผลคำทำนายและส่งผลกลับ
     *
     * @param FortuneAIService $aiService
     * @param FacebookWebhookService $facebookService
     * @return void
     * @throws Exception
     */
    public function handle(FortuneAIService $aiService, FacebookWebhookService $facebookService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('🔮 Processing fortune telling job', [
                'facebook_user_id' => $this->data['facebook_user_id'] ?? null,
                'attempt' => $this->attempts(),
            ]);

            // ดึงข้อมูลผู้ใช้จาก Facebook (optional)
            $userProfile = null;
            $userPosts = null;

            if (isset($this->data['facebook_user_id'])) {
                try {
                    $userProfile = $facebookService->getUserProfile($this->data['facebook_user_id']);
                    $userPosts = $facebookService->getUserPosts($this->data['facebook_user_id']);
                } catch (Exception $e) {
                    // ไม่ throw error ถ้าดึงข้อมูล user ไม่ได้
                    Log::warning('Could not fetch user data', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // เรียก AI เพื่อทำนาย
            $aiResponse = $aiService->generateFortuneTelling(
                $this->data['questions'],
                $userProfile,
                $userPosts
            );

            // บันทึกลงฐานข้อมูล
            $reading = $this->saveReading($aiResponse);

            // ส่งผลกลับไปที่ Facebook
            $this->sendResponse($facebookService, $reading);

            // Log success
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('✅ Fortune telling completed', [
                'reading_id' => $reading->id,
                'duration_ms' => $duration,
                'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                'provider' => $aiResponse['provider'] ?? 'unknown',
            ]);

        } catch (Exception $e) {
            // Log error
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('❌ Fortune telling job failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
            ]);

            // ถ้า retry หมดแล้ว ส่งข้อความ error กลับ
            if ($this->attempts() >= $this->tries) {
                $this->sendErrorResponse($facebookService);
            }

            // Throw exception เพื่อให้ queue retry
            throw $e;
        }
    }

    /**
     * บันทึกผลการทำนายลงฐานข้อมูล
     *
     * @param array $aiResponse
     * @return FortuneReading
     */
    protected function saveReading(array $aiResponse): FortuneReading
    {
        return FortuneReading::create([
            'facebook_user_id' => $this->data['facebook_user_id'] ?? null,
            'facebook_user_name' => $this->data['facebook_user_name'] ?? 'Unknown',
            'user_id' => $this->data['user_id'] ?? null,
            'questions' => $this->data['questions'],
            'ai_provider' => $aiResponse['provider'],
            'ai_model' => $aiResponse['model'],
            'ai_response' => $aiResponse['response'],
            'tokens_used' => $aiResponse['tokens_used'] ?? 0,
            'is_paid' => $this->data['is_paid'] ?? false,
            'amount_paid' => $this->data['amount_paid'] ?? 0,
            'metadata' => [
                'ip_address' => $this->data['ip_address'] ?? null,
                'user_agent' => $this->data['user_agent'] ?? null,
                'processing_time_ms' => $aiResponse['processing_time_ms'] ?? 0,
            ],
        ]);
    }

    /**
     * ส่งผลการทำนายกลับไปที่ Facebook
     *
     * @param FacebookWebhookService $facebookService
     * @param FortuneReading $reading
     * @return void
     */
    protected function sendResponse(FacebookWebhookService $facebookService, FortuneReading $reading): void
    {
        $message = "🔮 **การทำนายของคุณ**\n\n";
        $message .= $reading->ai_response;
        $message .= "\n\n✨ ขอให้โชคดี!";

        // ส่ง message กลับ
        if ($this->data['reply_type'] === 'comment' && isset($this->data['comment_id'])) {
            $facebookService->replyToComment($this->data['comment_id'], $message);
        } elseif ($this->data['reply_type'] === 'message' && isset($this->data['facebook_user_id'])) {
            $facebookService->sendMessage($this->data['facebook_user_id'], $message);
        }
    }

    /**
     * ส่งข้อความ error กลับเมื่อ job ล้มเหลว
     *
     * @param FacebookWebhookService $facebookService
     * @return void
     */
    protected function sendErrorResponse(FacebookWebhookService $facebookService): void
    {
        $message = "😔 ขออภัย ขณะนี้ระบบมีปัญหา\n\n";
        $message .= "กรุณาลองใหม่อีกครั้งในภายหลัง";

        try {
            if ($this->data['reply_type'] === 'comment' && isset($this->data['comment_id'])) {
                $facebookService->replyToComment($this->data['comment_id'], $message);
            } elseif ($this->data['reply_type'] === 'message' && isset($this->data['facebook_user_id'])) {
                $facebookService->sendMessage($this->data['facebook_user_id'], $message);
            }
        } catch (Exception $e) {
            Log::error('Failed to send error message', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * จัดการเมื่อ job ล้มเหลว (หลัง retry หมดทุกครั้ง)
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::critical('🚨 Fortune telling job failed permanently', [
            'error' => $exception->getMessage(),
            'data' => $this->data,
            'attempts' => $this->attempts(),
        ]);

        // TODO: ส่ง notification ไป admin
        // TODO: บันทึก failed job ลงฐานข้อมูล
    }

    /**
     * กำหนดชื่อของ job สำหรับ monitoring
     *
     * @return string
     */
    public function displayName(): string
    {
        $userId = $this->data['facebook_user_id'] ?? 'unknown';
        return "ProcessFortuneTelling[{$userId}]";
    }

    /**
     * Tags สำหรับ job monitoring (Horizon)
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'fortune-telling',
            'facebook:' . ($this->data['facebook_user_id'] ?? 'unknown'),
            'provider:' . ($this->data['ai_provider'] ?? 'default'),
        ];
    }
}
