# ThaiPrompt Affiliate Marketplace - System Recreation Prompt

## คำสั่งสำหรับสร้างระบบใหม่ทั้งหมด

คุณเป็น Senior Full-Stack Developer ที่มีความเชี่ยวชาญใน Laravel, MLM Systems, E-commerce และ Payment Integration

ฉันต้องการให้คุณสร้างระบบ **Multi-Vendor Affiliate Marketplace พร้อม MLM System** ที่มีความสามารถครบถ้วนตามรายละเอียดด้านล่าง

---

## 🎯 OVERVIEW

สร้าง **Multi-Vendor Marketplace** ที่รวม:
- ระบบร้านค้าหลายผู้ขาย (Multi-Vendor)
- ระบบ MLM แบบ Unilevel ไม่จำกัดชั้น
- ระบบ Wallet และ Commission
- ระบบโรงแรม (Hotel Management Addon)
- ระบบชำระเงินแบบ NFC
- ระบบ Theme Customization
- การผสานกับ PromptPay, Stripe, LINE OA

**เวอร์ชันเป้าหมาย:** 1.2.0
**ภาษา:** PHP 8.2+, Laravel 11
**Database:** MySQL 8.0+

---

## 📦 TECH STACK

### Backend Framework
```json
{
  "framework": "Laravel 11.0",
  "php_version": "8.2+",
  "authentication": "Laravel Sanctum 4.0",
  "authorization": "Spatie Permission 6.0",
  "pdf": "Laravel DomPDF 2.2",
  "excel": "Maatwebsite Excel 3.1",
  "image": "Intervention Image 3.0",
  "payment": "Stripe PHP SDK 13.0",
  "http": "Guzzle HTTP 7.8"
}
```

### Frontend Stack
```json
{
  "build_tool": "Vite 4.5.0",
  "css": "Tailwind CSS 3.3.5",
  "javascript": "Alpine.js 3.13.3",
  "charts": {
    "chartjs": "4.4.1",
    "d3": "7.8.5",
    "d3_hierarchy": "3.1.2"
  },
  "animations": "GSAP 3.12.5",
  "notifications": "SweetAlert2 11.10.5",
  "icons": "@iconify/iconify 3.1.1",
  "http_client": "Axios 1.6.0"
}
```

### Infrastructure
```yaml
Docker Services:
  - app: PHP 8.2-FPM Alpine (main application)
  - nginx: Alpine (web server, reverse proxy)
  - mysql: 8.0 (primary database)
  - redis: 7-Alpine (cache, sessions, queues)
  - queue: Background job processor
  - scheduler: Cron-like task scheduler
```

---

## 🗄️ DATABASE ARCHITECTURE

### Core Tables (สร้างครบทุก migration)

