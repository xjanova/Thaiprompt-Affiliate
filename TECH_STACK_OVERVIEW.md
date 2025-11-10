# Thaiprompt-Affiliate: Technology Stack & Architecture Overview

## Executive Summary
**TP-Affiliate** is a sophisticated Laravel 11 + Vite + Tailwind CSS platform built as a professional affiliate marketing and MLM system. Version 2.110.1 currently with 150+ database tables supporting complex business logic including e-commerce, cryptocurrency, AI bot marketplace, and enterprise features.

---

## 1. CURRENT TECH STACK

### Backend
- **Framework:** Laravel 11.0 (Latest)
- **PHP:** 8.1+ required
- **Database:** MySQL 8.0+ or MariaDB 10.3+
- **ORM:** Eloquent (with 105+ Models)
- **API:** REST API with Laravel Sanctum authentication
- **Queue System:** Configurable (sync, database, redis, beanstalkd, sqs)
- **Cache:** File, Database, Redis, or Memcached support

### Frontend
- **Build Tool:** Vite 5.0
- **CSS Framework:** Tailwind CSS 3.4.1
- **JavaScript Framework:** Alpine.js 3.13.5
- **Visualization Libraries:**
  - D3.js 7.9.0 (Network graphs, tree visualization)
  - Chart.js 4.4.1 (Analytics charts)
  - vis-network 10.0.2 (Organization charts)
  - GSAP 3.12.5 (Animations)

### Blockchain/Web3
- **Ethers.js:** 5.8.0 (Ethereum interactions)
- **Web3Modal:** 3.5.7 (Multi-wallet support)
- **Wagmi Core:** 1.4.13 (Web3 utilities)
- **Web3.php:** 0.1.6 (PHP Web3 interactions)

### Third-Party Integrations
- **Google Cloud:** Translate API, Vision API (OCR)
- **LINE Official Account:** Full integration with messaging, rich menus, flex messages
- **Payment Gateways:**
  - PromptPay (Thai QR Code)
  - Bank Transfer
  - Credit Card (via Omise, Stripe)
  - Cash on Delivery (COD)
  - PaySolutions integration
- **Email Services:**
  - Gmail API
  - Gmail SMTP
  - Generic SMTP support
  - Email tracking (opens/clicks)
- **Image Processing:** Intervention Image 3.11
- **Security:** Cloudflare Turnstile (CAPTCHA alternative)
- **File Upload:** guzzlehttp/guzzle 7.2

### Development Tools
- **Linting:** Laravel Pint 1.0
- **Testing:** PHPUnit 11.0
- **Monitoring:** Faker, Mockery
- **User Agent Detection:** jenssegers/agent 2.6

### DevOps & CI/CD
- **Git Workflow:** GitHub Actions with automatic versioning
- **Deployment:** Shell scripts with automated backup/migration
- **Version Management:** Semantic versioning (MAJOR.MINOR.PATCH)

---

## 2. EXISTING MEDIA HANDLING & STREAMING FEATURES

### Current Media Support

#### 1. **Image Processing**
- **WebP Conversion System** - Automatic image optimization
  - `WebPService` - Converts images to WebP format
  - `WebPDatabaseUpdateService` - Batch processing
  - Database tracking in `webp_conversion_stats_table`
- **Intervention Image Library** - Dynamic image manipulation
- **Multiple image support:** Products, Hotels, Users, Sliders
- **SEO-optimized:** Image ALT text, responsive sizing

#### 2. **Video Support (Limited)**
- **Slider Videos:** Recent migration `2025_10_30_113351_add_video_support_to_sliders_table.php`
  - Media type support: images and videos
  - Used for homepage promotional content
- **POS System:** Advertisement support with video/image/HTML/promotional content
  - Seller POS Controller validates: `type: image,video,html,promotion`
- **LINE Bot:** Video message support in broadcasts
  - LineBroadcastMessage model supports: `message_type: text,flex,image,video`

#### 3. **File Upload Management**
- **ImageUploadService** - Centralized image handling
- **Storage:** `storage/app/` with public access via symlink
- **Asset Processing:** Vite handles CSS/JS splitting

