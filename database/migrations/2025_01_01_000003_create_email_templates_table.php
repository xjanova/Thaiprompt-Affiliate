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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // welcome_email, password_reset, etc.
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->string('category')->default('system'); // system, marketing, transactional
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->string('language')->default('th');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['name', 'language']);
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
