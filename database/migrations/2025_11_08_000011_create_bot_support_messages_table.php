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
        Schema::create('bot_support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('bot_support_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['customer', 'agent', 'bot'])->default('customer');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->text('ai_prompt_used')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_support_messages');
    }
};
