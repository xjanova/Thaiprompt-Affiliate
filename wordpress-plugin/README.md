# 🔐 TP-Affiliate License Manager (WordPress Plugin)

**ระบบจัดการ License สำหรับ TP-Affiliate Pro**

ระบบจัดการ License แบบสมบูรณ์สำหรับขาย TP-Affiliate Pro พร้อม WooCommerce Integration และระบบ Add-on Management

---

## 📋 ภาพรวม

Plugin นี้เป็น **License Server** สำหรับ TP-Affiliate Pro ที่ทำหน้าที่:

- ✅ จัดการ License (สร้าง, เปิดใช้งาน, ปิดการใช้งาน)
- ✅ เชื่อมต่อกับ WooCommerce (สร้าง License อัตโนมัติหลังการซื้อ)
- ✅ REST API สำหรับ Laravel Client
- ✅ ระบบ Add-on Management (MLM, Payment Gateway, ฯลฯ)
- ✅ Download Management
- ✅ Support System (เตรียมไว้)
- ✅ Analytics และ Reporting

---

## 🏗️ โครงสร้างโปรเจกต์

```
TP-Affiliate-License-Manager/
│
├── tp-affiliate-license-manager/        📁 Main Plugin Directory
│   │
│   ├── tp-affiliate-license-manager.php (Main Plugin File)
│   ├── uninstall.php
│   │
│   ├── includes/                        📁 Core Classes
│   │   ├── class-activator.php         (Plugin Activation)
│   │   ├── class-deactivator.php       (Plugin Deactivation)
│   │   ├── class-license-manager.php   (Core Plugin Class)
│   │   ├── class-loader.php            (Hook Loader)
│   │   ├── class-i18n.php              (Internationalization)
│   │   ├── class-addon-manager.php     (Add-on Management) ⭐
│   │   ├── class-database.php          (Database Handler)
│   │   └── class-license-generator.php (Generate License Keys)
│   │
│   ├── admin/                           📁 Admin Dashboard
│   │   ├── class-admin.php
│   │   ├── class-admin-menu.php
│   │   └── views/
│   │       ├── dashboard.php
│   │       ├── licenses.php
│   │       ├── customers.php
│   │       ├── addons.php              ⭐ Add-on Management
│   │       └── settings.php
│   │
│   ├── api/                             📁 REST API
│   │   ├── class-api.php               (License API)
│   │   └── class-addon-api.php         (Add-on API) ⭐
│   │
│   ├── woocommerce/                     📁 WooCommerce Integration
│   │   ├── class-woocommerce.php
│   │   ├── class-product-type.php      (License Product Type)
│   │   └── class-order-handler.php     (Auto Generate License)
│   │
│   ├── assets/                          📁 Assets
│   │   ├── css/
│   │   └── js/
│   │
│   └── languages/                       📁 Translations
│       └── tp-license-th.po
│
└── README.md                            📄 This File

---

Related Repository:
📦 Thaiprompt-Affiliate (Laravel Client)
   https://github.com/xjanova/Thaiprompt-Affiliate
   ⭐ ติดตั้งบนเว็บลูกค้า, เชื่อมต่อ License API
```

---

## 🔗 ความสัมพันธ์ระหว่าง 2 Repositories

### 1. **TP-Affiliate-License-Manager** (Repository นี้)
- **ติดตั้งที่**: xman4289.com (WordPress + WooCommerce)
- **หน้าที่**: License Server
- **ฟังก์ชัน**:
  - ขาย License ผ่าน WooCommerce
  - API สำหรับตรวจสอบ License
  - จัดการ Add-ons
  - Download Management

### 2. **Thaiprompt-Affiliate** (Laravel Client)
- **ติดตั้งที่**: เว็บลูกค้า (customer-site.com)
- **หน้าที่**: Client Application
- **ฟังก์ชัน**:
  - เชื่อมต่อ API ของ xman4289.com
  - ตรวจสอบ License ก่อนใช้งาน
  - ดาวน์โหลดอัปเดตจาก xman4289.com

**การเชื่อมต่อ:**
```
┌─────────────────────────────────────┐
│  xman4289.com                       │
│  WordPress + WooCommerce            │
│  + TP-License-Manager Plugin       │
│                                     │
│  REST API:                          │
│  /wp-json/tp-license/v1/validate   │
│  /wp-json/tp-license/v1/activate   │
│  /wp-json/tp-license/v1/addons/*   │
└─────────────────────────────────────┘
              ↕ HTTPS
┌─────────────────────────────────────┐
│  customer-site.com                  │
│  Laravel (Thaiprompt-Affiliate)     │
│                                     │
│  LicenseService::validate()         │
│  AddonService::check()              │
└─────────────────────────────────────┘
```

---

## 🚀 การติดตั้ง

### ความต้องการของระบบ

- WordPress 6.0+
- PHP 8.0+
- WooCommerce 7.0+
- MySQL 5.7+ หรือ MariaDB 10.2+

### ขั้นตอนการติดตั้ง

#### 1. Upload Plugin

