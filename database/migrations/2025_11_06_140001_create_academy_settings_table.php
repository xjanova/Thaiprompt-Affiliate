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
        Schema::create('academy_settings', function (Blueprint $table) {
            $table->id();

            // Basic Academy Information
            $table->string('academy_name')->default('Academy Platform');
            $table->text('academy_description')->nullable();
            $table->string('academy_email')->nullable();
            $table->string('academy_phone')->nullable();
            $table->string('academy_website')->nullable();

            // Logo & Branding
            $table->string('logo_path')->nullable(); // Main logo
            $table->string('small_logo_path')->nullable(); // Small logo for certificates
            $table->string('favicon_path')->nullable();
            $table->string('primary_color')->default('#6366f1');
            $table->string('secondary_color')->default('#06b6d4');

            // Certificate Template Settings
            $table->string('certificate_template_type')->default('default'); // default, custom, uploaded
            $table->text('certificate_header_html')->nullable(); // Custom HTML for header
            $table->text('certificate_footer_html')->nullable(); // Custom HTML for footer
            $table->string('certificate_background_path')->nullable(); // Background image
            $table->string('certificate_border_style')->default('classic'); // classic, modern, minimal

            // Signatures
            $table->json('signatures')->nullable(); // Array of signatures with position
            // Structure: [{ name, title, signature_path, position: 'left'|'right'|'center' }]

            // Certificate Template Files
            $table->string('certificate_word_template_path')->nullable(); // Word template
            $table->string('certificate_pdf_template_path')->nullable(); // PDF template

            // Email Settings
            $table->boolean('send_certificate_email')->default(true);
            $table->text('certificate_email_subject')->nullable();
            $table->text('certificate_email_body')->nullable();

            // Course Completion Settings
            $table->integer('min_completion_percentage')->default(100); // Min % to get certificate
            $table->integer('min_quiz_score')->default(70); // Min quiz score to get certificate
            $table->boolean('require_all_quizzes')->default(false);

            // Certificate Numbering
            $table->string('certificate_number_prefix')->default('CERT');
            $table->integer('certificate_number_length')->default(8);
            $table->string('certificate_number_format')->default('CERT-{YEAR}{MONTH}-{RANDOM}');

            // Other Settings
            $table->boolean('enable_student_reviews')->default(true);
            $table->boolean('enable_course_discussions')->default(true);
            $table->boolean('enable_course_notes')->default(true);
            $table->boolean('enable_course_downloads')->default(true);
            $table->boolean('require_enrollment_approval')->default(false);
            $table->boolean('enable_course_preview')->default(true);

            // Instructor Settings
            $table->boolean('allow_instructors_create_courses')->default(true);
            $table->boolean('require_course_approval')->default(true);
            $table->integer('instructor_commission_percentage')->default(0);

            // System Settings
            $table->boolean('is_active')->default(true);
            $table->json('additional_settings')->nullable(); // For future extensibility

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_settings');
    }
};