```sql
-- Users & Authentication
CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255),
  phone VARCHAR(50),
  referral_code VARCHAR(20) UNIQUE,
  sponsor_id BIGINT NULLABLE,
  mlm_level INT DEFAULT 0,
  mlm_position VARCHAR(50),
  role ENUM('admin', 'vendor', 'customer'),
  is_active BOOLEAN DEFAULT TRUE,
  email_verified_at TIMESTAMP NULLABLE,
  remember_token VARCHAR(100),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (sponsor_id) REFERENCES users(id)
);

-- Vendors (Multi-Vendor Stores)
CREATE TABLE vendors (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNIQUE,
  shop_name VARCHAR(255),
  shop_slug VARCHAR(255) UNIQUE,
  shop_description TEXT,
  shop_logo VARCHAR(255),
  shop_banner VARCHAR(255),
  business_tax_id VARCHAR(50),
  bank_name VARCHAR(100),
  bank_account_number VARCHAR(50),
  bank_account_name VARCHAR(255),
  commission_rate DECIMAL(5,2) DEFAULT 70.00,
  is_approved BOOLEAN DEFAULT FALSE,
  is_verified BOOLEAN DEFAULT FALSE,
  verification_badge ENUM('bronze', 'silver', 'gold', 'platinum'),
  approved_at TIMESTAMP NULLABLE,
  approved_by BIGINT NULLABLE,
  featured BOOLEAN DEFAULT FALSE,
  status ENUM('pending', 'active', 'suspended', 'rejected'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Products
CREATE TABLE products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  vendor_id BIGINT,
  category_id BIGINT,
  name VARCHAR(255),
  slug VARCHAR(255),
  description TEXT,
  short_description VARCHAR(500),
  sku VARCHAR(100) UNIQUE,
  price DECIMAL(10,2),
  sale_price DECIMAL(10,2) NULLABLE,
  cost DECIMAL(10,2),
  quantity INT DEFAULT 0,
  weight DECIMAL(8,2),
  dimensions VARCHAR(100),
  images JSON,
  featured_image VARCHAR(255),
  is_featured BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  views INT DEFAULT 0,
  sales_count INT DEFAULT 0,
  meta_title VARCHAR(255),
  meta_description TEXT,
  meta_keywords VARCHAR(500),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Categories
CREATE TABLE categories (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  parent_id BIGINT NULLABLE,
  name VARCHAR(255),
  slug VARCHAR(255) UNIQUE,
  description TEXT,
  image VARCHAR(255),
  icon VARCHAR(100),
  order INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_number VARCHAR(50) UNIQUE,
  user_id BIGINT,
  vendor_id BIGINT,
  subtotal DECIMAL(10,2),
  tax DECIMAL(10,2) DEFAULT 0,
  shipping DECIMAL(10,2) DEFAULT 0,
  discount DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2),
  status ENUM('pending', 'processing', 'completed', 'cancelled', 'refunded'),
  payment_method ENUM('stripe', 'promptpay', 'wallet', 'cash'),
  payment_status ENUM('pending', 'paid', 'failed', 'refunded'),
  payment_id VARCHAR(255),
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  paid_at TIMESTAMP NULLABLE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

-- Order Items
CREATE TABLE order_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT,
  product_id BIGINT,
  quantity INT,
  price DECIMAL(10,2),
  subtotal DECIMAL(10,2),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- MLM Networks
CREATE TABLE mlm_networks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNIQUE,
  sponsor_id BIGINT NULLABLE,
  level INT DEFAULT 1,
  position VARCHAR(50),
  path VARCHAR(500),
  left_count INT DEFAULT 0,
  right_count INT DEFAULT 0,
  total_downline INT DEFAULT 0,
  personal_sales DECIMAL(12,2) DEFAULT 0,
  team_sales DECIMAL(12,2) DEFAULT 0,
  joined_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (sponsor_id) REFERENCES users(id)
);

-- MLM Ranks
CREATE TABLE mlm_ranks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),
  level INT UNIQUE,
  required_personal_sales DECIMAL(12,2) DEFAULT 0,
  required_team_sales DECIMAL(12,2) DEFAULT 0,
  required_direct_referrals INT DEFAULT 0,
  bonus_percentage DECIMAL(5,2) DEFAULT 0,
  benefits JSON,
  badge_icon VARCHAR(255),
  badge_color VARCHAR(20),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- User Ranks
CREATE TABLE user_ranks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT,
  rank_id BIGINT,
  achieved_at TIMESTAMP,
  is_current BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rank_id) REFERENCES mlm_ranks(id)
);

-- MLM Genealogy (Complete tree tracking)
CREATE TABLE mlm_genealogy (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT,
  ancestor_id BIGINT,
  depth INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (ancestor_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_genealogy (user_id, ancestor_id)
);

-- Commissions
CREATE TABLE commissions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT,
  order_id BIGINT NULLABLE,
  referrer_id BIGINT,
  type ENUM('level_commission', 'rank_bonus', 'performance_bonus', 'matching_bonus'),
  level INT,
  amount DECIMAL(10,2),
  percentage DECIMAL(5,2),
  status ENUM('pending', 'approved', 'paid', 'rejected'),
  calculation_details JSON,
  paid_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (referrer_id) REFERENCES users(id)
);

-- Commission Settings
CREATE TABLE commission_settings (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  level INT UNIQUE,
  percentage DECIMAL(5,2),
  name VARCHAR(100),
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Wallets
CREATE TABLE wallets (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNIQUE,
  balance DECIMAL(12,2) DEFAULT 0,
  pending_balance DECIMAL(12,2) DEFAULT 0,
  total_earned DECIMAL(12,2) DEFAULT 0,
  total_withdrawn DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wallet Transactions
CREATE TABLE wallet_transactions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  wallet_id BIGINT,
  transaction_id VARCHAR(100) UNIQUE,
  type ENUM('credit', 'debit'),
  amount DECIMAL(10,2),
  balance_before DECIMAL(12,2),
  balance_after DECIMAL(12,2),
  reference_type VARCHAR(100),
  reference_id BIGINT,
  description TEXT,
  metadata JSON,
  status ENUM('pending', 'completed', 'failed'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
);

-- Withdrawals
CREATE TABLE withdrawals (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT,
  amount DECIMAL(10,2),
  fee DECIMAL(10,2) DEFAULT 0,
  net_amount DECIMAL(10,2),
  method ENUM('bank_transfer', 'promptpay', 'check'),
  bank_name VARCHAR(100),
  account_number VARCHAR(100),
  account_name VARCHAR(255),
  status ENUM('pending', 'processing', 'completed', 'rejected'),
  admin_notes TEXT,
  processed_by BIGINT NULLABLE,
  processed_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Hotels
CREATE TABLE hotels (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  vendor_id BIGINT,
  name VARCHAR(255),
  slug VARCHAR(255) UNIQUE,
  description TEXT,
  address TEXT,
  city VARCHAR(100),
  province VARCHAR(100),
  postal_code VARCHAR(20),
  country VARCHAR(100) DEFAULT 'Thailand',
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  phone VARCHAR(50),
  email VARCHAR(255),
  website VARCHAR(255),
  star_rating INT,
  amenities JSON,
  policies JSON,
  check_in_time TIME,
  check_out_time TIME,
  images JSON,
  featured_image VARCHAR(255),
  video_url VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  total_bookings INT DEFAULT 0,
  total_revenue DECIMAL(12,2) DEFAULT 0,
  meta_title VARCHAR(255),
  meta_description TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- Room Types
CREATE TABLE room_types (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  hotel_id BIGINT,
  name VARCHAR(255),
  description TEXT,
  max_occupancy INT,
  size_sqm DECIMAL(6,2),
  bed_type VARCHAR(100),
  base_price DECIMAL(10,2),
  images JSON,
  amenities JSON,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Rooms
CREATE TABLE rooms (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  room_type_id BIGINT,
  room_number VARCHAR(50),
  floor INT,
  status ENUM('available', 'occupied', 'maintenance', 'reserved'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
);

-- Hotel Bookings
CREATE TABLE hotel_bookings (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  booking_number VARCHAR(50) UNIQUE,
  user_id BIGINT,
  hotel_id BIGINT,
  room_type_id BIGINT,
  room_id BIGINT NULLABLE,
  check_in_date DATE,
  check_out_date DATE,
  nights INT,
  guests_count INT,
  room_price DECIMAL(10,2),
  subtotal DECIMAL(10,2),
  tax DECIMAL(10,2),
  service_fee DECIMAL(10,2),
  discount DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2),
  payment_method ENUM('stripe', 'promptpay', 'wallet', 'cash'),
  payment_status ENUM('pending', 'paid', 'failed', 'refunded'),
  status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'),
  special_requests TEXT,
  cancellation_reason TEXT,
  checked_in_at TIMESTAMP NULLABLE,
  checked_out_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (hotel_id) REFERENCES hotels(id),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Booking Guests
CREATE TABLE booking_guests (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  booking_id BIGINT,
  first_name VARCHAR(255),
  last_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(50),
  id_number VARCHAR(100),
  is_primary BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES hotel_bookings(id) ON DELETE CASCADE
);

-- Hotel Promotions
CREATE TABLE hotel_promotions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  hotel_id BIGINT,
  code VARCHAR(50) UNIQUE,
  name VARCHAR(255),
  description TEXT,
  type ENUM('percentage', 'fixed_amount'),
  value DECIMAL(10,2),
  min_nights INT DEFAULT 1,
  min_amount DECIMAL(10,2) DEFAULT 0,
  max_uses INT NULLABLE,
  used_count INT DEFAULT 0,
  starts_at TIMESTAMP,
  expires_at TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- NFC Cards
CREATE TABLE nfc_cards (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  card_uid VARCHAR(100) UNIQUE,
  user_id BIGINT NULLABLE,
  wallet_id BIGINT NULLABLE,
  is_active BOOLEAN DEFAULT TRUE,
  linked_at TIMESTAMP NULLABLE,
  last_used_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);

-- NFC Card Transactions
CREATE TABLE nfc_card_transactions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  nfc_card_id BIGINT,
  transaction_id VARCHAR(100) UNIQUE,
  amount DECIMAL(10,2),
  type ENUM('payment', 'balance_check', 'card_info'),
  status ENUM('success', 'failed'),
  metadata JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (nfc_card_id) REFERENCES nfc_cards(id)
);

-- Addons (Purchasable Features)
CREATE TABLE addons (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  slug VARCHAR(255) UNIQUE,
  description TEXT,
  version VARCHAR(20),
  price DECIMAL(10,2),
  billing_cycle ENUM('monthly', 'yearly', 'lifetime'),
  features JSON,
  icon VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Addon Purchases
CREATE TABLE addon_purchases (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT,
  addon_id BIGINT,
  price_paid DECIMAL(10,2),
  billing_cycle ENUM('monthly', 'yearly', 'lifetime'),
  starts_at TIMESTAMP,
  expires_at TIMESTAMP NULLABLE,
  is_active BOOLEAN DEFAULT TRUE,
  auto_renew BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (addon_id) REFERENCES addons(id)
);

-- Theme Customizations
CREATE TABLE theme_customizations (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNIQUE,
  primary_color VARCHAR(20) DEFAULT '#4F46E5',
  secondary_color VARCHAR(20) DEFAULT '#EC4899',
  accent_color VARCHAR(20) DEFAULT '#06B6D4',
  gradient_preset VARCHAR(50),
  logo VARCHAR(255),
  favicon VARCHAR(255),
  banner VARCHAR(255),
  custom_css TEXT,
  custom_js TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Shop Verifications (KYC)
CREATE TABLE shop_verifications (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  vendor_id BIGINT,
  level ENUM('bronze', 'silver', 'gold', 'platinum'),
  documents JSON,
  status ENUM('pending', 'approved', 'rejected'),
  admin_notes TEXT,
  submitted_at TIMESTAMP,
  reviewed_at TIMESTAMP NULLABLE,
  reviewed_by BIGINT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- App Settings
CREATE TABLE app_settings (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  key VARCHAR(255) UNIQUE,
  value TEXT,
  type VARCHAR(50) DEFAULT 'string',
  description TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- System Info
CREATE TABLE system_info (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  version VARCHAR(20),
  build_number VARCHAR(50),
  release_date DATE,
  changelog TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## 🎨 CORE FEATURES TO IMPLEMENT

### 1. Multi-Vendor Marketplace

**Vendor Management:**
- ผู้ใช้สามารถสมัครเป็น Vendor ได้
- Admin อนุมัติ/ปฏิเสธ Vendor
- Vendor Dashboard พร้อม:
  - สถิติยอดขาย
  - จัดการสินค้า (CRUD)
  - ดูออเดอร์
  - ระบบ POS (Point of Sale)
  - จัดการพนักงาน
  - Theme Customization

**Product Management:**
- CRUD สินค้าพร้อม:
  - รูปภาพหลายรูป
  - SKU, ราคา, ราคาพิเศษ
  - สต็อก
  - หมวดหมู่
  - SEO meta tags
  - Featured products
  - Product variations (optional)

**Order Management:**
- ตะกร้าสินค้า (Cart)
- Checkout process
- Payment integration (Stripe, PromptPay, Wallet, Cash)
- Order status tracking
- Order history

### 2. MLM System (Unilevel - Unlimited Depth)

**Network Registration:**
```php
// Service: app/Services/MLM/MlmService.php
public function registerUser($user, $sponsorId = null)
{
    // 1. สร้าง MlmNetwork record
    // 2. คำนวณ level = sponsor->level + 1
    // 3. Generate path สำหรับ tracking position
    // 4. Build complete genealogy chain (ทุก ancestor)
    // 5. Update sponsor's downline counts
}
```

**Commission Distribution:**
```php
public function distributeCommissions($order)
{
    // เมื่อ order status = 'paid':
    // 1. ดึง commission_settings (Level 1: 10%, Level 2: 5%, Level 3: 3%)
    // 2. Loop แต่ละ level:
    //    - คำนวณ commission = order->total * (percentage / 100)
    //    - หา ancestor ที่ depth = level
    //    - สร้าง Commission record
    //    - Credit wallet ของ ancestor
    // 3. อัพเดท personal_sales, team_sales
}
```

**Genealogy Tracking:**
```php
public function buildGenealogy($userId, $sponsorId)
{
    // สร้าง MlmGenealogy records:
    // 1. Direct sponsor (depth = 1)
    // 2. All ancestors of sponsor (depth + 1)
    // ใช้สำหรับ query ได้เร็ว:
    // - "หา downline ทั้งหมดของ user X"
    // - "หา ancestor ชั้นที่ 3 ของ user Y"
}
```

**MLM Tree Visualization (D3.js):**
- Interactive tree view
- Zoom & Pan
- Node click เพื่อดูรายละเอียด
- แสดง commission flow
- Real-time updates

**Rank System:**
- 5 Ranks: Bronze, Silver, Gold, Platinum, Diamond
- Auto-calculation based on:
  - Personal sales
  - Team sales
  - Direct referrals count
- Rank achievement bonuses
- Rank history tracking

### 3. Digital Wallet System

**Wallet Operations:**
```php
// app/Services/Wallet/WalletService.php
public function credit($walletId, $amount, $reference)
{
    // 1. Validate amount > 0
    // 2. Lock wallet (prevent race condition)
    // 3. Update balance
    // 4. Update total_earned
    // 5. Create WalletTransaction
    // 6. Generate transaction_id (TXN-YYYYMMDD-XXXXX)
}

