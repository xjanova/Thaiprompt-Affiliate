<?php

namespace App\Jobs;

use App\Models\FortuneCommentEngagement;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⏱️ (2026-11-09) ตอบใต้คอมเมนต์แบบหน่วงเวลา (คิวจาก CommentReplyPacer)
 *
 * แยกออกจาก webhook เพื่อไม่ให้เพจตอบคอมเมนต์ภายในไม่กี่วินาทีทุกอัน (ดูเป็นบอทชัด ๆ)
 * DM (Private Reply) ยังส่งทันทีที่ webhook — job นี้ทำแค่คำตอบสาธารณะ
 *
 * - page context ติดไปกับ payload ของคิวอัตโนมัติ (AppServiceProvider) ⇒ ได้ token ของสาขาที่ถูก
 * - ตอนรันเช็คสวิตช์ใหม่อีกรอบ — แอดมินปิดระหว่างรอคิว = ไม่ตอบ
 * - ตอบสำเร็จแล้วเท่านั้นถึงบันทึก comment_reply / จอง fcr:replied (replyToComment คืน false ไม่ throw)
 */
class SendFacebookCommentReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ไม่ลองซ้ำทั้ง job — ภายในลองซ้ำเองแล้ว 1 ครั้ง (ยิงซ้ำหลายรอบ = สัญญาณสแปม) */
    public $tries = 1;

    public $timeout = 60;

    /**
     * @param  array{comment_id:string, post_id:?string, user_id:string, comment_text:?string, reply:string}  $data
     */
    public function __construct(public array $data)
    {
        // เลนช้าเดียวกับ ProcessCommentEngagement — ห้ามแย่งคิวงานลูกค้าที่จ่ายเงิน
        $this->onQueue('tpix-low');
    }

    public function handle(): void
    {
        $commentId = (string) ($this->data['comment_id'] ?? '');
        $reply = trim((string) ($this->data['reply'] ?? ''));

        if ($commentId === '' || $reply === '') {
            return;
        }

        $replyOnceKey = 'fcr:replied:'.$commentId;
        if (Cache::has($replyOnceKey)) {
            return;
        }

        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();

        if (! $settings->isCommentEngagementEnabled() || ! $settings->isPublicCommentReplyEnabled()) {
            Log::info('⏱️ ตอบคอมเมนต์ (หน่วงเวลา): ข้าม — สวิตช์ถูกปิดระหว่างรอคิว', ['comment_id' => $commentId]);

            return;
        }

        $page = FortunePageContext::current();
        if ($page !== null && ! $page->allowsCommentReply(true)) {
            Log::info('⏱️ ตอบคอมเมนต์ (หน่วงเวลา): ข้าม — เพจนี้ปิดตอบคอมเมนต์', ['comment_id' => $commentId]);

            return;
        }

        // auto-deploy สั่ง cache:clear เกือบทุกวัน ⇒ fcr:replied อาจหาย — เช็คจาก DB ซ้ำอีกชั้น
        if (FortuneCommentEngagement::where('facebook_comment_id', $commentId)->whereNotNull('comment_reply')->exists()) {
            return;
        }

        // เพดานตอบต่อคนต่อวัน — เช็คตอนส่งจริง เพราะคำตอบที่ยังรอคิวยังไม่ถูกนับในตาราง
        // (คนเดียวคอมเมนต์รัว 10 อัน = จองคิวไว้ 10 อันก่อนอันแรกจะได้ส่ง)
        $userId = (string) ($this->data['user_id'] ?? '');
        if ($userId !== '' && ! FortuneCommentEngagement::hasPublicReplyQuota($userId, FortunePageContext::currentId())) {
            Log::info('⏱️ ตอบคอมเมนต์ (หน่วงเวลา): ข้าม — ลูกค้าคนนี้ได้คำตอบครบเพดานวันนี้แล้ว', ['comment_id' => $commentId]);

            return;
        }

        $facebookService = $this->makeFacebookService($settings);

        $sent = false;
        try {
            $sent = $facebookService->replyToComment($commentId, $reply);
            if (! $sent) {
                // FB 500 ชั่วคราว ⇒ ลองซ้ำ 1 ครั้ง (บทเรียน 2026-11-05)
                sleep(3);
                $sent = $facebookService->replyToComment($commentId, $reply);
            }
        } catch (Throwable $e) {
            Log::warning('⏱️ ตอบคอมเมนต์ (หน่วงเวลา) ล้ม', ['comment_id' => $commentId, 'error' => $e->getMessage()]);
        }

        if (! $sent) {
            return;
        }

        Cache::put($replyOnceKey, 1, 86400);

        try {
            $row = FortuneCommentEngagement::where('facebook_comment_id', $commentId)->first();
            if ($row) {
                $row->update(['comment_reply' => $reply]);
            } else {
                // DM ไม่ได้ส่ง (cooldown) ⇒ webhook ไม่ได้สร้างแถว — บันทึกไว้ให้ตัวนับเพดานรายคน + กันตอบซ้ำ
                FortuneCommentEngagement::create([
                    'facebook_user_id' => (string) ($this->data['user_id'] ?? ''),
                    'facebook_post_id' => $this->data['post_id'] ?? null,
                    'facebook_comment_id' => $commentId,
                    'comment_text' => (string) ($this->data['comment_text'] ?? ''),
                    'comment_reply' => $reply,
                    'dm_message' => null,
                    'engaged_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('⏱️ ตอบคอมเมนต์ (หน่วงเวลา): บันทึก engagement ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }

        Log::info('⏱️ ตอบคอมเมนต์ (หน่วงเวลา) สำเร็จ', ['comment_id' => $commentId]);
    }

    /**
     * สร้างตัวส่งข้อความ Facebook (แยกเป็นเมธอดให้เทสต์สลับเป็นตัวปลอมได้)
     */
    protected function makeFacebookService(FortuneTellingSetting $settings): FacebookWebhookService
    {
        return new FacebookWebhookService($settings);
    }
}
