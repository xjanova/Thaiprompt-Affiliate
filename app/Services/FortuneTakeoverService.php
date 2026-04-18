<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FortuneTakeoverService
 *
 * Service กลางสำหรับจัดการระบบเทคโอเวอร์ (แอดมิน/แม่หมอคุยแทน AI)
 * ใช้งานร่วมกันระหว่าง LINE และ Facebook เพื่อป้องกัน logic ขัดแย้งกัน
 *
 * หลักการทำงาน:
 * 1. DB เป็นแหล่งข้อมูลหลัก (fortune_readings.admin_takeover_until)
 * 2. Cache เป็น fast path สำหรับเช็คก่อน AI ตอบ (ลด DB query)
 * 3. Cache จะหมดอายุตามเวลา takeover พอดี
 * 4. ถ้า cache miss → เช็ค DB (reliable)
 *
 * Flow หลัก:
 * - takeover(): เริ่มเทคโอเวอร์ (manual/auto_reply/customer_request)
 * - resume(): สั่งให้ AI กลับมาทำงาน
 * - extend(): ต่อเวลา
 * - isActive(): เช็คว่ากำลังเทคโอเวอร์อยู่หรือไม่ (AI ต้องเรียกก่อนตอบ)
 * - detectCustomerHandoffRequest(): ตรวจคำลูกค้า "อยากคุยกับคน"
 * - detectAdminResumeCommand(): ตรวจคำแอดมิน "/ai"
 */
class FortuneTakeoverService
{
    /**
     * Cooldown สำหรับ customer handoff spam (วินาที)
     *
     * ลูกค้าพิมพ์ "คุยกับคน" ซ้ำใน cooldown นี้ → ข้าม ไม่รีเซ็ตเวลา takeover
     */
    protected const CUSTOMER_HANDOFF_COOLDOWN_SECONDS = 60;

    /**
     * ดึง settings สดจาก singleton (ไม่ cache ใน instance — กัน Octane/Swoole stale)
     */
    protected function settings(): FortuneTellingSetting
    {
        return FortuneTellingSetting::getSettings();
    }

    // ============================================================
    // Active Check (เรียกก่อน AI ตอบทุกครั้ง)
    // ============================================================

    /**
     * ตรวจสอบว่า conversation นี้กำลังถูกเทคโอเวอร์อยู่หรือไม่
     *
     * เช็คผ่าน Cache ก่อน (fast path) → ถ้า miss ค่อยเช็ค DB
     * ถ้าระบบถูกปิดในตั้งค่า → return false ทันที
     *
     * @param  FortuneReading|null  $reading  conversation ที่ต้องการเช็ค
     */
    public function isActive(?FortuneReading $reading): bool
    {
        if (! $reading) {
            return false;
        }

        // ถ้าปิดระบบไว้ → ไม่เทคโอเวอร์เลย (ป้องกันบอทหยุดโดยไม่ตั้งใจ)
        if (! $this->settings()->isTakeoverEnabled()) {
            return false;
        }

        // Fast path: เช็ค Cache ก่อน
        $cacheKey = $reading->getTakeoverCacheKey();
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Fallback: เช็ค DB (เฉพาะกรณี cache หาย)
        // refresh เพื่อให้ได้ค่าล่าสุด (ข้าม attribute cache ในอ็อบเจ็กต์)
        $reading->refresh();

        $active = $reading->isAdminTakenOver();

        // ถ้า active + มีเวลาเหลือจริง → เติม cache (กัน stale cache)
        if ($active && $reading->admin_takeover_until) {
            $ttl = (int) now()->diffInSeconds($reading->admin_takeover_until, false);
            if ($ttl > 0) {
                Cache::put($cacheKey, true, $ttl);
            }
        }

        return $active;
    }

