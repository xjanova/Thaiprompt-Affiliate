# Admin App API v2 — สัญญา JSON (ไทยพร้อม แอดมิน)

> สถานะ: **กำลังพัฒนา** บน branch `feat/admin-app-api-v2` — เอกสารนี้คือสัญญาที่แอป Flutter ใช้สร้างคู่ขนาน
> ทุก endpoint อยู่ใต้ `/api/admin/*` (ไฟล์ `routes/admin_api.php`) ต้องมี `Authorization: Bearer <admin token>`
> (token ที่ได้จาก `/api/admin/auth/login` หรือ `/api/admin/auth/pair/claim` — ability `admin`)

## กติการ่วม

- Envelope: `{ "success": bool, "data": any, "message"?: string, "errors"?: object, "error_code"?: string }`
- 401 = ไม่ได้แนบ token / token หมดอายุ · 403 = ไม่ใช่แอดมิน (`error_code: NOT_ADMIN`) หรือไม่มีสิทธิ์ทำรายการนั้น (`error_code: PERMISSION_DENIED`)
- 422 = ข้อมูลไม่ถูกต้อง (`errors` เป็น object ของ Laravel validation)
- เวลาทั้งหมดเป็น ISO-8601 เขตเวลา `+07:00` (Asia/Bangkok)
- เงินทั้งหมดเป็น **บาท (THB) ตัวเลขทศนิยม** เช่น `39.42`
- **list ใหม่ทุกตัว** ส่ง paging แบบแบน:
  `{ "data": { "data": [...], "current_page": 1, "last_page": 3, "per_page": 20, "total": 51 } }`
  (`per_page` รับ 1–100 ค่าเริ่มต้น 20 · `page` เริ่มที่ 1)
- ไม่มี endpoint ไหนคืนความลับ (API key / token) — คีย์ AI แสดงเป็น `key_masked` = `"••••abcd"` (4 ตัวท้ายเท่านั้น)

## ปุ่มจัดการ (ใช้ endpoint เดิม — ไม่มีตรรกะเงินใหม่)

| การกระทำ | Endpoint เดิม |
|---|---|
| ยืนยันว่าจ่ายแล้ว | `POST fortune/readings/{id}/mark-paid` body `{amount?, note?}` |
| คืนเงิน (พลิกธงจ่าย) | `POST fortune/readings/{id}/refund` body `{reason?}` |
| ยกเลิกบิล | `POST fortune/readings/{id}/cancel` body `{reason?}` |
| ส่งข้อความหาลูกค้า | `POST chat/send` body `{reading_id, text}` |
| เทคโอเวอร์ (หยุดบอท) | `POST chat/takeover` body `{reading_id, minutes?}` — ตั้งเวลาสิ้นสุดใหม่ = ตอนนี้ + minutes |
| ต่อเวลาเทคโอเวอร์ (**ใหม่**) | `POST chat/extend` body `{reading_id, minutes}` — บวกเพิ่มจากเวลาสิ้นสุดเดิม |
| คืนงานให้บอท | `POST chat/resume` body `{reading_id}` |

---

## 1. `GET ops/summary` — หน้าแรกของแอป (เรียกครั้งเดียวได้ครบ)

