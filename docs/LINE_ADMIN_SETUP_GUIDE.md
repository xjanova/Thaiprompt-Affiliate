# 📘 LINE System - Admin Setup Guide

> **คู่มือการตั้งค่าระบบ LINE สำหรับผู้ดูแลระบบ**
> Version: 3.0.0 | อัปเดตล่าสุด: 2025-11-22

---

## 📋 สารบัญ

1. [ภาพรวมระบบ LINE](#ภาพรวมระบบ-line)
2. [ข้อกำหนดเบื้องต้น](#ข้อกำหนดเบื้องต้น)
3. [การสร้าง LINE Official Account](#การสร้าง-line-official-account)
4. [การตั้งค่า Webhook](#การตั้งค่า-webhook)
5. [การตั้งค่าระบบ AI Bot](#การตั้งค่าระบบ-ai-bot)
6. [การสร้าง Rich Menu](#การสร้าง-rich-menu)
7. [การส่ง Broadcast Messages](#การส่ง-broadcast-messages)
8. [การตั้งค่า Voice Message](#การตั้งค่า-voice-message)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 ภาพรวมระบบ LINE

ระบบ LINE ของ Thaiprompt-Affiliate ประกอบด้วย:

### ฟีเจอร์หลัก

- ✅ **LINE Login** - OAuth 2.1 authentication
- ✅ **AI Chatbot** - รองรับ OpenAI, DeepSeek, Claude, Gemini
- ✅ **Hybrid Bot** - Keyword matching + AI fallback
- ✅ **Membership Signup** - AI-powered signup flow พร้อม KYC
- ✅ **Rich Menus** - สร้างเมนูแบบ interactive
- ✅ **Broadcast Messages** - ส่งข้อความหมู่ (รองรับ scheduling)
- ✅ **Flex Messages** - ข้อความแบบ carousel/bubble
- ✅ **Voice Messages** - Speech-to-Text (Google Cloud)
- ✅ **Analytics** - Dashboard สถิติการใช้งาน

---

## ⚙️ ข้อกำหนดเบื้องต้น

### 1. บัญชี LINE

- 📱 **LINE Personal Account** - สำหรับสร้าง LINE Official Account
- 💼 **LINE for Business** - ลงทะเบียนที่ [https://entry-at.line.me/](https://entry-at.line.me/)

### 2. Server Requirements

- ✅ **PHP 8.1+**
- ✅ **MySQL 8.0+**
- ✅ **SSL Certificate** - Webhook ต้องใช้ HTTPS
- ✅ **Public URL** - สำหรับ Webhook (ไม่ใช่ localhost)

### 3. External Services (Optional)

- **Google Cloud** - สำหรับ Speech-to-Text (voice messages)
- **OpenAI/DeepSeek/Claude** - สำหรับ AI chatbot

---

## 🚀 การสร้าง LINE Official Account

### Step 1: สร้าง LINE Official Account

1. ไปที่ [LINE Official Account Manager](https://manager.line.biz/)
2. คลิก **"Create"** → **"Create your LINE Official Account"**
3. กรอกข้อมูล:
   - **Account name**: ชื่อบัญชี (เช่น "TP-Affiliate Bot")
   - **Category**: เลือกหมวดหมู่ธุรกิจ
   - **Description**: คำอธิบายสั้นๆ

4. คลิก **"Create"** เพื่อสร้างบัญชี

### Step 2: สร้าง Messaging API Channel

1. ในหน้า LINE Official Account Manager
2. เลือก Account ที่สร้าง → ไปที่ **"Settings"**
3. เลื่อนลงไปที่ **"Messaging API"**
4. คลิก **"Integrate with Messaging API"**
5. เลือก **Provider** (หรือสร้างใหม่)
6. กรอกข้อมูล:
   - **Channel name**: ชื่อ channel (เช่น "TP-Affiliate API")
   - **Channel description**: คำอธิบาย
   - **Privacy policy URL**: (optional)
   - **Terms of service URL**: (optional)

7. คลิก **"Create"**

### Step 3: ดึงข้อมูล Credentials

หลังสร้าง channel เสร็จ:

1. ไปที่ [LINE Developers Console](https://developers.line.biz/console/)
2. เลือก **Provider** → เลือก **Channel** ที่สร้าง
3. ไปที่แท็บ **"Basic settings"**
4. คัดลอกข้อมูลต่อไปนี้:

```
Channel ID:          12345678
Channel Secret:      abcdef1234567890abcdef1234567890
```

5. ไปที่แท็บ **"Messaging API"**
6. ในส่วน **"Channel access token"**:
   - คลิก **"Issue"** เพื่อสร้าง token
   - คัดลอก **Channel Access Token**

```
Channel Access Token (Long-lived): abcdefghijklmnopqrstuvwxyz1234567890...
```

⚠️ **สำคัญ**: เก็บข้อมูลเหล่านี้ไว้ในที่ปลอดภัย!

---

## 🔧 การตั้งค่า Webhook

### Step 1: ตั้งค่า Webhook URL

1. ไปที่ [LINE Developers Console](https://developers.line.biz/console/)
2. เลือก Channel → แท็บ **"Messaging API"**
3. ในส่วน **"Webhook settings"**:
   - **Webhook URL**: `https://yourdomain.com/api/webhook/line`
   - เปิด **"Use webhook"** = ON
   - เปิด **"Redelivery"** = ON (optional, แนะนำ)

4. คลิก **"Update"**
5. คลิก **"Verify"** เพื่อทดสอบ webhook

✅ ถ้าขึ้น **"Success"** แสดงว่าตั้งค่าถูกต้อง

### Step 2: บันทึกข้อมูลในระบบ

1. Login เข้า Admin Panel (`/admin`)
2. ไปที่ **"LINE OA Settings"** (`/admin/line-oa`)
3. กรอกข้อมูล:

```
Channel ID:              [ใส่ Channel ID จากขั้นตอนที่แล้ว]
Channel Secret:          [ใส่ Channel Secret]
Channel Access Token:    [ใส่ Channel Access Token]

Webhook URL:             https://yourdomain.com/api/webhook/line
```

4. เลือก **Mode**:
   - **Messaging** - สำหรับ chatbot (แนะนำ)
   - **Login** - สำหรับ LINE Login เท่านั้น
   - **Both** - รองรับทั้งสองอย่าง

5. คลิก **"Save"**

### Step 3: ทดสอบ Webhook

1. เปิด LINE app บนมือถือ
2. Add Friend → ค้นหา LINE OA ของคุณ
3. ส่งข้อความทดสอบ เช่น "สวัสดี"
4. ถ้า bot ตอบกลับ = ตั้งค่าสำเร็จ! ✅

ถ้าไม่มีการตอบกลับ → ดู [Troubleshooting](#troubleshooting)

---

## 🤖 การตั้งค่าระบบ AI Bot

### Step 1: เลือก AI Provider

ระบบรองรับ AI หลายตัว:

| Provider | Model | ราคา | แนะนำสำหรับ |
|----------|-------|------|-------------|
| **OpenAI** | GPT-4o, GPT-4o mini | $$$ | คุณภาพสูงสุด, ภาษาไทยดี |
| **DeepSeek** | deepseek-chat | $ | ราคาถูก, คุณภาพดี |
| **Claude** | Claude 3 Sonnet/Haiku | $$ | เข้าใจบริบทดี, ภาษาไทยดี |
| **Gemini** | Gemini Pro | $ | Google, ฟรีจำกัด |

### Step 2: สร้าง AI Bot Profile

1. ไปที่ **"LINE Bot AI"** (`/admin/line-bot/ai`)
2. คลิก **"Create New AI Bot"**
3. กรอกข้อมูล:

```
Bot Name:               TP-Affiliate Assistant
AI Provider:            [เลือก: OpenAI, DeepSeek, Claude, Gemini]
AI Model:               [เลือก model ที่ต้องการ]
API Key:                [ใส่ API key จาก provider]

Temperature:            0.7  (ค่อนข้างสร้างสรรค์)
Max Tokens:             500  (ความยาวคำตอบสูงสุด)
```

4. ตั้งค่า **System Prompt** (บุคลิกของ bot):

```
คุณคือผู้ช่วยของระบบ Thaiprompt-Affiliate
ชื่อว่า "TP-Assistant"

บทบาท:
- ตอบคำถามเกี่ยวกับระบบ affiliate
- ช่วยเหลือ user ในการใช้งาน
- แนะนำสินค้าและโปรโมชั่น

ลักษณะการพูด:
- ใช้ภาษาไทยสุภาพ
- เป็นกันเองแต่ professional
- ตอบแบบกระชับ ไม่เกิน 3-4 ประโยค
- ใช้ emoji เล็กน้อย 😊

ห้าม:
- ไม่ตอบคำถามที่ไม่เกี่ยวข้องกับธุรกิจ
- ไม่ให้ข้อมูลส่วนตัวของ user คนอื่น
- ไม่พูดถึงคู่แข่ง
```

5. เปิดใช้งาน **"Active"** = ON
6. คลิก **"Save"**

### Step 3: ทดสอบ AI Bot

1. ส่งข้อความถึง LINE OA: "สวัสดี ฉันต้องการคำแนะนำเรื่อง affiliate"
2. Bot ควรตอบกลับด้วย AI ที่เป็นมิตร
3. ถ้า bot ไม่ตอบ → ตรวจสอบ:
   - ✅ API Key ถูกต้องหรือไม่
   - ✅ Bot Profile เป็น "Active" หรือไม่
   - ✅ Credit คงเหลือใน AI provider

---

## 🗂️ การสร้าง Rich Menu

Rich Menu คือเมนูด้านล่างที่ปรากฏใน LINE chat

### ขนาดมาตรฐาน LINE

- **Full Size**: 2500 x 1686 px
- **Half Size**: 2500 x 843 px
- **File Size**: สูงสุด 1 MB
- **Format**: JPG, PNG

### Step 1: ออกแบบภาพ Rich Menu

ใช้เครื่องมือ:
- **Canva** - [https://canva.com](https://canva.com) (แนะนำ!)
- **Photoshop** - สำหรับมืออาชีพ
- **Figma** - สำหรับ UI/UX designer

**เทมเพลต Canva:**
1. สร้าง Custom Size: 2500 x 1686 px (Full) หรือ 2500 x 843 px (Half)
2. แบ่งเป็นปุ่ม 2-6 ปุ่ม (แนะนำ 4-6 ปุ่ม)
3. ใส่ไอคอนและข้อความให้ชัดเจน
4. Export เป็น PNG

**ตัวอย่างเลย์เอาต์ (Full Size, 6 ปุ่ม):**

```
┌─────────┬─────────┬─────────┐
│ สินค้า   │ โปรโมชั่น│ คะแนน   │
├─────────┼─────────┼─────────┤
│ โปรไฟล์ │ ออเดอร์  │ ติดต่อ   │
└─────────┴─────────┴─────────┘
```

### Step 2: อัปโหลดและตั้งค่า

1. ไปที่ **"Rich Menus"** (`/admin/line-bot/rich-menu`)
2. คลิก **"Create Rich Menu"**
3. กรอกข้อมูล:

```
Name:               Main Menu
Size:               Full (2500x1686) / Half (2500x843)
Chat Bar Text:      เมนู  (ข้อความบน chat bar, max 14 ตัวอักษร)
Selected:           ON (แสดงเมื่อเปิด chat)
Is Default:         ON (ตั้งเป็นเมนูหลัก)
```

4. อัปโหลดภาพ:
   - คลิก **"Choose File"**
   - เลือกภาพที่ออกแบบ
   - ระบบจะ **auto-resize** ถ้าขนาดไม่ตรง
   - ระบบจะ **convert เป็น WebP** เพื่อลดขนาด

5. กำหนดพื้นที่คลิก (Areas):

```json
{
  "areas": [
    {
      "bounds": {"x": 0, "y": 0, "width": 833, "height": 843},
      "action": {
        "type": "uri",
        "uri": "https://yourdomain.com/products"
      }
    },
    {
      "bounds": {"x": 833, "y": 0, "width": 834, "height": 843},
      "action": {
        "type": "message",
        "text": "ดูโปรโมชั่น"
      }
    }
    // ... เพิ่มอีก 4 areas
  ]
}
```

**ประเภท Action ที่รองรับ:**
- `uri` - เปิด URL (เช่น website, LIFF app)
- `message` - ส่งข้อความ (trigger bot)
- `postback` - ส่งข้อมูลแบบซ่อน
- `richmenuswitch` - สลับ rich menu

6. คลิก **"Save"**

### Step 3: ทดสอบ Rich Menu

1. เปิด LINE chat กับ OA ของคุณ
2. แตะ **icon keyboard** ด้านล่างซ้าย
3. Rich Menu ควรปรากฏ
4. กดปุ่มต่างๆ ทดสอบ

---

## 📢 การส่ง Broadcast Messages

### ฟีเจอร์ Broadcast

- ✅ ส่งถึงผู้ใช้หลายคน (หมู่)
- ✅ รองรับ Text, Flex, Image, Video
- ✅ **ตั้งเวลาส่งล่วงหน้า (Scheduling)** ⭐ NEW!
- ✅ เลือกกลุ่มเป้าหมาย (All, Users, Sellers, Custom)
- ✅ Retry อัตโนมัติถ้าล้มเหลว (max 3 ครั้ง)

### Step 1: สร้าง Broadcast Campaign

1. ไปที่ **"Broadcast"** (`/admin/line-bot/broadcast`)
2. คลิก **"Create New Broadcast"**
3. กรอกข้อมูล:

```
Campaign Name:        โปรโมชั่นสิ้นเดือน
Target Audience:      All Users / Users Only / Sellers Only / Custom
Message Type:         Text / Flex / Image / Video
```

4. เขียนข้อความ (ถ้าเป็น Text):

```
🎉 โปรโมชั่นพิเศษสิ้นเดือน!

ลดสูงสุด 50% สำหรับสมาชิกทุกท่าน
ตั้งแต่วันนี้ - 30 พ.ย. 2568

👉 คลิกเลย: https://yourdomain.com/promo
```

**เคล็ดลับ:**
- ใช้ emoji เพื่อดึงดูดสายตา 🎁✨
- ใส่ Call-to-Action ที่ชัดเจน
- ข้อความสั้นๆ กระชับ (ไม่เกิน 500 ตัวอักษร)

5. เลือก **ตั้งเวลาส่ง**:

```
○ ส่งทันที       - ส่งเมื่อกด Submit
● ตั้งเวลา       - กำหนดวัน-เวลาส่ง

วันที่: 30 พ.ย. 2568
เวลา:  09:00 น.
```

⚡ **เวลาที่เหมาะสม:**
- 09:00 - 11:00 น. (เช้า - คนตื่นมาดูมือถือ)
- 12:00 - 13:00 น. (พักเที่ยง)
- 19:00 - 21:00 น. (เย็น - ชั่วโมงทอง!)

6. คลิก **"ตั้งเวลาส่ง"** หรือ **"ส่งทันที"**

### Step 2: ติดตามผลลัพธ์

1. ไปที่ **"Broadcast"** → เลือก campaign ที่ส่ง
2. ดูสถิติ:

```
Status:           ✅ Sent / ⏰ Scheduled / 🚀 Sending / ❌ Failed
Total Recipients: 1,250 คน
Sent:             1,200 คน (96%)
Failed:           50 คน (4%)
```

3. ถ้าสถานะเป็น **"Failed"** และยัง retry ได้:
   - คลิก **"Retry"** เพื่อส่งซ้ำ
   - ระบบจะส่งเฉพาะคนที่ล้มเหลว

---

## 🎤 การตั้งค่า Voice Message

ระบบรองรับการแปลง **Voice → Text** ด้วย Google Cloud Speech-to-Text

### ข้อกำหนด

- ✅ **Google Cloud Account**
- ✅ **Enable Speech-to-Text API**
- ✅ **Service Account Credentials**

### Step 1: สร้าง Google Cloud Project

1. ไปที่ [Google Cloud Console](https://console.cloud.google.com/)
2. สร้าง Project ใหม่ (เช่น "TP-Affiliate-STT")
3. Enable **Cloud Speech-to-Text API**:
   - ไปที่ **APIs & Services** → **Library**
   - ค้นหา "Speech-to-Text"
   - คลิก **"Enable"**

### Step 2: สร้าง Service Account

1. ไปที่ **IAM & Admin** → **Service Accounts**
2. คลิก **"Create Service Account"**
3. กรอกข้อมูล:
   - **Name**: TP-Affiliate-STT
   - **Role**: Cloud Speech Client

4. คลิก **"Create Key"** → เลือก **JSON**
5. ดาวน์โหลดไฟล์ `credentials.json`

### Step 3: ตั้งค่าในระบบ

1. อัปโหลด `credentials.json` ไปยัง server:
   ```bash
   /var/www/thaiprompt-affiliate/storage/app/google/
   ```

2. ตั้งค่า environment variable:
   ```bash
   # .env
   GOOGLE_APPLICATION_CREDENTIALS=/var/www/thaiprompt-affiliate/storage/app/google/credentials.json
   ```

3. Restart application

### Step 4: ทดสอบ Voice Message

1. เปิด LINE chat กับ OA
2. กดปุ่ม **Microphone** (🎤)
3. บันทึกเสียงพูด (เช่น "สวัสดี ฉันต้องการทราบโปรโมชั่น")
4. ส่ง

**ผลลัพธ์ที่คาดหวัง:**

```
🎤 กำลังฟังและแปลงเสียงของคุณ... กรุณารอสักครู่ค่ะ

🎧 คุณพูดว่า: "สวัสดี ฉันต้องการทราบโปรโมชั่น"

[Bot ตอบกลับตามเนื้อหา]
```

---

## 🔍 Troubleshooting

### ปัญหา: Bot ไม่ตอบข้อความ

**Checklist:**
- [ ] Webhook URL ตั้งค่าถูกต้องหรือไม่ (`/api/webhook/line`)
- [ ] Channel Access Token ถูกต้องหรือไม่
- [ ] "Use webhook" เปิดอยู่หรือไม่ (ใน LINE Developers Console)
- [ ] AI Bot Profile มีสถานะ "Active" หรือไม่
- [ ] ตรวจสอบ logs: `storage/logs/laravel.log`

**วิธีแก้:**
```bash
# ดู logs แบบ real-time
tail -f storage/logs/laravel.log
```

### ปัญหา: Webhook Verification Failed

**สาเหตุ:**
- SSL Certificate หมดอายุหรือไม่ valid
- Domain ไม่สามารถเข้าถึงได้จากภายนอก (ต้องเป็น public URL)

**วิธีแก้:**
1. ตรวจสอบ SSL:
   ```bash
   curl https://yourdomain.com/api/webhook/line
   ```
2. ถ้าใช้ ngrok (สำหรับ testing):
   ```bash
   ngrok http 8000
   # ใช้ URL ที่ได้ตั้งใน Webhook
   ```

### ปัญหา: Voice Message ไม่ทำงาน

**สาเหตุ:**
- Google Cloud credentials ไม่ถูกต้อง
- Speech-to-Text API ยังไม่ enable
- FFmpeg ไม่ได้ติดตั้ง (สำหรับ audio conversion)

**วิธีแก้:**
1. ตรวจสอบ credentials:
   ```bash
   # ทดสอบ Google Cloud auth
   gcloud auth application-default print-access-token
   ```

2. ติดตั้ง FFmpeg:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install ffmpeg

   # macOS
   brew install ffmpeg
   ```

### ปัญหา: Broadcast ส่งไม่ออก

**สาเหตุ:**
- Queue worker ไม่ทำงาน
- Status ติดที่ "sending"

**วิธีแก้:**
1. Run queue worker:
   ```bash
   php artisan queue:work --queue=broadcasts
   ```

2. Reset broadcast status (ถ้าจำเป็น):
   ```sql
   UPDATE line_broadcast_messages
   SET status = 'draft'
   WHERE status = 'sending' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
   ```

### ปัญหา: Rich Menu ไม่แสดง

**สาเหตุ:**
- ภาพมีขนาดไม่ถูกต้อง
- ยังไม่ set เป็น default

**วิธีแก้:**
1. ตรวจสอบขนาดภาพ:
   - Full: 2500 x 1686 px
   - Half: 2500 x 843 px

2. Set เป็น default:
   - ไปที่ Rich Menu list → คลิก **"Set as Default"**

---

## 📞 ติดต่อ Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:

- 📧 **Email**: support@thaiprompt.com
- 💬 **LINE OA**: @thaiprompt
- 📱 **โทร**: 02-xxx-xxxx
- 🌐 **Docs**: https://docs.thaiprompt.com

---

## 📄 ภาคผนวก

### ตัวอย่าง System Prompts สำหรับ AI

#### 1. Customer Support Bot
```
คุณคือผู้ช่วยฝ่ายบริการลูกค้าของ Thaiprompt-Affiliate

หน้าที่:
- ตอบคำถามเกี่ยวกับผลิตภัณฑ์และบริการ
- แก้ไขปัญหาและข้อสงสัย
- แนะนำวิธีการใช้งานระบบ

ตอบด้วย:
- ภาษาไทยสุภาพ เป็นมิตร
- กระชับ ชัดเจน
- ถ้าไม่แน่ใจ ให้บอกตรงๆ และแนะนำติดต่อเจ้าหน้าที่
```

#### 2. Sales Assistant Bot
```
คุณคือที่ปรึกษาด้านการขายของ Thaiprompt-Affiliate

เป้าหมาย:
- แนะนำสินค้าที่เหมาะสมกับลูกค้า
- สร้างความต้องการซื้อ
- ปิดการขาย

กลยุทธ์:
- ถามเพื่อเข้าใจความต้องการ
- เน้นคุณค่าและประโยชน์
- สร้างความรู้สึกเร่งด่วน (scarcity, urgency)
- ใช้ emoji น้อยที่สุด
```

### ตัวอย่าง Flex Message Template

```json
{
  "type": "bubble",
  "hero": {
    "type": "image",
    "url": "https://yourdomain.com/images/product.jpg",
    "size": "full",
    "aspectRatio": "20:13"
  },
  "body": {
    "type": "box",
    "layout": "vertical",
    "contents": [
      {
        "type": "text",
        "text": "สินค้าพิเศษ",
        "weight": "bold",
        "size": "xl"
      },
      {
        "type": "text",
        "text": "ลดสูงสุด 50%",
        "size": "sm",
        "color": "#ff0000"
      }
    ]
  },
  "footer": {
    "type": "box",
    "layout": "vertical",
    "contents": [
      {
        "type": "button",
        "action": {
          "type": "uri",
          "label": "ดูเพิ่มเติม",
          "uri": "https://yourdomain.com/product/123"
        },
        "style": "primary"
      }
    ]
  }
}
```

---

**Document Version**: 1.0.0
**Created**: 2025-11-22
**Last Updated**: 2025-11-22
**Author**: Thaiprompt Development Team

✨ **Happy LINE Bot Building!** 🚀
