# 🚀 คู่มือการ Deploy TP-Affiliate

คู่มือการอัพเดทระบบจาก GitHub อย่างปลอดภัยและง่ายดาย

---

## 📋 สารบัญ

1. [เตรียมความพร้อม](#เตรียมความพร้อม)
2. [การ Deploy แบบ Auto](#การ-deploy-แบบ-auto)
3. [ขั้นตอนการ Deploy](#ขั้นตอนการ-deploy)
4. [การ Rollback](#การ-rollback)
5. [Troubleshooting](#troubleshooting)

---

## 🎯 เตรียมความพร้อม

### ความต้องการ

- ✅ Git repository ถูกตั้งค่าแล้ว
- ✅ ไฟล์ `.env` มีอยู่และถูกต้อง
- ✅ PHP และ Composer ติดตั้งแล้ว
- ✅ MySQL database พร้อมใช้งาน
- ✅ สิทธิ์ในการเขียนไฟล์ (storage, bootstrap/cache)

### ตรวจสอบก่อน Deploy

```bash
# เช็คว่า git repository สะอาด
git status

# เช็ค branch ปัจจุบัน
git branch

# เช็คว่ามี .env
ls -la .env

# เช็ค PHP version
php --version

# เช็ค Composer
composer --version
```

---

## 🚀 การ Deploy แบบ Auto

### วิธีที่ 1: Deploy จาก Main Branch (แนะนำ)

```bash
cd /path/to/Thaiprompt-Affiliate

# ให้สิทธิ์ execute (ครั้งแรก)
chmod +x deploy.sh

# รัน deployment script
./deploy.sh
```

### วิธีที่ 2: Deploy จาก Branch อื่น

```bash
# Deploy จาก branch ที่ระบุ
./deploy.sh develop

# หรือ
./deploy.sh feature/new-feature
```

### ตัวอย่าง Output

```
╔══════════════════════════════════════════════════╗
║   🚀 TP-Affiliate Deployment Script             ║
║   Safe Production Deployment                     ║
╚══════════════════════════════════════════════════╝

════════════════════════════════════════
  🔍 Pre-flight Checks
════════════════════════════════════════

✓ .env file found
✓ Git repository detected
ℹ Environment: production

════════════════════════════════════════
  📦 Deployment Process
════════════════════════════════════════

ℹ [1/14] Enabling maintenance mode...
✓ Maintenance mode enabled

ℹ [2/14] Creating database backup...
✓ Database backed up to: backups/db_backup_20251029_143022.sql

ℹ Current commit: a1b2c3d4
ℹ [3/14] Pulling latest code from git...
✓ Code updated to latest commit
ℹ New commit: e5f6g7h8

ℹ [4/14] Installing composer dependencies...
✓ Composer dependencies installed

ℹ [5/14] Clearing all caches...
✓ All caches cleared

ℹ [6/14] Running database migrations...
✓ Migrations completed

Run database seeders? (y/n) [n]: n
ℹ [7/14] Skipping database seeders

ℹ [8/14] Setting file permissions...
✓ Permissions set

ℹ [9/14] Caching configuration...
✓ Configuration cached

ℹ [10/14] Caching routes...
✓ Routes cached

ℹ [11/14] Caching views...
✓ Views cached

ℹ [12/14] Optimizing autoloader...
✓ Autoloader optimized

ℹ [13/14] Restarting services...
✓ Reloaded php8.2-fpm
✓ Queue workers restarted

ℹ [14/14] Disabling maintenance mode...
✓ Application is now live!

════════════════════════════════════════
  ✅ Deployment Completed Successfully!
════════════════════════════════════════

📊 Deployment Summary:

  Environment:   production
  Branch:        main
  Old Commit:    a1b2c3d4
  New Commit:    e5f6g7h8
  Time:          2025-10-29 14:32:15
  Backup:        backups/db_backup_20251029_143022.sql

ℹ 📋 Post-Deployment Checklist:
  □ Test the application in browser
  □ Check logs: tail -f storage/logs/laravel.log
  □ Monitor error logs: tail -f storage/logs/deployment.log
  □ Verify database migrations: php artisan migrate:status
  □ Check queue workers: php artisan queue:monitor

⚠ 🔄 Rollback Command (if needed):
  git reset --hard a1b2c3d4
  composer install --no-dev --optimize-autoloader
  php artisan migrate:rollback
  php artisan up

✓ Happy deploying! 🚀
```

---

## 📝 ขั้นตอนการ Deploy (14 Steps)

### 1. Pre-flight Checks (ตรวจสอบก่อน Deploy)

Script จะตรวจสอบ:
- ✅ มีไฟล์ `.env` หรือไม่
- ✅ เป็น Git repository หรือไม่
- ✅ `APP_ENV` ถูกต้องหรือไม่
- ✅ มี uncommitted changes หรือไม่

### 2. Maintenance Mode

```bash
php artisan down --retry=60 --render="errors::503"
```

- หน้าเว็บจะแสดง "503 Service Unavailable"
- Retry หลัง 60 วินาที
- ป้องกันการเข้าถึงระหว่าง deploy

### 3. Database Backup

- สร้าง backup อัตโนมัติใน `backups/`
- ชื่อไฟล์: `db_backup_YYYYMMDD_HHMMSS.sql`
- เก็บ backup สำหรับ rollback

### 4. Pull Latest Code

```bash
git fetch origin main
git reset --hard origin/main
```

- Pull code ล่าสุดจาก GitHub
- Reset ไปยัง commit ล่าสุด (hard reset)
- เก็บ commit เก่าสำหรับ rollback

### 5. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

- ติดตั้ง/อัพเดท PHP dependencies
- ไม่ติดตั้ง dev dependencies
- Optimize autoloader สำหรับ production

### 6. Clear All Caches

ล้าง cache ทั้งหมดก่อนรัน migration:
- Application cache
- Configuration cache
- Route cache
- View cache
- Event cache

### 7. Run Migrations

```bash
php artisan migrate --force
```

- รัน database migrations
- `--force` = ไม่ถามยืนยัน (production)
- สร้าง/แก้ไข tables อัตโนมัติ

### 8. Seed Database (Optional)

Script จะถาม: `Run database seeders? (y/n) [n]:`

- กด `y` = รัน seeders (เพิ่มข้อมูลตัวอย่าง)
- กด `n` = ข้าม (แนะนำสำหรับ production)

### 9. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
```

- ตั้งสิทธิ์ folders และ files
- รองรับ web server (Nginx/Apache)

### 10-12. Cache Configuration, Routes, Views

สร้าง cache ใหม่สำหรับ production:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 13. Restart Services

- Restart PHP-FPM (reload)
- Restart Queue Workers
- รองรับหลาย PHP versions

### 14. Disable Maintenance Mode

```bash
php artisan up
```

- เปิดเว็บไซต์อีกครั้ง
- ผู้ใช้สามารถเข้าถึงได้

---

## 🔄 การ Rollback (ย้อนกลับ)

### กรณีที่ Deploy แล้วเกิดปัญหา

### วิธีที่ 1: ใช้ Rollback Script (แนะนำ)

```bash
# ให้สิทธิ์ execute (ครั้งแรก)
chmod +x rollback.sh

# รัน rollback
./rollback.sh
```

Script จะแสดงรายการ commits ล่าสุด:
```
ℹ Recent commits:
e5f6g7h8 (HEAD -> main) feat: เพิ่มฟีเจอร์ใหม่
a1b2c3d4 fix: แก้บั๊ก
9x8y7z6w docs: อัพเดทเอกสาร

Enter commit hash to rollback to: a1b2c3d4
```

### วิธีที่ 2: Rollback แบบ Manual

```bash
# 1. เข้า maintenance mode
php artisan down

# 2. Rollback code
git reset --hard COMMIT_HASH

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Clear cache
php artisan cache:clear
php artisan config:clear

# 5. Rollback migrations (ถ้าจำเป็น)
php artisan migrate:rollback --step=1

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. ออกจาก maintenance mode
php artisan up
```

---

## 🔧 Advanced Usage

### Deploy with Custom Options

```bash
# Deploy โดยไม่ seed database
./deploy.sh main <<< "n"

# Deploy และ seed database
./deploy.sh main <<< "y"

# Deploy จาก specific commit
git checkout COMMIT_HASH
./deploy.sh
```

### ตรวจสอบ Deployment Logs

```bash
# ดู deployment logs
tail -f storage/logs/deployment.log

# ดู Laravel logs
tail -f storage/logs/laravel.log

# ดู web server logs (Nginx)
sudo tail -f /var/log/nginx/error.log
```

### ตรวจสอบสถานะ

```bash
# เช็ค migration status
php artisan migrate:status

# เช็ค queue workers
php artisan queue:monitor

# เช็ค cache
php artisan cache:clear --dry-run

# เช็ค git commit
git log --oneline -5
```

---

## 🐛 Troubleshooting

### ปัญหา: "Maintenance mode failed"

**สาเหตุ:** ไม่สามารถสร้าง maintenance file

**วิธีแก้:**
```bash
# เช็คสิทธิ์
ls -la storage/framework/

# ตั้งสิทธิ์
chmod -R 755 storage
sudo chown -R $USER:$USER storage
```

### ปัญหา: "Git pull failed"

**สาเหตุ:** มี uncommitted changes หรือ conflicts

**วิธีแก้:**
```bash
# เช็ค git status
git status

# Stash changes ชั่วคราว
git stash

# Pull อีกครั้ง
./deploy.sh

# กลับมาดู stash
git stash pop
```

### ปัญหา: "Composer install failed"

**สาเหตุ:** Memory limit หรือ network issue

**วิธีแก้:**
```bash
# เพิ่ม memory limit
php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader

# หรือใช้ composer 2
composer self-update --2
```

### ปัญหา: "Migration failed"

**สาเหตุ:** Database connection หรือ migration error

**วิธีแก้:**
```bash
# เช็ค database connection
php artisan tinker
>>> DB::connection()->getPdo();

# ดู migration status
php artisan migrate:status

# Rollback migration ก่อนหน้า
php artisan migrate:rollback --step=1

# รัน migration ใหม่
php artisan migrate --force
```

### ปัญหา: "Permission denied"

**สาเหตุ:** ไม่มีสิทธิ์ execute script

**วิธีแก้:**
```bash
chmod +x deploy.sh
chmod +x rollback.sh
```

---

## 📚 Best Practices

### 1. ทดสอบก่อน Deploy

```bash
# ทดสอบใน local/staging environment ก่อน
php artisan test

# เช็ค code quality
./vendor/bin/phpstan analyse

# เช็ค migrations
php artisan migrate --pretend
```

### 2. Backup ก่อน Deploy

```bash
# Backup database manual
mysqldump -u username -p database_name > backup_before_deploy.sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz \
    app/ config/ database/ resources/ routes/ public/
```

### 3. Deploy ในช่วงเวลาที่เหมาะสม

- 🌙 Deploy ตอนกลางคืน (traffic น้อย)
- 📅 หลีกเลี่ยงวันสำคัญ/ช่วง peak time
- ⏰ แจ้งผู้ใช้ล่วงหน้า

### 4. Monitor หลัง Deploy

```bash
# Monitor logs
tail -f storage/logs/laravel.log

# Monitor performance
top
htop

# Monitor database
mysql -u username -p -e "SHOW PROCESSLIST;"
```

---

## 📞 ความช่วยเหลือ

- 📖 **Documentation**: README.md
- 🌍 **Multi-language**: MULTI-LANGUAGE.md
- 👑 **Super Admin**: SUPER-ADMIN.md
- 🏭 **Production Install**: PRODUCTION-INSTALL.md
- 🐛 **GitHub Issues**: [Report a bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

---

**🚀 Deploy อย่างปลอดภัย Deploy อย่างมั่นใจ!**