```json
{
  "success": true,
  "data": {
    "queue": {
      "customer_requests": {
        "count": 2,
        "oldest_minutes": 14,
        "preview": [
          {"reading_id": 15001, "customer_name": "สมหญิง", "platform": "line",
           "keyword": "ขอคุยกับแอดมิน", "requested_at": "2026-10-06T13:40:00+07:00", "remaining_minutes": 16}
        ]
      },
      "bills_awaiting": {
        "count": 3,
        "amount_thb": 237.84,
        "oldest_minutes": 22,
        "preview": [ /* BillItem แบบย่อ (ดูหัวข้อ 2) สูงสุด 5 รายการ เก่าสุดก่อน */ ]
      },
      "withdrawals_pending": {
        "count": 1, "amount_thb": 500.0, "oldest_minutes": 300,
        "preview": [{"id": 88, "user_name": "นายเอ", "amount_thb": 500.0, "created_at": "..."}]
      },
      "sms_unmatched": {
        "count": 4, "amount_thb": 156.0, "oldest_minutes": 80,
        "preview": [{"id": 9001, "bank": "KBANK", "amount_thb": 39.17, "sender": "นาย ก", "status": "pending", "at": "..."}]
      },
      "stuck_readings": {
        "count": 1, "oldest_minutes": 6,
        "preview": [ /* ActiveReading แบบย่อ (ดูหัวข้อ 4) */ ]
      }
    },
    "health": {
      "ai_pool": {"healthy": 5, "total": 7},
      "line_push": {"used_this_month": 120, "limit": 300, "remaining": 180, "exhausted": false, "checked_at": "..."},
      "queue_backlog": {"driver": "database", "pending": 0, "failed_24h": 0},
      "server_time": "2026-10-06T14:00:00+07:00"
    },
    "revenue_today": {
      "total": 1234.0,
      "fortune": 1000.0,
      "marketplace": 234.0,
      "other": 0.0,
      "currency": "THB",
      "hourly": [{"hour": 0, "amount": 0.0}, {"hour": 1, "amount": 39.0}, "... ครบ 24 ชั่วโมง 0..23"],
      "as_of": "2026-10-06T14:00:00+07:00"
    },
    "revenue_yesterday_same_time": 900.0,
    "revenue_change_pct": 37.1,
    "generated_at": "2026-10-06T14:00:00+07:00"
  }
}
```

### นิยาม (ตรงตามโค้ด)

- **customer_requests** = บทสนทนาที่ **ลูกค้าพิมพ์ขอคุยกับคน** และระบบเทคโอเวอร์ให้อัตโนมัติ (`admin_takeover_reason = customer_request`) ที่ **ยังไม่หมดเวลา** (`admin_takeover_until > now`)
  `keyword` = ข้อความที่ลูกค้าพิมพ์ตอนขอ (จาก log เทคโอเวอร์) · `oldest_minutes` นับจาก `admin_takeover_started_at`
- **bills_awaiting** = บิลที่ **แอดมินต้องตัดสินว่าเงินเข้าหรือยัง** — ดูนิยามเต็มที่หัวข้อ 2 (สถานะ `awaiting`)
  `amount_thb` = ผลรวมยอดบิล · `oldest_minutes` นับจากเวลาที่ลูกค้าส่งสลิป/แจ้งโอน
- **withdrawals_pending** = คำขอถอนเงิน `status = pending` (ชุดเดียวกับ `finance/withdrawals/pending`) · `amount_thb` = ผลรวม `amount`
- **sms_unmatched** = SMS เงินเข้า (`type = credit`) ที่ยังไม่ผูกกับบิล (`status` = `pending` หรือ `requires_admin_review`) **ภายใน 24 ชม.** (เก่ากว่านั้นถือว่าไม่ใช่งานค้างแล้ว)
- **stuck_readings** = บิลจ่ายแล้วที่ **ระบบไม่ขยับเกิน 2 นาที** — ดูนิยามเต็มที่หัวข้อ 4 (`stuck = true`)
- **health.ai_pool** — `healthy` = คีย์ที่ระบบหยิบไปใช้ได้จริงตอนนี้ (เงื่อนไขเดียวกับ `AiApiKey::scopeAvailable()`:
  `is_active` + ไม่ critical + ไม่ถูกพักชั่วคราว + `last_test_passed_at` ไม่ว่าง) · `total` = คีย์ทั้งหมดที่ไม่ถูกลบ
- **health.line_push** — ดึงจาก LINE API (`/message/quota` + `/consumption`) แคช 10 นาที; `null` ถ้ายังไม่เคยดึงสำเร็จ
  (ครั้งแรกจะเป็น `null` แล้วระบบดึงเบื้องหลังหลังตอบ — เรียกซ้ำรอบถัดไปจะได้ค่า) · `exhausted` = ธงโควต้าหมดของ Gatekeeper
