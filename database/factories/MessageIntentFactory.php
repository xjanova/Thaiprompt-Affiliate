<?php

namespace Database\Factories;

use App\Models\MessageIntent;
use App\Models\MessageSentiment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ MessageIntent
 */
class MessageIntentFactory extends Factory
{
    protected $model = MessageIntent::class;

    public function definition(): array
    {
        return [
            'message_sentiment_id' => MessageSentiment::factory(),
            'primary_intent' => fake()->randomElement(['COMPLAINT', 'INQUIRY', 'PURCHASE', 'FEEDBACK', 'SUPPORT', 'OTHER']),
            'primary_confidence' => fake()->randomFloat(2, 0.5, 1),
            'intent_source' => 'KEYWORDS',
            'priority_level' => fake()->randomElement(['LOW', 'NORMAL', 'HIGH', 'URGENT']),
            'secondary_intents' => [],
            'intent_scores' => [],
            'trigger_keywords' => [],
            'suggested_actions' => [],
        ];
    }
}
