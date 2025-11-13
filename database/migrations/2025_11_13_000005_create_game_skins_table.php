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
        Schema::create('game_skins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');

            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();

            // Pricing
            $table->decimal('price', 10, 2)->default(0); // 0 = free
            $table->string('currency')->default('THB');

            // Appearance
            $table->string('color_primary')->nullable(); // #FF0000
            $table->string('color_secondary')->nullable();
            $table->string('pattern')->nullable(); // solid, striped, spotted, gradient
            $table->json('config')->nullable(); // extra config

            // Availability
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_limited')->default(false);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();

            // Requirements
            $table->integer('min_level')->default(1);
            $table->integer('min_score')->default(0);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('user_game_skins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skin_id')->constrained('game_skins')->onDelete('cascade');

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->timestamp('purchased_at');

            $table->timestamps();

            $table->unique(['user_id', 'skin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_game_skins');
        Schema::dropIfExists('game_skins');
    }
};
