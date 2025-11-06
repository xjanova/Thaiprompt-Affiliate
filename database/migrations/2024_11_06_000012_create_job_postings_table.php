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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('job_title');
            $table->string('job_code')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('position_id')->nullable()->constrained()->onDelete('set null');
            $table->text('job_description');
            $table->text('requirements');
            $table->text('responsibilities');
            $table->text('qualifications')->nullable();
            $table->text('benefits')->nullable();

            // Employment Details
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'freelance'])->default('full_time');
            $table->string('location');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('THB');
            $table->boolean('show_salary')->default(false);
            $table->integer('number_of_openings')->default(1);

            // Timeline
            $table->date('posting_date');
            $table->date('application_deadline')->nullable();
            $table->date('expected_start_date')->nullable();

            // Contact
            $table->foreignId('hiring_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Status
            $table->enum('status', ['draft', 'open', 'on_hold', 'closed', 'filled'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->boolean('show_on_website')->default(true);

            // SEO
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Stats
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'posting_date']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
