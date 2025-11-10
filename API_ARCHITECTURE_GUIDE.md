# แอร์คิเทคเจอร์ระบบจัดการ API - Thaiprompt-Affiliate

## 1. API Request Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT REQUEST                            │
│          (Web Browser, Mobile App, Third-party)             │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│              ROUTE DEFINITION LAYER                          │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  routes/    │  │  routes/     │  │  routes/     │       │
│  │  api.php    │  │  web.php     │  │  admin.php   │       │
│  └─────────────┘  └──────────────┘  └──────────────┘       │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│           MIDDLEWARE STACK (Sequential)                      │
│  1. VerifyCsrfToken / VerifyWebhookSignature               │
│  2. CheckBlockedIp (IP filtering)                          │
│  3. VerifyCloudfareTurnstile (CAPTCHA if needed)           │
│  4. Authenticate (Session or Sanctum Token)                │
│  5. CheckRole / CheckPermission                            │
│  6. RequireTwoFactor (if configured)                       │
│  7. PaymentRateLimiter / ThrottleLogin                     │
│  8. IdempotencyMiddleware (deduplication)                  │
│  9. TrackRequestMetrics (logging)                          │
│  10. VerifyLicenseIntegrity (license check)                │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│         CONTROLLER LAYER (Business Logic Dispatch)          │
│  App\Http\Controllers\{Admin|Api|Seller|User|...}          │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐           │
│  │ DashboardC │  │ RankC      │  │ CommissionC│           │
│  │ ontroller  │  │ ontroller  │  │ ontroller  │           │
│  └────────────┘  └────────────┘  └────────────┘           │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│           SERVICE LAYER (Complex Operations)                │
│  App\Services\                                              │
│  ├─ AI/ (OpenAiService, ClaudeService, RagService)        │
│  ├─ Marketplace/ (MarketplaceSyncService)                  │
│  ├─ LineService (LINE OA integration)                      │
│  └─ IpIntelligenceService (Security)                       │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│            MODEL LAYER (Data Access)                        │
│  App\Models\                                                │
│  └─ Relationships & Eloquent Queries                        │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│             DATABASE LAYER                                  │
│  MySQL / PostgreSQL                                         │
│  ├─ 271 migrations (schema)                                │
│  ├─ Cached queries                                         │
│  └─ Connection pooling                                     │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│         RESPONSE FORMATTING & CACHING                       │
│  JSON Response Builder → Cache Check → Client              │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│         MONITORING & LOGGING                                │
│  ├─ Security Logs (unauthorized attempts)                  │
│  ├─ Request Metrics (performance tracking)                 │
│  ├─ Email Logs (communication audit)                       │
│  └─ Activity Logs (user actions)                           │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│              RESPONSE TO CLIENT                             │
│       (JSON with HTTP Status Code & Headers)               │
└─────────────────────────────────────────────────────────────┘
```

## 2. Authentication & Authorization Architecture

```
┌─────────────────────────────────────────────────────────┐
│           AUTHENTICATION LAYER                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────────┐   ┌──────────────┐               │
│  │  Session Auth   │   │ Sanctum API  │               │
│  │  (Web Routes)   │   │ (API Routes) │               │
│  ├─────────────────┤   ├──────────────┤               │
│  │ Guard: web      │   │ Guard: api   │               │
│  │ Provider: User  │   │ Provider:    │               │
│  │ Model           │   │ Personal     │               │
│  │ Timeout: 60min  │   │ Access Token │               │
│  └─────────────────┘   └──────────────┘               │
│                                                         │
│  ┌──────────────────────────────────────┐             │
│  │  LINE OAuth2 (Social Authentication) │             │
│  ├──────────────────────────────────────┤             │
│  │ LINE Login endpoint                  │             │
│  │ Account linking supported            │             │
│  │ Signup via invitation tokens         │             │
│  └──────────────────────────────────────┘             │
└─────────────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│        AUTHORIZATION LAYER (Multi-Layer)                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Layer 1: USER FLAGS                                   │
│  ┌──────────────────────────────┐                     │
│  │ is_super_admin = true        │  <- Admin Access   │
│  │ is_admin = true              │  <- Admin Access   │
│  │ is_hotel_admin = true        │  <- Hotel Access  │
│  │ managed_hotel_id = X         │  <- Tenant ID     │
│  │ role_id = X                  │  <- Role-based    │
│  │ permissions = JSON array     │  <- Direct perms  │
│  └──────────────────────────────┘                     │
│                                                         │
│  Layer 2: ROLE-BASED ACCESS CONTROL (RBAC)            │
│  ┌──────────────────────────────┐                     │
│  │ Roles Table                  │                     │
│  │ ├─ id, name, display_name   │                     │
│  │ └─ is_system_role (locked)   │                     │
│  │ Permissions Table            │                     │
│  │ ├─ id, name, description     │                     │
│  │ └─ resource-action format    │                     │
│  │ Role_Permissions (Many-to-Many)                    │
│  └──────────────────────────────┘                     │
│                                                         │
│  Layer 3: POLICY-BASED AUTHORIZATION                   │
│  ┌──────────────────────────────┐                     │
│  │ PageBuilderPolicy            │                     │
│  │ StakingPositionPolicy        │                     │
│  │ Accounting*Policy            │                     │
│  │ (Resource-level access)      │                     │
│  └──────────────────────────────┘                     │
│                                                         │
│  Layer 4: MIDDLEWARE AUTHORIZATION                     │
│  ┌──────────────────────────────┐                     │
│  │ CheckRole (route-level)      │                     │
│  │ CheckPermission (action-level)                     │
│  │ AdminMiddleware              │                     │
│  │ HotelAdminMiddleware         │                     │
│  └──────────────────────────────┘                     │
└─────────────────────────────────────────────────────────┘
```

## 3. Multi-Role Access Hierarchy

```
┌────────────────────────────────────────────────────────┐
│            SUPER ADMIN (is_super_admin=true)           │
│  • Full system access                                  │
│  • Manage all users, roles, permissions               │
│  • View all data                                       │
│  • System configuration                               │
└────────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ADMIN        │  │ HOTEL ADMIN  │  │ SELLER       │
│(is_admin=true)  │(is_hotel_admin) │(role-based)  │
├──────────────┤  ├──────────────┤  ├──────────────┤
│ Manage users │  │ Manage hotel │  │ Manage store │
│ Manage roles │  │ Manage rooms │  │ Manage      │
│ Manage       │  │ Manage       │  │ products    │
│ permissions  │  │ bookings     │  │ Manage      │
│ Global       │  │ Limited to   │  │ orders      │
│ settings     │  │ managed_hotel│  │ Analytics   │
└──────────────┘  └──────────────┘  └──────────────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│                USER (Default)                          │
│  • View own profile                                    │
│  • Manage own wallet                                   │
│  • View personal genealogy/ranks                       │
│  • Access affiliate dashboard                         │
│  • Make investments                                    │
└────────────────────────────────────────────────────────┘
```

## 4. Security Features Architecture

```
┌─────────────────────────────────────────────────────────┐
│          SECURITY MIDDLEWARE STACK                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. IP-Based Security                                  │
│  ┌─────────────────────────────────────────────────┐  │
│  │ BlockedIp Model (manual blocking)               │  │
│  │ ThreatIp Model (automatic detection)            │  │
│  │ IpIntelligenceService (IP reputation check)     │  │
│  │ CheckBlockedIp Middleware (enforcement)         │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  2. Request Validation                                 │
│  ┌─────────────────────────────────────────────────┐  │
│  │ VerifyCsrfToken (web routes)                    │  │
│  │ VerifyWebhookSignature (payment webhooks)       │  │
│  │ VerifyCloudfareTurnstile (CAPTCHA)              │  │
│  │ VerifyLicenseIntegrity (license check)          │  │
│  │ IdempotencyMiddleware (duplicate prevention)    │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  3. Authentication                                     │
│  ┌─────────────────────────────────────────────────┐  │
│  │ Session Guard (web)                             │  │
│  │ Sanctum Token (API)                             │  │
│  │ LINE OAuth (social)                             │  │
│  │ ThrottleLogin (rate limiting)                   │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  4. Two-Factor Authentication                          │
│  ┌─────────────────────────────────────────────────┐  │
│  │ TwoFactorUserSetting Model                      │  │
│  │ TwoFactorSettingsController                     │  │
│  │ RequireTwoFactor Middleware                     │  │
│  │ OtpVerification Model (one-time passwords)      │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
│  5. Audit & Monitoring                                 │
│  ┌─────────────────────────────────────────────────┐  │
│  │ SecurityLog Model (all access attempts)         │  │
│  │ TrackRequestMetrics Middleware (performance)    │  │
│  │ EmailLog Model (email tracking)                 │  │
│  │ SystemAnalytic Model (system metrics)           │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

