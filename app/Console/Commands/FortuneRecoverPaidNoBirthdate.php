<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-05-13) Recover ลูกค้า Deep 39฿ ที่จ่ายแล้วแต่ flow Pay-First ไม่ทำงาน
 *
 * เคสที่จัดการ:
 *   - ลูกค้าจ่าย 39฿ ก่อน $payFirst undefined fix
 *   - pay_first_mode flag ไม่ถูก set ใน DB
 *   - SmsPaymentService:780 (เดิม) fall through → dispatch AI Job
 *   - Job AI gen reading ที่ไม่มี birth_date → fail
 *   - ลูกค้าได้ message "AI ขัดข้องชั่วคราว" + status=completed (errored)
 *
 * Recovery:
 *   1. หา reading_type=deep, is_paid=true, birth_date=NULL, paid_at < 48hr
 *   2. Reset status → COLLECTING_BIRTHDATE
 *   3. Set pay_first_mode = true
 *   4. Clear notification flags + AI failure flags
 *   5. Push "ขอวันเกิด" ผ่าน POST_PURCHASE_UPDATE message tag
 *
 * Usage:
 *   php artisan fortune:recover-paid-no-birthdate          # รันจริง
 *   php artisan fortune:recover-paid-no-birthdate --dry    # dry run
 *   php artisan fortune:recover-paid-no-birthdate --id=2474 # เฉพาะ ID
 */
class FortuneRecoverPaidNoBirthdate extends Command
{
    protected $signature = 'fortune:recover-paid-no-birthdate
                            {--dry : Dry run — แสดง list ที่จะ recover แต่ไม่ push}
                            {--id= : process เฉพาะ reading ID (admin manual)}
                            {--hours=48 : ค้น reading ย้อนหลังกี่ชั่วโมง (default 48)}';

    protected $description = 'Recover ลูกค้า Deep 39฿ ที่จ่ายแล้วแต่ flow Pay-First ไม่ทำงาน — push "ขอวันเกิด" ใหม่';

    public function handle(): int
    {
        $isDry = $this->option('dry');
        $specificId = $this->option('id');
        $hours = (int) $this->option('hours');

        $this->info('🛟 หา Deep readings ที่จ่ายแล้วแต่ค้าง (ไม่มีวันเกิด)...');

        $query = FortuneReading::where('reading_type', 'deep')
            ->where('is_paid', true)
            ->whereNull('birth_date')
            ->whereNull('deep_response')
            ->where('paid_at', '>=', now()->subHours($hours));

        if ($specificId) {
            $query->where('id', (int) $specificId);
        }

        $stuck = $query->orderBy('paid_at', 'desc')->get();

        if ($stuck->isEmpty()) {
            $this->info('✅ ไม่พบ reading ที่ต้อง recover');

            return 0;
        }

        $this->info("🔍 พบ {$stuck->count()} reading ที่ค้าง:");
        $this->table(
            ['ID', 'User', 'Platform', 'จ่าย', 'จ่ายเมื่อ', 'Status'],
            $stuck->map(fn ($r) => [
                $r->id,
                $r->facebook_user_name ?? '-',
                $r->platform ?? '?',
                number_format($r->amount_paid, 2),
                $r->paid_at?->diffForHumans() ?? '?',
                $r->conversation_status,
            ])->toArray()
        );

        if ($isDry) {
            $this->warn('Dry run — ไม่ได้ recover จริง');

            return 0;
        }

        $settings = FortuneTellingSetting::getSettings();
        $channelManager = new FortuneChannelManager($settings);

        $recovered = 0;
        $failed = 0;

        foreach ($stuck as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    $this->warn("  ⚠️ #{$reading->id} skip — ไม่มี user_id");
                    $failed++;

                    continue;
                }

                // 1. Reset state — กลับเข้า flow ขอวันเกิด
                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                ]);
                $reading->setConversationState('pay_first_mode', true);
                $reading->setConversationState('reading_notification_sent', false);
                $reading->setConversationState('reading_ready_for_reply', false);
                $reading->setConversationState('reading_notification_attempted', false);
                $reading->setConversationState('ai_failed_alert', false);
                $reading->setConversationState('reading_notification_retry_count', 0);

                // 2. Push "ขอวันเกิด" ผ่าน POST_PURCHASE_UPDATE
                $userName = $reading->facebook_user_name ?? 'เจ้าชะตา';
                $billRef = $reading->bill_reference ?? '-';
                $payAmountStr = number_format((float) ($reading->amount_paid ?? 39), 2);

                $thanksMessage = "🙏 ขออภัยอย่างสูงค่ะ คุณ{$userName}\n\n"
                    ."ระบบเกิดข้อขัดข้องเล็กน้อย — ตอนนี้แก้แล้ว ✨\n\n"
                    ."═══════════════════════\n"
                    ."🌙 *แม่หมอจันทรากำลังเปิดประตูดวงให้ใหม่*\n"
                    ."═══════════════════════\n\n"
                    ."🔖 เลขที่บิล: {$billRef}\n"
                    ."💰 ค่าครู: ฿{$payAmountStr} (ที่จ่ายแล้ว — ไม่ต้องจ่ายซ้ำ)\n\n"
                    ."🪄 ตอนนี้ขอ*วันเดือนปีเกิด*ของเจ้าชะตาก่อนนะคะ ✨\n\n"
                    ."📝 *ตัวอย่าง:* 15 มีนาคม 2538\n"
                    ."   หรือ 15/3/2538 / 15-3-2538\n\n"
                    ."💡 หากจำไม่ได้แม่นยำ — ใส่ปีก่อน เดือน ก็พอค่ะ";

                $pushSent = $channelManager->sendResponse($platform, $userId, [
                    'action' => 'collecting_birthdate',
                    'message' => $thanksMessage,
                    'reading' => $reading,
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($pushSent) {
                    $this->info("  ✅ #{$reading->id} recover + push 'ขอวันเกิด' สำเร็จ ({$platform})");
                    $recovered++;
                    Log::info('Fortune Recover: push "ขอวันเกิด" สำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'user_name' => $userName,
                    ]);
                } else {
                    $this->warn("  ⚠️ #{$reading->id} reset state แล้ว แต่ push ล้มเหลว (ลูกค้าทักกลับจะเข้า flow ใหม่)");
                    $recovered++; // ยังนับ recover เพราะ state reset แล้ว
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Fortune Recover: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: recover {$recovered} | failed {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
