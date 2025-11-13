<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Keyword-based Response System สำหรับ Hybrid Chatbot
     * - ตรวจสอบ keyword ก่อน ถ้าตรงจะตอบทันที
     * - ถ้าไม่ตรงจะส่งต่อไปยัง AI
     */
    public function up(): void
    {
        Schema::create('chatbot_keyword_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');

            // Keyword Configuration
            $table->json('keywords');                        // ['สวัสดี', 'หวัดดี', 'hello']
            $table->enum('match_type', ['exact', 'partial', 'regex'])->default('partial');
            $table->boolean('case_sensitive')->default(false);

            // Response Content
            $table->text('response_text');                   // คำตอบที่จะส่งกลับ
            $table->json('response_media')->nullable();      // รูปภาพ, วิดีโอ, ไฟล์ แนบ
            $table->json('quick_replies')->nullable();       // Quick reply buttons
            $table->json('response_variations')->nullable(); // คำตอบแบบสุ่ม (หลายแบบ)

            // Priority & Conditions
            $table->integer('priority')->default(0);         // ลำดับความสำคัญ (สูงกว่า = เช็คก่อน)
            $table->json('conditions')->nullable();          // เงื่อนไข เช่น เวลา, วัน, user_type

            // Usage Tracking
            $table->integer('usage_count')->default(0);      // จำนวนครั้งที่ถูกใช้
            $table->timestamp('last_used_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);            // ลำดับการแสดงผล

            $table->timestamps();

            // Indexes
            $table->index(['bot_profile_id', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_keyword_responses');
    }
};
