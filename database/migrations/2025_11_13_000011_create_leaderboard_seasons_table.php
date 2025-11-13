<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Season 1, Winter 2025, etc
            $table->integer('season_number');
            $table->string('status')->default('upcoming'); // upcoming, active, finished
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->json('rewards')->nullable(); // Rewards for top players
            $table->string('theme')->nullable(); // winter, summer, halloween
            $table->string('badge_image')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
        });

        // Add season_id to game_leaderboards
        Schema::table('game_leaderboards', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->after('game_id')
                ->constrained('leaderboard_seasons')->onDelete('set null');
            
            $table->index(['game_id', 'season_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::table('game_leaderboards', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::dropIfExists('leaderboard_seasons');
    }
};
