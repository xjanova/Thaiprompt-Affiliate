# 🚀 TPIX Blockchain Quick Start Guide

เริ่มต้น Deploy TPIX ใน 15 นาที!

---

## ✅ Prerequisites Checklist

- [ ] Ubuntu 22.04 หรือใหม่กว่า
- [ ] Node.js 18+ installed
- [ ] npm 9+ installed
- [ ] Git installed
- [ ] มี private key ที่มี TPIX สำหรับ deploy

---

## 📦 Step 1: Install Dependencies (5 นาที)

```bash
# 1. Install Polygon Edge
cd ~
wget https://github.com/0xPolygon/polygon-edge/releases/download/v1.0.0/polygon-edge-linux-amd64.tar.gz
tar -xzf polygon-edge-linux-amd64.tar.gz
sudo mv polygon-edge /usr/local/bin/
polygon-edge version

# 2. Install Node.js dependencies
cd /home/user/Thaiprompt-Affiliate/tpix-blockchain
npm install

# 3. Setup environment
cp .env.example .env
nano .env  # แก้ไข PRIVATE_KEY
```

---

## 🏗️ Step 2: Start Blockchain Node (3 นาที)

### Option A: Quick Local Node (Development)

```bash
# Terminal 1 - Start Hardhat node
npm run node

# ปล่อยทิ้งไว้ให้ทำงาน
```

### Option B: Polygon Edge Node (Production)

```bash
# สร้าง validator
mkdir -p ~/tpix-nodes/node-1
polygon-edge secrets init --data-dir ~/tpix-nodes/node-1

# สร้าง genesis
polygon-edge genesis \
  --consensus ibft \
  --ibft-validators-prefix-path ~/tpix-nodes/node- \
  --chain-id 7000 \
  --premine=0xYOUR_ADDRESS:7000000000000000000000000000

# เริ่ม node
polygon-edge server \
  --data-dir ~/tpix-nodes/node-1 \
  --chain genesis.json \
  --jsonrpc :8545 \
  --seal
```

---

## 🚀 Step 3: Deploy Contracts (5 นาที)

```bash
# 1. Compile contracts
npm run compile

# 2. Deploy to local/mainnet
npm run deploy:local     # Development
# หรือ
npm run deploy:mainnet   # Production

# Output จะแสดง contract addresses
# บันทึกไว้!
```

---

## ✅ Step 4: Verify & Test (2 นาที)

```bash
# 1. Test deployment
npm run test-deployment

# 2. Verify on explorer (optional)
npm run verify

# 3. Setup initial liquidity
npm run setup-pools
```

---

## 🔗 Step 5: Connect to Laravel (2 นาที)

```bash
cd /home/user/Thaiprompt-Affiliate

# 1. Sync contracts to database
php artisan tpix:sync-contracts

# 2. Run migrations (if not done)
php artisan migrate

# 3. Clear cache
php artisan config:clear
php artisan cache:clear

# 4. Test connection
php artisan tinker
>>> \App\Models\TPIXToken::count()
```

---

## 🎉 You're Done!

### ตรวจสอบว่าทุกอย่างทำงาน:

1. **Blockchain Node:**
   ```bash
   curl -X POST http://localhost:8545 \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
   ```

2. **Smart Contracts:**
   - TPIX Token: ✅
   - DEX Factory: ✅
   - DEX Router: ✅
   - Liquidity Pair: ✅

3. **Laravel Integration:**
   ```bash
   php artisan route:list | grep tpix
   # ควรเห็น routes ทั้งหมด
   ```

---

## 🚨 Troubleshooting

### Node ไม่ start:
```bash
# ตรวจสอบ port
sudo netstat -tulpn | grep 8545

# Kill process
sudo kill -9 $(sudo lsof -t -i:8545)
```

### Deploy failed:
```bash
# Check balance
npx hardhat console --network localhost
>>> (await ethers.provider.getBalance("YOUR_ADDRESS")).toString()

# Reset nonce
rm -rf cache/ artifacts/
npm run compile
```

### Laravel ไม่เชื่อมต่อ:
```bash
# ตรวจสอบ .env
cat .env | grep TPIX

# Sync contracts again
php artisan tpix:sync-contracts --force
```

---

## 📚 Next Steps

1. **Deploy Frontend:** [Frontend Setup Guide](./FRONTEND.md)
2. **Setup Monitoring:** [Monitoring Guide](./MONITORING.md)
3. **Production Checklist:** [Production Guide](./PRODUCTION.md)
4. **Security Audit:** [Security Guide](./SECURITY.md)

---

## 💡 Useful Commands

```bash
# Blockchain
npm run node              # Start local node
npm run compile           # Compile contracts
npm run deploy:local      # Deploy locally
npm run deploy:mainnet    # Deploy to mainnet
npm run verify            # Verify contracts
npm run test-deployment   # Test deployment

# Laravel
php artisan tpix:sync-contracts     # Sync contracts
php artisan migrate                  # Run migrations
php artisan config:clear            # Clear cache

# Monitoring
tail -f ~/tpix-nodes/node-1/logs/blockchain.log
curl -X POST http://localhost:8545 -H "Content-Type: application/json" -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

---

## 🆘 Need Help?

- 📖 **Full Guide:** [DEPLOYMENT.md](./DEPLOYMENT.md)
- 💬 **Discord:** https://discord.gg/tpix
- 📧 **Email:** support@tpix.com
- 🐛 **Issues:** https://github.com/tpix/blockchain/issues

---

**เวลารวมทั้งหมด: ~15-20 นาที** ⏱️

**ตอนนี้คุณมี Blockchain ที่ทำงานได้แล้ว!** 🎊
