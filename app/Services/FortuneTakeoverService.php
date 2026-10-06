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
     * 🤫 (2026-10-06) เวลาขั้นต่ำของเทคโอเวอร์ที่แอดมินสั่งเองโดยไม่ระบุนาที
     */
    public const ADMIN_TAKEOVER_DEFAULT_MINUTES = 30;

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

        // 🤫 (2026-10-06) สวิตช์ admin_handover_enabled คุมเฉพาะ "เทคโอเวอร์อัตโนมัติ" (ลูกค้าขอคุยกับคน)
        //    เดิม: ปิดสวิตช์ = แม้แอดมินกดเทคโอเวอร์เองบอทก็ยังคุยแทรก → ตอนนี้แอดมินสั่งเองนับเสมอ
        $autoEnabled = $this->settings()->isTakeoverEnabled();

        // Fast path: เช็ค Cache ก่อน (เฉพาะตอนสวิตช์เปิด — ปิดอยู่ต้องรู้เหตุผลของเทคโอเวอร์จาก DB)
        $cacheKey = $reading->getTakeoverCacheKey();
        if ($autoEnabled && Cache::has($cacheKey)) {
            return true;
        }

        // Fallback: เช็ค DB (เฉพาะกรณี cache หาย)
        // refresh เพื่อให้ได้ค่าล่าสุด (ข้าม attribute cache ในอ็อบเจ็กต์)
        $reading->refresh();

        $active = $reading->isAdminTakenOver()
            && ($autoEnabled || $reading->admin_takeover_reason !== FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST);

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
     * 🤫 (2026-10-06, เจ้าของสั่ง) เทคโอเวอร์อยู่ = **ไม่มีอะไร bypass แล้ว** — คืน false เสมอ
     *
     * เจ้าของ: "ถ้าเทคโอเวอร์คือแอดมินคุยแล้ว บอทต้องหยุดแทรกก่อน"
     *
     * ของเดิม (2026-05-17) ปล่อยผ่าน: กดปุ่ม / สถานะล็อก (จ่าย-กรอกข้อมูล-Celtic) / คำทำนายที่จ่ายแล้วรอส่ง /
     * ข้อความอยากซื้อ ("39", "99", "ดูดวง") → บอทพูดแทรกแอดมินกลางบทสนทนา
     * ของใหม่: ลูกค้าที่ถูกเทคโอเวอร์ → บอทเงียบสนิท (ข้อความขาเข้าถูกจด/park/เก็บสลิปที่ TakeoverIngress)
     * ของที่จ่ายเงินแล้วไม่หาย — พักไว้ใน conversation_state.takeover_deferred แล้วส่งครั้งเดียวตอนจบเทคโอเวอร์
     * (TakeoverResumeService) · คงเมธอดไว้ให้ 7 ด่านเดิมเรียกได้ (ด่านทั้งหมดจึงบล็อก)
     *
     * @param  bool  $isExplicitAction  (ไม่ใช้แล้ว — กดปุ่มก็ไม่ bypass)
     */
    public function shouldBypassTakeover(
        string $platform,
        string $platformUserId,
        string $messageText = '',
        bool $isExplicitAction = false,
    ): bool {
        return false;
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
        // 🤫 (2026-10-06) ใช้ตัวเช็คกลางตัวเดียวกับชั้นส่ง (TakeoverSendGuard) — ทุกบิลของลูกค้าคนนี้
        //    + เทคโอเวอร์ที่แอดมินสั่งเองนับเสมอแม้ปิดสวิตช์ admin_handover_enabled (สวิตช์คุมแค่แบบอัตโนมัติ)
        //    DB เป็นความจริง (cache:clear แล้วยังถูก) · เช็คพัง = false (fail open ธรรมเนียมเดิม)
        return \App\Services\Fortune\TakeoverSendGuard::userIsTakenOver($platform, $platformUserId);
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
     * @return int นาทีที่เทคโอเวอร์จริง
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
        // 🤫 (2026-10-06) แอดมินสั่งเอง (manual / พิมพ์ในกล่องแชท) ไม่ระบุนาที → อย่างน้อย 30 นาที
        //    migration 2026_04_27_130000 ตั้ง admin_handover_timeout = 1 (นาที) — /aistop เลยหมดอายุใน 1 นาที
        //    แล้วบอทกลับมาพูดแทรกแอดมิน · ค่าที่ตั้งไว้ยาวกว่า 30 ยังใช้ค่าที่ตั้ง (แอดมินตั้งใจ)
        //    เทคโอเวอร์อัตโนมัติ (ลูกค้าขอคุยกับคน) ใช้ค่าตั้งเดิม
        if (! $minutes) {
            $configured = $this->settings()->getTakeoverDefaultMinutes();
            $minutes = in_array($reason, [FortuneReading::TAKEOVER_REASON_MANUAL, FortuneReading::TAKEOVER_REASON_AUTO_REPLY], true)
                ? max(self::ADMIN_TAKEOVER_DEFAULT_MINUTES, $configured)
                : $configured;
        }
        $minutes = max(1, min(1440, $minutes)); // 1 นาที - 24 ชั่วโมง

        $until = now()->addMinutes($minutes);

        // 🤫 (2026-10-06) เทคโอเวอร์ซ้ำบนบิลที่ยังเทคโอเวอร์อยู่ = ช่วงเดิมต่อเนื่อง → คงเวลาเริ่มเดิม
        //    (ใช้คำนวณ "นาฬิกาเซสชันที่หยุดเดินระหว่างแอดมินคุย" ตอนจบ — เริ่มใหม่ทุกครั้ง = เลื่อนนาฬิกาน้อยไป)
        $startedAt = ($reading->isAdminTakenOver() && $reading->admin_takeover_started_at)
            ? $reading->admin_takeover_started_at
            : now();

        // บันทึกใน DB (transaction เพื่อให้ log + reading อัพเดตพร้อมกัน)
        DB::transaction(function () use ($reading, $reason, $adminId, $minutes, $messagePreview, $until, $startedAt) {
            $reading->update([
                'admin_takeover_until' => $until,
                'admin_takeover_by' => $adminId,
                'admin_takeover_reason' => $reason,
                'admin_takeover_started_at' => $startedAt,
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

        $this->forgetGuardMemo($reading);

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
     * @param  int|null  $adminId  user_id ของแอดมิน (null ถ้า auto-expire)
     * @param  bool  $fromCommand  มาจากคำสั่ง /ai หรือปุ่ม
     * @param  bool  $deliverDeferred  🤫 (2026-10-06) true = ส่งของที่จ่ายแล้วแต่ถูกพักไว้ระหว่างเทคโอเวอร์
     *                                 false = แอดมินบอก "จัดการเองแล้ว ไม่ต้องส่ง" → ล้างรายการพักทิ้ง
     */
    public function resume(
        FortuneReading $reading,
        ?int $adminId = null,
        bool $fromCommand = false,
        bool $deliverDeferred = true,
    ): void {
        $reading->refresh();

        // 🤫 (2026-10-06, bug-hunt L5) ด่านบอทเงียบนับ "ต่อลูกค้า" (ทุกบิลของคนนั้น) → คืนงานต้องปิดทุกบิลของคนนั้นด้วย
        //    เดิมปิดแค่บิลที่กด → API ตอบ "คืนงานแล้ว" แต่บอทยังเงียบเพราะบิลอื่นยังเทคโอเวอร์ค้าง
        //    และ deliver_deferred=false ถูกเมินเงียบ ๆ (ของที่พักรอบิลสุดท้ายจบ)
        $active = $this->customerTakeoverReadings($reading);

        if ($active->isEmpty()) {
            // ไม่มีเทคโอเวอร์ค้างแล้ว แต่อาจมีของที่พักค้าง (เช่น บิลอื่นของลูกค้าเพิ่งหมดเวลา)
            //    → ให้ตัวส่งของที่พักไว้ตัดสินเอง (มันเช็คซ้ำว่าลูกค้ายังถูกเทคโอเวอร์อยู่ไหม)
            \App\Services\Fortune\TakeoverResumeService::afterTakeoverEnded($reading, $deliverDeferred, $adminId);

            return;
        }

        // ช่วงเทคโอเวอร์ = ตั้งแต่บิลแรกที่เริ่ม จนถึงตอนนี้ (ใช้เลื่อนนาฬิกาเซสชันที่จ่ายแล้ว)
        //   บิลที่หมดเวลาไปแล้วแต่ cron ยังไม่กวาด = จบที่เวลาหมดของมัน (หลังจากนั้นบอทกลับมาทำงานแล้ว ห้ามนับ)
        $episodeStart = $active->pluck('admin_takeover_started_at')->filter()->min();
        $episodeEnd = $active
            ->map(fn (FortuneReading $r) => ($r->admin_takeover_until && $r->admin_takeover_until->lessThan(now()))
                ? $r->admin_takeover_until
                : now())
            ->max();

        DB::transaction(function () use ($active, $adminId, $fromCommand) {
            foreach ($active as $r) {
                $r->update([
                    'admin_takeover_until' => null,
                    // เก็บ admin_takeover_by + started_at ไว้เป็น audit trail
                ]);

                FortuneTakeoverLog::create([
                    'fortune_reading_id' => $r->id,
                    'user_id' => $adminId,
                    'action' => FortuneTakeoverLog::ACTION_RESUME,
                    'reason' => $fromCommand ? 'command' : 'manual',
                    'platform' => $r->platform,
                ]);
            }
        });

        // ล้าง cache ทั้งหมด
        foreach ($active as $r) {
            $this->clearCaches($r);
        }
        $this->forgetGuardMemo($reading);
        $reading->refresh();

        Log::info('✨ FortuneTakeover: AI กลับมาทำงาน', [
            'reading_id' => $reading->id,
            'ended_readings' => $active->pluck('id')->all(),
            'admin_id' => $adminId,
            'from_command' => $fromCommand,
            'deliver_deferred' => $deliverDeferred,
        ]);

        // 🤫 (2026-10-06) ส่งของที่จ่ายแล้วแต่ถูกพักไว้ระหว่างเทคโอเวอร์ (ตามลำดับ) — หรือล้างทิ้งถ้าแอดมินจัดการเองแล้ว
        //    + เลื่อนนาฬิกาเซสชันที่จ่ายแล้ว + ล้างคำถามที่ค้าง (แอดมินคุยไปแล้ว)
        \App\Services\Fortune\TakeoverResumeService::afterTakeoverEnded($reading, $deliverDeferred, $adminId, [
            'ended' => true,
            'episode_start' => $episodeStart,
            'episode_end' => $episodeEnd ?? now(),
        ]);
    }

    /**
     * 🤫 (2026-10-06, bug-hunt L10) แอดมินส่งข้อความไม่ออก และ request นั้นเป็นคน "เริ่ม" เทคโอเวอร์ → ถอยกลับ
     *
     * ไม่ใช่การคืนงานปกติ: ไม่ล้างคำถามที่ค้าง (แอดมินยังไม่ได้คุยอะไรเลย) — แค่ปิดเทคโอเวอร์ที่เพิ่งเปิด
     */
    public function revertAdminTakeover(FortuneReading $reading, ?int $adminId = null, ?int $takeoverLogId = null): void
    {
        $reading->refresh();
        if (empty($reading->admin_takeover_until)) {
            return;
        }

        $removedLog = false;
        DB::transaction(function () use ($reading, $adminId, $takeoverLogId, &$removedLog) {
            $reading->update(['admin_takeover_until' => null]);

            // เทคโอเวอร์ที่เปิดแล้วถอยภายใน request เดียวกัน = ไม่เคยมีผลจริง (ข้อความไม่ถึงลูกค้า)
            //   → ลบบันทึก "เริ่มเทคโอเวอร์" ของ request นี้ทิ้ง ไม่เขียน "คืนงาน" เพิ่ม
            //   ไม่งั้นคิว "ลูกค้าขอคุยกับคน" (CustomerRequestQueue) นับว่าแอดมินรับเรื่องแล้ว ทั้งที่ลูกค้าไม่ได้อะไรเลย
            if ($takeoverLogId) {
                $removedLog = FortuneTakeoverLog::query()
                    ->whereKey($takeoverLogId)
                    ->where('fortune_reading_id', $reading->id)
                    ->where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
                    ->delete() > 0;
            }

            if (! $removedLog) {
                FortuneTakeoverLog::create([
                    'fortune_reading_id' => $reading->id,
                    'user_id' => $adminId,
                    'action' => FortuneTakeoverLog::ACTION_RESUME,
                    'reason' => 'reverted_send_failed',
                    'platform' => $reading->platform,
                ]);
            }
        });

        $this->clearCaches($reading);
        $this->forgetGuardMemo($reading);

        Log::info('↩️ FortuneTakeover: ส่งข้อความแอดมินไม่ออก — ถอยเทคโอเวอร์ที่เพิ่งเปิด', [
            'reading_id' => $reading->id,
            'admin_id' => $adminId,
            'takeover_log_removed' => $removedLog,
        ]);

        \App\Services\Fortune\TakeoverResumeService::afterTakeoverEnded($reading, true, $adminId, [
            'ended' => true,
            'clear_pending' => false,
        ]);
    }

    /**
     * บิลของลูกค้าเจ้าของบิลนี้ที่ยังมีเทคโอเวอร์ค้าง (ยังไม่หมดเวลา หรือหมดแล้วแต่ยังไม่ถูกปิด)
     *
     * @return \Illuminate\Support\Collection<int, FortuneReading>
     */
    protected function customerTakeoverReadings(FortuneReading $reading): \Illuminate\Support\Collection
    {
        $userId = \App\Services\Fortune\FortuneRecipient::userIdOf($reading);
        if ($userId === '') {
            return empty($reading->admin_takeover_until) ? collect() : collect([$reading]);
        }

        return FortuneReading::query()
            ->where(function ($q) use ($userId) {
                $q->where('platform_user_id', $userId)->orWhere('facebook_user_id', $userId);
            })
            ->whereNotNull('admin_takeover_until')
            ->get();
    }

    /**
     * ลืมผลเช็คเทคโอเวอร์ที่ memo ไว้ของลูกค้าเจ้าของบิลนี้ (สถานะเพิ่งเปลี่ยน)
     */
    protected function forgetGuardMemo(FortuneReading $reading): void
    {
        try {
            \App\Services\Fortune\TakeoverSendGuard::forget(\App\Services\Fortune\FortuneRecipient::userIdOf($reading));
        } catch (\Throwable $e) {
            // memo เป็นแค่ตัวช่วย
        }
    }

    /**
     * ต่อเวลาเทคโอเวอร์
     *
     * @param  int  $minutes  เวลาที่ต้องการเพิ่ม
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
        $this->forgetGuardMemo($reading);

        Log::info('⏱ FortuneTakeover: ต่อเวลา', [
            'reading_id' => $reading->id,
            'minutes_added' => $minutes,
            'until' => $until->toIso8601String(),
        ]);

        return $minutes;
    }

    /**
     * 🤫 (2026-10-06) แอดมินพิมพ์หาลูกค้า = แอดมินคุยอยู่ → ต้องอยู่ในเทคโอเวอร์ (บอทหยุดแทรก)
     *
     * - ยังไม่ได้เทคโอเวอร์ → เริ่มเทคโอเวอร์ (force แม้ปิดสวิตช์ · max(30, ค่าที่ตั้ง) นาที)
     * - เทคโอเวอร์อยู่แต่เหลือไม่ถึง max(30, ค่าที่ตั้ง) → ต่อให้เหลืออย่างน้อยเท่านั้น
     *   (แอดมินคุยต่อเนื่อง บอทต้องไม่โผล่กลับมากลางบทสนทนา)
     *
     * @return int นาทีที่เริ่ม/ต่อ (0 = ไม่ต้องทำอะไร)
     */
    public function ensureAdminTakeover(
        FortuneReading $reading,
        ?int $adminId = null,
        ?string $messagePreview = null,
        string $reason = FortuneReading::TAKEOVER_REASON_MANUAL,
    ): int {
        return $this->ensureAdminTakeoverDetailed($reading, $adminId, $messagePreview, $reason)['minutes'];
    }

    /**
     * เหมือน ensureAdminTakeover() แต่บอกด้วยว่า "เริ่มใหม่" หรือ "ต่อเวลา" (ใช้ถอยกลับตอนส่งไม่ออก — L10)
     *
     * @return array{minutes: int, started: bool, takeover_log_id: ?int}
     */
    public function ensureAdminTakeoverDetailed(
        FortuneReading $reading,
        ?int $adminId = null,
        ?string $messagePreview = null,
        string $reason = FortuneReading::TAKEOVER_REASON_MANUAL,
    ): array {
        $reading->refresh();

        // ลูกค้าคนนี้ถูกเทคโอเวอร์อยู่แล้วด้วยบิลไหนก็ได้ = "ต่อ" ไม่ใช่ "เริ่ม"
        $userId = \App\Services\Fortune\FortuneRecipient::userIdOf($reading);
        $customerWasTakenOver = $userId !== ''
            && \App\Services\Fortune\TakeoverSendGuard::userIsTakenOver($reading->platform ?: 'facebook', $userId, true);

        if (! $reading->isAdminTakenOver()) {
            $minutes = $this->takeover($reading, $reason, $adminId, null, $messagePreview, true);
            $started = ! $customerWasTakenOver && $minutes > 0;

            return [
                'minutes' => $minutes,
                'started' => $started,
                // บันทึก "เริ่มเทคโอเวอร์" ของ request นี้ — ถอยกลับ (ส่งไม่ออก) จะลบแถวนี้ทิ้ง
                'takeover_log_id' => $started
                    ? FortuneTakeoverLog::query()
                        ->where('fortune_reading_id', $reading->id)
                        ->where('action', FortuneTakeoverLog::ACTION_TAKEOVER)
                        ->orderByDesc('id')
                        ->value('id')
                    : null,
            ];
        }

        $minimumSeconds = $this->adminTakeoverMinutes() * 60;
        $remaining = $reading->takeoverRemainingSeconds();
        if ($remaining >= $minimumSeconds) {
            return ['minutes' => 0, 'started' => false, 'takeover_log_id' => null];
        }

        return [
            'minutes' => $this->extend($reading, (int) ceil(($minimumSeconds - $remaining) / 60), $adminId),
            'started' => false,
            'takeover_log_id' => null,
        ];
    }

    /**
     * นาทีของเทคโอเวอร์ที่แอดมินสั่งเอง/พิมพ์เอง = max(30, ค่าที่ตั้ง) — prod ตั้ง admin_handover_timeout = 1
     */
    public function adminTakeoverMinutes(): int
    {
        return max(self::ADMIN_TAKEOVER_DEFAULT_MINUTES, (int) $this->settings()->getTakeoverDefaultMinutes());
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
     * 🔒 (2026-05-03) H2 — Tightened matching เพื่อกัน false positive
     *    เดิม: substring match → "ขอคุยกับคนรู้ใจ" → จับ "ขอคุยกับคน" → handoff ผิด
     *    ใหม่: ต้องเป็น exact match หรือ keyword เป็น "core" ของข้อความ
     *      (หลังตัด politeness suffixes แล้ว)
     *
     *    Logic:
     *    1. Strip politeness suffixes (ค่ะ, ครับ, นะ, ฯลฯ) + Lao equivalents
     *    2. exact match → handoff
     *    3. starts_with keyword + space/punctuation → handoff
     *    4. else → no match (กัน substring กลางข้อความ)
     */
    public function detectCustomerHandoffRequest(string $message): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        $lower = mb_strtolower($message);

        // 🧹 Strip politeness suffixes (TH+LAO+EN) เพื่อจับ "core" ของข้อความ
        //    เช่น "ขอคุยกับคนค่ะ" → "ขอคุยกับคน" (จะ exact match)
        // 🇱🇦 Lao particles: ເດີ, ແດ່ (please), ຫລາຍ (very/much), ດ້ວຍ (with/too), ຄ່ະ (Lao "ค่ะ")
        $normalized = preg_replace(
            '/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|นะคะ|นะครับ|หน่อย|ด้วย|ที|สิ|เลย|อะ|please|ເດີ|ແດ່|ຫລາຍ|ດ້ວຍ|ຄ່ະ|ເນາະ)\s*$/u',
            '',
            $lower
        );
        $normalized = trim($normalized);

        $keywords = $this->settings()->getCustomerHandoffKeywords();

        foreach ($keywords as $keyword) {
            $k = mb_strtolower(trim($keyword));
            if ($k === '') {
                continue;
            }

            // 1. Exact match (ทั้งหลัง trim และหลัง strip politeness)
            if ($lower === $k || $normalized === $k) {
                return true;
            }

            // 2. Starts-with + word break (กัน substring กลางข้อความ)
            //    "ขอแม่หมอ" + space/punct → match
            //    "ขอแม่หมอดูดวง" → ไม่ match (ไม่มี boundary หลัง keyword)
            $pattern = '/^'.preg_quote($k, '/').'(\s|[!?,.ๆฯ]|$)/u';
            if (preg_match($pattern, $lower) || preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งให้ AI กลับมาหรือไม่ (แอดมินพิมพ์)
     *
     * Match แบบ exact (เริ่มต้นของข้อความ, trim แล้ว)
     *
     * 🎯 (2026-05-17) รองรับทั้ง 2 คำสั่ง:
     *   - ai_resume_command setting (default: /ai)
     *   - /aistart (hardcoded alias — สำหรับ admin จำคู่กับ /aistop ง่ายขึ้น)
     */
    public function detectAdminResumeCommand(string $message): bool
    {
        $trimmed = trim($message);
        if ($trimmed === '') {
            return false;
        }

        $commands = [
            $this->settings()->getAiResumeCommand(),
            '/aistart', // hardcoded alias — pair กับ /aistop
        ];

        foreach (array_unique($commands) as $command) {
            if ($command === '') {
                continue;
            }
            // Match ทั้งคำสั่งเดี่ยวๆ หรือขึ้นต้นด้วยคำสั่ง + space
            if (strcasecmp($trimmed, $command) === 0
                || stripos($trimmed, $command.' ') === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นคำสั่งให้บอทหยุดทำงานหรือไม่ (แอดมินพิมพ์ /aistop)
     *
     * 🎯 (2026-05-17) เพิ่มเพื่อแทนที่ auto-takeover เดิม (FB echo auto-pause)
     * Admin พิมพ์ "/aistop" ใน Page Inbox → ระบบ takeover ทันที
     */
    public function detectAdminPauseCommand(string $message): bool
    {
        $trimmed = trim($message);
        if ($trimmed === '') {
            return false;
        }

        $command = $this->settings()->getAiPauseCommand();

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

        $count = 0;
        foreach ($expired as $reading) {
            if ($this->expireOne($reading)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * ปิดเทคโอเวอร์ที่หมดเวลาแล้วของบิลเดียว + ส่งของที่พักไว้ (ใช้ทั้ง cron และ "ทักมาหลังหมดเวลา")
     *
     * 🔒 อัปเดตแบบมีเงื่อนไข (ยังหมดเวลาอยู่จริง) — cron กับ webhook ชนกันได้ ห้ามปิดซ้ำ/ส่งของซ้ำ
     *    และห้ามปิดเทคโอเวอร์ที่แอดมินเพิ่งต่อเวลาเข้ามาระหว่างนั้น
     *
     * @return bool true = ปิดได้ (ตัวนี้เป็นคนปิด)
     */
    public function expireOne(FortuneReading $reading, bool $inbound = false): bool
    {
        // ช่วงเทคโอเวอร์จริง = เวลาเริ่ม → เวลาหมด (ไม่ใช่ตอน cron มากวาด — ระหว่างนั้นบอทกลับมาทำงานแล้ว)
        $episodeStart = $reading->admin_takeover_started_at;
        $episodeEnd = $reading->admin_takeover_until;

        $closed = DB::transaction(function () use ($reading) {
            $affected = FortuneReading::query()
                ->whereKey($reading->id)
                ->whereNotNull('admin_takeover_until')
                ->where('admin_takeover_until', '<=', now())
                ->update(['admin_takeover_until' => null]);

            if ($affected === 0) {
                return false;
            }

            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'action' => FortuneTakeoverLog::ACTION_AUTO_EXPIRE,
                'platform' => $reading->platform,
            ]);

            return true;
        });

        if (! $closed) {
            return false;
        }

        $this->clearCaches($reading);
        $this->forgetGuardMemo($reading);
        $reading->refresh();

        // 🤫 (2026-10-06) หมดเวลา = จบเทคโอเวอร์ → ส่งของที่จ่ายแล้วแต่ถูกพักไว้ + เลื่อนนาฬิกาเซสชัน + ล้างคำถามค้าง
        \App\Services\Fortune\TakeoverResumeService::afterTakeoverEnded($reading, true, null, [
            'ended' => true,
            'episode_start' => $episodeStart,
            'episode_end' => ($episodeEnd && $episodeEnd->lessThan(now())) ? $episodeEnd : now(),
            'inbound' => $inbound,
        ]);

        return true;
    }
}
