# 🔮 คู่มือการใช้งานระบบดูดวงออนไลน์ผ่าน Facebook Messenger

> ระบบดูดวงออนไลน์ที่ผสานเทคโนโลยี AI เข้ากับ Facebook Messenger Platform

**เวอร์ชัน**: 1.0.0
**อัปเดตล่าสุด**: 2026-01-29
**ผู้พัฒนา**: Thaiprompt-Affiliate Team

---

## 📋 สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [คุณสมบัติหลัก](#คุณสมบัติหลัก)
3. [สถาปัตยกรรมระบบ](#สถาปัตยกรรมระบบ)
4. [การติดตั้งและตั้งค่า](#การติดตั้งและตั้งค่า)
5. [การตั้งค่า Facebook App](#การตั้งค่า-facebook-app)
6. [การตั้งค่า AI Providers](#การตั้งค่า-ai-providers)
7. [การใช้งานระบบ](#การใช้งานระบบ)
8. [การทดสอบระบบ](#การทดสอบระบบ)
9. [คำถามที่พบบ่อย](#คำถามที่พบบ่อย)
10. [การแก้ไขปัญหา](#การแก้ไขปัญหา)

---

## ภาพรวมระบบ

ระบบดูดวงออนไลน์เป็นฟีเจอร์ที่ให้ผู้ใช้งาน Facebook สามารถขอคำทำนายผ่าน Facebook Comment หรือ Private Message โดยใช้ AI หลายรูปแบบในการวิเคราะห์และให้คำทำนาย

### กลไกการทำงาน

```
ผู้ใช้ Comment "ดูดวง" + คำถาม 3 ข้อ
         ↓
Facebook Webhook ส่งข้อมูลมาที่ระบบ
         ↓
ระบบวิเคราะห์คำถามและดึงข้อมูลผู้ใช้
         ↓
ส่งไป AI Provider (Gemini/Groq/Qwen/OpenRouter)
         ↓
ได้รับคำทำนายจาก AI
         ↓
บันทึกลงฐานข้อมูล + ส่งกลับให้ผู้ใช้
         ↓
(ถ้าครบโควต้า) แสดง QR Code ชำระเงิน
```

---

## คุณสมบัติหลัก

### ✨ ฟีเจอร์หลัก

- ✅ **Facebook Integration**: รองรับทั้ง Comment และ Private Message
- ✅ **Multi-AI Support**: รองรับ 4 AI Providers (Gemini, Groq, Qwen, OpenRouter)
- ✅ **ไม่ต้องสมัครสมาชิก**: ผู้ใช้ที่ไม่ได้สมัครก็ใช้งานได้ (แต่จะไม่มีประวัติ)
- ✅ **โควต้าฟรีต่อวัน**: กำหนดจำนวนครั้งฟรีได้ (ค่าเริ่มต้น 3 ครั้ง/วัน)
- ✅ **ระบบชำระเงิน**: แสดง QR Code สำหรับชำระเงิน
- ✅ **หมวดหมู่การทำนาย**: 6 หมวดหมู่ (ความรัก, การเงิน, สุขภาพ, การงาน, ครอบครัว, โชคลาภ)
- ✅ **Dashboard แอดมิน**: จัดการทุกอย่างผ่าน Admin Panel
- ✅ **Export CSV**: ส่งออกประวัติการทำนายเป็น CSV
- ✅ **สถิติแบบ Real-time**: ดูสถิติการใช้งานแบบเรียลไทม์

### 🎯 AI Providers ที่รองรับ

| Provider | Model | ราคา | ความเร็ว | คุณภาพ |
|----------|-------|------|----------|--------|
| **Gemini** ⭐ | gemini-1.5-flash | ฟรี | เร็วมาก | ดีมาก |
| **Groq** | llama-3.3-70b-versatile | ฟรี | เร็วที่สุด | ดี |
| **Qwen** | Qwen/Qwen2.5-72B | ฟรี (HF) | ปานกลาง | ดีมาก |
| **OpenRouter** | หลายรุ่น | มีค่าใช้จ่าย | ขึ้นกับรุ่น | ดีเยี่ยม |

**แนะนำ**: Gemini (ฟรี + คุณภาพดี + เร็ว)

---

## สถาปัตยกรรมระบบ

### Database Schema

**ตารางหลัก 3 ตาราง:**

```
fortune_telling_settings (ตั้งค่าระบบ)
├── facebook_app_id
├── facebook_app_secret
├── facebook_page_id
├── facebook_access_token
├── ai_provider
├── ai_model
├── ai_api_key
├── max_free_readings
├── price_per_reading
└── payment_qr_image

fortune_categories (หมวดหมู่)
├── name
├── slug
├── icon
├── color
├── description
├── prompt_context
└── example_questions

fortune_readings (ประวัติการทำนาย)
├── facebook_user_id
├── facebook_user_name
├── user_id (nullable)
├── questions (JSON)
├── ai_provider
├── ai_model
├── ai_response
├── tokens_used
├── is_paid
├── amount_paid
└── paid_at
```

### Services

**FortuneAIService** (`app/Services/FortuneAIService.php`)
- จัดการการเรียก API ของ AI Providers
- รองรับ 4 providers พร้อมกันโดยใช้ match expression
- คำนวณ tokens และราคา

**FacebookWebhookService** (`app/Services/FacebookWebhookService.php`)
- จัดการ Webhook events จาก Facebook
- ดึงข้อมูล user profile และ posts
- ส่งข้อความกลับผ่าน Messenger API
- ตรวจสอบโควต้าฟรี

---

## การติดตั้งและตั้งค่า

### ขั้นตอนที่ 1: ติดตั้งระบบ

```bash
# 1. รัน migrations
php artisan migrate

# 2. รัน seeders (สร้างข้อมูลเริ่มต้น)
php artisan db:seed --class=FortuneTellingSettingSeeder
php artisan db:seed --class=FortuneCategorySeeder

# 3. ตรวจสอบว่าข้อมูลถูกสร้างแล้ว
php artisan tinker
>>> \App\Models\FortuneTellingSetting::first()
>>> \App\Models\FortuneCategory::count()
```

### ขั้นตอนที่ 2: เข้าถึง Admin Panel

1. เข้าสู่ระบบ Admin: `https://yourdomain.com/admin`
2. ไปที่เมนู **"ดูดวงออนไลน์" 🔮** ในแถบด้านข้าง
3. คลิก **"ตั้งค่าระบบ"**

---

## การตั้งค่า Facebook App

### ขั้นตอนที่ 1: สร้าง Facebook App

1. ไปที่ [Facebook Developers](https://developers.facebook.com/)
2. คลิก **"My Apps"** → **"Create App"**
3. เลือก **"Business"** → ตั้งชื่อแอพ
4. เพิ่มผลิตภัณฑ์ **"Messenger"**

### ขั้นตอนที่ 2: ตั้งค่า Messenger

1. ไปที่ **Messenger → Settings**
2. ใน **"Access Tokens"** เลือก Facebook Page ที่ต้องการเชื่อมต่อ
3. คัดลอก **Page Access Token**
4. บันทึก Token ไว้ที่ Admin Panel

### ขั้นตอนที่ 3: ตั้งค่า Webhook

1. ไปที่ **Messenger → Settings → Webhooks**
2. คลิก **"Add Callback URL"**
3. ใส่ข้อมูลดังนี้:
   ```
   Callback URL: https://yourdomain.com/webhook/facebook
   Verify Token: [สร้าง random string ใน Admin Panel]
   ```
4. เลือก Subscription Fields:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `feed` (สำหรับ comments)

5. คลิก **"Verify and Save"**

### ขั้นตอนที่ 4: Subscribe Page to Webhook

1. ใน **Webhooks** section
2. เลือก Page ที่ต้องการ
3. คลิก **"Subscribe"**

### ขั้นตอนที่ 5: กรอกข้อมูลใน Admin Panel

1. ไปที่ **ดูดวงออนไลน์ → ตั้งค่าระบบ**
2. เปิดใช้งานระบบ: **เปิด** สวิตช์ "เปิดใช้งานระบบ"
3. กรอกข้อมูล Facebook:
   - **App ID**: จาก Facebook App Dashboard
   - **App Secret**: จาก Settings → Basic
   - **Page ID**: จาก About Page (ตัวเลขใน URL)
   - **Page Access Token**: จากขั้นตอนที่ 2
   - **Verify Token**: ที่สร้างใน Webhook Setup

4. คลิก **"บันทึกการตั้งค่า"**

---

## การตั้งค่า AI Providers

### 1️⃣ Gemini (Google AI) ⭐ แนะนำ

**ขั้นตอน:**
1. ไปที่ [Google AI Studio](https://makersuite.google.com/app/apikey)
2. คลิก **"Get API key"**
3. คัดลอก API Key
4. กรอกใน Admin Panel:
   - **AI Provider**: เลือก `Gemini`
   - **AI Model**: `gemini-1.5-flash` (เร็วและฟรี)
   - **API Key**: วาง API Key

**ราคา**: **ฟรี** (มีโควต้า 15 requests/minute)
**ความเร็ว**: ⚡⚡⚡⚡ (เร็วมาก)
**คุณภาพ**: ⭐⭐⭐⭐⭐ (ดีเยี่ยม)

### 2️⃣ Groq

**ขั้นตอน:**
1. ไปที่ [Groq Console](https://console.groq.com/)
2. สมัครสมาชิก (ฟรี)
3. ไปที่ **API Keys** → สร้าง Key ใหม่
4. กรอกใน Admin Panel:
   - **AI Provider**: เลือก `Groq`
   - **AI Model**: `llama-3.3-70b-versatile`
   - **API Key**: วาง API Key

**ราคา**: **ฟรี** (มีโควต้า)
**ความเร็ว**: ⚡⚡⚡⚡⚡ (เร็วที่สุด - 500+ tokens/sec)
**คุณภาพ**: ⭐⭐⭐⭐ (ดีมาก)

### 3️⃣ Qwen (HuggingFace)

**ขั้นตอน:**
1. ไปที่ [HuggingFace Settings](https://huggingface.co/settings/tokens)
2. สร้าง **Access Token** (แบบ Read)
3. กรอกใน Admin Panel:
   - **AI Provider**: เลือก `Qwen`
   - **AI Model**: `Qwen/Qwen2.5-72B-Instruct`
   - **API Key**: วาง Token

**ราคา**: **ฟรี** (ใช้ HuggingFace Inference API)
**ความเร็ว**: ⚡⚡⚡ (ปานกลาง)
**คุณภาพ**: ⭐⭐⭐⭐⭐ (ดีเยี่ยม - เก่งภาษาจีน/ไทย)

### 4️⃣ OpenRouter

**ขั้นตอน:**
1. ไปที่ [OpenRouter](https://openrouter.ai/)
2. สมัครสมาชิก
3. เติมเงิน (Credits)
4. สร้าง API Key
5. กรอกใน Admin Panel:
   - **AI Provider**: เลือก `OpenRouter`
   - **AI Model**: `anthropic/claude-3-sonnet` (หรือรุ่นอื่นๆ)
   - **API Key**: วาง API Key

**ราคา**: **มีค่าใช้จ่าย** (ขึ้นกับ model ที่เลือก)
**ความเร็ว**: ⚡⚡⚡⚡ (ขึ้นกับ model)
**คุณภาพ**: ⭐⭐⭐⭐⭐ (ดีที่สุด - หลายรุ่นให้เลือก)

### ทดสอบการเชื่อมต่อ AI

1. กรอกข้อมูล AI Provider
2. คลิกปุ่ม **"🧪 ทดสอบการเชื่อมต่อ AI"**
3. ถ้าสำเร็จจะแสดงข้อความทดสอบจาก AI
4. ถ้าล้มเหลวให้ตรวจสอบ API Key

---

## การใช้งานระบบ

### สำหรับผู้ดูแลระบบ (Admin)

#### 1. จัดการหมวดหมู่การทำนาย

**เข้าสู่หน้า**: ดูดวงออนไลน์ → หมวดหมู่การทำนาย

**หมวดหมู่เริ่มต้น 6 หมวด:**
- 💕 **ความรัก**: เรื่องความสัมพันธ์และความรัก
- 💰 **การเงิน**: เรื่องการเงินและความมั่งคั่ง
- 🏥 **สุขภาพ**: เรื่องสุขภาพกายและใจ
- 💼 **การงาน**: เรื่องหน้าที่การงาน
- 👨‍👩‍👧‍👦 **ครอบครัว**: เรื่องครอบครัวและคนใกล้ชิด
- 🍀 **โชคลาภ**: เรื่องโชคลาภและดวงชะตา

**การจัดการ:**
- **สร้างใหม่**: กำหนดชื่อ, ไอคอน, สี, คำอธิบาย
- **แก้ไข**: เปลี่ยน Prompt Context เพื่อปรับคำทำนาย
- **ลบ**: ลบหมวดหมู่ที่ไม่ใช้แล้ว
- **เปิด/ปิด**: ควบคุมการแสดงผล

#### 2. ดูประวัติการทำนาย

**เข้าสู่หน้า**: ดูดวงออนไลน์ → ประวัติการทำนาย

**ข้อมูลที่แสดง:**
- 📊 **สถิติ**: จำนวนทั้งหมด, วันนี้, ชำระเงิน, ฟรี
- 🔍 **กรองข้อมูล**: ตาม AI Provider, สถานะ, วันที่
- 📥 **Export CSV**: ส่งออกเป็นไฟล์ Excel
- 👤 **รายละเอียด**: ดูข้อมูลผู้ใช้, คำถาม, คำทำนาย

#### 3. ตั้งค่าการใช้งาน

**โควต้าฟรี:**
- กำหนดจำนวนครั้งฟรีต่อวัน (ค่าเริ่มต้น: 3 ครั้ง)
- นับต่อผู้ใช้ Facebook แยกกัน (ไม่ต้องสมัครสมาชิก)

**ราคา:**
- กำหนดราคาต่อ 1 คำทำนาย (เมื่อหมดโควต้า)
- อัปโหลดรูป QR Code สำหรับชำระเงิน

**พฤติกรรม:**
- **ตอบกลับใน Comment**: เปิด/ปิด การตอบใน Comment
- **บังคับสมัครสมาชิก**: เปิด/ปิด การบังคับให้สมัครก่อนใช้

### สำหรับผู้ใช้งาน (Facebook Users)

#### วิธีใช้งาน

**1. ผ่าน Facebook Comment:**

```
Comment ในโพสต์:
ดูดวง
1. [คำถามข้อ 1]
2. [คำถามข้อ 2]
3. [คำถามข้อ 3]
```

**ตัวอย่าง:**
```
ดูดวง
1. ความรักของฉันในปีนี้จะเป็นอย่างไร
2. การเงินของฉันจะดีขึ้นไหม
3. ฉันควรเปลี่ยนงานไหม
```

**2. ผ่าน Private Message:**

ส่งข้อความส่วนตัวถึง Facebook Page:
```
ดูดวง
1. [คำถามข้อ 1]
2. [คำถามข้อ 2]
3. [คำถามข้อ 3]
```

#### ขั้นตอนการทำงาน

1. **ผู้ใช้ส่งคำขอ** → ระบบรับข้อมูล
2. **ตรวจสอบโควต้า** → ฟรี 3 ครั้ง/วัน
3. **ดึงข้อมูลผู้ใช้** → ชื่อ, โปรไฟล์, โพสต์ล่าสุด (ถ้ามีสิทธิ์)
4. **AI วิเคราะห์** → สร้างคำทำนาย
5. **ส่งกลับผู้ใช้** → Comment Reply หรือ Private Message
6. **บันทึกประวัติ** → เก็บลงฐานข้อมูล

#### ถ้าหมดโควต้าฟรี

ระบบจะแสดง:
- 📊 **จำนวนที่ใช้ไป**: คุณใช้ไปแล้ว X ครั้ง
- 💰 **ราคา**: ต่อ 1 คำทำนาย = X บาท
- 📱 **QR Code**: สำหรับชำระเงิน PromptPay
- 📧 **วิธีชำระ**: โอนแล้วแจ้งหลักฐาน

---

## การทดสอบระบบ

### Test 1: ทดสอบ Webhook Verification

```bash
# ใช้ curl ทดสอบ
curl "https://yourdomain.com/webhook/facebook?hub.mode=subscribe&hub.verify_token=YOUR_VERIFY_TOKEN&hub.challenge=test123"

# ผลลัพธ์ที่ต้องการ:
test123
```

### Test 2: ทดสอบการเชื่อมต่อ AI

1. ไปที่ **ดูดวงออนไลน์ → ตั้งค่าระบบ**
2. คลิกปุ่ม **"🧪 ทดสอบการเชื่อมต่อ AI"**
3. ตรวจสอบผลลัพธ์

### Test 3: ทดสอบการส่งคำขอจริง

1. ไปที่ Facebook Page
2. สร้างโพสต์ทดสอบ
3. Comment:
   ```
   ดูดวง
   1. วันนี้จะโชคดีไหม
   2. ควรจะทำอะไรดี
   3. มีอะไรที่ควรระวัง
   ```
4. รอ 5-10 วินาที
5. ระบบจะตอบกลับใน Comment

### Test 4: ทดสอบ Private Message

1. ส่งข้อความส่วนตัวถึง Page
2. พิมพ์คำสั่งเดียวกับใน Comment
3. รอรับคำตอบ

---

## คำถามที่พบบ่อย

### Q: ทำไมระบบไม่ตอบกลับ?

**A:** ตรวจสอบ:
1. ✅ เปิดใช้งานระบบใน Admin Panel หรือยัง?
2. ✅ Facebook Webhook Subscribe แล้วหรือยัง?
3. ✅ Page Access Token ถูกต้องและไม่หมดอายุหรือ?
4. ✅ AI Provider API Key ใช้งานได้หรือไม่?
5. ✅ ดูใน `storage/logs/laravel.log` มี error หรือไม่?

### Q: คำทำนายภาษาอังกฤษออกมา ต้องทำอย่างไร?

**A:** แก้ไข Prompt Template:
1. ไปที่ **ตั้งค่าระบบ**
2. แก้ **"คำสั่งพื้นฐานสำหรับ AI"**
3. เพิ่ม: `"คุณต้องตอบเป็นภาษาไทยเท่านั้น"`
4. บันทึก

### Q: ต้องการเปลี่ยน AI Provider ต้องทำอย่างไร?

**A:**
1. ไปที่ **ตั้งค่าระบบ**
2. เลือก AI Provider ใหม่
3. กรอก API Key ใหม่
4. คลิก **"ทดสอบการเชื่อมต่อ"**
5. บันทึก

### Q: จะเพิ่มหมวดหมู่การทำนายได้ไหม?

**A:** ได้!
1. ไปที่ **หมวดหมู่การทำนาย**
2. คลิก **"+ เพิ่มหมวดหมู่ใหม่"**
3. กรอกข้อมูล:
   - ชื่อหมวดหมู่ (เช่น "การศึกษา")
   - ไอคอน (เช่น "📚")
   - สี (เช่น "#3B82F6")
   - Prompt Context สำหรับปรับคำทำนาย
4. บันทึก

### Q: ผู้ใช้สามารถดูประวัติการทำนายได้ไหม?

**A:**
- **ไม่สมัครสมาชิก**: ไม่มีประวัติ (ไม่บันทึก user_id)
- **สมัครสมาชิกแล้ว**: มีประวัติครบถ้วน
- แอดมินดูได้ทั้งหมดผ่าน **ประวัติการทำนาย**

### Q: จะกำหนดราคาต่างกันตามหมวดหมู่ได้ไหม?

**A:** ในเวอร์ชัน 1.0 ยังไม่รองรับ (ราคาเดียวสำหรับทุกหมวดหมู่)
แต่สามารถพัฒนาเพิ่มได้ในอนาคต

### Q: รองรับกี่คำถามต่อครั้ง?

**A:** ปัจจุบันรองรับ **3 คำถาม** ต่อ 1 คำขอ
แต่สามารถแก้ไขใน `FacebookWebhookService.php` เพื่อปรับจำนวนได้

---

## การแก้ไขปัญหา

### ปัญหา: Webhook Verification Failed

**อาการ:**
```
Error: The callback URL or verify token couldn't be verified
```

**วิธีแก้:**
1. ตรวจสอบ Callback URL: `https://yourdomain.com/webhook/facebook`
2. ตรวจสอบ Verify Token ต้องตรงกัน
3. ตรวจสอบ SSL Certificate (ต้องเป็น HTTPS)
4. ลองเรียก URL โดยตรง:
   ```
   https://yourdomain.com/webhook/facebook?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test
   ```
5. ต้องได้ผลลัพธ์: `test`

### ปัญหา: AI ไม่ตอบหรือ Error

**อาการ:**
```
Error calling AI: 401 Unauthorized
```

**วิธีแก้:**
1. ตรวจสอบ API Key ถูกต้องหรือไม่
2. ตรวจสอบว่า API Key ไม่หมดอายุ
3. ตรวจสอบโควต้า (ถ้าใช้ฟรี)
4. ลองเปลี่ยน AI Provider ดู
5. ดู logs: `tail -f storage/logs/laravel.log`

### ปัญหา: Facebook Access Token Expired

**อาการ:**
```
Error: Invalid OAuth access token
```

**วิธีแก้:**
1. ไปที่ Facebook Developers
2. สร้าง Page Access Token ใหม่
3. อัปเดตใน Admin Panel
4. บันทึก

### ปัญหา: ไม่มีการบันทึกลงฐานข้อมูล

**วิธีแก้:**
1. ตรวจสอบ migration รันหรือยัง:
   ```bash
   php artisan migrate:status
   ```
2. ตรวจสอบ foreign key constraints
3. ดู database logs
4. ลอง debug ด้วย:
   ```bash
   php artisan tinker
   >>> \App\Models\FortuneReading::all()
   ```

### ปัญหา: QR Code ไม่แสดง

**วิธีแก้:**
1. ตรวจสอบว่าอัปโหลดรูป QR ไว้หรือยัง
2. ตรวจสอบ path: `storage/app/public/fortune/`
3. สร้าง symbolic link:
   ```bash
   php artisan storage:link
   ```
4. ทดสอบเข้าถึง: `https://yourdomain.com/storage/fortune/qr-code.jpg`

---

## ไฟล์และโครงสร้างที่เกี่ยวข้อง

### Migrations

```
database/migrations/
├── 2026_01_29_110517_create_fortune_telling_settings_table.php
├── 2026_01_29_110518_create_fortune_categories_table.php
└── 2026_01_29_110520_create_fortune_readings_table.php
```

### Models

```
app/Models/
├── FortuneTellingSetting.php    # Singleton pattern
├── FortuneCategory.php           # หมวดหมู่
└── FortuneReading.php            # ประวัติ
```

### Services

```
app/Services/
├── FortuneAIService.php          # AI integration (4 providers)
└── FacebookWebhookService.php    # Facebook Messenger
```

### Controllers

```
app/Http/Controllers/
├── FacebookWebhookController.php              # Webhook handler
└── Admin/
    ├── FortuneSettingsController.php          # ตั้งค่า
    ├── FortuneCategoriesController.php        # หมวดหมู่
    └── FortuneReadingsController.php          # ประวัติ
```

### Views

```
resources/views/admin/fortune/
├── settings/
│   └── index.blade.php           # หน้าตั้งค่าระบบ
├── categories/
│   ├── index.blade.php           # รายการหมวดหมู่
│   ├── create.blade.php          # สร้างหมวดหมู่
│   └── edit.blade.php            # แก้ไขหมวดหมู่
└── readings/
    ├── index.blade.php           # รายการประวัติ
    └── show.blade.php            # รายละเอียด
```

### Routes

```php
// routes/web.php - Webhook
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::match(['GET', 'POST'], '/facebook', [FacebookWebhookController::class, 'webhook']);
});

// routes/admin.php - Admin Panel
Route::prefix('fortune')->name('fortune.')->group(function () {
    Route::get('/settings', [FortuneSettingsController::class, 'index']);
    Route::resource('categories', FortuneCategoriesController::class);
    Route::resource('readings', FortuneReadingsController::class);
});
```

---

## การพัฒนาต่อยอด

### ฟีเจอร์ที่อาจเพิ่มในอนาคต

- 📱 **Mobile App Integration**: รองรับ LINE, Telegram
- 💳 **ชำระเงินอัตโนมัติ**: เชื่อมต่อ Payment Gateway
- 📊 **Analytics Dashboard**: สถิติเชิงลึกและ insights
- 🎨 **Customizable Templates**: แม่แบบคำทำนายที่ปรับแต่งได้
- 🌍 **Multi-language**: รองรับหลายภาษา
- ⭐ **Rating System**: ให้ผู้ใช้ให้คะแนนคำทำนาย
- 🔔 **Push Notifications**: แจ้งเตือนเมื่อมีคำทำนายใหม่
- 📅 **Scheduled Readings**: กำหนดเวลาทำนายล่วงหน้า
- 🎁 **Referral System**: แนะนำเพื่อนได้โควต้าฟรี

### API Extensions

```php
// ตัวอย่าง API endpoint ที่อาจเพิ่ม
Route::prefix('api/v1/fortune')->group(function () {
    Route::post('/readings', 'FortuneAPIController@create');
    Route::get('/readings/{id}', 'FortuneAPIController@show');
    Route::get('/categories', 'FortuneAPIController@categories');
    Route::post('/webhooks/payment', 'FortuneAPIController@paymentWebhook');
});
```

---

## สนับสนุนและติดต่อ

- 📧 **Email**: support@thaiprompt-affiliate.com
- 💬 **Facebook**: [TP-Affiliate Facebook Page]
- 📚 **Documentation**: https://docs.thaiprompt-affiliate.com
- 🐛 **Bug Reports**: GitHub Issues

---

## เวอร์ชันและการอัปเดต

### v1.0.0 (2026-01-29)
- ✨ เปิดตัวระบบครั้งแรก
- ✅ รองรับ 4 AI Providers
- ✅ Facebook Comment + Private Message
- ✅ Admin Panel สมบูรณ์
- ✅ Export CSV
- ✅ 6 หมวดหมู่เริ่มต้น

### Roadmap
- **v1.1.0**: เพิ่ม LINE OA integration
- **v1.2.0**: Payment Gateway automation
- **v1.3.0**: Advanced Analytics
- **v2.0.0**: Multi-platform support

---

## License

Copyright © 2026 Thaiprompt-Affiliate. All rights reserved.

ระบบนี้เป็นส่วนหนึ่งของ TP-Affiliate Platform และอยู่ภายใต้ลิขสิทธิ์เดียวกัน

---

**สร้างด้วยความตั้งใจโดย ทีมพัฒนา Thaiprompt-Affiliate 💜**

*"ดูดวงด้วย AI - แม่นยำ รวดเร็ว ทันสมัย"* 🔮✨
