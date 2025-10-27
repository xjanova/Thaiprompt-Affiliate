# ⚡ Quick Start Guide

คู่มือเริ่มต้นใช้งานแบบเร็ว สำหรับติดตั้ง ThaiPrompt Marketplace

---

## 🎯 ขั้นตอนสั้นๆ (3 ขั้นตอน)

```bash
# 1. แตก ZIP และรันสคริปต์
unzip thaiprompt-marketplace.zip
cd Thaiprompt-Affiliate
bash install.sh

# 2. ตั้งค่า Web Server (ดูตัวอย่างด้านล่าง)

# 3. เข้าเว็บในเบราว์เซอร์
# → จะเห็นหน้า Setup Wizard อัตโนมัติ!
```

---

## 📦 สิ่งที่ต้องมีก่อนติดตั้ง

### บน Server:
- ✅ PHP >= 8.1
- ✅ MySQL >= 8.0
- ✅ Composer
- ✅ Node.js & NPM
- ✅ Nginx หรือ Apache

### ติดตั้ง Requirements (Ubuntu/Debian):

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1-fpm php8.1-cli php8.1-mysql php8.1-gd \
  php8.1-mbstring php8.1-curl php8.1-xml php8.1-zip php8.1-bcmath

# Install MySQL
sudo apt install -y mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js (v18 LTS)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Nginx
sudo apt install -y nginx
```

---

## 🚀 วิธีติดตั้ง

### ขั้นตอนที่ 1: แตกไฟล์และรันสคริปต์

```bash
# อัพโหลด ZIP ไปยัง server (ตำแหน่งที่ต้องการ)
# เช่น /var/www/

cd /var/www

# แตกไฟล์
unzip thaiprompt-marketplace-v1.2.0.zip
cd Thaiprompt-Affiliate

# รันสคริปต์ติดตั้ง
bash install.sh
```

**สคริปต์จะทำให้อัตโนมัติ:**
- ✅ ตรวจสอบ PHP, Composer, Node.js
- ✅ ติดตั้ง PHP dependencies (composer install)
- ✅ ติดตั้ง JavaScript dependencies (npm install)
- ✅ Build frontend assets (npm run build)
- ✅ ตั้งค่า permissions ที่ถูกต้อง
- ✅ สร้างไฟล์ .env และ generate APP_KEY

### ขั้นตอนที่ 2: ตั้งค่า Web Server

#### สำหรับ Nginx:

สร้างไฟล์ `/etc/nginx/sites-available/thaiprompt`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/Thaiprompt-Affiliate/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

เปิดใช้งาน:
```bash
sudo ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### สำหรับ Apache:

สร้างไฟล์ `/etc/apache2/sites-available/thaiprompt.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/Thaiprompt-Affiliate/public

    <Directory /var/www/Thaiprompt-Affiliate/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/thaiprompt_error.log
    CustomLog ${APACHE_LOG_DIR}/thaiprompt_access.log combined
</VirtualHost>
```

เปิดใช้งาน:
```bash
sudo a2ensite thaiprompt
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### ขั้นตอนที่ 3: เข้าเว็บและทำตาม Setup Wizard

เปิดเบราว์เซอร์ไปที่: `http://yourdomain.com`

**ระบบจะ redirect ไปหน้า Setup Wizard อัตโนมัติ!**

#### หน้า Setup Wizard มี 5 ขั้นตอน:

1. **ยินดีต้อนรับ** - แนะนำระบบ
2. **ตรวจสอบระบบ** - เช็ค PHP, Extensions, Permissions
3. **ตั้งค่าฐานข้อมูล** - กรอกข้อมูล MySQL และทดสอบการเชื่อมต่อ
4. **สร้างผู้ดูแลระบบ** - กรอกข้อมูล Admin
5. **เสร็จสิ้น!** - พร้อมใช้งาน

---

## 📋 ตัวอย่างข้อมูลที่ต้องเตรียม

### สำหรับ Database:

```
Database Host: 127.0.0.1 (หรือ localhost)
Database Port: 3306
Database Name: thaiprompt_marketplace
Username: root (หรือ user ที่สร้างไว้)
Password: your_password
```

**สร้าง Database ล่วงหน้า:**

```bash
sudo mysql
```