- **health.queue_backlog** — `pending` = งานค้างในคิว (driver database = นับตาราง `jobs`, redis = ผลรวมคิวหลัก) · `failed_24h` = ตาราง `failed_jobs` 24 ชม.; `null` ถ้าอ่านไม่ได้
- **revenue_today** = **เงินเข้าจริงวันนี้** (00:00 ถึงตอนนี้ เวลาไทย)
  - `fortune` = บิลดูดวงที่ **กลายเป็นจ่ายแล้ววันนี้** (`is_paid = 1` และ `paid_at` วันนี้) · ยอด = `COALESCE(amount_received, amount_paid)`
    · ตัดแถวที่ไม่เคยออกบิล (`exceptNeverBilled`) · ตัดบิลเว็บจันทรา (จันทราเก็บเงินเอง) · บิลยกเลิก/คืนเงิน (`is_paid = 0`) ไม่นับ
  - `marketplace` = ออเดอร์ร้านค้าในระบบ (`orders`) ที่ `payment_status = paid` และ `paid_at` วันนี้ · ยอด = `total_amount`
  - `other` = `0` (ยังไม่มีแหล่งรายได้อื่นที่ชัดเจน)
  - `hourly` = 24 ช่อง (ชั่วโมง 0–23 เวลาไทย) ผลรวม fortune + marketplace ของชั่วโมงนั้น
- **revenue_yesterday_same_time** = รายได้เมื่อวาน 00:00 ถึงเวลาเดียวกับตอนนี้ (นิยามเดียวกับ `revenue_today.total`)
- **revenue_change_pct** = `(today - yesterday_same_time) / yesterday_same_time × 100` ปัด 1 ตำแหน่ง · `null` ถ้าเมื่อวานเป็น 0

---

## 2. บิลดูดวง

### `GET fortune/bills?status=&search=&platform=&package=&date_from=&date_to=&page=&per_page=`

- `status`: `awaiting` | `unpaid` | `paid` | `refunded` | `cancelled` | `closed` | `all` (ค่าเริ่มต้น `all`)
- `search`: ชื่อลูกค้า · เลขบิล · PSID / LINE id · `#id`
- `platform`: `facebook` | `line` | `telegram` | `other`
- `package`: `deep` | `celtic` | `free_card` | `basic` | `juntra`
- `date_from` / `date_to`: `YYYY-MM-DD` (เทียบ `created_at`)
- เรียงลำดับ: `awaiting` = รอนานสุดก่อน · `paid` = จ่ายล่าสุดก่อน · อื่น ๆ = อัปเดตล่าสุดก่อน

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 13284,
        "bill_number": "FTU-260916-D8561",
        "package": "deep",
        "package_label": "ดูพื้นดวง 39฿",
        "reading_type": "deep",
        "amount_thb": 39.42,
        "amount_received_thb": null,
        "status": "awaiting",
        "status_label": "รอตรวจ · ลูกค้าส่งสลิปแล้ว",
        "status_reason": "slip_received",
        "conversation_status": "pending_payment",
        "stage": {"key": "deciding", "label": "รอชำระเงิน"},
        "platform": "line",
        "customer_name": "Patitta",
        "customer": {"name": "Patitta", "platform_user_id": "Uxxxxxxxx", "user_id": 77},
        "created_at": "2026-09-16T11:30:00+07:00",
        "updated_at": "2026-09-16T11:35:10+07:00",
        "paid_at": null,
        "slip_image_url": "https://main.thaiprompt.online/api/admin/fortune/bills/13284/slip",
        "slip": {
          "received_at": "2026-09-16T11:35:00+07:00",
          "verified_at": null,
          "image_url": "https://main.thaiprompt.online/api/admin/fortune/bills/13284/slip",
          "image_requires_auth": true
        },
        "sms_match": {"matched": false, "sms_id": null, "amount": null, "at": null, "bank": null, "sender": null},
        "question_preview": "งานใหม่จะผ่านไหม",
        "customer_prior_paid_count": 2,
        "is_juntra": false,
        "actions": {"can_mark_paid": true, "can_refund": false, "can_cancel": true}
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

### สถานะบิล (แต่ละใบอยู่ได้ **สถานะเดียว** — ตรวจตามลำดับนี้)

