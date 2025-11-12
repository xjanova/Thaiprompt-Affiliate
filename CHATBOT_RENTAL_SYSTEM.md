# ระบบให้เช่าบอทแชทอัจฉริยะ (Chatbot Rental System)

ระบบให้เช่าบอทแชทที่มีความสามารถแบบไฮบริด สามารถตอบคำถามแบบ keyword-based และ AI-powered พร้อมฟีเจอร์ครบวงจรสำหรับการขายและให้เช่าบอท

## 🎯 ความสามารถหลัก

### 1. ระบบแชทบอทแบบไฮบริด (Hybrid Chatbot)
- **Keyword-based Response**: ตอบคำถามด้วย keyword ที่ตั้งไว้ล่วงหน้า (รวดเร็ว ไม่เสีย token)
- **AI Fallback**: ถ้าไม่มี keyword ตรง จะส่งต่อให้ AI ตอบ (ChatGPT, Claude, Gemini, DeepSeek)
- **ประหยัด Token**: ใช้ keyword ตอบก่อน ลด cost ในการใช้งาน AI

### 2. ระบบเชื่อมต่อหลายแพลตฟอร์ม (Multi-platform Integration)
รองรับการเชื่อมต่อกับ:
- LINE Official Account
- Facebook Messenger
- Instagram DM
- Telegram
- Discord
- WhatsApp
- Twitter DM
- Slack
- Web Widget (ฝังในเว็บไซต์)
- Custom API

### 3. ระบบโพสต์คอนเทนต์อัตโนมัติ (Auto Content Posting)
- **AI Content Generation**: ให้ AI สร้างคอนเทนต์อัตโนมัติ
- **Multi-platform Posting**: โพสต์ไปหลายแพลตฟอร์มพร้อมกัน
- **Scheduling**: ตั้งเวลาโพสต์ล่วงหน้า (ครั้งเดียว, รายวัน, รายสัปดาห์, รายเดือน)
- **Content Variations**: สร้างคอนเทนต์หลายรูปแบบ
- **Hashtag Generation**: สร้าง hashtag อัตโนมัติ

### 4. ระบบ Marketplace
- **ซื้อ-ขาย-เช่าบอท**: ตลาดซื้อขายและให้เช่าบอท
- **แผนการเช่า**:
  - รายเดือน (Monthly)
  - ต่อข้อความ (Per Message)
- **Commission System**: ระบบค่าคอมมิชชั่นสำหรับเจ้าของบอท
- **Rating & Review**: ระบบรีวิวและให้คะแนน

### 5. ระบบ Embed Code
- **Widget**: ฝังบอทแบบ popup widget
- **Inline**: ฝังบอทในหน้าเว็บ
- **Customizable**: ปรับแต่งสี, ขนาด, ตำแหน่ง
- **Domain Restriction**: จำกัดโดเมนที่สามารถใช้งานได้

## 📊 โครงสร้างฐานข้อมูล

### Tables

#### 1. `chatbot_keyword_responses`
การตั้งค่าคำตอบแบบ keyword-based
- Keywords (array)
- Match Type (exact, partial, regex)
- Response Text & Media
- Priority & Conditions
- Usage Tracking

#### 2. `chatbot_auto_content_posts`
จัดการการสร้างและโพสต์คอนเทนต์อัตโนมัติ
- Content Prompt & Guidelines
- Generated Content & Hashtags
- Scheduling (frequency, time, days)
- Target Platforms
- Engagement Tracking (views, likes, comments, shares)

#### 3. `chatbot_platform_integrations`
การเชื่อมต่อกับแพลตฟอร์มต่างๆ
- Platform Credentials (LINE, Facebook, Telegram, etc.)
- Features & Settings
- Room/Channel Management
- Connection Status & Monitoring

#### 4. `chatbot_embed_codes`
โค้ดสำหรับฝังบอทในเว็บไซต์
- Embed Type & Configuration
- Appearance Settings
- Domain Restrictions
- Usage Analytics

## 🚀 การใช้งาน

### สร้างบอทใหม่

```bash
POST /api/chatbot/bots
```

```json
{
  "name": "บอทช่วยขาย",
  "description": "บอทสำหรับตอบคำถามลูกค้า",
  "provider_id": 1,
  "model_id": 1,
  "system_prompt": "คุณเป็นผู้ช่วยขายที่เป็นมิตร",
  "temperature": 0.7,
  "is_rentable": true,
  "rental_price_per_month": 999
}
```

### เพิ่ม Keyword Response

```bash
POST /api/chatbot/bots/{botId}/keywords
```

```json
{
  "keywords": ["สวัสดี", "หวัดดี", "hello"],
  "match_type": "partial",
  "response_text": "สวัสดีครับ ยินดีให้บริการ",
  "quick_replies": [
    {"text": "ดูสินค้า"},
    {"text": "ติดต่อทีมงาน"}
  ],
  "priority": 10
}
```

### เชื่อมต่อ LINE

```bash
POST /api/chatbot/bots/{botId}/integrations
```

```json
{
  "platform_type": "line",
  "line_channel_id": "xxx",
  "line_channel_secret": "xxx",
  "line_channel_access_token": "xxx",
  "auto_reply_enabled": true,
  "keyword_matching_enabled": true,
  "ai_fallback_enabled": true
}
```

