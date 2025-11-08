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
        Schema::create('app_features', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key')->unique();
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('category')->default('general'); // general, social, payment, etc.
            $table->integer('sort_order')->default(0);
            $table->json('config')->nullable(); // Additional configuration for the feature
            $table->string('min_app_version')->nullable(); // Minimum app version required
            $table->string('platform')->nullable(); // android, ios, all
            $table->timestamps();

            $table->index('feature_key');
            $table->index('category');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_features');
    }
};
