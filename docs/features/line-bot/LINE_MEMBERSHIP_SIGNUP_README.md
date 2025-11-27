# LINE Membership Signup System 🚀

## Overview

ระบบสมัครสมาชิกผ่าน LINE ที่ขับเคลื่อนด้วย AI แบบครบวงจร พร้อม Flex Message UI ที่สวยงาม และระบบติดตามผลแบบเรียลไทม์

## ✨ Features

### 🎯 Core Features
- **AI-Powered Conversation Flow** - AI Bot คอยช่วยเหลือตลอดกระบวนการสมัคร
- **Beautiful Flex Messages** - UI/UX ที่ทันสมัยและใช้งานง่าย
- **Step-by-Step Signup** - 7 ขั้นตอนการสมัครที่ชัดเจน
- **OTP Verification** - ยืนยันตัวตนผ่านเบอร์โทรศัพท์
- **Referral System** - รองรับระบบแนะนำเพื่อนอัตโนมัติ
- **Real-time Analytics** - ติดตามผลการสมัครแบบเรียลไทม์
- **Reward System** - ระบบให้รางวัลเมื่อสมัครสำเร็จ

### 📊 Admin Features
- Dashboard สำหรับดูภาพรวม
- จัดการ Signup Sessions
- Funnel Analytics แบบละเอียด
- Template Management
- Invitation Management
- Export ข้อมูลเป็น CSV

## 🗂️ Database Structure

### Main Tables

#### `line_signup_sessions`
เก็บข้อมูล Session การสมัครของแต่ละคน
- `line_user_id` - LINE User ID
- `session_token` - Token สำหรับติดตาม
- `current_step` - ขั้นตอนปัจจุบัน
- `collected_data` - ข้อมูลที่เก็บรวบรวม (JSON)
- `status` - สถานะ (active, completed, abandoned, expired)
- `otp_code` - รหัส OTP สำหรับยืนยัน
- Progress tracking fields

#### `line_signup_step_logs`
บันทึกการทำงานของแต่ละ Step
- `session_id` - Foreign key to sessions
- `step_name` - ชื่อ step
- `status` - สถานะ (started, completed, skipped, failed)
- `step_data` - ข้อมูลของ step (JSON)
- Duration tracking

#### `line_signup_conversations`
บันทึกการสนทนากับ AI Bot
- `session_id` - Foreign key to sessions
- `role` - บทบาท (user, assistant, system)
- `message` - ข้อความ
- `metadata` - ข้อมูลเพิ่มเติม (JSON)
- `message_type` - ประเภทข้อความ

#### `line_signup_templates`
เก็บ Flex Message Templates
- `template_key` - Key สำหรับเรียกใช้
- `template_name` - ชื่อ template
- `flex_message_json` - Flex Message JSON
- `variables` - ตัวแปรที่สามารถแทนค่าได้
- `is_active` - เปิด/ปิดใช้งาน
- `usage_count` - จำนวนการใช้งาน

#### `line_signup_rewards`
รางวัลสำหรับผู้สมัครสมาชิก
- `user_id` - Foreign key to users
- `session_id` - Foreign key to sessions
- `reward_type` - ประเภท (points, coins, bonus, coupon)
- `reward_amount` - จำนวน
- `status` - สถานะ (pending, granted, claimed, expired)
- Expiration tracking

#### `line_signup_invitations`
ลิงก์เชิญสมัครสมาชิก
- `inviter_user_id` - ผู้เชิญ
- `invitation_token` - Token สำหรับลิงก์
- `referral_code` - รหัสแนะนำ
- `max_uses` - จำนวนครั้งที่ใช้ได้
- `uses_count` - จำนวนครั้งที่ใช้แล้ว
- Expiration tracking

#### `line_signup_analytics`
เก็บข้อมูล Analytics แบบ Aggregate
- `date` - วันที่
- `step_name` - ชื่อ step
- `visitors` - จำนวนผู้เข้าถึง
- `completions` - จำนวนผู้ทำสำเร็จ
- `drop_offs` - จำนวนผู้ออกไป
- `average_time_seconds` - เวลาเฉลี่ย

## 📝 Signup Flow

### 7 Steps Process

1. **Welcome** - ต้อนรับและแนะนำระบบ
2. **Name** - กรอกชื่อ-นามสกุล
3. **Email** - กรอกอีเมล (พร้อมตรวจสอบซ้ำ)
4. **Phone** - กรอกเบอร์โทรศัพท์
5. **OTP** - ยืนยันรหัส OTP
6. **Password** - ตั้งรหัสผ่าน
7. **Referral** - กรอกรหัสผู้แนะนำ (ถ้ามี)
8. **Confirmation** - ยืนยันข้อมูลและสร้างบัญชี

## 🎨 Flex Message Templates

### Available Templates

