<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎂 BirthdateResolver — แหล่งความจริงเดียวของคำถาม "เรารู้ ว/ด/ป ของลูกค้าคนนี้ไหม"
 *
 * ปัญหาที่คลาสนี้เกิดมาแก้ (2026-08-21):
 *   เส้นดวงฟรีรายวันเก็บวันเกิดลง `fortune_user_credits` และ **ไม่สร้างแถวใน `fortune_readings`**
 *   แต่เส้นซื้อ (Deep 39 / Celtic 99) ค้นแต่ `fortune_readings` ⇒ ลูกค้า 727 คนที่เคยพิมพ์
 *   วันเกิดให้ตอนขอดวงฟรี ถูกถามซ้ำ 100% ตอนจ่ายเงิน — และหลายคนกรอกใหม่ไม่ตรงเดิม
 *   (prod: reading 11370 ได้ 1960-07-21 แต่ของเดิม 1960-06-21 · reading 11302 วัน/เดือนสลับ)
 *   = คำทำนายที่ลูกค้าจ่ายเงินมาผิดตั้งแต่พื้นดวง ไม่ใช่แค่เรื่อง UX
 *
 * 🚨 กติกาที่ห้ามพลาด
 *   1. **ห้ามอ่าน `birth_day` เด็ดขาด** — นั่นคือ "วันในสัปดาห์" ไม่ใช่วันเกิด
 *      ลูกค้า 2,523 คนรู้แค่ชื่อวัน ถ้าปนเข้ามาบอทจะพูดว่า "แม่หมอจดวันเกิดไว้แล้ว"
 *      แล้วพอถึงขั้นทำนายจริงต้องถามใหม่ = บอทโกหกลูกค้า
 *      ใครอยากได้วันในสัปดาห์ให้ใช้ `FortuneUserCredit::findBirthDayIndex()` ตามเดิม
 *   2. **ห้ามกรองด้วยคอลัมน์ `birth_date_source`** — คอลัมน์นั้นถูกเขียนทับทุกครั้งที่ลูกค้าตอบ
 *      ส่วน `birth_date` เขียนครั้งเดียวตลอดกาล (DailyHoroscopeModeTrait::rememberDailyBirthInfo)
 *      ⇒ คนที่พิมพ์ ว/ด/ป ก่อนแล้ววันหลังกดปุ่มชื่อวัน จะได้ source='daily_dm_button'
 *      ทั้งที่ค่าจริงมาจากการพิมพ์ (prod มี 144 คนแบบนี้) กรองแล้วทิ้งคนกลุ่มนี้ฟรี ๆ
 *   3. **ข้อมูลชั้น credits ต้องผ่านกล่องยืนยันเสมอ** (`mustConfirm()`)
 *      เพราะเส้นดวงฟรีไม่มีด่านกัน "วันเกิดคนอื่น" (ลูกค้าพิมพ์ "แฟนเกิด 12/3/2535" ก็ถูกเก็บ)
 *   4. **ห้ามเรียงด้วย `updated_at`** — คอลัมน์นั้นขยับทุกครั้งที่ `setConversationState()`
 *      บิลฟรีที่ยัง active จะกลบบิลที่จ่ายเงินไปแล้ว
 */
final class BirthdateResolver
{
    /** วันเกิดจากบิลที่ลูกค้าจ่ายเงินแล้ว — น่าเชื่อถือที่สุด */
    public const SRC_PAID_READING = 'paid_reading';

    /** วันเกิดจากบิลทั่วไป (ยังไม่จ่าย / ฟรี) */
    public const SRC_READING = 'reading';

    /** วันเกิดจากที่ลูกค้าพิมพ์ตอนขอดวงฟรีรายวันทาง DM */
    public const SRC_DAILY_DM = 'daily_dm';

    /**
     * หา ว/ด/ป ล่าสุดที่เชื่อถือได้ที่สุดของลูกค้าคนนี้
     *
     * ลำดับ: บิลที่จ่ายเงิน → บิลทั่วไป → วันเกิดจาก DM ดวงฟรี
     *
     * @param  string  $fbId  facebook_user_id (FB PSID)
     * @param  string  $platformId  platform_user_id (LINE userId / PSID)
     * @param  string|null  $platform  facebook | line · null = ไม่กรอง platform ชั้น credits
     *                                 (ใช้กับ caller เก่าที่ไม่รู้ platform — คงพฤติกรรมเดิมเป๊ะ)
     * @param  int|null  $excludeReadingId  บิลปัจจุบัน (กันดึงของตัวเอง)
     * @return array{ymd:string,date:Carbon,source:string,reading_id:int|null}|null
     */
    public static function resolve(
        string $fbId,
        string $platformId = '',
        ?string $platform = 'facebook',
        ?int $excludeReadingId = null
    ): ?array {
        $fbId = trim($fbId);
        $platformId = trim($platformId);

        if ($fbId === '' && $platformId === '') {
            return null;
        }

        // ── ชั้น 1+2: fortune_readings (บิลจ่ายเงินชนะเสมอ)
        $hit = self::fromReadings($fbId, $platformId, $excludeReadingId);

        if ($hit !== null) {
            return $hit;
        }

        // ── ชั้น 3: fortune_user_credits (เส้นดวงฟรีรายวัน)
        return self::fromCredits($fbId !== '' ? $fbId : $platformId, $platform);
    }

