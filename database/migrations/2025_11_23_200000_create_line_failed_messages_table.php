<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_failed_messages สำหรับเก็บข้อความที่ส่งล้มเหลว
     *
     * ตารางนี้ใช้สำหรับระบบ Auto-Retry & Error Recovery
     * เก็บข้อความที่ส่งไม่สำเร็จและทำการ retry อัตโนมัติ
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('line_failed_messages')) {
            return;
        }

        Schema::create('line_failed_messages', function (Blueprint $table) {
            $table->id();

            // ข้อมูลผู้รับ
            $table->string('line_user_id', 100)->index()->comment('LINE User ID ของผู้รับ');

            // ข้อมูลข้อความ
            $table->string('message_type', 50)->comment('ประเภทข้อความ: text, flex, image, etc.');
            $table->json('message_payload')->comment('ข้อมูลข้อความทั้งหมด (JSON)');

            // ข้อมูล Error
            $table->string('error_type', 100)->nullable()->index()->comment('ประเภท error: network, rate_limit, timeout, etc.');
            $table->text('error_message')->nullable()->comment('รายละเอียด error message');
            $table->json('error_context')->nullable()->comment('ข้อมูลเพิ่มเติมเกี่ยวกับ error');

            // การ Retry
            $table->unsignedInteger('retry_count')->default(0)->comment('จำนวนครั้งที่พยายาม retry แล้ว');
            $table->unsignedInteger('max_retries')->default(5)->comment('จำนวนครั้งสูงสุดที่จะ retry');
            $table->timestamp('next_retry_at')->nullable()->index()->comment('เวลาที่จะ retry ครั้งถัดไป');
            $table->timestamp('last_retry_at')->nullable()->comment('เวลาที่ retry ครั้งล่าสุด');

            // สถานะ
            $table->enum('status', ['pending', 'retrying', 'succeeded', 'failed', 'abandoned'])
                ->default('pending')
                ->index()
                ->comment('สถานะปัจจุบัน');

            // เวลาที่ resolve
            $table->timestamp('resolved_at')->nullable()->comment('เวลาที่ส่งสำเร็จหรือยกเลิก');

            // Metadata เพิ่มเติม
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();

            // Indexes เพิ่มเติมสำหรับ performance
            $table->index(['status', 'next_retry_at'], 'idx_status_next_retry');
            $table->index(['line_user_id', 'status'], 'idx_user_status');
            $table->index('created_at');
        });

        // เพิ่ม comment ให้ตาราง
        DB::statement("ALTER TABLE `line_failed_messages` COMMENT = 'เก็บข้อความ LINE ที่ส่งล้มเหลวสำหรับระบบ Auto-Retry'");
    }

    /**
     * ลบตาราง line_failed_messages
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_failed_messages');
    }
};
