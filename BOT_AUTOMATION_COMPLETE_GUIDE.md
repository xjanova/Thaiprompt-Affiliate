# 🤖 คู่มือระบบบอทออโตเมชั่นฉบับสมบูรณ์

## 📋 สารบัญ
1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [การติดตั้งและการตั้งค่า](#การติดตั้งและการตั้งค่า)
3. [โครงสร้างไฟล์](#โครงสร้างไฟล์)
4. [การใช้งาน API](#การใช้งาน-api)
5. [การสร้างบอทใหม่](#การสร้างบอทใหม่)
6. [การเชื่อมต่อแพลตฟอร์ม](#การเชื่อมต่อแพลตฟอร์ม)
7. [Advanced Features](#advanced-features)
8. [Troubleshooting](#troubleshooting)

---

## ภาพรวมระบบ

ระบบบอทออโตเมชั่นครบวงจรสำหรับการจัดการและโพสต์คอนเทนต์อัตโนมัติบนหลายแพลตฟอร์ม พร้อมระบบ AI ช่วยตอบคำถาม ปิดการขาย และตลาดซื้อขายบอท

### ✨ ฟีเจอร์หลัก

#### 1. **การโพสต์อัตโนมัติ**
- โพสตามเวลา (hourly, daily, weekly, monthly, cron)
- รองรับ 6 แพลตฟอร์ม: TikTok, Facebook, LINE, Instagram, Twitter, YouTube
- สร้างคอนเทนต์ด้วย AI
- ใช้เทมเพลตสำเร็จ
- กำหนดเวลาโพสต์ล่วงหน้า

#### 2. **AI Customer Support**
- ตอบคำถามอัตโนมัติด้วย AI
- ระบบ ticket support
- AI confidence scoring
- Flag for human assistance อัตโนมัติ
- ติดตาม response time และ satisfaction

#### 3. **AI Sales Assistant**
- ตรวจจับความต้องการลูกค้า (Intent Detection)
- แนะนำสินค้าอัตโนมัติ
- ติดตาม Sales Funnel
- Sentiment Analysis
- ปิดการขายอัตโนมัติ

#### 4. **Bot Marketplace**
- 10 หมวดหมู่บอท
- ระบบให้คะแนนและรีวิว
- Featured & Verified bots
- ระบบเช่าบอท (Rental/Subscription)
- รายงานรายได้แบบ real-time

#### 5. **Analytics & Reporting**
- ติดตามผลการโพสต์
- Engagement metrics (likes, comments, shares, views)
- Platform comparison
- Revenue tracking
- Export ข้อมูลเป็น CSV

---

## การติดตั้งและการตั้งค่า

### ขั้นตอนที่ 1: Run Migrations

```bash
# Run all bot automation migrations
php artisan migrate

# Seed initial data (platforms และ categories)
php artisan db:seed --class=BotPlatformSeeder
php artisan db:seed --class=BotMarketplaceCategorySeeder
```

### ขั้นตอนที่ 2: ตั้งค่า Cron Job

เพิ่มใน `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Process bot automations every minute
    $schedule->command('bot:process-automations')->everyMinute();

    // Process scheduled posts every 5 minutes
    $schedule->job(new ProcessScheduledPostsJob)->everyFiveMinutes();

    // Process subscription renewals daily
    $schedule->call(function () {
        app(BotRentalService::class)->processDueRenewals();
    })->daily();
}
```

### ขั้นตอนที่ 3: ตั้งค่า Queue

ใน `.env`:

```env
QUEUE_CONNECTION=database
# หรือ redis สำหรับ production
# QUEUE_CONNECTION=redis
```

Run queue worker:

```bash
php artisan queue:work --tries=3 --timeout=300
```

### ขั้นตอนที่ 4: ตั้งค่า Authorization

เพิ่มใน `app/Providers/AuthServiceProvider.php`:

```php
use App\Models\BotAutomation\BotAutomation;
use App\Policies\BotAutomation\BotAutomationPolicy;

protected $policies = [
    BotAutomation::class => BotAutomationPolicy::class,
];
```

---

## โครงสร้างไฟล์

```
app/
├── Models/BotAutomation/          # 14 Models
│   ├── BotSocialPlatform.php
│   ├── BotPlatformConnection.php
│   ├── BotContentTemplate.php
│   ├── BotAutomation.php
│   ├── BotScheduledPost.php
│   ├── BotAutomationExecution.php
│   ├── BotMarketplaceCategory.php
│   ├── BotMarketplaceListing.php
│   ├── BotMarketplaceReview.php
│   ├── BotSupportConversation.php
│   ├── BotSupportMessage.php
│   ├── BotSalesConversation.php
│   ├── BotSalesMessage.php
│   └── BotRentalSubscription.php
│
├── Services/BotAutomation/        # 7 Services
│   ├── BotAutomationService.php
│   ├── BotContentGenerationService.php
│   ├── BotPlatformService.php
│   ├── BotSupportService.php
│   ├── BotSalesService.php
│   ├── BotRentalService.php
│   └── BotAnalyticsService.php
│
├── Jobs/BotAutomation/            # 3 Jobs
│   ├── ExecuteBotAutomationJob.php
│   ├── ProcessScheduledPostsJob.php
│   └── PublishScheduledPostJob.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/BotAutomation/
│   │   │   └── BotAutomationController.php
│   │   └── Api/
│   │       └── BotAutomationApiController.php
│   ├── Resources/BotAutomation/   # 3 Resources
│   │   ├── BotAutomationResource.php
│   │   ├── BotTemplateResource.php
│   │   └── AiBotProfileResource.php
│   └── Requests/BotAutomation/    # 2 Form Requests
│       ├── StoreBotAutomationRequest.php
│       └── UpdateBotAutomationRequest.php
│
├── Policies/BotAutomation/
│   └── BotAutomationPolicy.php
│
└── Console/Commands/
    └── ProcessBotAutomations.php

database/
├── migrations/                     # 14 Migrations
│   └── 2025_11_08_000001_*.php
└── seeders/                        # 2 Seeders
    ├── BotPlatformSeeder.php
    └── BotMarketplaceCategorySeeder.php

routes/
├── web.php                        # Web routes
├── api.php                        # API routes
├── admin.php                      # Admin routes
└── bot_automation.php             # Bot automation routes
```

---

## การใช้งาน API

### Authentication

```bash
# Login และรับ token
curl -X POST http://your-domain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# ใช้ token ในการเรียก API
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://your-domain.com/api/v1/bot-automation/automations
```

### สร้าง Automation

```bash
POST /api/v1/bot-automation/automations
Authorization: Bearer YOUR_TOKEN

{
  "name": "Daily Facebook Post",
  "automation_type": "scheduled_post",
  "trigger_type": "schedule",
  "schedule_type": "daily",
  "content_source": "ai_generated",
  "ai_generation_prompt": "สร้างโพสต์เกี่ยวกับสุขภาพและความงาม",
  "ai_bot_profile_id": 1,
  "target_platforms": [1, 2],
  "is_active": true
}
```

### ดูรายการ Automation

```bash
GET /api/v1/bot-automation/automations
Authorization: Bearer YOUR_TOKEN
```

### Execute Automation ทันที

```bash
POST /api/v1/bot-automation/automations/{id}/execute
Authorization: Bearer YOUR_TOKEN
```

### ดู Statistics

```bash
GET /api/v1/bot-automation/automations/{id}/statistics
Authorization: Bearer YOUR_TOKEN
```

### ค้นหาบอทใน Marketplace

```bash
GET /api/v1/bot-automation/marketplace?category=1&search=marketing
Authorization: Bearer YOUR_TOKEN
```

---

## การสร้างบอทใหม่

### วิธีที่ 1: ใช้ Web Interface

1. ไปที่ **Admin → Bot Automation → Create**
2. กรอกข้อมูล:
   - ชื่อ automation
   - ประเภท (scheduled_post, support, sales)
   - เลือกแหล่งคอนเทนต์ (custom, template, AI)
   - เลือกแพลตฟอร์มเป้าหมาย
3. กำหนดเวลา (daily, weekly, cron)
4. กด Save

### วิธีที่ 2: ใช้ Code

```php
use App\Services\BotAutomation\BotAutomationService;

$service = app(BotAutomationService::class);

$automation = $service->createAutomation([
    'user_id' => auth()->id(),
    'name' => 'Daily Facebook Post',
    'automation_type' => 'scheduled_post',
    'trigger_type' => 'schedule',
    'schedule_type' => 'daily',
    'schedule_config' => [
        'hour' => 9,
        'minute' => 0,
        'days' => ['monday', 'wednesday', 'friday'],
    ],
    'content_source' => 'ai_generated',
    'ai_generation_prompt' => 'สร้างโพสต์ด้านการตลาด',
    'ai_bot_profile_id' => 1,
    'target_platforms' => [1, 2], // Facebook, Instagram
    'is_active' => true,
]);
```

### การใช้ Template

```php
use App\Models\BotAutomation\BotContentTemplate;

$template = BotContentTemplate::create([
    'user_id' => auth()->id(),
    'name' => 'Daily Motivational Quote',
    'content' => 'วันนี้วันที่ {{date}}\n\n💪 {{quote}}\n\n#motivation #{{day}}',
    'hashtags' => ['motivation', 'quotes', 'inspiration'],
    'variables' => ['date', 'quote', 'day'],
    'is_public' => true,
]);

// ใช้ template
$automation = $service->createAutomation([
    'content_source' => 'template',
    'template_id' => $template->id,
    // ... other fields
]);
```

---

## การเชื่อมต่อแพลตฟอร์ม

### Facebook

1. สร้าง Facebook App ที่ https://developers.facebook.com
2. เพิ่ม permissions: `pages_manage_posts`, `pages_read_engagement`
3. รับ Page Access Token
4. บันทึกการเชื่อมต่อ:

```php
BotPlatformConnection::create([
    'user_id' => auth()->id(),
    'platform_id' => 1, // Facebook
    'account_name' => 'My Page',
    'account_id' => 'PAGE_ID',
    'access_token' => 'PAGE_ACCESS_TOKEN',
    'is_active' => true,
]);
```

### Instagram

1. ต้องมี Instagram Business Account
2. เชื่อมต่อกับ Facebook Page
3. ใช้ Graph API เหมือน Facebook
4. บันทึกการเชื่อมต่อพร้อม Instagram Business Account ID

### LINE Official Account

1. สร้าง LINE Official Account
2. สร้าง Messaging API channel
3. รับ Channel Access Token
4. บันทึกการเชื่อมต่อ:

```php
BotPlatformConnection::create([
    'user_id' => auth()->id(),
    'platform_id' => 3, // LINE
    'account_name' => 'My LINE OA',
    'access_token' => 'CHANNEL_ACCESS_TOKEN',
    'credentials' => [
        'channel_secret' => 'CHANNEL_SECRET',
    ],
    'is_active' => true,
]);
```

### Twitter (X)

1. สร้าง Twitter App
2. Enable OAuth 2.0
3. รับ Access Token และ Refresh Token
4. บันทึกการเชื่อมต่อ

---

## Advanced Features

### AI Content Generation

```php
use App\Services\BotAutomation\BotContentGenerationService;

$service = app(BotContentGenerationService::class);

$content = $service->generateContent($automation);
// Returns: ['text' => '...', 'hashtags' => [...], 'source' => 'ai_generated']

// สร้างสำหรับแพลตฟอร์มเฉพาะ
$facebookContent = $service->generateForPlatform($automation, 'facebook');
$twitterContent = $service->generateForPlatform($automation, 'twitter'); // จำกัด 280 ตัวอักษร
```

### Customer Support Bot

```php
use App\Services\BotAutomation\BotSupportService;

$supportService = app(BotSupportService::class);

// สร้าง conversation
$conversation = $supportService->createConversation([
    'automation_id' => $automationId,
    'customer_id' => $customerId,
    'subject' => 'ต้องการความช่วยเหลือ',
    'priority' => 'medium',
    'channel' => 'line',
]);

// จัดการข้อความ
$response = $supportService->handleMessage(
    $conversation,
    'สินค้าที่สั่งไปเมื่อไหร่จะถึง?',
    $customerId
);

// AI จะตอบอัตโนมัติ หรือ flag สำหรับคน
if ($response['requires_human']) {
    // แจ้งเตือน agent
}
```

### Sales Assistant Bot

```php
use App\Services\BotAutomation\BotSalesService;

$salesService = app(BotSalesService::class);

// สร้าง sales conversation
$conversation = $salesService->createConversation([
    'automation_id' => $automationId,
    'customer_id' => $customerId,
    'channel' => 'facebook',
]);

// AI จะวิเคราะห์ intent และ sentiment
$response = $salesService->handleMessage(
    $conversation,
    'อยากได้ครีมบำรุงผิว แนะนำหน่อย',
    $customerId
);

// Response จะมี product recommendations
print_r($response['products']); // Array of recommended products
print_r($response['next_action']); // 'show_products', 'show_checkout', etc.
```

### Bot Rental System

```php
use App\Services\BotAutomation\BotRentalService;

$rentalService = app(BotRentalService::class);

// Subscribe to bot
$subscription = $rentalService->subscribe($user, $listing, [
    'billing_cycle' => 'monthly',
    'auto_renew' => true,
]);

// Check subscription status
if ($subscription->isActive()) {
    // User can use the bot
}

// Track usage (for per-use billing)
$rentalService->trackUsage($subscription);

// Cancel subscription
$rentalService->cancel($subscription, 'ไม่ต้องการใช้แล้ว');
```

### Analytics

```php
use App\Services\BotAutomation\BotAnalyticsService;

$analytics = app(BotAnalyticsService::class);

// Overview analytics
$overview = $analytics->getOverview(auth()->id());

// Platform comparison
$platforms = $analytics->getPlatformAnalytics(auth()->id());

// Engagement trends (last 30 days)
$trends = $analytics->getEngagementTrends(auth()->id(), 30);

// Export to CSV
$csv = $analytics->exportToCSV(auth()->id(), [
    'platform' => 1,
    'date_from' => '2025-01-01',
    'date_to' => '2025-01-31',
]);
```

---

## Troubleshooting

### ปัญหา: บอทไม่ทำงานอัตโนมัติ

**วิธีแก้:**
1. ตรวจสอบ cron job: `crontab -l`
2. ตรวจสอบ queue worker: `php artisan queue:work --tries=3`
3. ดู logs: `tail -f storage/logs/laravel.log`

### ปัญหา: โพสต์ไม่ขึ้น

**วิธีแก้:**
1. ตรวจสอบ platform connection:
```php
$connection = BotPlatformConnection::find($id);
if (!$connection->isValid()) {
    // Token หมดอายุ หรือ connection ไม่ active
}
```

2. ตรวจสอบ error logs:
```php
$post = BotScheduledPost::find($id);
echo $post->error_message;
```

3. Test connection manually:
```php
$service = app(BotPlatformService::class);
$result = $service->publishPost($post);
print_r($result);
```

### ปัญหา: AI ไม่ตอบ

**วิธีแก้:**
1. ตรวจสอบ AI bot profile configuration
2. ตรวจสอบ API key ของ AI provider
3. ดู AI usage logs:
```php
$logs = AiUsageLog::where('bot_profile_id', $botProfileId)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### ปัญหา: Subscription ไม่ renew

**วิธีแก้:**
1. Run manual renewal:
```php
$rentalService = app(BotRentalService::class);
$results = $rentalService->processDueRenewals();
```

2. ตรวจสอบ payment gateway logs

---

## Performance Optimization

### 1. Caching

```php
// Cache platform list
Cache::remember('bot_platforms', 3600, function () {
    return BotSocialPlatform::active()->get();
});

// Cache analytics
Cache::remember("bot_analytics_{$userId}", 300, function () use ($userId) {
    return app(BotAnalyticsService::class)->getOverview($userId);
});
```

### 2. Database Indexing

Indexes ที่สำคัญ:
- `bot_automations(user_id, is_active, next_execution_at)`
- `bot_scheduled_posts(automation_id, status, scheduled_for)`
- `bot_platform_connections(user_id, platform_id, is_active)`

### 3. Queue Priority

```php
// High priority for immediate posts
PublishScheduledPostJob::dispatch($post)->onQueue('high');

// Normal priority for analytics
ProcessAnalyticsJob::dispatch()->onQueue('default');
```

---

## Security Best Practices

1. **Encrypt sensitive data**
   - Access tokens are encrypted in database
   - Use `credentials` field with `encrypted` cast

2. **Rate limiting**
   - API rate limits per user
   - Platform rate limits per connection

3. **Authorization**
   - Use policies for access control
   - Check ownership before operations

4. **Input validation**
   - Use Form Requests
   - Sanitize user content

---

## Support & Documentation

- 📚 Full Documentation: `/docs/bot-automation`
- 🐛 Report Issues: GitHub Issues
- 💬 Community: Discord/Slack
- 📧 Email Support: support@example.com

---

**Created by**: Thaiprompt-Affiliate Bot Automation Team
**Version**: 1.0.0
**Last Updated**: 2025-11-08
