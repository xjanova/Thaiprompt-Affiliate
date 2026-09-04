<?php

namespace App\Http\Controllers;

use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmProspect;
use App\Services\FortuneBannerService;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
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

    /**
     * 🐛 (2026-06-01) FortuneConversationService — core สนทนา/สลิป
     *   เดิม LINE controller "ลืม" declare+init property นี้ (FB มีครบ) → $this->conversationService
     *   undefined → ทุก call (isInPrediction/fuzzy/store/handleReturningSlipImage) throw แล้วถูกกลืนเงียบ
     *   เคส entony: ส่งสลิป → handleReturningSlipImage → "Undefined property" → ตกไป silent ignore
     */
    protected $conversationService;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->lineService = new LineFortuneService($this->settings);
        $this->channelManager = new FortuneChannelManager($this->settings);
        $this->takeoverService = app(FortuneTakeoverService::class);
        $this->banService = app(FortuneBanService::class);
        $this->bannerService = new FortuneBannerService($this->settings);
        $this->conversationService = new \App\Services\FortuneConversationService($this->settings);
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

        // 🏬 (2026-08-10) ระบบสาขา — ติดป้ายสาขาให้บิล/ลูกค้าฝั่ง LINE ด้วย
        //
        //    LINE ส่ง `destination` มาเป็น "bot userId (Uxxxx)" ไม่ใช่ channel id
        //    จับคู่กับ external_page_id ไม่ได้ → ใช้สาขาหลักของช่องทาง line แทน
        //    (วันนี้มี LINE OA เดียว ถ้าวันหน้ามีหลายช่อง ต้องแยก webhook URL ต่อช่อง
        //     แล้วส่ง code สาขาเข้ามาทาง route parameter)
        \App\Services\Fortune\FortunePageContext::set(
            \App\Services\Fortune\FortunePageContext::default('line')
        );

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

        // 🛡️ (2026-05-24) VIP Bypass — parity กับ FB
        //   User directive 2026-05-24: "ลูกค้าจ่ายตังแล้ว อย่าเอาอะไรไปขวาง บายพาสให้หมด"
        //   ใช้ FortuneConversationService::hasPaidActiveReading() (cache 30s — ราคาถูก)
        //   เคสจริง LINE Celtic 2026-05-21: ลูกค้าพิมพ์ "พร้อม" 5 ครั้ง → silenced 1 ชม. (จาก FortuneCelticKick.php:95 comment)
        $isVipPaid = app(\App\Services\FortuneConversationService::class)->hasPaidActiveReading($userId);

        // ========================================
        // 📦 (2026-08-26) PARKED DELIVERY — ของที่ push ไม่ออก ส่งคืนด้วย reply (ฟรี)
        //
        // เหตุการณ์ 2026-08-25: โควต้า push รายเดือนหมด (300/300) → คำตอบ Celtic ที่ลูกค้า
        // จ่าย 99฿ push ไม่ออก หายเงียบ · reply ยังฟรีและใช้ได้ → ใช้จังหวะที่ลูกค้าทักมา
        // (มี replyToken สด) สะสางของค้างก่อน
        //
        // 🚨 ต้องอยู่ "ก่อน" spam guard / ban guard — เหตุผลเดียวกับฝั่ง FB (pendingDelivery)
        //    ลูกค้าจ่ายเงินแล้วถูกแบน/ถูกมองว่าสแปมกลางทาง ของที่ซื้อต้องไม่ค้าง
        //    📌 [[feedback_never_interrupt_payment_to_prediction_flow]]
        // ========================================
        // ⚠️ เฉพาะข้อความ text เท่านั้น — รูป/สติกเกอร์ต้องเก็บ replyToken ไว้ให้ flow ของมันเอง
        //    (สลิปโอนเงินคือเคสสำคัญ: ถ้าเผา token ตรงนี้ ลูกค้าส่งสลิปแล้วเงียบ = ขวางทางจ่ายเงิน)
        if ($replyToken && $messageType === 'text') {
            // เรียงตามความสำคัญของสิ่งที่ลูกค้าจ่ายเงินซื้อ:
            //   บทสรุป Celtic 99฿ > คำทำนาย Deep 39฿ > คำตอบ Celtic รายข้อ
            $flushed = $this->flushParkedCelticSummary($userId, $replyToken)
                || $this->flushParkedDeepReading($userId, $replyToken)
                || $this->flushParkedCelticAnswers($userId, $replyToken);

            if ($flushed) {
                // replyToken ใช้ได้ครั้งเดียว — ใช้ไปกับของที่ลูกค้าจ่ายเงินแล้ว (สำคัญกว่า ack)
                // ที่เหลือของเทิร์นนี้ต้องไม่เอา token ที่ถูกเผาแล้วไปยิงซ้ำ (จะได้ 400 แล้ว fallback push เปล่า)
                $replyToken = null;
            }
        }

        // 🚫 (2026-04-28) Spam guard — silence คนป่วน (parity กับ FB)
        // กัน: video/sticker/audio ซ้ำๆ, ข้อความมี URL, ข้อความซ้ำ
        // ⚠️ (2026-05-24) Skip ถ้าเป็น VIP paid customer
        if (! $isVipPaid && $this->isUserSpamming($userId, $messageText, $messageType)) {
            Log::info('🚫 LINE Fortune: ignore spam message (silenced)', [
                'user_id' => $userId,
                'message_type' => $messageType,
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            return;
        }

        // 🛡️ (2026-05-24) VIP paid → clear LINE spam keys ที่อาจค้าง
        if ($isVipPaid) {
            \Illuminate\Support\Facades\Cache::forget("fortune:spam:silenced:line:{$userId}");
            \Illuminate\Support\Facades\Cache::forget("fortune:spam:strikes:line:{$userId}");
            \Illuminate\Support\Facades\Cache::forget("fortune:spam:last_text:line:{$userId}");
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
            //   🛡️ (2026-05-24) VIP Bypass — paid + active → skip image spam guard ด้วย
            if (! $isVipPaid) {
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
            }

            // 🆕 (2026-05-20 Phase 3b.5) Classify image — ดึง LINE content แล้ว classify
            // ⚠️ (2026-05-24) Gate classify ที่ $hasActiveFortune เท่านั้น
            //    User spec: "ห้ามใช้ image_vision กับระบบ chat ทั่วไป เพราะมันเปลืองโควต้า"
            //    เคส no active flow → skip classify (Vision quota saved) + silent ignore ตามด้านล่าง
            //    เคส active flow → classify ตามเดิม (Celtic vision / slip routing)
            //    หมายเหตุ: LINE webhook ไม่ส่ง URL ตรง ๆ ต้องดึงผ่าน content API + base64 ก่อน
            //    ถ้า download fail → fall through ไป existing logic (Celtic vision หรือ slip handler ดึงเอง)
            $intent = null;
            $cachedBase64 = null; // เก็บไว้ส่งต่อให้ handleCelticVisionImage ได้
            $hasActiveFortune = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
                ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
                ->exists();

            if (! empty($messageId) && $hasActiveFortune) {
                try {
                    $cachedBase64 = $this->downloadLineImageAsBase64($messageId);
                    if ($cachedBase64) {
                        $intentResult = app(\App\Services\Fortune\ImageIntentClassifier::class)
                            ->classify($cachedBase64, 'celtic_active');
                        $intent = $intentResult['intent'] ?? null;

                        Log::info('LINE: image classified (active flow)', [
                            'user_id' => $userId,
                            'intent' => $intent,
                            'confidence' => $intentResult['confidence'] ?? null,
                        ]);
                    }
                } catch (\Throwable $clErr) {
                    Log::debug('LINE: classifier exception (fall through)', [
                        'error' => $clErr->getMessage(),
                    ]);
                }
            }

            // 🔇 (2026-05-24) ไม่มี active flow + รูป → silent (Vision skipped, ประหยัด quota)
            //    User: "ตอนลูกค้าส่งรูปในแชทปกติ → เงียบ ไม่ตอบ"
            if (! $hasActiveFortune) {
                // 🧾 (2026-06-01) ก่อน silent — ลูกค้า returning ที่เคยพยายามดู Celtic แล้วทิ้งบิล
                //    (กดยกเลิก/บิลหมดอายุ) กลับมาส่ง "รูปสลิป" เพื่อยืนยันการโอน → ต้องตรวจ+กู้บิล ไม่ใช่ทิ้งเงียบ
                //    🐛 เคส entony (reading 4544): รูปสลิปถูกทิ้งเงียบที่นี่ทุกครั้ง — returning-slip recovery
                //       (commit 25a574767) wired ไว้ใน handleSlipImageOnly ซึ่งถูกเรียก *หลัง* guard นี้ → มาไม่ถึง
                //    🛡️ Cheap-guard: เช็ค recoverable Celtic ก่อน (1 indexed query, mirror findRecoverableCelticReading)
                //       แล้วค่อย download — ลูกค้าทั่วไปที่ส่งรูปเล่นๆ ไม่มีบิลค้าง → ไม่ download/ไม่ยิง SlipOK/Vision
                $hasRecoverableCeltic = ! empty($messageId)
                    && (new \App\Services\Fortune\SlipOkService($this->settings))->isEnabled()
                    && (
                        // 🆕 (2026-06-01) เพิ่งขอสลิปไป (paid-claim ไม่มีบิลค้าง) → ตรวจรูปสลิปที่ตามมา + แจ้งแอดมิน
                        \Illuminate\Support\Facades\Cache::has('fortune:returning_slip_ask:'.$userId)
                        || FortuneReading::where('facebook_user_id', $userId)
                            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                            ->where('created_at', '>=', now()->subDays(3)) // อนุโลม 3 วัน (sync SlipOkService::MAX_SLIP_AGE_DAYS)
                            ->where(function ($q) {
                                $q->where('is_paid', false)
                                    ->orWhereNull('celtic_questions_used')
                                    ->orWhere('celtic_questions_used', '<=', 0);
                            })
                            ->exists()
                    );

                if ($hasRecoverableCeltic) {
                    try {
                        $retB64 = $this->downloadLineImageAsBase64($messageId);
                        if (! empty($retB64)) {
                            $ret = $this->conversationService->handleReturningSlipImage('line', $userId, null, $retB64);
                            if ($ret !== null) {
                                $this->channelManager->sendResponse(
                                    FortuneChannelManager::PLATFORM_LINE,
                                    $userId,
                                    $ret,
                                    ['reply_token' => $replyToken, 'from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']
                                );

                                Log::info('LINE: returning slip image (no active flow) → ตรวจ+ตอบ', [
                                    'user_id' => $userId,
                                    'action' => $ret['action'] ?? null,
                                ]);

                                return;
                            }
                        }
                    } catch (\Throwable $retErr) {
                        Log::warning('LINE: returning slip image (no active flow) ล้มเหลว (non-blocking)', [
                            'user_id' => $userId,
                            'error' => $retErr->getMessage(),
                        ]);
                    }
                }

                // 💎 (2026-06-07, owner) ไม่มี flag/บิล แต่มีรูป → เก็บไว้เงียบๆ 30 นาที (ไม่รัน AI)
                //   เผื่อลูกค้าพิมพ์ "โอนแล้ว/เช็คสลิป" ทีหลัง → ย้อนเอารูปนี้มาเช็คได้ ไม่ต้องส่งใหม่
                //   เคารพ enable_image_vision=false: แค่ดาวน์โหลดเก็บ ไม่วิเคราะห์จนกว่าจะมีคำว่าโอนแล้ว
                if (! empty($messageId)
                    && ($this->settings->slipok_auto_provision ?? true)
                    && (new \App\Services\Fortune\SlipOkService($this->settings))->isEnabled()) {
                    try {
                        $capB64 = $this->downloadLineImageAsBase64($messageId);
                        if (! empty($capB64)) {
                            $this->conversationService->capturePendingSlipFromImage('line', $userId, null, $capB64);
                        }
                    } catch (\Throwable $capErr) {
                        Log::debug('LINE: capturePendingSlip ล้มเหลว (non-blocking)', ['error' => $capErr->getMessage()]);
                    }
                }

                Log::debug('LINE: silent ignore image (no active fortune flow, vision skipped)', [
                    'user_id' => $userId,
                ]);

                return;
            }

            // 🤐 Emoji/nonsense → silent
            //    หมายเหตุ: intent=null เมื่อ classify ถูก skip → in_array=false → fall through
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
                        // 🆕 (2026-09-04, owner) "เปิดระบบดูภาพตั้งแต่จ่ายเงินสำเร็จ จะได้ไม่พลาด"
                        FortuneReading::STATUS_CELTIC_PICKING,
                    ])
                    ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                    ->latest()
                    ->first();

                if ($celticVisionReading && $celticVisionReading->getCelticPickedCount() >= 10) {
                    $this->handleCelticVisionImage($userId, $messageId, $celticVisionReading, $replyToken);

                    return;
                }

                // 📸 (2026-09-04, owner) จ่ายแล้วแต่ยังเปิดไพ่ไม่ครบ → อ่านรูปเก็บเป็นบริบท + ตบกลับไปเปิดไพ่
                //   ⚠️ ต้องอยู่ *หลัง* classifier — ไม่งั้นสลิปโอนเงินระหว่างเปิดไพ่จะถูกดูดมาเป็น "รูปบริบท"
                //      แทนที่จะวิ่งเข้า handleSlipImageOnly (ทำเงินหาย)
                //   ⚠️ intent=null (classify พัง/ถูกข้าม) = *ไม่รู้ว่าใช่สลิปไหม* → ปล่อยไปทาง slip เหมือนเดิม
                if ($intent !== null && $celticVisionReading && $celticVisionReading->is_paid) {
                    $this->handleCelticPendingImage($userId, $messageId, $celticVisionReading, $replyToken, $cachedBase64);

                    return;
                }
            }

            $this->handleSlipImageOnly($userId, $replyToken, $messageId, $cachedBase64);

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
        // 🌙 (2026-08-21) ป้ายปุ่มเลนดวงรายวันไหลกลับมาเป็น "ข้อความ"
        //
        // LINE quick reply เป็น `type=message` เสมอ (FortuneChannelManager::sendLineMessageWithQuickReply)
        // ⇒ ไม่มี postback / ไม่มี payload ให้ router — ทุกการกดมาถึงเป็นข้อความที่ลูกค้า "พิมพ์"
        //
        // ป้ายส่วนใหญ่ตัวจับเจตนา (looksLikeDailyIntent) รับได้เอง **ยกเว้นปุ่ม VIP**
        // ที่มีคำว่า "ค่าครู" อยู่บนป้าย → โดน looksLikePricingQuestion ชิงไปตอบเป็น
        // กล่องราคา แทนที่จะพาเข้าเมนูแพคเกจจริง (บั๊กเดียวกับฝั่ง FB ที่แก้ไปแล้ว)
        //
        // ⚠️ **exact match เท่านั้น ห้าม substring** และจงใจไม่ใส่ปุ่ม 7 วันเกิดในตาราง
        //    (ชื่อวันปล่อยให้ตัวจับข้อความคัดเอง มันมีด่านกัน "วันพุธนี้จะไปหาหมอ" อยู่แล้ว)
        // 📌 [[rule_fb_quickreply_label_arrives_as_text]]
        // ========================================
        $dailyLabelPayload = $this->resolveDailyButtonPayloadFromLabel($messageText);

        if ($dailyLabelPayload !== null) {
            Log::info('🌙 LINE: ป้ายปุ่มดวงรายวันมาเป็นข้อความ → routing เหมือนกดปุ่มจริง', [
                'user_id' => $userId,
                'payload' => $dailyLabelPayload,
                'text_preview' => mb_substr($messageText, 0, 50),
            ]);

            $this->handleDailyButtonPayload($userId, $dailyLabelPayload, $replyToken);

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
            // 🚫 (2026-08-26) ด่านนี้ต้องกันเฉพาะ "rate limit ชั่วคราว" เท่านั้น
            //    โควต้า push รายเดือนหมด → ห้ามเข้าด่านนี้เด็ดขาด เพราะจะทิ้งคำถามลูกค้าทั้งเดือน
            //    ทั้งที่ reply ฟรีและตอบได้ปกติ (เหตุการณ์จริง 2026-08-25: quota 300/300)
            //    markQuotaExhausted() จงใจไม่ตั้ง backoff อยู่แล้ว — เช็คซ้ำตรงนี้เป็นตาข่ายชั้นสอง
            // 👑 (2026-09-01) ลูกค้าจ่ายแล้วต้องไม่โดนทิ้งข้อความช่วง backoff (สูงสุด 30 วิ) —
            //   กฎ paid-bypass-all-guards: ด่าน throttle เป็นด่านระบบ ไม่ใช่ด่านลูกค้าคนนี้
            if (LineGatekeeperService::isSystemThrottled()
                && ! LineGatekeeperService::isQuotaExhausted()
                && ! $isVipPaid) {
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
            // 💬 (2026-06-06) งดรูป welcome ถ้าได้รูปสัปดาห์นี้แล้ว (USER SPEC) — คำตอบยังส่งผ่าน processMessage
            // 🚦 (2026-09-01) แบนเนอร์เส้นนี้จำเป็นต้อง push (replyToken ต้องเก็บไว้ให้คำตอบจริง
            //   ของ processMessage) = ข้อความไม่วิกฤต → ต้องผ่านกันชนโควตาก่อน
            //   โควตาต่ำ = งดรูป (ไม่ mark ส่งแล้ว — โควตากลับมาเมื่อไหร่ค่อยได้รูปตามปกติ)
            if (! $hasActiveForAffiliate
                && ! \App\Models\FortuneInviteMessage::shouldSuppressImage($userId, 'line')
                && $this->lineService->canSpendNonCriticalPush()) {
                $lineWelcomeImgSent = $this->bannerService->sendBannerOnce(
                    $userId,
                    fn ($url) => $this->lineService->sendImage($userId, $url),
                    'welcome',
                    24,
                    'line'
                );
                if ($lineWelcomeImgSent) {
                    \App\Models\FortuneUserCredit::markImageSent($userId, 'line');
                }
            }

            // ⏳ (2026-09-01) โชว์ "กำลังพิมพ์..." ระหว่าง settle-buffer + AI (ฟรี ไม่กินโควตา)
            //   เดิมลูกค้าเจอความเงียบ ~10-40 วิ ช่วง debounce → คิดว่าบอทไม่ตอบแล้วพิมพ์ซ้ำ
            //   indicator หายเองทันทีที่คำตอบมาถึง — best-effort ล้มได้เงียบๆ
            try {
                $this->lineService->showLoadingAnimation($userId, 60);
            } catch (\Throwable $e) {
                // non-blocking
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
                        .'ระหว่างรอ พิมพ์ถามแม่หมอต่อได้นะคะ 🔮',
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

            // 💬 (2026-06-06) งดรูป welcome ถ้าได้รูปสัปดาห์นี้แล้ว (USER SPEC) → ปล่อยให้ greeting text แทน
            $bannerUrl = null;
            if (\App\Models\FortuneInviteMessage::shouldSuppressImage($userId, 'line')) {
                $sent = false;
            } else {
                // 🚦 (2026-09-01) เส้น follow มี replyToken สดอยู่ในมือ — เลิก push รูป (เผาโควตา
                //    1 หน่วย/ลูกค้าใหม่) เก็บ URL ไว้รวมใน replyMessage เดียวกับ text ด้านล่าง = ฟรี
                //    (เดิม 2026-07-15 แก้จาก sendImageMessage ที่ไม่มีจริง มาเป็น sendImage = push)
                $sent = $bannerService->sendBannerOnce(
                    $userId,
                    function ($url) use (&$bannerUrl) {
                        $bannerUrl = $url;

                        return true;
                    },
                    'welcome',
                    24,
                    'line'
                );
                if ($sent) {
                    \App\Models\FortuneUserCredit::markImageSent($userId, 'line');
                }
            }

            // 📨 รวมข้อความ text ทั้งหมดส่งใน replyMessage ครั้งเดียว
            //    (replyToken ใช้ได้ครั้งเดียว — ห้ามเรียกซ้ำ)
            $messages = [];

            // 🖼️ รูป welcome เป็น message แรกใน reply (ก่อน text) — ลำดับเดิมที่ลูกค้าเห็น
            if ($sent && $bannerUrl && $replyToken) {
                $messages[] = $this->lineService->imageMessageObject($bannerUrl);
            } elseif ($sent && $bannerUrl && ! $replyToken) {
                // edge: follow event ไม่มี replyToken (แทบไม่เกิด) → ตกกลับ push แบบเดิม + ด่านโควตา
                if ($this->lineService->canSpendNonCriticalPush()) {
                    $this->lineService->sendImage($userId, $bannerUrl);
                }
            }

            // ถ้า banner ปิดอยู่หรือไม่มี — ส่ง greeting สั้น ๆ แทน (ไม่มี flex card)
            if (! $sent) {
                $greet = $userName ? "🌙 ยินดีต้อนรับ คุณ{$userName} ค่ะ" : '🌙 ยินดีต้อนรับค่ะ';
                $messages[] = ['type' => 'text', 'text' => $greet];
            }

            // 🎬 (2026-07-15) คลิปบรรยายแผนสร้างรายได้ — ส่งให้คนแอดใหม่ทุกคน (ถ้าแอดมินเปิด)
            //    ⚠️ ต้องส่งไม่ว่า $sent จะ true หรือ false — เดิมสาขา $sent=true ไม่ส่ง text เลย
            if ($planVideoMsg = $this->planVideoWelcomeMessage()) {
                $messages[] = ['type' => 'text', 'text' => $planVideoMsg];
            }

            if (! empty($messages)) {
                $this->lineService->replyMessage($replyToken, $messages);
            }
        } catch (\Throwable $e) {
            Log::warning('LINE Welcome: banner ส่งไม่ได้ — ใช้ text fallback', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $greet = $userName ? "🌙 ยินดีต้อนรับ คุณ{$userName} ค่ะ" : '🌙 ยินดีต้อนรับค่ะ';
            $fallback = [['type' => 'text', 'text' => $greet]];
            if ($planVideoMsg = $this->planVideoWelcomeMessage()) {
                $fallback[] = ['type' => 'text', 'text' => $planVideoMsg];
            }
            $this->lineService->replyMessage($replyToken, $fallback);
        }
    }

    /**
     * ข้อความชวนดูคลิปบรรยายแผน (ส่งตอนแอดเพื่อนใหม่)
     *
     * คืน null เมื่อแอดมินยังไม่เปิด / ไม่ได้ตั้งลิงก์ / ปิดการส่งตอน welcome
     * → default ปิดทั้งคู่ ต้องไปเปิดที่หน้าแอดมินก่อน
     */
    protected function planVideoWelcomeMessage(): ?string
    {
        $s = $this->settings;

        if (! ($s->plan_video_enabled ?? false) || ! ($s->plan_video_send_on_welcome ?? false)) {
            return null;
        }

        $url = trim((string) ($s->plan_video_url ?? ''));
        if ($url === '') {
            return null;
        }

        return "🎬 อยากมีรายได้เสริมจากการชวนเพื่อนดูดวงไหมคะ\n"
            ."ดูคลิปนี้ 5 นาที เข้าใจครบ เริ่มได้เลย\n\n"
            .$url."\n\n"
            .'(พิมพ์ "บรรยายแผน" เมื่อไหร่ก็ได้ เพื่อดูอีกครั้งค่ะ)';
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

        // 🚫 (2026-08-21) ด่านแบน — parity กับฝั่ง FB (rule_spam_guard_parity_fb_line)
        //   เดิมด่านแบนของ LINE เรียกที่สายข้อความจุดเดียว (:202) เส้นปุ่มไม่เคยผ่าน
        //   ⇒ คนที่ถูกแบนยังกดปุ่ม Rich Menu / Flex สั่งบอททำงานได้ครบ
        //   ⚠️ เช็ค "จ่ายเงินแล้ว" ก่อนเสมอ — ห้ามขวางเส้นจ่ายแล้วรอคำทำนาย
        try {
            if (! $this->conversationService?->hasPaidActiveReading($userId)) {
                $postbackBan = $this->banService->getActiveBan('line', $userId);

                if ($postbackBan !== null) {
                    Log::info('🚫 LINE postback: ผู้ใช้ถูกแบนอยู่ — ไม่ประมวลผลปุ่ม', [
                        'user_id' => $userId,
                        'data' => mb_substr($data, 0, 50),
                        'ban_id' => $postbackBan->id,
                    ]);

                    if ($this->banService->shouldNotify($postbackBan)) {
                        try {
                            $banMessage = $this->banService->buildBanReplyMessage($postbackBan);
                            // ใช้ replyToken ก่อนเสมอ — ฟรี ไม่กินโควตา push
                            if ($replyToken) {
                                $this->lineService->replyMessage($replyToken, [
                                    ['type' => 'text', 'text' => $banMessage],
                                ]);
                            } else {
                                $this->lineService->sendMessage($userId, $banMessage);
                            }
                            $this->banService->recordNotification($postbackBan);
                        } catch (\Throwable $notifyErr) {
                            Log::debug('LINE postback: แจ้งสถานะแบนไม่สำเร็จ (non-blocking)', [
                                'error' => $notifyErr->getMessage(),
                            ]);
                        }
                    }

                    return;
                }
            }
        } catch (\Throwable $banErr) {
            // ด่านเสริมพัง ต้องไม่ทำให้ปุ่มตายทั้งระบบ
            Log::warning('LINE postback: ด่านแบนล้ม (ปล่อยผ่าน)', [
                'user_id' => $userId,
                'error' => $banErr->getMessage(),
            ]);
        }

        // 🚦 (2026-08-21) ด่านกดปุ่มรัว — parity กับฝั่ง FB
        //   ใช้ replyToken ก่อนเสมอ (ฟรี ไม่กินโควตา push)
        try {
            $floodResult = app(\App\Services\Fortune\NavFloodGuard::class)
                ->check('line', $userId, $data);

            if ($floodResult['action'] !== \App\Services\Fortune\NavFloodGuard::ACTION_PASS) {
                if (! empty($floodResult['message'])) {
                    try {
                        if ($replyToken) {
                            $this->lineService->replyMessage($replyToken, [
                                ['type' => 'text', 'text' => $floodResult['message']],
                            ]);
                        } else {
                            $this->lineService->sendMessage($userId, $floodResult['message']);
                        }
                    } catch (\Throwable $sendErr) {
                        Log::debug('LINE: ส่งคำเตือนกดปุ่มรัวไม่สำเร็จ (non-blocking)', [
                            'error' => $sendErr->getMessage(),
                        ]);
                    }
                }

                return;
            }
        } catch (\Throwable $floodErr) {
            Log::warning('LINE: ด่านกดปุ่มรัวล้ม (ปล่อยผ่าน)', [
                'user_id' => $userId,
                'error' => $floodErr->getMessage(),
            ]);
        }

        // Parse postback data
        parse_str($data, $params);
        $action = $params['action'] ?? '';

        // 🌙 (2026-08-21) เลนดวงฟรีรายวัน — ปุ่มชุด DAILY_* ไม่เคยมี case ที่นี่เลย
        //   เดิมเลนนี้เปิดเฉพาะ FB ปุ่มจึงถูกนิยามด้วย payload แบบ FB (DAILY_BDAY_0 ฯลฯ)
        //   พอเปิด LINE แล้วมีคนทำปุ่ม Flex/Rich Menu ชี้มา จะตกไป default = log แล้วเงียบ
        //   รับทั้ง 2 รูปแบบ: `action=daily_show_mine` และ payload ดิบ `DAILY_SHOW_MINE`
        $dailyPayload = $this->resolveDailyButtonPayload($data !== '' ? $data : $action)
            ?? $this->resolveDailyButtonPayload($action);

        if ($dailyPayload !== null) {
            Log::info('🌙 LINE postback: ปุ่มเลนดวงรายวัน', [
                'user_id' => $userId,
                'payload' => $dailyPayload,
            ]);

            $this->handleDailyButtonPayload($userId, $dailyPayload, $replyToken);

            return;
        }

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
            // 📚 (2026-07-25) Rich Menu v3 — คำทำนายย้อนหลัง (เลือกได้ 3 บิลล่าสุด)
            'view_history' => $this->handleSimulateTextPostback($userId, $replyToken, 'บิลของฉัน'),
            // 🌳 (2026-07-25) Rich Menu v3 — ผังงาน (Magic Link เข้าเว็บจันทราแบบล็อกอินอัตโนมัติ)
            'view_network' => $this->handleViewNetworkPostback($userId, $replyToken),
            // 🐛 (2026-07-25) ปุ่มเก่าบน Rich Menu ที่ deploy อยู่ — เดิมไม่มี handler กดแล้วเงียบ
            //   celtic_cross → "99" เข้า tier-direct (มี paid-active guard ในตัว)
            //   affiliate_share → "แชร์" (isShareRequest → ลิงก์ชวนเพื่อน)
            'celtic_cross' => $this->handleSimulateTextPostback($userId, $replyToken, '99'),
            'affiliate_share' => $this->handleSimulateTextPostback($userId, $replyToken, 'แชร์'),
            'talk_human' => $this->lineService->sendMessageWithReplyFallback(
                $userId,
                "💬 พิมพ์ข้อความถึงแม่หมอได้เลยนะคะ เดี๋ยวแม่หมอตอบให้ค่ะ\n\nหากต้องการคุยกับแอดมินคนจริง พิมพ์แจ้งไว้ได้เลย ทีมงานจะเข้ามาตอบเร็วที่สุดค่ะ 🙏",
                $replyToken
            ),
            default => Log::warning('LINE Webhook: Unknown postback action', [
                'user_id' => $userId,
                'action' => $action,
                'data' => $data,
            ]),
        };
    }

    /**
     * 🌳 (2026-07-25) Rich Menu "ผังงาน" — ส่ง Magic Link เข้าเว็บจันทรา (หน้า /mlm) แบบล็อกอินอัตโนมัติ
     *
     * reply Flex ปุ่มลิงก์ (ฟรี — ไม่กิน push quota) / ลิงก์ผูกลูกค้ารายคน อายุ 72 ชม.
     * ระบบ Magic Link อยู่หลังสวิตช์ enable_web_fortune_button — ปิดอยู่ = แจ้งลูกค้าอย่างสุภาพ
     */
    protected function handleViewNetworkPostback(string $userId, ?string $replyToken): void
    {
        try {
            $linkService = app(\App\Services\FortuneWebLinkService::class);

            if (! $linkService->isEnabled()) {
                Log::warning('LINE view_network: enable_web_fortune_button ปิดอยู่ — แจ้งลูกค้าแทนส่งลิงก์', [
                    'user_id' => $userId,
                ]);
                $this->lineService->sendMessageWithReplyFallback(
                    $userId,
                    "🌳 ระบบดูผังงานบนเว็บกำลังเตรียมเปิดให้ใช้งานเร็วๆ นี้ค่ะ 🙏\n\nระหว่างนี้พิมพ์ \"สายงาน\" เพื่อดูรายชื่อคนที่แนะนำได้เลยนะคะ",
                    $replyToken
                );

                return;
            }

            $magicLink = $linkService->generateChatLink('line', $userId, '/mlm');

            $flex = [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'backgroundColor' => '#241038',
                    'contents' => [
                        ['type' => 'text', 'text' => '🌳 ผังงานของฉัน', 'color' => '#E7C97A', 'size' => 'xl', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => 'ดูสายงาน รายได้ และค่าแนะนำของคุณบนเว็บจันทรา', 'color' => '#C8B8DE', 'size' => 'sm', 'wrap' => true, 'margin' => 'sm'],
                    ],
                ],
                'body' => [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'backgroundColor' => '#160A26', 'spacing' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => '✦ กดปุ่มด้านล่าง เข้าเว็บได้ทันที ไม่ต้องกรอกรหัสผ่าน', 'color' => '#F3E9FF', 'size' => 'md', 'wrap' => true],
                        ['type' => 'text', 'text' => '✦ ลิงก์เป็นของคุณคนเดียว ใช้ได้ 3 วัน', 'color' => '#F3E9FF', 'size' => 'md', 'wrap' => true],
                    ],
                ],
                'footer' => [
                    'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg', 'backgroundColor' => '#160A26',
                    'contents' => [
                        // 🔘 (2026-07-25) box+action — ตัวหนังสือเข้มบนพื้นทอง (component button บังคับสีขาว = จาง)
                        $this->lineService->buildFlexTapButton(
                            '🌳 เปิดผังงานของฉัน',
                            ['type' => 'uri', 'label' => '🌳 เปิดผังงาน', 'uri' => $magicLink],
                            'gold'
                        ),
                    ],
                ],
            ];

            $this->lineService->sendFlexWithReplyFallback($userId, $flex, '🌳 ผังงานของฉัน — กดเปิดได้เลย', $replyToken);
        } catch (\Throwable $e) {
            Log::error('LINE view_network postback ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                '🙏 ขออภัยค่ะ ระบบผังงานขัดข้องชั่วคราว ลองใหม่อีกครั้งนะคะ',
                $replyToken
            );
        }
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
                        'text' => "• ชวนคนมาดูดวง → {$this->settings->fortuneLevel1Text(true)}/คน (Level 1)\n• เพื่อนชวนต่อ → ส่วนแบ่ง Level 2",
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
            $fallback = "🎉 วิธีเริ่มง่ายๆ 3 ขั้น\n\n1️⃣ ดูดวง {$deepPrice} บาท\n2️⃣ ได้เป็นสมาชิก\n3️⃣ แชร์ลิงก์ได้เลย\n\n💰 ชวนคน → {$this->settings->fortuneLevel1Text(true)}/คน\n\nพิมพ์ \"ดูดวง\" เพื่อเริ่มค่ะ";
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
                        'text' => "แม่หมอมีทางให้ค่ะ\n\nชวนเพื่อนมาดูดวง\nได้ค่าชวน {$this->settings->fortuneLevel1Text(true)}/คน\n\n✨ ไม่ต้องลงทุน\n📌 ชวนได้ไม่จำกัด",
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
            'altText' => '💰 รายได้เสริม — ชวนเพื่อนได้ '.$this->settings->fortuneLevel1Text(true).'/คน',
            'contents' => $flex,
        ]];

        if ($replyToken) {
            $this->lineService->replyMessage($replyToken, $messages);
        } else {
            // fallback: ส่งเป็น text (ไม่ใช่ flex) ถ้าไม่มี reply token
            $fallbackText = "💰 รายได้เสริม\n\nชวนเพื่อนมาดูดวงกับแม่หมอ ได้ค่าชวน {$this->settings->fortuneLevel1Text(true)}/คน\n\nพิมพ์ \"อยาก\" ถ้าสนใจ หรือ \"ไม่อยาก\" เพื่อข้ามค่ะ";
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
     * 🌙 (2026-08-21) ชื่อวันในสัปดาห์ตาม index ที่คอลัมน์ birth_day ใช้ (0=อาทิตย์)
     *
     * ⚠️ ห้ามใช้ Carbon locale — ต้องตรงกับ DailyHoroscopeModeTrait::DAILY_DAY_NAMES เป๊ะ ๆ
     *
     * @return array<int, string>
     */
    protected function dailyDayNames(): array
    {
        return ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    }

    /**
     * 🌙 นิยามปุ่มเลนดวงรายวันทั้งหมด — อ่านจากตัวจริง ไม่ hardcode
     *
     * แอดมิน/เจ้าของแก้ป้ายปุ่มมาแล้วหลายรอบ — hardcode ไว้ที่นี่ = เน่าแน่นอน
     *
     * @return array<int, array{content_type?: string, title?: string, payload?: string}>
     */
    protected function dailyButtonDefinitions(): array
    {
        return array_merge(
            [FortuneConversationService::dailyFreeStartQuickReply()],
            FortuneConversationService::dailyShowMineQuickReplies(),
            [FortuneConversationService::dailyUpgradeQuickReply()],
            FortuneConversationService::dailyBirthdayQuickReplies(),
        );
    }

    /**
     * 🌙 payload ของเลนดวงรายวันที่ "postback" ส่งมาได้ (การกดจริง = explicit เสมอ)
     *
     * @return array<int, string>
     */
    protected function dailyButtonPayloads(): array
    {
        return array_values(array_filter(array_map(
            static fn ($b) => (string) ($b['payload'] ?? ''),
            $this->dailyButtonDefinitions()
        )));
    }

    /**
     * 🌙 แปลง postback data / action → payload ของปุ่มเลนดวงรายวัน
     *
     * รับทั้ง `DAILY_SHOW_MINE` (payload ดิบ) และ `daily_show_mine` (รูปแบบ action ของ LINE)
     *
     * @return string|null null = ไม่ใช่ปุ่มเลนนี้ → ปล่อยให้ match() เดิมจัดการ
     */
    protected function resolveDailyButtonPayload(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $candidate = mb_strtoupper(str_replace('-', '_', $raw));

        return in_array($candidate, $this->dailyButtonPayloads(), true) ? $candidate : null;
    }

    /**
     * 🌙 แปลง "ป้ายปุ่มที่ไหลกลับมาเป็นข้อความ" → payload
     *
     * 🚫 **จงใจใส่เฉพาะปุ่มที่กดแล้วไม่มีอะไรเสียหาย** (เหตุผลเดียวกับ
     *    FacebookWebhookController::quickReplyTitlePayloadMap):
     *      - 🎁 รับดวงฟรีประจำวัน / 🔮 ดูดวงวันนี้เลย → ของฟรี
     *      - ปุ่ม VIP → พาไปเมนูแพคเกจ (ยังไม่สร้างบิล ยังไม่ตัดเงิน)
     *
     * 🚫 **ไม่ใส่ปุ่ม 7 วันเกิด** — ชื่อวันเป็นคำที่คนใช้คุยเรื่องอื่นได้ทุกวัน
     *    ปล่อยให้ resolveBirthDayNameIndex() คัดเอง (มันมีด่านกัน "วันพุธนี้จะไปหาหมอ"
     *    / "ศุกร์นี้เงินเดือนออกไหม" อยู่แล้ว) — บังคับ route ที่นี่ = พังด่านพวกนั้นทิ้ง
     *
     * @return string|null null = ไม่ใช่ป้ายปุ่ม → เป็นข้อความปกติ
     */
    protected function resolveDailyButtonPayloadFromLabel(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        $normalized = $this->normalizeDailyButtonLabel($text);

        if ($normalized === '') {
            return null;
        }

        $safeButtons = array_merge(
            [FortuneConversationService::dailyFreeStartQuickReply()],
            FortuneConversationService::dailyShowMineQuickReplies(),
            [FortuneConversationService::dailyUpgradeQuickReply()],
        );

        foreach ($safeButtons as $button) {
            $title = $this->normalizeDailyButtonLabel((string) ($button['title'] ?? ''));
            $payload = (string) ($button['payload'] ?? '');

            if ($title !== '' && $payload !== '' && $title === $normalized) {
                return $payload;
            }
        }

        return null;
    }

    /**
     * 🧹 ทำให้ข้อความ/ป้ายปุ่มอยู่ในรูปเดียวกันก่อนเทียบ
     *
     * ตัด: mention "@Meta AI" → emoji/variation selector → zero-width → ยุบช่องว่าง → lowercase
     *
     * ⚠️ range emoji ชุดเดียวกับ FacebookWebhookController::normalizeQuickReplyTitle
     *    — ไม่ทับภาษาไทย (U+0E00-0E7F) / ลาว (U+0E80-0EFF)
     */
    protected function normalizeDailyButtonLabel(string $text): string
    {
        $text = $this->conversationService?->stripPlatformAiMention($text) ?? $text;

        $text = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{2190}-\x{21FF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}\x{200D}\x{20E3}]/u',
            '',
            $text
        ) ?? $text;

        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strtolower(trim($text));
    }

    /**
     * 🌙 (2026-08-21) ลูกค้า LINE กดปุ่มเลนดวงรายวัน — มิเรอร์ฝั่ง FB ทุกประการ
     *
     * ⚠️ จงใจไม่เขียน logic เอง แต่แปลงเป็น "ข้อความชื่อวัน" แล้วส่งเข้า processMessage
     *    → ผ่าน maybeHandleDailyHoroscopeReply ที่มี guard ครบชุด (โหมด/ช่องทาง/ธง/
     *      จ่ายเงินแล้ว/บิลค้าง/กดรัว) เขียนแยกที่นี่ = ต้องก็อป guard มาทั้งชุด
     *      แล้วมันจะหลุดกันในอนาคต (เหตุผลเดียวกับ FacebookWebhookController::handleDailyBirthdayPick)
     */
    protected function handleDailyButtonPayload(string $userId, string $payload, ?string $replyToken): void
    {
        $dayNames = $this->dailyDayNames();

        try {
            // 🔘 ปุ่ม 7 วันเกิด — กดปุ่ม = ถือว่า "เราถามไปแล้ว" → ตั้งธงให้ด่านขาเข้ารับช่วงต่อ
            if (str_starts_with($payload, 'DAILY_BDAY_')) {
                $dayIndex = (int) substr($payload, -1);

                if (! isset($dayNames[$dayIndex])) {
                    return;
                }

                $this->markDailyPendingSafe($userId);
                $this->handleSimulateTextPostback($userId, $replyToken, 'วัน'.$dayNames[$dayIndex]);

                return;
            }

            // 💎 ปุ่ม VIP — ต้องส่ง "ดูดวง" ไม่ใช่ป้ายปุ่ม
            //    ป้ายมีคำว่า "ค่าครู" ⇒ ปล่อยไหลเป็นข้อความจะโดน looksLikePricingQuestion
            //    ตอบเป็นกล่องราคา แทนที่จะพาเข้าเมนูแพคเกจจริง
            if ($payload === 'DAILY_VIP_PACKAGES') {
                $this->handleSimulateTextPostback($userId, $replyToken, 'ดูดวง');

                return;
            }

            // 🎁 DAILY_FREE_START / 🔮 DAILY_SHOW_MINE — รู้วันเกิดแล้วส่งเลย ไม่รู้ก็ถาม
            $dayIndex = \App\Models\FortuneUserCredit::findBirthDayIndex($userId, 'line');

            if ($dayIndex === null || ! isset($dayNames[$dayIndex])) {
                $this->markDailyPendingSafe($userId);

                // ⚠️ คนกดปุ่ม "รับดวงฟรี" คือคนที่เรายังไม่เคยถาม — ห้ามพูดว่า "อีกครั้ง"
                $ask = $payload === 'DAILY_FREE_START'
                    ? "🌙 ได้เลยค่ะ ดวงประจำวันนี้แม่หมอเปิดให้ฟรี ไม่มีค่าใช้จ่าย\nเจ้าชะตาเกิดวันอะไรคะ กดเลือกด้านล่างได้เลย ✨"
                    : '🌙 ขอทราบวันเกิดอีกครั้งได้ไหมคะ เดี๋ยวแม่หมอเปิดดวงวันนี้ให้ฟรีเลย';

                $this->replyWithDailyQuickReplies(
                    $userId,
                    $ask,
                    FortuneConversationService::dailyBirthdayQuickReplies(),
                    $replyToken
                );

                return;
            }

            $this->markDailyPendingSafe($userId);
            $this->handleSimulateTextPostback($userId, $replyToken, 'วัน'.$dayNames[$dayIndex]);
        } catch (\Throwable $e) {
            Log::warning('🌙 LINE: ปุ่มเลนดวงรายวันล้ม', [
                'user_id' => $userId,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🌙 ตั้งธง "ถามวันเกิดไปแล้ว" ฝั่ง LINE — ล้มแล้วต้องไม่ทำให้ปุ่มตาย
     */
    protected function markDailyPendingSafe(string $userId): void
    {
        try {
            $this->conversationService?->markDailyPending('line', $userId);
        } catch (\Throwable $e) {
            Log::warning('🌙 LINE Daily: ตั้งธงจากปุ่มไม่สำเร็จ (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🌙 ส่งข้อความ + ปุ่มเลนดวงรายวัน
     *
     * ⚠️ ห้ามส่ง array รูปแบบ FB เข้า sendQuickReplies ตรง ๆ — buildQuickReplyItems()
     *    จะ fallback ไปหยิบ `payload` มาเป็น text ⇒ ลูกค้ากดปุ่มแล้วส่งคำว่า
     *    "DAILY_BDAY_1" เข้าแชท (ปุ่มตาย + หน้าแตก)
     *
     * ⚠️ ใช้ตัวแปลงกลาง FortuneConversationService::dailyQuickRepliesForLine() เท่านั้น
     *    ห้ามเขียน array_map เองที่นี่ — ปุ่มวันอาทิตย์มี VS16 ติดป้าย ซึ่งทำให้ตัวปอก
     *    ของ resolveBirthDayNameIndex() อ่านชื่อวันไม่ออก (ดูคำอธิบายเต็มในตัวแปลง)
     *
     * @param  array<int, array{title?: string, payload?: string}>  $buttons  ปุ่มรูปแบบ FB
     */
    protected function replyWithDailyQuickReplies(string $userId, string $message, array $buttons, ?string $replyToken): void
    {
        $lineQr = FortuneConversationService::dailyQuickRepliesForLine($buttons);

        // LINE API ห้าม quickReply.items ว่าง
        if (empty($lineQr)) {
            $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

            return;
        }

        $items = array_map(static fn ($r) => [
            'type' => 'action',
            'action' => ['type' => 'message', 'label' => $r['label'], 'text' => $r['text']],
        ], $lineQr);

        // ลอง replyMessage ก่อน (ฟรี ไม่กินโควตา push)
        if ($replyToken && $this->lineService->replyMessage($replyToken, [[
            'type' => 'text',
            'text' => $message,
            'quickReply' => ['items' => $items],
        ]])) {
            return;
        }

        $this->lineService->sendQuickReplies($userId, $message, $lineQr);
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
     * @return string|null data:image/jpeg;base64,...  หรือ null ถ้า fail
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

    /**
     * 📸 (2026-09-04, owner) จ่ายเงินแล้วแต่ยังเปิดไพ่ไม่ครบ 10 ใบ — อ่านรูปเก็บไว้เป็นบริบท
     *
     * owner directive: *"การเปิดระบบดูภาพ และรับคำพูด ควรเปิดตั้งแต่จ่ายเงินสำเร็จเลย จะได้ไม่พลาด
     *   และถ้ายังอยู่ในขั้นตอนเปิดไพ่ บอทก็จะตบให้ลูกค้าเข้ามาเปิดไพ่ก่อนค่อยถาม"*
     *
     * ต่างจาก handleCelticVisionImage(): ตัวนั้นตอบคำถามจากรูป + กินโควต้าคำถาม (ต้องเปิดไพ่ครบก่อน)
     *   ตัวนี้แค่ให้ vision บรรยายว่าเห็นอะไร แล้ว park ไว้ให้พรอมต์รอบทำนายใช้ต่อ
     */
    protected function handleCelticPendingImage(
        string $userId,
        string $messageId,
        \App\Models\FortuneReading $reading,
        ?string $replyToken,
        ?string $cachedBase64 = null
    ): void {
        $picked = $reading->getCelticPickedCount();
        $remain = max(0, 10 - $picked);

        $summaryLine = '📸 แม่หมอ *เก็บรูปที่ลูกส่งมาไว้แล้ว* นะคะ ไม่หายไปไหน';

        try {
            $base64 = $cachedBase64 ?: (empty($messageId) ? null : $this->downloadLineImageAsBase64($messageId));

            if ($base64 !== null) {
                $captured = app(\App\Services\CelticCrossService::class)
                    ->captureImageAsContext($reading, $base64);

                // 🔇 ส่งรูปรัวเกินเพดาน → เงียบ ไม่ตอบ (กันเปลืองโควต้าข้อความ LINE)
                //   [[rule_line_push_is_emergency_reserve_only]] — ข้อความเตือนซ้ำ ๆ ไม่คุ้มโควต้า
                if (($captured['reason'] ?? null) === 'cap_reached') {
                    Log::info('LINE: Celtic pending image เกินเพดานเก็บรูป → silent', [
                        'user_id' => $userId,
                        'reading_id' => $reading->id,
                    ]);

                    return;
                }

                if (! $captured['captured']) {
                    $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
                }
            } else {
                $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
            }
        } catch (\Throwable $e) {
            Log::warning('LINE: Celtic pending image capture ล้มเหลว (non-blocking)', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
            $summaryLine = '📸 รูปที่ลูกส่งมา แม่หมอยังเปิดดูไม่ได้ตอนนี้ค่ะ — เปิดไพ่ครบแล้วส่งมาใหม่อีกทีนะคะ';
        }

        $this->lineService->sendMessageWithReplyFallback(
            $userId,
            $summaryLine."\n\n"
                ."🃏 ตอนนี้เปิดไพ่ได้ *{$picked}/10 ใบ* (เหลืออีก {$remain} ใบ)\n"
                ."ไพ่ครบเมื่อไหร่ แม่หมอจะอ่านรูปนี้ผูกกับไพ่ให้เต็ม ๆ ค่ะ\n\n"
                .'👉 พิมพ์ *"พร้อม"* เพื่อเปิดไพ่ใบถัดไปได้เลยค่ะ ✨',
            $replyToken
        );
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

    protected function handleSlipImageOnly(string $userId, ?string $replyToken, ?string $messageId = null, ?string $cachedBase64 = null): void
    {
        try {
            // หา reading ที่ user กำลังใช้งานอยู่ (LINE platform)
            //   🔮 (2026-05-04) ครอบคลุม Celtic active states ด้วย — กันลูกค้าส่งสลิประหว่างเปิดไพ่
            $activeReading = FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', array_merge(
                    // 🐛 (2026-05-31) ใช้ PENDING_PAYMENT_STATUSES — เดิมตกหล่น celtic_pending_payment
                    //   ทำให้ Celtic 99 ที่ส่งสลิปตอนรอจ่าย ไม่ถูกจับ → silent
                    FortuneReading::PENDING_PAYMENT_STATUSES,
                    [FortuneReading::STATUS_PAID],
                    FortuneReading::CELTIC_ACTIVE_STATUSES,
                    // 🌙 (2026-07-02 FTU-260702-F3343) รวม DEEP_ACTIVE_STATUSES — เดิมตกหล่น
                    //   collecting_birthdate/questions/tarot → Deep-39 จ่ายแล้วส่งรูประหว่างรอวันเกิด → silent
                    FortuneReading::DEEP_ACTIVE_STATUSES
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
                // 🔍 (2026-05-31) ลูกค้าส่งสลิป = เคลมว่าจ่ายแล้ว → ลองตัดบิลด้วย fuzzy ก่อน
                //   user spec: "ลูกค้าส่งบิลมา แต่บิลไม่ตัด" → ต้องพยายามตัดให้จริง (รองรับโอนยอดไม่ตรง/เศษ)
                try {
                    $fuzzySlip = $this->conversationService->tryFuzzyMatchForSlip($activeReading);
                    if ($fuzzySlip !== null) {
                        $this->channelManager->sendResponse(
                            FortuneChannelManager::PLATFORM_LINE,
                            $userId,
                            $fuzzySlip,
                            ['reply_token' => $replyToken, 'from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']
                        );

                        Log::info('LINE: รับสลิป → fuzzy match', [
                            'user_id' => $userId,
                            'reading_id' => $activeReading->id,
                            'fuzzy_action' => $fuzzySlip['action'] ?? null,
                        ]);

                        return;
                    }
                } catch (\Throwable $fuzzyErr) {
                    Log::warning('LINE: slip fuzzy attempt ล้มเหลว (non-blocking)', [
                        'user_id' => $userId,
                        'reading_id' => $activeReading->id,
                        'error' => $fuzzyErr->getMessage(),
                    ]);
                }

                // 🛡️ (2026-06-04) Flood guard — คนส่งสลิป/บิลปลอมรัวๆ เกินเพดาน → หยุดยิง SlipOK ให้แอดมินตรวจ/แบน
                $floodGate = $this->conversationService->slipFloodGate('line', $userId, $activeReading, 'active_bill');
                if ($floodGate !== null) {
                    if (! empty($floodGate['message'])) {
                        $this->lineService->sendMessageWithReplyFallback($userId, $floodGate['message'], $replyToken);
                    }
                    Log::info('LINE: slip flood guard ทำงาน → หยุดเก็บ/ตรวจสลิป', [
                        'user_id' => $userId,
                        'reading_id' => $activeReading->id,
                        'action' => $floodGate['action'] ?? null,
                    ]);

                    return;
                }

                // 🧾 (2026-05-31) SlipOK fallback — SMS ยังไม่ตัด → เก็บสลิปไว้ตรวจใน 1 นาที (หรือ on-ping)
                try {
                    $slipSvc = new \App\Services\Fortune\SlipOkService($this->settings);
                    if ($slipSvc->isEnabled() && empty($activeReading->slipok_verified_at)) {
                        // LINE: ดึงรูปผ่าน content API เป็น base64 (ใช้ที่ classify ไว้แล้วถ้ามี)
                        $b64 = $cachedBase64;
                        if (empty($b64) && ! empty($messageId)) {
                            $b64 = $this->downloadLineImageAsBase64($messageId);
                        }

                        if (! empty($b64)) {
                            $stored = $this->conversationService->storeIncomingSlipFromBase64($activeReading, $b64);
                            if ($stored === \App\Services\FortuneConversationService::SLIP_STORE_OK) {
                                $this->lineService->sendMessageWithReplyFallback(
                                    $userId,
                                    "🌙 ได้รับรูปแล้วค่ะ ขอบคุณนะคะ\n\n"
                                    ."⏳ ถ้าเป็น*สลิปการโอน* ระบบกำลังตรวจสอบให้ — ถ้าโอนเข้าจริง จะตัดบิลและเริ่มดูดวงให้ภายใน 1 นาที ✨\n\n"
                                    .'💡 อยากให้เช็คทันที พิมพ์ "เช็คสถานะ" ได้เลยค่ะ',
                                    $replyToken
                                );

                                Log::info('LINE: เก็บสลิป → รอ SlipOK fallback', [
                                    'user_id' => $userId,
                                    'reading_id' => $activeReading->id,
                                ]);

                                return;
                            }

                            // 🚫 (2026-06-05, user) รูปไม่ใช่สลิป → บอกชัดให้ส่งสลิปจริง (กันเงียบ/ก่อกวน + รู้ว่าตรวจได้จริง)
                            if ($stored === \App\Services\FortuneConversationService::SLIP_STORE_NOT_SLIP) {
                                $nudge = $this->conversationService->notSlipNudgeMessage('line', $userId);
                                if ($nudge !== null) {
                                    $this->lineService->sendMessageWithReplyFallback($userId, $nudge, $replyToken);
                                }

                                Log::info('LINE: รูปไม่ใช่สลิป → บอกให้ส่งสลิปจริง (ไม่เงียบ)', [
                                    'user_id' => $userId,
                                    'reading_id' => $activeReading->id,
                                    'nudged' => $nudge !== null,
                                ]);

                                return;
                            }

                            // 💰 (2026-06-05) reading พัก HOLD (โอนขาด 3 รอบ) → ตอบ "รอแม่หมอตรวจ" (เงินไม่หาย)
                            if ($stored === \App\Services\FortuneConversationService::SLIP_STORE_HOLD) {
                                $holdResp = $this->conversationService->partialHoldResponse($userId);
                                if ($holdResp !== null && ! empty($holdResp['message'])) {
                                    $this->lineService->sendMessageWithReplyFallback($userId, $holdResp['message'], $replyToken);
                                }

                                return;
                            }
                            // SLIP_STORE_FAILED → ปล่อย fall through ไปข้อความ generic ด้านล่าง
                        }
                    }
                } catch (\Throwable $slipErr) {
                    Log::warning('LINE: SlipOK store ล้มเหลว (non-blocking)', [
                        'user_id' => $userId,
                        'error' => $slipErr->getMessage(),
                    ]);
                }

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

                // 🪄 (2026-06-11) State-aware — เคสเดียวกับ FB (FTU-260611-Z9851):
                //   รอวันเกิด/เปิดไพ่อยู่ ต้องบอกขั้นตอนจริง ไม่ใช่ "กำลังคำนวณ"
                if ($activeReading->conversation_status === FortuneReading::STATUS_COLLECTING_BIRTHDATE
                    || empty($activeReading->birth_date)) {
                    $message = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ (ไม่ต้องส่งสลิปซ้ำนะคะ)\n\n"
                        ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🪄 *ตอนนี้แม่หมอรอ วันเดือนปีเกิด ของเจ้าชะตาอยู่ค่ะ*\n"
                        ."พิมพ์บอกได้เลย เช่น:\n"
                        ."  • 15 มีนาคม 2538\n"
                        .'  • 15/3/2538';
                } elseif ($activeReading->conversation_status === FortuneReading::STATUS_COLLECTING_TAROT) {
                    $message = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ (ไม่ต้องส่งสลิปซ้ำนะคะ)\n\n"
                        ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🧘 *ตั้งจิตนึกถึงเรื่องที่อยากรู้ แล้วพิมพ์ \"พร้อม\"*\n"
                        .'🃏 แม่หมอจะเปิดไพ่อ่านพื้นดวงให้ทันทีค่ะ';
                } else {
                    $message = "✅ ระบบรับเงินไปเรียบร้อยแล้วค่ะ\n\n"
                        ."📋 บิลของเจ้าชะตา: {$billRef}\n\n"
                        ."🌙 *แม่หมอกำลังคำนวณดวงดาวให้เจ้าชะตาอยู่*\n"
                        ."ใช้เวลาประมาณ 1-3 นาที — รอสักครู่ คำทำนายจะส่งไปให้ทันทีเมื่อเสร็จ ✨\n\n"
                        .'💡 ห้ามสร้างบิลใหม่นะคะ (ป้องกันจ่ายซ้ำ)';
                }

                $this->lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

                Log::info('LINE: รับสลิประหว่าง PAID → ตอบตามขั้นตอนจริง', [
                    'user_id' => $userId,
                    'reading_id' => $activeReading->id,
                    'status' => $activeReading->conversation_status,
                    'has_birthdate' => ! empty($activeReading->birth_date),
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

            // 🧾 (2026-05-31) ไม่มีบิล active แต่เคยพยายามดู Celtic + ส่งสลิป → ตรวจ+กู้ (returning)
            try {
                if ((new \App\Services\Fortune\SlipOkService($this->settings))->isEnabled()) {
                    $b64 = $cachedBase64;
                    if (empty($b64) && ! empty($messageId)) {
                        $b64 = $this->downloadLineImageAsBase64($messageId);
                    }
                    if (! empty($b64)) {
                        $ret = $this->conversationService->handleReturningSlipImage('line', $userId, null, $b64);
                        if ($ret !== null) {
                            $this->channelManager->sendResponse(
                                FortuneChannelManager::PLATFORM_LINE,
                                $userId,
                                $ret,
                                ['reply_token' => $replyToken, 'from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']
                            );

                            Log::info('LINE: returning slip image → ตรวจ+ตอบ', [
                                'user_id' => $userId,
                                'action' => $ret['action'] ?? null,
                            ]);

                            return;
                        }
                    }
                }
            } catch (\Throwable $retErr) {
                Log::warning('LINE: returning slip image ล้มเหลว (non-blocking)', [
                    'user_id' => $userId,
                    'error' => $retErr->getMessage(),
                ]);
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
     * Strike rules (เก็บใน Cache 1 ชั่วโมง) — ⚖️ ปรับ parity กับ FB rewrite 2026-05-02 เมื่อ 2026-09-01:
     *   1. Non-text (video/audio/file — **สติกเกอร์ไม่นับ**) + ไม่มี active reading → +1 strike
     *   2. Text มี URL → +1 strike
     *   3. Text เหมือนเดิม **ตั้งแต่ครั้งที่ 3** (ซ้ำครั้งที่ 2 = คนทวนเพราะบอทช้า ไม่นับ) → +1 strike
     *   4. Rate flood > 10 ข้อความ/30 วิ → +1 strike
     *
     * เมื่อ strike >= 5 → silence **5 นาที** (เดิม 1 ชม. — โหดเกิน เคยกินลูกค้าใหม่ที่กำลังจะจ่าย)
     * ตอนลงโทษล้าง strike ทิ้งด้วย — พ้นโทษแล้วเริ่มนับใหม่
     *
     * @param  string|null  $messageType  text|image|sticker|video|audio|file|location|...
     */
    protected function isUserSpamming(string $userId, string $text, ?string $messageType): bool
    {
        $silencedKey = "fortune:spam:silenced:line:{$userId}";
        $strikeKey = "fortune:spam:strikes:line:{$userId}";
        $lastTextKey = "fortune:spam:last_text:line:{$userId}";
        $maxStrikes = 5;

        // 🚨 (2026-08-18) ลิงก์สแปม = สแปมเสมอ — คำนวณก่อนทุก bypass
        //   ต่อให้อยู่กลาง flow หรือพิมพ์คำสั่งปกติ ลิงก์ภายนอกก็ไม่ใช่พฤติกรรมลูกค้า
        //   ⚠️ ของเดิม `#https?://|www\.|\.com/|\.net/|\.online/#` ดัก main.thaiprompt.online ของตัวเอง
        //      → ลูกค้าก็อปลิงก์จ่ายเงิน/ลิงก์วอลเลตที่บอทส่งให้ กลับมาถาม = โดน strike ฟรี
        //
        //   วิธีแก้: "ลบโดเมนเราออกจากข้อความก่อน" แล้วค่อยตรวจด้วย pattern กว้างเหมือนเดิม
        //   — ไม่ใช้ negative lookahead แบบ FB เพราะ FB ดักได้แค่ลิงก์ที่มี https?:// นำหน้า
        //     ส่วนของ LINE ดักโดเมนเปล่า (`xxx.com/`) ได้ด้วย ถ้าเปลี่ยนไปตาม FB = ตรวจจับแย่ลง
        $textForUrlCheck = preg_replace(
            '#(?:https?://)?(?:www\.)?(?:main\.)?thaiprompt\.online\S*#i',
            '',
            $text
        );
        $hasSpamUrl = trim((string) $textForUrlCheck) !== ''
            && preg_match('#https?://|www\.|t\.me/|bit\.ly/|\.com/|\.net/|\.online/#i', $textForUrlCheck) === 1;

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
        //
        // 🛡️ (2026-08-18) เพิ่ม status ต้นทางของ flow — เคสจริงลูกค้า U46a1f097 เสียไป
        //   ลูกค้าใหม่เพิ่งกดติดตาม → พิมพ์ "ดูดวง" → ได้เมนูแพคเกจยาว 586 ตัวอักษร
        //   → พิมพ์ "ดูดวง" ซ้ำอีก 4 ครั้งใน 28 วินาที (นึกว่าบอทไม่ตอบ)
        //   → strike ครบ 5 → silenced 1 ชม. → พิมพ์ "99" (จะซื้อ!) บอทเงียบสนิท
        //   ตอนนั้น reading อยู่ status = tier_choice ซึ่ง "ไม่อยู่" ใน bypass list
        //   → ลูกค้ากำลังเลือกแพคเกจ = อยู่ใน flow เต็มตัว ไม่ใช่คนป่วน
        //   - TIER_CHOICE — กำลังเลือกแพคเกจ (39/99/199)
        //   - CELTIC_PENDING_PAYMENT / AWAITING_PAYMENT_METHOD — รอจ่าย
        //   - DISCOVERY_CHAT / DISCOVERY_CONFIRM — คุยเก็บข้อมูลก่อนทำนาย
        //   - AWAITING_CONFIRMATION — รอยืนยันข้อมูล
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
            \App\Models\FortuneReading::STATUS_TIER_CHOICE,
            \App\Models\FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            \App\Models\FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CHAT,
            \App\Models\FortuneReading::STATUS_DISCOVERY_CONFIRM,
            \App\Models\FortuneReading::STATUS_AWAITING_CONFIRMATION,
        ];
        try {
            $hasActiveFlow = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', $bypassStatuses)
                ->where('updated_at', '>=', now()->subHours(2))
                ->exists();

            // ⚠️ (2026-08-18) bypass ไม่ครอบลิงก์สแปม — คนป่วนเปิดบิลค้างไว้แล้วยิงลิงก์ได้ 2 ชม.
            if ($hasActiveFlow && ! $hasSpamUrl) {
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
        // ⚖️ (2026-09-01) parity FB rewrite 2026-05-02: **สติกเกอร์ไม่นับ strike** — เป็นภาษาแชท
        //   ปกติของคนไทย (FB ตัด sticker-strike ทิ้งแล้ว LINE ตกค้าง) เหลือดักเฉพาะ video/audio/file
        //   + แก้คอลัมน์: เดิมเช็คแค่ facebook_user_id — แถวที่มีแต่ platform_user_id มองไม่เห็น
        if ($messageType && ! in_array($messageType, ['text', 'image', 'sticker'], true)) {
            $hasActiveBill = \App\Models\FortuneReading::where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
                ->whereIn('conversation_status', [
                    \App\Models\FortuneReading::STATUS_PENDING_PAYMENT,
                    \App\Models\FortuneReading::STATUS_PAID,
                ])
                ->exists();

            if (! $hasActiveBill) {
                $newStrikes++;
            }
        }

        // 🚨 Strike 2: ข้อความมี URL/ลิงก์ (คำนวณไว้ข้างบน — ละเว้นโดเมนเราแล้ว)
        if ($hasSpamUrl) {
            $newStrikes++;
        }

        // 🚨 Strike 3: ข้อความเหมือนเดิม
        //
        // 🛡️ (2026-08-18) ยกเว้น "คำสั่งปกติ" — parity กับ FB (FacebookWebhookController::isUserSpamming
        //     $stateExpectedInputs มีมาตั้งแต่ 2026-05-06 commit 0ec4aa0f7 แต่ LINE ไม่เคยได้ตาม)
        //
        //   เคสจริง 2026-08-18 ลูกค้า U46a1f097 (เพิ่งกดติดตาม 10 วินาทีก่อน):
        //     14:44:02 "ดูดวง" → เมนูแพคเกจ 586 ตัวอักษร (has_quick_replies=false ไม่มีปุ่มให้กด)
        //     14:44:11/19/21/27 "ดูดวง" ซ้ำ → strike 1,2,3,4
        //     14:44:30 "ดูดวง" → strike 5 → silenced 1 ชม.
        //     14:46:35 "99" (จะซื้อแล้ว!) → บอทเงียบสนิท = เสียลูกค้าจ่ายเงินทั้งคน
        //
        //   "ดูดวง" คือคีย์เวิร์ดเปิดบทสนทนาหลักของบอทเอง — พิมพ์ซ้ำ = คนนึกว่าบอทไม่ตอบ ไม่ใช่คนป่วน
        //   ([[rule_nav_noise_never_counts_as_input]] — ตัวหนังสือบนปุ่มไหลกลับมาเป็น text ก็เข้าทางนี้)
        //
        //   ⚠️ ตัวเลขราคา 39/99 ต้องรอดด้วย ([[rule_typed_price_fasttrack_bill]] — พิมพ์เลขเอง = จะซื้อ)
        //      ครอบด้วยกฎความยาว ≤ 4 ตัวอักษร เหมือน FB
        //
        // 🌙 (2026-08-21) เลนดวงฟรีรายวันเปิดฝั่ง LINE แล้ว — คำของเลนนี้ต้องรอดด้วย
        //   กฎ fallback คือ `mb_strlen <= 4` ซึ่ง **ไม่ครอบชื่อวันไทยเลยสักวัน**
        //   ("จันทร์" 6 ตัว · "อาทิตย์" 7 ตัว · สั้นสุด "พุธ" ก็ยัง 3 ตัวรอดแค่วันเดียว)
        //   ⇒ ลูกค้าที่พิมพ์ชื่อวันเกิดซ้ำ 5 ครั้ง (เพราะบอทตอบช้า/ไม่ตอบ) โดนปิดปาก 1 ชม.
        //   และด่าน bypass ตาม status ด้านบนช่วยไม่ได้ เพราะเลนดวงรายวันคืน
        //   `'reading' => null` — ไม่มีแถว reading ให้ whereIn() เจอสักสถานะ
        //
        //   ⚠️ ใส่ "ชื่อวันล้วน" เท่านั้น — ประโยคที่มีชื่อวันปนยังนับ strike ตามปกติ
        //      (in_array เทียบเป๊ะ ไม่ใช่ substring)
        $stateExpectedInputs = [
            'พร้อม', 'ใช่', 'ไม่ใช่', 'ใช่เลย', 'ไม่', 'ตกลง', 'ok', 'OK',
            'ดูดวง', 'เริ่มถามคำถาม', 'พอแค่นี้', 'พอ', 'หยุด',
            'อ่านคำทำนาย', 'รับคำทำนาย', 'ยกเลิก', 'ดูคำทำนายล่าสุด',
            'ดวงรายวัน', 'ดูดวงรายวัน', 'เริ่มใหม่',
            // 🌙 คำขอดวงฟรีรายวัน (รวมป้ายปุ่มที่ไหลกลับมาเป็นข้อความ)
            'ดวงฟรี', 'ดูดวงฟรี', 'ดวงฟรีประจำวัน', 'รับดวงฟรีประจำวัน',
            'ดวงประจำวัน', 'ขอดวงวันนี้', 'ดูดวงวันนี้เลย',
            // 🌙 ชื่อวันเกิด 7 วัน — ทั้งแบบมี "วัน" นำหน้าและไม่มี
            'อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'พฤหัส', 'ศุกร์', 'เสาร์',
            'วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันพฤหัส', 'วันศุกร์', 'วันเสาร์',
        ];
        $normalizedText = trim($text);
        // 🌙 (2026-09-01) ปอกคำลงท้ายสุภาพก่อนเทียบ whitelist — "จันทร์ค่ะ" (7 ตัว เกิน 4)
        //   ต้องรอดเหมือน "จันทร์" (parser ฝั่งเลนรายวันปอกคำลงท้ายรับได้อยู่แล้ว ด่านนี้ต้องอดทนเท่ากัน)
        $strippedText = trim((string) preg_replace(
            '/(ครับผม|นะครับ|นะคะ|ครับ|ค้าบ|คับ|ค่ะ|คะ|จ้า|จ้ะ|จ๊ะ|น้า|นะ|ฮะ|ฮับ)+$/u',
            '',
            $normalizedText
        ));
        $isStateInput = in_array($normalizedText, $stateExpectedInputs, true)
            || ($strippedText !== '' && in_array($strippedText, $stateExpectedInputs, true))
            || mb_strlen($normalizedText) <= 4; // สั้นมาก = state input / เลขราคา (39, 99)

        if (! empty($text) && ! $isStateInput) {
            $lastText = \Illuminate\Support\Facades\Cache::get($lastTextKey);
            $repeatKey = "fortune:spam:repeat_count:line:{$userId}";
            if ($lastText === $text) {
                // ⚖️ (2026-09-01) parity FB: ซ้ำครั้งที่ 2 ยังไม่นับ (คนทวนเพราะบอทช้า/ไม่ตอบ)
                //   strike ตั้งแต่การซ้ำครั้งที่ 2 ขึ้นไป (= ข้อความเดียวกันโผล่ครั้งที่ 3)
                $repeatCount = (int) \Illuminate\Support\Facades\Cache::get($repeatKey, 0) + 1;
                \Illuminate\Support\Facades\Cache::put($repeatKey, $repeatCount, now()->addMinutes(10));
                if ($repeatCount >= 2) {
                    $newStrikes++;
                }
            } else {
                \Illuminate\Support\Facades\Cache::forget($repeatKey);
            }
            \Illuminate\Support\Facades\Cache::put($lastTextKey, $text, now()->addMinutes(10));
        }

        // 🚨 (2026-08-18) Strike 4: RATE FLOOD — parity กับ FB Rule 1
        //   ต้องมีตัวนี้เพราะ whitelist ข้างบนเปิดช่องให้พิมพ์ "ดูดวง" รัวได้ไม่จำกัด
        //   flood จริงดูที่ "ความถี่" ไม่ใช่ "เนื้อความ" — > 10 ข้อความใน 30 วินาที = ตั้งใจป่วน
        $rateKey = "fortune:spam:rate:line:{$userId}";
        $now = time();
        $rateLog = array_values(array_filter(
            (array) \Illuminate\Support\Facades\Cache::get($rateKey, []),
            fn ($t) => ($now - (int) $t) < 30
        ));
        $rateLog[] = $now;
        \Illuminate\Support\Facades\Cache::put($rateKey, $rateLog, now()->addMinute());
        if (count($rateLog) > 10) {
            $newStrikes++;
        }

        if ($newStrikes === 0) {
            return false;
        }

        $totalStrikes = $strikes + $newStrikes;
        \Illuminate\Support\Facades\Cache::put($strikeKey, $totalStrikes, now()->addHour());

        if ($totalStrikes >= $maxStrikes) {
            // ⚖️ (2026-09-01) parity FB rewrite 2026-05-02: โทษปิดปาก **5 นาที** ไม่ใช่ 1 ชม.
            //   (เคสจริง 2026-08-18: ลูกค้าใหม่โดน 1 ชม. ตอนกำลังจะจ่าย 99฿ — FB โดนแค่ 5 นาที)
            //   + ล้าง strike ตอนลงโทษ — ไม่งั้นพ้นโทษแล้ว strike เก่ายังค้างทั้งชั่วโมง
            //   พลาดอีกครั้งเดียวโดนปิดปากซ้ำทันที
            \Illuminate\Support\Facades\Cache::put($silencedKey, true, now()->addMinutes(5));
            \Illuminate\Support\Facades\Cache::forget($strikeKey);
            Log::warning('🚫 LINE Fortune spam guard: silenced user for 5 minutes', [
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

    /**
     * 📦 (2026-08-26) ส่งบทสรุป Celtic 99฿ (Grand Finale) ที่ push ไม่ออก คืนผ่าน reply
     *
     * 🔴 บั๊กเก่าที่อยู่มานาน: บทสรุปไม่เคยถูกเก็บลง DB (มีแต่รูป `celtic_summary_image_url`)
     *    ส่งไม่ออกเมื่อไหร่ = หายถาวร ลูกค้าจ่าย 99฿ แล้วไม่ได้ของ
     *    ตอนนี้ `endCelticSession()` เก็บ `celtic_finale_text` ไว้ก่อนส่งแล้ว → กู้ได้
     *
     * เงื่อนไขเข้มโดยตั้งใจ — ส่งซ้ำเฉพาะเคสที่ "รู้แน่ว่าส่งไม่ออก":
     *   celtic_summary_delivered === false (ตั้งโดย markCelticSummaryDelivery ตามผลส่งจริง)
     * ⚠️ ห้ามใช้ `!== true` เด็ดขาด — reading เก่าก่อนมีธงนี้จะถูกยิงซ้ำทั้งกอง
     *
     * ครอบทั้ง Celtic ปกติและคุณไสย (ใช้ endCelticSession ร่วมกัน)
     *
     * @return bool true = ใช้ replyToken ไปแล้ว
     */
    protected function flushParkedCelticSummary(string $userId, string $replyToken): bool
    {
        try {
            // ⚠️ ห้ามหยิบ "บิลล่าสุด" มาเช็ค — ลูกค้าอาจซื้อ Deep ต่อหลัง Celtic ที่บทสรุปหาย
            //   แล้วบิลล่าสุดจะกลายเป็น Deep ⇒ บทสรุป Celtic ไม่มีวันถูกกู้
            //   ค้นตรงจากธงบน JSON แทน (MySQL รองรับ path query)
            $reading = FortuneReading::query()
                ->where('platform', 'line')
                // 🩹 (2026-09-01) OR facebook_user_id — pattern เดียวกับทั้งเลน (แถวที่
                //   platform_user_id ยังไม่ถูก stamp จะมองไม่เห็นจาก flush ทั้งที่ cron เห็น)
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->where('is_paid', true)
                ->where('created_at', '>=', now()->subDays(3))
                ->where('conversation_state->celtic_summary_delivered', false)
                ->latest()
                ->first();

            if (! $reading) {
                return false;
            }

            if ($reading->getConversationState('celtic_finale_replayed', false)) {
                return false;
            }

            $text = trim((string) $reading->getConversationState('celtic_finale_text', ''));

            if ($text === '') {
                return false;
            }

            // เนื้อหาสำคัญกว่ารูป → ใส่ข้อความก่อน แล้วค่อยเติมรูปถ้ายังไม่ครบ 5 objects
            $messages = [];
            foreach ($this->lineService->splitTextForFlexPublic($text, 4500) as $chunk) {
                $messages[] = ['type' => 'text', 'text' => $chunk];
            }
            $messages = array_slice($messages, 0, 5);

            foreach (['celtic_finale_chart_url', 'celtic_finale_image_url'] as $key) {
                if (count($messages) >= 5) {
                    break;
                }
                $url = (string) $reading->getConversationState($key, '');
                if ($url !== '') {
                    $messages[] = ['type' => 'image', 'originalContentUrl' => $url, 'previewImageUrl' => $url];
                }
            }

            $ok = $this->lineService->replyMessage($replyToken, $messages);

            if (! $ok) {
                Log::warning('LINE parked celtic summary: reply ล้มเหลว — คงค้างไว้ให้รอบหน้า', [
                    'user_id' => $userId,
                    'reading_id' => $reading->id,
                ]);

                return false;
            }

            // ✅ mark หลังส่งสำเร็จจริงเท่านั้น
            $reading->setConversationState('celtic_summary_delivered', true);
            $reading->setConversationState('celtic_finale_replayed', true);
            $reading->setConversationState('celtic_finale_replayed_at', now()->toIso8601String());

            Log::info('📦 LINE parked celtic summary: ส่งบทสรุป 99฿ ที่ push ไม่ออก คืนลูกค้าผ่าน reply สำเร็จ', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'objects' => count($messages),
                'text_len' => mb_strlen($text),
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('LINE parked celtic summary: exception (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 📦 (2026-08-26) ส่งคำทำนาย Deep ที่จ่ายเงินแล้วแต่ push ไม่ออก คืนผ่าน reply (ฟรี)
     *
     * 🔴 เคสจริงที่ทำให้ต้องมีเมธอดนี้ (บิล FTU-260826-G5544, 39฿):
     *    ลูกค้าพิมพ์ "พร้อม" หลายรอบ ระบบเข้า `view_reading_deep` ทุกรอบจริง
     *    แต่ log ฟ้อง `has_reply_token: false` ทุกครั้ง — เพราะเส้นส่งคำทำนายวิ่งผ่าน
     *    job/debounce (ไม่ใช่จังหวะ webhook) ⇒ ไม่มี replyToken ⇒ ตกไป push ⇒ 429 ⇒ ตายวนลูป
     *
     * เมธอดนี้ดักที่ "จังหวะ webhook" ซึ่งเป็นที่เดียวที่ replyToken ยังสด แล้วยิงตรงเลย
     * ไม่ผ่าน job — ตัดปัญหา token หายระหว่างทาง
     *
     * @return bool true = ใช้ replyToken ไปแล้ว
     */
    protected function flushParkedDeepReading(string $userId, string $replyToken): bool
    {
        try {
            $reading = FortuneReading::query()
                ->where('platform', 'line')
                // 🩹 (2026-09-01) OR facebook_user_id — pattern เดียวกับทั้งเลน
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->where('is_paid', true)
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->whereNotNull('deep_response')
                ->where('deep_response', '!=', '')
                ->where('created_at', '>=', now()->subDays(3))
                ->latest()
                ->first();

            if (! $reading) {
                return false;
            }

            // ส่งไปแล้ว → ไม่ต้องส่งซ้ำ (กันลูกค้าเห็นคำทำนายเบิ้ล)
            if ($reading->getConversationState('reading_sent_directly', false)) {
                return false;
            }

            // 🔒 เส้น push กำลังถือ lock ส่งอยู่ → อย่าชิงส่งซ้อน (เหตุผลเดียวกับ deliverInFlight เดิม)
            if (\Illuminate\Support\Facades\Cache::has("fortune:deep_deliver:{$reading->id}")) {
                return false;
            }

            $name = $reading->facebook_user_name ?: 'คุณ';
            $header = "🌟 คำทำนายเชิงลึกของ{$name}\n"
                .'📋 เลขที่บิล: '.($reading->bill_reference ?? '-')."\n"
                .'📅 '.$reading->created_at->format('d/m/Y H:i')."\n"
                .'═══════════════════════';

            // LINE: 1 reply = 5 objects, กล่องละ ≤5000 ตัวอักษร → header + เนื้อหา ≤4 ก้อน
            $chunks = $this->lineService->splitTextForFlexPublic((string) $reading->deep_response, 4500);
            $messages = [['type' => 'text', 'text' => $header]];

            foreach (array_slice($chunks, 0, 4) as $chunk) {
                $messages[] = ['type' => 'text', 'text' => $chunk];
            }

            $ok = $this->lineService->replyMessage($replyToken, $messages);

            if (! $ok) {
                Log::warning('LINE parked deep: reply ล้มเหลว — คงค้างไว้ให้รอบหน้า', [
                    'user_id' => $userId,
                    'reading_id' => $reading->id,
                ]);

                return false;
            }

            // ✅ mark หลังส่งสำเร็จจริงเท่านั้น
            $reading->setConversationState('reading_sent_directly', true);
            $reading->setConversationState('reading_notification_sent', true);
            $reading->setConversationState('delivered_by_reply_message', true);
            $reading->setConversationState('reading_ready_sent', true);
            $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

            Log::info('📦 LINE parked deep: ส่งคำทำนายที่ push ไม่ออก คืนลูกค้าผ่าน reply สำเร็จ (ฟรี)', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'chunks' => count($messages),
                'response_len' => mb_strlen((string) $reading->deep_response),
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('LINE parked deep: exception (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 📦 (2026-08-26) ส่งคำตอบ Celtic ที่ค้างอยู่คืนลูกค้าผ่าน reply (ฟรี ไม่กิน push quota)
     *
     * ใช้ตอนที่ลูกค้าทักเข้ามา — เป็นจังหวะเดียวที่มี replyToken สด
     * ของค้าง = คำถามที่ AI ตอบแล้ว (`answered_at`) แต่ push ไม่ออก (`delivered_at` = null)
     * ซึ่งเกิดจากโควต้า push รายเดือนหมด หรือ LINE API ล่มชั่วคราว
     *
     * ⚠️ mark delivered เฉพาะตอน reply สำเร็จจริงเท่านั้น — ห้าม mark ล่วงหน้า
     *    (บทเรียน FortuneCelticRedeliver: mark ทิ้งไว้แล้วของหายกลายเป็น "ส่งแล้ว")
     *
     * @param  string  $userId  LINE userId
     * @param  string  $replyToken  reply token สดจาก webhook
     * @return bool true = ใช้ replyToken ไปแล้ว (caller ต้องเลิกใช้ token นี้)
     */
    protected function flushParkedCelticAnswers(string $userId, string $replyToken): bool
    {
        try {
            // หา reading ของลูกค้าคนนี้ที่จ่ายเงินแล้ว + มีคำตอบค้างส่ง
            //   จำกัด 3 วันล่าสุด — ของเก่ากว่านั้นส่งไปลูกค้าก็งงแล้ว (cron/แอดมินจัดการแทน)
            $readingIds = FortuneReading::query()
                ->where('platform', 'line')
                // 🩹 (2026-09-01) OR facebook_user_id — pattern เดียวกับทั้งเลน
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->where('is_paid', true)
                ->where('created_at', '>=', now()->subDays(3))
                ->pluck('id');

            if ($readingIds->isEmpty()) {
                return false;
            }

            $pending = \App\Models\FortuneCelticQuestion::query()
                ->whereIn('fortune_reading_id', $readingIds)
                ->undelivered()
                ->orderBy('fortune_reading_id')
                ->orderBy('sequence')
                ->limit(4)   // เหลือ 1 slot ให้หมายเหตุ (LINE reply ได้สูงสุด 5 objects/call)
                ->get();

            if ($pending->isEmpty()) {
                return false;
            }

            // 🏷️ (2026-08-31) ติดป้ายว่าคำตอบนี้ตอบคำถามข้อไหน
            //   เส้นนี้ทำงานตอน push ตาย ⇒ คำตอบข้อ N ถูกส่งตอนลูกค้าถามข้อ N+1 (off-by-one ถาวร)
            //   เดิมส่ง `$q->response` เปล่าๆ ⇒ ลูกค้าอ่านคำตอบเรื่องเงิน ต่อจากคำถามเรื่องแฟน = งง
            //   ป้ายอยู่ในข้อความเดิม **ไม่กิน object เพิ่ม** (LINE คิดเงินต่อ call ไม่ใช่ต่อกล่อง)
            $messages = [];
            foreach ($pending as $q) {
                $asked = trim((string) $q->question);
                $label = $asked !== ''
                    ? '↩️ ตอบคำถาม: «'.mb_substr($asked, 0, 60).(mb_strlen($asked) > 60 ? '…' : '')."»\n\n"
                    : '';

                $messages[] = [
                    'type' => 'text',
                    'text' => mb_substr($label.trim((string) $q->response), 0, 4900),
                ];
            }

            // โควต้าหมด = ที่เหลือของเทิร์นนี้ตอบไม่ได้ → บอกลูกค้าว่าข้อความถึงแล้ว จะได้ไม่คิดว่าโดนเท
            if (LineGatekeeperService::isQuotaExhausted()) {
                $messages[] = [
                    'type' => 'text',
                    'text' => "💬 ข้อความของเธอถึงแม่หมอแล้วนะคะ ✨\n\nแม่หมอกำลังดูให้อยู่ค่ะ — ทักมาอีกครั้งได้เลย เดี๋ยวแม่หมอส่งคำตอบให้ทันทีค่ะ 🙏",
                ];
            } elseif (count($messages) < 5) {
                // 🔢 (2026-08-31) กล่องคำถามแนะนำของคำตอบล่าสุด — เดิมตกหล่นในเส้นนี้ทั้งกล่อง
                //   เพราะ flush ส่งแค่ `$q->response` ⇒ ลูกค้าฝั่ง LINE แทบไม่เคยเห็นปุ่มถามต่อเลย
                //   (ต้องมีคอลัมน์เก็บก่อน — migration 2026_08_31_000100)
                $lastBox = trim((string) ($pending->last()->suggestion_box ?? ''));
                if ($lastBox !== '') {
                    $messages[] = ['type' => 'text', 'text' => mb_substr($lastBox, 0, 4900)];
                }
            }

            // 🔘 ปุ่มเลข 1️⃣2️⃣ ของคำตอบล่าสุด — เกาะกล่องสุดท้าย **ไม่กิน object เพิ่ม**
            //   ⚠️ LINE โชว์ quickReply เฉพาะกล่องล่าสุดของ call อยู่แล้ว → ต้องเกาะตัวท้ายเสมอ
            //   ⚠️ ใช้ `buildTextObject()` (public) ประกอบใหม่ — `buildQuickReplyItems()` เป็น
            //      protected เรียกจากตรงนี้ไม่ได้ (.claude/LINE_MESSAGING_RULES.md กฎข้อ 9)
            $lastButtons = $pending->last()->suggestion_quick_replies ?? null;
            if (! empty($lastButtons) && is_array($lastButtons) && ! empty($messages)) {
                $lastIdx = count($messages) - 1;
                $messages[$lastIdx] = $this->lineService->buildTextObject(
                    (string) ($messages[$lastIdx]['text'] ?? ''),
                    $lastButtons
                );
            }

            $ok = $this->lineService->replyMessage($replyToken, $messages);

            if (! $ok) {
                Log::warning('LINE parked delivery: reply ล้มเหลว — คงสถานะค้างไว้ให้รอบหน้า', [
                    'user_id' => $userId,
                    'pending_count' => $pending->count(),
                ]);

                return false;
            }

            foreach ($pending as $q) {
                $q->markDelivered();
            }

            Log::info('📦 LINE parked delivery: ส่งคำตอบค้างคืนลูกค้าผ่าน reply สำเร็จ (ฟรี)', [
                'user_id' => $userId,
                'delivered_question_ids' => $pending->pluck('id')->all(),
                'reading_ids' => $pending->pluck('fortune_reading_id')->unique()->values()->all(),
                'quota_exhausted' => LineGatekeeperService::isQuotaExhausted(),
            ]);

            return true;

        } catch (\Throwable $e) {
            // ห้ามให้ตรงนี้ล้มทั้ง webhook — ของค้างยังอยู่ รอบหน้าค่อยลองใหม่
            Log::error('LINE parked delivery: exception (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
