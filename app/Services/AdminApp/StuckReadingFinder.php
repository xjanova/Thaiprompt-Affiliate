<?php

namespace App\Services\AdminApp;

use App\Models\FortuneReading;
use App\Services\CelticCrossService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * 🧊 บิลจ่ายแล้วที่ "ระบบไม่ขยับ" — นิยามเดียวของแอปแอดมิน (ops/summary + fortune/active-readings)
 *
 * ค้าง = จ่ายแล้ว (is_paid = 1 · ไม่ใช่บิลจันทรา) และเข้าข้อใดข้อหนึ่ง:
 *   1. ai_generating_timeout — อยู่ในสถานะที่ AI ต้องทำงานเอง (AI_GENERATING_STATUSES = paid · celtic_generating)
 *      แต่แถวไม่ขยับเกิน STUCK_AFTER_MINUTES นาที (ภายใน 24 ชม.)
 *      ยกเว้นงานที่ยังวิ่งอยู่จริง (ช้า ≠ ตาย — เคส FTU-260926-S8307 Celtic คิดจริง 126 วิ):
 *        - Deep: มีธง fortune:deep_gen:{id} / fortune:deep_deliver:{id} (ธงเดียวกับ fortune:check-pending)
 *        - Celtic: CelticCrossService::isGenerationInFlight()
 *   2. deep_job_failed — Deep ที่ completed แต่ deep_response ว่าง จ่ายมาแล้ว 2 นาที–24 ชม.
 *      (ชุดเดียวกับที่ fortune:check-pending / แอป SMS Checker หยิบไป retry)
 *   3. escalated_24h (v3) — บิลที่ fortune:expire-stuck-paid ปักธง conversation_state.admin_review_needed = true
 *      (จ่ายเกิน 24 ชม. ยังไม่ได้คำทำนาย — ระบบอัตโนมัติยอมแพ้แล้ว ต้องคนกู้) และ **ยังไม่เสร็จจริง**
 *      ตามเงื่อนไขเดียวกับตัวปักธง:
 *        - Deep: สถานะ paid / collecting_* (DEEP_ACTIVE_STATUSES) หรือ completed แต่ deep_response ว่าง
 *        - Celtic: สถานะไม่ใช่ completed
 *      จ่ายมาไม่เกิน ESCALATED_MAX_AGE_DAYS วัน (ค่าเดียวกับ --max-age-days ของตัวปักธง)
 *      ⚠️ ธงนี้ไม่มีใครล้างนอกจาก voidApproval() — จึงต้องเช็ค "ยังไม่เสร็จ" ซ้ำทุกครั้ง ไม่งั้นบิลที่กู้สำเร็จแล้วจะค้างในคิวตลอดไป
 *
 * สถานะที่รอ "ลูกค้า" (เก็บวันเกิด / เลือกไพ่ / รอคำถาม) ไม่ใช่ค้าง ต่อให้เงียบนานก็ตาม (ยกเว้นข้อ 3 — เกิน 24 ชม. แล้ว)
 *
 * ⚠️ ต่างจาก workers/queue.stuck ของ Warroom (responded_at ว่าง + created_at 1–10 นาที) โดยตั้งใจ:
 *    ตัวนั้นนับลูกค้า Deep ที่กำลังกรอกวันเกิดเป็นค้าง และมองไม่เห็นบิลที่สร้างนานกว่า 10 นาที
 */
final class StuckReadingFinder
{
    /** ไม่ขยับเกินกี่นาทีถึงเรียกว่าค้าง */
    public const STUCK_AFTER_MINUTES = 2;

    /** มองย้อนหลังกี่ชั่วโมง (เกินนี้ fortune:expire-stuck-paid รับไปแจ้งแอดมินแล้ว) */
    public const LOOKBACK_HOURS = 24;

    /** บิลที่ถูกปักธงเกิน 24 ชม. มองย้อนหลังกี่วัน — ค่าเดียวกับ fortune:expire-stuck-paid --max-age-days (30) */
    public const ESCALATED_MAX_AGE_DAYS = 30;

    /** เหตุผลของบิลที่ fortune:expire-stuck-paid ส่งต่อให้แอดมินแล้ว */
    public const REASON_ESCALATED = 'escalated_24h';

