# CLAUDE.md - AI Assistant Guide for Thaiprompt-Affiliate

> **Complete Guide for AI Assistants Working on the Thaiprompt-Affiliate Codebase**
>
> **Version: 3.0.0** | Last Updated: 2025-11-15 | Framework: Laravel 11 + Vite
>
> 🚀 **NOW USING V3 CODING STANDARDS** - Tailwind CSS + Alpine.js + SortableJS

---

## 🆕 VERSION 3.0 - BREAKING CHANGES

> **⚠️ IMPORTANT: เราอยู่ที่ Version 3 แล้ว - แนวทางการเขียนโค้ดเปลี่ยนแปลงไปจาก V2**

### 🎯 V3 Core Technologies

**Frontend Stack (เปลี่ยนแปลงหลัก)**:
- ✅ **Tailwind CSS** - Pure utility-first (ไม่ใช้ Bootstrap แล้ว)
- ✅ **Alpine.js** - Lightweight JS framework (ลด jQuery/Vue.js)
- ✅ **SortableJS** - Modern drag & drop (ไม่ใช้ jQuery UI)
- ✅ **Vite** - Fast build tool

**Backend Stack (ไม่เปลี่ยนแปลง)**:
- ✅ **Laravel 11** - Backend framework
- ✅ **Blade Templates** - Server-side rendering
- ✅ **MySQL 8.0+** - Database

### 📚 V3 Documentation (อ่านก่อนเริ่มงาน)

**บังคับอ่านสำหรับ V3**:

1. **[.claude/V3_CODING_GUIDELINES.md](.claude/V3_CODING_GUIDELINES.md)** ⭐ **NEW**
   - แนวทางการเขียนโค้ด V3
   - Tailwind + Alpine.js + SortableJS patterns
   - Component architecture
   - Performance best practices

2. **[.claude/V3_UI_DESIGN_SYSTEM.md](.claude/V3_UI_DESIGN_SYSTEM.md)** ⭐ **NEW**
   - UI/UX standards สำหรับ V3
   - Modern design patterns (Glassmorphism, 3D effects)
   - Component library
   - Animation & transitions

3. **[.claude/V3_ALPINE_BEST_PRACTICES.md](.claude/V3_ALPINE_BEST_PRACTICES.md)** ⭐ **NEW**
   - Alpine.js best practices
   - Component patterns
   - State management
   - Integration กับ SortableJS

### ⚠️ สิ่งที่เปลี่ยนแปลงจาก V2

| ด้าน | V2 (เก่า) ❌ | V3 (ใหม่) ✅ |
|------|--------------|-------------|
| **CSS Framework** | Bootstrap + Custom CSS | Tailwind CSS (pure) |
| **JavaScript** | jQuery + Vue.js | Alpine.js (หลัก) |
| **Drag & Drop** | jQuery UI Sortable | SortableJS |
| **UI Style** | Flat, Traditional | 3D, Glassmorphism, Gradients |
| **Bundle Size** | ~500KB+ | ~150KB (target) |

### 🚫 สิ่งที่ห้ามทำใน V3

- ❌ **ห้ามใช้ jQuery** สำหรับ DOM manipulation (ใช้ Alpine.js แทน)
- ❌ **ห้ามใช้ Bootstrap classes** (ใช้ Tailwind utilities แทน)
- ❌ **ห้ามใช้ jQuery UI Sortable** (ใช้ SortableJS แทน)
- ❌ **ห้ามสร้าง custom CSS classes** ใหม่ (ใช้ Tailwind, ยกเว้นจำเป็นจริงๆ)
- ❌ **ห้าม inline styles** (ใช้ Tailwind classes)

### ✅ สิ่งที่ต้องทำใน V3