## 5. API Versioning & Endpoint Organization

```
/api/
├── /v1/                           (API Version 1)
│   ├── /auth
│   │   ├── POST   login
│   │   ├── POST   logout
│   │   └── GET    me
│   ├── /dashboard
│   │   ├── GET    statistics
│   │   ├── GET    commissions
│   │   └── GET    referrals
│   ├── /tree
│   │   ├── GET    user
│   │   ├── GET    admin/{id}
│   │   ├── GET    binary
│   │   └── GET    binary/admin/{id}
│   ├── /ranks
│   │   ├── GET    all
│   │   ├── GET    {id}
│   │   ├── GET    user/progress
│   │   ├── GET    leaderboard
│   │   ├── GET    user/eligibility
│   │   └── POST   promotions/request
│   ├── /investments
│   │   ├── GET    plans
│   │   ├── GET    plans/{id}
│   │   ├── POST   calculate-roi
│   │   ├── POST   invest
│   │   ├── GET    summary
│   │   ├── GET    positions
│   │   ├── POST   positions/{id}/withdraw
│   │   └── GET    distributions
│   ├── /crypto
│   │   ├── GET    balances
│   │   ├── GET    address/{currency}
│   │   ├── GET    prices
│   │   ├── GET    transaction/{hash}
│   │   ├── GET    gas-price
│   │   └── POST   verify-signature
│   ├── /app
│   │   ├── GET    config
│   │   ├── GET    settings
│   │   ├── GET    theme
│   │   ├── GET    features
│   │   └── GET    banners
│   └── (Public endpoints - no auth required)
│       ├── POST   login
│       ├── GET    settings
│       ├── GET    ranks
│       ├── GET    app/maintenance-status
│       └── GET    app/check-update
├── /webhook/
│   ├── POST   line                (LINE webhook)
│   ├── POST   paysolutions        (Payment webhook)
│   ├── POST   promptpay           (Payment webhook)
│   ├── POST   stripe              (Payment webhook)
│   └── POST   omise               (Payment webhook)
└── (Other public endpoints)
    ├── POST   cookie-consent
    ├── GET    cookie-consent
    ├── POST   cookie-track-page
    ├── POST   cookie-track-keyword
    └── POST   cookie-track-product
```

