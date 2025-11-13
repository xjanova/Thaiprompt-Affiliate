<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_power_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // speed, shield, magnet, invincibility, double_points
            $table->integer('duration')->default(10); // seconds
            $table->decimal('multiplier', 5, 2)->nullable();
            $table->string('color')->default('#FFD700');
            $table->string('icon')->nullable();
            $table->integer('spawn_probability')->default(5); // percentage
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_power_up_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('power_up_id')->constrained('game_power_ups')->onDelete('cascade');
            $table->foreignId('game_session_id')->nullable();
            $table->timestamp('activated_at');
            $table->integer('duration_used')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'activated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_power_up_activations');
        Schema::dropIfExists('game_power_ups');
    }
};
