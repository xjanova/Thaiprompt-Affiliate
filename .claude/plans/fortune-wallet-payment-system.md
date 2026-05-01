# Fortune Wallet-Based Payment System (Plan for Next Session)

> **Status**: 📝 Planned, NOT yet implemented
> **Owner**: User decision pending
> **Created**: 2026-05-01
> **Source session**: เปลี่ยน UPA → wallet-based bill matching

---

## 🎯 ปัญหาปัจจุบัน (Why)

ระบบเก่าใช้ **Unique Payment Amount (UPA)** จับคู่ slip — เช่น บิลคือ `39.27` บาท (มีทศนิยม unique ต่อ user) แล้ว SMS bank ที่เข้ามาต้องตรงเป๊ะถึงจับคู่ได้

**ปัญหา:**
1. ลูกค้าโอน `39` บาทถ้วน (ลืมเศษทศนิยม) → จับคู่ไม่ได้ → reading ค้าง
2. SMS Checker บนแอปมือถือต้อง sync ตลอด → กินแบต
3. ไม่มี wallet credit ให้ลูกค้าใช้ครั้งหน้า ถ้าโอนเกิน

## 💡 Solution ที่เสนอ

**เปลี่ยนเป็น wallet-based:**
1. SMS เข้า → identify ลูกค้า → เติม wallet
2. บิล pending → หักจาก wallet ถ้าพอ → จ่าย
3. ลูกค้ากด "โอนแล้ว" → server push FCM → แอปมือถือสแกน SMS ล่าสุด → ส่งกลับ (sync on-demand)
4. โอนเกิน = เครดิต wallet, ใช้ได้ครั้งหน้า
5. โอนไม่พอ = แจ้งจำนวนที่ขาด, รอเติมเพิ่ม

---

## ❓ Open Questions ที่ต้องถกในเซสชันหน้า

### H1. Customer identification on SMS
SMS bank ส่วนใหญ่**ไม่มีชื่อ/เบอร์ผู้โอน** — มีแค่ยอด + เลขบัญชีปลายทาง
- (a) ใช้ทศนิยม unique เหมือน UPA (กลับมาเรื่องเดิม)
- (b) User พิมพ์ "เลข 4 ตัวสุดท้ายเบอร์ที่โอน" ใส่ในแชท → match กับ SMS
- (c) User ส่งสลิปรูป → OCR (เดิมมี TPSlipCheckerService หรือเปล่า?)
- (d) "ใครกด `โอนแล้ว` ก่อน → SMS ใหม่ที่เข้ามาในช่วง N นาที = คนนั้น" (เสี่ยง race condition)
- **Best guess**: (a) + (d) ผสมกัน — UPA ยังคงไว้แต่ relax ถ้ายอดใกล้เคียง (±0.50 บาท ภายใน 30 นาทีหลัง "โอนแล้ว")

### H2. Wallet credit (โอนเกิน)
✅ Confirmed: เก็บส่วนเกินเป็นเครดิต wallet ใช้ได้ครั้งหน้า

### H3. บิลแบบใหม่ — เลขเต็มหรือ UPA?
- ถ้า wallet-based แล้ว ทำไมต้องมี UPA? บิล 39 บาทถ้วนพอ
- แต่ identification (H1) อาจยังต้อง UPA → ถ้าใช้ approach (b)/(c) ก็ไม่ต้อง UPA
- **Decision needed**

### H4. SMS sync trigger
ต้องเช็คโค้ด `app/Models/SmsCheckerDevice.php` + `app/Http/Controllers/Admin/SmsCheckerAdminController.php` ก่อน:
- ปัจจุบัน sync ยังไง? polling / FCM / webhook?
- ต้องเปลี่ยนเป็น **FCM push เมื่อกด "โอนแล้ว"** + sync บางช่วงเวลาเล็กน้อย (e.g. ทุก 5 นาที สำรอง)
- มี FCM service account แล้ว (`storage/app/firebase-credentials.json`, project `plptdb`)

