# MLM Money Flow & Commission Calculation Reference

> **เอกสารอ้างอิงหลัก** สำหรับ Flow การเงินและการคำนวณคอมมิชชั่นของระบบ MLM
>
> **Version:** 2.0.0 | **อัพเดทล่าสุด:** 2026-02-06

---

## ⚠️ Business Logic สำคัญ (ต้องอ่านก่อน!)

### หลักการแบ่งเงิน

```
┌─────────────────────────────────────────────────────────────┐
│  1. Platform Fee  = ของเว็บ 100%                            │
│     → ไปจ่าย Pool Bonus, All Sale Bonus, Rank Bonus ภายหลัง │
│                                                             │
│  2. MLM Commission = คำนวณจาก PV                            │
│     → ไปจ่ายคอมมิชชั่น uplines (คนละก้อนกับ Fee)            │
│                                                             │
│  3. Fee กับ MLM แยกจากกันอย่างเด็ดขาด                       │
│     → ไม่ได้เอา Fee มาจ่าย MLM                              │
│     → ไม่ได้เอา MLM ไปรวมกับ Fee                             │
│                                                             │
│  4. Seller เห็นระบบจำลองครบถ้วน                              │
│     → รู้ล่วงหน้าว่าจะถูกหักเท่าไหร่                        │
│     → ตั้งราคาขายให้ครอบคลุมค่าใช้จ่ายทั้งหมดเอง            │
└─────────────────────────────────────────────────────────────┘
```

### สูตรของ Seller

```
Seller ได้รับ = ยอดขาย - Platform Fee - VAT - MLM Commission
```

**Seller ไม่ควรตั้งราคาต่ำจนยอดสุทธิติดลบ** → ระบบจำลอง (Calculator) ช่วยให้เห็นก่อนตั้งราคา

---

## 1. Flow หลัก: Order → แบ่งเงิน

### Service: `OrderDistributionService::processOrderDistribution()`

```
ลูกค้าจ่ายเงิน (Order.payment_status = 'paid')
│
├── แยกรายการตาม Seller ID
│   ├── Official Shop (Admin)  → processAdminShopItems()
│   ├── Seller ทั่วไป          → processSellerItems()     ← จุดหลัก
│   └── Admin Services         → processAdminServices()
│
└── ถ้า mlm_enabled = true     → processMlmCommissions()
```

### ตัวอย่างตัวเลข (Seller ทั่วไป)

```
สินค้าราคา ฿10,000 / Platform Fee Rate 15% / VAT 7% (inclusive)
Global PV Rate = 1 / Commission Per PV = 1

┌─────────────────────────────────────────────────────────────┐
│  Gross Amount (ยอดขาย)                       = ฿10,000.00  │
│                                                             │
│  หัก 1. Platform Fee (15%)                   = ฿1,500.00   │
│        → เข้า Platform Wallet (ของเว็บ 100%)               │
│        → ใช้จ่าย: Pool Bonus, Rank Bonus ภายหลัง           │
│                                                             │
│  หัก 2. VAT (7% inclusive: 10000×7/107)      = ฿654.21     │
│        → เข้า VAT Wallet                                   │
│                                                             │
│  หัก 3. MLM Commission (PV × commission/pv)  = ฿10,000.00  │
│        → เข้า MLM Pool Wallet                              │
│        → กระจายจ่ายคอมมิชชั่นให้ uplines                    │
│                                                             │
│  ⚠️ กรณีนี้: ยอดสุทธิติดลบ! (10000 - 1500 - 654 - 10000)  │
│  → ระบบจะ log warning + ตั้ง net = 0                       │
│  → Seller ควรตั้งราคาสูงกว่านี้ หรือแอดมินควรปรับ PV Rate  │
│                                                             │
│  Seller ได้รับ                               = ฿0.00       │
└─────────────────────────────────────────────────────────────┘
```

### ตัวอย่างที่สมดุล (ตั้งราคาถูกต้อง)

