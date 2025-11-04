# คู่มือการติดตั้ง TP-Affiliate

ฉบับสำหรับผู้ซื้อระบบ

---

## 📋 สารบัญ

1. [ความต้องการของระบบ](#ความต้องการของระบบ)
2. [การเตรียมความพร้อม](#การเตรียมความพร้อม)
3. [การติดตั้งอัตโนมัติ](#การติดตั้งอัตโนมัติ)
4. [การตั้งค่าครั้งแรก](#การตั้งค่าครั้งแรก)
5. [การอัพเดทระบบ](#การอัพเดทระบบ)
6. [การแก้ปัญหา](#การแก้ปัญหา)
7. [การติดต่อสนับสนุน](#การติดต่อสนับสนุน)

---

## ความต้องการของระบบ

### เซิร์ฟเวอร์

- **ระบบปฏิบัติการ:** Ubuntu 20.04+ / Debian 10+ / CentOS 8+
- **PHP:** 8.1.0 หรือสูงกว่า
- **Database:** MySQL 5.7+ หรือ MariaDB 10.3+
- **Web Server:** Nginx หรือ Apache
- **Composer:** ล่าสุด
- **Node.js:** 18+ (สำหรับ build assets)

### PHP Extensions ที่จำเป็น

- PDO
- PDO MySQL
- MBString
- OpenSSL
- JSON
- cURL
- GD
- XML
- Zip
- Fileinfo

### ความจำและพื้นที่

- **RAM:** อย่างน้อย 512MB (แนะนำ 1GB+)
- **Disk Space:** อย่างน้อย 2GB

---

## การเตรียมความพร้อม

### 1. ข้อมูลที่ต้องเตรียม

คุณจะต้องมีข้อมูลต่อไปนี้ก่อนเริ่มติดตั้ง:

✅ **License Key** (รูปแบบ: XXXX-XXXX-XXXX-XXXX)
✅ **ข้อมูล Database**
   - Database Name
   - Database Username
   - Database Password
   - Database Host (มักจะเป็น localhost)

### 2. เตรียม Database

```sql
-- สร้าง database
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้าง user และให้สิทธิ์
CREATE USER 'tp_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'tp_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. ติดต่อแจ้ง IP Address

**สำคัญมาก!** คุณต้องแจ้ง IP address ของเซิร์ฟเวอร์ให้ทีมงานเพื่อเพิ่มเข้า whitelist

```bash
# ตรวจสอบ IP ของเซิร์ฟเวอร์
curl https://api.ipify.org
```

📧 ส่ง IP address พร้อม License Key มาที่: **support@xman4289.com**

---

## การติดตั้งอัตโนมัติ

### ขั้นตอนที่ 1: ดาวน์โหลด Installer

```bash
# เข้าไปยัง directory ที่ต้องการติดตั้ง
cd /var/www

# ดาวน์โหลด installer
wget https://raw.githubusercontent.com/xjanova/TP-Affiliate/main/install.sh

# กำหนดสิทธิ์
chmod +x install.sh
```

### ขั้นตอนที่ 2: รัน Installer

```bash
./install.sh
```

### ขั้นตอนที่ 3: ทำตามคำแนะนำ

Installer จะถามข้อมูลต่อไปนี้:

1. **Database Configuration**
   - Database host (default: localhost)
   - Database port (default: 3306)
   - Database name
   - Database username
   - Database password

2. **License Key**
   - ใส่ License Key ที่ได้รับ
   - ระบบจะตรวจสอบ IP address อัตโนมัติ

3. **Demo Data**
   - เลือกว่าต้องการติดตั้งข้อมูลตัวอย่างหรือไม่
   - แนะนำให้ติดตั้งสำหรับการทดสอบระบบ

### สิ่งที่ Installer ทำ

✅ ตรวจสอบ PHP version และ extensions
✅ ตรวจสอบ Composer
✅ ทดสอบการเชื่อมต่อ Database
✅ ตรวจสอบ IP address กับ License Server
✅ ดาวน์โหลดและติดตั้งระบบ
✅ กำหนดค่า environment
✅ ติดตั้ง dependencies
✅ รัน database migrations
✅ Seed demo data (ถ้าเลือก)
✅ ตั้งค่า file permissions
✅ Optimize สำหรับ production

---

## การตั้งค่าครั้งแรก

### ขั้นตอนที่ 1: ตั้งค่า Web Server

#### Nginx Configuration

สร้างไฟล์ `/etc/nginx/sites-available/tp-affiliate`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/tp-affiliate/public;

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
sudo ln -s /etc/nginx/sites-available/tp-affiliate /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache Configuration

สร้างไฟล์ `/etc/apache2/sites-available/tp-affiliate.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/tp-affiliate/public

    <Directory /var/www/tp-affiliate/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tp-affiliate-error.log
    CustomLog ${APACHE_LOG_DIR}/tp-affiliate-access.log combined
</VirtualHost>
```

เปิดใช้งาน:

```bash
sudo a2ensite tp-affiliate
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### ขั้นตอนที่ 2: เปิด Setup Wizard

เปิดเว็บบราวเซอร์และไปที่: `http://your-domain.com`

ระบบจะ redirect ไปที่ `/setup` อัตโนมัติ

### ขั้นตอนที่ 3: ทำตาม Setup Wizard

1. **ตรวจสอบความพร้อมของระบบ**
   - ระบบจะตรวจสอบ PHP และ extensions อัตโนมัติ
   - ถ้าผ่านทั้งหมด กดปุ่ม "ถัดไป"

2. **ยืนยัน License**
   - ใส่ License Key
   - กดปุ่ม "ยืนยัน License"
   - รอการตรวจสอบจาก License Server

3. **สร้างบัญชีแอดมิน**
   - ใส่ชื่อ-นามสกุล
   - ใส่อีเมล
   - ตั้งรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
   - ยืนยันรหัสผ่าน

4. **ข้อมูลตัวอย่าง**
   - เลือกว่าต้องการติดตั้งข้อมูลตัวอย่างหรือไม่
   - แนะนำให้ติดตั้งสำหรับทดสอบระบบ

5. **เสร็จสิ้น**
   - กดปุ่ม "เข้าสู่ระบบ"
   - เข้าสู่ระบบด้วยอีเมลและรหัสผ่านที่สร้างไว้

---

## การอัพเดทระบบ

### วิธีที่ 1: อัพเดทผ่าน CLI

```bash
# เข้าไปยัง directory ของระบบ
cd /var/www/tp-affiliate

# ตรวจสอบเวอร์ชั่นใหม่
php artisan app:check-update

# อัพเดทเป็นเวอร์ชั่นล่าสุด
php artisan app:update

# หรืออัพเดทเป็นเวอร์ชั่นที่กำหนด
php artisan app:update v1.145.0
```

### วิธีที่ 2: อัพเดทผ่าน Admin Panel

1. เข้าสู่ระบบในฐานะ Admin
2. ไปที่ **Settings** → **System Update**
3. กดปุ่ม **Check for Updates**
4. ถ้ามีเวอร์ชั่นใหม่ จะแสดง changelog
5. กดปุ่ม **Update Now**
6. รอจนกว่าระบบจะอัพเดทเสร็จ

### การอัพเดทจะทำอะไรบ้าง

✅ ตรวจสอบ License
✅ เปิด Maintenance Mode
✅ Backup Database อัตโนมัติ
✅ ดึงโค้ดเวอร์ชั่นใหม่
✅ อัพเดท Dependencies
✅ รัน Database Migrations
✅ Optimize แอปพลิเคชัน
✅ ปิด Maintenance Mode

### Backup ที่สร้างอัตโนมัติ

```
storage/app/backups/database-YYYY-MM-DD-HHmmss.sql
```

---

## การแก้ปัญหา

### ปัญหา: ติดตั้งไม่สำเร็จ - IP Not Allowed

**สาเหตุ:** IP address ของเซิร์ฟเวอร์ไม่ได้อยู่ใน whitelist

**วิธีแก้:**
1. ตรวจสอบ IP ของเซิร์ฟเวอร์: `curl https://api.ipify.org`
2. ติดต่อทีมสนับสนุนเพื่อเพิ่ม IP เข้า whitelist
3. แจ้ง License Key และ IP address

### ปัญหา: License Validation Failed

**สาเหตุ:** License หมดอายุหรือไม่ถูกต้อง

**วิธีแก้:**
```bash
# ตรวจสอบสถานะ license
php artisan license:status

# เช็คกับ server
php artisan license:check
```

### ปัญหา: File Integrity Check Failed

**สาเหตุ:** ไฟล์ระบบถูกแก้ไข

**วิธีแก้:**
```bash
# ตรวจสอบ integrity
php artisan app:integrity-check

# ถ้าพบปัญหา ให้ติดตั้งใหม่จาก official source
git reset --hard v{version}
composer install
```

### ปัญหา: Database Migration Failed

**สาเหตุ:** Migration ล้มเหลว

**วิธีแก้:**
```bash
# Rollback migration
php artisan migrate:rollback

# ลองใหม่
php artisan migrate

# ถ้ายังไม่ได้ ให้ restore จาก backup
mysql -u username -p database_name < storage/app/backups/database-*.sql
```

### ปัญหา: Permission Denied

**สาเหตุ:** File permissions ไม่ถูกต้อง

**วิธีแก้:**
```bash
# ตั้งค่า permissions ใหม่
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ถ้าใช้ Apache
chown -R apache:apache storage bootstrap/cache
```

### ปัญหา: 500 Internal Server Error

**วิธีแก้:**
```bash
# เปิด debug mode ชั่วคราว
# แก้ไข .env
APP_DEBUG=true

# ดู log
tail -f storage/logs/laravel.log

# แก้ไขปัญหาตาม error ที่เจอ
# อย่าลืมปิด debug mode เมื่อแก้เสร็จ
APP_DEBUG=false
```

---

## คำสั่งที่มีประโยชน์

### License Management

```bash
# Activate license
php artisan license:activate {LICENSE_KEY}

# Check license status
php artisan license:status

# Validate with server
php artisan license:check

# Deactivate license
php artisan license:deactivate
```

### System Maintenance

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed demo data
php artisan db:seed

# Clear all data (except admin)
php artisan app:clear-data --except-admin
```

### File Integrity

```bash
# Check file integrity
php artisan app:integrity-check

# Generate checksums
php artisan app:integrity-check --generate-checksums
```

---

## การติดต่อสนับสนุน

### ช่องทางการติดต่อ

📧 **Email:** support@xman4289.com
🌐 **Website:** https://xman4289.com
📚 **Documentation:** https://github.com/xjanova/TP-Affiliate/wiki

### ข้อมูลที่ควรแจ้งเมื่อขอความช่วยเหลือ

1. License Key ของคุณ
2. เวอร์ชั่นของระบบ (ดูได้จาก `php artisan app:version`)
3. รายละเอียดปัญหา
4. Error message (ถ้ามี)
5. ขั้นตอนที่ทำก่อนเกิดปัญหา

### เวลาทำการ

- **วันจันทร์ - ศุกร์:** 9:00 - 18:00 (GMT+7)
- **วันเสาร์ - อาทิตย์:** ปิดทำการ
- **ตอบกลับภายใน:** 24 ชั่วโมง (วันทำการ)

---

## คำแนะนำเพิ่มเติม

### ความปลอดภัย

✅ เปลี่ยนรหัสผ่านแอดมินเป็นประจำ
✅ Backup database เป็นประจำ
✅ อัพเดทระบบเมื่อมีเวอร์ชั่นใหม่
✅ ใช้ HTTPS (SSL/TLS)
✅ ตั้งค่า Firewall ให้เหมาะสม

### Performance

✅ ใช้ Redis หรือ Memcached สำหรับ cache (ถ้าทราฟฟิกสูง)
✅ เปิด OPcache ของ PHP
✅ ใช้ CDN สำหรับ static files
✅ Optimize images
✅ Enable gzip compression

### Backup

✅ Backup database ทุกวัน
✅ Backup files ทุกสัปดาห์
✅ เก็บ backup ไว้นอก server
✅ ทดสอบการ restore เป็นระยะ

---

**เวอร์ชั่นคู่มือ:** 1.0.0
**อัพเดทล่าสุด:** 2025-11-04
**ใช้กับ TP-Affiliate:** v1.144.0+

---

© 2025 xman4289.com. All rights reserved.
