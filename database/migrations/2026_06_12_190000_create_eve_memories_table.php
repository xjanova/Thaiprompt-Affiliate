<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🧠 (2026-06-12) Eve's long-term memory (RAG-lite).
 *
 * Every day-to-day problem the operator solves with Eve can be distilled into
 * a lesson row; /eve/chat retrieves the most relevant lessons (keyword match —
 * deliberately NOT embeddings: EmbeddingService has a prod boot bug and Thai
 * keyword substrings are reliable + free) and folds them into the system
 * prompt so future answers learn from past incidents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eve_memories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);                 // short lesson label
            $table->string('keywords', 500)->nullable();  // comma list — retrieval index (LLM writes these)
            $table->text('content');                      // the lesson / resolution
            $table->string('source', 30)->default('chat');// chat | manual | api
            $table->string('admin_name', 120)->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('updated_at');
            $table->index('use_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eve_memories');
    }
};
