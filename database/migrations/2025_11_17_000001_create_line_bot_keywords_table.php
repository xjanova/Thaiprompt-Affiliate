<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_bot_keywords
     *
     * สำหรับเก็บ custom keywords สำหรับ Hybrid Bot
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('line_bot_keywords')) {
            return;
        }

        Schema::create('line_bot_keywords', function (Blueprint $table) {
            $table->id();

            // Keyword info
            $table->string('keyword')->unique();
            $table->text('description')->nullable();

            // Triggers
            $table->json('trigger_words')->nullable()->comment('Array of trigger phrases');

            // Response configuration
            $table->enum('response_type', ['text', 'flex_message', 'quick_reply'])->default('text');
            $table->text('response_text')->nullable();
            $table->json('response_flex_json')->nullable()->comment('Flex message JSON structure');
            $table->json('quick_reply_options')->nullable()->comment('Quick reply buttons');

            // Categorization
            $table->enum('category', ['faq', 'support', 'product', 'custom'])->default('custom');
            $table->integer('priority')->default(50)->comment('1-100, higher = check first');

            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->index('priority');
        });
    }

    /**
     * ลบตาราง
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_bot_keywords');
    }
};
