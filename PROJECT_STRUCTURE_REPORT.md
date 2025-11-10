# รายงานสำรวจโครงสร้าง Thaiprompt-Affiliate

## 1. โครงสร้างไดเรกทอรี Backend/Frontend

### Backend Structure (Laravel)
```
app/
├── Http/
│   ├── Controllers/          # ประมาณ 200+ controller classes
│   │   ├── Admin/            # Controllers สำหรับแอดมิน
│   │   ├── Api/              # API Controllers
│   │   │   ├── V1/           # API version 1 controllers
│   │   ├── Auth/             # Authentication controllers
│   │   ├── Frontend/         # Public-facing controllers
│   │   ├── Seller/           # Seller dashboard controllers
│   │   ├── User/             # User account controllers
│   │   ├── HotelAdmin/       # Hotel management
│   │   └── Pos/              # Point of Sale
│   ├── Middleware/
│   │   ├── AdminMiddleware               # Admin access control
│   │   ├── CheckBlockedIp               # IP blocking
│   │   ├── CheckPermission              # Permission verification
│   │   ├── CheckRole                    # Role-based access
│   │   ├── DevMode                      # Development mode
│   │   ├── HotelAdminMiddleware         # Hotel-specific access
│   │   ├── IdempotencyMiddleware        # Request deduplication
│   │   ├── PaymentRateLimiter           # Payment rate limiting
│   │   ├── RequireTwoFactor             # 2FA enforcement
│   │   ├── ThrottleLogin                # Login throttling
│   │   ├── TrackRequestMetrics          # Request monitoring
│   │   ├── VerifyCloudfareTurnstile     # CAPTCHA verification
│   │   ├── VerifyLicenseIntegrity       # License validation
│   │   └── VerifyWebhookSignature       # Webhook validation
│   └── Requests/             # Form validation requests
├── Models/                   # 270+ Eloquent models
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── Affiliate.php
│   ├── Commission.php
│   ├── Rank.php              # Ranking system
│   ├── RankPromotion.php
│   ├── InvestmentPlan.php    # Investment/Staking
│   ├── StakingPosition.php
│   ├── CryptoWallet.php      # Crypto integration
│   ├── CryptoTransaction.php
│   ├── CryptoExchangeTransaction.php
│   ├── MlmMember.php         # MLM system
│   ├── MlmPlan.php
│   ├── AiBotProfile.php      # AI/Bot features
│   ├── AiConversation.php
│   ├── Hotel.php             # Hotel management
│   ├── HotelBooking.php
│   ├── Order.php             # E-commerce
│   ├── Product.php
│   ├── Wallet.php            # Digital wallet
│   ├── PaymentGateway.php    # Payment integration
│   ├── TarotReading.php      # Tarot services
│   ├── TradingBot.php        # Trading features
│   └── ... many more domain-specific models
├── Services/                 # Business logic services
│   ├── LineService.php
│   ├── IpIntelligenceService.php
│   ├── AI/
│   │   ├── BaseAiService.php
│   │   ├── OpenAiService.php
│   │   ├── ClaudeService.php
│   │   ├── LocalAiService.php
│   │   ├── AiServiceFactory.php
│   │   ├── RagService.php
│   │   ├── EmbeddingService.php
│   │   └── ... AI-related services
│   └── Marketplace/
│       └── MarketplaceSyncService.php
├── Policies/                 # Authorization policies
│   ├── PageBuilderPolicy.php
│   ├── StakingPositionPolicy.php
│   └── Accounting*Policy.php
├── Observers/                # Model observers
├── Providers/                # Service providers
├── Exceptions/               # Custom exceptions
├── Jobs/                     # Queued jobs
├── Mail/                     # Mailables
├── Notifications/            # Notification classes
├── Helpers/                  # Helper functions
└── Console/                  # Console commands

config/
├── app.php                   # App configuration
├── auth.php                  # Authentication config
├── sanctum.php              # Sanctum (API auth) config
├── database.php
├── cache.php
├── crypto.php               # Crypto settings
├── services.php             # Third-party services
├── email.php                # Email configuration
├── ratelimit.php            # Rate limiting
├── autoban.php              # Auto-ban configuration
├── license.php              # License configuration
└── ... other configs

database/
├── migrations/              # 271 migration files
│   ├── 2024_01_01_000001_create_users_table.php
│   ├── 2024_01_01_000002_create_affiliates_table.php
│   ├── 2024_01_01_000003_create_commissions_table.php
│   ├── 2024_11_01_100001_create_ranks_table.php
│   └── ... many more
├── seeders/                 # Database seeders
└── sql/                     # SQL dump files

routes/
├── api.php                  # API routes (139 lines)
├── web.php                  # Web routes (322 lines)
├── admin.php               # Admin routes (1,372 lines)
├── seller.php              # Seller routes (166 lines)
├── user.php                # User routes (276 lines)
├── hotel-admin.php         # Hotel admin routes (135 lines)
├── pos.php                 # POS routes (37 lines)
├── software_sales.php      # Software sales (102 lines)
└── console.php             # Console commands
```

