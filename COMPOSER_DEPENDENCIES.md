# Composer Dependencies for Email System

## Required Packages

ติดตั้ง packages เหล่านี้ก่อนใช้งาน Email Delivery Control System:

### 1. Google API PHP Client

สำหรับ Gmail API Provider:

```bash
composer require google/apiclient:"^2.0"
```

### 2. PHPMailer

สำหรับ SMTP Providers (Gmail SMTP, Generic SMTP):

```bash
composer require phpmailer/phpmailer
```

## Installation Command

ติดตั้งทั้งหมดพร้อมกัน:

```bash
composer require google/apiclient:"^2.0" phpmailer/phpmailer
```

## After Installation

1. Run migrations:
```bash
php artisan migrate
```

2. ตั้งค่า environment variables ใน `.env`

3. เพิ่ม Email Provider ผ่าน Admin Panel หรือ Tinker

---

**Note:** ถ้าไม่ต้องการใช้ Gmail API สามารถติดตั้งแค่ PHPMailer ได้:

```bash
composer require phpmailer/phpmailer
```
