<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneSystemVoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🎧 (2026-06-21) เล่นเสียง "เลือกไพ่ใบต่อไป" เมื่อลูกค้า Celtic 99 เงียบ
 *
 * owner spec: หลังลูกค้าเปิดไพ่ใบที่แล้ว ถ้าเงียบเกิน celtic_pick_voice_delay_sec วินาที
 * (ยังไม่กดเปิดใบต่อไป) → ส่งเสียงกระตุ้น 1 ครั้งต่อใบ. ถ้ากดก่อนครบเวลา = ไม่เล่น
 *
 * FB เท่านั้น (เสียงระบบส่งเฉพาะ FB). dedup ด้วย state pick_voice_nudged_count (1 ครั้ง/จำนวนใบ)
 */
class FortuneCelticPickVoiceNudge extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:celtic-pick-voice-nudge {--limit=80} {--dry-run}';

    /**
     * @var string
     */
    protected $description = '🎧 เล่นเสียง "เลือกไพ่ใบต่อไป" เมื่อลูกค้า Celtic 99 เงียบไม่กดเปิดไพ่ใบต่อไป';

    public function handle(): int
    {
        $settings = FortuneTellingSetting::getSettings();
        $delay = (int) ($settings->celtic_pick_voice_delay_sec ?? 45);

        // ปิดถ้า delay=0 หรือ master เสียงระบบปิด
        if ($delay <= 0 || ! $settings->system_voice_enabled) {
            return self::SUCCESS;
        }

        // คลิป card_pick_howto ต้องเปิด + มีไฟล์ (urlFor เช็คให้) — ไม่งั้นไม่ต้องสแกน
        $voiceUrl = (new FortuneSystemVoiceService($settings))->urlFor('card_pick_howto');
        if (empty($voiceUrl)) {
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $fb = app(FacebookWebhookService::class);
        $sent = 0;

        $readings = FortuneReading::where('conversation_status', FortuneReading::STATUS_CELTIC_PICKING)
            ->where('platform', 'facebook')
            ->where('is_paid', true)
            ->where('updated_at', '>=', now()->subHours(2)) // เฉพาะที่ยัง active
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($readings as $reading) {
            try {
                $userId = $reading->facebook_user_id ?: $reading->platform_user_id;
                if (empty($userId)) {
                    continue;
                }

                $picked = $reading->getCelticPickedCount();
                if ($picked >= 10) {
                    continue; // ครบแล้ว — ไม่ใช่ขั้นเลือกไพ่
                }

                // เงียบ = เวลาตั้งแต่ activity ล่าสุด (ส่วนใหญ่ = ตอนเปิดไพ่ใบที่แล้ว)
                $silence = $reading->updated_at ? (int) $reading->updated_at->diffInSeconds(now(), true) : 0;
                if ($silence < $delay) {
                    continue;
                }

                // dedup: เล่น 1 ครั้งต่อจำนวนใบที่เปิด (พอเปิดใบใหม่ count เปลี่ยน → เล่นได้อีก)
                $nudgedCount = (int) $reading->getConversationState('pick_voice_nudged_count', -1);
                if ($nudgedCount === $picked) {
                    continue;
                }

                if ($dry) {
                    $this->line("  #{$reading->id} picked={$picked} silence={$silence}s → NUDGE (dry)");
                    $sent++;

                    continue;
                }

                if ($fb->sendAudio($userId, $voiceUrl)) {
                    $reading->setConversationState('pick_voice_nudged_count', $picked);
                    $sent++;
                    Log::info('🎧 Celtic pick-voice nudge sent', [
                        'reading_id' => $reading->id,
                        'picked' => $picked,
                        'silence_sec' => $silence,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::debug('celtic pick-voice nudge fail (non-blocking)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("celtic-pick-voice-nudge: sent {$sent}");

        return self::SUCCESS;
    }
}
