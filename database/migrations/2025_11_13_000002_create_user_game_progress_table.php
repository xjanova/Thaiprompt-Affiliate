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
        Schema::create('user_game_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');

            // Progress
            $table->integer('highest_score')->default(0);
            $table->integer('highest_wave')->default(1);
            $table->integer('total_plays')->default(0);
            $table->integer('total_kills')->default(0);
            $table->integer('bosses_defeated')->default(0);

            // Time
            $table->integer('total_playtime_seconds')->default(0);
            $table->timestamp('last_played_at')->nullable();

            // Unlocks
            $table->json('unlocked_ships')->nullable(); // ['ship_1', 'ship_2']
            $table->json('unlocked_weapons')->nullable(); // ['laser', 'spread']
            $table->string('current_ship')->default('basic');
            $table->string('current_weapon')->default('basic');

            // Stats
            $table->integer('powerups_collected')->default(0);
            $table->decimal('accuracy_percentage', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_game_progress');
    }
};