public function debit($walletId, $amount, $reference)
{
    // 1. Check sufficient balance
    // 2. Lock wallet
    // 3. Deduct balance
    // 4. Create WalletTransaction
}
```

**Withdrawal System:**
```php
public function requestWithdrawal($userId, $amount, $method, $details)
{
    // 1. Validate minimum (100 THB)
    // 2. Calculate fee = max(10, amount * 0.02)
    // 3. Check balance >= amount
    // 4. Debit wallet
    // 5. Create Withdrawal (status: pending)
}

public function approveWithdrawal($withdrawalId)
{
    // 1. Update status → 'completed'
    // 2. Record processed_by, processed_at
    // 3. Send notification
    // 4. Process external transfer (bank/PromptPay)
}

public function rejectWithdrawal($withdrawalId, $reason)
{
    // 1. Refund to wallet
    // 2. Update status → 'rejected'
    // 3. Add admin notes
    // 4. Send notification
}
```

### 4. Payment Integration

**Stripe:**
```php
// app/Services/Payment/StripeService.php
public function processPayment($order)
{
    $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

    $intent = $stripe->paymentIntents->create([
        'amount' => $order->total * 100,
        'currency' => 'thb',
        'metadata' => ['order_id' => $order->id]
    ]);

    return $intent->client_secret;
}

