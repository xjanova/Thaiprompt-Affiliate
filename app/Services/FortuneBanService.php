<?php

namespace App\Services;

use App\Models\FortuneUserBan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FortuneBanService
 *
 * ระบบ "คุก" (Ban/Jail) สำหรับห้ามบอทคุยกับ user ที่ไม่เหมาะสม
 *
 * ⚠️ ต่างจาก FortuneTakeoverService:
 *   - Takeover  = บอทพักชั่วคราว (10-60 นาที) ให้แอดมินคุยแทน — user ปกติ
 *   - Ban       = ห้ามบอทคุย "เลย" (ถาวร/ระยะยาว) — user ก่อกวน
 *     แอดมินยังคุยได้ผ่าน Page Inbox/admin panel (เพราะใช้ Page API ตรง ไม่ผ่านบอท)
 *
 * Flow:
 * 1. webhook entry → isBanned() → ถ้า true ส่งข้อความเตือน 1 ครั้ง + return ทันที
 * 2. anti-spam — ส่งข้อความตอบครั้งเดียวต่อ cooldown 1 ชม. หลังจากนั้นเงียบ
 * 3. แอดมินจัดการผ่าน /admin/fortune/banned-users
 */
class FortuneBanService
{
    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'fortune_ban_active:';

    /**
     * Cooldown ก่อนส่งข้อความเตือนซ้ำ (วินาที) — กัน spam
     * ค่า default = 1 ชั่วโมง
     */
    protected const NOTIFY_COOLDOWN_SECONDS = 3600;

    /**
     * ตรวจว่า user ถูกแบนอยู่หรือไม่ (พร้อม cache fast path)
     */
    public function isBanned(string $platform, string $platformUserId): bool
    {
        return $this->getActiveBan($platform, $platformUserId) !== null;
    }

    /**
     * ดึง active ban record ของ user (ถ้ามี)
     *
     * @return FortuneUserBan|null
     */
    public function getActiveBan(string $platform, string $platformUserId): ?FortuneUserBan
    {
        $cacheKey = $this->cacheKey($platform, $platformUserId);

        // Fast path: cache hit = banned
        $cachedId = Cache::get($cacheKey);
        if ($cachedId !== null) {
            $ban = FortuneUserBan::find($cachedId);
            if ($ban && $ban->isActive()) {
                return $ban;
            }
            // Cache stale (record หมดอายุ หรือถูกลบ) → ล้าง
            Cache::forget($cacheKey);
        }

        // Slow path: DB
        $ban = FortuneUserBan::query()
            ->forPlatform($platform)
            ->where('platform_user_id', $platformUserId)
            ->active()
            ->first();

        if (! $ban) {
            return null;
        }

        // เติม cache เฉพาะเมื่อมี TTL เหลือ
        if ($ban->isPermanent()) {
            // ถาวร — cache 1 วัน (เผื่อ admin unban)
            Cache::put($cacheKey, $ban->id, 86400);
        } else {
            // ใช้ timestamp ลบกัน แทน diffInSeconds (กัน Carbon v3 absolute)
            $ttl = $ban->banned_until->getTimestamp() - now()->getTimestamp();
            if ($ttl > 0) {
                Cache::put($cacheKey, $ban->id, $ttl);
            }
        }

        return $ban;
    }

    /**
     * แบน user
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $platformUserId  PSID หรือ LINE userId
     * @param  int|null  $minutes  null = ถาวร, > 0 = แบน N นาที
     * @param  string|null  $reason  เหตุผล (audit)
     * @param  int|null  $adminId  user_id ของแอดมิน
     * @param  string|null  $displayName  ชื่อที่แสดง (snapshot)
     */
    public function ban(
        string $platform,
        string $platformUserId,
        ?int $minutes = null,
        ?string $reason = null,
        ?int $adminId = null,
        ?string $displayName = null,
    ): FortuneUserBan {
        $bannedUntil = $minutes !== null && $minutes > 0
            ? Carbon::now()->addMinutes($minutes)
            : null;

        $ban = FortuneUserBan::updateOrCreate(
            [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ],
            [
                'display_name' => $displayName,
                'reason' => $reason,
                'banned_until' => $bannedUntil,
                'banned_by' => $adminId,
                // รีเซ็ต counter เมื่อแบนใหม่
                'last_notified_at' => null,
                'notify_count' => 0,
                'attempt_count' => 0,
            ]
        );

        $this->clearCache($platform, $platformUserId);

        Log::warning('🚫 FortuneBan: แบน user', [
            'ban_id' => $ban->id,
            'platform' => $platform,
            'user_id' => $platformUserId,
            'permanent' => $bannedUntil === null,
            'minutes' => $minutes,
            'reason' => $reason,
            'admin_id' => $adminId,
        ]);

        return $ban;
    }

