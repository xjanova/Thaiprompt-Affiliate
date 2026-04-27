<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ════════════════════════════════════════════════════════════════
// 🔮 Daily Horoscope Auto-Post — โพสดวงประจำวัน 7 วันเกิด
// ════════════════════════════════════════════════════════════════
// 01:00 → จันทร์ | 02:00 → อังคาร | ... | 07:00 → อาทิตย์
// (Timezone: Asia/Bangkok ตาม config('app.timezone'))
//
// withoutOverlapping(15) — กัน overlap 15 นาที (เผื่อ AI ช้า)
// onOneServer() — กัน multi-server รันซ้ำ
foreach (range(1, 7) as $dayOfBirth) {
    $hour = $dayOfBirth; // 1=01:00, 2=02:00, ..., 7=07:00

    Schedule::command("fortune:daily-horoscope:publish {$dayOfBirth}")
        ->dailyAt(sprintf('%02d:00', $hour))
        ->timezone('Asia/Bangkok')
        ->withoutOverlapping(15)
        ->onOneServer()
        ->name("daily-horoscope-day-{$dayOfBirth}")
        ->runInBackground();
}
