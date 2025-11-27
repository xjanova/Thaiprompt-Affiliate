# TPIX Staking System - Deployment Guide

## คำอธิบายปัญหา (Problem Description)

เกิดข้อผิดพลาด `Table 'admin_mlmtestthai.tpix_stakes' doesn't exist` เมื่อเข้าใช้งานหน้า Staking ในระบบ

**สาเหตุ:**
- Migration สำหรับตาราง `tpix_stakes` และ `tpix_staking_pools` มีอยู่แล้วในโค้ด
- แต่ยังไม่ได้ทำการ run migration บนเซิร์ฟเวอร์ production/staging
- ไม่มีข้อมูล Seeder สำหรับ Staking Pools เริ่มต้น

## การแก้ไข (Solution)

### 1. ไฟล์ที่เกี่ยวข้อง

#### Migration Files:
- `database/migrations/2025_01_15_000004_create_tpix_staking_table.php`
  - สร้างตาราง `tpix_staking_pools`
  - สร้างตาราง `tpix_stakes`

#### Seeder Files (ใหม่):
- `database/seeders/TPIXStakingPoolSeeder.php` - สร้าง Staking Pools เริ่มต้น 5 แบบ

#### Model Files (มีอยู่แล้ว):
- `app/Models/TPIXStakingPool.php`
- `app/Models/TPIXStake.php`

### 2. โครงสร้างตาราง (Database Schema)

#### ตาราง `tpix_staking_pools`

สำหรับเก็บข้อมูล Staking Pools ต่างๆ

```sql
CREATE TABLE tpix_staking_pools (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    token_id BIGINT NOT NULL,
    creator_id BIGINT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    contract_address VARCHAR(42),

    -- Staking Parameters
    apy DECIMAL(8,2) NOT NULL,
    lock_period_days INT NOT NULL,
    min_stake_amount DECIMAL(30,8) NOT NULL,
    max_stake_amount DECIMAL(30,8),

    -- Pool Limits
    pool_cap DECIMAL(30,8),
    total_staked DECIMAL(30,8) DEFAULT 0,
    max_stakers INT,
    current_stakers INT DEFAULT 0,

    -- Rewards
    reward_token_type ENUM('same', 'tpix', 'other') DEFAULT 'same',
    reward_token_id BIGINT,
    total_rewards_distributed DECIMAL(30,8) DEFAULT 0,
    pending_rewards DECIMAL(30,8) DEFAULT 0,

    -- Status
    status ENUM('draft', 'active', 'paused', 'ended') DEFAULT 'draft',
    starts_at TIMESTAMP,
    ends_at TIMESTAMP,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    FOREIGN KEY (token_id) REFERENCES tpix_tokens(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id)
);
```

#### ตาราง `tpix_stakes`

สำหรับเก็บข้อมูล Stakes ของผู้ใช้แต่ละคน

```sql
CREATE TABLE tpix_stakes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pool_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    token_id BIGINT NOT NULL,

    -- Stake Information
    amount DECIMAL(30,8) NOT NULL,
    rewards_earned DECIMAL(30,8) DEFAULT 0,
    rewards_claimed DECIMAL(30,8) DEFAULT 0,
    rewards_pending DECIMAL(30,8) DEFAULT 0,

    -- Timing
    staked_at TIMESTAMP,
    unlock_at TIMESTAMP,
    last_reward_claim_at TIMESTAMP,

    -- Status
    status ENUM('active', 'unstaked', 'slashed') DEFAULT 'active',
    unstaked_at TIMESTAMP,

    -- Blockchain
    stake_tx_hash VARCHAR(255),
    unstake_tx_hash VARCHAR(255),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (pool_id) REFERENCES tpix_staking_pools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (token_id) REFERENCES tpix_tokens(id)
);
```

### 3. Staking Pools เริ่มต้น

Seeder จะสร้าง 5 Staking Pools:

| Pool Name | APY | Lock Period | Min Amount | Max Amount |
|-----------|-----|-------------|------------|------------|
| TPIX Flexible - รายวัน | 5% | 0 วัน (ถอนได้ทันที) | 10 TPIX | 100,000 TPIX |
| TPIX Locked 30 วัน | 12% | 30 วัน | 100 TPIX | 500,000 TPIX |
| TPIX Locked 90 วัน | 18% | 90 วัน | 500 TPIX | 1,000,000 TPIX |
| TPIX Locked 180 วัน | 25% | 180 วัน | 1,000 TPIX | 5,000,000 TPIX |
| TPIX Locked 365 วัน | 36% | 365 วัน | 5,000 TPIX | 10,000,000 TPIX |

## ขั้นตอนการ Deploy

### สำหรับ Development Environment

```bash
# 1. Pull โค้ดล่าสุด
git pull origin claude/fix-missing-stakes-table-012N63akpaiZdXpDUqAijgTo

# 2. Run migrations
php artisan migrate

# 3. Run seeders
php artisan db:seed --class=TPIXStakingPoolSeeder

# หรือ run seeder ทั้งหมด
php artisan db:seed
```

### สำหรับ Production/Staging Environment

