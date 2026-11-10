<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * 🔊 (2026-11-10) ปรับความดังคลิปเสียงตัวละครให้เท่ากันทุกคลิป — ffmpeg loudnorm 2 รอบ
 *
 * ทำไม: เสียง "บัว" ดิบจาก ElevenLabs วัดได้ -24..-31 LUFS แต่บทพูดประจำห้อง (static) ปรับไว้ที่ -19 LUFS
 *   ⇒ ฟังแล้วเสียงพากย์ที่สร้างสด ๆ เบากว่าคลิปห้องชัด ๆ · ใช้เป้าเดียวกับคลิปห้อง: I=-19 TP=-2 LRA=11, 44.1 kHz mono, libmp3lame 96k
 *
 * หาไบนารี: config('app.ffmpeg_path') (FFMPEG_PATH) ถ้าเป็นไฟล์ที่รันได้ หรือชื่อที่หาเจอใน PATH
 *   → ไม่เจอ ลอง ~/bin/ffmpeg ของ user PHP และ /home/admin/bin/ffmpeg (prod วาง ffmpeg static ไว้ตรงนั้น ไม่อยู่ใน PATH)
 *
 * ⚠️ กฎเหล็ก: ปรับเสียงไม่ได้ (ไม่มี ffmpeg / รันล้ม / ไฟล์เสีย) = คืนไฟล์เดิมไว้เหมือนเดิม ห้ามทำให้คลิปล้ม
 *   ไม่มี ffmpeg → log ครั้งเดียวต่อ process (ไม่ใช่ทุกคลิป)
 */
class VoiceLoudnessNormalizer
{
    /** เป้าความดัง (LUFS) — ตรงกับคลิปประจำห้องของเว็บ */
    public const TARGET_I = -19.0;

    public const TARGET_TP = -2.0;

    public const TARGET_LRA = 11.0;

    public const SAMPLE_RATE = 44100;

    public const BITRATE = '96k';

    /** ห่างเป้าไม่เกินนี้ (LU) = ถือว่าปรับแล้ว ไม่ต้องเข้ารหัสซ้ำ */
    public const TOLERANCE_LU = 1.0;

    /** เวลาสูงสุดต่อคำสั่ง ffmpeg (วินาที) — คลิปพูดสั้น ๆ ไม่กี่วินาที */
    private const TIMEOUT_SEC = 60;

    private static bool $missingLogged = false;

    /** null = ยังไม่ได้หา · false = หาแล้วไม่เจอ */
    private string|false|null $binary = null;

    /**
     * @param  array<int, string>|null  $fallbacks  ตำแหน่งสำรองเมื่อค่าใน config ใช้ไม่ได้ (null = ~/bin/ffmpeg + /home/admin/bin/ffmpeg)
     */
    public function __construct(private ?array $fallbacks = null) {}

    /** ตัวเลือก loudnorm ที่ใช้ทั้งรอบวัดและรอบปรับ */
    public static function targetFilter(): string
    {
        return sprintf('loudnorm=I=%s:TP=%s:LRA=%s', self::TARGET_I, self::TARGET_TP, self::TARGET_LRA);
    }

    /** path ของ ffmpeg ที่ใช้ได้ หรือ null */
    public function binary(): ?string
    {
        if ($this->binary === null) {
            $this->binary = $this->resolveBinary() ?? false;
        }

        return $this->binary === false ? null : $this->binary;
    }

    public function available(): bool
    {
        return $this->binary() !== null;
    }

    /**
     * วัดความดัง (loudnorm รอบแรก print_format=json)
     *
     * @return array{input_i: float, input_tp: float, input_lra: float, input_thresh: float, target_offset: float}|null
     */
    public function measure(string $path): ?array
    {
        $bin = $this->binary();
        if ($bin === null || ! is_file($path)) {
            return null;
        }

        $process = new Process([$bin, '-hide_banner', '-nostats', '-i', $path, '-af', self::targetFilter().':print_format=json', '-f', 'null', '-']);
        $process->setTimeout(self::TIMEOUT_SEC);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::warning('🔊 วัดความดังเสียงไม่สำเร็จ', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return null;
        }
        if (! $process->isSuccessful()) {
            return null;
        }

        // ffmpeg พิมพ์ JSON ก้อนสุดท้ายลง stderr
        $out = $process->getErrorOutput().$process->getOutput();
        if (! preg_match_all('/\{[^{}]*"input_i"[^{}]*\}/s', $out, $m)) {
            return null;
        }
        $json = json_decode((string) end($m[0]), true);
        if (! is_array($json)) {
            return null;
        }

        $vals = [];
        foreach (['input_i', 'input_tp', 'input_lra', 'input_thresh', 'target_offset'] as $k) {
            if (! isset($json[$k]) || ! is_numeric($json[$k])) {
                return null; // เช่น "-inf" ของไฟล์เงียบ — ไม่ปรับ
            }
            $vals[$k] = (float) $json[$k];
        }

        return $vals;
    }

