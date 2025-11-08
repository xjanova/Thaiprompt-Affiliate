# Thaiprompt-Affiliate: Comprehensive Architecture Overview

**Project**: Thai Prompt Affiliate Marketing Platform  
**Version**: 2.90.0  
**Framework**: Laravel 11 + Vite + Tailwind CSS  
**Database**: MySQL with 160+ migrations  
**API**: REST API with Laravel Sanctum  

---

## 1. DATABASE SCHEMA & MODELS (105+ Models)

### Core Affiliate System
- **User** - Main user entity with RBAC, LINE integration, KYC fields
- **Affiliate** - Hierarchical MLM structure with parent-child relationships, binary tree support
- **Commission** - Commission tracking (direct, indirect, bonus types)
- **Rank** - Rank levels with multi-language support, commission rates, bonuses
- **RankRequirement** - Configurable rank criteria (points, referrals, sales, team sales)
- **RankBonus** - Rank-based incentives (one_time, monthly, commission, multiplier)
- **RankPromotion** - Track rank transitions
- **UserRankProgress** - Individual rank advancement monitoring

### Financial System
- **Wallet** - User wallet with PIN protection, 2FA, balance tracking
- **WalletTransaction** - Detailed transaction history (deposit, withdrawal, transfer, commission, bonus)
- **WalletLog** - Action audit trail
- **WithdrawalRequest** - Withdrawal management workflow
- **PaymentMethod** - Multiple payment method storage
- **PaymentTransaction** - Payment processing records
- **PaymentGateway** - Payment provider configuration

### E-Commerce Products & Orders
- **Product** - Product catalog with variants, commission rates, inventory, SEO fields
- **ProductCategory** - Product categorization
- **ProductImage** - Product image management
- **ProductReview** - Customer reviews with ratings
- **Order** - Order management (pending, paid, processing, shipped, delivered, completed, cancelled, refunded)
- **OrderItem** - Individual items in orders with commission split
- **ShippingAddress** - Delivery addresses

### Marketplace System
- **MarketplacePlatform** - Connected marketplace platforms (Shopee, Lazada, etc.)
- **MarketplaceAccount** - User marketplace accounts with API credentials
- **MarketplaceProduct** - Synced products from external marketplaces
- **MarketplaceAffiliateLink** - Generated affiliate links for marketplace products
- **MarketplaceOrder** - Orders from affiliate links
- **MarketplaceCommission** - Commission tracking for affiliate sales
- **MarketplaceLinkClick** - Click tracking for affiliate links
- **MarketplaceSyncLog** - Sync operation audit trail

### Software Sales System
- **SoftwareProduct** - Customizable software product listings
- **SoftwareProductCategory** - Software product categories
- **SoftwareProductOption** - Configurable product options (select, checkbox, radio, number, text)
- **SoftwareProductOptionValue** - Option values with pricing (fixed or percentage)
- **SoftwareQuotation** - Automated quotations with status tracking
- **SoftwareQuotationItem** - Items in quotations
- **SoftwareQuotationSelectedOption** - Selected options with pricing
- **InstallmentPlan** - Flexible installment plans with interest rates
- **InstallmentPayment** - Payment tracking per installment

### AI & Bot Marketplace (20+ Models)
- **AiProvider** - AI provider configuration (OpenAI, DeepSeek, Anthropic, Gemini)
- **AiModel** - Available AI models
- **AiBotProfile** - Bot marketplace listings with rental configuration
- **AiBotRental** - Bot rental transactions
- **AiInstallationLog** - Installation audit trail
- **AiConversation** - Chat history per bot
- **AiMessage** - Individual messages with tokens/cost
- **AiUsageLog** - Usage analytics with status tracking
- **OwnerEarning** - Bot owner revenue tracking
- **KnowledgeBase** - Knowledge base management for RAG
- **KnowledgeChunk** - Knowledge chunks with embeddings
- **RagUsageLog** - RAG (Retrieval-Augmented Generation) logging

