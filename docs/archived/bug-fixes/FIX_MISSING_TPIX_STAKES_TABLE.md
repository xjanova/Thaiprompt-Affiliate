# คำแนะนำแก้ไขปัญหา: Table 'tpix_stakes' ไม่พบ

## 🔍 สาเหตุของปัญหา

ตาราง `tpix_stakes` ยังไม่ได้ถูกสร้างในฐานข้อมูล production ทำให้หน้า Staking แสดง error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'admin_mlmtestthai.tpix_stakes' doesn't exist
```

## ✅ การตรวจสอบ

Migration file สำหรับสร้างตาราง TPIX Staking มีอยู่แล้วที่:
- **ไฟล์**: `database/migrations/2025_01_15_000004_create_tpix_staking_table.php`
- **สถานะ**: ✅ Committed แล้ว
- **ตารางที่จะสร้าง**:
  - `tpix_staking_pools` - ตาราง staking pools
  - `tpix_stakes` - ตาราง user stakes

## 🚀 วิธีแก้ไข (สำหรับ Production Server)

### วิธีที่ 1: ใช้ Migration Script (แนะนำ)

1. **SSH เข้า Production Server**:
   ```bash
   ssh user@member123.thaiprompt.online
   cd /path/to/Thaiprompt-Affiliate
   ```

2. **ดึง Code ล่าสุด**:
   ```bash
   git fetch origin
   git pull origin claude/fix-missing-stakes-table-012x6iyJZD4KL6gero1karun
   ```

3. **รัน Migration Script**:
   ```bash
   ./run-migrations.sh
   ```

   Script จะ:
   - ✅ ตรวจสอบการเชื่อมต่อฐานข้อมูล
   - ✅ แสดงสถานะ migration ปัจจุบัน
   - ✅ Backup ฐานข้อมูลก่อน migrate
   - ✅ รัน pending migrations
   - ✅ ตรวจสอบความสมบูรณ์ของ migration

### วิธีที่ 2: ใช้ Deployment Script

```bash
./deploy.sh claude/fix-missing-stakes-table-012x6iyJZD4KL6gero1karun
```

Deployment script จะทำทุกอย่างอัตโนมัติ รวมถึง:
- ✅ Pull code ล่าสุด
- ✅ Install dependencies
- ✅ Run migrations
- ✅ Clear cache
- ✅ Optimize application

### วิธีที่ 3: Manual Migration (ถ้า Script ไม่ทำงาน)

```bash
# 1. Backup database ก่อน
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. รัน migration
php artisan migrate --force

# 3. ตรวจสอบสถานะ
php artisan migrate:status
```

## 📋 ตรวจสอบว่าแก้ไขสำเร็จ

1. **ตรวจสอบตารางในฐานข้อมูล**:
   ```sql
   SHOW TABLES LIKE 'tpix_%';
   ```
   ควรเห็น:
   - `tpix_staking_pools`
   - `tpix_stakes`

2. **ตรวจสอบผ่าน Artisan**:
   ```bash
   php artisan migrate:status | grep tpix_staking
   ```
   ควรเห็นสถานะ "Ran"

3. **ทดสอบหน้าเว็บ**:
   - เข้า: `https://member123.thaiprompt.online/user/staking`
   - ควรแสดงหน้า Staking ได้ปกติ (ไม่มี error)

## 🔧 โครงสร้างตาราง

### tpix_staking_pools
ตารางสำหรับเก็บข้อมูล Staking Pools:
- Pool information (name, description, contract_address)
- Staking parameters (APY, lock period, min/max stake)
- Pool limits (pool cap, max stakers)
- Reward configuration
- Status & timing

### tpix_stakes
ตารางสำหรับเก็บข้อมูล User Stakes:
- User stake information (amount, rewards)
- Timing (staked_at, unlock_at, unstaked_at)
- Status (active, unstaked, slashed)
- Blockchain transaction hashes

## 🔄 Rollback (ถ้าเกิดปัญหา)

หากเกิดปัญหาหลังจาก migrate:

```bash
# 1. Rollback migration
php artisan migrate:rollback

# 2. หรือ Restore จาก backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

## 📞 ติดต่อ Support

หากมีปัญหาในการแก้ไข:
1. ตรวจสอบ logs: `tail -f storage/logs/laravel.log`
2. ตรวจสอบ deployment logs: `tail -f storage/logs/deployment.log`
3. ติดต่อ Development Team

## 📝 หมายเหตุ

- ⚠️ **สำคัญ**: ควร backup ฐานข้อมูลก่อนทำการ migrate เสมอ
- ✅ Migration file มีการตรวจสอบ `Schema::hasTable()` อยู่แล้ว จึงปลอดภัยที่จะรันซ้ำ
- ✅ การ migrate ไม่ส่งผลกระทบต่อข้อมูลเดิมในระบบ
- ✅ หลังจากแก้ไขเสร็จ ผู้ใช้จะสามารถใช้งานระบบ Staking ได้ทันที

---

**วันที่สร้าง**: 2025-11-14
**Branch**: `claude/fix-missing-stakes-table-012x6iyJZD4KL6gero1karun`
**สถานะ**: ✅ พร้อม deploy