    /**
     * ตรวจสอบโดยใช้ platform identifier (สำหรับ Facebook/LINE webhook)
     *
     * ใช้เมื่อยังไม่มี FortuneReading instance
     *
     * @param  string  $platform  'line' หรือ 'facebook'
     * @param  string  $platformUserId  user id ของ platform นั้นๆ
     */
    public function isActiveByPlatform(string $platform, string $platformUserId): bool
    {
        if (! $this->settings()->isTakeoverEnabled()) {
            return false;
        }

        $cacheKey = "fortune_admin_active:{$platform}:{$platformUserId}";

        // Fast path: Cache
        if (Cache::has($cacheKey)) {
            return true;
        }

        // ค้นหา reading ล่าสุดของ user นี้ (เฉพาะที่ยังเปิดอยู่)
        $reading = FortuneReading::where(function ($q) use ($platform, $platformUserId) {
            // Match platform ปกติ
            $q->where(function ($sub) use ($platform, $platformUserId) {
                $sub->where('platform', $platform)
                    ->where('platform_user_id', $platformUserId);
            });

            // Legacy Facebook: rows เก่าอาจไม่มี platform/platform_user_id
            // ให้ match facebook_user_id โดยไม่ filter platform (กัน legacy rows)
            if ($platform === 'facebook') {
                $q->orWhere('facebook_user_id', $platformUserId);
            }
        })
            ->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', now())
            ->latest()
            ->first();

        if (! $reading) {
            return false;
        }

        // เติม cache เฉพาะเมื่อ TTL > 0 (กัน stale true cache 1 วิ เมื่อเวลาหมดแล้ว)
        $ttl = (int) now()->diffInSeconds($reading->admin_takeover_until, false);
        if ($ttl > 0) {
            Cache::put($cacheKey, true, $ttl);
        }

        return true;
    }

    // ============================================================
    // Takeover Action
    // ============================================================