### LINE Integration (12+ Models)
- **LineOaSetting** - LINE Official Account configuration
- **LineBotAiSetting** - LINE Bot AI provider setup
- **LineBotKnowledgeBase** - Knowledge base for LINE bot
- **LineBotConversation** - Chat history
- **LineBotMessage** - Chat messages with tokens
- **LineFlexMessageTemplate** - Flex message templates (20+ seed templates)
- **LineRichMenu** - Rich menu configuration
- **LineChatWidgetSetting** - Widget customization (position, color, avatar)
- **LineAvatar** - Bot avatar management (image, GIF, Lottie, video)
- **LineBroadcastMessage** - Broadcast message campaigns
- **LineLoginLog** - LOGIN authentication history
- **LineSignupFlow** - Registration flow configuration

### Learning & Content System (10+ Models)
- **LearningCategory** - Course/learning categories
- **LearningArticle** - Educational content with prerequisites
- **UserArticleProgress** - Learning progress tracking
- **ArticlePermission** - Content access control
- **TrainingCourse** - Training course management
- **TrainingEnrollment** - Course enrollment
- **Quiz** - Quiz management
- **QuizQuestion** - Quiz questions with options
- **QuizAttempt** - User quiz attempts
- **Certificate** - Achievement certificates

### MLM System (15+ Models)
- **MlmPlan** - MLM plan types (Binary, Unilevel, Matrix, Hybrid)
- **MlmMember** - MLM member registration
- **MlmCommission** - MLM commission rules and calculations
- **MlmGenealogy** - Family tree relationships
- **MlmBinaryPosition** - Binary tree node tracking
- **MlmProductPv** - Product point values for PV calculation
- **MlmPvTransaction** - PV transaction tracking
- **MlmRankAchievement** - Rank achievement records
- **MlmPackage** - MLM package options
- **MlmProspect** - Prospect lead management
- **MlmGlobalSetting** - System-wide MLM configuration

### Support & Ticketing
- **Ticket** - Support ticket management
- **TicketCategory** - Ticket categorization
- **TicketReply** - Ticket responses with status tracking

### Notification & Communication
- **Notification** - In-app notifications with scheduling
- **NotificationTemplate** - Notification templates
- **EmailLog** - Email delivery tracking
- **EmailTemplate** - Email templates
- **EmailProvider** - Email service configuration (SMTP, Gmail API, custom)
- **EmailPreference** - User email settings

### HR & Accounting Systems
- **Employee** - Employee management
- **Department** - Department organization
- **Position** - Job positions
- **EmployeeDocument** - Document management
- **AttendanceRecord** - Attendance tracking
- **LeaveRequest** - Leave management
- **LeaveType** - Leave type configuration
- **PayrollRecord** - Payroll processing
- **SalaryComponent** - Salary component configuration
- **PerformanceReview** - Performance evaluations
- **JobPosting** - Job listings
- **JobApplication** - Application tracking

### Accounting System
- **AccountingCompany** - Company configuration
- **AccountingChartOfAccount** - Chart of accounts
- **AccountingJournalEntry** - Journal entries
- **AccountingInvoice** - Invoice management
- **AccountingPayment** - Payment recording
- **AccountingExpense** - Expense tracking
- **AccountingProduct** - Accounting product tracking
- **AccountingContact** - Accounting contact management
- **AccountingBankAccount** - Bank account tracking

### Security & Monitoring
- **SecurityLog** - Comprehensive activity logging
- **BlockedIp** - IP blocking management
- **ThreatIp** - Threat intelligence tracking
- **OtpVerification** - OTP authentication records
- **OtpSetting** - OTP system configuration
- **TwoFactorSetting** - System 2FA configuration
- **TwoFactorUserSetting** - User 2FA preferences
- **KycVerification** - KYC verification status