### Frontend Structure
```
resources/
├── js/                       # JavaScript/TypeScript
│   ├── kyc-camera.js
│   ├── mlm-genealogy.js
│   ├── tree-visualization.js # Tree visualization
│   ├── tree-network.js
│   └── crypto/               # Crypto-related JS
├── views/                    # Blade templates
│   ├── admin/                # Admin panel views
│   │   ├── line-oa/
│   │   ├── ai-providers/
│   │   ├── translations/
│   │   ├── investments/
│   │   ├── ranks/
│   │   ├── crypto/
│   │   ├── tickets/
│   │   ├── mlm/
│   │   │   ├── genealogy/
│   │   │   ├── reports/
│   │   │   ├── plans/
│   │   │   └── members/
│   │   ├── roles/
│   │   └── ... many more
│   ├── shop/                 # E-commerce views
│   ├── software-products/
│   └── pdf/                  # PDF generation views

public/
├── images/
├── icons/
└── ... static assets
```

## 2. Database Schema Overview

### Core Tables
- **users** - User accounts with extensive profile fields
- **roles** - Role definitions (admin, seller, user, etc.)
- **permissions** - Permission definitions
- **role_permissions** - Role-permission mapping (many-to-many)

### Authentication & Security
- **personal_access_tokens** - Sanctum API tokens
- **blocked_ips** - IP blocking list
- **security_logs** - Security audit trail
- **threat_ips** - Threat tracking
- **otp_verification** - OTP management
- **two_factor_user_setting** - 2FA settings

### Affiliate & Commission System
- **affiliates** - Affiliate users
- **commissions** - Commission records
- **mlm_members** - MLM hierarchy members
- **mlm_genealogy** - MLM tree relationships
- **mlm_binary_position** - Binary tree positions
- **mlm_commission** - MLM commission calculations
- **ranks** - User rank definitions
- **rank_requirements** - Rank achievement requirements
- **rank_promotions** - Rank promotion records
- **user_rank_progress** - User progress toward ranks

### Financial & Wallet
- **wallets** - Digital wallets
- **wallet_transactions** - Transaction history
- **wallet_logs** - Wallet activity logs
- **payment_gateway** - Payment gateways config
- **payment_method** - Available payment methods
- **payment_transaction** - Payment transactions

### Cryptocurrency
- **crypto_wallet** - Crypto wallets
- **crypto_currency** - Supported crypto currencies
- **crypto_transaction** - Crypto transactions
- **crypto_exchange_transaction** - Exchange transactions
- **crypto_address** - Wallet addresses
- **crypto_deposit_address** - Deposit addresses
- **crypto_withdrawal_request** - Withdrawal requests

### Investment & Staking
- **investment_plan** - Investment products
- **staking_position** - Staking positions
- **roi_distribution** - ROI payouts
- **installment_plan** - Payment plans
- **installment_payment** - Plan payments

### E-Commerce
- **products** - Product catalog
- **product_category** - Categories
- **orders** - Order records
- **order_items** - Order line items
- **shopping_cart** - Cart data
- **product_review** - Product reviews

### Hotel Management
- **hotels** - Hotel listings
- **hotel_booking** - Bookings
- **room_type** - Room types
- **room_availability** - Availability
- **hotel_facility** - Amenities
- **hotel_special_offer** - Promotions

### AI/Bot Features
- **ai_bot_profile** - AI bot configurations
- **ai_conversation** - Chat conversations
- **ai_message** - Chat messages
- **ai_knowledge_base** - Knowledge base content
- **ai_knowledge_chunk** - Knowledge segments
- **ai_usage_log** - Usage tracking