    /**
     * เริ่มเทคโอเวอร์ conversation
     *
     * @param  FortuneReading  $reading  conversation ที่ต้องการเทคโอเวอร์
     * @param  string  $reason  เหตุผล (manual, auto_reply, customer_request)
     * @param  int|null  $adminId  user_id ของแอดมิน (null ถ้ามาจากลูกค้า)
     * @param  int|null  $minutes  ระยะเวลา (นาที) — null = ใช้ default
     * @param  string|null  $messagePreview  ข้อความที่ trigger
     * @return int  นาทีที่เทคโอเวอร์จริง
     */
    public function takeover(
        FortuneReading $reading,
        string $reason = FortuneReading::TAKEOVER_REASON_MANUAL,
        ?int $adminId = null,
        ?int $minutes = null,
        ?string $messagePreview = null,
        bool $forceIgnoreDisabled = false,
    ): int {
        // ถ้าระบบปิดอยู่ → ไม่ทำอะไร (ยกเว้นแอดมินสั่งเอง ผ่านแอดมินพาเนล)
        if (! $forceIgnoreDisabled && ! $this->settings()->isTakeoverEnabled()) {
            Log::info('FortuneTakeover: ระบบปิด — ข้าม takeover', [
                'reading_id' => $reading->id,
                'reason' => $reason,
            ]);

            return 0;
        }

        // Cooldown สำหรับ customer spam — ป้องกันลูกค้า reset timer ด้วยการพิมพ์ "คุยกับคน" ซ้ำ
        if ($reason === FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST
            && $this->hasRecentCustomerHandoff($reading)) {
            Log::info('FortuneTakeover: ข้าม (อยู่ใน cooldown customer handoff)', [
                'reading_id' => $reading->id,
                'cooldown_seconds' => self::CUSTOMER_HANDOFF_COOLDOWN_SECONDS,
            ]);

            // คืนเวลาที่เหลือปัจจุบัน (ถ้ามี takeover อยู่)
            return $reading->isAdminTakenOver() ? $reading->takeoverRemainingMinutes() : 0;
        }

        // ใช้ default ถ้าไม่ระบุ
        $minutes = $minutes ?: $this->settings()->getTakeoverDefaultMinutes();
        $minutes = max(1, min(1440, $minutes)); // 1 นาที - 24 ชั่วโมง

        $until = now()->addMinutes($minutes);

        // บันทึกใน DB (transaction เพื่อให้ log + reading อัพเดตพร้อมกัน)
        DB::transaction(function () use ($reading, $reason, $adminId, $minutes, $messagePreview, $until) {
            $reading->update([
                'admin_takeover_until' => $until,
                'admin_takeover_by' => $adminId,
                'admin_takeover_reason' => $reason,
                'admin_takeover_started_at' => now(),
            ]);

            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => $adminId,
                'action' => FortuneTakeoverLog::ACTION_TAKEOVER,
                'reason' => $reason,
                'duration_minutes' => $minutes,
                'message' => $messagePreview ? mb_substr($messagePreview, 0, 500) : null,
                'platform' => $reading->platform,
            ]);
        });

        // เติม cache (fast path) — เฉพาะเมื่อ TTL > 0
        $cacheKey = $reading->getTakeoverCacheKey();
        $ttlSeconds = (int) now()->diffInSeconds($until, false);
        if ($ttlSeconds > 0) {
            Cache::put($cacheKey, true, $ttlSeconds);

            // Cache key เก่า (backward compatibility กับ FacebookWebhookController เดิม)
            if (in_array($reading->platform, ['facebook', null], true)) {
                $legacyKey = 'fortune_admin_active:'.($reading->facebook_user_id ?? $reading->platform_user_id);
                Cache::put($legacyKey, [
                    'active_at' => now()->toIso8601String(),
                    'admin_message' => $messagePreview ? mb_substr($messagePreview, 0, 100) : null,
                ], $ttlSeconds);
            }
        }

        Log::info('🎯 FortuneTakeover: เริ่มเทคโอเวอร์', [
            'reading_id' => $reading->id,
            'platform' => $reading->platform,
            'reason' => $reason,
            'admin_id' => $adminId,
            'minutes' => $minutes,
            'until' => $until->toIso8601String(),
        ]);

        return $minutes;
    }

    /**
     * สั่งให้ AI กลับมาทำงาน
     *
     * @param  FortuneReading  $reading
     * @param  int|null  $adminId  user_id ของแอดมิน (null ถ้า auto-expire)
     * @param  bool  $fromCommand  มาจากคำสั่ง /ai หรือปุ่ม
     */
    public function resume(
        FortuneReading $reading,
        ?int $adminId = null,
        bool $fromCommand = false,
    ): void {
        // ถ้าไม่ได้เทคโอเวอร์อยู่ → ไม่ต้องทำอะไร
        $reading->refresh();
        if (empty($reading->admin_takeover_until)) {
            return;
        }

        DB::transaction(function () use ($reading, $adminId, $fromCommand) {
            $reading->update([
                'admin_takeover_until' => null,
                // เก็บ admin_takeover_by + started_at ไว้เป็น audit trail
            ]);

            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => $adminId,
                'action' => FortuneTakeoverLog::ACTION_RESUME,
                'reason' => $fromCommand ? 'command' : 'manual',
                'platform' => $reading->platform,
            ]);
        });

        // ล้าง cache ทั้งหมด
        $this->clearCaches($reading);

        Log::info('✨ FortuneTakeover: AI กลับมาทำงาน', [
            'reading_id' => $reading->id,
            'admin_id' => $adminId,
            'from_command' => $fromCommand,
        ]);
    }

    /**
     * ต่อเวลาเทคโอเวอร์
     *
     * @param  FortuneReading  $reading
     * @param  int  $minutes  เวลาที่ต้องการเพิ่ม
     * @param  int|null  $adminId
     */
    public function extend(FortuneReading $reading, int $minutes, ?int $adminId = null): int
    {
        $minutes = max(1, min(1440, $minutes));

        // Refresh เพื่อให้ได้สถานะปัจจุบัน (กัน race กับ auto-expire)
        $reading->refresh();

        // ถ้าไม่ active อยู่ → ปฏิเสธ (ต้องสั่ง takeover ใหม่แทน)
        if (! $reading->isAdminTakenOver()) {
            Log::info('FortuneTakeover: ปฏิเสธ extend (ไม่ได้ takeover อยู่) — ใช้ takeover ใหม่แทน', [
                'reading_id' => $reading->id,
            ]);

            return $this->takeover($reading, FortuneReading::TAKEOVER_REASON_MANUAL, $adminId, $minutes);
        }

        // ต่อเวลาจาก takeover ปัจจุบัน
        $until = $reading->admin_takeover_until->copy()->addMinutes($minutes);

        DB::transaction(function () use ($reading, $until, $minutes, $adminId) {
            $reading->update([
                'admin_takeover_until' => $until,
                'admin_takeover_by' => $adminId ?? $reading->admin_takeover_by,
            ]);

            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => $adminId,
                'action' => FortuneTakeoverLog::ACTION_EXTEND,
                'duration_minutes' => $minutes,
                'platform' => $reading->platform,
            ]);
        });

        // อัพเดต cache (เฉพาะเมื่อ TTL > 0)
        $cacheKey = $reading->getTakeoverCacheKey();
        $ttlSeconds = (int) now()->diffInSeconds($until, false);
        if ($ttlSeconds > 0) {
            Cache::put($cacheKey, true, $ttlSeconds);
        }

        Log::info('⏱ FortuneTakeover: ต่อเวลา', [
            'reading_id' => $reading->id,
            'minutes_added' => $minutes,
            'until' => $until->toIso8601String(),
        ]);

        return $minutes;
    }

    /**
     * บันทึกข้อความที่แอดมินส่ง (ผ่านแอดมินพาเนล)
     */
    public function logMessage(
        FortuneReading $reading,
        int $adminId,
        string $message,
    ): FortuneTakeoverLog {
        return FortuneTakeoverLog::create([
            'fortune_reading_id' => $reading->id,
            'user_id' => $adminId,
            'action' => FortuneTakeoverLog::ACTION_MESSAGE,
            'message' => mb_substr($message, 0, 2000),
            'platform' => $reading->platform,
        ]);
    }

    /**
     * ตรวจว่ามี customer handoff request ล่าสุดภายใน cooldown หรือไม่
     *
     * ป้องกันลูกค้าสแปมคำ "คุยกับคน" เพื่อ reset timer ของการเทคโอเวอร์
     */
    protected function hasRecentCustomerHandoff(FortuneReading $reading): bool
    {
        $threshold = now()->subSeconds(self::CUSTOMER_HANDOFF_COOLDOWN_SECONDS);

        return FortuneTakeoverLog::where('fortune_reading_id', $reading->id)
            ->where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
            ->where('reason', FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST)
            ->where('created_at', '>=', $threshold)
            ->exists();
    }

    // ============================================================
    // Message Detection (เรียกก่อน AI ตอบ)
    // ============================================================

    /**
     * ตรวจสอบว่าข้อความลูกค้าบ่งบอกว่าอยากคุยกับคนจริงหรือไม่
     *
     * Match แบบ substring + case-insensitive
     */
    public function detectCustomerHandoffRequest(string $message): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        $lower = mb_strtolower($message);
        $keywords = $this->settings()->getCustomerHandoffKeywords();

        foreach ($keywords as $keyword) {
            $k = mb_strtolower(trim($keyword));
            if ($k !== '' && mb_strpos($lower, $k) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งให้ AI กลับมาหรือไม่ (แอดมินพิมพ์)
     *
     * Match แบบ exact (เริ่มต้นของข้อความ, trim แล้ว)
     */
    public function detectAdminResumeCommand(string $message): bool
    {
        $trimmed = trim($message);
        if ($trimmed === '') {
            return false;
        }

        $command = $this->settings()->getAiResumeCommand();

        // Match ทั้งคำสั่งเดี่ยวๆ หรือขึ้นต้นด้วยคำสั่ง + space
        return strcasecmp($trimmed, $command) === 0
            || stripos($trimmed, $command.' ') === 0;
    }

    // ============================================================
    // Cache Management
    // ============================================================

    /**
     * ล้าง cache ทั้งหมดที่เกี่ยวข้องกับ reading นี้
     */
    protected function clearCaches(FortuneReading $reading): void
    {
        Cache::forget($reading->getTakeoverCacheKey());

        // Legacy key (backward compatibility)
        $legacyId = $reading->facebook_user_id ?? $reading->platform_user_id;
        if ($legacyId) {
            Cache::forget('fortune_admin_active:'.$legacyId);
        }
    }

    // ============================================================
    // Cleanup (เรียกจาก scheduled task หรือ on-demand)
    // ============================================================

    /**
     * ล้าง takeover ที่หมดเวลาแล้วทั้งหมด
     *
     * @return int จำนวน reading ที่ถูก expire
     */
    public function cleanupExpired(): int
    {
        $expired = FortuneReading::takeoverExpired()->get();

        foreach ($expired as $reading) {
            DB::transaction(function () use ($reading) {
                $reading->update(['admin_takeover_until' => null]);

                FortuneTakeoverLog::create([
                    'fortune_reading_id' => $reading->id,
                    'action' => FortuneTakeoverLog::ACTION_AUTO_EXPIRE,
                    'platform' => $reading->platform,
                ]);
            });

            $this->clearCaches($reading);
        }

        return $expired->count();
    }
}