### Special Systems
- **TarotCard** - Tarot card definitions (78 cards)
- **TarotReading** - User tarot readings
- **TarotReadingCard** - Cards drawn in reading
- **TarotSpreadType** - Spread type configuration
- **TarotSetting** - Tarot system configuration
- **TarotUserLimit** - Reading limits per user
- **Hotel** - Hotel listings
- **HotelBooking** - Booking management
- **HotelReview** - Guest reviews
- **PosTransaction** - Point of sale transactions
- **InvestmentPlan** - Investment product offerings
- **StakingPosition** - Crypto staking positions
- **CryptoCurrency** - Cryptocurrency configuration
- **CryptoWallet** - User crypto wallets
- **CryptoTransaction** - Crypto transaction tracking
- **VendorStore** - Vendor store management
- **VendorPackage** - Vendor subscription packages
- **VendorSubscription** - Active vendor subscriptions

---

## 2. API ROUTES STRUCTURE

### Public Routes (No Authentication)
```
POST   /api/webhook/line              - LINE Bot webhook endpoint
POST   /api/cookie-consent            - Cookie consent tracking
GET    /api/v1/login                  - User authentication
GET    /api/v1/settings               - Public app settings
GET    /api/v1/ranks                  - Public rank information
GET    /api/v1/app/maintenance-status - Maintenance status check
GET    /api/v1/app/check-update       - Update availability
POST   /api/crypto/generate-nonce     - Crypto wallet nonce generation
```

### Protected Routes (Sanctum Authentication)
```
// Auth
POST   /api/v1/logout                 - Logout
GET    /api/v1/me                     - Current user info

// Dashboard
GET    /api/v1/dashboard/statistics   - User dashboard stats
GET    /api/v1/dashboard/commissions  - Commission data
GET    /api/v1/dashboard/referrals    - Referral tree data

// Organization Tree
GET    /api/v1/tree/user              - User's network tree
GET    /api/v1/tree/admin/{id}        - Admin view of any user's tree
GET    /api/v1/tree/binary            - Binary tree structure
GET    /api/v1/tree/binary/admin/{id} - Admin binary tree view

// Ranking System
GET    /api/v1/ranks/user/progress    - User rank progress
GET    /api/v1/ranks/leaderboard      - Rank leaderboard
GET    /api/v1/ranks/user/eligibility - Rank eligibility check
POST   /api/v1/ranks/promotions/request - Request rank promotion

// Investments & Staking
GET    /api/v1/investments/plans      - Investment plans list
GET    /api/v1/investments/plans/{id} - Investment plan details
POST   /api/v1/investments/calculate-roi - ROI calculation
POST   /api/v1/investments/invest     - New investment
GET    /api/v1/investments/summary    - Investment summary
GET    /api/v1/investments/positions  - User positions
POST   /api/v1/investments/{id}/withdraw - Withdraw investment

// Crypto Wallet
POST   /api/v1/crypto/verify-signature - Wallet signature verification
GET    /api/v1/crypto/balances        - Wallet balances
GET    /api/v1/crypto/address/{currency} - Wallet address
GET    /api/v1/crypto/prices          - Cryptocurrency prices
GET    /api/v1/crypto/transaction/{tx} - Transaction status
GET    /api/v1/crypto/gas-price       - Network gas price

// App Configuration
GET    /api/v1/app/config             - Full app configuration
GET    /api/v1/app/settings           - App settings
GET    /api/v1/app/theme              - Theme configuration
GET    /api/v1/app/features           - Feature flags
GET    /api/v1/app/banners            - App banners
```

### Payment Webhooks (Webhook Verification Middleware)
```
POST   /api/webhook/paysolutions      - PaySolutions payment confirmation
POST   /api/webhook/promptpay         - PromptPay confirmation
POST   /api/webhook/stripe            - Stripe payment webhook
POST   /api/webhook/omise             - Omise payment webhook
```

---

## 3. FRONTEND COMPONENTS STRUCTURE

