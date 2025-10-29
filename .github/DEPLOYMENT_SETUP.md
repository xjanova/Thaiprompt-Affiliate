# การตั้งค่า GitHub Actions สำหรับ Deployment และ Laravel Sanctum

## 📋 ภาพรวม

Repository นี้มี GitHub Actions workflows ที่จะติดตั้ง Laravel Sanctum อัตโนมัติเมื่อ deploy:

- **deploy.yml** - ทดสอบการติดตั้งและ build ใน GitHub
- **deploy-production.yml** - Deploy ไปยังเซิร์ฟเวอร์จริงผ่าน SSH

## 🔐 การตั้งค่า GitHub Secrets

เพื่อให้ deployment ทำงานได้ คุณต้องตั้งค่า Secrets ใน GitHub:

### ขั้นตอนการตั้งค่า:

1. ไปที่ repository ของคุณบน GitHub
2. คลิก **Settings** > **Secrets and variables** > **Actions**
3. คลิก **New repository secret**
4. เพิ่ม secrets ต่อไปนี้:

### Required Secrets:

| Secret Name | คำอธิบาย | ตัวอย่าง |
|------------|---------|---------|
| `SERVER_HOST` | IP address หรือ domain ของเซิร์ฟเวอร์ | `192.168.1.100` หรือ `example.com` |
| `SERVER_USERNAME` | Username สำหรับ SSH | `ubuntu` หรือ `root` |
| `SERVER_SSH_KEY` | Private SSH key สำหรับเข้าถึงเซิร์ฟเวอร์ | เนื้อหาไฟล์ `~/.ssh/id_rsa` |

### Optional Secrets:

| Secret Name | คำอธิบาย | ค่าเริ่มต้น |
|------------|---------|-----------|
| `SERVER_PORT` | SSH port | `22` |
| `PROJECT_PATH` | Path ของโปรเจคบนเซิร์ฟเวอร์ | `/var/www/html` |

## 🔑 วิธีสร้าง SSH Key

หากยังไม่มี SSH key สามารถสร้างได้ดังนี้:

### บนเครื่องของคุณ:

```bash
# สร้าง SSH key pair
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# คัดลอก public key
cat ~/.ssh/id_rsa.pub
```

### บนเซิร์ฟเวอร์:

```bash
# เพิ่ม public key ไปยัง authorized_keys
echo "YOUR_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys

# ตั้งค่า permissions
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

### เพิ่ม private key ใน GitHub Secrets:

```bash
# คัดลอก private key ทั้งหมด (รวม -----BEGIN ... END-----)
cat ~/.ssh/id_rsa
```

จากนั้นนำไปวางใน GitHub Secret ชื่อ `SERVER_SSH_KEY`

## 🚀 การใช้งาน Workflows

### 1. deploy.yml (CI/CD Testing)

- รันอัตโนมัติเมื่อ push หรือ PR
- ทดสอบการติดตั้ง dependencies
- ติดตั้งและตั้งค่า Laravel Sanctum
- รัน migrations และ tests

### 2. deploy-production.yml (Production Deployment)

- รันอัตโนมัติเมื่อ push ไปยัง main/master
- หรือรันด้วยตนเองผ่าน "Actions" tab
- Deploy โค้ดไปยังเซิร์ฟเวอร์จริง
- ติดตั้ง/อัปเดต Laravel Sanctum
- รัน migrations และ optimize cache

## 📦 สิ่งที่ Workflow ทำอัตโนมัติ

### Laravel Sanctum:

```bash
# ติดตั้งและ publish config
composer install
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
```

### Database Migrations:

```bash
php artisan migrate --force
```

### Cache Optimization:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔧 การปรับแต่ง Workflow

### เปลี่ยน branch ที่จะ deploy:

แก้ไขใน `.github/workflows/deploy-production.yml`:

```yaml
on:
  push:
    branches:
      - main        # เปลี่ยนเป็น branch ที่ต้องการ
      - production
```

### เพิ่มคำสั่งเพิ่มเติม:

เพิ่มใน section `script:` ของ deploy-production.yml:

```yaml
script: |
  # ... คำสั่งเดิม ...

  # เพิ่มคำสั่งของคุณที่นี่
  php artisan queue:restart
  php artisan storage:link
```

### Restart Services:

ถ้าต้องการ restart PHP-FPM หรือ Nginx ให้เอา comment ออก:

```bash
sudo systemctl restart php8.1-fpm
sudo systemctl reload nginx
```

**หมายเหตุ:** User ที่ใช้ SSH ต้องมีสิทธิ์ sudo โดยไม่ต้องใส่รหัสผ่าน:

```bash
# เพิ่มใน /etc/sudoers
your_username ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.1-fpm
your_username ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
```

## 🐛 การแก้ปัญหา

### ถ้า deployment ล้มเหลว:

1. ตรวจสอบ logs ใน Actions tab
2. ตรวจสอบว่า Secrets ตั้งค่าถูกต้อง
3. ตรวจสอบว่า SSH key สามารถเข้าถึงเซิร์ฟเวอร์ได้
4. ตรวจสอบ permissions ของโฟลเดอร์บนเซิร์ฟเวอร์

### ทดสอบ SSH connection:

```bash
ssh -i ~/.ssh/id_rsa username@server_host -p 22
```

### ตรวจสอบ Laravel Sanctum:

```bash
# บนเซิร์ฟเวอร์
php artisan route:list | grep sanctum
ls -la config/sanctum.php
```

## ✅ Checklist การตั้งค่า

- [ ] ตั้งค่า GitHub Secrets ครบถ้วน
- [ ] ทดสอบ SSH connection
- [ ] ตรวจสอบ path ของโปรเจคบนเซิร์ฟเวอร์
- [ ] ตั้งค่า database credentials ใน `.env` บนเซิร์ฟเวอร์
- [ ] ตรวจสอบ permissions ของ storage/ และ bootstrap/cache/
- [ ] ทดสอบ manual deployment ครั้งแรก
- [ ] ตรวจสอบว่า Laravel Sanctum ทำงานได้

## 📚 เอกสารเพิ่มเติม

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)

---

หากมีปัญหาหรือข้อสงสัย กรุณาติดต่อทีมพัฒนา
