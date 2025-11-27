# 🤖 Hybrid Bot Keyword Management - Admin Guide

> **Complete Guide for Managing Keywords in the Hybrid Bot System**
>
> **Version: 1.0** | Last Updated: 2025-11-17 | Status: ✅ Production Ready

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Accessing the Admin Panel](#accessing-the-admin-panel)
3. [Keyword Management](#keyword-management)
4. [Analytics & Monitoring](#analytics--monitoring)
5. [Import/Export](#importexport)
6. [API Integration](#api-integration)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The **Hybrid Bot Keyword Management System** provides a complete interface for managing keywords in the Hybrid Bot system. Admins can:

- ✅ Create, read, update, and delete keywords
- ✅ Organize keywords by category and priority
- ✅ Test keywords in real-time
- ✅ Monitor keyword performance
- ✅ Export/import keywords in bulk
- ✅ Clone existing keywords
- ✅ Manage keyword status (active/inactive)

---

## Accessing the Admin Panel

### Web Interface

**URL:** `/admin/line-bot/keywords/`

### Navigation

In the admin dashboard:
1. Go to **LINE OA & AI** menu
2. Click **🤖 Hybrid Bot Keywords** (marked as NEW)
3. You'll see the keyword management dashboard

### Required Permissions

- Role: `admin`
- No additional permissions required

---

## Keyword Management

### Viewing Keywords

The main dashboard shows:

**Statistics Cards:**
- 📊 Total Keywords count
- ✅ Active Keywords count
- ❌ Inactive Keywords count
- 🎯 Average Priority
- 📈 Response Type Distribution

**Keywords Table:**
- Keyword name with description
- Trigger words (first 3 shown, +N if more)
- Category badge (FAQ, Support, Product, Custom)
- Priority level
- Active/Inactive status
- Action buttons (Edit/Delete)

**Search & Filter:**
- Search by keyword name
- Filter by category
- Real-time results

### Creating a Keyword

**Step 1: Click "สร้าง Keyword ใหม่" (Create New Keyword)**

**Step 2: Fill in Basic Information:**

| Field | Description | Required |
|-------|-------------|----------|
| Keyword Name | Unique identifier (e.g., "refund") | ✓ |
| Description | Optional explanation | ✗ |
| Category | FAQ, Support, Product, Custom | ✓ |
| Priority | 1-100 (higher = checked first) | ✓ |

**Step 3: Set Trigger Words:**

- Enter trigger words separated by commas
- Example: `refund, คืนเงิน, return, การคืนเงิน`
- Supports both English and Thai
- Case-insensitive matching

**Step 4: Choose Response Type:**

**Option A: Text Response (📝)**
- Simple text message
- Best for: FAQ, quick answers
- Supports emojis and line breaks

**Option B: Quick Reply (⚡)**
- Text + clickable buttons
- Best for: Multiple choice questions
- Example: "Did this help?" with Yes/No buttons

**Option C: Flex Message (🎨)**
- Rich formatted message (JSON)
- Best for: Complex layouts, product cards
- Requires JSON structure

**Step 5: Set Active Status**

- Toggle "เปิดใช้งาน" to enable/disable
- Inactive keywords won't be matched
- Useful for testing before activation

**Step 6: Save**

Click "สร้าง Keyword" to save.

### Editing a Keyword

1. Click the **"แก้ไข"** (Edit) button
2. Modify any fields
3. Click **"บันทึกการแก้ไข"** (Save Changes)

**Metadata shown:**
- Created timestamp
- Last edited timestamp
- Keyword ID

### Deleting a Keyword

1. Click the **"ลบ"** (Delete) button
2. Confirm deletion in the popup
3. Keyword is permanently removed

---

## Testing Keywords

### Real-time Keyword Testing

In the keyword list page, scroll to **"ทดสอบ Keyword"** section:

1. **Type a test message** (e.g., "refund ได้ไหม?")
2. **Click "ทดสอบ"** button
3. **See the result:**

**If Keyword Matches:**
```
✅ Found Keyword
├─ Keyword: refund
├─ Category: FAQ
├─ Priority: 50
├─ Response Type: text
├─ Trigger Words: [refund, คืนเงิน, return]
└─ Response: 💰 นโยบายการคืนเงิน...
```

**If No Match:**
```
ℹ️ No Keyword Found
└─ This message will be sent to AI provider
```

### API Test Endpoint

```bash
curl -X POST /api/v1/line-bot/keywords/test \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "refund"}'
```

---

## Analytics & Monitoring

**URL:** `/admin/line-bot/keywords/analytics/dashboard`

### Statistics Overview

**4 Key Metrics:**
- **Total Keywords** - All keywords in the system
- **Active Keywords** - Keywords currently enabled
- **Average Priority** - Mean priority across keywords
- **Response Types** - Distribution of text/flex/quick_reply

### Charts & Visualizations

**1. Keywords by Category (Pie Chart)**
- Shows distribution across FAQ, Support, Product, Custom
- Click for detailed view

**2. Response Types (Bar Chart)**
- Text vs Flex Message vs Quick Reply count
- Helps optimize response types

**3. Priority Distribution (Pie Chart)**
- Low (1-49), Medium (50-79), High (80-100)
- Ensure proper priority balance

### Keywords List with Clone

View all keywords with quick access to:
- Clone button (duplicate keywords)
- Status badge
- Quick reference table

---

## Import/Export

### Export Keywords

**Use Case:** Backup keywords, migration, sharing

**Steps:**
1. Go to Analytics page
2. Click **"Export JSON"** button
3. Browser downloads `keywords_export_YYYY-MM-DD_HH-MM-SS.json`

**JSON Structure:**
```json
[
  {
    "keyword": "refund",
    "description": "Refund policy",
    "trigger_words": ["refund", "คืนเงิน", "return"],
    "response_type": "text",
    "response_text": "💰 Refund Policy...",
    "category": "faq",
    "priority": 50,
    "is_active": true
  }
]
```

### Import Keywords

**Use Case:** Bulk import keywords, restore from backup

**Steps:**
1. Go to Analytics page
2. Click **"Choose File"** and select JSON file
3. **Option:** Toggle "ข้ามถ้า Keyword มีอยู่แล้ว" (skip existing)
4. Click **"Import JSON"** button

**Important Notes:**
- File must be valid JSON
- Maximum file size: 5MB
- Supports overwriting existing keywords
- Choose "skip existing" to avoid duplicates

### Cloning Keywords

**Use Case:** Create variants quickly

**Steps:**
1. Go to Analytics page
2. Click **"Clone"** button for any keyword
3. New keyword created with `_copy_TIMESTAMP` suffix
4. New keyword is **inactive by default**
5. Edit the cloned keyword and save

---

## API Integration

### Base URL
```
/api/v1/line-bot/keywords/
```

### Authentication
```
Authorization: Bearer {SANCTUM_TOKEN}
```

### Endpoints

#### 1. List Keywords
```bash
GET /api/v1/line-bot/keywords/
Query Params:
  - category: faq|support|product|custom
  - active: true|false
  - search: keyword name
  - sort: -priority (default), priority, keyword, -created_at
  - per_page: 15 (default)
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 50,
    "count": 15,
    "per_page": 15,
    "current_page": 1,
    "last_page": 4
  }
}
```

#### 2. Get Single Keyword
```bash
GET /api/v1/line-bot/keywords/{id}
```

#### 3. Create Keyword
```bash
POST /api/v1/line-bot/keywords/
Content-Type: application/json

{
  "keyword": "refund",
  "trigger_words": ["refund", "คืนเงิน"],
  "response_type": "text",
  "response_text": "...",
  "category": "faq",
  "priority": 50,
  "is_active": true
}
```

#### 4. Update Keyword
```bash
PUT /api/v1/line-bot/keywords/{id}
```

#### 5. Delete Keyword
```bash
DELETE /api/v1/line-bot/keywords/{id}
```

#### 6. Test Keyword
```bash
POST /api/v1/line-bot/keywords/test
Content-Type: application/json

{
  "message": "User's message to test"
}

Response:
{
  "success": true,
  "matched": true,
  "keyword": "refund",
  "response_type": "text",
  "response": { ... }
}
```

#### 7. Get Active Keywords
```bash
GET /api/v1/line-bot/keywords/active
```

#### 8. Get Statistics
```bash
GET /api/v1/line-bot/keywords/statistics

Response:
{
  "total": 30,
  "active": 25,
  "inactive": 5,
  "by_category": {
    "faq": 10,
    "support": 8,
    "product": 7,
    "custom": 5
  },
  "by_response_type": {
    "text": 25,
    "flex_message": 3,
    "quick_reply": 2
  }
}
```

---

## Best Practices

### 1. Keyword Naming

✅ **DO:**
- Use lowercase and underscores (e.g., `payment_issue`)
- Keep names short and descriptive
- Use English for the keyword name
- Make it meaningful (not random IDs)

❌ **DON'T:**
- Use spaces or special characters
- Make names too long
- Use numbers as primary identifiers
- Change keyword names frequently (breaks integrations)

### 2. Trigger Words

✅ **DO:**
- Include 3-5 variations per keyword
- Mix English and Thai
- Include common misspellings
- Add related terms and synonyms

❌ **DON'T:**
- Use very generic words (e.g., "a", "the", "is")
- Create overlapping triggers with other keywords
- Use inconsistent spacing or punctuation
- Ignore regional differences (Thai vs English)

### 3. Category Organization

| Category | Use Case | Examples |
|----------|----------|----------|
| **FAQ** | Frequently asked questions | How to, What is, Information |
| **Support** | Technical issues, help | Error, problem, not working |
| **Product** | Product info, features | Package, pricing, features |
| **Custom** | Everything else | Marketing, announcements |

### 4. Priority Management

**High Priority (80-100):**
- Urgent support issues
- Critical keywords
- Always check first

**Medium Priority (50-79):**
- Common questions
- General support
- Standard responses

**Low Priority (1-49):**
- Nice-to-have responses
- General information
- Fallback options

**Strategy:**
- Set priority based on importance
- Review conflicts with other keywords
- Avoid having all keywords at the same priority

### 5. Response Types

**Text (Best for):**
- Quick answers (< 200 chars)
- Simple FAQ responses
- Status messages

**Quick Reply (Best for):**
- Yes/No questions
- Feedback collection
- Menu selections

**Flex Message (Best for):**
- Product catalogs
- Complex information
- Rich formatting
- Images and buttons

---

## Troubleshooting

### Problem: Keyword Not Matching

**Symptoms:**
- User sends matching message but no response
- "No Keyword Found" message

**Solutions:**

1. **Check Keyword is Active**
   - Go to keyword edit page
   - Verify "เปิดใช้งาน" is checked

2. **Verify Trigger Words**
   - User's message must contain the exact trigger word
   - Case-insensitive matching applies
   - Full word match required (substring OK)

3. **Check Priority Conflict**
   - Another keyword may be matching first
   - Use test function to verify which keyword matches
   - Adjust priorities if needed

4. **Use Test Function**
   - Type exact user message in test box
   - Verify expected keyword is returned
   - If not, check trigger words

### Problem: Wrong Keyword Matching

**Symptoms:**
- Different keyword matches than expected
- Multiple keywords with similar triggers

**Solutions:**

1. **Check Trigger Word Overlap**
   - Example: If both "order" and "refund" keywords exist
   - And "refund my order" contains both
   - Higher priority keyword wins

2. **Adjust Priority**
   - More specific keywords should have higher priority
   - Generic keywords should have lower priority

3. **Remove Duplicate Triggers**
   - Each unique keyword should have unique trigger words
   - If overlap is necessary, prioritize accordingly

4. **Test Both Keywords**
   - Test each keyword individually
   - Verify matching logic is correct

### Problem: Keywords Not Importing

**Symptoms:**
- Import fails with error
- Partial import without notification

**Solutions:**

1. **Validate JSON Format**
   - Use online JSON validator
   - Check for proper escaping
   - Ensure valid array structure

2. **Check File Size**
   - Maximum 5MB
   - Large files may timeout

3. **Check Permissions**
   - User must have admin role
   - Required database write permissions

4. **Review Error Message**
   - System shows specific error
   - Check for duplicate keyword names
   - Verify all required fields present

### Problem: Analytics Not Showing

**Symptoms:**
- Empty charts
- 0 count for all keywords
- Slow loading

**Solutions:**

1. **Clear Cache**
   ```bash
   php artisan cache:clear
   ```

2. **Check Database**
   ```bash
   php artisan tinker
   >>> LineBotKeyword::count()  // Should return number
   ```

3. **Refresh Browser**
   - Hard refresh (Ctrl+F5 or Cmd+Shift+R)
   - Clear browser cache

---

## Advanced Features

### Bulk Operations (Coming Soon)

- Bulk status update (enable/disable multiple)
- Bulk delete with confirmation
- Bulk priority adjustment
- Bulk category change

### Keyword Groups (Coming Soon)

- Organize keywords into groups
- Manage groups separately
- Group-level permissions

### Usage Analytics (Coming Soon)

- Track keyword match frequency
- Monitor response times
- Analyze user satisfaction

---

## Support & Questions

**Documentation:**
- 📖 [LINE_BOT_HYBRID_MODE.md](LINE_BOT_HYBRID_MODE.md) - Technical details
- 🔧 [LINE_BOT_SETUP_GUIDE.md](LINE_BOT_SETUP_GUIDE.md) - Initial setup

**Contact:**
- 💬 Technical Support
- 📧 support@example.com

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**Status:** ✅ Complete & Ready
**Maintained By:** Development Team
