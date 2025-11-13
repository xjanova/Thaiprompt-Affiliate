# 💎 TPIX Blockchain - Native Cryptocurrency System

> **ระบบเหรียญคริปโต TPIX ที่มี Blockchain ของตัวเอง สำหรับระบบ Thaiprompt Affiliate**

---

## 📋 สารบัญ

1. [ภาพรวม](#-ภาพรวม)
2. [คุณสมบัติหลัก](#-คุณสมบัติหลัก)
3. [สถาปัตยกรรม](#-สถาปัตยกรรม)
4. [รายละเอียดทางเทคนิค](#-รายละเอียดทางเทคนิค)
5. [การติดตั้งและใช้งาน](#-การติดตั้งและใช้งาน)
6. [API Integration](#-api-integration)
7. [Use Cases](#-use-cases)
8. [ความปลอดภัย](#-ความปลอดภัย)
9. [FAQ](#-คำถามที่พบบ่อย)

---

## 🌟 ภาพรวม

**TPIX (Thaiprompt Affiliate Blockchain)** เป็นระบบ blockchain ที่พัฒนาขึ้นเฉพาะสำหรับระบบนิเวศน์ของ Thaiprompt Affiliate โดยมีเหรียญ **TPIX** เป็น **Native Coin** หลักของระบบ

### ความแตกต่างจาก Crypto อื่น ๆ

<table>
<tr>
<th width="33%">Token บน Ethereum/BSC</th>
<th width="33%">TPIX Native Coin</th>
<th width="33%">ข้อดี</th>
</tr>
<tr>
<td>
❌ ต้องใช้ ETH/BNB เป็นค่าแก๊ส<br>
❌ ไม่สามารถควบคุมค่าธรรมเนียม<br>
❌ ขึ้นอยู่กับเครือข่ายอื่น<br>
❌ ไม่สามารถปรับแต่ง consensus
</td>
<td>
✅ ใช้ TPIX เป็นค่าแก๊ส<br>
✅ ควบคุมค่าธรรมเนียมได้เต็มที่<br>
✅ Blockchain ของตัวเอง<br>
✅ ปรับแต่งได้ทุกอย่าง
</td>
<td>
💰 ประหยัดค่าใช้จ่าย<br>
⚡ รวดเร็วกว่า (2 วินาที/block)<br>
🔒 ปลอดภัยและเป็นอิสระ<br>
🎯 ควบคุมได้เต็มรูปแบบ
</td>
</tr>
</table>

---

## ✨ คุณสมบัติหลัก

### 1. 🪙 Native Cryptocurrency

- **TPIX** เป็นเหรียญหลักของ blockchain
- ใช้ชำระค่าธรรมเนียม (gas fees) ภายใน network
- ไม่ต้องพึ่งพา cryptocurrency อื่น

### 2. 💰 Fixed Supply (จำกัดจำนวน)

```
Total Supply: 7,000,000,000 TPIX
├─ Genesis Block: 7,000,000,000 TPIX
├─ Mining: ไม่มี (Pre-mined 100%)
└─ Inflation: 0% (ไม่สามารถสร้างเพิ่มได้)
```

### 3. ⚡ Lightning Fast

- **Block Time**: 2 วินาที
- **Transaction Finality**: ~10 วินาที (5 blocks)
- **TPS**: ~1,500 transactions/second
- **RPC Response**: <50ms

### 4. 🛠️ EVM-Compatible

- รองรับ **Solidity** smart contracts
- ใช้ **Remix IDE**, **Hardhat**, **Truffle** ได้
- รองรับ **ERC20**, **ERC721**, **ERC1155** token standards
- เข้ากันได้กับเครื่องมือ Ethereum ทั้งหมด

### 5. 🔒 Secure Consensus

- **IBFT (Istanbul Byzantine Fault Tolerant)**
- Proof of Stake based
- Byzantine Fault Tolerant (ทนต่อ Byzantine failure ได้)
- Validator-based security model

### 6. 🌐 Full Integration

- ✅ **PHP Laravel Service** - Backend integration
- ✅ **REST API** - HTTP endpoints
- ✅ **WebSocket** - Real-time updates
- ✅ **Block Explorer** - Transaction tracking
- ✅ **MetaMask Compatible** - Browser wallet support
- ✅ **Monitoring** - Prometheus + Grafana

---

## 🏗️ สถาปัตยกรรม

### System Architecture

```
┌───────────────────────────────────────────────────────────────────┐
│                    Thaiprompt Affiliate System                    │
│                                                                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │  Laravel Backend │  │   Frontend UI   │  │   Mobile App    │ │
│  │   (PHP 8.1+)    │  │ (Vue/React/JS)  │  │   (Flutter)     │ │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘ │
│           │                     │                     │           │
│           └─────────────────────┴─────────────────────┘           │
│                                 │                                 │
│                    ┌────────────▼────────────┐                   │
│                    │   TPIX REST API         │                   │
│                    │   /api/tpix/*           │                   │
│                    └────────────┬────────────┘                   │
│                                 │                                 │
│                    ┌────────────▼────────────┐                   │
│                    │  TPIXBlockchainService  │                   │
│                    │  (PHP Service Layer)    │                   │
│                    └────────────┬────────────┘                   │
└─────────────────────────────────┼─────────────────────────────────┘
                                  │
                                  │ JSON-RPC / WebSocket
                                  │
┌─────────────────────────────────▼─────────────────────────────────┐
│                         TPIX Blockchain                           │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    Validator Nodes                        │   │
│  │  ┌─────────┐    ┌─────────┐    ┌─────────┐              │   │
│  │  │ Node 1  │────│ Node 2  │────│ Node N  │              │   │
│  │  │Validator│    │Validator│    │Validator│              │   │
│  │  └────┬────┘    └────┬────┘    └────┬────┘              │   │
│  │       └──────────────┴──────────────┘                    │   │
│  │                      │                                    │   │
│  │              ┌───────▼───────┐                           │   │
│  │              │  IBFT Engine  │                           │   │
│  │              │  (Consensus)  │                           │   │
│  │              └───────┬───────┘                           │   │
│  └──────────────────────┼───────────────────────────────────┘   │
│                         │                                        │
│         ┌───────────────┼───────────────┐                       │
│         │               │               │                       │
│    ┌────▼────┐    ┌────▼────┐    ┌────▼────┐                  │
│    │   EVM   │    │ Storage │    │ Network │                  │
│    │ Engine  │    │  Layer  │    │  Layer  │                  │
│    └─────────┘    └─────────┘    └─────────┘                  │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    RPC Endpoints                            │ │
│  │  • JSON-RPC (8545)  • WebSocket (8546)  • gRPC (9632)     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
         ┌──────▼──────┐ ┌───▼────┐ ┌─────▼──────┐
         │   Block     │ │Grafana │ │ Prometheus │
         │  Explorer   │ │Monitor │ │  Metrics   │
         │  (Port 4000)│ │ (3000) │ │   (9090)   │
         └─────────────┘ └────────┘ └────────────┘
```

### Component Details

#### 1. **TPIX Blockchain Core**
- **Framework**: Polygon Edge (Go-based)
- **Consensus**: IBFT (Proof of Stake)
- **VM**: EVM (Ethereum Virtual Machine)
- **Storage**: LevelDB

#### 2. **Laravel Integration**
- **Service**: `TPIXBlockchainService.php`
- **Controller**: `TPIXBlockchainController.php`
- **Routes**: `/api/tpix/*`
- **Configuration**: `config/crypto.php`

#### 3. **Supporting Services**
- **Block Explorer**: Blockscout (PostgreSQL)
- **Monitoring**: Grafana + Prometheus
- **Deployment**: Docker Compose

---

## 📊 รายละเอียดทางเทคนิค

### Blockchain Specifications

| Parameter | Value | Description |
|-----------|-------|-------------|
| **Chain ID** | 7000 | Unique identifier |
| **Network Name** | TPIX Network | Display name |
| **Native Coin** | TPIX | Native cryptocurrency |
| **Symbol** | TPIX | Currency symbol |
| **Decimals** | 18 | Precision (like ETH) |
| **Total Supply** | 7,000,000,000 | Fixed supply |
| **Genesis Supply** | 7,000,000,000 | Pre-mined |
| **Block Time** | 2 seconds | Time between blocks |
| **Block Gas Limit** | 30,000,000 | Max gas per block |
| **Consensus** | IBFT | Byzantine Fault Tolerant |
| **Finality Time** | ~10 seconds | 5 block confirmations |

### Network Endpoints

```
JSON-RPC:  http://localhost:8545
WebSocket: ws://localhost:8546
gRPC:      localhost:9632
P2P:       localhost:1478
Metrics:   http://localhost:5001

Block Explorer: http://localhost:4000
Grafana:        http://localhost:3000
Prometheus:     http://localhost:9090
```

### Gas Economics

```javascript
// Standard transaction
Gas Limit: 21,000
Gas Price: 1 Gwei (1,000,000,000 wei)
Total Cost: 0.000021 TPIX (~0.42 satang if TPIX = 20 THB)

// Smart contract deploy
Gas Limit: ~500,000 - 2,000,000
Gas Price: 1 Gwei
Total Cost: 0.0005 - 0.002 TPIX

// ERC20 transfer
Gas Limit: ~65,000
Gas Price: 1 Gwei
Total Cost: 0.000065 TPIX
```

---

## 🚀 การติดตั้งและใช้งาน

### Quick Start (5 นาที)

```bash
# 1. ไปยังไดเรกทอรี TPIX
cd tpix-blockchain

# 2. สร้างไดเรกทอรี
mkdir -p data config

# 3. Copy genesis
cp config/genesis.json ./genesis.json

# 4. สร้าง .env
cat > .env << 'EOF'
POSTGRES_PASSWORD=secure_password
SECRET_KEY_BASE=$(openssl rand -hex 64)
GRAFANA_ADMIN_PASSWORD=admin
EOF

# 5. Start services
docker-compose up -d

# 6. Test
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

### Laravel Configuration

แก้ไข `.env`:

```env
# TPIX Blockchain
TPIX_RPC_URL=http://localhost:8545
TPIX_WS_URL=ws://localhost:8546
TPIX_EXPLORER_URL=http://localhost:4000
TPIX_CONFIRMATIONS=5
```

Clear cache:

```bash
php artisan config:clear
php artisan config:cache
```

### เอกสารเพิ่มเติม

- 📖 [Complete Documentation](../tpix-blockchain/docs/README.md)
- ⚡ [Quick Start Guide](../tpix-blockchain/docs/QUICKSTART.md)
- 🔧 [Installation Guide](../tpix-blockchain/docs/INSTALLATION.md)

---

## 🔌 API Integration

### REST API

```bash
# Network info
GET /api/tpix/network-info

# Balance
GET /api/tpix/balance?address=0x...

# Block number
GET /api/tpix/block-number

# Transaction
GET /api/tpix/transaction?txHash=0x...

# Send transaction
POST /api/tpix/send-raw-transaction
{
  "signedTx": "0x..."
}

# Estimate gas
POST /api/tpix/estimate-gas
{
  "from": "0x...",
  "to": "0x...",
  "value": "0x..."
}

# Gas price
GET /api/tpix/gas-price

# Transaction count (nonce)
GET /api/tpix/transaction-count?address=0x...

# Validate address
POST /api/tpix/validate-address
{
  "address": "0x..."
}

# Unit conversion
POST /api/tpix/to-wei
{
  "amount": "10.5"
}

POST /api/tpix/from-wei
{
  "amount": "10500000000000000000"
}
```

### PHP Service

```php
use App\Services\Crypto\TPIXBlockchainService;

// Initialize
$tpix = new TPIXBlockchainService();

// Get balance
$balance = $tpix->getBalance('0x...');
echo "Balance: $balance TPIX\n";

// Get network info
$info = $tpix->getNetworkInfo();
print_r($info);

// Send transaction
$txHash = $tpix->sendRawTransaction($signedTx);
echo "TX: $txHash\n";

// Get transaction
$tx = $tpix->getTransaction($txHash);
print_r($tx);

// Validate address
$isValid = $tpix->isValidAddress('0x...');

// Convert units
$wei = $tpix->toWei('10.5');
$tpix = $tpix->fromWei($wei);
```

### JavaScript (ethers.js)

```javascript
import { ethers } from 'ethers';

// Connect
const provider = new ethers.JsonRpcProvider('http://localhost:8545');

// Get balance
const balance = await provider.getBalance('0x...');
console.log('Balance:', ethers.formatEther(balance), 'TPIX');

// Send transaction
const wallet = new ethers.Wallet(privateKey, provider);
const tx = await wallet.sendTransaction({
  to: '0x...',
  value: ethers.parseEther('10')
});
await tx.wait();
console.log('TX:', tx.hash);

// Deploy contract
const factory = new ethers.ContractFactory(abi, bytecode, wallet);
const contract = await factory.deploy();
await contract.waitForDeployment();
console.log('Contract:', await contract.getAddress());
```

### MetaMask Integration

```javascript
// Add TPIX Network
await window.ethereum.request({
  method: 'wallet_addEthereumChain',
  params: [{
    chainId: '0x1B58', // 7000 in hex
    chainName: 'TPIX Network',
    nativeCurrency: {
      name: 'TPIX',
      symbol: 'TPIX',
      decimals: 18
    },
    rpcUrls: ['http://localhost:8545'],
    blockExplorerUrls: ['http://localhost:4000']
  }]
});
```

---

## 🎯 Use Cases

### 1. Affiliate Rewards System

```
User Actions → Earn TPIX → Withdraw/Spend TPIX

ตัวอย่าง:
- สมัครสมาชิกใหม่: รับ 100 TPIX
- แนะนำเพื่อน: รับ 50 TPIX
- ซื้อสินค้า: Cashback 5% เป็น TPIX
- ทำ Quest สำเร็จ: รับ 25 TPIX
```

### 2. Internal Payment System

```
ชำระเงินภายในระบบด้วย TPIX

ตัวอย่าง:
- ซื้อ AI Bot Packages
- ซื้อ Trading Bot Subscriptions
- ชำระค่า Hotel Booking
- ซื้อ Digital Products
```

### 3. Token Economy

```
สร้าง token บน TPIX blockchain

ตัวอย่าง:
- Point Token (สำหรับสะสมแต้ม)
- Voucher Token (บัตรกำนัล)
- Reward Token (รางวัล)
- Membership Token (สมาชิก VIP)
```

### 4. Smart Contract Applications

```
DApps บน TPIX

ตัวอย่าง:
- Loyalty Program Contract
- Multi-signature Wallet
- Escrow Service
- Staking Contract
```

---

## 🔒 ความปลอดภัย

### Blockchain Security

1. **IBFT Consensus**
   - Byzantine Fault Tolerant
   - ต้องมี validator >2/3 เพื่อ produce block
   - ป้องกัน double-spending

2. **Cryptographic Security**
   - SHA-256 hashing
   - ECDSA signatures (secp256k1)
   - HD wallet support (BIP39/BIP44)

3. **Network Security**
   - Encrypted P2P communication
   - Validator authentication
   - DDoS protection

### Application Security

1. **Laravel Security**
   - CSRF protection
   - XSS prevention
   - SQL injection protection
   - Rate limiting

2. **API Security**
   - JWT authentication
   - Request validation
   - IP whitelisting (optional)
   - Audit logging

3. **Key Management**
   - Private keys เข้ารหัส
   - HD wallet derivation
   - Seed phrase backup
   - Hardware wallet support

### Best Practices

```php
// ✅ DO: Validate addresses
if (!$tpix->isValidAddress($address)) {
    throw new Exception('Invalid address');
}

// ✅ DO: Use environment variables
$rpcUrl = config('crypto.networks.tpix.rpc_url');

// ✅ DO: Handle errors
try {
    $balance = $tpix->getBalance($address);
} catch (Exception $e) {
    Log::error('TPIX error: ' . $e->getMessage());
}

// ❌ DON'T: Expose private keys
// ❌ DON'T: Store keys in code
// ❌ DON'T: Skip validation
```

---

## 📈 Roadmap

### Phase 1: Foundation ✅ (สำเร็จแล้ว)
- [x] Blockchain core implementation
- [x] TPIX native coin (7B fixed supply)
- [x] IBFT consensus mechanism
- [x] EVM integration
- [x] Genesis configuration

### Phase 2: Integration ✅ (สำเร็จแล้ว)
- [x] Laravel service integration
- [x] REST API endpoints
- [x] PHP service layer
- [x] Block explorer
- [x] Docker deployment
- [x] Monitoring (Prometheus + Grafana)
- [x] Documentation

### Phase 3: Enhancement 🚧 (กำลังดำเนินการ)
- [ ] Multi-node testnet
- [ ] Faucet service (แจก TPIX ฟรีสำหรับทดสอบ)
- [ ] Advanced monitoring dashboards
- [ ] Performance optimization
- [ ] Additional RPC methods
- [ ] SDK development (PHP, JavaScript, Python)

### Phase 4: Production 📅 (วางแผน)
- [ ] Mainnet launch
- [ ] Mobile wallet app
- [ ] Bridge to other blockchains
- [ ] Governance system
- [ ] Staking rewards
- [ ] Lightning Network (Layer 2)

---

## ❓ คำถามที่พบบ่อย

### Q1: TPIX คืออะไร?

**A**: TPIX เป็น **Native Cryptocurrency** ที่มี **blockchain ของตัวเอง** ไม่ใช่ token บน Ethereum/BSC เหมาะสำหรับใช้ภายในระบบ Thaiprompt Affiliate

### Q2: TPIX ต่างจาก Token บน Ethereum ยังไง?

**A**:

| คุณสมบัติ | Token (ERC20) | TPIX Native Coin |
|-----------|---------------|------------------|
| Gas Fee | ต้องใช้ ETH/BNB | ใช้ TPIX |
| ควบคุม | ขึ้นกับ Ethereum/BSC | ควบคุมเต็มที่ |
| ค่าธรรมเนียม | สูง (ขึ้นกับ network) | ต่ำ (กำหนดเอง) |
| ความเร็ว | 12-15 วินาที | 2 วินาที |
| Smart Contract | ใช้ได้ | ใช้ได้ |

### Q3: Chain ID คือเท่าไร?

**A**: 7000

### Q4: Total Supply เท่าไร?

**A**: 7,000,000,000 TPIX (fixed supply, ไม่สามารถสร้างเพิ่มได้)

### Q5: รองรับ Smart Contracts ไหม?

**A**: รองรับ (EVM-compatible, ใช้ Solidity ได้)

### Q6: สามารถสร้าง ERC20 token บน TPIX ได้ไหม?

**A**: ได้ (รองรับ ERC20, ERC721, ERC1155)

### Q7: Consensus mechanism คืออะไร?

**A**: IBFT (Istanbul Byzantine Fault Tolerant) - Proof of Stake

### Q8: Block time เท่าไร?

**A**: 2 วินาที (เร็วกว่า Ethereum ~5 เท่า)

### Q9: ค่าธรรมเนียมเท่าไร?

**A**: ต่ำมาก (~0.000021 TPIX สำหรับ transfer ปกติ)

### Q10: ใช้กับ MetaMask ได้ไหม?

**A**: ได้ (รองรับ MetaMask และ wallet ที่รองรับ EVM)

---

## 📚 เอกสารเพิ่มเติม

### Documentation
- 📖 [Complete Documentation](../tpix-blockchain/docs/README.md)
- ⚡ [Quick Start Guide](../tpix-blockchain/docs/QUICKSTART.md)
- 🔧 [Installation Guide](../tpix-blockchain/docs/INSTALLATION.md)

### API Reference
- 🔌 [REST API Documentation](../tpix-blockchain/docs/API.md)
- 💻 [PHP Integration Guide](../tpix-blockchain/docs/PHP_INTEGRATION.md)
- 🌐 [Frontend Integration](../tpix-blockchain/docs/FRONTEND.md)

### Operations
- 🚀 [Production Deployment](../tpix-blockchain/docs/PRODUCTION.md)
- 🔒 [Security Best Practices](../tpix-blockchain/docs/SECURITY.md)
- 📊 [Monitoring Guide](../tpix-blockchain/docs/MONITORING.md)
- 🐛 [Troubleshooting](../tpix-blockchain/docs/TROUBLESHOOTING.md)

---

## 🙏 Credits

**TPIX Blockchain** พัฒนาโดย:
- Thaiprompt Affiliate Team
- Built with Polygon Edge
- Integrated with Laravel 11

**Technologies:**
- Polygon Edge - Blockchain framework
- Ethereum - EVM and standards
- Laravel - PHP framework
- Docker - Deployment
- Blockscout - Block explorer

---

## 📞 Support

หากมีคำถามหรือต้องการความช่วยเหลือ:

- 📖 Documentation: [docs/](../tpix-blockchain/docs/)
- 🐛 GitHub Issues: สร้าง issue
- 📧 Email: support@your-domain.com

---

<div align="center">

**TPIX Blockchain - Native Cryptocurrency for Thaiprompt Affiliate** 🚀

สร้างระบบเศรษฐกิจดิจิทัลของคุณเอง ด้วย blockchain ที่คุณควบคุมเต็มที่

[Get Started](../tpix-blockchain/docs/QUICKSTART.md) • [Documentation](../tpix-blockchain/docs/README.md) • [API Reference](../tpix-blockchain/docs/API.md)

</div>
