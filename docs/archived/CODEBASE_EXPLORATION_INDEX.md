# Thaiprompt-Affiliate Codebase Exploration Index

Complete architectural analysis and implementation planning for bot automation system.

## Generated Documentation

All documentation files have been created in the project root directory:

### 1. **COMPREHENSIVE_ARCHITECTURE_OVERVIEW.md** (872 lines)
Complete system architecture documentation covering:
- Database schema & models (105+ models)
- API routes structure (40+ routes)
- Frontend components (Vite, Tailwind, Alpine.js)
- Marketplace & product features
- Authentication & authorization
- File upload & storage system
- Core services & business logic (30+ services)
- Jobs & background processing
- Middleware pipeline (17 middleware)
- Deployment configuration
- System requirements
- Architectural patterns

**File**: `/home/user/Thaiprompt-Affiliate/COMPREHENSIVE_ARCHITECTURE_OVERVIEW.md`

### 2. **BOT_AUTOMATION_IMPLEMENTATION_PLAN.md** (500+ lines)
Detailed implementation strategy for bot automation system:
- Current bot & automation infrastructure analysis
- Phase-based implementation strategy (4 phases, 4 weeks)
- Database schema design
- Service architecture
- Workflow management system
- Advanced features roadmap
- Technical architecture diagrams
- Security considerations
- Implementation checklist
- Timeline & effort estimation
- Success metrics

**File**: `/home/user/Thaiprompt-Affiliate/BOT_AUTOMATION_IMPLEMENTATION_PLAN.md`

### 3. **ARCHITECTURE_EXPLORATION_SUMMARY.txt** (416 lines)
Quick reference summary with key statistics and findings:
- Key findings overview
- Documentation created
- Critical directories & files
- Key statistics
- Bot automation readiness assessment
- Recommended next steps
- Project maturity evaluation

**File**: `/home/user/Thaiprompt-Affiliate/ARCHITECTURE_EXPLORATION_SUMMARY.txt`

---

## Project Statistics

| Metric | Count |
|--------|-------|
| Eloquent Models | 105+ |
| Controllers | 70+ |
| Services | 30+ |
| Database Migrations | 160+ |
| Blade Templates | 100+ |
| API Routes | 40+ |
| Middleware Components | 17 |
| Configuration Files | 18 |
| Database Tables | 140+ |
| Lines of Code (estimate) | 50,000+ |

---

## Key Directory Locations

### Application Code
- **Models**: `/home/user/Thaiprompt-Affiliate/app/Models/`
- **Controllers**: `/home/user/Thaiprompt-Affiliate/app/Http/Controllers/`
- **Services**: `/home/user/Thaiprompt-Affiliate/app/Services/`
- **Middleware**: `/home/user/Thaiprompt-Affiliate/app/Http/Middleware/`
- **Policies**: `/home/user/Thaiprompt-Affiliate/app/Policies/`
- **Console Commands**: `/home/user/Thaiprompt-Affiliate/app/Console/Commands/`
- **Jobs**: `/home/user/Thaiprompt-Affiliate/app/Jobs/`

### Database
- **Migrations**: `/home/user/Thaiprompt-Affiliate/database/migrations/`
- **Seeders**: `/home/user/Thaiprompt-Affiliate/database/seeders/`

### Configuration
- **Config Files**: `/home/user/Thaiprompt-Affiliate/config/`
- **Environment**: `/home/user/Thaiprompt-Affiliate/.env.example`

### Frontend
- **Views**: `/home/user/Thaiprompt-Affiliate/resources/views/`
- **JavaScript**: `/home/user/Thaiprompt-Affiliate/resources/js/`
- **Storage**: `/home/user/Thaiprompt-Affiliate/public/storage/`

