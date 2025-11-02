# คำแนะนำการติดตั้ง LINE Bot AI System

## ⚠️ สิ่งที่ต้องทำเพื่อให้ระบบทำงานได้

### 1. รัน Database Migrations

ต้องรันคำสั่งต่อไปนี้ใน production server:

```bash
# เข้าไปที่ directory โปรเจค
cd /path/to/Thaiprompt-Affiliate

# รัน migrations ทั้งหมด
php artisan migrate

# หรือรันทีละไฟล์ (ถ้าต้องการ)
php artisan migrate --path=database/migrations/2025_11_02_100001_create_line_bot_ai_settings_table.php
php artisan migrate --path=database/migrations/2025_11_02_100002_create_line_bot_knowledge_bases_table.php
php artisan migrate --path=database/migrations/2025_11_02_100003_create_line_bot_conversations_table.php
php artisan migrate --path=database/migrations/2025_11_02_100004_create_line_bot_messages_table.php
php artisan migrate --path=database/migrations/2025_11_02_100005_create_line_flex_message_templates_table.php
php artisan migrate --path=database/migrations/2025_11_02_100006_create_line_rich_menus_table.php
php artisan migrate --path=database/migrations/2025_11_02_100007_create_line_chat_widget_settings_table.php
php artisan migrate --path=database/migrations/2025_11_02_100008_create_line_avatars_table.php
php artisan migrate --path=database/migrations/2025_11_02_100009_create_line_broadcast_messages_table.php
```

### 2. รัน Seeder สำหรับ Flex Message Templates

```bash
php artisan db:seed --class=LineFlexMessageTemplateSeeder
```

คำสั่งนี้จะสร้าง 3 Flex Message Templates สวยๆ ให้:
- Welcome Message
- Flash Sale Promotion
- Product Card

### 3. Clear Cache (ถ้าจำเป็น)

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📊 ตารางที่ถูกสร้าง

หลังจากรัน migrations จะมีตารางใหม่ 9 ตาราง:

1. **line_bot_ai_settings** - การตั้งค่า AI providers
2. **line_bot_knowledge_bases** - แหล่งข้อมูลสำหรับ AI
3. **line_bot_conversations** - บันทึกการสนทนา
4. **line_bot_messages** - ข้อความในแต่ละการสนทนา
5. **line_flex_message_templates** - เทมเพลต Flex Messages
6. **line_rich_menus** - Rich Menu configurations
7. **line_chat_widget_settings** - ตั้งค่า Chat Widget
8. **line_avatars** - Avatars สำหรับ Chat Widget
9. **line_broadcast_messages** - Broadcast campaigns

## 🎯 ขั้นตอนถัดไป

1. ✅ รัน migrations (ตามข้อ 1)
2. ✅ รัน seeder (ตามข้อ 2)
3. ✅ เข้าหน้า Admin Panel → Line & AI
4. ✅ ตั้งค่า AI Settings:
   - เลือก AI Provider (OpenAI, DeepSeek, Anthropic, Gemini)
   - ใส่ API Key
   - ตั้งค่า Temperature, Max Tokens
   - เขียน System Prompt
5. ✅ เพิ่ม Knowledge Base (ถ้าต้องการ)
6. ✅ ทดสอบ AI Connection
7. ✅ เปิดใช้งาน (คลิก Toggle)

## 🚨 หมายเหตุสำคัญ

- ต้อง **รัน migrations** ก่อนเข้าใช้งานหน้า Admin
- ถ้ายังไม่ได้รัน จะเจอ error: `Table 'xxx.line_avatars' doesn't exist`
- Migrations ต้องรันบน production server ที่มี PHP และ Laravel ติดตั้งแล้ว

## 📝 ฟีเจอร์ที่พร้อมใช้งาน

- ✅ AI Chat Bot (รองรับ 5 providers)
- ✅ Knowledge Base Management
- ✅ Flex Message Templates
- ✅ Rich Menu Builder
- ✅ Chat Widget
- ✅ Avatar Management
- ✅ Broadcast System

## 🔗 เมนูใน Admin

ตอนนี้เมนู **"Line & AI"** มีเมนูย่อยทั้งหมด:

**การตั้งค่า:**
- ตั้งค่า LINE OA
- ประวัติการใช้งาน
- ตั้งค่า OTP

**AI Chat Bot:**
- AI Settings (พร้อม Knowledge Base)

**ข้อความ & เมนู:**
- Flex Messages
- Rich Menus
- Broadcast

**Chat Widget:**
- ตั้งค่า Widget
- Avatars

**ทรัพยากร:**
- LINE Developers Console
- Flex Message Docs

---

หากมีปัญหาใดๆ กรุณาตรวจสอบ:
1. ✅ รัน migrations แล้วหรือยัง
2. ✅ Database connection ถูกต้องหรือไม่
3. ✅ มี permission เขียนลง database หรือไม่