ขอบเขต "บิล" = แถว `fortune_readings` ที่ **เคยออกบิลจริง** (`exceptNeverBilled()` — แถวเมนูที่ไม่เคยมียอดไม่นับ)
และต้องเป็นแพคเกจเสียเงิน (deep / celtic_cross / juntra) **หรือ** มีร่องรอยเงิน (จ่ายแล้ว / ยอด > 0 / บิลลอย) — ไพ่ฟรีที่ไม่มียอดไม่นับ

| status | นิยาม | ป้าย |
|---|---|---|
| `paid` | `is_paid = 1` และไม่ใช่บิลลอย | ชำระแล้ว |
| `awaiting` | (ก) **บิลลอย** `is_floating = 1` (เงินเข้าแต่ไม่รู้เจ้าของ) **หรือ** (ข) ยังไม่จ่าย + บิลยังเปิดอยู่ (`PENDING_DISPLAY_STATUSES`: pending_payment / celtic_pending_payment / awaiting_payment_method / pending_stripe_payment) + สร้างไม่เกิน 7 วัน + **ลูกค้าแจ้งว่าจ่ายแล้ว** (`slip_received_at` ไม่ว่าง = ส่งสลิป หรือ `transfer_reported = 1` = กดแจ้งโอน) | รอตรวจ · ลูกค้าส่งสลิปแล้ว / แจ้งโอนแล้ว / บิลลอย |
| `refunded` | ยังไม่จ่าย และ (`paid_at` ไม่ว่าง = ถูกพลิกจากจ่ายแล้วด้วย `readings/{id}/refund` **หรือ** `conversation_state.approval_voided = true` = ยกเลิกการอนุมัติจากหลังบ้าน) | คืนเงิน/ยกเลิกการอนุมัติ |
| `cancelled` | บิลยกเลิกตามนิยามกลาง (`completed` + ไม่จ่าย + มี `cancellation_reason` — `FortuneReading::cancelledSql()`) **หรือ** สถานะดิบ `cancelled` (แอป SMS Checker ปฏิเสธบิล) | ป้ายตามเหตุผลยกเลิก เช่น "ยกเลิกโดยระบบ" |
| `unpaid` | ยังไม่จ่าย + บิลยังเปิดอยู่ (`PENDING_DISPLAY_STATUSES`) + สร้างไม่เกิน 7 วัน + **ลูกค้ายังไม่ได้แจ้งจ่าย** | รอลูกค้าโอน |
| `closed` | ที่เหลือทั้งหมด: ออกบิลแล้วไม่จ่ายแล้วปิดเงียบ (completed ไม่มีเหตุผลยกเลิก) · บิลค้างรอจ่ายเกิน 7 วัน (ซากบิล) | ปิดโดยไม่ได้ชำระ |

- `status_reason`: `awaiting` → `slip_received` | `transfer_reported` | `floating` · `cancelled` → คีย์เหตุผล เช่น `auto_expired` · อื่น ๆ → `null`
- `amount_thb` = ยอดบิล (`amount_paid` ถ้า > 0 ไม่งั้นยอดทศนิยมจาก UPA) · `amount_received_thb` = ยอดที่เข้าจริง (ถ้ามี)
- `platform` อ่านจากคอลัมน์ `platform` เท่านั้น (ห้ามเดาจาก `facebook_user_id`) — ค่านอกเหนือ facebook/line/telegram = `other`
- `slip_image_url`: ถ้ามีรูปสลิปเก็บในเซิร์ฟเวอร์ → URL ของ `GET fortune/bills/{id}/slip` (**ต้องแนบ Bearer token**, `slip.image_requires_auth = true`)
  ถ้าไม่มีแต่ลูกค้าส่งรูปผ่านแพลตฟอร์ม → URL รูปนั้น (`image_requires_auth = false`) · ไม่มีเลย = `null`
