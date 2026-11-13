<?php

namespace App\Services\Fortune;

use App\Models\FortuneWebFunnelEvent;
use App\Models\JuntraAccount;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวนับเส้นทาง แชทเพจ FB → ห้องแม่หมอบนเว็บจันทรา (2026-11-14)
 *
 * เจ้าของถาม "คนกดเข้าห้องแม่หมอหรือเปล่า ทำไมไม่มียอดเลย" — ตัวนับเดิม (consumed_at ของ handoff)
 * เป็น 0 ตลอดตั้งแต่เว็บพาไปหน้าเลือกห้องเลย ⇒ นับแต่ละขั้นตรงจุดที่มันเกิดจริงบน Thaiprompt:
 *
 *   1. box_sent    — WebHandoffTrait::sendWebHandoff (source button:* = ลูกค้ากดปุ่มเอง)
 *   2. link_opened — WebFortuneGatewayController::consume ล็อกอินสำเร็จ (มีทั้ง PSID และ users.id)
 *      link_dead   — แตะลิงก์แต่ใช้ไม่ได้ (เหตุผลใน source)
 *   3. chatted     — เว็บเรียก /juntra/server/chat/* ของลูกค้าที่เคย link_opened (ครั้งแรกของวันต่อคน)
 *   4. paid        — เว็บส่งบิลคำทำนายที่จ่ายแล้วมา /juntra/server/affiliate/bills
 *
 * กติกา: ทุกเมธอด "ห้าม throw" — ตัวนับพังต้องไม่ทำให้ลูกค้าเข้าห้อง/คุย/จ่ายไม่ได้
 *        ขั้น 3-4 นับเฉพาะลูกค้าที่มาจากลิงก์ในแชทเพจ (เคยมี link_opened) — ลูกค้าเว็บทั่วไปไม่นับ
 */
class WebFunnelTracker
{
    /** แคชผล "คนนี้มาจาก FB ไหม" (นาที) — ค่าลบก็แคช กันยิงคิวรีทุกข้อความแชท */
    public const ORIGIN_CACHE_MINUTES = 10;

    /** กันนับบิลซ้ำ (วัน) */
    public const BILL_DEDUPE_DAYS = 40;

    /** ค่าในแคชที่แปลว่า "ไม่ได้มาจาก FB" */
    private const NOT_FROM_FB = -1;

    /** memo ต่อ process — Schema::hasTable ยิง information_schema ทุกครั้ง */
    private static ?bool $hasJuntraAccounts = null;

    /**
     * ขั้น 1 — ส่งกล่องลิงก์เข้าห้องแม่หมอให้ลูกค้าในแชทเพจแล้ว
     *
     * @param  string  $psid  PSID ของลูกค้า
     * @param  int|null  $pageId  สาขา (fortune_page_id)
     * @param  string  $source  จุดที่ส่ง เช่น button:WEB_ROOM / web_only_gate / gesture_emoji
     */
    public static function boxSent(string $psid, ?int $pageId, string $source): void
    {
        self::insert([
            'event' => FortuneWebFunnelEvent::BOX_SENT,
            'platform_user_id' => $psid,
            'fortune_page_id' => $pageId,
            'source' => $source,
        ]);
    }

    /**
     * ขั้น 2 — ลูกค้าแตะลิงก์ ระบบล็อกอินให้แล้ว กำลังส่งต่อไปเว็บจันทรา
     *
     * @param  bool  $newAccount  บัญชีเพิ่งถูกสร้างจากการแตะครั้งนี้
     */
    public static function linkOpened(string $psid, int $thaipromptUserId, ?int $pageId, bool $newAccount): void
    {
        self::insert([
            'event' => FortuneWebFunnelEvent::LINK_OPENED,
            'platform_user_id' => $psid,
            'thaiprompt_user_id' => $thaipromptUserId,
            'fortune_page_id' => $pageId,
            'source' => $newAccount ? 'new_account' : 'existing_account',
        ]);

        // คนนี้กลายเป็น "มาจาก FB" แล้ว — ล้างค่าลบที่อาจแคชไว้ก่อนหน้า
        try {
            Cache::forget(self::originKey($thaipromptUserId));
        } catch (\Throwable $e) {
            // แคชพังไม่เป็นไร หมดอายุเองใน 10 นาที
        }
    }

