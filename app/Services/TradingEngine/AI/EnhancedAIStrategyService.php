<?php

namespace App\Services\TradingEngine\AI;

use App\Models\TradingStrategy;
use App\Models\TradingMarketData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EnhancedAIStrategyService extends AIStrategyService
{
    /**
     * Advanced AI prediction with multiple factors
     */
    public function advancedPredict(array $marketData, TradingStrategy $strategy, array $options = []): ?array
    {
        try {
            // Market regime detection
            $marketRegime = $this->detectMarketRegime($marketData);

            // Multi-timeframe analysis
            $multiTimeframeSignal = $this->analyzeMultipleTimeframes($marketData, $strategy);

            // Advanced pattern recognition
            $patterns = $this->advancedPatternRecognition($marketData);

            // Risk-adjusted prediction
            $riskMetrics = $this->calculateRiskMetrics($marketData);

            // Combine all signals with ensemble method
            $prediction = $this->ensemblePrediction([
                'base_ai' => parent::predict($marketData, $strategy),
                'market_regime' => $marketRegime,
                'multi_timeframe' => $multiTimeframeSignal,
                'patterns' => $patterns,
                'risk_metrics' => $riskMetrics,
            ], $strategy);

            return array_merge($prediction, [
                'enhanced' => true,
                'market_regime' => $marketRegime,
                'risk_metrics' => $riskMetrics,
                'patterns' => $patterns,
            ]);

        } catch (\Exception $e) {
            Log::error("Enhanced AI prediction failed", [
                'strategy_id' => $strategy->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to basic prediction
            return parent::predict($marketData, $strategy);
        }
    }

    /**
     * Detect market regime (trending, ranging, volatile)
     */
    protected function detectMarketRegime(array $marketData): array
    {
        $closes = array_column($marketData, 'close');
        $highs = array_column($marketData, 'high');
        $lows = array_column($marketData, 'low');

        // Calculate ADX (Average Directional Index) for trend strength
        $adx = $this->calculateADX($highs, $lows, $closes);

        // Calculate ATR for volatility
        $atr = $this->calculateATR($highs, $lows, $closes);
        $atrPercentage = ($atr / end($closes)) * 100;

        // Determine regime
        $regime = 'ranging';
        if ($adx > 25) {
            $regime = 'trending';
        }
        if ($atrPercentage > 3) {
            $regime = 'volatile';
        }

        return [
            'regime' => $regime,
            'adx' => round($adx, 2),
            'atr' => round($atr, 2),
            'atr_percentage' => round($atrPercentage, 2),
            'confidence' => $this->calculateRegimeConfidence($adx, $atrPercentage),
        ];
    }

    /**
     * Analyze multiple timeframes for confluence
     */
    protected function analyzeMultipleTimeframes(array $marketData, TradingStrategy $strategy): array
    {
        // Simulate different timeframe analyses
        $shortTerm = $this->analyzeTimeframe(array_slice($marketData, -20), 'short');
        $mediumTerm = $this->analyzeTimeframe(array_slice($marketData, -50), 'medium');
        $longTerm = $this->analyzeTimeframe($marketData, 'long');

        // Calculate confluence
        $signals = [$shortTerm['signal'], $mediumTerm['signal'], $longTerm['signal']];
        $buyCount = count(array_filter($signals, fn($s) => $s === 'buy'));
        $sellCount = count(array_filter($signals, fn($s) => $s === 'sell'));

        $confluence = 'none';
        $confidence = 33;

        if ($buyCount >= 2) {
            $confluence = 'buy';
            $confidence = ($buyCount / 3) * 100;
        } elseif ($sellCount >= 2) {
            $confluence = 'sell';
            $confidence = ($sellCount / 3) * 100;
        }

        return [
            'confluence' => $confluence,
            'confidence' => round($confidence, 2),
            'timeframes' => [
                'short' => $shortTerm,
                'medium' => $mediumTerm,
                'long' => $longTerm,
            ],
        ];
    }

    /**
     * Analyze specific timeframe
     */
    protected function analyzeTimeframe(array $data, string $timeframe): array
    {
        $trend = $this->calculateTrendStrength($data);
        $momentum = $this->calculateMomentum($data);

        $score = ($trend + $momentum) / 2;

        return [
            'timeframe' => $timeframe,
            'signal' => $score > 55 ? 'buy' : ($score < 45 ? 'sell' : 'hold'),
            'strength' => round($score, 2),
            'trend' => round($trend, 2),
            'momentum' => round($momentum, 2),
        ];
    }

    /**
     * Advanced pattern recognition
     */
    protected function advancedPatternRecognition(array $marketData): array
    {
        $patterns = [];

        // Detect various patterns
        $patterns['double_top'] = $this->detectDoubleTop($marketData);
        $patterns['double_bottom'] = $this->detectDoubleBottom($marketData);
        $patterns['head_shoulders'] = $this->detectHeadAndShoulders($marketData);
        $patterns['triangle'] = $this->detectTriangle($marketData);
        $patterns['flag'] = $this->detectFlag($marketData);
        $patterns['wedge'] = $this->detectWedge($marketData);

        // Support and resistance levels
        $patterns['support_resistance'] = $this->findSupportResistance($marketData);

        // Fibonacci levels
        $patterns['fibonacci'] = $this->calculateFibonacciLevels($marketData);

        // Overall pattern score
        $bullishCount = 0;
        $bearishCount = 0;

        foreach ($patterns as $key => $pattern) {
            if (is_array($pattern) && isset($pattern['signal'])) {
                if ($pattern['signal'] === 'bullish') {
                    $bullishCount++;
                } elseif ($pattern['signal'] === 'bearish') {
                    $bearishCount++;
                }
            }
        }

        return [
            'detected_patterns' => $patterns,
            'bullish_count' => $bullishCount,
            'bearish_count' => $bearishCount,
            'overall_signal' => $bullishCount > $bearishCount ? 'bullish' : ($bearishCount > $bullishCount ? 'bearish' : 'neutral'),
        ];
    }

    /**
     * Calculate comprehensive risk metrics
     */
    protected function calculateRiskMetrics(array $marketData): array
    {
        $closes = array_column($marketData, 'close');
        $returns = $this->calculateReturns($closes);

        // Sharpe Ratio
        $sharpeRatio = $this->calculateSharpeRatio($returns);

        // Sortino Ratio
        $sortinoRatio = $this->calculateSortinoRatio($returns);

        // Max Drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($closes);

        // Value at Risk (VaR)
        $var95 = $this->calculateVaR($returns, 0.95);

        // Risk Score (0-100, higher = riskier)
        $riskScore = $this->calculateOverallRiskScore($maxDrawdown, $sharpeRatio, $this->calculateVolatility($marketData));

        return [
            'sharpe_ratio' => round($sharpeRatio, 2),
            'sortino_ratio' => round($sortinoRatio, 2),
            'max_drawdown' => round($maxDrawdown, 2),
            'var_95' => round($var95, 2),
            'risk_score' => round($riskScore, 2),
            'risk_level' => $this->getRiskLevel($riskScore),
        ];
    }

    /**
     * Ensemble prediction combining all signals
     */
    protected function ensemblePrediction(array $signals, TradingStrategy $strategy): array
    {
        $predictions = [];
        $confidences = [];

        // Collect all predictions
        if ($signals['base_ai'] ?? null) {
            $predictions[] = $signals['base_ai']['prediction'];
            $confidences[] = $signals['base_ai']['confidence'];
        }

        if (($signals['multi_timeframe']['confluence'] ?? 'none') !== 'none') {
            $predictions[] = $signals['multi_timeframe']['confluence'];
            $confidences[] = $signals['multi_timeframe']['confidence'];
        }

        if (($signals['patterns']['overall_signal'] ?? 'neutral') !== 'neutral') {
            $signal = $signals['patterns']['overall_signal'] === 'bullish' ? 'buy' : 'sell';
            $predictions[] = $signal;
            $confidences[] = 70;
        }

        // Adjust for market regime
        $regime = $signals['market_regime']['regime'];
        $regimeAdjustment = match ($regime) {
            'trending' => 1.2,
            'ranging' => 0.8,
            'volatile' => 0.6,
            default => 1.0,
        };

        // Adjust for risk
        $riskScore = $signals['risk_metrics']['risk_score'];
        $riskAdjustment = 1 - ($riskScore / 200); // Reduce confidence in high-risk scenarios

        // Count predictions
        $buyCount = count(array_filter($predictions, fn($p) => $p === 'buy'));
        $sellCount = count(array_filter($predictions, fn($p) => $p === 'sell'));
        $holdCount = count(array_filter($predictions, fn($p) => $p === 'hold'));

        // Determine final prediction
        $totalPredictions = count($predictions);
        $finalPrediction = 'hold';
        $rawConfidence = 50;

        if ($buyCount > max($sellCount, $holdCount)) {
            $finalPrediction = 'buy';
            $rawConfidence = ($buyCount / $totalPredictions) * 100;
        } elseif ($sellCount > max($buyCount, $holdCount)) {
            $finalPrediction = 'sell';
            $rawConfidence = ($sellCount / $totalPredictions) * 100;
        }

        // Calculate average confidence
        $avgConfidence = !empty($confidences) ? array_sum($confidences) / count($confidences) : 50;

        // Apply adjustments
        $adjustedConfidence = min(98, max(50, $avgConfidence * $regimeAdjustment * $riskAdjustment));

        // Apply strategy's confidence threshold
        $threshold = $strategy->ai_confidence_threshold ?? 70;
        if ($adjustedConfidence < $threshold) {
            $finalPrediction = 'hold';
        }

        return [
            'model' => 'ensemble_enhanced',
            'prediction' => $finalPrediction,
            'confidence' => round($adjustedConfidence, 2),
            'raw_confidence' => round($rawConfidence, 2),
            'vote_breakdown' => [
                'buy' => $buyCount,
                'sell' => $sellCount,
                'hold' => $holdCount,
                'total' => $totalPredictions,
            ],
            'adjustments' => [
                'regime' => round($regimeAdjustment, 2),
                'risk' => round($riskAdjustment, 2),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate ADX (Average Directional Index)
     */
    protected function calculateADX(array $highs, array $lows, array $closes, int $period = 14): float
    {
        // Simplified ADX calculation
        $count = count($closes);
        if ($count < $period + 1) {
            return 20; // Default neutral value
        }

        $movements = [];
        for ($i = 1; $i < $count; $i++) {
            $upMove = $highs[$i] - $highs[$i - 1];
            $downMove = $lows[$i - 1] - $lows[$i];

            if ($upMove > $downMove && $upMove > 0) {
                $movements[] = $upMove;
            } elseif ($downMove > $upMove && $downMove > 0) {
                $movements[] = -$downMove;
            } else {
                $movements[] = 0;
            }
        }

        $avgMovement = array_sum(array_map('abs', array_slice($movements, -$period))) / $period;
        $adx = min(100, $avgMovement * 50); // Scale to 0-100

        return $adx;
    }

    /**
     * Calculate ATR (Average True Range)
     */
    protected function calculateATR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $count = count($closes);
        if ($count < 2) {
            return 0;
        }

        $trueRanges = [];
        for ($i = 1; $i < $count; $i++) {
            $high = $highs[$i];
            $low = $lows[$i];
            $prevClose = $closes[$i - 1];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );

            $trueRanges[] = $tr;
        }

        $recentTR = array_slice($trueRanges, -min($period, count($trueRanges)));
        return array_sum($recentTR) / count($recentTR);
    }

    /**
     * Pattern detection methods
     */
    protected function detectDoubleTop(array $marketData): array
    {
        $highs = array_column($marketData, 'high');
        $count = count($highs);

        if ($count < 20) {
            return ['detected' => false];
        }

        // Simplified detection
        $recentHighs = array_slice($highs, -20);
        $max1 = max(array_slice($recentHighs, 0, 10));
        $max2 = max(array_slice($recentHighs, 10, 10));

        $similar = abs($max1 - $max2) / $max1 < 0.02; // Within 2%

        return [
            'detected' => $similar,
            'signal' => $similar ? 'bearish' : null,
            'confidence' => $similar ? 70 : 0,
        ];
    }

    protected function detectDoubleBottom(array $marketData): array
    {
        $lows = array_column($marketData, 'low');
        $count = count($lows);

        if ($count < 20) {
            return ['detected' => false];
        }

        $recentLows = array_slice($lows, -20);
        $min1 = min(array_slice($recentLows, 0, 10));
        $min2 = min(array_slice($recentLows, 10, 10));

        $similar = abs($min1 - $min2) / $min1 < 0.02;

        return [
            'detected' => $similar,
            'signal' => $similar ? 'bullish' : null,
            'confidence' => $similar ? 70 : 0,
        ];
    }

    protected function detectHeadAndShoulders(array $marketData): array
    {
        // Simplified H&S detection
        return ['detected' => false, 'signal' => null];
    }

    protected function detectTriangle(array $marketData): array
    {
        return ['detected' => false, 'signal' => null];
    }

    protected function detectFlag(array $marketData): array
    {
        return ['detected' => false, 'signal' => null];
    }

    protected function detectWedge(array $marketData): array
    {
        return ['detected' => false, 'signal' => null];
    }

    /**
     * Find support and resistance levels
     */
    protected function findSupportResistance(array $marketData): array
    {
        $closes = array_column($marketData, 'close');
        $highs = array_column($marketData, 'high');
        $lows = array_column($marketData, 'low');

        $currentPrice = end($closes);

        return [
            'resistance' => round(max($highs) * 0.98, 2),
            'support' => round(min($lows) * 1.02, 2),
            'current_price' => $currentPrice,
            'distance_to_resistance' => round(((max($highs) - $currentPrice) / $currentPrice) * 100, 2),
            'distance_to_support' => round((($currentPrice - min($lows)) / $currentPrice) * 100, 2),
        ];
    }

    /**
     * Calculate Fibonacci retracement levels
     */
    protected function calculateFibonacciLevels(array $marketData): array
    {
        $closes = array_column($marketData, 'close');
        $high = max($closes);
        $low = min($closes);
        $diff = $high - $low;

        return [
            'level_0' => round($high, 2),
            'level_236' => round($high - ($diff * 0.236), 2),
            'level_382' => round($high - ($diff * 0.382), 2),
            'level_50' => round($high - ($diff * 0.5), 2),
            'level_618' => round($high - ($diff * 0.618), 2),
            'level_100' => round($low, 2),
        ];
    }

    /**
     * Risk calculation methods
     */
    protected function calculateReturns(array $closes): array
    {
        $returns = [];
        for ($i = 1; $i < count($closes); $i++) {
            $returns[] = ($closes[$i] - $closes[$i - 1]) / $closes[$i - 1];
        }
        return $returns;
    }

    protected function calculateSharpeRatio(array $returns, float $riskFreeRate = 0.02): float
    {
        if (empty($returns)) {
            return 0;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);

        if ($stdDev == 0) {
            return 0;
        }

        // Annualized Sharpe Ratio
        return (($avgReturn - $riskFreeRate / 365) / $stdDev) * sqrt(365);
    }

    protected function calculateSortinoRatio(array $returns, float $targetReturn = 0): float
    {
        if (empty($returns)) {
            return 0;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $downside = array_filter($returns, fn($r) => $r < $targetReturn);

        if (empty($downside)) {
            return 999; // All positive returns
        }

        $downsideDeviation = sqrt(array_sum(array_map(fn($r) => pow($r - $targetReturn, 2), $downside)) / count($downside));

        if ($downsideDeviation == 0) {
            return 0;
        }

        return (($avgReturn - $targetReturn) / $downsideDeviation) * sqrt(365);
    }

    protected function calculateMaxDrawdown(array $closes): float
    {
        $maxDrawdown = 0;
        $peak = $closes[0];

        foreach ($closes as $close) {
            if ($close > $peak) {
                $peak = $close;
            }

            $drawdown = (($peak - $close) / $peak) * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return $maxDrawdown;
    }

    protected function calculateVaR(array $returns, float $confidence = 0.95): float
    {
        if (empty($returns)) {
            return 0;
        }

        sort($returns);
        $index = (int) ((1 - $confidence) * count($returns));
        return abs($returns[$index] ?? 0) * 100;
    }

    protected function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    protected function calculateOverallRiskScore(float $maxDrawdown, float $sharpeRatio, float $volatility): float
    {
        // Combine metrics into risk score (0-100, higher = riskier)
        $ddScore = min(100, $maxDrawdown * 2); // Max drawdown component
        $sharpeScore = max(0, 50 - ($sharpeRatio * 10)); // Sharpe component (inverse)
        $volScore = min(100, $volatility); // Volatility component

        return ($ddScore * 0.4) + ($sharpeScore * 0.3) + ($volScore * 0.3);
    }

    protected function getRiskLevel(float $riskScore): string
    {
        return match (true) {
            $riskScore < 30 => 'low',
            $riskScore < 50 => 'moderate',
            $riskScore < 70 => 'high',
            default => 'very_high',
        };
    }

    protected function calculateRegimeConfidence(float $adx, float $atrPercentage): float
    {
        // Higher ADX and moderate ATR = high confidence
        $adxConfidence = min(100, $adx * 2);
        $atrConfidence = $atrPercentage > 1 && $atrPercentage < 5 ? 80 : 50;

        return ($adxConfidence + $atrConfidence) / 2;
    }
}
