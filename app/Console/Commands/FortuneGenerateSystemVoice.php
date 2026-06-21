<?php

namespace App\Console\Commands;

use App\Models\FortuneSystemVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneSystemVoiceService;
use Illuminate\Console\Command;

/**
 * 🎧 (2026-06-21) สร้างไฟล์เสียงระบบ (system voice clips) เก็บไว้ล่วงหน้า
 *
 * ใช้สร้าง/สร้างใหม่ไฟล์เสียงกลางทั้งหมด หรือเฉพาะ key ที่ระบุ
 * - ปกติข้ามคลิปที่มีไฟล์อยู่แล้ว (ยกเว้น --force)
 * - เก็บไฟล์ที่ public disk (local server) เท่านั้น
 */
class FortuneGenerateSystemVoice extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:generate-system-voice
                            {--key= : สร้างเฉพาะ clip_key นี้}
                            {--force : สร้างใหม่ทับของเดิม แม้มีไฟล์อยู่แล้ว}
                            {--only-enabled : สร้างเฉพาะคลิปที่เปิดใช้งาน}';

    /**
     * @var string
     */
    protected $description = '🎧 สร้างไฟล์เสียงระบบ (กล่องกระตุ้น/กติกา/วันเกิด ฯลฯ) เก็บไว้ล่วงหน้า';

    public function handle(): int
    {
        $settings = FortuneTellingSetting::getSettings();
        $service = new FortuneSystemVoiceService($settings);

        $query = FortuneSystemVoiceClip::query()->orderBy('sort_order');

        if ($key = $this->option('key')) {
            $query->where('clip_key', $key);
        }
        if ($this->option('only-enabled')) {
            $query->where('enabled', true);
        }

        $clips = $query->get();
        if ($clips->isEmpty()) {
            $this->warn('ไม่พบคลิปเสียงระบบที่ตรงเงื่อนไข (รัน db:seed FortuneSystemVoiceClipSeeder ก่อนหรือยัง?)');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $ok = 0;
        $skip = 0;
        $fail = 0;

        foreach ($clips as $clip) {
            // ⬆️ คลิปที่ใช้ไฟล์อัปโหลดเอง — ห้าม TTS ทับ (แม้ --force)
            if ($clip->isUploaded()) {
                $this->line("⏭️  ข้าม {$clip->clip_key} (เป็นไฟล์อัปโหลดเอง — ไม่สร้าง TTS ทับ)");
                $skip++;

                continue;
            }

            if (! $force && $clip->hasAudio()) {
                $this->line("⏭️  ข้าม {$clip->clip_key} (มีไฟล์อยู่แล้ว — ใช้ --force เพื่อสร้างใหม่)");
                $skip++;

                continue;
            }

            $this->line("🎙️  กำลังสร้าง: {$clip->clip_key} ...");
            $result = $service->generateClip($clip);

            if ($result['success']) {
                $this->info("   ✅ สำเร็จ ({$result['provider_used']}) → {$result['audio_url']}");
                $ok++;
            } else {
                $this->error("   ❌ ล้มเหลว: {$result['error']}");
                $fail++;
            }
        }

        $this->newLine();
        $this->info("สรุป: สำเร็จ {$ok} | ข้าม {$skip} | ล้มเหลว {$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
