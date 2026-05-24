<?php

namespace App\Services;

use App\Models\FortuneBanner;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Banner Service
 *
 * จัดการการเลือก + ส่งแบนเนอร์ภาพให้ลูกค้าผ่าน Facebook/LINE
 *
 * ใช้งาน:
 *   $banner = $bannerService->pickForChannel('reaction');  // หรือ 'comment', 'welcome'
 *   if ($banner) {
 *       $facebookService->sendImage($userId, $banner->image_url);
 *       $banner->recordSend();
 *   }
 */
class FortuneBannerService
{
    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจสอบว่าแบนเนอร์ถูกเปิดใช้งานสำหรับ channel นี้หรือไม่
     *
     * @param  string  $channel  'reaction' | 'comment' | 'welcome'
     */
    public function isEnabledFor(string $channel): bool
    {
        // Master toggle — ปิดทั้งระบบ
        if (! ($this->settings->enable_dm_banner ?? false)) {
            Log::debug('FortuneBanner: enable_dm_banner = false', ['channel' => $channel]);
            return false;
        }

        $perChannel = match ($channel) {
            'reaction' => (bool) ($this->settings->banner_send_on_reaction ?? true),
            'comment' => (bool) ($this->settings->banner_send_on_comment ?? true),
            'welcome' => (bool) ($this->settings->banner_send_on_welcome ?? true),
            default => false,
        };

        if (! $perChannel) {
            Log::debug('FortuneBanner: per-channel toggle off', ['channel' => $channel]);
        }

        return $perChannel;
    }

    /**
     * เลือกแบนเนอร์ที่จะส่งให้ลูกค้า ตาม strategy
     *
     * @param  string  $channel  ใช้ตัดสินใจว่าควรส่งหรือไม่
     * @return FortuneBanner|null  null ถ้าปิด หรือไม่มีแบนเนอร์ active
     */
    public function pickForChannel(string $channel): ?FortuneBanner
    {
        if (! $this->isEnabledFor($channel)) {
            return null;
        }

        $strategy = $this->settings->banner_pick_strategy ?? 'rotation';

        try {
            $banner = FortuneBanner::pickByStrategy($strategy);
            if (! $banner) {
                Log::warning('FortuneBanner: ไม่มี active banner ในตาราง — ตรวจ /admin/fortune/banners', [
                    'channel' => $channel,
                    'strategy' => $strategy,
                ]);
            }
            return $banner;
        } catch (\Throwable $e) {
            Log::warning('FortuneBannerService: pick failed', [
                'channel' => $channel,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * ส่งแบนเนอร์ครั้งเดียวต่อ user/channel — รีเซ็ต cooldown ตอนเที่ยงคืน
     *
     * 🆕 (2026-05-06) เปลี่ยน semantics: cooldown หมดอายุตอน end-of-day (Asia/Bangkok)
     *   user spec: "ต้องส่งอย่างน้อยวันละ 1 ครั้ง ต่อ 1 คน ที่ทัก"
     *   เดิม: 24-hour absolute — ถ้าส่งเที่ยงวานนี้ จะส่งซ้ำได้แค่เที่ยงวันนี้
     *   ใหม่: calendar day — ส่ง 23:59 วานนี้ + ทักครั้งแรก 00:01 วันนี้ = ได้ banner ใหม่
     *
     * @param  string  $userId  Facebook/LINE user ID
     * @param  callable  $sendFn  function($imageUrl): bool
     * @param  string  $channel  'welcome' | 'reaction' | 'comment'
     * @param  int  $cooldownHours  legacy param (ignored — ใช้ calendar day แทน)
     * @return bool
     */
    public function sendBannerOnce(string $userId, callable $sendFn, string $channel, int $cooldownHours = 24, ?string $platform = null): bool
    {
        // 📅 cache key รวมวันที่ — auto-rotate ทุกเที่ยงคืน
        //   timezone = Asia/Bangkok (config('app.timezone') ก็ได้)
        $today = now()->format('Y-m-d');
        $cacheKey = "fortune_banner_sent:{$channel}:{$userId}:{$today}";

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            // 🔍 (2026-05-07) ใช้ info แทน debug — production log level ส่วนใหญ่ปิด debug
            Log::info('FortuneBanner: ส่งให้ user คนนี้ไปแล้ววันนี้ — skip', [
                'user_id' => $userId,
                'channel' => $channel,
                'date' => $today,
                'cache_key' => $cacheKey,
            ]);
            return false;
        }

        // 👤 (2026-05-14) Skip ลูกค้าเก่า — banner เฉพาะ first-ever interaction
        //   user spec: "ส่งแค่ลูกค้าใหม่จริงๆ" — ลูกค้าเก่าทักเข้ามาแล้วได้รูปทุกวัน annoying
        if ($platform && $this->isReturningCustomer($platform, $userId)) {
            Log::info('FortuneBanner: skip — returning customer (เก่า)', [
                'platform' => $platform,
                'user_id' => $userId,
                'channel' => $channel,
            ]);

            // Mark daily cache เพื่อกัน check ซ้ำในวันเดียวกัน
            $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now(), absolute: true));
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, $secondsUntilMidnight);

            return false;
        }

        // 🔍 (2026-05-07) Log entry ที่บอกว่าจะลองส่ง — debug ทุกขั้น
        Log::info('FortuneBanner: เข้า sendBannerOnce — กำลังลองส่ง', [
            'user_id' => $userId,
            'channel' => $channel,
            'date' => $today,
            'platform' => $platform,
            'is_new_customer' => $platform !== null,
        ]);

        $sent = $this->sendBannerThenWait($sendFn, $channel);

        if ($sent) {
            // cache ถึงเที่ยงคืน (end of day) — ใช้ remaining seconds ไม่ใช่ 24hr absolute
            $secondsUntilMidnight = max(60, now()->endOfDay()->diffInSeconds(now(), absolute: true));
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, $secondsUntilMidnight);
        }

