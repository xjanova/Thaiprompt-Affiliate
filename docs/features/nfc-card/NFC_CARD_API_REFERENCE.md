# 📡 NFC Card Payment API Reference

> Complete API documentation for NFC Card Payment System integration

[![Version](https://img.shields.io/badge/API%20Version-v1-blue.svg)](https://github.com/xjanova/Thaiprompt-Affiliate)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-green.svg)](https://github.com/xjanova/Thaiprompt-Affiliate)

---

## Table of Contents

- [Introduction](#introduction)
- [Authentication](#authentication)
- [Rate Limiting](#rate-limiting)
- [Endpoints](#endpoints)
  - [Cards](#cards)
  - [Readers](#readers)
  - [Transactions](#transactions)
- [Webhooks](#webhooks)
- [SDKs](#sdks)
- [Code Examples](#code-examples)
- [Testing](#testing)

---

## Introduction

### Base URLs

| Environment | URL | Description |
|------------|-----|-------------|
| Production | `https://api.yourdomain.com/v1` | Live production environment |
| Staging | `https://staging-api.yourdomain.com/v1` | Testing environment |
| Development | `http://localhost:8000/api/v1` | Local development |

### Request Format

- **Content-Type:** `application/json`
- **Accept:** `application/json`
- **Charset:** `UTF-8`

### Response Format

All responses follow this structure:

```json
{
    "success": true|false,
    "data": {...}|[...],
    "error": "Error message (if failed)",
    "meta": {
        "timestamp": "2025-11-13T10:30:00Z",
        "version": "1.0.0"
    }
}
```

---

## Authentication

### Bearer Token Authentication

All API requests require a valid Sanctum Bearer token.

#### Obtaining a Token

```http
POST /api/v1/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123xyz789...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        }
    }
}
```

#### Using the Token

```http
GET /api/v1/nfc/cards
Authorization: Bearer 1|abc123xyz789...
Accept: application/json
```

### Token Security

- ✅ Tokens expire after 1 hour of inactivity
- ✅ Use HTTPS only in production
- ✅ Store tokens securely (not in localStorage)
- ✅ Refresh tokens before expiry

---

## Rate Limiting

### Limits

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| Read Operations | 100 req/min | Per user |
| Write Operations | 30 req/min | Per user |
| Payment Operations | 10 req/min | Per card |
| Verification | 20 req/min | Per IP |

### Rate Limit Headers

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699876800
```

### Exceeded Response

```json
{
    "success": false,
    "error": "Too many requests. Please try again later.",
    "retry_after": 60
}
```

---

## Endpoints

### Cards

#### List User's Cards

```http
GET /api/v1/nfc/cards
```

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `status` | string | all | Filter by status: `active`, `inactive`, `blocked` |
| `card_type` | string | all | Filter by type: `standard`, `premium`, `vip` |
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Items per page (max 100) |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "card_number_masked": "1234********5678",
            "card_name": "My Primary Card",
            "card_type": "premium",
            "card_type_label": "พรีเมียม",
            "balance": 1250.00,
            "credit_limit": 5000.00,
            "status": "active",
            "status_label": "ใช้งานได้",
            "is_active": true,
            "is_paired": true,
            "paired_at": "2025-01-01T00:00:00Z",
            "activated_at": "2025-01-01T00:00:00Z",
            "expires_at": "2030-01-01T00:00:00Z",
            "last_used_at": "2025-11-13T10:30:00Z",
            "created_at": "2025-01-01T00:00:00Z",
            "updated_at": "2025-11-13T10:30:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 1,
        "total_pages": 1
    }
}
```

---

#### Get Card Details

```http
GET /api/v1/nfc/cards/{cardId}
```

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cardId` | integer | ✅ | Card ID |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1,
        "card_number_masked": "1234********5678",
        "card_name": "My Primary Card",
        "card_type": "premium",
        "card_type_label": "พรีเมียม",
        "balance": 1250.00,
        "credit_limit": 5000.00,
        "status": "active",
        "status_label": "ใช้งานได้",
        "is_active": true,
        "is_paired": true,
        "paired_at": "2025-01-01T00:00:00Z",
        "activated_at": "2025-01-01T00:00:00Z",
        "expires_at": "2030-01-01T00:00:00Z",
        "last_used_at": "2025-11-13T10:30:00Z",
        "statistics": {
            "total_transactions": 156,
            "completed_transactions": 154,
            "total_spent": 12450.00,
            "total_topped_up": 15000.00,
            "current_balance": 1250.00,
            "last_transaction": {
                "transaction_id": "NFCABC123",
                "type": "payment",
                "amount": 150.00,
                "created_at": "2025-11-13T10:30:00Z"
            }
        }
    }
}
```

**Error Responses:**

- `404 Not Found` - Card not found
- `403 Forbidden` - Card doesn't belong to user

---

#### Verify Card

```http
POST /api/v1/nfc/cards/verify
```

**Request Body:**
```json
{
    "card_number": "1234567890123456",
    "encrypted_data": "base64_encoded_encrypted_data_here"
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `card_number` | string | ✅ | 16 digits |
| `encrypted_data` | string | ✅ | Base64 encoded |

**Response:** `200 OK`
```json
{
    "success": true,
    "verified": true,
    "data": {
        "card_id": 1,
        "card_number_masked": "1234********5678",
        "balance": 1250.00,
        "card_type": "premium",
        "status": "active",
        "is_active": true,
        "expires_at": "2030-01-01T00:00:00Z"
    }
}
```

**Error Responses:**

- `400 Bad Request` - Invalid encrypted data
- `403 Forbidden` - Card doesn't belong to user
- `422 Unprocessable Entity` - Validation failed

```json
{
    "success": false,
    "verified": false,
    "error": "Invalid card signature - fake card detected",
    "details": {
        "reason": "signature_mismatch",
        "attempts_remaining": 2
    }
}
```

---

#### Process Payment

```http
POST /api/v1/nfc/cards/payment
```

**Request Body:**
```json
{
    "card_id": 1,
    "amount": 150.00,
    "reader_id": 5,
    "encrypted_data": "base64_encoded_encrypted_data_here",
    "metadata": {
        "order_id": "ORD-12345",
        "description": "Purchase at Shop A",
        "items": [
            {
                "name": "Product A",
                "quantity": 2,
                "price": 75.00
            }
        ]
    }
}
```

**Validation Rules:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `card_id` | integer | ✅ | Must exist and belong to user |
| `amount` | decimal | ✅ | Min: 0.01, Max: card balance |
| `reader_id` | integer | ❌ | Must be active reader |
| `encrypted_data` | string | ✅ | Base64 encoded |
| `metadata` | object | ❌ | Additional data |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "transaction_id": "NFCABC1234567890",
        "receipt_number": "RCP20251113001234",
        "amount": 150.00,
        "balance_before": 1250.00,
        "balance_after": 1100.00,
        "currency": "THB",
        "status": "completed",
        "type": "payment",
        "card": {
            "id": 1,
            "card_number_masked": "1234********5678"
        },
        "reader": {
            "id": 5,
            "name": "POS Terminal 1",
            "location": "Shop A"
        },
        "metadata": {
            "order_id": "ORD-12345",
            "description": "Purchase at Shop A"
        },
        "created_at": "2025-11-13T10:30:00Z",
        "completed_at": "2025-11-13T10:30:01Z"
    }
}
```

**Error Responses:**

- `400 Bad Request` - Payment processing failed
- `402 Payment Required` - Insufficient balance
- `403 Forbidden` - Card blocked or inactive
- `422 Unprocessable Entity` - Validation failed

```json
{
    "success": false,
    "error": "Insufficient card balance",
    "details": {
        "balance": 50.00,
        "required": 150.00,
        "shortfall": 100.00
    }
}
```

---

#### Get Card Transactions

```http
GET /api/v1/nfc/cards/{cardId}/transactions
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | 20 | Max: 100 |
| `offset` | integer | 0 | For pagination |
| `type` | string | all | `payment`, `topup`, `refund`, `transfer` |
| `status` | string | all | `completed`, `failed`, `pending` |
| `from_date` | date | - | Format: Y-m-d |
| `to_date` | date | - | Format: Y-m-d |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": [
        {
            "id": 1234,
            "transaction_id": "NFCABC1234567890",
            "receipt_number": "RCP20251113001234",
            "type": "payment",
            "type_label": "ชำระเงิน",
            "amount": 150.00,
            "balance_before": 1250.00,
            "balance_after": 1100.00,
            "currency": "THB",
            "status": "completed",
            "status_label": "สำเร็จ",
            "location": "Shop A",
            "reader_name": "POS Terminal 1",
            "description": "Purchase at Shop A",
            "metadata": {
                "order_id": "ORD-12345"
            },
            "created_at": "2025-11-13T10:30:00Z",
            "completed_at": "2025-11-13T10:30:01Z"
        }
    ],
    "meta": {
        "total": 156,
        "limit": 20,
        "offset": 0,
        "has_more": true
    }
}
```

---

#### Get Card Balance

```http
GET /api/v1/nfc/cards/{cardId}/balance
```

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "balance": 1100.00,
        "credit_limit": 5000.00,
        "available_credit": 5000.00,
        "currency": "THB",
        "last_transaction": {
            "amount": 150.00,
            "type": "payment",
            "timestamp": "2025-11-13T10:30:00Z"
        },
        "updated_at": "2025-11-13T10:30:01Z"
    }
}
```

---

### Readers

#### Get Nearby Readers

```http
GET /api/v1/nfc/readers/nearby
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `latitude` | decimal | - | User's latitude |
| `longitude` | decimal | - | User's longitude |
| `radius` | integer | 5000 | Search radius in meters |
| `status` | string | active | `active`, `all` |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "reader_id": "RDR-001",
            "name": "POS Terminal 1",
            "location": "Shop A, Counter 1",
            "status": "active",
            "is_online": true,
            "last_heartbeat": "2025-11-13T10:29:00Z",
            "coordinates": {
                "latitude": 13.7563,
                "longitude": 100.5018
            },
            "distance": 150.5,
            "distance_unit": "meters"
        }
    ]
}
```

---

### Transactions

#### Get Transaction Details

```http
GET /api/v1/nfc/transactions/{transactionId}
```

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "id": 1234,
        "transaction_id": "NFCABC1234567890",
        "receipt_number": "RCP20251113001234",
        "type": "payment",
        "amount": 150.00,
        "balance_before": 1250.00,
        "balance_after": 1100.00,
        "currency": "THB",
        "status": "completed",
        "card": {
            "id": 1,
            "card_number_masked": "1234********5678",
            "card_type": "premium"
        },
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "reader": {
            "id": 5,
            "name": "POS Terminal 1",
            "location": "Shop A"
        },
        "metadata": {
            "order_id": "ORD-12345"
        },
        "created_at": "2025-11-13T10:30:00Z",
        "processed_at": "2025-11-13T10:30:00Z",
        "completed_at": "2025-11-13T10:30:01Z"
    }
}
```

---

## Webhooks

### Setup

Configure webhook URL in Admin Panel:
```
Admin → Settings → NFC → Webhooks
```

### Webhook Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `card.paired` | Card paired with user | Card pairing |
| `card.blocked` | Card blocked | Security event |
| `transaction.completed` | Transaction completed | Successful payment |
| `transaction.failed` | Transaction failed | Failed payment |
| `reader.offline` | Reader went offline | Heartbeat timeout |

### Webhook Payload

```json
{
    "event": "transaction.completed",
    "timestamp": "2025-11-13T10:30:01Z",
    "data": {
        "transaction_id": "NFCABC1234567890",
        "card_id": 1,
        "amount": 150.00,
        "status": "completed"
    },
    "signature": "hmac_sha256_signature_here"
}
```

### Verifying Webhooks

```php
$signature = $request->header('X-Webhook-Signature');
$payload = $request->getContent();
$secret = config('nfc.webhook_secret');

$calculated = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $calculated)) {
    abort(401, 'Invalid signature');
}
```

---

## SDKs

### PHP SDK

```bash
composer require thaiprompt/nfc-card-sdk
```

```php
use Thaiprompt\NFCCard\Client;

$client = new Client([
    'base_uri' => 'https://api.yourdomain.com/v1',
    'token' => 'your-bearer-token',
]);

// Get cards
$cards = $client->cards()->list();

// Verify card
$result = $client->cards()->verify(
    cardNumber: '1234567890123456',
    encryptedData: $encryptedData
);

// Process payment
$transaction = $client->cards()->payment([
    'card_id' => 1,
    'amount' => 150.00,
    'encrypted_data' => $encryptedData,
]);
```

### JavaScript SDK

```bash
npm install @thaiprompt/nfc-card-sdk
```

```javascript
import { NFCCardClient } from '@thaiprompt/nfc-card-sdk';

const client = new NFCCardClient({
    baseURL: 'https://api.yourdomain.com/v1',
    token: 'your-bearer-token',
});

// Get cards
const cards = await client.cards.list();

// Verify card
const result = await client.cards.verify({
    cardNumber: '1234567890123456',
    encryptedData: encryptedData,
});

// Process payment
const transaction = await client.cards.payment({
    cardId: 1,
    amount: 150.00,
    encryptedData: encryptedData,
});
```

---

## Code Examples

### Mobile App Integration

```kotlin
// Android Kotlin
class NFCCardReader(private val apiClient: APIClient) {

    suspend fun readAndVerifyCard(tag: Tag): Result<CardData> {
        // Read NFC tag
        val nfcData = readNFCTag(tag)

        // Verify with API
        return apiClient.verifyCard(
            cardNumber = nfcData.cardNumber,
            encryptedData = nfcData.encryptedData
        )
    }

    suspend fun processPayment(
        cardId: Int,
        amount: Double,
        encryptedData: String
    ): Result<Transaction> {
        return apiClient.processPayment(
            cardId = cardId,
            amount = amount,
            encryptedData = encryptedData
        )
    }
}
```

### POS Terminal Integration

```python
# Python
import nfc
from thaiprompt_nfc import NFCCardClient

client = NFCCardClient(
    base_url='https://api.yourdomain.com/v1',
    token='your-bearer-token'
)

def on_card_detected(tag):
    """Called when NFC card is detected"""

    # Read card data
    card_data = read_card_data(tag)

    # Verify card
    result = client.verify_card(
        card_number=card_data['card_number'],
        encrypted_data=card_data['encrypted_data']
    )

    if result['verified']:
        print(f"Card verified: {result['data']['balance']} THB")

        # Process payment
        amount = float(input("Enter amount: "))
        transaction = client.process_payment(
            card_id=result['data']['card_id'],
            amount=amount,
            encrypted_data=card_data['encrypted_data']
        )

        print(f"Payment successful: {transaction['receipt_number']}")
    else:
        print(f"Card verification failed: {result['error']}")

# Start NFC reader
clf = nfc.ContactlessFrontend('usb')
clf.connect(rdwr={'on-connect': on_card_detected})
```

---

## Testing

### Sandbox Environment

```
Base URL: https://sandbox-api.yourdomain.com/v1
```

### Test Cards

| Card Number | Balance | Status | Behavior |
|------------|---------|--------|----------|
| `4111111111111111` | ฿1000 | Active | Always succeeds |
| `4222222222222222` | ฿50 | Active | Insufficient balance |
| `4333333333333333` | ฿1000 | Blocked | Always blocked |
| `4444444444444444` | ฿1000 | Active | Network timeout (slow) |
| `4555555555555555` | ฿1000 | Active | Random failures |

### Test Credentials

```json
{
    "email": "test@example.com",
    "password": "test123"
}
```

### Postman Collection

Download: [NFC-Card-API.postman_collection.json](https://api.yourdomain.com/docs/postman)

---

## Changelog

### v1.0.0 (2025-11-13)

- ✨ Initial release
- ✅ Card management endpoints
- ✅ Payment processing
- ✅ Card verification
- ✅ Transaction history
- ✅ Reader management

---

<div align="center">

**[⬆ Back to Top](#-nfc-card-payment-api-reference)**

Made with ❤️ by Claude AI

</div>
