<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use Illuminate\Support\Facades\Log;

/**
 * 🌐 (2026-11-03 FB web-only) ด่านกรองข้อความที่ "แอดมินพิมพ์เอง" ส่งหาลูกค้า FB
 *
 * เหตุผล: Facebook แบนเพจที่มีสลิป/เลขบัญชี/เรื่องโอนเงินในแชท Messenger
 *   เมื่อเปิดธง facebook_web_only บอทเลิกพูดเรื่องเงินบน FB แล้ว แต่แอดมินยังพิมพ์เองได้จาก
 *   หน้าผู้ใช้ (ส่งรายคน / broadcast) และหน้าเทคโอเวอร์ → ต้องกรองที่ต้นทางด้วย
 *
 * กติกา:
 *   - กรองเฉพาะเมื่อช่องทางที่ resolve ได้คือ facebook **และ** ธงเปิด (FortuneBotMode::fbWebOnlyFor)
 *     LINE / Telegram ไม่โดนเลย
 *   - คำเรื่องเงิน (โอน/สลิป/บัญชี/พร้อมเพย์/QR/ราคา/บาท/฿/ค่าครู …) หรือเลขยาว ≥ 8 หลัก (เลขบัญชี/เบอร์พร้อมเพย์)
 *     = ส่งไม่ได้ แอดมินต้องเปลี่ยนเป็นชวนไปคุยต่อบนเว็บจันทราแทน
 *   - broadcast: ลูกค้า FB ต้องอยู่ในหน้าต่าง 24 ชม. (ทักมาภายใน 24 ชม.) เท่านั้น — ไม่มีข้อมูล = ข้าม (fail-closed)
 */
final class FbWebOnlyAdminMessageFilter
{
    /** เหตุผลที่ระบบบันทึก/แสดงเมื่อบล็อก */
    public const REASON_MONEY_WORD = 'fb_web_only_money_word';

    /**
     * คำเรื่องเงินที่ห้ามส่งเข้าแชท FB (ไม่สนตัวพิมพ์เล็ก/ใหญ่)
     * ⚠️ ตั้งใจให้กว้าง — บล็อกเกินแล้วแอดมินเปลี่ยนคำได้ ดีกว่าหลุดแล้วเพจโดนแบน
     */
    private const MONEY_WORDS = '/(โอน|สลิป|บัญชี|พร้อมเพย์|พร้อมเพ|prompt\s*pay|\bqr\b|คิวอาร์|ชำระ|ราคา|บาท|฿|จ่ายเงิน|จ่ายค่า|ค่าครู|ค่าดู|ค่าบริการ|ยอดเงิน|ยอดโอน|เติมเงิน|truemoney|ทรูมันนี่|ทรูมันนี|stripe|บัตรเครดิต|เปิดบิล|ออกบิล|ใบแจ้งหนี้|ธนาคาร|\bbank\b|\bbaht\b|\bthb\b|\bpay(?:ment)?\b)/iu';

    /** เลขยาว ≥ 8 หลัก (คั่นด้วยขีด/จุด/ช่องว่างได้) — เลขบัญชี / เบอร์พร้อมเพย์ */
    private const LONG_NUMBER = '/\d(?:[\s\-\.]?\d){7,}/u';

    /** หน้าต่างส่งข้อความของ Messenger (ชั่วโมง) */
    public const BROADCAST_WINDOW_HOURS = 24;