    /**
     * ขั้น 2 (ทางตัน) — แตะลิงก์แต่ใช้ไม่ได้
     *
     * @param  string  $reason  used / expired / busy / error / denied / staff_session / invalid ...
     */
    public static function linkDead(?string $psid, ?int $pageId, string $reason): void
    {
        self::insert([
            'event' => FortuneWebFunnelEvent::LINK_DEAD,
            'platform_user_id' => $psid,
            'fortune_page_id' => $pageId,
            'source' => mb_substr($reason, 0, 60),
        ]);
    }

    /**
     * ขั้น 3 — ลูกค้าเว็บคุยกับแม่หมอ (นับครั้งแรกของวันต่อคน เฉพาะคนที่มาจาก FB)
     *
     * @param  string  $userRef  users.id ฝั่งเว็บจันทรา
     * @param  int|null  $claimedUserId  thaiprompt_user_id ที่เว็บส่งมา (ใช้เมื่อไม่มี JuntraAccount)
     */
    public static function chatted(string $userRef, ?int $claimedUserId): void
    {
        try {
            [$tpUserId, $pageId] = self::fromFacebook($userRef, $claimedUserId);
            if ($tpUserId === null) {
                return;
            }

            $key = 'wff:chat:'.$tpUserId.':'.now()->format('Y-m-d');
            if (! Cache::add($key, 1, now()->endOfDay()->addHour())) {
                return;
            }
        } catch (\Throwable $e) {
            self::warn('chatted', $e);

            return;
        }

        self::insert([
            'event' => FortuneWebFunnelEvent::CHATTED,
            'thaiprompt_user_id' => $tpUserId,
            'juntra_user_ref' => $userRef,
            'fortune_page_id' => $pageId,
        ]);
    }

    /**
     * ขั้น 4 — ลูกค้าที่มาจาก FB ซื้อคำทำนายบนเว็บ (บิลละครั้ง)
     *
     * @param  int|null  $pageId  สาขาของบิล (ไม่มี = ใช้สาขาที่ลูกค้าแตะลิงก์ล่าสุด)
     */
    public static function paid(string $userRef, ?int $claimedUserId, int $billId, float $amount, ?CarbonInterface $paidAt = null, ?int $pageId = null): void
    {
        try {
            [$tpUserId, $originPage] = self::fromFacebook($userRef, $claimedUserId);
            if ($tpUserId === null) {
                return;
            }

            $source = 'bill:'.$billId;
            if (! Cache::add('wff:bill:'.$billId, 1, now()->addDays(self::BILL_DEDUPE_DAYS))) {
                return;
            }
            // แคชถูกล้าง (deploy) — เช็คในตารางอีกชั้น
            if (FortuneWebFunnelEvent::query()->where('event', FortuneWebFunnelEvent::PAID)->where('source', $source)->exists()) {
                return;
            }
        } catch (\Throwable $e) {
            self::warn('paid', $e);

            return;
        }

        self::insert([
            'event' => FortuneWebFunnelEvent::PAID,
            'thaiprompt_user_id' => $tpUserId,
            'juntra_user_ref' => $userRef,
            'fortune_page_id' => $pageId ?? $originPage,
            'source' => $source,
            'amount' => round($amount, 2),
            // บิลส่งมาหลังจ่ายจริงหลายนาที (รอพ้นช่วงคืนเงิน) — ลงวันตามเวลาที่จ่ายจริง ไม่ใช่ตอนรับบิล
            'created_at' => ($paidAt !== null && $paidAt->isPast()) ? $paidAt : now(),
        ]);
    }