- `sms_match` = SMS ธนาคารที่ผูกกับบิลนี้ (`sms_notification_id`) ถ้ามี
- `customer_prior_paid_count` = จำนวนบิลอื่นที่ลูกค้าคนนี้จ่ายแล้ว **ก่อน** บิลนี้ (นับจาก platform_user_id / facebook_user_id)
- `actions` เป็นคำใบ้ให้ UI เท่านั้น — การตัดสินจริงอยู่ที่ endpoint เดิม
  `can_mark_paid` = ยังไม่จ่าย + ไม่ใช่บิลจันทรา + ไม่ใช่บิลลอย + ไม่ใช่บิลที่ "ลูกค้าจ่ายบิลอื่นแทนแล้ว" (`superseded_by_paid` — อนุมัติซ้ำ = เก็บเงินซ้ำ)
  `can_refund` = จ่ายแล้ว + ไม่ใช่บิลจันทรา · `can_cancel` = ยังไม่จ่าย + ไม่ใช่บิลจันทรา

### `GET fortune/bills/stats`

```json
{
  "success": true,
  "data": {
    "counts": {"awaiting": 3, "unpaid": 5, "paid": 1200, "refunded": 4, "cancelled": 700, "closed": 300, "all": 2212},
    "awaiting_amount_thb": 237.84,
    "paid_today": {"count": 12, "revenue_thb": 800.0},
    "generated_at": "2026-10-06T14:00:00+07:00"
  }
}
```

`paid_today.revenue_thb` ใช้นิยามเดียวกับ `revenue_today.fortune`

### `GET fortune/bills/{id}/slip`

สตรีมรูปสลิป (image/jpeg) — แนบ Bearer token · `Cache-Control: private, no-store` · 404 ถ้าไม่มีรูป/ถูกลบตามรอบ 30 วัน

---

## 3. กล่องแชท / เทคโอเวอร์

### `GET takeover/conversations?status=&platform=&search=&page=&per_page=`

- `status`: `taken_over` (ค่าเริ่มต้น — บอทหยุดอยู่ตอนนี้) | `requested` (ลูกค้าขอคุยกับคน + ยังไม่หมดเวลา) | `active` (บทสนทนาที่ยังไม่จบ อัปเดตใน 7 วัน — ชุดเดียวกับหน้าเว็บ) | `all`

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "reading_id": 15001,
        "bill_number": "FTU-261006-K1234",
        "customer_name": "สมหญิง",
        "platform": "line",
        "platform_user_id": "Uxxxxxxxx",
        "package_label": "Celtic 99฿",
        "is_paid": true,
        "conversation_status": "celtic_qa_prompt",
        "stage": {"key": "celtic", "label": "เลือกไพ่ · Celtic"},
        "is_taken_over": true,
        "takeover_reason": "customer_request",
        "takeover_reason_label": "ลูกค้าขอคุยกับคน",
        "requested_by_customer": true,
        "request_keyword": "ขอคุยกับแอดมิน",
        "takeover_started_at": "2026-10-06T13:40:00+07:00",
        "takeover_until": "2026-10-06T14:10:00+07:00",
        "remaining_minutes": 10,
        "takeover_admin": null,
        "last_message": {"sender": "customer", "text": "ขอคุยกับแอดมิน", "at": "2026-10-06T13:40:00+07:00"},
        "unread": true,
        "updated_at": "2026-10-06T13:40:01+07:00"
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

- `last_message` มาจากบันทึกแชทสดวันนี้ (Redis) ถ้าไม่มี → ข้อความล่าสุดที่แอดมินส่งผ่านแผง (log เทคโอเวอร์) → `null`
- `unread` = ข้อความล่าสุดเป็นของลูกค้า (= ลูกค้ากำลังรอคำตอบ)

### `GET takeover/stats`

```json
{"success": true, "data": {"taken_over": 3, "requested": 2, "active_conversations": 41, "takeovers_today": 7,
  "takeover_enabled": true, "default_minutes": 30, "generated_at": "..."}}
```

### `GET takeover/{reading}/messages`

