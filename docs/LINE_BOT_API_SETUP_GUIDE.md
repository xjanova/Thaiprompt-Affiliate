# คู่มือการตั้งค่า LINE Bot API แบบละเอียด

> **เวอร์ชัน:** 1.0 | **อัปเดตล่าสุด:** 2025-11-26

## สารบัญ

1. [ภาพรวมของระบบ](#1-ภาพรวมของระบบ)
2. [ขั้นตอนที่ 1: สร้างบัญชี LINE Developers](#2-ขั้นตอนที่-1-สร้างบัญชี-line-developers)
3. [ขั้นตอนที่ 2: สร้าง Messaging API Channel](#3-ขั้นตอนที่-2-สร้าง-messaging-api-channel)
4. [ขั้นตอนที่ 3: รับค่า API Credentials](#4-ขั้นตอนที่-3-รับค่า-api-credentials)
5. [ขั้นตอนที่ 4: ตั้งค่า Webhook](#5-ขั้นตอนที่-4-ตั้งค่า-webhook)
6. [ขั้นตอนที่ 5: ตั้งค่าในระบบ TP-Affiliate](#6-ขั้นตอนที่-5-ตั้งค่าในระบบ-tp-affiliate)
7. [ขั้นตอนที่ 6: ตั้งค่า AI Provider](#7-ขั้นตอนที่-6-ตั้งค่า-ai-provider)
8. [การทดสอบระบบ](#8-การทดสอบระบบ)
9. [การแก้ไขปัญหา](#9-การแก้ไขปัญหา)

---

## 1. ภาพรวมของระบบ

### API ที่ต้องใช้ทั้งหมด

| ประเภท | API Keys | จำเป็น | ใช้ทำอะไร |
|--------|----------|--------|-----------|
| **LINE Messaging API** | | | |
| - Channel ID | `LINE_CHANNEL_ID` | ✅ | ระบุ Channel |
| - Channel Secret | `LINE_CHANNEL_SECRET` | ✅ | ยืนยัน Webhook |
| - Channel Access Token | `LINE_CHANNEL_ACCESS_TOKEN` | ✅ | ส่งข้อความ |
| **AI Provider** (เลือก 1) | | | |
| - OpenAI | API Key | 🔶 | ChatGPT/GPT-4 |
| - Anthropic | API Key | 🔶 | Claude AI |
| - Google Gemini | API Key | 🔶 | Gemini Pro |
| - DeepSeek | API Key | 🔶 | DeepSeek Chat |

### Flow การทำงาน

```
ผู้ใช้ส่งข้อความ → LINE Server → Webhook → TP-Affiliate → AI Provider → ตอบกลับ
```

---

## 2. ขั้นตอนที่ 1: สร้างบัญชี LINE Developers

### 2.1 เข้าสู่ LINE Developers Console

1. ไปที่ **https://developers.line.biz/**
2. กดปุ่ม **"Log in"** มุมขวาบน
3. ใช้ **LINE Account** ในการล็อกอิน (แนะนำใช้บัญชีธุรกิจ)

### 2.2 สร้าง Provider

Provider คือ "เจ้าของ" ของ Channels ทั้งหมด (เหมือนชื่อบริษัท)

1. กด **"Create a new provider"**
2. ใส่ชื่อ Provider:
   - ✅ ชื่อบริษัท/องค์กร (เช่น "Thai Prompt Co., Ltd.")
   - ✅ ชื่อแบรนด์ (เช่น "TP Affiliate")
3. กด **"Create"**

---

## 3. ขั้นตอนที่ 2: สร้าง Messaging API Channel

### 3.1 สร้าง Channel ใหม่

1. ในหน้า Provider กด **"Create a new channel"**
2. เลือก **"Messaging API"**

### 3.2 กรอกข้อมูล Channel

| ฟิลด์ | คำอธิบาย | ตัวอย่าง |
|------|---------|---------|
| **Channel type** | เลือก Messaging API | Messaging API |
| **Provider** | เลือก Provider ที่สร้าง | TP Affiliate |
| **Channel icon** | รูปโปรไฟล์ Bot (อัปโหลดได้) | รูปโลโก้บริษัท |
| **Channel name** | ชื่อ Bot ที่ผู้ใช้จะเห็น | TP Assistant |
| **Channel description** | คำอธิบาย Bot | ผู้ช่วยอัจฉริยะสำหรับระบบ Affiliate |
| **Category** | หมวดหมู่ธุรกิจ | Shopping > E-commerce |
| **Subcategory** | หมวดย่อย | Marketing |
| **Email address** | อีเมลติดต่อ | support@example.com |
| **Privacy policy URL** | (ถ้ามี) | https://example.com/privacy |
| **Terms of use URL** | (ถ้ามี) | https://example.com/terms |

3. ติ๊ก ✅ ยอมรับ Terms and Conditions
4. กด **"Create"**

---

## 4. ขั้นตอนที่ 3: รับค่า API Credentials

หลังจากสร้าง Channel เสร็จ ให้เข้าไปหาค่าต่างๆ:

### 4.1 Channel ID และ Channel Secret

**ตำแหน่ง:** Tab **"Basic settings"**

```
┌─────────────────────────────────────────┐
│ Basic settings                          │
├─────────────────────────────────────────┤
│ Channel ID:      1234567890            │  ← คัดลอกค่านี้
│ Channel secret:  abc123def456ghi789    │  ← คัดลอกค่านี้
│ Assertion Signing Key: (ไม่ต้องใช้)      │
└─────────────────────────────────────────┘
```

### 4.2 Channel Access Token

**ตำแหน่ง:** Tab **"Messaging API"**

1. เลื่อนลงไปที่ **"Channel access token"**
2. กดปุ่ม **"Issue"** เพื่อสร้าง Token
3. คัดลอก Token ที่ได้ (ยาวมาก เริ่มต้นด้วย eyJ...)

```
┌─────────────────────────────────────────┐
│ Channel access token                    │
├─────────────────────────────────────────┤
│ [Issue] button                          │
│                                         │
│ Token: eyJhbGciOiJIUzI1NiIsInR5cCI...   │  ← คัดลอกค่านี้
│ (Long-lived channel access token)       │
└─────────────────────────────────────────┘
```

---

## 5. ขั้นตอนที่ 4: ตั้งค่า Webhook

Webhook คือ URL ที่ LINE จะส่งข้อความมาหาระบบเรา

### 5.1 ตั้งค่า Webhook URL

**ตำแหน่ง:** Tab **"Messaging API"** → ส่วน **"Webhook settings"**

1. **Webhook URL:** ใส่ URL ของระบบ:
   ```
   https://your-domain.com/api/webhook/line
   ```

   ตัวอย่าง:
   - Production: `https://affiliate.example.com/api/webhook/line`
   - Development: `https://dev.affiliate.example.com/api/webhook/line`

2. กด **"Update"**

3. กดปุ่ม **"Verify"** เพื่อทดสอบ
   - ✅ Success = ตั้งค่าถูกต้อง
   - ❌ Error = ตรวจสอบ URL และการตั้งค่าระบบ

### 5.2 เปิดใช้งาน Webhook

| ตัวเลือก | ค่าที่แนะนำ | คำอธิบาย |
|---------|------------|---------|
| **Use webhook** | ✅ เปิด | รับข้อความผ่าน Webhook |
| **Webhook redelivery** | ✅ เปิด | ส่งซ้ำถ้าล้มเหลว |
| **Error statistics aggregation** | ✅ เปิด | เก็บสถิติ error |

### 5.3 ปิด Auto-reply (สำคัญ!)

เพื่อให้ AI ตอบแทน ต้องปิด Auto-reply ของ LINE

**ตำแหน่ง:** Tab **"Messaging API"** → ส่วน **"LINE Official Account features"**

1. กด **"Edit"** ที่ Auto-reply messages
2. เลือก **"Disabled"**
3. บันทึก

| ฟีเจอร์ | ค่าที่แนะนำ |
|--------|------------|
| Auto-reply messages | ❌ Disabled |
| Greeting messages | ❌ Disabled (ให้ AI ตอบแทน) |

---

## 6. ขั้นตอนที่ 5: ตั้งค่าในระบบ TP-Affiliate

### 6.1 ตั้งค่าใน .env

เปิดไฟล์ `.env` และเพิ่มค่าดังนี้:

```env
# =============================================================================
# LINE OA & LINE BOT CONFIGURATION
# =============================================================================

# LINE Messaging API
LINE_CHANNEL_ID=1234567890                    # ← ใส่ค่าจากขั้นตอน 4.1
LINE_CHANNEL_SECRET=abc123def456ghi789        # ← ใส่ค่าจากขั้นตอน 4.1
LINE_CHANNEL_ACCESS_TOKEN=eyJhbGciOiJ...      # ← ใส่ค่าจากขั้นตอน 4.2

# LINE Webhook
LINE_WEBHOOK_VERIFY_SIGNATURE=true            # เปิดการตรวจสอบ signature
```

### 6.2 ตั้งค่าผ่าน Admin Panel

1. เข้าสู่ระบบ Admin
2. ไปที่ **"LINE OA"** → **"ตั้งค่าทั่วไป"**
3. กรอกข้อมูล:
   - Channel ID
   - Channel Secret
   - Channel Access Token
4. กด **"บันทึก"**

---

## 7. ขั้นตอนที่ 6: ตั้งค่า AI Provider

### 7.1 เลือก AI Provider

ระบบรองรับ AI หลายตัว เลือกตัวที่เหมาะสม:

| Provider | ราคา | ความเร็ว | คุณภาพ | ภาษาไทย |
|----------|------|---------|--------|---------|
| **OpenAI GPT-4o** | ปานกลาง | เร็ว | ดีมาก | ดีมาก |
| **OpenAI GPT-3.5** | ถูก | เร็วมาก | ดี | ดี |
| **Anthropic Claude** | ปานกลาง | เร็ว | ดีมาก | ดีมาก |
| **Google Gemini** | ถูก/ฟรี | เร็ว | ดี | ดี |
| **DeepSeek** | ถูกมาก | เร็ว | ดี | ปานกลาง |

### 7.2 ขอ API Key จาก Provider

#### OpenAI (ChatGPT, GPT-4)

1. ไปที่ **https://platform.openai.com/api-keys**
2. ล็อกอินหรือสมัครบัญชี
3. กด **"Create new secret key"**
4. ตั้งชื่อ key (เช่น "TP Affiliate Bot")
5. **คัดลอก API Key** (แสดงครั้งเดียว!)
   - รูปแบบ: `sk-...`

**Models ที่แนะนำ:**
- `gpt-4o` - เร็วและฉลาด (แนะนำ)
- `gpt-4o-mini` - ถูกและเร็ว
- `gpt-4-turbo` - ฉลาดมาก

---

#### Anthropic (Claude)

1. ไปที่ **https://console.anthropic.com/**
2. ล็อกอินหรือสมัครบัญชี
3. ไปที่ **"API Keys"**
4. กด **"Create Key"**
5. ตั้งชื่อและกด **"Create Key"**
6. **คัดลอก API Key**
   - รูปแบบ: `sk-ant-...`

**Models ที่แนะนำ:**
- `claude-3-5-sonnet-20241022` - สมดุลดี (แนะนำ)
- `claude-3-5-haiku-20241022` - เร็วและถูก
- `claude-3-opus-20240229` - ฉลาดที่สุด (แพง)

---

#### Google Gemini

1. ไปที่ **https://aistudio.google.com/app/apikey**
2. ล็อกอินด้วย Google Account
3. กด **"Create API Key"**
4. เลือก Project (หรือสร้างใหม่)
5. **คัดลอก API Key**
   - รูปแบบ: `AIza...`

**Models ที่แนะนำ:**
- `gemini-1.5-pro` - ฉลาดมาก
- `gemini-1.5-flash` - เร็วมาก (แนะนำ)

---

#### DeepSeek

1. ไปที่ **https://platform.deepseek.com/**
2. ล็อกอินหรือสมัครบัญชี
3. ไปที่ **"API Keys"**
4. กด **"Create API Key"**
5. **คัดลอก API Key**
   - รูปแบบ: `sk-...`

**Models ที่แนะนำ:**
- `deepseek-chat` - ภาษาทั่วไป
- `deepseek-coder` - เขียนโค้ด

---

### 7.3 ตั้งค่า AI ในระบบ

1. เข้า Admin Panel
2. ไปที่ **"LINE Bot"** → **"AI Settings"**
3. กด **"สร้างการตั้งค่าใหม่"**
4. กรอกข้อมูล:

| ฟิลด์ | คำอธิบาย | ตัวอย่าง |
|------|---------|---------|
| **ชื่อการตั้งค่า** | ชื่อสำหรับจำ | "AI ตอบลูกค้าทั่วไป" |
| **Provider** | เลือก AI | OpenAI |
| **API Key** | Key ที่ได้มา | sk-xxx... |
| **Model** | รุ่น AI | gpt-4o |
| **Temperature** | ความสร้างสรรค์ (0-2) | 0.7 |
| **Max Tokens** | ความยาวคำตอบ | 1000 |
| **System Prompt** | คำสั่งให้ AI | "คุณคือผู้ช่วยขายที่เป็นมิตร..." |

5. กด **"บันทึก"**
6. กด **"ทดสอบ"** เพื่อเช็คว่าใช้งานได้

---

## 8. การทดสอบระบบ

### 8.1 ทดสอบ Webhook

1. ไปที่ LINE Developers Console
2. Tab **"Messaging API"** → **"Webhook settings"**
3. กด **"Verify"**
4. ผลลัพธ์:
   - ✅ **Success** = ใช้งานได้
   - ❌ **Error** = ดูข้อความ error

### 8.2 ทดสอบ AI

1. ไปที่ Admin Panel → **"LINE Bot"** → **"AI Settings"**
2. เลือก AI Setting ที่ต้องการทดสอบ
3. กด **"ทดสอบ"**
4. พิมพ์ข้อความทดสอบ: "สวัสดีครับ"
5. ตรวจสอบคำตอบ

### 8.3 ทดสอบจริงบน LINE

1. เพิ่ม LINE Bot เป็นเพื่อน (สแกน QR Code จาก Messaging API tab)
2. ส่งข้อความ "สวัสดี"
3. Bot ควรตอบกลับภายใน 3-5 วินาที

---

## 9. การแก้ไขปัญหา

### ปัญหา: Webhook Verify ไม่ผ่าน

**สาเหตุที่เป็นไปได้:**
1. URL ไม่ถูกต้อง
2. HTTPS certificate ไม่ valid
3. Route ไม่ได้ลงทะเบียน

**วิธีแก้:**
```bash
# ตรวจสอบ route
php artisan route:list | grep webhook

# ควรเห็น:
# POST api/webhook/line  → LineWebhookController@handle
```

---

### ปัญหา: Bot ไม่ตอบข้อความ

**ตรวจสอบ:**
1. ✅ Use webhook เปิดอยู่หรือไม่
2. ✅ Auto-reply ปิดอยู่หรือไม่
3. ✅ AI Setting active อยู่หรือไม่
4. ✅ API Key ถูกต้องหรือไม่

**ดู Log:**
```bash
# ดู log ล่าสุด
tail -f storage/logs/laravel.log

# หา error เกี่ยวกับ LINE
grep -i "line" storage/logs/laravel.log | tail -50
```

---

### ปัญหา: API Key ไม่ถูกต้อง

**Error ที่พบ:**
- OpenAI: `Invalid API key provided`
- Anthropic: `Authentication failed`
- Gemini: `API key not valid`

**วิธีแก้:**
1. ตรวจสอบว่าคัดลอก key ครบถ้วน
2. ตรวจสอบว่า key ยังไม่หมดอายุ
3. ตรวจสอบว่ามี credit เหลือ
4. สร้าง key ใหม่

---

### ปัญหา: ตอบช้ามาก

**สาเหตุ:**
1. AI model ช้า (เช่น GPT-4 ช้ากว่า GPT-3.5)
2. Max tokens สูงเกินไป
3. Internet ช้า

**วิธีแก้:**
1. เปลี่ยนไปใช้ model เร็วกว่า (gpt-4o-mini, claude-3-haiku)
2. ลด max_tokens เหลือ 500-1000
3. ตรวจสอบ server connectivity

---

## ภาคผนวก

### A. ตัวอย่าง System Prompt ที่ดี

```
คุณคือผู้ช่วยขายที่เป็นมิตรของ [ชื่อบริษัท]

หน้าที่ของคุณ:
- ตอบคำถามเกี่ยวกับสินค้าและบริการ
- แนะนำสินค้าที่เหมาะสม
- ช่วยเหลือเรื่องการสั่งซื้อ
- ให้ข้อมูลโปรโมชั่น

กฎการตอบ:
- ใช้ภาษาไทยที่สุภาพ ลงท้ายด้วย "ค่ะ/ครับ"
- ตอบสั้นกระชับ ไม่เกิน 3 ย่อหน้า
- ถ้าไม่รู้คำตอบ ให้แนะนำติดต่อ LINE: @support
- ไม่พูดเรื่องคู่แข่ง
```

### B. Webhook URL ที่รองรับ

| Environment | URL |
|-------------|-----|
| Production | `https://your-domain.com/api/webhook/line` |
| Staging | `https://staging.your-domain.com/api/webhook/line` |
| Development | ใช้ ngrok: `https://xxx.ngrok.io/api/webhook/line` |

### C. ลิงก์ที่เป็นประโยชน์

- LINE Developers Console: https://developers.line.biz/
- LINE Messaging API Docs: https://developers.line.biz/en/docs/messaging-api/
- OpenAI Platform: https://platform.openai.com/
- Anthropic Console: https://console.anthropic.com/
- Google AI Studio: https://aistudio.google.com/

---

**เอกสารนี้จัดทำโดย:** TP-Affiliate Development Team
**ติดต่อ:** support@tp-affiliate.com