### Routes
- **Web Routes**: `/home/user/Thaiprompt-Affiliate/routes/web.php`
- **API Routes**: `/home/user/Thaiprompt-Affiliate/routes/api.php`
- **Admin Routes**: `/home/user/Thaiprompt-Affiliate/routes/admin.php` (92KB)
- **Seller Routes**: `/home/user/Thaiprompt-Affiliate/routes/seller.php`
- **Software Sales**: `/home/user/Thaiprompt-Affiliate/routes/software_sales.php`

---

## Core Models & Database Schema

### Affiliate System
- **User** (`/home/user/Thaiprompt-Affiliate/app/Models/User.php`)
- **Affiliate** (`/home/user/Thaiprompt-Affiliate/app/Models/Affiliate.php`)
- **Commission** (`/home/user/Thaiprompt-Affiliate/app/Models/Commission.php`)
- **Rank** (`/home/user/Thaiprompt-Affiliate/app/Models/Rank.php`)

### Marketplace System
- **MarketplacePlatform** (`/home/user/Thaiprompt-Affiliate/app/Models/MarketplacePlatform.php`)
- **MarketplaceAccount** (`/home/user/Thaiprompt-Affiliate/app/Models/MarketplaceAccount.php`)
- **MarketplaceProduct** (`/home/user/Thaiprompt-Affiliate/app/Models/MarketplaceProduct.php`)
- **MarketplaceAffiliateLink** (`/home/user/Thaiprompt-Affiliate/app/Models/MarketplaceAffiliateLink.php`)

### Software Sales System
- **SoftwareProduct** (`/home/user/Thaiprompt-Affiliate/app/Models/SoftwareProduct.php`)
- **SoftwareQuotation** (`/home/user/Thaiprompt-Affiliate/app/Models/SoftwareQuotation.php`)
- **InstallmentPlan** (`/home/user/Thaiprompt-Affiliate/app/Models/InstallmentPlan.php`)

### AI & Bot System
- **AiBotProfile** (`/home/user/Thaiprompt-Affiliate/app/Models/AiBotProfile.php`)
- **AiBotRental** (`/home/user/Thaiprompt-Affiliate/app/Models/AiBotRental.php`)
- **AiConversation** (`/home/user/Thaiprompt-Affiliate/app/Models/AiConversation.php`)
- **AiUsageLog** (`/home/user/Thaiprompt-Affiliate/app/Models/AiUsageLog.php`)
- **LineBotAiSetting** (`/home/user/Thaiprompt-Affiliate/app/Models/LineBotAiSetting.php`)

### Complete List
All 105+ models are in: `/home/user/Thaiprompt-Affiliate/app/Models/`

---

## API Routes Summary

### Base URL: `/api/v1/`

**Public Routes** (no authentication)
```
GET  /login              - User authentication
GET  /settings           - Public app settings
GET  /ranks              - Public rank information
GET  /app/maintenance-status
GET  /app/check-update
```

**Protected Routes** (Sanctum authentication)
```
Dashboard:
  GET  /dashboard/statistics
  GET  /dashboard/commissions
  GET  /dashboard/referrals

Tree:
  GET  /tree/user
  GET  /tree/admin/{id}
  GET  /tree/binary
  GET  /tree/binary/admin/{id}

Ranks:
  GET  /ranks
  GET  /ranks/{rank}
  GET  /ranks/user/progress
  GET  /ranks/leaderboard
  GET  /ranks/user/eligibility
  POST /ranks/promotions/request

Investments:
  GET  /investments/plans
  GET  /investments/plans/{plan}
  POST /investments/calculate-roi
  POST /investments/invest
  GET  /investments/summary
  GET  /investments/positions
  POST /investments/{position}/withdraw

Crypto:
  POST /crypto/verify-signature
  GET  /crypto/balances
  GET  /crypto/address/{currency}
  GET  /crypto/prices
  GET  /crypto/transaction/{txHash}
  GET  /crypto/gas-price

App:
  GET  /app/config
  GET  /app/settings
  GET  /app/theme
  GET  /app/features
  GET  /app/banners
```

