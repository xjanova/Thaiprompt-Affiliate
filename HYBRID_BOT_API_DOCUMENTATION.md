# 🔌 Hybrid Bot API Documentation

> **Complete REST API Reference for Keyword Management**
>
> **Base URL:** `/api/v1/line-bot/keywords/`
> **Authentication:** Bearer Token (Sanctum)
> **Content-Type:** application/json

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Endpoints](#endpoints)
3. [Request/Response Examples](#requestresponse-examples)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Examples](#examples)

---

## Authentication

### Token Generation

```bash
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "token": "YOUR_SANCTUM_TOKEN"
}
```

### Using Token

```bash
Authorization: Bearer YOUR_SANCTUM_TOKEN
Content-Type: application/json
```

---

## Endpoints

### 1. List Keywords

**Endpoint:** `GET /api/v1/line-bot/keywords/`

**Description:** Retrieve all keywords with optional filtering

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| category | string | Filter by category (faq, support, product, custom) | `?category=faq` |
| active | boolean | Filter by status | `?active=true` |
| search | string | Search by keyword name | `?search=refund` |
| sort | string | Sort by field (prefix with `-` for desc) | `?sort=-priority` |
| per_page | integer | Results per page | `?per_page=20` |
| page | integer | Page number | `?page=1` |

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/?category=faq&active=true&sort=-priority" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "refund",
      "description": "Refund policy",
      "trigger_words": ["refund", "คืนเงิน", "return"],
      "response_type": "text",
      "response_text": "💰 Refund policy...",
      "category": "faq",
      "priority": 50,
      "is_active": true,
      "times_matched": 125,
      "last_matched_at": "2025-11-17T10:30:00Z",
      "created_at": "2025-11-17T08:00:00Z",
      "updated_at": "2025-11-17T09:30:00Z"
    }
  ],
  "pagination": {
    "total": 30,
    "count": 15,
    "per_page": 15,
    "current_page": 1,
    "last_page": 2
  },
  "message": "Keywords ดึงข้อมูลสำเร็จ"
}
```

**Status Code:** `200 OK`

---

### 2. Get Single Keyword

**Endpoint:** `GET /api/v1/line-bot/keywords/{id}`

**Description:** Retrieve a specific keyword by ID

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Keyword ID |

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/1" \
  -H "Authorization: Bearer TOKEN"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "refund",
    "description": "Refund policy",
    "trigger_words": ["refund", "คืนเงิน"],
    "response_type": "text",
    "response_text": "💰 Refund policy...",
    "category": "faq",
    "priority": 50,
    "is_active": true,
    "times_matched": 125,
    "created_at": "2025-11-17T08:00:00Z"
  },
  "message": "ดึงข้อมูล Keyword สำเร็จ"
}
```

**Status Code:** `200 OK`

---

### 3. Create Keyword

**Endpoint:** `POST /api/v1/line-bot/keywords/`

**Description:** Create a new keyword

**Request Body:**

```json
{
  "keyword": "support",
  "description": "Customer support issues",
  "trigger_words": ["help", "support", "issue", "problem"],
  "response_type": "text",
  "response_text": "How can we help you?",
  "category": "support",
  "priority": 70,
  "is_active": true
}
```

**Validation Rules:**

| Field | Rule | Example |
|-------|------|---------|
| keyword | required, unique, max:100 | "support" |
| trigger_words | required, array, min:1 | ["help", "support"] |
| response_type | required, in:text,flex_message,quick_reply | "text" |
| response_text | required if text/quick_reply | "Help message" |
| category | required, in:faq,support,product,custom | "support" |
| priority | required, integer, 1-100 | 70 |

**Example Request:**

```bash
curl -X POST "http://localhost/api/v1/line-bot/keywords/" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "keyword": "support",
    "trigger_words": ["help", "support"],
    "response_type": "text",
    "response_text": "How can we help?",
    "category": "support",
    "priority": 70,
    "is_active": true
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "id": 31,
    "keyword": "support",
    "trigger_words": ["help", "support"],
    "response_type": "text",
    "response_text": "How can we help?",
    "category": "support",
    "priority": 70,
    "is_active": true,
    "created_at": "2025-11-17T11:00:00Z"
  },
  "message": "สร้าง Keyword 'support' สำเร็จ"
}
```

**Status Code:** `201 Created`

---

### 4. Update Keyword

**Endpoint:** `PUT /api/v1/line-bot/keywords/{id}`

**Description:** Update an existing keyword

**Request Body:**

```json
{
  "keyword": "support_updated",
  "trigger_words": ["help", "support", "assistance"],
  "response_text": "Updated help message",
  "priority": 80
}
```

**Example Request:**

```bash
curl -X PUT "http://localhost/api/v1/line-bot/keywords/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "keyword": "support_updated",
    "trigger_words": ["help", "support", "assistance"],
    "response_type": "text",
    "response_text": "Updated help message",
    "category": "support",
    "priority": 80,
    "is_active": true
  }'
```

**Status Code:** `200 OK`

---

### 5. Delete Keyword

**Endpoint:** `DELETE /api/v1/line-bot/keywords/{id}`

**Description:** Delete a keyword

**Example Request:**

```bash
curl -X DELETE "http://localhost/api/v1/line-bot/keywords/1" \
  -H "Authorization: Bearer TOKEN"
```

