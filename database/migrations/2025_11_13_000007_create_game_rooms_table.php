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
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('room_code')->unique();
            $table->string('name')->nullable();
            $table->integer('max_players')->default(10);
            $table->integer('current_players')->default(0);
            $table->string('status')->default('waiting'); // waiting, active, finished
            $table->json('settings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
        });

        Schema::create('game_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('game_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('player_name');
            $table->string('skin_slug')->default('classic');
            $table->json('position')->nullable(); // {x, y, z}
            $table->json('direction')->nullable(); // {x, y, z}
            $table->integer('score')->default(0);
            $table->integer('length')->default(3);
            $table->boolean('is_alive')->default(true);
            $table->string('status')->default('active'); // active, dead, disconnected
            $table->timestamp('last_update')->useCurrent();
            $table->timestamps();

            $table->unique(['room_id', 'user_id']);
            $table->index(['room_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_room_players');
        Schema::dropIfExists('game_rooms');
    }
};
