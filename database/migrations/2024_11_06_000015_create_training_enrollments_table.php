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
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_course_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('enrolled_at');

            // Attendance
            $table->enum('attendance_status', ['registered', 'attending', 'completed', 'absent', 'cancelled', 'failed'])->default('registered');
            $table->integer('attendance_percentage')->default(0);
            $table->text('absence_reason')->nullable();

            // Assessment
            $table->decimal('assessment_score', 5, 2)->nullable();
            $table->integer('passing_score')->default(70);
            $table->boolean('passed')->default(false);
            $table->date('completion_date')->nullable();
            $table->text('trainer_feedback')->nullable();
            $table->text('employee_feedback')->nullable();
            $table->integer('employee_rating')->nullable(); // rating of the course 1-5

            // Certificate
            $table->string('certificate_path')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('certificate_issue_date')->nullable();
            $table->date('certificate_expiry_date')->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['training_course_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
