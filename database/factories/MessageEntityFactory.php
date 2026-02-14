<?php

namespace Database\Factories;

use App\Models\MessageEntity;
use App\Models\MessageSentiment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ MessageEntity
 */
class MessageEntityFactory extends Factory
{
    protected $model = MessageEntity::class;

    public function definition(): array
    {
        return [
            'message_sentiment_id' => MessageSentiment::factory(),
            'entity_type' => fake()->randomElement(['PRODUCT', 'ISSUE', 'LOCATION', 'QUANTITY', 'TIME', 'PERSON', 'CUSTOM']),
            'entity_value' => fake()->word(),
            'display_name' => fake()->word(),
            'entity_text' => fake()->word(),
            'start_position' => 0,
            'end_position' => 10,
            'confidence' => fake()->randomFloat(2, 0.5, 1),
            'entity_source' => 'LEXICON',
            'is_primary' => false,
            'metadata' => [],
        ];
    }
}
