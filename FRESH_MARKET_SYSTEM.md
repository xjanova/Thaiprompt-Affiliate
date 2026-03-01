# ระบบตลาดสดไทยพร้อม — Fresh Market System

> **Super Platform**: ซื้อ-ขาย-ส่ง-บริการ ในที่เดียว
>
> ทุก role สมัครผ่าน LINE OA → Auto-register → พร้อมใช้ทันที

---

## ภาพรวมระบบ

ตลาดสดไทยพร้อมเป็น On-Demand Marketplace ที่รวม 4 บทบาทหลัก:

| บทบาท | คำอธิบาย | การสมัคร |
|--------|----------|----------|
| **ผู้ซื้อ (Buyer)** | ซื้อของสดออนไลน์ สั่งซื้อผ่าน LINE | เพิ่มเพื่อน LINE OA ตลาดสด |
| **ผู้ขาย (Seller)** | เปิดร้านออนไลน์ ขายของผ่าน LINE | เพิ่มเพื่อน → OTP → ถ่ายรูปสินค้า |
| **ไรเดอร์ (Rider)** | รับส่งของจากร้านค้าถึงผู้ซื้อ | เพิ่มเพื่อน → OTP → วางค่าประกัน |
| **ช่างบริการ (Service)** | ให้บริการถึงบ้าน (ช่างประปา/ไฟฟ้า/แอร์/นวด/เสริมสวย) | เพิ่มเพื่อน → OTP → เลือกหมวดหมู่ |

---

## Architecture

```
                    ┌─────────────────┐
                    │   LINE OA       │
                    │  (ตลาดสด)       │
                    └────────┬────────┘
                             │ Webhook
                    ┌────────▼────────┐
                    │ ChannelManager   │
                    │ (State Machine)  │
                    └────────┬────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌───────▼───────┐ ┌─────▼─────┐ ┌───────▼───────┐
    │  Buyer Flow   │ │Seller Flow│ │  Rider Flow   │
    │  (ค้นหา/สั่งซื้อ) │ │(ลงขาย/OTP)│ │(สมัคร/ประกัน)  │
    └───────┬───────┘ └─────┬─────┘ └───────┬───────┘
            │                │                │
    ┌───────▼────────────────▼────────────────▼───────┐
    │              Laravel Backend                     │
    │  Models: FreshMarketOrder, Rider, ServiceProvider│
    │  Services: RiderDispatchService, MarketService   │
    └─────────────────────┬───────────────────────────┘
                          │
                ┌─────────▼─────────┐
                │     MySQL DB      │
                └───────────────────┘
```

---

## Database Schema

### ตาราง riders (ไรเดอร์)

| คอลัมน์ | ประเภท | คำอธิบาย |
|---------|--------|----------|
| id | bigint | Primary key |
| user_id | bigint FK | เชื่อม users |
| line_user_id | string | LINE User ID |
| fresh_market_linked | boolean | เชื่อมกับตลาดสด |
| full_name | string | ชื่อ-นามสกุล |
| phone | string | เบอร์โทร |
| rider_type | enum | delivery/service/both |
| service_categories | json | หมวดหมู่ช่าง |
| service_provider_id | bigint FK | เชื่อม service_providers |
| deposit_amount | decimal | ค่าประกัน |
| deposit_status | enum | pending/paid/refunded |
| deposit_paid_at | timestamp | วันชำระ |
| deposit_transaction_id | string | เลขอ้างอิง |
| status | enum | pending/approved/rejected/suspended |
| availability | enum | online/offline/busy |
| last_latitude | decimal | พิกัดล่าสุด |
| last_longitude | decimal | พิกัดล่าสุด |
| rating | decimal | คะแนนเฉลี่ย |

### ตาราง fresh_market_orders (คำสั่งซื้อ)

| คอลัมน์ | ประเภท | คำอธิบาย |
|---------|--------|----------|
| rider_id | bigint FK | ไรเดอร์ที่ assign |
| rider_assigned_at | timestamp | เวลา assign |
| rider_accepted_at | timestamp | เวลาไรเดอร์รับงาน |
| rider_picked_up_at | timestamp | เวลารับของ |
| rider_delivered_at | timestamp | เวลาส่งถึง |
| delivery_distance_km | decimal | ระยะทาง (กม.) |

### ตาราง service_providers (ผู้ให้บริการ)

| คอลัมน์ | ประเภท | คำอธิบาย |
|---------|--------|----------|
| rider_id | bigint FK | เชื่อม riders (ถ้าเป็นไรเดอร์เซอร์วิส) |
| accepts_delivery_jobs | boolean | รับงานส่งของด้วย |

---

## Rider System Integration

### Rider Types

