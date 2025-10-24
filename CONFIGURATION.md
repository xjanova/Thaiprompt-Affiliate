# ⚙️ คู่มือการตั้งค่า ThaiPrompt Marketplace

เอกสารนี้อธิบายการตั้งค่าต่างๆ ของระบบอย่างละเอียด

---

## 📋 สารบัญ

1. [Environment Variables](#environment-variables)
2. [การตั้งค่า Database](#การตั้งค่า-database)
3. [การตั้งค่า Email](#การตั้งค่า-email)
4. [การตั้งค่า Payment Gateway](#การตั้งค่า-payment-gateway)
5. [การตั้งค่า MLM](#การตั้งค่า-mlm)
6. [การตั้งค่า LINE Official Account](#การตั้งค่า-line-official-account)
7. [การตั้งค่า File Storage](#การตั้งค่า-file-storage)
8. [การตั้งค่า Cache & Queue](#การตั้งค่า-cache--queue)
9. [การตั้งค่า Security](#การตั้งค่า-security)
10. [การตั้งค่า NFC](#การตั้งค่า-nfc)

---

## Environment Variables

### ตัวแปรพื้นฐาน

```env
# Application
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=local                    # local, staging, production
APP_DEBUG=true                   # true สำหรับ development, false สำหรับ production
APP_URL=http://localhost:8000    # URL หลักของแอพ
APP_TIMEZONE=Asia/Bangkok        # Timezone

# Locale
APP_LOCALE=th                    # ภาษาหลัก
APP_FALLBACK_LOCALE=en           # ภาษาสำรอง
APP_FAKER_LOCALE=th_TH           # Locale สำหรับ Faker
```

---

## การตั้งค่า Database

### MySQL Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=thaiprompt
DB_PASSWORD=your_secure_password
```

### การปรับแต่ง Performance

แก้ไข `config/database.php`:

```php
'mysql' => [
    // ... existing config
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]) : [],
    'strict' => true,
    'engine' => 'InnoDB',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

### Multiple Database Connections

ถ้าต้องการใช้ database หลายตัว:

```env
DB_CONNECTION_ANALYTICS=mysql
DB_HOST_ANALYTICS=127.0.0.1
DB_DATABASE_ANALYTICS=analytics_db
DB_USERNAME_ANALYTICS=analytics_user
DB_PASSWORD_ANALYTICS=analytics_password
```

```php
// config/database.php
'analytics' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST_ANALYTICS', '127.0.0.1'),
    'database' => env('DB_DATABASE_ANALYTICS'),
    // ...
],
```

---

## การตั้งค่า Email

### Development (Mailpit)

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@thaiprompt.local"
MAIL_FROM_NAME="${APP_NAME}"
```

### Production (Gmail)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@thaiprompt.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**วิธีสร้าง Gmail App Password:**
1. ไปที่ Google Account Settings
2. Security → 2-Step Verification
3. App passwords → Generate new password
4. เลือก "Mail" และ "Other (Custom name)"
5. คัดลอก password 16 ตัว

### Production (AWS SES)

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_SES_REGION=ap-southeast-1
MAIL_FROM_ADDRESS="noreply@thaiprompt.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Production (Mailgun)

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.thaiprompt.com
MAILGUN_SECRET=your_mailgun_api_key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="noreply@thaiprompt.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Email Templates Customization

Email templates อยู่ที่ `resources/views/emails/`

แก้ไข styling ใน `resources/views/vendor/mail/`:
```bash
php artisan vendor:publish --tag=laravel-mail
```

---

## การตั้งค่า Payment Gateway

### Stripe

#### 1. สมัครบัญชี Stripe

ไปที่ https://dashboard.stripe.com/register

#### 2. รับ API Keys

Dashboard → Developers → API keys:
- Publishable key: `pk_test_...` (test mode) หรือ `pk_live_...` (live mode)
- Secret key: `sk_test_...` (test mode) หรือ `sk_live_...` (live mode)

#### 3. ตั้งค่า Webhook

Dashboard → Developers → Webhooks → Add endpoint:
- **URL:** `https://yourdomain.com/api/webhooks/stripe`
- **Events to send:**
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `charge.refunded`
  - `checkout.session.completed`

#### 4. คัดลอก Webhook Secret

หลังสร้าง webhook จะได้ signing secret: `whsec_...`

#### 5. อัพเดท .env

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxx
```

#### 6. ทดสอบ Webhook (Local)

ติดตั้ง Stripe CLI:
```bash
# macOS
brew install stripe/stripe-cli/stripe

# Windows
scoop bucket add stripe https://github.com/stripe/scoop-stripe-cli.git
scoop install stripe

# Linux
wget https://github.com/stripe/stripe-cli/releases/download/v1.16.0/stripe_1.16.0_linux_x86_64.tar.gz
tar -xvf stripe_1.16.0_linux_x86_64.tar.gz
sudo mv stripe /usr/local/bin
```

Login และ forward events:
```bash
stripe login
stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

### PromptPay

#### 1. สมัครบัญชี PromptPay Merchant

ติดต่อธนาคารของคุณเพื่อสมัครบริการ PromptPay QR Payment

#### 2. รับ API Credentials

จากธนาคารจะได้รับ:
- Merchant ID
- Terminal ID
- API Key
- API Secret

#### 3. ตั้งค่าใน .env

```env
PROMPTPAY_MERCHANT_ID=your_merchant_id
PROMPTPAY_TERMINAL_ID=your_terminal_id
PROMPTPAY_API_KEY=your_api_key
PROMPTPAY_API_SECRET=your_api_secret
PROMPTPAY_API_URL=https://api.promptpay.io/v1
PROMPTPAY_WEBHOOK_SECRET=your_webhook_secret
```

#### 4. ตั้งค่า Webhook

แจ้งธนาคารให้ส่ง webhook ไปที่:
```
POST https://yourdomain.com/api/webhooks/promptpay
```

---

## การตั้งค่า MLM

### ประเภท MLM Structure

```env
# ประเภท: unilevel, binary, matrix, hybrid
MLM_TYPE=unilevel
```

#### Unilevel
- ไม่จำกัดจำนวน direct referrals
- คำนวณค่าคอมมิชชั่นตามระดับ (level)

#### Binary
- จำกัด 2 ขา (left และ right)
- มี matching bonus

#### Matrix
- จำกัดความกว้างและความลึก เช่น 3x7

### การตั้งค่าค่าคอมมิชชั่น

```env
# ระดับสูงสุดที่จะคำนวณค่าคอมมิชชั่น
MLM_MAX_DEPTH=10

# อัตราค่าคอมมิชชั่นแต่ละระดับ (%)
COMMISSION_RATE_LEVEL_1=10    # ระดับ 1: 10%
COMMISSION_RATE_LEVEL_2=5     # ระดับ 2: 5%
COMMISSION_RATE_LEVEL_3=3     # ระดับ 3: 3%
COMMISSION_RATE_LEVEL_4=2     # ระดับ 4: 2%
COMMISSION_RATE_LEVEL_5=1     # ระดับ 5: 1%
COMMISSION_RATE_LEVEL_6=1
COMMISSION_RATE_LEVEL_7=1
COMMISSION_RATE_LEVEL_8=0.5
COMMISSION_RATE_LEVEL_9=0.5
COMMISSION_RATE_LEVEL_10=0.5
```

### การแบ่งรายได้ระหว่าง Vendor และ Platform

```env
# Vendor จะได้รับจากการขายสินค้า (%)
VENDOR_COMMISSION_RATE=70

# Platform/Admin จะได้รับ (%)
ADMIN_COMMISSION_RATE=30
```

**ตัวอย่าง:**
- สินค้าราคา 1,000 บาท
- Vendor ได้: 700 บาท (70%)
- Admin ได้: 300 บาท (30%)
- จาก 300 บาท ของ Admin จะนำไปแจกจ่ายเป็นค่าคอมมิชชั่น MLM

### Rank Settings

แก้ไขใน Database Seeder หรือ Admin Panel:

```php
// database/seeders/MlmRankSeeder.php
MlmRank::create([
    'name' => 'Diamond',
    'min_personal_sales' => 100000,  // ยอดขายส่วนตัวขั้นต่ำ
    'min_team_sales' => 1000000,     // ยอดขายทีมขั้นต่ำ
    'min_direct_referrals' => 10,    // จำนวน referrals โดยตรงขั้นต่ำ
    'bonus_percentage' => 5,          // โบนัสพิเศษ (%)
]);
```

### การตั้งค่า Genealogy

```env
# จำนวนระดับที่จะแสดงใน Genealogy Tree
MLM_GENEALOGY_MAX_DEPTH=5

# การแสดงผล: tree, table, list
MLM_GENEALOGY_VIEW=tree
```

---

## การตั้งค่า LINE Official Account

### 1. สร้าง LINE Official Account

1. ไปที่ https://developers.line.biz/
2. Login ด้วย LINE account
3. Create a new provider (ถ้ายังไม่มี)
4. Create a Messaging API channel

### 2. ตั้งค่า Channel

**Basic settings:**
- Channel name: ThaiPrompt Marketplace
- Channel description: ระบบร้านค้า MLM
- Category: E-commerce

**Messaging API:**
- Enable Messaging API
- Issue Channel access token

### 3. คัดลอก Credentials

จาก Channel basic settings:
- **Channel ID:** 1234567890
- **Channel secret:** xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

จาก Messaging API:
- **Channel access token:** คลิก "Issue" แล้วคัดลอก

### 4. ตั้งค่าใน .env

```env
LINE_CHANNEL_ID=1234567890
LINE_CHANNEL_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
LINE_CHANNEL_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 5. ตั้งค่า Webhook URL

ใน LINE Developers Console:
1. ไปที่ Messaging API settings
2. Webhook URL: `https://yourdomain.com/api/webhooks/line`
3. Enable "Use webhook"
4. Verify webhook (ทดสอบการเชื่อมต่อ)

### 6. ปิดการตอบกลับอัตโนมัติ

Messaging API settings:
- Auto-reply messages: Disabled
- Greeting messages: Enabled (optional)

### 7. เพิ่ม Rich Menu (Optional)

สร้าง Rich Menu ผ่าน LINE Developers หรือ API:

```bash
php artisan line:create-rich-menu
```

---

## การตั้งค่า File Storage

### Local Storage (Development)

```env
FILESYSTEM_DISK=local
```

ไฟล์จะถูกเก็บที่ `storage/app/`

### Public Storage

```env
FILESYSTEM_DISK=public
```

ไฟล์จะถูกเก็บที่ `storage/app/public/` และเข้าถึงผ่าน `/storage/`

**สร้าง symbolic link:**
```bash
php artisan storage:link
```

### AWS S3 (Production)

#### 1. สร้าง S3 Bucket

1. ไปที่ AWS Console → S3
2. Create bucket: `thaiprompt-uploads`
3. Region: ap-southeast-1 (Singapore)
4. Block public access: OFF (เฉพาะไฟล์ที่ต้องการ)

#### 2. สร้าง IAM User

1. IAM → Users → Add user
2. Access type: Programmatic access
3. Attach policy: `AmazonS3FullAccess`
4. Download credentials (Access Key ID และ Secret Access Key)

#### 3. ตั้งค่าใน .env

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=thaiprompt-uploads
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://thaiprompt-uploads.s3.ap-southeast-1.amazonaws.com
```

#### 4. ติดตั้ง AWS SDK

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### Digital Ocean Spaces

```env
FILESYSTEM_DISK=spaces

DO_SPACES_KEY=your_access_key
DO_SPACES_SECRET=your_secret_key
DO_SPACES_ENDPOINT=https://sgp1.digitaloceanspaces.com
DO_SPACES_REGION=sgp1
DO_SPACES_BUCKET=thaiprompt
```

---

## การตั้งค่า Cache & Queue

### Cache Drivers

#### File Cache (Development)

```env
CACHE_DRIVER=file
```

#### Redis Cache (Production)

ติดตั้ง Redis:
```bash
# Ubuntu
sudo apt install redis-server

# macOS
brew install redis
brew services start redis
```

ตั้งค่า:
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

ติดตั้ง PHP Redis extension:
```bash
pecl install redis
```

### Queue Drivers

#### Database Queue

```env
QUEUE_CONNECTION=database
```

สร้างตาราง jobs:
```bash
php artisan queue:table
php artisan migrate
```

รัน queue worker:
```bash
php artisan queue:work
```

#### Redis Queue (แนะนำสำหรับ Production)

```env
QUEUE_CONNECTION=redis
```

รัน multiple workers:
```bash
php artisan queue:work redis --queue=default,emails,notifications
```

#### Supervisor Configuration

สร้าง `/etc/supervisor/conf.d/thaiprompt-worker.conf`:

```ini
[program:thaiprompt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/thaiprompt/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/thaiprompt/storage/logs/worker.log
stopwaitsecs=3600
```

เริ่มใช้งาน:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start thaiprompt-worker:*
```

---

## การตั้งค่า Security

### HTTPS Configuration

```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

### CORS Settings

แก้ไข `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['https://yourdomain.com'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### Rate Limiting

แก้ไข `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1', // 60 requests per minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

สร้าง custom rate limiters ใน `app/Providers/RouteServiceProvider.php`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

### XSS Protection

ใช้ `{{ }}` แทน `{!! !!}` ใน Blade templates:

```blade
{{-- Safe (escaped) --}}
{{ $user->name }}

{{-- Unsafe (raw HTML) - ใช้เฉพาะเมื่อจำเป็น --}}
{!! $trustedHtml !!}
```

### SQL Injection Protection

ใช้ Query Builder หรือ Eloquent เสมอ:

```php
// ✅ Good
User::where('email', $email)->first();
DB::table('users')->where('id', $id)->get();

// ❌ Bad
DB::select("SELECT * FROM users WHERE id = $id");
```

---

## การตั้งค่า NFC

### Requirements

- ✅ HTTPS (หรือ localhost สำหรับ development)
- ✅ Chrome browser บน Android
- ✅ อุปกรณ์ที่รองรับ NFC

### Environment Configuration

```env
NFC_ENABLED=true
NFC_DEFAULT_ACTION=scan_product  # scan_product, scan_payment, scan_coupon
```

### NFC Tag Format

#### สำหรับสินค้า:

**URL Format:**
```
https://yourdomain.com/products/{product-slug}
```

**JSON Format:**
```json
{
  "type": "product",
  "id": 123,
  "slug": "iphone-15-pro"
}
```

#### สำหรับ PromptPay:

```
{
  "type": "payment",
  "method": "promptpay",
  "qr_code": "00020101021129370016A000000677010111011300669XXXXX..."
}
```

### การใช้งาน NFC Scanner

ใน Blade template:

```blade
@push('scripts')
<script src="{{ asset('js/nfc-scanner.js') }}"></script>
<script>
document.getElementById('scanButton').addEventListener('click', async () => {
    await nfcExamples.scanProduct();
});
</script>
@endpush
```

---

## การตั้งค่า Logging

```env
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug  # debug, info, notice, warning, error, critical, alert, emergency
```

### Custom Log Channels

แก้ไข `config/logging.php`:

```php
'channels' => [
    'mlm' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mlm.log'),
        'level' => 'info',
        'days' => 14,
    ],
    'payments' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payments.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

ใช้งาน:

```php
Log::channel('mlm')->info('Commission distributed', ['amount' => 500]);
Log::channel('payments')->error('Payment failed', ['order_id' => 123]);
```

---

## บันทึกการเปลี่ยนแปลง

เมื่อแก้ไข configuration:

```bash
# Clear และ rebuild cache
php artisan config:clear
php artisan config:cache

# Clear route cache
php artisan route:clear
php artisan route:cache

# Clear view cache
php artisan view:clear
php artisan view:cache
```

---

สำหรับข้อมูลเพิ่มเติม ดูที่:
- [Installation Guide](./INSTALLATION_GUIDE.md)
- [API Documentation](./API_DOCUMENTATION.md)
- [Deployment Guide](./DEPLOYMENT.md)
