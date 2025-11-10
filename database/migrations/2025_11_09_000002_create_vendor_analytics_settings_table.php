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
        Schema::create('vendor_analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

            // Retention settings
            $table->integer('retention_days')->default(90); // Keep data for 90 days by default
            $table->boolean('auto_cleanup_enabled')->default(true);

            // Visit tracking settings
            $table->boolean('track_visits')->default(true);
            $table->boolean('track_unique_visitors')->default(true);
            $table->boolean('track_session_duration')->default(true);

            // Privacy settings
            $table->boolean('anonymize_ip')->default(true);
            $table->boolean('respect_dnt')->default(true); // Do Not Track

            // Email notifications
            $table->boolean('email_weekly_reports')->default(false);
            $table->boolean('email_monthly_reports')->default(false);

            $table->timestamps();

            $table->unique('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_analytics_settings');
    }
};
