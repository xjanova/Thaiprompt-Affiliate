# LINE Chatbot MLM Signup System

## 📋 ภาพรวมระบบ

ระบบสมัครสมาชิก MLM ผ่าน LINE Chatbot ที่มีประสิทธิภาพสูง พร้อมฟีเจอร์ครบครัน:

- ✅ ระบบลิงก์เชิญพร้อมล็อกผู้แนะนำ
- ✅ ระบบผู้มุ่งหวัง (Prospects) พร้อมการติดตาม
- ✅ ระบบเพิ่มเพื่อนแม่ทีมอัตโนมัติ
- ✅ Conversation Flow แบบ Step-by-Step
- ✅ Admin Configuration ที่ปรับแต่งได้
- ✅ สนับสนุน AI Bot ในอนาคต

---

## 🗂 โครงสร้างไฟล์

### Database Migrations
```
database/migrations/
├── 2025_11_08_000001_create_mlm_prospects_table.php
├── 2025_11_08_000002_create_line_signup_flows_table.php
├── 2025_11_08_000003_add_line_id_to_users_table.php
└── 2025_11_08_000004_add_prospect_settings_to_mlm_global_settings_table.php
```

### Models
```
app/Models/
├── MlmProspect.php           # ผู้มุ่งหวัง
└── LineSignupFlow.php        # บทสนทนาสมัครสมาชิก
```

### Services
```
app/Services/
├── MlmProspectService.php    # จัดการผู้มุ่งหวัง
└── LineSignupService.php     # จัดการ Signup Flow
```

### Controllers
```
app/Http/Controllers/
├── LineSignupController.php                        # Public signup routes
├── User/MlmProspectController.php                 # User dashboard
├── Admin/LineSignupFlowController.php             # Admin: จัดการ Flow
└── Admin/MlmProspectController.php                # Admin: จัดการ Prospects
```

### Routes
```
routes/
├── web.php     # LINE Signup routes
├── user.php    # Prospect management routes
└── admin.php   # Admin management routes
```

---

## 🚀 การติดตั้งและใช้งาน

### 1. รัน Migrations

```bash
php artisan migrate
```

### 2. รัน Seeders

```bash
# Seed Signup Flow
php artisan db:seed --class=LineSignupFlowSeeder

# Update MLM Global Settings
php artisan db:seed --class=MlmGlobalSettingSeeder
```

### 3. ตั้งค่า LINE OA

1. ไปที่ Admin Panel → LINE OA Management
2. กรอก Channel ID และ Channel Secret
3. กรอก Channel Access Token
4. เปิดใช้งาน LINE Messaging

### 4. ตั้งค่า Webhook URL

ตั้งค่า Webhook URL ใน LINE Developers Console:
```
https://yourdomain.com/api/webhook/line
```

---

## 📖 วิธีใช้งาน

### สำหรับสมาชิก MLM (แม่ทีม)

#### 1. สร้างลิงก์เชิญ

```php
// ผ่าน Service
$prospectService = app(MlmProspectService::class);
$prospect = $prospectService->createInvitationLink($mlmMember);

// URL: /line/signup/invitation/{token}
echo $prospect->invitation_url;
```

#### 2. แชร์ลิงก์ให้ผู้สนใจ

- คัดลอกลิงก์จากหน้าผู้มุ่งหวัง
- แชร์ผ่าน LINE, Facebook, หรือช่องทางอื่นๆ

#### 3. ติดตามผู้มุ่งหวัง

ไปที่: **User Dashboard → ผู้มุ่งหวัง**

จะเห็น:
- จำนวนผู้กดลิงก์
- สถานะการสมัคร (Pending, In Progress, Completed, Expired)
- เวลาหมดอายุล็อก
- ข้อมูล LINE ของผู้สนใจ

---

### สำหรับผู้สนใจสมัคร

#### การใช้งาน

