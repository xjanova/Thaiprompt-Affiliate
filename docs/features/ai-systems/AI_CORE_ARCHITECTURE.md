# 🤖 AI Core System - Architecture & Implementation Plan

> **ระบบควบคุม AI แกนหลักของ Thaiprompt-Affiliate**
>
> Version: 1.0.0 | Created: 2025-11-18 | Status: Planning & Implementation

---

## 🎯 วิสัยทัศน์และเป้าหมาย

### ภาพรวม AI Core

**AI Core** เป็นระบบควบคุม AI แบบรวมศูนย์ที่เป็นหัวใจหลักในการจัดการ AI features ทั้งหมดของระบบ Thaiprompt-Affiliate

### เป้าหมายหลัก

1. ✅ **ควบคุมแบบรวมศูนย์**
   - จัดการ AI features ทั้งหมดในที่เดียว
   - ตัดการเชื่อมต่อเดิม ให้ใช้ผ่าน AI Core เท่านั้น
   - มี Master Control สำหรับเปิด/ปิดระบบ

2. ✅ **ระบบ Quota Management**
   - จำกัดการใช้งานต่อเดือน/วัน/ชั่วโมง
   - รองรับการให้เช่าระบบ (SaaS model)
   - Track usage แบบเรียลไทม์

3. ✅ **ระบบ Scheduling**
   - กำหนดเวลาเปิด/ปิดอัตโนมัติ
   - กำหนดเวลาทำงานของแต่ละ AI feature
   - รองรับ timezone

4. ✅ **ระบบ Monitoring & Analytics**
   - ติดตามการใช้งานแบบเรียลไทม์
   - แจ้งเตือนเมื่อใกล้หมด quota
   - รายงานการใช้งาน

5. ✅ **Multi-tenancy Support**
   - แยก quota ตาม tenant/organization
   - รองรับการให้เช่าหลายราย
   - Billing integration

---

## 📊 AI Features ที่จะรวมเข้า AI Core

### ปัจจุบันมี AI Features อะไรบ้าง?

**ผมต้อง scan codebase ก่อนเพื่อหา AI features ทั้งหมด:**

1. **LINE Bot AI**
   - LINE Membership Signup (AI-powered)
   - LINE Chatbot
   - LINE AI Assistant

2. **AI Bot Marketplace**
   - AI Bot Profiles
   - AI Installations
   - AI Bot Settings

3. **Automation**
   - LINE Signup Flow Automation (ที่เพิ่งวางแผน)
   - Workflow Automation

4. **อื่นๆ** (ต้อง scan เพิ่ม)

---

