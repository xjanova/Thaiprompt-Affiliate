# ThaiPrompt Marketplace API Documentation

## Overview

This document provides comprehensive API documentation for the ThaiPrompt Multi-vendor Marketplace with MLM System.

### Base URL
- **Production:** `https://api.thaiprompt.com/api/v1`
- **Development:** `http://localhost:8000/api/v1`

### Authentication

The API uses **Laravel Sanctum** for authentication. To authenticate requests:

1. Register or login to obtain an API token
2. Include the token in the Authorization header:
   ```
   Authorization: Bearer {your_token}
   ```

### Response Format

All API responses follow this standard format:

```json
{
  "success": true|false,
  "data": {},
  "message": "Response message",
  "meta": {}
}
```

### Rate Limiting

- **Authenticated users:** 60 requests per minute
- **Guest users:** 20 requests per minute

---

## Authentication Endpoints

### Register User

**POST** `/register`

Register a new user account with optional referral code for MLM.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "0812345678",
  "referral_code": "SPONSOR123" // optional
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "referral_code": "JOHN123",
      "mlm_level": 1
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  },
  "message": "Registration successful"
}
```

### Login

**POST** `/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": { /* user object */ },
    "token": "2|abcdefghijklmnopqrstuvwxyz"
  }
}
```

### Logout

**POST** `/logout` (Requires authentication)

---

## Product Endpoints

### Get All Products

**GET** `/products`

Retrieve paginated list of products with optional filters.

**Query Parameters:**
- `search` (string): Search by product name
- `category` (integer): Filter by category ID
- `min_price` (number): Minimum price
- `max_price` (number): Maximum price
- `sort` (enum): `newest`, `popular`, `price_low`, `price_high`
- `page` (integer): Page number
- `per_page` (integer): Items per page (default: 20)

**Example Request:**
```
GET /products?search=iPhone&category=1&min_price=10000&max_price=50000&sort=price_low&page=1
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "iPhone 15 Pro",
      "slug": "iphone-15-pro",
      "price": 45000.00,
      "sale_price": 42000.00,
      "stock_quantity": 50,
      "featured_image": "https://...",
      "vendor": { /* vendor object */ },
      "category": { /* category object */ }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 20
  }
}
```

### Get Product by ID

**GET** `/products/{id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "iPhone 15 Pro",
    "description": "...",
    "price": 45000.00,
    "sale_price": 42000.00,
    "stock_quantity": 50,
    "reviews": [ /* review objects */ ],
    "related_products": [ /* product objects */ ]
  }
}
```

---

## Cart Endpoints

### Get Cart

**GET** `/cart` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "product": { /* product object */ },
        "quantity": 2,
        "subtotal": 84000.00
      }
    ],
    "total": 84000.00,
    "item_count": 1
  }
}
```

### Add to Cart

**POST** `/cart/add` (Requires authentication)

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

### Update Cart Item

**PUT** `/cart/{item_id}` (Requires authentication)

**Request Body:**
```json
{
  "quantity": 3
}
```

### Remove from Cart

**DELETE** `/cart/{item_id}` (Requires authentication)

### Clear Cart

**DELETE** `/cart` (Requires authentication)

---

## Order Endpoints

### Get Orders

**GET** `/orders` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20250124-001",
      "total_amount": 84000.00,
      "status": "processing",
      "payment_status": "paid",
      "items": [ /* order items */ ],
      "created_at": "2025-01-24T10:00:00Z"
    }
  ]
}
```

### Create Order

**POST** `/orders` (Requires authentication)

**Request Body:**
```json
{
  "payment_method": "promptpay",
  "shipping_address": "123 Main St",
  "shipping_city": "Bangkok",
  "shipping_state": "Bangkok",
  "shipping_postal_code": "10110",
  "notes": "Please deliver after 5 PM"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "order": { /* order object */ },
    "payment_url": "https://..." // For online payments
  },
  "message": "Order created successfully"
}
```

### Get Order Details

**GET** `/orders/{order_id}` (Requires authentication)

---

## MLM Endpoints

### Get MLM Statistics

**GET** `/mlm/stats` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "level": 3,
    "direct_referrals": 5,
    "total_team": 23,
    "team_sales": 150000.00,
    "total_commissions": 15000.00,
    "rank": "Silver"
  }
}
```

