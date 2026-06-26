<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-06-20) Flow Nudge — กระตุ้นลูกค้าที่ค้างขั้น "เลือกแพคเกจ" / "กล่องกติกา (ก่อนกดโอนค่าบูชาครู)"
 *
 * user spec:
 *   - ขั้นเลือกแพคเกจ (tier_choice): เงียบ/ไม่เลือก > 1 นาที → ส่งกล่อง "เลือกได้เลยนะคะ" + ปุ่มแพคเกจ
 *   - ขั้นกล่องกติกา (consent gate): เงียบ > 1 นาที → ส่งกล่อง "เมื่อพร้อมโอนค่าบูชาครู กดพร้อม..." + ปุ่มพร้อมบูชาครู
 *   - เงียบ > 30 นาที → ออกจากโฟลว์อัตโนมัติ "เงียบๆ" เฉพาะที่ยังไม่สร้างบิลใดๆ (ไม่ส่งข้อความ)
 *
 * Design:
 *   - กระตุ้น "ครั้งเดียวต่อ step" (flag flow_nudge_sent_at) — กัน spam
 *   - anchor เวลา = marker ตอนแสดงกล่อง (tier_choice_shown_at / consent_gate_shown_at)
 *     ติดตั้งที่ presentTierChoice + consentGateOrNull
 *   - ครอบเฉพาะ reading "ยังไม่จ่าย + ยังไม่มี bill_reference" → ไม่ยุ่งกับบิลที่สร้างแล้ว
 *     (ขั้นหลังบิล = FortuneSendBillReminder ดูแลอยู่แล้ว)
 *   - exit = ปิดเงียบ (status=COMPLETED) → ลูกค้ากลับไปแชทปกติ พิมพ์ "ดูดวง" เริ่มใหม่ได้
 *
 * Schedule: ทุก 1 นาที (routes/console.php — ไม่ใช่ Kernel.php ที่เป็น ghost ใน Laravel 11)
 *
 * Usage:
 *   php artisan fortune:flow-nudge
 *   php artisan fortune:flow-nudge --dry
 */
class FortuneFlowNudge extends Command
{
    protected $signature = 'fortune:flow-nudge
                            {--dry : Dry run — แสดงรายการ ไม่ส่ง/ไม่ปิดจริง}
                            {--limit=50 : จำนวน reading สูงสุดต่อ step}';

    protected $description = '🔔 กระตุ้นลูกค้าค้างขั้นเลือกแพคเกจ/กล่องกติกา (เงียบ 1 นาที) + ปิดเงียบถ้าเงียบ 30 นาที (ยังไม่สร้างบิล)';

    /** เงียบกี่วินาทีถึงกระตุ้น (50 ไม่ใช่ 60 — cron ทุก 1 นาทีบวก granularity → ให้ยิงรอบ :00
     *  แรกหลังลูกค้าเงียบ ~1 นาทีจริง ไม่งั้นต้องรอถึง ~2 นาที ลูกค้านึกว่าไม่ทำงาน) */
    private const NUDGE_AFTER_SEC = 50;

    /** เงียบกี่วินาทีถึงออกจากโฟลว์อัตโนมัติ (30 นาที) */
    private const EXIT_AFTER_SEC = 1800;

    /** Cache key prefix ของ consent gate (sync กับ FortuneConsentGateTrait) */
    private const CONSENT_PENDING_PREFIX = 'fortune:consent_pending:';

    /** 🔊 (2026-06-26) Cache key — รหัสยืนยันเสียงกติกา (ต้องตรงกับ FortuneConsentGateTrait::CONSENT_CODE_PREFIX) */
    private const CONSENT_CODE_PREFIX = 'fortune:consent_code:';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = max(1, (int) $this->option('limit'));
        $settings = FortuneTellingSetting::getSettings();

        // ปิดทั้งระบบดูดวง → ไม่กระตุ้น
        if (empty($settings->is_enabled)) {
            $this->info('ℹ️ ระบบดูดวงปิดอยู่ — ข้าม flow-nudge');

            return 0;
        }

        $stats = ['tier_nudge' => 0, 'consent_nudge' => 0, 'exit' => 0, 'skip' => 0];

