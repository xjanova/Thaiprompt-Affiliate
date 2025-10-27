# Setup Wizard - คู่มือการติดตั้งแบบ GUI

ThaiPrompt Marketplace มาพร้อมกับ **Setup Wizard** ที่ช่วยให้คุณติดตั้งระบบได้ง่ายผ่านหน้าเว็บ โดยไม่ต้องรันคำสั่งผ่าน terminal

---

## ✨ คุณสมบัติ

- ติดตั้งระบบผ่านหน้าเว็บ 100%
- ตรวจสอบความพร้อมของระบบอัตโนมัติ
- ทดสอบการเชื่อมต่อฐานข้อมูลก่อนบันทึก
- สร้างผู้ดูแลระบบคนแรก
- รัน database migrations อัตโนมัติ
- Interface ที่เป็นมิตรกับผู้ใช้

---

## 📋 ขั้นตอนการติดตั้ง

### 1. เตรียม Server

ติดตั้ง requirements พื้นฐาน:

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y nginx php8.1-fpm php8.1-cli php8.1-mysql php8.1-gd \
  php8.1-mbstring php8.1-curl php8.1-xml php8.1-zip mysql-server
```

### 2. อัพโหลดไฟล์โปรเจค

```bash
# วิธีที่ 1: Clone จาก Git
cd /var/www
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
cd thaiprompt

# ติดตั้ง dependencies
composer install
npm install
npm run build

# วิธีที่ 2: อัพโหลดด้วย FTP/SFTP
# อัพโหลดไฟล์ทั้งหมดไปที่ /var/www/thaiprompt
```

### 3. ตั้งค่า Permissions

```bash
sudo chown -R www-data:www-data /var/www/thaiprompt
sudo chmod -R 775 /var/www/thaiprompt/storage
sudo chmod -R 775 /var/www/thaiprompt/bootstrap/cache
```

### 4. ตั้งค่า Nginx

สร้างไฟล์ `/etc/nginx/sites-available/thaiprompt`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/thaiprompt/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

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

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. เริ่มต้นการติดตั้งผ่าน Web

เปิดเบราว์เซอร์แล้วไปที่: `http://yourdomain.com`

ระบบจะ redirect ไปยัง Setup Wizard อัตโนมัติ!

---

## 🎯 ขั้นตอนใน Setup Wizard

### Step 1: ยินดีต้อนรับ

- แสดงข้อมูลเกี่ยวกับระบบ
- แสดงคุณสมบัติหลักของ ThaiPrompt Marketplace
- คลิก "เริ่มต้นติดตั้ง"

### Step 2: ตรวจสอบความต้องการระบบ

ระบบจะตรวจสอบ:
- ✅ เวอร์ชัน PHP (>= 8.1)
- ✅ PHP Extensions ที่จำเป็น
- ✅ สิทธิ์การเขียนไฟล์

หากไม่ผ่าน จะแสดงรายการสิ่งที่ต้องแก้ไข

### Step 3: ตั้งค่าฐานข้อมูล

กรอกข้อมูล MySQL:
- **Database Host**: ปกติเป็น `127.0.0.1` หรือ `localhost`
- **Database Port**: ปกติเป็น `3306`
- **Database Name**: ชื่อฐานข้อมูลที่ต้องการใช้
- **Username**: ชื่อผู้ใช้ MySQL
- **Password**: รหัสผ่าน (ไม่บังคับ)

คลิก "ทดสอบการเชื่อมต่อ" ก่อนบันทึก!

### Step 4: สร้างบัญชีผู้ดูแลระบบ

กรอกข้อมูล:
- **ชื่อ-นามสกุล**: ชื่อของผู้ดูแลระบบ
- **อีเมล**: อีเมลสำหรับเข้าสู่ระบบ
- **รหัสผ่าน**: ต้องมีอย่างน้อย 8 ตัวอักษร
- **ยืนยันรหัสผ่าน**: กรอกรหัสผ่านอีกครั้ง

คลิก "เริ่มการติดตั้ง"

ระบบจะ:
1. สร้างตารางในฐานข้อมูล
2. สร้างบัญชี Admin
3. ตั้งค่าเริ่มต้น

### Step 5: เสร็จสิ้น!

ระบบติดตั้งเสร็จแล้ว คุณสามารถ:
- เข้าสู่ระบบด้วยบัญชี Admin ที่สร้างไว้
- ตั้งค่าระบบเพิ่มเติมในหน้า Admin