    /**
     * สถานะ "กำลังใช้บริการ" ของบิลที่จ่ายแล้ว — Deep กำลังเก็บข้อมูล/รอทำนาย + Celtic ทุกขั้นหลังจ่าย
     */
    public const IN_PROGRESS_STATUSES = [
        FortuneReading::STATUS_PAID,
        FortuneReading::STATUS_COLLECTING_BIRTHDATE,
        FortuneReading::STATUS_COLLECTING_QUESTIONS,
        FortuneReading::STATUS_COLLECTING_TAROT,
        FortuneReading::STATUS_CELTIC_PICKING,
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_GENERATING,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    /**
     * ขอบเขต "บิลจ่ายแล้วที่ยังใช้บริการอยู่" + Deep ที่งาน AI ล้ม (ตัวเลือกของ active-readings)
     */
    public static function activeScope(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $since = $now->copy()->subHours(self::LOOKBACK_HOURS);

        return $query->withoutJuntra()
            ->where('is_paid', true)
            ->where(function ($q) use ($since, $now) {
                $q->where(function ($a) use ($since) {
                    $a->whereIn('conversation_status', self::IN_PROGRESS_STATUSES)
                        ->where('updated_at', '>=', $since);
                })->orWhere(function ($b) use ($since) {
                    self::deepFailedWhere($b, $since);
                })->orWhere(function ($c) use ($now) {
                    // (v3) ค้างเกิน 24 ชม. ที่ fortune:expire-stuck-paid ส่งต่อให้แอดมินแล้ว
                    self::escalatedWhere($c, $now);
                });
            });
    }

    /**
     * ขอบเขต "ผู้ต้องสงสัยว่าค้าง" ระดับ SQL (ยังไม่ตัดงานที่วิ่งอยู่ใน cache — ใช้ isStuck() กรองซ้ำ)
     */
    public static function candidateScope(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $since = $now->copy()->subHours(self::LOOKBACK_HOURS);
        $cutoff = $now->copy()->subMinutes(self::STUCK_AFTER_MINUTES);

        return $query->withoutJuntra()
            ->where('is_paid', true)
            ->where(function ($q) use ($since, $cutoff, $now) {
                $q->where(function ($a) use ($since, $cutoff) {
                    $a->whereIn('conversation_status', FortuneReading::AI_GENERATING_STATUSES)
                        ->where('updated_at', '<=', $cutoff)
                        ->where('updated_at', '>=', $since);
                })->orWhere(function ($b) use ($since, $cutoff) {
                    self::deepFailedWhere($b, $since)->where('paid_at', '<=', $cutoff);
                })->orWhere(function ($c) use ($now) {
                    self::escalatedWhere($c, $now);
                });
            });
    }

    /**
     * เหตุผลที่บิลนี้ค้าง — null = ไม่ค้าง
     *
     * @return 'ai_generating_timeout'|'deep_job_failed'|'escalated_24h'|null
     */
    public static function stuckReason(FortuneReading $r, ?CarbonInterface $now = null): ?string
    {
        if (! $r->is_paid || $r->isJuntraBill()) {
            return null;
        }

        $now ??= now();
        $cutoff = $now->copy()->subMinutes(self::STUCK_AFTER_MINUTES);
        $since = $now->copy()->subHours(self::LOOKBACK_HOURS);
        $status = (string) $r->conversation_status;

        if (in_array($status, FortuneReading::AI_GENERATING_STATUSES, true)
            && $r->updated_at !== null
            && $r->updated_at->lessThanOrEqualTo($cutoff)
            && $r->updated_at->greaterThanOrEqualTo($since)
            && ! self::generationInFlight($r)) {
            return 'ai_generating_timeout';
        }

        if ($r->reading_type === FortuneReading::READING_TYPE_DEEP
            && $status === FortuneReading::STATUS_COMPLETED
            && ! self::hasDeepResponse($r)
            && $r->paid_at !== null
            && $r->paid_at->lessThanOrEqualTo($cutoff)
            && $r->paid_at->greaterThanOrEqualTo($since)
            && ! self::generationInFlight($r)) {
            return 'deep_job_failed';
        }

        if (self::isEscalated($r, $now) && ! self::generationInFlight($r)) {
            return self::REASON_ESCALATED;
        }

        return null;
    }

    /**
     * (v3) บิลที่ fortune:expire-stuck-paid ส่งต่อให้แอดมิน (ธง admin_review_needed) และยังไม่เสร็จจริง
     * — ตรรกะเดียวกับ escalatedWhere() ทุกข้อ
     */
    public static function isEscalated(FortuneReading $r, ?CarbonInterface $now = null): bool
    {
        if (! $r->is_paid || $r->isJuntraBill() || ! self::hasEscalationFlag($r) || $r->paid_at === null) {
            return false;
        }

        $now ??= now();
        if ($r->paid_at->lessThan($now->copy()->subDays(self::ESCALATED_MAX_AGE_DAYS))) {
            return false;
        }

        $status = (string) $r->conversation_status;

        if ($r->reading_type === FortuneReading::READING_TYPE_DEEP) {
            return in_array($status, array_merge([FortuneReading::STATUS_PAID], FortuneReading::DEEP_ACTIVE_STATUSES), true)
                || ($status === FortuneReading::STATUS_COMPLETED && ! self::hasDeepResponse($r));
        }

        if ($r->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
            return $status !== FortuneReading::STATUS_COMPLETED;
        }

        return false;
    }

    /**
     * ธง admin_review_needed ที่ fortune:expire-stuck-paid ตั้ง (true / "true" / 1)
     */
    public static function hasEscalationFlag(FortuneReading $r): bool
    {
        $flag = $r->getConversationState('admin_review_needed');

        return $flag === true || $flag === 1 || in_array(strtolower((string) $flag), ['true', '1'], true);
    }

    /**
     * เวลาที่ใช้นับ "ค้างมานานเท่าไร"
     *
     * บิลที่ถูกส่งต่อเกิน 24 ชม. นับจากเวลาจ่าย (ลูกค้ารอมาตั้งแต่ตอนนั้น)
     */
    public static function stuckSince(FortuneReading $r): ?CarbonInterface
    {
        if (self::hasEscalationFlag($r)) {
            return $r->paid_at ?? $r->updated_at;
        }

        return $r->conversation_status === FortuneReading::STATUS_COMPLETED
            ? ($r->paid_at ?? $r->updated_at)
            : ($r->updated_at ?? $r->paid_at);
    }

    /**
     * งาน AI ของบิลนี้ยังวิ่งอยู่จริงไหม (ธงใน cache ที่ตัวสร้างคำทำนายตั้งไว้)
     */
    public static function generationInFlight(FortuneReading $r): bool
    {
        try {
            if ($r->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                return CelticCrossService::isGenerationInFlight((int) $r->id);
            }

            return Cache::has('fortune:deep_gen:'.$r->id) || Cache::has('fortune:deep_deliver:'.$r->id);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * มีคำทำนาย Deep แล้วไหม — แถวที่ดึงแบบประหยัด (FortuneBillPresenter::selectListColumns) ไม่มี deep_response
     * แต่มีธง has_deep_response ที่คิดจาก SQL เงื่อนไขเดียวกับ deepFailedWhere (NULL หรือ '' = ไม่มี)
     */
    private static function hasDeepResponse(FortuneReading $r): bool
    {
        $attrs = $r->getAttributes();

        if (array_key_exists('deep_response', $attrs)) {
            return (string) $r->deep_response !== '';
        }

        return (bool) ($attrs['has_deep_response'] ?? false);
    }

    /**
     * (v3) เงื่อนไข SQL ของบิลที่ถูกส่งต่อเกิน 24 ชม. และยังไม่เสร็จ — ตรรกะเดียวกับ isEscalated()
     */
    private static function escalatedWhere($query, CarbonInterface $now)
    {
        return $query
            ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(conversation_state, '$.admin_review_needed')), '') IN ('true', '1')")
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $now->copy()->subDays(self::ESCALATED_MAX_AGE_DAYS))
            ->where(function ($q) {
                $q->where(function ($deep) {
                    $deep->where('reading_type', FortuneReading::READING_TYPE_DEEP)
                        ->where(function ($s) {
                            $s->whereIn('conversation_status', array_merge([FortuneReading::STATUS_PAID], FortuneReading::DEEP_ACTIVE_STATUSES))
                                ->orWhere(function ($c) {
                                    $c->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                        ->where(function ($e) {
                                            $e->whereNull('deep_response')->orWhere('deep_response', '');
                                        });
                                });
                        });
                })->orWhere(function ($celtic) {
                    $celtic->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                        ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED);
                });
            });
    }

    /**
     * เงื่อนไข Deep ที่งาน AI ล้ม (completed + deep_response ว่าง) ภายในหน้าต่างเวลา
     */
    private static function deepFailedWhere($query, CarbonInterface $since)
    {
        return $query->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->whereNull('deep_response')->orWhere('deep_response', '');
            })
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $since);
    }
}
