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
        Schema::table('mlm_prospects', function (Blueprint $table) {
            // Conversation tracking fields
            $table->timestamp('conversation_started_at')->nullable()->after('conversation_data');
            $table->timestamp('conversation_updated_at')->nullable()->after('conversation_started_at');
            $table->timestamp('conversation_timeout_at')->nullable()->after('conversation_updated_at');
            $table->boolean('conversation_expired')->default(false)->after('conversation_timeout_at');
            $table->integer('conversation_message_count')->default(0)->after('conversation_expired');
            $table->integer('conversation_retry_count')->default(0)->after('conversation_message_count');

            // Progress tracking
            $table->integer('conversation_progress_percent')->default(0)->after('conversation_retry_count');
            $table->integer('conversation_total_steps')->default(0)->after('conversation_progress_percent');
            $table->integer('conversation_completed_steps')->default(0)->after('conversation_total_steps');

            // Add index for performance
            $table->index('conversation_started_at');
            $table->index(['status', 'conversation_expired']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_prospects', function (Blueprint $table) {
            $table->dropIndex(['conversation_started_at']);
            $table->dropIndex(['status', 'conversation_expired']);

            $table->dropColumn([
                'conversation_started_at',
                'conversation_updated_at',
                'conversation_timeout_at',
                'conversation_expired',
                'conversation_message_count',
                'conversation_retry_count',
                'conversation_progress_percent',
                'conversation_total_steps',
                'conversation_completed_steps',
            ]);
        });
    }
};
