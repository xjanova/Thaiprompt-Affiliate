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
     *
     * @param array $params
     * @return array
     */
    public function chat(array $params): array;

    /**
     * Generate text
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate(string $prompt, array $options = []): array;
}
