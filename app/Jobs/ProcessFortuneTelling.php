<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Fortune Telling Job
 *
 * ประมวลผลคำทำนายแบบ asynchronous ด้วย Queue
 * - Retry logic with exponential backoff
 * - Error handling และ logging
 * - ส่งผลลัพธ์กลับ Facebook อัตโนมัติ
 * - รองรับ Freemium (basic/deep) พร้อม prompt template
 * - ส่ง typing indicator ระหว่างประมวลผล
 * - รองรับส่งรูปภาพกลับ
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
     *
     * Expected keys:
     * - facebook_user_id: string
     * - facebook_user_name: string|null
     * - questions: array
     * - reply_type: 'comment'|'message'
     * - comment_id: string|null
     * - post_id: string|null
     * - is_comment: bool
     * - is_deep: bool
     * - is_paid: bool
     * - amount_paid: float
     * - user_image_url: string|null
     * - birth_date: string|null (รูปแบบ Y-m-d)
     */
    protected array $data;

    /**
     * สร้าง job instance ใหม่
     *
     * @param  array  $data  ข้อมูลจาก Facebook webhook
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('tpix-default'); // ใช้ queue ที่มี worker อยู่แล้ว
    }

    /**
     * Execute the job.
     *
     * ประมวลผลคำทำนายและส่งผลกลับ
     *
     * @throws Exception
     */
    public function handle(FacebookWebhookService $facebookService): void
    {
        $startTime = microtime(true);
        $fromId = $this->data['facebook_user_id'] ?? null;
        $isComment = $this->data['is_comment'] ?? false;
        $isDeep = $this->data['is_deep'] ?? false;

        try {
            Log::info('🔮 Processing fortune telling job', [
                'facebook_user_id' => $fromId,
                'reading_type' => $isDeep ? 'deep' : 'basic',
                'attempt' => $this->attempts(),
            ]);

            // 🆕 (2026-05-07) สร้าง AI service พร้อม purpose ที่ตรง — pool จะเลือก key ที่ตรงประเภท
            //   เดิม: DI inject $aiService โดยไม่รู้ purpose → ได้ key ทั่วไป
            //   ใหม่: deep = 'prediction_deep' (Gemini 3.1 Pro), basic = 'free_card'
            // 🎯 (2026-05-18) Deep 39฿ ใช้ Gemini 3.1 Pro → purpose='prediction_deep'
            //   (scopeForPurpose hierarchy: prediction_deep → prediction → any → null)
            $aiService = new FortuneAIService(null, $isDeep ? 'prediction_deep' : 'free_card');

            // ส่ง typing indicator ขณะ AI กำลังประมวลผล (Messenger only)
            if (! $isComment && $fromId) {
                $facebookService->sendTypingIndicator($fromId);
            }

            // ดึงข้อมูลผู้ใช้จาก Facebook (optional)
            $userProfile = null;
            $userPosts = null;

            if ($fromId) {
                try {
                    $userProfile = $facebookService->getUserProfile($fromId);
                    // ดึงโพสล่าสุดเฉพาะคำทำนายเชิงลึก
                    $userPosts = $isDeep ? $facebookService->getUserPosts($fromId, 3) : null;
                } catch (Exception $e) {
                    // ไม่ throw error ถ้าดึงข้อมูล user ไม่ได้
                    Log::warning('Could not fetch user data', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // เลือก prompt template ตามระดับ
            $settings = FortuneTellingSetting::getSettings();
            $promptTemplate = $isDeep
                ? $settings->getDeepPromptTemplate()
                : $settings->getBasicPromptTemplate();

            // ใช้วันเกิดที่ผู้ใช้พิมพ์ หรือ fallback จาก Facebook profile
            $birthDate = $this->data['birth_date'] ?? null;
            if (empty($birthDate) && ! empty($userProfile['birth_date_formatted'])) {
                $birthDate = $userProfile['birth_date_formatted'];
                Log::info('ใช้วันเกิดจาก Facebook profile', ['birth_date' => $birthDate]);
            }

            // เรียก AI เพื่อทำนาย (ส่ง readingType เพื่อกำหนด maxTokens/temperature)
            $readingType = $isDeep ? 'deep' : 'basic';
            $aiResponse = $aiService->generateFortuneTelling(
                $this->data['questions'],
                $userProfile,
                $userPosts,
                $promptTemplate,
                $readingType,
                $birthDate
            );

            // บันทึกลงฐานข้อมูล
            $reading = $this->saveReading($aiResponse, $userProfile, $userPosts);

            // ปิด typing indicator
            if (! $isComment && $fromId) {
                $facebookService->sendTypingIndicator($fromId, false);
            }

            // ส่งผลกลับไปที่ Facebook
            $this->sendResponse($facebookService, $reading, $aiResponse['response']);

            // 🌙 (2026-05-23) Quick Replies dynamic ตาม toggle — Deep ปิด = ไม่โชว์ปุ่ม Deep
            $deepEnabledQR = $settings->isDeepReadingEnabled();
            $celticEnabledQR = (bool) ($settings->enable_celtic_cross ?? false);
            $celticPriceQR = 99;
            if ($celticEnabledQR) {
                try {
                    $celticPriceQR = (int) app(\App\Services\CelticCrossService::class)->getPrice();
                } catch (\Throwable $e) {
                }
            }

            // หลังส่งคำทำนายเชิงลึกฟรี ส่ง quick replies แนะนำสมัครสมาชิก
            if ($isDeep && $settings->isTryBeforeBuyEnabled() && ! $isComment && $fromId) {
                $tryBeforeBuyMsg = $settings->getTryBeforeBuyMessage();
                $tryQR = [
                    ['title' => 'ดูดวงอีกครั้ง', 'payload' => 'FORTUNE_BASIC'],
                ];
                if ($deepEnabledQR) {
                    $tryQR[] = ['title' => 'ดูดวงเชิงลึก', 'payload' => 'FORTUNE_DEEP'];
                } elseif ($celticEnabledQR) {
                    $tryQR[] = ['title' => "ดู vip ส่วนตัว {$celticPriceQR}บาท", 'payload' => 'TIER_CELTIC_99'];
                }
                $tryQR[] = ['title' => 'สมัครสมาชิก', 'payload' => 'SUBSCRIBE'];
                $facebookService->sendQuickReplies($fromId, $tryBeforeBuyMsg, $tryQR);
            }

            // ส่งข้อความชวนเพื่อนมาดูดวง (ทุกประเภท, เฉพาะ Messenger)
            if (! $isComment && $fromId) {
                $userName = $this->data['facebook_user_name'] ?? $userProfile['name'] ?? '';
                $referMsg = "✨ ชอบคำทำนายไหมคะ?\n";
                $referMsg .= "📲 ส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันสิคะ! แค่แชร์เพจเราให้เพื่อน แล้วบอกให้พิมพ์ \"ดูดวง\" ได้เลย 🔮\n";
                $referMsg .= '🌟 ยิ่งบอกวันเกิดด้วย ยิ่งแม่นค่ะ!';

                $referQR = [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวงอีก', 'payload' => 'FORTUNE_BASIC'],
                ];
                if ($deepEnabledQR) {
                    $referQR[] = ['content_type' => 'text', 'title' => '🌟 ดูดวงเชิงลึก', 'payload' => 'FORTUNE_DEEP'];
                } elseif ($celticEnabledQR) {
                    $referQR[] = ['content_type' => 'text', 'title' => "ดู vip ส่วนตัว {$celticPriceQR}บาท", 'payload' => 'TIER_CELTIC_99'];
                }
                $facebookService->sendQuickReplies($fromId, $referMsg, $referQR);
            }

            // Log success
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('✅ Fortune telling completed', [
                'reading_id' => $reading->id,
                'reading_type' => $isDeep ? 'deep' : 'basic',
                'duration_ms' => $duration,
                'tokens_used' => $aiResponse['tokens_used'] ?? 0,
                'provider' => $aiResponse['provider'] ?? 'unknown',
            ]);

        } catch (Exception $e) {
            // ปิด typing indicator ถ้า error
            if (! $isComment && $fromId) {
                $facebookService->sendTypingIndicator($fromId, false);
            }

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
     * @param  array  $aiResponse  ผลลัพธ์จาก AI
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  ข้อมูลโพสล่าสุดของผู้ใช้
     */
    protected function saveReading(array $aiResponse, ?array $userProfile, ?array $userPosts): FortuneReading
    {
        $isComment = $this->data['is_comment'] ?? false;
        $isDeep = $this->data['is_deep'] ?? false;
        $settings = FortuneTellingSetting::getSettings();

        return FortuneReading::create([
            'facebook_user_id' => $this->data['facebook_user_id'] ?? null,
            'facebook_user_name' => $this->data['facebook_user_name'] ?? $userProfile['name'] ?? 'Unknown',
            'facebook_comment_id' => $isComment ? ($this->data['comment_id'] ?? null) : null,
            'facebook_post_id' => $isComment ? ($this->data['post_id'] ?? null) : null,
            'user_id' => $this->data['user_id'] ?? null,
            'questions' => $this->data['questions'],
            'ai_provider' => $aiResponse['provider'],
            'ai_model' => $aiResponse['model'],
            'ai_response' => $aiResponse['response'],
            'tokens_used' => $aiResponse['tokens_used'] ?? 0,
            'user_profile' => $userProfile,
            'user_posts_context' => $userPosts,
            'response_type' => ($isComment && $settings->respond_in_comment) ? 'comment' : 'private_message',
            'reading_type' => $isDeep ? 'deep' : 'basic',
            'user_image_url' => $this->data['user_image_url'] ?? null,
            'is_paid' => $this->data['is_paid'] ?? false,
            'amount_paid' => $this->data['amount_paid'] ?? 0,
            'birth_date' => $this->data['birth_date'] ?? null,
        ]);
    }

    /**
     * ส่งผลการทำนายกลับไปที่ Facebook
     *
     * ใช้ sendFortuneTelling ของ FacebookWebhookService ซึ่งรองรับ:
     * - ข้อความยาว (auto-split)
     * - รูปภาพ (ถ้ามี reading_image_url)
     * - ตอบในคอมเมนต์หรือ Messenger ตามการตั้งค่า
     *
     * @param  string  $response  คำตอบจาก AI
     */
    protected function sendResponse(FacebookWebhookService $facebookService, FortuneReading $reading, string $response): void
    {
        if ($facebookService->sendFortuneTelling($reading, $response)) {
            $reading->markAsResponded();
        }
    }

    /**
     * ส่งข้อความ error กลับเมื่อ job ล้มเหลว
     */
    protected function sendErrorResponse(FacebookWebhookService $facebookService): void
    {
        $message = "😔 ขออภัย ขณะนี้ระบบมีปัญหา\n\n";
        $message .= 'กรุณาลองใหม่อีกครั้งในภายหลัง';

        try {
            $isComment = $this->data['is_comment'] ?? ($this->data['reply_type'] === 'comment');

            if ($isComment && isset($this->data['comment_id'])) {
                $facebookService->replyToComment($this->data['comment_id'], $message);
            } elseif (isset($this->data['facebook_user_id'])) {
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
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('🚨 Fortune telling job failed permanently', [
            'error' => $exception->getMessage(),
            'facebook_user_id' => $this->data['facebook_user_id'] ?? 'unknown',
            'reading_type' => ($this->data['is_deep'] ?? false) ? 'deep' : 'basic',
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * กำหนดชื่อของ job สำหรับ monitoring
     */
    public function displayName(): string
    {
        $userId = $this->data['facebook_user_id'] ?? 'unknown';
        $type = ($this->data['is_deep'] ?? false) ? 'deep' : 'basic';

        return "ProcessFortuneTelling[{$userId}:{$type}]";
    }

    /**
     * Tags สำหรับ job monitoring (Horizon)
     */
    public function tags(): array
    {
        return [
            'fortune-telling',
            'type:'.(($this->data['is_deep'] ?? false) ? 'deep' : 'basic'),
            'facebook:'.($this->data['facebook_user_id'] ?? 'unknown'),
        ];
    }
}
