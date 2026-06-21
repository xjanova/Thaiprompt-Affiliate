<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_system_voice_clips
     *
     * คลังเสียง "ระบบ" (ข้อความกลางตายตัว) ที่สร้างไฟล์เสียงเก็บไว้ล่วงหน้า
     * แล้ว reuse ส่งให้ลูกค้าทุกคน — ไม่ต้องสร้าง TTS ใหม่ทุกครั้ง
     */
    public function up(): void
    {
        // ✅ SAFE: CREATE TABLE — เช็คก่อนสร้างได้
        if (Schema::hasTable('fortune_system_voice_clips')) {
            return;
        }

        Schema::create('fortune_system_voice_clips', function (Blueprint $table) {
            $table->id();

            // key เฉพาะของคลิป (slug) — ใช้เรียกในโค้ดจุดส่งต่างๆ
            $table->string('clip_key', 64)->unique()->comment('slug เรียกใช้ในโค้ด เช่น howto_birthdate');

            // กลุ่มขั้นตอน (ไว้จัดกลุ่มในหน้าแอดมิน)
            $table->string('step_group', 32)->default('other')->comment('sales_nudge / consent / birthdate / payment / welcome / celtic / other');

            $table->string('title')->comment('ชื่อแสดงในหน้าแอดมิน');
            $table->text('description')->nullable()->comment('คำอธิบายว่าเสียงนี้ใช้ตอนไหน');

            // ข้อความที่จะสังเคราะห์เป็นเสียง (admin แก้ได้)
            $table->text('script_text')->comment('ข้อความกลางที่จะสร้างเป็นเสียง');

            // เปิด/ปิดการส่งเสียงตัวนี้ (เลือกได้ว่าจะส่งอันไหน)
            $table->boolean('enabled')->default(true)->comment('true = ส่งเสียงตัวนี้เมื่อถึงจุด (ขึ้นกับ master toggle อีกชั้น)');

            $table->unsignedInteger('sort_order')->default(0);

            // metadata ไฟล์เสียงที่สร้างไว้ (เก็บ local server เท่านั้น)
            $table->string('audio_path')->nullable()->comment('path บน public disk เช่น fortune-voice/system/howto_birthdate.mp3');
            $table->string('audio_url')->nullable()->comment('public URL ของไฟล์เสียง');
            $table->unsignedInteger('audio_duration_ms')->nullable();
            $table->string('audio_provider', 32)->nullable()->comment('provider ที่ใช้สร้าง (gtts/minimax/...)');
            $table->string('audio_voice_id', 64)->nullable()->comment('voice id ที่ใช้สร้าง');
            $table->unsignedInteger('audio_chars')->nullable();
            $table->timestamp('generated_at')->nullable();

            // override การตั้งค่าเสียงเฉพาะคลิป (provider/voice/speed) — null = ใช้ค่า global
            $table->json('voice_config')->nullable()->comment('override provider/voice/speed เฉพาะคลิปนี้');

            $table->timestamps();

            $table->index('step_group');
            $table->index('enabled');
        });
    }

    /**
     * ลบตาราง fortune_system_voice_clips
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_system_voice_clips');
    }
};
