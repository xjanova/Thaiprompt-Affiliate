# Thaiprompt-Affiliate Documentation Index

**Generated:** November 3, 2025  
**Version:** 1.92.0

## Reading Guide

Start with the document that best matches your needs:

### For Complete Technical Understanding
📖 **[CODEBASE_ARCHITECTURE.md](./CODEBASE_ARCHITECTURE.md)** (35 KB)
- Complete architectural breakdown
- All 105+ models documented
- 73+ controllers organized by scope
- 30+ services with detailed descriptions
- Database relationships & schemas
- Authentication & security features
- Business workflows & data flows
- **Best for:** Building on top of the system, understanding all components

### For Quick Lookups & Development
⚡ **[QUICK_REFERENCE.md](./QUICK_REFERENCE.md)** (12 KB)
- Key directory map
- 6 core components at a glance
- Admin panel routes
- API endpoints
- Database models by category
- Critical files by functionality
- Common code operations
- **Best for:** Day-to-day development, quick answers

## System at a Glance

```
Framework: Laravel 11 + Tailwind CSS + Alpine.js
Database:  MySQL 8.0+ with 95+ migrations
Models:    105+ Eloquent models
Controllers: 73+ (40+ for admin)
Services:  30+ business logic services
Views:     100+ Blade templates
API:       REST v1 with Laravel Sanctum
```

## What's in This Codebase

### Core MLM/Affiliate System
- Hierarchical affiliate tree (unlimited depth)
- Multi-tier commission system (direct, indirect, bonus)
- Rank-based progression with auto-promotion
- Comprehensive rank requirements & bonuses

### E-Commerce Platform
- Product catalog with variants
- Inventory tracking
- Complete order workflow
- Multiple payment methods
- Seller dashboard

### Financial Management
- User wallets with PIN + 2FA
- Multi-currency support
- Withdrawal management
- Detailed audit trail

### Admin Panel
- 40+ controller modules
- Affiliate tree visualization
- Commission management
- Product & order management
- Security monitoring
- AI bot marketplace
- LINE integration

### REST API
- Token-based authentication (Sanctum)
- Dashboard endpoints
- Organization tree data
- Rank system endpoints
- Mobile-ready responses

### Additional Features
- AI bot marketplace & rental system
- LINE Official Account integration
- Learning center & course management
- Multi-language support (English, Thai)
- Email & notification system
- Threat intelligence & IP blocking

## File Organization

```
Thaiprompt-Affiliate/
├── DOCUMENTATION_INDEX.md          ← You are here
├── CODEBASE_ARCHITECTURE.md        ← Full technical reference
├── QUICK_REFERENCE.md              ← Quick lookup guide
├── README.md                       ← Original project README
├── app/
│   ├── Models/                     ← 105+ models
│   ├── Http/Controllers/           ← 73+ controllers
│   ├── Services/                   ← 30+ services
│   └── ...
├── routes/                         ← API & web routes
├── resources/views/                ← Blade templates
├── database/migrations/            ← 95+ schema files
└── ...
```

## Key Learning Paths

### Path 1: Understanding the Affiliate System
1. Read: CODEBASE_ARCHITECTURE.md → "Affiliate & MLM System"
2. Review: `/app/Models/Affiliate.php`
3. Review: `/app/Models/Commission.php`
4. Review: `/app/Services/RankingService.php`
5. Review: `/app/Models/Rank*.php` (all rank models)

### Path 2: Understanding E-Commerce
1. Read: CODEBASE_ARCHITECTURE.md → "E-Commerce Integration"
2. Review: `/app/Models/Product.php`
3. Review: `/app/Models/Order.php`
4. Review: `/app/Http/Controllers/Admin/ECommerceController.php`

### Path 3: Understanding Financial System
1. Read: CODEBASE_ARCHITECTURE.md → "Key Services & Business Logic"
2. Review: `/app/Services/WalletService.php`
3. Review: `/app/Models/Wallet*.php`
4. Review: `/app/Models/WithdrawalRequest.php`

### Path 4: Understanding Admin Panel
1. Read: CODEBASE_ARCHITECTURE.md → "Admin Panel Structure"
2. Review: `/resources/views/layouts/admin.blade.php`
3. Browse: `/app/Http/Controllers/Admin/` (any controller)
4. Browse: `/resources/views/admin/` (relevant views)

### Path 5: Understanding API
1. Read: CODEBASE_ARCHITECTURE.md → "API Routes & Backend"
2. Review: `/routes/api.php`
3. Review: `/app/Http/Controllers/Api/V1/`

## Critical Models to Understand

```
User                    ← User authentication & RBAC
Affiliate               ← MLM tree structure
Commission              ← Commission tracking
Rank                    ← Rank progression system
Product                 ← E-commerce products
Order                   ← E-commerce orders
Wallet                  ← User financial management
WithdrawalRequest       ← Withdrawal workflow
```

