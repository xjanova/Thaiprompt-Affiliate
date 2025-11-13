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
        Schema::create('user_daily_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_claim_date')->nullable();
            $table->integer('total_claims')->default(0);
            $table->decimal('total_rewards', 10, 2)->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('daily_reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('claim_date');
            $table->integer('day_number'); // Day 1, 2, 3... of streak
            $table->string('reward_type'); // coins, skin, boost, premium
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->string('reward_item')->nullable();
            $table->boolean('bonus_applied')->default(false); // Milestone bonus
            $table->timestamps();

            $table->index(['user_id', 'claim_date']);
        });

        Schema::create('game_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // daily, weekly, special
            $table->string('objective'); // play_games, reach_score, kill_count, survive_time
            $table->integer('target'); // Target value to complete
            $table->json('requirements')->nullable(); // Additional requirements
            $table->string('reward_type'); // coins, skin, boost
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->string('reward_item')->nullable();
            $table->integer('reward_points')->default(0);
            $table->integer('difficulty')->default(1); // 1-5
            $table->boolean('is_active')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();
            $table->timestamps();
        });

        Schema::create('user_mission_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mission_id')->constrained('game_missions')->onDelete('cascade');
            $table->integer('current_progress')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mission_id']);
            $table->index(['user_id', 'is_completed', 'is_claimed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mission_progress');
        Schema::dropIfExists('game_missions');
        Schema::dropIfExists('daily_reward_claims');
        Schema::dropIfExists('user_daily_streaks');
    }
};
