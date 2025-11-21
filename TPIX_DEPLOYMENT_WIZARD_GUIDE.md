# 🚀 TPIX Native Coin Deployment Wizard - คู่มือฉบับสมบูรณ์

> **ระบบ Admin UI แบบ Step-by-Step สำหรับ Deploy TPIX Native Coin ตั้งแต่เริ่มต้นจนสำเร็จ**
>
> **Version**: 1.0.0 | **Created**: 2025-01-21 | **Framework**: Laravel 11 + Tailwind CSS + Alpine.js

---

## 📋 Table of Contents

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [สถาปัตยกรรม](#สถาปัตยกรรม)
3. [การติดตั้ง](#การติดตั้ง)
4. [7 ขั้นตอนการ Deploy](#7-ขั้นตอนการ-deploy)
5. [UI/UX Design](#uiux-design)
6. [API Documentation](#api-documentation)
7. [การใช้งาน](#การใช้งาน)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 ภาพรวมระบบ

### ปัญหาที่แก้ไข

การ deploy native coin บน blockchain นั้นซับซ้อนและต้องการความรู้ทางเทคนิคสูง:

- ❌ ต้องเขียน Smart Contract เอง
- ❌ ต้องรู้จัก Solidity programming
- ❌ ต้องตั้งค่า blockchain node
- ❌ ต้องจัดการ gas, wallet, และ deployment
- ❌ ต้อง verify contract บน explorer
- ❌ ต้องสร้าง DEX pool และ liquidity
- ❌ ต้อง list บน CoinMarketCap, CoinGecko

### Solution: TPIX Deployment Wizard

**ระบบ Wizard แบบ Step-by-Step ที่ทำให้คนไม่มีความรู้ก็สามารถ deploy native coin สำเร็จได้!**

✅ **Guided Process** - ทำตามขั้นตอน 7 steps เท่านั้น
✅ **Prerequisites Check** - ตรวจสอบความพร้อมอัตโนมัติ
✅ **Form-Based Configuration** - ตั้งค่าผ่านฟอร์มง่ายๆ
✅ **Visual Progress Tracking** - เห็นความคืบหน้าแบบ real-time
✅ **Auto Smart Contract Generation** - สร้าง contract อัตโนมัติ
✅ **One-Click Deployment** - Deploy แค่กดปุ่มเดียว
✅ **Integrated DEX** - สร้าง liquidity pool ในระบบ
✅ **CMC/CoinGecko Ready** - เตรียมข้อมูลสำหรับ list

---

## 🏗️ สถาปัตยกรรม

### ไฟล์ที่สร้างขึ้น

```
app/
├── Models/
│   └── TpixConfiguration.php              (เก็บ config ทั้งหมด)
├── Services/TPIX/
│   └── TpixDeploymentService.php         (Business logic)
└── Http/Controllers/Admin/
    └── TpixDeploymentController.php      (HTTP handlers)

database/migrations/
└── 2025_01_21_000001_create_tpix_configurations_table.php

routes/
└── admin.php                              (เพิ่ม routes ใหม่)

resources/views/admin/tpix/deployment/     (ต้องสร้างเอง)
├── index.blade.php                        (รายการ configs)
├── create.blade.php                       (สร้าง config ใหม่)
└── steps/
    ├── step1.blade.php                    (Prerequisites)
    ├── step2.blade.php                    (Token Config)
    ├── step3.blade.php                    (Tokenomics)
    ├── step4.blade.php                    (Smart Contract)
    ├── step5.blade.php                    (Deploy & Verify)
    ├── step6.blade.php                    (DEX Integration)
    └── step7.blade.php                    (Listing)
```

### Database Schema

**Table: `tpix_configurations`**

```sql
-- Primary fields
id, user_id, name, slug, status, current_step (1-7)

-- Step 1: Prerequisites
prerequisites (JSON)

-- Step 2: Token Configuration
token_name, token_symbol, total_supply, decimals, description,
logo_url, website_url, category, social_links (JSON)

-- Step 3: Tokenomics
supply_distribution (JSON), initial_price_tpix, initial_price_usd,
market_cap_target, is_mintable, is_burnable, is_pausable,
is_freezable, max_supply, fee_structure (JSON)

-- Step 4: Smart Contract
contract_features (JSON), contract_code (TEXT),
access_control, safety_features (JSON), is_upgradeable

-- Step 5: Deployment
contract_address, deployer_address, deploy_tx_hash,
deployed_at, is_verified, explorer_url,
deploy_gas_used, deploy_cost_tpix

-- Step 6: DEX Integration
dex_enabled, liquidity_pool_id, initial_liquidity_tpix,
initial_liquidity_token, liquidity_lock_days,
trading_params (JSON), trading_enabled_at

-- Step 7: Listing
cmc_submitted, cmc_id, cmc_submitted_at, cmc_status,
coingecko_submitted, coingecko_id, coingecko_submitted_at,
coingecko_status, marketing_materials (JSON),
whitepaper_url, pitch_deck_url

-- Metadata
deployment_logs (JSON), error_logs (JSON), notes (TEXT)
```

### Status Flow

```
draft → prerequisites_done → config_done → tokenomics_done →
contract_done → deployed → dex_ready → listed
```

---

## 🔧 การติดตั้ง

### Step 1: Run Migration

```bash
# เช็คว่า migration อยู่ใน database/migrations/
php artisan migrate

# ถ้าต้องการ fresh install
php artisan migrate:fresh --seed
```

### Step 2: Configure Environment

```env
# .env

# TPIX Blockchain
TPIX_RPC_URL=http://localhost:8545
TPIX_CHAIN_ID=7000
TPIX_EXPLORER=http://localhost:4000
TPIX_EXPLORER_API=http://localhost:4000/api

# Deployer Wallet
TPIX_DEPLOYER_ADDRESS=0x...
TPIX_DEPLOYER_PRIVATE_KEY=0x...

# CoinMarketCap (optional)
COINMARKETCAP_API_KEY=your_api_key
```

### Step 3: Start TPIX Blockchain

```bash
cd tpix-blockchain
./scripts/start-node.sh
```

### Step 4: Test Prerequisites

```bash
# ทดสอบเชื่อมต่อ RPC
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'
```

---

## 📝 7 ขั้นตอนการ Deploy

### 🔍 Step 1: Prerequisites Check

**จุดประสงค์**: ตรวจสอบความพร้อมของระบบก่อน deploy

**สิ่งที่ตรวจสอบ:**

1. ✅ **RPC Node**
   - เชื่อมต่อ TPIX Blockchain Node ได้
   - ดึง Block Number สำเร็จ
   - Chain ID ถูกต้อง

2. ✅ **Wallet**
   - มี deployer wallet address
   - มี private key
   - มี TPIX balance เพียงพอ (≥1 TPIX)

3. ✅ **Services**
   - Redis ทำงานปกติ
   - Database เชื่อมต่อสำเร็จ
   - Queue Worker พร้อมใช้งาน

4. ✅ **Environment**
   - PHP version ≥8.1
   - Laravel 11.x
   - Required PHP extensions
   - Directory permissions

**Code Example:**

```php
// Service method
public function checkPrerequisites(): array
{
    return [
        'rpc_node' => $this->checkRpcNode(),
        'wallet' => $this->checkWallet(),
        'services' => $this->checkServices(),
        'environment' => $this->checkEnvironment(),
        'all_passed' => /* all checks passed */
    ];
}
```

**API Endpoint:**

```http
GET /admin/tpix/deployment/api/check-prerequisites

Response:
{
  "success": true,
  "data": {
    "rpc_node": {"passed": true, "message": "เชื่อมต่อ RPC Node สำเร็จ"},
    "wallet": {"passed": true, "balance": "100.0000 TPIX"},
    "services": {"passed": true},
    "environment": {"passed": true},
    "all_passed": true
  }
}
```

---

### ⚙️ Step 2: Token Configuration

**จุดประสงค์**: ตั้งค่าพื้นฐานของเหรียญ

**ฟอร์ม Input:**

```
📌 Basic Information
┣━ Token Name: [My Awesome Token]
┣━ Token Symbol: [MAT] (2-20 ตัวอักษร)
┣━ Total Supply: [1,000,000]
┣━ Decimals: [18] (0-18)
┗━ Description: [คำอธิบายโครงการ...]

🎨 Branding
┣━ Logo URL: [https://...]
┗━ Website URL: [https://...]

📁 Category
┗━ [ ] DeFi [ ] GameFi [ ] Meme [✓] Utility [ ] NFT [ ] DAO

🌐 Social Links
┣━ Twitter: [https://twitter.com/...]
┣━ Telegram: [https://t.me/...]
┣━ Discord: [https://discord.gg/...]
┗━ GitHub: [https://github.com/...]
```

**Validation Rules:**

```php
[
    'token_name' => 'required|string|min:3|max:100',
    'token_symbol' => 'required|string|min:2|max:20|uppercase',
    'total_supply' => 'required|numeric|min:1|max:1000000000000',
    'decimals' => 'required|integer|min:0|max:18',
    'description' => 'nullable|string|max:1000',
    'logo_url' => 'nullable|url',
    'website_url' => 'nullable|url',
    'category' => 'required|in:defi,gamefi,meme,utility,stablecoin,nft,dao,other',
    'social_links' => 'nullable|array',
]
```

---

### 💰 Step 3: Tokenomics

**จุดประสงค์**: กำหนดเศรษฐศาสตร์และการกระจายเหรียญ

**ฟอร์ม Input:**

```
📊 Supply Distribution
┣━ Team/Founders: [20%] [200,000 tokens]
┣━ Public Sale: [40%] [400,000 tokens]
┣━ Liquidity Pool: [30%] [300,000 tokens]
┗━ Marketing: [10%] [100,000 tokens]

💵 Pricing
┣━ Initial Price (TPIX): [0.01]
┣━ Initial Price (USD): [0.20]
┗━ Market Cap Target: [$200,000]

🔧 Token Features
┣━ [✓] Burnable - สามารถเผาได้
┣━ [ ] Mintable - สามารถสร้างเพิ่มได้
┣━ [ ] Pausable - สามารถหยุดชั่วคราวได้
┗━ [ ] Freezable - สามารถแช่แข็ง address ได้

📈 Max Supply (ถ้า Mintable)
┗━ Max Supply: [10,000,000]

💸 Fee Structure
┣━ Transfer Fee: [0%]
┣━ Buy Fee: [2%]
┗━ Sell Fee: [3%]
```

**Supply Distribution JSON:**

```json
{
  "team": {
    "percentage": 20,
    "amount": 200000,
    "vesting": "12 months",
    "cliff": "3 months"
  },
  "public_sale": {
    "percentage": 40,
    "amount": 400000,
    "price": 0.01
  },
  "liquidity": {
    "percentage": 30,
    "amount": 300000,
    "lock_days": 365
  },
  "marketing": {
    "percentage": 10,
    "amount": 100000
  }
}
```

---

### 📜 Step 4: Smart Contract Customization

**จุดประสงค์**: ปรับแต่ง Smart Contract ตามต้องการ

**ฟีเจอร์ที่เลือกได้:**

```
✅ Available Features
┣━ [✓] Burnable - เผาเหรียญได้
┣━ [ ] Mintable - สร้างเหรียญเพิ่มได้
┣━ [ ] Pausable - หยุดการทำงานชั่วคราว
┣━ [✓] Freezable - แช่แข็ง address
┣━ [✓] Access Control - ควบคุมการเข้าถึง
┗━ [ ] Snapshot - บันทึก balance ณ เวลาหนึ่ง

🔐 Access Control
┗━ ( ) Owner (✓) Role-Based ( ) DAO ( ) Multi-Sig

🛡️ Safety Features
┣━ [✓] Max Transaction Limit
┣━ [✓] Anti-Whale
┣━ [✓] Anti-Bot
┗━ [ ] Blacklist

🔄 Upgradeability
┗━ [ ] Is Upgradeable (Proxy Pattern)
```

**Contract Code Preview:**

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/token/ERC20/extensions/ERC20Burnable.sol";
import "@openzeppelin/contracts/access/AccessControl.sol";

contract MyAwesomeToken is ERC20, ERC20Burnable, AccessControl {
    bytes32 public constant MINTER_ROLE = keccak256("MINTER_ROLE");

    constructor(address defaultAdmin, address minter)
        ERC20("My Awesome Token", "MAT")
    {
        _grantRole(DEFAULT_ADMIN_ROLE, defaultAdmin);
        _grantRole(MINTER_ROLE, minter);
        _mint(msg.sender, 1000000 * 10 ** decimals());
    }

    function mint(address to, uint256 amount) public onlyRole(MINTER_ROLE) {
        _mint(to, amount);
    }
}
```

**Service Method:**

```php
public function generateSmartContract(TpixConfiguration $config, array $features): string
{
    $template = file_get_contents(base_path('tpix-blockchain/contracts/TPIXERC20.sol'));

    // Customize contract based on config
    $contractCode = $this->customizeContract($template, $config, $features);

    // Save to config
    $config->contract_code = $contractCode;
    $config->contract_features = $features;
    $config->save();

    return $contractCode;
}
```

---

### 🚀 Step 5: Deploy & Verify

**จุดประสงค์**: Deploy contract ขึ้น blockchain และ verify

**การแสดงผล:**

```
📊 Deployment Summary
┣━ Token Name: My Awesome Token
┣━ Symbol: MAT
┣━ Total Supply: 1,000,000 MAT
┣━ Decimals: 18
┗━ Features: Burnable, Freezable, Access Control

⛽ Estimated Cost
┣━ Gas Price: 2.5 Gwei
┣━ Estimated Gas: 2,500,000
┗━ Total Cost: ~0.00625 TPIX ($0.12)

👛 Deployer Wallet
┣━ Address: 0x1234...5678
┗━ Balance: 100.0000 TPIX

🚀 Actions
┣━ [Deploy Contract] ← กดเพื่อ deploy
┗━ [Verify on Explorer] ← หลัง deploy สำเร็จ
```

**Deploy Flow:**

```php
public function deployContract(TpixConfiguration $config): array
{
    // 1. ตรวจสอบความพร้อม
    if (!$config->isReadyToDeploy()) {
        throw new Exception('Not ready to deploy');
    }

    // 2. Deploy via blockchain service
    $result = $this->blockchainService->deployContract([
        'name' => $config->token_name,
        'symbol' => $config->token_symbol,
        'total_supply' => $config->total_supply,
        'contract_code' => $config->contract_code,
    ]);

    // 3. บันทึกผลลัพธ์
    $config->update([
        'contract_address' => $result['contract_address'],
        'deploy_tx_hash' => $result['tx_hash'],
        'deployed_at' => now(),
        'deploy_gas_used' => $result['gas_used'],
        'deploy_cost_tpix' => $result['cost_tpix'],
        'explorer_url' => config('tpix.explorer_url') . '/address/' . $result['contract_address'],
    ]);

    // 4. Log
    $config->addDeploymentLog('contract_deployed', $result);

    return $result;
}
```

**หลัง Deploy สำเร็จ:**

```
✅ Deployment Successful!

📋 Details
┣━ Contract Address: 0xabcd...ef01
┣━ Transaction Hash: 0x1234...5678
┣━ Gas Used: 2,450,123
┣━ Cost: 0.00612 TPIX ($0.12)
┗━ Block Number: #1,234,567

🔗 Links
┣━ [View on Explorer] → http://explorer.tpix.com/address/0xabcd...
┗━ [View Transaction] → http://explorer.tpix.com/tx/0x1234...

📝 Next Steps
┗━ [Verify Contract] ← ยืนยัน source code บน explorer
```

---

### 💱 Step 6: DEX Integration

**จุดประสงค์**: สร้าง Liquidity Pool และเปิดการซื้อขาย

**ฟอร์ม Input:**

```
💧 Initial Liquidity
┣━ TPIX Amount: [1,000] TPIX
┣━ Token Amount: [100,000] MAT
┗━ Initial Price: 0.01 TPIX/MAT

🔒 Lock Period
┗━ Liquidity Lock: [365] days

📊 Calculated Values
┣━ LP Tokens: 3,162.28
┣━ Pool Share: 100%
┗━ Total Value: $20,000

⚙️ Trading Parameters
┣━ Trading Fee: [0.3%]
┣━ Slippage Tolerance: [0.5%]
┣━ Max Transaction: [10,000] MAT
┗━ Price Impact Alert: [5%]

🚀 Actions
┣━ [Create Liquidity Pool]
┗━ [Enable Trading]
```

**Service Methods:**

```php
public function createLiquidityPool(
    TpixConfiguration $config,
    float $amountTPIX,
    float $amountToken
): array {
    DB::beginTransaction();

    // 1. สร้าง pool
    $pool = TPIXLiquidityPool::create([
        'token_a_address' => '0x0000...', // TPIX native
        'token_b_address' => $config->contract_address,
        'reserve_a' => $amountTPIX,
        'reserve_b' => $amountToken,
        'total_liquidity' => sqrt($amountTPIX * $amountToken),
        'fee_percentage' => 0.3,
        'is_active' => false, // ยังไม่เปิด trading
    ]);

    // 2. บันทึกใน config
    $config->update([
        'liquidity_pool_id' => $pool->id,
        'initial_liquidity_tpix' => $amountTPIX,
        'initial_liquidity_token' => $amountToken,
        'dex_enabled' => true,
    ]);

    DB::commit();

    return ['success' => true, 'pool_id' => $pool->id];
}

public function enableTrading(TpixConfiguration $config): array
{
    $pool = $config->liquidityPool;
    $pool->is_active = true;
    $pool->save();

    $config->trading_enabled_at = now();
    $config->save();

    return ['success' => true];
}
```

**หลังเปิด Trading:**

```
✅ Trading Enabled!

💱 DEX Pool Information
┣━ Pool Address: 0x9876...5432
┣━ Pair: TPIX/MAT
┣━ TPIX Reserve: 1,000.00
┣━ MAT Reserve: 100,000.00
┗━ Current Price: 0.01 TPIX/MAT

📊 24h Statistics
┣━ Volume: $0 (just started)
┣━ Trades: 0
┣━ TVL: $20,000
┗━ APY: 0% (no fees yet)

🔗 Trading Links
┣━ [Swap on DEX] → /dex/swap?pair=TPIX-MAT
┗━ [Add Liquidity] → /dex/liquidity/add?pair=TPIX-MAT
```

---

### 📈 Step 7: Listing & Marketing

**จุดประสงค์**: List เหรียญบน CMC, CoinGecko และเตรียมการตลาด

**CoinMarketCap Submission:**

```
📋 CoinMarketCap Application

✅ Required Information (Auto-filled)
┣━ Project Name: My Awesome Token
┣━ Ticker Symbol: MAT
┣━ Contract Address: 0xabcd...ef01
┣━ Total Supply: 1,000,000 MAT
┣━ Circulating Supply: 400,000 MAT
┣━ Website: https://myawesometoken.com
┣━ Explorer: http://explorer.tpix.com/address/0xabcd...
┗━ Description: [Auto-generated]

📄 Additional Materials
┣━ Logo (PNG, 200x200): [Upload]
┣━ Whitepaper: [Upload PDF]
┣━ Pitch Deck: [Upload PDF]
┗━ Audit Report: [Upload PDF] (optional)

🌐 Social Verification
┣━ Twitter: [@myawesometoken]
┣━ Telegram: [t.me/myawesometoken]
┗━ Discord: [discord.gg/myawesometoken]

🚀 Actions
┣━ [Preview Submission]
┗━ [Submit to CoinMarketCap]
```

**CoinGecko Submission:**

```
📋 CoinGecko Application

✅ Basic Information
┣━ Token Name: My Awesome Token
┣━ Symbol: MAT
┣━ Contract: 0xabcd...ef01
┣━ Website: https://myawesometoken.com
┗━ Explorer: http://explorer.tpix.com

📊 Market Data
┣━ Trading Platform: TPIX DEX
┣━ Trading Pair: MAT/TPIX
┗━ Liquidity: $20,000

🚀 Actions
┗━ [Submit to CoinGecko]
```

**Service Methods:**

```php
public function submitToCoinMarketCap(TpixConfiguration $config): array
{
    $data = [
        'name' => $config->token_name,
        'symbol' => $config->token_symbol,
        'contract_address' => $config->contract_address,
        'total_supply' => $config->total_supply,
        'website' => $config->website_url,
        'explorer' => $config->explorer_url,
        'description' => $config->description,
        'whitepaper' => $config->whitepaper_url,
        'social_links' => $config->social_links,
    ];

    // บันทึกว่าส่งแล้ว (ต้องส่งจริงผ่าน CMC form)
    $config->update([
        'cmc_submitted' => true,
        'cmc_submitted_at' => now(),
        'cmc_status' => 'pending',
    ]);

    $config->addDeploymentLog('submitted_to_cmc', $data);

    return ['success' => true, 'data' => $data];
}
```

**เสร็จสิ้น:**

```
🎉 Congratulations!

คุณได้ Deploy TPIX Native Coin สำเร็จแล้ว!

✅ Completed Steps
┣━ [✓] Prerequisites Check
┣━ [✓] Token Configuration
┣━ [✓] Tokenomics Setup
┣━ [✓] Smart Contract Customization
┣━ [✓] Contract Deployed & Verified
┣━ [✓] DEX Pool Created & Trading Enabled
┗━ [✓] Submitted to CMC & CoinGecko

📊 Token Information
┣━ Name: My Awesome Token
┣━ Symbol: MAT
┣━ Contract: 0xabcd...ef01
┣━ Total Supply: 1,000,000 MAT
┗━ Current Price: 0.01 TPIX/MAT

🔗 Important Links
┣━ [Explorer] → http://explorer.tpix.com/token/mat
┣━ [DEX Trading] → /dex/swap?token=mat
┣━ [Add Liquidity] → /dex/liquidity/add?token=mat
┗━ [Admin Dashboard] → /admin/tpix/deployment

📝 Next Steps
1. Monitor trading activity
2. Engage with community
3. Wait for CMC/CoinGecko approval (1-7 days)
4. Start marketing campaigns
5. List on more exchanges
```

---

## 🎨 UI/UX Design

### V3 Design Standards

**Technology Stack:**

- ✅ **Tailwind CSS** - Pure utility-first CSS
- ✅ **Alpine.js** - Lightweight JS framework (~15KB)
- ✅ **Modern UI** - Glassmorphism, 3D effects, Gradients
- ✅ **Dark Mode** - Full support
- ✅ **Responsive** - Mobile-first design

### Progress Bar (ทุกหน้า)

```html
<!-- Wizard Progress Bar -->
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="relative">
        <!-- Progress Line -->
        <div class="absolute top-5 left-0 w-full h-1 bg-gray-200 dark:bg-gray-700">
            <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-500"
                 style="width: {{ ($config->current_step / 7) * 100 }}%"></div>
        </div>

        <!-- Steps -->
        <div class="relative flex justify-between">
            @foreach(['Prerequisites', 'Config', 'Tokenomics', 'Contract', 'Deploy', 'DEX', 'List'] as $index => $step)
                @php $stepNum = $index + 1; @endphp
                <div class="flex flex-col items-center">
                    <!-- Circle -->
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                {{ $config->current_step >= $stepNum ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}
                                shadow-lg transition-all duration-300">
                        @if($config->current_step > $stepNum)
                            <!-- Checkmark -->
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            {{ $stepNum }}
                        @endif
                    </div>
                    <!-- Label -->
                    <span class="mt-2 text-sm font-medium {{ $config->current_step === $stepNum ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                        {{ $step }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
```

### Card Design (Modern Glassmorphism)

```html
<!-- Modern Card with Glassmorphism -->
<div class="relative group">
    <!-- Glow Effect -->
    <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl blur-lg opacity-25 group-hover:opacity-40 transition duration-300"></div>

    <!-- Card Content -->
    <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-xl p-8 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
            Step {{ $step }}: {{ $title }}
        </h3>

        <p class="text-gray-600 dark:text-gray-300 mb-6">
            {{ $description }}
        </p>

        <!-- Content -->
        <slot></slot>

        <!-- Actions -->
        <div class="flex justify-between mt-8">
            @if($step > 1)
                <button class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    ← ย้อนกลับ
                </button>
            @endif

            <button type="submit" class="ml-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                ถัดไป →
            </button>
        </div>
    </div>
</div>
```

### Form Components

```html
<!-- Text Input with Label -->
<div x-data="{ focused: false }">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Token Name <span class="text-red-500">*</span>
    </label>
    <input
        type="text"
        name="token_name"
        value="{{ old('token_name', $config->token_name) }}"
        @focus="focused = true"
        @blur="focused = false"
        class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
               focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
               dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        :class="focused ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'"
        placeholder="e.g., My Awesome Token"
        required
    />
    @error('token_name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Toggle Switch (Alpine.js) -->
<div x-data="{ enabled: {{ $config->is_burnable ? 'true' : 'false' }} }">
    <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Burnable</span>
            <p class="text-xs text-gray-500 dark:text-gray-400">สามารถเผาเหรียญได้</p>
        </div>

        <div @click="enabled = !enabled" class="relative">
            <input type="checkbox" name="is_burnable" :checked="enabled" class="sr-only" />
            <div class="w-14 h-8 rounded-full transition-colors duration-300"
                 :class="enabled ? 'bg-gradient-to-r from-blue-500 to-purple-600' : 'bg-gray-300 dark:bg-gray-600'">
            </div>
            <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full shadow-lg transition-transform duration-300"
                 :class="enabled ? 'transform translate-x-6' : ''">
            </div>
        </div>
    </label>
</div>
```

### Alert Messages

```html
<!-- Success Alert -->
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6">
    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-r-xl shadow-lg">
        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
        <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">×</button>
    </div>
</div>

<!-- Error Alert -->
<div x-data="{ show: true }" x-show="show" x-transition class="mb-6">
    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500 rounded-r-xl shadow-lg">
        <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
        <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">×</button>
    </div>
</div>
```

---

## 🔌 API Documentation

### Check Prerequisites

```http
GET /admin/tpix/deployment/api/check-prerequisites

Headers:
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "rpc_node": {
      "passed": true,
      "message": "เชื่อมต่อ RPC Node สำเร็จ",
      "details": {
        "block_number": "1234567",
        "chain_id": "7000",
        "rpc_url": "http://localhost:8545"
      }
    },
    "wallet": {
      "passed": true,
      "message": "Wallet พร้อมใช้งาน",
      "details": {
        "address": "0x1234...5678",
        "balance": "100.0000 TPIX"
      }
    },
    "services": {
      "passed": true,
      "message": "บริการทั้งหมดพร้อมใช้งาน",
      "services": {
        "redis": {"running": true, "message": "Redis ทำงานปกติ"},
        "database": {"running": true, "message": "Database เชื่อมต่อสำเร็จ"},
        "queue": {"running": true, "message": "Queue Worker พร้อมใช้งาน"}
      }
    },
    "environment": {
      "passed": true,
      "message": "Environment พร้อมใช้งาน",
      "checks": {
        "php_version": {"passed": true, "value": "8.2.0", "required": "8.1+"},
        "laravel_version": {"passed": true, "value": "11.0.0", "required": "11.x"},
        "php_extensions": {"passed": true, "missing": []},
        "permissions": {"passed": true, "not_writable": []}
      }
    },
    "all_passed": true
  }
}

Response 500:
{
  "success": false,
  "message": "Error message"
}
```

---

## 🚀 การใช้งาน

### 1. เข้าสู่ระบบ Wizard

```
1. เข้า Admin Dashboard
2. ไปที่ "TPIX" → "Deployment Wizard"
   URL: /admin/tpix/deployment
3. คลิก "สร้าง Configuration ใหม่"
4. กรอกชื่อ Configuration (e.g., "My Token Project")
5. คลิก "เริ่มต้น"
```

### 2. ทำตาม 7 Steps

**ระยะเวลาโดยประมาณ:** 20-30 นาที (ถ้าเตรียมข้อมูลครบ)

```
Step 1: Prerequisites       → 2-3 นาที (ตรวจสอบอัตโนมัติ)
Step 2: Token Config        → 5 นาที (กรอกฟอร์ม)
Step 3: Tokenomics          → 5 นาที (กำหนดเศรษฐศาสตร์)
Step 4: Smart Contract      → 3 นาที (เลือก features)
Step 5: Deploy & Verify     → 5 นาที (รอ blockchain confirm)
Step 6: DEX Integration     → 5 นาที (สร้าง pool)
Step 7: Listing             → 5 นาที (submit ข้อมูล)
```

### 3. ตรวจสอบผลลัพธ์

```bash
# ดู contract บน explorer
open http://explorer.tpix.com/address/0xabcd...

# ทดสอบ swap บน DEX
open /dex/swap?pair=TPIX-MAT

# ตรวจสอบ pool
open /dex/pools

# Admin dashboard
open /admin/tpix/deployment
```

### 4. Marketing & Launch

```
1. รอ CMC/CoinGecko อนุมัติ (1-7 วัน)
2. โพสต์ข่าวบน Social Media
3. สร้าง Community (Telegram, Discord)
4. Airdrop สำหรับ Early Adopters
5. Partnership & Collaborations
```

---

## ❓ Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. Prerequisites Failed: RPC Node

**Symptom:**
```
❌ ไม่สามารถเชื่อมต่อ RPC Node ได้
```

**Solution:**
```bash
# 1. ตรวจสอบว่า node ทำงานหรือไม่
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'

# 2. ตรวจสอบ .env
TPIX_RPC_URL=http://localhost:8545  # ✅ ถูกต้อง
TPIX_RPC_URL=localhost:8545         # ❌ ผิด - ไม่มี http://

# 3. Start node
cd tpix-blockchain
./scripts/start-node.sh
```

#### 2. Prerequisites Failed: Wallet Balance

**Symptom:**
```
❌ Wallet มี TPIX ไม่เพียงพอ (ต้องการอย่างน้อย 1 TPIX)
```

**Solution:**
```bash
# 1. ตรวจสอบ balance
curl -X POST http://localhost:8545 \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc":"2.0",
    "method":"eth_getBalance",
    "params":["0xYourAddress", "latest"],
    "id":1
  }'

# 2. เติม TPIX เข้า wallet
# ใช้ faucet หรือโอนจาก wallet อื่น
```

#### 3. Deploy Failed: Gas Estimation

**Symptom:**
```
❌ Deploy ล้มเหลว: Gas estimation error
```

**Solution:**
```bash
# 1. ลด contract complexity
# - ลบ features ที่ไม่จำเป็นออก
# - ลด max supply

# 2. เพิ่ม gas limit
# ใน TpixDeploymentService.php
$estimatedGas = 3000000; // เพิ่มจาก 2500000

# 3. ตรวจสอบ contract code
# ดู syntax error ใน Step 4
```

#### 4. DEX Pool Creation Failed

**Symptom:**
```
❌ สร้าง Liquidity Pool ล้มเหลว
```

**Solution:**
```bash
# 1. ตรวจสอบว่า contract deploy แล้ว
if ($config->isDeployed()) {
    // OK
}

# 2. ตรวจสอบ balance
# ต้องมี TPIX และ Token เพียงพอ

# 3. ตรวจสอบ database
# tpix_liquidity_pools table ต้องมี
php artisan migrate:status
```

#### 5. CoinMarketCap Submission Rejected

**Symptom:**
```
❌ CMC ปฏิเสธการ list
```

**Solution:**
```
Common reasons:
1. Trading volume ต่ำเกินไป (ต้อง >$1,000/day)
2. Website ไม่ professional
3. ไม่มี whitepaper
4. Social media ไม่ active
5. Contract ไม่ verified

How to fix:
1. เพิ่ม trading volume (ทำการซื้อขายจริง)
2. ปรับปรุง website ให้ดูเป็นมืออาชีพ
3. เขียน whitepaper ที่มีคุณภาพ
4. Active บน social media
5. Verify contract บน explorer
```

---

## 📚 เอกสารเพิ่มเติม

### ไฟล์ที่เกี่ยวข้อง

```
/TPIX_TOKEN_SYSTEM.md           - TPIX ecosystem overview
/tpix-blockchain/README.md      - Blockchain setup guide
/.claude/V3_CODING_GUIDELINES.md - V3 coding standards
/.claude/V3_UI_DESIGN_SYSTEM.md  - V3 UI/UX guidelines
```

### External Links

- **TPIX Blockchain Explorer**: http://explorer.tpix.com
- **DEX Documentation**: /docs/dex
- **CoinMarketCap Listing Guide**: https://support.coinmarketcap.com/hc/en-us/articles/360043659351
- **CoinGecko Listing Guide**: https://www.coingecko.com/en/coins/submit

---

## 🎓 Best Practices

### ก่อน Deploy

1. ✅ **เตรียมข้อมูลให้ครบ**
   - Logo (PNG, 200x200px)
   - Website พร้อม SSL
   - Whitepaper (PDF)
   - Social media accounts

2. ✅ **ทดสอบบน Testnet ก่อน**
   - Deploy บน testnet เพื่อทดสอบ
   - ทดสอบการทำงานของ contract
   - ทดสอบ DEX swap

3. ✅ **Audit Smart Contract**
   - ให้บริษัท audit ตรวจสอบ
   - แก้ไข vulnerabilities
   - จัดเตรียม audit report

### หลัง Deploy

1. ✅ **Monitor อย่างต่อเนื่อง**
   - ดู trading volume
   - ตรวจสอบ transactions
   - Monitor gas prices

2. ✅ **Community Engagement**
   - ตอบคำถาม/feedback
   - Update progress
   - Announce news

3. ✅ **Marketing Campaigns**
   - Airdrops
   - Bounty programs
   - Partnerships
   - Exchange listings

### Security Tips

```
⚠️ ข้อควรระวัง

1. ❌ อย่าแชร์ Private Key
2. ❌ อย่า Deploy โดยไม่ Audit
3. ❌ อย่าใช้ Upgradeable ถ้าไม่จำเป็น
4. ✅ ใช้ Multi-Sig สำหรับ admin functions
5. ✅ Lock Liquidity ≥1 ปี
6. ✅ Verify Contract ทันทีหลัง deploy
7. ✅ Test ทุก feature ก่อน launch
```

---

## 📄 License

Proprietary - All Rights Reserved

---

## 📞 Support

**For issues or questions:**

- GitHub Issues: [Create Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- Email: support@tpix.com
- Documentation: [TPIX Docs](/docs/tpix)

---

**Built with ❤️ using Laravel 11 + Tailwind CSS + Alpine.js + TPIX Blockchain**

**ทีมพัฒนา**: Thaiprompt-Affiliate Development Team

**Last Updated**: 2025-01-21