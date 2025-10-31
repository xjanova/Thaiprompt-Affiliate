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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('home'); // home, about, contact, etc.
            $table->string('component_type'); // hero, features, stats, faq, cta, etc.
            $table->string('name')->nullable(); // User-friendly name
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->json('content')->nullable(); // Component data (title, subtitle, description, etc.)
            $table->json('styles')->nullable(); // Custom styles (colors, spacing, etc.)
            $table->json('settings')->nullable(); // Component-specific settings
            $table->timestamps();

            $table->index(['page', 'order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
