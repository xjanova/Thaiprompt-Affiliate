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
        Schema::create('rag_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->foreignId('message_id')->constrained('ai_messages')->onDelete('cascade');
            $table->foreignId('knowledge_base_id')->nullable()->constrained('knowledge_bases')->onDelete('set null');

            $table->text('query'); // User's question
            $table->integer('chunks_retrieved')->default(0); // Number of chunks found
            $table->json('chunk_ids')->nullable(); // IDs of chunks used
            $table->float('search_time_ms')->nullable(); // Time taken for search
            $table->float('avg_similarity_score')->nullable(); // Average cosine similarity
            $table->float('max_similarity_score')->nullable(); // Best match score

            $table->boolean('context_used')->default(false); // Was RAG context injected?
            $table->integer('context_tokens')->default(0); // Tokens used for context

            $table->timestamps();

            // Indexes
            $table->index(['conversation_id', 'created_at']);
            $table->index('knowledge_base_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_usage_logs');
    }
};
