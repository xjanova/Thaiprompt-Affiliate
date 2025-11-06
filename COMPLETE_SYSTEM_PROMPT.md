# 🚀 Complete System Rebuild Prompt - ThaiPrompt Affiliate Marketplace

> **Use this comprehensive prompt to recreate the entire system from scratch in a new project**

---

## 📋 Executive Summary

Build a complete **Multi-Vendor Marketplace with MLM (Multi-Level Marketing) System** using modern web technologies. This system combines e-commerce, network marketing, hotel management, and extensive customization features into one unified platform.

---

## 🎯 Project Overview

### Project Name
**ThaiPrompt Affiliate Marketplace**

### Version
**1.2.0**

### Core Concept
A sophisticated multi-vendor marketplace that allows:
- Multiple vendors to sell products and services
- MLM network structure with commission distribution
- Hotel/resort booking and management
- Wallet system with multiple payment gateways
- Point of Sale (POS) for physical stores
- Extensive customization and theming capabilities

---

## 🛠️ Technology Stack

### Backend Framework
- **PHP**: 8.2+
- **Framework**: Laravel 11.x (latest stable)
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Cache & Queue**: Redis (optional, can use database driver)
- **Authentication**: Laravel Sanctum (API tokens)
- **Permissions**: Spatie Laravel Permission (role-based access control)

### Frontend Technologies
- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript Framework**: Alpine.js 3.x (lightweight, reactive)
- **Build Tool**: Vite 4.x
- **Template Engine**: Blade (Laravel's native)

### JavaScript Libraries
- **Chart.js 4.x**: Dashboard charts and analytics
- **GSAP 3.x**: Smooth animations
- **D3.js 7.x**: MLM tree visualization
- **SweetAlert2**: Beautiful notifications
- **Iconify**: Icon system
- **NFC Web API**: Contactless payment support

### PHP Packages (Composer)
```json
{
  "guzzlehttp/guzzle": "^7.8",
  "laravel/framework": "^11.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.9",
  "stripe/stripe-php": "^13.0",
  "intervention/image": "^3.0",
  "spatie/laravel-permission": "^6.0",
  "barryvdh/laravel-dompdf": "^2.2",
  "maatwebsite/excel": "^3.1"
}
```

### NPM Packages
```json
{
  "alpinejs": "^3.13.3",
  "chart.js": "^4.4.1",
  "gsap": "^3.12.5",
  "@iconify/iconify": "^3.1.1",
  "sweetalert2": "^11.10.5",
  "d3": "^7.8.5",
  "d3-hierarchy": "^3.1.2",
  "@tailwindcss/forms": "^0.5.7",
  "autoprefixer": "^10.4.16",
  "axios": "^1.6.0",
  "laravel-vite-plugin": "^0.8.0",
  "postcss": "^8.4.31",
  "tailwindcss": "^3.3.5",
  "vite": "^4.5.0"
}
```

---

## 🏗️ System Architecture

### Application Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin dashboard controllers
│   │   ├── Vendor/         # Vendor dashboard controllers
│   │   ├── Api/            # API controllers
│   │   └── Auth/           # Authentication controllers
│   ├── Middleware/         # Custom middleware
│   └── Requests/           # Form request validation
├── Models/                 # Eloquent models (28+ models)
├── Services/               # Business logic services
│   ├── MLM/               # MLM-specific services
│   ├── Payment/           # Payment gateway integrations
│   ├── Hotel/             # Hotel management services
│   ├── Wallet/            # Wallet transaction services
│   ├── NFC/               # NFC payment services
│   ├── Theme/             # Theme customization
│   ├── Verification/      # KYC verification
│   ├── Version/           # Version management
│   └── Backup/            # Backup services
├── Mail/                  # Email notifications
└── Providers/             # Service providers

database/
├── migrations/            # Database migrations (25+ files)
├── seeders/              # Database seeders
└── factories/            # Model factories

resources/
├── views/                # Blade templates (40+ files)
│   ├── admin/           # Admin views
│   ├── vendor/          # Vendor views
│   ├── customer/        # Customer views
│   └── layouts/         # Layout templates
├── js/                  # JavaScript files
└── css/                 # CSS files

public/
├── build/               # Compiled assets (Vite)
└── storage/             # Public storage (symlink)

routes/
├── web.php             # Web routes
├── api.php             # API routes
└── channels.php        # Broadcasting channels
```

---

## 💾 Complete Database Schema

### Core Tables (User & Vendor Management)

#### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    avatar_source ENUM('default', 'line', 'upload') DEFAULT 'default',
    referral_code VARCHAR(20) UNIQUE,
    referred_by BIGINT UNSIGNED NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    line_user_id VARCHAR(255) UNIQUE NULL,
    line_display_name VARCHAR(255) NULL,
    line_picture_url VARCHAR(255) NULL,
    is_line_kyc_verified BOOLEAN DEFAULT FALSE,
    line_kyc_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_referral_code (referral_code),
    INDEX idx_referred_by (referred_by)
);
```

#### vendors
```sql
CREATE TABLE vendors (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    shop_name VARCHAR(255) NOT NULL,
    shop_slug VARCHAR(255) UNIQUE NOT NULL,
    shop_logo VARCHAR(255),
    shop_banner VARCHAR(255),
    description TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Thailand',
    phone VARCHAR(20),
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending',
    verified_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_shop_slug (shop_slug)
);
```

### Product Management

#### categories
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id BIGINT UNSIGNED NULL,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

#### products
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    cost_price DECIMAL(10,2),
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    min_stock_alert INT DEFAULT 5,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    images JSON,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    views_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
);
```

### Order Management

#### orders
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    vendor_id BIGINT UNSIGNED NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('wallet', 'stripe', 'promptpay', 'cash', 'nfc') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address JSON,
    notes TEXT,
    paid_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status)
);
```