### Frontend Framework
- **Build Tool**: Vite 5.0 with hot module replacement
- **CSS**: Tailwind CSS 3.4.1 with forms plugin
- **JS Framework**: Alpine.js 3.13.5 for interactivity
- **Visualization**: D3.js, Chart.js, vis-network for graphs/charts
- **Animation**: GSAP 3.12.5 for smooth animations
- **Crypto**: Web3modal (wagmi), ethers.js, viem for blockchain

### View Structure (Blade Templates)
```
resources/views/
├── admin/                          # Admin panel views
│   ├── dashboard/                  # Admin dashboard
│   ├── users/                      # User management
│   ├── affiliate/                  # Affiliate management
│   ├── commissions/                # Commission management
│   ├── products/                   # Product management
│   ├── orders/                     # Order management
│   ├── line-bot/                   # LINE Bot configuration
│   ├── ai-bots/                    # AI Bot marketplace
│   ├── settings/                   # System settings
│   └── accounts/                   # Accounting system

├── auth/                           # Authentication views
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── reset-password.blade.php
│   └── line-register-guide.blade.php

├── layouts/                        # Layout templates
│   ├── app.blade.php               # Main app layout
│   ├── admin.blade.php             # Admin layout
│   └── guest.blade.php             # Guest layout

├── frontend/                       # Public-facing pages
│   ├── home.blade.php              # Homepage
│   ├── about.blade.php
│   ├── products.blade.php          # Product listing
│   └── pages/                      # Dynamic pages

├── user/                           # User dashboard
│   ├── dashboard.blade.php
│   ├── profile.blade.php
│   ├── wallet.blade.php
│   ├── commissions.blade.php
│   ├── referrals.blade.php
│   └── tree.blade.php

├── seller/                         # Seller dashboard
│   ├── dashboard.blade.php
│   ├── products.blade.php
│   ├── orders.blade.php
│   └── analytics.blade.php

├── components/                     # Reusable components
│   ├── navbar.blade.php
│   ├── sidebar.blade.php
│   ├── card.blade.php
│   └── modal.blade.php

└── emails/                         # Email templates
    ├── order-confirmation.blade.php
    ├── withdrawal-approved.blade.php
    └── notification.blade.php
```

### JavaScript Assets
```
resources/js/
├── crypto/                         # Crypto wallet integration
│   ├── wallet.js
│   ├── network.js
│   └── transactions.js

├── mlm-genealogy.js               # Network visualization
├── tree-visualization.js           # Tree graph rendering
├── kyc-camera.js                   # KYC camera capture
└── tree-network.js                # Advanced network display
```

---

## 4. MARKETPLACE & PRODUCT LISTING FEATURES

### External Marketplace Integration
- **Connected Platforms**: Shopee, Lazada, Tokopay, AliExpress, Amazon, etc.
- **Platform Models**: MarketplacePlatform, MarketplaceAccount, MarketplaceProduct
- **Features**:
  - API credential management
  - Automatic product sync from marketplace APIs
  - Affiliate link generation
  - Commission rate configuration per product
  - Real-time inventory sync
  - Order tracking and commission calculation

### Internal Software Products System
- **Product Types**: MLM Systems, E-Commerce, Learning Platforms, Chat Bots
- **Customizable Options**:
  - Multiple input types: Select, Checkbox, Radio, Number, Text
  - Multi-level option grouping
  - Fixed or percentage-based pricing
  - Setup fees and monthly recurring fees
- **Quotation System**:
  - Real-time pricing calculation
  - Status tracking (draft, sent, accepted, rejected, converted)
  - Email delivery with PDF
  - Automatic quotation expiration (30 days)
- **Installment Plans**:
  - Flexible payment schedules
  - Interest rate configuration
  - Down payment requirement
  - Late payment penalties
  - Payment reminders

### Product Listing Features
- **Product Display**:
  - Image gallery with WebP optimization
  - SEO meta data (title, description)
  - Technical specifications
  - Feature highlights
  - Demo URL and documentation links
- **Analytics**:
  - View count tracking
  - Click count for affiliate links
  - Sales count per product
  - Rating and review management
