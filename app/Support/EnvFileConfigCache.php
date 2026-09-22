<?php

namespace App\Support;

/**
 * ลบ config cache หลังโค้ดเขียนไฟล์ .env — ให้ค่าที่แอดมินเพิ่งบันทึกมีผลทันที
 *
 * ทำไมต้องมี (2026-09-22): deploy.sh เคย cache config แล้วโดน `composer dump-autoload`
 * (hook ComposerScripts::clearCompiled) ลบทิ้งทุกรอบ prod เลยไม่เคยมี config cache และ
 * หน้าแอดมินที่เขียน .env ก็ทำงานได้เพราะทุกคำขออ่าน .env ใหม่เอง พอแก้ให้ cache อยู่รอด
 * ค่าใหม่ใน .env จะไม่มีผลจนกว่าจะ cache รอบหน้า ถ้าไม่ลบ cache ตรงนี้
 *
 * ลบไฟล์เฉย ๆ ไม่สั่ง config:cache ในคำขอเว็บ — config:cache สร้าง Application ตัวที่สอง
 * แล้วสลับ Facade ไปชี้ตัวใหม่กลางคำขอ ผลคือเว็บกลับไปอ่าน .env ทุกคำขอ (ช้าลง ~100ms)
 * จนกว่า deploy รอบถัดไปจะ cache ให้ใหม่
 */
final class EnvFileConfigCache
{
    public static function forget(): void
    {
        $path = app()->getCachedConfigPath();

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
