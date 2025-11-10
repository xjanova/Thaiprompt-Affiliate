# 📦 คู่มือการปล่อย Release

คู่มือสำหรับการสร้างและปล่อย Release ของ TP-Affiliate

---

## 🎯 สิ่งสำคัญที่ต้องเข้าใจ

### Release คืออะไร?

**Release** คือเวอร์ชันของระบบที่ **อนุมัติแล้ว** และพร้อมให้ผู้ใช้อัพเดท

- ✅ **Official Release** = ผู้ใช้เห็นในระบบอัพเดท
- ❌ **Draft Release** = ยังไม่เผยแพร่ ผู้ใช้ไม่เห็น
- ⚠️ **Pre-release** = เวอร์ชันทดสอบ (beta, alpha)

### ทำไมต้องควบคุม Release?

1. **ไม่ใช่ทุก commit จะพร้อมให้อัพเดท** - อาจมีบัคที่ยังแก้ไม่หมด
2. **Changelog ต้องเขียนให้เข้าใจง่าย** - ไม่ใช่รายการ git commits
3. **ต้องทดสอบก่อนปล่อย** - แน่ใจว่าทำงานได้จริง

---

## 📋 ขั้นตอนการปล่อย Release

### 1. เตรียมความพร้อม

ก่อนสร้าง Release ตรวจสอบ:

```bash
# ตรวจสอบว่าอยู่บน main branch
git branch

# ตรวจสอบสถานะ
git status

# ดึง code ล่าสุด
git pull origin main

# รัน tests (ถ้ามี)
php artisan test

# ทดสอบระบบให้แน่ใจว่าทำงานได้
```

### 2. กำหนดเลขเวอร์ชัน

ใช้ **Semantic Versioning**: `MAJOR.MINOR.PATCH`

| Type | เมื่อไหร่ใช้ | ตัวอย่าง |
|------|-------------|----------|
| **Major** (x.0.0) | มีการเปลี่ยนแปลงใหญ่ หรือ Breaking Changes | 1.0.0 → 2.0.0 |
| **Minor** (0.x.0) | เพิ่มฟีเจอร์ใหม่ (ไม่ทำให้เวอร์ชันเก่าพัง) | 1.0.0 → 1.1.0 |
| **Patch** (0.0.x) | แก้บัค หรือปรับปรุงเล็กน้อย | 1.0.0 → 1.0.1 |

**ตัวอย่าง:**
- `v1.0.0` - เวอร์ชันแรก
- `v1.1.0` - เพิ่มฟีเจอร์ tracking แบบ real-time
- `v1.1.1` - แก้บัคและปรับปรุงประสิทธิภาพ
- `v2.0.0` - เปลี่ยนระบบใหม่ทั้งหมด

### 3. เขียน Changelog (ภาษาไทยเท่านั้น!)

**✅ ตัวอย่างที่ดี:**

```
"เพิ่มระบบติดตามผลแบบเรียลไทม์ พร้อมกราฟสถิติแบบโต้ตอบและรองรับหลายสกุลเงิน ผู้ใช้สามารถดูยอดขายได้ทันทีที่มีการซื้อ"
```

**❌ ตัวอย่างที่ไม่ดี:**

```
"Fixed bug in tracking system"
"แก้บัค issue #123, #124, #125"
"Merge PR #456, Added feature X, Updated dependency Y"
```

**หลักการเขียน:**
- เน้น **ประโยชน์ที่ผู้ใช้ได้รับ**
- ใช้ **ภาษาไทยที่เข้าใจง่าย**
- **ไม่ต้องระบุรายละเอียดเทคนิค** หรือการแก้บัค
- **สรุปเฉพาะฟีเจอร์สำคัญ**

### 4. รันคำสั่งสร้าง Release

#### Option 1: Release ทันที (แนะนำ)

```bash
php artisan release:create v1.2.0 \
  --type=minor \
  --changelog="เพิ่มระบบติดตามผลแบบเรียลไทม์ พร้อมกราฟสถิติแบบโต้ตอบและรองรับหลายสกุลเงิน" \
  --push
```

#### Option 2: สร้าง Draft ก่อน (ทดสอบก่อนปล่อย)