public function handleWebhook($payload, $signature)
{
    // Verify signature
    // Handle events: payment_intent.succeeded, payment_intent.failed
    // Update order payment_status
    // Trigger commission distribution
}
```

**PromptPay:**
```php
public function generateQR($order)
{
    // Generate PromptPay QR Code
    // Store payment_id
    // Return QR image URL
}

public function handleWebhook($payload)
{
    // Verify payment success
    // Update order
    // Trigger commission
}
```

**Wallet Payment:**
```php
public function processWalletPayment($order, $userId)
{
    // 1. Check balance
    // 2. Debit wallet
    // 3. Update order (payment_status: paid)
    // 4. Create order transaction
    // 5. Trigger commission distribution
}
```

### 5. Hotel Management System (Addon)

**Hotel Features:**
- Hotel property management
- Multiple room types
- Room inventory
- Amenities & facilities
- Star rating
- Photo gallery

**Booking System:**
```php
// app/Services/Hotel/BookingService.php
public function createBooking($data)
{
    // 1. Check room availability (dates, room_type_id)
    // 2. Calculate pricing:
    //    - nights = checkout - checkin
    //    - subtotal = room_price * nights
    //    - Apply promotion discount
    //    - Calculate tax (7%)
    //    - Add service fee (5%)
    //    - total = subtotal + tax + fee - discount
    // 3. Create HotelBooking
    // 4. Generate booking_number (BOOK-YYYYMMDD-XXXXX)
    // 5. Process payment
    // 6. Send confirmation email
}

public function checkIn($bookingId)
{
    // 1. Update status → 'checked_in'
    // 2. Assign room_id
    // 3. Record checked_in_at
}

public function checkOut($bookingId)
{
    // 1. Update status → 'checked_out'
    // 2. Record checked_out_at
    // 3. Process commission:
    //    - Vendor: 70%
    //    - Admin: 30%
    // 4. Request review
}
```

**Promotion System:**
- Discount codes
- Percentage or fixed amount
- Min nights/amount requirements
- Usage limits
- Date restrictions

### 6. NFC Payment System

**Card Registration:**
```php
// app/Services/NFC/NfcService.php
public function registerCard($cardUid)
{
    // Create NfcCard record
    // Status: unlinked
}

public function linkCard($cardUid, $userId)
{
    // 1. Find card by UID
    // 2. Link to user & wallet
    // 3. Activate card
    // 4. Set linked_at
}
```

**Payment Processing:**
```php
public function processPayment($cardUid, $amount)
{
    // 1. Find card & validate:
    //    - Is linked?
    //    - Is active?
    // 2. Get wallet
    // 3. Check balance >= amount
    // 4. Debit wallet
    // 5. Create NfcCardTransaction
    // 6. Update last_used_at
    // 7. Return receipt data
}
```

**Web NFC Integration (Frontend):**
```javascript
// resources/js/nfc.js
async function readNFCCard() {
    if ('NDEFReader' in window) {
        const reader = new NDEFReader();
        await reader.scan();

        reader.onreading = (event) => {
            const cardUid = event.serialNumber;
            processPayment(cardUid, amount);
        };
    }
}
```

### 7. Theme Customization

**Theme System:**
- Color customization (primary, secondary, accent)
- 6 Gradient presets:
  1. Default (Indigo-Pink)
  2. Ocean (Blue-Cyan)
  3. Sunset (Orange-Red-Pink)
  4. Forest (Green-Lime)
  5. Royal (Purple)
  6. Custom
- Logo & favicon upload
- Custom CSS/JS
- Real-time preview

**Subscription Tiers:**
```php
'monthly' => 299,    // THB/month
'yearly' => 2990,   // THB/year (17% savings)
'lifetime' => 9990  // THB one-time
```

### 8. Shop Verification (KYC)

**Verification Levels:**
- 🥉 Bronze: Basic (ID card)
- 🥈 Silver: Enhanced (ID + Business license)
- 🥇 Gold: Full (ID + Business + Bank verification)
- 💎 Platinum: Premium (All above + Background check)

**Verification Flow:**
```php
public function submitVerification($vendorId, $level, $documents)
{
    // 1. Upload documents (ID, license, etc.)
    // 2. Create ShopVerification (status: pending)
    // 3. Notify admin
}

