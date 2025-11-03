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
        if (Schema::hasTable('knowledge_bases')) {
            echo "Table 'knowledge_bases' already exists, skipping creation.\n";
            return;
        }

        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'pdf', 'docx', 'url', 'file'])->default('text');
            $table->text('source_url')->nullable(); // URL if type is url
            $table->string('file_path')->nullable(); // File path if uploaded
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->longText('raw_content')->nullable(); // Raw extracted text
            $table->integer('total_chunks')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['bot_profile_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
