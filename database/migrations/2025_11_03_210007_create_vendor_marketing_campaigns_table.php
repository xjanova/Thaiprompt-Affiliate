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
        Schema::create('vendor_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

            // Campaign Details
            $table->string('campaign_name');
            $table->enum('campaign_type', ['email', 'line_broadcast', 'discount', 'promotion']);
            $table->text('description')->nullable();

            // Target Audience
            $table->enum('target_type', ['all', 'customers', 'subscribers', 'segment'])->default('all');
            $table->json('target_segment')->nullable();

            // Content
            $table->json('content')->nullable();

            // Schedule
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Budget (if applicable)
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('spent', 10, 2)->default(0);

            // Stats
            $table->integer('sent_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->decimal('revenue_generated', 15, 2)->default(0);

            // Status
            $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'cancelled'])->default('draft');

            $table->timestamps();

            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_marketing_campaigns');
    }
};
