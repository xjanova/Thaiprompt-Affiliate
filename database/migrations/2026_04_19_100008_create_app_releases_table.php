<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App release catalog. Powers the in-app auto-update flow.
 *
 * Two ways to populate:
 *   A) Admin UI manual entry (CRUD at /admin/releases)
 *   B) GitHub release webhook (already exists: /api/webhooks/github/release)
 *      On a new release tagged `v*`, the webhook handler parses the release
 *      notes + asset URLs and creates a row here.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $t) {
            $t->id();
            $t->string('version', 32);                 // "1.0.3"
            $t->unsignedInteger('build_number');       // 10003
            $t->string('platform', 16)->default('android'); // android|ios
            $t->string('channel', 16)->default('stable');   // stable|beta|alpha

            $t->string('min_supported_version', 32)->nullable();
            $t->text('release_notes_md')->nullable();

            // Assets
            $t->string('apk_url', 512)->nullable();
            $t->unsignedBigInteger('apk_size_bytes')->nullable();
            $t->string('apk_sha256', 64)->nullable();
            $t->string('aab_url', 512)->nullable();
            $t->string('play_store_url', 512)->nullable();

            $t->boolean('published')->default(true);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();

            $t->unique(['version', 'platform', 'channel']);
            $t->index(['platform', 'channel', 'published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
