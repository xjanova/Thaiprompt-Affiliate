<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตารางสำหรับเก็บบทสนทนา (Conversation Flow) สำหรับการสมัครสมาชิก
     */
    public function up(): void
    {
        Schema::create('line_signup_flows', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name'); // ชื่อ Flow
            $table->string('step_key')->unique(); // Key ของ Step (e.g., 'welcome', 'ask_name', 'ask_phone')
            $table->integer('step_order')->default(0); // ลำดับของ Step

            // เนื้อหาข้อความ
            $table->text('message_text'); // ข้อความที่จะส่งให้ผู้ใช้
            $table->json('message_flex')->nullable(); // Flex Message JSON (ถ้ามี)

            // ประเภทของ Input ที่รอรับ
            $table->enum('input_type', [
                'text',         // ข้อความทั่วไป
                'phone',        // เบอร์โทรศัพท์
                'email',        // อีเมล
                'name',         // ชื่อ
                'confirm',      // ยืนยัน (ใช่/ไม่ใช่)
                'choice',       // เลือก (Multiple choice)
                'none',         // ไม่ต้องรับ input
            ])->default('text');

            // Validation Rules
            $table->json('validation_rules')->nullable(); // กฎการตรวจสอบ (JSON)
            $table->text('validation_error_message')->nullable(); // ข้อความเมื่อ validation ผิดพลาด

            // Next Step Logic
            $table->string('next_step_key')->nullable(); // Step ถัดไป (default)
            $table->json('conditional_next_steps')->nullable(); // Step ถัดไปแบบมีเงื่อนไข

            // Quick Reply Options
            $table->json('quick_reply_options')->nullable(); // ตัวเลือก Quick Reply

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_skippable')->default(false); // สามารถข้ามได้หรือไม่
            $table->boolean('require_ai')->default(false); // ต้องใช้ AI ตอบหรือไม่

            // AI Settings (สำหรับอนาคต)
            $table->text('ai_prompt')->nullable(); // Prompt สำหรับ AI
            $table->json('ai_context')->nullable(); // Context สำหรับ AI

            $table->timestamps();

            // Indexes
            $table->index('step_order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_flows');
    }
};
