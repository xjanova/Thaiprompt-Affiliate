<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔐 (2026-11-03 รีวิว) สร้างตาราง fortune_web_link_uses — jti ของ Magic Link ที่ใช้ไปแล้ว
     *
     * เดิมเก็บใน Cache ⇒ deploy.sh เรียก cache:clear (Redis flushdb) ทุกรอบ
     * ลิงก์ที่ใช้ไปแล้วแต่ยังไม่หมดอายุกลับมาล็อกอินได้อีก — ย้ายมาเก็บใน DB (jti unique)
     * gateway ใช้ insertOrIgnore เป็นตัวตัดสิน "ใช้ครั้งเดียว" · prune แถวที่หมดอายุเกิน 1 วัน
     */
    public function up(): void
    {
        // ✅ CREATE TABLE — เช็คตารางก่อนสร้าง
        if (Schema::hasTable('fortune_web_link_uses')) {
            return;
        }

        Schema::create('fortune_web_link_uses', function (Blueprint $table) {
            $table->id();
            // jti ของ token (32 hex) — unique = ตัวตัดสิน single-use
            $table->char('jti', 32)->unique();
            // users.id ที่ token นี้ล็อกอินให้ (null = กำลังใช้อยู่)
            $table->unsignedBigInteger('user_id')->nullable();
            // หมดอายุของ token — ใช้ prune
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * ลบตาราง fortune_web_link_uses
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_web_link_uses');
    }
};
