<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_readings
     *
     * ตารางนี้เก็บบันทึกการทำนายแต่ละครั้ง
     * รองรับทั้งผู้ใช้ที่สมัครสมาชิกและไม่สมัครสมาชิก
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('fortune_readings')) {
            return;
        }

        Schema::create('fortune_readings', function (Blueprint $table) {
            $table->id();

            // ข้อมูลผู้ใช้ (nullable สำหรับผู้ไม่สมัครสมาชิก)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('ID ของผู้ใช้ในระบบ (null ถ้าไม่สมัครสมาชิก)');

            // ข้อมูลจาก Facebook
            $table->string('facebook_user_id', 100)->comment('Facebook User ID');
            $table->string('facebook_user_name')->nullable()->comment('ชื่อผู้ใช้จาก Facebook');
            $table->string('facebook_comment_id', 100)->nullable()->comment('Comment ID ที่ส่งคำถาม');
            $table->string('facebook_post_id', 100)->nullable()->comment('Post ID ที่คอมเมนต์');

            // คำถามและคำตอบ
            $table->json('questions')->comment('คำถามทั้งหมดที่ถาม (array of questions)');
            $table->json('categories')->nullable()->comment('หมวดหมู่ของคำถาม (array of category IDs)');
            $table->longText('ai_response')->comment('คำทำนายจาก AI');

            // ข้อมูลโปรไฟล์และบริบท
            $table->json('user_profile')->nullable()->comment('ข้อมูลโปรไฟล์จาก Facebook Graph API');
            $table->json('user_posts_context')->nullable()->comment('โพสล่าสุดของผู้ใช้ (สำหรับวิเคราะห์บริบท)');

            // ข้อมูล AI
            $table->string('ai_provider', 50)->comment('AI Provider ที่ใช้ (gemini, groq, qwen)');
            $table->string('ai_model', 100)->nullable()->comment('AI Model ที่ใช้');
            $table->integer('tokens_used')->nullable()->comment('จำนวน tokens ที่ใช้');

            // การชำระเงิน
            $table->boolean('is_paid')->default(false)->comment('ชำระเงินแล้วหรือยัง');
            $table->decimal('amount_paid', 10, 2)->default(0)->comment('จำนวนเงินที่ชำระ');
            $table->timestamp('paid_at')->nullable()->comment('วันเวลาที่ชำระเงิน');

            // การตอบกลับ
            $table->enum('response_type', ['comment', 'private_message'])
                ->default('private_message')
                ->comment('ประเภทการตอบกลับ');
            $table->timestamp('responded_at')->nullable()->comment('วันเวลาที่ตอบกลับ');

            // สถิติ
            $table->integer('view_count')->default(0)->comment('จำนวนครั้งที่ดูผลการทำนาย');
            $table->integer('rating')->nullable()->comment('คะแนนความพึงพอใจ (1-5)');
            $table->text('feedback')->nullable()->comment('ความคิดเห็นจากผู้ใช้');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id', 'fortune_read_user_idx');
            $table->index('facebook_user_id', 'fortune_read_fb_user_idx');
            $table->index('is_paid', 'fortune_read_paid_idx');
            $table->index('created_at', 'fortune_read_created_idx');
            $table->index(['facebook_user_id', 'created_at'], 'fortune_read_fb_date_idx');
        });
    }

    /**
     * ลบตาราง fortune_readings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_readings');
    }
};
