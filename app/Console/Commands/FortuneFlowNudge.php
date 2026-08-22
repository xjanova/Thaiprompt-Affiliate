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

    /** ⏱️ (2026-08-13, owner: "ทำไมมีหลายกล่องเกิน") ขั้น "เลือกแพคเกจ" รอนานกว่า = 3 นาที
     *  (170 ไม่ใช่ 180 ด้วยเหตุผล granularity เดียวกับด้านบน)
     *
     *  ราก: เมนูแพคเกจยาว 3 ย่อหน้า (39 / Celtic 99 / คุณไสย 99) ลูกค้าส่วนใหญ่เป็นผู้สูงอายุ
     *  อ่านยังไม่จบใน 50 วิ ก็โดนกล่องกระตุ้นทับ กลายเป็นเมนูซ้อนกัน 2 ชุดบนจอเดียว
     *  ทั้งที่เป็นตัวเลือกชุดเดียวกัน → ลูกค้าสับสนว่าต้องกดอันไหน
     *
     *  ⚠️ ขั้น "กล่องกติกา" (consent_gate) ยังใช้ 50 วิเหมือนเดิม — ตรงนั้นลูกค้าเลือกแพคเกจแล้ว
     *     กล่องสั้น แค่รอกด "พร้อมบูชาครู" กระตุ้นเร็วช่วยปิดการขาย ไม่ใช่ต้นเหตุกล่องรก */
    private const NUDGE_AFTER_SEC_TIER = 170;

    /** เงียบกี่วินาทีถึงออกจากโฟลว์อัตโนมัติ (30 นาที) */
    private const EXIT_AFTER_SEC = 1800;

    /** Cache key prefix ของ consent gate (sync กับ FortuneConsentGateTrait) */
    private const CONSENT_PENDING_PREFIX = 'fortune:consent_pending:';

    /** 🔊 (2026-06-26) Cache key — รหัสยืนยันเสียงกติกา (ต้องตรงกับ FortuneConsentGateTrait::CONSENT_CODE_PREFIX) */
    private const CONSENT_CODE_PREFIX = 'fortune:consent_code:';

    /** 📋 (2026-07-11) Cache key — ขั้นแบบสอบถามยืนยันเจตนา (ต้องตรงกับ FortuneConsentGateTrait::CONSENT_QUIZ_STEP_PREFIX) */
    private const CONSENT_QUIZ_STEP_PREFIX = 'fortune:consent_quiz_step:';

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

                // 🛒 (2026-08-22) เสนอดูดวงแล้วลูกค้าไม่เอา/เงียบหาย → ทิ้งของเสริมดวงไว้ให้ก่อนปิด
                //    จุดนี้เดิม "ปิดเงียบ" ไปเปล่าๆ — เป็นโอกาสสุดท้ายก่อนลูกค้าหายไปเฉยๆ
                //    ⚠️ ต้องอยู่ "หลัง" อัปเดตสถานะเป็น COMPLETED — ถ้าส่งก่อนแล้วส่งพัง
                //       reading จะค้างสถานะเดิม แล้ว cron รอบหน้าหยิบมาส่งซ้ำทุกนาที
                $this->offerProductsOnExit($reading, $platform, $userId);
            }
            $this->line("  #{$reading->id} [{$step}] EXIT (silent {$silenceSec}s)");
            $stats['exit']++;

            return;
        }

        // 🛡️ (2026-07-24) Guard: platform=line แต่ userId ไม่ใช่รูปแบบ LINE userId (^U + 32 hex)
        //   = ข้อมูล platform เพี้ยน (เคส reading 10179: chat redirect สร้าง reading platform='line'
        //   ทั้งที่ id เป็น FB PSID ตัวเลขล้วน). ถ้า nudge ต่อ → LineFortuneService.pushMessage
        //   ยิง LINE API ด้วย 'to' เป็นตัวเลข → 400 "invalid to" ซ้ำทุกนาที (push fail → ไม่เซ็ต
        //   flow_nudge_sent_at → cron หยิบซ้ำทุกรอบ). แก้: ข้าม nudge + log ครั้งเดียว ไม่ retry
        //   (EXIT 30 นาทีด้านบนยังทำงาน → ปิด reading เพี้ยนนี้เองในที่สุด)
        if ($platform === 'line' && ! preg_match('/^U[0-9a-f]{32}$/i', $userId)) {
            if (empty($reading->getConversationState('flow_platform_mismatch_logged'))) {
                Log::warning('⚠️ FortuneFlowNudge: platform=line แต่ userId ไม่ใช่รูปแบบ LINE — ข้าม nudge (ข้อมูล platform เพี้ยน, ไม่ retry)', [
                    'reading_id' => $reading->id,
                    'step' => $step,
                    'platform' => $platform,
                    'user_id' => $userId,
                ]);
                if (! $dry) {
                    $reading->setConversationState('flow_platform_mismatch_logged', now()->toIso8601String());
                }
            }
            $stats['skip']++;

            return;
        }

        // 💸 (2026-06-26) toggle กระตุ้นจ่ายบิล ปิด → ไม่ส่ง nudge (กดพร้อมบูชาครู) — แต่ exit-cleanup ด้านบนยังทำงาน
        if (! (bool) ($settings->enable_bill_payment_nudge ?? true)) {
            $stats['skip']++;

            return;
        }

        // ── NUDGE: เงียบครบเวลา + ยังไม่เคยเตือน → ส่งกล่อง 1 ครั้ง ──
        //   เกณฑ์เวลาแยกตาม step: เลือกแพคเกจ 3 นาที (เมนูยาว ต้องให้เวลาอ่าน) · กติกา 1 นาที
        $nudgeAfterSec = $step === 'tier_choice'
            ? self::NUDGE_AFTER_SEC_TIER
            : self::NUDGE_AFTER_SEC;

        if ($silenceSec >= $nudgeAfterSec && empty($reading->getConversationState('flow_nudge_sent_at'))) {
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
     * 🛒 ทิ้งของเสริมดวงไว้ให้ ตอนลูกค้าเงียบหายจากขั้นเสนอขายดูดวง
     *
     * เจตนา (owner 2026-08-22): "เสนอดูดวงแล้วลูกค้าไม่ดู หรือเลื่อนไปก่อน
     * ก็เสนอสินค้าเสริมดวงทิ้งเอาไว้"
     *
     * ⚠️ ด่านความปลอดภัยของช่องทาง — ต้องเช็คเองที่นี่
     *   ด่านตรวจ platform/userId เพี้ยน (line แต่ id เป็นตัวเลข) อยู่ **ใต้** จุด EXIT
     *   ⇒ โค้ดตรงนี้ยังไม่ผ่านด่านนั้น ถ้ายิงเลยจะได้ LINE API 400 "invalid to"
     *     กับ reading ที่ข้อมูลเพี้ยน (เคส reading 10179)
     *
     * ⚠️ ห้ามให้ล้มแล้วกระทบการปิด reading — ห่อ try/catch คืนเงียบๆ
     */
    private function offerProductsOnExit(FortuneReading $reading, string $platform, string $userId): void
    {
        if ($userId === '') {
            return;
        }

        // ข้อมูล platform เพี้ยน → ห้ามยิง (เหตุผลเดียวกับด่าน nudge ด้านล่าง)
        if ($platform === 'line' && ! preg_match('/^U[0-9a-f]{32}$/i', $userId)) {
            return;
        }

        try {
            app(\App\Services\Fortune\FortuneMuOfferService::class)->offer(
                $platform,
                $userId,
                \App\Models\FortuneProductOffer::TRIGGER_PITCH_DECLINED,
                $reading,
                // ไม่ต้องส่งบริบทเอง — service แกะหัวข้อ/ปีเกิดจาก reading ให้แล้ว
                // (แกะเองทุก call site = เขียนซ้ำ 6 ที่ แล้วเพี้ยนกันทีละจุด)
            );
        } catch (\Throwable $e) {
            Log::warning('FortuneFlowNudge: เสนอสินค้าตอน exit ล้มเหลว (ไม่กระทบการปิด reading)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
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

            // 🪬 (2026-08-12, owner) ปุ่มโหมดดูคุณไสย์ — กล่องกระตุ้นนี้เคยมีแค่ 99/39
            //   ทั้งที่เมนูแพคเกจตัวจริงมี 3 ทางเลือก (ดู FortuneChannelManager กับ
            //   LineFortuneService::buildTierChoiceFlexMessage ที่มีปุ่มนี้มาตั้งแต่ 2026-06-24)
            //   → ลูกค้าที่ค้างอยู่ตรงเลือกแพคเกจ พอโดนกระตุ้นจะเห็นแค่ 2 ทาง ทั้งที่ข้อความ
            //     ด้านบนเพิ่งเล่าเรื่องคุณไสย 99 ไป = เสียลูกค้าที่ตั้งใจมาดูเรื่องนี้โดยเฉพาะ
            //   gate เดียวกับที่อื่นเป๊ะ ๆ (CelticCrossConversationTrait:342) — ต้องเปิด Celtic ด้วย
            //   เพราะโหมดนี้คือ Celtic 99 ที่ล็อกเลนส์ ไม่ใช่แพคเกจแยก
            $blackMagicEnabled = $celticEnabled
                && (bool) ($settings->enable_celtic_black_magic_mode ?? true);

            // 🏷️ (2026-08-13, owner) ป้ายปุ่ม + ลำดับ ต้องตรงกับเมนูแพคเกจตัวจริงเป๊ะ ๆ
            //   (FortuneChannelManager tier_choice: 39 → vip 99 → คุณไสย)
            //   เดิมกล่องนี้ใช้ป้ายคนละชุด ("✨ เริ่มเลย 99฿" / "🔮 แพคเกจ 39฿") เรียงสลับด้วย
            //   → ลูกค้าอ่านว่าเป็น "เมนูใหม่อีกชุด" ไม่ใช่การย้ำเมนูเดิม = ต้นเหตุที่เจ้าของเห็น
            //     ว่ามีหลายกล่องเกิน. ไฟล์นี้ตกหล่นจากรอบเปลี่ยนป้าย 99 ทุกจุด (commit 0aef22d16
            //     แก้ 9 ไฟล์ ไม่มี FortuneFlowNudge)
            //   ⚠️ ห้ามใส่อีโมจินำหน้าป้าย 99 — FB/LINE ตัด label ที่ 20 ตัว และ
            //      "ดู vip ส่วนตัว 99บาท" = 20 ตัวพอดี ใส่อีโมจิแล้วคำว่า "บาท" จะหายไป
            $buttons = [];
            if ($deepEnabled) {
                $buttons[] = ['content_type' => 'text', 'title' => "🔹 ดูดวง {$deepPrice} บาท", 'text' => '39', 'payload' => '39'];
            }
            if ($celticEnabled) {
                // 🚨 mb_substr 20 ไม่ใช่ของประดับ — กล่องนี้ส่ง LINE ด้วย และ
                //    LineFortuneService::buildQuickReplyItems ไม่ตัด label ให้ (ต่างจากฝั่ง FB)
                //    LINE API ปฏิเสธ label > 20 ตัว = ยิงไม่ออกทั้งกล่อง
                //    "ดู vip ส่วนตัว 99บาท" = 20 พอดี แต่ถ้าแอดมินขึ้นค่าครูเป็น 3 หลัก (129)
                //    จะกลายเป็น 21 ตัว → กล่องกระตุ้นตายเงียบทั้งใบบน LINE
                $celticLabel = mb_substr("ดู vip ส่วนตัว {$celticPrice}บาท", 0, 20);
                $buttons[] = ['content_type' => 'text', 'title' => $celticLabel, 'text' => 'celtic', 'payload' => 'celtic'];
            }
            // 📦 ครบ 3 ปุ่มพอดี = กล่องเดียวจบ ถ้าอนาคตเพิ่มปุ่มที่ 4 ระบบจะแตกเป็น 2 กล่อง
            //    (2+2) ส่งไปให้ครบเอง — ไม่ตกกลับไปเป็น quick reply แล้ว
            //    (ดู FacebookWebhookService::sendPostbackButtons)
            // 📌 ส่ง text "ดูคุณไสย" ไม่ใช่ payload TIER_CELTIC_BLACKMAGIC — กล่องนี้ส่งทั้ง FB
            //    และ LINE ด้วยชุดปุ่มเดียวกัน คำว่า "ดูคุณไสย" เข้าด่าน keyword ใน
            //    handleTierChoice ได้ทั้งสองช่องทาง (CelticCrossConversationTrait:514)
            if ($blackMagicEnabled) {
                $buttons[] = ['content_type' => 'text', 'title' => "🪬 ดูคุณไสย {$celticPrice}฿", 'text' => 'ดูคุณไสย', 'payload' => 'ดูคุณไสย'];
            }

            $message = "🌙 เลือกได้เลยนะคะ คุณ{$name} — แม่หมอจันทรารออยู่ค่ะ ✨\n\n"
                .'กดปุ่มด้านล่างเพื่อเริ่มดูดวงได้เลย 👇';

            return [$message, $buttons];
        }

        // 📋 (2026-07-11) โหมดแบบสอบถามยืนยันเจตนา — มีแบบสอบถามค้าง → กระตุ้นให้ "ตอบ ใช่/ไม่ใช่"
        //   ไม่ใช่ "กดพร้อมบูชาครู" (กันคำสั่งขัดกันตอนลูกค้ากำลังตอบคำถามยืนยันเจตนา)
        if ((bool) ($settings->enable_consent_quiz ?? false)
            && Cache::get(self::CONSENT_QUIZ_STEP_PREFIX.$userId) !== null) {
            $message = "🙏 รบกวนตอบคำถามยืนยันเจตนาให้ครบก่อนนะคะ — พิมพ์ \"ใช่\" หรือ \"ไม่ใช่\" ในแต่ละข้อค่ะ\n\n"
                .'ถ้าไม่สะดวกแล้ว พิมพ์ "ยกเลิก" ได้เลยนะคะ';
            $buttons = [
                ['content_type' => 'text', 'title' => '✅ ใช่', 'text' => 'ใช่', 'payload' => 'ใช่'],
                ['content_type' => 'text', 'title' => '🙏 ยกเลิก', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก'],
            ];

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
