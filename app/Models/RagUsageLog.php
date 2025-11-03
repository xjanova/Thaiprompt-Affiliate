<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'knowledge_base_id',
        'query',
        'chunks_retrieved',
        'chunk_ids',
        'search_time_ms',
        'avg_similarity_score',
        'max_similarity_score',
        'context_used',
        'context_tokens',
    ];

    protected $casts = [
        'chunk_ids' => 'array',
        'chunks_retrieved' => 'integer',
        'search_time_ms' => 'float',
        'avg_similarity_score' => 'float',
        'max_similarity_score' => 'float',
        'context_used' => 'boolean',
        'context_tokens' => 'integer',
    ];

    /**
     * Get the conversation this log belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Get the message this log belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'message_id');
    }

    /**
     * Get the knowledge base used
     */
    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    /**
     * Get chunks that were used
     */
    public function getChunks()
    {
        if (!$this->chunk_ids) {
            return collect();
        }

        return KnowledgeChunk::whereIn('id', $this->chunk_ids)->get();
    }
}
