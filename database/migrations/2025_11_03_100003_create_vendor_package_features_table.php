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
        Schema::create('vendor_package_features', function (Blueprint $table) {
            $table->id();

            // Feature Information
            $table->string('feature_name', 100);
            $table->string('feature_slug', 100)->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();

            // Pricing
            $table->enum('feature_type', ['one_time', 'recurring'])->default('recurring');
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly');

            // Category
            $table->string('category', 100)->nullable();

            // Requirements
            $table->integer('required_package_level')->default(0);

            // Settings
            $table->json('settings_schema')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('feature_slug');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_package_features');
    }
};
