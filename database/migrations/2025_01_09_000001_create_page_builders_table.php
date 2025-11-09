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
        Schema::create('page_builders', function (Blueprint $table) {
            $table->id();
            $table->string('page_type')->index(); // homepage, wiki, about, custom
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('meta_data')->nullable(); // SEO, OG tags, etc.
            $table->json('settings')->nullable(); // Global page settings
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_builders');
    }
};
