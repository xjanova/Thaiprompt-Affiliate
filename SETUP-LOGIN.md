# คู่มือแก้ไข Error 503 และติดตั้งระบบ Login

## สาเหตุของ Error 503
1. ❌ ไม่มีไฟล์ `.env` หรือ `APP_KEY` ว่างเปล่า
2. ❌ ไม่มี `vendor/` directory (Composer dependencies ยังไม่ได้ install)
3. ❌ Database ยังไม่ได้สร้างหรือ migrations ยังไม่ได้รัน
4. ❌ Permissions ของ `storage/` และ `bootstrap/cache/` ไม่ถูกต้อง

## ✅ สิ่งที่แก้ไขแล้ว
- ✅ สร้างไฟล์ `.env` พร้อม `APP_KEY`
- ✅ เปลี่ยน database เป็น SQLite (ไม่ต้องใช้ MySQL)
- ✅ สร้างไฟล์ `database/database.sqlite`

## 📋 ขั้นตอนการติดตั้งระบบ Login

### วิธีที่ 1: ใช้ Docker (แนะนำ)

```bash
# 1. Build และรัน containers
docker-compose up -d --build

# 2. เข้าไปใน container
docker-compose exec app bash

# 3. ติดตั้ง dependencies
composer install --no-interaction

# 4. Generate APP_KEY (ถ้ายังไม่มี)
php artisan key:generate

# 5. รัน migrations
php artisan migrate --force

# 6. สร้าง admin user
php artisan tinker
# แล้วพิมพ์คำสั่งนี้:
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'referral_code' => 'ADMIN001',
    'status' => 'active'
]);
# กด Ctrl+D เพื่อออก

# 7. ออกจาก container
exit

# 8. เปิด browser ไปที่
# http://localhost:8000/login
# Email: admin@test.com
# Password: password
```

### วิธีที่ 2: ไม่ใช้ Docker (ต้องมี PHP 8.1+ และ Composer)

```bash
# 1. ติดตั้ง Composer dependencies
composer install

# 2. สร้าง .env ถ้ายังไม่มี
cp .env.example .env

# 3. Generate APP_KEY
php artisan key:generate

# 4. สร้าง database directory
mkdir -p database
touch database/database.sqlite

# 5. แก้ไข .env ให้ใช้ SQLite
# เปลี่ยน DB_CONNECTION=mysql เป็น DB_CONNECTION=sqlite
# Comment หรือลบบรรทัดที่เป็น DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. รัน migrations
php artisan migrate

# 7. สร้าง admin user
php artisan tinker
# พิมพ์:
\App\Models\User::create(['name'=>'Admin','email'=>'admin@test.com','password'=>bcrypt('password'),'referral_code'=>'ADMIN001','status'=>'active']);
# กด Ctrl+D

# 8. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 9. รันเซิร์ฟเวอร์
php artisan serve

# 10. เปิด browser ไปที่
# http://localhost:8000/login
# Email: admin@test.com
# Password: password
```

### วิธีที่ 3: Setup ด่วน (Emergency Quick Fix)

ถ้าต้องการแก้ไขด่วนเพื่อให้ Login ทำงานได้ทันที:

```bash
# 1. ให้สิทธิ์ในการเขียนไฟล์
chmod -R 775 storage bootstrap/cache

# 2. สร้าง symbolic link สำหรับ storage
php artisan storage:link

# 3. Restart web server/PHP-FPM
# สำหรับ Docker:
docker-compose restart

# สำหรับ Nginx + PHP-FPM:
sudo systemctl restart php8.1-fpm nginx

# สำหรับ Apache:
sudo systemctl restart apache2
```

## 🐛 การแก้ไขปัญหาที่พบบ่อย

### Error: "Please provide a valid app key"
```bash
php artisan key:generate
php artisan config:clear
```

### Error: "SQLSTATE[HY000]: General error: 1 no such table: users"
```bash
php artisan migrate:fresh
```

### Error: "Permission denied" เมื่อเขียนไฟล์
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Linux
chown -R _www:_www storage bootstrap/cache          # macOS
```

### หน้าเว็บโชว์ "503 Service Unavailable"
1. ตรวจสอบ `.env` ว่ามี `APP_KEY` หรือไม่
2. ตรวจสอบ `storage/logs/laravel.log` เพื่อดู error
3. ตรวจสอบ web server logs (nginx/apache)
4. ลอง restart web server

### ไม่สามารถ login ได้ (credentials ไม่ถูกต้อง)
1. ตรวจสอบว่าสร้าง user ในระบบแล้ว
2. Password ต้อง hash ด้วย `bcrypt()` เท่านั้น
3. ตรวจสอบว่า email ถูกต้อง (case-sensitive)

## 📝 Test Accounts

หลังจาก setup เสร็จ ให้สร้าง test users:

```php
// Admin User
php artisan tinker
\App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'referral_code' => 'ADMIN001',
    'status' => 'active'
]);

// Test User
\App\Models\User::create([
    'name' => 'Test User',
    'email' => 'user@test.com',
    'password' => bcrypt('password'),
    'referral_code' => 'USER001',
    'status' => 'active'
]);
```

## 📞 ติดต่อสอบถาม

หากยังพบปัญหา กรุณา:
1. ตรวจสอบ log file ที่ `storage/logs/laravel.log`
2. ตรวจสอบ browser console (F12) เพื่อดู JavaScript errors
3. ลองรัน `php artisan config:clear && php artisan cache:clear`

---

**หมายเหตุ:** ไฟล์นี้สร้างขึ้นเพื่อแก้ปัญหา Error 503 และให้ระบบ Login ทำงานได้โดยเร็ว
