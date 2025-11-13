# TPIX Blockchain - Quick Start Guide

เริ่มต้นใช้งาน TPIX Blockchain ภายใน 5 นาที! ⚡

---

## 🚀 เริ่มต้นอย่างรวดเร็ว (5 นาที)

### ข้อกำหนดเบื้องต้น

- Docker และ Docker Compose ติดตั้งแล้ว
- Port 8545, 4000, 3000 ว่าง
- RAM อย่างน้อย 4 GB

### ขั้นตอนที่ 1: เตรียม Environment (1 นาที)

```bash
# 1. ไปยังไดเรกทอรี TPIX
cd tpix-blockchain

# 2. สร้างไดเรกทอรีที่จำเป็น
mkdir -p data config

# 3. Copy genesis configuration
cp config/genesis.json ./genesis.json

# 4. สร้าง .env file
cat > .env << 'EOF'
POSTGRES_DB=blockscout
POSTGRES_USER=postgres
POSTGRES_PASSWORD=tpix_password
SECRET_KEY_BASE=$(openssl rand -hex 64)
GRAFANA_ADMIN_PASSWORD=admin123
EOF
```

### ขั้นตอนที่ 2: Start Blockchain (2 นาที)

```bash
# Start all services
docker-compose up -d

# รอ 30 วินาทีให้ services เริ่มต้น
sleep 30
```

### ขั้นตอนที่ 3: ทดสอบ (1 นาที)

```bash
# ทดสอบว่า node ทำงาน
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# ถ้าได้ผลลัพธ์คล้ายนี้ แสดงว่าสำเร็จ!
# {"jsonrpc":"2.0","id":1,"result":"0x1"}
```

### ขั้นตอนที่ 4: เปิด Block Explorer (30 วินาที)

เปิด browser และไปที่:
- **Block Explorer**: http://localhost:4000
- **Grafana**: http://localhost:3000 (username: admin, password: admin123)

---

## 🎯 ทดสอบส่ง Transaction แรก

### 1. เตรียม Wallet

```javascript
// ใช้ Node.js หรือ browser console
const { ethers } = require('ethers');

// เชื่อมต่อกับ TPIX
const provider = new ethers.JsonRpcProvider('http://localhost:8545');

// สร้าง wallet ใหม่ (หรือใช้ที่มีอยู่)
const wallet = ethers.Wallet.createRandom();
console.log('Address:', wallet.address);
console.log('Private Key:', wallet.privateKey);
// ⚠️ เก็บ private key ไว้ให้ปลอดภัย!
```

### 2. ได้รับ TPIX ฟรี (Testnet)

```bash
# ในระบบ production คุณจะต้องซื้อหรือรับ TPIX
# สำหรับ development ให้ใช้ faucet หรือ premine address

# ตัวอย่าง: โอน TPIX จาก premine address
# (ต้องมี private key ของ premine address)
```

### 3. ส่ง Transaction

```javascript
// Connect wallet กับ provider
const wallet = new ethers.Wallet('YOUR_PRIVATE_KEY', provider);

// ส่ง TPIX
const tx = await wallet.sendTransaction({
  to: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
  value: ethers.parseEther('10.5') // ส่ง 10.5 TPIX
});

console.log('Transaction sent:', tx.hash);

// รอให้ transaction ถูก mine
const receipt = await tx.wait();
console.log('Transaction mined in block:', receipt.blockNumber);
```

---

## 🌐 เพิ่ม TPIX Network ใน MetaMask

### วิธีที่ 1: Manual

1. เปิด MetaMask
2. คลิก network dropdown (ด้านบน)
3. คลิก "Add Network"
4. คลิก "Add a network manually"
5. กรอกข้อมูล:

```
Network Name: TPIX Network
RPC URL: http://localhost:8545
Chain ID: 7000
Currency Symbol: TPIX
Block Explorer URL: http://localhost:4000
```

6. คลิก "Save"

### วิธีที่ 2: Automatic (ผ่าน JavaScript)

```javascript
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

## 📝 ทดสอบ Smart Contract

### 1. สร้าง Simple Contract

สร้างไฟล์ `HelloTPIX.sol`:

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract HelloTPIX {
    string public message;

    constructor(string memory _message) {
        message = _message;
    }

    function setMessage(string memory _message) public {
        message = _message;
    }

    function getMessage() public view returns (string memory) {
        return message;
    }
}
```

### 2. Deploy Contract

```javascript
const { ethers } = require('ethers');

// Connect
const provider = new ethers.JsonRpcProvider('http://localhost:8545');
const wallet = new ethers.Wallet('YOUR_PRIVATE_KEY', provider);

// Contract bytecode และ ABI (compile จาก Remix หรือ Hardhat)
const bytecode = '0x608060405234801561001057600080fd5b50...';
const abi = [{"inputs":[{"internalType":"string","name":"_message","type":"string"}],"stateMutability":"nonpayable","type":"constructor"},...];

// Deploy
const factory = new ethers.ContractFactory(abi, bytecode, wallet);
const contract = await factory.deploy('Hello TPIX Blockchain!');
await contract.waitForDeployment();

const address = await contract.getAddress();
console.log('Contract deployed at:', address);

// เรียกใช้งาน
const message = await contract.getMessage();
console.log('Message:', message); // "Hello TPIX Blockchain!"
```

---

