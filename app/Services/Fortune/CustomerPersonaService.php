<?php

namespace App\Services\Fortune;

use App\Jobs\ExtractCustomerPersonaJob;
use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 👤 (2026-05-14) Customer Persona Service — บริการจัดการ persona ลูกค้า
 *
 * Responsibilities:
 *  1. getOrCreate() — load/create persona record
 *  2. inject() — สร้าง context block สำหรับ AI prompt
 *  3. dispatchExtraction() — เรียก AI วิเคราะห์ข้อความ user (async)
 *  4. toObsidianMarkdown() — export สำหรับ ObsidianX
 *
 * Cache layer:
 *  - In-request cache (per-request) → กัน N+1 query
 *  - Daily cache (24hr) สำหรับ inject — เร็วกว่า DB hit ทุก message
 *
 * Cost throttle:
 *  - ไม่ dispatch extraction ถ้าเพิ่งสำเร็จไปไม่ถึง 30 นาที (cache flag)
 *  - ไม่ dispatch ถ้าข้อความสั้นเกินไป (< 20 chars — ไม่มีอะไรให้วิเคราะห์)
 */
class CustomerPersonaService
{
    /** Cache TTL สำหรับ inject context (วินาที) */
    private const INJECT_CACHE_TTL = 86400; // 24 ชม

    /** Cache TTL สำหรับ extraction throttle (วินาที) */
    private const EXTRACTION_THROTTLE_TTL = 1800; // 30 นาที (default)
    private const EXTRACTION_THROTTLE_TTL_CRITICAL = 600; // 10 นาที (เคสวิกฤต)

    /** ข้อความที่สั้นกว่านี้จะไม่ extract (ไม่มีอะไรให้วิเคราะห์) */
    private const MIN_MESSAGE_LENGTH = 20;

    /**
     * 🔍 ดึง persona (cached 24hr) — ใช้ตอน inject ลง AI prompt
     */
    public function getCached(string $platform, string $userId): ?FortuneCustomerPersona
    {
        $cacheKey = $this->injectCacheKey($platform, $userId);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            // cache hit (string "MISSING" หรือ serialized persona)
            if ($cached === 'MISSING') {
                return null;
            }

            return $cached;
        }

        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        Cache::put($cacheKey, $persona ?? 'MISSING', self::INJECT_CACHE_TTL);

