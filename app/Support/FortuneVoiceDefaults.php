<?php

namespace App\Support;

use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\JuntraVoiceLibrary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🎭 (2026-11-04) ค่าเริ่มต้นของตัวละครเสียงพากย์ + คลังบทพูด — ใช้ทั้งใน migration (prod ไม่รัน seeder) และ seeder
 *
 * ใส่เฉพาะที่ยังไม่มี (insertOrIgnore ตาม key) — รันซ้ำกี่รอบก็ได้ ไม่ทับค่าที่แอดมินแก้ไว้
 */
final class FortuneVoiceDefaults
{
    public static function seed(): void
    {
        if (! Schema::hasTable('fortune_voice_characters') || ! Schema::hasTable('fortune_voice_lines')) {
            return;
        }

        $now = now();
        $order = 0;
        foreach (FortuneCharacterVoiceService::PERSONAS as $key => $persona) {
            DB::table('fortune_voice_characters')->insertOrIgnore([
                'key' => $key,
                'label' => $persona['label'],
                'brief' => $persona['brief'],
                'sample_text' => $persona['sample_text'],
                // ว่าง = อ่านเสียงที่เลือกไว้เดิมจากคอลัมน์ {persona}_voice_id ของค่ากลาง (FortuneCharacterVoiceService::voiceFor)
                'voice_id' => null,
                'model' => null,
                'is_builtin' => true,
                'is_active' => true,
                'sort_order' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (JuntraVoiceLibrary::DEFAULT_CLIPS as $persona => $clips) {
            $order = 0;
            foreach ($clips as $lineKey => $script) {
                DB::table('fortune_voice_lines')->insertOrIgnore([
                    'persona' => $persona,
                    'line_key' => $lineKey,
                    'category' => JuntraVoiceLibrary::categoryOf($lineKey),
                    'script' => $script,
                    'is_active' => true,
                    'sort_order' => $order++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