1. **Delivery Rider** (`rider_type = 'delivery'`): รับเฉพาะงานส่งของ
2. **Service Rider** (`rider_type = 'service'`): เป็นช่างบริการอย่างเดียว
3. **Both** (`rider_type = 'both'`): ทั้งส่งของและบริการช่าง

### Deposit Flow (ค่าประกัน)

```
สมัครไรเดอร์ → OTP ยืนยัน → เช็ค deposit_status
  ├─ paid → เริ่มรับงานได้
  └─ pending → แสดงช่องทางชำระ
       ├─ QR PromptPay
       └─ โอนบัญชี
       → Admin approve → deposit_status = paid
```

### RiderDispatchService

- `findNearestRiders($order)` — Haversine formula หาไรเดอร์ใกล้ร้านค้า
- `dispatchToRider($order, $rider)` — สร้าง RiderJob + assign ไรเดอร์
- `calculateDeliveryFee($distance)` — คำนวณค่าส่ง (base ฿30 + ฿10/km)
- `autoDispatch($order)` — หาไรเดอร์อัตโนมัติและจ่ายงาน

---

## LINE OA Integration

### ตลาดสด OA

- **Webhook**: `/api/webhook/fresh-market`
- **ChannelManager**: `App\Services\FreshMarketChannelManager`
- **State Machine**: 15 states (idle, seller_phone, seller_otp, listing_*, search_*, order_*, rider_register, rider_category)

### Thaiprompt Main OA

- **Rich Menu**: `App\Services\ThaipromptRichMenuService`
- **Admin Editor**: `/admin/rich-menu/thaiprompt`
- **6 ปุ่ม**: ตลาดสด, AI Bots, MLM, ดูดวง, แดชบอร์ด, ช่วยเหลือ

---

## Routes

### Public Routes (`routes/taladsod.php`)

```
GET  /taladsod/                    → หน้าแรก
GET  /taladsod/start/buyer         → Landing ผู้ซื้อ
GET  /taladsod/start/seller        → Landing ผู้ขาย
GET  /taladsod/start/rider         → Landing ไรเดอร์/ช่าง
GET  /taladsod/search              → ค้นหาสินค้า
GET  /taladsod/listing/{slug}      → รายละเอียดสินค้า
GET  /taladsod/seller/{id}         → หน้าร้านค้า
```

### Admin Routes (`routes/admin.php`)

```
GET  /admin/rich-menu/thaiprompt              → Rich Menu Editor
POST /admin/rich-menu/thaiprompt/deploy       → Deploy Rich Menu
```

---

## Frontend Pages

### หน้าแรกตลาดสด (`/taladsod`)

```
┌─────────────────────────────────────┐
│          Hero + Search Bar           │
├─────────────────────────────────────┤
│     หมวดหมู่สินค้า (10 หมวด)         │
├─────────────────────────────────────┤
│     สินค้าแนะนำ + "ดูทั้งหมด"        │
├─────────────────────────────────────┤
│     สินค้าล่าสุด + "ดูทั้งหมด"       │
├─────────────────────────────────────┤
│     ร้านค้ายอดนิยม                   │
├─────────────────────────────────────┤
│     ตลาดช่าง (8 หมวด) + "ดูทั้งหมด"  │
├─────────────────────────────────────┤
│     CTA: เปิดร้าน + LINE            │
└─────────────────────────────────────┘
```

### Landing Pages

- **Buyer** (`/taladsod/start/buyer`): 3 ขั้นตอน → CTA เพิ่มเพื่อน LINE
- **Seller** (`/taladsod/start/seller`): 4 ขั้นตอน → CTA เพิ่มเพื่อน LINE
- **Rider** (`/taladsod/start/rider`): 2 แทร็ค (ส่งของ/ช่าง) → CTA เพิ่มเพื่อน LINE

---

## Key Files

| ไฟล์ | คำอธิบาย |
|------|----------|
| `app/Models/Rider.php` | Model ไรเดอร์ |
| `app/Models/FreshMarketOrder.php` | Model คำสั่งซื้อ |
| `app/Models/ServiceProvider.php` | Model ผู้ให้บริการ |
| `app/Models/RiderJob.php` | Model งานไรเดอร์ |
| `app/Models/FreshMarketConversation.php` | State Machine สนทนา |
| `app/Services/RiderDispatchService.php` | จัดการจ่ายงานไรเดอร์ |
| `app/Services/ThaipromptRichMenuService.php` | Rich Menu Thaiprompt OA |
| `app/Services/FreshMarketChannelManager.php` | LINE Webhook handler |
| `resources/views/taladsod/home.blade.php` | หน้าแรกตลาดสด |
| `resources/views/taladsod/landing-*.blade.php` | Landing pages |
| `routes/taladsod.php` | Routes ตลาดสด |
