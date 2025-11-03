<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeChunk extends Model
{
    protected $fillable = [
        'knowledge_base_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
        'token_count',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the knowledge base that owns this chunk
     */
    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeBase::class, 'knowledge_base_id');
    }

    /**
     * Calculate cosine similarity with a query embedding
     */
    public function cosineSimilarity(array $queryEmbedding): float
    {
        if (!$this->embedding || empty($this->embedding)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        foreach ($this->embedding as $i => $value) {
            if (!isset($queryEmbedding[$i])) {
                continue;
            }

            $dotProduct += $value * $queryEmbedding[$i];
            $magnitudeA += $value * $value;
            $magnitudeB += $queryEmbedding[$i] * $queryEmbedding[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * Find similar chunks based on embedding
     */
    public static function findSimilar(int $knowledgeBaseId, array $queryEmbedding, int $limit = 5): array
    {
        $chunks = self::where('knowledge_base_id', $knowledgeBaseId)
            ->whereNotNull('embedding')
            ->get();

        $results = [];

        foreach ($chunks as $chunk) {
            $similarity = $chunk->cosineSimilarity($queryEmbedding);

            if ($similarity > 0) {
                $results[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity (highest first)
        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Scope: With embeddings
     */
    public function scopeWithEmbeddings($query)
    {
        return $query->whereNotNull('embedding');
    }
}