### Content & CMS
- **pages** - CMS pages
- **page_builder** - Page builder data
- **page_builder_section** - Page sections
- **learning_article** - Article content
- **article_permission** - Article access control
- **knowledge_base** - Knowledge base

### Marketplace & Vendor
- **vendor_store** - Seller stores
- **vendor_package** - Vendor packages
- **vendor_subscription** - Package subscriptions
- **marketplace_product** - Marketplace products
- **marketplace_order** - Marketplace orders
- **marketplace_affiliate_link** - Affiliate links

### Other Major Systems
- **notification** - Notification records
- **notification_template** - Template definitions
- **email_log** - Email tracking
- **email_preference** - User preferences
- **ticket** - Support tickets
- **certificate** - Certificate management

## 3. API Routes Structure

### Authentication Endpoints
```
POST   /api/v1/login                          - User login
POST   /api/v1/logout                         - User logout (protected)
GET    /api/v1/me                             - Get current user (protected)
```

### Public Routes (No Auth Required)
```
GET    /api/v1/settings                       - App settings
GET    /api/v1/app/maintenance-status         - Maintenance status
GET    /api/v1/app/check-update               - Version check
GET    /api/v1/app/banners                    - App banners
POST   /api/v1/app/banners/{id}/view          - Track banner view
GET    /api/v1/ranks                          - Get all ranks
```

### Protected Routes (Require Authentication)

#### Dashboard & Statistics
```
GET    /api/v1/dashboard/statistics           - User statistics
GET    /api/v1/dashboard/commissions          - Commission summary
GET    /api/v1/dashboard/referrals            - Referral data
```

#### Tree/Genealogy
```
GET    /api/v1/tree/user                      - User's tree
GET    /api/v1/tree/admin/{affiliateId}       - Admin tree view
GET    /api/v1/tree/binary                    - Binary tree
GET    /api/v1/tree/binary/admin/{id}         - Admin binary tree
```

#### Ranks
```
GET    /api/v1/ranks                          - All ranks
GET    /api/v1/ranks/{id}                     - Rank detail
GET    /api/v1/ranks/user/progress            - User rank progress
GET    /api/v1/ranks/leaderboard              - Rank leaderboard
GET    /api/v1/ranks/user/eligibility         - Check rank eligibility
POST   /api/v1/ranks/promotions/request       - Request promotion
```

#### Investment/Staking
```
GET    /api/v1/investments/plans              - Investment plans
GET    /api/v1/investments/plans/{id}         - Plan details
POST   /api/v1/investments/calculate-roi      - ROI calculator
POST   /api/v1/investments/invest             - Create investment
GET    /api/v1/investments/summary            - Portfolio summary
GET    /api/v1/investments/positions          - User positions
POST   /api/v1/investments/positions/{id}/withdraw - Withdraw
GET    /api/v1/investments/distributions      - ROI distributions
```

#### Crypto Wallet
```
POST   /api/crypto/generate-nonce             - Generate nonce (public)
POST   /api/v1/crypto/verify-signature        - Verify wallet signature
GET    /api/v1/crypto/balances                - Get balances
GET    /api/v1/crypto/address/{currency}      - Get address
GET    /api/v1/crypto/prices                  - Get prices
GET    /api/v1/crypto/transaction/{hash}      - Check transaction
GET    /api/v1/crypto/gas-price               - Get gas price
```

#### App Configuration
```
GET    /api/v1/app/config                     - App configuration
GET    /api/v1/app/settings                   - App settings
GET    /api/v1/app/theme                      - Theme configuration
GET    /api/v1/app/features                   - Feature flags
GET    /api/v1/app/banners                    - App banners
```

### Webhook Routes (No Auth, Signature Verification)
```
POST   /api/webhook/line                      - LINE webhook
POST   /api/webhook/paysolutions              - PaySolutions payment
POST   /api/webhook/promptpay                 - PromptPay payment
POST   /api/webhook/stripe                    - Stripe payment
POST   /api/webhook/omise                     - Omise payment
```

### Other Public API Routes
```
POST   /api/cookie-consent                    - Cookie preferences
GET    /api/cookie-consent                    - Get consent status
POST   /api/cookie-track-page                 - Track page views
POST   /api/cookie-track-keyword              - Track keywords
POST   /api/cookie-track-product              - Track products
```

