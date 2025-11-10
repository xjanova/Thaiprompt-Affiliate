<?php

namespace App\Services\TradingEngine;

use App\Models\TradingBot;
use App\Models\TradingSignal;
use App\Models\TradingTrade;
use App\Services\TradingEngine\Indicators\TechnicalIndicatorService;
use App\Services\TradingEngine\AI\AIStrategyService;
use Illuminate\Support\Facades\Log;

class TradingEngineService
{
    protected TechnicalIndicatorService $indicatorService;
    protected AIStrategyService $aiService;
    protected SignalGeneratorService $signalGenerator;
    protected TradeExecutionService $tradeExecutor;

    public function __construct(
        TechnicalIndicatorService $indicatorService,
        AIStrategyService $aiService,
        SignalGeneratorService $signalGenerator,
        TradeExecutionService $tradeExecutor
    ) {
        $this->indicatorService = $indicatorService;
        $this->aiService = $aiService;
        $this->signalGenerator = $signalGenerator;
        $this->tradeExecutor = $tradeExecutor;
    }

    /**
     * Start trading bot
     */
    public function startBot(TradingBot $bot): bool
    {
        try {
            // Validate bot can start
            if (!$this->canStartBot($bot)) {
                throw new \Exception('Bot cannot be started. Check account and subscription status.');
            }

            $bot->start();

            Log::info("Trading bot started", [
                'bot_id' => $bot->id,
                'trading_pair' => $bot->trading_pair,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to start bot", [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            $bot->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Stop trading bot
     */
    public function stopBot(TradingBot $bot): bool
    {
        try {
            // Close any open positions if configured
            if ($bot->advanced_settings['close_on_stop'] ?? false) {
                $this->closeAllPositions($bot);
            }

            $bot->stop();

            Log::info("Trading bot stopped", ['bot_id' => $bot->id]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to stop bot", [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process trading iteration for a bot
     */
    public function processTradingCycle(TradingBot $bot): void
    {
        try {
            if (!$bot->isRunning()) {
                return;
            }

            // 1. Fetch latest market data
            $marketData = $this->fetchMarketData($bot);

            // 2. Calculate technical indicators
            $indicators = $this->indicatorService->calculate($marketData, $bot->strategy->indicators);

            // 3. Generate AI predictions if enabled
            $aiPrediction = null;
            if ($bot->strategy->use_ai) {
                $aiPrediction = $this->aiService->predict($marketData, $bot->strategy);
            }

            // 4. Generate trading signal
            $signal = $this->signalGenerator->generate($bot, $marketData, $indicators, $aiPrediction);

            // 5. Execute trade if signal is strong enough
            if ($signal && $this->shouldExecuteTrade($signal, $bot)) {
                $this->tradeExecutor->execute($bot, $signal);
            }

            // 6. Manage open positions
            $this->manageOpenPositions($bot);

            // 7. Update bot statistics
            $bot->updatePerformance();

        } catch (\Exception $e) {
            Log::error("Error in trading cycle", [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $bot->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if bot can be started
     */
    protected function canStartBot(TradingBot $bot): bool
    {
        // Check subscription
        if (!$bot->subscription->isActive()) {
            return false;
        }

        // Check account
        if (!$bot->account->isActive() && !$bot->dry_run) {
            return false;
        }

        // Check if account can trade
        if (!$bot->account->canTrade() && !$bot->dry_run) {
            return false;
        }

        return true;
    }

    /**
     * Fetch market data for the bot's trading pair
     */
    protected function fetchMarketData(TradingBot $bot): array
    {
        $exchange = $bot->account->exchange;
        $connector = app("App\\Services\\Exchange\\" . $exchange->slug . "Connector");

        return $connector->getOHLCV(
            $bot->trading_pair,
            $bot->timeframe,
            100 // Get last 100 candles
        );
    }

    /**
     * Determine if trade should be executed based on signal
     */
    protected function shouldExecuteTrade(TradingSignal $signal, TradingBot $bot): bool
    {
        // Check signal confidence
        $minConfidence = $bot->strategy->ai_confidence_threshold ?? 70;
        if ($signal->confidence_score < $minConfidence) {
            return false;
        }

        // Check if bot has available capital
        if ($bot->available_capital <= 0) {
            return false;
        }

        // Check daily loss limit
        $dailyPnL = $this->getDailyPnL($bot);
        $maxDailyLoss = ($bot->allocated_capital * $bot->strategy->max_daily_loss) / 100;
        if ($dailyPnL < -$maxDailyLoss) {
            Log::info("Daily loss limit reached", [
                'bot_id' => $bot->id,
                'daily_pnl' => $dailyPnL,
                'max_daily_loss' => $maxDailyLoss,
            ]);
            return false;
        }

        // Check max active trades
        $activeTrades = $bot->trades()->open()->count();
        $maxActiveTrades = $bot->subscription->package->max_active_trades;
        if ($activeTrades >= $maxActiveTrades) {
            return false;
        }

        return true;
    }

    /**
     * Manage open positions (stop loss, take profit, trailing stop)
     */
    protected function manageOpenPositions(TradingBot $bot): void
    {
        $openTrades = $bot->trades()->open()->get();

        foreach ($openTrades as $trade) {
            $currentPrice = $this->getCurrentPrice($bot);

            // Check stop loss
            if ($trade->stop_loss && $this->hitStopLoss($trade, $currentPrice)) {
                $this->tradeExecutor->closePosition($trade, $currentPrice, 'stop_loss');
                continue;
            }

            // Check take profit
            if ($trade->take_profit && $this->hitTakeProfit($trade, $currentPrice)) {
                $this->tradeExecutor->closePosition($trade, $currentPrice, 'take_profit');
                continue;
            }

            // Update trailing stop if configured
            if ($bot->strategy->trailing_stop_percentage) {
                $this->updateTrailingStop($trade, $currentPrice, $bot->strategy->trailing_stop_percentage);
            }
        }
    }

    /**
     * Close all open positions for a bot
     */
    protected function closeAllPositions(TradingBot $bot): void
    {
        $openTrades = $bot->trades()->open()->get();
        $currentPrice = $this->getCurrentPrice($bot);

        foreach ($openTrades as $trade) {
            $this->tradeExecutor->closePosition($trade, $currentPrice, 'manual');
        }
    }

    /**
     * Get current price for trading pair
     */
    protected function getCurrentPrice(TradingBot $bot): float
    {
        $exchange = $bot->account->exchange;
        $connector = app("App\\Services\\Exchange\\" . $exchange->slug . "Connector");

        $ticker = $connector->getTicker($bot->trading_pair);
        return $ticker['last'] ?? 0;
    }

    /**
     * Check if stop loss is hit
     */
    protected function hitStopLoss(TradingTrade $trade, float $currentPrice): bool
    {
        if ($trade->side === 'buy') {
            return $currentPrice <= $trade->stop_loss;
        } else {
            return $currentPrice >= $trade->stop_loss;
        }
    }

    /**
     * Check if take profit is hit
     */
    protected function hitTakeProfit(TradingTrade $trade, float $currentPrice): bool
    {
        if ($trade->side === 'buy') {
            return $currentPrice >= $trade->take_profit;
        } else {
            return $currentPrice <= $trade->take_profit;
        }
    }

    /**
     * Update trailing stop
     */
    protected function updateTrailingStop(TradingTrade $trade, float $currentPrice, float $trailingPercentage): void
    {
        $trailingDistance = ($currentPrice * $trailingPercentage) / 100;

        if ($trade->side === 'buy') {
            $newStopLoss = $currentPrice - $trailingDistance;
            if ($newStopLoss > $trade->stop_loss) {
                $trade->update(['stop_loss' => $newStopLoss]);
            }
        } else {
            $newStopLoss = $currentPrice + $trailingDistance;
            if ($newStopLoss < $trade->stop_loss) {
                $trade->update(['stop_loss' => $newStopLoss]);
            }
        }
    }

    /**
     * Get daily P&L for bot
     */
    protected function getDailyPnL(TradingBot $bot): float
    {
        return $bot->trades()
            ->whereBetween('closed_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('net_profit');
    }
}