```bash
php artisan release:create v1.2.0 \
  --type=minor \
  --changelog="เพิ่มระบบติดตามผลแบบเรียลไทม์ พร้อมกราฟสถิติแบบโต้ตอบและรองรับหลายสกุลเงิน" \
  --draft \
  --push
```

**จากนั้น:**
1. ไปที่ GitHub Releases
2. ทดสอบว่าระบบทำงานได้
3. คลิก "Publish release" เมื่อพร้อม

---

## 🚀 ตัวอย่างการใช้งาน

### ตัวอย่างที่ 1: Minor Release (ฟีเจอร์ใหม่)

```bash
# ตรวจสอบเวอร์ชันปัจจุบัน
cat VERSION
# Output: 1.0.0

# สร้าง release ใหม่
php artisan release:create v1.1.0 \
  --type=minor \
  --changelog="เพิ่มระบบแดชบอร์ดแบบโต้ตอบพร้อมกราฟสถิติ และรองรับการแสดงผลหลายสกุลเงิน" \
  --push

# ผลลัพธ์:
# ✓ CHANGELOG.md updated
# ✓ VERSION file updated to 1.1.0
# ✓ Changes committed
# ✓ Git tag v1.1.0 created
# ✓ Pushed to GitHub
# ✓ GitHub Release created
# ✅ Release v1.1.0 is now available for updates!
```

### ตัวอย่างที่ 2: Patch Release (แก้บัค/ปรับปรุง)

```bash
php artisan release:create v1.1.1 \
  --type=patch \
  --changelog="ปรับปรุงประสิทธิภาพการทำงานและเพิ่มความเสถียรของระบบ" \
  --push
```

### ตัวอย่างที่ 3: Major Release (Breaking Changes)

```bash
php artisan release:create v2.0.0 \
  --type=major \
  --changelog="ระบบใหม่ทั้งหมด พร้อมการวิเคราะห์ข้อมูลด้วย AI และรายงานแบบอัตโนมัติ รองรับ API แบบใหม่" \
  --push
```

### ตัวอย่างที่ 4: Draft Release (ทดสอบก่อน)

```bash
# สร้าง draft
php artisan release:create v2.0.0-beta \
  --type=major \
  --changelog="เวอร์ชันทดสอบของระบบใหม่ รอการทดสอบจากทีม" \
  --draft \
  --push

# เมื่อทดสอบเสร็จแล้ว ไปที่ GitHub แล้วคลิก "Publish release"
```

---

## 📊 สถานะของ Release

### Draft Release (ยังไม่เผยแพร่)

```
Status: 📝 Draft
ผู้ใช้เห็น: ❌ ไม่เห็น
Update System: ❌ ไม่ปรากฏ
ใช้เมื่อ: ต้องการทดสอบก่อนปล่อย
```

### Official Release (เผยแพร่แล้ว)

```
Status: ✅ Published
ผู้ใช้เห็น: ✅ เห็น
Update System: ✅ ปรากฏในระบบอัพเดท
ใช้เมื่อ: พร้อมให้ผู้ใช้อัพเดท
```

### Pre-release (เวอร์ชันทดสอบ)

```
Status: ⚠️ Pre-release (beta/alpha)
ผู้ใช้เห็น: ❌ ไม่เห็น (เว้นแต่เปิด allow_prerelease)
Update System: ❌ ไม่ปรากฏ
ใช้เมื่อ: ทดสอบกับกลุ่มผู้ใช้เฉพาะ
```

---

## 🔍 ตรวจสอบ Release

### ตรวจสอบบน GitHub

1. ไปที่ https://github.com/xjanova/Thaiprompt-Affiliate/releases
2. ดู release ล่าสุด
3. ตรวจสอบว่า:
   - Tag name ถูกต้อง
   - Changelog เป็นภาษาไทย
   - Status เป็น Published (ไม่ใช่ Draft)

### ตรวจสอบจากระบบ

```bash
# เช็คเวอร์ชันปัจจุบัน
cat VERSION

# เช็ค git tags
git tag -l

# เช็ค CHANGELOG
head -50 CHANGELOG.md
```

---

## ❌ ข้อผิดพลาดที่พบบ่อย

