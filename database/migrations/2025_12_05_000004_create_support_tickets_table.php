<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง support_tickets สำหรับระบบ Ticket ติดต่อแอดมิน
 */
return new class extends Migration
{
    /**
     * สร้างตาราง support_tickets
     */
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // หัวข้อและเนื้อหา
            $table->string('subject');
            $table->enum('category', [
                'general',      // ทั่วไป
                'account',      // บัญชี
                'payment',      // การชำระเงิน
                'rider',        // ไรเดอร์
                'technical',    // ปัญหาเทคนิค
                'complaint',    // ร้องเรียน
                'suggestion',   // ข้อเสนอแนะ
                'other',         // อื่นๆ
            ])->default('general');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                ->default('medium');

            // สถานะ
            $table->enum('status', [
                'open',         // เปิดใหม่
                'in_progress',  // กำลังดำเนินการ
                'waiting',      // รอผู้ใช้ตอบกลับ
                'resolved',     // แก้ไขแล้ว
                'closed',        // ปิดแล้ว
            ])->default('open');

            // ผู้รับผิดชอบ
            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // เวลา
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();

            // การให้คะแนน
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_comment')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('category');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง support_tickets
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
