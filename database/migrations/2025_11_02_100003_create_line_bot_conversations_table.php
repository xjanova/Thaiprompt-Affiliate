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
        Schema::create('line_bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_setting_id')->nullable()->constrained('line_bot_ai_settings')->nullOnDelete();
            $table->string('session_id')->unique();
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->json('context')->nullable()->comment('บริบทการสนทนา');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['line_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_bot_conversations');
    }
};
