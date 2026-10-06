<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🤫 TakeoverSendGuard — ด่านกลางของกฎ "แอดมินเทคโอเวอร์อยู่ = บอทห้ามส่งอะไรหาลูกค้าคนนั้นเลย"
 *
 * เจ้าของสั่ง (2026-10-06): "ถ้าเทคโอเวอร์คือแอดมินคุยแล้ว บอทต้องหยุดแทรกก่อน"
 * ⇒ ระหว่างเทคโอเวอร์ บอทห้ามส่ง ข้อความ / กล่องกำลังพิมพ์ / สติกเกอร์ / รีแอคชัน / ทวง / เตือน / ping /
 *    follow-up / ส่งคำทำนาย — จนกว่าเทคโอเวอร์จะจบ (คืนงาน หรือหมดเวลา)
 *
 * กลับทิศพฤติกรรมเดิมของ 2026-05-17 (`FortuneTakeoverService::shouldBypassTakeover` ที่ปล่อยปุ่ม / flow ล็อก /
 * คำทำนายที่จ่ายแล้ว / ข้อความอยากซื้อ ผ่านด่าน) — แต่ของที่ลูกค้าจ่ายเงินแล้ว **ต้องไม่หาย**:
 * ถูกพักไว้ใน `conversation_state.takeover_deferred` แล้วส่งครั้งเดียวตอนเทคโอเวอร์จบ (ดู TakeoverResumeService)
 *
 * ## จุดที่เรียกด่านนี้ (ชั้นส่ง — ทุกเส้นวิ่งผ่าน)
 * - FacebookWebhookService::shouldSkipSend() (10 เมธอดส่ง)
 * - LineFortuneService pushMessage / pushMessagePriority / replyMessage / showLoadingAnimation
 * - TelegramFortuneService::call() (เมธอดส่ง/แก้ข้อความ/กำลังพิมพ์ — answerCallbackQuery ยังส่งได้)
 * - FortuneChannelManager::sendResponse()
 *
 * ## แอดมินตัวจริงส่งได้เสมอ
 * ห่อการส่งด้วย `TakeoverSendGuard::asHumanAdmin(fn () => ...)` — ⚠️ ห้ามใช้ option `from_admin`
 * เพราะ `from_admin` ในโค้ดเดิมแปลว่า "ข้อความระบบแบบ push" และบอทอัตโนมัติ ~40 จุดตั้งไว้
 *
 * ## ล้มแบบเปิด (fail open)
 * ถ้าเช็คเองพัง (DB ล่ม ฯลฯ) → log error แล้วปล่อยส่ง — ธรรมเนียมเดิมของระบบ (บอทเงียบทั้งระบบแย่กว่า)
 */
final class TakeoverSendGuard
{
    /** ระดับซ้อนของ asHumanAdmin() — > 0 = กำลังส่งในนามแอดมินตัวจริง */
    private static int $humanAdminDepth = 0;

    /** replyToken → [platform, userId] ภายใน process (เส้นด่วนก่อน Cache) */
    private static array $replyTokenOwners = [];

    /** อายุการจำเจ้าของ replyToken (LINE token อายุ ~60 วิ) */
    private const REPLY_TOKEN_TTL_SECONDS = 120;

    /** ช่องทางที่ถือเป็น "ของประดับ" — ไม่จดลงแชทล็อก (กันล็อกบวมจากกล่องกำลังพิมพ์) */
    private const SILENT_CHANNELS = [
        'sendTypingIndicator', 'showLoadingAnimation', 'sendChatAction', 'postGesture',
        'editMessageReplyMarkup',
    ];

    // ============================================================
    // แอดมินตัวจริง
    // ============================================================

    /**
     * รันการส่งในนาม "แอดมินตัวจริง" — ด่านเทคโอเวอร์ไม่บล็อกสิ่งที่ส่งภายใน callback นี้
     *
     * @template T
     *
     * @param  callable():T  $send
     * @return T
     */
    public static function asHumanAdmin(callable $send): mixed
    {
        self::$humanAdminDepth++;

        try {
            return $send();
        } finally {
            self::$humanAdminDepth = max(0, self::$humanAdminDepth - 1);
        }
    }

    /** กำลังส่งในนามแอดมินตัวจริงอยู่หรือไม่ */
    public static function inHumanAdminScope(): bool
    {
        return self::$humanAdminDepth > 0;
    }

    // ============================================================
    // สถานะเทคโอเวอร์ (ต่อ "ลูกค้า" — ทุกบิลของคนนั้น)
    // ============================================================

