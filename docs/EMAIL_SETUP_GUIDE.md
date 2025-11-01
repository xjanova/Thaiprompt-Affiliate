# Email Delivery Control System - Setup Guide

คู่มือการติดตั้งและใช้งานระบบควบคุมการส่งอีเมลสำหรับ TP-Affiliate

## สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [Google Email Services](#google-email-services)
   - [Gmail SMTP Setup](#gmail-smtp-setup)
   - [Gmail API Setup](#gmail-api-setup)
3. [การติดตั้งและการตั้งค่า](#การติดตั้งและการตั้งค่า)
4. [Tips และ Best Practices](#tips-และ-best-practices)
5. [Troubleshooting](#troubleshooting)

---

## ภาพรวมระบบ

ระบบ Email Delivery Control ของ TP-Affiliate รองรับหลาย Email Provider พร้อมฟีเจอร์:

### ✨ คุณสมบัติหลัก

- ✅ **Multi-Provider Support** - รองรับหลาย Email Provider (Gmail API, Gmail SMTP, SMTP)
- ✅ **Automatic Failover** - สลับ Provider อัตโนมัติเมื่อเกิดข้อผิดพลาด
- ✅ **Email Tracking** - ติดตามสถานะการส่ง, การเปิด, การคลิก
- ✅ **Rate Limiting** - จำกัดอัตราการส่งอีเมลเพื่อป้องกัน abuse
- ✅ **Email Templates** - จัดการ Template อีเมลได้ง่าย
- ✅ **User Preferences** - ผู้ใช้สามารถตั้งค่าการรับอีเมลได้เอง
- ✅ **Retry Logic** - ลองส่งอีเมลอีกครั้งอัตโนมัติเมื่อล้มเหลว
- ✅ **Health Monitoring** - ตรวจสอบสุขภาพของ Provider

### 📊 Database Schema

ระบบใช้ 4 ตารางหลัก:

1. **email_logs** - บันทึกการส่งอีเมลทุกครั้ง
2. **email_preferences** - การตั้งค่าการรับอีเมลของผู้ใช้
3. **email_templates** - Template สำหรับอีเมล
4. **email_providers** - การตั้งค่า Email Providers

---

## Google Email Services

Google มีบริการส่งอีเมล 2 แบบ ที่เหมาะสำหรับแอปพลิเคชัน:

### 1. Gmail SMTP (แนะนำสำหรับเริ่มต้น)

**ข้อดี:**
- ✅ ตั้งค่าง่าย ไม่ต้องใช้ API Credentials
- ✅ เหมาะสำหรับปริมาณอีเมลไม่มาก (< 500 emails/day)
- ✅ ใช้ App Password ปลอดภัย

**ข้อจำกัด:**
- ⚠️ ส่งได้สูงสุด 500 emails ต่อวัน (Free tier)
- ⚠️ ส่งได้สูงสุด 100 emails ต่อชั่วโมง

### 2. Gmail API (แนะนำสำหรับ Production)

**ข้อดี:**
- ✅ ส่งได้สูงสุด 1 billion emails ต่อวัน (มี quota แยกตาม user)
- ✅ ควบคุมได้มากกว่า (tracking, labels, filters)
- ✅ รองรับ OAuth2 authentication

**ข้อจำกัด:**
- ⚠️ ต้องตั้งค่า Google Cloud Project
- ⚠️ ซับซ้อนกว่าในการ setup

---

## Gmail SMTP Setup

### ขั้นตอนที่ 1: เปิดใช้งาน 2-Factor Authentication

1. ไปที่ [Google Account Security](https://myaccount.google.com/security)
2. เปิดใช้งาน **2-Step Verification**

### ขั้นตอนที่ 2: สร้าง App Password

1. ไปที่ [App Passwords](https://myaccount.google.com/apppasswords)
2. เลือก **Mail** และเลือกอุปกรณ์เป็น **Other (Custom name)**
3. ตั้งชื่อว่า "TP-Affiliate"
4. คัดลอก App Password ที่ได้ (16 ตัวอักษร)

### ขั้นตอนที่ 3: ตั้งค่าใน .env

```env
# Gmail SMTP Configuration
GMAIL_SMTP_ENABLED=true
GMAIL_SMTP_HOST=smtp.gmail.com
GMAIL_SMTP_PORT=587
GMAIL_SMTP_USERNAME=your-email@gmail.com
GMAIL_SMTP_PASSWORD=your-app-password-here
GMAIL_SMTP_ENCRYPTION=tls
GMAIL_SMTP_FROM_EMAIL=your-email@gmail.com
GMAIL_SMTP_FROM_NAME="TP-Affiliate"
GMAIL_SMTP_DAILY_LIMIT=500
GMAIL_SMTP_HOURLY_LIMIT=100
```

### ขั้นตอนที่ 4: เพิ่ม Provider ในระบบ

```bash
php artisan tinker
```

```php
use App\Models\EmailProvider;

EmailProvider::create([
    'name' => 'gmail_smtp',
    'display_name' => 'Gmail SMTP',
    'type' => 'smtp',
    'configuration' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'TP-Affiliate',
    ],
    'is_active' => true,
    'is_default' => true,
    'priority' => 80,
    'daily_limit' => 500,
    'hourly_limit' => 100,
]);
```

---

## Gmail API Setup

### ขั้นตอนที่ 1: สร้าง Google Cloud Project

1. ไปที่ [Google Cloud Console](https://console.cloud.google.com)
2. สร้าง Project ใหม่ หรือเลือก Project ที่มีอยู่
3. จดชื่อ Project ID ไว้

### ขั้นตอนที่ 2: เปิดใช้งาน Gmail API

1. ไปที่ [API Library](https://console.cloud.google.com/apis/library)
2. ค้นหา "Gmail API"
3. คลิก **Enable**

### ขั้นตอนที่ 3: สร้าง OAuth 2.0 Credentials

1. ไปที่ [Credentials](https://console.cloud.google.com/apis/credentials)
2. คลิก **Create Credentials** > **OAuth client ID**
3. เลือก Application type: **Web application**
4. ตั้งชื่อ: "TP-Affiliate Email"
5. เพิ่ม **Authorized redirect URIs**:
   ```
   https://your-domain.com/admin/email/gmail/callback
   http://localhost/admin/email/gmail/callback (สำหรับ dev)
   ```
6. คลิก **Create**
7. ดาวน์โหลด JSON credentials file

### ขั้นตอนที่ 4: Configure OAuth Consent Screen

1. ไปที่ [OAuth consent screen](https://console.cloud.google.com/apis/credentials/consent)
2. เลือก **External** (สำหรับ testing) หรือ **Internal** (สำหรับ Google Workspace)
3. กรอกข้อมูล:
   - App name: "TP-Affiliate"
   - User support email: your-email@gmail.com
   - Developer contact: your-email@gmail.com
4. เพิ่ม Scopes:
   - `https://www.googleapis.com/auth/gmail.send`
   - `https://www.googleapis.com/auth/gmail.readonly`
5. เพิ่ม Test users (สำหรับ External):
   - your-email@gmail.com

### ขั้นตอนที่ 5: ตั้งค่าใน .env

```env
# Gmail API Configuration
GMAIL_API_ENABLED=true
GMAIL_API_CREDENTIALS_PATH=storage/app/gmail-credentials.json
GMAIL_API_USER_EMAIL=your-email@gmail.com
GMAIL_API_FROM_EMAIL=your-email@gmail.com
GMAIL_API_FROM_NAME="TP-Affiliate"
GMAIL_API_REDIRECT_URI="${APP_URL}/admin/email/gmail/callback"
GMAIL_API_DAILY_LIMIT=500
```

### ขั้นตอนที่ 6: Upload Credentials File

1. คัดลอก JSON credentials file ที่ดาวน์โหลดมา
2. วางไว้ที่ `storage/app/gmail-credentials.json`

### ขั้นตอนที่ 7: เพิ่ม Provider และ Authorize

1. เข้าสู่ระบบ Admin Panel
2. ไปที่ **Email Management** > **Providers**
3. คลิก **Add Provider**
4. เลือก Type: **Gmail API**
5. กรอกข้อมูล configuration
6. คลิก **Authorize with Google**
7. อนุญาตการเข้าถึง Gmail
8. ระบบจะบันทึก Access Token และ Refresh Token อัตโนมัติ

---

## การติดตั้งและการตั้งค่า

### 1. Install Dependencies

```bash
# Install Google API PHP Client
composer require google/apiclient:"^2.0"

# Install PHPMailer (สำหรับ SMTP)
composer require phpmailer/phpmailer
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=email-config
```

### 4. Set Default Provider

เข้าสู่ Admin Panel:
1. ไปที่ **Email Management** > **Providers**
2. เลือก Provider ที่ต้องการใช้เป็น Default
3. คลิก **Set as Default**

### 5. Test Email Sending

```bash
# ผ่าน Tinker
php artisan tinker
```

```php
use App\Services\EmailService;

$emailService = app(EmailService::class);

$result = $emailService->send([
    'to' => 'recipient@example.com',
    'subject' => 'Test Email',
    'body_html' => '<h1>Hello!</h1><p>This is a test email.</p>',
    'body_text' => 'Hello! This is a test email.',
]);

// ตรวจสอบผลลัพธ์
dd($result);
```

---

## Tips และ Best Practices

### 🎯 Tips สำหรับการใช้งาน

#### 1. เลือก Provider ที่เหมาะสม

**สำหรับเริ่มต้น (< 500 emails/day):**
```
Gmail SMTP ✅
```

**สำหรับ Production (> 500 emails/day):**
```
Gmail API + Fallback SMTP
```

#### 2. ตั้งค่า Priority และ Failover

```php
// Provider Priority (ในฐานข้อมูล)
Gmail API:  Priority 100 (ลองก่อน)
Gmail SMTP: Priority 80  (ถ้า Gmail API fail)
SMTP:       Priority 50  (ถ้าทั้งสองอันแรก fail)
```

#### 3. Monitor Email Logs

ตรวจสอบ Email Logs เป็นประจำ:
- Admin Panel > Email Management > Logs
- ดูอัตราความสำเร็จ (Success Rate)
- ตรวจสอบ Bounced และ Failed emails

#### 4. Set Reasonable Limits

```env
# สำหรับ Gmail SMTP
GMAIL_SMTP_DAILY_LIMIT=450  # เผื่อไว้ไม่ถึง limit
GMAIL_SMTP_HOURLY_LIMIT=90

# สำหรับ Gmail API
GMAIL_API_DAILY_LIMIT=500
```

#### 5. Use Email Templates

สร้าง Email Templates สำหรับการส่งที่ซ้ำๆ:

```php
// ส่งอีเมลด้วย Template
$emailService->sendWithTemplate('welcome_email', [
    'to' => $user->email,
    'template_data' => [
        'user_name' => $user->name,
        'activation_link' => $link,
    ],
]);
```

### 🔒 Security Best Practices

1. **ใช้ App Password แทน Gmail Password**
   - ไม่เคยใช้ password จริงของ Gmail
   - ใช้ App Password ที่สร้างจาก Google

2. **เก็บ Credentials อย่างปลอดภัย**
   - ไม่ commit credentials file ลง Git
   - ใช้ Environment Variables
   - Encrypt sensitive data ในฐานข้อมูล

3. **ตั้งค่า CORS และ Redirect URIs อย่างถูกต้อง**
   - เพิ่มเฉพาะ domain ที่ต้องการ
   - ใช้ HTTPS ใน Production

4. **Monitor Suspicious Activity**
   - ตั้งค่า alerts สำหรับ failed emails มากเกินไป
   - ตรวจสอบ bounce rate

### ⚡ Performance Tips

1. **ใช้ Queue สำหรับส่งอีเมลจำนวนมาก**

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

// ใช้ Queue
dispatch(new SendEmailJob($emailData))->onQueue('emails');
```

2. **Enable Caching**

```env
CACHE_DRIVER=redis
```

3. **Batch Processing**

```php
// ส่งอีเมลเป็น batch
foreach (array_chunk($recipients, 50) as $batch) {
    foreach ($batch as $recipient) {
        $emailService->send([...]);
    }
    sleep(1); // Delay ระหว่าง batch
}
```

### 📈 Monitoring และ Analytics

1. **ดู Email Statistics**
   - Admin Panel > Email Management > Statistics
   - ติดตาม:
     - Total Sent
     - Success Rate
     - Bounce Rate
     - Open Rate (ถ้าเปิด tracking)

2. **Provider Health Checks**
   - ระบบจะตรวจสอบ health ของ provider อัตโนมัติ
   - ดูได้ที่ Admin Panel > Email Management > Providers

3. **Set up Alerts**

```php
// ใน AutoBanService หรือ Custom Service
if ($failedEmailCount > 100) {
    // ส่ง alert ไปหา admin
    Notification::route('mail', 'admin@example.com')
        ->notify(new EmailSystemAlert($failedEmailCount));
}
```

---

## Troubleshooting

### ❌ Gmail SMTP: "Username and Password not accepted"

**สาเหตุ:**
- ใช้ password จริงแทน App Password
- 2FA ยังไม่เปิด

**วิธีแก้:**
1. เปิด 2-Factor Authentication
2. สร้าง App Password ใหม่
3. ใช้ App Password แทน password จริง

### ❌ Gmail API: "Invalid Credentials"

**สาเหตุ:**
- Credentials file ผิด
- OAuth consent screen ยังไม่ตั้งค่า

**วิธีแก้:**
1. ตรวจสอบ credentials file ที่ `storage/app/gmail-credentials.json`
2. ตรวจสอบ OAuth consent screen ใน Google Cloud Console
3. Authorize ใหม่ผ่าน Admin Panel

### ❌ "Daily sending quota exceeded"

**สาเหตุ:**
- ส่งอีเมลเกิน limit ของ Gmail

**วิธีแก้:**
1. รอให้ quota reset (ทุก 24 ชั่วโมง)
2. เพิ่ม Provider อื่นเป็น fallback
3. พิจารณาใช้ Google Workspace (limit สูงกว่า)

### ❌ Emails ไม่ส่ง

**ตรวจสอบ:**
1. Provider status ใน Admin Panel
2. Email Logs สำหรับ error messages
3. Laravel Logs: `storage/logs/laravel.log`
4. Queue worker (ถ้าใช้ queue):
   ```bash
   php artisan queue:work --verbose
   ```

### ❌ OAuth Token Expired

**วิธีแก้:**
1. Refresh token จะถูกใช้อัตโนมัติ
2. ถ้า refresh ไม่สำเร็จ ให้ authorize ใหม่ผ่าน Admin Panel

---

## การอัพเกรดและบำรุงรักษา

### Regular Maintenance

1. **ตรวจสอบ Email Logs เป็นประจำ**
   - ลบ logs เก่าที่ไม่ใช้แล้ว
   - วิเคราะห์ trends

2. **Update Dependencies**
   ```bash
   composer update google/apiclient
   composer update phpmailer/phpmailer
   ```

3. **Backup Configuration**
   - Export provider settings
   - Backup email templates

4. **Monitor Quota Usage**
   - ดู daily/hourly sent count
   - วางแผนเพิ่ม provider ถ้าใกล้ limit

---

## ทรัพยากรเพิ่มเติม

### Google Documentation
- [Gmail API Documentation](https://developers.google.com/gmail/api)
- [Gmail SMTP Settings](https://support.google.com/mail/answer/7126229)
- [Google Cloud Console](https://console.cloud.google.com)

### Package Documentation
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)

### TP-Affiliate Resources
- Email Service: `app/Services/EmailService.php`
- Providers: `app/Services/Email/Providers/`
- Models: `app/Models/Email*.php`
- Configuration: `config/email.php`

---

## สรุป

ระบบ Email Delivery Control ของ TP-Affiliate ถูกออกแบบมาให้:
- ✅ ใช้งานง่าย
- ✅ ปรับขนาดได้ (Scalable)
- ✅ เชื่อถือได้ (Reliable)
- ✅ Monitor ได้ (Monitorable)

**แนะนำสำหรับ Production:**
1. ใช้ Gmail API เป็น primary provider
2. ตั้งค่า Gmail SMTP เป็น fallback
3. เปิด email tracking และ monitoring
4. ตั้งค่า rate limiting อย่างเหมาะสม
5. ตรวจสอบ logs และ statistics เป็นประจำ

---

**จัดทำโดย:** TP-Affiliate Development Team
**Version:** 1.0.0
**อัพเดทล่าสุด:** 2025-01-01