        return $persona;
    }

    /**
     * 🎯 สร้าง context block สำหรับ inject ใน AI system message
     *
     * @return string  block พร้อม inject (อาจเป็น empty string ถ้าไม่มี persona ที่มีข้อมูล)
     */
    public function buildInjectBlock(string $platform, string $userId): string
    {
        // 📅 (2026-05-25) Timeline header — รู้จักลูกค้า + รู้ช่วงเวลา
        //   ห้ามทำเหมือนคนใหม่ ถ้าเคยดูดวงมาก่อน
        $timeline = $this->buildTimelineHeader($platform, $userId);

        $persona = $this->getCached($platform, $userId);
        if (! $persona) {
            // ลูกค้าใหม่จริงๆ (ไม่มี persona) → return timeline only (อาจมี past readings)
            return $timeline;
        }

        return $timeline.$persona->toAiContextBlock();
    }

    /**
     * 📅 (2026-05-25) Timeline header — บอก AI ว่ารู้จักลูกค้าคนนี้แค่ไหน
     *
     * Format:
     *   - 🆕 ลูกค้าใหม่ (ครั้งแรก) → ถ้าไม่เคยดูดวง (paid)
     *   - 👤 ลูกค้าเก่า — ดูดวงเป็นครั้งที่ N / ครั้งล่าสุด X วันก่อน เรื่อง "..."
     *
     * Cached 1 ชม. — invalidate ตอน paid reading ใหม่ (ไม่ critical หากช้า)
     *
     * @param  string  $platform  'facebook' / 'line'
     * @param  string  $userId    FB PSID / LINE userId
     * @return string  ว่างเปล่าถ้าไม่ใช่ FB user (line ใช้ user_id table — ต้อง resolve ก่อน)
     */
    public function buildTimelineHeader(string $platform, string $userId): string
    {
        $cacheKey = "fortune:persona_timeline:{$platform}:{$userId}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 'EMPTY' ? '' : $cached;
        }

        try {
            // ✅ ดึงเฉพาะ paid + completed (Deep / Celtic) — ไม่นับ free / unpaid
            //   line ใช้ผ่าน user_id resolve เพิ่มทีหลัง — รอบนี้ FB เป็นหลัก
            $query = FortuneReading::where('is_paid', true)
                ->whereIn('conversation_status', ['completed', 'celtic_grand_finale', 'celtic_generating']);

            if ($platform === 'facebook') {
                $query->where('facebook_user_id', $userId);
            } else {
                // LINE: ผ่าน user_id (line_user_id ไม่มีใน fortune_readings ตาม code comment)
                //   skip ชั่วคราว — return empty (จะปรับให้ resolve ภายหลัง)
                Cache::put($cacheKey, 'EMPTY', 3600);
                return '';
            }

            $count = $query->count();
            $last = (clone $query)->orderByDesc('paid_at')->first();

            if ($count === 0 || ! $last) {
                $header = "🆕 [CUSTOMER_TIMELINE] ลูกค้าใหม่ครั้งแรก — ยังไม่เคยดูดวงแบบจ่ายเงินมาก่อน\n\n";
                Cache::put($cacheKey, $header, 3600);
                return $header;
            }

            $daysAgo = (int) max(0, now()->diffInDays($last->paid_at, false) * -1);
            $daysText = $daysAgo === 0 ? 'วันนี้' : ($daysAgo === 1 ? 'เมื่อวาน' : "{$daysAgo} วันก่อน");

            // หา topic จาก questions[0]
            $questions = $last->questions ?? [];
            $firstQ = is_array($questions) && ! empty($questions) ? (string) ($questions[0] ?? '') : '';
            $topicSnippet = mb_substr(trim($firstQ), 0, 60);
            $topicText = $topicSnippet ? "เรื่อง \"{$topicSnippet}\"" : 'ไม่ระบุเรื่อง';

            $header = "👤 [CUSTOMER_TIMELINE] ลูกค้าเก่า — ดูดวงแบบจ่ายเงินเป็นครั้งที่ ".($count + 1).
                " / ครั้งล่าสุด {$daysText} {$topicText}\n".
                "  ⚠️ AI: รู้จักลูกค้าคนนี้แล้ว — ห้ามทำเหมือนพบครั้งแรก ห้ามถามชื่อ/ทักทาย formal เกินไป\n\n";

            Cache::put($cacheKey, $header, 3600);
            return $header;
        } catch (\Throwable $e) {
            Log::debug('CustomerPersonaService: buildTimelineHeader fail (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            Cache::put($cacheKey, 'EMPTY', 600);
            return '';
        }
    }

    /**
     * 🚀 Dispatch async extraction (วิเคราะห์ message ลูกค้า → update persona)
     *
     * Throttled — ไม่ run บ่อยกว่า 30 นาที/user
     */
    public function dispatchExtraction(
        string $platform,
        string $userId,
        string $messageText,
        ?string $displayName = null
    ): bool {
        // Skip ถ้าข้อความสั้นเกินไป
        if (mb_strlen(trim($messageText)) < self::MIN_MESSAGE_LENGTH) {
            return false;
        }

        // 🚩 (2026-05-25) Critical keyword → throttle สั้นลง 30→10 min
        //   เคสจริง conv 4936: "เคยคิดฆ่าตัวตาย 3 ครั้ง" / "กินยานอนหลับจนดื้อยา"
        //   → ระบบต้อง flag เร็ว เพื่อปรับ tone ตอบ + แนะ 1323/1669
        $isCritical = $this->hasCriticalKeyword($messageText);
        $throttleTtl = $isCritical
            ? self::EXTRACTION_THROTTLE_TTL_CRITICAL
            : self::EXTRACTION_THROTTLE_TTL;

        // Throttle — กัน spam AI cost
        $throttleKey = $this->throttleCacheKey($platform, $userId);
        if (Cache::has($throttleKey) && ! $isCritical) {
            Log::debug('CustomerPersonaService: skip extraction — throttled', [
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            return false;
        }
        Cache::put($throttleKey, true, $throttleTtl);

        // 🎯 (2026-05-17) ใช้ dispatchAfterResponse — ทำงานได้ทั้ง sync และ async driver
        //   เดิม: ถ้า QUEUE_CONNECTION=sync → skip dispatch → persona table ว่างเปล่า
        //   ใหม่: dispatchAfterResponse() รันหลัง webhook ส่ง response แล้ว → ไม่ block ลูกค้า
        //         + ทำงานได้แม้ไม่มี queue worker (รันใน same PHP process)
        //   ถ้า queue driver ≠ sync → ใช้ async dispatch ปกติ (delay 5s ให้ message setdown)
        $driver = config('queue.default', 'sync');

        try {
            if ($driver === 'sync') {
                // Sync driver → ใช้ dispatchAfterResponse (รันหลัง webhook response)
                ExtractCustomerPersonaJob::dispatchAfterResponse(
                    $platform,
                    $userId,
                    $messageText,
                    $displayName
                );

                Log::info('CustomerPersonaService: dispatched extraction (after response)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($messageText),
                    'mode' => 'sync_after_response',
                ]);
            } else {
                // Async driver → ใช้ queue normal
                ExtractCustomerPersonaJob::dispatch(
                    $platform,
                    $userId,
                    $messageText,
                    $displayName
                )->delay(now()->addSeconds(5));

                Log::info('CustomerPersonaService: dispatched extraction (queue)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'message_length' => mb_strlen($messageText),
                    'mode' => 'async_queue',
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: dispatch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🔥 Invalidate cache หลัง update persona (จาก job)
     */
    public function invalidateCache(string $platform, string $userId): void
    {
        Cache::forget($this->injectCacheKey($platform, $userId));
    }

    /**
     * 👤 (2026-05-25 Patch F) Eager Persona extraction หลังลูกค้าจ่ายเงิน
     *
     * เคสจริง R3741 (2026-05-25): ลูกค้าทัก 10:11 → จ่าย 10:24 = 13 นาที
     * → Persona ไม่ทันสร้าง → AI ทำนาย Q1 โดยไม่รู้นิสัย → ตอบ generic
     *
     * Fix: ดึง messages 60 นาทีก่อน paid_at รวมเป็นหนึ่งก้อน → ส่งให้ Job
     *      Bypass throttle ปกติ (30 min) — เคสนี้สำคัญ เริ่มทำนายแล้ว
     *
     * Window: 60 min ก่อน paid_at (เหมือน buildPreCelticChatContext)
     * Cap: รวม 2000 chars (กัน prompt บวม)
     */
    public function dispatchEagerExtractionOnPaid(FortuneReading $reading): bool
    {
        // Resolve platform + user
        $platform = $reading->platform
            ?? (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
        $userId = (string) ($reading->facebook_user_id ?? $reading->line_user_id ?? '');

        if ($userId === '' || ! $reading->paid_at) {
            return false;
        }

        // ดึง user messages 60 min ก่อน paid_at จาก line_bot_messages
        $anchor = $reading->paid_at;
        $messages = DB::table('line_bot_conversations as c')
            ->join('line_bot_messages as m', 'm.conversation_id', '=', 'c.id')
            ->where('c.platform', $platform)
            ->where('c.line_user_id', $userId)
            ->where('m.role', 'user')
            ->where('m.created_at', '>=', $anchor->copy()->subMinutes(60))
            ->where('m.created_at', '<=', $anchor)
            ->orderBy('m.created_at')
            ->pluck('m.message')
            ->filter(fn ($t) => trim((string) $t) !== '')
            ->all();

        if (empty($messages)) {
            Log::debug('CustomerPersonaService: eager extraction skip — no pre-paid messages', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            return false;
        }

        // รวม + cap
        $combined = trim(implode(' / ', $messages));
        if (mb_strlen($combined) > 2000) {
            $combined = mb_substr($combined, -2000); // เก็บ tail (ใกล้ paid_at ที่สุด)
        }

        if (mb_strlen($combined) < self::MIN_MESSAGE_LENGTH) {
            return false;
        }

        // 🔥 Bypass throttle — eager call หลัง paid สำคัญกว่า throttle ปกติ
        $displayName = $reading->facebook_user_name ?? null;

        try {
            // Always sync after-response style (run hot)
            ExtractCustomerPersonaJob::dispatchAfterResponse(
                $platform,
                $userId,
                $combined,
                $displayName
            );

            // Update throttle ให้ skip duplicate extraction ใน 10 นาที
            Cache::put(
                $this->throttleCacheKey($platform, $userId),
                true,
                self::EXTRACTION_THROTTLE_TTL_CRITICAL
            );

            Log::info('CustomerPersonaService: dispatched EAGER extraction on paid', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'user_id' => $userId,
                'pre_paid_msgs' => count($messages),
                'combined_length' => mb_strlen($combined),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: eager extraction dispatch failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🛒 (2026-05-18) บันทึก "บอทเสนอขาย" — เรียกใน Hook A
     *
     * Throttle 5 นาที (ใน model) — เรียกซ้ำๆ ใน flow เดียวกันได้ปลอดภัย
     */
    public function recordPitch(string $platform, string $userId, ?string $displayName = null): bool
    {
        try {
            $persona = FortuneCustomerPersona::getOrCreate($platform, $userId, $displayName);
            $recorded = $persona->recordPitch();

            if ($recorded) {
                $this->invalidateCache($platform, $userId);

                Log::info('CustomerPersonaService: recorded sales pitch', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'total_pitches' => $persona->sales_pitch_count,
                ]);
            }

            return $recorded;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: recordPitch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🤐 (2026-05-18) ตรวจว่า bot ควรเงียบกับลูกค้าคนนี้หรือไม่
     *
     * ใช้ใน Hook B — เรียก lazy resume ถ้า cooldown หมด
     * Caller ต้องตรวจ bypass keyword เอง (FortuneCustomerPersona::shouldBypassSilence)
     */
    public function isChatSilenced(string $platform, string $userId): bool
    {
        try {
            $persona = $this->getCached($platform, $userId);
            if (! $persona) {
                return false;
            }

            $wasSilenced = $persona->chat_silenced_until !== null
                && $persona->chat_silenced_until->gt(now());

            $stillSilenced = $persona->isChatSilenced();

            // ถ้า lazy resume ทำงาน → invalidate cache (state เปลี่ยน)
            if ($wasSilenced && ! $stillSilenced) {
                $this->invalidateCache($platform, $userId);

                Log::info('CustomerPersonaService: chat resumed from cooldown', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'new_score' => $persona->time_waster_score,
                ]);
            }

            return $stillSilenced;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: isChatSilenced ล้มเหลว (default: not silenced)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 💬 (2026-05-18) บันทึก "ลูกค้าตอบฟุ้งหลังบอทเสนอ" — Hook C
     *
     * ตรวจ pre-conditions:
     *  - last_pitch_at อยู่ใน rolling 30min window
     *  - message ไม่ได้มี bypass keyword
     *  - caller ต้องตรวจ looksLikeMetaOrChitchat() เอง
     *
     * @return array{triggered: bool, goodbye_message: ?string}
     *   triggered = true → silence เริ่ม → caller ส่ง goodbye_message แทน AI response
     */
    public function recordChitchatAfterPitch(string $platform, string $userId, string $messageText): array
    {
        $result = ['triggered' => false, 'goodbye_message' => null];

        try {
            // Skip ถ้ามี bypass keyword (ลูกค้าพร้อมจ่าย/ดูดวงต่อ)
            if (FortuneCustomerPersona::shouldBypassSilence($messageText)) {
                return $result;
            }

            $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
            if (! $persona || ! $persona->last_pitch_at) {
                return $result;
            }

            // ต้องอยู่ใน rolling window
            $windowMinutes = FortuneCustomerPersona::PITCH_WINDOW_MINUTES;
            if ($persona->last_pitch_at->lt(now()->subMinutes($windowMinutes))) {
                return $result;
            }

            $reachedThreshold = $persona->recordChitchatAfterPitch($messageText);

            if ($reachedThreshold) {
                $reason = sprintf(
                    'failed %d pitches in %dmin window — example: %s',
                    $persona->sales_pitch_failed_count,
                    $windowMinutes,
                    mb_substr($messageText, 0, 100)
                );

                $persona->triggerSilence($reason);

                $result['triggered'] = true;
                $result['goodbye_message'] = $persona->buildGoodbyeMessage();

                Log::info('CustomerPersonaService: silence triggered (chitchat threshold reached)', [
                    'platform' => $platform,
                    'user_id' => $userId,
                    'failed_count' => $persona->sales_pitch_failed_count,
                    'new_score' => $persona->time_waster_score,
                    'silenced_until' => $persona->chat_silenced_until?->toIso8601String(),
                ]);

                $this->invalidateCache($platform, $userId);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: recordChitchatAfterPitch ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
    }

    /**
     * 🆕 (2026-05-29) Content-agnostic free-chat turn cap — wind-down เชิงระบบ
     *
     * ปัญหา: Hook C เดิม (recordChitchatAfterPitch) พึ่ง keyword detector
     *   (looksLikeMetaOrChitchat / looksLikeLowValueRamble) + ต้องมี last_pitch_at
     *   ใน window → เคส venter/ผู้สูงอายุพิมพ์เรื่องแปลกๆ (พระเจ้า/บาป/บุญ/ขายยา)
     *   ที่ keyword จับไม่ได้ → failed_count ค้าง → ไม่เคยเงียบ → บอทตอบ AI ไม่เลิก
     *   (เคสสนิท สาม่าน 87 turns/วัน, 0฿)
     *
     * วิธี: นับ free-chat turns/วัน ด้วย Cache counter (ไม่อิง keyword/pitch)
     *   เกิน FREECHAT_DAILY_CAP + ไม่มี buy-intent + ไม่วิกฤต → trigger silence
     *   + ส่ง goodbye สุภาพ 1 ครั้ง
     *
     * ⚠️ Caller ต้องเช็ค hasPaidActiveReading ก่อนเรียก (ลูกค้าจ่าย = bypass ทั้งหมด)
     *
     * @return array{triggered: bool, goodbye_message: ?string}
     *   triggered = true → silence เริ่ม → caller ส่ง goodbye_message แทน AI response
     */
    public function recordFreeChatTurn(string $platform, string $userId, string $messageText): array
    {
        $result = ['triggered' => false, 'goodbye_message' => null];

        try {
            // buy-intent (ดูดวง/จ่าย/celtic...) → ไม่นับ + ไม่ silence (ลูกค้าอาจพร้อมซื้อ)
            //   🛡️ (2026-05-29 Fix A) เดิม reset counter เป็น 0 ที่บรรทัดนี้ = ช่องโหว่:
            //     troll พิมพ์คำว่า "ดูดวง"/"พร้อม"/"ไพ่"/"99" คั่นทุก ~24 ข้อความ → ล้าง counter
            //     → วนเล่น free-chat ไม่จบ (ทำลาย turn-cap วันเดียวกัน) แถม goodbye message
            //     ยังบอกให้พิมพ์ "ดูดวง" เอง = ยื่นวิธี bypass ให้ลูกค้า
            //   แก้: ไม่ reset ที่นี่ — buy-intent แค่ "ไม่ถูกนับ" (ผ่านไป route tier menu ปกติ ซึ่งถูก/เบา)
            //     counter จะถูกล้างเฉพาะตอน "จ่ายเงินจริง" (confirmPayment → clearFreeChatCounter)
            //     ดังนั้น troll ที่พิมพ์เพ้อเจ้อ (ไม่ใช่ buy-intent) ยังถูกนับสะสมจน cap → เงียบ
            if (FortuneCustomerPersona::shouldBypassSilence($messageText)) {
                return $result;
            }

            // วิกฤต (อยากตาย/หมดหวัง...) → ห้าม silence (ต้อง empathy ไม่ตัดบทเงียบ)
            if ($this->hasCriticalKeyword($messageText)) {
                return $result;
            }

            $cap = FortuneCustomerPersona::FREECHAT_DAILY_CAP;
            $key = $this->freeChatCounterKey($platform, $userId);

            // increment daily counter (TTL = สิ้นวัน → พรุ่งนี้เริ่มนับใหม่)
            $count = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $count, now()->endOfDay());

            if ($count < $cap) {
                return $result;
            }

            // ถึง cap → silence + goodbye สุภาพ (content-agnostic)
            $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
            if ($persona) {
                $persona->triggerSilence(sprintf(
                    'free-chat daily cap reached (%d turns/day, unpaid, no buy-intent)',
                    $count
                ));
                $goodbye = $persona->buildGoodbyeMessage();
                $this->invalidateCache($platform, $userId);
            } else {
                $goodbye = '🌙 ขอบคุณที่พูดคุยกับแม่หมอวันนี้นะคะ 🙏 ถ้าอยากดูดวง พิมพ์ "ดูดวง" ได้เลยค่ะ เดี๋ยวกลับมาคุยกันใหม่ ✨';
            }

            $result['triggered'] = true;
            $result['goodbye_message'] = $goodbye;

            Log::info('CustomerPersonaService: free-chat turn cap reached → silence', [
                'platform' => $platform,
                'user_id' => $userId,
                'count' => $count,
                'cap' => $cap,
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::warning('CustomerPersonaService: recordFreeChatTurn ล้มเหลว (non-blocking)', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
    }

    /**
     * 🆕 (2026-05-29) Cache key สำหรับ free-chat daily counter (reset สิ้นวัน)
     */
    private function freeChatCounterKey(string $platform, string $userId): string
    {
        return "fortune:freechat_count:{$platform}:{$userId}:".now()->format('Ymd');
    }

    /**
     * 🆕 (2026-05-29) ล้าง free-chat counter — เรียกตอน buy-intent / paid event
     */
    public function clearFreeChatCounter(string $platform, string $userId): void
    {
        try {
            Cache::forget($this->freeChatCounterKey($platform, $userId));
        } catch (\Throwable $e) {
            // non-blocking
        }
    }

    /**
     * 📝 Export persona เป็น Obsidian markdown
     */
    public function toObsidianMarkdown(FortuneCustomerPersona $persona): string
    {
        return $persona->toObsidianMarkdown();
    }

    private function injectCacheKey(string $platform, string $userId): string
    {
        return "fortune:persona:inject:{$platform}:{$userId}";
    }

    private function throttleCacheKey(string $platform, string $userId): string
    {
        return "fortune:persona:extract_throttle:{$platform}:{$userId}";
    }

    /**
     * 🚩 (2026-05-25) ตรวจ critical keyword → ลด throttle จาก 30→10 min
     *
     * Keywords ที่ถือเป็น mental crisis / abuse → ต้อง extract เร็ว
     * เพื่อ flag mental_fragile / abusive_tone / scam_victim ทันสถานการณ์
     */
    public function hasCriticalKeyword(string $text): bool
    {
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return false;
        }

        $critical = [
            // Mental crisis
            'ฆ่าตัวตาย', 'ฆ่าตัวเอง', 'อยากตาย', 'อยากจบ',
            'ทำร้ายตัวเอง', 'กรีดข้อมือ', 'กินยาตาย',
            'หมดหวัง', 'ไม่อยากอยู่', 'ไม่ไหวแล้ว',
            'กินยานอนหลับ', 'ดื้อยา',
            'ซึมเศร้า', 'แพนิค',
            // Abusive
            'ห่า', 'เหี้ย', 'ไอ้สัส', 'แม่ง', 'ควาย',
            'โง่', 'กาก', 'ห่วยแตก',
            // Scam victim
            'โดนหลอก', 'โดนโกง', 'มิจฉาชีพ', 'คอลเซ็นเตอร์',
            // 🎭 (2026-05-25) Hostile superior — กล่าวหา/เหยียดบอท
            'หาแดก', 'มั่ว', 'หลอกลวง', 'หลอกกินเงิน', 'เพจหลอก',
            'แถ', 'คิดเอาเอง', 'นั่งทางใน', 'ไม่ใช่เลย',
            // 🎭 Disruptive troll — เพ้อเจ้อ/แปลกประหลาด
            'เจอผีบอก', 'เทพมาเข้าฝัน', 'ได้ยินเสียง', 'มีคนตามมา',
        ];

        foreach ($critical as $kw) {
            if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}
