# 📌 คู่มือระบบเวอร์ชั่น (Versioning System Guide)

ระบบเวอร์ชั่นของ TP-Affiliate ออกแบบมาเพื่อให้การจัดการและอัปเดตระบบเป็นไปอย่างง่ายดายและมีมาตรฐาน

---

## 📋 สารบัญ

1. [Semantic Versioning](#semantic-versioning)
2. [การตรวจสอบเวอร์ชั่น](#การตรวจสอบเวอร์ชั่น)
3. [การอัปเดตระบบ](#การอัปเดตระบบ)
4. [Git Tags และ Releases](#git-tags-และ-releases)
5. [สำหรับผู้พัฒนา](#สำหรับผู้พัฒนา)
6. [คำถามที่พบบ่อย](#คำถามที่พบบ่อย)

---

## 🔢 Semantic Versioning

โปรเจกต์นี้ใช้ **Semantic Versioning 2.0.0** (SemVer) ในรูปแบบ `MAJOR.MINOR.PATCH`

### รูปแบบ: `X.Y.Z`

- **MAJOR (X)**: เปลี่ยนแปลงใหญ่ที่อาจไม่ backward compatible
  - ตัวอย่าง: `1.0.0` → `2.0.0`
  - เมื่อใด: มีการเปลี่ยนแปลง API, โครงสร้างฐานข้อมูล, หรือฟีเจอร์หลักที่ทำลาย compatibility

- **MINOR (Y)**: เพิ่มฟีเจอร์ใหม่แบบ backward compatible
  - ตัวอย่าง: `1.0.0` → `1.1.0`
  - เมื่อใด: เพิ่มฟีเจอร์ใหม่ที่ไม่ทำลายระบบเดิม

- **PATCH (Z)**: แก้ไขบั๊กและปรับปรุงเล็กน้อย
  - ตัวอย่าง: `1.0.0` → `1.0.1`
  - เมื่อใด: แก้บั๊ก, ปรับปรุง performance, แก้ไข UI เล็กน้อย

### ตัวอย่างการอัปเดตเวอร์ชั่น

```
1.0.0 → 1.0.1 (Bug fixes)
1.0.1 → 1.1.0 (New feature: Payment Gateway)
1.1.0 → 1.1.1 (Bug fixes)
1.1.1 → 2.0.0 (Breaking changes: New authentication system)
```

---

## 🔍 การตรวจสอบเวอร์ชั่น

### 1. ดูเวอร์ชั่นปัจจุบัน

#### ผ่าน Admin Dashboard
- เข้าสู่ระบบ Admin
- ดูเวอร์ชั่นที่มุมซ้ายล่างของ Sidebar

#### ผ่าน Command Line
```bash
# แสดงข้อมูลเวอร์ชั่นแบบเต็ม
php artisan app:version

# แสดง changelog
php artisan app:version --changelog

# ตรวจสอบ system requirements
php artisan app:version --system
```

### 2. ตรวจสอบการอัปเดต

```bash
# ตรวจสอบว่ามีเวอร์ชั่นใหม่หรือไม่
php artisan app:check-update

# แสดงรายการเวอร์ชั่นทั้งหมด
php artisan app:check-update --list

# ล้าง cache และตรวจสอบใหม่
php artisan app:check-update --clear-cache
```

### 3. ดูข้อมูลเวอร์ชั่นในโค้ด

```php
// Get current version
$version = config('version.current');

// Get version info
$info = app(\App\Services\VersionService::class)->getVersionInfo();

// Check if up to date
$isUpToDate = app(\App\Services\VersionService::class)->isUpToDate();
```

---

## 🚀 การอัปเดตระบบ

### วิธีที่ 1: อัปเดตอัตโนมัติ (แนะนำ)

```bash
# อัปเดตไปยังเวอร์ชั่นล่าสุด
php artisan app:update

# อัปเดตไปยังเวอร์ชั่นเฉพาะ
php artisan app:update v1.2.0

# อัปเดตโดยข้าม confirmation
php artisan app:update --force

# อัปเดตโดยไม่สร้าง backup
php artisan app:update --no-backup

# อัปเดตโดยข้ามการติดตั้ง dependencies
php artisan app:update --skip-deps
```

### วิธีที่ 2: อัปเดตแบบ Manual

```bash
# 1. สำรองข้อมูล
php artisan backup:run  # ถ้ามี backup package
# หรือ
cp database/database.sqlite database/database.sqlite.backup

# 2. Fetch และ Checkout version ใหม่
git fetch origin --tags
git checkout v1.2.0

# 3. ติดตั้ง dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. รัน migrations
php artisan migrate --force

# 5. ทำความสะอาดและ optimize
php artisan app:optimize --clear

# 6. Restart services (ถ้าจำเป็น)
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## 🏷️ Git Tags และ Releases

### สำหรับผู้ใช้งาน

#### ดู Tags ที่มีอยู่

```bash
# แสดง tags ทั้งหมด
git tag

# แสดง tags แบบละเอียด
git tag -l -n3

# ค้นหา tags ที่ต้องการ
git tag -l "v1.*"
```

#### Checkout เวอร์ชั่นเฉพาะ

```bash
# Checkout ไปยังเวอร์ชั่นที่ต้องการ
git checkout v1.0.0

# สร้าง branch จาก tag
git checkout -b my-branch v1.0.0
```

#### Download Release จาก GitHub

1. ไปที่ [Releases Page](https://github.com/xjanova/Thaiprompt-Affiliate/releases)
2. เลือกเวอร์ชั่นที่ต้องการ
3. Download Source Code (zip หรือ tar.gz)
4. แตกไฟล์และติดตั้ง

---

## 👨‍💻 สำหรับผู้พัฒนา

### การสร้าง Release ใหม่

#### 1. อัปเดต VERSION file

```bash
# แก้ไขไฟล์ VERSION
echo "1.1.0" > VERSION

# หรือใช้ service (สำหรับโค้ด)
php artisan tinker
>>> app(\App\Services\VersionService::class)->bumpVersion('minor');
```

#### 2. อัปเดต CHANGELOG.md

เพิ่มข้อมูลการเปลี่ยนแปลงในรูปแบบ:

```markdown
## [1.1.0] - 2025-11-01

### Added
- เพิ่มระบบ Payment Gateway
- เพิ่ม API สำหรับ mobile app

### Changed
- ปรับปรุง UI ของ Dashboard

### Fixed
- แก้ไขบั๊กการคำนวณคอมมิชชั่น
```

#### 3. อัปเดต package.json และ composer.json

```bash
# แก้ไข version ใน package.json
npm version 1.1.0

# ใน composer.json (แก้ไข manually)
# "version": "1.1.0"
```

#### 4. Commit และ Push

```bash
# Commit การเปลี่ยนแปลง
git add VERSION CHANGELOG.md package.json composer.json
git commit -m "chore: bump version to 1.1.0"

# Push to repository
git push origin main
```

#### 5. สร้าง Git Tag

```bash
# สร้าง annotated tag
git tag -a v1.1.0 -m "Release version 1.1.0

Added:
- Payment Gateway integration
- Mobile API endpoints

Changed:
- Improved Dashboard UI

Fixed:
- Commission calculation bug
"

# Push tag to remote
git push origin v1.1.0

# หรือ push tags ทั้งหมด
git push origin --tags
```

#### 6. สร้าง GitHub Release

##### วิธีที่ 1: ผ่าน GitHub Web Interface

1. ไปที่ Repository → Releases → "Draft a new release"
2. เลือก tag: `v1.1.0`
3. Release title: `v1.1.0 - [Codename]`
4. Description: Copy จาก CHANGELOG.md
5. คลิก "Publish release"

##### วิธีที่ 2: ผ่าน GitHub CLI

```bash
# สร้าง release จาก tag
gh release create v1.1.0 \
  --title "v1.1.0 - Payment Gateway Update" \
  --notes-file RELEASE_NOTES.md

# หรือสร้างพร้อม assets
gh release create v1.1.0 \
  --title "v1.1.0" \
  --notes "See CHANGELOG.md for details" \
  dist/*.zip
```

### การจัดการ Pre-releases

```bash
# สร้าง pre-release tag
git tag -a v1.1.0-beta.1 -m "Beta release 1.1.0"
git push origin v1.1.0-beta.1

# สร้าง GitHub pre-release
gh release create v1.1.0-beta.1 \
  --title "v1.1.0 Beta 1" \
  --notes "Beta version for testing" \
  --prerelease
```

---

## 🔧 Configuration

### ไฟล์ Config: `config/version.php`

```php
return [
    // เวอร์ชั่นปัจจุบัน (อ่านจากไฟล์ VERSION)
    'current' => trim(file_get_contents(base_path('VERSION'))),

    // ชื่อเวอร์ชั่น (codename)
    'name' => 'Foundation',

    // วันที่ release
    'released_at' => '2025-10-31',

    // PHP version ขั้นต่ำ
    'min_php_version' => '8.1.0',

    // ข้อมูล GitHub repository
    'repository' => [
        'owner' => 'xjanova',
        'name' => 'Thaiprompt-Affiliate',
        'branch' => 'main',
    ],

    // การตั้งค่าการตรวจสอบอัปเดต
    'update' => [
        'enabled' => true,
        'cache_ttl' => 3600, // 1 hour
        'auto_check' => true,
    ],
];
```

### ปิดการตรวจสอบอัปเดต

แก้ไขใน `.env`:

```env
VERSION_CHECK_ENABLED=false
VERSION_AUTO_CHECK=false
```

---

## ❓ คำถามที่พบบ่อย (FAQ)

### 1. ระบบจะอัปเดตอัตโนมัติหรือไม่?

**ไม่** ระบบจะแจ้งเตือนเมื่อมีเวอร์ชั่นใหม่ แต่ต้องอัปเดตด้วยตนเอง

### 2. การอัปเดตจะทำให้ข้อมูลหายหรือไม่?

**ไม่** ถ้าใช้คำสั่ง `php artisan app:update` จะมีการสำรองข้อมูลอัตโนมัติ แต่แนะนำให้สำรองเองก่อนอัปเดตเสมอ

### 3. สามารถย้อนกลับเวอร์ชั่นได้หรือไม่?

**ได้** ใช้คำสั่ง:

```bash
# ย้อนกลับด้วย git
git checkout v1.0.0

# Restore database backup
cp database/database.sqlite.backup database/database.sqlite

# Run migrations
php artisan migrate:rollback
```

### 4. จะทราบได้อย่างไรว่าต้องอัปเดต?

- ดูใน Admin Dashboard (จะมี badge แจ้งเตือน)
- รันคำสั่ง `php artisan app:check-update`
- ดู [Releases Page](https://github.com/xjanova/Thaiprompt-Affiliate/releases)

### 5. ต้องอัปเดตทุกเวอร์ชั่นหรือไม่?

**ไม่** สามารถข้ามเวอร์ชั่นได้ แต่ควรอ่าน CHANGELOG เพื่อดูการเปลี่ยนแปลงที่สำคัญ

### 6. เวอร์ชั่น Beta/Alpha คืออะไร?

- **Alpha**: ยังไม่เสถียร อาจมีบั๊กเยอะ (ไม่แนะนำใช้ใน production)
- **Beta**: ใกล้เสร็จแล้ว ใช้ทดสอบได้ (ระวังในการใช้ production)
- **RC** (Release Candidate): เวอร์ชั่นก่อน release จริง
- **Stable**: เวอร์ชั่นเสถียร ใช้ใน production ได้

### 7. การอัปเดตใช้เวลานานแค่ไหน?

ขึ้นอยู่กับ:
- ขนาดการเปลี่ยนแปลง: 2-10 นาที
- ความเร็ว internet (สำหรับ download)
- จำนวน migrations

### 8. ถ้าอัปเดตแล้วระบบเสีย?

1. อย่าตื่นตระหนก
2. ตรวจสอบ error logs: `storage/logs/laravel.log`
3. ย้อนกลับเวอร์ชั่นเก่า
4. Restore database backup
5. รายงานปัญหาที่ GitHub Issues

---

## 📚 เอกสารที่เกี่ยวข้อง

- [CHANGELOG.md](CHANGELOG.md) - ประวัติการเปลี่ยนแปลง
- [README.md](README.md) - ภาพรวมโปรเจกต์
- [DEPLOYMENT.md](DEPLOYMENT.md) - คู่มือการ Deploy
- [GitHub Releases](https://github.com/xjanova/Thaiprompt-Affiliate/releases)

---

## 🆘 ขอความช่วยเหลือ

หากมีปัญหาหรือข้อสงสัย:

1. ดู [Troubleshooting](#คำถามที่พบบ่อย)
2. เปิด [GitHub Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
3. ติดต่อ: support@thaiprompt.com

---

**📌 Note**: คู่มือนี้อาจมีการเปลี่ยนแปลงตามการพัฒนาของระบบ โปรดตรวจสอบเวอร์ชั่นล่าสุดที่ [GitHub](https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/VERSIONING.md)
