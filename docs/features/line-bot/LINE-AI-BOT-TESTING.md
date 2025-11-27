# คู่มือการทดสอบระบบ AI Bot + LINE OA Integration

## ภาพรวม

เอกสารนี้อธิบายวิธีการทดสอบระบบ AI Chatbot ที่เชื่อมต่อกับ LINE Official Account เพื่อให้มั่นใจว่าระบบทำงานได้ถูกต้องครบทุกส่วน

---

## 📋 สิ่งที่ต้องเตรียม

### 1. LINE Official Account
- สร้าง LINE OA ที่ https://manager.line.biz/
- เปิดใช้งาน Messaging API
- ได้รับข้อมูล:
  - Channel ID
  - Channel Secret
  - Channel Access Token (Long-lived)

### 2. Webhook URL
- URL ที่ LINE จะส่ง event มาหา: `https://your-domain.com/webhook/line`
- ต้องเป็น HTTPS (ใช้ ngrok สำหรับ local testing)
- ตั้งค่าใน LINE Developers Console > Messaging API > Webhook settings

### 3. AI Provider
ต้องมีอย่างน้อย 1 provider ที่พร้อมใช้งาน:
- **OpenAI**: ต้องมี API Key
- **Claude (Anthropic)**: ต้องมี API Key
- **DeepSeek**: ต้องมี API Key
- **Local AI (Ollama)**: ต้องติดตั้งและ start service แล้ว

---

## 🔧 ขั้นตอนการตั้งค่า

### Step 1: ตั้งค่า LINE OA Settings (Global)

1. เข้าสู่ระบบ Admin
2. ไปที่ **Admin > LINE Settings**
3. กรอกข้อมูล LINE OA หลัก:
   ```
   Channel ID: [YOUR_CHANNEL_ID]
   Channel Secret: [YOUR_CHANNEL_SECRET]
   Channel Access Token: [YOUR_ACCESS_TOKEN]
   Welcome Message: สวัสดีครับ! ยินดีต้อนรับสู่ระบบ AI Bot
   ```
4. บันทึกและทดสอบ Connection

### Step 2: สร้าง AI Bot Profile

1. ไปที่ **Admin > AI Bots > Create New Bot**
2. กรอกข้อมูลพื้นฐาน:
   ```
   Name: test-bot
   Display Name: AI Helper Bot
   Description: Bot ทดสอบสำหรับ LINE OA
   ```
3. เลือก Provider และ Model:
   ```
   Provider: OpenAI (หรือ provider ที่คุณมี)
   Model: gpt-4o-mini (หรือ model ที่ต้องการ)
   ```
4. กำหนด System Prompt:
   ```
   คุณคือผู้ช่วย AI ที่เป็นมิตรและชอบช่วยเหลือผู้คน
   ตอบคำถามด้วยภาษาไทยที่เป็นกันเอง และให้ข้อมูลที่ถูกต้อง
   ```
5. ตั้งค่า Parameters:
   ```
   Temperature: 0.7
   Max Tokens: 500
   ```
6. บันทึก Bot

### Step 3: เชื่อมต่อ Bot กับ LINE OA

1. ไปที่ **AI Bots > [เลือก bot ที่สร้าง] > Edit**
2. Scroll ลงมาหาส่วน **"เชื่อมต่อกับ LINE OA"**
3. กรอกข้อมูล LINE OA:
   ```
   LINE Channel ID: [CHANNEL_ID ของคุณ]
   LINE Channel Secret: [CHANNEL_SECRET ของคุณ]
   LINE Channel Access Token: [ACCESS_TOKEN ของคุณ]
   ```

   **หมายเหตุ**: ข้อมูลเหล่านี้อาจจะเหมือนกับ Step 1 (ถ้าใช้ LINE OA เดียวกัน) หรือต่างกันได้ (ถ้าต้องการแยก bot ต่อ LINE OA)

4. บันทึกการแก้ไข

### Step 4: ตั้งค่า Webhook ใน LINE Developers

1. ไปที่ https://developers.line.biz/console/
2. เลือก Provider > Channel ของคุณ
3. ไปที่แท็บ **Messaging API**
4. ตั้งค่า Webhook:
   ```
   Webhook URL: https://your-domain.com/webhook/line
   Use webhook: เปิดใช้งาน (ON)
   ```
5. กด **Verify** เพื่อทดสอบ webhook
   - ถ้าสำเร็จจะขึ้น "Success"
   - ถ้าล้มเหลว ให้ตรวจสอบ URL และ channel_secret

6. ตั้งค่าเพิ่มเติม:
   ```
   Auto-reply messages: ปิด (OFF)
   Greeting messages: เปิด (ON) - ใช้ welcome_message จาก settings
   ```

---

## ✅ การทดสอบ

