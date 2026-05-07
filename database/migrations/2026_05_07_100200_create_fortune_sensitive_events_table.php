<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-07) สร้างตาราง fortune_sensitive_events
 *
 * บันทึกทุกครั้งที่ FortuneSensitivityDetector ตรวจพบบริบทละเอียดอ่อน
 *   - admin วิเคราะห์ pattern ได้
 *   - feed ลง ObsidianX (Phase 3) → บอทเรียนรู้จาก history
 *   - debug false positive/negative
 *
 * ⚠️ ห้ามเก็บ message_text เต็ม (PII) — เก็บแค่ hash + first 60 chars
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_sensitive_events')) {
            return;
        }

        Schema::create('fortune_sensitive_events', function (Blueprint $table) {
            $table->id();

            // ระบุผู้ใช้
            $table->string('platform', 20)->comment('facebook / line');
            $table->string('platform_user_id', 100)->comment('FB PSID / LINE userId');
            $table->unsignedBigInteger('user_id')->nullable()->comment('users.id ถ้า map ได้');

            // บริบท
            $table->enum('context', [
                'chat',           // sensitive ในแชทธรรมดา
                'deep_question',  // ในคำถาม Deep 39
                'celtic_turn',    // ในเทิร์น Celtic 99
                'free_card',      // ในแชทหลัง free card
            ])->comment('บริบทที่ trigger');

            // ผลการตรวจจับ
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_offtopic')->default(false);
            $table->tinyInteger('mood_level')->nullable()->comment('1-5 (5=ก้าวร้าวสุด)');
            $table->tinyInteger('complexity')->nullable()->comment('1-5 (5=ซับซ้อนสุด)');
            $table->json('reasons')->nullable()->comment('Array ของ trigger reasons');

            // วิธีการตรวจ
            $table->enum('detection_used', ['heuristic', 'classifier', 'hybrid'])->nullable();

            // การตอบสนอง
            $table->boolean('used_pro_model')->default(false);
            $table->string('pro_provider', 50)->nullable();
            $table->string('pro_model', 100)->nullable();
            $table->boolean('budget_blocked')->default(false)->comment('ถูก block เพราะเกิน budget');
            $table->boolean('offtopic_blocked')->default(false)->comment('ถูก block เพราะ off-topic');

            // ข้อมูลข้อความ (privacy-safe)
            $table->string('message_hash', 64)->nullable()->comment('SHA-256 ของข้อความ (dedupe)');
            $table->string('message_preview', 60)->nullable()->comment('60 chars แรก (debug)');
            $table->integer('message_length')->nullable();

            // Cost tracking
            $table->integer('tokens_used')->nullable();
            $table->decimal('cost_thb', 8, 4)->nullable()->comment('ประมาณการต้นทุน THB');

            $table->timestamps();

            // Indexes
            $table->index(['platform', 'platform_user_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['context', 'created_at']);
            $table->index('is_sensitive');
            $table->index('used_pro_model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_sensitive_events');
    }
};
