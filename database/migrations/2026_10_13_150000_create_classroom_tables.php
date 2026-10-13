<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎓 ห้องเรียน (classroom) — อบรมผู้ขาย / ไรเดอร์ / ผู้ซื้อ (ไม่บังคับ) แล้วได้ป้าย "ผ่านการอบรม"
 *
 * 1) classroom_courses — 1 คอร์สที่เปิดอยู่ต่อบทบาท (seller|rider|buyer) · rules_version = รุ่นกติกา
 *    เปลี่ยนรุ่นกติกา → ใบรับรองรุ่นเก่ากลายเป็น "ต้องอบรมทบทวน" (needs_refresh) โดยไม่เสียคะแนน
 * 2) classroom_lessons — วิดีโอ YouTube (unlisted) + ความยาวที่แอดมินกรอก (ใช้คิด 90%)
 * 3) classroom_lesson_progress — เวลาดูที่นับให้จริง (กันกดข้าม: นับไม่เกินเวลาจริงระหว่าง heartbeat + 3 วิ)
 * 4) classroom_questions — คลังข้อสอบ (ตัวเลือก 2–6 ข้อ เก็บเป็น JSON)
 * 5) classroom_attempts — การสอบแต่ละครั้ง (ข้อที่สุ่มได้ + ลำดับตัวเลือก + เฉลยฝั่งเซิร์ฟเวอร์ หมดอายุ 30 นาที)
 * 6) classroom_certifications — ผ่านการอบรม 1 แถวต่อ ผู้ใช้+บทบาท (unique)
 *
 * ⚠️ คอลัมน์เวลาทุกตัว nullable (dateTime/timestamp) — MariaDB บน prod เติม ON UPDATE CURRENT_TIMESTAMP
 *    ให้ TIMESTAMP ตัวแรกที่ไม่ nullable · บทบาทเป็น string ไม่ใช่ enum (เพิ่มบทบาททีหลังไม่ต้อง ALTER)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classroom_courses')) {
            Schema::create('classroom_courses', function (Blueprint $table) {
                $table->id();
                $table->string('role', 16)->comment('seller|rider|buyer — เปิดได้ทีละ 1 คอร์สต่อบทบาท');
                $table->string('title', 150);
                $table->text('description')->nullable();
                $table->string('rules_version', 20)->default('2026-10')->comment('รุ่นกติกา — เปลี่ยนแล้วใบรับรองเก่าต้องทบทวน');
                $table->unsignedSmallInteger('question_count')->default(15)->comment('จำนวนข้อที่สุ่มต่อการสอบ');
                $table->unsignedSmallInteger('pass_score')->default(13)->comment('ตอบถูกขั้นต่ำเพื่อผ่าน');
                $table->unsignedSmallInteger('points_reward')->default(10)->comment('คะแนนความประพฤติเมื่อผ่านครั้งแรก');
                $table->unsignedInteger('retry_cooldown_seconds')->default(60)->comment('ต้องรอกี่วินาทีหลังสอบไม่ผ่าน');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['role', 'is_active'], 'classroom_courses_role_active_idx');
            });
        }

        if (! Schema::hasTable('classroom_lessons')) {
            Schema::create('classroom_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('classroom_courses')->cascadeOnDelete();
                $table->string('title', 200);
                $table->string('youtube_id', 11)->comment('รหัสวิดีโอ 11 ตัว (แยกจากลิงก์ YouTube ทุกแบบ)');
                $table->unsignedInteger('duration_seconds')->default(0)->comment('ความยาววิดีโอที่แอดมินกรอก (วินาที)');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['course_id', 'is_active', 'sort_order'], 'classroom_lessons_course_idx');
            });
        }

        if (! Schema::hasTable('classroom_lesson_progress')) {
            Schema::create('classroom_lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained('classroom_lessons')->cascadeOnDelete();
                $table->unsignedInteger('watched_seconds')->default(0)->comment('เวลาดูที่นับให้แล้ว (กันกดข้าม)');
                $table->unsignedInteger('last_position_seconds')->default(0);
                $table->dateTime('last_heartbeat_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['user_id', 'lesson_id'], 'classroom_progress_user_lesson_unique');
                $table->index(['lesson_id', 'completed_at'], 'classroom_progress_lesson_done_idx');
            });
        }

        if (! Schema::hasTable('classroom_questions')) {
            Schema::create('classroom_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('classroom_courses')->cascadeOnDelete();
                $table->text('question');
                $table->json('choices')->comment('ตัวเลือก 2–6 ข้อ (ข้อความ)');
                $table->unsignedTinyInteger('correct_index')->comment('ตำแหน่งคำตอบที่ถูก (เริ่มที่ 0)');
                $table->text('explanation')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['course_id', 'is_active'], 'classroom_questions_course_active_idx');
            });
        }

        if (! Schema::hasTable('classroom_attempts')) {
            Schema::create('classroom_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('classroom_courses')->cascadeOnDelete();
                $table->json('question_ids')->comment('ข้อที่สุ่มได้ตามลำดับที่แสดง');
                $table->json('choice_order')->nullable()->comment('{question_id: [ลำดับตัวเลือกเดิม]} ตัวเลือกที่สลับให้ผู้สอบ');
                $table->json('answer_key')->nullable()->comment('{question_id: ตำแหน่งที่ถูกตามลำดับที่แสดง} — ห้ามส่งให้แอป');
                $table->json('answers')->nullable();
                $table->unsignedSmallInteger('score')->nullable();
                $table->unsignedSmallInteger('total')->default(0);
                $table->unsignedSmallInteger('pass_score')->default(0)->comment('เกณฑ์ผ่านของการสอบครั้งนี้');
                $table->boolean('passed')->default(false);
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['user_id', 'course_id', 'submitted_at'], 'classroom_attempts_user_course_idx');
                $table->index(['course_id', 'passed'], 'classroom_attempts_course_passed_idx');
            });
        }

        if (! Schema::hasTable('classroom_certifications')) {
            Schema::create('classroom_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 16);
                $table->foreignId('course_id')->nullable()->constrained('classroom_courses')->nullOnDelete();
                $table->string('rules_version', 20);
                $table->foreignId('attempt_id')->nullable()->constrained('classroom_attempts')->nullOnDelete();
                $table->unsignedSmallInteger('points_awarded')->default(0)->comment('คะแนนที่ให้ตอนผ่านครั้งแรก (ทบทวนไม่ได้เพิ่ม)');
                $table->dateTime('certified_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['user_id', 'role'], 'classroom_cert_user_role_unique');
                $table->index(['role', 'rules_version'], 'classroom_cert_role_version_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_certifications');
        Schema::dropIfExists('classroom_attempts');
        Schema::dropIfExists('classroom_questions');
        Schema::dropIfExists('classroom_lesson_progress');
        Schema::dropIfExists('classroom_lessons');
        Schema::dropIfExists('classroom_courses');
    }
};