### H5. Migration scope
- (a) เปลี่ยนทั้งระบบ (Celtic + Deep + Discovery + อื่นๆ)
- (b) Fortune ก่อน (Celtic + Deep)
- (c) สร้างขนาน + toggle setting
- **Recommendation**: (b) Fortune ก่อน, test 2 อาทิตย์, แล้วขยาย

---

## 📋 Implementation Steps (Draft)

### Step 1: Wallet integration
- [ ] เช็ค `app/Services/WalletService.php` — มี `deposit()` แล้ว
- [ ] เพิ่ม `deductForBill(Bill $bill)` method
- [ ] เพิ่ม `WalletTransaction` reference_type = 'fortune_reading'

### Step 2: SMS identification logic
- [ ] เช็ค `app/Services/SmsPaymentService.php` parsing
- [ ] เพิ่ม identification strategy (TBD per H1 decision)
- [ ] Fallback: queue SMS + ask user to claim manually

### Step 3: FCM push to SMS Checker app
- [ ] เช็ค `app/Models/SmsCheckerDevice.php` — หา fcm_token field
- [ ] เพิ่ม endpoint `/api/v1/fortune/{reading}/trigger-sms-sync`
- [ ] กด "โอนแล้ว" → trigger FCM `sync_now` ไปที่ device ที่จดทะเบียนกับเลขบัญชีปลายทาง
- [ ] App side: รับ FCM → scan SMS 5 อันล่าสุด → POST กลับ server

### Step 4: Bill amount simplification
- [ ] เปลี่ยน `unique_amount` → `amount` (จำนวนเต็ม, no UPA) — ขึ้นกับ H3
- [ ] หรือเก็บ UPA ไว้แต่ wallet-deduct ใช้ amount หลัก

### Step 5: Customer auto-create flow
- [ ] SMS เข้า + identify ได้ → ถ้ายังไม่มี user → สร้าง user (ใช้ phone/name ที่มี) + wallet
- [ ] Send LINE/FB notification "เติมเงิน X บาทเรียบร้อย"

### Step 6: Wallet UI in chat
- [ ] เมื่อบิล pending → แสดงยอด wallet ปัจจุบัน + "พอ/ไม่พอ"
- [ ] ถ้าพอ → ปุ่ม "ใช้ wallet จ่าย" → หักทันที
- [ ] ถ้าไม่พอ → แสดง "ขาดอีก X บาท กรุณาโอนเพิ่ม"

---

## 🚨 Risks

1. **Race condition** — 2 ลูกค้าโอนยอดใกล้เคียงในเวลาใกล้กัน → ต้อง lock ด้วย DB transaction
2. **SMS delay** — บางธนาคารส่ง SMS ช้า 1-5 นาที → user impatient
3. **Wallet abuse** — ใครได้สิทธิเข้า wallet ใคร? ต้อง access control
4. **FCM token ตาย** — ถ้า device ไม่ online นาน → ต้อง fallback polling
5. **UPA legacy** — บิลเก่ายังมีทศนิยม → migration script ต้อง handle

---

## 📁 Files ที่จะกระทบ

- `app/Services/SmsPaymentService.php` — main logic
- `app/Services/WalletService.php` — เพิ่ม deductForBill()
- `app/Services/Fortune/CelticCrossConversationTrait.php`
- `app/Services/FortuneConversationService.php` — handlePendingPayment
- `app/Services/FortuneChannelManager.php`
- `app/Models/FortuneReading.php` — add wallet_transaction_id
- `app/Models/SmsCheckerDevice.php` — fcm_token usage
- `app/Http/Controllers/Api/V1/SmsPaymentController.php`
- ใหม่: `app/Services/SmsCheckerNotifier.php` — FCM sender
- ใหม่: migration เพิ่ม `wallet_transaction_id` ใน fortune_readings, `fcm_token` ใน sms_checker_devices ถ้ายังไม่มี

---

## 🎯 ขั้นตอนถัดไป

เซสชันหน้า: **อ่านไฟล์นี้ก่อน** แล้วถามคำถาม H1/H3/H4/H5 ก่อนเริ่มลงมือ