**Example Response:**

```json
{
  "success": true,
  "message": "ลบ Keyword 'support' สำเร็จ"
}
```

**Status Code:** `200 OK`

---

### 6. Test Keyword

**Endpoint:** `POST /api/v1/line-bot/keywords/test`

**Description:** Test if a message matches any keyword

**Request Body:**

```json
{
  "message": "Can I get a refund?"
}
```

**Example Request:**

```bash
curl -X POST "http://localhost/api/v1/line-bot/keywords/test" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Can I get a refund?"}'
```

**Response (Match Found):**

```json
{
  "success": true,
  "matched": true,
  "keyword": "refund",
  "category": "faq",
  "priority": 50,
  "response_type": "text",
  "response": {
    "type": "text",
    "text": "💰 Refund policy...",
    "flex_message": null,
    "quick_reply": null
  },
  "message": "พบ Keyword ที่ตรงกัน"
}
```

**Response (No Match):**

```json
{
  "success": true,
  "matched": false,
  "message": "ไม่พบ Keyword - จะใช้ AI Fallback"
}
```

**Status Code:** `200 OK`

---

### 7. Get Active Keywords

**Endpoint:** `GET /api/v1/line-bot/keywords/active`

**Description:** Get only active keywords (used by Hybrid Bot)

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/active" \
  -H "Authorization: Bearer TOKEN"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "refund",
      "trigger_words": ["refund", "คืนเงิน"],
      "response_type": "text",
      "response": {
        "type": "text",
        "text": "💰 Refund policy..."
      }
    }
  ],
  "count": 25,
  "message": "ดึง Active Keywords สำเร็จ"
}
```

**Status Code:** `200 OK`

---

### 8. Get Statistics

**Endpoint:** `GET /api/v1/line-bot/keywords/statistics`

**Description:** Get keyword statistics

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/statistics" \
  -H "Authorization: Bearer TOKEN"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
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
  },
  "message": "ดึงสถิติสำเร็จ"
}
```

**Status Code:** `200 OK`

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| VALIDATION_ERROR | 422 | Validation failed |
| NOT_FOUND | 404 | Keyword not found |
| UNAUTHORIZED | 401 | Invalid/missing token |
| FORBIDDEN | 403 | Insufficient permissions |
| CONFLICT | 409 | Duplicate keyword |
| SERVER_ERROR | 500 | Server error |

### Example Error Response

```json
{
  "success": false,
  "error": "The keyword field must be unique.",
  "code": "VALIDATION_ERROR"
}
```

---

## Rate Limiting

**Limits (Default):**
- 60 requests per minute per user
- 1000 requests per hour per user

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1637101200
```

---

## Examples

### Example 1: Create and Test Keyword

```bash
# 1. Create keyword
curl -X POST "http://localhost/api/v1/line-bot/keywords/" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "keyword": "booking",
    "trigger_words": ["book", "booking", "reserve"],
    "response_type": "text",
    "response_text": "Book at: https://example.com/booking",
    "category": "support",
    "priority": 60
  }'

# 2. Test the keyword
curl -X POST "http://localhost/api/v1/line-bot/keywords/test" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "I want to book a slot"}'
```

### Example 2: Filter Keywords by Category

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/?category=support&active=true&sort=-priority" \
  -H "Authorization: Bearer TOKEN"
```

### Example 3: Get Statistics and Analyze

```bash
curl -X GET "http://localhost/api/v1/line-bot/keywords/statistics" \
  -H "Authorization: Bearer TOKEN" \
  | jq '.data.by_category'
```

### Example 4: JavaScript/Node.js

```javascript
// List keywords
const response = await fetch('/api/v1/line-bot/keywords/', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
console.log(data.data);

// Test keyword
const testResponse = await fetch('/api/v1/line-bot/keywords/test', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    message: 'User message here'
  })
});

const testData = await testResponse.json();
if (testData.matched) {
  console.log('Keyword found:', testData.keyword);
}
```

### Example 5: Python

```python
import requests

token = "YOUR_TOKEN"
headers = {
    "Authorization": f"Bearer {token}",
    "Content-Type": "application/json"
}

# List keywords
response = requests.get(
    "http://localhost/api/v1/line-bot/keywords/",
    headers=headers
)
keywords = response.json()['data']

# Create keyword
new_keyword = {
    "keyword": "new_test",
    "trigger_words": ["test", "ทดสอบ"],
    "response_type": "text",
    "response_text": "Test response",
    "category": "custom",
    "priority": 50,
    "is_active": True
}

response = requests.post(
    "http://localhost/api/v1/line-bot/keywords/",
    headers=headers,
    json=new_keyword
)
```

---

## Webhook Integration

### Receiving Keyword Events

**Setup webhook endpoint in your application:**

```php
// routes/api.php
Route::post('/webhook/keywords', function (\Illuminate\Http\Request $request) {
    $event = $request->input('event');
    $keyword = $request->input('data');

    switch ($event) {
        case 'keyword.created':
            // Handle keyword creation
            break;
        case 'keyword.updated':
            // Handle keyword update
            break;
        case 'keyword.deleted':
            // Handle keyword deletion
            break;
    }

    return response()->json(['success' => true]);
});
```

---

**API Version:** 1.0
**Last Updated:** 2025-11-17
**Status:** ✅ Complete & Ready
