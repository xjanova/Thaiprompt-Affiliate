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
        Schema::create('page_builder_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_builder_id')->constrained()->onDelete('cascade');
            $table->string('section_type'); // hero, features, stats, testimonials, cta, custom, html
            $table->string('name')->nullable();
            $table->integer('order')->default(0)->index();
            $table->json('settings')->nullable(); // Layout, colors, spacing, animations
            $table->json('content')->nullable(); // Actual content data
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_builder_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_builder_sections');
    }
};
