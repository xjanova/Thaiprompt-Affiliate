<?php

namespace App\Services\Crypto;

use App\Models\CryptoCurrency;
use App\Models\CryptoExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CryptoPriceService
{
    /**
     * Update exchange rates for all active currencies
     *
     * @return array
     */
    public function updateAllRates(): array
    {
        $currencies = CryptoCurrency::active()
            ->where('price_source', '!=', 'manual')
            ->get();

        $results = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($currencies as $currency) {
            try {
                $this->updateRate($currency);
                $results['updated']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to update rate', [
                    'currency' => $currency->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Update exchange rate for specific currency
     *
     * @param CryptoCurrency $currency
     * @return CryptoExchangeRate|null
     */
    public function updateRate(CryptoCurrency $currency): ?CryptoExchangeRate
    {
        // If manual pricing, use the manual price
        if ($currency->price_source === 'manual') {
            return $this->createManualRate($currency);
        }

        // Try API pricing
        $priceData = null;

        // Try CoinGecko first
        if ($currency->coingecko_id) {
            $priceData = $this->fetchFromCoinGecko($currency->coingecko_id);
        }

        // Fallback to Binance
        if (!$priceData && $currency->binance_symbol) {
            $priceData = $this->fetchFromBinance($currency->binance_symbol);
        }

        // Fallback to manual if both APIs fail and fallback is enabled
        if (!$priceData && $currency->price_source === 'both' && $currency->manual_price_thb) {
            return $this->createManualRate($currency);
        }

        if (!$priceData) {
            throw new \Exception('Failed to fetch price data from all sources');
        }

        // Calculate buy/sell rates with spread
        $spreadMultiplier = 1 + ($currency->exchange_fee_percentage / 100);
        $buyRate = $priceData['price_thb'] * $spreadMultiplier;
        $sellRate = $priceData['price_thb'] / $spreadMultiplier;

        // Create exchange rate record
        $rate = CryptoExchangeRate::create([
            'crypto_currency_id' => $currency->id,
            'rate_thb' => $priceData['price_thb'],
            'buy_rate_thb' => $buyRate,
            'sell_rate_thb' => $sellRate,
            'spread_percentage' => $currency->exchange_fee_percentage,
            'source' => 'api',
            'api_source' => $priceData['source'],
            'api_response' => $priceData['raw_response'] ?? null,
            'price_change_24h' => $priceData['change_24h'] ?? null,
            'volume_24h' => $priceData['volume_24h'] ?? null,
            'market_cap' => $priceData['market_cap'] ?? null,
            'valid_from' => now(),
            'valid_until' => now()->addMinutes(5), // Rates valid for 5 minutes
            'updated_by_type' => 'system',
        ]);

        // Clear cache
        Cache::forget("crypto_rate_{$currency->id}");

        return $rate;
    }

    /**
     * Create manual rate from currency settings
     *
     * @param CryptoCurrency $currency
     * @return CryptoExchangeRate
     */
    private function createManualRate(CryptoCurrency $currency): CryptoExchangeRate
    {
        if (!$currency->manual_price_thb) {
            throw new \Exception('Manual price not set for currency: ' . $currency->code);
        }

        $spreadMultiplier = 1 + ($currency->exchange_fee_percentage / 100);
        $buyRate = $currency->manual_price_thb * $spreadMultiplier;
        $sellRate = $currency->manual_price_thb / $spreadMultiplier;

        return CryptoExchangeRate::create([
            'crypto_currency_id' => $currency->id,
            'rate_thb' => $currency->manual_price_thb,
            'buy_rate_thb' => $buyRate,
            'sell_rate_thb' => $sellRate,
            'spread_percentage' => $currency->exchange_fee_percentage,
            'source' => 'manual',
            'api_source' => null,
            'valid_from' => now(),
            'valid_until' => null, // Manual rates don't expire
            'updated_by_type' => 'system',
        ]);
    }

    /**
     * Fetch price from CoinGecko API
     *
     * @param string $coinGeckoId
     * @return array|null
     */
    private function fetchFromCoinGecko(string $coinGeckoId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => $coinGeckoId,
                    'vs_currencies' => 'thb',
                    'include_24hr_change' => 'true',
                    'include_24hr_vol' => 'true',
                    'include_market_cap' => 'true',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (!isset($data[$coinGeckoId]['thb'])) {
                return null;
            }

            $coinData = $data[$coinGeckoId];

            return [
                'price_thb' => $coinData['thb'],
                'change_24h' => $coinData['thb_24h_change'] ?? null,
                'volume_24h' => $coinData['thb_24h_vol'] ?? null,
                'market_cap' => $coinData['thb_market_cap'] ?? null,
                'source' => 'coingecko',
                'raw_response' => $data,
            ];

        } catch (\Exception $e) {
            Log::warning('CoinGecko API error', [
                'coin_id' => $coinGeckoId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch price from Binance API
     *
     * @param string $symbol (e.g., BTCUSDT)
     * @return array|null
     */
    private function fetchFromBinance(string $symbol): ?array
    {
        try {
            // Get crypto/USDT price
            $response = Http::timeout(10)
                ->get('https://api.binance.com/api/v3/ticker/24hr', [
                    'symbol' => $symbol,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            // Get USDT/THB rate
            $usdtThbRate = $this->getUSDTTHBRate();

            if (!$usdtThbRate) {
                return null;
            }

            $priceUsdt = (float) $data['lastPrice'];
            $priceThb = $priceUsdt * $usdtThbRate;
            $change24h = (float) $data['priceChangePercent'];
            $volume24h = (float) $data['volume'] * $priceThb;

            return [
                'price_thb' => $priceThb,
                'change_24h' => $change24h,
                'volume_24h' => $volume24h,
                'market_cap' => null,
                'source' => 'binance',
                'raw_response' => $data,
            ];

        } catch (\Exception $e) {
            Log::warning('Binance API error', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get USDT to THB conversion rate
     *
     * @return float|null
     */
    private function getUSDTTHBRate(): ?float
    {
        // Cache for 5 minutes
        return Cache::remember('usdt_thb_rate', 300, function () {
            try {
                // Try to get THB rate from forex API
                $response = Http::timeout(10)
                    ->get('https://api.exchangerate-api.com/v4/latest/USD');

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates']['THB'])) {
                        return (float) $data['rates']['THB'];
                    }
                }

                // Fallback to approximate rate
                return 33.0; // Approximate USD to THB rate

            } catch (\Exception $e) {
                Log::warning('Failed to get USDT/THB rate', [
                    'error' => $e->getMessage(),
                ]);
                return 33.0; // Fallback
            }
        });
    }

    /**
     * Get current rate for currency (with caching)
     *
     * @param CryptoCurrency $currency
     * @param bool $forceRefresh
     * @return CryptoExchangeRate|null
     */
    public function getCurrentRate(CryptoCurrency $currency, bool $forceRefresh = false): ?CryptoExchangeRate
    {
        $cacheKey = "crypto_rate_{$currency->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () use ($currency) {
            $rate = CryptoExchangeRate::getCurrentRate($currency->id);

            // If no rate or expired, update it
            if (!$rate || $rate->isExpired()) {
                try {
                    $rate = $this->updateRate($currency);
                } catch (\Exception $e) {
                    Log::error('Failed to get current rate', [
                        'currency' => $currency->code,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            }

            return $rate;
        });
    }

    /**
     * Convert crypto amount to THB
     *
     * @param CryptoCurrency $currency
     * @param float $cryptoAmount
     * @param string $type ('buy' or 'sell')
     * @return float|null
     */
    public function convertToTHB(CryptoCurrency $currency, float $cryptoAmount, string $type = 'sell'): ?float
    {
        $rate = $this->getCurrentRate($currency);

        if (!$rate) {
            return null;
        }

        if ($type === 'buy') {
            return $cryptoAmount * $rate->buy_rate_thb;
        } else {
            return $cryptoAmount * $rate->sell_rate_thb;
        }
    }

    /**
     * Convert THB to crypto amount
     *
     * @param CryptoCurrency $currency
     * @param float $thbAmount
     * @param string $type ('buy' or 'sell')
     * @return float|null
     */
    public function convertToCrypto(CryptoCurrency $currency, float $thbAmount, string $type = 'buy'): ?float
    {
        $rate = $this->getCurrentRate($currency);

        if (!$rate) {
            return null;
        }

        if ($type === 'buy') {
            return $thbAmount / $rate->buy_rate_thb;
        } else {
            return $thbAmount / $rate->sell_rate_thb;
        }
    }

    /**
     * Get price chart data (placeholder for future implementation)
     *
     * @param CryptoCurrency $currency
     * @param string $timeframe
     * @return array
     */
    public function getPriceChart(CryptoCurrency $currency, string $timeframe = '24h'): array
    {
        // This would fetch historical data from CoinGecko or Binance
        // For now, return empty array
        return [];
    }

    /**
     * Calculate exchange preview
     *
     * @param CryptoCurrency $currency
     * @param float $amount
     * @param string $fromCurrency ('THB' or crypto code)
     * @param string $type ('buy' or 'sell')
     * @return array|null
     */
    public function calculateExchangePreview(
        CryptoCurrency $currency,
        float $amount,
        string $fromCurrency,
        string $type
    ): ?array {
        $rate = $this->getCurrentRate($currency);

        if (!$rate) {
            return null;
        }

        if ($fromCurrency === 'THB') {
            // Buying crypto with THB
            $cryptoAmount = $this->convertToCrypto($currency, $amount, 'buy');
            $fee = $amount * ($currency->exchange_fee_percentage / 100);

            return [
                'from_currency' => 'THB',
                'from_amount' => $amount,
                'to_currency' => $currency->code,
                'to_amount' => $cryptoAmount,
                'exchange_rate' => $rate->buy_rate_thb,
                'fee_percentage' => $currency->exchange_fee_percentage,
                'fee_amount' => $fee,
                'final_amount' => $cryptoAmount,
                'rate_valid_until' => $rate->valid_until,
            ];
        } else {
            // Selling crypto for THB
            $thbAmount = $this->convertToTHB($currency, $amount, 'sell');
            $fee = $thbAmount * ($currency->exchange_fee_percentage / 100);

            return [
                'from_currency' => $currency->code,
                'from_amount' => $amount,
                'to_currency' => 'THB',
                'to_amount' => $thbAmount,
                'exchange_rate' => $rate->sell_rate_thb,
                'fee_percentage' => $currency->exchange_fee_percentage,
                'fee_amount' => $fee,
                'final_amount' => $thbAmount - $fee,
                'rate_valid_until' => $rate->valid_until,
            ];
        }
    }
}
