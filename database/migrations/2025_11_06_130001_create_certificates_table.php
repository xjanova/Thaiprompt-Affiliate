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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique(); // Unique certificate number
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('article_id')->constrained('learning_articles')->onDelete('cascade');
            $table->string('student_name');
            $table->string('course_title');
            $table->decimal('completion_percentage', 5, 2)->default(100);
            $table->decimal('quiz_score', 5, 2)->nullable(); // Average quiz score if applicable
            $table->integer('total_hours')->default(0); // Total learning hours
            $table->timestamp('issued_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('pdf_path')->nullable(); // Path to generated PDF
            $table->string('verification_code')->unique(); // For verification
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            $table->index(['user_id', 'article_id']);
            $table->index('certificate_number');
            $table->index('verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
