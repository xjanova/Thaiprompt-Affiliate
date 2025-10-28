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

## ติดต่อ / Contact

หากยังมีปัญหาหรือข้อสงสัย กรุณาติดต่อทีมพัฒนา
