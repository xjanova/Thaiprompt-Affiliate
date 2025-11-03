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
        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained('ai_knowledge_bases')->onDelete('cascade');
            $table->integer('chunk_index');
            $table->text('content');
            $table->json('embedding')->nullable();                   // Vector embedding as JSON array
            $table->json('metadata')->nullable();                    // {page: 1, section: 'intro'}
            $table->timestamps();

            $table->index(['knowledge_base_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
    }
};
