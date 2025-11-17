<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_ab_tests สำหรับเก็บข้อมูล A/B test
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('keyword_ab_tests')) {
            return;
        }

        Schema::create('keyword_ab_tests', function (Blueprint $table) {
            $table->id();

            // Foreign key - คีย์เวิร์ดต้นฉบับ
            $table->foreignId('keyword_id')
                ->constrained('line_bot_keywords')
                ->onDelete('cascade');

            // Test metadata
            $table->string('test_name'); // ชื่อการทดสอบ
            $table->text('description')->nullable(); // คำอธิบาย
            $table->enum('status', ['planning', 'active', 'paused', 'completed', 'cancelled'])->default('planning');

            // Test configuration
            $table->integer('variant_a_percentage')->default(50); // แบบ A กี่เปอร์เซ็นต์
            $table->integer('variant_b_percentage')->default(50); // แบบ B กี่เปอร์เซ็นต์
            $table->string('winning_criterion', 50)->default('conversion_rate'); // เกณฑ์ชนะ (conversion_rate, response_time, satisfaction)

            // Test duration
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->integer('minimum_samples')->default(100); // จำนวนตัวอย่างต่ำสุด

            // Results
            $table->string('winner')->nullable(); // variant_a หรือ variant_b
            $table->float('winner_confidence')->nullable(); // ความมั่นใจทางสถิติ (%)
            $table->json('results')->nullable(); // ผลลัพธ์โดยละเอียด

            // Metadata
            $table->json('config')->nullable(); // การตั้งค่าเพิ่มเติม
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('keyword_id');
            $table->index('status');
            $table->index('started_at');
            $table->index('winning_criterion');
        });
    }

    /**
     * ลบตาราง keyword_ab_tests
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_ab_tests');
    }
};
