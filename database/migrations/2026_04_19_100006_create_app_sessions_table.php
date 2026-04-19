<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_sessions', function (Blueprint $t) {
            $t->string('session_id', 64)->primary();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('duration_sec')->nullable();
            $t->unsignedSmallInteger('screens_viewed')->default(0);
            $t->string('start_geohash', 12)->nullable();
            $t->string('app_version', 16)->nullable();
            $t->string('device_platform', 8)->nullable();
            $t->string('device_tier', 8)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
