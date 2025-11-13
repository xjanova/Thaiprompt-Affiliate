# TPIX Blockchain - Native Cryptocurrency System

## 🌟 Overview

**TPIX** เป็นเหรียญคริปโตแบบ **Native Coin** ที่มี **blockchain ของตัวเอง** ไม่ใช่ token บน blockchain อื่น (เช่น Ethereum, BSC)

### ✨ คุณสมบัติหลัก

- ✅ **Native Coin** - TPIX เป็นเหรียญหลักของ blockchain ไม่ต้องใช้เหรียญอื่นเป็นค่าแก๊ส
- ✅ **Fixed Supply** - จำนวนทั้งหมด **7,000,000,000 TPIX** (7 พันล้านเหรียญ) สร้างเพิ่มไม่ได้
- ✅ **EVM-Compatible** - รองรับ Smart Contracts แบบ Ethereum (Solidity)
- ✅ **ERC20 Support** - สามารถสร้าง token บน TPIX blockchain ได้
- ✅ **Fast Transactions** - Block time เพียง 2 วินาที
- ✅ **Low Gas Fees** - ค่าธรรมเนียมต่ำเพราะไม่ต้องแข่งขันกับ network อื่น
- ✅ **IBFT Consensus** - ใช้ Istanbul Byzantine Fault Tolerant (Proof of Stake)
- ✅ **Secure & Decentralized** - ออกแบบตามมาตรฐานสากล

---

## 🏗️ สถาปัตยกรรม (Architecture)

### Blockchain Specifications

| ข้อมูล | รายละเอียด |
|--------|------------|
| **ชื่อเครือข่าย** | TPIX Network |
| **ชื่อเหรียญ** | TPIX |
| **Chain ID** | 7000 |
| **Total Supply** | 7,000,000,000 TPIX |
| **Decimals** | 18 |
| **Consensus** | IBFT (Proof of Stake) |
| **Block Time** | 2 วินาที |
| **Block Gas Limit** | 30,000,000 |
| **EVM Version** | London (EIP-1559) |

### Technology Stack

- **Framework**: Polygon Edge (Go-based blockchain framework)
- **Smart Contracts**: Solidity ^0.8.20
- **RPC Protocol**: JSON-RPC 2.0 (Ethereum-compatible)
- **P2P Network**: Libp2p
- **Consensus**: IBFT (Istanbul Byzantine Fault Tolerant)

---

## 📦 Components

### 1. Blockchain Node
- JSON-RPC endpoint (port 8545)
- WebSocket endpoint (port 8546)
- gRPC endpoint (port 9632)
- P2P networking (port 1478)
- Prometheus metrics (port 5001)

### 2. Block Explorer
- Web-based explorer interface (port 4000)
- Transaction tracking
- Address monitoring
- Smart contract verification

### 3. PHP Integration
- Laravel service (`TPIXBlockchainService`)
- REST API endpoints
- Wallet management
- Transaction broadcasting

---

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Or: Linux/macOS with Go 1.19+
- PHP 8.1+ (for Laravel integration)
- Node.js 18+ (for frontend)

### Option 1: Docker Deployment (แนะนำ)

```bash
# 1. Navigate to TPIX blockchain directory
cd tpix-blockchain

# 2. Start all services
docker-compose up -d

# 3. Check status
docker-compose ps

# 4. View logs
docker-compose logs -f tpix-node
```

**Services will be available at:**
- JSON-RPC: http://localhost:8545
- WebSocket: ws://localhost:8546
- Block Explorer: http://localhost:4000
- Grafana: http://localhost:3000
- Prometheus: http://localhost:9090

### Option 2: Manual Installation

```bash
# 1. Make scripts executable
cd tpix-blockchain/scripts
chmod +x *.sh

# 2. Setup node
./setup-node.sh

# 3. Start node
./start-node.sh

# 4. Check status
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

---

## 💻 API Usage

### REST API Endpoints

Base URL: `http://your-domain.com/api/tpix`

#### 1. Get Network Information

```bash
GET /api/tpix/network-info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "name": "TPIX Network",
    "chainId": 7000,
    "symbol": "TPIX",
    "decimals": 18,
    "totalSupply": "7000000000.000000000000000000",
    "maxSupply": "7000000000.000000000000000000",
    "blockNumber": 12345,
    "gasPrice": 1000000000,
    "rpcUrl": "http://localhost:8545",
    "explorerUrl": "http://localhost:4000",
    "features": {
      "nativeCoin": true,
      "evmCompatible": true,
      "erc20Support": true,
      "smartContracts": true,
      "fixedSupply": true
    }
  }
}
```

#### 2. Get Balance

```bash
GET /api/tpix/balance?address=0x1234567890123456789012345678901234567890
```

**Response:**
```json
{
  "success": true,
  "data": {
    "address": "0x1234567890123456789012345678901234567890",
    "balance": "1000.500000000000000000",
    "symbol": "TPIX",
    "decimals": 18
  }
}
```

#### 3. Send Transaction

