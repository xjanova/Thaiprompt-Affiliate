<?php

namespace Database\Factories;

use App\Models\KeywordCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ KeywordCluster
 */
class KeywordClusterFactory extends Factory
{
    protected $model = KeywordCluster::class;

    public function definition(): array
    {
        return [
            'cluster_name' => fake()->unique()->word().'_'.fake()->unique()->randomNumber(5),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'cluster_category' => fake()->randomElement(['PRODUCT', 'SERVICE', 'ISSUE', 'FEATURE', 'FEEDBACK', 'OTHER']),
            'keyword_count' => fake()->numberBetween(0, 50),
            'total_matches' => fake()->numberBetween(0, 1000),
            'usage_frequency' => fake()->randomFloat(2, 0, 1),
            'is_active' => true,
            'is_system' => false,
            'primary_keywords' => [fake()->word()],
            'related_intents' => [],
            'related_entities' => [],
            'suggested_actions' => [],
        ];
    }
}
