<?php

namespace App\Console\Commands;

use App\Models\FortuneInviteMessage;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneBotMode;
use App\Services\FortuneWebLinkService;
use Illuminate\Console\Command;

/**
 * ✈️ ตรวจความพร้อมก่อนเปิดโหมด transfer (พาลูกค้าจาก FB ไปเว็บ/LINE)
 *
 * ทำไมต้องมีคำสั่งนี้: โหมดนี้มีสวิตช์เกี่ยวข้องหลายตัวและอยู่คนละการ์ดในหน้าแอดมิน
 * เปิดโหมดโดยลืมตัวใดตัวหนึ่ง = ลูกค้าเห็นกล่อง "ดูดวงฟรี" แล้วกดไปเจอทางตัน
 * ซึ่งเสียหายกว่าไม่เปิดเลย → ให้เครื่องตรวจให้ก่อน ไม่ต้องจำเอง
 *
 * ใช้: php artisan fortune:transfer-preflight
 */
class FortuneTransferPreflight extends Command
{
    protected $signature = 'fortune:transfer-preflight';

    protected $description = 'ตรวจความพร้อมก่อนเปิดโหมด transfer (FB → เว็บ/LINE)';

    /** จำนวนข้อบกพร่องที่ทำให้ "ยังเปิดไม่ได้" */
    protected int $blockers = 0;

    /** จำนวนข้อที่ควรรู้แต่ไม่บล็อก */
    protected int $warnings = 0;

    public function handle(): int
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $mode = new FortuneBotMode($settings);

        $this->newLine();
        $this->line('✈️  <options=bold>ตรวจความพร้อมโหมด TRANSFER (FB → เว็บ/LINE)</>');
        $this->line(str_repeat('─', 62));

        $this->checkMode($mode);
        $this->checkWebDestination();
        $this->checkLineDestination($settings);
        $this->checkFreeCard($settings, $mode);
        $this->checkInviteMessages();
        $this->checkFallbackPath($settings);

        $this->line(str_repeat('─', 62));

        if ($this->blockers > 0) {
            $this->error("❌ ยังเปิดไม่ได้ — มี {$this->blockers} จุดที่ต้องแก้ก่อน");

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->warn("⚠️  เปิดได้ แต่มี {$this->warnings} จุดที่ควรรู้ไว้");
        } else {
            $this->info('✅ พร้อมเปิดครบทุกจุด');
        }

        $this->newLine();
        $this->line('<options=bold>อย่าลืมหลัง deploy:</> sudo systemctl restart fortune-queue-worker.service');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * โหมดปัจจุบัน + สัดส่วนที่เปิด
     */
    protected function checkMode(FortuneBotMode $mode): void
    {
        if ($mode->isTransfer()) {
            $this->ok('โหมดบอท', 'transfer (เปิดอยู่)');
        } else {
            $this->info_('โหมดบอท', 'classic — ยังไม่เปิด (ตรวจล่วงหน้าได้ตามปกติ)');
        }

        $percent = (int) (FortuneTellingSetting::getSettings()->transfer_rollout_percent ?? 100);

        if ($percent >= 100) {
            $this->warn_('สัดส่วนที่เปิด (transfer_rollout_percent)', '100% — แนะนำเริ่มที่ 10 แล้วดู 2-3 วัน');
        } elseif ($percent <= 0) {
            $this->fail_('สัดส่วนที่เปิด (transfer_rollout_percent)', '0% = ไม่มีใครเข้าโหมดนี้เลย');
        } else {
            $this->ok('สัดส่วนที่เปิด', $percent.'% ของลูกค้า FB');
        }
    }

    /**
     * ปลายทางที่ 1 — เว็บจันทรา (ปลายทางหลักตามที่เจ้าของเคาะ)
     */
    protected function checkWebDestination(): void
    {
        $svc = app(FortuneWebLinkService::class);

        if (! $svc->isEnabled()) {
            $this->fail_(
                'ปุ่มเว็บ (enable_web_fortune_button)',
                'ปิดอยู่ → กล่องจะไม่มีปุ่มไปเว็บ ซึ่งเป็นปลายทางหลัก'
            );

            return;
        }

        $this->ok('ปุ่มเว็บ (enable_web_fortune_button)', 'เปิดอยู่');
        $this->info_('ปลายทางเว็บ', $svc->getSsoUrl().' → /tarot/free');
        $this->info_(
            '⚠️ ยังไม่พิสูจน์',
            'ต้องกดลิงก์จาก FB DM ในมือถือจริง (Messenger เปิดใน in-app webview)'
        );
    }

    /**
     * ปลายทางที่ 2 — LINE OA
     */
    protected function checkLineDestination(FortuneTellingSetting $settings): void
    {
        $basicId = trim((string) ($settings->line_bot_basic_id ?? ''));

        if ($basicId === '') {
            $this->warn_('LINE OA (line_bot_basic_id)', 'ไม่ได้ตั้ง → กล่องจะไม่มีปุ่ม LINE');

            return;
        }

        $this->ok('LINE OA (line_bot_basic_id)', $basicId);
    }