### สร้างคอนเทนต์อัตโนมัติ

```bash
POST /api/chatbot/bots/{botId}/auto-content
```

```json
{
  "topic": "โปรโมชั่นสินค้าใหม่",
  "content_prompt": "สร้างโพสต์โปรโมชั่นสินค้าใหม่ที่น่าสนใจ",
  "content_guidelines": {
    "tone": "friendly",
    "style": "casual",
    "language": "Thai",
    "include_emoji": true,
    "include_hashtags": true
  },
  "target_platforms": ["line", "facebook", "instagram"],
  "frequency": "daily",
  "post_time": "09:00",
  "auto_regenerate": true
}
```

### ทดสอบบอท

```bash
POST /api/chatbot/bots/{botId}/test
```

```json
{
  "message": "สวัสดีครับ"
}
```

**Response:**
```json
{
  "success": true,
  "response": "สวัสดีครับ ยินดีให้บริการ",
  "type": "keyword",
  "processing_time_ms": 2.5,
  "tokens_used": 0,
  "cost": 0
}
```

## 🏪 Marketplace API

### ดูบอทในตลาด

```bash
GET /api/chatbot/marketplace?search=ขาย&sort_by=popular
```

### เช่าบอท

```bash
POST /api/chatbot/marketplace/{botId}/rent
```

```json
{
  "rental_plan": "monthly",
  "months": 3
}
```

### ดูรายได้

```bash
GET /api/chatbot/marketplace/my-earnings
```

## 🔧 Service Classes

### HybridChatbotEngine
Core engine สำหรับประมวลผลข้อความ
- `processMessage()` - ประมวลผลข้อความ (Keyword → AI Fallback)
- `validateBotConfiguration()` - ตรวจสอบการตั้งค่าบอท

### AutoContentService
สร้างและโพสต์คอนเทนต์อัตโนมัติ
- `generateContent()` - สร้างคอนเทนต์ด้วย AI
- `postToPlatforms()` - โพสต์ไปยังแพลตฟอร์มต่างๆ
- `processScheduledPosts()` - ประมวลผลโพสต์ที่ตั้งเวลาไว้ (Cron Job)

### AI Services
- `OpenAiService` - ChatGPT
- `AnthropicService` - Claude
- `GoogleGeminiService` - Gemini
- `DeepSeekService` - DeepSeek

## 📱 ฟีเจอร์พิเศษ

### 1. ระบบ Priority Matching
- Keyword responses มี priority
- เช็ค keyword ตาม priority สูงสุดก่อน
- ลด cost จากการใช้ AI

### 2. Conditional Responses
ตั้งเงื่อนไขการตอบ:
- เวลา (09:00-17:00)
- วัน (จันทร์-ศุกร์)
- User Type
- Custom Conditions

### 3. Response Variations
- ตั้งคำตอบได้หลายแบบ
- สุ่มเลือกคำตอบ (ดูเป็นธรรมชาติ)

### 4. Usage Analytics
- จำนวนข้อความ
- Token ที่ใช้
- Cost
- Engagement Rate
- Platform Performance

## 🎨 Frontend (Dashboard)

Dashboard จะประกอบด้วย:

### Bot Management
- สร้าง/แก้ไข/ลบบอท
- ตั้งค่า AI Provider & Model
- ดู Statistics

### Keyword Management
- เพิ่ม/แก้ไข/ลบ Keywords
- Drag & Drop เรียงลำดับ
- ทดสอบ Matching

### Platform Integration
- เชื่อมต่อแพลตฟอร์ม
- ตรวจสอบสถานะ
- จัดการห้องแชท

### Auto Content
- สร้างคอนเทนต์
- ตั้งเวลาโพสต์
- ดูผลลัพธ์และ Engagement

### Marketplace
- เรียกดูบอทที่ให้เช่า
- เช่า/ซื้อบอท
- จัดการรายได้

### Analytics Dashboard
- Overview Statistics
- Usage Trends
- Revenue Reports
- Platform Performance

## 🔐 Security

- API Authentication (Sanctum)
- Domain Restrictions (Embed Code)
- Rate Limiting
- IP Blocking
- Webhook Verification

## 💰 Pricing & Commission

### แผนการเช่า
- **Monthly**: ค่าเช่ารายเดือน
- **Per Message**: ค่าบริการต่อข้อความ

### Commission
- Platform Commission: 20% (default)
- Owner Earning: 80%
- Customizable per bot

## 📈 Roadmap

- [ ] Dashboard UI (React/Vue)
- [ ] Real-time Analytics
- [ ] A/B Testing for Responses
- [ ] Voice Input Support
- [ ] Image Generation
- [ ] Advanced NLP Features
- [ ] Bot Templates
- [ ] Multi-language Support
- [ ] Mobile App

## 🤝 การใช้งาน Cron Jobs

เพิ่มใน `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Process scheduled content posts
    $schedule->call(function () {
        app(AutoContentService::class)->processScheduledPosts();
    })->everyMinute();
}
```

## 📝 License

MIT License

---

**สร้างโดย**: Thai Prompt Affiliate Platform
**เวอร์ชัน**: 1.0.0
**อัปเดตล่าสุด**: 2025-11-12
