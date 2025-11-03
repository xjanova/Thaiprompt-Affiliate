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
        Schema::create('ai_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('kb_type', ['text', 'url', 'file', 'database'])->default('text');

            // Storage
            $table->longText('content')->nullable();                 // For text type
            $table->string('file_path', 500)->nullable();            // For file type
            $table->string('url', 500)->nullable();                  // For URL type
            $table->json('metadata')->nullable();

            // Vector Embeddings (for RAG)
            $table->boolean('has_embeddings')->default(false);
            $table->string('embedding_model', 100)->nullable();      // 'text-embedding-ada-002'
            $table->integer('vector_dimension')->nullable();

            // Processing
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->integer('chunk_size')->default(1000);
            $table->integer('chunk_overlap')->default(200);
            $table->integer('total_chunks')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['bot_profile_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_bases');
    }
};
