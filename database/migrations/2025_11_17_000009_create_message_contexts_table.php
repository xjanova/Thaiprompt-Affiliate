<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง message_contexts สำหรับเก็บ context ของแต่ละข้อความ
     *
     * ใช้สำหรับ context-aware intent recognition
     * จดจำประวัติการสนทนาและ context แบบเรียลไทม์
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('message_contexts')) {
            return;
        }

        Schema::create('message_contexts', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('message_sentiment_id')
                ->constrained('message_sentiments')
                ->onDelete('cascade');

            $table->string('line_user_id')->nullable()->index(); // LINE user identifier
            $table->foreignId('conversation_id')->nullable()->index(); // Conversation group

            // Context information
            $table->enum('context_type', [
                'FIRST_MESSAGE',      // First message in conversation
                'FOLLOW_UP',          // Follow-up to previous
                'RESOLUTION',         // Resolving previous issue
                'ESCALATION',         // Escalating problem
                'FEEDBACK',           // Feedback on resolution
                'NEW_TOPIC',          // New topic in conversation
                'CONTINUATION',       // Continuing previous topic
                'CLARIFICATION'       // Clarifying something
            ])->index();

            // Related messages
            $table->foreignId('parent_message_sentiment_id')
                ->nullable()
                ->constrained('message_sentiments')
                ->onDelete('set null'); // Previous related message

            // Conversation metrics
            $table->integer('message_position')->default(1); // Position in conversation (1st, 2nd, 3rd...)
            $table->integer('similar_messages_count')->default(0); // Count of similar messages
            $table->integer('conversation_length')->default(1); // Total messages in conversation
            $table->integer('minutes_since_last_message')->nullable(); // Time gap from previous
            $table->integer('conversation_duration_minutes')->default(0); // Total conversation time

            // Contextual data
            $table->json('conversation_summary')->nullable(); // Summary of conversation so far
            $table->json('topic_stack')->nullable(); // Stack of topics discussed
            $table->json('entities_mentioned')->nullable(); // All entities mentioned in conversation
            $table->json('intents_in_conversation')->nullable(); // All intents in conversation
            $table->json('mentioned_keywords')->nullable(); // Keywords mentioned

            // Sentiment progression
            $table->float('sentiment_trend')->nullable(); // Sentiment trend over conversation
            $table->enum('conversation_sentiment_direction', [
                'IMPROVING',
                'DECLINING',
                'STABLE',
                'VOLATILE'
            ])->nullable();

            // Prediction & recommendations
            $table->json('predicted_next_intent')->nullable(); // ML prediction
            $table->json('recommended_responses')->nullable(); // Suggested responses
            $table->float('urgency_escalation_score')->nullable(); // How urgent this is getting

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['line_user_id', 'created_at']);
            $table->index(['context_type', 'message_position']);
        });
    }

    /**
     * ลบตาราง message_contexts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_contexts');
    }
};