    /**
     * ลูกค้าคนนี้กำลังถูกแอดมินเทคโอเวอร์อยู่หรือไม่ (ทุกบิลของคนนั้น ไม่ใช่แค่บิลเดียว)
     *
     * - เทคโอเวอร์ที่แอดมินสั่งเอง (manual / แอดมินพิมพ์ในกล่องแชท) นับเสมอ แม้ปิดสวิตช์ admin_handover_enabled
     * - เทคโอเวอร์อัตโนมัติ (ลูกค้าขอคุยกับคน) นับเฉพาะตอนเปิดสวิตช์
     * - DB เป็นความจริง — cache:clear แล้วยังได้คำตอบถูก (ไม่มี negative cache)
     *
     * ⚠️ โยน exception ได้ (DB ล่ม) — ผู้เรียกที่ต้องการ fail-open ให้ใช้ blocks() / readingIsTakenOver()
     */
    public static function isTakenOver(string $platform, ?string $userId): bool
    {
        return self::activeUntil($platform, $userId) !== null;
    }

    /**
     * เวลาสิ้นสุดของเทคโอเวอร์ที่ยาวที่สุดของลูกค้าคนนี้ (null = ไม่ได้ถูกเทคโอเวอร์)
     */
    public static function activeUntil(string $platform, ?string $userId): ?\Carbon\CarbonInterface
    {
        $row = self::activeTakeoverQuery($userId)?->orderByDesc('admin_takeover_until')
            ->first(['id', 'admin_takeover_until']);

        return $row?->admin_takeover_until;
    }

    /**
     * บิลที่ถือเทคโอเวอร์อยู่ (ตัวที่หมดเวลาช้าสุด) — null = ไม่ได้ถูกเทคโอเวอร์
     */
    public static function activeTakeoverReading(string $platform, ?string $userId): ?FortuneReading
    {
        return self::activeTakeoverQuery($userId)?->orderByDesc('admin_takeover_until')->first();
    }

    /**
     * วินาทีที่เหลือของเทคโอเวอร์ (0 = ไม่ได้ถูกเทคโอเวอร์)
     */
    public static function remainingSeconds(string $platform, ?string $userId): int
    {
        $until = self::activeUntil($platform, $userId);

        return $until ? max(0, (int) now()->diffInSeconds($until, false)) : 0;
    }

