<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ตรวจสอบบิลดูดวงที่ชำระเงินแล้วแต่ยังไม่ได้รับคำทำนาย
 *
 * ทำงานเป็น safety net สำหรับกรณี:
 * 1. ProcessDeepFortuneReadingJob ล้มเหลว/ถูกฆ่าโดย process timeout
 * 2. Queue worker ไม่ทำงาน / queue stuck
 * 3. proc_open() background process ไม่ start
 * 4. คนดูดวงพร้อมกันเยอะ ทำให้ job ค้างใน queue
 *
 * Command นี้:
 * - เช็คบิลที่ is_paid=true แต่ยังไม่มี deep_response
 * - เฉพาะที่ชำระเงินมาแล้ว 2-30 นาที (ให้เวลา job ทำงานปกติก่อน)
 * - Dispatch ProcessDeepFortuneReadingJob ใหม่ให้อัตโนมัติ
 * - ป้องกัน duplicate ด้วย conversation_status check
 *
 * Schedule: ทุก 1 นาที (everyMinute)
 */
class FortuneCheckPendingReadings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fortune:check-pending
                            {--dry-run : แสดงผลอย่างเดียว ไม่ dispatch job}
                            {--force : บังคับ retry ทั้งหมด ไม่ว่าจะผ่านมานานแค่ไหน}';

    /**
     * The console command description.
     */
    protected $description = 'ตรวจสอบบิลดูดวงที่ชำระเงินแล้วแต่ยังไม่ได้คำทำนาย → retry อัตโนมัติ';

    /**
     * เวลาขั้นต่ำหลังชำระเงินก่อนจะ retry (นาที)
     * ให้เวลา job ทำงานปกติก่อน ไม่ dispatch ซ้ำซ้อน
     */
    protected const MIN_WAIT_MINUTES = 2;

    /**
     * เวลาสูงสุดที่จะ retry (นาที)
     * ขยายเป็น 24 ชั่วโมง เพื่อรองรับกรณี AI ล่มนาน
     * ลูกค้าจ่ายเงินแล้วต้องได้รับคำทำนายเสมอ
     */
    protected const MAX_WAIT_MINUTES = 1440; // 24 ชั่วโมง

    /**
     * จำนวน retry สูงสุดต่อ reading ใน command นี้
     * เพิ่มเป็น 5 ครั้ง เพราะ AI อาจล่มหลายชั่วโมง
     */
    protected const MAX_AUTO_RETRIES = 5;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('🔮 ตรวจสอบบิลดูดวงที่รอคำทำนาย...');

        // ค้นหาบิลที่ชำระเงินแล้วแต่ยังไม่มี deep_response
        $query = FortuneReading::where('is_paid', true)
            ->where('reading_type', 'deep')
            ->whereNull('deep_response')
            ->where(function ($q) {
                // สถานะ paid (รอ processing) หรือ completed (job failed + safety net เปลี่ยนแล้ว)
                $q->where('conversation_status', FortuneReading::STATUS_PAID)
                    ->orWhere(function ($sub) {
                        // completed แต่ยังไม่มี deep_response = job ล้มเหลว
                        $sub->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                            ->whereNull('deep_response');
                    });
            })
            ->whereNotNull('paid_at');

        // ไม่ force → กรองเฉพาะ 2-30 นาทีหลังชำระ
        if (! $isForce) {
            $query->where('paid_at', '<=', now()->subMinutes(self::MIN_WAIT_MINUTES))
                ->where('paid_at', '>=', now()->subMinutes(self::MAX_WAIT_MINUTES));
        }

        $pendingReadings = $query->orderBy('paid_at', 'asc')->get();

        if ($pendingReadings->isEmpty()) {
            $this->info('✅ Phase 1: ไม่มีบิลที่รอสร้างคำทำนาย');
        } else {
            $this->info("📋 Phase 1: พบ {$pendingReadings->count()} บิลที่รอสร้างคำทำนาย");
        }

        $dispatched = 0;
        $skipped = 0;

        foreach ($pendingReadings as $reading) {
            $waitMinutes = (int) $reading->paid_at->diffInMinutes(now());
            $billRef = $reading->bill_reference ?? "#{$reading->id}";
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

            // 🔒 (2026-06-10) มี process กำลัง generate/ส่งอยู่ → ข้ามรอบนี้ (กัน dispatch ซ้อน)
            //   เคสจริง (R5604): Pay-First ลูกค้าให้วันเกิด "หลัง" จ่าย → paid_at เก่ากว่า
            //   ตอนเริ่ม generate จริงได้หลายนาที → MIN_WAIT จาก paid_at คิดว่า job ค้าง
            //   → dispatch ซ้อนระหว่าง AI กำลัง gen → รัน 3 รอบขนาน ส่งซ้ำ 3 ชุด + AI cost ×3
            if (\Illuminate\Support\Facades\Cache::has("fortune:deep_gen:{$reading->id}")
                || \Illuminate\Support\Facades\Cache::has("fortune:deep_deliver:{$reading->id}")) {
                $this->info("  ⏳ {$billRef} — มี process กำลัง generate/ส่งอยู่ (ข้ามรอบนี้)");
                $skipped++;

                continue;
            }

            // ตรวจสอบ retry count จาก conversation_state
            $retryCount = $reading->getConversationState('auto_retry_count', 0);
            if ($retryCount >= self::MAX_AUTO_RETRIES && ! $isForce) {
                $this->warn("  ⏭  {$billRef} — retry ครบ {$retryCount} ครั้งแล้ว (ข้าม) ต้องให้แอดมิน retry");
                $skipped++;

                // ✅ แจ้งลูกค้าว่าระบบมีปัญหา (ส่งครั้งเดียว)
                if (! $reading->getConversationState('failure_notified', false) && ! empty($userId) && ! $isDryRun) {
                    try {
                        $settings = FortuneTellingSetting::getSettings();
                        $channelManager = new FortuneChannelManager($settings);
                        $name = $reading->facebook_user_name ?? 'คุณ';

                        // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอ/ระบบขัดข้อง" — โทนนุ่ม
                        $failMessage = "🔮 ขออภัยค่ะ คุณ{$name}\n\n"
                            ."คำทำนายของคุณกำลังใช้เวลามากกว่าปกติเล็กน้อย\n"
                            ."แอดมินกำลังดูแลให้เร็วที่สุดค่ะ\n\n"
                            ."💬 พิมพ์ 'ดูคำทำนาย' เพื่อตรวจสอบสถานะได้ตลอดนะคะ 🙏";

                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'error',
                            'message' => $failMessage,
                        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                        $reading->setConversationState('failure_notified', true);
                        $reading->setConversationState('failure_notified_at', now()->toIso8601String());

                        $this->info("  📨 {$billRef} — ส่งข้อความแจ้งปัญหาให้ลูกค้าแล้ว");
                    } catch (\Exception $notifyErr) {
                        Log::warning('fortune:check-pending: ส่ง failure notification ล้มเหลว', [
                            'reading_id' => $reading->id,
                            'error' => $notifyErr->getMessage(),
                        ]);
                    }
                }

                continue;
            }

            // ตรวจสอบว่ามี user ID สำหรับส่งข้อความหรือไม่
            if (empty($userId)) {
                $this->warn("  ⏭  {$billRef} — ไม่มี User ID (ข้าม)");
                $skipped++;

                continue;
            }

            // ตรวจสอบว่ามีคำถามสำหรับทำนายหรือไม่
            $hasQuestions = ! empty($reading->getCollectedQuestions())
                || ! empty($reading->questions);
            if (! $hasQuestions) {
                $this->warn("  ⏭  {$billRef} — ไม่มีคำถาม (ข้าม)");
                $skipped++;

                continue;
            }

            $this->info("  🔄 {$billRef} — รอ {$waitMinutes} นาที, retry #{$retryCount} → dispatch job");

            // ✅ ส่งข้อความ "คนใช้งานมาก" ถ้ารอ >10 นาที + ยังไม่เคยส่ง
            // (ให้เวลา AI retry สัก 2-3 รอบก่อนแจ้งลูกค้า)
            if ($waitMinutes >= 10 && ! $reading->getConversationState('busy_message_sent', false) && ! $isDryRun) {
                try {
                    $settings = FortuneTellingSetting::getSettings();
                    $channelManager = new FortuneChannelManager($settings);

                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $busyMessage = "🔮 เนื่องจากตอนนี้มีคนใช้งานมาก จันทรากำลังพยายามตรวจดวงชะตาให้อยู่ค่ะ\n\n"
                        ."กรุณารอสักครู่นะคะ {$name} 🙏";

                    $channelManager->sendResponse($platform, $userId, [
                        'action' => 'busy_processing',
                        'message' => $busyMessage,
                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                    $reading->setConversationState('busy_message_sent', true);
                    $reading->setConversationState('busy_message_sent_at', now()->toIso8601String());

                    $this->info("  📨 {$billRef} — ส่งข้อความ 'คนใช้งานมาก' สำเร็จ");
                    Log::info('fortune:check-pending: ส่งข้อความ busy message', [
                        'reading_id' => $reading->id,
                        'wait_minutes' => $waitMinutes,
                    ]);
                } catch (\Exception $msgErr) {
                    Log::warning('fortune:check-pending: ส่ง busy message ล้มเหลว', [
                        'reading_id' => $reading->id,
                        'error' => $msgErr->getMessage(),
                    ]);
                }
            }

            if (! $isDryRun) {
                try {
                    // อัพเดท retry count
                    $reading->setConversationState('auto_retry_count', $retryCount + 1);
                    $reading->setConversationState('last_auto_retry_at', now()->toIso8601String());

                    // เปลี่ยนสถานะกลับเป็น paid เพื่อให้ job ทำงานได้
                    if ($reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                        $reading->update(['conversation_status' => FortuneReading::STATUS_PAID]);
                    }

                    // Dispatch job
                    ProcessDeepFortuneReadingJob::dispatchSmart(
                        $reading->id, null, $platform, $userId
                    );

                    Log::info('fortune:check-pending: dispatch retry job', [
                        'reading_id' => $reading->id,
                        'bill_reference' => $billRef,
                        'retry_count' => $retryCount + 1,
                        'wait_minutes' => $waitMinutes,
                        'platform' => $platform,
                    ]);

                    $dispatched++;
                } catch (\Exception $e) {
                    $this->error("  ❌ {$billRef} — dispatch ล้มเหลว: {$e->getMessage()}");
                    Log::error('fortune:check-pending: dispatch failed', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->info("  [DRY RUN] จะ dispatch job สำหรับ {$billRef}");
                $dispatched++;
            }
        }

        $this->newLine();
        $this->info("📊 Phase 1 สรุป: dispatch {$dispatched} บิล, ข้าม {$skipped} บิล");

        if ($dispatched > 0) {
            Log::info('fortune:check-pending: Phase 1 สรุปผล', [
                'dispatched' => $dispatched,
                'skipped' => $skipped,
                'total_found' => $pendingReadings->count(),
            ]);
        }

        // ================================================================
        // Phase 2: ส่งคำทำนายที่สร้างแล้วแต่ยังไม่ได้ส่งให้ลูกค้า
        // ================================================================
        // กรณี: คำทำนาย (deep_response) สร้างสำเร็จแล้วบันทึก DB แล้ว
        // แต่ LINE pushMessage ล้มเหลว (Gatekeeper, rate limit, timeout)
        // ทำให้ reading_sent_directly flag ไม่ถูกเซ็ต
        // ลูกค้าจ่ายเงินแล้วแต่ไม่ได้รับคำทำนาย → ต้อง retry ส่งให้!
        // ================================================================
        $deliveryRetried = $this->retryUnsentDeliveries($isDryRun, $isForce);

        return self::SUCCESS;
    }

    /**
     * Phase 2: ตรวจสอบคำทำนายที่สร้างแล้วแต่ยังไม่ได้ตั้ง flag พร้อมส่ง
     *
     * ✅ V3: ไม่ push คำทำนายผ่าน pushMessage อีกต่อไป (ประหยัดโควต้า LINE 200/เดือน)
     * แค่ตั้ง flag reading_ready_for_reply → เมื่อ user ส่งข้อความมา จะได้รับผ่าน replyMessage (ฟรี!)
     *
     * @return int จำนวนที่ตั้ง flag สำเร็จ
     */
    protected function retryUnsentDeliveries(bool $isDryRun, bool $isForce): int
    {
        $this->newLine();
        $this->info('📨 Phase 2: ตรวจสอบคำทำนายที่สร้างแล้วแต่ยังไม่ตั้ง flag พร้อมส่ง...');

        // ค้นหา readings ที่มี deep_response แต่ยังไม่ได้ส่ง
        $query = FortuneReading::where('is_paid', true)
            ->where('reading_type', 'deep')
            ->whereNotNull('deep_response')
            ->where('deep_response', '!=', '')
            ->whereNotNull('paid_at');

        // ไม่ force → กรองเฉพาะ 3 นาที - 24 ชม. หลังชำระ
        if (! $isForce) {
            $query->where('paid_at', '<=', now()->subMinutes(3))
                ->where('paid_at', '>=', now()->subMinutes(self::MAX_WAIT_MINUTES));
        }

        $readings = $query->orderBy('paid_at', 'asc')->get();

        // กรองเฉพาะที่ยังไม่ได้ส่ง (เช็ค conversation_state)
        $unsentReadings = $readings->filter(function ($r) {
            return ! $r->getConversationState('reading_sent_directly', false);
        });

        if ($unsentReadings->isEmpty()) {
            $this->info('✅ ไม่มีคำทำนายที่ค้างส่ง');

            return 0;
        }

        $this->info("📋 พบ {$unsentReadings->count()} คำทำนายที่ยังไม่ได้ส่ง");

        $flagged = 0;

        foreach ($unsentReadings as $reading) {
            $billRef = $reading->bill_reference ?? "#{$reading->id}";
            $waitMinutes = (int) $reading->paid_at->diffInMinutes(now());

            // ✅ V3: ตั้ง flag reading_ready_for_reply
            if (! $reading->getConversationState('reading_ready_for_reply', false)) {
                if (! $isDryRun) {
                    $reading->setConversationState('reading_ready_for_reply', true);
                    $reading->setConversationState('reading_ready_at', now()->toIso8601String());
                }

                $this->info("  ✅ {$billRef} — ตั้ง flag reading_ready_for_reply (รอ {$waitMinutes} นาที)");
                Log::info('fortune:check-pending Phase 2: ตั้ง flag reading_ready_for_reply', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $billRef,
                    'wait_minutes' => $waitMinutes,
                ]);
            }

            // ✅ FIX: Push แจ้งเตือน "คำทำนายพร้อมแล้ว" ถ้ายังไม่เคยแจ้ง
            // ก่อนหน้านี้แค่ตั้ง flag แต่ไม่ push → ผู้ใช้ไม่รู้ว่าคำทำนายพร้อมแล้ว
            // 📱 (2026-05-22) FB: ส่งคำทำนายเต็มทันที (view_reading_deep) — ตาม user spec
            //                 LINE: คงเดิม push Flex notification (quota จำกัด)
            $notificationSent = $reading->getConversationState('reading_notification_sent', false);
            $readingSentDirectly = $reading->getConversationState('reading_sent_directly', false);
            $notifyRetryCount = (int) $reading->getConversationState('phase2_notify_retry_count', 0);

            if (! $notificationSent && ! $readingSentDirectly && $notifyRetryCount < 2 && ! $isDryRun) {
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
                $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

                // 🔒 (2026-06-10) Atomic delivery lock — key เดียวกับ fortune:process-deep + Job
                //   เคสจริง (R5644): fortune:process-deep กำลัง push อยู่ (~18s) → Phase 2 รอบนาทีนั้น
                //   เห็น flag ยังไม่ตั้ง → push ซ้ำ → ลูกค้าได้ภาพ+ไพ่+คำทำนาย 2 ชุด
                $deliverLockKey = "fortune:deep_deliver:{$reading->id}";

                if (! empty($userId) && ! \Illuminate\Support\Facades\Cache::add($deliverLockKey, 1, 600)) {
                    $this->info("  ⏳ {$billRef} — delivery lock ถูกถือโดย process อื่น (ข้ามรอบนี้)");
                    Log::info('fortune:check-pending Phase 2: delivery lock ถูกถือ — skip duplicate push', [
                        'reading_id' => $reading->id,
                    ]);
                } elseif (! empty($userId)) {
                    try {
                        $settings = $settings ?? FortuneTellingSetting::getSettings();
                        $channelManager = $channelManager ?? new FortuneChannelManager($settings);
                        $name = $reading->facebook_user_name ?? 'คุณ';

                        // 📱 (2026-07-25) ส่งคำทำนายเต็มทันที ทั้ง FB และ LINE
                        //   เดิม LINE แยก branch ส่งกล่อง "คำทำนายพร้อมแล้ว กดอ่าน" — ตอนนี้ command หลัก
                        //   (fortune:process-deep) เลิกใช้กล่องนั้นแล้ว safety net ตัวนี้ต้อง sync ตาม
                        //   ไม่งั้นเคส push รอบแรกล้ม ลูกค้าจะเจอกล่องที่เจ้าของสั่งให้เอาออกกลับมาอีก
                        $fullMessage = "🌟 *คำทำนายเชิงลึกของคุณ{$name}*\n";
                        $fullMessage .= '📋 เลขที่บิล: '.($reading->bill_reference ?? '-')."\n";
                        $fullMessage .= '📅 วันที่: '.$reading->created_at->format('d/m/Y H:i')."\n";
                        $fullMessage .= "═══════════════════════\n\n";
                        $fullMessage .= $reading->deep_response;

                        $pushSent = $channelManager->sendResponse($platform, $userId, [
                            'action' => 'view_reading_deep',
                            'message' => $fullMessage,
                            'reading' => $reading,
                            'chart_image_url' => $reading->reading_image_url,
                            'tarot_image_urls' => collect($reading->getCollectedTarotCards())
                                ->pluck('image_url')->filter()->values()->all(),
                            // 🌙 (2026-05-22) ส่งกล่อง follow-up "หมออยู่ตอบเพิ่ม N นาที" หลังคำทำนาย
                            'send_pro_session_followup' => true,
                        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                        $reading->setConversationState('phase2_notify_retry_count', $notifyRetryCount + 1);

                        if ($pushSent) {
                            $reading->setConversationState('reading_sent_directly', true);
                            $reading->setConversationState('reading_ready_sent', true);
                            $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                            $reading->setConversationState('reading_notification_sent', true);
                            $reading->setConversationState('delivered_by_push', true);
                            // 🔔 (2026-06-23 bug-hunt) เข้า Deep Pro Session — ให้ follow-up Q&A เข้า Pro path
                            //   (ไม่ตกไป chat upsell/OOM = อาการ K3440) + nudge/auto-finalize ทำงานกับ path นี้
                            //   K3440 fix เดิมใส่เฉพาะ command path — check-pending P2 ตกหล่น. idempotent.
                            try {
                                $fcsAfterSend = new FortuneConversationService($settings);
                                $fcsAfterSend->enterDeepProSessionFor($reading->fresh());
                                // 📢 (2026-07-25) ชวนแนะนำเพื่อน — ส่งหลังคำทำนายถึงลูกค้าแล้ว (ดู flushPendingAffiliatePromo)
                                $fcsAfterSend->flushPendingAffiliatePromo($reading->fresh());
                            } catch (\Throwable $psErr) {
                                \Illuminate\Support\Facades\Log::warning('check-pending P2: enter Deep Pro Session/promo fail (non-blocking)', [
                                    'reading_id' => $reading->id, 'error' => $psErr->getMessage(),
                                ]);
                            }
                            $this->info("  📨 {$billRef} — push คำทำนายเต็มสำเร็จ ({$platform})");
                        } else {
                            // 🔓 push ล้ม → ปล่อย delivery lock ให้รอบหน้า/ทักกลับ deliver ได้
                            \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                            $reading->setConversationState('reading_notification_attempted', true);
                            $this->warn("  ⚠️ {$billRef} — push คำทำนายเต็มไม่สำเร็จ ({$platform}) — ลูกค้าจะได้รับเมื่อทักกลับมา");
                        }
                    } catch (\Exception $notifyErr) {
                        // 🔓 push exception → ปล่อย delivery lock
                        \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                        $reading->setConversationState('reading_notification_attempted', true);
                        $reading->setConversationState('phase2_notify_retry_count', $notifyRetryCount + 1);
                        Log::warning('fortune:check-pending Phase 2: push แจ้งเตือนล้มเหลว', [
                            'reading_id' => $reading->id,
                            'error' => $notifyErr->getMessage(),
                        ]);
                    }
                }
            } elseif ($notificationSent || $readingSentDirectly) {
                $this->info("  📌 {$billRef} — ".($readingSentDirectly ? 'ส่งคำทำนายเต็มแล้ว' : 'แจ้งแล้ว รอ user ส่งข้อความมา')." (รอ {$waitMinutes} นาที)");

                // 📢 (2026-07-25) late-flush ข้อความชวนแนะนำเพื่อนที่ค้าง
                //   เคสที่ต้องมี: push คำทำนายรอบแรกล้ม → ลูกค้าทักกลับแล้วได้คำทำนายผ่าน reply path
                //   ซึ่งไม่ได้ flush promo → flag ค้างถาวร (promo ส่งเฉพาะ LINE ซึ่งเป็นช่องที่ quota
                //   หมดบ่อยสุด = กระทบรายได้ affiliate จริง)
                //   cron ตัวนี้รันทุกนาที + เข้าสาขานี้เมื่อคำทำนายถึงลูกค้าแล้วเท่านั้น → ลำดับถูกเสมอ
                if ($readingSentDirectly) {
                    try {
                        $settings = $settings ?? FortuneTellingSetting::getSettings();
                        (new FortuneConversationService($settings))->flushPendingAffiliatePromo($reading);
                    } catch (\Throwable $promoErr) {
                        Log::warning('fortune:check-pending: late-flush promo ล้มเหลว (non-blocking)', [
                            'reading_id' => $reading->id,
                            'error' => $promoErr->getMessage(),
                        ]);
                    }
                }
            }

            $flagged++;
        }

        $this->newLine();
        $this->info("📊 Phase 2 สรุป: ตั้ง flag {$flagged} รายการ (รอ user ส่งข้อความมาเพื่อรับผ่าน replyMessage)");

        if ($flagged > 0) {
            Log::info('fortune:check-pending Phase 2: V3 สรุปผล — ตั้ง flag ไม่ push', [
                'flagged' => $flagged,
                'total_found' => $unsentReadings->count(),
            ]);
        }

        return $flagged;
    }
}