#### order_items
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
```

### MLM System

#### mlm_networks
```sql
CREATE TABLE mlm_networks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    sponsor_id BIGINT UNSIGNED NULL,
    position ENUM('left', 'right', 'center') NULL,
    level INT DEFAULT 1,
    total_downlines INT DEFAULT 0,
    direct_downlines INT DEFAULT 0,
    left_leg_volume DECIMAL(12,2) DEFAULT 0,
    right_leg_volume DECIMAL(12,2) DEFAULT 0,
    personal_sales DECIMAL(12,2) DEFAULT 0,
    team_sales DECIMAL(12,2) DEFAULT 0,
    rank_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    activated_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rank_id) REFERENCES mlm_ranks(id) ON DELETE SET NULL,
    INDEX idx_sponsor_id (sponsor_id),
    INDEX idx_level (level)
);
```

#### mlm_genealogy
```sql
CREATE TABLE mlm_genealogy (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    ancestor_id BIGINT UNSIGNED NOT NULL,
    depth INT NOT NULL,
    path VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ancestor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_ancestor (user_id, ancestor_id),
    INDEX idx_depth (depth)
);
```

#### mlm_ranks
```sql
CREATE TABLE mlm_ranks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    min_personal_sales DECIMAL(12,2) DEFAULT 0,
    min_team_sales DECIMAL(12,2) DEFAULT 0,
    min_direct_referrals INT DEFAULT 0,
    achievement_bonus DECIMAL(10,2) DEFAULT 0,
    monthly_bonus DECIMAL(10,2) DEFAULT 0,
    sort_order INT DEFAULT 0,
    icon VARCHAR(255),
    color VARCHAR(20),
    requirements JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### user_ranks
```sql
CREATE TABLE user_ranks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    rank_id BIGINT UNSIGNED NOT NULL,
    achieved_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rank_id) REFERENCES mlm_ranks(id) ON DELETE CASCADE,
    INDEX idx_user_rank (user_id, rank_id)
);
```

#### commissions
```sql
CREATE TABLE commissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    from_user_id BIGINT UNSIGNED NULL,
    type ENUM('level', 'rank_bonus', 'performance', 'matching') NOT NULL,
    level INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2),
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    description TEXT,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
);
```

#### commission_settings
```sql
CREATE TABLE commission_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    level INT NOT NULL UNIQUE,
    percentage DECIMAL(5,2) NOT NULL,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Wallet System

#### wallets
```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(12,2) DEFAULT 0.00,
    pending_balance DECIMAL(12,2) DEFAULT 0.00,
    total_earned DECIMAL(12,2) DEFAULT 0.00,
    total_withdrawn DECIMAL(12,2) DEFAULT 0.00,
    total_spent DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'THB',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

#### wallet_transactions
```sql
CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    wallet_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    source_type ENUM('commission', 'refund', 'deposit', 'withdrawal', 'purchase', 'transfer', 'bonus') NOT NULL,
    source_id VARCHAR(100),
    description TEXT,
    reference_number VARCHAR(50) UNIQUE,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_created_at (created_at)
);
```

#### withdrawals
```sql
CREATE TABLE withdrawals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    wallet_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0,
    net_amount DECIMAL(10,2) NOT NULL,
    method ENUM('bank_transfer', 'promptpay', 'check') NOT NULL,
    account_details JSON NOT NULL,
    status ENUM('pending', 'processing', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejected_reason TEXT,
    processed_at TIMESTAMP NULL,
    reference_number VARCHAR(50) UNIQUE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
);
```

### Shopping Cart

#### carts
```sql
CREATE TABLE carts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(255) NULL,
    coupon_code VARCHAR(50) NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
);
```

#### cart_items
```sql
CREATE TABLE cart_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id)
);
```

### POS System

#### pos_sessions
```sql
CREATE TABLE pos_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    session_number VARCHAR(50) UNIQUE NOT NULL,
    opening_balance DECIMAL(10,2) DEFAULT 0,
    closing_balance DECIMAL(10,2) NULL,
    total_sales DECIMAL(10,2) DEFAULT 0,
    total_transactions INT DEFAULT 0,
    status ENUM('open', 'closed') DEFAULT 'open',
    opened_at TIMESTAMP NOT NULL,
    closed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vendor_status (vendor_id, status)
);
```

#### pos_sales
```sql
CREATE TABLE pos_sales (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pos_session_id BIGINT UNSIGNED NOT NULL,
    sale_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'wallet', 'nfc') NOT NULL,
    payment_received DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (pos_session_id) REFERENCES pos_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (pos_session_id)
);
```

#### pos_sale_items
```sql
CREATE TABLE pos_sale_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pos_sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (pos_sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
```

### Review System

#### reviews
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product_approved (product_id, is_approved)
);
```

#### review_responses
```sql
CREATE TABLE review_responses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    review_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    is_vendor BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Marketing & Promotions

#### coupons
```sql
CREATE TABLE coupons (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_purchase_amount DECIMAL(10,2) DEFAULT 0,
    max_discount_amount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    usage_count INT DEFAULT 0,
    per_user_limit INT DEFAULT 1,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_code_active (code, is_active)
);
```

#### coupon_usage
```sql
CREATE TABLE coupon_usage (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    coupon_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
```

#### invitations
```sql
CREATE TABLE invitations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    phone VARCHAR(20),
    token VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
    message TEXT,
    accepted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_status (status)
);
```

### Hotel Management System

#### hotels
```sql
CREATE TABLE hotels (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('hotel', 'resort', 'hostel', 'apartment', 'guesthouse') NOT NULL,
    star_rating INT CHECK (star_rating >= 1 AND star_rating <= 5),
    description TEXT,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Thailand',
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '12:00:00',
    cancellation_policy TEXT,
    house_rules TEXT,
    amenities JSON,
    images JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_city (city)
);
```

