<?php

namespace App\Services\Exchange;

use App\Models\TradingAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseExchangeConnector
{
    protected string $baseUrl;
    protected ?TradingAccount $account = null;

    /**
     * Set trading account
     */
    public function setAccount(TradingAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Get ticker data
     */
    abstract public function getTicker(string $symbol): array;

    /**
     * Get OHLCV data
     */
    abstract public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array;

    /**
     * Create market order
     */
    abstract public function createMarketOrder(
        TradingAccount $account,
        string $symbol,
        string $side,
        float $quantity
    ): array;

    /**
     * Create limit order
     */
    abstract public function createLimitOrder(
        TradingAccount $account,
        string $symbol,
        string $side,
        float $quantity,
        float $price
    ): array;

    /**
     * Cancel order
     */
    abstract public function cancelOrder(TradingAccount $account, string $orderId, string $symbol): bool;

    /**
     * Get order status
     */
    abstract public function getOrderStatus(TradingAccount $account, string $orderId, string $symbol): array;

    /**
     * Get account balance
     */
    abstract public function getBalance(TradingAccount $account): array;

    /**
     * Test connection
     */
    abstract public function testConnection(TradingAccount $account): bool;

    /**
     * Generate signature for authenticated requests
     */
    abstract protected function generateSignature(array $params, string $secret): string;

    /**
     * Make HTTP request with error handling
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], bool $signed = false): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            if ($signed && $this->account) {
                $params = $this->signRequest($params);
            }

            $response = match (strtoupper($method)) {
                'GET' => Http::get($url, $params),
                'POST' => Http::post($url, $params),
                'DELETE' => Http::delete($url, $params),
                default => throw new \Exception("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("API request failed: " . $response->body());

        } catch (\Exception $e) {
            Log::error("Exchange API error", [
                'exchange' => static::class,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sign request with API credentials
     */
    protected function signRequest(array $params): array
    {
        if (!$this->account) {
            throw new \Exception("Trading account not set");
        }

        $params['timestamp'] = now()->timestamp * 1000;
        $params['signature'] = $this->generateSignature($params, $this->account->api_secret);

        return $params;
    }

    /**
     * Normalize symbol format
     */
    protected function normalizeSymbol(string $symbol): string
    {
        return str_replace('/', '', strtoupper($symbol));
    }

    /**
     * Convert timeframe to exchange format
     */
    protected function convertTimeframe(string $timeframe): string
    {
        return match ($timeframe) {
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '30m' => '30m',
            '1h' => '1h',
            '4h' => '4h',
            '1d' => '1d',
            '1w' => '1w',
            default => '1h',
        };
    }
}