## 6. Database Relationship Model

```
User (Core)
├─ Role (M:1) → role_id
├─ Affiliate (1:1) → polymorphic
├─ Wallet (1:1) → has_one
├─ CryptoWallet (1:M) → has_many
├─ Rank (M:1) → current_rank_id
├─ InvestmentPosition (1:M) → has_many
├─ Order (1:M) → has_many
├─ Commission (1:M) → has_many
├─ AiConversation (1:M) → has_many
├─ Hotel (1:M) → via managed_hotel_id
├─ SecurityLog (1:M) → tracked_by_user_id
└─ PersonalAccessToken (1:M) → API tokens (Sanctum)

Role (RBAC)
├─ Permission (M:M) → via role_permissions
├─ User (1:M) → reverse relationship

Permission (RBAC)
└─ Role (M:M) → via role_permissions

Affiliate
├─ User (M:1)
├─ Commission (1:M)
├─ MlmMember (1:M)
├─ Rank (M:1) → current_rank

RoiDistribution (Investment)
├─ InvestmentPlan (M:1)
├─ User (M:1)

Order (E-commerce)
├─ User (M:1)
├─ OrderItem (1:M)
├─ PaymentTransaction (M:1)

Product
├─ ProductCategory (M:1)
├─ ProductImage (1:M)
├─ Order (M:M) → via order_items
```

