# 🤖 ระบบ Crypto Trading Bot API - คู่มือฉบับสมบูรณ์

## 📋 สารบัญ
- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [ฟีเจอร์หลัก](#ฟีเจอร์หลัก)
- [การติดตั้ง](#การติดตั้ง)
- [การใช้งาน](#การใช้งาน)
- [API Reference](#api-reference)
- [Exchanges ที่รองรับ](#exchanges-ที่รองรับ)
- [AI Trading Strategy](#ai-trading-strategy)
- [Arbitrage System](#arbitrage-system)

---

## 🎯 ภาพรวมระบบ

ระบบบอทเทรดคริปโตอัตโนมัติที่สมบูรณ์แบบ พัฒนาด้วย Laravel 11 พร้อมระบบ AI ที่ชาญฉลาด รองรับการเชื่อมต่อหลาย Exchange พร้อมกันสำหรับการทำ Arbitrage

### 🌟 จุดเด่นของระบบ

- ✅ **Multi-Exchange Support** - รองรับ Bitkub, Binance, KuCoin, Bybit
- ✅ **Arbitrage Trading** - หาส่วนต่างราคาระหว่าง exchanges อัตโนมัติ
- ✅ **Enhanced AI** - ระบบ AI หลายระดับ (LSTM, Transformer, Random Forest)
- ✅ **Admin Panel** - ระบบจัดการแอดมินครบครัน
- ✅ **Bot Marketplace** - ซื้อขายกลยุทธ์เทรด
- ✅ **Subscription System** - 3 แพคเกจ (Starter, Professional, Enterprise)
- ✅ **Risk Management** - ระบบบริหารความเสี่ยงอัตโนมัติ
- ✅ **Real-time Analytics** - วิเคราะห์ผลการเทรดแบบเรียลไทม์

---

## 🚀 ฟีเจอร์หลัก

### 1. Exchange Connectors (รองรับ 5 exchanges)

#### 🇹🇭 Thai Exchanges
- **Bitkub** - Exchange ชั้นนำของไทย รองรับ THB
- **Binance** - Global platform
- **KuCoin** - รองรับ Altcoin มากมาย

#### 🌏 International Exchanges
- **Bybit** - Derivatives trading
- **MT4** - Forex trading

### 2. Trading Strategies

- **Trend Following** - ตามเทรนด์ตลาด
- **Mean Reversion** - กลับตัวของราคา
- **Breakout** - จุด Breakout
- **Scalping** - เทรดระยะสั้น
- **Grid Trading** - เทรดแบบตาราง
- **Arbitrage** - หาส่วนต่างราคา ⭐ NEW
- **AI/ML Strategies** - กลยุทธ์ AI ขั้นสูง

### 3. AI Models (Enhanced)

- **LSTM** (Long Short-Term Memory) - วิเคราะห์ Time Series
- **Transformer** - Pattern Recognition
- **Random Forest** - Ensemble Learning
- **Sentiment Analysis** - วิเคราะห์ความรู้สึกตลาด
- **Market Regime Detection** - ตรวจจับสภาวะตลาด ⭐ NEW
- **Multi-Timeframe Analysis** - วิเคราะห์หลาย Timeframe ⭐ NEW
- **Advanced Pattern Recognition** - ตรวจจับรูปแบบขั้นสูง ⭐ NEW
- **Risk-Adjusted Predictions** - คำนวณความเสี่ยง ⭐ NEW

### 4. Arbitrage System ⭐ NEW

ระบบหาส่วนต่างราคาระหว่าง exchanges อัตโนมัติ

#### คุณสมบัติ:
- เปรียบเทียบราคาจากหลาย exchanges พร้อมกัน
- คำนวณกำไรหลังหักค่าธรรมเนียม
- Execute trades อัตโนมัติเมื่อพบโอกาส
- รองรับ Minimum profit threshold ที่ตั้งค่าได้
- Cache ข้อมูลเพื่อลด API rate limits

### 5. Admin Panel

- Dashboard ภาพรวมระบบ
- จัดการแพคเกจและราคา
- จัดการ Subscriptions
- ติดตามผลการเทรดของผู้ใช้
- จัดการ Exchanges
- Analytics และรายงาน
- Arbitrage Monitor
- System Settings

### 6. Package System

#### 💵 Starter - ฿299/เดือน
- 1 บอท
- 1 Exchange
- 3 กลยุทธ์
- 5 Trades พร้อมกัน
- Paper Trading
- Basic Backtesting

#### 💰 Professional - ฿999/เดือน ⭐ แนะนำ
- 5 บอท
- 3 Exchanges
- 10 กลยุทธ์
- 20 Trades พร้อมกัน
- ✅ AI Signals
- ✅ Arbitrage Trading
- ✅ Advanced Analytics
- ✅ Strategy Marketplace
- ✅ Priority Support

#### 🚀 Enterprise - ฿2,999/เดือน
- ไม่จำกัดบอท
- 10 Exchanges
- ไม่จำกัดกลยุทธ์
- 100 Trades พร้อมกัน
- ✅ All Professional Features
- ✅ API Access
- ✅ Custom Indicators
- ✅ Dedicated Support

---

## 📦 การติดตั้ง

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed ข้อมูลตัวอย่าง
```bash
php artisan db:seed --class=TradingBotSystemSeeder
```

สคริปต์นี้จะสร้าง:
- ✅ 3 แพคเกจ (Starter, Professional, Enterprise)
- ✅ 5 Exchanges (Binance, Bybit, Bitkub, KuCoin, MT4)

### 3. ตั้งค่า Cron Job (สำหรับ Production)
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 💡 การใช้งาน

### 1. สำหรับผู้ใช้งานทั่วไป

#### สมัครแพคเกจ
```
เข้าสู่ระบบ → ไปที่ /trading-bot/marketplace → เลือกแพคเกจ → Subscribe
```

#### สร้างบัญชีเทรด
```
Dashboard → Trading Accounts → เพิ่มบัญชีใหม่
```

ระบุข้อมูล:
- Exchange (Bitkub, Binance, KuCoin, ฯลฯ)
- API Key
- API Secret
- API Passphrase (สำหรับ KuCoin)

#### สร้างกลยุทธ์
```
Dashboard → Strategies → สร้างกลยุทธ์ใหม่
```

เลือก:
- ประเภทกลยุทธ์ (Arbitrage, AI/ML, Trend Following, ฯลฯ)
- Indicators
- Entry/Exit Conditions
- Stop Loss & Take Profit
- AI Model (ถ้าเลือก AI/ML)

#### สร้างบอท
```
Dashboard → Create Bot
```

ตั้งค่า:
- ชื่อบอท
- เลือกบัญชีเทรด
- เลือกกลยุทธ์
- Trading Pair (BTC/USDT, ETH/THB, ฯลฯ)
- Timeframe
- Capital allocation

#### เริ่มเทรด
```
Bot Details → Start Bot
```

### 2. สำหรับแอดมิน

#### เข้าถึง Admin Panel
```
/admin/trading-bot/dashboard
```

#### จัดการแพคเกจ
```
Admin → Trading Bot → Packages
```

#### ติดตาม Arbitrage Opportunities
```
Admin → Trading Bot → Arbitrage Monitor
```

#### ดู Analytics
```
Admin → Trading Bot → Analytics
```

---

## 🔧 API Reference

### Exchange Connectors

#### Bitkub Connector
```php
use App\Services\Exchange\BitkubConnector;

$connector = app(BitkubConnector::class);

// Get ticker
$ticker = $connector->getTicker('BTC/THB');

// Get OHLCV data
$candles = $connector->getOHLCV('BTC/THB', '1h', 100);

// Create market order
$order = $connector->createMarketOrder($account, 'BTC/THB', 'buy', 0.001);

// Get balance
$balances = $connector->getBalance($account);
```

#### KuCoin Connector
```php
use App\Services\Exchange\KuCoinConnector;

$connector = app(KuCoinConnector::class);

// Get ticker
$ticker = $connector->getTicker('BTC/USDT');

// Create limit order
$order = $connector->createLimitOrder($account, 'BTC/USDT', 'buy', 0.001, 50000);
```

### Arbitrage Service

```php
use App\Services\TradingEngine\ArbitrageService;

$arbitrageService = app(ArbitrageService::class);

// Find opportunities
$opportunities = $arbitrageService->findOpportunities(
    ['BTC/USDT', 'ETH/USDT'],
    [1, 2, 3] // Exchange IDs
);

// Execute arbitrage
$result = $arbitrageService->executeArbitrage($bot, $opportunity, 100);

// Monitor opportunities
$results = $arbitrageService->monitorOpportunities();
```

### Enhanced AI Strategy

```php
use App\Services\TradingEngine\AI\EnhancedAIStrategyService;

$aiService = app(EnhancedAIStrategyService::class);

// Advanced prediction
$prediction = $aiService->advancedPredict($marketData, $strategy);

// Returns:
// [
//     'model' => 'ensemble_enhanced',
//     'prediction' => 'buy',
//     'confidence' => 85.5,
//     'enhanced' => true,
//     'market_regime' => [...],
//     'risk_metrics' => [...],
//     'patterns' => [...]
// ]
```

---

## 🌐 Exchanges ที่รองรับ

### 1. Bitkub (Thailand) 🇹🇭
- **ประเภท**: Crypto
- **Markets**: Spot
- **Base Currency**: THB (บาทไทย)
- **Trading Fee**: 0.25%
- **API**: REST + WebSocket
- **KYC**: Required

### 2. Binance (Global)
- **ประเภท**: Crypto
- **Markets**: Spot, Futures, Margin
- **Trading Fee**: 0.1%
- **API**: REST + WebSocket
- **KYC**: Required

### 3. KuCoin (Global)
- **ประเภท**: Crypto
- **Markets**: Spot, Futures, Margin
- **Trading Fee**: 0.1%
- **API**: REST + WebSocket
- **KYC**: Optional
- **Note**: ต้องการ API Passphrase

### 4. Bybit
- **ประเภท**: Crypto
- **Markets**: Spot, Derivatives
- **Trading Fee**: 0.1%
- **API**: REST + WebSocket

### 5. MT4 (Forex)
- **ประเภท**: Forex
- **Markets**: Forex, CFD
- **API**: Bridge required

---

## 🧠 AI Trading Strategy

### Market Regime Detection
ตรวจจับสภาวะตลาดปัจจุบัน:
- **Trending** - ตลาดมีทิศทางชัดเจน
- **Ranging** - ตลาดแกว่งในช่วง
- **Volatile** - ตลาดผันผวนสูง

### Multi-Timeframe Analysis
วิเคราะห์หลาย Timeframe เพื่อหา Confluence:
- Short-term (20 candles)
- Medium-term (50 candles)
- Long-term (All data)

### Advanced Pattern Recognition
ตรวจจับรูปแบบ:
- Double Top/Bottom
- Head and Shoulders
- Triangle
- Flag & Wedge
- Support/Resistance Levels
- Fibonacci Retracements

### Risk Metrics
- **Sharpe Ratio** - ผลตอบแทนเทียบความเสี่ยง
- **Sortino Ratio** - Sharpe ปรับปรุง
- **Max Drawdown** - การขาดทุนสูงสุด
- **Value at Risk (VaR)** - ความเสี่ยงที่ระดับ 95%
- **Risk Score** - คะแนนความเสี่ยงรวม

---

## 📊 Arbitrage System

### วิธีการทำงาน

1. **Fetch Prices** - ดึงราคาจากทุก exchanges พร้อมกัน
2. **Compare** - เปรียบเทียบราคา Bid/Ask
3. **Calculate Profit** - คำนวณกำไรหลังหัก:
   - Trading fees (0.2% per trade)
   - Withdrawal/Transfer fees
4. **Execute** - Execute trades อัตโนมัติถ้ากำไร > threshold

### ตั้งค่า Arbitrage Bot

```php
$strategy = TradingStrategy::create([
    'user_id' => Auth::id(),
    'name' => 'BTC Arbitrage Bot',
    'strategy_type' => 'arbitrage',
    'trading_pairs' => ['BTC/USDT', 'BTC/THB'],
    'exchange_ids' => [1, 2, 3], // Binance, Bitkub, KuCoin
    'advanced_settings' => [
        'minimum_profit_percentage' => 0.5, // 0.5% minimum
        'auto_execute_arbitrage' => true,
    ],
]);
```

### Monitoring

```
Admin → Arbitrage Monitor
```

แสดง:
- โอกาสที่พบปัจจุบัน
- Exchange pairs
- Profit percentage
- Buy/Sell prices
- Volume

---

## 🔐 Security

### API Credentials
- API Keys และ Secrets ถูก **encrypt** ด้วย Laravel Crypt
- ใช้ HTTPS สำหรับการเชื่อมต่อทั้งหมด
- Rate limiting ป้องกัน API abuse

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
- **Total Trades** - จำนวนเทรดทั้งหมด
- **Average Profit per Trade**

---

## 🛣️ Routes

### User Routes (Authenticated)
- `GET /trading-bot` - Dashboard
- `GET /trading-bot/marketplace` - Marketplace
- `POST /trading-bot/subscribe/{package}` - Subscribe
- `GET /trading-bot/bots/create` - Create bot form
- `POST /trading-bot/bots` - Store bot
- `GET /trading-bot/bots/{bot}` - Show bot
- `POST /trading-bot/bots/{bot}/start` - Start bot
- `POST /trading-bot/bots/{bot}/stop` - Stop bot
- `GET /trading-bot/accounts` - Trading accounts
- `GET /trading-bot/strategies` - Strategies

### Admin Routes (Admin only)
- `GET /admin/trading-bot/dashboard` - Admin dashboard
- `GET /admin/trading-bot/packages` - Manage packages
- `GET /admin/trading-bot/subscriptions` - Manage subscriptions
- `GET /admin/trading-bot/bots` - Manage all bots
- `GET /admin/trading-bot/exchanges` - Manage exchanges
- `GET /admin/trading-bot/analytics` - Analytics
- `GET /admin/trading-bot/arbitrage-monitor` - Arbitrage monitor
- `GET /admin/trading-bot/settings` - System settings

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
4. สำหรับ KuCoin: ตรวจสอบ API Passphrase

### Arbitrage ไม่พบโอกาส
1. ตรวจสอบว่าเชื่อมต่อหลาย exchanges
2. ลด minimum profit threshold
3. ตรวจสอบ Trading pairs ตรงกันทุก exchange

---

## 📞 Support

- **Documentation**: อ่านเพิ่มเติมใน `TRADING_BOT_SYSTEM_README.md`
- **Issues**: Report ที่ GitHub Issues
- **Email**: support@yourdomain.com

---

## 🎓 ตัวอย่างการใช้งานจริง

### ตัวอย่างที่ 1: Arbitrage Bot (Bitkub ↔ Binance)

```php
// 1. สร้าง Strategy
$strategy = TradingStrategy::create([
    'user_id' => Auth::id(),
    'name' => 'BTC/USDT Arbitrage',
    'strategy_type' => 'arbitrage',
    'trading_pairs' => ['BTC/USDT', 'BTC/THB'],
    'exchange_ids' => [1, 3], // Binance & Bitkub
    'advanced_settings' => [
        'minimum_profit_percentage' => 0.5,
        'auto_execute_arbitrage' => true,
    ],
]);

// 2. สร้าง Bot
$bot = TradingBot::create([
    'user_id' => Auth::id(),
    'subscription_id' => $subscription->id,
    'account_id' => $account->id,
    'strategy_id' => $strategy->id,
    'name' => 'BTC Arb Bot',
    'trading_pair' => 'BTC/USDT',
    'allocated_capital' => 10000,
]);

// 3. Start
app(TradingEngineService::class)->startBot($bot);
```

### ตัวอย่างที่ 2: Enhanced AI Bot

```php
$strategy = TradingStrategy::create([
    'user_id' => Auth::id(),
    'name' => 'ETH Enhanced AI',
    'strategy_type' => 'ai_ml',
    'use_ai' => true,
    'ai_model' => 'lstm',
    'ai_confidence_threshold' => 80.0,
    'indicators' => [
        ['name' => 'rsi', 'params' => ['period' => 14]],
        ['name' => 'macd', 'params' => ['fast' => 12, 'slow' => 26]],
        ['name' => 'ema', 'params' => ['period' => 20]],
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

# 4. เข้าใช้งาน
http://localhost:8000/trading-bot/marketplace
```

**พร้อมเทรดแบบมืออาชีพด้วย AI และ Arbitrage! 🚀💰**

---

## 📝 License

© 2025 Thai Prompt Affiliate. All rights reserved.
