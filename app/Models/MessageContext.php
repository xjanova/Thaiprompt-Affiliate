<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MessageContext Model
 *
 * เก็บข้อมูล context ของแต่ละข้อความ
 * ใช้สำหรับการวิเคราะห์บทสนทนาแบบ context-aware
 *
 * @property int $id
 * @property int $message_sentiment_id
 * @property string|null $line_user_id
 * @property int|null $conversation_id
 * @property string $context_type
 * @property int|null $parent_message_sentiment_id
 * @property int $message_position
 * @property int $similar_messages_count
 * @property int $conversation_length
 * @property int|null $minutes_since_last_message
 * @property int $conversation_duration_minutes
 * @property array|null $conversation_summary
 * @property array|null $topic_stack
 * @property array|null $entities_mentioned
 * @property array|null $intents_in_conversation
 * @property array|null $mentioned_keywords
 * @property float|null $sentiment_trend
 * @property string|null $conversation_sentiment_direction
 * @property array|null $predicted_next_intent
 * @property array|null $recommended_responses
 * @property float|null $urgency_escalation_score
 */
class MessageContext extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message_contexts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_sentiment_id',
        'line_user_id',
        'conversation_id',
        'context_type',
        'parent_message_sentiment_id',
        'message_position',
        'similar_messages_count',
        'conversation_length',
        'minutes_since_last_message',
        'conversation_duration_minutes',
        'conversation_summary',
        'topic_stack',
        'entities_mentioned',
        'intents_in_conversation',
        'mentioned_keywords',
        'sentiment_trend',
        'conversation_sentiment_direction',
        'predicted_next_intent',
        'recommended_responses',
        'urgency_escalation_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conversation_summary' => 'json',
        'topic_stack' => 'json',
        'entities_mentioned' => 'json',
        'intents_in_conversation' => 'json',
        'mentioned_keywords' => 'json',
        'predicted_next_intent' => 'json',
        'recommended_responses' => 'json',
        'sentiment_trend' => 'float',
        'urgency_escalation_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ MessageSentiment
     *
     * @return BelongsTo
     */
    public function sentiment(): BelongsTo
    {
        return $this->belongsTo(MessageSentiment::class, 'message_sentiment_id');
    }

    /**
     * ความสัมพันธ์กับ parent message
     *
     * @return BelongsTo
     */
    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(MessageSentiment::class, 'parent_message_sentiment_id');
    }

    /**
     * Scope: ดึง first messages ในแต่ละ conversation
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFirstMessages($query)
    {
        return $query->where('context_type', 'FIRST_MESSAGE');
    }

    /**
     * Scope: ดึง follow-up messages
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFollowUps($query)
    {
        return $query->where('context_type', 'FOLLOW_UP');
    }

    /**
     * Scope: ดึง escalation messages
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscalations($query)
    {
        return $query->where('context_type', 'ESCALATION');
    }

    /**
     * Scope: ดึง conversations ที่ improving
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImproving($query)
    {
        return $query->where('conversation_sentiment_direction', 'IMPROVING');
    }

    /**
     * Scope: ดึง conversations ที่ declining
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeclining($query)
    {
        return $query->where('conversation_sentiment_direction', 'DECLINING');
    }

    /**
     * Scope: ดึง high urgency escalations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold ค่าต่ำสุด (default: 0.7)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighUrgency($query, float $threshold = 0.7)
    {
        return $query->where('urgency_escalation_score', '>=', $threshold);
    }

    /**
     * Scope: ดึง messages จาก user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $lineUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromUser($query, string $lineUserId)
    {
        return $query->where('line_user_id', $lineUserId);
    }

    /**
     * ตรวจสอบว่า context เป็น first message
     *
     * @return bool
     */
    public function isFirstMessage(): bool
    {
        return $this->context_type === 'FIRST_MESSAGE';
    }

    /**
     * ตรวจสอบว่า conversation กำลัง improving
     *
     * @return bool
     */
    public function isSentimentImproving(): bool
    {
        return $this->conversation_sentiment_direction === 'IMPROVING';
    }

    /**
     * ตรวจสอบว่า urgency escalating
     *
     * @return bool
     */
    public function isUrgencyEscalating(): bool
    {
        return $this->urgency_escalation_score !== null && $this->urgency_escalation_score >= 0.7;
    }

    /**
     * ดึง estimated next intent
     *
     * @return string|null
     */
    public function getNextIntentAttribute(): ?string
    {
        if (empty($this->predicted_next_intent) || !is_array($this->predicted_next_intent)) {
            return null;
        }

        return $this->predicted_next_intent['intent'] ?? null;
    }

    /**
     * ดึง recommended response
     *
     * @return array|null
     */
    public function getFirstRecommendedResponseAttribute(): ?array
    {
        if (empty($this->recommended_responses) || !is_array($this->recommended_responses)) {
            return null;
        }

        return $this->recommended_responses[0] ?? null;
    }

    /**
     * ดึง topic count
     *
     * @return int
     */
    public function getTopicCountAttribute(): int
    {
        return count($this->topic_stack ?? []);
    }

    /**
     * ดึง entities count
     *
     * @return int
     */
    public function getEntityCountAttribute(): int
    {
        return count($this->entities_mentioned ?? []);
    }

    /**
     * ดึง intents count
     *
     * @return int
     */
    public function getIntentCountAttribute(): int
    {
        return count($this->intents_in_conversation ?? []);
    }

    /**
     * Update urgency escalation score
     *
     * @param float $newScore
     * @return void
     */
    public function updateUrgencyScore(float $newScore): void
    {
        $this->update(['urgency_escalation_score' => min(max($newScore, 0), 1)]);
    }

    /**
     * Add topic to stack
     *
     * @param string $topic
     * @return void
     */
    public function addTopic(string $topic): void
    {
        $topics = $this->topic_stack ?? [];
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->update(['topic_stack' => $topics]);
        }
    }

    /**
     * Add entity to mentioned list
     *
     * @param array $entity
     * @return void
     */
    public function addMentionedEntity(array $entity): void
    {
        $entities = $this->entities_mentioned ?? [];
        $entities[] = $entity;
        $this->update(['entities_mentioned' => $entities]);
    }
}
