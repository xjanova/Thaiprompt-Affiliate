<?php

namespace Database\Factories;

use App\Models\KeywordCluster;
use App\Models\KeywordClusterItem;
use App\Models\LineBotKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ KeywordClusterItem
 */
class KeywordClusterItemFactory extends Factory
{
    protected $model = KeywordClusterItem::class;

    public function definition(): array
    {
        return [
            'keyword_cluster_id' => KeywordCluster::factory(),
            'line_bot_keyword_id' => LineBotKeyword::factory(),
            'relationship_type' => fake()->randomElement(['PRIMARY', 'SYNONYM', 'RELATED', 'VARIANT', 'SIMILAR']),
            'relevance_score' => fake()->randomFloat(2, 0, 1),
            'co_occurrence_count' => fake()->numberBetween(0, 100),
            'context_data' => [],
        ];
    }
}