#### room_types
```sql
CREATE TABLE room_types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    hotel_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    size_sqm DECIMAL(6,2),
    bed_type VARCHAR(100),
    max_adults INT NOT NULL DEFAULT 2,
    max_children INT DEFAULT 0,
    max_occupancy INT NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    weekend_price DECIMAL(10,2),
    extra_bed_charge DECIMAL(10,2) DEFAULT 0,
    extra_person_charge DECIMAL(10,2) DEFAULT 0,
    room_amenities JSON,
    images JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    INDEX idx_hotel_id (hotel_id)
);
```

#### rooms
```sql
CREATE TABLE rooms (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    room_type_id BIGINT UNSIGNED NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    floor INT,
    status ENUM('available', 'occupied', 'maintenance', 'cleaning') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_type_number (room_type_id, room_number),
    INDEX idx_status (status)
);
```

#### hotel_bookings
```sql
CREATE TABLE hotel_bookings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(50) UNIQUE NOT NULL,
    hotel_id BIGINT UNSIGNED NOT NULL,
    room_type_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    nights INT NOT NULL,
    rooms_count INT NOT NULL DEFAULT 1,
    adults INT NOT NULL,
    children INT DEFAULT 0,
    room_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    service_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('wallet', 'stripe', 'promptpay', 'cash') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    cancellation_reason TEXT,
    checked_in_at TIMESTAMP NULL,
    checked_out_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking_number (booking_number),
    INDEX idx_check_dates (check_in_date, check_out_date)
);
```

#### booking_guests
```sql
CREATE TABLE booking_guests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    hotel_booking_id BIGINT UNSIGNED NOT NULL,
    guest_name VARCHAR(255) NOT NULL,
    guest_email VARCHAR(255),
    guest_phone VARCHAR(20),
    id_card_number VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_booking_id) REFERENCES hotel_bookings(id) ON DELETE CASCADE
);
```

#### hotel_promotions
```sql
CREATE TABLE hotel_promotions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    hotel_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    type ENUM('percentage', 'fixed', 'stay_pay') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_nights INT DEFAULT 1,
    min_rooms INT DEFAULT 1,
    max_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    usage_count INT DEFAULT 0,
    blackout_dates JSON,
    valid_days_of_week JSON,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    INDEX idx_code (code)
);
```

#### hotel_promotion_usage
```sql
CREATE TABLE hotel_promotion_usage (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    hotel_promotion_id BIGINT UNSIGNED NOT NULL,
    hotel_booking_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_promotion_id) REFERENCES hotel_promotions(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_booking_id) REFERENCES hotel_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### room_pricing_rules
```sql
CREATE TABLE room_pricing_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    room_type_id BIGINT UNSIGNED NOT NULL,
    rule_type ENUM('seasonal', 'event', 'day_of_week', 'length_of_stay') NOT NULL,
    name VARCHAR(255) NOT NULL,
    adjustment_type ENUM('percentage', 'fixed') NOT NULL,
    adjustment_value DECIMAL(10,2) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    days_of_week JSON,
    min_nights INT NULL,
    priority INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
    INDEX idx_room_type_dates (room_type_id, start_date, end_date)
);
```

#### hotel_amenities
```sql
CREATE TABLE hotel_amenities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    category ENUM('general', 'room', 'bathroom', 'kitchen', 'entertainment', 'outdoor', 'business') NOT NULL,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### hotel_reviews
```sql
CREATE TABLE hotel_reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    hotel_id BIGINT UNSIGNED NOT NULL,
    hotel_booking_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    overall_rating DECIMAL(2,1) NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5),
    cleanliness_rating INT CHECK (cleanliness_rating >= 1 AND cleanliness_rating <= 5),
    service_rating INT CHECK (service_rating >= 1 AND service_rating <= 5),
    location_rating INT CHECK (location_rating >= 1 AND location_rating <= 5),
    amenities_rating INT CHECK (amenities_rating >= 1 AND amenities_rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    vendor_response TEXT,
    vendor_responded_at TIMESTAMP NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_booking_id) REFERENCES hotel_bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_hotel_approved (hotel_id, is_approved)
);
```

### Addon System

#### addons
```sql
CREATE TABLE addons (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    features JSON,
    price DECIMAL(10,2) NOT NULL,
    icon VARCHAR(255),
    category ENUM('hotel', 'theme', 'analytics', 'marketing', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### addon_purchases
```sql
CREATE TABLE addon_purchases (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    addon_id BIGINT UNSIGNED NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    payment_method ENUM('wallet', 'stripe', 'promptpay') NOT NULL,
    transaction_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    purchased_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES addons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_addon (vendor_id, addon_id)
);
```

### Theme Customization

#### store_themes
```sql
CREATE TABLE store_themes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category ENUM('fashion', 'hotel', 'electronics', 'food', 'general') NOT NULL,
    preview_image VARCHAR(255),
    is_free BOOLEAN DEFAULT TRUE,
    price DECIMAL(10,2) DEFAULT 0,
    config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### vendor_themes
```sql
CREATE TABLE vendor_themes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL UNIQUE,
    store_theme_id BIGINT UNSIGNED NULL,
    primary_color VARCHAR(20) DEFAULT '#4F46E5',
    secondary_color VARCHAR(20) DEFAULT '#EC4899',
    accent_color VARCHAR(20) DEFAULT '#F59E0B',
    heading_font VARCHAR(100) DEFAULT 'Inter',
    body_font VARCHAR(100) DEFAULT 'Inter',
    font_size INT DEFAULT 16,
    layout_style ENUM('grid', 'list', 'masonry') DEFAULT 'grid',
    products_per_row INT DEFAULT 4,
    show_sidebar BOOLEAN DEFAULT TRUE,
    header_style ENUM('default', 'centered', 'minimal') DEFAULT 'default',
    logo_url VARCHAR(255),
    favicon_url VARCHAR(255),
    mobile_logo_url VARCHAR(255),
    hero_banner VARCHAR(255),
    hero_title VARCHAR(255),
    hero_subtitle VARCHAR(255),
    hero_cta_text VARCHAR(100),
    hero_cta_url VARCHAR(255),
    social_facebook VARCHAR(255),
    social_instagram VARCHAR(255),
    social_twitter VARCHAR(255),
    social_line VARCHAR(255),
    social_youtube VARCHAR(255),
    custom_css TEXT,
    custom_js TEXT,
    footer_about TEXT,
    contact_widget_enabled BOOLEAN DEFAULT TRUE,
    contact_widget_position ENUM('bottom-right', 'bottom-left') DEFAULT 'bottom-right',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (store_theme_id) REFERENCES store_themes(id) ON DELETE SET NULL
);
```

