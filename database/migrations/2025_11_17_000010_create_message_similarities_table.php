<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง message_similarities สำหรับเก็บความคล้ายกันระหว่างข้อความ
     *
     * ใช้สำหรับ semantic similarity analysis
     * ค้นหาข้อความที่คล้ายกันและเรียนรู้จากประวัติ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('message_similarities')) {
            return;
        }

        Schema::create('message_similarities', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('message_sentiment_id_1')
                ->constrained('message_sentiments')
                ->onDelete('cascade'); // First message

            $table->foreignId('message_sentiment_id_2')
                ->constrained('message_sentiments')
                ->onDelete('cascade'); // Second message

            // Similarity metrics
            $table->float('cosine_similarity')->default(0); // Cosine similarity (0-1)
            $table->float('entity_overlap')->default(0); // Entity overlap percentage
            $table->float('intent_similarity')->default(0); // Intent similarity
            $table->float('overall_similarity')->default(0); // Weighted overall similarity

            // Similarity type
            $table->enum('similarity_type', [
                'EXACT',              // Almost exact match
                'SEMANTIC',           // Same meaning, different words
                'ENTITY_BASED',       // Same entities mentioned
                'INTENT_BASED',       // Same intent/goal
                'CONTEXTUAL',         // Similar context
                'TOPIC_BASED'         // Same topic
            ])->index();

            // Related details
            $table->json('matching_entities')->nullable(); // Common entities
            $table->json('matching_intents')->nullable(); // Common intents
            $table->json('matching_keywords')->nullable(); // Common keywords
            $table->text('explanation')->nullable(); // Why they're similar

            // Statistics
            $table->integer('common_entity_count')->default(0);
            $table->integer('common_keyword_count')->default(0);
            $table->integer('message_gap_days')->default(0); // Days between messages

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['overall_similarity', 'similarity_type']);
            $table->unique(['message_sentiment_id_1', 'message_sentiment_id_2'], 'unique_message_pair');
        });
    }

    /**
     * ลบตาราง message_similarities
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('message_similarities');
    }
};