**Webhooks** (webhook verification middleware)
```
POST /webhook/paysolutions
POST /webhook/promptpay
POST /webhook/stripe
POST /webhook/omise
POST /webhook/line
```

Full routes in: `/home/user/Thaiprompt-Affiliate/routes/api.php` (135 lines)

---

## Core Services

Located in: `/home/user/Thaiprompt-Affiliate/app/Services/`

### AI & Bot Services
- **AI Services** (`/home/user/Thaiprompt-Affiliate/app/Services/AI/`)
  - Multiple AI provider integration
  - Knowledge base management
  - RAG implementation

- **LineBotAiService** - LINE Bot AI integration
- **AiProviderService** - AI provider management

### Financial Services
- **WalletService** - Wallet management
- **WithdrawalService** - Withdrawal processing
- **PaymentGatewayService** - Payment integration
- **CashbackService** - Cashback calculation

### MLM & Commission Services
- **MlmCalculationService** - Commission engine
- **MlmGenealogyService** - Family tree
- **MlmBinaryService** - Binary tree operations
- **MlmUnilevelService** - Unilevel calculations
- **MlmProspectService** - Lead management

### Marketplace Services
- **BaseMarketplaceService** (`/home/user/Thaiprompt-Affiliate/app/Services/Marketplace/BaseMarketplaceService.php`)
- **MarketplaceFactory** - Platform-specific services
- **MarketplaceSyncService** - Product sync
- **MarketplaceCommissionService** - Commission calculation

### Other Services
- **EmailService** - Email delivery with tracking
- **NotificationService** - Notification management
- **OtpService** - OTP generation/verification
- **TwoFactorService** - 2FA workflow
- **TranslationService** - Google Translate integration
- **ImageUploadService** - File upload with WebP conversion
- **HotelBookingService** - Hotel reservations
- **RentalService** - Bot rental management
- **LineService** - LINE OA integration
- **ThemeService** - Theme management
- **UpdateService** - Update checking

Complete list in: `/home/user/Thaiprompt-Affiliate/app/Services/`

---

## Controllers

Located in: `/home/user/Thaiprompt-Affiliate/app/Http/Controllers/`

### Admin Controllers
Path: `/home/user/Thaiprompt-Affiliate/app/Http/Controllers/Admin/`
- Dashboard, User Management, Affiliate Management
- Commission Management, Product Management
- Order Management, Settings, Reports
- LINE Bot Configuration, AI Bot Management
- Accounting System, HR Management
- And 30+ more specialized controllers

### API Controllers
Path: `/home/user/Thaiprompt-Affiliate/app/Http/Controllers/Api/`
- V1 API controllers
- AuthController, DashboardController
- RankController, InvestmentController
- CryptoWalletApiController
- SoftwareProductController
- AppConfigController

### Seller Controllers
- Product management
- Order management
- Analytics

### User Controllers
- Dashboard
- Profile
- Wallet
- Commissions

---

## Authentication & Security

### Models
- **User** - Main authentication model with RBAC fields
- **OtpVerification** - OTP authentication records
- **OtpSetting** - System OTP configuration
- **TwoFactorSetting** - 2FA system settings
- **TwoFactorUserSetting** - User 2FA preferences
- **KycVerification** - KYC verification status
- **SecurityLog** - Activity audit trail
- **BlockedIp** - IP blocking management

### Middleware
Located in: `/home/user/Thaiprompt-Affiliate/app/Http/Middleware/`

Key middleware:
- **CheckRole** - Role validation
- **CheckPermission** - Permission verification
- **CheckBlockedIp** - IP-based access blocking
- **VerifyLicenseIntegrity** - License validation
- **VerifyCloudfareTurnstile** - Bot protection
- **RequireTwoFactor** - 2FA enforcement
- **ThrottleLogin** - Login rate limiting
- **PaymentRateLimiter** - Payment endpoint limiting

