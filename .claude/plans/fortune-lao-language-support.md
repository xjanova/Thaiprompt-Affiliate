# Fortune Bot — Lao Language Support (Facebook only)

> **Status:** ✅ Phase 1 (MVP) implemented 2026-05-03 — รอ deploy + smoke test production
> **Platform scope:** Facebook Messenger เท่านั้น (LINE ยังเป็นไทยล้วน)
> **Last discussed:** 2026-04-22
> **Session context:** หลังจากชุดแก้ fortune comment engagement + free-off streamlining + mid-flow AI assistance

---

## ✅ 2026-05-03 — Phase 1 Implementation

**แนวทางที่เลือก:** Auto-detect ทุก inbound message + manual picker override + AI mirror ภาษา (ไม่แตะ prompt)

### Files added
- `database/migrations/2026_05_03_100000_create_fortune_user_languages_table.php`
- `app/Models/FortuneUserLanguage.php`
- `app/Services/FortuneLocaleService.php`
  - `detectFromText()` — Unicode block U+0E80–U+0EFF (Lao) vs U+0E00–U+0E7F (Thai), majority wins
  - `resolveForMessage()` — manual choice ชนะเสมอ, ไม่งั้น auto-detect + cache 5min
  - `current()` / `setCurrent()` — request-scoped locale สำหรับ template/builder อ่าน
  - `lo($th, $lo)` — helper switch text ตาม current()

### Files modified (additive — ไม่แตะ flow logic)
- `app/Services/FortuneChannelManager.php` — resolve locale ก่อน processMessage (LINE บังคับ 'th')
- `app/Jobs/ProcessCommentEngagement.php` — detect จาก comment text → set current() (AI mirror ภาษา DM)
- `app/Services/FacebookRichMessageService.php` — Lao variants ของ welcome/upsell/payment templates + buttons
- `app/Http/Controllers/FacebookWebhookController.php` — LANG_TH/LANG_LO postback handlers
- `app/Services/FacebookWebhookService.php` — Ice Breaker entries สำหรับ language picker

### Payment hint ลาว (ใน buildPaymentTemplate)
- ระบุ Cross-Border QR app: BCEL One, LDB, JDB, APB
- แจ้งแปลง KIP→BAHT อัตโนมัติ
- ขยาย delay expectation 1-5 นาที

### ⚠️ Pending production deploy
1. รัน migration: `php artisan migrate --force` บน production
2. รี-setup messenger profile (ให้ FB API รับ Ice Breaker ใหม่):
   - Admin → SMS Payment / Fortune Settings → ปุ่ม "Setup Messenger Profile"
   - หรือ artisan command ที่เรียก `FacebookWebhookService::setupMessengerProfile()`
3. Smoke test:
   - พิมพ์ลาว → บอทควรตอบลาว (ผ่าน AI mirror)
   - กด Ice Breaker "🇱🇦 ປ່ຽນເປັນພາສາລາວ" → confirmation ลาว
   - กด Welcome button "🆓 ดูดวงฟรี" → ทดสอบ flow ปกติยังทำงาน
   - LINE: พิมพ์ "ดูดวง" → ต้องเป็นไทย 100%

### Known limitations (จงใจ scope cut)
- **Static strings ใน FortuneConversationService** (~60 จุด: AWAITING_CONFIRMATION/COLLECTING_BIRTHDATE prompts ฯลฯ) — ยังเป็นไทย, รอ Phase 2
  - แนวทาง: AI step reminder + AI assist replies ที่มีอยู่จะแปลให้ลาวอยู่แล้ว, ไทย-hardcode prompts ลูกค้าลาวจะเห็นเป็นไทย แต่ตอบลาวได้ปกติ
- **Date parser** — ยังรับเฉพาะเลขไทย/อารบิก (ไม่รับเลขลาว ໐–໙ + ชื่อเดือนลาว)
  - Workaround: ลูกค้าลาวพิมพ์เลขอารบิก 12/05/2530 ได้ปกติ
- **Keyword detection** (`isCancelRequest`, `isSimpleConfirmResponse` ฯลฯ) — ยังเป็นไทย/อังกฤษ
  - Workaround: AI mid-flow assist จับ chitchat ลาวได้ + คำว่า "ใช่" "ไม่" "ยกเลิก" ลูกค้าลาวพิมพ์ไทยได้
