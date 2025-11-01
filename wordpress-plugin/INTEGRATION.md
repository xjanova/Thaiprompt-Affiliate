# Integration Guide - TP-Affiliate License Manager

คู่มือการเชื่อมต่อระหว่าง WordPress Plugin (License Server) กับ Laravel Client (TP-Affiliate Pro)

---

## 🔗 ภาพรวมการเชื่อมต่อ

```
Laravel Client                    WordPress Plugin
(TP-Affiliate Pro)               (License Server)
─────────────────────────────────────────────────

┌──────────────┐                ┌──────────────┐
│ Laravel App  │  REST API      │  WordPress   │
│              │ ──────────────>│   Plugin     │
│ LicenseService│ HTTPS Request │ class-api.php│
│ AddonService │                │              │
└──────────────┘ <──────────────└──────────────┘
                  JSON Response
```

---

## 📂 Repository Structure

### Repository 1: Thaiprompt-Affiliate (Laravel Client)
**URL:** https://github.com/xjanova/Thaiprompt-Affiliate

```
Thaiprompt-Affiliate/
├── app/Services/
│   ├── LicenseService.php    → เรียก API จาก WordPress
│   └── AddonService.php      → เรียก Add-on API
├── config/license.php         → ตั้งค่า API URL
└── app/Console/Commands/
    ├── License*.php           → คำสั่งจัดการ License
    └── Addon*.php             → คำสั่งจัดการ Add-ons
```

### Repository 2: TP-Affiliate (WordPress Plugin)
**URL:** https://github.com/xjanova/TP-Affiliate

```
tp-affiliate-license-manager/
├── api/
│   ├── class-api.php          → รับ request จาก Laravel
│   └── class-addon-api.php    → Add-on API endpoints
├── includes/
│   ├── class-license-manager.php
│   └── class-addon-manager.php
└── tp-affiliate-license-manager.php
```

---

## 🔧 การติดตั้ง

### 1. ติดตั้ง WordPress Plugin

```bash
# Clone repository
git clone https://github.com/xjanova/TP-Affiliate.git

# Copy to WordPress plugins directory
cp -r TP-Affiliate /path/to/wordpress/wp-content/plugins/tp-affiliate-license-manager

# Activate plugin in WordPress Admin
# WP Admin → Plugins → Activate "TP-Affiliate License Manager"
```

### 2. ตั้งค่า Laravel Client

```bash
# Clone Laravel repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# Configure .env
echo "LICENSE_API_URL=https://xman4289.com/wp-json/tp-license/v1" >> .env
echo "LICENSE_DEVELOPER_MODE=false" >> .env
```

---

## 🌐 API Endpoints Reference

### Base URL
```
https://xman4289.com/wp-json/tp-license/v1/
```

### License Endpoints

#### 1. Validate License
```http
POST /validate
Content-Type: application/json

{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "https://example.com",
  "ip": "192.168.1.1",
  "installation_id": "uuid-string"
}
```

**Response:**
```json
{
  "success": true,
  "valid": true,
  "license": {
    "key": "XXXX-XXXX-XXXX-XXXX",
    "status": "active",
    "expires_at": "2026-10-31",
    "customer_email": "user@example.com"
  }
}
```

#### 2. Activate License
```http
POST /activate
```

#### 3. Deactivate License
```http
POST /deactivate
```

### Add-on Endpoints

#### 1. List Available Add-ons
```http
GET /addons
```

#### 2. Validate Add-on License
```http
POST /addons/validate

{
  "license_key": "CORE-LICENSE-KEY",
  "addon_slug": "mlm",
  "addon_license_key": "ADDON-LICENSE-KEY"
}
```

---

## 💻 การใช้งานใน Laravel

### ตรวจสอบ License

```php
use App\Services\LicenseService;

$licenseService = app(LicenseService::class);

// Validate license
$result = $licenseService->validate();

if ($result['valid']) {
    echo "License is active!";
}
```

### เปิดใช้งาน Add-on

```php
use App\Services\AddonService;

$addonService = app(AddonService::class);

// Enable MLM add-on
$result = $addonService->enableAddon('mlm', 'MLM-LICENSE-KEY');

if ($result['success']) {
    echo "MLM Add-on activated!";
}
```

### ผ่าน Artisan Commands

```bash
# License management
php artisan license:activate YOUR-LICENSE-KEY
php artisan license:status
php artisan license:check

# Add-on management
php artisan addon:list
php artisan addon:enable mlm MLM-LICENSE-KEY
```

---

## 🔐 Security Considerations

### 1. HTTPS Only
```php
// config/license.php
'api_url' => env('LICENSE_API_URL', 'https://xman4289.com/wp-json/tp-license/v1'),
```

**ห้าม** ใช้ HTTP เด็ดขาด!

### 2. Domain Binding
License จะผูกกับ domain ที่ activate:
- Production: `https://your-domain.com`
- Staging: ต้อง activate แยก
- Local: ใช้ Developer Mode

### 3. Developer Mode
```env
# .env (Development only!)
LICENSE_DEVELOPER_MODE=true
```

**อย่าเปิดใน Production!**

---

## 🧪 Testing Integration

### 1. Test WordPress API

```bash
curl -X POST https://xman4289.com/wp-json/tp-license/v1/validate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "TEST-KEY",
    "domain": "https://test.com",
    "ip": "127.0.0.1",
    "installation_id": "test-uuid"
  }'
```

### 2. Test Laravel Integration

```bash
# Check license
php artisan license:check

# Clear cache
php artisan cache:clear
php artisan license:check --clear-cache
```

---

## 🔄 Development Workflow

### Scenario 1: พัฒนา WordPress Plugin

```bash
# 1. Clone repository
git clone https://github.com/xjanova/TP-Affiliate.git
cd TP-Affiliate

# 2. Create feature branch
git checkout -b feature/new-endpoint

# 3. Edit files
vim api/class-api.php

# 4. Test in WordPress
cp -r . /path/to/wordpress/wp-content/plugins/tp-affiliate-license-manager/

# 5. Test API endpoint
curl -X POST http://localhost:8080/wp-json/tp-license/v1/your-endpoint

# 6. Commit and push
git add .
git commit -m "feat: add new endpoint"
git push origin feature/new-endpoint
```

### Scenario 2: พัฒนา Laravel Client

```bash
# 1. Clone Laravel repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. Create feature branch
git checkout -b feature/new-license-command

# 3. Edit files
php artisan make:command License/NewCommand

# 4. Test
php artisan license:new-command

# 5. Commit and push
git add .
git commit -m "feat: add new license command"
git push origin feature/new-license-command
```

---

## 📞 Support

- **WordPress Plugin Issues:** https://github.com/xjanova/TP-Affiliate/issues
- **Laravel Client Issues:** https://github.com/xjanova/Thaiprompt-Affiliate/issues
- **Email:** support@xman4289.com
- **Website:** https://xman4289.com

---

**Developer:** Xman Enterprise Co., Ltd.  
**Copyright © 2025 All rights reserved.**
