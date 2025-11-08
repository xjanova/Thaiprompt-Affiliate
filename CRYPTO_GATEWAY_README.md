# 🚀 Cryptocurrency Payment Gateway System

ระบบชำระเงินด้วย Cryptocurrency แบบครบวงจร พร้อม Trading Dashboard และ Portfolio Management ระดับ Enterprise

---

## 📋 สารบัญ

1. [คุณสมบัติหลัก](#คุณสมบัติหลัก)
2. [สถาปัตยกรรมระบบ](#สถาปัตยกรรมระบบ)
3. [การติดตั้ง](#การติดตั้ง)
4. [การตั้งค่า](#การตั้งค่า)
5. [การใช้งาน](#การใช้งาน)
6. [Admin Panel](#admin-panel)
7. [API Documentation](#api-documentation)
8. [Security](#security)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 คุณสมบัติหลัก

### ✅ Phase 1: Database & Models
- **6 Database Tables** สำหรับจัดการข้อมูลคริปโต
- **14 Models** พร้อม Relationships ครบถ้วน
- รองรับ Multi-wallet per user
- Transaction tracking และ balance management

### ✅ Phase 2: Controllers & Security
- **6 Form Request Validators** ตรวจสอบข้อมูลอย่างเข้มงวด
- **2 Middleware** (wallet exists, wallet active)
- **5 Service Classes** แยกตาม business logic
- PIN Protection และ 2FA support

### ✅ Phase 3: Web3 Integration
- เชื่อมต่อ **MetaMask** และ External Wallets
- Signature Verification ด้วย Web3
- รองรับ Multi-network (Ethereum, BSC, Polygon)
- Real-time balance checking

### ✅ Phase 4: Premium Trading UI
- **Trading Dashboard** แบบ Real-time
- **Portfolio Analytics** พร้อม Charts
- **Wallet Management** หลายกระเป๋า
- **GSAP Animations** เอฟเฟกต์สวยงาม
- Chart.js visualizations

### ✅ Phase 5: Blockchain Integration
- **BlockchainTransactionService** - ส่งธุรกรรมจริง
- **DepositDetectionService** - ตรวจจับเงินฝากอัตโนมัติ
- **WithdrawalProcessingService** - ประมวลผลการถอน
- Auto-approval based on risk score
- Transaction monitoring

### ✅ Phase 6: Admin Panel & Automation
- **Admin Dashboard** สำหรับจัดการระบบ
- **Withdrawal Approval** System
- **Transaction Monitoring** Real-time
- **Scheduled Tasks** ทำงานอัตโนมัติ
- **Email Notifications** แจ้งเตือนผู้ใช้

---

## 🏗️ สถาปัตยกรรมระบบ

```
┌─────────────────────────────────────────────────────────────┐
│                     User Interface Layer                     │
│  ┌────────────┐  ┌────────────┐  ┌─────────────────────┐   │
│  │  Trading   │  │ Portfolio  │  │ Wallet Management  │   │
│  │ Dashboard  │  │ Analytics  │  │     (Multi)        │   │
│  └────────────┘  └────────────┘  └─────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │  Middleware  │  │Form Requests │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Service Layer                             │
│  ┌──────────────┐  ┌───────────────────┐  ┌──────────────┐ │
│  │CryptoWallet  │  │  Blockchain Tx    │  │  Deposit     │ │
│  │  Service     │  │     Service       │  │  Detection   │ │
│  └──────────────┘  └───────────────────┘  └──────────────┘ │
│  ┌──────────────┐  ┌───────────────────┐  ┌──────────────┐ │
│  │ Withdrawal   │  │   Web3 Service    │  │    Price     │ │
│  │ Processing   │  │                   │  │   Service    │ │
│  └──────────────┘  └───────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Blockchain Layer                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Ethereum    │  │    BSC       │  │   Polygon    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 การติดตั้ง

### 1. Dependencies

```bash
# PHP Dependencies
composer require web3p/web3.php phpseclib kornrunner/keccak

# JavaScript Dependencies
npm install ethers@^5.8.0 @web3modal/wagmi @wagmi/core viem chart.js gsap
```

### 2. Database Migration

```bash
php artisan migrate
```

### 3. Seed Cryptocurrencies

```bash
php artisan db:seed --class=CryptoCurrencySeeder
```

### 4. Build Assets

```bash
npm run build
```

### 5. Schedule Tasks (Cron)

เพิ่มในserver crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## ⚙️ การตั้งค่า

### Environment Variables

เพิ่มใน `.env`:

```env
# Crypto Payment Gateway
CRYPTO_AUTO_APPROVE_ENABLED=true
CRYPTO_MAX_AUTO_APPROVE_AMOUNT=100000

# Blockchain RPC URLs
ETHEREUM_RPC_URL=https://ethereum.publicnode.com
BSC_RPC_URL=https://bsc-dataseed.binance.org
POLYGON_RPC_URL=https://polygon-rpc.com

# Confirmations Required
ETHEREUM_CONFIRMATIONS=12
BSC_CONFIRMATIONS=15
POLYGON_CONFIRMATIONS=128

# Scanning Intervals (seconds)
CRYPTO_DEPOSIT_SCAN_INTERVAL=30
CRYPTO_WITHDRAWAL_PROCESS_INTERVAL=60

# Trading Settings
CRYPTO_TRADING_ENABLED=true
CRYPTO_MIN_TRADE_AMOUNT=100
CRYPTO_MAX_TRADE_AMOUNT=10000000

# Security
CRYPTO_REQUIRE_KYC=true
CRYPTO_REQUIRE_2FA=false
CRYPTO_MAX_DAILY_WITHDRAWAL=1000000

# Price Provider
CRYPTO_PRICE_PROVIDER=coingecko
COINGECKO_API_KEY=your_api_key_here
```

### Config File

Configuration file: `config/crypto.php`

---

## 💻 การใช้งาน

### สำหรับผู้ใช้ (User)

#### 1. สร้างกระเป๋าคริปโต

```
เข้าสู่ระบบ → Crypto Wallet → สร้างกระเป๋า
เลือก: Custodial (ระบบจัดการให้) หรือ External (MetaMask)
```

#### 2. ฝากเงิน

```
Crypto Wallet → ฝากเหรียญ → เลือกสกุล
คัดลอกที่อยู่ → โอนจากกระเป๋าภายนอก
รอ Confirmations (12-128 blocks)
```

#### 3. ถอนเงิน

```
Crypto Wallet → ถอนเหรียญ
กรอกที่อยู่ปลายทาง + จำนวน
ยืนยัน PIN
รอการอนุมัติ (auto หรือ manual)
```

#### 4. Trading

```
Crypto Wallet → เทรดดิ้ง
เลือกคู่เทรด (เช่น BTC/THB)
กรอกจำนวน → Buy/Sell
ยืนยันธุรกรรม
```

#### 5. Portfolio

```
Crypto Wallet → พอร์ตโฟลิโอ
ดู Asset Allocation
ดู Performance Chart
ติดตาม P&L
```

---

## 🛠️ Admin Panel

### การเข้าถึง

```
URL: /admin/crypto/dashboard
```

### Features

#### 1. Dashboard
- สถิติรวมทั้งระบบ
- Pending Withdrawals
- Recent Transactions
- Volume Charts

#### 2. Withdrawal Management
```
/admin/crypto/withdrawals

ฟีเจอร์:
- อนุมัติ/ปฏิเสธการถอนเงิน
- ดู Risk Score
- Filter by status/currency
- Bulk operations
```

#### 3. Transaction Monitor
```
/admin/crypto/transactions

ฟีเจอร์:
- ดูธุรกรรมทั้งหมด
- Filter by type/status
- Search by TX Hash
- Export to CSV
```

#### 4. Wallet Management
```
/admin/crypto/wallets

ฟีเจอร์:
- ดูกระเป๋าทั้งหมด
- Lock/Unlock wallets
- View balances
- User details
```

#### 5. Currency Settings
```
/admin/crypto/currencies

ฟีเจอร์:
- Enable/Disable currencies
- Set min/max limits
- Configure fees
- Update RPC URLs
```

#### 6. Manual Operations
```
Scan Deposits (Manual):
POST /admin/crypto/scan-deposits

Process Withdrawals (Manual):
POST /admin/crypto/process-withdrawals
```

---

## 📡 API Documentation

### User API

#### Get Prices
```http
GET /user/crypto-wallet/prices

Response:
{
  "BTC": {
    "code": "BTC",
    "price_thb": 2500000,
    "change_24h": 5.2
  }
}
```

#### Get Balances
```http
GET /api/v1/crypto/balances

Headers:
Authorization: Bearer {token}

Response:
[
  {
    "currency": "BTC",
    "balance": 0.05,
    "balance_thb": 125000
  }
]
```

#### Generate Nonce
```http
POST /api/crypto/generate-nonce

Body:
{
  "address": "0x..."
}

Response:
{
  "nonce": "...",
  "message": "Sign this message..."
}
```

#### Verify Signature
```http
POST /api/v1/crypto/verify-signature

Body:
{
  "address": "0x...",
  "signature": "0x...",
  "nonce": "..."
}
```

---

## 🔒 Security

### ความปลอดภัยหลายชั้น

1. **PIN Protection** - กำหนด PIN 6 หลัก
2. **2FA Support** - Two-Factor Authentication
3. **KYC Verification** - ตรวจสอบตัวตนก่อนถอน
4. **Risk Scoring** - คำนวณความเสี่ยง
5. **Balance Locking** - ล็อคยอดระหว่างประมวลผล
6. **Nonce Verification** - ป้องกัน Replay Attack

### Private Key Management

```
⚠️ IMPORTANT:
- Private keys เข้ารหัสด้วย Laravel Encryption
- ควรใช้ HSM หรือ KMS ในโปรดักชั่น
- อย่าเก็บ Private Key แบบ Plain Text
```

### Rate Limiting

```php
// กำหนดใน Middleware
'throttle:60,1' // 60 requests per minute
```

---

## 🐛 Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. Deposit ไม่เข้า

**Solution:**
```bash
# ตรวจสอบ Scheduled Tasks
php artisan crypto:scan-deposits

# เช็ค Logs
tail -f storage/logs/laravel.log | grep crypto
```

#### 2. Withdrawal ค้าง

**Solution:**
```bash
# รัน Manual Processing
php artisan crypto:process-withdrawals

# เช็คสถานะ
php artisan tinker
> App\Models\CryptoWithdrawalRequest::find($id)
```

#### 3. Gas Fee สูงเกินไป

**Solution:**
```
1. ตรวจสอบ Network Congestion
2. รอ Gas Price ลดลง
3. ปรับ Gas Limit ใน Config
```

#### 4. Transaction Failed

**Solution:**
```bash
# เช็ค TX Hash บน Explorer
https://etherscan.io/tx/{tx_hash}

# ดู Error Message
php artisan tinker
> App\Models\CryptoTransaction::where('tx_hash', '...')->first()
```

---

## 📊 Monitoring

### Logs

```bash
# Deposit Scanning
tail -f storage/logs/laravel.log | grep "Crypto deposit scan"

# Withdrawal Processing
tail -f storage/logs/laravel.log | grep "Crypto withdrawal processing"

# Errors
tail -f storage/logs/laravel.log | grep ERROR
```

### Database Queries

```sql
-- Pending Deposits
SELECT * FROM crypto_transactions
WHERE type = 'deposit' AND status = 'pending';

-- Pending Withdrawals
SELECT * FROM crypto_withdrawal_requests
WHERE status IN ('pending', 'reviewing');

-- Total Volume 24h
SELECT SUM(amount_thb) FROM crypto_transactions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND status = 'confirmed';
```

---

## 📈 Performance Optimization

### Caching

```php
// Price caching (60 seconds)
Cache::remember('crypto_price_BTC', 60, function() {
    return $priceService->getCurrentRate($btc);
});

// Balance caching (30 seconds)
Cache::remember("wallet_balance_{$wallet->id}", 30, function() {
    return $walletService->getAllBalances($wallet);
});
```

### Queue Jobs

```php
// ใส่งานหนักเข้า Queue
dispatch(new ProcessCryptoDeposit($transaction));
```

### Database Indexing

```sql
-- สำคัญมาก!
CREATE INDEX idx_tx_hash ON crypto_transactions(tx_hash);
CREATE INDEX idx_status ON crypto_transactions(status);
CREATE INDEX idx_user_wallet ON crypto_wallets(user_id);
```

---

## 🔄 Update & Maintenance

### การอัปเดตระบบ

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:clear

# Rebuild assets
npm run build
```

---

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:

- **GitHub Issues**: [Create Issue](https://github.com/yourrepo/issues)
- **Email**: support@yoursite.com
- **Documentation**: [Full Docs](https://docs.yoursite.com)

---

## 📝 License

This project is proprietary software. All rights reserved.

---

## 🙏 Credits

Developed with ❤️ using:
- Laravel 11
- Web3.php
- Ethers.js
- Chart.js
- GSAP
- Alpine.js
- Tailwind CSS

---

**Version**: 1.0.0
**Last Updated**: 2025-01-08
**Status**: Production Ready ✅
