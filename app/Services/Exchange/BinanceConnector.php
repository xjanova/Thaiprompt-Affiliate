<?php

namespace App\Services\Exchange;

use App\Models\TradingAccount;
use Illuminate\Support\Facades\Http;

class BinanceConnector extends BaseExchangeConnector
{
    protected string $baseUrl = 'https://api.binance.com';

    /**
     * Get ticker data
     */
    public function getTicker(string $symbol): array
    {
        $symbol = $this->normalizeSymbol($symbol);
        $response = $this->makeRequest('GET', '/api/v3/ticker/24hr', ['symbol' => $symbol]);

        return [
            'symbol' => $response['symbol'],
            'last' => (float) $response['lastPrice'],
            'bid' => (float) $response['bidPrice'],
            'ask' => (float) $response['askPrice'],
            'high' => (float) $response['highPrice'],
            'low' => (float) $response['lowPrice'],
            'volume' => (float) $response['volume'],
            'timestamp' => $response['closeTime'],
        ];
    }

    /**
     * Get OHLCV data
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array
    {
        $symbol = $this->normalizeSymbol($symbol);
        $interval = $this->convertTimeframe($timeframe);

        $response = $this->makeRequest('GET', '/api/v3/klines', [
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
        ]);

        return array_map(function ($candle) {
            return [
                'timestamp' => $candle[0],
                'open' => (float) $candle[1],
                'high' => (float) $candle[2],
                'low' => (float) $candle[3],
                'close' => (float) $candle[4],
                'volume' => (float) $candle[5],
            ];
        }, $response);
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
        $symbol = $this->normalizeSymbol($symbol);

        $params = [
            'symbol' => $symbol,
            'side' => strtoupper($side),
            'type' => 'MARKET',
            'quantity' => $quantity,
        ];

        $response = $this->makeRequest('POST', '/api/v3/order', $params, true);

        return [
            'id' => (string) $response['orderId'],
            'symbol' => $response['symbol'],
            'side' => strtolower($response['side']),
            'type' => strtolower($response['type']),
            'quantity' => (float) $response['executedQty'],
            'price' => (float) ($response['fills'][0]['price'] ?? 0),
            'fee' => (float) ($response['fills'][0]['commission'] ?? 0),
            'status' => strtolower($response['status']),
            'timestamp' => $response['transactTime'],
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
        $symbol = $this->normalizeSymbol($symbol);

        $params = [
            'symbol' => $symbol,
            'side' => strtoupper($side),
            'type' => 'LIMIT',
            'timeInForce' => 'GTC',
            'quantity' => $quantity,
            'price' => $price,
        ];

        $response = $this->makeRequest('POST', '/api/v3/order', $params, true);

        return [
            'id' => (string) $response['orderId'],
            'symbol' => $response['symbol'],
            'side' => strtolower($response['side']),
            'type' => strtolower($response['type']),
            'quantity' => (float) $response['origQty'],
            'price' => (float) $response['price'],
            'status' => strtolower($response['status']),
            'timestamp' => $response['transactTime'],
        ];
    }

    /**
     * Cancel order
     */
    public function cancelOrder(TradingAccount $account, string $orderId, string $symbol): bool
    {
        $this->setAccount($account);
        $symbol = $this->normalizeSymbol($symbol);

        try {
            $this->makeRequest('DELETE', '/api/v3/order', [
                'symbol' => $symbol,
                'orderId' => $orderId,
            ], true);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get order status
     */
    public function getOrderStatus(TradingAccount $account, string $orderId, string $symbol): array
    {
        $this->setAccount($account);
        $symbol = $this->normalizeSymbol($symbol);

        $response = $this->makeRequest('GET', '/api/v3/order', [
            'symbol' => $symbol,
            'orderId' => $orderId,
        ], true);

        return [
            'id' => (string) $response['orderId'],
            'symbol' => $response['symbol'],
            'status' => strtolower($response['status']),
            'side' => strtolower($response['side']),
            'type' => strtolower($response['type']),
            'quantity' => (float) $response['origQty'],
            'filled' => (float) $response['executedQty'],
            'price' => (float) $response['price'],
        ];
    }

    /**
     * Get account balance
     */
    public function getBalance(TradingAccount $account): array
    {
        $this->setAccount($account);

        $response = $this->makeRequest('GET', '/api/v3/account', [], true);

        $balances = [];
        foreach ($response['balances'] as $balance) {
            $free = (float) $balance['free'];
            $locked = (float) $balance['locked'];

            if ($free > 0 || $locked > 0) {
                $balances[$balance['asset']] = [
                    'free' => $free,
                    'locked' => $locked,
                    'total' => $free + $locked,
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
            $this->makeRequest('GET', '/api/v3/account', [], true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate signature for Binance
     */
    protected function generateSignature(array $params, string $secret): string
    {
        $queryString = http_build_query($params);
        return hash_hmac('sha256', $queryString, $secret);
    }

    /**
     * Sign request with Binance-specific headers
     */
    protected function signRequest(array $params): array
    {
        if (!$this->account) {
            throw new \Exception("Trading account not set");
        }

        $params['timestamp'] = now()->timestamp * 1000;
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->account->api_secret);
        $params['signature'] = $signature;

        return $params;
    }

    /**
     * Override makeRequest to add API key header
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], bool $signed = false): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            if ($signed && $this->account) {
                $params = $this->signRequest($params);
            }

            $headers = [];
            if ($this->account) {
                $headers['X-MBX-APIKEY'] = $this->account->api_key;
            }

            $response = match (strtoupper($method)) {
                'GET' => Http::withHeaders($headers)->get($url, $params),
                'POST' => Http::withHeaders($headers)->post($url, $params),
                'DELETE' => Http::withHeaders($headers)->delete($url, $params),
                default => throw new \Exception("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Binance API error: " . $response->body());

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
