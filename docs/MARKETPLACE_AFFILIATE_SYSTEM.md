# Marketplace Affiliate System - Technical Design Document

## 📌 Overview

ระบบ Marketplace Affiliate เป็นระบบที่ช่วยให้ผู้ใช้สามารถแชร์สินค้าจาก Marketplace ชั้นนำ (Lazada, TikTok Shop, Shopee) และได้รับค่าคอมมิชชั่นผ่านระบบ MLM ที่มีอยู่

---

## 🎯 Business Requirements

### 1. แอดมิน (Admin)
- เพิ่ม/แก้ไข/ลบ API Credentials สำหรับแต่ละ Marketplace
- ดูคำแนะนำการขอ API keys จากแต่ละแพลตฟอร์ม
- จัดการการ Sync สินค้าจาก Marketplaces
- ติดตามออเดอร์และค่าคอมมิชชั่น
- ตั้งค่าเปอร์เซ็นต์ค่าคอมมิชชั่นต่อ Marketplace
- ดู Analytics และรายงาน

### 2. ผู้ขาย (Seller)
- เชื่อมต่อบัญชี Marketplace ของตัวเอง (Optional)
- นำสินค้าจาก Marketplace มาลงในร้านตัวเอง
- ติดตามยอดขายและค่าคอมมิชชั่น
- จัดการสต็อกและราคา

### 3. ยูสเซอร์ทั่วไป (User/Affiliate)
- เข้าถึงสินค้าจาก Marketplaces ผ่านระบบ
- สร้าง Affiliate Links สำหรับแชร์
- แชร์สินค้าไปยังช่องทางต่างๆ (Social Media, LINE, etc.)
- ได้รับค่าคอมมิชชั่นเมื่อมีคนซื้อผ่านลิงก์
- ค่าคอมมิชชั่นจะถูกคำนวณผ่านระบบ MLM
- ดูสถิติการแชร์และรายได้

---

## 🗄️ Database Schema Design

### 1. marketplace_platforms

เก็บข้อมูลแพลตฟอร์ม Marketplace ที่รองรับ

