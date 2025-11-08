<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Make max_images_per_product nullable to support unlimited packages
     * (Enterprise package uses null to represent unlimited)
     */
    public function up(): void
    {
        Schema::table('vendor_packages', function (Blueprint $table) {
            // Make max_images_per_product nullable to allow "unlimited" (null) values
            $table->integer('max_images_per_product')->nullable()->default(10)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_packages', function (Blueprint $table) {
            // Revert back to NOT NULL (may fail if null values exist)
            $table->integer('max_images_per_product')->default(10)->change();
        });
    }
};
