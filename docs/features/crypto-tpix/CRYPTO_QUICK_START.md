# ⚡ Cryptocurrency Gateway - Quick Start Guide

## 🚀 เริ่มใช้งานใน 5 นาที

### 1. ตั้งค่า Environment (.env)

```env
# Blockchain RPC URLs
ETHEREUM_RPC_URL=https://ethereum.publicnode.com
BSC_RPC_URL=https://bsc-dataseed.binance.org
POLYGON_RPC_URL=https://polygon-rpc.com

# Blockchain Explorer API Keys (สำคัญ!)
ETHERSCAN_API_KEY=your_etherscan_api_key_here
BSCSCAN_API_KEY=your_bscscan_api_key_here
POLYGONSCAN_API_KEY=your_polygonscan_api_key_here
```

**สมัคร API Keys ฟรี:**
- Etherscan: https://etherscan.io/apis
- BSCScan: https://bscscan.com/apis
- PolygonScan: https://polygonscan.com/apis

### 2. ทดสอบการเชื่อมต่อ

```bash
php artisan crypto:test-connections --detail
```

**ถ้าผลลัพธ์แสดง ✅ ทุกอัน = พร้อมใช้งาน!**

### 3. ทดสอบระบบ

```bash
php artisan crypto:health-check
```

### 4. ดูกราฟราคา

เปิดเว็บบราวเซอร์:
```
http://localhost/crypto/charts
```

---

## 📱 ใช้งาน Withdrawal (Client-Side Signing)

### Backend API (ตัวอย่าง)

```php
use App\Services\Crypto\ClientSideTransactionService;

// Prepare transaction
Route::post('/api/crypto/withdraw/prepare', function(Request $request) {
    $service = app(ClientSideTransactionService::class);

    return $service->prepareTransaction(
        wallet: auth()->user()->cryptoWallet,
        toAddress: $request->to_address,
        amount: $request->amount,
        currency: CryptoCurrency::find($request->currency_id),
        network: 'ethereum'
    );
});

// Broadcast signed transaction
Route::post('/api/crypto/withdraw/broadcast', function(Request $request) {
    $service = app(ClientSideTransactionService::class);

    return $service->broadcastTransaction(
        transactionId: $request->transaction_id,
        signedTx: $request->signed_tx,
        network: 'ethereum'
    );
});
```

### Frontend (JavaScript + MetaMask)

```javascript
// 1. Prepare transaction
const prepareRes = await fetch('/api/crypto/withdraw/prepare', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        to_address: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
        amount: 0.1,
        currency_id: 1
    })
});

const { transaction_data, transaction_id } = await prepareRes.json();

// 2. Sign with MetaMask
const signedTx = await ethereum.request({
    method: 'eth_sendTransaction',
    params: [transaction_data]
});

// 3. Broadcast to blockchain
const broadcastRes = await fetch('/api/crypto/withdraw/broadcast', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ transaction_id, signed_tx: signedTx })
});

const result = await broadcastRes.json();
console.log('✅ Transaction Hash:', result.tx_hash);
console.log('🔍 Explorer:', result.explorer_url);
```

---

## 📊 ใช้งาน Price Charts API

```javascript
// Get chart data
fetch('/api/crypto/chart/1?period=24h')
    .then(r => r.json())
    .then(data => {
        console.log('Current Price:', data.statistics.current);
        console.log('24h Change:', data.statistics.change + '%');
        console.log('Chart Data:', data.data);
    });

// Compare multiple currencies
fetch('/api/crypto/compare?currencies=1,2,3&period=24h')
    .then(r => r.json())
    .then(data => {
        data.currencies.forEach(currency => {
            console.log(currency.code, ':', currency.data);
        });
    });

// Market overview
fetch('/api/crypto/market-overview')
    .then(r => r.json())
    .then(data => {
        console.log('Total Market Cap:', data.overview.total_market_cap);
        console.log('Top Gainers:', data.top_gainers);
        console.log('Top Losers:', data.top_losers);
    });
```

---

## 🔧 Commands ที่มีให้ใช้

```bash
# ทดสอบการเชื่อมต่อ
php artisan crypto:test-connections
php artisan crypto:test-connections --network=ethereum
php artisan crypto:test-connections --detail

# ตรวจสุขภาพระบบ
php artisan crypto:health-check
php artisan crypto:health-check --fix

# Scan deposits (production)
php artisan crypto:scan-deposits
php artisan crypto:scan-deposits --continuous

# Process withdrawals (production)
php artisan crypto:process-withdrawals
php artisan crypto:process-withdrawals --continuous
```

---

## 🎯 Routes ที่สำคัญ

**Web:**
- `/crypto/charts` - กราฟราคาและการเปรียบเทียบ

**API:**
- `GET /api/crypto/chart/{id}?period=24h` - ข้อมูลกราฟ
- `GET /api/crypto/compare?currencies=1,2,3&period=24h` - เปรียบเทียบ
- `GET /api/crypto/market-overview` - ภาพรวมตลาด
- `GET /api/crypto/realtime-prices?currencies=1,2,3` - ราคาเรียลไทม์

---

## 🔒 ความปลอดภัย (Client-Side Signing)

**✅ ปลอดภัย:**
- Private keys ไม่เคยเข้าเซิร์ฟเวอร์
- ผู้ใช้ควบคุม keys เอง (ผ่าน MetaMask)
- เป็นมาตรฐานอุตสาหกรรม (Uniswap, Aave ใช้แบบเดียวกัน)

**⚠️ ข้อควรระวัง:**
- ผู้ใช้ต้องสำรอง seed phrase
- ถ้าหาย private key = เงินหาย (ไม่มีทางกู้คืน)
- ต้องมี MetaMask หรือ wallet app

---

## 📚 เอกสารเพิ่มเติม

1. **CRYPTO_GATEWAY_README.md** - คู่มือผู้ใช้ฉบับเต็ม
2. **CRYPTO_PRODUCTION_DEPLOYMENT.md** - Production deployment guide
3. **CRYPTO_LIBRARIES_AND_FEATURES.md** - รายละเอียด libraries และ features
4. **CRYPTO_IMPROVEMENTS_SUMMARY.md** - สรุปการปรับปรุงทั้งหมด

---

## ❓ Troubleshooting

**RPC Connection Failed?**
```env
# ใช้ dedicated RPC จาก Infura, Alchemy, QuickNode
ETHEREUM_RPC_URL=https://mainnet.infura.io/v3/YOUR_INFURA_KEY
```

**Indexer API Failed?**
```env
# ตรวจสอบว่าใส่ API key แล้ว
ETHERSCAN_API_KEY=your_actual_api_key_here
```

**Database Connection Refused?**
```bash
# ตรวจสอบว่า MySQL/PostgreSQL ทำงานอยู่
sudo service mysql status
```

**Cache Not Working?**
```bash
# ตรวจสอบว่า Redis ทำงานอยู่
sudo service redis status
```

---

**Ready to go! 🚀**

ตอนนี้คุณมีระบบ Cryptocurrency Payment Gateway ที่:
- ✅ ใช้งานได้จริง
- ✅ ปลอดภัย (Client-Side Signing)
- ✅ รองรับหลาย chain (Ethereum, BSC, Polygon)
- ✅ มีกราฟและ analytics
- ✅ พร้อม production

**Next Steps:**
1. เพิ่ม MetaMask integration ใน Frontend
2. ทดสอบบน Testnet (Goerli, BSC Testnet)
3. Deploy to Production
4. Monitor และปรับปรุงต่อไป

---

**Version:** 2.0.0
**Last Updated:** 2025-01-08
