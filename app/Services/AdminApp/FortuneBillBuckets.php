<?php

namespace App\Services\AdminApp;

use App\Http\Controllers\Admin\FortuneBillsController;
use App\Models\FortuneReading;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * 🧾 กองสถานะบิลดูดวงสำหรับแอปแอดมิน (ไทยพร้อม แอดมิน)
 *
 * บิลหนึ่งใบอยู่ได้ "กองเดียว" — ลำดับการตัดสิน (ตรงกับ statusOf()):
 *   1. paid      จ่ายแล้ว (ไม่ใช่บิลลอย)
 *   2. awaiting  แอดมินต้องตัดสินว่าเงินเข้าหรือยัง:
 *                บิลลอย (เงินเข้าไม่รู้เจ้าของ) หรือ บิลยังเปิด + ลูกค้าส่งสลิป/กดแจ้งโอนแล้ว แต่ระบบยังไม่ตัด
 *   3. refunded  ถูกพลิกจากจ่ายแล้วกลับเป็นยังไม่จ่าย (คืนเงินผ่าน API / ยกเลิกการอนุมัติจากหลังบ้าน)
 *   4. cancelled บิลยกเลิกตามนิยามกลางของโมเดล + สถานะดิบ 'cancelled' จากแอป SMS Checker
 *   5. unpaid    บิลยังเปิด รอลูกค้าโอน (ลูกค้ายังไม่แจ้งจ่าย)
 *   6. closed    ที่เหลือ — ออกบิลแล้วไม่จ่ายแล้วปิดเงียบ / ซากบิลรอจ่ายเกิน 7 วัน
 *
 * ⚠️ กติกาที่ห้ามพัง (memory rules):
 *   - แถว fortune_readings ≠ บิล → ขอบเขตต้อง exceptNeverBilled() เสมอ (rule_fortune_reading_row_is_not_a_bill)
 *   - บิลยกเลิก = completed + is_paid=0 + cancellation_reason → ใช้ cancelledSql() ของโมเดล
 *     ห้าม whereNotNull(JSON) เอง (rule_fortune_cancelled_bills_are_completed_status)
 *   - "จ่ายหรือยัง" ดูคอลัมน์ is_paid อย่างเดียว ธงใน state ไม่ใช่หลักฐานจ่าย (rule_flag_is_not_proof_of_payment)
 *   - SQL กับ PHP ต้องตรงกันทุกข้อ — แก้ข้างหนึ่งต้องแก้อีกข้าง (เทสต์ AdminAppFortuneBillsTest ตรึงไว้)
 *
 * ทุกเงื่อนไข SQL ห่อ COALESCE ให้ได้ 0/1 เสมอ — ถ้าได้ NULL แถวจะหายจากทุกกองตอนใช้ NOT (...)
 */
final class FortuneBillBuckets
{
    /** กองสถานะทั้งหมดที่แอปกรองได้ (ไม่นับ 'all') */
    public const STATUSES = ['awaiting', 'unpaid', 'paid', 'refunded', 'cancelled', 'closed'];

    /** บิลรอจ่ายที่ยัง "ลุ้นได้เงิน" = สร้างมาไม่เกินกี่วัน — ค่าเดียวกับหน้าศูนย์รวมบิลบนเว็บ */
    public const OPEN_BILL_DAYS = FortuneBillsController::PENDING_FRESH_DAYS;

    /** แพคเกจที่เป็นบิลเสียเงินแน่นอน (นอกจากนี้ต้องมีร่องรอยเงินถึงนับเป็นบิล) */
    private const PAID_PACKAGE_TYPES = [
        FortuneReading::READING_TYPE_DEEP,
        FortuneReading::READING_TYPE_CELTIC_CROSS,
        FortuneReading::READING_TYPE_JUNTRA,
    ];

    private const T = 'fortune_readings';

