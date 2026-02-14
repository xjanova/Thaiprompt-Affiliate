<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง line_recruitment_topic_boundaries
 *
 * ใช้สำหรับกำหนดขอบเขตหัวข้อที่ AI สามารถคุยได้
 * - หัวข้อที่อนุญาต (allowed)
 * - หัวข้อที่ห้าม (blocked)
 * - ข้อความตอบกลับเมื่อคุยเรื่องที่ห้าม
 */
return new class extends Migration
{
    /**
     * สร้างตาราง line_recruitment_topic_boundaries
     */
    public function up(): void
    {
        if (Schema::hasTable('line_recruitment_topic_boundaries')) {
            return;
        }

        Schema::create('line_recruitment_topic_boundaries', function (Blueprint $table) {
            $table->id();

            // เชื่อมกับ AI Setting
            $table->foreignId('ai_setting_id')
                ->constrained('line_bot_ai_settings')
                ->onDelete('cascade');

            // ชื่อหัวข้อ
            $table->string('name');

            // คำอธิบายหัวข้อ
            $table->text('description')->nullable();

            // ประเภท: allowed (อนุญาต) หรือ blocked (ห้าม)
            $table->enum('type', ['allowed', 'blocked'])->default('allowed');

            // คำหลักที่ใช้ตรวจจับ (JSON array)
            $table->json('keywords')->nullable();

            // ข้อความตอบกลับเมื่อพบหัวข้อนี้ (สำหรับ blocked)
            $table->text('response_message')->nullable();

            // ลำดับความสำคัญ
            $table->integer('priority')->default(0);

            // สถานะใช้งาน
            $table->boolean('is_active')->default(true);

            // ตัวอย่างประโยคที่เกี่ยวข้อง (JSON array)
            $table->json('example_phrases')->nullable();

            // จำนวนครั้งที่ถูกตรวจจับ
            $table->integer('hit_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ai_setting_id');
            $table->index('type');
            $table->index('is_active');
            $table->index(['ai_setting_id', 'type', 'is_active'], 'topic_active_idx');
        });
    }

    /**
     * ลบตาราง line_recruitment_topic_boundaries
     */
    public function down(): void
    {
        Schema::dropIfExists('line_recruitment_topic_boundaries');
    }
};
