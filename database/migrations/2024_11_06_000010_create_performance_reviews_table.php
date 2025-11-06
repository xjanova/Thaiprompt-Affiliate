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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('restrict');
            $table->string('review_period'); // Q1 2024, Annual 2024, etc
            $table->date('review_date');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->enum('review_type', ['probation', 'quarterly', 'semi_annual', 'annual', 'project_based'])->default('annual');

            // Rating Categories
            $table->integer('technical_skills_rating')->nullable(); // 1-5 scale
            $table->integer('communication_rating')->nullable();
            $table->integer('teamwork_rating')->nullable();
            $table->integer('leadership_rating')->nullable();
            $table->integer('problem_solving_rating')->nullable();
            $table->integer('productivity_rating')->nullable();
            $table->integer('quality_of_work_rating')->nullable();
            $table->integer('punctuality_rating')->nullable();
            $table->integer('initiative_rating')->nullable();
            $table->integer('adaptability_rating')->nullable();

            // Overall
            $table->decimal('overall_rating', 3, 2)->nullable(); // calculated average
            $table->string('overall_grade')->nullable(); // A, B, C, D, F or Excellent, Good, Average, Poor

            // Feedback
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('achievements')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->text('training_needs')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->text('employee_comments')->nullable();

            // Status
            $table->enum('status', ['draft', 'submitted', 'acknowledged', 'completed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // Recommendations
            $table->boolean('recommend_promotion')->default(false);
            $table->boolean('recommend_salary_increase')->default(false);
            $table->decimal('recommended_increase_percentage', 5, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'review_date']);
            $table->index('review_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
