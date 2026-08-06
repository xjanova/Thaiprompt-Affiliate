<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private AiProvider $provider;

    private AiModel $model;

    // 🩹 (2026-05-08) ทำให้เป็น nullable + default ''
    //   เหตุผล: api_endpoint/api_key ใน DB อาจเป็น null → assign ตรงๆ TypeError
    //   → service boot fail ตอน route:list → autoload crash → LINE webhook 500
    private string $endpoint = '';

    private string $apiKey = '';

    public function __construct(?AiProvider $provider = null)
    {
        // Use OpenAI by default for embeddings
        if (! $provider) {
            $provider = AiProvider::where('name', 'openai')
                ->where('is_active', true)
                ->first();

            if (! $provider) {
                throw new \Exception('OpenAI provider not found or not active');
            }
        }

        $this->provider = $provider;
        // 🩹 fallback empty string ถ้า DB ค่าว่าง — caller ต้องเช็คก่อนใช้
        $this->endpoint = (string) ($provider->api_endpoint ?? '');
        $this->apiKey = (string) ($provider->api_key ?? '');

        // Get embedding model.
        // 🩹 (2026-06-04) Resolve into a LOCAL first, fall back, THEN assign the
        //   typed property. Assigning ->first() (nullable) straight to the
        //   non-nullable AiModel $model threw "Cannot assign null to property
        //   $model of type AiModel" AT this line — before the fallback below could
        //   run — crashing service boot (php artisan route:list / any artisan
        //   eager-boot) when no active embedding model row exists. Same class of
        //   bug as the endpoint/apiKey fix above; $model was the one left unfixed.
        $model = AiModel::where('provider_id', $provider->id)
            ->where('model_identifier', 'LIKE', '%embedding%')
            ->where('is_active', true)
            ->first();

        if (! $model) {
            // Fallback to text-embedding-3-small
            $model = new AiModel([
                'model_identifier' => 'text-embedding-3-small',
                'display_name' => 'Text Embedding 3 Small',
                'context_window' => 8191,
            ]);
        }

        $this->model = $model;
    }

    /**
     * Create embedding for a single text
     *
     * @return array ['success' => bool, 'embedding' => array, 'dimension' => int, 'tokens' => int]
     */
    public function createEmbedding(string $text): array
    {
        try {
            if ($this->provider->name === 'openai') {
                return $this->createOpenAiEmbedding($text);
            }

            return [
                'success' => false,
                'error' => 'Provider not supported for embeddings: '.$this->provider->name,
            ];
        } catch (\Exception $e) {
            Log::error('Embedding creation failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider->name,
                'text_length' => strlen($text),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create embeddings for multiple texts (batch)
     */
    public function createEmbeddings(array $texts): array
    {
        $results = [];
        $totalTokens = 0;

        foreach ($texts as $index => $text) {
            $result = $this->createEmbedding($text);

            if ($result['success']) {
                $results[] = [
                    'index' => $index,
                    'embedding' => $result['embedding'],
                    'dimension' => $result['dimension'],
                    'tokens' => $result['tokens'],
                ];
                $totalTokens += $result['tokens'];
            } else {
                $results[] = [
                    'index' => $index,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'total_tokens' => $totalTokens,
            'count' => count($texts),
        ];
    }

    /**
     * Create OpenAI embedding
     */
    private function createOpenAiEmbedding(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->endpoint.'/embeddings', [
            'model' => $this->model->model_identifier,
            'input' => $text,
            'encoding_format' => 'float',
        ]);

        if (! $response->successful()) {
            throw new \Exception('OpenAI API error: '.$response->body());
        }

        $data = $response->json();

        return [
            'success' => true,
            'embedding' => $data['data'][0]['embedding'] ?? [],
            'dimension' => count($data['data'][0]['embedding'] ?? []),
            'tokens' => $data['usage']['total_tokens'] ?? 0,
        ];
    }

    /**
     * Calculate cosine similarity between two embeddings
     */
    public static function cosineSimilarity(array $embedding1, array $embedding2): float
    {
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
     * Estimate token count for text
     */
    private function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get embedding dimension for current model
     */
    public function getEmbeddingDimension(): int
    {
        // OpenAI text-embedding-3-small: 1536 dimensions
        // OpenAI text-embedding-3-large: 3072 dimensions
        if (str_contains($this->model->model_identifier, 'text-embedding-3-small')) {
            return 1536;
        } elseif (str_contains($this->model->model_identifier, 'text-embedding-3-large')) {
            return 3072;
        } elseif (str_contains($this->model->model_identifier, 'nomic')) {
            return 768;
        }

        return 1536; // Default
    }
}
