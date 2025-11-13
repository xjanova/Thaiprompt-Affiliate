<?php

namespace App\Services\Exchange;

use App\Models\TradingAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitkubConnector extends BaseExchangeConnector
{
    protected string $baseUrl = 'https://api.bitkub.com';

    /**
     * Get ticker data
     */
    public function getTicker(string $symbol): array
    {
        // Bitkub uses format: THB_BTC
        $symbol = $this->normalizeBitkubSymbol($symbol);

        $response = $this->makeRequest('GET', '/api/market/ticker', ['sym' => $symbol]);

        $ticker = $response[$symbol] ?? null;
        if (!$ticker) {
            throw new \Exception("Symbol {$symbol} not found");
        }

        return [
            'symbol' => $symbol,
            'last' => (float) $ticker['last'],
            'bid' => (float) ($ticker['highestBid'] ?? $ticker['last']),
            'ask' => (float) ($ticker['lowestAsk'] ?? $ticker['last']),
            'high' => (float) $ticker['high24hr'],
            'low' => (float) $ticker['low24hr'],
            'volume' => (float) $ticker['baseVolume'],
            'timestamp' => time() * 1000,
        ];
    }

    /**
     * Get OHLCV data
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array
    {
        $symbol = $this->normalizeBitkubSymbol($symbol);
        $resolution = $this->convertBitkubTimeframe($timeframe);

        $toTime = time();
        $fromTime = $toTime - ($limit * $this->timeframeToSeconds($timeframe));

        $response = $this->makeRequest('GET', '/tradingview/history', [
            'symbol' => $symbol,
            'resolution' => $resolution,
            'from' => $fromTime,
            'to' => $toTime,
        ]);

        if ($response['s'] !== 'ok') {
            throw new \Exception("Failed to fetch OHLCV data");
        }

        $candles = [];
        $count = count($response['t'] ?? []);

        for ($i = 0; $i < $count; $i++) {
            $candles[] = [
                'timestamp' => $response['t'][$i] * 1000,
                'open' => (float) $response['o'][$i],
                'high' => (float) $response['h'][$i],
                'low' => (float) $response['l'][$i],
                'close' => (float) $response['c'][$i],
                'volume' => (float) $response['v'][$i],
            ];
        }

        return $candles;
    }

    /**
     * Create market order
     */
    public function createMarketOrder(
        TradingAccount $account,
        string $symbol,
        string $side,
        float $quantity
    ): array {
        $this->setAccount($account);
        $symbol = $this->normalizeBitkubSymbol($symbol);

        $params = [
            'sym' => $symbol,
            'amt' => $quantity,
            'typ' => 'market',
        ];

        $endpoint = $side === 'buy' ? '/api/market/place-bid' : '/api/market/place-ask';
        $response = $this->makeRequest('POST', $endpoint, $params, true);

        if ($response['error'] ?? null) {
            throw new \Exception($response['error']);
        }

        return [
            'id' => (string) $response['id'],
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'market',
            'quantity' => $quantity,
            'price' => (float) ($response['rate'] ?? 0),
            'fee' => (float) ($response['fee'] ?? 0),
            'status' => 'filled',
            'timestamp' => $response['ts'] ?? time() * 1000,
        ];
    }

    /**
     * Create limit order
     */
    public function createLimitOrder(
        TradingAccount $account,
        string $symbol,
        string $side,
        float $quantity,
        float $price
    ): array {
        $this->setAccount($account);
        $symbol = $this->normalizeBitkubSymbol($symbol);

        $params = [
            'sym' => $symbol,
            'amt' => $quantity,
            'rat' => $price,
            'typ' => 'limit',
        ];

        $endpoint = $side === 'buy' ? '/api/market/place-bid' : '/api/market/place-ask';
        $response = $this->makeRequest('POST', $endpoint, $params, true);

        if ($response['error'] ?? null) {
            throw new \Exception($response['error']);
        }

        return [
            'id' => (string) $response['id'],
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'limit',
            'quantity' => $quantity,
            'price' => $price,
            'status' => 'open',
            'timestamp' => $response['ts'] ?? time() * 1000,
        ];
    }

    /**
     * Cancel order
     */
    public function cancelOrder(TradingAccount $account, string $orderId, string $symbol): bool
    {
        $this->setAccount($account);
        $symbol = $this->normalizeBitkubSymbol($symbol);

        try {
            $response = $this->makeRequest('POST', '/api/market/cancel-order', [
                'sym' => $symbol,
                'id' => $orderId,
                'sd' => 'buy', // Will be determined by order history
            ], true);

            return !isset($response['error']);
        } catch (\Exception $e) {
            Log::error("Failed to cancel Bitkub order", [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get order status
     */
    public function getOrderStatus(TradingAccount $account, string $orderId, string $symbol): array
    {
        $this->setAccount($account);
        $symbol = $this->normalizeBitkubSymbol($symbol);

        $response = $this->makeRequest('POST', '/api/market/my-order-info', [
            'sym' => $symbol,
            'id' => $orderId,
        ], true);

        if ($response['error'] ?? null) {
            throw new \Exception($response['error']);
        }

        $order = $response['result'] ?? $response;

        return [
            'id' => (string) $order['id'],
            'symbol' => $symbol,
            'status' => $this->normalizeOrderStatus($order),
            'side' => strtolower($order['side'] ?? 'buy'),
            'type' => strtolower($order['type'] ?? 'limit'),
            'quantity' => (float) $order['amount'],
            'filled' => (float) ($order['amount'] - $order['remaining']),
            'price' => (float) $order['rate'],
        ];
    }

    /**
     * Get account balance
     */
    public function getBalance(TradingAccount $account): array
    {
        $this->setAccount($account);

        $response = $this->makeRequest('POST', '/api/market/balances', [], true);

        if ($response['error'] ?? null) {
            throw new \Exception($response['error']);
        }

        $balances = [];
        $result = $response['result'] ?? [];

        foreach ($result as $asset => $balance) {
            $available = (float) $balance['available'];
            $reserved = (float) $balance['reserved'];

            if ($available > 0 || $reserved > 0) {
                $balances[strtoupper($asset)] = [
                    'free' => $available,
                    'locked' => $reserved,
                    'total' => $available + $reserved,
                ];
            }
        }

        return $balances;
    }

    /**
     * Test connection
     */
    public function testConnection(TradingAccount $account): bool
    {
        try {
            $this->setAccount($account);
            $this->makeRequest('POST', '/api/market/balances', [], true);
            return true;
        } catch (\Exception $e) {
            Log::error("Bitkub connection test failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate signature for Bitkub
     */
    protected function generateSignature(array $params, string $secret): string
    {
        $jsonPayload = json_encode($params);
        return hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Override makeRequest for Bitkub-specific authentication
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], bool $signed = false): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $headers = [];

            if ($signed && $this->account) {
                $timestamp = time();
                $params['ts'] = $timestamp;

                $jsonPayload = json_encode($params);
                $signature = hash_hmac('sha256', $timestamp . 'POST' . $endpoint . $jsonPayload, $this->account->api_secret);

                $headers = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-BTK-APIKEY' => $this->account->api_key,
                    'X-BTK-SIGN' => $signature,
                    'X-BTK-TIMESTAMP' => $timestamp,
                ];
            }

            $response = match (strtoupper($method)) {
                'GET' => empty($headers)
                    ? Http::get($url, $params)
                    : Http::withHeaders($headers)->get($url, $params),
                'POST' => empty($headers)
                    ? Http::post($url, $params)
                    : Http::withHeaders($headers)->post($url, $params),
                default => throw new \Exception("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Bitkub API error: " . $response->body());

        } catch (\Exception $e) {
            Log::error("Bitkub API request failed", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Normalize symbol for Bitkub (THB_BTC format)
     */
    protected function normalizeBitkubSymbol(string $symbol): string
    {
        // Convert BTC/THB or BTCTHB to THB_BTC
        $symbol = str_replace('/', '', strtoupper($symbol));

        // Handle common patterns
        if (str_ends_with($symbol, 'THB')) {
            $base = substr($symbol, 0, -3);
            return "THB_{$base}";
        }

        if (str_starts_with($symbol, 'THB')) {
            $base = substr($symbol, 3);
            return "THB_{$base}";
        }

        // Default: assume format is already correct or prepend THB_
        return str_contains($symbol, '_') ? $symbol : "THB_{$symbol}";
    }

    /**
     * Convert timeframe to Bitkub format
     */
    protected function convertBitkubTimeframe(string $timeframe): string
    {
        return match ($timeframe) {
            '1m' => '1',
            '5m' => '5',
            '15m' => '15',
            '30m' => '30',
            '1h' => '60',
            '4h' => '240',
            '1d' => 'D',
            '1w' => 'W',
            default => '60',
        };
    }

    /**
     * Convert timeframe to seconds
     */
    protected function timeframeToSeconds(string $timeframe): int
    {
        return match ($timeframe) {
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h' => 3600,
            '4h' => 14400,
            '1d' => 86400,
            '1w' => 604800,
            default => 3600,
        };
    }

    /**
     * Normalize order status
     */
    protected function normalizeOrderStatus(array $order): string
    {
        $amount = (float) $order['amount'];
        $remaining = (float) ($order['remaining'] ?? 0);

        if ($remaining === 0 && $amount > 0) {
            return 'filled';
        } elseif ($remaining > 0 && $remaining < $amount) {
            return 'partially_filled';
        } elseif ($remaining === $amount) {
            return 'open';
        }

        return 'unknown';
    }
}
