<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id',
        'content',
        'chunk_index',
        'token_count',
        'start_position',
        'end_position',
        'embedding',
        'embedding_dimension',
        'page_number',
        'section_title',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'start_position' => 'integer',
        'end_position' => 'integer',
        'embedding_dimension' => 'integer',
    ];

    /**
     * Get the knowledge base that owns this chunk
     */
    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    /**
     * Calculate cosine similarity with another embedding
     *
     * @param array $otherEmbedding
     * @return float
     */
    public function cosineSimilarity(array $otherEmbedding): float
    {
        if (!$this->embedding || empty($this->embedding)) {
            return 0.0;
        }

        $embedding1 = $this->embedding;
        $embedding2 = $otherEmbedding;

        if (count($embedding1) !== count($embedding2)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $norm1 += $embedding1[$i] * $embedding1[$i];
            $norm2 += $embedding2[$i] * $embedding2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($norm1 * $norm2);
    }

    /**
     * Get content preview (first 100 characters)
     */
    public function getPreviewAttribute(): string
    {
        return mb_substr($this->content, 0, 100) . (mb_strlen($this->content) > 100 ? '...' : '');
    }

    /**
     * Check if chunk has embedding
     */
    public function hasEmbedding(): bool
    {
        return !empty($this->embedding);
    }
}