    /**
     * คำทำนายฟรี — จุดที่เคยพังเงียบที่สุด
     */
    protected function checkFreeCard(FortuneTellingSetting $settings, FortuneBotMode $mode): void
    {
        // LINE: โหมด transfer เปิดให้เองแล้ว (ไม่ต้องรอสวิตช์หลัก)
        if ($mode->freeCardEnabledFor('line')) {
            $master = (bool) ($settings->enable_free_card_reading ?? false);
            $this->ok(
                'ฟรีบน LINE',
                $master ? 'เปิด (สวิตช์หลักเปิดอยู่)' : 'เปิดโดยโหมด transfer (สวิตช์หลักปิด — ไม่เป็นไร)'
            );
        } else {
            $this->fail_('ฟรีบน LINE', 'ปิด — เปิดโหมด transfer หรือเปิด enable_free_card_reading');
        }

        $chars = $mode->freeCardMaxChars();
        if ($chars === 0) {
            $this->warn_(
                'ความยาวคำทำนายฟรี (free_card_max_chars)',
                '0 = ยาวแบบเดิม 1,500-2,000 ตัว · แนะนำ 500 ให้เท่าเว็บ'
            );
        } else {
            $this->ok('ความยาวคำทำนายฟรี', $chars.' ตัวอักษร');
        }

        $regrant = $mode->freeCardRegrantAt();
        if ($regrant === null) {
            $this->warn_(
                'รอบแจกสิทธิ์ฟรีใหม่ (free_card_regrant_at)',
                'ยังไม่ตั้ง → คนที่เคยใช้ฟรีบน FB จะได้กล่องที่ไม่พูดคำว่า "ฟรี" (hook อ่อนลงมาก)'
            );
        } elseif ($regrant->isFuture()) {
            $this->warn_('รอบแจกสิทธิ์ฟรีใหม่', 'ตั้งไว้อนาคต ('.$regrant->format('Y-m-d H:i').') ยังไม่มีผล');
        } else {
            $this->ok('รอบแจกสิทธิ์ฟรีใหม่', $regrant->format('Y-m-d H:i').' (ทุกคนได้สิทธิ์ใหม่แล้ว)');
        }
    }

    /**
     * ชุดข้อความ DM ต้องมีของโหมดนี้ ไม่งั้นเสียงสวนกับกล่อง
     */
    protected function checkInviteMessages(): void
    {
        try {
            $count = FortuneInviteMessage::where('mode', FortuneInviteMessage::MODE_TRANSFER)
                ->where('is_active', true)
                ->count();
        } catch (\Throwable $e) {
            $this->warn_('ข้อความ DM ชุด transfer', 'ตรวจไม่ได้: '.$e->getMessage());

            return;
        }

        if ($count === 0) {
            $this->fail_(
                'ข้อความ DM ชุด transfer',
                'ไม่มีเลย → DM จะยังชวน "ทักมาดูดวงในแชท" สวนกับกล่อง '
                    .'(รัน php artisan db:seed --class=FortuneTransferInviteMessageSeeder)'
            );

            return;
        }

        $this->ok('ข้อความ DM ชุด transfer', $count.' ข้อความ');
    }

    /**
     * ทางถอยสำหรับคนที่ทำเว็บ/ไลน์ไม่เป็น
     */
    protected function checkFallbackPath(FortuneTellingSetting $settings): void
    {
        $audio = (bool) ($settings->enable_consent_audio_code ?? false);
        $quiz = (bool) ($settings->enable_consent_quiz ?? false);

        if ($audio || $quiz) {
            $gates = array_filter([$audio ? 'ฟังเสียง+รหัส' : null, $quiz ? 'แบบสอบถาม 5 ข้อ' : null]);
            $this->ok(
                'ทางถอย (คนทำไม่เป็น)',
                'ผ่อนเกตให้อัตโนมัติแล้ว — เกตที่เปิดอยู่: '.implode(' + ', $gates)
            );
        } else {
            $this->ok('ทางถอย (คนทำไม่เป็น)', 'ไม่มีเกตพิเศษเปิดอยู่');
        }
    }

    // ── ตัวช่วยแสดงผล ────────────────────────────────────────────

    protected function ok(string $label, string $detail): void
    {
        $this->line("  <fg=green>✅</> <options=bold>{$label}</>: {$detail}");
    }

    protected function warn_(string $label, string $detail): void
    {
        $this->warnings++;
        $this->line("  <fg=yellow>⚠️ </> <options=bold>{$label}</>: {$detail}");
    }

    protected function fail_(string $label, string $detail): void
    {
        $this->blockers++;
        $this->line("  <fg=red>❌</> <options=bold>{$label}</>: {$detail}");
    }

    protected function info_(string $label, string $detail): void
    {
        $this->line("  <fg=cyan>ℹ️ </> <options=bold>{$label}</>: {$detail}");
    }
}
