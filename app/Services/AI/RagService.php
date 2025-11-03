<?php

namespace App\Services\AI;

use App\Models\AiBotProfile;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\RagUsageLog;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RagService
{
    private EmbeddingService $embeddingService;
    private float $similarityThreshold;
    private int $maxChunks;

    public function __construct(
        ?EmbeddingService $embeddingService = null,
        float $similarityThreshold = 0.7,
        int $maxChunks = 5
    ) {
        $this->embeddingService = $embeddingService ?? new EmbeddingService();
        $this->similarityThreshold = $similarityThreshold;
        $this->maxChunks = $maxChunks;
    }

    /**
     * Search for relevant chunks based on query
     *
     * @param AiBotProfile $bot
     * @param string $query
     * @param int $topK Number of chunks to retrieve
     * @return array ['chunks' => Collection, 'search_time_ms' => float, 'avg_similarity' => float]
     */
    public function search(AiBotProfile $bot, string $query, int $topK = 5): array
    {
        $startTime = microtime(true);

        try {
            // Get query embedding
            $embeddingResult = $this->embeddingService->createEmbedding($query);

            if (!$embeddingResult['success']) {
                Log::error('Failed to create query embedding', [
                    'bot_id' => $bot->id,
                    'error' => $embeddingResult['error'] ?? 'Unknown',
                ]);

                return [
                    'chunks' => collect(),
                    'search_time_ms' => 0,
                    'avg_similarity' => 0,
                    'max_similarity' => 0,
                ];
            }

            $queryEmbedding = $embeddingResult['embedding'];

            // Get all knowledge bases for this bot
            $knowledgeBases = $bot->knowledgeBases()
                ->where('status', 'completed')
                ->pluck('id');

            if ($knowledgeBases->isEmpty()) {
                return [
                    'chunks' => collect(),
                    'search_time_ms' => (microtime(true) - $startTime) * 1000,
                    'avg_similarity' => 0,
                    'max_similarity' => 0,
                ];
            }

            // Get all chunks with embeddings
            $chunks = KnowledgeChunk::whereIn('knowledge_base_id', $knowledgeBases)
                ->whereNotNull('embedding')
                ->get();

            if ($chunks->isEmpty()) {
                return [
                    'chunks' => collect(),
                    'search_time_ms' => (microtime(true) - $startTime) * 1000,
                    'avg_similarity' => 0,
                    'max_similarity' => 0,
                ];
            }

            // Calculate similarity scores
            $chunksWithScores = $chunks->map(function ($chunk) use ($queryEmbedding) {
                $similarity = $chunk->cosineSimilarity($queryEmbedding);
                $chunk->similarity_score = $similarity;
                return $chunk;
            });

            // Filter by threshold and sort by similarity
            $relevantChunks = $chunksWithScores
                ->filter(fn($chunk) => $chunk->similarity_score >= $this->similarityThreshold)
                ->sortByDesc('similarity_score')
                ->take($topK);

            $searchTime = (microtime(true) - $startTime) * 1000;

            $avgSimilarity = $relevantChunks->isNotEmpty()
                ? $relevantChunks->avg('similarity_score')
                : 0;

            $maxSimilarity = $relevantChunks->isNotEmpty()
                ? $relevantChunks->max('similarity_score')
                : 0;

            return [
                'chunks' => $relevantChunks,
                'search_time_ms' => $searchTime,
                'avg_similarity' => round($avgSimilarity, 4),
                'max_similarity' => round($maxSimilarity, 4),
            ];
        } catch (\Exception $e) {
            Log::error('RAG search failed', [
                'bot_id' => $bot->id,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'chunks' => collect(),
                'search_time_ms' => (microtime(true) - $startTime) * 1000,
                'avg_similarity' => 0,
                'max_similarity' => 0,
            ];
        }
    }

    /**
     * Build RAG context from search results
     *
     * @param Collection $chunks
     * @param int $maxTokens Maximum tokens for context
     * @return string
     */
    public function buildContext(Collection $chunks, int $maxTokens = 2000): string
    {
        if ($chunks->isEmpty()) {
            return '';
        }

        $contextParts = [];
        $totalTokens = 0;

        foreach ($chunks as $chunk) {
            $chunkTokens = $chunk->token_count;

            if ($totalTokens + $chunkTokens > $maxTokens) {
                break;
            }

            $contextParts[] = $chunk->content;
            $totalTokens += $chunkTokens;
        }

        if (empty($contextParts)) {
            return '';
        }

        // Format context
        $context = "ข้อมูลที่เกี่ยวข้องจากฐานความรู้:\n\n";
        $context .= implode("\n\n---\n\n", $contextParts);
        $context .= "\n\n---\n\nใช้ข้อมูลข้างต้นในการตอบคำถาม หากข้อมูลไม่เพียงพอ ให้บอกผู้ใช้";

        return $context;
    }

    /**
     * Get context for conversation with RAG
     *
     * @param AiBotProfile $bot
     * @param string $userMessage
     * @param int $maxContextTokens
     * @return array ['context' => string, 'chunks_used' => Collection, 'stats' => array]
     */
    public function getContextForMessage(
        AiBotProfile $bot,
        string $userMessage,
        int $maxContextTokens = 2000
    ): array {
        // Search for relevant chunks
        $searchResult = $this->search($bot, $userMessage, $this->maxChunks);

        $chunks = $searchResult['chunks'];

        if ($chunks->isEmpty()) {
            return [
                'context' => '',
                'chunks_used' => collect(),
                'stats' => [
                    'chunks_retrieved' => 0,
                    'search_time_ms' => $searchResult['search_time_ms'],
                    'avg_similarity' => 0,
                    'max_similarity' => 0,
                    'context_tokens' => 0,
                ],
            ];
        }

        // Build context
        $context = $this->buildContext($chunks, $maxContextTokens);

        // Estimate context tokens
        $contextTokens = (int) ceil(mb_strlen($context) / 3);

        return [
            'context' => $context,
            'chunks_used' => $chunks,
            'stats' => [
                'chunks_retrieved' => $chunks->count(),
                'search_time_ms' => $searchResult['search_time_ms'],
                'avg_similarity' => $searchResult['avg_similarity'],
                'max_similarity' => $searchResult['max_similarity'],
                'context_tokens' => $contextTokens,
            ],
        ];
    }

    /**
     * Log RAG usage
     *
     * @param AiConversation $conversation
     * @param AiMessage $message
     * @param string $query
     * @param array $ragResult
     * @return RagUsageLog
     */
    public function logUsage(
        AiConversation $conversation,
        AiMessage $message,
        string $query,
        array $ragResult
    ): RagUsageLog {
        $chunks = $ragResult['chunks_used'] ?? collect();
        $stats = $ragResult['stats'] ?? [];

        return RagUsageLog::create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'knowledge_base_id' => $chunks->first()?->knowledge_base_id,
            'query' => $query,
            'chunks_retrieved' => $stats['chunks_retrieved'] ?? 0,
            'chunk_ids' => $chunks->pluck('id')->toArray(),
            'search_time_ms' => $stats['search_time_ms'] ?? 0,
            'avg_similarity_score' => $stats['avg_similarity'] ?? 0,
            'max_similarity_score' => $stats['max_similarity'] ?? 0,
            'context_used' => !empty($ragResult['context']),
            'context_tokens' => $stats['context_tokens'] ?? 0,
        ]);
    }

    /**
     * Get RAG statistics for bot
     *
     * @param AiBotProfile $bot
     * @return array
     */
    public function getStatistics(AiBotProfile $bot): array
    {
        $knowledgeBases = $bot->knowledgeBases()->completed()->get();

        $totalChunks = KnowledgeChunk::whereIn(
            'knowledge_base_id',
            $knowledgeBases->pluck('id')
        )->count();

        $totalUsage = RagUsageLog::whereHas('conversation', function ($query) use ($bot) {
            $query->where('bot_profile_id', $bot->id);
        })->count();

        $avgSearchTime = RagUsageLog::whereHas('conversation', function ($query) use ($bot) {
            $query->where('bot_profile_id', $bot->id);
        })->avg('search_time_ms');

        $avgSimilarity = RagUsageLog::whereHas('conversation', function ($query) use ($bot) {
            $query->where('bot_profile_id', $bot->id);
        })->where('chunks_retrieved', '>', 0)->avg('avg_similarity_score');

        return [
            'knowledge_bases' => $knowledgeBases->count(),
            'total_chunks' => $totalChunks,
            'total_usage' => $totalUsage,
            'avg_search_time_ms' => round($avgSearchTime ?? 0, 2),
            'avg_similarity' => round($avgSimilarity ?? 0, 4),
        ];
    }
}
