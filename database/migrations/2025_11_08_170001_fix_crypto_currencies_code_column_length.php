<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Increase the length of the 'code' column to accommodate longer codes
     * like USDT_POLYGON (12 chars), USDT_ETHEREUM (13 chars), etc.
     */
    public function up(): void
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            // Change code column from VARCHAR(10) to VARCHAR(30)
            $table->string('code', 30)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_currencies', function (Blueprint $table) {
            // Revert back to VARCHAR(10) (may fail if longer codes exist)
            $table->string('code', 10)->unique()->change();
        });
    }
};