## 🏗️ Architecture Design

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      AI CORE                                │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Master Control Panel                     │  │
│  │  - Enable/Disable AI Features                         │  │
│  │  - Global Quota Management                            │  │
│  │  - Scheduling & Automation                            │  │
│  │  - Monitoring & Analytics                             │  │
│  │  - Tenant/Organization Management                     │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↓                                 │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              AI Feature Registry                      │  │
│  │  - Register all AI features                           │  │
│  │  - Feature metadata (quota, pricing, etc.)            │  │
│  │  - Feature dependencies                               │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↓                                 │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Quota & Usage Manager                    │  │
│  │  - Track usage per feature                            │  │
│  │  - Enforce quota limits                               │  │
│  │  - Usage alerts & notifications                       │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↓                                 │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              Scheduler & Automation                   │  │
│  │  - Schedule feature activation/deactivation           │  │
│  │  - Automated workflows                                │  │
│  │  - Cron jobs management                               │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                   AI Features                               │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐              │
│  │ LINE Bot   │ │ Chatbot    │ │ Automation │              │
│  │ Signup     │ │ AI         │ │ Workflows  │              │
│  └────────────┘ └────────────┘ └────────────┘              │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐              │
│  │ AI Bot     │ │ ML Models  │ │ Analytics  │              │
│  │ Marketplace│ │            │ │ AI         │              │
│  └────────────┘ └────────────┘ └────────────┘              │
└─────────────────────────────────────────────────────────────┘
```

### Component Breakdown

#### 1. **Master Control Panel**
- Dashboard แสดงภาพรวม AI features
- เปิด/ปิด features แบบเรียลไทม์
- กำหนด global settings

#### 2. **AI Feature Registry**
- ลงทะเบียน AI features ทั้งหมด
- Metadata: ชื่อ, คำอธิบาย, icon, category
- ข้อกำหนด: quota, pricing tier, dependencies

#### 3. **Quota & Usage Manager**
- Track การใช้งานแบบเรียลไทม์
- Enforce limits (hard limit, soft limit)
- Alert เมื่อใกล้หมด quota

#### 4. **Scheduler & Automation**
- กำหนดเวลาเปิด/ปิดอัตโนมัติ
- Recurring schedules (daily, weekly, monthly)
- One-time schedules

#### 5. **Billing & Subscription**
- รองรับหลาย pricing tiers
- Integration กับ payment gateways
- Invoice generation

---

## 💾 Database Schema

### Tables ที่ต้องสร้าง

#### 1. **`ai_core_features`** - Registry ของ AI features
```sql
CREATE TABLE ai_core_features (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Feature Info
    feature_key VARCHAR(100) UNIQUE NOT NULL,  -- e.g., 'line_bot_signup', 'chatbot_ai'
    feature_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),  -- 'chatbot', 'automation', 'analytics', etc.
    icon VARCHAR(100),     -- Icon class (e.g., 'fas fa-robot')

    -- Status
    is_enabled BOOLEAN DEFAULT true,  -- Global enable/disable
    is_beta BOOLEAN DEFAULT false,
    is_premium BOOLEAN DEFAULT false,

    -- Quota Configuration
    quota_type ENUM('none', 'requests', 'tokens', 'minutes', 'executions') DEFAULT 'requests',
    default_quota_limit INT UNSIGNED,  -- Default quota per month
    quota_unit VARCHAR(20),  -- 'per_month', 'per_day', 'per_hour'

    -- Pricing
    pricing_tier VARCHAR(50),  -- 'free', 'basic', 'pro', 'enterprise'
    price_per_unit DECIMAL(10, 2),  -- ราคาต่อหน่วย (สำหรับ pay-as-you-go)

    -- Dependencies
    depends_on JSON,  -- Array of feature_keys that this feature depends on

    -- Configuration
    config JSON,  -- Feature-specific configuration

    -- Metadata
    version VARCHAR(20),
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_category (category),
    INDEX idx_enabled (is_enabled),
    INDEX idx_premium (is_premium)
);
```

#### 2. **`ai_core_tenants`** - Tenants/Organizations (สำหรับ multi-tenancy)
```sql
CREATE TABLE ai_core_tenants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Tenant Info
    tenant_key VARCHAR(100) UNIQUE NOT NULL,  -- e.g., 'org_abc123'
    tenant_name VARCHAR(255) NOT NULL,
    tenant_type ENUM('self', 'client', 'partner') DEFAULT 'self',

    -- Contact
    contact_name VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),

    -- Subscription
    subscription_plan VARCHAR(50),  -- 'free', 'basic', 'pro', 'enterprise'
    subscription_status ENUM('active', 'suspended', 'cancelled', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    subscription_starts_at TIMESTAMP NULL,
    subscription_ends_at TIMESTAMP NULL,

    -- Billing
    billing_email VARCHAR(255),
    billing_cycle ENUM('monthly', 'yearly', 'custom') DEFAULT 'monthly',
    next_billing_date DATE,

    -- Limits
    max_users INT UNSIGNED,
    max_api_calls INT UNSIGNED,
    storage_limit_gb INT UNSIGNED,

    -- Status
    is_active BOOLEAN DEFAULT true,

    -- Metadata
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_status (subscription_status),
    INDEX idx_active (is_active)
);
```

#### 3. **`ai_core_feature_access`** - สิทธิ์การใช้งาน features ของแต่ละ tenant
```sql
CREATE TABLE ai_core_feature_access (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,

    -- Access Control
    is_enabled BOOLEAN DEFAULT true,  -- Tenant-level enable/disable

    -- Quota Override (override default from feature)
    quota_limit INT UNSIGNED,  -- NULL = use default from feature
    quota_limit_type VARCHAR(20),  -- 'per_month', 'per_day', 'per_hour', 'unlimited'

    -- Scheduling
    scheduled_enabled_at TIMESTAMP NULL,
    scheduled_disabled_at TIMESTAMP NULL,

    -- Auto-renewal
    auto_renew_quota BOOLEAN DEFAULT true,
    renew_on_day INT,  -- Day of month to renew (1-31)

    -- Metadata
    custom_config JSON,  -- Tenant-specific configuration
    notes TEXT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by BIGINT UNSIGNED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_tenant_feature (tenant_id, feature_id),
    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES ai_core_features(id) ON DELETE CASCADE,

    INDEX idx_enabled (is_enabled),
    INDEX idx_tenant (tenant_id)
);
```

#### 4. **`ai_core_usage_logs`** - บันทึกการใช้งาน
```sql
CREATE TABLE ai_core_usage_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,  -- User who triggered the usage

    -- Usage Details
    usage_type VARCHAR(50),  -- 'api_call', 'token', 'execution', 'minute'
    usage_amount INT UNSIGNED DEFAULT 1,

    -- Context
    request_id VARCHAR(100),  -- Unique request ID for tracing
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Metadata
    metadata JSON,  -- Additional context (e.g., model used, input/output sizes)

    -- Performance
    response_time_ms INT UNSIGNED,
    status VARCHAR(20),  -- 'success', 'failed', 'throttled'
    error_message TEXT,

    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES ai_core_features(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_tenant_feature (tenant_id, feature_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);
```

#### 5. **`ai_core_quotas`** - ติดตาม quota usage
```sql
CREATE TABLE ai_core_quotas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,

    -- Period
    period_type ENUM('hourly', 'daily', 'monthly', 'yearly') NOT NULL,
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,

    -- Quota
    quota_limit INT UNSIGNED NOT NULL,
    quota_used INT UNSIGNED DEFAULT 0,
    quota_remaining INT UNSIGNED,

    -- Status
    is_exceeded BOOLEAN DEFAULT false,
    exceeded_at TIMESTAMP NULL,

    -- Reset
    last_reset_at TIMESTAMP,
    next_reset_at TIMESTAMP,

    -- Alerts
    alert_sent_at_50_percent TIMESTAMP NULL,
    alert_sent_at_80_percent TIMESTAMP NULL,
    alert_sent_at_100_percent TIMESTAMP NULL,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_tenant_feature_period (tenant_id, feature_id, period_type, period_start),
    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES ai_core_features(id) ON DELETE CASCADE,

    INDEX idx_period (period_start, period_end),
    INDEX idx_exceeded (is_exceeded)
);
```

#### 6. **`ai_core_schedules`** - กำหนดเวลาเปิด/ปิด features
```sql
CREATE TABLE ai_core_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NOT NULL,

    -- Schedule Type
    schedule_type ENUM('one_time', 'recurring') NOT NULL,
    action ENUM('enable', 'disable', 'reset_quota') NOT NULL,

    -- One-time Schedule
    execute_at TIMESTAMP NULL,

    -- Recurring Schedule
    recurrence_pattern VARCHAR(50),  -- 'daily', 'weekly', 'monthly', 'custom_cron'
    recurrence_cron VARCHAR(100),  -- Cron expression for custom patterns
    recurrence_days JSON,  -- Array of days (for weekly: [1,2,3,4,5])
    recurrence_time TIME,  -- Time to execute (HH:MM:SS)

    -- Timezone
    timezone VARCHAR(50) DEFAULT 'Asia/Bangkok',

    -- Status
    is_active BOOLEAN DEFAULT true,
    last_executed_at TIMESTAMP NULL,
    next_execution_at TIMESTAMP NULL,
    execution_count INT UNSIGNED DEFAULT 0,

    -- Limits
    max_executions INT UNSIGNED NULL,  -- NULL = unlimited
    expires_at TIMESTAMP NULL,

    -- Metadata
    description TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES ai_core_features(id) ON DELETE CASCADE,

    INDEX idx_next_execution (next_execution_at),
    INDEX idx_active (is_active)
);
```

#### 7. **`ai_core_alerts`** - แจ้งเตือน
```sql
CREATE TABLE ai_core_alerts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    feature_id BIGINT UNSIGNED NULL,

    -- Alert Type
    alert_type ENUM('quota_warning', 'quota_exceeded', 'feature_disabled', 'error', 'info') NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',

    -- Content
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,

    -- Notification Channels
    channels JSON,  -- ['email', 'line', 'slack', 'sms']

    -- Status
    is_read BOOLEAN DEFAULT false,
    is_sent BOOLEAN DEFAULT false,
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,

    -- Context
    context JSON,  -- Additional data

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES ai_core_features(id) ON DELETE SET NULL,

    INDEX idx_tenant (tenant_id),
    INDEX idx_read (is_read),
    INDEX idx_type (alert_type)
);
```

#### 8. **`ai_core_settings`** - Global settings
```sql
CREATE TABLE ai_core_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Tenant (NULL = global settings)
    tenant_id BIGINT UNSIGNED NULL,

    -- Setting
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON NOT NULL,
    setting_type VARCHAR(50),  -- 'string', 'number', 'boolean', 'json'

    -- Metadata
    description TEXT,
    is_readonly BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_tenant_key (tenant_id, setting_key),
    FOREIGN KEY (tenant_id) REFERENCES ai_core_tenants(id) ON DELETE CASCADE,

    INDEX idx_key (setting_key)
);
```

---

## 🎨 Menu Structure

### Admin Menu: AI Core

```
AI Core
├── 📊 Dashboard
│   ├── Overview (usage, quotas, alerts)
│   ├── Real-time Monitoring
│   └── Analytics Charts
│
├── 🤖 AI Features
│   ├── All Features (list + enable/disable)
│   ├── Feature Settings
│   ├── Add New Feature
│   └── Feature Dependencies
│
├── 🏢 Tenants/Organizations
│   ├── All Tenants
│   ├── Add Tenant
│   ├── Tenant Settings
│   └── Subscription Management
│
├── 📈 Usage & Quotas
│   ├── Current Usage
│   ├── Quota Management
│   ├── Usage History
│   └── Usage Reports
│
├── ⏰ Scheduling
│   ├── Active Schedules
│   ├── Create Schedule
│   ├── Schedule History
│   └── Cron Jobs
│
├── 🔔 Alerts & Notifications
│   ├── All Alerts
│   ├── Alert Rules
│   └── Notification Settings
│
├── 💰 Billing & Pricing (Optional)
│   ├── Pricing Tiers
│   ├── Invoices
│   ├── Payment History
│   └── Subscription Plans
│
├── ⚙️ Settings
│   ├── Global Settings
│   ├── Feature Defaults
│   ├── Quota Defaults
│   └── Integration Settings
│
└── 📚 Documentation
    ├── API Documentation
    ├── Feature Guides
    └── Developer Docs
