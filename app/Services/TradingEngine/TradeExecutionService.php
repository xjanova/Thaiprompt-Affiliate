<?php

namespace App\Services\TradingEngine;

use App\Models\TradingBot;
use App\Models\TradingSignal;
use App\Models\TradingTrade;
use App\Models\TradingNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TradeExecutionService
{
    /**
     * Execute trade based on signal
     */
    public function execute(TradingBot $bot, TradingSignal $signal): ?TradingTrade
    {
        DB::beginTransaction();

        try {
            // Calculate position size
            $positionSize = $this->calculatePositionSize($bot, $signal);

            // Create trade record
            $trade = TradingTrade::create([
                'bot_id' => $bot->id,
                'account_id' => $bot->account_id,
                'signal_id' => $signal->id,
                'trading_pair' => $bot->trading_pair,
                'side' => $signal->signal_type,
                'type' => 'market',
                'position_side' => $signal->signal_type === 'buy' ? 'long' : 'short',
                'quantity' => $positionSize,
                'entry_price' => $signal->suggested_entry,
                'stop_loss' => $signal->suggested_stop_loss,
                'take_profit' => $signal->suggested_take_profit,
                'status' => 'pending',
                'is_paper_trade' => $bot->dry_run,
            ]);

            // Execute on exchange (if not paper trading)
            if (!$bot->dry_run) {
                $this->executeOnExchange($bot, $trade);
            } else {
                // Simulate execution for paper trading
                $trade->update([
                    'status' => 'open',
                    'opened_at' => now(),
                ]);
            }

            // Update bot capital
            $usedCapital = $signal->suggested_entry * $positionSize;
            $bot->decrement('available_capital', $usedCapital);

            // Mark signal as executed
            $signal->update([
                'status' => 'executed',
                'trade_id' => $trade->id,
                'executed_at' => now(),
            ]);

            // Send notification
            $this->sendTradeNotification($bot, $trade, 'opened');

            DB::commit();

            Log::info("Trade executed", [
                'trade_id' => $trade->id,
                'bot_id' => $bot->id,
                'pair' => $trade->trading_pair,
                'side' => $trade->side,
                'quantity' => $trade->quantity,
                'price' => $trade->entry_price,
            ]);

            return $trade;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Trade execution failed", [
                'bot_id' => $bot->id,
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);

            $signal->update([
                'status' => 'failed',
            ]);

            return null;
        }
    }

    /**
     * Close position
     */
    public function closePosition(TradingTrade $trade, float $exitPrice, string $reason): bool
    {
        DB::beginTransaction();

        try {
            $bot = $trade->bot;

            // Execute close on exchange (if not paper trading)
            if (!$bot->dry_run) {
                $this->closeOnExchange($bot, $trade, $exitPrice);
            }

            // Calculate fees
            $exitFee = $this->calculateFee($exitPrice * $trade->quantity, $bot);

            // Update trade
            $trade->update([
                'exit_price' => $exitPrice,
                'exit_fee' => $exitFee,
                'total_fees' => $trade->entry_fee + $exitFee,
                'status' => 'closed',
                'closed_at' => now(),
                'close_reason' => $reason,
                'duration_seconds' => $trade->opened_at ? now()->diffInSeconds($trade->opened_at) : 0,
            ]);

            // Calculate profit/loss
            $trade->calculateProfit();

            // Return capital to bot
            $returnedCapital = $trade->entry_price * $trade->quantity + $trade->net_profit;
            $bot->increment('available_capital', $returnedCapital);

            // Update bot performance
            $bot->updatePerformance();

            // Update bot's last trade time
            $bot->update(['last_trade_at' => now()]);

            // Send notification
            $this->sendTradeNotification($bot, $trade, 'closed');

            DB::commit();

            Log::info("Position closed", [
                'trade_id' => $trade->id,
                'exit_price' => $exitPrice,
                'profit' => $trade->net_profit,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to close position", [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute order on exchange
     */
    protected function executeOnExchange(TradingBot $bot, TradingTrade $trade): void
    {
        $exchange = $bot->account->exchange;
        $connector = app("App\\Services\\Exchange\\" . $exchange->slug . "Connector");

        // Place market order
        $order = $connector->createMarketOrder(
            $bot->account,
            $trade->trading_pair,
            $trade->side,
            $trade->quantity
        );

        // Update trade with exchange order ID
        $trade->update([
            'exchange_order_id' => $order['id'] ?? null,
            'entry_price' => $order['price'] ?? $trade->entry_price,
            'entry_fee' => $order['fee'] ?? $this->calculateFee($trade->entry_price * $trade->quantity, $bot),
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    /**
     * Close position on exchange
     */
    protected function closeOnExchange(TradingBot $bot, TradingTrade $trade, float $exitPrice): void
    {
        $exchange = $bot->account->exchange;
        $connector = app("App\\Services\\Exchange\\" . $exchange->slug . "Connector");

        // Determine close side (opposite of entry)
        $closeSide = $trade->side === 'buy' ? 'sell' : 'buy';

        // Place market order to close
        $order = $connector->createMarketOrder(
            $bot->account,
            $trade->trading_pair,
            $closeSide,
            $trade->quantity
        );
    }

    /**
     * Calculate position size based on bot settings
     */
    protected function calculatePositionSize(TradingBot $bot, TradingSignal $signal): float
    {
        $positionPercentage = $bot->position_size_percentage / 100;
        $positionValue = $bot->available_capital * $positionPercentage;

        // Calculate quantity
        $quantity = $positionValue / $signal->suggested_entry;

        // Apply leverage if enabled
        if ($bot->use_leverage) {
            $quantity *= $bot->leverage;
        }

        // Apply max position size limit if set
        if ($bot->strategy->max_position_size && $quantity > $bot->strategy->max_position_size) {
            $quantity = $bot->strategy->max_position_size;
        }

        return round($quantity, 8);
    }

    /**
     * Calculate trading fee
     */
    protected function calculateFee(float $orderValue, TradingBot $bot): float
    {
        // Default fee is 0.1% (varies by exchange)
        $feePercentage = $bot->account->exchange->additional_config['trading_fee'] ?? 0.001;
        return $orderValue * $feePercentage;
    }

    /**
     * Send trade notification to user
     */
    protected function sendTradeNotification(TradingBot $bot, TradingTrade $trade, string $event): void
    {
        if (!$bot->notifications_enabled) {
            return;
        }

        $type = $event === 'opened' ? 'trade_opened' : 'trade_closed';
        $title = $event === 'opened'
            ? "Trade Opened: {$trade->trading_pair}"
            : "Trade Closed: {$trade->trading_pair}";

        $message = $event === 'opened'
            ? "New {$trade->side} position opened at {$trade->entry_price}"
            : "Position closed at {$trade->exit_price}. P&L: {$trade->net_profit}";

        TradingNotification::create([
            'user_id' => $bot->user_id,
            'bot_id' => $bot->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => [
                'trade_id' => $trade->id,
                'pair' => $trade->trading_pair,
                'side' => $trade->side,
                'entry_price' => $trade->entry_price,
                'exit_price' => $trade->exit_price,
                'profit' => $trade->net_profit,
            ],
            'priority' => 'normal',
            'channels' => $bot->notification_channels ?? ['email'],
        ]);
    }
}
