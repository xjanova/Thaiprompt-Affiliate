# คำแนะนำสำหรับ Claude - Thaiprompt Affiliate System

## 🚨 MANDATORY - READ ALL GUIDELINES BEFORE STARTING ANY TASK

> **⚠️ บังคับให้อ่านเอกสารทั้งหมดก่อนเริ่มทำงาน ⚠️**

**EVERY TIME** you start working on ANY task (create, update, refactor, debug), you **MUST** read these guideline documents first:

### 📋 Required Reading Checklist (อ่านก่อนทำงานเสมอ)

ก่อนลงมือทำงานใดๆ ต้องอ่านเอกสารเหล่านี้ก่อน:

- [ ] **[seeder-guidelines.md](./.claude/seeder-guidelines.md)** - กฎสำหรับ Seeders และ DatabaseSeeder.php synchronization
  - ⚠️ **CRITICAL**: ทุกครั้งที่สร้าง/ลบ/แก้ไข Seeder ต้องอัพเดท DatabaseSeeder.php
  - Smart seeding strategies (check before seeding, protect user data)
  - รัน `php scripts/verify-seeders.php` ก่อน commit เสมอ
  - 🔧 **Git Hook**: Pre-commit hook จะตรวจสอบอัตโนมัติและบล็อก commit ถ้าไม่ผ่าน

- [ ] **[DATABASE_GUIDELINES.md](./.claude/DATABASE_GUIDELINES.md)** - กฎสำหรับ Database, Migrations และ Schema
  - Table existence checks (Schema::hasTable, Schema::hasColumn)
  - Foreign keys และ indexes
  - Migration best practices และ rollback

- [ ] **[UI_DESIGN_SYSTEM.md](./.claude/UI_DESIGN_SYSTEM.md)** - มาตรฐาน UI/UX และ Design System
  - Dark/Light mode (บังคับ)
  - Responsive design (บังคับ)
  - Component standards และ styling

- [ ] **[MENU_RULES.md](./.claude/MENU_RULES.md)** - กฎสำหรับระบบเมนูและ Navigation
  - Windows UI menu system
  - Dynamic menu management
  - Menu seeding และ configuration

- [ ] **[ROUTES_GUIDELINES.md](./.claude/ROUTES_GUIDELINES.md)** - กฎสำหรับ Routes และ Views
  - Route verification และ naming conventions
  - View best practices
  - Controller → View data flow

- [ ] **[DEPLOYMENT_GUIDELINES.md](./.claude/DEPLOYMENT_GUIDELINES.md)** - กฎสำหรับ Deployment และ Composer Packages
  - Composer package management
  - deploy.sh best practices
  - Error handling และ fallback strategies

### ⚠️ Why This Is Critical

**ถ้าไม่อ่านเอกสาร จะเกิดปัญหาเหล่านี้:**
- ❌ สร้าง Seeder แล้วลืมเพิ่มใน DatabaseSeeder.php → Deployment fails
- ❌ สร้าง Migration โดยไม่ check table exists → Production errors
- ❌ UI ไม่รองรับ dark mode → User experience ไม่ดี
- ❌ ไม่ทำ responsive → ใช้งานบน mobile ไม่ได้
- ❌ ไม่ตรวจสอบ foreign keys → Database integrity issues
- ❌ Routes ซ้ำหรือไม่มี middleware → Security issues
- ❌ ติดตั้ง package แล้วไม่เพิ่มใน deploy.sh → Deployment breaks

**ถ้าอ่านเอกสารและปฏิบัติตาม:**
- ✅ Code มีคุณภาพสูง ไม่มี bugs
- ✅ ทำงานถูกต้องตามมาตรฐาน
- ✅ Deployment สำเร็จทุกครั้ง
- ✅ ไม่ทำผิดพลาดที่เคยเกิดขึ้นแล้ว
- ✅ Code maintainable และ scalable

### 🔄 When to Re-read Guidelines

อ่านเอกสารใหม่เมื่อ:
1. **เริ่มแชทใหม่** - อ่านก่อนทำงานอะไรก็ตาม
2. **ก่อนสร้าง Seeder** - อ่าน seeder-guidelines.md
3. **ก่อนสร้าง Migration** - อ่าน DATABASE_GUIDELINES.md
4. **ก่อนสร้าง UI Component** - อ่าน UI_DESIGN_SYSTEM.md
5. **ก่อนแก้ไข Menu** - อ่าน MENU_RULES.md
6. **ก่อนสร้าง Routes/Views** - อ่าน ROUTES_GUIDELINES.md
7. **ก่อนติดตั้ง Package** - อ่าน DEPLOYMENT_GUIDELINES.md
8. **เมื่อไม่แน่ใจ** - อ่านเอกสารที่เกี่ยวข้อง

### ✅ How to Confirm You've Read

