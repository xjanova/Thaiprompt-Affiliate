<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');

            $table->integer('score');
            $table->integer('wave_reached')->default(1);
            $table->string('ship_used')->default('basic');
            $table->string('weapon_used')->default('basic');
            $table->integer('playtime_seconds')->default(0);

            $table->timestamps();

            $table->index(['game_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_leaderboards');
    }
};