- **Inventory Management**:
  - Stock quantity tracking
  - Low stock threshold alerts
  - Availability status

---

## 5. AUTHENTICATION & AUTHORIZATION

### Authentication Methods

#### Email/Password Authentication
- Local authentication with hashed passwords
- Password reset via email
- Remember me functionality
- Session-based (web) and token-based (API) authentication

#### LINE Official Account Integration
- LINE OA OAuth 2.0 flow
- User profile linking (display name, picture URL)
- Automatic account creation or linking
- Secure access token storage
- LINE login logging for audit trail

#### OTP (One-Time Password)
- Email and SMS OTP support
- Configurable OTP expiry (default 5 minutes)
- Rate limiting on OTP requests
- OTP-based signup flow
- Two-factor authentication integration

#### Two-Factor Authentication (2FA)
- Email-based 2FA
- LINE-based 2FA
- User preference configuration
- Challenge-response workflow
- Backup codes support

#### API Authentication
- **Framework**: Laravel Sanctum
- **Token Types**:
  - Personal access tokens for long-lived access
  - API tokens for third-party integrations
  - Session-based tokens for SPAs
- **Token Management**:
  - Token creation with optional expiry
  - Scoped permissions per token
  - Token revocation

### Authorization & Access Control

#### Role-Based Access Control (RBAC)
- **System Roles**:
  - super_admin - Full system access
  - admin - Administrative functions
  - seller - E-commerce seller access
  - affiliate - Affiliate/MLM member
  - user - Regular user access
- **Permissions**: Granular permission system with role assignment

#### Policies (Authorization Gates)
- **AccountingContactPolicy** - Accounting contact access control
- **AccountingExpensePolicy** - Expense management authorization
- **AccountingInvoicePolicy** - Invoice access control
- **AccountingProductPolicy** - Product accounting authorization
- **StakingPositionPolicy** - Investment position authorization

#### Middleware Security
- **CheckRole** - Role validation
- **CheckPermission** - Permission verification
- **CheckBlockedIp** - IP-based access blocking
- **VerifyLicenseIntegrity** - License validation
- **VerifyCloudfareTurnstile** - Bot protection (Turnstile CAPTCHA)
- **RequireTwoFactor** - 2FA enforcement
- **ThrottleLogin** - Login rate limiting
- **PaymentRateLimiter** - Payment endpoint rate limiting

#### Security Features
- **CSRF Protection** - VerifyCsrfToken middleware
- **IP Intelligence** - ProxyCheck API integration for fraud detection
- **Auto-Ban System**:
  - Failed login threshold-based blocking
  - Rate limit violation blocking
  - Suspicious activity detection
  - Configurable ban duration
- **Activity Logging** - SecurityLog model for audit trail
- **Session Management** - File/database-based sessions

---

## 6. FILE UPLOAD & STORAGE SYSTEM

### Storage Configuration
```php
// Configured Storage Disks
- 'local'  - Local file system (storage/app)
- 'public' - Public-facing files (storage/app/public -> public/storage)
- 's3'     - AWS S3 integration (optional)
```

### File Upload Service (ImageUploadService)
- **Supported Formats**: JPG, PNG, GIF, WebP
- **Processing**:
  - Automatic WebP conversion for optimization
  - Responsive resizing with aspect ratio preservation
  - Quality adjustment (default 85%)
  - Max dimensions: 1200x1200px (configurable)
- **Directory Organization**:
  - `products/` - Product images
  - `users/` - User profiles
  - `documents/` - Document storage
  - `software/` - Software product files

### WebP Conversion System
- **Job**: ConvertImagesToWebPJob
- **Command**: ConvertImagesToWebP
- **Features**:
  - Batch image conversion
  - Progressive processing queue
  - Fallback to original format if conversion fails
  - Statistics tracking in WebPConversionStat model
  - Database-driven tracking of conversion status