- **PromptPay QR** — ยังเป็น QR ไทย (ใช้ Cross-Border ของแอปลาว)
- **Static text on persistent menu top-3** — ยังเป็นไทย (FB จำกัด 3 + ภาษาตาม FB locale ไม่ relate กับ user choice)
- **AI prompt directives** — ไม่แตะ (per user — Gemini/Groq mirror ภาษา input อยู่แล้ว)

---

## 🎯 User Request

"ถ้าเราจะโต้ตอบเป็นภาษาลาวอีกภาษาหนึ่ง เฉพาะ facebook โดยให้เลือกภาษาได้ด้วยจะทำเยอะไหม"

**ต้องการ:**
- รองรับภาษาลาวเพิ่ม (ไทย ← default, ລາວ ← เพิ่ม)
- เฉพาะช่องทาง Facebook Messenger
- ลูกค้าเลือกภาษาได้เอง (language picker)
- ต้องไม่กระทบการทำงานปัจจุบัน (ไทย + LINE)

---

## 📊 Scope Breakdown

### 🟢 เล็ก (ทำเร็ว)

- **DB migration** — ตาราง `fortune_user_languages`
  ```
  id, facebook_user_id (unique), locale (th|lo), created_at, updated_at
  ```
- **Model** — `FortuneUserLanguage` + helper `getLocale($psid)` / `setLocale($psid, $locale)`

### 🟡 กลาง (ทำพอควร)

- **Language picker UX**
  - Postback button `LANG_TH` / `LANG_LO` ใน persistent menu + quick reply
  - ถาม locale ครั้งแรกตอน Get Started (ก่อนเข้า welcome)
  - บันทึก choice ลง DB

- **AI prompts** — append locale directive
  - `FortuneAIService::generateChatResponse` → system prompt เพิ่ม "ตอบเป็น{ภาษา}"
  - `generateFortuneTelling` → prompt เพิ่ม locale
  - `buildAIAssistedStepReminder` → pass locale ด้วย
  - `generateCommentEngagement` → locale

- **Keywords ลาว**
  - `isGenericFortuneRequest` → เพิ่ม "ເບິ່ງດວງ", "ທຳນາຍ", "ຫມໍດູ"
  - `isCancelRequest` → "ຍົກເລີກ", "ເລີ່ມໃໝ່"
  - `isDeclineResponse` → "ບໍ່", "ບໍ່ເອົາ"
  - `isSimpleConfirmResponse` → "ແມ່ນ", "ເອົາ", "ດູ"
  - `isExplicitDeepReadingRequest` → "ເບິ່ງດວງລະອຽດ"
  - `looksLikeMetaOrChitchat` → "ສະບາຍດີ", "ຂອບໃຈ", "ລາຄາເທົ່າໃດ", "ແມ່ນບໍ"

- **Date parser** — `parseBirthDate` + Lao month names
  - ມັງກອນ=1, ກຸມພາ=2, ມີນາ=3, ເມສາ=4, ພຶດສະພາ=5, ມິຖຸນາ=6, ກໍລະກົດ=7, ສິງຫາ=8, ກັນຍາ=9, ຕຸລາ=10, ພະຈິກ=11, ທັນວາ=12
  - Lao numerals: ໐໑໒໓໔໕໖໗໘໙ → 0123456789

- **Button labels** + **Persistent menu** — duplicate Lao copy
  - "🔮 ເບິ່ງດວງ", "💎 ເບິ່ງດວງລະອຽດ", "📊 ກວດສິດ", "📝 ວິທີໃຊ້"

- **Admin UI** — toggle "Enable Lao on Facebook" + editor template

- **Welcome/Help templates** — เพิ่ม field `content_lo` ใน `fortune_response_templates`

### 🔴 เยอะสุด

- **Static messages** ~80-120 จุด — สกัดออกเป็น lang file
  - `resources/lang/fortune/th.php` + `resources/lang/fortune/lo.php`
  - ทุก hardcoded string ใน services ต้องแทนด้วย `__('fortune.xxx')` หรือ helper custom
  - จุดหลัก:
    - `FortuneConversationService.php` — ~60 strings
    - `FacebookWebhookService.php` — ~20 strings
    - `FacebookRichMessageService.php` — ~30 strings (welcome, upsell, check-remaining, etc.)