    /**
     * ลูกค้าเว็บคนนี้มาจากลิงก์ในแชทเพจไหม
     *
     * @return array{0:?int,1:?int} [users.id ฝั่ง Thaiprompt หรือ null = ไม่ได้มาจาก FB, สาขาที่แตะลิงก์ล่าสุด]
     */
    protected static function fromFacebook(string $userRef, ?int $claimedUserId): array
    {
        // แคชการจับคู่ลูกค้าเว็บ → users.id (แชททุกข้อความผ่านตรงนี้ — ไม่ยิงคิวรี JuntraAccount ทุกข้อความ)
        //   คีย์รวมค่าที่เว็บอ้างมา: เว็บส่ง thaiprompt_user_id มาทีหลังได้ก็ไม่ติดค่าเก่า
        $mapped = Cache::remember(
            'wff:jw:'.$userRef.':'.($claimedUserId ?? 0),
            now()->addMinutes(self::ORIGIN_CACHE_MINUTES),
            fn () => self::resolveThaipromptUser($userRef, $claimedUserId) ?? 0,
        );
        $tpUserId = (int) $mapped > 0 ? (int) $mapped : null;
        if ($tpUserId === null) {
            return [null, null];
        }

        $page = Cache::remember(self::originKey($tpUserId), now()->addMinutes(self::ORIGIN_CACHE_MINUTES), function () use ($tpUserId) {
            $row = FortuneWebFunnelEvent::query()
                ->where('thaiprompt_user_id', $tpUserId)
                ->where('event', FortuneWebFunnelEvent::LINK_OPENED)
                ->orderByDesc('id')
                ->first(['fortune_page_id']);

            return $row ? (int) ($row->fortune_page_id ?? 0) : self::NOT_FROM_FB;
        });

        if ((int) $page === self::NOT_FROM_FB) {
            return [null, null];
        }

        return [$tpUserId, (int) $page > 0 ? (int) $page : null];
    }

    /**
     * users.id ฝั่ง Thaiprompt ของลูกค้าเว็บ — JuntraAccount ชนะเสมอ ไม่มีจึงใช้ค่าที่เว็บส่งมา
     *
     * (ค่าที่เว็บอ้างมาไม่มีผลเสีย: ถ้าไม่ใช่คนที่เคยแตะลิงก์จากเพจ ก็ไม่ถูกนับอยู่ดี)
     */
    protected static function resolveThaipromptUser(string $userRef, ?int $claimedUserId): ?int
    {
        self::$hasJuntraAccounts ??= Schema::hasTable('juntra_accounts');

        if (ctype_digit($userRef) && self::$hasJuntraAccounts) {
            $accountUserId = JuntraAccount::query()->where('juntra_user_id', (int) $userRef)->value('user_id');
            if ($accountUserId !== null) {
                return (int) $accountUserId;
            }
        }

        return ($claimedUserId !== null && $claimedUserId > 0) ? $claimedUserId : null;
    }

    protected static function originKey(int $tpUserId): string
    {
        return 'wff:origin:'.$tpUserId;
    }

    /**
     * บันทึก 1 แถว — พังได้แค่ log ห้ามโยนต่อ
     */
    protected static function insert(array $row): void
    {
        try {
            $row['created_at'] = $row['created_at'] ?? now();
            FortuneWebFunnelEvent::query()->insert($row);
        } catch (\Throwable $e) {
            self::warn($row['event'] ?? 'unknown', $e);
        }
    }

    protected static function warn(string $event, \Throwable $e): void
    {
        Log::warning('📈 WebFunnel: บันทึกเหตุการณ์ไม่สำเร็จ (ไม่กระทบลูกค้า)', [
            'event' => $event,
            'error' => mb_substr($e->getMessage(), 0, 200),
        ]);
    }
}
