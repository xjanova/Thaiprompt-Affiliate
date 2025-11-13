# Git Hooks สำหรับ Thaiprompt Affiliate System

## 📖 เกี่ยวกับ Git Hooks

Git Hooks เป็นสคริปต์ที่รันอัตโนมัติเมื่อมีเหตุการณ์บางอย่างเกิดขึ้นใน Git (เช่น ก่อน commit, หลัง merge)

ระบบนี้ใช้ Git Hooks เพื่อ **บังคับให้ปฏิบัติตามกฎการเขียนโค้ด** โดยอัตโนมัติ

---

## 🔧 การติดตั้ง

### วิธีที่ 1: ติดตั้งทั้งหมดพร้อมกัน (แนะนำ)

```bash
bash scripts/git-hooks/install.sh
```

### วิธีที่ 2: ติดตั้งเฉพาะ hook

```bash
cp scripts/git-hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

---

## 📋 Hooks ที่มีอยู่

### 1. Pre-Commit Hook (Seeder Verification)

**ไฟล์**: `scripts/git-hooks/pre-commit`

**วัตถุประสงค์**: บังคับให้ปฏิบัติตาม **CRITICAL RULE #1** จาก `.claude/seeder-guidelines.md`

**การทำงาน**:
1. ตรวจสอบว่ามีไฟล์ seeder ใน commit หรือไม่
2. ถ้ามี ให้รัน `php scripts/verify-seeders.php`
3. ถ้า verification ผ่าน → อนุญาตให้ commit
4. ถ้า verification ไม่ผ่าน → **บล็อกการ commit** และแสดงข้อความแก้ไข

**ตัวอย่างผลลัพธ์เมื่อมีปัญหา**:

```
═══════════════════════════════════════════════
  ❌ COMMIT BLOCKED
═══════════════════════════════════════════════

⚠  CRITICAL RULE #1 VIOLATION (seeder-guidelines.md)

You MUST add missing seeders to DatabaseSeeder.php before committing.

📖 Documentation:
   • .claude/seeder-guidelines.md (CRITICAL RULE #1)
   • .claude/instructions.md (Section 5: Seeder Synchronization)

🔧 To fix:
   1. Open database/seeders/DatabaseSeeder.php
   2. Add missing seeder(s) to the call() array
   3. Add descriptive comment for each seeder
   4. Run: php scripts/verify-seeders.php
   5. Try committing again
```

---

## 🚨 เมื่อ Hook บล็อก Commit

### ขั้นตอนแก้ไข:

1. **อ่านข้อความแจ้งเตือน** - Hook จะบอกว่าอะไรผิดพลาด

2. **แก้ไขปัญหา**:
   ```bash
   # ตรวจสอบว่า seeder ไหนไม่ได้อยู่ใน DatabaseSeeder.php
   php scripts/verify-seeders.php
   ```

3. **เพิ่ม Seeder ที่ขาดหายไปใน DatabaseSeeder.php**:
   ```php
   // database/seeders/DatabaseSeeder.php
   $this->call([
       ExistingSeeder::class,
       YourNewSeeder::class,  // เพิ่ม seeder ใหม่ที่นี่
   ]);
   ```

4. **ตรวจสอบอีกครั้ง**:
   ```bash
   php scripts/verify-seeders.php
   ```

5. **Commit อีกครั้ง**:
   ```bash
   git add database/seeders/DatabaseSeeder.php
   git commit -m "Your commit message"
   ```

---

## ⚠️ การข้าม Hook (ไม่แนะนำ)

ถ้าจำเป็นจริงๆ สามารถข้าม hook ได้ด้วย:

```bash
git commit --no-verify -m "Your message"
```

**⚠️ คำเตือน**: การข้าม hook อาจทำให้:
- Deployment ล้มเหลว
- Database ไม่สมบูรณ์
- Production มีปัญหา

**ควรใช้เฉพาะเมื่อ**:
- Emergency hotfix
- Hook มีปัญหา (bug)
- กำลังแก้ไข hook เอง

---

## 🔍 การตรวจสอบว่า Hook ติดตั้งแล้วหรือยัง

```bash
# ตรวจสอบว่ามี pre-commit hook
ls -la .git/hooks/pre-commit

# ตรวจสอบว่าเป็น executable
test -x .git/hooks/pre-commit && echo "✓ Installed and executable" || echo "✗ Not installed or not executable"
```

---

## 📚 เอกสารที่เกี่ยวข้อง

- **[.claude/seeder-guidelines.md](../../.claude/seeder-guidelines.md)** - CRITICAL RULE #1: DatabaseSeeder.php Synchronization
- **[.claude/instructions.md](../../.claude/instructions.md)** - คำแนะนำทั้งหมดสำหรับ Claude
- **[scripts/verify-seeders.php](../verify-seeders.php)** - Seeder verification script

---

## 🆘 Troubleshooting

### Hook ไม่ทำงาน

**สาเหตุที่เป็นไปได้**:
1. ไม่ได้ติดตั้ง hook
2. Hook ไม่เป็น executable
3. Hook อยู่ผิดที่

**วิธีแก้**:
```bash
# ติดตั้งใหม่
bash scripts/git-hooks/install.sh

# หรือติดตั้งด้วยตนเอง
cp scripts/git-hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### Hook รันแล้วเกิด Error

**ตรวจสอบ**:
```bash
# ทดสอบรัน hook โดยตรง
.git/hooks/pre-commit

# ตรวจสอบว่ามี verify-seeders.php
test -f scripts/verify-seeders.php && echo "✓ Found" || echo "✗ Not found"

# ทดสอบ verify-seeders.php
php scripts/verify-seeders.php
```

---

## 🔮 Hook ที่จะเพิ่มในอนาคต

- **pre-push**: ตรวจสอบว่ามี uncommitted changes หรือไม่
- **commit-msg**: ตรวจสอบ format ของ commit message
- **post-checkout**: อัพเดท dependencies อัตโนมัติ
- **post-merge**: รัน migrations และ seeders อัตโนมัติ

---

## 💡 Tips

1. **ติดตั้ง hooks ทันทีหลัง clone repository**
   ```bash
   git clone <repo>
   cd <repo>
   bash scripts/git-hooks/install.sh
   ```

2. **อัพเดท hooks เมื่อมีการเปลี่ยนแปลง**
   ```bash
   git pull
   bash scripts/git-hooks/install.sh
   ```

3. **สำหรับ Team Lead: แจ้งให้ทีมติดตั้ง hooks**
   - เพิ่มใน onboarding documentation
   - เพิ่มใน README.md
   - ตั้งเป็น required step ใน setup script

---

**Last Updated**: 2025-11-12
**Maintained by**: Development Team
