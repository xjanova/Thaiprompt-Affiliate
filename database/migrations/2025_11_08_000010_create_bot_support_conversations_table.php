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
        Schema::create('bot_support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'pending', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('channel', ['line', 'facebook', 'email', 'chat', 'phone'])->default('chat');

            $table->text('initial_message')->nullable();
            $table->json('tags')->nullable();
            $table->string('category')->nullable(); // Technical, Billing, General, etc.

            $table->boolean('is_ai_handled')->default(true);
            $table->boolean('requires_human')->default(false);
            $table->integer('ai_confidence_score')->nullable(); // 0-100
            $table->text('ai_suggested_response')->nullable();

            $table->integer('message_count')->default(0);
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('resolution_time_minutes')->nullable();

            $table->integer('customer_satisfaction_rating')->nullable(); // 1-5
            $table->text('customer_feedback')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['automation_id', 'is_ai_handled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_support_conversations');
    }
};
