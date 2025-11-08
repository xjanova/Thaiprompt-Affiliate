<?php

namespace App\Services\TradingEngine;

use App\Models\TradingBot;
use App\Models\TradingSignal;
use Illuminate\Support\Facades\Log;

class SignalGeneratorService
{
    /**
     * Generate trading signal based on indicators and AI prediction
     */
    public function generate(
        TradingBot $bot,
        array $marketData,
        array $indicators,
        ?array $aiPrediction = null
    ): ?TradingSignal {
        $strategy = $bot->strategy;
        $currentPrice = $marketData[count($marketData) - 1]['close'];

        // Evaluate entry conditions
        $signalType = $this->evaluateConditions(
            $strategy->entry_conditions,
            $indicators,
            $aiPrediction
        );

        if ($signalType === 'hold') {
            return null;
        }

        // Calculate confidence score
        $confidenceScore = $this->calculateConfidence($indicators, $aiPrediction, $strategy);

        // Calculate suggested levels
        $suggestedLevels = $this->calculateSuggestedLevels(
            $currentPrice,
            $signalType,
            $strategy
        );

        // Create signal
        $signal = TradingSignal::create([
            'bot_id' => $bot->id,
            'strategy_id' => $strategy->id,
            'trading_pair' => $bot->trading_pair,
            'signal_type' => $signalType,
            'timeframe' => $bot->timeframe,
            'price' => $currentPrice,
            'suggested_entry' => $suggestedLevels['entry'],
            'suggested_stop_loss' => $suggestedLevels['stop_loss'],
            'suggested_take_profit' => $suggestedLevels['take_profit'],
            'suggested_position_size' => $this->calculatePositionSize($bot),
            'confidence_score' => $confidenceScore,
            'signal_source' => $aiPrediction ? 'ai' : 'indicator',
            'indicators_data' => $indicators,
            'ai_prediction' => $aiPrediction,
            'market_trend' => $this->determineTrend($indicators),
            'volatility' => $indicators['atr'] ?? null,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ]);

        Log::info("Trading signal generated", [
            'bot_id' => $bot->id,
            'signal_type' => $signalType,
            'confidence' => $confidenceScore,
            'price' => $currentPrice,
        ]);

        return $signal;
    }

    /**
     * Evaluate trading conditions
     */
    protected function evaluateConditions(
        ?array $conditions,
        array $indicators,
        ?array $aiPrediction
    ): string {
        if (!$conditions) {
            return 'hold';
        }

        $buyScore = 0;
        $sellScore = 0;

        // Evaluate technical indicators
        foreach ($conditions as $condition) {
            $indicator = $condition['indicator'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['value'] ?? null;

            if (!$indicator || !$operator || $value === null) {
                continue;
            }

            $indicatorValue = $indicators[$indicator] ?? null;
            if ($indicatorValue === null) {
                continue;
            }

            if ($this->evaluateCondition($indicatorValue, $operator, $value)) {
                if ($condition['action'] === 'buy') {
                    $buyScore += $condition['weight'] ?? 1;
                } elseif ($condition['action'] === 'sell') {
                    $sellScore += $condition['weight'] ?? 1;
                }
            }
        }

        // Add AI prediction weight
        if ($aiPrediction) {
            if ($aiPrediction['prediction'] === 'buy') {
                $buyScore += $aiPrediction['confidence'] / 10;
            } elseif ($aiPrediction['prediction'] === 'sell') {
                $sellScore += $aiPrediction['confidence'] / 10;
            }
        }

        // Determine signal
        if ($buyScore > $sellScore && $buyScore >= 3) {
            return 'buy';
        } elseif ($sellScore > $buyScore && $sellScore >= 3) {
            return 'sell';
        }

        return 'hold';
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition($indicatorValue, string $operator, $value): bool
    {
        return match ($operator) {
            '>' => $indicatorValue > $value,
            '<' => $indicatorValue < $value,
            '>=' => $indicatorValue >= $value,
            '<=' => $indicatorValue <= $value,
            '==' => $indicatorValue == $value,
            '!=' => $indicatorValue != $value,
            'crosses_above' => $this->crossesAbove($indicatorValue, $value),
            'crosses_below' => $this->crossesBelow($indicatorValue, $value),
            default => false,
        };
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(array $indicators, ?array $aiPrediction, $strategy): float
    {
        $confidence = 50; // Base confidence

        // Add confidence from indicators alignment
        if (isset($indicators['rsi'])) {
            $rsi = $indicators['rsi'];
            if ($rsi < 30 || $rsi > 70) {
                $confidence += 15; // Strong RSI signal
            }
        }

        if (isset($indicators['macd_histogram'])) {
            if (abs($indicators['macd_histogram']) > 0.5) {
                $confidence += 10; // Strong MACD signal
            }
        }

        // Add AI confidence
        if ($aiPrediction && $strategy->use_ai) {
            $confidence = ($confidence + $aiPrediction['confidence']) / 2;
        }

        // Add trend alignment
        if (isset($indicators['trend'])) {
            $confidence += 10;
        }

        return min(100, $confidence);
    }

    /**
     * Calculate suggested trading levels
     */
    protected function calculateSuggestedLevels(
        float $currentPrice,
        string $signalType,
        $strategy
    ): array {
        $stopLossPercentage = $strategy->stop_loss_percentage ?? 2;
        $takeProfitPercentage = $strategy->take_profit_percentage ?? 5;

        if ($signalType === 'buy') {
            $entry = $currentPrice;
            $stopLoss = $currentPrice * (1 - $stopLossPercentage / 100);
            $takeProfit = $currentPrice * (1 + $takeProfitPercentage / 100);
        } else {
            $entry = $currentPrice;
            $stopLoss = $currentPrice * (1 + $stopLossPercentage / 100);
            $takeProfit = $currentPrice * (1 - $takeProfitPercentage / 100);
        }

        return [
            'entry' => round($entry, 8),
            'stop_loss' => round($stopLoss, 8),
            'take_profit' => round($takeProfit, 8),
        ];
    }

    /**
     * Calculate position size
     */
    protected function calculatePositionSize(TradingBot $bot): float
    {
        $positionPercentage = $bot->position_size_percentage / 100;
        $positionValue = $bot->available_capital * $positionPercentage;

        // Get current price
        $currentPrice = 0; // This should be passed or fetched

        if ($currentPrice > 0) {
            return $positionValue / $currentPrice;
        }

        return 0;
    }

    /**
     * Determine market trend
     */
    protected function determineTrend(array $indicators): string
    {
        // Use EMA crossover or other trend indicators
        if (isset($indicators['ema_20'], $indicators['ema_50'])) {
            if ($indicators['ema_20'] > $indicators['ema_50']) {
                return 'bullish';
            } elseif ($indicators['ema_20'] < $indicators['ema_50']) {
                return 'bearish';
            }
        }

        return 'neutral';
    }

    /**
     * Check if value crosses above threshold
     */
    protected function crossesAbove($current, $threshold): bool
    {
        // This would need historical data to properly implement
        return $current > $threshold;
    }

    /**
     * Check if value crosses below threshold
     */
    protected function crossesBelow($current, $threshold): bool
    {
        // This would need historical data to properly implement
        return $current < $threshold;
    }
}