public function approveVerification($verificationId)
{
    // 1. Update status → 'approved'
    // 2. Update vendor:
    //    - is_verified = true
    //    - verification_badge = level
    //    - verified_at = now
    // 3. Send approval email with badge
}
```

### 9. Admin Dashboard

**Statistics:**
- Total users, vendors, products, orders
- Revenue trends (12-month chart using Chart.js)
- Commission breakdown
- Top products
- User growth
- Vendor status distribution

**Admin Capabilities:**
- User management (view, edit, ban)
- Vendor approval/rejection
- Commission management
- Withdrawal approval
- Product & order oversight
- System settings
- Backup & restore
- Version management
- Security logs

**Vendor Dashboard:**
- Sales overview
- Product performance
- Order management
- POS system
- Employee management
- Store settings
- Theme customization

### 10. Additional Features

**Reviews & Ratings:**
- Product reviews
- Hotel reviews
- Rating system (1-5 stars)
- Verified purchase badge

**Notifications:**
- Email notifications (order, commission, withdrawal)
- In-app notifications
- LINE OA integration

**Security:**
- Role-based access control (RBAC) with Spatie Permission
- Spam detection
- Rate limiting
- Security audit logs
- Input validation
- CSRF protection

**Backup System:**
- Full backup (code + database)
- Database-only backup
- Scheduled backups
- One-click restore
- Version rollback

---

## 🔧 SERVICES ARCHITECTURE

สร้าง Service Classes สำหรับ Business Logic:

```
app/Services/
├── MLM/
│   ├── MlmService.php              # MLM registration, genealogy, network
│   └── CommissionService.php       # Commission calculation & distribution
├── Wallet/
│   └── WalletService.php           # Wallet operations, withdrawal
├── Payment/
│   ├── PaymentService.php          # Payment router
│   ├── StripeService.php           # Stripe integration
│   ├── PromptPayService.php        # PromptPay integration
│   └── WalletPaymentService.php    # Wallet payment
├── Hotel/
│   ├── HotelService.php            # Hotel management
│   └── BookingService.php          # Booking workflow
├── NFC/
│   └── NfcService.php              # NFC card & payment
├── Theme/
│   └── ThemeService.php            # Theme customization
├── Verification/
│   └── ShopVerificationService.php # KYC processing
└── System/
    ├── BackupService.php           # Backup & restore
    └── VersionService.php          # Version management
```

---

## 🌐 API ENDPOINTS

### Authentication
```
POST   /api/v1/register
POST   /api/v1/login
POST   /api/v1/register-with-referral
POST   /api/v1/logout (auth)
GET    /api/v1/user (auth)
```

### Products & Marketplace
```
GET    /api/v1/products
GET    /api/v1/products/{id}
GET    /api/v1/categories
GET    /api/v1/vendors
```

### Cart & Orders
```
GET    /api/v1/cart (auth)
POST   /api/v1/cart/add (auth)
PUT    /api/v1/cart/{item} (auth)
DELETE /api/v1/cart/{item} (auth)
GET    /api/v1/orders (auth)
POST   /api/v1/orders (auth)
```

### MLM
```
GET    /api/v1/mlm/stats (auth)
GET    /api/v1/mlm/network (auth)
GET    /api/v1/mlm/commissions (auth)
GET    /api/v1/mlm/tree-data/{userId} (auth)
POST   /api/v1/mlm/invite (auth)
```

### Wallet
```
GET    /api/v1/wallet (auth)
GET    /api/v1/wallet/transactions (auth)
POST   /api/v1/wallet/withdraw (auth)
```

### Hotels
```
GET    /api/v1/hotels
GET    /api/v1/hotels/{id}
GET    /api/v1/hotels/{id}/availability
POST   /api/v1/bookings (auth)
POST   /api/v1/bookings/{id}/cancel (auth)
```

### Vendor (role:vendor)
```
GET    /api/v1/vendor/dashboard
GET    /api/v1/vendor/products
POST   /api/v1/vendor/products
PUT    /api/v1/vendor/products/{id}
DELETE /api/v1/vendor/products/{id}
```

### Admin (role:admin)
```
GET    /api/v1/admin/dashboard
GET    /api/v1/admin/users
POST   /api/v1/admin/vendors/{id}/approve
POST   /api/v1/admin/vendors/{id}/reject
GET    /api/v1/admin/withdrawals
POST   /api/v1/admin/withdrawals/{id}/approve
```

### NFC (public)
```
POST   /api/v1/nfc/process
POST   /api/v1/nfc/check-balance
POST   /api/v1/nfc/verify
```

### Webhooks
```
POST   /webhooks/stripe
POST   /webhooks/promptpay
POST   /webhooks/line
```

---

## 🚀 DEPLOYMENT & INFRASTRUCTURE

### Docker Setup (คงไว้ตามเดิม)

สร้างไฟล์ `docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      target: production
    container_name: thaiprompt_app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    networks:
      - thaiprompt
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: thaiprompt_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - thaiprompt
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: thaiprompt_mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - thaiprompt
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  redis:
    image: redis:7-alpine
    container_name: thaiprompt_redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - thaiprompt

  queue:
    build:
      context: .
      dockerfile: Dockerfile
      target: production
    container_name: thaiprompt_queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3
    volumes:
      - ./:/var/www/html
    networks:
      - thaiprompt
    depends_on:
      - mysql
      - redis

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
      target: production
    container_name: thaiprompt_scheduler
    restart: unless-stopped
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"
    volumes:
      - ./:/var/www/html
    networks:
      - thaiprompt
    depends_on:
      - mysql
      - redis

