<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_nav_flood_strikes — ประวัติ "กดปุ่มรัว" ของลูกค้าแต่ละคน
     *
     * 🚨 ทำไมต้องอยู่ DB ไม่ใช่ Cache:
     *   deploy.sh รัน `cache:clear` ทุกครั้ง ⇒ ถ้าเก็บ strike ใน Cache
     *   คนป่วนจะได้รีเซ็ตฟรีทุกครั้งที่เราพุชโค้ด (บทเรียนเดียวกับ fb_last_inbound ที่หายตอน deploy)
     *
     *   ตัวนับ "ความถี่ระยะสั้น" (กี่ครั้งใน 2 นาที) ยังอยู่ Cache ได้ — หายแล้วไม่เสียหาย
     *   แต่ "ประวัติความผิด" ที่ใช้ตัดสินว่าจะระงับ 7 วันหรือไม่ ต้องอยู่ถาวร
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_nav_flood_strikes')) {
            return;
        }

        Schema::create('fortune_nav_flood_strikes', function (Blueprint $table) {
            $table->id();

            $table->string('platform', 20)->comment('facebook | line');
            $table->string('platform_user_id', 191)->comment('FB PSID / LINE userId');
            $table->string('display_name', 191)->nullable()->comment('ชื่อที่แสดง (snapshot ไว้ดูย้อนหลัง)');

            $table->unsignedTinyInteger('strikes')->default(0)->comment('จำนวนครั้งที่แตะเกณฑ์ในหน้าต่างปัจจุบัน');
            $table->timestamp('window_started_at')->nullable()->comment('เริ่มนับหน้าต่าง 24 ชม. เมื่อไหร่');
            $table->timestamp('last_hit_at')->nullable()->comment('แตะเกณฑ์ครั้งล่าสุดเมื่อไหร่');

            $table->unsignedTinyInteger('warned_count')->default(0)->comment('ส่งคำเตือนไปแล้วกี่ครั้งในหน้าต่างนี้');
            $table->timestamp('last_warned_at')->nullable();
            $table->timestamp('banned_at')->nullable()->comment('ระงับ 7 วันเมื่อไหร่ (null = ยังไม่เคย)');

            $table->string('last_payload', 100)->nullable()->comment('ปุ่มล่าสุดที่กดรัว — ไว้ดูว่าปุ่มไหนมีปัญหา');

            $table->timestamps();

            // 1 แถวต่อ 1 ลูกค้าต่อ 1 ช่องทาง
            $table->unique(['platform', 'platform_user_id'], 'nav_flood_user_unique');
            $table->index('last_hit_at', 'nav_flood_last_hit_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_nav_flood_strikes');
    }
};
