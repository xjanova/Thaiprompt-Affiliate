<?php

namespace App\Services\TradingEngine;

use App\Models\TradingBot;
use App\Models\TradingExchange;
use App\Models\TradingAccount;
use App\Models\TradingSignal;
use App\Services\Exchange\BinanceConnector;
use App\Services\Exchange\BitkubConnector;
use App\Services\Exchange\KuCoinConnector;
use App\Services\Exchange\BybitConnector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ArbitrageService
{
    protected array $exchangeConnectors = [];
    protected float $minimumProfitPercentage = 0.5; // 0.5% minimum profit
    protected float $tradingFeeEstimate = 0.002; // 0.2% trading fee estimate

    public function __construct()
    {
        $this->initializeConnectors();
    }

    /**
     * Initialize exchange connectors
     */
    protected function initializeConnectors(): void
    {
        $this->exchangeConnectors = [
            'binance' => app(BinanceConnector::class),
            'bitkub' => app(BitkubConnector::class),
            'kucoin' => app(KuCoinConnector::class),
            'bybit' => app(BybitConnector::class),
        ];
    }

    /**
     * Find arbitrage opportunities across exchanges
     */
    public function findOpportunities(array $symbols, array $exchangeIds): array
    {
        $opportunities = [];

        foreach ($symbols as $symbol) {
            $prices = $this->fetchPricesFromExchanges($symbol, $exchangeIds);

            if (count($prices) < 2) {
                continue; // Need at least 2 exchanges
            }

            $opportunity = $this->analyzeArbitrageOpportunity($symbol, $prices);

            if ($opportunity && $opportunity['profit_percentage'] >= $this->minimumProfitPercentage) {
                $opportunities[] = $opportunity;
            }
        }

        // Sort by profit percentage
        usort($opportunities, function ($a, $b) {
            return $b['profit_percentage'] <=> $a['profit_percentage'];
        });

        return $opportunities;
    }

    /**
     * Fetch prices from multiple exchanges
     */
    protected function fetchPricesFromExchanges(string $symbol, array $exchangeIds): array
    {
        $prices = [];

        foreach ($exchangeIds as $exchangeId) {
            try {
                $exchange = TradingExchange::find($exchangeId);
                if (!$exchange || !$exchange->is_active) {
                    continue;
                }

                $cacheKey = "arbitrage_price_{$exchange->code}_{$symbol}";

                // Cache for 10 seconds to avoid rate limits
                $ticker = Cache::remember($cacheKey, 10, function () use ($exchange, $symbol) {
                    return $this->getExchangeTicker($exchange, $symbol);
                });

                if ($ticker) {
                    $prices[] = [
                        'exchange_id' => $exchange->id,
                        'exchange_name' => $exchange->name,
                        'exchange_code' => $exchange->code,
                        'symbol' => $symbol,
                        'bid' => $ticker['bid'],
                        'ask' => $ticker['ask'],
                        'last' => $ticker['last'],
                        'volume' => $ticker['volume'],
                        'timestamp' => $ticker['timestamp'],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch price from exchange", [
                    'exchange_id' => $exchangeId,
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $prices;
    }

    /**
     * Get ticker from exchange
     */
    protected function getExchangeTicker(TradingExchange $exchange, string $symbol): ?array
    {
        $connectorKey = strtolower($exchange->code);

        if (!isset($this->exchangeConnectors[$connectorKey])) {
            return null;
        }

        $connector = $this->exchangeConnectors[$connectorKey];

        try {
            return $connector->getTicker($symbol);
        } catch (\Exception $e) {
            Log::error("Exchange ticker fetch failed", [
                'exchange' => $exchange->code,
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Analyze arbitrage opportunity
     */
    protected function analyzeArbitrageOpportunity(string $symbol, array $prices): ?array
    {
        if (count($prices) < 2) {
            return null;
        }

        // Find lowest ask (buy price) and highest bid (sell price)
        $lowestAsk = null;
        $highestBid = null;

        foreach ($prices as $price) {
            if ($lowestAsk === null || $price['ask'] < $lowestAsk['ask']) {
                $lowestAsk = $price;
            }

            if ($highestBid === null || $price['bid'] > $highestBid['bid']) {
                $highestBid = $price;
            }
        }

        // Can't arbitrage on the same exchange
        if ($lowestAsk['exchange_id'] === $highestBid['exchange_id']) {
            return null;
        }

        // Calculate profit
        $buyPrice = $lowestAsk['ask'];
        $sellPrice = $highestBid['bid'];

        $grossProfit = $sellPrice - $buyPrice;
        $profitPercentage = ($grossProfit / $buyPrice) * 100;

        // Subtract estimated trading fees (0.2% per trade = 0.4% total)
        $netProfitPercentage = $profitPercentage - ($this->tradingFeeEstimate * 200);

        // Add withdrawal/transfer fee consideration
        $estimatedTransferFee = 0.1; // 0.1% transfer fee estimate
        $netProfitPercentage -= $estimatedTransferFee;

        if ($netProfitPercentage < $this->minimumProfitPercentage) {
            return null;
        }

        return [
            'symbol' => $symbol,
            'buy_exchange' => $lowestAsk['exchange_name'],
            'buy_exchange_id' => $lowestAsk['exchange_id'],
            'buy_exchange_code' => $lowestAsk['exchange_code'],
            'buy_price' => $buyPrice,
            'sell_exchange' => $highestBid['exchange_name'],
            'sell_exchange_id' => $highestBid['exchange_id'],
            'sell_exchange_code' => $highestBid['exchange_code'],
            'sell_price' => $sellPrice,
            'gross_profit' => $grossProfit,
            'gross_profit_percentage' => $profitPercentage,
            'profit_percentage' => $netProfitPercentage,
            'timestamp' => now()->timestamp,
            'all_prices' => $prices,
        ];
    }

    /**
     * Execute arbitrage trade
     */
    public function executeArbitrage(TradingBot $bot, array $opportunity, float $amount): array
    {
        $results = [
            'success' => false,
            'buy_order' => null,
            'sell_order' => null,
            'profit' => 0,
            'errors' => [],
        ];

        try {
            // Get trading accounts for both exchanges
            $buyAccount = $this->getTradingAccount($bot, $opportunity['buy_exchange_id']);
            $sellAccount = $this->getTradingAccount($bot, $opportunity['sell_exchange_id']);

            if (!$buyAccount || !$sellAccount) {
                throw new \Exception("Trading accounts not found for arbitrage");
            }

            // Calculate quantity to trade
            $quantity = $amount / $opportunity['buy_price'];

            // Step 1: Buy on cheaper exchange
            $buyOrder = $this->executeBuyOrder($buyAccount, $opportunity, $quantity);

            if (!$buyOrder) {
                throw new \Exception("Failed to execute buy order");
            }

            $results['buy_order'] = $buyOrder;

            // Create trading signal for buy
            $this->createArbitrageSignal($bot, 'buy', $opportunity, $buyOrder);

            // Step 2: Sell on expensive exchange
            // Note: In real-world, you'd need to wait for withdrawal/transfer
            $sellOrder = $this->executeSellOrder($sellAccount, $opportunity, $quantity);

            if (!$sellOrder) {
                // Try to reverse buy order if sell fails
                Log::error("Sell order failed, manual intervention required", [
                    'buy_order' => $buyOrder,
                ]);
                $results['errors'][] = "Sell order failed";
                return $results;
            }

            $results['sell_order'] = $sellOrder;

            // Create trading signal for sell
            $this->createArbitrageSignal($bot, 'sell', $opportunity, $sellOrder);

            // Calculate actual profit
            $buyTotal = $buyOrder['price'] * $buyOrder['quantity'] + ($buyOrder['fee'] ?? 0);
            $sellTotal = $sellOrder['price'] * $sellOrder['quantity'] - ($sellOrder['fee'] ?? 0);
            $actualProfit = $sellTotal - $buyTotal;

            $results['success'] = true;
            $results['profit'] = $actualProfit;

            Log::info("Arbitrage executed successfully", [
                'bot_id' => $bot->id,
                'symbol' => $opportunity['symbol'],
                'profit' => $actualProfit,
            ]);

        } catch (\Exception $e) {
            Log::error("Arbitrage execution failed", [
                'bot_id' => $bot->id,
                'opportunity' => $opportunity,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Execute buy order
     */
    protected function executeBuyOrder(TradingAccount $account, array $opportunity, float $quantity): ?array
    {
        try {
            $connector = $this->exchangeConnectors[$opportunity['buy_exchange_code']];
            $connector->setAccount($account);

            return $connector->createMarketOrder(
                $account,
                $opportunity['symbol'],
                'buy',
                $quantity
            );
        } catch (\Exception $e) {
            Log::error("Buy order failed", [
                'exchange' => $opportunity['buy_exchange_code'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Execute sell order
     */
    protected function executeSellOrder(TradingAccount $account, array $opportunity, float $quantity): ?array
    {
        try {
            $connector = $this->exchangeConnectors[$opportunity['sell_exchange_code']];
            $connector->setAccount($account);

            return $connector->createMarketOrder(
                $account,
                $opportunity['symbol'],
                'sell',
                $quantity
            );
        } catch (\Exception $e) {
            Log::error("Sell order failed", [
                'exchange' => $opportunity['sell_exchange_code'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get trading account for exchange
     */
    protected function getTradingAccount(TradingBot $bot, int $exchangeId): ?TradingAccount
    {
        return TradingAccount::where('user_id', $bot->user_id)
            ->where('exchange_id', $exchangeId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Create arbitrage signal
     */
    protected function createArbitrageSignal(TradingBot $bot, string $action, array $opportunity, array $order): void
    {
        TradingSignal::create([
            'bot_id' => $bot->id,
            'strategy_id' => $bot->strategy_id,
            'signal_type' => 'arbitrage',
            'action' => $action,
            'symbol' => $opportunity['symbol'],
            'price' => $order['price'],
            'confidence' => 95.0, // High confidence for arbitrage
            'indicators' => [
                'buy_exchange' => $opportunity['buy_exchange'],
                'sell_exchange' => $opportunity['sell_exchange'],
                'profit_percentage' => $opportunity['profit_percentage'],
            ],
            'reasoning' => "Arbitrage opportunity: {$opportunity['profit_percentage']}% profit",
            'status' => 'executed',
            'generated_at' => now(),
        ]);
    }

    /**
     * Set minimum profit percentage
     */
    public function setMinimumProfitPercentage(float $percentage): self
    {
        $this->minimumProfitPercentage = $percentage;
        return $this;
    }

    /**
     * Set trading fee estimate
     */
    public function setTradingFeeEstimate(float $fee): self
    {
        $this->tradingFeeEstimate = $fee;
        return $this;
    }

    /**
     * Get current arbitrage status for a bot
     */
    public function getArbitrageStatus(TradingBot $bot): array
    {
        $strategy = $bot->strategy;

        if (!$strategy || $strategy->strategy_type !== 'arbitrage') {
            return ['active' => false];
        }

        $symbols = $strategy->trading_pairs ?? ['BTC/USDT', 'ETH/USDT'];
        $exchangeIds = $strategy->exchange_ids ?? [];

        if (empty($exchangeIds)) {
            // Default to all active exchanges
            $exchangeIds = TradingExchange::where('is_active', true)->pluck('id')->toArray();
        }

        $opportunities = $this->findOpportunities($symbols, $exchangeIds);

        return [
            'active' => true,
            'opportunities_count' => count($opportunities),
            'opportunities' => $opportunities,
            'best_opportunity' => $opportunities[0] ?? null,
        ];
    }

    /**
     * Monitor arbitrage opportunities (for background jobs)
     */
    public function monitorOpportunities(): array
    {
        $activeBots = TradingBot::whereHas('strategy', function ($query) {
            $query->where('strategy_type', 'arbitrage');
        })
            ->where('status', 'running')
            ->get();

        $results = [];

        foreach ($activeBots as $bot) {
            $status = $this->getArbitrageStatus($bot);

            if ($status['best_opportunity'] ?? null) {
                $results[] = [
                    'bot_id' => $bot->id,
                    'bot_name' => $bot->name,
                    'best_opportunity' => $status['best_opportunity'],
                ];

                // Auto-execute if enabled
                if ($bot->advanced_settings['auto_execute_arbitrage'] ?? false) {
                    $amount = $bot->position_size ?? 100;
                    $this->executeArbitrage($bot, $status['best_opportunity'], $amount);
                }
            }
        }

        return $results;
    }
}