        return $sent;
    }

    /**
     * 👤 (2026-05-14) ตรวจว่า user เป็น "ลูกค้าเก่า" หรือไม่
     *
     * Returns true ถ้า user เคยมี interaction ในระบบ (ใน 3 sources):
     *   1. LineBotConversation — มี chat history
     *   2. FortuneCustomerPersona — มี persona record
     *   3. FortuneReading — มี reading ใดๆ
     *
     * ใช้ตัดสินใจว่าจะส่ง banner หรือไม่ — ส่งเฉพาะลูกค้าใหม่จริงๆ
     *
     * Cache: ไม่ cache เพราะ result อาจ flip ได้ตามเวลา (user เพิ่งทักครั้งแรก)
     *   sendBannerOnce มี daily cache อยู่แล้ว — ป้องกัน DB hit ซ้ำต่อ user/day
     */
    public function isReturningCustomer(string $platform, string $userId): bool
    {
        try {
            // 1️⃣ Check LineBotConversation — primary source ของ chat history
            $hasConversation = \App\Models\LineBotConversation::where('platform', $platform)
                ->where('user_id', $userId)
                ->exists();
            if ($hasConversation) {
                return true;
            }

            // 2️⃣ Check FortuneCustomerPersona — มี persona record = เคยคุย
            if (class_exists(\App\Models\FortuneCustomerPersona::class)) {
                $hasPersona = \App\Models\FortuneCustomerPersona::where('platform', $platform)
                    ->where('platform_user_id', $userId)
                    ->exists();
                if ($hasPersona) {
                    return true;
                }
            }

            // 3️⃣ Check FortuneReading — มี reading ใดๆ = ลูกค้าเก่า
            $platformField = $platform === 'facebook' ? 'facebook_user_id' : 'line_user_id';
            $hasReading = \App\Models\FortuneReading::where($platformField, $userId)->exists();

            return $hasReading;
        } catch (\Throwable $e) {
            // ถ้า check ล้ม → fail-safe = treat as NEW customer (ส่ง banner)
            //   เพราะ user spec ต้องการ banner สำหรับใหม่ — false-positive (เก่าได้ banner) ดีกว่า false-negative (ใหม่ไม่ได้ banner)
            Log::warning('FortuneBanner: isReturningCustomer check failed (fail-safe = NEW)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ส่งแบนเนอร์พร้อมหน่วงเวลาให้ image มาก่อน text
     *
     * @param  callable  $sendFn  function($imageUrl): bool — function ที่ส่งภาพจริง (FB/LINE)
     * @param  string  $channel  'reaction' | 'comment' | 'welcome'
     * @param  string|null  $platform  'facebook'|'line' — ถ้าระบุ → check isReturningCustomer skip ลูกค้าเก่า
     * @param  string|null  $userId    user ID สำหรับ check
     * @return bool true ถ้าส่งภาพสำเร็จ (เพื่อ caller ตัดสินใจว่ารอ delay หรือไม่)
     */
    public function sendBannerThenWait(callable $sendFn, string $channel, ?string $platform = null, ?string $userId = null): bool
    {
        // 👤 (2026-05-14) Skip ลูกค้าเก่า ถ้าระบุ platform+userId
        //   user spec: "ส่งแค่ลูกค้าใหม่จริงๆ" — backward compat: ไม่ระบุ = ไม่ check
        if ($platform && $userId && $this->isReturningCustomer($platform, $userId)) {
            Log::info('FortuneBanner: skip — returning customer (sendBannerThenWait)', [
                'platform' => $platform,
                'user_id' => $userId,
                'channel' => $channel,
            ]);
            return false;
        }

        $banner = $this->pickForChannel($channel);

        if (! $banner) {
            return false;
        }

        try {
            $sent = (bool) $sendFn($banner->image_url);

            if ($sent) {
                $banner->recordSend();

                // 🐌 (2026-05-25) คืน usleep 800ms — แก้ race condition FB Reels
                //   ปัญหาเดิม (commit 2026-05-06 ลบออก): comment_id ของ Reels ใช้ใน Private Reply
                //   ได้ครั้งเดียว — ส่งภาพแล้วส่ง text ตามภายใน 1s → FB block (#10900 Activity already replied)
                //   ส่งผลให้ ~50% ของ comment engagement ลูกค้าได้ภาพแต่ไม่ได้ Quick Replies/CTA
                //   อยู่ใน queue job แล้ว (tpix-default) ไม่กระทบ request thread → block ได้ปลอดภัย
                usleep(800000); // 800ms ระหว่างภาพ + text DM

                Log::info('🖼️ Fortune banner sent', [
                    'banner_id' => $banner->id,
                    'banner_name' => $banner->name,
                    'channel' => $channel,
                    'send_count' => $banner->send_count + 1,
                ]);
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::warning('FortuneBannerService: send failed', [
                'banner_id' => $banner->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
