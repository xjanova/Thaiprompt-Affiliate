<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommentEngagement;
use App\Jobs\ProcessFortuneTelling;
use App\Models\FortuneCommentEngagement;
use App\Models\FortunePostReaction;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Services\CelticCrossService;
use App\Services\FacebookRichMessageService;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\ImageIntentClassifier;
use App\Services\Fortune\ImageSpamGuard;
use App\Services\FortuneAIService;
use App\Services\FortuneBannerService;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use App\Services\FortuneMonthlyClaimService;
use App\Services\FortuneTakeoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Facebook Webhook Controller
 *
 * จัดการ webhook events จาก Facebook Messenger
 * รองรับการรับคอมเมนต์, Messenger messages, รูปภาพ
 * รองรับระบบ Freemium: คำทำนายพื้นฐาน + เชิงลึก
 *
 * Production features:
 * - Webhook signature verification (X-Hub-Signature-256)
 * - Always return HTTP 200 (Facebook จะ disable webhook ถ้าได้ 500)
 * - Typing indicator ขณะ AI กำลังประมวลผล
 * - รองรับรับรูปภาพจากผู้ใช้และส่งรูปกลับ
 * - Quick replies buttons สำหรับ UX ที่ดี
 * - Message splitting สำหรับข้อความยาว
 */
class FacebookWebhookController extends Controller
{
    protected $facebookService;

    protected $aiService;

    protected $conversationService;

    protected $settings;

    /**
     * FortuneChannelManager — ตัวกลางจัดการ routing + Rich Message response
     * ใช้ตรรกะเดียวกันกับ LINE Bot (keyword matching, state machine, AI chat)
     */
    protected $channelManager;

    /**
     * FortuneTakeoverService — ระบบเทคโอเวอร์ (แม่หมอ/แอดมินคุยแทน AI)
     * ใช้ร่วมกันกับ LINE เพื่อให้โฟลและ cache ไม่ขัดแย้งกัน
     */
    protected FortuneTakeoverService $takeoverService;

    /**
     * FortuneBanService — ระบบ "คุก" ห้ามบอทคุยกับ user ที่ไม่เหมาะสม
     */
    protected FortuneBanService $banService;

    /**
     * FortuneBannerService — ส่งภาพแบนเนอร์ก่อนข้อความใน DM
     */
    protected ?FortuneBannerService $bannerService = null;

