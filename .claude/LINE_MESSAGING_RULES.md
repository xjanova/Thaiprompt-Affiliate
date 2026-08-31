# 🟢 LINE MESSAGING RULES — อ่านก่อนแตะโค้ดที่ส่งข้อความหา LINE

> **บังคับอ่านทุกครั้งที่จะเพิ่ม/แก้จุดที่ส่งข้อความไปหาลูกค้าฝั่ง LINE**
>
> เจ้าของ (2026-08-31): *"ไลน์มันเรื่องมากตรงเรื่องพวกนี้ ต้องเขียนไว้ เวลาทำโค๊ดต้องคำนึงถึงเสมอ"*
>
> ทุกข้อในเอกสารนี้มาจาก **เหตุการณ์จริงที่ลูกค้าเสียหายไปแล้ว** ไม่ใช่ทฤษฎี

---

## 🔴 กฎข้อที่ 1 — push คือเงินสำรองฉุกเฉิน ไม่ใช่ช่องทางส่งปกติ

| ช่องทาง | ราคา | เงื่อนไข |
|---|---|---|
| **reply** (replyToken) | **ฟรี ไม่จำกัด** | ต้องมี token สดจาก webhook · อายุ 60 วิ · ใช้ได้ครั้งเดียว |
| **showLoadingAnimation** | **ฟรี ไม่นับโควต้า** | **ไม่กิน replyToken ด้วย** (คนละ endpoint) · โชว์ 5-60 วิ |
| **push** | **300 ครั้ง/เดือน** | หมดแล้วหมดเลยจนขึ้นเดือนใหม่ (รีเซ็ตตาม JST) |

### บันไดการส่ง — ห้ามข้ามขั้น

```
1. showLoadingAnimation()        ← ฟรี ยิงทันทีที่รับข้อความ ลูกค้าไม่เจอความเงียบ
2. reply ด้วย token ของเทิร์นนั้น ← ฟรี (ฝากข้ามไป job ผ่าน ReplyTokenVault)
3. retry reply 2-3 ครั้ง          ← เฉพาะ timeout/5xx · 400 = token ตาย ห้าม retry
4. park ลง DB รอส่งคืนรอบหน้า     ← ฟรี ส่งตอนลูกค้าทักมา
5. push                          ← ท้ายสุด + เฉพาะ "ของสำคัญ" เท่านั้น
```

### อะไรคือ "ของสำคัญ" (push ได้)
✅ บทสรุป Grand Finale 99฿ · คำทำนายที่จ่ายเงินแล้ว (Celtic/Deep) · คำตอบ Q&A ที่จ่ายแล้ว · ยืนยันการชำระเงิน

### อะไรที่ **ห้าม push เด็ดขาด**
🚫 กล่อง "กำลังคิด"/ping ทุกตัว (ใช้ `showLoadingAnimation()` แทน) · upsell/คุณไสย · pricing follow-up · greeting banner · nudge/ทวงบิล · ban/flood warning

**ก่อนเพิ่มจุด push ใหม่ ให้ถามตัวเองว่า "ถ้าส่งไม่ออก ลูกค้าเสียอะไร"** — ตอบไม่ได้ = ห้าม push

> ⚠️ `LineFortuneService::sendMessage()` วิ่งเข้า `pushMessage()` **ตรงๆ ไม่ลอง reply เลย**
> มี **53 call site** ฝั่ง LINE ที่เข้าประตูนี้ — ทุกจุดคือ push เงียบๆ ระวังตอนก็อปโค้ดเก่า

### 🚫 ห้ามเสนออัปแพลน LINE OA
ตัดสินใจแล้ว 2026-08-26: **ไม่อัป** (1,200฿/15,000 ข้อความ ทั้งที่ใช้ไม่ถึง 2,000/เดือน)
โจทย์คือ **"อยู่ให้ได้ใน 300 push"** — พิสูจน์แล้วว่าทำได้ (2026-08-26: โควต้า 300/300 ตาย แต่บอทตอบครบ `has_reply_token:false` = 0)

---

## 🔴 กฎข้อที่ 2 — LINE คิดเงินต่อ "call" ไม่ใช่ต่อ "กล่อง"

**1 call (reply หรือ push) ใส่ได้สูงสุด 5 message objects**

⇒ รวมรูป+ข้อความ+กล่องแนะนำไว้ใน call เดียว = ราคาเท่ากับส่งกล่องเดียว
⇒ ยิงทีละกล่อง 3 ครั้ง = **จ่าย 3 เท่าโดยไม่ได้อะไรเพิ่ม**

```php
// ✅ ถูก — 1 call
$lineService->sendMessagesWithReplyFallback($userId, [$img, $text, $suggestion], $replyToken);

// ❌ ผิด — 3 call = 3 โควต้า
$lineService->sendImage($userId, $img);
$lineService->sendMessage($userId, $text);
$lineService->sendMessage($userId, $suggestion);
```

