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
        if (Schema::hasTable('marketplace_sync_logs')) {
            return;
        }

        Schema::create('marketplace_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('marketplace_accounts')->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('marketplace_platforms')->onDelete('cascade');

            // Sync Info
            $table->enum('sync_type', ['products', 'orders', 'manual']);
            $table->enum('sync_status', ['running', 'completed', 'failed', 'partial'])->default('running');

            // Results
            $table->integer('items_processed')->default(0);
            $table->integer('items_created')->default(0);
            $table->integer('items_updated')->default(0);
            $table->integer('items_failed')->default(0);

            // Error Handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();

            // Duration
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Triggered By
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null');

            $table->index(['account_id', 'sync_status']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_logs');
    }
};