    public function __construct()
    {
        try {
            $this->settings = FortuneTellingSetting::getSettings();
            $this->facebookService = new FacebookWebhookService($this->settings);
            $this->aiService = new FortuneAIService($this->settings);
            $this->conversationService = new FortuneConversationService($this->settings);
            $this->channelManager = new FortuneChannelManager($this->settings);
            $this->takeoverService = app(FortuneTakeoverService::class);
            $this->banService = app(FortuneBanService::class);
            // 🛡️ (2026-05-06) แยก try/catch ออกมา — กัน bannerService = null
            //    ถ้า service ตัวข้างบนไม่ throw แต่ banner init ดันพัง
            //    (ก่อนหน้าถ้า init ก่อนหน้านี้ throw แล้วรันเข้า catch fallback
            //     bannerService ก็จะเป็น null ตลอด → DM banner ไม่ส่งเลย)
            $this->initBannerService();
        } catch (\Exception $e) {
            // ป้องกัน controller พังทั้งหมดถ้า DB/Pool มีปัญหา
            Log::error('FacebookWebhookController: เริ่มต้นระบบไม่สำเร็จ', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            // สร้าง fallback services เพื่อให้ระบบยังทำงานได้
            if (! $this->settings) {
                $this->settings = new FortuneTellingSetting;
            }
            if (! $this->facebookService) {
                try {
                    $this->facebookService = new FacebookWebhookService($this->settings);
                } catch (\Exception $fbError) {
                    Log::error('FacebookWebhookController: สร้าง FacebookWebhookService ไม่ได้', [
                        'error' => $fbError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ aiService และ conversationService ด้วย
            if (! $this->aiService) {
                try {
                    $this->aiService = new FortuneAIService($this->settings);
                } catch (\Exception $aiError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneAIService ไม่ได้', [
                        'error' => $aiError->getMessage(),
                    ]);
                }
            }
            if (! $this->conversationService) {
                try {
                    $this->conversationService = new FortuneConversationService($this->settings);
                } catch (\Exception $convError) {
                    Log::error('FacebookWebhookController: สร้าง ConversationService ไม่ได้', [
                        'error' => $convError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ channelManager
            if (! $this->channelManager) {
                try {
                    $this->channelManager = new FortuneChannelManager($this->settings);
                } catch (\Exception $cmError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneChannelManager ไม่ได้', [
                        'error' => $cmError->getMessage(),
                    ]);
                }
            }
            // ✅ สร้าง fallback สำหรับ takeoverService
            if (! isset($this->takeoverService)) {
                try {
                    $this->takeoverService = app(FortuneTakeoverService::class);
                } catch (\Exception $toError) {
                    Log::error('FacebookWebhookController: สร้าง FortuneTakeoverService ไม่ได้', [
                        'error' => $toError->getMessage(),
                    ]);
                }
            }
            // ✅ (2026-05-06) สร้าง fallback สำหรับ bannerService
            //    ก่อนหน้านี้ขาด — ทำให้ DM banner ไม่ส่งเงียบ ๆ ถ้า init ตัวบนพัง
            $this->initBannerService();
        }
    }

    /**
     * Initialize FortuneBannerService แยก try/catch
     * เพื่อให้ banner ทำงานได้ แม้ service ตัวอื่นจะ throw ตอน init
     */
    protected function initBannerService(): void
    {
        if ($this->bannerService) {
            return; // มีอยู่แล้ว
        }

        try {
            $settings = $this->settings ?? FortuneTellingSetting::getSettings();
            $this->bannerService = new FortuneBannerService($settings);
        } catch (\Throwable $e) {
            Log::error('FacebookWebhookController: สร้าง FortuneBannerService ไม่ได้', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify webhook (GET request จาก Facebook)
     *
     * Facebook จะส่ง GET request เพื่อ verify webhook URL
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $this->settings->facebook_verify_token) {
            Log::info('Facebook Webhook Verified');

            return response($challenge, 200);
        }

        Log::warning('Facebook Webhook Verification Failed', [
            'mode' => $mode,
            'token_match' => $token === $this->settings->facebook_verify_token,
        ]);

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * รับ webhook events (GET/POST request จาก Facebook)
     *
     * GET: Facebook ส่ง verify request พร้อม hub.mode, hub.verify_token, hub.challenge
     * POST: Facebook ส่ง events (messages, comments, etc.)
     *
     * ⚠️ CRITICAL: POST ต้อง return 200 เสมอ
     * Facebook จะ retry ถ้าได้ non-200 response และจะ disable webhook หลัง retry หลายครั้ง
     */
    public function webhook(Request $request)
    {
        // จัดการ GET verification request จาก Facebook
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        try {
            // 🔍 Debug: Log ทุก webhook request ที่เข้ามา
            Log::info('📥 Facebook Webhook RAW', [
                'method' => $request->method(),
                'has_signature' => ! empty($request->header('X-Hub-Signature-256')),
                'content_length' => strlen($request->getContent()),
                'ip' => $request->ip(),
            ]);

            // ตรวจสอบ webhook signature (security)
            $signature = $request->header('X-Hub-Signature-256', '');
            if (! $this->facebookService->verifyWebhookSignature(
                $request->getContent(),
                $signature
            )) {
                Log::warning('Facebook Webhook: Invalid signature', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['status' => 'ok']); // ยังคง return 200
            }

            if (! $this->settings->isServiceEnabled()) {
                Log::info('📥 Webhook: Service disabled, skipping');

                return response()->json(['status' => 'ok']);
            }

            $data = $request->all();
            Log::info('Received Facebook Webhook', [
                'object' => $data['object'] ?? 'unknown',
                'entry_count' => count($data['entry'] ?? []),
            ]);

            if (($data['object'] ?? '') !== 'page') {
                return response()->json(['status' => 'ok']);
            }

            foreach ($data['entry'] ?? [] as $entry) {
                // 🔍 Debug: Log entry details
                $hasChanges = ! empty($entry['changes']);
                $hasMessaging = ! empty($entry['messaging']);
                if ($hasChanges) {
                    foreach ($entry['changes'] as $change) {
                        Log::info('📥 Webhook Entry Change', [
                            'field' => $change['field'] ?? 'unknown',
                            'item' => $change['value']['item'] ?? 'unknown',
                            'verb' => $change['value']['verb'] ?? 'unknown',
                            'from_id' => $change['value']['from']['id'] ?? null,
                            'message' => substr($change['value']['message'] ?? '', 0, 100),
                        ]);
                    }
                }
                if ($hasMessaging) {
                    Log::info('📥 Webhook Entry Messaging', [
                        'count' => count($entry['messaging']),
                    ]);
                }

                $this->processEntry($entry);
            }
        } catch (\Exception $e) {
            // Log error แต่ยังคง return 200 เพื่อไม่ให้ Facebook retry
            Log::error('Facebook Webhook Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // ⚠️ ALWAYS return 200 OK
        return response()->json(['status' => 'ok']);
    }

    /**
     * ประมวลผล entry จาก webhook
     */
    protected function processEntry(array $entry): void
    {
        // ประมวลผลคอมเมนต์ + reaction
        foreach ($entry['changes'] ?? [] as $change) {
            if (($change['field'] ?? '') !== 'feed') {
                continue;
            }
            $item = $change['value']['item'] ?? '';

            if ($item === 'comment') {
                $this->processComment($change['value']);
            } elseif ($item === 'reaction') {
                // กดไลก์/หัวใจ/wow ฯลฯ → track + ลองส่ง DM ถ้าอยู่ใน 24hr window
                $this->processReaction($change['value']);
            }
        }

        // ประมวลผล direct messages
        foreach ($entry['messaging'] ?? [] as $messaging) {
            // ตรวจจับ Echo Messages (ข้อความที่แอดมินส่งจากเพจ)
            // เมื่อแอดมินตอบข้อความ user ผ่าน Page Inbox
            // Facebook จะส่ง webhook พร้อม is_echo = true
            if (! empty($messaging['message']['is_echo'])) {
                $this->handleEchoMessage($messaging);

                continue; // ไม่ต้องประมวลผลเป็น user message
            }

            if (isset($messaging['message'])) {
                $this->processMessage($messaging);
            }

            // ประมวลผล postback events (ปุ่ม Get Started, Quick Replies, etc.)
            if (isset($messaging['postback'])) {
                $this->processPostback($messaging);
            }

            // 💗 ประมวลผล message_reactions (user กด ❤️/👍 ข้อความ bot)
            // — ส่ง emoji กลับ (throttle 1 ครั้ง/60 วินาที)
            if (isset($messaging['reaction'])) {
                $this->handleMessageReaction($messaging);
            }

            // 🎁 (2026-05-04) ประมวลผล m.me referral
            //   - ผู้ใช้ใหม่: postback.referral.ref (ส่งคู่กับ GET_STARTED postback)
            //   - ผู้ใช้เก่า: referral.ref (event เดี่ยว)
            //   ใช้สำหรับแคมเปญ MONTHLY_CLAIM_YYYY-MM
            $referralRef = $messaging['referral']['ref']
                ?? $messaging['postback']['referral']['ref']
                ?? null;
            if ($referralRef) {
                $this->processMessengerReferral($messaging, $referralRef);
            }
        }
    }

    /**
     * 🎁 ประมวลผล m.me referral — handle MONTHLY_CLAIM_YYYY-MM
     *
     * รองรับ ref pattern อื่นๆ ในอนาคตได้ (campaign tracking, deep-link landing)
     */
    protected function processMessengerReferral(array $messaging, string $ref): void
    {
        try {
            $senderId = $messaging['sender']['id'] ?? null;
            if (! $senderId) {
                return;
            }

            Log::info('🎁 Facebook referral received', [
                'sender_id' => $senderId,
                'ref' => $ref,
            ]);

            $claimService = app(FortuneMonthlyClaimService::class);
            $monthKey = $claimService->parseRefForCurrentMonth($ref);

            // ไม่ใช่ ref ของแคมเปญรายเดือน หรือเป็น month_key เก่า → skip
            if (! $monthKey) {
                Log::info('🎁 Referral ref ไม่ใช่ MONTHLY_CLAIM ของเดือนปัจจุบัน', [
                    'ref' => $ref,
                ]);

                return;
            }

            $result = $claimService->claimForUser(
                $senderId,
                'facebook',
                FortuneMonthlyClaimService::SOURCE_GROUP_POST,
                ['referrer' => $ref]
            );

            // 🎁 (2026-05-04) Auto-trigger ทำนายฟรีทันทีหลัง claim สำเร็จ
            //   เคสเดิม: ส่งแค่ข้อความ "รับสิทธิ์แล้ว — พิมพ์ดูดวง" → ลูกค้าต้องพิมพ์เอง
            //   เคสใหม่: claim สำเร็จ → ทำนายฟรีให้เลย (ลูกค้าได้คำทำนายทันที)
            //
            //   hasUsedFreeCard ถูกแก้ให้ตรวจ claimed_at vs responded_at →
            //   ลูกค้าที่เคยใช้ฟรีไปแล้วจะ eligible ใหม่หลัง claim
            if ($result['status'] === 'granted' && $this->channelManager && $this->facebookService) {
                try {
                    // ส่งข้อความขอบคุณก่อน (สั้นๆ — ทำนายจะตามมาทันที)
                    $this->facebookService->sendMessage(
                        $senderId,
                        "🎁 รับสิทธิ์ดูฟรีประจำเดือนเรียบร้อยค่ะ ✨\nแม่หมอจะเปิดไพ่ให้ทันทีเลยนะคะ 🌙"
                    );
                    usleep(500_000);

                    // trigger startFreeCardFlow ผ่าน processConversationalMessage
                    //   ส่ง keyword "ทำนายฟรี" → matchesFreeCardKeyword → startFreeCardFlow
                    //   (hasUsedFreeCard จะคืน false เพราะ claim ใหม่กว่า responded_at)
                    $this->processConversationalMessage($senderId, 'ทำนายฟรี');

                    return;
                } catch (\Throwable $autoErr) {
                    Log::warning('🎁 Auto-trigger free card หลัง claim ล้ม — fallback ส่งข้อความปกติ', [
                        'sender_id' => $senderId,
                        'error' => $autoErr->getMessage(),
                    ]);
                }
            }

            // Fallback: ส่งข้อความตอบกลับเฉยๆ (สำหรับ already_claimed / disabled / error)
            if ($this->facebookService) {
                $this->facebookService->sendMessage($senderId, $result['message']);
            }
        } catch (\Throwable $e) {
            Log::error('🎁 processMessengerReferral failed', [
                'error' => $e->getMessage(),
                'ref' => $ref,
            ]);
        }
    }

    /**
     * ประมวลผล reaction (ไลก์/หัวใจ/wow ฯลฯ)
     *
     * Flow:
     * 1. Track reaction ลง DB (fortune_post_reactions) — analytics + warm lead
     * 2. ลองส่ง DM ผ่าน Send API ถ้า user อยู่ใน 24hr window
     *    (ถ้าไม่อยู่ → FB 551 → skip — แต่ track ไว้)
     *
     * Facebook policy:
     * - ไม่อนุญาต unsolicited DM ให้ user ที่แค่ reaction alone
     * - Private Replies ใช้กับ comment เท่านั้น (ไม่รองรับ reaction)
     * - ดังนั้นถ้าไม่มี conversation window → skip DM
     *
     * @param  array  $data  Facebook reaction webhook payload
     */
    protected function processReaction(array $data): void
    {
        $fromId = $data['from']['id'] ?? null;
        $postId = $data['post_id'] ?? null;
        $reactionType = $data['reaction_type'] ?? null;
        $verb = $data['verb'] ?? null;
        $fromName = $data['from']['name'] ?? null;

        if (empty($fromId) || empty($postId)) {
            return;
        }

        // ไม่ track reaction จากเพจเอง
        if ($fromId === $this->settings->facebook_page_id) {
            return;
        }

        // ถ้าเพจ remove reaction (verb='remove') → ข้าม ไม่สร้าง record
        if ($verb === 'remove') {
            return;
        }

        // ตรวจว่าเปิด comment engagement ไหม (ใช้ setting เดียวกัน — reaction = engagement)
        if (! $this->settings->isCommentEngagementEnabled()) {
            return;
        }

        try {
            // 1. Track reaction — upsert (กัน duplicate ต่อ post เดียวกัน)
            $reaction = FortunePostReaction::updateOrCreate(
                [
                    'facebook_user_id' => $fromId,
                    'facebook_post_id' => $postId,
                ],
                [
                    'reaction_type' => $reactionType,
                    'verb' => $verb,
                    'user_name' => $fromName,
                    'reacted_at' => now(),
                ]
            );

            Log::info('👍 Reaction tracked', [
                'user_id' => $fromId,
                'post_id' => $postId,
                'type' => $reactionType,
                'is_new' => $reaction->wasRecentlyCreated,
            ]);

            // 2. ลอง DM เฉพาะ reaction ใหม่ (กัน spam ต่อคน) + ยังไม่ได้ลอง
            if (! $reaction->wasRecentlyCreated || $reaction->dm_attempted) {
                return;
            }

            // ลองส่ง DM (จะล้มเหลวถ้า user ไม่อยู่ใน 24hr window — OK skip)
            $this->tryReactionDm($reaction);

        } catch (\Exception $e) {
            Log::warning('processReaction error: '.$e->getMessage(), [
                'user_id' => $fromId,
                'post_id' => $postId,
            ]);
        }
    }

    /**
     * ลองส่ง DM ให้ user ที่กด reaction (เฉพาะถ้าอยู่ใน 24hr conversation window)
     *
     * ใช้ Send API RESPONSE — ถ้าล้มเหลวด้วย error 551 (user not available)
     * → skip ไม่ fallback MESSAGE_TAG เพราะ reaction อย่างเดียวไม่เพียงพอให้ FB อนุญาต tag
     */
    protected function tryReactionDm(FortunePostReaction $reaction): void
    {
        $reaction->dm_attempted = true;
        $userId = $reaction->facebook_user_id;

        try {
            // 🔒 (2026-05-21) 24h rolling cooldown (reverted จาก 3-day — คนเงียบเลย)
            //    DB query — ถ้า user คนนี้ได้รับ DM (reaction OR comment) ใน 24 ชม.
            //    ที่ผ่านมา → ข้าม. ใช้ now()->subHours(24) (rolling)
            $hasRecentReactionDm = FortunePostReaction::hasDmSuccessRecently($userId, 24);
            $hasRecentCommentDm = FortuneCommentEngagement::hasEngagedRecently($userId, 24);

            if ($hasRecentReactionDm || $hasRecentCommentDm) {
                Log::info('👍 Reaction DM ข้าม — user คนนี้ได้รับ DM ใน 24 ชม. ล่าสุดแล้ว', [
                    'user_id' => $userId,
                    'post_id' => $reaction->facebook_post_id,
                    'reason' => $hasRecentReactionDm ? 'recent_reaction_dm' : 'recent_comment_dm',
                ]);
                $reaction->dm_success = false;
                $reaction->save();

                return;
            }

            // 🚫 ถ้าลูกค้ากำลังคุยกับบอท (มี active reading) → ห้ามส่ง DM "ขอบคุณที่กดไลก์"
            //    แทรก เพราะจะทำให้ลูกค้างง (กำลังคุยเรื่องดูดวงอยู่แล้ว)
            try {
                $hasActive = FortuneReading::activeConversation($userId)->exists();
                if ($hasActive) {
                    Log::info('👍 Reaction DM ข้าม — user กำลังคุยกับบอทอยู่', [
                        'user_id' => $userId,
                    ]);
                    $reaction->dm_success = false;
                    $reaction->save();

                    return;
                }
            } catch (\Throwable $e) {
                // ถ้า query ล้ม → ดำเนินการต่อ best-effort (ไม่ควรบล็อก reaction DM)
                Log::debug('tryReactionDm: active check failed (non-blocking): '.$e->getMessage());
            }

            // 🌙 (2026-05-21) Daily Horoscope greeting — แทน returning-user variants เดิม
            //    มี birth_date ใน DB → ส่งดวงประจำวันตาม day_of_birth
            //    ไม่มี → ทักทายชวนดูดวง + promise ส่งดวงฟรีหลังจากนั้น
            $userName = $reaction->user_name ?? null;
            if (empty($userName)) {
                // ลอง lookup จาก past reading
                $userName = \App\Models\FortuneReading::where('facebook_user_id', $userId)
                    ->whereNotNull('facebook_user_name')
                    ->latest('updated_at')
                    ->value('facebook_user_name');
            }
            $greetingService = app(\App\Services\Fortune\FortuneGreetingService::class);
            $message = $greetingService->buildDailyHoroscopeGreeting($userId, $userName ?? 'คุณ');

            // ⚠️ ไม่ใส่ Quick Reply ปุ่มขาย/ดูดวง — ให้ลูกค้าพิมพ์ตอบเอง (ตอบอะไรก็ทำนายฟรี)
            $quickReplies = [];

            // 🖼️ ส่งแบนเนอร์ก่อน text (ถ้าเปิดใน admin)
            //   👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — pass platform+userId เพื่อ skip ลูกค้าเก่า
            if ($this->bannerService) {
                $this->bannerService->sendBannerThenWait(
                    fn ($url) => $this->facebookService->sendImage($userId, $url),
                    'reaction',
                    'facebook',
                    $userId
                );
            }

            $success = $this->facebookService->sendQuickReplies(
                $userId,
                $message,
                $quickReplies,
                ['messaging_type' => 'RESPONSE']
            );

            $reaction->dm_success = (bool) $success;
            $reaction->save();

            if ($success) {
                Log::info('✅ Reaction DM sent (daily horoscope greeting)', [
                    'user_id' => $userId,
                    'post_id' => $reaction->facebook_post_id,
                ]);
            } else {
                Log::info('ℹ️ Reaction DM skipped (user not in 24hr window)', [
                    'user_id' => $userId,
                ]);
            }
        } catch (\Exception $e) {
            $reaction->dm_success = false;
            $reaction->save();

            Log::debug('tryReactionDm: '.$e->getMessage(), [
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * ประมวลผลคอมเมนต์
     */
    protected function processComment(array $comment): void
    {
        $message = $comment['message'] ?? '';
        $fromId = $comment['from']['id'] ?? null;
        $commentId = $comment['comment_id'] ?? null;

        if (empty($fromId)) {
            return;
        }

        // 🛡️ Link Spam Moderation — ซ่อน/ลบคอมเม้นต์ที่มีลิงค์ภายนอก
        //    — ตรวจก่อน auto-like + engagement → ถ้าซ่อน/ลบสำเร็จก็จบ flow
        //    — ข้ามถ้าคอมจากเพจเอง
        if (! empty($commentId)
            && ($this->settings->auto_hide_link_comments ?? false)
            && $fromId !== ($this->settings->facebook_page_id ?? null)) {
            if ($this->moderateLinkComment($commentId, $message)) {
                return; // ซ่อน/ลบแล้ว → ไม่ต้องทำ engagement ต่อ
            }
        }

        // 👍 Auto-like ทุกคอมเม้นต์ที่มาจาก user (ไม่ใช่จากเพจเอง)
        //    — ครั้งเดียวต่อ comment_id (cache 24 ชม.)
        //    — best-effort: ถ้าล้มยังไป flow ต่อได้
        //    — เปิดใช้ก็ต่อเมื่อ token มี pages_manage_engagement scope
        if (! empty($commentId) && $fromId !== ($this->settings->facebook_page_id ?? null)) {
            $likeKey = "fb_liked_comment_{$commentId}";
            if (! Cache::has($likeKey)) {
                try {
                    $this->facebookService->reactToComment($commentId, 'LIKE');
                    Cache::put($likeKey, 1, now()->addHours(24));
                } catch (\Throwable $e) {
                    // non-blocking — log แค่ debug
                    Log::debug('Auto-like comment ล้ม (non-blocking): '.$e->getMessage(), [
                        'comment_id' => $commentId,
                    ]);
                }
            }
        }

        // ตรวจสอบว่าเป็นคำขอดูดวงเชิงลึกหรือพื้นฐาน
        $isDeepRequest = $this->facebookService->isDeepReadingRequest($message);
        $questions = $this->facebookService->parseQuestions($message);

        if (empty($questions)) {
            // ไม่ใช่คำสั่งดูดวง → ลอง engage ชวนดูดวง
            $this->handleCommentEngagement($comment);

            return;
        }

        // แยกวันเกิดจากข้อความ (ถ้ามี)
        $birthDate = $this->facebookService->parseBirthDate($message);

        // ตรวจสอบ limit ตามประเภทคำขอ
        if ($isDeepRequest && $this->settings->isDeepReadingEnabled()) {
            $deepLimitCheck = $this->facebookService->checkDeepFreeLimit($fromId);

            if ($deepLimitCheck['has_reached_limit']) {
                // ✅ ครบ limit → ใช้ engagement template ชวนดูดวงแทนการส่ง limit message
                Log::info('🚫 Deep limit reached → redirect to engagement', [
                    'user_id' => $fromId,
                ]);
                $this->handleCommentEngagement($comment);

                return;
            }

            $this->processFortuneTelling($comment, $questions, true, true, null, $birthDate);
        } else {
            $limitCheck = $this->facebookService->checkFreeLimit($fromId);

            if ($limitCheck['has_reached_limit']) {
                // ✅ ครบ limit → ใช้ engagement template ชวนดูดวงแทนการส่ง limit message
                Log::info('🚫 Free limit reached → redirect to engagement', [
                    'user_id' => $fromId,
                ]);
                $this->handleCommentEngagement($comment);

                return;
            }

            $this->processFortuneTelling($comment, $questions, true, false, null, $birthDate);
        }
    }

    /**
     * 🛡️ ตรวจ + ซ่อน/ลบคอมเม้นต์ที่มีลิงค์ภายนอก
     *
     * Returns true ถ้าทำการ moderate แล้ว (caller ควรหยุด flow)
     * Returns false ถ้าไม่มีลิงค์ หรือ log_only mode
     */
    protected function moderateLinkComment(string $commentId, string $message): bool
    {
        if (empty(trim($message))) {
            return false;
        }

        $whitelist = $this->settings->link_whitelist_domains ?? [];
        if (! is_array($whitelist)) {
            $whitelist = [];
        }

        // เพิ่ม default whitelist ของระบบเสมอ (กันแอดมินลืมตั้ง)
        $defaults = ['thaiprompt.online', 'main.thaiprompt.online', 'm.me', 'lin.ee', 'line.me', 'facebook.com', 'fb.com'];
        $whitelist = array_unique(array_merge($whitelist, $defaults));

        if (! $this->facebookService->containsExternalLink($message, $whitelist)) {
            return false;
        }

        $action = $this->settings->link_comment_action ?? 'hide';
        $logOnly = (bool) ($this->settings->link_moderation_log_only ?? false);

        Log::warning('🛡️ Link spam detected ในคอมเม้นต์', [
            'comment_id' => $commentId,
            'action' => $action,
            'log_only' => $logOnly,
            'message_preview' => mb_substr($message, 0, 200),
        ]);

        if ($logOnly) {
            return false; // dry-run — ไม่ทำจริง ปล่อย flow ต่อ
        }

        if ($action === 'delete') {
            return $this->facebookService->deleteComment($commentId);
        }

        return $this->facebookService->hideComment($commentId);
    }

    /**
     * จัดการ Comment Engagement - ชวนดูดวงเมื่อมีคนคอมเม้นต์
     *
     * เมื่อมีคอมเม้นต์ที่ไม่ใช่คำสั่งดูดวง:
     * 1. ตอบคอมเม้นต์สั้นๆ ชวนดูดวง
     * 2. ทัก inbox ส่วนตัว + Quick Replies
     */
    protected function handleCommentEngagement(array $comment): void
    {
        try {
            Log::info('🗨️ handleCommentEngagement called', [
                'from_id' => $comment['from']['id'] ?? null,
                'comment_id' => $comment['comment_id'] ?? null,
                'message' => substr($comment['message'] ?? '', 0, 100),
            ]);

            // ตรวจสอบว่าเปิดระบบ engagement หรือไม่
            if (! $this->settings->isCommentEngagementEnabled()) {
                Log::info('🗨️ Comment Engagement: DISABLED - skipping');

                return;
            }

            $fromId = $comment['from']['id'] ?? null;
            $commentId = $comment['comment_id'] ?? null;
            $postId = $comment['post_id'] ?? null;
            $message = $comment['message'] ?? '';
            $fromName = $comment['from']['name'] ?? null;

            if (empty($fromId) || empty($commentId) || empty($postId)) {
                Log::warning('🗨️ Comment Engagement: Missing data', [
                    'has_from_id' => ! empty($fromId),
                    'has_comment_id' => ! empty($commentId),
                    'has_post_id' => ! empty($postId),
                ]);

                return;
            }

            // ไม่ตอบคอมเม้นต์จากเพจเอง
            if ($fromId === $this->settings->facebook_page_id) {
                Log::info('🗨️ Comment Engagement: Own page comment - skipping');

                return;
            }

            // 🛑 Admin Handover: ถ้าแอดมินกำลังดูแล user คนนี้ใน DM
            // → บอทไม่ควรส่ง DM แทรกระหว่าง conversation ของแอดมิน
            try {
                if ($this->isAdminActive($fromId)) {
                    Log::info('👨‍💼 Comment Engagement: ข้าม (แอดมินกำลังดูแล user คนนี้ใน DM)', [
                        'user_id' => $fromId,
                        'comment_id' => $commentId,
                    ]);

                    return;
                }
            } catch (\Throwable $e) {
                // ถ้า takeover service ล้ม → ดำเนินการต่อ best-effort
                Log::debug('Takeover check failed (non-blocking): '.$e->getMessage());
            }

            // 🚫 ถ้าลูกค้ากำลังคุยกับบอทอยู่ (มี active reading) → ห้ามส่ง DM "ชวนดูดวง"
            //    แทรก เพราะจะทำให้ลูกค้างง (กำลังอยู่ในสเตปดูดวงอยู่แล้ว)
            try {
                $hasActive = FortuneReading::activeConversation($fromId)->exists();
                if ($hasActive) {
                    Log::info('🗨️ Comment Engagement: ข้าม — user กำลังคุยกับบอทอยู่ (active reading)', [
                        'user_id' => $fromId,
                        'comment_id' => $commentId,
                    ]);

                    return;
                }
            } catch (\Throwable $e) {
                Log::debug('Comment engagement active check failed (non-blocking): '.$e->getMessage());
            }

            // ตรวจสอบซ้ำเฉพาะระดับ comment_id (ป้องกัน webhook retry ส่ง DM ซ้ำ
            // สำหรับคอมเม้นต์เดียวกัน)
            if (FortuneCommentEngagement::hasEngagedComment($commentId)) {
                Log::info('Comment Engagement: คอมเม้นต์นี้ engage แล้ว ข้าม', [
                    'user_id' => $fromId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

            // 📆 (2026-05-21) 24h rolling cooldown (reverted จาก 3-day — คนเงียบเลย)
            //    เคย DM (comment OR reaction) ใน 24 ชม. ล่าสุด → ข้าม
            if (FortuneCommentEngagement::hasEngagedRecently($fromId, 24)
                || FortunePostReaction::hasDmSuccessRecently($fromId, 24)) {
                Log::info('Comment Engagement: user ได้ DM ใน 24 ชม. ล่าสุดแล้ว ข้าม', [
                    'user_id' => $fromId,
                    'comment_id' => $commentId,
                ]);

                return;
            }

            // 🔥 Warm lead detection — ถ้า user เคยกด reaction ในโพสต์ใด
            // → แสดงว่า user สนใจเพจอยู่แล้ว → log เป็น high-intent signal
            // ⚠️ ห่อ try/catch: ถ้าตาราง fortune_post_reactions ยังไม่มี (migration ยังไม่รัน)
            //    → ข้ามได้ ห้ามให้ warm-lead check มาบล็อกการส่ง DM หลัก
            $isWarmLead = false;
            try {
                $isWarmLead = FortunePostReaction::hasReacted($fromId);
                if ($isWarmLead) {
                    Log::info('🔥 Comment Engagement: WARM LEAD — user เคยกด reaction', [
                        'user_id' => $fromId,
                        'post_id' => $postId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::debug('Warm lead check failed (non-blocking): '.$e->getMessage());
            }

            // 💰 Money-keyword route: ถ้าคอมเม้นต์เกี่ยวกับการเงิน/เงิน/หนี้ ฯลฯ
            // → ชวนเข้าร่วม affiliate (ได้ค่าชวน 10 บาท/คน) + 2 ปุ่ม อยาก/ไม่อยาก
            if ($this->isMoneyRelatedComment($message)) {
                Log::info('💰 Comment Engagement: detected money keyword → affiliate pitch', [
                    'user_id' => $fromId,
                    'comment_snippet' => mb_substr($message, 0, 60),
                    'is_warm_lead' => $isWarmLead,
                ]);
                $this->sendAffiliateRecruitmentEngagement($comment);

                return;
            }

            $mode = $this->settings->getCommentEngagementMode();

            if ($mode === 'template') {
                // โหมดเทมเพลต: ส่งเลยไม่ต้องรอ AI
                $this->sendTemplateEngagement($comment);
            } else {
                // โหมด AI: dispatch job ให้ AI สร้างข้อความ
                // ⚠️ ถ้า queue driver = sync → dispatch จะ block webhook ยาวเกิน 20s
                //    → fallback เป็น template ส่งทันที (ลูกค้ายังได้ DM แต่ไม่มี AI personalization)
                $queueDriver = config('queue.default', 'sync');
                if ($queueDriver === 'sync') {
                    Log::info('🗨️ queue=sync → AI engagement ส่งเป็น template fallback', [
                        'user_id' => $fromId,
                    ]);
                    $this->sendTemplateEngagement($comment);
                } else {
                    ProcessCommentEngagement::dispatch([
                        'facebook_user_id' => $fromId,
                        'facebook_post_id' => $postId,
                        'facebook_comment_id' => $commentId,
                        'comment_text' => $message,
                        'user_name' => $fromName,
                    ]);
                }
            }

            Log::info('Comment Engagement: dispatched', [
                'mode' => $mode,
                'user_id' => $fromId,
                'post_id' => $postId,
            ]);

        } catch (\Exception $e) {
            Log::error('Comment Engagement Error: '.$e->getMessage(), [
                'comment' => $comment,
            ]);
        }
    }

    /**
     * ส่ง engagement แบบเทมเพลต (ไม่ใช้ AI)
     */
    protected function sendTemplateEngagement(array $comment): void
    {
        $fromId = $comment['from']['id'];
        $commentId = $comment['comment_id'];
        $postId = $comment['post_id'];
        $commentText = $comment['message'] ?? '';
        $fromName = $comment['from']['name'] ?? 'คุณ';

        // ดึง user profile พร้อม fallback
        $userProfile = $this->facebookService->getUserProfile($fromId);
        if (! is_array($userProfile)) {
            $userProfile = ['name' => $fromName, 'id' => $fromId];
        }
        $name = $userProfile['name'] ?? $fromName;

        // 👤 Persist name ที่ได้จาก comment payload ลง FortuneUserCredit ทันที
        //   เคสที่เกิด: ระบบ comment ได้ name จริงจาก Facebook payload แต่ flow ดูดวง
        //   หา name ไม่เจอ → ลูกค้าเห็น "FACEBOOK-XXXXXX" ใน DM ครั้งต่อมา
        //   Fix: save name ลง credit ตั้งแต่ point ที่ได้ name → flow อื่นใช้ผ่าน credit
        FortuneUserCredit::rememberName($fromId, 'facebook', $name);

        // 🌙 (2026-05-21) Comment reply: short text in the public comment
        $commentReply = str_replace(
            ['{name}', '{comment}'],
            [$name, $commentText],
            $this->settings->getCommentReplyTemplate()
        );

        // 🌙 DM message — ดวงประจำวันสั้นๆ (deterministic)
        //   มี birth_date ใน DB → ดวงประจำวันตาม day_of_birth
        //   ไม่มี → ทักทาย + ชวนดูดวง + promise ส่งดวงฟรีหลังจากนั้น
        $greetingService = app(\App\Services\Fortune\FortuneGreetingService::class);
        $dmMessage = $greetingService->buildDailyHoroscopeGreeting($fromId, $name);

        // 1. ตอบคอมเม้นต์ (best-effort — ถ้าล้มยังส่ง DM ต่อได้)
        try {
            $this->facebookService->replyToComment($commentId, $commentReply);
        } catch (\Throwable $e) {
            Log::warning('replyToComment ล้ม (ยังส่ง DM ต่อ)', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }

        // 1.5 👍 กด "ถูกใจ" comment ของลูกค้าอัตโนมัติ
        // - User เห็นว่าเพจ active + ใส่ใจ → engagement rate สูงขึ้น
        // - FB algorithm boost reach (เพจที่ engage comment ตัวเองได้คะแนนสูง)
        try {
            $this->facebookService->reactToComment($commentId, 'LIKE');
        } catch (\Throwable $e) {
            // non-blocking
        }

        // 2. ส่ง inbox + Quick Replies
        // ส่ง comment_id เพื่อให้ใช้ Private Replies endpoint (bypass 24hr window
        // และแก้ error 551 "บุคคลนี้ไม่พร้อมใช้งาน" สำหรับ user ที่ไม่เคยทักเพจ)
        // 🎁 (2026-05-04) ลบปุ่ม Quick Reply ขายออก — ให้ลูกค้าตอบเอง
        //    เมื่อตอบกลับ → tryAutoFreeCardForFirstReply ทำนายฟรีทันที
        $quickReplies = [];

        // 🖼️ ส่งแบนเนอร์ก่อน text DM (ถ้าเปิดใน admin)
        // 🆕 (2026-05-07) ส่ง comment_id เพื่อใช้ Private Replies endpoint (bypass error 551)
        // 👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — skip ลูกค้าเก่า
        if ($this->bannerService) {
            $this->bannerService->sendBannerThenWait(
                fn ($url) => $this->facebookService->sendImage($fromId, $url, null, ['comment_id' => $commentId]),
                'comment',
                'facebook',
                $fromId
            );
        }

        $dmSent = $this->facebookService->sendQuickReplies($fromId, $dmMessage, $quickReplies, [
            'from_comment_engagement' => true,
            'comment_id' => $commentId,
        ]);

        // 2.5 ส่งปุ่มติดตามเพจ (best-effort — เพื่อ algorithm boost)
        // ลูกค้ากดติดตาม → FB อัลกอริธึมเห็นว่าเพจมี follower เพิ่ม + แจ้งเตือนได้ตลอด
        if ($dmSent) {
            $this->sendFollowPagePrompt($fromId, $commentId);
        }

        // 3. บันทึก engagement เฉพาะเมื่อ DM ส่งสำเร็จ
        //    ถ้าส่งไม่สำเร็จ → ไม่ dedupe ลูกค้า ให้ retry ได้ในคอมเม้นต์ถัดไป
        if (! $dmSent) {
            Log::warning('❌ Template DM ไม่ได้ส่งถึงลูกค้า — ไม่สร้าง engagement record (allow retry)', [
                'user_id' => $fromId,
                'comment_id' => $commentId,
            ]);

            return;
        }

        FortuneCommentEngagement::create([
            'facebook_user_id' => $fromId,
            'facebook_post_id' => $postId,
            'facebook_comment_id' => $commentId,
            'comment_text' => $commentText,
            'comment_reply' => $commentReply,
            'dm_message' => $dmMessage,
            'user_profile' => $userProfile,
            'engaged_at' => now(),
        ]);
    }

    /**
     * ดึงราคาดูดวงละเอียดจาก settings (DRY — เทียบกับ LineFortuneService::getDeepReadingPrice)
     *
     * ลำดับ fallback:
     * 1. deep_reading_price (จากส่วน Freemium)
     * 2. reading_price (ราคาดูดวงพื้นฐาน/ครั้ง)
     * 3. FortuneConversationService::DEEP_READING_PRICE constant
     */
    protected function getDeepReadingPriceFromSettings(): float
    {
        $price = (float) ($this->settings->deep_reading_price ?? 0);
        if ($price > 0) {
            return $price;
        }
        $price = (float) ($this->settings->reading_price ?? 0);
        if ($price > 0) {
            return $price;
        }

        return (float) FortuneConversationService::DEEP_READING_PRICE;
    }

    /**
     * ตรวจสอบว่าคอมเม้นต์บ่งชี้ว่าคนอยากหารายได้ / มีปัญหาเงิน (affiliate signal)
     *
     * ใช้ keyword เข้ม + มี "positive fortune blessing filter" เพื่อไม่ hijack
     * คนที่แค่อวยพรขอโชคลาภ หรือคนที่ต้องการดูดวงเรื่องเงิน
     *
     * กฎ:
     * 1. ถ้ามีคำอวยพร/ขอพรดวง (น้อมรับ, สาธุ, ทรัพย์สิน, ขอให้) → ไม่ใช่ signal
     * 2. ถ้ามีคำสื่อถึงการดูดวง (ดูดวง, ทำนาย, ดวง) → ไม่ใช่ signal
     * 3. ต้องมี keyword ที่บ่งบอกปัญหาเงิน/ต้องการรายได้เสริมโดยตรง
     *
     * @param  string  $message  ข้อความคอมเม้นต์
     */
    protected function isMoneyRelatedComment(string $message): bool
    {
        $message = mb_strtolower(trim($message));
        if ($message === '') {
            return false;
        }

        // 1. Filter ออก: คำอวยพร / ขอพรดวง / ดูดวง — เหล่านี้ user ต้องการให้ดวงดี ไม่ใช่หางาน
        $excludeKeywords = [
            'น้อมรับ', 'นอ้มรับ', 'ຂໍຍ້ອມຮັບ', 'ຍອມຮັບ', 'ยอมรับ',
            'สาธุ', 'ສາທຸ', 'ขอให้', 'ขอพร',
            'ทรัพย์สิน', 'ทรัพยสิน', 'ไหลมา', 'ไหลเท',
            'ดูดวง', 'ทำนาย', 'ดวง', 'หมอดู', 'ยิปซี', 'ไพ่', 'ลายมือ',
        ];
        foreach ($excludeKeywords as $kw) {
            if (mb_stripos($message, $kw) !== false) {
                return false;
            }
        }

        // 2. ต้องมี signal ชัดเจนว่าต้องการหารายได้ / มีปัญหาเงิน
        $signalKeywords = [
            'ไม่มีเงิน', 'เงินไม่พอ', 'เงินขาด', 'ขัดสน',
            'เป็นหนี้', 'หนี้สิน', 'ติดหนี้', 'มีหนี้',
            'ตกงาน', 'ว่างงาน', 'ไม่มีงาน',
            'หารายได้', 'รายได้เสริม', 'งานเสริม', 'งานออนไลน์', 'งานพิเศษ',
            'อยากรวย', 'อยากได้เงิน', 'อยากมีเงิน',
            'ขาดทุน', 'เศรษฐกิจไม่ดี', 'ลำบาก',
        ];
        foreach ($signalKeywords as $kw) {
            if (mb_stripos($message, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ส่ง Affiliate Recruitment pitch ตอบคอมเม้นต์ "การเงิน"
     *
     * Flow:
     * 1. ตอบคอมเม้นต์สั้นๆ ชวนไปคุยใน inbox
     * 2. ส่ง DM อธิบายโปรแกรม affiliate + 2 ปุ่ม "อยาก"/"ไม่อยาก"
     * 3. บันทึก engagement กันส่งซ้ำ
     *
     * @param  array  $comment  Facebook comment payload
     */
    protected function sendAffiliateRecruitmentEngagement(array $comment): void
    {
        $fromId = $comment['from']['id'];
        $commentId = $comment['comment_id'];
        $postId = $comment['post_id'];
        $commentText = $comment['message'] ?? '';
        $fromName = $comment['from']['name'] ?? 'คุณ';

        // ดึง user profile (fallback ใช้ชื่อจาก comment payload)
        $userProfile = $this->facebookService->getUserProfile($fromId);
        if (! is_array($userProfile)) {
            $userProfile = ['name' => $fromName, 'id' => $fromId];
        }
        $name = $userProfile['name'] ?? $fromName;

        // 👤 Persist name จาก comment payload ลง FortuneUserCredit (กันชื่อหายใน flow อื่น)
        FortuneUserCredit::rememberName($fromId, 'facebook', $name);

        // ข้อความตอบใต้คอมเม้นต์ (สั้น ไม่สปอยล์รายละเอียด)
        $commentReply = 'เรื่องเงินมาถูกทางแล้วค่ะ 🙏 แม่หมอมีเคล็ดลับง่ายๆ เช็คใน inbox นะคะ ✨';

        // ข้อความ DM — pitch โปรแกรม affiliate (greeting แบบ conditional ไม่ซ้อน "คุณคุณ")
        $hasName = ! empty($name) && $name !== 'คุณ';
        $greeting = $hasName ? "🙏 สวัสดีค่ะ คุณ{$name}" : '🙏 สวัสดีค่ะ';
        $dmMessage = "{$greeting}\n\n"
            ."เห็นเม้นต์เรื่องเงินแล้ว แม่หมอมีทางสร้างรายได้ง่ายๆ ให้ค่ะ\n\n"
            ."💰 ชวนเพื่อนมาดูดวงกับแม่หมอ\n"
            ."→ ได้ค่าชวน 10 บาท/คน\n\n"
            ."✨ ไม่ต้องลงทุน ไม่มีความเสี่ยง\n"
            ."📌 ชวนได้ไม่จำกัดคน\n\n"
            .'สนใจไหมคะ?';

        $quickReplies = [
            ['content_type' => 'text', 'title' => '✅ อยาก', 'payload' => 'AFFILIATE_RECRUIT_YES'],
            ['content_type' => 'text', 'title' => '❌ ไม่อยาก', 'payload' => 'AFFILIATE_RECRUIT_NO'],
        ];

        // 1. ตอบคอมเม้นต์ (403 ล้มเหลวได้ถ้าไม่มี pages_manage_engagement)
        $this->facebookService->replyToComment($commentId, $commentReply);

        // 2. ส่ง DM + Quick Replies ผ่าน Private Replies (bypass 24hr window)
        $this->facebookService->sendQuickReplies($fromId, $dmMessage, $quickReplies, [
            'from_comment_engagement' => true,
            'comment_id' => $commentId,
        ]);

        // 3. บันทึก engagement กันส่งซ้ำในโพสต์เดียวกัน
        FortuneCommentEngagement::create([
            'facebook_user_id' => $fromId,
            'facebook_post_id' => $postId,
            'facebook_comment_id' => $commentId,
            'comment_text' => $commentText,
            'comment_reply' => $commentReply,
            'dm_message' => $dmMessage,
            'user_profile' => $userProfile,
            'engaged_at' => now(),
        ]);

        Log::info('💰 Affiliate Recruitment engagement sent', [
            'user_id' => $fromId,
            'post_id' => $postId,
            'comment_id' => $commentId,
        ]);
    }

    /**
     * จัดการ Echo Message (ข้อความที่แอดมินส่งจากเพจ)
     *
     * เมื่อแอดมินตอบ user ผ่าน Facebook Page Inbox จะมี message.is_echo = true
     * ใช้สำหรับ Admin Handover: บอทจะหยุดทำงานเมื่อแอดมินกำลังดูแลลูกค้า
     *
     * Facebook Echo Message structure:
     * - sender.id = page_id (เพจเป็นผู้ส่ง)
     * - recipient.id = user_id (user เป็นผู้รับ)
     * - message.is_echo = true
     * - message.app_id = app_id ของ bot (ถ้าส่งจาก bot จะมี app_id)
     *
     * @param  array  $messaging  ข้อมูล messaging event จาก Facebook
     */
    protected function handleEchoMessage(array $messaging): void
    {
        $recipientId = $messaging['recipient']['id'] ?? null;
        $appId = $messaging['message']['app_id'] ?? null;
        $messageText = $messaging['message']['text'] ?? '';
        // 🆔 (2026-05-20) sender.id ใน echo = Page ID (ไม่ใช่ admin individual)
        //    เก็บไว้เพื่อ:
        //      - รู้ว่าตอบจากเพจไหน (multi-page support)
        //      - debug/analytics ใน RAG Q&A
        $pageId = $messaging['sender']['id'] ?? null;

        // 🔍 (2026-05-20) Diagnostic log — confirm echo arrival + state
        //    ใช้ตรวจว่า FB ส่ง echo มาจริงไหม, และเหตุผลที่ skip (ถ้ามี)
        \Illuminate\Support\Facades\Log::info('🟢 FB Echo received', [
            'recipient_id' => $recipientId,
            'page_id' => $pageId,
            'app_id' => $appId,
            'is_human_typed' => empty($appId),
            'text_preview' => mb_substr($messageText, 0, 80),
            'capture_enabled' => (bool) ($this->settings->admin_qa_capture_enabled ?? true),
            'handover_enabled' => (bool) ($this->settings->admin_handover_enabled ?? true),
        ]);

        if (empty($recipientId)) {
            return;
        }

        // 🚦 (2026-05-20 v2.2) แยก bot vs human reply ผ่าน app_id เปรียบเทียบ
        //   เดิม: !empty($appId) → skip ทุก echo ที่มี app_id (ผิด!)
        //   ปัญหา: แอดมินตอบใน Meta Business Suite → echo มี app_id=263902037430900
        //          (= Business Suite's app_id) → ถูก skip ผิด เป็น Q&A ไม่เคยถูกจับ
        //   ใหม่: เปรียบเทียบ app_id กับ bot's app_id เฉพาะตัวเรา
        //          → bot's own echo (app_id=our_app) → skip
        //          → admin via Meta Business Suite (app_id=263902037430900) → capture
        //          → admin via Page Inbox classic (app_id=null) → capture
        //   Fallback: ถ้า settings->facebook_app_id ว่าง → ใช้ behavior เก่า (safe default)
        $ourBotAppId = (string) ($this->settings->facebook_app_id ?? '');
        $echoAppId = $appId !== null ? (string) $appId : '';

        if ($ourBotAppId !== '') {
            // มี settings → เช็คแม่นยำ — skip เฉพาะ bot ของเรา
            if ($echoAppId === $ourBotAppId) {
                return; // bot's own message
            }
        } else {
            // ไม่มี settings → fallback ใช้ behavior เก่า (กัน false positive)
            \Illuminate\Support\Facades\Log::warning(
                'FB Echo: facebook_app_id ใน settings ว่าง — ใช้ legacy check (skip ทุก echo ที่มี app_id)',
                ['echo_app_id' => $echoAppId]
            );
            if (! empty($appId)) {
                return;
            }
        }

        // 📚 (2026-05-19) Capture admin Q&A สำหรับ RAG learning
        //     (2026-05-20) v2 — ส่ง context (page_id, reading_id, reading_type) เข้า job
        //     (2026-05-20) v2.1 — ย้ายออกมาก่อน handover gate
        //                          เพราะ Capture เป็น feature แยกจาก handover
        //                          admin ต้องเรียนรู้สไตล์ได้ไม่ว่า handover จะ ON/OFF
        //   admin คนตอบลูกค้าใน Page Inbox → เก็บเป็นคู่ Q&A + category
        //   ถ้า settings ปิด admin_qa_capture ก็ skip (default เปิด)
        $captureEnabled = (bool) ($this->settings->admin_qa_capture_enabled ?? true);
        if ($captureEnabled && trim($messageText) !== '') {
            try {
                // 🔍 หา reading ที่ลูกค้ามี active ณ ขณะนี้ (7 วันล่าสุด)
                //    ใช้ classify category — รู้ว่า admin ตอบลูกค้าที่อยู่ใน state ไหน
                $activeReading = \App\Models\FortuneReading::where(function ($q) use ($recipientId) {
                    $q->where('facebook_user_id', $recipientId)
                        ->orWhere(function ($sub) use ($recipientId) {
                            $sub->where('platform', 'facebook')
                                ->where('platform_user_id', $recipientId);
                        });
                })
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest()
                    ->first();

                \App\Jobs\CaptureAdminQAJob::dispatch(
                    'facebook',
                    $recipientId,
                    $messageText,
                    null, // admin_user_id ไม่รู้ (FB Page Inbox ไม่บอก)
                    [
                        'app_id' => null,
                        'echo' => true,
                        'page_id' => $pageId,
                        'reading_id' => $activeReading?->id,
                        'reading_type' => $activeReading?->reading_type,
                    ],
                );
            } catch (\Throwable $e) {
                // non-blocking — ไม่ throw ออกมา กัน webhook fail
                \Illuminate\Support\Facades\Log::warning(
                    'FacebookWebhook: CaptureAdminQAJob dispatch ล้มเหลว',
                    ['error' => $e->getMessage()]
                );
            }
        }

        // 🎯 (2026-05-17) Manual control mode — แทนที่ auto-takeover เดิม
        //   user spec: "อัตโนมัติมันไม่เวิร์ค ถอนออกไปก่อน"
        //   ถ้าปิด handover → ไม่ process /aistop /aistart (Capture ทำไปแล้วข้างบน)
        if (! ($this->settings->admin_handover_enabled ?? true)) {
            return;
        }

        // 🎯 ตรวจ slash command — admin พิมพ์ /aistop หรือ /aistart เท่านั้น
        //    ที่จะ trigger pause/resume. ข้อความอื่นๆ ของ admin ปล่อยผ่าน (ไม่ takeover อัตโนมัติ)
        $isPauseCommand = $this->takeoverService->detectAdminPauseCommand($messageText);
        $isResumeCommand = $this->takeoverService->detectAdminResumeCommand($messageText);

        if (! $isPauseCommand && ! $isResumeCommand) {
            // admin reply ปกติ — ไม่ auto-takeover (Capture ทำไปแล้วข้างบน)
            return;
        }

        // 🤝 หา reading ล่าสุดของ user นี้ — รวม COMPLETED ที่อยู่ใน Pro Session ด้วย
        $reading = FortuneReading::where(function ($q) use ($recipientId) {
            $q->where('facebook_user_id', $recipientId)
                ->orWhere(function ($sub) use ($recipientId) {
                    $sub->where('platform', 'facebook')
                        ->where('platform_user_id', $recipientId);
                });
        })
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->first();

        // ไม่มี active conversation — สร้าง placeholder เพื่อให้ track ได้
        if (! $reading) {
            if ($isResumeCommand) {
                // resume แต่ไม่มี reading → ไม่ทำอะไร (ไม่มีอะไรให้ resume)
                Log::debug('Facebook /aistart: ไม่มี reading — ข้าม', [
                    'user_id' => $recipientId,
                ]);

                return;
            }
            // /aistop แต่ไม่มี reading → สร้าง placeholder
            $reading = FortuneReading::create([
                'facebook_user_id' => $recipientId,
                'platform' => 'facebook',
                'platform_user_id' => $recipientId,
                'reading_type' => 'basic',
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
                'conversation_state' => ['placeholder' => true, 'source' => 'aistop_command'],
                'questions' => [],
                'ai_response' => '',
                'ai_provider' => 'none',
            ]);
        }

        // /aistart หรือ /ai → resume bot
        if ($isResumeCommand) {
            $this->takeoverService->resume($reading, null, true);
            Log::info('✨ Facebook /aistart: แอดมินสั่งให้บอทกลับมาทำงาน', [
                'reading_id' => $reading->id,
                'user_id' => $recipientId,
            ]);

            return;
        }

        // /aistop → manual takeover
        $this->takeoverService->takeover(
            $reading,
            FortuneReading::TAKEOVER_REASON_MANUAL,
            null,
            null,
            '/aistop',
        );

        Log::info('🛑 Facebook /aistop: แอดมินสั่งให้บอทหยุด', [
            'reading_id' => $reading->id,
            'user_id' => $recipientId,
        ]);
    }

    /**
     * ตรวจสอบว่าแอดมินกำลังดูแล user คนนี้อยู่หรือไม่
     *
     * @param  string  $userId  Facebook User ID
     * @return bool true = แอดมินกำลังดูแล, บอทควรหยุดทำงาน
     */
    protected function isAdminActive(string $userId): bool
    {
        // ใช้ service กลางเพื่อ sync กับ LINE
        return $this->takeoverService->isActiveByPlatform('facebook', $userId);
    }

    /**
     * ประมวลผล direct message (Messenger)
     *
     * รองรับ:
     * - ข้อความ text
     * - รูปภาพ (image attachment)
     * - Quick reply payloads
     * - Conversational fortune telling flow (ใหม่)
     * - Admin Handover: บอทหยุดทำงานเมื่อแอดมินกำลังดูแล
     */
    protected function processMessage(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;

        if (empty($senderId)) {
            return;
        }

        // 🔒 (2026-05-03) H1 — Message ID dedupe (กัน FB webhook retry สร้าง reading ซ้ำ)
        //    FB จะ retry ถ้า server ตอบ non-200 ภายในไม่กี่วินาที — เคยทำให้:
        //      - reading ซ้ำ (start fortune flow 2 ครั้งสำหรับ message เดียว)
        //      - state machine สับสน (ถาม "วันเกิด" สอง message พร้อมกัน)
        //    Cache::add (atomic) — เขียนเฉพาะถ้า key ยังไม่มี = first wins, retry skip
        //    TTL 10 นาที — FB retry จะเสร็จในไม่กี่นาที, ไม่ต้องเก็บนาน
        $mid = $messaging['message']['mid'] ?? null;
        if (! empty($mid)) {
            $dedupeKey = 'fb_webhook_mid:'.$mid;
            if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
                Log::info('🔁 FB webhook: skip duplicate mid (retry/replay)', [
                    'mid' => $mid,
                    'sender_id' => $senderId,
                    'has_text' => ! empty($messaging['message']['text']),
                ]);

                return;
            }
        }

        // 🎯 Quick Reply payload — explicit user selection ต้องผ่านก่อน takeover guard
        //    user spec (2026-05-17): "กดเลือกแพคเกจแล้ว ต้องเข้าโฟลการจ่ายเงิน อย่าขัด"
        $quickReplyPayload = $messaging['message']['quick_reply']['payload'] ?? null;
        if ($quickReplyPayload) {
            $this->handleQuickReply($senderId, $quickReplyPayload);

            return;
        }

        // 🛑 Admin Handover: ถ้าแอดมิน /aistop → บอทหยุด (เฉพาะ chitchat)
        //
        // 🚨 (2026-05-17) FLOW BYPASS — ห้ามหยุด flow จ่ายเงิน/รับคำทำนาย
        //   user spec: "พิมพ์ 99 33 / กดเลือกแพคเกจ ต้องเข้า flow จ่ายเงิน อย่าขัด"
        if ($this->isAdminActive($senderId)) {
            $messageText = $messaging['message']['text'] ?? '';

            if (! $this->takeoverService->shouldBypassTakeover('facebook', $senderId, $messageText)) {
                Log::info('👨‍💼 Admin /aistop: บอทข้าม (chitchat ก่อนเข้า flow)', [
                    'user_id' => $senderId,
                    'message_preview' => mb_substr($messageText, 0, 50),
                ]);

                return;
            }

            Log::info('💰 Admin /aistop active แต่ flow bypass — ดำเนิน flow ต่อ', [
                'user_id' => $senderId,
            ]);
        }

        $messageText = $messaging['message']['text'] ?? '';
        $attachments = $messaging['message']['attachments'] ?? [];

        // 🎯 (2026-05-02) Auto-deliver pending prediction — กัน sticker/emoji silent ignore
        //   user request: "คนแก่ส่ง sticker → ระบบควรส่งคำทำนายเลย ไม่ต้องพิมพ์"
        //   ถ้ามี deep_response รอส่ง → ส่งทันที ไม่ว่า user ส่งอะไรมา (text/sticker/emoji/image)
        //
        // 🚨 (2026-05-17) กฎเด็ดขาด: ต้องอยู่ก่อน customer handoff check — กันคำทำนายค้าง
        //   เคส: ลูกค้าจ่าย 39฿ + พิมพ์ "คุยกับคน" → ต้องได้คำทำนายก่อน + alert admin
        //   (เดิม customer handoff อยู่ก่อน + auto-takeover → คำทำนายไม่ส่ง)
        //   ใหม่: alert-only mode → ส่งคำทำนายก่อน แล้ว handoff ส่ง alert ทีหลัง
        $pendingDelivery = FortuneReading::where('facebook_user_id', $senderId)
            ->where('is_paid', true)
            ->whereNotNull('deep_response')
            ->where('deep_response', '!=', '')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->latest()
            ->first();

        if ($pendingDelivery && ! $pendingDelivery->getConversationState('reading_sent_directly', false)) {
            Log::info('FB: pending deep reading detected → bypass silent rules + deliver', [
                'sender_id' => $senderId,
                'reading_id' => $pendingDelivery->id,
                'has_text' => ! empty($messageText),
                'has_attachments' => ! empty($attachments),
            ]);
            // ใช้ processConversationalMessage ที่มี logic ส่งคำทำนายเต็มในตัว
            // ส่ง text เป็น 'อ่านคำทำนาย' ถ้า user ส่ง sticker/emoji เพื่อ trigger ส่งคำทำนาย
            $effectiveText = $messageText ?: 'อ่านคำทำนาย';
            $this->processConversationalMessage($senderId, $effectiveText);

            return;
        }

        // 🚫 (2026-05-22) Ban guard — user ที่ถูกแบนห้ามบอทคุยด้วย
        //    ⚠️ ต้องอยู่ "หลัง" pendingDelivery — กันลูกค้าจ่ายแล้วถูกแบนกลางทาง คำทำนายค้าง
        //    Anti-spam: ตอบเตือนครั้งแรกเท่านั้น (cooldown 1 ชม.) หลังจากนั้นเงียบ
        //    แอดมินยังคุยได้ผ่าน Page Inbox / admin panel (เพราะใช้ Page API ตรง)
        $activeBan = $this->banService->getActiveBan('facebook', $senderId);
        if ($activeBan !== null) {
            if ($this->banService->shouldNotify($activeBan)) {
                $banMessage = $this->banService->buildBanReplyMessage($activeBan);
                try {
                    $this->facebookService->sendMessage($senderId, $banMessage);
                    $this->banService->recordNotification($activeBan);
                } catch (\Throwable $e) {
                    Log::warning('FB ban: ส่งข้อความเตือนแบนล้มเหลว (non-blocking)', [
                        'sender_id' => $senderId,
                        'ban_id' => $activeBan->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('🚫 FB: ignore banned user', [
                'sender_id' => $senderId,
                'ban_id' => $activeBan->id,
                'attempt_count' => $activeBan->attempt_count,
                'permanent' => $activeBan->isPermanent(),
            ]);

            return;
        }

        // 🔒 (2026-05-20) IN-PREDICTION guard — ห้าม handoff/affiliate ระหว่างทำนาย
        //    User spec: ระหว่างทำนาย ไม่ต้องคุยกับคน ไม่ต้องโยน affiliate
        //    เดี๋ยวแอดมินจะแทคเอง ถ้าจำเป็น (admin /aistop ยัง win)
        $inPredictionGuardActive = false;
        try {
            $inPredictionGuardActive = $this->conversationService->isInPrediction($senderId);
        } catch (\Throwable $e) {
            Log::debug('FB: in-prediction guard check fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }

        // 🙋 (2026-05-17) Customer handoff — alert-only mode (ไม่ takeover อัตโนมัติ)
        //    ลูกค้าพิมพ์ "คุยกับคน" → ส่งข้อความ "รอแอดมิน" + alert admin
        //    ⚠️ ต้องอยู่หลัง pendingDelivery — กันลูกค้าจ่ายแล้วพิมพ์ "คุยกับคน" คำทำนายค้าง
        //    🔒 (2026-05-20) skip ถ้าอยู่ระหว่างทำนาย (ห้ามแทรก)
        if (! $inPredictionGuardActive
            && ! empty($messageText)
            && $this->takeoverService->detectCustomerHandoffRequest($messageText)) {
            $this->handleCustomerHandoffRequest($senderId, $messageText);

            return;
        }

        // 💰 (2026-05-17) Affiliate interest — opt-in only: คนทักว่าอยากทำ/อยากแชร์/ขอลิงก์
        //    ส่ง Button Template "เข้าระบบด้วย Facebook" → FB OAuth → หน้าแชร์ลิงก์
        //    ⚠️ ต้องอยู่หลัง pendingDelivery — กันลูกค้าจ่ายแล้วถามอยาก-ทำ-แชร์ตอนรอคำทำนาย
        //    ลูกค้าที่จ่ายเงินแล้ว = ส่งคำทำนายก่อน, ถามต่อในวันถัดไป
        //    🔒 (2026-05-20) skip ถ้าอยู่ระหว่างทำนาย (ห้ามแทรก)
        if (! $inPredictionGuardActive
            && ! empty($messageText)
            && $this->conversationService->looksLikeAffiliateInterestRequest($messageText)) {
            $this->sendFacebookAffiliateInvite($senderId);

            return;
        }

        // 🚫 (2026-04-28) Spam guard — ปิดการตอบสนองคนป่วน
        // กัน: ส่งวิดีโอ/รูปสุ่ม/ลิงก์/ข้อความซ้ำ ๆ
        if ($this->isUserSpamming($senderId, $messageText, $attachments)) {
            // ไม่ตอบ ไม่ log error — แค่ log info สำหรับ audit
            Log::info('🚫 Fortune: ignore spam message (silenced)', [
                'sender_id' => $senderId,
                'has_attachments' => ! empty($attachments),
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            return;
        }

        // 🔇 (2026-05-01) Silent rule — ลูกค้าสั่ง: รูป/ลิงก์/วิดีโอ/อีโมจิ/sticker นอก fortune flow → ห้ามตอบ
        //    เช็ค active fortune flow ก่อน (มี active = อยู่กลางขั้นตอนทำนาย/ชำระ)
        $hasActiveFortune = FortuneReading::where('facebook_user_id', $senderId)
            ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
            ->exists();

        // ตรวจสอบว่ามีรูปภาพแนบมาหรือไม่
        $userImageUrl = null;
        if (! empty($attachments)) {
            $userImageUrl = $this->facebookService->extractImageFromAttachments($attachments);

            $hasSticker = false;
            foreach ($attachments as $att) {
                if (($att['type'] ?? '') === 'sticker' || isset($att['payload']['sticker_id'])) {
                    $hasSticker = true;
                    break;
                }
            }

            // 🛡️ (2026-05-20 Patch 1+2) Celtic paid 99฿ bypass — ตรวจก่อนทุกอย่าง
            //   เหตุผล: ลูกค้าจ่าย 99฿ ส่งรูป → ต้องได้ vision ทันที ห้ามถูก spam block / classify ผิด
            //   • Patch 1: skip spam guard (paid → ไม่ใช่ spam แม้ส่งหลายรูป)
            //   • Patch 2: skip classifier (Celtic + picked=10 = ส่งให้ vision ตรง ไม่เสี่ยง classify ผิด)
            $isCelticPaidVision = false;
            if ($userImageUrl) {
                $celticPaidCheck = FortuneReading::where('facebook_user_id', $senderId)
                    ->whereIn('conversation_status', [
                        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                        FortuneReading::STATUS_CELTIC_GENERATING,
                    ])
                    ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->latest()
                    ->first();

                if ($celticPaidCheck && $celticPaidCheck->getCelticPickedCount() >= 10) {
                    $isCelticPaidVision = true;
                    Log::info('FB: Celtic paid vision path → bypass spam+classifier', [
                        'sender_id' => $senderId,
                        'reading_id' => $celticPaidCheck->id,
                    ]);

                    // ส่ง vision ตรง — ไม่ผ่าน spam/classifier
                    $this->handleCelticVisionImage($senderId, $userImageUrl, $messageText, $celticPaidCheck);

                    return;
                }
            }

            // 🚫 (2026-05-20 Phase 3b.5) Spam guard — ก่อน dispatch อื่น
            //   ลูกค้าส่งรูปรัวๆ (>= 3/10s) → silent cooldown 60s
            //   sustained (>= 5/60s) → cooldown 5 นาที + alert admin
            //   user spec 2026-05-20: "ถ้าส่งรูปรัวๆ จะถือว่าสแปม"
            //   ⚠️ Patch 1: Celtic paid bypass แล้ว — มาถึงตรงนี้ = non-paid Celtic / no active
            if ($userImageUrl) {
                try {
                    $spamGuard = app(ImageSpamGuard::class);
                    $spamCheck = $spamGuard->check('facebook', $senderId);
                    if ($spamCheck['blocked']) {
                        Log::info('FB: image spam cooldown active → silent', [
                            'sender_id' => $senderId,
                            'level' => $spamCheck['level'],
                            'cooldown_until' => $spamCheck['cooldown_until'],
                        ]);

                        return;
                    }
                    // Record this image — อาจ trigger cooldown ถ้าครบเกณฑ์
                    $spamRecord = $spamGuard->record('facebook', $senderId);
                    if ($spamRecord['triggered']) {
                        Log::info('FB: image spam triggered (silent)', [
                            'sender_id' => $senderId,
                            'level' => $spamRecord['level'],
                            'count' => $spamRecord['count'],
                        ]);

                        return;
                    }
                } catch (\Throwable $spamErr) {
                    // Spam guard fail = ไม่ block flow ปกติ
                    Log::debug('FB: spam guard exception (non-blocking)', [
                        'error' => $spamErr->getMessage(),
                    ]);
                }
            }

            // 🆕 (2026-05-20 Phase 3b.5) Classify image intent ก่อน routing
            //   ใช้ Gemini Flash (ราคาถูก) เพื่อ first-pass classify:
            //     • payment_slip       → existing slip flow
            //     • fortune_subject    → Celtic vision (ถ้า Celtic active+10ใบ) OR hint
            //     • general_photo      → chat reply (เมื่อ no active)
            //     • emoji_sticker      → silent (ลูกค้าส่งสติ๊กเกอร์ไม่ต้องตอบทุกครั้ง)
            //     • nonsense           → silent
            //   Fallback: ถ้า classifier fail → fall through ไป existing logic (zero regression)
            //   ⚠️ Patch 2: Celtic paid vision bypassed แล้ว — ตรงนี้ classify เฉพาะ non-paid path
            $intent = null;
            if ($userImageUrl) {
                try {
                    $contextHint = $hasActiveFortune ? 'celtic_active' : 'chat_normal';
                    $intentResult = app(ImageIntentClassifier::class)->classify($userImageUrl, $contextHint);
                    $intent = $intentResult['intent'] ?? null;

                    Log::info('FB: image classified', [
                        'sender_id' => $senderId,
                        'intent' => $intent,
                        'confidence' => $intentResult['confidence'] ?? null,
                        'reason' => $intentResult['reason'] ?? null,
                        'context_hint' => $contextHint,
                    ]);
                } catch (\Throwable $clErr) {
                    Log::debug('FB: classifier exception (fall through to legacy logic)', [
                        'error' => $clErr->getMessage(),
                    ]);
                }
            }

            // 🤐 Emoji/nonsense → silent (ไม่ตอบ ไม่ flood ลูกค้า)
            if (in_array($intent, [ImageIntentClassifier::INTENT_EMOJI_STICKER, ImageIntentClassifier::INTENT_NONSENSE], true)) {
                Log::debug('FB: emoji/nonsense → silent', [
                    'sender_id' => $senderId,
                    'intent' => $intent,
                ]);

                return;
            }

            // 🔇 ไม่มี active flow + attachment ใดๆ → silent (ทุกประเภท)
            //   เคสที่ classifier ไม่ active (intent=null) — เก็บ behavior เดิม
            //   เคสที่ classifier บอก general_photo + no active flow → ตอบ chat สั้น ๆ
            if (empty($messageText) && ! $hasActiveFortune) {
                // 🆕 (2026-05-20) Classifier บอก general_photo / fortune_subject → ตอบเชิญดูดวง
                if ($intent === ImageIntentClassifier::INTENT_FORTUNE_SUBJECT) {
                    $this->facebookService->sendMessage(
                        $senderId,
                        "🌙 เห็นเจ้าชะตาส่งรูปมา — สนใจดูดวงเรื่องคนในรูปใช่ไหมคะ?\n\n"
                        ."✨ พิมพ์ \"ดูดวง\" เพื่อเริ่มทำนายเลยค่ะ"
                    );

                    Log::info('FB: classifier=fortune_subject (no active) → invite ดูดวง', [
                        'sender_id' => $senderId,
                    ]);

                    return;
                }
                if ($intent === ImageIntentClassifier::INTENT_PAYMENT_SLIP) {
                    $this->facebookService->sendMessage(
                        $senderId,
                        "🌙 ดูเหมือนเป็นสลิปการโอนเงิน — แต่แม่หมอยังไม่มีรายการที่ต้องชำระสำหรับเจ้าชะตาตอนนี้นะคะ\n\n"
                        ."✨ ถ้าอยากดูดวง พิมพ์ \"ดูดวง\" เพื่อเริ่มเลยค่ะ"
                    );

                    Log::info('FB: classifier=payment_slip (no active) → แจ้งไม่มีรายการ', [
                        'sender_id' => $senderId,
                    ]);

                    return;
                }
                // general_photo / unknown → silent เดิม (ไม่ตอบรูปสุ่ม)
                Log::debug('FB: silent ignore attachment (no active fortune flow)', [
                    'sender_id' => $senderId,
                    'has_image' => ! empty($userImageUrl),
                    'has_sticker' => $hasSticker,
                    'intent' => $intent,
                    'attachment_types' => array_column($attachments, 'type'),
                ]);

                return;
            }

            // 📸 (2026-05-16) Celtic Pro Session + image → vision AI วิเคราะห์
            //    ลูกค้าจ่าย 99 เปิดไพ่ครบ → ส่งรูปมา → ส่งให้ vision AI แทนที่จะตอบ "สลิป"
            //    🆕 (Phase 3b.5) Skip ถ้า classifier บอก payment_slip — ส่ง slip flow แทน
            if ($userImageUrl && $intent !== ImageIntentClassifier::INTENT_PAYMENT_SLIP) {
                $celticVisionReading = FortuneReading::where('facebook_user_id', $senderId)
                    ->whereIn('conversation_status', [
                        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                        FortuneReading::STATUS_CELTIC_GENERATING,
                    ])
                    ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->latest()
                    ->first();

                if ($celticVisionReading && $celticVisionReading->getCelticPickedCount() >= 10) {
                    $this->handleCelticVisionImage($senderId, $userImageUrl, $messageText, $celticVisionReading);

                    return;
                }
            }

            // 📸 มี active flow + รูป (อาจเป็นสลิป) → handleSlipImageOnly
            //   จะตอบเฉพาะ PENDING_PAYMENT + PAID (พฤติกรรมเดิม)
            if (empty($messageText) && $userImageUrl && $hasActiveFortune) {
                $this->handleSlipImageOnly($senderId);

                return;
            }

            // 🔮 (2026-05-04) Celtic active state — sticker/non-image ต้องไม่ silent
            //    ลูกค้าจ่าย 99฿ แล้วส่ง sticker → ระบบเงียบ → ลูกค้าเข้าใจผิดว่าบอทตาย
            //    ต้องนำกลับไปเปิดไพ่/พิมพ์คำถามต่อ
            if (empty($messageText)) {
                $celticActive = FortuneReading::where('facebook_user_id', $senderId)
                    ->whereIn('conversation_status', FortuneReading::CELTIC_ACTIVE_STATUSES)
                    ->latest()
                    ->first();

                if ($celticActive) {
                    $reason = $hasSticker ? 'sticker' : 'generic';
                    $resume = app(CelticCrossService::class)->buildResumeMessage($celticActive, $reason);
                    $opts = [];
                    if (! empty($resume['quick_replies'])) {
                        $opts['quick_replies'] = $resume['quick_replies'];
                    }

                    $this->facebookService->sendMessage($senderId, $resume['message'], $opts);

                    Log::info('FB: Celtic active + non-image attachment → นำกลับเปิดไพ่/ถาม', [
                        'sender_id' => $senderId,
                        'reading_id' => $celticActive->id,
                        'celtic_status' => $celticActive->conversation_status,
                        'has_sticker' => $hasSticker,
                    ]);

                    return;
                }
            }

            // 🔇 sticker / video / audio / file ระหว่างกลาง active flow แต่ไม่ใช่รูป → silent
            //   (ไม่อยากรบกวนถ้าผู้ใช้กำลังคิดวันเกิด/คำถาม — non-Celtic)
            if (empty($messageText)) {
                Log::debug('FB: silent ignore non-image attachment in active flow', [
                    'sender_id' => $senderId,
                    'has_sticker' => $hasSticker,
                    'attachment_types' => array_column($attachments, 'type'),
                ]);

                return;
            }
        }

        // 🔇 (2026-05-01) ไม่มี active flow + ข้อความเป็นแค่ลิงก์/อีโมจิล้วน → silent
        //   (ป้องกันลูกค้าทดลองส่ง junk ทำให้บอทพยายามตอบไม่ตรงประเด็น)
        if (! empty($messageText) && ! $hasActiveFortune && $this->isNonFortuneNoise($messageText)) {
            Log::debug('FB: silent ignore non-fortune noise (link/emoji-only, no active flow)', [
                'sender_id' => $senderId,
                'preview' => mb_substr($messageText, 0, 60),
            ]);

            return;
        }

        // ใช้ Conversational Flow ใหม่
        $this->processConversationalMessage($senderId, $messageText);

        // 👁️ Follow-page prompt: ส่งหลัง bot ตอบ — gated ที่ DB
        //   (2026-05-02) ปรับเป็น "ครั้งแรกของวัน" — skip ถ้าติดตามแล้ว / ส่งวันนี้ไปแล้ว
        //   user request: "ทักแชทครั้งแรกของวันนั้น ถ้ายังไม่ติดตาม ให้ปรากฏเสมอ"
        $this->facebookService->sendFollowPagePromptToUser($senderId);
    }

    /**
     * จัดการเมื่อลูกค้าส่งรูปภาพอย่างเดียว (ไม่มีข้อความ)
     *
     * ปัญหาเดิม: ตอบ generic "พิมพ์ดูดวง" — ลูกค้าที่ส่งสลิปกังวลว่าระบบไม่ได้รับ
     * ใหม่: ตรวจ active reading → ตอบตามบริบท + ปุ่ม "แจ้งชำระเงิน"/"เช็คสถานะ"
     */
    /**
     * 📸 (2026-05-16) Celtic Pro Session vision flow — รับรูป (URL ตรงจาก FB CDN)
     *
     * FB ส่ง public URL มาเลย — ไม่ต้อง download (OpenAI ดึงได้)
     *
     * 🔒 OpenAI only — ถ้าไม่มี sensitive key vision-capable → แจ้งลูกค้าตรงๆ
     */
    protected function handleCelticVisionImage(
        string $senderId,
        string $imageUrl,
        string $userText,
        \App\Models\FortuneReading $reading
    ): void {
        try {
            // 1. ส่ง pre-reply "กำลังพิมพ์" ทันที
            $this->facebookService->sendMessage(
                $senderId,
                '🌙 แม่หมอจันทรากำลังดูภาพของเจ้าชะตา... ✨'
            );

            // 2. ตั้ง state = GENERATING (กัน spam)
            $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_GENERATING]);

            // 3. Call vision AI (FB URL ส่งให้ OpenAI ตรงๆ ได้)
            $service = app(\App\Services\CelticCrossService::class);
            $result = $service->askQuestionWithImage($reading, $imageUrl, $userText);

            // 4. กลับ AWAITING
            $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            if (! $result['success']) {
                $this->facebookService->sendMessage(
                    $senderId,
                    $result['message'] ?? "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
                );

                return;
            }

            // 5. ส่งคำตอบ + footer
            $reading->refresh();
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $qaWindow = (int) (\App\Models\FortuneTellingSetting::getSettings()->celtic_cross_qa_window_minutes ?? 30);
            $timeHint = $remainingMin !== null
                ? "⏳ เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที"
                : "⏳ เจ้าชะตาคุยต่อได้ภายใน {$qaWindow} นาทีนับจากคำทำนายแรก";

            $message = $result['response']
                ."\n\n──────────────────────\n"
                .$timeHint."\n"
                .'💬 พิมพ์ต่อได้เรื่อยๆ — แม่หมอรับฟังจนจุใจ';

            $this->facebookService->sendQuickReplies($senderId, $message, [
                ['content_type' => 'text', 'title' => '📜 เลิกทำนายและสรุปผล', 'payload' => 'CELTIC_END_ASK'],
            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

            \Log::info('FB Celtic vision สำเร็จ', [
                'sender_id' => $senderId,
                'reading_id' => $reading->id,
                'image_url' => $imageUrl,
            ]);
        } catch (\Throwable $e) {
            // Reset state
            try {
                $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
            } catch (\Throwable $stateErr) {
                // ignore
            }

            \Log::error('FB Celtic vision exception', [
                'sender_id' => $senderId,
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $this->facebookService->sendMessage(
                $senderId,
                "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
            );
        }
    }

    protected function handleSlipImageOnly(string $senderId): void
    {
        try {
            // หา reading ที่ user กำลังใช้งานอยู่
            //   🔮 (2026-05-04) ครอบคลุม Celtic active states ด้วย — กันลูกค้าส่งสลิประหว่างเปิดไพ่
            //   แล้วโดนข้อความ "แม่หมอกำลังคำนวณ" ที่ผิดความจริง (ที่จริงต้องเปิดไพ่ต่อ)
            $activeReading = FortuneReading::where('facebook_user_id', $senderId)
                ->whereIn('conversation_status', array_merge(
                    [
                        FortuneReading::STATUS_PENDING_PAYMENT,
                        FortuneReading::STATUS_PAID,
                    ],
                    FortuneReading::CELTIC_ACTIVE_STATUSES
                ))
                ->latest()
                ->first();

            // ⏰ ถ้าไม่พบ active → ตรวจบิลที่เพิ่งจ่าย/cancel ใน 30 นาที (อาจเป็นเคสลูกค้าจ่ายแล้ว conversation ปิดไปแล้ว)
            if (! $activeReading) {
                $recentReading = FortuneReading::where('facebook_user_id', $senderId)
                    ->where(function ($q) {
                        $q->where('paid_at', '>=', now()->subMinutes(30))
                            ->orWhere('updated_at', '>=', now()->subMinutes(30));
                    })
                    ->whereNotNull('bill_reference')
                    ->latest('updated_at')
                    ->first();

                if ($recentReading && $recentReading->is_paid) {
                    $activeReading = $recentReading;
                }
            }

            // 🔮 (2026-05-04) Celtic active state — ลูกค้าส่งรูประหว่างเปิดไพ่/ถามคำถาม
            //    → ห้ามตอบ "แม่หมอกำลังคำนวณ" — ต้องนำกลับไปเปิดไพ่ต่อ/พิมพ์คำถาม
            //    เคสลูกค้าจ่าย Celtic 99฿ แล้วส่งสลิปอีก หรือส่งรูปสุ่ม → จะเข้า branch นี้
            if ($activeReading && in_array($activeReading->conversation_status, FortuneReading::CELTIC_ACTIVE_STATUSES, true)) {
                $resume = app(CelticCrossService::class)->buildResumeMessage($activeReading, 'image');
                $opts = [];
                if (! empty($resume['quick_replies'])) {
                    $opts['quick_replies'] = $resume['quick_replies'];
                }

                $this->facebookService->sendMessage($senderId, $resume['message'], $opts);

                Log::info('Facebook: รับรูประหว่าง Celtic active → นำกลับเปิดไพ่/ถาม', [
                    'sender_id' => $senderId,
                    'reading_id' => $activeReading->id,
                    'celtic_status' => $activeReading->conversation_status,
                    'picked_count' => $activeReading->getCelticPickedCount(),
                ]);

                return;
            }

            // 🟢 PENDING_PAYMENT — ลูกค้าส่งสลิป → ปลอบ + บังคับให้กด "แจ้งชำระเงิน"
            // (ครอบคลุม Celtic Cross 99฿ ด้วย — ทั้ง deep และ celtic ใช้ flow ตอบเดียวกันตรงนี้)
            if ($activeReading && in_array($activeReading->conversation_status, FortuneReading::PENDING_PAYMENT_STATUSES, true)) {
                $billRef = $activeReading->bill_reference ?? '-';
                $message = "🌙 ขอบคุณค่ะที่ส่งสลิปมาให้แม่หมอ\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                    ."💡 ระบบใช้ SMS Banking ตรวจสอบอัตโนมัติ — ไม่ต้องส่งสลิปให้แอดมินดูค่ะ\n\n"
                    ."🔔 *กรุณากดปุ่ม \"แจ้งชำระเงิน\" หรือพิมพ์ \"โอนแล้ว\"* เพื่อให้ระบบเช็คเร็วขึ้น\n"
                    ."ระบบจะตรวจสอบและตัดบิลภายใน 1-3 นาทีค่ะ ✨\n\n"
                    .'🪐 ระหว่างรอ ใจเย็นๆ นะคะ — ดาวเจ้าชนะของเจ้าชะตากำลังเรียงตัว';

                $this->facebookService->sendMessage($senderId, $message, [
                    'quick_replies' => [
                        ['title' => '✅ แจ้งชำระเงิน', 'payload' => 'REPORT_PAYMENT'],
                        ['title' => '📋 เช็คสถานะ', 'payload' => 'check_payment'],
                    ],
                ]);

                Log::info('Facebook: รับสลิประหว่าง PENDING_PAYMENT → ปลอบ + ขอกด แจ้งชำระเงิน', [
                    'sender_id' => $senderId,
                    'reading_id' => $activeReading->id,
                    'bill_reference' => $billRef,
                ]);

                return;
            }

            // 🟢 PAID — ลูกค้าจ่ายแล้ว ระหว่าง AI ประมวลผล → ปลอบ "แม่หมอกำลังคำนวณ"
            //   🔮 (2026-05-04) ยกเว้น Celtic — เพราะ Celtic จบแล้วก็ is_paid=true + empty(deep_response)
            //      ถ้าไม่ filter จะส่ง "AI กำลังคำนวณ" ทั้งที่จริงๆ Celtic เสร็จแล้ว
            //      Celtic ACTIVE state ถูก catch ใน Celtic branch ก่อนหน้านี้ (line ~1356) แล้ว
            //      Celtic COMPLETED → ตอบเป็นการขอดูคำทำนายล่าสุดแทน
            if ($activeReading && $activeReading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS
                && ($activeReading->conversation_status === FortuneReading::STATUS_PAID
                    || ($activeReading->is_paid && empty($activeReading->deep_response)))) {
                $billRef = $activeReading->bill_reference ?? '-';
                $message = "✅ ระบบรับเงินไปเรียบร้อยแล้วค่ะ\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                    ."🌙 *แม่หมอกำลังคำนวณดวงดาวให้เจ้าชะตาอยู่*\n"
                    ."ใช้เวลาประมาณ 1-3 นาที — รอสักครู่ คำทำนายจะส่งไปให้ทันทีเมื่อเสร็จ ✨\n\n"
                    .'💡 ห้ามสร้างบิลใหม่นะคะ (ป้องกันจ่ายซ้ำ)';

                $this->facebookService->sendMessage($senderId, $message);

                Log::info('Facebook: รับสลิประหว่าง PAID → ปลอบ แม่หมอกำลังคำนวณ', [
                    'sender_id' => $senderId,
                    'reading_id' => $activeReading->id,
                ]);

                return;
            }

            // 🔮 (2026-05-04) Celtic COMPLETED — ลูกค้าส่งรูปหลัง Celtic จบ
            //   เคยเป็น bug: ตกเข้า PAID branch (เพราะ is_paid+empty deep_response)
            //   → "AI กำลังคำนวณ" ผิด — Celtic จบไปแล้ว ไม่ใช่กำลังคำนวณ
            //   ตอนนี้: ตอบขอบคุณ + แนะนำให้พิมพ์ "ดูคำทำนายล่าสุด" เพื่อดู Q&A list
            if ($activeReading
                && $activeReading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                && $activeReading->is_paid) {
                $billRef = $activeReading->bill_reference ?? '-';
                $message = "💖 ขอบคุณค่ะ — ได้รับรูปแล้ว\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                    ."🌟 *การดูดวง Celtic Cross ของเจ้าชะตาเสร็จไปแล้ว*\n\n"
                    ."💡 หากต้องการอ่านคำทำนาย/คำถามที่ถามไปอีกครั้ง:\n"
                    ."    → พิมพ์ *\"ดูคำทำนายล่าสุด\"*\n\n"
                    .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ ✨';

                $this->facebookService->sendMessage($senderId, $message);

                Log::info('Facebook: รับรูปหลัง Celtic จบ → แนะให้พิมพ์ดูคำทำนายล่าสุด', [
                    'sender_id' => $senderId,
                    'reading_id' => $activeReading->id,
                ]);

                return;
            }

            // ⚪ (2026-05-01) ไม่มี active reading → silent (ผู้ใช้สั่ง: ห้ามตอบรูปนอกโฟล)
            //    เคสนี้เกิดเมื่อ user ส่งรูปขณะที่กำลังจะหมดอายุ paid window (>30 นาที)
            //    หรือ status เปลี่ยนเป็น COMPLETED ระหว่างที่ส่ง — ไม่อยากรบกวน
            Log::debug('Facebook: handleSlipImageOnly — no active reading → silent', [
                'sender_id' => $senderId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Facebook: handleSlipImageOnly ล้มเหลว — silent', [
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
            // silent — กันสแปม
        }
    }

    /**
     * 🔇 (2026-05-01) ตรวจว่าข้อความเป็น "noise" ที่ไม่เกี่ยวกับการดูดวง
     *
     * คืน true เมื่อข้อความเป็น:
     *   - ลิงก์/URL ล้วน (http/https/www/.com)
     *   - อีโมจิล้วน (ไม่มีอักษรไทย/อังกฤษ/ตัวเลข)
     *
     * ใช้กรองข้อความตอน*ไม่มี active fortune flow* — ไม่ตอบ ไม่เปลือง AI
     *
     * @param  string  $text  ข้อความที่ลูกค้าส่งมา
     */
    protected function isNonFortuneNoise(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return false;
        }

        // ลิงก์ล้วน — ตัด URL ออกแล้วเหลือ text < 5 chars
        $withoutUrls = preg_replace('#https?://\S+|www\.\S+|\b\S+\.(com|net|org|co|io|ai|app|in|me|tv)\b#iu', '', $trimmed) ?? $trimmed;
        if (mb_strlen(trim($withoutUrls)) < 5 && $withoutUrls !== $trimmed) {
            return true; // ลิงก์ล้วน
        }

        // อีโมจิล้วน — ลบ emoji + space แล้วเหลือว่าง
        // ครอบคลุม emoji unicode ranges + symbols
        $withoutEmoji = preg_replace(
            '#[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}\x{200D}\x{FE0F}\s]+#u',
            '',
            $trimmed
        ) ?? $trimmed;
        if (trim((string) $withoutEmoji) === '') {
            return true; // อีโมจิ/space ล้วน
        }

        return false;
    }

    /**
     * 🆓 ปุ่ม "ดูดวงฟรี" จาก welcome — ส่งข้อความขอคำถาม (ไม่ใช้ category quick replies)
     *
     * (2026-05-01)
     *   - ถ้า admin ปิดบริการฟรี → ทะลุไป tier menu (39/99)
     *   - ⚠️ ห้ามใช้ FORTUNE_LOVE / FORTUNE_WORK ฯลฯ เป็น quick reply ที่นี่
     *     เพราะ payload เหล่านั้น dispatch text "ดูดวงความรัก..." → trigger tier menu (paid)
     *     ขัดเจตนา "ฟรี" — ลูกค้าคาดหวังฟรีแต่จะได้เมนูจ่ายเงิน
     *   - ทางออก: ขอให้ลูกค้าพิมพ์คำถามตรงๆ (ไม่ขึ้นต้นด้วย "ดูดวง") → free quota เปิดทำงาน
     */
    protected function handleFortuneFreePicker(string $senderId): void
    {
        // ถ้า free ปิด → ทะลุไป tier menu (39 vs 99)
        if (! $this->settings->isFreeReadingEnabled()) {
            $this->processConversationalMessage($senderId, 'ดูดวง');

            return;
        }

        $message = "🌙 *ดูดวงฟรีพร้อมแล้วค่ะ* ✨\n\n"
            ."💡 พิมพ์ *คำถามที่อยากรู้* มาได้เลย เช่น:\n"
            ."  ✦ \"ความรักช่วงนี้จะเป็นยังไง\"\n"
            ."  ✦ \"การงานปีนี้จะดีขึ้นไหม\"\n"
            ."  ✦ \"การเงินจะเข้ามาเมื่อไหร่\"\n"
            ."  ✦ \"สุขภาพช่วงนี้ต้องระวังอะไร\"\n\n"
            .'🌙 หมอจันทราพร้อมรับฟังเสมอค่ะ';

        $this->facebookService->sendMessage($senderId, $message);
    }

    /**
     * 💰 (2026-05-17) ส่ง Button Template "เข้าระบบด้วย Facebook" → หน้าแชร์ลิงก์
     *
     * Flow:
     * 1. ลูกค้าทักว่า "อยากทำ" / "อยากแชร์" / "ขอลิงก์" → trigger จาก looksLikeAffiliateInterestRequest
     * 2. บอทส่ง Button Template + 3 ปุ่ม web_url ผ่าน /auth/facebook?redirect=...
     * 3. ลูกค้ากด → FB OAuth (FacebookLoginController) → auto-match facebook_user_id/email
     *    → redirect ไปหน้าเป้าหมาย (recruit/wallet/tree)
     *
     * Rate limit: 1 ครั้ง / 6 ชม. ต่อ user (กันสแปม แต่ไม่บล็อกการถามซ้ำในวันถัดไป)
     */
    protected function sendFacebookAffiliateInvite(string $senderId): void
    {
        // กันส่งซ้ำถี่ — 6 ชม. ต่อ user
        $cacheKey = "fortune:fb_affiliate_invite:{$senderId}";
        if (Cache::has($cacheKey)) {
            Log::debug('FB Affiliate Invite: ข้ามเพราะเพิ่งส่งไปไม่ถึง 6 ชม.', [
                'sender_id' => $senderId,
            ]);

            // ส่งข้อความสั้นๆ บอกว่ากดปุ่มจากข้อความก่อนหน้าได้เลย
            $this->facebookService->sendMessage(
                $senderId,
                "🔗 กดปุ่ม \"เข้าเว็บด้วย Facebook\" จากข้อความก่อนหน้าได้เลยค่ะ\n\n"
                .'หรือเข้าตรงๆ ที่: '.url('/auth/facebook')
            );

            return;
        }

        try {
            // สร้างลิงก์ FB OAuth พร้อม redirect ไปหน้าเป้าหมาย
            $recruitUrl = url('/auth/facebook?redirect='.urlencode(route('user.fortune-referral.recruit', [], false)));
            $walletUrl = url('/auth/facebook?redirect='.urlencode(route('user.wallet.index', [], false)));
            $treeUrl = url('/auth/facebook?redirect='.urlencode(route('user.fortune-referral.tree', [], false)));

            $payload = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => "🎉 ดีใจที่อยากร่วมทีมค่ะ!\n\n"
                            ."💰 แชร์ลิงก์ให้เพื่อนดูดวง\n"
                            ."รับ 10 บาท/บิล เข้ากระเป๋าตลอดไป\n\n"
                            ."👇 กดปุ่มด้านล่างเข้าระบบด้วย Facebook\n"
                            .'(ใช้ FB เดียวกันนี้ — เป็นสมาชิกอัตโนมัติ)',
                        'buttons' => [
                            [
                                'type' => 'web_url',
                                'url' => $recruitUrl,
                                'title' => '🚀 รับลิงก์แชร์',
                                'webview_height_ratio' => 'full',
                            ],
                            [
                                'type' => 'web_url',
                                'url' => $walletUrl,
                                'title' => '💼 ดูกระเป๋าเงิน',
                                'webview_height_ratio' => 'full',
                            ],
                            [
                                'type' => 'web_url',
                                'url' => $treeUrl,
                                'title' => '📊 ดูสายงาน',
                                'webview_height_ratio' => 'full',
                            ],
                        ],
                    ],
                ],
            ];

            $sent = $this->facebookService->sendButtonTemplate($senderId, $payload);

            if ($sent) {
                Cache::put($cacheKey, true, now()->addHours(6));
                Log::info('💰 FB Affiliate Invite: ส่ง button template สำเร็จ', [
                    'sender_id' => $senderId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('FB Affiliate Invite: ส่ง button template ล้มเหลว', [
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);

            // Fallback — ส่ง text + URL ตรงๆ
            try {
                $fallbackUrl = url('/auth/facebook?redirect=/user/fortune-referral/recruit');
                $this->facebookService->sendMessage(
                    $senderId,
                    "🎉 ดีใจที่อยากร่วมทีมค่ะ!\n\n"
                    ."💰 แชร์ลิงก์ให้เพื่อนดูดวง รับ 10 บาท/บิล\n\n"
                    ."👇 เข้าระบบด้วย Facebook:\n{$fallbackUrl}"
                );
            } catch (\Throwable $fallbackErr) {
                Log::error('FB Affiliate Invite: fallback ก็ล้มเหลว', [
                    'sender_id' => $senderId,
                    'error' => $fallbackErr->getMessage(),
                ]);
            }
        }
    }

    /**
     * จัดการเมื่อลูกค้าขอคุยกับคนจริง (customer handoff request)
     *
     * 🎯 (2026-05-17) Alert-only mode — ไม่ takeover อัตโนมัติ
     *   user spec: ลูกค้าเลือก option B — "Alert only ให้ admin"
     *
     * Flow:
     * 1. หาหรือสร้าง reading + log การขอ (FortuneTakeoverLog action=message)
     * 2. แจ้งลูกค้าว่าจะแจ้งแอดมิน (ไม่ทำ takeover)
     * 3. ส่ง alert ให้ admin ผ่าน LINE OA push (LineAlertService)
     * 4. Admin ดูใน Page Inbox + พิมพ์ /aistop เองเมื่อพร้อมตอบ
     */
    protected function handleCustomerHandoffRequest(string $senderId, string $messageText): void
    {
        // หา active reading ของ user นี้
        $reading = FortuneReading::where(function ($q) use ($senderId) {
            $q->where('facebook_user_id', $senderId)
                ->orWhere(function ($sub) use ($senderId) {
                    $sub->where('platform', 'facebook')
                        ->where('platform_user_id', $senderId);
                });
        })
            ->latest()
            ->first();

        // ถ้าไม่มี reading เลย → สร้าง placeholder เพื่อให้ track ได้
        if (! $reading) {
            $reading = FortuneReading::create([
                'facebook_user_id' => $senderId,
                'platform' => 'facebook',
                'platform_user_id' => $senderId,
                'reading_type' => 'basic',
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
                'conversation_state' => ['placeholder' => true, 'source' => 'customer_request_alert'],
                'questions' => [],
                'ai_response' => '',
                'ai_provider' => 'none',
            ]);
        }

        // 📝 บันทึก log การขอคุยกับคน (สำหรับ admin panel + audit)
        try {
            \App\Models\FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => null,
                'action' => \App\Models\FortuneTakeoverLog::ACTION_MESSAGE,
                'reason' => FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST,
                'platform' => 'facebook',
                'message' => mb_substr('🙋 ลูกค้าขอคุยกับคน: '.$messageText, 0, 2000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FB Customer Handoff: log ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }

        // 🌙 แจ้งลูกค้าว่าจะแจ้งแอดมิน (ไม่ takeover — ลูกค้าจะรอแอดมินมาเอง)
        $this->facebookService->sendMessage(
            $senderId,
            "🌙 รอแอดมินสักครู่นะคะ\n\n"
            ."แม่หมอจะแจ้งให้แอดมินมาตอบคุณค่ะ ✨\n"
            .'ระหว่างรอ พิมพ์ถามแม่หมอต่อได้นะคะ 🔮'
        );

        // 📢 ส่ง alert ให้ admin ผ่าน LINE OA push (admin ดูใน FB Page Inbox + พิมพ์ /aistop)
        try {
            $alertService = app(\App\Services\LineAlertService::class);
            $alertService->alertUnusualActivity('🙋 ลูกค้า FB ขอคุยกับคน', [
                'sender_id' => $senderId,
                'reading_id' => $reading->id,
                'message' => mb_substr($messageText, 0, 200),
                'admin_panel' => url('/admin/takeover/'.$reading->id),
                'note' => 'พิมพ์ /aistop ใน Page Inbox เพื่อหยุดบอท แล้วตอบลูกค้า',
            ]);
        } catch (\Throwable $alertErr) {
            Log::warning('FB Customer Handoff: ส่ง alert ไม่สำเร็จ (non-blocking)', [
                'error' => $alertErr->getMessage(),
            ]);
        }

        Log::info('🙋 FB Customer Handoff: alert-only mode (ไม่ takeover)', [
            'sender_id' => $senderId,
            'reading_id' => $reading->id,
            'message_preview' => mb_substr($messageText, 0, 50),
        ]);
    }

    /**
     * ประมวลผล postback events (ปุ่ม Get Started, Persistent Menu, etc.)
     *
     * Facebook จะส่ง postback เมื่อ:
     * - ผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     * - ผู้ใช้เลือกรายการจาก Persistent Menu
     * - ผู้ใช้กดปุ่ม Template ที่เป็น postback
     *
     * @param  array  $messaging  ข้อมูล messaging event จาก Facebook
     */
    protected function processPostback(array $messaging): void
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $payload = $messaging['postback']['payload'] ?? null;

        if (empty($senderId) || empty($payload)) {
            Log::warning('Facebook Postback: Missing sender or payload', [
                'messaging' => $messaging,
            ]);

            return;
        }

        // 🛑 Admin Handover: ถ้าแอดมิน /aistop → บอทข้าม postback
        //
        // 🚨 (2026-05-17) FLOW BYPASS — postback คือ explicit action (กดปุ่ม)
        //   user spec: "กดเลือกแพคเกจแล้ว ต้องเข้า flow จ่ายเงิน อย่าขัด"
        //   postback → bypass takeover เสมอ (Get Started / Persistent Menu / Quick Reply)
        if ($this->isAdminActive($senderId)) {
            if (! $this->takeoverService->shouldBypassTakeover('facebook', $senderId, '', true)) {
                Log::info('👨‍💼 Admin /aistop: บอทข้าม postback', [
                    'user_id' => $senderId,
                    'payload' => $payload,
                ]);

                return;
            }

            Log::info('💰 Admin /aistop active แต่ postback bypass — ดำเนิน flow ต่อ', [
                'user_id' => $senderId,
                'payload' => $payload,
            ]);
        }

        Log::info('📬 Facebook Postback received', [
            'sender_id' => $senderId,
            'payload' => $payload,
        ]);

        // จัดการ postback ตาม payload
        match ($payload) {
            // ปุ่ม Get Started - ผู้ใช้เข้ามาใช้งานครั้งแรก
            'GET_STARTED', 'get_started' => $this->handleGetStarted($senderId),

            // Persistent Menu options
            // ⚠️ ผู้สูงอายุงงระหว่าง "ดูดวง" vs "ดูดวงละเอียด" → ให้ทั้ง 2 เมนูเข้า deep flow
            // ตรงๆ ลูกค้ากดอะไรก็ได้ → ถูกพาเข้ากระบวนการทำนายทันที
            'MENU_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'MENU_DEEP_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            // 🆓 (2026-05-01) ปุ่ม "ดูดวงฟรี" จาก welcome — ส่ง category picker (ไม่ผ่าน tier menu)
            'FORTUNE_FREE' => $this->handleFortuneFreePicker($senderId),
            'MENU_CHECK_REMAINING' => $this->processConversationalMessage($senderId, 'เช็คสิทธิ์'),
            // 📋 (2026-05-06) Default QR ปุ่ม "เมนู" — ส่ง keyword "เมนู" → handleMenuRequest
            'MENU_OPEN' => $this->processConversationalMessage($senderId, 'เมนู'),
            'MENU_HELP' => $this->sendHelpMessage($senderId),

            // ✨ Ice Breakers + Persistent Menu (2026-04-27)
            'MENU_ABOUT_US', 'ICEBREAKER_ABOUT' => $this->handleAboutUs($senderId),
            'MENU_REFERRAL', 'ICEBREAKER_REFERRAL' => $this->handleReferralMenu($senderId),
            'ICEBREAKER_REGISTER' => $this->handleRegisterMenu($senderId),

            // ✅ ปุ่มจาก Rich Templates — ดูดวงละเอียด flow
            'REPORT_PAYMENT' => $this->processConversationalMessage($senderId, 'แจ้งชำระเงิน'),
            'CANCEL_PAYMENT' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            // 🛑 (2026-05-15) Cancel-reason prompt postbacks — ลูกค้าตอบ "ติดปัญหาอะไร?"
            'CANCEL_HELP_TRANSFER' => $this->processConversationalMessage($senderId, 'ขอเลขบัญชี'),
            'CANCEL_HELP_ADMIN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            'CANCEL_CONFIRM_REAL' => $this->processConversationalMessage($senderId, 'ยืนยันยกเลิก'),
            // 🛑 (2026-05-15 v2) cancelled_to_chat → ปุ่ม "คุยกับแม่หมอ"
            'TALK_ADMIN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            'SHOW_BANK_ACCOUNT' => $this->processConversationalMessage($senderId, 'แสดงบัญชี'),
            'CANCEL_DEEP' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),
            // 📅 (2026-05-01) ทวนวันเกิด — confirm/reject buttons → ส่ง keyword ที่ handler รับได้
            'BIRTHDATE_CONFIRM_YES' => $this->processConversationalMessage($senderId, 'ใช่'),
            'BIRTHDATE_CONFIRM_NO' => $this->processConversationalMessage($senderId, 'ไม่ใช่'),
            // ❓ (2026-05-01) ทวนคำถาม — confirm/reject buttons
            'QUESTION_CONFIRM_YES' => $this->processConversationalMessage($senderId, 'ใช่'),
            'QUESTION_CONFIRM_NO' => $this->processConversationalMessage($senderId, 'ไม่ตรงคำถาม'),

            // ✅ ปุ่ม LINE Invite + Affiliate Share
            'LINE_ADD_FRIEND' => $this->handleLineAddFriend($senderId),
            'LINE_INVITE' => $this->handleLineAddFriend($senderId),
            'AFFILIATE_SHARE' => $this->processConversationalMessage($senderId, 'แชร์'),

            // 💰 Affiliate Recruitment — comment engagement ชวนเข้าร่วม (ได้ค่าชวน 10 บาท/คน)
            'AFFILIATE_RECRUIT_YES' => $this->handleAffiliateRecruitYes($senderId),
            'AFFILIATE_RECRUIT_NO' => $this->handleAffiliateRecruitNo($senderId),

            // 👁️ Follow-page confirmation — user คลิก "✅ ติดตามแล้ว"
            'FOLLOW_CONFIRMED' => $this->handleFollowConfirmed($senderId),

            // 📖 (2026-05-02) คนแก่ใช้งานง่าย — กดปุ่มอ่านคำทำนาย
            //   ใช้ใน fortune_ready_notification (Button Template) → ส่งคำทำนายเต็มทันที
            'READ_PREDICTION' => $this->processConversationalMessage($senderId, 'อ่านคำทำนาย'),

            // 🌐 (2026-05-03) Language picker — manual override (auto-detect ทำงานเป็น default)
            'LANG_PICKER' => $this->handleLanguagePicker($senderId),
            'LANG_TH' => $this->handleLanguagePick($senderId, 'th'),
            'LANG_LO' => $this->handleLanguagePick($senderId, 'lo'),

            // 🛑 (2026-05-14) ลบ CELTIC_PREDICT_NOW + CELTIC_START_Q — flow ใหม่ไม่มี predict-now
            //   ลูกค้าเก่าที่ยังกดปุ่มเก่า → route ไปยัง help text ที่ persistent menu จัดการ
            //   (ถ้ามีบิล active กลับเข้า flow เดิมผ่าน processConversationalMessage)
            'CELTIC_PREDICT_NOW', 'CELTIC_START_Q' => $this->processConversationalMessage($senderId, 'เล่าเรื่องที่ค้างคาใจหน่อยค่ะ'),

            // ส่งไปจัดการตาม Quick Reply (backward compatibility)
            default => $this->handleQuickReply($senderId, $payload),
        };
    }

    /**
     * 🌐 ส่ง Quick Reply ให้เลือกภาษา (เรียกจาก Ice Breaker LANG_PICKER)
     *
     * 🚫 (2026-05-09) Lao support permanently disabled (FortuneLocaleService::ENABLED=false)
     *    → short-circuit ไปที่ Thai ทันที ไม่ต้องโชว์ตัวเลือก. กัน customer คลิก 🇱🇦 แล้วเจอ
     *      response ภาษาลาว (ผ่าน LANG_LO postback) ตามด้วยข้อความไทยทั้งหมด → ลูกค้างง.
     */
    protected function handleLanguagePicker(string $senderId): void
    {
        if (! FortuneLocaleService::ENABLED) {
            $this->handleLanguagePick($senderId, 'th');

            return;
        }

        try {
            $this->facebookService->sendMessage(
                $senderId,
                '🌐 เลือกภาษา / ເລືອກພາສາ / Choose language',
                [
                    'quick_replies' => [
                        ['title' => '🇹🇭 ไทย', 'payload' => 'LANG_TH'],
                        ['title' => '🇱🇦 ລາວ', 'payload' => 'LANG_LO'],
                    ],
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('handleLanguagePicker error', [
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 📜 (2026-05-03) ดู Celtic Q&A list — state ไม่เปลี่ยน (read-only)
     */
    protected function handleCelticViewList(string $senderId): void
    {
        try {
            $result = $this->conversationService->handleViewCelticList($senderId);
            $channelManager = new FortuneChannelManager($this->settings);
            $channelManager->sendResponse('facebook', $senderId, $result);
        } catch (\Throwable $e) {
            Log::warning('handleCelticViewList error', [
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 📜 (2026-05-03) ดูคำตอบ Celtic Q[N] — state ไม่เปลี่ยน (read-only)
     */
    protected function handleCelticViewQuestion(string $senderId, int $sequence): void
    {
        try {
            $result = $this->conversationService->handleViewCelticQuestion($senderId, $sequence);
            $channelManager = new FortuneChannelManager($this->settings);
            $channelManager->sendResponse('facebook', $senderId, $result);
        } catch (\Throwable $e) {
            Log::warning('handleCelticViewQuestion error', [
                'sender_id' => $senderId,
                'sequence' => $sequence,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🌐 บันทึกภาษาที่ user เลือก (manual override) + ส่งข้อความยืนยัน
     *
     * 🚫 (2026-05-09) ถ้า kill switch FortuneLocaleService::ENABLED=false → coerce เป็น 'th' เสมอ
     *    เคสเก่าที่ปุ่ม LANG_LO ยังค้างใน chat history (Ice Breaker) ลูกค้าคลิกได้ → ไม่ปล่อยให้
     *    เขียน 'lo' ลง DB + ตอบลาว 1 ครั้ง แล้ว response ถัดไปเป็นไทยทั้งหมด (force_thai_only).
     */
    protected function handleLanguagePick(string $senderId, string $locale): void
    {
        // 🚫 Coerce เป็น 'th' ถ้า Lao ถูกปิดถาวร
        if (! FortuneLocaleService::ENABLED && $locale !== 'th') {
            Log::info('handleLanguagePick: coerce locale → th (Lao disabled)', [
                'sender_id' => $senderId,
                'requested_locale' => $locale,
            ]);
            $locale = 'th';
        }

        try {
            FortuneLocaleService::set(
                'facebook',
                $senderId,
                $locale,
                FortuneLocaleService::SOURCE_MANUAL
            );
            FortuneLocaleService::setCurrent($locale);

            $msg = $locale === 'lo'
                ? "🇱🇦 ປ່ຽນເປັນພາສາລາວແລ້ວ ✓\nພິມຄຳຖາມເຂົ້າມາໄດ້ເລີຍ"
                : "🇹🇭 เปลี่ยนเป็นภาษาไทยแล้ว ✓\nพิมพ์คำถามมาได้เลยค่ะ";

            $this->facebookService->sendMessage($senderId, $msg);
        } catch (\Throwable $e) {
            Log::warning('handleLanguagePick error', [
                'sender_id' => $senderId,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 👁️ จัดการเมื่อ user คลิก "✅ ติดตามแล้ว"
     * - mark facebook_followed_confirmed_at ใน FortuneUserCredit
     * - ส่ง thank-you message
     * - ไม่ส่ง follow-prompt อีก
     */
    protected function handleFollowConfirmed(string $senderId): void
    {
        try {
            $credit = FortuneUserCredit::getOrCreate($senderId, 'facebook');
            $credit->markFacebookFollowed();

            $this->facebookService->sendMessage(
                $senderId,
                "✨ ขอบคุณที่ติดตามเพจค่ะ\n"
                ."ตั้งแต่ตี 1-7 โมงเช้าทุกวัน เราจะโพสดวงประจำวันให้\n"
                .'อย่าลืมกดเปิดการแจ้งเตือนนะคะ 🔔'
            );

            Log::info('👁️ Follow confirmed', ['user_id' => $senderId]);
        } catch (\Throwable $e) {
            Log::warning('handleFollowConfirmed failed (non-blocking)', [
                'user_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ผู้ใช้กด "✅ อยาก" หลัง affiliate recruitment pitch
     * → ส่งรายละเอียดการเริ่มต้น (ดูดวงราคา dynamic → สมาชิก → รับส่วนแบ่ง)
     */
    protected function handleAffiliateRecruitYes(string $senderId): void
    {
        // ใช้ราคา dynamic จาก settings (ไม่ hardcode)
        // 🌙 (2026-05-23) Deep ปิด — ใช้ Celtic price แทน + ปรับ wording
        $deepPrice = number_format($this->getDeepReadingPriceFromSettings(), 0);
        $deepEnabledAr = $this->settings->isDeepReadingEnabled();
        $celticEnabledAr = (bool) ($this->settings->enable_celtic_cross ?? false);
        $celticPriceAr = 99;
        try {
            $celticPriceAr = (int) app(\App\Services\CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
        }
        $tierLine = $deepEnabledAr
            ? "1️⃣ ดูดวงเชิงลึกกับแม่หมอ {$deepPrice} บาท/ครั้ง\n"
            : ($celticEnabledAr ? "1️⃣ ดูดวงไพ่ Celtic Cross 10 ใบ {$celticPriceAr} บาท/ครั้ง\n" : "1️⃣ ดูดวงกับแม่หมอ\n");

        $message = "🎉 ยินดีค่ะ! วิธีเริ่มง่ายๆ 3 ขั้น\n\n"
            .$tierLine
            ."2️⃣ หลังดูดวงเสร็จ → ได้เป็นสมาชิกอัตโนมัติ\n"
            ."3️⃣ รับลิงก์แชร์ส่วนตัว → แชร์ให้เพื่อน\n\n"
            ."💰 รายได้:\n"
            ."• ชวนคนมาดูดวง → ได้ 10 บาท/คน (Level 1)\n"
            ."• เพื่อนชวนต่อ → ได้ส่วนแบ่งอีกชั้น (Level 2)\n\n"
            .'กดปุ่มด้านล่างเพื่อเริ่มเลยค่ะ ✨';

        $quickReplies = [];
        if ($deepEnabledAr) {
            $quickReplies[] = ['content_type' => 'text', 'title' => "💎 เริ่มดูดวง {$deepPrice} บาท", 'payload' => 'MENU_DEEP_FORTUNE'];
        } elseif ($celticEnabledAr) {
            $quickReplies[] = ['content_type' => 'text', 'title' => "🔮 ไพ่ 10 ใบ {$celticPriceAr}฿", 'payload' => 'TIER_CELTIC_99'];
        }
        $quickReplies[] = ['content_type' => 'text', 'title' => '🔮 ดูดวงก่อน', 'payload' => 'MENU_FORTUNE'];

        $this->facebookService->sendQuickReplies($senderId, $message, $quickReplies);

        Log::info('💰 Affiliate Recruit YES clicked', ['user_id' => $senderId]);
    }

    /**
     * ผู้ใช้กด "❌ ไม่อยาก" หลัง affiliate recruitment pitch
     * → fallback ชวนดูดวงปกติ
     */
    protected function handleAffiliateRecruitNo(string $senderId): void
    {
        $message = "ไม่เป็นไรค่ะ 😊\n\n"
            ."ถ้าสนใจให้แม่หมอทำนายดวง\n"
            .'พิมพ์ "ดูดวง" มาได้เลยนะคะ 🔮';

        // 🌙 (2026-05-23) Deep ปิด → ไม่โชว์ปุ่ม Deep — swap เป็น Celtic ถ้าเปิด
        $deepEnabledArn = $this->settings->isDeepReadingEnabled();
        $celticEnabledArn = (bool) ($this->settings->enable_celtic_cross ?? false);
        $quickReplies = [
            ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
        ];
        if ($deepEnabledArn) {
            $quickReplies[] = ['content_type' => 'text', 'title' => '🌟 ดูดวง', 'payload' => 'MENU_DEEP_FORTUNE'];
        } elseif ($celticEnabledArn) {
            $celticPriceArn = 99;
            try {
                $celticPriceArn = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
            $quickReplies[] = ['content_type' => 'text', 'title' => "🔮 ไพ่ 10 ใบ {$celticPriceArn}฿", 'payload' => 'TIER_CELTIC_99'];
        }

        $this->facebookService->sendQuickReplies($senderId, $message, $quickReplies);

        Log::info('💰 Affiliate Recruit NO clicked', ['user_id' => $senderId]);
    }

    /**
     * 🔍 รู้จักเรา — อธิบายศาสตร์ที่ใช้ทำนาย + เน้นความน่าเชื่อถือ + ติดต่อ xman studio
     *
     * เน้นว่าไม่ใช่ AI มโน — ใช้ระบบหลักล้านจริง
     */
    protected function handleAboutUs(string $senderId): void
    {
        $message = "✨ รู้จัก \"แม่หมอจันทรา\"\n"
            ."─────────────\n\n"
            ."🔮 *ศาสตร์ที่เราใช้ทำนาย*\n"
            ."• โหราศาสตร์ไทยสายเจ้าชนะ (วิชาดั้งเดิมจากสายลังกา)\n"
            ."• โหราศาสตร์สากล — ตำแหน่งดาวเคราะห์ปัจจุบัน (transit)\n"
            ."• ไพ่ทาโรต์ — สื่อพลังจิตและสัญลักษณ์\n"
            ."• เลขศาสตร์ — วันเดือนปีเกิดเชื่อมพลังตัวเลข\n\n"
            ."💎 *ทำไมแม่นและน่าเชื่อถือ*\n"
            .'ระบบของเราใช้ AI ระดับ flagship หลักล้าน '
            ."(Grok, Gemini Pro, GPT-class) ผูกกับ\n"
            ."ฐานข้อมูลโหราศาสตร์จริงที่ถ่ายทอดจากครูบาอาจารย์\n"
            ."→ ไม่ใช่ AI มโนเดา แต่วิเคราะห์จากดวงชะตาจริง\n\n"
            ."🏢 *พัฒนาโดย xman studio*\n"
            ."ทีมพัฒนาระบบ AI ดูดวงและ Affiliate ระดับองค์กร\n"
            ."📍 https://xman4289.com\n\n"
            .'💼 สนใจให้ทำระบบให้องค์กร? — ติดต่อได้ที่ xman studio';

        // 🌙 (2026-05-23) Deep ปิด → swap ปุ่ม Deep เป็น Celtic ถ้าเปิด ไม่งั้นซ่อน
        $deepEnabledAu = $this->settings->isDeepReadingEnabled();
        $celticEnabledAu = (bool) ($this->settings->enable_celtic_cross ?? false);
        $aboutButtons = [
            ['type' => 'web_url', 'title' => '🌐 เยี่ยมชม xman studio', 'url' => 'https://xman4289.com'],
        ];
        if ($deepEnabledAu) {
            $aboutButtons[] = ['type' => 'postback', 'title' => '💎 ดูดวงเชิงลึก', 'payload' => 'MENU_DEEP_FORTUNE'];
        } elseif ($celticEnabledAu) {
            $aboutButtons[] = ['type' => 'postback', 'title' => '🔮 ไพ่ Celtic Cross', 'payload' => 'TIER_CELTIC_99'];
        }
        $aboutButtons[] = ['type' => 'postback', 'title' => '👥 ชวนเพื่อน', 'payload' => 'MENU_REFERRAL'];

        $this->sendButtons($senderId, $message, $aboutButtons);

        Log::info('🔍 About Us shown', ['user_id' => $senderId]);
    }

    /**
     * 📝 สมัครสมาชิก — ส่งลิงก์ FB OAuth ที่ทำไว้แล้ว
     *
     * ลูกค้าคลิก → /auth/facebook → Socialite → auto-create User+Wallet+MlmMember
     * → redirect ไปหน้า wallet
     */
    protected function handleRegisterMenu(string $senderId): void
    {
        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');
        $registerUrl = $appUrl.'/auth/facebook';

        $message = "📝 *สมัครสมาชิก thaiprompt — ฟรี!*\n"
            ."─────────────\n\n"
            ."✅ ใช้ Facebook ของคุณสมัครได้เลย — ไม่ต้องกรอกอะไร\n"
            ."✅ ระบบสร้างกระเป๋าให้อัตโนมัติ\n"
            ."✅ ดูยอดรายได้ค่าแนะนำได้ตลอด\n"
            ."✅ ถอนเงินได้เมื่อยืนยันตัวตน (KYC)\n\n"
            .'👉 กดปุ่มด้านล่างเพื่อสมัคร — ใช้เวลา 10 วินาที';

        // 🌙 (2026-05-23) Deep ปิด → swap ปุ่ม Deep เป็น Celtic ถ้าเปิด
        $deepEnabledRg = $this->settings->isDeepReadingEnabled();
        $celticEnabledRg = (bool) ($this->settings->enable_celtic_cross ?? false);
        $registerButtons = [
            ['type' => 'web_url', 'title' => '📝 สมัครด้วย Facebook', 'url' => $registerUrl, 'webview_height_ratio' => 'full'],
        ];
        if ($deepEnabledRg) {
            $registerButtons[] = ['type' => 'postback', 'title' => '💎 ดูดวงเชิงลึก', 'payload' => 'MENU_DEEP_FORTUNE'];
        } elseif ($celticEnabledRg) {
            $registerButtons[] = ['type' => 'postback', 'title' => '🔮 ไพ่ Celtic Cross', 'payload' => 'TIER_CELTIC_99'];
        }

        $this->sendButtons($senderId, $message, $registerButtons);

        Log::info('📝 Register menu shown', ['user_id' => $senderId]);
    }

    /**
     * 👥 ชวนเพื่อน — ตรวจ membership ก่อน
     *
     * - ดูดวง 1 ครั้ง (paid) = สมาชิก → ส่ง referral link พร้อมรายละเอียดรายได้
     * - ยังไม่ได้ดูดวง → แจ้ง "ต้องเป็นสมาชิกก่อน" + อธิบาย benefit
     */
    protected function handleReferralMenu(string $senderId): void
    {
        // ตรวจ membership: มี FortuneReading ที่ is_paid=true หรือไม่
        $isMember = FortuneReading::where('facebook_user_id', $senderId)
            ->where('is_paid', true)
            ->exists();

        $deepPrice = number_format($this->getDeepReadingPriceFromSettings(), 0);

        if (! $isMember) {
            // ⛔ ยังไม่ใช่สมาชิก — pitch ให้ดูดวง 1 ครั้ง
            // 🌙 (2026-05-23) Deep ปิด → ใช้ Celtic price ใน pitch
            $deepEnabledRm = $this->settings->isDeepReadingEnabled();
            $celticEnabledRm = (bool) ($this->settings->enable_celtic_cross ?? false);
            $celticPriceRm = 99;
            try {
                $celticPriceRm = (int) app(\App\Services\CelticCrossService::class)->getPrice();
            } catch (\Throwable $e) {
            }
            $signupTier = $deepEnabledRm
                ? "เพียงดูดวงเชิงลึก *1 ครั้ง* ({$deepPrice} บาท)\n"
                : ($celticEnabledRm ? "เพียงดูดวงไพ่ Celtic Cross *1 ครั้ง* ({$celticPriceRm} บาท)\n" : "เพียงดูดวง *1 ครั้ง*\n");
            $message = "⚠️ ต้องเป็นสมาชิกก่อนนะคะ\n"
                ."─────────────\n\n"
                ."📋 *วิธีเป็นสมาชิก — ง่ายมาก:*\n"
                .$signupTier
                ."ระบบจะลงทะเบียนให้อัตโนมัติทันที\n\n"
                ."🎁 *สิทธิ์ที่ได้รับ:*\n"
                ."• 💎 กระเป๋าเงินส่วนตัวในระบบ\n"
                ."• 👥 ลิงก์เชิญเพื่อนพิเศษ\n"
                ."• 💰 ค่าแนะนำ 10 บาท/คนที่เพื่อนของคุณดูดวง (Level 1)\n"
                ."• 🌳 ค่าแนะนำชั้นหลาน (Level 2) อีกชั้น\n"
                ."• 📊 Dashboard ดูรายได้แบบ real-time\n"
                ."• 💸 ถอนเงินเข้าบัญชีได้ (หลัง KYC)\n\n"
                .'✨ ดูดวง 1 ครั้ง = ได้ทั้งคำทำนายแม่นๆ + เป็นสมาชิกเลยค่ะ';

            $this->sendButtons($senderId, $message, [
                ['type' => 'postback', 'title' => "💎 เริ่มดูดวง {$deepPrice} บาท", 'payload' => 'MENU_DEEP_FORTUNE'],
                ['type' => 'postback', 'title' => '✨ รู้จักเราเพิ่ม', 'payload' => 'MENU_ABOUT_US'],
            ]);

            Log::info('👥 Referral pitch (non-member)', ['user_id' => $senderId]);

            return;
        }

        // ✅ เป็นสมาชิกแล้ว — ส่ง referral link
        $this->processConversationalMessage($senderId, 'แชร์');

        Log::info('👥 Referral link sent (member)', ['user_id' => $senderId]);
    }

    /**
     * Helper: wrap buttons array → FB Button Template payload + ส่ง
     */
    protected function sendButtons(string $userId, string $message, array $buttons): bool
    {
        $payload = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => mb_substr($message, 0, 640), // FB limit 640 chars
                    'buttons' => array_slice($buttons, 0, 3), // FB limit 3 buttons
                ],
            ],
        ];

        return $this->facebookService->sendButtonTemplate($userId, $payload);
    }

    /**
     * 👁️ ส่งข้อความกระตุ้นให้กดติดตามเพจ (หลังส่ง DM comment engagement)
     *
     * เป็นข้อความที่ 2 หลัง DM Quick Replies — ใช้ Button Template
     * เพื่อให้กดติดตามได้ง่าย + รับการแจ้งเตือนดวงประจำวันที่ระบบโพสรายชั่วโมง
     */
    protected function sendFollowPagePrompt(string $userId, ?string $commentId = null): void
    {
        // ใช้ service method ที่มี shouldPromptFollow gating + postback button
        // (refactored 2026-04-28 — เดิมส่งทุกครั้ง ตอนนี้เช็ค + dedupe + ติดตามผ่าน DB)
        $this->facebookService->sendFollowPagePromptToUser($userId);
    }

    /**
     * 💗 จัดการเมื่อลูกค้า react ข้อความ (❤️/👍/😆/😮/😢/😡)
     *
     * Throttle: 1 ครั้ง/user/60 วินาที — ป้องกันรัวสแปม
     * ตอบกลับด้วย emoji เดียวกัน (เพราะ FB Page Messenger API ยังไม่รองรับ react กลับ
     * เป็น sender_action — เลยใช้ emoji เป็นข้อความแทน)
     */
    protected function handleMessageReaction(array $messaging): void
    {
        // 🧹 (2026-05-01) Mute reactions ทุกกรณี — ไม่ตอบ emoji
        //    เหตุผล: reactions ไม่ใช่ข้อความที่ต้อง engage; การตอบทำให้ลูกค้ารำคาญ
        //    + ยังเสี่ยงโดน FB rate limit ถ้า user รัวกด
        $senderId = $messaging['sender']['id'] ?? null;
        $reaction = $messaging['reaction']['reaction'] ?? null;
        $action = $messaging['reaction']['action'] ?? null;

        if ($senderId && $reaction) {
            Log::debug('💗 FB Reaction: silenced (no auto-reply)', [
                'user_id' => $senderId,
                'reaction' => $reaction,
                'action' => $action,
            ]);
        }
        // ไม่ตอบกลับใดๆ
    }

    /**
     * จัดการเมื่อผู้ใช้กดปุ่ม "Get Started" (เริ่มต้นใช้งาน)
     *
     * ส่ง Rich Welcome Template พร้อมปุ่มดูดวง + เพิ่มเพื่อน LINE
     *
     * @param  string  $senderId  Facebook User ID
     */
    protected function handleGetStarted(string $senderId): void
    {
        try {
            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile พร้อม fallback
            $userProfile = $this->facebookService->getUserProfile($senderId);
            $userName = (is_array($userProfile) && ! empty($userProfile['name'])) ? $userProfile['name'] : 'คุณ';

            Log::info('🎉 New user started conversation', [
                'sender_id' => $senderId,
                'user_name' => $userName,
            ]);

            // 🔓 (2026-05-07 review fix) GET_STARTED = user just clicked = 24hr window OPEN
            //   เคลียร์ per-user 551 unreachable cache เพื่อกัน leak จาก reaction/comment-time
            $unreachableKey = "fb_user_unreachable:{$senderId}:".now()->format('Y-m-d');
            Cache::forget($unreachableKey);

            // 🖼️ (2026-05-06) ส่ง banner welcome ก่อน (ครั้งแรกที่กด GET_STARTED ก็ควรเห็น)
            //   เดิม: banner ส่งเฉพาะใน processConversationalMessage → คนที่กด GET_STARTED แล้วไม่ทักต่อ ไม่เคยเห็น
            //   👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — pass platform+userId
            if ($this->bannerService) {
                $this->bannerService->sendBannerOnce(
                    $senderId,
                    fn ($url) => $this->facebookService->sendImage($senderId, $url),
                    'welcome',
                    24,
                    'facebook'
                );
            }

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // 🎯 (2026-05-08) UX cleanup — banner image อย่างเดียว ไม่ส่ง welcome rich card
            //   user feedback: "กล่องชักชวนมันเยอะไปตาลาย เอาแค่รูป อย่างเดียวก่อน"
            //   เดิม: banner image + welcome rich card + 3 ปุ่ม + quick replies (ตาลาย)
            //   ใหม่: banner image อย่างเดียว — รอลูกค้าทักมา → AI chat ตอบเป็นกันเอง
            //   ❗ ลบ buildWelcomeTemplate / sendTemplateWithQuickReplies
            //   ❗ ลบ buildWelcomeMessage / sendQuickReplies
            //
            // ✅ FB 24hr messaging window เปิดอยู่แล้วจาก banner image (เป็น message เหมือนกัน)
            // ✅ ลูกค้าพิมพ์ทักมา → processConversationalMessage → AI chat ตอบเป็นกันเอง
            //    + เนียนชวนดูดวง (ผ่าน chat_system_prompt) เมื่อบริบทเหมาะ

            // 🚫 (2026-05-08) Group invite ลบออกจาก get_started — user feedback "ส่งทำไม ไม่มีประโยชน์"
            //   กล่อง group invite รบกวน UX ใหม่ที่ทักมาครั้งแรก → ลบทิ้ง
            //   ถ้าจะใช้อนาคต — uncomment + ตั้ง fortune_group_invite_enabled=true
            // $this->maybeInviteToGroup($senderId, 'get_started');

        } catch (\Exception $e) {
            Log::error('Get Started Error: '.$e->getMessage(), [
                'sender_id' => $senderId,
            ]);

            $this->facebookService->sendTypingIndicator($senderId, false);
            $this->facebookService->sendMessage(
                $senderId,
                "🔮 ยินดีต้อนรับค่ะ!\n\nพิมพ์ 'ดูดวง' เพื่อเริ่มต้นดูดวงได้เลยนะคะ ✨"
            );
        }
    }

    /**
     * 🌟 ชวนเข้ากลุ่ม Facebook (non-blocking)
     *
     * เงื่อนไขการเชิญ:
     *   - get_started: ทุก user ที่กด GET_STARTED
     *   - post_prediction: หลังส่งคำทำนาย (อนาคต)
     *   - comment_dm: หลัง comment engagement DM (อนาคต)
     *
     * Service ภายใน gate ด้วย: toggle, group_url, 7-day cooldown
     */
    protected function maybeInviteToGroup(string $senderId, string $context = 'get_started'): void
    {
        try {
            // ✅ เชิญเฉพาะคนที่ยังไม่เคยดูดวงเลย (ตาม spec ของ user)
            if ($context === 'get_started') {
                $hasReading = FortuneReading::where('facebook_user_id', $senderId)
                    ->where('platform', 'facebook')
                    ->exists();
                if ($hasReading) {
                    return; // เคยดูดวงแล้ว ไม่ต้องเชิญ
                }
            }

            // หน่วงเล็กน้อยให้ welcome ขึ้นก่อนเป็น UX ที่ดี
            usleep(300_000); // 0.3 วินาที

            $this->facebookService->sendGroupInvitePrompt($senderId);
        } catch (\Throwable $e) {
            Log::debug('maybeInviteToGroup failed (non-blocking)', [
                'sender_id' => $senderId,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * สร้างข้อความต้อนรับสำหรับผู้ใช้ใหม่
     *
     * @param  string  $userName  ชื่อผู้ใช้
     */
    protected function buildWelcomeMessage(string $userName): string
    {
        // 🎁 (2026-05-04) ลบ mention "ฟรี" ออกทั้งหมด — ฟรีให้เฉพาะตอบกลับ DM react/comment
        //   เดิม: มีบล็อก "🆓 ดูดวงพื้นฐาน (ฟรี)" + "พิมพ์ 'เช็คสิทธิ์' เพื่อดูครั้งฟรี"
        //   ใหม่: เน้นบริการเสียเงิน 39/99 อย่างเดียว (welcome ปกติ — ไม่ใช่ react/comment DM)

        // กันซ้อน "คุณคุณ" — ถ้า fallback เป็น 'คุณ' ให้ใช้ greeting สั้น
        $greeting = ($userName === 'คุณ' || $userName === '' || empty($userName))
            ? '✨ *สวัสดีค่ะ!*'
            : "✨ *สวัสดีค่ะ คุณ{$userName}!*";
        $message = $greeting."\n\n";
        $message .= "🔮 ยินดีต้อนรับสู่ระบบดูดวง AI\n";
        $message .= "ทางเพจพร้อมทำนายดวงชะตาให้คุณค่ะ\n\n";

        $message .= "═══════════════════════\n";
        $message .= "📋 *บริการของเรา*\n";
        $message .= "═══════════════════════\n\n";

        // ใช้ราคาจาก settings (dynamic) — ไม่ hardcode
        $deepPriceText = number_format($this->getDeepReadingPriceFromSettings(), 0);
        $qCount = FortuneConversationService::REQUIRED_QUESTIONS;
        $message .= "💎 *ดูดวงเชิงลึก ({$qCount} คำถาม {$deepPriceText} บาท)*\n";
        $message .= "ทำนายเชิงลึกจากดาวเจ้าชนะ + ไพ่ยิปซีจริง — ไม่ยกเมฆ\n\n";

        $message .= "═══════════════════════\n";
        $message .= "💡 *วิธีเริ่มต้น*\n";
        $message .= "═══════════════════════\n\n";

        $message .= "พิมพ์อะไรก็ได้มาคุยกับทางเพจได้เลยค่ะ!\n\n";

        $message .= "ตัวอย่าง:\n";
        $message .= "• ดวงความรักปีนี้เป็นอย่างไร\n";
        $message .= "• ปีนี้จะได้เลื่อนตำแหน่งไหม\n";
        $message .= "• การเงินเดือนหน้าเป็นอย่างไร\n\n";

        $message .= 'กดปุ่มด้านล่างหรือพิมพ์เลยค่ะ 👇';

        return $message;
    }

    /**
     * ประมวลผลข้อความแบบ Conversational ผ่าน FortuneChannelManager
     *
     * ใช้ตรรกะเดียวกับ LINE Bot:
     * - keyword matching → state machine → AI fallback
     * - Rich Message Templates (Button/Generic แทน Flex)
     * - Quick Replies อัตโนมัติตาม action
     * - LINE invite + affiliate share
     *
     * Flow:
     * 1. ดูดวงพื้นฐานฟรี (ดึงโปรไฟล์ทำนายเบื้องต้น)
     * 2. เสนอดูดวงละเอียด (ราคาดึงจาก admin settings) ถามวันเกิด + 1 คำถาม + ตั้งจิตเลือกไพ่
     * 3. สร้างบิล + unique amount + แสดงบัญชีธนาคาร
     * 4. SMS match → ส่งคำทำนายละเอียดผ่าน Messenger
     *
     * ⚠️ ไม่แตะ SMS Payment / UniquePaymentAmount / confirmPayment()
     *
     * @param  string  $senderId  Facebook User ID
     * @param  string  $messageText  ข้อความที่ส่งมา
     */
    protected function processConversationalMessage(string $senderId, string $messageText): void
    {
        try {
            // 🔓 (2026-05-07 review fix) เคลียร์ per-user 551 unreachable cache
            //   เหตุผล: ถ้า user ทักเพจ → 24hr window เปิดใหม่ → ส่ง DM/banner ได้แน่นอน
            //   เดิม: cache ที่ตั้งจาก reaction/comment-time 551 จะ leak มา block welcome banner
            //   ใหม่: ทุกครั้งที่ webhook รับ message → clear cache (proves user reachable)
            $unreachableKey = "fb_user_unreachable:{$senderId}:".now()->format('Y-m-d');
            Cache::forget($unreachableKey);

            // ตรวจสอบว่า channelManager พร้อมใช้งาน (fallback ไป conversationService ถ้าไม่มี)
            if (! $this->channelManager && ! $this->conversationService) {
                Log::error('ChannelManager และ ConversationService ไม่พร้อม');
                $this->facebookService->sendMessage(
                    $senderId,
                    "🔮 สวัสดีค่ะ\n\nระบบกำลังเตรียมพร้อมอยู่ค่ะ กรุณาลองพิมพ์มาใหม่ในอีกสักครู่นะคะ 🙏✨"
                );

                return;
            }

            // ส่ง typing indicator
            $this->facebookService->sendTypingIndicator($senderId);

            // ดึง user profile พร้อม fallback กรณี API ล้มเหลว
            // 🎯 Phase E — ถ้า API ล้มเหลว ใช้ชื่อที่บันทึกไว้ใน FortuneUserCredit (แทน "คุณ" hardcode)
            //    กัน "สวัสดี คุณคุณ" จากการพิมพ์ซ้ำของ prefix
            $userProfile = $this->facebookService->getUserProfile($senderId);
            if (! is_array($userProfile) || empty($userProfile['name']) || $userProfile['name'] === 'คุณ') {
                $savedName = FortuneUserCredit::findByUser($senderId, 'facebook')?->facebook_user_name;

                // 🛠️ (2026-05-01) ห้ามใช้ '' เป็น fallback — กัน DB save empty string
                //    ChannelManager::resolveUserName() จะ scan historical readings ต่อให้
                //    เป็น 'คุณ' (treated as not-human-like ใน isHumanLikeName)
                $userProfile = [
                    'name' => ($savedName && $savedName !== 'คุณ' && $savedName !== '')
                        ? $savedName
                        : 'คุณ',
                    'id' => $senderId,
                ];
                Log::info('Facebook: ดึงโปรไฟล์ไม่สำเร็จ ใช้ชื่อที่บันทึกไว้/fallback "คุณ"', [
                    'sender_id' => $senderId,
                    'used_saved_name' => ! empty($savedName) && $savedName !== 'คุณ',
                ]);
            }

            // 🖼️ ส่งแบนเนอร์ welcome (ครั้งเดียวต่อ user/24 ชม.)
            // ส่งก่อน processMessage เพื่อให้ภาพมาก่อนข้อความตอบ
            // 👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — ลูกค้าเก่าได้ text ตรง (ไม่ส่งรูป)
            //
            // 🚨 (2026-05-17) Skip banner ระหว่าง active flow (กันแทรกกลาง payment/reading)
            //   user report: ลูกค้ากรอก "39.54ค่ะ" แจ้งยอดจ่าย → ระบบส่ง welcome banner ซ้อน
            //   เคสที่ครอบคลุม:
            //     - PENDING_PAYMENT / AWAITING_PAYMENT_METHOD / CELTIC_PENDING_PAYMENT (รอจ่าย)
            //     - COLLECTING_BIRTHDATE / QUESTIONS / TAROT (อยู่กลาง flow)
            //     - CELTIC_PICKING / GENERATING / QA_PROMPT (จ่ายแล้ว ต่อ flow)
            //     - paid + pendingDelivery (รอคำทำนาย)
            $hasActiveFlow = FortuneReading::where('facebook_user_id', $senderId)
                ->where(function ($q) {
                    $q->whereIn('conversation_status', FortuneReading::ACTIVE_READING_STATUSES)
                        ->orWhere(function ($sub) {
                            $sub->where('is_paid', true)
                                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                ->whereNotNull('deep_response')
                                ->where('deep_response', '!=', '');
                        });
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (! $hasActiveFlow && $this->bannerService) {
                $this->bannerService->sendBannerOnce(
                    $senderId,
                    fn ($url) => $this->facebookService->sendImage($senderId, $url),
                    'welcome',
                    24,
                    'facebook'
                );
            } elseif ($hasActiveFlow) {
                Log::debug('FB: skip welcome banner — มี active flow', [
                    'sender_id' => $senderId,
                ]);
            }

            // ✅ ใช้ FortuneChannelManager เพื่อ routing + Rich Message response
            // ChannelManager จะเรียก conversationService->processMessage() ภายใน
            // แล้วส่ง Rich Message (Button/Generic Template) ตอบกลับอัตโนมัติ
            if ($this->channelManager) {
                $result = $this->channelManager->processMessage(
                    FortuneChannelManager::PLATFORM_FACEBOOK,
                    $senderId,
                    $messageText,
                    $userProfile
                );

                // ปิด typing indicator
                $this->facebookService->sendTypingIndicator($senderId, false);

                Log::info('Conversational Fortune (via ChannelManager): ประมวลผลสำเร็จ', [
                    'facebook_user_id' => $senderId,
                    'action' => $result['action'] ?? 'unknown',
                    'reading_id' => ($result['reading'] ?? null)?->id,
                ]);

                return;
            }

            // 🔄 Fallback: ใช้ conversationService โดยตรง (กรณี channelManager พัง)
            Log::warning('Facebook: ChannelManager ไม่พร้อม ใช้ fallback conversationService', [
                'sender_id' => $senderId,
            ]);

            // ✅ ต้องตั้ง platform ก่อน processMessage เพื่อให้ saveQuestionForAdmin() เก็บค่าถูก
            $this->conversationService->setPlatform('facebook');

            $result = $this->conversationService->processMessage($senderId, $messageText, $userProfile);

            // ปิด typing indicator
            $this->facebookService->sendTypingIndicator($senderId, false);

            // Fallback: ส่งภาพ Birth Chart ก่อนข้อความทำนาย (ถ้ามี)
            $chartUrl = $result['chart_image_url'] ?? null;
            if ($chartUrl) {
                try {
                    $this->facebookService->sendImage($senderId, $chartUrl);
                    usleep(500000);
                } catch (\Exception $imgErr) {
                    Log::warning('Fortune: Failed to send chart image', [
                        'error' => $imgErr->getMessage(),
                        'chart_url' => $chartUrl,
                    ]);
                }
            }

            // Fallback: ส่งข้อความกลับ
            $message = $result['message'] ?? '';
            if (! empty($message)) {
                $this->sendLongMessage($senderId, $message);
            }

            // Fallback: ส่งภาพ QR Code ชำระเงิน (ถ้ามี)
            $paymentQrUrl = $result['payment_qr_url'] ?? null;
            if ($paymentQrUrl) {
                try {
                    usleep(300000);
                    $this->facebookService->sendImage($senderId, $paymentQrUrl);
                } catch (\Exception $qrErr) {
                    Log::warning('Fortune: ส่งภาพ QR Code ไม่สำเร็จ', [
                        'error' => $qrErr->getMessage(),
                    ]);
                }
            }

            // Fallback: ส่ง Quick Replies
            // 🎯 Phase A.2 (FB) — เพิ่ม escape-hatch buttons ให้ state ที่ user มักติด
            //   - invalid_birthdate / collecting_birthdate: ยกเลิก / ช่วยเหลือ
            //   - awaiting_question: เลือกหัวข้อ / ยกเลิก
            //   - waiting_payment / pending_payment: ยกเลิกบิล / วิธีใช้งาน
            $actionsWithQuickReplies = [
                'awaiting_confirmation', 'basic_done', 'check_remaining',
                'collecting_questions', 'need_more_questions', 'retry_question',
                'awaiting_question', 'invalid_birthdate', 'collecting_birthdate',
                'pending_payment', 'waiting_payment',
                'ai_limit', 'declined', 'payment_expired', 'completed',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty',
                // 🎯 Phase C — ตรวจพบวันเกิดจากข้อความแรก
                'birthdate_detected',
                // 🎯 Phase D — AI + pool fail หมด → ชี้ปุ่ม ดูดวงละเอียด ให้ลูกค้า
                'welcome_guide_button',
            ];
            if (! empty($result['show_quick_replies']) || in_array($result['action'] ?? '', $actionsWithQuickReplies)) {
                $this->sendConversationQuickReplies($senderId, $result['action']);
            }

            Log::info('Conversational Fortune (fallback): ประมวลผลสำเร็จ', [
                'facebook_user_id' => $senderId,
                'action' => $result['action'],
                'reading_id' => $result['reading']?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Conversational Fortune Error: '.$e->getMessage(), [
                'facebook_user_id' => $senderId,
                'message' => $messageText,
                'error_class' => get_class($e),
                'trace' => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);

            try {
                $this->facebookService->sendTypingIndicator($senderId, false);
            } catch (\Exception $ignored) {
            }

            // ส่งข้อความที่เป็นมิตรกับผู้ใช้ แทนที่จะบอกว่า "ผิดพลาด"
            try {
                // ตัด "เช็คสิทธิ์ฟรี" ออกถ้า admin ปิดบริการฟรี
                $freeEnabled = $this->settings->isFreeReadingEnabled();
                $checkHint = $freeEnabled
                    ? "💡 ลองพิมพ์ 'เช็คสิทธิ์' เพื่อดูสิทธิ์ดูดวงฟรี\n"
                    : '';
                $this->facebookService->sendMessage(
                    $senderId,
                    "🔮 สวัสดีค่ะ\n\n".
                    "ตอนนี้ระบบกำลังปรับปรุงชั่วคราวค่ะ\n".
                    "กรุณาลองพิมพ์มาใหม่อีกครั้งนะคะ 🙏\n\n".
                    $checkHint.
                    'หรือพิมพ์คำถามใหม่ได้เลยค่ะ ✨'
                );
            } catch (\Exception $sendError) {
                Log::error('ส่งข้อความ error response ไม่สำเร็จ: '.$sendError->getMessage());
            }
        }
    }

    /**
     * ส่งข้อความยาวโดยแบ่งเป็นหลายข้อความ
     */
    protected function sendLongMessage(string $senderId, string $message): void
    {
        // Facebook Messenger รองรับข้อความยาวสูงสุด 2000 ตัวอักษร
        $maxLength = 1800;

        if (mb_strlen($message) <= $maxLength) {
            $result = $this->facebookService->sendMessage($senderId, $message);
            if (! $result) {
                Log::error('❌ sendLongMessage: ส่งข้อความล้มเหลว', [
                    'recipient' => $senderId,
                    'message_length' => mb_strlen($message),
                ]);
            }

            return;
        }

        // แบ่งข้อความตาม paragraph หรือ ═══
        $parts = preg_split('/(?=═══════════════════════)/', $message);

        $currentMessage = '';
        $chunkIndex = 0;
        foreach ($parts as $part) {
            if (mb_strlen($currentMessage.$part) > $maxLength && ! empty($currentMessage)) {
                $chunkResult = $this->facebookService->sendMessage($senderId, trim($currentMessage));
                if (! $chunkResult) {
                    Log::error('❌ sendLongMessage: ส่งข้อความส่วนที่ '.($chunkIndex + 1).' ล้มเหลว', [
                        'recipient' => $senderId,
                        'chunk_length' => mb_strlen($currentMessage),
                    ]);
                }
                $chunkIndex++;
                usleep(300000); // รอ 300ms ระหว่างข้อความ
                $currentMessage = $part;
            } else {
                $currentMessage .= $part;
            }
        }

        if (! empty($currentMessage)) {
            $chunkResult = $this->facebookService->sendMessage($senderId, trim($currentMessage));
            if (! $chunkResult) {
                Log::error('❌ sendLongMessage: ส่งข้อความส่วนสุดท้ายล้มเหลว', [
                    'recipient' => $senderId,
                    'chunk_length' => mb_strlen($currentMessage),
                ]);
            }
        }
    }

    /**
     * ส่ง Quick Replies ตาม action
     *
     * ถ้า user มีบิลดูดวงที่ชำระเงินแล้ววันนี้ → แสดงปุ่ม "📜 คำทำนายล่าสุด" เพิ่ม
     */
    protected function sendConversationQuickReplies(string $senderId, string $action): void
    {
        $quickReplies = match ($action) {
            'awaiting_confirmation' => [
                ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'FORTUNE_LOVE'],
                ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'FORTUNE_WORK'],
                ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'FORTUNE_MONEY'],
                ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'FORTUNE_HEALTH'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงรวม', 'payload' => 'FORTUNE_OVERVIEW'],
            ],
            // 🎯 Phase E — เอาปุ่ม "เช็คสิทธิ์" ออก (ซ้ำซ้อน)
            'basic_done' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'DEEP_READING_ACCEPT'],
                ['content_type' => 'text', 'title' => '❌ ไม่ต้องค่ะ', 'payload' => 'DEEP_READING_NO'],
                ['content_type' => 'text', 'title' => '💕 ถามเรื่องรัก', 'payload' => 'FORTUNE_LOVE'],
                ['content_type' => 'text', 'title' => '💼 ถามเรื่องงาน', 'payload' => 'FORTUNE_WORK'],
            ],
            'check_remaining' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_DEEP'],
            ],
            'collecting_questions', 'need_more_questions', 'retry_question' => [
                ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'QUESTION_LOVE'],
                ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'QUESTION_WORK'],
                ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'QUESTION_MONEY'],
                ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'QUESTION_HEALTH'],
                ['content_type' => 'text', 'title' => '✏️ พิมพ์เอง', 'payload' => 'QUESTION_CUSTOM'],
            ],
            'ai_limit', 'payment_expired' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_DEEP'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
            ],
            // 🎯 Phase E — เอาปุ่ม "เช็คสิทธิ์" ออก (ซ้ำซ้อน)
            'declined', 'completed' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_DEEP'],
            ],
            'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_BASIC'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_DEEP'],
            ],
            // 🎯 Phase A.2 (FB) — escape-hatch buttons ระหว่างขั้นตอนกรอกข้อมูล
            // รองรับผู้สูงวัยที่พิมพ์ keyword ไม่ได้ ให้กดปุ่มแทน
            // 🎯 Phase E — เอาปุ่ม "วิธีใช้งาน" ออก (ซ้ำซ้อนกับ AI chat)
            'invalid_birthdate', 'collecting_birthdate' => [
                ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL'],
            ],
            'awaiting_question' => [
                ['content_type' => 'text', 'title' => '💕 ความรัก', 'payload' => 'QUESTION_LOVE'],
                ['content_type' => 'text', 'title' => '💼 การงาน', 'payload' => 'QUESTION_WORK'],
                ['content_type' => 'text', 'title' => '💰 การเงิน', 'payload' => 'QUESTION_MONEY'],
                ['content_type' => 'text', 'title' => '🏥 สุขภาพ', 'payload' => 'QUESTION_HEALTH'],
                ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL'],
            ],
            // 🎯 Phase E — เอาปุ่ม "วิธีใช้งาน" ออก
            'pending_payment', 'waiting_payment' => [
                ['content_type' => 'text', 'title' => '❌ ยกเลิกบิล', 'payload' => 'CANCEL_PAYMENT'],
            ],
            // 🎯 Phase C — ลูกค้าพิมพ์วันเกิดมาก่อน → ถาม "ดูดวงเชิงลึกไหม?"
            'birthdate_detected' => [
                ['content_type' => 'text', 'title' => '💎 ดูดวงเชิงลึก', 'payload' => 'DEEP_WITH_BIRTHDATE'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงฟรีก่อน', 'payload' => 'FORTUNE_OVERVIEW'],
                ['content_type' => 'text', 'title' => '❌ ยังไม่ก่อน', 'payload' => 'CANCEL'],
            ],
            // 🎯 Phase D — welcome guide (แสดงตอน AI fail / ข้อความไม่ match intent ใด)
            //   ชี้ปุ่มชัดเจน — ผู้สูงวัยไม่ต้องเดาว่าต้อง "พิมพ์" อะไร
            // 🎯 Phase E — เอาปุ่ม "วิธีใช้งาน" ออก (ซ้ำซ้อนกับ AI chat)
            'welcome_guide_button' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'DEEP_READING_ACCEPT'],
                ['content_type' => 'text', 'title' => '🔮 ดูดวงฟรี', 'payload' => 'FORTUNE_OVERVIEW'],
            ],
            default => null,
        };

        // 🎯 Phase C — กรองปุ่มตามสถานะระบบ (ปิด free / deep)
        //   ถ้าปิดระบบฟรี → ซ่อนปุ่มที่พึ่งระบบฟรี
        //   ถ้าปิดระบบเชิงลึก → ซ่อนปุ่มที่พึ่ง deep reading
        if ($quickReplies) {
            $freeEnabled = $this->settings->isFreeReadingEnabled();
            $deepEnabled = $this->settings->isDeepReadingEnabled();

            // Payload ที่ต้องมี free เปิดอยู่
            $freeOnlyPayloads = [
                'FORTUNE_OVERVIEW', 'FORTUNE_LOVE', 'FORTUNE_WORK',
                'FORTUNE_MONEY', 'FORTUNE_HEALTH', 'CHECK_REMAINING',
            ];
            // Payload ที่ต้องมี deep เปิดอยู่
            $deepOnlyPayloads = [
                'DEEP_READING_ACCEPT', 'DEEP_WITH_BIRTHDATE',
                'FORTUNE_BASIC', 'FORTUNE_DEEP',
            ];

            $quickReplies = array_values(array_filter($quickReplies, function ($btn) use ($freeEnabled, $deepEnabled, $freeOnlyPayloads, $deepOnlyPayloads) {
                $payload = $btn['payload'] ?? '';
                if (! $freeEnabled && in_array($payload, $freeOnlyPayloads, true)) {
                    return false;
                }
                if (! $deepEnabled && in_array($payload, $deepOnlyPayloads, true)) {
                    return false;
                }

                return true;
            }));

            // หลังกรอง ถ้าไม่เหลือปุ่มอะไร → ไม่ส่ง quick replies
            if (empty($quickReplies)) {
                $quickReplies = null;
            }
        }

        // เพิ่มปุ่ม "📜 คำทำนายล่าสุด" ถ้า user มีบิลที่จ่ายวันนี้
        // แสดงเฉพาะ actions ที่ไม่ได้อยู่กลาง flow (ไม่ใส่ตอนเก็บคำถาม/รอชำระ)
        if ($quickReplies) {
            $showViewLastButton = in_array($action, [
                'awaiting_confirmation', 'basic_done', 'check_remaining',
                'ai_limit', 'payment_expired', 'declined', 'completed',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing',
                'view_reading_empty',
            ]);

            if ($showViewLastButton && $this->hasTodayPaidReading($senderId)) {
                // Facebook Quick Replies รองรับสูงสุด 13 ปุ่ม — ต่อท้ายได้เลย
                $quickReplies[] = ['content_type' => 'text', 'title' => '📜 คำทำนายล่าสุด', 'payload' => 'VIEW_LAST_READING'];
            }
        }

        if ($quickReplies) {
            // ส่ง Quick Replies แยก
            usleep(500000); // รอ 500ms
            $this->facebookService->sendQuickReplies(
                $senderId,
                'เลือกได้เลยค่ะ 👇',
                $quickReplies
            );
        }
    }

    /**
     * ตรวจสอบว่า user มีบิลดูดวงที่ชำระเงินแล้ววันนี้หรือไม่
     *
     * ใช้สำหรับแสดง/ซ่อนปุ่ม "📜 คำทำนายล่าสุด"
     */
    protected function hasTodayPaidReading(string $facebookUserId): bool
    {
        return FortuneReading::where('facebook_user_id', $facebookUserId)
            ->where('is_paid', true)
            ->whereDate('paid_at', today())
            ->exists();
    }

    /**
     * ทำนายดวง (รองรับพื้นฐานและเชิงลึก)
     *
     * เมื่อ queue driver ไม่ใช่ 'sync' จะ dispatch job แบบ async
     * เพื่อไม่ให้ webhook response ช้า (Facebook timeout 20 วินาที)
     * ถ้าเป็น 'sync' จะประมวลผลแบบ inline ทันที
     *
     * @param  array  $data  ข้อมูลจาก Facebook
     * @param  array  $questions  คำถามที่แยกแล้ว
     * @param  bool  $isComment  เป็นคอมเมนต์หรือ direct message
     * @param  bool  $isDeep  เป็นคำทำนายเชิงลึกหรือไม่
     * @param  string|null  $userImageUrl  URL รูปที่ผู้ใช้ส่งมา
     * @param  string|null  $birthDate  วันเกิดรูปแบบ Y-m-d
     */
    protected function processFortuneTelling(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep = false,
        ?string $userImageUrl = null,
        ?string $birthDate = null
    ): void {
        $fromId = $isComment ? ($data['from']['id'] ?? null) : ($data['sender']['id'] ?? null);
        $fromName = $isComment ? ($data['from']['name'] ?? null) : null;

        if (empty($fromId)) {
            return;
        }

        // เตรียมข้อมูลสำหรับ job
        $jobData = [
            'facebook_user_id' => $fromId,
            'facebook_user_name' => $fromName,
            'questions' => $questions,
            'is_comment' => $isComment,
            'is_deep' => $isDeep,
            'is_paid' => false,
            'amount_paid' => 0,
            'user_image_url' => $userImageUrl,
            'birth_date' => $birthDate,
            'comment_id' => $isComment ? ($data['comment_id'] ?? null) : null,
            'post_id' => $isComment ? ($data['post_id'] ?? null) : null,
            'reply_type' => $isComment ? 'comment' : 'message',
        ];

        // ใช้ queue แบบ async เมื่อมี queue driver ที่รองรับ
        // เพื่อ return 200 ให้ Facebook ทันเวลา (Facebook timeout 20 วินาที)
        $queueDriver = config('queue.default', 'sync');

        if ($queueDriver !== 'sync') {
            // Async: dispatch job ให้ queue จัดการ
            ProcessFortuneTelling::dispatch($jobData);

            Log::info('Fortune telling job dispatched (async)', [
                'facebook_user_id' => $fromId,
                'reading_type' => $isDeep ? 'deep' : 'basic',
                'queue_driver' => $queueDriver,
            ]);

            return;
        }

        // Sync fallback: ประมวลผลทันที (สำหรับ dev หรือเมื่อไม่มี queue)
        $this->processFortuneSync($data, $questions, $isComment, $isDeep, $userImageUrl, $fromId, $fromName, $birthDate);
    }

    /**
     * ประมวลผลคำทำนายแบบ synchronous (ใช้เมื่อ queue = sync)
     *
     * @param  array  $data  ข้อมูลจาก Facebook
     * @param  array  $questions  คำถาม
     * @param  bool  $isComment  เป็นคอมเมนต์
     * @param  bool  $isDeep  เป็นเชิงลึก
     * @param  string|null  $userImageUrl  รูปจากผู้ใช้
     * @param  string  $fromId  Facebook User ID
     * @param  string|null  $fromName  ชื่อผู้ใช้
     * @param  string|null  $birthDate  วันเกิดรูปแบบ Y-m-d
     */
    protected function processFortuneSync(
        array $data,
        array $questions,
        bool $isComment,
        bool $isDeep,
        ?string $userImageUrl,
        string $fromId,
        ?string $fromName,
        ?string $birthDate = null
    ): void {
        // ส่ง typing indicator ขณะ AI กำลังประมวลผล
        if (! $isComment) {
            $this->facebookService->sendTypingIndicator($fromId);
        }

        $userProfile = $this->facebookService->getUserProfile($fromId);
        // ✅ ป้องกัน null userProfile
        if (! is_array($userProfile)) {
            $userProfile = [
                'name' => $fromName ?? 'คุณ',
                'id' => $fromId,
            ];
        }
        // ดึงโพสล่าสุดเฉพาะคำทำนายเชิงลึก
        $userPosts = $isDeep ? $this->facebookService->getUserPosts($fromId, 3) : null;

        try {
            // เลือก prompt template ตามระดับ
            $promptTemplate = $isDeep
                ? $this->settings->getDeepPromptTemplate()
                : $this->settings->getBasicPromptTemplate();

            $readingType = $isDeep ? 'deep' : 'basic';

            $aiResponse = $this->aiService->generateFortuneTelling(
                $questions,
                $userProfile,
                $userPosts,
                $promptTemplate,
                $readingType,
                $birthDate
            );

            // บันทึกผลลงฐานข้อมูล
            $reading = FortuneReading::create([
                'facebook_user_id' => $fromId,
                'facebook_user_name' => $fromName ?? $userProfile['name'] ?? null,
                'facebook_comment_id' => $isComment ? ($data['comment_id'] ?? null) : null,
                'facebook_post_id' => $isComment ? ($data['post_id'] ?? null) : null,
                'questions' => $questions,
                'ai_response' => $aiResponse['response'],
                'user_profile' => $userProfile,
                'user_posts_context' => $userPosts,
                'birth_date' => $birthDate,
                'ai_provider' => $aiResponse['provider'],
                'ai_model' => $aiResponse['model'],
                'tokens_used' => $aiResponse['tokens_used'],
                'response_type' => ($isComment && $this->settings->respond_in_comment) ? 'comment' : 'private_message',
                'reading_type' => $readingType,
                'user_image_url' => $userImageUrl,
                'is_paid' => false,
            ]);

            // ปิด typing indicator
            if (! $isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            // ส่งคำทำนายกลับ (รองรับรูปภาพ + message splitting)
            if ($this->facebookService->sendFortuneTelling($reading, $aiResponse['response'])) {
                $reading->markAsResponded();
            }

            // หลังส่งคำทำนายเชิงลึกฟรี ส่ง quick replies แนะนำจ่ายเงิน/สมัครสมาชิก
            if ($isDeep && $this->settings->isTryBeforeBuyEnabled() && ! $isComment) {
                $tryBeforeBuyMsg = $this->settings->getTryBeforeBuyMessage();
                $this->facebookService->sendQuickReplies($fromId, $tryBeforeBuyMsg, [
                    ['title' => 'ดูดวงอีกครั้ง', 'payload' => 'FORTUNE_BASIC'],
                    ['title' => 'ดูดวงเชิงลึก', 'payload' => 'FORTUNE_DEEP'],
                    ['title' => 'สมัครสมาชิก', 'payload' => 'SUBSCRIBE'],
                ]);
            }

            Log::info('ทำนายสำเร็จ (sync)', [
                'reading_id' => $reading->id,
                'type' => $readingType,
                'provider' => $aiResponse['provider'],
                'tokens' => $aiResponse['tokens_used'],
            ]);
        } catch (\Exception $e) {
            Log::error('เกิดข้อผิดพลาดในการทำนาย: '.$e->getMessage(), [
                'from_id' => $fromId,
                'is_deep' => $isDeep,
                'trace' => $e->getTraceAsString(),
            ]);

            // ปิด typing indicator
            if (! $isComment) {
                $this->facebookService->sendTypingIndicator($fromId, false);
            }

            try {
                // ตัด "เช็คสิทธิ์ฟรี" ออกถ้า admin ปิดบริการฟรี
                $freeEnabled = $this->settings->isFreeReadingEnabled();
                $checkHint = $freeEnabled
                    ? "💡 พิมพ์ 'เช็คสิทธิ์' เพื่อดูสิทธิ์ดูดวงฟรี\n"
                    : '';
                $this->facebookService->sendMessage(
                    $fromId,
                    "🔮 สวัสดีค่ะ\n\nตอนนี้ระบบกำลังปรับปรุงชั่วคราวค่ะ\nกรุณาลองพิมพ์มาใหม่อีกครั้งนะคะ 🙏\n\n{$checkHint}หรือพิมพ์คำถามใหม่ได้เลยค่ะ ✨"
                );
            } catch (\Exception $sendError) {
                Log::error('ส่งข้อความ error ไม่สำเร็จ: '.$sendError->getMessage());
            }
        }
    }

    /**
     * จัดการ Quick Reply payload
     */
    protected function handleQuickReply(string $senderId, string $payload): void
    {
        match ($payload) {
            // Quick Replies ใหม่สำหรับ conversational flow
            'DEEP_READING_ACCEPT' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'DEEP_READING_DECLINE' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),
            'DEEP_READING_NO' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),

            // Quick Replies หมวดคำทำนาย (ดูดวงฟรี)
            'FORTUNE_LOVE' => $this->processConversationalMessage($senderId, 'ดูดวงความรัก เนื้อคู่ คู่ครอง'),
            'FORTUNE_WORK' => $this->processConversationalMessage($senderId, 'ดูดวงการงาน อาชีพ เลื่อนตำแหน่ง'),
            'FORTUNE_MONEY' => $this->processConversationalMessage($senderId, 'ดูดวงการเงิน รายได้ การลงทุน'),
            'FORTUNE_HEALTH' => $this->processConversationalMessage($senderId, 'ดูดวงสุขภาพ สิ่งที่ต้องระวัง'),
            'FORTUNE_OVERVIEW' => $this->processConversationalMessage($senderId, 'ดูดวงภาพรวมทุกด้าน ความรัก การงาน การเงิน สุขภาพ'),

            // Quick Replies สำหรับเลือกหมวดคำถาม (ดูดวงละเอียด — เก็บทีละข้อ)
            'QUESTION_LOVE' => $this->handleCategoryQuestion($senderId, 'love'),
            'QUESTION_WORK' => $this->handleCategoryQuestion($senderId, 'work'),
            'QUESTION_MONEY' => $this->handleCategoryQuestion($senderId, 'money'),
            'QUESTION_HEALTH' => $this->handleCategoryQuestion($senderId, 'health'),
            'QUESTION_CUSTOM' => $this->sendCustomQuestionPrompt($senderId),

            // Quick Reply ดูคำทำนายล่าสุด
            'VIEW_LAST_READING' => $this->processConversationalMessage($senderId, 'ดูคำทำนาย'),

            // ✅ LINE Invite + Affiliate Share (จาก Rich Templates)
            'LINE_ADD_FRIEND' => $this->handleLineAddFriend($senderId),
            'LINE_INVITE' => $this->handleLineAddFriend($senderId),
            'AFFILIATE_SHARE' => $this->processConversationalMessage($senderId, 'แชร์'),

            // 💰 Affiliate Recruitment — Quick Replies จาก comment engagement pitch
            // (FB ส่ง quick_reply.payload เป็น message event ไม่ใช่ postback)
            'AFFILIATE_RECRUIT_YES' => $this->handleAffiliateRecruitYes($senderId),
            'AFFILIATE_RECRUIT_NO' => $this->handleAffiliateRecruitNo($senderId),

            // Quick Replies ที่ mirror Postback payloads จาก Rich Templates
            // ผู้สูงอายุงง → ทั้ง 2 ปุ่มเข้า deep flow ตรงๆ (→ tier menu 39 vs 99)
            'MENU_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'MENU_DEEP_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            // 🩹 (2026-05-08) Single-click fix — ลูกค้ากด 39 / 99 → เข้า flow ตรงไม่ผ่าน tier menu
            //   เดิม: TIER_DEEP_39 → "ดูดวง 39 บาท" → startDeepReadingFlow → tier menu (ต้องกด 2 ครั้ง)
            //   ใหม่: ตั้ง Cache flag forceTier → processMessage จะเช็คที่ top แล้ว skip tier menu
            'TIER_DEEP_39' => (function () use ($senderId) {
                Cache::put("fortune:force_tier:{$senderId}", 'deep', 30);
                $this->processConversationalMessage($senderId, 'ดูดวง');
            })(),
            'TIER_CELTIC_99' => (function () use ($senderId) {
                Cache::put("fortune:force_tier:{$senderId}", 'celtic', 30);
                $this->processConversationalMessage($senderId, 'ดูดวง');
            })(),
            // 🆓 (2026-05-01) ปุ่ม "ดูดวงฟรี" จาก welcome — ส่ง category picker (ไม่ผ่าน tier menu)
            //   ⚠️ legacy — ระบบฟรีแบบเก่า, ค่อยๆ หายไป
            'FORTUNE_FREE' => $this->handleFortuneFreePicker($senderId),
            // 🎁 (2026-05-03) ทำนายฟรี 1 ใบ — ระบบใหม่ (ครั้งแรก/platform)
            //   ส่ง keyword "ทำนายฟรี" → matchesFreeCardKeyword() จับได้ใน processMessage
            //   → startFreeCardFlow() เช็ค first-timer + เปิดสิทธิ์ → จั่วไพ่ + AI ทำนาย
            'FREE_CARD_START' => $this->processConversationalMessage($senderId, 'ทำนายฟรี'),
            // 🌙 (2026-05-03) ลูกค้าปฏิเสธ upsell หลังทำนายฟรี — ส่งไปที่ trait
            'FREE_CARD_DECLINE' => $this->processConversationalMessage($senderId, 'ไม่สนใจ'),
            // 💎 (2026-05-03) Request-Before-Pay confirmation (Deep 39 first-time only)
            'DELIVERY_CONFIRM_YES' => $this->processConversationalMessage($senderId, 'รับคำทำนาย'),
            'DELIVERY_CONFIRM_NO' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            // 🆕 (2026-05-04) Pay-later reconfirm payloads
            'PAY_LATER_ACK_YES' => $this->processConversationalMessage($senderId, 'ใช่'),
            'PAY_LATER_ACK_NO' => $this->processConversationalMessage($senderId, 'ยกเลิก'),

            // 💳 (2026-05-16) Payment method selection — Stripe gate buttons
            //   user report: ลูกค้ากด "QR ไทย" ใน Celtic 99฿ menu → AI ตอบหลอน "39 บาท"
            //   root cause: default case ส่ง "PAY_METHOD_QR_THAI" raw → handlePaymentMethodSelection
            //              บางเงื่อนไขไม่ match (state race / ไม่ใช่ AWAITING_PAYMENT_METHOD)
            //              → AI fallback hallucinate
            //   fix: translate payload → keyword ชัดเจน "qr ไทย" / "บัตร"
            //        keyword นี้จับได้ทั้ง handlePaymentMethodSelection + maybePresentPaymentInfo
            //        → routing ปลอดภัยกว่าทุก state
            'PAY_METHOD_QR_THAI' => $this->processConversationalMessage($senderId, 'qr ไทย'),
            'PAY_METHOD_STRIPE' => $this->processConversationalMessage($senderId, 'บัตร'),
            'STRIPE_OPEN_CHECKOUT' => $this->processConversationalMessage($senderId, 'STRIPE_OPEN_CHECKOUT'),
            'STRIPE_RESUME' => $this->processConversationalMessage($senderId, 'STRIPE_RESUME'),

            // ✅ ปุ่มจาก Button Templates
            'REPORT_PAYMENT' => $this->processConversationalMessage($senderId, 'แจ้งชำระเงิน'),
            'CANCEL_PAYMENT' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            // 🛑 (2026-05-15) Cancel-reason prompt postbacks — ลูกค้าตอบ "ติดปัญหาอะไร?"
            'CANCEL_HELP_TRANSFER' => $this->processConversationalMessage($senderId, 'ขอเลขบัญชี'),
            'CANCEL_HELP_ADMIN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            'CANCEL_CONFIRM_REAL' => $this->processConversationalMessage($senderId, 'ยืนยันยกเลิก'),
            // 🛑 (2026-05-15 v2) cancelled_to_chat → ปุ่ม "คุยกับแม่หมอ"
            'TALK_ADMIN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            'SHOW_BANK_ACCOUNT' => $this->processConversationalMessage($senderId, 'แสดงบัญชี'),
            'CANCEL_DEEP' => $this->processConversationalMessage($senderId, 'ไม่ต้องการ'),
            // 🛠️ (2026-05-01) CANCEL_FORTUNE จาก tier menu — pre-existing payload ไม่เคยมี handler
            //    ทำให้ปุ่ม ❌ ยกเลิก ใน tier menu ไม่ทำงาน → fix ที่นี่
            'CANCEL_FORTUNE' => $this->processConversationalMessage($senderId, 'ยกเลิก'),
            // 🃏 (2026-05-03) Celtic pick buttons — คนแก่กดปุ่มแทนการพิมพ์
            'CELTIC_READY' => $this->processConversationalMessage($senderId, 'พร้อม'),
            'CELTIC_RESET' => $this->processConversationalMessage($senderId, 'สับใหม่'),
            'CELTIC_CONTINUE' => $this->processConversationalMessage($senderId, 'ถามต่อ'),

            // 🌙 (2026-05-23) Celtic end-session 2-step confirm
            //    user spec: "ปุ่มยุติทำนายเปลี่ยนเป็น เลิกทำนายและสรุปผล + ถามก่อนว่าจะเลิกแล้วสรุปเลย
            //                 จริงไหม เพราะบางคนมือไปกดผิด"
            //    CELTIC_END_ASK = กดปุ่มเลิก → ส่ง confirm dialog
            //    CELTIC_END_YES = ยืนยัน → call endCelticSession (Grand Finale)
            //    CELTIC_END_NO  = ยกเลิก → clear flag + กลับ Q&A normal
            'CELTIC_END_ASK' => $this->processConversationalMessage($senderId, 'เลิกทำนายและสรุปผล'),
            'CELTIC_END_YES' => $this->processConversationalMessage($senderId, 'ส่งสรุปเลย'),
            'CELTIC_END_NO' => $this->processConversationalMessage($senderId, 'ขอคุยต่อ'),
            // Backward compat — ลูกค้าที่ FB cache ปุ่มเก่าไว้ (CELTIC_DONE) → เข้าสู่ confirm dialog
            'CELTIC_DONE' => $this->processConversationalMessage($senderId, 'เลิกทำนายและสรุปผล'),

            // 📜 (2026-05-03) Celtic Q&A review — ดูคำตอบที่ผ่านมา (state ไม่เปลี่ยน)
            'CELTIC_VIEW_LIST' => $this->handleCelticViewList($senderId),
            'CELTIC_VIEW_Q1' => $this->handleCelticViewQuestion($senderId, 1),
            'CELTIC_VIEW_Q2' => $this->handleCelticViewQuestion($senderId, 2),
            'CELTIC_VIEW_Q3' => $this->handleCelticViewQuestion($senderId, 3),
            'CELTIC_VIEW_Q4' => $this->handleCelticViewQuestion($senderId, 4),
            'CELTIC_VIEW_Q5' => $this->handleCelticViewQuestion($senderId, 5),
            'CELTIC_VIEW_Q6' => $this->handleCelticViewQuestion($senderId, 6),
            'CELTIC_VIEW_Q7' => $this->handleCelticViewQuestion($senderId, 7),
            'CELTIC_VIEW_Q8' => $this->handleCelticViewQuestion($senderId, 8),
            'CELTIC_VIEW_Q9' => $this->handleCelticViewQuestion($senderId, 9),
            'CELTIC_VIEW_Q10' => $this->handleCelticViewQuestion($senderId, 10),
            // 📅 (2026-05-01) confirm/reject buttons (mirror postback handler)
            'BIRTHDATE_CONFIRM_YES' => $this->processConversationalMessage($senderId, 'ใช่'),
            'BIRTHDATE_CONFIRM_NO' => $this->processConversationalMessage($senderId, 'ไม่ใช่'),
            'QUESTION_CONFIRM_YES' => $this->processConversationalMessage($senderId, 'ใช่'),
            'QUESTION_CONFIRM_NO' => $this->processConversationalMessage($senderId, 'ไม่ตรงคำถาม'),
            // ผู้สูงอายุงง → ปุ่ม "ดูดวงใหม่/ต่อ" → deep flow ตรง
            'NEW_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'VIEW_READING' => $this->processConversationalMessage($senderId, 'ดูคำทำนาย'),

            // ✅ Phase A quick reply payloads (จาก FortuneChannelManager getFacebookFallbackQuickReplies)
            'TALK_HUMAN' => $this->processConversationalMessage($senderId, 'คุยกับแม่หมอ'),
            // ผู้สูงอายุงง → ปุ่ม "เริ่มดูดวง" → deep flow ตรง
            'START_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'DEEP_FORTUNE' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'CHECK_STATUS' => $this->processConversationalMessage($senderId, 'เช็คสถานะ'),
            'RESTART' => $this->processConversationalMessage($senderId, 'เริ่มใหม่'),
            'CANCEL' => $this->processConversationalMessage($senderId, 'ยกเลิก'),

            // 🎯 Phase C — ลูกค้าพิมพ์วันเกิดมาก่อน → กดปุ่ม "ดูดวงเชิงลึก" → ใช้วันเกิดที่ cache ไว้
            'DEEP_WITH_BIRTHDATE' => $this->processConversationalMessage($senderId, '__DEEP_WITH_CACHED_BIRTHDATE__'),

            // ✅ Post-reading payloads (จบคำทำนาย → เชิญแชร์/ชวนเพื่อน)
            'VIEW_LATER' => $this->facebookService->sendMessage($senderId, "✨ ได้เลย! เมื่อพร้อมดูแล้ว พิมพ์ 'ดูคำทำนาย' ได้ทุกเมื่อ 🔮"),
            'FORTUNE_EARN_INFO' => $this->handleFortuneEarnInfo($senderId),
            'SHARE_PAGE' => $this->handleSharePage($senderId),

            // Quick Replies เดิม (backward compatibility)
            // ผู้สูงอายุงง → ปุ่ม "ดูดวง" และ "ดูดวงละเอียด" → deep flow เดียวกัน
            'FORTUNE_BASIC' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'FORTUNE_DEEP' => $this->processConversationalMessage($senderId, 'ดูดวง'),
            'CHECK_REMAINING' => $this->processConversationalMessage($senderId, 'เช็คสิทธิ์'),
            'SUBSCRIBE' => $this->facebookService->sendMessage(
                $senderId,
                $this->settings->getSubscriptionMessage()
            ),
            'HELP' => $this->sendHelpMessage($senderId),

            // 👁️ Follow-page confirmation (safety: postback usually routes via processPostback)
            'FOLLOW_CONFIRMED' => $this->handleFollowConfirmed($senderId),

            default => $this->processConversationalMessage($senderId, $payload),
        };
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (พื้นฐาน)
     */
    protected function sendLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'] ?? '', $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'] ?? '', $message);
        }
    }

    /**
     * ส่งข้อความครบจำนวนฟรี (เชิงลึก)
     */
    protected function sendDeepLimitMessage(array $comment): void
    {
        $message = $this->facebookService->getDeepLimitExceededMessage();

        if ($this->settings->respond_in_comment) {
            $this->facebookService->replyToComment($comment['comment_id'] ?? '', $message);
        } else {
            $this->facebookService->sendMessage($comment['from']['id'] ?? '', $message);
        }
    }

    /**
     * จัดการเมื่อ user กดปุ่มเลือกหมวดคำถาม (Quick Reply)
     *
     * ดึงคำถามสำเร็จรูปจาก CATEGORY_QUESTION_MAP แล้วส่งเข้า conversation
     * เหมือนกับว่า user พิมพ์คำถามเอง
     *
     * @param  string  $senderId  Facebook user ID
     * @param  string  $category  หมวดคำถาม (love, work, money, health)
     */
    protected function handleCategoryQuestion(string $senderId, string $category): void
    {
        try {
            // ดึง active reading เพื่อเช็คคำถามที่เก็บไปแล้ว
            $reading = FortuneReading::where('facebook_user_id', $senderId)
                ->where('conversation_status', FortuneReading::STATUS_COLLECTING_QUESTIONS)
                ->latest()
                ->first();

            $existingQuestions = [];
            if ($reading) {
                $existingQuestions = $reading->getCollectedQuestions();
            }

            // สร้างคำถามจากหมวดที่เลือก (ไม่ซ้ำกับที่เก็บไปแล้ว)
            $question = $this->conversationService->getQuestionForCategory($category, $existingQuestions);

            // ส่งเข้า processConversationalMessage เหมือน user พิมพ์เอง
            $this->processConversationalMessage($senderId, $question);
        } catch (\Exception $e) {
            Log::error('handleCategoryQuestion error: '.$e->getMessage(), [
                'sender_id' => $senderId,
                'category' => $category,
            ]);
            // Fallback: ส่งข้อความแจ้ง
            $this->facebookService->sendMessage($senderId, '🔮 ขอโทษค่ะ ลองกดเลือกอีกครั้งนะคะ ✨');
        }
    }

    /**
     * ส่ง prompt ให้ user พิมพ์คำถามเอง
     *
     * เมื่อ user กดปุ่ม "✏️ พิมพ์เอง" จะส่งข้อความตัวอย่างให้
     *
     * @param  string  $senderId  Facebook user ID
     */
    protected function sendCustomQuestionPrompt(string $senderId): void
    {
        $message = "✏️ *พิมพ์คำถามที่ต้องการถามได้เลยค่ะ*\n\n";
        $message .= "💡 ตัวอย่าง:\n";
        $message .= "• ปีนี้จะมีคู่ครองไหม\n";
        $message .= "• ควรเปลี่ยนงานตอนนี้ไหม\n";
        $message .= "• การเงินช่วงนี้จะเป็นอย่างไร\n\n";
        $message .= 'พิมพ์คำถามมาได้เลยค่ะ 👇';

        $this->facebookService->sendMessage($senderId, $message);
    }

    /**
     * ส่งคำแนะนำการใช้งานพร้อม Quick Reply buttons
     */
    protected function sendHelpMessage(string $userId): void
    {
        // ✅ ใช้ Rich Welcome Template แทนข้อความธรรมดา
        try {
            $richService = new FacebookRichMessageService($this->settings);
            // 🩹 (2026-05-04) pass userId เพื่อตรวจ hasUsedFreeCard → ซ่อนปุ่ม/ข้อความฟรีถ้าใช้แล้ว
            $helpTemplate = $richService->buildWelcomeTemplate('คุณ', $userId);
            $helpQuickReplies = $richService->getQuickRepliesForAction('help');

            if ($helpTemplate && ! empty($helpTemplate['elements'])) {
                $this->facebookService->sendTemplateWithQuickReplies(
                    $userId,
                    [
                        'template_type' => 'generic',
                        'elements' => $helpTemplate['elements'],
                    ],
                    $helpQuickReplies
                );

                return;
            }
        } catch (\Exception $e) {
            Log::warning('sendHelpMessage: Rich Template ล้มเหลว ใช้ fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: ข้อความธรรมดา — gate "ฟรี" ตามสถานะระบบ + ใช้ราคา dynamic
        $freeEnabled = $this->settings->isFreeReadingEnabled();
        $freeDeep = (int) ($this->settings->free_deep_per_day ?? 0);
        $deepPriceText = number_format($this->getDeepReadingPriceFromSettings(), 0);

        $message = "🔮 ระบบดูดวง AI\n\n";
        if ($freeEnabled) {
            $message .= "📌 ดูดวงพื้นฐาน (ฟรี):\n";
        } else {
            $message .= "📌 ดูดวงพื้นฐาน:\n";
        }
        $message .= "พิมพ์: ดูดวง ตามด้วยคำถาม\n";
        $message .= "ตัวอย่าง: ดูดวง เรื่องความรัก, เรื่องการเงิน\n\n";

        if ($this->settings->isDeepReadingEnabled()) {
            $message .= "🌟 ดูดวงเชิงลึก:\n";
            $message .= "พิมพ์: ดูดวง ตามด้วยคำถาม\n";
            $message .= "ราคา {$deepPriceText} บาท/ครั้ง";
            if ($freeDeep > 0) {
                $message .= " (ทดลองฟรี {$freeDeep} ครั้ง/วัน)";
            }
            $message .= "\n\n";
        }

        $message .= "📸 ส่งรูปภาพ:\n";
        $message .= "ส่งรูปพร้อมข้อความ 'ดูดวง'\n";

        // ส่งพร้อม quick reply buttons
        // 🎯 Phase E — เอาปุ่ม "เช็คสิทธิ์" ออก (ซ้ำซ้อน)
        $quickReplies = [
            ['title' => $freeEnabled ? '🔮 ดูดวง' : '🔮 เริ่มดูดวง', 'payload' => 'FORTUNE_BASIC'],
        ];
        if ($this->settings->isDeepReadingEnabled()) {
            $quickReplies[] = ['title' => '🔮 ดูดวง', 'payload' => 'FORTUNE_DEEP'];
        }

        $this->facebookService->sendQuickReplies($userId, $message, $quickReplies);
    }

    /**
     * จัดการเมื่อ user กดปุ่ม "เพิ่มเพื่อน LINE"
     *
     * ส่ง LINE Add-Friend Template พร้อม URL + Quick Replies
     * ถ้าไม่มี LINE configured จะส่งข้อความทั่วไปแทน
     *
     * @param  string  $senderId  Facebook User ID
     */
    /**
     * ส่งข้อมูลการชวนเพื่อน/รับรายได้ พร้อมลิงก์ไปหน้าศึกษา
     */
    protected function handleFortuneEarnInfo(string $senderId): void
    {
        $appUrl = config('app.url', 'https://main.thaiprompt.online');
        $recruitUrl = $appUrl.'/auth/line?redirect=/user/fortune-referral/recruit';
        $wealthUrl = $appUrl.'/wealth-guide';

        // 🌙 (2026-05-23) ปรับ wording ตาม toggle — ไม่อ้าง "ดูดวงเชิงลึก" ถ้า Deep ปิด
        $deepEnabledEi = $this->settings->isDeepReadingEnabled();
        $earnTier = $deepEnabledEi ? 'ดูดวงเชิงลึก' : 'ดูดวง';

        $this->facebookService->sendButtonTemplate($senderId, [
            'template_type' => 'button',
            'text' => "📢 เชิญเพื่อนมาดูดวง — ได้รายได้จริง!\n\n"
                ."💰 ทุกครั้งที่เพื่อนคุณ{$earnTier} คุณจะได้ค่าแนะนำเข้า Wallet ทันที\n"
                .'🔗 กดศึกษาวิธีและรับลิงก์เชิญเพื่อนได้ด้านล่าง',
            'buttons' => [
                ['type' => 'web_url', 'title' => '🔗 รับลิงก์เชิญเพื่อน', 'url' => $recruitUrl],
                ['type' => 'web_url', 'title' => '📚 ศึกษาวิธีสร้างรายได้', 'url' => $wealthUrl],
                ['type' => 'postback', 'title' => '🔮 ดูดวงต่อ', 'payload' => 'FORTUNE_BASIC'],
            ],
        ]);
    }

    /**
     * แชร์เพจ Facebook ให้เพื่อน
     */
    protected function handleSharePage(string $senderId): void
    {
        $pageId = $this->settings->facebook_page_id ?? null;
        $pageUrl = $pageId ? "https://www.facebook.com/{$pageId}" : config('app.url');

        // 🌙 (2026-05-23) ปรับ wording ตาม toggle — ไม่อ้าง "ดูดวงเชิงลึก" ถ้า Deep ปิด
        $deepEnabledSp = $this->settings->isDeepReadingEnabled();
        $shareTier = $deepEnabledSp ? 'ดูดวงเชิงลึก' : 'ดูดวง';

        $this->facebookService->sendButtonTemplate($senderId, [
            'template_type' => 'button',
            'text' => "🙏 ขอบคุณที่ใช้บริการ!\n\n"
                ."📢 แชร์เพจนี้ให้เพื่อน — ทุกครั้งที่เพื่อนมา{$shareTier} คุณได้ค่าแนะนำเข้า Wallet",
            'buttons' => [
                ['type' => 'web_url', 'title' => '📤 แชร์เพจให้เพื่อน', 'url' => $pageUrl],
                ['type' => 'postback', 'title' => '📢 ดูวิธีรับรายได้', 'payload' => 'FORTUNE_EARN_INFO'],
                ['type' => 'postback', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'FORTUNE_BASIC'],
            ],
        ]);
    }

    protected function handleLineAddFriend(string $senderId): void
    {
        try {
            $richService = new FacebookRichMessageService($this->settings);
            $lineUrl = $richService->getLineAddFriendUrl();

            if ($lineUrl) {
                // ส่ง LINE Invite Template
                $lineTemplate = $richService->buildLineInviteTemplate();
                if ($lineTemplate) {
                    $this->facebookService->sendButtonTemplate($senderId, $lineTemplate);
                } else {
                    // Fallback: ส่ง URL โดยตรง
                    $this->facebookService->sendMessage(
                        $senderId,
                        "💚 เพิ่มเพื่อน LINE เพื่อดูดวงแบบสวยงาม!\n\n".
                        "👉 {$lineUrl}\n\n".
                        '✨ ดูดวง Flex Message สวยๆ ได้ที่ LINE ค่ะ'
                    );
                }
            } else {
                // ไม่มี LINE configured — แนะนำดูดวงผ่าน Facebook ต่อ
                $quickReplies = $richService->getQuickRepliesForAction('declined');
                $this->facebookService->sendQuickReplies(
                    $senderId,
                    "🔮 ดูดวงต่อได้เลยค่ะ!\n\nพิมพ์คำถามมาได้เลย หรือเลือกจากปุ่มด้านล่าง 👇",
                    $quickReplies
                );
            }

            Log::info('💚 LINE Add Friend: ส่ง invite สำเร็จ', [
                'sender_id' => $senderId,
                'has_line_url' => ! empty($lineUrl),
            ]);
        } catch (\Exception $e) {
            Log::error('handleLineAddFriend error: '.$e->getMessage(), [
                'sender_id' => $senderId,
            ]);
            $this->facebookService->sendMessage(
                $senderId,
                "🔮 ขอโทษค่ะ ลองพิมพ์ 'ดูดวง' เพื่อเริ่มต้นใหม่นะคะ ✨"
            );
        }
    }

    /**
     * 🚫 (2026-04-28) Anti-spam guard
     *
     * คืน true เมื่อควร silence (ไม่ตอบ — แต่ log)
     *
     * Strike rules (เก็บใน Cache 1 ชั่วโมง):
     *   1. ส่ง attachment + ไม่มี text + ไม่มี active reading → +1 strike
     *      (ถ้ามี active reading + payment pending → handleSlipImageOnly แทน — ไม่นับ)
     *   2. ข้อความมี URL → +1 strike (ลิงก์ภายนอก = สแปม spam-y)
     *   3. ข้อความเหมือนเดิมกับ 2 turn ก่อน → +1 strike (echo spam)
     *
     * เมื่อ strike >= 5 ภายใน 1 ชม. → silence (return true เสมอจนกว่าจะ expire)
     */
    /**
     * 🎯 (2026-05-02 — ปรับใหม่) Spam guard — แยก flood จากแชทปกติ
     *
     * user feedback: "การ block ที่บอกว่าส่งข้อความเยอะเกินไป ควรเป็น flood จงใจ
     *                ไม่ใช่การพูดคุยปกติ ต้องดูให้ออก"
     *
     * เกณฑ์ใหม่ (ทุกอันต้องเป็น "เร็วมาก/บ่อยมาก" ในช่วงเวลาสั้น — ไม่ใช่นานๆ ทีละครั้ง):
     *   1. RATE FLOOD: ส่ง > 10 messages ภายใน 30s = ตั้งใจ flood (ปกติคนพิมพ์ ~3-5/30s)
     *   2. REPEAT FLOOD: ส่งข้อความเดียวกัน ≥ 3 ครั้งติด ภายใน 30s
     *   3. SPAM URL: text มี URL ที่ไม่ใช่ domain ของเรา (link ภายนอก = สแปม)
     *
     * เกณฑ์เก่าที่ลบออก (เพราะ block ลูกค้าปกติ):
     *   ❌ Sticker/attachment เปล่า → คนแก่ส่งบ่อย ไม่ใช่ spam
     *   ❌ Same text ในช่วง 10 นาที → ลูกค้าพิมพ์ "ดูดวง" 2-3 ครั้งในวันเดียวกันเป็นเรื่องปกติ
     *   ❌ Strike accumulator 1 ชม. → ทำให้ลูกค้าค้าง strike จาก request เก่า
     *
     * Silence: 5 นาที (ลดจาก 1 ชม.) — ถ้าคน flood จริงๆ จะหายไปเอง,
     *   ลูกค้าปกติจะ recover ได้เร็ว
     */
    protected function isUserSpamming(string $senderId, string $text, array $attachments): bool
    {
        $silencedKey = "fortune:spam:silenced:{$senderId}";

        // ถ้า silenced อยู่ → ยังคง block จนกว่าจะหมดเวลา
        if (Cache::has($silencedKey)) {
            return true;
        }

        $now = time();
        $reason = null;

        // 🚨 Rule 1: RATE FLOOD — > 10 messages ใน 30s = ตั้งใจ flood
        $rateKey = "fortune:spam:rate:{$senderId}";
        $rateLog = Cache::get($rateKey, []);
        $rateLog = array_values(array_filter($rateLog, fn ($t) => ($now - $t) < 30));
        $rateLog[] = $now;
        Cache::put($rateKey, $rateLog, 60);
        if (count($rateLog) > 10) {
            $reason = 'rate_flood (>10 msg/30s)';
        }

        // 🚨 Rule 2: REPEAT FLOOD — ส่งข้อความเดียวกัน ≥ 3 ครั้งติด ใน 30s
        // 🆎 (2026-05-06) ยกเว้น state-expected inputs (กัน false-positive คนแก่กดซ้ำ)
        //   เช่น "พร้อม" "ใช่" "ไม่ใช่" — ลูกค้าอาจกดซ้ำเพราะคิดว่าบอทไม่ตอบ
        $stateExpectedInputs = [
            'พร้อม', 'ใช่', 'ไม่ใช่', 'ใช่เลย', 'ไม่', 'ตกลง', 'ok', 'OK',
            'ดูดวง', 'เริ่มถามคำถาม', 'พอแค่นี้', 'พอ', 'หยุด',
            'อ่านคำทำนาย', 'รับคำทำนาย', 'ยกเลิก',
            'ດວງ', 'ແມ່ນ', 'ບໍ່', 'ພ້ອມ', // Lao
        ];
        $normalizedText = trim($text);
        $isStateInput = in_array($normalizedText, $stateExpectedInputs, true)
            || mb_strlen($normalizedText) <= 4; // ข้อความสั้นมาก ≤ 4 chars = น่าจะเป็น state input

        if ($reason === null && ! empty($normalizedText) && mb_strlen($text) > 1 && ! $isStateInput) {
            $repeatKey = "fortune:spam:repeat:{$senderId}";
            $cached = Cache::get($repeatKey, ['text' => '', 't' => 0, 'count' => 0]);

            if ($cached['text'] === $text && ($now - $cached['t']) < 30) {
                $newCount = $cached['count'] + 1;
                Cache::put($repeatKey, [
                    'text' => $text, 't' => $now, 'count' => $newCount,
                ], 60);
                if ($newCount >= 3) {
                    $reason = 'repeat_flood (3x same text/30s)';
                }
            } else {
                Cache::put($repeatKey, [
                    'text' => $text, 't' => $now, 'count' => 1,
                ], 60);
            }
        }

        // 🚨 Rule 3: SPAM URL — link ภายนอก (ไม่ใช่ domain ของเรา)
        if ($reason === null && ! empty($text)) {
            // ใช้ negative lookahead — ละเว้น domain ของเรา
            if (preg_match('#https?://(?!(www\.)?(main\.)?thaiprompt\.online)|t\.me/|bit\.ly/|tinyurl\.com#i', $text)) {
                $reason = 'spam_url (external link)';
            }
        }

        // ไม่ trigger rule ใด → แชทปกติ ไม่ block
        if ($reason === null) {
            return false;
        }

        // Silence 5 นาที (ลดจาก 1 ชม. — ลูกค้าปกติ recovery ได้)
        Cache::put($silencedKey, true, now()->addMinutes(5));
        Log::warning('🚫 Fortune spam guard: silenced 5 min', [
            'sender_id' => $senderId,
            'reason' => $reason,
            'text_preview' => mb_substr($text, 0, 80),
            'rate_count' => count($rateLog),
        ]);

        return true;
    }
}