## 4. ระบบ Authentication/Authorization

### Authentication Methods
1. **Session-based** (Web routes)
   - Guard: `web`
   - Provider: Eloquent User model
   - Default password reset: 60 minutes timeout

2. **Token-based** (API routes)
   - Using Laravel Sanctum
   - Config: `config/sanctum.php`
   - Stateless API authentication

3. **OAuth2 Integration**
   - LINE Login (Social authentication)
   - Routes: `/auth/line`, `/auth/line/callback`
   - Account linking supported
   - Signup via LINE invitation tokens

### Authorization Layers

#### 1. Middleware-Based Access Control
```php
- AdminMiddleware              // Super admin only
- HotelAdminMiddleware         // Hotel managers
- SuperAdminMiddleware         // Super admin verification
- CheckRole                    // Role-based routing
- CheckPermission              // Permission-based routing
- CheckBlockedIp              // IP blocking enforcement
- RequireTwoFactor            // 2FA requirement
```

#### 2. Role-Based Access Control (RBAC)
- **Roles table**: Stores role definitions
- **Permissions table**: Stores permission definitions
- **Role_permissions table**: Many-to-many relationship
- System roles cannot be deleted (is_system_role flag)

```php
// Role Model Methods:
$role->permissions()           // Get role permissions
$role->hasPermission()         // Check single permission
$role->hasAnyPermission()      // Check any permission
$role->hasAllPermissions()     // Check all permissions
$role->grantPermission()       // Add permission
$role->revokePermission()      // Remove permission
$role->syncPermissions()       // Bulk update
```

#### 3. Policy-Based Authorization
- **PageBuilderPolicy.php** - Page builder access
- **StakingPositionPolicy.php** - Investment position access
- **Accounting Policies** - Accounting module access

#### 4. User Roles in System
```
User Flags:
- is_super_admin              // Top-level access
- is_admin                    // General admin
- is_hotel_admin              // Hotel-specific
- managed_hotel_id            // Associated hotel
- role_id                     // Assigned role
- permissions (JSON array)    // Direct permissions
```

#### 5. Security Features
- **IP Blocking**: BlockedIp model + CheckBlockedIp middleware
- **Security Logs**: SecurityLog model tracks all access
- **Threat Tracking**: ThreatIp model for suspicious activity
- **2FA**: TwoFactorUserSetting model with verification codes
- **OTP**: OtpVerification model for additional verification
- **API Token Management**: Personal access tokens (Sanctum)

## 5. UI Components & Frontend Framework

### Technology Stack
```json
{
  "build_tool": "Vite",
  "css_framework": "Tailwind CSS 3.4.1",
  "javascript": "Alpine.js 3.13.5",
  "charting": "Chart.js 4.4.1",
  "visualization": "D3.js 7.9.0",
  "animation": "GSAP 3.12.5",
  "crypto": "ethers.js 5.8.0, wagmi 1.4.13",
  "web3": "Web3Modal, viem 1.21.4"
}
```

### Frontend Components by Section

#### Admin Panel Components
- **Dashboard** - Analytics and key metrics
- **User Management** - CRUD operations
- **Role Management** - Permission assignment
- **Analytics** - Real-time and historical data
- **MLM Genealogy** - Tree visualization
- **Rank Management** - Rank creation and settings
- **Investment Management** - Plan management
- **Crypto Management** - Wallet and transaction views
- **Payment Gateway Config** - Gateway setup
- **Email Templates** - Template editor
- **Notification Management** - Alert configuration
- **Security Logs** - Audit trail viewer

#### Seller Dashboard Components
- **Sales Dashboard** - Sales metrics
- **Product Management** - Product CRUD
- **Order Management** - Order processing
- **Analytics** - Sales and traffic analysis
- **AI-Powered Insights** - Prediction and segmentation
- **Customer Segmentation** - Cohort analysis
- **Wallet** - Earnings and withdrawals
- **Store Settings** - Store configuration
- **Package Management** - Subscription plans

#### User Portal Components
- **Dashboard** - Personal metrics
- **Genealogy Tree** - MLM tree viewer
- **Commission Tracking** - Commission details
- **Rank Progress** - Achievement tracking
- **Wallet** - Balance and transactions
- **Investments** - Portfolio and ROI
- **Account Settings** - Profile management