## Critical Services to Understand

```
WalletService           ← Financial operations (18,468 lines)
RankingService          ← Rank progression (10,028 lines)
PaymentGatewayService   ← Payment handling (8,699 lines)
EmailService            ← Email operations (11,285 lines)
NotificationService     ← Notification system (14,104 lines)
LineService             ← LINE integration (15,573 lines)
```

## Admin URLs to Know

```
/admin/dashboard                    Main dashboard
/admin/affiliates                   Affiliate management
/admin/affiliates/tree-interactive  Network tree visualization
/admin/commissions                  Commission management
/admin/users                        User management
/admin/wallet                       Wallet operations
/admin/ecommerce/*                  Product & order management
/admin/ranks                        Rank configuration
/admin/security                     Security & monitoring
```

## API Endpoints to Know

```
POST   /api/v1/login                User authentication
GET    /api/v1/me                   Current user profile
GET    /api/v1/dashboard/*          Statistics & data
GET    /api/v1/tree/*               Organization structure
GET    /api/v1/ranks                Rank system data
POST   /api/v1/ranks/promotions/request    Rank request
```

## Next Steps for MLM Development

This codebase provides the foundation for a complete MLM system. To build a full implementation:

1. **Customize Commission Formulas**
   - Modify RankingService
   - Extend RankBonus types
   - Create custom CommissionService

2. **Add Advanced Rank Features**
   - Implement demotion rules
   - Add rank-specific features
   - Create rank-based restrictions

3. **Build Analytics Dashboard**
   - Query affiliate performance
   - Calculate network metrics
   - Track revenue attribution

4. **Extend Mobile API**
   - Add more endpoints
   - Implement push notifications
   - Add real-time updates

5. **Implement Payment Integrations**
   - Add new payment methods
   - Integrate with banks
   - Implement crypto support

## Database Schema Relationships

Key relationships to understand:

```
users (1) ──[has affiliate]──→ affiliates
affiliates ──[parent-child]──→ affiliates
affiliates ──[has many]──→ commissions
users ──[has one]──→ wallets
users ──[has many]──→ orders
users ──[belongs to]──→ ranks
```

## Security Features to Maintain

- CSRF protection on all forms
- SQL injection prevention
- XSS attack prevention
- Rate limiting on login & API
- IP blocking capability
- Wallet PIN protection
- Two-factor authentication
- Activity logging (SecurityLog)

## Documentation Files in Project Root

Existing documentation you should read:
- `README.md` - Project overview
- `INSTALLATION-GUIDE.md` - Setup guide
- `RANKING_SYSTEM.md` - Rank system details
- `WALLET_SYSTEM.md` - Wallet features
- `NOTIFICATION_SYSTEM.md` - Notifications
- `MULTI-LANGUAGE.md` - Language setup
- `DEPLOYMENT-GUIDE.md` - Production deployment
- `DESIGN_GUIDELINES.md` - UI/UX standards

## Questions Answered by This Documentation

### Architecture Questions
✓ How is the codebase organized?
✓ What technology is being used?
✓ How do models relate to each other?
✓ How is business logic structured?

### Feature Questions
✓ How does the affiliate system work?
✓ How are commissions calculated?
✓ How is the wallet system implemented?
✓ How does e-commerce integrate?

### Development Questions
✓ Where is controller X located?
✓ Which service handles Y?
✓ What does model Z do?
✓ How do I extend feature A?

### Integration Questions
✓ How does the API work?
✓ What endpoints are available?
✓ How do I authenticate?
✓ What response format is expected?

## Quick Checklist for New Developers

- [ ] Read QUICK_REFERENCE.md (15 minutes)
- [ ] Skim CODEBASE_ARCHITECTURE.md (30 minutes)
- [ ] Review the 8 critical models listed above
- [ ] Review the 6 critical services listed above
- [ ] Check out the admin panel (/admin/dashboard)
- [ ] Review API routes (/routes/api.php)
- [ ] Read existing documentation files in project root

## Getting Help

For specific topics, use these documents:
- **Models:** See CODEBASE_ARCHITECTURE.md → "Database Architecture"
- **Controllers:** See CODEBASE_ARCHITECTURE.md → "Admin Panel Structure"
- **Services:** See CODEBASE_ARCHITECTURE.md → "Key Services & Business Logic"
- **API:** See CODEBASE_ARCHITECTURE.md → "API Routes & Backend"
- **Quick lookup:** See QUICK_REFERENCE.md

## Version Information

```
Framework:  Laravel 11.0
PHP:        8.1+
Database:   MySQL 8.0+
Version:    1.92.0
Created:    November 3, 2025
```

---

**Start with:** [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) for quick answers  
**Deep dive:** [CODEBASE_ARCHITECTURE.md](./CODEBASE_ARCHITECTURE.md) for complete understanding

Happy coding!