### File Handling
- **Database Tracking**: file paths stored as relative URLs
- **Security**: 
  - File validation by mime type
  - Virus scanning (optional)
  - Access control via policies
- **Cleanup**: 
  - Orphaned file detection
  - Automatic deletion on model deletion
  - Storage optimization routines

### Supported Uploads
- **Profile Pictures**: User avatars and profile images
- **Product Images**: Product gallery and featured images
- **Documents**: PDF, Word, Excel for various systems
- **Knowledge Base**: PDF, TXT, DOCX, CSV for AI knowledge base
- **LINE Bot Assets**: Avatar images, GIF, Lottie animations, videos
- **KYC Documents**: ID card images for verification
- **Hotel Images**: Room and facility photos
- **Software Images**: Product and feature images

---

## 7. CORE SERVICES & BUSINESS LOGIC

### AI & Bot Services
- **AI Service** (/app/Services/AI/)
  - Multiple AI provider integration (OpenAI, DeepSeek, Anthropic, Gemini)
  - Knowledge base management and retrieval
  - Conversation history tracking
  - Token usage calculation and cost tracking
  - RAG (Retrieval-Augmented Generation) implementation

- **LINE Bot AI Service** (/app/Services/LineBotAiService.php)
  - AI provider configuration management
  - Knowledge base integration
  - Conversation context management
  - Fallback handling for failed AI responses
  - Flex message and rich menu integration

### Marketplace Services
- **BaseMarketplaceService** - Abstract marketplace API integration
- **MarketplaceFactory** - Platform-specific service instantiation
- **MarketplaceSyncService** - Product and order synchronization
- **MarketplaceCommissionService** - Commission calculation for marketplace sales

### Financial Services
- **WalletService** - User wallet management
  - Balance tracking and updates
  - PIN security
  - Transaction processing
  - Withdrawal request management
  
- **WithdrawalService** - Withdrawal processing
  - Request validation
  - Status tracking
  - Payment method integration
  - Audit logging

- **PaymentGatewayService** - Multi-gateway integration
  - PaySolutions integration
  - PromptPay (Thai QR code payment)
  - Stripe integration
  - Omise integration
  - Gateway credential management

- **CashbackService** - Cashback calculation and distribution
  - Per-product cashback rates
  - Transaction tracking
  - User balance updates
  - Refund reversals

### MLM & Commission Services
- **MlmCalculationService** - Commission calculation engine
  - Direct commission (immediate downline)
  - Indirect commission (multi-level, configurable depth)
  - Bonus commissions based on achievements
  - Commission cap implementation
  
- **MlmGenealogyService** - Family tree management
  - Parent-child relationships
  - Genealogy tree traversal
  - Depth calculation
  - Network analysis

- **MlmBinaryService** - Binary tree operations
  - Left/right position assignment
  - Spillover handling
  - Binary tree traversal
  - Balance calculation

- **MlmUnilevelService** - Unilevel plan operations
  - Flat depth bonus distribution
  - Volume accumulation across levels
  - Downline aggregation

- **MlmProspectService** - Lead management
  - Prospect tracking and follow-up
  - Conversion to member
  - Lead analytics
  - Sales pipeline management

- **MlmPvService** - Point Value (PV) system
  - PV assignment to products
  - Transaction-based PV tracking
  - Volume reports
  - Qualification tracking

### Accounting & Business Services
- **AccountingService** - Double-entry bookkeeping
  - Journal entry creation
  - Account ledger management
  - Trial balance reporting
  - Financial statement generation

- **InvoiceService** - Invoice management
  - Invoice generation from orders
  - Payment tracking
  - Overdue notification
  - Invoice reconciliation

- **PayrollService** - Employee payroll
  - Salary calculation
  - Deduction processing
  - Payslip generation
  - Tax calculation

- **QuotationCalculatorService** - Quotation pricing
  - Real-time price calculation
  - Option-based pricing
  - Discount application
  - Tax calculation (VAT)

### User & Content Services
- **RankingService** - Rank advancement
  - Requirement verification
  - Rank promotion processing
  - Leaderboard calculation
  - Achievement tracking