### Get Network Structure

**GET** `/mlm/network?depth=5` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "tree": {
      "user": { /* user object */ },
      "children": [
        {
          "user": { /* user object */ },
          "children": [ /* nested children */ ]
        }
      ]
    }
  }
}
```

### Get Commission History

**GET** `/mlm/commissions` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "amount": 500.00,
      "type": "level_commission",
      "status": "paid",
      "order_id": 123,
      "created_at": "2025-01-24T10:00:00Z"
    }
  ]
}
```

### Send Referral Invitation

**POST** `/mlm/invite` (Requires authentication)

**Request Body:**
```json
{
  "email": "friend@example.com",
  "message": "Join me on ThaiPrompt!"
}
```

---

## Wallet Endpoints

### Get Wallet Info

**GET** `/wallet` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "balance": 5000.00,
    "pending_balance": 500.00,
    "total_earned": 10000.00,
    "total_withdrawn": 5000.00
  }
}
```

### Get Transactions

**GET** `/wallet/transactions?page=1&limit=20` (Requires authentication)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "credit",
      "transaction_type": "commission",
      "amount": 500.00,
      "balance_after": 5500.00,
      "description": "Level 1 commission",
      "created_at": "2025-01-24T10:00:00Z"
    }
  ]
}
```

### Request Withdrawal

**POST** `/wallet/withdraw` (Requires authentication)

**Request Body:**
```json
{
  "amount": 1000.00,
  "method": "bank_transfer",
  "bank_name": "SCB",
  "account_number": "1234567890",
  "account_name": "John Doe"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "withdrawal_id": "WD-20250124-001",
    "amount": 1000.00,
    "fee": 20.00,
    "net_amount": 980.00,
    "status": "pending"
  },
  "message": "Withdrawal request submitted successfully"
}
```

---

## Vendor Endpoints

**(Requires vendor role)**

### Get Vendor Products

**GET** `/vendor/products` (Requires authentication + vendor role)

### Create Product

**POST** `/vendor/products` (Requires authentication + vendor role)

**Request Body (multipart/form-data):**
```
name: iPhone 15 Pro
description: Latest iPhone model...
price: 45000.00
sale_price: 42000.00
stock_quantity: 50
category_id: 1
featured_image: [file]
gallery_images[]: [file1], [file2]
```

### Update Product

**PUT** `/vendor/products/{product_id}` (Requires authentication + vendor role)

### Delete Product

**DELETE** `/vendor/products/{product_id}` (Requires authentication + vendor role)

### Get Sales Report

**GET** `/vendor/sales?start_date=2025-01-01&end_date=2025-01-31`

---

## Webhook Endpoints

### Stripe Webhook

**POST** `/webhooks/stripe`

Handles Stripe payment events. Must include valid Stripe signature header.

### PromptPay Webhook

**POST** `/webhooks/promptpay`

Handles PromptPay payment confirmations.

**Request Body:**
```json
{
  "transaction_id": "TX123456",
  "status": "success",
  "amount": 1000.00,
  "reference": "ORD-20250124-001",
  "signature": "..."
}
```

### LINE Webhook

**POST** `/webhooks/line`

Handles LINE Official Account webhook events.

### GitHub Webhook

**POST** `/webhooks/github`

Handles GitHub push events for auto-deployment.

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Authentication Error (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Authorization Error (403)
```json
{
  "success": false,
  "message": "This action is unauthorized"
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Rate Limiting

When rate limit is exceeded:

**Response (429):**
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

Headers include:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `Retry-After`: Seconds until reset

---

## Testing the API

### Using Postman

1. Import the OpenAPI specification (`storage/api-docs/openapi.yaml`)
2. Set up environment variables:
   - `base_url`: `http://localhost:8000/api/v1`
   - `token`: Your authentication token
3. Use the Bearer Token authentication type

### Using cURL

```bash
# Register
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123"}'

# Get Products
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {your_token}"

# Add to Cart
curl -X POST http://localhost:8000/api/v1/cart/add \
  -H "Authorization: Bearer {your_token}" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'
```

---

## Support

For API support, please contact:
- Email: support@thaiprompt.com
- Documentation: https://docs.thaiprompt.com
- GitHub: https://github.com/thaiprompt/marketplace

---

*Last Updated: January 24, 2025*
