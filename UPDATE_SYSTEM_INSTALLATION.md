# 🚀 TP-Affiliate Update System - Installation Guide

คู่มือการติดตั้งระบบอัพเดทสำหรับ TP-Affiliate

---

## 📋 สาเหตุที่ระบบอัพเดทยังใช้งานไม่ได้

1. ❌ **ไม่มีไฟล์ .env** - Laravel ต้องการไฟล์นี้สำหรับ configuration
2. ❌ **Database ยังไม่ได้ตั้งค่า** - ต้องสร้าง database และ tables
3. ❌ **Migrations ยังไม่ได้รัน** - Tables สำหรับ update system ยังไม่มี

---

## ✅ ขั้นตอนการติดตั้ง

### Step 1: สร้างไฟล์ .env (เสร็จแล้ว ✅)

```bash
# สร้าง .env จาก .env.example
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 2: ตั้งค่า Database

#### Option A: ใช้ MySQL (แนะนำสำหรับ Production)

```bash
# 1. สร้าง database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. แก้ไข .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=thaiprompt_affiliate
# DB_USERNAME=root
# DB_PASSWORD=your_password_here

# 3. Test connection
php artisan db:show
```

#### Option B: ใช้ SQLite (แนะนำสำหรับ Development)

```bash
# 1. ติดตั้ง PHP SQLite extension
sudo apt-get install php-sqlite3  # Ubuntu/Debian
# หรือ
sudo yum install php-sqlite3       # CentOS/RHEL

# 2. สร้าง database file
touch database/database.sqlite

# 3. แก้ไข .env
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database/database.sqlite

# 4. Test connection
php artisan db:show
```

### Step 3: Run Migrations

```bash
# รัน migrations ทั้งหมด
php artisan migrate --force

# ถ้าต้องการ seed ข้อมูลตัวอย่าง
php artisan db:seed --force
```

### Step 4: ตรวจสอบว่า Tables สร้างแล้ว

```bash
# ดู tables ทั้งหมด
php artisan db:table migrations

# หรือเช็คเฉพาะ update tables
php artisan tinker --execute="
echo 'system_updates: ' . (Schema::hasTable('system_updates') ? 'YES' : 'NO') . PHP_EOL;
echo 'update_logs: ' . (Schema::hasTable('update_logs') ? 'YES' : 'NO') . PHP_EOL;
echo 'update_notifications: ' . (Schema::hasTable('update_notifications') ? 'YES' : 'NO') . PHP_EOL;
echo 'update_settings: ' . (Schema::hasTable('update_settings') ? 'YES' : 'NO') . PHP_EOL;
"
```

### Step 5: ตั้งค่าเพิ่มเติม (Optional)

```bash
# เพิ่มใน .env
VERSION_CHECK_ENABLED=true
VERSION_CHECK_CACHE_TTL=300           # 5 minutes
VERSION_AUTO_CHECK=true
VERSION_ALLOW_PRERELEASE=false

# สำหรับ GitHub Webhook (ถ้าใช้)
GITHUB_WEBHOOK_SECRET=your_secret_here
```

### Step 6: ทดสอบระบบ

```bash
# 1. ทดสอบตรวจสอบเวอร์ชั่น
php artisan app:version

# 2. ทดสอบเช็คอัพเดท
php artisan app:check-update

# 3. ทดสอบ clear cache
php artisan app:check-update --clear-cache

# 4. ทดสอบแสดง available versions
php artisan app:check-update --list
```

---

## 🎯 Quick Start (สำหรับ Development)

```bash
# ติดตั้งทุกอย่างในคำสั่งเดียว
cp .env.example .env && \
php artisan key:generate && \
touch database/database.sqlite && \
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env && \
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$(pwd)/database/database.sqlite|" .env && \
php artisan migrate --force && \
php artisan app:version

echo ""
echo "✅ Installation Complete!"
echo "🔍 Try: php artisan app:check-update"
```

---

## 🔧 Troubleshooting

### Problem: "could not find driver"

**Solution:** ติดตั้ง database driver

```bash
# MySQL
sudo apt-get install php-mysql
sudo service php8.2-fpm restart