- **TranslationService** - Multi-language support
  - Google Translate API integration
  - Translation caching
  - Batch translation
  - Language detection

- **NotificationService** - Notification management
  - In-app notification creation
  - Email notification dispatch
  - LINE notification sending
  - Notification scheduling
  - Template rendering

- **EmailService** - Email delivery
  - SMTP integration (Gmail, custom)
  - Email template rendering
  - Attachment handling
  - Delivery tracking
  - Retry mechanism
  - Rate limiting

- **OtpService** - OTP generation and verification
  - Secure OTP generation
  - Email/SMS delivery
  - Expiration handling
  - Attempt limiting
  - Audit logging

- **TwoFactorService** - 2FA workflow
  - Challenge generation
  - Response verification
  - Backup code management
  - Device trust tracking

### Special Features
- **HotelBookingService** - Hotel reservation system
- **RentalService** - Equipment/bot rental management
- **InvestmentService** - Investment product handling
- **StakingService** - Crypto staking position management
- **LineService** - LINE OA API integration
- **LineSignupService** - LINE signup flow management
- **ThemeService** - Dynamic theme management
- **UpdateService** - System update checking
- **WebPService** - WebP image optimization
- **IntegrityService** - System integrity checking
- **IpIntelligenceService** - IP reputation checking

---

## 8. JOBS & BACKGROUND PROCESSING

### Queue Jobs
- **ConvertImagesToWebPJob** - Batch WebP conversion
- **ProcessOrderCashback** - Cashback calculation and distribution
- **ReverseCashbackOnRefund** - Cashback reversal on order refund

### Console Commands
- **LicenseActivateCommand** - License activation
- **LicenseCheckCommand** - License validation
- **BumpVersionCommand** - Version increment
- **UpdateCommand** - System update
- **CheckUpdateCommand** - Update availability check
- **OptimizeCommand** - System optimization
- **IntegrityCheckCommand** - System integrity verification
- **ConvertImagesToWebP** - Image conversion
- **FixStorageLinks** - Storage symlink repair
- **ExpireInactiveMemberships** - Membership expiration
- **NotifyExpiringMemberships** - Membership expiration alerts

---

## 9. MIDDLEWARE PIPELINE

### HTTP Middleware Stack
1. **TrustProxies** - Proxy header handling
2. **HandleCors** - CORS support
3. **PreventRequestsDuringMaintenance** - Maintenance mode
4. **ValidatePostSize** - Post size validation
5. **TrimStrings** - String trimming
6. **ConvertEmptyStringsToNull** - Empty string handling
7. **CheckBlockedIp** - IP blocking check
8. **VerifyLicenseIntegrity** - License validation
9. **LoadTheme** - Theme loading
10. **SetLocale** - Language detection
11. **VerifyCloudfareTurnstile** - Bot protection
12. **RequireTwoFactor** - 2FA enforcement
13. **RedirectIfAuthenticated** - Guest redirect
14. **ThrottleLogin** - Login rate limiting
15. **PaymentRateLimiter** - Payment rate limiting
16. **CheckRole** - Role validation
17. **CheckPermission** - Permission checking

### Route Middleware Groups
- **web** - Web application routes (session, cookies, CSRF)
- **api** - API routes (Sanctum authentication)
- **admin** - Admin panel routes (role verification)
- **seller** - Seller dashboard routes
- **user** - User dashboard routes

---

## 10. DEPLOYMENT & CONFIGURATION

