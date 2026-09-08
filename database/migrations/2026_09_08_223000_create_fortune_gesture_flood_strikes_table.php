<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_gesture_flood_strikes — ประวัติ "ยิงสติกเกอร์/อีโมจิรัว" ของลูกค้าแต่ละคน
     *
     * 🚨 ทำไมต้องมีตารางแยกจาก fortune_nav_flood_strikes:
     *   คนละพฤติกรรม คนละเกณฑ์ และ NavFloodGuard ยังปิดอยู่ (mode=log_only)
     *   ถ้าใช้แถวร่วมกัน strike ของสองด่านจะทับกัน — คนกดปุ่มรัวจะพาคนส่งสติกเกอร์โดนแบนไปด้วย
     *
     * 🚨 ทำไมต้องอยู่ DB ไม่ใช่ Cache:
     *   deploy.sh รัน `cache:clear` ทุกครั้ง ⇒ ถ้าเก็บ strike ใน Cache
     *   คนป่วนจะได้รีเซ็ตประวัติฟรีทุกครั้งที่เราพุชโค้ด
     *
     *   ตัวนับ "ความถี่ระยะสั้น" (กี่ใบใน 5 นาที) ยังอยู่ Cache ได้ — หายแล้วไม่เสียหาย
     *   แต่ "ประวัติความผิด" ที่ใช้ตัดสินว่าจะระงับ 7 วันหรือไม่ ต้องอยู่ถาวร
     *
     * เคสจริงที่ทำให้ต้องมีตารางนี้ (prod 2026-09-08 21:40-21:57):
     *   PSID 26308980832136739 (แสนที เล็ก) เปิดบิล FTU-260908-Y1018 (deep 39฿) แล้วไม่จ่าย
     *   ยิงข้อความ 113 ครั้งใน 15 นาที — 73 ใบเป็นสติกเกอร์/อีโมจิล้วน
     *   บอทถูกลากตอบกลับ 94 ข้อความ (action=waiting_payment 156 ครั้ง)
     *   ไม่มีด่านไหนแตะได้เลย เพราะ "มีบิลค้าง" = ได้เกราะ active flow
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_gesture_flood_strikes')) {
            return;
        }

        Schema::create('fortune_gesture_flood_strikes', function (Blueprint $table) {
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

            $table->unsignedInteger('total_hits')->default(0)->comment('นับสะสมทั้งชีวิต — ไว้ดูสถิติว่าใครยิงเยอะสุด');
            $table->string('last_sample', 120)->nullable()->comment('ตัวอย่างล่าสุดที่ยิงมา — ไว้ยืนยันว่าไม่ใช่ false-positive');

            $table->timestamps();

            // 1 แถวต่อ 1 ลูกค้าต่อ 1 ช่องทาง
            $table->unique(['platform', 'platform_user_id'], 'gesture_flood_user_unique');
            $table->index('last_hit_at', 'gesture_flood_last_hit_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_gesture_flood_strikes');
    }
};
