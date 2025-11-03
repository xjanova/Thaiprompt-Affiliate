# Thaiprompt-Affiliate: Quick Reference Guide

## 🎯 System Overview
- **Platform**: Laravel 11 + Tailwind CSS + Alpine.js
- **Database**: MySQL with 95+ migrations, 105+ models
- **Architecture**: Multilayered with 73+ controllers, 30+ services
- **Purpose**: Complete MLM/Affiliate system with E-commerce integration

---

## 📂 Key Directory Map

| Directory | Purpose | Key Files |
|-----------|---------|-----------|
| `/app/Models/` | Database models (105+) | Affiliate, User, Commission, Rank, Product, Order, Wallet |
| `/app/Http/Controllers/Admin/` | Admin panel (40+ controllers) | AffiliateController, ECommerceController, RankController |
| `/app/Services/` | Business logic (30+ services) | WalletService, RankingService, PaymentGatewayService |
| `/routes/` | API & web routes | admin.php, api.php, seller.php, user.php |
| `/resources/views/admin/` | Admin templates | Dashboard, Affiliates, E-commerce, Security |
| `/database/migrations/` | Schema definition | 95+ files defining entire database |

---

## 🏗️ Core Components

### 1. Affiliate System
- **Models**: Affiliate, Commission, Rank, RankRequirement, RankBonus, RankPromotion
- **Features**: 
  - Hierarchical MLM tree (unlimited depth)
  - Multi-tier commission system
  - Rank-based progression with requirements
  - Auto-promotion engine
- **Key File**: `/app/Services/RankingService.php`

### 2. E-Commerce Integration
- **Models**: Product, Order, OrderItem, ProductCategory, ProductReview, ShippingAddress
- **Features**:
  - Product variants & inventory tracking
  - Multi-status order workflow
  - Commission split (platform vs seller)
  - Payment method support (PromptPay, bank transfer, credit card, COD)
- **Key Controller**: `/app/Http/Controllers/Admin/ECommerceController.php`

### 3. Financial Management
- **Models**: Wallet, WalletTransaction, WalletLog, WithdrawalRequest, PaymentMethod, PaymentTransaction
- **Features**:
  - User wallets with PIN + 2FA
  - Multi-currency support
  - Detailed audit trail
  - Withdrawal workflow
- **Key Service**: `/app/Services/WalletService.php` (18,468 lines)

### 4. Admin Panel
- **40+ Controllers** managing all systems
- **Modules**: Dashboard, Users, Affiliates, Commissions, Wallet, E-commerce, Security, AI, LINE
- **Layout**: Sidebar navigation + responsive design
- **Key File**: `/resources/views/layouts/admin.blade.php` (76KB)

### 5. REST API
- **Authentication**: Laravel Sanctum tokens
- **Public**: Login, Settings, LINE webhook
- **Protected**: Dashboard, Tree view, Ranks, Promotions
- **Base URL**: `/api/v1`
- **Key File**: `/routes/api.php`

### 6. Marketplace & Bot Rental
- **Models**: AiBotProfile, AiBotRental, AiConversation, AiMessage, OwnerEarning
- **Features**: Bot marketplace, rental system, AI conversation tracking
- **Integration**: Payment, commission split with owners

---

## 🎛️ Admin Panel Routes

```
/admin/dashboard                    - Main dashboard
/admin/affiliates                   - Affiliate management & tree view
/admin/commissions                  - Commission tracking & approval
/admin/users                        - User management
/admin/wallet                       - Wallet management
/admin/ecommerce/*                  - Product & order management
/admin/ranks                        - Rank configuration
/admin/security                     - IP blocking, threat intelligence
/admin/ai-*                         - AI/Bot management
/admin/line-bot/*                   - LINE integration setup
/admin/settings                     - Global configuration
/admin/notifications                - Notification management
```

---

## 👤 User Dashboards