networks:
  thaiprompt:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
```

### Deployment Script (คงไว้ตามเดิม)

**สร้างไฟล์ `deploy.sh` เหมือนเดิมทุกบรรทัด** ตามที่อยู่ในโปรเจกต์ปัจจุบัน (342 บรรทัด)

Key features ของ deploy script:
1. Maintenance mode toggle
2. Git pull from main branch (claude/Main)
3. Composer & NPM install with error handling
4. Database migrations
5. Cache optimization
6. Permission management
7. Service restart
8. Comprehensive error handling

### Environment Configuration

สร้าง `.env` จาก template:

```bash
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=root
DB_PASSWORD=secure_password

REDIS_HOST=redis
REDIS_PORT=6379

# Payment Gateways
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

PROMPTPAY_MERCHANT_ID=...
PROMPTPAY_API_KEY=...

# LINE Integration
LINE_CHANNEL_ID=...
LINE_CHANNEL_SECRET=...
LINE_CHANNEL_ACCESS_TOKEN=...

# MLM Settings
MLM_TYPE=unilevel
MLM_MAX_DEPTH=10
COMMISSION_RATE_LEVEL_1=10
COMMISSION_RATE_LEVEL_2=5
COMMISSION_RATE_LEVEL_3=3
VENDOR_COMMISSION_RATE=70
ADMIN_COMMISSION_RATE=30
```

---

## 📋 IMPLEMENTATION STEPS

### Phase 1: Foundation (Week 1-2)
1. ✅ Setup Laravel 11 + Docker
2. ✅ Install dependencies (Sanctum, Spatie Permission, etc.)
3. ✅ Setup Vite + Tailwind CSS
4. ✅ Create all migrations (59 tables)
5. ✅ Create all models with relationships
6. ✅ Setup authentication (Sanctum + session)
7. ✅ Create Spatie roles & permissions seeder

### Phase 2: Core Features (Week 3-4)
8. ✅ Multi-vendor system
   - Vendor registration & approval
   - Vendor dashboard
   - Shop profile management
9. ✅ Product management
   - CRUD operations
   - Image uploads
   - Categories
10. ✅ Shopping cart & checkout
11. ✅ Order management
12. ✅ Basic payment integration (Stripe)

### Phase 3: MLM System (Week 5-6)
13. ✅ MLM Service implementation
    - Network registration
    - Genealogy building
    - Path tracking
14. ✅ Commission Service
    - Auto-calculation
    - Distribution on payment
15. ✅ MLM Ranks
    - Rank definitions
    - Auto-advancement
    - Bonuses
16. ✅ D3.js Tree Visualization
    - Interactive network view
    - Node details
    - Real-time updates

### Phase 4: Wallet & Payments (Week 7-8)
17. ✅ Wallet Service
    - Credit/Debit operations
    - Transaction logging
18. ✅ Withdrawal system
    - Request workflow
    - Admin approval
    - Fee calculation
19. ✅ PromptPay integration
20. ✅ Wallet payment method

### Phase 5: Advanced Features (Week 9-10)
21. ✅ Hotel Management System
    - Property management
    - Room types & inventory
    - Booking system
    - Pricing & promotions
22. ✅ NFC Payment System
    - Card registration
    - Payment processing
    - Web NFC integration
23. ✅ Theme Customization
    - Color presets
    - Custom CSS/JS
    - Logo uploads
    - Subscription system

### Phase 6: Admin & Final (Week 11-12)
24. ✅ Admin Dashboard
    - Statistics & charts
    - User management
    - Commission oversight
25. ✅ Shop Verification (KYC)
    - Document upload
    - Approval workflow
26. ✅ Backup & Restore system
27. ✅ Version management (GitHub integration)
28. ✅ Security & optimization
29. ✅ Testing & bug fixes
30. ✅ Documentation

---

## 🔐 SECURITY REQUIREMENTS

1. **Authentication:**
   - Bcrypt password hashing
   - API token validation (Sanctum)
   - Session expiry (120 min)
   - CSRF protection

2. **Authorization:**
   - RBAC with Spatie Permission
   - Policy-based access
   - Middleware route protection

3. **Input Validation:**
   - Laravel validation on all requests
   - Sanitization middleware
   - Spam detection
   - Rate limiting

4. **Financial Security:**
   - Transaction logging
   - Withdrawal approval workflow
   - Commission audit trail
   - Payment webhook verification

5. **Data Protection:**
   - Encryption of sensitive fields
   - Soft deletes for audit
   - Secure token generation
   - PII protection

---

## 📊 BUSINESS LOGIC EXAMPLES

### Example 1: Order to Commission Flow

```php
// When order is paid (OrderController@store)
public function store(Request $request)
{
    // 1. Create order
    $order = Order::create([
        'user_id' => auth()->id(),
        'total' => $request->total,
        'status' => 'pending'
    ]);

    // 2. Process payment
    $payment = PaymentService::process($order, $request->payment_method);

    if ($payment->success) {
        // 3. Update order
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);

        // 4. Distribute commissions (automatic)
        CommissionService::distribute($order);

        // 5. Update sales statistics
        MlmService::updateSales($order->user_id, $order->total);
    }
}

