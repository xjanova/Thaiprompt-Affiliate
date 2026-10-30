<?php

namespace App\Services;

use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserBan;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * FortuneBanService
 *
 * แบนผู้ก่อกวน: Facebook ต้องบล็อกจากเพจจริง พร้อมเก็บผลและลองใหม่เมื่อ API ล้มเหลว
 * แพลตฟอร์มอื่นใช้การแบนระดับบอทตามเดิม
 *
 * Flow:
 * 1. Facebook แบนแล้วเงียบ และบล็อกจากเพจผ่าน Graph API
 * 2. ช่องทางอื่นแจ้งเตือนได้ไม่เกินครั้งละ 1 ชั่วโมง
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
            array_merge([
                'display_name' => $displayName,
                'reason' => $reason,
                'banned_until' => $bannedUntil,
                'banned_by' => $adminId,
                // รีเซ็ต counter เมื่อแบนใหม่
                'last_notified_at' => null,
                'notify_count' => 0,
                'attempt_count' => 0,
            ], $this->hasPageBlockColumns() ? [
                'page_block_status' => $platform === 'facebook' ? 'pending' : null,
                'page_block_error' => null,
            ] : [])
        );

        $this->clearCache($platform, $platformUserId);

        if ($platform === 'facebook') {
            if (FortunePageContext::currentId() && ! $ban->fortune_page_id) {
                $ban->fortune_page_id = FortunePageContext::currentId();
                $ban->save();
            }
            $this->syncPageBlock($ban);
        }

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
        // ต้องปลดบนเพจสำเร็จก่อนลบหลักฐาน มิฉะนั้นจะไม่มีแถวให้ลองใหม่
        // แบนชั่วคราวรุ่นเก่าที่หมดอายุและไม่เคยส่ง API เป็นเพียงแบนบอท
        $needsPageUnblock = $platform === 'facebook'
            && ($ban->isActive() || $ban->page_block_status !== null || $ban->page_block_attempted_at !== null);
        if ($needsPageUnblock && ! $this->applyPageBlock($ban, false)) {
            return false;
        }
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

        // คนที่ถูกแบนจากเพจไม่ควรได้รับข้อความเตือนจากบอทอีก
        if ($ban->platform === 'facebook') {
            return false;
        }

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
            $lines[] = 'สถานะ: ⏳ ติดแบน — '.$ban->remainingHumanReadable();
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
            if ($this->unban($ban->platform, $ban->platform_user_id)) {
                $count++;
            }
        }

        if ($count > 0) {
            Log::info('🧹 FortuneBan: ล้าง ban ที่หมดอายุ', ['count' => $count]);
        }

        return $count;
    }

    /** บล็อกบนเพจจริง โดยใช้สาขาของผู้ถูกแบนและเก็บผลจาก Facebook */
    public function syncPageBlock(FortuneUserBan $ban): bool
    {
        if ($ban->platform !== 'facebook') {
            return true;
        }
        if (! $ban->isActive() || $ban->page_block_status === 'unblock_failed') {
            return false;
        }

        return $this->applyPageBlock($ban, true);
    }

    protected function applyPageBlock(FortuneUserBan $ban, bool $block): bool
    {
        $ok = false;
        $error = null;
        try {
            // แถวเก่าหรือแบนจากแอดมินไม่มี context: หาสาขาจากบทสนทนาก่อน
            if (! $ban->fortune_page_id) {
                $pageId = FortuneReading::where('platform', 'facebook')
                    ->where('facebook_user_id', $ban->platform_user_id)
                    ->whereNotNull('fortune_page_id')->latest('id')->value('fortune_page_id');
                if ($pageId) {
                    $ban->fortune_page_id = $pageId;
                    $ban->save();
                }
            }
            $facebook = $this->facebookForBan($ban);
            $ok = $block
                ? $facebook->blockPageUser($ban->platform_user_id)
                : $facebook->unblockPageUser($ban->platform_user_id);
            if (! $ok) {
                $error = $facebook->lastFetchError ?? 'Facebook ไม่ยืนยันผล';
            }
        } catch (\Throwable $e) {
            $error = \App\Support\SafeLog::exceptionMessage($e);
        }

        $state = [
            'page_block_status' => $ok ? ($block ? 'blocked' : 'unblocked') : ($block ? 'failed' : 'unblock_failed'),
            'page_block_error' => $error ? mb_substr($error, 0, 1000) : null,
            'page_block_attempted_at' => now(),
        ];
        $ban->forceFill($state);
        if ($this->hasPageBlockColumns()) {
            $ban->save();
            if ($ban->isPageBlockLimited()) {
                $this->holdPageBlockRetries($ban);
            }
        } else {
            // webhook ยังเข้าระหว่างดีพลอยได้: บล็อกจริงและเก็บแบนเดิมก่อน รอ migration แล้วค่อย sync ผล
            $ban->syncOriginalAttributes(array_keys($state));
        }

        if (! $ok) {
            Log::warning('FortuneBan: จัดการบล็อกบนเพจไม่สำเร็จ รอลองใหม่', [
                'ban_id' => $ban->id, 'block' => $block, 'error' => $error,
            ]);
        }

        return $ok;
    }

    /**
     * Facebook จำกัดทั้งเพจ: พักแถวที่รอของเพจเดียวกันพร้อมกัน
     *
     * เก็บใน DB ไม่ใช่ cache เพราะทุก deploy ล้าง cache แล้วจะกลับไปยิงซ้ำ
     * รอบถัดไปจึงมีแค่แถวแรกที่ลองจริง ที่เหลือรอผลแถวนั้น
     */
    protected function holdPageBlockRetries(FortuneUserBan $ban): void
    {
        FortuneUserBan::forPlatform('facebook')
            ->where('fortune_page_id', $ban->fortune_page_id)
            ->where(fn ($q) => $q->whereNull('page_block_status')
                ->orWhereIn('page_block_status', ['pending', 'failed']))
            ->update([
                'page_block_error' => $ban->page_block_error,
                'page_block_attempted_at' => now(),
            ]);

        Log::warning('FortuneBan: Facebook จำกัดการบล็อกของเพจ พักการลองใหม่', [
            'fortune_page_id' => $ban->fortune_page_id,
            'hours' => FortuneUserBan::PAGE_LIMITED_RETRY_HOURS,
        ]);
    }

    protected function hasPageBlockColumns(): bool
    {
        return Schema::hasColumns('fortune_user_bans', ['page_block_status', 'page_block_error', 'page_block_attempted_at']);
    }

    /** สร้างบริการใหม่ตามสาขาของแถว ห้ามใช้ token ที่ค้างจากผู้รับคนก่อน */
    protected function facebookForBan(FortuneUserBan $ban): FacebookWebhookService
    {
        $page = $ban->fortune_page_id ? FortunePage::findOrFail($ban->fortune_page_id) : null;
        if ($page && ($page->platform !== 'facebook' || empty($page->external_page_id) || empty($page->page_access_token))) {
            throw new \RuntimeException('สาขาไม่มีข้อมูลเพจ Facebook ครบถ้วน กรุณาตรวจ Page ID และ token');
        }

        return FortunePageContext::run($page, fn () => new FacebookWebhookService(FortuneTellingSetting::getSettings()));
    }

    /**
     * Cache key สำหรับ user หนึ่งคน
     */
    protected function cacheKey(string $platform, string $platformUserId): string
    {
        return self::CACHE_PREFIX.$platform.':'.$platformUserId;
    }

    /**
     * ล้าง cache ของ user คนนี้
     */
    public function clearCache(string $platform, string $platformUserId): void
    {
        Cache::forget($this->cacheKey($platform, $platformUserId));
    }
}
