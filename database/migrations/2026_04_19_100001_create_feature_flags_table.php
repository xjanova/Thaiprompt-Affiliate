<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature flags with targeting.
 *
 * Targeting dimensions (ALL may be null = "match any"):
 *   - min_app_version:    "1.0.0"  — only show to app >= this version
 *   - platform:            android|ios
 *   - role:                user|seller|admin|…
 *   - region_geohash:      prefix like "w5c" to target Bangkok
 *   - rollout_percent:     0-100 — hash(user_id + flag_key) < this → enabled
 *
 * Per-user overrides live in a future `feature_flag_user_overrides` table (not
 * created here — wait until we actually need per-user kill switches).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $t) {
            $t->id();
            $t->string('flag_key')->unique();
            $t->boolean('enabled')->default(false);
            $t->string('description')->nullable();

            // Targeting
            $t->string('min_app_version', 16)->nullable();
            $t->string('max_app_version', 16)->nullable();
            $t->string('platform', 8)->nullable();
            $t->string('role')->nullable();
            $t->string('region_geohash', 12)->nullable();
            $t->unsignedTinyInteger('rollout_percent')->default(100);

            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();

            $t->index(['enabled', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
