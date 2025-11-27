# 🚀 คู่มือติดตั้งและเชื่อมต่อ LINE Official Account ฉบับสมบูรณ์

> **คู่มือการตั้งค่า LINE OA พร้อมระบบ Hybrid AI Bot**
> รองรับการใช้งาน Chatbot ธรรมดา + AI (ChatGPT, Claude, Gemini, DeepSeek)

**Version**: 3.0.0
**Last Updated**: 2025-11-22
**สำหรับ**: Thaiprompt-Affiliate V3

---

## 📋 สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [ความต้องการเบื้องต้น](#ความต้องการเบื้องต้น)
3. [ขั้นตอนที่ 1: สร้าง LINE Official Account](#ขั้นตอนที่-1-สร้าง-line-official-account)
4. [ขั้นตอนที่ 2: สร้าง LINE Login Channel](#ขั้นตอนที่-2-สร้าง-line-login-channel)
5. [ขั้นตอนที่ 3: สร้าง LINE Messaging API Channel](#ขั้นตอนที่-3-สร้าง-line-messaging-api-channel)
6. [ขั้นตอนที่ 4: ตั้งค่าในระบบ TP-Affiliate](#ขั้นตอนที่-4-ตั้งค่าในระบบ-tp-affiliate)
7. [ขั้นตอนที่ 5: ตั้งค่า Webhook](#ขั้นตอนที่-5-ตั้งค่า-webhook)
8. [ขั้นตอนที่ 6: ตั้งค่า AI Bot (Hybrid Mode)](#ขั้นตอนที่-6-ตั้งค่า-ai-bot-hybrid-mode)
9. [ขั้นตอนที่ 7: ตั้งค่า Keywords & Hybrid Bot](#ขั้นตอนที่-7-ตั้งค่า-keywords--hybrid-bot)
10. [การใช้งานระบบ Hybrid AI Bot](#การใช้งานระบบ-hybrid-ai-bot)
11. [การทดสอบระบบ](#การทดสอบระบบ)
12. [Troubleshooting](#troubleshooting)
13. [คำถามที่พบบ่อย (FAQ)](#คำถามที่พบบ่อย-faq)

---

## ภาพรวมระบบ

### 🎯 ระบบ LINE ใน TP-Affiliate

ระบบ LINE ของ TP-Affiliate รองรับการทำงานแบบ **Hybrid AI Bot** ซึ่งสามารถสลับระหว่าง:

1. **Chatbot ธรรมดา (Keyword-based)** - ตอบคำถามตาม keywords ที่ตั้งไว้
2. **AI Bot (AI-powered)** - ใช้ AI จาก ChatGPT, Claude, Gemini, DeepSeek
3. **Hybrid Mode** - สลับระหว่าง Chatbot + AI อัตโนมัติตาม conditions

### 🌟 ฟีเจอร์หลัก

```
┌─────────────────────────────────────────────────┐
│  ระบบ LINE OA ใน TP-Affiliate V3              │
├─────────────────────────────────────────────────┤
│                                                 │
│  ✅ LINE Login Authentication                  │
│  ✅ LINE Messaging API                         │
│  ✅ Hybrid AI Bot                              │
│     ├─ Simple Chatbot (Keywords)               │
│     ├─ ChatGPT Integration                     │
│     ├─ Claude Integration                      │
│     ├─ Gemini Integration                      │
│     └─ DeepSeek Integration                    │
│  ✅ Knowledge Base Management                  │
│  ✅ Conversation History                       │
│  ✅ Broadcast Messaging                        │
│  ✅ Rich Menu & Flex Messages                  │
│  ✅ Analytics & Reporting                      │
│  ✅ Membership Signup (7-step flow)            │
│                                                 │
└─────────────────────────────────────────────────┘
```

### 📊 โครงสร้างการทำงาน

```
┌──────────────┐
│  LINE User   │
└──────┬───────┘
       │ ส่งข้อความ
       ▼
┌──────────────────────┐
│  LINE OA (Webhook)   │
└──────┬───────────────┘
       │ Forward to
       ▼
┌──────────────────────────────────┐
│  TP-Affiliate (LineWebhook)      │
└──────┬───────────────────────────┘
       │
       ├─► LineBotKeywordController (ตรวจสอบ Keywords)
       │   │
       │   ├─ Match Keyword? → ส่งคำตอบที่ตั้งไว้
       │   └─ No Match? → ส่งต่อไป AI Bot
       │
       └─► LineBotAiService (AI Processing)
           │
           ├─ ChatGPT
           ├─ Claude
           ├─ Gemini
           └─ DeepSeek
           │
           └─► ส่งคำตอบกลับไปยัง LINE User
```

---

## ความต้องการเบื้องต้น

### ✅ สิ่งที่ต้องมี

1. **LINE Business ID**
   - สมัครได้ที่: https://manager.line.biz/

2. **เว็บไซต์ที่ใช้ HTTPS**
   - LINE Webhook ต้องการ HTTPS เท่านั้น
   - แนะนำ: ใช้ Cloudflare (ฟรี SSL)

3. **Domain Name**
   - ต้องมี domain ที่ชี้ไปยัง server
   - เช่น: `https://yourdomain.com`

4. **API Keys (ตามที่เลือกใช้)**
   - OpenAI API Key (สำหรับ ChatGPT)
   - Anthropic API Key (สำหรับ Claude)
   - Google AI API Key (สำหรับ Gemini)
   - DeepSeek API Key (สำหรับ DeepSeek)

### 📋 Checklist ก่อนเริ่ม

- [ ] มี LINE Business ID แล้ว
- [ ] เว็บไซต์ทำงานบน HTTPS
- [ ] มี Domain Name
- [ ] ติดตั้ง TP-Affiliate เรียบร้อยแล้ว
- [ ] มี API Key ของ AI ที่ต้องการใช้ (ถ้าใช้ AI Bot)

---

## ขั้นตอนที่ 1: สร้าง LINE Official Account

### 1.1 สมัคร LINE Business ID

1. เข้าไปที่ https://manager.line.biz/
2. คลิก **"สร้างบัญชี"** (Create Account)
3. เลือก **"ธุรกิจ"** (Business Account)
4. กรอกข้อมูล:
   - ชื่อบัญชี (Account Name)
   - ประเภทธุรกิจ (Business Type)
   - ชื่อบริษัท/องค์กร
   - อีเมล
5. ยืนยันอีเมล
6. เข้าสู่ระบบ LINE Business ID

### 1.2 สร้าง LINE Official Account

1. ใน LINE Managers เลือก **"สร้างบัญชี LINE Official Account"**
2. กรอกข้อมูล:
   - **ชื่อบัญชี** (Account Name) - ชื่อที่จะแสดงใน LINE
   - **รูปโปรไฟล์** (Profile Picture) - อัปโหลดรูปภาพ 512x512px
   - **คำอธิบาย** (Description) - อธิบายเกี่ยวกับบริการ
   - **ประเภทบัญชี** (Account Type) - เลือกตามธุรกิจ
3. คลิก **"สร้าง"** (Create)

### 1.3 บันทึกข้อมูลสำคัญ

หลังจากสร้าง LINE OA เรียบร้อย ให้บันทึกข้อมูลเหล่านี้:

```
LINE OA Information:
├─ Basic ID: @xxxxx
├─ LINE ID: @xxxxx (เหมือนกับ Basic ID)
└─ QR Code: [ดาวน์โหลดได้จาก LINE Managers]
```

---

## ขั้นตอนที่ 2: สร้าง LINE Login Channel

LINE Login ใช้สำหรับให้ผู้ใช้เข้าสู่ระบบด้วย LINE Account

### 2.1 เข้าสู่ LINE Developers Console

1. ไปที่ https://developers.line.biz/console/
2. Login ด้วย LINE Business ID
3. คลิก **"Create a new provider"** (ถ้ายังไม่มี)
   - ชื่อ Provider: ใส่ชื่อบริษัท/องค์กร
4. เลือก Provider ที่สร้าง

### 2.2 สร้าง LINE Login Channel

1. ใน Provider คลิก **"Create a new channel"**
2. เลือก **"LINE Login"**
3. กรอกข้อมูล:

   **Channel Information:**
   ```
   Channel name: TP-Affiliate Login
   Channel description: Login system for TP-Affiliate
   App types: ✅ Web app
   ```

   **App settings:**
   ```
   Email address: your@email.com
   Privacy policy URL: https://yourdomain.com/privacy
   Terms of use URL: https://yourdomain.com/terms
   ```

4. คลิก **"Create"**

### 2.3 ตั้งค่า Channel

หลังจากสร้าง Channel แล้ว:

1. ไปที่แท็บ **"LINE Login"**
2. ตั้งค่า **Callback URL**:
   ```
   https://yourdomain.com/auth/line/callback
   ```
3. บันทึก

### 2.4 บันทึก Credentials

ไปที่แท็บ **"Basic settings"** และบันทึก:

```env
LINE_LOGIN_CHANNEL_ID=1234567890
LINE_LOGIN_CHANNEL_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## ขั้นตอนที่ 3: สร้าง LINE Messaging API Channel

LINE Messaging API ใช้สำหรับส่งและรับข้อความ

### 3.1 สร้าง Messaging API Channel

1. ใน LINE Developers Console
2. ในหน้า Provider คลิก **"Create a new channel"**
3. เลือก **"Messaging API"**
4. กรอกข้อมูล:

   **Channel Information:**
   ```
   Channel name: TP-Affiliate Messaging
   Channel description: Messaging bot for TP-Affiliate
   Category: เลือกหมวดหมู่ธุรกิจ
   Subcategory: เลือกหมวดหมู่ย่อย
   ```

   **App settings:**
   ```
   Email address: your@email.com
   Privacy policy URL: https://yourdomain.com/privacy
   Terms of use URL: https://yourdomain.com/terms
   ```

5. คลิก **"Create"**

### 3.2 ตั้งค่า Channel

1. ไปที่แท็บ **"Messaging API"**
2. เลื่อนลงไปที่ **"Channel access token"**
3. คลิก **"Issue"** เพื่อสร้าง Channel Access Token
4. **คัดลอกและเก็บ Token ไว้อย่างปลอดภัย** (จะแสดงครั้งเดียว!)

### 3.3 ตั้งค่า Webhook

1. ใน Messaging API tab
2. หา **"Webhook settings"**
3. ตั้งค่า:
   ```
   Webhook URL: https://yourdomain.com/api/webhook/line
   Use webhook: ✅ Enabled
   Verify webhook: (รอก่อน จะทำในขั้นตอนที่ 5)
   ```

### 3.4 ตั้งค่า Auto-reply

**⚠️ สำคัญมาก! ต้องปิด Auto-reply**

1. ใน Messaging API tab
2. หา **"Auto-reply messages"**
3. คลิก **"Edit"** (จะเปิด LINE Official Account Manager)
4. ปิดการตั้งค่าเหล่านี้:
   - **Auto-reply messages**: ❌ Disabled
   - **Greeting messages**: ❌ Disabled (หรือตั้งค่าตามต้องการ)

### 3.5 บันทึก Credentials

บันทึกข้อมูลเหล่านี้:

```env
LINE_MESSAGING_CHANNEL_ID=1234567890
LINE_MESSAGING_CHANNEL_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
LINE_CHANNEL_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## ขั้นตอนที่ 4: ตั้งค่าในระบบ TP-Affiliate

### 4.1 ตั้งค่า Environment Variables

แก้ไขไฟล์ `.env`:

```env
# LINE Login Channel
LINE_LOGIN_CHANNEL_ID=your_login_channel_id
LINE_LOGIN_CHANNEL_SECRET=your_login_channel_secret

# LINE Messaging API Channel
LINE_MESSAGING_CHANNEL_ID=your_messaging_channel_id
LINE_MESSAGING_CHANNEL_SECRET=your_messaging_channel_secret
LINE_CHANNEL_ACCESS_TOKEN=your_channel_access_token

# LINE OA Basic Info
LINE_OA_BASIC_ID=@xxxxx

# AI Providers (ถ้าใช้)
OPENAI_API_KEY=sk-xxxxx
ANTHROPIC_API_KEY=sk-ant-xxxxx
GOOGLE_AI_API_KEY=xxxxx
DEEPSEEK_API_KEY=xxxxx
```

### 4.2 เข้าสู่ระบบ Admin

1. เปิดเบราว์เซอร์ไปที่ `https://yourdomain.com/admin`
2. Login ด้วย Admin account
3. ไปที่เมนู **"LINE OA & AI"** > **"LINE OA Settings"**

### 4.3 บันทึกการตั้งค่า LINE OA

ในหน้า **LINE OA Settings** กรอกข้อมูล:

#### **LINE Login Channel**
```
Login Channel ID: [กรอก LINE_LOGIN_CHANNEL_ID]
Login Channel Secret: [กรอก LINE_LOGIN_CHANNEL_SECRET]
Login Redirect URI: https://yourdomain.com/auth/line/callback
```

#### **LINE Messaging API Channel**
```
Messaging Channel ID: [กรอก LINE_MESSAGING_CHANNEL_ID]
Messaging Channel Secret: [กรอก LINE_MESSAGING_CHANNEL_SECRET]
Channel Access Token: [กรอก LINE_CHANNEL_ACCESS_TOKEN]
Webhook URL: https://yourdomain.com/api/webhook/line
```

#### **LINE OA Information**
```
Basic ID: @xxxxx
LINE OA Name: [ชื่อ LINE OA]
```

คลิก **"บันทึก"** (Save)

### 4.4 ทดสอบการเชื่อมต่อ

ในหน้า LINE OA Settings:

1. คลิกปุ่ม **"ทดสอบการเชื่อมต่อ"** (Test Connection)
2. ระบบจะตรวจสอบ:
   - ✅ Channel ID ถูกต้อง
   - ✅ Channel Secret ถูกต้อง
   - ✅ Access Token ใช้งานได้
3. ถ้าทุกอย่างถูกต้อง จะแสดง **"เชื่อมต่อสำเร็จ"** (Connection Success)

---

## ขั้นตอนที่ 5: ตั้งค่า Webhook

### 5.1 ตรวจสอบ Webhook URL

1. เปิด Terminal/Command Prompt
2. ทดสอบ Webhook URL:
   ```bash
   curl -X POST https://yourdomain.com/api/webhook/line \
     -H "Content-Type: application/json" \
     -d '{}'
   ```
3. ควรได้ response กลับมา (ไม่ว่าจะ error ก็ได้ แสดงว่า URL ทำงาน)

### 5.2 Verify Webhook ใน LINE Developers Console

1. กลับไปที่ LINE Developers Console
2. เลือก Messaging API Channel
3. ไปที่แท็บ **"Messaging API"**
4. หา **"Webhook settings"**
5. คลิก **"Verify"**
6. ถ้าผ่าน จะแสดง **"Success"** ✅

### 5.3 ทดสอบส่งข้อความ

ใน LINE OA Settings (TP-Affiliate Admin):

1. หา **"ทดสอบส่งข้อความ"** (Test Message)
2. กรอก LINE User ID (หรือใช้ LINE User ID ของตัวเอง)
3. กรอกข้อความทดสอบ
4. คลิก **"ส่ง"** (Send)
5. เช็คที่ LINE App ว่าได้รับข้อความหรือไม่

### 5.4 หา LINE User ID ของตัวเอง

**วิธีที่ 1: ผ่าน LINE Official Account Manager**

1. เปิด LINE App บนมือถือ
2. Add Friend LINE OA ที่สร้างไว้ (สแกน QR Code)
3. ส่งข้อความอะไรก็ได้ไปยัง LINE OA
4. ใน TP-Affiliate Admin ไปที่ **"LINE OA & AI"** > **"LINE Users"**
5. จะเห็นรายชื่อผู้ใช้ที่ส่งข้อความมา พร้อม User ID

**วิธีที่ 2: ดูจาก Database**

```sql
SELECT * FROM line_login_logs ORDER BY created_at DESC LIMIT 10;
```

---

## ขั้นตอนที่ 6: ตั้งค่า AI Bot (Hybrid Mode)

### 6.1 เลือก AI Provider

TP-Affiliate รองรับ AI Providers เหล่านี้:

| Provider | Model | Use Case |
|----------|-------|----------|
| **OpenAI (ChatGPT)** | GPT-4, GPT-3.5 | General purpose, ดีรอบด้าน |
| **Anthropic (Claude)** | Claude 3 Opus, Sonnet, Haiku | สนทนายาว, ตอบคำถามซับซ้อน |
| **Google (Gemini)** | Gemini Pro | Multilingual, ภาษาไทยดี |
| **DeepSeek** | DeepSeek Chat | ราคาถูก, เหมาะกับ simple tasks |

### 6.2 สมัคร API Key

#### **OpenAI (ChatGPT)**

1. ไปที่ https://platform.openai.com/
2. สมัครบัญชี/Login
3. ไปที่ **"API Keys"**
4. คลิก **"Create new secret key"**
5. คัดลอก API Key (ขึ้นต้นด้วย `sk-`)
6. บันทึกใน `.env`:
   ```env
   OPENAI_API_KEY=sk-xxxxx
   ```

#### **Anthropic (Claude)**

1. ไปที่ https://console.anthropic.com/
2. สมัครบัญชี/Login
3. ไปที่ **"API Keys"**
4. คลิก **"Create Key"**
5. คัดลอก API Key (ขึ้นต้นด้วย `sk-ant-`)
6. บันทึกใน `.env`:
   ```env
   ANTHROPIC_API_KEY=sk-ant-xxxxx
   ```

#### **Google (Gemini)**

1. ไปที่ https://makersuite.google.com/app/apikey
2. Login ด้วย Google Account
3. คลิก **"Create API Key"**
4. คัดลอก API Key
5. บันทึกใน `.env`:
   ```env
   GOOGLE_AI_API_KEY=xxxxx
   ```

#### **DeepSeek**

1. ไปที่ https://platform.deepseek.com/
2. สมัครบัญชี/Login
3. ไปที่ **"API Keys"**
4. คลิก **"Create new secret key"**
5. คัดลอก API Key
6. บันทึกใน `.env`:
   ```env
   DEEPSEEK_API_KEY=xxxxx
   ```

### 6.3 สร้าง AI Bot Configuration

1. ใน TP-Affiliate Admin ไปที่ **"LINE OA & AI"** > **"AI Chat Bot"**
2. คลิก **"+ สร้าง AI Bot ใหม่"**
3. กรอกข้อมูล:

#### **ข้อมูลพื้นฐาน**
```
ชื่อ Bot: Customer Support Bot
คำอธิบาย: ตอบคำถามลูกค้าเกี่ยวกับสินค้าและบริการ
สถานะ: ✅ เปิดใช้งาน
```

#### **AI Provider Settings**
```
AI Provider: [เลือก OpenAI / Anthropic / Google / DeepSeek]
Model: [เลือก model ที่ต้องการ]
API Key: [กรอก API Key หรือใช้จาก .env]
```

#### **AI Configuration**
```
Temperature: 0.7
  (0.0 = ตอบตายตัว, 1.0 = ตอบสร้างสรรค์)

Max Tokens: 500
  (จำนวนคำสูงสุดในการตอบ)

System Prompt:
คุณคือ AI ผู้ช่วยของ TP-Affiliate
คุณสามารถตอบคำถามเกี่ยวกับ:
- ระบบ Affiliate Marketing
- สินค้าและบริการ
- การสมัครสมาชิก
- การถอนเงิน

ให้ตอบด้วยภาษาไทยที่สุภาพและเป็นกันเอง
```

#### **Conversation Settings**
```
จำนวนข้อความย้อนหลัง (Context): 10
  (จำนวนข้อความที่ AI จะจำได้ในการสนทนา)

Timeout (วินาที): 30
  (เวลารอคำตอบจาก AI สูงสุด)

Fallback Message:
ขออภัยค่ะ ขณะนี้ระบบ AI มีปัญหา
กรุณาลองใหม่อีกครั้งหรือติดต่อเจ้าหน้าที่
```

4. คลิก **"บันทึก"** (Save)

### 6.4 ทดสอบ AI Bot

1. ในหน้า AI Bot Configuration
2. คลิก **"ทดสอบ AI"** (Test AI)
3. กรอกข้อความทดสอบ:
   ```
   สวัสดีครับ TP-Affiliate ให้บริการอะไรบ้าง?
   ```
4. คลิก **"ส่ง"**
5. ดูคำตอบจาก AI

---

## ขั้นตอนที่ 7: ตั้งค่า Keywords & Hybrid Bot

### 7.1 ทำความเข้าใจ Hybrid Bot

**Hybrid Bot** = Keyword Chatbot + AI Bot

```
User Message
    │
    ▼
┌─────────────────────┐
│ Keyword Matching    │◄─── ตรวจสอบ keywords ก่อน
└─────┬───────┬───────┘
      │       │
  Match?      No Match
      │       │
      ▼       ▼
  ส่งคำตอบ   ส่งต่อไป AI Bot
  ที่ตั้งไว้
```

**ข้อดีของ Hybrid Mode:**
- ✅ ตอบคำถามที่ต้องการคำตอบแน่นอนได้เร็ว (เช่น "ราคา", "โปรโมชั่น")
- ✅ ประหยัดค่า API (ไม่ต้องเรียก AI ทุกครั้ง)
- ✅ ยืดหยุ่น สามารถปรับแต่ง keywords ได้ตลอด

### 7.2 สร้าง Keywords

1. ใน TP-Affiliate Admin ไปที่ **"LINE OA & AI"** > **"Bot Keywords"**
2. คลิก **"+ สร้าง Keyword ใหม่"**
3. กรอกข้อมูล:

#### **ตัวอย่างที่ 1: Simple Keyword**

```
Keyword: ราคา, ค่าบริการ, เท่าไหร่
Match Type: Contains (มีคำใดคำหนึ่ง)
Priority: 10

Response Type: Text
Response:
💰 ราคาบริการของเรา

📦 แพ็กเกจ Basic - 990 บาท/เดือน
📦 แพ็กเกจ Pro - 1,990 บาท/เดือน
📦 แพ็กเกจ Enterprise - 4,990 บาท/เดือน

สนใจสมัครติดต่อ: 02-123-4567
```

#### **ตัวอย่างที่ 2: Flex Message Response**

```
Keyword: สมัครสมาชิก, register, sign up
Match Type: Contains
Priority: 10

Response Type: Flex Message
[เลือก Flex Message Template ที่สร้างไว้]
```

#### **ตัวอย่างที่ 3: Hybrid - Forward to AI**

```
Keyword: *
Match Type: Fallback (ไม่ match keyword ใดเลย)
Priority: 1

Response Type: Forward to AI
AI Bot: [เลือก AI Bot ที่สร้างไว้]
```

### 7.3 ตั้งค่า Priority

Priority (ความสำคัญ):
- **10** = สูงสุด (ตรวจสอบก่อน)
- **5** = ปานกลาง
- **1** = ต่ำสุด (ตรวจสอบท้ายสุด)

**ตัวอย่าง:**
```
Priority 10: "ราคา", "โปรโมชั่น", "สมัครสมาชิก"
Priority 5:  "เกี่ยวกับเรา", "ติดต่อ"
Priority 1:  "*" (Fallback - ส่งต่อไป AI)
```

### 7.4 ทดสอบ Keywords

1. ในหน้า Bot Keywords
2. คลิก **"ทดสอบ Keywords"**
3. กรอกข้อความทดสอบ:
   ```
   ราคาเท่าไหร่ครับ?
   ```
4. ระบบจะแสดง:
   - Matched Keyword
   - Response ที่จะส่งกลับ
   - Priority

---

## การใช้งานระบบ Hybrid AI Bot

### 🎯 Use Cases

#### **1. Customer Support Bot**

```yaml
Scenario: ตอบคำถามลูกค้า
Keywords:
  - "ราคา" → แสดงราคาบริการ
  - "วิธีสมัคร" → แสดงขั้นตอนการสมัคร
  - "ติดต่อ" → แสดงข้อมูลติดต่อ
  - "*" (Fallback) → ส่งต่อไป AI Bot

AI Bot:
  Provider: OpenAI (GPT-4)
  System Prompt: "คุณคือ AI ผู้ช่วยฝ่ายบริการลูกค้า..."
```

#### **2. Product Recommendation Bot**

```yaml
Scenario: แนะนำสินค้า
Keywords:
  - "สินค้าแนะนำ" → แสดงสินค้ายอดนิยม
  - "โปรโมชั่น" → แสดงโปรโมชั่นปัจจุบัน
  - "*" → ส่งต่อไป AI (ถามคำถามเพิ่มเติม แนะนำสินค้าตามความต้องการ)

AI Bot:
  Provider: Google Gemini Pro
  Knowledge Base: รายการสินค้า, โปรโมชั่น
```

#### **3. MLM Signup Bot**

```yaml
Scenario: ช่วยสมัครสมาชิก MLM
Keywords:
  - "สมัคร" → เริ่มกระบวนการสมัคร (7-step flow)
  - "ตรวจสอบสถานะ" → แสดงสถานะการสมัคร
  - "*" → AI ช่วยตอบคำถามเกี่ยวกับระบบ MLM

AI Bot:
  Provider: Claude 3 Sonnet
  System Prompt: "คุณคือผู้เชี่ยวชาญด้าน MLM..."
```

### 📊 Hybrid Bot Flow Example

```
User: "สินค้าแนะนำอะไรบ้างครับ"
    │
    ▼
Keyword Match: "สินค้าแนะนำ"
    │
    ▼
Response: [Flex Message แสดงสินค้า Top 5]
    │
    ▼
User: "สินค้าตัวแรกเหมาะกับมือใหม่ไหม?"
    │
    ▼
No Keyword Match
    │
    ▼
Forward to AI Bot (ChatGPT)
    │
    ▼
AI Response: "สินค้าตัวแรกที่แนะนำนั้นเหมาะมากค่ะ
เพราะมีขั้นตอนการใช้งานง่าย..."
```

---

## การทดสอบระบบ

### ✅ Checklist การทดสอบ

#### **1. ทดสอบ LINE Login**

- [ ] เปิดหน้า Login
- [ ] คลิก "เข้าสู่ระบบด้วย LINE"
- [ ] Authorize ใน LINE App
- [ ] Redirect กลับมาที่เว็บไซต์สำเร็จ
- [ ] เข้าสู่ระบบสำเร็จ

#### **2. ทดสอบ Webhook**

- [ ] Add Friend LINE OA
- [ ] ส่งข้อความ "สวัสดี"
- [ ] ได้รับการตอบกลับ

#### **3. ทดสอบ Keyword Bot**

- [ ] ส่งข้อความที่ match keyword (เช่น "ราคา")
- [ ] ได้รับคำตอบที่ตั้งไว้

#### **4. ทดสอบ AI Bot**

- [ ] ส่งข้อความที่ไม่ match keyword
- [ ] ได้รับคำตอบจาก AI
- [ ] AI ตอบเป็นภาษาไทยถูกต้อง

#### **5. ทดสอบ Conversation History**

- [ ] ส่งข้อความหลายๆ ข้อความ
- [ ] AI จำบริบทการสนทนาได้
- [ ] ดู conversation history ใน Admin Panel

#### **6. ทดสอบ Broadcast**

- [ ] สร้าง broadcast message
- [ ] ส่งถึงผู้ใช้ทดสอบ
- [ ] ได้รับข้อความสำเร็จ

---

## Troubleshooting

### ❌ ปัญหาที่พบบ่อย

#### **1. Webhook ไม่ทำงาน**

**อาการ:**
- ส่งข้อความใน LINE แล้วไม่ได้รับการตอบกลับ

**แก้ไข:**
```bash
# 1. ตรวจสอบ webhook URL
curl -X POST https://yourdomain.com/api/webhook/line

# 2. ตรวจสอบ logs
tail -f storage/logs/laravel.log

# 3. ตรวจสอบว่า webhook enabled ใน LINE Console
# Messaging API > Webhook settings > Use webhook: ON

# 4. ตรวจสอบว่าปิด auto-reply แล้ว
# LINE Official Account Manager > Response settings > Auto-reply: OFF
```

#### **2. AI Bot ตอบช้า**

**อาการ:**
- รอนานกว่า AI จะตอบ

**แก้ไข:**
```env
# เพิ่ม timeout ใน .env
AI_TIMEOUT=60

# หรือลดจำนวน max tokens
AI_MAX_TOKENS=300
```

#### **3. AI Bot ตอบผิดบริบท**

**อาการ:**
- AI ตอบไม่ตรงกับสิ่งที่ถาม

**แก้ไข:**
```
1. ปรับ System Prompt ให้ชัดเจนขึ้น
2. เพิ่ม Knowledge Base
3. ลด Temperature (0.3 - 0.5)
```

#### **4. Keyword ไม่ work**

**อาการ:**
- ส่งคำที่ตรงกับ keyword แต่ไม่ได้รับคำตอบ

**แก้ไข:**
```
1. ตรวจสอบว่า keyword active
2. ตรวจสอบ Match Type (Exact / Contains / Regex)
3. ตรวจสอบ Priority (ควรเป็น 5-10)
4. ใช้ "ทดสอบ Keywords" ใน Admin Panel
```

#### **5. API Key ไม่ถูกต้อง**

**อาการ:**
```
Error: Invalid API Key
```

**แก้ไข:**
```bash
# 1. ตรวจสอบ API key ใน .env
cat .env | grep API_KEY

# 2. Restart server
php artisan config:clear
php artisan cache:clear

# 3. ทดสอบ API key
# OpenAI
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"

# Anthropic
curl https://api.anthropic.com/v1/messages \
  -H "x-api-key: $ANTHROPIC_API_KEY"
```

---

## คำถามที่พบบ่อย (FAQ)

### Q1: ต้องเสียค่าใช้จ่ายอะไรบ้าง?

**A:**
- **LINE Official Account**: ฟรี (มีแพ็กเกจเสียเงินถ้าต้องการส่งข้อความมาก)
- **OpenAI API**: ~$0.03 ต่อ 1,000 คำ (GPT-4)
- **Anthropic API**: ~$0.01 ต่อ 1,000 คำ (Claude 3)
- **Google Gemini**: ฟรี (มี quota จำกัด)
- **DeepSeek**: ~$0.0015 ต่อ 1,000 คำ (ถูกที่สุด)

### Q2: ใช้ AI Provider ไหนดี?

**A:**
- **ChatGPT (GPT-4)**: ดีรอบด้าน แม่นยำสูง แต่ราคาแพง
- **Claude 3**: ดีกับงานที่ต้องอ่าน/เข้าใจ context ยาวๆ
- **Gemini Pro**: ภาษาไทยดี ฟรี (แต่มี quota)
- **DeepSeek**: ราคาถูก เหมาะกับงาน simple

**แนะนำ:**
- Production: ChatGPT (GPT-4)
- Development/Testing: Gemini Pro (ฟรี)
- Budget: DeepSeek

### Q3: สามารถใช้หลาย AI พร้อมกันได้ไหม?

**A:** ได้! สร้างหลาย AI Bot แล้วใช้ keywords เพื่อเลือกว่าจะส่งไป AI ไหน

**ตัวอย่าง:**
```
Keyword: "วิเคราะห์ข้อมูล" → AI Bot A (GPT-4)
Keyword: "แปลภาษา" → AI Bot B (Gemini Pro)
Keyword: "*" (default) → AI Bot C (DeepSeek)
```

### Q4: Conversation History เก็บไว้นานแค่ไหน?

**A:**
- Default: 30 วัน
- สามารถตั้งค่าได้ใน `.env`:
  ```env
  LINE_CONVERSATION_RETENTION_DAYS=90
  ```

### Q5: จำกัดจำนวนผู้ใช้ไหม?

**A:**
- **LINE OA Free**: ส่งได้ 500 ข้อความ/เดือน
- **LINE OA Paid**: ไม่จำกัด (เสียเงินตามแพ็กเกจ)
- **TP-Affiliate**: ไม่จำกัด

### Q6: รองรับภาษาไทยไหม?

**A:** รองรับ! AI Providers ทั้งหมดรองรับภาษาไทย โดย:
- **Gemini Pro**: ดีที่สุดสำหรับภาษาไทย
- **GPT-4**: ดีมาก
- **Claude 3**: ดี
- **DeepSeek**: พอใช้

### Q7: สามารถ export ข้อมูล conversation ได้ไหม?

**A:** ได้! ไปที่:
```
Admin > LINE OA & AI > Conversations > Export
```

### Q8: ปลอดภัยหรือไม่?

**A:**
- ✅ HTTPS encryption
- ✅ API Keys เข้ารหัส
- ✅ User data ไม่ถูกส่งไปยัง third party (นอกจาก AI providers)
- ✅ ปฏิบัติตาม PDPA

---

## 📚 เอกสารเพิ่มเติม

### Official Documentation

- **LINE Developers**: https://developers.line.biz/en/docs/
- **LINE Messaging API**: https://developers.line.biz/en/docs/messaging-api/
- **LINE Login**: https://developers.line.biz/en/docs/line-login/
- **OpenAI API**: https://platform.openai.com/docs/
- **Anthropic API**: https://docs.anthropic.com/
- **Google AI**: https://ai.google.dev/docs
- **DeepSeek API**: https://platform.deepseek.com/docs

### TP-Affiliate Documentation

- `LINE_BOT_AI_IMPLEMENTATION.md` - การใช้งาน AI Bot
- `LINE_BOT_HYBRID_MODE.md` - Hybrid Bot patterns
- `LINE_MEMBERSHIP_SIGNUP_README.md` - ระบบสมัครสมาชิก
- `.claude/V3_CODING_GUIDELINES.md` - V3 coding standards

---

## 🎉 สรุป

คุณได้ตั้งค่า LINE Official Account พร้อมระบบ Hybrid AI Bot เรียบร้อยแล้ว!

**สิ่งที่ทำได้แล้ว:**
- ✅ สร้าง LINE OA
- ✅ ตั้งค่า LINE Login
- ✅ ตั้งค่า LINE Messaging API
- ✅ เชื่อมต่อ Webhook
- ✅ ตั้งค่า AI Bot (ChatGPT/Claude/Gemini/DeepSeek)
- ✅ ตั้งค่า Keywords & Hybrid Mode
- ✅ ทดสอบระบบครบถ้วน

**ขั้นตอนต่อไป:**
1. ปรับแต่ง System Prompt ตามธุรกิจ
2. เพิ่ม Keywords สำหรับคำถามที่พบบ่อย
3. สร้าง Flex Messages สำหรับ rich content
4. ตั้งค่า Rich Menu
5. สร้าง Broadcast campaigns
6. Monitor analytics & optimize

---

**เอกสารนี้สร้างโดย**: Thaiprompt Development Team
**Version**: 3.0.0
**Last Updated**: 2025-11-22
**License**: Proprietary - TP-Affiliate

**ติดต่อสอบถาม**: support@thaiprompt.com

---

*"ระบบ LINE OA ที่ดีที่สุดสำหรับธุรกิจของคุณ"* 🚀
