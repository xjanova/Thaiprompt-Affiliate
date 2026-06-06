<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💬 (2026-06-06) สร้างตาราง fortune_invite_messages
 *
 * คลังข้อความ "ชวนดูดวงแบบเนียน" (สุ่มส่งใน DM กลับ)
 *
 * USER SPEC:
 *   "การ DM กลับหาคนที่คอมเม้นต์ อยากให้น่าสนใจมากขึ้น ใครเคยส่งรูปแล้ว
 *    ในสัปดาห์นั้นก็ไม่ต้องส่งอีก แต่ส่งเป็นคำพูดไป เช่น ดวงชะตาเปิดแล้ว
 *    วันนี้โอกาสดีต้องรีบดูทริคชีวิต ไปต่อยังไงให้ถูกทาง คุยกับแม่หมอได้เลย"
 *
 * แนวคิด:
 *   - คนที่ได้รูปแบนเนอร์ไปแล้วในสัปดาห์นี้ → ไม่ส่งรูปซ้ำ ส่งข้อความเชิญชวนแทน
 *   - ข้อความ = ทริค/ทางออก/จังหวะ (ไม่ขายตรง) — สุ่มจากคลังนี้ กัน FB spam detection
 *   - แอดมินเพิ่ม/ลบ/เปิดปิด/แก้ไขข้อความเองได้ในหน้าแอดมิน
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_invite_messages
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (CREATE TABLE ใช้ได้)
        if (Schema::hasTable('fortune_invite_messages')) {
            return;
        }

        Schema::create('fortune_invite_messages', function (Blueprint $table) {
            $table->id();

            // ข้อความเชิญชวน — รองรับ {name} แทนชื่อลูกค้า
            $table->text('message');

            // หมวดหมู่ (จังหวะ/ทางออก/ความรัก/การเงิน ฯลฯ) — ไว้ filter ในแอดมิน
            $table->string('category', 50)->default('general');

            // เปิด/ปิดเป็นรายข้อความ
            $table->boolean('is_active')->default(true);

            // ลำดับการแสดงในแอดมิน
            $table->integer('sort_order')->default(0);

            // สถิติการส่ง
            $table->integer('send_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            // แอดมินที่สร้าง/แก้ไขล่าสุด
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->index('sort_order');
        });
    }

    /**
     * ลบตาราง fortune_invite_messages
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_invite_messages');
    }
};