### NO EXISTING LIVE STREAMING INFRASTRUCTURE

**Currently Missing:**
- No WebSocket/real-time broadcasting framework (Laravel Echo not configured)
- No RTMP/HLS streaming servers
- No live chat/real-time notifications system
- No streaming protocol handlers (RTMP, HLS, DASH)
- No adaptive bitrate streaming
- No CDN integration for live content
- No recording/archival system for live streams

---

## 3. CURRENT ARCHITECTURE & DEPLOYMENT SETUP

### Project Structure
```
Thaiprompt-Affiliate/
├── app/                          # Application code
│   ├── Console/Commands/         # Artisan commands
│   ├── Http/Controllers/         # 73+ controllers
│   │   ├── Admin/                # Admin dashboard (40+ controllers)
│   │   ├── Api/V1/               # REST API endpoints
│   │   ├── Auth/                 # Authentication (Login, Register, LINE)
│   │   ├── Frontend/             # Public pages
│   │   ├── Seller/               # E-commerce seller panel
│   │   ├── User/                 # User dashboard
│   │   └── Pos/                  # Point of Sale system
│   ├── Jobs/                     # Queue jobs
│   ├── Models/                   # 105+ Eloquent models
│   ├── Services/                 # 30+ business logic services
│   │   ├── AI/                   # AI bot management
│   │   ├── Crypto/               # Cryptocurrency handling
│   │   ├── Payment/              # Payment processing
│   │   ├── Email/                # Email delivery
│   │   ├── Marketplace/          # Marketplace operations
│   │   ├── Exchange/             # Trading exchange
│   │   ├── TradingEngine/        # Bot trading logic
│   │   ├── OCR/                  # ID card scanning
│   │   └── [Others]              # 20+ more services
│   └── Providers/                # Service providers
├── database/
│   ├── migrations/               # 150+ migrations
│   ├── seeders/                  # Database seeders
│   └── sql/                      # SQL scripts
├── routes/
│   ├── web.php                   # Web routes (public + auth)
│   ├── api.php                   # REST API routes
│   ├── admin.php                 # Admin routes
│   ├── seller.php                # Seller routes
│   ├── user.php                  # User routes
│   └── pos.php                   # POS routes
├── resources/
│   ├── views/                    # 100+ Blade templates
│   │   ├── admin/                # Admin dashboard views
│   │   ├── auth/                 # Auth templates
│   │   ├── frontend/             # Public pages
│   │   ├── seller/               # Seller dashboard
│   │   ├── user/                 # User area
│   │   └── components/           # Reusable components
│   ├── js/                       # JavaScript assets
│   │   └── crypto/               # Blockchain integration
│   └── css/                      # Tailwind CSS
├── config/                       # 19 configuration files
├── public/                       # Static assets
│   ├── images/
│   ├── icons/
│   └── storage/ (symlink)
├── storage/                      # Runtime storage
│   ├── app/                      # Uploaded files
│   ├── logs/                     # Application logs
│   └── backups/                  # Database backups
└── tests/                        # PHPUnit tests
```

### Database Architecture

**150+ Migrations** organized by domain:
- **Core System** (20 tables): Users, Settings, Permissions
- **Affiliate/MLM** (25 tables): Affiliates, Commissions, Ranks, Bonuses
- **E-Commerce** (30 tables): Products, Orders, Categories, Vendors
- **AI/Bot** (20 tables): Bot profiles, AI models, Conversations, Usage logs
- **LINE Integration** (12 tables): Bot settings, Conversations, Messages, Broadcasts
- **Crypto** (10 tables): Wallets, Transactions, Exchange rates
- **Payment** (15 tables): Gateways, Transactions, Withdrawals
- **Learning** (10 tables): Courses, Articles, Progress tracking
- **Trading** (20 tables): Bots, Strategies, Orders, Market data
- **Hotel Booking** (6 tables): Hotels, Rooms, Bookings, Reviews
- **Additional** (35 tables): POS, HRM, Tarot, Accounting, Tickets, etc.

### Authentication & Security

