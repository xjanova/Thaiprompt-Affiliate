<?php

namespace App\Services\AI;

/**
 * AI Service Interface
 *
 * Interface สำหรับ AI Services ทั้งหมด
 */
interface AiServiceInterface
{
    /**
     * Send chat completion request
     */
    public function chat(array $params): array;

    /**
     * Generate text
     */
    public function generate(string $prompt, array $options = []): array;
}
