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
 * เคสที่กู้:
 * - is_paid=true แต่ conversation_status ยังเป็น celtic_pending_payment
 * - conversation_status=celtic_picking แต่ไม่ได้สุ่มไพ่ใบไหนเลย (count=0) > 5 นาที
 *
 * Action: re-push first card prompt ผ่าน FB MESSAGE_TAG=POST_PURCHASE_UPDATE
 */
class FortuneCelticRecover extends Command
{
    protected $signature = 'fortune:celtic-recover
                            {id? : Reading ID เฉพาะที่จะกู้}
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

        $readings = collect();

        if ($id) {
            $reading = FortuneReading::find($id);
            if (! $reading) {
                $this->error("ไม่พบ reading #{$id}");

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

            // Filter ใน PHP — getCelticPickedCount() อ่านจาก JSON column
            $readings = $candidates->filter(fn ($r) => $r->getCelticPickedCount() === 0);
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

                // Re-trigger Celtic onPaymentConfirmed (idempotent — แค่ update status + return prompt)
                $response = $conversationService->onCelticPaymentConfirmed($reading);

                // Add recovery header
                $response['message'] = "🔔 *ขออภัยที่ทำให้รอนะคะ*\n"
                    . "ระบบเพิ่งตรวจพบว่ายังไม่ได้ส่งให้เจ้าชะตาเลือกไพ่\n"
                    . "ขอเริ่มเปิดไพ่ให้ตอนนี้เลยค่ะ ⬇️\n\n"
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
