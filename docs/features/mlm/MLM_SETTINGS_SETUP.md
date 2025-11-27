# MLM Settings Setup Guide

## ปัญหา: หน้า MLM Settings ไม่แสดงฟอร์มการตั้งค่า

หากคุณเข้าไปที่ `/admin/mlm/settings` แล้วไม่เห็นฟอร์มการตั้งค่าใดๆ แสดงว่า **ข้อมูลใน database ยังไม่มี**

---

## วิธีแก้ไข

### ขั้นตอนที่ 1: รัน Seeder เพื่อสร้างข้อมูลเริ่มต้น

เลือกวิธีใดวิธีหนึ่ง:

#### ✅ วิธีที่ 1: รัน Seeder เฉพาะ MLM Settings (แนะนำ)

```bash
php artisan db:seed --class=MlmGlobalSettingsSeeder
```

#### วิธีที่ 2: รัน Seeder ทั้งหมด

```bash
php artisan db:seed
```

⚠️ **คำเตือน:** การรัน `php artisan db:seed` จะสร้างข้อมูลตัวอย่างทั้งหมดใหม่

---

### ขั้นตอนที่ 2: ตรวจสอบว่าข้อมูลถูกสร้างแล้ว

```bash
# ตรวจสอบจำนวนการตั้งค่า
php artisan tinker
>>> \App\Models\MlmGlobalSetting::count();
# ควรได้ประมาณ 30+ records

>>> \App\Models\MlmGlobalSetting::where('is_visible', true)->count();
# ควรได้เท่ากัน

>>> \App\Models\MlmGlobalSetting::first();
# ควรเห็นข้อมูล setting แรก
```

---

### ขั้นตอนที่ 3: Refresh หน้า MLM Settings

1. เปิดเบราว์เซอร์ไปที่: `http://your-domain/admin/mlm/settings`
2. กด Refresh (F5)
3. ควรเห็นฟอร์มการตั้งค่าแบ่งเป็นกลุ่ม:
   - ✅ General (ทั่วไป)
   - ✅ PV (ระบบ PV)
   - ✅ Unilevel
   - ✅ Binary
   - ✅ Flush (การล้าง PV)
   - ✅ Placement (การจัดวาง)
   - ✅ Roll-up (การปันผลข้าม)
   - ✅ Retention (การรักษายอด)
   - ✅ Commission (คอมมิชชั่น)

---

## การตั้งค่าที่ถูกสร้างโดย Seeder

จำนวนการตั้งค่าทั้งหมด: **30+ settings**

### กลุ่ม General (3 settings)
- `mlm_enabled` - เปิด/ปิดระบบ MLM
- `mlm_system_name` - ชื่อระบบ
- `default_currency` - สกุลเงิน

### กลุ่ม PV (2 settings)
- `global_pv_rate` - อัตราแปลง THB เป็น PV
- `commission_per_pv` - ค่าคอมต่อ 1 PV

### กลุ่ม Unilevel (4 settings)
- `unilevel_max_depth` - ระดับสูงสุด
- `unilevel_levels` - เปอร์เซ็นต์แต่ละชั้น (JSON)
- `unilevel_compression_enabled` - เปิด compression
- `unilevel_max_commission_per_level` - จำกัดคอมต่อชั้น

### กลุ่ม Binary (6 settings)
- `binary_pair_commission` - ค่าคอมต่อคู่
- `binary_match_percentage` - เปอร์เซ็นต์จับคู่
- `binary_max_pairs_per_day` - จำนวนคู่สูงสุดต่อวัน
- `binary_max_commission_per_day` - คอมสูงสุดต่อวัน
- `binary_spillover_enabled` - เปิด spillover
- `binary_pairing_type` - ประเภทการจับคู่ (1:1 หรือ 2:1)

### กลุ่ม Flush (4 settings)
- `binary_flush_mode` - โหมดการล้าง (percentage, full, none)
- `binary_flush_percentage` - เปอร์เซ็นต์การล้าง
- `binary_carry_forward_enabled` - เปิดการยกยอด
- `binary_carry_forward_days` - จำนวนวันที่ยกยอด

### กลุ่ม Placement (2 settings)
- `auto_placement_enabled` - เปิดการจัดวางอัตโนมัติ
- `auto_placement_strategy` - กลยุทธ์การจัดวาง

### กลุ่ม Roll-up (3 settings)
- `rollup_enabled` - เปิดระบบ roll-up
- `rollup_prevent_duplicate` - ป้องกันได้ซ้ำ
- `rollup_max_levels` - จำนวนชั้นสูงสุดที่ roll-up

### กลุ่ม Retention (3 settings)
- `volume_retention_enabled` - เปิดระบบรักษายอด
- `volume_retention_monthly_pv` - PV ต้องรักษาต่อเดือน
- `volume_retention_grace_days` - วันผ่อนผัน

### กลุ่ม Commission (2 settings)
- `overpay_protection_enabled` - เปิดป้องกัน overpay
- `max_commission_percentage` - เปอร์เซ็นต์คอมรวมสูงสุด

---

## Troubleshooting

### ปัญหา: ยังไม่เห็นฟอร์มหลังรัน seeder

```bash
# 1. ตรวจสอบว่า migration รันแล้วหรือยัง
php artisan migrate:status

# 2. ตรวจสอบตาราง mlm_global_settings มีอยู่หรือไม่
php artisan tinker
>>> Schema::hasTable('mlm_global_settings');

# 3. ตรวจสอบข้อมูลใน database
>>> \App\Models\MlmGlobalSetting::all();
```

### ปัญหา: Migration ยังไม่รัน

```bash
# รัน migration
php artisan migrate

# รัน seeder ใหม่
php artisan db:seed --class=MlmGlobalSettingsSeeder
```

---

## ค่าเริ่มต้นที่แนะนำ

การตั้งค่าที่ถูก seed จะมีค่าเริ่มต้นที่ปลอดภัย:

- **Unilevel:** 10 ชั้น, เริ่มจาก 10% → 5% → 3% → 2% → 1%...
- **Binary:** 100 บาท/คู่, 50% matching
- **Commission Max:** 50% (ป้องกัน overpay)
- **Retention:** 100 PV/เดือน, 7 วันผ่อนผัน

คุณสามารถปรับค่าเหล่านี้ได้ตามต้องการในหน้า MLM Settings

---

## หมายเหตุ

- การตั้งค่าทั้งหมดมี **caching** เพื่อเพิ่มประสิทธิภาพ
- เมื่อแก้ไขการตั้งค่า cache จะถูกล้างอัตโนมัติ
- การตั้งค่าบางอย่างเป็น **read-only** (is_editable = false) เพื่อป้องกันระบบ