---

## ⏱️ ประเมินเวลา

| Phase | Scope | เวลา |
|---|---|---|
| **Phase 1 (MVP)** | DB + picker + AI directive + 10-15 critical strings ด้วยมือ | **1-1.5 วัน** |
| **Phase 2 (Full)** | แปลครบทุก static + keywords + date parser + admin editor | **+1-2 วัน** |
| **Phase 3 (Polish)** | wording ลาวให้ถูกต้อง (ควรให้คนลาวตรวจ) | **+0.5 วัน** |

**รวม 2.5-4 วัน**

---

## 💡 แนวทางที่เสนอ: Phase 1 (MVP) First

วิธีนี้ลูกค้าลาวใช้งานได้ ~90% ใน 1 วัน เพราะ AI แปลสด:

### Step 1: Infrastructure (2-3 hrs)
1. Migration: `fortune_user_languages` table
2. Model: `App\Models\FortuneUserLanguage`
3. Helper: `FortuneLocaleService::get($psid, 'facebook') → 'th' | 'lo'`
4. Middleware หรือ inject ใน `FortuneChannelManager` ให้ทุก handler รู้ locale

### Step 2: Picker UX (2-3 hrs)
1. เพิ่ม persistent menu item "🌐 ພາສາ / ภาษา"
2. Postback handlers:
   - `LANG_TH` → set locale=th, reply "เปลี่ยนเป็นภาษาไทยแล้ว"
   - `LANG_LO` → set locale=lo, reply "ປ່ຽນເປັນພາສາລາວແລ້ວ"
3. Get Started hook: ถ้ายังไม่เคยเลือก → ถามก่อน
   - Quick reply: `🇹🇭 ไทย` / `🇱🇦 ລາວ`

### Step 3: AI directive (1-2 hrs)
1. `FortuneAIService` constructor รับ `$locale = 'th'`
2. System prompts + user prompts → append directive
   - th: (no change — default)
   - lo: "ກະລຸນາຕອບເປັນພາສາລາວເທົ່ານັ້ນ"
3. `tryAIChatResponse`, `generateCommentEngagement`, `buildAIAssistedStepReminder`, `generateFortuneTelling` — pass locale
4. Chat system prompt (`chat_system_prompt`) — ถ้ามี locale=lo, prepend lao directive

### Step 4: Critical strings by hand (2-3 hrs)
แปลข้อความที่ AI แปลไม่ทัน (static) — ใช้ `match($locale)` แบบง่ายๆ:
- Welcome/Get Started response
- "กรุณาระบุวันเกิด" + examples
- "กำลังประมวลผลคำทำนาย"
- Payment info (ยอด, บัญชี, เวลา)
- Cancel/restart messages
- Button labels หลักๆ (ดูดวง, ดูดวงละเอียด, ยกเลิก, ใช่/ไม่)

---

## 📁 Files ที่ต้องแตะ

### New files
- `database/migrations/XXXX_create_fortune_user_languages_table.php`
- `app/Models/FortuneUserLanguage.php`
- `app/Services/FortuneLocaleService.php` (หรือ trait)
- `resources/lang/fortune/th.php` + `lo.php` (ถ้าใช้ Phase 2)

### Modified files
- `app/Http/Controllers/FacebookWebhookController.php` — picker postbacks
- `app/Services/FortuneConversationService.php` — inject locale, wrap strings
- `app/Services/FortuneAIService.php` — locale directive
- `app/Services/FortuneChannelManager.php` — determine locale ตาม platform+psid
- `app/Services/FacebookWebhookService.php` — persistent menu
- `app/Services/FacebookRichMessageService.php` — rich templates
- `app/Http/Controllers/Admin/FortuneSettingsController.php` — toggle + Lao editor (Phase 2)

### Do NOT touch
- LINE paths (`LineFortuneService`, `LineFortuneWebhookController`) — ลาวเฉพาะ Facebook
- Comment engagement: อาจต้อง locale-aware ด้วยเพราะ DM ออกจากการ comment — ตัดสินใจตอนทำ

---

## ⚠️ Gotchas ที่ต้องระวัง

