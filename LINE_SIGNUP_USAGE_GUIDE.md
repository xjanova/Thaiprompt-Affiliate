# 🚀 LINE Membership Signup - คู่มือการใช้งาน

## 📋 สารบัญ

1. [การติดตั้งและเริ่มต้น](#การติดตั้งและเริ่มต้น)
2. [การตั้งค่า LINE OA](#การตั้งค่า-line-oa)
3. [การสร้าง Rich Menu](#การสร้าง-rich-menu)
4. [การใช้งาน AI Assistant](#การใช้งาน-ai-assistant)
5. [การจัดการ Templates](#การจัดการ-templates)
6. [การดู Analytics](#การดู-analytics)
7. [การแก้ไขปัญหา](#การแก้ไขปัญหา)

---

## 🔧 การติดตั้งและเริ่มต้น

### 1. Run Database Migrations

```bash
php artisan migrate
```

### 2. Seed Template Data

```bash
php artisan db:seed --class=LineSignupTemplateSeeder
```

### 3. Setup Rich Menu

```bash
# สร้าง Rich Menu และตั้งเป็น default
php artisan line:setup-signup-richmenu --set-default
```

### 4. ตั้งค่า Environment Variables

```env
# LINE OA Settings
LINE_CHANNEL_ACCESS_TOKEN=your_access_token_here
LINE_CHANNEL_SECRET=your_channel_secret_here

# LINE Webhook URL
LINE_WEBHOOK_URL=https://your-domain.com/api/webhook/line-membership-signup
```

### 5. ตั้งค่า Webhook ใน LINE Developers Console

1. ไปที่ https://developers.line.biz/console/
2. เลือก Provider และ Channel ของคุณ
3. ไปที่ Messaging API settings
4. ตั้งค่า Webhook URL: `https://your-domain.com/api/webhook/line-membership-signup`
5. Enable "Use webhook"
6. Disable "Auto-reply messages" (ถ้าต้องการใช้ AI reply)

---

## 📱 การตั้งค่า LINE OA

### เปิดใช้งานฟีเจอร์

1. เข้าสู่ระบบ Admin
2. ไปที่ **Admin > LINE OA Management**
3. กรอก Channel Access Token และ Channel Secret
4. กด "Test Connection" เพื่อตรวจสอบการเชื่อมต่อ
5. กด "Save Settings"

### ตั้งค่า Greeting Message

```
👋 สวัสดีค่ะ! ยินดีต้อนรับสู่ Thaiprompt Affiliate

✨ สมัครสมาชิกฟรี รับโบนัสต้อนรับทันที!
💰 สร้างรายได้ง่ายๆ ด้วยระบบ Affiliate Marketing

กดที่เมนูด้านล่างเพื่อเริ่มต้น หรือพิมพ์ "สมัครสมาชิก" ได้เลย!
```

---

## 🎨 การสร้าง Rich Menu

### สร้างด้วย Command (แนะนำ)

```bash
# สร้าง Rich Menu ใหม่
php artisan line:setup-signup-richmenu

# สร้างและตั้งเป็น default ทันที
php artisan line:setup-signup-richmenu --set-default

# บังคับสร้างใหม่ทับของเดิม
php artisan line:setup-signup-richmenu --force --set-default
```

### สร้างผ่าน Admin Dashboard

1. ไปที่ **Admin > LINE Bot > Rich Menus**
2. กด "Create New Rich Menu"
3. อัพโหลดรูปภาพ (2500x1686 px)
4. กำหนด clickable areas:
   - **สมัครสมาชิก** (ใหญ่ซ้ายบน) → Postback: `action=start_signup`
   - **เส้นทางเศรษฐี** (ขวาบน) → Postback: `action=wealth_path`
   - **วิธีการทำงาน** (ล่างซ้าย) → Postback: `action=how_it_works`
   - **เรื่องสำเร็จ** (ล่างกลาง) → Postback: `action=success_stories`
   - **ติดต่อเรา** (ล่างขวา) → Message: `ติดต่อทีมงาน`

### ขนาดและ Layout Rich Menu

```
┌───────────────┬───────────────┐
│               │               │
│  สมัครสมาชิก   │ เส้นทางเศรษฐี  │  (843px สูง)
│   (PRIMARY)   │               │
├───────┬───────┼───────────────┤
│ วิธี   │ เรื่อง │   ติดต่อเรา   │
│ ทำงาน │สำเร็จ  │               │  (843px สูง)
└───────┴───────┴───────────────┘
```

---

## 🤖 การใช้งาน AI Assistant

### ตัวอย่างการสนทนา

**ผู้ใช้:** สมัครสมาชิก

**AI:** 👋 ยินดีต้อนรับค่ะ! ระบบสมัครสมาชิกของเราใช้เวลาแค่ 2-3 นาที เท่านั้น!

คุณรู้ไหมว่า สมาชิกของเรา 50% สามารถสร้างรายได้ภายใน 30 วันแรก! 💰

เริ่มกันเลยไหมคะ? 🚀

---

**ผู้ใช้:** ได้เงินเท่าไหร่

**AI:** 💰 รายได้ขึ้นอยู่กับจำนวนคนที่คุณแนะนำค่ะ!

ตัวอย่างเฉลี่ย:
- 5 คน = 1,000-2,000 บาท/เดือน
- 10 คน = 3,000-5,000 บาท/เดือน
- 20 คน = 8,000-15,000 บาท/เดือน
- 50+ คน = 25,000+ บาท/เดือน

ยิ่งแชร์มาก ยิ่งได้มาก! พร้อมเริ่มแล้วหรือยัง? 🚀

---

### Conversation Flow

```
START
  ↓
WELCOME (พร้อม Quick Reply)
  ↓
NAME INPUT (AI ช่วยตรวจสอบ)
  ↓
EMAIL INPUT (AI ตรวจรูปแบบ)
  ↓
PHONE INPUT (AI ส่ง OTP)
  ↓
OTP VERIFICATION (AI นับจำนวนครั้ง)
  ↓
PASSWORD INPUT (AI แนะนำความปลอดภัย)
  ↓
REFERRAL CODE (Optional, AI อธิบายประโยชน์)
  ↓
CONFIRMATION (AI สรุปข้อมูล)
  ↓
SUCCESS! (AI ส่งข้อมูลสมาชิก + โบนัส)
```

### AI Responses สำหรับแต่ละ Step

#### Welcome Step
```
✨ ยินดีต้อนรับสู่ระบบสมัครสมาชิก!

เราจะช่วยคุณสมัครสมาชิกภายใน 2 นาที

คุณรู้ไหมว่า สมาชิกของเรามากกว่า 50% สามารถสร้างรายได้เสริมได้
ภายใน 30 วันแรก! 💰

พร้อมเริ่มต้นเส้นทางสู่อิสรภาพทางการเงินกันไหมคะ? 🚀
```

#### Name Step
```
📝 กรุณากรอกชื่อ-นามสกุล ของคุณ

ชื่อนี้จะใช้สำหรับ:
- เอกสารทางการ
- ใบรับรองสมาชิก
- ระบบการจ่ายเงิน

💡 กรอกชื่อจริงของคุณนะคะ เพื่อความถูกต้อง
```

#### Email Step
```
📧 กรุณากรอกอีเมล

อีเมลใช้สำหรับ:
✓ เข้าสู่ระบบ
✓ รับข่าวสารและโปรโมชั่น
✓ Reset รหัสผ่าน

🔒 ข้อมูลของคุณปลอดภัย 100%
เราไม่แชร์อีเมลของคุณกับบุคคลที่สาม
```

#### Phone Step
```
📱 กรุณากรอกหมายเลขโทรศัพท์

เบอร์โทรใช้สำหรับ:
✓ ยืนยันตัวตน
✓ รับข้อมูลสำคัญ
✓ ติดต่อฉุกเฉิน

🔐 เราจะส่ง OTP มาทาง LINE
(ไม่ใช่ SMS เพื่อความปลอดภัย)
```

#### OTP Step
```
🔐 ยืนยัน OTP

เราได้ส่งรหัส OTP 6 หลักไปให้คุณแล้ว
กรุณาตรวจสอบข้อความด้านบนนะคะ

⏱ รหัส OTP จะหมดอายุใน 5 นาที
🔄 หากไม่ได้รับ OTP สามารถกดขอใหม่ได้
```

#### Password Step
```
🔒 ตั้งรหัสผ่าน

รหัสผ่านต้องมี:
✓ อย่างน้อย 8 ตัวอักษร
✓ ผสมตัวอักษรและตัวเลข
✓ ไม่ใช้รหัสที่ง่ายเกินไป (123456, password)

💡 ตัวอย่างรหัสผ่านที่ดี:
- MyPass@2025
- Thaiprompt#99

🔐 ห้ามแชร์รหัสผ่านกับใคร!
```

#### Referral Step
```
🎁 รหัสผู้แนะนำ

มีรหัสผู้แนะนำไหมคะ?

ประโยชน์ของการมีผู้แนะนำ:
✅ ได้รับคำแนะนำจากผู้มีประสบการณ์
✅ เข้ากลุ่มไลน์สำหรับสมาชิกใหม่
✅ รับโบนัสต้อนรับเพิ่ม 50%!

หากไม่มี สามารถข้ามขั้นตอนนี้ได้เลย
ระบบจะจับคู่ผู้แนะนำให้อัตโนมัติค่ะ
```

#### Confirmation Step
```
✅ ยืนยันข้อมูล

คุณกำลังจะก้าวเข้าสู่โลกใหม่ของโอกาสทางการเงิน! 🚀

หลังจากยืนยัน คุณจะได้รับ:
✓ รหัสสมาชิก (Member ID)
✓ รหัสแนะนำส่วนตัว (ใช้ชวนเพื่อน)
✓ คะแนนสะสมต้อนรับ 100 คะแนน
✓ เข้าถึงคอร์สเรียนฟรี 3 คอร์ส
✓ eBook "เส้นทางสู่เศรษฐี" ฟรี!

พร้อมยืนยันแล้วไหมคะ? 😊
```

---

## 🎨 การจัดการ Templates

### ดู Templates ทั้งหมด

```bash
# ไปที่ Admin Dashboard
https://your-domain.com/admin/line-membership-signup/templates
```

### สร้าง Template ใหม่

1. ไปที่ **Admin > LINE Membership Signup > Templates**
2. กด "Create New Template"
3. กรอกข้อมูล:
   - **Template Key**: `welcome_new_year` (ไม่มีช่องว่าง)
   - **Template Name**: `Welcome Message สำหรับปีใหม่`
   - **Description**: `ข้อความต้อนรับพิเศษช่วงปีใหม่`
   - **Flex Message JSON**: วาง JSON ของ Flex Message
   - **Variables**: `["user_name", "bonus_amount"]`
4. กด "Save"

### แก้ไข Template

1. คลิกที่ Template ที่ต้องการแก้ไข
2. แก้ไข Flex Message JSON
3. Test ด้วย LINE Bot Designer
4. กด "Update"

### ใช้ Template ในโค้ด

```php
use App\Models\LineSignupTemplate;

// Get template
$template = LineSignupTemplate::where('template_key', 'welcome_hero')->first();

// Render with variables
$message = $template->render([
    'user_name' => 'คุณสมชาย',
]);

// Send via LINE
$lineService->pushMessage($lineUserId, $message);
```

---

## 📊 การดู Analytics

### Dashboard Overview

ไปที่: `https://your-domain.com/admin/line-membership-signup`

คุณจะเห็น:
- **Total Sessions**: จำนวน session ทั้งหมด
- **Completed**: จำนวนที่สมัครสำเร็จ
- **Active**: จำนวนที่กำลังสมัครอยู่
- **Completion Rate**: อัตราความสำเร็จ (%)
- **Daily Signups Chart**: กราฟแสดงจำนวนรายวัน
- **Step Funnel**: Conversion rate แต่ละขั้นตอน
- **Recent Sessions**: รายการล่าสุด
- **Top Referrers**: ผู้แนะนำที่มียอดสูงสุด

### ดูรายละเอียด Session

1. คลิกที่ Session ID
2. จะเห็น:
   - ข้อมูลที่เก็บรวบรวม
   - Step logs (แต่ละขั้นตอน)
   - Conversation history (บทสนทนากับ AI)
   - Rewards ที่ได้รับ

### Export ข้อมูล

```bash
# ผ่าน Admin Dashboard
https://your-domain.com/admin/line-membership-signup/export/sessions?status=completed&start_date=2025-01-01

# จะได้ไฟล์ CSV
```

---

## 🎯 การใช้งานจริง

### สำหรับ Admin

#### 1. ตรวจสอบระบบทุกวัน
- เช็ค Active Sessions
- ดู Completion Rate
- ตรวจสอบ Abandoned Sessions

#### 2. ปรับปรุง Conversion
- ดู Step Funnel → หา bottleneck
- อ่าน Conversation Logs → เข้าใจปัญหา
- แก้ไข AI Prompts หรือ Templates

#### 3. ให้รางวัล
- Grant rewards ให้สมาชิกใหม่
- ส่ง broadcast message พิเศษ

### สำหรับผู้ใช้

#### การสมัครผ่าน LINE
1. Add LINE OA: `@your-line-oa`
2. กดปุ่ม "สมัครสมาชิก" ใน Rich Menu
3. ทำตามขั้นตอนที่ AI แนะนำ
4. รับรหัสสมาชิก + โบนัสทันที!

#### การแชร์ลิงก์เชิญ
1. เข้าสู่ระบบ
2. ไปที่ Dashboard
3. คัดลอก Referral Link
4. แชร์ไปยัง Social Media, LINE, Facebook

---

## 🔧 การแก้ไขปัญหา

### OTP ไม่ถูกส่ง

**สาเหตุ:**
- LINE Service ไม่ทำงาน
- Rate limit exceeded

**แก้ไข:**
1. ตรวจสอบ LINE Channel Access Token
2. เช็ค Rate Limit (5,000 messages/hour)
3. ดู Logs: `tail -f storage/logs/laravel.log`

### Rich Menu ไม่แสดง

**สาเหตุ:**
- Rich Menu ยังไม่ set เป็น default
- LINE API ล่ม

**แก้ไข:**
```bash
# Set Rich Menu เป็น default
php artisan line:setup-signup-richmenu --set-default

# Check Rich Menus
curl -X GET https://api.line.me/v2/bot/richmenu/list \
  -H "Authorization: Bearer {YOUR_CHANNEL_ACCESS_TOKEN}"
```

### AI ไม่ตอบ

**สาเหตุ:**
- AI Service ไม่ทำงาน
- Prompt ผิดพลาด

**แก้ไข:**
1. เช็ค AI Service connection
2. ดู Error logs
3. ใช้ fallback responses

### Session หมดอายุ

**สาเหตุ:**
- ไม่มี activity เกิน 24 ชั่วโมง

**แก้ไข:**
- ผู้ใช้ต้องเริ่มสมัครใหม่
- Admin สามารถ Reset session ได้

### Webhook ไม่ทำงาน

**แก้ไข:**
1. ตรวจสอบ Webhook URL ใน LINE Console
2. ตรวจสอบ SSL Certificate
3. Test webhook ด้วย LINE Bot Designer
4. เช็ค Logs:
```bash
tail -f storage/logs/laravel.log | grep "LINE Membership"
```

---

## 💡 Tips & Best Practices

### 1. Optimize Conversion Rate

- **ใช้ Quick Reply ทุกขั้นตอน** - ง่ายกว่าการพิมพ์
- **AI ต้องเป็นมิตร** - อย่าเป็นทางการมากเกินไป
- **Progress Bar** - ให้เห็นว่าเหลืออีกกี่ขั้นตอน
- **Reward Preview** - บอกว่าจะได้อะไรเมื่อสมัครเสร็จ

### 2. ลด Abandonment Rate

- **ตอบเร็ว** - ไม่ให้รอนานเกิน 2 วินาที
- **OTP ง่าย** - ส่งทาง LINE แทน SMS
- **ข้ามได้** - ให้ข้าม Referral ได้
- **บันทึกความคืบหน้า** - Resume ได้ถ้ากลับมา

### 3. เพิ่ม Engagement

- **Send Follow-ups** - ถ้า abandoned ให้ส่งข้อความเตือน
- **Broadcast News** - ส่งข่าวสารให้สมาชิกใหม่
- **Success Stories** - แชร์เรื่องราวสำเร็จ
- **Onboarding Series** - ส่ง tips การใช้งาน 7 วัน

---

## 📞 การขอความช่วยเหลือ

### Documentation
- Technical Docs: `/LINE_MEMBERSHIP_SIGNUP_README.md`
- API Docs: `/docs/api/line-signup.md`

### Support Channels
- LINE Official: `@thaiprompt-support`
- Email: support@thaiprompt.com
- GitHub Issues: https://github.com/your-repo/issues

### Community
- Facebook Group: Thaiprompt Affiliate Community
- LINE Group: (สำหรับสมาชิก)

---

## 🎓 การเรียนรู้เพิ่มเติม

### LINE Messaging API
- https://developers.line.biz/en/docs/messaging-api/

### Flex Message Simulator
- https://developers.line.biz/flex-simulator/

### Rich Menu Designer
- https://developers.line.biz/console/

---

**สร้างโดย:** Claude AI Assistant
**เวอร์ชั่น:** 1.0.0
**อัพเดทล่าสุด:** 2025-11-12
