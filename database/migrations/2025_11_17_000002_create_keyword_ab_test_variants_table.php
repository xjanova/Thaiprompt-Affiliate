<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง keyword_ab_test_variants สำหรับเก็บรายละเอียดตัวแปร A/B
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('keyword_ab_test_variants')) {
            return;
        }

        Schema::create('keyword_ab_test_variants', function (Blueprint $table) {
            $table->id();

            // Foreign key - A/B test
            $table->foreignId('ab_test_id')
                ->constrained('keyword_ab_tests')
                ->onDelete('cascade');

            // Variant type
            $table->enum('variant_type', ['variant_a', 'variant_b']);

            // Variant content
            $table->string('response_text', 1000); // ข้อความตอบกลับ
            $table->json('trigger_words')->nullable(); // คำที่ทริกเกอร์ (ถ้าต่างกัน)
            $table->string('response_type', 50)->default('text'); // ประเภท: text, quick_reply, flex_message

            // Additional options
            $table->text('description')->nullable(); // คำอธิบายความแตกต่าง
            $table->json('additional_data')->nullable(); // ข้อมูลเพิ่มเติม (สำหรับ flex_message, quick_reply)

            // Metrics (ในการทดสอบ)
            $table->bigInteger('impressions')->default(0); // จำนวนครั้งที่แสดง
            $table->bigInteger('interactions')->default(0); // จำนวนครั้งที่ user โต้ตอบ
            $table->float('conversion_rate')->default(0); // อัตราการแปลง (%)
            $table->float('avg_response_time')->default(0); // เวลาตอบกลับเฉลี่ย (ms)
            $table->float('satisfaction_score')->default(0); // คะแนนความพึงพอใจ (0-100)

            // Metadata
            $table->timestamps();

            // Indexes
            $table->unique(['ab_test_id', 'variant_type']);
            $table->index('ab_test_id');
        });
    }

    /**
     * ลบตาราง keyword_ab_test_variants
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_ab_test_variants');
    }
};
