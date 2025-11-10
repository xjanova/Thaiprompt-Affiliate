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
        Schema::create('bot_sales_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('bot_sales_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['customer', 'sales_rep', 'bot'])->default('customer');
            $table->enum('message_type', ['text', 'product_recommendation', 'offer', 'question'])->default('text');

            $table->text('message');
            $table->json('product_data')->nullable(); // Product recommendations
            $table->json('attachments')->nullable();

            $table->boolean('is_ai_generated')->default(false);
            $table->text('ai_prompt_used')->nullable();
            $table->integer('tokens_used')->nullable();

            $table->string('intent_detected')->nullable(); // Customer intent
            $table->json('entities_extracted')->nullable(); // Product names, price mentions, etc.
            $table->integer('sentiment_score')->nullable(); // -100 to 100

            $table->timestamp('read_at')->nullable();
            $table->timestamp('clicked_at')->nullable(); // For product recommendations
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_sales_messages');
    }
};