1. **กดลิงก์เชิญ** → Redirect ไป LINE Login
2. **Login ด้วย LINE** → ระบบบันทึก LINE Profile
3. **กลับมาที่ LINE Chat** → Bot เริ่ม Conversation
4. **ตอบคำถามตาม Flow**:
   - ชื่อ-นามสกุล
   - เบอร์โทรศัพท์
   - อีเมล (ถ้ามี)
   - ยืนยันข้อมูล
5. **สมัครสำเร็จ** → ได้รับข้อมูลบัญชีและเพิ่มเพื่อนแม่ทีมอัตโนมัติ

---

## ⚙️ การตั้งค่า Admin

### MLM Global Settings

**ที่ตั้ง**: Admin → MLM → Global Settings

#### Prospect Lock Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `prospect_lock_duration_hours` | อายุล็อกผู้มุ่งหวัง (ชั่วโมง) | 24 |
| `enable_prospect_lock` | เปิด/ปิดระบบล็อก | true |

#### Auto Add Friend Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `enable_auto_add_sponsor_friend` | เปิด/ปิดการเพิ่มเพื่อนแม่ทีมอัตโนมัติ | true |

#### LINE Signup Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `enable_line_signup` | เปิด/ปิดการสมัครผ่าน LINE | true |
| `require_line_verification` | บังคับยืนยัน LINE | false |

---

### Signup Flow Management

**ที่ตั้ง**: Admin → LINE Bot → Signup Flow

#### คุณสมบัติ

- ✏️ แก้ไขข้อความในแต่ละ Step
- 🔢 เปลี่ยนลำดับ Step
- ✅ เปิด/ปิด Step
- 🤖 เปิดใช้ AI สำหรับ Step นั้นๆ (อนาคต)
- 📝 ตั้งค่า Validation Rules

#### Input Types

- `text` - ข้อความทั่วไป
- `phone` - เบอร์โทรศัพท์
- `email` - อีเมล
- `name` - ชื่อ-นามสกุล
- `confirm` - ยืนยัน (ใช่/ไม่ใช่)
- `choice` - เลือก (Multiple choice)
- `none` - ไม่ต้องรับ input

---

## 🔐 ระบบความปลอดภัย

### 1. Prospect Locking

เมื่อผู้สนใจกดลิงก์:
- ✅ ระบบล็อกผู้สนใจกับแม่ทีมคนนั้นทันที
- ✅ ผู้สนใจไม่สามารถถูกแย่งโดยแม่ทีมคนอื่นได้
- ✅ ล็อกมีอายุ (Default 24 ชม.)
- ✅ หมดอายุอัตโนมัติหากไม่สมัครภายในเวลาที่กำหนด

### 2. Signature Verification

- ✅ ตรวจสอบ LINE Webhook Signature
- ✅ ป้องกัน Request ปลอม

### 3. Token Security

- ✅ Referral Token แบบ Random (32 characters)
- ✅ Unique per invitation
- ✅ ไม่สามารถเดาได้

---

## 📊 ข้อมูลและ Reporting

### Prospect Statistics

```php
$stats = $prospectService->getProspectStats($mlmMemberId);

/*
[
    'total' => 100,
    'pending' => 20,
    'in_progress' => 10,
    'completed' => 60,
    'expired' => 10,
    'locked' => 15,
    'conversion_rate' => 60.00
]
*/
```

### Prospect Tracking

แต่ละ Prospect จะมีข้อมูล:
- 👤 LINE Profile (ชื่อ, รูป)
- 🔗 Referral Token
- 📅 เวลาที่กดลิงก์
- 🔒 สถานะล็อก
- 📝 Conversation Data
- 🌐 IP Address, User Agent
- 📈 Visit Count

---

## 🔧 API Usage

### สร้างลิงก์เชิญ

```php
use App\Services\MlmProspectService;

$prospectService = app(MlmProspectService::class);
$prospect = $prospectService->createInvitationLink($mlmMember);

echo $prospect->invitation_url;
// https://yourdomain.com/line/signup/invitation/abc123...
```

### ดึงข้อมูลผู้มุ่งหวัง

