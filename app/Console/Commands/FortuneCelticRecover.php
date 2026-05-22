<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🚨 Celtic Cross Recovery Command
 *
 * Use cases:
 * 1. Diagnose & recover specific reading: php artisan fortune:celtic-recover {id}
 * 2. Auto-recover all stuck (paid but no card picked > 5min): php artisan fortune:celtic-recover --auto
 * 3. Dry run: php artisan fortune:celtic-recover --auto --dry
 *
 * เคสที่กู้ (2026-05-04 expanded scope):
 * - is_paid=true แต่ conversation_status ยังเป็น celtic_pending_payment (transition fail)
 * - conversation_status=celtic_picking แต่ไม่ได้สุ่มไพ่ใบไหนเลย (count=0) > N นาที
 * - 🆕 conversation_status ใดๆ ของ Celtic active flow + celtic_questions_used=0 + ค้าง > N นาที
 *      → ครอบคลุมเคส "เปิดไพ่กลางทางหลุด" (เช่น เปิด 6/10 แล้วหายไป) + "เปิดครบ 10 แต่ไม่ถาม"
 *
 * Action: re-push prompt ที่ตรงกับ state ปัจจุบัน ผ่าน FB MESSAGE_TAG=POST_PURCHASE_UPDATE
 *         (ใช้ buildCelticResumeResponse — สร้าง message ตาม state)
 */
class FortuneCelticRecover extends Command
{
    protected $signature = 'fortune:celtic-recover
                            {id? : Reading ID หรือ bill_reference (เช่น FTU-260505-J1439)}
                            {--auto : สแกนหา readings ที่ติดทั้งหมด}
                            {--dry : Dry run — แสดงรายการแต่ไม่ส่งจริง}
                            {--minutes=5 : เกณฑ์เวลาที่ถือว่าค้าง (นาที)}';

    protected $description = 'กู้ Celtic Cross readings ที่ลูกค้าจ่ายแล้วแต่บอทเงียบไม่ส่งให้เลือกไพ่';

    public function handle(): int
    {
        $id = $this->argument('id');
        $auto = $this->option('auto');
        $dry = $this->option('dry');
        $minutes = (int) $this->option('minutes');

        if (! $id && ! $auto) {
            $this->error('ต้องระบุ {id} หรือ --auto');

            return 1;
        }

        // 🛑 (2026-05-22 #5) DISABLED auto mode per user request
        //   "ปิดระบบตามคนที่ยังไม่ได้รับคำทำนาย — สร้างปัญหามากกว่า"
        //   admin trigger ตัวเดียวด้วย {id} หรือ {bill_reference} ยังใช้ได้
        if ($auto) {
            $this->warn('⛔ auto mode disabled (2026-05-22 #5) — ใช้ fortune:celtic-recover {id|bill_ref} แทน');
            Log::info('fortune:celtic-recover: --auto disabled, skipping run');

            return 0;
        }

        $readings = collect();

        if ($id) {
            // 🩹 (2026-05-05) รับทั้ง numeric ID และ bill_reference (FTU-XXXXXX-XXXXX)
            //   ลูกค้า/แอดมินจะเห็น bill_reference ใน slip — ใช้กู้ตรงๆ ได้เลย
            $reading = is_numeric($id)
                ? FortuneReading::find($id)
                : FortuneReading::where('bill_reference', $id)->first();

            if (! $reading) {
                $this->error("ไม่พบ reading — id/bill: {$id}");

                return 1;
            }
            $readings->push($reading);
        }

        if ($auto) {
            $cutoff = now()->subMinutes($minutes);

            // 🎯 Universal stuck detector: Celtic + paid + 0 picked + ค้าง > N นาที
            //   จับทุก status — pending_payment, picking, completed (force-completed bug),
            //   awaiting_question (เคยแสดงไพ่แต่ flow หลุด)
            //   ยกเว้น: cancelled / qa_window_expired (legitimate end)
            $excludedStatuses = [
                'cancelled',                    // ลูกค้ายกเลิกเอง
                'celtic_qa_window_expired',     // หมดเวลาถาม Q
                'expired',                      // บิลหมดอายุ
            ];

            $candidates = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
                ->where('is_paid', true)
                ->whereNotIn('conversation_status', $excludedStatuses)
                ->where('updated_at', '<=', $cutoff)
                ->get();

            // 🆕 (2026-05-04) Filter: เคสที่ user ยังไม่ได้ใช้สิทธิ์ถามคำถาม (questions_used=0)
            //    เดิม: filter เฉพาะ picked=0 → ตกหล่น "เปิดไพ่ 6/10 แล้วหลุด" / "เปิดครบไม่ถาม"
            //    ใหม่: ครอบคลุมทุกสถานะที่ลูกค้าจ่ายแล้วยังไม่ได้คำตอบเลย
            $readings = $candidates->filter(fn ($r) => (int) ($r->celtic_questions_used ?? 0) === 0);
        }

        if ($readings->isEmpty()) {
            $this->info('✅ ไม่มี reading ค้าง');

            return 0;
        }

        $this->info("🔍 พบ {$readings->count()} reading ที่ติด:");
        $this->table(
            ['ID', 'User', 'Status', 'Paid', 'Picked', 'Updated'],
            $readings->map(fn ($r) => [
                $r->id,
                $r->facebook_user_name ?? '-',
                $r->conversation_status,
                $r->is_paid ? '✓' : '✗',
                $r->getCelticPickedCount() . '/10',
                $r->updated_at?->diffForHumans(),
            ])->toArray()
        );

        if ($dry) {
            $this->warn('Dry run — ไม่ได้ส่งจริง');

            return 0;
        }

        $settings = FortuneTellingSetting::getSettings();
        $conversationService = new FortuneConversationService($settings);
        $channelManager = new FortuneChannelManager($settings);

        $recovered = 0;
        $failed = 0;

        foreach ($readings as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id) ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    $this->warn("  #{$reading->id} skip — ไม่มี user_id");
                    $failed++;
                    continue;
                }

