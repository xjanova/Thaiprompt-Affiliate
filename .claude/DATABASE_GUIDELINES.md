# Database Guidelines - Thaiprompt Affiliate System

## 📋 Migration Best Practices

### ✅ กฎสำคัญที่ต้องปฏิบัติเสมอ

#### 1. **Table Existence Check (ตรวจสอบตารางก่อนสร้าง)**

**กฎบังคับ**: ทุก migration ที่สร้างตารางใหม่ **ต้องมี** table existence check เสมอ

```php
public function up(): void
{
    // ✅ ถูกต้อง - ตรวจสอบก่อนสร้างตาราง
    if (Schema::hasTable('table_name')) {
        return;
    }

    Schema::create('table_name', function (Blueprint $table) {
        // ... table definition
    });
}
```

**เหตุผล:**
- ป้องกัน error `SQLSTATE[42S01]: Base table or view already exists`
- รองรับกรณีที่ตารางมีอยู่แล้วแต่ migration ยังไม่ถูกบันทึก
- ทำให้ migration idempotent (รันซ้ำได้โดยไม่เกิด error)
- ป้องกันปัญหาเมื่อมีการ rollback และ migrate ซ้ำ

**ตัวอย่างที่ไม่ถูกต้อง:**

```php
// ❌ ผิด - ไม่มีการตรวจสอบ
public function up(): void
{
    Schema::create('table_name', function (Blueprint $table) {
        // ...
    });
}
```

#### 2. **Column/Index Modification Checks**

เมื่อแก้ไข column หรือ index ที่มีอยู่แล้ว ต้องตรวจสอบก่อนเสมอ:

```php
public function up(): void
{
    Schema::table('table_name', function (Blueprint $table) {
        // ตรวจสอบก่อนเพิ่ม column
        if (!Schema::hasColumn('table_name', 'new_column')) {
            $table->string('new_column')->nullable();
        }
    });
}
```

#### 3. **Foreign Key Constraints**

```php
// ✅ ถูกต้อง - ใช้ constrained() สำหรับ foreign keys
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// ตรวจสอบก่อนเพิ่ม foreign key
if (!$this->foreignKeyExists('table_name', 'table_name_user_id_foreign')) {
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
}
```

#### 4. **Index Naming Conventions**

เมื่อสร้าง unique constraint หรือ index ที่มีชื่อยาว ให้กำหนดชื่อสั้นๆ:

```php
// ✅ ถูกต้อง - กำหนดชื่อ index สั้นๆ
$table->unique(['user_id', 'crypto_currency_id', 'network'], 'cda_user_currency_network_unq');

// ❌ ผิด - ให้ Laravel สร้างชื่ออัตโนมัติ (อาจยาวเกินไป)
$table->unique(['user_id', 'crypto_currency_id', 'network']);
```

**ความยาวชื่อ index สูงสุด:**
- MySQL: 64 characters
- PostgreSQL: 63 characters
- แนะนำ: ไม่เกิน 50 characters เพื่อความปลอดภัย

---

## 🌱 Seeder Best Practices

### ✅ กฎสำคัญสำหรับ Seeders

#### 1. **ทุก Seeder ต้องอยู่ใน DatabaseSeeder.php**

**กฎบังคับ**: Seeder ทุกตัวที่สร้างขึ้นมา **ต้องถูกเรียกใช้ใน DatabaseSeeder.php เสมอ**

```php
// ใน database/seeders/DatabaseSeeder.php

public function run(): void
{
    $this->call([
        // เรียงลำดับตาม dependencies
        UserSeeder::class,
        ProductCategorySeeder::class,  // ต้องมาก่อน ProductSeeder
        ProductSeeder::class,
        // ... seeders อื่นๆ
        NewFeatureSeeder::class,       // ← ต้องเพิ่ม seeder ใหม่ที่นี่
    ]);
}
```

**วิธีตรวจสอบ Seeder Integrity:**

```bash
# รันสคริปต์ตรวจสอบ (ถ้ามี)
php artisan db:seed:verify

# หรือตรวจสอบด้วยตนเอง
# 1. นับจำนวนไฟล์ seeder (ยกเว้น DatabaseSeeder.php)
ls database/seeders/*.php | grep -v DatabaseSeeder | wc -l

# 2. นับจำนวน seeders ใน DatabaseSeeder.php
grep "::class" database/seeders/DatabaseSeeder.php | grep -v "//" | wc -l

# ตัวเลขทั้งสองต้องเท่ากัน
```

#### 2. **Seeder Ordering (ลำดับการ Seed)**

เรียงลำดับ seeders ตาม dependencies:

```php
$this->call([
    // 1. Core Settings (ไม่ depend อะไร)
    AppNameSettingSeeder::class,
    ThemeSeeder::class,

    // 2. User & Demo Data (depend on settings)
    DemoUsersSeeder::class,

    // 3. Categories ก่อน Items
    ProductCategorySeeder::class,    // ← มาก่อน
    ProductSeeder::class,            // ← ต้อง depend on categories

    // 4. Parent ก่อน Child relationships
    SoftwareProductSeeder::class,              // ← parent
    SoftwareProductOptionSeeder::class,        // ← child (ถ้ามี)
]);
```

