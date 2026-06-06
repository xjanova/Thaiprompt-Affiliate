<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_consent_images
 *
 * คลังรูป "กติกาก่อนจองคิว" — ส่งให้ลูกค้าพร้อมคำเตือน 2 จังหวะ:
 *   1. ตอนแสดงกติกาก่อนสร้างบิล QR (consent gate)
 *   2. ตอนลูกค้าสร้างบิลแล้วกดยกเลิก (เตือนสติ — เฉพาะเจตนาเบี้ยว ไม่ใช่เหตุสุดวิสัย)
 *
 * แอดมินอัปโหลด/จัดเรียง/เปิด-ปิด/สุ่มได้ + เลือก scope ต่อรูป (consent/cancel/both)
 * (clone โครงจาก fortune_banners)
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_consent_images
     */
    public function up(): void
    {
        // ✅ CREATE TABLE — ใช้ hasTable + return ได้ (CLAUDE.md กฎทอง #1)
        if (Schema::hasTable('fortune_consent_images')) {
            return;
        }

        Schema::create('fortune_consent_images', function (Blueprint $table) {
            $table->id();

            // ชื่อ + คำอธิบาย (ภายในแอดมิน)
            $table->string('name', 100);
            $table->string('description', 500)->nullable();

            // 🎯 ใช้รูปนี้ตอนไหน — consent (อ่านกติกา) | cancel (ตอนยกเลิก) | both (ทั้งคู่)
            $table->string('usage_scope', 20)->default('both');

            // ไฟล์ภาพ (เก็บที่ storage/app/public/fortune/consent/)
            $table->string('image_path', 500);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // bytes

            // จัดเรียง + เปิดปิดรายตัว
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // สถิติการใช้งาน
            $table->unsignedInteger('send_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            // Tracking
            $table->unsignedBigInteger('uploaded_by')->nullable(); // user_id ของ admin ที่ upload

            $table->timestamps();
            $table->softDeletes();

            // index สั้น (< 50 chars)
            $table->index(['is_active', 'usage_scope', 'sort_order'], 'fci_active_scope_sort_idx');
        });
    }

    /**
     * ลบตาราง fortune_consent_images
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_consent_images');
    }
};
