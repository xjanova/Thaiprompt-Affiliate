# Mobile App API Documentation

This document describes the REST API endpoints available for mobile app development.

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication

The API uses Laravel Sanctum for authentication. After login, you'll receive a token that must be included in the Authorization header for protected endpoints.

```
Authorization: Bearer {your-token-here}
```

## Endpoints

### Public Endpoints

#### 1. Login
```http
POST /login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "user",
            "permissions": ["view_dashboard", "manage_affiliates"]
        },
        "token": "1|abcdef123456..."
    }
}
```

#### 2. Get App Settings
```http
GET /settings
```

**Response:**
```json
{
    "success": true,
    "data": {
        "branding": {
            "logo": "https://your-domain.com/uploads/branding/logo.png",
            "favicon": "https://your-domain.com/uploads/branding/favicon.png"
        },
        "theme": {
            "primary": {
                "start": "#3B82F6",
                "end": "#1D4ED8"
            },
            "secondary": {
                "start": "#10B981",
                "end": "#059669"
            },
            "accent": {
                "start": "#8B5CF6",
                "end": "#6D28D9"
            }
        }
    }
}
```

### Protected Endpoints

#### 3. Get Current User
```http
GET /me
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "user",
        "permissions": ["view_dashboard"]
    }
}
```

#### 4. Logout
```http
POST /logout
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

#### 5. Get Dashboard Statistics
```http
GET /dashboard/statistics
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_earnings": 15000.00,
        "pending_earnings": 2500.00,
        "total_referrals": 25,
        "recent_commissions": [
            {
                "id": 1,
                "amount": 500.00,
                "status": "approved",
                "created_at": "2024-01-15T10:30:00.000000Z"
            }
        ]
    }
}
```

#### 6. Get Commissions
```http
GET /dashboard/commissions?page=1
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "amount": 500.00,
                "status": "approved",
                "description": "Commission from referral",
                "created_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 50
    }
}
```

#### 7. Get Referrals
```http
GET /dashboard/referrals
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "referrals": [
            {
                "id": 1,
                "referral_code": "ABC123",
                "user": {
                    "name": "Jane Doe",
                    "email": "jane@example.com"
                },
                "created_at": "2024-01-10T08:00:00.000000Z"
            }
        ],
        "referral_link": "https://your-domain.com/register?ref=ABC123"
    }
}
```

## Error Responses

### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### 500 Server Error
```json
{
    "success": false,
    "message": "Internal server error"
}
```

## Mobile App Development Guide

### Recommended Tech Stack
- **React Native**: Cross-platform (iOS & Android)
- **Flutter**: Alternative cross-platform solution
- **Expo**: For faster React Native development

### Key Features to Implement

1. **Authentication**
   - Login screen
   - Token storage (secure storage)
   - Auto-refresh on app launch

2. **Dashboard**
   - Display earnings statistics
   - Recent commissions list
   - Pull-to-refresh

3. **Referrals**
   - Display referral link
   - Share functionality
   - Referral list

4. **Profile**
   - User information
   - Settings
   - Logout

5. **Theme Support**
   - Load theme colors from API
   - Apply gradient backgrounds
   - Support light/dark mode

### Security Considerations

1. Store API token securely using:
   - iOS: Keychain
   - Android: EncryptedSharedPreferences

2. Implement certificate pinning for production

3. Use HTTPS only

4. Implement biometric authentication option

### Sample React Native Code

```javascript
// API Service
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_BASE_URL = 'https://your-domain.com/api/v1';

export const login = async (email, password) => {
  const response = await fetch(`${API_BASE_URL}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  const data = await response.json();

  if (data.success) {
    await AsyncStorage.setItem('token', data.data.token);
    return data.data;
  }

  throw new Error(data.message);
};

export const getDashboardStats = async () => {
  const token = await AsyncStorage.getItem('token');

  const response = await fetch(`${API_BASE_URL}/dashboard/statistics`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });

  return response.json();
};
```

## Testing

Use tools like Postman or curl to test the API:

```bash
# Login
curl -X POST https://your-domain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Get Dashboard Stats
curl https://your-domain.com/api/v1/dashboard/statistics \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Support

For issues or questions, please contact the development team.
