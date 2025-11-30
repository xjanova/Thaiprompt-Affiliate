<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง Tournaments สำหรับระบบแข่งขัน
     */
    public function up(): void
    {
        // Game Tournaments - ข้อมูลการแข่งขัน
        if (!Schema::hasTable('game_tournaments')) {
            Schema::create('game_tournaments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('slug')->unique();
                $table->string('type'); // solo, team, battle_royale
                $table->string('status')->default('upcoming'); // upcoming, active, finished, cancelled
                $table->integer('max_participants')->default(100);
                $table->integer('entry_fee')->default(0);
                $table->json('prize_pool')->nullable(); // [1st => 1000, 2nd => 500, 3rd => 250]
                $table->json('rules')->nullable();
                $table->timestamp('registration_starts')->nullable();
                $table->timestamp('registration_ends')->nullable();
                // ⚠️ timestamp ต้องมี default value หรือ nullable ใน MySQL strict mode
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('ends_at')->useCurrent();
                $table->integer('duration_minutes')->default(60);
                $table->string('banner_image')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->timestamps();

                $table->index(['status', 'starts_at']);
            });
        }

        // Tournament Participants - ผู้เข้าแข่งขัน
        if (!Schema::hasTable('tournament_participants')) {
            Schema::create('tournament_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tournament_id')->constrained('game_tournaments')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('player_name');
                $table->integer('best_score')->default(0);
                $table->integer('total_games')->default(0);
                $table->integer('rank')->nullable();
                $table->boolean('paid_entry')->default(false);
                $table->boolean('is_disqualified')->default(false);
                $table->decimal('prize_won', 10, 2)->default(0);
                // ⚠️ timestamp ต้องมี default value หรือ nullable
                $table->timestamp('registered_at')->useCurrent();
                $table->timestamps();

                $table->unique(['tournament_id', 'user_id']);
                $table->index(['tournament_id', 'best_score']);
            });
        }

        // Tournament Matches - ผลการแข่งขัน
        if (!Schema::hasTable('tournament_matches')) {
            Schema::create('tournament_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tournament_id')->constrained('game_tournaments')->onDelete('cascade');
                $table->foreignId('participant_id')->constrained('tournament_participants')->onDelete('cascade');
                $table->integer('score');
                $table->integer('rank_in_match')->nullable();
                $table->json('stats')->nullable(); // kills, time, powerups, etc
                // ⚠️ timestamp ต้องมี default value หรือ nullable
                $table->timestamp('played_at')->useCurrent();
                $table->timestamps();

                $table->index(['tournament_id', 'score']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_participants');
        Schema::dropIfExists('game_tournaments');
    }
};
