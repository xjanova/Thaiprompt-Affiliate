<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fine-grained product impression log. Used for:
 *   - recommendation training ("users who saw X often tapped Y")
 *   - seller-facing analytics ("your product was shown 1,420 times this week")
 *   - region heatmaps ("Khao Soi is viewed most in Bangkok")
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_impressions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('product_id');
            $t->string('surface', 32); // home_nearby | category_page | search | shop_profile | ai_suggest
            $t->unsignedSmallInteger('position')->nullable();
            $t->boolean('tapped')->default(false);
            $t->string('geohash', 12)->nullable();
            $t->string('session_id', 64)->nullable();
            $t->timestamp('ts');
            $t->timestamps();

            $t->index(['product_id', 'ts']);
            $t->index(['user_id', 'ts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_impressions');
    }
};
