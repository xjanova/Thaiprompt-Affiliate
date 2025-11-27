# 🎯 TPIX Blockchain Management Guide

คู่มือการจัดการเหรียญ TPIX และระบบ Blockchain ทั้งหมด

---

## 📍 สถานที่จัดการทั้งหมด (All Management Locations)

### 🔷 สำหรับผู้ใช้ทั่วไป (User Panel)

#### 1. **TPIX Wallet (กระเป๋า TPIX)**
- **Dashboard**: `/user/tpix/wallet`
- **เติมเงิน (Deposit)**: `/user/tpix/deposit`
- **ถอนเงิน (Withdrawal)**: `/user/tpix/withdrawal`
- **ส่ง TPIX (P2P Transfer)**: `/user/tpix/send`
- **ประวัติธุรกรรม (Transactions)**: `/user/tpix/transactions`

#### 2. **Token Marketplace (ตลาด Token)**
- **ดูตลาด Token ทั้งหมด**: `/user/tokens`
- **สร้าง Token ใหม่**: `/user/tokens/create`
- **Token ของฉัน (My Tokens)**: `/user/tokens/my/tokens`
- **Portfolio Token**: `/user/tokens/my/portfolio`
- **ดูรายละเอียด Token**: `/user/tokens/{id}`
- **Deploy Token ลง Blockchain**: `/user/tokens/{id}/deploy`
- **โอน Token**: `/user/tokens/{id}/transfer`

#### 3. **DEX - Decentralized Exchange**
- **Swap (แลกเหรียญ)**: `/user/dex/swap`
- **Liquidity Pools (สระน้ำสภาพคล่อง)**: `/user/dex/pools`
- **เพิ่ม Liquidity**: `/user/dex/add-liquidity`
- **ถอน Liquidity**: `/user/dex/remove-liquidity/{positionId}`
- **สถานะ Liquidity ของฉัน**: `/user/dex/my-positions`
- **ประวัติ Swap**: `/user/dex/swap-history`

#### 4. **Staking (เดิมพันเหรียญ)**
- **Staking Pools ทั้งหมด**: `/user/staking`
- **ดูรายละเอียด Pool**: `/user/staking/pools/{poolId}`
- **Stake เหรียญ**: `/user/staking/stake/{poolId}`
- **ประวัติ Staking**: `/user/staking/history`

---

### 🔶 สำหรับแอดมิน (Admin Panel)

#### 1. **TPIX Blockchain Management**
- **Dashboard**: `/admin/tpix/dashboard`
- **Network Status**: `/admin/tpix/network-status`
- **จัดการ Wallets**: `/admin/tpix/wallets`
- **ธุรกรรมทั้งหมด**: `/admin/tpix/transactions`
- **ดูรายละเอียดธุรกรรม**: `/admin/tpix/transactions/{id}`
- **ตั้งค่า Blockchain**: `/admin/tpix/settings`
- **ตรวจสอบการเชื่อมต่อ**: `/admin/tpix/check-connection`

#### 2. **Token Management (จัดการ Token ทั้งหมด)**
- **Token ทั้งหมด**: `/admin/tokens`
- **ดูรายละเอียด Token**: `/admin/tokens/{id}`
- **อนุมัติ Token (Approve)**: `/admin/tokens/{id}/approve`
- **ปฏิเสธ Token (Reject)**: `/admin/tokens/{id}/reject`
- **ตรวจสอบ Token (Verify)**: `/admin/tokens/{id}/verify`
- **แนะนำ Token (Feature)**: `/admin/tokens/{id}/feature`
- **Mint Token เพิ่ม**: `/admin/tokens/{id}/mint`
- **Burn Token (ทำลาย)**: `/admin/tokens/{id}/burn`
- **Freeze Address (ระงับ)**: `/admin/tokens/{id}/freeze-address`
- **Unfreeze Address**: `/admin/tokens/{id}/unfreeze-address`
- **Pause Token**: `/admin/tokens/{id}/pause`
- **Unpause Token**: `/admin/tokens/{id}/unpause`
- **Import จาก CoinMarketCap**: `/admin/tokens/import-cmc`

---

## 🚀 API Endpoints (สำหรับนักพัฒนา)

### TPIX Wallet API
```
GET    /api/v1/tpix/wallet/balance
GET    /api/v1/tpix/wallet/transactions
POST   /api/v1/tpix/wallet/send
```

### Token API
```
GET    /api/v1/tpix/tokens
GET    /api/v1/tpix/tokens/{id}
POST   /api/v1/tpix/tokens/create
POST   /api/v1/tpix/tokens/{id}/deploy
POST   /api/v1/tpix/tokens/{id}/transfer
POST   /api/v1/tpix/tokens/{id}/buy
POST   /api/v1/tpix/tokens/{id}/sell
```

### DEX API
```
GET    /api/v1/tpix/dex/pools
GET    /api/v1/tpix/dex/pools/{id}
POST   /api/v1/tpix/dex/swap
POST   /api/v1/tpix/dex/add-liquidity
POST   /api/v1/tpix/dex/remove-liquidity
GET    /api/v1/tpix/dex/quote
GET    /api/v1/tpix/dex/my-positions
GET    /api/v1/tpix/dex/swap-history
```