    /**
     * บิลใบนี้ (ลูกค้าเจ้าของบิล) กำลังถูกเทคโอเวอร์ไหม — ล้มแบบเปิด (เช็คพัง = false)
     *
     * ใช้ใน cron / job ที่วนทีละบิล: true = ข้ามบิลนี้ก่อนเรียก AI / เพิ่มตัวนับ / ปิดบิล
     */
    public static function readingIsTakenOver(?FortuneReading $reading): bool
    {
        if (! $reading) {
            return false;
        }

        try {
            ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);

            return self::isTakenOver($platform, $userId);
        } catch (\Throwable $e) {
            Log::error('🤫 TakeoverSendGuard: เช็คเทคโอเวอร์ของบิลล้ม — ปล่อยผ่าน (fail open)', [
                'reading_id' => $reading->id,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return false;
        }
    }

    /**
     * ลูกค้าเจ้าของบิลนี้เคยถูกเทคโอเวอร์ (เริ่ม) ตั้งแต่เวลานี้หรือไม่ — ใช้ "หยุดนาฬิกา" ของงานที่นับเวลาบิลค้าง
     * ล้มแบบเปิด (เช็คพัง = false)
     */
    public static function hadTakeoverSince(FortuneReading $reading, \DateTimeInterface $since): bool
    {
        try {
            $userId = FortuneRecipient::userIdOf($reading);
            if ($userId === '') {
                return false;
            }

            return FortuneReading::query()
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
                })
                ->whereNotNull('admin_takeover_started_at')
                ->where('admin_takeover_started_at', '>=', $since)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ลูกค้าคนนี้ถูกเทคโอเวอร์ไหม — ล้มแบบเปิด (เช็คพัง = false) สำหรับ job ที่ถือแค่ platform + userId
     */
    public static function userIsTakenOver(string $platform, ?string $userId): bool
    {
        try {
            return self::isTakenOver($platform, $userId);
        } catch (\Throwable $e) {
            Log::error('🤫 TakeoverSendGuard: เช็คเทคโอเวอร์ล้ม — ปล่อยผ่าน (fail open)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return false;
        }
    }

    // ============================================================
    // ด่านชั้นส่ง
    // ============================================================

    /**
     * ต้องงดส่งไหม — true = ห้ามส่ง (caller คืน false ทันที ห้าม mark ว่าส่งแล้ว)
     *
     * @param  string  $channel  ชื่อเมธอด/ช่องทางที่กำลังจะส่ง (ไว้ใน log)
     * @param  string|null  $preview  ตัวอย่างเนื้อหา (ไว้ในแชทล็อกของแอดมิน)
     */
    public static function blocks(string $platform, ?string $userId, string $channel = '', ?string $preview = null): bool
    {
        if ($userId === null || $userId === '' || self::inHumanAdminScope()) {
            return false;
        }

        try {
            if (! self::isTakenOver($platform, $userId)) {
                return false;
            }
        } catch (\Throwable $e) {
            Log::error('🤫 TakeoverSendGuard: เช็คเทคโอเวอร์ก่อนส่งล้ม — ปล่อยส่ง (fail open)', [
                'platform' => $platform,
                'user_id' => $userId,
                'channel' => $channel,
                'error' => SafeLog::exceptionMessage($e),
            ]);

            return false;
        }

        self::recordSuppressed($platform, $userId, $channel, $preview);

        return true;
    }

    /**
     * จดว่าบอทถูกงดส่ง — log ทุกครั้ง + แชทล็อกของแอดมิน (เฉพาะข้อความจริง ไม่จดกล่องกำลังพิมพ์)
     */
    public static function recordSuppressed(string $platform, string $userId, string $channel, ?string $preview = null): void
    {
        Log::info('🤫 bot_suppressed: แอดมินเทคโอเวอร์อยู่ — บอทงดส่ง', [
            'platform' => $platform,
            'user_id' => $userId,
            'channel' => $channel,
            'preview' => $preview !== null ? mb_substr($preview, 0, 80) : null,
        ]);

        if (in_array($channel, self::SILENT_CHANNELS, true)) {
            return;
        }

        $preview = trim((string) $preview);

        try {
            // กันล็อกบวม: ข้อความเดียวกันจากคำทำนายยาวที่ถูกซอยหลายท่อน → จดครั้งเดียวต่อ 20 วิ
            $dedupe = 'takeover_suppressed_log:'.$platform.':'.$userId.':'.md5($channel.'|'.mb_substr($preview, 0, 60));
            if (! Cache::add($dedupe, 1, 20)) {
                return;
            }

            app(FortuneChatLogService::class)->record(
                $platform,
                $userId,
                'system',
                '🤫 บอทงดส่ง (แอดมินคุยอยู่)'.($preview !== '' ? ': '.mb_substr($preview, 0, 140) : ''),
                ['by' => 'bot_suppressed']
            );
        } catch (\Throwable $e) {
            // แชทล็อกเป็น best-effort — ห้ามทำให้การงดส่งพัง
        }
    }

    // ============================================================
    // LINE replyToken → เจ้าของ (replyMessage ไม่รู้ว่าตอบใคร)
    // ============================================================

    /**
     * จำว่า replyToken นี้เป็นของลูกค้าคนไหน — เรียกที่หัว webhook ของ LINE
     */
    public static function bindReplyToken(?string $replyToken, string $platform, ?string $userId): void
    {
        if (empty($replyToken) || empty($userId)) {
            return;
        }

        self::$replyTokenOwners[$replyToken] = [$platform, $userId];

        // จำกัดขนาดในหน่วยความจำ (worker รันยาว)
        if (count(self::$replyTokenOwners) > 500) {
            self::$replyTokenOwners = array_slice(self::$replyTokenOwners, -200, null, true);
        }

        try {
            Cache::put(self::replyTokenKey($replyToken), [$platform, $userId], self::REPLY_TOKEN_TTL_SECONDS);
        } catch (\Throwable $e) {
            // ไม่มี cache ก็ยังมีตัวในหน่วยความจำ
        }
    }

    /**
     * เจ้าของ replyToken — [platform, userId] หรือ null ถ้าไม่รู้
     *
     * @return array{0:string,1:string}|null
     */
    public static function replyTokenOwner(string $replyToken): ?array
    {
        if (isset(self::$replyTokenOwners[$replyToken])) {
            return self::$replyTokenOwners[$replyToken];
        }

        try {
            $owner = Cache::get(self::replyTokenKey($replyToken));

            return is_array($owner) && count($owner) === 2 ? [(string) $owner[0], (string) $owner[1]] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ============================================================
    // ภายใน
    // ============================================================

    /**
     * คิวรีหาบิลของลูกค้าที่เทคโอเวอร์ยังไม่หมดเวลา (null = ไม่มี user id)
     *
     * ไม่กรองด้วย platform — รูปทรง id แยกช่องทางกันเองอยู่แล้ว (LINE 'U…' / Telegram 'tg_…' / FB ตัวเลข)
     * และแถวเก่าของ FB บางแถว platform ว่าง
     */
    private static function activeTakeoverQuery(?string $userId): ?\Illuminate\Database\Eloquent\Builder
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return null;
        }

        $query = FortuneReading::query()
            ->where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)
                    ->orWhere('facebook_user_id', $userId);
            })
            ->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', now());

        // สวิตช์ปิด = ปิดเฉพาะเทคโอเวอร์อัตโนมัติ (ลูกค้าขอคุยกับคน) — ที่แอดมินสั่งเองยังนับเสมอ
        if (! self::autoTakeoverEnabled()) {
            $query->where(function ($q) {
                $q->whereNull('admin_takeover_reason')
                    ->orWhere('admin_takeover_reason', '!=', FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST);
            });
        }

        return $query;
    }

    private static function autoTakeoverEnabled(): bool
    {
        try {
            return FortuneTellingSetting::getSettings()->isTakeoverEnabled();
        } catch (\Throwable $e) {
            return true;
        }
    }

    private static function replyTokenKey(string $replyToken): string
    {
        return 'takeover_rt_owner:'.sha1($replyToken);
    }
}