⚠️ เกิน 5 objects ต้อง `array_chunk($msgs, 5)` — **chunk แรกใช้ replyToken (ฟรี) ที่เหลือถึงจะ push**

---

## 🔴 กฎข้อที่ 3 — replyToken: ใช้ครั้งเดียว อายุ 60 วินาที

- 1 webhook event = 1 token · ใช้แล้วใช้ซ้ำไม่ได้ (ครั้งที่ 2 ได้ 400)
- `ReplyTokenVault::take()` เป็น **pull** (อ่านแล้วลบ) — ห้ามใช้ `get`
- ฝากอายุไม่เกิน **50 วิ** (`MAX_AGE_SECONDS`) กันชนไว้ 10 วิ

### ⏱️ ตัวเลขที่ต้องจำ (วัดจริง 1,958 คำถาม / 60 วัน)
| | เวลา |
|---|---|
| AI p50 | 8.9s |
| AI p95 | 22.2s |
| Celtic Q&A ทั้งกระบวน | 20-40s |
| **เสร็จภายใน 50s** | **99.8%** |

⇒ **งานเกือบทั้งหมดตอบทัน reply window** ไม่มีเหตุผลต้อง push

### 🪤 กับดัก: debounce = อายุ token
`message_debounce_seconds` ต้อง **ไม่เกิน ~45 วิ** — ถ้าตั้ง 60 job จะตื่นมาตอน token ตายพอดีเสมอ
(เคยตั้ง 60 → ยืม token ไม่ได้เลยสักครั้ง → ลดเหลือ 30 บน prod)

### 🪤 กับดัก: job ที่ลูกค้าเป็นคนจุดมี **3 ตัว** ต้องต่อ vault ครบ
`ProcessBufferedCelticMessageJob` · `ProcessBufferedProSessionMessageJob` · `ProcessBufferedChatMessageJob`
กวาดด้วย: `grep -rn "sendResponse(" app/ --include=*.php | grep -v FortuneChannelManager.php`
ที่เหลือ ~40 จุดเป็น cron/admin/SMS — ลูกค้าไม่ได้พิมพ์ ⇒ ไม่มี token โดยธรรมชาติ ⇒ push ถูกแล้ว

---

## 🔴 กฎข้อที่ 4 — ของที่ลูกค้าจ่ายเงินแล้ว ห้ามมีชีวิตอยู่แค่ในหน่วยความจำ

**ส่งไม่ออกครั้งเดียว = หายถาวร** ถ้าไม่ได้เก็บลง DB ก่อนส่ง

เคยเกิดจริง: บทสรุป Grand Finale 6,000-10,000 ตัวอักษร ถูก `return` ให้ ChannelManager ส่งเลย
ไม่เคยเขียนลงที่ไหน → push ไม่ออก → **ลูกค้าจ่าย 99฿ ไม่ได้อะไรเลย regenerate ก็ได้คนละข้อความ**

**กฎ: generate เสร็จ → เก็บลง `conversation_state` ก่อน → ค่อยส่ง**
`celtic_finale_text` (cap 20,000) · `celtic_finale_chart_url` · `celtic_finale_image_url`

เช่นเดียวกับกล่องคำถามแนะนำ — ถ้าไม่มีคอลัมน์เก็บ `suggestion_box`/`quick_replies`
เส้น park จะส่งคืนได้แค่ตัวคำตอบ **ปุ่มหายถาวร**

---

## 🔴 กฎข้อที่ 5 — ธงบอกว่า "ส่งแล้ว" ต้องตั้งจาก **ผลส่งจริง** เท่านั้น

🪤 เคสจริง reading 11594: push ได้ 429 แต่ safety net ยัง `markDelivered()` คำถาม 6 ข้อพร้อมกัน
⇒ ของที่ไม่เคยถึงลูกค้า กลายเป็น "ส่งแล้ว" ในฐานข้อมูล = **หายแบบไร้ร่องรอย ตามเก็บไม่ได้**

- mark delivered **หลังส่งสำเร็จจริง** ห้าม mark ล่วงหน้า
- เงื่อนไขค้นของค้างต้องเป็น **`=== false`** เท่านั้น ⛔ ห้าม `!== true`
  (reading เก่าที่ไม่มีธง = NULL จะโดนยิงซ้ำทั้งกอง)
- **ห้ามหยิบ "บิลล่าสุด" มาเช็ค** — ลูกค้าซื้อ Deep ต่อหลัง Celtic ที่บทสรุปหาย ⇒ บิลล่าสุดเป็น Deep ⇒ ของเดิมไม่มีวันถูกกู้
  ใช้ JSON path แทน: `where('conversation_state->celtic_summary_delivered', false)`

