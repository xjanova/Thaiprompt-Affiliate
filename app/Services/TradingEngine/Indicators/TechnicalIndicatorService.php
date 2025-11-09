<?php

namespace App\Services\TradingEngine\Indicators;

class TechnicalIndicatorService
{
    /**
     * Calculate all requested indicators
     */
    public function calculate(array $marketData, ?array $indicatorConfig): array
    {
        if (empty($marketData) || empty($indicatorConfig)) {
            return [];
        }

        $indicators = [];

        foreach ($indicatorConfig as $indicator) {
            $name = $indicator['name'] ?? null;
            $params = $indicator['params'] ?? [];

            if (!$name) {
                continue;
            }

            $indicators[$name] = match ($name) {
                'rsi' => $this->calculateRSI($marketData, $params['period'] ?? 14),
                'macd' => $this->calculateMACD($marketData, $params),
                'ema' => $this->calculateEMA($marketData, $params['period'] ?? 20),
                'sma' => $this->calculateSMA($marketData, $params['period'] ?? 20),
                'bollinger_bands' => $this->calculateBollingerBands($marketData, $params),
                'atr' => $this->calculateATR($marketData, $params['period'] ?? 14),
                'stochastic' => $this->calculateStochastic($marketData, $params),
                'adx' => $this->calculateADX($marketData, $params['period'] ?? 14),
                default => null,
            };
        }

        return array_filter($indicators);
    }

    /**
     * Calculate RSI (Relative Strength Index)
     */
    public function calculateRSI(array $data, int $period = 14): ?float
    {
        if (count($data) < $period + 1) {
            return null;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($data); $i++) {
            $change = $data[$i]['close'] - $data[$i - 1]['close'];
            $gains[] = max($change, 0);
            $losses[] = abs(min($change, 0));
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    /**
     * Calculate MACD (Moving Average Convergence Divergence)
     */
    public function calculateMACD(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;

        $fastEMA = $this->calculateEMA($data, $fastPeriod);
        $slowEMA = $this->calculateEMA($data, $slowPeriod);

        $macdLine = $fastEMA - $slowEMA;

        // For simplicity, signal line would need additional calculation
        $signalLine = $macdLine * 0.9; // Simplified
        $histogram = $macdLine - $signalLine;

        return [
            'macd' => round($macdLine, 4),
            'signal' => round($signalLine, 4),
            'histogram' => round($histogram, 4),
        ];
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(array $data, int $period = 20): ?float
    {
        if (count($data) < $period) {
            return null;
        }

        $closes = array_column($data, 'close');
        $multiplier = 2 / ($period + 1);

        // Start with SMA
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        // Calculate EMA
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] - $ema) * $multiplier + $ema;
        }

        return round($ema, 8);
    }

    /**
     * Calculate SMA (Simple Moving Average)
     */
    public function calculateSMA(array $data, int $period = 20): ?float
    {
        if (count($data) < $period) {
            return null;
        }

        $closes = array_column($data, 'close');
        $recentCloses = array_slice($closes, -$period);

        return round(array_sum($recentCloses) / $period, 8);
    }

    /**
     * Calculate Bollinger Bands
     */
    public function calculateBollingerBands(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;

        if (count($data) < $period) {
            return [];
        }

        $closes = array_column($data, 'close');
        $recentCloses = array_slice($closes, -$period);

        $sma = array_sum($recentCloses) / $period;
        $variance = 0;

        foreach ($recentCloses as $close) {
            $variance += pow($close - $sma, 2);
        }

        $standardDeviation = sqrt($variance / $period);

        return [
            'upper' => round($sma + ($stdDev * $standardDeviation), 8),
            'middle' => round($sma, 8),
            'lower' => round($sma - ($stdDev * $standardDeviation), 8),
        ];
    }

    /**
     * Calculate ATR (Average True Range)
     */
    public function calculateATR(array $data, int $period = 14): ?float
    {
        if (count($data) < $period + 1) {
            return null;
        }

        $trueRanges = [];

        for ($i = 1; $i < count($data); $i++) {
            $high = $data[$i]['high'];
            $low = $data[$i]['low'];
            $prevClose = $data[$i - 1]['close'];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );

            $trueRanges[] = $tr;
        }

        $recentTR = array_slice($trueRanges, -$period);
        $atr = array_sum($recentTR) / $period;

        return round($atr, 8);
    }

    /**
     * Calculate Stochastic Oscillator
     */
    public function calculateStochastic(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;

        if (count($data) < $period) {
            return [];
        }

        $recentData = array_slice($data, -$period);

        $highest = max(array_column($recentData, 'high'));
        $lowest = min(array_column($recentData, 'low'));
        $currentClose = $data[count($data) - 1]['close'];

        if ($highest - $lowest == 0) {
            return ['k' => 50, 'd' => 50];
        }

        $k = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
        $d = $k; // Simplified - would normally be smoothed

        return [
            'k' => round($k, 2),
            'd' => round($d, 2),
        ];
    }

    /**
     * Calculate ADX (Average Directional Index)
     */
    public function calculateADX(array $data, int $period = 14): ?float
    {
        if (count($data) < $period + 1) {
            return null;
        }

        // Simplified ADX calculation
        $atr = $this->calculateATR($data, $period);

        if (!$atr || $atr == 0) {
            return null;
        }

        $plusDM = [];
        $minusDM = [];

        for ($i = 1; $i < count($data); $i++) {
            $highDiff = $data[$i]['high'] - $data[$i - 1]['high'];
            $lowDiff = $data[$i - 1]['low'] - $data[$i]['low'];

            $plusDM[] = ($highDiff > $lowDiff && $highDiff > 0) ? $highDiff : 0;
            $minusDM[] = ($lowDiff > $highDiff && $lowDiff > 0) ? $lowDiff : 0;
        }

        $avgPlusDM = array_sum(array_slice($plusDM, -$period)) / $period;
        $avgMinusDM = array_sum(array_slice($minusDM, -$period)) / $period;

        $plusDI = ($avgPlusDM / $atr) * 100;
        $minusDI = ($avgMinusDM / $atr) * 100;

        $dx = abs($plusDI - $minusDI) / ($plusDI + $minusDI) * 100;

        return round($dx, 2);
    }
}
