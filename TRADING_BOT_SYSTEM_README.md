# 🤖 ระบบเทรดดิ้งบอทอัจฉริยะ (AI Trading Bot System)

## 📋 สารบัญ
- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [คุณสมบัติหลัก](#คุณสมบัติหลัก)
- [สถาปัตยกรรมระบบ](#สถาปัตยกรรมระบบ)
- [การติดตั้ง](#การติดตั้ง)
- [การใช้งาน](#การใช้งาน)
- [API Documentation](#api-documentation)
- [การพัฒนาต่อ](#การพัฒนาต่อ)

---

## 🎯 ภาพรวมระบบ

ระบบเทรดดิ้งบอทอัจฉริยะที่สมบูรณ์แบบ รองรับทั้ง **Cryptocurrency** และ **Forex** พัฒนาด้วย **Laravel 11** และ **AI/ML**

### 🌟 จุดเด่น
- ✅ **Multi-Market Support** - รองรับ Crypto (Binance, Bybit) และ Forex (MT4/MT5)
- ✅ **AI-Powered Trading** - ใช้ LSTM, Transformer, Random Forest
- ✅ **Package System** - 3 แพคเกจ (Starter, Professional, Enterprise)
- ✅ **Bot Marketplace** - ซื้อขายกลยุทธ์เทรด
- ✅ **Advanced Analytics** - วิเคราะห์ผลการเทรดแบบ Real-time
- ✅ **Risk Management** - ระบบบริหารความเสี่ยงอัตโนมัติ
- ✅ **Backtesting** - ทดสอบกลยุทธ์ย้อนหลัง
- ✅ **Paper Trading** - ฝึกเทรดด้วยเงินเสมือน

---

## 🚀 คุณสมบัติหลัก

### 1. ระบบจัดการบอท
- สร้างและจัดการบอทเทรดหลายตัว
- ตั้งค่ากลยุทธ์และพารามิเตอร์
- Start/Stop/Pause บอทได้ทันที
- ดูสถิติและประสิทธิภาพแบบ Real-time

### 2. กลยุทธ์เทรด (Trading Strategies)
- **Trend Following** - ตามเทรนด์ตลาด
- **Mean Reversion** - เทรดจากการกลับตัวของราคา
- **Breakout** - เทรดจุด Breakout
- **Scalping** - เทรดระยะสั้น
- **Grid Trading** - เทรดแบบตาราง
- **Arbitrage** - หาส่วนต่างราคา
- **AI/ML Strategies** - กลยุทธ์ AI

### 3. Technical Indicators
- RSI (Relative Strength Index)
- MACD (Moving Average Convergence Divergence)
- EMA/SMA (Moving Averages)
- Bollinger Bands
- ATR (Average True Range)
- Stochastic Oscillator
- ADX (Average Directional Index)

### 4. AI Models
- **LSTM** (Long Short-Term Memory) - วิเคราะห์ Time Series
- **Transformer** - Pattern Recognition
- **Random Forest** - Ensemble Learning
- **Sentiment Analysis** - วิเคราะห์ความรู้สึกตลาด

### 5. Risk Management
- Stop Loss อัตโนมัติ
- Take Profit
- Trailing Stop
- Max Daily Loss Limit
- Position Size Management
- Portfolio Diversification

### 6. Exchange Integration
- **Binance** - Spot, Futures, Margin
- **Bybit** - Spot, Futures
- **MT4/MT5** - Forex Trading

---

## 🏗️ สถาปัตยกรรมระบบ

### Database Schema (13 Tables)
```
trading_bot_packages          - แพคเกจและราคา
trading_bot_subscriptions     - การสมัครสมาชิก
trading_exchanges             - ข้อมูล Exchange
trading_accounts              - บัญชีเทรด
trading_strategies            - กลยุทธ์เทรด
trading_bots                  - บอทเทรด
trading_signals               - สัญญาณเทรด
trading_trades                - ประวัติการเทรด
trading_market_data           - ข้อมูลตลาด (OHLCV)
trading_portfolio_snapshots   - Snapshot พอร์ต
trading_strategy_purchases    - การซื้อกลยุทธ์
trading_strategy_reviews      - รีวิวกลยุทธ์
trading_backtests             - ผล Backtest
trading_notifications         - การแจ้งเตือน
```

### Models (13 Models)
```
TradingBotPackage
TradingBotSubscription
TradingExchange
TradingAccount
TradingStrategy
TradingBot
TradingSignal
TradingTrade
TradingMarketData
TradingPortfolioSnapshot
TradingStrategyPurchase
TradingStrategyReview
TradingBacktest
TradingNotification
```

### Services
```
TradingEngine/
├── TradingEngineService.php        - Core engine
├── SignalGeneratorService.php      - สร้างสัญญาณ
├── TradeExecutionService.php       - Execute trades
├── Indicators/
│   └── TechnicalIndicatorService.php
└── AI/
    └── AIStrategyService.php

Exchange/
├── BaseExchangeConnector.php
├── BinanceConnector.php
└── BybitConnector.php
```

---

## 📦 การติดตั้ง

### ขั้นตอนที่ 1: Run Migrations
```bash
php artisan migrate
```

### ขั้นตอนที่ 2: Seed ข้อมูลตัวอย่าง
```bash
php artisan db:seed --class=TradingBotSystemSeeder
```

สคริปต์นี้จะสร้าง:
- ✅ 3 แพคเกจ (Starter, Professional, Enterprise)
- ✅ 3 Exchanges (Binance, Bybit, MT4)
- ✅ 3 กลยุทธ์ตัวอย่าง

### ขั้นตอนที่ 3: ตั้งค่า Cron Job (สำหรับ Production)
เพิ่มใน crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 💡 การใช้งาน

### 1. สร้างบัญชีเทรด (Trading Account)
```php
use App\Models\TradingAccount;

$account = TradingAccount::create([
    'user_id' => $user->id,
    'exchange_id' => 1, // Binance
    'account_name' => 'My Binance Account',
    'account_type' => 'live', // หรือ 'demo', 'paper'
    'market_type' => 'spot',
    'api_key' => 'your-api-key',
    'api_secret' => 'your-api-secret',
    'initial_balance' => 1000.00,
    'current_balance' => 1000.00,
    'balance_currency' => 'USDT',
]);
```

### 2. สร้างกลยุทธ์
```php
use App\Models\TradingStrategy;

$strategy = TradingStrategy::create([
    'user_id' => $user->id,
    'name' => 'My RSI Strategy',
    'description' => 'Simple RSI strategy',
    'strategy_type' => 'trend_following',
    'indicators' => [
        ['name' => 'rsi', 'params' => ['period' => 14]],
        ['name' => 'macd', 'params' => ['fast' => 12, 'slow' => 26]],
    ],
    'entry_conditions' => [
        ['indicator' => 'rsi', 'operator' => '<', 'value' => 30, 'action' => 'buy'],
    ],
    'stop_loss_percentage' => 2.00,
    'take_profit_percentage' => 5.00,
    'status' => 'active',
]);
```

### 3. สร้างและเริ่มบอท
```php
use App\Models\TradingBot;
use App\Services\TradingEngine\TradingEngineService;

// สร้างบอท
$bot = TradingBot::create([
    'user_id' => $user->id,
    'subscription_id' => $subscription->id,
    'account_id' => $account->id,
    'strategy_id' => $strategy->id,
    'name' => 'BTC/USDT Bot',
    'trading_pair' => 'BTC/USDT',
    'base_currency' => 'BTC',
    'quote_currency' => 'USDT',
    'timeframe' => '1h',
    'allocated_capital' => 500.00,
    'available_capital' => 500.00,
    'position_size_percentage' => 10.00,
    'dry_run' => true, // Paper trading
]);

// เริ่มบอท
$engineService = app(TradingEngineService::class);
$engineService->startBot($bot);
```

### 4. ประมวลผลเทรด (Trading Cycle)
```php
// ควรเรียกใช้ผ่าน Cron Job หรือ Queue
$engineService->processTradingCycle($bot);
```

---

## 🎨 แพคเกจ (Packages)

### 1. Starter - ฿299/เดือน
- 1 บอท
- 1 Exchange
- 3 กลยุทธ์
- 5 Trades พร้อมกัน
- Paper Trading
- Basic Backtesting

### 2. Professional - ฿999/เดือน ⭐
- 5 บอท
- 3 Exchanges
- 10 กลยุทธ์
- 20 Trades พร้อมกัน
- ✅ AI Signals
- ✅ Advanced Analytics
- ✅ Copy Trading
- ✅ Strategy Marketplace
- ✅ Priority Support

### 3. Enterprise - ฿2,999/เดือน 🚀
- ไม่จำกัดบอท
- 10 Exchanges
- ไม่จำกัดกลยุทธ์
- 100 Trades พร้อมกัน
- ✅ All Professional Features
- ✅ API Access
- ✅ Custom Indicators
- ✅ Dedicated Support

---

## 🔧 API Reference

### Exchange Connectors

#### Binance
```php
$connector = app(BinanceConnector::class);

// Get ticker
$ticker = $connector->getTicker('BTC/USDT');

// Get OHLCV
$candles = $connector->getOHLCV('BTC/USDT', '1h', 100);

// Create order
$order = $connector->createMarketOrder($account, 'BTC/USDT', 'buy', 0.001);

// Get balance
$balances = $connector->getBalance($account);
```

#### Technical Indicators
```php
$indicatorService = app(TechnicalIndicatorService::class);

$indicators = $indicatorService->calculate($marketData, [
    ['name' => 'rsi', 'params' => ['period' => 14]],
    ['name' => 'macd', 'params' => ['fast' => 12, 'slow' => 26]],
]);
```

#### AI Predictions
```php
$aiService = app(AIStrategyService::class);

$prediction = $aiService->predict($marketData, $strategy);
// Returns: ['model' => 'lstm', 'prediction' => 'buy', 'confidence' => 85.5]
```

---

## 📊 Bot Marketplace

### ขายกลยุทธ์
```php
$strategy->update([
    'is_public' => true,
    'is_for_sale' => true,
    'price' => 999.00,
]);
```

### ซื้อกลยุทธ์
```php
use App\Models\TradingStrategyPurchase;

$purchase = TradingStrategyPurchase::create([
    'buyer_id' => $user->id,
    'seller_id' => $strategy->user_id,
    'strategy_id' => $strategy->id,
    'price' => $strategy->price,
    'commission_percentage' => 10.00,
    'commission_amount' => $strategy->price * 0.1,
    'seller_payout' => $strategy->price * 0.9,
    'status' => 'completed',
    'purchased_at' => now(),
]);
```

---

## 🔐 Security

### API Credentials
- API Keys และ Secrets ถูก **encrypt** ด้วย Laravel Crypt
- ใช้ HTTPS สำหรับการเชื่อมต่อทั้งหมด
- Rate limiting ป้องกัน API abuse
- Two-factor authentication (แนะนำ)

### Risk Management
- Daily loss limits
- Position size limits
- Maximum drawdown protection
- Emergency stop mechanism

---

## 📈 Performance Metrics

ระบบติดตามและคำนวณ:
- **Win Rate** - อัตราการชนะ
- **Profit Factor** - กำไร / ขาดทุน
- **Sharpe Ratio** - ผลตอบแทนเทียบความเสี่ยง
- **Sortino Ratio** - Sharpe ที่ปรับปรุง
- **Max Drawdown** - การขาดทุนสูงสุด
- **ROI** - ผลตอบแทนจากเงินลงทุน

---

## 🛣️ Roadmap / การพัฒนาต่อ

### Phase 1 - Core System ✅
- [x] Database Schema
- [x] Models & Relationships
- [x] Trading Engine
- [x] Exchange Connectors (Binance, Bybit)
- [x] Technical Indicators
- [x] AI Strategy Engine

### Phase 2 - Frontend & UI
- [ ] Dashboard หน้าหลัก
- [ ] Bot Management UI
- [ ] Strategy Builder (Visual)
- [ ] Real-time Charts (TradingView)
- [ ] Portfolio Analytics
- [ ] Bot Marketplace UI

### Phase 3 - Advanced Features
- [ ] Copy Trading System
- [ ] Social Trading Features
- [ ] Multi-timeframe Analysis
- [ ] Custom Indicator Builder
- [ ] Webhook Integration
- [ ] Mobile App (React Native)

### Phase 4 - Enterprise
- [ ] White Label Solution
- [ ] API for External Apps
- [ ] Advanced ML Models
- [ ] High-Frequency Trading
- [ ] Institutional Features

---

## 🐛 Troubleshooting

### บอทไม่ทำงาน
1. ตรวจสอบ API Credentials
2. ตรวจสอบ Balance เพียงพอ
3. ตรวจสอบ Subscription active
4. ดู logs: `storage/logs/laravel.log`

### การเชื่อมต่อ Exchange ล้มเหลว
1. ตรวจสอบ API Key permissions
2. ตรวจสอบ IP Whitelist (ถ้ามี)
3. ตรวจสอบ Rate Limits

---

## 📞 Support & Contact

- **Documentation**: [Link to docs]
- **GitHub Issues**: [Link to repo issues]
- **Email**: support@yourdomain.com
- **Discord**: [Link to Discord server]

---

## 📝 License

This Trading Bot System is proprietary software.
© 2025 Thai Prompt Affiliate. All rights reserved.

---

## 🎓 ตัวอย่างการใช้งานจริง

### ตัวอย่างที่ 1: Simple RSI Bot
```php
// 1. สร้าง Strategy
$strategy = TradingStrategy::create([
    'user_id' => 1,
    'name' => 'RSI Oversold Strategy',
    'strategy_type' => 'mean_reversion',
    'indicators' => [
        ['name' => 'rsi', 'params' => ['period' => 14]]
    ],
    'entry_conditions' => [
        ['indicator' => 'rsi', 'operator' => '<', 'value' => 30, 'action' => 'buy', 'weight' => 3]
    ],
    'stop_loss_percentage' => 2.5,
    'take_profit_percentage' => 5.0,
]);

// 2. สร้าง Bot
$bot = TradingBot::create([
    'user_id' => 1,
    'subscription_id' => 1,
    'account_id' => 1,
    'strategy_id' => $strategy->id,
    'name' => 'ETH RSI Bot',
    'trading_pair' => 'ETH/USDT',
    'timeframe' => '1h',
    'allocated_capital' => 1000,
    'dry_run' => true, // Paper trading
]);

// 3. Start
app(TradingEngineService::class)->startBot($bot);
```

### ตัวอย่างที่ 2: AI-Powered Bot
```php
$strategy = TradingStrategy::create([
    'user_id' => 1,
    'name' => 'AI LSTM Bitcoin Bot',
    'strategy_type' => 'ai_ml',
    'use_ai' => true,
    'ai_model' => 'lstm',
    'ai_confidence_threshold' => 80.0,
    'indicators' => [
        ['name' => 'ema', 'params' => ['period' => 20]],
        ['name' => 'atr', 'params' => ['period' => 14]]
    ],
]);
```

---

## 🎉 เริ่มต้นใช้งาน

```bash
# 1. Run migrations
php artisan migrate

# 2. Seed data
php artisan db:seed --class=TradingBotSystemSeeder

# 3. Start Laravel
php artisan serve

# 4. (Optional) Start queue worker
php artisan queue:work

# 5. เข้าใช้งาน
http://localhost:8000/trading-bots
```

**ยินดีต้อนรับสู่ยุคของการเทรดอัตโนมัติด้วย AI! 🚀**
