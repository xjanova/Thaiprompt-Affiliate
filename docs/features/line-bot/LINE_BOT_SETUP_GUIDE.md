# 📱 LINE Bot Integration Setup Guide

> **Complete Setup Guide for LINE Official Account Bot Integration with Thaiprompt Affiliate**
>
> **Version: 1.0** | Last Updated: 2025-11-17 | Framework: Laravel 11 + LINE Messaging API

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Prerequisites](#prerequisites)
3. [LINE Official Account Setup](#line-official-account-setup)
4. [Configuration](#configuration)
5. [Seeder Setup](#seeder-setup)
6. [Bot Features](#bot-features)
7. [Signup Flow Customization](#signup-flow-customization)
8. [Testing Guide](#testing-guide)
9. [Troubleshooting](#troubleshooting)
10. [API Reference](#api-reference)

---

## Quick Start

ตัวเร็ว! ถ้าคุณรีบ ทำตามขั้นตอนนี้:

```bash
# 1. Setup LINE Credentials ใน .env
LINE_LOGIN_CHANNEL_ID=your_channel_id
LINE_CHANNEL_SECRET=your_channel_secret
LINE_REDIRECT_URI=https://yourdomain.com/auth/line/callback
LINE_MESSAGING_CHANNEL_ID=your_messaging_channel_id
LINE_CHANNEL_ACCESS_TOKEN=your_channel_access_token

# 2. Run Migrations & Seeders
php artisan migrate:fresh --seed

# 3. Configure LINE OA Settings ที่ Admin Panel
# ไปที่ /admin/line-oa/

# 4. Test Webhook
# ไปที่ /admin/line-oa/webhook-test/

# เสร็จ! 🎉
```

---

## Prerequisites

### Requirements

- ✅ PHP 8.1+ with Laravel 11
- ✅ MySQL 8.0+ or MariaDB 10.3+
- ✅ Redis (optional but recommended for caching)
- ✅ LINE Official Account
- ✅ LINE Channel (Login + Messaging API)
- ✅ Domain with HTTPS (required for LINE webhooks)

### LINE Channels to Create

คุณต้องสร้าง **2 LINE Channels**:

| Channel | Purpose | Use For |
|---------|---------|---------|
| **LINE Login Channel** | OAuth Authentication | User login/signup |
| **LINE Messaging API Channel** | Bot messaging | Sending messages to users |

---

## LINE Official Account Setup

### Step 1: Create LINE Official Account

1. ไปที่ https://account.line.biz/
2. สมัครสมาชิก และสร้าง Official Account
3. ยืนยันอีเมล
4. ตั้งค่า Account Display Name (ชื่อจะแสดงบน LINE)

### Step 2: Create LINE Login Channel

1. ไปที่ https://developers.line.biz/
2. เข้า Management Console
3. สร้าง Provider (ชื่อ company)
4. สร้าง Channel:
   - **Channel type**: Line Login
   - **Display name**: Thaiprompt Affiliate Login
   - **Description**: Login system for affiliate platform
5. ดึง credentials:
   - **Channel ID** (copy ไปใส่ `LINE_LOGIN_CHANNEL_ID`)
   - **Channel Secret** (copy ไปใส่ `LINE_CHANNEL_SECRET`)

### Step 3: Configure LINE Login Callback

1. In Management Console → Line Login Channel → Edit
2. **Redirect URI** section:
   - Add: `https://yourdomain.com/auth/line/callback`
   - Save ✅

### Step 4: Create LINE Messaging API Channel

1. Management Console → Create new Channel
2. **Channel type**: Messaging API
3. **Channel name**: Thaiprompt Affiliate Bot
4. **Category**: E-commerce / Marketing
5. ดึง credentials:
   - **Channel ID** (copy ไปใส่ `LINE_MESSAGING_CHANNEL_ID`)
   - **Channel Access Token** (copy ไปใส่ `LINE_CHANNEL_ACCESS_TOKEN`)
   - **Channel Secret** (copy ไปใส่ `LINE_CHANNEL_SECRET` - ใช้ได้ร่วมกับ Login Channel)

### Step 5: Configure Messaging API Webhook

1. Management Console → Messaging API Channel → Edit
2. **Webhook URL**:
   - URL: `https://yourdomain.com/api/webhook/line`
   - Status: ✅ Enabled
3. **Auto-reply**: Disable (ใช้ AI bot ตรงนี้แทน)
4. Test webhook: ✅ (Green light = Success)

### Step 6: Create Rich Menu (Optional)

1. Management Console → Rich Menu Editor
2. สร้าง menu เมื่อผู้ใช้เปิด chat:
   ```
   [🚀 สมัครสมาชิก] [💰 ดูรายได้] [📖 เรียนรู้]
   ```
3. Link to: `https://yourdomain.com/line/signup` หรือ postback

---

## Configuration

### 1. Environment Configuration (.env)

```bash
# LINE Login Channel (for OAuth)
LINE_LOGIN_CHANNEL_ID=1234567890
LINE_CHANNEL_SECRET=abcdef1234567890abcdef1234567890
LINE_REDIRECT_URI=https://yourdomain.com/auth/line/callback

# LINE Messaging API Channel (for Bot)
LINE_MESSAGING_CHANNEL_ID=9876543210
LINE_CHANNEL_ACCESS_TOKEN=Channel_access_token_here_very_long_string
LINE_LIFF_ID=1234567890-abcdef12  # Optional: สำหรับ LIFF apps

# Optional: LINE Bot Settings
LINE_BOT_ENABLED=true
LINE_BOT_AUTO_REPLY=false
LINE_WEBHOOK_VERIFY=true
```

### 2. Database Configuration

Run migrations to create tables:

```bash
php artisan migrate
```

**Tables created:**
- `line_oa_settings` - Configuration
- `line_login_logs` - Login audit trail
- `line_signup_flows` - Signup conversation steps
- `line_signup_sessions` - Active signup sessions
- `line_signup_templates` - Message templates
- `line_flex_message_templates` - Flex message cards
- `kyc_verifications` - KYC documents
- `ai_bot_profiles` - Bot configurations
- และอื่นๆ

### 3. Seeder Setup

สร้าง demo data:

```bash
# Run all seeders (recommended for first time)
php artisan migrate:fresh --seed

# Or run individual seeders:
php artisan db:seed --class=LineOaSettingSeeder
php artisan db:seed --class=LineSignupFlowSeeder
php artisan db:seed --class=LineBotAiSeeder
php artisan db:seed --class=KycVerificationSeeder
php artisan db:seed --class=LineSignupSessionSeeder
```

**Seeders created:**

| Seeder | Description | Records |
|--------|-------------|---------|
| `LineOaSettingSeeder` | LINE OA configuration | 1 |
| `LineSignupFlowSeeder` | Signup conversation steps | 9 steps |
| `LineBotAiSeeder` | AI bot profiles | 3 bots |
| `KycVerificationSeeder` | KYC demo data | 3 records (pending, approved, rejected) |
| `LineSignupSessionSeeder` | Demo signup sessions | 3 sessions (new, in-progress, completed) |

### 4. Admin Panel Configuration

ไปที่ `/admin/line-oa/` เพื่อตั้งค่า:

1. **Enable LINE Integration**: Toggle on
2. **Welcome Message**: ข้อความต้อนรับเมื่อ follow
3. **Registration Success Message**: ข้อความยืนยันหลังสมัคร
4. **Test Webhook**: Click "Test" → ควรเป็น Green ✅

---

## Bot Features

### 1. LINE Signup Flow (AI-Powered)

**ขั้นตอนการสมัครสมาชิก:**

```
1. ยินดีต้อนรับ (welcome)
   ↓
2. เบอร์โทรศัพท์ (phone) - with validation
   ↓
3. อีเมล (email) - with AI checking
   ↓
4. ชื่อ-นามสกุล (full_name) - with AI validation
   ↓
5. ที่อยู่ (address)
   ↓
6. ยินยอมข้อมูล (consent) - with condition routing
   ↓
7. สรุปข้อมูล (completion) - with edit option
   ↓
8. ✅ สมัครสำเร็จ (success)
```

**ตัวอย่าง:**

```
User: /start

Bot: 👋 สวัสดี! ยินดีต้อนรับ...
     [🚀 เริ่มต้น] [❓ ถามรายละเอียด]

User: เริ่มต้น

Bot: 📱 ขอเบอร์โทรศัพท์ของคุณด้วยครับ

User: 0891234567

Bot: ✅ เบอร์โทรบันทึกแล้ว!
     📧 ขอที่อยู่อีเมลของคุณด้วยครับ

... (continue until completion)
```

### 2. AI Bot Chat

**3 Demo Bots:**

| Bot | Purpose | System Prompt |
|-----|---------|---------------|
| 💰 **Affiliate Assistant** | ตอบคำถาม Affiliate | เน้นรายได้ และวิธีสมัคร |
| 💬 **Support Helper** | ช่วยเหลือ troubleshoot | ใจเย็น เป็นมืออาชีพ |
| 🛍️ **Sales Advisor** | แนะนำสินค้า | ตั้งใจขาย ไม่บังคับ |

**Activate Bot:**

1. ไปที่ `/admin/line-bot/ai/`
2. Select a bot → Click "Activate"
3. ตั้งค่า LINE Messaging API credentials (ถ้ายังไม่ได้ตั้ง)

### 3. KYC Verification (via Images)

ผู้ใช้ส่งรูป ID card + Selfie:

```
User: kyc  (or ยืนยันตัวตน)

Bot: 📸 กรุณาส่งรูปบัตรประชาชนของคุณ

User: [sends image]

Bot: ✅ บันทึกรูปบัตรแล้ว
     📸 กรุณาส่งรูปถ่ายตัวเองด้วย

User: [sends selfie]

Bot: ⏳ กำลังประมวลผล... โปรดรอสักครู่

Bot: ✅ บันทึก KYC สำเร็จ!
     👨‍⚖️ แอดมินจะตรวจสอบในไม่ช้า
```

**Admin Review:**

ไปที่ `/admin/kyc-verification/` เพื่อ:
- ✅ Approve KYC
- ❌ Reject with reason
- 📝 View extracted data (from OCR)

### 4. Commands

Users can type commands in LINE chat:

| Command | Thai | Function |
|---------|------|----------|
| `info` | `ข้อมูล` | Show user profile card |
| `kyc` | `ยืนยันตัวตน` | Start KYC verification |
| `reset` | `รีเซ็ต` | Reset/resume signup |

---

## Signup Flow Customization

### Modify Signup Steps

คุณสามารถแก้ไขขั้นตอนได้ 2 วิธี:

#### วิธีที่ 1: Admin Panel (Recommended)

1. ไปที่ `/admin/line-signup-flow/`
2. Click on step ที่ต้องแก้ไข
3. แก้ไข:
   - Message text
   - Input type (phone, email, name, text, button, etc.)
   - Validation rules
   - Next step logic
   - Quick reply options
   - AI requirements
4. Save ✅

#### วิธีที่ 2: Database (Advanced)

Edit `line_signup_flows` table directly:

```php
LineSignupFlow::where('step_key', 'phone')
    ->update([
        'message_text' => 'ใหม่ข้อความ...',
        'validation_rules' => [
            'required' => true,
            'regex' => '/^0[0-9]{9}$/',
        ],
    ]);
```

### Add New Step

```php
// Via Seeder
LineSignupFlow::create([
    'name' => 'ขั้นตอนใหม่: บัตรเครดิต',
    'step_key' => 'credit_card',
    'step_order' => 6, // ระบุลำดับ
    'message_text' => '💳 ขอเลขบัตรเครดิต...',
    'input_type' => 'text',
    'validation_rules' => [
        'required' => true,
        'regex' => '/^[0-9]{16}$/',
    ],
    'next_step_key' => 'address',
    'is_active' => true,
    'require_ai' => false,
]);
```

### Conditional Routing

ส่งผู้ใช้ไปยังขั้นตอนต่างกันตาม condition:

```php
LineSignupFlow::create([
    'step_key' => 'package_selection',
    'message_text' => '📦 เลือกแพคเกจสมาชิก...',
    'next_step_key' => 'bronze', // default
    'conditional_next_steps' => [
        [
            'field' => 'selected_package',
            'operator' => '==',
            'value' => 'gold',
            'next_step_key' => 'gold_special',
        ],
        [
            'field' => 'selected_package',
            'operator' => '==',
            'value' => 'premium',
            'next_step_key' => 'premium_special',
        ],
    ],
]);
```

---

## Testing Guide

### 1. Test LINE Login

```bash
# 1. Open browser
https://yourdomain.com/login

# 2. Click "Login with LINE"
# 3. Authorize in LINE app
# 4. Should redirect to dashboard ✅
```

### 2. Test LINE Signup

```bash
# 1. Create MLM Prospect with invitation token
$ php artisan tinker
>>> $prospect = MlmProspect::factory()->create();

# 2. Open invitation link
https://yourdomain.com/line/signup/{prospect->referral_token}

# 3. Click "Authorization" → authorize in LINE
# 4. Should start signup conversation in LINE ✅

# 5. Follow signup steps in LINE chat
```

### 3. Test Webhook

```bash
# 1. Admin Panel
https://yourdomain.com/admin/line-oa/

# 2. Click "Test Webhook"
# 3. Should see green checkmark ✅

# 4. Or test via curl
curl -X POST https://yourdomain.com/api/webhook/line \
  -H "X-Line-Signature: $(echo -n '{body}' | openssl dgst -sha256 -mac HMAC -macopt key:'{secret}' -binary | base64)" \
  -H "Content-Type: application/json" \
  -d '{"events":[{"type":"follow","source":{"userId":"U1234567..."}}]}'
```

### 4. Test AI Bot

```bash
# 1. Add bot to LINE official account
https://line.me/R/bot/your_bot_id

# 2. Send message to bot
"สมัครสมาชิกต้องเงินเท่าไร?"

# 3. AI should respond ✅
```

### 5. Test KYC

```bash
# 1. In LINE chat
kyc

# 2. Send ID card image
[upload image]

# 3. Send selfie
[upload image]

# 4. Check /admin/kyc-verification/ ✅
```

### Test Data

**Demo Users** (from seeders):

| Email | Password | Status |
|-------|----------|--------|
| john@example.com | password | Pending KYC |
| jane@example.com | password | Approved KYC |
| bob@example.com | password | Rejected KYC |

**LINE IDs** (for testing):

```
Pending: U1234567890abcdef1234567890abcde
In Progress: U9876543210abcdef0987654321abcde
Completed: Uabcdef1234567890abcdef1234567890
```

---

## Troubleshooting

### ❌ "Invalid signature" Error

**Problem:** Webhook signature verification failed

**Solution:**
1. ตรวจสอบ `LINE_CHANNEL_SECRET` ใน .env
2. ต้องตรงกับ Channel Secret ใน LINE Management Console
3. Restart Laravel: `php artisan cache:clear`

### ❌ "LINE_MESSAGING_CHANNEL_ID not configured"

**Problem:** Bot can't send messages

**Solution:**
1. ตั้งค่า `LINE_MESSAGING_CHANNEL_ID` ใน .env
2. ตั้งค่า `LINE_CHANNEL_ACCESS_TOKEN` ใน .env
3. Update in `/admin/line-oa/`

### ❌ Webhook Test Shows Red

**Problem:** Webhook URL is unreachable

**Solution:**
1. ตรวจสอบ domain มี HTTPS ✅
2. Firewall ไม่บล็อก LINE requests
3. Webhook URL ถูกต้อง: `https://yourdomain.com/api/webhook/line`
4. `LINE_CHANNEL_SECRET` ถูกต้อง

### ❌ Signup Flow Not Working

**Problem:** Users can't proceed with signup

**Solution:**
1. ตรวจสอบ `line_signup_flows` table มีข้อมูล
2. Run: `php artisan db:seed --class=LineSignupFlowSeeder`
3. ตรวจสอบ logs: `storage/logs/laravel.log`

### ❌ Images Not Processing (KYC)

**Problem:** KYC image upload fails

**Solution:**
1. ตรวจสอบ `storage/` directory writable
2. Run: `php artisan storage:link`
3. ตรวจสอบ Google Cloud Vision API (ถ้าใช้)

### ❌ AI Bot Not Responding

**Problem:** Bot sends error message

**Solution:**
1. ตรวจสอบ AI Provider configured: `/admin/ai-providers/`
2. ตรวจสอบ API key valid
3. ตรวจสอบ bot is_active: `/admin/line-bot/ai/`
4. Check logs for AI errors

---

## API Reference

### LINE Webhook Events

**Handled Events:**

```php
// Follow Event - User added bot as friend
POST /api/webhook/line
{
    "events": [{
        "type": "follow",
        "source": {"userId": "U..."},
        "timestamp": 1234567890
    }]
}

// Message Event - User sent text/image
POST /api/webhook/line
{
    "events": [{
        "type": "message",
        "source": {"userId": "U..."},
        "message": {
            "type": "text",
            "text": "Hello"
        },
        "timestamp": 1234567890
    }]
}

// Postback Event - User clicked button
POST /api/webhook/line
{
    "events": [{
        "type": "postback",
        "source": {"userId": "U..."},
        "postbackData": "action=signup",
        "timestamp": 1234567890
    }]
}
```

### Sending Messages to LINE

```php
use App\Services\LineService;

$lineService = app(LineService::class);

// Send text message
$lineService->sendPushMessage(
    'U1234567890...',
    'Hello! 👋'
);

// Send flex message
$lineService->sendFlexMessage(
    'U1234567890...',
    [
        'type' => 'bubble',
        'body' => [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [...]
        ]
    ]
);

// Send with buttons
$lineService->sendQuickReplyMessage(
    'U1234567890...',
    'Select one:',
    [
        ['label' => 'Option 1', 'text' => 'option_1'],
        ['label' => 'Option 2', 'text' => 'option_2'],
    ]
);
```

### Managing Signup Flow

```php
use App\Models\LineSignupFlow;

// Get first step
$firstStep = LineSignupFlow::getFirstStep();

// Get specific step
$phoneStep = LineSignupFlow::getByStepKey('phone');

// Validate user input
$validation = $phoneStep->validateInput('0891234567');

// Get next step
$nextStep = $phoneStep->getNextStepFor(['phone' => '0891234567']);

// Get quick reply structure
$quickReply = $phoneStep->getQuickReplyStructure();
```

### Managing Bot Profiles

```php
use App\Models\AiBotProfile;

// Get active bot
$bot = AiBotProfile::where('is_active', true)->first();

// Send message via bot
$response = $bot->sendMessage('Hello', $user, $context);

// Update system prompt
$bot->update([
    'system_prompt' => 'New prompt here...'
]);
```

---

## Performance Optimization

### Caching

LINE settings are cached for 1 hour:

```php
// Clear cache when updating settings
LineOaSetting::clearCache();
```

### Database Indexes

Recommended indexes on large installations:

```sql
CREATE INDEX idx_line_user_id ON users(line_user_id);
CREATE INDEX idx_mlm_prospect_line_user_id ON mlm_prospects(line_user_id);
CREATE INDEX idx_line_signup_session_line_user_id ON line_signup_sessions(line_user_id);
```

### Token Management

Access tokens are encrypted and cached:

```php
// Tokens stored safely
$tokenService->storeAccessToken($user, $token, $expiresIn);

// Auto-refresh on expiry
$tokenService->getAccessToken($user);
```

---

## Production Checklist

- [ ] ✅ Set `APP_ENV=production` in .env
- [ ] ✅ Set `APP_DEBUG=false`
- [ ] ✅ Enable HTTPS (required for LINE)
- [ ] ✅ Configure LINE credentials in .env
- [ ] ✅ Run migrations: `php artisan migrate`
- [ ] ✅ Run seeders: `php artisan migrate:fresh --seed`
- [ ] ✅ Test webhook: Green ✅ in Admin Panel
- [ ] ✅ Configure LINE Rich Menu
- [ ] ✅ Test signup flow end-to-end
- [ ] ✅ Test KYC verification
- [ ] ✅ Set up error monitoring (e.g., Sentry)
- [ ] ✅ Configure email for notifications
- [ ] ✅ Set up database backups
- [ ] ✅ Monitor logs regularly

---

## Support & Resources

**Useful Links:**

- 📱 [LINE Developers](https://developers.line.biz/)
- 📚 [LINE Messaging API Docs](https://developers.line.biz/en/reference/messaging-api/)
- 🆚 [LINE Login Docs](https://developers.line.biz/en/reference/line-login/)
- 💬 [LINE Bot Designer](https://botdesigner.line.me/)
- 🐛 [Issue Tracker](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

**Community:**

- 👥 [LINE Developers Community](https://community.line.biz/)
- 💼 [Thaiprompt Affiliate Docs](https://docs.thaiprompt.com/)

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**Maintained By:** Development Team
**For:** Thaiprompt Affiliate Platform
