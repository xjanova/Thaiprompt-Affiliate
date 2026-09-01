<?php

namespace App\Console\Commands;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Services\CelticCrossService;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🐛 (2026-05-28) Celtic Q&A Re-Deliver — หลักประกันว่าลูกค้าจ่าย 99฿ ได้รับคำทำนายเสมอ
 *
 * ปัญหา: AI สร้างคำตอบสำเร็จ + markCelticAnswered + บันทึก response ลง DB
 *   แต่ FB/LINE push ล้มเหลว (transient / 24hr window / #551) → ลูกค้าไม่ได้รับ เห็นแค่ "ติดขัด"
 *   เคสจริง: บิล FTU-260528-E8815 (reading 4009) — คำตอบ 908 ตัวอักษรอยู่ใน DB แต่ลูกค้าไม่เห็น
 *   (pending architectural fix #1 จาก hotfix 2026-05-21 — markAnswered ก่อน confirm delivery)
 *
 * วิธีแก้: ChannelManager set `delivered_at` เมื่อ push สำเร็จ. cron นี้หา question ที่
 *   answered แต่ delivered_at ยังว่าง → re-push ผ่าน POST_PURCHASE_UPDATE (FB) / push (LINE)
 *   → ลูกค้าได้รับภายใน 1-2 นาทีแม้ push แรกพลาด
 *
 * เงื่อนไข candidate:
 *   - answered_at not null + response ไม่ว่าง + delivered_at null
 *   - answered_at ในช่วง [now-maxHours, now-minSeconds]  (min-age ให้ sync push ลองก่อน)
 *   - delivery_attempts < max-attempts  (cap กัน loop)
 *   - reading เป็น Celtic paid
 *
 * Schedule: ทุกนาที (routes/console.php) — withoutOverlapping
 *
 * Usage:
 *   php artisan fortune:celtic-redeliver
 *   php artisan fortune:celtic-redeliver --dry
 *   php artisan fortune:celtic-redeliver --min-seconds=60 --max-hours=2 --max-attempts=3
 */
class FortuneCelticRedeliver extends Command
{
    protected $signature = 'fortune:celtic-redeliver
                            {--dry : Dry run — แสดงรายการ ไม่ส่งจริง}
                            {--min-seconds=60 : อายุขั้นต่ำหลัง answered (ให้ sync push ลองก่อน)}
                            {--max-hours=2 : อายุสูงสุด — ไม่ส่งคำตอบเก่าเกิน}
                            {--max-attempts=3 : จำนวนครั้งสูงสุดที่พยายาม re-deliver}
                            {--limit=30 : จำนวน question สูงสุดต่อรอบ}';

    protected $description = 'ส่งคำทำนาย Celtic ซ้ำให้ลูกค้าที่ AI ตอบแล้วแต่ push แรกไม่ถึง (delivered_at null)';

    /**
     * เพดานการปั่นคำตอบใหม่ต่อการรัน 1 รอบ (นับทุกครั้งที่เรียก AI จริง ไม่ว่าสำเร็จหรือล้ม)
     *
     * AI ใช้ 20-50 วิ/ครั้ง และ cron นี้รันทุกนาที (`withoutOverlapping(5)`)
     * ⇒ ตั้งต่ำไว้ กันรอบเดียวลากยาวจนทับรอบถัดไป
     * ใบที่เกินเพดานจะถูกติดธง `celtic_regen_pending` ไว้ให้รอบถัดไปเก็บต่อ — ไม่เสียสิทธิ์กู้
     */
    private const MAX_REGEN_PER_RUN = 2;

    /**
     * เพดานจำนวนครั้งที่ยอมเรียก AI กู้คำตอบต่อ 1 บิล (กันลูปเผาเงิน AI)
     *
     * เดิมเป็นธง one-shot `celtic_regen_recovered_at` ที่ตั้ง "ก่อน" รู้ผล ⇒ gen ล้มครั้งเดียว
     * = บิลนั้นหมดสิทธิ์กู้ตลอดกาล — เปลี่ยนเป็นตัวนับ `celtic_regen_attempts` แทน
     */
    private const MAX_REGEN_ATTEMPTS_PER_BILL = 2;

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry');
        $minSeconds = max(15, (int) $this->option('min-seconds'));
        $maxHours = max(1, (int) $this->option('max-hours'));
        $maxAttempts = max(1, (int) $this->option('max-attempts'));
        $limit = max(1, (int) $this->option('limit'));

        // 🐛 (2026-05-29) Proactive recover stuck GENERATING — ไม่ต้องรอลูกค้าพิมพ์
        //   เคสจริง reading 4211 (บิล FTU-260529-H8518): status ค้าง CELTIC_GENERATING 212s
        //   (AI ตอบ 21s แต่ status ไม่คืน — FPM kill / race admin+webhook) → ลูกค้าพิมพ์โดน
        //   silent_skip เงียบ → ลูกค้างง ไม่พิมพ์ต่อ → admin ต้องเข้า panel ช่วยตอบเอง
        //   เดิม recover เป็น lazy (เช็คตอนลูกค้าพิมพ์ครั้งถัดไป) → ลูกค้าเงียบ = ไม่ recover
        //   Fix: cron นี้ (ทุกนาที) recover reading ที่ค้าง > 90s เป็น AWAITING เชิงรุก
        $this->recoverStuckGenerating($isDry);

        $candidates = FortuneCelticQuestion::query()
            ->undelivered()
            ->where('answered_at', '<=', now()->subSeconds($minSeconds))
            ->where('answered_at', '>=', now()->subHours($maxHours))
            ->where('delivery_attempts', '<', $maxAttempts)
            ->with('reading')
            ->orderBy('answered_at', 'asc')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มีคำทำนาย Celtic ที่ค้างส่ง');

            return 0;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $q) {
            $reading = $q->reading;

            // ต้องเป็น Celtic paid เท่านั้น (กันเคสแปลก)
            if (! $reading || ! $reading->is_paid
                || $reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
                $skipped++;

                continue;
            }

            // 🐛 (2026-05-29) Safety net — session จบแล้ว (completed) ไม่ต้อง re-deliver รายข้อ
            //   Grand Finale ตอนจบรวมคำตอบทุกข้อให้แล้ว — ถ้า cron ส่งคำตอบรายข้อซ้ำ
            //   ลูกค้าจะเห็นคำตอบเก่าโผล่ "หลังสรุป" (เคสจริง reading 4191 สมร: ครอบครัวซ้ำ)
            //   mark delivered กัน cron วนจับทุกนาทีจน maxAttempts (terminal state — ไม่ต้องส่งแล้ว)
            //   หมายเหตุ: endCelticSession ก็ mark ให้แล้ว — นี่คือ safety net เผื่อ legacy/race
            // 🐛 (2026-08-26) safety net นี้ถูกต้อง "เฉพาะตอนบทสรุปส่งออกจริง"
            //   ถ้า Grand Finale push ไม่ออก (โควต้าหมด/LINE ล่ม) แล้วยัง mark delivered
            //   = ลูกค้าจ่าย 99฿ ไม่ได้อะไรเลย แต่ DB บอกว่า "ส่งแล้ว" → ของหายแบบไร้ร่องรอย
            //   (เคสจริง reading 11594: คำถาม 6 ข้อถูก stamp พร้อมกันตอน push ได้ 429)
            //   ⚠️ default = true เพื่อความเข้ากันได้ย้อนหลัง — reading เก่าก่อนมีธงนี้
            //      ไม่มี state เก็บไว้ ต้องคงพฤติกรรมเดิม (ไม่งั้นจะ re-deliver ย้อนหลังทั้งกอง)
            if ($reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                $summaryDelivered = (bool) $reading->getConversationState('celtic_summary_delivered', true);

                if ($summaryDelivered) {
                    $q->markDelivered();
                    $skipped++;

                    continue;
                }

                $this->warn("  Q#{$q->id} (reading {$reading->id}) — completed แต่บทสรุปยังส่งไม่ออก → ส่งคำตอบรายข้อแทน");
                Log::warning('FortuneCelticRedeliver: completed แต่ celtic_summary_delivered=false → re-deliver รายข้อ', [
                    'question_id' => $q->id,
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference ?? null,
                ]);
            }

            // resolve platform + userId (pattern เดียวกับทั้งระบบ — platform field ก่อน แล้ว ID pattern)
            $platform = $reading->platform;
            if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
                $candidateId = $reading->facebook_user_id ?: $reading->platform_user_id ?: '';
                $platform = preg_match('/^U[a-f0-9]{32}$/i', $candidateId) ? 'line' : 'facebook';
            }
            $userId = $platform === 'line'
                ? (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '')
                : (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');

            if ($userId === '') {
                $this->warn("  Q#{$q->id} (reading {$reading->id}) skip — no user_id");
                $skipped++;

                continue;
            }

            // 🏷️ (2026-09-01) ติดป้ายว่าคำตอบนี้ตอบคำถามข้อไหน — เส้น cron ส่งช้ากว่าจังหวะสนทนาเสมอ
            //   (สถานการณ์เดียวกับ off-by-one ของ parked flush) เดิมส่ง response เปล่า ลูกค้าอ่านคำตอบ
            //   เรื่องเงินต่อจากคำถามเรื่องแฟน = งง — ป้ายอยู่ในข้อความเดิม ไม่กิน message object เพิ่ม
            $asked = trim((string) $q->question);
            $label = $asked !== ''
                ? '↩️ ตอบคำถาม: «'.mb_substr($asked, 0, 60).(mb_strlen($asked) > 60 ? '…' : '')."»\n\n"
                : '';

            $message = $label.trim((string) $q->response)
                ."\n\n──────────────────────\n"
                .'💬 พิมพ์คำถามต่อได้เลย — หรือพิมพ์ *"เลิกทำนายและสรุปผล"* เมื่อพร้อมค่ะ ✨';

            $this->info("  Q#{$q->id} (reading {$reading->id}, {$platform}, attempt ".($q->delivery_attempts + 1).") answered {$q->answered_at}");

            if ($isDry) {
                $this->line('    [DRY] '.mb_substr((string) $q->response, 0, 80).'...');

                continue;
            }

            // 🚫 (2026-08-26) LINE โควต้า push รายเดือนหมด → ยิงไปก็ไม่ออก
            //   ข้ามโดย "ไม่นับ attempt" — เก็บโควต้า retry ไว้ใช้ตอน push กลับมาได้จริง
            //   ของยังค้างอยู่ เดี๋ยว parked delivery ส่งคืนผ่าน reply ตอนลูกค้าทักมา (ฟรี)
            //   📌 LineFortuneWebhookController::flushParkedCelticAnswers()
            if ($platform === 'line' && \App\Services\LineGatekeeperService::isQuotaExhausted()) {
                $this->warn("  Q#{$q->id} ข้าม — โควต้า LINE หมด (รอส่งผ่าน reply ตอนลูกค้าทัก)");
                $skipped++;

                continue;
            }

            // นับ attempt ก่อนส่ง — แม้ exception ก็ถือว่าใช้ไป 1 ครั้ง (กัน loop)
            $q->forceFill(['delivery_attempts' => $q->delivery_attempts + 1])->save();

            try {
                $ok = $this->pushAnswer($platform, $userId, $message);

                if ($ok) {
                    $q->markDelivered();
                    $sent++;
                    Log::info('FortuneCelticRedeliver: re-delivered สำเร็จ', [
                        'question_id' => $q->id,
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $q->delivery_attempts,
                    ]);
                } else {
                    $failed++;
                    $this->error('    ❌ re-deliver ไม่สำเร็จ');
                    Log::warning('FortuneCelticRedeliver: re-deliver fail', [
                        'question_id' => $q->id,
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'attempt' => $q->delivery_attempts,
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("    ❌ exception: {$e->getMessage()}");
                Log::warning('FortuneCelticRedeliver: exception', [
                    'question_id' => $q->id,
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: re-delivered={$sent} skipped={$skipped} failed={$failed}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * push คำทำนายซ้ำผ่าน platform-specific service
     *
     * FB: POST_PURCHASE_UPDATE tag → ส่งได้แม้นอก 24hr window (ลูกค้าจ่ายแล้ว)
     * LINE: pushMessage (ไม่ติด reply token window)
     *
     * @return bool ส่งสำเร็จไหม
     */
    protected function pushAnswer(string $platform, string $userId, string $message): bool
    {
        if ($platform === 'facebook') {
            return app(FacebookWebhookService::class)->sendMessage(
                $userId,
                $message,
                [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]
            );
        }

        if ($platform === 'line') {
            // 🐛 (2026-08-31) ของเดิมพังสองชั้น — เส้นนี้ไม่เคยส่งอะไรถึงลูกค้า LINE ได้เลย
            //   1) ด่านเป็น `method_exists($lineService,'pushMessage')` ซึ่ง **คืน true กับ
            //      เมธอด protected ด้วย** → ผ่านด่านแล้วไปโยน Error ตอนเรียกจริงจากนอกคลาส
            //   2) ต่อให้เรียกได้ ก็ยังส่ง `$message` (string) เข้า `pushMessage(string,array)`
            //      → TypeError อีกดอก
            //   ทั้งคู่ถูกกลืนโดย `catch (\Throwable)` ในลูป → นับเป็น failed เงียบๆ
            //   ⚠️ บทเรียน: `method_exists()` ตอบแค่ "มีเมธอดนี้ไหม" ไม่ได้ตอบ "เรียกได้ไหม"
            //   ใช้ประตู public ที่เปิดไว้ให้โดยเจตนาแทน (คำตอบรายข้อ = ของที่จ่ายเงินแล้ว)
            return app(LineFortuneService::class)->pushPaidDeliverable(
                $userId,
                [['type' => 'text', 'text' => mb_substr($message, 0, 4900)]],
                true
            );
        }

        return false;
    }

    /**
     * 🐛 (2026-05-29) Recover reading ที่ค้างสถานะ CELTIC_GENERATING นานเกินไป (เชิงรุก)
     *
     * AI Celtic ตอบจริง 20-40s — ถ้าค้าง > 90s = process ตาย (FPM kill / timeout / race
     * admin ask + webhook) → status ไม่คืน → ลูกค้าพิมพ์โดน silent_skip เงียบ → admin ต้องช่วย
     *
     * cron นี้รันทุกนาที → revert เป็น AWAITING_QUESTION ให้ลูกค้าพิมพ์ถามต่อได้เองโดยไม่ต้องรอ
     * (เดิม recover เป็น lazy — เช็คตอนลูกค้าพิมพ์เท่านั้น → ถ้าลูกค้าเงียบจะค้างยาว)
     *
     * ขอบเขตปลอดภัย: เฉพาะ Celtic + ค้าง 90s–2hr (ไม่แตะที่ AI ยังตอบปกติ / ของเก่ามาก)
     */
    protected function recoverStuckGenerating(bool $isDry = false): void
    {
        try {
            $stuck = FortuneReading::query()
                ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('conversation_status', FortuneReading::STATUS_CELTIC_GENERATING)
                ->where('updated_at', '<', now()->subSeconds(90))
                ->where('updated_at', '>', now()->subHours(2))
                ->limit(50)
                ->get(['id', 'bill_reference', 'updated_at']);

            foreach ($stuck as $r) {
                $stuckSec = abs(now()->diffInSeconds($r->updated_at, false));

                if (! $isDry) {
                    FortuneReading::where('id', $r->id)
                        ->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
                }

                $this->warn("  ♻️ recover stuck GENERATING: reading {$r->id} ({$r->bill_reference}) ค้าง {$stuckSec}s → AWAITING");
                Log::warning('FortuneCelticRedeliver: recover stuck GENERATING → AWAITING', [
                    'reading_id' => $r->id,
                    'bill_reference' => $r->bill_reference,
                    'stuck_seconds' => $stuckSec,
                ]);
            }

            // 🐛 (2026-09-01) คิวกู้ต้องรวม "ใบที่เด้ง status ไปแล้วแต่ยังไม่ได้ปั่นคำตอบ" ด้วย
            //   เดิม: เด้ง status → AWAITING ให้ทุกใบ (สูงสุด 50) แต่ปั่นได้แค่ MAX_REGEN_PER_RUN
            //   รอบถัดไป query หาเฉพาะ CELTIC_GENERATING ⇒ ใบที่ 3+ ไม่เข้าเงื่อนไขอีกเลย
            //   = deploy ตัด worker หลายใบพร้อมกัน ได้คำตอบคืนแค่ 2 ใบแรก ที่เหลือหายเงียบ
            //   Fix: ใบที่เกินเพดาน/ล้ม ติดธง celtic_regen_pending='1' ไว้ แล้วรอบถัดไปหยิบมาก่อน
            $pendingIds = FortuneReading::query()
                ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)
                ->where('conversation_state->celtic_regen_pending', '1')
                ->where('updated_at', '>', now()->subDay())
                ->limit(20)
                ->pluck('id');

            // ใบค้างจากรอบก่อนมาก่อน (เก่ากว่า) แล้วค่อยใบที่เพิ่งเด้ง status รอบนี้
            $queue = $pendingIds->merge($stuck->pluck('id'))->unique()->values();

            $aiSpent = 0;
            foreach ($queue as $readingId) {
                if ($aiSpent >= self::MAX_REGEN_PER_RUN) {
                    // เพดานรอบนี้เต็ม — ติดธงให้รอบถัดไปเก็บต่อ ไม่เสียสิทธิ์
                    $this->setRegenPending((int) $readingId, true, $isDry);

                    continue;
                }

                $outcome = $this->regenerateOrphanAnswer((int) $readingId, $isDry);

                // 'generated'/'failed' = เรียก AI จริง → กินเพดานรอบทั้งคู่ (ล้มก็เสียเวลา 20-50 วิ)
                if (in_array($outcome, ['generated', 'failed'], true)) {
                    $aiSpent++;
                }

                match ($outcome) {
                    // สำเร็จ / ไม่มีอะไรให้กู้แล้ว → ปลดธง
                    'generated', 'skipped' => $this->setRegenPending((int) $readingId, false, $isDry),
                    // ล้ม → คงธงไว้ให้รอบถัดไปลองใหม่ (เพดานต่อบิลจะตัดเองใน regenerateOrphanAnswer)
                    // 🐛 (2026-09-01 จับผี #4) 'busy' ก็ต้องติดธง — ใบ fresh-stuck ที่เจอ lock ชน
                    //   (รอบก่อน crash ทั้งที่ถือ lock TTL 300s) ถูกเด้ง status ไปแล้ว ไม่ติดธง
                    //   = หลุดจากทั้งสอง queue ถาวร (idempotent — แย่สุดรอบถัดไป 'skipped' ปลดเอง)
                    'failed', 'busy' => $this->setRegenPending((int) $readingId, true, $isDry),
                    default => null,
                };
            }
        } catch (\Throwable $e) {
            Log::warning('FortuneCelticRedeliver: recoverStuckGenerating fail', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ติด/ปลดธง "ยังมีคำตอบค้างให้กู้" บน conversation_state (DB — ห้าม Cache, ของลูกค้าจ่ายเงิน)
     *
     * ใช้ค่า '1'/'0' แทน set/remove key — เพราะ JSON null ใน MySQL ไม่ใช่ SQL NULL
     * (`whereNotNull('conversation_state->x')` ยังเจอ key ที่ค่าเป็น null) query ฝั่งอ่านจึงเทียบ '1' ตรงๆ
     */
    protected function setRegenPending(int $readingId, bool $pending, bool $isDry): void
    {
        if ($isDry) {
            return;
        }

        try {
            $reading = FortuneReading::find($readingId);
            if (! $reading) {
                return;
            }

            $target = $pending ? '1' : '0';
            if ((string) $reading->getConversationState('celtic_regen_pending', '0') !== $target) {
                $reading->setConversationState('celtic_regen_pending', $target);
            }
        } catch (\Throwable $e) {
            // non-blocking — ธงเป็นแค่ตัวช่วยคิว พลาดแล้วอย่างแย่คือกู้ช้าลง 1 รอบ
            Log::warning('FortuneCelticRedeliver: setRegenPending fail', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🛟 (2026-08-31) ปั่นคำตอบที่หายไปกลับมา — ปิดรูที่ตาข่ายทุกชั้นมองไม่เห็น
     *
     * ## ทำไมต้องมี (เคสจริง reading 11920 / FTU-260831-D9958)
     * ลูกค้าจ่าย 99฿ → ให้วันเกิด → ระบบเริ่ม gen "พื้นดวง" → deploy ตัดเข้ามากลางคัน
     * AI session ตาย คำตอบไม่เคยถูกเขียน → **เงียบ 40 นาที** ไม่มีตาข่ายชั้นไหนเก็บได้เลย:
     *
     * | ตาข่าย | ต้องเจอ | ทำไมพลาด |
     * |---|---|---|
     * | redeliver (ตัวหลักไฟล์นี้) | answered แล้ว แต่ `delivered_at` ว่าง | แถวไม่เคยถูกตอบ → มองไม่เห็น |
     * | `celtic-answer-recover` เคส A | ธง `celtic_pending_q` | คำถามพื้นดวง**ระบบสังเคราะห์เอง** ไม่ผ่าน settle-buffer → ไม่มีธง |
     * | `celtic-answer-recover` เคส B | `celtic_generating` ครบ **5 นาที** | `recoverStuckGenerating()` เด้ง status ที่ **90 วิ** → **เคส B ไม่มีวันยิงได้เลย** |
     *
     * ⇒ ผูกการกู้กับ **ความจริงที่มีเสมอ** แทน: แถวใน `fortune_celtic_questions` ที่ยังไม่มีคำตอบ
     *   (มีทุกเส้นทาง ไม่ว่าลูกค้าพิมพ์เองหรือระบบสังเคราะห์)
     *
     * ## เกราะกันลูป / กันซ้ำ (ปรับ 2026-09-01)
     * - ลบแถวค้างก่อน gen — กัน sequence ชน (askQuestion จะ insert แถวใหม่ของมันเองก่อนเรียก AI)
     *   **แต่ถ้า gen ล้มแล้วไม่เหลือแถวค้างเลย ต้องคืนแถวคำถามกลับ DB** — คำถามลูกค้าห้ามหาย
     * - ตัวนับ `celtic_regen_attempts` (เพดาน MAX_REGEN_ATTEMPTS_PER_BILL) — เดิมเป็นธง one-shot
     *   ที่ตั้ง "ก่อน" รู้ผล ⇒ ล้มครั้งเดียวหมดสิทธิ์ตลอดกาล · ธงเก่า `celtic_regen_recovered_at`
     *   ยังถูกเช็คเพื่อ backward compat (บิลที่เคยกู้สำเร็จแล้ว ไม่กู้ซ้ำ)
     * - `Cache::lock` กัน cron ซ้อน · เพดาน `MAX_REGEN_PER_RUN` ต่อรอบ (AI ใช้ 20-50 วิ/ครั้ง)
     * - กู้เฉพาะแถวที่เป็น **sequence สูงสุด** — ถ้ามีข้อใหม่กว่าที่ตอบแล้ว = ลูกค้าเดินต่อไปแล้ว ไม่ต้องย้อน
     *
     * ⚠️ **จงใจไม่แตะเส้นส่ง** — gen เสร็จปล่อย `delivered_at` ว่างไว้ ให้ตัว redeliver เดิม
     *    (ที่รู้เรื่องโควตา LINE / 24hr window อยู่แล้ว) เก็บต่อในรอบถัดไป
     *    ห้าม `markDelivered()` เอง — ไฟล์นี้เคยมีบั๊ก stamp ว่าส่งแล้วทั้งที่ push ไม่ออก
     *
     * @return string 'generated' = กู้สำเร็จ · 'failed' = เรียก AI แล้วล้ม (คำถามถูกคืน DB แล้ว)
     *                · 'skipped' = ไม่มีอะไรให้กู้/หมดสิทธิ์ · 'busy' = lock ชน รอรอบถัดไป
     */
    protected function regenerateOrphanAnswer(int $readingId, bool $isDry): string
    {
        $generationSucceeded = false;

        try {
            $reading = FortuneReading::find($readingId);
            if (! $reading || ! $reading->is_paid) {
                return 'skipped';
            }

            // 🛡️ (2026-09-01 จับผี #2) ห้ามแตะใบที่ AI กำลังตอบสดอยู่ — คิว pending เข้ามา
            //   โดยไม่ผ่านเงื่อนไข "ค้าง >90s" ของ stuck query ⇒ อาจหยิบใบที่ลูกค้าเพิ่ง
            //   พิมพ์คำถามใหม่ (GENERATING สดๆ) แล้วลบแถวสดกลาง generation
            //   = คำตอบสดหายเงียบ + AI จ่ายซ้ำ 2 เท่า
            if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_GENERATING
                && $reading->updated_at
                && $reading->updated_at->gt(now()->subSeconds(90))) {
                return 'busy'; // generation สดกำลังวิ่ง — คงธง pending ไว้รอรอบถัดไป
            }

            // ธงเก่า (pre-2026-09-01) = เคยกู้สำเร็จไปแล้ว — ไม่กู้ซ้ำ
            if (! empty($reading->getConversationState('celtic_regen_recovered_at'))) {
                return 'skipped';
            }

            // เพดานต่อบิล — กันลูปเผาเงิน AI (นับทุกครั้งที่เรียก AI ไม่ว่าผลเป็นอะไร)
            $attempts = (int) $reading->getConversationState('celtic_regen_attempts', 0);
            if ($attempts >= self::MAX_REGEN_ATTEMPTS_PER_BILL) {
                return 'skipped';
            }

            // 🛡️ (2026-09-01 จับผี #2) กู้เฉพาะแถวที่ "เก่าจริง" (>90s) — แถวสดคือคำถามที่
            //   flow ปกติกำลังจัดการอยู่ ห้ามลบแข่ง
            $orphan = FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->whereNull('answered_at')
                ->where('created_at', '<', now()->subSeconds(90))
                ->orderByDesc('sequence')
                ->first();

            if (! $orphan || trim((string) $orphan->question) === '') {
                return 'skipped'; // ไม่มีคำถามค้าง = ไม่มีอะไรให้กู้
            }

            // ถ้ามีข้อที่ตอบแล้ว "ใหม่กว่า" แถวค้าง = ลูกค้าเดินหน้าไปแล้ว ไม่ต้องย้อนกลับไปตอบของเก่า
            $maxAnswered = (int) FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->whereNotNull('answered_at')
                ->max('sequence');
            if ($maxAnswered > (int) $orphan->sequence) {
                return 'skipped';
            }

            if (count($reading->getCelticCards()) < 10 || ! $reading->canAskMoreCeltic()) {
                return 'skipped'; // ไพ่ไม่ครบ / หมดเวลาคุย → ปล่อยให้ flow ปกติจัดการ
            }

            $question = trim((string) $orphan->question);
            $preview = mb_substr($question, 0, 60);

            if ($isDry) {
                $this->warn("  🛟 [DRY] จะปั่นคำตอบใหม่ให้ reading {$reading->id} (seq {$orphan->sequence}): {$preview}");

                return 'skipped';
            }

            $lock = Cache::lock("celtic:regen:{$reading->id}", 300);
            if (! $lock->get()) {
                return 'busy'; // รอบก่อนยังทำอยู่ — คงธง pending ไว้ให้รอบถัดไป
            }

            try {
                // นับ attempt ก่อนเรียก AI — process ตายกลางทางก็ยังนับ (crash-safe)
                $reading->setConversationState('celtic_regen_attempts', $attempts + 1);

                // ลบแถวค้างก่อน gen — กัน sequence ชนกับแถวใหม่ที่ askQuestion จะ insert เอง
                FortuneCelticQuestion::where('id', $orphan->id)->delete();

                $result = app(CelticCrossService::class)->askQuestion($reading->fresh(), $question);

                $ok = ! empty($result['success']);
                $this->warn(sprintf(
                    '  🛟 ปั่นคำตอบใหม่ reading %d: %s (%d ตัวอักษร, attempt %d/%d)',
                    $reading->id,
                    $ok ? 'สำเร็จ' : 'ไม่สำเร็จ',
                    mb_strlen((string) ($result['response'] ?? '')),
                    $attempts + 1,
                    self::MAX_REGEN_ATTEMPTS_PER_BILL
                ));

                Log::warning('FortuneCelticRedeliver: ปั่นคำตอบที่หายกลับมา', [
                    'reading_id' => $reading->id,
                    'sequence' => $result['sequence'] ?? null,
                    'success' => $ok,
                    'attempt' => $attempts + 1,
                    'response_len' => mb_strlen((string) ($result['response'] ?? '')),
                    'q_preview' => $preview,
                ]);

                if ($ok) {
                    $generationSucceeded = true;

                    // 🐛 (2026-09-01 จับผี #2) ต้อง refresh ก่อน stamp — askQuestion แก้ state
                    //   บนอินสแตนซ์ fresh (กินธง celtic_base_chart ฯลฯ) ถ้า stamp บนอินสแตนซ์เก่า
                    //   = เขียน state ทั้งก้อนทับย้อนหลัง → ธง base_chart เด้งกลับ ลูกค้าได้พื้นดวงซ้ำ
                    $reading->refresh();

                    // ธง "กู้สำเร็จแล้ว" — คงชื่อเดิมไว้เพื่อ backward compat กับบิลเก่า
                    $reading->setConversationState('celtic_regen_recovered_at', now()->toIso8601String());

                    return 'generated';
                }

                // 🛟 gen ล้ม — askQuestion ปกติ insert แถวคำถามก่อนเรียก AI (ล้มหลัง insert = แถวยังอยู่)
                //   แต่ถ้าล้ม "ก่อน" insert (lock ชน / หมดเวลา race / exception ระหว่างทาง)
                //   แถวที่เราลบไปจะหายจาก DB ถาวร → ต้องคืนคำถามกลับ ห้ามให้ของลูกค้าหาย
                $this->restoreOrphanIfLost($reading->id, $question);

                return 'failed';
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::error('FortuneCelticRedeliver: regenerateOrphanAnswer fail', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);

            // exception หลังลบแถว = ความเสี่ยงคำถามหาย — พยายามคืนแบบ best-effort
            // 🛡️ (2026-09-01 จับผี #3) ยกเว้นกรณี gen สำเร็จไปแล้ว (exception เกิดตอน stamp)
            //   — restore ตอนนั้น = ชุบคำถามที่ตอบเสร็จแล้วกลับมาเป็น orphan ให้ตอบซ้ำ
            if (isset($question) && isset($reading) && ! $generationSucceeded) {
                $this->restoreOrphanIfLost($reading->id, $question);

                return 'failed';
            }

            return $generationSucceeded ? 'generated' : 'skipped';
        }
    }

    /**
     * คืนแถวคำถามที่ถูกลบไประหว่างกู้ ถ้า DB ไม่เหลือแถวค้างเลย (คำถามลูกค้าห้ามหายถาวร)
     *
     * ใช้ sequence = MAX(ทุกแถว)+1 แบบเดียวกับ createQuestionRecordSafely — ชน unique ก็ลองขยับ 1 ครั้ง
     */
    protected function restoreOrphanIfLost(int $readingId, string $question): void
    {
        try {
            $stillHasOrphan = FortuneCelticQuestion::where('fortune_reading_id', $readingId)
                ->whereNull('answered_at')
                ->exists();

            if ($stillHasOrphan) {
                return; // askQuestion insert แถวใหม่ไว้แล้ว — คำถามไม่หาย
            }

            for ($try = 0; $try < 2; $try++) {
                try {
                    $seq = (int) FortuneCelticQuestion::where('fortune_reading_id', $readingId)->max('sequence') + 1;
                    FortuneCelticQuestion::create([
                        'fortune_reading_id' => $readingId,
                        'sequence' => max(1, $seq),
                        'question' => mb_substr($question, 0, 1000),
                    ]);

                    Log::warning('FortuneCelticRedeliver: คืนแถวคำถามที่ลบไปหลัง gen ล้ม', [
                        'reading_id' => $readingId,
                        'sequence' => max(1, $seq),
                    ]);

                    return;
                } catch (\Illuminate\Database\QueryException $e) {
                    // ชน unique → วนขยับเลขอีกครั้งเดียว (มีเส้นอื่น insert แทรก = คำถามไม่หายอยู่แล้ว)
                    if ($try === 1) {
                        throw $e;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('FortuneCelticRedeliver: restoreOrphanIfLost fail — คำถามอาจหายจาก DB', [
                'reading_id' => $readingId,
                'q_preview' => mb_substr($question, 0, 60),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
