<?php

namespace Tests\Concerns;

/**
 * รัน data migration (migration ที่ใส่ข้อมูลตั้งต้น) ซ้ำในเทสต์
 *
 * ทำไมต้องมี: CI รัน `php artisan migrate` แล้ว `php artisan schema:dump` ก่อนรันเทสต์
 *   dump ใหม่บันทึกว่า migration ทุกตัวรันแล้ว แต่เก็บแค่โครงตาราง ไม่เก็บข้อมูล
 *   → RefreshDatabase โหลด dump แล้วไม่รัน data migration อีก ข้อมูลที่ migration ใส่
 *     (แบนเนอร์เปิดตัว ค่า pricing เริ่มต้น) จึงไม่มีในฐานข้อมูลเทสต์บน CI
 *   ในเครื่อง dump ใน repo เก่ากว่า migration พวกนี้ มันจึงรันและเทสต์ผ่าน — ผลต่างกันตามที่รัน
 *
 * data migration ที่เรียกผ่านตัวนี้ต้อง idempotent (ข้ามแถวที่มีอยู่แล้ว) และไม่แก้โครงตาราง
 * ระหว่างเทสต์ (DDL ใน MySQL commit ทันที หลุดจาก transaction ของ RefreshDatabase)
 */
trait RunsDataMigrations
{
    /**
     * @param  string  $file  ชื่อไฟล์ใน database/migrations เช่น '2026_09_26_133100_seed_launch_app_campaign_banners.php'
     */
    protected function runDataMigration(string $file): void
    {
        (require database_path('migrations/'.$file))->up();
    }
}
