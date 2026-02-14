<?php

/**
 * สร้างตาราง video_mission_completions
 *
 * บันทึกการทำภารกิจของผู้ใช้พร้อมข้อมูลป้องกันการโกง
 * เก็บ timestamps ต่างๆ เพื่อตรวจสอบความถูกต้อง
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_mission_completions
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_mission_completions')) {
            return;
        }

        Schema::create('video_mission_completions', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('mission_id');

            // Session tracking
            $table->string('session_token', 64)->unique();             // Token สำหรับ session นี้
            $table->string('device_fingerprint')->nullable();          // Device fingerprint

            // สถานะการทำภารกิจ
            $table->enum('status', [
                'started',        // เริ่มดู
                'watching',       // กำลังดู
                'paused',         // หยุดชั่วคราว
                'completed',      // ดูจบแล้ว
                'verified',       // ยืนยันแล้ว (ได้รางวัล)
                'failed',         // ไม่ผ่าน (โกง/ไม่ครบ)
                'expired',        // หมดเวลา
                'cancelled',       // ยกเลิก
            ])->default('started');

            // ข้อมูลการดู
            $table->integer('watched_seconds')->default(0);            // เวลาที่ดูไป (วินาที)
            $table->integer('required_seconds');                       // เวลาที่ต้องดู (วินาที)
            $table->decimal('watch_percentage', 5, 2)->default(0);     // % ที่ดูไป
            $table->integer('video_position_seconds')->default(0);     // ตำแหน่งปัจจุบันในวิดีโอ
            $table->decimal('playback_speed', 3, 2)->default(1.00);    // ความเร็วในการเล่น

            // Timestamps สำหรับการดู
            $table->timestamp('started_at')->nullable();               // เวลาเริ่มดู
            $table->timestamp('last_heartbeat_at')->nullable();        // Heartbeat ล่าสุด
            $table->timestamp('completed_at')->nullable();             // เวลาดูจบ
            $table->timestamp('verified_at')->nullable();              // เวลายืนยัน
            $table->timestamp('reward_claimed_at')->nullable();        // เวลารับรางวัล

            // ข้อมูลป้องกันการโกง
            $table->integer('heartbeat_count')->default(0);            // จำนวน heartbeats
            $table->integer('tab_switch_count')->default(0);           // จำนวนครั้งที่สลับ tab
            $table->integer('pause_count')->default(0);                // จำนวนครั้งที่หยุด
            $table->integer('seek_count')->default(0);                 // จำนวนครั้งที่กระโดดข้าม
            $table->integer('total_seek_seconds')->default(0);         // จำนวนวินาทีที่กระโดดข้ามรวม
            $table->integer('focus_lost_count')->default(0);           // จำนวนครั้งที่ออกจากหน้าเว็บ
            $table->integer('interaction_count')->default(0);          // จำนวน interactions
            $table->json('interaction_timestamps')->nullable();        // Timestamps ของ interactions
            $table->json('watch_segments')->nullable();                // ส่วนที่ดู [{"start":0,"end":30},...]
            $table->json('suspicious_activities')->nullable();         // กิจกรรมน่าสงสัย

            // ผลการตรวจสอบ
            $table->boolean('is_suspicious')->default(false);          // น่าสงสัย
            $table->boolean('passed_verification')->nullable();        // ผ่านการตรวจสอบ
            $table->text('verification_notes')->nullable();            // หมายเหตุการตรวจสอบ
            $table->json('verification_answers')->nullable();          // คำตอบจากคำถามยืนยัน
            $table->integer('verification_score')->nullable();         // คะแนนการยืนยัน

            // รางวัลที่ได้รับ
            $table->boolean('reward_given')->default(false);           // ให้รางวัลแล้ว
            $table->decimal('reward_money', 12, 2)->default(0);        // รางวัลเงินสด
            $table->decimal('reward_coins', 12, 2)->default(0);        // รางวัล Coins
            $table->integer('reward_points')->default(0);              // รางวัลแต้ม
            $table->decimal('reward_tpix', 18, 8)->default(0);         // รางวัล TPIX
            $table->integer('reward_exp')->default(0);                 // รางวัล EXP
            $table->decimal('bonus_multiplier_applied', 5, 2)->default(1.00); // ตัวคูณที่ใช้

            // ข้อมูล User และ Device
            $table->string('ip_address', 45)->nullable();              // IP Address
            $table->string('user_agent')->nullable();                  // User Agent
            $table->string('browser')->nullable();                     // Browser
            $table->string('os')->nullable();                          // Operating System
            $table->string('device_type')->nullable();                 // mobile, tablet, desktop
            $table->string('country_code', 2)->nullable();             // Country code
            $table->string('city')->nullable();                        // City

            // Period tracking (สำหรับ daily/weekly/monthly limits)
            $table->date('completion_date');                           // วันที่ทำ
            $table->integer('completion_week')->nullable();            // สัปดาห์ที่ (1-52)
            $table->integer('completion_month')->nullable();           // เดือนที่
            $table->integer('completion_year')->nullable();            // ปี

            // Admin actions
            $table->unsignedBigInteger('verified_by')->nullable();     // Admin ที่ verify
            $table->unsignedBigInteger('rejected_by')->nullable();     // Admin ที่ reject
            $table->text('admin_notes')->nullable();                   // หมายเหตุจาก Admin

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('mission_id')
                ->references('id')
                ->on('video_missions')
                ->onDelete('cascade');

            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('rejected_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes สำหรับ query ที่ใช้บ่อย
            $table->index('user_id');
            $table->index('mission_id');
            $table->index('status');
            $table->index('completion_date');
            $table->index('reward_given');
            $table->index('is_suspicious');
            $table->index(['user_id', 'mission_id', 'completion_date'], 'vmc_user_mission_date_idx');
            $table->index(['user_id', 'status', 'completion_date'], 'vmc_user_status_date_idx');
            $table->index(['mission_id', 'status'], 'vmc_mission_status_idx');
        });
    }

    /**
     * ลบตาราง video_mission_completions
     */
    public function down(): void
    {
        Schema::dropIfExists('video_mission_completions');
    }
};
