<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\LineAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🚨 (2026-05-19 Batch 2) Expire stuck paid readings — Last-resort safety net
 *
 * เป้าหมาย: หาบิลที่ "จ่ายแล้วเกิน 24 ชม. แต่ยังไม่ได้คำทำนายเสร็จสมบูรณ์"
 *           แล้วโยนเข้าคิว admin manual review + alert admin ผ่าน LINE
 *
 * ทำไมต้องมี command นี้:
 *   - fortune:check-pending retry สูงสุด 5 ครั้ง — fail หมด → reading ค้างใน DB
 *   - fortune:celtic-recover auto-recover — ถ้า re-push fail หลายครั้งจะ skip
 *   - หลัง 24 ชม. = ระบบยอมแพ้ → ต้อง human intervention เท่านั้น
 *   - เดิมไม่มี alert → admin ต้อง grep log เอง = ไม่ทันการ ลูกค้าด่า
 *
 * เคสที่จัดการ:
 *   1. Deep 39฿: is_paid + (STATUS_PAID หรือ COMPLETED+no deep_response) + paid_at < now-24hr
 *   2. Celtic 99฿: is_paid + reading_type=celtic + celtic_picked_count=0 + paid_at < now-24hr
 *
 * Action ต่อ reading:
 *   1. Set conversation_state.admin_review_needed = true
 *   2. Set conversation_state.admin_review_at = now (timestamp)
 *   3. Set conversation_state.admin_review_alerted = true (idempotent guard)
 *   4. สร้าง FortuneTakeoverLog entry (audit trail)
 *   5. Alert admin ผ่าน LineAlertService — ครั้งเดียวต่อบิล
 *   6. Push คำขอโทษ + ปุ่ม "คุยกับแม่หมอ" ให้ลูกค้า (best-effort, FB 24hr อาจหมด)
 *
 * Schedule: ทุก 6 ชั่วโมง (ไม่บ่อยเกิน — มี check-pending รับงาน 0-24 ชม. แล้ว)
 *
 * Usage:
 *   php artisan fortune:expire-stuck-paid          # รันจริง
 *   php artisan fortune:expire-stuck-paid --dry    # dry run แสดงรายการ
 *   php artisan fortune:expire-stuck-paid --hours=48 # ปรับ threshold
 */
class FortuneExpireStuckPaid extends Command
{
    protected $signature = 'fortune:expire-stuck-paid
                            {--dry : Dry run — แสดงรายการ แต่ไม่ alert/push จริง}
                            {--hours=24 : threshold ที่ถือว่าเกินเยียวยา (default 24 ชม.)}
                            {--max-age-days=30 : อายุบิลเก่าสุดที่กวาด — กัน backlog ประวัติศาสตร์ท่วมคิวแอดมิน}
                            {--limit=50 : จำนวนสูงสุดที่ process ต่อรอบ}';

