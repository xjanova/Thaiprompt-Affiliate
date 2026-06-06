<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\PaymentBankAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Channel Manager
 *
 * จัดการหลายช่องทาง (Multi-Channel) สำหรับระบบดูดวง
 * รองรับ: Facebook Messenger, LINE Official Account, และช่องทางอื่นในอนาคต
 *
 * Features:
 * - Auto-detect platform จาก user ID format หรือ webhook
 * - ส่งข้อความตอบกลับผ่าน platform ที่เหมาะสม
 * - รองรับ Rich Message (Flex Message, Templates) ตาม platform
 */
class FortuneChannelManager
{
    protected FortuneTellingSetting $settings;

    protected FortuneConversationService $conversationService;

    protected FortuneAIService $aiService;

    /**
     * FortuneTakeoverService — เช็คสถานะเทคโอเวอร์ (defense in depth)
     */
    protected FortuneTakeoverService $takeoverService;

    /**
     * Platform instances cache
     */
    protected array $platforms = [];

    /**
     * Supported platforms
     */
    public const PLATFORM_FACEBOOK = 'facebook';

    public const PLATFORM_LINE = 'line';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->conversationService = new FortuneConversationService($this->settings);
        $this->aiService = new FortuneAIService($this->settings);
        $this->takeoverService = app(FortuneTakeoverService::class);
    }

    /**
     * ดึงราคาดูดวงจากการตั้งค่าระบบ
     *
     * ลำดับ: deep_reading_price → reading_price → DEEP_READING_PRICE constant
     *
     * @return float ราคาดูดวง (บาท)
     */
    protected function getReadingPrice(): float
    {
        $deepPrice = (float) ($this->settings->deep_reading_price ?? 0);
        if ($deepPrice > 0) {
            return $deepPrice;
        }

        $readingPrice = (float) ($this->settings->reading_price ?? 0);
        if ($readingPrice > 0) {
            return $readingPrice;
        }

        return FortuneConversationService::DEEP_READING_PRICE;
    }

    /**
     * ดึง platform instance
     *
     * @param  string  $platform  ชื่อ platform (facebook, line)
     */
    public function getPlatform(string $platform): ?MessagingPlatformInterface
    {
        if (isset($this->platforms[$platform])) {
            return $this->platforms[$platform];
        }

        $instance = match ($platform) {
            self::PLATFORM_FACEBOOK => new FacebookWebhookService($this->settings),
            self::PLATFORM_LINE => new LineFortuneService($this->settings),
            default => null,
        };

        if ($instance) {
            $this->platforms[$platform] = $instance;
        }

        return $instance;
    }

    /**
     * ประมวลผลข้อความจากทุก platform
     *
     * @param  string  $platform  ชื่อ platform
     * @param  string  $userId  User ID ของ platform นั้น
     * @param  string  $messageText  ข้อความ
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้ (optional)
     * @param  array  $extra  ข้อมูลเพิ่มเติม (reply_token สำหรับ LINE, etc.)
     * @return array ผลลัพธ์
     */
    public function processMessage(
        string $platform,
        string $userId,
        string $messageText,
        ?array $userProfile = null,
        array $extra = []
    ): array {
        // บันทึก platform ลงใน context
        $contextUserId = "{$platform}:{$userId}";

        // 🛑 Defense-in-depth: เช็ค takeover ก่อนเรียก AI ทุกครั้ง
        // (controller ควรเช็คแล้ว แต่ถ้าหลุดมา ให้เงียบ)
        //
        // 🚨 (2026-05-17) FLOW BYPASS — ใช้ shouldBypassTakeover เหมือน controller
        //   $extra['is_explicit_action'] = true ถ้ามาจาก postback/quick reply
        if ($this->takeoverService->isActiveByPlatform($platform, $userId)) {
            $isExplicit = ! empty($extra['is_explicit_action']);
            if (! $this->takeoverService->shouldBypassTakeover($platform, $userId, $messageText, $isExplicit)) {
                Log::info('🛑 ChannelManager: ข้ามข้อความ (/aistop active, ไม่มี flow ที่ต้อง bypass)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'message_preview' => mb_substr($messageText, 0, 50),
                ]);

                return [
                    'action' => 'skipped_takeover',
                    'platform' => $platform,
                    'user_id' => $userId,
                    'reading' => null,
                ];
            }

            Log::info('💰 ChannelManager: /aistop active แต่ bypass — ดำเนิน flow ต่อ', [
                'platform' => $platform,
                'user_id' => $userId,
            ]);
        }

        // ดึงโปรไฟล์ถ้ายังไม่มี
        if (empty($userProfile)) {
            $platformService = $this->getPlatform($platform);
            if ($platformService) {
                $userProfile = $platformService->getUserProfile($userId);
            }
        }

        // 👤 Enrich profile name ก่อนส่งให้ conversationService (กันชื่อหายระหว่าง flow)
        //   เคสที่เกิด: getUserProfile() คืน {'name' => 'คุณ'} (FB API fail / token expired)
        //   → reading ถูกสร้างด้วย name='คุณ' → ค้างที่ 'คุณ' ตลอด flow
        //   Fallback chain: profile → FortuneUserCredit → historical FortuneReading
        if (empty($userProfile['name']) || $userProfile['name'] === 'คุณ') {
            $resolvedName = $this->resolveUserName($platform, $userId);
            if (! empty($resolvedName) && $resolvedName !== 'คุณ') {
                $userProfile = is_array($userProfile) ? $userProfile : [];
                $userProfile['name'] = $resolvedName;
            }
        }

        // ✅ ตั้ง platform ก่อน processMessage เพื่อให้ saveQuestionForAdmin() เก็บค่าถูก
        $this->conversationService->setPlatform($platform);

        // 🌐 (2026-05-03) Resolve locale — รองรับลาว auto-detect ทั้ง FB & LINE
        //   - manual choice (picker) ชนะเสมอ
        //   - FB: detect จาก messageText + profileName (ชื่อช่วย boost confidence)
        //   - LINE: detect จาก profileName เท่านั้น (ชื่อลาวล้วน → 'lo')
        //   - ถ้า DB error → fallback 'th' (safe)
        //
        // 🚦 (2026-05-06) ระหว่าง active reading — ใช้ locale ที่ stored ไว้แล้วเท่านั้น
        //   ห้ามรัน text-detect (ทำให้ flip locale กลางคันถ้าลูกค้าพิมพ์อังกฤษ/ผสมไทย-ลาว)
        //   ลูกค้าบอก: "เอา Lao detect ออกระหว่างการทำนาย"
        $profileName = $userProfile['name'] ?? null;
        $hasActive = false;
        try {
            $hasActive = FortuneReading::hasActiveReading($platform, $userId);
        } catch (\Throwable $e) {
            // ถ้าเช็คไม่ได้ → ใช้ behaviour เดิม (run detect)
        }

        if ($hasActive) {
            // ใช้ locale ที่ stored ไว้แล้วเท่านั้น — ห้าม detect จาก text
            $locale = FortuneLocaleService::getStored($platform, $userId) ?? 'th';
        } else {
            $locale = FortuneLocaleService::resolveForMessage(
                $platform,
                $userId,
                $messageText,
                $profileName,
            );
        }
        FortuneLocaleService::setCurrent($locale);

        // ใช้ conversation service ประมวลผล
        $result = $this->conversationService->processMessage($userId, $messageText, $userProfile);

        // เพิ่ม platform info + ชื่อผู้ใช้ (ให้ handler ใช้ fallback ได้)
        $result['platform'] = $platform;
        $result['user_id'] = $userId;
        // 🛠️ (2026-05-01) ใช้ ?: เพื่อ fallback empty string → 'คุณ' (?? ไม่ catch '')
        $result['user_name'] = ($result['reading']?->facebook_user_name ?: null)
            ?? ($userProfile['name'] ?: null)
            ?? 'คุณ';

        // อัพเดท reading ด้วย platform info
        if ($result['reading'] instanceof FortuneReading) {
            $result['reading']->update([
                'platform' => $platform,
                'platform_user_id' => $userId,
            ]);
        }

        // 💰 แทรกข้อความแจ้งรายได้ค่าแนะนำที่ยังไม่ได้บอก (FB 24h-safe — ใช้ replyMessage ฟรี)
        // เรียกหลัง processMessage เพื่อให้ user_id ถูก resolve แล้ว
        $this->prependPendingCommissionNotification($result, $platform, $userId);

        // ส่งข้อความตอบกลับ
        $this->sendResponse($platform, $userId, $result, $extra);

        return $result;
    }

    /**
     * แทรกข้อความ "💰 มีรายได้ค่าแนะนำ X บาท" หน้า message ปกติ
     *
     * Trigger: user ทักแชทมา → ส่ง notification ฟรีผ่าน replyMessage
     * (กฎ FB: bot ตอบฟรีภายใน 24h หลัง user ส่งข้อความ — ใช้ window นี้ได้)
     *
     * Skip ถ้า:
     * - หา user_id (DB) ไม่ได้ (ลูกค้าใหม่/guest)
     * - ไม่มี pending commission (notify ครบแล้ว)
     * - action = 'skipped_takeover' / 'dedup_skip' (ไม่มี reply)
     */
    protected function prependPendingCommissionNotification(array &$result, string $platform, string $userId): void
    {
        $action = $result['action'] ?? '';
        if (in_array($action, ['skipped_takeover', 'dedup_skip', 'smart_skip', 'silent_skip', 'silent_warning'], true)) {
            return;
        }

        try {
            // หา user DB id จาก platform_user_id
            $dbUserId = $this->resolveDbUserId($platform, $userId, $result['reading'] ?? null);
            if (! $dbUserId) {
                return;
            }

            $commissionService = app(FortuneCommissionService::class);
            $note = $commissionService->buildPendingChatNotification($dbUserId);

            if ($note) {
                $original = $result['message'] ?? '';
                $result['message'] = $note.($original !== '' ? "\n\n─────\n\n".$original : '');
                $result['has_commission_notification'] = true;
            }
        } catch (\Throwable $e) {
            Log::warning('FortuneChannelManager: แทรก commission notification ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * หา DB user_id จาก platform_user_id
     *
     * Order:
     * 1. ใช้ reading->user_id ถ้ามี (เร็วสุด)
     * 2. ค้นจาก User table ตาม platform column (line_user_id / facebook_user_id)
     * 3. ค้นจาก FortuneReading ที่ link user แล้ว
     */
    protected function resolveDbUserId(string $platform, string $platformUserId, $reading): ?int
    {
        // 1. reading มี user_id แล้ว
        if ($reading instanceof FortuneReading && $reading->user_id) {
            return (int) $reading->user_id;
        }

        // 2. หาจาก User table
        $userQuery = User::query();
        if ($platform === 'line') {
            $userQuery->where('line_user_id', $platformUserId);
        } else {
            $userQuery->where('facebook_user_id', $platformUserId);
        }
        $user = $userQuery->first(['id']);
        if ($user) {
            return (int) $user->id;
        }

        // 3. หาจาก FortuneReading ล่าสุดของ platform_user_id ที่มี user_id
        $linked = FortuneReading::where(function ($q) use ($platformUserId) {
            $q->where('platform_user_id', $platformUserId)
                ->orWhere('facebook_user_id', $platformUserId);
        })
            ->whereNotNull('user_id')
            ->latest('updated_at')
            ->first(['user_id']);

        return $linked?->user_id ? (int) $linked->user_id : null;
    }

    /**
     * 👤 Resolve user name จาก persistent sources
     *
     * Fallback chain (same logic as FortuneReading::resolveCustomerName):
     *   1. FortuneUserCredit ของ user คนนี้
     *   2. FortuneReading เก่าๆ ของ user เดียวกันที่มีชื่อจริง
     *
     * @return string|null null = หาไม่เจอ — caller ใช้ fallback อื่น
     */
    protected function resolveUserName(string $platform, string $userId): ?string
    {
        // 1. FortuneUserCredit
        try {
            $credit = FortuneUserCredit::findByUser($userId, $platform);
            if ($credit && $this->isHumanLikeName($credit->facebook_user_name)) {
                return $credit->facebook_user_name;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2. Historical FortuneReading — scan top 10 ล่าสุด เลือกอันที่เป็นชื่อคนจริง
        try {
            $candidates = FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
                ->whereNotNull('facebook_user_name')
                ->where('facebook_user_name', '!=', '')
                ->latest('updated_at')
                ->limit(10)
                ->pluck('facebook_user_name');

            foreach ($candidates as $candidate) {
                if ($this->isHumanLikeName($candidate)) {
                    return $candidate;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * ตรวจว่าค่าที่ได้ดูเป็นชื่อคนจริง (กัน code pattern "FACEBOOK-494919" หลุดเข้า flow)
     */
    protected function isHumanLikeName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if ($name === '' || $name === 'คุณ' || $name === 'ลูกค้า' || $name === 'เจ้าชะตา') {
            return false;
        }
        if (preg_match('/^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$/i', $name)) {
            return false;
        }
        if (preg_match('/^U[0-9a-f]{32}$/i', $name)) {
            return false;
        }
        if (preg_match('/^\d{15,}$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * ส่งข้อความตอบกลับตาม platform
     *
     * @param  array  $result  ผลลัพธ์จาก conversation service
     * @param  array  $extra  ข้อมูลเพิ่มเติม
     */
    public function sendResponse(string $platform, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';

        // ✅ Silent skip actions — ข้ามเงียบๆ ไม่ส่งข้อความตอบใดๆ
        // 🩹 (2026-05-08) เพิ่ม smart_skip / silent_skip / silent_warning
        //   เดิม fall through ไป default ทำให้ส่ง "ระบบกำลังดำเนินการ 🙏" — ตรงข้ามกับ intent
        // 🛡️ (2026-06-04) slip_flood_silent / slip_flood_banned = flood guard เกินเพดานรอบถัดไป/แบน → เงียบ
        //   (กัน VerifySlipFallbackJob / on-ping ส่ง "ระบบกำลังดำเนินการ" ตอน message ว่าง — flooder ไม่ควรได้ตอบ)
        if (in_array($action, ['dedup_skip', 'smart_skip', 'silent_skip', 'silent_warning', 'slip_flood_silent', 'slip_flood_banned'], true)) {
            // silent_warning อาจมี message ที่ต้องส่ง 1 ครั้ง — แยก case
            if ($action === 'silent_warning' && ! empty($message)) {
                $platformService = $this->getPlatform($platform);
                if ($platformService instanceof FacebookWebhookService) {
                    $platformService->sendMessage($userId, $message);
                }
            }

            return true;
        }

        Log::info('FortuneChannelManager: sendResponse เริ่มส่ง', [
            'platform' => $platform,
            'user_id' => $userId,
            'action' => $action,
            'message_length' => mb_strlen($message),
            'has_quick_replies' => ! empty($result['show_quick_replies']),
            'from_admin' => ! empty($extra['from_admin']),
        ]);

        $platformService = $this->getPlatform($platform);
        if (! $platformService) {
            Log::error('FortuneChannelManager: Platform not found', ['platform' => $platform]);

            return false;
        }

        // สำหรับ LINE ใช้ Flex Message ที่สวยงาม
        if ($platform === self::PLATFORM_LINE && $platformService instanceof LineFortuneService) {
            return $this->sendLineResponse($platformService, $userId, $result, $extra);
        }

        // สำหรับ Facebook ใช้ Button/Generic Template ที่สวยงาม
        if ($platform === self::PLATFORM_FACEBOOK && $platformService instanceof FacebookWebhookService) {
            return $this->sendFacebookResponse($platformService, $userId, $result, $extra);
        }

        // สำหรับ platform อื่นๆ ส่งข้อความธรรมดา
        $options = [];

        // ถ้าเป็นการส่งจาก admin/ระบบอัตโนมัติ → ส่งผ่าน from_admin flag
        // FacebookWebhookService จะลอง RESPONSE ก่อน แล้ว fallback เป็น MESSAGE_TAG
        if (! empty($extra['from_admin'])) {
            $options['from_admin'] = true;
        }

        // ถ้ากำหนด message_tag มา → ส่งต่อให้ FacebookWebhookService
        // ✅ POST_PURCHASE_UPDATE = update หลังชำระเงิน (ไม่ต้องขออนุมัติ Facebook)
        // ⚠️ HUMAN_AGENT = ต้องได้รับอนุมัติจาก Facebook ก่อนใช้
        if (! empty($extra['message_tag'])) {
            $options['message_tag'] = $extra['message_tag'];
        }

        // ส่ง Birth Chart / Quick Chart ก่อนข้อความทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $platformService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send chart image', [
                    'platform' => $platform,
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // เพิ่ม quick replies ถ้ามี (ใช้ค่าจาก result ถ้ามี, fallback เป็น getQuickReplies)
        if (! empty($result['show_quick_replies'])) {
            $options['quick_replies'] = ! empty($result['quick_replies'])
                ? $result['quick_replies']
                : $this->getQuickReplies($action);
        }

        $sent = $platformService->sendMessage($userId, $message, $options);

        Log::info('FortuneChannelManager: sendResponse ผลลัพธ์', [
            'platform' => $platform,
            'user_id' => $userId,
            'action' => $action,
            'sent' => $sent,
            'has_quick_replies' => ! empty($options['quick_replies']),
        ]);

        return $sent;
    }

    /**
     * ส่ง Response สำหรับ Facebook ด้วย Button/Generic Template
     *
     * ใช้ FacebookRichMessageService สร้าง templates แล้วส่งผ่าน
     * FacebookWebhookService.sendButtonTemplate()
     *
     * ⚠️ ไม่แตะ SMS Payment / UniquePaymentAmount / confirmPayment()
     * เปลี่ยนแค่วิธี display ข้อมูล (จาก text → Button Template)
     */
    protected function sendFacebookResponse(FacebookWebhookService $fbService, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;

        // 🔔 (2026-05-03 v2) Apply payment warning prefix if set by PayFirstGateTrait
        //    ยกเว้น actions ที่เกี่ยวกับ payment โดยตรง (ไม่ต้องเตือนซ้ำ)
        $message = $this->maybeApplyPaymentWarning($message, $action);

        // 🩹 (2026-05-09) Strip markdown asterisks/underscores — FB plain text ไม่ render
        //    *เน้น* / _italic_ จะโชว์ raw → ดูไม่เป็นมืออาชีพ (โดยเฉพาะ gen_processing_announce
        //    หลังลูกค้าจ่ายเงิน + 8 จุดอื่นที่มี markdown ใน FortuneConversationService)
        $message = $this->stripMessengerMarkdown($message);

        Log::info('Facebook sendFacebookResponse: เริ่มจัดการ action', [
            'action' => $action,
            'user_id' => $userId,
            'reading_id' => $reading?->id ?? null,
        ]);

        $richService = new FacebookRichMessageService($this->settings);
        // อัพเดท message ใน result array ด้วย (handlers อ่านจาก $result['message'])
        $result['message'] = $message;

        try {
            $sent = match ($action) {
                // ทำนายพื้นฐานเสร็จ → ส่งคำทำนาย + Upsell Template
                'basic_done' => $this->sendFacebookBasicDoneResponse($fbService, $richService, $userId, $result),

                // รอชำระเงิน → ส่ง Payment Template + QR
                'pending_payment' => $this->sendFacebookPaymentResponse($fbService, $richService, $userId, $result),

                // ทำนายละเอียดเสร็จ → ส่งคำทำนาย + LINE invite + affiliate
                'completed' => $this->sendFacebookCompletedResponse($fbService, $richService, $userId, $result),

                // ยืนยันดูดวง → Quick Replies เลือกหมวด
                // Facebook awaiting_confirmation — ถ้าปิดบริการฟรี (show_quick_replies=false) → ส่งแค่ text
                // ป้องกันดันลูกค้าเข้า free flow ผ่านปุ่ม category
                'awaiting_confirmation' => ($result['show_quick_replies'] ?? true)
                    ? $this->sendFacebookWithQuickReplies($fbService, $richService, $userId, $message, $action)
                    : $fbService->sendMessage($userId, $message),

                // ขอวันเกิด → Birthdate prompt Template
                'collecting_birthdate' => $this->sendFacebookBirthdateResponse($fbService, $richService, $userId, $result),

                // เลือกคำถาม → Quick Replies (+ ส่งรูปไพ่ยิปซีก่อนถ้ามี)
                'collecting_questions', 'need_more_questions', 'retry_question' => $this->sendFacebookQuestionWithTarotImage($fbService, $richService, $userId, $message, $action, $result),

                // เช็คสิทธิ์ → Check Remaining Template
                'check_remaining' => $this->sendFacebookCheckRemainingResponse($fbService, $richService, $userId, $result),

                // หมดสิทธิ์ฟรี → AI Limit Template
                'ai_limit' => $this->sendFacebookAiLimitResponse($fbService, $richService, $userId, $result),

                // บิลหมดอายุ → Payment Expired Template
                'payment_expired' => $this->sendFacebookPaymentExpiredResponse($fbService, $richService, $userId, $result),

                // ปฏิเสธ / ยกเลิก → Declined Template
                'declined', 'cancelled' => $this->sendFacebookDeclinedResponse($fbService, $richService, $userId, $result),

                // Help → Welcome Template
                'help', 'filtered' => $this->sendFacebookHelpResponse($fbService, $richService, $userId, $result),

                // รอชำระเงิน (เตือนซ้ำ) → Waiting Payment Template
                'waiting_payment' => $this->sendFacebookWaitingPaymentResponse($fbService, $richService, $userId, $result),

                // ยืนยันชำระเงินสำเร็จ → Payment Confirmed Template
                'payment_confirmed_wait' => $this->sendFacebookPaymentConfirmedResponse($fbService, $richService, $userId, $result),

                // 🔍 เช็คสถานะบิล (ผู้ใช้กดปุ่ม) — ตอบสถานะจริงพร้อมปุ่ม
                'payment_check_processing' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔄 เช็คอีกครั้ง', 'payload' => 'CHECK_PAYMENT_STATUS'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน (พิมพ์ "แอดมิน" / "คุยกับแม่หมอ")
                ], $extra),
                'payment_check_pending' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔄 เช็คอีกครั้ง', 'payload' => 'CHECK_PAYMENT_STATUS'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน (พิมพ์ "แอดมิน" / "คุยกับแม่หมอ")
                    ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL_FORTUNE'],
                ], $extra),
                'payment_check_expired' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน (พิมพ์ "แอดมิน" / "คุยกับแม่หมอ")
                ], $extra),

                // 🔍 (2026-05-15) Fuzzy Payment Match — auto-approve flow
                'fuzzy_auto_approved' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔄 เช็คคำทำนาย', 'payload' => 'CHECK_PAYMENT_STATUS'],
                ], $extra),

                'fuzzy_ask_confirmation' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ ใช่ ของฉัน', 'payload' => 'FUZZY_CONFIRM_YES'],
                    ['content_type' => 'text', 'title' => '❌ ไม่ใช่', 'payload' => 'FUZZY_CONFIRM_NO'],
                ], $extra),

                // 🛑 (2026-05-15) ถามก่อนยกเลิก — กันลูกค้าหายเพราะติดปัญหาเล็กน้อย (legacy pattern)
                'cancel_reason_prompt' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🆘 โอนไม่เป็น', 'payload' => 'CANCEL_HELP_TRANSFER'],
                    ['content_type' => 'text', 'title' => '💬 คุยกับแอดมิน', 'payload' => 'CANCEL_HELP_ADMIN'],
                    ['content_type' => 'text', 'title' => '❌ ยกเลิกจริง', 'payload' => 'CANCEL_CONFIRM_REAL'],
                ], $extra),

                // 🛑 (2026-05-15 v2) AI-driven cancel dialogue — ไม่ต้องมี Quick Reply (ให้ลูกค้าพิมพ์อิสระ)
                //   AI ฟัง + decide ตีความเจตนาจริง
                //   มี escape hatch: "ยืนยันยกเลิก" (พิมพ์เอง)
                'cancel_dialog_ask', 'cancel_dialog_continue' => $fbService->sendMessage($userId, $message, $extra),

                // 🛑 (2026-05-15 v2) ยกเลิก + กลับเข้า normal chat
                'cancelled_to_chat' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'START_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💬 คุยกับแม่หมอ', 'payload' => 'TALK_ADMIN'],
                ], $extra),

                'fuzzy_admin_alert', 'fuzzy_rejected_by_customer', 'fuzzy_approve_race_lost' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔄 เช็คอีกครั้ง', 'payload' => 'CHECK_PAYMENT_STATUS'],
                ], $extra),

                // เช็คสถานะ → เช็คสิทธิ์
                'check_status' => $this->sendFacebookCheckRemainingResponse($fbService, $richService, $userId, $result),

                // แชร์ลิงก์เชิญเพื่อน
                'share_link' => $this->sendFacebookShareResponse($fbService, $richService, $userId, $result),

                // สายงาน/รายได้ → ส่ง text + Button Template (ปุ่มกดลิงก์)
                'downline_info', 'earnings_info' => $this->sendFacebookButtonLinkResponse($fbService, $userId, $message, $result),

                // AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
                'ai_redirect_deep_reading' => $this->sendFacebookDeepReadingRedirect($fbService, $richService, $userId, $result),

                // keyword matched, AI chat, throttle, busy ฯลฯ → ส่ง text + Quick Replies
                'keyword_matched', 'ai_chat_response', 'fortune_throttled', 'busy', 'busy_processing',
                'bank_account_info', 'partial', 'processing', 'queued',
                'share_no_user', 'share_error',
                'deep_reading_disabled',
                'view_reading_basic', 'view_reading_deep', 'view_reading_processing', 'view_reading_empty',
                'view_reading_celtic_pending', 'view_reading_deep_pending', // 🔧 (2026-05-03) bills รอชำระ
                'payment_lock_admin', // 🔒 (2026-05-03) reach revive limit — admin only
                'view_later',
                'invalid_birthdate', 'retry_birthdate',
                'restart_from_birthdate',
                'error',
                'ai_ask_save_question',
                'send_chart', 'deep_reading_result', 'reading_ready' => $this->sendFacebookTextWithOptionalQuickReplies($fbService, $richService, $userId, $message, $action, $result, $extra),

                // 🌙 (2026-05-23) Deep 39 หมดช่วงโปร — แนบปุ่ม Celtic 99฿ ถ้าเปิดอยู่
                'deep_promo_ended' => (function () use ($fbService, $userId, $message, $extra) {
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    if ($celticEnabled) {
                        return $fbService->sendQuickReplies($userId, $message, [
                            ['content_type' => 'text', 'title' => '💎 โอนค่าบูชาครู 99฿', 'payload' => 'TIER_CELTIC_99'],
                            ['content_type' => 'text', 'title' => '🙏 ไว้คราวหน้า', 'payload' => 'NOT_INTERESTED'],
                        ], $extra);
                    }

                    return $fbService->sendMessage($userId, $message, $extra);
                })(),

                // 🔔 คำทำนายพร้อม → ส่ง Button Template พร้อมปุ่ม "อ่านคำทำนาย" โดดเด่น
                'fortune_ready_notification' => $this->sendFacebookFortuneReadyNotification($fbService, $userId, $message, $result),

                // 🙏 ทำนายจบ → ส่งข้อความขอบคุณ + ลิงก์ชวนเพื่อน/แชร์เพจ
                'reading_complete' => $this->sendFacebookReadingCompleteResponse($fbService, $userId, $result),

                // ✅ สุ่มไพ่ยิปซี → ส่ง Quick Reply พร้อมปุ่มเลือกไพ่
                'draw_tarot_card' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🃏 สุ่มไพ่ยิปซี', 'payload' => 'DRAW_TAROT'],
                    ['content_type' => 'text', 'title' => '🔮 เลือกไพ่ 1', 'payload' => 'DRAW_TAROT_1'],
                    ['content_type' => 'text', 'title' => '✨ เลือกไพ่ 2', 'payload' => 'DRAW_TAROT_2'],
                ], $extra),

                // 🧘 ตั้งจิตเลือกไพ่ → Quick Reply ปุ่ม "พร้อม" / "ยกเลิก"
                //    title คือสิ่งที่ส่งกลับเป็น message text → ใช้คำสั้น (ไม่มี emoji ในคำหลัก) เพื่อให้ระบบเข้าใจง่าย
                'awaiting_tarot_intention' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => 'พร้อม', 'payload' => 'TAROT_READY'],
                    ['content_type' => 'text', 'title' => 'ยกเลิก', 'payload' => 'CANCEL_FORTUNE'],
                ], $extra),

                // 📅 (2026-05-01) ทวนวันเกิด — ปุ่ม ✅ ใช่ / ❌ ไม่ใช่ พิมพ์ใหม่
                'awaiting_birthdate_confirmation' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ ใช่ ถูกต้อง', 'payload' => 'BIRTHDATE_CONFIRM_YES'],
                    ['content_type' => 'text', 'title' => '❌ ไม่ใช่ พิมพ์ใหม่', 'payload' => 'BIRTHDATE_CONFIRM_NO'],
                ], $extra),

                // ❓ (2026-05-01) ทวนคำถาม — ปุ่ม ✅ ใช่ / ❌ ไม่ตรงคำถาม
                'awaiting_question_confirmation' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ ใช่ ถูกต้อง', 'payload' => 'QUESTION_CONFIRM_YES'],
                    ['content_type' => 'text', 'title' => '❌ ไม่ตรงคำถาม', 'payload' => 'QUESTION_CONFIRM_NO'],
                ], $extra),

                // 🃏 รอเปิดไพ่ (หลังตั้งจิตแล้ว)
                'awaiting_tarot_draw' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => 'เปิดไพ่', 'payload' => 'DRAW_TAROT'],
                    ['content_type' => 'text', 'title' => 'ยกเลิก', 'payload' => 'CANCEL_FORTUNE'],
                ], $extra),

                // 📜 บิลถูกยกเลิก / AI rebuttal → ข้อความ + ปุ่มเริ่มใหม่
                'ai_rebuttal' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                ], $extra),

                // 🆕 (2026-04-29) Tier choice menu — ลูกค้าเลือก 1 จาก 2 แพคเกจ
                //   - 39฿ Basic Deep (วันเกิด + ไพ่ 1 ใบ)
                //   - 99฿ Celtic Cross (ไพ่ยิปซีเต็มสำรับ 10 ใบ)
                //   ✏️ (2026-05-01) ปรับ label ให้ผู้สูงอายุเข้าใจราคาและบริการชัดเจน
                //   ✏️ (2026-05-03) ซ่อนปุ่ม 99 ถ้า admin ปิด Celtic
                'tier_choice', 'tier_choice_invalid', 'tier_choice_chitchat' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    $offerFree = (bool) ($result['offer_free'] ?? false);
                    $celticOnlyIntro = (bool) ($result['celtic_only_intro'] ?? false);
                    $buttons = [];

                    // 🆕 (2026-05-27) Celtic-only intro — ปุ่ม "เริ่มเลย / ไว้คราวหน้า" (ไม่ใช่ "เลือกแพคเกจ")
                    //   user feedback: "ลูกค้าพิมพ์ดูดวง→สร้างบิลเลย ลูกค้ายังไม่รู้ว่าต้องจ่ายเงิน"
                    //   ปุ่มควรชัด: เริ่ม=ยินยอม / ไว้คราวหน้า=ปฏิเสธ
                    if ($celticOnlyIntro) {
                        $celticPriceLabel = (int) ($result['celtic_price'] ?? 99);
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo("💎 โอนค่าบูชาครู {$celticPriceLabel}฿", "💎 ໂອນຄ່າບູຊາຄູ {$celticPriceLabel}฿"),
                            'payload' => 'TIER_CELTIC_99'];
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo('🙏 ไว้คราวหน้า', '🙏 ໄວ້ຄາວໜ້າ'),
                            'payload' => 'CANCEL_FORTUNE'];

                        return $fbService->sendQuickReplies($userId, $message, $buttons, $extra);
                    }

                    // 🎁 (2026-05-03) ปุ่ม "ทำนายฟรี" — เฉพาะ first-timer + feature เปิด (offer_free flag)
                    // 🌐 localize labels — ลูกค้าลาวเห็น Quick Reply เป็นลาว
                    if ($offerFree) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo('🎁 ทำนายฟรี (1 ใบ)', '🎁 ທຳນາຍຟຣີ (1 ໃບ)'),
                            'payload' => 'FREE_CARD_START'];
                    }
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo('🔹 ดูดวง 39 บาท', '🔹 ເບິ່ງດວງ 39 ບາດ'),
                            'payload' => 'TIER_DEEP_39'];
                    }
                    if ($celticEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo('💎 โอนค่าบูชาครู 99฿', '💎 ໂອນຄ່າບູຊາຄູ 99฿'),
                            'payload' => 'TIER_CELTIC_99'];
                    }
                    $buttons[] = ['content_type' => 'text',
                        'title' => FortuneLocaleService::lo('❌ ยกเลิก', '❌ ຍົກເລີກ'),
                        'payload' => 'CANCEL_FORTUNE'];

                    return $fbService->sendQuickReplies($userId, $message, $buttons, $extra);
                })(),

                // 📜 (2026-06-06) Consent Gate — รูปกติกา (ถ้ามี) + คำเตือน + ปุ่มยืนยัน (พร้อมโอน/ยกเลิก)
                'consent_gate' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    $imageUrl = $result['consent_image_url'] ?? null;
                    $buttons = $result['quick_replies'] ?? [];
                    if (! empty($imageUrl)) {
                        try {
                            $fbService->sendImage($userId, $imageUrl);
                            usleep(400000); // ให้รูปมาก่อน text
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Fortune: consent image FB send failed', ['error' => $e->getMessage()]);
                        }
                    }

                    return $fbService->sendQuickReplies($userId, $message, $buttons, $extra);
                })(),

                // 💰 (2026-05-08) Pricing menu — ส่งกล่องราคา + ปุ่มเริ่มดูดวงทันที
                //   trigger เมื่อลูกค้าถาม "ราคา/อัตรา/กี่บาท" (ไม่ต้องอยู่ใน fortune flow)
                'pricing_menu' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    $pricing = $result['pricing'] ?? [];
                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($pricing['celtic_enabled'] ?? false);
                    $freeEnabled = (bool) ($pricing['free_enabled'] ?? false);

                    $buttons = [];
                    if ($freeEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => '🎁 ทำนายฟรี',
                            'payload' => 'FREE_CARD_START'];
                    }
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => '🔹 เริ่ม 39 บาท',
                            'payload' => 'TIER_DEEP_39'];
                    }
                    if ($celticEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => '💎 โอนค่าบูชาครู 99฿',
                            'payload' => 'TIER_CELTIC_99'];
                    }

                    $sent = $fbService->sendQuickReplies($userId, $message, $buttons, $extra);

                    // 🙏 (2026-05-14) AI follow-up — แม่หมออธิบาย "ค่าครู" แบบสนทนา ปรับ tone ตาม persona
                    // Delay 4s ให้กล่องราคาขึ้นก่อน — graceful degradation ถ้า queue ไม่รัน
                    try {
                        \App\Jobs\SendPricingFollowUpJob::dispatch('facebook', $userId, $pricing)
                            ->delay(now()->addSeconds(4));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Pricing followup dispatch fail', ['err' => $e->getMessage()]);
                    }

                    return $sent;
                })(),

                // 💳 (2026-05-14) Payment info — ส่งเลขบัญชี + QR (เมื่อลูกค้าพิมพ์ "ขอเลขบัญชี" / "พร้อมเพย์")
                'payment_info' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // 1. ส่ง QR ก่อน (ถ้ามี) — ลูกค้าจะได้สแกนเลย
                    $qrUrl = $result['payment_qr_url'] ?? null;
                    if ($qrUrl) {
                        try {
                            $fbService->sendImage($userId, $qrUrl);
                            usleep(500000); // 0.5s กัน LINE 429 + ให้ภาพ render ก่อน
                        } catch (\Throwable $e) {
                            Log::warning('Facebook: ส่ง payment QR ไม่สำเร็จ', ['err' => $e->getMessage()]);
                        }
                    }

                    // 2. ส่งข้อความเลขบัญชี
                    return $fbService->sendMessage($userId, $message, $extra);
                })(),

                // 🎁 (2026-05-03) ทำนายฟรี — ส่งภาพไพ่ + ข้อความทำนาย + Quick Reply [39][99][ไม่สนใจ]
                'free_card_drawn' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // 1. ส่งภาพไพ่ก่อน (ถ้ามี)
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['tarot_image_url']);
                            usleep(500000); // delay 0.5s ให้ภาพปรากฏก่อนข้อความ
                        } catch (\Throwable $e) {
                            // image fail ไม่เป็นไร — ส่งข้อความต่อ
                        }
                    }

                    // 2. ส่งข้อความทำนาย + Quick Reply ตัวเลือก (🌐 localize labels)
                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    $deepPrice = (int) (FortuneTellingSetting::getSettings()->deep_reading_price ?? 39);
                    $celticPrice = (int) app(CelticCrossService::class)->getPrice();

                    $buttons = [];
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo("🔹 ดูดวง {$deepPrice}฿", "🔹 ເບິ່ງດວງ {$deepPrice}฿"),
                            'payload' => 'TIER_DEEP_39'];
                    }
                    if ($celticEnabled) {
                        // 🛡️ FB QR title cap = 20 chars — เลิก "— " เพื่อ fit ใน 19 chars
                        $buttons[] = ['content_type' => 'text',
                            'title' => FortuneLocaleService::lo("💎 โอนค่าบูชาครู {$celticPrice}฿", "💎 ໂອນຄ່າບູຊາຄູ {$celticPrice}฿"),
                            'payload' => 'TIER_CELTIC_99'];
                    }
                    $buttons[] = ['content_type' => 'text',
                        'title' => FortuneLocaleService::lo('🌙 ไม่สนใจ', '🌙 ບໍ່ສົນໃຈ'),
                        'payload' => 'FREE_CARD_DECLINE'];

                    return $fbService->sendQuickReplies($userId, $message, $buttons, $extra);
                })(),

                // 🌙 (2026-05-03) ลูกค้าปฏิเสธ upsell หลังฟรี — คำลา + ปรัชญา (ไม่ฮาร์ดเซล ไม่มีปุ่ม)
                'free_card_declined' => $fbService->sendMessage($userId, $message, $extra),

                // 🎁 (2026-05-03) free flow chitchat / ไพ่จั่วไม่สำเร็จ / AI fail — ส่งข้อความ + Quick Reply เลือก tier
                'free_card_chitchat', 'free_card_draw_failed', 'free_card_ai_failed' => $this->sendFacebookTextWithOptionalQuickReplies($fbService, $richService, $userId, $message, $action, $result, $extra),

                // 🎁 (2026-05-03) ดูคำทำนายฟรีย้อนหลัง — ส่งภาพไพ่ก่อน + ข้อความ
                'view_reading_free' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['tarot_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                        }
                    }

                    return $fbService->sendMessage($userId, $message, $extra);
                })(),

                // 🔒 (2026-05-03) Pay-First Gate responses — บิลค้าง resend QR / revive
                //    ใช้ template เดียวกับ pending_payment / celtic_pending_payment (มี QR + ปุ่ม)
                'payment_lock_pending', 'payment_lock_revived' => $this->sendFacebookPaymentResponse($fbService, $richService, $userId, $result),

                // 💳 (2026-05-09) Stripe payment method selection — 2 ปุ่ม QR Thai vs Stripe
                'awaiting_payment_method' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    $qrs = [];
                    foreach (($result['quick_replies'] ?? []) as $qr) {
                        $qrs[] = [
                            'content_type' => 'text',
                            'title' => $qr['label'] ?? $qr['text'] ?? '',
                            'payload' => $qr['payload'] ?? $qr['text'] ?? '',
                        ];
                    }
                    if (empty($qrs)) {
                        $qrs = [
                            ['content_type' => 'text', 'title' => '💚 QR ไทย', 'payload' => 'PAY_METHOD_QR_THAI'],
                            ['content_type' => 'text', 'title' => '💳 บัตร ตปท.', 'payload' => 'PAY_METHOD_STRIPE'],
                        ];
                    }

                    return $fbService->sendQuickReplies($userId, $message, $qrs, $extra);
                })(),

                // 💳 (2026-05-09) Stripe checkout session created — ส่งลิงก์ + Quick Reply กลับไป QR Thai
                'pending_stripe_payment', 'pending_stripe_payment_reminder' => (function () use ($fbService, $userId, $message, $extra) {
                    // FB Quick Reply ไม่ render URL — จึงใช้ text Quick Reply แทน + ลิงก์อยู่ใน body
                    $qrs = [
                        ['content_type' => 'text', 'title' => '💳 จ่ายต่อ', 'payload' => 'STRIPE_RESUME'],
                        ['content_type' => 'text', 'title' => '↩️ ใช้ QR Thai', 'payload' => 'PAY_METHOD_QR_THAI'],
                    ];

                    return $fbService->sendQuickReplies($userId, $message, $qrs, $extra);
                })(),

                // 💎 (2026-05-03) Request-Before-Pay — confirmation prompt + 2 buttons
                'delivery_confirm_prompt' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ รับคำทำนาย', 'payload' => 'DELIVERY_CONFIRM_YES'],
                    ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'DELIVERY_CONFIRM_NO'],
                ], $extra),

                // 🆕 (2026-05-04) Pay-later reconfirm — Quick Reply ยืนยันก่อน AI gen
                'pay_later_reconfirm', 'pay_later_reconfirm_invalid' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ ใช่ เริ่มเลย', 'payload' => 'PAY_LATER_ACK_YES'],
                    ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'PAY_LATER_ACK_NO'],
                ], $extra),

                // 🆕 (2026-05-04) Pay-later declined — text only
                'pay_later_declined' => $fbService->sendMessage($userId, $message, $extra),

                // 💎 ลูกค้ายกเลิกการรับคำทำนาย — ปิด conversation (text only)
                'delivery_cancelled' => $fbService->sendMessage($userId, $message, $extra),

                // 💎 invalid response → re-prompt with same buttons
                'delivery_confirm_invalid' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ รับคำทำนาย', 'payload' => 'DELIVERY_CONFIRM_YES'],
                    ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'DELIVERY_CONFIRM_NO'],
                ], $extra),

                // 💎 deliver_with_qr — ส่งคำทำนาย + QR (ใช้ payment template)
                'deliver_with_qr' => $this->sendFacebookPaymentResponse($fbService, $richService, $userId, $result),

                // 💎 (2026-05-04) bill creation failed — ส่งคำทำนายอย่างน้อยให้ลูกค้าได้อ่าน + แจ้ง error
                'delivery_bill_failed' => (function () use ($fbService, $userId, $message, $result) {
                    if (! empty($result['chart_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['chart_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                        }
                    }

                    return $fbService->sendMessage($userId, $message);
                })(),

                // 🔮 Celtic Cross actions (2026-04-29)
                // pending_payment ของ Celtic → ใช้ template เดียวกับ Deep (ส่ง QR + button)
                // celtic_pending_payment_reuse → resume guard เจอบิลเก่า → ใช้ template เดียวกัน (2026-05-04)
                'celtic_pending_payment', 'celtic_pending_payment_reuse' => $this->sendFacebookPaymentResponse($fbService, $richService, $userId, $result),

                // celtic_card_picked → ส่งรูปไพ่ + ข้อความ + ปุ่ม "เปิดไพ่" สำหรับใบถัดไป
                // ✏️ (2026-05-03) เปลี่ยน label ให้คนแก่เข้าใจชัดเจน + เพิ่มปุ่มสับใหม่
                // 🧾 (2026-06-01) slipok_recovered_celtic — กู้บิลด้วยสลิป → render แบบ celtic (ปุ่ม "เปิดไพ่") ไม่ใช่ default
                'celtic_card_picked', 'celtic_pick_prompt', 'celtic_chitchat_reminder', 'celtic_reset',
                'celtic_restart_hint', 'celtic_already_in_session', 'slipok_recovered_celtic' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // ส่งรูปไพ่ก่อน (ถ้ามี)
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['tarot_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                            // ignore image fail
                        }
                    }

                    // 🃏 ปุ่มชัดเจนสำหรับผู้สูงอายุ
                    $reading = $result['reading'] ?? null;
                    $picked = $reading?->getCelticPickedCount() ?? 0;
                    $nextLabel = $picked === 0 ? '🃏 เปิดไพ่ใบที่ 1' : '🃏 เปิดไพ่ใบถัดไป';

                    // 🆕 (2026-05-17) ซ่อนปุ่ม "สับใหม่" เมื่อใช้ครบโควต้า 1 ครั้งแล้ว
                    $quickReplies = [['content_type' => 'text', 'title' => $nextLabel, 'payload' => 'CELTIC_READY']];
                    if ($reading && method_exists($reading, 'canShuffleCelticAgain') && $reading->canShuffleCelticAgain()) {
                        $quickReplies[] = ['content_type' => 'text', 'title' => '🔄 สับใหม่', 'payload' => 'CELTIC_RESET'];
                    }
                    $quickReplies[] = ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL_FORTUNE'];

                    return $fbService->sendQuickReplies($userId, $message, $quickReplies, $extra);
                })(),

                // celtic_all_picked → ส่งภาพ composite Celtic Cross + ข้อความขอ Q1
                // 🆕 (2026-05-06) เพิ่ม Quick Reply ปุ่ม "เริ่มถามคำถาม"
                //   ลูกค้าหลังเปิดไพ่ครบ มักงงไม่รู้ต้องทำอะไรต่อ — ปุ่มนี้เป็น UX cue
                'celtic_all_picked' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // ส่งรูปไพ่ใบสุดท้าย (ถ้ามี)
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['tarot_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                        }
                    }

                    // ส่งภาพ composite Celtic Cross spread
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['celtic_summary_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                        }
                    }

                    // 🛑 (2026-05-14 v2) ลบ "พอแค่นี้" ออกจาก opening — AI ทักเองแล้ว user
                    //   user spec: "เอาปุ่ม พอแค่นี้ออกด้วย" หลังเปิดไพ่เสร็จ
                    //   → ส่ง plain message (AI ใน opening greeting ก็เชิญถามอยู่แล้ว)
                    //   ถ้า trait ส่ง quick_replies มาเฉพาะ → ก็ใช้ตามนั้น (custom case)
                    if (! empty($result['quick_replies'])) {
                        return $fbService->sendQuickReplies($userId, $message, $result['quick_replies'], $extra);
                    }

                    return $fbService->sendMessage($userId, $message, $extra);
                })(),

                // 🛑 (2026-05-16) เอาปุ่ม "ถามต่อ" ออก — user spec: ลูกค้าพิมพ์คำถามได้เลย
                //                  เหลือแค่ปุ่ม "ยุติการทำนาย" เพื่อจบ session
                // 🆕 (2026-05-23) celtic_continue = alias หลัง user ยืนยัน "ขอคุยต่อ"
                // 🐛 (2026-05-28) Delivery confirm — capture push result, retry 1x, mark delivered
                //   เคสจริง บิล FTU-260528-E8815: AI ตอบสำเร็จ แต่ push ไม่ถึงลูกค้า เห็นแค่ "ติดขัด"
                //   เดิม FB ไม่เช็คผลส่งเลย → fortune:celtic-redeliver cron จับ delivered_at=null ส่งซ้ำ
                'celtic_question_answered', 'celtic_qa_prompt_resume', 'celtic_continue' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    //   ลูกค้ายังจบเองได้ด้วยการ "พิมพ์ เลิก" (handleCelticEndConfirmation จับ เลิก/จบ/พอแล้ว)
                    //   ส่ง plain (sendQuickReplies + [] → fallback sendMessage ภายใน) — คง retry + markDelivered เดิม
                    $qr = [];
                    $ok = $fbService->sendQuickReplies($userId, $message, $qr, $extra);
                    if (! $ok) {
                        usleep(800000); // 0.8s แล้ว retry 1 ครั้ง — กัน transient FB blip
                        $ok = $fbService->sendQuickReplies($userId, $message, $qr, $extra);
                    }

                    $reading = $result['reading'] ?? null;
                    if ($ok && $reading) {
                        try {
                            // 🐛 (2026-05-29) mark delivered ตรง row by sequence — เลิกเดา orderByDesc
                            //   เคสจริง reading 4211 (บิล H8518): race admin ask + ลูกค้าพิมพ์พร้อมกัน
                            //   + orphan record (AI fail) → orderByDesc mark ผิด row → row ที่ push จริง
                            //   ไม่ถูก mark → cron redeliver ส่งซ้ำ → ลูกค้าเห็นคำตอบ 2 ครั้ง
                            //   ใหม่: mark ตาม sequence ที่ push จริง (ส่งจาก trait/admin) — fallback orderByDesc
                            $seq = $result['sequence'] ?? null;
                            $q = $seq !== null
                                ? $reading->celticQuestions()->where('sequence', (int) $seq)->whereNotNull('answered_at')->first()
                                : $reading->celticQuestions()->whereNotNull('answered_at')->orderByDesc('sequence')->first();
                            $q?->markDelivered();
                        } catch (\Throwable $e) {
                            \Log::debug('FB Celtic: markDelivered fail (non-blocking)', ['error' => $e->getMessage()]);
                        }
                    } elseif (! $ok) {
                        \Log::critical('FB Celtic Q&A: ส่งคำทำนายล้มเหลว — ลูกค้าไม่ได้รับ! (cron จะ re-deliver)', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'msg_preview' => mb_substr($message, 0, 150),
                        ]);
                    }

                    // 🔢 (2026-06-05) กล่องที่ 2 — คำถามแนะนำต่อยอด + ปุ่มเลข 1️⃣2️⃣ (best-effort)
                    //   ส่งหลังคำทำนายถึงแล้ว — ถ้า fail ไม่ retry/ไม่ redeliver (เป็นแค่ตัวช่วย ไม่ใช่คำตอบ)
                    if ($ok && ! empty($result['suggestion_box']) && ! empty($result['quick_replies'])) {
                        try {
                            $fbService->sendQuickReplies($userId, $result['suggestion_box'], $result['quick_replies'], $extra);
                        } catch (\Throwable $e) {
                            \Log::debug('FB Celtic: suggestion box send fail (non-blocking)', ['error' => $e->getMessage()]);
                        }
                    }

                    return $ok;
                })(),

                // 🆕 (2026-05-23) celtic_end_confirm — 2-step confirm dialog ก่อนจบ session
                //    user spec: "ถามก่อนว่าจะเลิกแล้วสรุปเลยจริงไหม เพราะบางคนมือไปกดผิด"
                'celtic_end_confirm' => $fbService->sendQuickReplies($userId, $message, $result['quick_replies'] ?? [
                    ['content_type' => 'text', 'title' => '✅ ใช่ ส่งสรุปเลย', 'payload' => 'CELTIC_END_YES'],
                    ['content_type' => 'text', 'title' => '↩️ ขอคุยต่ออีกหน่อย', 'payload' => 'CELTIC_END_NO'],
                ], $extra),

                // 🆕 (2026-05-17) celtic_typing_only — typing delay 60-120s pattern
                //   Trait dispatch SendCelticDelayedPrediction job แล้ว — channel manager แค่ส่ง typing_on
                //   (typing dot จะหายเองที่ ~20s แต่ยอมรับได้ — sendCelticThinkingAck แจ้งล่วงหน้าว่ารอ 1-2 นาที)
                'celtic_typing_only' => true,

                // 📜 (2026-05-03) Celtic Q&A review — legacy quick-reply list (Q1/Q2)
                'celtic_review_detail', 'celtic_review_not_found' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['celtic_summary_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                            // ignore image fail
                        }
                    }

                    return $fbService->sendQuickReplies(
                        $userId,
                        $message,
                        $result['celtic_review_quick_replies'] ?? [
                            ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
                        ],
                        $extra
                    );
                })(),

                // 💬 (2026-05-14) Celtic chat-style conversation log
                //   user spec: "บันทึกเป็นบทสนทนายาวๆ" — ลบ Q1/Q2/Q3 + quick reply เลือกคำถาม
                //   แสดงสลับ User ↔ แม่หมอจันทรา ตามลำดับเวลา
                //   ถ้ายาว → ส่งหลายข้อความ (overflow) — FB จำกัด ~2000 char/message
                //   🛡️ U1 fix (2026-05-14): propagate $extra ทุก sendImage/sendMessage
                //      กัน silent fail ถ้า flow นี้ถูกเรียกนอก 24h window (scheduled/admin retry)
                'celtic_review_log' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    // 🖼️ ส่งภาพไพ่ก่อน (ที่ระลึก)
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['celtic_summary_image_url'], null, $extra);
                            usleep(500000);
                        } catch (\Throwable $e) {
                            // ignore image fail
                        }
                    }

                    // ส่ง message แรก (header + บทสนทนาช่วงต้น)
                    $fbService->sendMessage($userId, $message, $extra);

                    // ส่ง overflow messages (ถ้ามี)
                    foreach ((array) ($result['celtic_conversation_overflow'] ?? []) as $segment) {
                        usleep(500000);
                        $fbService->sendMessage($userId, $segment, $extra);
                    }

                    // ส่ง quick reply ปิดท้าย — ตามสถานะ session
                    $canAskMore = (bool) ($result['celtic_can_ask_more'] ?? false);
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    //   ยังคุยต่อได้ / จบเองด้วยการพิมพ์ "เลิก" — เหลือ "ดูดวงใหม่" เฉพาะตอนจบรอบแล้ว
                    $quickReplies = $canAskMore
                        ? []
                        : [
                            ['content_type' => 'text', 'title' => '🔮 ดูดวงใหม่', 'payload' => 'MENU_FORTUNE'],
                        ];

                    usleep(500000);

                    return $fbService->sendQuickReplies(
                        $userId,
                        $canAskMore
                            ? '💬 คุยต่อได้เลยค่ะ — หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบและรับสรุป 🙏'
                            : '🙏 ขอบคุณที่ใช้บริการแม่หมอจันทรานะคะ ✨',
                        $quickReplies,
                        $extra
                    );
                })(),

                // celtic_session_ended → ส่งภาพ Celtic Cross spread (ที่ระลึก) + closing message
                // 🖼️ (2026-05-03) ภาพ composite ส่งตอนจบ ไม่ใช่ตอนเปิดครบ 10 ใบ
                // 🎙️ (2026-05-08) Voice ส่งผ่าน ProcessVoiceSummaryJob async (push หลัง 5-15s)
                //                 ตรงนี้แค่ส่ง image+closing — ไม่ block UI
                'celtic_session_ended' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $fbService->sendImage($userId, $result['celtic_summary_image_url']);
                            usleep(500000);
                        } catch (\Throwable $e) {
                            // ignore image fail
                        }
                    }

                    return $fbService->sendMessage($userId, $message, $extra);
                })(),

                // celtic actions ที่เป็น text-only (cancelled, completed, expired, ai_failed, etc.)
                // celtic_resume_qa → resume เข้า AWAITING_QUESTION (ลูกค้าพิมพ์คำถามเอง — ไม่ใส่ปุ่ม)
                'celtic_cancelled', 'celtic_completed', 'celtic_qa_window_expired',
                'celtic_ai_failed', 'celtic_processing', 'celtic_disabled',
                'celtic_question_too_short', 'celtic_pick_failed', 'celtic_reset_denied',
                'celtic_awaiting_payment', 'celtic_bill_creation_failed',
                'celtic_resume_qa' => $fbService->sendMessage($userId, $message, $extra),

                // 📚 (2026-05-09) ประวัติบิล — ส่งข้อความ list + Quick Reply ปุ่มเลือกบิล
                //   handleMyBills คืน quick_replies = [['title','text','payload']] (FB format)
                'my_bills_list' => (function () use ($fbService, $userId, $message, $result, $extra) {
                    $quickReplies = $result['quick_replies'] ?? [];
                    if (empty($quickReplies)) {
                        return $fbService->sendMessage($userId, $message, $extra);
                    }

                    return $fbService->sendQuickReplies($userId, $message, $quickReplies, $extra);
                })(),

                // ประวัติบิลว่าง — ส่งข้อความ + ปุ่ม "ดูดวง"
                'my_bills_empty' => $fbService->sendQuickReplies($userId, $message, [
                    ['title' => '🔮 ดูดวงเลย', 'payload' => 'ดูดวง'],
                ], $extra),

                // อื่นๆ → ส่ง text ธรรมดา
                // 🌍 (2026-06-03) ลูกค้าต่างชาติ — ถามยืนยัน + ปุ่ม "แน่ใจ จ่ายเงินไทยได้"
                'foreign_service_confirm' => $fbService->sendQuickReplies($userId, $message, [
                    ['content_type' => 'text', 'title' => '✅ แน่ใจ จ่ายเงินไทยได้', 'payload' => 'FOREIGN_CONFIRM_PAY'],
                    ['content_type' => 'text', 'title' => '🙏 ไว้คราวหน้า', 'payload' => 'CANCEL_FORTUNE'],
                ], $extra),

                default => $fbService->sendMessage($userId, $message ?: 'ระบบกำลังดำเนินการ 🙏', $extra),
            };

            // Log ถ้าส่งไม่สำเร็จ
            if (! $sent) {
                Log::warning('Facebook sendFacebookResponse: ส่งไม่สำเร็จ, fallback เป็น text', [
                    'action' => $action,
                    'user_id' => $userId,
                ]);
                // Fallback ส่ง text ธรรมดา
                if ($message) {
                    $fbService->sendMessage($userId, mb_substr($message, 0, 2000));
                }
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Facebook sendFacebookResponse exception — fallback เป็น text', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback ส่ง text ธรรมดาเสมอ
            $fallbackText = $message ?: 'ระบบกำลังดำเนินการ กรุณารอสักครู่ 🙏';

            return $fbService->sendMessage($userId, mb_substr($fallbackText, 0, 2000));
        }
    }

    // ============================================================
    // Facebook Response Handlers (เทียบเท่า sendLine*Response)
    // ============================================================

    /**
     * Facebook: ส่งคำทำนายพื้นฐาน + Upsell + LINE invite
     */
    protected function sendFacebookBasicDoneResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ส่ง Birth Chart ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ส่งคำทำนาย (text — อาจยาว ต้องแบ่ง)
        if (! empty($message)) {
            // แยก message ออกจากส่วน upsell
            $parts = explode('═══════════════════════', $message);
            $prediction = trim($parts[0] ?? $message);
            if (! empty($prediction)) {
                $fbService->sendMessage($userId, $prediction);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            }
        }

        // ส่ง Upsell Template (ถ้าเปิดดูดวงละเอียด)
        if ($this->settings->isDeepReadingEnabled()) {
            $upsellTemplate = $richService->buildUpsellTemplate($userName, $this->getReadingPrice());

            return $fbService->sendButtonTemplate($userId, $upsellTemplate);
        }

        // ถ้าไม่มี deep reading → ส่ง LINE invite
        $lineInvite = $richService->buildLineInviteTemplate();
        if ($lineInvite) {
            return $fbService->sendButtonTemplate($userId, $lineInvite);
        }

        return true;
    }

    /**
     * Facebook: ส่งข้อมูลชำระเงิน
     * ⚠️ ไม่แก้ logic การจับคู่ SMS — แค่แสดงข้อมูลบิลสวยขึ้น
     */
    /**
     * 🃏 (2026-05-04) Helper: ดึงรายการ image URLs ของไพ่ยิปซีจาก result array
     *
     * รองรับทั้ง 2 รูปแบบ:
     *   - tarot_image_url (single string) — เดิม (เคส single card pick)
     *   - tarot_image_urls (array) — ใหม่ (เคส Pay-Later/Deep view ที่จับหลายใบ)
     *   - reading.getCollectedTarotCards() — fallback ถ้ามี reading
     */
    protected function normalizeTarotImageUrls(array $result): array
    {
        // 1) ถ้ามี tarot_image_urls (array) ใช้เลย
        if (! empty($result['tarot_image_urls']) && is_array($result['tarot_image_urls'])) {
            return array_values(array_filter($result['tarot_image_urls']));
        }

        // 2) ถ้ามี tarot_image_url (single) — wrap เป็น array
        if (! empty($result['tarot_image_url']) && is_string($result['tarot_image_url'])) {
            return [$result['tarot_image_url']];
        }

        // 3) Fallback — ดึงจาก reading.getCollectedTarotCards()
        $reading = $result['reading'] ?? null;
        if ($reading && method_exists($reading, 'getCollectedTarotCards')) {
            try {
                $urls = collect($reading->getCollectedTarotCards())
                    ->pluck('image_url')
                    ->filter()
                    ->values()
                    ->all();

                return $urls;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return [];
    }

    protected function sendFacebookPaymentResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $reading = $result['reading'] ?? null;
        $message = $result['message'] ?? '';

        // 🃏 ส่งรูปไพ่ยิปซีก่อน (ถ้ามี) — รองรับทั้ง single (tarot_image_url) และ array (tarot_image_urls)
        //    draw_tarot_card action: tarot_image_url (single — สำหรับเคส single card pick)
        //    Pay-Later/Deep view: tarot_image_urls (array — ทุกใบที่ลูกค้าจับ)
        $tarotImageUrls = $this->normalizeTarotImageUrls($result);
        foreach ($tarotImageUrls as $idx => $imgUrl) {
            try {
                $fbService->sendImage($userId, $imgUrl);
                usleep(500000); // 0.5s
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่งรูปไพ่ยิปซีไม่สำเร็จ (payment)', [
                    'error' => $e->getMessage(),
                    'image_url' => $imgUrl,
                    'card_index' => $idx,
                ]);
            }
        }

        // ส่ง Birth Chart (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ (payment)', ['error' => $e->getMessage()]);
            }
        }

        // ✅ ส่งข้อมูลบิล + QR เป็นชุดเดียว (ไม่ซ้ำกับ Payment Template)
        // ส่งเฉพาะ text ข้อมูลบิล (ไม่ส่ง Payment Template อีก เพราะข้อมูลซ้ำ)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        // 🆕 (2026-05-31) ส่งเลขบัญชีเป็น "บับเบิลเดี่ยว" เลขล้วนๆ — ผู้สูงอายุกดค้างคัดลอกได้ง่าย
        //   (เลขใส่ dash แล้วจาก getCopyableAccountNumber → กัน FB auto-QR, banking app ตัด dash เอง)
        $copyableAccount = $result['copyable_account'] ?? null;
        if (! empty($copyableAccount)) {
            try {
                $fbService->sendMessage($userId, $copyableAccount);
                usleep(500000); // 0.5s กัน LINE/FB rate limit
            } catch (\Throwable $e) {
                Log::warning('Facebook: ส่งเลขบัญชี (copyable) ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ส่งภาพ QR Code โอนเงิน (ถ้ามี) — ส่งครั้งเดียว
        $paymentQrUrl = $result['payment_qr_url'] ?? null;
        if ($paymentQrUrl) {
            try {
                $fbService->sendImage($userId, $paymentQrUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง QR Code ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // 🌙 (2026-05-31) ส่งข้อความปิดท้าย "โอนแล้วรอ" เป็น text ธรรมดา (ไม่มีปุ่ม — user request)
        //   เดิมเป็น button template [✅ แจ้งโอนแล้ว][❌ ยกเลิก] → เอาปุ่มออก (คนแก่สับสน)
        //   แจ้งโอน = SMS auto-match เปิดไพ่ให้เอง / ยกเลิก = ลูกค้าพิมพ์ "ยกเลิก" ได้
        if ($reading) {
            $paymentNote = $richService->buildPaymentInstructionText($reading);
            if (! empty($paymentNote)) {
                return $fbService->sendMessage($userId, $paymentNote);
            }
        }

        return true;
    }

    /**
     * Facebook: ส่งคำทำนายละเอียดเสร็จ + LINE invite + affiliate
     */
    protected function sendFacebookCompletedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // 🃏 (2026-05-04) ส่งรูปไพ่ยิปซีก่อน chart — user request
        $tarotImageUrls = $this->normalizeTarotImageUrls($result);
        foreach ($tarotImageUrls as $imgUrl) {
            try {
                $fbService->sendImage($userId, $imgUrl);
                usleep(500000);
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่งรูปไพ่ยิปซีไม่สำเร็จ (completed)', ['error' => $e->getMessage()]);
            }
        }

        // ส่ง Birth Chart ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart image ไม่สำเร็จ (completed)', ['error' => $e->getMessage()]);
            }
        }

        // ส่งคำทำนาย (text)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        // 🌙 (2026-05-08 v3) ถ้าเปิด Pro Session ไป suppress Reading Complete Template
        //    เพื่อไม่ให้ปุ่ม "เพิ่ม LINE / เชิญเพื่อน" รบกวน Pro Session opening msg
        //    Pro Session takes over UX จนกว่าจะหมดเวลาหรือลูกค้าจบ session
        if (! empty($result['suppress_complete_template'] ?? false)) {
            return true;
        }

        // ส่ง Reading Complete Template + LINE invite + affiliate
        $completeTemplate = $richService->buildReadingCompleteTemplate();

        return $fbService->sendButtonTemplate($userId, $completeTemplate);
    }

    /**
     * Facebook: ส่งข้อความ + Quick Replies ตาม action
     */
    /**
     * Facebook: ส่งรูปไพ่ยิปซีก่อน + Quick Replies เลือกคำถาม
     */
    protected function sendFacebookQuestionWithTarotImage(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action, array $result): bool
    {
        // ✅ ส่งรูปไพ่ยิปซีก่อน (ถ้ามี)
        $tarotImageUrl = $result['tarot_image_url'] ?? null;
        if ($tarotImageUrl) {
            try {
                $fbService->sendImage($userId, $tarotImageUrl);
            } catch (\Exception $imgErr) {
                Log::warning('Facebook: ส่งรูปไพ่ยิปซีไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                    'image_url' => $tarotImageUrl,
                ]);
            }
        }

        return $this->sendFacebookWithQuickReplies($fbService, $richService, $userId, $message, $action);
    }

    protected function sendFacebookWithQuickReplies(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action): bool
    {
        $quickReplies = $richService->getQuickRepliesForAction($action);

        if (! empty($quickReplies) && ! empty($message)) {
            return $fbService->sendQuickReplies($userId, $message, $quickReplies);
        }

        return $fbService->sendMessage($userId, $message ?: 'พิมพ์คำถามมาได้เลย 🔮');
    }

    /**
     * Facebook: ขอวันเกิด
     */
    protected function sendFacebookBirthdateResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        // ✅ ส่งเฉพาะ Button Template เท่านั้น (ไม่ส่ง text ก่อน — เพราะ template มีข้อความขอวันเกิดอยู่แล้ว)
        $template = $richService->buildBirthdatePromptTemplate($this->getReadingPrice());

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: เช็คสิทธิ์ดูดวง
     */
    protected function sendFacebookCheckRemainingResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        // ดึงข้อมูลจาก result
        $remaining = $result['remaining'] ?? 0;
        $maxFree = $result['max_free'] ?? (int) ($this->settings->max_free_readings ?? 3);
        $todayCount = $result['today_count'] ?? 0;

        $template = $richService->buildCheckRemainingTemplate($remaining, $maxFree, $todayCount);

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: หมดสิทธิ์ฟรี
     */
    protected function sendFacebookAiLimitResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text ข้อความเดิม
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildAiLimitTemplate($this->getReadingPrice());

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: บิลหมดอายุ
     */
    protected function sendFacebookPaymentExpiredResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildPaymentExpiredTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: ปฏิเสธ/ยกเลิก
     */
    protected function sendFacebookDeclinedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
        }

        $template = $richService->buildDeclinedTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: Help / Welcome
     */
    protected function sendFacebookHelpResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        // 🔒 Guard: ถ้าลูกค้ามีบิลค้าง / กำลังประมวลผล / มีคำทำนายเสร็จแล้ว
        //   → ห้ามแสดง welcome bubble — ตอบ contextual message แทน
        $contextual = $this->buildActiveBillContextMessage(self::PLATFORM_FACEBOOK, $userId);
        if ($contextual !== null) {
            return $fbService->sendQuickReplies($userId, $contextual['message'], $contextual['quick_replies'] ?? []);
        }

        $userName = $result['user_name'] ?? 'คุณ';
        // 🩹 (2026-05-04) pass userId เพื่อให้ buildWelcomeTemplate ตรวจ hasUsedFreeCard
        $template = $richService->buildWelcomeTemplate($userName, $userId);

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: เตือนชำระเงิน
     */
    protected function sendFacebookWaitingPaymentResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text + Quick Replies "เช็คสถานะ / ยกเลิก" — ผู้ใช้กดปุ่มแทนพิมพ์เอง
        // (เพราะถ้าพิมพ์เองอาจหลงทาง)
        return $fbService->sendQuickReplies($userId, $message ?: 'รอชำระเงิน', [
            ['content_type' => 'text', 'title' => '🔍 เช็คสถานะ', 'payload' => 'CHECK_PAYMENT_STATUS'],
            ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL_FORTUNE'],
        ]);
    }

    /**
     * Facebook: ยืนยันชำระเงินสำเร็จ
     */
    protected function sendFacebookPaymentConfirmedResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $template = $richService->buildPaymentConfirmedTemplate();

        return $fbService->sendButtonTemplate($userId, $template);
    }

    /**
     * Facebook: แชร์ลิงก์เชิญเพื่อน
     */
    protected function sendFacebookShareResponse(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $referralUrl = $result['referral_url'] ?? null;

        if ($referralUrl) {
            $template = $richService->buildAffiliateShareTemplate($referralUrl);

            return $fbService->sendButtonTemplate($userId, $template);
        }

        // ถ้าไม่มี referral URL → ส่ง LINE invite แทน
        $lineInvite = $richService->buildLineInviteTemplate();
        if ($lineInvite) {
            return $fbService->sendButtonTemplate($userId, $lineInvite);
        }

        return $fbService->sendMessage($userId, $result['message'] ?? 'กรุณาสมัครสมาชิกก่อนเพื่อรับลิงก์เชิญเพื่อน');
    }

    /**
     * Facebook: ส่ง text + Quick Replies (ถ้ามี) สำหรับ actions ทั่วไป
     *
     * ใช้กับ actions ที่ไม่ต้องการ Button Template เช่น:
     * keyword_matched, ai_chat_response, error, ฯลฯ
     */
    protected function sendFacebookTextWithOptionalQuickReplies(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, string $message, string $action, array $result, array $extra = []): bool
    {
        if (empty($message)) {
            $message = 'ระบบกำลังดำเนินการ 🙏';
        }

        // 🕐 (2026-05-17) Human-like delay 15s — เฉพาะ chitchat บน FB
        //   user spec: "ดีเลย์ 15 วินาที ยกเว้น ดูดวง/แพคเกจ/keyword command (เมนู)"
        //   - ai_chat_response = chitchat → delay
        //   - keyword_matched / payment_info / view_reading_* / etc. → ตอบทันที
        //   - LINE ไม่ delay (LINE ช้าอยู่แล้ว — user spec)
        if ($action === 'ai_chat_response') {
            $this->humanLikeDelayFb($fbService, $userId, 15);
        }

        // 🔍 Strip control tags ที่ไม่ควรโชว์ให้ลูกค้า (เผื่อหลุดจาก service layer)
        $offerFortune = (bool) ($result['offer_fortune'] ?? false);
        if (mb_strpos($message, '[OFFER_FORTUNE]') !== false) {
            $offerFortune = true;
            $message = trim(str_replace('[OFFER_FORTUNE]', '', $message));
        }

        // 🃏 (2026-05-04) ส่งรูปไพ่ยิปซีก่อน chart (ถ้ามี) — เห็นไพ่ก่อนเข้าคำทำนาย
        //    user request: "การดูแบบ 39 ต้องส่งรูปไพ่ที่จับได้ด้วย ตอนนี้มีแต่กราฟดวงดาว"
        $tarotImageUrls = $this->normalizeTarotImageUrls($result);
        foreach ($tarotImageUrls as $imgUrl) {
            try {
                $fbService->sendImage($userId, $imgUrl);
                usleep(500000); // 0.5s
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่งรูปไพ่ยิปซีไม่สำเร็จ (text+options)', ['error' => $e->getMessage()]);
            }
        }

        // ส่ง chart image ก่อน (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                $fbService->sendImage($userId, $chartUrl);
                usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง chart ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        // ส่ง QR code (ถ้ามี)
        $paymentQrUrl = $result['payment_qr_url'] ?? null;
        if ($paymentQrUrl) {
            $fbService->sendMessage($userId, $message, $extra);
            usleep(500000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            try {
                $fbService->sendImage($userId, $paymentQrUrl);
            } catch (\Exception $e) {
                Log::warning('Facebook: ส่ง QR ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }

            return true;
        }

        // เช็คว่ามี Quick Replies สำหรับ action นี้ไหม
        $quickReplies = $richService->getQuickRepliesForAction($action);

        // ⚡ Fallback quick replies สำหรับ actions ที่ RichMessageService ไม่ได้ครอบคลุม
        // เพื่อให้ UX สอดคล้องกับ LINE (ปุ่ม "คุยกับแม่หมอ" เสมอ)
        if (empty($quickReplies)) {
            $quickReplies = $this->getFacebookFallbackQuickReplies($action, $result, $offerFortune);
        }

        $sent = ! empty($quickReplies)
            ? $fbService->sendQuickReplies($userId, $message, $quickReplies, $extra)
            : $fbService->sendMessage($userId, $message, $extra);

        // 🌙 (2026-05-22) Pro Session follow-up — กล่องที่ 2 บอก "หมออยู่ตอบเพิ่ม 10 นาที"
        //   User spec 2026-05-22: "กล่องข้อความต่อท้ายคำทำนายเพิ่มว่า
        //                          หมอจะอยู่ตอบเพิ่มให้อีก 10 นาที มีอะไรให้รีบถาม"
        //   เฉพาะกรณี caller request โดยตั้ง flag — กัน follow-up พ่นใส่ action อื่น
        if ($sent && ! empty($result['send_pro_session_followup'])) {
            $this->sendFacebookProSessionFollowUp($fbService, $userId, $result, $extra);
        }

        return $sent;
    }

    /**
     * 🌙 (2026-05-22) ส่งกล่อง follow-up "หมออยู่ตอบเพิ่ม 10 นาที" หลังคำทำนาย Deep 39
     *
     * Pro Session ถูก enter โดย FCS:7950 (processPaymentConfirmed) แล้ว — กล่องนี้แค่
     * แจ้งลูกค้าให้รู้ว่ามี window 10 นาที สำหรับถามต่อ
     */
    protected function sendFacebookProSessionFollowUp(
        FacebookWebhookService $fbService,
        string $userId,
        array $result,
        array $extra = []
    ): void {
        try {
            $reading = $result['reading'] ?? null;
            $rawName = $reading?->facebook_user_name ?? '';
            $greet = ($rawName !== '' && $rawName !== 'คุณ') ? " คุณ{$rawName}" : '';

            // 🌙 ใช้ window จาก setting (default 10 นาที — ProSessionTrait::PRO_SESSION_DEEP_MINUTES)
            $windowMinutes = (int) ($this->settings->pro_session_deep_minutes ?? 10);

            $followUp = "🌙✨ *แม่หมอจันทราอยู่ตอบเพิ่มอีก {$windowMinutes} นาที{$greet}* ✨\n\n"
                ."💬 ถ้ามีอะไรสงสัย หรืออยากให้แม่หมอขยายความ — *รีบถามได้เลยค่ะ*\n"
                ."🪐 แม่หมอจะตอบจากดวงดาว + ไพ่ของเจ้าชะตา\n\n"
                ."⏳ หลังหมดเวลา — พลังจะค่อยๆ ปิดลง\n"
                .'🔚 พอใจแล้วพิมพ์ *"พอแค่นี้"* หรือ *"ขอบคุณ"* แม่หมอจะปิดให้ค่ะ';

            usleep(800000); // 0.8s delay กัน race กับ chunks ของคำทำนาย (ห้ามต่ำกว่า 0.5s)

            $fbService->sendMessage($userId, $followUp, $extra);

            Log::info('FortuneChannelManager: ส่ง Pro Session follow-up สำเร็จ', [
                'user_id' => $userId,
                'reading_id' => $reading?->id,
                'window_minutes' => $windowMinutes,
            ]);
        } catch (\Throwable $e) {
            // ไม่ block flow — follow-up เป็น nice-to-have
            Log::warning('FortuneChannelManager: ส่ง Pro Session follow-up ล้มเหลว (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fallback quick replies สำหรับ Facebook (เมื่อ FacebookRichMessageService ไม่ได้ครอบคลุม)
     *
     * ใช้เฉพาะ actions ที่เราเพิ่ม/ปรับปรุงใหม่ — ai_chat_response, processing (stuck),
     * restart_from_birthdate, invalid_birthdate
     */
    protected function getFacebookFallbackQuickReplies(string $action, array $result, bool $offerFortune): array
    {
        // 🩹 (2026-05-15) Empathy-First Quick Reply Gate — TURN 1-2 ไม่ขึ้นปุ่มดูดวง
        //   user report: "ลูกค้าพูดยังไม่ได้พูดถึงดูดวง แต่บอทดันปุ่มขายมาทุกข้อความ"
        //   เดิม: ทุก ai_chat_response แสดงปุ่ม "🔮 ดูดวง" / "💎 ดูดวง" — รู้สึกฮาร์ดเซลตั้งแต่ทักทาย
        //   ใหม่: TURN < 3 + offer_fortune=false → ไม่ใส่ปุ่ม (AI Empathy-First Protocol)
        //         TURN ≥ 3 หรือ offer_fortune=true → แสดงปุ่มตามเดิม (rapport built แล้ว)
        $turnCount = (int) ($result['turn_count'] ?? 0);
        $earlyEmpathyTurn = ! $offerFortune && $turnCount > 0 && $turnCount < 3;

        return match ($action) {
            'ai_chat_response' => $earlyEmpathyTurn
                ? []
                : ($offerFortune
                ? [
                    ['content_type' => 'text', 'title' => '🔮 เริ่มดูดวง', 'payload' => 'START_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💎 ดูดวง', 'payload' => 'DEEP_FORTUNE'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
                ]
                : [
                    ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                    ['content_type' => 'text', 'title' => '💎 ดูดวง', 'payload' => 'DEEP_FORTUNE'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
                ]),

            // PAID stuck → เช็คสถานะ + คุยกับแม่หมอ
            'processing' => ($result['is_stuck'] ?? false)
                ? [
                    ['content_type' => 'text', 'title' => '🔍 เช็คสถานะ', 'payload' => 'CHECK_STATUS'],
                    // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
                ]
                : [],

            // ยูสเซ่อร์ขอเริ่มใหม่ระหว่างกรอกวันเกิด
            'restart_from_birthdate' => [
                ['content_type' => 'text', 'title' => '🔮 ดูดวง', 'payload' => 'START_FORTUNE'],
                ['content_type' => 'text', 'title' => '💎 ดูดวง', 'payload' => 'DEEP_FORTUNE'],
                // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
            ],

            // วันเกิดผิดรูปแบบ
            'invalid_birthdate', 'retry_birthdate' => [
                ['content_type' => 'text', 'title' => '🔄 เริ่มใหม่', 'payload' => 'RESTART'],
                ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL'],
                // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
            ],

            default => [],
        };
    }

    /**
     * ส่ง Response สำหรับ LINE ด้วย Flex Message
     *
     * ⚡ ปรับปรุง: ใช้ replyToken สำหรับข้อความแรก (เร็วกว่า + ฟรี)
     * replyToken จาก webhook มีอายุ 1 นาที ใช้ได้ครั้งเดียว
     */
    protected function sendLineResponse(LineFortuneService $lineService, string $userId, array $result, array $extra = []): bool
    {
        $action = $result['action'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $replyToken = $extra['reply_token'] ?? null;

        // 🔔 (2026-05-03 v2) Apply payment warning prefix (LINE)
        $message = $this->maybeApplyPaymentWarning($message, $action);

        // 🩹 (2026-05-09) Strip markdown asterisks/underscores — LINE plain text ไม่ render
        //    เคสเดียวกับ FB — *เน้น* / _italic_ โชว์ raw, strip ที่ layer นี้
        $message = $this->stripMessengerMarkdown($message);

        $result['message'] = $message;

        Log::info('LINE sendLineResponse: เริ่มจัดการ action', [
            'action' => $action,
            'user_id' => $userId,
            'has_reply_token' => ! empty($replyToken),
            'question_number' => $result['question_number'] ?? null,
            'reading_id' => $reading?->id ?? null,
        ]);

        // ⚡ ใช้ Flex Message สวยงามทุก action — ห่อด้วย try-catch เพื่อ fallback เป็น text ถ้า Flex ล้มเหลว
        try {
            $sent = match ($action) {
                // ทำนายพื้นฐานเสร็จ → ส่งคำทำนาย + Upsell Flex
                'basic_done' => $this->sendLineBasicDoneResponse($lineService, $userId, $result, $replyToken),

                // รอชำระเงิน → ส่ง Payment Flex
                'pending_payment' => $this->sendLinePaymentResponse($lineService, $userId, $result, $replyToken),

                // ทำนายละเอียดเสร็จ → ส่งทีละคำถาม
                'completed' => $this->sendLineDeepReadingResponse($lineService, $userId, $result, $replyToken),

                // Help → ส่ง Welcome Flex
                'help', 'filtered' => $this->sendLineHelpResponse($lineService, $userId, $result, $replyToken),

                // เริ่มเก็บคำถาม → ส่ง Flex เลือกหมวด
                'collecting_questions' => $this->sendLineQuestionSelectionResponse($lineService, $userId, $result, $replyToken),

                // ต้องการคำถามเพิ่ม → ส่ง Flex เลือกหมวด (ข้อถัดไป)
                'need_more_questions' => $this->sendLineQuestionSelectionResponse($lineService, $userId, $result, $replyToken),

                // สุ่มไพ่ยิปซี → ส่งรูปไพ่ (ถ้ามี) + text + quick reply
                'draw_tarot_card' => $this->sendLineTarotCardResponse($lineService, $userId, $result, $replyToken),

                // 🧘 ตั้งจิตเลือกไพ่ — ส่ง text + quick reply "พร้อม" / "ยกเลิก"
                //    LINE: label มี emoji ได้ (โชว์ในปุ่ม), text คือข้อความที่ระบบรับ → ใช้คำสะอาด
                'awaiting_tarot_intention' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🧘 พร้อมแล้ว', 'text' => 'พร้อม'],
                    ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
                ]),

                // 📅 (2026-05-01) ทวนวันเกิด → quick reply ใช่/ไม่ใช่ (LINE)
                'awaiting_birthdate_confirmation' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ ใช่ ถูกต้อง', 'text' => 'ใช่'],
                    ['label' => '❌ ไม่ใช่ พิมพ์ใหม่', 'text' => 'ไม่ใช่'],
                ]),

                // ❓ (2026-05-01) ทวนคำถาม → quick reply ใช่/ไม่ตรงคำถาม (LINE)
                'awaiting_question_confirmation' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ ใช่ ถูกต้อง', 'text' => 'ใช่'],
                    ['label' => '❌ ไม่ตรงคำถาม', 'text' => 'ไม่ตรงคำถาม'],
                ]),

                // 🃏 รอเปิดไพ่ (หลังตั้งจิตแล้ว)
                'awaiting_tarot_draw' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🃏 เปิดไพ่', 'text' => 'เปิดไพ่'],
                    ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
                ]),

                // 📜 AI rebuttal หลังบิลถูกยกเลิก
                'ai_rebuttal' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
                ]),

                // ยืนยันดูดวง → ถ้าเป็น "รอคำถาม" ส่ง TopicFlex / ถ้าเป็นปกติ ส่ง ConfirmationFlex
                'awaiting_confirmation' => $this->sendLineAwaitingResponse($lineService, $userId, $result, $replyToken),

                // ขอวันเกิด → Flex พร้อมรูปแบบ + ราคา
                'collecting_birthdate' => $this->sendLineBirthdateResponse($lineService, $userId, $result, $replyToken),

                // วันเกิดผิดรูปแบบ → Flex แจ้ง error + ตัวอย่าง
                'invalid_birthdate', 'retry_birthdate' => $this->sendLineInvalidBirthdateResponse($lineService, $userId, $result, $replyToken),

                // 🔄 ยูสเซ่อร์ขอเริ่มใหม่ระหว่างกรอกวันเกิด → ส่ง text + quick reply เริ่มใหม่
                'restart_from_birthdate' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message ?: '🔄 ยกเลิกการดูดวงรอบก่อนแล้ว — พิมพ์ "ดูดวง" เพื่อเริ่มใหม่',
                    $replyToken,
                    [
                        ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
                        ['label' => '💎 ดูดวง', 'text' => 'ดูดวง'],
                        ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                    ]
                ),

                // หมดสิทธิ์ฟรี → Flex แนะนำดูดวงละเอียดพร้อมราคา
                'ai_limit' => $this->sendLineAiLimitResponse($lineService, $userId, $result, $replyToken),

                // เช็คสิทธิ์ → Flex แสดงสิทธิ์ + ราคา
                'check_remaining' => $this->sendLineCheckRemainingResponse($lineService, $userId, $result, $replyToken),

                // สถานะ/สิทธิ์ (จาก Rich Menu) → Flex แสดงสถานะรวม
                'check_status' => $this->sendLineCheckStatusResponse($lineService, $userId, $result, $replyToken),

                // ปฏิเสธ → Flex บอกลา + ปุ่มแชร์
                'declined' => $this->sendLineDeclinedResponse($lineService, $userId, $result, 'declined', $replyToken),

                // ยกเลิก → Flex ยืนยันยกเลิก + ปุ่มดูดวงใหม่
                'cancelled' => $this->sendLineDeclinedResponse($lineService, $userId, $result, 'cancelled', $replyToken),

                // รอชำระเงิน (เตือนซ้ำ) → Flex ยอดเงิน + เวลาเหลือ
                'waiting_payment' => $this->sendLineWaitingPaymentResponse($lineService, $userId, $result, $replyToken),

                // 🔍 เช็คสถานะบิล (ผู้ใช้กด "เช็คสถานะ") — ตอบสถานะจริง + ปุ่มเช็คอีกครั้ง
                'payment_check_processing' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔄 เช็คอีกครั้ง', 'text' => 'เช็คสถานะ'],
                    ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                ]),
                'payment_check_pending' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔄 เช็คอีกครั้ง', 'text' => 'เช็คสถานะ'],
                    ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                    ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
                ]),
                'payment_check_expired' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
                    ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                ]),

                // 🔍 (2026-05-15) Fuzzy Payment Match — auto-approve flow
                'fuzzy_auto_approved' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔄 เช็คคำทำนาย', 'text' => 'เช็คสถานะ'],
                ]),

                'fuzzy_ask_confirmation' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ ใช่ ของฉัน', 'text' => 'ใช่ ยืนยันการโอน'],
                    ['label' => '❌ ไม่ใช่', 'text' => 'ไม่ใช่ของฉัน'],
                ]),

                // 🛑 (2026-05-15) ถามก่อนยกเลิก (legacy pattern)
                'cancel_reason_prompt' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message, $replyToken,
                    $result['quick_reply_options'] ?? [
                        ['label' => '🆘 โอนไม่เป็น', 'text' => 'ขอเลขบัญชี'],
                        ['label' => '💬 คุยกับแอดมิน', 'text' => 'คุยกับแม่หมอ'],
                        ['label' => '❌ ยกเลิกจริง', 'text' => 'ยืนยันยกเลิก'],
                    ]
                ),

                // 🛑 (2026-05-15 v2) AI-driven cancel dialogue — text only, no Quick Reply
                'cancel_dialog_ask', 'cancel_dialog_continue' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 🛑 (2026-05-15 v2) ยกเลิก + กลับเข้า normal chat
                'cancelled_to_chat' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message, $replyToken,
                    [
                        ['label' => '🔮 ดูดวงใหม่', 'text' => 'ดูดวง'],
                        ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                    ]
                ),

                'fuzzy_admin_alert', 'fuzzy_rejected_by_customer', 'fuzzy_approve_race_lost' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔄 เช็คอีกครั้ง', 'text' => 'เช็คสถานะ'],
                    ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                ]),

                // 📜 (2026-06-06) Consent Gate — รูปกติกา (ถ้ามี) + คำเตือน + ปุ่มยืนยัน (พร้อมโอน/ยกเลิก)
                'consent_gate' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    $imageUrl = $result['consent_image_url'] ?? null;
                    // แปลง quick_replies (FB format: title/text) → LINE format (label/text)
                    $lineQr = [];
                    foreach (($result['quick_replies'] ?? []) as $b) {
                        $lineQr[] = [
                            'label' => mb_substr($b['title'] ?? '', 0, 20),
                            'text' => $b['text'] ?? ($b['title'] ?? ''),
                        ];
                    }

                    if (! empty($imageUrl)) {
                        // รูป + คำเตือน + ปุ่ม ในชุดเดียว
                        return $lineService->sendImageAndText($userId, $imageUrl, $message, $lineQr);
                    }

                    return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $lineQr);
                })(),

                // บิลหมดอายุ → Flex แจ้ง + ปุ่มเริ่มใหม่
                'payment_expired' => $this->sendLinePaymentExpiredResponse($lineService, $userId, $result, $replyToken),

                // กำลังประมวลผล → Flex แจ้งสถานะ
                'view_reading_processing' => $this->sendLineProcessingResponse($lineService, $userId, $result, $replyToken),

                // ไม่มีคำทำนาย → Flex เชิญดูดวง
                'view_reading_empty' => $this->sendLineNoReadingResponse($lineService, $userId, $result, $replyToken),

                // ไว้ดูทีหลัง → Flex ยืนยันบันทึก
                'view_later' => $this->sendLineViewLaterResponse($lineService, $userId, $result, $replyToken),

                // ดูดวงละเอียดปิด → Flex แจ้ง
                'deep_reading_disabled' => $this->sendLineDeepDisabledResponse($lineService, $userId, $result, $replyToken),

                // 🌙 (2026-05-23) Deep 39 หมดช่วงโปร — แนบ Quick Reply Celtic 99฿ ถ้าเปิด
                'deep_promo_ended' => (function () use ($lineService, $userId, $message, $replyToken) {
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    if ($celticEnabled) {
                        return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                            ['label' => '👑 VIP 99฿', 'text' => '99'],
                            ['label' => '🙏 ไว้คราวหน้า', 'text' => 'ไม่สนใจ'],
                        ]);
                    }

                    return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
                })(),

                // ข้อผิดพลาด → Flex สวยงาม
                'error', 'retry_question' => $this->sendLineErrorResponse($lineService, $userId, $result, $replyToken),

                // ดูคำทำนายพื้นฐาน/ละเอียด → ส่ง Flex คำทำนาย
                'view_reading_basic', 'view_reading_deep' => $this->sendLineViewReadingResponse($lineService, $userId, $result, $replyToken),

                // partial (streaming) → ส่ง text ธรรมดา (Flex ถูก handle ใน FortuneConversationService แล้ว)
                'partial' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // ✅ ยืนยันชำระเงินสำเร็จ → Flex สีเขียว "ได้รับเงินแล้ว กำลังวิเคราะห์"
                'payment_confirmed_wait' => $this->sendLinePaymentConfirmedResponse($lineService, $userId, $result, $replyToken),

                // กำลังสร้างคำทำนาย (หลังชำระเงิน) → Flex แจ้งสถานะ
                'queued' => $this->sendLineQueuedResponse($lineService, $userId, $result, $replyToken),

                // ส่ง Chart Image (จาก FortuneProcessDeepReading batch mode)
                'send_chart' => $this->sendLineChartResponse($lineService, $userId, $result, $replyToken),

                // ส่งคำทำนายเชิงลึก (จาก FortuneProcessDeepReading batch mode)
                'deep_reading_result' => $this->sendLineDeepReadingResultResponse($lineService, $userId, $result, $replyToken),

                // ข้อความปิดท้าย (จาก FortuneProcessDeepReading batch mode)
                'reading_complete' => $this->sendLineReadingCompleteResponse($lineService, $userId, $result, $replyToken),

                // แจ้งคำทำนายพร้อม (จาก FortuneProcessDeepReading batch mode)
                'reading_ready' => $this->sendLineReadingReadyResponse($lineService, $userId, $result, $replyToken),

                // กำลังประมวลผล (AI ทำงานอยู่ / PAID status) → Flex แจ้งสถานะ ไม่มีปุ่มดูดวงใหม่
                'processing' => $this->sendLineProcessingResponse($lineService, $userId, $result, $replyToken),

                // ข้อความซ้ำซ้อน (mutex lock) / กำลังประมวลผลอยู่ → ส่ง text สั้นๆ
                'busy' => $lineService->sendMessageWithReplyFallback($userId, $message ?: '🌙 แม่หมอจันทรากำลังพิมพ์... กรุณารอสักครู่ ✨', $replyToken),

                // แสดงบัญชีธนาคาร → ส่ง text (ไม่มีปุ่มดูดวง)
                'bank_account_info' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // busy_processing (จาก FortuneCheckPendingReadings — แจ้งคนใช้งานมาก)
                'busy_processing' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // Keyword auto-reply จากฐานข้อมูล → ส่งตาม response_type
                'keyword_matched' => $this->sendLineKeywordResponse($lineService, $userId, $result, $replyToken),

                // AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
                'ai_redirect_deep_reading' => $this->sendLineDeepReadingRedirect($lineService, $userId, $result, $replyToken),

                // AI Chat ทั่วไป → ส่ง text + Quick Replies default (ดูดวง / คุยกับแม่หมอ)
                'ai_chat_response' => $this->sendLineAiChatResponse($lineService, $userId, $message, $replyToken, $result),

                // Gatekeeper throttle → ส่งข้อความ "รอสักครู่" แทน
                'fortune_throttled' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // แชร์ลิงก์เชิญเพื่อน / ไม่มี user / error
                'share_link', 'share_no_user', 'share_error' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // สายงาน/รายได้ → ส่ง Flex พร้อมปุ่มกดลิงก์ (ไม่ใช่ URL text)
                'downline_info', 'earnings_info' => $this->sendLineButtonLinkResponse($lineService, $userId, $message, $result, $replyToken),

                // AI ตอบไม่ได้ → ส่งข้อความพร้อม quick reply ให้เลือก "ฝาก/ไม่ฝาก"
                // ใช้ replyMessage ก่อน (เร็ว + ฟรี) → fallback เป็น pushMessage
                'ai_ask_save_question' => $this->sendLineMessageWithQuickReply(
                    $lineService, $userId, $message, $replyToken,
                    $result['quick_reply_options'] ?? [
                        ['label' => '📝 ฝากถึงแอดมิน', 'text' => 'ฝากคำถามถึงแอดมิน'],
                        ['label' => '❌ ไม่ฝาก', 'text' => 'ไม่ฝากคำถาม'],
                    ]
                ),

                // แจ้งเตือนคำทำนายพร้อม → ส่ง Flex สวยงาม (สะดุดตา + ปุ่มกดอ่าน)
                // ✅ ใช้ Flex Message แทน text ธรรมดา → ลูกค้าเห็นชัดกว่า ไม่พลาด
                // ✅ ใช้ priority push (ข้าม Gatekeeper) เพราะลูกค้าจ่ายเงินแล้ว ต้องแจ้งให้ได้
                'fortune_ready_notification' => $this->sendLineFortuneReadyNotification(
                    $lineService, $userId, $result, $replyToken
                ),

                // 🆕 (2026-04-29) Tier choice menu — ลูกค้าเลือก 1 จาก 2-3 แพคเกจ
                //   - 🎁 ฟรี (เฉพาะ first-timer + feature เปิด — flag offer_free)
                //   - 39฿ Basic Deep
                //   - 99฿ Celtic Cross (ถ้า admin เปิด)
                'tier_choice', 'tier_choice_invalid', 'tier_choice_chitchat' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    $offerFree = (bool) ($result['offer_free'] ?? false);
                    $celticOnlyIntro = (bool) ($result['celtic_only_intro'] ?? false);
                    $quickReplies = [];

                    // 🆕 (2026-05-27) Celtic-only intro — ปุ่ม "เริ่มเลย / ไว้คราวหน้า"
                    //   ลูกค้าเห็น intro แล้วเลือก: ยินยอม=เริ่มเลย / ปฏิเสธ=ไว้คราวหน้า
                    if ($celticOnlyIntro) {
                        $celticPriceLabel = (int) ($result['celtic_price'] ?? 99);
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo("✨ เริ่มเลย {$celticPriceLabel}฿", "✨ ເລີ່ມເລີຍ {$celticPriceLabel}฿"),
                            'text' => 'เริ่มเลย',
                        ];
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo('🙏 ไว้คราวหน้า', '🙏 ໄວ້ຄາວໜ້າ'),
                            'text' => FortuneLocaleService::lo('ไว้คราวหน้า', 'ໄວ້ຄາວໜ້າ'),
                        ];

                        return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $quickReplies);
                    }

                    // 🌐 localize Quick Reply labels
                    if ($offerFree) {
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo('🎁 ทำนายฟรี', '🎁 ທຳນາຍຟຣີ'),
                            'text' => FortuneLocaleService::lo('ทำนายฟรี', 'ທຳນາຍຟຣີ'),
                        ];
                    }
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo('🔹 39฿ พื้นฐาน', '🔹 39฿ ພື້ນຖານ'),
                            'text' => '39',
                        ];
                    }
                    if ($celticEnabled) {
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo('👑 VIP 99฿', '👑 VIP 99฿'),
                            'text' => '99',
                        ];
                    }
                    $quickReplies[] = [
                        'label' => FortuneLocaleService::lo('❌ ยกเลิก', '❌ ຍົກເລີກ'),
                        'text' => FortuneLocaleService::lo('ยกเลิก', 'ຍົກເລີກ'),
                    ];

                    return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $quickReplies);
                })(),

                // 💰 (2026-05-08) Pricing menu (LINE) — ส่งกล่องราคา + ปุ่มเริ่มดูดวง
                'pricing_menu' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    $pricing = $result['pricing'] ?? [];
                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($pricing['celtic_enabled'] ?? false);
                    $freeEnabled = (bool) ($pricing['free_enabled'] ?? false);

                    $quickReplies = [];
                    if ($freeEnabled) {
                        $quickReplies[] = ['label' => '🎁 ทำนายฟรี', 'text' => 'ทำนายฟรี'];
                    }
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $quickReplies[] = ['label' => '🔹 เริ่ม 39 บาท', 'text' => 'ดูดวง'];
                    }
                    if ($celticEnabled) {
                        $quickReplies[] = ['label' => '👑 VIP 99 บาท', 'text' => '99'];
                    }

                    $sent = $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $quickReplies);

                    // 🙏 (2026-05-14) AI follow-up — แม่หมออธิบาย "ค่าครู" แบบสนทนา ปรับ tone ตาม persona
                    try {
                        \App\Jobs\SendPricingFollowUpJob::dispatch('line', $userId, $pricing)
                            ->delay(now()->addSeconds(4));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Pricing followup dispatch fail (LINE)', ['err' => $e->getMessage()]);
                    }

                    return $sent;
                })(),

                // 💳 (2026-05-14) Payment info (LINE) — ส่งเลขบัญชี + QR
                'payment_info' => (function () use ($lineService, $userId, $message, $result) {
                    $qrUrl = $result['payment_qr_url'] ?? null;
                    if ($qrUrl) {
                        try {
                            $lineService->sendImage($userId, $qrUrl);
                        } catch (\Throwable $e) {
                            Log::warning('LINE: ส่ง payment QR ไม่สำเร็จ', ['err' => $e->getMessage()]);
                        }
                    }

                    return $lineService->sendMessage($userId, $message);
                })(),

                // 🎁 (2026-05-03) ทำนายฟรี — ส่งภาพไพ่ + คำทำนาย + Quick Reply [39][99][ไม่สนใจ]
                'free_card_drawn' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['tarot_image_url']);
                        } catch (\Throwable $e) {
                        }
                    }

                    $deepEnabled = $this->settings->isDeepReadingEnabled();
                    $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);
                    $deepPrice = (int) (FortuneTellingSetting::getSettings()->deep_reading_price ?? 39);
                    $celticPrice = (int) app(CelticCrossService::class)->getPrice();

                    // 🌐 localize Quick Reply labels
                    $quickReplies = [];
                    // 🌙 (2026-05-23) Deep 39฿ ปิด — ซ่อนปุ่ม
                    if ($deepEnabled) {
                        $quickReplies[] = [
                            'label' => FortuneLocaleService::lo("🔹 ดูดวง {$deepPrice}฿", "🔹 ເບິ່ງດວງ {$deepPrice}฿"),
                            'text' => '39',
                        ];
                    }
                    if ($celticEnabled) {
                        $quickReplies[] = [
                            'label' => "👑 VIP {$celticPrice}฿",
                            'text' => '99',
                        ];
                    }
                    $quickReplies[] = [
                        'label' => FortuneLocaleService::lo('🌙 ไม่สนใจ', '🌙 ບໍ່ສົນໃຈ'),
                        'text' => FortuneLocaleService::lo('ไม่สนใจ', 'ບໍ່ສົນໃຈ'),
                    ];

                    return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $quickReplies);
                })(),

                // 🌙 (2026-05-03) ลูกค้าปฏิเสธ upsell — คำลา + ปรัชญา (ไม่ฮาร์ดเซล ไม่มีปุ่ม)
                'free_card_declined' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 🎁 (2026-05-03) free flow chitchat / fail — text + tier menu QR
                'free_card_chitchat', 'free_card_draw_failed', 'free_card_ai_failed' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 🎁 (2026-05-03) ดูคำทำนายฟรีย้อนหลัง — ส่งภาพไพ่ก่อน + ข้อความ
                'view_reading_free' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['tarot_image_url']);
                        } catch (\Throwable $e) {
                        }
                    }

                    return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
                })(),

                // 🔧 (2026-05-03) bills รอชำระ — text only (LINE)
                'view_reading_celtic_pending', 'view_reading_deep_pending' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 🔒 (2026-05-03) Pay-First Gate responses — บิลค้าง resend QR / revive (LINE)
                'payment_lock_pending', 'payment_lock_revived' => $this->sendLinePaymentResponse($lineService, $userId, $result, $replyToken),

                // 💳 (2026-05-09) Stripe payment method selection (LINE Quick Reply)
                'awaiting_payment_method' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, array_map(
                    fn ($qr) => ['label' => $qr['label'] ?? $qr['text'] ?? '', 'text' => $qr['text'] ?? $qr['payload'] ?? ''],
                    $result['quick_replies'] ?? [
                        ['label' => '💚 QR ไทย', 'text' => 'PAY_METHOD_QR_THAI'],
                        ['label' => '💳 บัตร ตปท.', 'text' => 'PAY_METHOD_STRIPE'],
                    ]
                )),

                // 💳 (2026-05-09) Stripe Checkout link sent / reminder (LINE) — ลิงก์อยู่ใน text body
                'pending_stripe_payment', 'pending_stripe_payment_reminder' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '💳 จ่ายต่อ', 'text' => 'STRIPE_RESUME'],
                    ['label' => '↩️ ใช้ QR Thai', 'text' => 'PAY_METHOD_QR_THAI'],
                ]),

                // 🔒 (2026-05-03) reach revive limit — admin only (text-only)
                'payment_lock_admin' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 💎 (2026-05-03) Request-Before-Pay confirmation (LINE Quick Reply)
                'delivery_confirm_prompt', 'delivery_confirm_invalid' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ รับคำทำนาย', 'text' => 'รับคำทำนาย'],
                    ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
                ]),

                // 🆕 (2026-05-04) Pay-later reconfirm (LINE Quick Reply)
                'pay_later_reconfirm', 'pay_later_reconfirm_invalid' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ ใช่ เริ่มเลย', 'text' => 'ใช่'],
                    ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
                ]),

                // 🆕 (2026-05-04) Pay-later declined — text only
                'pay_later_declined' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 💎 ยกเลิกการรับคำทำนาย — text only
                'delivery_cancelled' => $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken),

                // 💎 deliver_with_qr — ใช้ payment template (LINE)
                'deliver_with_qr' => $this->sendLinePaymentResponse($lineService, $userId, $result, $replyToken),

                // 🔮 Celtic Cross actions (2026-04-29)
                // celtic_pending_payment_reuse → resume guard เจอบิลเก่า (2026-05-04)
                'celtic_pending_payment', 'celtic_pending_payment_reuse' => $this->sendLinePaymentResponse($lineService, $userId, $result, $replyToken),

                // celtic_card_picked → ส่งรูปไพ่ + ปุ่ม "เปิดไพ่ใบที่ N"
                // 🩹 (2026-05-05) Refactor — combine image+text ใน reply call เดียว
                //   เดิม: sendImage (push, ใช้ quota) + sendLineMessageWithQuickReply (reply)
                //         → 2 API calls + ดูค้าง ลื่นไหลไม่ดี
                //   ใหม่: replyMessage([image, text+quickReply]) → 1 call ฟรี + เร็วขึ้น
                //   + dynamic label "🃏 เปิดไพ่ใบที่ N" (เหมือน FB) — ผู้สูงอายุเข้าใจ
                //   + เพิ่ม 'celtic_restart_hint', 'celtic_already_in_session'
                // 🧾 (2026-06-01) slipok_recovered_celtic — กู้บิลด้วยสลิป (recoverCelticFromVerifiedSlip)
                //   override action แต่ response = promptNextCelticCard → ต้อง render แบบ celtic (ปุ่ม "เปิดไพ่")
                //   เดิมตกไป default → ได้ปุ่ม generic "ดูดวง" (สับสน — ลูกค้าจ่ายแล้วต้องพิมพ์ "พร้อม" เปิดไพ่)
                'celtic_card_picked', 'celtic_pick_prompt', 'celtic_chitchat_reminder', 'celtic_reset',
                'celtic_restart_hint', 'celtic_already_in_session', 'slipok_recovered_celtic' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    // 🃏 Dynamic label ตามจำนวนไพ่ที่เปิด
                    $reading = $result['reading'] ?? null;
                    $picked = method_exists($reading, 'getCelticPickedCount') ? $reading->getCelticPickedCount() : 0;
                    // 🆕 (2026-05-17) ซ่อนปุ่ม "สับใหม่" เมื่อใช้ครบโควต้า 1 ครั้งแล้ว
                    $canShuffle = $reading && method_exists($reading, 'canShuffleCelticAgain') && $reading->canShuffleCelticAgain();

                    // 🛡️ (2026-05-21) Smart quick replies — ครบ 10 ใบ → แค่ "ยุติการทำนาย"
                    //   เคสจริง: ลูกค้าเปิดครบ 10 ใบเข้า Q&A mode แล้ว แต่ปุ่ม "เปิดไพ่ใบถัดไป"
                    //            + "สับใหม่" ยังโชว์อยู่ → ลูกค้าสับสน
                    //   user spec: "ปุ่มเปิดไพ่ ยังอยู่ทั้งๆ ที่เปิดไพ่ครบแล้ว"
                    //
                    //   Format: simple {label, text} — sendMessage แปลงเป็น LINE format ภายใน
                    $quickReplies = [];
                    if ($picked >= 10) {
                        // ครบ 10 ใบ — Q&A mode → ไม่มีปุ่ม
                        // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                        //   พิมพ์คำถามต่อได้เลย / จบเองด้วยการ "พิมพ์ เลิก" ($quickReplies = [] = ไม่มีปุ่ม ปลอดภัย)
                    } else {
                        // ยังเปิดไม่ครบ → ปุ่มเปิดไพ่ + สับใหม่ (ถ้ามีสิทธิ์) + ยกเลิก
                        $nextLabel = $picked === 0 ? '🃏 เปิดไพ่ใบที่ 1' : '🃏 เปิดไพ่ใบถัดไป';
                        $quickReplies[] = ['label' => $nextLabel, 'text' => 'พร้อม'];
                        if ($canShuffle) {
                            $quickReplies[] = ['label' => '🔄 สับใหม่', 'text' => 'สับใหม่'];
                        }
                        $quickReplies[] = ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'];
                    }

                    // 🚨 (2026-05-21) ENTRY LOG — กัน silent path ไม่รู้ว่า handler เคยรันไหม
                    \Log::info('LINE Celtic handler ENTRY', [
                        'user_id' => $userId,
                        'action' => $result['action'] ?? null,
                        'reading_id' => $reading?->id,
                        'picked' => $picked,
                        'has_reply_token' => ! empty($replyToken),
                        'has_image' => ! empty($result['tarot_image_url']),
                        'msg_len' => mb_strlen($message),
                    ]);

                    // 🛡️ (2026-05-21) Force push for picking — replyMessage ไม่เสถียร
                    //   เคสจริง: ลูกค้าเลือกใบต่อไป → DB ขยับ แต่ LINE ไม่เด้ง
                    //   ROOT CAUSE 2026-05-21 (commit f7442a914 first attempt):
                    //     ensureHttps() เป็น protected → เรียกจาก channel manager throw 500
                    //     → silent error → ลูกค้าไม่เห็นอะไรเลย
                    //   Fix: ลบ messages array (ไม่ใช้แล้ว) — push ตรง sendImage handle HTTPS เอง
                    // 🚀 (2026-05-21) Combine image + text เป็น 1 push call เดียว
                    //   user spec: 'ยอมจ่าย push เลย' — no throttle, push ทันที
                    //   ใช้ sendImageAndText (atomic — 1 call ส่งทั้ง image+text+quickReply)
                    \Log::info('LINE Celtic: push (combined, no throttle)', [
                        'user_id' => $userId,
                        'reading_id' => $reading?->id,
                    ]);

                    $pushOk = false;
                    $imageUrl = $result['tarot_image_url'] ?? null;
                    try {
                        if (! empty($imageUrl)) {
                            // มีรูป → ใช้ combined push (1 call)
                            $pushOk = $lineService->sendImageAndText($userId, $imageUrl, $message, $quickReplies);
                        } else {
                            // ไม่มีรูป → text only
                            $pushOk = $lineService->sendMessage($userId, $message, [
                                'quick_replies' => $quickReplies,
                            ]);
                        }
                        \Log::info('LINE Celtic: combined push result', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'success' => $pushOk,
                            'has_image' => ! empty($imageUrl),
                            'msg_preview' => mb_substr($message, 0, 80),
                        ]);
                    } catch (\Throwable $e) {
                        \Log::error('LINE Celtic: combined push exception', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if (! $pushOk) {
                        \Log::critical('LINE Celtic: combined push ล้มเหลว — ลูกค้าไม่เห็นไพ่!', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'action' => $result['action'] ?? null,
                            'message_preview' => mb_substr($message, 0, 100),
                            'hint' => 'ตรวจ LINE token / push quota / userId',
                        ]);
                    }

                    return $pushOk;
                })(),

                // celtic_all_picked → ส่งภาพ composite + ขอ Q1 + ปุ่ม "เริ่มถามคำถาม"
                // 🆕 (2026-05-06) เพิ่ม Quick Reply ปุ่ม "เริ่มถามคำถาม" ให้ user รู้ว่าทำอะไรต่อ
                'celtic_all_picked' => (function () use ($lineService, $userId, $message, $replyToken, $result) {
                    if (! empty($result['tarot_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['tarot_image_url']);
                        } catch (\Throwable $e) {
                        }
                    }
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['celtic_summary_image_url']);
                        } catch (\Throwable $e) {
                        }
                    }

                    // 🛑 (2026-05-14 v2) ลบ "พอแค่นี้" ออกจาก opening — AI ทักเองแล้ว
                    return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
                })(),

                // 🛑 (2026-05-16) เอาปุ่ม "ถามต่อ" ออก — เหลือแค่ "ยุติการทำนาย"
                // 🛡️ (2026-05-21) Force push — replyMessage ไม่เสถียร (ลูกค้าจ่าย 99฿
                //   ทำนายเสร็จที่ server แต่ไม่ส่งถึงลูกค้าบน LINE)
                // 🆕 (2026-05-23) celtic_continue = alias หลัง user ยืนยัน "ขอคุยต่อ"
                'celtic_question_answered', 'celtic_qa_prompt_resume', 'celtic_continue' => (function () use ($lineService, $userId, $message, $result) {
                    $reading = $result['reading'] ?? null;

                    // 🚀 (2026-05-21) No throttle — user spec 'ยอมจ่าย push เลย'
                    \Log::info('LINE Celtic Q&A: force push delivery', [
                        'user_id' => $userId,
                        'action' => $result['action'] ?? null,
                        'reading_id' => $reading?->id,
                        'msg_len' => mb_strlen($message),
                    ]);

                    $textOk = false;
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    //   ส่ง plain (sendMessage guard quick_replies ว่าง) — ลูกค้าจบเองด้วยการ "พิมพ์ เลิก"
                    $lineQr = [];
                    try {
                        $textOk = $lineService->sendMessage($userId, $message, $lineQr);
                        \Log::info('LINE Celtic Q&A: sendMessage (push) result', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'success' => $textOk,
                            'msg_preview' => mb_substr($message, 0, 100),
                        ]);
                    } catch (\Throwable $e) {
                        \Log::error('LINE Celtic Q&A: sendMessage (push) exception', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // 🐛 (2026-05-28) sync retry 1 ครั้งถ้า fail — กัน transient LINE blip
                    if (! $textOk) {
                        try {
                            usleep(800000);
                            $textOk = $lineService->sendMessage($userId, $message, $lineQr);
                        } catch (\Throwable $e) {
                            // ignore — critical log + cron re-deliver จัดการต่อ
                        }
                    }

                    // 🐛 (2026-05-28) mark delivered เมื่อส่งสำเร็จ — กัน cron re-deliver ซ้ำ
                    // 🐛 (2026-05-29) mark ตรง row by sequence — เลิกเดา orderByDesc (กัน race/orphan → mark ผิด row → ซ้ำ)
                    if ($textOk && $reading) {
                        try {
                            $seq = $result['sequence'] ?? null;
                            $q = $seq !== null
                                ? $reading->celticQuestions()->where('sequence', (int) $seq)->whereNotNull('answered_at')->first()
                                : $reading->celticQuestions()->whereNotNull('answered_at')->orderByDesc('sequence')->first();
                            $q?->markDelivered();
                        } catch (\Throwable $e) {
                            \Log::debug('LINE Celtic: markDelivered fail (non-blocking)', ['error' => $e->getMessage()]);
                        }
                    }

                    if (! $textOk) {
                        \Log::critical('LINE Celtic Q&A: ส่งคำทำนายล้มเหลว — ลูกค้าไม่ได้รับ! (cron จะ re-deliver)', [
                            'user_id' => $userId,
                            'reading_id' => $reading?->id,
                            'msg_preview' => mb_substr($message, 0, 200),
                        ]);
                    }

                    // 🔢 (2026-06-05) กล่องที่ 2 — คำถามแนะนำต่อยอด + ปุ่มเลข 1️⃣2️⃣ (best-effort)
                    //   ปุ่มเลข (label/text) ไม่มีคำว่า "ดูดวง" → ไม่โดน stripFortuneStartQuickReplies
                    if ($textOk && ! empty($result['suggestion_box']) && ! empty($result['quick_replies'])) {
                        try {
                            $lineService->sendMessage($userId, $result['suggestion_box'], ['quick_replies' => $result['quick_replies']]);
                        } catch (\Throwable $e) {
                            \Log::debug('LINE Celtic: suggestion box send fail (non-blocking)', ['error' => $e->getMessage()]);
                        }
                    }

                    return $textOk;
                })(),

                // 🆕 (2026-05-17) celtic_typing_only — typing delay 60-120s pattern (LINE OA ไม่มี typing indicator)
                //   Trait dispatch SendCelticDelayedPrediction job แล้ว — channel manager no-op
                //   ลูกค้ารู้แล้วว่าต้องรอ 1-2 นาที (sendCelticThinkingAck แจ้งไว้)
                'celtic_typing_only' => true,

                // 🆕 (2026-05-23) celtic_end_confirm — 2-step confirm dialog ก่อนจบ session (LINE)
                //    user spec: "ถามก่อนว่าจะเลิกแล้วสรุปเลยจริงไหม เพราะบางคนมือไปกดผิด"
                'celtic_end_confirm' => $lineService->sendMessage($userId, $message, [
                    'quick_replies' => [
                        ['label' => '✅ ใช่ ส่งสรุปเลย', 'text' => 'ส่งสรุปเลย'],
                        ['label' => '↩️ ขอคุยต่ออีกหน่อย', 'text' => 'ขอคุยต่อ'],
                    ],
                ]),

                // 🎙️ (2026-05-08) celtic_session_ended (LINE) — ภาพ + closing
                //                 Voice ส่งผ่าน ProcessVoiceSummaryJob async (push หลัง 5-15s)
                'celtic_session_ended' => (function () use ($lineService, $userId, $message, $result, $replyToken) {
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['celtic_summary_image_url']);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
                })(),

                // 💬 (2026-05-14) Celtic chat-style conversation log — LINE
                //   ส่งภาพไพ่ + บทสนทนา (อาจหลายข้อความ) + quick reply
                'celtic_review_log' => (function () use ($lineService, $userId, $message, $result, $replyToken) {
                    if (! empty($result['celtic_summary_image_url'])) {
                        try {
                            $lineService->sendImage($userId, $result['celtic_summary_image_url']);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);

                    foreach ((array) ($result['celtic_conversation_overflow'] ?? []) as $segment) {
                        try {
                            $lineService->sendMessage($userId, $segment);
                        } catch (\Throwable $e) {
                            // ignore segment fail
                        }
                    }

                    $canAskMore = (bool) ($result['celtic_can_ask_more'] ?? false);
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    //   คุยต่อ → ไม่มีปุ่ม (พิมพ์ "เลิก" จบเอง) / จบรอบแล้ว → เหลือ "ดูดวงใหม่"
                    $replies = $canAskMore
                        ? []
                        : [['label' => '🔮 ดูดวงใหม่', 'text' => 'ดูดวง']];

                    return $this->sendLineMessageWithQuickReply(
                        $lineService,
                        $userId,
                        $canAskMore
                            ? '💬 คุยต่อได้เลย — หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบและรับสรุป 🙏'
                            : '🙏 ขอบคุณที่ใช้บริการแม่หมอจันทรานะคะ ✨',
                        null,
                        $replies
                    );
                })(),

                // Celtic actions ที่เป็น text-only
                // celtic_resume_qa → resume เข้า AWAITING_QUESTION (ลูกค้าพิมพ์คำถามเอง — ไม่ใส่ปุ่ม)
                // 🛡️ (2026-05-21) Force push — ลูกค้าต้องได้ข้อความเสมอ
                'celtic_cancelled', 'celtic_completed', 'celtic_qa_window_expired',
                'celtic_ai_failed', 'celtic_processing', 'celtic_disabled',
                'celtic_question_too_short', 'celtic_pick_failed', 'celtic_reset_denied',
                'celtic_awaiting_payment', 'celtic_bill_creation_failed',
                'celtic_resume_qa' => (function () use ($lineService, $userId, $message, $result) {
                    \Log::info('LINE Celtic text-only: force push', [
                        'user_id' => $userId,
                        'action' => $result['action'] ?? null,
                        'msg_len' => mb_strlen($message),
                    ]);

                    try {
                        return $lineService->sendMessage($userId, $message);
                    } catch (\Throwable $e) {
                        \Log::error('LINE Celtic text-only push exception', [
                            'user_id' => $userId,
                            'action' => $result['action'] ?? null,
                            'error' => $e->getMessage(),
                        ]);

                        return false;
                    }
                })(),

                // 📚 (2026-05-09) ประวัติบิล — ส่งข้อความ list + Quick Reply ปุ่มเลือกบิล
                //   handleMyBills คืน quick_replies = [['title','text','payload']] (FB format)
                //   convert title → label สำหรับ LINE Quick Reply
                'my_bills_list' => (function () use ($lineService, $userId, $message, $result, $replyToken) {
                    $rawReplies = $result['quick_replies'] ?? [];
                    if (empty($rawReplies)) {
                        return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
                    }

                    $lineReplies = array_map(fn ($r) => [
                        'label' => mb_substr($r['title'] ?? $r['label'] ?? '', 0, 20),
                        'text' => $r['text'] ?? $r['payload'] ?? '',
                    ], array_slice($rawReplies, 0, 13));

                    return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, $lineReplies);
                })(),

                // ประวัติบิลว่าง — ส่งข้อความ + Quick Reply "ดูดวง"
                'my_bills_empty' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '🔮 ดูดวงเลย', 'text' => 'ดูดวง'],
                ]),

                // อื่นๆ → Flex ข้อผิดพลาด (fallback สวยกว่า text ธรรมดา)
                // 🌍 (2026-06-03) ลูกค้าต่างชาติ — ถามยืนยัน + ปุ่ม "แน่ใจ จ่ายเงินไทยได้"
                'foreign_service_confirm' => $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                    ['label' => '✅ แน่ใจ จ่ายเงินไทยได้', 'text' => 'ยืนยันจ่ายเงินไทยได้'],
                    ['label' => '🙏 ไว้คราวหน้า', 'text' => 'ยกเลิก'],
                ]),

                default => $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken),
            };

            // ⚡ Log ถ้าส่งไม่สำเร็จ (return false) — ช่วยวิเคราะห์ปัญหา
            if (! $sent) {
                Log::warning('LINE sendLineResponse: ส่งไม่สำเร็จ', [
                    'action' => $action,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($message),
                ]);
                // fallback ส่ง text ธรรมดาถ้า Flex ส่งไม่ได้ (ลอง reply ก่อน push)
                if ($message) {
                    $lineService->sendMessageWithReplyFallback($userId, mb_substr($message, 0, 2000), $replyToken);
                }
            }

            return $sent;
        } catch (\Exception $e) {
            // ⚡ Flex Message ล้มเหลว → fallback ส่ง text ธรรมดา (ดีกว่าไม่ส่งอะไรเลย!)
            Log::error('LINE Flex Message ล้มเหลว — fallback เป็น text', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile().':'.$e->getLine(),
            ]);

            // ส่ง text ธรรมดาเป็น fallback เสมอ
            $fallbackText = $message ?: 'ระบบกำลังดำเนินการ กรุณารอสักครู่ 🙏';

            return $lineService->sendMessageWithReplyFallback($userId, mb_substr($fallbackText, 0, 2000), $replyToken);
        }
    }

    /**
     * ส่ง Response เมื่อทำนายพื้นฐานเสร็จ (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken สำหรับ chart+คำทำนาย (เร็วขึ้น)
     */
    protected function sendLineBasicDoneResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $billRef = $reading?->bill_reference;

        // ส่ง Birth Chart / Quick Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        if ($chartUrl) {
            try {
                // ⚡ ใช้ replyToken ส่ง chart + คำทำนาย รวมกัน (เร็วมาก!)
                // LINE อนุญาต max 5 messages ต่อ replyMessage
                if ($replyToken) {
                    $message = $result['message'] ?? '';
                    $parts = explode('═══════════════════════', $message);
                    $prediction = trim($parts[0] ?? $message);

                    // แบ่งคำทำนายเป็นส่วนๆ
                    $fortuneBubbles = $lineService->buildSplitFortuneMessages($prediction, $userName, $billRef);

                    $replyMessages = [
                        [
                            'type' => 'image',
                            'originalContentUrl' => $chartUrl,
                            'previewImageUrl' => $chartUrl,
                        ],
                    ];

                    // ใส่ fortune bubbles (จำกัดให้ reply รวมไม่เกิน 5)
                    $maxFortuneInReply = $this->settings->isDeepReadingEnabled() ? 3 : 4; // เผื่อที่ให้ upsell
                    $inReplyBubbles = array_slice($fortuneBubbles, 0, $maxFortuneInReply);
                    $overflowBubbles = array_slice($fortuneBubbles, $maxFortuneInReply);

                    foreach ($inReplyBubbles as $bubble) {
                        $replyMessages[] = [
                            'type' => 'flex',
                            'altText' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                            'contents' => $bubble,
                        ];
                    }

                    // เพิ่ม Upsell ถ้าเปิดดูดวงละเอียด
                    if ($this->settings->isDeepReadingEnabled() && count($replyMessages) < 5) {
                        $upsellFlex = $lineService->buildUpsellFlexMessage($userName, $this->getReadingPrice());
                        $replyMessages[] = [
                            'type' => 'flex',
                            'altText' => 'ดูดวง',
                            'contents' => $upsellFlex,
                        ];
                    }

                    $sent = $lineService->replyMessage($replyToken, $replyMessages);
                    if ($sent) {
                        // ส่วนที่เกิน replyMessage → รวมเป็น carousel แล้ว push ครั้งเดียว
                        if (! empty($overflowBubbles)) {
                            usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                            if (count($overflowBubbles) > 1) {
                                $carousel = ['type' => 'carousel', 'contents' => $overflowBubbles];
                                $lineService->sendRichMessage($userId, ['alt_text' => 'คำทำนาย (ต่อ)', 'contents' => $carousel]);
                            } else {
                                $lineService->sendRichMessage($userId, ['alt_text' => 'คำทำนาย (ต่อ)', 'contents' => $overflowBubbles[0]]);
                            }
                        }

                        return true;
                    }
                    Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (basic_done) fallback เป็น push');
                }

                $lineService->sendImage($userId, $chartUrl);
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager: Failed to send LINE chart image (basic_done)', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // แยกคำทำนายออกจากข้อความ upsell
        $message = $result['message'] ?? '';
        $parts = explode('═══════════════════════', $message);
        $prediction = trim($parts[0] ?? $message);

        // ⚡ แบ่งคำทำนายเป็นส่วนๆ (ลดปัญหา Flex ยาวเกินไม่ส่ง)
        $fortuneBubbles = $lineService->buildSplitFortuneMessages($prediction, $userName, $billRef);

        // ✅ รวมทุก bubble เป็น carousel เดียว — ป้องกัน LINE rate limit
        if (count($fortuneBubbles) > 1) {
            $carousel = ['type' => 'carousel', 'contents' => $fortuneBubbles];
            $lineService->sendRichMessage($userId, [
                'alt_text' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                'contents' => $carousel,
            ]);
        } elseif (count($fortuneBubbles) === 1) {
            $lineService->sendRichMessage($userId, [
                'alt_text' => "คำทำนายจาก{$this->settings->getFortuneBrandName()}",
                'contents' => $fortuneBubbles[0],
            ]);
        }

        // ส่ง Flex Message Upsell (เฉพาะเมื่อเปิดดูดวงละเอียด)
        if ($this->settings->isDeepReadingEnabled()) {
            $upsellFlex = $lineService->buildUpsellFlexMessage($userName, $this->getReadingPrice());

            return $lineService->sendRichMessage($userId, [
                'alt_text' => 'ดูดวง',
                'contents' => $upsellFlex,
            ]);
        }

        return true;
    }

    /**
     * ส่ง Response เมื่อรอชำระเงิน (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken
     */
    protected function sendLinePaymentResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;

        if (! $reading) {
            return $lineService->sendMessage($userId, $result['message'] ?? 'เกิดข้อผิดพลาด');
        }

        // ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งรวมตอนคำทำนายทีเดียว (ประหยัด push + ลดโอกาสค้าง)

        // ✅ ดึงโหมดแสดงช่องทางชำระเงิน (both, bank_only, promptpay_only)
        $displayMode = $this->settings->getPaymentDisplayMode();
        $showBank = $this->settings->shouldShowBankAccount();
        $showPromptpay = $this->settings->shouldShowPromptpay();

        // ดึงบัญชีธนาคารตามโหมด
        $accounts = PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->ordered()
            ->get();

        if ($accounts->isEmpty()) {
            $accounts = PaymentBankAccount::active()
                ->ordered()
                ->get();
        }

        // 🆕 (2026-05-31) โชว์บัญชีหลักบัญชีเดียว (is_default ก่อน → ไม่มีก็ตัวแรก) — เป็นมิตรกับผู้สูงอายุ
        $primaryAccount = $accounts->firstWhere('is_default', true) ?? $accounts->first();
        if ($primaryAccount) {
            $accounts = collect([$primaryAccount]);
        }

        // จัดรูปแบบตาม payment_display_mode
        $bankAccounts = $accounts->map(function ($a) use ($displayMode) {
            $info = ['account_name' => $a->account_name];

            if ($displayMode === 'promptpay_only') {
                // โหมดพร้อมเพย์อย่างเดียว — แสดง PromptPay เท่านั้น
                if ($a->hasPromptpay()) {
                    $info['bank_name'] = '📱 พร้อมเพย์';
                    $info['account_number'] = $a->promptpay_id;
                    $info['is_promptpay'] = true;
                } else {
                    return null; // ข้ามบัญชีที่ไม่มี promptpay
                }
            } elseif ($displayMode === 'bank_only') {
                // โหมดบัญชีธนาคารอย่างเดียว
                $info['bank_name'] = $a->bank_name;
                $info['account_number'] = $a->account_number;
            } else {
                // โหมด both — แสดงทั้งสอง
                $info['bank_name'] = $a->bank_name;
                $info['account_number'] = $a->account_number;
                if ($a->hasPromptpay()) {
                    $info['promptpay_id'] = $a->promptpay_id;
                }
            }

            return $info;
        })->filter()->values()->toArray();

        // ⚡ Safety: ถ้าไม่มีบัญชีเลย → ส่ง text แทน
        if (empty($bankAccounts)) {
            Log::error('FortuneChannelManager: ไม่มีบัญชีธนาคาร/พร้อมเพย์', ['display_mode' => $displayMode]);

            return $lineService->sendMessageWithReplyFallback(
                $userId,
                $result['message'] ?? 'กรุณาติดต่อแอดมินเพื่อชำระเงิน 🙏',
                $replyToken
            );
        }

        // ดึงยอดจาก uniquePaymentAmount (unique amount สำหรับเช็คผ่าน SMS payment checker)
        // ใช้ unique_amount เป็นหลัก เพราะมีทศนิยมเฉพาะสำหรับจับคู่ SMS
        $uniquePayment = $reading->uniquePaymentAmount;
        $amount = $uniquePayment
            ? (float) $uniquePayment->unique_amount
            : ((float) $reading->amount_paid ?: $this->getReadingPrice());
        $expiresAt = $uniquePayment?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference;

        // 🃏 รูปไพ่ยิปซี (เมื่อ REQUIRED_QUESTIONS=1 flow ข้าม draw_tarot_card → มาที่ pending_payment ตรง)
        $tarotImageUrl = $result['tarot_image_url'] ?? null;

        // ส่ง Birth Chart ก่อนบิล (ถ้ามี) เพื่อให้ผู้ใช้เห็นภาพดวงดาวก่อนชำระเงิน
        $chartUrl = $result['chart_image_url'] ?? null;
        $qrImageUrl = $result['payment_qr_url'] ?? null;
        $paymentFlex = $lineService->buildPaymentFlexMessage($bankAccounts, $amount, $expiresAt, $billRef);

        // ⚡ ใช้ replyToken ส่ง tarot + chart + QR + payment รวมกัน (LINE จำกัด 5 messages)
        if ($replyToken) {
            $replyMessages = [];
            // 🃏 ไพ่ยิปซีมาก่อนเสมอ (เป็นเรื่องที่ผู้ใช้คาดหวังจากการ "ตั้งจิตเลือก")
            if ($tarotImageUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $tarotImageUrl,
                    'previewImageUrl' => $tarotImageUrl,
                ];
            }
            if ($chartUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $chartUrl,
                    'previewImageUrl' => $chartUrl,
                ];
            }
            // ส่ง PromptPay QR Code (Dynamic — มียอดเงินฝังอยู่ สแกนจ่ายได้เลย)
            if ($qrImageUrl) {
                $replyMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $qrImageUrl,
                    'previewImageUrl' => $qrImageUrl,
                ];
            }
            $replyMessages[] = [
                'type' => 'flex',
                'altText' => 'ยอดโอน ฿'.number_format($amount, 2),
                'contents' => $paymentFlex,
            ];

            // 🆕 (2026-05-31) เลขบัญชีเป็นข้อความเดี่ยวๆ ต่อท้าย — กดค้างคัดลอกง่าย (เป็นมิตรกับผู้สูงอายุ)
            $copyableAccount = $result['copyable_account'] ?? null;
            if (! empty($copyableAccount)) {
                $replyMessages[] = [
                    'type' => 'text',
                    'text' => $copyableAccount,
                ];
            }

            $sent = $lineService->replyMessage($replyToken, $replyMessages);
            if ($sent) {
                return true;
            }
            Log::warning('FortuneChannelManager: replyMessage ล้มเหลว (payment) fallback เป็น push');
        }

        // Fallback: pushMessage
        if ($tarotImageUrl) {
            try {
                $lineService->sendImage($userId, $tarotImageUrl);
                usleep(500_000);
            } catch (\Exception $tarotErr) {
                Log::warning('FortuneChannelManager LINE: ส่งรูปไพ่ยิปซีก่อนบิลไม่สำเร็จ', [
                    'error' => $tarotErr->getMessage(),
                ]);
            }
        }

        if ($chartUrl) {
            try {
                $lineService->sendImage($userId, $chartUrl);
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $imgErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง chart image ก่อนบิลไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // ส่ง PromptPay QR Code ผ่าน pushMessage (fallback)
        if ($qrImageUrl) {
            try {
                $lineService->sendImage($userId, $qrImageUrl);
                usleep(500_000); // 0.5s — ลดจาก 1s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $qrErr) {
                Log::warning('FortuneChannelManager LINE: ส่ง PromptPay QR ไม่สำเร็จ', [
                    'error' => $qrErr->getMessage(),
                ]);
            }
        }

        $lineService->sendRichMessage($userId, [
            'alt_text' => 'ยอดโอน ฿'.number_format($amount, 2),
            'contents' => $paymentFlex,
        ]);

        // 🆕 (2026-05-31) เลขบัญชีเป็นข้อความเดี่ยวๆ — กดค้างคัดลอกง่าย (เป็นมิตรกับผู้สูงอายุ)
        $copyableAccount = $result['copyable_account'] ?? null;
        if (! empty($copyableAccount)) {
            try {
                $lineService->sendMessage($userId, $copyableAccount);
            } catch (\Throwable $e) {
                Log::warning('LINE: ส่งเลขบัญชี (copyable) ไม่สำเร็จ', ['error' => $e->getMessage()]);
            }
        }

        return true;
    }

    /**
     * ส่ง Response คำทำนายละเอียดทีละคำถาม (LINE)
     *
     * ใช้ Flex Message การ์ดสวยๆ แทน text ธรรมดา
     * แต่ละคำถามเป็นการ์ดแยก มีสีตามหมวด
     * ปิดท้ายด้วยการ์ดขอบคุณ + ปุ่มแชร์ + ปุ่ม engagement
     *
     * ⚡ ปรับปรุง: ลด usleep, ใช้ replyToken ถ้ามี
     */
    protected function sendLineDeepReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepReadings = $result['deep_readings'] ?? [];
        $thankYou = $result['thank_you'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ถ้าไม่มี deep_readings (format เก่า หรือ streaming thank you) → ส่ง Thank You Flex
        if (empty($deepReadings)) {
            $message = $result['message'] ?? '';

            // 🌙 (2026-05-08 v3) ถ้าเปิด Pro Session — skip Thank You Flex
            //    Pro Session opening msg ส่งผ่าน streaming แล้ว ไม่ต้อง Flex แทรก
            $suppressTemplate = ! empty($result['suppress_complete_template'] ?? false);

            // ตรวจว่าเป็นข้อความขอบคุณ
            if (! $suppressTemplate
                && (mb_strpos($message, 'ขอบคุณ') !== false || mb_strpos($message, 'ขอให้โชคดี') !== false)) {
                $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

                return $lineService->sendFlexWithReplyFallback(
                    $userId, $thankYouFlex, '🙏 ขอบคุณที่ไว้วางใจค่ะ', $replyToken
                );
            }

            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        // ส่ง Birth Chart ก่อนคำทำนาย (ถ้ามี)
        $chartUrl = $result['chart_image_url'] ?? null;
        $totalQuestions = count($deepReadings);

        // ✅ รวม messages ทั้งหมดที่ต้องส่ง → ใช้ replyToken batch 5 ข้อความแรก (ฟรี)
        // ส่วนเกิน → push (เสียโควต้า แต่ลูกค้าจ่ายเงินแล้วสมควร)
        $allMessages = [];

        // 1. Birth Chart (ถ้ามี)
        if ($chartUrl) {
            $allMessages[] = [
                'type' => 'image',
                'originalContentUrl' => $chartUrl,
                'previewImageUrl' => $chartUrl,
            ];
        }

        // 2. สร้าง Flex bubbles สำหรับแต่ละข้อ
        foreach ($deepReadings as $dr) {
            $questionNum = $dr['question_number'];
            $question = $dr['question'];
            $answer = $dr['answer'];

            // ไพ่ยิปซี (ถ้ามี) — ส่งรูป + ชื่อไพ่เป็น push ทีหลัง (เกิน 5 messages)
            $tarotCard = $dr['tarot_card'] ?? null;
            $tarotImageUrl = $tarotCard['image_url'] ?? null;
            if ($tarotImageUrl) {
                $cardName = $tarotCard['name_th'] ?? $tarotCard['card_name_th'] ?? 'ไพ่ยิปซี';
                $isReversed = $tarotCard['is_reversed'] ?? false;
                $position = $isReversed ? '(กลับหัว)' : '(หงาย)';

                $allMessages[] = [
                    'type' => 'image',
                    'originalContentUrl' => $tarotImageUrl,
                    'previewImageUrl' => $tarotImageUrl,
                    '_meta' => ['tarot' => true, 'label' => "🃏 ไพ่ข้อที่ {$questionNum}: {$cardName} {$position}"],
                ];
            }

            $flex = $lineService->buildDeepReadingFlexMessage(
                $questionNum,
                $question,
                $answer,
                $totalQuestions
            );

            $allMessages[] = [
                'type' => 'flex',
                'altText' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$question}",
                'contents' => $flex,
                '_meta' => ['question_num' => $questionNum, 'question' => $question, 'answer' => $answer],
            ];
        }

        // 3. Thank You Flex ปิดท้าย
        // 🌙 (2026-05-08 v3) ถ้าเปิด Pro Session — skip Thank You Flex
        //    เพราะ Pro Session opening msg ส่งแล้ว Thank You Flex จะรบกวน UX
        if (empty($result['suppress_complete_template'] ?? false)) {
            $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
            $allMessages[] = [
                'type' => 'flex',
                'altText' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
                'contents' => $thankYouFlex,
            ];
        }

        // ✅ ใช้ replyToken ส่ง batch 5 ข้อความแรก (ฟรี!)
        $replyBatch = array_slice($allMessages, 0, 5);
        $pushBatch = array_slice($allMessages, 5);

        // ลบ _meta ก่อนส่ง LINE API (LINE ไม่รู้จัก field นี้)
        $cleanMessages = function (array $messages): array {
            return array_map(function ($msg) {
                unset($msg['_meta']);

                return $msg;
            }, $messages);
        };

        $sentCount = 0;
        $replyUsed = false;

        if ($replyToken && ! empty($replyBatch)) {
            $sent = $lineService->replyMessage($replyToken, $cleanMessages($replyBatch));
            if ($sent) {
                $replyUsed = true;
                $sentCount += count($replyBatch);
                Log::info('LINE DeepReading: ส่ง reply batch สำเร็จ (ฟรี!)', [
                    'count' => count($replyBatch),
                    'remaining_push' => count($pushBatch),
                ]);
            }
        }

        // ถ้า reply ล้มเหลว → push ทั้งหมด
        if (! $replyUsed) {
            $pushBatch = $allMessages;
        }

        // ส่วนเกิน (หรือทั้งหมดถ้า reply ล้มเหลว) → push ทีละข้อความ
        foreach ($pushBatch as $msg) {
            $meta = $msg['_meta'] ?? [];
            unset($msg['_meta']);

            try {
                if ($msg['type'] === 'image') {
                    $lineService->sendImage($userId, $msg['originalContentUrl']);
                    // ส่ง label ไพ่ (ถ้ามี)
                    if (! empty($meta['label'])) {
                        usleep(500_000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                        $lineService->sendMessage($userId, $meta['label']);
                    }
                } elseif ($msg['type'] === 'flex') {
                    $sent = $lineService->sendRichMessage($userId, [
                        'alt_text' => $msg['altText'],
                        'contents' => $msg['contents'],
                    ]);
                    if (! $sent && ! empty($meta['answer'])) {
                        // Fallback: ส่งเป็น text
                        $qNum = $meta['question_num'] ?? '?';
                        $textMsg = "🔮 คำทำนายข้อที่ {$qNum}/{$totalQuestions}\n❓ {$meta['question']}\n\n{$meta['answer']}";
                        $lineService->sendMessage($userId, mb_substr($textMsg, 0, 5000));
                    }
                }
                $sentCount++;
                usleep(500_000); // 0.5s ระหว่าง push messages (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $sendErr) {
                Log::warning('LINE DeepReading: ส่งข้อความล้มเหลว', [
                    'type' => $msg['type'],
                    'error' => $sendErr->getMessage(),
                ]);
            }
        }

        return $sentCount > 0;
    }

    /**
     * ส่ง Response Help/Welcome (LINE)
     *
     * ⚡ ปรับปรุง: ใช้ replyToken
     */
    protected function sendLineHelpResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        // 🔒 Guard: ถ้าลูกค้ามีบิลค้าง / กำลังประมวลผล / มีคำทำนายเสร็จแล้ว
        //   → ห้ามแสดง welcome bubble "ยินดีต้อนรับ" (สับสนลูกค้าที่จ่ายเงินแล้ว)
        //   → ตอบ contextual message แทน เช่น "แม่หมอกำลังพยากรณ์" / "พิมพ์ อ่านคำทำนาย"
        $contextual = $this->buildActiveBillContextMessage(self::PLATFORM_LINE, $userId);
        if ($contextual !== null) {
            return $lineService->sendMessageWithReplyFallback($userId, $contextual['message'], $replyToken);
        }

        // 🩹 (2026-05-04) pass userId เพื่อตรวจ hasUsedFreeCard → ซ่อนการ์ดฟรีถ้าใช้แล้ว
        $welcomeFlex = $lineService->buildWelcomeFlexMessage('', $userId);

        return $lineService->sendFlexWithReplyFallback(
            $userId, $welcomeFlex, "{$this->settings->getFortuneBrandName()}ยินดีต้อนรับค่ะ", $replyToken
        );
    }

    /**
     * 🔒 ตรวจ active bill / processing / unread reading — ส่ง contextual message แทน welcome
     *
     * Returns:
     *   ['message' => string, 'quick_replies' => array<['content_type','title','payload']>]
     *   หรือ null ถ้าไม่มี context พิเศษ (caller จะ fallback ไป welcome bubble)
     *
     * Rules (ลำดับสำคัญ):
     *   1. PENDING_PAYMENT + UPA active        → "ยังไม่ได้ชำระ" + เช็คสถานะ/ยกเลิก
     *   2. PAID หรือ COMPLETED+!deep (in 30m)  → "แม่หมอกำลังพยากรณ์"
     *   3. COMPLETED+is_paid+deep (in 24h)     → "ขอบคุณ + พิมพ์ 'อ่านคำทำนาย'"
     *   4. ไม่เข้าเงื่อนไข                       → null (fallback welcome)
     */
    protected function buildActiveBillContextMessage(string $platform, string $userId): ?array
    {
        try {
            $reading = FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
                ->where(function ($q) {
                    // มี bill_reference + ไม่เก่าเกินไป (24 ชม.)
                    $q->whereNotNull('bill_reference');
                })
                ->where('updated_at', '>=', now()->subHours(24))
                ->latest('updated_at')
                ->first();

            if (! $reading) {
                return null;
            }

            $billRef = $reading->bill_reference ?? '-';
            $name = $reading->resolved_customer_name ?? 'คุณ';

            // Rule 1: PENDING_PAYMENT + UPA active → "ยังไม่ได้ชำระ"
            if ($reading->conversation_status === FortuneReading::STATUS_PENDING_PAYMENT) {
                $upa = $reading->uniquePaymentAmount;
                if ($upa && $upa->status === 'reserved' && $upa->expires_at > now()) {
                    $payAmount = number_format((float) $upa->unique_amount, 2);
                    $remainingMin = (int) max(0, now()->diffInMinutes($upa->expires_at, false));

                    return [
                        'message' => "🔔 บิลรอชำระอยู่นะคะ คุณ{$name}\n\n"
                            ."📋 เลขบิล: {$billRef}\n"
                            ."💰 ยอด: ฿{$payAmount}\n"
                            ."⏳ เหลือเวลา {$remainingMin} นาที\n\n"
                            ."👉 พิมพ์ \"เช็คสถานะ\" ดูว่าระบบตัดบิลแล้วหรือยัง\n"
                            ."👉 พิมพ์ \"โอนแล้ว\" ถ้าเพิ่งโอน\n"
                            .'👉 พิมพ์ "ยกเลิก" ถ้าต้องการยกเลิก',
                        'quick_replies' => [
                            ['content_type' => 'text', 'title' => '🔍 เช็คสถานะ', 'payload' => 'CHECK_PAYMENT_STATUS'],
                            ['content_type' => 'text', 'title' => '✅ โอนแล้ว', 'payload' => 'REPORT_PAYMENT'],
                            ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL_FORTUNE'],
                        ],
                    ];
                }
            }

            // Rule 2: PAID / processing → "แม่หมอกำลังพยากรณ์"
            $isProcessing = $reading->is_paid && empty($reading->deep_response)
                && (
                    $reading->conversation_status === FortuneReading::STATUS_PAID
                    || $reading->conversation_status === FortuneReading::STATUS_COMPLETED
                );
            if ($isProcessing) {
                $waitedMin = (int) ceil(abs(
                    ($reading->paid_at ?? $reading->updated_at)->diffInMinutes(now(), true)
                ));

                return [
                    'message' => "🌙 คุณ{$name} แม่หมอกำลังพยากรณ์ดวงดาวให้อยู่ค่ะ\n\n"
                        ."📋 เลขบิล: {$billRef}\n"
                        ."⏳ รอมาแล้ว {$waitedMin} นาที (ปกติใช้ 1-3 นาที)\n\n"
                        ."✨ จะแจ้งทันทีเมื่อคำทำนายพร้อม\n"
                        .'💡 ห้ามสร้างบิลใหม่นะคะ — กันจ่ายซ้ำ',
                    'quick_replies' => [
                        ['content_type' => 'text', 'title' => '🔍 เช็คสถานะ', 'payload' => 'CHECK_PAYMENT_STATUS'],
                    ],
                ];
            }

            // Rule 3: คำทำนายเสร็จแล้วใน 24 ชม. → "พิมพ์ อ่านคำทำนาย"
            if ($reading->is_paid && ! empty($reading->deep_response)
                && ($reading->paid_at && $reading->paid_at >= now()->subHours(24))) {
                return [
                    'message' => "🙏 ขอบคุณคุณ{$name} ที่ใช้บริการแม่หมอจันทรา\n\n"
                        ."📋 เลขบิลล่าสุด: {$billRef}\n"
                        ."🌟 คำทำนายเชิงลึกของเจ้าชะตาพร้อมแล้ว\n\n"
                        ."👉 พิมพ์ *\"อ่านคำทำนาย\"* เพื่อดูคำทำนายอีกครั้ง\n"
                        .'👉 พิมพ์ *"บิลของฉัน"* เพื่อดูประวัติบิลย้อนหลัง',
                    'quick_replies' => [
                        ['content_type' => 'text', 'title' => '🌟 อ่านคำทำนาย', 'payload' => 'READ_LAST_READING'],
                        ['content_type' => 'text', 'title' => '📚 บิลของฉัน', 'payload' => 'MY_BILLS'],
                    ],
                ];
            }

            // 🔮 Rule 4 (2026-05-04): Celtic Cross flow active → "กำลังเปิดไพ่ X/10"
            //   กัน welcome bubble โผล่กลาง flow เมื่อมี trigger 'help'/'filtered' จากเหตุอื่น
            //   (รวม: repetitive-filter false-positive, GET_STARTED, MENU_HELP)
            $celticActiveStatuses = [
                FortuneReading::STATUS_CELTIC_PICKING,
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_GENERATING,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ];
            if (in_array($reading->conversation_status, $celticActiveStatuses, true)) {
                $picked = (int) $reading->getCelticPickedCount();
                $next = $reading->getNextCelticPosition();

                if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_PICKING) {
                    $hint = $picked === 0
                        ? '👉 กดปุ่ม *"🃏 เปิดไพ่ใบที่ 1"* เพื่อเริ่ม'
                        : "👉 กดปุ่ม *\"🃏 เปิดไพ่ใบถัดไป\"* (ใบที่ {$next})";
                    $btnLabel = $picked === 0 ? '🃏 เปิดไพ่ใบที่ 1' : '🃏 เปิดไพ่ใบถัดไป';
                    // 🆕 (2026-05-17) ซ่อน "สับใหม่" เมื่อใช้ครบโควต้า 1 ครั้งแล้ว
                    $quickReplies = [['content_type' => 'text', 'title' => $btnLabel, 'payload' => 'CELTIC_READY']];
                    if (method_exists($reading, 'canShuffleCelticAgain') && $reading->canShuffleCelticAgain()) {
                        $quickReplies[] = ['content_type' => 'text', 'title' => '🔄 สับใหม่', 'payload' => 'CELTIC_RESET'];
                    }
                    $quickReplies[] = ['content_type' => 'text', 'title' => '❌ ยกเลิก', 'payload' => 'CANCEL_FORTUNE'];
                } elseif ($reading->conversation_status === FortuneReading::STATUS_CELTIC_AWAITING_QUESTION) {
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    $hint = '👉 พิมพ์คำถามที่อยากรู้มาได้เลย — แม่หมอจะอ่านพลังงานให้ (พิมพ์ *"เลิก"* เมื่อพร้อมจบ)';
                    $quickReplies = [];
                } elseif ($reading->conversation_status === FortuneReading::STATUS_CELTIC_GENERATING) {
                    $hint = '🌌 แม่หมอกำลังพิจารณาไพ่ทั้ง 10 ใบ — กรุณารอสักครู่ (~30-60 วินาที) ✨';
                    $quickReplies = [];
                } else { // CELTIC_QA_PROMPT
                    // 🛑 (2026-05-16) เอาปุ่ม "ถามต่อ" ออก — พิมพ์คำถามเข้ามาได้เลย
                    // 🛑 (2026-06-01, user) เอาปุ่ม "เลิกทำนายและสรุปผล" ออก — คนเผลอกดก่อนจบจริง
                    $hint = '👉 พิมพ์คำถามมาได้เลย — หรือพิมพ์ *"เลิก"* เพื่อจบรอบ';
                    $quickReplies = [];
                }

                return [
                    'message' => "🔮 คุณ{$name} กำลังอยู่ในรอบ Celtic Cross นะคะ\n\n"
                        ."📋 เลขบิล: {$billRef}\n"
                        ."🃏 เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n\n"
                        .$hint,
                    'quick_replies' => $quickReplies,
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('FortuneChannelManager: buildActiveBillContextMessage ล้ม (fallback to welcome)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * ส่ง Response เลือกหมวดคำถาม (LINE)
     *
     * ใช้ Flex Message การ์ดสวยๆ มีปุ่มหมวดคำถามให้กด
     * แทน text ธรรมดาที่บอกให้ "พิมพ์เองได้เลย"
     */
    protected function sendLineQuestionSelectionResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $questionNumber = $result['question_number'] ?? 1;
        // ดึงจำนวนคำถามจาก constant — ปัจจุบัน 1 ข้อ (admin ตั้งใน FortuneConversationService::REQUIRED_QUESTIONS)
        $totalQuestions = FortuneConversationService::REQUIRED_QUESTIONS;

        // ตรวจหาคำถามก่อนหน้า (ถ้าเป็นข้อ 2+)
        $previousQuestion = null;
        if ($reading && $questionNumber > 1) {
            $collected = $reading->getCollectedQuestions();
            $previousQuestion = end($collected) ?: null;
        }

        $questionFlex = $lineService->buildQuestionSelectionFlexMessage(
            $questionNumber,
            $totalQuestions,
            $userName,
            $previousQuestion
        );

        Log::info('LINE QuestionSelection: กำลังส่ง Flex เลือกหมวดคำถาม', [
            'user_id' => $userId,
            'question_number' => $questionNumber,
            'has_reply_token' => ! empty($replyToken),
            'reading_id' => $reading?->id,
        ]);

        // ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งตอนคำทำนายทีเดียว (ประหยัด push + ไม่ค้าง)
        // ส่งแค่ Flex เลือกคำถาม (ใช้ replyToken → ฟรี + เร็ว)
        return $lineService->sendFlexWithReplyFallback(
            $userId, $questionFlex, "📝 เลือกหมวดคำถามข้อที่ {$questionNumber}", $replyToken
        );
    }

    // ============================================================
    // 🆕 LINE Flex Handlers — ข้อความสวยงามทุกจุด
    // ============================================================

    /**
     * ส่ง Response awaiting_confirmation — ตรวจสอบว่าเป็น "รอคำถาม" หรือ "รอยืนยัน"
     *
     * ถ้า awaiting_type=question → ส่ง TopicFlex (เลือกหัวข้อดูดวง)
     * ถ้า awaiting_type อื่น → ส่ง ConfirmationFlex (ปกติ)
     */
    protected function sendLineAwaitingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $awaitingType = $reading?->getConversationState('awaiting_type');

        // ถ้าเป็น "รอคำถาม" → ส่ง Flex เลือกหัวข้อ
        if ($awaitingType === 'question') {
            return $this->sendLineQuestionTopicResponse($lineService, $userId, $result, $replyToken);
        }

        // ปกติ → ส่ง Flex ยืนยัน
        return $this->sendLineConfirmationResponse($lineService, $userId, $result, $replyToken);
    }

    /**
     * ส่ง Flex เลือกหัวข้อดูดวง (เมื่อผู้ใช้พิมพ์ "ดูดวง" เฉยๆ)
     */
    protected function sendLineQuestionTopicResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 1;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);

        $flex = $lineService->buildQuestionTopicFlexMessage($userName, $remaining, $isUnlimited);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔮 อยากถามเรื่องอะไรคะ?', $replyToken);
    }

    /**
     * ส่ง Flex สถานะ/สิทธิ์ (check_status จาก Rich Menu)
     *
     * แสดงข้อมูลรวม: สิทธิ์ฟรี, เครดิตพิเศษ, สถานะสมาชิก
     */
    protected function sendLineCheckStatusResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $userName = $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 0;
        $used = $result['used'] ?? 0;
        $total = $result['total'] ?? 1;
        $specialCredits = $result['special_credits'] ?? 0;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);
        $memberStatus = $result['member_status'] ?? null;
        $walletBalance = $result['wallet_balance'] ?? 0;
        $totalCommission = $result['total_commission'] ?? 0;

        $flex = $lineService->buildStatusFlexMessage(
            $userName,
            $remaining,
            $used,
            $total,
            $specialCredits,
            $isUnlimited,
            $memberStatus,
            $walletBalance,
            $totalCommission,
        );

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "✅ สถานะ: สิทธิ์ฟรี {$remaining} ครั้ง", $replyToken);
    }

    /**
     * ส่ง Flex ยืนยันดูดวง (awaiting_confirmation)
     */
    protected function sendLineConfirmationResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        // ดึงสิทธิ์จาก result (ถูกส่งมาจาก FortuneConversationService)
        $remaining = $result['remaining'] ?? (($result['show_quick_replies'] ?? false) ? 1 : 0);
        $deepReadingEnabled = $this->settings->isDeepReadingEnabled();
        $deepPrice = $this->getReadingPrice();

        $flex = $lineService->buildConfirmationFlexMessage($userName, $remaining, $deepPrice, $deepReadingEnabled);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "🔮 สวัสดีค่ะ คุณ{$userName}", $replyToken);
    }

    /**
     * ส่ง Flex ขอวันเกิด (collecting_birthdate)
     */
    protected function sendLineBirthdateResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $flex = $lineService->buildBirthdateRequestFlexMessage($deepPrice);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🎂 กรุณาบอกวันเกิดค่ะ', $replyToken);
    }

    /**
     * LINE: ส่งข้อความไพ่ยิปซี + quick reply (draw_tarot_card)
     *
     * ✅ ไม่ส่งรูปไพ่ตรงนี้ — ส่งตอนคำทำนายทีเดียว (ประหยัด push + เร็วขึ้น)
     * ส่งแค่ text บอกชื่อไพ่ + ปุ่มสุ่มไพ่
     */
    protected function sendLineTarotCardResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';

        // ส่ง text + quick reply ปุ่มสุ่มไพ่ (ใช้ replyMessage → ฟรี!)
        return $this->sendLineMessageWithQuickReply(
            $lineService, $userId, $message, $replyToken,
            [['label' => '🃏 สุ่มไพ่ยิปซี', 'text' => 'สุ่มไพ่']]
        );
    }

    /**
     * ส่ง Flex วันเกิดผิดรูปแบบ (invalid_birthdate)
     */
    protected function sendLineInvalidBirthdateResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        // ⬅️ ใช้ text + quick replies แทน Flex เดิม เพื่อให้มีปุ่ม escape
        $message = $result['message'] ?? "ไม่เข้าใจรูปแบบวันเกิด ลองใหม่:\n\n📅 วัน/เดือน/ปี เช่น 15/08/1990";

        return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
            ['label' => '🔄 เริ่มใหม่', 'text' => 'เริ่มใหม่'],
            ['label' => '❌ ยกเลิก', 'text' => 'ยกเลิก'],
            ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
        ]);
    }

    /**
     * ส่ง Flex หมดสิทธิ์ฟรี (ai_limit)
     */
    protected function sendLineAiLimitResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $flex = $lineService->buildAiLimitFlexMessage($deepPrice, $deepEnabled);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏰ สิทธิ์ฟรีหมดแล้วค่ะ', $replyToken);
    }

    /**
     * ส่ง Flex เช็คสิทธิ์ (check_remaining)
     */
    protected function sendLineCheckRemainingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $userName = $result['user_name'] ?? 'คุณ';
        $remaining = $result['remaining'] ?? 0;
        $used = $result['used'] ?? 0;
        $total = $result['total'] ?? 1;
        $isUnlimited = $result['is_unlimited'] ?? ($remaining >= 99);
        $deepPrice = $this->getReadingPrice();
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $walletBalance = $result['wallet_balance'] ?? 0;
        $totalCommission = $result['total_commission'] ?? 0;

        $flex = $lineService->buildCheckRemainingFlexMessage($userName, $remaining, $used, $total, $deepPrice, $deepEnabled, $isUnlimited, $walletBalance, $totalCommission);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "📊 สิทธิ์คงเหลือ: {$remaining}", $replyToken);
    }

    /**
     * ส่ง Flex ปฏิเสธ/ยกเลิก (declined, cancelled)
     */
    protected function sendLineDeclinedResponse(LineFortuneService $lineService, string $userId, array $result, string $type = 'declined', ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $flex = $lineService->buildDeclinedFlexMessage($userName, $type);

        $altText = $type === 'cancelled' ? '✅ ยกเลิกแล้วค่ะ' : '🙏 ไม่เป็นไรค่ะ';

        return $lineService->sendFlexWithReplyFallback($userId, $flex, $altText, $replyToken);
    }

    /**
     * ส่ง Flex รอชำระเงิน — เตือนซ้ำ (waiting_payment)
     */
    protected function sendLineWaitingPaymentResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        if (! $reading) {
            return $lineService->sendMessageWithReplyFallback($userId, $result['message'] ?? 'เกิดข้อผิดพลาด', $replyToken);
        }

        $uniquePayment = $reading->uniquePaymentAmount;
        $amount = $uniquePayment ? (float) $uniquePayment->unique_amount : $this->getReadingPrice();
        $expiresAt = $uniquePayment?->expires_at?->format('H:i') ?? '--:--';
        $billRef = $reading->bill_reference ?? '-';
        $remainingMinutes = $uniquePayment?->expires_at ? max(0, (int) now()->diffInMinutes($uniquePayment->expires_at, false)) : 0;

        $flex = $lineService->buildWaitingPaymentFlexMessage($amount, $billRef, $expiresAt, $remainingMinutes);

        // 🎯 Quick Reply ปุ่ม "เช็คสถานะ / ยกเลิก" — แนบกับ Flex เพื่อไม่ให้ผู้ใช้ต้องพิมพ์เอง
        $quickReply = [
            'items' => [
                ['type' => 'action', 'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะ', 'text' => 'เช็คสถานะ']],
                ['type' => 'action', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
            ],
        ];

        // ส่ง PromptPay QR Code ซ้ำ (ถ้ามี) เพื่อให้ผู้ใช้สแกนจ่ายได้สะดวก
        $qrImageUrl = $result['payment_qr_url'] ?? null;
        if ($qrImageUrl && $replyToken) {
            $replyMessages = [
                [
                    'type' => 'image',
                    'originalContentUrl' => $qrImageUrl,
                    'previewImageUrl' => $qrImageUrl,
                ],
                [
                    'type' => 'flex',
                    'altText' => '💰 ยอดชำระ ฿'.number_format($amount, 2),
                    'contents' => $flex,
                    'quickReply' => $quickReply,
                ],
            ];
            $sent = $lineService->replyMessage($replyToken, $replyMessages);
            if ($sent) {
                return true;
            }
            $replyToken = null; // ✅ token อาจถูกใช้แล้ว ห้ามใช้ซ้ำ
        }

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '💰 ยอดชำระ ฿'.number_format($amount, 2), $replyToken);
    }

    /**
     * ส่ง Flex บิลหมดอายุ (payment_expired)
     */
    protected function sendLinePaymentExpiredResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $deepPrice = $this->getReadingPrice();
        $flex = $lineService->buildPaymentExpiredFlexMessage($deepPrice);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏰ บิลหมดอายุแล้ว', $replyToken);
    }

    /**
     * ส่ง Flex กำลังประมวลผล (view_reading_processing)
     */
    protected function sendLineProcessingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';
        $isStuck = (bool) ($result['is_stuck'] ?? false);

        // ⏳ ถ้ารอนาน → ส่ง text + quick replies (เช็คสถานะ / คุยกับแม่หมอ)
        // แทน Flex ธรรมดา เพื่อให้มีทางออกชัดเจน
        if ($isStuck) {
            $message = $result['message'] ?? '⏳ คำทำนายใช้เวลานานกว่าปกติ';

            return $this->sendLineMessageWithQuickReply($lineService, $userId, $message, $replyToken, [
                ['label' => '🔍 เช็คสถานะ', 'text' => 'เช็คสถานะ'],
                ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
            ]);
        }

        $flex = $lineService->buildProcessingFlexMessage($billRef);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⏳ กำลังสร้างคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Flex กำลังสร้างคำทำนาย (queued — หลังชำระเงินแล้ว)
     */
    protected function sendLineQueuedResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';

        // ใช้ Processing Flex เดิม (แจ้งว่ากำลังสร้างคำทำนาย)
        $flex = $lineService->buildProcessingFlexMessage($billRef);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ ชำระเงินสำเร็จ กำลังสร้างคำทำนาย...', $replyToken);
    }

    /**
     * ส่ง Flex ยืนยันชำระเงินสำเร็จ (payment_confirmed_wait)
     *
     * ✅ ข้อความแรกที่ลูกค้าเห็นหลังจ่ายเงิน — สีเขียว "จ่ายแล้ว รอวิเคราะห์"
     * เรียกจาก SmsPaymentService หลัง confirmPayment() สำเร็จ
     */
    protected function sendLinePaymentConfirmedResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $billRef = $reading?->bill_reference ?? '-';
        $userName = $reading?->facebook_user_name ?? 'คุณ';

        // ถ้าไม่มี reading object → ลองดึง bill ref จาก message หรือ result
        if (! $reading && ! empty($result['facebook_user_id'])) {
            $reading = FortuneReading::where('facebook_user_id', $result['facebook_user_id'])
                ->where('is_paid', true)
                ->latest()
                ->first();
            $billRef = $reading?->bill_reference ?? $billRef;
            $userName = $reading?->facebook_user_name ?? $userName;
        }

        $flex = $lineService->buildPaymentConfirmedFlexMessage($billRef, $userName);

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ ชำระเงินสำเร็จ! กำลังวิเคราะห์ดวงชะตา...', $replyToken);
    }

    /**
     * ส่ง Flex ไม่มีคำทำนาย (view_reading_empty)
     */
    protected function sendLineNoReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildNoReadingFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔮 ยังไม่มีคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Flex ไว้ดูทีหลัง (view_later)
     */
    protected function sendLineViewLaterResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildViewLaterFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '✅ บันทึกแล้วค่ะ', $replyToken);
    }

    /**
     * ส่ง Flex ดูดวงละเอียดปิด (deep_reading_disabled)
     */
    protected function sendLineDeepDisabledResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildDeepReadingDisabledFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '🔒 ปิดให้บริการชั่วคราว', $replyToken);
    }

    /**
     * ส่ง Flex ข้อผิดพลาด (error)
     */
    protected function sendLineErrorResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $flex = $lineService->buildErrorFlexMessage();

        return $lineService->sendFlexWithReplyFallback($userId, $flex, '⚠️ เกิดข้อผิดพลาด', $replyToken);
    }

    /**
     * ส่ง Flex ดูคำทำนาย (view_reading_basic, view_reading_deep)
     */
    protected function sendLineViewReadingResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $action = $result['action'] ?? '';
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $chartUrl = $result['chart_image_url'] ?? $reading?->reading_image_url;

        // ⭐ ดูดวงละเอียด (เสียเงิน) → ส่งคำทำนายผ่าน replyToken ก่อน (ฟรี+เร็ว+เชื่อถือได้)
        // สำคัญ: replyToken ต้องใช้สำหรับ content ที่ลูกค้าจ่ายเงิน ไม่ใช่ chart image!
        if ($action === 'view_reading_deep' && ! empty($reading?->deep_response)) {
            // ✅ แสดงวันเวลาชำระเงินในคำทำนาย
            $paidAt = $reading->paid_at ? $reading->paid_at->format('d/m/Y H:i') : ($reading->created_at ? $reading->created_at->format('d/m/Y H:i') : null);
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->deep_response, $userName, $reading->bill_reference, $paidAt);

            // ✅ V3: ตรวจ JSON size ก่อนส่ง — ป้องกัน carousel > 50KB ที่ LINE reject
            $sent = false;

            if (count($fortuneBubbles) === 1) {
                // Bubble เดียว → ส่งตรง
                $flexContent = $fortuneBubbles[0];
                $jsonSize = strlen(json_encode($flexContent, JSON_UNESCAPED_UNICODE));

                if ($jsonSize < 45000) {
                    if ($replyToken) {
                        $sent = $lineService->replyWithFlex($replyToken, $flexContent, '🌟 คำทำนายเชิงลึก');
                        if ($sent) {
                            $replyToken = null;
                        }
                    }
                    if (! $sent) {
                        $sent = $lineService->sendRichMessagePriority($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $flexContent]);
                    }
                }
            } elseif (count($fortuneBubbles) > 1) {
                // หลาย bubbles → ลอง carousel ถ้า JSON ไม่เกิน 45KB
                $carousel = ['type' => 'carousel', 'contents' => array_slice($fortuneBubbles, 0, 12)];
                $carouselJson = json_encode($carousel, JSON_UNESCAPED_UNICODE);

                if (strlen($carouselJson) < 45000) {
                    // Carousel ขนาดพอดี → ส่ง carousel เดียว
                    if ($replyToken) {
                        $sent = $lineService->replyWithFlex($replyToken, $carousel, '🌟 คำทำนายเชิงลึก');
                        if ($sent) {
                            $replyToken = null;
                        }
                    }
                    if (! $sent) {
                        $sent = $lineService->sendRichMessagePriority($userId, ['alt_text' => '🌟 คำทำนายเชิงลึก', 'contents' => $carousel]);
                    }
                } else {
                    // ✅ Carousel ใหญ่เกิน → ส่งทีละ bubble (เหมือนที่แอดมิน resend ได้ครบ)
                    Log::info('LINE view_reading_deep: carousel ใหญ่เกิน → ส่งทีละ bubble', [
                        'reading_id' => $reading->id ?? null,
                        'bubble_count' => count($fortuneBubbles),
                        'carousel_json_size' => strlen($carouselJson),
                    ]);

                    $sent = true;
                    foreach ($fortuneBubbles as $idx => $bubble) {
                        $bubbleSent = false;
                        // bubble แรก ลองใช้ replyToken (ฟรี)
                        if ($idx === 0 && $replyToken) {
                            $bubbleSent = $lineService->replyWithFlex($replyToken, $bubble, '🌟 คำทำนายเชิงลึก');
                            if ($bubbleSent) {
                                $replyToken = null;
                            }
                        }
                        if (! $bubbleSent) {
                            $bubbleSent = $lineService->sendRichMessagePriority($userId, [
                                'alt_text' => '🌟 คำทำนายเชิงลึก (ส่วนที่ '.($idx + 1).')',
                                'contents' => $bubble,
                            ]);
                        }
                        if (! $bubbleSent) {
                            $sent = false;
                            Log::warning('LINE view_reading_deep: ส่ง bubble ที่ '.($idx + 1).' ไม่สำเร็จ', [
                                'reading_id' => $reading->id ?? null,
                            ]);
                        }
                        if ($idx < count($fortuneBubbles) - 1) {
                            usleep(500_000); // 0.5s — ลดจาก 0.8s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                        }
                    }
                }
            }

            // ✅ Fallback สุดท้าย: ถ้าทุก Flex ล้มเหลว → ส่งเป็น text ธรรมดา (ดีกว่าไม่ได้อ่าน)
            if (! $sent) {
                Log::warning('LINE view_reading_deep: Flex ทุกวิธีล้มเหลว → fallback text', [
                    'reading_id' => $reading->id ?? null,
                    'user_id' => $userId,
                    'response_len' => mb_strlen($reading->deep_response),
                ]);
                // ตัดเป็น chunks ≤ 5000 ตัวอักษร แล้วส่งทีละ chunk
                $textChunks = $lineService->splitTextForFlexPublic($reading->deep_response, 4800);
                foreach ($textChunks as $idx => $chunk) {
                    $header = $idx === 0 ? "🌟 คำทำนายเชิงลึกของคุณ{$userName}\n📋 {$reading->bill_reference}\n═══════════════════════\n\n" : "(ต่อ)\n\n";
                    $lineService->sendMessagePriority($userId, mb_substr($header.$chunk, 0, 5000));
                    if ($idx < count($textChunks) - 1) {
                        usleep(500_000); // 0.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                    }
                }
                $sent = true; // ถือว่าส่งแล้ว (อย่างน้อย text)
            }

            if ($sent) {
                Log::info('LINE view_reading_deep: ส่งคำทำนายสำเร็จ', ['reading_id' => $reading->id ?? null]);
            }

            // ส่ง chart image ทีหลัง (ไม่สำคัญเท่าคำทำนาย)
            if ($chartUrl) {
                try {
                    usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                    $lineService->sendImage($userId, $chartUrl);
                } catch (\Exception $e) {
                    Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
                }
            }

            // ส่ง Thank You ทีหลัง (ไม่สำคัญ)
            try {
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
                $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);
                $lineService->sendRichMessage($userId, ['alt_text' => '🙏 ขอบคุณค่ะ', 'contents' => $thankYouFlex]);
            } catch (\Exception $e) {
                // ไม่สำคัญ ข้ามได้
            }

            return $sent;
        }

        // ดูดวงพื้นฐาน (ฟรี) → ส่ง chart image ก่อนก็ได้
        if ($chartUrl) {
            try {
                if ($replyToken) {
                    $sent = $lineService->replyMessage($replyToken, [
                        ['type' => 'image', 'originalContentUrl' => $chartUrl, 'previewImageUrl' => $chartUrl],
                    ]);
                    if ($sent) {
                        $replyToken = null;
                    }
                } else {
                    $lineService->sendImage($userId, $chartUrl);
                }
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $e) {
                Log::warning('FortuneChannelManager: ส่ง chart image ไม่สำเร็จ (view_reading)', ['error' => $e->getMessage()]);
            }
        }

        // ดูดวงพื้นฐาน → รวมเป็น Carousel เดียว (ป้องกัน LINE rate limit)
        if (! empty($reading?->basic_response)) {
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($reading->basic_response, $userName);

            if (count($fortuneBubbles) > 1) {
                // ✅ รวมทุก bubble เป็น carousel เดียว
                $carousel = ['type' => 'carousel', 'contents' => $fortuneBubbles];
                if ($replyToken) {
                    $sent = $lineService->replyWithFlex($replyToken, $carousel, '🔮 คำทำนายล่าสุด');
                    if (! $sent) {
                        $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $carousel]);
                    }
                } else {
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $carousel]);
                }
            } elseif (count($fortuneBubbles) === 1) {
                if ($replyToken) {
                    $sent = $lineService->replyWithFlex($replyToken, $fortuneBubbles[0], '🔮 คำทำนายล่าสุด');
                    if (! $sent) {
                        $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $fortuneBubbles[0]]);
                    }
                } else {
                    $lineService->sendRichMessage($userId, ['alt_text' => '🔮 คำทำนายล่าสุด', 'contents' => $fortuneBubbles[0]]);
                }
            }

            return true;
        }

        // Fallback
        return $lineService->sendMessageWithReplyFallback($userId, $result['message'] ?? 'ไม่พบคำทำนาย', $replyToken);
    }

    /**
     * ส่ง Chart Image ให้ลูกค้า (send_chart)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     * ส่ง chart image + ข้อความแจ้งสถานะ
     */
    protected function sendLineChartResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $chartUrl = $result['chart_image_url'] ?? null;
        $message = $result['message'] ?? '';

        Log::info('LINE sendLineChartResponse: ส่ง chart image', [
            'user_id' => $userId,
            'chart_url' => $chartUrl,
            'has_message' => ! empty($message),
        ]);

        // ส่ง chart image ก่อน (ถ้ามี)
        if ($chartUrl) {
            try {
                if ($replyToken) {
                    $sent = $lineService->replyMessage($replyToken, [
                        ['type' => 'image', 'originalContentUrl' => $chartUrl, 'previewImageUrl' => $chartUrl],
                    ]);
                    if ($sent) {
                        $replyToken = null; // ใช้แล้ว ห้ามใช้ซ้ำ
                    }
                } else {
                    $lineService->sendImage($userId, $chartUrl);
                }
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            } catch (\Exception $imgErr) {
                Log::warning('LINE sendLineChartResponse: ส่ง chart image ไม่สำเร็จ', [
                    'error' => $imgErr->getMessage(),
                ]);
            }
        }

        // ส่งข้อความแจ้ง (ถ้ามี) — ใช้ text ธรรมดา (สั้นๆ)
        if ($message) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        return true;
    }

    /**
     * ส่งคำทำนายเชิงลึก (deep_reading_result)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     * ข้อความเป็น raw text (deep_response) → แบ่งเป็น Flex bubble สวยๆ
     */
    protected function sendLineDeepReadingResultResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';
        $billRef = $reading?->bill_reference ?? null;

        Log::info('LINE sendLineDeepReadingResultResponse: ส่งคำทำนายละเอียด', [
            'user_id' => $userId,
            'message_length' => mb_strlen($message),
            'has_reading' => ! empty($reading),
        ]);

        if (empty(trim($message))) {
            return false;
        }

        // ลอง parse คำทำนายจาก reading model (มี collected_questions สำหรับแบ่งเป็น Flex card แต่ละคำถาม)
        $collectedQuestions = $reading ? $reading->getCollectedQuestions() : [];

        // สร้าง Flex content
        $flexContent = null;
        $allBubbles = [];
        $altText = '🌟 คำทำนายเชิงลึก';

        if (! empty($collectedQuestions)) {
            $deepResponse = $reading->deep_response ?? $message;
            $parsedQA = $this->parseDeepResponseByQuestions($deepResponse, $collectedQuestions);

            if (! empty($parsedQA)) {
                $totalQuestions = count($parsedQA);
                $altText = "🔮 คำทำนายเชิงลึก {$totalQuestions} ข้อ";
                foreach ($parsedQA as $idx => $qa) {
                    $questionNum = $idx + 1;
                    $allBubbles[] = $lineService->buildDeepReadingFlexMessage(
                        $questionNum,
                        $qa['question'],
                        $qa['answer'],
                        $totalQuestions
                    );
                }

                if (count($allBubbles) > 1) {
                    $flexContent = ['type' => 'carousel', 'contents' => array_slice($allBubbles, 0, 12)];
                } elseif (count($allBubbles) === 1) {
                    $flexContent = $allBubbles[0];
                }
            }
        }

        // Fallback: ใช้ buildSplitFortuneMessages (แบ่ง text ยาวเป็นหลาย bubble)
        if (! $flexContent) {
            $paidAt = $reading?->paid_at ? $reading->paid_at->format('d/m/Y H:i') : null;
            $fortuneBubbles = $lineService->buildSplitFortuneMessages($message, $userName, $billRef, $paidAt);

            if (count($fortuneBubbles) > 1) {
                $flexContent = ['type' => 'carousel', 'contents' => $fortuneBubbles];
            } elseif (count($fortuneBubbles) === 1) {
                $flexContent = $fortuneBubbles[0];
            }
        }

        if (! $flexContent) {
            return false;
        }

        // ⭐ ส่งคำทำนายเชิงลึก (เสียเงิน) พร้อม retry — ต้องส่งให้ได้!
        $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);

        // 🔄 Retry 1: รอ 5 วิ แล้วลองใหม่
        if (! $sent) {
            Log::warning('LINE DeepResult: push ครั้งแรกไม่สำเร็จ → retry ใน 5 วิ', ['reading_id' => $reading->id ?? null]);
            sleep(5);
            $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);
        }

        // 🔄 Retry 2: รอ 10 วิ แล้วลองอีก
        if (! $sent) {
            Log::warning('LINE DeepResult: push ครั้งที่ 2 ไม่สำเร็จ → retry ใน 10 วิ', ['reading_id' => $reading->id ?? null]);
            sleep(10);
            $sent = $lineService->sendRichMessage($userId, ['alt_text' => $altText, 'contents' => $flexContent]);
        }

        // ⚡ Fallback: carousel ส่งไม่ได้ → ลองส่งทีละ bubble (ป้องกัน Flex size limit เกิน 50KB)
        if (! $sent && ! empty($allBubbles) && count($allBubbles) > 1) {
            Log::warning('LINE DeepResult: carousel ส่งไม่ได้ → fallback ส่งทีละ bubble', [
                'reading_id' => $reading->id ?? null,
                'bubble_count' => count($allBubbles),
            ]);
            $individualSentCount = 0;
            foreach ($allBubbles as $bubble) {
                $bubbleSent = $lineService->sendRichMessage($userId, [
                    'alt_text' => $altText,
                    'contents' => $bubble,
                ]);
                if ($bubbleSent) {
                    $individualSentCount++;
                }
                usleep(500_000); // 0.5s — ลดจาก 1.5s (ห้ามต่ำกว่า 0.5s เพราะ LINE 429)
            }
            $sent = $individualSentCount > 0;
        }

        // ⚡ Fallback สุดท้าย: ส่ง text ธรรมดา (ดีกว่าไม่ส่งอะไรเลย!)
        if (! $sent) {
            Log::warning('LINE DeepResult: Flex ไม่ได้เลย → fallback text ธรรมดา', [
                'reading_id' => $reading->id ?? null,
            ]);
            $textMessage = mb_substr($message, 0, 5000);
            $sent = $lineService->sendMessage($userId, $textMessage);
        }

        if (! $sent) {
            Log::error('LINE DeepResult: ส่งคำทำนายเชิงลึกไม่สำเร็จทุกวิธี!', ['reading_id' => $reading->id ?? null, 'user_id' => $userId]);
        }

        return $sent;
    }

    /**
     * ส่งข้อความปิดท้าย Thank You (reading_complete)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode
     */
    protected function sendLineReadingCompleteResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? $result['user_name'] ?? 'คุณ';

        $thankYouFlex = $lineService->buildThankYouFlexMessage($userName);

        return $lineService->sendRichMessage($userId, [
            'alt_text' => '🙏 ขอบคุณที่ไว้วางใจค่ะ',
            'contents' => $thankYouFlex,
        ]);
    }

    /**
     * ส่งแจ้งเตือนคำทำนายพร้อม (reading_ready)
     *
     * ใช้โดย FortuneProcessDeepReading batch mode (เมื่อ chart ส่งไปก่อนแล้ว)
     */
    protected function sendLineReadingReadyResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '🔮✨ คำทำนายของคุณพร้อมแล้วค่ะ!';

        // สร้าง Flex notification สวยๆ
        $flex = [
            'type' => 'bubble',
            'size' => 'kilo',
            'styles' => ['header' => ['backgroundColor' => $this->settings->getLineFlexPrimaryColor()]],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮✨', 'size' => 'xl', 'align' => 'center'],
                    ['type' => 'text', 'text' => 'คำทำนายพร้อมแล้วค่ะ!', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'align' => 'center', 'margin' => 'sm'],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'color' => '#555555', 'align' => 'center'],
                ],
            ],
        ];

        return $lineService->sendRichMessage($userId, [
            'alt_text' => '🔮 คำทำนายพร้อมแล้วค่ะ!',
            'contents' => $flex,
        ]);
    }

    /**
     * Parse deep_response text ตามคำถามที่เก็บไว้
     *
     * พยายาม match คำถามใน deep_response text เพื่อแยกคำตอบออกมา
     *
     * @param  string  $deepResponse  ข้อความ deep_response ทั้งหมด
     * @param  array  $collectedQuestions  คำถามที่เก็บไว้ ['ดูดวงความรัก', 'ดูดวงการเงิน']
     * @return array [['question' => '...', 'answer' => '...'], ...]
     */
    protected function parseDeepResponseByQuestions(string $deepResponse, array $collectedQuestions): array
    {
        $result = [];
        $totalQuestions = count($collectedQuestions);

        if ($totalQuestions === 0) {
            return [];
        }

        // ลอง split ด้วยรูปแบบต่างๆ ที่ AI มักใช้
        // เช่น "คำถามที่ 1:", "ข้อ 1:", "1.", "**คำถามที่ 1**" ฯลฯ
        $patterns = [
            '/(?:คำถาม(?:ที่)?\s*(\d+)\s*[:：])/u',
            '/(?:ข้อ\s*(\d+)\s*[:：])/u',
            '/(?:^|\n)\s*(\d+)\s*[.)\]]\s*/u',
            '/(?:\*{1,2}คำถาม(?:ที่)?\s*(\d+)\*{1,2}\s*[:：]?)/u',
            '/(?:═{3,})/u', // เส้นแบ่ง
        ];

        // วิธีง่ายที่สุด: split ด้วย pattern คำถามที่พบ
        // (เดิม 2 คำถาม → ตอนนี้ 1 คำถามเป็นค่า default — ยังคง pattern เดิมไว้
        //  เผื่อ admin เปลี่ยน REQUIRED_QUESTIONS เป็น 2+ ในอนาคต)
        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $deepResponse, -1, PREG_SPLIT_NO_EMPTY);

            // ลบส่วนที่เป็น header (เช่น "คำทำนายเชิงลึก", "เลขที่บิล") → เอาเฉพาะส่วนคำตอบ
            $cleanParts = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                // ข้ามส่วนที่เป็น header (สั้นมาก หรือ มีแค่สัญลักษณ์)
                if (mb_strlen($trimmed) < 20) {
                    continue;
                }
                // ข้ามบรรทัดที่เป็น header เช่น "คำทำนายเชิงลึก", "เลขที่บิล"
                if (preg_match('/^[🌟📋═*\s]*(?:คำทำนายเชิงลึก|เลขที่บิล)/u', $trimmed)) {
                    continue;
                }
                $cleanParts[] = $trimmed;
            }

            // ถ้าได้จำนวนส่วนตรงกับจำนวนคำถาม → จับคู่ได้!
            if (count($cleanParts) === $totalQuestions) {
                foreach ($collectedQuestions as $idx => $question) {
                    $result[] = [
                        'question' => $question,
                        'answer' => trim($cleanParts[$idx]),
                    ];
                }

                return $result;
            }
        }

        // ❌ parse ไม่สำเร็จ → คืน empty (จะ fallback ไปใช้ buildSplitFortuneMessages)
        Log::debug('parseDeepResponseByQuestions: parse ไม่สำเร็จ fallback ไปใช้ split', [
            'total_questions' => $totalQuestions,
            'response_length' => mb_strlen($deepResponse),
        ]);

        return [];
    }

    /**
     * ส่ง Response สำหรับ keyword match จากฐานข้อมูล (LineBotKeyword)
     *
     * รองรับ 3 response types:
     * - text: ส่งข้อความ text ผ่าน fallback (Flex สวยถ้ายาว)
     * - flex_message: ส่ง Flex Message JSON โดยตรง
     * - quick_reply: ส่ง text + quick reply buttons
     */
    protected function sendLineKeywordResponse(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $responseType = $result['response_type'] ?? 'text';
        $message = $result['message'] ?? '';

        try {
            switch ($responseType) {
                case 'flex_message':
                    $flexJson = $result['response_flex_json'] ?? null;
                    if ($flexJson) {
                        return $lineService->sendFlexWithReplyFallback(
                            $userId,
                            $flexJson,
                            mb_substr($message ?: 'ข้อมูลจากระบบ', 0, 40),
                            $replyToken
                        );
                    }

                    // fallback เป็น text ถ้าไม่มี flex json
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);

                case 'quick_reply':
                    // ส่ง text พร้อม quick reply buttons ผ่าน sendMessage
                    $quickReplyOptions = $result['quick_reply_options'] ?? [];
                    if (! empty($quickReplyOptions) && ! empty($message)) {
                        return $lineService->sendMessage($userId, $message, [
                            'quick_replies' => $quickReplyOptions,
                        ]);
                    }

                    // fallback เป็น text ถ้าไม่มี quick reply
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);

                case 'text':
                default:
                    return $this->sendLineFallbackResponse($lineService, $userId, $message, $replyToken);
            }
        } catch (\Exception $e) {
            Log::warning('Fortune: sendLineKeywordResponse error', [
                'error' => $e->getMessage(),
                'response_type' => $responseType,
                'keyword_name' => $result['keyword_name'] ?? 'unknown',
            ]);

            // fallback เป็น text ธรรมดา
            return $lineService->sendMessageWithReplyFallback($userId, $message ?: '🔮 มีอะไรให้ช่วยค่ะ?', $replyToken);
        }
    }

    /**
     * ส่งข้อความพร้อม Quick Reply ปุ่มเลือก
     *
     * ลอง replyMessage ก่อน (เร็ว + ฟรี) → fallback เป็น pushMessage
     */
    /**
     * Default quick replies ที่ติดท้ายทุก AI chat response
     *
     * ทำให้ลูกค้ามีทางเลือกเสมอ: เริ่มดูดวง / คุยกับคน
     * ถ้า AI ใส่ [OFFER_FORTUNE] tag → จะได้ปุ่มเริ่มดูดวงเด่นขึ้น
     */
    protected function getDefaultQuickReplies(bool $offerFortune = false): array
    {
        if ($offerFortune) {
            return [
                ['label' => '🔮 เริ่มดูดวง', 'text' => 'ดูดวง'],
                ['label' => '💎 ดูดวง', 'text' => 'ดูดวง'],
                ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
                ['label' => '💬 คุยต่อ', 'text' => 'ขอคำแนะนำเพิ่ม'],
            ];
        }

        return [
            ['label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
            ['label' => '💎 ดูดวง', 'text' => 'ดูดวง'],
            ['label' => '💬 คุยกับแม่หมอ', 'text' => 'คุยกับแม่หมอ'],
            ['label' => '📖 อ่านคำทำนาย', 'text' => 'ดูคำทำนาย'],
        ];
    }

    /**
     * ส่ง AI chat response พร้อม default quick replies
     *
     * ตรวจ [OFFER_FORTUNE] tag ในข้อความ AI → ถ้ามี ให้ปุ่มเริ่มดูดวงเด่นขึ้น
     */
    protected function sendLineAiChatResponse(
        LineFortuneService $lineService,
        string $userId,
        string $message,
        ?string $replyToken,
        array $result = []
    ): bool {
        // ถ้า result บ่งบอกว่า AI แนะนำเริ่มดูดวง → ใช้ quick replies แบบ offer
        $offerFortune = (bool) ($result['offer_fortune'] ?? false);

        // ตรวจ [OFFER_FORTUNE] tag ในข้อความ (fallback ถ้า result ไม่ได้ตั้ง flag)
        if (! $offerFortune && mb_strpos($message, '[OFFER_FORTUNE]') !== false) {
            $offerFortune = true;
            $message = trim(str_replace('[OFFER_FORTUNE]', '', $message));
        }

        // 🩹 (2026-05-15) Empathy-First Quick Reply Gate — TURN 1-2 ไม่ขึ้นปุ่มดูดวง
        //   เหตุผลเดียวกับ FB fallback — ลูกค้ายังไม่ได้พูดถึงดูดวง ไม่ควรเห็นปุ่มขาย
        $turnCount = (int) ($result['turn_count'] ?? 0);
        if (! $offerFortune && $turnCount > 0 && $turnCount < 3) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        return $this->sendLineMessageWithQuickReply(
            $lineService,
            $userId,
            $message,
            $replyToken,
            $this->getDefaultQuickReplies($offerFortune)
        );
    }

    protected function sendLineMessageWithQuickReply(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken, array $quickReplies): bool
    {
        // 🧹 (2026-06-01, user) เอาปุ่ม generic "ดูดวง" (เริ่มบิลใหม่) ออกจากทุกกล่อง LINE ยกเว้น welcome
        //   user: "ปุ่มดูดวงในกล่องที่ไม่เกี่ยวกับ welcome มันสับสน" — ลูกค้ากดทับ flow (เช่นตอนจะส่งสลิป → เปิดบิลใหม่)
        //   • กล่อง welcome = Flex แยก (buildWelcomeFlexMessage) ไม่ผ่าน method นี้ → ปุ่ม welcome อยู่ครบ
        //   • ปุ่ม "ราคา/แพคเกจ" (label มี ฿/บาท/ตัวเลข = ปุ่มจ่ายเงินจริง) เก็บไว้ — ลบแล้วซื้อไม่ได้
        $quickReplies = $this->stripFortuneStartQuickReplies($quickReplies);

        // สร้าง Quick Reply items
        $quickReplyItems = [];
        foreach ($quickReplies as $reply) {
            $quickReplyItems[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply,
                    'text' => $reply['text'] ?? $reply,
                ],
            ];
        }

        // ⚠️ ถ้ากรองแล้วไม่เหลือปุ่ม → ส่ง text เปล่า (LINE API ห้าม quickReply.items ว่าง)
        if (empty($quickReplyItems)) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        $textMessage = [
            'type' => 'text',
            'text' => $message,
            'quickReply' => ['items' => $quickReplyItems],
        ];

        // ลอง replyMessage ก่อน (เร็วกว่า + ฟรี)
        if ($replyToken) {
            $result = $lineService->replyMessage($replyToken, [$textMessage]);
            if ($result) {
                return true;
            }
        }

        // Fallback: pushMessage พร้อม quick replies (ที่กรองแล้ว)
        return $lineService->sendMessage($userId, $message, [
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * 🧹 (2026-06-01) กรองปุ่ม quick reply "ดูดวง" แบบ generic (เริ่มบิลใหม่) ออกจากกล่อง LINE
     *   เก็บไว้: ปุ่มที่มีราคา (label มี ฿/บาท/ตัวเลข = ปุ่มซื้อจริง) + ปุ่มอื่นที่ไม่ใช่ดูดวง (คุยกับแม่หมอ/อ่านคำทำนาย ฯลฯ)
     *   welcome เป็น Flex แยก ไม่ผ่านที่นี่ → ปุ่ม welcome ไม่ถูกแตะ
     *
     * @param  array  $quickReplies  รายการปุ่ม (['label'=>..,'text'=>..] หรือ string)
     * @return array ปุ่มที่กรองแล้ว
     */
    protected function stripFortuneStartQuickReplies(array $quickReplies): array
    {
        return array_values(array_filter($quickReplies, function ($r) {
            $label = is_array($r) ? (string) ($r['label'] ?? '') : (string) $r;
            $isFortuneStart = mb_strpos($label, 'ดูดวง') !== false;
            $hasPrice = preg_match('/[0-9฿]|บาท/u', $label) === 1;

            // ลบเฉพาะปุ่ม "ดูดวง" ที่ไม่มีราคา (generic start) — ที่เหลือเก็บหมด
            return ! ($isFortuneStart && ! $hasPrice);
        }));
    }

    /**
     * ส่ง LINE Quick Reply แบบ priority (ข้าม Gatekeeper)
     *
     * ใช้สำหรับแจ้งเตือนสำคัญหลังชำระเงิน เช่น "คำทำนายพร้อมแล้ว"
     * ✅ ลอง replyMessage ก่อน (ฟรี) → fallback pushMessagePriority (ข้าม Gatekeeper)
     */
    protected function sendLinePriorityQuickReply(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken, array $quickReplies): bool
    {
        // สร้าง Quick Reply items
        $quickReplyItems = [];
        foreach ($quickReplies as $reply) {
            $quickReplyItems[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply,
                    'text' => $reply['text'] ?? $reply,
                ],
            ];
        }

        $textMessage = [
            'type' => 'text',
            'text' => $message,
            'quickReply' => ['items' => $quickReplyItems],
        ];

        // ลอง replyMessage ก่อน (ฟรี)
        if ($replyToken) {
            $result = $lineService->replyMessage($replyToken, [$textMessage]);
            if ($result) {
                return true;
            }
        }

        // ✅ Fallback: pushMessagePriority (ข้าม Gatekeeper — ลูกค้าจ่ายเงินแล้ว ต้องส่งให้ได้)
        Log::info('LINE sendLinePriorityQuickReply: ใช้ priority push แจ้งคำทำนายพร้อม', [
            'user_id' => $userId,
        ]);

        return $lineService->sendMessagePriority($userId, $message, [
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * ส่ง LINE Flex แจ้ง "คำทำนายพร้อมแล้ว" (fortune_ready_notification)
     *
     * ✅ ใช้ Flex Message สวยงาม (สีม่วง+ทอง) แทน text ธรรมดา
     * ✅ มีปุ่ม "อ่านคำทำนาย" กดได้ทันที (เหมือน Facebook Button Template)
     * ✅ ใช้ priority push (ข้าม Gatekeeper) เพราะลูกค้าจ่ายเงินแล้ว
     * ✅ Fallback เป็น text ถ้า Flex ล้มเหลว
     */
    protected function sendLineFortuneReadyNotification(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $reading = $result['reading'] ?? null;
        $userName = $reading?->facebook_user_name ?? 'คุณ';
        $billRef = $reading?->bill_reference;
        $message = $result['message'] ?? '';

        try {
            // ✅ สร้าง Flex Message สวยงาม
            $flex = $lineService->buildFortuneReadyFlexMessage($userName, $billRef);

            // ลอง replyToken ก่อน (ฟรี + เร็ว)
            $sent = false;
            if ($replyToken) {
                $sent = $lineService->replyWithFlex($replyToken, $flex, '🔮 คำทำนายพร้อมแล้ว!');
            }

            // Fallback: pushMessagePriority (ข้าม Gatekeeper — ลูกค้าจ่ายเงินแล้ว)
            if (! $sent) {
                $sent = $lineService->sendRichMessagePriority($userId, [
                    'alt_text' => '🔮 คำทำนายเชิงลึกพร้อมแล้ว! กดอ่านได้เลยค่ะ',
                    'contents' => $flex,
                ]);
            }

            if ($sent) {
                Log::info('LINE sendLineFortuneReadyNotification: ส่ง Flex สำเร็จ', [
                    'user_id' => $userId,
                    'reading_id' => $reading?->id,
                    'used_reply' => empty($replyToken) ? false : $sent,
                ]);

                return true;
            }
        } catch (\Exception $flexErr) {
            Log::warning('LINE sendLineFortuneReadyNotification: Flex ล้มเหลว → fallback text', [
                'user_id' => $userId,
                'error' => $flexErr->getMessage(),
            ]);
        }

        // ✅ Fallback: ส่งเป็น text + quick replies (ถ้า Flex ล้มเหลว)
        Log::info('LINE sendLineFortuneReadyNotification: fallback text+quick_replies', [
            'user_id' => $userId,
        ]);

        return $lineService->sendMessagePriority($userId, $message ?: "🔮✨ คุณ{$userName}คะ คำทำนายพร้อมแล้วค่ะ!\n\nกดปุ่ม 'อ่านคำทำนาย' ด้านล่างเลยค่ะ ✨", [
            'quick_replies' => [
                ['label' => '📖 อ่านคำทำนาย', 'text' => 'อ่านคำทำนาย'],
                ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
            ],
        ]);
    }

    /**
     * ส่ง LINE Flex Message พร้อมปุ่มลิงก์ (URI action)
     *
     * สำหรับ downline_info, earnings_info — แสดงข้อความ + ปุ่มกดไปเว็บ
     */
    protected function sendLineButtonLinkResponse(LineFortuneService $lineService, string $userId, string $message, array $result, ?string $replyToken = null): bool
    {
        $buttons = $result['buttons'] ?? [];
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        // สร้าง Flex buttons
        $flexButtons = [];
        foreach ($buttons as $btn) {
            $flexButtons[] = [
                'type' => 'button',
                'style' => 'primary',
                'color' => $primaryColor,
                'height' => 'sm',
                'margin' => 'sm',
                'action' => [
                    'type' => 'uri',
                    'label' => mb_substr($btn['label'], 0, 20),
                    'uri' => $btn['url'],
                ],
            ];
        }

        $flex = [
            'type' => 'bubble',
            'styles' => [
                'header' => ['backgroundColor' => $primaryColor],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $brandName,
                        'color' => '#FFFFFF',
                        'size' => 'sm',
                        'weight' => 'bold',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                        'wrap' => true,
                        'size' => 'sm',
                        'color' => '#333333',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => $flexButtons,
            ],
        ];

        $altText = mb_substr($message, 0, 100);

        // ✅ ใช้ replyMessage เท่านั้น — ไม่ push (ประหยัดโควต้า)
        // เป็นการตอบ user message → replyToken ควรใช้ได้เสมอ
        if ($replyToken) {
            $sent = $lineService->replyWithFlex($replyToken, $flex, $altText);
            if ($sent) {
                return true;
            }
            $replyToken = null; // ✅ token อาจถูกใช้แล้ว ห้ามใช้ซ้ำ
        }

        // Fallback: ส่ง text ธรรมดา (push เพราะ replyToken ใช้แล้วหรือไม่มี)
        return $lineService->sendMessage($userId, $message);
    }

    /**
     * ส่ง Facebook Button Template พร้อมปุ่มลิงก์ (web_url)
     *
     * สำหรับ downline_info, earnings_info — แสดงข้อความ + ปุ่มกดไปเว็บ
     */
    protected function sendFacebookButtonLinkResponse(FacebookWebhookService $fbService, string $userId, string $message, array $result): bool
    {
        $buttons = $result['buttons'] ?? [];

        if (empty($buttons)) {
            return $fbService->sendMessage($userId, $message);
        }

        // สร้าง Facebook web_url buttons (สูงสุด 3 ปุ่ม)
        $fbButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $fbButtons[] = [
                'type' => 'web_url',
                'url' => $btn['url'],
                'title' => mb_substr($btn['label'], 0, 20),
            ];
        }

        // ส่ง Button Template
        try {
            return $fbService->sendButtonTemplate($userId, mb_substr($message, 0, 640), $fbButtons);
        } catch (\Exception $e) {
            Log::warning('Facebook: Button Template ล้มเหลว — fallback text', [
                'error' => $e->getMessage(),
            ]);

            return $fbService->sendMessage($userId, $message);
        }
    }

    /**
     * Facebook: แจ้ง "คำทำนายพร้อมแล้ว" พร้อมปุ่ม "อ่านคำทำนาย" โดดเด่น
     *
     * ใช้ Button Template เพื่อให้ลูกค้ากดปุ่มเพียงครั้งเดียวเปิดคำทำนายได้
     * ไม่ใช่ quick replies (ที่ Messenger อาจซ่อนหลังส่งข้อความใหม่)
     */
    protected function sendFacebookFortuneReadyNotification(FacebookWebhookService $fbService, string $userId, string $message, array $result): bool
    {
        $defaultMessage = "🔮✨ คำทำนายเชิงลึกพร้อมแล้ว!\n\nกดปุ่มด้านล่างเพื่ออ่านคำทำนายได้เลย";
        $text = $message ?: $defaultMessage;

        return $fbService->sendButtonTemplate($userId, [
            'template_type' => 'button',
            'text' => mb_substr($text, 0, 640), // button template text max 640 chars
            'buttons' => [
                ['type' => 'postback', 'title' => '📖 อ่านคำทำนาย', 'payload' => 'VIEW_READING'],
                ['type' => 'postback', 'title' => '⏰ ไว้ดูทีหลัง', 'payload' => 'VIEW_LATER'],
                // 🧹 (2026-05-01) ลบปุ่ม "💬 คุยกับแม่หมอ" — ใช้ keyword detection แทน
            ],
        ]);
    }

    /**
     * Facebook: ข้อความขอบคุณ + อวยพรหลังทำนายจบ
     *
     * แยกจากข้อความทำนาย (reading_complete ถูกส่งหลัง view_reading_deep)
     * รวม: ขอบคุณ + อวยพร + ลิงก์ชวนเพื่อนได้เงิน + ลิงก์แชร์เพจ
     */
    protected function sendFacebookReadingCompleteResponse(FacebookWebhookService $fbService, string $userId, array $result): bool
    {
        $reading = $result['reading'] ?? null;
        $rawName = $reading?->facebook_user_name ?? $result['user_name'] ?? '';

        // 🎯 Phase E — กัน "คุณคุณ" ถ้าชื่อเป็น 'คุณ' (fallback) → ละเว้น prefix
        $greet = ($rawName !== '' && $rawName !== 'คุณ') ? "คุณ{$rawName}" : '';

        $appUrl = rtrim(config('app.url', 'https://main.thaiprompt.online'), '/');
        $recruitUrl = $appUrl.'/user/fortune-referral/recruit';
        $pageId = $this->settings->facebook_page_id ?? null;
        $pageUrl = $pageId ? "https://www.facebook.com/{$pageId}" : $appUrl;

        // 🎯 Phase E — เพิ่ม "อ่านคำทำนายล่าสุด" hint + เน้นส่วนแบ่งการตลาดเมื่อชวนเพื่อน
        $thankYouLine = $greet !== ''
            ? "🙏 ขอบคุณที่ไว้วางใจหมอจันทรา {$greet}"
            : '🙏 ขอบคุณที่ไว้วางใจหมอจันทรา';

        $text = "{$thankYouLine}\n\n"
            ."✨ ขอให้โชคดี สุขภาพแข็งแรง การงานการเงินเจริญรุ่งเรือง สมหวังทุกประการ\n\n"
            ."📖 อยากอ่านคำทำนายอีกรอบ → พิมพ์ \"อ่านคำทำนายล่าสุด\"\n\n"
            .'📢 ถ้าคำทำนายถูกใจ — ชวนเพื่อนมาดูดวง ได้ **ส่วนแบ่งการตลาด** เข้า Wallet ทันที!';

        return $fbService->sendButtonTemplate($userId, [
            'template_type' => 'button',
            'text' => mb_substr($text, 0, 640),
            'buttons' => [
                ['type' => 'web_url', 'title' => '📢 ชวนเพื่อน/รับส่วนแบ่ง', 'url' => $recruitUrl],
                ['type' => 'web_url', 'title' => '📤 แชร์เพจให้เพื่อน', 'url' => $pageUrl],
                ['type' => 'postback', 'title' => '📖 อ่านคำทำนายล่าสุด', 'payload' => 'VIEW_LAST_READING'],
            ],
        ]);
    }

    /**
     * Facebook: AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
     *
     * ส่งข้อความ AI ตอบก่อน (แนะนำบริการ) แล้วเรียก startDeepReadingFlow()
     * ส่ง birthdate collection response ตามหลัง (เป็น 2 ข้อความต่อกัน)
     */
    protected function sendFacebookDeepReadingRedirect(FacebookWebhookService $fbService, FacebookRichMessageService $richService, string $userId, array $result): bool
    {
        $message = $result['message'] ?? '';

        // ส่งข้อความ AI ตอบก่อน (แนะนำบริการดูดวงเชิงลึก)
        if (! empty($message)) {
            $fbService->sendMessage($userId, $message);
            usleep(500000);
        }

        // เริ่ม deep reading flow → ได้ result ใหม่ (collecting_birthdate)
        $deepResult = $this->conversationService->startDeepReadingFlowPublic($userId);

        if (($deepResult['action'] ?? '') === 'deep_reading_disabled') {
            // ดูดวงเชิงลึกปิดอยู่ → แจ้งผู้ใช้
            return $fbService->sendMessage($userId, $deepResult['message'] ?? 'บริการดูดวงเชิงลึกปิดให้บริการชั่วคราวค่ะ');
        }

        // ส่ง response ตาม action ที่ได้ (ปกติจะเป็น collecting_birthdate)
        return $this->sendFacebookResponse($fbService, $userId, $deepResult);
    }

    /**
     * LINE: AI detect intent ดูดวงเชิงลึก → ส่งข้อความ AI + redirect เข้า deep reading flow
     */
    protected function sendLineDeepReadingRedirect(LineFortuneService $lineService, string $userId, array $result, ?string $replyToken = null): bool
    {
        $message = $result['message'] ?? '';

        // ส่งข้อความ AI ตอบก่อน (แนะนำบริการดูดวงเชิงลึก)
        if (! empty($message)) {
            $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
            $replyToken = null; // ใช้ replyToken ได้ครั้งเดียว
            usleep(500000);
        }

        // เริ่ม deep reading flow → ได้ result ใหม่ (collecting_birthdate)
        $deepResult = $this->conversationService->startDeepReadingFlowPublic($userId);

        if (($deepResult['action'] ?? '') === 'deep_reading_disabled') {
            return $lineService->sendMessageWithReplyFallback($userId, $deepResult['message'] ?? 'บริการดูดวงเชิงลึกปิดให้บริการชั่วคราวค่ะ', $replyToken);
        }

        // ส่ง response ตาม action ที่ได้ (ปกติจะเป็น collecting_birthdate)
        return $this->sendLineResponse($lineService, $userId, $deepResult, ['reply_token' => $replyToken]);
    }

    /**
     * ส่ง Flex สำหรับข้อความ default (fallback)
     *
     * แทนที่จะส่ง text ธรรมดา → ส่ง Flex message ที่ดูดี
     * ถ้าข้อความสั้น → ส่ง text ปกติ
     */
    protected function sendLineFallbackResponse(LineFortuneService $lineService, string $userId, string $message, ?string $replyToken = null): bool
    {
        // ถ้าไม่มีข้อความ → ส่งข้อความเริ่มต้น
        if (empty(trim($message))) {
            $message = '🔮 สวัสดีค่ะ พิมพ์คำถามมาได้เลยนะคะ';
        }

        // ถ้าข้อความสั้นมาก → ส่ง text ปกติ
        if (mb_strlen($message) < 50) {
            return $lineService->sendMessageWithReplyFallback($userId, $message, $replyToken);
        }

        // ถ้ายาว → ส่ง Flex สวยๆ
        $brandName = $this->settings->getFortuneBrandName();
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        $flex = [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => $primaryColor]],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'lg', 'flex' => 0],
                    ['type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md', 'justifyContent' => 'center', 'contents' => [
                        ['type' => 'text', 'text' => "{$brandName}ดูดวง", 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold'],
                    ]],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'color' => '#333333', 'lineSpacing' => '6px'],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'horizontal', 'spacing' => 'md', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => $primaryColor, 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง']],
                ],
            ],
        ];

        return $lineService->sendFlexWithReplyFallback($userId, $flex, "🔮 ข้อความจาก{$brandName}", $replyToken);
    }

    /**
     * ดึง Quick Replies ตาม action
     */
    protected function getQuickReplies(string $action): array
    {
        return match ($action) {
            'awaiting_confirmation' => [
                ['label' => '🔮 ดูเลย', 'text' => 'ดู'],
                ['label' => 'ไม่ต้องการ', 'text' => 'ไม่'],
            ],
            'basic_done' => $this->settings->isDeepReadingEnabled()
                ? [
                    ['label' => '✨ ต้องการ', 'text' => 'ดูดวง'],
                    ['label' => 'ไม่ต้องการ', 'text' => 'ไม่ต้องการ'],
                ]
                : [],
            'collecting_birthdate' => [
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            'pending_payment' => [
                ['label' => '🏦 ดูบัญชี', 'text' => 'บัญชี'],
                ['label' => 'ยกเลิก', 'text' => 'ยกเลิก'],
            ],
            'reading_ready' => [
                ['label' => '🔮 ดูเลย', 'text' => 'ดูคำทำนาย'],
                ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
            ],
            'payment_confirmed_wait' => [],
            default => [],
        };
    }

    /**
     * ตรวจสอบว่า platform รองรับหรือไม่
     */
    public function isPlatformSupported(string $platform): bool
    {
        return in_array($platform, [self::PLATFORM_FACEBOOK, self::PLATFORM_LINE]);
    }

    /**
     * ดึงรายการ platform ที่รองรับ
     */
    public function getSupportedPlatforms(): array
    {
        return [
            self::PLATFORM_FACEBOOK => [
                'name' => 'Facebook Messenger',
                'icon' => 'fab fa-facebook-messenger',
                'color' => '#0084FF',
                'supports_rich' => false,
            ],
            self::PLATFORM_LINE => [
                'name' => 'LINE Official Account',
                'icon' => 'fab fa-line',
                'color' => '#00B900',
                'supports_rich' => true,
            ],
        ];
    }

    /**
     * ส่งคำทำนายละเอียดเมื่อชำระเงินสำเร็จ
     *
     * ส่งคำทำนายทีละคำถาม คู่กับคำตอบ
     * ให้ผู้ใช้อ่านทีละข้อ น่าติดตาม
     */
    public function sendDeepReadingAfterPayment(FortuneReading $reading): array
    {
        // ✅ ตรวจจับ LINE user จาก ID format เป็น fallback (กรณี reading เก่าที่ไม่มี platform)
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform;
        if (! $platform) {
            $platform = (preg_match('/^U[0-9a-f]{32}$/i', $userId)) ? self::PLATFORM_LINE : self::PLATFORM_FACEBOOK;
        }

        // Dispatch background job → ไม่ติด web server timeout / webhook 5s timeout
        // Job จะ: confirmPayment → สร้าง chart → สร้างคำทำนาย → ส่ง Messenger → save DB
        ProcessDeepFortuneReadingJob::dispatchSmart(
            $reading->id, null, $platform, $userId
        );

        Log::info('FortuneChannelManager: dispatch ProcessDeepFortuneReadingJob', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'user_id' => $userId,
        ]);

        return [
            'action' => 'queued',
            'message' => null,
            'reading' => $reading,
            'streaming' => true,
        ];
    }

    /**
     * 🔔 (2026-05-03 v2) Apply payment warning prefix from PayFirstGateTrait
     *
     * อ่าน FortuneConversationService::$pendingPaymentWarning (set โดย gate)
     * Prepend ลง message ยกเว้น actions ที่เกี่ยวกับ payment โดยตรง
     * Clear static หลัง apply เพื่อกัน leak ไป next request
     *
     * @param  string|null  $message  ข้อความเดิม
     * @param  string  $action  action key เพื่อตัดสินใจว่าจะ apply หรือข้าม
     * @return string|null ข้อความที่ใส่ warning แล้ว
     */
    protected function maybeApplyPaymentWarning(?string $message, string $action): ?string
    {
        $warning = FortuneConversationService::$pendingPaymentWarning;
        if (empty($warning) || empty($message)) {
            return $message;
        }

        // ⛔ Actions ที่ไม่ apply warning (เกี่ยวกับ payment โดยตรง — ลูกค้ารู้อยู่แล้ว)
        $skipActions = [
            'pending_payment', 'celtic_pending_payment', 'celtic_pending_payment_reuse',
            'waiting_payment',
            'payment_check_processing', 'payment_check_pending', 'payment_check_expired',
            'payment_confirmed_wait',
            'view_reading_celtic_pending', 'view_reading_deep_pending',
            'celtic_awaiting_payment', 'celtic_bill_creation_failed',
            'celtic_pending_payment_hint', 'celtic_pick_prompt',
            // ⛔ Free card flow ก็ skip ไม่ให้ wallet ลูกค้าใหม่งง
            'free_card_drawn', 'free_card_chitchat',
            'free_card_declined', 'free_card_draw_failed', 'free_card_ai_failed',
            // Payment lock actions (legacy v1 — ตอนนี้ไม่ใช้แล้ว แต่กัน BC)
            'payment_lock_pending', 'payment_lock_revived', 'payment_lock_admin',
        ];
        if (in_array($action, $skipActions, true)) {
            return $message;
        }

        // ✅ Apply + clear (ป้องกัน leak ไป next request ในเดียวกัน worker)
        FortuneConversationService::$pendingPaymentWarning = null;

        return $warning.$message;
    }

    /**
     * 🩹 (2026-05-09) Strip markdown asterisks/underscores จาก plain text message
     *
     * FB Messenger / LINE plain text ไม่ render markdown → *text* และ _text_ จะโชว์
     * เป็น literal asterisks / underscores ดูไม่เป็นมืออาชีพ
     *
     * แก้ที่ layer ส่งข้อความแทนที่จะไล่แก้ source 8+ จุด — minimize blast radius
     * + กัน regression ในอนาคต (ถ้า dev ใส่ markdown ใหม่ใน FCS ก็จะถูก strip อัตโนมัติ)
     *
     * @param  string|null  $message  ข้อความดิบที่อาจมี markdown
     * @return string|null ข้อความสะอาดที่พร้อมส่งให้ลูกค้า
     */
    protected function stripMessengerMarkdown(?string $message): ?string
    {
        if (empty($message)) {
            return $message;
        }

        // *bold* → bold
        // - non-greedy เพื่อกัน "*foo* and *bar*" รวมกัน
        // - [^*\n] กัน double asterisk + ห้ามข้ามบรรทัด (กัน multi-line bold ที่ไม่ตั้งใจ)
        $message = preg_replace('/\*([^*\n]+?)\*/u', '$1', $message);

        // _italic_ → italic
        // - lookbehind/lookahead กัน snake_case identifier (e.g. user_id, snake_case)
        // - \p{L}\p{N}_ ครอบคลุม Thai/Latin/digit + underscore เอง
        $message = preg_replace('/(?<![\p{L}\p{N}_])_([^_\n]+?)_(?![\p{L}\p{N}_])/u', '$1', $message);

        return $message;
    }

    /**
     * 🕐 (2026-05-17) Human-like delay สำหรับ FB chitchat
     *
     * Refresh typing indicator → fastcgi_finish_request (flush response) →
     * sleep N วินาที → message ส่งที่ caller ภายหลัง
     *
     * จุดประสงค์: ทำให้บอทดูเป็นมนุษย์ ไม่ตอบเร็วผิดธรรมชาติ
     * Note: LINE ไม่ delay (per user spec — LINE ช้าอยู่แล้ว)
     */
    protected function humanLikeDelayFb(FacebookWebhookService $fbService, string $userId, int $seconds = 15): void
    {
        try {
            // Refresh typing indicator — กัน FB hide ก่อน message มา
            $fbService->sendTypingIndicator($userId, true);
        } catch (\Throwable $e) {
            // ignore — delay ยังเดินต่อ
        }

        // Flush response กลับ FB webhook ก่อน (กัน webhook timeout)
        //   FB webhook timeout ~20s → flush ก่อน sleep ป้องกัน retry
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }

        // จำกัด delay: 1-25 วินาที (เผื่อ admin set ผิดพลาด)
        $seconds = max(1, min(25, $seconds));
        sleep($seconds);

        Log::debug('FortuneChannelManager: human-like delay ครบ', [
            'user_id' => $userId,
            'seconds' => $seconds,
        ]);
    }
}
