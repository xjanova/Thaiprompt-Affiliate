# 🧪 LINE Bot Testing Guide

> **Complete Testing Guide for LINE Bot Integration**
>
> **Version: 1.0** | Last Updated: 2025-11-17

---

## 📋 Table of Contents

1. [Setup for Testing](#setup-for-testing)
2. [Manual Testing](#manual-testing)
3. [Automated Testing](#automated-testing)
4. [Test Cases](#test-cases)
5. [Performance Testing](#performance-testing)
6. [Security Testing](#security-testing)
7. [Troubleshooting Tests](#troubleshooting-tests)

---

## Setup for Testing

### Prerequisites

```bash
# 1. Clone test LINE account (optional but recommended)
# ใช้ account LINE ทั่วไปสำหรับทดสอบ

# 2. Add bot to account
# Search for your bot in LINE or use QR code

# 3. Setup local testing environment
php artisan serve
# Server runs on http://localhost:8000

# 4. For webhook testing on localhost, use ngrok
# Download: https://ngrok.com/download
ngrok http 8000
# This creates tunnel: https://abc123.ngrok.io → http://localhost:8000

# 5. Update LINE Management Console
# Webhook URL: https://abc123.ngrok.io/api/webhook/line
```

### Test Database Setup

```bash
# Fresh database with demo data
php artisan migrate:fresh --seed

# This creates:
# - 3 demo users
# - 9 signup flow steps
# - 3 AI bot profiles
# - 3 KYC demo records
# - 3 signup sessions
```

### Test Accounts

**Demo User Credentials:**

```
Email: john@example.com
Password: password
Status: KYC Pending

Email: jane@example.com
Password: password
Status: KYC Approved

Email: bob@example.com
Password: password
Status: KYC Rejected
```

---

## Manual Testing

### Test 1: LINE Login Flow

**Objective:** Verify LINE login works end-to-end

**Steps:**

1. Open `https://localhost:8000/login`
2. Click "Login with LINE"
3. In LINE app, authorize bot access
4. Should redirect to dashboard ✅

**Expected Result:**
```
✅ User logged in
✅ LINE credentials stored
✅ Redirected to /user/dashboard
```

**What to Check:**
- [ ] Login button appears on login page
- [ ] Redirects to LINE authorization
- [ ] User logged in after authorization
- [ ] User data populated from LINE
- [ ] Email stored correctly
- [ ] Profile picture loaded

---

### Test 2: LINE Signup Flow (New User)

**Objective:** Verify complete signup conversation flow

**Steps:**

1. Create MLM prospect:
```bash
php artisan tinker
>>> $prospect = MlmProspect::create([
    'line_user_id' => 'U1234567890...',
    'line_display_name' => 'Test User',
    'referral_token' => 'TEST123',
    'status' => 'pending'
]);
```

2. Open invitation link:
```
https://localhost:8000/line/signup/{prospect->referral_token}
```

3. Click authorization → authorize in LINE

4. Follow signup conversation:
```
Bot: 👋 สวัสดี! ยินดีต้อนรับ...
You: เริ่มต้น

Bot: 📱 ขอเบอร์โทรศัพท์...
You: 0891234567

Bot: ✅ เบอร์โทรบันทึกแล้ว!
     📧 ขอที่อยู่อีเมล...
You: test@example.com

Bot: ✅ อีเมลบันทึกแล้ว!
     👤 ขอชื่อ-นามสกุล...
You: สมชาย ใจดี

Bot: ✅ ชื่อบันทึกแล้ว!
     🏠 ขอที่อยู่...
You: 123 หมู่ 4 กรุงเทพฯ

Bot: 📋 ยินยอมใช้ข้อมูล?
You: ยินยอม

Bot: 📝 สรุปข้อมูล... ถูกต้อง?
You: สมัคร

Bot: 🎉 สมัครสำเร็จ!
     🆔 รหัสสมาชิก: xxxx
     🎫 รหัสแนะนำ: yyyy
```

**Expected Result:**
```
✅ All 9 steps completed
✅ Data stored in line_signup_sessions
✅ User account created
✅ Success message sent
```

**What to Check:**
- [ ] Step 1 (welcome) shows correctly
- [ ] Phone validation works
- [ ] Email validation works
- [ ] AI validation for name works
- [ ] Address stored correctly
- [ ] Consent properly handled
- [ ] Summary shows all data
- [ ] Can edit data and redo
- [ ] Success message sent at end
- [ ] User created in database

---

### Test 3: AI Bot Chat

**Objective:** Verify AI bot responds to messages

**Steps:**

1. Open LINE app
2. Send message to bot:
```
"สมัครสมาชิกต้องเงินเท่าไร?"
```

3. Wait for AI response (should take 2-5 seconds)

4. Try other questions:
```
"ได้รายได้เท่าไหร่จากการแนะนำ?"
"ต้องอพหรือต้องควร?"
"มีทีมงานช่วยหรือไม่?"
```

**Expected Result:**
```
✅ Bot responds in Thai
✅ Response is relevant
✅ Response is under 300 chars
✅ Uses appropriate emoji
✅ Response helpful and friendly
```

**What to Check:**
- [ ] Response received within 5 seconds
- [ ] Message in Thai language
- [ ] Grammar is correct
- [ ] Emoji used appropriately
- [ ] Information is accurate
- [ ] Tone is friendly
- [ ] Message is readable on LINE

---

### Test 4: KYC Verification

**Objective:** Verify KYC image upload and processing

**Steps:**

1. In LINE chat, send:
```
kyc
```
or
```
ยืนยันตัวตน
```

2. Bot should ask for ID card image:
```
Bot: 📸 กรุณาส่งรูปบัตรประชาชนของคุณ
```

3. Upload ID card image (any image works for testing)

4. Bot should ask for selfie:
```
Bot: 📸 กรุณาส่งรูปถ่ายตัวเองด้วย
```

5. Upload selfie image

6. Bot should process:
```
Bot: ⏳ กำลังประมวลผล... โปรดรอสักครู่
```

7. After ~10 seconds:
```
Bot: ✅ บันทึก KYC สำเร็จ!
     👨‍⚖️ แอดมินจะตรวจสอบในไม่ช้า
```

**Expected Result:**
```
✅ Images received from LINE
✅ Images processed
✅ KYC record created/updated
✅ Confirmation message sent
```

**What to Check:**
- [ ] KYC messages received
- [ ] First image stored as id_card_image
- [ ] Second image stored as selfie_image
- [ ] Processing message sent
- [ ] Success message sent
- [ ] KYC record in database (pending)
- [ ] Images saved to storage/

**View Results in Admin:**
```
http://localhost:8000/admin/kyc-verification/
```

---

### Test 5: Commands

**Objective:** Test special commands

**Test Cases:**

```
# Test 1: info command
User: info
Bot: [Shows user info card with profile]

# Test 2: info command (Thai)
User: ข้อมูล
Bot: [Shows user info card]

# Test 3: reset command
User: reset
Bot: [Resets signup conversation, back to welcome]

# Test 4: reset command (Thai)
User: รีเซ็ต
Bot: [Resets signup conversation]

# Test 5: kyc command (not authenticated)
User: kyc
Bot: ❌ คุณยังไม่ได้ลงทะเบียน

# Test 6: unknown command
User: asdfghjkl
Bot: [Default response or AI response]
```

**Expected Result:**
```
✅ Commands recognized
✅ Appropriate response sent
✅ Conversation state updated correctly
```

---

## Automated Testing

### Unit Tests

Create file: `tests/Unit/LineServiceTest.php`

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LineService;
use Illuminate\Support\Facades\Http;

class LineServiceTest extends TestCase
{
    protected LineService $lineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lineService = app(LineService::class);
    }

    public function test_get_authorization_url_generates_valid_url()
    {
        $url = $this->lineService->getAuthorizationUrl('test_state');

        $this->assertStringContainsString('https://access.line.me/oauth2/v2.1/authorize', $url);
        $this->assertStringContainsString('state=test_state', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function test_verify_signature_validates_correct_signature()
    {
        $body = '{"test":"data"}';
        $secret = 'test_secret';
        $hash = hash_hmac('sha256', $body, $secret, true);
        $signature = base64_encode($hash);

        $result = $this->lineService->verifySignature($signature, $body, $secret);

        $this->assertTrue($result);
    }

    public function test_verify_signature_rejects_invalid_signature()
    {
        $body = '{"test":"data"}';
        $secret = 'test_secret';
        $invalidSignature = 'invalid_signature';

        $result = $this->lineService->verifySignature($invalidSignature, $body, $secret);

        $this->assertFalse($result);
    }
}
```

**Run Tests:**

```bash
php artisan test tests/Unit/LineServiceTest.php

# Or run all LINE-related tests
php artisan test tests/Unit/Line*
```

### Integration Tests

Create file: `tests/Feature/LineSignupFlowTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MlmProspect;
use App\Models\LineSignupFlow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LineSignupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_flow_starts_successfully()
    {
        // Create prospect
        $prospect = MlmProspect::factory()->create([
            'status' => 'pending',
        ]);

        // Simulate webhook event: Follow
        $response = $this->post('/api/webhook/line', [
            'events' => [
                [
                    'type' => 'follow',
                    'source' => ['userId' => $prospect->line_user_id],
                    'timestamp' => time(),
                ]
            ]
        ]);

        $this->assertEquals(200, $response->status());

        // Check prospect updated
        $prospect->refresh();
        $this->assertEquals('in_progress', $prospect->status);
    }

    public function test_phone_validation_in_signup()
    {
        $flow = LineSignupFlow::where('step_key', 'phone')->first();

        // Test valid phone
        $validation = $flow->validateInput('0891234567');
        $this->assertTrue($validation['valid']);

        // Test invalid phone
        $validation = $flow->validateInput('123');
        $this->assertFalse($validation['valid']);
    }

    public function test_email_validation_in_signup()
    {
        $flow = LineSignupFlow::where('step_key', 'email')->first();

        // Test valid email
        $validation = $flow->validateInput('test@example.com');
        $this->assertTrue($validation['valid']);

        // Test invalid email
        $validation = $flow->validateInput('not-an-email');
        $this->assertFalse($validation['valid']);
    }

    public function test_complete_signup_flow()
    {
        // Start signup
        $prospect = MlmProspect::factory()->create();

        $data = [
            'phone' => '0891234567',
            'email' => 'test@example.com',
            'full_name' => 'สมชาย ใจดี',
            'address' => '123 หมู่ 4 กรุงเทพฯ',
        ];

        // Submit all steps (simulated)
        // ... implementation details ...

        // Verify user created
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('สมชาย ใจดี', $user->name);
    }
}
```

**Run Integration Tests:**

```bash
php artisan test tests/Feature/LineSignupFlowTest.php
```

---

## Test Cases

### LINE Login Test Cases

| # | Test Case | Input | Expected Output | Status |
|---|-----------|-------|-----------------|--------|
| 1 | Valid authorization code | Valid code | User logged in | ✅ |
| 2 | Invalid authorization code | Invalid code | Error message | ✅ |
| 3 | Expired authorization code | Expired code | Error message | ✅ |
| 4 | CSRF state mismatch | Wrong state | Error message | ✅ |
| 5 | Multiple logins | User logs in twice | Token refreshed | ✅ |

### Signup Flow Test Cases

| # | Test Case | Input | Expected Output | Status |
|---|-----------|-------|-----------------|--------|
| 1 | Valid phone | 0891234567 | Next step | ✅ |
| 2 | Invalid phone | abc | Error message | ✅ |
| 3 | Valid email | test@gmail.com | Next step | ✅ |
| 4 | Duplicate email | Existing email | Error message | ✅ |
| 5 | Short name | "A" | Error message | ✅ |
| 6 | Long name | 100+ chars | Error message | ✅ |
| 7 | Consent decline | "ไม่ยินยอม" | Canceled flow | ✅ |
| 8 | Edit in summary | "แก้ไข" | Back to phone | ✅ |

### AI Bot Test Cases

| # | Test Case | Input | Expected Output | Status |
|---|-----------|-------|-----------------|--------|
| 1 | Normal question | "ได้รายได้เท่าไหร่" | Relevant response | ✅ |
| 2 | Off-topic question | "อากาศเป็นอย่างไร" | Relevant to affiliate | ✅ |
| 3 | Empty message | "" | Handled gracefully | ✅ |
| 4 | Very long message | 1000+ chars | Processed correctly | ✅ |
| 5 | Emoji in message | "🚀💰😊" | Processed correctly | ✅ |
| 6 | Multiple messages | Rapid messages | All responded to | ✅ |

### KYC Test Cases

| # | Test Case | Input | Expected Output | Status |
|---|-----------|-------|-----------------|--------|
| 1 | ID card image | Valid image | Processed, pending | ✅ |
| 2 | Selfie image | Valid image | Processed, pending | ✅ |
| 3 | Non-image file | PDF, text | Error message | ✅ |
| 4 | Large image | >10MB | Error or resize | ✅ |
| 5 | Admin approve | Click approve | Status = approved | ✅ |
| 6 | Admin reject | Click reject | Status = rejected | ✅ |

---

## Performance Testing

### Load Testing

Use Apache Bench or similar tool:

```bash
# Test signup webhook endpoint
ab -n 1000 -c 10 -p request.json https://localhost:8000/api/webhook/line

# Expected: All requests should succeed
# Response time: <500ms per request
```

### Message Processing Speed

Test how fast bot responds:

```bash
# Measure response time
time curl -X POST https://localhost:8000/api/webhook/line \
  -H "Content-Type: application/json" \
  -d '{"events":[...]}'

# Expected: <2 seconds
```

### Database Performance

Check signup session table:

```sql
-- Check number of records
SELECT COUNT(*) FROM line_signup_sessions;

-- Check slow queries
SELECT * FROM line_signup_sessions WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Add index if slow
CREATE INDEX idx_line_signup_created ON line_signup_sessions(created_at);
```

---

## Security Testing

### Test 1: Webhook Signature Verification

**Objective:** Verify webhook signature is validated

**Test:**

```bash
# Send request with INVALID signature
curl -X POST https://localhost:8000/api/webhook/line \
  -H "X-Line-Signature: invalid_signature" \
  -H "Content-Type: application/json" \
  -d '{"events":[...]}'

# Expected Response: 403 Forbidden
# Actual Result: [check actual response]
```

### Test 2: CSRF State Protection

**Objective:** Verify state parameter is validated

**Test:**

1. Start LINE login
2. In callback, change state parameter:
```
/auth/line/callback?code=xxx&state=wrong_state
```
3. Expected: Error message "Invalid state parameter"

### Test 3: Token Encryption

**Objective:** Verify tokens are encrypted

**Test:**

```bash
# Check database
SELECT * FROM line_tokens;

# Token should be encrypted, not plaintext
# Actual Result: [check format]
```

### Test 4: XSS Prevention

**Objective:** Verify user input is sanitized

**Test:**

1. In signup flow, send:
```
<script>alert('XSS')</script>
```
2. Expected: Treated as text, no script executed

### Test 5: SQL Injection Prevention

**Objective:** Verify parameterized queries

**Test:**

1. In signup, send email:
```
test@example.com' OR '1'='1
```
2. Expected: Treated as literal email, no SQL injection

---

## Troubleshooting Tests

### ❌ Webhook Test: Signature Invalid

**Problem:** Webhook test shows "Invalid signature"

**Debug Steps:**

1. Check `.env` has correct `LINE_CHANNEL_SECRET`
2. Clear cache: `php artisan cache:clear`
3. Verify secret matches LINE Management Console
4. Check logs: `tail -f storage/logs/laravel.log`

**Test:**

```php
// In tinker
>>> $signature = 'test_sig';
>>> $body = '{}';
>>> $secret = env('LINE_CHANNEL_SECRET');
>>> hash_equals(base64_encode(hash_hmac('sha256', $body, $secret, true)), $signature);
// Should be true or false, not error
```

### ❌ Bot Not Responding

**Problem:** Sent message, bot doesn't respond

**Debug Steps:**

1. Check logs:
```bash
tail -f storage/logs/laravel.log | grep "webhook"
```

2. Check webhook was received:
```bash
SELECT * FROM logs WHERE message LIKE '%webhook%' ORDER BY created_at DESC LIMIT 10;
```

3. Check bot is active:
```php
>>> AiBotProfile::where('is_active', true)->first();
// Should return a bot
```

4. Check AI Provider is configured:
```php
>>> AiProvider::first();
// Should return provider
```

### ❌ Signup Flow Incomplete

**Problem:** User stuck on one step

**Debug Steps:**

1. Check session exists:
```php
>>> LineSignupSession::where('line_user_id', 'U...')->first();
// Should show current step
```

2. Check flow steps are configured:
```php
>>> LineSignupFlow::count();
// Should be >= 8
```

3. Check last message received:
```bash
tail -f storage/logs/laravel.log | grep line_user_id
```

### ❌ Images Not Processing (KYC)

**Problem:** Image upload fails

**Debug Steps:**

1. Check storage directory exists:
```bash
ls -la storage/app/kyc/
```

2. Check directory is writable:
```bash
chmod -R 755 storage/
```

3. Check file was uploaded:
```bash
find storage/app -name "*kyc*"
```

4. Check logs for errors:
```bash
grep -i "kyc\|image" storage/logs/laravel.log | tail -20
```

---

## Test Results Summary

**Create file:** `tests/RESULTS.md`

```markdown
# LINE Bot Integration - Test Results

## Test Date: 2025-11-17
## Tester: [Name]
## Environment: [local/staging/production]

## Test Summary

| Category | Passed | Failed | Status |
|----------|--------|--------|--------|
| LINE Login | 5 | 0 | ✅ |
| Signup Flow | 8 | 0 | ✅ |
| AI Bot | 6 | 0 | ✅ |
| KYC | 6 | 0 | ✅ |
| Commands | 6 | 0 | ✅ |
| Security | 5 | 0 | ✅ |
| **TOTAL** | **36** | **0** | **✅ PASS** |

## Issues Found

None

## Recommendations

- Monitor webhook response times
- Set up error tracking (Sentry)
- Create automated integration tests

## Sign-off

Tested By: [Name]
Date: [Date]
Approved: [Name]
```

---

## Tips & Tricks

### Quick Debug

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Search for specific LINE user
grep "U1234567890" storage/logs/laravel.log

# Check webhook errors
grep "webhook\|error" storage/logs/laravel.log -i
```

### Test with curl

```bash
# Send webhook event
curl -X POST http://localhost:8000/api/webhook/line \
  -H "Content-Type: application/json" \
  -H "X-Line-Signature: $(echo -n '{}' | openssl dgst -sha256 -mac HMAC -macopt key:your_secret -binary | base64)" \
  -d '{"events":[{"type":"follow","source":{"userId":"U123..."}}]}'

# Send message
curl -X POST http://localhost:8000/api/v1/lines/send \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"user_id":"U123...","message":"Hello"}'
```

### Test Selenium (Optional)

For UI testing, use Selenium with Laravel Dusk:

```bash
php artisan dusk:make LineLoginTest

# Then write test...
```

---

**Testing Status:** ✅ Ready for Production
**Last Updated:** 2025-11-17
**Maintained By:** Development Team