```bash
POST /api/tpix/send-raw-transaction
Content-Type: application/json

{
  "signedTx": "0xf86c808504a817c800825208941234567890123456789012345678901234567890880de0b6b3a764000080820a95a0..."
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "txHash": "0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890"
  }
}
```

#### 4. Get Transaction

```bash
GET /api/tpix/transaction?txHash=0xabcdef...
```

**Response:**
```json
{
  "success": true,
  "data": {
    "hash": "0xabcdef...",
    "from": "0x123...",
    "to": "0x456...",
    "value": "10.000000000000000000",
    "gas": 21000,
    "gasPrice": 1000000000,
    "nonce": 5,
    "blockNumber": 12345,
    "blockHash": "0x789...",
    "chainId": 7000
  }
}
```

### Complete API List

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tpix/network-info` | ข้อมูล network |
| GET | `/api/tpix/balance` | ยอดคงเหลือ |
| GET | `/api/tpix/block-number` | Block number ปัจจุบัน |
| GET | `/api/tpix/transaction` | ข้อมูล transaction |
| GET | `/api/tpix/transaction-receipt` | Transaction receipt |
| POST | `/api/tpix/send-raw-transaction` | ส่ง transaction |
| POST | `/api/tpix/estimate-gas` | คำนวณ gas |
| GET | `/api/tpix/gas-price` | ราคา gas |
| GET | `/api/tpix/transaction-count` | จำนวน transaction (nonce) |
| POST | `/api/tpix/validate-address` | ตรวจสอบ address |
| POST | `/api/tpix/to-wei` | แปลง TPIX เป็น wei |
| POST | `/api/tpix/from-wei` | แปลง wei เป็น TPIX |

---

## 🔧 PHP Integration

### Using TPIXBlockchainService

```php
use App\Services\Crypto\TPIXBlockchainService;

// Initialize service
$tpixService = new TPIXBlockchainService();

// Get balance
$balance = $tpixService->getBalance('0x1234...');
echo "Balance: $balance TPIX\n";

// Get network info
$info = $tpixService->getNetworkInfo();
print_r($info);

// Send transaction
$txHash = $tpixService->sendRawTransaction($signedTx);
echo "Transaction sent: $txHash\n";

// Check transaction
$tx = $tpixService->getTransaction($txHash);
print_r($tx);

// Validate address
$isValid = $tpixService->isValidAddress('0x1234...');
echo $isValid ? 'Valid' : 'Invalid';

// Convert units
$wei = $tpixService->toWei('10.5'); // TPIX to wei
$tpix = $tpixService->fromWei($wei); // wei to TPIX
```

---

## 🎨 Frontend Integration (JavaScript)

### Using ethers.js

```javascript
import { ethers } from 'ethers';

// Connect to TPIX network
const provider = new ethers.JsonRpcProvider('http://localhost:8545');

// Get network info
const network = await provider.getNetwork();
console.log('Chain ID:', network.chainId); // 7000

// Get balance
const balance = await provider.getBalance('0x1234...');
console.log('Balance:', ethers.formatEther(balance), 'TPIX');

// Send transaction
const wallet = new ethers.Wallet(privateKey, provider);
const tx = await wallet.sendTransaction({
  to: '0x5678...',
  value: ethers.parseEther('10.5') // 10.5 TPIX
});
await tx.wait();
console.log('Transaction:', tx.hash);
```

### Using Web3.js

```javascript
import Web3 from 'web3';

// Connect to TPIX network
const web3 = new Web3('http://localhost:8545');

// Get balance
const balance = await web3.eth.getBalance('0x1234...');
console.log('Balance:', web3.utils.fromWei(balance, 'ether'), 'TPIX');

// Send transaction
const tx = await web3.eth.sendTransaction({
  from: '0x1234...',
  to: '0x5678...',
  value: web3.utils.toWei('10.5', 'ether'),
  gas: 21000
});
console.log('Transaction:', tx.transactionHash);
```

### MetaMask Integration

Add TPIX network to MetaMask:

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

## 📝 Smart Contract Development

### Example ERC20 Token on TPIX

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "./TPIXERC20.sol";

contract MyToken is TPIXToken {
    constructor() TPIXToken("My Token", "MTK", 1000000 * 10**18) {
        // Initial supply: 1,000,000 MTK
    }
}
```

### Deploying Contracts

```javascript
import { ethers } from 'ethers';

// Connect to TPIX
const provider = new ethers.JsonRpcProvider('http://localhost:8545');
const wallet = new ethers.Wallet(privateKey, provider);

// Deploy contract
const factory = new ethers.ContractFactory(abi, bytecode, wallet);
const contract = await factory.deploy('My Token', 'MTK', ethers.parseEther('1000000'));
await contract.waitForDeployment();

console.log('Contract deployed at:', await contract.getAddress());
```

---

## 🔐 Security Best Practices

### Node Security

1. **Firewall Configuration**
   ```bash
   # Allow only necessary ports
   ufw allow 8545/tcp  # JSON-RPC
   ufw allow 8546/tcp  # WebSocket
   ufw allow 1478/tcp  # P2P
   ufw enable
   ```

2. **Use Reverse Proxy**
   - Use Nginx or Traefik for SSL/TLS
   - Rate limiting
   - DDoS protection

