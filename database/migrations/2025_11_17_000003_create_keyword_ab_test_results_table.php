<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_ab_test_results สำหรับบันทึกผลการทดสอบแต่ละครั้ง
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('keyword_ab_test_results')) {
            return;
        }

        Schema::create('keyword_ab_test_results', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('ab_test_id')
                ->constrained('keyword_ab_tests')
                ->onDelete('cascade');

            $table->foreignId('variant_id')
                ->constrained('keyword_ab_test_variants')
                ->onDelete('cascade');

            // User interaction
            $table->string('line_user_id'); // ผู้ใช้ LINE
            $table->string('user_message'); // ข้อความของผู้ใช้
            $table->string('variant_served', 20); // variant_a หรือ variant_b

            // Metrics
            $table->float('response_time')->default(0); // เวลาตอบกลับ (ms)
            $table->boolean('matched')->default(true); // ตรงกับคำหลักหรือไม่
            $table->boolean('interacted')->default(true); // ผู้ใช้โต้ตอบหรือไม่
            $table->integer('satisfaction')->nullable(); // ความพึงพอใจ (1-5 stars)
            $table->text('user_feedback')->nullable(); // ข้อเสนอแนะของผู้ใช้

            // Context
            $table->json('context')->nullable(); // ข้อมูลเพิ่มเติม (device, language, etc)
            $table->timestamps();

            // Indexes
            $table->index('ab_test_id');
            $table->index('variant_id');
            $table->index('line_user_id');
            $table->index('variant_served');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง keyword_ab_test_results
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_ab_test_results');
    }
};
