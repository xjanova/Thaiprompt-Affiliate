<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TradingBot;
use Illuminate\Auth\Access\HandlesAuthorization;

class TradingBotPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the trading bot
     */
    public function view(User $user, TradingBot $bot): bool
    {
        return $user->id === $bot->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can create trading bots
     */
    public function create(User $user): bool
    {
        // Check if user has active subscription
        $subscription = $user->tradingBotSubscription()->active()->first();

        if (!$subscription) {
            return false;
        }

        // Check if user hasn't exceeded bot limit
        $currentBots = $user->tradingBots()->count();

        return $currentBots < $subscription->package->max_bots;
    }

    /**
     * Determine if the user can update the trading bot
     */
    public function update(User $user, TradingBot $bot): bool
    {
        return $user->id === $bot->user_id;
    }

    /**
     * Determine if the user can delete the trading bot
     */
    public function delete(User $user, TradingBot $bot): bool
    {
        return $user->id === $bot->user_id;
    }

    /**
     * Determine if the user can start/stop the trading bot
     */
    public function control(User $user, TradingBot $bot): bool
    {
        return $user->id === $bot->user_id;
    }
}
