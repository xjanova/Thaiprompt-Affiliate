# คำแนะนำการติดตั้ง Laravel Sanctum

## ขั้นตอนการติดตั้ง

เนื่องจากโปรเจคนี้ใช้ API สำหรับ mobile app จึงจำเป็นต้องติดตั้ง Laravel Sanctum

### 1. ติดตั้ง Laravel Sanctum

```bash
composer install
```

หรือถ้าต้องการติดตั้ง Sanctum เพิ่มเติม:

```bash
composer require laravel/sanctum
```

### 2. Publish Sanctum Configuration และ Migration

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. รัน Migration

```bash
php artisan migrate
```

### 4. เพิ่ม Sanctum Middleware (ถ้ายังไม่มี)

เปิดไฟล์ `bootstrap/app.php` และเพิ่ม middleware:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

### 5. ตั้งค่า CORS (ถ้าใช้กับ mobile app)

เปิดไฟล์ `config/cors.php` และตั้งค่า:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => ['*'], // สำหรับ development, ใน production ควรระบุ domain ที่แน่นอน

'supports_credentials' => true,
```

### 6. ตรวจสอบการติดตั้ง

ลองเรียก API endpoint:

```bash
curl http://your-domain.com/api/v1/settings
```

ควรได้ response กลับมา

## การใช้งาน API

ดู documentation ใน `MOBILE-APP-API.md` สำหรับรายละเอียดเพิ่มเติม

### ตัวอย่างการ Login

```bash
curl -X POST http://your-domain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

### ตัวอย่างการเรียก Protected Endpoint

```bash
curl http://your-domain.com/api/v1/dashboard/statistics \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Troubleshooting

### Error: Trait "Laravel\Sanctum\HasApiTokens" not found

แก้ไขโดย:
```bash
composer install
```

### Token ไม่ทำงาน

ตรวจสอบ:
1. SANCTUM_STATEFUL_DOMAINS ใน `.env`
2. SESSION_DRIVER ใน `.env` ควรเป็น `cookie` หรือ `database`
3. APP_URL ใน `.env` ต้องตรงกับ domain ที่ใช้งาน

### CORS Error

เพิ่มใน `config/cors.php`:
```php
'allowed_origins' => ['http://localhost:3000'], // เพิ่ม domain ที่ต้องการ
'supports_credentials' => true,
```

## สำหรับ Production

1. ตั้งค่า `SANCTUM_STATEFUL_DOMAINS` ใน `.env`:
```
SANCTUM_STATEFUL_DOMAINS=your-domain.com,api.your-domain.com
```

2. ตั้งค่า `SESSION_DOMAIN` ใน `.env`:
```
SESSION_DOMAIN=.your-domain.com
```

3. ใช้ HTTPS เท่านั้น
