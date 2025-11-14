<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตารางไอเทมในห้องเกม
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบก่อนสร้างเสมอ
        if (Schema::hasTable('game_room_items')) {
            return;
        }

        Schema::create('game_room_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('game_rooms')->onDelete('cascade');
            $table->string('item_type'); // food, powerup_magnet, powerup_speed, powerup_multiplier
            $table->json('position'); // {x, y, z}
            $table->integer('value')->default(1); // คะแนนหรือค่าของไอเทม
            $table->timestamp('spawned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // หมดอายุใน 1 นาที
            $table->boolean('is_collected')->default(false);
            $table->foreignId('collected_by')->nullable()->constrained('game_room_players')->onDelete('set null');
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'is_collected']);
            $table->index(['room_id', 'expires_at']);
        });
    }

    /**
     * ลบตาราง game_room_items
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('game_room_items');
    }
};
