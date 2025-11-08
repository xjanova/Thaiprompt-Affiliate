<?php

namespace App\Services\Exchange;

use App\Models\TradingAccount;
use Illuminate\Support\Facades\Http;

class BybitConnector extends BaseExchangeConnector
{
    protected string $baseUrl = 'https://api.bybit.com';

    /**
     * Get ticker data
     */
    public function getTicker(string $symbol): array
    {
        $symbol = $this->normalizeSymbol($symbol);
        $response = $this->makeRequest('GET', '/v5/market/tickers', [
            'category' => 'spot',
            'symbol' => $symbol,
        ]);

        $ticker = $response['result']['list'][0] ?? [];

        return [
            'symbol' => $ticker['symbol'] ?? $symbol,
            'last' => (float) ($ticker['lastPrice'] ?? 0),
            'bid' => (float) ($ticker['bid1Price'] ?? 0),
            'ask' => (float) ($ticker['ask1Price'] ?? 0),
            'high' => (float) ($ticker['highPrice24h'] ?? 0),
            'low' => (float) ($ticker['lowPrice24h'] ?? 0),
            'volume' => (float) ($ticker['volume24h'] ?? 0),
            'timestamp' => now()->timestamp * 1000,
        ];
    }

    /**
     * Get OHLCV data
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array
    {
        $symbol = $this->normalizeSymbol($symbol);
        $interval = $this->convertTimeframe($timeframe);

        $response = $this->makeRequest('GET', '/v5/market/kline', [
            'category' => 'spot',
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
        ]);

        $candles = $response['result']['list'] ?? [];

        return array_map(function ($candle) {
            return [
                'timestamp' => (int) $candle[0],
                'open' => (float) $candle[1],
                'high' => (float) $candle[2],
                'low' => (float) $candle[3],
                'close' => (float) $candle[4],
                'volume' => (float) $candle[5],
            ];
        }, $candles);
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
            'category' => 'spot',
            'symbol' => $symbol,
            'side' => ucfirst(strtolower($side)),
            'orderType' => 'Market',
            'qty' => (string) $quantity,
        ];

        $response = $this->makeRequest('POST', '/v5/order/create', $params, true);

        return [
            'id' => $response['result']['orderId'] ?? '',
            'symbol' => $symbol,
            'side' => strtolower($side),
            'type' => 'market',
            'quantity' => $quantity,
            'price' => 0, // Market orders don't have set price
            'fee' => 0,
            'status' => 'pending',
            'timestamp' => now()->timestamp * 1000,
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
            'category' => 'spot',
            'symbol' => $symbol,
            'side' => ucfirst(strtolower($side)),
            'orderType' => 'Limit',
            'qty' => (string) $quantity,
            'price' => (string) $price,
        ];

        $response = $this->makeRequest('POST', '/v5/order/create', $params, true);

        return [
            'id' => $response['result']['orderId'] ?? '',
            'symbol' => $symbol,
            'side' => strtolower($side),
            'type' => 'limit',
            'quantity' => $quantity,
            'price' => $price,
            'status' => 'pending',
            'timestamp' => now()->timestamp * 1000,
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
            $this->makeRequest('POST', '/v5/order/cancel', [
                'category' => 'spot',
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

        $response = $this->makeRequest('GET', '/v5/order/realtime', [
            'category' => 'spot',
            'orderId' => $orderId,
        ], true);

        $order = $response['result']['list'][0] ?? [];

        return [
            'id' => $order['orderId'] ?? $orderId,
            'symbol' => $order['symbol'] ?? $symbol,
            'status' => strtolower($order['orderStatus'] ?? 'unknown'),
            'side' => strtolower($order['side'] ?? ''),
            'type' => strtolower($order['orderType'] ?? ''),
            'quantity' => (float) ($order['qty'] ?? 0),
            'filled' => (float) ($order['cumExecQty'] ?? 0),
            'price' => (float) ($order['price'] ?? 0),
        ];
    }

    /**
     * Get account balance
     */
    public function getBalance(TradingAccount $account): array
    {
        $this->setAccount($account);

        $response = $this->makeRequest('GET', '/v5/account/wallet-balance', [
            'accountType' => 'SPOT',
        ], true);

        $balances = [];
        $coins = $response['result']['list'][0]['coin'] ?? [];

        foreach ($coins as $coin) {
            $free = (float) $coin['walletBalance'];
            $locked = (float) $coin['locked'];

            if ($free > 0 || $locked > 0) {
                $balances[$coin['coin']] = [
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
            $this->makeRequest('GET', '/v5/account/wallet-balance', [
                'accountType' => 'SPOT',
            ], true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate signature for Bybit
     */
    protected function generateSignature(array $params, string $secret): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        return hash_hmac('sha256', $queryString, $secret);
    }

    /**
     * Sign request with Bybit-specific headers
     */
    protected function signRequest(array $params): array
    {
        if (!$this->account) {
            throw new \Exception("Trading account not set");
        }

        $timestamp = now()->timestamp * 1000;
        $params['api_key'] = $this->account->api_key;
        $params['timestamp'] = $timestamp;

        ksort($params);
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->account->api_secret);

        $params['sign'] = $signature;

        return $params;
    }

    /**
     * Convert timeframe to Bybit format
     */
    protected function convertTimeframe(string $timeframe): string
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
}
