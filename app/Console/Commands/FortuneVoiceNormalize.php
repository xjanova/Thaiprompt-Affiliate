<?php

namespace App\Console\Commands;

use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\VoiceLoudnessNormalizer;
use App\Services\FortuneVoiceStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🔊 (2026-11-10) ปรับความดังคลิปเสียงตัวละครที่เก็บไว้แล้ว — ทับไฟล์เดิม URL เดิม
 *
 * ทำไม: คลิปแคชตลอดไปด้วย text_hash — คลิปที่สร้างก่อนเปิด loudnorm (เช่น เสียง "บัว" ของ maemor/kunsai
 *   ที่สลับบน prod 2026-11-10) ยังเบากว่าคลิปห้องอยู่ (-24..-31 LUFS) จนกว่าจะปรับทับ
 *
 * - ดึงไฟล์: disk local อ่านจาก Storage public · cloud ดึงผ่าน audio_url (HTTP GET)
 * - ปรับด้วย VoiceLoudnessNormalizer ตัวเดียวกับตอนสร้างคลิป แล้ว putAudio ทับ path เดิม
 * - idempotent: วัดก่อน ห่างเป้า ≤ ±1 LU = ข้าม · ไม่มี ffmpeg = จบเงียบ ๆ (exit 0)
 * - ข้ามคลิปที่ disk ไม่ตรง driver ปัจจุบัน (putAudio เขียนได้แค่ driver ปัจจุบัน — เขียนผิดที่ = URL เดิมไม่เปลี่ยน)
 *
 * ตัวอย่าง: php artisan fortune:voice-normalize --persona=maemor --persona=kunsai --voice=UxyS2mR9eUefCFboHnRH --dry-run
 */
class FortuneVoiceNormalize extends Command
{
    protected $signature = 'fortune:voice-normalize
        {--persona=* : เฉพาะตัวละคร (ใส่ซ้ำได้) เช่น maemor kunsai busaba}
        {--voice= : เฉพาะ voice_id}
        {--since= : เฉพาะคลิปที่สร้างตั้งแต่วันที่นี้ (Y-m-d หรือ Y-m-d H:i)}
        {--limit=0 : จำนวนคลิปสูงสุดต่อรอบ (0 = ไม่จำกัด)}
        {--dry-run : วัดอย่างเดียว ไม่เขียนทับ}';

    protected $description = '🔊 ปรับความดังคลิปเสียงตัวละครที่เก็บไว้แล้วให้เท่าคลิปห้อง (-19 LUFS) ทับไฟล์เดิม';

    public function handle(VoiceLoudnessNormalizer $normalizer): int
    {
        if (! $normalizer->available()) {
            $this->warn('ไม่พบ ffmpeg (ตั้ง FFMPEG_PATH หรือวางไว้ที่ ~/bin/ffmpeg) — ไม่ได้ปรับคลิปใด ๆ');

            return self::SUCCESS;
        }

        $storage = new FortuneVoiceStorageService(FortuneTellingSetting::getGlobalSettings());
        $driver = $storage->driver();
        $dry = (bool) $this->option('dry-run');

        $query = FortuneCharacterVoiceClip::query()
            ->where('status', FortuneCharacterVoiceClip::STATUS_COMPLETED)
            ->whereNotNull('audio_path')
            ->orderBy('id');
        if ($personas = array_filter((array) $this->option('persona'))) {
            $query->whereIn('persona', $personas);
        }
        if ($voice = $this->option('voice')) {
            $query->where('voice_id', $voice);
        }
        if ($since = $this->option('since')) {
            try {
                $query->where('created_at', '>=', Carbon::parse($since));
            } catch (\Throwable $e) {
                $this->error('รูปแบบ --since ไม่ถูกต้อง: '.$since);

                return self::INVALID;
            }
        }
        $limit = max(0, (int) $this->option('limit'));

        $stats = ['seen' => 0, 'normalized' => 0, 'within' => 0, 'would_normalize' => 0, 'skipped' => 0, 'failed' => 0];
        $tmpDir = storage_path('app/tmp-voice');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $query->chunkById(100, function ($clips) use (&$stats, $limit, $normalizer, $storage, $driver, $dry, $tmpDir) {
            foreach ($clips as $clip) {
                if ($limit > 0 && $stats['seen'] >= $limit) {
                    return false;
                }
                $stats['seen']++;

                if (($clip->audio_disk ?: 'local') !== $driver) {
                    $stats['skipped']++;
                    $this->line("  ข้าม #{$clip->id}: disk {$clip->audio_disk} ≠ driver ปัจจุบัน {$driver}");

                    continue;
                }

                $tmp = $tmpDir.'/renorm-'.$clip->id.'-'.Str::random(8).'.mp3';
                try {
                    if (! $this->fetch($clip, $driver, $tmp)) {
                        $stats['failed']++;
                        $this->line("  ดึงไฟล์ไม่ได้ #{$clip->id}");

                        continue;
                    }

                    $m = $normalizer->measure($tmp);
                    if ($m === null) {
                        $stats['failed']++;

                        continue;
                    }
                    if (VoiceLoudnessNormalizer::withinTarget($m)) {
                        $stats['within']++;

                        continue;
                    }
                    if ($dry) {
                        $stats['would_normalize']++;
                        $this->line(sprintf('  #%d %s %.1f LUFS → %.0f', $clip->id, $clip->persona, $m['input_i'], VoiceLoudnessNormalizer::TARGET_I));

                        continue;
                    }

                    $res = $normalizer->normalizeFile($tmp, false);
                    if ($res['status'] !== 'normalized') {
                        $stats['failed']++;

                        continue;
                    }
                    $stored = $storage->putAudio($tmp, $clip->audio_path);
                    if (! $stored['success']) {
                        $stats['failed']++;
                        $this->line("  เขียนทับไม่สำเร็จ #{$clip->id}");

                        continue;
                    }
                    $stats['normalized']++;
                    $this->line(sprintf('  ✓ #%d %s %.1f → %.0f LUFS', $clip->id, $clip->persona, $m['input_i'], VoiceLoudnessNormalizer::TARGET_I));
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $this->line("  ผิดพลาด #{$clip->id}: ".mb_substr($e->getMessage(), 0, 150));
                } finally {
                    if (is_file($tmp)) {
                        @unlink($tmp);
                    }
                }
            }

            return true;
        });

        $this->info(sprintf(
            '🔊 สรุป: ตรวจ %d · ปรับแล้ว %d · อยู่ในเป้าแล้ว %d%s · ข้าม %d · ล้ม %d',
            $stats['seen'], $stats['normalized'], $stats['within'],
            $dry ? ' · จะปรับ (dry-run) '.$stats['would_normalize'] : '',
            $stats['skipped'], $stats['failed']
        ));

        return self::SUCCESS;
    }

    /** ดึงไฟล์ของคลิปมาไว้ที่ $dest */
    private function fetch(FortuneCharacterVoiceClip $clip, string $driver, string $dest): bool
    {
        if ($driver === 'local') {
            $disk = Storage::disk('public');
            if (! $disk->exists($clip->audio_path)) {
                return false;
            }

            return file_put_contents($dest, $disk->get($clip->audio_path)) > 100;
        }

        if (empty($clip->audio_url)) {
            return false;
        }
        $res = Http::timeout(30)->get($clip->audio_url);

        return $res->successful() && file_put_contents($dest, $res->body()) > 100;
    }
}
