<?php

namespace Database\Factories;

use App\Models\LineBotKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ LineBotKeyword
 */
class LineBotKeywordFactory extends Factory
{
    protected $model = LineBotKeyword::class;

    public function definition(): array
    {
        return [
            'keyword' => fake()->unique()->word().'_'.fake()->unique()->randomNumber(5),
            'description' => fake()->sentence(),
            'response_type' => 'text',
            'response_text' => fake()->sentence(),
            'category' => fake()->randomElement(['faq', 'support', 'product', 'custom']),
            'priority' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'trigger_words' => [fake()->word(), fake()->word()],
        ];
    }
}
