# Admin App API v2 / v3 — สัญญา JSON (ไทยพร้อม แอดมิน)

> สถานะ: v2 ขึ้น `claude/Main` แล้ว · **v3 กำลังพัฒนา** บน branch `feat/admin-app-api-v3` (ดู **หัวข้อ 8 — v3**)
> — เอกสารนี้คือสัญญาที่แอป Flutter ใช้สร้างคู่ขนาน · ข้อความที่ v3 เปลี่ยนความหมายมีป้าย **(v3)** ชี้ไปหัวข้อ 8
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
| ยืนยันว่าจ่ายแล้ว | `POST fortune/readings/{id}/mark-paid` body `{amount?, note?}` — **(v3)** ไม่ส่ง amount = ยอดจริงของบิล (8.7) |
| คืนเงิน / ยกเลิกการอนุมัติ | `POST fortune/readings/{id}/refund` body `{reason?}` — **(v3)** ถอยครบแบบปุ่ม void บนเว็บ (8.6) |
| ยกเลิกบิล | `POST fortune/readings/{id}/cancel` body `{reason?}` |
| ทำนายซ้ำบิลค้าง (**v3 ใหม่**) | `POST fortune/readings/{id}/retry` — เซิร์ฟเวอร์เลือกวิธีกู้เอง (8.5) |
| โอนเงินถอนแล้ว (แนบสลิป) | `POST finance/withdrawals/{id}/complete` multipart `{transfer_slip?, transfer_note?}` (สัญญาเต็ม 8.8) |
| ส่งข้อความหาลูกค้า | `POST chat/send` body `{reading_id, text}` — **(2026-10-06) เริ่ม/ต่อเทคโอเวอร์ให้อัตโนมัติ** (เหลืออย่างน้อย 30 นาที) |
| เทคโอเวอร์ (หยุดบอท) | `POST chat/takeover` body `{reading_id, minutes?}` — ตั้งเวลาสิ้นสุดใหม่ = ตอนนี้ + minutes (ไม่ส่ง minutes = อย่างน้อย 30 นาที) |
| ต่อเวลาเทคโอเวอร์ (**ใหม่**) | `POST chat/extend` body `{reading_id, minutes}` — บวกเพิ่มจากเวลาสิ้นสุดเดิม (หมดเวลาไปแล้ว = เริ่มใหม่ ตอนนี้ + minutes) |
| คืนงานให้บอท | `POST chat/resume` body `{reading_id, deliver_deferred?}` — `deliver_deferred` (bool, ค่าเริ่มต้น `true`) = ให้บอทส่งของที่ลูกค้าจ่ายแล้วซึ่งถูกพักไว้ · `false` = "จัดการเองแล้ว ไม่ต้องส่ง" |

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
        "preview": [
          {"id": 13284, "bill_number": "FTU-260916-D8561", "package_label": "ดูพื้นดวง 39฿", "amount_thb": 39.42,
           "status": "awaiting", "status_label": "รอตรวจ · ลูกค้าส่งสลิปแล้ว", "status_reason": "slip_received",
           "platform": "line", "customer_name": "Patitta", "created_at": "...", "slip_image_url": "...",
           "sms_match": {"matched": false, "sms_id": null, "amount": null, "at": null, "bank": null, "sender": null},
           "claimed_at": "2026-10-06T13:38:00+07:00"}
        ]
      },
      "withdrawals_pending": {
        "count": 1, "amount_thb": 500.0, "oldest_minutes": 300,
        "preview": [{"id": 88, "user_name": "นายเอ", "amount_thb": 500.0, "net_amount_thb": 490.0, "created_at": "..."}]
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
    "computed_at": "2026-10-06T13:59:48+07:00",
    "generated_at": "2026-10-06T14:00:00+07:00",
    "degraded": []
  }
}
```

### Cache และส่วนที่อ่านไม่ได้

- ผลทั้งก้อน **cache 20 วินาที ใช้ร่วมกันทุกแอดมิน** (key `admin_app:ops_summary`) — `computed_at` = เวลาที่คำนวณจริง
  · `generated_at` และ `health.server_time` = เวลาของคำตอบนี้ (เขียนทับทุกครั้ง) · `revenue_today.as_of` = เวลาที่คำนวณ
- ส่วนที่คำนวณไม่สำเร็จ (DB/บริการภายนอกพัง) ได้ค่า **`null`** และชื่อส่วนอยู่ใน **`degraded`** (อาร์เรย์ว่าง = ปกติทุกส่วน)
  ชื่อที่เป็นไปได้: `customer_requests` · `bills_awaiting` · `withdrawals_pending` · `withdrawals_approved` **(v3)** · `sms_unmatched` · `stuck_readings`
  · `ai_pool` · `line_push` · `queue_backlog` · `revenue_fortune` · `revenue_marketplace`
  - กล่องคิวที่เป็น `null` = **อ่านไม่ได้** (ไม่ใช่ "ไม่มีงาน") — แอปควรโชว์ "—" + คำเตือน
  - `revenue_fortune` / `revenue_marketplace` พัง → `revenue_today.fortune` / `.marketplace` = `null`, `total` = ผลรวมเฉพาะส่วนที่อ่านได้,
    `hourly` = เฉพาะส่วนที่อ่านได้ · ถ้าวันนี้หรือเมื่อวานอ่านไม่ครบ → `revenue_change_pct = null`
    (ส่วนเมื่อวานพัง → `revenue_yesterday_same_time = null` ด้วย)
  - `health.line_push = null` โดยที่ **ไม่มี** `line_push` ใน `degraded` = ยังไม่เคยดึงโควตาสำเร็จ (ปกติ ดูด้านล่าง)

### นิยาม (ตรงตามโค้ด)

- **customer_requests** **(v3 — นิยามใหม่ ดู 8.2)** = บิลที่ลูกค้าพิมพ์ขอคุยกับคนใน 24 ชม. และ **ยังไม่มีแอดมินลงมือ** (อ่านจาก `fortune_takeover_logs`)
  ~~เดิม: เทคโอเวอร์อัตโนมัติ `admin_takeover_reason = customer_request` ที่ยังไม่หมดเวลา~~ — webhook เลิกเทคโอเวอร์ให้ตั้งแต่ 2026-05-17 กล่องเลยว่างตลอด
  `keyword` = ข้อความที่ลูกค้าพิมพ์ตอนขอ (คำขอล่าสุด) · `oldest_minutes` นับจากคำขอที่ค้างนานสุด
- **bills_awaiting** = บิลที่ **แอดมินต้องตัดสินว่าเงินเข้าหรือยัง** — ดูนิยามเต็มที่หัวข้อ 2 (สถานะ `awaiting`)
  `amount_thb` = ผลรวมยอดบิลต่อใบ **ตัวเดียวกับ `amount_thb` ในรายการบิล** (`amount_paid` ถ้า > 0 ไม่งั้นยอดทศนิยมจาก UPA)
  · `oldest_minutes` นับจากเวลาที่ลูกค้าส่งสลิป/แจ้งโอน
- **withdrawals_pending** = คำขอถอนเงิน `status = pending` (ชุดเดียวกับ `finance/withdrawals/pending`) · `amount_thb` = ผลรวม `amount`
- **sms_unmatched** = SMS เงินเข้า (`type = credit`) ที่ยังไม่ผูกกับบิล (`status` = `pending` หรือ `requires_admin_review`) **ภายใน 24 ชม.** (เก่ากว่านั้นถือว่าไม่ใช่งานค้างแล้ว)
- **withdrawals_approved** **(v3)** = คำขอถอนที่อนุมัติแล้วแต่ยังไม่ได้โอน (`status = approved`) — ดู 8.3
- **stuck_readings** = บิลจ่ายแล้วที่ **ระบบไม่ขยับเกิน 2 นาที** — ดูนิยามเต็มที่หัวข้อ 4 (`stuck = true`) · **(v3)** รวมบิลค้างเกิน 24 ชม. (`escalated_24h`)
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
        "stage": {"key": "deciding", "label": "รอชำระเงิน", "icon": "💰", "detail": "รอจ่าย 39 (Deep)"},
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

- `status_reason`: `awaiting` → `slip_received` | `transfer_reported` | `floating`
  · `cancelled` → คีย์เหตุผลยกเลิก (`auto_expired` / `auto_expired_grace` / `user_cancelled` / `superseded_by_paid` / `package_switch` / … / `unknown`) หรือ `rejected_in_app` (แอป SMS Checker ปฏิเสธ)
  · `refunded` → `refund_flagged` (คืนเงินผ่าน API **ก่อน v3** — แถวเก่าเท่านั้น) | `approval_voided` (ยกเลิกการอนุมัติ — **ตั้งแต่ v3 API refund ก็ได้ค่านี้**) · อื่น ๆ → `null`
- `stage` = ขั้นในกรวยขายชุดเดียวกับ Warroom (`App\Support\FortuneFunnelStage`) — `key` / `label` / `icon` / `detail` (null ได้)
- บิลที่ถูก `readings/{id}/cancel` จะ **หายจากทุกรายการ** (endpoint นั้นทำ soft delete — พฤติกรรมเดิม)
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

`paid_today.revenue_thb` ใช้นิยามเดียวกับ `revenue_today.fortune` · `awaiting_amount_thb` = ผลรวม `amount_thb` ของรายการ `status=awaiting` (ยอดต่อใบตัวเดียวกัน)

### `GET fortune/bills/{id}/slip`

สตรีมรูปสลิป (image/jpeg) — แนบ Bearer token · `Cache-Control: private, no-store` · 404 ถ้าไม่มีรูป/ถูกลบตามรอบ 30 วัน

---

## 3. กล่องแชท / เทคโอเวอร์

### `GET takeover/conversations?status=&platform=&search=&page=&per_page=`

- `status`: `taken_over` (ค่าเริ่มต้น — บอทหยุดอยู่ตอนนี้) | `requested` (**(v3)** ลูกค้าขอคุยกับคนใน 24 ชม. ที่ยังไม่มีแอดมินลงมือ — ดู 8.2) | `active` (บทสนทนาที่ยังไม่จบ อัปเดตใน 7 วัน — ชุดเดียวกับหน้าเว็บ) | `all`

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
        "stage": {"key": "celtic", "label": "เลือกไพ่ · Celtic", "icon": "🃏", "detail": "ตอบไป 1 คำถาม · ถามต่อไหม"},
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
        "deferred": [
          {"reading_id": 15001, "bill_reference": "FTU-261006-K1234", "item": "celtic_start",
           "label": "กล่องเริ่มเปิดไพ่ Celtic หลังจ่าย 99", "at": "2026-10-06T13:45:00+07:00"}
        ],
        "updated_at": "2026-10-06T13:40:01+07:00"
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

- `last_message` มาจากบันทึกแชทสดวันนี้ (Redis — อ่านแค่ข้อความท้ายสุด `LINDEX -1`) ถ้าไม่มี → ข้อความล่าสุดที่แอดมินส่งผ่านแผง (log เทคโอเวอร์ **ที่มี `user_id`** — v3 แก้: เดิมหยิบ log คำขอของลูกค้า/log ระบบมาโชว์เป็นข้อความแอดมิน) → `null`
- `unread` = ข้อความล่าสุดเป็นของลูกค้า (= ลูกค้ากำลังรอคำตอบ) · `last_message.sender` อาจเป็น `system` (บันทึก "บอทงดส่ง") — ไม่นับว่ายังไม่อ่าน
- `deferred` (**ใหม่ 2026-10-06**) — ของที่ลูกค้าจ่ายแล้วแต่บอท**พักไว้**เพราะแอดมินเทคโอเวอร์อยู่ (มีค่าเฉพาะแถวที่เทคโอเวอร์อยู่ · ว่าง = `[]`)
  ดูรายการ `item` ที่ "เทคโอเวอร์ = บอทเงียบสนิท" ด้านล่าง

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
  - ระหว่างเทคโอเวอร์: ข้อความลูกค้า (รวมกดปุ่ม `[กดปุ่ม] …` / รูป `📷 [ลูกค้าส่งรูป — เก็บไว้แล้ว]` / สติกเกอร์) ถูกจดเป็น `customer` ทันที
    และสิ่งที่บอท "เกือบจะส่ง" ถูกจดเป็น `system` ข้อความขึ้นต้น `🤫 บอทงดส่ง (แอดมินคุยอยู่)` · จ่ายเงินระหว่างเทคโอเวอร์ = `system` `💰 ลูกค้าจ่ายแล้วระหว่างเทคโอเวอร์ — …`
- `source`: `redis` = แชทสดวันนี้ (ครบทุกข้อความ) · `structured` = ประกอบจากข้อมูลที่เก็บถาวร (คำถาม/ไพ่/คำทำนาย/ข้อความแอดมิน) — ตรรกะเดียวกับ `fortune/readings/{id}/transcript`

### `POST chat/extend` (ใหม่)

body `{ "reading_id": 15001, "minutes": 15 }` (1–1440)

- ยังเทคโอเวอร์อยู่ → `FortuneTakeoverService::extend()` ตัวเดียวกับปุ่มต่อเวลาบนเว็บ (เวลาสิ้นสุดเดิม + minutes)
- หมดเวลาไปแล้ว → เริ่มเทคโอเวอร์ใหม่ = ตอนนี้ + minutes (แบบเดียวกับ `chat/takeover` — ทำงานแม้ปิดระบบส่งต่อแอดมินอัตโนมัติ)

```json
{"success": true, "data": {"reading_id": 15001, "is_takeover": true, "minutes_added": 15,
  "until": "2026-10-06T14:25:00+07:00", "remaining_minutes": 25}, "message": "ต่อเวลาอีก 15 นาที"}
