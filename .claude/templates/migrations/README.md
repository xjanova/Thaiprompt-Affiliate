# Migration Templates

Migration templates ที่ถูกต้องตามแนวทาง TP-Affiliate V3

## 📚 Templates Available

### 1️⃣ Create Table Template
**File:** `create_table_template.php`

**ใช้เมื่อ:** สร้างตารางใหม่ที่ยังไม่มีในฐานข้อมูล

**ลักษณะเด่น:**
- ✅ ใช้ `Schema::hasTable()` + `return` ได้ (ปลอดภัย)
- ✅ ระบุชื่อตารางใน `constrained()` ชัดเจน
- ✅ มี indexes ที่จำเป็น

**ตัวอย่างการใช้:**
```bash
# 1. Copy template
cp .claude/templates/migrations/create_table_template.php database/migrations/2025_11_17_000001_create_products_table.php

# 2. แก้ไข 'table_name' เป็น 'products'

# 3. Run migration
php artisan migrate
```

---

### 2️⃣ Add Columns Template
**File:** `add_columns_template.php`

**ใช้เมื่อ:** เพิ่มคอลัมน์ใหม่ในตารางที่มีอยู่แล้ว

**⚠️ CRITICAL:**
- ❌ ห้ามใช้ `Schema::hasTable()` + `return`
- ✅ ใช้ `Schema::hasColumn()` แทน

**ลักษณะเด่น:**
- ✅ เช็คแต่ละคอลัมน์ก่อนเพิ่ม
- ✅ ใช้ `Schema::table()` แทน `Schema::create()`
- ✅ ปลอดภัยเมื่อรันซ้ำ (idempotent)

**ตัวอย่างการใช้:**
```bash
# 1. Copy template
cp .claude/templates/migrations/add_columns_template.php database/migrations/2025_11_17_000002_add_phone_to_users_table.php

# 2. แก้ไข:
#    - 'table_name' เป็น 'users'
#    - 'new_column' เป็น 'phone'
#    - เพิ่ม/ลด columns ตามต้องการ

# 3. Run migration
php artisan migrate
```

---

### 3️⃣ Safe Migration Template (⭐ RECOMMENDED)
**File:** `safe_migration_template.php`

**ใช้เมื่อ:** ทุกกรณี (แนะนำให้ใช้เสมอ!)

**ลักษณะเด่น:**
- ⭐ ปลอดภัยที่สุด!
- ✅ เช็คอัตโนมัติทุกอย่าง (columns, indexes, foreign keys)
- ✅ ไม่ต้องเขียน `Schema::hasColumn()` เอง
- ✅ รองรับ batch operations

**ตัวอย่างการใช้:**
```bash
# 1. Copy template
cp .claude/templates/migrations/safe_migration_template.php database/migrations/2025_11_17_000003_update_users_table.php

# 2. แก้ไข table_name และ columns

# 3. Run migration
php artisan migrate
```

---

## 🎯 แนวทางการเลือก Template

| กรณี | Template ที่แนะนำ | เหตุผล |
|------|-------------------|--------|
| สร้างตารางใหม่ | `create_table_template.php` | เหมาะสมและปลอดภัย |
| เพิ่มคอลัมน์ | `safe_migration_template.php` ⭐ | ปลอดภัยที่สุด |
| เพิ่มคอลัมน์ (ไม่ใช้ trait) | `add_columns_template.php` | ใช้ได้แต่ต้องระวัง |
| แก้ไข schema ซับซ้อน | `safe_migration_template.php` ⭐ | จัดการทุกอย่างได้ |

---

## 📖 Best Practices

### 1. ✅ DO (ควรทำ)

```php
// ✅ ระบุชื่อตารางชัดเจนใน constrained()
$table->foreignId('user_id')
    ->constrained('users')  // ระบุชัดเจน!
    ->onDelete('cascade');

// ✅ เช็คคอลัมน์ก่อนเพิ่ม
if (!Schema::hasColumn('users', 'phone')) {
    $table->string('phone')->nullable();
}

// ✅ ใช้ SafeMigration trait (แนะนำ)
use SafeMigration;
$this->safeAddColumn($table, 'users', 'phone', fn($t) => $t->string('phone')->nullable());
```

### 2. ❌ DON'T (ไม่ควรทำ)

```php
// ❌ ห้ามใช้ constrained() โดยไม่ระบุตาราง
$table->foreignId('user_id')->constrained();  // Laravel อาจ pluralize ผิด!

// ❌ ห้ามใช้ hasTable() + return เมื่อเพิ่มคอลัมน์
if (Schema::hasTable('users')) {
    return;  // คอลัมน์ใหม่จะไม่ถูกสร้าง!
}

// ❌ ห้ามสร้าง timestamp NOT NULL โดยไม่มี default
$table->timestamp('created_at');  // Error: Invalid default value
```

---

## 🔧 Smart Migration System

deploy.sh มีระบบ Smart Migration ที่จะ:

✅ **สร้างตารางใหม่** ถ้ายังไม่มี
✅ **เพิ่มคอลัมน์ใหม่** ในตารางที่มีอยู่แล้ว
✅ **ข้ามคอลัมน์** ที่มีอยู่แล้ว

**วิธีใช้:**
```bash
# Smart Migration จะรันอัตโนมัติเมื่อ deploy
./deploy.sh

# หรือรันด้วยตนเอง
php artisan migrate:smart --force
```

---

## 📚 เอกสารเพิ่มเติม

- **CLAUDE.md** - แนวทางการเขียน migrations (บังคับอ่าน!)
- **database/migrations/README_MIGRATIONS.md** - คู่มือ SafeMigration trait
- **.claude/DATABASE_GUIDELINES.md** - Database best practices

---

## 💡 Tips

1. **เสมอใช้ SafeMigration trait** เมื่อเพิ่มคอลัมน์
2. **ระบุชื่อตารางชัดเจน** ใน `constrained('table_name')`
3. **ทดสอบ migrate + rollback** บน local ก่อน deploy
4. **อ่าน CLAUDE.md** ก่อนเริ่มเขียน migration

---

**Version:** 3.0.0
**Last Updated:** 2025-11-17
**Maintained By:** TP-Affiliate Development Team
