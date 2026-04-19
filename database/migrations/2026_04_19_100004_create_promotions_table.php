<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing promotions. Separate from discount_codes (the existing
 * seller/cart-level promo code table).
 *
 * Types:
 *   flash   — time-limited discount on a category
 *   coupon  — generic coupon code
 *   banner  — informational
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $t) {
            $t->id();
            $t->string('type', 16); // flash | coupon | banner
            $t->string('title_th');
            $t->string('title_en')->nullable();
            $t->string('subtitle_th')->nullable();
            $t->string('cover_image')->nullable();
            $t->string('deeplink')->nullable();

            // Mechanics (nullable depending on type)
            $t->string('code', 64)->nullable();
            $t->decimal('discount_percent', 5, 2)->nullable();
            $t->decimal('discount_amount', 12, 2)->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('usage_count')->default(0);

            // Targeting
            $t->string('region_geohash', 12)->nullable();
            $t->json('target_segment')->nullable();
            $t->unsignedSmallInteger('priority')->default(0);

            $t->boolean('enabled')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();

            $t->index(['enabled', 'starts_at', 'ends_at', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