```

ทำไม่สำเร็จ (บริการไม่ได้เทคโอเวอร์ให้) → **409**
```json
{"success": false, "error_code": "TAKEOVER_NOT_ACTIVE", "message": "ต่อเวลาไม่สำเร็จ — ...",
 "data": {"reading_id": 15001, "is_takeover": false, "minutes_added": 0, "until": null, "remaining_minutes": 0}}
```

### เทคโอเวอร์ = บอทเงียบสนิท (เจ้าของสั่ง 2026-10-06)

> "ถ้าเทคโอเวอร์คือแอดมินคุยแล้ว บอทต้องหยุดแทรกก่อน"

- ระหว่างเทคโอเวอร์ บอท**ไม่ส่งอะไรเลย**หาลูกค้าคนนั้น (ทุกบิลของคนนั้น): ข้อความ, กล่องกำลังพิมพ์, สติกเกอร์, รีแอคชัน, ทวงบิล, เตือน, ping, follow-up, คำทำนาย
  — ยกเลิกพฤติกรรมเดิม (2026-05-17) ที่ปล่อยปุ่ม / flow จ่ายเงิน / ข้อความ "39" "99" ผ่าน
- ข้อความลูกค้าที่เข้ามา: จดลงแชท (`takeover/{reading}/messages`) + park เป็นบริบทของบิลที่จ่ายแล้ว + รูปถูกเก็บถาวร (อาจเป็นสลิป) — บอทไม่ตอบ ไม่เรียก AI
- เงินเข้า (SMS / Stripe / mark-paid / force-approve) ระหว่างเทคโอเวอร์: **ตัดบิล/คอมมิชชั่น/สถานะเหมือนเดิมทุกอย่าง** แต่กล่องที่จะส่งหาลูกค้าถูกพักไว้
  + แจ้งแอดมินผ่านช่องแจ้งเตือนแอดมิน (Telegram/LINE แอดมิน — ไม่ใช่โควตาลูกค้า)
- ปิดสวิตช์ `admin_handover_enabled` = ปิดเฉพาะ **เทคโอเวอร์อัตโนมัติ** (ลูกค้าขอคุยกับคน) · ที่แอดมินกดเอง/พิมพ์ `/aistop` ทำงานเสมอ
- จบเทคโอเวอร์ (`chat/resume`, หมดเวลา, หรือลูกค้าทักมาหลังหมดเวลา) → บอทส่งของที่พัก **ครั้งเดียว ตามลำดับ** แล้วกลับมาทำงานปกติ
  ไม่ตอบข้อความที่ลูกค้าพิมพ์ระหว่างเทคโอเวอร์ซ้ำ (แอดมินคุยไปแล้ว)

`item` ของ `deferred` (เรียงตามลำดับที่ส่ง):

| item | ความหมาย | ตอนจบเทคโอเวอร์ (`deliver_deferred=true`) | `deliver_deferred=false` |
|---|---|---|---|
| `deep_reading` | คำทำนาย Deep 39 ที่ AI ทำเสร็จแล้ว | ส่งคำทำนายเต็ม (`fortune:process-deep` — ไม่เรียก AI ซ้ำ) | ตั้งธงว่าส่งแล้ว |
| `payfirst_birthdate` | กล่องขอวันเกิด/ตั้งจิตหลังจ่าย 39 | ส่งกล่องเดิม (ถ้าสถานะบิลยังไม่เปลี่ยน) | ไม่ส่ง |
| `celtic_start` | กล่องตัดบิล + เริ่มเปิดไพ่ Celtic 99 | ส่งกล่องเดิม (ถ้าสถานะบิลยังไม่เปลี่ยน) | ไม่ส่ง |
| `slip_result` | ผลตรวจสลิป SlipOK | ส่งกล่องเดิม (ถ้าสถานะบิลยังไม่เปลี่ยน) | ไม่ส่ง |
| `celtic_resume` | Celtic ค้างกลางทาง แล้วลูกค้าพยายามคุย | กล่อง "แม่หมอกลับมาแล้ว เปิดไพ่/ถามต่อได้เลย" | ไม่ส่ง |
| `celtic_answers` | คำตอบ Celtic ที่ตอบแล้วแต่ยังไม่ถึงลูกค้า | cron `fortune:celtic-redeliver` ส่งรอบถัดไป (ภายใน 2 ชม. หลังตอบ) | ตั้งว่าส่งแล้ว |
| `celtic_summary` | บทสรุป Celtic ที่ยังไม่ถึงลูกค้า | ส่งบทสรุป | ตั้งว่าส่งแล้ว |
| `bubbles` | คำทำนายแบบบับเบิ้ลที่ส่งค้างครึ่งทาง | cron `fortune:bubble-recover` ส่งต่อ | ล้างคิวบับเบิ้ล |
| `voice_summary` | สรุปเสียงคำทำนาย | ทำ/ส่งสรุปเสียง | ไม่ส่ง |

### `POST chat/resume`

body `{ "reading_id": 15001, "deliver_deferred": true }` (`deliver_deferred` ไม่ส่ง = `true`)

```json
{"success": true, "data": {"reading_id": 15001, "is_takeover": false, "deliver_deferred": true,
  "deferred": [{"reading_id": 15001, "bill_reference": "FTU-261006-K1234", "item": "celtic_start", "label": "...", "at": "..."}]},
 "message": "bot resumed"}
```

- `deferred` = รายการที่พักไว้ ณ ตอนกดคืนงาน — ถูกส่ง (เข้าคิว ไม่ถ่วง request) หรือถูกล้าง ตาม `deliver_deferred`
- ลูกค้ายังถูกเทคโอเวอร์ด้วยบิลอื่นอยู่ → ยังไม่ส่ง (รอเทคโอเวอร์ตัวสุดท้ายจบ)

### `GET chat/takeover-status?reading_id=`

```json
{"success": true, "data": {"reading_id": 15001, "is_takeover": true, "until": "...", "remaining_minutes": 12,
  "deferred": [ ... ]}}
