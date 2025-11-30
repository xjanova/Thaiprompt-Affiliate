<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง Leaderboard Seasons
     */
    public function up(): void
    {
        // สร้างตาราง leaderboard_seasons
        if (!Schema::hasTable('leaderboard_seasons')) {
            Schema::create('leaderboard_seasons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained()->onDelete('cascade');
                $table->string('name'); // Season 1, Winter 2025, etc
                $table->integer('season_number');
                $table->string('status')->default('upcoming'); // upcoming, active, finished
                // ⚠️ timestamp ต้องมี default value หรือ nullable ใน MySQL strict mode
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('ends_at')->useCurrent();
                $table->json('rewards')->nullable(); // Rewards for top players
                $table->string('theme')->nullable(); // winter, summer, halloween
                $table->string('badge_image')->nullable();
                $table->timestamps();

                $table->index(['game_id', 'status']);
            });
        }

        // Add season_id to game_leaderboards ถ้าตาราง game_leaderboards มีอยู่
        if (Schema::hasTable('game_leaderboards') && !Schema::hasColumn('game_leaderboards', 'season_id')) {
            Schema::table('game_leaderboards', function (Blueprint $table) {
                $table->foreignId('season_id')->nullable()->after('game_id')
                    ->constrained('leaderboard_seasons')->onDelete('set null');
            });

            // เพิ่ม index แยก เพราะอาจมีปัญหา
            try {
                Schema::table('game_leaderboards', function (Blueprint $table) {
                    $table->index(['game_id', 'season_id', 'score'], 'gl_game_season_score_idx');
                });
            } catch (\Exception $e) {
                // Index อาจมีอยู่แล้ว - ข้าม
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('game_leaderboards') && Schema::hasColumn('game_leaderboards', 'season_id')) {
            Schema::table('game_leaderboards', function (Blueprint $table) {
                $table->dropForeign(['season_id']);
                $table->dropColumn('season_id');
            });
        }

        Schema::dropIfExists('leaderboard_seasons');
    }
};