#### E-Commerce Components
- **Shop Listing** - Product catalog
- **Product Detail** - Product information
- **Shopping Cart** - Cart management
- **Checkout** - Order placement
- **Order Tracking** - Delivery status

#### Hotel Management Components
- **Hotel Listing** - Property management
- **Room Management** - Room types and availability
- **Booking Calendar** - Reservation view
- **Special Offers** - Promotion management

### JavaScript/Frontend Features
1. **Tree Visualization**
   - `mlm-genealogy.js` - MLM tree structure
   - `tree-visualization.js` - D3-based rendering
   - `tree-network.js` - Network diagram

2. **Crypto Integration**
   - Web3 modal integration
   - Wallet connection (wagmi)
   - Transaction signing (ethers.js)

3. **Interactive Forms**
   - KYC camera integration
   - Document upload
   - Multi-step forms

4. **Real-time Updates**
   - Chart.js for metrics
   - GSAP animations
   - Real-time data refresh

## 6. การออกแบบระบบจัดการ API

### API Architecture Considerations

#### Request Flow
1. **Route Definition** → Specifies endpoint and controller
2. **Middleware Stack** → Validates request (CORS, auth, CSRF)
3. **Controller** → Business logic delegation
4. **Service Layer** → Complex operations
5. **Model** → Data access with relationships
6. **Response** → JSON output with HTTP status

#### Rate Limiting
```php
Config: config/ratelimit.php
- Login throttling: 5 attempts per minute
- Payment operations: Custom rate limiter
- Translation API: 60 requests per minute
- General: 60 requests per minute (default)
```

#### Webhook Handling
- **Signature Verification**: VerifyWebhookSignature middleware
- **Idempotency**: IdempotencyMiddleware for duplicate prevention
- **Supported Gateways**: 
  - PaySolutions
  - PromptPay
  - Stripe
  - Omise

#### Error Handling
- Custom exception classes in `app/Exceptions/`
- Validation requests in `app/Http/Requests/`
- JSON error responses with proper HTTP codes

#### Security Headers
- CSRF token verification (VerifyCsrfToken)
- Cloudflare Turnstile CAPTCHA
- Webhook signature verification
- License integrity checking

### Database Relationships (Key Examples)
```php
User → Affiliate (polymorphic)
User → Role (many-to-many via role_id)
Role → Permission (many-to-many via role_permissions)
User → Rank (belongs to current_rank_id)
User → Wallet (has one)
User → CryptoWallet (has many)
Affiliate → Commission (has many)
Product → Category (belongs to)
Order → OrderItem (has many)
Investment → StakingPosition (has many)
```

## 7. Third-Party Integrations

### Payment Gateways
- PaySolutions
- PromptPay
- Stripe
- Omise

### Communication
- LINE OA (Official Account)
- Email providers
- SMS (OTP)

### Crypto
- Multiple blockchain networks
- Wallet integration
- Exchange rate fetching

### AI Services
- OpenAI
- Claude (Anthropic)
- Local AI options
- RAG (Retrieval-Augmented Generation)

### Analytics & Monitoring
- System analytics tracking
- Performance monitoring
- Traffic analysis
- Business metrics

## 8. ข้อมูลสำคัญสำหรับออกแบบระบบจัดการ API

### Key Statistics
- **Total Models**: 270+
- **Total Controllers**: 200+
- **Total Route Lines**: 2,558
- **Database Migrations**: 271
- **Middleware Layers**: 24+
- **Service Classes**: 20+

### Access Control Hierarchy
1. Super Admin (is_super_admin = true)
2. Admin (is_admin = true)
3. Hotel Admin (is_hotel_admin = true)
4. Seller (specific role)
5. User (default role)

### Multi-Tenancy
- Hotel Admin: Isolated hotel data (managed_hotel_id)
- Seller: Store separation via vendor_store
- User: Personal data isolation

### Scalability Considerations
- Sanctum for stateless API auth
- Rate limiting middleware
- Queue jobs for heavy operations
- Cache layer configured
- Database connection pooling

---

**Generated**: 2025-11-10
**Project**: Thaiprompt-Affiliate
**Version**: 2.110.1
**Framework**: Laravel 10.x with Vite + Tailwind CSS
