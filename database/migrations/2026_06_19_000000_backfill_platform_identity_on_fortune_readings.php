<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill platform / platform_user_id on existing fortune_readings so the
 * warroom multi-view can collapse a customer's many readings into ONE card
 * reliably — including LINE, whose rows have no facebook_user_id.
 *
 * SAFE by design:
 *   - Fill-only: never overwrites a non-empty platform_user_id.
 *   - platform is only CHANGED when the synthesized user email definitively
 *     says 'line_' (the original ADD COLUMN default backfilled every pre-existing
 *     row to 'facebook', which is correct for FB but wrong for any old LINE row).
 *   - Derives solely from authoritative signals (facebook_user_id, the
 *     bot-synthesized user email fb_<id>@ / line_<id>@). Never guesses.
 *   - Guarded + idempotent: safe to re-run; no-ops if columns/table absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fortune_readings', 'platform_user_id')) {
            return;
        }

        // 1. platform_user_id ← facebook_user_id (FB rows — definitive).
        DB::statement("
            UPDATE fortune_readings
            SET platform_user_id = facebook_user_id
            WHERE (platform_user_id IS NULL OR platform_user_id = '')
              AND facebook_user_id IS NOT NULL AND facebook_user_id <> ''
        ");

        if (! Schema::hasTable('users')) {
            return;
        }

        // 2. platform_user_id ← id embedded in the bot-synthesized user email
        //    (line_<id>@…  → strip 'line_' = 5 chars → start at 6;
        //     fb_<id>@…    → strip 'fb_'   = 3 chars → start at 4).
        DB::statement("
            UPDATE fortune_readings fr
            JOIN users u ON u.id = fr.user_id
            SET fr.platform_user_id = SUBSTRING(SUBSTRING_INDEX(u.email, '@', 1), 6)
            WHERE (fr.platform_user_id IS NULL OR fr.platform_user_id = '')
              AND u.email LIKE 'line\_%@%'
        ");
        DB::statement("
            UPDATE fortune_readings fr
            JOIN users u ON u.id = fr.user_id
            SET fr.platform_user_id = SUBSTRING(SUBSTRING_INDEX(u.email, '@', 1), 4)
            WHERE (fr.platform_user_id IS NULL OR fr.platform_user_id = '')
              AND u.email LIKE 'fb\_%@%'
        ");

        // 3. Correct platform to 'line' where the email definitively says so
        //    (default-backfilled old rows are all 'facebook').
        if (Schema::hasColumn('fortune_readings', 'platform')) {
            DB::statement("
                UPDATE fortune_readings fr
                JOIN users u ON u.id = fr.user_id
                SET fr.platform = 'line'
                WHERE fr.platform <> 'line'
                  AND u.email LIKE 'line\_%@%'
            ");
        }
    }

    public function down(): void
    {
        // Irreversible: backfilled values are indistinguishable from real ones.
    }
};