```
/user/dashboard                     - User home
/user/wallet                        - Wallet interface
/user/commissions                   - Commission history
/user/affiliates                    - Referral management
/user/withdrawal                    - Withdrawal requests
/user/learning                      - Course interface

/seller/dashboard                   - Seller overview
/seller/products                    - Product management
/seller/orders                      - Order management
/seller/analytics                   - Sales analytics
```

---

## 💻 Key APIs

### Authentication
```
POST   /api/v1/login                - User login
POST   /api/v1/logout               - Logout
GET    /api/v1/me                   - Current user
```

### Dashboard
```
GET    /api/v1/dashboard/statistics - User statistics
GET    /api/v1/dashboard/commissions- Commission data
GET    /api/v1/dashboard/referrals  - Referral data
```

### Organization
```
GET    /api/v1/tree/user            - User's affiliate tree
GET    /api/v1/tree/admin/{id}      - Admin tree view
```

### Ranks
```
GET    /api/v1/ranks                - All ranks
GET    /api/v1/ranks/{rank}         - Rank details
GET    /api/v1/ranks/user/progress  - Progress tracking
GET    /api/v1/ranks/leaderboard    - Top performers
```

---

## 📊 Database Models by Category

### Affiliate System (4 models)
- User, Affiliate, Commission, RankPromotion

### Ranking (5 models)
- Rank, RankRequirement, RankBonus, RankSetting, UserRankProgress

### Financial (8 models)
- Wallet, WalletTransaction, WalletLog, WalletSetting, WithdrawalRequest, PaymentMethod, PaymentTransaction

### E-Commerce (8 models)
- Product, ProductCategory, ProductImage, ProductReview, Order, OrderItem, ShippingAddress, ShoppingCart

### Learning (6 models)
- LearningCategory, LearningArticle, UserArticleProgress, ArticlePermission, KnowledgeBase, KnowledgeChunk

### AI/Bot (10 models)
- AiProvider, AiModel, AiBotProfile, AiBotRental, AiConversation, AiMessage, AiUsageLog, AiInstallationLog, OwnerEarning, RagUsageLog

### LINE Integration (10 models)
- LineOaSetting, LineBotAiSetting, LineBotKnowledgeBase, LineBotConversation, LineBotMessage, LineFlexMessageTemplate, LineRichMenu, LineChatWidgetSetting, LineAvatar, LineBroadcastMessage

### Security & Monitoring (6 models)
- SecurityLog, BlockedIp, ThreatIp, EmailLog, OtpVerification, OtpSetting

### Content & Settings (10 models)
- Setting, LanguageSetting, TranslationMapping, MenuItem, Page, PageSection, PageTemplate, SeoMeta, Notification, NotificationTemplate, EmailTemplate, EmailProvider

---

## 🔧 Essential Services

### Financial
- **WalletService**: Deposit, withdraw, transfer, lock/unlock
- **RankingService**: Auto-promotion, eligibility checking
- **WithdrawalService**: Withdrawal processing, payment method handling

### Communication
- **EmailService**: Template rendering, queue-based sending
- **NotificationService**: In-app, push, email, SMS notifications
- **LineService**: LINE OA integration, message pushing

### Security
- **ThreatIntelligenceService**: IP reputation, auto-ban
- **AutoBanService**: Automated blocking, failed login tracking

### Data Processing
- **TranslationService**: Google Translate integration
- **WebPService**: Image optimization & format conversion

---

## 🛡️ Security Features

- **Authentication**: Email/password, LINE OAuth, API tokens
- **Authorization**: RBAC (Role-Based Access Control)
- **Protection**: CSRF tokens, XSS prevention, SQL injection protection
- **Rate Limiting**: Login attempts, API endpoints, translation service
- **Monitoring**: 
  - SecurityLog for all activities
  - IP blocking with CIDR support
  - Threat intelligence database
  - Failed login tracking
- **Wallet Security**:
  - PIN protection
  - Two-factor authentication
  - Auto-lock after failed attempts
  - IP/User-Agent logging

