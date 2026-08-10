<?php

use App\Support\ActivityLogger;

if (! function_exists('activity')) {
    /**
     * 📝 เริ่มบันทึกกิจกรรม — API เข้ากันได้กับ spatie/laravel-activitylog
     *
     * ใช้แบบเดียวกับเทมเพลตใน CLAUDE.md:
     *   activity()->performedOn($model)->causedBy($user)->withProperties([...])->log('ข้อความ');
     *
     * 🚨 ห่อด้วย function_exists โดยตั้งใจ — ถ้าวันหนึ่งติดตั้ง
     *    spatie/laravel-activitylog จริง ตัวของแพคเกจจะชนะและไฟล์นี้จะไม่ทำอะไร
     *    (composer โหลด autoload.files ของแพคเกจก่อนของโปรเจกต์)
     *    เหตุผลเต็ม ๆ อยู่ใน App\Support\ActivityLogger
     */
    function activity(?string $logName = null): ActivityLogger
    {
        return new ActivityLogger($logName);
    }
}