#### **Multi-Method Authentication**
1. **Email/Password** - Traditional login with rate limiting
2. **LINE Login** - OAuth integration with LINE official account
3. **Crypto Wallet** - Web3 signature verification (Ethers.js)
4. **OTP Verification** - Two-factor authentication with SMS/LINE support
5. **2FA System** - Optional enhanced security

#### **Security Layers**
- **Sanctum:** Laravel API token authentication
- **Rate Limiting:** 
  - Login: 5 attempts per 15 minutes
  - API: 60/120 per minute (guest/authenticated)
  - Password change: 3 attempts per 60 minutes
- **Auto-Ban System:** Automatic IP banning for suspicious activity
- **CAPTCHA:** Cloudflare Turnstile with configurable triggers
- **RBAC:** Role-based access control (user, seller, admin, super_admin)
- **Permission System:** Fine-grained permissions per role

### Deployment Architecture

#### **Ecosystem Structure**
```
Development Repo          Distribution Repo          License Server
(Thaiprompt-Affiliate)   (TP-Affiliate)             (TpLicense)
     ↓ Deploy                  ↓ Install                  ↓
[Full Source Code] ───→ [Production Ready] ────→ [Validates License]
[Dev Tools]              [No .env, secrets]         [WordPress Plugin]
[Testing]                [Optimized Assets]         [REST API]
```

#### **Deployment Methods**

**1. Automated GitHub Actions**
- Triggers on push to `main` or merged PRs
- Semantic versioning with commit parsing
- Automatic changelog generation (Thai/English)
- Backup database before migration
- Rollback support

**2. Manual Deployment Scripts**
```bash
./deploy.sh                    # Quick deployment
php artisan deploy             # Artisan command
./scripts/deploy-to-dist...    # To distribution repo
```

**3. Server Requirements**
- PHP 8.1+ with extensions (PDO, cURL, GD, OpenSSL, Mbstring)
- MySQL 5.7+ or MariaDB 10.3+
- Nginx/Apache with proper rewrite rules
- Redis (recommended for cache/sessions)
- Supervisor (for queue workers)
- Node.js + NPM (for asset building)

### Environment Configuration
- **19 config files** in `config/` directory:
  - `app.php` - Application settings
  - `database.php` - Multi-database support
  - `crypto.php` - Blockchain configurations
  - `email.php` - Email providers
  - `cache.php` - Cache drivers
  - `license.php` - License validation
  - `pagebuilder.php` - Page builder config
  - And 12 more...

### Backup & Disaster Recovery
- **Automatic database backup** before each deployment
- **Versioning:** VERSION file + git tags
- **Rollback capability:** Via git checkout to previous tag
- **Deployment logs:** Complete audit trail

---

## 4. EXISTING REAL-TIME FEATURES

### 1. **Notifications System** (Partial Real-Time)
- **In-App Notifications:**
  - Database-backed notification system
  - Scheduled notifications
  - Immediate popups (`show_immediately` flag)
  - `NotificationController::immediate()` endpoint
  - Status tracking (read/unread)
  
- **No WebSocket Implementation:**
  - No Laravel Echo configured
  - No Pusher/Ably integration
  - Polling-based or page refresh required

### 2. **LINE Integration** (Message Broadcasting)
- **Broadcast Messaging:** `LineBroadcastMessage` model
  - Schedule messages for later delivery
  - Target specific user segments
  - Supports text, flex, image, video formats
  - Draft/scheduled/sent/failed statuses
- **Real-Time Chat:** LINE Bot conversations
  - `LineBotConversation` & `LineBotMessage` models
  - Webhook-based message handling
  - Flex message template builder
  - Rich menu support

### 3. **Webhook Systems** (Event-Driven)
- **Payment Gateway Webhooks:**
  - PaySolutions webhook handler
  - PromptPay webhook handler
  - Stripe webhook handler
  - Omise webhook handler
- **LINE Webhook:**
  - `POST /api/webhook/line` endpoint
  - Message push handling
  - Account linking events

### 4. **Analytics Streaming** (Data Streaming, Not Live)
- **Real-time analytics with `response()->stream()`:**
  - `SellerAnalyticsController::analytics()` - CSV streaming
  - `AdminAnalyticsController::analyticsExport()` - Streaming data export
