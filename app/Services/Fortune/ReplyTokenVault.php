<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🎟️ (2026-08-26) ตู้เก็บ replyToken ชั่วคราว — ให้ job ที่ทำงานหลัง webhook ยืมไปตอบฟรี
 *
 * ## ทำไมต้องมี
 * LINE คิดเงิน push แต่ **reply ฟรีไม่จำกัด** — แพลนที่ใช้อยู่มีแค่ 300 push/เดือน
 * แต่คำตอบ Celtic/Deep วิ่งผ่าน job (settle-buffer) ซึ่ง "ไม่มี replyToken"
 * ⇒ ตกไป push ทุกครั้ง ⇒ โควต้าหมดใน ~15 เซสชัน (เหตุการณ์ 2026-08-25: 300/300)
 *
 * ## ทำไมทำได้
 * วัดจากของจริง 1,958 คำถาม (60 วัน):
 *   debounce 10s + AI p50 8.9s = ~20s · p95 22.2s = ~33s
 * ทั้งคู่อยู่ใน **หน้าต่าง reply 60 วินาที** สบายๆ — แค่ต้องพก token ข้ามไปให้ job
 *
 * ## กติกาที่ห้ามพลาด
 * - replyToken ใช้ได้ **ครั้งเดียว** → `take()` เป็น pull (อ่านแล้วลบทันที) ห้ามใช้ get
 * - อายุจริง 60s → เก็บ TTL 55s + เช็คอายุซ้ำตอนใช้ (เผื่อ queue ค้าง)
 * - อยู่บน Cache ล้วนโดยตั้งใจ: token เป็นของชั่วคราวจริงๆ หายแล้วแค่ตกไป push
 *   = พฤติกรรมเดิมก่อนมีคลาสนี้ **ไม่มี regression**
 *   (ต่างจากของลูกค้าที่จ่ายเงินแล้ว ซึ่งห้ามอยู่บน Cache อย่างเดียว)
 */
class ReplyTokenVault
{
    /** อายุที่ยอมให้ยืม (วินาที) — ต่ำกว่าอายุจริง 60s ไว้เป็นกันชน */
    public const MAX_AGE_SECONDS = 50;

    /** TTL ของ cache — เผื่อกว่า MAX_AGE เล็กน้อย ให้ตัวเช็คอายุเป็นคนตัดสิน */
    protected const TTL_SECONDS = 55;

    protected static function key(string $platform, string $userId): string
    {
        return "reply_token:{$platform}:{$userId}";
    }

    /**
     * ฝาก token ไว้ให้ job มายืม
     *
     * เรียกที่ webhook ตอนรับข้อความ — ก่อนรู้ว่าเทิร์นนี้จะใช้ token เองหรือเปล่า
     * ถ้าเทิร์นนี้ใช้เอง job จะยืมไปเจอ token ตายแล้ว → ตกไป push (เท่าเดิม ไม่แย่ลง)
     */
    public static function remember(string $platform, string $userId, ?string $replyToken): void
    {
        if (empty($replyToken)) {
            return;
        }

        try {
            Cache::put(self::key($platform, $userId), [
                'token' => $replyToken,
                'at' => now()->timestamp,
            ], self::TTL_SECONDS);
        } catch (\Throwable $e) {
            // ฝากไม่ได้ = แค่เสียโอกาสตอบฟรี ห้ามทำให้ flow ล้ม
            Log::debug('ReplyTokenVault: remember fail (non-blocking)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ยืม token ออกมาใช้ (อ่านแล้วลบทันที — กันสองที่แย่งใช้ token เดียวกัน)
     *
     * @return string|null null = ไม่มี / หมดอายุแล้ว → caller ต้องตกไป push ตามเดิม
     */
    public static function take(string $platform, string $userId): ?string
    {
        try {
            $data = Cache::pull(self::key($platform, $userId));

            if (! is_array($data) || empty($data['token'])) {
                return null;
            }

            $age = now()->timestamp - (int) ($data['at'] ?? 0);

            if ($age > self::MAX_AGE_SECONDS) {
                Log::debug('ReplyTokenVault: token เก่าเกินยืม', [
                    'platform' => $platform,
                    'age_seconds' => $age,
                ]);

                return null;
            }

            return (string) $data['token'];
        } catch (\Throwable $e) {
            Log::debug('ReplyTokenVault: take fail (non-blocking)', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ทิ้ง token (เรียกเมื่อเทิร์น webhook ใช้ token นั้นไปเองแล้ว)
     */
    public static function forget(string $platform, string $userId): void
    {
        try {
            Cache::forget(self::key($platform, $userId));
        } catch (\Throwable $e) {
            Log::debug('ReplyTokenVault: forget fail (non-blocking)', ['error' => $e->getMessage()]);
        }
    }
}
