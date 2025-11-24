<?php

namespace App\Services\CloudProviders;

use App\Models\AiRentalCloudConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base Cloud Provider
 *
 * Base class สำหรับ Cloud Providers ทั้งหมด
 * มี utility methods ที่ใช้ร่วมกัน
 */
abstract class BaseCloudProvider implements CloudProviderInterface
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName;

    /**
     * API Base URL
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Default timeout (seconds)
     *
     * @var int
     */
    protected int $timeout = 30;

    /**
     * สร้าง HTTP Client พร้อม authentication
     *
     * @param AiRentalCloudConfig $config
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function createHttpClient(AiRentalCloudConfig $config)
    {
        return Http::timeout($this->timeout)
            ->withHeaders($this->getAuthHeaders($config))
            ->baseUrl($this->baseUrl);
    }

    /**
     * ดึง Auth Headers สำหรับ API
     *
     * @param AiRentalCloudConfig $config
     * @return array
     */
    abstract protected function getAuthHeaders(AiRentalCloudConfig $config): array;

    /**
     * Log API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return void
     */
    protected function logRequest(string $method, string $endpoint, array $data = []): void
    {
        Log::info("[{$this->providerName}] API Request", [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
        ]);
    }

    /**
     * Log API response
     *
     * @param int $statusCode
     * @param mixed $response
     * @return void
     */
    protected function logResponse(int $statusCode, $response): void
    {
        Log::info("[{$this->providerName}] API Response", [
            'status_code' => $statusCode,
            'response' => $response,
        ]);
    }

    /**
     * Log error
     *
     * @param string $message
     * @param \Exception|null $exception
     * @return void
     */
    protected function logError(string $message, ?\Exception $exception = null): void
    {
        Log::error("[{$this->providerName}] Error: {$message}", [
            'exception' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }

    /**
     * Handle API error response
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return array
     */
    protected function handleErrorResponse($response): array
    {
        $statusCode = $response->status();
        $body = $response->json();

        $this->logResponse($statusCode, $body);

        return [
            'success' => false,
            'message' => $body['error'] ?? $body['message'] ?? "API Error: {$statusCode}",
            'status_code' => $statusCode,
        ];
    }

    /**
     * ทดสอบ Inference Endpoint (Common implementation)
     *
     * @param string $endpointUrl
     * @param array $payload
     * @return array
     */
    public function testInference(string $endpointUrl, array $payload): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout(30)
                ->post($endpointUrl, $payload);

            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json(),
                    'latency_ms' => $latencyMs,
                    'message' => 'Inference test successful',
                ];
            }

            return [
                'success' => false,
                'response' => null,
                'latency_ms' => $latencyMs,
                'message' => "HTTP {$response->status()}: " . ($response->json()['error'] ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            $this->logError('Inference test failed', $e);

            return [
                'success' => false,
                'response' => null,
                'latency_ms' => 0,
                'message' => "Error: {$e->getMessage()}",
            ];
        }
    }

    /**
     * แปลง GPU type เป็นรูปแบบที่ provider ต้องการ
     *
     * @param string $instanceType
     * @return string
     */
    protected function normalizeGpuType(string $instanceType): string
    {
        // Default: ใช้ตามที่ได้รับมา
        return $instanceType;
    }

    /**
     * สร้าง deployment name
     *
     * @param string $modelName
     * @return string
     */
    protected function generateDeploymentName(string $modelName): string
    {
        $slug = \Illuminate\Support\Str::slug($modelName);
        $random = \Illuminate\Support\Str::random(6);
        return "{$slug}-{$random}";
    }
}
