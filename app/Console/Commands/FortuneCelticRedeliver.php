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
     * เพดานการปั่นคำตอบใหม่ต่อการรัน 1 รอบ
     *
     * AI ใช้ 20-50 วิ/ครั้ง และ cron นี้รันทุกนาที (`withoutOverlapping(5)`)
     * ⇒ ตั้งต่ำไว้ กันรอบเดียวลากยาวจนทับรอบถัดไป
     */
    private const MAX_REGEN_PER_RUN = 2;

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

            $message = trim((string) $q->response)
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

            $regenerated = 0;

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

                // 🛟 (2026-08-31) คืนสถานะอย่างเดียว "ไม่พอ" — ต้องปั่นคำตอบที่หายไปกลับมาด้วย
                if ($regenerated < self::MAX_REGEN_PER_RUN && $this->regenerateOrphanAnswer($r->id, $isDry)) {
                    $regenerated++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('FortuneCelticRedeliver: recoverStuckGenerating fail', ['error' => $e->getMessage()]);
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
     * ## เกราะกันลูป / กันซ้ำ
     * - ลบแถวค้างก่อน gen — ไม่งั้น cron รอบหน้าเห็นแถวเดิมแล้วปั่นซ้ำไม่รู้จบ
     * - ธง `celtic_regen_recovered_at` — กู้อัตโนมัติได้ **ครั้งเดียวต่อบิล**
     * - `Cache::lock` กัน cron ซ้อน · เพดาน `MAX_REGEN_PER_RUN` ต่อรอบ (AI ใช้ 20-50 วิ/ครั้ง)
     * - กู้เฉพาะแถวที่เป็น **sequence สูงสุด** — ถ้ามีข้อใหม่กว่าที่ตอบแล้ว = ลูกค้าเดินต่อไปแล้ว ไม่ต้องย้อน
     *
     * ⚠️ **จงใจไม่แตะเส้นส่ง** — gen เสร็จปล่อย `delivered_at` ว่างไว้ ให้ตัว redeliver เดิม
     *    (ที่รู้เรื่องโควตา LINE / 24hr window อยู่แล้ว) เก็บต่อในรอบถัดไป
     *    ห้าม `markDelivered()` เอง — ไฟล์นี้เคยมีบั๊ก stamp ว่าส่งแล้วทั้งที่ push ไม่ออก
     *
     * @return bool true ถ้าปั่นคำตอบใหม่สำเร็จ
     */
    protected function regenerateOrphanAnswer(int $readingId, bool $isDry): bool
    {
        try {
            $reading = FortuneReading::find($readingId);
            if (! $reading || ! $reading->is_paid) {
                return false;
            }

            // กู้อัตโนมัติได้ครั้งเดียวต่อบิล — กันลูปและกันคิดเงิน AI ซ้ำ
            if (! empty($reading->getConversationState('celtic_regen_recovered_at'))) {
                return false;
            }

            $orphan = FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->whereNull('answered_at')
                ->orderByDesc('sequence')
                ->first();

            if (! $orphan || trim((string) $orphan->question) === '') {
                return false; // ไม่มีคำถามค้าง = ไม่มีอะไรให้กู้
            }

            // ถ้ามีข้อที่ตอบแล้ว "ใหม่กว่า" แถวค้าง = ลูกค้าเดินหน้าไปแล้ว ไม่ต้องย้อนกลับไปตอบของเก่า
            $maxAnswered = (int) FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->whereNotNull('answered_at')
                ->max('sequence');
            if ($maxAnswered > (int) $orphan->sequence) {
                return false;
            }

            if (count($reading->getCelticCards()) < 10 || ! $reading->canAskMoreCeltic()) {
                return false; // ไพ่ไม่ครบ / หมดเวลาคุย → ปล่อยให้ flow ปกติจัดการ
            }

            $question = trim((string) $orphan->question);
            $preview = mb_substr($question, 0, 60);

            if ($isDry) {
                $this->warn("  🛟 [DRY] จะปั่นคำตอบใหม่ให้ reading {$reading->id} (seq {$orphan->sequence}): {$preview}");

                return false;
            }

            $lock = Cache::lock("celtic:regen:{$reading->id}", 300);
            if (! $lock->get()) {
                return false; // รอบก่อนยังทำอยู่
            }

            try {
                // ต้องลบแถวค้างก่อน gen — กัน sequence ชน และกัน cron รอบหน้าปั่นซ้ำ
                FortuneCelticQuestion::where('id', $orphan->id)->delete();
                $reading->setConversationState('celtic_regen_recovered_at', now()->toIso8601String());

                $result = app(CelticCrossService::class)->askQuestion($reading->fresh(), $question);

                $ok = ! empty($result['success']);
                $this->warn(sprintf(
                    '  🛟 ปั่นคำตอบใหม่ reading %d: %s (%d ตัวอักษร)',
                    $reading->id,
                    $ok ? 'สำเร็จ' : 'ไม่สำเร็จ',
                    mb_strlen((string) ($result['response'] ?? ''))
                ));

                Log::warning('FortuneCelticRedeliver: ปั่นคำตอบที่หายกลับมา', [
                    'reading_id' => $reading->id,
                    'sequence' => $result['sequence'] ?? null,
                    'success' => $ok,
                    'response_len' => mb_strlen((string) ($result['response'] ?? '')),
                    'q_preview' => $preview,
                ]);

                return $ok;
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::error('FortuneCelticRedeliver: regenerateOrphanAnswer fail', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
