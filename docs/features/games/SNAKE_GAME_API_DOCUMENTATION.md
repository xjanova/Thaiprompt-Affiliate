# 🐍 Snake.io Game API Documentation (ภาษาไทย)

> **เอกสารคู่มือ API สำหรับเกม Snake.io**
> Version: 2.6.0 | Last Updated: 2025-01-15

---

## 📋 สารบัญ

1. [ภาพรวม](#ภาพรวม)
2. [Authentication](#authentication)
3. [Base URL](#base-url)
4. [Rate Limiting](#rate-limiting)
5. [API Endpoints](#api-endpoints)
   - [Game Management](#game-management)
   - [Player Actions](#player-actions)
   - [Wallet & Scoring](#wallet--scoring)
6. [Data Models](#data-models)
7. [Error Handling](#error-handling)
8. [Example Usage](#example-usage)

---

## ภาพรวม

Snake.io Game API เป็น REST API สำหรับจัดการเกม multiplayer แบบ real-time รองรับ:

- ✅ **Multiplayer Mode** - เล่นพร้อมกันได้สูงสุด 30 คน/ห้อง
- ✅ **Guest & Member** - รองรับทั้งผู้เล่นที่ล็อกอินและไม่ล็อกอิน
- ✅ **Anti-Cheat System** - ตรวจจับการโกง 5 ระดับ
- ✅ **Wallet Integration** - บันทึกคะแนนใช้แต้ม 1 แต้ม
- ✅ **Real-time Broadcasting** - ใช้ Laravel Reverb WebSocket

---

## Authentication

### สำหรับ Guest (ไม่ต้อง login)
```
ไม่ต้องส่ง headers พิเศษ
```

### สำหรับ Member (login แล้ว)
```http
Cookie: laravel_session=<your_session_token>
X-CSRF-TOKEN: <csrf_token>
```

**หมายเหตุ:** API endpoints บางตัวต้อง login (บังคับ authentication)

---

## Base URL

```
Production: https://yourdomain.com/api
Development: http://localhost:8000/api
```

**Prefix:** `/games/snake-io`

---

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| Public APIs | 60 requests | 1 minute |
| Sync APIs | 120 requests | 1 minute |
| Admin APIs | 100 requests | 1 minute |

**Response Headers:**
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 60
```

---

## API Endpoints

### Game Management

#### 1. เข้าร่วมเกม (Join Game)

**Endpoint:** `POST /games/snake-io/join`

**Authentication:** ❌ ไม่ต้อง (รองรับ Guest)

**Request Body:**
```json
{
  "player_name": "ชื่อผู้เล่น",
  "skin_slug": "classic"
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `player_name` | string | ✅ | ชื่อผู้เล่น (max 20 ตัวอักษร) |
| `skin_slug` | string | ✅ | รหัส skin (classic, fire, ice, gold, rainbow) |

**Response:** `200 OK`
```json
{
  "success": true,
  "room_code": "ABC123",
  "room_id": 1,
  "player": {
    "id": 123,
    "room_id": 1,
    "user_id": null,
    "player_name": "ชื่อผู้เล่น",
    "skin_slug": "classic",
    "position": {"x": 0, "y": 0, "z": 0},
    "direction": {"x": 1, "y": 0, "z": 0},
    "score": 0,
    "length": 5,
    "is_alive": true,
    "status": "active"
  },
  "room_state": {
    "room": {...},
    "players": [...],
    "items": [...]
  }
}
```

**Errors:**
- `422 Validation Error` - ข้อมูลไม่ถูกต้อง
- `500 Server Error` - ระบบขัดข้อง

---

#### 2. ออกจากเกม (Leave Game)

**Endpoint:** `POST /games/snake-io/leave`

**Authentication:** ❌ ไม่ต้อง

**Request Body:**
```json
{
  "player_id": 123
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `player_id` | integer | ✅ | ID ของผู้เล่น (จาก join response) |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "ออกจากเกมสำเร็จ"
}
```

---

#### 3. ดึงสถานะห้อง (Get Room State)

**Endpoint:** `GET /games/snake-io/room-state/{roomId}`

**Authentication:** ❌ ไม่ต้อง

**URL Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `roomId` | integer | ✅ | ID ของห้อง |

**Response:** `200 OK`
```json
{
  "success": true,
  "room": {
    "id": 1,
    "room_code": "ABC123",
    "name": "Room ABC123",
    "current_players": 5,
    "max_players": 30,
    "status": "active",
    "settings": {
      "world_size": 200,
      "food_count": 100,
      "powerup_spawn_interval": 20
    }
  },
  "players": [
    {
      "id": 123,
      "player_name": "Player1",
      "skin_slug": "classic",
      "score": 150,
      "length": 20,
      "is_alive": true,
      "position": {"x": 10, "y": 0, "z": 20}
    }
  ],
  "items": [
    {
      "id": 456,
      "item_type": "food",
      "position": {"x": 50, "y": 0, "z": 30},
      "value": 1,
      "is_collected": false
    }
  ]
}
```

---

### Player Actions

#### 4. อัพเดทสถานะผู้เล่น (Update Player State)

**Endpoint:** `POST /games/snake-io/update-state`

**Authentication:** ❌ ไม่ต้อง

**Request Body:**
```json
{
  "player_id": 123,
  "position": {"x": 10, "y": 0, "z": 20},
  "direction": {"x": 1, "y": 0, "z": 0},
  "score": 100,
  "length": 15
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `player_id` | integer | ✅ | ID ของผู้เล่น |
| `position` | object | ✅ | ตำแหน่งปัจจุบัน {x, y, z} |
| `direction` | object | ✅ | ทิศทางเคลื่อนไหว {x, y, z} |
| `score` | integer | ✅ | คะแนนปัจจุบัน |
| `length` | integer | ✅ | ความยาวงู |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "อัพเดทสถานะสำเร็จ"
}
```

---

#### 5. แจ้งว่าผู้เล่นตาย (Player Died)

**Endpoint:** `POST /games/snake-io/player-died`

**Authentication:** ❌ ไม่ต้อง

**Request Body:**
```json
{
  "player_id": 123
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "food_spawned": 10,
  "message": "ผู้เล่นตายแล้ว และสร้างอาหาร 10 ชิ้น"
}
```

---

#### 6. เก็บไอเทม (Collect Item)

**Endpoint:** `POST /games/snake-io/collect-item`

**Authentication:** ❌ ไม่ต้อง

**Request Body:**
```json
{
  "player_id": 123,
  "item_id": 456
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `player_id` | integer | ✅ | ID ของผู้เล่น |
| `item_id` | integer | ✅ | ID ของไอเทมที่เก็บ |

**Response:** `200 OK`
```json
{
  "success": true,
  "item": {
    "id": 456,
    "item_type": "food",
    "value": 1,
    "position": {"x": 50, "y": 0, "z": 30}
  },
  "message": "เก็บไอเทมสำเร็จ"
}
```

**Errors:**
- `404 Not Found` - ไม่พบไอเทม
- `409 Conflict` - ไอเทมถูกเก็บไปแล้ว

---

### Wallet & Scoring

#### 7. ตรวจสอบ Wallet (Check Wallet)

**Endpoint:** `GET /games/snake-io/check-wallet`

**Authentication:** ✅ **ต้อง login**

**Response:** `200 OK`
```json
{
  "success": true,
  "has_wallet": true,
  "balance": 1000,
  "can_save_score": true,
  "cost_to_save": 1,
  "user_id": 1,
  "user_name": "TestUser"
}
```

**Errors:**
- `401 Unauthorized` - ไม่ได้ login

---

#### 8. บันทึกคะแนน (Save Score)

**Endpoint:** `POST /games/snake-io/save-score`

**Authentication:** ✅ **ต้อง login**

**Request Body:**
```json
{
  "score": 1500,
  "length": 60
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `score` | integer | ✅ | คะแนนที่ทำได้ |
| `length` | integer | ✅ | ความยาวงูเมื่อจบเกม |

**Response:** `200 OK`
```json
{
  "success": true,
  "leaderboard": {
    "id": 789,
    "user_id": 1,
    "game_id": 1,
    "score": 1500,
    "wave_reached": 60,
    "rank": 5
  },
  "wallet_deducted": 1,
  "new_balance": 999,
  "message": "บันทึกคะแนนสำเร็จ"
}
```

**Errors:**
- `401 Unauthorized` - ไม่ได้ login
- `402 Payment Required` - แต้มไม่พอ (ถ้ามี wallet แต่แต้มไม่พอ)

---

#### 9. บันทึกการตั้งค่า Skin (Save Skin Preference)

**Endpoint:** `POST /games/snake-io/save-skin-preference`

**Authentication:** ✅ **ต้อง login**

**Request Body:**
```json
{
  "skin_slug": "fire",
  "custom_colors": ["#FF4400", "#FF2200", "#FF6600"]
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "บันทึกการตั้งค่า skin สำเร็จ"
}
```

---

#### 10. ดึงการตั้งค่า Skin (Get Skin Preference)

**Endpoint:** `GET /games/snake-io/get-skin-preference`

**Authentication:** ✅ **ต้อง login**

**Response:** `200 OK`
```json
{
  "success": true,
  "skin_slug": "fire",
  "custom_colors": ["#FF4400", "#FF2200", "#FF6600"]
}
```

---

#### 11. ตรวจสอบสถานะ Service (Service Status)

**Endpoint:** `GET /games/snake-io/service-status`

**Authentication:** ❌ ไม่ต้อง

**Response:** `200 OK`
```json
{
  "success": true,
  "is_online": true,
  "mode": "database",
  "active_players": 25,
  "active_rooms": 3,
  "server_time": "2025-01-15 10:30:00"
}
```

---

## Data Models

### GameRoom
```json
{
  "id": 1,
  "game_id": 1,
  "room_code": "ABC123",
  "name": "Room ABC123",
  "max_players": 30,
  "current_players": 5,
  "status": "active",
  "settings": {
    "world_size": 200,
    "food_count": 100,
    "powerup_spawn_interval": 20
  },
  "started_at": "2025-01-15 10:00:00",
  "ended_at": null,
  "created_at": "2025-01-15 09:55:00",
  "updated_at": "2025-01-15 10:00:00"
}
```

### GameRoomPlayer
```json
{
  "id": 123,
  "room_id": 1,
  "user_id": null,
  "player_name": "Player1",
  "skin_slug": "classic",
  "position": {"x": 10, "y": 0, "z": 20},
  "direction": {"x": 1, "y": 0, "z": 0},
  "score": 150,
  "length": 20,
  "is_alive": true,
  "status": "active",
  "last_update": "2025-01-15 10:05:00",
  "created_at": "2025-01-15 10:00:00",
  "updated_at": "2025-01-15 10:05:00"
}
```

### GameRoomItem
```json
{
  "id": 456,
  "room_id": 1,
  "item_type": "food",
  "position": {"x": 50, "y": 0, "z": 30},
  "value": 1,
  "spawned_at": "2025-01-15 10:00:00",
  "expires_at": null,
  "is_collected": false,
  "collected_by": null,
  "collected_at": null
}
```

**Item Types:**
- `food` - อาหารปกติ (value = 1)
- `powerup_magnet` - Powerup แม่เหล็ก (ดูดอาหาร)
- `powerup_speed` - Powerup เร่งความเร็ว
- `powerup_multiplier` - Powerup เพิ่มคะแนน x2

---

## Error Handling

### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "ข้อมูลไม่ถูกต้อง",
    "details": {
      "player_name": ["ช่อง player_name จำเป็นต้องมี"]
    }
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success - สำเร็จ |
| 401 | Unauthorized - ไม่ได้ login |
| 402 | Payment Required - แต้มไม่พอ |
| 404 | Not Found - ไม่พบข้อมูล |
| 409 | Conflict - ข้อมูลซ้ำกัน |
| 422 | Validation Error - ข้อมูลไม่ถูกต้อง |
| 429 | Too Many Requests - เกิน rate limit |
| 500 | Server Error - ระบบขัดข้อง |

---

## Example Usage

### JavaScript (Fetch API)

```javascript
// เข้าร่วมเกม
async function joinGame(playerName, skinSlug) {
  const response = await fetch('/api/games/snake-io/join', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      player_name: playerName,
      skin_slug: skinSlug,
    }),
  });

  const data = await response.json();

  if (data.success) {
    console.log('เข้าร่วมห้อง:', data.room_code);
    console.log('Player ID:', data.player.id);
    return data;
  } else {
    console.error('เข้าร่วมไม่สำเร็จ:', data.error);
    throw new Error(data.error.message);
  }
}

// อัพเดทสถานะ
async function updatePlayerState(playerId, position, direction, score, length) {
  const response = await fetch('/api/games/snake-io/update-state', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      player_id: playerId,
      position: position,
      direction: direction,
      score: score,
      length: length,
    }),
  });

  return await response.json();
}

// บันทึกคะแนน (ต้อง login)
async function saveScore(score, length) {
  const response = await fetch('/api/games/snake-io/save-score', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    credentials: 'same-origin', // ส่ง cookies
    body: JSON.stringify({
      score: score,
      length: length,
    }),
  });

  return await response.json();
}
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8000/api',
    'timeout'  => 5.0,
]);

// เข้าร่วมเกม
$response = $client->post('/games/snake-io/join', [
    'json' => [
        'player_name' => 'PHPPlayer',
        'skin_slug' => 'classic',
    ],
]);

$data = json_decode($response->getBody(), true);

if ($data['success']) {
    echo "เข้าร่วมห้อง: {$data['room_code']}\n";
    echo "Player ID: {$data['player']['id']}\n";
}
```

### cURL

```bash
# เข้าร่วมเกม
curl -X POST http://localhost:8000/api/games/snake-io/join \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"player_name":"CurlPlayer","skin_slug":"classic"}'

# ดึงสถานะห้อง
curl -X GET http://localhost:8000/api/games/snake-io/room-state/1 \
  -H "Accept: application/json"

# บันทึกคะแนน (ต้อง login)
curl -X POST http://localhost:8000/api/games/snake-io/save-score \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION_TOKEN" \
  -H "X-CSRF-TOKEN: YOUR_CSRF_TOKEN" \
  -d '{"score":1500,"length":60}'
```

---

## Anti-Cheat System

API มีระบบตรวจจับการโกง 5 ระดับ:

### 1. Score Increment Validation
- **Rule:** คะแนนเพิ่มสูงสุด 100/วินาที
- **Action:** ปฏิเสธการอัพเดท + log suspicious activity

### 2. Length Validation
- **Rule:** ความยาวต้องสอดคล้องกับคะแนน
- **Action:** ตรวจสอบ formula: `length = Math.floor(score / 10) + 5`

### 3. Movement Speed Check
- **Rule:** เคลื่อนที่สูงสุด 30 units/วินาที
- **Action:** ปฏิเสธการอัพเดท + log suspicious activity

### 4. Map Boundary Check
- **Rule:** ต้องอยู่ภายในแผนที่ (-100 ถึง 100)
- **Action:** ผู้เล่นตายทันที

### 5. Rate Limiting
- **Rule:** อัพเดทสถานะสูงสุด 60 ครั้ง/วินาที (16ms ต่อครั้ง)
- **Action:** ปฏิเสธ request + 429 Error

---

## WebSocket Broadcasting

เกมใช้ Laravel Reverb สำหรับ real-time updates:

### Channels
```
snake-room.{roomId}
```

### Events
- `player.joined` - ผู้เล่นเข้าร่วม
- `player.left` - ผู้เล่นออก
- `player.moved` - ผู้เล่นเคลื่อนไหว
- `player.died` - ผู้เล่นตาย
- `item.spawned` - ไอเทมถูก spawn
- `item.collected` - ไอเทมถูกเก็บ

### การเชื่อมต่อ WebSocket

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Subscribe to room
window.Echo.channel(`snake-room.${roomId}`)
    .listen('PlayerJoined', (e) => {
        console.log('ผู้เล่นเข้าร่วม:', e.player);
    })
    .listen('PlayerMoved', (e) => {
        console.log('ผู้เล่นเคลื่อนไหว:', e.player);
    });
```

---

## Contact & Support

- **Developer:** XMAN STUDIO
- **Version:** 2.6.0
- **GitHub:** https://github.com/xjanova/Thaiprompt-Affiliate
- **License:** Commercial License

---

**หมายเหตุ:** เอกสารนี้อาจมีการเปลี่ยนแปลงตาม version ของเกม โปรดตรวจสอบ version ก่อนใช้งาน