```
สินค้าราคา ฿10,000 / Platform Fee Rate 10% / VAT 7% (inclusive)
Global PV Rate = 0.1 / Commission Per PV = 1

┌─────────────────────────────────────────────────────────────┐
│  Gross Amount                                = ฿10,000.00  │
│  Platform Fee (10%)                          = ฿1,000.00   │
│  VAT (7%: 10000×7/107)                       = ฿654.21     │
│  MLM Commission (PV=1000 × 1)               = ฿1,000.00   │
│                                                             │
│  Seller ได้รับ = 10000 - 1000 - 654.21 - 1000 = ฿7,345.79 │
│                                                             │
│  เงินไปไหน:                                                │
│  ├── ฿7,345.79 → Seller Wallet                             │
│  ├── ฿1,000.00 → Platform Wallet (pool/rank bonus)         │
│  ├── ฿654.21   → VAT Wallet                                │
│  └── ฿1,000.00 → MLM Pool Wallet (upline commission)       │
│  รวม: ฿10,000.00 ✓                                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. การคำนวณ PV (Point Value)

### Service: `OrderDistributionService::calculateTotalPvFromOrder()`

```
สำหรับแต่ละ Order Item:
│
├── ลำดับ 1: ดึง MlmProductPv (แอดมินกำหนดต่อสินค้า)
│   → PV = pv_value × quantity
│   → เช่น สินค้า A มี pv_value = 50, ซื้อ 2 ชิ้น → PV = 100
│
└── ลำดับ 2 (Fallback): ใช้ global_pv_rate
    → PV = item.total × global_pv_rate
    → เช่น total = ฿1,000 × rate 0.1 → PV = 100
```

### สูตร PV

| สูตร | รายละเอียด |
|------|-----------|
| **PV (จากสินค้า)** | `MlmProductPv.pv_value × quantity` |
| **PV (จาก global rate)** | `item.total × global_pv_rate` |
| **PV Amount (THB)** | `Total PV × commission_per_pv` |

### Settings ที่เกี่ยวข้อง

| Key | ค่าเริ่มต้น | คำอธิบาย |
|-----|-------------|---------|
| `global_pv_rate` | 1 | อัตราแปลงราคา → PV (ต้อง >= 0.01) |
| `commission_per_pv` | 1 | อัตราแปลง PV → บาท (ต้อง > 0) |

---

## 3. การจ่ายคอมมิชชั่น Uplines

### Service: `MlmCommissionService::processOrderCommissions()`

```
Order ชำระแล้ว
│
├── 1. Duplicate Guard
│   → ตรวจว่า Order นี้คำนวณไปแล้วหรือยัง
│   → ถ้าคำนวณแล้ว → SKIP
│
├── 2. Direct Referral Bonus (ค่าแนะนำตรง)
│   → MlmReferralBonusService
│   → จ่ายให้ original_sponsor (ผู้แนะนำตรงจริงๆ)
│   → ไม่ใช่ unilevel_sponsor (ที่อาจเปลี่ยนจาก spillover)
│
└── 3. Unilevel + Binary Commission
    → calculateCommissionsWithRollup()
    │
    ├── Unilevel Commission (ตามชั้น)
    ├── Binary Commission (pair matching)
    └── Overpay Protection (scale down ถ้าเกิน)
```

---

## 4. Unilevel Commission

### Service: `MlmCommissionService::calculateUnilevelWithRollup()`

### สูตร

```
Commission = (PV × percentage / 100) × commission_per_pv × rank.bonus_multiplier
```

### ตัวอย่าง

```
PV = 1,000 / commission_per_pv = 1
Unilevel Levels: [{level:1, %:10}, {level:2, %:5}, {level:3, %:3}]

ผู้ซื้อ (Member)
│
├── Level 1: Sponsor A (Active, Gold rank × 1.5)
│   = (1000 × 10 / 100) × 1 × 1.5 = ฿150
│
├── Level 2: Sponsor B (Active, Silver rank × 1.0)
│   = (1000 × 5 / 100) × 1 × 1.0 = ฿50
│
└── Level 3: Sponsor C (Active, Bronze rank × 1.0)
    = (1000 × 3 / 100) × 1 × 1.0 = ฿30

