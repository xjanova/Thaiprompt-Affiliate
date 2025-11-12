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
        Schema::create('ticket_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Conditions
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->onDelete('cascade');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->json('keywords')->nullable()->comment('Keywords in subject/description');

            // Assignment strategy
            $table->enum('strategy', ['round_robin', 'least_active', 'random', 'specific_user'])->default('round_robin');
            $table->json('assignable_users')->nullable()->comment('Array of user IDs eligible for assignment');
            $table->foreignId('specific_user_id')->nullable()->constrained('users')->onDelete('cascade');

            // State tracking for round-robin
            $table->integer('last_assigned_index')->default(0);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Higher priority rules processed first');

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignment_rules');
    }
};
