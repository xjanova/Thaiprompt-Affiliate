<?php

namespace Tests\Feature;

use App\Services\Fortune\VoiceLoudnessNormalizer;
use Tests\TestCase;

/**
 * 🔊 (2026-11-10) ปรับความดังคลิปเสียงตัวละคร — ทางที่ไม่มี ffmpeg ต้องปลอดภัยเสมอ
 *
 * ล็อกไว้:
 *   1. หา ffmpeg ไม่เจอ (config ชี้ไฟล์ที่ไม่มี + ปิดตำแหน่งสำรอง) = available() false
 *   2. normalizeFile คืน "unavailable" และไม่แตะไฟล์เดิมเลย
 *   3. คำสั่ง fortune:voice-normalize จบเงียบ ๆ exit 0 ไม่แตะคลิปใด ๆ (ไม่ต้องมีตาราง)
 *   4. เป้าความดังอยู่ที่ค่าคงที่เดียว: I=-19 TP=-2 LRA=11
 */
class FortuneVoiceNormalizeCommandTest extends TestCase
{
    private function missingFfmpeg(): VoiceLoudnessNormalizer
    {
        config(['app.ffmpeg_path' => base_path('no-such-dir/ffmpeg-missing')]);

        return new VoiceLoudnessNormalizer([]);
    }

    public function test_normalizer_leaves_the_file_alone_when_ffmpeg_is_missing(): void
    {
        $normalizer = $this->missingFfmpeg();
        $this->assertFalse($normalizer->available());
        $this->assertNull($normalizer->binary());

        $path = tempnam(sys_get_temp_dir(), 'vln');
        $bytes = str_repeat("\xFF\xFB\x90\x00", 400);
        file_put_contents($path, $bytes);
        try {
            $res = $normalizer->normalizeFile($path);
            $this->assertSame('unavailable', $res['status']);
            $this->assertNull($res['before']);
            $this->assertSame($bytes, file_get_contents($path));
            $this->assertNull($normalizer->measure($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_command_exits_cleanly_when_ffmpeg_is_missing(): void
    {
        $this->app->instance(VoiceLoudnessNormalizer::class, $this->missingFfmpeg());

        $this->artisan('fortune:voice-normalize', ['--persona' => ['maemor', 'kunsai'], '--dry-run' => true])
            ->expectsOutputToContain('ไม่พบ ffmpeg')
            ->assertExitCode(0);
    }

    public function test_target_lives_in_one_place(): void
    {
        $this->assertSame('loudnorm=I=-19:TP=-2:LRA=11', VoiceLoudnessNormalizer::targetFilter());
        $this->assertTrue(VoiceLoudnessNormalizer::withinTarget(['input_i' => -19.6, 'input_tp' => -2.5]));
        $this->assertFalse(VoiceLoudnessNormalizer::withinTarget(['input_i' => -27.0, 'input_tp' => -9.0]), 'คลิปเบา -27 LUFS ต้องถูกปรับ');
    }
}
