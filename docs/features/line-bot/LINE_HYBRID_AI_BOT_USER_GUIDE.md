# 🤖 คู่มือการใช้งาน LINE Hybrid AI Bot - User Guide

> **คู่มือการใช้งาน Hybrid AI Bot System**
> สำหรับผู้ดูแลระบบและทีมงาน

**Version**: 3.0.0
**Last Updated**: 2025-11-22
**สำหรับ**: TP-Affiliate V3

---

## 📋 สารบัญ

1. [ภาพรวม Hybrid AI Bot](#ภาพรวมsystem-hybrid-ai-bot)
2. [การจัดการ AI Bot Configuration](#การจัดการ-ai-bot-configuration)
3. [การจัดการ Keywords](#การจัดการ-keywords)
4. [การตั้งค่า Hybrid Mode](#การตั้งค่า-hybrid-mode)
5. [Use Cases & Examples](#use-cases--examples)
6. [Knowledge Base Management](#knowledge-base-management)
7. [Conversation History](#conversation-history)
8. [Broadcast Messages](#broadcast-messages)
9. [Analytics & Monitoring](#analytics--monitoring)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

---

## ภาพรวม Hybrid AI Bot

### 🎯 Hybrid AI Bot คืออะไร?

**Hybrid AI Bot** คือระบบแชทบอทที่รวม **Chatbot ธรรมดา** (Keyword-based) กับ **AI Bot** (AI-powered) เข้าด้วยกัน เพื่อให้ได้ทั้ง:

- ✅ **ความเร็ว** - ตอบคำถามที่พบบ่อยได้ทันที (ด้วย Keywords)
- ✅ **ความยืดหยุ่น** - ตอบคำถามที่ซับซ้อนได้ (ด้วย AI)
- ✅ **ประหยัดต้นทุน** - ไม่ต้องเรียก AI ทุกครั้ง

### 🔄 การทำงานของ Hybrid Bot

```
                    User ส่งข้อความ
                           │
                           ▼
              ┌────────────────────────┐
              │  1. Keyword Matching   │
              └────────┬───────────────┘
                       │
         ┌─────────────┴─────────────┐
         │                           │
    Match? YES                   Match? NO
         │                           │
         ▼                           ▼
┌────────────────┐         ┌────────────────┐
│ ส่งคำตอบ       │         │ Forward to AI  │
│ ที่ตั้งไว้      │         │ Bot (ChatGPT/  │
│                │         │ Claude/Gemini) │
└────────────────┘         └────────────────┘
         │                           │
         └───────────┬───────────────┘
                     ▼
              ส่งคำตอบกลับ User
```

### 📊 ความแตกต่างระหว่าง Chatbot, AI Bot, และ Hybrid Bot

| ประเภท | ข้อดี | ข้อเสีย | เหมาะกับ |
|--------|-------|---------|----------|
| **Chatbot** (Keyword-based) | เร็ว, แม่นยำ, ไม่มีค่าใช้จ่าย | ตอบได้แค่คำถามที่ตั้งไว้ | FAQ, ข้อมูลพื้นฐาน |
| **AI Bot** (AI-powered) | ตอบคำถามซับซ้อนได้, เข้าใจบริบท | ช้ากว่า, มีค่าใช้จ่าย API | คำถามที่หลากหลาย, สนทนา |
| **Hybrid Bot** ⭐ | ได้ทั้งความเร็วและความยืดหยุ่น | ต้องจัดการ 2 ระบบ | ทุกสถานการณ์ (แนะนำ) |

---

## การจัดการ AI Bot Configuration

### 📍 เข้าสู่หน้าจัดการ AI Bot

```
Admin Panel > LINE OA & AI > AI Chat Bot
```

### ✨ สร้าง AI Bot ใหม่

**ขั้นตอนที่ 1: คลิก "สร้าง AI Bot ใหม่"**

![Create AI Bot Button](path/to/screenshot)

**ขั้นตอนที่ 2: กรอกข้อมูลพื้นฐาน**

```yaml
ชื่อ Bot: Customer Support Bot
คำอธิบาย: ตอบคำถามลูกค้าเกี่ยวกับสินค้าและบริการ
สถานะ: ✅ เปิดใช้งาน
```

**ขั้นตอนที่ 3: เลือก AI Provider**

เลือก AI Provider ที่ต้องการใช้:

| Provider | ข้อดี | ข้อเสีย | ราคา |
|----------|-------|---------|------|
| **OpenAI (ChatGPT)** | ดีรอบด้าน, ตอบแม่นยำ | ราคาแพง | ~$0.03/1K คำ |
| **Anthropic (Claude)** | เข้าใจ context ยาว, สุภาพ | ราคาปานกลาง | ~$0.01/1K คำ |
| **Google (Gemini)** | ภาษาไทยดี, ฟรี | มี quota จำกัด | ฟรี (มี limit) |
| **DeepSeek** | ราคาถูกที่สุด | คุณภาพต่ำกว่า | ~$0.0015/1K คำ |

**ขั้นตอนที่ 4: ตั้งค่า AI Configuration**

```yaml
# Model Selection
Model:
  - ChatGPT: gpt-4 (แม่นยำ) หรือ gpt-3.5-turbo (เร็ว)
  - Claude: claude-3-opus-20240229 (ดีที่สุด)
  - Gemini: gemini-pro
  - DeepSeek: deepseek-chat

# AI Parameters
Temperature: 0.7
  (0.0 = ตอบตายตัว, 1.0 = ตอบสร้างสรรค์)
  แนะนำ: 0.5-0.7 สำหรับ customer support

Max Tokens: 500
  (จำนวนคำสูงสุดในการตอบ)
  แนะนำ: 300-500 สำหรับคำตอบทั่วไป

# System Prompt (สำคัญมาก!)
System Prompt:
  คุณคือ AI ผู้ช่วยของ TP-Affiliate ชื่อ "Bot ผู้ช่วย"

  ความสามารถของคุณ:
  - ตอบคำถามเกี่ยวกับระบบ Affiliate Marketing
  - แนะนำสินค้าและบริการ
  - ช่วยเหลือในการสมัครสมาชิก
  - ตอบคำถามเกี่ยวกับการถอนเงินและคอมมิชชั่น

  กฎการตอบ:
  - ตอบด้วยภาษาไทยที่สุภาพและเป็นกันเอง
  - ใช้ emoji เพื่อให้ดูเป็นมิตร (ไม่เกิน 2-3 ตัว/ข้อความ)
  - ตอบให้กระชับ ไม่เกิน 3-4 ประโยค ยกเว้นคำถามที่ซับซ้อน
  - ถ้าไม่รู้คำตอบ ให้บอกตรงๆ และแนะนำให้ติดต่อเจ้าหน้าที่
  - ห้ามให้คำแนะนำทางการเงินหรือลงทุน

  ข้อมูลสำคัญ:
  - เว็บไซต์: https://yourdomain.com
  - LINE OA: @yourlineoa
  - เบอร์โทร: 02-123-4567
  - Email: support@yourdomain.com
```

**ขั้นตอนที่ 5: ตั้งค่า Conversation Settings**

```yaml
จำนวนข้อความย้อนหลัง (Context): 10
  (AI จะจำบทสนทนา 10 ข้อความล่าสุด)
  แนะนำ: 5-15 ข้อความ

Timeout (วินาที): 30
  (เวลารอคำตอบจาก AI สูงสุด)
  แนะนำ: 20-30 วินาที

Fallback Message:
  ขออภัยค่ะ ขณะนี้ระบบ AI มีปัญหาชั่วคราว 😔

  กรุณา:
  - ลองใหม่อีกครั้งในอีกสักครู่
  - หรือติดต่อเจ้าหน้าที่: 02-123-4567

  ขอบคุณสำหรับความเข้าใจค่ะ 🙏
```

**ขั้นตอนที่ 6: ทดสอบ AI Bot**

1. คลิก **"ทดสอบ AI"**
2. กรอกข้อความทดสอบ:
   ```
   สวัสดีครับ TP-Affiliate มีบริการอะไรบ้าง?
   ```
3. คลิก **"ส่ง"**
4. ดูคำตอบจาก AI และปรับแต่ง System Prompt ตามต้องการ

---

## การจัดการ Keywords

### 📍 เข้าสู่หน้าจัดการ Keywords

```
Admin Panel > LINE OA & AI > Bot Keywords
```

### ✨ สร้าง Keyword ใหม่

**ขั้นตอนที่ 1: คลิก "สร้าง Keyword ใหม่"**

**ขั้นตอนที่ 2: กรอกข้อมูล Keyword**

#### **ตัวอย่างที่ 1: Simple Text Response**

```yaml
Keyword: ราคา, ค่าบริการ, เท่าไหร่, price
Match Type: Contains (มีคำใดคำหนึ่ง)
Priority: 10 (สูงสุด)

Response Type: Text
Response:
  💰 ราคาบริการของเรา

  📦 Basic - 990 บาท/เดือน
  - สมาชิก 100 คน
  - Commission tracking
  - Basic support

  📦 Pro - 1,990 บาท/เดือน
  - สมาชิก 500 คน
  - Advanced analytics
  - Priority support
  - LINE OA integration

  📦 Enterprise - 4,990 บาท/เดือน
  - สมาชิก ไม่จำกัด
  - Full features
  - Dedicated support
  - Custom integration

  สนใจสอบถามเพิ่มเติม: 02-123-4567 📞
```

#### **ตัวอย่างที่ 2: Multiple Keywords**

```yaml
Keyword: วิธีสมัคร, สมัครยังไง, register, sign up
Match Type: Contains
Priority: 10

Response Type: Text
Response:
  📝 วิธีสมัครสมาชิก TP-Affiliate

  ขั้นตอน:
  1️⃣ คลิก "สมัครสมาชิก" บนหน้าเว็บ
  2️⃣ กรอกข้อมูลส่วนตัว
  3️⃣ ยืนยัน OTP ผ่าน LINE
  4️⃣ ตั้งรหัสผ่าน
  5️⃣ กรอกรหัสผู้แนะนำ (ถ้ามี)
  6️⃣ เสร็จสิ้น! 🎉

  💡 Tips: ใช้ LINE Login เพื่อความสะดวก

  ลงทะเบียนที่: https://yourdomain.com/register
```

#### **ตัวอย่างที่ 3: Contact Information**

```yaml
Keyword: ติดต่อ, contact, โทร, เบอร์
Match Type: Contains
Priority: 8

Response Type: Text
Response:
  📞 ช่องทางติดต่อ TP-Affiliate

  🏢 สำนักงาน
  123 ถนนสุขุมวิท แขวงคลองเตย
  เขตคลองเตย กรุงเทพฯ 10110

  📱 โทรศัพท์: 02-123-4567
  📧 Email: support@yourdomain.com
  💬 LINE OA: @tpaffiliate
  🌐 Website: https://yourdomain.com

  ⏰ เวลาทำการ:
  จันทร์ - ศุกร์: 09:00 - 18:00 น.
  เสาร์: 09:00 - 15:00 น.
  อาทิตย์: ปิดทำการ
```

#### **ตัวอย่างที่ 4: FAQ - Withdrawal**

```yaml
Keyword: ถอนเงิน, withdraw, เบิก, รับเงิน
Match Type: Contains
Priority: 9

Response Type: Text
Response:
  💵 การถอนเงินคอมมิชชั่น

  ✅ เงื่อนไข:
  - คอมมิชชั่นขั้นต่ำ: 500 บาท
  - ถอนได้ทุกวันอังคาร และศุกร์
  - ผ่านบัญชีธนาคารที่ลงทะเบียนไว้เท่านั้น

  📝 วิธีถอน:
  1. เข้า Dashboard
  2. คลิก "ถอนเงิน"
  3. กรอกจำนวนเงิน
  4. ยืนยัน OTP
  5. รอรับเงิน 1-3 ชั่วโมง

  ⚡ Express Withdrawal (ภายใน 30 นาที):
  - สำหรับสมาชิก Pro ขึ้นไป
  - ค่าธรรมเนียม 20 บาท/ครั้ง

  คำถาม? โทร: 02-123-4567
```

#### **ตัวอย่างที่ 5: Fallback - Forward to AI**

```yaml
Keyword: *
Match Type: Fallback (ไม่ match keyword ใดเลย)
Priority: 1 (ต่ำสุด)

Response Type: Forward to AI
AI Bot: [เลือก Customer Support Bot]

หมายเหตุ:
- Keyword "*" จะทำงานเมื่อไม่ match keyword อื่นๆ
- Priority ต้องเป็น 1 เสมอ (ต่ำสุด)
- จะส่งต่อข้อความไปยัง AI Bot ที่เลือก
```

---

## การตั้งค่า Hybrid Mode

### 🔄 Hybrid Mode Strategy

การตั้งค่า Hybrid Mode ที่ดีควรมี:

1. **High Priority Keywords** (9-10) - คำถามที่พบบ่อยและต้องตอบเร็ว
2. **Medium Priority Keywords** (5-8) - คำถามทั่วไป
3. **Low Priority Keywords** (2-4) - คำถามเฉพาะเจาะจง
4. **Fallback** (1) - ส่งต่อไป AI

### 📋 Priority Guidelines

```
Priority 10: คำถามสำคัญและพบบ่อยมาก
├─ ราคา, แพ็กเกจ
├─ วิธีสมัคร, ลงทะเบียน
├─ ติดต่อ, เบอร์โทร
└─ ถอนเงิน, รับเงิน

Priority 8-9: คำถามพบบ่อย
├─ คอมมิชชั่น, Commission
├─ Affiliate link, ลิงก์แนะนำ
├─ Dashboard, รายงาน
└─ โปรโมชั่น, ส่วนลด

Priority 5-7: คำถามทั่วไป
├─ สินค้า, Products
├─ การชำระเงิน, Payment
├─ การจัดส่ง, Shipping
└─ รีวิว, Reviews

Priority 2-4: คำถามเฉพาะเจาะจง
├─ API Integration
├─ Webhook Setup
└─ Custom Features

Priority 1: Fallback (ส่งต่อไป AI)
└─ * (ทุกอย่างที่ไม่ match)
```

### 🎯 Best Practices

1. **ตั้ง Keywords สำหรับคำถามที่พบบ่อย (FAQ)**
   - ทำให้ตอบได้เร็ว
   - ประหยัดค่า API
   - คำตอบแม่นยำ 100%

2. **ใช้ AI สำหรับคำถามที่ซับซ้อน**
   - คำถามที่ต้องการบริบท
   - คำถามที่มีความหลากหลาย
   - การสนทนาที่ต่อเนื่อง

3. **ปรับ Temperature ตาม Use Case**
   ```
   Customer Support: 0.5-0.7 (ตอบตายตัวพอสมควร)
   Creative Writing: 0.8-1.0 (ตอบสร้างสรรค์)
   Technical Support: 0.3-0.5 (ตอบแม่นยำ)
   ```

4. **กำหนด Max Tokens ให้เหมาะสม**
   ```
   Quick Answers: 150-300 tokens
   Normal Answers: 300-500 tokens
   Detailed Answers: 500-1000 tokens
   ```

---

## Use Cases & Examples

### 📦 Use Case 1: Customer Support Bot

**เป้าหมาย**: ตอบคำถามลูกค้าทั่วไป

**Keywords Setup:**

| Keyword | Response | Priority |
|---------|----------|----------|
| `ราคา, แพ็กเกจ` | รายการราคาบริการ | 10 |
| `วิธีสมัคร` | ขั้นตอนการสมัคร | 10 |
| `ติดต่อ` | ข้อมูลติดต่อ | 10 |
| `ถอนเงิน` | วิธีถอนเงิน | 9 |
| `*` (Fallback) | Forward to AI | 1 |

**AI Configuration:**
```yaml
Provider: OpenAI GPT-4
Temperature: 0.6
Max Tokens: 400
System Prompt: "คุณคือผู้ช่วยฝ่ายบริการลูกค้า..."
```

**ผลลัพธ์:**
- 70% คำถามตอบด้วย Keywords (เร็ว, ไม่มีค่าใช้จ่าย)
- 30% คำถามตอบด้วย AI (ยืดหยุ่น)

---

### 🛍️ Use Case 2: E-commerce Support Bot

**เป้าหมาย**: แนะนำสินค้าและตอบคำถามเกี่ยวกับการสั่งซื้อ

**Keywords Setup:**

| Keyword | Response | Priority |
|---------|----------|----------|
| `สินค้าแนะนำ, bestseller` | รายการสินค้าขายดี | 10 |
| `โปรโมชั่น, ส่วนลด` | โปรโมชั่นปัจจุบัน | 10 |
| `จัดส่ง, shipping` | ข้อมูลการจัดส่ง | 9 |
| `คืนสินค้า, refund` | นโยบายคืนสินค้า | 9 |
| `*` (Fallback) | Forward to AI | 1 |

**AI Configuration:**
```yaml
Provider: Google Gemini Pro
Temperature: 0.7
Max Tokens: 500
System Prompt: "คุณคือผู้ช่วยแนะนำสินค้า..."
Knowledge Base: รายการสินค้า, โปรโมชั่น
```

**Flow ตัวอย่าง:**
```
User: "สินค้าแนะนำอะไรบ้าง?"
Bot: [แสดง Flex Message สินค้า Top 5]

User: "สินค้าตัวแรกมีสีอื่นไหม?"
Bot (AI): "สินค้า [ชื่อสินค้า] มีให้เลือก 4 สี ได้แก่..."
```

---

### 🎓 Use Case 3: MLM Signup Bot

**เป้าหมาย**: ช่วยสมัครสมาชิก MLM

**Keywords Setup:**

| Keyword | Response | Priority |
|---------|----------|----------|
| `สมัคร, register` | เริ่ม 7-step signup flow | 10 |
| `ตรวจสอบสถานะ` | เช็คสถานะการสมัคร | 10 |
| `คอมมิชชั่น` | อัตราคอมมิชชั่น | 9 |
| `ระบบ MLM` | อธิบายระบบ MLM | 8 |
| `*` (Fallback) | Forward to AI | 1 |

**AI Configuration:**
```yaml
Provider: Claude 3 Sonnet
Temperature: 0.5
Max Tokens: 600
System Prompt: "คุณคือผู้เชี่ยวชาญด้าน MLM..."
Knowledge Base: MLM System, Commission Plan
```

---

## Knowledge Base Management

### 📚 เพิ่ม Knowledge Base

Knowledge Base คือแหล่งข้อมูลที่ AI สามารถอ้างอิงได้

**ขั้นตอนที่ 1: ไปที่ AI Bot > Knowledge Base**

**ขั้นตอนที่ 2: เลือกประเภท Knowledge Base**

#### **1. URL Source**

```yaml
Type: URL
URL: https://yourdomain.com/products
Description: รายการสินค้าทั้งหมด

AI จะ:
- Fetch HTML จาก URL
- Extract text content
- ใช้ข้อมูลในการตอบคำถาม
```

#### **2. File Upload**

```yaml
Type: File
File: products.pdf, faq.docx, manual.txt
Description: เอกสารข้อมูลสินค้า

รองรับไฟล์:
- PDF
- Word (doc, docx)
- Text (txt)
- CSV
- Excel (xls, xlsx)
```

#### **3. Text Input**

```yaml
Type: Text
Content:
  ข้อมูลสินค้า TP-Affiliate Pro Package

  คุณสมบัติ:
  - สมาชิก: 500 คน
  - Commission tracking: แบบ real-time
  - Analytics: Advanced dashboard
  - Support: Priority support 24/7
  - LINE Integration: รองรับ
  - API Access: มี
  - Custom Domain: มี

  ราคา: 1,990 บาท/เดือน

  เหมาะสำหรับ:
  - ธุรกิจขนาดกลาง
  - ทีมงาน 5-10 คน
  - ต้องการ analytics เชิงลึก
```

**ขั้นตอนที่ 3: Sync Knowledge Base**

- คลิก **"Sync"** เพื่ออัพเดทข้อมูล
- AI จะใช้ข้อมูลล่าสุดในการตอบคำถาม

---

## Conversation History

### 📖 ดู Conversation History

```
Admin Panel > LINE OA & AI > Conversations
```

**ข้อมูลที่แสดง:**
- User ที่สนทนา
- จำนวนข้อความ
- Timestamp
- AI Provider ที่ใช้
- สถานะการสนทนา

**การกรอง:**
- Filter by User
- Filter by Date Range
- Filter by AI Provider
- Search by Message Content

**การ Export:**
- Export to CSV
- Export to Excel
- Export selected conversations

---

## Broadcast Messages

### 📢 ส่ง Broadcast Message

```
Admin Panel > LINE OA & AI > Broadcast
```

**ขั้นตอนที่ 1: สร้าง Broadcast**

```yaml
ชื่อ Campaign: Flash Sale November
Message Type: Flex Message (แนะนำ)

ผู้รับ:
- ✅ All users
- หรือเลือก Segment: Active users, Premium members, etc.

กำหนดเวลาส่ง:
- ส่งทันที
- หรือกำหนดเวลา: 2025-11-25 10:00 AM
```

**ขั้นตอนที่ 2: ออกแบบข้อความ**

```blade
Option 1: Text Message
🎉 Flash Sale พิเศษ!
ลดสูงสุด 50% วันนี้เท่านั้น!

Option 2: Flex Message (แนะนำ)
[ใช้ Flex Message Builder]
- รูปสินค้า
- ราคาเดิม/ราคาลด
- ปุ่ม "ซื้อเลย"
```

**ขั้นตอนที่ 3: Preview & Send**

- Preview ก่อนส่ง
- Test send to yourself
- Confirm and Send

---

## Analytics & Monitoring

### 📊 Dashboard

```
Admin Panel > LINE OA & AI > Analytics
```

**Metrics ที่ติดตาม:**

1. **Message Statistics**
   - Total messages received
   - Keyword matches (%)
   - AI responses (%)
   - Response time (average)

2. **AI Usage**
   - API calls per day
   - Tokens used
   - Cost (estimated)
   - Success rate

3. **User Engagement**
   - Active users
   - New users
   - Churn rate
   - Retention rate

4. **Keyword Performance**
   - Top keywords
   - Match rate
   - Response effectiveness

5. **Conversation Metrics**
   - Average conversation length
   - Resolution rate
   - Satisfaction score (if available)

---

## Best Practices

### ✅ DO (สิ่งที่ควรทำ)

1. **ตั้ง Keywords สำหรับคำถามที่พบบ่อย**
   - ประหยัดค่า API
   - ตอบได้เร็วกว่า
   - คำตอบแม่นยำ 100%

2. **เขียน System Prompt ให้ชัดเจน**
   - กำหนด role ของ AI
   - ระบุขอบเขตความรับผิดชอบ
   - ให้คำแนะนำการตอบ

3. **ทดสอบก่อนเปิดใช้งานจริง**
   - ทดสอบ keywords ทุกตัว
   - ทดสอบ AI responses
   - ทดสอบ fallback scenarios

4. **Monitor และ Optimize**
   - ดู analytics เป็นประจำ
   - ปรับ keywords ตามความจำเป็น
   - ปรับ System Prompt เพื่อผลลัพธ์ที่ดีขึ้น

5. **Update Knowledge Base เป็นประจำ**
   - Sync ข้อมูลใหม่
   - ลบข้อมูลเก่าที่ไม่ใช้
   - เพิ่มข้อมูลสินค้า/บริการใหม่

### ❌ DON'T (สิ่งที่ไม่ควรทำ)

1. **อย่าใช้ AI สำหรับทุกคำถาม**
   - เปลืองค่า API
   - ตอบช้ากว่า keywords
   - อาจตอบผิดพลาด

2. **อย่าตั้ง System Prompt ที่คลุมเครือ**
   - AI จะสับสน
   - คำตอบไม่ตรงประเด็น
   - ไม่สอดคล้องกับแบรนด์

3. **อย่าลืม Fallback**
   - ต้องมี Keyword "*" เสมอ
   - Priority ต้องเป็น 1
   - ต้องมี Fallback Message

4. **อย่าตั้งค่า Temperature สูงเกินไป**
   - คำตอบจะไม่สม่ำเสมอ
   - อาจตอบผิดพลาด
   - ไม่เหมาะกับ customer support

5. **อย่าละเลยการ Monitor**
   - ต้องดู analytics
   - ต้องเช็คค่าใช้จ่าย API
   - ต้องปรับปรุงอย่างสม่ำเสมอ

---

## Troubleshooting

### ❓ ปัญหาที่พบบ่อย

#### **1. AI Bot ตอบช้า**

**สาเหตุ:**
- Network latency
- AI Provider ช้า
- Timeout ตั้งต่ำเกินไป

**แก้ไข:**
```
1. เพิ่ม Timeout: 30-60 วินาที
2. ลด Max Tokens: 300-400
3. เปลี่ยน Provider: ลอง Gemini (ฟรี + เร็ว)
```

#### **2. AI Bot ตอบไม่ตรงคำถาม**

**สาเหตุ:**
- System Prompt ไม่ชัดเจน
- ไม่มี Knowledge Base
- Temperature สูงเกินไป

**แก้ไข:**
```
1. ปรับ System Prompt ให้ชัดเจน
2. เพิ่ม Knowledge Base
3. ลด Temperature: 0.5-0.7
```

#### **3. Keyword ไม่ทำงาน**

**สาเหตุ:**
- Keyword ไม่ match
- Priority ต่ำเกินไป
- Keyword ไม่ active

**แก้ไข:**
```
1. ตรวจสอบ Keyword spelling
2. เพิ่ม Priority: 8-10
3. เช็คสถานะ: ต้องเป็น Active
4. ใช้ "Test Keyword" ทดสอบ
```

#### **4. API Cost สูงเกินไป**

**สาเหตุ:**
- ใช้ AI ทุกคำถาม (ไม่มี Keywords)
- Max Tokens สูงเกินไป
- ใช้ Model ที่แพง (GPT-4)

**แก้ไข:**
```
1. เพิ่ม Keywords สำหรับคำถามที่พบบ่อย
2. ลด Max Tokens: 300-400
3. ใช้ Model ถูกกว่า:
   - GPT-3.5-turbo แทน GPT-4
   - Gemini Pro (ฟรี)
   - DeepSeek (ถูกที่สุด)
```

#### **5. User ไม่ได้รับข้อความ**

**สาเหตุ:**
- LINE OA ไม่ได้ตั้งค่า
- Webhook ไม่ทำงาน
- User block LINE OA

**แก้ไข:**
```
1. ตรวจสอบ LINE OA Settings
2. Test Connection ใน Admin
3. ตรวจสอบ Webhook URL
4. ดู Logs ว่ามี errors หรือไม่
```

---

## 📚 สรุป

### 🎯 Key Takeaways

1. **Hybrid Bot = Keyword + AI**
   - ใช้ Keywords สำหรับคำถามที่พบบ่อย
   - ใช้ AI สำหรับคำถามที่ซับซ้อน

2. **Priority คือกุญแจสำคัญ**
   - 10 = คำถามสำคัญที่สุด
   - 1 = Fallback (ส่งต่อไป AI)

3. **System Prompt สำคัญมาก**
   - กำหนด role และขอบเขต
   - ให้คำแนะนำการตอบ
   - ระบุข้อมูลสำคัญ

4. **Monitor และ Optimize ต่อเนื่อง**
   - ดู Analytics เป็นประจำ
   - ปรับ Keywords ตามข้อมูล
   - Update Knowledge Base

5. **Test ก่อนเปิดใช้จริง**
   - ทดสอบทุก Keyword
   - ทดสอบ AI responses
   - ทดสอบ Fallback

---

**เอกสารนี้สร้างโดย**: Thaiprompt Development Team
**Version**: 3.0.0
**Last Updated**: 2025-11-22

**ติดต่อสอบถาม**: support@thaiprompt.com

---

*"Hybrid AI Bot - ผสมผสานความเร็วและความยืดหยุ่นอย่างลงตัว"* 🤖✨
