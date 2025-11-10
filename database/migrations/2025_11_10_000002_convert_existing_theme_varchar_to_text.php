<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use SafeMigration;

    /**
     * Run the migrations.
     *
     * This migration converts existing VARCHAR columns to TEXT to reduce row size
     * and make room for additional columns in the app_theme_settings table.
     */
    public function up(): void
    {
        // Change all existing color VARCHAR columns to TEXT
        // This frees up significant row space as TEXT only uses ~10 bytes in the row
        DB::statement('ALTER TABLE app_theme_settings
            MODIFY COLUMN primary_color TEXT,
            MODIFY COLUMN primary_dark_color TEXT,
            MODIFY COLUMN primary_light_color TEXT,
            MODIFY COLUMN secondary_color TEXT,
            MODIFY COLUMN secondary_dark_color TEXT,
            MODIFY COLUMN secondary_light_color TEXT,
            MODIFY COLUMN background_color TEXT,
            MODIFY COLUMN background_dark_color TEXT,
            MODIFY COLUMN surface_color TEXT,
            MODIFY COLUMN surface_dark_color TEXT,
            MODIFY COLUMN text_primary_color TEXT,
            MODIFY COLUMN text_primary_dark_color TEXT,
            MODIFY COLUMN text_secondary_color TEXT,
            MODIFY COLUMN text_secondary_dark_color TEXT,
            MODIFY COLUMN success_color TEXT,
            MODIFY COLUMN warning_color TEXT,
            MODIFY COLUMN error_color TEXT,
            MODIFY COLUMN info_color TEXT,
            MODIFY COLUMN border_color TEXT,
            MODIFY COLUMN border_dark_color TEXT,
            MODIFY COLUMN divider_color TEXT,
            MODIFY COLUMN divider_dark_color TEXT,
            MODIFY COLUMN animation_curve TEXT
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to VARCHAR(255) if needed
        DB::statement('ALTER TABLE app_theme_settings
            MODIFY COLUMN primary_color VARCHAR(255) DEFAULT "#3B82F6",
            MODIFY COLUMN primary_dark_color VARCHAR(255) DEFAULT "#2563EB",
            MODIFY COLUMN primary_light_color VARCHAR(255) DEFAULT "#60A5FA",
            MODIFY COLUMN secondary_color VARCHAR(255) DEFAULT "#8B5CF6",
            MODIFY COLUMN secondary_dark_color VARCHAR(255) DEFAULT "#7C3AED",
            MODIFY COLUMN secondary_light_color VARCHAR(255) DEFAULT "#A78BFA",
            MODIFY COLUMN background_color VARCHAR(255) DEFAULT "#FFFFFF",
            MODIFY COLUMN background_dark_color VARCHAR(255) DEFAULT "#1F2937",
            MODIFY COLUMN surface_color VARCHAR(255) DEFAULT "#F9FAFB",
            MODIFY COLUMN surface_dark_color VARCHAR(255) DEFAULT "#374151",
            MODIFY COLUMN text_primary_color VARCHAR(255) DEFAULT "#111827",
            MODIFY COLUMN text_primary_dark_color VARCHAR(255) DEFAULT "#F9FAFB",
            MODIFY COLUMN text_secondary_color VARCHAR(255) DEFAULT "#6B7280",
            MODIFY COLUMN text_secondary_dark_color VARCHAR(255) DEFAULT "#D1D5DB",
            MODIFY COLUMN success_color VARCHAR(255) DEFAULT "#10B981",
            MODIFY COLUMN warning_color VARCHAR(255) DEFAULT "#F59E0B",
            MODIFY COLUMN error_color VARCHAR(255) DEFAULT "#EF4444",
            MODIFY COLUMN info_color VARCHAR(255) DEFAULT "#3B82F6",
            MODIFY COLUMN border_color VARCHAR(255) DEFAULT "#E5E7EB",
            MODIFY COLUMN border_dark_color VARCHAR(255) DEFAULT "#4B5563",
            MODIFY COLUMN divider_color VARCHAR(255) DEFAULT "#E5E7EB",
            MODIFY COLUMN divider_dark_color VARCHAR(255) DEFAULT "#4B5563",
            MODIFY COLUMN animation_curve VARCHAR(255) DEFAULT "ease-in-out"
        ');
    }
};