### 🪤 ตาข่ายกู้ที่วางผิดที่ = ไม่มีตาข่าย
`FortuneCelticRedeliver` เคยวางตัวกู้บทสรุปไว้ **ข้างในลูปคำถามที่ยังไม่ส่ง** แต่ก่อนลูปมี
`if ($candidates->isEmpty()) return 0;` ⇒ พอคำถามส่งครบ ลูปว่าง → return → **ตัวกู้ไม่เคยทำงาน**
ตาข่ายขาดตรงเคสที่ต้องใช้ที่สุด ⇒ แยกเป็น `fortune:celtic-summary-redeliver`

**บทเรียนทั่วไป: ตัวกู้ของ A ห้ามแขวนอยู่กับเงื่อนไขของ B**

---

## 🔴 กฎข้อที่ 6 — แยก 429 "โควต้าหมด" ออกจาก 429 "rate limit"

| | ลายเซ็น |
|---|---|
| โควต้าเดือนหมด | `retry_after: 0` + **`x-ratelimit-*` ว่างเปล่า** |
| rate limit จริง | มี `x-ratelimit-remaining` / `x-ratelimit-reset` ครบ |

🪤 ถ้าแยกไม่ออกแล้วไปตั้ง backoff → `isSystemThrottled()` จะ **ปิดปาก webhook ขาเข้า**
ตอบ "⏳ มีผู้ใช้งานจำนวนมาก" แล้วทิ้งคำถามลูกค้า **ทั้งที่ reply ยังฟรีและตอบได้**

⇒ `isSystemThrottled() && ! isQuotaExhausted()` เสมอ
⇒ คลุมเครือ → **ยืนยันกับ quota API ก่อนล็อก** (อย่าปิด push ทั้งเดือนเพราะพลาดครั้งเดียว) + `hasQuotaRecovered()` เช็คซ้ำทุก 30 นาที

---

## 🔴 กฎข้อที่ 7 — ข้อจำกัดหน้าตา/รูปแบบของ LINE

| เรื่อง | กฎ |
|---|---|
| **WebP** | LINE **ไม่รองรับ** — รูปหายทั้งหมด ต้องผ่าน `lineSafeImageUrl()` (แปลงเป็น JPEG cache ที่ `line-jpg/`) |
| **รูปต้องเป็น HTTPS** | และ URL ต้องเข้าถึงได้จากภายนอกจริง — LINE ไป fetch เอง (ยิง reply สำเร็จ 200 ไม่ได้แปลว่ารูปขึ้น) |
| **Quick Reply เป็นลิงก์ไม่ได้** | ต้องใช้ button template + `web_url` |
| **ป้ายปุ่ม = อินพุตจริง** | กดปุ่มแล้วข้อความที่ส่งกลับมาคือ `text` ของปุ่ม — ด่านตรวจข้อความต้องรองรับ |
| **Quick Reply โชว์เฉพาะกล่องล่าสุด** | และไม่โชว์บน LINE เดสก์ท็อป |
| **ข้อความยาว** | ตัดที่ ~4,900 ตัว/กล่อง — ยาวกว่านั้นต้อง `splitTextForFlexPublic()` ห้าม `mb_substr` ตัดทิ้งเงียบๆ |
| **ปุ่มอันตรายห้ามอยู่ติดปุ่มหลัก** | "🔄 สับใหม่" ติด "🃏 เปิดไพ่" → ลูกค้ากดพลาดทิ้งไพ่ที่เปิดแล้ว 4 ใบ (เคสจริง 2026-08-31) |

---

## 🔴 กฎข้อที่ 8 — `fortune_readings` **ไม่มีคอลัมน์ `line_user_id`**

LINE userId เก็บใน **`platform_user_id`** และ/หรือ **`facebook_user_id`** (ชื่อคอลัมน์หลอก)

```php
// ❌ ผิด — ลูกค้า LINE จะตกเข้าสาขา FB เสมอ แล้วยิงเข้า Facebook Send API
if (! empty($reading->facebook_user_id)) { $fbService->sendMessage(...); }
elseif (! empty($reading->line_user_id)) { ... }   // ← DEAD CODE คอลัมน์นี้ไม่มีอยู่จริง

// ✅ ถูก — ดูจาก platform
$platform = $reading->platform;   // 'line' | 'facebook'
```

🪤 เคสจริง: `sendCelticThinkingAck()` ใช้ท่าผิดข้างบน ⇒ **ลูกค้า LINE ไม่เคยเห็นกล่อง
"แม่หมอกำลังเชื่อมจิต" เลยสักครั้ง** ตลอด 20-40 วิที่ AI คิด → ลูกค้าเจอความเงียบ →
พิมพ์ "หนูต้องกดตรงไหนนะคะ" → กด "สับใหม่" ทิ้งไพ่ที่เปิดไปแล้ว

