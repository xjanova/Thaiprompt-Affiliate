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
     * Separate LINE Login Channel from Messaging API Channel
     * LINE now requires different Channel IDs for:
     * - LINE Login (OAuth authentication)
     * - Messaging API (sending messages)
     */
    public function up(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            // Rename existing channel_id to login_channel_id (for LINE Login)
            $table->renameColumn('channel_id', 'login_channel_id');

            // Add messaging_channel_id (for Messaging API - optional reference)
            $table->string('messaging_channel_id')->nullable()->after('login_channel_id')
                ->comment('Channel ID for LINE Messaging API (optional, different from Login Channel)');
        });

        // Update column comments for clarity
        DB::statement("ALTER TABLE line_oa_settings MODIFY COLUMN login_channel_id VARCHAR(255) NULL COMMENT 'Channel ID for LINE Login (OAuth)'");
        DB::statement("ALTER TABLE line_oa_settings MODIFY COLUMN channel_secret VARCHAR(255) NULL COMMENT 'Channel Secret for LINE Login (OAuth)'");
        DB::statement("ALTER TABLE line_oa_settings MODIFY COLUMN redirect_uri VARCHAR(500) NULL COMMENT 'Callback URL for LINE Login. Must match URL registered in LINE Developers Console'");
        DB::statement("ALTER TABLE line_oa_settings MODIFY COLUMN channel_access_token TEXT NULL COMMENT 'Long-lived Channel Access Token for LINE Messaging API'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_oa_settings', function (Blueprint $table) {
            $table->renameColumn('login_channel_id', 'channel_id');
            $table->dropColumn('messaging_channel_id');
        });
    }
};