เมื่ออ่านเอกสารแล้ว ให้ตอบผู้ใช้ว่า:
```
✅ อ่านเอกสารแนวทางแล้ว:
- seeder-guidelines.md: [สรุปสั้นๆ ว่าเข้าใจอะไร]
- DATABASE_GUIDELINES.md: [สรุปสั้นๆ]
- [เอกสารอื่นๆ ที่เกี่ยวข้อง]: [สรุปสั้นๆ]

พร้อมเริ่มทำงานตามมาตรฐานที่กำหนด
```

---

## 💎 หลักการสำคัญ (Core Principles)

### 1. 🌓 Dark/Light Mode (บังคับ)
- ทุก UI ต้องรองรับทั้งสองโหมด
- ใช้ CSS variables และ Tailwind dark utilities
- ทดสอบ contrast และ readability

### 2. 📱 Responsive Design (บังคับ)
- Mobile-first approach
- ทดสอบทุก device (mobile, tablet, desktop)
- Touch-friendly บน mobile (≥44px)

### 3. 💬 คอมเม้นต์ภาษาไทย (บังคับ)
- อธิบายการทำงานเป็นภาษาไทย
- มี JSDoc/PHPDoc พร้อม @param, @returns, @example
- ใส่ @tip การใช้งานและ best practices

### 4. 🗄️ Database Best Practices (บังคับ)
- ตรวจสอบ tables/columns ไม่ซ้ำ
- Foreign keys ถูกต้อง พร้อม onDelete/onUpdate
- Indexes ครบสำหรับ performance
- Migration ต้องมี table existence check

### 5. 🌱 Seeder Synchronization (บังคับ)
- ทุก Seeder ต้องอยู่ใน DatabaseSeeder.php
- รัน `php scripts/verify-seeders.php` ก่อน commit
- Smart seeding (check before insert, protect user data)

### 6. 🛣️ Routes & Views (บังคับ)
- Routes ไม่ซ้ำ มี middleware ครบถ้วน
- Views ส่งข้อมูลครบ มี error handling
- ทดสอบทุก route path ทำงานถูกต้อง

### 7. 📦 Deployment & Packages (บังคับ)
- ทุก package ต้องเพิ่มใน deploy.sh
- มี version detection และ error handling
- ทดสอบ deployment script หลังแก้ไข

### 8. 🪝 Git Hooks (บังคับ)
- ติดตั้ง Git Hooks เสมอเมื่อเริ่มโปรเจคใหม่ หรือ clone repository
- Git Hooks จะตรวจสอบและบังคับให้ปฏิบัติตามกฎอัตโนมัติ
- **ติดตั้ง**: `bash scripts/git-hooks/install.sh`
- Pre-commit hook จะตรวจสอบ seeder synchronization อัตโนมัติ

---

## 🎯 Code Quality Standards

### Backend (Laravel/PHP)
- Clean Code Principles (SRP, DRY)
- Laravel Best Practices (Eloquent, Service layer)
- Type hints และ validation ครบถ้วน
- คอมเม้นต์ภาษาไทยอธิบายการทำงาน

### Frontend (Vue.js/JavaScript)
- Single File Components (SFC)
- Props validation และ TypeScript types
- Composition API สำหรับ logic reuse
- Performance optimization (lazy loading, code splitting)

### Security
- Input validation (frontend + backend)
- XSS และ CSRF protection
- Proper authentication & authorization
- Graceful error handling

---

## ✅ Checklist ก่อน Commit

### Design & UI
- [ ] รองรับ dark/light mode (บังคับ)
- [ ] Responsive บนทุก device (บังคับ)
- [ ] UI สวยงามระดับมืออาชีพ
- [ ] มี animations และ loading states

### Code Quality
- [ ] มีคอมเม้นต์ภาษาไทย (บังคับ)
- [ ] มี JSDoc/PHPDoc พร้อม @example และ @tip (บังคับ)
- [ ] Code clean, readable, maintainable
- [ ] Error handling ครบถ้วน

### Database
- [ ] Migration มี table existence check (บังคับ)
- [ ] Foreign keys และ indexes ครบถ้วน
- [ ] Model relationships ตรงกับ database schema

### Seeders
- [ ] Seeder ทุกตัวอยู่ใน DatabaseSeeder.php (บังคับ)
- [ ] รัน `php scripts/verify-seeders.php` ผ่าน (บังคับ)
- [ ] Smart seeding (check before insert)
- [ ] Git pre-commit hook จะตรวจสอบอัตโนมัติ

### Routes & Views
- [ ] Routes ไม่ซ้ำ มี middleware
- [ ] Views มี error handling
- [ ] ทดสอบทุก route ทำงานถูกต้อง

### Deployment
- [ ] Packages ทั้งหมดอยู่ใน deploy.sh (บังคับ)
- [ ] มี error handling และ fallback
- [ ] ทดสอบ deployment script

---

## 🪝 Git Hooks Setup (IMPORTANT)

