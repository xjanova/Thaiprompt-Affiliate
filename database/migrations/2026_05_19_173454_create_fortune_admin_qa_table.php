<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_admin_qa
 *
 * เก็บคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" (Q&A pairs) สำหรับระบบ RAG
 *
 * Flow:
 *   1. แอดมินตอบลูกค้าใน FB Page Inbox/Business Suite
 *   2. FB ส่ง webhook message_echoes → handleEchoMessage
 *   3. ถ้าเป็น admin คนพิมพ์ (app_id ว่าง) → dispatch CaptureAdminQAJob
 *   4. Job: หา last customer message (Q) + admin reply (A) → embed(Q) → INSERT
 *   5. ตอนบอท chat AI ตอบลูกค้า → retrieve top-K similar Q&A → inject system prompt
 *
 * Storage: q_embedding เป็น JSON array 768-dim (text-embedding-004)
 *          MySQL cosine similarity ทำใน PHP (เหมาะ <10k rows, ~50ms)
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_admin_qa
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (CREATE TABLE only)
        if (Schema::hasTable('fortune_admin_qa')) {
            return;
        }

        Schema::create('fortune_admin_qa', function (Blueprint $table) {
            $table->id();

            // 📝 คำถามลูกค้า (last customer turn ก่อน admin ตอบ)
            $table->text('q_text');

            // 🧠 Vector embedding ของ q_text — 768 float values (Gemini text-embedding-004)
            //    JSON column รองรับ MySQL 8.0+ (Laravel cast เป็น array)
            $table->json('q_embedding')->nullable();

            // 💬 คำตอบแอดมิน (admin reply text)
            $table->text('a_text');

            // 👤 admin user id (ใน users table) — null = ไม่รู้ (FB Page Inbox ไม่บอก admin id)
            $table->unsignedBigInteger('admin_user_id')->nullable();

            // 🌐 ที่มา — แหล่งที่ capture (ตอนนี้มีแค่ facebook echo)
            $table->enum('source_platform', ['facebook', 'line', 'manual'])->default('facebook');

            // 👥 ลูกค้าที่ได้รับคำตอบนี้ (FB PSID / LINE userId)
            //    เก็บไว้เพื่อ debug + analytics (ไม่ใช้กรองตอน retrieve)
            $table->string('source_user_id', 64)->nullable();

            // 📋 context เพิ่มเติม (previous 1-2 turns, page_id, reading_id, etc.)
            $table->json('context_json')->nullable();

            // 🎯 Threshold override (null = ใช้ default 0.78 จาก settings)
            $table->decimal('similarity_threshold', 3, 2)->nullable();

            // ✅ active flag — แอดมิน toggle ปิดได้ผ่าน UI
            $table->boolean('is_active')->default(true);

            // 📊 hit tracking — เก็บว่าถูก retrieve กี่ครั้ง
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();

            // ⏰ timestamps
            $table->timestamps();

            // 🔍 Indexes
            $table->index('is_active');
            $table->index(['source_platform', 'is_active']);
            $table->index('admin_user_id');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง fortune_admin_qa
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_admin_qa');
    }
};