    protected $description = 'Mark paid+incomplete readings เกิน 24 ชม. ว่า admin_review_needed + alert admin (last-resort safety net)';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry');
        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, (int) $this->option('limit'));

        $cutoff = now()->subHours($hours);

        // 🛡️ (2026-09-01 จับผี #3) floor อายุบิล — ตาข่ายเพิ่งขยายครอบ collecting_*/picking 1-9
        //   ถ้าไม่มี floor: รันแรกกวาดบิลค้างย้อนหลังทั้งประวัติศาสตร์ แล้ว admin_review_alerted
        //   จะไปปลด guard "บิลจ่ายแล้วต้อง resume" (hasPaidActiveReading/isInPrediction) ของบิลเก่า
        //   ทั้งก้อนแบบลูกค้าไม่รู้ตัว — จำกัดที่ 30 วันล่าสุดพอ (เก่ากว่านั้น = จบไปแล้วโดยพฤตินัย)
        $maxAgeDays = max(2, (int) $this->option('max-age-days'));
        $oldestPaidAt = now()->subDays($maxAgeDays);

        // ─────────────────────────────────────────────────────────────────
        // Phase 1: Deep 39฿ — paid + (PAID หรือ COMPLETED+no deep_response)
        // ─────────────────────────────────────────────────────────────────
        try {
            $deepStuck = FortuneReading::query()
                ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
                ->where('is_paid', true)
                ->where(function ($q) {
                    $q->where('conversation_status', FortuneReading::STATUS_PAID)
                        // 🕳️ (2026-09-01) ปิดรูตาข่าย: บิลจ่ายแล้วที่ค้างสถานะเก็บข้อมูล
                        //   (collecting_birthdate/questions/tarot เช่น จ่ายแล้วไม่เคยบอกวันเกิด)
                        //   เดิมไม่มี cron ตัวไหน alert เลย (fortune:recover-paid-no-birthdate
                        //   ถูกปิดใน scheduler โดยเจตนา = เครื่องมือ manual) → เพิ่มเข้าตาข่าย
                        //   last-resort นี้: alert แอดมินอย่างเดียว ไม่ auto-action
                        ->orWhereIn('conversation_status', FortuneReading::DEEP_ACTIVE_STATUSES)
                        ->orWhere(function ($q2) {
                            $q2->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                                ->where(function ($q3) {
                                    $q3->whereNull('deep_response')
                                        ->orWhere('deep_response', '');
                                });
                        });
                })
                ->whereNotNull('paid_at')
                ->where('paid_at', '<=', $cutoff)
                ->where('paid_at', '>=', $oldestPaidAt)
                // กัน duplicate alert: ถ้าเคย alert แล้ว → skip (รอ admin จัดการ)
                ->where(function ($q) {
                    $q->whereNull('conversation_state')
                        ->orWhere(function ($q2) {
                            $q2->whereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') IS NULL")
                                ->orWhereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') != true");
                        });
                })
                ->orderBy('paid_at', 'asc')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            $this->error('Deep query ล้มเหลว: '.$e->getMessage());
            Log::error('Fortune Expire Stuck Paid: Deep query failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        // ─────────────────────────────────────────────────────────────────
        // Phase 2: Celtic 99฿ — paid + ยังไม่เปิดไพ่ + เกินเวลา
        // ─────────────────────────────────────────────────────────────────
        try {
            $celticStuck = FortuneReading::query()
                ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)
                // 🩹 (2026-09-01) ตัด 'cancelled'/'expired' ทิ้ง — status พวกนี้ไม่มีจริงในระบบ
                //   (บิลยกเลิก = COMPLETED + is_paid=0) ใส่ไว้ = หลอกคนอ่านว่ามี
                ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
                ->whereNotNull('paid_at')
                ->where('paid_at', '<=', $cutoff)
                ->where('paid_at', '>=', $oldestPaidAt)
                ->where(function ($q) {
                    $q->whereNull('conversation_state')
                        ->orWhere(function ($q2) {
                            $q2->whereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') IS NULL")
                                ->orWhereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') != true");
                        });
                })
                ->orderBy('paid_at', 'asc')
                ->limit($limit)
                ->get();
            // 🕳️ (2026-09-01) เลิกกรอง picked=0 — ลูกค้าที่เปิดไพ่ค้าง 1-9 ใบแล้วหาย >24 ชม.
            //   เดิมหลุดจากตาข่ายทุกตัว (reminder จับเฉพาะช่วง 30m-6h / finalize จับเฉพาะ AWAITING)
            //   ตาข่าย last-resort นี้ต้องเห็น "ทุกบิล Celtic จ่ายแล้วที่ไม่ COMPLETED" — alert อย่างเดียว
        } catch (\Throwable $e) {
            $this->error('Celtic query ล้มเหลว: '.$e->getMessage());
            Log::error('Fortune Expire Stuck Paid: Celtic query failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $totalCount = $deepStuck->count() + $celticStuck->count();

        if ($totalCount === 0) {
            $this->info('✅ ไม่มีบิลค้างเกิน '.$hours.' ชม. ที่ต้อง escalate');

            return self::SUCCESS;
        }

        $this->warn("🚨 พบบิลค้างเกิน {$hours} ชม.: Deep {$deepStuck->count()} | Celtic {$celticStuck->count()}");

        if ($isDry) {
            $this->table(
                ['Type', 'ID', 'Bill Ref', 'User', 'Status', 'Paid (hours ago)'],
                $deepStuck->merge($celticStuck)->map(fn ($r) => [
                    $r->reading_type ?? '-',
                    $r->id,
                    $r->bill_reference ?? '-',
                    $r->facebook_user_name ?? '-',
                    $r->conversation_status ?? '-',
                    $r->paid_at ? round($r->paid_at->diffInHours(now()), 1) : '?',
                ])->toArray()
            );

            $this->warn('Dry run — ไม่ได้ alert/push จริง');

            return self::SUCCESS;
        }

        $alerted = 0;
        $failed = 0;
        $processedReadingIds = []; // 🩹 (Batch 8) Track readings ที่ process success → set alerted flag *หลัง* alert ส่งสำเร็จ

        // 🛡️ Safe service init — ถ้า settings/services โหลดไม่ได้ ก็ไม่ block
        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);
            $alertService = app(LineAlertService::class);
        } catch (\Throwable $e) {
            $this->error('Service init ล้มเหลว: '.$e->getMessage());
            Log::error('Fortune Expire Stuck Paid: service init failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        foreach ($deepStuck->merge($celticStuck) as $reading) {
            try {
                $this->processOne($reading, $channelManager, $alertService);
                $alerted++;
                $processedReadingIds[] = $reading->id; // 🩹 (Batch 8) collect ตัวที่ process ผ่าน
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} ล้มเหลว: {$e->getMessage()}");
                $failed++;
                Log::error('Fortune Expire Stuck Paid: process ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // Phase 3: Aggregate admin alert (สรุปรวมรอบเดียว แทนการ alert ทีละบิล)
        //
        // 🩹 (Batch 8 hotfix BUG 1) Race condition fix:
        //   เดิม: processOne ตั้ง admin_review_alerted=true ก่อน → ถ้า alert throw = silent loss
        //         (idempotent guard ใน query จะ skip รอบถัดไป → admin ไม่มีทางรู้)
        //   ใหม่: ตั้ง flag *หลัง* alert success — ถ้า alert fail = ไม่ตั้ง flag → next cron retry alert
        // ─────────────────────────────────────────────────────────────────
        $alertSent = false;
        if ($alerted > 0) {
            try {
                $alertService->alertSystemError(
                    'มีบิลค้างเกิน '.$hours.' ชม. ต้อง admin recover ด่วน',
                    [
                        'total' => $alerted,
                        'deep_count' => $deepStuck->count(),
                        'celtic_count' => $celticStuck->count(),
                        'threshold_hours' => $hours,
                        'admin_action' => 'ไปที่ /admin/fortune/billing → filter admin_review_needed → retry/refund/manual recover',
                    ]
                );
                $alertSent = true;
            } catch (\Throwable $alertErr) {
                Log::warning('Fortune Expire Stuck Paid: aggregate LINE alert ล้มเหลว — flag ยังไม่ตั้ง รอบหน้า retry', [
                    'error' => $alertErr->getMessage(),
                    'pending_reading_ids' => $processedReadingIds,
                ]);
            }
        }

        // 🩹 (Batch 8) Set admin_review_alerted=true เฉพาะเมื่อ alert success
        //   ถ้า alert fail → flag ไม่ set → next cron round เจอ reading เดิม → retry alert
        //   guard ไม่ duplicate apology: processOne ใช้ admin_review_apology_sent (set ในขั้น push)
        if ($alertSent && ! empty($processedReadingIds)) {
            try {
                FortuneReading::whereIn('id', $processedReadingIds)
                    ->get()
                    ->each(function ($reading) {
                        $reading->setConversationState('admin_review_alerted', true);
                        $reading->setConversationState('admin_review_alerted_at', now()->toIso8601String());
                    });
                Log::info('Fortune Expire Stuck Paid: marked admin_review_alerted หลัง alert success', [
                    'count' => count($processedReadingIds),
                ]);
            } catch (\Throwable $flagErr) {
                Log::warning('Fortune Expire Stuck Paid: ตั้ง admin_review_alerted หลัง alert ล้ม', [
                    'error' => $flagErr->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: alerted {$alerted} | failed {$failed}".($alertSent ? ' | LINE alert ✅' : ' | LINE alert ⚠️ จะ retry รอบหน้า'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * จัดการ reading ตัวเดียว — set flags + log + push คำขอโทษให้ลูกค้า
     */
    protected function processOne(
        FortuneReading $reading,
        FortuneChannelManager $channelManager,
        LineAlertService $alertService
    ): void {
        $billRef = $reading->bill_reference ?? "#{$reading->id}";
        $waitedHours = $reading->paid_at ? round($reading->paid_at->diffInHours(now()), 1) : 0;

        // 1. Set conversation_state flags
        //   🩹 (Batch 8 hotfix BUG 1) admin_review_alerted ย้ายไปตั้งหลัง alert success ใน Phase 3
        //      เพื่อป้องกัน silent failure: ถ้า aggregate alert throw → flag ไม่ set → cron retry รอบถัดไป
        $reading->setConversationState('admin_review_needed', true);
        $reading->setConversationState('admin_review_at', now()->toIso8601String());
        $reading->setConversationState('admin_review_reason', 'stuck_paid_over_24hr');

        // 2. สร้าง FortuneTakeoverLog (audit trail)
        try {
            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => null,
                'action' => FortuneTakeoverLog::ACTION_MESSAGE,
                'reason' => 'stuck_paid_over_24hr',
                'message' => "บิลค้างเกิน 24 ชม. — Deep/Celtic ไม่เสร็จสมบูรณ์ ต้อง admin recover (รอ {$waitedHours} ชม.)",
                'platform' => $reading->platform,
                'metadata' => [
                    'reading_type' => $reading->reading_type,
                    'conversation_status' => $reading->conversation_status,
                    'bill_reference' => $billRef,
                    'paid_at' => $reading->paid_at?->toIso8601String(),
                    'waited_hours' => $waitedHours,
                    'requires_admin_action' => true,
                ],
            ]);
        } catch (\Throwable $logErr) {
            Log::warning('Fortune Expire Stuck Paid: บันทึก takeover log ล้มเหลว (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $logErr->getMessage(),
            ]);
        }

        // 3. Push คำขอโทษให้ลูกค้า + เสนอ handoff (best-effort)
        // 🛑 (2026-05-22 #5) DISABLED per user request — "ปิดระบบตามคนที่ยังไม่ได้รับคำทำนาย"
        //   เหตุผล: apology DM "คำทำนายของคุณจะมาแน่นอน" → admin ดูแลไม่ทัน → ลูกค้าด่ามากกว่าเดิม
        //   ลูกค้า paid + เกิน 24 ชม. = admin alert via LINE + flag admin_review_needed
        //   admin จัดการ manual ผ่าน /admin/fortune/billing filter=admin_review_needed
        $this->info("  ✅ #{$reading->id} ({$billRef}) — flag set (apology DM disabled, admin LINE alert ยังเปิด)");

        Log::info('Fortune Expire Stuck Paid: marked admin_review_needed', [
            'reading_id' => $reading->id,
            'reading_type' => $reading->reading_type,
            'bill_reference' => $billRef,
            'waited_hours' => $waitedHours,
            // 🩹 (2026-09-01) เดิมอ้าง $platform ที่ไม่มีในเมธอดนี้ (undefined variable warning)
            'platform' => $reading->platform,
        ]);
    }
}