    /**
     * เวอร์ชันที่รับ reading ตรง ๆ — ดึง id/platform ให้เอง
     *
     * @return array{ymd:string,date:Carbon,source:string,reading_id:int|null}|null
     */
    public static function forReading(FortuneReading $reading): ?array
    {
        return self::resolve(
            (string) ($reading->facebook_user_id ?? ''),
            (string) ($reading->platform_user_id ?? ''),
            (string) ($reading->platform ?: 'facebook'),
            $reading->id ? (int) $reading->id : null,
        );
    }

    /**
     * ป้ายบอกที่มาของวันเกิด — ใช้ต่อท้ายข้อความยืนยันให้ลูกค้ารู้ว่าเราเอามาจากไหน
     *
     * ⚠️ ต้องพูดตามจริงเสมอ ห้ามบอกว่า "จากบิลที่เคยดู" กับข้อมูลที่มาจากช่องทางฟรี
     */
    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            self::SRC_PAID_READING => '(จากบิลที่เจ้าชะตาเคยดูกับแม่หมอ)',
            self::SRC_READING => '(จากที่เคยกรอกไว้กับแม่หมอ)',
            self::SRC_DAILY_DM => '(จากที่เจ้าชะตาเคยพิมพ์บอกแม่หมอตอนขอดวงรายวัน)',
            default => '',
        };
    }

    /**
     * ที่มานี้ต้องให้ลูกค้ายืนยันก่อนใช้ไหม
     *
     * ชั้น credits = true เสมอ เพราะไม่เคยผ่านด่านกัน "วันเกิดคนอื่น"
     * (ชั้น reading ก็ควรยืนยันด้วย แต่เป็นข้อมูลที่ลูกค้าเคยกรอกในบริบทของบิลจริง)
     */
    public static function mustConfirm(string $source): bool
    {
        return $source === self::SRC_DAILY_DM;
    }

    /**
     * ชั้น 1+2 — fortune_readings
     *
     * @return array{ymd:string,date:Carbon,source:string,reading_id:int|null}|null
     */
    protected static function fromReadings(string $fbId, string $platformId, ?int $excludeReadingId): ?array
    {
        try {
            $query = FortuneReading::query()
                ->whereNotNull('birth_date')
                ->where(function ($q) use ($fbId, $platformId) {
                    if ($fbId !== '') {
                        $q->where('facebook_user_id', $fbId);
                    }
                    // fortune_readings ไม่มีคอลัมน์ line_user_id — LINE ใช้ platform_user_id
                    if ($platformId !== '') {
                        $q->orWhere('platform_user_id', $platformId);
                    }
                });

            if ($excludeReadingId !== null) {
                $query->where('id', '!=', $excludeReadingId);
            }

            // 🔑 บิลที่จ่ายเงินชนะเสมอ แล้วค่อยดูว่าใครใหม่กว่า
            //    ห้ามใช้ updated_at (ขยับทุกครั้งที่ setConversationState)
            $prior = $query
                ->orderByDesc('is_paid')
                ->orderByRaw('COALESCE(paid_at, created_at) DESC')
                ->orderByDesc('id')
                ->first(['id', 'birth_date', 'is_paid']);
        } catch (\Throwable $e) {
            Log::warning('BirthdateResolver: query readings ล้ม (ถือว่าไม่เจอ)', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $prior || empty($prior->birth_date)) {
            return null;
        }

        try {
            $date = Carbon::parse($prior->birth_date);
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'ymd' => $date->format('Y-m-d'),
            'date' => $date,
            'source' => $prior->is_paid ? self::SRC_PAID_READING : self::SRC_READING,
            'reading_id' => (int) $prior->id,
        ];
    }

    /**
     * ชั้น 3 — fortune_user_credits (วันเกิดที่ลูกค้าพิมพ์ตอนขอดวงฟรีรายวัน)
     *
     * @return array{ymd:string,date:Carbon,source:string,reading_id:int|null}|null
     */
    protected static function fromCredits(string $userId, ?string $platform): ?array
    {
        if ($userId === '') {
            return null;
        }

        try {
            // ช่วง deploy ที่โค้ดขึ้นก่อน migrate — ถือว่าไม่มีข้อมูล
            if (! Schema::hasColumn('fortune_user_credits', 'birth_date')) {
                return null;
            }

            // ⚠️ ตารางนี้เก็บ LINE userId ในคอลัมน์ชื่อ facebook_user_id แล้วแยกด้วย platform
            //    caller ที่รู้ platform ต้องส่งมาเสมอ ไม่งั้นข้ามช่องทางกันได้
            //    ส่ง null = caller เก่าที่ไม่รู้ platform → ไม่กรอง (พฤติกรรมเดิมของ findLatestBirthdate)
            $credit = FortuneUserCredit::query()
                ->where('facebook_user_id', $userId)
                ->when($platform !== null, fn ($q) => $q->where('platform', $platform))
                ->whereNotNull('birth_date')
                ->orderByDesc('birth_date_at')
                ->first(['birth_date']);
        } catch (\Throwable $e) {
            Log::warning('BirthdateResolver: query credits ล้ม (ถือว่าไม่เจอ)', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $credit || empty($credit->birth_date)) {
            return null;
        }

        try {
            $date = Carbon::parse($credit->birth_date);
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'ymd' => $date->format('Y-m-d'),
            'date' => $date,
            'source' => self::SRC_DAILY_DM,
            'reading_id' => null,
        ];
    }
}