- ✅ **ใช้ Tailwind CSS** สำหรับทุก styling
- ✅ **ใช้ Alpine.js** สำหรับ interactivity
- ✅ **Component-based architecture** (Blade + Alpine components)
- ✅ **Modern UI** - Glassmorphism, 3D effects, smooth animations
- ✅ **Performance-first** - Lazy loading, debounce, optimize
- ✅ **Dark mode support** - ทุก component
- ✅ **Mobile-first responsive** - ทดสอบทุก breakpoint

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Repository Overview](#repository-overview)
3. [Technology Stack](#technology-stack)
4. [Codebase Structure](#codebase-structure)
5. [Development Workflows](#development-workflows)
6. [Critical Guidelines](#critical-guidelines)
7. [Common Development Tasks](#common-development-tasks)
8. [Testing & Quality Assurance](#testing--quality-assurance)
9. [Deployment & Version Management](#deployment--version-management)
10. [Troubleshooting](#troubleshooting)
11. [Reference Documentation](#reference-documentation)

---

## Quick Start

### First-Time Setup Checklist

```bash
# 1. Clone repository (if not already cloned)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. Install Git Hooks (CRITICAL - do this first!)
bash scripts/git-hooks/install.sh

# 3. Install dependencies
composer install
npm install

# 4. Environment setup
cp .env.example .env
php artisan key:generate

# 5. Database setup
php artisan migrate:fresh --seed

# 6. Build assets
npm run dev  # For development
npm run build  # For production

# 7. Start development server
php artisan serve
```

### Essential Documents to Read BEFORE Starting Any Work

**MANDATORY READING (กฎบังคับ):**

**🆕 V3 Guidelines (อ่านก่อน!):**

1. **[.claude/V3_CODING_GUIDELINES.md](.claude/V3_CODING_GUIDELINES.md)** ⭐ - แนวทางการเขียนโค้ด V3
2. **[.claude/V3_UI_DESIGN_SYSTEM.md](.claude/V3_UI_DESIGN_SYSTEM.md)** ⭐ - UI/UX standards V3
3. **[.claude/V3_ALPINE_BEST_PRACTICES.md](.claude/V3_ALPINE_BEST_PRACTICES.md)** ⭐ - Alpine.js patterns

**Core Guidelines (ยังคงใช้):**

4. **[CLAUDE_CONTEXT.md](CLAUDE_CONTEXT.md)** - Ecosystem context and license system
5. **[.claude/THAI_LANGUAGE_RULES.md](.claude/THAI_LANGUAGE_RULES.md)** - 🇹🇭 Thai language requirements (100% compliance)
6. **[.claude/instructions.md](.claude/instructions.md)** - Core development guidelines
7. **[.claude/DATABASE_GUIDELINES.md](.claude/DATABASE_GUIDELINES.md)** - Database and migration rules
8. **[.claude/seeder-guidelines.md](.claude/seeder-guidelines.md)** - Seeder synchronization rules
9. **[.claude/ROUTES_GUIDELINES.md](.claude/ROUTES_GUIDELINES.md)** - Route and view conventions

**⚠️ WARNING**: Not reading these guidelines will result in:
- Code not meeting V3 standards ⚠️ **NEW**
- Using deprecated technologies (jQuery, Bootstrap) ⚠️ **NEW**
- Deployment failures
- Database integrity issues
- UI/UX inconsistencies
- Thai language violations

---

## Repository Overview

### What is Thaiprompt-Affiliate?

**TP-Affiliate** is a comprehensive, enterprise-level affiliate marketing platform built with Laravel 11. It's a commercial product designed for:

- MLM (Multi-Level Marketing) systems
- Affiliate marketing networks
- E-commerce marketplaces
- AI bot marketplaces
- Blockchain/crypto integration (TPIX token system)
- Supply chain management (Food Passport)
- Hotel booking systems
- Software sales platforms
- And 20+ other integrated systems

### Repository Type

**Development Repository**: `xjanova/Thaiprompt-Affiliate`

- **Related Repositories:**
  - `xjanova/TP-Affiliate` - Distribution repository (deployment target)
  - `xjanova/TpLicense` - WordPress license management plugin

### Current Branch Strategy

**Branch**: `claude/claude-md-mhya6ld6lgdhbp54-01NCebcUhTRN2TPBK6HVnFMb`

**Important Git Rules:**
- ALL development happens on feature branches starting with `claude/`
- Branch names MUST match session ID for push authentication
- Always use: `git push -u origin <branch-name>`
- Network errors: Auto-retry up to 4 times with exponential backoff (2s, 4s, 8s, 16s)

### Key Statistics

- **Models**: 339+ Eloquent models
- **Controllers**: 41+ controllers across different modules
- **Migrations**: 100+ database migrations
- **Routes**:
  - Web: 23KB route definitions
  - API: 46KB REST endpoints
  - Admin: 129KB admin panel routes
- **Views**: 100+ Blade templates
- **JS Files**: 20+ specialized JavaScript modules
- **Documentation**: 100+ markdown documentation files

---

## Technology Stack

### Backend Technologies

```json
{
  "framework": "Laravel 11.x",
  "php": "8.1+",
  "database": "MySQL 8.0+ / MariaDB 10.3+",
  "authentication": "Laravel Sanctum (API tokens)",
  "queue": "Redis / Database",
  "cache": "Redis / File / Database"
}
```

**Key Laravel Packages:**
- `google/cloud-translate` - Multi-language support
- `google/cloud-vision` - OCR and image recognition
- `intervention/image` - Image processing
- `web3p/web3.php` - Blockchain integration
- `jenssegers/agent` - User agent detection
- `guzzlehttp/guzzle` - HTTP client

### Frontend Technologies (V3 Stack)

```json
{
  "build_tool": "Vite 5.0",
  "css_framework": "Tailwind CSS 3.4",
  "js_framework": "Alpine.js 3.13.5",
  "drag_drop": "SortableJS 1.15+",
  "charts": "Chart.js 4.4.1",
  "3d": "Three.js 0.181.1",
  "visualization": "D3.js 7.9.0, vis-network 10.0.2",
  "animation": "GSAP 3.12.5",
  "blockchain": "ethers 5.8.0, viem 1.21.4"
}
```

**🆕 V3 Primary Stack**:
- ✅ **Tailwind CSS** - Utility-first CSS framework
- ✅ **Alpine.js** - Lightweight reactive framework (~15KB)
- ✅ **SortableJS** - Drag & drop library
- ✅ **Vite** - Fast build tool & HMR

**📊 Specialized Libraries** (ยังคงใช้):
- Chart.js, Three.js, D3.js - Data visualization
- GSAP - Advanced animations
- ethers, viem - Blockchain integration

### Development Tools

- **Linting**: Laravel Pint (PHP CodeStyle)
- **Testing**: PHPUnit 11
- **Version Control**: Git with automated hooks
- **CI/CD**: GitHub Actions
- **Mocking**: Faker, Mockery

---

## Codebase Structure

### Root Directory Layout

```
/home/user/Thaiprompt-Affiliate/
├── app/                          # Laravel application code
│   ├── Console/                  # 40+ Artisan commands
│   ├── Events/                   # Event dispatching
│   ├── Exceptions/               # Error handlers
│   ├── Helpers/                  # Helper functions
│   ├── Http/                     # Controllers, Middleware, Requests
│   ├── Jobs/                     # Queue jobs
│   ├── Listeners/                # Event listeners
│   ├── Mail/                     # Email notifications
│   ├── Models/                   # 339+ Eloquent models
│   ├── Notifications/            # Push/Email notifications
│   ├── Observers/                # Model observers
│   ├── Policies/                 # Authorization
│   └── Providers/                # Service providers
├── bootstrap/                    # Framework bootstrap
├── config/                       # 30+ configuration files
├── database/
│   ├── migrations/               # 100+ migrations
│   ├── seeders/                  # Database seeders
│   └── sql/                      # Custom SQL scripts
├── deployment/                   # Deployment configs
├── lang/                         # Multi-language files
│   ├── en/                       # English translations
│   └── th/                       # Thai translations
├── mobile-app-samples/           # .NET MAUI mobile app
├── public/                       # Public assets
│   ├── build/                    # Compiled Vite assets
│   ├── images/                   # Image files
│   └── icons/                    # Icon files
├── resources/
│   ├── css/                      # Stylesheets
│   ├── js/                       # JavaScript modules
│   └── views/                    # Blade templates
├── routes/                       # Route definitions
│   ├── admin.php                 # Admin routes (129KB)
│   ├── api.php                   # API routes (46KB)
│   ├── web.php                   # Web routes (23KB)
│   ├── user.php                  # User dashboard routes
│   ├── seller.php                # Seller routes
│   └── [10+ other route files]
├── scripts/                      # Utility scripts
│   └── git-hooks/                # Git hooks
├── storage/                      # File storage
│   ├── app/                      # Application files
│   └── logs/                     # Log files
├── tests/                        # PHPUnit tests
├── tpix-blockchain/              # Blockchain smart contracts
├── .claude/                      # Claude AI guidelines
├── .github/                      # GitHub Actions workflows
├── [100+ documentation .md files]
└── [deployment scripts]
```

### App Directory Deep Dive

```
app/
├── Console/Commands/
│   ├── License management commands
│   ├── Version management commands
│   ├── Update/deployment commands
│   ├── Crypto/TPIX commands
│   └── Data management commands
├── Http/Controllers/
│   ├── Admin/                    # 40+ admin controllers
│   │   ├── AffiliateController
│   │   ├── CommissionController
│   │   ├── DashboardController
│   │   ├── ECommerceController
│   │   ├── RankController
│   │   ├── WalletController
│   │   └── [35+ more]
│   ├── Api/V1/                   # REST API controllers
│   ├── Auth/                     # Authentication
│   │   ├── LoginController
│   │   ├── RegisterController
│   │   ├── LineLoginController
│   │   └── SetupController
│   ├── Frontend/                 # Public pages
│   ├── Seller/                   # E-commerce seller
│   └── User/                     # User dashboard
├── Models/                       # 339+ models
│   ├── User, Affiliate, Commission
│   ├── Rank, RankRequirement, RankBonus
│   ├── Wallet, WalletTransaction
│   ├── Product, Order, OrderItem
│   ├── AiBotProfile, AiInstallation
│   ├── LineBotAiSetting
│   └── [330+ more models]
└── Services/                     # 30+ business logic services
    ├── WalletService (18,468 lines)
    ├── RankingService (10,028 lines)
    ├── EmailService (11,285 lines)
    ├── NotificationService (14,104 lines)
    └── [26+ more services]
```

### Routes Structure

**Route Files by Module:**

```
routes/
├── web.php              # Public web routes
├── admin.php            # Admin dashboard (requires auth + admin role)
├── api.php              # REST API v1 (Sanctum auth)
├── user.php             # User dashboard routes
├── seller.php           # E-commerce seller routes
├── hotel-admin.php      # Hotel management system
├── software_sales.php   # Software sales routes
├── bot_automation.php   # Bot automation routes
├── pos.php              # Point of sale system
└── console.php          # Artisan commands
```

**Route Prefixes:**
- Admin: `/admin/*`
- API: `/api/v1/*`
- User: `/user/*`
- Seller: `/seller/*`

### Views Structure

```
resources/views/
├── admin/                       # Admin dashboard UI
│   ├── dashboard.blade.php
│   ├── affiliates/
│   ├── ecommerce/
│   ├── ai-bots/
│   ├── commissions/
│   ├── wallet/
│   └── [40+ admin modules]
├── auth/                        # Login/Register pages
├── frontend/                    # Public-facing pages
│   ├── pages/                   # CMS pages
│   ├── home.blade.php
│   └── marketplace/
├── user/                        # User dashboard
│   ├── dashboard/
│   ├── profile/
│   ├── wallet/
│   ├── commissions/
│   └── affiliates/
├── seller/                      # Seller dashboard
│   ├── products/
│   ├── orders/
│   └── analytics/
├── layouts/                     # Master templates
│   ├── app.blade.php           # Main layout
│   ├── admin.blade.php         # Admin layout (77KB)
│   ├── seller.blade.php
│   └── user.blade.php
├── components/                  # Reusable components
└── emails/                      # Email templates
```

---

## Development Workflows

### Standard Development Workflow

```bash
# 1. Start new feature
git checkout -b claude/feature-name-<session-id>

# 2. Make changes following guidelines
# - Read relevant .claude/ guidelines first!
# - Use Thai language 100% in comments
# - Check table/column existence in migrations
# - Add seeders to DatabaseSeeder.php
# - Implement dark/light mode in UI

# 3. Test locally
php artisan test
npm run build

# 4. Commit (Git hooks will run automatically)
git add .
git commit -m "feat: description in English"
# Pre-commit hook verifies seeders automatically

# 5. Push to remote
git push -u origin claude/feature-name-<session-id>

# 6. Create pull request (if needed)
# Use GitHub web interface
```

### Creating a New Feature (Complete Checklist)

**Before Starting:**
- [ ] Read CLAUDE_CONTEXT.md for ecosystem understanding
- [ ] Read relevant .claude/ guideline files
- [ ] Confirm Thai language requirements (100% in comments/docs)
- [ ] Understand dark/light mode requirements
- [ ] Check existing similar features for patterns

**During Development:**
- [ ] Create migration with `Schema::hasTable()` check
- [ ] Create model with proper relationships
- [ ] Create seeder and add to DatabaseSeeder.php
- [ ] Create controller with proper validation
- [ ] Create routes with middleware
- [ ] Create views with dark/light mode support
- [ ] Add Thai language comments to all code
- [ ] Implement responsive design (mobile-first)
- [ ] Add error handling

**After Development:**
- [ ] Run `php artisan test`
- [ ] Run `npm run build`
- [ ] Test dark/light mode manually
- [ ] Test on mobile/tablet/desktop
- [ ] Commit with descriptive message
- [ ] Git hook passes automatically
- [ ] Push to feature branch

### Database Development Workflow

**Creating Migrations:**

```bash
# Generate migration
php artisan make:migration create_table_name_table

# Edit migration file
```

**Migration Template (ALWAYS use this pattern):**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง table_name
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('table_name')) {
            return;
        }

        Schema::create('table_name', function (Blueprint $table) {
            $table->id();

            // Foreign keys with constraints
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Regular columns
            $table->string('name');
            $table->text('description')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->unique(['user_id', 'name'], 'user_name_unique');
        });
    }

    /**
     * ลบตาราง table_name
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

**Creating Seeders:**

```bash
# Generate seeder
php artisan make:seeder FeatureSeeder

# Edit seeder file AND DatabaseSeeder.php (CRITICAL!)
```

**Seeder Template (ALWAYS use this pattern):**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับ Feature
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Feature...');

        // ✅ CRITICAL: ตรวจสอบก่อนสร้าง (idempotent)
        if (Model::where('key', 'value')->exists()) {
            $this->command->info('ข้อมูลมีอยู่แล้ว ข้าม...');
            return;
        }

        // Seeding logic here
        Model::create([
            'name' => 'ชื่อภาษาไทย',
            'description' => 'คำอธิบายภาษาไทย',
        ]);

        $this->command->info('✅ Seed ข้อมูล Feature สำเร็จ!');
    }
}
```

**CRITICAL: Update DatabaseSeeder.php:**

```php
// database/seeders/DatabaseSeeder.php

public function run(): void
{
    $this->call([
        // ... existing seeders
        FeatureSeeder::class,  // ← เพิ่ม seeder ใหม่ที่นี่!
    ]);
}
```

**Verification:**

```bash
# Verify seeder sync (if script exists)
php scripts/verify-seeders.php

# Run migrations and seeds
php artisan migrate:fresh --seed
```

---

## Critical Guidelines

### 1. Thai Language Requirements (🇹🇭 MANDATORY)

**100% Thai Language Compliance:**

```php
/**
 * ✅ CORRECT: Thai language comments
 *
 * คำนวณคอมมิชชั่นสำหรับ affiliate
 *
 * @param User $user ผู้ใช้ที่จะคำนวณคอมมิชชั่น
 * @param float $amount จำนวนเงินที่ใช้คำนวณ
 * @return float จำนวนคอมมิชชั่นที่คำนวณได้
 *
 * @example
 * $commission = $this->calculateCommission($user, 1000);
 * // ผลลัพธ์: 100 (10% ของ 1000)
 *
 * @tip ใช้ rank multiplier เพื่อเพิ่มคอมมิชชั่น
 */
public function calculateCommission(User $user, float $amount): float
{
    // คำนวณอัตราคอมมิชชั่นตาม rank
    $rate = $user->rank->commission_rate ?? 0.1;

    // คำนวณคอมมิชชั่น
    return $amount * $rate;
}
```

```php
/**
 * ❌ WRONG: English comments
 *
 * Calculate commission for affiliate
 *
 * @param User $user User to calculate commission
 * @return float Commission amount
 */
public function calculateCommission(User $user): float
{
    // Calculate commission rate
    // ...
}
```

**Thai in User-Facing Content:**

```blade
{{-- ✅ CORRECT --}}
<h1>ระบบจัดการผู้ใช้</h1>
<p>ยินดีต้อนรับสู่ระบบ Affiliate</p>

{{-- ❌ WRONG --}}
<h1>User Management</h1>
<p>Welcome to Affiliate System</p>
```

**Reference**: [.claude/THAI_LANGUAGE_RULES.md](.claude/THAI_LANGUAGE_RULES.md)

### 2. Database Integrity (CRITICAL)

**Migration Rules:**

```php
// ✅ ALWAYS check table existence
if (Schema::hasTable('table_name')) {
    return;
}

// ✅ ALWAYS check column existence before adding
if (!Schema::hasColumn('table_name', 'column_name')) {
    $table->string('column_name')->nullable();
}

// ✅ ALWAYS use constrained() for foreign keys
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// ✅ ALWAYS use short index names (max 50 chars)
$table->unique(['col1', 'col2', 'col3'], 'short_unique_idx');
```

**Reference**: [.claude/DATABASE_GUIDELINES.md](.claude/DATABASE_GUIDELINES.md)

### 3. Seeder Synchronization (CRITICAL)

**RULE #1**: Every seeder MUST be in DatabaseSeeder.php

```php
// Create seeder
php artisan make:seeder NewFeatureSeeder

// ✅ IMMEDIATELY add to DatabaseSeeder.php
$this->call([
    // ... existing
    NewFeatureSeeder::class,  // ← ADD HERE
]);

// ✅ Commit BOTH files together
git add database/seeders/NewFeatureSeeder.php
git add database/seeders/DatabaseSeeder.php
git commit -m "feat: add new feature seeder"
```

**Git Hook**: Pre-commit hook will automatically verify and block commit if seeder is missing.

**Reference**: [.claude/seeder-guidelines.md](.claude/seeder-guidelines.md)

### 4. UI/UX Standards (MANDATORY)

**Dark/Light Mode (Required):**

```blade
{{-- ✅ CORRECT: Use Tailwind dark: utilities --}}
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
    <h1 class="text-2xl font-bold">Title</h1>
</div>

{{-- ❌ WRONG: Hard-coded colors --}}
<div style="background: white; color: black;">
    <h1>Title</h1>
</div>
```

**Responsive Design (Required):**

```blade
{{-- ✅ CORRECT: Mobile-first responsive --}}
<div class="
    w-full                    <!-- Mobile: full width -->
    md:w-1/2                  <!-- Tablet: half width -->
    lg:w-1/3                  <!-- Desktop: third width -->
    p-4                       <!-- Consistent padding -->
">
    <button class="
        w-full                <!-- Mobile: full width button -->
        md:w-auto             <!-- Desktop: auto width -->
        px-6 py-3             <!-- Touch-friendly size (≥44px) -->
    ">
        คลิก
    </button>
</div>
```

**Reference**: [.claude/UI_DESIGN_SYSTEM.md](.claude/UI_DESIGN_SYSTEM.md)

### 5. Route Conventions

**Route Naming:**

```php
// ✅ CORRECT: Proper naming and middleware
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->name('users.show');
    });

// ❌ WRONG: No middleware, duplicate names
Route::get('/admin/users', [UserController::class, 'index']);
Route::get('/users', [UserController::class, 'index']); // Duplicate!
```

**Reference**: [.claude/ROUTES_GUIDELINES.md](.claude/ROUTES_GUIDELINES.md)

### 6. Git Hooks (AUTOMATED VERIFICATION)

**Setup (CRITICAL - Do this first!):**

```bash
# Install git hooks
bash scripts/git-hooks/install.sh

# Verify installation
test -x .git/hooks/pre-commit && echo "✓ Installed" || echo "✗ Not installed"
```

**What Git Hooks Check:**
- ✅ Seeder synchronization with DatabaseSeeder.php
- ✅ (Future) Code style compliance
- ✅ (Future) Test execution

**When Hook Blocks Commit:**

```
❌ COMMIT BLOCKED
⚠  CRITICAL RULE #1 VIOLATION

You MUST add missing seeders to DatabaseSeeder.php before committing.

Missing seeder(s):
  • NewFeatureSeeder

🔧 To fix:
   1. Open database/seeders/DatabaseSeeder.php
   2. Add NewFeatureSeeder::class to the call() array
   3. Try committing again
```

---

## Common Development Tasks

### Task 1: Create New Controller

```bash
# Generate controller
php artisan make:controller Admin/FeatureController --resource

# Edit controller with Thai comments
```

**Controller Template:**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * จัดการ Feature ในระบบ Admin
 */
class FeatureController extends Controller
{
    /**
     * แสดงรายการ Feature ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงข้อมูล features พร้อม pagination
        $features = Feature::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.features.index', [
            'features' => $features,
            'pageTitle' => 'จัดการ Feature',
        ]);
    }

    /**
     * แสดงฟอร์มสร้าง Feature ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.features.create', [
            'pageTitle' => 'สร้าง Feature ใหม่',
        ]);
    }

    /**
     * บันทึก Feature ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // สร้าง feature
        $feature = Feature::create($validated);

        return redirect()
            ->route('admin.features.index')
            ->with('success', 'สร้าง Feature สำเร็จ');
    }
}
```

### Task 2: Create New Model with Relationships

```bash
# Generate model with migration
php artisan make:model Feature -m
```

**Model Template:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Feature Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $name ชื่อ Feature
 * @property string|null $description คำอธิบาย
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Feature extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'features';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Task 3: Create New Blade View with Dark Mode

```blade
{{-- resources/views/admin/features/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'จัดการ Feature')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header with dark mode support --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            จัดการ Feature
        </h1>
        <a href="{{ route('admin.features.create') }}"
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition">
            + สร้าง Feature ใหม่
        </a>
    </div>

    {{-- Table with responsive design --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ชื่อ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            คำอธิบาย
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($features as $feature)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ $feature->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ Str::limit($feature->description, 50) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.features.edit', $feature) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:underline">
                                    แก้ไข
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล Feature
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $features->links() }}
    </div>
</div>
@endsection
```

### Task 4: Create API Endpoint

```php
// routes/api.php

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Feature endpoints
    Route::get('/features', [Api\V1\FeatureController::class, 'index']);
    Route::get('/features/{feature}', [Api\V1\FeatureController::class, 'show']);
});
```

**API Controller:**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;

/**
 * Feature API Controller
 */
class FeatureController extends Controller
{
    /**
     * แสดงรายการ Features
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $features = Feature::with('user')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $features,
            'message' => 'ดึงข้อมูล features สำเร็จ',
        ]);
    }

    /**
     * แสดงรายละเอียด Feature
     *
     * @param Feature $feature
     * @return JsonResponse
     */
    public function show(Feature $feature): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $feature->load('user'),
            'message' => 'ดึงข้อมูล feature สำเร็จ',
        ]);
    }
}
```

### Task 5: Add New Service

```bash
# Create service in app/Services/
touch app/Services/FeatureService.php
```

**Service Template:**

```php
<?php

namespace App\Services;

use App\Models\Feature;
use Illuminate\Support\Facades\DB;

/**
 * Feature Service
 *
 * จัดการ business logic สำหรับ Feature
 */
class FeatureService
{
    /**
     * สร้าง feature ใหม่
     *
     * @param array $data ข้อมูล feature
     * @return Feature
     *
     * @throws \Exception
     */
    public function create(array $data): Feature
    {
        return DB::transaction(function () use ($data) {
            // สร้าง feature
            $feature = Feature::create($data);

            // Log การสร้าง
            activity()
                ->performedOn($feature)
                ->log('สร้าง feature ใหม่');

            return $feature;
        });
    }

    /**
     * อัพเดท feature
     *
     * @param Feature $feature
     * @param array $data
     * @return Feature
     */
    public function update(Feature $feature, array $data): Feature
    {
        return DB::transaction(function () use ($feature, $data) {
            // อัพเดทข้อมูล
            $feature->update($data);

            // Log การแก้ไข
            activity()
                ->performedOn($feature)
                ->log('อัพเดท feature');

            return $feature->fresh();
        });
    }
}
```

---

## Testing & Quality Assurance

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/FeatureTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Writing Tests

**Feature Test Template:**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ทดสอบการแสดงรายการ features
     *
     * @return void
     */
    public function test_can_list_features(): void
    {
        // Arrange: สร้างข้อมูลทดสอบ
        $user = User::factory()->create();
        $features = Feature::factory()->count(5)->create();

        // Act: เรียก API
        $response = $this->actingAs($user)
            ->getJson('/api/v1/features');

        // Assert: ตรวจสอบผลลัพธ์
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'description'],
                    ],
                ],
                'message',
            ]);
    }
}
```

### Code Quality Tools

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Format specific file
./vendor/bin/pint app/Http/Controllers/FeatureController.php

# Check without fixing
./vendor/bin/pint --test
```

---

## Deployment & Version Management

### Deployment Scripts

**Available Scripts:**

```bash
# Main deployment script (recommended)
./deploy.sh                    # Deploy from current branch
./deploy.sh main              # Deploy from main branch

# Other deployment scripts
./install.sh                  # Initial installation
./deploy-fix.sh              # Quick fixes
./run-migrations.sh          # Run migrations only
./rollback.sh                # Rollback deployment
```

### deploy.sh Features

```bash
# Features:
# ✅ Maintenance mode during deployment
# ✅ Automatic database backup
# ✅ Composer dependency updates
# ✅ NPM asset building
# ✅ Database migrations
# ✅ Cache optimization
# ✅ Auto-retry on timeout (max 3 attempts)
# ✅ Rollback commands if deployment fails
# ✅ Logging to storage/logs/deployment.log

# Usage:
./deploy.sh [branch]

# Example:
./deploy.sh main
```

### Version Management

```bash
# Bump version
php artisan app:bump-version [major|minor|patch]

# Examples:
php artisan app:bump-version patch    # 2.203.0 → 2.203.1
php artisan app:bump-version minor    # 2.203.0 → 2.204.0
php artisan app:bump-version major    # 2.203.0 → 3.0.0

# Check current version
php artisan app:version

# Check for updates
php artisan app:check-update
```

### Release Process

```bash
# 1. Bump version
php artisan app:bump-version patch

# 2. Update CHANGELOG.md
# Add new version section with changes

# 3. Commit version changes
git add VERSION CHANGELOG.md package.json composer.json
git commit -m "chore: bump version to X.X.X"

# 4. Deploy (if using distribution repo)
./scripts/deploy-to-distribution.sh

# 5. Push and create tag
git push origin main
git tag -a vX.X.X -m "Release vX.X.X"
git push origin vX.X.X
```

### Environment Configuration

**Important .env Variables:**

```bash
# Application
APP_NAME="TP-Affiliate"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=

# License System (if applicable)
LICENSE_KEY=
LICENSE_DEVELOPER_MODE=false
LICENSE_ALLOW_OFFLINE=false

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=

# Google Cloud
GOOGLE_CLOUD_TRANSLATE_API_KEY=

# LINE Integration
LINE_CHANNEL_ID=
LINE_CHANNEL_SECRET=
LINE_ACCESS_TOKEN=

# Blockchain (TPIX)
TPIX_RPC_URL=
TPIX_CHAIN_ID=
```

---

## Troubleshooting

### Common Issues and Solutions

#### Issue 1: "Table already exists" Error

**Symptom:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'xxx' already exists
```

**Solution:**
Add table existence check to migration:

```php
if (Schema::hasTable('table_name')) {
    return;
}
```

#### Issue 2: Seeder Not in DatabaseSeeder.php

**Symptom:**
```
❌ COMMIT BLOCKED
Missing seeder(s):
  • NewFeatureSeeder
```

**Solution:**
```php
// Add to database/seeders/DatabaseSeeder.php
$this->call([
    // ... existing seeders
    NewFeatureSeeder::class,  // ← Add here
]);
```

#### Issue 3: Permission Denied Errors

**Symptom:**
```
Permission denied: storage/logs/laravel.log
```

**Solution:**
```bash
# Fix permissions
./fix-permissions.sh

# Or manually:
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Issue 4: Vite Build Failures

**Symptom:**
```
Error: Cannot find module 'vite'
```

**Solution:**
```bash
# Reinstall node modules
rm -rf node_modules package-lock.json
npm install

# Try building again
npm run build
```

#### Issue 5: Foreign Key Constraint Errors

**Symptom:**
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solution:**
1. Check migration order (parent tables first)
2. Ensure referenced table/column exists
3. Use `constrained()` for foreign keys:

```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
```

#### Issue 6: Git Hook Not Running

**Symptom:**
Git hook doesn't run on commit

**Solution:**
```bash
# Reinstall hooks
bash scripts/git-hooks/install.sh

# Check permissions
chmod +x .git/hooks/pre-commit

# Verify
test -x .git/hooks/pre-commit && echo "✓ Installed" || echo "✗ Not installed"
```

---

## Reference Documentation

### Core Documentation Files

**Essential Reading:**
- [CLAUDE_CONTEXT.md](CLAUDE_CONTEXT.md) - Ecosystem context and license system
- [README.md](README.md) - Project overview (Thai)
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture
- [CODEBASE_ARCHITECTURE.md](CODEBASE_ARCHITECTURE.md) - Code organization
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development guide

**Guidelines (.claude/ directory):**
- [.claude/instructions.md](.claude/instructions.md) - Core development guidelines
- [.claude/THAI_LANGUAGE_RULES.md](.claude/THAI_LANGUAGE_RULES.md) - Thai language requirements
- [.claude/DATABASE_GUIDELINES.md](.claude/DATABASE_GUIDELINES.md) - Database best practices
- [.claude/seeder-guidelines.md](.claude/seeder-guidelines.md) - Seeder synchronization
- [.claude/ROUTES_GUIDELINES.md](.claude/ROUTES_GUIDELINES.md) - Route conventions
- [.claude/UI_DESIGN_SYSTEM.md](.claude/UI_DESIGN_SYSTEM.md) - UI/UX standards
- [.claude/MENU_RULES.md](.claude/MENU_RULES.md) - Menu system rules
- [.claude/DEPLOYMENT_GUIDELINES.md](.claude/DEPLOYMENT_GUIDELINES.md) - Deployment practices

**Feature Documentation:**
- [LINE_MEMBERSHIP_SIGNUP_README.md](LINE_MEMBERSHIP_SIGNUP_README.md) - LINE AI signup
- [TPIX_TOKEN_SYSTEM.md](TPIX_TOKEN_SYSTEM.md) - Blockchain integration
- [MLM_SYSTEM_DOCUMENTATION.md](MLM_SYSTEM_DOCUMENTATION.md) - MLM features
- [API_ARCHITECTURE_GUIDE.md](API_ARCHITECTURE_GUIDE.md) - API design patterns

### Quick Commands Reference

```bash
# Development
php artisan serve                 # Start dev server
npm run dev                      # Watch and compile assets
php artisan migrate:fresh --seed # Reset database

# Testing
php artisan test                 # Run tests
./vendor/bin/pint               # Format code

# Deployment
./deploy.sh                      # Deploy updates
./rollback.sh                   # Rollback if needed

# Version
php artisan app:version          # Show current version
php artisan app:bump-version patch  # Bump version

# Cache
php artisan optimize             # Optimize application
php artisan optimize:clear       # Clear all caches
./clear-cache.sh                # Clear cache script

# Database
php artisan migrate              # Run migrations
php artisan db:seed              # Run seeders
php artisan migrate:rollback     # Rollback last migration

# License (if applicable)
php artisan license:check        # Check license status
php artisan license:activate KEY # Activate license
```

### Important Files Quick Reference

```bash
# Configuration
.env                            # Environment configuration
config/app.php                  # Application settings
config/database.php             # Database configuration

# Routes
routes/web.php                  # Web routes (23KB)
routes/admin.php                # Admin routes (129KB)
routes/api.php                  # API routes (46KB)

# Core Services
app/Services/WalletService.php  # Wallet operations
app/Services/RankingService.php # Ranking system
app/Services/EmailService.php   # Email sending

# Deployment
deploy.sh                       # Main deployment
.github/workflows/release.yml   # CI/CD pipeline

# Documentation
CLAUDE_CONTEXT.md              # Ecosystem context
.claude/instructions.md        # Development guidelines
```

---

## Final Checklist for AI Assistants

**Before Starting ANY Task:**
- [ ] Read CLAUDE_CONTEXT.md for ecosystem understanding
- [ ] Read relevant .claude/ guidelines for the task type
- [ ] Understand Thai language requirements (100% compliance)
- [ ] Check if Git hooks are installed
- [ ] Verify current branch matches session ID

**During Development:**
- [ ] Write ALL comments in Thai language
- [ ] Add `Schema::hasTable()` check to migrations
- [ ] Add seeders to DatabaseSeeder.php
- [ ] Implement dark/light mode in UI
- [ ] Make design responsive (mobile-first)
- [ ] Add proper error handling
- [ ] Use proper naming conventions

**Before Committing:**
- [ ] Run `php artisan test`
- [ ] Run `./vendor/bin/pint`
- [ ] Run `npm run build`
- [ ] Test dark/light mode manually
- [ ] Test on mobile/tablet/desktop
- [ ] Verify seeder synchronization (Git hook will check)
- [ ] Write descriptive commit message

**After Committing:**
- [ ] Git hook passes successfully
- [ ] Push to feature branch (with session ID)
- [ ] Create PR if needed
- [ ] Update documentation if necessary

---

## Appendix: Key Patterns and Conventions

### Naming Conventions

**PHP:**
- Classes: `PascalCase` (e.g., `UserController`)
- Methods: `camelCase` (e.g., `calculateCommission`)
- Variables: `camelCase` (e.g., `$userId`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `MAX_ATTEMPTS`)

**Database:**
- Tables: `snake_case`, plural (e.g., `user_features`)
- Columns: `snake_case` (e.g., `user_id`, `created_at`)
- Foreign keys: `{table}_id` (e.g., `user_id`)
- Indexes: Short names (e.g., `user_email_idx`)

**Routes:**
- Prefix: `kebab-case` (e.g., `/admin/user-features`)
- Names: `dot.notation` (e.g., `admin.users.index`)

**Views:**
- Directories: `kebab-case` (e.g., `user-features/`)
- Files: `kebab-case.blade.php` (e.g., `index.blade.php`)

### Code Organization Patterns

**Controller Pattern:**
```
1. Validate input
2. Call service method
3. Return response/redirect with message
```

**Service Pattern:**
```
1. Wrap in DB::transaction
2. Perform business logic
3. Log activity
4. Return result
```

**Model Pattern:**
```
1. Define fillable/guarded
2. Define casts
3. Define relationships
4. Define scopes
5. Define accessors/mutators
```

---

**Document Version**: 1.0
**Last Updated**: 2025-11-14
**Maintained By**: Development Team
**For**: Claude AI Assistants and Human Developers

**Remember**: This is a commercial product used by real businesses. Every change affects real users. Test thoroughly, document clearly, and maintain backwards compatibility whenever possible.

---

*"Excellence is not an act, but a habit" - Make every code contribution something to be proud of.*
