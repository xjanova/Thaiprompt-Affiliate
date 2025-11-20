# 🚀 Scripts Directory

คู่มือการใช้งาน Scripts ต่างๆ ของ Thai Prompt Affiliate Platform

## 📋 Available Scripts

### 🔧 fix-affiliates-migration.sh

**Purpose:** แก้ไขปัญหา migration ของตาราง affiliates

**ใช้เมื่อเจอ Error:**
- `SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'affiliates' already exists`
- `SQLSTATE[HY000]: General error: 1005 Can't create table (errno: 150 "Foreign key constraint is incorrectly formed")`

**สิ่งที่ Script ทำ:**
1. ✅ ลบไฟล์ migration เก่า (`2024_01_01_000002_create_affiliates_table.php`)
2. ✅ ลบตาราง `affiliates` ที่มีอยู่ (ถ้ามี)
3. ✅ ทำความสะอาด `migrations` table entries
4. ✅ ตรวจสอบไฟล์ migration ใหม่ (`2025_11_17_000001_create_affiliates_table.php`)
5. ✅ ตรวจสอบว่าตาราง `users` มีอยู่แล้ว (required for foreign keys)

**วิธีใช้:**

```bash
# ไปที่ project root
cd /home/admin/domains/thaiprompt.online/public_html

# รัน fix script
bash scripts/fix-affiliates-migration.sh

# หลังจาก cleanup เสร็จ รัน migrations ใหม่
php artisan migrate

# หรือรัน install.sh ต่อ
./install.sh
```

**⚠️ คำเตือน:**
- Script นี้จะ DROP ตาราง `affiliates` ถ้ามีอยู่
- ปลอดภัย สามารถรันได้หลายครั้ง (idempotent)
- จะลบเฉพาะตาราง `affiliates` เท่านั้น ตารางอื่นไม่กระทบ

---

## 📋 Version Management Scripts

### วิธีที่ 1: อัพเดตเวอร์ชั่นแบบเร็ว (แนะนำ)

```bash
# Bump PATCH version (1.0.0 → 1.0.1)
./scripts/quick-bump.sh patch

# Bump MINOR version (1.0.0 → 1.1.0)
./scripts/quick-bump.sh minor

# Bump MAJOR version (1.0.0 → 2.0.0)
./scripts/quick-bump.sh major
```

**สิ่งที่จะเกิดขึ้น:**
- ✅ อัพเดต `VERSION` file
- ✅ อัพเดต `package.json`
- ✅ อัพเดต `CHANGELOG.md` พร้อม entry ใหม่
- ✅ แสดงคำสั่ง git ที่ต้องรันต่อ

**หลังจากรัน script:**
```bash
# Review การเปลี่ยนแปลง
git diff VERSION package.json CHANGELOG.md

# Commit และสร้าง tag
git add VERSION package.json CHANGELOG.md
git commit -m "chore: bump version to x.x.x"
git tag -a vx.x.x -m "Release vx.x.x"

# Push ไปยัง remote
git push && git push --tags
```

### วิธีที่ 2: สร้าง Initial Tag

ใช้เมื่อต้องการสร้าง tag ครั้งแรก:

```bash
./scripts/init-version.sh
```

Script นี้จะ:
- อ่านเวอร์ชั่นจาก `VERSION` file
- สร้าง git tag (เช่น v1.0.0)
- ถามว่าต้องการ push tag หรือไม่

### วิธีที่ 3: ใช้ Artisan Command (ถ้ามี vendor)

```bash
# Bump version
php artisan version:bump patch
php artisan version:bump minor
php artisan version:bump major

# ดูเวอร์ชั่นปัจจุบัน
php artisan app:version

# ตรวจสอบ updates
php artisan app:version --check
```

### วิธีที่ 4: Automatic (GitHub Actions)

เมื่อ merge PR เข้า `claude/Main` พร้อม commit message ที่ถูกต้อง:

**PATCH** (1.0.0 → 1.0.1):
```
fix: แก้ไขบัคการแสดงผล
```

