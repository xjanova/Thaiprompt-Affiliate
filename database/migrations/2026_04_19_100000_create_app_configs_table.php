<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remote app configuration.
 * One row per (key, environment). Mobile apps fetch the whole set in a single
 * call and cache it with an ETag.
 *
 * Example keys:
 *   maintenance_banner      → string
 *   min_app_version_android → "1.2.0"
 *   wallet_topup_min_thb    → 20
 *   ai_runtime              → "gemma4" | "gemma3_4b" | "gemma3_1b" | "server"
 *   tts_voice_id            → "th_TH-vaja-medium"
 *   support_phone           → "+66xx"
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_configs', function (Blueprint $t) {
            $t->id();
            $t->string('key')->index();
            $t->string('environment', 16)->default('production'); // production | staging | dev
            $t->text('value')->nullable();
            $t->string('value_type', 16)->default('string'); // string|int|float|bool|json
            $t->string('description')->nullable();
            $t->boolean('is_public')->default(true); // if false, only authenticated users get this key
            $t->timestamps();
            $t->unique(['key', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_configs');
    }
};