                // 🆕 (2026-05-04) Build resume response ตาม state ปัจจุบัน
                //   - CELTIC_PENDING_PAYMENT + is_paid=true → ใช้ onCelticPaymentConfirmed
                //     (transition → CELTIC_PICKING + return prompt ใบ 1)
                //   - 🚨 (2026-05-05) status='new' + is_paid=true → force-promote เป็น CELTIC + ทำเหมือน PENDING_PAYMENT
                //     เคสที่เจอ: บิล FTU-260505-J1439 — slip matcher ไม่ transition reading_type/status
                //   - state อื่นๆ → ใช้ buildCelticResumeResponse (resume ที่จุดเดิม)
                if ($reading->is_paid
                    && in_array($reading->conversation_status, [FortuneReading::STATUS_NEW, FortuneReading::STATUS_CELTIC_PENDING_PAYMENT], true)
                    && $reading->getCelticPickedCount() === 0) {
                    if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
                        $reading->update(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]);
                    }
                    if ($reading->conversation_status !== FortuneReading::STATUS_CELTIC_PENDING_PAYMENT) {
                        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT]);
                    }
                    $response = $conversationService->onCelticPaymentConfirmed($reading->fresh());
                } else {
                    $response = $conversationService->buildCelticResumeResponse($reading->fresh(), false);
                }

                // Add recovery header (override ส่วนหัวเดิม)
                $response['message'] = "🔔 *ขออภัยที่ทำให้รอนะคะ*\n"
                    . "ระบบตรวจพบว่ายังไม่ได้ทำนายให้สมบูรณ์ — ขอกู้ข้อมูลให้ตอนนี้ค่ะ ⬇️\n\n"
                    . "═══════════════════════\n\n"
                    . ($response['message'] ?? '');

                $sent = $channelManager->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($sent) {
                    $this->info("  ✅ #{$reading->id} ส่งสำเร็จ ({$platform})");
                    $recovered++;
                    Log::info('Celtic Recovery: re-push first card prompt สำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                    ]);
                } else {
                    $this->error("  ❌ #{$reading->id} ส่งไม่สำเร็จ");
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Celtic Recovery: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: กู้สำเร็จ {$recovered} | ล้มเหลว {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