### Test 1: ทดสอบ Bot ผ่าน Admin UI

1. ไปที่ **AI Bots > [เลือก bot] > View**
2. Scroll ลงมาหาส่วน **"ทดสอบ Bot"**
3. พิมพ์ข้อความทดสอบ:
   ```
   สวัสดีครับ
   ```
4. กด **ส่ง**
5. ตรวจสอบ:
   - ✅ ได้รับ response จาก AI
   - ✅ Response ตรงตาม system prompt
   - ✅ แสดง tokens_used
   - ✅ แสดง response_time

**ผลลัพธ์ที่คาดหวัง**:
```
Response: สวัสดีครับ! ยินดีให้บริการนะครับ มีอะไรให้ช่วยไหมครับ?
Tokens: 45
Response time: 850ms
```

### Test 2: ทดสอบผ่าน LINE OA (User Perspective)

1. เปิดแอป LINE บนมือถือ
2. เพิ่ม Official Account ของคุณเป็นเพื่อน (ใช้ QR Code หรือ LINE ID)
3. คาดหวัง:
   - ✅ ได้รับ Welcome Message ทันที

4. ส่งข้อความทดสอบ:
   ```
   สวัสดี
   ```
5. คาดหวัง:
   - ✅ Bot ตอบกลับภายใน 2-5 วินาที
   - ✅ คำตอบเป็นภาษาไทย
   - ✅ คำตอบสอดคล้องกับ system prompt

6. ทดสอบ Conversation Context:
   ```
   User: ชื่อฉันคือ สมชาย
   Bot: สวัสดีครับคุณสมชาย...

   User: ชื่อฉันคืออะไร
   Bot: ชื่อของคุณคือสมชายครับ
   ```
   - ✅ Bot จำบริบทการสนทนาได้

### Test 3: ทดสอบ Special Commands

1. พิมพ์ `info` หรือ `ข้อมูล`
   - ถ้ามี user account เชื่อมกับ LINE: จะแสดงข้อมูล user
   - ถ้าไม่มี: จะแจ้งให้ลงทะเบียน

### Test 4: ตรวจสอบ Logs และ Database

#### 4.1 ตรวจสอบ Conversation Records

```sql
SELECT * FROM ai_conversations
WHERE line_user_id = 'U1234567890abcdef'
ORDER BY created_at DESC
LIMIT 1;
```

คาดหวัง:
- มี record ใหม่สำหรับ conversation
- `bot_profile_id` ตรงกับ bot ที่ตั้งค่าไว้
- `status` = 'active'

#### 4.2 ตรวจสอบ Messages

```sql
SELECT role, content, tokens_used, created_at
FROM ai_messages
WHERE conversation_id = [conversation_id]
ORDER BY created_at ASC;
```

คาดหวัง:
- มีทั้ง role='user' และ role='assistant'
- content ถูกต้องตรงกับที่ส่ง/รับ
- tokens_used มีค่า > 0 สำหรับ assistant messages

#### 4.3 ตรวจสอบ Usage Logs

```sql
SELECT * FROM ai_usage_logs
WHERE conversation_id = [conversation_id]
ORDER BY created_at DESC;
```

คาดหวัง:
- บันทึก prompt_tokens, completion_tokens, total_tokens
- cost คำนวณถูกต้อง (0 สำหรับ local AI)
- status = 'success'
- response_time_ms มีค่า

### Test 5: ตรวจสอบ Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

เมื่อส่งข้อความผ่าน LINE ควรเห็น:

```
[2025-11-03 12:34:56] local.INFO: LINE message received {"line_user_id":"U1234...","message":"สวัสดี"}
[2025-11-03 12:34:58] local.INFO: AI response sent to LINE {"line_user_id":"U1234...","bot_id":1,"tokens_used":45}
```

ถ้ามี error:
```
[2025-11-03 12:34:58] local.ERROR: AI Bot chat error {"error":"...","line_user_id":"U1234..."}
```

---

## 🐛 การแก้ปัญหา (Troubleshooting)

### ปัญหา 1: ไม่ได้รับข้อความจาก LINE

**สาเหตุที่เป็นไปได้**:
- Webhook URL ไม่ถูกต้องหรือไม่สามารถเข้าถึงได้
- Channel Secret ผิด (signature verification failed)
- Auto-reply ยังเปิดอยู่ใน LINE OA Settings

