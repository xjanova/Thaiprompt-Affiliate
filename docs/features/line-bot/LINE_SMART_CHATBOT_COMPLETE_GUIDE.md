# LINE Smart Chatbot - Complete Guide

> **ระบบ LINE Official Account แบบครบเครื่องพร้อม Smart Features**
>
> **Version:** 1.0.0 (Phase 1 Complete) | **Last Updated:** 2025-11-23

---

## 📚 Table of Contents

1. [Overview](#overview)
2. [Features Summary](#features-summary)
3. [Architecture](#architecture)
4. [Quick Start](#quick-start)
5. [Component Guide](#component-guide)
6. [API Reference](#api-reference)
7. [Usage Examples](#usage-examples)
8. [Troubleshooting](#troubleshooting)
9. [What's Next](#whats-next)

---

## 🎯 Overview

**LINE Smart Chatbot** คือระบบจัดการ LINE Official Account ที่ทันสมัยและอัจฉริยะ พร้อมด้วย:

- ✅ **Quick Settings Panel** - จัดการระบบแบบเรียลไทม์ผ่าน Admin Dashboard
- ✅ **Auto-Retry System** - ส่งข้อความล้มเหลว? ระบบจัดการให้อัตโนมัติ
- ✅ **Error Recovery** - Self-healing เมื่อเกิดปัญหา
- ✅ **Analytics Dashboard** - วิเคราะห์ error patterns และ recovery metrics
- ✅ **Production-Ready** - พร้อม deploy ทันที พร้อม deployment guide

### สถานะปัจจุบัน: **Phase 1 Complete** ✅

**Phase 1 - Essential Smart Features** (100% เสร็จสมบูรณ์):
- [x] Quick Settings Panel
- [x] Auto-Retry & Error Recovery
- [x] Deployment Automation
- [x] Complete Documentation

---

## ⭐ Features Summary

### 1. Quick Settings Panel (เข้าถึงได้ทันทีใน Admin Dashboard)

**ปุ่มลอยมุมล่างขวา** ที่ให้คุณ:
- 🟢 เปิด/ปิดระบบ LINE OA ทั้งหมดด้วยคลิกเดียว
- 💬 Toggle LINE Messaging เปิด/ปิด
- ✅ Toggle บังคับลงทะเบียนผ่าน LINE
- 🧪 ทดสอบการเชื่อมต่อ LINE API
- 📊 ดูสถานะระบบแบบเรียลไทม์
- ⌨️ Keyboard shortcut: `Ctrl+Shift+L` (Windows/Linux) หรือ `Cmd+Shift+L` (Mac)

**UI/UX:**
- Glassmorphism Design (V3 Standards)
- Dark Mode Support
- Smooth Animations
- Mobile Responsive

**Files:**
- `resources/views/components/line/quick-settings-panel.blade.php` (500 lines)
- `app/Http/Controllers/Admin/LineOaController.php` - quickUpdate() method
- `routes/admin.php` - PATCH /admin/line-oa/quick-update

**Documentation:**
- [LINE_QUICK_SETTINGS_PANEL_GUIDE.md](LINE_QUICK_SETTINGS_PANEL_GUIDE.md)

---

### 2. Auto-Retry & Error Recovery System

**เมื่อส่งข้อความ LINE ล้มเหลว:**
1. 📝 ระบบบันทึกข้อความอัตโนมัติ
2. ⏰ Schedule retry ด้วย exponential backoff (2s → 4s → 8s → 16s → 32s)
3. 🔄 ลองส่งอีกครั้งอัตโนมัติ (สูงสุด 5 ครั้ง)
4. ✅ สำเร็จ? บันทึก recovery metrics
5. ❌ ล้มเหลว? ลอง circuit breaker และ retry ต่อ
6. ⚠️ เกิน limit? ทำเครื่องหมาย abandoned และแจ้งเตือน

**Components:**

| Component | Description | Lines | Status |
|-----------|-------------|-------|--------|
| **LineAutoRetryService** | Core retry orchestration | ~400 | ✅ |
| **RetryFailedMessagesJob** | Queue worker | ~250 | ✅ |
| **LineService** | Auto-retry integration | +300 | ✅ |
| **LineFailedMessage** | Model & scopes | ~300 | ✅ |
| **LineErrorLog** | Analytics model | ~250 | ✅ |

**Database:**
- `line_failed_messages` - Queue ข้อความที่ล้มเหลว
- `line_error_logs` - บันทึก error สำหรับ analytics

**Files:**
- `app/Services/LineAutoRetryService.php`
- `app/Jobs/RetryFailedMessagesJob.php`
- `app/Models/LineFailedMessage.php`
- `app/Models/LineErrorLog.php`
- `database/migrations/2025_11_23_200000_create_line_failed_messages_table.php`
- `database/migrations/2025_11_23_200001_create_line_error_logs_table.php`

---

### 3. Deployment Automation

**Artisan Commands:**

```bash
# ประมวลผลข้อความที่รอ retry (สำหรับ cron)
php artisan line:process-retries --limit=100 --cleanup

# ตรวจสอบสถานะระบบ
php artisan line:retry-status --days=30 --detailed
```

**Cron Job Setup:**

```bash
# ทุก 5 นาที: ประมวลผล retry
*/5 * * * * php artisan line:process-retries --limit=100

# ทุกวัน 2:00 AM: ลบข้อความเก่า
0 2 * * * php artisan line:process-retries --cleanup --cleanup-days=30

# ทุกชั่วโมง: Health check
0 * * * * php artisan line:retry-status >> storage/logs/line-retry-health.log
```

**Queue Worker (Supervisor):**

```ini
[program:line-retry-worker]
command=php /path/to/artisan queue:work --queue=line-retry --sleep=3 --tries=3
numprocs=2
autostart=true
autorestart=true
```

**Files:**
- `app/Console/Commands/ProcessLineRetries.php` (~250 lines)
- `app/Console/Commands/LineRetryStatus.php` (~350 lines)
- [LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md](LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md) (~800 lines)

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Dashboard                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │       Quick Settings Panel (Alpine.js)               │  │
│  │  • Master Toggle  • Feature Toggles  • Quick Actions │  │
│  └──────────────────────────────────────────────────────┘  │
└───────────────────────┬─────────────────────────────────────┘
                        │ PATCH /admin/line-oa/quick-update
                        ↓
┌─────────────────────────────────────────────────────────────┐
│                  LineOaController                            │
│              (quickUpdate method)                            │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ↓
┌─────────────────────────────────────────────────────────────┐
│                     LineService                              │
│  • sendPushMessage()      • sendFlexMessage()               │
│  • sendTemplateMessage()  • sendImageMessage()              │
│  • recordFailureAndRetry() helper                           │
└───────────┬──────────────────────────┬──────────────────────┘
            │                          │
            ↓ (Success)                ↓ (Failure)
     LINE API ✅            ┌──────────────────────────┐
                            │  LineAutoRetryService    │
                            │  • recordFailure()       │
                            │  • scheduleRetry()       │
                            └──────────┬───────────────┘
                                       │
                                       ↓ Dispatch Job
                            ┌──────────────────────────┐
                            │ RetryFailedMessagesJob   │
                            │ (Queue: line-retry)      │
                            └──────────┬───────────────┘
                                       │
                                       ↓ delay (exponential backoff)
                            ┌──────────────────────────┐
                            │  retry() → LineService   │
                            │  • Try resend            │
                            │  • Update status         │
                            │  • Log recovery          │
                            └──────────────────────────┘
                                       │
                                       ↓
                        ┌──────────────┴──────────────┐
                        ↓                              ↓
               ✅ Succeeded                   ❌ Failed
        • markAsSucceeded()            • incrementRetryCount()
        • Log recovery                 • Schedule next retry
        • Update error_log              • Or markAsAbandoned()
```

**Database Schema:**

```sql
line_failed_messages:
├─ id
├─ line_user_id
├─ message_type (text, flex, template, image)
├─ message_payload (JSON)
├─ error_type (network, rate_limit, timeout, etc.)
├─ error_message
├─ retry_count / max_retries
├─ next_retry_at (exponential backoff)
├─ status (pending → retrying → succeeded/abandoned)
└─ resolved_at

line_error_logs:
├─ id
├─ error_type / severity (low, medium, high, critical)
├─ is_recovered / recovery_method
├─ recovery_time_seconds
├─ occurred_at / resolved_at
└─ metadata (JSON)
```

---

## 🚀 Quick Start

### 1. Installation

```bash
# 1. Run migrations
php artisan migrate

# Expected output:
# Migrating: 2025_11_23_200000_create_line_failed_messages_table
# Migrated:  2025_11_23_200000_create_line_failed_messages_table
# Migrating: 2025_11_23_200001_create_line_error_logs_table
# Migrated:  2025_11_23_200001_create_line_error_logs_table

# 2. Configure queue (Redis recommended)
# .env:
QUEUE_CONNECTION=redis

# Or database:
php artisan queue:table
php artisan migrate

# 3. Start queue worker
php artisan queue:work --queue=line-retry --tries=3

# Or use Supervisor (production)
# See: LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md
```

### 2. Configure LINE OA

**Via Quick Settings Panel:**

1. เข้า Admin Dashboard
2. คลิกปุ่มลอย LINE ที่มุมล่างขวา (หรือกด `Ctrl+Shift+L`)
3. เปิด Master Switch
4. เปิด LINE Messaging
5. คลิก "Test Connection" เพื่อทดสอบ

**Via Settings Page:**

1. ไปที่ `/admin/line-oa/settings`
2. กรอก:
   - LINE Login Channel ID
   - Channel Secret
   - Channel Access Token
3. กดบันทึก

### 3. Setup Cron Jobs

```bash
# Edit crontab
crontab -e

# Add:
*/5 * * * * cd /path/to/project && php artisan line:process-retries --limit=100 >> /dev/null 2>&1
0 2 * * * cd /path/to/project && php artisan line:process-retries --cleanup --cleanup-days=30 >> /dev/null 2>&1
```

### 4. Test

```bash
# ทดสอบส่งข้อความ
php artisan tinker
>>> $user = User::where('line_user_id', '!=', null)->first();
>>> $service = new \App\Services\LineService();
>>> $service->sendPushMessage($user->line_user_id, 'สวัสดีครับ! 🎉');
>>> exit

# ตรวจสอบสถานะ
php artisan line:retry-status

# ประมวลผล retries
php artisan line:process-retries
```

---

## 📖 Component Guide

### Quick Settings Panel

**Location:** มุมล่างขวาของทุกหน้า Admin

**การใช้งาน:**

1. **เปิด/ปิดระบบ:**
   - คลิก Master Switch บน panel
   - ระบบจะอัพเดททันที
   - แจ้งเตือน: "🟢 เปิดใช้งานระบบ LINE OA" หรือ "⚫ ปิดใช้งานระบบ LINE OA"

2. **เปิด/ปิด Features:**
   - Toggle "LINE Messaging" เพื่อเปิด/ปิดการส่งข้อความ
   - Toggle "Require LINE Registration" เพื่อบังคับลงทะเบียนผ่าน LINE

3. **Quick Actions:**
   - **Test Connection**: ทดสอบการเชื่อมต่อ LINE API
   - **View Logs**: ดูประวัติการใช้งาน
   - **Check Stats**: เช็คสถิติ
   - **Full Settings**: ไปยังหน้าตั้งค่าเต็ม

**Keyboard Shortcut:**
- Windows/Linux: `Ctrl + Shift + L`
- macOS: `Cmd + Shift + L`

**API Endpoint:**
```http
PATCH /admin/line-oa/quick-update
Content-Type: application/json
X-CSRF-TOKEN: <token>

{
  "is_active": true,
  "enable_line_messaging": true,
  "require_line_registration": false
}
```

**Documentation:** [LINE_QUICK_SETTINGS_PANEL_GUIDE.md](LINE_QUICK_SETTINGS_PANEL_GUIDE.md)

---

### Auto-Retry System

**Automatic Behavior:**

เมื่อ `LineService::sendPushMessage()` (หรือ method อื่นๆ) ล้มเหลว:

1. ระบบบันทึกข้อความใน `line_failed_messages` อัตโนมัติ
2. Schedule retry job ด้วย exponential backoff:
   - 1st retry: after 2 seconds
   - 2nd retry: after 4 seconds
   - 3rd retry: after 8 seconds
   - 4th retry: after 16 seconds
   - 5th retry: after 32 seconds
   - Max: 60 seconds
3. `RetryFailedMessagesJob` ถูก dispatch ไปยัง queue `line-retry`
4. Queue worker ประมวลผล job และเรียก `LineAutoRetryService::retry()`
5. ถ้าสำเร็จ: mark as `succeeded` และบันทึก recovery
6. ถ้าล้มเหลว: increment retry count และ schedule ครั้งถัดไป
7. ถ้าเกิน max retries: mark as `abandoned`

**Manual Processing:**

```bash
# ประมวลผลข้อความที่รอ retry (max 100)
php artisan line:process-retries --limit=100

# พร้อมลบข้อความเก่า
php artisan line:process-retries --cleanup --cleanup-days=30
```

**Check Status:**

```bash
# สถานะโดยรวม
php artisan line:retry-status

# สถิติ 30 วัน พร้อมรายละเอียด
php artisan line:retry-status --days=30 --detailed
```

**Programmatic Usage:**

```php
use App\Services\LineAutoRetryService;

$retryService = app(LineAutoRetryService::class);

// บันทึกความล้มเหลว manually (ถ้าจำเป็น)
$retryService->recordFailure(
    $lineUserId,
    'text',
    ['text' => 'ข้อความ'],
    new Exception('Error message')
);

// Retry ข้อความ manually
$failedMessage = LineFailedMessage::find(1);
$success = $retryService->retry($failedMessage);

// ดึงสถิติ
$stats = $retryService->getRetryStatistics(7); // 7 days

// Cleanup
$deleted = $retryService->cleanupOldMessages(30); // 30 days
```

---

## 🔌 API Reference

### LineService Methods

#### `sendPushMessage()`

ส่งข้อความ text ไปยัง LINE user พร้อม auto-retry

```php
/**
 * @param string $lineUserId LINE User ID
 * @param string $message ข้อความที่จะส่ง
 * @param array $additionalMessages ข้อความเพิ่มเติม (optional)
 * @return bool สำเร็จหรือไม่
 */
public function sendPushMessage(
    string $lineUserId,
    string $message,
    array $additionalMessages = []
): bool
```

**Example:**

```php
$lineService = new LineService();
$success = $lineService->sendPushMessage(
    'U1234567890abcdef',
    'สวัสดีครับ!'
);

if ($success) {
    echo "ส่งสำเร็จ!";
} else {
    echo "ส่งล้มเหลว (จะ retry อัตโนมัติ)";
}
```

#### `sendFlexMessage()`

ส่ง Flex Message (Rich UI) พร้อม auto-retry

```php
public function sendFlexMessage(
    string $lineUserId,
    array $flexMessage,
    ?array $quickReply = null
): bool
```

**Example:**

```php
$flexMessage = [
    'type' => 'flex',
    'altText' => 'Welcome!',
    'contents' => [
        'type' => 'bubble',
        'body' => [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => 'ยินดีต้อนรับ!',
                    'weight' => 'bold',
                    'size' => 'xl',
                ],
            ],
        ],
    ],
];

$lineService->sendFlexMessage('U1234567890', $flexMessage);
```

#### `sendTemplateMessage()`

ส่ง Template Message (Buttons, Confirm, Carousel) พร้อม auto-retry

```php
public function sendTemplateMessage(
    string $lineUserId,
    array $templateMessage
): bool
```

#### `sendImageMessage()`

ส่งรูปภาพพร้อม auto-retry

```php
public function sendImageMessage(
    string $lineUserId,
    string $originalContentUrl,
    string $previewImageUrl
): bool
```

**Example:**

```php
$lineService->sendImageMessage(
    'U1234567890',
    'https://example.com/images/photo.jpg',
    'https://example.com/images/photo_preview.jpg'
);
```

---

### LineAutoRetryService Methods

#### `recordFailure()`

บันทึกข้อความที่ล้มเหลวและ schedule retry อัตโนมัติ

```php
public function recordFailure(
    string $lineUserId,
    string $messageType,
    array $messagePayload,
    Exception $exception,
    int $maxRetries = 5
): LineFailedMessage
```

#### `retry()`

ทำการ retry ข้อความที่ล้มเหลว

```php
public function retry(LineFailedMessage $failedMessage): bool
```

#### `retryPendingMessages()`

Batch process ข้อความทั้งหมดที่รอการ retry

```php
public function retryPendingMessages(int $limit = 100): int // returns success count
```

#### `getRetryStatistics()`

ดึงสถิติการ retry

```php
public function getRetryStatistics(int $days = 7): array
```

**Returns:**

```php
[
    'total' => 150,
    'succeeded' => 120,
    'abandoned' => 10,
    'pending' => 20,
    'success_rate' => 80.0,
    'abandonment_rate' => 6.67,
    'avg_retry_count' => 2.5,
    'by_error_type' => [
        'network' => 50,
        'rate_limit' => 30,
        'timeout' => 20,
        //...
    ],
]
```

---

## 💡 Usage Examples

### Example 1: Send Welcome Message

```php
use App\Services\LineService;
use App\Models\User;

// ส่งข้อความต้อนรับ
$user = User::find(1);
$lineService = new LineService();

$success = $lineService->sendWelcomeMessage($user);

if ($success) {
    Log::info("Welcome message sent to {$user->name}");
} else {
    // ไม่ต้องกังวล! ระบบจะ retry อัตโนมัติ
    Log::warning("Welcome message failed (will auto-retry)");
}
```

### Example 2: Send Flex Message with Error Handling

```php
use App\Services\LineService;

$lineService = new LineService();

try {
    $flexMessage = [
        'type' => 'flex',
        'altText' => 'Product Info',
        'contents' => [
            // ... flex message content
        ],
    ];

    $success = $lineService->sendFlexMessage(
        $user->line_user_id,
        $flexMessage
    );

    if (!$success) {
        // Failed, but auto-retry is handling it
        return response()->json([
            'status' => 'queued',
            'message' => 'Message queued for retry',
        ]);
    }

    return response()->json([
        'status' => 'sent',
        'message' => 'Message sent successfully',
    ]);

} catch (Exception $e) {
    Log::error('Unexpected error: ' . $e->getMessage());
    return response()->json([
        'status' => 'error',
        'message' => 'An error occurred',
    ], 500);
}
```

### Example 3: Monitor and Alert on High Failure Rate

```php
use App\Services\LineAutoRetryService;

$retryService = app(LineAutoRetryService::class);
$stats = $retryService->getRetryStatistics(1); // Last 24 hours

// Alert if abandonment rate > 15%
if ($stats['abandonment_rate'] > 15) {
    // Send notification to admin
    Notification::route('mail', 'admin@example.com')
        ->notify(new HighFailureRateAlert($stats));
}

// Alert if too many pending
if ($stats['pending'] > 500) {
    Log::critical('Too many pending LINE messages!', $stats);
}
```

### Example 4: Disable Auto-Retry for Specific Use Case

```php
// Create LineService with auto-retry disabled
$lineService = new LineService(autoRetryEnabled: false);

// This will fail immediately without retry
$success = $lineService->sendPushMessage($userId, 'Critical message');

if (!$success) {
    // Handle failure manually
    $this->handleCriticalFailure($userId);
}
```

---

## 🔧 Troubleshooting

### Quick Diagnostics

```bash
# 1. Check system health
php artisan line:retry-status --detailed

# 2. Check recent failures
php artisan tinker
>>> LineFailedMessage::latest()->limit(5)->get();
>>> exit

# 3. Check queue worker
sudo supervisorctl status line-retry-worker:*

# 4. Check cron jobs
crontab -l | grep line

# 5. Check logs
tail -f storage/logs/laravel.log | grep LINE
```

### Common Issues

**1. Messages Not Retrying**

```bash
# Check queue worker
sudo supervisorctl status

# Restart queue worker
sudo supervisorctl restart line-retry-worker:*

# Process manually
php artisan line:process-retries
```

**2. High Abandonment Rate**

```bash
# Check LINE credentials
php artisan tinker
>>> $settings = \App\Models\LineOaSetting::first();
>>> $settings->channel_access_token; // Should not be empty
>>> exit

# Test connection
# Via Quick Settings Panel > Test Connection
```

**3. Rate Limit Errors**

```bash
# Reduce cron frequency
crontab -e
# Change from */5 to */10 or */15 minutes

# Reduce batch size
php artisan line:process-retries --limit=50
```

**Full Troubleshooting Guide:** [LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md#troubleshooting](LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md#troubleshooting)

---

## 🎯 What's Next

### Phase 2: Intelligence & Analytics (Coming Soon)

Planned features:

1. **Smart Analytics Dashboard**
   - Real-time message delivery stats
   - Error pattern visualization
   - Recovery rate trends
   - User engagement metrics

2. **Smart Notifications**
   - Admin alerts for critical errors
   - Daily/weekly summary reports
   - Threshold-based notifications
   - Integration with Slack/Discord

3. **Real-time Monitoring**
   - Live dashboard
   - WebSocket updates
   - Performance metrics
   - System health indicators

### Phase 3: Advanced Features (Future)

1. **Smart User Segmentation**
2. **A/B Testing Framework**
3. **Advanced Message Templates**
4. **Multi-language Support**
5. **Webhook Management**

**See:** [LINE_SMART_CHATBOT_ENHANCEMENT_PLAN.md](LINE_SMART_CHATBOT_ENHANCEMENT_PLAN.md)

---

## 📚 Documentation Index

| Document | Description |
|----------|-------------|
| **[LINE_SMART_CHATBOT_ENHANCEMENT_PLAN.md](LINE_SMART_CHATBOT_ENHANCEMENT_PLAN.md)** | Master enhancement plan (10 features) |
| **[LINE_QUICK_SETTINGS_PANEL_GUIDE.md](LINE_QUICK_SETTINGS_PANEL_GUIDE.md)** | Quick Settings Panel usage guide |
| **[LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md](LINE_AUTO_RETRY_DEPLOYMENT_GUIDE.md)** | Complete deployment guide |
| **[LINE_SMART_CHATBOT_COMPLETE_GUIDE.md](LINE_SMART_CHATBOT_COMPLETE_GUIDE.md)** | This document (master guide) |

---

## 📞 Support & Resources

**Artisan Commands:**

```bash
# System status
php artisan line:retry-status [--days=7] [--detailed]

# Process retries
php artisan line:process-retries [--limit=100] [--cleanup] [--cleanup-days=30]
```

**Log Files:**

- `storage/logs/laravel.log` - Application logs
- `storage/logs/line-retry-worker.log` - Queue worker logs (if configured)
- `storage/logs/line-retry-health.log` - Health check logs (if cron enabled)

**Database Tables:**

- `line_failed_messages` - Failed message queue
- `line_error_logs` - Error analytics
- `line_oa_settings` - LINE OA configuration

---

## ✅ Phase 1 Complete Checklist

- [x] Quick Settings Panel implemented
- [x] Auto-Retry System implemented
- [x] Error Recovery implemented
- [x] Database migrations created
- [x] Models created (LineFailedMessage, LineErrorLog)
- [x] Services created (LineAutoRetryService)
- [x] Jobs created (RetryFailedMessagesJob)
- [x] LineService enhanced with auto-retry
- [x] Artisan commands created (ProcessLineRetries, LineRetryStatus)
- [x] Deployment guide written
- [x] Complete documentation written
- [x] All code committed and pushed

**Commits:**

1. `b5a3b24` - Quick Settings Panel
2. `9af613f` - Auto-Retry foundation (migrations & models)
3. `64712af` - Auto-Retry System (services & jobs)
4. `2ea9942` - Deployment Automation (commands & guide)

---

**Made with ❤️ for Thaiprompt-Affiliate**

**Version:** 1.0.0 | **Phase:** 1/3 Complete | **Date:** 2025-11-23