```php
// ทั้งหมดของ Sponsor
$prospects = $prospectService->getProspectsForSponsor($mlmMemberId);

// กรองตามสถานะ
$pending = $prospectService->getProspectsForSponsor($mlmMemberId, 'pending');
```

### ส่งข้อความ Custom

```php
use App\Services\LineService;

$lineService = app(LineService::class);
$lineService->sendPushMessage(
    $prospect->line_user_id,
    'สวัสดี! ขอบคุณที่สนใจ'
);
```

---

## 🤖 AI Integration (อนาคต)

ระบบรองรับการใช้ AI ในการตอบคำถาม:

1. ✅ เปิดใช้ AI ใน Signup Flow
2. ✅ ตั้งค่า AI Prompt
3. ✅ แม่ทีมซื้อ AI Package เพื่ออัพเกรด

```php
LineSignupFlow::create([
    'require_ai' => true,
    'ai_prompt' => 'You are a helpful MLM assistant...',
    // ...
]);
```

---

## 📈 Cron Jobs

### Expire Old Prospects

```bash
# ใน app/Console/Kernel.php

$schedule->call(function () {
    app(MlmProspectService::class)->expireOldProspects();
})->daily();
```

---

## 🐛 Troubleshooting

### ผู้สนใจไม่ได้รับข้อความจาก Bot

**สาเหตุที่เป็นไปได้**:
1. ❌ LINE Channel Access Token ไม่ถูกต้อง
2. ❌ ผู้สนใจยังไม่เพิ่มเพื่อนกับ LINE OA
3. ❌ `enable_line_messaging` ปิดอยู่

**วิธีแก้**:
1. ✅ ตรวจสอบ LINE OA Settings
2. ✅ ทดสอบส่งข้อความผ่าน Admin Panel
3. ✅ ตรวจสอบ LINE Bot Logs

### Prospect ไม่ถูกล็อก

**สาเหตุ**:
- `enable_prospect_lock` = false

**วิธีแก้**:
- เปิดใช้งาน Prospect Lock ใน MLM Global Settings

---

## 📝 Database Schema

### mlm_prospects

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary Key |
| sponsor_mlm_member_id | bigint | FK to mlm_members |
| sponsor_user_id | bigint | FK to users |
| line_user_id | string | LINE User ID |
| referral_token | string | Unique token |
| status | enum | pending/in_progress/completed/expired |
| clicked_at | timestamp | เวลาที่กดลิงก์ |
| locked_until | timestamp | ล็อกจนถึง |
| is_locked | boolean | สถานะล็อก |
| conversation_step | string | Step ปัจจุบัน |
| conversation_data | json | ข้อมูลการสนทนา |

### line_signup_flows

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary Key |
| step_key | string | Unique key |
| step_order | int | ลำดับ |
| message_text | text | ข้อความ |
| input_type | enum | ประเภท input |
| next_step_key | string | Step ถัดไป |
| require_ai | boolean | ใช้ AI หรือไม่ |

---

## 🎯 Use Cases

### 1. การสมัครปกติ

```
User → Click Link → LINE Login → Bot Conversation → Complete
```

### 2. การสมัครที่มีปัญหา

```
User → Click Link → Bot Conversation → ข้อมูลผิดพลาด
     → Bot แจ้ง Error → User แก้ไข → Complete
```

### 3. หมดอายุล็อก

```
User → Click Link (Day 1) → ไม่สมัครต่อ
     → 24 hours later → Expired → Sponsor sees "Expired"
```

---

## 🌟 Features Roadmap

- [ ] Rich Menu สำหรับ LINE Bot
- [ ] Flex Message สวยงามสำหรับ Signup Flow
- [ ] AI Auto Response
- [ ] Multi-language Support
- [ ] Analytics Dashboard
- [ ] Email Notification สำหรับ Sponsor

---

## 📞 Support

หากมีปัญหา กรุณาติดต่อทีมพัฒนา

---

**Version**: 1.0
**Last Updated**: November 8, 2025
**Developer**: Claude AI Assistant
