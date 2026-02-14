<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ Game
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'title' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->sentence(),
            'category' => 'arcade',
            'is_active' => true,
            'requires_auth' => false,
            'min_level_required' => 1,
            'order' => fake()->numberBetween(1, 100),
            'settings' => [],
            'meta_data' => [],
        ];
    }
}