## 7. Rate Limiting & Throttling Strategy

```
Configuration: config/ratelimit.php

┌─────────────────────────────────────────────┐
│    ENDPOINT                    │  LIMIT      │
├────────────────────────────────┼─────────────┤
│ Login attempts                 │ 5/min       │
│ API Translation                │ 60/min      │
│ Payment operations             │ Custom      │
│ General API                    │ 60/min      │
│ Webhook endpoints              │ No limit    │
│ Public endpoints               │ 60/min      │
│ Cookie tracking                │ No limit    │
└─────────────────────────────────────────────┘

PaymentRateLimiter: Special handling for payment requests
IdempotencyMiddleware: Prevents duplicate processing
```

## 8. Recommended API Management Improvements

### 1. Documentation & Monitoring
```
□ API Documentation (OpenAPI/Swagger)
  - Auto-generated from routes
  - Request/response examples
  - Rate limit documentation

□ API Monitoring Dashboard
  - Real-time request metrics
  - Error rate tracking
  - Latency monitoring
  - Endpoint usage analytics

□ API Versioning Policy
  - Support multiple versions simultaneously
  - Migration path for deprecations
  - Backward compatibility testing
```

### 2. Request/Response Management
```
□ Standardized Response Format
  {
    "status": "success|error",
    "data": {...},
    "message": "...",
    "timestamp": "...",
    "request_id": "..." (for tracing)
  }

□ Error Response Format
  {
    "status": "error",
    "error_code": "ERROR_TYPE",
    "message": "...",
    "details": {...},
    "timestamp": "..."
  }

□ Pagination Standard
  - cursor-based for large datasets
  - Configurable page size
  - Total count optional

□ Request Validation
  - Input sanitization
  - Type validation
  - Length limits
  - Format validation
```

### 3. Security Enhancements
```
□ API Key Management
  - Rotation policy
  - Expiration dates
  - Scope limitations
  - Usage tracking

□ Rate Limiting Refinement
  - Per-user limits
  - Per-endpoint limits
  - Burst handling
  - Priority queuing

□ Request Signing
  - HMAC-SHA256 for webhooks
  - Timestamp validation
  - Nonce prevention

□ Audit Trail
  - All API calls logged
  - Request/response bodies
  - User identification
  - Timestamp accuracy
```

### 4. Performance Optimization
```
□ Response Caching
  - HTTP caching headers
  - Redis caching
  - Cache invalidation strategy
  - CDN integration

□ Query Optimization
  - N+1 query prevention
  - Index optimization
  - Query result caching
  - Lazy loading

□ Load Balancing
  - Request distribution
  - Health checks
  - Failover handling
  - Session affinity
```

### 5. Webhook Management
```
□ Webhook Registry
  - Event subscriptions
  - Delivery retry logic
  - Failed event storage
  - Event replay capability

□ Delivery Guarantees
  - At-least-once delivery
  - Request timeout handling
  - Exponential backoff retry
  - Dead letter queues

□ Webhook Security
  - Signature verification
  - Event encryption
  - IP whitelisting
  - SSL/TLS enforcement
```

---

**Next Steps for API Access Control Implementation:**

1. Create API Access Control Model
2. Implement token scope system
3. Add API key management UI
4. Create rate limiting per API key
5. Build API usage analytics dashboard
6. Document API governance policies
7. Implement request logging/auditing
8. Setup monitoring & alerting

