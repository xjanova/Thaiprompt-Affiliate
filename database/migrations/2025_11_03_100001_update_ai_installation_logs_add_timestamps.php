<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_installation_logs', function (Blueprint $table) {
            // Add created_at and updated_at if they don't exist
            if (!Schema::hasColumn('ai_installation_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('failed_at');
            }

            if (!Schema::hasColumn('ai_installation_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        // Set created_at to started_at for existing records
        DB::statement("
            UPDATE ai_installation_logs
            SET created_at = started_at,
                updated_at = COALESCE(completed_at, failed_at, started_at)
            WHERE created_at IS NULL
        ");

        // Make started_at nullable
        Schema::table('ai_installation_logs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make started_at not nullable again
        Schema::table('ai_installation_logs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable(false)->change();
        });

        // Remove created_at and updated_at
        Schema::table('ai_installation_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_installation_logs', 'created_at')) {
                $table->dropColumn('created_at');
            }

            if (Schema::hasColumn('ai_installation_logs', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
