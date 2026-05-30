<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_contact_signals
     *
     * เก็บ "พฤติกรรมการส่งข้อความ" ของแต่ละ contact (Facebook PSID / LINE userId)
     * เพื่อตรวจจับคนที่ "ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุยจริง" → candidate สำหรับแบน
     *
     * ⚠️ ไม่เก็บเนื้อหาแชตเต็ม — เก็บแค่ counter + ตัวอย่างล่าสุด (เคารพ privacy)
     */
    public function up(): void
    {
        // ✅ SAFE: CREATE TABLE → เช็คตารางก่อนสร้างได้
        if (Schema::hasTable('fortune_contact_signals')) {
            return;
        }

        Schema::create('fortune_contact_signals', function (Blueprint $table) {
            $table->id();

            // platform + ผู้ใช้
            $table->string('platform', 20)->index();   // facebook | line
            $table->string('platform_user_id', 191);   // PSID / LINE userId
            $table->string('display_name')->nullable();

            // ตัวนับพฤติกรรม
            $table->unsignedInteger('inbound_total')->default(0);     // ข้อความขาเข้าทั้งหมด
            $table->unsignedInteger('link_image_count')->default(0);  // ลิงก์ภายนอก/รูป/วิดีโอ (ไม่นับ sticker)
            $table->unsignedInteger('real_text_count')->default(0);   // พิมพ์คุยจริง (มีคำ ไม่ใช่ลิงก์ล้วน)
            $table->unsignedInteger('interaction_count')->default(0); // กดปุ่ม/quick-reply/คุยจริง = engagement

            // ตัวอย่างล่าสุด (ให้แอดมินดูประกอบการตัดสินใจ)
            $table->text('last_sample')->nullable();

            // สถานะ
            $table->boolean('whitelisted')->default(false)->index();    // เคยคุยจริง/เคยจ่าย → ห้ามแบนอัตโนมัติ
            $table->string('status', 20)->default('tracking')->index(); // tracking | flagged | banned | cleared

            // เวลา
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->timestamp('banned_at')->nullable();

            $table->timestamps();

            // 1 contact = 1 แถว
            $table->unique(['platform', 'platform_user_id'], 'fcs_platform_user_unique');
        });
    }

    /**
     * ลบตาราง fortune_contact_signals
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_contact_signals');
    }
};
