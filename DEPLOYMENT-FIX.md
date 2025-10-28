# การแก้ไขปัญหา Deployment Conflict / Fix Deployment Conflict

## ปัญหา / Problem

เมื่อรัน deploy script บน production server เกิด error:

```
error: The following untracked working tree files would be overwritten by merge:
        bootstrap/cache/packages.php
        bootstrap/cache/services.php
        composer.lock
        config/permission.php
        package-lock.json
Please move or remove them before you merge.
```

## สาเหตุ / Root Cause

ไฟล์เหล่านี้มีอยู่บน production server เป็น untracked files (ไฟล์ที่ git ไม่ได้ติดตาม) แต่ใน commit ใหม่ที่จะ pull มา ไฟล์เหล่านี้ถูกเพิ่มเข้าไปใน git repository แล้ว Git จึงไม่สามารถ overwrite ไฟล์ untracked เหล่านี้ได้โดยอัตโนมัติ

## วิธีแก้ไข / Solution

### ขั้นตอนที่ 1: รัน Fix Script บน Production Server

1. เข้าไปที่ directory ของโปรเจค:
```bash
cd /path/to/your/Thaiprompt-Affiliate
```

2. Pull fix script ล่าสุด:
```bash
git fetch origin claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE
git checkout origin/claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE -- fix-deployment-conflict.sh
chmod +x fix-deployment-conflict.sh
```

3. รัน fix script:
```bash
./fix-deployment-conflict.sh
```

Script นี้จะ:
- สำรองไฟล์ที่ขัดแย้งไว้ใน directory `deployment-backup-YYYYMMDD-HHMMSS`
- ลบไฟล์ที่ขัดแย้งออก
- เตรียมระบบให้พร้อมสำหรับ deployment

### ขั้นตอนที่ 2: รัน Deployment Script

หลังจากรัน fix script แล้ว ให้รัน deployment script ตามปกติ:

```bash
./deploy.sh
```

### ขั้นตอนที่ 3: ตรวจสอบผลลัพธ์

หาก deployment สำเร็จ:
- เว็บไซต์ควรทำงานได้ปกติ
- สามารถลบ backup directory ได้: `rm -rf deployment-backup-*`

หากมีปัญหา:
- Restore จาก backup: `cp -r deployment-backup-*/* ./`
- ติดต่อทีมพัฒนาเพื่อขอความช่วยเหลือ

## ไฟล์ที่ถูกลบและเหตุผล / Files Removed and Reasons

1. **bootstrap/cache/packages.php** - ไฟล์ที่ Laravel generate อัตโนมัติ จะถูกสร้างใหม่เมื่อจำเป็น
2. **bootstrap/cache/services.php** - ไฟล์ที่ Laravel generate อัตโนมัติ จะถูกสร้างใหม่เมื่อจำเป็น
3. **composer.lock** - จะถูกแทนที่ด้วยเวอร์ชันจาก git repository
4. **config/permission.php** - จะถูกแทนที่ด้วยเวอร์ชันจาก git repository
5. **package-lock.json** - จะถูกแทนที่ด้วยเวอร์ชันจาก git repository

ไฟล์เหล่านี้จะถูก pull มาจาก git repository พร้อมกับ deployment และ Laravel จะ regenerate ไฟล์ cache ที่จำเป็นเอง

## Alternative Solution (สำหรับ Advanced Users)

หากต้องการแก้ไขด้วยตนเอง สามารถรัน:

```bash
# Backup files (optional)
mkdir deployment-backup
cp bootstrap/cache/packages.php deployment-backup/ 2>/dev/null || true
cp bootstrap/cache/services.php deployment-backup/ 2>/dev/null || true
cp composer.lock deployment-backup/ 2>/dev/null || true
cp config/permission.php deployment-backup/ 2>/dev/null || true
cp package-lock.json deployment-backup/ 2>/dev/null || true

# Remove conflicting files
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f composer.lock
rm -f config/permission.php
rm -f package-lock.json

# Now run deployment
./deploy.sh
```