**MINOR** (1.0.0 → 1.1.0):
```
feat: เพิ่มฟีเจอร์ export PDF
```

**MAJOR** (1.0.0 → 2.0.0):
```
feat!: เปลี่ยน API structure
```

ระบบจะทำอัตโนมัติ:
- ✅ สร้าง git tag
- ✅ สร้าง GitHub Release
- ✅ อัพเดตไฟล์ทั้งหมด
- ✅ เวอร์ชั่นแสดงในหน้าแอดมิน/ยูสเซอร์ทันที

## 🎯 Semantic Versioning

เวอร์ชั่นอยู่ในรูปแบบ: **MAJOR.MINOR.PATCH**

- **MAJOR**: Breaking changes (เปลี่ยน API, ไม่ backward compatible)
- **MINOR**: Features ใหม่ (ยัง backward compatible)
- **PATCH**: Bug fixes และการปรับปรุงเล็กน้อย

## 📝 ตัวอย่างการใช้งานจริง

### ตัวอย่าง 1: แก้ไขบัคเล็กน้อย

```bash
# แก้ไขโค้ด...

# Bump patch version
./scripts/quick-bump.sh patch

# Output: v1.0.0 → v1.0.1

# Commit และ push
git add VERSION package.json CHANGELOG.md
git commit -m "fix: แก้ไขปัญหา null pointer exception"
git tag -a v1.0.1 -m "Release v1.0.1"
git push && git push --tags
```

### ตัวอย่าง 2: เพิ่มฟีเจอร์ใหม่

```bash
# พัฒนาฟีเจอร์ใหม่...

# Bump minor version
./scripts/quick-bump.sh minor

# Output: v1.0.1 → v1.1.0

# Commit และ push
git add VERSION package.json CHANGELOG.md
git commit -m "feat: เพิ่มระบบ notification"
git tag -a v1.1.0 -m "Release v1.1.0"
git push && git push --tags
```

### ตัวอย่าง 3: Breaking Changes

```bash
# เปลี่ยนโครงสร้างใหญ่...

# Bump major version
./scripts/quick-bump.sh major

# Output: v1.1.0 → v2.0.0

# Commit และ push
git add VERSION package.json CHANGELOG.md
git commit -m "feat!: เปลี่ยน API structure ใหม่ทั้งหมด"
git tag -a v2.0.0 -m "Release v2.0.0"
git push && git push --tags
```

## 🔍 ตรวจสอบเวอร์ชั่นปัจจุบัน

```bash
# ดูจากไฟล์
cat VERSION

# ดูจาก package.json
grep version package.json

# ดู git tags
git tag -l

# ดูเวอร์ชั่นล่าสุด
git describe --tags --abbrev=0
```

## ⚙️ ไฟล์ที่เกี่ยวข้อง

- `VERSION` - เก็บเวอร์ชั่นปัจจุบัน (ใช้โดย Laravel)
- `package.json` - เก็บเวอร์ชั่น (ใช้โดย npm/node)
- `CHANGELOG.md` - ประวัติการเปลี่ยนแปลง
- `config/version.php` - Configuration สำหรับ Laravel
- `.github/workflows/release.yml` - GitHub Actions workflow

## 🆘 Troubleshooting

### ปัญหา: Script ไม่ทำงาน

```bash
# ตรวจสอบว่า script executable หรือไม่
ls -l scripts/*.sh

# ถ้าไม่ใช่ ให้รัน
chmod +x scripts/*.sh
```

### ปัญหา: เวอร์ชั่นไม่แสดงในหน้าเว็บ

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart web server
```

### ปัญหา: Git tag conflict

```bash
# ลบ tag ที่ซ้ำ
git tag -d v1.0.0

# ลบ tag จาก remote
git push origin :refs/tags/v1.0.0
```

## 📞 ความช่วยเหลือ

หากพบปัญหา:
1. ตรวจสอบไฟล์ VERSION, package.json, CHANGELOG.md
2. ดู git tags: `git tag -l`
3. ตรวจสอบ GitHub Actions logs (ถ้าใช้ automatic)
4. ติดต่อทีม DevOps