```json
{
  "success": true,
  "data": {
    "reading_id": 15001,
    "source": "redis",
    "messages": [
      {"id": 1, "sender": "system", "text": "💬 บทสนทนาเรียลไทม์วันนี้", "at": null, "admin_name": null, "image_url": null},
      {"id": 2, "sender": "customer", "text": "สวัสดีค่ะ", "at": "2026-10-06T13:39:00+07:00", "admin_name": null, "image_url": null},
      {"id": 3, "sender": "bot", "text": "สวัสดีค่ะ แม่หมอ...", "at": "...", "admin_name": null, "image_url": null, "ai_provider": "gemini"},
      {"id": 4, "sender": "admin", "text": "สักครู่นะคะ", "at": "...", "admin_name": "แอดมินเอ", "image_url": null}
    ],
    "generated_at": "..."
  }
}
```

- `sender`: `customer` | `bot` | `admin` | `system`
- `source`: `redis` = แชทสดวันนี้ (ครบทุกข้อความ) · `structured` = ประกอบจากข้อมูลที่เก็บถาวร (คำถาม/ไพ่/คำทำนาย/ข้อความแอดมิน) — ตรรกะเดียวกับ `fortune/readings/{id}/transcript`

### `POST chat/extend` (ใหม่)

body `{ "reading_id": 15001, "minutes": 15 }` (1–1440) — ใช้ `FortuneTakeoverService::extend()` ตัวเดียวกับปุ่มต่อเวลาบนเว็บ
(ถ้าไม่ได้เทคโอเวอร์อยู่ บริการจะเริ่มเทคโอเวอร์ใหม่ให้ตามพฤติกรรมเดิมของเว็บ)

```json
{"success": true, "data": {"reading_id": 15001, "is_takeover": true, "minutes_added": 15,
  "until": "2026-10-06T14:25:00+07:00", "remaining_minutes": 25}, "message": "ต่อเวลาอีก 15 นาที"}
```

### พฤติกรรมของ `POST chat/send` (เดิม — ไม่ได้แก้)

- Facebook: `FacebookWebhookService::sendMessage()` แบบ `RESPONSE` — ส่งได้เฉพาะลูกค้าที่ทักมาภายใน 24 ชม.
  (หน้าเว็บส่ง `message_tag = HUMAN_AGENT` แต่ `MESSAGE_TAG_USABLE = false` ตั้งแต่ 2026-08-13 เพราะ Meta ยกเลิกแท็ก ⇒ ผลเท่ากัน)
- LINE: `LineFortuneService::sendMessage()` = **push** (กินโควตา 300/เดือน) — ไม่มี reply token ให้ใช้ เพราะแอดมินพิมพ์หลังเวลาที่ token ใช้ได้
  (หน้าเว็บก็ลงเอยที่ push เหมือนกัน) · ถ้าโควตาหมด Gatekeeper จะไม่ยิงและตอบ `502 platform service rejected`
- Telegram: `TelegramFortuneService::sendMessage()`
- ไม่เทคโอเวอร์ให้อัตโนมัติ — ถ้าจะกันบอทพูดแทรก ให้เรียก `chat/takeover` ก่อน

---

## 4. `GET fortune/active-readings?stuck=&page=&per_page=`

บิลที่ **จ่ายแล้วและยังอยู่ระหว่างใช้บริการ** (ขยับภายใน 24 ชม.) + บิล Deep ที่งาน AI ล้มเหลว

