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
        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->string('course_code')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['technical', 'soft_skills', 'compliance', 'leadership', 'safety', 'product', 'other'])->default('technical');
            $table->enum('type', ['internal', 'external', 'online', 'workshop', 'seminar', 'certification'])->default('internal');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');

            // Course Details
            $table->integer('duration_hours')->nullable();
            $table->integer('duration_days')->nullable();
            $table->integer('max_participants')->nullable();
            $table->decimal('cost_per_participant', 10, 2)->nullable();
            $table->string('currency', 3)->default('THB');

            // Instructor/Provider
            $table->string('instructor_name')->nullable();
            $table->string('training_provider')->nullable();
            $table->string('provider_contact')->nullable();

            // Schedule
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->string('venue')->nullable();

            // Content
            $table->text('objectives')->nullable();
            $table->text('prerequisites')->nullable();
            $table->json('modules')->nullable(); // course modules/topics
            $table->json('materials')->nullable(); // training materials/files

            // Certification
            $table->boolean('provides_certificate')->default(false);
            $table->string('certificate_name')->nullable();
            $table->integer('validity_months')->nullable(); // certificate validity

            // Status
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_mandatory')->default(false);
            $table->json('target_departments')->nullable();
            $table->json('target_positions')->nullable();

            // Stats
            $table->integer('enrolled_count')->default(0);
            $table->integer('completed_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};