// CommissionService::distribute()
public function distribute(Order $order)
{
    $buyer = $order->user;
    $settings = CommissionSetting::where('is_active', true)
        ->orderBy('level')
        ->get();

    foreach ($settings as $setting) {
        // Get ancestors at this level
        $ancestors = MlmGenealogy::where('user_id', $buyer->id)
            ->where('depth', $setting->level)
            ->with('ancestor')
            ->get();

        foreach ($ancestors as $genealogy) {
            $amount = $order->total * ($setting->percentage / 100);

            // Create commission
            Commission::create([
                'user_id' => $genealogy->ancestor_id,
                'order_id' => $order->id,
                'referrer_id' => $buyer->id,
                'type' => 'level_commission',
                'level' => $setting->level,
                'amount' => $amount,
                'percentage' => $setting->percentage,
                'status' => 'approved'
            ]);

            // Credit wallet
            WalletService::credit(
                $genealogy->ancestor->wallet->id,
                $amount,
                ['type' => 'commission', 'order_id' => $order->id]
            );
        }
    }
}
```

### Example 2: MLM Registration

```php
// MlmService::registerUser()
public function registerUser(User $user, $sponsorId = null)
{
    $sponsor = $sponsorId ? User::find($sponsorId) : null;

    // 1. Create MLM Network
    $network = MlmNetwork::create([
        'user_id' => $user->id,
        'sponsor_id' => $sponsor?->id,
        'level' => $sponsor ? $sponsor->mlm_level + 1 : 1,
        'path' => $this->generatePath($sponsor),
        'joined_at' => now()
    ]);

    // 2. Build genealogy
    if ($sponsor) {
        // Direct sponsor
        MlmGenealogy::create([
            'user_id' => $user->id,
            'ancestor_id' => $sponsor->id,
            'depth' => 1
        ]);

        // All ancestors of sponsor
        $sponsorAncestors = MlmGenealogy::where('user_id', $sponsor->id)->get();
        foreach ($sponsorAncestors as $ancestor) {
            MlmGenealogy::create([
                'user_id' => $user->id,
                'ancestor_id' => $ancestor->ancestor_id,
                'depth' => $ancestor->depth + 1
            ]);
        }

        // Update sponsor downline count
        $sponsor->mlmNetwork->increment('total_downline');
    }

    // 3. Update user
    $user->update([
        'mlm_level' => $network->level,
        'mlm_position' => $network->path
    ]);
}
```

---

## 🎨 FRONTEND COMPONENTS

### Dashboard Charts (Chart.js)
```javascript
// resources/js/charts.js
import Chart from 'chart.js/auto';

// Sales Trend Chart
const salesCtx = document.getElementById('salesChart');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'ยอดขาย (THB)',
            data: salesData,
            borderColor: 'rgb(79, 70, 229)',
            tension: 0.4
        }]
    }
});
```

### MLM Tree (D3.js)
```javascript
// resources/js/mlm-tree.js
import * as d3 from 'd3';
import { hierarchy, tree } from 'd3-hierarchy';

function renderTree(data) {
    const svg = d3.select('#mlm-tree')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    const root = hierarchy(data);
    const treeLayout = tree().size([height, width]);
    treeLayout(root);

    // Draw links
    svg.selectAll('.link')
        .data(root.links())
        .enter()
        .append('line')
        .attr('class', 'link');

    // Draw nodes
    svg.selectAll('.node')
        .data(root.descendants())
        .enter()
        .append('circle')
        .attr('class', 'node')
        .on('click', showNodeDetails);
}
```

### Theme Preview (Alpine.js)
```html
<!-- resources/views/vendor/theme.blade.php -->
<div x-data="themeCustomizer()">
    <div class="color-picker">
        <input type="color" x-model="primaryColor" @change="applyTheme">
        <input type="color" x-model="secondaryColor" @change="applyTheme">
    </div>

    <div class="preview" :style="`--primary: ${primaryColor}; --secondary: ${secondaryColor}`">
        <!-- Live preview -->
    </div>
</div>