- `stuck=1` = เฉพาะที่ค้าง

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "reading_id": 15020,
        "bill_number": "FTU-261006-P0001",
        "customer_name": "นายบี",
        "platform": "facebook",
        "package": "celtic",
        "package_label": "Celtic 99฿",
        "conversation_status": "celtic_generating",
        "stage": {"key": "predicting", "label": "AI กำลังทำนาย", "detail": "..."},
        "paid_at": "2026-10-06T13:20:00+07:00",
        "last_activity_at": "2026-10-06T13:52:00+07:00",
        "minutes_since_activity": 8,
        "stuck": true,
        "stuck_reason": "ai_generating_timeout",
        "celtic": {"picked": 10, "questions_used": 1},
        "is_taken_over": false
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1,
    "summary": {"total": 9, "stuck": 1}
  }
}
```

### นิยาม stuck (`App\Services\AdminApp\StuckReadingFinder`)

บิลจ่ายแล้ว (`is_paid = 1`, ไม่ใช่บิลจันทรา) ที่เข้าข้อใดข้อหนึ่ง:

1. `ai_generating_timeout` — อยู่ในสถานะที่ **AI ต้องทำงานเอง** (`AI_GENERATING_STATUSES` = `paid` · `celtic_generating`)
   และแถว **ไม่ขยับเกิน 2 นาที** (`updated_at <= now - 2 นาที`, ภายใน 24 ชม.)
   ยกเว้นงานที่ยังวิ่งอยู่จริง: Deep ที่มีธง `fortune:deep_gen:{id}` / `fortune:deep_deliver:{id}` · Celtic ที่ `CelticCrossService::isGenerationInFlight()`
   (Celtic ตอบช้าได้ถึง ~2 นาทีจริง — ช้า ≠ ตาย)
2. `deep_job_failed` — Deep ที่ `completed` แต่ `deep_response` ว่าง และจ่ายมาแล้ว 2 นาที–24 ชม.
   (ชุดเดียวกับที่ `fortune:check-pending` และแอป SMS Checker หยิบไป retry)

สถานะที่รอ **ลูกค้า** ตอบ (เก็บวันเกิด / เลือกไพ่ / รอคำถาม) **ไม่นับว่าค้าง** ต่อให้เงียบนาน — ระบบไม่ได้ค้าง ลูกค้ายังไม่พิมพ์

> ต่างจาก `fortune/workers/queue.stuck` ของ Warroom (นับ `responded_at` ว่าง + `created_at` 1–10 นาที)
> ซึ่งนับลูกค้า Deep ที่กำลังกรอกวันเกิดเป็นค้าง และมองไม่เห็นบิลที่สร้างนานกว่า 10 นาที — ตัวนี้ใช้สถานะจริงแทน

---

## 5. AI Pool (คีย์ AI ของบอทแม่หมอ — ตาราง `ai_api_keys`)

### `GET fortune/ai-pool`

```json
{
  "success": true,
  "data": {
    "global_mode": "smart",
    "global_mode_source": "env",
    "global_mode_writable": false,
    "modes": [{"key": "smart", "label": "Smart (⭐ กระจาย load อัจฉริยะ — แนะนำ)"}, {"key": "round_robin", "label": "..."}],
    "providers": [{"provider": "gemini", "name": "Google Gemini", "rotation_mode": "round_robin", "keys_total": 3, "keys_healthy": 2}],
    "summary": {"total": 7, "healthy": 5, "active": 6},
    "keys": [
      {
        "id": 12,
        "provider": "gemini",
        "provider_name": "Google Gemini",
        "label": "Gemini หลัก",
        "model": "gemini-2.5-flash",
        "priority": 80,
        "purpose": "prediction",
        "purposes": ["prediction"],
        "purpose_label": "🔮 ทำนายทั่วไป (ใช้ทั้ง Deep 39 + Celtic 99 ถ้าไม่แยก)",
        "is_active": true,
        "is_critical": false,
        "healthy": true,
        "disabled_until": null,
        "last_test_passed_at": "2026-10-06T09:00:00+07:00",
        "last_test_failed_at": null,
        "last_test_message": "OK (412ms)",
        "last_error": null,
        "last_error_at": null,
        "consecutive_errors": 0,
        "last_used_at": "2026-10-06T13:59:00+07:00",
        "usage_today": {"requests": 120, "tokens": 340000, "errors": 2},
        "key_masked": "••••a1B2"
      }
    ],
    "can_manage": true,
    "generated_at": "..."
  }
}
```

- `healthy` = เงื่อนไข `AiApiKey::scopeAvailable()` (active + ไม่ critical + ไม่ถูกพัก + เคยเทสผ่าน)
- `usage_today` นับจาก `ai_api_key_usage_logs` วันนี้ (log ทุกครั้งที่บอทเรียก AI)
- `last_test_message` / `last_error` ผ่าน `SafeLog::redactSecrets()` ก่อนส่ง
- **โหมดรวม** (`global_mode`) ตั้งจาก env `AI_CROSS_PROVIDER_ROTATION` เท่านั้น (config/ai.php — ตั้งใจไม่เก็บใน DB) ⇒ แก้จากแอปไม่ได้
  โหมดที่แก้ได้คือ **โหมดราย provider** (ชุดเดียวกับหน้าเว็บ `ai-api-keys/provider/{provider}/settings`)

### `POST fortune/ai-pool/keys/{key}/toggle`
เปิด/ปิดคีย์ — `AiApiKeyPoolService::toggleKey()` ตัวเดียวกับเว็บ · ตอบ `{success, data: {key: KeyItem}, message}`

### `POST fortune/ai-pool/keys/{key}/test`
เทสคีย์ด้วยรูทีนเดียวกับปุ่ม "ทดสอบ" บนเว็บ (`Admin\AiApiKeyController::test`) — ผ่าน = ตั้ง `last_test_passed_at` · ไม่ผ่าน = ตั้ง `last_test_failed_at`
```json
{"success": true, "data": {"passed": true, "response_time_ms": 412, "model": "gemini-2.5-flash", "model_known": true,
  "model_warning": null, "key": {"...KeyItem..."}}, "message": "✅ API Key ใช้งานได้ปกติ — key พร้อมใช้ใน Pool"}
