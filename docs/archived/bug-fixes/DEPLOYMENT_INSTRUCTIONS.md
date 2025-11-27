# คำแนะนำสำหรับการ Deploy OCR/KYC Feature

## ปัญหาที่พบ

```
Warning: The lock file is not up to date with the latest changes in composer.json.
Required package "google/cloud-vision" is not present in the lock file.
```

## วิธีแก้ไข (ทำตามลำดับ)

### ขั้นตอนที่ 1: อัพเดท Composer Dependencies

รันคำสั่งนี้ใน **local environment** (ไม่ใช่บน production server):

```bash
cd /path/to/Thaiprompt-Affiliate

# ติดตั้ง/อัพเดท dependencies ทั้งหมด
composer update

# หรือติดตั้งเฉพาะ google/cloud-vision
composer require google/cloud-vision
```

### ขั้นตอนที่ 2: Commit composer.lock

หลังจากรัน `composer update` แล้ว ไฟล์ `composer.lock` จะถูกอัพเดท ให้ commit มันด้วย:

```bash
# ตรวจสอบการเปลี่ยนแปลง
git status

# เพิ่ม composer.lock
git add composer.lock

# Commit
git commit -m "chore: update composer.lock for google/cloud-vision dependency"

# Push to branch
git push origin claude/setup-google-ocr-api-011CUnVwmYYKGparJkfMV2Fb
```

### ขั้นตอนที่ 3: Deploy to Production

หลังจาก push แล้ว deploy ใหม่:

```bash
# บน production server
cd /path/to/production

# Pull changes
git pull origin claude/setup-google-ocr-api-011CUnVwmYYKGparJkfMV2Fb

# ติดตั้ง dependencies (ใช้ --no-dev สำหรับ production)
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## วิธีทางเลือก: ถ้ายังไม่มี Composer บน Local

หากคุณไม่มี Composer บนเครื่อง local สามารถรันบน production server ได้:

```bash
# SSH เข้า production server
cd /home/admin/domains/member123.thaiprompt.online/public_html/Thaiprompt-Affiliate

# Update composer dependencies
composer update google/cloud-vision --no-dev

# ถ้าเกิด memory error ให้เพิ่ม memory limit
COMPOSER_MEMORY_LIMIT=-1 composer update google/cloud-vision --no-dev

# Commit composer.lock (ถ้ามี git บน server)
git add composer.lock
git commit -m "chore: update composer.lock for google/cloud-vision"
git push origin claude/setup-google-ocr-api-011CUnVwmYYKGparJkfMV2Fb
```

## ตรวจสอบว่าติดตั้งสำเร็จ

```bash
# ตรวจสอบว่า package ติดตั้งแล้ว
composer show google/cloud-vision

# ควรเห็นข้อมูลแบบนี้:
# name     : google/cloud-vision
# version  : v1.7.x
# ...
```

## หลังจากติดตั้งเสร็จ

1. ✅ เข้าหน้า `/admin/settings/ocr`
2. ✅ อัปโหลดไฟล์ Google Service Account Key (JSON)
3. ✅ กดปุ่ม "ทดสอบการเชื่อมต่อ"
4. ✅ ควรเห็นข้อความ "เชื่อมต่อ Google Cloud Vision API สำเร็จ!"

## หมายเหตุสำคัญ

- ⚠️ **อย่า commit folder `vendor/`** - ใช้ `.gitignore` บล็อกอยู่แล้ว
- ⚠️ **ต้อง commit `composer.lock`** - เพื่อให้ production ใช้ version เดียวกับ development
- ⚠️ **ใช้ `composer install` บน production** - ไม่ใช่ `composer update`
- ⚠️ **ใช้ `--no-dev` flag** - เพื่อไม่ติดตั้ง dev dependencies บน production

## ถ้ายังมีปัญหา

### Error: PHP Fatal error: Failed opening required 'vendor/autoload.php'

```bash
# ลบ vendor ทั้งหมดแล้วติดตั้งใหม่
rm -rf vendor/
composer install --no-dev
```

### Error: Composer memory limit

```bash
# เพิ่ม memory limit
php -d memory_limit=-1 /usr/bin/composer install --no-dev
```

### Error: Permission denied

```bash
# แก้ไข permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## ติดต่อ Support

หากยังมีปัญหา กรุณาส่ง error log มาที่ GitHub Issues
