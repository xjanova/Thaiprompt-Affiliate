# แผนแก้ไข: ระบบแจ้งเตือนคำทำนาย + เปิดไพ่ยิปซีประกอบคำทำนาย

## สรุปงาน 3 ส่วน

### ส่วนที่ 1: ปรับปรุงการแจ้งเตือนเมื่อคำทำนายพร้อม
**ปัญหาปัจจุบัน**: ระบบ 2-step ซับซ้อนเกินไป — Step 1 push "พร้อมแล้ว" → Step 2 user ตอบ → ส่งคำทำนาย
**แก้ไข**:
- **มี push quota**: Push แจ้ง "คำทำนายพร้อมแล้ว จะอ่านเลยไหมคะ?" (เหมือนเดิม ทำงานอยู่แล้ว)
- **ไม่มี push quota**: ไม่ push — รอ user พิมพ์อะไรก็ได้ แล้วตอบว่า "คำทำนายพร้อมแล้ว จะอ่านเลยไหมคะ?"
- **เมื่อ user พิมพ์อะไรก็ตาม** (บอทเงียบ): ถ้ามี reading ที่ยังไม่ได้อ่าน → ตอบทันทีว่าคำทำนายพร้อมแล้ว
- ไม่เปลี่ยน flow หลัก แค่ทำให้ Step 2 (ส่งคำทำนาย) ทำงานเมื่อ user ตอบ ไม่ว่า notification_sent จะเป็น true หรือ false

**ไฟล์ที่แก้**:
- `app/Services/FortuneConversationService.php` — lines 642-709: แก้ condition ให้ไม่ต้องเช็ค `reading_notification_sent` ก่อนส่งคำทำนาย

### ส่วนที่ 2: เพิ่มการเปิดไพ่ยิปซีประกอบคำทำนาย (เฉพาะแบบเสียเงิน)
**ฟีเจอร์ใหม่**: หลังถามคำถามแต่ละข้อ → ให้ user กดสุ่มไพ่ → บอกชื่อไพ่ที่ได้ → เก็บไว้ใน conversation_state
**Flow ใหม่**:
```
คำถามที่ 1 → user ถาม → "กดสุ่มไพ่ยิปซีประกอบคำทำนายค่ะ" → user กด → "ได้ไพ่ XX ค่ะ ✨"
                          → เก็บไพ่ไว้ใน conversation_state
คำถามที่ 2 → user ถาม → "กดสุ่มไพ่ยิปซีประกอบคำทำนายค่ะ" → user กด → "ได้ไพ่ XX ค่ะ ✨"
                          → เก็บไพ่ไว้ใน conversation_state
→ สร้างบิลชำระเงิน
```

**State ใหม่**: `STATUS_COLLECTING_TAROT` — หลังรับคำถามแต่ละข้อ รอ user กดสุ่มไพ่
**ข้อมูลที่เก็บ**: `collected_tarot_cards` array ใน conversation_state เช่น:
```json
[
  {"question_index": 0, "card_id": 5, "card_name_th": "The Hierophant - พระสันตะปาปา", "is_reversed": false},
  {"question_index": 1, "card_id": 13, "card_name_th": "Death - ความตาย", "is_reversed": true}
]
```

**ไฟล์ที่แก้**:
- `app/Services/FortuneConversationService.php`:
  - `handleQuestionInput()` — หลังรับคำถาม ไม่ถาม question ต่อทันที แต่ให้สุ่มไพ่ก่อน
  - เพิ่ม `handleTarotCardDraw()` method ใหม่ — สุ่มไพ่จาก TarotCard model, เก็บใน state
  - เพิ่ม state transition: COLLECTING_QUESTIONS → COLLECTING_TAROT → COLLECTING_QUESTIONS (วนจน 2 คำถาม)
- `app/Models/FortuneReading.php`:
  - เพิ่ม `addTarotCard()` method
  - เพิ่ม `getCollectedTarotCards()` method

### ส่วนที่ 3: ใช้ไพ่ยิปซีใน AI prompt
**แก้ไข**: เพิ่มข้อมูลไพ่ที่เปิดได้ลงใน prompt template

**ไฟล์ที่แก้**:
- `app/Services/FortuneAIService.php`:
  - `buildDeepPrompt()` / deep reading prompt — เพิ่ม section ไพ่ยิปซี:
    ```
    🃏 ไพ่ยิปซีที่ผู้ถามเปิดได้: {tarot_card_name}
    - ความหมาย: {tarot_meaning}
    - ตำแหน่ง: {upright/reversed}
    → ใช้ไพ่นี้ประกอบการวิเคราะห์ร่วมกับดวงดาว
    ```

---

## ไฟล์ที่ต้องแก้ทั้งหมด (4 ไฟล์)

1. **`app/Services/FortuneConversationService.php`** — notification flow + tarot card draw step
2. **`app/Models/FortuneReading.php`** — helper methods สำหรับ tarot cards
3. **`app/Services/FortuneAIService.php`** — เพิ่มไพ่ใน AI prompt
4. **`app/Services/FortuneChannelManager.php`** — เพิ่ม action handler สำหรับ tarot card draw (quick reply "สุ่มไพ่")

## ไม่ต้องสร้างไฟล์ใหม่ / migration ใหม่
- ใช้ TarotCard model ที่มีอยู่แล้ว (78 ใบ seeded แล้ว)
- เก็บข้อมูลไพ่ใน `conversation_state` JSON column ที่มีอยู่แล้ว
- ไม่ต้อง migration เพิ่ม