    /**
     * หาคำเรื่องเงินในข้อความ
     *
     * @return string|null คำ/เลขที่เจอ (ใช้แสดงให้แอดมินเห็น) · null = ไม่มี
     *
     * @example
     * FbWebOnlyAdminMessageFilter::findMoneyWord('โอนมาที่ 123-4-56789-0'); // 'โอน'
     * FbWebOnlyAdminMessageFilter::findMoneyWord('สวัสดีค่ะ แม่หมอคิดถึงนะคะ'); // null
     */
    public static function findMoneyWord(string $message): ?string
    {
        if (preg_match(self::MONEY_WORDS, $message, $m)) {
            return $m[0];
        }

        if (preg_match(self::LONG_NUMBER, $message, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * ข้อความนี้ส่งหาลูกค้าคนนี้ไม่ได้เพราะพูดเรื่องเงินภายใต้ FB web-only หรือไม่
     *
     * @param  string|null  $platform  ช่องทางที่หน้าแอดมินส่งมา (เป็น hint — รูปทรง id ชนะ)
     * @param  string|null  $platformUserId  PSID / LINE uid
     * @return string|null ข้อความ error ภาษาไทยสำหรับแอดมิน · null = ส่งได้
     */
    public static function blockReason(?string $platform, ?string $platformUserId, string $message, ?FortuneReading $reading = null): ?string
    {
        // เช็คคำก่อน — ข้อความไม่มีคำเงินไม่ต้องอ่านธง (LINE/ข้อความปกติไม่เสียอะไรเลย)
        $word = self::findMoneyWord($message);
        if ($word === null) {
            return null;
        }

        if (! (new FortuneBotMode)->fbWebOnlyFor($platform, $platformUserId, $reading)) {
            return null;
        }

        Log::info('🌐 FB web-only: บล็อกข้อความแอดมินที่พูดเรื่องเงินหาลูกค้า FB', [
            'admin_id' => auth()->id(),
            'platform_user_id' => $platformUserId,
            'reading_id' => $reading?->id,
            'reason' => self::REASON_MONEY_WORD,
        ]);

        return self::errorMessage($word);
    }

    /**
     * ข้อความ error สำหรับแอดมิน (บอกคำที่เจอ + ทางออก)
     */
    public static function errorMessage(string $word): string
    {
        return '🌐 ส่งไม่ได้ — ตอนนี้แชท Facebook อยู่โหมด "ดูดวงบนเว็บเท่านั้น" '
            .'ห้ามพูดเรื่องเงิน/โอน/ราคา/เลขบัญชีในแชท FB (เพจเสี่ยงโดนแบน) '
            .'เจอคำว่า "'.mb_substr($word, 0, 30).'" — ให้ชวนลูกค้าไปคุยต่อกับแม่หมอบนเว็บจันทราแทนค่ะ';
    }

    /**
     * ลูกค้า FB คนนี้ทักมาภายใน 24 ชม. หรือไม่ (ใช้คัดผู้รับ broadcast)
     *
     * อ่านจาก fortune_user_credits.last_dm_at (FCS::processMessage บันทึกทุกครั้งที่ลูกค้าทัก)
     * ไม่มีแถว / อ่านไม่ได้ = false (fail-closed: ไม่ส่งนอกหน้าต่าง)
     */
    public static function withinMessagingWindow(string $platformUserId, string $platform = 'facebook'): bool
    {
        try {
            $credit = FortuneUserCredit::query()
                ->where('facebook_user_id', $platformUserId)
                ->where('platform', $platform)
                ->first();

            if (! $credit || empty($credit->last_dm_at)) {
                return false;
            }

            return $credit->last_dm_at->greaterThanOrEqualTo(now()->subHours(self::BROADCAST_WINDOW_HOURS));
        } catch (\Throwable $e) {
            Log::warning('🌐 FB web-only: ตรวจหน้าต่าง 24 ชม. ไม่ได้ — ข้ามผู้รับ', [
                'platform_user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * สาขา (fortune_pages.id) ล่าสุดของลูกค้า — ใช้ตั้ง page context ก่อนส่ง (token ถูกเพจ)
     */
    public static function latestPageIdFor(string $platformUserId): ?int
    {
        try {
            $pageId = FortuneReading::query()
                ->where(function ($q) use ($platformUserId) {
                    $q->where('platform_user_id', $platformUserId)
                        ->orWhere('facebook_user_id', $platformUserId);
                })
                ->whereNotNull('fortune_page_id')
                ->orderByDesc('id')
                ->value('fortune_page_id');

            return $pageId ? (int) $pageId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
