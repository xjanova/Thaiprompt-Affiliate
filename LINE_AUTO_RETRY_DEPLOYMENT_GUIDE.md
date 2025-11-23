# LINE Auto-Retry System - Deployment Guide

> **Complete Deployment Guide for LINE Auto-Retry & Error Recovery System**
>
> **Version:** 1.0.0 | **Last Updated:** 2025-11-23

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Database Migration](#database-migration)
3. [Queue Configuration](#queue-configuration)
4. [Cron Job Setup](#cron-job-setup)
5. [Testing](#testing)
6. [Monitoring](#monitoring)
7. [Troubleshooting](#troubleshooting)
8. [Rollback Procedure](#rollback-procedure)

---

## ✅ Pre-Deployment Checklist

**Before deploying, ensure:**

- [ ] Server has PHP 8.1+
- [ ] Laravel 11 application is running
- [ ] MySQL 8.0+ database is configured
- [ ] Queue worker is set up (Redis, Database, or other driver)
- [ ] Cron jobs can be configured on the server
- [ ] LINE Official Account credentials are configured

---

## 🗄️ Database Migration

### Step 1: Run Migrations

```bash
# Run migrations to create tables
php artisan migrate

# Expected output:
# Migrating: 2025_11_23_200000_create_line_failed_messages_table
# Migrated:  2025_11_23_200000_create_line_failed_messages_table (XX.XXs)
# Migrating: 2025_11_23_200001_create_line_error_logs_table
# Migrated:  2025_11_23_200001_create_line_error_logs_table (XX.XXs)
```

### Step 2: Verify Tables

```bash
# Check if tables exist
php artisan tinker
>>> \Schema::hasTable('line_failed_messages');
// Should return: true
>>> \Schema::hasTable('line_error_logs');
// Should return: true
>>> exit
```

### Migration Details

**Tables Created:**

1. **`line_failed_messages`** - Queue สำหรับข้อความที่ส่งล้มเหลว
   - Fields: `id`, `line_user_id`, `message_type`, `message_payload`, `error_type`, `retry_count`, `max_retries`, `next_retry_at`, `status`, etc.
   - Indexes: `status`, `next_retry_at`, `line_user_id`

2. **`line_error_logs`** - บันทึก error สำหรับ analytics
   - Fields: `id`, `error_code`, `error_type`, `severity`, `is_recovered`, `recovery_method`, `occurred_at`, etc.
   - Indexes: `error_type`, `severity`, `is_recovered`

---

## ⚙️ Queue Configuration

### Step 1: Choose Queue Driver

**Option A: Redis (Recommended for Production)**

```bash
# .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Option B: Database (Easier Setup)**

```bash
# .env
QUEUE_CONNECTION=database

# Create jobs table
php artisan queue:table
php artisan migrate
```

### Step 2: Start Queue Worker

**Production (using Supervisor):**

```bash
# Create supervisor config: /etc/supervisor/conf.d/line-retry-worker.conf
[program:line-retry-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=line-retry --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/line-retry-worker.log
stopwaitsecs=3600

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start line-retry-worker:*
```

**Development:**

```bash
# Run queue worker manually
php artisan queue:work --queue=line-retry --tries=3
```

### Step 3: Verify Queue Worker

```bash
# Check supervisor status
sudo supervisorctl status line-retry-worker:*

# Expected output:
# line-retry-worker:line-retry-worker_00   RUNNING   pid 12345, uptime 0:01:23
# line-retry-worker:line-retry-worker_01   RUNNING   pid 12346, uptime 0:01:23

# Test queue
php artisan tinker
>>> dispatch(new App\Jobs\RetryFailedMessagesJob(1));
>>> exit

# Check queue worker logs
tail -f storage/logs/line-retry-worker.log
```

---

## ⏰ Cron Job Setup

### Step 1: Add Cron Jobs

**Option A: Full Automation (Recommended)**

```bash
# Edit crontab
crontab -e

# Add these lines:
# Process retries every 5 minutes
*/5 * * * * cd /path/to/artisan && php artisan line:process-retries --limit=100 >> /dev/null 2>&1

# Cleanup old messages daily at 2 AM
0 2 * * * cd /path/to/artisan && php artisan line:process-retries --cleanup --cleanup-days=30 >> /dev/null 2>&1

# Health check hourly
0 * * * * cd /path/to/artisan && php artisan line:retry-status >> /path/to/storage/logs/line-retry-health.log 2>&1
```

**Option B: Manual Processing Only**

```bash
# Process retries manually when needed
php artisan line:process-retries --limit=100
```

### Step 2: Verify Cron Jobs

```bash
# List cron jobs
crontab -l

# Wait 5 minutes and check logs
tail -f storage/logs/laravel.log | grep "LINE Auto-Retry"

# Expected output:
# [2025-11-23 10:05:00] local.INFO: LINE Auto-Retry: Processing pending messages {"count":15,"limit":100}
# [2025-11-23 10:05:05] local.INFO: LINE Auto-Retry: Batch completed {"processed":15,"succeeded":12,"failed":3}
```

---

## 🧪 Testing

### Test 1: Manual Failure Test

```bash
php artisan tinker

# Create a test failed message
$failedMessage = \App\Models\LineFailedMessage::create([
    'line_user_id' => 'U1234567890',
    'message_type' => 'text',
    'message_payload' => ['text' => 'Test message'],
    'error_type' => 'network',
    'error_message' => 'Test error',
    'retry_count' => 0,
    'max_retries' => 5,
    'next_retry_at' => now(),
    'status' => 'pending',
]);

echo "Created failed message ID: {$failedMessage->id}\n";
exit
```

### Test 2: Process Retry

```bash
# Process the test message
php artisan line:process-retries --limit=1

# Expected output:
# 🔄 เริ่มประมวลผลข้อความ LINE ที่รอการ retry...
# 📊 กำลังประมวลผล (สูงสุด 1 ข้อความ)...
# ✅ ประมวลผลเสร็จสิ้น:
#    - Retry สำเร็จ: 0 ข้อความ  (หรือ 1 ถ้า LINE credentials ถูกต้อง)
```

### Test 3: Check Status

```bash
# Check system status
php artisan line:retry-status --detailed

# Expected output:
# 🔍 LINE Auto-Retry System Status
# 📊 สถานะโดยรวม:
# ...
# 🟢 Health Status: HEALTHY
```

### Test 4: Integration Test

```php
// In your code, trigger a failure
$lineService = new \App\Services\LineService();

// This will fail if LINE user ID is invalid
$result = $lineService->sendPushMessage(
    'INVALID_USER_ID',
    'Test message'
);

// Check if failure was recorded
$failedMessage = \App\Models\LineFailedMessage::latest()->first();
// Should see the failed message with auto-retry scheduled
```

---

## 📊 Monitoring

### Dashboard Commands

**Quick Status Check:**

```bash
# Basic status
php artisan line:retry-status

# Detailed status with 30-day stats
php artisan line:retry-status --days=30 --detailed
```

**Monitor Health:**

```bash
# Check health status hourly
watch -n 3600 'php artisan line:retry-status'
```

### Log Monitoring

**Application Logs:**

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log | grep LINE

# Monitor queue worker logs
tail -f storage/logs/line-retry-worker.log
```

**Error Patterns to Watch:**

```bash
# Check for critical errors
grep "CRITICAL" storage/logs/laravel.log | grep LINE

# Check for rate limit errors
grep "rate_limit" storage/logs/laravel.log | grep LINE

# Check for abandoned messages
grep "abandoned" storage/logs/laravel.log | grep LINE
```

### Database Queries for Monitoring

```sql
-- Count messages by status
SELECT status, COUNT(*) as count
FROM line_failed_messages
GROUP BY status;

-- Recent failures
SELECT *
FROM line_failed_messages
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC
LIMIT 20;

-- Error type breakdown
SELECT error_type, COUNT(*) as count
FROM line_failed_messages
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY error_type
ORDER BY count DESC;

-- Unresolved critical errors
SELECT *
FROM line_error_logs
WHERE severity = 'critical'
  AND is_recovered = FALSE
ORDER BY occurred_at DESC
LIMIT 10;
```

### Metrics to Track

1. **Success Rate** - Should be > 80%
2. **Abandonment Rate** - Should be < 10%
3. **Average Retry Count** - Should be < 3
4. **Pending Queue Size** - Should be < 100
5. **Unresolved Critical Errors** - Should be 0

---

## 🐛 Troubleshooting

### Issue 1: Queue Worker Not Processing

**Symptoms:**
- Messages stuck in pending status
- `next_retry_at` in the past but not processed

**Solutions:**

```bash
# 1. Check if queue worker is running
sudo supervisorctl status line-retry-worker:*

# 2. Restart queue worker
sudo supervisorctl restart line-retry-worker:*

# 3. Check queue worker logs
tail -f storage/logs/line-retry-worker.log

# 4. Manually process
php artisan line:process-retries
```

### Issue 2: High Abandonment Rate

**Symptoms:**
- Many messages marked as "abandoned"
- Low success rate

**Solutions:**

```bash
# 1. Check error types
php artisan line:retry-status --detailed

# 2. Check LINE API credentials
php artisan tinker
>>> $settings = \App\Models\LineOaSetting::first();
>>> echo $settings->channel_access_token;  // Should not be empty
>>> exit

# 3. Test LINE connection
# Via Quick Settings Panel or:
php artisan tinker
>>> $service = new \App\Services\LineService();
>>> $result = $service->testConnection();
>>> print_r($result);
>>> exit

# 4. Increase max_retries if needed
php artisan tinker
>>> \App\Models\LineFailedMessage::where('status', 'pending')
    ->update(['max_retries' => 7]);
>>> exit
```

### Issue 3: Rate Limit Errors

**Symptoms:**
- Many "rate_limit" error types
- Messages failing even after retries

**Solutions:**

```bash
# 1. Reduce cron frequency
# Change from */5 to */10 or */15 minutes
crontab -e

# 2. Reduce batch size
# Change --limit=100 to --limit=50
php artisan line:process-retries --limit=50

# 3. Add delay in retry service
# Edit LineAutoRetryService::retryPendingMessages()
# Increase usleep from 100000 to 200000 (0.2 second)
```

### Issue 4: Database Growing Too Large

**Symptoms:**
- `line_failed_messages` table > 100k rows
- `line_error_logs` table > 500k rows

**Solutions:**

```bash
# 1. Run cleanup immediately
php artisan line:process-retries --cleanup --cleanup-days=7

# 2. Schedule more frequent cleanup
crontab -e
# Add: 0 0 * * * php artisan line:process-retries --cleanup --cleanup-days=14

# 3. Manual cleanup (if needed)
php artisan tinker
>>> \App\Models\LineFailedMessage::whereNotNull('resolved_at')
      ->where('resolved_at', '<', now()->subDays(7))
      ->delete();
>>> exit
```

---

## 🔄 Rollback Procedure

### If Deployment Fails

**Step 1: Disable Auto-Retry**

```php
// Temporarily disable auto-retry in LineService
// Edit app/Services/LineService.php constructor:
public function __construct(bool $autoRetryEnabled = false) // Change to false
```

**Step 2: Stop Queue Worker**

```bash
sudo supervisorctl stop line-retry-worker:*
```

**Step 3: Disable Cron Jobs**

```bash
crontab -e
# Comment out LINE retry cron jobs
# */5 * * * * php artisan line:process-retries...
```

**Step 4: Rollback Migrations (if needed)**

```bash
# Rollback last 2 migrations
php artisan migrate:rollback --step=2

# Confirm tables are dropped
php artisan tinker
>>> \Schema::hasTable('line_failed_messages');
// Should return: false
>>> exit
```

**Step 5: Deploy Previous Version**

```bash
# Checkout previous commit
git checkout <previous-commit-hash>

# Or use deployment rollback script
./rollback.sh
```

---

## 📈 Performance Tuning

### Queue Worker Optimization

```ini
# Increase workers for high volume
numprocs=4  # in supervisor config

# Increase max time
--max-time=7200  # 2 hours
```

### Database Optimization

```sql
-- Add index if needed
CREATE INDEX idx_created_at ON line_failed_messages(created_at);

-- Partition table by month (for very high volume)
ALTER TABLE line_failed_messages
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202511 VALUES LESS THAN (202512),
    PARTITION p202512 VALUES LESS THAN (202601),
    ...
);
```

### Cron Job Tuning

```bash
# High volume: More frequent, smaller batches
*/2 * * * * php artisan line:process-retries --limit=50

# Low volume: Less frequent, larger batches
*/15 * * * * php artisan line:process-retries --limit=200
```

---

## 🎯 Best Practices

1. **Monitor Daily** - Check `php artisan line:retry-status` daily
2. **Set Alerts** - Alert when abandonment rate > 15%
3. **Regular Cleanup** - Run cleanup at least weekly
4. **Log Rotation** - Rotate Laravel logs to prevent disk fill
5. **Backup Database** - Backup before major changes
6. **Test in Staging** - Always test in staging environment first
7. **Document Changes** - Keep deployment log

---

## 📞 Support

**Logs Location:**
- Application: `storage/logs/laravel.log`
- Queue Worker: `storage/logs/line-retry-worker.log`
- Health Check: `storage/logs/line-retry-health.log`

**Useful Commands:**

```bash
# Quick health check
php artisan line:retry-status

# Process pending retries
php artisan line:process-retries

# Detailed diagnostics
php artisan line:retry-status --days=30 --detailed

# Check queue
php artisan queue:work --queue=line-retry --once

# Clear all caches
php artisan optimize:clear
```

---

**Deployment Checklist:**

- [ ] Migrations run successfully
- [ ] Tables created and indexed
- [ ] Queue worker running
- [ ] Cron jobs configured
- [ ] Test messages processed
- [ ] Health check shows green
- [ ] Logs monitored for 24 hours
- [ ] Documentation updated

---

**Made with ❤️ for Thaiprompt-Affiliate LINE Auto-Retry System**

**Version:** 1.0.0 | **Date:** 2025-11-23
