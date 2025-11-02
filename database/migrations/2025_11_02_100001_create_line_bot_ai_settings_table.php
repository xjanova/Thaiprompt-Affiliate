<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('line_bot_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อการตั้งค่า AI');
            $table->enum('provider', ['openai', 'deepseek', 'anthropic', 'gemini', 'custom'])->default('openai');
            $table->string('api_key')->nullable();
            $table->string('api_endpoint')->nullable()->comment('สำหรับ custom provider');
            $table->string('model')->default('gpt-3.5-turbo')->comment('โมเดล AI เช่น gpt-4, deepseek-chat');
            $table->float('temperature')->default(0.7)->comment('ความคิดสร้างสรรค์ 0-1');
            $table->integer('max_tokens')->default(1000)->comment('จำนวน token สูงสุด');
            $table->text('system_prompt')->nullable()->comment('คำสั่งระบบสำหรับ AI');
            $table->boolean('enable_conversation_history')->default(true);
            $table->integer('conversation_memory_limit')->default(10)->comment('จำการสนทนากี่ข้อความ');
            $table->json('knowledge_base_sources')->nullable()->comment('แหล่งข้อมูล: urls, internal, files');
            $table->boolean('is_active')->default(false);
            $table->boolean('enable_fallback')->default(true)->comment('ใช้การตอบกลับสำรองเมื่อ AI ล้มเหลว');
            $table->text('fallback_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_bot_ai_settings');
    }
};