    /**
     * ขอบเขต "บิลจริง" — ตัดแถวแชทที่ไม่เคยออกบิล + ไพ่ฟรีที่ไม่มียอด
     */
    public static function billedScope(Builder $query): Builder
    {
        $t = self::T;
        $types = "'".implode("', '", self::PAID_PACKAGE_TYPES)."'";

        return $query->exceptNeverBilled()
            ->whereRaw("(COALESCE({$t}.reading_type, '') IN ({$types})"
                ." OR COALESCE({$t}.is_paid, 0) = 1"
                ." OR COALESCE({$t}.amount_paid, 0) > 0"
                ." OR COALESCE({$t}.is_floating, 0) = 1)");
    }

    /**
     * ยอดบิลต่อใบ (SQL) — ตรงกับ FortuneBillPresenter::billAmount() ที่โชว์ในรายการ:
     * amount_paid ถ้า > 0 ไม่งั้นยอดทศนิยมจาก UPA ไม่งั้น 0 (ผลรวมบนหัวจอจะได้ตรงกับรายการ)
     */
    public static function billAmountSql(string $t = self::T): string
    {
        return "(CASE WHEN COALESCE({$t}.amount_paid, 0) > 0 THEN {$t}.amount_paid"
            .' ELSE COALESCE((SELECT upa.unique_amount FROM unique_payment_amounts upa'
            ." WHERE upa.id = {$t}.unique_payment_amount_id), 0) END)";
    }

    /**
     * กรองตามกอง ('all' หรือค่าว่าง = ไม่กรอง)
     */
    public static function applyStatus(Builder $query, string $status, ?CarbonInterface $now = null): Builder
    {
        if (! in_array($status, self::STATUSES, true)) {
            return $query;
        }

        return $query->whereRaw(self::statusSql($status, $now));
    }

    /**
     * เงื่อนไข SQL ของกอง — คืนค่า 0/1 เสมอ (ไม่มี NULL) ใช้ได้ทั้งใน WHERE และ SUM(CASE ...)
     */
    public static function statusSql(string $status, ?CarbonInterface $now = null): string
    {
        $parts = self::sqlParts($now);

        if ($status === 'closed') {
            return '(NOT ('.implode(' OR ', [
                $parts['paid'], $parts['awaiting'], $parts['refunded'], $parts['cancelled'], $parts['unpaid'],
            ]).'))';
        }

        return $parts[$status];
    }

    /**
     * กองของบิลใบนี้ (ฝั่ง PHP — ตรรกะเดียวกับ statusSql ทุกข้อ)
     */
    public static function statusOf(FortuneReading $reading, ?CarbonInterface $now = null): string
    {
        $floating = (bool) $reading->is_floating;
        $paid = (bool) $reading->is_paid;

        if ($paid && ! $floating) {
            return 'paid';
        }

        $refunded = ! $paid && ! $floating && self::looksRefunded($reading);
        $open = self::isOpenBill($reading, $now);
        $claimed = self::claimReason($reading) !== null;

        if ($floating || (! $paid && ! $refunded && $open && $claimed)) {
            return 'awaiting';
        }
        if ($refunded) {
            return 'refunded';
        }
        if ($reading->isCancelled() || strtolower((string) $reading->conversation_status) === 'cancelled') {
            return 'cancelled';
        }
        if ($open) {
            return 'unpaid';
        }

        return 'closed';
    }

    /**
     * เหตุผลที่บิลเข้ากอง awaiting — null = ลูกค้ายังไม่แจ้งจ่าย
     *
     * @return 'floating'|'slip_received'|'transfer_reported'|null
     */
    public static function claimReason(FortuneReading $reading): ?string
    {
        if ((bool) $reading->is_floating) {
            return 'floating';
        }
        if ($reading->slip_received_at !== null) {
            return 'slip_received';
        }
        if ((bool) $reading->transfer_reported) {
            return 'transfer_reported';
        }

        return null;
    }

    /**
     * เวลาที่ลูกค้าแจ้งจ่าย (ใช้นับว่ารอแอดมินมานานเท่าไร)
     */
    public static function claimedAt(FortuneReading $reading): ?CarbonInterface
    {
        return $reading->slip_received_at
            ?? $reading->transfer_reported_at
            ?? $reading->paid_at
            ?? $reading->updated_at;
    }

