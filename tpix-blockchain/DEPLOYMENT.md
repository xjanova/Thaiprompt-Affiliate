# 🚀 TPIX Blockchain Deployment Guide

คู่มือการ Deploy Smart Contracts ของ TPIX ลง Blockchain จริงแบบ Step-by-Step

---

## 📋 สารบัญ

1. [ข้อกำหนดเบื้องต้น](#ข้อกำหนดเบื้องต้น)
2. [ติดตั้ง Polygon Edge Node](#ติดตั้ง-polygon-edge-node)
3. [เตรียม Environment](#เตรียม-environment)
4. [Compile Smart Contracts](#compile-smart-contracts)
5. [Deploy ลง Blockchain](#deploy-ลง-blockchain)
6. [Verify Contracts](#verify-contracts)
7. [Setup Initial Pools](#setup-initial-pools)
8. [เชื่อมต่อกับ Laravel](#เชื่อมต่อกับ-laravel)
9. [Troubleshooting](#troubleshooting)

---

## 🔧 ข้อกำหนดเบื้องต้น

### Software Requirements:
- Node.js >= 18.0.0
- npm >= 9.0.0
- Polygon Edge >= 1.0.0
- Git
- Linux/MacOS (แนะนำ Ubuntu 22.04)

### Hardware Requirements (สำหรับ Validator Node):
- CPU: 4 cores (8 cores แนะนำ)
- RAM: 8GB minimum (16GB แนะนำ)
- Storage: 100GB SSD
- Network: 100 Mbps

---

## 🏗️ ติดตั้ง Polygon Edge Node

### Step 1: ติดตั้ง Polygon Edge

```bash
# ดาวน์โหลด Polygon Edge Binary
wget https://github.com/0xPolygon/polygon-edge/releases/download/v1.0.0/polygon-edge-linux-amd64.tar.gz

# แตกไฟล์
tar -xzf polygon-edge-linux-amd64.tar.gz

# ย้ายไปยัง PATH
sudo mv polygon-edge /usr/local/bin/

# ตรวจสอบการติดตั้ง
polygon-edge version
```

### Step 2: สร้าง Validator Nodes (4 nodes สำหรับ IBFT Consensus)

```bash
# สร้าง directory สำหรับ nodes
mkdir -p ~/tpix-blockchain/nodes
cd ~/tpix-blockchain/nodes

# สร้าง 4 validator nodes
for i in {1..4}; do
  polygon-edge secrets init --data-dir node-$i
done

# เก็บ validator addresses
polygon-edge secrets output --data-dir node-1
polygon-edge secrets output --data-dir node-2
polygon-edge secrets output --data-dir node-3
polygon-edge secrets output --data-dir node-4
```

**⚠️ สำคัญ:** เก็บ private keys ไว้ในที่ปลอดภัย!

### Step 3: สร้าง Genesis File

```bash
# สร้าง genesis.json
polygon-edge genesis \
  --consensus ibft \
  --ibft-validators-prefix-path node- \
  --bootnode /ip4/127.0.0.1/tcp/10001/p2p/<NODE1_LIBP2P_ADDRESS> \
  --bootnode /ip4/127.0.0.1/tcp/20001/p2p/<NODE2_LIBP2P_ADDRESS> \
  --chain-id 7000 \
  --name "TPIX Blockchain" \
  --premine=0xYOUR_ADMIN_ADDRESS:7000000000000000000000000000 \
  --block-gas-limit 8000000 \
  --epoch-size 100000

# genesis.json จะถูกสร้างในแต่ละ node directory
```

**Genesis Configuration:**
- Chain ID: `7000` (TPIX Mainnet)
- Consensus: IBFT 2.0 (Istanbul Byzantine Fault Tolerant)
- Block Time: 2 seconds
- Block Gas Limit: 8,000,000
- Premine: 7 billion TPIX สำหรับ admin address

### Step 4: เริ่ม Validator Nodes

```bash
# Terminal 1 - Node 1
polygon-edge server --data-dir ./node-1 --chain genesis.json \
  --grpc-address :10000 --libp2p :10001 --jsonrpc :8545 \
  --seal --log-level DEBUG

# Terminal 2 - Node 2
polygon-edge server --data-dir ./node-2 --chain genesis.json \
  --grpc-address :20000 --libp2p :20001 --jsonrpc :8546 \
  --seal --log-level DEBUG

# Terminal 3 - Node 3
polygon-edge server --data-dir ./node-3 --chain genesis.json \
  --grpc-address :30000 --libp2p :30001 --jsonrpc :8547 \
  --seal --log-level DEBUG

# Terminal 4 - Node 4
polygon-edge server --data-dir ./node-4 --chain genesis.json \
  --grpc-address :40000 --libp2p :40001 --jsonrpc :8548 \
  --seal --log-level DEBUG
```

**หรือใช้ systemd service (Production):**

```bash
# สร้าง service file
sudo nano /etc/systemd/system/tpix-node-1.service
```

```ini
[Unit]
Description=TPIX Blockchain Node 1
After=network.target

[Service]
Type=simple
User=tpix
WorkingDirectory=/home/tpix/tpix-blockchain/nodes
ExecStart=/usr/local/bin/polygon-edge server \
  --data-dir ./node-1 \
  --chain genesis.json \
  --grpc-address :10000 \
  --libp2p :10001 \
  --jsonrpc :8545 \
  --seal \
  --log-level INFO
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
# เริ่ม service
sudo systemctl daemon-reload
sudo systemctl enable tpix-node-1
sudo systemctl start tpix-node-1

# ตรวจสอบสถานะ
sudo systemctl status tpix-node-1
```

### Step 5: ตรวจสอบ Blockchain

```bash
# ตรวจสอบ block number
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# ผลลัพธ์: {"jsonrpc":"2.0","id":1,"result":"0x1"} (hex block number)
```

---

## ⚙️ เตรียม Environment

### Step 1: ติดตั้ง Dependencies

```bash
cd /home/user/Thaiprompt-Affiliate/tpix-blockchain

# ติดตั้ง Node.js packages
npm install

# หรือใช้ yarn
yarn install
```

### Step 2: สร้าง .env File

```bash
cp .env.example .env
nano .env
```

**ตัวอย่าง .env:**

```env
# Network Configuration
TPIX_RPC_URL=http://localhost:8545
TPIX_CHAIN_ID=7000
TPIX_TESTNET_RPC_URL=http://testnet.tpix.com:8545

# Deployer Private Key (ใช้ key จาก genesis premine)
PRIVATE_KEY=your_private_key_here
TESTNET_PRIVATE_KEY=your_testnet_private_key_here

# Explorer Configuration
TPIX_EXPLORER_URL=http://explorer.tpix.com
TPIX_EXPLORER_API_URL=http://explorer.tpix.com/api
TPIX_EXPLORER_API_KEY=your_api_key_here

# CoinMarketCap (สำหรับ gas reporter)
COINMARKETCAP_API_KEY=your_coinmarketcap_key

# Gas Settings (TPIX ไม่มี gas fees)
REPORT_GAS=false
```

**⚠️ Security Warning:**
- **ห้าม** commit .env เข้า git
- เก็บ private keys ในที่ปลอดภัย
- ใช้ Hardware Wallet หรือ Vault ใน Production

---

## 🔨 Compile Smart Contracts

### Step 1: Compile Contracts

```bash
cd /home/user/Thaiprompt-Affiliate/tpix-blockchain

# Compile ทุก contracts
npm run compile

# หรือ
npx hardhat compile
```

**Output:**
```
Compiled 15 Solidity files successfully
✓ Compiled contracts:
  - TPIXERC20
  - TPIXDEXFactory
  - TPIXDEXPair
  - TPIXDEXRouter02
  - และ libraries อื่นๆ
```

### Step 2: ตรวจสอบ Artifacts

```bash
ls -la artifacts/contracts/

# ควรเห็น:
# - TPIXERC20.sol/
# - TPIXDEXFactory.sol/
# - TPIXDEXPair.sol/
# - TPIXDEXRouter02.sol/
# - libraries/
```

---

## 🚀 Deploy ลง Blockchain

### Option 1: Deploy to Localhost (Development)

```bash
# เริ่ม local Hardhat node (ถ้ายังไม่ได้เริ่ม Polygon Edge)
npm run node

# Deploy (terminal ใหม่)
npm run deploy:local
```

### Option 2: Deploy to TPIX Testnet

```bash
npm run deploy:testnet
```

### Option 3: Deploy to TPIX Mainnet (Production)

```bash
# ⚠️ ตรวจสอบทุกอย่างให้แน่ใจก่อน!
npm run deploy:mainnet
```

### Deployment Process

Script จะทำตามลำดับ:

1. ✅ Deploy TPIX Native Token (ERC20)
   - Symbol: TPIX
   - Total Supply: 7,000,000,000 TPIX (fixed)

2. ✅ Deploy DEX Factory
   - Creates liquidity pairs
   - Uses CREATE2 for deterministic addresses

3. ✅ Deploy WETH (Wrapped TPIX)
   - Used for ETH trading pairs

4. ✅ Deploy DEX Router
   - User-facing interface
   - Handles swaps and liquidity

5. ✅ Create TPIX/WETH Pair
   - Initial liquidity pool

6. ✅ Save Deployment Info
   - JSON file in `deployments/`
   - Synced to Laravel `storage/app/tpix/`

7. ✅ Copy ABIs to Laravel
   - For blockchain interaction

**Expected Output:**

```
🚀 Starting TPIX Blockchain Deployment...

📍 Deployer address: 0x1234...
💰 Deployer balance: 10000 TPIX

📝 Step 1: Deploying TPIX Native Token (ERC20)...
✅ TPIX Token deployed at: 0xabcd...
   Total Supply: 7,000,000,000 TPIX

📝 Step 2: Deploying DEX Factory...
✅ DEX Factory deployed at: 0xefgh...

📝 Step 3: Deploying WETH (Wrapped TPIX)...
✅ WETH address (using TPIX): 0xabcd...

📝 Step 4: Deploying DEX Router...
✅ DEX Router deployed at: 0xijkl...

📝 Step 5: Creating initial TPIX/WETH liquidity pool...
✅ Approved router to spend tokens
✅ TPIX/WETH pair created at: 0xmnop...

📝 Step 6: Saving deployment information...
✅ Deployment info saved to: deployments/tpixMainnet-1234567890.json
✅ Deployment info synced to Laravel

📝 Step 7: Copying contract ABIs to Laravel...
  ✅ Copied TPIXERC20 ABI
  ✅ Copied TPIXDEXFactory ABI
  ✅ Copied TPIXDEXRouter02 ABI
  ✅ Copied TPIXDEXPair ABI

============================================================
🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉
============================================================

📋 Deployment Summary:
   Network: tpixMainnet (Chain ID: 7000)
   Deployer: 0x1234...

📝 Deployed Contracts:
   TPIXToken: 0xabcd...
   DEXFactory: 0xefgh...
   WETH: 0xabcd...
   DEXRouter: 0xijkl...
   TPIXWETHPair: 0xmnop...

📖 Next Steps:
   1. Update your .env file with contract addresses
   2. Run: php artisan tpix:sync-contracts
   3. Verify contracts: npm run verify
   4. Setup staking pools: npm run setup-pools
   5. Test deployment: npm run test-deployment

💡 Import to .env:
TPIX_TOKEN_ADDRESS=0xabcd...
TPIX_DEX_FACTORY_ADDRESS=0xefgh...
TPIX_DEX_ROUTER_ADDRESS=0xijkl...
TPIX_WETH_ADDRESS=0xabcd...
TPIX_PAIR_ADDRESS=0xmnop...
```

---

## ✅ Verify Contracts

การ verify ทำให้ source code แสดงบน Block Explorer:

```bash
npm run verify
```

**Output:**
```
🔍 Verifying contracts on TPIX Explorer...

📄 Using deployment: tpixMainnet-1234567890.json

Verifying TPIX Token...
✅ TPIX Token verified

Verifying DEX Factory...
✅ DEX Factory verified

Verifying DEX Router...
✅ DEX Router verified

🎉 Verification complete!
```

---

## 🏊 Setup Initial Pools

สร้าง liquidity pools เริ่มต้น:

```bash
npm run setup-pools
```

**Output:**
```
🏊 Setting up initial liquidity pools...

Adding liquidity to TPIX/WETH pair...
✅ Approved TPIX
✅ Liquidity added successfully!

💰 Your LP Token Balance: 31622.776601683792319757

🎉 Pool setup complete!
```

---

## 🔗 เชื่อมต่อกับ Laravel

### Step 1: Update Laravel .env

```bash
cd /home/user/Thaiprompt-Affiliate
nano .env
```

เพิ่ม/อัปเดต:

```env
# TPIX Blockchain
TPIX_RPC_URL=http://localhost:8545
TPIX_CHAIN_ID=7000
TPIX_EXPLORER_URL=http://explorer.tpix.com

# Smart Contract Addresses (จาก deployment)
TPIX_TOKEN_ADDRESS=0xabcd...
TPIX_DEX_FACTORY_ADDRESS=0xefgh...
TPIX_DEX_ROUTER_ADDRESS=0xijkl...
TPIX_WETH_ADDRESS=0xabcd...

# Admin Wallet (สำหรับ backend operations)
TPIX_ADMIN_WALLET_ADDRESS=0x1234...
TPIX_ADMIN_WALLET_PRIVATE_KEY=your_key_here
```

### Step 2: Run Migrations

```bash
php artisan migrate

# หรือ migrate เฉพาะ TPIX tables
php artisan migrate --path=database/migrations/2024_01_20_*_tpix_*.php
```

### Step 3: Sync Contracts to Database

สร้าง Artisan command:

```bash
php artisan make:command SyncTPIXContracts
```

แก้ไข `app/Console/Commands/SyncTPIXContracts.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TPIXContract;
use Illuminate\Support\Facades\Storage;

class SyncTPIXContracts extends Command
{
    protected $signature = 'tpix:sync-contracts';
    protected $description = 'Sync deployed TPIX contracts to database';

    public function handle()
    {
        $this->info('Syncing TPIX contracts...');

        $deploymentPath = storage_path('app/tpix/deployments.json');

        if (!file_exists($deploymentPath)) {
            $this->error('Deployment file not found!');
            return 1;
        }

        $deployment = json_decode(file_get_contents($deploymentPath), true);

        foreach ($deployment['contracts'] as $name => $address) {
            TPIXContract::updateOrCreate(
                ['name' => $name],
                [
                    'address' => $address,
                    'network' => $deployment['network'],
                    'chain_id' => $deployment['chainId'],
                    'deployer' => $deployment['deployer'],
                    'deployed_at' => $deployment['timestamp'],
                ]
            );

            $this->info("✓ Synced {$name}: {$address}");
        }

        $this->info("\n✅ All contracts synced successfully!");
        return 0;
    }
}
```

รัน command:

```bash
php artisan tpix:sync-contracts
```

### Step 4: Test Connection

```bash
php artisan tinker
```

```php
// Test Web3 connection
$web3 = new Web3\Web3(new Web3\Providers\HttpProvider(config('tpix.blockchain.rpc_url')));
$web3->eth->blockNumber(function ($err, $blockNumber) {
    echo "Current block: " . $blockNumber . "\n";
});

// Test TPIX Token contract
$token = \App\Models\TPIXToken::where('symbol', 'TPIX')->first();
echo "TPIX Token: " . $token->contract_address;
```

---

## 🧪 Testing

### Test Deployment

```bash
npm run test-deployment
```

### Manual Tests

```bash
# Test swap
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc":"2.0",
    "method":"eth_call",
    "params":[{
      "to": "0xROUTER_ADDRESS",
      "data": "0x..."
    }, "latest"],
    "id":1
  }'
```

### Integration Tests

```bash
php artisan test --filter=TPIXTest
```

---

## 🚨 Troubleshooting

### Issue 1: "insufficient funds for gas * price + value"

**สาเหตุ:** Deployer ไม่มี TPIX เพียงพอ

**แก้ไข:**
```bash
# Transfer TPIX to deployer
# ใช้ account ที่ได้รับ premine
```

### Issue 2: "nonce too low"

**สาเหตุ:** Transaction nonce ไม่ตรง

**แก้ไข:**
```bash
# Reset nonce
npx hardhat clean
rm -rf cache/ artifacts/
npm run compile
```

### Issue 3: "contract deployment failed"

**สาเหตุ:** Gas limit ไม่พอ หรือ contract มี error

**แก้ไข:**
```bash
# ตรวจสอบ logs
npx hardhat test --network localhost

# เพิ่ม gas limit ใน hardhat.config.js
gas: 10000000,
```

### Issue 4: Node ไม่ sync

**แก้ไข:**
```bash
# ตรวจสอบ peers
polygon-edge peers list --grpc-address localhost:10000

# Restart node
sudo systemctl restart tpix-node-1
```

---

## 📊 Monitoring

### Check Node Status

```bash
# Block number
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# Peer count
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"net_peerCount","params":[],"id":1}'

# Syncing status
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_syncing","params":[],"id":1}'
```

### View Logs

```bash
# Node logs
sudo journalctl -u tpix-node-1 -f

# Deployment logs
tail -f deployments/*.log
```

---

## 🔐 Security Best Practices

1. **Private Keys:**
   - ใช้ Hardware Wallet (Ledger/Trezor)
   - เก็บ keys ใน Vault (HashiCorp Vault, AWS KMS)
   - ไม่ hardcode ใน code

2. **Network Security:**
   - ใช้ Firewall (ufw, iptables)
   - เปิดเฉพาะ ports ที่จำเป็น
   - ใช้ VPN สำหรับ admin access

3. **Smart Contract Security:**
   - Audit code ก่อน deploy mainnet
   - ใช้ OpenZeppelin libraries
   - Test ทุก function

4. **Monitoring:**
   - Setup alerting (Prometheus + Grafana)
   - Monitor transaction failures
   - Track gas usage

---

## 📚 Additional Resources

- [Polygon Edge Documentation](https://docs.polygon.technology/edge/)
- [Hardhat Documentation](https://hardhat.org/docs)
- [OpenZeppelin Contracts](https://docs.openzeppelin.com/contracts)
- [Ethereum JSON-RPC](https://ethereum.org/en/developers/docs/apis/json-rpc/)

---

## 🎉 Congratulations!

คุณได้ deploy TPIX Blockchain สำเร็จแล้ว! 🚀

**Next Steps:**
1. Setup Block Explorer
2. Deploy Frontend
3. Marketing & Launch
4. List on exchanges

**Support:**
- Discord: https://discord.gg/tpix
- Telegram: https://t.me/tpixofficial
- Email: support@tpix.com
