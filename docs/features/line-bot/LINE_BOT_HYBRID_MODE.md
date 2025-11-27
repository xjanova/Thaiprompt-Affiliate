# 🤖 LINE Bot Hybrid Mode - Complete Guide

> **Intelligent Bot Routing: Keywords + AI Fallback**
>
> **Version: 1.0** | Last Updated: 2025-11-17 | Status: ✅ Production Ready

---

## 📋 Table of Contents

1. [What is Hybrid Mode?](#what-is-hybrid-mode)
2. [How It Works](#how-it-works)
3. [Built-in Keywords](#built-in-keywords)
4. [Custom Keywords](#custom-keywords)
5. [Configuration](#configuration)
6. [Admin Panel](#admin-panel)
7. [Examples](#examples)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## What is Hybrid Mode?

**Hybrid Mode** เป็นระบบบอท LINE ที่อัจฉริยะ ที่สามารถ:

1. ✅ **ตรวจสอบ Keyword ก่อน** (Fast & Cheap)
   - ใช้บอทพื้นฐาน
   - ตอบทันที (no API delay)
   - ไม่มีค่าใช้จ่าย API

2. ❌ **ไม่มี Keyword?** → ส่งให้ AI (Smart & Comprehensive)
   - ใช้ AI provider ที่ตั้งค่าไว้ (ChatGPT, DeepSeek, Gemini, etc.)
   - ตอบข้อมูลประมาณในการตอบที่สำคัญ
   - Cost-effective (ลดการใช้ API)

### Benefits ✨

| ด้าน | Classic Bot | Hybrid Bot | ↑ Improvement |
|------|-------------|------------|---------------|
| **Speed** | 2-5 sec (AI) | <100ms (keyword) | ⚡ 50x faster |
| **Cost** | High ($$$) | Low ($) | 💰 90% cheaper |
| **FAQs** | Slow | Instant | ✅ Better UX |
| **Complex Q** | Limited | Smart AI | 🧠 Much better |
| **Scalability** | Limited | Unlimited | 📈 Grow freely |

---

## How It Works

### Message Flow Diagram

```
User sends message in LINE chat
       ↓
[1] Check if user is in signup flow?
    YES → Continue signup flow
    NO  → Go to next step
       ↓
[2] Check for keyword match?
    FOUND → Use Built-in Bot (instant response)
    NOT FOUND → Go to next step
       ↓
[3] Route to AI Provider
    Send to configured AI (ChatGPT, DeepSeek, etc.)
    Get AI response
    Send back to user
```

### Step-by-Step Example

```
📱 USER sends: "refund"
    ↓
🤖 HYBRID BOT checks:
    1. Is user in signup? NO
    2. Is "refund" a keyword? YES! ✓
    3. Get keyword response
    ↓
⚡ RESPONSE (instant):
   "💰 นโยบายการคืนเงิน...
    เรามีสิทธิ์คืนเงินได้ 7 วัน..."
```

```
📱 USER sends: "คุณแนะนำอะไรให้สำหรับผม?"
    ↓
🤖 HYBRID BOT checks:
    1. Is user in signup? NO
    2. Is this a keyword? NO
    3. Route to AI
    ↓
🧠 AI PROCESSES:
    - Analyzes question context
    - Considers user profile
    - Generates personalized response
    ↓
💬 RESPONSE (2-5 seconds):
   "บนความเข้าใจ... ฉันแนะนำให้
    คุณลองแพคเกจ Silver เนื่องจาก..."
```

---

## Built-in Keywords

### Default Keywords (Always Available)

| Keyword | Triggers | Response | Use Case |
|---------|----------|----------|----------|
| **info** | info, ข้อมูล, profile, โปรไฟล์ | Show user card | Check account info |
| **kyc** | kyc, ยืนยันตัวตน, verify, ยืนยัน | Start KYC flow | ID verification |
| **reset** | reset, รีเซ็ต, restart, เริ่มใหม่ | Reset signup | Restart signup |
| **help** | help, ช่วยเหลือ, guide, คู่มือ | Show commands | Get help |

### Example Commands

```
User types: "info"
Bot responds: Shows your profile card with:
  - Your name
  - Email
  - Phone
  - Status (KYC, Commission, etc.)

User types: "kyc"
Bot responds: Starts KYC verification process
  - "📸 Send your ID card image"
  - Later: "📸 Send your selfie"

User types: "help"
Bot responds: Shows all available commands
  - info / ข้อมูล
  - kyc / ยืนยันตัวตน
  - reset / รีเซ็ต
  - help / ช่วยเหลือ
  - (+ custom keywords)
```

---

## Custom Keywords

### Pre-seeded Keywords

6 useful keywords are seeded by default:

| Keyword | Trigger Words | Category | Use Case |
|---------|---------------|----------|----------|
| **refund** | refund, คืนเงิน, return | FAQ | Refund policy |
| **shipping** | shipping, จัดส่ง, delivery | FAQ | Delivery info |
| **payment_issue** | payment error, ชำระเงินไม่ได้ | Support | Payment help |
| **affiliate_package** | affiliate, package, แพค | Product | Package details |
| **account** | account, password, login | Support | Account issues |
| **commission** | commission, คอมมิชชั่น | FAQ | Commission info |

### Example Responses

**Keyword: "refund"**
Trigger: "Can I refund?" or "คืนเงินได้ไหม?"

Response:
```
💰 นโยบายการคืนเงิน

เรามีสิทธิ์คืนเงินได้ภายใน 7 วัน
✓ สินค้าอยู่ในสภาพเดิม
✓ ไม่ได้ใช้งาน
✓ ยังมีซองแพคเกจเดิม

⏰ ขั้นตอน:
1. ติดต่อเรา
2. ส่งรูปสินค้า
3. เตรียมคืนสินค้า
4. รับการคืนเงิน (3-5 วัน)
```

---

## Configuration

### 1. Enable Hybrid Mode

```bash
# In .env (if you add this feature):
HYBRID_BOT_ENABLED=true
HYBRID_BOT_USE_AI_FALLBACK=true
HYBRID_BOT_AI_PROVIDER=openai  # or deepseek, gemini, etc.
```

### 2. Select AI Fallback Provider

Go to `/admin/line-bot/ai/` and:
- Select which AI bot to use as fallback
- Make sure bot is marked as active
- Configure provider (OpenAI, DeepSeek, Gemini, etc.)

### 3. Configure Keywords

### Method 1: Via Admin Panel (Recommended)

```
Go to: /admin/line-bot/keywords/

1. Click "Add New Keyword"
2. Fill in:
   - Keyword name (e.g., "support")
   - Description
   - Trigger words (comma-separated)
   - Response type (text, flex_message, quick_reply)
   - Response text
3. Set priority (1-100, higher = check first)
4. Toggle is_active to enable
5. Save
```

### Method 2: Via Seeder

Edit `database/seeders/LineBotKeywordSeeder.php`:

```php
LineBotKeyword::create([
    'keyword' => 'my_keyword',
    'description' => 'What this keyword does',
    'trigger_words' => ['trigger1', 'trigger2', 'trigger3'],
    'response_type' => 'text',
    'response_text' => 'Your response here...',
    'category' => 'faq',  // faq, support, product, custom
    'priority' => 50,      // 1-100
    'is_active' => true,
]);
```

Then run:
```bash
php artisan db:seed --class=LineBotKeywordSeeder
```

### Method 3: Programmatically

```php
use App\Models\LineBotKeyword;

LineBotKeyword::create([
    'keyword' => 'urgent',
    'trigger_words' => ['urgent', 'ด่วน', 'asap'],
    'response_type' => 'text',
    'response_text' => '🚨 ขออภัย! ข้อความของคุณถูกมาร์กเป็น Urgent
    Support team จะติดต่อคุณในเร็วสุด
    📞 +66-XXX-XXXX',
    'priority' => 90,  // Check this first
    'is_active' => true,
]);
```

---

## Admin Panel

### Keywords Management Page

**Location:** `/admin/line-bot/keywords/`

#### Features Available:

1. **View All Keywords**
   - List of all active/inactive keywords
   - Filter by category
   - Sort by priority
   - Search by keyword name

2. **Add New Keyword**
   - Form to create custom keywords
   - Real-time trigger word testing
   - Preview response
   - Save to database

3. **Edit Existing Keyword**
   - Modify trigger words
   - Update response text
   - Change priority
   - Toggle active status

4. **Delete Keywords**
   - Remove custom keywords
   - Keep built-in keywords protected

5. **Test Keyword**
   - Type a message to test
   - See which keyword matches
   - View the response that would be sent

#### Column Descriptions:

| Column | Meaning | Example |
|--------|---------|---------|
| Keyword | Short name | "refund" |
| Category | Type (faq/support/product/custom) | "faq" |
| Triggers | Words that activate it | "refund, คืนเงิน, return" |
| Type | Response format | "text" |
| Priority | Check order (1-100) | "80" (check before 50) |
| Active | Is it enabled? | "✅ Yes" |
| Actions | Edit/Delete/Test | Buttons |

---

## Examples

### Example 1: Simple FAQ Keyword

**Keyword Setup:**
```
Name: warranty
Triggers: warranty, รับประกัน, guarantee
Response:
  "✅ รับประกันทั้งหมด
   - 1 ปี ฟรี
   - ครอบคลุมทั้งหมด
   - ไม่มีค่าใช้จ่าย

   ติดต่อ support@example.com"
```

**User Interaction:**
```
User: "how long is warranty?"
Bot:  [Matches "warranty" keyword]
      ✅ รับประกันทั้งหมด...
```

### Example 2: Support Keyword with Quick Replies

**Keyword Setup:**
```
Name: broken
Triggers: broken, เสีย, damaged, หัก
Response Type: quick_reply
Response Text: "ขออภัยที่สินค้าเสีย! ช่วยเรามั้ย?"
Quick Replies:
  - "📞 โทรหา Support"
  - "📧 ส่งอีเมล"
  - "💬 Chat"
```

### Example 3: Product Keyword with Flex Message

**Keyword Setup:**
```
Name: new_product
Triggers: new, สินค้าใหม่, what's new
Response Type: flex_message
Response (JSON):
{
  "type": "bubble",
  "body": {
    "type": "box",
    "contents": [
      {
        "type": "text",
        "text": "🎉 สินค้าใหม่!",
        "weight": "bold"
      },
      {
        "type": "text",
        "text": "ดูเลยที่ www.example.com/new"
      }
    ]
  }
}
```

---

## Best Practices

### 1. Keyword Organization

✅ **DO:**
```
- Use short, memorable keywords
- Include both English and Thai triggers
- Group related keywords together
- Set priority properly (urgent first)
```

❌ **DON'T:**
```
- Use very long keywords
- Mix too many trigger words
- Set all priorities the same
- Create duplicate keywords
```

### 2. Trigger Words

✅ **Good Trigger Words:**
```
- "refund" / "คืนเงิน" / "return"
- "kyc" / "ยืนยันตัวตน" / "verify"
- "shipping" / "จัดส่ง" / "delivery"
```

❌ **Avoid These:**
```
- "a", "the", "is" (too common)
- Very long phrases
- Misspellings that don't make sense
```

### 3. Response Text

✅ **Good Responses:**
```
- Clear and concise
- Use emojis for visual appeal
- Include contact info for escalation
- List options/steps clearly
```

❌ **Avoid:**
```
- Very long walls of text
- Too many technical jargon
- No clear next steps
- Missing contact information
```

### 4. Priority Management

```
Priority 90-100:  Urgent issues (broken, urgent)
Priority 70-89:   Support questions (account, password)
Priority 50-69:   FAQ & Info (refund, shipping)
Priority 1-49:    General keywords (features, reviews)
```

---

## Troubleshooting

### ❌ Keyword not matching

**Problem:** User types keyword but no response

**Solution:**
1. Check trigger words in admin panel
2. Verify keyword is active (is_active = true)
3. Check for typos in trigger words
4. Test exact text user typed
5. Try case-insensitive matching

**Debug:**
```php
// In tinker
>>> LineBotKeyword::where('keyword', 'refund')->first()
// Check trigger_words field
```

### ❌ Wrong keyword matching

**Problem:** Different keyword matches than expected

**Solution:**
1. Check priority of keywords
2. Higher priority keywords checked first
3. Adjust priority if needed
4. Remove conflicting trigger words

### ❌ AI fallback not working

**Problem:** When no keyword matches, AI not responding

**Solution:**
1. Check AI bot is active: `/admin/line-bot/ai/`
2. Check AI provider is configured
3. Verify API keys are valid
4. Check logs for AI errors
5. Try manually testing AI bot

### ❌ Keyword response not updating

**Problem:** Changed keyword but old response still showing

**Solution:**
1. Clear cache: `php artisan cache:clear`
2. Verify changes saved in database
3. Check for multiple keywords matching same trigger
4. Test in fresh browser/LINE window

---

## Advanced Configuration

### Conditional Responses

You can create keywords with different responses based on user status:

```php
// Check if user is registered
if (!$user) {
    $response = "คุณยังไม่ได้ลงทะเบียน";
} else {
    $response = "ยินดีต้อนรับกลับมา " . $user->name;
}
```

### Multi-language Support

Set trigger words in multiple languages:

```php
LineBotKeyword::create([
    'keyword' => 'support',
    'trigger_words' => [
        // English
        'help', 'support', 'issue', 'problem',
        // Thai
        'ช่วยเหลือ', 'ปัญหา', 'ด่วน',
        // Emoji
        '🆘', '⚠️'
    ],
]);
```

### Integration with Custom Services

```php
// In LineHybridBotService.php
case 'custom_handler':
    $myService = app(MyCustomService::class);
    $response = $myService->handle($user, $messageText);
    $lineService->sendPushMessage($lineUserId, $response);
    break;
```

---

## Monitoring & Analytics

### Track Keyword Usage

```php
// View keyword usage stats
LineBotKeyword::withCount('conversations')
    ->orderBy('conversations_count', 'desc')
    ->get();
```

### Monitor AI Fallback Usage

```php
// See how often we fallback to AI
LineMessage::where('type', 'ai_bot')
    ->count();  // Returns count
```

### Optimize Keywords

Based on usage:
- High-matching keywords: Keep and improve
- Low-matching keywords: Consider removing
- Add new keywords for common non-matching questions

---

## Migration & Backup

### Export Keywords

```php
// Export all keywords to JSON
$keywords = LineBotKeyword::all();
$json = $keywords->toJson();
// Save to file
```

### Import Keywords

```php
// Import from JSON
$data = json_decode(file_get_contents('keywords.json'), true);
foreach ($data as $keyword) {
    LineBotKeyword::create($keyword);
}
```

---

## Performance Tips

1. **Keep Keyword List Lean**
   - Remove unused keywords
   - Consolidate similar keywords
   - Archive old keywords

2. **Optimize Trigger Words**
   - Use specific trigger words
   - Avoid very common words
   - Test trigger combinations

3. **Monitor AI Costs**
   - Track API usage
   - Optimize keyword coverage
   - Balance keyword/AI ratio

4. **Cache Keywords**
   - Keywords are cached in memory
   - Clear cache when updating
   - Use cache expiration

---

## Support

**Questions about Hybrid Mode?**

- 📖 Read LINE_BOT_SETUP_GUIDE.md
- 📊 Check /admin/line-bot/keywords/
- 🧪 Use test function in admin panel
- 💬 Contact support@example.com

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**Status:** ✅ Complete & Ready
**Maintained By:** Development Team
