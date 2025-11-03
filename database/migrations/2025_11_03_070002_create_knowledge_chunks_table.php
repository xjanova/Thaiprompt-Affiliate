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
        if (Schema::hasTable('knowledge_chunks')) {
            echo "Table 'knowledge_chunks' already exists, skipping creation.\n";
            return;
        }

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->onDelete('cascade');
            $table->text('content'); // The actual chunk text
            $table->integer('chunk_index')->default(0); // Position in document
            $table->integer('token_count')->default(0);
            $table->integer('start_position')->nullable(); // Character position in original doc
            $table->integer('end_position')->nullable();

            // Vector Embedding (stored as JSON array)
            $table->json('embedding')->nullable(); // Vector representation [0.123, 0.456, ...]
            $table->integer('embedding_dimension')->nullable(); // e.g., 1536 for OpenAI

            // Metadata
            $table->string('page_number')->nullable(); // For PDFs
            $table->string('section_title')->nullable(); // If document has sections
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['knowledge_base_id', 'chunk_index']);
            $table->index('token_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
