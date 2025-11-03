<?php

namespace App\Services\AI;

use App\Models\AiProvider;
use App\Models\AiModel;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiUsageLog;

abstract class BaseAiService
{
    protected AiProvider $provider;
    protected AiModel $model;
    protected array $config;

    public function __construct(AiProvider $provider, AiModel $model)
    {
        $this->provider = $provider;
        $this->model = $model;
        $this->config = $provider->config ?? [];
    }

    /**
     * ส่ง chat completion request
     *
     * @param array $messages รูปแบบ [['role' => 'user', 'content' => '...'], ...]
     * @param array $options ตัวเลือกเพิ่มเติม (temperature, max_tokens, etc.)
     * @return array ['content' => string, 'tokens' => int, 'finish_reason' => string]
     */
    abstract public function chat(array $messages, array $options = []): array;

    /**
     * ส่ง completion request แบบ streaming
     *
     * @param array $messages
     * @param array $options
     * @param callable $callback function($chunk) - เรียกทุกครั้งที่ได้ chunk ใหม่
     * @return array
     */
    abstract public function chatStream(array $messages, array $options, callable $callback): array;

    /**
     * สร้าง embeddings
     *
     * @param string $text
     * @return array vector embeddings
     */
    abstract public function createEmbedding(string $text): array;

    /**
     * ทดสอบการเชื่อมต่อ
     *
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    abstract public function testConnection(): array;

    /**
     * บันทึก usage log
     */
    protected function logUsage(
        AiConversation $conversation,
        array $request,
        array $response,
        float $responseTimeMs,
        string $status = 'success',
        ?string $errorMessage = null
    ): AiUsageLog {
        $promptTokens = $response['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $response['usage']['completion_tokens'] ?? 0;
        $totalTokens = $response['usage']['total_tokens'] ?? ($promptTokens + $completionTokens);

        // คำนวณ cost
        $cost = $this->calculateCost($promptTokens, $completionTokens);

        return AiUsageLog::create([
            'user_id' => $conversation->user_id,
            'bot_profile_id' => $conversation->bot_profile_id,
            'conversation_id' => $conversation->id,
            'rental_id' => $conversation->rental_id,
            'provider_id' => $this->provider->id,
            'model_id' => $this->model->id,
            'request_type' => 'chat_completion',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost' => $cost,
            'response_time_ms' => $responseTimeMs,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => [
                'request' => $request,
                'response' => $response,
            ],
        ]);
    }

    /**
     * คำนวณ cost จาก tokens
     */
    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        $pricing = $this->model->pricing ?? [];

        $inputCost = ($promptTokens / 1000) * ($pricing['input'] ?? 0);
        $outputCost = ($completionTokens / 1000) * ($pricing['output'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * จัดรูปแบบ messages ให้เข้ากับ provider
     */
    protected function formatMessages(array $messages): array
    {
        return array_map(function ($message) {
            return [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }, $messages);
    }

    /**
     * ตรวจสอบว่ามี API key หรือไม่
     */
    protected function hasApiKey(): bool
    {
        return !empty($this->config['api_key']);
    }

    /**
     * ดึง API key
     */
    protected function getApiKey(): ?string
    {
        return $this->config['api_key'] ?? null;
    }

    /**
     * ดึง API endpoint
     */
    protected function getEndpoint(): string
    {
        return $this->provider->api_endpoint;
    }

    /**
     * สร้าง headers สำหรับ HTTP request
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * จัดการ HTTP error
     */
    protected function handleHttpError(\Throwable $e): array
    {
        return [
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ];
    }
}
