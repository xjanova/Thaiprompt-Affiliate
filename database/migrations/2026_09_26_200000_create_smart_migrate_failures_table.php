<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางจำ "migration ที่ล้มใน migrate:smart" ข้าม deploy (app/Console/Commands/SmartMigrate.php)
 *
 * ทำไมต้องจำ: MySQL/MariaDB ไม่มี transaction ครอบ DDL — migration ที่ล้มกลางทางอาจสร้างตาราง/คอลัมน์ไปแล้วครึ่งหนึ่ง
 *   deploy รอบถัดไปรัน up() ซ้ำ ถ้า up() มี guard ทั้งก้อน (hasTable → return) มันจะไม่ทำอะไรเลย
 *   แล้วถูกบันทึกว่ารันแล้วทั้งที่ส่วนที่เหลือ (FK/index/ข้อมูล) ไม่เคยถูกสร้าง
 *   ⇒ migrate:smart จำเฉพาะตัวที่ล้ม "หลังเปลี่ยนฐานข้อมูลไปแล้วบางส่วน" แล้วปฏิเสธการบันทึกรอบซ้ำที่ไม่ได้เปลี่ยนอะไรเลย
 *   (ลบแถวของตัวนั้นทิ้ง = ยอมรับ schema ปัจจุบัน ให้รอบหน้าบันทึกได้ตามปกติ)
 *
 * ไฟล์ใน storage/ ใช้ไม่ได้ เพราะ deploy.sh `git clean -fdx` ลบทิ้งทุกรอบก่อนถึงขั้น migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('smart_migrate_failures')) {
            return;
        }

        Schema::create('smart_migrate_failures', function (Blueprint $table) {
            $table->id();
            $table->string('migration')->unique()->comment('ชื่อไฟล์ migration (ไม่มี .php)');
            $table->text('error')->comment('error ของการล้มครั้งแรก');
            $table->unsignedInteger('attempts')->default(1)->comment('จำนวนครั้งที่ล้ม/ถูกปฏิเสธ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_migrate_failures');
    }
};
