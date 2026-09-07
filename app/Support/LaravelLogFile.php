<?php

namespace App\Support;

/**
 * หาไฟล์ log ของ Laravel "ตัวปัจจุบัน" ไม่ว่าจะตั้ง LOG_CHANNEL เป็น single หรือ daily
 *
 * ทำไมต้องมี (2026-09-07): เปลี่ยน prod เป็น LOG_CHANNEL=daily เพื่อให้ log หมุนไฟล์รายวัน
 * (เดิม single = laravel.log ไฟล์เดียวโตไม่มีเพดาน และ deploy.sh ต้องลบทิ้งทุกรอบ ~10 ครั้ง/วัน
 * ทำให้ไม่เหลือร่องรอย error ให้ไล่ย้อน) — แต่มีหน้าแอดมิน 2 จุดที่อ่าน storage/logs/laravel.log
 * ด้วยชื่อตายตัว ถ้าไม่ผ่านตัวนี้จะไปอ่านไฟล์เก่าที่ไม่มีใครเขียนแล้ว
 *
 * กติกา: เลือกไฟล์ laravel*.log ที่ "แก้ไขล่าสุด" ถ้าไม่มีเลยคืน laravel.log (พาธเดิม) เพื่อให้
 * โค้ดฝั่งเรียกใช้ที่เช็ค file_exists() ทำงานเหมือนเดิม
 */
final class LaravelLogFile
{
    /**
     * พาธไฟล์ log ที่กำลังถูกเขียนอยู่ตอนนี้
     */
    public static function current(): string
    {
        $fallback = storage_path('logs/laravel.log');

        $candidates = glob(storage_path('logs/laravel*.log')) ?: [];
        if ($candidates === []) {
            return $fallback;
        }

        // ไฟล์ที่ mtime ใหม่สุด = ไฟล์ที่ channel ปัจจุบันเขียนอยู่ (daily จะเป็น laravel-YYYY-MM-DD.log ของวันนี้)
        usort($candidates, static fn (string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));

        return $candidates[0];
    }
}