รวม = ฿150 + ฿50 + ฿30 = ฿230
```

### Rollup Logic (เมื่อ Upline ไม่ Active)

```
Level 2: Sponsor B (INACTIVE - ไม่รักษายอด PV)
│
├── Rollup เปิด (rollup_enabled = true)?
│   ├── Yes → หา upline ที่ active ถัดไป
│   │   ├── เช็ค rollup_max_per_member (ป้องกันคนเดียวได้เยอะเกิน)
│   │   ├── เช็ค rollup_prevent_duplicate (ป้องกันจ่ายซ้ำ)
│   │   ├── พบ Active Upline → จ่ายให้คนนั้น (type: unilevel_rollup)
│   │   └── ไม่พบ →
│   │       ├── rollup_to_pool_enabled = true → ส่งไป Pool Bonus
│   │       └── rollup_to_pool_enabled = false → ส่งไป Admin (fallback)
│   │
│   └── No → ข้ามไปเลย (commission ของชั้นนี้หายไป)
│
└── บันทึก rollup_chain สำหรับ tracking ย้อนหลัง
```

### Settings ที่เกี่ยวข้อง

| Key | ค่าเริ่มต้น | คำอธิบาย |
|-----|-------------|---------|
| `unilevel_enabled` | true | เปิด/ปิดระบบ Unilevel |
| `unilevel_levels` | JSON array | กำหนดชั้นและ % เช่น `[{level:1, percentage:10}]` |
| `unilevel_max_depth` | 10 | จำนวนชั้นสูงสุดที่จ่ายคอมมิชชั่น |
| `unilevel_max_commission_per_level` | null | จำกัดคอมมิชชั่นสูงสุดต่อชั้น (บาท) |
| `rollup_enabled` | false | เปิด/ปิดระบบ rollup |
| `rollup_max_per_member` | 1 | จำนวน rollup สูงสุดที่คนเดียวจะได้ต่อ transaction |
| `rollup_to_pool_enabled` | true | ส่ง rollup ที่หาคนไม่ได้ไป Pool Bonus |
| `max_rank_bonus_multiplier` | 5.0 | cap สูงสุดของ rank multiplier |

---

## 5. Binary Commission

### Service: `MlmBinaryService`

### สูตร Pair Matching

```
Pairing Type 1:1:
  pairs = min(leftPv, rightPv) / pv_per_pair
  commission = pairs × commission_per_pair

Pairing Type 2:1:
  pairs = min(weakerLeg, floor(strongerLeg / 2))
  commission = pairs × commission_per_pair
```

### ตัวอย่าง (1:1)

```
         Member A
        /        \
   Left Leg    Right Leg
   PV: 500     PV: 300

pairs = min(500, 300) = 300 PV ÷ 100 (per pair) = 3 pairs
commission = 3 × ฿100 = ฿300

Carry Forward:
  Left: 500 - 300 = 200 PV (เก็บไว้ครั้งหน้า)
  Right: 300 - 300 = 0 PV
  → Carried PV หมดอายุตาม binary_carry_forward_expiry_days
