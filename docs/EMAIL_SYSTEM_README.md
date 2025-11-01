# Email Delivery Control System

ระบบควบคุมการส่งอีเมลแบบ Multi-Provider พร้อมฟีเจอร์ครบครัน

## Quick Start

### 1. ติดตั้ง Dependencies

```bash
composer require google/apiclient:"^2.0"
composer require phpmailer/phpmailer
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. ตั้งค่า Environment

เลือก 1 ใน 3 options:

#### Option A: Gmail SMTP (แนะนำสำหรับเริ่มต้น)

```env
GMAIL_SMTP_ENABLED=true
GMAIL_SMTP_USERNAME=your-email@gmail.com
GMAIL_SMTP_PASSWORD=your-app-password
```

[วิธีสร้าง App Password →](https://myaccount.google.com/apppasswords)

#### Option B: Gmail API (แนะนำสำหรับ Production)

```env
GMAIL_API_ENABLED=true
GMAIL_API_CREDENTIALS_PATH=storage/app/gmail-credentials.json
GMAIL_API_USER_EMAIL=your-email@gmail.com
```

[วิธีสร้าง Gmail API Credentials →](https://console.cloud.google.com)

#### Option C: Generic SMTP

```env
SMTP_ENABLED=true
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

### 4. เพิ่ม Provider

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
]);
```

## Usage

### ส่งอีเมลแบบธรรมดา

```php
use App\Services\EmailService;

$emailService = app(EmailService::class);

$result = $emailService->send([
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'body_html' => '<h1>Welcome to TP-Affiliate!</h1>',
    'body_text' => 'Welcome to TP-Affiliate!',
]);
```

### ส่งอีเมลด้วย Template

```php
$result = $emailService->sendWithTemplate('welcome_email', [
    'to' => $user->email,
    'user_id' => $user->id,
    'type' => 'system',
    'template_data' => [
        'user_name' => $user->name,
        'activation_link' => $link,
    ],
]);
```

### ส่งอีเมลด้วย Provider ที่กำหนด

```php
$result = $emailService->send([
    'to' => 'user@example.com',
    'subject' => 'Test',
    'body_html' => '<p>Test</p>',
    'provider' => 'gmail_api', // บังคับใช้ Gmail API
]);
```

## Features

### ✨ Multi-Provider Support

ระบบรองรับหลาย Email Providers:
- **Gmail API** - สำหรับ high-volume sending
- **Gmail SMTP** - ใช้งานง่าย ตั้งค่าเร็ว
- **Generic SMTP** - รองรับ SMTP ทั่วไป (Mailtrap, SendGrid, etc.)

### 🔄 Automatic Failover

เมื่อ Provider หลักล้มเหลว ระบบจะสลับไปใช้ Provider สำรอง:

```
Gmail API (Priority 100) [FAILED]
  ↓ Auto Fallback
Gmail SMTP (Priority 80) [SUCCESS]
```

### 📊 Email Tracking

ติดตามสถานะการส่งอีเมล:
- ✅ Sent
- ❌ Failed
- 🔄 Pending
- 📭 Bounced
- 👁️ Opened
- 🔗 Clicked

### 🚦 Rate Limiting

ป้องกันการส่งเกิน limit:

```php
// Provider จะถูก skip อัตโนมัติถ้าถึง limit
EmailProvider::create([
    'daily_limit' => 500,
    'hourly_limit' => 100,
]);
```

### 📝 Email Templates

สร้างและจัดการ Template ได้ง่าย:

```php
use App\Models\EmailTemplate;

EmailTemplate::create([
    'name' => 'welcome_email',
    'subject' => 'Welcome {{user_name}}!',
    'body_html' => '<h1>Hello {{user_name}}!</h1>',
    'variables' => ['user_name', 'activation_link'],
    'category' => 'system',
]);
```

### ⚙️ User Preferences

ผู้ใช้สามารถตั้งค่าการรับอีเมลได้เอง:

```php
use App\Models\EmailPreference;

