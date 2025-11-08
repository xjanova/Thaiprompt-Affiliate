<?php

namespace App\Http\Controllers;

use App\Models\CryptoCurrency;
use App\Models\CryptoPriceHistory;
use App\Services\Crypto\CryptoPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cryptocurrency Price Chart Controller
 *
 * Handles price charts, comparisons, and analytics
 */
class CryptoPriceChartController extends Controller
{
    protected CryptoPriceService $priceService;

    public function __construct(CryptoPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Show price chart dashboard
     */
    public function index()
    {
        $currencies = CryptoCurrency::where('is_active', true)
            ->with('latestRate')
            ->orderBy('market_cap', 'desc')
            ->get();

        // Get current prices with 24h changes
        $priceData = $currencies->map(function ($currency) {
            $rate = $currency->latestRate;

            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'icon' => $currency->icon_url,
                'price_thb' => $rate->rate_thb ?? 0,
                'price_usd' => $rate->rate_usd ?? 0,
                'change_24h' => $rate->change_24h ?? 0,
                'volume_24h' => $rate->volume_24h ?? 0,
                'market_cap' => $currency->market_cap ?? 0,
                'network' => $currency->network,
            ];
        });

        return view('crypto.price-charts.index', [
            'currencies' => $currencies,
            'priceData' => $priceData,
        ]);
    }

    /**
     * Get chart data for specific currency
     */
    public function getChartData(Request $request, $currencyId)
    {
        $period = $request->get('period', '24h');

        $cacheKey = "crypto_chart_{$currencyId}_{$period}";

        return Cache::remember($cacheKey, 300, function () use ($currencyId, $period) {
            $currency = CryptoCurrency::findOrFail($currencyId);

            // Calculate time range
            [$from, $interval] = $this->getPeriodRange($period);

            // Get price history
            $priceHistory = CryptoPriceHistory::where('crypto_currency_id', $currencyId)
                ->where('created_at', '>=', $from)
                ->orderBy('created_at', 'asc')
                ->get();

            // Format data for Chart.js
            $chartData = $priceHistory->map(function ($history) {
                return [
                    'time' => $history->created_at->format('Y-m-d H:i:s'),
                    'timestamp' => $history->created_at->timestamp * 1000, // JavaScript timestamp
                    'price_thb' => (float) $history->rate_thb,
                    'price_usd' => (float) $history->rate_usd,
                    'volume' => (float) ($history->volume_24h ?? 0),
                ];
            });

            // Calculate statistics
            $prices = $priceHistory->pluck('rate_thb')->filter();
            $high = $prices->max();
            $low = $prices->min();
            $avg = $prices->avg();
            $current = $prices->last();
            $first = $prices->first();
            $change = $first > 0 ? (($current - $first) / $first) * 100 : 0;

            return [
                'success' => true,
                'currency' => [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ],
                'period' => $period,
                'data' => $chartData,
                'statistics' => [
                    'current' => $current,
                    'high' => $high,
                    'low' => $low,
                    'average' => round($avg, 2),
                    'change' => round($change, 2),
                    'change_amount' => round($current - $first, 2),
                ],
            ];
        });
    }

    /**
     * Get comparison data for multiple currencies
     */
    public function getComparisonData(Request $request)
    {
        $currencyIds = $request->get('currencies', []);
        $period = $request->get('period', '24h');

        if (empty($currencyIds) || count($currencyIds) > 5) {
            return response()->json([
                'success' => false,
                'error' => 'Please select 1-5 currencies to compare',
            ], 400);
        }

        $cacheKey = "crypto_compare_" . implode('_', $currencyIds) . "_{$period}";

        return Cache::remember($cacheKey, 300, function () use ($currencyIds, $period) {
            [$from, $interval] = $this->getPeriodRange($period);

            $compareData = [];

            foreach ($currencyIds as $currencyId) {
                $currency = CryptoCurrency::find($currencyId);
                if (!$currency) continue;

                $priceHistory = CryptoPriceHistory::where('crypto_currency_id', $currencyId)
                    ->where('created_at', '>=', $from)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Normalize to percentage change from start
                $firstPrice = $priceHistory->first()->rate_thb ?? 1;
                $normalizedData = $priceHistory->map(function ($history) use ($firstPrice) {
                    $percentChange = (($history->rate_thb - $firstPrice) / $firstPrice) * 100;

                    return [
                        'timestamp' => $history->created_at->timestamp * 1000,
                        'percent_change' => round($percentChange, 2),
                        'price' => (float) $history->rate_thb,
                    ];
                });

                $compareData[] = [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'color' => $this->getCurrencyColor($currency->code),
                    'data' => $normalizedData,
                ];
            }

            return [
                'success' => true,
                'period' => $period,
                'currencies' => $compareData,
            ];
        });
    }