```

### Settings ที่เกี่ยวข้อง

| Key | ค่าเริ่มต้น | คำอธิบาย |
|-----|-------------|---------|
| `binary_enabled` | false | เปิด/ปิดระบบ Binary |
| `binary_pair_commission` | 100 | คอมมิชชั่นต่อ pair (บาท) |
| `binary_match_percentage` | 50 | % การ match |
| `binary_max_pairs_per_day` | null | จำกัดจำนวนคู่ต่อวัน |
| `binary_max_commission_per_day` | null | จำกัดคอมมิชชั่นต่อวัน (บาท) |
| `binary_pairing_type` | "1:1" | วิธีจับคู่: 1:1 หรือ 2:1 |
| `binary_carry_forward_expiry_days` | 30 | วันหมดอายุ carried PV |

---

## 6. Overpay Protection (3 ชั้น)

### Service: `OverpayProtectionService`

```
┌──────────────────────────────────────────────────────────────┐
│  ชั้นที่ 1: Settings Validation (ก่อนบันทึกค่า)              │
│  ─────────────────────────────────────────────               │
│  ตรวจสอบโดย: MlmGlobalSettingController + MlmGlobalSetting  │
│                                                              │
│  • global_pv_rate >= 0.01 (ห้ามเป็น 0 หรือลบ)              │
│  • commission_per_pv > 0                                     │
│  • max_commission_percentage อยู่ระหว่าง 0-100               │
│  • ผลรวม % ทุกชั้น × commission_per_pv <= max%              │
│  → ถ้าไม่ผ่าน: BLOCK การบันทึกทันที                          │
├──────────────────────────────────────────────────────────────┤
│  ชั้นที่ 2: Runtime Guard (ก่อนจ่ายจริง)                     │
│  ─────────────────────────────────────────                   │
│  ตรวจสอบโดย: MlmCommissionService                           │
│                                                              │
│  สูตร: Max Allowed = PV × commission_per_pv × (max% / 100) │
│  ถ้า Total Commission > Max Allowed:                        │
│    → Scale down ทุก commission ตามสัดส่วน                    │
│    → ratio = Max Allowed / Total Commission                 │
│    → แต่ละคนได้: commission × ratio                         │
├──────────────────────────────────────────────────────────────┤
│  ชั้นที่ 3: Seller Net Check (ก่อนหักเงิน)                   │
│  ──────────────────────────────────────────                  │
│  ตรวจสอบโดย: OrderDistributionService                       │
│                                                              │
│  Seller Net = Gross - Fee - VAT - MLM                       │
│  ถ้า Net < 0:                                               │
│    → Log warning (ราคาสินค้าตั้งต่ำเกินไป)                  │
│    → ตั้ง Net = 0 (Seller ไม่ติดลบ)                          │
│    → Admin ควรตรวจสอบและปรับค่า PV Rate                      │
└──────────────────────────────────────────────────────────────┘
```

### Settings ที่เกี่ยวข้อง

| Key | ค่าเริ่มต้น | คำอธิบาย |
|-----|-------------|---------|
| `max_commission_percentage` | 40 | % สูงสุดของ PV Pool ที่จ่ายได้ |

---

## 7. Platform Fee → Pool/Rank Bonus

### Platform Fee เป็นของเว็บ 100%

```
Platform Fee (จาก Seller)
│
├── Pool Bonus
│   → MlmPoolBonusService
│   → แบ่งตามช่วงเวลา (period) + rank
│   → สมาชิกที่ qualified + active ได้รับ
│
├── All Sale Bonus
│   → แบ่งตามยอดขายรวม
│
├── Rank Bonus
│   → ให้ตาม rank ที่ achieve
│   → bonus_multiplier ใช้เฉพาะ unilevel commission
│
└── Platform Operating Costs
    → ค่าใช้จ่ายดำเนินงานของเว็บ
```

---

## 8. สรุปสูตรทั้งหมด

| สูตร | รายละเอียด |
|------|-----------|
| **PV (จากสินค้า)** | `MlmProductPv.pv_value × quantity` |
| **PV (จาก global rate)** | `item.total × global_pv_rate` |
| **PV Pool (งบคอมมิชชั่น)** | `Total PV × commission_per_pv` |
| **Unilevel Commission** | `(PV × percentage / 100) × commission_per_pv × rank.bonus_multiplier` |
| **Binary Commission** | `pairs × commission_per_pair` |
| **Max Commission Allowed** | `PV × commission_per_pv × (max_commission_percentage / 100)` |
| **Seller Net** | `grossAmount - platformFee - vatAmount - mlmCommission` |
| **VAT (inclusive)** | `grossAmount × vatRate / (1 + vatRate)` |

---

## 9. Flow Diagram รวม

```
Customer จ่ายเงิน ฿10,000
         │
         ▼
  ┌──────────────┐
  │ Order (paid) │
  └──────┬───────┘
         │
    processOrderDistribution()
         │
         ▼
  ┌──────────────────────────────────────────────────┐
  │  แบ่งเงินจาก Order:                              │
  │                                                  │
  │  1. Platform Fee (10%)    = ฿1,000 → Platform    │
  │  2. VAT (7% incl.)       = ฿654   → VAT Wallet  │
  │  3. MLM Commission (PV)  = ฿1,000 → MLM Pool    │
  │  4. Seller Net            = ฿7,346 → Seller      │
  │                                                  │
  │  (แต่ละก้อนแยกจากกัน)                             │
  └──────────────────────────────────────────────────┘
         │
     (ถ้า mlm_enabled)
         │
         ▼
  ┌────────────────────────────────────┐
  │  processMlmCommissions()           │
  │  • คำนวณ PV จาก Order Items       │
  │  • บันทึก PV Transaction           │
  │  • อัพเดท member.total_pv         │
  └──────────┬─────────────────────────┘
             │
             ▼
  ┌────────────────────────────────────┐
  │  processOrderCommissions()          │
  │  ├── Duplicate Guard (ตรวจซ้ำ)     │
  │  ├── Direct Referral Bonus          │
  │  └── calculateCommissionsWithRollup │
  │      ├── Unilevel (rollup logic)    │
  │      ├── Binary (pair matching)     │
  │      └── Overpay Protection (scale) │
  └────────────────────────────────────┘
             │
             ▼
  ┌────────────────────────────────────┐
  │  MlmCommission records (pending)    │
  │  → Admin approve → จ่ายเข้า        │
  │    Wallet ของ uplines              │
  │  → จ่ายจาก MLM Pool Wallet         │
  └────────────────────────────────────┘

         │
  Platform Fee (แยกต่างหาก)
         │
         ▼
  ┌────────────────────────────────────┐
  │  Platform Wallet                    │
  │  → Pool Bonus (ตาม period/rank)    │
  │  → Rank Bonus                      │
  │  → All Sale Bonus                  │
  │  → Platform Operating Costs        │
  └────────────────────────────────────┘
