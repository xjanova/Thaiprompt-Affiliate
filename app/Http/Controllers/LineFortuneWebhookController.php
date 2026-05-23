<?php

namespace App\Http\Controllers;

use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmProspect;
use App\Services\FortuneBannerService;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneTakeoverService;
use App\Services\LineFortuneService;
use App\Services\LineGatekeeperService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * LINE Fortune Webhook Controller
 *
 * รับ webhook events จาก LINE Official Account สำหรับระบบดูดวง
 *
 * Webhook URL: /webhook/line/fortune
 */
class LineFortuneWebhookController extends Controller
{
    protected FortuneTellingSetting $settings;

    protected LineFortuneService $lineService;

    protected FortuneChannelManager $channelManager;

    /**
     * FortuneTakeoverService — ระบบเทคโอเวอร์ (ใช้ร่วมกับ Facebook)
     */
    protected FortuneTakeoverService $takeoverService;

    /**
     * FortuneBanService — ระบบ "คุก" ห้ามบอทคุยกับ user ที่ไม่เหมาะสม
     */
    protected FortuneBanService $banService;

    /**
     * FortuneBannerService — ส่งภาพแบนเนอร์ก่อนข้อความ
     */
    protected FortuneBannerService $bannerService;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->lineService = new LineFortuneService($this->settings);
        $this->channelManager = new FortuneChannelManager($this->settings);
        $this->takeoverService = app(FortuneTakeoverService::class);
        $this->banService = app(FortuneBanService::class);
        $this->bannerService = new FortuneBannerService($this->settings);
    }

    /**
     * Handle LINE Webhook
     */
    public function handle(Request $request): Response
    {
        // ตรวจสอบว่าเปิดใช้งาน LINE หรือไม่
        if (! $this->settings->line_enabled) {
            Log::warning('LINE Webhook: LINE is not enabled');

            return response('LINE is not enabled', 200);
        }

        // ตรวจสอบ signature
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if (! $this->lineService->verifySignature($body, $signature ?? '')) {
            Log::warning('LINE Webhook: Invalid signature');

            return response('Invalid signature', 400);
        }

        // Parse events
        $data = json_decode($body, true);
        $events = $data['events'] ?? [];

        // ✅ FIX: ส่ง 200 OK ให้ LINE ก่อน → ป้องกัน LINE retry จาก timeout
        // ใช้ fastcgi_finish_request() (PHP-FPM) หรือ ob_end_flush() (Apache mod_php)
        // เพื่อปิดการเชื่อมต่อ HTTP แล้วประมวลผลต่อในพื้นหลัง
        $response = response('OK', 200);

        // ส่ง HTTP response ก่อนประมวลผล events
        if (function_exists('fastcgi_finish_request')) {
            // PHP-FPM: ส่ง response ทันที → ประมวลผลต่อใน background
            $response->send();
            fastcgi_finish_request();

            // ✅ ประมวลผล events หลังส่ง response แล้ว (ลูกค้าไม่ต้องรอ)
            foreach ($events as $event) {
                $this->handleEvent($event);
            }

            // ส่ง response ว่างเพื่อให้ Laravel middleware ไม่ error
            return response('', 200);
        }

        // Non-FPM (Apache mod_php, CLI): ประมวลผลก่อน return ปกติ
        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return $response;
    }

    /**
     * Handle single event
     */
    protected function handleEvent(array $event): void
    {
        $eventType = $event['type'] ?? null;

        Log::info('LINE Webhook: Event received', [
            'type' => $eventType,
            'source' => $event['source'] ?? null,
        ]);

        match ($eventType) {
            'message' => $this->handleMessageEvent($event),
            'follow' => $this->handleFollowEvent($event),
            'unfollow' => $this->handleUnfollowEvent($event),
            'postback' => $this->handlePostbackEvent($event),
            default => Log::debug('LINE Webhook: Unhandled event type', ['type' => $eventType]),
        };
    }

    /**
     * Handle message event
     */
    protected function handleMessageEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $messageType = $event['message']['type'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            Log::warning('LINE Webhook: No userId in message event');

            return;
        }

        $messageText = $event['message']['text'] ?? '';
        $messageId = $event['message']['id'] ?? '';

        // 🚫 (2026-04-28) Spam guard — silence คนป่วน (parity กับ FB)
        // กัน: video/sticker/audio ซ้ำๆ, ข้อความมี URL, ข้อความซ้ำ
        if ($this->isUserSpamming($userId, $messageText, $messageType)) {
            Log::info('🚫 LINE Fortune: ignore spam message (silenced)', [
                'user_id' => $userId,
                'message_type' => $messageType,
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            return;
        }

        // 🚫 (2026-05-22) Ban guard — user ที่ถูกแบนห้ามบอทคุยด้วย
        //    Anti-spam: ตอบเตือนครั้งแรกเท่านั้น (cooldown 1 ชม.) หลังจากนั้นเงียบ
        //    แอดมินยังคุยได้ผ่าน LINE OA Manager / admin panel (เพราะใช้ Channel Token ตรง)
        //    ⚠️ LINE ส่งคำทำนายทันทีหลังจ่ายเงิน (ไม่ต้องรอ user ทักเหมือน FB)
        //    ดังนั้นการแบนระหว่างทาง = แบน user ที่ยังไม่จ่าย / จ่ายเสร็จแล้ว — safe
        $activeBan = $this->banService->getActiveBan('line', $userId);
        if ($activeBan !== null) {
            if ($this->banService->shouldNotify($activeBan)) {
                $banMessage = $this->banService->buildBanReplyMessage($activeBan);
                try {
                    if ($replyToken) {
                        $this->lineService->replyMessage($replyToken, [
                            ['type' => 'text', 'text' => $banMessage],
                        ]);
                    } else {
                        $this->lineService->sendMessage($userId, $banMessage);
                    }
                    $this->banService->recordNotification($activeBan);
                } catch (\Throwable $e) {
                    Log::warning('LINE ban: ส่งข้อความเตือนแบนล้มเหลว (non-blocking)', [
                        'user_id' => $userId,
                        'ban_id' => $activeBan->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('🚫 LINE: ignore banned user', [
                'user_id' => $userId,
                'ban_id' => $activeBan->id,
                'attempt_count' => $activeBan->attempt_count,
                'permanent' => $activeBan->isPermanent(),
            ]);

            return;
        }

        // 📸 รับรูปภาพ — ตรวจบริบท active reading แล้วตอบตามสถานะ
        //   📸 (2026-05-16) Celtic Pro Session (เปิดไพ่ครบ 10) + image → vision AI วิเคราะห์
        //   PENDING_PAYMENT → assume สลิป → ปลอบ + ขอกด "แจ้งชำระเงิน"
        //   PAID / processing → "แม่หมอกำลังคำนวณ"
        //   ไม่มี active → guidance generic
        if ($messageType === 'image') {
            // 🛡️ (2026-05-20 Patch 1+2) Celtic paid 99฿ bypass — ตรวจก่อนทุกอย่าง
            //   เหตุผล: ลูกค้าจ่าย 99฿ ส่งรูป → ต้องได้ vision ทันที ห้ามถูก spam block / classify ผิด
            //   • Patch 1: skip spam guard (paid → ไม่ใช่ spam แม้ส่งหลายรูป)
            //   • Patch 2: skip classifier (Celtic + picked=10 = ส่งให้ vision ตรง ไม่เสี่ยง classify ผิด)
            $celticPaidCheck = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                    FortuneReading::STATUS_CELTIC_GENERATING,
                ])
                ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->latest()
                ->first();

            if ($celticPaidCheck && $celticPaidCheck->getCelticPickedCount() >= 10) {
                Log::info('LINE: Celtic paid vision path → bypass spam+classifier', [
                    'user_id' => $userId,
                    'reading_id' => $celticPaidCheck->id,
                ]);

                // ส่ง vision ตรง — ไม่ผ่าน spam/classifier
                $this->handleCelticVisionImage($userId, $messageId, $celticPaidCheck, $replyToken);

                return;
            }

            // 🚫 (2026-05-20 Phase 3b.5) Image spam guard — ก่อน dispatch
            //   user spec 2026-05-20: "ถ้าส่งรูปรัวๆ จะถือว่าสแปม"
            //   ⚠️ Patch 1: Celtic paid bypassed แล้ว — มาถึงตรงนี้ = non-paid path
            try {
                $spamGuard = app(\App\Services\Fortune\ImageSpamGuard::class);
                $spamCheck = $spamGuard->check('line', $userId);
                if ($spamCheck['blocked']) {
                    Log::info('LINE: image spam cooldown active → silent', [
                        'user_id' => $userId,
                        'level' => $spamCheck['level'],
                    ]);

                    return;
                }
                $spamRecord = $spamGuard->record('line', $userId);
                if ($spamRecord['triggered']) {
                    Log::info('LINE: image spam triggered (silent)', [
                        'user_id' => $userId,
                        'level' => $spamRecord['level'],
                        'count' => $spamRecord['count'],
                    ]);

                    return;
                }
            } catch (\Throwable $spamErr) {
                Log::debug('LINE: spam guard exception (non-blocking)', [
                    'error' => $spamErr->getMessage(),
                ]);
            }

            // 🆕 (2026-05-20 Phase 3b.5) Classify image — ดึง LINE content แล้ว classify
            //   หมายเหตุ: LINE webhook ไม่ส่ง URL ตรง ๆ ต้องดึงผ่าน content API + base64 ก่อน
            //   ถ้า download fail → fall through ไป existing logic (Celtic vision หรือ slip handler ดึงเอง)
            $intent = null;
            $cachedBase64 = null; // เก็บไว้ส่งต่อให้ handleCelticVisionImage ได้
            if (! empty($messageId)) {
                try {
                    $cachedBase64 = $this->downloadLineImageAsBase64($messageId);
                    if ($cachedBase64) {
                        $hasActiveFortune = FortuneReading::where(function ($q) use ($userId) {
                            $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                        })
                            ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
                            ->exists();

                        $contextHint = $hasActiveFortune ? 'celtic_active' : 'chat_normal';
                        $intentResult = app(\App\Services\Fortune\ImageIntentClassifier::class)
                            ->classify($cachedBase64, $contextHint);
                        $intent = $intentResult['intent'] ?? null;

                        Log::info('LINE: image classified', [
                            'user_id' => $userId,
                            'intent' => $intent,
                            'confidence' => $intentResult['confidence'] ?? null,
                            'context_hint' => $contextHint,
                        ]);
                    }
                } catch (\Throwable $clErr) {
                    Log::debug('LINE: classifier exception (fall through)', [
                        'error' => $clErr->getMessage(),
                    ]);
                }
            }

            // 🤐 Emoji/nonsense → silent
            if (in_array($intent, [
                \App\Services\Fortune\ImageIntentClassifier::INTENT_EMOJI_STICKER,
                \App\Services\Fortune\ImageIntentClassifier::INTENT_NONSENSE,
            ], true)) {
                Log::debug('LINE: emoji/nonsense → silent', [
                    'user_id' => $userId,
                    'intent' => $intent,
                ]);

                return;
            }

            // 🃏 Celtic active + เปิดไพ่ครบ 10 → ส่งให้ vision AI
            //    🆕 (Phase 3b.5) Skip ถ้า classifier บอก payment_slip — ส่ง slip flow แทน
            if ($intent !== \App\Services\Fortune\ImageIntentClassifier::INTENT_PAYMENT_SLIP) {
                $celticVisionReading = FortuneReading::where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                    ->whereIn('conversation_status', [
                        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                        FortuneReading::STATUS_CELTIC_GENERATING,
                    ])
                    ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->latest()
                    ->first();

                if ($celticVisionReading && $celticVisionReading->getCelticPickedCount() >= 10) {
                    $this->handleCelticVisionImage($userId, $messageId, $celticVisionReading, $replyToken);

                    return;
                }
            }

            $this->handleSlipImageOnly($userId, $replyToken);

            return;
        }

        // รองรับเฉพาะ text message (sticker, video, audio, file → fallback)
        if ($messageType !== 'text') {
            // 🔮 (2026-05-04) Celtic active state — sticker/video ฯลฯ ต้องนำกลับเปิดไพ่ ไม่ใช่ generic
            $celticActive = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', FortuneReading::CELTIC_ACTIVE_STATUSES)
                ->latest()
                ->first();

            if ($celticActive) {
                $reason = $messageType === 'sticker' ? 'sticker' : 'generic';
                $resume = app(\App\Services\CelticCrossService::class)->buildResumeMessage($celticActive, $reason);
                $this->lineService->sendMessageWithReplyFallback($userId, $resume['message'], $replyToken);

                Log::info('LINE: Celtic active + non-text message → นำกลับเปิดไพ่/ถาม', [
                    'user_id' => $userId,
                    'reading_id' => $celticActive->id,
                    'celtic_status' => $celticActive->conversation_status,
                    'message_type' => $messageType,
                ]);

                return;
            }

            $this->lineService->replyMessage($replyToken, [
                [
                    'type' => 'text',
                    'text' => "🙏 ขอบคุณที่ทักมานะคะ\n\nทางเพจรับเฉพาะข้อความเท่านั้นค่ะ\n\nพิมพ์คำถามที่อยากให้ดูดวงมาได้เลยนะคะ 🔮✨",
                ],
            ]);

            return;
        }

        // ========================================
        // จับคู่ FortuneReferral จาก ref_{token} (แม่นยำ 100%)
        // ========================================
        if (preg_match('/^ref_([A-Za-z0-9]{32})$/i', trim($messageText), $matches)) {
            $this->handleReferralTokenMessage($userId, $matches[1], $replyToken);

            return;
        }

        // ========================================
        // 🛑 Admin Takeover: ถ้าแม่หมอ /aistop → บอทเงียบ (เฉพาะ chitchat)
        //
        // 🚨 (2026-05-17) FLOW BYPASS — ห้ามหยุด flow จ่ายเงิน/รับคำทำนาย
        // ========================================
        if ($this->takeoverService->isActiveByPlatform('line', $userId)) {
            if (! $this->takeoverService->shouldBypassTakeover('line', $userId, $messageText)) {
                Log::info('👨‍💼 LINE /aistop: บอทข้าม (chitchat ก่อนเข้า flow)', [
                    'user_id' => $userId,
                    'message_preview' => mb_substr($messageText, 0, 50),
                ]);

                return;
            }

            Log::info('💰 LINE /aistop active แต่ flow bypass — ดำเนิน flow ต่อ', [
                'user_id' => $userId,
            ]);
        }

        // ========================================
        // 🙋 ลูกค้าขอคุยกับคนจริง → เทคโอเวอร์ + แจ้ง
        // 🔒 (2026-05-20) skip ถ้าอยู่ระหว่างทำนาย (ห้ามแทรก — แอดมินจะ /aistop เองถ้าจำเป็น)
        // ========================================
        $inPredictionGuardActive = false;
        try {
            $inPredictionGuardActive = $this->conversationService->isInPrediction($userId);
        } catch (\Throwable $e) {
            \Log::debug('LINE: in-prediction guard check fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }

        if (! $inPredictionGuardActive && $this->takeoverService->detectCustomerHandoffRequest($messageText)) {
            $this->handleCustomerHandoffRequest($userId, $messageText, $replyToken);

            return;
        }

        try {
            // ✅ Gatekeeper: เช็คทราฟฟิคภาพรวมทั้งระบบก่อน (ทุก user รวมกัน)
            if (LineGatekeeperService::isSystemThrottled()) {
                Log::warning('LINE Webhook: System throttled — ส่งข้อความเตือน', [
                    'user_id' => $userId,
                    'stats' => LineGatekeeperService::getStats(),
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "⏳ ขณะนี้มีผู้ใช้งานจำนวนมากค่ะ\n\nกรุณารอสักครู่แล้วพิมพ์ข้อความมาใหม่นะคะ 🙏✨"],
                    ]);
                }

                return;
            }

            // ✅ Flood Protection: กันเฉพาะ spam bot ที่ส่งถี่มากผิดปกติ
            $floodKey = "line_flood:{$userId}";
            $floodCount = (int) cache()->get($floodKey, 0);
            cache()->put($floodKey, $floodCount + 1, 10); // นับข้อความใน 10 วินาที

            if ($floodCount >= 10) {
                // ⚡ ส่ง replyMessage ตรงๆ (ฟรี ไม่นับ push quota) — ไม่เรียก AI, ไม่เรียก LINE push
                Log::warning('LINE Webhook: Flood detected — ส่งข้อความเตือนซ้ำ', [
                    'user_id' => $userId,
                    'flood_count' => $floodCount,
                    'text' => mb_substr($messageText, 0, 30),
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "🌙 แม่หมอจันทรากำลังพิมพ์อยู่ค่ะ ✨\n\nกรุณารอสักครู่ — พิมพ์ทีละข้อความนะคะ 💫"],
                    ]);
                }

                return;
            }

            // ⚡ ดึง profile จาก cache 24hr (ไม่เรียก LINE API ถ้ามี cache แล้ว)
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) {
                Log::debug('LINE Webhook: ดึง profile ไม่ได้ ใช้ default', [
                    'user_id' => $userId,
                ]);
            }
            // ส่ง empty array ถ้า null → กัน FortuneChannelManager เรียก getUserProfile ซ้ำ
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            // 💰 Affiliate Signal: ถ้าข้อความบ่งบอกว่ามีปัญหาเงิน / อยากรายได้เสริม
            // (และไม่ใช่คำสั่งดูดวง) → ส่ง recruitment pitch แทน
            //
            // 🛑 (2026-05-16) Skip affiliate pitch ถ้าลูกค้าอยู่ใน reading/payment context
            //   user report screenshot: ลูกค้า Celtic 99฿ ถามคำถาม "หนี้สินจะได้รับการแก้ไข..."
            //   → bot ตอบ "รายได้เสริมง่ายๆ ชวนเพื่อนมาดูดวง" ❌ (ไม่ตอบคำถาม)
            //   root: keyword 'หนี้สิน' / 'รายได้' จับโดย isAffiliateSignalMessage
            //         → block processMessage → AI ไม่ได้เห็นคำถาม
            //
            //   Skip condition ครอบ:
            //   1. Active reading (Celtic picking/awaiting/etc + Deep collecting)
            //   2. Pending delivery (Deep 39฿ paid + completed + ยังไม่ส่งคำทำนาย)
            //      → ระหว่าง AI gen 30-60s + window ก่อนลูกค้ากด "อ่านคำทำนาย"
            //      → กัน hijack ระหว่างจ่ายแล้วรอผล
            //   FB ไม่มี bug นี้ — ไม่มี affiliate pitch ตรง webhook-level
            $hasActiveForAffiliate = \App\Models\FortuneReading::hasActiveReading('line', $userId);
            if (! $hasActiveForAffiliate) {
                // เช็คเพิ่ม: paid + awaiting delivery (status PAID หรือ COMPLETED แต่ยังไม่ส่งคำทำนาย)
                // 🩹 (2026-05-21 hotfix) ลบ orWhere('line_user_id') — fortune_readings ไม่มี column นี้
                //   SQL error: 'Unknown column line_user_id in WHERE' → webhook throw → flex error to customer
                //   ดู bc3415a9a192: LINE customer ใช้ platform_user_id (legacy facebook_user_id ด้วย)
                $hasPendingDelivery = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)
                        ->orWhere('facebook_user_id', $userId);
                })
                    ->where('is_paid', true)
                    ->whereIn('conversation_status', [
                        \App\Models\FortuneReading::STATUS_PAID,
                        \App\Models\FortuneReading::STATUS_COMPLETED,
                    ])
                    ->where('created_at', '>=', now()->subHours(2)) // window 2 ชม. กัน reading เก่า
                    ->exists();
                $hasActiveForAffiliate = $hasPendingDelivery;
            }
            if (! $hasActiveForAffiliate && $this->isAffiliateSignalMessage($messageText)) {
                Log::info('💰 LINE: Affiliate signal detected → pitch', [
                    'user_id' => $userId,
                    'message_snippet' => mb_substr($messageText, 0, 60),
                ]);
                $this->sendAffiliatePitchToLine($userId, $replyToken);

                return;
            }

            // 🖼️ ส่งแบนเนอร์ welcome (ครั้งเดียวต่อ user/24 ชม.)
            // ส่งก่อน processMessage เพื่อให้ภาพมาก่อนข้อความตอบ
            // 👤 (2026-05-14) ส่งเฉพาะลูกค้าใหม่ — ลูกค้าเก่าได้ text ตรง (ไม่ส่งรูป)
            //
            // 🚀 (2026-05-16) Skip banner ระหว่าง active reading + pending delivery
            //   เคส 1: ลูกค้าจ่าย Celtic แล้วพิมพ์ "พร้อม" → bot ต้องเปิดไพ่
            //   เคส 2: Deep 39฿ paid + AI gen → ลูกค้าพิมพ์ "อ่านคำทำนาย" → ต้องส่งทันที
            //   ก่อน fix: banner sendImage (push API ~500-800ms) block ก่อน processMessage
            //   ใหม่: reuse $hasActiveForAffiliate ที่เช็คทั้ง 2 condition แล้ว
            //   → critical path เร็วขึ้น 500-800ms
            if (! $hasActiveForAffiliate) {
                $this->bannerService->sendBannerOnce(
                    $userId,
                    fn ($url) => $this->lineService->sendImage($userId, $url),
                    'welcome',
                    24,
                    'line'
                );
            }

            // ประมวลผลข้อความผ่าน Channel Manager
            $result = $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $messageText,
                $userProfile,
                ['reply_token' => $replyToken]
            );

            Log::info('LINE Webhook: Message processed', [
                'user_id' => $userId,
                'action' => $result['action'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Error processing message', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile().':'.$e->getLine(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            // ส่งข้อความ error (ลอง reply ก่อน ถ้าไม่ได้ใช้ push)
            $errorMessage = 'ขออภัย เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏';
            $this->lineService->sendMessageWithReplyFallback($userId, $errorMessage, $replyToken);
        }
    }

    /**
     * จัดการเมื่อลูกค้าพิมพ์ขอคุยกับคนจริง (customer handoff request)
     *
     * 🎯 (2026-05-17) Alert-only mode — ไม่ takeover อัตโนมัติ
     *   user spec: ลูกค้าเลือก option B — "Alert only ให้ admin"
     *
     * Flow:
     * 1. หาหรือสร้าง reading + log การขอ (FortuneTakeoverLog action=message)
     * 2. แจ้งลูกค้าผ่าน replyToken (ฟรี) ว่าจะแจ้งแอดมิน
     * 3. ส่ง alert ให้ admin (LineAlertService)
     * 4. Admin ดูใน LINE OA Inbox แล้วจัดการเอง (ผ่าน admin panel /admin/takeover)
     */
    protected function handleCustomerHandoffRequest(
        string $userId,
        string $messageText,
        ?string $replyToken,
    ): void {
        // หา active reading ล่าสุดของ user นี้
        $reading = FortuneReading::where('platform', 'line')
            ->where('platform_user_id', $userId)
            ->latest()
            ->first();

        // ไม่มี reading เลย → สร้าง placeholder เพื่อให้ track ได้
        // ใช้ status=COMPLETED เพื่อไม่ให้ ConversationService picks up เป็น active conversation
        if (! $reading) {
            $reading = FortuneReading::create([
                'platform' => 'line',
                'platform_user_id' => $userId,
                'facebook_user_id' => $userId, // legacy column ต้องไม่ null
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
                'platform' => 'line',
                'message' => mb_substr('🙋 ลูกค้าขอคุยกับคน: '.$messageText, 0, 2000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LINE Customer Handoff: log ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }

        // 🌙 แจ้งลูกค้าผ่าน replyToken (ฟรี ไม่นับ push quota)
        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, [
                [
                    'type' => 'text',
                    'text' => "🌙 รอแอดมินสักครู่นะคะ\n\n"
                        ."แม่หมอจะแจ้งให้แอดมินมาตอบคุณค่ะ ✨\n"
                        ."ระหว่างรอ พิมพ์ถามแม่หมอต่อได้นะคะ 🔮",
                ],
            ]);
        }

        // 📢 ส่ง alert ให้ admin
        try {
            $alertService = app(\App\Services\LineAlertService::class);
            $alertService->alertUnusualActivity('🙋 ลูกค้า LINE ขอคุยกับคน', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'message' => mb_substr($messageText, 0, 200),
                'admin_panel' => url('/admin/takeover/'.$reading->id),
            ]);
        } catch (\Throwable $alertErr) {
            Log::warning('LINE Customer Handoff: ส่ง alert ไม่สำเร็จ (non-blocking)', [
                'error' => $alertErr->getMessage(),
            ]);
        }

        Log::info('🙋 LINE Customer Handoff: alert-only mode (ไม่ takeover)', [
            'user_id' => $userId,
            'reading_id' => $reading->id,
            'message_preview' => mb_substr($messageText, 0, 50),
        ]);
    }

    /**
     * Handle follow event (user add friend)
     *
     * ดึงชื่อจาก LINE Profile แล้วส่ง Welcome Flex Message พร้อมชื่อ
     */
    protected function handleFollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        // ดึงชื่อจาก LINE Profile
        $userName = '';
        try {
            $profile = $this->lineService->getUserProfile($userId);
            $userName = $profile['name'] ?? '';

            Log::info('LINE Webhook: New follower', [
                'user_id' => $userId,
                'display_name' => $userName,
            ]);
        } catch (\Exception $e) {
            Log::warning('LINE Webhook: ดึงโปรไฟล์ไม่ได้ ส่ง Welcome โดยไม่มีชื่อ', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        // ========================================
        // การจับคู่ referral ย้ายไป handleReferralTokenMessage() แล้ว
        // เพื่อนจะส่ง "ref_{token}" มาทาง message event → จับคู่ 100%
        // ========================================

        // 🎯 (2026-05-08 review L6) UX cleanup — banner image อย่างเดียว ไม่ส่ง welcome flex
        //   user feedback: "กล่องชักชวนมันเยอะไปตาลาย เอาแค่รูป อย่างเดียวก่อน"
        //   เดิม: welcome flex รวยภาพ + คำบรรยาย + 3 ปุ่ม
        //   ใหม่: ส่ง banner image ผ่าน FortuneBannerService (ถ้ามี toggle) ไม่ก็เงียบ
        //   ลูกค้าพิมพ์ทักมา → AI chat ตอบเป็นกันเอง
        try {
            $bannerService = app(\App\Services\FortuneBannerService::class);
            $sent = $bannerService->sendBannerOnce(
                $userId,
                fn ($url) => $this->lineService->sendImageMessage($userId, $url, $url),
                'welcome',
                24
            );

            // ถ้า banner ปิดอยู่หรือไม่มี — ส่ง greeting สั้น ๆ แทน (ไม่มี flex card)
            if (! $sent) {
                $greet = $userName ? "🌙 ยินดีต้อนรับ คุณ{$userName} ค่ะ" : '🌙 ยินดีต้อนรับค่ะ';
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => $greet],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('LINE Welcome: banner ส่งไม่ได้ — ใช้ text fallback', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $greet = $userName ? "🌙 ยินดีต้อนรับ คุณ{$userName} ค่ะ" : '🌙 ยินดีต้อนรับค่ะ';
            $this->lineService->replyMessage($replyToken, [
                ['type' => 'text', 'text' => $greet],
            ]);
        }
    }

    /**
     * จับคู่ referral จาก token ที่ฝังในข้อความ (แม่นยำ 100%)
     *
     * เมื่อเพื่อนกด LINE deep link → ข้อความ "ref_{token}" ถูก pre-fill
     * เพื่อนกดส่ง → method นี้จับคู่ token กับ FortuneReferral ได้ทันที
     */
    protected function handleReferralTokenMessage(string $lineUserId, string $token, ?string $replyToken): void
    {
        try {
            // 1. หา FortuneReferral จาก token
            $referral = FortuneReferral::where('referral_token', $token)
                ->where('status', FortuneReferral::STATUS_PENDING)
                ->where('expires_at', '>', now())
                ->first();

            if (! $referral) {
                // token หมดอายุหรือไม่ถูกต้อง
                Log::info('LINE Webhook: ref_ token ไม่ถูกต้องหรือหมดอายุ', [
                    'token' => $token,
                    'line_user_id' => $lineUserId,
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "⏰ ลิงก์เชิญนี้หมดอายุแล้วค่ะ\n\nกรุณาขอลิงก์ใหม่จากผู้เชิญนะคะ\n\nหรือพิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยค่ะ 🔮"],
                    ]);
                }

                return;
            }

            // 2. ตรวจว่า LINE user นี้ถูกจับคู่กับ referral อื่นอยู่แล้วหรือไม่
            $existingReferral = FortuneReferral::where('referred_line_user_id', $lineUserId)
                ->whereIn('status', [FortuneReferral::STATUS_FOLLOWED, FortuneReferral::STATUS_CONVERTED])
                ->first();

            if ($existingReferral) {
                Log::info('LINE Webhook: LINE user มี referral อยู่แล้ว', [
                    'existing_referral_id' => $existingReferral->id,
                    'line_user_id' => $lineUserId,
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "🔮 คุณเข้าระบบแล้วค่ะ\n\nพิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยนะคะ ✨"],
                    ]);
                }

                return;
            }

            // 3. ดึง LINE profile
            $userName = '';
            try {
                $profile = $this->lineService->getUserProfile($lineUserId);
                $userName = $profile['name'] ?? '';
            } catch (\Exception $e) {
                // ไม่กระทบ flow หลัก
            }

            // 4. Mark referral as "followed" + บันทึก LINE user ID
            $referral->markAsFollowed($lineUserId);

            // 5. อัพเดท MlmProspect ที่เชื่อมกัน
            if ($referral->mlm_prospect_id) {
                $prospect = MlmProspect::find($referral->mlm_prospect_id);
                if ($prospect) {
                    $prospect->update([
                        'line_user_id' => $lineUserId,
                        'line_display_name' => $userName ?: null,
                        'line_picture_url' => $profile['pictureUrl'] ?? null,
                        'clicked_at' => now(),
                        'status' => 'in_progress',
                        'is_locked' => true,
                        'locked_until' => now()->addHours(24),
                    ]);
                }
            }

            Log::info('LINE Webhook: จับคู่ referral สำเร็จ 100% ผ่าน deep link token', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
                'new_follower_line_id' => $lineUserId,
                'display_name' => $userName,
            ]);

            // 6. ส่ง Welcome + แนะนำให้ดูดวง
            $referrerName = $referral->referrerUser?->name ?? 'เพื่อน';

            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => '🎉 ยินดีต้อนรับค่ะ'.($userName ? " คุณ{$userName}" : '')."\n\nคุณ{$referrerName} เชิญคุณมาดูดวงค่ะ\n\n🔮 พิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยนะคะ ✨"],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('LINE Webhook: handleReferralTokenMessage ล้มเหลว', [
                'token' => $token,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);

            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => '🔮 พิมพ์ "ดูดวง" เพื่อเริ่มดูดวงได้เลยค่ะ ✨'],
                ]);
            }
        }
    }

    /**
     * Handle unfollow event (user block/remove friend)
     */
    protected function handleUnfollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;

        if (! $userId) {
            return;
        }

        Log::info('LINE Webhook: User unfollowed (blocked bot)', ['user_id' => $userId]);

        try {
            // ปิด conversation ที่ค้างอยู่ทั้งหมด (ยกเว้น PAID — อาจกำลัง processing)
            $closedConversations = FortuneReading::where('facebook_user_id', $userId)
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_AWAITING_CONFIRMATION,
                    FortuneReading::STATUS_BASIC_DONE,
                    FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                    FortuneReading::STATUS_COLLECTING_QUESTIONS,
                    FortuneReading::STATUS_COLLECTING_TAROT,
                    FortuneReading::STATUS_NEW,
                ])
                ->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            // ยกเลิก pending payment bills + คืน UniquePaymentAmount
            $pendingReadings = FortuneReading::where('facebook_user_id', $userId)
                ->whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->with('uniquePaymentAmount')
                ->get();

            foreach ($pendingReadings as $reading) {
                if ($reading->uniquePaymentAmount && $reading->uniquePaymentAmount->status === 'reserved') {
                    $reading->uniquePaymentAmount->cancel();
                }
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }

            if ($closedConversations > 0 || $pendingReadings->count() > 0) {
                Log::info('LINE Webhook: Unfollow cleanup สำเร็จ', [
                    'user_id' => $userId,
                    'closed_conversations' => $closedConversations,
                    'cancelled_bills' => $pendingReadings->count(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('LINE Webhook: Unfollow cleanup ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle postback event (button clicks)
     */
    protected function handlePostbackEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $data = $event['postback']['data'] ?? '';
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        // 🛑 Admin Takeover: ถ้าแม่หมอ /aistop → ข้าม postback
        //
        // 🚨 (2026-05-17) FLOW BYPASS — postback คือ explicit action (กดปุ่ม)
        //   LINE Flex/Quick Reply button → bypass เสมอ (เปิดไพ่ Celtic, เลือกแพคเกจ, ฯลฯ)
        if ($this->takeoverService->isActiveByPlatform('line', $userId)) {
            if (! $this->takeoverService->shouldBypassTakeover('line', $userId, '', true)) {
                Log::info('👨‍💼 LINE /aistop: บอทข้าม postback', [
                    'user_id' => $userId,
                    'data' => mb_substr($data, 0, 50),
                ]);

                return;
            }

            Log::info('💰 LINE /aistop active แต่ postback bypass — ดำเนิน flow ต่อ', [
                'user_id' => $userId,
                'data' => mb_substr($data, 0, 50),
            ]);
        }

        Log::info('LINE Webhook: Postback received', [
            'user_id' => $userId,
            'data' => $data,
        ]);

        // Parse postback data
        parse_str($data, $params);
        $action = $params['action'] ?? '';

        match ($action) {
            'deep_reading' => $this->handleDeepReadingPostback($userId, $replyToken),
            'cancel' => $this->handleCancelPostback($userId, $replyToken),
            'view_last_reading' => $this->handleSimulateTextPostback($userId, $replyToken, 'ดูคำทำนาย'),
            'check_remaining' => $this->handleSimulateTextPostback($userId, $replyToken, 'เช็คสิทธิ์'),
            'check_status' => $this->handleCheckStatusPostback($userId, $replyToken),
            'report_payment' => $this->handleReportPaymentPostback($userId, $replyToken),
            'help' => $this->handleHelpPostback($userId, $replyToken),
            'menu' => $this->handleSimulateTextPostback($userId, $replyToken, 'เมนู'),
            'confirm_transfer' => $this->handleConfirmTransferPostback($userId, $replyToken, $params),
            // 💰 Affiliate Recruitment (parity กับ FB comment engagement)
            'affiliate_pitch' => $this->handleAffiliatePitchPostback($userId, $replyToken),
            'affiliate_yes' => $this->handleAffiliateYesPostback($userId, $replyToken),
            'affiliate_no' => $this->handleAffiliateNoPostback($userId, $replyToken),
            default => Log::warning('LINE Webhook: Unknown postback action', [
                'user_id' => $userId,
                'action' => $action,
                'data' => $data,
            ]),
        };
    }

    /**
     * ตรวจสอบว่าข้อความ LINE มี money-signal ที่ควร pitch affiliate หรือไม่
     *
     * ใช้ keyword ที่เข้มกว่า FB เพื่อลดการ hijack conversation
     * → ต้องมี "negative money context" + ไม่มี "fortune intent"
     */
    protected function isAffiliateSignalMessage(string $message): bool
    {
        $message = mb_strtolower(trim($message));
        if ($message === '') {
            return false;
        }

        // ถ้ามีคำสื่อถึงการดูดวง → ไม่ใช่ signal (user ต้องการดูดวง)
        $fortuneIntent = ['ดูดวง', 'ทำนาย', 'ดวง', 'หมอดู', 'ยิปซี', 'ไพ่', 'ลายมือ'];
        foreach ($fortuneIntent as $kw) {
            if (mb_stripos($message, $kw) !== false) {
                return false;
            }
        }

        // คำที่สื่อถึงปัญหาเงินหรือต้องการรายได้เสริม (เข้มกว่า FB)
        $signals = [
            'ไม่มีเงิน', 'เงินไม่พอ', 'เงินขาด', 'หารายได้', 'งานเสริม',
            'อยากรวย', 'เป็นหนี้', 'หนี้สิน', 'ขาดทุน', 'อยากได้เงิน',
            'ว่างงาน', 'ตกงาน', 'รายได้เสริม', 'งานออนไลน์',
        ];
        foreach ($signals as $kw) {
            if (mb_stripos($message, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ส่ง affiliate recruitment pitch + 2 ปุ่มอยาก/ไม่อยาก (LINE)
     */
    protected function handleAffiliatePitchPostback(string $userId, ?string $replyToken): void
    {
        $this->sendAffiliatePitchToLine($userId, $replyToken);
    }

    /**
     * ผู้ใช้กด "✅ อยาก" → ส่ง Flex detail + ปุ่มเริ่มดูดวง (parity กับ FB)
     */
    protected function handleAffiliateYesPostback(string $userId, ?string $replyToken): void
    {
        // ใช้ราคา dynamic จาก LineFortuneService::getDeepReadingPrice()
        $deepPrice = number_format($this->lineService->getDeepReadingPrice(), 0);

        $flex = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => '🎉 ยินดีค่ะ!', 'weight' => 'bold', 'size' => 'xl', 'color' => '#6b46c1'],
                    ['type' => 'separator', 'margin' => 'md'],
                    ['type' => 'text', 'text' => '📋 วิธีเริ่ม 3 ขั้น', 'weight' => 'bold', 'margin' => 'md', 'size' => 'md'],
                    [
                        'type' => 'text',
                        'text' => "1️⃣ ดูดวง {$deepPrice} บาท/ครั้ง\n2️⃣ ได้เป็นสมาชิกอัตโนมัติ\n3️⃣ รับลิงก์แชร์ส่วนตัว",
                        'wrap' => true,
                        'size' => 'sm',
                        'margin' => 'sm',
                    ],
                    ['type' => 'separator', 'margin' => 'md'],
                    ['type' => 'text', 'text' => '💰 รายได้', 'weight' => 'bold', 'margin' => 'md', 'size' => 'md'],
                    [
                        'type' => 'text',
                        'text' => "• ชวนคนมาดูดวง → 10 บาท/คน (Level 1)\n• เพื่อนชวนต่อ → ส่วนแบ่ง Level 2",
                        'wrap' => true,
                        'size' => 'sm',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6b46c1',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => "💎 เริ่มดูดวง {$deepPrice} บาท", 'text' => 'ดูดวง'],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => ['type' => 'message', 'label' => '🔮 ดูดวงก่อน', 'text' => 'ดูดวง'],
                    ],
                ],
            ],
        ];

        $messages = [[
            'type' => 'flex',
            'altText' => "🎉 วิธีเริ่มสร้างรายได้ — ดูดวง {$deepPrice} บาท",
            'contents' => $flex,
        ]];

        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, $messages);
        } else {
            // fallback เป็น text เมื่อ reply token หมดอายุ
            $fallback = "🎉 วิธีเริ่มง่ายๆ 3 ขั้น\n\n1️⃣ ดูดวง {$deepPrice} บาท\n2️⃣ ได้เป็นสมาชิก\n3️⃣ แชร์ลิงก์ได้เลย\n\n💰 ชวนคน → 10 บาท/คน\n\nพิมพ์ \"ดูดวง\" เพื่อเริ่มค่ะ";
            $this->lineService->sendMessageWithReplyFallback($userId, $fallback, null);
        }
        Log::info('💰 LINE Affiliate YES clicked', ['user_id' => $userId]);
    }

    /**
     * ผู้ใช้กด "❌ ไม่อยาก" → ชวนดูดวงปกติ
     */
    protected function handleAffiliateNoPostback(string $userId, ?string $replyToken): void
    {
        $message = "ไม่เป็นไรค่ะ 😊\n\n"
            ."ถ้าสนใจให้แม่หมอทำนายดวง\n"
            .'พิมพ์ "ดูดวง" มาได้เลยนะคะ 🔮';

        $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        Log::info('💰 LINE Affiliate NO clicked', ['user_id' => $userId]);
    }

    /**
     * ส่ง Flex Message recruitment pitch พร้อม 2 ปุ่ม (postback)
     */
    protected function sendAffiliatePitchToLine(string $userId, ?string $replyToken): void
    {
        $flex = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => '💰 รายได้เสริมง่ายๆ', 'weight' => 'bold', 'size' => 'xl', 'color' => '#6b46c1'],
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'text',
                        'text' => "แม่หมอมีทางให้ค่ะ\n\nชวนเพื่อนมาดูดวง\nได้ค่าชวน 10 บาท/คน\n\n✨ ไม่ต้องลงทุน\n📌 ชวนได้ไม่จำกัด",
                        'wrap' => true,
                        'size' => 'sm',
                        'margin' => 'md',
                    ],
                    ['type' => 'text', 'text' => 'สนใจไหมคะ?', 'weight' => 'bold', 'margin' => 'lg', 'align' => 'center'],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6b46c1',
                        'height' => 'sm',
                        'action' => ['type' => 'postback', 'label' => '✅ อยาก', 'data' => 'action=affiliate_yes'],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => ['type' => 'postback', 'label' => '❌ ไม่อยาก', 'data' => 'action=affiliate_no'],
                    ],
                ],
            ],
        ];

        $messages = [[
            'type' => 'flex',
            'altText' => '💰 รายได้เสริม — ชวนเพื่อนได้ 10 บาท/คน',
            'contents' => $flex,
        ]];

        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, $messages);
        } else {
            // fallback: ส่งเป็น text (ไม่ใช่ flex) ถ้าไม่มี reply token
            $fallbackText = "💰 รายได้เสริม\n\nชวนเพื่อนมาดูดวงกับแม่หมอ ได้ค่าชวน 10 บาท/คน\n\nพิมพ์ \"อยาก\" ถ้าสนใจ หรือ \"ไม่อยาก\" เพื่อข้ามค่ะ";
            $this->lineService->sendMessageWithReplyFallback($userId, $fallbackText, null);
        }

        Log::info('💰 LINE Affiliate pitch sent', ['user_id' => $userId]);
    }

    /**
     * Handle deep reading postback
     */
    protected function handleDeepReadingPostback(string $userId, ?string $replyToken): void
    {
        try {
            // ✅ FIX: ดึง profile จาก cache ก่อน → ไม่ต้องให้ ChannelManager เรียก LINE API ซ้ำ
            // ป้องกัน delay 3-8 วินาทีจากการเรียก getUserProfile ใน ChannelManager
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) {
                // ไม่เป็นไร — ChannelManager จะ fallback ดึงเอง
            }
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                'ดูดวง',
                $userProfile,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback deep_reading ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * Handle cancel postback
     */
    protected function handleCancelPostback(string $userId, ?string $replyToken): void
    {
        try {
            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                'ยกเลิก',
                null,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback cancel ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * จำลองส่งข้อความ text ผ่าน channelManager (ใช้กับ postback ที่ trigger เหมือนพิมพ์ข้อความ)
     */
    protected function handleSimulateTextPostback(string $userId, ?string $replyToken, string $text): void
    {
        try {
            // ✅ ดึง profile จาก cache → ป้องกัน delay จาก getUserProfile ใน ChannelManager
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) { /* ignore */
            }
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $text,
                $userProfile,
                [
                    'reply_token' => $replyToken,
                    'is_explicit_action' => true, // 🎯 postback = explicit (bypass takeover)
                ]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback simulate text ล้มเหลว', [
                'user_id' => $userId,
                'text' => $text,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แสดงสถานะ/สิทธิ์ (จากปุ่ม Rich Menu)
     *
     * ดึงข้อมูลสิทธิ์ฟรี, เครดิตพิเศษ, สถานะสมาชิก แล้วส่ง Flex
     */
    protected function handleCheckStatusPostback(string $userId, ?string $replyToken): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $maxFreeReadings = $settings->max_free_readings ?? 3;
            $usedToday = FortuneReading::countTodayReadings($userId);

            // ดึงข้อมูลเครดิตพิเศษ
            $userCredit = \App\Models\FortuneUserCredit::findByUser($userId);
            $specialCredits = 0;
            $isUnlimited = false;

            $normalRemaining = max(0, $maxFreeReadings - $usedToday);
            if ($userCredit) {
                if ($userCredit->isCurrentlyUnlimited()) {
                    $isUnlimited = true;
                    $normalRemaining = 99;
                } elseif ($userCredit->isDailyResetActive()) {
                    $normalRemaining = max($normalRemaining, $maxFreeReadings);
                } else {
                    $extraCredits = $userCredit->getRemainingCredits();
                    $specialCredits = $extraCredits;
                    $normalRemaining += $extraCredits;
                }
            }

            // ✅ ดึงชื่อผู้ใช้: ลำดับ 1) LINE Profile API, 2) DB (reading ล่าสุด), 3) fallback 'คุณ'
            $userName = 'คุณ';
            $profile = $this->lineService->getUserProfile($userId);
            if ($profile && ! empty($profile['name'])) {
                $userName = $profile['name'];
            } else {
                // ถ้า LINE API ไม่ได้ชื่อ → ดึงจาก reading ล่าสุด
                $latestReading = FortuneReading::where('facebook_user_id', $userId)
                    ->whereNotNull('facebook_user_name')
                    ->latest()
                    ->value('facebook_user_name');
                if ($latestReading) {
                    $userName = $latestReading;
                }
            }

            // ✅ ดึงข้อมูล wallet + รายได้ค่าคอม
            $walletBalance = 0;
            $totalCommission = 0;
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if ($user) {
                $walletBalance = $user->wallet?->balance ?? 0;
                // ดึงยอดรายได้ค่าคอมรวม (จาก wallet transactions ประเภท commission)
                $totalCommission = $user->wallet
                    ? \App\Models\WalletTransaction::where('wallet_id', $user->wallet->id)
                        ->where('type', 'credit')
                        ->where('description', 'LIKE', '%คอมมิชชั่น%')
                        ->sum('amount')
                    : 0;
            }

            // สร้าง result ในรูปแบบที่ FortuneChannelManager ต้องการ
            // เมื่อปิดระบบฟรี ($maxFreeReadings = 0) → ไม่พูดถึงสิทธิ์ฟรี
            $statusMessage = $maxFreeReadings > 0
                ? "✅ สถานะ: สิทธิ์ฟรี {$normalRemaining} ครั้ง"
                : '✅ สถานะบัญชี';
            $result = [
                'action' => 'check_status',
                'message' => $statusMessage,
                'reading' => null,
                'user_name' => $userName,
                'remaining' => $normalRemaining,
                'used' => $usedToday,
                'total' => $maxFreeReadings,
                'special_credits' => $specialCredits,
                'is_unlimited' => $isUnlimited,
                'member_status' => null,
                'wallet_balance' => $walletBalance,
                'total_commission' => $totalCommission,
            ];

            // ส่งผ่าน FortuneChannelManager เพื่อใช้ Flex Message
            $this->channelManager->sendResponse(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $result,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback check_status ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ ไม่สามารถดึงข้อมูลสถานะได้ กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แจ้งปัญหาการโอนเงิน — แสดงคำเตือน + ข้อมูลบิลที่รอชำระ
     */
    protected function handleReportPaymentPostback(string $userId, ?string $replyToken): void
    {
        try {
            // หาบิลที่รอชำระ
            $pendingReading = FortuneReading::where('facebook_user_id', $userId)
                ->whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->with('uniquePaymentAmount')
                ->latest()
                ->first();

            $uniqueAmount = null;
            $expiresAt = null;

            if ($pendingReading && $pendingReading->uniquePaymentAmount) {
                $uniqueAmount = (float) $pendingReading->uniquePaymentAmount->amount;
                $expiresAtCarbon = $pendingReading->uniquePaymentAmount->expires_at ?? null;
                $expiresAt = $expiresAtCarbon ? $expiresAtCarbon->format('H:i') : null;
            }

            $flex = $this->lineService->buildPaymentProblemFlexMessage(
                $pendingReading,
                $uniqueAmount,
                $expiresAt
            );

            $this->lineService->sendFlexWithReplyFallback(
                $userId, $flex, '⚠️ แจ้งปัญหาการโอนเงิน', $replyToken
            );

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback report_payment ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แสดงวิธีใช้งาน (help)
     */
    protected function handleHelpPostback(string $userId, ?string $replyToken): void
    {
        try {
            $flex = $this->lineService->buildHelpFlexMessage();
            $brandName = $this->settings->getFortuneBrandName();

            $this->lineService->sendFlexWithReplyFallback(
                $userId, $flex, "ℹ️ วิธีใช้งาน{$brandName}", $replyToken
            );

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback help ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * ยืนยันว่าโอนเงินแล้ว — บันทึก transfer_reported + แจ้ง admin
     */
    protected function handleConfirmTransferPostback(string $userId, ?string $replyToken, array $params): void
    {
        try {
            $readingId = $params['reading_id'] ?? null;

            // หา reading ที่ตรงกับ ID + user
            $reading = null;
            if ($readingId) {
                $reading = FortuneReading::where('id', $readingId)
                    ->where('facebook_user_id', $userId)
                    ->first();
            }

            // ถ้าไม่มี reading_id → หาจาก pending ล่าสุด
            if (! $reading) {
                $reading = FortuneReading::where('facebook_user_id', $userId)
                    ->whereIn('conversation_status', FortuneReading::PENDING_PAYMENT_STATUSES)
                    ->where('is_paid', false)
                    ->latest()
                    ->first();
            }

            if (! $reading) {
                $this->lineService->sendMessageWithReplyFallback(
                    $userId, 'ไม่พบบิลที่รอชำระค่ะ กรุณาเริ่มดูดวงใหม่ 🔮', $replyToken
                );

                return;
            }

            // บันทึกว่าผู้ใช้แจ้งว่าโอนแล้ว
            $reading->update([
                'transfer_reported' => true,
                'transfer_reported_at' => now(),
            ]);

            Log::info('LINE Webhook: ผู้ใช้แจ้งว่าโอนเงินแล้ว', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'bill_ref' => $reading->bill_reference,
            ]);

            // ตอบผู้ใช้ทันที — แจ้งว่ากำลังสร้างคำทำนาย
            $billRef = $reading->bill_reference ?? $reading->id;
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                "✅ รับแจ้งแล้วค่ะ!\n\n🔮 กำลังสร้างคำทำนายให้ค่ะ...\n⏳ แป๊บเดียวเสร็จค่ะ จะแจ้งทันทีเลยนะคะ ✨",
                $replyToken
            );

            // ✅ FIX: Auto-process payment + dispatch deep reading job
            // เมื่อผู้ใช้กด "โอนเงินแล้ว" → เริ่มสร้างคำทำนายทันที
            // (ก่อนหน้านี้แค่บันทึก transfer_reported แต่ไม่ dispatch job)
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $autoProcessOnReport = $settings->auto_process_on_transfer_report ?? true;

            if ($autoProcessOnReport && ! $reading->is_paid) {
                $reading->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'conversation_status' => \App\Models\FortuneReading::STATUS_PAID,
                ]);

                Log::info('LINE Webhook: Auto-process payment on transfer report', [
                    'reading_id' => $reading->id,
                    'bill_ref' => $billRef,
                ]);

                // Dispatch background job สร้างคำทำนาย
                \App\Jobs\ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id,
                    null,
                    'line',
                    $userId
                );
            }

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback confirm_transfer ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * 📸 จัดการเมื่อลูกค้า LINE ส่งรูปภาพ (มักเป็นสลิปการโอน)
     *
     * ตรวจ active reading → ตอบตามบริบท เหมือน FacebookWebhookController::handleSlipImageOnly:
     *   - PENDING_PAYMENT → ปลอบ + แนะให้กด "แจ้งชำระเงิน" / พิมพ์ "โอนแล้ว"
     *   - PAID / processing → "แม่หมอกำลังคำนวณดวงดาว"
     *   - ไม่มี active → guidance ว่าระบบใช้ SMS Banking auto-check
     *
     * เคยเป็น generic "รับเฉพาะข้อความ" → ลูกค้าที่ส่งสลิปกังวลว่าระบบไม่รับ
     */
    /**
     * 📸 (2026-05-16) Celtic Pro Session vision flow — รับรูป + ส่งให้ Vision AI
     *
     * Flow:
     *   1. Download content จาก LINE API (binary)
     *   2. Convert เป็น base64 data URL
     *   3. Call CelticCrossService::askQuestionWithImage()
     *   4. Reply ผ่าน FortuneChannelManager (action celtic_question_answered)
     */
    /**
     * 🆕 (2026-05-20 Phase 3b.5) Download LINE image content → base64 data URL
     *
     * เหตุผล: ใช้ทั้ง classifier + Celtic vision — DRY + cache ภายใน request
     *
     * @return string|null  data:image/jpeg;base64,...  หรือ null ถ้า fail
     */
    protected function downloadLineImageAsBase64(string $messageId): ?string
    {
        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $accessToken = $settings->line_channel_access_token
                ?? config('services.line.channel_token');

            if (empty($accessToken)) {
                Log::warning('LINE downloadLineImageAsBase64: access token ไม่พบ');

                return null;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer '.$accessToken])
                ->get("https://api-data.line.me/v2/bot/message/{$messageId}/content");

            if (! $response->successful()) {
                Log::warning('LINE downloadLineImageAsBase64: download fail', [
                    'status' => $response->status(),
                    'message_id' => $messageId,
                ]);

                return null;
            }

            $bytes = $response->body();
            if (strlen($bytes) > 10 * 1024 * 1024) {
                Log::warning('LINE downloadLineImageAsBase64: image too large', [
                    'size_mb' => round(strlen($bytes) / 1024 / 1024, 2),
                ]);

                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/jpeg';
            $mime = trim(explode(';', $mime)[0]);

            if (! str_starts_with($mime, 'image/')) {
                Log::warning('LINE downloadLineImageAsBase64: non-image MIME', ['mime' => $mime]);

                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($bytes);
        } catch (\Throwable $e) {
            Log::warning('LINE downloadLineImageAsBase64 exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function handleCelticVisionImage(
        string $userId,
        string $messageId,
        \App\Models\FortuneReading $reading,
        ?string $replyToken
    ): void {
        if (empty($messageId)) {
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                "🌙 ขออภัยนะคะ — แม่หมอรับรูปไม่ได้\nเจ้าชะตาพิมพ์เล่าให้แม่หมอฟังได้ไหมคะ? 🙏",
                $replyToken
            );

            return;
        }

        try {
            // 1. Download content จาก LINE API
            //   Fortune Bot ใช้ token แยกจาก main bot — เก็บใน fortune_telling_settings.line_channel_access_token
            //   (อ้างอิง LineFortuneService:32 — pattern เดียวกัน)
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $accessToken = $settings->line_channel_access_token
                ?? config('services.line.channel_token');

            if (empty($accessToken)) {
                throw new \Exception('LINE Fortune access token ไม่พบ (ตั้งใน fortune_telling_settings)');
            }

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer '.$accessToken])
                ->get("https://api-data.line.me/v2/bot/message/{$messageId}/content");

            if (! $response->successful()) {
                throw new \Exception('Download LINE image fail: HTTP '.$response->status());
            }

            $imageBytes = $response->body();
            $mimeType = $response->header('Content-Type') ?: 'image/jpeg';

            // Size check — กัน DoS (max 10MB)
            if (strlen($imageBytes) > 10 * 1024 * 1024) {
                throw new \Exception('Image too large (> 10MB)');
            }

            $base64DataUrl = 'data:'.$mimeType.';base64,'.base64_encode($imageBytes);

            // 2. ส่ง pre-reply "กำลังพิมพ์" → ลูกค้ารู้ว่ารับรูปแล้ว
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                '🌙 แม่หมอจันทรากำลังดูภาพของเจ้าชะตา... ✨',
                $replyToken
            );

            // 3. ตั้ง state = GENERATING (กัน user spam)
            $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_GENERATING]);

            // 4. Call vision AI
            $service = app(\App\Services\CelticCrossService::class);
            $result = $service->askQuestionWithImage($reading, $base64DataUrl);

            // 5. กลับสู่ AWAITING_QUESTION
            $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            if (! $result['success']) {
                $this->lineService->sendMessage(
                    $userId,
                    $result['message'] ?? "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
                );

                return;
            }

            // 6. ส่งคำตอบ + footer (push เพราะ replyToken ใช้ไปแล้ว)
            $reading->refresh();
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $settingsObj = \App\Models\FortuneTellingSetting::getSettings();
            $qaWindow = (int) ($settingsObj->celtic_cross_qa_window_minutes ?? 15);
            $maxQ = (int) ($settingsObj->celtic_cross_max_questions ?? 5);
            $usedQ = (int) ($reading->celtic_questions_used ?? 0);
            $remainingQ = $maxQ > 0 ? max(0, $maxQ - $usedQ) : null;

            $timeHint = $remainingMin !== null
                ? "⏳ เหลือเวลา *{$remainingMin} นาที* (จาก {$qaWindow})"
                : "⏳ คุยได้ภายใน *{$qaWindow} นาที* นับจากคำทำนายแรก";
            if ($remainingQ !== null) {
                $timeHint .= "\n❓ เหลือถามได้อีก *{$remainingQ} คำถาม* (จาก {$maxQ})";
            }

            $message = $result['response']
                ."\n\n──────────────────────\n"
                .$timeHint."\n"
                .'💬 พิมพ์ต่อได้เลย — หรือกด *"📜 เลิกทำนายและสรุปผล"* เมื่อพร้อม ✨';

            $this->lineService->sendMessage($userId, $message);

            Log::info('LINE Celtic vision สำเร็จ', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'message_id' => $messageId,
                'image_bytes' => strlen($imageBytes),
            ]);
        } catch (\Throwable $e) {
            // Reset state กลับ
            try {
                $reading->update(['conversation_status' => \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
            } catch (\Throwable $stateErr) {
                // ignore
            }

            Log::error('LINE Celtic vision exception', [
                'user_id' => $userId,
                'reading_id' => $reading->id ?? null,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            $this->lineService->sendMessage(
                $userId,
                "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\nเจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏"
            );
        }
    }

    protected function handleSlipImageOnly(string $userId, ?string $replyToken): void
    {
        try {
            // หา reading ที่ user กำลังใช้งานอยู่ (LINE platform)
            //   🔮 (2026-05-04) ครอบคลุม Celtic active states ด้วย — กันลูกค้าส่งสลิประหว่างเปิดไพ่
            $activeReading = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', array_merge(
                    [
                        FortuneReading::STATUS_PENDING_PAYMENT,
                        FortuneReading::STATUS_PAID,
                    ],
                    FortuneReading::CELTIC_ACTIVE_STATUSES
                ))
                ->latest()
                ->first();

            // Fallback: ตรวจบิลที่เพิ่งจ่ายใน 30 นาที (กรณี conversation ปิดไปแล้วแต่ AI ยังประมวลผลอยู่)
            if (! $activeReading) {
                $recentReading = FortuneReading::where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)
                        ->orWhere('facebook_user_id', $userId);
                })
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
            if ($activeReading && in_array($activeReading->conversation_status, FortuneReading::CELTIC_ACTIVE_STATUSES, true)) {
                $resume = app(\App\Services\CelticCrossService::class)->buildResumeMessage($activeReading, 'image');
                $this->lineService->sendMessageWithReplyFallback($userId, $resume['message'], $replyToken);

                Log::info('LINE: รับรูประหว่าง Celtic active → นำกลับเปิดไพ่/ถาม', [
                    'user_id' => $userId,
                    'reading_id' => $activeReading->id,
                    'celtic_status' => $activeReading->conversation_status,
                    'picked_count' => $activeReading->getCelticPickedCount(),
                ]);

                return;
            }

            // 🟢 PENDING_PAYMENT — ลูกค้าส่งสลิป → ปลอบ + ขอกด "แจ้งชำระเงิน"
            if ($activeReading && in_array($activeReading->conversation_status, FortuneReading::PENDING_PAYMENT_STATUSES, true)) {
                $billRef = $activeReading->bill_reference ?? '-';
                $message = "🌙 ขอบคุณค่ะที่ส่งสลิปมาให้แม่หมอ\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                    ."💡 ระบบใช้ SMS Banking ตรวจสอบอัตโนมัติ — ไม่ต้องส่งสลิปให้แอดมินดูค่ะ\n\n"
                    ."🔔 *กรุณาพิมพ์ \"โอนแล้ว\" หรือ \"แจ้งชำระเงิน\"* เพื่อให้ระบบเช็คเร็วขึ้น\n"
                    ."ระบบจะตรวจสอบและตัดบิลภายใน 1-3 นาทีค่ะ ✨\n\n"
                    .'🪐 ระหว่างรอ ใจเย็นๆ นะคะ — ดาวเจ้าชนะของเจ้าชะตากำลังเรียงตัว';

                $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

                Log::info('LINE: รับสลิประหว่าง PENDING_PAYMENT → ปลอบ + ขอกด แจ้งชำระเงิน', [
                    'user_id' => $userId,
                    'reading_id' => $activeReading->id,
                    'bill_reference' => $billRef,
                ]);

                return;
            }

            // 🟢 PAID / processing — ระบบรับเงินแล้ว แม่หมอกำลังคำนวณ
            //   🔮 (2026-05-04) ยกเว้น Celtic — เพราะ Celtic จบแล้วก็ is_paid=true + empty(deep_response)
            //      Celtic ACTIVE state catch ด้านบนแล้ว, COMPLETED มี branch เฉพาะข้างล่าง
            if ($activeReading && $activeReading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS
                && ($activeReading->conversation_status === FortuneReading::STATUS_PAID
                    || ($activeReading->is_paid && empty($activeReading->deep_response)))) {
                $billRef = $activeReading->bill_reference ?? '-';
                $message = "✅ ระบบรับเงินไปเรียบร้อยแล้วค่ะ\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                    ."🌙 *แม่หมอกำลังคำนวณดวงดาวให้เจ้าชะตาอยู่*\n"
                    ."ใช้เวลาประมาณ 1-3 นาที — รอสักครู่ คำทำนายจะส่งไปให้ทันทีเมื่อเสร็จ ✨\n\n"
                    .'💡 ห้ามสร้างบิลใหม่นะคะ (ป้องกันจ่ายซ้ำ)';

                $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

                Log::info('LINE: รับสลิประหว่าง PAID → ปลอบ แม่หมอกำลังคำนวณ', [
                    'user_id' => $userId,
                    'reading_id' => $activeReading->id,
                ]);

                return;
            }

            // 🔮 (2026-05-04) Celtic COMPLETED — ลูกค้าส่งรูปหลัง Celtic จบ
            //   เคยตกเข้า PAID branch (เพราะ is_paid + empty deep_response) → "AI กำลังคำนวณ" ผิด
            //   ตอนนี้: ขอบคุณ + แนะให้พิมพ์ "ดูคำทำนายล่าสุด" เพื่อดู Q&A
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

                $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

                Log::info('LINE: รับรูปหลัง Celtic จบ → แนะให้พิมพ์ดูคำทำนายล่าสุด', [
                    'user_id' => $userId,
                    'reading_id' => $activeReading->id,
                ]);

                return;
            }

            // ⚪ ไม่มี active → guidance generic
            $genericMessage = "📸 ได้รับรูปภาพแล้วค่ะ\n\n"
                ."💡 ถ้าเป็นสลิปการโอน — ระบบใช้ SMS Banking ตรวจสอบอัตโนมัติ ไม่ต้องส่งสลิปให้แอดมินค่ะ\n\n"
                ."🔮 ถ้าต้องการเริ่มดูดวง พิมพ์ 'ดูดวง' หรือคำถามที่อยากรู้มาได้เลย ✨";

            $this->lineService->sendMessageWithReplyFallback($userId, $genericMessage, $replyToken);
        } catch (\Throwable $e) {
            Log::warning('LINE: handleSlipImageOnly ล้มเหลว fallback generic', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => "📸 ได้รับรูปภาพแล้วค่ะ\n\nพิมพ์คำถามที่อยากให้ดูดวงมาได้เลยนะคะ 🔮✨"],
                ]);
            }
        }
    }

    /**
     * 🚫 (2026-04-28) Anti-spam guard (LINE side, parity กับ FB)
     *
     * คืน true เมื่อควร silence (ไม่ตอบ — แต่ log)
     *
     * Strike rules (เก็บใน Cache 1 ชั่วโมง):
     *   1. Non-text + non-image (sticker/video/audio/file) ติด ๆ + ไม่มี active reading → +1 strike
     *   2. Text มี URL → +1 strike
     *   3. Text เหมือนเดิมกับ turn ก่อน → +1 strike
     *
     * เมื่อ strike >= 5 ภายใน 1 ชม. → silence (return true เสมอจนกว่าจะ expire)
     *
     * @param  string|null  $messageType  text|image|sticker|video|audio|file|location|...
     */
    protected function isUserSpamming(string $userId, string $text, ?string $messageType): bool
    {
        $silencedKey = "fortune:spam:silenced:line:{$userId}";
        $strikeKey = "fortune:spam:strikes:line:{$userId}";
        $lastTextKey = "fortune:spam:last_text:line:{$userId}";
        $maxStrikes = 5;

        // 🛡️ (2026-05-21) CRITICAL FIX — Bypass spam guard ถ้า user อยู่ใน active prediction flow
        //   เคสจริง: ลูกค้า LINE Celtic 99฿ พิมพ์ "พร้อม" 5 ครั้ง (เปิดไพ่ 5 ใบ)
        //            → strike #3 (text เหมือนเดิม) ติด 5 ครั้ง → silenced 1 ชั่วโมง
        //            → บอทเงียบ ลูกค้าเสียเงินใช้ไม่ได้
        //   บอทเอง instruct ให้พิมพ์ "พร้อม" 10 ครั้งติดกัน — ไม่ใช่ spam!
        //
        //   Statuses ที่ bypass:
        //   - CELTIC_PICKING — เปิดไพ่ (พิมพ์ "พร้อม" ซ้ำ 10 ครั้ง)
        //   - CELTIC_AWAITING_QUESTION/GENERATING/QA_PROMPT — Q&A flow
        //   - PAID — รอ AI gen (อย่ารังควาน)
        //   - COLLECTING_BIRTHDATE/QUESTIONS/TAROT — pre-payment flow
        $bypassStatuses = [
            \App\Models\FortuneReading::STATUS_CELTIC_PICKING,
            \App\Models\FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            \App\Models\FortuneReading::STATUS_CELTIC_GENERATING,
            \App\Models\FortuneReading::STATUS_CELTIC_QA_PROMPT,
            \App\Models\FortuneReading::STATUS_PAID,
            \App\Models\FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            \App\Models\FortuneReading::STATUS_COLLECTING_QUESTIONS,
            \App\Models\FortuneReading::STATUS_COLLECTING_TAROT,
            \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
        ];
        try {
            $hasActiveFlow = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', $bypassStatuses)
                ->where('updated_at', '>=', now()->subHours(2))
                ->exists();

            if ($hasActiveFlow) {
                // ลูกค้าอยู่ใน flow ปกติ → ไม่ใช่ spam
                // เคลียร์ silence + strikes ที่อาจติดมาจาก guard ผิดพลาด
                if (\Illuminate\Support\Facades\Cache::has($silencedKey)) {
                    \Illuminate\Support\Facades\Cache::forget($silencedKey);
                    \Illuminate\Support\Facades\Cache::forget($strikeKey);
                    Log::info('LINE Fortune spam guard: bypassed + cleared silence (active flow)', [
                        'user_id' => $userId,
                        'text_preview' => mb_substr($text, 0, 50),
                    ]);
                }

                return false;
            }
        } catch (\Throwable $e) {
            // เช็คล้มเหลว → fall through ไป spam check ปกติ
            Log::debug('LINE spam guard: active flow check fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }

        if (\Illuminate\Support\Facades\Cache::has($silencedKey)) {
            return true;
        }

        $strikes = (int) \Illuminate\Support\Facades\Cache::get($strikeKey, 0);
        $newStrikes = 0;

        // 🚨 Strike 1: non-text/non-image + ไม่มี active bill
        if ($messageType && ! in_array($messageType, ['text', 'image'], true)) {
            $hasActiveBill = \App\Models\FortuneReading::where('facebook_user_id', $userId)
                ->whereIn('conversation_status', [
                    \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
                    \App\Models\FortuneReading::STATUS_PAID,
                ])
                ->exists();

            if (! $hasActiveBill) {
                $newStrikes++;
            }
        }

        // 🚨 Strike 2: ข้อความมี URL/ลิงก์
        if (! empty($text) && preg_match('#https?://|www\.|t\.me/|\.com/|\.net/|\.online/#i', $text)) {
            $newStrikes++;
        }

        // 🚨 Strike 3: ข้อความเหมือนเดิม
        if (! empty($text)) {
            $lastText = \Illuminate\Support\Facades\Cache::get($lastTextKey);
            if ($lastText === $text) {
                $newStrikes++;
            }
            \Illuminate\Support\Facades\Cache::put($lastTextKey, $text, now()->addMinutes(10));
        }

        if ($newStrikes === 0) {
            return false;
        }

        $totalStrikes = $strikes + $newStrikes;
        \Illuminate\Support\Facades\Cache::put($strikeKey, $totalStrikes, now()->addHour());

        if ($totalStrikes >= $maxStrikes) {
            \Illuminate\Support\Facades\Cache::put($silencedKey, true, now()->addHour());
            Log::warning('🚫 LINE Fortune spam guard: silenced user for 1 hour', [
                'user_id' => $userId,
                'total_strikes' => $totalStrikes,
                'message_type' => $messageType,
                'last_text' => mb_substr($text, 0, 80),
            ]);

            return true;
        }

        Log::info('LINE Fortune spam guard: strike recorded', [
            'user_id' => $userId,
            'strikes' => "{$totalStrikes}/{$maxStrikes}",
            'message_type' => $messageType,
        ]);

        return false;
    }
}