#### 1. Name Input Message
- Progress bar แสดง 1/7
- ฟอร์มกรอกชื่อที่สวยงาม
- Hero image สะดุดตา

#### 2. Email Input Message
- แสดงประโยชน์ของการกรอกอีเมล
- Checkmark list
- Progress bar แสดง 2/7

#### 3. Phone Input Message
- แจ้งเตือนการส่ง OTP
- Progress bar แสดง 3/7
- Warning box สีเหลือง

#### 4. OTP Verification Message
- แสดงเบอร์ที่ปิดบาง (masked)
- นับถอยหลังเวลา OTP หมดอายุ
- Progress bar แสดง 4/7

#### 5. Password Input Message
- แสดงเงื่อนไขรหัสผ่าน
- Checklist ความแข็งแกร่ง
- Progress bar แสดง 5/7

#### 6. Referral Input Message
- ปุ่ม Skip สำหรับข้ามขั้นตอน
- แสดงโบนัสพิเศษ
- Progress bar แสดง 6/7

#### 7. Confirmation Message
- แสดงข้อมูลทั้งหมดสำหรับตรวจสอบ
- ปุ่มยืนยันสีเขียว
- Progress bar เต็ม 100%

#### 8. Success Message
- แสดงอีเมลและรหัสสมาชิก
- แจ้งโบนัสที่ได้รับ
- ปุ่มเข้าสู่ระบบ

#### 9. Welcome Sequence Message
- แสดงรหัสแนะนำส่วนตัว
- 3 ขั้นตอนการเริ่มต้น
- ปุ่มเริ่มใช้งาน

## 🔌 API Endpoints

### Public Endpoints

#### Webhook
```
POST /api/webhook/line-membership-signup
```
รับ webhook events จาก LINE

#### Invitation Pages
```
GET /line/membership/invitation/{token}
POST /line/membership/invitation/{token}
GET /line/membership/progress/{sessionToken}
```

### Authenticated Endpoints

#### Create Invitation
```
POST /line/membership/invitations/create
```
Request Body:
```json
{
  "referral_code": "ABC12345",
  "max_uses": 10,
  "expires_at": "2025-12-31 23:59:59"
}
```

#### Analytics
```
GET /line/membership/analytics?start_date=2025-01-01&end_date=2025-12-31
```

### Admin Endpoints

#### Dashboard
```
GET /admin/line-membership-signup
GET /admin/line-membership-signup/sessions
GET /admin/line-membership-signup/sessions/{id}
```

#### Templates
```
GET /admin/line-membership-signup/templates
POST /admin/line-membership-signup/templates
PUT /admin/line-membership-signup/templates/{id}
DELETE /admin/line-membership-signup/templates/{id}
```

#### Analytics Data
```
GET /admin/line-membership-signup/analytics/data?type=daily&days=30
GET /admin/line-membership-signup/analytics/data?type=funnel&days=30
GET /admin/line-membership-signup/analytics/data?type=sources&days=30
GET /admin/line-membership-signup/analytics/data?type=completion_time&days=30
```

#### Export
```
GET /admin/line-membership-signup/export/sessions?status=completed&start_date=2025-01-01
```

## 🚀 Installation

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Configure LINE OA Settings
```php
// .env
LINE_CHANNEL_ACCESS_TOKEN=your_token_here
LINE_CHANNEL_SECRET=your_secret_here
LINE_WEBHOOK_URL=https://your-domain.com/api/webhook/line-membership-signup
```

### 3. Set Webhook URL in LINE Developers Console
```
https://your-domain.com/api/webhook/line-membership-signup
```

### 4. (Optional) Seed Template Data
```bash
php artisan db:seed --class=LineSignupTemplateSeeder
```

## 🎯 Usage Examples

### Creating an Invitation Link (as User)

```php
use App\Models\LineSignupInvitation;

$invitation = LineSignupInvitation::create([
    'inviter_user_id' => auth()->id(),
    'referral_code' => auth()->user()->affiliate->referral_code,
    'max_uses' => 100,
    'expires_at' => now()->addDays(30),
]);

$invitationUrl = $invitation->getUrl();
// https://your-domain.com/line/membership/invitation/{token}
```

### Checking Signup Analytics

```php
use App\Models\LineSignupSession;

// Get completion rate
$total = LineSignupSession::count();
$completed = LineSignupSession::where('status', 'completed')->count();
$rate = ($total > 0) ? ($completed / $total) * 100 : 0;

echo "Completion Rate: {$rate}%";
```

### Starting Signup from LINE Message

User sends message: `สมัครสมาชิก` or `signup`

System responds with:
1. Welcome Flex Message
2. Starts new session
3. Moves to first step (Name input)

## 📊 Analytics Metrics

### Available Metrics