<script>
function themeCustomizer() {
    return {
        primaryColor: '#4F46E5',
        secondaryColor: '#EC4899',
        applyTheme() {
            document.documentElement.style.setProperty('--primary', this.primaryColor);
            document.documentElement.style.setProperty('--secondary', this.secondaryColor);
        }
    }
}
</script>
```

---

## 🧪 TESTING REQUIREMENTS

### Unit Tests
```php
// tests/Unit/MlmServiceTest.php
public function test_commission_distribution()
{
    $sponsor = User::factory()->create();
    $user = User::factory()->create(['sponsor_id' => $sponsor->id]);

    MlmService::registerUser($user, $sponsor->id);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 1000,
        'payment_status' => 'paid'
    ]);

    CommissionService::distribute($order);

    // Assert sponsor received 10% commission
    $this->assertEquals(100, $sponsor->wallet->balance);
}
```

### Feature Tests
```php
// tests/Feature/CheckoutTest.php
public function test_checkout_with_wallet_payment()
{
    $user = User::factory()->create();
    $user->wallet->update(['balance' => 5000]);

    $response = $this->actingAs($user)
        ->post('/api/v1/orders', [
            'items' => [...],
            'payment_method' => 'wallet'
        ]);

    $response->assertStatus(201);
    $this->assertEquals(4000, $user->wallet->fresh()->balance);
}
```

---

## 📝 ADDITIONAL NOTES

### Commission Rates (ตามตัวอย่าง)
```
Level 1 (Direct Sponsor): 10%
Level 2 (Second Generation): 5%
Level 3 (Third Generation): 3%
```

### Marketplace Commission Split
```
Vendor: 70%
Admin Platform: 30%
```

### Withdrawal Settings
```
Minimum: 100 THB
Fee: max(10 THB, 2% of amount)
```

### Theme Subscription Pricing
```
Monthly: 299 THB/month
Yearly: 2,990 THB/year (17% savings)
Lifetime: 9,990 THB (one-time)
```

---

## 🎯 SUCCESS CRITERIA

ระบบถือว่าสมบูรณ์เมื่อ:

1. ✅ ผู้ใช้สามารถสมัคร/เข้าสู่ระบบได้
2. ✅ Vendor สามารถสร้างร้านค้า จัดการสินค้า รับออเดอร์
3. ✅ ลูกค้าสามารถซื้อสินค้า ชำระเงินผ่าน Stripe, PromptPay, Wallet
4. ✅ MLM system ทำงานอัตโนมัติ:
   - สมัคร referral link ถูกต้อง
   - Genealogy tracking ครบถ้วน
   - Commission auto-distribute เมื่อ order paid
   - Tree visualization แสดงผลถูกต้อง
5. ✅ Wallet system:
   - รับ commission อัตโนมัติ
   - ถอนเงินได้ (มี approval workflow)
   - Transaction history ครบถ้วน
6. ✅ Hotel system ใช้งานได้:
   - Search availability
   - Booking process
   - Check-in/out
   - Promotion codes
7. ✅ NFC payment ทำงานได้
8. ✅ Theme customization ใช้งานได้
9. ✅ Admin dashboard แสดง metrics ถูกต้อง
10. ✅ Deploy script ใช้งานได้ปกติ
11. ✅ GitHub integration สำหรับ version management
12. ✅ Security measures ครบถ้วน
13. ✅ All tests passing

---

## 🚨 IMPORTANT REMINDERS

1. **ใช้ Laravel 11 Best Practices:**
   - Service classes for business logic
   - Request validation classes
   - Resource classes for API responses
   - Database transactions for financial operations

2. **Security First:**
   - Validate ALL inputs
   - Use middleware for authorization
   - Log security events
   - Rate limit sensitive endpoints

3. **Performance:**
   - Index foreign keys
   - Cache frequent queries
   - Eager load relationships
   - Queue heavy operations

4. **Code Quality:**
   - Follow PSR-12 (Laravel Pint)
   - Write meaningful tests
   - Comment complex logic
   - Document API endpoints

5. **Deploy Script:**
   - **MUST keep deploy.sh exactly as is**
   - Main branch: `claude/Main`
   - Auto-migration on deploy
   - Permission handling
   - Error recovery mechanisms

6. **GitHub Integration:**
   - Version checking via GitHub API
   - Automated backups before updates
   - Migration logs
   - Changelog display

---

## 📚 REFERENCE DOCUMENTATION

Current System Analysis: `/tmp/codebase_analysis.md` (1449 lines)

Key Files to Reference:
- `app/Services/MLM/MlmService.php` - MLM logic
- `app/Services/Wallet/WalletService.php` - Wallet operations
- `app/Http/Controllers/Api/*` - API endpoints
- `database/migrations/*` - Database schema
- `routes/api.php` - API routing
- `deploy.sh` - Deployment automation

---

## ✨ FINAL CHECKLIST

Before considering the system complete:

- [ ] All 59 models created with relationships
- [ ] All 40 migrations executable
- [ ] MLM registration & commission distribution tested
- [ ] Payment gateways (Stripe, PromptPay, Wallet) working
- [ ] D3.js tree visualization rendering
- [ ] Hotel booking workflow end-to-end
- [ ] NFC payment processing
- [ ] Theme customization & preview
- [ ] Admin dashboard with all charts
- [ ] Vendor dashboard fully functional
- [ ] Withdrawal approval workflow
- [ ] Shop verification (KYC) process
- [ ] Backup & restore working
- [ ] Docker setup running
- [ ] deploy.sh script tested
- [ ] GitHub integration for versions
- [ ] All API endpoints documented
- [ ] Security measures implemented
- [ ] Tests written & passing
- [ ] .env.example complete

---

## 🎬 DEPLOYMENT INSTRUCTIONS

เมื่อพัฒนาเสร็จ:

```bash
# 1. Clone repository
git clone <repository-url>
cd thaiprompt-affiliate

# 2. Copy .env
cp .env.example .env
# Edit .env with production values

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. Generate key
php artisan key:generate

# 5. Run migrations
php artisan migrate --force
php artisan db:seed --force

# 6. Set permissions
chmod +x deploy.sh
chmod -R 775 storage bootstrap/cache

# 7. Link storage
php artisan storage:link

# 8. Deploy using script
./deploy.sh
```

---

**END OF SYSTEM RECREATION PROMPT**

---

## คำแนะนำการใช้ Prompt นี้

เมื่อต้องการสร้างระบบใหม่:
1. Copy prompt ทั้งหมดนี้
2. วางให้ AI (Claude, GPT-4, etc.)
3. เพิ่มคำสั่งเฉพาะ เช่น:
   - "เริ่มสร้าง Phase 1 ให้หน่อย"
   - "สร้าง MLM Service ตามที่ระบุ"
   - "สร้าง migrations ทั้งหมด"
4. AI จะสร้างระบบตาม spec นี้ได้ทันที

Prompt นี้รวม:
- ✅ Tech stack ครบถ้วน
- ✅ Database schema สมบูรณ์
- ✅ Business logic ชัดเจน
- ✅ Code examples พร้อมใช้
- ✅ API endpoints ครบ
- ✅ Deployment ready
- ✅ คงไว้ deploy.sh และ GitHub integration