---

## 🔴 กฎข้อที่ 9 — `method_exists()` ไม่ได้แปลว่าเรียกได้

`method_exists()` คืน **true กับเมธอด `protected`/`private` ด้วย**

```php
// ❌ ผิด — ผ่านด่านแล้วไปโยน Error ตอนเรียกจริง (pushMessage เป็น protected)
if (method_exists($lineService, 'pushMessage')) {
    return $lineService->pushMessage($userId, $message);
}
```

🪤 เคสจริง: `FortuneCelticRedeliver::pushAnswer()` ใช้ท่านี้ + ยังส่ง string เข้าพารามิเตอร์ที่รับ array
⇒ พังสองชั้น ถูกกลืนโดย `catch (\Throwable)` ⇒ **ตัวตามส่งฝั่ง LINE ตายเงียบมาตลอด**

⇒ ใช้ประตู public ที่เปิดไว้โดยเจตนา: `LineFortuneService::pushPaidDeliverable()`

---

## 🔴 กฎข้อที่ 10 — เรื่องที่ทำแล้วลูกค้าเสียหายทันที

- 🚫 **ห้ามรัน `php artisan cache:clear` ตอนลูกค้ากำลังคุย** — เป็น `flushdb` ทั้ง DB
  ล้าง MessageBuffer + ReplyTokenVault ทิ้ง = คำถามลูกค้าหายเกลี้ยง (เคสจริง FTU-260821-K9664)
- 🚫 **ห้าม deploy ตอนลูกค้ากำลังอยู่กลางเซสชันที่จ่ายเงินแล้ว** — deploy restart queue worker
  งานใน buffer ที่ยังไม่ flush จะหาย
- 🚫 **ห้าม restart queue worker เอง** — ให้ deploy จัดการ (`deploy.sh:1926` restart อัตโนมัติแล้ว)
- ⚠️ `FortuneTellingSetting::getSettings()` = static memo TTL 5 วิ **ไม่มี Cache store**
  ⇒ แก้ค่าใน DB มีผลเองใน 5 วิ ไม่ต้อง deploy ไม่ต้อง clear cache

---

## 📋 เช็คลิสต์ก่อน commit โค้ดที่ส่งข้อความหา LINE

- [ ] จุดนี้มี replyToken ให้ใช้ไหม — ถ้ามี **ต้อง** ลอง reply ก่อน
- [ ] ถ้าเป็นงานที่รอ AI — ยิง `showLoadingAnimation()` แล้วหรือยัง
- [ ] รวมหลายกล่องเป็น call เดียวแล้วหรือยัง (≤5 objects)
- [ ] ถ้าจะ push — ตอบได้ไหมว่า "ถ้าส่งไม่ออก ลูกค้าเสียอะไร"
- [ ] ถ้าส่งไม่ออก มี park รองรับไหม — หรือของหายเงียบ
- [ ] เนื้อหาที่ลูกค้าจ่ายเงินแล้ว เก็บลง DB **ก่อน** ส่งหรือยัง
- [ ] ธง delivered ตั้งจากผลส่งจริงหรือเปล่า (ไม่ใช่ตั้งล่วงหน้า)
- [ ] แยกแพลตฟอร์มด้วย `$reading->platform` ไม่ใช่ `!empty($reading->facebook_user_id)`
- [ ] รูปเป็น WebP ไหม — ผ่าน `lineSafeImageUrl()` หรือยัง
- [ ] ทดสอบเคส "โควต้าหมด" แล้วหรือยัง (`LineGatekeeperService::isQuotaExhausted()`)

---

## 🔗 ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | หน้าที่ |
|---|---|
| `app/Services/LineFortuneService.php` | ตัวส่งทั้งหมด · `pushPaidDeliverable()` = ประตู push สำหรับของที่จ่ายแล้ว |
| `app/Services/Fortune/ReplyTokenVault.php` | ฝาก replyToken ข้ามไปให้ job ตอบฟรี |
| `app/Services/LineGatekeeperService.php` | ธง quota_exhausted · throttle |
| `app/Http/Controllers/LineFortuneWebhookController.php` | `flushParkedCelticSummary()` / `flushParkedCelticAnswers()` / parked deep |
| `app/Console/Commands/FortuneCelticSummaryRedeliver.php` | ตามส่งบทสรุป 99฿ ที่ค้าง |
| `app/Console/Commands/FortuneCelticRedeliver.php` | ตามส่งคำตอบรายข้อที่ค้าง |
| `app/Services/FortuneChannelManager.php` | `markCelticSummaryDelivery()` บันทึกผลส่งจริง |

**เอกสารเวอร์ชัน:** 1.0 — 2026-08-31