---

## 🔒 Security Features

### ป้องกันการติดตั้งซ้ำ

เมื่อติดตั้งเสร็จ ระบบจะสร้างไฟล์ `storage/installed` เพื่อป้องกันการติดตั้งซ้ำ

### การติดตั้งใหม่

หากต้องการติดตั้งใหม่:

```bash
# ลบไฟล์ lock
rm storage/installed

# ล้างฐานข้อมูล
php artisan migrate:fresh

# หรือใช้ Setup Wizard ใหม่อีกครั้ง
```

**⚠️ คำเตือน:** การติดตั้งใหม่จะลบข้อมูลทั้งหมด!

---

## 🛠️ Troubleshooting

### ปัญหา: ไม่สามารถเข้า Setup Wizard

**สาเหตุ:**
- ไฟล์ `storage/installed` มีอยู่แล้ว
- Nginx/Apache ไม่ได้ point ไปที่ `public/`

**วิธีแก้:**
```bash
# ลบไฟล์ installed (ถ้ามี)
rm storage/installed

# ตรวจสอบ Nginx config
sudo nginx -t
```

### ปัญหา: ทดสอบฐานข้อมูลไม่ผ่าน

**สาเหตุ:**
- ข้อมูลการเชื่อมต่อผิด
- MySQL service ไม่ทำงาน
- User ไม่มีสิทธิ์สร้าง database

**วิธีแก้:**
```bash
# ตรวจสอบ MySQL
sudo systemctl status mysql

# ทดสอบเชื่อมต่อ
mysql -h127.0.0.1 -uroot -p

# สร้าง database และ user
mysql -uroot -p
CREATE DATABASE thaiprompt_marketplace;
CREATE USER 'thaiprompt'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON thaiprompt_marketplace.* TO 'thaiprompt'@'localhost';
FLUSH PRIVILEGES;
```

### ปัญหา: Permission denied

**วิธีแก้:**
```bash
# ตั้งค่า permissions ใหม่
sudo chown -R www-data:www-data /var/www/thaiprompt
sudo chmod -R 775 storage bootstrap/cache
```

### ปัญหา: White screen / 500 error

**วิธีแก้:**
```bash
# ดู error logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# ตรวจสอบ PHP errors
sudo tail -f /var/log/php8.1-fpm.log
```

---

## 🚀 หลังการติดตั้ง

### ขั้นตอนที่แนะนำ

1. **ตั้งค่าระบบ**
   - ไปที่ Admin > Settings
   - กรอก Site Name, Description
   - อัพโหลด Logo และ Favicon

2. **ตั้งค่า Payment Gateway**
   - Admin > Settings > Payment
   - กรอก Stripe API Keys
   - กรอก PromptPay Merchant ID

3. **ตั้งค่า Email**
   - แก้ไข `.env`:
     ```env
     MAIL_MAILER=smtp
     MAIL_HOST=smtp.gmail.com
     MAIL_PORT=587
     MAIL_USERNAME=your-email@gmail.com
     MAIL_PASSWORD=your-app-password
     MAIL_ENCRYPTION=tls
     ```

4. **ตั้งค่า MLM Commission**
   - Admin > Settings > MLM
   - กำหนดอัตราคอมมิชชั่นแต่ละระดับ
   - เลือกประเภท MLM (Unilevel, Binary, Matrix)

5. **ติดตั้ง SSL Certificate**
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d yourdomain.com
   ```

---

## 📚 เอกสารเพิ่มเติม

- [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) - คู่มือติดตั้งแบบละเอียด
- [DEPLOYMENT.md](./DEPLOYMENT.md) - คู่มือ deploy production
- [CONFIGURATION.md](./CONFIGURATION.md) - คู่มือการตั้งค่า
- [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) - API Documentation

---

## 🆘 ต้องการความช่วยเหลือ?

- **อีเมล:** support@thaiprompt.com
- **GitHub Issues:** https://github.com/xjanova/Thaiprompt-Affiliate/issues
- **Documentation:** https://docs.thaiprompt.com

---

## 🎉 ติดตั้งเสร็จแล้ว!

ยินดีต้อนรับสู่ ThaiPrompt Marketplace!

เริ่มต้นสร้างตลาดออนไลน์ของคุณได้เลยตอนนี้ 🚀

---

**Version:** 1.1.0
**Last Updated:** 2025-10-27