```bash
# 1. Backup database ก่อน
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Pull โค้ดล่าสุด
cd /path/to/Thaiprompt-Affiliate
git pull origin main  # หรือ branch ที่จะ deploy

# 3. Run migrations (จะสร้างตารางเท่านั้น ไม่กระทบข้อมูลเดิม)
php artisan migrate --force

# 4. Run seeder (เฉพาะ TPIXStakingPoolSeeder)
php artisan db:seed --class=TPIXStakingPoolSeeder --force

# 5. Clear cache
php artisan optimize:clear

# 6. Optimize application
php artisan optimize
```

### ใช้ Deployment Script (แนะนำ)

```bash
# Run deployment script (มี auto-retry)
./deploy.sh

# Script จะทำสิ่งต่อไปนี้อัตโนมัติ:
# - เข้า maintenance mode
# - Backup database
# - Pull โค้ด
# - Update dependencies (composer, npm)
# - Run migrations
# - Run seeders
# - Build assets
# - Clear & optimize cache
# - ออกจาก maintenance mode
```

## การตรวจสอบหลัง Deploy

### 1. ตรวจสอบว่าตารางถูกสร้างแล้ว

```bash
php artisan tinker
```

```php
// ตรวจสอบ tables
Schema::hasTable('tpix_staking_pools'); // ต้องได้ true
Schema::hasTable('tpix_stakes');        // ต้องได้ true

// ตรวจสอบจำนวน Staking Pools
\App\Models\TPIXStakingPool::count();   // ต้องได้ 5

// ตรวจสอบ Pools
\App\Models\TPIXStakingPool::all();
```

### 2. ทดสอบผ่าน Web Interface

```bash
# เข้าใช้งานหน้า Staking
https://[your-domain]/user/staking

# ควรเห็น:
# - รายการ Staking Pools ทั้ง 5 แบบ
# - ไม่มี error Table doesn't exist
# - สามารถดูรายละเอียดแต่ละ Pool ได้
```

### 3. ตรวจสอบ Logs

```bash
# ตรวจสอบ Laravel logs
tail -f storage/logs/laravel.log

# ตรวจสอบ Deployment logs
tail -f storage/logs/deployment.log
```

## Rollback (กรณีมีปัญหา)

### วิธีที่ 1: Rollback Migration

```bash
# Rollback migration ล่าสุด
php artisan migrate:rollback --step=1

# ลบตาราง tpix_stakes และ tpix_staking_pools
```

### วิธีที่ 2: Restore Database Backup

```bash
# Restore จาก backup
mysql -u [username] -p [database_name] < backup_YYYYMMDD_HHMMSS.sql
```

### วิธีที่ 3: ใช้ Rollback Script

```bash
# Run rollback script
./rollback.sh

# ระบุจำนวน commits ที่ต้องการ rollback
```

## หมายเหตุสำคัญ

### ความสัมพันธ์ของตาราง (Dependencies)

Migration `2025_01_15_000004_create_tpix_staking_table.php` ต้องการตารางเหล่านี้:
- ✅ `users` - สำหรับ user_id และ creator_id
- ✅ `tpix_tokens` - สำหรับ token_id และ reward_token_id

**ตรวจสอบก่อน Deploy:**

```bash
php artisan tinker
```

```php
// ตรวจสอบว่าตารางที่ต้องการมีอยู่
Schema::hasTable('users');       // ต้องได้ true
Schema::hasTable('tpix_tokens'); // ต้องได้ true

// ตรวจสอบว่ามี TPIX Token
\App\Models\TPIXToken::where('symbol', 'TPIX')->exists(); // ต้องได้ true
```

### Migration Features

Migration มี safety features:
- ✅ `Schema::hasTable()` - ตรวจสอบก่อนสร้างตาราง (ไม่ error ถ้าตารางมีอยู่แล้ว)
- ✅ Foreign key constraints - รักษาความสมบูรณ์ของข้อมูล
- ✅ Cascade delete - ลบข้อมูลที่เกี่ยวข้องอัตโนมัติ
- ✅ Indexes - เพิ่มประสิทธิภาพการ query

### Seeder Features

Seeder มี safety features:
- ✅ Idempotent - Run ซ้ำได้โดยไม่ duplicate ข้อมูล
- ✅ Auto-create TPIX Token - สร้างอัตโนมัติถ้ายังไม่มี
- ✅ Validation - ตรวจสอบข้อมูลก่อนสร้าง

## การแก้ไขเพิ่มเติม (Future Improvements)

### 1. เพิ่ม Menu Item สำหรับ Staking

```php
// database/seeders/StakingMenuSeeder.php
// สร้าง menu items สำหรับระบบ Staking
```

### 2. เพิ่ม API Endpoints

```php
// routes/api.php
Route::prefix('staking')->group(function () {
    Route::get('/pools', [StakingApiController::class, 'pools']);
    Route::post('/stake', [StakingApiController::class, 'stake']);
    Route::post('/unstake', [StakingApiController::class, 'unstake']);
});
```

### 3. เพิ่ม Admin Interface

```php
// routes/admin.php
Route::prefix('staking')->group(function () {
    Route::resource('pools', StakingPoolController::class);
    Route::get('stakes', [StakingController::class, 'stakes']);
});
```

## Support & Contact

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- 📧 Email: support@thaiprompt.com
- 📱 LINE: @thaiprompt
- 💬 GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues

---

**Version:** 1.0
**Last Updated:** 2025-11-14
**Author:** Development Team
**Related Issue:** Fix missing tpix_stakes table error