```sql
CREATE DATABASE thaiprompt_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'thaiprompt'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON thaiprompt_marketplace.* TO 'thaiprompt'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### สำหรับ Admin Account:

```
ชื่อ-นามสกุล: Admin Name
อีเมล: admin@yourdomain.com
รหัสผ่าน: (อย่างน้อย 8 ตัวอักษร)
```

---

## ✅ Checklist การติดตั้ง

- [ ] แตกไฟล์ ZIP แล้ว
- [ ] รัน `bash install.sh` สำเร็จ
- [ ] ตั้งค่า Nginx/Apache แล้ว
- [ ] สร้าง Database แล้ว
- [ ] เข้าเว็บเห็นหน้า Setup Wizard
- [ ] ทำตาม Setup Wizard จนเสร็จ
- [ ] Login ด้วย Admin account ได้
- [ ] ระบบทำงานปกติ

---

## 🎓 การใช้งานครั้งแรก

หลังจากติดตั้งเสร็จ:

1. **เข้าสู่ระบบ** - ด้วย Admin account ที่สร้าง
2. **ตั้งค่าระบบ** - ไปที่ Admin > Settings
   - Site Name, Description
   - Upload Logo
   - ตั้งค่า Email
3. **ตั้งค่า Payment Gateway** - Admin > Settings > Payment
   - Stripe API Keys
   - PromptPay Settings
4. **ตั้งค่า MLM** - Admin > Settings > MLM
   - Commission Rates
   - MLM Type
5. **เริ่มใช้งาน!**

---

## 🔧 Troubleshooting

### ปัญหา: ไม่สามารถรัน install.sh

```bash
# ตรวจสอบว่าไฟล์เป็น executable
chmod +x install.sh

# รันใหม่
bash install.sh
```

### ปัญหา: Permission denied

```bash
# ตั้งค่า permissions ใหม่
sudo chown -R www-data:www-data /var/www/Thaiprompt-Affiliate
sudo chmod -R 775 /var/www/Thaiprompt-Affiliate/storage
sudo chmod -R 775 /var/www/Thaiprompt-Affiliate/bootstrap/cache
```

### ปัญหา: 404 Not Found

**ตรวจสอบ:**
- Nginx/Apache config ถูกต้องหรือไม่
- DocumentRoot ชี้ไปที่ `/public` หรือยัง
- Nginx/Apache restart แล้วหรือยัง

### ปัญหา: 500 Internal Server Error

```bash
# ดู error logs
tail -f /var/www/Thaiprompt-Affiliate/storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# หรือ
tail -f /var/log/apache2/error.log
```

### ปัญหา: Composer install ล้มเหลว

```bash
# ลองเปลี่ยน memory limit
php -d memory_limit=-1 /usr/local/bin/composer install
```

### ปัญหา: npm install ล้มเหลว

```bash
# ลบ node_modules และลองใหม่
rm -rf node_modules package-lock.json
npm install
```

---

## 📚 เอกสารเพิ่มเติม

- **SETUP_WIZARD.md** - รายละเอียด Setup Wizard
- **INSTALLATION_GUIDE.md** - คู่มือติดตั้งแบบละเอียด
- **DEPLOYMENT.md** - คู่มือ deploy production
- **CONFIGURATION.md** - คู่มือการตั้งค่า
- **VERSION_UPDATE_GUIDE.md** - คู่มือการอัปเดตเวอร์ชัน

---

## 🎬 Video Tutorial

*(Coming soon)*

---

## 🆘 ต้องการความช่วยเหลือ?

- **Email**: support@thaiprompt.com
- **GitHub Issues**: https://github.com/xjanova/Thaiprompt-Affiliate/issues
- **Documentation**: https://docs.thaiprompt.com

---

## 🎉 สรุป

```bash
# ทั้งหมดเพียง 3 ขั้นตอนง่ายๆ:

1️⃣  bash install.sh          # รันสคริปต์ติดตั้ง
2️⃣  ตั้งค่า Nginx/Apache      # ตั้งค่า Web Server
3️⃣  เข้าเว็บ                  # ทำตาม Setup Wizard

# แค่นี้ก็พร้อมใช้งาน! 🚀
```

---

**Version**: 1.2.0
**Last Updated**: 2025-10-27
**License**: MIT