---

## 🎨 Frontend Stack

- **Framework**: Laravel Blade (server-side templates)
- **CSS**: Tailwind CSS 3.4 (utility-first)
- **JavaScript**: Alpine.js 3.13.5 (reactive, lightweight)
- **Visualization**: D3.js, Chart.js, vis-network
- **Build Tool**: Vite 5.0
- **Styling**: Dark mode support, responsive design

---

## 📁 File Structure for Development

### Models
```
app/Models/
├── User.php                       # Main user with RBAC
├── Affiliate.php                  # MLM structure
├── Commission.php                 # Commission tracking
├── Rank.php                       # Rank system
├── Product.php, Order.php         # E-commerce
├── Wallet.php                     # Financial management
└── [100+ more models]
```

### Controllers (73 total)
```
app/Http/Controllers/
├── Admin/                         # 40+ admin controllers
│   ├── DashboardController
│   ├── AffiliateController
│   ├── ECommerceController
│   ├── RankController
│   └── [...many more]
├── Api/V1/                        # REST API controllers
├── Auth/                          # Authentication
├── Frontend/                      # Public pages
└── Seller/                        # E-commerce seller
```

### Services (30+ total)
```
app/Services/
├── WalletService.php              # Financial operations
├── RankingService.php             # Rank progression
├── PaymentGatewayService.php      # Payment handling
├── EmailService.php               # Email sending
├── NotificationService.php        # Notifications
├── LineService.php                # LINE integration
└── [20+ more services]
```

---

## 🚀 For Building MLM System

### Key Extension Points

1. **Commission Calculation**
   - Modify `CommissionService` for custom formulas
   - Add bonus types in `RankBonus`
   - Implement multi-tier logic in `RankingService`

2. **Rank System**
   - Add new requirement types in `RankRequirement`
   - Implement demotion logic
   - Create rank-specific features

3. **Reports & Analytics**
   - Extend `DashboardController`
   - Add custom queries in models
   - Build visualization in admin

4. **Mobile Integration**
   - API is ready for mobile apps
   - Use REST endpoints with Sanctum tokens
   - Implement push notifications

5. **Payment Integration**
   - Extend `PaymentGatewayService`
   - Add new payment methods
   - Implement bank APIs for payouts

---

## 📞 Critical Files Reference

| Functionality | File | Lines |
|--------------|------|-------|
| Affiliate management | `AffiliateController.php` | 5,668 |
| Rank system | `RankingService.php` | 10,028 |
| Wallet operations | `WalletService.php` | 18,468 |
| E-commerce | `ECommerceController.php` | 20,135 |
| Security | `SecurityController.php` | 29,422 |
| Admin layout | `admin.blade.php` | ~77KB |
| User dashboard | `user.blade.php` | ~33KB |
| Seller dashboard | `seller.blade.php` | ~23KB |

---

## 🔗 Quick Links

- **Documentation**: `/CODEBASE_ARCHITECTURE.md`
- **Main App**: `http://yourdomain.com`
- **Admin Panel**: `http://yourdomain.com/admin`
- **API Docs**: Check `/routes/api.php`
- **Database**: Check `/database/migrations/`

---

## ⚡ Common Operations

### Check User Rank Eligibility
```php
$user = User::find($userId);
$nextRank = $user->currentRank->next_rank;
$eligibility = $nextRank->checkUserEligibility($user);
```

### Create Wallet Transaction
```php
$walletService = new WalletService();
$transaction = $walletService->deposit(
    $wallet, 
    $amount, 
    'Commission earned'
);
```

### Process Commissions
```php
$affiliate = Affiliate::find($affiliateId);
$commissions = $affiliate->commissions()->pending()->get();
// Approve and pay...
```

### View Affiliate Tree
```php
$affiliate = Affiliate::find($id);
$children = $affiliate->children()->with('user')->get();
```

---

Last Updated: November 3, 2025 | Version: 1.92.0
