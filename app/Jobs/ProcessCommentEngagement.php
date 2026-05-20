<?php

namespace App\Jobs;

use App\Models\FortuneCommentEngagement;
use App\Models\FortunePostReaction;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use App\Services\FortuneBannerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Comment Engagement Job
 *
 * เมื่อมีคนคอมเม้นต์ในโพสต์ (ไม่ใช่คำสั่งดูดวง) → AI สร้างข้อความชวนดูดวง
 * 1. ดึง user profile
 * 2. AI สร้างข้อความ (ตอบคอมเม้นต์ + ทัก inbox) — fallback ไป template ถ้า AI ล้ม
 * 3. ส่งตอบคอมเม้นต์
 * 4. ส่ง inbox + Quick Replies
 * 5. บันทึก engagement (เฉพาะเมื่อ DM ส่งสำเร็จ — เพื่อไม่ dedupe ลูกค้าที่ยังไม่ได้รับ DM)
 */
class ProcessCommentEngagement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 60;

    public $backoff = [10, 30];

    protected array $data;

    /**
     * @param  array  $data  Expected keys:
     *                       - facebook_user_id: string (PSID)
     *                       - facebook_post_id: string
     *                       - facebook_comment_id: string
     *                       - comment_text: string
     *                       - user_name: string|null
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('tpix-default'); // ใช้ queue ที่มี worker อยู่แล้ว
    }

    public function handle(): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            if (! $settings->isCommentEngagementEnabled()) {
                Log::info('Comment engagement ถูกปิดอยู่ ข้ามการประมวลผล');

                return;
            }

            $facebookService = new FacebookWebhookService($settings);
            $aiService = new FortuneAIService($settings);

            // ✅ validate required data (กัน missing keys → PHP warning/Error)
            $userId = $this->data['facebook_user_id'] ?? null;
            $commentId = $this->data['facebook_comment_id'] ?? null;
            $postId = $this->data['facebook_post_id'] ?? null;
            $commentText = $this->data['comment_text'] ?? '';

            if (empty($userId) || empty($commentId) || empty($postId)) {
                Log::warning('Comment engagement: missing required data', [
                    'has_user_id' => ! empty($userId),
                    'has_comment_id' => ! empty($commentId),
                    'has_post_id' => ! empty($postId),
                ]);

                return;
            }

            // ตรวจสอบซ้ำเฉพาะระดับ comment_id (กัน race condition จาก webhook retry)
            if (FortuneCommentEngagement::hasEngagedComment($commentId)) {
                Log::info('คอมเม้นต์ถูก engage ไปแล้ว (webhook retry)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

            // 📆 (2026-05-20) 3-day calendar cooldown — ขยายจาก 24h เดิม
            //    เคย DM (comment OR reaction) ใน 3 วันล่าสุด → ข้าม
            //    ลูกค้าเก่ารำคาญถ้า DM ทุกวัน → เว้น 3 วัน + เปลี่ยนคำทักทาย
            if (FortuneCommentEngagement::hasEngagedRecently($userId, 3)
                || FortunePostReaction::hasDmSuccessRecently($userId, 3)) {
                Log::info('Comment engagement skip — user ได้ DM ใน 3 วันล่าสุดแล้ว', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

            // 🔁 (2026-05-20) Returning-user detection — เปลี่ยนคำทักทายถ้าเคย DM แล้ว
            //   ผ่าน 3-day cooldown ข้างบน = สิทธิ์ทักใหม่ — แต่คำทักทายต้องไม่ซ้ำเดิม
            $isReturning = FortuneCommentEngagement::hasAnyEngagement($userId)
                || FortunePostReaction::hasEverDmSuccess($userId);

            // 1. ดึง user profile (ชื่อ, เพศ, วันเกิด ฯลฯ)
            $userProfile = $facebookService->getUserProfile($userId);
            if (! is_array($userProfile)) {
                $userProfile = [
                    'name' => $this->data['user_name'] ?? 'คุณ',
                    'id' => $userId,
                ];
            }
            $name = $userProfile['name'] ?? ($this->data['user_name'] ?? 'คุณ');

            // 👤 Persist name (จาก comment payload หรือ profile API) ลง FortuneUserCredit
            //   → flow ดูดวงครั้งต่อไปจะหาชื่อจริงเจอ ไม่ตก fallback "FACEBOOK-XXXXXX"
            \App\Models\FortuneUserCredit::rememberName($userId, 'facebook', $name);

            // 🌐 (2026-05-03) Detect locale จาก comment text + ชื่อ FB — ตอบภาษาเดียวกับที่ comment
            //   AI mirror ภาษา input อยู่แล้ว → set current() ก็พอ ไม่ต้องแตะ prompt
            //   ชื่อลาวล้วน → DM เป็นลาวแม้ comment สั้น/ใช้อิโมจิ
            $commentLocale = \App\Services\FortuneLocaleService::resolveForMessage(
                'facebook',
                $userId,
                $commentText,
                $name,
            );
            \App\Services\FortuneLocaleService::setCurrent($commentLocale);

            Log::info('Comment Engagement: กำลังสร้างข้อความชวนดูดวง', [
                'user_id' => $userId,
                'comment' => mb_substr($commentText, 0, 50),
                'has_profile' => ! empty($userProfile),
            ]);

            // 2. AI สร้างข้อความ — ถ้าล้ม → fallback เป็น template
            //    (AI rate-limited / key หมด / 429 ฯลฯ จะไม่ทำให้ลูกค้าเงียบ)
            //    🔁 (2026-05-20) ส่ง $isReturning เข้า AI เพื่อเปลี่ยน greeting คนเก่า
            try {
                $engagement = $aiService->generateCommentEngagement(
                    $commentText,
                    $userProfile,
                    null,
                    $isReturning
                );
                $commentReply = $engagement['comment_reply'] ?? '';
                $dmMessage = $engagement['dm_message'] ?? '';
            } catch (Throwable $aiError) {
                Log::warning('Comment Engagement: AI ล้ม → ใช้ template fallback', [
                    'user_id' => $userId,
                    'error' => $aiError->getMessage(),
                ]);
                $commentReply = '';
                $dmMessage = '';
            }

            // Guard: ถ้า AI คืน empty → ใช้ template แทน
            if (empty($commentReply) || empty($dmMessage)) {
                $commentReply = str_replace(
                    ['{name}', '{comment}'],
                    [$name, $commentText],
                    $settings->getCommentReplyTemplate($isReturning)
                );
                $dmMessage = str_replace(
                    ['{name}', '{comment}'],
                    [$name, $commentText],
                    // 🎯 Phase L — ส่ง userId เพื่อเลือก variant (stable per user)
                    // 🔁 (2026-05-20) ส่ง $isReturning สลับชุด first-time / returning
                    $settings->getCommentDmTemplate($userId, $isReturning)
                );
            }

            // 3. ตอบคอมเม้นต์ (best-effort — ถ้าล้มยังส่ง DM ต่อได้)
            try {
                $facebookService->replyToComment($commentId, $commentReply);
            } catch (Throwable $e) {
                Log::warning('replyToComment ล้ม (ยังส่ง DM ต่อ)', [
                    'comment_id' => $commentId,
                    'error' => $e->getMessage(),
                ]);
            }

            // 3.5 💗 กด LIKE คอมเม้นต์อัตโนมัติ — boost engagement signal ให้ FB algorithm
            //     (ไม่ block ถ้า fail — best-effort)
            try {
                $facebookService->reactToComment($commentId, 'LIKE');
            } catch (Throwable $e) {
                // non-blocking
                Log::debug('reactToComment LIKE ล้ม (non-blocking): '.$e->getMessage());
            }

            // 4. ส่ง inbox พร้อม Quick Replies
            // ส่ง comment_id เพื่อให้ใช้ Private Replies endpoint (bypass 24hr window
            // และแก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ)
            $quickReplies = [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '🌟 ดูดวงเชิงลึก', 'payload' => 'FORTUNE_DEEP'],
            ];

            // 🖼️ ส่งแบนเนอร์ก่อน text DM (ถ้าเปิดใน admin)
            // 🆕 (2026-05-07) ส่ง comment_id เพื่อใช้ Private Replies endpoint (bypass error 551)
            // 👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — skip ลูกค้าเก่า (ลด mass spam unreachable)
            try {
                $bannerService = new FortuneBannerService($settings);
                $bannerService->sendBannerThenWait(
                    fn ($url) => $facebookService->sendImage($userId, $url, null, ['comment_id' => $commentId]),
                    'comment',
                    'facebook',
                    $userId
                );
            } catch (Throwable $bannerErr) {
                Log::debug('Comment Engagement: banner send failed (non-blocking): '.$bannerErr->getMessage());
            }

            $dmSent = $facebookService->sendQuickReplies($userId, $dmMessage, $quickReplies, [
                'from_comment_engagement' => true,
                'comment_id' => $commentId,
            ]);

            // 5. บันทึก engagement เฉพาะเมื่อ DM ส่งสำเร็จ
            //    ถ้าส่งไม่สำเร็จ → ไม่ dedupe ลูกค้า ให้ retry ได้ในคอมเม้นต์ถัดไป
            if (! $dmSent) {
                Log::warning('❌ DM ไม่ได้ส่งถึงลูกค้า — ไม่สร้าง engagement record (allow retry)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

            // 5.5 👁️ Follow-page prompt — gated ที่ DB (cooldown 7 วัน + skip ถ้าติดตามแล้ว)
            //     ส่งหลัง DM main เพื่อโน้มน้าวให้กดติดตาม → รับดวงประจำวันทุกวัน
            $facebookService->sendFollowPagePromptToUser($userId);

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

            Log::info('✅ Comment Engagement สำเร็จ', [
                'user_id' => $userId,
                'post_id' => $postId,
                'comment_id' => $commentId,
            ]);

        } catch (Throwable $e) {
            // ใช้ Throwable (ไม่ใช่ Exception) เพื่อจับ TypeError, Error ด้วย
            Log::error('Comment Engagement Error: '.$e->getMessage(), [
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