```sql
CREATE TABLE marketplace_platforms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                    -- 'Lazada', 'TikTok Shop', 'Shopee'
    slug VARCHAR(50) NOT NULL UNIQUE,              -- 'lazada', 'tiktok', 'shopee'
    logo_url VARCHAR(255),
    api_documentation_url VARCHAR(255),
    is_active BOOLEAN DEFAULT true,

    -- API Configuration
    requires_app_key BOOLEAN DEFAULT true,
    requires_app_secret BOOLEAN DEFAULT true,
    requires_access_token BOOLEAN DEFAULT false,
    requires_shop_id BOOLEAN DEFAULT false,
    additional_fields JSON,                         -- Extra fields needed per platform

    -- Commission Settings
    default_commission_rate DECIMAL(5,2) DEFAULT 0, -- Default commission %
    min_commission_rate DECIMAL(5,2) DEFAULT 0,
    max_commission_rate DECIMAL(5,2) DEFAULT 100,

    -- Features
    supports_product_sync BOOLEAN DEFAULT true,
    supports_order_sync BOOLEAN DEFAULT true,
    supports_real_time_webhook BOOLEAN DEFAULT false,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. marketplace_accounts

เก็บ API Credentials ของแต่ละบัญชี (Admin/Seller level)

```sql
CREATE TABLE marketplace_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,                       -- Admin/Seller who owns this account
    platform_id BIGINT UNSIGNED NOT NULL,

    -- Account Info
    account_name VARCHAR(100) NOT NULL,            -- Friendly name
    shop_id VARCHAR(100),                          -- Shop ID from platform
    shop_name VARCHAR(200),

    -- API Credentials (Encrypted)
    app_key TEXT NOT NULL,                         -- Encrypted
    app_secret TEXT NOT NULL,                      -- Encrypted
    access_token TEXT,                             -- Encrypted (if applicable)
    refresh_token TEXT,                            -- Encrypted (if applicable)
    token_expires_at TIMESTAMP NULL,

    -- Additional credentials (platform-specific)
    additional_credentials JSON,                   -- Store extra fields

    -- Configuration
    commission_rate DECIMAL(5,2) DEFAULT 0,        -- Custom commission rate for this account
    auto_sync_products BOOLEAN DEFAULT false,
    auto_sync_orders BOOLEAN DEFAULT true,
    sync_frequency VARCHAR(20) DEFAULT 'hourly',   -- 'realtime', 'hourly', 'daily'

    -- Status
    status ENUM('active', 'inactive', 'error', 'expired') DEFAULT 'active',
    last_sync_at TIMESTAMP NULL,
    last_error TEXT,

    -- Statistics
    total_products_synced INT DEFAULT 0,
    total_orders_synced INT DEFAULT 0,
    total_sales DECIMAL(15,2) DEFAULT 0,
    total_commission DECIMAL(15,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    INDEX idx_user_platform (user_id, platform_id),
    INDEX idx_status (status)
);
```

### 3. marketplace_products

เก็บสินค้าที่ sync จาก Marketplaces

```sql
CREATE TABLE marketplace_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    platform_id BIGINT UNSIGNED NOT NULL,

    -- Product IDs
    external_product_id VARCHAR(100) NOT NULL,     -- Product ID from marketplace
    external_sku VARCHAR(100),

    -- Product Info
    name VARCHAR(500) NOT NULL,
    description TEXT,
    category VARCHAR(200),
    brand VARCHAR(100),

    -- Pricing
    price DECIMAL(15,2) NOT NULL,
    original_price DECIMAL(15,2),                  -- Compare at price
    currency VARCHAR(10) DEFAULT 'THB',

    -- Inventory
    stock_quantity INT DEFAULT 0,
    is_available BOOLEAN DEFAULT true,

    -- Media
    main_image_url TEXT,
    images JSON,                                    -- Array of image URLs

    -- Affiliate Info
    affiliate_url TEXT,                             -- Direct affiliate link from platform
    commission_rate DECIMAL(5,2),                   -- Product-specific commission rate
    commission_amount DECIMAL(15,2),                -- Fixed commission amount (if applicable)

    -- Product Details
    attributes JSON,                                -- Size, color, etc.
    variants JSON,                                  -- Product variants

    -- SEO
    tags JSON,

    -- Statistics
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,

    -- Status
    sync_status ENUM('synced', 'pending', 'error') DEFAULT 'synced',
    is_active BOOLEAN DEFAULT true,
    last_synced_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id) REFERENCES marketplace_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_external_product (account_id, external_product_id),
    INDEX idx_platform_product (platform_id, external_product_id),
    INDEX idx_active (is_active),
    FULLTEXT INDEX ft_name_description (name, description)
);
```

### 4. marketplace_affiliate_links

เก็บ Affiliate Links ที่ User สร้าง

```sql
CREATE TABLE marketplace_affiliate_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,              -- User who created the link
    product_id BIGINT UNSIGNED NOT NULL,           -- marketplace_products.id
    platform_id BIGINT UNSIGNED NOT NULL,

    -- Link Info
    short_code VARCHAR(20) NOT NULL UNIQUE,        -- Short code for tracking
    original_url TEXT NOT NULL,                     -- Original product URL
    affiliate_url TEXT NOT NULL,                    -- Generated affiliate URL
    tracking_url TEXT,                              -- Our tracking URL

    -- Tracking
    click_count INT DEFAULT 0,
    unique_click_count INT DEFAULT 0,
    conversion_count INT DEFAULT 0,                 -- Number of successful orders

    -- Revenue
    total_sales DECIMAL(15,2) DEFAULT 0,
    total_commission DECIMAL(15,2) DEFAULT 0,

    -- Metadata
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    custom_params JSON,

    -- Status
    is_active BOOLEAN DEFAULT true,
    expires_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES marketplace_products(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    INDEX idx_short_code (short_code),
    INDEX idx_user_active (user_id, is_active)
);
```

### 5. marketplace_orders

เก็บ Orders จาก Marketplaces

```sql
CREATE TABLE marketplace_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    platform_id BIGINT UNSIGNED NOT NULL,
    affiliate_link_id BIGINT UNSIGNED,             -- Link that generated this order
    affiliate_user_id BIGINT UNSIGNED,             -- User who shared the link

    -- Order IDs
    external_order_id VARCHAR(100) NOT NULL,       -- Order ID from marketplace
    order_number VARCHAR(100),

    -- Customer Info (if available)
    customer_name VARCHAR(200),
    customer_email VARCHAR(200),
    customer_phone VARCHAR(50),

    -- Order Details
    subtotal DECIMAL(15,2) NOT NULL,
    shipping_fee DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'THB',

    -- Commission
    commission_rate DECIMAL(5,2),
    commission_amount DECIMAL(15,2) DEFAULT 0,
    platform_fee DECIMAL(15,2) DEFAULT 0,          -- Fee charged by marketplace
    net_commission DECIMAL(15,2) DEFAULT 0,        -- After platform fee

    -- Status
    order_status VARCHAR(50),                       -- pending, processing, shipped, completed, cancelled, refunded
    payment_status VARCHAR(50),                     -- pending, paid, refunded
    fulfillment_status VARCHAR(50),                 -- unfulfilled, fulfilled, partially_fulfilled

    -- Dates
    ordered_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,

    -- Tracking
    tracking_number VARCHAR(100),
    shipping_provider VARCHAR(100),

    -- Commission Status
    commission_status ENUM('pending', 'calculated', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    commission_calculated_at TIMESTAMP NULL,
    commission_approved_at TIMESTAMP NULL,
    commission_paid_at TIMESTAMP NULL,

    -- MLM Integration
    mlm_commission_distributed BOOLEAN DEFAULT false,
    mlm_distribution_id BIGINT UNSIGNED,           -- Link to mlm_commissions if applicable

    -- Metadata
    order_data JSON,                                -- Full order data from platform

    -- Sync
    last_synced_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id) REFERENCES marketplace_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_link_id) REFERENCES marketplace_affiliate_links(id) ON DELETE SET NULL,
    FOREIGN KEY (affiliate_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_external_order (account_id, external_order_id),
    INDEX idx_affiliate_user (affiliate_user_id),
    INDEX idx_commission_status (commission_status),
    INDEX idx_ordered_at (ordered_at)
);
```

### 6. marketplace_order_items

เก็บรายการสินค้าในแต่ละ Order

```sql
CREATE TABLE marketplace_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED,                    -- marketplace_products.id (may be null if not synced)

    -- Product Info
    external_product_id VARCHAR(100),
    external_sku VARCHAR(100),
    product_name VARCHAR(500) NOT NULL,
    product_image_url TEXT,

    -- Variant Info
    variant_name VARCHAR(200),
    variant_sku VARCHAR(100),

    -- Pricing
    unit_price DECIMAL(15,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,

    -- Commission
    commission_rate DECIMAL(5,2),
    commission_amount DECIMAL(15,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES marketplace_products(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);
```

### 7. marketplace_commissions

เก็บค่าคอมมิชชั่นที่คำนวณจาก Marketplace Orders (เชื่อมกับระบบ MLM)

```sql
CREATE TABLE marketplace_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,              -- User receiving commission
    order_id BIGINT UNSIGNED NOT NULL,
    affiliate_link_id BIGINT UNSIGNED,
    platform_id BIGINT UNSIGNED NOT NULL,

    -- Commission Type
    commission_type ENUM('direct', 'mlm_unilevel', 'mlm_binary', 'bonus') DEFAULT 'direct',

    -- MLM Details (if applicable)
    mlm_level INT,                                  -- Unilevel level (1, 2, 3, ...)
    mlm_sponsor_id BIGINT UNSIGNED,                -- Direct sponsor

    -- Amounts
    order_amount DECIMAL(15,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL,
    platform_fee DECIMAL(15,2) DEFAULT 0,
    net_commission DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'THB',

    -- Status
    status ENUM('pending', 'approved', 'rejected', 'paid', 'cancelled') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED,
    rejected_at TIMESTAMP NULL,
    rejected_reason TEXT,
    paid_at TIMESTAMP NULL,

    -- Payment
    wallet_transaction_id BIGINT UNSIGNED,         -- Link to wallet_transactions

    -- Metadata
    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_link_id) REFERENCES marketplace_affiliate_links(id) ON DELETE SET NULL,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (mlm_sponsor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (wallet_transaction_id) REFERENCES wallet_transactions(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_order (order_id),
    INDEX idx_status (status)
);
```

### 8. marketplace_link_clicks

เก็บข้อมูลการคลิก Affiliate Links

```sql
CREATE TABLE marketplace_link_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_link_id BIGINT UNSIGNED NOT NULL,

    -- Visitor Info
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer_url TEXT,

    -- Location (if available)
    country_code VARCHAR(2),
    city VARCHAR(100),

    -- Tracking
    session_id VARCHAR(100),
    is_unique_click BOOLEAN DEFAULT false,         -- First click from this session
    converted BOOLEAN DEFAULT false,                -- Did this click result in order?
    order_id BIGINT UNSIGNED,

    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (affiliate_link_id) REFERENCES marketplace_affiliate_links(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE SET NULL,
    INDEX idx_link (affiliate_link_id),
    INDEX idx_clicked_at (clicked_at)
);
```

### 9. marketplace_sync_logs

เก็บ logs การ sync ข้อมูลจาก Marketplaces

```sql
CREATE TABLE marketplace_sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    platform_id BIGINT UNSIGNED NOT NULL,

    -- Sync Info
    sync_type ENUM('products', 'orders', 'manual') NOT NULL,
    sync_status ENUM('running', 'completed', 'failed', 'partial') DEFAULT 'running',

    -- Results
    items_processed INT DEFAULT 0,
    items_created INT DEFAULT 0,
    items_updated INT DEFAULT 0,
    items_failed INT DEFAULT 0,

    -- Error Handling
    error_message TEXT,
    error_details JSON,

    -- Duration
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    duration_seconds INT,

    -- Triggered By
    triggered_by BIGINT UNSIGNED,                  -- User who triggered (null for auto)

    FOREIGN KEY (account_id) REFERENCES marketplace_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES marketplace_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_account_status (account_id, sync_status),
    INDEX idx_started_at (started_at)
);
```

### 10. marketplace_settings

เก็บการตั้งค่าทั่วไปของระบบ Marketplace Affiliate

```sql
CREATE TABLE marketplace_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',     -- string, int, boolean, json
    description TEXT,
    is_public BOOLEAN DEFAULT false,               -- Can users see this setting?

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (setting_key)
);
```

---

## 🔐 API Credentials Encryption

**ความปลอดภัย**: ข้อมูล API Credentials จะถูกเข้ารหัสก่อนเก็บในฐานข้อมูล

```php
// Using Laravel's built-in encryption
use Illuminate\Support\Facades\Crypt;

// Encrypt
$encrypted = Crypt::encryptString($apiKey);

// Decrypt
$decrypted = Crypt::decryptString($encrypted);
```

---

## 📊 MLM Commission Integration Flow

### การคำนวณค่าคอมมิชชั่น:

1. **Direct Commission** (ผู้แชร์โดยตรง):
   - ได้รับ commission ตาม rate ที่กำหนด
   - เช่น: 5% ของยอดขาย

2. **MLM Unilevel Commission** (ตามสายงาน):
   - Level 1: 2%
   - Level 2: 1.5%
   - Level 3: 1%
   - ... ตามการตั้งค่า MLM Plan

3. **MLM Binary Commission** (ขาซ้าย/ขวา):
   - คำนวณตาม PV จาก marketplace orders
   - ใช้ระบบ pairing ที่มีอยู่

### ตัวอย่าง Workflow:

```
User A แชร์สินค้า → Customer ซื้อผ่านลิงก์ → Order เข้าระบบ
  ↓
1. สร้าง marketplace_order
2. คำนวณ direct commission → User A
3. คำนวณ MLM commission → User B (sponsor), User C (level 2), ...
4. สร้าง marketplace_commissions records
5. เมื่อ approved → โอนเข้า wallet
6. อัพเดท PV ในระบบ MLM (ถ้ามี)
```

---

## 🔄 Product & Order Sync Strategy

### Product Sync:
- **Initial Sync**: ดึงสินค้าทั้งหมดครั้งแรก
- **Incremental Sync**: อัพเดทเฉพาะที่เปลี่ยนแปลง
- **Scheduled Sync**: ทำงานทุกชั่วโมง (configurable)
- **Manual Sync**: แอดมินกดเองได้ตลอด

### Order Sync:
- **Webhook (Real-time)**: ถ้า platform รองรับ
- **Polling (Scheduled)**: ตรวจสอบทุก 15 นาที
- **Date Range Sync**: ดึง orders ย้อนหลังตามช่วงเวลา

---

## 🎨 User Interface Components

### Admin Panel:
1. **Marketplace Settings**
   - จัดการ platforms
   - API credentials management
   - Commission rate settings

2. **Account Management**
   - เพิ่ม/แก้ไข marketplace accounts
   - ทดสอบ API connection
   - ดู sync logs

3. **Product Management**
   - ดูสินค้าที่ sync มา
   - จัดการ commission rates
   - Enable/disable products

4. **Order Management**
   - ดู marketplace orders
   - Approve/reject commissions
   - Export reports

5. **Analytics Dashboard**
   - Total sales per platform
   - Commission paid
   - Top affiliates
   - Conversion rates

### User Panel:
1. **Marketplace Products**
   - Browse products from all platforms
   - Filter by platform/category
   - Search functionality

2. **My Affiliate Links**
   - สร้าง affiliate links
   - ดูสถิติ clicks/conversions
   - Share buttons (Social media, LINE, copy link)

3. **My Marketplace Commissions**
   - รายการ commissions
   - Filter by platform/status
   - แสดง pending/approved/paid

4. **Marketplace Analytics**
   - Performance metrics
   - Best performing products
   - Click-through rates

---

## 🔌 API Endpoints Design

### Admin API:

```
POST   /admin/marketplace/platforms                    - Create platform
PUT    /admin/marketplace/platforms/{id}               - Update platform
DELETE /admin/marketplace/platforms/{id}               - Delete platform

POST   /admin/marketplace/accounts                     - Add account
PUT    /admin/marketplace/accounts/{id}                - Update account
DELETE /admin/marketplace/accounts/{id}                - Delete account
POST   /admin/marketplace/accounts/{id}/test           - Test connection
POST   /admin/marketplace/accounts/{id}/sync-products  - Trigger product sync
POST   /admin/marketplace/accounts/{id}/sync-orders    - Trigger order sync

GET    /admin/marketplace/products                     - List products
PUT    /admin/marketplace/products/{id}                - Update product
DELETE /admin/marketplace/products/{id}                - Delete product

GET    /admin/marketplace/orders                       - List orders
GET    /admin/marketplace/orders/{id}                  - Order details

GET    /admin/marketplace/commissions                  - List commissions
POST   /admin/marketplace/commissions/{id}/approve     - Approve commission
POST   /admin/marketplace/commissions/{id}/reject      - Reject commission
POST   /admin/marketplace/commissions/{id}/pay         - Pay commission

GET    /admin/marketplace/analytics                    - Analytics data
GET    /admin/marketplace/sync-logs                    - Sync logs
```

### User API:

```
GET    /user/marketplace/products                      - Browse products
GET    /user/marketplace/products/{id}                 - Product details
POST   /user/marketplace/affiliate-links               - Create affiliate link
GET    /user/marketplace/affiliate-links               - My links
DELETE /user/marketplace/affiliate-links/{id}          - Delete link
GET    /user/marketplace/affiliate-links/{id}/stats    - Link statistics

GET    /user/marketplace/commissions                   - My commissions
GET    /user/marketplace/analytics                     - My analytics
```

### Public API:

```
GET    /marketplace/redirect/{shortCode}               - Redirect to affiliate link (track click)
```

### Webhooks:

```
POST   /webhook/marketplace/lazada                     - Lazada webhook
POST   /webhook/marketplace/tiktok                     - TikTok Shop webhook
POST   /webhook/marketplace/shopee                     - Shopee webhook
```

---

## 📝 Implementation Notes

### ลำดับการพัฒนา:

**Phase 1: Database & Models** ✅
- Create migrations
- Create Eloquent models
- Setup relationships

**Phase 2: Service Layer**
- LazadaApiService
- TikTokApiService
- ShopeeApiService
- MarketplaceCommissionService
- MarketplaceSyncService

**Phase 3: Admin Features**
- Platform management
- Account management
- Product/Order viewing
- Commission approval

**Phase 4: User Features**
- Product browsing
- Affiliate link creation
- Commission tracking
- Analytics dashboard

**Phase 5: Integration**
- MLM commission distribution
- Wallet integration
- Notification system
- Email/LINE notifications

**Phase 6: Testing & Documentation**
- API testing
- Integration testing
- User documentation
- Admin guide

---

## 🔗 External API References

### 1. Lazada Open Platform
- **Website**: https://open.lazada.com/
- **API Docs**: https://open.lazada.com/doc/doc.htm
- **Required**:
  - App Key
  - App Secret
  - Access Token (OAuth 2.0)
- **Key APIs**:
  - Product API (GetProducts, GetProductItem)
  - Order API (GetOrders, GetOrder)
  - Finance API (GetTransactionDetails)
  - Affiliate API (GetAffiliateLinks)

### 2. TikTok Shop Open Platform
- **Website**: https://partner.tiktokshop.com/
- **API Docs**: https://partner.tiktokshop.com/doc
- **Required**:
  - App Key
  - App Secret
  - Access Token
  - Shop ID
- **Key APIs**:
  - Product API (GetProductList, GetProductDetail)
  - Order API (GetOrderList, GetOrderDetail)
  - Authorization API (OAuth)
  - Webhook subscriptions

### 3. Shopee Open Platform
- **Website**: https://open.shopee.com/
- **API Docs**: https://open.shopee.com/documents
- **Required**:
  - Partner ID
  - Partner Key
  - Shop ID
  - Access Token
- **Key APIs**:
  - Product API (GetItemsList, GetItemDetail)
  - Order API (GetOrdersList, GetOrderDetail)
  - Logistics API (GetTrackingNumber)
  - Authorization API

---

## 🎯 Success Metrics

### KPIs to Track:
- Number of marketplace accounts connected
- Total products synced
- Number of affiliate links created
- Total clicks on affiliate links
- Conversion rate (clicks → orders)
- Total marketplace orders
- Total commission paid
- Average commission per user
- Top performing platforms
- Top performing products
- User engagement (daily/monthly active affiliates)

---

## 🚀 Future Enhancements

1. **More Marketplaces**:
   - Amazon
   - AliExpress
   - Facebook Marketplace
   - LINE Shopping

2. **Advanced Features**:
   - AI-powered product recommendations
   - Automated posting to social media
   - A/B testing for affiliate links
   - Smart commission optimization
   - Fraud detection

3. **Mobile App**:
   - iOS/Android apps for affiliates
   - Push notifications for new products
   - Quick share functionality

4. **Gamification**:
   - Leaderboards
   - Badges and achievements
   - Contests and challenges

---

## 📚 Additional Resources

### คำแนะนำการขอ API Credentials:

ดูเอกสารแยกใน:
- `docs/marketplace/LAZADA_API_SETUP.md`
- `docs/marketplace/TIKTOK_API_SETUP.md`
- `docs/marketplace/SHOPEE_API_SETUP.md`

---

**Last Updated**: 2025-11-08
**Version**: 1.0
**Author**: Thaiprompt Affiliate Development Team