    /**
     * Get real-time price updates (for WebSocket/polling)
     */
    public function getRealTimePrices(Request $request)
    {
        $currencyIds = $request->get('currencies', []);

        $prices = CryptoCurrency::whereIn('id', $currencyIds)
            ->with('latestRate')
            ->get()
            ->map(function ($currency) {
                $rate = $currency->latestRate;

                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'price_thb' => $rate->rate_thb ?? 0,
                    'price_usd' => $rate->rate_usd ?? 0,
                    'change_24h' => $rate->change_24h ?? 0,
                    'updated_at' => $rate->updated_at ?? now(),
                ];
            });

        return response()->json([
            'success' => true,
            'prices' => $prices,
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Get market overview statistics
     */
    public function getMarketOverview()
    {
        $cacheKey = 'crypto_market_overview';

        return Cache::remember($cacheKey, 300, function () {
            $currencies = CryptoCurrency::where('is_active', true)
                ->with('latestRate')
                ->get();

            $totalMarketCap = $currencies->sum('market_cap');
            $totalVolume24h = $currencies->sum(function ($currency) {
                return $currency->latestRate->volume_24h ?? 0;
            });

            $gainers = $currencies->filter(function ($currency) {
                $rate = $currency->latestRate;
                return $rate && ($rate->change_24h ?? 0) > 0;
            })->sortByDesc(function ($currency) {
                return $currency->latestRate->change_24h ?? 0;
            })->take(5);

            $losers = $currencies->filter(function ($currency) {
                $rate = $currency->latestRate;
                return $rate && ($rate->change_24h ?? 0) < 0;
            })->sortBy(function ($currency) {
                return $currency->latestRate->change_24h ?? 0;
            })->take(5);

            return [
                'success' => true,
                'overview' => [
                    'total_market_cap' => $totalMarketCap,
                    'total_volume_24h' => $totalVolume24h,
                    'active_currencies' => $currencies->count(),
                ],
                'top_gainers' => $gainers->map(fn($c) => [
                    'code' => $c->code,
                    'name' => $c->name,
                    'change_24h' => $c->latestRate->change_24h ?? 0,
                    'price_thb' => $c->latestRate->rate_thb ?? 0,
                ]),
                'top_losers' => $losers->map(fn($c) => [
                    'code' => $c->code,
                    'name' => $c->name,
                    'change_24h' => $c->latestRate->change_24h ?? 0,
                    'price_thb' => $c->latestRate->rate_thb ?? 0,
                ]),
            ];
        });
    }

    /**
     * Get period range for queries
     */
    protected function getPeriodRange(string $period): array
    {
        return match($period) {
            '1h' => [now()->subHour(), '1 minute'],
            '24h' => [now()->subDay(), '10 minutes'],
            '7d' => [now()->subDays(7), '1 hour'],
            '30d' => [now()->subDays(30), '4 hours'],
            '90d' => [now()->subDays(90), '12 hours'],
            '1y' => [now()->subYear(), '1 day'],
            default => [now()->subDay(), '10 minutes'],
        };
    }

    /**
     * Get color for currency (for charts)
     */
    protected function getCurrencyColor(string $code): string
    {
        return match($code) {
            'BTC' => '#F7931A',
            'ETH' => '#627EEA',
            'BNB' => '#F3BA2F',
            'MATIC' => '#8247E5',
            'USDT' => '#26A17B',
            'USDC' => '#2775CA',
            'ADA' => '#0033AD',
            'SOL' => '#00FFA3',
            'DOT' => '#E6007A',
            'LINK' => '#2A5ADA',
            default => '#' . substr(md5($code), 0, 6),
        };
    }
}
