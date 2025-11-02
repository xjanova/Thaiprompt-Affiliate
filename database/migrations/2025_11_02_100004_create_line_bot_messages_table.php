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
        Schema::create('line_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('line_bot_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'audio', 'video', 'sticker', 'location', 'flex'])->default('text');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติมเช่น sticker id, image url');
            $table->integer('tokens_used')->nullable()->comment('จำนวน tokens ที่ใช้');
            $table->float('response_time')->nullable()->comment('เวลาในการตอบกลับ (วินาที)');
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_bot_messages');
    }
};
