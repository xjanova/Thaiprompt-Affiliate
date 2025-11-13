<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตารางสำหรับเก็บ Template ข้อความสำหรับการสมัครสมาชิกผ่าน LINE
     */
    public function up(): void
    {
        Schema::create('line_signup_templates', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('template_key')->unique(); // Key เฉพาะของ Template (e.g., 'welcome_hero', 'earning_calculator')
            $table->string('template_name'); // ชื่อ Template
            $table->text('description')->nullable(); // คำอธิบาย Template

            // เนื้อหาข้อความ
            $table->json('flex_message_json'); // Flex Message JSON

            // Variables
            $table->json('variables')->nullable(); // ตัวแปรที่ใช้ใน Template (e.g., ['user_name', 'member_code'])

            // การใช้งาน
            $table->boolean('is_active')->default(true); // สถานะการใช้งาน
            $table->unsignedInteger('usage_count')->default(0); // จำนวนครั้งที่ใช้งาน

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_templates');
    }
};
