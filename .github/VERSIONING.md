# 🚀 Automatic Versioning และ Release System

ระบบ automatic versioning นี้จะสร้าง version และ release ใหม่อัตโนมัติทุกครั้งที่มีการ merge เข้า main branch ตามหลักการ Semantic Versioning (SemVer)

## 📋 หลักการทำงาน

### Semantic Versioning (SemVer)

เวอร์ชั่นจะอยู่ในรูปแบบ: **MAJOR.MINOR.PATCH** (เช่น v1.2.3)

- **MAJOR** (1.x.x): การเปลี่ยนแปลงที่ไม่ backward compatible
- **MINOR** (x.1.x): การเพิ่มฟีเจอร์ใหม่ที่ยัง backward compatible
- **PATCH** (x.x.1): การแก้ไขบัคหรือปรับปรุงเล็กน้อย

## 📝 Commit Message Convention

ระบบจะอ่าน commit messages เพื่อกำหนดประเภทของ version bump:

### PATCH Version (x.x.1)

```
fix: แก้ไขปัญหาการแสดงผลในหน้า dashboard
bugfix: แก้ไข error ในระบบ login
hotfix: แก้ไขปัญหาเร่งด่วนในระบบชำระเงิน
```

### MINOR Version (x.1.x)

```
feat: เพิ่มฟีเจอร์ export รายงาน PDF
feature: เพิ่มระบบการแจ้งเตือนทาง email
```

### MAJOR Version (1.x.x)

```
feat!: เปลี่ยนโครงสร้าง API ใหม่ทั้งหมด
BREAKING CHANGE: ลบ API v1 และใช้ v2 เท่านั้น
```

## 🔄 การทำงานอัตโนมัติ

เมื่อมีการ merge PR เข้า main branch:

1. ✅ อ่าน commit messages เพื่อกำหนด version bump type
2. 🏷️ สร้าง git tag ใหม่ (เช่น v1.2.3)
3. 📦 สร้าง GitHub Release พร้อม changelog
4. 📝 อัพเดต CHANGELOG.md ในโปรเจค
5. 🔄 อัพเดต version ใน package.json และไฟล์ VERSION
6. 🖥️ เวอร์ชั่นใหม่จะแสดงอัตโนมัติในหน้าแอดมินและยูสเซอร์
7. ✍️ Commit การเปลี่ยนแปลงกลับเข้า main branch

## 📖 ตัวอย่างการใช้งาน

### 1. การแก้ไขบัค (PATCH)

```bash
git commit -m "fix: แก้ไขปัญหา null pointer exception"
```

Result: `v1.0.0` → `v1.0.1`

### 2. การเพิ่มฟีเจอร์ (MINOR)

```bash
git commit -m "feat: เพิ่มระบบ notification"
```

Result: `v1.0.0` → `v1.1.0`

### 3. Breaking Changes (MAJOR)

```bash
git commit -m "feat!: เปลี่ยน authentication system ใหม่"
# หรือ
git commit -m "feat: เปลี่ยน authentication system

BREAKING CHANGE: ต้องอัพเดต API keys ทั้งหมด"
```

Result: `v1.0.0` → `v2.0.0`

## 📄 ไฟล์ที่เกี่ยวข้อง

- `.github/workflows/release.yml` - GitHub Actions workflow
- `CHANGELOG.md` - ประวัติการเปลี่ยนแปลง
- `package.json` - ข้อมูล version ของโปรเจค (Node.js)
- `VERSION` - ไฟล์เก็บเวอร์ชั่นปัจจุบัน (ใช้โดย Laravel)
- `config/version.php` - Configuration สำหรับระบบ versioning

## 🎯 Best Practices

1. **ใช้ commit message ที่ชัดเจน**: ระบุประเภท (feat, fix) และคำอธิบายที่เข้าใจง่าย
2. **แยก commit ตามประเภท**: ไม่ควรรวม feature และ bugfix ใน commit เดียวกัน
3. **ตรวจสอบ changelog**: ดู changelog ที่สร้างอัตโนมัติว่าถูกต้องหรือไม่
4. **ทดสอบก่อน merge**: ตรวจสอบให้แน่ใจว่าโค้ดทำงานได้ดีก่อน merge เข้า main

## 🔍 การตรวจสอบ Release

หลังจากระบบสร้าง release อัตโนมัติ สามารถตรวจสอบได้ที่:

1. **GitHub Releases**: ดู release notes และ changelog
2. **Tags**: ตรวจสอบ git tags ที่ถูกสร้าง
3. **CHANGELOG.md**: ดูประวัติการเปลี่ยนแปลงในไฟล์
4. **package.json**: ตรวจสอบ version ที่อัพเดต

## ⚙️ การ Trigger ด้วยตนเอง

หากต้องการสร้าง release ด้วยตนเอง:

1. ไปที่ Actions tab ใน GitHub
2. เลือก "Automatic Version Release"
3. คลิก "Run workflow"
4. เลือก branch main
5. คลิก "Run workflow"

## 🚫 การข้าม Versioning

หากต้องการ commit โดยไม่สร้าง release ใหม่:

```bash
git commit -m "docs: อัพเดตเอกสาร [skip ci]"
```

ใช้ `[skip ci]` ใน commit message เพื่อข้ามการ trigger workflow

## 🖥️ การแสดงเวอร์ชั่นในระบบ

### ในหน้าแอดมิน

เวอร์ชั่นจะแสดงอัตโนมัติใน sidebar ของหน้าแอดมิน ประกอบด้วย:

- **เวอร์ชั่นปัจจุบัน**: แสดงในรูปแบบ badge สีน้ำเงิน-ม่วง
- **Laravel Version**: เวอร์ชั่นของ framework
- **PHP Version**: เวอร์ชั่นของ PHP ที่กำลังใช้งาน

ตำแหน่ง: `resources/views/layouts/admin.blade.php` (บรรทัด 600-623)

### ในหน้ายูสเซอร์

เวอร์ชั่นจะแสดงอัตโนมัติใน sidebar ของหน้ายูสเซอร์ ประกอบด้วย:

- **เวอร์ชั่นปัจจุบัน**: แสดงในรูปแบบ badge สีน้ำเงิน-ม่วง
- **Laravel Version**: เวอร์ชั่นของ framework
- **PHP Version**: เวอร์ชั่นของ PHP ที่กำลังใช้งาน

ตำแหน่ง: `resources/views/layouts/user.blade.php` (บรรทัด 420-443)

### วิธีการทำงาน

1. ระบบจะอ่านเวอร์ชั่นจาก `config('version.current')`
2. Config จะดึงค่าจากไฟล์ `VERSION` เป็นหลัก
3. ถ้าไม่มีไฟล์ `VERSION` จะ fallback ไปที่ `package.json`
4. เวอร์ชั่นจะอัพเดตอัตโนมัติทุกครั้งที่มีการ merge และ release ใหม่

### การตรวจสอบเวอร์ชั่นผ่าน Command Line

```bash
# แสดงข้อมูลเวอร์ชั่น
php artisan app:version

# ตรวจสอบ updates
php artisan app:version --check

# แสดง system requirements
php artisan app:version --system

# แสดง changelog
php artisan app:version --changelog
```

## 📞 การขอความช่วยเหลือ

หากพบปัญหาหรือต้องการความช่วยเหลือ:

1. ตรวจสอบ Actions log ใน GitHub
2. ดู workflow file ที่ `.github/workflows/release.yml`
3. ตรวจสอบไฟล์ VERSION และ package.json
4. รัน `php artisan app:version --check` เพื่อตรวจสอบสถานะ
5. ติดต่อทีม DevOps หรือ maintainers
