<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeBase extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'kb_type',
        'title',
        'description',
        'source_url',
        'file_path',
        'content',
        'metadata',
        'has_embeddings',
        'chunk_size',
        'chunk_overlap',
        'total_chunks',
        'embedding_model',
        'is_active',
        'last_processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'has_embeddings' => 'boolean',
        'is_active' => 'boolean',
        'last_processed_at' => 'datetime',
    ];

    /**
     * Get the bot profile that owns this knowledge base
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get all chunks for this knowledge base
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'knowledge_base_id');
    }

    /**
     * Get chunks with embeddings
     */
    public function chunksWithEmbeddings(): HasMany
    {
        return $this->chunks()->whereNotNull('embedding');
    }

    /**
     * Scope: Only active knowledge bases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('kb_type', $type);
    }

    /**
     * Scope: With embeddings
     */
    public function scopeWithEmbeddings($query)
    {
        return $query->where('has_embeddings', true);
    }

    /**
     * Check if knowledge base needs processing
     */
    public function needsProcessing(): bool
    {
        if (!$this->has_embeddings) {
            return true;
        }

        if ($this->kb_type === 'url' && $this->last_processed_at) {
            // Re-process URLs older than 7 days
            return $this->last_processed_at->diffInDays(now()) > 7;
        }

        return false;
    }

    /**
     * Get processing status
     */
    public function getProcessingStatus(): string
    {
        if (!$this->has_embeddings) {
            return 'pending';
        }

        if ($this->needsProcessing()) {
            return 'outdated';
        }

        return 'ready';
    }

    /**
     * Update chunk statistics
     */
    public function updateChunkStatistics(): void
    {
        $this->total_chunks = $this->chunks()->count();
        $this->has_embeddings = $this->chunksWithEmbeddings()->count() > 0;
        $this->save();
    }
}