```

- `deferred` (**ใหม่**) — รูปเดียวกับ `takeover/conversations`

### พฤติกรรมของ `POST chat/send`

- Facebook: `FacebookWebhookService::sendMessage()` แบบ `RESPONSE` — ส่งได้เฉพาะลูกค้าที่ทักมาภายใน 24 ชม.
  (หน้าเว็บส่ง `message_tag = HUMAN_AGENT` แต่ `MESSAGE_TAG_USABLE = false` ตั้งแต่ 2026-08-13 เพราะ Meta ยกเลิกแท็ก ⇒ ผลเท่ากัน)
- LINE: `LineFortuneService::sendMessage()` = **push** (กินโควตา 300/เดือน) — ไม่มี reply token ให้ใช้ เพราะแอดมินพิมพ์หลังเวลาที่ token ใช้ได้
  (หน้าเว็บก็ลงเอยที่ push เหมือนกัน) · ถ้าโควตาหมด Gatekeeper จะไม่ยิงและตอบ `502 platform service rejected`
- Telegram: `TelegramFortuneService::sendMessage()`
- **(2026-10-06) เทคโอเวอร์ให้อัตโนมัติ** — ยังไม่ได้เทคโอเวอร์ = เริ่ม (30 นาที, ทำงานแม้ปิดสวิตช์อัตโนมัติ) · เทคโอเวอร์อยู่แต่เหลือไม่ถึง 30 นาที = ต่อให้เหลือ 30
  (ไม่ส่ง `reading_id` = ใช้บิลล่าสุดของลูกค้าคนนั้น) · ข้อความแอดมินส่งผ่านด่านเทคโอเวอร์ได้เสมอ
- ผลลัพธ์เพิ่ม `data.reading_id`, `data.is_takeover`, `data.takeover_until`, `data.remaining_minutes`
- ส่งไม่ได้เพราะแพลตฟอร์มปฏิเสธ → `502` `{success:false, data:{delivered:false,...}, message:"platform service rejected"}`
  · เกิด exception → `500` `message: "send failed: ..."` (ข้อความผ่าน `SafeLog::exceptionMessage()` แล้ว — token ใน URL ถูกปิด; `takeover`/`resume` ก็เช่นกัน)

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

(ข้อ 3 `escalated_24h` เพิ่มใน v3 — ดู 8.4)

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

---

## 8. v3 — สิ่งที่เพิ่ม/แก้ (branch `feat/admin-app-api-v3`)

ทุกข้อ **เพิ่มอย่างเดียว** (Warroom Juntra ใช้รูปเดิมได้ต่อ) ยกเว้นที่ติดป้าย **แก้พฤติกรรม**

| # | เรื่อง | ประเภท |
|---|---|---|
| 8.2 | คิว "ลูกค้าขอคุยกับคน" อ่านจาก log (เดิมว่างตลอด) — `ops/summary.queue.customer_requests` · `takeover/conversations?status=requested` · `takeover/stats.requested` | แก้พฤติกรรม + ฟิลด์ใหม่ |
| 8.2 | `chat/send` บันทึกข้อความแอดมินลง log เทคโอเวอร์ (แบบแผงเว็บ) | เพิ่ม (side effect) |
| 8.3 | `ops/summary.queue.withdrawals_approved` (อนุมัติแล้วรอโอน) | เพิ่ม |
| 8.4 | บิลค้างเกิน 24 ชม. (`stuck_reason = escalated_24h`) ใน `fortune/active-readings` + `ops/summary.queue.stuck_readings` | เพิ่ม |
| 8.5 | `POST fortune/readings/{id}/retry` ทำนายซ้ำบิลค้าง | ใหม่ |
| 8.6 | `POST fortune/readings/{id}/refund` ถอยครบผ่าน `voidApproval()` · (v3.1) 409 `GENERATION_IN_FLIGHT` / `REFUND_CONFIRM_REQUIRED` + `confirm` · message ตามจริง | แก้พฤติกรรม (รูปคำตอบเดิม) |
| 8.7 | `POST fortune/readings/{id}/mark-paid` ยอดจริงของบิล (เลิก 49 ตายตัว) · (v3.1) 409 `BUSY` กันกดซ้ำ | แก้พฤติกรรม (รูปคำตอบเดิม + 422/409 ใหม่) |
| 8.8 | `POST finance/withdrawals/{id}/complete` — เขียนสัญญาให้ครบ (โค้ดไม่ได้แก้) | เอกสาร |
| 8.9 | แจ้งเตือนแอปแอดมิน: `POST/DELETE devices/push-token` + คำสั่ง `admin-app:push-alerts` | ใหม่ |

### 8.2 คิว "ลูกค้าขอคุยกับคน" (แก้พฤติกรรม)

**ทำไมเดิมว่าง:** ตั้งแต่ 2026-05-17 webhook ทั้ง 3 ช่องทาง (`handleCustomerHandoffRequest` ของ LINE / FB / Telegram)
เป็นโหมด "แจ้งแอดมินอย่างเดียว" — เขียน `fortune_takeover_logs` (`action = message` · `reason = customer_request` · `user_id = null`
· `message = "🙋 ลูกค้าขอคุยกับคน: <ข้อความลูกค้า>"`) แล้วบอกลูกค้าให้รอ **ไม่เทคโอเวอร์ให้** ⇒ นับจาก `admin_takeover_reason` ได้ 0 เสมอ
(webhook ไม่ได้แก้)

**นิยามใหม่** (`App\Services\AdminApp\CustomerRequestQueue` — ที่เดียวทั้ง 3 endpoint):

- คำขอ = log ที่ `reason = customer_request` และ `action` ∈ `message` (ปัจจุบัน) / `takeover` (แถวเก่าก่อน 2026-05-17) ภายใน **24 ชม.**
- **ยังไม่มีใครรับ** = ไม่มี log ของบิลเดียวกันที่ใหม่กว่า (id มากกว่า) และแปลว่าแอดมินลงมือแล้ว:
  - `message` ที่มี `user_id` (แอดมินส่งผ่านแผงเว็บ / แอปผ่าน `chat/send`)
  - `takeover` ที่ reason ≠ `customer_request` (กดเทคโอเวอร์ · `auto_reply` = แอดมินพิมพ์ใน Page Inbox ของ FB)
  - `extend` / `resume`
  - (`auto_expire` และ log ระบบที่ `user_id = null` **ไม่นับ**)
- บิลที่ถูกลบ (`readings/{id}/cancel`) ไม่นับ · นับเป็น **จำนวนบิล** ไม่ใช่จำนวนข้อความ
- ⚠️ แอดมินที่ตอบใน LINE OA Chat ระบบมองไม่เห็น — คำขอจะค้างจนครบ 24 ชม. หรือจนแอดมินกดอะไรสักอย่างจากแอป/เว็บ (ส่งข้อความ/เทคโอเวอร์/คืนงาน)

`ops/summary.queue.customer_requests` (รูปเดิม + ฟิลด์ใหม่):

```json
{
  "count": 2,
  "oldest_minutes": 30,
  "preview": [
    {"reading_id": 15001, "customer_name": "สมหญิง", "platform": "line",
     "keyword": "ขอคุยกับคนจริงค่ะ", "requested_at": "2026-10-06T13:30:00+07:00", "remaining_minutes": 0,
     "waiting_minutes": 30, "request_count": 2, "last_requested_at": "2026-10-06T13:55:00+07:00", "is_taken_over": false}
  ]
}
```

- `requested_at` = คำขอแรกที่ยังไม่มีใครรับ · `keyword` = ข้อความของคำขอล่าสุด (ตัดหัว "🙋 ลูกค้าขอคุยกับคน:" แล้ว)
- `remaining_minutes` = นาทีเทคโอเวอร์ที่เหลือ (**0 = บอทยังตอบเองอยู่** — ปกติของโหมดแจ้งแอดมินอย่างเดียว)
- ใหม่: `waiting_minutes` · `request_count` (ลูกค้าขอกี่ครั้ง) · `last_requested_at` · `is_taken_over`
- ตัวอย่างเรียง **รอนานสุดก่อน**

`takeover/conversations?status=requested`:
- รายการ = บิลตามนิยามข้างบน เรียง **คำขอแรกเก่าสุดก่อน** (สถานะอื่นยังเรียง `updated_at` ใหม่สุดก่อนเหมือนเดิม) · `platform` / `search` ใช้ได้ตามเดิม
- ฟิลด์ของทุกแถว (ทุก status): `requested_by_customer` = มีคำขอที่ยังไม่มีใครรับ (หรือเทคโอเวอร์แบบเก่าจากคำขอลูกค้า)
  · `request_keyword` · ใหม่ `requested_at` (null ถ้าไม่มี) · `request_count` (0 ถ้าไม่มี)

`takeover/stats.requested` = จำนวนบิลตามนิยามเดียวกัน

`chat/send` (แก้เพิ่ม): ส่งสำเร็จ → บันทึก `fortune_takeover_logs` (`action = message`, `user_id = แอดมิน`)
ด้วย `FortuneTakeoverService::logMessage()` ตัวเดียวกับแผงเว็บ ⇒ คำขอหลุดจากคิว + โผล่ในประวัติเทคโอเวอร์บนเว็บ
- บันทึกกับ: บิลจาก `reading_id` (ถ้ามี) **+ ทุกบิลของลูกค้าคนเดียวกัน (platform + platform user id) ที่มีคำขอค้างอยู่**
  — คำขอถูกบันทึกกับ "บิลล่าสุดของลูกค้าตอนขอ" ซึ่งอาจเป็นคนละใบกับที่แอดมินเปิด หรือแอดมินส่งแบบ `platform` + `platform_user_id` ไม่มี `reading_id`
  · ไม่มีทั้งสองอย่าง → บิลล่าสุดของลูกค้า
- รูปคำตอบเดิม · ส่งไม่สำเร็จ = ไม่บันทึก · บันทึกพลาดไม่กระทบผลส่ง

### 8.3 `ops/summary.queue.withdrawals_approved` (ใหม่)

```json
"withdrawals_approved": {
  "count": 1, "amount_thb": 300.0, "net_amount_thb": 290.0, "oldest_minutes": 45,
  "preview": [{"id": 89, "user_name": "นายบี", "amount_thb": 300.0, "net_amount_thb": 290.0,
               "approved_at": "2026-10-06T13:15:00+07:00", "created_at": "..."}]
}
```

- = `withdrawal_requests.status = approved` (อนุมัติแล้ว ยังไม่ได้กด `finance/withdrawals/{id}/complete`)
- `amount_thb` = ผลรวม `amount` (นิยามเดียวกับ `withdrawals_pending`) · `net_amount_thb` = ผลรวมยอดที่ต้องโอนจริง
- `oldest_minutes` นับจาก `approved_at` (แถวเก่าที่ไม่มี `approved_at` ใช้ `created_at`) · ตัวอย่างเรียงอนุมัติเก่าสุดก่อน
- พังแล้วได้ `null` + ชื่อ `withdrawals_approved` ใน `degraded` เหมือนกล่องอื่น · ไม่อยู่ในรายการแจ้งเตือน push (8.9)

### 8.4 บิลค้างเกิน 24 ชม. — `stuck_reason = "escalated_24h"`

`fortune:expire-stuck-paid` (ทุก 6 ชม.) ปักธง `conversation_state.admin_review_needed = true` ให้บิลที่ **จ่ายเกิน 24 ชม. แล้วยังไม่ได้คำทำนาย**
(ระบบอัตโนมัติยอมแพ้ ต้องคนกู้) — เดิมบิลพวกนี้หลุดจากทั้ง `active-readings` และ `ops/summary` เพราะหน้าต่าง 24 ชม.

นิยาม (`StuckReadingFinder::isEscalated` / SQL คู่กัน): จ่ายแล้ว · ไม่ใช่บิลจันทรา · มีธง `admin_review_needed`
· จ่ายมาไม่เกิน 30 วัน (ค่าเดียวกับ `--max-age-days` ของตัวปักธง) · และ **ยังไม่เสร็จจริง**:
- Deep: สถานะ `paid` / `collecting_birthdate` / `collecting_questions` / `collecting_tarot` หรือ `completed` แต่ `deep_response` ว่าง
- Celtic: สถานะไม่ใช่ `completed`

(ธงนี้ไม่มีใครล้างนอกจาก `voidApproval()` — บิลที่กู้สำเร็จแล้วหลุดจากคิวเองเพราะเช็ค "ยังไม่เสร็จ" ทุกครั้ง)
งานที่ยังวิ่งอยู่จริง (ธง `fortune:deep_gen` / `deep_deliver` / Celtic in-flight) ไม่นับว่าค้าง ·
`minutes_since_activity` / `oldest_minutes` ของบิลกลุ่มนี้นับจาก **เวลาจ่าย** · สถานะที่รอลูกค้าตอบ **นับว่าค้าง** ในข้อนี้ (เกิน 24 ชม. แล้ว)
· (v3.1) SQL ของ `active-readings` / `stuck_readings` มีขอบบนสุด `created_at >= now - 31 วัน` ให้ใช้ index `(is_paid, created_at)`
(เดิม OR ข้ามกิ่งทำให้สแกนบิลจ่ายแล้วทั้งตาราง + `JSON_EXTRACT` ทุกแถว ทุกรอบของ `admin-app:push-alerts`)

### 8.5 `POST fortune/readings/{id}/retry` — ทำนายซ้ำบิลค้าง (ใหม่)

ไม่มี body · เซิร์ฟเวอร์เลือกวิธีกู้เอง (`App\Services\AdminApp\StuckReadingRetrier`) — ทุกวิธีลอกจากปุ่มบนหน้าเว็บที่ใช้อยู่จริง

**ด่าน (ตรวจตามลำดับ)**

| ไม่ผ่าน | HTTP | `error_code` | `message` |
|---|---|---|---|
| ยังไม่จ่าย | 422 | `NOT_PAID` | บิลนี้ยังไม่ได้ชำระเงิน — สั่งทำนายซ้ำไม่ได้ |
| บิลจันทรา | 422 | `JUNTRA_BILL` | บิลจากเว็บจันทรา — จันทราเป็นผู้ส่งคำทำนายเอง สั่งซ้ำจากที่นี่ไม่ได้ |
| แอดมินเทคโอเวอร์อยู่ | 409 | `ADMIN_TAKEOVER_ACTIVE` | แอดมินคุมห้องนี้อยู่ — คืนให้บอทก่อนสั่งทำนายซ้ำ |
| งาน AI/ส่งยังวิ่งอยู่ (`StuckReadingFinder::generationInFlight`) | 409 | `GENERATION_IN_FLIGHT` | ระบบกำลังสร้าง/ส่งคำทำนายบิลนี้อยู่ — รอสักครู่แล้วค่อยเช็คใหม่ |
| ไม่ค้าง (ไม่มี `stuck_reason` และไม่มีธง `admin_review_needed`) | 409 | `NOT_STUCK` | บิลนี้ไม่ได้ค้าง — ระบบยังทำงานตามปกติ ไม่ต้องสั่งซ้ำ |
| ไม่มีไอดีลูกค้า | 422 | `NO_RECIPIENT` | ไม่พบไอดีลูกค้าของบิลนี้ — ส่งข้อความหาลูกค้าไม่ได้ |
| **(v3.1)** ลูกค้าคนเดียวกัน (`facebook_user_id` / `platform_user_id` — ตัวตนชุดเดียวกับ `cancelCompetingPrePaymentReadings`) มี **บิลใหม่กว่า** ที่จ่ายแล้ว หรือยังเป็นบทสนทนาที่เปิดอยู่ (`scopeActiveConversation`) — ธง `admin_review_*` คงไว้ | 409 | `NEWER_ACTIVE_BILL` | ลูกค้ามีบิลใหม่กว่า (FTU-…) ที่จ่ายแล้วหรือกำลังคุยอยู่ — ห้ามปลุกบิลนี้ซ้อน ให้คืนเงินหรือปิดบิลนี้แทนการทำนายซ้ำ |
| Celtic จบแล้ว | 409 | `ALREADY_COMPLETED` | บิล Celtic นี้ทำนายจบแล้ว — ไม่มีอะไรให้กู้ |
| Deep ส่งถึงลูกค้าแล้ว | 409 | `ALREADY_DELIVERED` | คำทำนายบิลนี้ส่งถึงลูกค้าแล้ว — ไม่ส่งซ้ำ (เปิดดูในแชทก่อน) |
| **(v3.1)** Deep ที่ลูกค้ายังกรอกไม่จบ (`collecting_questions` / `collecting_tarot`) หรือไม่มีคำถามเลย (ด่านเดียวกับ `fortune:check-pending`) | 409 | `AWAITING_CUSTOMER` | ลูกค้ายังให้ข้อมูลไม่ครบ (คำถาม/เปิดไพ่) — สร้างคำทำนายตอนนี้ไม่ได้ แนะนำให้เทคโอเวอร์แชทคุยกับลูกค้าแทน |
| แพคเกจอื่น (basic/free_card) | 422 | `UNSUPPORTED_PACKAGE` | บิลแพคเกจนี้ไม่มีขั้นตอนทำนายซ้ำอัตโนมัติ — จัดการจากหน้าเว็บ |
| กดซ้ำภายใน 2 นาที (`Cache::add("admin_retry:{id}", 120)`) | 429 | `RETRY_COOLDOWN` | เพิ่งสั่งทำนายซ้ำบิลนี้ไปแล้ว — รอ 2 นาทีก่อนสั่งอีกครั้ง |

**วิธีกู้ (`action`)**

| action | เมื่อไร | ทำอะไร (ลอกจาก) |
|---|---|---|
| `resend` | Deep ที่มีคำทำนายแล้วแต่ `reading_sent_directly` ยังไม่ตั้ง (**เช็คก่อนวันเกิด** — v3.1) | ส่งคำทำนายเดิมซ้ำ (`resendDeepReading`) + ถือล็อก `fortune:deep_deliver:{id}` + ส่งสำเร็จ = ตั้งธงส่งแล้ว + `completed` (กัน `fortune:check-pending` ส่งซ้ำ) · ส่งไม่ออก/โยน error = ปล่อยล็อกใน `finally` |
| `recover_pay_first` | Deep ที่ยังไม่มีวันเกิด (และยังไม่มีคำทำนาย) | `fortune:recover-paid-no-birthdate --id={id} --force` (ปุ่ม "🛟 ส่งขอวันเกิดใหม่" — `recoverPayFirstReading`) — ขอวันเกิดใหม่ / ใช้วันเกิดเดิมถ้ามีในประวัติ |
| `regenerate` | Deep ที่มีวันเกิด + คำถาม และไม่ได้รอลูกค้ากรอก | ล้างคำทำนาย/ธงส่ง → `paid` + รีเซ็ต `auto_retry_count = 0` · `failure_notified = false` แล้วสั่ง `ProcessDeepFortuneReadingJob::dispatchSmart` **หลังส่งคำตอบ** (`register_shutdown_function` แบบปุ่มเว็บ `retryDeepReading`) |
| `celtic_recover` | Celtic ที่ยังไม่จบ (ยกเว้นข้อถัดไป) | เส้นบิลเดี่ยวของ Emergency Recovery (`FortuneCelticCrossController::emergencyRecoverAction`): สถานะ `new` / `celtic_pending_payment` / **`paid` (v3.1)** + ยังไม่เปิดไพ่ → ยืนยันการจ่ายใหม่ + พรอมต์ไพ่ใบแรก · `paid` ที่เปิดไพ่ไปบ้างแล้ว → กลับเป็น `celtic_picking` · อื่น ๆ → ส่งข้อความกู้ ณ จุดเดิม (`buildCelticResumeResponse`) หัวข้อ "ขออภัยที่ทำให้รอ" |
| `celtic_regenerate` **(v3.1)** | Celtic ค้าง `celtic_generating` (หรือ `paid` ที่เปิดไพ่ครบ 10 ใบแล้ว) | แบบ `recoverStuckGenerating()` ของ `fortune:celtic-redeliver`: → `celtic_awaiting_question` + `conversation_state.celtic_regen_pending = "1"` ให้ cron ปั่นคำตอบที่ค้างแล้วส่งผ่านเส้น redeliver เอง · **ไม่ส่งข้อความหาลูกค้า (ไม่ push LINE)** · `delivered = false` · `message` บอกว่าเข้าคิวแล้ว (หรือ "ปลดสถานะค้างแล้ว — ไม่มีคำถามค้างให้ปั่น/หมดเวลาคุย") |

ทุกวิธีตั้ง `conversation_state.admin_retry_at` / `admin_retry_by` และ **ล้าง `admin_review_alerted`**
(ธงนี้ทำให้บอทไม่นับบิลเป็นบทสนทนาที่ยังเปิด — ถ้าไม่ล้าง ลูกค้าตอบวันเกิด/เลือกไพ่ต่อแล้วข้อความไม่ไหลเข้าบิลที่จ่ายแล้ว
· ถ้ายังค้างอีก `fortune:expire-stuck-paid` จะปักธง + แจ้งแอดมินใหม่เอง) — ยกเว้นถูกปฏิเสธด้วย `NEWER_ACTIVE_BILL` (ไม่แตะธง) ·
ส่งข้อความทาง LINE = push (คำทำนาย/บริการที่จ่ายแล้ว — หมวดที่อนุญาตให้ใช้โควตา)

**คำตอบ**

```json
{"success": true, "action": "regenerate",
 "message": "เริ่มสร้างคำทำนายใหม่แล้ว — ระบบจะส่งให้ลูกค้าเองเมื่อเสร็จ (ประมาณ 1–2 นาที)",
 "data": {"reading_id": 15020, "action": "regenerate", "delivered": null,
          "stuck_reason": "deep_job_failed", "conversation_status": "paid"}}
