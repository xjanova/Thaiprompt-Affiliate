# LINE Bot AI Integration - Implementation Guide

## ภาพรวมระบบ

ระบบ LINE Bot AI Chat ที่พัฒนาขึ้นมีความสามารถดังนี้:

### 1. AI Chat Bot System
- รองรับ AI Providers หลากหลาย: OpenAI (ChatGPT), DeepSeek, Anthropic (Claude), Google Gemini, และ Custom API
- ระบบจัดการ Knowledge Base (แหล่งความรู้):
  - จาก URL ที่กำหนด
  - จากข้อมูลภายในเว็บ
  - จากไฟล์ที่อัปโหลด (PDF, TXT, DOCX, CSV)
  - จากข้อความที่พิมพ์สอนเอง
- บันทึกประวัติการสนทนา (Conversation History)
- ระบบ Fallback เมื่อ AI ไม่สามารถตอบได้
- ควบคุมความยาวของ Memory และพารามิเตอร์ AI (temperature, max_tokens)

### 2. Flex Message Builder
- สร้าง Flex Messages แบบลากวาง (Drag & Drop)
- เทมเพลตสำเร็จรูปหลากหลาย (Seed Templates)
- จัดหมวดหมู่: โปรโมชั่น, สินค้า, แจ้งเตือน, กิจกรรม, ทักทาย, ข้อมูล
- ส่งแบบ Broadcast หรือส่วนตัว
- ระบบติดตามการใช้งานเทมเพลต

### 3. Rich Menu Builder
- สร้าง Rich Menu สำหรับ LINE OA
- รองรับ 2 ขนาด: Full และ Half
- กำหนด Areas และ Actions
- อัปโหลดรูปภาพ Rich Menu
- ตั้งเป็น Default Menu ได้

### 4. Broadcast System
- ส่งข้อความแบบ Broadcast
- เลือกกลุ่มเป้าหมาย: ทั้งหมด, ผู้ใช้, ผู้ขาย, หรือกำหนดเอง
- กำหนดเวลาส่ง (Schedule)
- ติดตามสถานะการส่ง (ส่งสำเร็จ/ล้มเหลว)
- รองรับหลายรูปแบบ: Text, Flex, Image, Video

### 5. Chat Widget (ตัวบอทลอย)
- แสดงบนหน้าเว็บ
- เลือกตำแหน่งได้ 4 มุม
- กำหนดสี, Avatar, ข้อความต้อนรับ
- เปิด/ปิดการใช้งานตามหน้า
- Auto-open ได้
- รองรับเสียงและการแจ้งเตือน
- สื่อสารกับ 3 กลุ่ม: ผู้ใช้, ผู้ขาย, AI Bot

### 6. Avatar Management
- อัปโหลดอวาตาร์ได้หลายรูปแบบ: รูปภาพ, GIF, Lottie Animation, Video
- ดึงจาก URL หรืออัปโหลดเอง
- ตั้งเป็น Default ได้
- แสดงขนาดไฟล์

## Database Structure

### Tables Created:
1. `line_bot_ai_settings` - การตั้งค่า AI Provider
2. `line_bot_knowledge_bases` - แหล่งข้อมูลสำหรับ AI
3. `line_bot_conversations` - บันทึกการสนทนา
4. `line_bot_messages` - ข้อความในการสนทนา
5. `line_flex_message_templates` - เทมเพลต Flex Messages
6. `line_rich_menus` - Rich Menus
7. `line_chat_widget_settings` - การตั้งค่า Chat Widget
8. `line_avatars` - Avatar และอนิเมชั่น
9. `line_broadcast_messages` - ข้อความ Broadcast

## Models Created:
- LineBotAiSetting
- LineBotKnowledgeBase
- LineBotConversation
- LineBotMessage
- LineFlexMessageTemplate
- LineRichMenu
- LineChatWidgetSetting
- LineAvatar
- LineBroadcastMessage

## Services:
- `LineBotAiService` - หลักในการเชื่อมต่อกับ AI Providers

## Controllers (To be created):
- LineBotAiController
- LineFlexMessageController
- LineRichMenuController
- LineChatWidgetController
- LineAvatarController
- LineBroadcastController

