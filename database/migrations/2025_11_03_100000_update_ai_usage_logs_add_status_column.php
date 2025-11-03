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
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            // Check if columns exist before making changes
            if (!Schema::hasColumn('ai_usage_logs', 'status')) {
                // Add status column (will replace the success boolean)
                $table->enum('status', ['success', 'error', 'pending'])->default('success')->after('response_time_ms');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'provider_id')) {
                // Add provider_id foreign key
                $table->foreignId('provider_id')->nullable()->after('message_id')->constrained('ai_providers')->onDelete('set null');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'model_id')) {
                // Add model_id foreign key
                $table->foreignId('model_id')->nullable()->after('provider_id')->constrained('ai_models')->onDelete('set null');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'request_type')) {
                // Add request_type column
                $table->string('request_type')->nullable()->after('model_id');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'metadata')) {
                // Add metadata column
                $table->json('metadata')->nullable()->after('error_message');
            }
        });

        // Migrate data from success boolean to status enum if success column exists
        if (Schema::hasColumn('ai_usage_logs', 'success')) {
            DB::statement("UPDATE ai_usage_logs SET status = IF(success = 1, 'success', 'error')");

            // Drop the old success column
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->dropColumn('success');
            });
        }

        // Migrate data from provider/model strings to IDs if they exist
        if (Schema::hasColumn('ai_usage_logs', 'provider')) {
            // Try to match existing provider names to provider IDs
            DB::statement("
                UPDATE ai_usage_logs ul
                LEFT JOIN ai_providers ap ON ap.provider_type = ul.provider
                SET ul.provider_id = ap.id
                WHERE ap.id IS NOT NULL
            ");

            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }

        if (Schema::hasColumn('ai_usage_logs', 'model')) {
            // Try to match existing model names to model IDs
            DB::statement("
                UPDATE ai_usage_logs ul
                LEFT JOIN ai_models am ON am.model_identifier = ul.model
                SET ul.model_id = am.id
                WHERE am.id IS NOT NULL
            ");

            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->dropColumn('model');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            // Restore old columns
            if (!Schema::hasColumn('ai_usage_logs', 'success')) {
                $table->boolean('success')->default(true)->after('response_time_ms');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'provider')) {
                $table->string('provider')->nullable()->after('message_id');
            }

            if (!Schema::hasColumn('ai_usage_logs', 'model')) {
                $table->string('model')->nullable()->after('provider');
            }

            // Remove new columns
            if (Schema::hasColumn('ai_usage_logs', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('ai_usage_logs', 'provider_id')) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn('provider_id');
            }

            if (Schema::hasColumn('ai_usage_logs', 'model_id')) {
                $table->dropForeign(['model_id']);
                $table->dropColumn('model_id');
            }

            if (Schema::hasColumn('ai_usage_logs', 'request_type')) {
                $table->dropColumn('request_type');
            }

            if (Schema::hasColumn('ai_usage_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
