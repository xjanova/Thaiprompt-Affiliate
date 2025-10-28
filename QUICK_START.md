# ⚡ Quick Start Guide

คู่มือเริ่มต้นใช้งานแบบเร็ว สำหรับติดตั้ง ThaiPrompt Marketplace บน Production Server

---

## 📚 คู่มือหลัก

**สำหรับการติดตั้งแบบละเอียด กรุณาดู:**

### 🖥️ ติดตั้งเซิร์ฟเวอร์ครั้งแรก
👉 [SERVER_SETUP.md](./SERVER_SETUP.md) - คู่มือติดตั้งเซิร์ฟเวอร์ทีละขั้นตอนอย่างละเอียด

**ครอบคลุม:**
- ติดตั้ง PHP 8.2 และ Extensions
- ติดตั้ง MySQL และสร้าง Database
- ติดตั้ง Nginx และตั้งค่า
- ติดตั้ง Composer และ Node.js
- Clone โปรเจคจาก GitHub
- ตั้งค่า SSL Certificate
- ตั้งค่า Supervisor สำหรับ Queue Workers

### 🚀 Deploy และอัพเดทโปรเจค
👉 [DEPLOYMENT.md](./DEPLOYMENT.md) - คู่มือการ deploy และอัพเดทโปรเจค

**ครอบคลุม:**
- การ deploy ครั้งแรก
- การอัพเดทโปรเจคด้วย `deploy.sh`
- CI/CD Pipeline
- Rollback
- Troubleshooting

---

## ⚡ Quick Commands

### ติดตั้งครั้งแรก

```bash
# 1. Clone โปรเจค
cd /var/www
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
cd thaiprompt

# 2. สร้างไฟล์ .env
cp .env.example .env
nano .env  # แก้ไขการตั้งค่า

# 3. ติดตั้ง dependencies
composer install --no-dev --optimize-autoloader
npm ci --only=production
npm run build

# 4. Setup Laravel
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# 5. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 storage bootstrap/cache
```

### Deploy อัพเดทใหม่

```bash
cd /var/www/thaiprompt
./deploy.sh
```

**เพียงแค่นี้!** 🎉

---

## 📋 System Requirements

### บน Server:
- ✅ **OS:** Ubuntu 22.04 LTS หรือใหม่กว่า
- ✅ **PHP:** >= 8.2 พร้อม Extensions
- ✅ **MySQL:** >= 8.0
- ✅ **Composer:** >= 2.0
- ✅ **Node.js:** >= 20 LTS
- ✅ **Nginx:** Latest
- ✅ **Redis:** Latest (optional แต่แนะนำ)

### Hardware Minimum:
- **CPU:** 2 cores
- **RAM:** 4GB
- **Storage:** 50GB SSD

### Hardware Recommended:
- **CPU:** 4 cores
- **RAM:** 8GB
- **Storage:** 100GB SSD

---

## 🔍 หลังการติดตั้ง

### สร้าง Admin User

```bash
cd /var/www/thaiprompt
php artisan tinker
```

ใน tinker:

```php
$user = new App\Models\User;
$user->name = 'Admin';
$user->email = 'admin@your-domain.com';
$user->password = bcrypt('your-secure-password');
$user->role = 'admin';
$user->save();
exit
```

### ทดสอบระบบ

```bash
# ตรวจสอบบริการ
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql

# ดู logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### เข้าสู่ระบบ

เปิดเบราว์เซอร์ไปที่: `https://your-domain.com/login`

---

## 🆘 Troubleshooting

### ปัญหาทั่วไป

**500 Internal Server Error:**
```bash
# ตรวจสอบ permissions
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 storage bootstrap/cache

# ตรวจสอบ logs
tail -f storage/logs/laravel.log
```

**Database Connection Failed:**
```bash
# ทดสอบ MySQL
mysql -u thaiprompt -p

# ตรวจสอบ .env
cat .env | grep DB_
```

**CSS/JS ไม่โหลด:**
```bash
npm run build
php artisan config:cache
```

---

## 📞 ต้องการความช่วยเหลือ?

- 📖 [SERVER_SETUP.md](./SERVER_SETUP.md) - คู่มือติดตั้งละเอียด
- 🚀 [DEPLOYMENT.md](./DEPLOYMENT.md) - คู่มือ deployment
- 📚 [README.md](./README.md) - ข้อมูลโปรเจคและ features
- 🐛 [GitHub Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues) - รายงานปัญหา

---

**Happy Coding! 🚀**
