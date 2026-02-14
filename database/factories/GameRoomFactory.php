<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRoom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ GameRoom
 */
class GameRoomFactory extends Factory
{
    protected $model = GameRoom::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'room_code' => strtoupper(Str::random(6)),
            'name' => fake()->words(2, true),
            'max_players' => 10,
            'current_players' => 0,
            'status' => 'waiting',
            'settings' => [],
        ];
    }
}