#### 3. **Idempotent Seeders**

Seeders ควรรันได้หลายครั้งโดยไม่เกิด error:

```php
// ✅ ถูกต้อง - ตรวจสอบก่อนสร้าง
public function run(): void
{
    if (SoftwareProductCategory::where('slug', 'mlm-network-marketing')->exists()) {
        $this->command->info('MLM category already exists, skipping...');
        return;
    }

    SoftwareProductCategory::create([
        'name' => 'ระบบ MLM / เครือข่าย',
        'slug' => 'mlm-network-marketing',
        // ...
    ]);
}
```

#### 4. **Seeder Information Messages**

ใส่ข้อความแจ้งเตือนเพื่อให้รู้ว่า seeder กำลังทำอะไร:

```php
public function run(): void
{
    $this->command->info('');
    $this->command->info('🌱 Seeding software products...');

    // ... seeding logic

    $this->command->info('✅ Software products seeded successfully!');
}
```

---

## 🔍 Verification Checklist

### ก่อน Commit Migrations/Seeders ใหม่

- [ ] ทุก migration มี `Schema::hasTable()` check
- [ ] ทุก column modification มี `Schema::hasColumn()` check
- [ ] Foreign keys ใช้ `constrained()` หรือมีการตรวจสอบก่อนเพิ่ม
- [ ] Index ที่มีชื่อยาวถูกกำหนดชื่อสั้นแล้ว (≤50 chars)
- [ ] ทุก seeder ใหม่ถูกเพิ่มใน `DatabaseSeeder.php` แล้ว
- [ ] Seeder เรียงลำดับตาม dependencies ถูกต้อง
- [ ] Seeders เป็น idempotent (รันซ้ำได้)
- [ ] มี information messages ใน seeders
- [ ] ทดสอบ `php artisan migrate:fresh --seed` สำเร็จ

---

## 🚨 Common Issues และวิธีแก้

### Issue 1: "Table already exists" Error

**Symptoms:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'xxx' already exists
```

**Solution:**
เพิ่ม table existence check ใน migration:

```php
if (Schema::hasTable('table_name')) {
    return;
}
```

### Issue 2: "Seeder not found in DatabaseSeeder"

**Symptoms:**
```
Found 1 seeder(s) NOT included in DatabaseSeeder.php:
  • NewFeatureSeeder
```

**Solution:**
เพิ่ม seeder ใน `DatabaseSeeder.php`:

```php
$this->call([
    // ... existing seeders
    NewFeatureSeeder::class,  // ← เพิ่มตรงนี้
]);
```

### Issue 3: "Specified key was too long" Error

**Symptoms:**
```
SQLSTATE[42000]: Syntax error: 1071 Specified key was too long; max key length is 767 bytes
```

**Solution:**
กำหนดชื่อ index สั้นๆ เอง:

```php
$table->unique(['long_column_name', 'another_long_name'], 'short_idx_name');
```

### Issue 4: Foreign Key Constraint Errors

**Symptoms:**
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solution:**
1. ตรวจสอบลำดับการ migrate (parent tables ต้องมาก่อน)
2. ตรวจสอบว่า referenced table/column มีอยู่จริง
3. ใช้ `constrained()` สำหรับ foreignId:

```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
```

---

## 📝 Quick Reference

### การสร้าง Migration ใหม่

```bash
php artisan make:migration create_table_name_table
```

**Template:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ เพิ่ม check นี้เสมอ
        if (Schema::hasTable('table_name')) {
            return;
        }

        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### การสร้าง Seeder ใหม่

```bash
php artisan make:seeder NewFeatureSeeder
```

**Template:**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NewFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding new feature...');

        // ✅ ตรวจสอบก่อนสร้าง (idempotent)
        if (Model::where('key', 'value')->exists()) {
            $this->command->info('Data already exists, skipping...');
            return;
        }

        // Seeding logic here

        $this->command->info('✅ New feature seeded successfully!');
    }
}
```

**ห้ามลืม:** เพิ่มใน `DatabaseSeeder.php`:

```php
$this->call([
    // ... existing seeders
    NewFeatureSeeder::class,  // ← เพิ่มตรงนี้ด้วย!
]);
```

---

## 🎯 Summary

**กฎทองสำหรับ Database Management:**

1. ✅ **ทุก Migration** = ต้องมี `Schema::hasTable()` check
2. ✅ **ทุก Seeder** = ต้องอยู่ใน `DatabaseSeeder.php`
3. ✅ **ทุก Index** = ชื่อไม่เกิน 50 characters
4. ✅ **ทุก Foreign Key** = ใช้ `constrained()`
5. ✅ **ทุกอย่าง** = ต้อง Idempotent (รันซ้ำได้)

**Remember:** Database integrity is critical. Always follow these guidelines!

---

*Last Updated: 2025-11-08*
*Version: 1.0*