        // ── A. ขั้นเลือกแพคเกจ (tier_choice — ยังไม่กดเลือก = ไม่มี consent marker) ──
        //   ⚠️ ตัวแยกคือ "marker" ไม่ใช่ status: ถ้ากด Celtic แล้ว consent_gate_shown_at จะถูกตั้ง
        //      แม้ status ยังเป็น tier_choice → ย้ายไปประมวลผลเป็น consent (กลุ่ม B) แทน
        //   ⚠️ ห้ามกรอง whereNull('bill_reference') — model auto-gen bill_reference ตอน create
        //      ทุก reading → จะ match 0 เสมอ. status=tier_choice (pre-payment) = "ยังไม่สร้างบิลจริง" อยู่แล้ว
        $tierReadings = FortuneReading::query()
            ->where('conversation_status', FortuneReading::STATUS_TIER_CHOICE)
            ->where(fn ($q) => $q->whereNull('is_paid')->orWhere('is_paid', false))
            ->where(function ($q) {
                $q->whereNull('conversation_state')
                    ->orWhere('conversation_state', 'not like', '%consent_gate_shown_at%');
            })
            ->orderBy('id') // เก่าสุดก่อน — คนรอนานสุดได้รับบริการก่อน (กัน starvation)
            ->limit($limit)
            ->get();
        foreach ($tierReadings as $reading) {
            $this->processReading($reading, 'tier_choice', 'tier_choice_shown_at', $settings, $dry, $stats);
        }

