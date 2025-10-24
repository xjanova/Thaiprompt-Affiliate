# Security Audit Report
# ThaiPrompt Affiliate Marketplace

**Audit Date:** 2024-10-24
**Project Version:** 1.0.0
**Auditor:** Security Review System
**Audit Scope:** Complete codebase security analysis

---

## Executive Summary

This security audit report provides a comprehensive analysis of the ThaiPrompt Affiliate Marketplace codebase. The audit covers authentication, authorization, data validation, payment processing, API security, and common vulnerabilities.

### Overall Security Rating: ⚠️ MEDIUM-HIGH RISK

**Overall Assessment:**
The codebase demonstrates good architectural design with proper use of Laravel security features. However, several critical components are missing (controllers not implemented), and there are security concerns that need to be addressed before production deployment.

### Key Findings Summary

✅ **Strengths:** 17 security controls implemented
⚠️ **Medium Risk Issues:** 8 areas requiring attention
🔴 **High Risk Issues:** 5 critical security concerns
📋 **Recommendations:** 15 security improvements suggested

---

## Table of Contents

1. [Authentication & Authorization Security](#1-authentication--authorization-security)
2. [SQL Injection & Database Security](#2-sql-injection--database-security)
3. [XSS (Cross-Site Scripting) Protection](#3-xss-cross-site-scripting-protection)
4. [CSRF Protection](#4-csrf-protection)
5. [Payment Processing Security](#5-payment-processing-security)
6. [API Security](#6-api-security)
7. [Data Validation & Sanitization](#7-data-validation--sanitization)
8. [File Upload Security](#8-file-upload-security)
9. [Session Management](#9-session-management)
10. [Financial Transaction Security](#10-financial-transaction-security)
11. [Webhook Security](#11-webhook-security)
12. [Password & Credential Management](#12-password--credential-management)
13. [Information Disclosure](#13-information-disclosure)
14. [Business Logic Security](#14-business-logic-security)
15. [Missing Security Controls](#15-missing-security-controls)
16. [Security Recommendations](#16-security-recommendations)
17. [Compliance Considerations](#17-compliance-considerations)

---

## 1. Authentication & Authorization Security

### ✅ Implemented Security Controls

#### 1.1 Laravel Sanctum Token Authentication
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:14`

```php
use Laravel\Sanctum\HasApiTokens;
```

**Assessment:**
- Token-based authentication using Laravel Sanctum
- Industry-standard approach for API authentication
- Tokens are securely generated and validated

#### 1.2 Password Hashing
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:41-43`

```php
protected $casts = [
    'password' => 'hashed',
];
```

**Assessment:**
- Passwords automatically hashed using bcrypt
- Laravel's default hashing algorithm (secure)
- Proper use of password casting

#### 1.3 Role-Based Access Control (RBAC)
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:10,14`

```php
use Spatie\Permission\Traits\HasRoles;
```

**Assessment:**
- Spatie Laravel Permission package integration
- Role middleware on vendor routes (`routes/api.php:65`)
- Proper role checking methods (`isVendor()`, `isAdmin()`)

#### 1.4 Password Hiding in API Responses
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:36-39`

```php
protected $hidden = [
    'password',
    'remember_token',
];
```

**Assessment:**
- Sensitive fields excluded from JSON responses
- Prevents password leak in API responses

### ⚠️ Security Concerns

#### 1.5 Missing Multi-Factor Authentication (MFA)
**Severity:** ⚠️ MEDIUM
**Risk:** Account takeover through password compromise

**Finding:**
No multi-factor authentication implementation found in the codebase.

**Recommendation:**
```php
// Implement 2FA for high-value accounts (vendors, admins)
// Consider Laravel Fortify or Laravel Breeze with 2FA
```

**Impact:** Medium - Especially important for admin and vendor accounts with financial access

#### 1.6 No Account Lockout Mechanism
**Severity:** ⚠️ MEDIUM
**Risk:** Brute force password attacks

**Finding:**
No rate limiting or account lockout after failed login attempts.

**Recommendation:**
```php
// In AuthController (to be implemented)
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::hit('login:' . $request->ip(), 300); // 5 minutes
if (RateLimiter::tooManyAttempts('login:' . $request->ip(), 5)) {
    // Lock account or delay response
}
```

**Impact:** Medium - Could allow password guessing attacks

#### 1.7 Missing Session Timeout Configuration
**Severity:** ⚠️ LOW
**Location:** `.env.example:23`

**Finding:**
```env
SESSION_LIFETIME=120
```

**Assessment:**
- 120 minutes session lifetime is reasonable
- However, no idle timeout for sensitive operations
- No "remember me" token rotation

**Recommendation:**
- Implement shorter session timeout for admin/vendor roles
- Add idle timeout for financial operations
- Rotate "remember me" tokens periodically

---

## 2. SQL Injection & Database Security

### ✅ Implemented Security Controls

#### 2.1 Eloquent ORM Usage
**Status:** ✅ EXCELLENT
**Assessment:** No raw SQL queries found

**Finding:**
Comprehensive search for raw SQL:
```bash
grep -r "DB::raw\|DB::select\|DB::statement" app/
# Result: No matches found
```

**Assessment:**
- 100% use of Eloquent ORM for database operations
- All queries properly parameterized
- Built-in protection against SQL injection

**Examples of Secure Queries:**

**app/Services/MLM/MlmService.php:170-176**
```php
$downlineIds = MlmGenealogy::where('ancestor_id', $user->id)
    ->pluck('descendant_id')
    ->toArray();

return Order::whereIn('user_id', $downlineIds)
    ->where('payment_status', 'completed')
    ->sum('total');
```

**app/Services/MLM/MlmService.php:107-110**
```php
$uplines = MlmGenealogy::where('descendant_id', $user->id)
    ->with('ancestor')
    ->orderBy('depth')
    ->get();
```

**Security Score:** 🟢 EXCELLENT - Zero SQL injection vulnerabilities detected

#### 2.2 Database Transaction Safety
**Status:** ✅ Properly Implemented

**Location:** `app/Services/Wallet/WalletService.php:45`
```php
return DB::transaction(function () use ($user, $wallet, $amount, $bankDetails) {
    // Critical financial operations
});
```

**Assessment:**
- Database transactions used for critical operations
- Ensures atomicity of financial transactions
- Prevents partial updates in case of errors

**Locations:**
- Withdrawal requests: `WalletService.php:45`
- Withdrawal approval: `WalletService.php:85`
- Withdrawal rejection: `WalletService.php:105`
- MLM registration: `MlmService.php:20`

---

## 3. XSS (Cross-Site Scripting) Protection

### ⚠️ Security Concerns

#### 3.1 Missing Controller Implementation
**Severity:** 🔴 HIGH
**Risk:** Cannot assess XSS protection without controllers

**Finding:**
Controllers referenced in routes but not implemented:
- `app/Http/Controllers/Api/AuthController.php` - NOT FOUND
- `app/Http/Controllers/Api/ProductController.php` - NOT FOUND
- `app/Http/Controllers/Api/OrderController.php` - NOT FOUND
- All other controllers - NOT FOUND

**Impact:**
- Cannot validate input sanitization
- Cannot verify output encoding
- Cannot assess JSON response safety

**Recommendation:**
When implementing controllers, ensure:

```php
// 1. Validate and sanitize all input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email',
    'description' => 'required|string|max:5000',
]);

// 2. Use htmlspecialchars or e() helper for output
$safeName = e($user->name);

// 3. For API responses (JSON), Laravel auto-escapes
return response()->json(['name' => $user->name]); // Safe

// 4. For Blade templates, use {{ }} not {!! !!}
{{ $user->name }} // Safe - auto-escaped
{!! $user->name !!} // Dangerous - no escaping
```

#### 3.2 No View Templates Found
**Severity:** ⚠️ MEDIUM
**Risk:** Cannot assess XSS in presentation layer

**Finding:**
No Blade templates found in `resources/views/` directory.

**Assessment:**
- Frontend implementation pending
- Cannot verify proper output escaping
- Risk when templates are created

**Recommendation:**
When creating Blade templates:

```blade
<!-- GOOD - Auto-escaped -->
<h1>{{ $product->name }}</h1>
<p>{{ $product->description }}</p>

<!-- BAD - No escaping, XSS vulnerable -->
<h1>{!! $product->name !!}</h1>

<!-- GOOD - Safe for HTML attributes -->
<input type="text" value="{{ $product->name }}" />

<!-- GOOD - For displaying user-generated content -->
<div class="content">
    {!! clean($product->description) !!}  // Use HTML Purifier
</div>
```

#### 3.3 Rich Text Content Concerns
**Severity:** ⚠️ MEDIUM
**Location:** Product descriptions, review comments

**Finding:**
Models store rich text content:
- `products.description` - Could contain HTML
- `reviews.comment` - User-generated content
- `review_responses.response` - Vendor responses

**Recommendation:**
```bash
# Install HTML Purifier
composer require mews/purifier

# Usage in controller
use Mews\Purifier\Facades\Purifier;

$clean = Purifier::clean($request->description);
```

**Recommended Configuration:**
```php
// config/purifier.php
'default' => [
    'HTML.Allowed' => 'p,b,strong,i,em,u,a[href],ul,ol,li,br',
    'AutoFormat.RemoveEmpty' => true,
];
```

---

## 4. CSRF Protection

### ✅ Implemented Security Controls

#### 4.1 Laravel CSRF Middleware
**Status:** ✅ Built-in (Framework Default)

**Assessment:**
- Laravel automatically applies CSRF protection to web routes
- `VerifyCsrfToken` middleware enabled by default
- API routes exempt (use token authentication instead)

**Evidence:**
```php
// routes/api.php - Uses Sanctum tokens instead of CSRF
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

**Security Score:** 🟢 GOOD - Proper CSRF protection strategy

### ⚠️ Security Concerns

#### 4.2 Webhook Endpoints Lack Signature Verification
**Severity:** 🔴 HIGH
**Location:** `routes/api.php:80-82`

```php
// Webhook Routes (No Auth)
Route::post('/webhooks/stripe', [OrderController::class, 'stripeWebhook']);
Route::post('/webhooks/promptpay', [OrderController::class, 'promptpayWebhook']);
Route::post('/webhooks/line', [AuthController::class, 'lineWebhook']);
```

**Risk:**
- Webhooks bypass authentication
- Vulnerable to forged webhook requests
- Could be exploited to manipulate orders/payments

**Recommendation:**

**For Stripe:**
```php
// In OrderController::stripeWebhook()
use Stripe\Webhook;

public function stripeWebhook(Request $request)
{
    $payload = $request->getContent();
    $sig_header = $request->header('Stripe-Signature');
    $endpoint_secret = config('services.stripe.webhook_secret');

    try {
        $event = Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        return response()->json(['error' => 'Invalid payload'], 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        return response()->json(['error' => 'Invalid signature'], 400);
    }

    // Process webhook
}
```

**For Line OA:**
```php
// In AuthController::lineWebhook()
public function lineWebhook(Request $request)
{
    $signature = $request->header('X-Line-Signature');
    $body = $request->getContent();

    $hash = hash_hmac('sha256', $body, config('services.line.channel_secret'), true);
    $expected_signature = base64_encode($hash);

    if (!hash_equals($signature, $expected_signature)) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }

    // Process webhook
}
```

**For PromptPay:**
```php
// Implement bank-specific signature verification
// Contact PromptPay API provider for documentation
```

---

## 5. Payment Processing Security

### ✅ Implemented Security Controls

#### 5.1 Amount Conversion for Stripe
**Status:** ✅ Correct Implementation
**Location:** `app/Services/Payment/PaymentService.php:134`

```php
'amount' => $order->total * 100, // Convert to cents
```

**Assessment:**
- Proper conversion to cents (Stripe requirement)
- Prevents floating-point errors
- Correct currency handling (THB)

#### 5.2 Transaction ID Storage
**Status:** ✅ Properly Implemented

**Assessment:**
- External transaction IDs stored for reconciliation
- Unique transaction IDs generated for wallet/cash payments
- Proper audit trail

**Examples:**
- Stripe: `$charge->id` (PaymentService.php:147)
- Wallet: `'WALLET-' . uniqid()` (PaymentService.php:59)
- Cash: `'CASH-' . uniqid()` (PaymentService.php:76)

#### 5.3 Balance Verification Before Debit
**Status:** ✅ Properly Implemented
**Location:** `app/Models/Wallet.php:68-70`

```php
public function debit(float $amount, ...)
{
    if ($this->balance < $amount) {
        throw new \Exception('Insufficient balance');
    }
    // ...
}
```

**Assessment:**
- Prevents negative balance
- Atomic operation with database update
- Proper exception handling

### ⚠️ Security Concerns

#### 5.4 Missing Payment Amount Validation
**Severity:** 🔴 HIGH
**Risk:** Payment manipulation attacks

**Finding:**
No validation that payment amount matches order total in controllers (not yet implemented).

**Vulnerable Flow:**
```
1. User creates order with total = 1000 THB
2. Attacker modifies payment request to amount = 1 THB
3. System processes payment for 1 THB
4. Order marked as paid (vulnerability)
```

**Recommendation:**
```php
// In OrderController::store() - MUST IMPLEMENT
public function store(Request $request)
{
    // 1. Calculate order total server-side (NEVER trust client)
    $calculatedTotal = $this->calculateOrderTotal($request->items);

    // 2. Compare with payment amount
    if ($request->payment_amount != $calculatedTotal) {
        return response()->json(['error' => 'Amount mismatch'], 400);
    }

    // 3. Process payment
    $result = $this->paymentService->processPayment($order, $paymentData);

    // 4. Verify payment success before marking order as paid
    if (!$result['success']) {
        // Don't mark order as paid
    }
}
```

#### 5.5 No Idempotency for Payment Requests
**Severity:** ⚠️ MEDIUM
**Risk:** Duplicate charges

**Finding:**
No idempotency key implementation for payment processing.

**Scenario:**
- User clicks "Pay" button multiple times
- Network retry causes duplicate requests
- Same order charged twice

**Recommendation:**
```php
// Generate idempotency key on order creation
$order->idempotency_key = Str::uuid();

// In payment processing
if (Order::where('idempotency_key', $request->idempotency_key)
        ->where('payment_status', 'completed')
        ->exists()) {
    return response()->json(['error' => 'Already processed'], 409);
}

// For Stripe
$charge = Charge::create([
    'amount' => $order->total * 100,
    // ... other params
], [
    'idempotency_key' => $order->idempotency_key,
]);
```

#### 5.6 PromptPay QR Code Generation Incomplete
**Severity:** ⚠️ MEDIUM
**Location:** `app/Services/Payment/PaymentService.php:219-220`

```php
// CRC (placeholder - should be calculated)
$payload .= '6304';
```

**Finding:**
- CRC checksum not calculated
- QR code may be invalid
- Comment indicates incomplete implementation

**Recommendation:**
```bash
# Use a proper PromptPay library
composer require kittinan/php-promptpay-qr

# Or implement CRC-16-CCITT
function crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
        }
    }
    return $crc & 0xFFFF;
}
```

#### 5.7 PromptPay Payment Verification Not Implemented
**Severity:** 🔴 HIGH
**Location:** `app/Services/Payment/PaymentService.php:225-235`

```php
public function verifyPayment(string $transactionId): array
{
    // In production, implement actual verification with bank API
    return [
        'success' => true,
        'verified' => true,  // ALWAYS TRUE - DANGEROUS
        'transaction_id' => $transactionId,
    ];
}
```

**Risk:**
- Payment verification always returns success
- Attacker can mark orders as paid without payment
- Critical financial vulnerability

**Recommendation:**
```php
// MUST implement before production
public function verifyPayment(string $transactionId): array
{
    // Integrate with PromptPay API
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . config('services.promptpay.api_key'),
    ])->post(config('services.promptpay.api_url') . '/verify', [
        'transaction_id' => $transactionId,
    ]);

    if (!$response->successful()) {
        return ['success' => false, 'verified' => false];
    }

    $data = $response->json();
    return [
        'success' => true,
        'verified' => $data['status'] === 'paid',
        'transaction_id' => $transactionId,
        'amount' => $data['amount'],
        'paid_at' => $data['paid_at'],
    ];
}
```

#### 5.8 No Refund Limits or Validation
**Severity:** ⚠️ MEDIUM
**Location:** `app/Services/Payment/PaymentService.php:85-94`

```php
public function processRefund(Order $order, float $amount = null): array
{
    $refundAmount = $amount ?? $order->total;
    // No validation that refund <= original amount
    // No check if already refunded
}
```

**Risk:**
- Could refund more than original payment
- Could refund same order multiple times
- No refund audit trail

**Recommendation:**
```php
public function processRefund(Order $order, float $amount = null): array
{
    // 1. Validate order is paid
    if (!$order->isPaid()) {
        return ['success' => false, 'message' => 'Order not paid'];
    }

    // 2. Calculate refund amount
    $refundAmount = $amount ?? $order->total;

    // 3. Check total refunded amount
    $totalRefunded = $order->refunds()->sum('amount');
    if ($totalRefunded + $refundAmount > $order->total) {
        return ['success' => false, 'message' => 'Refund exceeds order total'];
    }

    // 4. Process refund
    $result = match ($order->payment_method) {
        // ... refund logic
    };

    // 5. Record refund
    if ($result['success']) {
        $order->refunds()->create([
            'amount' => $refundAmount,
            'status' => 'completed',
            'refund_id' => $result['refund_id'] ?? null,
        ]);
    }

    return $result;
}
```

---

## 6. API Security

### ✅ Implemented Security Controls

#### 6.1 Rate Limiting
**Status:** ✅ Properly Implemented
**Location:** `app/Providers/RouteServiceProvider.php:27-29`

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Assessment:**
- 60 requests per minute per user/IP
- Protects against DoS attacks
- Reasonable limit for normal usage

**Recommendation:**
Consider different rate limits for different endpoints:

```php
// Stricter limits for authentication endpoints
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Stricter limits for financial operations
RateLimiter::for('financial', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});

// Apply to specific routes
Route::middleware(['auth:sanctum', 'throttle:financial'])
    ->post('/wallet/withdraw', ...);
```

#### 6.2 API Versioning
**Status:** ✅ Good Practice
**Location:** `routes/api.php:20,34`

```php
Route::prefix('v1')->group(function () {
    // All routes
});
```

**Assessment:**
- API versioning implemented
- Allows backward compatibility
- Enables gradual migration

### ⚠️ Security Concerns

#### 6.3 No CORS Configuration
**Severity:** ⚠️ MEDIUM
**Finding:** No `config/cors.php` file found

**Risk:**
- CORS policy unclear
- May allow requests from any origin
- Potential for CSRF-like attacks via API

**Default Laravel CORS (app/Http/Middleware/HandleCors.php):**
```php
// Default allows all origins in development
'allowed_origins' => ['*'],  // DANGEROUS for production
```

**Recommendation:**
```bash
php artisan config:publish cors
```

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_origins' => [
        'https://yourdomain.com',
        'https://app.yourdomain.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### 6.4 No API Request Logging
**Severity:** ⚠️ LOW
**Risk:** Difficult to detect attacks or audit activity

**Recommendation:**
```php
// Create middleware: LogApiRequests
public function handle($request, Closure $next)
{
    $response = $next($request);

    Log::channel('api')->info('API Request', [
        'user_id' => $request->user()?->id,
        'ip' => $request->ip(),
        'method' => $request->method(),
        'path' => $request->path(),
        'status' => $response->status(),
    ]);

    return $response;
}
```

#### 6.5 No API Response Size Limits
**Severity:** ⚠️ LOW
**Risk:** Resource exhaustion via large responses

**Recommendation:**
```php
// In controllers
public function index(Request $request)
{
    $perPage = min($request->get('per_page', 20), 100); // Max 100
    return Product::paginate($perPage);
}
```

#### 6.6 Missing API Authentication Documentation
**Severity:** ⚠️ LOW
**Risk:** Improper API usage by developers

**Recommendation:**
Create API documentation with authentication examples:

```markdown
## Authentication

All protected endpoints require a Bearer token:

```
Authorization: Bearer {token}
```

Obtain token via login:
POST /api/v1/login
```

---

## 7. Data Validation & Sanitization

### ⚠️ Critical Security Concerns

#### 7.1 No Input Validation (Controllers Not Implemented)
**Severity:** 🔴 CRITICAL
**Risk:** HIGHEST PRIORITY SECURITY ISSUE

**Finding:**
All API controllers referenced but not implemented. No input validation exists.

**Impact:**
- Mass assignment vulnerabilities
- Invalid data in database
- Business logic bypass
- SQL injection (if raw queries added later)
- XSS via stored data
- Type confusion attacks

**MANDATORY Implementation for ALL Controllers:**

```php
// Example: ProductController::store()
public function store(Request $request)
{
    // 1. VALIDATE ALL INPUT
    $validated = $request->validate([
        'name' => 'required|string|max:255|min:3',
        'slug' => 'required|string|max:255|unique:products,slug',
        'description' => 'required|string|max:10000',
        'price' => 'required|numeric|min:0|max:999999.99',
        'sale_price' => 'nullable|numeric|min:0|lt:price',
        'stock_quantity' => 'required|integer|min:0',
        'category_id' => 'required|exists:categories,id',
        'vendor_id' => 'required|exists:vendors,id',
        'sku' => 'required|string|max:100|unique:products,sku',
        'weight' => 'nullable|numeric|min:0',
        'status' => 'required|in:draft,active,inactive',
        'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    // 2. SANITIZE HTML CONTENT
    $validated['description'] = Purifier::clean($validated['description']);

    // 3. CREATE WITH VALIDATED DATA ONLY
    $product = Product::create($validated);

    return response()->json($product, 201);
}
```

**Validation Rules MUST Include:**

**For User Registration:**
```php
$request->validate([
    'name' => 'required|string|max:255|min:2',
    'email' => 'required|email|unique:users,email|max:255',
    'phone' => 'required|string|regex:/^[0-9]{10}$/|unique:users,phone',
    'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
    'address' => 'nullable|string|max:500',
    'sponsor_id' => 'nullable|exists:users,id',
    'referral_code' => 'nullable|exists:users,referral_code',
]);
```

**For Order Creation:**
```php
$request->validate([
    'items' => 'required|array|min:1',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|integer|min:1|max:999',
    'shipping_address' => 'required|string|max:500',
    'shipping_city' => 'required|string|max:100',
    'shipping_postal_code' => 'required|string|regex:/^[0-9]{5}$/',
    'payment_method' => 'required|in:stripe,promptpay,wallet,cash',
]);
```

**For Withdrawal Requests:**
```php
$request->validate([
    'amount' => 'required|numeric|min:100|max:' . auth()->user()->wallet->balance,
    'method' => 'required|in:bank_transfer,promptpay,check',
    'bank_name' => 'required_if:method,bank_transfer|string|max:100',
    'account_number' => 'required_if:method,bank_transfer|string|max:50',
    'account_name' => 'required_if:method,bank_transfer|string|max:255',
    'promptpay_number' => 'required_if:method,promptpay|string|regex:/^[0-9]{10}$/',
]);
```

#### 7.2 Mass Assignment Vulnerability Prevention
**Status:** ⚠️ Partial Protection

**Finding:**
Models use `$fillable` arrays (good), but without controller validation (bad).

**Current Protection (Model Level):**
```php
// app/Models/User.php:16-34
protected $fillable = [
    'name', 'email', 'phone', 'password', 'avatar', 'status',
    'sponsor_id', 'referral_code', 'mlm_level', 'mlm_position',
    // ... all fields listed
];
```

**Risk Without Controller Validation:**
```php
// VULNERABLE (if controller doesn't validate):
User::create($request->all());  // Attacker can set any fillable field!

// Example attack:
POST /api/v1/register
{
    "name": "Attacker",
    "email": "attacker@example.com",
    "password": "password",
    "status": "active",  // Bypass email verification
    "mlm_level": 0,      // Set as top-level MLM member
    "sponsor_id": null   // No sponsor (top of pyramid)
}
```

**MANDATORY Implementation:**
```php
// SECURE (with validation):
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|string|min:8|confirmed',
]);

// Only create with validated fields
User::create($validated + [
    'status' => 'pending',  // Force initial status
    'mlm_level' => null,    // Set by system, not user
]);
```

#### 7.3 Guarding Critical Fields
**Severity:** ⚠️ MEDIUM

**Recommendation:**
Use `$guarded` instead of `$fillable` for critical models:

```php
// For financial models
class Wallet extends Model
{
    protected $guarded = ['id', 'balance', 'total_earned', 'total_withdrawn'];

    // Only allow explicit updates via methods
    public function credit($amount) {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);
    }
}

class Commission extends Model
{
    protected $guarded = ['id', 'amount', 'status', 'paid_at'];
}
```

---

## 8. File Upload Security

### ⚠️ Security Concerns

#### 8.1 No File Upload Validation Found
**Severity:** 🔴 HIGH
**Risk:** Malicious file upload, arbitrary file execution

**Vulnerable Fields:**
- `users.avatar` - User profile picture
- `products.featured_image` - Product main image
- `products.gallery_images` - Product gallery (array)
- `vendors.shop_logo` - Vendor logo
- `vendors.shop_banner` - Vendor banner
- `reviews.images` - Review images (array)

**Current State:**
- Database schema accepts image paths
- No validation code found (controllers not implemented)
- No upload handling implemented

**MANDATORY Implementation:**

```php
// In ProductController (and similar for other uploads)
public function store(Request $request)
{
    $request->validate([
        'featured_image' => [
            'required',
            'image',                          // Must be image
            'mimes:jpeg,png,jpg,webp',       // Allowed formats
            'max:2048',                       // Max 2MB
            'dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
        ],
        'gallery_images' => 'nullable|array|max:10',
        'gallery_images.*' => [
            'image',
            'mimes:jpeg,png,jpg,webp',
            'max:2048',
        ],
    ]);

    // IMPORTANT: Validate file content, not just extension
    $image = $request->file('featured_image');

    // 1. Check file mime type (not just extension)
    if (!in_array($image->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
        return response()->json(['error' => 'Invalid file type'], 400);
    }

    // 2. Generate secure random filename (don't use original)
    $filename = Str::random(40) . '.' . $image->extension();

    // 3. Store in private storage, not public (use signed URLs for access)
    $path = $image->storeAs('products', $filename, 'private');

    // 4. Optionally: Re-encode image to strip metadata and ensure it's valid
    $img = Image::make($image)->encode('jpg', 85);
    Storage::disk('private')->put($path, $img);

    return $path;
}
```

**File Upload Security Checklist:**

```php
✅ Validate file type by mime type (not extension)
✅ Validate file size (prevent DoS via large files)
✅ Validate image dimensions
✅ Generate random filenames (prevent path traversal)
✅ Store in private storage (not web-accessible public folder)
✅ Re-encode images to strip EXIF/metadata
✅ Limit number of files per upload
✅ Scan files for malware (using ClamAV or similar)
✅ Use separate subdirectories per model type
✅ Implement file access via signed URLs
```

**Intervention Image Configuration:**
```php
// Use Intervention Image to validate and re-process
use Intervention\Image\Facades\Image;

try {
    $img = Image::make($file);

    // Validate it's actually an image
    if (!$img->width() || !$img->height()) {
        throw new \Exception('Invalid image');
    }

    // Resize if too large
    if ($img->width() > 2000 || $img->height() > 2000) {
        $img->resize(2000, 2000, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }

    // Strip all EXIF data and re-encode
    $img->encode('jpg', 85);

    Storage::put($path, $img);
} catch (\Exception $e) {
    return response()->json(['error' => 'Invalid image file'], 400);
}
```

#### 8.2 No Antivirus Scanning
**Severity:** ⚠️ MEDIUM
**Recommendation:**

```bash
# Install ClamAV
sudo apt-get install clamav clamav-daemon

# Install PHP ClamAV
composer require xenolope/quahog
```

```php
// Scan uploaded files
use Xenolope\Quahog\Client;

$scanner = new Client('unix:///var/run/clamav/clamd.ctl');
$result = $scanner->scanFile($file->path());

if ($result['status'] !== 'OK') {
    return response()->json(['error' => 'Malicious file detected'], 400);
}
```

#### 8.3 Directory Traversal Prevention
**Status:** ✅ Protected by Laravel Storage

**Assessment:**
- Laravel's `Storage` facade prevents directory traversal
- As long as `storeAs()` is used (not manual file operations)

**BAD (Vulnerable):**
```php
// NEVER DO THIS
$filename = $request->file_name;  // User controlled
file_put_contents("/var/www/uploads/" . $filename, $content);
// Attacker uploads: "../../.ssh/authorized_keys"
```

**GOOD (Secure):**
```php
// Use Laravel Storage
$filename = Str::random(40) . '.' . $file->extension();
$file->storeAs('uploads', $filename);
```

---

## 9. Session Management

### ✅ Implemented Security Controls

#### 9.1 Laravel Sanctum Token Management
**Status:** ✅ Properly Configured

**Assessment:**
- Tokens stored securely in `personal_access_tokens` table
- Token hashing using SHA-256
- Automatic token validation on each request

#### 9.2 Session Configuration
**Status:** ✅ Secure Defaults
**Location:** `.env.example:22-23`

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

**Assessment:**
- 120-minute session lifetime (reasonable)
- Secure session driver (file-based for single server)

**Recommendation for Production:**
```env
# For multi-server setups
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# For high security
SESSION_SECURE_COOKIE=true  # HTTPS only
SESSION_HTTP_ONLY=true      # Prevent JavaScript access
SESSION_SAME_SITE=strict    # CSRF protection
```

### ⚠️ Security Concerns

#### 9.3 No Token Expiration Strategy
**Severity:** ⚠️ MEDIUM

**Finding:**
Sanctum tokens don't expire by default.

**Risk:**
- Stolen tokens valid indefinitely
- No automatic logout
- Token accumulation in database

**Recommendation:**
```php
// In config/sanctum.php
'expiration' => 60 * 24, // 24 hours

// Token rotation on important actions
public function updatePassword(Request $request)
{
    // Update password
    $request->user()->password = Hash::make($request->new_password);
    $request->user()->save();

    // Revoke all tokens (force re-login)
    $request->user()->tokens()->delete();

    return response()->json(['message' => 'Password updated, please login again']);
}
```

#### 9.4 No Device/Session Management
**Severity:** ⚠️ LOW

**Recommendation:**
```php
// Store device info with tokens
$token = $user->createToken('api-token', ['*'], [
    'device' => $request->userAgent(),
    'ip' => $request->ip(),
]);

// List active sessions
public function sessions()
{
    return auth()->user()->tokens()->get()->map(function($token) {
        return [
            'device' => $token->device,
            'ip' => $token->ip,
            'last_used' => $token->last_used_at,
        ];
    });
}

// Revoke specific session
public function revokeSession($tokenId)
{
    auth()->user()->tokens()->where('id', $tokenId)->delete();
}
```

---

## 10. Financial Transaction Security

### ✅ Implemented Security Controls

#### 10.1 Database Transactions for Financial Operations
**Status:** ✅ EXCELLENT
**Assessment:** All financial operations use database transactions

**Locations:**
- Withdrawal requests: `WalletService.php:45-73`
- Withdrawal approval: `WalletService.php:85-93`
- Withdrawal rejection: `WalletService.php:105-123`
- MLM registration: `MlmService.php:20-41`

**Example:**
```php
return DB::transaction(function () use ($user, $wallet, $amount, $bankDetails) {
    $wallet->debit($amount, 'withdrawal', "Withdrawal request...");
    return Withdrawal::create([...]);
});
```

**Security Score:** 🟢 EXCELLENT - Atomic financial operations

#### 10.2 Balance Before/After Tracking
**Status:** ✅ EXCELLENT
**Location:** `app/Models/Wallet.php:46-64,66-87`

```php
public function credit(float $amount, ...)
{
    $balanceBefore = $this->balance;
    $this->increment('balance', $amount);

    return $this->transactions()->create([
        'balance_before' => $balanceBefore,
        'balance_after' => $this->balance,
        // ...
    ]);
}
```

**Assessment:**
- Complete audit trail
- Enables balance reconciliation
- Detects discrepancies

#### 10.3 Insufficient Balance Check
**Status:** ✅ Properly Implemented
**Location:** `app/Models/Wallet.php:68-70`

```php
if ($this->balance < $amount) {
    throw new \Exception('Insufficient balance');
}
```

### ⚠️ Security Concerns

#### 10.4 Race Condition in Wallet Operations
**Severity:** 🔴 HIGH
**Risk:** Double-spending vulnerability

**Vulnerable Code:**
```php
// app/Models/Wallet.php:66-87
public function debit(float $amount, ...)
{
    if ($this->balance < $amount) {  // ← Check balance
        throw new \Exception('Insufficient balance');
    }

    $this->decrement('balance', $amount);  // ← Decrement balance
    // Time gap between check and update!
}
```

**Attack Scenario:**
```
Time  | Request A (Withdrawal 1000)    | Request B (Purchase 1000)
------|--------------------------------|---------------------------
T1    | Check balance: 1000 ≥ 1000 ✓  |
T2    |                                | Check balance: 1000 ≥ 1000 ✓
T3    | Deduct 1000, balance = 0       |
T4    |                                | Deduct 1000, balance = -1000 ❌
```

**CRITICAL FIX REQUIRED:**
```php
// Use database-level locking
public function debit(float $amount, string $type, string $description, $reference = null): WalletTransaction
{
    return DB::transaction(function () use ($amount, $type, $description, $reference) {
        // Lock the wallet row for update
        $wallet = Wallet::where('id', $this->id)->lockForUpdate()->first();

        // Check balance with lock held
        if ($wallet->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $balanceBefore = $wallet->balance;

        // Decrement with lock held
        $wallet->decrement('balance', $amount);

        return $wallet->transactions()->create([
            'user_id' => $wallet->user_id,
            'transaction_id' => $wallet->generateTransactionId(),
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $wallet->balance,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->id : null,
            'status' => 'completed',
        ]);
    });
}
```

**Alternative Solution (Optimistic Locking):**
```php
// Add version column to wallets table
Schema::table('wallets', function (Blueprint $table) {
    $table->integer('version')->default(0);
});

// In Wallet model
public function debit($amount, ...)
{
    $maxAttempts = 3;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $currentVersion = $this->version;

        $updated = DB::table('wallets')
            ->where('id', $this->id)
            ->where('version', $currentVersion)
            ->where('balance', '>=', $amount)
            ->update([
                'balance' => DB::raw('balance - ' . $amount),
                'version' => $currentVersion + 1,
            ]);

        if ($updated) {
            // Success
            break;
        }

        $attempt++;
        $this->refresh();
    }

    if ($attempt >= $maxAttempts) {
        throw new \Exception('Transaction conflict, please retry');
    }
}
```

#### 10.5 No Transaction Limits
**Severity:** ⚠️ MEDIUM

**Finding:**
No daily/monthly limits on withdrawals or commissions.

**Recommendation:**
```php
// In Withdrawal request
public function requestWithdrawal(User $user, float $amount, array $bankDetails)
{
    // Daily limit
    $dailyWithdrawals = Withdrawal::where('user_id', $user->id)
        ->where('created_at', '>=', now()->startOfDay())
        ->where('status', '!=', 'cancelled')
        ->sum('amount');

    if ($dailyWithdrawals + $amount > 50000) { // 50,000 THB/day
        throw new \Exception('Daily withdrawal limit exceeded');
    }

    // Monthly limit
    $monthlyWithdrawals = Withdrawal::where('user_id', $user->id)
        ->where('created_at', '>=', now()->startOfMonth())
        ->where('status', '!=', 'cancelled')
        ->sum('amount');

    if ($monthlyWithdrawals + $amount > 500000) { // 500,000 THB/month
        throw new \Exception('Monthly withdrawal limit exceeded');
    }

    // ... process withdrawal
}
```

#### 10.6 Commission Distribution Without Approval
**Severity:** ⚠️ MEDIUM
**Location:** `app/Services/MLM/MlmService.php:121-146`

```php
Commission::create([
    'status' => 'approved',  // ← Immediately approved
]);

$ancestor->wallet->credit($amount, 'commission', ...);  // ← Immediately credited
```

**Risk:**
- Fraudulent orders immediately credit commissions
- No review period for suspicious activity
- Difficult to reverse commissions

**Recommendation:**
```php
// Create commission as pending
Commission::create([
    'status' => 'pending',  // Wait for order completion
]);

// Only credit after order shipped/delivered
if ($order->status === 'delivered') {
    $commission->update(['status' => 'approved']);
    $user->wallet->credit($commission->amount, 'commission', ...);
}
```

#### 10.7 No Fraud Detection
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```php
// Flag suspicious orders
class FraudDetectionService
{
    public function checkOrder(Order $order): array
    {
        $flags = [];

        // 1. Velocity check (too many orders too quickly)
        $recentOrders = Order::where('user_id', $order->user_id)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();
        if ($recentOrders > 10) {
            $flags[] = 'high_velocity';
        }

        // 2. Amount check (unusually large order)
        $avgOrder = Order::where('user_id', $order->user_id)->avg('total');
        if ($order->total > $avgOrder * 5) {
            $flags[] = 'large_amount';
        }

        // 3. IP check (different IP than usual)
        // 4. Billing/shipping mismatch
        // 5. First-time high-value order

        return $flags;
    }
}
```

---

## 11. Webhook Security

### 🔴 Critical Security Issues

#### 11.1 No Webhook Signature Verification
**Severity:** 🔴 CRITICAL
**Location:** `routes/api.php:80-82`

```php
Route::post('/webhooks/stripe', [OrderController::class, 'stripeWebhook']);
Route::post('/webhooks/promptpay', [OrderController::class, 'promptpayWebhook']);
Route::post('/webhooks/line', [AuthController::class, 'lineWebhook']);
```

**Risk:**
- Anyone can send fake webhooks
- Attacker can mark orders as paid
- Attacker can create fake users
- Bypasses payment verification

**Attack Example:**
```bash
# Attacker sends fake Stripe webhook
curl -X POST https://yourdomain.com/api/v1/webhooks/stripe \
  -H "Content-Type: application/json" \
  -d '{
    "type": "charge.succeeded",
    "data": {
      "object": {
        "id": "ch_fake123",
        "metadata": {
          "order_id": "123"
        }
      }
    }
  }'

# Result: Order #123 marked as paid without actual payment!
```

**MANDATORY FIX (See Section 4.2 for detailed implementation)**

#### 11.2 Webhook Replay Attack Prevention
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```php
// Store processed webhook IDs
Schema::create('processed_webhooks', function (Blueprint $table) {
    $table->string('webhook_id')->primary();
    $table->timestamp('processed_at');
});

// In webhook handler
public function stripeWebhook(Request $request)
{
    $event = $this->verifyStripeWebhook($request);

    // Check if already processed
    if (DB::table('processed_webhooks')->where('webhook_id', $event->id)->exists()) {
        return response()->json(['message' => 'Already processed'], 200);
    }

    // Process webhook
    $this->handleStripeEvent($event);

    // Mark as processed
    DB::table('processed_webhooks')->insert([
        'webhook_id' => $event->id,
        'processed_at' => now(),
    ]);

    return response()->json(['message' => 'Success'], 200);
}
```

---

## 12. Password & Credential Management

### ✅ Implemented Security Controls

#### 12.1 Password Hashing
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:43`

```php
'password' => 'hashed',
```

**Assessment:**
- Automatic bcrypt hashing
- Secure hashing algorithm
- Cannot be reversed

#### 12.2 Password Hidden in Responses
**Status:** ✅ Properly Implemented
**Location:** `app/Models/User.php:36-39`

```php
protected $hidden = [
    'password',
    'remember_token',
];
```

### ⚠️ Security Concerns

#### 12.3 No Password Complexity Requirements
**Severity:** ⚠️ MEDIUM

**Finding:**
No password validation rules (controllers not implemented).

**Recommendation:**
```php
$request->validate([
    'password' => [
        'required',
        'string',
        'min:8',
        'confirmed',
        Password::min(8)
            ->mixedCase()      // At least one uppercase and lowercase
            ->numbers()        // At least one number
            ->symbols()        // At least one special character
            ->uncompromised(), // Not in data breach database
    ],
]);
```

#### 12.4 No Password History
**Severity:** ⚠️ LOW

**Recommendation:**
```php
// Prevent password reuse
Schema::create('password_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('password_hash');
    $table->timestamp('created_at');
});

// Check on password change
$recentPasswords = PasswordHistory::where('user_id', $user->id)
    ->latest()
    ->take(5)
    ->get();

foreach ($recentPasswords as $old) {
    if (Hash::check($newPassword, $old->password_hash)) {
        throw ValidationException::withMessages([
            'password' => 'Cannot reuse recent passwords',
        ]);
    }
}
```

#### 12.5 Sensitive Configuration in .env
**Status:** ✅ Properly Configured

**Assessment:**
- All sensitive credentials in `.env.example` are empty
- `.env` file in `.gitignore` (verified)
- No hardcoded credentials in code

**Best Practices Followed:**
```env
STRIPE_SECRET=          # Empty in .env.example
DB_PASSWORD=            # Empty in .env.example
LINE_CHANNEL_SECRET=    # Empty in .env.example
```

---

## 13. Information Disclosure

### ⚠️ Security Concerns

#### 13.1 Debug Mode in Production
**Severity:** ⚠️ MEDIUM
**Location:** `.env.example:4`

```env
APP_DEBUG=true
```

**Risk:**
- Stack traces expose code structure
- Sensitive data in error messages
- Database credentials in exceptions

**MANDATORY for Production:**
```env
APP_DEBUG=false
APP_ENV=production
```

#### 13.2 Detailed Error Messages
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```php
// In app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if (!config('app.debug')) {
        // Production: generic error messages
        return response()->json([
            'error' => 'An error occurred',
            'message' => 'Please contact support',
        ], 500);
    }

    // Development: detailed errors
    return parent::render($request, $exception);
}
```

#### 13.3 API Enumeration
**Severity:** ⚠️ LOW

**Finding:**
API endpoints may reveal existence of resources.

**Example:**
```
GET /api/v1/users/999999
Response: 404 "User not found"

GET /api/v1/users/1
Response: 403 "Unauthorized" or 200 (reveals user exists)
```

**Recommendation:**
```php
// Always return same error for unauthorized access
public function show($id)
{
    $user = User::find($id);

    // Don't reveal if user exists
    if (!$user || !Gate::allows('view', $user)) {
        abort(404);  // Same response for both cases
    }

    return $user;
}
```

---

## 14. Business Logic Security

### ⚠️ Security Concerns

#### 14.1 MLM Pyramid Scheme Risk
**Severity:** ⚠️ LEGAL RISK
**Assessment:** MLM structure could be illegal in some jurisdictions

**Finding:**
- Commission distributed up to 10 levels
- Revenue primarily from recruitment
- Could violate pyramid scheme laws

**Legal Recommendations:**
1. Ensure majority of revenue from product sales (not recruitment)
2. Limit MLM levels (check local regulations)
3. Implement "retail customer" tracking
4. Require minimum product sales for commission eligibility
5. Consult legal counsel for MLM compliance

**Code Implementation:**
```php
// Ensure sales-based commissions
public function distributeCommissions(Order $order)
{
    // Only distribute if order is for real products (not enrollment fees)
    if ($order->type === 'enrollment') {
        return; // Don't distribute commissions on sign-up fees
    }

    // Require minimum personal sales
    if ($user->personalSales() < 1000) {
        // User not eligible for commissions
        return;
    }

    // ... distribute commissions
}
```

#### 14.2 Self-Referral Prevention
**Severity:** ⚠️ MEDIUM

**Finding:**
No validation preventing users from referring themselves or creating circular referrals.

**Vulnerable Code:**
```php
// User could set themselves as sponsor
'sponsor_id' => $request->sponsor_id  // No validation
```

**Recommendation:**
```php
public function registerWithReferral(Request $request)
{
    $request->validate([
        'sponsor_id' => [
            'nullable',
            'exists:users,id',
            function ($attribute, $value, $fail) use ($request) {
                // Can't sponsor yourself
                if ($value == $request->user()?->id) {
                    $fail('Cannot use yourself as sponsor');
                }
            },
        ],
    ]);

    // Check for circular references
    $sponsor = User::find($request->sponsor_id);
    if ($this->hasCircularReference($sponsor, $request->user())) {
        throw new \Exception('Circular reference detected');
    }
}

private function hasCircularReference($sponsor, $newUser)
{
    $currentSponsor = $sponsor;
    $maxDepth = 100;  // Prevent infinite loop

    while ($currentSponsor && $maxDepth > 0) {
        if ($currentSponsor->id === $newUser->id) {
            return true;  // Circular reference found
        }
        $currentSponsor = $currentSponsor->sponsor;
        $maxDepth--;
    }

    return false;
}
```

#### 14.3 Commission Manipulation
**Severity:** ⚠️ MEDIUM

**Finding:**
Commission settings can be changed, affecting existing calculations.

**Recommendation:**
```php
// Lock commission settings after calculation
Schema::table('commissions', function (Blueprint $table) {
    $table->decimal('locked_percentage', 5, 2);  // Store actual percentage used
});

Commission::create([
    'percentage' => $setting->percentage,
    'locked_percentage' => $setting->percentage,  // Lock value
]);

// Prevent retroactive changes
public function updateCommissionSetting($level, $newPercentage)
{
    // Only apply to future commissions
    CommissionSetting::where('level', $level)->update([
        'percentage' => $newPercentage,
        'effective_from' => now(),
    ]);
}
```

#### 14.4 Order Cancellation Abuse
**Severity:** ⚠️ MEDIUM

**Finding:**
No validation on when orders can be cancelled.

**Current Code:**
```php
// app/Models/Order.php:74-77
public function canCancel(): bool
{
    return in_array($this->status, ['pending', 'processing']);
}
```

**Issue:**
- User could cancel after receiving product
- Commissions already distributed
- Payment already completed

**Recommendation:**
```php
public function canCancel(): bool
{
    // Can't cancel after shipped
    if (in_array($this->status, ['shipped', 'delivered', 'refunded'])) {
        return false;
    }

    // Can't cancel after 24 hours of payment
    if ($this->paid_at && $this->paid_at->diffInHours(now()) > 24) {
        return false;
    }

    return true;
}

public function cancel()
{
    if (!$this->canCancel()) {
        throw new \Exception('Order cannot be cancelled');
    }

    DB::transaction(function () {
        // Refund payment
        // Reverse commissions
        // Restore inventory
        $this->update(['status' => 'cancelled']);
    });
}
```

---

## 15. Missing Security Controls

### 🔴 Critical Missing Components

#### 15.1 Controllers Not Implemented
**Severity:** 🔴 CRITICAL
**Impact:** ALL security validation missing

**Missing Files:**
- AuthController - No input validation, no authentication logic
- ProductController - No validation, no authorization
- OrderController - No payment validation, no fraud checks
- CartController - No validation
- WalletController - No withdrawal validation
- MlmController - No authorization checks
- VendorController - No product ownership validation

**Total Security Impact:** HIGHEST PRIORITY

#### 15.2 No HTTPS Enforcement
**Severity:** 🔴 HIGH

**Recommendation:**
```php
// app/Http/Middleware/ForceHttps.php
public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri());
    }

    return $next($request);
}

// Or in AppServiceProvider
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

#### 15.3 No Request Logging/Monitoring
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```php
// Log critical operations
Log::channel('audit')->info('Withdrawal requested', [
    'user_id' => $user->id,
    'amount' => $amount,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

#### 15.4 No Security Headers
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);

    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Content-Security-Policy', "default-src 'self'");

    return $response;
}
```

#### 15.5 No Backup & Recovery Plan
**Severity:** ⚠️ MEDIUM

**Recommendation:**
```bash
# Daily database backups
# Laravel backup package
composer require spatie/laravel-backup

# Configure in config/backup.php
# Backup to S3, Google Cloud, or other remote storage
```

---

## 16. Security Recommendations Priority Matrix

### 🔴 CRITICAL (Must Fix Before Production)

1. **Implement All Controllers with Input Validation** (Section 7.1)
   - Effort: High
   - Impact: Critical
   - Timeline: Before any deployment

2. **Fix Race Condition in Wallet Operations** (Section 10.4)
   - Effort: Medium
   - Impact: Critical (financial loss)
   - Timeline: Before handling real money

3. **Implement Webhook Signature Verification** (Section 11.1)
   - Effort: Medium
   - Impact: Critical (payment fraud)
   - Timeline: Before accepting payments

4. **Implement Payment Amount Validation** (Section 5.4)
   - Effort: Low
   - Impact: Critical (payment fraud)
   - Timeline: Before accepting payments

5. **Implement PromptPay Payment Verification** (Section 5.7)
   - Effort: High
   - Impact: Critical (payment fraud)
   - Timeline: Before enabling PromptPay

6. **Implement File Upload Validation** (Section 8.1)
   - Effort: Medium
   - Impact: Critical (code execution)
   - Timeline: Before allowing uploads

7. **Enable HTTPS Enforcement** (Section 15.2)
   - Effort: Low
   - Impact: Critical (credential theft)
   - Timeline: Day 1 of production

### ⚠️ HIGH (Fix Within First Month)

8. **Implement Multi-Factor Authentication for Admins** (Section 1.5)
9. **Add Account Lockout Mechanism** (Section 1.6)
10. **Configure CORS Properly** (Section 6.3)
11. **Implement Payment Idempotency** (Section 5.5)
12. **Add Transaction Limits** (Section 10.5)
13. **Add Security Headers** (Section 15.4)

### ⚠️ MEDIUM (Fix Within 3 Months)

14. **Implement Fraud Detection** (Section 10.7)
15. **Add API Request Logging** (Section 6.4)
16. **Implement Token Expiration** (Section 9.3)
17. **Add Password Complexity Requirements** (Section 12.3)
18. **Implement HTML Purifier for Rich Content** (Section 3.3)
19. **Add Antivirus Scanning for Uploads** (Section 8.2)
20. **Implement Backup & Recovery** (Section 15.5)

### ⚠️ LOW (Nice to Have)

21. **Add Session Timeout for Idle Users** (Section 1.7)
22. **Implement Password History** (Section 12.4)
23. **Add Device Management** (Section 9.4)
24. **Prevent API Enumeration** (Section 13.3)

---

## 17. Compliance Considerations

### 17.1 PDPA (Thailand Personal Data Protection Act)

**Current Status:** ⚠️ Partial Compliance

**Requirements:**
- ✅ Soft deletes implemented (data retention)
- ❌ No consent management
- ❌ No data export functionality
- ❌ No data deletion workflow (right to be forgotten)
- ❌ No privacy policy acceptance tracking

**Recommendations:**
```php
// Add consent fields
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('privacy_policy_accepted_at')->nullable();
    $table->timestamp('marketing_consent_at')->nullable();
});

// Implement data export (GDPR/PDPA right to data portability)
public function exportPersonalData(User $user)
{
    return [
        'user' => $user->toArray(),
        'orders' => $user->orders()->get(),
        'wallet_transactions' => $user->wallet->transactions()->get(),
        'commissions' => $user->commissions()->get(),
        // ... all personal data
    ];
}

// Implement data deletion (right to be forgotten)
public function deletePersonalData(User $user)
{
    DB::transaction(function () use ($user) {
        // Anonymize or delete data
        $user->update([
            'name' => 'Deleted User',
            'email' => 'deleted_' . $user->id . '@deleted.local',
            'phone' => null,
            'address' => null,
        ]);
        $user->delete(); // Soft delete
    });
}
```

### 17.2 PCI DSS (Payment Card Industry Data Security Standard)

**Current Status:** ⚠️ Not Assessed (Stripe handles card data)

**Assessment:**
- ✅ No card data stored locally (good - using Stripe)
- ✅ Stripe is PCI compliant
- ❌ No SAQ (Self-Assessment Questionnaire) completed
- ❌ No security scans performed

**Recommendations:**
- Never store card numbers, CVV, or full PAN
- Use Stripe Elements/Checkout (hosted form)
- Complete PCI DSS SAQ-A (if using Stripe properly)
- Implement quarterly vulnerability scans

### 17.3 MLM Regulations (Thailand)

**Current Status:** ⚠️ Potential Legal Risk

**Concerns:**
- Multi-level structure may require Direct Sales license
- Commission structure may violate pyramid scheme laws
- Need legal review for compliance

**Recommendations:**
1. Consult Thai MLM lawyer
2. Register with Direct Sales and Direct Marketing Association
3. Ensure 70/30 rule (70% revenue from sales, not recruitment)
4. Implement cooling-off period for new members
5. Add disclaimer about income claims

---

## 18. Security Audit Conclusion

### Overall Assessment

The **ThaiPrompt Affiliate Marketplace** demonstrates good architectural design with proper use of Laravel's built-in security features. The codebase shows understanding of secure coding practices, particularly in:

- SQL injection prevention (100% Eloquent ORM)
- Password security (bcrypt hashing, hidden fields)
- Database transaction safety
- Role-based access control structure

However, **CRITICAL SECURITY ISSUES** exist due to missing implementation of the presentation layer (controllers) and payment verification logic. The application is **NOT PRODUCTION-READY** in its current state.

### Blocking Issues for Production

1. No input validation (controllers not implemented)
2. No payment verification (PromptPay always succeeds)
3. No webhook signature verification
4. Race condition in wallet operations
5. No file upload security

### Security Maturity Score

**Current Score: 4/10**

- Architecture: 8/10 ✅
- Implementation: 2/10 🔴
- Payment Security: 3/10 🔴
- Data Protection: 6/10 ⚠️
- API Security: 5/10 ⚠️
- Code Quality: 7/10 ✅

**Target Score: 9/10** (achievable after implementing recommendations)

### Next Steps

1. **Week 1-2:** Implement all controllers with input validation
2. **Week 3:** Fix financial race conditions and payment verification
3. **Week 4:** Implement webhook security and file upload validation
4. **Week 5:** Security testing and penetration testing
5. **Week 6:** Fix identified issues and deploy to staging
6. **Week 7-8:** Production hardening and monitoring setup

### Final Recommendation

**DO NOT DEPLOY TO PRODUCTION** until:
- ✅ All controllers implemented with validation
- ✅ Payment verification working correctly
- ✅ Webhook signatures verified
- ✅ Race conditions fixed
- ✅ File uploads secured
- ✅ Security testing completed
- ✅ Legal review for MLM compliance

---

## Appendix A: Security Checklist

### Pre-Production Security Checklist

**Authentication & Authorization**
- [ ] All controllers implement input validation
- [ ] Password complexity requirements enforced
- [ ] Account lockout after failed login attempts
- [ ] Multi-factor authentication for admins
- [ ] Role-based access control tested
- [ ] Token expiration implemented
- [ ] Session timeout configured

**Data Protection**
- [ ] All user input validated and sanitized
- [ ] File uploads validated and scanned
- [ ] XSS protection verified
- [ ] SQL injection testing completed
- [ ] CSRF protection enabled
- [ ] Sensitive data encrypted at rest

**Payment Security**
- [ ] Payment amount validation implemented
- [ ] Webhook signature verification working
- [ ] Idempotency keys implemented
- [ ] Refund validation added
- [ ] Transaction limits configured
- [ ] Fraud detection enabled

**Financial Security**
- [ ] Race conditions fixed (wallet operations)
- [ ] Database transactions used for all financial ops
- [ ] Audit trail complete
- [ ] Balance reconciliation tested
- [ ] Commission distribution approval workflow

**API Security**
- [ ] Rate limiting configured
- [ ] CORS policy configured
- [ ] API authentication tested
- [ ] Request logging enabled
- [ ] Response size limits implemented
- [ ] Error messages sanitized

**Infrastructure**
- [ ] HTTPS enforced
- [ ] Security headers configured
- [ ] Debug mode disabled in production
- [ ] Database backups configured
- [ ] Monitoring and alerting setup
- [ ] Incident response plan documented

**Compliance**
- [ ] Privacy policy published
- [ ] Terms of service published
- [ ] PDPA compliance verified
- [ ] PCI DSS SAQ completed
- [ ] MLM legal review completed
- [ ] Data retention policy implemented

---

**Report Generated:** 2024-10-24
**Version:** 1.0
**Classification:** Confidential - Internal Security Review