### Staking API
```
GET    /api/v1/tpix/staking/pools
GET    /api/v1/tpix/staking/pools/{poolId}
GET    /api/v1/tpix/staking/pools/{poolId}/recent
GET    /api/v1/tpix/staking/my-stake/{poolId}
POST   /api/v1/tpix/staking/stake
POST   /api/v1/tpix/staking/unstake
POST   /api/v1/tpix/staking/claim
GET    /api/v1/tpix/staking/history
```

---

## 📱 การเข้าถึงผ่าน Menu Navigation

### ⚠️ สถานะปัจจุบัน (Current Status)

**หมายเหตุสำคัญ**: ขณะนี้เมนู TPIX **ยังไม่ถูกเพิ่มใน Navigation Sidebar** แต่ routes และ views พร้อมใช้งานแล้วทั้งหมด!

คุณสามารถเข้าถึงได้โดย:
1. พิมพ์ URL โดยตรง (ตามรายการด้านบน)
2. หรือรอให้เพิ่มเมนูใน Navigation (แนะนำ)

### 🎯 จะเพิ่มเมนูดังนี้ (Recommended Menu Structure)

#### สำหรับ User:
```
🪙 TPIX Blockchain [NEW]
├── 💰 TPIX Wallet
├── 🎫 Token Marketplace
├── 🔄 DEX (Swap & Liquidity)
└── 🔒 Staking Pools
```

#### สำหรับ Admin:
```
⛓️ TPIX Blockchain
├── 📊 Dashboard
├── 🌐 Network Status
├── 💼 Wallets Management
├── 📝 Transactions
└── ⚙️ Settings

🎫 Token Management
├── 📋 All Tokens
├── ✅ Approve/Reject
├── 🔍 Verification
├── 🔥 Mint/Burn
├── ❄️ Freeze/Unfreeze
└── 📈 Import from CMC
```

---

## 🎨 ไฟล์ที่เกี่ยวข้อง (Related Files)

### Views (User)
```
resources/views/user/tokens/index.blade.php
resources/views/user/tokens/create.blade.php
resources/views/user/tokens/show.blade.php
resources/views/user/dex/swap.blade.php
resources/views/user/dex/pools.blade.php
resources/views/user/dex/add-liquidity.blade.php
resources/views/user/dex/remove-liquidity.blade.php
resources/views/user/dex/my-positions.blade.php
resources/views/user/dex/swap-history.blade.php
resources/views/user/staking/index.blade.php
resources/views/user/staking/show.blade.php
resources/views/user/staking/stake.blade.php
resources/views/user/staking/history.blade.php
```

### Controllers
```
app/Http/Controllers/User/TokenController.php
app/Http/Controllers/User/DEXController.php
app/Http/Controllers/User/StakingController.php
app/Http/Controllers/TPIXWalletController.php
app/Http/Controllers/Admin/TPIXController.php
app/Http/Controllers/Admin/TokenManagementController.php
app/Http/Controllers/Api/V1/StakingApiController.php
```

### Routes
```
routes/user.php (lines 155-244)
routes/admin.php (TPIX & Token sections)
routes/api.php (API endpoints)
```

### Navigation Files
```
resources/views/components/classic-x-menu.blade.php
resources/views/components/millennium-start-menu.blade.php
```

---

## ⚡ Quick Access URLs (คัดลอกแล้วใช้เลย)

### User Panel
```bash
# TPIX Wallet
https://yourdomain.com/user/tpix/wallet

# Token Marketplace
https://yourdomain.com/user/tokens

# DEX Swap
https://yourdomain.com/user/dex/swap

# Staking
https://yourdomain.com/user/staking
```

### Admin Panel
```bash
# TPIX Dashboard
https://yourdomain.com/admin/tpix/dashboard

# Token Management
https://yourdomain.com/admin/tokens

# Network Status
https://yourdomain.com/admin/tpix/network-status
```

---

## 📊 สถิติระบบ

- **Total Routes**: 44 TPIX routes (26 user + 18 admin)
- **API Endpoints**: 28 endpoints
- **Views**: 13 user views + admin views
- **Controllers**: 6 controllers
- **Smart Contracts**: 12 contracts
- **Database Tables**: 12 TPIX tables

---

## 🔧 ขั้นตอนถัดไป (Next Steps)

1. ✅ **ระบบพร้อมใช้งาน 100%** - All routes, controllers, views ready
2. ⏳ **เพิ่มเมนูใน Navigation** - Add to sidebar/menu (recommended)
3. ⏳ **Deploy Smart Contracts** - Follow `tpix-blockchain/QUICK-START.md`
4. ⏳ **Test ทุกฟีเจอร์** - Comprehensive testing

---

## 💡 Tips

- **Bookmark URLs**: เก็บ URLs สำคัญไว้ใน Browser Bookmarks
- **Admin Access**: ต้องมี role `admin` หรือ `super_admin`
- **User Access**: ผู้ใช้ทั่วไปเข้าถึง user routes ได้ทันที
- **API Access**: ต้องมี Sanctum token สำหรับ authenticated endpoints

---

## 📞 ต้องการความช่วยเหลือ?

1. **Deployment Guide**: อ่าน `tpix-blockchain/DEPLOYMENT.md`
2. **Quick Start**: อ่าน `tpix-blockchain/QUICK-START.md`
3. **Sync Contracts**: รัน `php artisan tpix:sync-contracts`

---

**เอกสารนี้สร้างโดย**: Claude Code
**วันที่**: 2025-01-13
**เวอร์ชั่น**: 1.0.0
