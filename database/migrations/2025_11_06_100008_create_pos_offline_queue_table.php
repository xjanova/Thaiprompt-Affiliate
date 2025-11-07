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
        Schema::create('pos_offline_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->string('queue_type'); // transaction, stock_update, session, etc.
            $table->json('data'); // serialized data to sync
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('priority')->default(0); // higher priority synced first
            $table->timestamps();

            // Indexes
            $table->index('pos_device_id');
            $table->index('status');
            $table->index('queue_type');
            $table->index(['status', 'priority', 'created_at']); // for efficient queue processing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_offline_queue');
    }
};