### 1. Changelog เป็นภาษาอังกฤษ

```bash
# ❌ ผิด
--changelog="Fixed bug in tracking system"

# ✅ ถูก
--changelog="ปรับปรุงระบบติดตามผลให้ทำงานได้เสถียรขึ้น"
```

### 2. Changelog ยาวเกินไป / มีรายละเอียดเทคนิค

```bash
# ❌ ผิด
--changelog="Fixed issue #123, Merged PR #456, Updated database schema, Refactored code in UserController, Fixed typo in views"

# ✅ ถูก
--changelog="ปรับปรุงระบบผู้ใช้ให้ทำงานได้รวดเร็วและเสถียรขึ้น"
```

### 3. ลืม --push

```bash
# ❌ ถ้าไม่ใส่ --push
php artisan release:create v1.2.0 --changelog="..."

# Release จะถูกสร้างแค่ local ต้อง push manual:
git push origin v1.2.0
gh release create v1.2.0 --title "v1.2.0" --notes "..."

# ✅ ถูกต้อง
php artisan release:create v1.2.0 --changelog="..." --push
```

---

## 📝 Checklist การปล่อย Release

ก่อนปล่อย Release ตรวจสอบให้แน่ใจว่า:

- [ ] อยู่บน main branch
- [ ] Code ทดสอบแล้วว่าทำงานได้
- [ ] Changelog เป็นภาษาไทย
- [ ] Changelog เน้นสิ่งที่ผู้ใช้ได้รับ ไม่ใช่รายละเอียดเทคนิค
- [ ] เลขเวอร์ชันถูกต้องตาม Semantic Versioning
- [ ] มี --push flag (หรือพร้อม push manual)
- [ ] ไม่ใช่ draft (เว้นแต่ต้องการทดสอบก่อน)

---

## 🆘 วิธีแก้ไขถ้าผิดพลาด

### ถ้าสร้าง Release ผิด

1. **ลบ Tag local:**
   ```bash
   git tag -d v1.2.0
   ```

2. **ลบ Tag บน GitHub:**
   ```bash
   git push origin :refs/tags/v1.2.0
   ```

3. **ลบ Release บน GitHub:**
   - ไปที่ GitHub Releases
   - คลิก Release ที่ต้องการลบ
   - คลิก "Delete this release"

4. **สร้างใหม่:**
   ```bash
   php artisan release:create v1.2.0 --changelog="..." --push
   ```

### ถ้า Changelog ผิด

1. **แก้ไข CHANGELOG.md:**
   ```bash
   nano CHANGELOG.md
   # แก้ไข changelog
   ```

2. **แก้ไขบน GitHub Release:**
   - ไปที่ GitHub Releases
   - คลิก "Edit"
   - แก้ไข release notes
   - คลิก "Update release"

---

## 💡 Tips & Best Practices

1. **ทดสอบก่อนปล่อย**
   - ใช้ `--draft` สำหรับเวอร์ชันที่ยังไม่แน่ใจ
   - Publish เมื่อทดสอบผ่านแล้ว

2. **เขียน Changelog ให้ดี**
   - คิดว่าผู้ใช้จะได้อะไร
   - ใช้ภาษาไทยที่เข้าใจง่าย
   - ไม่ต้องระบุทุกการเปลี่ยนแปลง เฉพาะที่สำคัญ

3. **ตั้งเวอร์ชันให้ถูกต้อง**
   - Major: เปลี่ยนแปลงใหญ่
   - Minor: ฟีเจอร์ใหม่
   - Patch: แก้บัค/ปรับปรุง

4. **สร้าง Release เป็นประจำ**
   - ไม่ต้องรอจนมีฟีเจอร์เยอะ
   - ปล่อยบ่อยๆ แต่ละครั้งน้อย ดีกว่าปล่อยนานๆ ครั้งเดียวเยอะ

---

## 📚 อ่านเพิ่มเติม

- [Semantic Versioning](https://semver.org/)
- [CHANGELOG.md](./CHANGELOG.md)
- [INSTALLATION-GUIDE.md](./INSTALLATION-GUIDE.md#การอัพเดทระบบ)

---

**Happy Releasing! 🚀**