### Policies
Located in: `/home/user/Thaiprompt-Affiliate/app/Policies/`
- AccountingContactPolicy
- AccountingExpensePolicy
- AccountingInvoicePolicy
- AccountingProductPolicy
- StakingPositionPolicy

---

## Database Migrations

Located in: `/home/user/Thaiprompt-Affiliate/database/migrations/`

160+ migration files including:

### Core Migrations
- 2024_01_01_000001 - Users table
- 2024_01_01_000002 - Affiliates table
- 2024_01_01_000003 - Commissions table
- 2024_01_01_000004 - Settings table

### Marketplace Migrations
- 2025_11_08_133224 - Marketplace platforms
- 2025_11_08_133225 - Marketplace accounts
- 2025_11_08_133226 - Marketplace products
- 2025_11_08_133227 - Marketplace affiliate links
- 2025_11_08_133235 - Marketplace commissions

### Software Sales Migrations
- 2025_11_08_140001 - Software product categories
- 2025_11_08_140002 - Software products
- 2025_11_08_140003 - Software product options
- 2025_11_08_140008 - Installment plans

### AI & Bot Migrations
- 2025_11_03_060001 - AI providers
- 2025_11_03_060003 - AI bot profiles
- 2025_11_03_060004 - AI bot rentals
- 2025_11_02_100001 - LINE bot AI settings
- 2025_11_02_100002 - LINE bot knowledge bases

---

## File Upload System

**ImageUploadService** (`/home/user/Thaiprompt-Affiliate/app/Services/ImageUploadService.php`)

Features:
- WebP conversion with quality control
- Responsive image resizing
- Aspect ratio preservation
- Multiple directory organization
- Supported formats: JPG, PNG, GIF, WebP

Usage:
```php
// Upload single image
$uploadedPath = $imageUploadService->uploadImage(
    $file,
    'products',        // directory
    1200,             // maxWidth
    1200,             // maxHeight
    85                // quality
);

// Upload multiple images
$uploadedPaths = $imageUploadService->uploadMultiple($files, 'products');
```

Directories:
- `products/` - Product images
- `users/` - User profiles
- `documents/` - Document storage
- `software/` - Software files

---

## Job Queue System

Located in: `/home/user/Thaiprompt-Affiliate/app/Jobs/`

Jobs:
- **ConvertImagesToWebPJob** - Batch image conversion
- **ProcessOrderCashback** - Cashback calculation
- **ReverseCashbackOnRefund** - Refund reversal

Configuration:
- **Queue Driver**: Database (configurable to Redis)
- **Queue Commands**: 
  - `queue:work` - Process jobs
  - `queue:failed` - View failed jobs
  - `queue:retry` - Retry failed jobs

---

## Console Commands

Located in: `/home/user/Thaiprompt-Affiliate/app/Console/Commands/`

Key commands:
- **LicenseActivateCommand** - Activate license
- **LicenseCheckCommand** - Validate license
- **BumpVersionCommand** - Increment version
- **UpdateCommand** - System update
- **ConvertImagesToWebP** - Image conversion
- **ExpireInactiveMemberships** - Membership expiration
- **OptimizeCommand** - System optimization
- **IntegrityCheckCommand** - Integrity verification

Usage:
```bash
php artisan license:activate
php artisan license:check
php artisan convert:images-webp
php artisan expire:memberships
```

---

## Frontend Structure

### Views
Path: `/home/user/Thaiprompt-Affiliate/resources/views/`

Main directories:
- `admin/` - Admin panel (40+ views)
- `auth/` - Authentication (login, register)
- `layouts/` - Layout templates
- `frontend/` - Public pages
- `user/` - User dashboard
- `seller/` - Seller dashboard
- `components/` - Reusable components
- `emails/` - Email templates

### JavaScript
Path: `/home/user/Thaiprompt-Affiliate/resources/js/`

Assets:
- `crypto/` - Crypto wallet integration
- `mlm-genealogy.js` - Network visualization
- `tree-visualization.js` - Tree rendering
- `kyc-camera.js` - KYC camera
- `tree-network.js` - Advanced network display

