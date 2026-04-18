<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_takeover_logs สำหรับบันทึกประวัติการเทคโอเวอร์
 *
 * เก็บทุกการกระทำเกี่ยวกับ Admin Takeover:
 * - takeover: เทคโอเวอร์ (กดเอง/แอดมินพิมพ์/ลูกค้าขอ)
 * - resume: สั่งให้ AI กลับมาทำงาน
 * - extend: ต่อเวลา
 * - message: แอดมินส่งข้อความผ่านแอดมินพาเนล
 * - auto_expire: หมดเวลาอัตโนมัติ
 */
return new class extends Migration
{
    public function up(): void
    {
        // ✅ เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('fortune_takeover_logs')) {
            return;
        }

        Schema::create('fortune_takeover_logs', function (Blueprint $table) {
            $table->id();

            // Foreign key ไปยัง fortune_readings
            $table->foreignId('fortune_reading_id')
                ->constrained('fortune_readings')
                ->onDelete('cascade')
                ->comment('conversation ที่เกี่ยวข้อง');

            // ผู้กระทำ (nullable เพราะ customer_request/auto_expire ไม่ใช่แอดมิน)
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('user_id ของแอดมิน (null = ระบบ/ลูกค้า)');

            // ประเภทการกระทำ
            $table->enum('action', [
                'takeover',      // เริ่มเทคโอเวอร์
                'resume',        // สั่ง AI กลับมา
                'extend',        // ต่อเวลา
                'message',       // แอดมินส่งข้อความ
                'auto_expire',   // หมดเวลาอัตโนมัติ
            ])->comment('ประเภทการกระทำ');

            // เหตุผล (สำหรับ takeover)
            $table->string('reason', 50)
                ->nullable()
                ->comment('manual, auto_reply, customer_request');

            // ระยะเวลา (นาที) — สำหรับ takeover/extend
            $table->unsignedInteger('duration_minutes')
                ->nullable()
                ->comment('เวลาเทคโอเวอร์/ต่อเวลา (นาที)');

            // ข้อความ (สำหรับ message action)
            $table->text('message')
                ->nullable()
                ->comment('ข้อความที่แอดมินส่ง');

            // Platform ที่ใช้ (line, facebook)
            $table->string('platform', 20)
                ->nullable()
                ->comment('line, facebook');

            // Metadata เพิ่มเติม (JSON)
            $table->json('metadata')
                ->nullable()
                ->comment('ข้อมูลเพิ่มเติม เช่น IP, user agent');

            $table->timestamps();

            // Indexes
            $table->index(['fortune_reading_id', 'created_at'], 'ft_takeover_logs_reading_idx');
            $table->index(['user_id', 'action'], 'ft_takeover_logs_user_idx');
            $table->index('action', 'ft_takeover_logs_action_idx');

            // Foreign key to users (manual because no guaranteed table name)
            $table->foreign('user_id', 'ft_takeover_logs_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_takeover_logs');
    }
};