### ทำไมต้องมี Git Hooks?

Git Hooks จะ **ตรวจสอบและบังคับให้ปฏิบัติตามกฎอัตโนมัติ** ก่อนที่จะ commit โค้ด

**ปัญหาที่แก้ไข**:
- ❌ ลืมเพิ่ม Seeder ลง DatabaseSeeder.php → Deployment fails
- ❌ Commit โค้ดที่ผิดกฎ → Production errors
- ❌ ต้องจำกฎทั้งหมด → มนุษย์ลืมได้

**ประโยชน์**:
- ✅ ตรวจสอบอัตโนมัติก่อน commit
- ✅ บล็อก commit ถ้าไม่ผ่านกฎ
- ✅ ลดข้อผิดพลาดจากมนุษย์

### 🔧 วิธีติดตั้ง Git Hooks

**เมื่อเริ่มโปรเจคใหม่หรือ clone repository**:

```bash
# ติดตั้ง Git Hooks ทั้งหมด
bash scripts/git-hooks/install.sh
```

**ตรวจสอบว่าติดตั้งแล้ว**:

```bash
# ตรวจสอบว่ามี pre-commit hook
test -x .git/hooks/pre-commit && echo "✓ Installed" || echo "✗ Not installed"
```

### 📋 Git Hooks ที่มีอยู่

**1. Pre-Commit Hook (Seeder Verification)**
- **ทำงาน**: ตรวจสอบว่าทุก Seeder อยู่ใน DatabaseSeeder.php
- **เมื่อไร**: ก่อน `git commit`
- **ถ้าไม่ผ่าน**: บล็อก commit และแสดงวิธีแก้ไข

**ตัวอย่างเมื่อ Hook บล็อก Commit**:
```
❌ COMMIT BLOCKED
⚠  CRITICAL RULE #1 VIOLATION

You MUST add missing seeders to DatabaseSeeder.php before committing.

🔧 To fix:
   1. Open database/seeders/DatabaseSeeder.php
   2. Add missing seeder(s) to the call() array
   3. Run: php scripts/verify-seeders.php
   4. Try committing again
```

### 🚨 เมื่อ Claude สร้าง Seeder ใหม่

**ขั้นตอนที่ Claude ต้องทำ (MANDATORY)**:

1. ✅ สร้างไฟล์ Seeder ใหม่
2. ✅ **เพิ่ม Seeder ลง DatabaseSeeder.php ทันที** (ห้ามลืม!)
3. ✅ Commit ทั้งสองไฟล์พร้อมกัน
4. ✅ Pre-commit hook จะตรวจสอบอัตโนมัติ

**ตัวอย่างที่ถูกต้อง**:
```bash
# 1. สร้าง seeder
php artisan make:seeder NewFeatureSeeder

# 2. เพิ่มลง DatabaseSeeder.php (อย่าลืม!)
# แก้ไขไฟล์ database/seeders/DatabaseSeeder.php

# 3. Commit ทั้งสองไฟล์
git add database/seeders/NewFeatureSeeder.php
git add database/seeders/DatabaseSeeder.php
git commit -m "Add NewFeatureSeeder"

# 4. Pre-commit hook จะรันอัตโนมัติ
# ถ้าทุกอย่างถูกต้อง → Commit สำเร็จ
# ถ้ามีปัญหา → Commit ถูกบล็อก
```

### 📚 เอกสารเพิ่มเติม

- **[scripts/git-hooks/README.md](../scripts/git-hooks/README.md)** - คู่มือ Git Hooks
- **[.claude/seeder-guidelines.md](./.claude/seeder-guidelines.md)** - CRITICAL RULE #1

---

## 💡 สรุป

**"โปรแกรมที่เราพัฒนาต้องมีคุณภาพระดับหลักล้าน"**

ทุกครั้งที่เขียนโค้ด ถามตัวเองว่า:
1. ✅ อ่านเอกสารแนวทางที่เกี่ยวข้องแล้วหรือยัง?
2. ✅ UI สวยงามและรองรับ dark/light mode + responsive หรือยัง?
3. ✅ มีคอมเม้นต์และคู่มือครบถ้วนหรือยัง?
4. ✅ Database/Seeder/Routes ตรวจสอบแล้วหรือยัง?
5. ✅ Code clean และไม่มี technical debt หรือยัง?
6. ✅ ทดสอบครบทุก scenario หรือยัง?
7. ✅ น่าภูมิใจที่จะให้คนอื่นใช้หรือยัง?

**ถ้าตอบ "ใช่" ทั้งหมด แสดงว่าโค้ดมีคุณภาะ์ระดับหลักล้าน! 💎✨**

---

*"Excellence is not an act, but a habit" - ทำให้ทุกโค้ดเป็นผลงานที่ภาคภูมิใจ*

*"Quality is never an accident; it is always the result of intelligent effort" - ใส่ใจในทุกรายละเอียด*
