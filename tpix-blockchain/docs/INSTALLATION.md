# คู่มือการติดตั้ง TPIX Blockchain

## 📋 สารบัญ

1. [ความต้องการของระบบ](#ความต้องการของระบบ)
2. [การติดตั้งด้วย Docker (แนะนำ)](#การติดตั้งด้วย-docker-แนะนำ)
3. [การติดตั้งแบบ Manual](#การติดตั้งแบบ-manual)
4. [การตั้งค่า Laravel Integration](#การตั้งค่า-laravel-integration)
5. [การทดสอบการติดตั้ง](#การทดสอบการติดตั้ง)
6. [การแก้ไขปัญหา](#การแก้ไขปัญหา)

---

## ความต้องการของระบบ

### สำหรับ Development

- **Operating System**: Linux (Ubuntu 20.04+), macOS 11+, หรือ Windows 10/11 with WSL2
- **CPU**: 2 cores ขึ้นไป
- **RAM**: 4 GB ขึ้นไป
- **Storage**: 50 GB SSD
- **Network**: 10 Mbps

### สำหรับ Production

- **Operating System**: Linux (Ubuntu 22.04 LTS แนะนำ)
- **CPU**: 4 cores ขึ้นไป (8 cores แนะนำ)
- **RAM**: 8 GB ขึ้นไป (16 GB แนะนำ)
- **Storage**: 100 GB NVMe SSD (500 GB แนะนำ)
- **Network**: 100 Mbps ขึ้นไป

### Software Requirements

#### Option 1: Docker (แนะนำ)
- Docker 20.10+
- Docker Compose 1.29+

#### Option 2: Manual Installation
- Go 1.19+
- Node.js 18+
- PostgreSQL 14+
- PHP 8.1+
- Composer 2.5+

---

## การติดตั้งด้วย Docker (แนะนำ)

วิธีนี้เหมาะสำหรับ development และ production โดยติดตั้งได้ง่ายและรวดเร็ว

### ขั้นตอนที่ 1: ติดตั้ง Docker

#### Ubuntu/Debian

```bash
# อัพเดท package index
sudo apt update

# ติดตั้ง prerequisites
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common

# เพิ่ม Docker's GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# เพิ่ม Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# ติดตั้ง Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# เพิ่ม user ของคุณเข้า docker group
sudo usermod -aG docker $USER

# Logout และ login ใหม่เพื่อให้ group changes มีผล
```

#### macOS

```bash
# ติดตั้งด้วย Homebrew
brew install --cask docker

# หรือดาวน์โหลด Docker Desktop จาก
# https://www.docker.com/products/docker-desktop
```

#### Windows

1. ดาวน์โหลด [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop)
2. ติดตั้งและเปิดใช้งาน WSL2
3. เปิด Docker Desktop

### ขั้นตอนที่ 2: Clone Repository

```bash
# Clone repository (หรือ copy ไดเรกทอรี tpix-blockchain)
cd /path/to/your/project
cd tpix-blockchain
```

### ขั้นตอนที่ 3: สร้าง Environment Configuration

```bash
# สร้างไฟล์ .env
cat > .env << 'EOF'
# TPIX Network Configuration
CHAIN_ID=7000
NETWORK_NAME="TPIX Network"
BLOCK_TIME=2
BLOCK_GAS_LIMIT=30000000

# Database Configuration
POSTGRES_DB=blockscout
POSTGRES_USER=postgres
POSTGRES_PASSWORD=your_secure_password_here

# Security
SECRET_KEY_BASE=your_secret_key_base_here

# Monitoring
GRAFANA_ADMIN_PASSWORD=your_grafana_password_here
EOF

# สร้าง random secret key
echo "SECRET_KEY_BASE=$(openssl rand -hex 64)" >> .env

# ตั้งค่า permissions
chmod 600 .env
```

### ขั้นตอนที่ 4: สร้าง Genesis Configuration

```bash
# สร้างไดเรกทอรีสำหรับ data
mkdir -p data config

# Copy genesis configuration
cp config/genesis.json ./genesis.json

# (Optional) แก้ไข genesis.json หากต้องการปรับแต่งค่าเริ่มต้น
nano genesis.json
```

### ขั้นตอนที่ 5: Start Services

```bash
# Start all services
docker-compose up -d

# ตรวจสอบ status
docker-compose ps

# ดู logs
docker-compose logs -f tpix-node
```

### ขั้นตอนที่ 6: รอให้ Services พร้อม

```bash
# รอให้ node เริ่มทำงาน (ประมาณ 30 วินาที)
sleep 30

# ทดสอบ connection
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# ถ้าได้ผลลัพธ์ แสดงว่าติดตั้งสำเร็จ!
```

### Services URLs

หลังจากติดตั้งเสร็จแล้ว คุณสามารถเข้าถึง services ต่อไปนี้:

- **JSON-RPC**: http://localhost:8545
- **WebSocket**: ws://localhost:8546
- **Block Explorer**: http://localhost:4000
- **Grafana Dashboard**: http://localhost:3000 (admin/your_password)
- **Prometheus Metrics**: http://localhost:9090

---

## การติดตั้งแบบ Manual

สำหรับผู้ที่ต้องการควบคุมการติดตั้งเต็มที่หรือไม่สามารถใช้ Docker ได้

### ขั้นตอนที่ 1: ติดตั้ง Polygon Edge

```bash
# ดาวน์โหลด Polygon Edge
wget https://github.com/0xPolygon/polygon-edge/releases/latest/download/polygon-edge_linux_amd64.tar.gz

# แตกไฟล์
tar -xzf polygon-edge_linux_amd64.tar.gz

# ย้ายไปยัง system path
sudo mv polygon-edge /usr/local/bin/

# ทดสอบ
polygon-edge version
```

### ขั้นตอนที่ 2: Setup Node

```bash
# สร้างไดเรกทอรีสำหรับ TPIX
mkdir -p ~/.tpix/{data,config,logs}

# เข้าไปยัง scripts directory
cd tpix-blockchain/scripts

# ทำให้ scripts สามารถรันได้
chmod +x *.sh

# รัน setup script
./setup-node.sh
```

### ขั้นตอนที่ 3: Generate Validator Keys

```bash
# Generate secrets
polygon-edge secrets init --data-dir ~/.tpix/data

# บันทึก validator address
polygon-edge secrets output --data-dir ~/.tpix/data

# ⚠️ สำคัญ: Backup keys
cp -r ~/.tpix/data ~/backup/tpix-keys-$(date +%Y%m%d)
```

### ขั้นตอนที่ 4: Create Genesis Block

```bash
# สร้าง genesis
polygon-edge genesis \
  --consensus ibft \
  --ibft-validators-prefix-path ~/.tpix/data \
  --bootnode "/dns4/localhost/tcp/1478/p2p/REPLACE_WITH_YOUR_NODE_ID" \
  --premine 0x0000000000000000000000000000000000000000:0x5F5E1000000000000000000000 \
  --epoch-size 100000 \
  --block-time 2s \
  --chain-id 7000 \
  --name "TPIX Network" \
  --block-gas-limit 30000000 \
  --pos

# Copy genesis.json
cp genesis.json ~/.tpix/config/
```

### ขั้นตอนที่ 5: Start Node

```bash
# เริ่ม node
./start-node.sh

# หรือรันโดยตรง
polygon-edge server \
  --data-dir ~/.tpix/data \
  --chain ~/.tpix/config/genesis.json \
  --grpc-address 0.0.0.0:9632 \
  --libp2p 0.0.0.0:1478 \
  --jsonrpc 0.0.0.0:8545 \
  --prometheus 0.0.0.0:5001 \
  --block-gas-target 30000000 \
  --seal
```

### ขั้นตอนที่ 6: ติดตั้ง Block Explorer (Optional)

```bash
# ติดตั้ง dependencies
sudo apt install -y postgresql-14 elixir erlang nodejs npm

# Clone Blockscout
git clone https://github.com/blockscout/blockscout.git
cd blockscout

# ตั้งค่า database
sudo -u postgres createdb blockscout

# ตั้งค่า environment
cat > .env << 'EOF'
DATABASE_URL=postgresql://postgres:postgres@localhost:5432/blockscout
ETHEREUM_JSONRPC_VARIANT=geth
ETHEREUM_JSONRPC_HTTP_URL=http://localhost:8545
ETHEREUM_JSONRPC_WS_URL=ws://localhost:8546
CHAIN_ID=7000
COIN=TPIX
SUBNETWORK=TPIX Network
EOF

# ติดตั้ง dependencies
mix deps.get

# Compile
mix compile

# สร้าง database
mix ecto.create
mix ecto.migrate

# เริ่ม server
mix phx.server
```

---

## การตั้งค่า Laravel Integration

### ขั้นตอนที่ 1: ตั้งค่า Environment Variables

แก้ไขไฟล์ `.env` ในโปรเจค Laravel:

```env
# เพิ่มบรรทัดเหล่านี้

# TPIX Blockchain Configuration
TPIX_RPC_URL=http://localhost:8545
TPIX_WS_URL=ws://localhost:8546
TPIX_EXPLORER_URL=http://localhost:4000
TPIX_CONFIRMATIONS=5

# สำหรับ Production (ใช้ domain จริง)
# TPIX_RPC_URL=https://rpc.your-domain.com
# TPIX_WS_URL=wss://ws.your-domain.com
# TPIX_EXPLORER_URL=https://explorer.your-domain.com
```

### ขั้นตอนที่ 2: Clear Cache

```bash
# Clear Laravel cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
```

### ขั้นตอนที่ 3: ทดสอบการเชื่อมต่อ

สร้างไฟล์ `test-tpix.php`:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Crypto\TPIXBlockchainService;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new TPIXBlockchainService();

try {
    $info = $service->getNetworkInfo();
    echo "✅ Connected to TPIX Network\n";
    echo "Chain ID: " . $info['chainId'] . "\n";
    echo "Block Number: " . $info['blockNumber'] . "\n";
    echo "Total Supply: " . $info['totalSupply'] . " TPIX\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

รันทดสอบ:

```bash
php test-tpix.php
```

---

## การทดสอบการติดตั้ง

### 1. ทดสอบ JSON-RPC

```bash
# ทดสอบ eth_blockNumber
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# ทดสอบ eth_chainId
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_chainId","params":[],"id":1}'

# ทดสอบ web3_clientVersion
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"web3_clientVersion","params":[],"id":1}'
```

### 2. ทดสอบ REST API

```bash
# ทดสอบ network info
curl http://localhost/api/tpix/network-info

# ทดสอบ block number
curl http://localhost/api/tpix/block-number

# ทดสอบ gas price
curl http://localhost/api/tpix/gas-price
```

### 3. ทดสอบ Block Explorer

เปิด browser และไปที่:
- http://localhost:4000

คุณควรเห็นหน้า Block Explorer พร้อมข้อมูล blocks และ transactions

### 4. ทดสอบ MetaMask Integration

1. เปิด MetaMask
2. คลิก network dropdown
3. เลือก "Add Network"
4. กรอกข้อมูล:
   - Network Name: TPIX Network
   - RPC URL: http://localhost:8545
   - Chain ID: 7000
   - Currency Symbol: TPIX
   - Block Explorer: http://localhost:4000
5. คลิก "Save"

---

## การแก้ไขปัญหา

### ปัญหา: Node ไม่เริ่มทำงาน

**อาการ**: `docker-compose up` แสดง error

**วิธีแก้**:

```bash
# ดู logs
docker-compose logs tpix-node

# ลอง restart
docker-compose restart tpix-node

# หรือ rebuild
docker-compose down
docker-compose up -d --build
```

### ปัญหา: Connection refused (8545)

**อาการ**: ไม่สามารถเชื่อมต่อ RPC endpoint

**วิธีแก้**:

```bash
# ตรวจสอบว่า node กำลังทำงาน
docker-compose ps

# ตรวจสอบ port
netstat -tlnp | grep 8545

# ตรวจสอบ firewall
sudo ufw status
sudo ufw allow 8545/tcp
```

### ปัญหา: Block Explorer ไม่แสดงผล

**อาการ**: Block Explorer เปิดไม่ได้หรือไม่มีข้อมูล

**วิธีแก้**:

```bash
# ตรวจสอบ database
docker-compose exec explorer-db psql -U postgres -d blockscout -c "SELECT COUNT(*) FROM blocks;"

# Restart explorer
docker-compose restart tpix-explorer

# ดู logs
docker-compose logs -f tpix-explorer
```

### ปัญหา: Out of Memory

**อาการ**: Node หยุดทำงานเนื่องจาก memory ไม่พอ

**วิธีแก้**:

```bash
# เพิ่ม swap space
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# ทำให้ permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### ปัญหา: Laravel ไม่สามารถเชื่อมต่อ

**อาการ**: API คืน error "Failed to connect"

**วิธีแก้**:

```bash
# ตรวจสอบ .env
grep TPIX_ .env

# ตรวจสอบว่า RPC URL ถูกต้อง
curl -X POST $TPIX_RPC_URL \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# Clear cache
php artisan config:clear
php artisan config:cache
```

### ปัญหา: Disk Space เต็ม

**อาการ**: Node หยุดทำงานเพราะ disk เต็ม

**วิธีแก้**:

```bash
# ตรวจสอบ disk usage
df -h

# ลบ logs เก่า
docker-compose exec tpix-node sh -c "find /data/logs -name '*.log' -mtime +7 -delete"

# Prune Docker
docker system prune -a --volumes
```

---

## Next Steps

หลังจากติดตั้งเสร็จแล้ว:

1. **อ่านคู่มือการใช้งาน**: [README.md](README.md)
2. **ทำความเข้าใจ API**: [API Documentation](API.md)
3. **พัฒนา Smart Contracts**: [Smart Contract Guide](SMART_CONTRACTS.md)
4. **Setup Production**: [Production Deployment Guide](PRODUCTION.md)

---

## การขอความช่วยเหลือ

หากพบปัญหาในการติดตั้ง:

1. ตรวจสอบ [Troubleshooting Guide](TROUBLESHOOTING.md)
2. ดู logs: `docker-compose logs -f`
3. สร้าง GitHub Issue พร้อมแนบ logs
4. ติดต่อทีม support

---

**ขอให้การติดตั้งเป็นไปด้วยดี!** 🚀