## Routes (To be added):
```php
// Admin routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // AI Bot Management
    Route::get('/line-bot/ai', [LineBotAiController::class, 'index'])->name('admin.line-bot.ai.index');
    Route::post('/line-bot/ai', [LineBotAiController::class, 'store'])->name('admin.line-bot.ai.store');
    Route::put('/line-bot/ai/{id}', [LineBotAiController::class, 'update'])->name('admin.line-bot.ai.update');

    // Knowledge Base
    Route::resource('line-bot/knowledge', LineBotKnowledgeBaseController::class);

    // Flex Messages
    Route::resource('line-bot/flex-messages', LineFlexMessageController::class);

    // Rich Menus
    Route::resource('line-bot/rich-menus', LineRichMenuController::class);

    // Chat Widget
    Route::get('/line-bot/chat-widget', [LineChatWidgetController::class, 'index']);
    Route::put('/line-bot/chat-widget', [LineChatWidgetController::class, 'update']);

    // Avatars
    Route::resource('line-bot/avatars', LineAvatarController::class);

    // Broadcast
    Route::resource('line-bot/broadcast', LineBroadcastController::class);
});
```

## Next Steps:

1. สร้าง Controllers ทั้งหมด ✅ (Partially - need to create actual files)
2. สร้าง Views สำหรับ Admin UI
3. สร้าง Flex Message Builder UI (drag & drop interface)
4. สร้าง Rich Menu Builder UI
5. สร้าง Chat Widget Frontend Component
6. สร้าง Seeders สำหรับ Flex Message Templates ตัวอย่าง
7. อัปเดต Routes
8. อัปเดต Navigation Menu
9. Run Migrations
10. Test ระบบ

## Features Implemented:

### AI Integration ✅
- Multiple AI Providers (OpenAI, DeepSeek, Anthropic, Gemini, Custom)
- Knowledge Base Management
- Conversation History
- Fallback System

### Webhook Integration ✅
- Updated LineWebhookController to support AI chat
- Automatic conversation creation
- Message logging with tokens and response time

### Database & Models ✅
- All migrations created
- All models with relationships
- Caching for performance
- Soft deletes where appropriate

## การใช้งาน:

### 1. ตั้งค่า AI Provider
```php
$setting = LineBotAiSetting::create([
    'name' => 'ChatGPT Bot',
    'provider' => 'openai',
    'api_key' => 'sk-...',
    'model' => 'gpt-3.5-turbo',
    'temperature' => 0.7,
    'max_tokens' => 1000,
    'system_prompt' => 'You are a helpful Thai customer support assistant.',
    'is_active' => true,
]);
```

### 2. เพิ่ม Knowledge Base
```php
$setting->knowledgeBases()->create([
    'name' => 'Product FAQ',
    'type' => 'text',
    'content' => 'Q: ราคาสินค้า? A: 990 บาท...',
    'priority' => 100,
]);
```

### 3. ทดสอบ AI
```php
$aiService = new LineBotAiService();
$response = $aiService->generateResponse('สวัสดี');
```

## UI Components Needed:

1. **AI Settings Page** - ฟอร์มตั้งค่า AI Provider และ Knowledge Base
2. **Flex Message Builder** - Drag & drop interface สำหรับสร้าง Flex Messages
3. **Rich Menu Builder** - Visual builder สำหรับสร้าง Rich Menu
4. **Chat Widget Settings** - ฟอร์มตั้งค่า Chat Widget
5. **Avatar Manager** - Upload และจัดการ Avatars
6. **Broadcast Manager** - สร้างและส่ง Broadcast Messages

## สิ่งที่ต้องทำต่อ:

เนื่องจากระบบมีขนาดใหญ่มาก ไฟล์ที่ยังต้องสร้าง:
- [ ] Controllers (6 files)
- [ ] Views (15-20 files)
- [ ] JavaScript สำหรับ Flex Message Builder
- [ ] JavaScript สำหรับ Rich Menu Builder
- [ ] Chat Widget Frontend Component
- [ ] Seeders สำหรับ Templates
- [ ] Routes
- [ ] Navigation updates
- [ ] Run migrations

ระบบพื้นฐานทั้งหมดพร้อมแล้ว (Database, Models, Services)
ขั้นตอนถัดไปคือการสร้าง UI และ Controllers เพื่อให้ผู้ใช้สามารถจัดการผ่านหน้า Admin ได้