```

- `delivered`: `true` ส่งถึงแล้ว · `false` ส่งไม่ออก · `null` ยังไม่รู้ (งานวิ่งเบื้องหลัง)
- ส่งไม่ออก (resend / celtic_recover) → **502** `success: false` + `message` บอกสาเหตุ (เช่น FB เกิน 24 ชม.) — สถานะบิลที่กู้แล้วคงไว้
- เกิด exception → **500** `error_code: RETRY_FAILED` (ปลดล็อก 2 นาทีให้กดใหม่ได้ทันที)
- ด่านไม่ผ่าน → `{"success": false, "error_code": "...", "action": null, "message": "...", "data": {"reading_id": ..., "action": null, ...}}`
- `stuck_reason`: `ai_generating_timeout` | `deep_job_failed` | `escalated_24h` | `null` (มีแค่ธงแต่ไม่ค้างตามนิยาม)

### 8.6 `POST fortune/readings/{id}/refund` (แก้พฤติกรรม — รูปคำตอบเดิม)

เดิมพลิก `is_paid = false` อย่างเดียว ⇒ ยอดทศนิยม (UPA) ค้าง `used` · SMS ยังผูกบิลนี้ · ค่าแนะนำไม่ถูกดึงคืน
ตอนนี้ใช้ `FortuneReading::voidApproval()` ตัวเดียวกับปุ่ม void บนเว็บ (`Admin\FortuneBillingController::void`):

- คืน UPA → `cancelled` · ปลด SMS (`matched_transaction_id = null`, `status = pending`) · ดึงค่าแนะนำที่จ่ายแล้วคืนจากกระเป๋า (commission → `rejected`)
- บิล → `is_paid = 0`, `paid_at = null`, `amount_received = null`, `conversation_status = completed`,
  `conversation_state.approval_voided = true` (+ `approval_void_reason` = "คืนเงินจากแอปแอดมิน: <reason>", `approval_voided_by_admin_id`)
- ในรายการบิลอยู่กอง `refunded` ด้วย `status_reason = approval_voided` (ป้าย "ยกเลิกการอนุมัติแล้ว")
- ไม่โอนเงินคืนจริง และไม่แจ้งลูกค้า (แอดมินโอนคืนเองนอกระบบ) — **แอปควรถามยืนยันก่อนเรียก**

Body: `{reason?: string, confirm?: bool}` — `confirm` ใหม่ใน v3.1 (ไม่ส่ง = false)

**ด่าน (v3.1)**

| ไม่ผ่าน | HTTP | `error_code` | `message` |
|---|---|---|---|
| งาน AI/ส่งของบิลนี้ยังวิ่งอยู่ (`fortune:deep_gen` / `deep_deliver` / Celtic in-flight) — คืนตอนนี้ job จะเขียนธงชุดเก่าทับ + ส่งคำทำนายให้คนที่คืนเงินแล้ว | 409 | `GENERATION_IN_FLIGHT` | ระบบกำลังสร้าง/ส่งคำทำนายบิลนี้อยู่ — รอให้เสร็จก่อนแล้วค่อยคืนเงิน |
| ลูกค้ายังใช้บริการอยู่ (`StuckReadingFinder::IN_PROGRESS_STATUSES`: `paid` · `collecting_*` · `celtic_picking` · `celtic_awaiting_question` · `celtic_generating` · `celtic_qa_prompt`) และไม่ได้ส่ง `confirm: true` | 409 | `REFUND_CONFIRM_REQUIRED` | ลูกค้ายังใช้บริการบิลนี้อยู่ — คืนเงินแล้วจะดึงค่าแนะนำคืนจากผู้แนะนำ และปิดเซสชันทันที (บอทหยุดคุยบิลนี้) ถ้าแน่ใจให้กดยืนยันอีกครั้ง |

บิลที่จบแล้ว/ไม่ขยับ (`completed` ฯลฯ) คืนได้เลยโดยไม่ต้องส่ง `confirm` (ปุ่มเดิมของ Warroom ใช้ได้ตามเดิม)

คำตอบสำเร็จรูปเดิม: `{success: true, data: FortuneReadingResource, message}` — **message เปลี่ยนให้ตรงความจริง (v3.1)**:
"คืนเงินแล้ว — ยกเลิกการจ่ายของบิลนี้ทันทีและปิดบิล · ดึงค่าแนะนำคืน N รายการ (ระบบไม่ได้โอนเงินคืนให้ลูกค้า — โอนคืนเองนอกระบบ)"
(เดิม "ส่งเข้าคิวคืนเงินแล้ว" — ไม่มีคิว ทำทันที · ถ้าดึงค่าแนะนำบางแถวไม่สำเร็จ ต่อท้าย message ด้วย `⚠️ ...` ให้แอดมินแก้มือ)
· ยังไม่จ่าย / void แล้ว / จันทรา → **422** `{success: false, message}` · ฐานข้อมูลยุ่ง (deadlock) → **503** "ลองใหม่อีกครั้ง" (ยังไม่มีอะไรถูกเปลี่ยน)

### 8.7 `POST fortune/readings/{id}/mark-paid` (แก้พฤติกรรม)

ไม่ส่ง `amount` → บันทึก **ยอดจริงของบิล** ลำดับเดียวกับ `amount_thb` ในรายการบิล:
1. `amount_paid` ถ้า > 0
2. ยอดทศนิยมจาก UPA (`unique_payment_amounts.unique_amount` — ยอดที่ลูกค้าเห็นใน QR เช่น 39.42)
3. ราคาแพคเกจในตั้งค่าแม่หมอ: Deep = `deep_reading_price` · Celtic = `celtic_cross_price` · basic = `reading_price`
4. หาไม่ได้เลย (เช่นไพ่ฟรี) → **422** `{"success": false, "error_code": "AMOUNT_REQUIRED", "message": "บิลนี้ไม่มียอดให้ดึงอัตโนมัติ — กรุณาระบุยอดที่ได้รับ (amount)"}`

~~เดิม: `amount_paid = 0` → บันทึก 49 ตายตัว~~ · ส่ง `amount` มา = ใช้ค่านั้นเสมอ (เหมือนเดิม) · คำตอบสำเร็จรูปเดิม

**กันกดซ้ำ (v3.1):** ถือ `Cache::lock("admin-app:mark-paid:{id}", 30)` แบบไม่รอ — อีกคำขอถือล็อกอยู่ →
**409** `{"success": false, "error_code": "BUSY", "message": "บิลนี้กำลังถูกมาร์คจ่ายจากอีกเครื่อง — รอสักครู่แล้วรีเฟรช"}`
· ได้ล็อกแล้วอ่านแถวใหม่ก่อนตัดสิน (คำขอที่สองหลังจ่ายแล้ว = 422 "บิลนี้ถูกมาร์คจ่ายแล้ว") · ไม่ล็อกแถว DB ข้ามการส่งข้อความหาลูกค้า

### 8.8 `POST finance/withdrawals/{id}/complete` — สัญญาเต็ม (โค้ดเดิม ไม่ได้แก้)

บันทึกว่าโอนเงินให้คำขอถอนที่ **อนุมัติแล้ว** (`status = approved`) เรียบร้อย → `status = completed` + แจ้งผู้ใช้

- `Content-Type: multipart/form-data`
  - `transfer_slip` — ไม่บังคับ · ไฟล์รูป (กฎ `image` ของ Laravel) · ≤ 5 MB (`max:5120`) · เซิร์ฟเวอร์แปลงเป็น WebP เก็บที่ `withdrawal-slips/…`
  - `transfer_note` — ไม่บังคับ · ข้อความ ≤ 500 ตัวอักษร
- สิทธิ์: super admin หรือมีสิทธิ์ `approve_withdrawals` — ไม่งั้น **403** `{"success": false, "message": "ไม่มีสิทธิ์จัดการคำขอถอนเงิน", "error_code": "PERMISSION_DENIED"}`
- ข้อมูลผิด → **422** `{"success": false, "message": "ข้อมูลไม่ถูกต้อง", "errors": {...}}`
- สถานะไม่ใช่ `approved` (ยังไม่อนุมัติ / โอนไปแล้ว / ถูกปฏิเสธ) → **500** `{"success": false, "message": "คำขอถอนเงินนี้ยังไม่ได้รับการอนุมัติ"}`
  (โค้ดเดิมโยน Exception — แอปควรเช็ค `status` ก่อนเปิดปุ่ม และถือ 500 + ข้อความนี้เป็น "ทำไปแล้ว/ทำไม่ได้")
- สำเร็จ → **200** `{"success": true, "data": {"withdrawal": WithdrawalResource}, "message": "บันทึกการโอนเงินสำเร็จ"}`
  — `withdrawal.status = "completed"`, `transfer_slip` = path ในดิสก์ public (ไม่ใช่ URL เต็ม), `transfer_note`, `transfer_completed_at`
- ⚠️ ไม่มีล็อกกันกดซ้ำพร้อมกัน — แอปควรปิดปุ่มระหว่างส่ง

### 8.9 แจ้งเตือนแอปแอดมิน (FCM)

#### `POST devices/push-token`

```json
{"token": "<FCM registration token>", "platform": "android", "device_id": "pixel-7-abc", "app_version": "1.2.0"}
```

- `token` บังคับ 20–512 ตัวอักษร · `platform` บังคับ `android` | `ios` | `web` · `device_id` ≤ 191 · `app_version` ≤ 50
- 1 token = 1 แถว (unique): ลงทะเบียนซ้ำ = อัปเดตแถวเดิม (เครื่องเดิมล็อกอินแอดมินคนอื่น → แถวย้ายเจ้าของ)
  · `device_id` เดิมของแอดมินคนเดิมส่ง token ใหม่มา → token เก่าของเครื่องนั้นถูกลบ (ไม่ยิงซ้ำ 2 ชุด)
- ผูกกับ Sanctum token ที่ใช้เรียก (`access_token_id`) — ใช้ตอนออกจากระบบ
- คำตอบ (ไม่คืน token): `{"success": true, "data": {"registered": true, "platform": "android", "device_id": "pixel-7-abc", "app_version": "1.2.0", "updated_at": "..."}, "message": "ลงทะเบียนเครื่องรับแจ้งเตือนแล้ว"}`
- แอปควรเรียกทุกครั้งที่ล็อกอินสำเร็จ และเมื่อ FCM ออก token ใหม่ (`onTokenRefresh`)

#### `DELETE devices/push-token`

body `{"token": "..."}` — ถอนได้เฉพาะ token ของตัวเอง · `{"success": true, "data": {"removed": 1}, "message": "ยกเลิกการแจ้งเตือนของเครื่องนี้แล้ว"}` (ไม่พบ = `removed: 0`)

#### ออกจากระบบ

- `auth/logout` → ลบ push token ที่ลงทะเบียนด้วย Sanctum token นี้ (+ `push_token` หรือ `token` ใน body ถ้าแอปส่งมา)
- `auth/logout-all` → ลบ push token **ทุกเครื่อง** ของแอดมินคนนี้
- (รูปคำตอบของ logout เดิม)

#### คำสั่ง `admin-app:push-alerts` (scheduler **ทุก 2 นาที** (v3.1) · `withoutOverlapping(5)` · `onOneServer`)

1. ปิด FCM ในหลังบ้าน (Setting `fcm_enabled` — **สวิตช์เดียวกับแอป SMS Checker** · v3.1) / ไม่มีไฟล์ credentials Firebase
   (`storage/app/firebase-credentials.json` หรือ Setting `fcm_credentials_path`) / หา project ไม่ได้ → **จบเงียบ**
   · ตาราง `admin_push_tokens` ยังไม่มี (โค้ดขึ้นก่อน migrate) → จบเงียบ
2. **เครื่องที่ส่งได้ (v3.1)** = เจ้าของยังเป็นแอดมิน (`is_super_admin` หรือ `role = admin` · ยังไม่ถูกลบ — นิยามเดียวกับ `AdminApiMiddleware`)
   **และ** Sanctum token ที่ใช้ลงทะเบียน (`access_token_id`) ยังอยู่และยังไม่หมดอายุ — แถวอื่น **ถูกลบทุกรอบ** (ลดสิทธิ์/ถอน token แล้วต้องเลิกได้ยอดเงิน)
   · ไม่เหลือเครื่อง → ล้างสถานะแล้วจบ
3. นับกล่องคิวด้วย `OpsSummaryController::buildQueue()` (ตัวนับเดียวกับหน้าแรก ไม่ดึงตัวอย่าง)
   แล้วเทียบกับ **จำนวนที่แจ้งไปล่าสุดของกล่องนั้น** (cache `admin_app:push_alerts:notified` · snapshot ล่าสุดอยู่ที่ `admin_app:push_alerts:snapshot`)
   - รอบแรก = เก็บฐาน ไม่ส่ง · กล่องที่อ่านไม่ได้ (degraded) = ไม่ส่ง + คงค่ารอบก่อน
   - **กันกระพริบ (v3.1):** แจ้งเมื่อจำนวน **สูงกว่าจำนวนที่แจ้งไปล่าสุด** และห่างจากครั้งก่อนของกล่องนั้น **≥ 15 นาที**
     · จำนวนลงถึง 0 = รีเซ็ตจำนวนที่แจ้งเป็น 0 (คูลดาวน์ยังนับจากครั้งที่แจ้งจริง — ของใหม่ที่ยังค้างหลังพ้นคูลดาวน์จะถูกแจ้ง)
     · ลดลงแต่ไม่ถึง 0 แล้วขึ้นกลับมาไม่เกินค่าที่แจ้งล่าสุด = ไม่แจ้ง (ยอมพลาดบ้างแลกกับไม่สแปม)
4. ส่งหาทุกเครื่อง — FCM ตอบ `UNREGISTERED` / `SENDER_ID_MISMATCH` / `INVALID_ARGUMENT` ที่ระบุว่าผิดที่ token → **ลบแถวนั้น**
   (INVALID_ARGUMENT ทั่วไปไม่ลบ — อาจเป็นเพราะ payload) · ไม่ log token

| `data.type` | `data.route` | title | body (ตัวอย่าง) |
|---|---|---|---|
| `customer_requests` | `/chat` | 🙋 ลูกค้าขอคุยกับแอดมิน | มีคำขอใหม่ 1 ราย — รอแอดมินตอบทั้งหมด 2 ราย |
| `bills_awaiting` | `/work?tab=bills` | 🧾 บิลรอตรวจการโอน | ลูกค้าแจ้งโอน/ส่งสลิปใหม่ — รอตรวจ 3 บิล (237.84 บาท) |
| `withdrawals_pending` | `/work?tab=withdrawals` | 🏦 คำขอถอนเงินใหม่ | รออนุมัติ 1 รายการ (500.00 บาท) |
| `sms_unmatched` | `/work?tab=sms` | 💬 เงินเข้ายังไม่ผูกบิล | SMS เงินเข้ารอจับคู่ 4 รายการ (156.00 บาท) |
| `stuck_readings` | `/work?tab=stuck` | ⚠️ บิลจ่ายแล้วค้าง | ลูกค้าจ่ายแล้วยังไม่ได้คำทำนาย 1 บิล — เปิดดูแล้วเลือกวิธีกู้ (v3.1: เลิกบอก "กดทำนายซ้ำ" — บางบิลลูกค้ามีบิลใหม่แล้ว retry จะตอบ `NEWER_ACTIVE_BILL`) |

ข้อความ FCM (HTTP v1 · project `plptdb`):

```json
{"message": {
  "token": "<token>",
  "notification": {"title": "🧾 บิลรอตรวจการโอน", "body": "..."},
  "data": {"type": "bills_awaiting", "route": "/work?tab=bills", "count": "3"},
  "android": {"priority": "high", "ttl": "86400s", "notification": {"channel_id": "admin_ops_alerts", "tag": "bills_awaiting"}},
  "apns": {"payload": {"aps": {"sound": "default", "thread-id": "bills_awaiting"}}}
}}
```

- แอป Android ควรสร้าง notification channel `admin_ops_alerts` (ไม่มี = FCM ใช้ช่องเริ่มต้น) · `tag` = แจ้งเตือนชนิดเดียวกันทับของเก่า
- ค่าใน `data` เป็น string ทั้งหมด (`count` ด้วย) · แตะแจ้งเตือน → เปิด `data.route`
- เคารพสวิตช์ `fcm_enabled` ตัวเดียวกับแอป SMS Checker (v3.1 — เดิมไม่ผูก) · ปิดเฉพาะแอปแอดมินได้ด้วยการไม่ลงทะเบียนเครื่อง

ตัวส่ง FCM แยกเป็น `App\Services\Fcm\FcmHttpV1Client` (OAuth2 JWT → access token เก็บในหน่วยความจำเท่านั้น)
— `FcmNotificationService` ของแอป SMS Checker เรียกผ่านตัวนี้ พฤติกรรมเดิมทุกอย่าง (ข้อความ · `sms_payment_channel` · `OPEN_ORDERS` · การล้าง token เดิม)

## 9. Approvals — คิวอนุมัติที่ต้องใช้คนตัดสิน

> route ทั้งหมดอยู่ที่ `routes/admin_api_approvals.php` (require จากกลุ่ม auth ของ `routes/admin_api.php`)
> controller: `app/Http/Controllers/Api/Admin/Approvals/*` — **ทุกการกระทำเรียก service / controller ตัวเดียวกับหลังบ้านเว็บ ไม่มีกติกาธุรกิจใหม่**

### กติการ่วมของหมวดนี้

- ทุก endpoint ต้อง Bearer admin token (401 ไม่มี token · 403 `NOT_ADMIN` ไม่ใช่แอดมิน) — **สิทธิ์ย่อยเท่ากับหน้าเว็บ**:
  หน้าเว็บของทุกคิวอยู่ใต้ `role:admin,super_admin` อย่างเดียว ยกเว้น eKYC (`KycVerificationPolicy`) · ระงับบัญชี (`UserPolicy::block`) · รีเซ็ต PIN (super admin เท่านั้น)
  ไม่ผ่าน = 403 `error_code: PERMISSION_DENIED`
- ข้อมูลไม่ถูกต้อง = **422** envelope `{success:false, message:"<ข้อผิดพลาดแรกภาษาไทย>", error_code:"VALIDATION_ERROR", errors:{field:[...]}}`
  (คิวไรเดอร์ 9.4 / 9.5 ส่งต่อให้ controller ของเว็บตรวจ → มี `message` + `error_code` แต่ **ไม่มี `errors`** — แสดง `message` ได้เลย)
- ทำไม่ได้ตามสถานะ = **409** (`error_code` ตามแต่ละคิว) · ไม่พบ = **404** · พังไม่คาดคิด = **500** `SERVER_ERROR` ข้อความไทยกลาง ๆ (ไม่คืนข้อความ exception ดิบ — log ผ่าน `SafeLog`)
- **กดซ้ำปลอดภัย (idempotent)**: สั่งผลเดิมซ้ำกับรายการที่เป็นผลนั้นอยู่แล้ว → **200** `success:true` + `data.already_decided:true` (คิวผู้ใช้/MLM/ตั๋วใช้ชื่อ `already`) — ไม่มีเงิน/แจ้งเตือนซ้ำ
  สั่ง **ผลอื่น** กับรายการที่ตัดสินไปแล้ว → 409
- รายการใช้ paging แบบแบน (`per_page` 1–100 ค่าเริ่มต้น 20) · คิวรอทำ = **รอนานสุดก่อน** · กองที่จบแล้ว = ล่าสุดก่อน
- PII ในรายการน้อยที่สุด: `user` = `{id, name, member_number}` (ไม่มีอีเมล/เบอร์) · เบอร์ = `phone_masked` (`081•••5678`) · เลขบัตร/เลขภาษี = ปิดกลาง
- `waiting_minutes` = นาทีที่รายการรออยู่ (null เมื่อไม่ได้อยู่ในคิวรอ)
- ตัวเลขเงิน/คะแนนส่งเป็นทศนิยมเสมอ (`140.0` ไม่ใช่ `140` — `JSON_PRESERVE_ZERO_FRACTION`) · id/จำนวน = จำนวนเต็ม
- รูป/เอกสารส่วนตัวทั้งหมด = URL ของ endpoint ที่ **ต้องแนบ Bearer token** (`requires_auth` / `image_requires_auth = true`) ตอบ `Cache-Control: private, no-store`

### 9.1 `GET approvals/summary` — ตัวเลขป้ายทุกคิว (cache 20 วินาที ใช้ร่วมทุกแอดมิน)

```json
{
  "success": true,
  "data": {
    "queues": {
      "ekyc": {"count": 2, "oldest_minutes": 95},
      "seller_applications": {"count": 1, "oldest_minutes": 640},
      "rider_applications": {"count": 1, "oldest_minutes": 30},
      "rider_documents": {"count": 1, "oldest_minutes": 12},
      "rider_jobs": {"count": 2, "oldest_minutes": 48, "disputed": 1, "awaiting_release": 1, "manual_needed": 0},
      "tickets": {"count": 4, "oldest_minutes": 1500},
      "mlm_commissions": {"count": 3, "oldest_minutes": 300, "amount_thb": 350.5, "approved_unpaid": 1}
    },
    "total": 12,
    "computed_at": "2026-10-06T14:00:00+07:00",
    "generated_at": "2026-10-06T14:00:12+07:00",
    "degraded": []
  }
}
```

- นิยามแต่ละคิว = ค่าเริ่มต้นของรายการนั้น: `ekyc` (eKYC `pending`) · `seller_applications` (`pending`) · `rider_applications` (ไรเดอร์ `pending`)
  · `rider_documents` (อนุมัติแล้วแต่เปลี่ยนเอกสารสำคัญ `documents_changed_at` ไม่ว่าง) · `rider_jobs` (`filter=needs_decision`) · `tickets` (`status=open`) · `mlm_commissions` (`pending`)
- `total` = ผลรวม `count` · `rider_jobs.disputed/awaiting_release/manual_needed` = แยกย่อย (อาจซ้อนกันได้: ร้องเรียนระหว่างจัดส่ง = ทั้ง disputed และ awaiting_release)
- `mlm_commissions.approved_unpaid` = อนุมัติแล้วรอจ่าย (**ไม่นับ** ใน `count`)
- คิวที่อ่านไม่ได้ = `null` + ชื่ออยู่ใน `degraded` (ไม่ใช่ "ไม่มีงาน") · `oldest_minutes = null` เมื่อคิวว่าง

### 9.2 eKYC ที่ AI ไม่มั่นใจ — `approvals/ekyc`

เฉพาะแถว AI eKYC (`method = ekyc`) — คำขอแบบเดิม (อัปโหลดเอกสารเอง) ยังตรวจที่หลังบ้านเว็บ (`404` ถ้าเรียกด้วย id ของแถวแบบเดิม)
สิทธิ์ตาม `KycVerificationPolicy` (`viewAny` / `view` / `viewImages` / `approve` / `reject` / `requestRetake`)

**`GET approvals/ekyc?status=pending|approved|rejected|retake|all&search=&page=&per_page=`** (ค่าเริ่มต้น `pending`, `search` = ชื่อ/อีเมลผู้ใช้)

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 55,
        "user": {"id": 77, "name": "ณัฐ ใจงาม", "member_number": "TP000077"},
        "status": "pending",
        "status_label": "รอเจ้าหน้าที่ตรวจ",
        "method": "ekyc",
        "document_type": "thai_national_id",
        "document_type_label": "บัตรประชาชนไทย",
        "id_last4": "•••••••••0121",
        "submitted_at": "2026-10-06T13:20:00+07:00",
        "waiting_minutes": 40,
        "ai": {"decision": "review", "face_match": 0.41, "liveness": 0.92, "real": 0.88, "card_real": 0.9, "ocr": 0.95},
        "reasons": ["BORDERLINE_MATCH"],
        "reason_texts": ["ใบหน้าคล้ายรูปบนบัตรระดับกลาง"],
        "flags": {"duplicate_id": false, "replay_suspected": false, "prior_rejected": false, "attempts_exhausted": false,
                  "user_corrected": false, "ai_unavailable": false, "card_not_real": false, "lifelong_card": false}
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

**`GET approvals/ekyc/{id}`** = ฟิลด์ของรายการ +

```json
{
  "card": {"name_th": "นาย ณัฐ ใจงาม", "name_en": "Mr. Nat Jaingam", "birth_date": "1995-01-12", "expiry_date": "2030-01-11",
           "lifelong": false, "id_masked": "1 2345 ••••• 12 1", "checksum_ok": true},
  "account": {"name": "ณัฐ ใจงาม", "kyc_status": "pending", "name_matches_card": true, "created_at": "2026-09-01T10:00:00+07:00"},
  "ai_detail": {"match_score": 0.63, "liveness_passed": true, "challenges": {"blink": true, "turn_left": true, "smile": true},
                "thresholds": {"auto_approve_match": 0.5, "review_match": 0.3, "retake_match": 0.15, "min_liveness": 0.8,
                               "min_real": 0.7, "min_card_real": 0.6, "min_ocr": 0.8},
                "model_version": "card:1.0.0;face:1.0.0"},
  "corrections": {},
  "duplicate_now": false,
  "images": [
    {"kind": "card", "label": "รูปบัตรประชาชน", "available": true, "url": "https://main.thaiprompt.online/api/admin/approvals/ekyc/55/image/card"},
    {"kind": "card_face", "label": "รูปหน้าบนบัตร", "available": true, "url": "https://main.thaiprompt.online/api/admin/approvals/ekyc/55/image/card_face"},
    {"kind": "best_frame", "label": "ใบหน้าจากกล้อง (เฟรมที่ดีที่สุด)", "available": true, "url": "https://main.thaiprompt.online/api/admin/approvals/ekyc/55/image/best_frame"}
  ],
  "image_requires_auth": true,
  "recent_views": [{"viewer": "แอดมินเอ", "kind": "card", "at": "2026-10-06T13:59:00+07:00"}],
  "review": {"reviewed_by": null, "reviewed_at": null, "note": null},
  "actions": {"can_approve": true, "can_reject": true, "can_request_retake": true}
}
```

- `duplicate_now` = เช็คสด ณ ตอนเปิดดู (บัตรนี้ยืนยันกับบัญชีอื่นแล้วหรือยัง) — ถ้า `true` การอนุมัติจะถูกปฏิเสธ `409 EKYC_DUPLICATE_ID`
- **ไม่มีเลขบัตรเต็มในทุกคำตอบ** (รายการ = 4 ตัวท้าย · รายละเอียด = ปิดกลาง)

**`GET approvals/ekyc/{id}/image/{kind}`** — `kind` = `card` | `card_face` | `best_frame`

- สตรีม `image/jpeg` ที่ถอดรหัสแล้ว · `Cache-Control: private, no-store, max-age=0` · `X-Content-Type-Options: nosniff`
- **PDPA: ทุกการเปิดดูเขียน `kyc_access_logs` 1 แถว** (`viewer_id`, `subject_user_id`, `kind`, `ip_address`, `user_agent`) ผ่าน `KycAccessLog::record()` ตัวเดียวกับหน้าเว็บ — แอปควรแคชรูปในหน่วยความจำของหน้าจอเท่านั้น (เปิดซ้ำ = บันทึกซ้ำ)
- throttle 120 ครั้ง/นาที (ถังเดียวกับหน้าเว็บ) · ไม่มีรูป/ถูกลบตามรอบ = 404 `IMAGE_NOT_FOUND`

**`POST approvals/ekyc/{id}/approve`** · **`POST .../reject` `{reason}`** (บังคับ ≤1000) · **`POST .../request-retake` `{reason}`** (บังคับ ≤500 — ผู้ใช้เห็นข้อความนี้)

ตัดสินผ่าน `EkycService::adminDecide()` (ล็อกเลขบัตร · เช็คบัตรซ้ำสด · อัปเดต `users.kyc_status` · แจ้งผู้ใช้ `kyc_result`)

```json
{"success": true, "data": {"id": 55, "status": "approved", "already_decided": false}, "message": "อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว"}
```
```json
{"success": false, "data": {"id": 55, "status": "approved"}, "message": "การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว", "error_code": "EKYC_ALREADY_DECIDED"}
```
`status` หลังตัดสิน: `approved` · `rejected` · `retake` (ผู้ใช้กลับเป็น "ยังไม่ยืนยัน" เริ่มรอบใหม่ได้) · error อื่น: `409 EKYC_DUPLICATE_ID`

### 9.3 คำขอเปิดร้านค้า — `approvals/seller-applications`

**`GET approvals/seller-applications?status=pending|active|closed|all&search=`** (ค่าเริ่มต้น `pending` · `closed` = ถูกปฏิเสธ)

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 31, "store_name": "ร้านป้าแดง", "status": "pending", "status_label": "รออนุมัติ",
        "business_type": "company", "business_type_label": "นิติบุคคล", "company_name": "บริษัท ป้าแดง จำกัด",
        "tax_id_masked": "010•••••••456", "phone_masked": "081•••5678", "province": "กรุงเทพมหานคร",
        "owner": {"id": 90, "name": "ป้าแดง", "member_number": "TP000090"},
        "rejection_reason": null,
        "submitted_at": "2026-10-06T03:20:00+07:00", "waiting_minutes": 640
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

**`GET approvals/seller-applications/{id}`** = รายการ + `store_description` · `store_phone` · `store_email` · `address{line,city,state,postal_code}`
· `owner_detail{role, kyc_status, kyc_verified, suspended, deleted, joined_at}` · `actions{can_approve, can_reject}`

**`POST .../{id}/approve`** → `SellerApplicationService::approve()` (ล็อกร้าน+เจ้าของ · ร้าน active + แพ็กเกจฟรี · role → seller · แจ้งผู้สมัคร)
**`POST .../{id}/reject` `{reason}`** (บังคับ ≤500 — ผู้สมัครเห็น) → ร้าน `closed` + เก็บเหตุผล · ผู้สมัครแก้แล้วยื่นใหม่ได้

```json
{"success": true, "data": {"id": 31, "status": "active", "already_decided": false}, "message": "อนุมัติร้าน \"ร้านป้าแดง\" เรียบร้อย"}
```
409: `ALREADY_PROCESSED` (ตัดสินเป็นผลอื่นไปแล้ว) · `OWNER_NOT_FOUND` · `OWNER_SUSPENDED` · `ROLE_NOT_CONVERTIBLE` (เจ้าของมี role อื่น เช่น provider — เปลี่ยนที่หน้าเว็บ)

### 9.4 ใบสมัครไรเดอร์ + ตรวจเอกสารซ้ำ — `approvals/riders`

ทุกการเขียนเรียก `Admin\RiderController` ของเว็บตัวเดิม (validation / เงื่อนไขสถานะ / ข้อความเดียวกัน)

**`GET approvals/riders?status=pending|documents_changed|rejected|all&search=`**
(`pending` = ใบสมัครรอตรวจ เก่าสุดก่อน · `documents_changed` = อนุมัติแล้วแต่เปลี่ยนบัตร/ใบขับขี่/ทะเบียนรถ รอตรวจซ้ำ — ระหว่างนี้รับงานไม่ได้)

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 12, "full_name": "สมชาย ขยันส่ง", "phone_masked": "089•••5432", "id_card_masked": "123xxxxxxx121",
        "status": "pending", "status_text": "รอตรวจสอบ", "vehicle_type": "motorcycle", "vehicle_type_text": "มอเตอร์ไซค์",
        "vehicle_plate": "1กข 1234", "province": "กรุงเทพมหานคร",
        "user": {"id": 140, "name": "สมชาย", "member_number": "TP000140"}, "kyc_verified": true,
        "documents_complete": true, "documents_missing": [], "documents_missing_text": null,
        "documents_changed_at": null, "submitted_at": "2026-10-06T13:30:00+07:00", "waiting_minutes": 30
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

**`GET approvals/riders/{id}`** = รายการ + `phone` · `birth_date` · `address{line,district,province}` · `vehicle{type,type_text,plate,brand,color}` · `rider_type`
· `rejection_reason` · `suspension_reason` · `approved_at` · `actions{can_approve, can_reject, can_mark_documents_reviewed}` ·
```json
"documents": [
  {"type": "id_card", "label": "บัตรประชาชน", "uploaded": true, "required": true,
   "url": "https://main.thaiprompt.online/api/admin/approvals/riders/12/document/id_card", "requires_auth": true},
  {"type": "profile", "label": "รูปถ่ายหน้าตรง", "uploaded": false, "required": true, "url": null, "requires_auth": false}
]
```

**`GET approvals/riders/{id}/document/{type}`** — `type` = `id_card` | `driver_license` | `vehicle_registration` | `profile`
สตรีมจาก private disk ผ่าน `Admin\RiderController::document` (log การเปิดดูแบบเดียวกับเว็บ) · `Cache-Control: private, no-store` · ไม่มีไฟล์ = 404 `DOCUMENT_NOT_FOUND`

**`POST .../{id}/approve`** — เอกสารบังคับต้องครบ (ไม่ครบ = **422 `DOCUMENTS_INCOMPLETE`** `data.missing`) · ถูกระงับ = 409 `RIDER_SUSPENDED` · อนุมัติอยู่แล้ว = 200 (ถือว่าตรวจเอกสารที่เปลี่ยนแล้ว)
**`POST .../{id}/reject` `{reason}`** (3–500 ตัวอักษร) — เฉพาะ `pending`/`inactive` (อนุมัติแล้ว = 409 `INVALID_STATUS` ใช้ "ระงับ" ที่หน้าเว็บ) · ปฏิเสธไปแล้ว = 200 `already_decided`
**`POST .../{id}/documents-reviewed`** — เอกสารต้องครบ → ล้าง `documents_changed_at` (ไรเดอร์รับงานต่อได้)

```json
{"success": true, "data": null, "message": "อนุมัติไรเดอร์เรียบร้อย"}
```
```json
{"success": false, "data": {"missing": ["profile"]}, "message": "อนุมัติไม่ได้ ยังขาดเอกสาร: รูปถ่ายหน้าตรง", "error_code": "DOCUMENTS_INCOMPLETE"}
```

### 9.5 งานไรเดอร์ที่รอตัดสิน (💸 เคลื่อนเงิน) — `approvals/rider-jobs`

**`GET approvals/rider-jobs?filter=needs_decision|handover_review|disputed|awaiting_release|manual_needed`** (ค่าเริ่มต้น `needs_decision` · เก่าสุดก่อน · **`per_page` สูงสุด 50** — ขอมากกว่าได้ 50 ดูค่าจริงที่ `data.per_page`)

- `handover_review` = งานที่ **แอดมินตัดสินการส่งมอบได้จริง** (`HandoverService::scopeAdminResolvable`) และ (เงินพักรอปลด `awaiting_release` หรือ ผู้ซื้อร้องเรียน) — นิยามเดียวกับตัวกรองหน้าเว็บ
- `disputed` / `awaiting_release` = แยกย่อยของข้างบน · `manual_needed` = งานรอไรเดอร์ที่ระบบหาคนรับไม่ได้ (`dispatch_type = manual_needed`) ต้องมอบหมายเอง
- `needs_decision` = `handover_review` ∪ `manual_needed`

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 501, "job_number": "RJ2610060001", "job_type": "shop_delivery",
        "status": "awaiting_release", "status_text": "วางของแล้ว รอปลดเงิน",
        "decision_type": "dispute",
        "reason": {"code": "wrong_item", "text": "ผู้ซื้อร้องเรียน: ได้ของผิด", "note": "ได้สีผิด"},
        "amounts": {"order_total_thb": 140.0, "delivery_fee_thb": 40.0, "rider_earnings_thb": 32.0,
                    "platform_fee_thb": 8.0, "shop_bonus_thb": 0.0, "cod_thb": 0.0},
        "order": {"type": "Order", "id": 9001, "order_number": "ORD-261006-0001"},
        "rider": {"id": 12, "name": "สมชาย ขยันส่ง"},
        "customer": {"id": 300, "name": "สมหญิง ใจดี", "member_number": "TP000300"},
        "handover": {"status": "disputed", "status_text": "ผู้ซื้อร้องเรียน รอแอดมินตัดสิน", "method": null,
                     "disputed_at": "2026-10-06T13:10:00+07:00", "waited_photo_at": null,
                     "buyer_confirmed_at": null, "auto_release_at": null},
        "created_at": "2026-10-06T12:30:00+07:00", "waiting_minutes": 50,
        "actions": {"can_release": true, "can_refund": true, "can_reassign": false, "can_redispatch": false}
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

- `decision_type`: `dispute` (ร้องเรียน) · `awaiting_release` (วางของแล้ว เงินพักรอปลดอัตโนมัติ) · `manual_dispatch` (ไม่มีไรเดอร์รับ) · `handover_review` (ตัดสินได้แต่ไม่เข้าข้อข้างบน) · `none`
- `amounts.order_total_thb` = ยอดออเดอร์ต้นทาง (เงินที่พักอยู่) · `delivery_fee_thb` = ค่าส่งรวม · `rider_earnings_thb` = ส่วนของไรเดอร์ · `shop_bonus_thb` = โบนัสที่ร้านเติม
- `actions` = คำใบ้ปุ่มชุดเดียวกับหน้าเว็บ (`HandoverService::adminCanResolve` + `Admin\RiderJobController::adminActions`) — ตัดสินจริงที่ service

**`GET approvals/rider-jobs/{id}`** = รายการ + `title` · `pickup{address,contact_name}` · `delivery{address,contact_name}` · `distance_km` · `timeline{...}`
· `cancellation_reason` · `failure_reason` · `dispatch_round` ·
```json
"handover_photos": [{"kind": "waited", "label": "รูปวางของ (รอบ 2)", "taken_at": "...",
                     "url": "https://main.thaiprompt.online/api/admin/approvals/rider-jobs/501/handover-photo/waited", "requires_auth": true}],
"eligible_riders": [{"id": 15, "full_name": "สมศักดิ์", "vehicle_type_text": "มอเตอร์ไซค์", "availability": "online",
                     "availability_text": "ออนไลน์", "distance_to_pickup_km": 1.2, "last_location_update": "..."}]
```
(`eligible_riders` = รายชื่อเดียวกับหน้าเว็บ — อนุมัติแล้ว ไม่ถูกระงับ ไม่มีงานค้าง ไม่ใช่คู่กรณี · ตัดเบอร์โทรออก)

**`GET approvals/rider-jobs/{id}/handover-photo/{kind}`** — `arrival` | `waited` (รูปหลักฐาน private disk ผ่าน `Admin\RiderJobController::handoverPhoto`) · no-store

**การกระทำ (ทุกตัวเรียก `Admin\RiderJobController` ของเว็บ → service เดิม)** — ผลตอบรูปเดียวกับเว็บ:

| Endpoint | body | ทำอะไร / การตรวจ (อยู่ใน service) | error_code ที่เป็นไปได้ |
|---|---|---|---|
| `POST .../{id}/release` | `{reason?}` ≤1000 | `HandoverService::adminRelease` — ล็อกงาน+การส่งมอบ · `adminCanResolve` · ออเดอร์ต้องยังไม่ยกเลิก/คืนเงิน → ปิดงานเป็น **ส่งสำเร็จ** แบ่งเงินร้าน + จ่ายไรเดอร์ | `HANDOVER_FINAL` · `HANDOVER_NOT_READY` · `VALIDATION_ERROR` |
| `POST .../{id}/refund` | `{reason}` 3–1000 | `HandoverService::adminRefund` — ล็อกคู่เดียวกัน · `adminCanResolve` → งาน **ส่งไม่สำเร็จ** (ไรเดอร์ไม่ได้ค่าส่ง) + ยกเลิกออเดอร์ **คืนเงินผู้ซื้อเต็มจำนวน** | `HANDOVER_FINAL` · `HANDOVER_NOT_READY` · `REFUND_FAILED` (เช่นกระเป๋าผู้ซื้อถูกระงับ — ไม่มีอะไรเปลี่ยน) |
| `POST .../{id}/reassign` | `{rider_id}` | `RiderJobService::adminReassign` — งานยังไม่จบ/ไม่อยู่ในช่วงเงินพัก · ไรเดอร์ใหม่อนุมัติแล้ว ไม่ถูกระงับ ไม่มีงานค้าง วงเงิน COD พอ ไม่ใช่ผู้ซื้อ/ผู้ขาย | `INVALID_TRANSITION` · `HAS_ACTIVE_JOB` · `NOT_ELIGIBLE`/รหัสเหตุผล · `SELF_ORDER` · `INSUFFICIENT_COD_CREDIT` · `VALIDATION_ERROR` |
| `POST .../{id}/redispatch` | — | สร้างงานใหม่ให้ออเดอร์ของงาน `cancelled`/`failed` (ออเดอร์ต้องยังเรียกไรเดอร์ได้ — `FreshMarketService::redispatchRider` / `RiderDispatchService::createJobForSource`) | `INVALID_TRANSITION` · `REDISPATCH_FAILED` |

```json
{"success": true, "data": {"job": {"id": 501, "status": "completed"}}, "message": "ปล่อยเงินแล้ว งานปิดเป็นส่งสำเร็จ และแจ้งผู้ซื้อกับไรเดอร์แล้ว"}
```
```json
{"success": true, "data": {"job": {"id": 501, "status": "completed"}, "already_decided": true}, "message": "งานนี้ปล่อยเงินไปแล้ว"}
```
```json
{"success": false, "data": null, "message": "การส่งมอบนี้ปิดไปแล้ว", "error_code": "HANDOVER_FINAL"}
```

- กดซ้ำ: `release` หลังปล่อยแล้ว / `refund` หลังคืนแล้ว / `reassign` ให้ไรเดอร์คนเดิม = 200 `already_decided:true` (ไม่มีเงินขยับ)
  — รวมกรณีสองคำขอวิ่งพร้อมกัน: ตัวที่สองได้ 200 `already_decided:true` ถ้าผลตรงกับที่สั่ง · ผลอื่นไปแล้ว = 409 `HANDOVER_FINAL`
- `reassign` ตรวจ `rider_id` ก่อนเสมอ (ไม่ส่ง/ไม่ใช่ตัวเลข/ไม่มีไรเดอร์ = 422 `VALIDATION_ERROR` + `errors.rider_id`)
- สิทธิ์: แอดมิน (หน้าเว็บก็ `role:admin,super_admin` เท่านั้น — ไม่มีสิทธิ์ย่อยสำหรับการตัดสินเงิน)

### 9.6 ผู้ใช้ — ระงับ / ยกเลิกระงับ / รีเซ็ต PIN

| Endpoint | body | สิทธิ์ / กติกา |
|---|---|---|
| `POST users/{id}/suspend` | `{reason?}` ≤500 | `UserPolicy::block` (ห้ามตัวเอง → 403) · `UserSuspensionService` (ตัวเดียวกับเว็บ): ห้ามระงับตัวเอง/super admin → 409 `CANNOT_SUSPEND_SELF` / `CANNOT_SUSPEND_SUPER_ADMIN` · บัญชีทีมงาน (role `admin`/`moderator`) → **super admin เท่านั้น** ไม่ใช่ = 403 `STAFF_REQUIRES_SUPER_ADMIN` · ระงับ = เพิกถอน token แอปทั้งหมด + ปล่อยงานไรเดอร์ที่ค้าง |
| `POST users/{id}/unsuspend` | — | `UserPolicy::block` · บัญชีทีมงาน → super admin เท่านั้น (403 `STAFF_REQUIRES_SUPER_ADMIN`) |
| `POST users/{id}/reset-wallet-pin` | — | **super admin เท่านั้น** (เหมือนเว็บ) → `WalletService::adminResetPin` ล้าง PIN + ปลดล็อกกรอกผิด + log `pin_changed` ระดับ warning · ไม่มีกระเป๋า = 404 `WALLET_NOT_FOUND` |

```json
{"success": true, "data": {"user_id": 77, "suspended": true, "suspended_at": "2026-10-06T14:00:00+07:00", "already": false}, "message": "ระงับบัญชี สมชาย เรียบร้อย"}
```
```json
{"success": true, "data": {"user_id": 77, "wallet_id": 40, "has_pin": false, "locked": false}, "message": "รีเซ็ต PIN สำเร็จ ผู้ใช้สามารถตั้ง PIN ใหม่ได้"}
```
ระงับซ้ำ / ยกเลิกซ้ำ = 200 `already:true` (ไม่เขียนทับเหตุผลเดิม)

### 9.7 ตั๋วซัพพอร์ต — `approvals/tickets`

กองของแอป → สถานะจริง: `open` = `open` + `in_progress` (รอทีมงาน) · `pending` = `waiting_customer` (รอลูกค้า) · `closed` = `resolved` + `closed` · `all`

**`GET approvals/tickets?status=open|pending|closed|all&priority=low|medium|high|critical&search=`** (ค่าเริ่มต้น `open` · ด่วนก่อน แล้วรอนานสุดก่อน · `closed` = ล่าสุดก่อน)

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 8, "ticket_number": "TKT-ABC-20261006-1234", "subject": "ถอนเงินไม่เข้า",
        "status": "open", "status_label": "เปิด", "bucket": "open",
        "priority": "high", "priority_label": "สูง", "category": "การเงิน",
        "user": {"id": 300, "name": "สมหญิง", "member_number": "TP000300"},
        "assigned_to": null, "replies_count": 0, "is_overdue": false,
        "created_at": "2026-10-05T13:00:00+07:00", "last_reply_at": null, "waiting_minutes": 1500
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1
  }
}
```

**`GET approvals/tickets/{id}`** = รายการ + `description` · `resolution_notes` · `due_at` · `first_response_at` · `resolved_at` · `closed_at`
· `statuses` (ตัวเลือกที่ตั้งได้ + ป้าย) ·
```json
"messages": [
  {"id": null, "sender": "customer", "author": "สมหญิง", "message": "ถอนเงินเมื่อวานยังไม่เข้า", "is_internal_note": false, "at": "..."},
  {"id": 41, "sender": "staff", "author": "แอดมินเอ", "message": "เช็คกับฝ่ายการเงิน", "is_internal_note": true, "at": "..."}
]
```
(ข้อความแรก = คำอธิบายตอนเปิดตั๋ว · `is_internal_note: true` = บันทึกภายในจากหน้าเว็บ ลูกค้าไม่เห็น)

**`POST approvals/tickets/{id}/reply` `{message}`** (บังคับ ≤10,000) → `TicketService::addReply` (ตอบลูกค้า ไม่ใช่บันทึกภายใน · บันทึกเวลาตอบครั้งแรก · แจ้งลูกค้า) → **201**
```json
{"success": true, "data": {"reply": {"id": 42, "sender": "staff", "author": "แอดมินเอ", "message": "...", "is_internal_note": false, "at": "..."},
  "duplicate": false, "ticket": {"id": 8, "status": "open"}}, "message": "เพิ่มข้อความตอบกลับเรียบร้อยแล้ว"}
```
กันกดส่งซ้ำ: ข้อความเดิมจากแอดมินคนเดิมในตั๋วเดิมภายใน 60 วินาที → **200** `duplicate:true` คืนรายการเดิม (ไม่ส่งถึงลูกค้าซ้ำ)
· สองคำขอพร้อมกันถูกล็อกต่อ (ตั๋ว·แอดมิน·ข้อความ) — ตัวที่สองรอแล้วได้ `duplicate:true` · รอเกิน 5 วินาที = 409 `REPLY_IN_PROGRESS` (ลองใหม่ได้)

**`POST approvals/tickets/{id}/status` `{status, resolution_notes?}`** — `status` ∈ `open, in_progress, waiting_customer, resolved, closed` (ชุดเดียวกับเว็บ) → `TicketService::changeStatus`
```json
{"success": true, "data": {"id": 8, "status": "resolved", "status_label": "แก้ไขแล้ว", "already": false}, "message": "อัปเดตสถานะตั๋วเรียบร้อยแล้ว"}
```
สถานะเดิมอยู่แล้ว = 200 `already:true` (ไม่เรียก service ซ้ำ — ไม่แจ้ง/ขอคะแนนซ้ำ)

> แก้บั๊กที่มาด้วย: `NotificationService::notifyAdminNewTicket` / `notifyAdminUnassignedTicket` / `notifyAdminNewCommission` เดิม query `users.is_admin` (ไม่มีคอลัมน์นี้) → SQL error ถูก `TicketObserver` กลืน แอดมินไม่เคยได้แจ้งเตือนตั๋ว
> ตอนนี้ใช้ตัวเลือกผู้รับเดียวกับ `notifyAdminNewKyc` (super admin / role admin, super_admin / สิทธิ์ที่เกี่ยวข้อง · ไม่ถูกระงับ) · ตั๋วเพิ่ม role `moderator` + สิทธิ์ `manage_tickets`
> · บันทึกภายใน (`is_internal_note`) ไม่แจ้งลูกค้าอีกต่อไป (เดิมแจ้งพร้อมตัวอย่างข้อความภายใน) · ตัวอย่างข้อความตอบกลับตัดด้วย `mb_substr` (เดิมตัดไบต์กลางตัวอักษรไทย → แจ้งเตือนหายเงียบ)

### 9.8 คอมมิชชัน MLM (💸 เคลื่อนเงิน) — `approvals/mlm-commissions`

**`GET approvals/mlm-commissions?status=pending|approved|paid|rejected|all&type=`** (ค่าเริ่มต้น `pending` · `pending`/`approved` = เก่าสุดก่อน)

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 700, "type": "direct_referral", "level": 1, "status": "pending", "status_label": "รออนุมัติ",
        "amount_thb": 100.0, "sales_amount_thb": 1000.0, "pv_amount": 0.0,
        "recipient": {"id": 77, "name": "ผู้รับคอม", "member_number": "TP000077"},
        "from_member": {"id": 51, "name": "เพื่อนที่สั่งซื้อ"}, "plan": "Default Plan",
        "source": {"type": "Order", "id": 9001},
        "created_at": "2026-10-06T11:00:00+07:00", "approved_at": null, "paid_at": null, "waiting_minutes": 180,
        "actions": {"can_approve": true, "can_pay": false}
      }
    ],
    "current_page": 1, "last_page": 1, "per_page": 20, "total": 1,
    "summary": {"pending_count": 2, "pending_amount_thb": 150.25, "approved_count": 1, "approved_amount_thb": 70.0}
  }
}
```

| Endpoint | ทำอะไร (ทางเดียวกับ `Admin\MlmCommissionController`) | ผล |
|---|---|---|
| `POST .../{id}/approve` | `MlmCalculationService::approvePendingCommissions([id])` — เฉพาะ `pending` | อนุมัติไปแล้ว/จ่ายแล้ว = 200 `already:true` · สถานะอื่น (เช่น rejected) = 409 `INVALID_STATUS` |
| `POST .../{id}/pay` | `MlmCalculationService::payApprovedCommissions([id])` — transaction ต่อรายการ · ล็อกแถวคอมด้วย primary key แล้วตรวจสถานะซ้ำ · เคยมีรายการ wallet ของคอมนี้ = แค่ปิดสถานะ (ไม่จ่ายซ้ำ) · หักกองทุน MLM ก่อน · ฝากเข้ากระเป๋าผ่าน `WalletService` (type `commission`) | ยังไม่อนุมัติ = 409 `NOT_APPROVED` · จ่ายแล้ว = 200 `already:true` · กองทุนไม่พอ/กระเป๋าผู้รับใช้ไม่ได้ = 409 `PAY_FAILED` (ไม่มีเงินขยับ) |

```json
{"success": true, "data": {"id": 700, "status": "paid", "amount_thb": 120.0, "already": false}, "message": "จ่ายคอมมิชชั่นแล้ว"}
```

- 🚫 ไม่มีการ `lockForUpdate` แบบค้นหาบน `mlm_commissions` เพิ่ม (กติกาโปรเจกต์: gap lock ชนกับ insert ของการแบ่งเงิน = deadlock) — ใช้เส้นทางเดียวกับหน้าเว็บทุกตัวอักษร
- สิทธิ์: แอดมิน (หน้าเว็บ `role:admin,super_admin` — ไม่มีสิทธิ์ย่อย)