        // ── B. ขั้นกล่องกติกา ก่อนกดโอนค่าบูชาครู (consent gate — มี marker) ──
        //   consent-pending อยู่ได้ทั้ง status=tier_choice (Celtic จาก tier menu) หรือ
        //   collecting_birthdate (Deep / startDeepReadingFlow). ยกเลิกแล้ว = completed → ไม่เข้า
        //   ⚠️ ไม่กรอง bill_reference: Deep บาง path set bill_reference (string) ก่อนกล่องกติกา
        //      แต่ยังไม่ถึงขั้นจ่าย — status whitelist (ไม่รวม pending_payment) = กัน "บิลจริง" อยู่แล้ว
        $consentReadings = FortuneReading::query()
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_TIER_CHOICE,
                FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            ])
            ->where('conversation_state', 'like', '%consent_gate_shown_at%')
            ->where(fn ($q) => $q->whereNull('is_paid')->orWhere('is_paid', false))
            ->orderBy('id')
            ->limit($limit)
            ->get();
        foreach ($consentReadings as $reading) {
            $this->processReading($reading, 'consent_gate', 'consent_gate_shown_at', $settings, $dry, $stats);
        }

        $this->info("📊 tier_nudge={$stats['tier_nudge']} consent_nudge={$stats['consent_nudge']} exit={$stats['exit']} skip={$stats['skip']}");

        return 0;
    }

    /**
     * ประมวลผล reading แต่ละตัว — exit (30 นาที) ก่อน, ไม่งั้น nudge (1 นาที, ครั้งเดียว)
     */
    private function processReading(FortuneReading $reading, string $step, string $anchorKey, FortuneTellingSetting $settings, bool $dry, array &$stats): void
    {
        // marker ต้องมี (กัน reading เก่าก่อนมีฟีเจอร์ — ไม่แตะ)
        $shownAtRaw = $reading->getConversationState($anchorKey);
        if (empty($shownAtRaw)) {
            $stats['skip']++;

            return;
        }
        try {
            $anchor = Carbon::parse($shownAtRaw);
        } catch (\Throwable $e) {
            $stats['skip']++;

            return;
        }

        $platform = $reading->platform ?: (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
        $userId = (string) ($reading->platform_user_id ?: ($reading->facebook_user_id ?: ''));
        if ($userId === '') {
            $stats['skip']++;

            return;
        }

        // 🕐 "เงียบ" = นับจากกิจกรรมล่าสุด = max(ตอนแสดงกล่อง, ข้อความ user ล่าสุด)
        //   กันกระตุ้น/ปิดลูกค้าที่ยังคุยอยู่ (anchor เลื่อนตามข้อความจริง ไม่ใช่แค่ marker คงที่)
        try {
            $lastUserMsg = DB::table('line_bot_conversations as c')
                ->join('line_bot_messages as m', 'm.conversation_id', '=', 'c.id')
                ->where('c.platform', $platform)
                ->where('c.line_user_id', $userId)
                ->where('m.role', 'user')
                ->max('m.created_at');
            if (! empty($lastUserMsg)) {
                $lastUserAt = Carbon::parse($lastUserMsg);
                if ($lastUserAt->gt($anchor)) {
                    $anchor = $lastUserAt;
                }
            }
        } catch (\Throwable $e) {
            // หา last message ไม่ได้ → ใช้ marker อย่างเดียว
        }
        $silenceSec = (int) $anchor->diffInSeconds(now(), true);

        // ── EXIT: เงียบ ≥ 30 นาที → ปิดเงียบ "เฉพาะที่ยังไม่สร้างบิลใดๆ" (user spec) ──
        //   ⚠️ bill_reference auto-gen ทุก reading → ใช้เป็นตัวชี้ "บิลจริง" ไม่ได้
        //   status (tier_choice/collecting_birthdate) = pre-payment = ยังไม่มีบิลจริง (QR/UPA) อยู่แล้ว
        //   → query group A/B กรอง status + is_paid ไว้แล้ว ที่นี่ปิดได้เลย
        if ($silenceSec >= self::EXIT_AFTER_SEC) {
            if (! $dry) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                $reading->setConversationState('flow_exit_at', now()->toIso8601String());
                Cache::forget(self::CONSENT_PENDING_PREFIX.$userId);
                Log::info('🔕 FortuneFlowNudge: auto-exit (เงียบ 30 นาที, ยังไม่สร้างบิล)', [
                    'reading_id' => $reading->id,
                    'step' => $step,
                    'platform' => $platform,
                    'silence_sec' => $silenceSec,
                ]);
            }
            $this->line("  #{$reading->id} [{$step}] EXIT (silent {$silenceSec}s)");
            $stats['exit']++;

            return;
        }

        // ── NUDGE: เงียบ ≥ 1 นาที + ยังไม่เคยเตือน → ส่งกล่อง 1 ครั้ง ──
        if ($silenceSec >= self::NUDGE_AFTER_SEC && empty($reading->getConversationState('flow_nudge_sent_at'))) {
            $service = app(FortuneChannelManager::class)->getPlatform($platform);
            if (! $service) {
                $stats['skip']++;

                return;
            }

            [$message, $buttons] = $this->buildBox($step, $reading, $settings, $userId);
            if (empty($buttons)) {
                $stats['skip']++;

                return;
            }

            $statKey = $step === 'tier_choice' ? 'tier_nudge' : 'consent_nudge';

            if ($dry) {
                $this->line("  #{$reading->id} [{$step}] NUDGE (dry) silent {$silenceSec}s");
                $stats[$statKey]++;

                return;
            }

            try {
                $ok = $service->sendQuickReplies($userId, $message, $buttons);
                if ($ok) {
                    $reading->setConversationState('flow_nudge_sent_at', now()->toIso8601String());

                    // 💳 (2026-06-20) RE-ARM consent flag เมื่อ re-send กล่องกติกา (consent_gate)
                    //   ราก (มุกดา แสนนุภาพ FTU-260620-*): flow-nudge ส่งปุ่ม "พร้อมบูชาครู" ซ้ำ แต่ไม่ได้
                    //   ตั้ง fortune:consent_pending → ถ้า flag เดิมหมดอายุ/หาย ลูกค้ากด "พร้อมบูชาครูแล้ว"
                    //   → handleConsentAcceptIfPending เห็น Cache ว่าง → fall-through → สร้าง reading ใหม่
                    //   วน tier_choice ไม่ออก QR. แก้: re-arm flag (tier จาก marker) ให้ consent accept ทำงาน
                    if ($step === 'consent_gate') {
                        $tier = (string) ($reading->getConversationState('consent_gate_tier')
                            ?: ($reading->reading_type === FortuneReading::READING_TYPE_DEEP ? 'deep' : 'celtic'));
                        Cache::put(self::CONSENT_PENDING_PREFIX.$userId, $tier, 600); // 600s = FortuneConsentGateTrait::CONSENT_TTL
                    }

                    // 🎧 (2026-06-21) เสียงระบบ: อ่านกล่องกระตุ้นให้ฟัง (FB only) — best-effort
                    //   instanceof guard: sendAudio ไม่อยู่ใน MessagingPlatformInterface (LINE signature ต่าง)
                    if ($platform === 'facebook' && $service instanceof \App\Services\FacebookWebhookService) {
                        try {
                            $clipKey = $step === 'tier_choice' ? 'sales_nudge_tier' : 'sales_nudge_consent';
                            $voiceUrl = (new \App\Services\FortuneSystemVoiceService($settings))->urlFor($clipKey);
                            if (! empty($voiceUrl)) {
                                $service->sendAudio($userId, $voiceUrl);
                            }
                        } catch (\Throwable $e) {
                            // เสียงเป็นแค่ตัวช่วย ไม่ critical
                        }
                    }

                    Log::info('🔔 FortuneFlowNudge: nudge sent', [
                        'reading_id' => $reading->id,
                        'step' => $step,
                        'platform' => $platform,
                    ]);
                    $stats[$statKey]++;
                } else {
                    $stats['skip']++;
                }
            } catch (\Throwable $e) {
                $stats['skip']++;
                Log::warning('FortuneFlowNudge: nudge fail', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        $stats['skip']++;
    }

    /**
     * สร้างข้อความ + ปุ่ม ตาม step
     *
     * @return array{0: string, 1: array}
     */
    private function buildBox(string $step, FortuneReading $reading, FortuneTellingSetting $settings, string $userId): array
    {
        $name = $reading->facebook_user_name ?? 'เจ้าชะตา';

        if ($step === 'tier_choice') {
            $deepEnabled = $settings->isDeepReadingEnabled();
            $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
            $celticPrice = number_format((float) app(CelticCrossService::class)->getPrice(), 0);
            $deepPrice = number_format((float) ($settings->deep_reading_price ?? 39), 0);

            $buttons = [];
            if ($celticEnabled) {
                $buttons[] = ['content_type' => 'text', 'title' => "✨ เริ่มเลย {$celticPrice}฿", 'text' => 'celtic', 'payload' => 'celtic'];
            }
            if ($deepEnabled) {
                $buttons[] = ['content_type' => 'text', 'title' => "🔮 แพคเกจ {$deepPrice}฿", 'text' => '39', 'payload' => '39'];
            }

            $message = "🌙 เลือกได้เลยนะคะ คุณ{$name} — แม่หมอจันทรารออยู่ค่ะ ✨\n\n"
                .'กดปุ่มด้านล่างเพื่อเริ่มดูดวงได้เลย 👇';

            return [$message, $buttons];
        }

        // 🔊 (2026-06-26) โหมดบังคับรหัสเสียง — มีรหัสค้าง → กระตุ้นให้ "พิมพ์รหัสท้ายคลิป" ไม่ใช่ "กดพร้อมบูชาครู"
        //   กันคำสั่งขัดกัน (ปุ่มยอมรับ vs ต้องพิมพ์รหัส) ที่ทำลูกค้าสูงอายุงง/ติดลูป (บทเรียน 2026-06-24 lost customer)
        if ((bool) ($settings->enable_consent_audio_code ?? false)
            && Cache::get(self::CONSENT_CODE_PREFIX.$userId) !== null) {
            $message = "🔊 รบกวนกด ▶️ ฟังเสียงกติกา *ท้ายคลิป* แล้วพิมพ์ *รหัส 4 หลัก* ที่ได้ยินกลับมาในแชทนะคะ\n\n"
                .'ฟังไม่ได้/ไม่ชัด กดปุ่ม "ขอรหัส" ได้เลยค่ะ 🙏';
            $buttons = [
                ['content_type' => 'text', 'title' => '🔑 ขอรหัส (ฟังไม่ได้)', 'text' => 'ขอรหัส', 'payload' => 'ขอรหัส'],
                ['content_type' => 'text', 'title' => '🙏 ยกเลิก', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก'],
            ];

            return [$message, $buttons];
        }

        // consent_gate — tier เก็บใน marker (กัน reading_type ยังไม่อัปเดต / Cache หมดอายุ)
        $tier = (string) ($reading->getConversationState('consent_gate_tier')
            ?: (Cache::get(self::CONSENT_PENDING_PREFIX.$userId)
            ?: ($reading->reading_type === FortuneReading::READING_TYPE_DEEP ? 'deep' : 'celtic')));
        $price = $tier === 'deep'
            ? number_format((float) ($settings->deep_reading_price ?? 39), 0)
            : number_format((float) app(CelticCrossService::class)->getPrice(), 0);

        $message = "🙏 เมื่อพร้อมโอนค่าบูชาครูแล้ว กดปุ่ม \"พร้อมบูชาครู\" ด้านล่างได้เลยนะคะ\n\n"
            .'แล้วระบบจะพาเข้าสู่การโอนผ่าน QR หรือธนาคารอัตโนมัติค่ะ ✨';
        $buttons = [
            ['content_type' => 'text', 'title' => "🙏 พร้อมบูชาครู {$price}฿", 'text' => 'พร้อมบูชาครูแล้ว', 'payload' => 'พร้อมบูชาครูแล้ว'],
        ];

        return [$message, $buttons];
    }
}