### Build System
- **Tool**: Vite 5.0
- **CSS**: Tailwind CSS 3.4.1
- **JS Framework**: Alpine.js 3.13.5
- **Visualization**: D3.js, Chart.js, vis-network
- **Animation**: GSAP 3.12.5
- **Crypto**: Web3modal, wagmi, ethers.js, viem

---

## Bot Automation Readiness

### Already Implemented
✓ Database schema for AI bots
✓ Multi-AI provider system
✓ LINE Bot AI integration
✓ Queue system for async processing
✓ Webhook infrastructure
✓ Payment integration
✓ RBAC system
✓ Notification framework
✓ File upload system

### Ready for Implementation
✓ Bot Scheduling & Automation Framework
✓ Workflow Engine
✓ Execution Monitoring
✓ Bot Templates
✓ Marketplace Integration
✓ Advanced Features

### Implementation Timeline
- Phase 1: 2 weeks (Scheduling & Framework)
- Phase 2: 2 weeks (Workflow System)
- Phase 3: 2 weeks (Advanced Features)
- Phase 4: 1 week (Integration & Deployment)
- **Total: 4 weeks**

See: `/home/user/Thaiprompt-Affiliate/BOT_AUTOMATION_IMPLEMENTATION_PLAN.md`

---

## Configuration Files

Located in: `/home/user/Thaiprompt-Affiliate/config/`

Key files:
- `app.php` - Application settings
- `auth.php` - Authentication (17 lines)
- `autoban.php` - Auto-ban configuration
- `cache.php` - Caching system
- `crypto.php` - Cryptocurrency settings
- `database.php` - Database connection
- `email.php` - Email configuration
- `flowaccount.php` - Flow Account integration
- `license.php` - License configuration
- `ratelimit.php` - Rate limiting
- `sanctum.php` - API token configuration
- `services.php` - External services
- `session.php` - Session configuration
- `translate.php` - Translation settings
- `turnstile.php` - Cloudflare Turnstile
- `version.php` - Version configuration

---

## Environment Configuration

See: `/home/user/Thaiprompt-Affiliate/.env.example`

Key variables:
```
# Basic
APP_NAME, APP_ENV, APP_DEBUG, APP_URL

# Database
DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Cache & Queue
CACHE_DRIVER, QUEUE_CONNECTION, REDIS_HOST, REDIS_PORT

# Mail
MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME

# Payment Gateways
PAYSOLUTIONS_API_KEY, STRIPE_PUBLIC_KEY, OMISE_SECRET_KEY

# LINE Integration
LINE_OA_CHANNEL_ID, LINE_OA_CHANNEL_SECRET
LINE_MESSAGING_CHANNEL_ID, LINE_MESSAGING_CHANNEL_SECRET

# AI Providers
OPENAI_API_KEY, DEEPSEEK_API_KEY, ANTHROPIC_API_KEY, GOOGLE_GEMINI_API_KEY

# Security
CLOUDFLARE_TURNSTILE_SITE_KEY, CLOUDFLARE_TURNSTILE_SECRET_KEY
PROXYCHECK_API_KEY

# Storage
FILESYSTEM_DISK, AWS_BUCKET
```

---

## Supported Integrations

### Payment Gateways (4)
- PaySolutions
- Stripe
- Omise
- PromptPay (Thai QR code)

### Cloud Services (3)
- Google Translate API
- Google Vision (OCR)
- AWS S3

### Communication (3)
- LINE Official Account
- Email (SMTP, Gmail API)
- SMS (optional)

### Crypto (2)
- Web3 / Ethereum
- ERC-20 tokens

### Marketplaces (5+)
- Shopee
- Lazada
- Tokopay
- AliExpress (ready)
- Amazon (ready)

### AI Providers (4+)
- OpenAI (ChatGPT-4, GPT-3.5)
- DeepSeek (DeepSeek-V2)
- Anthropic (Claude-3)
- Google Gemini
- Custom API support

