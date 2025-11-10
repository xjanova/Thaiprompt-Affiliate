<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANT: This migration only creates the table structure.
     * All default values are managed by WindowsUiSeeder.
     *
     * Rule: Migrations = Structure, Seeders = Data
     */
    public function up(): void
    {
        Schema::create('windows_ui_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json, color
            $table->timestamps();

            $table->index('key');
        });

        // NO default data insertion here!
        // All default values are managed by database/seeders/WindowsUiSeeder.php
        // This ensures consistency and allows Smart Seeding to work properly
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('windows_ui_settings');
    }
};