### Environment Configuration
```php
// Critical Settings
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database (or redis)
CACHE_DRIVER=redis (recommended for performance)

// Database
DB_CONNECTION=mysql
DB_HOST=production-host
DB_DATABASE=thaiprompt_affiliate

// Payment Gateways
PAYSOLUTIONS_API_KEY=...
STRIPE_PUBLIC_KEY=...
OMISE_SECRET_KEY=...

// LINE Integration
LINE_OA_CHANNEL_ID=...
LINE_OA_CHANNEL_SECRET=...
LINE_MESSAGING_CHANNEL_ID=...
LINE_MESSAGING_CHANNEL_SECRET=...

// AI Providers
OPENAI_API_KEY=...
DEEPSEEK_API_KEY=...
ANTHROPIC_API_KEY=...
GOOGLE_GEMINI_API_KEY=...

// Security
CLOUDFLARE_TURNSTILE_SITE_KEY=...
CLOUDFLARE_TURNSTILE_SECRET_KEY=...
PROXYCHECK_API_KEY=...

// Email Delivery
MAIL_MAILER=smtp
GMAIL_API_ENABLED=true
GMAIL_API_CREDENTIALS_JSON=...

// Storage
FILESYSTEM_DISK=s3
AWS_BUCKET=production-bucket
```

---

## 11. SYSTEM REQUIREMENTS

### Server Requirements
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Redis**: Optional but recommended
- **Node.js**: 18+ (for Vite build)
- **Composer**: 2.0+

### Key Libraries
- laravel/framework ^11.0
- laravel/sanctum ^4.0
- intervention/image ^3.11
- google/cloud-translate ^1.15
- guzzlehttp/guzzle ^7.2
- web3p/web3.php ^0.1.6
- jenssegers/agent ^2.6

### Supported Integrations
- Payment: PaySolutions, Stripe, Omise, PromptPay
- Cloud: Google Translate, Google Vision (OCR)
- Communication: LINE Official Account, SMS, Email
- Crypto: Web3, Ethereum, ERC-20 tokens
- Storage: Local filesystem, AWS S3
- Marketplace: Shopee, Lazada, Tokopay API

---

## 12. KEY ARCHITECTURAL PATTERNS

### Design Patterns Used
- **Service Layer Pattern** - Business logic separation
- **Repository Pattern** - Data access abstraction
- **Factory Pattern** - Object creation (MarketplaceFactory)
- **Observer Pattern** - Model observers for side effects
- **Middleware Pipeline** - Request/response handling
- **Policy-based Authorization** - Granular access control
- **Event-driven Architecture** - Model events and listeners

### Data Flow Architecture
1. **Request Entry** → Middleware pipeline
2. **Route Matching** → Controller dispatch
3. **Authorization** → Policy/Gate validation
4. **Validation** → Request validation rules
5. **Service Processing** → Business logic execution
6. **Database Transaction** → Model operations
7. **Side Effects** → Jobs/Events/Notifications
8. **Response** → JSON/View rendering

### Performance Optimization
- **Caching**: Query result caching, view caching
- **Database**: Indexed columns, relationship eager loading, soft deletes
- **Images**: WebP conversion, responsive sizing
- **API**: Rate limiting, request throttling
- **Queue**: Async job processing for heavy operations
- **CDN**: Static asset delivery via public/storage

---

## SUMMARY

The **Thaiprompt-Affiliate** system is a comprehensive enterprise platform built on **Laravel 11** featuring:

✅ **Advanced MLM System** - Unilevel, Binary, Matrix, and Hybrid plans with commission calculation  
✅ **E-Commerce Integration** - Multiple marketplace synchronization with affiliate linking  
✅ **Software Sales** - Customizable product quotations with installment payment plans  
✅ **AI Bot Marketplace** - AI provider integration with knowledge base and conversation history  
✅ **LINE Integration** - Official Account chatbot with Flex Messages, Rich Menus, and Broadcast  
✅ **Financial System** - Wallet management, crypto support, multiple payment gateways  
✅ **Learning Platform** - LMS with courses, quizzes, and certifications  
✅ **Accounting System** - Double-entry bookkeeping with GL and financial reports  
✅ **Security** - RBAC, 2FA, IP blocking, auto-ban system, license validation  
✅ **Multi-language** - Google Translate integration with caching  
✅ **Analytics** - Comprehensive tracking, reporting, and dashboards  

