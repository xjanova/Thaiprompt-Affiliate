<?php

namespace Database\Factories;

use App\Models\MessageSentiment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory สำหรับสร้างข้อมูลทดสอบ MessageSentiment
 */
class MessageSentimentFactory extends Factory
{
    protected $model = MessageSentiment::class;

    public function definition(): array
    {
        $message = fake()->sentence();

        return [
            'line_user_id' => 'U'.Str::random(32),
            'user_message' => $message,
            'message_hash' => md5($message.microtime()),
            'sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'sentiment_score' => fake()->randomFloat(2, -1, 1),
            'confidence' => fake()->randomFloat(2, 0, 100),
            'joy_score' => fake()->randomFloat(2, 0, 1),
            'anger_score' => fake()->randomFloat(2, 0, 1),
            'sadness_score' => fake()->randomFloat(2, 0, 1),
            'fear_score' => fake()->randomFloat(2, 0, 1),
            'surprise_score' => fake()->randomFloat(2, 0, 1),
            'language' => 'th',
            'is_complaint' => false,
            'is_urgent' => false,
            'positive_keywords' => [],
            'negative_keywords' => [],
            'neutral_keywords' => [],
        ];
    }
}
