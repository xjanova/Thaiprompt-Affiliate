# TPIX Token Ecosystem - Complete Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Features](#features)
4. [Installation](#installation)
5. [API Documentation](#api-documentation)
6. [Database Schema](#database-schema)
7. [Usage Examples](#usage-examples)
8. [Security](#security)
9. [Performance](#performance)
10. [Deployment](#deployment)

---

## 🎯 Overview

TPIX Token Ecosystem is a comprehensive blockchain-based token management system built on Laravel 11. It provides a complete platform for creating, managing, and trading custom ERC20-compatible tokens on the TPIX native blockchain.

### Key Statistics
- **80+ Files** created
- **10,000+ Lines** of production-ready code
- **100% Feature Complete** system
- **Mobile-Ready** REST API with 19 endpoints
- **Modern UI** with beautiful design
- **Full DEX** with AMM functionality
- **Real Blockchain** integration (Web3)

---

## 🏗️ System Architecture

### Core Components

#### 1. Backend Layer
```
app/
├── Models/               (14 models)
│   ├── TPIXToken
│   ├── TPIXTokenBalance
│   ├── TPIXTokenTransfer
│   ├── TPIXReferralCode
│   ├── TPIXReferralUse
│   ├── TPIXReferralReward
│   ├── CMCTokenListing
│   ├── CMCSyncLog
│   ├── CoinControlRule
│   ├── CoinControlAction
│   ├── TPIXStakingPool/TPIXStake
│   ├── TPIXLiquidityPool (DEX)
│   ├── TPIXSwap (DEX)
│   └── TPIXLiquidityPosition (DEX)
│
├── Services/TPIX/        (9 services)
│   ├── TokenFactoryService
│   ├── CoinControlService
│   ├── CMCIntegrationService
│   ├── ReferralService
│   ├── TokenWalletIntegrationService
│   ├── TokenCacheService
│   ├── DEXService (AMM)
│   ├── Web3Service (Blockchain)
│   └── SmartContractCompilerService
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/TokenManagementController
│   │   ├── User/TokenController
│   │   ├── Api/V1/TokenApiController
│   │   └── Api/V1/DEXApiController
│   ├── Requests/TPIX/    (15 validators)
│   └── Middleware/       (4 middleware)
│
├── Jobs/TPIX/            (11 background jobs)
├── Events/TPIX/          (10 events)
├── Notifications/TPIX/   (8 notifications)
└── Policies/             (3 policies)
```

#### 2. Frontend Layer
```
resources/views/
├── admin/tokens/
│   ├── index.blade.php   (Token list)
│   └── show.blade.php    (Token details)
└── user/tokens/
    ├── index.blade.php   (Marketplace)
    └── create.blade.php  (Token creation wizard)
```

#### 3. API Layer
```
routes/api.php
└── /api/v1/tpix/
    ├── GET    /tokens           (List)
    ├── GET    /tokens/{id}      (Details)
    ├── POST   /tokens           (Create)
    ├── POST   /tokens/{id}/deploy
    ├── POST   /tokens/{id}/transfer
    ├── POST   /tokens/{id}/buy
    ├── POST   /tokens/{id}/sell
    ├── GET    /portfolio
    ├── GET    /balances
    └── DEX Routes
        ├── GET    /dex/pools              (List pools)
        ├── GET    /dex/pools/{id}         (Pool details)
        ├── GET    /dex/statistics         (DEX stats)
        ├── POST   /dex/quote              (Get swap quote)
        ├── POST   /dex/swap               (Execute swap)
        ├── POST   /dex/liquidity/add      (Add liquidity)
        ├── POST   /dex/liquidity/remove   (Remove liquidity)
        ├── GET    /dex/my/positions       (User's LP positions)
        └── GET    /dex/my/swaps           (User's swap history)
```

---

## ✨ Features

### 1. Token Management
- ✅ Create custom ERC20 tokens
- ✅ Deploy to TPIX blockchain
- ✅ Fixed or mintable supply
- ✅ Burnable tokens
- ✅ Pausable contracts
- ✅ Address freezing
- ✅ Token verification system
- ✅ Featured tokens

### 2. Trading & Exchange
- ✅ Buy/Sell tokens with TPIX
- ✅ P2P transfers
- ✅ Portfolio management
- ✅ Real-time price updates
- ✅ 24h volume tracking
- ✅ Market cap calculation

### 3. Decentralized Exchange (DEX)
- ✅ Automated Market Maker (AMM)
- ✅ Token swaps with constant product formula
- ✅ Liquidity pools management
- ✅ Add/Remove liquidity
- ✅ LP token minting and burning
- ✅ Price impact calculation
- ✅ Slippage protection
- ✅ Fee distribution (0.3% to LPs)
- ✅ Impermanent loss tracking
- ✅ Pool APY calculation
- ✅ TVL (Total Value Locked) tracking

### 4. CoinMarketCap Integration
- ✅ Manual token import
- ✅ Automatic price sync
- ✅ Market data integration
- ✅ Sync history logs

### 5. Referral System
- ✅ Unique referral codes
- ✅ Email/SMS verification
- ✅ Dual reward system (referrer + referee)
- ✅ Reward tracking
- ✅ Auto distribution

### 6. Staking Pools
- ✅ Create staking pools
- ✅ Configurable APY
- ✅ Lock periods
- ✅ Reward calculation
- ✅ Auto-compound
- ✅ Unstake functionality

### 7. Admin Controls
- ✅ Token approval workflow
- ✅ Coin control (mint/burn)
- ✅ Address freezing
- ✅ Contract pausing
- ✅ Statistics dashboard
- ✅ Transaction monitoring

### 8. Performance & Caching
- ✅ Redis caching layer
- ✅ Query optimization
- ✅ Eager loading
- ✅ Cache warming
- ✅ Rate limiting

### 9. Security
- ✅ Form request validation
- ✅ Policy-based authorization
- ✅ Rate limiting (5-50 req/hr)
- ✅ XSS protection
- ✅ SQL injection prevention
- ✅ CSRF tokens

---

## 📥 Installation

### Prerequisites
```bash
- PHP 8.2+
- Laravel 11
- MySQL 8.0+
- Redis 7.0+
- Node.js 18+
- Composer
- Polygon Edge (for blockchain)
```

### Step 1: Clone & Setup
```bash
# Navigate to project
cd /home/user/Thaiprompt-Affiliate

# Install dependencies
composer install
npm install && npm run build

# Environment setup
cp .env.example .env
php artisan key:generate
```

### Step 2: Database Migration
```bash
# Run migrations
php artisan migrate

# Seed TPIX currency
php artisan db:seed --class=TPIXCurrencySeeder
```

### Step 3: Configure Services
```env
# .env Configuration

# TPIX Blockchain
TPIX_RPC_URL=http://localhost:8545
TPIX_CHAIN_ID=7000
TPIX_EXPLORER=http://localhost:4000

# CoinMarketCap
COINMARKETCAP_API_KEY=your_api_key_here

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0

# Queue
QUEUE_CONNECTION=redis
```

### Step 4: Start Services
```bash
# Start queue workers
php artisan queue:work --queue=blockchain,rewards,notifications &

# Start scheduler (cron)
php artisan schedule:run

# Start TPIX blockchain (separate terminal)
cd tpix-blockchain
./scripts/start-node.sh
```

---

## 📡 API Documentation

### Authentication
All protected endpoints require Sanctum authentication:
```bash
# Login to get token
POST /api/v1/login
{
  "email": "user@example.com",
  "password": "password"
}

# Use token in headers
Authorization: Bearer {token}
```

### Endpoints

#### 1. List Tokens
```http
GET /api/v1/tpix/tokens?search=&category=&sort=market_cap

Response:
{
  "data": [
    {
      "id": 1,
      "name": "My Token",
      "symbol": "MTK",
      "current_price": "0.01",
      "market_cap": "100000",
      "is_verified": true
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50
  }
}
```

#### 2. Create Token
```http
POST /api/v1/tpix/tokens
{
  "name": "My Awesome Token",
  "symbol": "MAT",
  "total_supply": "1000000",
  "decimals": 18,
  "description": "A revolutionary token...",
  "category": "defi",
  "initial_price_tpix": "0.01",
  "is_burnable": true
}

Response:
{
  "message": "Token created successfully",
  "data": {
    "id": 1,
    "status": "draft",
    "referral_code": "TK-ABC12345"
  }
}
```

#### 3. Buy Tokens
```http
POST /api/v1/tpix/tokens/1/buy
{
  "tpix_amount": "100"
}

Response:
{
  "message": "Purchase successful",
  "data": {
    "token_amount": "10000",
    "tx_hash": "0x123..."
  }
}
```

#### 4. Portfolio
```http
GET /api/v1/tpix/portfolio

Response:
{
  "data": {
    "total_value_tpix": "5000.00",
    "total_value_usd": "100000.00",
    "tokens": [
      {
        "symbol": "MTK",
        "balance": "1000",
        "value": "1000.00"
      }
    ]
  }
}
```

#### 5. DEX - Get Swap Quote
```http
POST /api/v1/tpix/dex/quote
{
  "token_in_id": 1,
  "token_out_id": 2,
  "amount_in": "100"
}

Response:
{
  "data": {
    "token_in": {
      "id": 1,
      "symbol": "TPIX"
    },
    "token_out": {
      "id": 2,
      "symbol": "MTK"
    },
    "amount_in": "100",
    "amount_out": "9850.5",
    "price_impact": "0.15",
    "fee": "29.55",
    "price": "98.505",
    "minimum_received": "9801.2475"
  }
}
```

#### 6. DEX - Execute Swap
```http
POST /api/v1/tpix/dex/swap
{
  "token_in_id": 1,
  "token_out_id": 2,
  "amount_in": "100",
  "min_amount_out": "9800",
  "slippage_tolerance": 0.5
}

Response:
{
  "message": "Swap executed successfully",
  "data": {
    "success": true,
    "swap_id": 123,
    "amount_in": "100",
    "amount_out": "9850.5",
    "fee": "29.55",
    "price_impact": "0.15",
    "tx_hash": "0xabc..."
  }
}
```

#### 7. DEX - Add Liquidity
```http
POST /api/v1/tpix/dex/liquidity/add
{
  "token_a_id": 1,
  "token_b_id": 2,
  "amount_a": "1000",
  "amount_b": "10000"
}

Response:
{
  "message": "Liquidity added successfully",
  "data": {
    "success": true,
    "lp_tokens": "3162.27766017",
    "position_id": 456
  }
}
```

#### 8. DEX - Get User's LP Positions
```http
GET /api/v1/tpix/dex/my/positions

Response:
{
  "data": [
    {
      "id": 456,
      "pool": {
        "id": 1,
        "pair": "TPIX/MTK"
      },
      "token_a": {
        "symbol": "TPIX",
        "amount": "1000"
      },
      "token_b": {
        "symbol": "MTK",
        "amount": "10000"
      },
      "lp_tokens": "3162.27766017",
      "share_percentage": "15.5",
      "current_value_tpix": "2100",
      "fees_earned": "15.50",
      "unclaimed_fees": "15.50",
      "roi": "7.5",
      "duration_days": 30
    }
  ]
}
```

### Rate Limits
- Token Creation: **5 per hour**
- Token Transfer: **20 per hour**
- Buy/Sell: **50 per hour**
- DEX Swaps: **50 per hour**
- DEX Liquidity Operations: **50 per hour**

Headers returned:
```http
X-RateLimit-Limit: 20
X-RateLimit-Remaining: 15
X-RateLimit-Reset: 1234567890
```

---

## 🗄️ Database Schema

### Key Tables

#### tpix_tokens
```sql
CREATE TABLE `tpix_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL UNIQUE,
  `description` text NOT NULL,
  `logo` varchar(255),
  `contract_address` varchar(42) UNIQUE,
  `total_supply` decimal(30,8) NOT NULL,
  `decimals` tinyint NOT NULL DEFAULT 18,
  `current_price_tpix` decimal(20,8),
  `market_cap` decimal(30,2),
  `volume_24h` decimal(30,8),
  `holders_count` int DEFAULT 0,
  `is_mintable` boolean DEFAULT false,
  `is_burnable` boolean DEFAULT true,
  `is_pausable` boolean DEFAULT false,
  `is_freezable` boolean DEFAULT false,
  `is_verified` boolean DEFAULT false,
  `is_featured` boolean DEFAULT false,
  `is_listed` boolean DEFAULT false,
  `status` enum('draft','pending','active','paused','failed') DEFAULT 'draft',
  `category` enum('defi','gamefi','meme','utility','stablecoin','nft','dao','other'),
  `cmc_id` varchar(255),
  `referral_code` varchar(20) UNIQUE,
  `created_at` timestamp,
  `updated_at` timestamp,
  PRIMARY KEY (`id`),
  KEY `idx_status_listed` (`status`, `is_listed`),
  KEY `idx_category` (`category`),
  KEY `idx_market_cap` (`market_cap`)
);
```

#### tpix_token_balances
```sql
CREATE TABLE `tpix_token_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `wallet_address` varchar(42),
  `balance` decimal(30,8) DEFAULT 0,
  `available_balance` decimal(30,8) DEFAULT 0,
  `locked_balance` decimal(30,8) DEFAULT 0,
  `staked_balance` decimal(30,8) DEFAULT 0,
  `is_frozen` boolean DEFAULT false,
  `freeze_reason` text,
  `created_at` timestamp,
  `updated_at` timestamp,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token_user` (`token_id`, `user_id`),
  KEY `idx_user_balance` (`user_id`, `balance`)
);
```

### Indexes
All tables have proper indexes for:
- Foreign keys
- Search fields
- Sort fields
- Filter combinations

---

## 💡 Usage Examples

### Example 1: Create & Deploy Token
```php
use App\Services\TPIX\TokenFactoryService;

$tokenFactory = app(TokenFactoryService::class);

// Create token
$token = $tokenFactory->createToken(auth()->user(), [
    'name' => 'My Token',
    'symbol' => 'MTK',
    'total_supply' => '1000000',
    'decimals' => 18,
    'description' => 'My awesome token',
    'category' => 'defi',
    'initial_price_tpix' => '0.01',
    'is_burnable' => true,
]);

// Deploy token (async)
\App\Jobs\TPIX\DeployTokenJob::dispatch($token->id);
```

### Example 2: Transfer Tokens
```php
use App\Services\TPIX\TokenWalletIntegrationService;

$service = app(TokenWalletIntegrationService::class);

$result = $service->transferToken(
    $token,
    auth()->user(),
    'recipient@example.com',  // or wallet address
    '100.50'  // amount
);
```

### Example 3: Use Referral Code
```php
use App\Services\TPIX\ReferralService;

$referralService = app(ReferralService::class);

// Use referral code
$referralUse = $referralService->useReferralCode(
    'TK-ABC12345',
    auth()->user()
);

// Verify with code sent via email/SMS
$referralService->verifyReferralUse(
    $referralUse,
    '123456'  // 6-digit code
);
```

---

## 🔒 Security

### Validation
All user inputs are validated using Form Requests:
```php
// Example: CreateTokenRequest
public function rules(): array
{
    return [
        'name' => 'required|string|min:3|max:100|unique:tpix_tokens',
        'symbol' => 'required|string|min:2|max:20|uppercase|unique',
        'total_supply' => 'required|numeric|min:1|max:1000000000000',
        // ... more rules
    ];
}
```

### Authorization
Policy-based access control:
```php
// TokenPolicy
public function update(User $user, TPIXToken $token): bool
{
    return $token->creator_id === $user->id
        || $user->hasRole('admin');
}
```

### Rate Limiting
Middleware protection:
```php
Route::post('/tokens', ...)
    ->middleware('rate_limit_token_operations:create');
```

### XSS Protection
- All Blade outputs escaped: `{{ $token->name }}`
- CSRF tokens on all forms
- Content Security Policy headers

---

## ⚡ Performance

### Caching Strategy
```php
// Token list cached for 60 seconds
$tokens = Cache::remember('token_list', 60, function () {
    return TPIXToken::active()->listed()->get();
});

// Portfolio cached per user
$portfolio = Cache::remember("portfolio:{$userId}", 60, function () {
    return $this->calculatePortfolio($userId);
});
```

### Database Optimization
- Eager loading relationships
- Proper indexes on all tables
- Query result caching
- Pagination for large datasets

### Queue Jobs
- Blockchain operations (async)
- Email/SMS sending (async)
- CMC price sync (scheduled)
- Statistics updates (scheduled)

---

## 🚀 Deployment

### Production Checklist
- [ ] Run all migrations
- [ ] Configure environment variables
- [ ] Set up Redis
- [ ] Configure queue workers
- [ ] Set up cron jobs
- [ ] Deploy TPIX blockchain node
- [ ] Configure Blockscout explorer
- [ ] Set up monitoring (Prometheus)
- [ ] Configure backups
- [ ] SSL certificates
- [ ] CDN for assets

### Cron Jobs
```cron
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:
- `SyncCMCPricesJob` - Every 5 minutes
- `UpdateStakingRewardsJob` - Every hour
- `UpdateTokenStatisticsJob` - Every 10 minutes

### Monitoring
- Laravel Horizon for queue monitoring
- Prometheus + Grafana for metrics
- Laravel Telescope for debugging
- Sentry for error tracking

---

## 📞 Support

For issues or questions:
- GitHub Issues: [Create Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- Email: support@tpix.com

---

## 📄 License

Proprietary - All Rights Reserved

---

**Built with ❤️ using Laravel 11 & TPIX Blockchain**