$preference = EmailPreference::getOrCreateForUser($userId);
$preference->update([
    'marketing_emails' => false,      // ไม่รับอีเมล marketing
    'security_alerts' => true,        // รับ security alerts
    'commission_notifications' => true,
]);
```

### 🔁 Retry Logic

ลองส่งอีเมลอีกครั้งอัตโนมัติเมื่อล้มเหลว:

```env
EMAIL_RETRY_ENABLED=true
EMAIL_MAX_RETRY_ATTEMPTS=3
EMAIL_RETRY_DELAY=60
```

## Admin Panel

เข้าถึงได้ที่: `/admin/email`

### Dashboard
- ดู Email Statistics
- Monitor Provider Health
- ดู Recent Logs

### Providers
- จัดการ Email Providers
- Test Connection
- Set Default Provider
- View Health Status

### Templates
- สร้าง/แก้ไข Email Templates
- Preview Templates
- Manage Variables

### Logs
- ดู Email Logs
- Filter by Status/Provider/Date
- View Email Details
- Retry Failed Emails

## API Endpoints

### Admin

```
GET    /admin/email              # Dashboard
GET    /admin/email/logs         # Email Logs
GET    /admin/email/providers    # Providers List
POST   /admin/email/providers    # Create Provider
POST   /admin/email/test         # Send Test Email
```

### User

```
GET    /user/email/preferences        # View Preferences
PUT    /user/email/preferences        # Update Preferences
POST   /user/email/preferences/disable-all
POST   /user/email/preferences/enable-all
```

## Configuration

ดูได้ที่ `config/email.php`:

```php
return [
    'default_provider' => env('EMAIL_DEFAULT_PROVIDER', 'smtp'),

    'tracking' => [
        'enabled' => env('EMAIL_TRACKING_ENABLED', true),
        'track_opens' => env('EMAIL_TRACK_OPENS', true),
        'track_clicks' => env('EMAIL_TRACK_CLICKS', true),
    ],

    'retry' => [
        'enabled' => env('EMAIL_RETRY_ENABLED', true),
        'max_attempts' => env('EMAIL_MAX_RETRY_ATTEMPTS', 3),
    ],

    'rate_limit' => [
        'enabled' => env('EMAIL_RATE_LIMIT_ENABLED', true),
        'max_per_hour' => env('EMAIL_MAX_PER_HOUR', 100),
        'max_per_day' => env('EMAIL_MAX_PER_DAY', 1000),
    ],
];
```

## Gmail Limits

### Gmail SMTP
- **Free Gmail:** 500 emails/day, 100 emails/hour
- **Google Workspace:** 2,000 emails/day

### Gmail API
- **Free Gmail:** 500 emails/day (per user)
- **Google Workspace:** 10,000 emails/day (per user)

## Comparison: SMTP vs API

| Feature | Gmail SMTP | Gmail API |
|---------|-----------|-----------|
| Setup | ⭐⭐⭐ Easy | ⭐⭐ Medium |
| Daily Limit | 500 | 500+ |
| Authentication | App Password | OAuth2 |
| Tracking | Limited | Full |
| Recommended For | Small apps | Production |

## Troubleshooting

### อีเมลไม่ส่ง

```bash
# 1. ตรวจสอบ logs
tail -f storage/logs/laravel.log

# 2. ตรวจสอบ provider status
php artisan tinker
>>> App\Models\EmailProvider::all()

# 3. Test provider connection
>>> $provider = App\Models\EmailProvider::first()
>>> app(App\Services\EmailService::class)->testProvider($provider)
```

### Gmail SMTP ไม่ work

1. ✅ ตรวจสอบว่าเปิด 2FA แล้ว
2. ✅ ใช้ App Password (ไม่ใช่ password จริง)
3. ✅ ตรวจสอบ host/port ถูกต้อง (smtp.gmail.com:587)

### Gmail API ไม่ work

1. ✅ ตรวจสอบ credentials file อยู่ที่ storage/app/
2. ✅ OAuth consent screen ตั้งค่าแล้ว
3. ✅ Gmail API enabled ใน Google Cloud Console
4. ✅ Redirect URI ตรงกับที่ตั้งค่า

## Best Practices

### 1. ใช้ Multiple Providers

```php
// Setup 2-3 providers with different priorities
Gmail API    (Priority 100) - Primary
Gmail SMTP   (Priority 80)  - Fallback
SMTP/Mailtrap (Priority 50)  - Last resort
```

### 2. Monitor Email Stats

```php
// ดู statistics เป็นประจำ
$stats = app(EmailService::class)->getStats([
    'date_from' => now()->subDays(7),
]);

// Success rate ควรสูงกว่า 95%
$successRate = ($stats['sent'] / $stats['total']) * 100;
```

### 3. Use Queue for Bulk Emails

```php
// สำหรับส่งอีเมลจำนวนมาก
dispatch(new SendEmailJob($emailData))->onQueue('emails');
```

### 4. Respect User Preferences

```php
// ตรวจสอบ preferences ก่อนส่ง
$result = $emailService->send([
    'to' => $user->email,
    'user_id' => $user->id,
    'type' => 'marketing', // ระบบจะตรวจสอบ preference อัตโนมัติ
    'subject' => '...',
]);
```

## Documentation

- 📖 [Full Setup Guide](./EMAIL_SETUP_GUIDE.md)
- 🔧 [Gmail SMTP Setup](./EMAIL_SETUP_GUIDE.md#gmail-smtp-setup)
- 🔌 [Gmail API Setup](./EMAIL_SETUP_GUIDE.md#gmail-api-setup)
- 💡 [Tips & Best Practices](./EMAIL_SETUP_GUIDE.md#tips-และ-best-practices)
- 🐛 [Troubleshooting](./EMAIL_SETUP_GUIDE.md#troubleshooting)

## License

Part of TP-Affiliate System - Proprietary Software

---

**Need Help?** อ่าน [Full Setup Guide](./EMAIL_SETUP_GUIDE.md) สำหรับคำแนะนำแบบละเอียด