```

---

## 🔧 Implementation Steps

### Phase 1: Core Infrastructure (Week 1-2)

#### Step 1.1: Database Setup
- [ ] สร้าง migrations ทั้ง 8 tables
- [ ] สร้าง models พร้อม relationships
- [ ] สร้าง seeders สำหรับ default data
- [ ] ทดสอบ relationships

#### Step 1.2: สร้าง Menu Structure
- [ ] เพิ่ม "AI Core" menu ใน admin sidebar
- [ ] สร้าง submenu items
- [ ] ปรับ UI icons และ styling
- [ ] ทดสอบ navigation

#### Step 1.3: สร้าง Base Services
- [ ] `AICoreService` - Main service class
- [ ] `FeatureRegistry` - Manage features
- [ ] `QuotaManager` - Track & enforce quotas
- [ ] `ScheduleManager` - Handle schedules
- [ ] `AlertManager` - Send notifications

### Phase 2: Dashboard & Feature Management (Week 3)

#### Step 2.1: Dashboard
- [ ] Overview cards (total features, active tenants, usage, alerts)
- [ ] Real-time usage charts
- [ ] Recent activity feed
- [ ] Quick actions

#### Step 2.2: Feature Management
- [ ] List all AI features
- [ ] Enable/Disable toggle (real-time)
- [ ] Feature configuration panel
- [ ] Feature dependencies visualization

#### Step 2.3: Register Existing AI Features
- [ ] Scan codebase for AI features
- [ ] Create feature entries in database
- [ ] Migrate existing features to AI Core control

### Phase 3: Quota & Usage Tracking (Week 4)

#### Step 3.1: Usage Logging
- [ ] Middleware สำหรับ track usage
- [ ] Real-time usage counter
- [ ] Usage aggregation (hourly, daily, monthly)

#### Step 3.2: Quota Enforcement
- [ ] Check quota before feature execution
- [ ] Soft limit warnings
- [ ] Hard limit blocking
- [ ] Auto-renewal logic

#### Step 3.3: Usage Reports
- [ ] Usage dashboard per feature
- [ ] Export usage reports (CSV, PDF)
- [ ] Billing reports

### Phase 4: Scheduling System (Week 5)

#### Step 4.1: Schedule Management UI
- [ ] Create schedule form
- [ ] Schedule calendar view
- [ ] Edit/Delete schedules

#### Step 4.2: Schedule Executor
- [ ] Cron job runner
- [ ] Execute schedules based on timezone
- [ ] Schedule history logging

#### Step 4.3: Schedule Types
- [ ] One-time schedules
- [ ] Recurring schedules (daily, weekly, monthly)
- [ ] Custom cron patterns

### Phase 5: Alerts & Notifications (Week 6)

#### Step 5.1: Alert Rules
- [ ] Quota warning alerts (50%, 80%, 100%)
- [ ] Feature error alerts
- [ ] Schedule execution alerts

#### Step 5.2: Notification Channels
- [ ] Email notifications
- [ ] LINE notifications
- [ ] In-app notifications
- [ ] Slack/Discord webhooks (optional)

### Phase 6: Tenant Management (Week 7)

#### Step 6.1: Tenant CRUD
- [ ] Create/Edit/Delete tenants
- [ ] Tenant settings
- [ ] Tenant-specific quotas

#### Step 6.2: Multi-tenancy Features
- [ ] Tenant isolation
- [ ] Tenant-specific feature access
- [ ] Tenant billing

### Phase 7: Integration & Migration (Week 8)

#### Step 7.1: Migrate Existing Features
- [ ] ตัดการเชื่อมต่อเดิม
- [ ] เชื่อมต่อผ่าน AI Core เท่านั้น
- [ ] ทดสอบ backward compatibility

#### Step 7.2: API Integration
- [ ] REST API สำหรับ feature execution
- [ ] Webhook support
- [ ] SDK/Client libraries

---

## 🚦 AI Features ที่ต้อง Register (ต้อง scan codebase)

### ตัวอย่าง Features ที่จะ Register:

1. **LINE Bot Signup**
   - Feature Key: `line_bot_signup`
   - Quota Type: `executions`
   - Default Limit: 1000/month

2. **Chatbot AI**
   - Feature Key: `chatbot_ai`
   - Quota Type: `tokens`
   - Default Limit: 100000 tokens/month

3. **Automation Workflows**
   - Feature Key: `automation_workflows`
   - Quota Type: `executions`
   - Default Limit: 5000/month

4. **AI Bot Marketplace**
   - Feature Key: `ai_bot_marketplace`
   - Quota Type: `requests`
   - Default Limit: Unlimited

---

## 📋 ตัวอย่าง Code Structure

### Service: AICoreService

```php
class AICoreService
{
    /**
     * Check if feature is enabled for tenant
     */
    public function isFeatureEnabled(string $featureKey, int $tenantId): bool
    {
        // Check global feature status
        $feature = AICoreFeature::where('feature_key', $featureKey)->first();
        if (!$feature || !$feature->is_enabled) {
            return false;
        }

        // Check tenant access
        $access = AICoreFeatureAccess::where('tenant_id', $tenantId)
            ->where('feature_id', $feature->id)
            ->first();

        if (!$access || !$access->is_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Check quota before feature execution
     */
    public function checkQuota(string $featureKey, int $tenantId, int $amount = 1): bool
    {
        $feature = AICoreFeature::where('feature_key', $featureKey)->first();

        // Get current quota
        $quota = AICoreQuota::getCurrentQuota($tenantId, $feature->id);

        if ($quota->quota_remaining < $amount) {
            // Send alert
            $this->alertManager->sendQuotaExceededAlert($tenantId, $feature);
            return false;
        }

        return true;
    }

    /**
     * Track feature usage
     */
    public function trackUsage(string $featureKey, int $tenantId, array $metadata = []): void
    {
        $feature = AICoreFeature::where('feature_key', $featureKey)->first();

        // Log usage
        AICoreUsageLog::create([
            'tenant_id' => $tenantId,
            'feature_id' => $feature->id,
            'user_id' => auth()->id(),
            'usage_type' => $feature->quota_type,
            'usage_amount' => $metadata['amount'] ?? 1,
            'metadata' => $metadata,
        ]);

        // Update quota
        AICoreQuota::incrementUsage($tenantId, $feature->id, $metadata['amount'] ?? 1);
    }
}
```

### Middleware: CheckAIFeatureAccess

```php
class CheckAIFeatureAccess
{
    public function handle($request, Closure $next, string $featureKey)
    {
        $tenantId = $this->getCurrentTenantId();

        // Check if feature is enabled
        if (!app(AICoreService::class)->isFeatureEnabled($featureKey, $tenantId)) {
            return response()->json([
                'error' => 'Feature is disabled or not accessible',
            ], 403);
        }

        // Check quota
        if (!app(AICoreService::class)->checkQuota($featureKey, $tenantId)) {
            return response()->json([
                'error' => 'Quota exceeded',
            ], 429);
        }

        return $next($request);
    }
}
```

---

**Document Version**: 1.0.0
**Created**: 2025-11-18
**Status**: Planning Complete - Ready to Implement
**Next Step**: Start Phase 1 - Database Setup & Menu Creation