**วิธีแก้**:
1. ตรวจสอบ webhook logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "LINE webhook"
   ```
2. ถ้าเห็น "Invalid signature" = channel_secret ผิด
3. ถ้าไม่มี log เลย = webhook ไม่ถูกเรียก (ตรวจสอบ URL)

### ปัญหา 2: Bot ไม่ตอบกลับ

**สาเหตุที่เป็นไปได้**:
- ไม่มี Bot ที่ match กับ line_oa_channel_id
- Bot is_active = false
- AI Provider ไม่สามารถเชื่อมต่อได้

**วิธีแก้**:
1. ตรวจสอบ Bot configuration:
   ```sql
   SELECT id, name, line_oa_channel_id, is_active
   FROM ai_bot_profiles
   WHERE line_oa_channel_id = '[YOUR_CHANNEL_ID]';
   ```
2. ถ้าไม่มี = ยังไม่ได้เชื่อม Bot กับ LINE OA
3. ถ้า is_active = 0 = เปิด Bot ใน Admin UI

### ปัญหา 3: AI Error / Timeout

**สาเหตุที่เป็นไปได้**:
- API Key หมดอายุหรือผิด
- ถึง rate limit ของ provider
- Local AI ไม่ running

**วิธีแก้**:
1. ทดสอบ connection ใน Admin > AI Providers > Test Connection
2. ตรวจสอบ API usage ใน provider dashboard
3. สำหรับ Local AI:
   ```bash
   sudo systemctl status ollama
   curl http://localhost:11434/api/tags
   ```

### ปัญหา 4: Bot จำบริบทไม่ได้

**สาเหตุที่เป็นไปได้**:
- สร้าง conversation ใหม่ทุกครั้ง (ควรใช้ findOrCreateConversation)
- Messages ไม่ถูกบันทึกลง database

**วิธีแก้**:
1. ตรวจสอบ ConversationManager logic
2. ตรวจสอบ conversation_id consistency

### ปัญหา 5: Cost / Tokens ไม่ถูกบันทึก

**วิธีแก้**:
1. ตรวจสอบว่า BaseAiService::logUsage() ถูกเรียก
2. ตรวจสอบ pricing config ใน ai_models table
3. Debug usage logs table

---

## 📊 ตัวชี้วัดความสำเร็จ

### Functional Requirements
- ✅ User สามารถส่งข้อความผ่าน LINE และได้รับ response จาก AI
- ✅ Bot จำบริบทการสนทนาได้อย่างน้อย 10 ข้อความ
- ✅ รองรับหลาย user พร้อมกัน (แต่ละ user มี conversation แยก)
- ✅ รองรับหลาย Bot สำหรับหลาย LINE OA
- ✅ บันทึก usage logs สำหรับ billing

### Performance Requirements
- ✅ Response time < 5 วินาที (cloud API)
- ✅ Response time < 3 วินาที (local AI)
- ✅ รองรับ concurrent users อย่างน้อย 10 คน

### Data Integrity
- ✅ ทุก message ถูกบันทึกใน database
- ✅ Token count และ cost ถูกต้อง
- ✅ ไม่มี message สูญหาย

---

## 🚀 ขั้นตอนถัดไป

หลังจาก LINE + AI Integration ทำงานได้แล้ว ขั้นตอนต่อไปคือ:

### Phase 7: RAG (Knowledge Base)
- อัพโหลดเอกสารเป็น knowledge base
- Text chunking และ embedding
- Vector search เพื่อดึง context ที่เกี่ยวข้อง
- ใช้ RAG context ใน ConversationManager

### Phase 8: Bot Marketplace
- UI สำหรับ browse และ rent bots
- ระบบ subscription (monthly/per-message)
- Commission calculation
- Revenue reports

---

## 📝 Checklist สำหรับ Production

ก่อนนำระบบขึ้น Production ควรตรวจสอบ:

### Security
- [ ] ใช้ HTTPS สำหรับ webhook
- [ ] Validate LINE signature ทุก request
- [ ] ซ่อน API keys ใน .env (ไม่ commit)
- [ ] ตั้งค่า CORS และ CSRF protection
- [ ] Rate limiting สำหรับ webhook endpoint

### Scalability
- [ ] ใช้ Queue สำหรับ AI requests (ไม่ block webhook)
- [ ] Cache system metrics
- [ ] Database indexing สำหรับ line_user_id, bot_profile_id
- [ ] Monitor memory usage

### Monitoring
- [ ] ตั้งค่า error alerting (email/Slack)
- [ ] Dashboard สำหรับ real-time metrics
- [ ] Log retention policy
- [ ] Backup database daily

### Cost Management
- [ ] ตั้ง budget limit สำหรับ cloud AI providers
- [ ] Alert เมื่อ usage ใกล้ limit
- [ ] พิจารณาใช้ local AI สำหรับ queries ง่ายๆ

---

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- ตรวจสอบ Laravel logs: `storage/logs/laravel.log`
- ตรวจสอบ database records
- Review code ใน `app/Http/Controllers/LineWebhookController.php`

---

**Last Updated**: 2025-11-03
**Version**: 1.0
**Status**: Phase 6 Complete ✅
