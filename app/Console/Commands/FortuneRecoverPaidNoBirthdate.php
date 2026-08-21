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
                            {--bill= : process เฉพาะ bill_reference (เช่น FTU-260513-F2933)}
                            {--hours=48 : ค้น reading ย้อนหลังกี่ชั่วโมง (default 48)}
                            {--force : บังคับ recover แม้มี birth_date แล้ว (เคส edge case ที่บิลค้างหลังเก็บวันเกิด)}
                            {--auto : โหมด scheduler — silent + ใช้ min-age threshold + กัน flood (recover เฉพาะ status=collecting_birthdate ที่เก่ากว่า 3 นาที)}
                            {--min-age-minutes=3 : ใช้กับ --auto — recover เฉพาะ reading ที่ paid_at เก่ากว่ากี่นาที (เผื่อ initial push)}';

    protected $description = 'Recover ลูกค้า Deep 39฿ ที่จ่ายแล้วแต่ flow Pay-First ไม่ทำงาน — push "ขอวันเกิด" ใหม่';

    public function handle(): int
    {
        $isDry = $this->option('dry');
        $specificId = $this->option('id');
        $specificBill = $this->option('bill');
        $hours = (int) $this->option('hours');
        $force = (bool) $this->option('force');
        $isAuto = (bool) $this->option('auto');
        $minAgeMinutes = max(1, (int) $this->option('min-age-minutes'));

        // 🛑 (2026-05-22 #5) DISABLED auto mode per user request
        //   "ปิดระบบตามคนที่ยังไม่ได้รับคำทำนาย — สร้างปัญหามากกว่า"
        //   admin trigger ตัวเดียวด้วย --id หรือ --bill ยังใช้ได้
        if ($isAuto) {
            Log::info('fortune:recover-paid-no-birthdate: --auto disabled, skipping run');

            return 0;
        }

        if (! $isAuto) {
            $this->info('🛟 หา Deep readings ที่จ่ายแล้วแต่ค้าง (ไม่มีวันเกิด)...');
        }

        // 🛟 (2026-05-13 v2) ตัด whereNull('deep_response') ออก
        //   เคสจริง Entony (#2474): status=completed + อาจมี error text ใน deep_response
        //   เงื่อนไขจริงๆ = "จ่ายแล้ว + ไม่มีวันเกิด" → ค้างแน่นอน (Pay-First ต้องมี birth_date)
        //   ไม่ว่า deep_response จะเป็น NULL หรือ error text → reset แล้วขอวันเกิดใหม่
        $query = FortuneReading::where('reading_type', 'deep')
            ->where('is_paid', true);

        // --force = ไม่กรอง birth_date (recover แม้มีวันเกิดแล้ว)
        if (! $force) {
            $query->whereNull('birth_date');
        }

        // 🤖 (2026-05-13) --auto mode — strict filter เพื่อกัน flood + กัน race
        //   1. status = collecting_birthdate หรือ completed (orphan จาก expire เก่า)
        //   2. paid_at เก่ากว่า min-age-minutes (เผื่อ initial push ใน flow ปกติ)
        //   3. กัน duplicate push: เช็คว่า resent_at ไม่ใช่ในช่วง 30 นาทีล่าสุด
        //      (ใช้ conversation_state['birthdate_resent_at'])
        // 🚨 (2026-05-14) เพิ่ม STATUS_COMPLETED ใน whitelist
        //   เคสจริง #2545: ลูกค้าจ่าย Deep 39 → status=COLLECTING_BIRTHDATE
        //                  → 30 min ผ่าน → expire mark COMPLETED → recovery skip
        //                  → ลูกค้าค้าง ไม่ได้ทำนาย
        //   Fix expireOldConversationsQuery ใส่ is_paid=false guard แล้ว (commit prev)
        //        — แต่ orphan ที่ COMPLETED ไปแล้วต้อง recover ผ่านที่นี่ด้วย
        if ($isAuto) {
            $query->whereIn('conversation_status', [
                FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                FortuneReading::STATUS_COMPLETED, // 🛡️ orphan ที่ expire ก่อน fix
            ])
                ->where('paid_at', '<=', now()->subMinutes($minAgeMinutes))
                ->where('paid_at', '>=', now()->subHours($hours));
        } elseif (! $specificId && ! $specificBill) {
            // manual mode (no --id/--bill) — ใช้ --hours window
            $query->where('paid_at', '>=', now()->subHours($hours));
        }

        if ($specificId) {
            $query->where('id', (int) $specificId);
        }

        if ($specificBill) {
            $query->where('bill_reference', trim($specificBill));
        }

        $stuck = $query->orderBy('paid_at', 'desc')->get();

        if ($stuck->isEmpty()) {
            if (! $isAuto) {
                $this->info('✅ ไม่พบ reading ที่ต้อง recover');
            }

            return 0;
        }

        if (! $isAuto) {
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
        }

        if ($isDry) {
            $this->warn('Dry run — ไม่ได้ recover จริง');

            return 0;
        }

        $settings = FortuneTellingSetting::getSettings();
        $channelManager = new FortuneChannelManager($settings);

        $recovered = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($stuck as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    if (! $isAuto) {
                        $this->warn("  ⚠️ #{$reading->id} skip — ไม่มี user_id");
                    }
                    $failed++;

                    continue;
                }

                // 🛡️ (2026-05-13) Auto mode — dedup: กัน flood ส่ง push ซ้ำๆ
                //   ถ้า resent_at อยู่ใน 30 นาทีล่าสุด → skip (รอลูกค้าตอบ)
                //   manual mode (--id/--bill/--force) ข้าม check นี้ — admin บังคับ resend ได้เสมอ
                if ($isAuto && ! $specificId && ! $specificBill && ! $force) {
                    $lastResent = $reading->getConversationState('birthdate_resent_at');
                    if ($lastResent) {
                        try {
                            $resentAt = \Carbon\Carbon::parse($lastResent);
                            if ($resentAt->gt(now()->subMinutes(30))) {
                                $skipped++;

                                continue; // เพิ่งส่ง push ไปแล้ว — รอลูกค้าตอบ
                            }
                        } catch (\Throwable $e) {
                            // parse fail = ไม่มี dedup, ส่งต่อ
                        }
                    }
                }

                // 🎂 (2026-08-21) ก่อนทวงวันเกิด — เช็คก่อนว่าเรามีอยู่แล้วหรือเปล่า
                //   ลูกค้าที่เคยพิมพ์วันเกิดตอนขอดวงฟรีรายวัน (เก็บใน fortune_user_credits)
                //   เคยถูกทวงซ้ำเป็นระลอกทั้งที่เรามีข้อมูลอยู่แล้ว
                $priorHit = \App\Services\Fortune\BirthdateResolver::forReading($reading);

                if ($priorHit !== null) {
                    $fcs = new \App\Services\FortuneConversationService($settings);
                    $fcs->beginDeepGeneralReading($reading, $priorHit['ymd']);
                    $reading->setConversationState('birthdate_auto_filled', true);
                    $reading->setConversationState('birthdate_reused_from_history', $priorHit['ymd']);

                    $this->info("   ♻️  reading {$reading->id} — ใช้วันเกิดเดิม {$priorHit['ymd']} ({$priorHit['source']}) แทนการทวง");

                    Log::info('RecoverPaidNoBirthdate: reuse วันเกิดเดิม ไม่ทวงซ้ำ', [
                        'reading_id' => $reading->id,
                        'birth_date' => $priorHit['ymd'],
                        'source' => $priorHit['source'],
                    ]);

                    $reading->setConversationState('birthdate_resent_at', now()->toIso8601String());
                    $recovered++;

                    continue;
                }

                // 1. Reset state — กลับเข้า flow ขอวันเกิด + clear error text
                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                    'deep_response' => null, // clear error text ถ้ามี
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

                // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอ/ระบบขัดข้อง" — โทนนุ่ม คงคำว่า "พร้อมแล้ว"
                $thanksMessage = "🙏 ขออภัยอย่างสูงค่ะ คุณ{$userName}\n\n"
                    ."ใช้เวลาประมวลผลนานกว่าปกตินิดหน่อย — ตอนนี้พร้อมแล้วค่ะ ✨\n\n"
                    ."═══════════════════════\n"
                    ."🌙 *แม่หมอจันทรากำลังเปิดประตูดวงให้ใหม่*\n"
                    ."═══════════════════════\n\n"
                    ."🔖 เลขที่บิล: {$billRef}\n"
                    ."💰 ค่าครู: ฿{$payAmountStr} (ที่จ่ายแล้ว — ไม่ต้องจ่ายซ้ำ)\n\n"
                    ."🪄 ตอนนี้ขอ*วันเดือนปีเกิด*ของเจ้าชะตาก่อนนะคะ ✨\n\n"
                    ."📝 *ตัวอย่าง:* 15 มีนาคม 2538\n"
                    ."   หรือ 15/3/2538 / 15-3-2538\n\n"
                    .'💡 หากจำไม่ได้แม่นยำ — ใส่ปีก่อน เดือน ก็พอค่ะ';

                $pushSent = $channelManager->sendResponse($platform, $userId, [
                    'action' => 'collecting_birthdate',
                    'message' => $thanksMessage,
                    'reading' => $reading,
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($pushSent) {
                    // 🛡️ Mark resent_at ทันที (กัน duplicate ใน scheduler รอบถัดไป)
                    $reading->setConversationState('birthdate_resent_at', now()->toIso8601String());
                    if (! $isAuto) {
                        $this->info("  ✅ #{$reading->id} recover + push 'ขอวันเกิด' สำเร็จ ({$platform})");
                    }
                    $recovered++;
                    Log::info('Fortune Recover: push "ขอวันเกิด" สำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'user_name' => $userName,
                        'mode' => $isAuto ? 'auto' : 'manual',
                    ]);
                } else {
                    if (! $isAuto) {
                        $this->warn("  ⚠️ #{$reading->id} reset state แล้ว แต่ push ล้มเหลว (ลูกค้าทักกลับจะเข้า flow ใหม่)");
                    }
                    $recovered++; // ยังนับ recover เพราะ state reset แล้ว
                }
            } catch (\Throwable $e) {
                if (! $isAuto) {
                    $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                }
                $failed++;
                Log::error('Fortune Recover: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 📊 Summary — silent ถ้า auto + ไม่มีอะไร recover (กัน log noise ทุก 5 นาที)
        if (! $isAuto) {
            $this->newLine();
            $this->info("📊 สรุป: recover {$recovered} | failed {$failed} | skipped {$skipped}");
        } elseif ($recovered > 0 || $failed > 0) {
            // auto mode — log เฉพาะที่มี action จริง
            Log::info('Fortune Auto-Recover: completed', [
                'recovered' => $recovered,
                'failed' => $failed,
                'skipped_recent' => $skipped,
            ]);
        }

        return $failed > 0 ? 1 : 0;
    }
}
