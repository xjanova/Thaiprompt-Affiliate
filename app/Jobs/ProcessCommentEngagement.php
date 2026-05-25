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

            // 📆 (2026-05-21) 24h rolling cooldown — reverted จาก 3-day (คนเงียบเลย)
            //    เคย DM (comment OR reaction) ใน 24 ชม. ล่าสุด → ข้าม
            if (FortuneCommentEngagement::hasEngagedRecently($userId, 24)
                || FortunePostReaction::hasDmSuccessRecently($userId, 24)) {
                Log::info('Comment engagement skip — user ได้ DM ใน 24 ชม. ล่าสุดแล้ว', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

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

            // 🚫 (2026-05-24) Sub-toggle: เปิดตอบคอมเม้นต์สาธารณะหรือไม่?
            //    User: "บอท api ตอบโพสแต่ไม่ได้โพสจริง (app ยังไม่ให้โพส) ทำให้เสีย quota
            //          แค่ DM อย่างเดียวพอ"
            //    เมื่อ false (default): skip pattern match + AI gen + replyToComment + reactToComment
            //                          ส่ง DM อย่างเดียว (Page Messaging — scope แยก ไม่กระทบ)
            //    เมื่อ true: ทำงานครบ — สำหรับเมื่อ App Review approved pages_manage_engagement แล้ว
            $publicReplyEnabled = $settings->isPublicCommentReplyEnabled();
            $commentReply = '';

            if ($publicReplyEnabled) {
                // 2. ⚡ (2026-05-23) Phase 2: ลอง match common gratitude pattern ก่อน — skip AI ถ้าตรง
                //    pattern matcher → "สาธุ/น้อมรับ/รับ/ขอบคุณ/🙏/✨" (Thai + Lao + emoji-only)
                //    Hit rate คาดหวัง: 40-60% ของ comment ทั่วไป (ลูกค้ามักทักสั้นๆ)
                //    Speedup: AI call 2-3s → matcher 1-2ms = ~3× throughput per worker
                $commentReply = $this->matchCommonGratitudePattern($commentText, $name);
                $usedTemplate = $commentReply !== null;

                if ($usedTemplate) {
                    Log::info('⚡ Comment Engagement: matched common pattern (skip AI)', [
                        'user_id' => $userId,
                        'comment_preview' => mb_substr($commentText, 0, 30),
                    ]);
                } else {
                    // 2b. AI สร้าง comment_reply (context-aware) — comment ไม่ตรง pattern
                    //    🌙 (2026-05-21) DM message เปลี่ยนเป็น "ดวงประจำวันสั้นๆ" (deterministic)
                    //    AI ไม่ generate dm_message อีกต่อไป — ใช้ FortuneGreetingService แทน
                    try {
                        $engagement = $aiService->generateCommentEngagement(
                            $commentText,
                            $userProfile
                        );
                        $commentReply = $engagement['comment_reply'] ?? '';
                    } catch (Throwable $aiError) {
                        Log::warning('Comment Engagement: AI ล้ม → ใช้ template fallback', [
                            'user_id' => $userId,
                            'error' => $aiError->getMessage(),
                        ]);
                        $commentReply = '';
                    }
                }

                // Comment reply fallback ถ้า AI คืน empty
                if (empty($commentReply)) {
                    $commentReply = str_replace(
                        ['{name}', '{comment}'],
                        [$name, $commentText],
                        $settings->getCommentReplyTemplate()
                    );
                }
            } else {
                Log::info('🚫 Comment Engagement: public reply ปิดอยู่ — skip AI + replyToComment (DM ปกติ)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);
            }

            // 🌙 DM message — ดวงประจำวันสั้นๆ (มีวันเกิด → ดวงวันเกิด, ไม่มี → ชวนดูดวง)
            $greetingService = app(\App\Services\Fortune\FortuneGreetingService::class);
            $dmMessage = $greetingService->buildDailyHoroscopeGreeting($userId, $name);

            // 3. ตอบคอมเม้นต์ (best-effort — ถ้าล้มยังส่ง DM ต่อได้)
            //    🚫 (2026-05-24) Skip ทั้งหมดถ้า public reply ปิดอยู่ (กัน FB API call เปล่า)
            if ($publicReplyEnabled && ! empty($commentReply)) {
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
            }

            // 4. ส่ง inbox พร้อม Quick Replies
            // ส่ง comment_id เพื่อให้ใช้ Private Replies endpoint (bypass 24hr window
            // และแก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ)
            // 🌙 (2026-05-23) Deep ปิด = ไม่โชว์ปุ่ม Deep — swap เป็น Celtic ถ้าเปิด
            $deepEnabledQR = $settings->isDeepReadingEnabled();
            $celticEnabledQR = (bool) ($settings->enable_celtic_cross ?? false);
            $quickReplies = [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ];
            if ($deepEnabledQR) {
                $quickReplies[] = ['content_type' => 'text', 'title' => '🌟 ดูดวงเชิงลึก', 'payload' => 'FORTUNE_DEEP'];
            } elseif ($celticEnabledQR) {
                $celticPriceQR = 99;
                try {
                    $celticPriceQR = (int) app(\App\Services\CelticCrossService::class)->getPrice();
                } catch (\Throwable $e) {
                }
                $quickReplies[] = ['content_type' => 'text', 'title' => "🔮 ไพ่ 10 ใบ {$celticPriceQR}฿", 'payload' => 'TIER_CELTIC_99'];
            }

            // 🎯 (2026-05-25 v2) F+G Strategy — แก้ race condition FB Reels Private Reply
            //   เดิม v1: ส่งภาพ → 800ms → text+QR → race condition #10900 91% fail
            //   ใหม่ v2: text+QR ก่อน (CTA สำคัญสุด!) → ภาพตามทีหลัง best effort
            //           ถ้า text+QR ล้ม → fallback Option G (image+QR atomic single call)
            //           ถ้า Option G ล้ม → fallback Option H (image only — last resort)
            //
            //   เหตุผล: Quick Replies = "🔮 ไพ่ 10 ใบ 99฿" / "🌟 ดูดวงเชิงลึก" = CTA conversion
            //          ลูกค้าเสียภาพ banner = แค่ decoration. ลูกค้าเสีย CTA = สูญ conversion
            $stage1TextSent = false; // Option F primary: text+QR
            $stage2gImageQrSent = false; // Option G fallback: image+QR atomic
            $stage3ImageOnlySent = false; // Option H last resort: image only
            $bannerAfterTextSent = false; // Bonus: image หลัง text สำเร็จแล้ว

            // STAGE 1 (Option F primary) — text + Quick Replies ก่อน
            //   ผ่าน sendQuickReplies → sendPrivateReply Send API + recipient.comment_id
            $stage1TextSent = $facebookService->sendQuickReplies($userId, $dmMessage, $quickReplies, [
                'from_comment_engagement' => true,
                'comment_id' => $commentId,
            ]);

            if ($stage1TextSent) {
                // Stage 1 สำเร็จ → ส่งภาพตามหลัง 800ms (bonus, best effort — fail #10900 = ปกติ)
                usleep(800000);
                try {
                    $bannerService = new FortuneBannerService($settings);
                    $bannerAfterTextSent = (bool) $bannerService->sendBannerThenWait(
                        fn ($url) => $facebookService->sendImage($userId, $url, null, ['comment_id' => $commentId]),
                        'comment',
                        'facebook',
                        $userId
                    );
                } catch (Throwable $bannerErr) {
                    Log::debug('Comment Engagement: banner-after-text ล้ม (non-blocking): '.$bannerErr->getMessage());
                }
            } else {
                // STAGE 2 (Option G fallback) — image + QR ใน single call atomic
                //   ใช้ pattern: sendBannerThenWait + callback ใช้ new method
                Log::info('🔁 Comment Engagement: Stage 1 (text+QR) ล้ม → ลอง Stage 2 Option G (image+QR atomic)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);

                try {
                    $bannerService = new FortuneBannerService($settings);
                    $stage2gImageQrSent = (bool) $bannerService->sendBannerThenWait(
                        fn ($url) => $facebookService->sendPrivateReplyImageWithQuickReplies(
                            $commentId,
                            $url,
                            $quickReplies
                        ),
                        'comment',
                        'facebook',
                        $userId
                    );
                } catch (Throwable $gErr) {
                    Log::debug('Comment Engagement: Stage 2 (Option G) ล้ม (non-blocking): '.$gErr->getMessage());
                }

                // STAGE 3 (Option H last resort) — image only (ภาพอย่างเดียว ไม่มี QR)
                //   เกิดเฉพาะเมื่อ Stage 1 + Stage 2 ล้มทั้งคู่ — ลูกค้าได้อย่างน้อยภาพ banner
                if (! $stage2gImageQrSent) {
                    Log::info('🔁 Comment Engagement: Stage 2 (Option G) ล้ม → ลอง Stage 3 (image only last resort)', [
                        'user_id' => $userId,
                        'comment_id' => $commentId,
                    ]);

                    try {
                        $bannerService = new FortuneBannerService($settings);
                        $stage3ImageOnlySent = (bool) $bannerService->sendBannerThenWait(
                            fn ($url) => $facebookService->sendImage($userId, $url, null, ['comment_id' => $commentId]),
                            'comment',
                            'facebook',
                            $userId
                        );
                    } catch (Throwable $hErr) {
                        Log::debug('Comment Engagement: Stage 3 (image only) ล้ม (non-blocking): '.$hErr->getMessage());
                    }
                }
            }

            // map สำหรับ engagement record logic (compat กับเดิม)
            $dmSent = $stage1TextSent || $stage2gImageQrSent; // ลูกค้าได้ CTA แบบใดแบบหนึ่ง
            $bannerSent = $bannerAfterTextSent || $stage2gImageQrSent || $stage3ImageOnlySent; // ลูกค้าได้ภาพแบบใดแบบหนึ่ง

            // 5. บันทึก engagement ถ้าอย่างน้อย stage ใด stage หนึ่งสำเร็จ
            //    ❌ retry เฉพาะกรณี fail ทั้ง 3 stages (FB privacy block ฯลฯ)
            if (! $dmSent && ! $bannerSent) {
                Log::warning('❌ DM ไม่ได้ส่งถึงลูกค้า (ทุก stage fail) — ไม่สร้าง engagement record (allow retry)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                    'stages' => 'F-fail / G-fail / H-fail',
                ]);

                return;
            }

            // Log แยกตาม path success
            if ($stage1TextSent && $bannerAfterTextSent) {
                Log::info('✨ Comment Engagement: FULL success (text+QR + image bonus)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);
            } elseif ($stage1TextSent && ! $bannerAfterTextSent) {
                Log::info('💬 Comment Engagement: text+QR สำเร็จ (CTA ครบ — ภาพ skip/fail ยอมรับได้)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);
            } elseif ($stage2gImageQrSent) {
                Log::info('📸 Comment Engagement: Option G สำเร็จ (image+QR atomic — text verbose body หายแต่ CTA ครบ)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);
            } elseif ($stage3ImageOnlySent) {
                Log::info('🖼️ Comment Engagement: Stage 3 image-only last resort (CTA หาย — รอ Quick Replies recover ตอน user ตอบ)', [
                    'user_id' => $userId,
                    'comment_id' => $commentId,
                ]);
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

    /**
     * ⚡ (2026-05-23) Phase 2 — Common gratitude pattern matcher
     *
     * ลูกค้าส่วนใหญ่ comment สั้น ("สาธุ", "🙏", "น้อมรับ", "รับค่ะ") — ซ้ำซากมาก
     * → ไม่จำเป็นต้องใช้ AI gen (เปลือง 2-3s + token cost)
     * → match pattern → ใช้ canned reply ที่มี variation 4-6 แบบ (ดูไม่ spam)
     *
     * @param  string  $commentText  ข้อความคอมเม้นต์ดิบ
     * @param  string  $name         ชื่อลูกค้า (มาจาก FB profile)
     * @return string|null  reply ถ้า match, null ถ้าไม่ตรง (caller จะ fallback ไป AI)
     */
    protected function matchCommonGratitudePattern(string $commentText, string $name): ?string
    {
        $normalized = $this->normalizeForPatternMatch($commentText);

        // ข้อความเปล่า (เป็น emoji ล้วน) → handle ด้วย emoji branch ด้านล่าง
        // ข้อความยาวเกิน 25 ตัว → น่าจะเป็นคำถาม/คำสั่ง ไม่ match
        if (mb_strlen($normalized) > 25) {
            return null;
        }

        $isLao = \App\Services\FortuneLocaleService::current() === \App\Services\FortuneLocaleService::LOCALE_LO;

        // 🙏 Blessing/gratitude patterns (Thai + Lao) — v2 expanded (2026-05-23)
        //   เพิ่ม manifestation/wish keywords ที่ลูกค้า comment "claim" พรของโพสต์
        $blessingPatterns = [
            // Thai — สาธุ family (มี dot variant: "สาธุ.สาธุ.สาธุ" หลัง normalize → "สาธุสาธุสาธุ")
            '/^(สาธุ|สาทุ|สาธู|สาธุๆ){1,5}(ค่ะ|ครับ|คะ|นะ|ๆ)*$/u',
            // Thai — น้อมรับ family
            '/^(ขอ)?น้อมรับ(ค่ะ|ครับ|คะ|พลัง|พร|คำทำนาย|สิ่งดีๆ|พลังบวก|ๆ|\s)*$/u',
            // Thai — รับ family (รวม "รับพลัง", "รับโชค", "รับเงิน", "รับทรัพย์", "รับโชคลาภ")
            '/^รับ(ค่ะ|ครับ|คะ|นะ|เลย|พลัง|พลังบวก|โชค|โชคลาภ|ลาภ|เงิน|ทรัพย์|พร|ความรวย|รวย|ความสุข|ๆ)*$/u',
            // Thai — ขอบคุณ family
            '/^ขอบคุณ(ค่ะ|ครับ|คะ|นะ|มาก|มากๆ|มากค่ะ|มากครับ|จ้า)*$/u',
            // Thai — อาเมน / amen
            '/^(อาเมน|อามีน|amen)$/iu',
            // Thai — ฟ้าเปิด / ฟ้าเปิดทาง / พ้นเคราะห์
            '/^ฟ้าเปิด(ทาง|ค่ะ|ครับ|แล้ว|นะ|ๆ)*$/u',
            '/^พ้น(เคราะห์|ทุกข์|ภัย|กรรม)(ค่ะ|ครับ|ๆ)*$/u',
            // Thai — ดวงพุ่ง/ดวงดี
            '/^ดวง(พุ่ง|ดี|เปิด|เฮง|รุ่ง|ขึ้น)(แรง|ค่ะ|ครับ|มาก|มากๆ|ๆ)*$/u',
            // Thai — เปลี่ยน (ตอบโพสต์ "ใครอยากเปลี่ยนชีวิตคอมเม้นเปลี่ยน")
            '/^เปลี่ยน(ชีวิต|ค่ะ|ครับ|คะ|นะ|ๆ)*$/u',
            // Thai — สวัสดี / ทักทาย
            '/^(สวัสดี|หวัดดี|hi|hello)(ค่ะ|ครับ|คะ|จ้า)?$/iu',
            // Thai — manifestation: เติบโต/เจริญ/รุ่งเรือง
            '/^(เติบโต|เติมโต|เจริญ|เจริญรุ่งเรือง|เจริญก้าวหน้า|รุ่งเรือง|รุ่ง|ก้าวหน้า|ก้าวหน้าๆ)(ค่ะ|ครับ|นะ|ๆ)*$/u',
            // Thai — ปลดหนี้/หมดหนี้/พ้นหนี้
            '/^(ปลด|หมด|พ้น|ใช้)หนี้(สิน|ค่ะ|ครับ|ๆ)*$/u',
            // Thai — ความรวย/รวย/ลาภ
            '/^(รวย|ความรวย|รวยๆ|ร่ำรวย|ลาภลอย|โชคลาภ|มั่งคั่ง|มั่งมี)(ค่ะ|ครับ|นะ|ๆ)*$/u',
            // Thai — สมหวัง / สมปรารถนา
            '/^สม(หวัง|ปรารถนา|ใจ|ความปรารถนา)(ค่ะ|ครับ|นะ|ทุกประการ|ๆ)*$/u',
            // Thai — หมดเวร / หมดกรรม
            '/^หมด(เวร|กรรม|เวรกรรม|ทุกข์)(ค่ะ|ครับ|ๆ)*$/u',
            // Thai — เทวดา / สิ่งศักดิ์สิทธิ์
            '/^(เทวดา|เทพ|พระ|สิ่งศักดิ์สิทธิ์)?(ช่วย|คุ้มครอง|ปกปัก|ปกป้อง|รักษา)(ค่ะ|ครับ|ด้วย|นะ|ๆ)*$/u',
            // Thai — รักแท้ / เนื้อคู่
            '/^(รักแท้|เนื้อคู่|คู่แท้|คู่ครอง|ความรัก)(ค่ะ|ครับ|นะ|ๆ)*$/u',
            // Thai — สุขภาพดี / แข็งแรง
            '/^(สุขภาพ|แข็งแรง|สุขภาพดี|แข็งแรงสมบูรณ์)(ค่ะ|ครับ|นะ|ๆ)*$/u',
            // Thai — ขอพร / ขอโชค
            '/^ขอ(พร|โชค|ลาภ|พลัง|บุญ|กุศล|พลังบวก)(ค่ะ|ครับ|นะ|ๆ)*$/u',
            // Thai — โชคดี / ฟลุก / เฮง
            '/^(โชคดี|เฮง|เฮงๆ|ฟลุก|มงคล)(ค่ะ|ครับ|นะ|ๆ)*$/u',

            // Lao — ສາທຸ family (รวม dot variant)
            '/^(ສາທຸ|ສາທູ){1,5}(ເດີ|ໆ)*$/u',
            // Lao — ນ້ອມຮັບ
            '/^ນ້ອມຮັບ(ເດີ|ໆ|ພະລັງ|ຄຳທຳນາຍ|ພອນ)*$/u',
            // Lao — ຮັບ
            '/^ຮັບ(ເດີ|ໆ|ພະລັງ|ໂຊກ|ເງິນ|ຄວາມຮັ່ງມີ)*$/u',
            // Lao — ຂອບໃຈ
            '/^ຂອບໃຈ(ເດີ|ຫຼາຍ|ໆ)*$/u',
            // Lao — ລັບເງີນ
            '/^ລັບເງີນ(ສາທຸ|ສາທູ|ເດີ)*$/u',
            // Lao — ດວງພຸ່ງ/ດວງດີ (รวม ດວງພຸ້ງ ที่เป็น typo tone-mark)
            '/^ດວງ(ພຸ່ງ|ພຸ້ງ|ດີ|ເປີດ|ເຮັງ|ຮຸ່ງ)(ແຮງ|ໆ|ສາທຸ)*$/u',
            // Lao — ສົມຫວັງ / ສົມຫ້ວງ (variant spelling)
            '/^ສົມ(ຫວັງ|ຫ້ວງ|ຄວາມປາຖະໜາ)(ສາທຸ|ສາທູ|ໆ)*$/u',
            // Lao — ພົ້ນເຄາະ
            '/^ພົ້ນ(ເຄາະ|ທຸກ|ໄພ|ກຳ)(ຟ້າເປີດ|ສາທຸ|ໆ)*$/u',
            // Lao — ລວຍ
            '/^(ລວຍ|ຄວາມຮັ່ງມີ|ຮັ່ງມີ)(ໆ|ສາທຸ)*$/u',
            // Lao — ແມ່ນ (= ใช่/yes — ลูกค้า claim พร)
            '/^ແມ່ນ(ແລ້ວ|ເດີ|ໆ)*$/u',
        ];

        foreach ($blessingPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return $this->pickCannedReply($name, $isLao, 'blessing');
            }
        }

        // 💖 Emoji-only or emoji+short — single sentiment expression
        //   เช็คทั้ง original (มี emoji) — ถ้า normalized ว่าง = emoji ล้วน
        if (mb_strlen($normalized) === 0) {
            return $this->pickCannedReply($name, $isLao, 'emoji');
        }

        // Mixed: emoji + 1-2 ตัวอักษร (เช่น "🙏ค่ะ", "❤️")
        $stripped = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{27BF}\x{1F600}-\x{1F64F}\x{2700}-\x{27BF}\x{2B00}-\x{2BFF}\s]/u', '', $commentText);
        if (mb_strlen(trim($stripped)) <= 2 && mb_strlen($commentText) > 0) {
            return $this->pickCannedReply($name, $isLao, 'emoji');
        }

        return null;
    }

    /**
     * Normalize ข้อความสำหรับ pattern matching
     *  - ตัด emoji, whitespace, punctuation ที่ขอบ
     *  - lower case
     */
    protected function normalizeForPatternMatch(string $text): string
    {
        // ตัด emoji (BMP + supplementary planes)
        $stripped = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{27BF}\x{1F600}-\x{1F64F}\x{2700}-\x{27BF}\x{2B00}-\x{2BFF}]/u', '', $text);
        // ตัด punctuation พื้นฐาน
        $stripped = preg_replace('/[\.,\-\!\?\"\'`~@#\$%\^&\*\(\)\[\]\{\}<>\/\\\\|_=\+]+/u', '', $stripped);
        // collapse whitespace
        $stripped = preg_replace('/\s+/u', '', $stripped);
        return mb_strtolower(trim($stripped));
    }

    /**
     * เลือก canned reply ตาม category + locale (มี variation กัน FB spam detection)
     *
     * @param  string  $name      ชื่อลูกค้า
     * @param  bool    $isLao     true = ภาษาลาว, false = ไทย
     * @param  string  $category  'blessing' | 'emoji'
     * @return string  reply ที่ใส่ name แล้ว
     */
    protected function pickCannedReply(string $name, bool $isLao, string $category): string
    {
        if ($category === 'blessing') {
            $replies = $isLao ? [
                'ສາທຸເດີ {name} 🙏 ຂໍໃຫ້ດວງດີຕະຫຼອດອາທິດນີ້',
                'ນ້ອມຮັບພອນດີໆ {name} 💖 ຂໍໃຫ້ສຸກສະບາຍ',
                'ຂອບໃຈເດີ {name} 🌟 ສົ່ງພະລັງບວກໃຫ້ກັບເຊັ່ນກັນ',
                'ສາທຸ ຟ້າເປີດທາງໃໝ່ໃຫ້ {name} 🙏✨',
                'ນ້ອມຮັບພະລັງບວກເດີ {name} 💫',
                'ຂໍໃຫ້ສົມຫວັງທຸກປະການເດີ {name} ✨',
            ] : [
                'สาธุค่ะ {name} 🙏 ขอให้ดวงพุ่งแรงทั้งสัปดาห์นี้',
                'ขอให้สมหวังทุกประการค่ะคุณ {name} ✨',
                'น้อมรับพรดีๆ ค่ะ {name} 💖 ขอให้สุขภาพแข็งแรงนะคะ',
                'ขอบคุณค่ะ {name} 🌟 ส่งพลังบวกให้คุณกลับด้วยนะคะ',
                'สาธุค่ะ ฟ้าเปิดทางใหม่ให้คุณ {name} 🙏✨',
                'น้อมรับพลังบวกค่ะ {name} 💫 ขอให้สิ่งดีๆ เข้ามาทุกวัน',
                'ขอให้ทุกอย่างราบรื่นนะคะ {name} 🌈',
                'สาธุค่ะ ขอให้คุณ {name} โชคดีตลอดทั้งเดือน 🍀',
            ];
        } else { // emoji
            $replies = $isLao ? [
                '🙏 ສາທຸເດີ {name}',
                '💖 ສົ່ງພະລັງບວກໃຫ້ {name}',
                '✨ ຂໍໃຫ້ສຸກສະບາຍເດີ',
                '🌟 ຂອບໃຈເດີ {name}',
            ] : [
                '💖 ขอให้คุณ {name} โชคดีค่ะ',
                '🙏 สาธุค่ะ ขอพรดีๆ ตามมาเลย',
                '✨ พลังบวกถึงคุณ {name} แล้วค่ะ',
                '🌟 ขอบคุณค่ะคุณ {name}',
                '💫 ส่งความปรารถนาดีกลับนะคะ',
                '🌈 ขอให้วันนี้สดใสค่ะ {name}',
            ];
        }

        $reply = $replies[array_rand($replies)];
        return str_replace('{name}', $name, $reply);
    }
}
