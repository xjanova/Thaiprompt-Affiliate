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
        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('set_by')->constrained('users')->onDelete('restrict');
            $table->string('goal_title');
            $table->text('description')->nullable();
            $table->enum('category', ['performance', 'development', 'behavioral', 'project', 'sales', 'other'])->default('performance');
            $table->date('start_date');
            $table->date('target_date');
            $table->decimal('target_value', 12, 2)->nullable(); // for measurable goals
            $table->string('measurement_unit')->nullable(); // sales, hours, percentage, etc
            $table->integer('weight_percentage')->default(100); // importance weight
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Progress Tracking
            $table->decimal('current_progress', 5, 2)->default(0); // percentage 0-100
            $table->decimal('current_value', 12, 2)->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'at_risk', 'completed', 'cancelled', 'overdue'])->default('not_started');
            $table->date('completion_date')->nullable();
            $table->text('completion_notes')->nullable();

            // Evaluation
            $table->integer('achievement_rating')->nullable(); // 1-5 scale
            $table->text('evaluation_notes')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['target_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_goals');
    }
};