1. **Total Sessions** - จำนวน session ทั้งหมด
2. **Completed Sessions** - จำนวน session ที่สำเร็จ
3. **Abandoned Sessions** - จำนวน session ที่ยกเลิก
4. **Active Sessions** - จำนวน session ที่กำลังดำเนินการ
5. **Completion Rate** - อัตราความสำเร็จ (%)
6. **Average Time** - เวลาเฉลี่ยในการสมัคร
7. **Step-by-Step Funnel** - Conversion rate แต่ละขั้นตอน
8. **Daily Signups** - จำนวนการสมัครรายวัน
9. **Top Performers** - ผู้แนะนำที่มียอดสมัครสูงสุด

## 🛠️ Customization

### Adding New Step

1. Add step name to `LineSignupSession::STEPS` array
2. Create handler method in `LineMembershipSignupService`
3. Create Flex Message template in `LineSignupFlexMessageService`
4. Update progress bar calculation

### Customizing Flex Messages

```php
// In LineSignupFlexMessageService.php

public function buildCustomStepMessage(): array
{
    return [
        'type' => 'flex',
        'altText' => 'ข้อความของคุณ',
        'contents' => [
            // Your Flex Message JSON here
        ],
    ];
}
```

### Adding Custom Rewards

```php
// In LineMembershipSignupService.php

protected function grantSignupRewards(LineSignupSession $session, User $user): void
{
    // Add your custom reward logic
    LineSignupReward::create([
        'user_id' => $user->id,
        'session_id' => $session->id,
        'reward_type' => 'custom',
        'reward_name' => 'Special Bonus',
        'reward_amount' => 500,
        'status' => LineSignupReward::STATUS_GRANTED,
        'granted_at' => now(),
    ]);
}
```

## 🎨 UI/UX Highlights

### Design Principles
- ✅ Mobile-first approach
- ✅ Progress indicators ในทุกขั้นตอน
- ✅ Clear call-to-action buttons
- ✅ Helpful error messages
- ✅ Visual feedback for success/error
- ✅ Consistent color scheme (Green #1DB446)
- ✅ Proper spacing and padding
- ✅ Icons for visual cues

### Color Palette
- **Primary Green**: #1DB446 (success, buttons)
- **Warning Yellow**: #FFF9E6 (background), #8B7500 (text)
- **Info Blue**: #E8F5E9 (background), #2E7D32 (text)
- **Alert Orange**: #FFF3E0 (background), #E65100 (text)
- **Error Red**: #FF6B6B

## 🔒 Security Features

- OTP verification with expiration (5 minutes)
- Maximum OTP attempts (5 tries)
- Session expiration (24 hours of inactivity)
- Rate limiting on webhook endpoints
- Encrypted LINE access tokens
- Validation on all input fields
- Prevention of duplicate emails/phones

## 🐛 Debugging

### View Session Progress
```
https://your-domain.com/line/membership/progress/{sessionToken}
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Webhook Testing
Use LINE Bot Designer or Postman to send test events

## 📈 Performance Optimization

- JSON columns for flexible data storage
- Indexed fields for fast queries
- Cached template rendering
- Batch operations for analytics
- Lazy loading of relationships
- Query optimization with eager loading

## 🎉 Success Criteria

สมัครสมาชิกสำเร็จเมื่อ:
1. ✅ Session status = 'completed'
2. ✅ User account created
3. ✅ Affiliate account created
4. ✅ All validation passed
5. ✅ Rewards granted
6. ✅ Welcome message sent

## 🤝 Integration with Existing Systems

### User System
- สร้าง User account อัตโนมัติ
- เชื่อมกับ LINE account
- Verify email & phone

### Affiliate System
- สร้าง Affiliate account
- เชื่อมกับ parent affiliate (จากรหัสแนะนำ)
- Generate unique referral code

### Wallet System (Optional)
- สร้าง wallet
- เพิ่มโบนัส welcome

### Notification System
- ส่ง LINE notification
- ส่ง email confirmation (ถ้ามี)

## 📞 Support

ถ้ามีปัญหาหรือคำถาม:
1. ตรวจสอบ logs ใน `storage/logs/laravel.log`
2. ดูที่ admin dashboard `/admin/line-membership-signup`
3. ตรวจสอบ webhook logs
4. ตรวจสอบ LINE OA settings

## 🎯 Future Enhancements

- [ ] LIFF app integration
- [ ] LINE Login quick signup
- [ ] Social proof (แสดงจำนวนคนที่สมัครแล้ว)
- [ ] Gamification (badges, levels)
- [ ] A/B testing for messages
- [ ] Multi-language support
- [ ] Rich Menu integration
- [ ] Push notification reminders
- [ ] Video tutorials in chat
- [ ] Voice message support

## 📄 License

Proprietary - Thaiprompt Affiliate System

---

Made with ❤️ by Claude AI Assistant