3. **Backup Keys**
   - Backup validator keys regularly
   - Store in encrypted format
   - Use hardware security modules (HSM) for production

### Application Security

1. **Validate All Inputs**
   ```php
   // Always validate addresses
   if (!$tpixService->isValidAddress($address)) {
       throw new Exception('Invalid address');
   }
   ```

2. **Use Environment Variables**
   ```env
   TPIX_RPC_URL=http://localhost:8545
   TPIX_WS_URL=ws://localhost:8546
   TPIX_EXPLORER_URL=http://localhost:4000
   ```

3. **Rate Limiting**
   - Implement rate limiting on API endpoints
   - Protect against spam transactions

---

## 🎯 Production Deployment

### System Requirements

#### Minimum
- CPU: 2 cores
- RAM: 4 GB
- Storage: 50 GB SSD
- Network: 10 Mbps

#### Recommended
- CPU: 4+ cores
- RAM: 8+ GB
- Storage: 100+ GB NVMe SSD
- Network: 100+ Mbps

### Deployment Steps

1. **Server Setup**
   ```bash
   # Update system
   apt update && apt upgrade -y

   # Install Docker
   curl -fsSL https://get.docker.com -o get-docker.sh
   sh get-docker.sh

   # Install Docker Compose
   apt install docker-compose -y
   ```

2. **Configure Firewall**
   ```bash
   ufw allow 22/tcp   # SSH
   ufw allow 80/tcp   # HTTP
   ufw allow 443/tcp  # HTTPS
   ufw allow 8545/tcp # JSON-RPC
   ufw allow 1478/tcp # P2P
   ufw enable
   ```

3. **Setup SSL/TLS**
   ```bash
   # Install Certbot
   apt install certbot

   # Get certificate
   certbot certonly --standalone -d your-domain.com
   ```

4. **Deploy with Docker**
   ```bash
   cd tpix-blockchain
   docker-compose up -d
   ```

5. **Setup Monitoring**
   - Access Grafana: http://your-domain.com:3000
   - Default credentials: admin/admin
   - Configure dashboards

### Environment Configuration

Create `.env` file in Laravel root:

```env
# TPIX Blockchain Configuration
TPIX_RPC_URL=http://localhost:8545
TPIX_WS_URL=ws://localhost:8546
TPIX_EXPLORER_URL=http://localhost:4000
TPIX_CONFIRMATIONS=5

# Production URLs (replace with your domain)
# TPIX_RPC_URL=https://rpc.your-domain.com
# TPIX_WS_URL=wss://ws.your-domain.com
# TPIX_EXPLORER_URL=https://explorer.your-domain.com
```

---

## 📊 Monitoring & Maintenance

### Health Checks

```bash
# Check node status
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# Check peer count
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"net_peerCount","params":[],"id":1}'

# Check syncing status
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_syncing","params":[],"id":1}'
```

### Logs

```bash
# View node logs
docker-compose logs -f tpix-node

# View specific number of lines
docker-compose logs --tail=100 tpix-node

# View logs with timestamp
docker-compose logs -f -t tpix-node
```

### Backup

```bash
# Backup validator keys
cp -r ~/.tpix/data ~/backup/tpix-data-$(date +%Y%m%d)

# Backup with Docker
docker-compose exec tpix-node cp -r /data /backup
```

---

## 🐛 Troubleshooting

### Node Not Starting

```bash
# Check logs
docker-compose logs tpix-node

# Restart services
docker-compose restart

# Clean restart
docker-compose down
docker-compose up -d
```

### Connection Issues

```bash
# Test RPC connection
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"web3_clientVersion","params":[],"id":1}'

# Check ports
netstat -tlnp | grep -E '8545|8546|9632|1478'

# Check firewall
ufw status
```

### Performance Issues

```bash
# Check system resources
htop

# Check disk space
df -h

# Check database size
docker-compose exec explorer-db psql -U postgres -d blockscout -c "SELECT pg_size_pretty(pg_database_size('blockscout'));"
```

---

## 📚 Additional Resources

### Documentation
- [Polygon Edge Documentation](https://wiki.polygon.technology/docs/edge/)
- [Ethereum JSON-RPC API](https://ethereum.org/en/developers/docs/apis/json-rpc/)
- [Solidity Documentation](https://docs.soliditylang.org/)

### Tools
- [Remix IDE](https://remix.ethereum.org/) - Smart contract development
- [Hardhat](https://hardhat.org/) - Ethereum development environment
- [Metamask](https://metamask.io/) - Browser wallet

### Community
- GitHub Issues: Report bugs and feature requests
- Discord: Join our developer community
- Documentation: Comprehensive guides and tutorials

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Credits

- **Polygon Edge** - Blockchain framework
- **Laravel** - PHP framework
- **Ethereum** - Smart contract platform
- **Blockscout** - Block explorer

---

## 📞 Support

For support and questions:
- Email: support@your-domain.com
- GitHub: Create an issue
- Discord: Join our server

---

**TPIX Blockchain - Native Cryptocurrency for the Future** 🚀