    /**
     * ปลดแบน user
     */
    public function unban(string $platform, string $platformUserId, ?int $adminId = null): bool
    {
        $ban = FortuneUserBan::query()
            ->forPlatform($platform)
            ->where('platform_user_id', $platformUserId)
            ->first();

        if (! $ban) {
            return false;
        }

        $banId = $ban->id;
        $ban->delete();

        $this->clearCache($platform, $platformUserId);

        Log::info('✨ FortuneBan: ปลดแบน user', [
            'ban_id' => $banId,
            'platform' => $platform,
            'user_id' => $platformUserId,
            'admin_id' => $adminId,
        ]);

        return true;
    }

    /**
     * บันทึกว่า user ที่ถูกแบนพยายามทักบอท + คืนว่าควรส่งข้อความเตือนหรือไม่
     *
     * @return bool true ถ้าควรส่งข้อความเตือน (ครั้งแรก หรือพ้น cooldown แล้ว)
     */
    public function shouldNotify(FortuneUserBan $ban): bool
    {
        // เพิ่ม attempt_count ทุกครั้งที่ทัก (สำหรับตรวจ spam)
        $ban->increment('attempt_count');

        // ไม่เคยแจ้ง → แจ้ง
        if ($ban->last_notified_at === null) {
            return true;
        }

        // ผ่าน cooldown แล้ว → แจ้งใหม่
        // ใช้ compare ตรง — กัน Carbon diffInSeconds() return absolute ใน v3
        return $ban->last_notified_at->lt(now()->subSeconds(self::NOTIFY_COOLDOWN_SECONDS));
    }

    /**
     * บันทึกว่าได้ส่งข้อความแจ้งสถานะแบนแล้ว
     */
    public function recordNotification(FortuneUserBan $ban): void
    {
        $ban->update([
            'last_notified_at' => now(),
            'notify_count' => $ban->notify_count + 1,
        ]);
    }

    /**
     * สร้างข้อความแจ้งสถานะแบน (สำหรับส่งให้ user)
     */
    public function buildBanReplyMessage(FortuneUserBan $ban): string
    {
        $lines = [
            '🚫 คุณมีพฤติกรรมไม่เหมาะสม',
            'แม่หมอขอไม่สนใจคนแบบนี้',
            '',
        ];

        if ($ban->isPermanent()) {
            $lines[] = 'สถานะ: 🔒 ติดแบนถาวร';
        } else {
            $lines[] = 'สถานะ: ⏳ ติดแบน — ' . $ban->remainingHumanReadable();
        }

        return implode("\n", $lines);
    }

    /**
     * ล้าง expired bans (เรียกจาก scheduled task)
     *
     * @return int จำนวน ban ที่ถูกลบ
     */
    public function cleanupExpired(): int
    {
        $expired = FortuneUserBan::expired()->get();
        $count = 0;

        foreach ($expired as $ban) {
            $this->clearCache($ban->platform, $ban->platform_user_id);
            $ban->delete();
            $count++;
        }

        if ($count > 0) {
            Log::info('🧹 FortuneBan: ล้าง ban ที่หมดอายุ', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Cache key สำหรับ user หนึ่งคน
     */
    protected function cacheKey(string $platform, string $platformUserId): string
    {
        return self::CACHE_PREFIX . $platform . ':' . $platformUserId;
    }

    /**
     * ล้าง cache ของ user คนนี้
     */
    public function clearCache(string $platform, string $platformUserId): void
    {
        Cache::forget($this->cacheKey($platform, $platformUserId));
    }
}