```
ไม่ผ่าน → HTTP 200 แต่ `success: false`, `data.passed: false`, `message` = สาเหตุ (ปิดความลับแล้ว)

### `POST fortune/ai-pool/mode`
body `{ "provider": "gemini", "mode": "smart" }` — `mode` ∈ คีย์ของ `modes` · ตั้งโหมดราย provider (`AiApiKeyPoolService::updateProviderSettings`)
ไม่ส่ง `provider` / ส่ง `"*"` → 422 `error_code: GLOBAL_MODE_ENV_ONLY`
```json
{"success": true, "data": {"provider": "gemini", "rotation_mode": "smart"}, "message": "ตั้งโหมดของ gemini แล้ว"}
```

**สิทธิ์ (ทุก endpoint เขียนในหมวดนี้)**: super admin หรือมีสิทธิ์ `manage_api_keys` — ไม่งั้น 403 `PERMISSION_DENIED`

---

## 6. `GET fortune/services` (อ่านอย่างเดียว)

```json
{
  "success": true,
  "data": {
    "services": [
      {"id": "deep", "reading_type": "deep", "name": "ดูพื้นดวง 39฿", "price_thb": 39.0, "is_active": true},
      {"id": "celtic", "reading_type": "celtic_cross", "name": "Celtic 99฿", "price_thb": 99.0, "is_active": true},
      {"id": "free_card", "reading_type": "free_card", "name": "ไพ่ฟรี", "price_thb": 0.0, "is_active": false}
    ],
    "categories": [{"id": 1, "name": "ความรัก", "slug": "love", "icon": "💕", "color": "#ff6699", "is_active": true}],
    "writable": false
  }
}
```

ไม่มี toggle — หน้าเว็บไม่มีปุ่มเปิด/ปิดแพคเกจแบบเดี่ยว (เปิดปิดผ่านฟอร์มตั้งค่าทั้งก้อน) จึงไม่สร้างทางเขียนใหม่

---

## 7. แก้ endpoint เดิม (เพิ่มอย่างเดียว — รูปเดิมยังอยู่ครบ)

- `GET finance/wallets`, `GET finance/withdrawals`, `GET finance/withdrawals/pending`
  เดิมคืนแค่ `{data: [...]}` (paging หายเพราะเรียก `->load()` บน paginator) → ตอนนี้คืน
  `{data: [...], links: {...}, meta: {current_page, last_page, per_page, total, ...}, current_page, last_page, per_page, total}`
- `auth/me` (และทุกที่ที่คืน `admin`): `permissions` = รายชื่อสิทธิ์จริง (role ใหม่ + คอลัมน์ `permissions` เดิม) · super admin = `["*"]`
  `avatar_url` = URL รูปโปรไฟล์จริง (รูป LINE หรือรูปที่อัปโหลด) · ไม่มีรูป = `null`
- บัญชีถูกระงับ (403): เพิ่ม `error_code: "ACCOUNT_SUSPENDED"` คู่กับ `code` เดิม
