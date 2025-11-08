<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Change credentials and test_credentials columns from JSON to TEXT
     * because we store encrypted strings, not plain JSON.
     *
     * MySQL's JSON column type has a CHECK constraint that validates JSON format,
     * but encrypted strings (even though they are technically JSON with iv/value/mac)
     * fail this validation when being stored.
     */
    public function up(): void
    {
        // For MySQL, we need to alter the column type from JSON to LONGTEXT
        // to avoid the CHECK constraint that validates JSON format

        Schema::table('payment_gateways', function (Blueprint $table) {
            // Change credentials from JSON to LONGTEXT
            $table->longText('credentials')->nullable()->change();

            // Change test_credentials from JSON to LONGTEXT
            $table->longText('test_credentials')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            // Revert back to JSON type (though this may fail if encrypted data is present)
            $table->json('credentials')->nullable()->change();
            $table->json('test_credentials')->nullable()->change();
        });
    }
};
