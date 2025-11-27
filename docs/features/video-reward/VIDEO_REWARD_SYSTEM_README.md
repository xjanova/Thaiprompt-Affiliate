# ระบบดูคลิปรับรางวัล (Video Reward System)

## 📋 สารบัญ
- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [ฟีเจอร์หลัก](#ฟีเจอร์หลัก)
- [การติดตั้ง](#การติดตั้ง)
- [โครงสร้างฐานข้อมูล](#โครงสร้างฐานข้อมูล)
- [API Endpoints](#api-endpoints)
- [การใช้งาน](#การใช้งาน)
- [ตัวอย่างการใช้งาน](#ตัวอย่างการใช้งาน)

## 🎯 ภาพรวมระบบ

ระบบดูคลิปรับรางวัลเป็นระบบที่ครบครันสำหรับการสร้างแรงจูงใจให้ผู้ใช้ดูคลิปวิดีโอ โดยสามารถรับรางวัลเป็นเหรียญ (Coins) ที่สามารถแลกเป็นเงินสดเข้า Wallet ได้

### ✨ ฟีเจอร์หลัก

#### 1. ระบบวิดีโอและช่อง
- จัดการช่องวิดีโอได้หลายช่อง
- เพิ่มวิดีโอเข้าสู่แต่ละช่อง
- กำหนดรางวัลสำหรับแต่ละวิดีโอ
- รองรับหลายแพลตฟอร์ม (YouTube, Vimeo, Direct URL)

#### 2. ระบบติดตามการดู (Watch Tracking)
- **Heartbeat System**: ตรวจสอบการดูจริงด้วยระบบ heartbeat ทุก 10-15 วินาที
- **Anti-Cheat**: ระบบตรวจจับการโกงอัตโนมัติ
- **Progress Bar**: แสดงความคืบหน้าการดูแบบ real-time
- **Watch Sessions**: บันทึก session การดูแต่ละครั้ง

#### 3. ระบบเลเวล (Level System)
7 ระดับที่มีสิทธิประโยชน์เพิ่มขึ้น:
- 🌱 **ระดับ 1**: มือใหม่ (0 EXP)
- 🌿 **ระดับ 2**: ผู้สนใจ (100 EXP)
- 🌾 **ระดับ 3**: ผู้ติดตาม (300 EXP)
- 🌻 **ระดับ 4**: แฟนตัวจริง (600 EXP)
- 🌲 **ระดับ 5**: ผู้เชี่ยวชาญ (1,200 EXP)
- 🏆 **ระดับ 6**: ปรมาจารย์ (2,500 EXP)
- 👑 **ระดับ 7**: ตำนาน (5,000 EXP)

**สิทธิประโยชน์ตามระดับ**:
- Reward Multiplier เพิ่มขึ้น (1.0x - 2.5x)
- จำนวนภาระกิจต่อวันเพิ่มขึ้น (3 - 10 ภาระกิจ)
- โบนัสเหรียญเมื่อขึ้นระดับ (50 - 1,000 coins)

#### 4. ระบบภาระกิจ (Quest System)

**ประเภทภาระกิจ**:
- 🎯 **watch_videos**: ดูวิดีโอ X คลิป
- ⏱️ **watch_duration**: ดูรวม X นาที
- 📺 **watch_channel**: ดูวิดีโอจากช่องที่กำหนด
- 🔥 **daily_streak**: ดูติดต่อกัน X วัน
- 👥 **refer_users**: ชวนเพื่อน X คน
- 🎁 **referred_watch**: เพื่อนที่ชวนดูวิดีโอ X คลิป
- ⭐ **reach_level**: ถึงระดับ X

**ความถี่ภาระกิจ**:
- **daily**: ทุกวัน
- **weekly**: ทุกสัปดาห์
- **monthly**: ทุกเดือน
- **once**: ครั้งเดียว
- **repeatable**: ทำซ้ำได้

#### 5. ระบบเหรียญและการแลกเงิน

**เหรียญ (Coins)**:
- ได้จากการดูวิดีโอ
- ได้จากการทำภาระกิจสำเร็จ
- ได้จากการขึ้นระดับ
- ได้จากการชวนเพื่อน

**อัตราแลกเปลี่ยน**:
- 100 coins = 10 THB (ระดับ 1+)
- 500 coins = 55 THB (ระดับ 3+)
- 1,000 coins = 120 THB (ระดับ 5+)

**ข้อจำกัด**:
- จำกัดแลกต่อวัน
- จำกัดแลกต่อเดือน
- ต้องผ่านการอนุมัติจาก Admin

#### 6. ระบบ MLM/Referral

**รางวัลการชวน**:
- รับเมื่อเพื่อนสมัคร (signup)
- รับเมื่อเพื่อนดูวิดีโอแรก (first_video)
- รับเมื่อเพื่อนดูครบ milestone (video_milestone)
- รับเมื่อเพื่อนทำภาระกิจสำเร็จ (quest_complete)
- รับเมื่อเพื่อนขึ้นระดับ (level_up)
- รับต่อเนื่องจากกิจกรรมเพื่อน (ongoing %)

#### 7. ระบบ Daily Streak
- ติดตามการดูติดต่อกัน
- สถิติ streak ที่ยาวที่สุด
- โบนัสพิเศษสำหรับ streak ยาว

## 🚀 การติดตั้ง

### 1. รัน Migrations

```bash
php artisan migrate
```

### 2. รัน Seeder (ข้อมูลตัวอย่าง)

```bash
php artisan db:seed --class=VideoRewardSystemSeeder
```

Seeder จะสร้าง:
- ✅ 7 ระดับ (Levels)
- ✅ 4 ช่องวิดีโอ
- ✅ 10 วิดีโอตัวอย่าง
- ✅ 8 ภาระกิจ (Daily, Weekly, Once)
- ✅ 3 อัตราแลกเปลี่ยนเหรียญ

## 📊 โครงสร้างฐานข้อมูล

### ตารางหลัก

1. **video_channels** - ช่องวิดีโอ
2. **video_contents** - วิดีโอแต่ละเรื่อง
3. **video_levels** - ระดับต่างๆ
4. **video_quests** - ภาระกิจ
5. **coin_exchange_rates** - อัตราแลกเปลี่ยน

### ตารางผู้ใช้

6. **user_video_levels** - ระดับของผู้ใช้
7. **video_coins** - ยอดเหรียญของผู้ใช้
8. **user_video_watches** - ประวัติการดู
9. **video_watch_sessions** - Session การดู
10. **user_quest_progress** - ความคืบหน้าภาระกิจ
11. **user_daily_streaks** - Streak ของผู้ใช้

### ตารางธุรกรรม

12. **video_coin_transactions** - ประวัติเหรียญ
13. **coin_exchange_requests** - คำขอแลกเหรียญ
14. **video_referral_rewards** - รางวัลการชวนเพื่อน

## 🔌 API Endpoints

### สำหรับผู้ใช้ (User API)

#### Dashboard & Overview
```
GET /api/v1/video-rewards/dashboard
GET /api/v1/video-rewards/statistics
GET /api/v1/video-rewards/leaderboard?type=level|coins|videos
```

#### วิดีโอ
```
GET /api/v1/video-rewards/channels
GET /api/v1/video-rewards/channels/{channelId}/videos
GET /api/v1/video-rewards/videos/{videoId}
```

#### การดูวิดีโอ
```
POST /api/v1/video-rewards/watch/start
     Body: { video_id, start_position }

POST /api/v1/video-rewards/watch/heartbeat
     Body: { session_token, current_position, timestamp }

POST /api/v1/video-rewards/watch/end
     Body: { session_token, end_position }

POST /api/v1/video-rewards/watch/claim-reward
     Body: { video_id }
```

#### ภาระกิจ
```
GET /api/v1/video-rewards/quests?frequency=daily|weekly|monthly
GET /api/v1/video-rewards/quests/{questId}
POST /api/v1/video-rewards/quests/{questId}/claim
GET /api/v1/video-rewards/quests/history
```

#### แลกเหรียญ
```
GET /api/v1/video-rewards/exchange/rates
POST /api/v1/video-rewards/exchange/calculate
     Body: { coins_amount, exchange_rate_id }

POST /api/v1/video-rewards/exchange/request
     Body: { coins_amount, exchange_rate_id }

GET /api/v1/video-rewards/exchange/history
GET /api/v1/video-rewards/exchange/requests/{requestId}
```

### สำหรับ Admin

#### Dashboard
```
GET /admin/video-rewards/dashboard
```

#### จัดการคำขอแลกเหรียญ
```
GET /admin/video-rewards/exchange-requests?status=pending|approved|rejected
POST /admin/video-rewards/exchange-requests/{requestId}/approve
POST /admin/video-rewards/exchange-requests/{requestId}/reject
```

#### จัดการช่อง
```
GET /admin/video-rewards/channels
POST /admin/video-rewards/channels
PUT /admin/video-rewards/channels/{channelId}
```

#### จัดการวิดีโอ
```
GET /admin/video-rewards/videos?channel_id=X
POST /admin/video-rewards/videos
PUT /admin/video-rewards/videos/{videoId}
```

#### จัดการภาระกิจ
```
GET /admin/video-rewards/quests
POST /admin/video-rewards/quests
PUT /admin/video-rewards/quests/{questId}
```

#### จัดการอัตราแลกเปลี่ยน
```
GET /admin/video-rewards/exchange-rates
PUT /admin/video-rewards/exchange-rates/{rateId}
```

## 💡 การใช้งาน

### การดูวิดีโอ

```javascript
// 1. เริ่มดู
const response = await fetch('/api/v1/video-rewards/watch/start', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    video_id: 1,
    start_position: 0
  })
});

const { session_token } = await response.json();

// 2. ส่ง Heartbeat ทุก 10-15 วินาที
setInterval(() => {
  fetch('/api/v1/video-rewards/watch/heartbeat', {
    method: 'POST',
    body: JSON.stringify({
      session_token,
      current_position: video.currentTime,
      timestamp: Date.now()
    })
  });
}, 15000);

// 3. จบการดู
await fetch('/api/v1/video-rewards/watch/end', {
  method: 'POST',
  body: JSON.stringify({
    session_token,
    end_position: video.duration
  })
});

// 4. รับรางวัล
await fetch('/api/v1/video-rewards/watch/claim-reward', {
  method: 'POST',
  body: JSON.stringify({ video_id: 1 })
});
```

### การรับภาระกิจ

```javascript
// ดูภาระกิจที่มี
const quests = await fetch('/api/v1/video-rewards/quests?frequency=daily');

// รับรางวัลภาระกิจ
await fetch('/api/v1/video-rewards/quests/1/claim', {
  method: 'POST'
});
```

### การแลกเหรียญ

```javascript
// ดูอัตราแลก
const rates = await fetch('/api/v1/video-rewards/exchange/rates');

// คำนวณเงินที่จะได้
const result = await fetch('/api/v1/video-rewards/exchange/calculate', {
  method: 'POST',
  body: JSON.stringify({
    coins_amount: 500,
    exchange_rate_id: 2
  })
});

// ส่งคำขอแลก
await fetch('/api/v1/video-rewards/exchange/request', {
  method: 'POST',
  body: JSON.stringify({
    coins_amount: 500,
    exchange_rate_id: 2
  })
});
```

## 🎮 ตัวอย่างการใช้งาน

### สำหรับผู้ใช้

1. **ดูวิดีโอรับเหรียญ**
   - เข้าไปดู Dashboard → เลือกวิดีโอ
   - ดูจนครบตามเงื่อนไข (80%)
   - รับเหรียญและ EXP อัตโนมัติ

2. **ทำภาระกิจ**
   - เช็คภาระกิจรายวัน/รายสัปดาห์
   - ทำตามเงื่อนไข (ดู 3 คลิป, ดู 30 นาที, etc.)
   - Claim รางวัลเมื่อเสร็จ

3. **ชวนเพื่อน**
   - แชร์ referral code
   - เพื่อนสมัครและดูคลิป
   - รับโบนัสตามกิจกรรมของเพื่อน

4. **แลกเหรียญเป็นเงิน**
   - สะสมเหรียญให้ครบตามอัตราแลก
   - ส่งคำขอแลก
   - รอ Admin อนุมัติ
   - รับเงินเข้า Wallet

### สำหรับ Admin

1. **เพิ่มวิดีโอใหม่**
   - สร้างช่อง (ถ้ายังไม่มี)
   - เพิ่มวิดีโอพร้อมกำหนดรางวัล
   - ตั้งเงื่อนไขการดู (80%, 90%, etc.)

2. **สร้างภาระกิจ**
   - กำหนดประเภทภาระกิจ
   - ตั้งเป้าหมายและรางวัล
   - เลือกความถี่ (daily/weekly/monthly)

3. **อนุมัติคำขอแลกเหรียญ**
   - ดูรายการคำขอที่รอ
   - ตรวจสอบความถูกต้อง
   - Approve/Reject
   - เงินเข้า Wallet ผู้ใช้อัตโนมัติ

## 🔐 ความปลอดภัย

- ✅ Heartbeat system ป้องกันการโกง
- ✅ Session validation
- ✅ Watch time tracking แบบ real-time
- ✅ Admin approval สำหรับการแลกเหรียญ
- ✅ Transaction logging ทุกรายการ
- ✅ Daily/Monthly limits

## 📈 สถิติและรายงาน

ระบบมีการเก็บสถิติ:
- จำนวนวิดีโอที่ดู
- เวลาดูทั้งหมด
- เหรียญที่ได้รับ/ใช้ไป
- ภาระกิจที่สำเร็จ
- Leaderboard แบ่งตามระดับ/เหรียญ/วิดีโอ
- Streak ปัจจุบันและสถิติสูงสุด

## 🎨 การปรับแต่ง

### เพิ่มภาระกิจแบบกำหนดเอง

```php
VideoQuest::create([
    'name' => 'ดูครบ 100 คลิป',
    'type' => 'watch_videos',
    'frequency' => 'once',
    'target_value' => 100,
    'reward_coins' => 1000,
    'reward_exp' => 500,
    'reward_money' => 100,
]);
```

### เพิ่มระดับใหม่

```php
VideoLevel::create([
    'level' => 8,
    'name' => 'เซียน',
    'icon' => '💎',
    'required_exp' => 10000,
    'max_daily_quests' => 15,
    'reward_multiplier' => 3.00,
    'coin_bonus' => 2000,
]);
```

## 🐛 การแก้ปัญหา

### ปัญหาที่พบบ่อย

1. **เหรียญไม่เข้า**: ตรวจสอบว่าดูวิดีโอครบตามเงื่อนไข (80%)
2. **ภาระกิจไม่อัพเดท**: Refresh หรือตรวจสอบเงื่อนไขภาระกิจ
3. **แลกเหรียญไม่ได้**: ตรวจสอบระดับและจำนวนเหรียญขั้นต่ำ

## 📝 License

Copyright © 2025 Thaiprompt Affiliate System

---

สร้างด้วย ❤️ โดย Claude Code