## 🔧 Laravel Integration (สำหรับ Backend)

### 1. Setup Environment

แก้ไข `.env`:

```env
TPIX_RPC_URL=http://localhost:8545
TPIX_WS_URL=ws://localhost:8546
TPIX_EXPLORER_URL=http://localhost:4000
TPIX_CONFIRMATIONS=5
```

### 2. ใช้งาน Service

```php
use App\Services\Crypto\TPIXBlockchainService;

// Initialize
$tpix = new TPIXBlockchainService();

// Get balance
$balance = $tpix->getBalance('0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb');
echo "Balance: $balance TPIX\n";

// Get network info
$info = $tpix->getNetworkInfo();
print_r($info);
```

### 3. สร้าง API Endpoint

```php
// routes/api.php (มีอยู่แล้ว!)
Route::prefix('tpix')->group(function () {
    Route::get('/network-info', [TPIXBlockchainController::class, 'getNetworkInfo']);
    Route::get('/balance', [TPIXBlockchainController::class, 'getBalance']);
    // ... more endpoints
});

// ทดสอบ API
curl http://localhost/api/tpix/network-info
```

---

## 📊 ตรวจสอบสถานะระบบ

### Dashboard URLs

- **Block Explorer**: http://localhost:4000
  - ดู blocks, transactions, addresses

- **Grafana**: http://localhost:3000
  - ดู metrics และ performance
  - Login: admin / admin123

- **Prometheus**: http://localhost:9090
  - ดู raw metrics

### Command Line

```bash
# ดู logs
docker-compose logs -f tpix-node

# ตรวจสอบ block number
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# ตรวจสอบ peer count
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"net_peerCount","params":[],"id":1}'

# ตรวจสอบ syncing status
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_syncing","params":[],"id":1}'
```

---

## 🛑 หยุดและรีสตาร์ท

```bash
# หยุด services
docker-compose down

# เริ่มใหม่
docker-compose up -d

# รีสตาร์ท service เดียว
docker-compose restart tpix-node

# ดู status
docker-compose ps
```

---

## 🎓 ทรัพยากรเพิ่มเติม

### Documentation
- [คู่มือฉบับเต็ม](README.md) - รายละเอียดครบถ้วน
- [คู่มือติดตั้ง](INSTALLATION.md) - การติดตั้งแบบละเอียด
- [API Documentation](API.md) - รายละเอียด API endpoints

### Tools
- [Remix IDE](https://remix.ethereum.org/) - พัฒนา Smart Contracts
- [Hardhat](https://hardhat.org/) - Smart Contract development environment
- [ethers.js](https://docs.ethers.org/) - JavaScript library

### Examples
```bash
# ดู example code
ls tpix-blockchain/examples/

# - send-transaction.js
# - deploy-contract.js
# - read-blockchain.js
# - erc20-token.sol
```

---

## ❓ คำถามที่พบบ่อย (FAQ)

### Q: TPIX คืออะไร?
**A**: TPIX เป็น native cryptocurrency ที่มี blockchain ของตัวเอง ไม่ใช่ token บน Ethereum/BSC

### Q: Chain ID คือเท่าไร?
**A**: 7000

### Q: Block time เท่าไร?
**A**: 2 วินาที

### Q: Total supply เท่าไร?
**A**: 7,000,000,000 TPIX (fixed, ไม่สามารถเพิ่มได้)

### Q: รองรับ Smart Contract ไหม?
**A**: รองรับ (EVM-compatible, ใช้ Solidity ได้)

### Q: รองรับ ERC20 ไหม?
**A**: รองรับ (สามารถสร้าง token บน TPIX blockchain ได้)

### Q: ค่า gas ใช้เหรียญอะไร?
**A**: ใช้ TPIX เป็นค่า gas (ไม่ต้องใช้เหรียญอื่น)

### Q: Consensus mechanism คืออะไร?
**A**: IBFT (Istanbul Byzantine Fault Tolerant) - Proof of Stake

---

## 🐛 พบปัญหา?

### Node ไม่เริ่ม
```bash
docker-compose logs tpix-node
docker-compose restart tpix-node
```

### ไม่สามารถเชื่อมต่อ port 8545
```bash
sudo ufw allow 8545/tcp
netstat -tlnp | grep 8545
```

### Memory ไม่พอ
```bash
# เพิ่ม swap
sudo fallocate -l 4G /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

---

## 🎉 ขั้นตอนต่อไป

ตอนนี้คุณได้:
- ✅ ติดตั้ง TPIX blockchain แล้ว
- ✅ ทดสอบส่ง transaction แล้ว
- ✅ เชื่อมต่อกับ MetaMask แล้ว
- ✅ Deploy smart contract แล้ว

**ลองทำต่อ:**
1. 🎨 สร้าง DApp บน TPIX
2. 🪙 สร้าง ERC20 token ของคุณเอง
3. 🚀 Deploy production node
4. 📱 สร้าง mobile wallet

---

## 📞 ติดต่อและสนับสนุน

- 📖 Documentation: [docs/](docs/)
- 💬 Discord: [Join our community]
- 🐛 Issues: [GitHub Issues](https://github.com/your-repo/issues)
- 📧 Email: support@your-domain.com

---

**ยินดีต้อนรับสู่ TPIX Blockchain!** 🚀💎

เริ่มสร้างอนาคตของ Web3 กับ TPIX วันนี้!