# SQLite
sudo apt-get install php-sqlite3
sudo service php8.2-fpm restart
```

### Problem: "Access denied for user"

**Solution:** ตรวจสอบ database credentials ใน .env

```bash
# Test MySQL connection
mysql -u root -p -e "SELECT 1;"

# ถ้า connect ได้ แต่ Laravel ไม่ได้
# แก้ไข .env:
DB_USERNAME=root
DB_PASSWORD=your_actual_password
```

### Problem: "Database does not exist"

**Solution:** สร้าง database

```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate;"

# SQLite
touch database/database.sqlite
chmod 664 database/database.sqlite
```

### Problem: "Permission denied" (SQLite)

**Solution:** ตั้งค่า permissions

```bash
chmod 775 database
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite  # Production
```

### Problem: "Table not found"

**Solution:** รัน migrations

```bash
php artisan migrate:status  # ดู status
php artisan migrate --force  # รัน migrations
```

---

## 📊 Database Schema

### Tables Created:

1. **system_updates** - ข้อมูล releases ที่มี
2. **update_logs** - ประวัติการอัพเดท
3. **update_notifications** - การแจ้งเตือนให้ admin
4. **update_settings** - การตั้งค่าระบบอัพเดท

### Verify Tables:

```bash
# ดู schema
php artisan db:table system_updates
php artisan db:table update_logs

# นับจำนวน records
php artisan tinker --execute="
echo 'SystemUpdate count: ' . App\Models\SystemUpdate::count() . PHP_EOL;
echo 'UpdateLog count: ' . App\Models\UpdateLog::count() . PHP_EOL;
"
```

---

## 🧪 Testing the Update System

### 1. Check for Updates

```bash
php artisan app:check-update

# Expected output:
# Current: 2.127.6
# Latest: 2.127.9 (or current latest)
```

### 2. List Available Versions

```bash
php artisan app:check-update --list

# Shows all available versions from git tags
```

### 3. Test Version Commands

```bash
# Show version info
php artisan app:version

# Check for updates
php artisan app:version --check

# Show system requirements
php artisan app:version --system

# Show changelog
php artisan app:version --changelog
```

### 4. Access Admin Panel

```
http://your-domain.com/admin/updates
```

---

## 📝 Post-Installation Checklist

- [ ] .env file created
- [ ] APP_KEY generated
- [ ] Database created and connected
- [ ] Migrations run successfully
- [ ] Update tables exist
- [ ] Version check works
- [ ] Can see available updates
- [ ] Admin panel accessible
- [ ] (Optional) GitHub webhook configured

---

## 🎉 Success Indicators

✅ คำสั่งนี้ควรทำงาน:
```bash
php artisan app:version
```

✅ แสดงผลลัพธ์:
```
╔═══════════════════════════════════════════════════════════╗
║         TP-Affiliate - Version Information                ║
╚═══════════════════════════════════════════════════════════╝

+----------+------------+
| Property | Value      |
+----------+------------+
| Version  | 2.127.6    |
| Codename | Phoenix    |
| Released | 2025-11-07 |
| Laravel  | 11.46.1    |
| PHP      | 8.x.x      |
| Min PHP  | 8.1.0      |
+----------+------------+
```

✅ สามารถเช็คอัพเดท:
```bash
php artisan app:check-update

# แสดง:
# Current: 2.127.6
# Latest: 2.127.x
```

---

## 📚 Next Steps

1. ✅ **Setup Complete** - ระบบพื้นฐานพร้อมใช้งาน
2. 📱 **Configure Webhook** - ตั้งค่า GitHub webhook (ดู GITHUB_WEBHOOK_SETUP.md)
3. 🎨 **Customize UI** - แก้ไข UI หน้า admin/updates
4. 🔔 **Setup Notifications** - ตั้งค่าการแจ้งเตือน email
5. 🤖 **Auto Update** - ตั้งค่า cron job สำหรับ auto-check

---

## 💡 Tips

### Development Mode:
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
```

### Production Mode:
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
VERSION_CHECK_CACHE_TTL=300
```

### Enable Auto-Check:
```bash
# เพิ่ม cron job
* */6 * * * cd /path/to/project && php artisan app:check-update >> /dev/null 2>&1
```

---

**Created:** 2025-11-11
**Version:** 1.0.0
**Status:** Ready for Use
