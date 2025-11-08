<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

abstract class BaseMarketplaceService implements MarketplaceApiInterface
{
    protected MarketplaceAccount $account;
    protected Client $httpClient;
    protected string $baseUrl;
    protected array $defaultHeaders = [];

    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Make HTTP request to API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     * @return array|null
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], array $headers = []): ?array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $options = [
                'headers' => array_merge($this->defaultHeaders, $headers),
            ];

            if ($method === 'GET') {
                $options['query'] = $params;
            } else {
                $options['json'] = $params;
            }

            Log::info("Marketplace API Request: {$method} {$url}", [
                'account_id' => $this->account->id,
                'platform' => $this->account->platform->slug,
            ]);

            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            Log::info("Marketplace API Response: Success", [
                'account_id' => $this->account->id,
                'status_code' => $response->getStatusCode(),
            ]);

            return $data;
        } catch (GuzzleException $e) {
            Log::error("Marketplace API Error: {$e->getMessage()}", [
                'account_id' => $this->account->id,
                'platform' => $this->account->platform->slug,
                'endpoint' => $endpoint,
            ]);

            // Update account with error
            $this->account->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create sync log
     *
     * @param string $type
     * @param string $status
     * @param array $data
     * @return MarketplaceSyncLog
     */
    protected function createSyncLog(string $type, string $status = 'running', array $data = []): MarketplaceSyncLog
    {
        return MarketplaceSyncLog::create([
            'account_id' => $this->account->id,
            'platform_id' => $this->account->platform_id,
            'sync_type' => $type,
            'sync_status' => $status,
            'triggered_by' => auth()->id(),
            ...$data,
        ]);
    }

    /**
     * Update sync log
     *
     * @param MarketplaceSyncLog $log
     * @param string $status
     * @param array $data
     * @return void
     */
    protected function updateSyncLog(MarketplaceSyncLog $log, string $status, array $data = []): void
    {
        $updates = [
            'sync_status' => $status,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($log->started_at),
            ...$data,
        ];

        $log->update($updates);
    }

    /**
     * Check if token is expired and refresh if needed
     *
     * @return bool
     */
    protected function ensureValidToken(): bool
    {
        if ($this->account->isTokenExpired()) {
            return $this->refreshToken();
        }

        return true;
    }

    /**
     * Normalize product data to our format
     *
     * @param array $rawProduct
     * @return array
     */
    abstract protected function normalizeProduct(array $rawProduct): array;

    /**
     * Normalize order data to our format
     *
     * @param array $rawOrder
     * @return array
     */
    abstract protected function normalizeOrder(array $rawOrder): array;
}
