<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_error_logs สำหรับบันทึก Error ทั้งหมด
     *
     * ตารางนี้ใช้สำหรับ Error Analytics และ Monitoring
     * ช่วยให้ Admin วิเคราะห์ปัญหาและ trend ของ errors
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('line_error_logs')) {
            return;
        }

        Schema::create('line_error_logs', function (Blueprint $table) {
            $table->id();

            // ข้อมูล Error
            $table->string('error_code', 50)->nullable()->index()->comment('รหัส error (ถ้ามี)');
            $table->string('error_type', 100)->index()->comment('ประเภท error: network, rate_limit, timeout, etc.');
            $table->text('error_message')->comment('ข้อความ error');

            // Context และ Stack Trace
            $table->json('context')->nullable()->comment('ข้อมูลบริบทเมื่อเกิด error');
            $table->text('stack_trace')->nullable()->comment('Stack trace (ถ้ามี)');

            // Recovery Information
            $table->boolean('is_recovered')->default(false)->index()->comment('แก้ไขตัวเองได้หรือไม่');
            $table->string('recovery_method', 100)->nullable()->comment('วิธีที่ใช้แก้ไข: auto_retry, circuit_breaker, manual, etc.');
            $table->text('recovery_details')->nullable()->comment('รายละเอียดการแก้ไข');

            // Timing
            $table->timestamp('occurred_at')->index()->comment('เวลาที่เกิด error');
            $table->timestamp('resolved_at')->nullable()->comment('เวลาที่แก้ไขแล้ว');
            $table->unsignedInteger('recovery_time_seconds')->nullable()->comment('เวลาที่ใช้ในการแก้ไข (วินาที)');

            // Severity Level
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])
                ->default('medium')
                ->index()
                ->comment('ระดับความรุนแรง');

            // Related Records
            $table->unsignedBigInteger('failed_message_id')->nullable()->comment('อ้างอิงไปยัง line_failed_messages.id');
            $table->string('line_user_id', 100)->nullable()->index()->comment('LINE User ID ที่เกี่ยวข้อง (ถ้ามี)');

            // Metadata
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');

            // Timestamps
            $table->timestamps();

            // Indexes สำหรับ Analytics
            $table->index(['error_type', 'occurred_at'], 'idx_type_occurred');
            $table->index(['is_recovered', 'severity'], 'idx_recovered_severity');
            $table->index('created_at');

            // Foreign Key (แต่ไม่บังคับเพราะ failed_message อาจถูกลบ)
            // $table->foreign('failed_message_id')->references('id')->on('line_failed_messages')->onDelete('set null');
        });

        // เพิ่ม comment ให้ตาราง
        DB::statement("ALTER TABLE `line_error_logs` COMMENT = 'บันทึก Error ทั้งหมดของระบบ LINE สำหรับ Analytics และ Monitoring'");
    }

    /**
     * ลบตาราง line_error_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_error_logs');
    }
};