```

---

## 10. Retention System (รักษายอด)

### Service: `MlmRetentionHelper::isMemberActive()`

```
ตรวจสอบว่าสมาชิก active หรือไม่:

1. volume_retention_enabled = false → ทุกคน active
2. member.status !== 'active' → ไม่ active
3. ยอด PV เดือนนี้ >= min_monthly_pv → active
4. อยู่ใน grace_period → ยัง active
5. นอกเหนือจากนี้ → ไม่ active
```

### Settings ที่เกี่ยวข้อง

| Key | คำอธิบาย |
|-----|---------|
| `volume_retention_enabled` | เปิด/ปิดระบบรักษายอด |
| `min_monthly_pv` | PV ขั้นต่ำต่อเดือน |
| `retention_grace_period_days` | จำนวนวัน grace period |

---

## 11. Clawback System (เรียกคืน)

### Service: `MlmCommissionClawbackService`

```
เมื่อ Order ถูก refund:
│
├── Commission ที่ยัง pending/approved
│   → ยกเลิกโดยตรง (status: cancelled)
│
└── Commission ที่จ่ายไปแล้ว (paid)
    → สร้าง WalletDebt
    → หักจากรายได้ครั้งถัดไปอัตโนมัติ
```

---

## 12. Files Reference

### Services (Business Logic)

| File | หน้าที่ |
|------|--------|
| `app/Services/OrderDistributionService.php` | แบ่งเงินจาก Order (Fee/VAT/MLM/Seller) |
| `app/Services/MlmCommissionService.php` | Production engine - Unilevel rollup + Binary + Overpay |
| `app/Services/MlmPvService.php` | จัดการ PV - คำนวณ, บันทึก, preview |
| `app/Services/MlmUnilevelService.php` | คำนวณ Unilevel Commission |
| `app/Services/MlmBinaryService.php` | คำนวณ Binary Commission + pair matching |
| `app/Services/MlmReferralBonusService.php` | Direct Referral Bonus (original_sponsor) |
| `app/Services/MlmPoolBonusService.php` | Pool Bonus distribution by rank |
| `app/Services/MlmCommissionClawbackService.php` | Clawback/reversal on refund |
| `app/Services/PlatformRevenueService.php` | จัดการ Platform Wallets (fee/vat/mlm pool) |
| `app/Services/OverpayProtectionService.php` | Smart validation 3 ชั้น |

### Models

| File | หน้าที่ |
|------|--------|
| `app/Models/MlmGlobalSetting.php` | การตั้งค่ากลาง (cache 1 ชม.) |
| `app/Models/MlmMember.php` | สมาชิก MLM + binary legs |
| `app/Models/MlmCommission.php` | บันทึกคอมมิชชั่น |
| `app/Models/MlmProductPv.php` | PV ต่อสินค้า |
| `app/Models/MlmPlan.php` | แผน MLM |

### Controllers

| File | หน้าที่ |
|------|--------|
| `app/Http/Controllers/Admin/MlmGlobalSettingController.php` | ตั้งค่า + Calculator preview |

### Helpers

| File | หน้าที่ |
|------|--------|
| `app/Helpers/MlmRetentionHelper.php` | ตรวจสอบ active status |

---

**Document Version:** 2.0.0
**Last Updated:** 2026-02-06
**Maintained By:** Development Team