```bash
# Upload ผ่าน WordPress Admin
1. ไปที่ Plugins → Add New → Upload Plugin
2. เลือกไฟล์ tp-affiliate-license-manager.zip
3. คลิก Install Now
4. คลิก Activate

# หรือ Upload ผ่าน FTP
1. Upload โฟลเดอร์ tp-affiliate-license-manager ไปที่
   /wp-content/plugins/
2. ไปที่ WordPress Admin → Plugins
3. Activate Plugin
```

#### 2. ตั้งค่าเริ่มต้น

```
1. ไปที่ TP License → Settings
2. ตั้งค่า:
   - License Prefix (default: TPAF)
   - License Length
   - Expiry Days
   - Max Activations per License
   - Email Notifications
3. Save Settings
```

#### 3. สร้าง Product ใน WooCommerce

```
1. Products → Add New
2. Product Data: เลือก "License Product"
3. กรอกรายละเอียด:
   - ชื่อสินค้า: TP-Affiliate Pro - Single Site
   - ราคา: ฿9,900
   - License Type: Core
   - License Duration: 365 days
   - Max Activations: 1
4. Publish
```

#### 4. สร้าง Add-on Products

```
1. Products → Add New
2. Product Data: เลือก "License Product"
3. กรอกรายละเอียด:
   - ชื่อสินค้า: MLM Add-on
   - ราคา: ฿4,900
   - License Type: mlm (Add-on)
   - Requires Core: Yes
4. Publish

ทำซ้ำสำหรับ Add-ons อื่นๆ
```

---

## 📡 REST API Documentation

### Base URL

```
https://xman4289.com/wp-json/tp-license/v1
```

### Authentication

ไม่ต้องมี Authentication (ตรวจสอบผ่าน License Key)

---

### 1. Validate License

**Endpoint:** `POST /validate`

**Request:**
```json
{
  "license_key": "TPAF-XXXX-XXXX-XXXX-XXXX",
  "domain": "customer-site.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxx-xxxx"
}
```

**Response (Success):**
```json
{
  "success": true,
  "license": {
    "key": "TPAF-XXXX-XXXX-XXXX-XXXX",
    "status": "active",
    "customer_email": "customer@example.com",
    "domain": "customer-site.com",
    "expires_at": "2026-01-01 00:00:00",
    "created_at": "2025-01-01 00:00:00"
  }
}
```

---

### 2. Activate License

**Endpoint:** `POST /activate`

**Request:**
```json
{
  "license_key": "TPAF-XXXX-XXXX-XXXX-XXXX",
  "domain": "customer-site.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxx-xxxx",
  "php_version": "8.2.0",
  "laravel_version": "11.0.0"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "License activated successfully",
  "activation": {
    "domain": "customer-site.com",
    "activated_at": "2025-10-31 12:00:00"
  }
}
```

---

### 3. Get Available Add-ons

**Endpoint:** `GET /addons`

**Response:**
```json
{
  "success": true,
  "addons": {
    "mlm": {
      "name": "MLM Add-on",
      "slug": "tp-affiliate-mlm",
      "description": "ระบบ Multi-Level Marketing",
      "version": "1.0.0",
      "price": 4900,
      "requires_core": "1.0.0"
    },
    "payment-gateway": {
      "name": "Payment Gateway Add-on",
      ...
    }
  }
}
```

---

### 4. Get Activated Add-ons

**Endpoint:** `POST /addons/activated`

**Request:**
```json
{
  "license_key": "TPAF-XXXX-XXXX-XXXX-XXXX"
}
```

**Response:**
```json
{
  "success": true,
  "addons": [
    {
      "slug": "mlm",
      "name": "MLM Add-on",
      "version": "1.0.0",
      "license_key": "MLM-XXXX-XXXX-XXXX-XXXX",
      "activated_at": "2025-10-31 12:00:00",
      "expires_at": "2026-10-31 12:00:00"
    }
  ]
}
```

---

### 5. Validate Add-on

**Endpoint:** `POST /addons/validate`

**Request:**
```json
{
  "license_key": "TPAF-XXXX-XXXX-XXXX-XXXX",
  "addon_slug": "mlm",
  "addon_license_key": "MLM-XXXX-XXXX-XXXX-XXXX"
}
```

**Response:**
```json
{
  "success": true,
  "addon": {
    "slug": "mlm",
    "name": "MLM Add-on",
    "version": "1.0.0",
    "license_key": "MLM-XXXX-XXXX-XXXX-XXXX",
    "status": "active",
    "expires_at": "2026-10-31 12:00:00"
  }
}
```

---

## 🔧 การใช้งาน

### สำหรับ Admin (xman4289.com)

#### 1. ดู Dashboard

```
1. เข้า WordPress Admin
2. ไปที่ TP License → Dashboard
3. ดูสถิติ:
   - Total Licenses
   - Active Licenses
   - Revenue
   - Recent Activations
```

#### 2. จัดการ Licenses

```
1. TP License → Licenses
2. ดูรายการ License ทั้งหมด
3. Actions:
   - View Details
   - Deactivate
   - Extend Expiry
   - Change Domain
```

#### 3. จัดการ Add-ons