### NFC Payment System

#### nfc_cards
```sql
CREATE TABLE nfc_cards (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    card_uid VARCHAR(255) UNIQUE NOT NULL,
    card_type ENUM('standard', 'premium', 'vip') DEFAULT 'standard',
    user_id BIGINT UNSIGNED NULL,
    wallet_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    issued_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL,
    INDEX idx_card_uid (card_uid)
);
```

#### nfc_card_transactions
```sql
CREATE TABLE nfc_card_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nfc_card_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('payment', 'balance_check', 'verification') NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('success', 'failed', 'pending') NOT NULL,
    error_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (nfc_card_id) REFERENCES nfc_cards(id) ON DELETE CASCADE,
    INDEX idx_card_created (nfc_card_id, created_at)
);
```

### Verification System

#### shop_verifications
```sql
CREATE TABLE shop_verifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL UNIQUE,
    verification_badge ENUM('bronze', 'silver', 'gold', 'platinum') NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    documents JSON,
    business_registration_number VARCHAR(100),
    tax_id VARCHAR(50),
    notes TEXT,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### System Management

#### app_settings
```sql
CREATE TABLE app_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    type ENUM('string', 'boolean', 'integer', 'json', 'file') DEFAULT 'string',
    group VARCHAR(100) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_group (group)
);
```

#### system_versions
```sql
CREATE TABLE system_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    release_date DATE NOT NULL,
    changelog JSON,
    is_critical BOOLEAN DEFAULT FALSE,
    download_url VARCHAR(255),
    installed_at TIMESTAMP NULL,
    installed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (installed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### backups
```sql
CREATE TABLE backups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('full', 'database', 'files') NOT NULL,
    file_path VARCHAR(255),
    file_size BIGINT,
    version VARCHAR(20),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    auto_backup BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### line_oa_configs
```sql
CREATE TABLE line_oa_configs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    channel_id VARCHAR(255) UNIQUE NOT NULL,
    channel_secret VARCHAR(255) NOT NULL,
    channel_access_token TEXT NOT NULL,
    webhook_url VARCHAR(255),
    require_kyc_for_withdrawal BOOLEAN DEFAULT TRUE,
    min_withdrawal_without_kyc DECIMAL(10,2) DEFAULT 1000.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### line_messages
```sql
CREATE TABLE line_messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    line_user_id VARCHAR(255) NOT NULL,
    message_type ENUM('text', 'image', 'video', 'audio', 'location', 'sticker') NOT NULL,
    message_content TEXT,
    is_from_user BOOLEAN DEFAULT TRUE,
    webhook_event_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_line_user (line_user_id)
);
```

---

## 🎨 Core Features Implementation

### 1. Multi-Vendor Marketplace

**Key Features:**
- Vendor registration with admin approval
- Shop management (name, logo, banner, description)
- Product catalog with categories
- Order management
- Commission tracking
- Vendor dashboard with analytics

**Implementation Notes:**
- Each vendor has unique shop_slug for custom URLs
- Vendors can only manage their own products
- Commission rates configurable per vendor
- Admin approval workflow for new vendors

### 2. MLM (Multi-Level Marketing) System

**Supported Structures:**
- **Unilevel**: Unlimited width, configurable depth
- **Binary**: Left/right legs with balance tracking

**Key Features:**
- Automatic genealogy tree building
- Multi-level commission distribution (configurable levels)
- Rank advancement system with bonuses
- Network visualization with D3.js
- Performance tracking (personal sales, team sales)

**Commission Types:**
- Level commission (e.g., L1: 10%, L2: 5%, L3: 3%)
- Rank achievement bonus (one-time)
- Performance bonus (monthly)
- Matching bonus (binary MLM)

**Implementation:**
```php
// MLM Service Methods
- registerUser(): Create MLM network entry
- buildGenealogy(): Build ancestor relationships
- distributeCommissions(): Calculate and distribute commissions
- checkRankUpgrade(): Check and upgrade user rank
- getNetworkStats(): Get network statistics
- getDownlines(): Get downline members
```

### 3. Wallet System

**Features:**
- Digital wallet for each user
- Multiple transaction types (commission, refund, deposit, withdrawal, purchase)
- Withdrawal requests with admin approval
- Transaction history
- Balance tracking (available, pending, total earned, total withdrawn)

**Payment Methods:**
- Wallet balance
- Stripe (credit/debit cards)
- PromptPay (Thai QR payment)
- Cash (for POS)
- NFC cards

**Implementation:**
```php
// Wallet Service Methods
- createWallet(): Create wallet for new user
- credit(): Add funds to wallet
- debit(): Deduct funds from wallet
- processWithdrawal(): Handle withdrawal request
- getTransactionHistory(): Get transaction log
```

### 4. Point of Sale (POS)

**Features:**
- Session management (open/close shift)
- Quick product search
- Multiple payment methods
- Receipt printing
- Cash drawer tracking
- Real-time inventory sync

**Workflow:**
1. Vendor opens POS session with opening balance
2. Create sales with products and quantities
3. Process payment (cash, card, wallet, NFC)
4. Print receipt
5. Close session with closing balance

### 5. Hotel Management System (Addon)

**Complete Hotel Operations:**
- Multi-property management
- Room types with pricing
- Individual room tracking
- Online booking system
- Dynamic pricing rules
- Promotion/discount system
- Guest management
- Check-in/check-out tracking
- Review and ratings

**Pricing Features:**
- Base price + weekend price
- Seasonal pricing rules
- Event-based pricing
- Length-of-stay discounts
- Stay 3 Pay 2 promotions
- Multiple promotion stacking

**Commission Integration:**
- Vendor receives configured commission (e.g., 70%)
- Platform takes service fee (e.g., 30%)
- MLM commissions distributed from platform share

### 6. Theme Customization (Addon)

**Store Personalization:**
- Pre-built theme templates
- Color customization (primary, secondary, accent)
- Typography settings (fonts, sizes)
- Layout options (grid, list, masonry)
- Hero banner with CTA
- Logo and branding
- Social media integration
- Custom CSS/JS support

**Template Categories:**
- Fashion boutiques
- Hotel/resort showcases
- Electronics stores
- Food and restaurants
- General purpose

### 7. NFC Payment System

**Features:**
- Web NFC API integration
- Card registration and linking
- Contactless payment processing
- Balance checking without payment
- Transaction logging
- Card type tiers (standard, premium, VIP)

**Supported Browsers:**
- Chrome 89+ (Android)
- Chrome 114+ (Desktop with flag)
- Edge 89+ (Android)

**Use Cases:**
- POS payments
- Quick checkout
- Loyalty programs
- Access control

### 8. Verification System

**Multi-Level KYC:**
- Bronze: ID card verification
- Silver: + Business registration + Tax certificate
- Gold: + Bank verification
- Platinum: + Business license

**Features:**
- Document upload (PDF, JPG, PNG)
- Admin review interface
- Approval/rejection workflow
- Badge display on shop profile
- Trust indicator for customers

### 9. Version Management System

**Automatic Updates:**
- GitHub release checking
- Version comparison
- Changelog display
- Update notifications
- Manual update process
- Version history tracking

**Features:**
- Semantic versioning (1.2.3)
- Critical update flagging
- Breaking changes warnings
- Rollback capability

### 10. Backup System

**Backup Types:**
- Full backup (files + database)
- Database only
- Files only

**Features:**
- Automatic pre-update backups
- Scheduled backups
- One-click restore
- Backup management UI
- Auto-cleanup of old backups
- Backup verification

---

## 🔧 Business Logic Services

Create these service classes in `app/Services/`:

### MLM/MlmService.php
```php
- registerUser(User $user, ?User $sponsor, $position = null)
- buildGenealogy(User $user)
- distributeCommissions(Order $order)
- calculateLevelCommissions(Order $order, User $buyer)
- checkRankUpgrade(User $user)
- getNetworkStats(User $user)
- getDownlines(User $user, $depth = null)
- getUplines(User $user, $depth = null)
- getGenealogy(User $user)
```

### Wallet/WalletService.php
```php
- createWallet(User $user)
- credit(Wallet $wallet, $amount, $sourceType, $description)
- debit(Wallet $wallet, $amount, $sourceType, $description)
- createWithdrawalRequest(User $user, $amount, $method, $accountDetails)
- approveWithdrawal(Withdrawal $withdrawal)
- rejectWithdrawal(Withdrawal $withdrawal, $reason)
- processWithdrawal(Withdrawal $withdrawal)
- getBalance(User $user)
- getTransactionHistory(User $user, $filters = [])
```

### Payment/StripeService.php
```php
- createPaymentIntent($amount, $currency = 'thb')
- confirmPayment($paymentIntentId)
- createCustomer(User $user)
- refundPayment($chargeId, $amount = null)
- handleWebhook($payload, $signature)
```

### Payment/PromptPayService.php
```php
- generateQRCode($amount, $reference)
- verifyPayment($reference)
- handleWebhook($payload)
```

### Hotel/HotelService.php
```php
- createHotel($vendorId, $data)
- createRoomType($hotelId, $data)
- bulkCreateRooms($roomTypeId, $count, $startNumber, $floor)
- checkAvailability($hotelId, $checkIn, $checkOut, $rooms)
- calculatePrice($roomTypeId, $checkIn, $checkOut, $rooms, $adults, $children, $promoCode)
- searchHotels($filters)
- getHotelStats($hotelId)
```

### Hotel/BookingService.php
```php
- createBooking($data)
- calculateBookingPrice($booking, $promoCode = null)
- processPayment($booking, $paymentMethod)
- calculateCommissions($booking)
- cancelBooking($booking, $reason)
- processRefund($booking)
- checkIn($booking)
- checkOut($booking)
- getVendorBookingStats($vendorId)
```

### NFC/NfcService.php
```php
- registerCard($cardUid, $cardType)
- linkCardToUser($cardId, $userId)
- processPayment($cardUid, $amount, $merchantId)
- checkBalance($cardUid)
- verifyCard($cardUid)
- deactivateCard($cardId)
- getCardInfo($cardUid)
- getCardTransactions($cardId)
```

### Theme/ThemeService.php
```php
- createDefaultTheme($vendorId)
- applyStoreTheme($vendorId, $themeId)
- customizeVendorTheme($vendorId, $customizations)
- resetTheme($vendorId)
- uploadLogo($vendorId, $file)
- uploadFavicon($vendorId, $file)
- uploadBanner($vendorId, $file)
- getVendorTheme($vendorId)
```

### Verification/VerificationService.php
```php
- submitVerification($vendorId, $documents, $businessInfo)
- reviewVerification($verificationId, $approved, $badge, $notes)
- calculateBadgeLevel($documents)
- uploadDocument($file, $type)
- getVerificationStatus($vendorId)
```

### Version/VersionService.php
```php
- checkForUpdates()
- compareVersions($current, $latest)
- getLatestRelease()
- markAsInstalled($version, $userId)
- getVersionHistory()
- isUpdateAvailable()
```

### Backup/BackupService.php
```php
- createBackup($type, $userId)
- createFullBackup()
- createDatabaseBackup()
- createFilesBackup()
- restoreBackup($backupId)
- deleteBackup($backupId)
- cleanOldBackups($keepLast = 10)
- verifyBackup($backupId)
- getBackupList()
```

---

## 🛣️ Routing Structure

### routes/web.php
```php
// Public routes
Route::get('/', [HomeController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/product/{slug}', [ProductController::class, 'show']);
Route::get('/shop/{slug}', [ShopController::class, 'show']);
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotel/{slug}', [HotelController::class, 'show']);

// Auth routes
Auth::routes(['verify' => true]);

// Customer routes
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index']);
    Route::resource('/orders', OrderController::class);
    Route::get('/cart', [CartController::class, 'index']);
    Route::get('/mlm/network', [MlmController::class, 'network']);
    Route::get('/mlm/tree', [MlmController::class, 'tree']);
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
});

// Vendor routes
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index']);
    Route::resource('/products', VendorProductController::class);
    Route::resource('/hotels', VendorHotelController::class);
    Route::get('/bookings', [VendorBookingController::class, 'index']);
    Route::get('/orders', [VendorOrderController::class, 'index']);
    Route::get('/addons', [VendorAddonController::class, 'index']);
    Route::post('/addons/purchase', [VendorAddonController::class, 'purchase']);
    Route::get('/theme', [VendorThemeController::class, 'edit']);
    Route::post('/theme', [VendorThemeController::class, 'update']);
    Route::get('/pos', [PosController::class, 'index']);
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::resource('/vendors', AdminVendorController::class);
    Route::resource('/users', AdminUserController::class);
    Route::resource('/products', AdminProductController::class);
    Route::get('/commissions', [AdminCommissionController::class, 'index']);
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index']);
    Route::post('/withdrawals/{id}/approve', [AdminWithdrawalController::class, 'approve']);
    Route::get('/verifications', [AdminVerificationController::class, 'index']);
    Route::post('/verifications/{id}/review', [AdminVerificationController::class, 'review']);
    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::post('/settings', [AdminSettingController::class, 'update']);
    Route::get('/backups', [BackupController::class, 'index']);
    Route::post('/backups', [BackupController::class, 'create']);
});
```

### routes/api.php
```php
Route::prefix('v1')->group(function () {
    // Public API
    Route::get('/products', [Api\ProductController::class, 'index']);
    Route::get('/products/{id}', [Api\ProductController::class, 'show']);
    Route::get('/hotels', [Api\HotelController::class, 'index']);
    Route::get('/hotels/{id}/availability', [Api\HotelController::class, 'availability']);

    // Auth API
    Route::post('/register', [Api\AuthController::class, 'register']);
    Route::post('/login', [Api\AuthController::class, 'login']);

    // Protected API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [Api\AuthController::class, 'logout']);
        Route::get('/user', [Api\AuthController::class, 'user']);

        // Cart
        Route::get('/cart', [Api\CartController::class, 'index']);
        Route::post('/cart/add', [Api\CartController::class, 'add']);

        // Orders
        Route::get('/orders', [Api\OrderController::class, 'index']);
        Route::post('/orders', [Api\OrderController::class, 'store']);

        // MLM
        Route::get('/mlm/stats', [Api\MlmController::class, 'stats']);
        Route::get('/mlm/network', [Api\MlmController::class, 'network']);
        Route::get('/mlm/commissions', [Api\MlmController::class, 'commissions']);

        // Wallet
        Route::get('/wallet', [Api\WalletController::class, 'index']);
        Route::get('/wallet/transactions', [Api\WalletController::class, 'transactions']);
        Route::post('/wallet/withdraw', [Api\WalletController::class, 'withdraw']);

        // Hotel Booking
        Route::post('/bookings', [Api\BookingController::class, 'store']);
        Route::post('/bookings/calculate-price', [Api\BookingController::class, 'calculatePrice']);

        // NFC
        Route::post('/nfc/process', [Api\NfcController::class, 'process']);
        Route::post('/nfc/check-balance', [Api\NfcController::class, 'checkBalance']);
    });
});
```

---

## 🎨 Frontend Views Structure

Create Blade templates in `resources/views/`:

### layouts/
- app.blade.php (main layout)
- admin.blade.php (admin layout)
- vendor.blade.php (vendor layout)
- guest.blade.php (guest layout)

### components/
- navbar.blade.php
- footer.blade.php
- sidebar.blade.php
- alert.blade.php
- pagination.blade.php

### home/
- index.blade.php (homepage)
- about.blade.php
- contact.blade.php

### products/
- index.blade.php (product listing)
- show.blade.php (product details)
- search.blade.php

### cart/
- index.blade.php

### checkout/
- index.blade.php
- success.blade.php

### customer/
- dashboard.blade.php
- orders/index.blade.php
- orders/show.blade.php
- wallet.blade.php
- profile.blade.php

### mlm/
- network.blade.php
- tree.blade.php (D3.js tree)
- commissions.blade.php
- invite.blade.php

### vendor/
- dashboard.blade.php
- products/index.blade.php
- products/create.blade.php
- products/edit.blade.php
- orders/index.blade.php
- hotels/index.blade.php
- hotels/create.blade.php
- bookings/index.blade.php
- theme/edit.blade.php
- addons/index.blade.php
- pos/index.blade.php

### admin/
- dashboard.blade.php
- vendors/index.blade.php
- users/index.blade.php
- products/index.blade.php
- commissions/index.blade.php
- withdrawals/index.blade.php
- verifications/index.blade.php
- settings/index.blade.php
- backups/index.blade.php

### hotels/
- index.blade.php (search hotels)
- show.blade.php (hotel details)
- booking.blade.php

---

## 🚀 Deployment & GitHub Integration

### deploy.sh Script Features

**IMPORTANT: Keep the existing deploy.sh structure with these capabilities:**

1. **Maintenance Mode**
   - Enable before deployment
   - Disable after completion

2. **Git Operations**
   - Pull from specific branch (claude/Main or configurable)
   - Handle merge conflicts
   - Stash local changes if needed

3. **Dependency Management**
   - Composer install with error handling
   - NPM install with fallback solutions
   - Permission fixes for vendor/ and node_modules/

4. **Asset Building**
   - npm run build
   - Clean old build directory
   - Set correct permissions

5. **Database Migrations**
   - Run migrations with --force
   - Handle existing tables gracefully
   - Log migration output

6. **Optimization**
   - Clear all caches
   - Cache config, routes, views, events
   - Optimize autoloader

7. **Permission Management**
   - Set ownership to web server user
   - Fix storage/ and bootstrap/cache/ permissions
   - Handle public/build/ permissions

8. **Service Management**
   - Restart PHP-FPM
   - Restart queue workers (if using supervisor)
   - Reload services gracefully

**Deploy Script Usage:**
```bash
# On production server
cd /path/to/project
./deploy.sh
```

### GitHub Integration Features

1. **Branch Strategy**
   - Main branch: `claude/Main` (or configurable)
   - Feature branches: `claude/feature-name-sessionid`
   - Deploy from Main branch only

2. **Webhook Support** (optional)
   - GitHub webhook for auto-deployment
   - Verify webhook signature
   - Trigger deploy.sh on push to Main

3. **CI/CD Ready**
   - GitHub Actions compatible
   - Automated testing on PR
   - Deployment on merge to Main

---

## 📝 Environment Configuration

### .env Configuration
```env
# App Settings
APP_NAME="ThaiPrompt Affiliate Marketplace"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe Payment
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PromptPay
PROMPTPAY_MERCHANT_ID=your_merchant_id
PROMPTPAY_MERCHANT_NAME="Your Business"
PROMPTPAY_API_KEY=your_api_key

# LINE Official Account
LINE_CHANNEL_ID=your_channel_id
LINE_CHANNEL_SECRET=your_channel_secret
LINE_CHANNEL_ACCESS_TOKEN=your_access_token
LINE_REQUIRE_KYC_FOR_WITHDRAWAL=true
LINE_MIN_WITHDRAWAL_WITHOUT_KYC=1000

# MLM Settings
MLM_TYPE=unilevel
MLM_MAX_DEPTH=10
COMMISSION_RATE_LEVEL_1=10
COMMISSION_RATE_LEVEL_2=5
COMMISSION_RATE_LEVEL_3=3
COMMISSION_RATE_LEVEL_4=2
COMMISSION_RATE_LEVEL_5=1

# NFC Configuration
NFC_ENABLED=true
NFC_TIMEOUT=30

# Version Management
GITHUB_REPO=xjanova/Thaiprompt-Affiliate
VERSION_CHECK_INTERVAL=24
VERSION_AUTO_CHECK=true

# Verification
VERIFICATION_ENABLED=true
VERIFICATION_DOCUMENT_DISK=public
MAX_DOCUMENT_SIZE=5120

# Backup
BACKUP_DISK=local
BACKUP_AUTO_CLEANUP=true
BACKUP_KEEP_LAST=10

# File Storage
FILESYSTEM_DISK=public
```

---

## 🔒 Security Considerations

### Authentication & Authorization
- Use Laravel Sanctum for API authentication
- Implement Spatie Permission for role-based access
- Hash passwords with bcrypt
- Email verification required
- 2FA optional (can add later)

### Data Protection
- CSRF protection on all forms
- SQL injection prevention (use Eloquent ORM)
- XSS protection (Blade auto-escaping)
- File upload validation
- Rate limiting on API endpoints

### Payment Security
- PCI DSS compliance (use Stripe for card processing)
- Never store full card numbers
- Encrypt sensitive data
- Secure webhook endpoints with signature verification

### MLM Security
- Prevent circular references
- Validate sponsor relationships
- Lock genealogy once built
- Audit all commission calculations

---

## 📊 Seeding Data

### Database Seeders

Create seeders in `database/seeders/`:

1. **RoleAndPermissionSeeder**
   - Admin role with all permissions
   - Vendor role with vendor permissions
   - Customer role with customer permissions

2. **AdminUserSeeder**
   - Create default admin user
   - Email: admin@example.com
   - Password: password

3. **MlmRankSeeder**
   - Bronze: min 0 THB personal sales
   - Silver: min 10,000 THB personal sales
   - Gold: min 50,000 THB personal sales
   - Platinum: min 200,000 THB personal sales
   - Diamond: min 1,000,000 THB personal sales

4. **CommissionSettingSeeder**
   - Level 1: 10%
   - Level 2: 5%
   - Level 3: 3%
   - Level 4: 2%
   - Level 5: 1%

5. **CategorySeeder**
   - Electronics
   - Fashion
   - Home & Garden
   - Beauty & Health
   - Sports & Outdoors
   - Accommodation

6. **HotelAmenitySeeder**
   - Free WiFi
   - Swimming Pool
   - Fitness Center
   - Restaurant
   - Room Service
   - etc.

7. **AddonSeeder**
   - Hotel Management System (4,999 THB)
   - Store Theme Customization (1,999 THB)
   - Advanced Analytics (999 THB)
   - Email Marketing (1,499 THB)

8. **StoreThemeSeeder**
   - Modern Minimalist (Free)
   - Fashion Boutique (999 THB)
   - Hotel Paradise (1,499 THB)
   - Electronics Hub (799 THB)
   - Food & Restaurant (Free)

---

## 🎯 Development Workflow

### Step-by-Step Implementation

1. **Phase 1: Foundation** (Week 1-2)
   - Set up Laravel 11 project
   - Install dependencies
   - Configure environment
   - Set up database
   - Create migrations
   - Implement authentication
   - Set up roles and permissions

2. **Phase 2: Core Features** (Week 3-4)
   - User management
   - Vendor registration and management
   - Product catalog
   - Shopping cart
   - Order processing
   - Basic dashboard

3. **Phase 3: MLM System** (Week 5-6)
   - MLM network structure
   - Genealogy building
   - Commission calculation
   - Rank system
   - Network visualization

4. **Phase 4: Wallet & Payments** (Week 7-8)
   - Wallet system
   - Stripe integration
   - PromptPay integration
   - Withdrawal system
   - Transaction history

5. **Phase 5: POS System** (Week 9)
   - POS session management
   - Quick sale interface
   - Receipt printing
   - Inventory sync

6. **Phase 6: Hotel Management** (Week 10-11)
   - Hotel CRUD
   - Room management
   - Booking system
   - Pricing rules
   - Promotions

7. **Phase 7: Advanced Features** (Week 12-13)
   - Theme customization
   - NFC payment
   - Verification system
   - Version management
   - Backup system

8. **Phase 8: Frontend & UX** (Week 14-15)
   - Design all Blade templates
   - Implement Tailwind CSS
   - Add Alpine.js interactivity
   - Dashboard charts (Chart.js)
   - MLM tree visualization (D3.js)
   - GSAP animations

9. **Phase 9: Testing & Optimization** (Week 16)
   - Unit tests
   - Feature tests
   - Performance optimization
   - Security audit
   - Bug fixes

10. **Phase 10: Deployment** (Week 17)
    - Production server setup
    - Deploy script testing
    - GitHub integration
    - Documentation
    - Launch

---

## 📚 Additional Documentation to Create

After building the system, create these documentation files:

1. **README.md** - Project overview and quick start
2. **INSTALLATION_GUIDE.md** - Detailed installation instructions
3. **CONFIGURATION.md** - All configuration options explained
4. **API_DOCUMENTATION.md** - Complete API reference
5. **DEPLOYMENT.md** - Production deployment guide
6. **FEATURES.md** - Feature documentation
7. **TROUBLESHOOTING.md** - Common issues and solutions
8. **CHANGELOG.md** - Version history

---

## 🎨 UI/UX Design Guidelines

### Color Scheme
- Primary: Indigo (#4F46E5)
- Secondary: Pink (#EC4899)
- Accent: Amber (#F59E0B)
- Success: Green (#10B981)
- Warning: Yellow (#F59E0B)
- Danger: Red (#EF4444)

### Typography
- Headings: Inter, Kanit (for Thai)
- Body: Inter, Sarabun (for Thai)
- Font sizes: 12px, 14px, 16px, 18px, 20px, 24px, 32px, 48px

### Components
- Use Tailwind CSS utilities
- Consistent spacing (4px, 8px, 12px, 16px, 24px, 32px, 48px)
- Rounded corners (rounded-md, rounded-lg)
- Shadows (shadow-sm, shadow, shadow-lg)
- Transitions (duration-200, duration-300)

### Responsive Design
- Mobile-first approach
- Breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px), 2xl (1536px)

---

## 🚦 Testing Strategy

### Unit Tests
- MLM Service tests
- Wallet Service tests
- Commission calculation tests
- Price calculation tests

### Feature Tests
- Authentication flow
- Product CRUD operations
- Order processing
- MLM network building
- Wallet transactions
- Hotel booking flow

### Browser Tests (Optional)
- Use Laravel Dusk for E2E testing
- Test critical user flows
- Test across different browsers

---

## 🔧 Performance Optimization

### Database Optimization
- Index foreign keys
- Index frequently queried columns
- Use eager loading for relationships
- Cache expensive queries

### Caching Strategy
- Cache product listings
- Cache MLM network data
- Cache commission rates
- Cache app settings
- Use Redis for session and cache

### Asset Optimization
- Minify CSS and JS (Vite handles this)
- Lazy load images
- Use CDN for assets (optional)
- Compress images

### Code Optimization
- Use queue jobs for heavy tasks
- Optimize database queries
- Use chunking for large datasets
- Implement pagination

---

## 📋 Final Checklist

Before going live:

- [ ] All migrations run successfully
- [ ] All seeders create correct data
- [ ] Authentication works (login, register, password reset)
- [ ] All roles and permissions configured
- [ ] MLM network builds correctly
- [ ] Commissions calculate accurately
- [ ] All payment methods work
- [ ] Emails send correctly
- [ ] Deploy script works without errors
- [ ] All tests pass
- [ ] Documentation is complete
- [ ] Security audit completed
- [ ] Performance optimized
- [ ] SSL certificate installed
- [ ] Backup system configured
- [ ] Monitoring set up
- [ ] Error tracking configured (e.g., Sentry)

---

## 🎯 Success Criteria

The system is complete when:

1. ✅ All 24+ features are implemented and working
2. ✅ Database has 40+ tables properly migrated
3. ✅ API has 50+ endpoints fully functional
4. ✅ Frontend has 40+ Blade templates
5. ✅ MLM network builds and calculates commissions correctly
6. ✅ All payment gateways process transactions
7. ✅ Hotel booking system works end-to-end
8. ✅ Theme customization allows full personalization
9. ✅ Deploy script deploys without manual intervention
10. ✅ System passes all tests and security audits

---

## 📞 Support & Maintenance

After deployment:

1. Monitor error logs daily
2. Check queue workers status
3. Review failed jobs
4. Monitor database performance
5. Check backup success
6. Review security updates
7. Update dependencies monthly
8. Respond to user feedback
9. Plan feature enhancements
10. Maintain documentation

---

## 🎉 Conclusion

This comprehensive prompt provides everything needed to rebuild the ThaiPrompt Affiliate Marketplace system from scratch. Follow the technology stack, implement the database schema, create the business logic services, build the frontend views, and use the deploy.sh script for deployments.

**Key Principles:**
- **Modularity**: Keep services separate and focused
- **Security**: Never compromise on security
- **Scalability**: Design for growth from day one
- **Maintainability**: Write clean, documented code
- **User Experience**: Prioritize UX in every feature
- **Performance**: Optimize from the start

**Remember:**
- Keep the deploy.sh script structure intact
- Maintain GitHub integration workflow
- Follow Laravel best practices
- Test thoroughly before deployment
- Document everything

Good luck building the next generation ThaiPrompt Affiliate Marketplace! 🚀
