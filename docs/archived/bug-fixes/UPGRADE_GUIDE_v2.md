# คู่มือการอัพเกรดสู่ v2.0.0 "Phoenix"

## 📋 สารบัญ
- [ภาพรวมการเปลี่ยนแปลง](#ภาพรวมการเปลี่ยนแปลง)
- [ความต้องการของระบบ](#ความต้องการของระบบ)
- [ขั้นตอนการอัพเกรด](#ขั้นตอนการอัพเกรด)
- [Breaking Changes](#breaking-changes)
- [คุณสมบัติใหม่](#คุณสมบัติใหม่)
- [การแก้ไขปัญหาที่พบบ่อย](#การแก้ไขปัญหาที่พบบ่อย)

---

## 🎉 ภาพรวมการเปลี่ยนแปลง

Version 2.0.0 "Phoenix" เป็นการอัพเดทครั้งใหญ่ที่นำเสนอ:

### 🌟 ระบบ Theme v2
- ระบบธีมใหม่แบบ Line OA ที่ยืดหยุ่นและใช้งานง่าย
- รองรับ Dark/Light Mode แบบสมจริง
- Admin สามารถสร้าง theme ของตัวเองได้
- User สามารถเลือก theme ที่ชอบได้
- มี 6 theme presets สำเร็จรูป

### 🔄 ระบบ Update อัตโนมัติ
- ตรวจสอบและอัพเดทจาก GitHub อัตโนมัติ
- ระบบสำรองข้อมูลและ rollback
- แจ้งเตือนเมื่อมีเวอร์ชันใหม่

---

## 💻 ความต้องการของระบบ

### ขั้นต่ำ
- **PHP**: >= 8.1.0
- **MySQL**: >= 8.0.0
- **Laravel**: 11.0
- **Disk Space**: อย่างน้อย 500MB สำหรับ backups
- **Memory**: อย่างน้อย 256MB

### แนะนำ
- **PHP**: 8.2+
- **MySQL**: 8.0.30+
- **Memory**: 512MB+
- **SSD Storage**: สำหรับประสิทธิภาพที่ดีขึ้น

---

## 🚀 ขั้นตอนการอัพเกรด

### ⚠️ ขั้นตอนที่ 1: สำรองข้อมูล (CRITICAL!)

```bash
# 1. สำรองฐานข้อมูล
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. สำรองไฟล์โปรเจค
tar -czf project_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/project

# 3. สำรองไฟล์ .env
cp .env .env.backup_$(date +%Y%m%d_%H%M%S)

# 4. สำรอง storage
tar -czf storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/
```

### 📥 ขั้นตอนที่ 2: Pull Code ใหม่

```bash
# 1. Fetch ข้อมูลล่าสุด
git fetch origin

# 2. Checkout branch ใหม่ (ถ้ามี)
git checkout claude/prepare-v2-update-011CUtch2PvnQdtf6JErcaFF

# 3. Pull code ล่าสุด
git pull origin claude/prepare-v2-update-011CUtch2PvnQdtf6JErcaFF
```

### 🗄️ ขั้นตอนที่ 3: Update Database

```bash
# 1. Run migrations
php artisan migrate

# 2. Seed default themes
php artisan db:seed --class=ThemeSeeder

# 3. (Optional) Initialize update system
php artisan db:seed --class=UpdateSeeder
```

### 🧹 ขั้นตอนที่ 4: Clear Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ✅ ขั้นตอนที่ 5: ตรวจสอบระบบ

```bash
# 1. ตรวจสอบ PHP version
php -v

# 2. ตรวจสอบ extensions ที่จำเป็น
php -m | grep -E 'pdo|mysql|gd|zip|mbstring|json'

# 3. ตรวจสอบ permissions
ls -la storage/
ls -la bootstrap/cache/

# 4. ทดสอบเข้าระบบ
# เปิดเบราว์เซอร์และทดสอบการเข้าสู่ระบบ
```

---

## ⚠️ Breaking Changes

### 1. Theme System
**เดิม (v1.x):**
```php
// ใช้ settings table สำหรับเก็บสี
Setting::get('theme_primary_start', '#3B82F6');
```

**ใหม่ (v2.0):**
```php
// ใช้ Theme model และ ThemeService
$theme = auth()->user()->currentTheme();
$css = $theme->generateCss('light');
```

**วิธีแก้ไข:**
- ระบบจะ migrate settings เดิมให้อัตโนมัติ
- User อาจต้องเลือก theme ใหม่หลัง upgrade
- Admin ควรตั้งค่า default theme

### 2. Layout Files
**เดิม (v1.x):**
```blade
{{-- สี hardcode ใน layout --}}
<div class="bg-blue-500">
```

**ใหม่ (v2.0):**
```blade
{{-- ใช้ CSS variables จาก theme --}}
<div class="bg-[var(--color-primary)]">
```

**วิธีแก้ไข:**
- Layout files จะได้รับการอัพเดทอัตโนมัติ
- ถ้ามี custom templates ต้องปรับให้ใช้ CSS variables

### 3. Dark Mode
**เดิม (v1.x):**
```blade
{{-- Dark mode แบบ Tailwind class --}}
<div class="bg-white dark:bg-gray-800">
```

**ใหม่ (v2.0):**
```blade
{{-- Dark mode ผ่าน theme system --}}
<div class="bg-[var(--color-bg-primary)]">
```

**วิธีแก้ไข:**
- ระบบจะจัดการ dark mode ผ่าน theme
- Custom components อาจต้องปรับให้รองรับ theme variables

---

## 🎨 คุณสมบัติใหม่

### 1. Theme Management (Admin)

#### เข้าถึงระบบ Theme
```
/admin/themes
```

#### สร้าง Theme ใหม่
1. ไปที่ Admin > Themes
2. คลิก "สร้าง Theme ใหม่"
3. เลือก Preset หรือออกแบบเอง
4. ปรับแต่งสี, ฟอนต์, ระยะห่าง
5. บันทึก

#### ตั้งค่า Default Theme
1. ไปที่ Admin > Themes
2. เลือก Theme ที่ต้องการ
3. คลิก "ตั้งเป็น Theme เริ่มต้น"

### 2. Theme Selection (User)

#### เลือก Theme
```
/user/themes
```

1. ไปที่ Dashboard > Themes
2. เลือก Theme ที่ชอบ
3. เลือก Mode (Light/Dark/Auto)
4. บันทึก

### 3. Update System (Admin)

#### ตรวจสอบ Updates
```
/admin/updates
```

1. ไปที่ Admin > Updates
2. คลิก "ตรวจสอบ Updates"
3. ดูรายละเอียด Changelog
4. คลิก "Install Update"

#### ดู Update History
```
/admin/updates/logs
```

#### Rollback (ถ้าจำเป็น)
```
/admin/updates/logs/{id}/rollback
```

---

## 🔧 การแก้ไขปัญหาที่พบบ่อย

### ปัญหา: Migration Failed

**อาการ:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'themes' already exists
```

**วิธีแก้:**
```bash
# 1. ตรวจสอบ migrations ที่รันไปแล้ว
php artisan migrate:status

# 2. Rollback และรันใหม่
php artisan migrate:rollback --step=1
php artisan migrate

# 3. ถ้ายังไม่ได้ ลอง fresh (WARNING: จะลบข้อมูลทั้งหมด!)
php artisan migrate:fresh --seed
```

### ปัญหา: Theme ไม่แสดง

**อาการ:**
- สีไม่ขึ้น
- Layout พัง

**วิธีแก้:**
```bash
# 1. Clear cache
php artisan cache:clear
php artisan view:clear

# 2. Regenerate theme
php artisan db:seed --class=ThemeSeeder

# 3. ตรวจสอบ default theme
# ใน Admin > Themes ตรวจสอบว่ามี default theme
```

### ปัญหา: Permission Denied

**อาการ:**
```
file_put_contents(...): failed to open stream: Permission denied
```

**วิธีแก้:**
```bash
# 1. ตั้งค่า permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/

# 2. สำหรับ development (localhost)
sudo chown -R $USER:www-data storage/
sudo chown -R $USER:www-data bootstrap/cache/
```

### ปัญหา: CSS Variables ไม่ทำงาน

**อาการ:**
- CSS variables เช่น `var(--color-primary)` ไม่แสดงสี

**วิธีแก้:**
```blade
{{-- ตรวจสอบว่ามี style tag ใน layout --}}
@php
    $themeService = app(\App\Services\ThemeService::class);
    $css = $themeService->getCssForUser(auth()->id());
@endphp
<style>{!! $css !!}</style>
```

### ปัญหา: Update System ไม่ทำงาน

**อาการ:**
- ตรวจสอบ update ไม่ได้
- Error: "Failed to fetch updates"

**วิธีแก้:**
```bash
# 1. ตรวจสอบ internet connection
curl -I https://api.github.com

# 2. ตรวจสอบ config
php artisan config:show version

# 3. Clear cache
php artisan cache:clear

# 4. ตรวจสอบ permissions
ls -la storage/backups/
ls -la storage/updates/
```

---

## 📞 ติดต่อและรับการสนับสนุน

หากพบปัญหาหรือต้องการความช่วยเหลือ:

### GitHub Issues
https://github.com/xjanova/Thaiprompt-Affiliate/issues

### Documentation
https://github.com/xjanova/Thaiprompt-Affiliate/wiki

### Email Support
support@example.com (ถ้ามี)

---

## 🎯 Tips และ Best Practices

### 1. Theme Development
- ใช้ CSS variables แทนการ hardcode สี
- ทดสอบใน Dark Mode ด้วยเสมอ
- สร้าง theme presets สำหรับองค์กร

### 2. Update Management
- ตรวจสอบ changelog ก่อน update
- สำรองข้อมูลทุกครั้งก่อน update
- ทดสอบใน staging environment ก่อน production

### 3. Performance
- ใช้ theme caching
- Optimize CSS variables
- ใช้ CDN สำหรับ assets

### 4. Security
- อัพเดทเป็นเวอร์ชันล่าสุดเสมอ
- ตรวจสอบ permissions อย่างสม่ำเสมอ
- ใช้ HTTPS เสมอ

---

## 📚 เอกสารเพิ่มเติม

- [CHANGELOG.md](CHANGELOG.md) - รายละเอียดการเปลี่ยนแปลงทั้งหมด
- [README.md](README.md) - เอกสารหลักของโปรเจค
- [Theme Development Guide](docs/THEME_DEVELOPMENT.md) - คู่มือพัฒนา theme
- [Update System Guide](docs/UPDATE_SYSTEM.md) - คู่มือระบบ update

---

**สุดท้าย**: การอัพเกรดเป็น v2.0.0 จะทำให้คุณได้รับประสบการณ์ที่ดีขึ้น พร้อมคุณสมบัติใหม่ๆ ที่ทันสมัย อย่าลืมสำรองข้อมูลก่อนอัพเกรดทุกครั้ง!

🔥 **Version 2.0.0 "Phoenix"** - การเกิดใหม่ของ Thai Prompt Affiliate Marketing Platform! 🔥
