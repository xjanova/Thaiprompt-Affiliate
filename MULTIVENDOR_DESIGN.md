# Multi-Vendor Marketplace System Design
**Version:** 1.0.0
**Created:** 2025-11-03
**Project:** Thai Prompt Affiliate Platform

---

## 📋 สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [Database Schema Design](#database-schema-design)
3. [Package System](#package-system)
4. [Feature Management](#feature-management)
5. [UI/UX Dashboard Design](#uiux-dashboard-design)
6. [API Design](#api-design)
7. [Implementation Plan](#implementation-plan)

---

## 🎯 ภาพรวมระบบ

### วัตถุประสงค์
พัฒนาระบบ Multi-vendor Marketplace แบบครบวงจร ที่แยกระบบร้านค้าอย่างชัดเจนระหว่าง **Seller** และ **Admin** พร้อมระบบจัดการแพ็คเกจและฟีเจอร์แบบยืดหยุ่น

### คุณสมบัติหลัก

#### 🏪 **Seller (ผู้ขาย)**
- เห็นเฉพาะสินค้าและคำสั่งซื้อของตัวเอง
- จัดการร้านค้าแบบครบวงจร
- แต่งร้าน อัพโหลดโลโก้ และปรับแต่ง Branding
- เครื่องมือการตลาด (Marketing Tools) แบบซื้อเพิ่ม
- ระบบ AI Bot สำหรับร้านค้า
- ส่งสินค้าขออนุมัติไปแสดงหน้าหลัก (Public Product)

#### 👑 **Admin (ผู้ดูแลระบบ)**
- เห็นและจัดการร้านค้าทั้งหมด
- อนุมัติสินค้าที่ส่งมาแสดงหน้าหลัก
- ตั้งค่าแพ็คเกจและข้อจำกัดต่างๆ
- จัดการฟีเจอร์และเครื่องมือการตลาด
- ระบบรายงานและ Analytics แบบ Global

#### 📦 **Package System**
- **Free** - ร้านฟรี (จำกัดสินค้า, ฟีเจอร์พื้นฐาน)
- **Basic** - แพ็คเกจพื้นฐาน (สินค้าจำกัด + ฟีเจอร์เพิ่ม)
- **Premium** - แพ็คเกจขั้นสูง (สินค้าไม่จำกัด + ฟีเจอร์ครบ)
- **Enterprise** - แพ็คเกจองค์กร (ฟีเจอร์ครบถ้วน + Custom)

---

## 💾 Database Schema Design

### ตารางใหม่ที่ต้องสร้าง

#### 1. **vendor_stores** - ข้อมูลร้านค้า
```sql
CREATE TABLE vendor_stores (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,                    -- เจ้าของร้าน (seller)
    package_id BIGINT UNSIGNED DEFAULT NULL,             -- แพ็คเกจปัจจุบัน

    -- Store Information
    store_name VARCHAR(255) NOT NULL,
    store_slug VARCHAR(255) UNIQUE NOT NULL,
    store_description TEXT,
    store_logo VARCHAR(500),
    store_banner VARCHAR(500),
    store_domain VARCHAR(255),                           -- Custom domain (optional)

    -- Contact & Address
    store_email VARCHAR(255),
    store_phone VARCHAR(50),
    store_address TEXT,
    store_city VARCHAR(100),
    store_state VARCHAR(100),
    store_postal_code VARCHAR(20),
    store_country VARCHAR(100) DEFAULT 'Thailand',

    -- Business Information
    business_type ENUM('individual', 'company') DEFAULT 'individual',
    tax_id VARCHAR(50),
    company_name VARCHAR(255),

    -- Branding
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    secondary_color VARCHAR(7) DEFAULT '#10B981',
    store_theme VARCHAR(50) DEFAULT 'default',           -- Theme template
    custom_css TEXT,                                     -- Custom CSS

    -- Social Media
    facebook_url VARCHAR(500),
    line_oa_id VARCHAR(255),
    instagram_url VARCHAR(500),
    twitter_url VARCHAR(500),
    tiktok_url VARCHAR(500),

    -- Settings
    commission_rate DECIMAL(5,2) DEFAULT 10.00,          -- % ค่าคอมมิชชั่นร้านนี้
    minimum_order_amount DECIMAL(10,2) DEFAULT 0,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    free_shipping_threshold DECIMAL(10,2),
    enable_cod BOOLEAN DEFAULT false,                    -- Cash on Delivery
    enable_reviews BOOLEAN DEFAULT true,
    auto_approve_orders BOOLEAN DEFAULT false,

    -- Analytics
    total_products INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_sales DECIMAL(15,2) DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    rating_average DECIMAL(3,2) DEFAULT 0,
    rating_count INT DEFAULT 0,

    -- Status
    is_active BOOLEAN DEFAULT true,
    is_verified BOOLEAN DEFAULT false,
    verified_at TIMESTAMP NULL,
    status ENUM('pending', 'active', 'suspended', 'closed') DEFAULT 'pending',
    suspension_reason TEXT,

    -- Subscription
    subscription_started_at TIMESTAMP NULL,
    subscription_expires_at TIMESTAMP NULL,
    subscription_status ENUM('active', 'expired', 'cancelled', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES vendor_packages(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_store_slug (store_slug),
    INDEX idx_status (status, is_active),
    INDEX idx_subscription (subscription_status, subscription_expires_at)
);
```

#### 2. **vendor_packages** - แพ็คเกจร้านค้า
```sql
CREATE TABLE vendor_packages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Package Information
    package_name VARCHAR(100) NOT NULL,                  -- Free, Basic, Premium, Enterprise
    package_slug VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,                  -- ชื่อแสดง
    description TEXT,
    features JSON,                                       -- รายการฟีเจอร์

    -- Pricing
    price DECIMAL(10,2) DEFAULT 0,                       -- ราคาต่อเดือน
    yearly_price DECIMAL(10,2),                          -- ราคาต่อปี
    setup_fee DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'THB',

    -- Limitations
    max_products INT,                                    -- จำนวนสินค้าสูงสุด (NULL = unlimited)
    max_images_per_product INT DEFAULT 10,
    max_categories INT,
    max_storage_mb INT,                                  -- Storage quota (MB)
    max_monthly_orders INT,                              -- จำกัดออเดอร์ต่อเดือน

    -- Commission
    commission_rate DECIMAL(5,2) DEFAULT 10.00,          -- % ค่าคอมมิชชั่นแพ็คเกจนี้

    -- Features Flags
    allow_custom_domain BOOLEAN DEFAULT false,
    allow_custom_theme BOOLEAN DEFAULT false,
    allow_api_access BOOLEAN DEFAULT false,
    allow_export_data BOOLEAN DEFAULT false,
    allow_advanced_analytics BOOLEAN DEFAULT false,
    allow_bulk_operations BOOLEAN DEFAULT false,
    allow_ai_bot BOOLEAN DEFAULT false,
    allow_marketing_tools BOOLEAN DEFAULT false,
    priority_support BOOLEAN DEFAULT false,

    -- Trial Settings
    trial_days INT DEFAULT 0,                            -- จำนวนวันทดลองใช้

    -- Display
    badge VARCHAR(50),                                   -- "Popular", "Best Value", etc.
    badge_color VARCHAR(7),
    sort_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    is_default BOOLEAN DEFAULT false,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_slug (package_slug),
    INDEX idx_status (is_active, sort_order)
);
```

#### 3. **vendor_package_features** - ฟีเจอร์พิเศษที่ซื้อเพิ่ม
```sql
CREATE TABLE vendor_package_features (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Feature Information
    feature_name VARCHAR(100) NOT NULL,
    feature_slug VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),                                   -- Icon class

    -- Pricing
    feature_type ENUM('one_time', 'recurring') DEFAULT 'recurring',
    price DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',

    -- Category
    category VARCHAR(100),                               -- marketing, analytics, ai, etc.

    -- Requirements
    required_package_level INT DEFAULT 0,                -- 0=Free, 1=Basic, 2=Premium, 3=Enterprise

    -- Settings
    settings_schema JSON,                                -- Configuration schema

    -- Status
    is_active BOOLEAN DEFAULT true,
    sort_order INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (feature_slug),
    INDEX idx_category (category)
);
```

#### 4. **vendor_subscriptions** - ประวัติการสมัครแพ็คเกจ
```sql
CREATE TABLE vendor_subscriptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,

    -- Subscription Details
    subscription_type ENUM('trial', 'monthly', 'yearly') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'THB',

    -- Period
    started_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP NULL,

    -- Payment
    payment_method VARCHAR(50),
    payment_transaction_id VARCHAR(255),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,

    -- Renewal
    auto_renew BOOLEAN DEFAULT true,
    next_billing_date DATE,

    -- Status
    status ENUM('active', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
    cancellation_reason TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES vendor_packages(id) ON DELETE RESTRICT,
    INDEX idx_store_package (store_id, package_id),
    INDEX idx_expires_at (expires_at, status)
);
```

#### 5. **vendor_features_usage** - ฟีเจอร์ที่ร้านค้าเปิดใช้งาน
```sql
CREATE TABLE vendor_features_usage (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,

    -- Activation
    activated_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,                           -- NULL = lifetime

    -- Settings
    feature_settings JSON,                               -- Configuration values

    -- Usage Stats
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,

    -- Status
    is_active BOOLEAN DEFAULT true,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES vendor_package_features(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_feature (store_id, feature_id),
    INDEX idx_store_active (store_id, is_active)
);
```

#### 6. **vendor_public_products** - สินค้าที่ส่งขออนุมัติไปหน้าหลัก
```sql
CREATE TABLE vendor_public_products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    -- Request Details
    requested_by BIGINT UNSIGNED NOT NULL,               -- User who requested
    request_note TEXT,

    -- Review
    reviewed_by BIGINT UNSIGNED NULL,                    -- Admin who reviewed
    review_note TEXT,
    reviewed_at TIMESTAMP NULL,

    -- Status
    status ENUM('pending', 'approved', 'rejected', 'removed') DEFAULT 'pending',
    rejection_reason TEXT,

    -- Display Settings (if approved)
    is_featured BOOLEAN DEFAULT false,
    display_order INT DEFAULT 0,
    featured_until TIMESTAMP NULL,

    -- Stats
    views_count INT DEFAULT 0,
    clicks_count INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_product (product_id),              -- ป้องกันส่งซ้ำ
    INDEX idx_status (status, created_at),
    INDEX idx_store (store_id)
);
```

#### 7. **vendor_marketing_campaigns** - แคมเปญการตลาด
```sql
CREATE TABLE vendor_marketing_campaigns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,

    -- Campaign Details
    campaign_name VARCHAR(255) NOT NULL,
    campaign_type ENUM('email', 'line_broadcast', 'discount', 'promotion') NOT NULL,
    description TEXT,

    -- Target Audience
    target_type ENUM('all', 'customers', 'subscribers', 'segment') DEFAULT 'all',
    target_segment JSON,                                 -- Segmentation criteria

    -- Content
    content JSON,                                        -- Campaign content/settings

    -- Schedule
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,

    -- Budget (if applicable)
    budget DECIMAL(10,2),
    spent DECIMAL(10,2) DEFAULT 0,

    -- Stats
    sent_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    conversion_count INT DEFAULT 0,
    revenue_generated DECIMAL(15,2) DEFAULT 0,

    -- Status
    status ENUM('draft', 'scheduled', 'running', 'completed', 'cancelled') DEFAULT 'draft',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
    INDEX idx_store_status (store_id, status)
);
```

#### 8. **vendor_analytics** - สถิติและ Analytics
```sql
CREATE TABLE vendor_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,

    -- Date
    date DATE NOT NULL,

    -- Traffic
    page_views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    avg_session_duration INT DEFAULT 0,                  -- seconds

    -- Sales
    orders_count INT DEFAULT 0,
    total_sales DECIMAL(15,2) DEFAULT 0,
    avg_order_value DECIMAL(10,2) DEFAULT 0,

    -- Products
    products_viewed INT DEFAULT 0,
    products_added_to_cart INT DEFAULT 0,

    -- Conversion
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    cart_abandonment_rate DECIMAL(5,2) DEFAULT 0,

    -- Revenue
    gross_revenue DECIMAL(15,2) DEFAULT 0,
    net_revenue DECIMAL(15,2) DEFAULT 0,
    commission_paid DECIMAL(15,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_date (store_id, date),
    INDEX idx_date (date)
);
```

### ตารางที่ต้องอัพเดต

#### **products** - เพิ่มฟิลด์
```sql
ALTER TABLE products
ADD COLUMN store_id BIGINT UNSIGNED AFTER seller_id,
ADD COLUMN is_public_approved BOOLEAN DEFAULT false AFTER is_featured,
ADD COLUMN public_approved_at TIMESTAMP NULL AFTER is_public_approved,
ADD COLUMN public_approved_by BIGINT UNSIGNED NULL AFTER public_approved_at,
ADD FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE CASCADE,
ADD FOREIGN KEY (public_approved_by) REFERENCES users(id) ON DELETE SET NULL,
ADD INDEX idx_store_active (store_id, is_active),
ADD INDEX idx_public (is_public_approved, is_active);
```

#### **orders** - เพิ่มฟิลด์
```sql
ALTER TABLE orders
ADD COLUMN store_id BIGINT UNSIGNED AFTER user_id,
ADD COLUMN store_commission DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
ADD COLUMN store_earning DECIMAL(10,2) DEFAULT 0 AFTER store_commission,
ADD FOREIGN KEY (store_id) REFERENCES vendor_stores(id) ON DELETE SET NULL,
ADD INDEX idx_store_status (store_id, status);
```

---

## 📦 Package System Design

### แพ็คเกจที่แนะนำ

#### 🆓 **Free Plan**
```json
{
  "name": "Free",
  "price": 0,
  "features": {
    "max_products": 10,
    "max_images_per_product": 5,
    "max_storage_mb": 100,
    "max_monthly_orders": 50,
    "commission_rate": 15.0,
    "allow_custom_domain": false,
    "allow_custom_theme": false,
    "allow_ai_bot": false,
    "allow_marketing_tools": false
  },
  "description": "เหมาะสำหรับร้านค้าเริ่มต้น ทดลองใช้งานฟรี"
}
```

#### ⭐ **Basic Plan** (฿999/เดือน)
```json
{
  "name": "Basic",
  "price": 999,
  "yearly_price": 9999,
  "features": {
    "max_products": 100,
    "max_images_per_product": 10,
    "max_storage_mb": 1000,
    "max_monthly_orders": 500,
    "commission_rate": 10.0,
    "allow_custom_domain": false,
    "allow_custom_theme": true,
    "allow_api_access": false,
    "allow_export_data": true,
    "allow_advanced_analytics": false,
    "allow_ai_bot": false,
    "allow_marketing_tools": true
  },
  "description": "เหมาะสำหรับร้านค้าขนาดกลาง มีฟีเจอร์ครบ"
}
```

#### 💎 **Premium Plan** (฿2,999/เดือน)
```json
{
  "name": "Premium",
  "price": 2999,
  "yearly_price": 29999,
  "features": {
    "max_products": null,
    "max_images_per_product": 20,
    "max_storage_mb": 5000,
    "max_monthly_orders": null,
    "commission_rate": 7.0,
    "allow_custom_domain": true,
    "allow_custom_theme": true,
    "allow_api_access": true,
    "allow_export_data": true,
    "allow_advanced_analytics": true,
    "allow_bulk_operations": true,
    "allow_ai_bot": true,
    "allow_marketing_tools": true,
    "priority_support": true
  },
  "description": "เหมาะสำหรับร้านค้าขนาดใหญ่ ไม่จำกัดสินค้า"
}
```

#### 🏢 **Enterprise Plan** (Custom Price)
```json
{
  "name": "Enterprise",
  "price": "custom",
  "features": {
    "max_products": null,
    "max_images_per_product": null,
    "max_storage_mb": null,
    "max_monthly_orders": null,
    "commission_rate": 5.0,
    "allow_custom_domain": true,
    "allow_custom_theme": true,
    "allow_api_access": true,
    "allow_export_data": true,
    "allow_advanced_analytics": true,
    "allow_bulk_operations": true,
    "allow_ai_bot": true,
    "allow_marketing_tools": true,
    "priority_support": true,
    "dedicated_account_manager": true
  },
  "description": "เหมาะสำหรับองค์กร ปรับแต่งได้ตามต้องการ"
}
```

### ฟีเจอร์พิเศษที่ซื้อเพิ่ม (Add-ons)

```json
{
  "marketing_addons": [
    {
      "name": "AI ChatBot",
      "price": 499,
      "billing": "monthly",
      "description": "บอท AI ตอบลูกค้าอัตโนมัติ 24/7"
    },
    {
      "name": "LINE Broadcast",
      "price": 299,
      "billing": "monthly",
      "description": "ส่งข่าวสารการตลาดผ่าน LINE OA"
    },
    {
      "name": "Email Marketing",
      "price": 199,
      "billing": "monthly",
      "description": "ส่งอีเมลการตลาดถึงลูกค้า"
    },
    {
      "name": "MLM System",
      "price": 1999,
      "billing": "monthly",
      "description": "ระบบ Multi-level Marketing ส่วนตัว"
    }
  ],
  "analytics_addons": [
    {
      "name": "Advanced Analytics",
      "price": 399,
      "billing": "monthly",
      "description": "รายงานขั้นสูงและ Business Intelligence"
    },
    {
      "name": "Heatmap & Behavior",
      "price": 299,
      "billing": "monthly",
      "description": "วิเคราะห์พฤติกรรมลูกค้าแบบ Real-time"
    }
  ],
  "storage_addons": [
    {
      "name": "Extra Storage 5GB",
      "price": 99,
      "billing": "monthly",
      "description": "พื้นที่จัดเก็บเพิ่มเติม 5GB"
    }
  ]
}
```

---

## 🎨 UI/UX Dashboard Design

### Seller Dashboard Layout

#### **Main Dashboard Components**

```
┌─────────────────────────────────────────────────────────────┐
│ [LOGO]  ชื่อร้านค้า          🔔  👤 Profile  [Package Badge]│
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  📊 Overview Statistics (Today/This Month)                   │
│  ┌─────────┬─────────┬─────────┬─────────┬─────────┐       │
│  │ ยอดขาย  │ ออเดอร์ │ ผู้เยี่ยม│ สินค้า  │ รีวิว   │       │
│  │ ฿25,000 │   45    │  1,234  │   87    │ 4.8⭐  │       │
│  └─────────┴─────────┴─────────┴─────────┴─────────┘       │
│                                                               │
│  📈 Sales Chart (Last 30 Days)                              │
│  ┌────────────────────────────────────────────────┐         │
│  │                  📊 Chart.js                    │         │
│  └────────────────────────────────────────────────┘         │
│                                                               │
│  🔥 Quick Actions                                            │
│  ┌──────────┬──────────┬──────────┬──────────┐             │
│  │ เพิ่มสินค้า│ ดูออเดอร์│ จัดการร้าน│ รายงาน  │             │
│  └──────────┴──────────┴──────────┴──────────┘             │
│                                                               │
│  📋 Recent Orders                                            │
│  ┌──────────────────────────────────────────────┐           │
│  │ #12345 | ฿1,200 | รอดำเนินการ | 2 ชม. ที่แล้ว│           │
│  │ #12344 | ฿850   | จัดส่งแล้ว   | 5 ชม. ที่แล้ว│           │
│  └──────────────────────────────────────────────┘           │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

#### **Sidebar Navigation**

```
🏠 Dashboard
┣━ 📊 ภาพรวม
┣━ 📈 Analytics
┗━ 🎯 เป้าหมาย

🛍️ สินค้า
┣━ 📦 สินค้าทั้งหมด
┣━ ➕ เพิ่มสินค้า
┣━ 🗂️ หมวดหมู่
┣━ 🏷️ แท็ก
┗━ 🌍 ส่งไปหน้าหลัก (Public)

📋 คำสั่งซื้อ
┣━ 📝 คำสั่งซื้อทั้งหมด
┣━ ⏳ รอดำเนินการ
┣━ 📦 กำลังเตรียมสินค้า
┣━ 🚚 จัดส่งแล้ว
┗━ ✅ สำเร็จ

💰 การเงิน
┣━ 💵 กระเป๋าเงิน
┣━ 📊 รายได้
┣━ 💳 รายการถอนเงิน
┗━ 🧾 ใบแจ้งหนี้

👥 ลูกค้า
┣━ 👤 ลูกค้าทั้งหมด
┣━ ⭐ รีวิว
┗━ 💬 แชท

📢 การตลาด
┣━ 🎯 แคมเปญ
┣━ 🤖 AI Bot (🔒)
┣━ 📧 Email Marketing (🔒)
┣━ 💬 LINE Broadcast (🔒)
┗━ 🎁 คูปอง & โปรโมชั่น

🎨 จัดการร้าน
┣━ ⚙️ ตั้งค่าทั่วไป
┣━ 🎨 แต่งร้าน (Logo, Theme)
┣━ 🚚 การจัดส่ง
┣━ 💳 ช่องทางชำระเงิน
┗━ 🔗 เชื่อมต่อ Social Media

📦 แพ็คเกจ
┣━ 🎁 แพ็คเกจปัจจุบัน
┣━ 🔼 อัพเกรด
┗━ 🛒 ฟีเจอร์เสริม

⚙️ ตั้งค่า
┣━ 👤 โปรไฟล์ร้าน
┣━ 🔔 การแจ้งเตือน
┣━ 🔐 ความปลอดภัย
┗━ ❓ ช่วยเหลือ
```

### Design System

#### **Color Palette**

```css
/* Primary Colors */
--primary-50: #eff6ff;
--primary-500: #3b82f6;
--primary-600: #2563eb;
--primary-700: #1d4ed8;

/* Secondary Colors */
--secondary-500: #10b981;
--secondary-600: #059669;

/* Status Colors */
--success: #10b981;
--warning: #f59e0b;
--error: #ef4444;
--info: #3b82f6;

/* Package Colors */
--free: #94a3b8;
--basic: #3b82f6;
--premium: #8b5cf6;
--enterprise: #eab308;
```

#### **Typography**

```css
/* Thai Font */
font-family: 'Noto Sans Thai', 'Inter', sans-serif;

/* Headings */
.heading-1 { font-size: 2.5rem; font-weight: 700; }
.heading-2 { font-size: 2rem; font-weight: 700; }
.heading-3 { font-size: 1.5rem; font-weight: 600; }
.heading-4 { font-size: 1.25rem; font-weight: 600; }

/* Body */
.body-large { font-size: 1.125rem; }
.body-normal { font-size: 1rem; }
.body-small { font-size: 0.875rem; }
```

#### **Components**

```html
<!-- Stat Card -->
<div class="stat-card bg-white rounded-xl shadow-sm p-6">
  <div class="flex items-center justify-between">
    <div>
      <p class="text-gray-600 text-sm">ยอดขายวันนี้</p>
      <h3 class="text-3xl font-bold text-gray-900">฿25,000</h3>
      <p class="text-green-600 text-sm mt-1">+12.5% จากเมื่อวาน</p>
    </div>
    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
      <svg>...</svg>
    </div>
  </div>
</div>

<!-- Package Badge -->
<span class="package-badge premium">
  💎 Premium
</span>

<!-- Feature Lock -->
<button class="feature-locked" disabled>
  🔒 AI Bot
  <span class="tooltip">อัพเกรดเป็น Premium เพื่อใช้งาน</span>
</button>
```

### Mobile Responsive Design

```
📱 Mobile (< 768px)
  - Stack all cards vertically
  - Bottom navigation bar
  - Collapsible sidebar

💻 Tablet (768px - 1024px)
  - 2-column grid for stats
  - Sidebar always visible (narrow)

🖥️ Desktop (> 1024px)
  - Full sidebar with labels
  - 4-column grid for stats
  - Multi-column layouts
```

---

## 🔌 API Design

### Endpoint Structure

```
/api/v1/vendor/
  ├── stores/
  │   ├── GET    /                  List all stores (admin only)
  │   ├── POST   /                  Create new store
  │   ├── GET    /{id}              Get store details
  │   ├── PUT    /{id}              Update store
  │   ├── DELETE /{id}              Delete store
  │   ├── GET    /{id}/analytics    Get store analytics
  │   └── POST   /{id}/verify       Verify store (admin only)
  │
  ├── packages/
  │   ├── GET    /                  List all packages
  │   ├── GET    /{id}              Get package details
  │   ├── POST   /subscribe         Subscribe to package
  │   └── POST   /cancel            Cancel subscription
  │
  ├── features/
  │   ├── GET    /                  List available features
  │   ├── POST   /purchase          Purchase feature
  │   └── GET    /active            Get active features
  │
  ├── products/
  │   ├── GET    /                  List store products
  │   ├── POST   /                  Create product
  │   ├── POST   /{id}/public       Request public listing
  │   └── GET    /public            Get public products (admin)
  │
  └── analytics/
      ├── GET    /dashboard         Dashboard stats
      ├── GET    /sales             Sales analytics
      └── GET    /traffic           Traffic analytics
```

---

## 📅 Implementation Plan

### Phase 1: Foundation (Week 1-2)
- [x] ✅ วิเคราะห์และออกแบบ Database Schema
- [ ] 🚧 สร้าง Migration files
- [ ] 🚧 สร้าง Models และ Relationships
- [ ] 🚧 สร้าง Seeders สำหรับ Packages เริ่มต้น
- [ ] 🚧 อัพเดต User Model เพื่อรองรับ Store

### Phase 2: Package System (Week 3)
- [ ] ⏳ สร้าง Package Management System
- [ ] ⏳ สร้าง Feature Limitation Middleware
- [ ] ⏳ สร้าง Subscription Management
- [ ] ⏳ ระบบ Trial Period

### Phase 3: Seller Dashboard UI (Week 4-5)
- [ ] ⏳ ออกแบบ Layout และ Components
- [ ] ⏳ สร้างหน้า Dashboard หลัก
- [ ] ⏳ สร้างหน้าจัดการสินค้า
- [ ] ⏳ สร้างหน้าคำสั่งซื้อ
- [ ] ⏳ สร้างหน้าการเงิน

### Phase 4: Store Customization (Week 6)
- [ ] ⏳ ระบบอัพโหลดโลโก้และแบนเนอร์
- [ ] ⏳ Theme Customization
- [ ] ⏳ Color Picker
- [ ] ⏳ Social Media Integration

### Phase 5: Public Product System (Week 7)
- [ ] ⏳ ระบบขออนุมัติสินค้า
- [ ] ⏳ Admin Approval Interface
- [ ] ⏳ Public Product Display
- [ ] ⏳ Analytics สำหรับ Public Products

### Phase 6: Marketing Tools (Week 8-9)
- [ ] ⏳ Campaign Management
- [ ] ⏳ Email Marketing Integration
- [ ] ⏳ LINE Broadcast System
- [ ] ⏳ Coupon System

### Phase 7: Analytics & Reporting (Week 10)
- [ ] ⏳ Sales Analytics
- [ ] ⏳ Traffic Analytics
- [ ] ⏳ Customer Analytics
- [ ] ⏳ Export Reports

### Phase 8: Testing & Optimization (Week 11-12)
- [ ] ⏳ Unit Tests
- [ ] ⏳ Integration Tests
- [ ] ⏳ Performance Optimization
- [ ] ⏳ Security Audit
- [ ] ⏳ UI/UX Testing

---

## 🚀 Next Steps

1. สร้าง Migration files ตาม Schema ที่ออกแบบ
2. สร้าง Models พร้อม Relationships
3. สร้าง Seeders สำหรับข้อมูลเริ่มต้น
4. เริ่มพัฒนา Seller Dashboard UI
5. Implement Package Limitation System

---

## 📝 Notes

- ออกแบบให้รองรับการขยายระบบในอนาคต (Scalable)
- ใช้ Queue สำหรับงานหนักๆ (Analytics, Email, etc.)
- Cache ข้อมูลที่ใช้บ่อย (Package, Features)
- Implement Rate Limiting ตาม Package
- มี Audit Log สำหรับการเปลี่ยนแปลงสำคัญ
- รองรับ Multi-language (TH/EN)
- Mobile-first Design Approach
- Progressive Web App (PWA) Ready

---

**End of Document**
