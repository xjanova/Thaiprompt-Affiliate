# Persona Rambler Detection + Smooth Withdraw

> **Status**: 📝 Planned, NOT yet implemented
> **Owner**: แม่หมอจันทรา (Fortune bot — FB Messenger + LINE OA)
> **Created**: 2026-05-18
> **Source session**: User requested "ปิดการขายไม่ลง 3 ครั้ง → ปลีกตัว 10 ชม + persona ต้องรู้ว่าคนนี้คุยไร้สาระ"

---

## 🎯 เป้าหมาย (Why)

ลูกค้าบางคนคุยฟุ้งซ่านไปเรื่อย ไม่ปิดการขาย — เปลือง AI token + แม่หมอเหนื่อย
ระบบต้อง: detect → ตัดจบเนียน → silence ชั่วคราว + RPG persona จำว่าเป็น "คนเวิ่น"

## ✅ สเปค Final (user ยืนยันแล้ว)

| ด้าน | ค่า |
|------|------|
| Threshold | 3 pitches ใน rolling 30 นาที + chitchat reply |
| Silence duration | 10 ชม. |
| Silence behavior | เงียบสนิท ยกเว้น keyword `ดูดวง / จ่าย / โอน / qr / พร้อม / 39 / 99` |
| Resume (หลัง 10 ชม.) | failed_count=0, score -=10, chat_silenced_until=null |
| Permanent low-engagement | ❌ ไม่มี — cooldown วนได้เรื่อยๆ |
| Platforms | FB Messenger + LINE OA (เพราะ persona เก็บทั้ง 2 platform อยู่แล้ว) |

## 🏗️ Architecture

**Reuse 80%:**
- `FortuneCustomerPersona` model — persistent persona per platform+user
- `CustomerPersonaService` — service layer + cache invalidation
- `FortuneTakeoverService` pattern (DB-as-truth + cache TTL) เป็น reference
- `tryAIChatResponse()` at FortuneConversationService.php:11572 — AI chat gate
- `looksLikeMetaOrChitchat()` — heuristic detector มีอยู่แล้ว

**Δ (สิ่งที่เพิ่ม):**

### 1) Migration: `2026_05_18_add_rambler_cooldown_to_fortune_customer_personas`

```php
$table->unsignedInteger('sales_pitch_count')->default(0);          // lifetime
$table->unsignedInteger('sales_pitch_failed_count')->default(0);   // rolling, reset on resume
$table->unsignedTinyInteger('time_waster_score')->default(0);      // 0-100
$table->timestamp('last_pitch_at')->nullable();
$table->timestamp('chat_silenced_until')->nullable();
$table->text('last_silence_reason')->nullable();                   // debug
$table->index('chat_silenced_until');
```

### 2) Service: `CustomerPersonaService` ขยาย method

```php
recordSalesPitch($platform, $userId): void
recordChitchatAfterPitch($platform, $userId, $messageText): bool  // true = triggered cooldown
isChatSilenced($platform, $userId): bool                          // + lazy resume
maybeResumeFromCooldown($persona): void                           // lazy decay
```

### 3) Hook 3 จุดใน `FortuneConversationService`

| Hook | จุดเรียก | Action |
|------|---------|--------|
| Hook A | หลังเสนอ 39฿/99฿ (`sendDeepFortuneIntro`, `askFortuneConfirmation` pitch, `sendCelticPaymentInfo`) | `recordSalesPitch()` |
| Hook B | ใน `tryAIChatResponse` (line 11576) หลัง `enable_ai_chat` check | `if (isChatSilenced()) return null;` |
| Hook C | ในตัว detector ก่อน `tryAIChatResponse` | ถ้า `last_pitch_at < 30min` + `looksLikeMetaOrChitchat=true` + ไม่มี keyword → `recordChitchatAfterPitch()` |

### 4) Bypass keyword (Hook B passthrough)

```php
const SILENCE_BYPASS_KEYWORDS = ['ดูดวง', 'จ่าย', 'โอน', 'qr', 'พร้อม', '39', '99', 'ไพ่', 'เซลติก'];
```

ถ้า message contains any → skip silence check (ให้ flow ปกติทำงาน — payment/start flow)

### 5) "เนียน goodbye" message (ก่อน silence เริ่ม)

