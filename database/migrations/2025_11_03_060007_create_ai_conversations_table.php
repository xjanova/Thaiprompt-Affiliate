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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');
            $table->foreignId('rental_id')->nullable()->constrained('ai_bot_rentals')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('line_user_id')->nullable();             // LINE User ID

            $table->string('title')->nullable();                     // Auto-generated or user-set
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');

            // Metadata
            $table->integer('total_messages')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost', 10, 4)->default(0);

            $table->timestamp('started_at');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->index(['bot_profile_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('line_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