---

## Architecture Patterns Used

- **Service Layer Pattern** - Business logic separation
- **Repository Pattern** - Data abstraction (Eloquent models)
- **Factory Pattern** - Object creation (MarketplaceFactory)
- **Observer Pattern** - Model observers
- **Middleware Pipeline** - Request/response handling
- **Policy-based Authorization** - Granular access control
- **Event-driven Architecture** - Model events

---

## Performance Features

- **Caching**: Query caching, view caching, Redis support
- **Database**: Indexed columns, eager loading, soft deletes
- **Images**: WebP conversion, responsive sizing
- **API**: Rate limiting, request throttling
- **Queue**: Async job processing
- **CDN**: Static asset delivery

---

## Security Features

- **CSRF Protection** - VerifyCsrfToken middleware
- **IP Intelligence** - ProxyCheck API integration
- **Auto-Ban System** - Threshold-based blocking
- **Rate Limiting** - Login, payment, API endpoints
- **License Validation** - System integrity checks
- **Bot Protection** - Cloudflare Turnstile CAPTCHA
- **Activity Logging** - SecurityLog model
- **Access Control** - RBAC + Policies

---

## Project Maturity Assessment

| Aspect | Status | Notes |
|--------|--------|-------|
| Architecture | Production-Ready | Service-oriented with clean separation |
| Code Organization | Excellent | Well-structured, clear hierarchy |
| Database Design | Comprehensive | 140+ tables with proper relationships |
| API Structure | RESTful | Sanctum authentication, proper routes |
| Security | Strong | RBAC, 2FA, IP blocking, auto-ban |
| Performance | Optimized | Caching, queue jobs, image optimization |
| Scalability | Good | Modular design, ready for growth |
| Documentation | Good | Existing docs + comprehensive new docs |
| Testing | Partial | PHPUnit setup available |
| Monitoring | Good | SecurityLog, analytics, metrics |

**Overall Maturity**: Production-Ready for bot automation implementation

---

## Recommended Reading Order

1. Start: `/home/user/Thaiprompt-Affiliate/ARCHITECTURE_EXPLORATION_SUMMARY.txt`
2. Reference: `/home/user/Thaiprompt-Affiliate/COMPREHENSIVE_ARCHITECTURE_OVERVIEW.md`
3. Implementation: `/home/user/Thaiprompt-Affiliate/BOT_AUTOMATION_IMPLEMENTATION_PLAN.md`
4. Code Review: Navigate to specific files using paths provided above

---

## Quick Command Reference

```bash
# Install dependencies
composer install
npm install

# Build frontend assets
npm run build
npm run dev

# Database operations
php artisan migrate
php artisan db:seed
php artisan migrate:rollback

# Queue operations
php artisan queue:work
php artisan queue:failed
php artisan queue:retry

# System operations
php artisan license:check
php artisan convert:images-webp
php artisan optimize
php artisan cache:clear

# Development
php artisan tinker
php artisan serve

# Testing
php artisan test
php artisan test --filter=SpecificTest
```

---

## Next Steps for Bot Automation

1. **Review Documentation**
   - Read COMPREHENSIVE_ARCHITECTURE_OVERVIEW.md
   - Read BOT_AUTOMATION_IMPLEMENTATION_PLAN.md

2. **Database Planning**
   - Design bot automation tables
   - Plan model relationships
   - Review migration strategy

3. **Service Architecture**
   - Design BotAutomationService
   - Plan workflow execution engine
   - Consider error handling

4. **Frontend Implementation**
   - Design management UI
   - Build workflow builder
   - Create monitoring dashboard

5. **Testing & Deployment**
   - Write unit tests
   - Integration tests
   - Load testing
   - Deployment planning

---

**Generated**: 2025-11-08  
**Version**: 2.90.0  
**Framework**: Laravel 11 + Vite + Tailwind CSS  
**Status**: Ready for bot automation implementation
