<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics event log. Raw events — aggregations live in separate rollup
 * tables (see AnalyticsRollupJob in future patches).
 *
 * High-volume table — expect 100-500k rows/day once we ship. Strategies:
 *   - Index on (user_id, ts) and (event_name, ts) only
 *   - Auto-delete rows > 180 days via scheduled `app:cleanup-analytics` command
 *   - Partition by month if/when this exceeds 50M rows
 *
 * Privacy:
 *   - `geohash` stored at precision 5 (≈5km) for home/search/browse events
 *   - `geohash` stored at precision 7 (≈150m) ONLY for active-order events
 *   - `ip` never stored directly; we only keep the /24 prefix
 *   - Users who opt out of analytics → we don't insert at all
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_events', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('session_id', 64)->nullable();
            $t->string('event_name', 48);
            $t->json('props')->nullable();

            // Location (privacy-safe)
            $t->string('geohash', 12)->nullable();

            // Device
            $t->string('device_platform', 8)->nullable(); // android|ios
            $t->string('device_tier', 8)->nullable();     // low|mid|high
            $t->string('app_version', 16)->nullable();

            $t->ipAddress('ip_prefix')->nullable(); // store /24 only; see controller
            $t->timestamp('ts');
            $t->timestamps();

            $t->index(['user_id', 'ts']);
            $t->index(['event_name', 'ts']);
            $t->index(['geohash', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
