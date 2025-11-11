<?php

namespace App\Services\AiGen;

class FreepikProvider extends BaseAiGenProvider
{
    /**
     * Generate image using Freepik API.
     */
    public function generateImage(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $endpoint = $this->getConfig('api_endpoint', 'https://api.freepik.com/v1');

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'API key not configured',
            ];
        }

        // Prepare request data
        $data = [
            'prompt' => $prompt,
            'num_images' => $parameters['num_images'] ?? 1,
            'size' => $parameters['size'] ?? '1024x1024',
            'style' => $parameters['style'] ?? 'realistic',
        ];

        // Add optional parameters
        if (isset($parameters['negative_prompt'])) {
            $data['negative_prompt'] = $parameters['negative_prompt'];
        }

        if (isset($parameters['seed'])) {
            $data['seed'] = $parameters['seed'];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $response = $this->makeRequest('post', $endpoint . '/ai/image/generate', $data, $headers);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $this->parseError($response),
            ];
        }

        // Parse Freepik response
        $result = $response['data'];

        return [
            'success' => true,
            'generation_id' => $result['id'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'images' => $result['images'] ?? [],
            'data' => $result,
        ];
    }

    /**
     * Generate video using Freepik API.
     */
    public function generateVideo(string $prompt, array $parameters = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $endpoint = $this->getConfig('api_endpoint', 'https://api.freepik.com/v1');

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'API key not configured',
            ];
        }

        // Prepare request data
        $data = [
            'prompt' => $prompt,
            'duration' => $parameters['duration'] ?? 5,
            'resolution' => $parameters['resolution'] ?? '1080p',
            'fps' => $parameters['fps'] ?? 30,
        ];

        if (isset($parameters['style'])) {
            $data['style'] = $parameters['style'];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $response = $this->makeRequest('post', $endpoint . '/ai/video/generate', $data, $headers);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $this->parseError($response),
            ];
        }

        $result = $response['data'];

        return [
            'success' => true,
            'generation_id' => $result['id'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'video_url' => $result['video_url'] ?? null,
            'data' => $result,
        ];
    }

    /**
     * Check generation status.
     */
    public function checkStatus(string $generationId): array
    {
        $apiKey = $this->getConfig('api_key');
        $endpoint = $this->getConfig('api_endpoint', 'https://api.freepik.com/v1');

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'API key not configured',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ];

        $response = $this->makeRequest('get', $endpoint . '/ai/generation/' . $generationId, [], $headers);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $this->parseError($response),
            ];
        }

        $result = $response['data'];

        return [
            'success' => true,
            'status' => $result['status'] ?? 'unknown',
            'data' => $result,
        ];
    }

    /**
     * Get result by ID.
     */
    public function getResult(string $generationId): array
    {
        return $this->checkStatus($generationId);
    }

    /**
     * Validate provider is properly configured.
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->getConfig('api_key');
        return !empty($apiKey);
    }

    /**
     * Test API connection.
     */
    public function testConnection(): array
    {
        $apiKey = $this->getConfig('api_key');
        $endpoint = $this->getConfig('api_endpoint', 'https://api.freepik.com/v1');

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'API key not configured',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ];

        $response = $this->makeRequest('get', $endpoint . '/ai/models', [], $headers);

        return [
            'success' => $response['success'],
            'message' => $response['success'] ? 'Connection successful' : $this->parseError($response),
        ];
    }
}
