<?php

namespace Database\Seeders;

use App\Support\FortuneVoiceDefaults;
use Illuminate\Database\Seeder;

/**
 * 🎭 ตัวละครเสียงพากย์ (แม่หมอ/บุษบา/คุณไสย) + คลังบทพูดเริ่มต้น — ใส่เฉพาะที่ยังไม่มี
 */
class FortuneVoiceLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('🌱 กำลัง seed ตัวละครเสียงพากย์ + คลังบทพูด...');

        FortuneVoiceDefaults::seed();

        $this->command?->info('✅ Seed ตัวละครเสียงพากย์สำเร็จ!');
    }
}
