<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TradingBotPackage;
use App\Models\TradingExchange;
use App\Models\TradingStrategy;
use Illuminate\Support\Str;

class TradingBotSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPackages();
        $this->seedExchanges();
        $this->seedStrategies();
    }

    /**
     * Seed trading bot packages
     */
    protected function seedPackages(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'เริ่มต้นเทรดด้วยบอทอัจฉริยะ เหมาะสำหรับมือใหม่',
                'price' => 299.00,
                'billing_cycle' => 'monthly',
                'max_bots' => 1,
                'max_exchanges' => 1,
                'max_strategies' => 3,
                'max_active_trades' => 5,
                'ai_signals' => false,
                'advanced_analytics' => false,
                'backtesting' => true,
                'paper_trading' => true,
                'copy_trading' => false,
                'strategy_marketplace' => false,
                'custom_indicators' => false,
                'api_access' => false,
                'priority_support' => false,
                'display_order' => 1,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'สำหรับเทรดเดอร์มืออาชีพ พร้อม AI และเครื่องมือขั้นสูง',
                'price' => 999.00,
                'billing_cycle' => 'monthly',
                'max_bots' => 5,
                'max_exchanges' => 3,
                'max_strategies' => 10,
                'max_active_trades' => 20,
                'ai_signals' => true,
                'advanced_analytics' => true,
                'backtesting' => true,
                'paper_trading' => true,
                'copy_trading' => true,
                'strategy_marketplace' => true,
                'custom_indicators' => true,
                'api_access' => false,
                'priority_support' => true,
                'display_order' => 2,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'แพคเกจระดับองค์กร ไม่จำกัดบอท พร้อม API Access',
                'price' => 2999.00,
                'billing_cycle' => 'monthly',
                'max_bots' => 999,
                'max_exchanges' => 10,
                'max_strategies' => 999,
                'max_active_trades' => 100,
                'ai_signals' => true,
                'advanced_analytics' => true,
                'backtesting' => true,
                'paper_trading' => true,
                'copy_trading' => true,
                'strategy_marketplace' => true,
                'custom_indicators' => true,
                'api_access' => true,
                'priority_support' => true,
                'display_order' => 3,
                'is_featured' => true,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            TradingBotPackage::updateOrCreate(
                ['slug' => $package['slug']],
                $package
            );
        }

        $this->command->info('✅ Trading bot packages seeded successfully!');
    }

    /**
     * Seed exchanges
     */
    protected function seedExchanges(): void
    {
        $exchanges = [
            [
                'name' => 'Binance',
                'slug' => 'binance',
                'type' => 'crypto',
                'description' => 'แพลตฟอร์มเทรด Crypto ที่ใหญ่ที่สุดในโลก',
                'logo' => 'exchanges/binance.png',
                'website_url' => 'https://www.binance.com',
                'api_base_url' => 'https://api.binance.com',
                'supported_markets' => ['spot', 'futures', 'margin'],
                'supported_pairs' => ['BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'ADA/USDT', 'SOL/USDT'],
                'requires_kyc' => true,
                'supports_websocket' => true,
                'supports_paper_trading' => true,
                'supports_margin' => true,
                'supports_futures' => true,
                'is_active' => true,
                'is_maintenance' => false,
                'display_order' => 1,
                'rate_limit_per_minute' => 1200,
                'additional_config' => [
                    'trading_fee' => 0.001,
                    'withdrawal_fee' => 0.0005,
                ],
            ],
            [
                'name' => 'Bybit',
                'slug' => 'bybit',
                'type' => 'crypto',
                'description' => 'แพลตฟอร์มเทรด Derivatives ชั้นนำ',
                'logo' => 'exchanges/bybit.png',
                'website_url' => 'https://www.bybit.com',
                'api_base_url' => 'https://api.bybit.com',
                'supported_markets' => ['spot', 'futures'],
                'supported_pairs' => ['BTC/USDT', 'ETH/USDT', 'XRP/USDT', 'DOGE/USDT'],
                'requires_kyc' => true,
                'supports_websocket' => true,
                'supports_paper_trading' => true,
                'supports_margin' => false,
                'supports_futures' => true,
                'is_active' => true,
                'is_maintenance' => false,
                'display_order' => 2,
                'rate_limit_per_minute' => 600,
                'additional_config' => [
                    'trading_fee' => 0.001,
                    'withdrawal_fee' => 0.0005,
                ],
            ],
            [
                'name' => 'MT4',
                'slug' => 'mt4',
                'type' => 'forex',
                'description' => 'MetaTrader 4 - แพลตฟอร์ม Forex ยอดนิยม',
                'logo' => 'exchanges/mt4.png',
                'website_url' => 'https://www.metatrader4.com',
                'api_base_url' => null,
                'supported_markets' => ['forex', 'cfd'],
                'supported_pairs' => ['EUR/USD', 'GBP/USD', 'USD/JPY', 'AUD/USD'],
                'requires_kyc' => true,
                'supports_websocket' => false,
                'supports_paper_trading' => true,
                'supports_margin' => true,
                'supports_futures' => false,
                'is_active' => true,
                'is_maintenance' => false,
                'display_order' => 3,
                'rate_limit_per_minute' => 300,
                'additional_config' => [
                    'requires_bridge' => true,
                ],
            ],
        ];

        foreach ($exchanges as $exchange) {
            TradingExchange::updateOrCreate(
                ['slug' => $exchange['slug']],
                $exchange
            );
        }

        $this->command->info('✅ Exchanges seeded successfully!');
    }

    /**
     * Seed demo strategies
     */
    protected function seedStrategies(): void
    {
        // Get first user or create a demo user
        $user = \App\Models\User::first();

        if (!$user) {
            $this->command->warn('⚠️ No users found. Create a user first to seed strategies.');
            return;
        }

        $strategies = [
            [
                'user_id' => $user->id,
                'name' => 'RSI + MACD Combo',
                'slug' => 'rsi-macd-combo-' . Str::random(8),
                'description' => 'กลยุทธ์ยอดนิยม ใช้ RSI และ MACD ร่วมกันเพื่อหาจุด Entry ที่แม่นยำ',
                'strategy_type' => 'trend_following',
                'indicators' => [
                    ['name' => 'rsi', 'params' => ['period' => 14]],
                    ['name' => 'macd', 'params' => ['fast' => 12, 'slow' => 26, 'signal' => 9]],
                ],
                'entry_conditions' => [
                    ['indicator' => 'rsi', 'operator' => '<', 'value' => 30, 'action' => 'buy', 'weight' => 2],
                    ['indicator' => 'macd_histogram', 'operator' => '>', 'value' => 0, 'action' => 'buy', 'weight' => 1],
                ],
                'use_ai' => false,
                'stop_loss_percentage' => 2.00,
                'take_profit_percentage' => 5.00,
                'max_daily_loss' => 5.00,
                'is_public' => true,
                'is_for_sale' => true,
                'price' => 499.00,
                'status' => 'active',
                'is_verified' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'AI-Powered LSTM Strategy',
                'slug' => 'ai-lstm-strategy-' . Str::random(8),
                'description' => 'กลยุทธ์ AI ที่ใช้ LSTM Neural Network วิเคราะห์ตลาด พร้อม Backtesting 85% Win Rate',
                'strategy_type' => 'ai_ml',
                'indicators' => [
                    ['name' => 'ema', 'params' => ['period' => 20]],
                    ['name' => 'atr', 'params' => ['period' => 14]],
                ],
                'use_ai' => true,
                'ai_model' => 'lstm',
                'ai_confidence_threshold' => 75.00,
                'stop_loss_percentage' => 1.50,
                'take_profit_percentage' => 4.00,
                'trailing_stop_percentage' => 1.00,
                'max_daily_loss' => 3.00,
                'is_public' => true,
                'is_for_sale' => true,
                'price' => 1999.00,
                'status' => 'active',
                'is_verified' => true,
                'win_rate' => 85.50,
                'total_trades' => 250,
                'winning_trades' => 214,
                'average_rating' => 4.8,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Grid Trading Bot',
                'slug' => 'grid-trading-bot-' . Str::random(8),
                'description' => 'Grid Trading สำหรับตลาด Sideways กำไรจากความผันผวน',
                'strategy_type' => 'grid_trading',
                'indicators' => [
                    ['name' => 'bollinger_bands', 'params' => ['period' => 20, 'std_dev' => 2]],
                ],
                'stop_loss_percentage' => 3.00,
                'take_profit_percentage' => 2.00,
                'max_daily_loss' => 5.00,
                'is_public' => true,
                'is_for_sale' => true,
                'price' => 799.00,
                'status' => 'active',
                'is_verified' => true,
            ],
        ];

        foreach ($strategies as $strategy) {
            TradingStrategy::create($strategy);
        }

        $this->command->info('✅ Demo trading strategies seeded successfully!');
    }
}
