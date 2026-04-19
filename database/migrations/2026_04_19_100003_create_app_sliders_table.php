<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Home-screen sliders.
 *
 * NOTE: existing `sliders` table in Thaiprompt-Affiliate is used by the web
 * frontend. This separate `app_sliders` table exists because mobile targeting
 * (geohash, app-version) is incompatible with the web schema. Duplicate is OK
 * — admin UI can share a writer that updates both.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_sliders', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('order')->default(0);
            $t->string('title_th');
            $t->string('title_en')->nullable();
            $t->string('subtitle_th')->nullable();
            $t->string('cta_label_th')->nullable();
            $t->string('cta_deeplink')->nullable();

            // Media
            $t->string('media_type', 16)->default('image'); // image | video
            $t->string('media_url');
            $t->string('media_url_dark')->nullable();

            // Theme
            $t->string('bg_gradient', 64)->nullable(); // e.g. "pink-tomato"
            $t->string('text_color', 16)->nullable(); // "#fff"

            // Targeting
            $t->string('region_geohash', 12)->nullable();
            $t->string('min_app_version', 16)->nullable();
            $t->json('target_segment')->nullable(); // {rank:"silver+", purchases_gte:3}

            $t->boolean('enabled')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();

            $t->index(['enabled', 'starts_at', 'ends_at', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sliders');
    }
};
