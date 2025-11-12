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
        Schema::create('ticket_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Target times in minutes
            $table->integer('first_response_time')->comment('First response SLA in minutes');
            $table->integer('resolution_time')->comment('Resolution SLA in minutes');

            // Applies to
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->onDelete('cascade');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->nullable();

            // Business hours
            $table->boolean('business_hours_only')->default(true);
            $table->json('business_hours')->nullable()->comment('Business hours config');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sla_policies');
    }
};
