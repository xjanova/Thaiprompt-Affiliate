<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FreshMarketMessage - ข้อความในการสนทนาตลาดสด
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role user|assistant|system
 * @property string $content
 * @property array|null $metadata
 * @property int $tokens_used
 * @property string|null $ai_provider
 * @property string|null $ai_model
 */
class FreshMarketMessage extends Model
{
    protected $table = 'fresh_market_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'tokens_used',
        'ai_provider',
        'ai_model',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
    ];

    /**
     * การสนทนาที่ข้อความนี้อยู่
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(FreshMarketConversation::class, 'conversation_id');
    }
}
