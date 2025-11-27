# 🚨 แก้ไขด่วน: Footer Logo Animation Column

## ปัญหา

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'footer_logo_animation' in 'SET'
```

## ✅ วิธีแก้ไข (เลือก 1 วิธี)

### วิธีที่ 1: รัน Migration (แนะนำ ⭐)

```bash
cd /path/to/Thaiprompt-Affiliate
php artisan migrate --force
```

### วิธีที่ 2: ใช้ Deploy Script (ง่ายที่สุด 🚀)

```bash
cd /path/to/Thaiprompt-Affiliate
./deploy.sh
```

### วิธีที่ 3: รัน SQL โดยตรง (ถ้า artisan ไม่ทำงาน)

```bash
# 1. เข้า MySQL
mysql -u root -p

# 2. เลือก database
USE thaiprompt_affiliate;  # เปลี่ยนชื่อตามที่ใช้จริง

# 3. เพิ่มคอลัมน์
ALTER TABLE theme_settings
ADD COLUMN footer_logo_animation
ENUM('none', 'float', 'spin', 'bounce', 'pulse', 'swing')
DEFAULT 'float'
COMMENT 'Footer Logo Animation Style';

# 4. ตรวจสอบ
SHOW COLUMNS FROM theme_settings LIKE 'footer_logo_animation';
```

### วิธีที่ 4: ใช้ SQL Script สำเร็จรูป

```bash
# แก้ไขชื่อ database ใน file
nano FIX_THEME_SETTINGS_COLUMNS.sql
# เปลี่ยน: USE thaiprompt_affiliate; เป็นชื่อที่ใช้จริง

# รัน script
mysql -u root -p < FIX_THEME_SETTINGS_COLUMNS.sql
```

## 🔍 ตรวจสอบว่าแก้สำเร็จ

```bash
# ตรวจสอบผ่าน artisan
php artisan migrate:status | grep "add_background_effects"

# หรือตรวจสอบผ่าน MySQL
mysql -u root -p -e "SHOW COLUMNS FROM theme_settings LIKE 'footer_logo_animation';"
```

## 📋 คอลัมน์ที่จะถูกเพิ่ม

Migration นี้จะเพิ่มคอลัมน์ทั้งหมด 13 คอลัมน์:

1. ✅ `footer_logo_path` - เส้นทางโลโก้มุมล่างซ้าย
2. ✅ `footer_logo_animation` - ⭐ Animation โลโก้ (none/float/spin/bounce/pulse/swing)
3. ✅ `bg_effects_enabled` - เปิด/ปิด background effects
4. ✅ `bg_circle1_color1` - สีวงกลมที่ 1 เริ่มต้น (#22d3ee)
5. ✅ `bg_circle1_color2` - สีวงกลมที่ 1 สิ้นสุด (#2563eb)
6. ✅ `bg_circle2_color1` - สีวงกลมที่ 2 เริ่มต้น (#f472b6)
7. ✅ `bg_circle2_color2` - สีวงกลมที่ 2 สิ้นสุด (#9333ea)
8. ✅ `bg_circle3_color1` - สีวงกลมที่ 3 เริ่มต้น (#fbbf24)
9. ✅ `bg_circle3_color2` - สีวงกลมที่ 3 สิ้นสุด (#f97316)
10. ✅ `bg_animation_speed` - ความเร็ว (slow/normal/fast)
11. ✅ `bg_circle_opacity` - ความโปร่งใส (0-100)
12. ✅ `bg_circle_blur` - Blur intensity (0-200)
13. ✅ `bg_circle_size` - ขนาดวงกลม (200-800 px)

## 🎯 Migration File

**ไฟล์:** `database/migrations/2025_11_17_000001_add_background_effects_to_theme_settings_table.php`

Migration นี้มีการเช็คคอลัมน์อัตโนมัติ:
- ✅ เช็คว่าตารางมีอยู่ก่อน
- ✅ เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
- ✅ เพิ่มเฉพาะคอลัมน์ที่ยังไม่มี
- ✅ ปลอดภัย ไม่ลบข้อมูลเดิม

## 🚨 สำคัญ!

**สำหรับ Production Server:**
1. ⚠️ **Backup database ก่อนเสมอ!**
   ```bash
   mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. ✅ **ใช้ --force flag**
   ```bash
   php artisan migrate --force
   ```

3. ✅ **ทดสอบหลัง deploy**
   - เข้า Admin → Theme Settings
   - เลือก Footer Logo Animation
   - กด Save
   - Refresh และตรวจสอบว่าค่ายังอยู่

## 📚 เอกสารเพิ่มเติม

- **FIX_THEME_SETTINGS_README.md** - คำอธิบายละเอียด
- **FIX_THEME_SETTINGS_COLUMNS.sql** - SQL script สำเร็จรูป
- **DEPLOY_THEME_SETTINGS.md** - คำอธิบาย background effects

---

**สรุป:** ใช้ `./deploy.sh` หรือ `php artisan migrate --force` จะแก้ได้ทันที! 🚀
