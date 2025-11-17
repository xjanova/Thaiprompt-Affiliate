<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง message_sentiments สำหรับเก็บผลการวิเคราะห์ความรู้สึก
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('message_sentiments')) {
            return;
        }

        Schema::create('message_sentiments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('keyword_id')
                ->nullable()
                ->constrained('line_bot_keywords')
                ->onDelete('set null');

            // Message data
            $table->string('line_user_id'); // ผู้ใช้ LINE
            $table->text('user_message'); // ข้อความของผู้ใช้
            $table->string('message_hash')->unique(); // Hash เพื่อหลีกเลี่ยง duplicates

            // Sentiment analysis
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('neutral');
            $table->float('sentiment_score')->default(0); // -1 (very negative) to 1 (very positive)
            $table->float('confidence')->default(0); // 0-100%

            // Emotion breakdown
            $table->float('joy_score')->default(0); // 0-100%
            $table->float('anger_score')->default(0);
            $table->float('sadness_score')->default(0);
            $table->float('fear_score')->default(0);
            $table->float('surprise_score')->default(0);

            // Keywords detection
            $table->json('positive_keywords')->nullable(); // ['happy', 'thank', ...]
            $table->json('negative_keywords')->nullable(); // ['angry', 'bad', ...]
            $table->json('neutral_keywords')->nullable();

            // Context
            $table->string('language', 10)->default('th'); // Language detected
            $table->text('context')->nullable(); // Previous messages context
            $table->boolean('is_complaint')->default(false); // ข้อร้องเรียน?
            $table->boolean('is_urgent')->default(false); // ด่วน?

            // Pain points detection
            $table->json('detected_issues')->nullable(); // ['refund', 'shipping', ...]
            $table->string('primary_issue', 50)->nullable(); // Main issue

            // Metadata
            $table->json('nlp_data')->nullable(); // Raw NLP output
            $table->json('extra_data')->nullable(); // Additional analysis
            $table->timestamps();

            // Indexes
            $table->index('keyword_id');
            $table->index('line_user_id');
            $table->index('sentiment');
            $table->index('is_complaint');
            $table->index('is_urgent');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง message_sentiments
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_sentiments');
    }
};