    /**
     * ยกเลิกการอนุมัติจากหลังบ้าน (voidApproval) หรือคืนเงินผ่าน API (ธงจ่ายถูกพลิกแต่ paid_at ยังอยู่)
     */
    public static function looksRefunded(FortuneReading $reading): bool
    {
        if ((bool) $reading->is_paid) {
            return false;
        }

        $voided = $reading->getConversationState('approval_voided');
        $voided = $voided === true || in_array(strtolower((string) $voided), ['true', '1'], true);

        return $reading->paid_at !== null || $voided;
    }

    /**
     * บิลยังเปิดรอจ่ายและยังไม่เก่าเกิน OPEN_BILL_DAYS
     */
    public static function isOpenBill(FortuneReading $reading, ?CarbonInterface $now = null): bool
    {
        $cutoff = self::openCutoff($now);

        return in_array((string) $reading->conversation_status, FortuneReading::PENDING_DISPLAY_STATUSES, true)
            && $reading->created_at !== null
            && $reading->created_at->greaterThanOrEqualTo($cutoff);
    }

    /**
     * จุดตัดอายุบิลที่ยังเปิด
     */
    public static function openCutoff(?CarbonInterface $now = null): CarbonInterface
    {
        return ($now ?? now())->copy()->subDays(self::OPEN_BILL_DAYS);
    }

    /**
     * ชิ้นส่วน SQL ของแต่ละกอง (ห่อ COALESCE(..., 0) = 1 ให้ไม่มี NULL)
     *
     * @return array<string, string>
     */
    private static function sqlParts(?CarbonInterface $now = null): array
    {
        $t = self::T;
        // จุดตัดเป็นเวลาที่ระบบสร้างเอง (ไม่ใช่ค่าจากผู้ใช้) — ฝังเป็นสตริงได้ ใช้ใน SUM(CASE) ได้โดยไม่ต้อง binding
        $cutoff = self::openCutoff($now)->format('Y-m-d H:i:s');
        $pending = "'".implode("', '", FortuneReading::PENDING_DISPLAY_STATUSES)."'";

        $floating = "COALESCE({$t}.is_floating, 0) = 1";
        $notFloating = "COALESCE({$t}.is_floating, 0) = 0";
        $isPaid = "COALESCE({$t}.is_paid, 0) = 1";
        $notPaid = "COALESCE({$t}.is_paid, 0) = 0";
        $voided = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$t}.conversation_state, '$.approval_voided')), '') IN ('true', '1')";
        $open = "(COALESCE({$t}.conversation_status, '') IN ({$pending})"
            ." AND COALESCE({$t}.created_at, '1970-01-01 00:00:00') >= '{$cutoff}')";
        $claimed = "({$t}.slip_received_at IS NOT NULL OR COALESCE({$t}.transfer_reported, 0) = 1)";

        $refunded = self::nn("{$notPaid} AND {$notFloating} AND ({$t}.paid_at IS NOT NULL OR {$voided})");
        $cancelled = self::nn("{$notPaid} AND {$notFloating} AND NOT {$refunded}"
            .' AND ('.self::nn(FortuneReading::cancelledSql($t))." OR COALESCE({$t}.conversation_status, '') = 'cancelled')");

        return [
            'paid' => self::nn("{$isPaid} AND {$notFloating}"),
            'awaiting' => self::nn("{$floating} OR ({$notPaid} AND NOT {$refunded} AND {$open} AND {$claimed})"),
            'refunded' => $refunded,
            'cancelled' => $cancelled,
            'unpaid' => self::nn("{$notPaid} AND {$notFloating} AND NOT {$refunded} AND {$open} AND NOT {$claimed}"),
        ];
    }

    /**
     * ห่อเงื่อนไขให้คืน 0/1 เสมอ
     */
    private static function nn(string $expr): string
    {
        return "(COALESCE(({$expr}), 0) = 1)";
    }
}