    /** ความดังอยู่ในช่วงเป้าแล้วหรือยัง (กันเข้ารหัสซ้ำ) */
    public static function withinTarget(array $m): bool
    {
        return abs($m['input_i'] - self::TARGET_I) <= self::TOLERANCE_LU
            && $m['input_tp'] <= self::TARGET_TP + self::TOLERANCE_LU;
    }

    /**
     * ปรับความดังไฟล์ mp3 ทับที่เดิม
     *
     * @param  bool  $skipIfWithin  true = ถ้าความดังอยู่ในเป้าแล้วไม่เข้ารหัสซ้ำ
     * @return array{status: 'normalized'|'within'|'unavailable'|'failed', before: ?float}
     *                                                                                     unavailable/failed = ไฟล์เดิมไม่ถูกแตะ
     */
    public function normalizeFile(string $path, bool $skipIfWithin = true): array
    {
        $bin = $this->binary();
        if ($bin === null) {
            if (! self::$missingLogged) {
                self::$missingLogged = true;
                Log::warning('🔊 ไม่พบ ffmpeg — ข้ามการปรับความดังเสียงตัวละคร (ตั้ง FFMPEG_PATH เพื่อเปิด)');
            }

            return ['status' => 'unavailable', 'before' => null];
        }

        $m = $this->measure($path);
        if ($m === null) {
            Log::warning('🔊 วัดความดังไม่ได้ — คงไฟล์เดิม', ['file' => basename($path)]);

            return ['status' => 'failed', 'before' => null];
        }
        if ($skipIfWithin && self::withinTarget($m)) {
            return ['status' => 'within', 'before' => $m['input_i']];
        }

        $tmp = $path.'.norm-'.bin2hex(random_bytes(4)).'.mp3';
        $filter = self::targetFilter().sprintf(
            ':measured_I=%s:measured_TP=%s:measured_LRA=%s:measured_thresh=%s:offset=%s:linear=true:print_format=summary',
            $m['input_i'], $m['input_tp'], $m['input_lra'], $m['input_thresh'], $m['target_offset']
        );
        $process = new Process([
            $bin, '-hide_banner', '-nostats', '-loglevel', 'error', '-y', '-i', $path,
            '-af', $filter, '-ar', (string) self::SAMPLE_RATE, '-ac', '1',
            '-c:a', 'libmp3lame', '-b:a', self::BITRATE, $tmp,
        ]);
        $process->setTimeout(self::TIMEOUT_SEC);

        try {
            $process->run();
            if (! $process->isSuccessful() || ! is_file($tmp) || filesize($tmp) < 100) {
                Log::warning('🔊 ปรับความดังเสียงไม่สำเร็จ — คงไฟล์เดิม', [
                    'file' => basename($path),
                    'error' => mb_substr($process->getErrorOutput(), 0, 200),
                ]);

                return ['status' => 'failed', 'before' => $m['input_i']];
            }
            if (! @rename($tmp, $path)) {
                // Windows ทับไฟล์ที่มีอยู่ด้วย rename ไม่ได้บางกรณี — คัดลอกทับแทน
                if (! @copy($tmp, $path)) {
                    return ['status' => 'failed', 'before' => $m['input_i']];
                }
            }

            return ['status' => 'normalized', 'before' => $m['input_i']];
        } catch (\Throwable $e) {
            Log::warning('🔊 ปรับความดังเสียงล้ม — คงไฟล์เดิม', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return ['status' => 'failed', 'before' => $m['input_i']];
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function resolveBinary(): ?string
    {
        $configured = trim((string) config('app.ffmpeg_path', 'ffmpeg'));
        if ($configured !== '') {
            if (preg_match('#[/\\\\]#', $configured)) {
                if (@is_file($configured) && @is_executable($configured)) {
                    return $configured;
                }
            } else {
                $found = (new ExecutableFinder)->find($configured);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);
        $fallbacks = $this->fallbacks ?? array_values(array_filter([
            $home ? rtrim((string) $home, '/').'/bin/ffmpeg' : null,
            '/home/admin/bin/ffmpeg',
        ]));
        foreach ($fallbacks as $c) {
            if (@is_file($c) && @is_executable($c)) {
                return $c;
            }
        }

        return null;
    }
}
