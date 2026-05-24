<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds customer-identity context to ai_api_key_usage_logs so the warroom
 * /workers page can deep-link each AI call to the chat thread it's
 * responding to, and so /chat can show "🤖 ตอบโดย groq/llama-3.3" live.
 *
 * Nullable on purpose — many internal calls (Eve, playground, daily-horoscope
 * cron) don't have a target customer and should keep working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_api_key_usage_logs')) {
            return;
        }

        Schema::table('ai_api_key_usage_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_api_key_usage_logs', 'reading_id')) {
                $table->unsignedBigInteger('reading_id')->nullable()->after('request_type')
                    ->comment('fortune_readings.id — link to the chat this AI call answered');
                $table->index('reading_id', 'ai_usage_reading_idx');
            }
            if (! Schema::hasColumn('ai_api_key_usage_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('reading_id')
                    ->comment('users.id of the customer (when known)');
                $table->index('user_id', 'ai_usage_user_idx');
            }
            if (! Schema::hasColumn('ai_api_key_usage_logs', 'fb_user_id')) {
                // varchar(100) to match fortune_readings.facebook_user_id,
                // fortune_comment_engagements.facebook_user_id, etc. — keeps
                // schema diffs / joins clean.
                $table->string('fb_user_id', 100)->nullable()->after('user_id')
                    ->comment('Facebook PSID of the customer (when known)');
                $table->index('fb_user_id', 'ai_usage_fb_user_idx');
            }
            if (! Schema::hasColumn('ai_api_key_usage_logs', 'customer_name')) {
                $table->string('customer_name', 120)->nullable()->after('fb_user_id')
                    ->comment('display name snapshot at call time (denormalized for fast list rendering)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_api_key_usage_logs')) {
            return;
        }

        Schema::table('ai_api_key_usage_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_api_key_usage_logs', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('ai_api_key_usage_logs', 'fb_user_id')) {
                $table->dropIndex('ai_usage_fb_user_idx');
                $table->dropColumn('fb_user_id');
            }
            if (Schema::hasColumn('ai_api_key_usage_logs', 'user_id')) {
                $table->dropIndex('ai_usage_user_idx');
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('ai_api_key_usage_logs', 'reading_id')) {
                $table->dropIndex('ai_usage_reading_idx');
                $table->dropColumn('reading_id');
            }
        });
    }
};