---

# ปัญหาที่ 2: Composer Permission Error / Problem 2: Composer Permission Error

## ปัญหา / Problem

เมื่อรัน deployment script composer ไม่สามารถลบ development packages ได้:

```
Could not delete /home/admin/domains/.../vendor/theseer/tokenizer/README.md:
Uninstall of [package] failed
```

## สาเหตุ / Root Cause

Composer พยายามลบ development packages (phpunit, faker, mockery, etc.) เพื่อติดตั้งเฉพาะ production packages แต่ไม่มีสิทธิ์ในการลบไฟล์ใน vendor directory เนื่องจาก:
- File permissions ไม่ถูกต้อง
- File ownership เป็นของ user อื่น
- Directory มี read-only permissions

## วิธีแก้ไข / Solution

### วิธีที่ 1: ใช้ Fix Script (แนะนำ / Recommended)

```bash
# 1. เข้าไปที่ directory ของโปรเจค
cd /path/to/your/Thaiprompt-Affiliate

# 2. ดึง fix script มาจาก repository
git fetch origin claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE
git checkout origin/claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE -- fix-composer-permissions.sh
chmod +x fix-composer-permissions.sh

# 3. รัน fix script (จะมี menu ให้เลือก)
./fix-composer-permissions.sh
```

Script มี 2 options:
1. **Fix permissions and retry** - แก้ไข permissions แล้วลอง install ใหม่ (เร็วกว่า)
2. **Clean install** - ลบ vendor ทั้งหมดแล้ว install ใหม่ (ปลอดภัยกว่า)

### วิธีที่ 2: แก้ไขด้วยตนเอง / Manual Fix

```bash
# ขั้นตอนที่ 1: แก้ไข permissions
sudo chmod -R u+w vendor/
sudo chown -R $(whoami):$(whoami) vendor/

# ขั้นตอนที่ 2: Clear cache
composer clear-cache

# ขั้นตอนที่ 3: ลองติดตั้งใหม่
composer install --no-dev --optimize-autoloader --no-interaction

# ถ้ายังไม่ได้ ให้ลบ vendor แล้วติดตั้งใหม่
rm -rf vendor/
composer install --no-dev --optimize-autoloader --no-interaction
```

### วิธีที่ 3: แก้ไขใน deploy.sh

Deploy script ได้รับการปรับปรุงให้แก้ไข permissions อัตโนมัติแล้ว:
- ตรวจสอบและแก้ไข permissions ก่อน composer install
- มี error handling และ retry mechanism
- แสดง error message ที่ชัดเจนหากมีปัญหา

ดึง version ล่าสุดของ deploy.sh:
```bash
git fetch origin claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE
git checkout origin/claude/fix-503-service-unavailable-011CUa1Nf68Tx2C8JwQ5K6wE -- deploy.sh
chmod +x deploy.sh
```

## ป้องกันปัญหาในอนาคต / Prevention

1. **Set correct permissions หลัง deployment:**
   ```bash
   chmod -R u+w vendor/
   chown -R $USER:$USER vendor/
   ```

2. **ใช้ deployment script ที่ปรับปรุงแล้ว** - มีการแก้ไข permissions อัตโนมัติ

3. **Check permissions ก่อน deploy:**
   ```bash
   ls -la vendor/ | head -20
   ```

## ถ้ายังมีปัญหา / If Issues Persist

หากยังคงมีปัญหา ลองวิธีนี้:

```bash
# 1. Backup composer.lock
cp composer.lock composer.lock.backup

# 2. ลบ vendor directory ทั้งหมด
sudo rm -rf vendor/

# 3. Clear composer cache
composer clear-cache
rm -rf ~/.composer/cache

# 4. ติดตั้งใหม่
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Optimize Laravel
php artisan optimize
```

---

## ติดต่อ / Contact

หากยังมีปัญหาหรือข้อสงสัย กรุณาติดต่อทีมพัฒนา
