<?php

namespace App\Services\Exchange;

use App\Models\TradingAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KuCoinConnector extends BaseExchangeConnector
{
    protected string $baseUrl = 'https://api.kucoin.com';

    /**
     * Get ticker data
     */
    public function getTicker(string $symbol): array
    {
        $symbol = $this->normalizeKuCoinSymbol($symbol);

        $response = $this->makeRequest('GET', "/api/v1/market/orderbook/level1", [
            'symbol' => $symbol,
        ]);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to fetch ticker');
        }

        $data = $response['data'];

        return [
            'symbol' => $symbol,
            'last' => (float) $data['price'],
            'bid' => (float) $data['bestBid'],
            'ask' => (float) $data['bestAsk'],
            'high' => (float) ($data['high'] ?? $data['price']),
            'low' => (float) ($data['low'] ?? $data['price']),
            'volume' => (float) ($data['size'] ?? 0),
            'timestamp' => $data['time'],
        ];
    }

    /**
     * Get OHLCV data
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array
    {
        $symbol = $this->normalizeKuCoinSymbol($symbol);
        $type = $this->convertKuCoinTimeframe($timeframe);

        $endAt = time();
        $startAt = $endAt - ($limit * $this->timeframeToSeconds($timeframe));

        $response = $this->makeRequest('GET', '/api/v1/market/candles', [
            'symbol' => $symbol,
            'type' => $type,
            'startAt' => $startAt,
            'endAt' => $endAt,
        ]);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to fetch OHLCV');
        }

        $candles = [];
        foreach ($response['data'] as $candle) {
            $candles[] = [
                'timestamp' => (int) $candle[0] * 1000,
                'open' => (float) $candle[1],
                'close' => (float) $candle[2],
                'high' => (float) $candle[3],
                'low' => (float) $candle[4],
                'volume' => (float) $candle[5],
            ];
        }

        return array_reverse($candles); // KuCoin returns in reverse order
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
        $symbol = $this->normalizeKuCoinSymbol($symbol);

        $clientOid = Str::uuid()->toString();

        $params = [
            'clientOid' => $clientOid,
            'side' => $side,
            'symbol' => $symbol,
            'type' => 'market',
            'size' => (string) $quantity,
        ];

        $response = $this->makeRequest('POST', '/api/v1/orders', $params, true);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to create order');
        }

        $orderId = $response['data']['orderId'];

        // Wait a bit and get order details
        sleep(1);
        $orderDetails = $this->getOrderStatus($account, $orderId, $symbol);

        return [
            'id' => $orderId,
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'market',
            'quantity' => $quantity,
            'price' => (float) ($orderDetails['price'] ?? 0),
            'fee' => (float) ($orderDetails['fee'] ?? 0),
            'status' => $orderDetails['status'] ?? 'filled',
            'timestamp' => time() * 1000,
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
        $symbol = $this->normalizeKuCoinSymbol($symbol);

        $clientOid = Str::uuid()->toString();

        $params = [
            'clientOid' => $clientOid,
            'side' => $side,
            'symbol' => $symbol,
            'type' => 'limit',
            'size' => (string) $quantity,
            'price' => (string) $price,
        ];

        $response = $this->makeRequest('POST', '/api/v1/orders', $params, true);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to create order');
        }

        return [
            'id' => $response['data']['orderId'],
            'symbol' => $symbol,
            'side' => $side,
            'type' => 'limit',
            'quantity' => $quantity,
            'price' => $price,
            'status' => 'open',
            'timestamp' => time() * 1000,
        ];
    }

    /**
     * Cancel order
     */
    public function cancelOrder(TradingAccount $account, string $orderId, string $symbol): bool
    {
        $this->setAccount($account);

        try {
            $response = $this->makeRequest('DELETE', "/api/v1/orders/{$orderId}", [], true);

            return $response['code'] === '200000';
        } catch (\Exception $e) {
            Log::error("Failed to cancel KuCoin order", [
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

        $response = $this->makeRequest('GET', "/api/v1/orders/{$orderId}", [], true);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to get order status');
        }

        $order = $response['data'];

        return [
            'id' => $order['id'],
            'symbol' => $order['symbol'],
            'status' => $this->normalizeOrderStatus($order['isActive'], $order['cancelExist']),
            'side' => strtolower($order['side']),
            'type' => strtolower($order['type']),
            'quantity' => (float) $order['size'],
            'filled' => (float) $order['dealSize'],
            'price' => (float) $order['price'],
            'fee' => (float) ($order['fee'] ?? 0),
        ];
    }

    /**
     * Get account balance
     */
    public function getBalance(TradingAccount $account): array
    {
        $this->setAccount($account);

        $response = $this->makeRequest('GET', '/api/v1/accounts', ['type' => 'trade'], true);

        if ($response['code'] !== '200000') {
            throw new \Exception($response['msg'] ?? 'Failed to get balance');
        }

        $balances = [];
        foreach ($response['data'] as $balance) {
            $available = (float) $balance['available'];
            $holds = (float) $balance['holds'];

            if ($available > 0 || $holds > 0) {
                $balances[$balance['currency']] = [
                    'free' => $available,
                    'locked' => $holds,
                    'total' => $available + $holds,
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
            $this->makeRequest('GET', '/api/v1/accounts', ['type' => 'trade'], true);
            return true;
        } catch (\Exception $e) {
            Log::error("KuCoin connection test failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate signature for KuCoin
     */
    protected function generateSignature(array $params, string $secret): string
    {
        $timestamp = (string) (time() * 1000);
        $method = 'POST';
        $endpoint = '/api/v1/orders';
        $body = json_encode($params);

        $strToSign = $timestamp . $method . $endpoint . $body;

        return base64_encode(hash_hmac('sha256', $strToSign, $secret, true));
    }

    /**
     * Override makeRequest for KuCoin-specific authentication
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], bool $signed = false): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $headers = [
                'Content-Type' => 'application/json',
            ];

            if ($signed && $this->account) {
                $timestamp = (string) (time() * 1000);
                $body = '';

                if (strtoupper($method) === 'POST') {
                    $body = json_encode($params);
                }

                $strToSign = $timestamp . strtoupper($method) . $endpoint . $body;
                $signature = base64_encode(hash_hmac('sha256', $strToSign, $this->account->api_secret, true));

                // Generate passphrase signature
                $passphrase = $this->account->additional_credentials['passphrase'] ?? '';
                $passphraseSign = base64_encode(hash_hmac('sha256', $passphrase, $this->account->api_secret, true));

                $headers['KC-API-KEY'] = $this->account->api_key;
                $headers['KC-API-SIGN'] = $signature;
                $headers['KC-API-TIMESTAMP'] = $timestamp;
                $headers['KC-API-PASSPHRASE'] = $passphraseSign;
                $headers['KC-API-KEY-VERSION'] = '2';
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

            throw new \Exception("KuCoin API error: " . $response->body());

        } catch (\Exception $e) {
            Log::error("KuCoin API request failed", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Normalize symbol for KuCoin (BTC-USDT format)
     */
    protected function normalizeKuCoinSymbol(string $symbol): string
    {
        // Convert BTC/USDT to BTC-USDT
        return str_replace('/', '-', strtoupper($symbol));
    }

    /**
     * Convert timeframe to KuCoin format
     */
    protected function convertKuCoinTimeframe(string $timeframe): string
    {
        return match ($timeframe) {
            '1m' => '1min',
            '5m' => '5min',
            '15m' => '15min',
            '30m' => '30min',
            '1h' => '1hour',
            '4h' => '4hour',
            '1d' => '1day',
            '1w' => '1week',
            default => '1hour',
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
    protected function normalizeOrderStatus(bool $isActive, bool $cancelExist): string
    {
        if ($cancelExist) {
            return 'canceled';
        }

        return $isActive ? 'open' : 'filled';
    }
}