1. **LINE ไม่เปลี่ยน** — scope facebook only → ทุก change ต้อง guard ด้วย platform check
2. **Comment engagement** — user comment ใน post (อาจเป็นลาว) แต่ระบบส่ง DM → locale ต้องมาจากการเลือกของ user, ไม่ใช่จากคอมเม้นต์
3. **Admin takeover** — admin พิมพ์เอง ไม่ต้องแปล (บอทแค่หยุด)
4. **Persistent menu FB จำกัด 3 top-level** — อาจต้องแทนที่ปุ่มเดิม
5. **Shared `FortuneConversationService` FB+LINE** — ต้อง pass platform+locale เข้า method (อย่าให้ LINE ได้ locale=lo หลุดเข้ามา)
6. **Date parser** — ถ้า user พิมพ์ไทยขณะอยู่ locale=lo → ต้อง parse ไทยได้ด้วย (fallback) ไม่งั้น stuck
7. **AI prompts ยาวขึ้น** — inject locale directive ใช้ token เพิ่ม — อาจต้องปรับ max_tokens
8. **Keyword detection fallback** — detect Lao keywords ก่อน Thai keyword เพราะบางคำใกล้กัน
9. **PromptPay QR** — ยังคงเป็นไทย (ระบบ payment ภายในประเทศ) — ลูกค้าลาวต้องโอนตาม QR ไทย — อาจมีปัญหา UX ต้องชี้แจง

---

## 🗺️ Commit chain ก่อนหน้า (context)

เส้นทาง claude/Main ล่าสุด:
- `003c6fe83` skip reaction/comment DM when mid-flow
- `66af492df` AI across mid-flow states (chitchat detector)
- `ea9d743f5` AI step reminder (birthdate invalid)
- `cb2108d15` streamline free-off flow
- `2227bf2fe` route all fortune buttons to deep flow
- `f93377a41` 5 reliability fixes comment engagement
- `e00004e76` defensive warm-lead check
- `68582cbff` DM every comment (drop user+post dedupe)

Session ก่อนหน้าโฟกัสที่ fortune bot UX + comment engagement ในภาษาไทย

---

## 🚦 Decision pending (ถามผู้ใช้ก่อน)

**ก่อนเริ่มทำ ต้องยืนยันว่าเลือกทางไหน:**

- [ ] **Phase 1 MVP** (1-1.5 วัน) — โครงสร้าง + picker + AI directive + critical strings
- [ ] **Phase 1+2 Full** (2.5-3.5 วัน) — แปลครบทุก static + keywords + date parser + admin editor
- [ ] **Phase 1+2+3 Polish** (3-4 วัน) — + wording ลาวตรวจโดยเจ้าของภาษา

คำถามเพิ่ม:
- Admin ต้องการแก้ Lao template ได้เองหรือให้ AI แปลสด?
- การ์ด Flex/rich template ทั้งหมดแปลด้วยไหม (บางจุด label ภาษาไทย preset)?
- ถ้าลูกค้าคอมเม้นต์เป็นลาว → บอทควรตอบลาวหรือถามเลือกภาษาก่อน?

---

## 📝 Implementation checklist (เมื่อเริ่มทำ)

```
Phase 1 (MVP)
[ ] Migration fortune_user_languages
[ ] Model FortuneUserLanguage + tests
[ ] FortuneLocaleService::get/set
[ ] Inject locale context ใน FortuneChannelManager
[ ] Persistent menu "🌐 ພາສາ / ภาษา" item
[ ] Get Started locale picker quick reply
[ ] Postback handler LANG_TH / LANG_LO
[ ] AI prompts — append locale directive (4 methods)
[ ] 15 critical strings by hand (welcome, birthdate, payment, cancel, buttons)
[ ] Test: switch locale → AI ตอบลาว
[ ] Test: LINE ยังทำงานเป็นไทยปกติ
[ ] Test: Thai default ไม่ถูกกระทบ

Phase 2 (Full)
[ ] Extract all static strings → lang files
[ ] Lao keywords detection
[ ] Lao date parser
[ ] Admin UI toggle + template editor
[ ] Rich templates Lao variant
[ ] Comment engagement Lao (ถ้าจำเป็น)

Phase 3 (Polish)
[ ] ตรวจ wording กับคนลาว
[ ] Fix sensitivity / politeness
[ ] Edge cases (mixed language input)
```