ส่ง 1 ข้อความสุดท้ายผ่าน FortuneChannelManager — persona-driven tone:
- Casual customer: "ตอนนี้แม่ติดดูคนอื่นเยอะ ✨ ถ้าพร้อมดูดวงพิมพ์ 'ดูดวง' ได้เลยนะคะ"
- Formal customer: "ขออภัยค่ะ แม่หมอยุ่งอยู่ พิมพ์ 'ดูดวง' เมื่อพร้อมนะคะ ❤️"

(เลือก template ตาม `communication_style.formality`)

### 6) AI Prompt inject (extend `toAiContextBlock`)

```php
if ($this->time_waster_score >= 40) {
    $lines[] = "⚠️ ลูกค้านี้ score รบกวน {$this->time_waster_score}/100 — ตอบ ≤1 ประโยค ไม่ chitchat ปรับเข้าเรื่องดูดวงทันที";
}
```

## 🚦 Trigger flow

```
[ลูกค้าพิมพ์] → check chat_silenced_until
  ├─ silenced + ไม่มี bypass keyword → SKIP (no reply)
  ├─ silenced + bypass keyword → ดำเนิน flow ปกติ
  └─ ไม่ silenced →
       [บอทตอบ + ถ้าเสนอขาย] → recordSalesPitch()
       [ลูกค้าตอบกลับ within 30min] →
         ├─ มี bypass keyword → ไม่นับ
         └─ looksLikeMetaOrChitchat=true →
              ├─ failed_count++ 
              └─ ถ้า >= 3 → set silenced_until=now+10h
                          score += 20
                          ส่งเนียน goodbye 1 ข้อความ
                          (ครั้งถัดไป Hook B จะ skip ทุกอย่างยกเว้น keyword)
```

## 🧪 Tradeoff หลัก

**Pro**: ประหยัด token, ลดเวลาคุยกับคนเวิ่น, RPG persona เรียนรู้ระยะยาว
**Con**: ลูกค้ากำลังลังเล (จะจ่าย) หน้าตาเหมือนคนเวิ่น — แก้โดยใช้ `looksLikeMetaOrChitchat=true` เท่านั้น + keyword bypass กว้าง (39/99/จ่าย/พร้อม)

## 📋 Implementation Checklist

- [ ] Migration: เพิ่ม 6 columns ใน fortune_customer_personas
- [ ] FortuneCustomerPersona: เพิ่ม method `recordPitch()`, `recordChitchatAfterPitch()`, `isChatSilenced()`, `maybeResume()`
- [ ] CustomerPersonaService: expose 4 methods + invalidate cache
- [ ] FortuneConversationService Hook A: หลัง pitch (3-4 จุด — Deep/Celtic intro)
- [ ] FortuneConversationService Hook B: tryAIChatResponse silence check
- [ ] FortuneConversationService Hook C: detect chitchat after pitch
- [ ] FortuneChannelManager: ส่ง goodbye message persona-driven
- [ ] toAiContextBlock: inject time_waster directive
- [ ] Admin UI (optional): แสดง score + manual unsilence ใน persona detail page
- [ ] Test: simulate 3 pitches + chitchat → verify silence + 10hr resume

## 📂 Files affected

- `database/migrations/2026_05_18_add_rambler_cooldown_to_fortune_customer_personas.php` (new)
- `app/Models/FortuneCustomerPersona.php` (extend)
- `app/Services/Fortune/CustomerPersonaService.php` (extend)
- `app/Services/FortuneConversationService.php` (3 hooks)
- `app/Services/FortuneChannelManager.php` (goodbye message helper)
- `resources/views/admin/fortune/personas/show.blade.php` (optional UI)

## 🎯 Success criteria

- บอทเสนอ 39฿ 3 ครั้งใน 30 นาที + ลูกค้าตอบฟุ้ง 3 ครั้ง → silence 10 ชม.
- ระหว่าง silence: พิมพ์ "หิวข้าว" → ไม่ตอบ / พิมพ์ "ดูดวง" → ทำงานปกติ
- หลัง 10 ชม.: failed_count=0, score -=10, chat_silenced_until=null
- Persona admin page: เห็น `time_waster_score` + `last_silence_reason`

## 🚫 Out of scope (สำหรับรอบนี้)

- Admin override (force unsilence) — phase 2
- Per-user analytics dashboard — มี persona page อยู่แล้ว ใช้ได้
- Reset score command — ไม่ทำ (decay พอ)
