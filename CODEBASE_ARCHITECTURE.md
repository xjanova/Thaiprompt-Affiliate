# Thaiprompt-Affiliate: Complete Codebase Architecture Overview

**Version:** 1.92.0  
**Framework:** Laravel 11 + Vite + Tailwind CSS + Alpine.js  
**Database:** MySQL/MariaDB with 95+ migrations  
**API:** REST API with Laravel Sanctum authentication  
**Target:** MLM/Affiliate System with E-Commerce Integration

---

## 📋 Table of Contents
1. [Project Structure](#project-structure)
2. [Technology Stack](#technology-stack)
3. [Database Architecture](#database-architecture)
4. [Affiliate & MLM System](#affiliate--mlm-system)
5. [E-Commerce Integration](#e-commerce-integration)
6. [Admin Panel Structure](#admin-panel-structure)
7. [API Routes & Backend](#api-routes--backend)
8. [Frontend Framework](#frontend-framework)
9. [Key Services & Business Logic](#key-services--business-logic)
10. [Authentication & Security](#authentication--security)

---

## 📁 Project Structure

```
/home/user/Thaiprompt-Affiliate/
├── app/                          # Laravel application code
│   ├── Console/                  # Artisan commands
│   ├── Exceptions/               # Custom exceptions
│   ├── Helpers/                  # Helper functions (SeoHelper)
│   ├── Http/
│   │   ├── Controllers/          # 73+ controllers organized by scope
│   │   │   ├── Admin/            # 40+ admin panel controllers
│   │   │   ├── Api/              # API v1 controllers
│   │   │   ├── Auth/             # Authentication (Login, Register, LINE)
│   │   │   ├── Frontend/         # Public-facing pages
│   │   │   ├── Seller/           # E-commerce seller functionality
│   │   │   └── User/             # User dashboard routes
│   │   └── Middleware/           # Custom middleware
│   ├── Jobs/                     # Queue jobs
│   ├── Models/                   # 105+ Eloquent models
│   ├── Observers/                # Model observers
│   ├── Notifications/            # Notification classes
│   ├── Providers/                # Service providers
│   └── Services/                 # 30+ business logic services
├── database/
│   ├── migrations/               # 95+ database migrations
│   ├── seeders/                  # Database seeders
│   └── schema_snapshot.example.json
├── routes/
│   ├── admin.php                 # Admin panel routes
│   ├── api.php                   # REST API routes
│   ├── web.php                   # Web application routes
│   ├── seller.php                # E-commerce seller routes
│   ├── user.php                  # User dashboard routes
│   └── console.php
├── resources/
│   ├── views/                    # Blade templates (100+ files)
│   │   ├── admin/                # Admin panel views
│   │   ├── auth/                 # Authentication templates
│   │   ├── layouts/              # Layout templates
│   │   ├── frontend/             # Public pages
│   │   ├── user/                 # User dashboard
│   │   ├── seller/               # Seller dashboard
│   │   └── components/           # Reusable components
│   └── js/                       # JavaScript assets
├── config/                       # Laravel configuration
├── public/                       # Public assets
├── storage/                      # File storage
└── package.json                  # Node dependencies
```

---

## 🛠 Technology Stack

### Backend
- **Framework:** Laravel 11.0
- **PHP:** 8.1+
- **ORM:** Eloquent
- **Authentication:** Laravel Sanctum (API tokens)
- **Database:** MySQL 8.0+
- **APIs & Integrations:**
  - Google Cloud Translate API (Multi-language support)
  - LINE Official Account Integration
  - Payment Gateway Support (PromptPay, Bank Transfer, Credit Card, COD)
  - File Upload with Image Processing (Intervention Image)

### Frontend
- **Build Tool:** Vite 5.0
- **CSS Framework:** Tailwind CSS 3.4
- **JS Framework:** Alpine.js 3.13.5
- **Visualization:** D3.js 7.9, Chart.js 4.4.1, vis-network 10.0.2
- **Animation:** GSAP 3.12.5

### Developer Tools
- **Linting:** Laravel Pint
- **Testing:** PHPUnit 11
- **Monitoring:** Faker, Mockery

---

## 💾 Database Architecture

### Core Affiliate System Models (25+ Models)

#### **Users & Authentication**
- `User` - Main user entity with RBAC system
- `OtpVerification`, `OtpSetting` - OTP authentication
- `LineLoginLog` - LINE account linking history
- `LineOaSetting` - LINE OA configuration
- `EmailPreference` - User email settings

#### **Affiliate System**
- `Affiliate` - Core affiliate entity with parent-child relationships
  - Fields: `parent_id`, `referral_code`, `level`, `total_referrals`, `total_earnings`
  - Supports hierarchical MLM structure
- `Commission` - Commission tracking
  - Fields: Types (direct, indirect, bonus), Status (pending, approved, rejected, paid)
  - Timestamped (approved_at, paid_at, rejected_at)
- `RankPromotion` - Track user rank transitions
- `UserRankProgress` - Monitor progress toward next rank

#### **Ranking & Achievement System**
- `Rank` - Define rank levels with requirements
  - Fields: `name`, `level`, `commission_rate`, `bonus_multiplier`, `is_default`
  - Multi-language support (`name_th`, `description_th`)
- `RankRequirement` - Configurable rank criteria
  - Types: points, referrals, sales, active_referrals, team_sales, consecutive_months
- `RankBonus` - Rank-based incentives
  - Types: one_time, monthly, commission, multiplier
- `RankSetting` - System-wide rank configuration
- `UserRankProgress` - Track individual rank advancement

#### **Wallet & Financial System**
- `Wallet` - User wallet with PIN protection & 2FA
  - Fields: `balance`, `currency`, `wallet_address`, `status`, `locked_until`
- `WalletTransaction` - Detailed transaction history
  - Types: deposit, withdrawal, transfer, commission, bonus
  - Includes IP/User-Agent for security
- `WalletLog` - Action audit trail
- `WalletSetting` - Wallet system configuration
- `WithdrawalRequest` - Withdrawal management workflow
- `PaymentMethod` - Multiple payment method storage
- `PaymentTransaction` - Payment processing records

#### **E-Commerce Products & Orders**
- `Product` - Product catalog
  - Features: Variants, Commission rate, Rental linking
  - Inventory tracking with low stock threshold
  - SEO fields (meta_title, meta_description)
  - Stats: view_count, sales_count, rating_average
- `ProductCategory` - Product categorization
- `ProductImage` - Product image management
- `ProductReview` - Customer reviews
- `Order` - Order management
  - Status: pending, paid, processing, shipped, delivered, completed, cancelled, refunded
  - Financial tracking: subtotal, discount, shipping_fee, tax, total
  - Commission split: platform_commission, seller_earning
- `OrderItem` - Individual items in orders
- `ShippingAddress` - Delivery addresses

### Learning & Content System (10+ Models)
- `LearningCategory` - Course/learning categories
- `LearningArticle` - Educational content with prerequisites
- `UserArticleProgress` - Learning progress tracking
- `ArticlePermission` - Content access control
- `KnowledgeBase`, `KnowledgeChunk` - Knowledge base management
- `RagUsageLog` - RAG (Retrieval-Augmented Generation) logging

### AI & Bot Marketplace (20+ Models)
- `AiProvider`, `AiModel` - AI provider configuration
- `AiBotProfile` - Bot marketplace listings
- `AiBotRental` - Bot rental transactions
- `AiInstallationLog` - Installation audit trail
- `AiConversation`, `AiMessage` - Chat history
- `AiUsageLog` - Usage analytics with status tracking
- `OwnerEarning` - Bot owner revenue tracking

### LINE Integration (12+ Models)
- `LineBotAiSetting` - LINE Bot AI configuration
- `LineBotKnowledgeBase` - Knowledge base for LINE bot
- `LineBotConversation`, `LineBotMessage` - Chat history
- `LineFlexMessageTemplate` - Flex message templates
- `LineRichMenu` - Rich menu configuration
- `LineChatWidgetSetting` - Widget customization
- `LineAvatar` - Bot avatar management
- `LineBroadcastMessage` - Broadcast messaging

### Notification & Communication (8+ Models)
- `Notification` - In-app notifications with scheduling
- `NotificationTemplate` - Notification templates
- `EmailLog` - Email delivery tracking
- `EmailTemplate` - Email templates
- `EmailProvider` - Email service configuration

### Security & Monitoring (6+ Models)
- `SecurityLog` - Comprehensive activity logging
- `BlockedIp` - IP blocking with CIDR support
- `ThreatIp` - Threat intelligence data
- `AiInstallationLog` - Installation security audit

### Membership & Settings (10+ Models)
- `MembershipRetentionStatus` - Membership tracking
- `MembershipRetentionHistory` - Renewal history
- `MembershipRetentionTransaction` - Financial records
- `Setting` - Global application settings
- `LanguageSetting` - Multi-language configuration
- `TranslationMapping` - Custom translation overrides
- `MenuItem` - Navigation menu items
- `Page`, `PageSection`, `PageTemplate` - CMS pages

---

## 🎯 Affiliate & MLM System

### Architecture Overview

```
User (Affiliate)
  ├── Affiliate (parent-child relationship)
  │   ├── parent_id → creates hierarchical tree
  │   ├── level → depth tracking
  │   ├── total_referrals → count of direct referrals
  │   └── total_earnings → cumulative earnings
  ├── Commission (multi-tier support)
  │   ├── direct → 1st level commission
  │   ├── indirect → multiple levels
  │   └── bonus → special incentives
  └── Rank System
      ├── current_rank_id → active rank
      ├── rank_points → progression metric
      └── RankPromotion → transition history
```

### Affiliate Features

#### 1. **Referral System**
- Automatic referral code generation (unique, 8-character alphanumeric)
- Multi-level hierarchy support (unlimited depth)
- Parent-child relationships tracked via `Affiliate::parent_id`

#### 2. **Commission Management**
```php
Commission Types:
- direct: Commission from direct referrals
- indirect: Commission from deeper levels
- bonus: Special incentive commissions

Commission Status Workflow:
pending → approved → paid
         └→ rejected
```

- Configurable commission rates per rank
- Approval workflow (manual/auto)
- Rejection with notes capability
- Timestamp tracking for all state changes

#### 3. **Rank System** (Advanced MLM Feature)
```
Rank Hierarchy:
- Each rank has multiple requirements (AND logic)
- Requirements types:
  * points: Accumulated rank points
  * referrals: Total direct referrals
  * sales: Total personal sales amount
  * active_referrals: Active direct referrals only
  * team_sales: Team-wide sales total
  * consecutive_months: Continuous membership

Rank Bonuses:
- one_time: Single payment bonus
- monthly: Recurring monthly bonus
- commission: Commission rate multiplier
- multiplier: Earnings multiplier bonus
```

#### 4. **Auto-Promotion System**
- Eligibility checking engine in `RankingService`
- Manual approval option (configurable)
- Progress tracking via `UserRankProgress`
- Bilingual support (English, Thai)

#### 5. **Network Tree Visualization**
- Interactive tree view (`affiliates/tree-interactive`)
- Admin can view entire network structure
- Move/reorganize affiliate positions
- D3.js/vis-network powered visualization

---

## 🛒 E-Commerce Integration

### E-Commerce Module Structure

#### **Product Management**
```
Product Entity:
├── Basic Info
│   ├── name, slug, sku (unique)
│   ├── description, short_description
│   └── brand
├── Pricing
│   ├── price (selling price)
│   ├── compare_at_price (original price for discounts)
│   └── cost_price (for profit margin calculation)
├── Inventory
│   ├── stock_quantity
│   ├── low_stock_threshold
│   ├── track_inventory (boolean)
│   └── stock_status (in_stock, out_of_stock, on_backorder)
├── Marketing
│   ├── is_active, is_featured
│   ├── published_at
│   ├── meta_title, meta_description
│   └── tags (json)
├── Commission
│   ├── commission_rate (% to platform)
│   └── linked_bot_rental_id (AI bot requirement)
├── Variants
│   ├── has_variants (boolean)
│   ├── parent_product_id (for variants)
│   └── attributes (json: color, size, etc.)
└── Stats
    ├── view_count, sales_count
    ├── rating_average, rating_count
    └── images (ProductImage relationship)
```

#### **Order Processing**
```
Order Status Workflow:
pending → paid → processing → shipped → delivered → completed
                                   └→ cancelled (refund)

Order Financial Split:
total_amount = subtotal + shipping_fee + tax - discount
platform_commission + seller_earning = total_amount (after fees)

Payment Methods Supported:
- promptpay (QR Code)
- bank_transfer
- credit_card
- cash_on_delivery (COD)
```

#### **Seller Dashboard**
Routes: `/seller/*`
- Product management (CRUD, toggle status, update stock)
- Order management with shipping tracking
- Sales analytics and performance
- Profile management
- Notification center

#### **Seller Controllers**
- `ProductController` - Product CRUD, variants, images
- `OrderManagementController` - Order processing, shipping
- `DashboardController` - Analytics and metrics

#### **Admin E-Commerce Management**
- `ECommerceController` - Dashboard with 15+ statistics
  - Revenue tracking (monthly trends)
  - Product performance metrics
  - Stock management
  - Order status breakdown
  - Top selling products
  - Customer analytics

---

## 👨‍💼 Admin Panel Structure

### Admin Routes & Controllers (40+ Controllers)

```
Admin URL Structure: /admin/*
Protected: All admin routes require authentication + permissions

Core Admin Modules:
├── Dashboard
├── User Management
├── Affiliate Management
├── Commission Management
├── Wallet Management
├── E-Commerce
├── Payment Processing
├── Security & Monitoring
├── Content Management
├── Settings & Configuration
├── Marketing & Notifications
├── AI & Bot Management
└── LINE Integration
```

### Key Admin Controllers

#### **1. System Management**
- `DashboardController` - Admin overview
- `SettingsController` - Global app settings, branding, theme
- `SecurityController` - IP blocking, rate limiting, threat intelligence
- `UserController` - User CRUD, permissions management

#### **2. Affiliate System**
- `AffiliateController` - Affiliate management
  - Tree view (`treeView`, `treeViewInteractive`)
  - Individual affiliate display with tree
  - Move affiliates between parents
- `CommissionController` - Commission approval/rejection workflow
- `RankController` - Rank configuration and management

#### **3. Financial Management**
- `WalletController` - Wallet balance management
- `WithdrawalController` - Withdrawal request processing
- `WalletSettingsController` - Financial system configuration
- `PaymentMethodController` - Payment gateway setup

#### **4. E-Commerce**
- `ECommerceController` - Dashboard, product/order management
- `CategoryManagementController` - Product categories
- `ArticleManagementController` - Product descriptions/help

#### **5. Content & Marketing**
- `PageController` - Static page management
- `PremiumPageController` - Premium content pages
- `VisualBuilderController` - Drag-drop page builder
- `HeaderEditorController` - Header customization
- `TemplateController` - Page templates
- `NotificationManagementController` - Push notifications
- `NotificationTemplateController` - Notification templates
- `EmailController` - Email templates & campaign
- `SeoController` - SEO metadata management

#### **6. AI & Automation**
- `AiInstallationController` - AI bot installation management
- `AiProviderManagementController` - Provider setup (OpenAI, etc.)
- `AiBotController` - Bot marketplace management
- `AiMonitoringController` - Usage analytics
- `KnowledgeBaseController` - AI knowledge bases
- `LineBotAiController` - LINE bot AI config
- `LineFlexMessageController` - Flex message templates
- `LineRichMenuController` - Rich menu setup
- `LineChatWidgetController` - Chat widget config
- `LineAvatarController` - Bot avatar management
- `LineBroadcastController` - Broadcast messaging

#### **7. Membership & Retention**
- `MembershipRetentionController` - Membership renewal system
- `OtpSettingsController` - OTP configuration

#### **8. Multi-Language**
- `LanguageSettingController` - Language configuration
- `TranslationMappingController` - Custom translation overrides

#### **9. Content Generation**
- `LearningCenterController` - Course management
- `WebPManagementController` - Image optimization (WebP conversion)

### Admin Panel Views Hierarchy

```
resources/views/admin/
├── dashboard.blade.php                    # Main dashboard
├── affiliates/
│   ├── index.blade.php                   # Affiliate list
│   ├── show.blade.php                    # Affiliate detail
│   ├── edit.blade.php                    # Edit affiliate
│   ├── tree.blade.php                    # Tree view
│   └── tree-interactive.blade.php        # Interactive tree
├── ecommerce/
│   ├── dashboard.blade.php
│   ├── products/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── edit.blade.php
│   ├── orders/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   └── categories/
├── ai-bots/                              # Bot marketplace
├── ai-providers/                         # AI provider setup
├── ai-installation/                      # Installation logs
├── ai-monitoring/                        # Usage analytics
├── line-bot/                             # LINE integration
├── learning-center/                      # Course management
├── articles/                             # Article management
├── categories/                           # Category management
├── commissions/                          # Commission management
├── ranks/                                # Rank configuration
├── wallet/                               # Wallet management
├── security/                             # Security & monitoring
├── email/                                # Email settings
├── notifications/                        # Notification system
├── line-bot/
│   ├── ai/                              # LINE Bot AI
│   ├── flex-messages/                   # Flex message builder
│   ├── rich-menus/                      # Rich menu setup
│   ├── chat-widget/                     # Widget config
│   ├── broadcast/                       # Broadcast messages
│   └── avatars/                         # Avatar management
└── [settings, translations, etc.]
```

### Admin Layout Template
- File: `resources/views/layouts/admin.blade.php`
- Sidebar navigation with module grouping
- Top navigation bar with user menu
- Notifications panel
- Dark/Light mode support
- Responsive design (Mobile, Tablet, Desktop)

---

## 🌐 API Routes & Backend

### REST API v1 Structure (`routes/api.php`)

#### **Public Endpoints** (No Authentication)
```
POST   /api/v1/login                      # User login
GET    /api/v1/settings                   # App settings
POST   /webhook/line                      # LINE webhook (NO CSRF)
```

#### **Protected Endpoints** (Auth: Laravel Sanctum)
```
POST   /api/v1/logout                     # Logout
GET    /api/v1/me                         # Current user profile

Dashboard:
GET    /api/v1/dashboard/statistics       # User stats
GET    /api/v1/dashboard/commissions      # Commission summary
GET    /api/v1/dashboard/referrals        # Referral data

Organization Tree:
GET    /api/v1/tree/user                  # User's tree
GET    /api/v1/tree/admin/{affiliateId?}  # Admin tree view

Ranks:
GET    /api/v1/ranks                      # List all ranks
GET    /api/v1/ranks/{rank}               # Rank details
GET    /api/v1/ranks/user/progress        # User rank progress
GET    /api/v1/ranks/leaderboard          # Rank leaderboard
GET    /api/v1/ranks/user/eligibility     # Check eligibility
POST   /api/v1/ranks/promotions/request   # Request promotion
```

### API Controllers

#### **V1 API Controllers**
- `AuthController` - Login/logout
- `DashboardController` - Statistics & settings
- `TreeController` - Organization structure
- `RankController` - Rank data & leaderboard

### Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "...",
  "errors": { ... }
}
```

### Authentication Flow
1. User sends credentials to `POST /api/v1/login`
2. Server returns API token (Sanctum)
3. Client includes token in `Authorization: Bearer {token}` header
4. Middleware validates token on protected routes

---

## 🎨 Frontend Framework

### Frontend Tech Stack
- **View Engine:** Laravel Blade templates
- **CSS:** Tailwind CSS 3.4 (utility-first)
- **Interactivity:** Alpine.js 3.13.5 (lightweight reactive)
- **Build:** Vite 5.0
- **Charts:** Chart.js 4.4.1
- **Visualization:** D3.js 7.9, vis-network 10.0.2
- **Animation:** GSAP 3.12.5

### Layout Templates

#### **Main Layout** (`resources/views/layouts/app.blade.php`)
- Responsive Tailwind CSS
- Navigation component
- Footer component
- SEO meta tags
- Font loading optimization

#### **Admin Layout** (`resources/views/layouts/admin.blade.php`)
- Sidebar navigation (~77KB)
- Module-based menu structure
- User profile dropdown
- Quick action buttons
- Notification panel

#### **Seller Layout** (`resources/views/layouts/seller.blade.php`)
- Seller-specific navigation
- Dashboard shortcuts
- Product management quick links
- Order notifications

#### **User Dashboard Layout** (`resources/views/layouts/user.blade.php`)
- User profile management
- Affiliate tree visualization
- Commission tracking
- Wallet management
- Referral link sharing

### Frontend Components

#### **Reusable Components** (`resources/views/components/`)
- Alert/notification boxes
- Buttons (primary, secondary, danger)
- Forms (input, textarea, select)
- Cards (content containers)
- Modals/dialogs
- Tables with sorting
- Pagination
- Loading indicators

#### **User Pages**
```
resources/views/user/
├── dashboard/              # Main dashboard
├── profile/                # Profile management
├── wallet/                 # Wallet interface
├── commissions/            # Commission tracking
├── affiliates/             # Referral management
├── learning/               # Course interface
└── withdrawal/             # Withdrawal requests
```

#### **Seller Pages**
```
resources/views/seller/
├── dashboard/              # Seller overview
├── products/               # Product management
├── orders/                 # Order management
├── analytics/              # Sales analytics
└── profile/                # Seller profile
```

#### **Frontend Public Pages**
```
resources/views/frontend/
├── pages/                  # Dynamic CMS pages
├── home.blade.php          # Homepage
├── marketplace/            # Bot marketplace
└── my-rentals/            # Rental management
```

### Forms & Input Handling
- CSRF protection on all forms
- Validation messages
- File uploads (images, documents)
- Real-time validation with Alpine.js
- Loading states during submission

### Components Features
- Mobile-responsive Tailwind classes
- Dark mode support
- Accessibility (ARIA labels)
- Tab/keyboard navigation
- Focus management

---

## 🔧 Key Services & Business Logic

### Service Classes (30+ Services)

#### **1. Financial Services**
- **WalletService** (18,468 lines)
  - `createWallet()` - Initialize wallet
  - `deposit()` - Add funds with audit trail
  - `withdraw()` - Process withdrawals
  - `transfer()` - Peer-to-peer transfers
  - `lock/unlock()` - Security freezing
  - Atomic transactions with DB::transaction()

- **RankingService** (10,028 lines)
  - `processAutoPromotions()` - Batch promotion checking
  - `checkAndPromoteUser()` - Individual eligibility
  - `checkUserEligibility()` - Detailed requirement checking
  - Requirements validation
  - Notification triggers

- **WithdrawalService** (12,911 lines)
  - Withdrawal request processing
  - Payment method validation
  - Bank transfer handling
  - Request status tracking
  - Approval/rejection workflow

#### **2. E-Commerce Services**
- **ProductService** (implied) - Product management
- **OrderService** (implied) - Order processing
- **CommissionService** (implied) - Commission calculation

#### **3. Payment Services**
- **PaymentGatewayService** (8,699 lines)
  - Multi-gateway integration
  - Transaction processing
  - Webhook handling
  - Payment status tracking

#### **4. Communication Services**
- **EmailService** (11,285 lines)
  - Email template rendering
  - Queue-based sending
  - Multiple providers support
  - Bounce handling
  - Campaign tracking

- **NotificationService** (14,104 lines)
  - In-app notifications
  - Push notifications
  - Email notifications
  - SMS (if configured)
  - Schedule management

- **LineService** (15,573 lines)
  - LINE Official Account integration
  - Message pushing
  - User binding
  - Rich menu management
  - Template messages

#### **5. AI/Bot Services**
- **LineBotAiService** (10,592 lines)
  - AI conversation management
  - Knowledge base integration
  - Response generation
  - Context management

#### **6. Security & Monitoring**
- **ThreatIntelligenceService** (12,911 lines)
  - IP reputation checking
  - Auto-banning mechanisms
  - Attack pattern detection
  - Threat data updates

- **AutoBanService** (8,802 lines)
  - Automated blocking rules
  - Failed login tracking
  - CAPTCHA enforcement
  - Rate limiting

#### **7. Data Processing**
- **TranslationService** (9,633 lines)
  - Google Translate API integration
  - Batch translation
  - Language detection
  - Cache management

- **WebPService** (5,602 lines) & **WebPDatabaseUpdateService** (8,495 lines)
  - Image optimization
  - Format conversion
  - Database migration
  - Progressive enhancement

#### **8. Utilities**
- **LicenseService** (9,663 lines) - License validation
- **OtpService** (6,344 lines) - OTP generation/validation
- **SeoService** (7,175 lines) - SEO metadata
- **VersionService** (8,671 lines) - Version management
- **MembershipRetentionService** (16,494 lines) - Membership renewal

---

## 🔐 Authentication & Security

### Authentication Methods

#### **1. Email/Password**
- Standard Laravel authentication
- Password hashing with bcrypt
- "Remember me" functionality
- Session-based for web routes

#### **2. LINE Official Account**
- OAuth 2.0 integration
- One-tap login
- Account linking for existing users
- Profile data import (name, picture)
- `LineLoginController` - OAuth flow

#### **3. API Token Authentication (Sanctum)**
- Token-based for REST API
- Revocable tokens
- Token-specific abilities
- Used for mobile apps

#### **4. Two-Factor Authentication**
- OTP (One-Time Password) support
- `OtpController` - OTP lifecycle
- SMS/Email delivery
- Rate limiting on attempts

### Security Features

#### **Request Validation**
- CSRF token on all forms
- Middleware validation
- Turnstile CAPTCHA (Cloudflare)
  - Login form protection
  - Registration form protection

#### **Rate Limiting**
- Throttle middleware
- Login attempts: Limited per IP
- API requests: Configurable per route
- Translation API: 60 requests/minute

#### **IP Management**
- `BlockedIp` model - Manual IP blocking
- CIDR notation support (IP ranges)
- `ThreatIp` model - Threat intelligence
- Auto-ban after failed attempts

#### **Security Logging**
- `SecurityLog` model (10,660 lines)
  - All user actions logged
  - Failed login attempts
  - Permission changes
  - Payment transactions
  - Account modifications

#### **Wallet Security**
- PIN protection
- Two-factor authentication on wallet
- Recovery codes backup
- Failed attempt tracking
- Auto-lock after 5 failures
- IP/User-Agent logging

#### **Data Protection**
- Sensitive fields hidden from serialization
- Password and tokens not exposed in responses
- Soft deletes for audit trail
- Encrypted sensitive data options

---

## 📊 Database Schema Highlights

### Key Table Relationships

```
users (1) ──── (1) affiliates
               └─ (many) commissions
               └─ (1) wallet
               └─ (many) orders
               └─ (many) rank_promotions
               └─ (many) user_rank_progress
               └─ (1) current_rank (via current_rank_id)

affiliates (parent) ──── (many) affiliates (children)
                    └─ (many) commissions
                    └─ (1) rank

ranks (1) ──── (many) users
          └─ (many) rank_requirements
          └─ (many) rank_bonuses
          └─ (many) rank_promotions

products (1) ──── (many) order_items
           └─ (many) product_images
           └─ (many) reviews
           └─ (1) category
           └─ (many) variants

orders (1) ──── (many) order_items
        └─ (1) user
        └─ (1) shipping_address

wallets (1) ──── (many) wallet_transactions
                └─ (many) wallet_logs

ai_bot_rentals (1) ──── (many) ai_conversations
                    └─ (many) owner_earnings
```

### Indexing Strategy
- Primary keys indexed automatically
- Foreign keys indexed
- Frequently searched fields (status, created_at, user_id)
- Composite indexes on common WHERE + ORDER BY combinations
- Unique constraints on referral_code, email, slug, sku

---

## 🚀 Key Business Flows

### 1. User Registration & Affiliate Activation
```
User Registration
  ↓
Email Verification (optional OTP)
  ↓
Affiliate Auto-Creation
  ↓
Default Rank Assignment
  ↓
Wallet Creation
  ↓
User Can Start Recruiting
```

### 2. Commission Calculation Flow
```
Order Placement (Order::create)
  ↓
Order Paid (Order::markAsPaid)
  ↓
Commission Auto-Generation (triggered by event)
  ↓
Multi-Level Commission Calculation
  ├─ Direct: Commission from order amount
  ├─ Indirect: From child affiliate orders
  └─ Bonus: Based on rank
  ↓
Commission Status: pending → approved → paid
```

### 3. Rank Promotion Flow
```
User Earns Points/Referrals/Sales
  ↓
RankingService::checkAndPromoteUser()
  ↓
Check All Requirements Met
  ├─ Points ≥ minimum
  ├─ Referrals ≥ minimum
  ├─ Team Sales ≥ minimum
  └─ Other criteria...
  ↓
IF eligible:
  ├─ Create RankPromotion record
  ├─ Update User::current_rank_id
  ├─ Apply rank bonuses (if auto_apply)
  ├─ Notify user
  └─ Log security action
```

### 4. Wallet Transaction Flow
```
Commission/Bonus/Topup
  ↓
WalletService::deposit()
  ↓
Atomic Transaction:
  ├─ Create WalletTransaction
  ├─ Update Wallet::balance
  ├─ Log action in WalletLog
  └─ Update totals (total_income/expense)
  ↓
Notification Sent
```

---

## 📱 Mobile/API Considerations

### API Response Format
```php
// Success response
{
  "success": true,
  "data": { /* model data */ },
  "message": "Operation successful"
}

// Error response
{
  "success": false,
  "errors": { "field": ["error message"] },
  "message": "Validation failed"
}
```

### Token Management
- Tokens created on login
- Stored on client
- Sent in `Authorization: Bearer` header
- Revoked on logout
- Auto-refresh capability (configurable)

---

## 🔄 MLM System Extensibility

### For Building Complete MLM on Top

The system provides foundation for:

1. **Variable Commission Structures**
   - Per-product commission rates
   - Rank-based multipliers
   - Bonus types flexibility
   - Multi-tier depth support

2. **Achievement Gamification**
   - Rank system with visual badges
   - Leaderboards
   - Achievement tracking
   - Progress indicators

3. **Financial Management**
   - Multiple wallet currencies
   - Conversion rates
   - Payout methods
   - Audit trail

4. **Communication Infrastructure**
   - EMAIL: Templates, SMTP providers
   - SMS: Integration ready
   - PUSH: App notifications
   - LINE: Official Account integration

5. **Analytics & Reporting**
   - Commission tracking
   - Affiliate performance
   - Revenue attribution
   - Churn analysis

---

## 📦 Configuration Files

### Environment Variables (`/home/user/Thaiprompt-Affiliate/.env.example`)
```
APP_NAME=TP-Affiliate
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465

GOOGLE_CLOUD_TRANSLATE_API_KEY=
LINE_CHANNEL_ID=
LINE_CHANNEL_SECRET=
LINE_ACCESS_TOKEN=
```

### Composer Dependencies
- Laravel 11 framework core
- Google Cloud Translate
- Guzzle HTTP client
- Intervention Image processing
- Laravel Sanctum (API auth)
- Laravel Tinker (debugging)

### NPM Dependencies
- Tailwind CSS + Forms plugin
- Alpine.js
- Axios (HTTP client)
- PostCSS/Autoprefixer
- Vite build tool
- Chart.js, D3.js, vis-network, GSAP

---

## 📈 Deployment Considerations

### Database Migrations
- 95+ migrations ensuring schema consistency
- Run with: `php artisan migrate`
- Reversible with: `php artisan migrate:rollback`

### Asset Building
```bash
npm run build    # Production build with Vite
npm run dev      # Development with hot reload
```

### Storage
- Public disk for user uploads
- Organized directories for images, documents
- WebP conversion jobs for optimization

### Queue Jobs (Optional)
- Email sending in background
- Image processing
- Translation batching
- Notification dispatching

---

## 🎓 For MLM System Development

### Key Areas to Extend

1. **Commission Calculation Engine**
   - Create service for custom formulas
   - Support multiple calculation types
   - Implement bonus structures

2. **Rank System Enhancement**
   - Add more requirement types
   - Implement rank-specific features
   - Create demotion logic if needed

3. **Reporting & Analytics**
   - Revenue attribution by affiliate
   - Performance metrics
   - Churn prediction
   - Top performer identification

4. **Mobile App Integration**
   - API already supports mobile clients
   - Build iOS/Android apps consuming REST API
   - Push notification support

5. **Payment Integration**
   - Expand gateway support
   - Implement crypto payments
   - Add bank APIs for payouts

---

## 📞 Support & Documentation Files

The project includes comprehensive documentation:
- `README.md` - Project overview
- `INSTALLATION-GUIDE.md` - Setup instructions
- `RANKING_SYSTEM.md` - Rank system details
- `WALLET_SYSTEM.md` - Wallet implementation
- `NOTIFICATION_SYSTEM.md` - Notification setup
- `MULTI-LANGUAGE.md` - i18n configuration
- `DEPLOYMENT-GUIDE.md` - Production deployment
- `DESIGN_GUIDELINES.md` - UI/UX standards

---

## 🔗 Quick Reference

**Admin URL:** `/admin/dashboard`  
**API Base:** `/api/v1`  
**Seller Dashboard:** `/seller/dashboard`  
**User Dashboard:** `/user/dashboard`  
**LINE Webhook:** `/api/webhook/line`  
**Translation API:** `/api/translate`

