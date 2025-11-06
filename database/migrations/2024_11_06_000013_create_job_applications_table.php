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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->onDelete('cascade');
            $table->string('application_number')->unique();

            // Applicant Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();

            // Application Details
            $table->string('resume_path')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->text('cover_letter_text')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('additional_documents')->nullable();

            // Work Experience
            $table->integer('years_of_experience')->nullable();
            $table->text('current_company')->nullable();
            $table->text('current_position')->nullable();
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->integer('notice_period_days')->nullable();
            $table->date('available_from')->nullable();

            // Education
            $table->string('highest_education')->nullable();
            $table->string('university')->nullable();
            $table->string('major')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();

            // Additional Info
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            $table->text('referral_source')->nullable(); // how they found the job

            // Application Status
            $table->enum('status', [
                'new',
                'reviewing',
                'shortlisted',
                'interview_scheduled',
                'interviewed',
                'second_interview',
                'offer_pending',
                'offer_sent',
                'offer_accepted',
                'offer_rejected',
                'rejected',
                'withdrawn'
            ])->default('new');

            // Interview Details
            $table->dateTime('interview_date')->nullable();
            $table->string('interview_location')->nullable();
            $table->text('interview_notes')->nullable();
            $table->integer('interview_rating')->nullable();
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Evaluation
            $table->integer('overall_rating')->nullable(); // 1-5 or 1-10
            $table->text('evaluation_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            // Offer Details
            $table->decimal('offered_salary', 12, 2)->nullable();
            $table->date('offer_date')->nullable();
            $table->date('offer_response_deadline')->nullable();
            $table->date('joining_date')->nullable();

            // System
            $table->string('source_ip')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['job_posting_id', 'status']);
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