```
1. TP License → Add-ons
2. ดูรายการ Add-ons
3. เพิ่ม/แก้ไข Add-ons
4. ดูสถิติการขาย Add-ons
```

---

### สำหรับ Developer (Laravel Client)

**ดูเอกสารฉบับเต็มที่:**
📖 [Thaiprompt-Affiliate Repository](https://github.com/xjanova/Thaiprompt-Affiliate)

---

## 🤖 วิธีบอก Claude ให้ดู Repository อื่น

เมื่อคุณทำงานในโปรเจกต์ Laravel Client (Thaiprompt-Affiliate) และต้องการให้ Claude ดูข้อมูลจาก WordPress Plugin นี้:

### วิธีที่ 1: ใช้ URL

```
ฉันกำลังทำงานใน Laravel Client
ให้ดูข้อมูล License API จาก WordPress Plugin ที่:
https://github.com/xjanova/TP-Affiliate-License-Manager
โดยเฉพาะไฟล์ api/class-api.php และ api/class-addon-api.php
```

### วิธีที่ 2: Clone ทั้ง 2 Repos ในเครื่องเดียวกัน

```bash
# Clone ทั้ง 2 repos ไว้ใน parent directory เดียวกัน
cd ~/projects

git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
git clone https://github.com/xjanova/TP-Affiliate-License-Manager.git

# ตอนใช้ Claude Code บอกว่า:
"ฉันมี 2 repos ใน ~/projects:
- Thaiprompt-Affiliate (Laravel Client)
- TP-Affiliate-License-Manager (WordPress Plugin)

ให้อ่านไฟล์จาก ../TP-Affiliate-License-Manager/api/class-api.php"
```

### วิธีที่ 3: Copy API Spec

```
คัดลอก API Documentation จาก README.md ของ WordPress Plugin
แล้วบอก Claude ว่า:

"นี่คือ API spec ของ License Server:
[paste API documentation here]

ให้สร้าง LicenseService ใน Laravel ที่เรียกใช้ API นี้"
```

### วิธีที่ 4: ใช้ .claude_code/context

สร้างไฟล์ใน Laravel repo:

```bash
# Thaiprompt-Affiliate/.claude_code/context/license-api.md
```

เนื้อหา:
```markdown
# License API Reference

Base URL: https://xman4289.com/wp-json/tp-license/v1

## Endpoints

### POST /validate
...

(คัดลอกจาก README ของ WordPress Plugin)
```

แล้วบอก Claude:
```
"ดู API spec ใน .claude_code/context/license-api.md"
```

---

## 📌 Add-on Management System

### โครงสร้าง Add-on

```
Core License (TP-Affiliate Pro)
    │
    ├─ Add-on 1: MLM (license_type: 'mlm')
    │  └─ parent_license_id → Core License ID
    │
    ├─ Add-on 2: Payment Gateway (license_type: 'payment-gateway')
    │  └─ parent_license_id → Core License ID
    │
    └─ Add-on 3: Analytics (license_type: 'analytics')
       └─ parent_license_id → Core License ID
```

### กฎการใช้งาน

1. **ต้องมี Core License ก่อน** - ไม่สามารถซื้อ Add-on เพียงอย่างเดียว
2. **License แยกกัน** - แต่ละ Add-on มี License Key ของตัวเอง
3. **Domain เดียวกัน** - Add-on ต้องใช้งานบน Domain เดียวกับ Core
4. **หมดอายุแยก** - Core และ Add-on หมดอายุแยกกัน

### เพิ่ม Add-on ใหม่

แก้ไขไฟล์: `includes/class-addon-manager.php`

```php
private static $available_addons = array(
    'your-addon-slug' => array(
        'name' => 'Your Add-on Name',
        'slug' => 'tp-affiliate-your-addon',
        'description' => 'คำอธิบาย',
        'version' => '1.0.0',
        'price' => 2900,
        'requires_core' => '1.0.0',
    ),
);
```

---

## 🔐 Security

### การป้องกัน

- ✅ ตรวจสอบ Domain Binding
- ✅ ตรวจสอบ IP Address
- ✅ Installation ID (UUID unique)
- ✅ Remote Verification ทุก 7 วัน
- ✅ Rate Limiting (WordPress built-in)
- ✅ SQL Injection Protection (WordPress wpdb)
- ✅ XSS Protection (sanitize inputs)

### Best Practices

1. **ใช้ HTTPS** - API ต้องใช้ HTTPS เท่านั้น
2. **Rate Limiting** - จำกัดจำนวนครั้งการเรียก API
3. **Logging** - บันทึก activation attempts ทั้งหมด
4. **Email Alerts** - แจ้งเตือนเมื่อมีการ activate ใหม่

---

## 📞 Support

**Developed by:** Xman Enterprise Co., Ltd.
**Website:** https://xman4289.com
**Email:** support@xman4289.com

---

## 📄 License

Copyright © 2025 Xman Enterprise Co., Ltd.
All rights reserved.

---

**เวอร์ชั่น:** 1.0.0
**อัปเดตล่าสุด:** 2025-10-31