- **Not true streaming:** One-time data dumps to browser

### 5. **Polling-Based Updates**
- **API endpoints for periodic fetching:**
  - `/api/v1/dashboard/statistics` - Dashboard metrics
  - `/api/v1/dashboard/commissions` - Commission updates
  - `/api/v1/app/banners` - Banner tracking
- **No persistent connection**

### 6. **Email Notifications** (Delayed)
- **Background email delivery:**
  - Queue-based processing
  - Email templates with dynamic content
  - Tracking opens/clicks
  - Multi-provider support (Gmail, SMTP)

### 7. **Trading Bot Updates** (Scheduled, Not Live)
- **TradingEngine Services:**
  - Scheduled strategy execution
  - Database-driven signal updates
  - No real-time price feeds configured
  - Market data updates via migrations

---

## 5. KEY ARCHITECTURAL PATTERNS

### Service-Oriented Architecture
- **30+ Services** handle complex business logic
- Separation of concerns (Controllers → Services → Models)
- Example: `WalletService` (646 lines) manages all wallet operations

### Repository/Model Pattern
- **105+ Eloquent Models** with relationships
- Soft deletes for audit trails
- Observers for automated actions
- Polymorphic relationships for flexible data modeling

### API-First Design
- **REST API v1** with Sanctum authentication
- Resource-based endpoints
- Standardized response format
- Rate limiting and throttling

### Event-Driven Architecture
- **Observers** for model events (creating, updating, deleting)
- **Jobs** for queued tasks
- **Webhooks** for external service integrations
- **Listeners** (potential but not extensively used)

### Multi-Tenancy Consideration
- **Admin store** vs **Vendor stores**
- **Affiliate hierarchy** with parent-child relationships
- Separate analytics per vendor/seller

---

## 6. SCALABILITY CONSIDERATIONS

### Current Limitations
- No built-in multi-server support
- Single database assumption
- File storage on local filesystem (not cloud CDN)
- No image CDN integration
- No caching layer for expensive queries (Redis optional)

### Ready for Growth
- **Modular structure** allows adding new systems
- **Service layer** abstracts business logic
- **API endpoints** enable mobile app integration
- **Queue system** supports background jobs
- **Redis support** for caching/sessions
- **Migration-based** database versioning

---

## 7. NOTABLE FEATURES NOT RELATED TO STREAMING

### Advanced Features
- **AI Bot Marketplace:** Rent, install, and manage AI bots
- **Cryptocurrency Integration:** Wallets, transactions, exchanges
- **Trading Bots:** Automated trading with strategies
- **MLM System:** Multi-level affiliate structure with ranks/bonuses
- **E-Commerce:** Products, orders, vendors, shipping
- **Hotel Booking System:** Full booking management
- **Point of Sale (POS):** Retail transaction system
- **Learning Management:** Courses, articles, quizzes, certificates
- **Tarot Reading:** Gamified reading with marketplace
- **HRM System:** Employee, attendance, payroll management
- **Accounting System:** Invoice, expense, and journal entry management
- **Two-Factor Authentication:** Multiple 2FA methods
- **KYC Verification:** Thai ID card OCR with verification
- **Multi-Language:** Google Translate API integration
- **Cookie Consent:** GDPR/privacy compliance system

---

## CONCLUSION

The **TP-Affiliate platform** is a feature-rich, enterprise-grade Laravel application designed for multi-purpose affiliate marketing, e-commerce, and blockchain integration. However, **it has NO current live streaming infrastructure** beyond basic video file support in sliders and POS ads.

### To Implement Mobile Live Streaming:
1. **Add WebSocket Layer** (Laravel Echo + Pusher/Ably)
2. **Implement Streaming Protocol** (HLS/DASH via FFmpeg)
3. **Add Video Infrastructure** (Storage, CDN, encoding)
4. **Create Chat System** (Real-time messaging with broadcasting)
5. **Build Mobile App** (iOS/Android with streaming client)
6. **Add Monetization** (Viewing, tipping, subscriptions)

The current codebase provides excellent foundation for this expansion with its modular architecture and service-oriented design.

