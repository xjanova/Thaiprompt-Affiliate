<?php

namespace App\Jobs;

use App\Models\FortuneCommentEngagement;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use App\Services\FacebookWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

/**
 * Process Comment Engagement Job
 *
 * เมื่อมีคนคอมเม้นต์ในโพสต์ (ไม่ใช่คำสั่งดูดวง) → AI สร้างข้อความชวนดูดวง
 * 1. ดึง user profile
 * 2. AI สร้างข้อความ (ตอบคอมเม้นต์ + ทัก inbox)
 * 3. ส่งตอบคอมเม้นต์
 * 4. ส่ง inbox + Quick Replies
 * 5. บันทึก engagement
 */
class ProcessCommentEngagement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;
    public $backoff = [10, 30];

    protected array $data;

    /**
     * @param array $data Expected keys:
     *   - facebook_user_id: string (PSID)
     *   - facebook_post_id: string
     *   - facebook_comment_id: string
     *   - comment_text: string
     *   - user_name: string|null
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('fortune-telling');
    }

    public function handle(): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (!$settings->isCommentEngagementEnabled()) {
                Log::info('Comment engagement ถูกปิดอยู่ ข้ามการประมวลผล');
                return;
            }

            $facebookService = new FacebookWebhookService($settings);
            $aiService = new FortuneAIService($settings);

            $userId = $this->data['facebook_user_id'];
            $commentId = $this->data['facebook_comment_id'];
            $postId = $this->data['facebook_post_id'];
            $commentText = $this->data['comment_text'] ?? '';

            // ตรวจสอบซ้ำอีกครั้ง (กัน race condition)
            if (FortuneCommentEngagement::hasEngaged($userId, $postId)) {
                Log::info('User เคยถูก engage ในโพสต์นี้แล้ว', [
                    'user_id' => $userId,
                    'post_id' => $postId,
                ]);
                return;
            }

            // 1. ดึง user profile (ชื่อ, เพศ, วันเกิด ฯลฯ)
            $userProfile = $facebookService->getUserProfile($userId);

            Log::info('Comment Engagement: กำลังสร้างข้อความชวนดูดวง', [
                'user_id' => $userId,
                'comment' => mb_substr($commentText, 0, 50),
                'has_profile' => !empty($userProfile),
            ]);

            // 2. AI สร้างข้อความ
            $engagement = $aiService->generateCommentEngagement(
                $commentText,
                $userProfile
            );

            $commentReply = $engagement['comment_reply'];
            $dmMessage = $engagement['dm_message'];

            // 3. ตอบคอมเม้นต์
            $facebookService->replyToComment($commentId, $commentReply);

            // 4. ส่ง inbox พร้อม Quick Replies
            $quickReplies = [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '🌟 ดูดวงละเอียด', 'payload' => 'FORTUNE_DEEP'],
            ];
            $facebookService->sendQuickReplies($userId, $dmMessage, $quickReplies);

            // 5. บันทึก engagement
            FortuneCommentEngagement::create([
                'facebook_user_id' => $userId,
                'facebook_post_id' => $postId,
                'facebook_comment_id' => $commentId,
                'comment_text' => $commentText,
                'comment_reply' => $commentReply,
                'dm_message' => $dmMessage,
                'user_profile' => $userProfile,
                'engaged_at' => now(),
            ]);

            Log::info('Comment Engagement สำเร็จ', [
                'user_id' => $userId,
                'post_id' => $postId,
                'comment_id' => $commentId,
            ]);

        } catch (Exception $e) {
            Log::error('Comment Engagement Error: ' . $e->getMessage(), [
                'data' => $this->data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * จัดการเมื่อ job fail ทุก retry
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Comment Engagement Job FAILED (all retries exhausted)', [
            'data' => $this->data,
            'error' => $exception->getMessage(),
        ]);
    }
}
