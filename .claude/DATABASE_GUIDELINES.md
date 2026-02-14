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

#### 3. **Foreign Key Constraints - ⚠️ กฎสำคัญมาก!**

**ปัญหาหลัก:** Migration อ้างอิง FK ไปยังตารางที่ยังไม่ถูกสร้าง

**กฎ #1: ตรวจสอบลำดับการสร้างตาราง**
- Migration รันตามลำดับ: วันที่ → เวลา → ชื่อไฟล์ (alphabetical)
- `2025_01_07_000001` รันก่อน `2025_11_03_000001`
- `2025_11_08_000007` รันก่อน `2025_11_08_000008`

**กฎ #2: ถ้าตารางที่อ้างอิงสร้างทีหลัง → ใช้ unsignedBigInteger**

```php
// ❌ ผิด - ถ้า payment_transactions ถูกสร้างใน migration ทีหลัง
$table->foreignId('payment_transaction_id')->constrained()->onDelete('set null');

// ✅ ถูกต้อง - ใช้ unsignedBigInteger + conditional FK
Schema::create('hotel_bookings', function (Blueprint $table) {
    // ...
    // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained()
    // เพราะ payment_transactions ถูกสร้างใน migration 2025_11_03 (ทีหลัง)
    $table->unsignedBigInteger('payment_transaction_id')->nullable();
    // ...
});

// เพิ่ม foreign key ถ้าตารางมีอยู่แล้ว
if (Schema::hasTable('payment_transactions')) {
    Schema::table('hotel_bookings', function (Blueprint $table) {
        $table->foreign('payment_transaction_id')
            ->references('id')
            ->on('payment_transactions')
            ->onDelete('set null');
    });
}
```

**กฎ #3: ตรวจสอบก่อนสร้าง migration ใหม่**

```php
// ถามตัวเองก่อนใช้ foreignId()->constrained():
// 1. ตาราง X ที่อ้างอิง ถูกสร้างเมื่อไหร่?
// 2. Migration ปัจจุบันมีวันที่/เวลา เท่าไหร่?
// 3. ถ้า X สร้างหลัง migration นี้ → ใช้ unsignedBigInteger
```

**ตัวอย่างปัญหาที่พบบ่อย:**

| Migration ปัจจุบัน | อ้างอิงไปยัง | ตารางสร้างเมื่อ | ผลลัพธ์ |
|-------------------|-------------|----------------|---------|
| `2025_01_07_000005` | `payment_transactions` | `2025_11_03` | ❌ ERROR |
| `2025_10_30_000015` | `payment_methods` | `2025_10_30_000016` | ❌ ERROR |
| `2025_11_08_000007` | `trading_trades` | `2025_11_08_000008` | ❌ ERROR |
| `2025_11_02_100007` | `line_avatars` | `2025_11_02_100008` | ❌ ERROR |

**Template สำหรับ FK ที่อ้างอิงตารางสร้างทีหลัง:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง xxx
     * คำอธิบายภาษาไทย
     */
    public function up(): void
    {
        if (Schema::hasTable('xxx')) {
            return;
        }

        Schema::create('xxx', function (Blueprint $table) {
            $table->id();

            // FK ที่ตารางมีอยู่แล้ว - ใช้ constrained() ได้
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // ⚠️ FK ที่ตารางสร้างทีหลัง - ใช้ unsignedBigInteger
            $table->unsignedBigInteger('future_table_id')->nullable();

            $table->timestamps();
        });

        // เพิ่ม FK หลังสร้างตาราง (ถ้าตารางที่อ้างอิงมีอยู่แล้ว)
        if (Schema::hasTable('future_table')) {
            Schema::table('xxx', function (Blueprint $table) {
                $table->foreign('future_table_id')
                    ->references('id')
                    ->on('future_table')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('xxx');
    }
};
```

#### 4. **Index Naming Conventions - ⚠️ ระวัง MySQL 64 char limit!**

**ปัญหา:** Laravel auto-generate ชื่อ index ยาวเกิน 64 ตัวอักษร

**กฎ: ถ้าชื่อตาราง + ชื่อคอลัมน์ยาว → กำหนดชื่อ index เอง**

```php
// ❌ ผิด - Laravel สร้างชื่อ: membership_retention_advance_renewals_user_id_valid_from_valid_until_index (78 chars!)
$table->index(['user_id', 'valid_from', 'valid_until']);

// ✅ ถูกต้อง - กำหนดชื่อสั้นๆ เอง
$table->index(['user_id', 'valid_from', 'valid_until'], 'mra_user_validity_idx');
```

**ตัวอย่างปัญหาที่พบบ่อย:**

| ชื่อ Auto-generated | ความยาว | ผลลัพธ์ |
|--------------------|---------|---------|
| `membership_retention_advance_renewals_user_id_valid_from_valid_until_index` | 78 | ❌ ERROR |
| `installment_payments_installment_plan_id_installment_number_index` | 65 | ❌ ERROR |

**วิธีตั้งชื่อ Index ที่ดี:**

```php
// ใช้ prefix สั้นๆ ของชื่อตาราง
// mra = membership_retention_advance
// ip = installment_payments
// ts = trading_signals

$table->index(['user_id', 'valid_from', 'valid_until'], 'mra_user_validity_idx');
$table->index(['installment_plan_id', 'installment_number'], 'ip_plan_number_idx');
$table->index(['bot_id', 'status'], 'ts_bot_status_idx');

// สำหรับ unique
$table->unique(['user_id', 'period_month'], 'mrt_user_period_unq');
```

**ความยาวชื่อ index สูงสุด:**
- MySQL: 64 characters
- PostgreSQL: 63 characters
- **แนะนำ: ไม่เกิน 50 characters เพื่อความปลอดภัย**

**Checklist ก่อนสร้าง Index:**
1. นับความยาว: `ชื่อตาราง` + `_` + `ชื่อคอลัมน์ทั้งหมด` + `_index`
2. ถ้า > 50 ตัวอักษร → กำหนดชื่อเอง
3. ใช้ prefix 2-3 ตัวอักษรแทนชื่อตารางยาว

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
- [ ] **ไม่มี MySQL-specific SQL** (ห้าม `SET FOREIGN_KEY_CHECKS`, `INFORMATION_SCHEMA`, `MODIFY COLUMN`)
- [ ] **ใช้ Laravel Schema methods เท่านั้น** (ไม่ใช้ raw DB::statement สำหรับ DDL)
- [ ] ทุก seeder ใหม่ถูกเพิ่มใน `DatabaseSeeder.php` แล้ว
- [ ] Seeder เรียงลำดับตาม dependencies ถูกต้อง
- [ ] Seeders เป็น idempotent (รันซ้ำได้)
- [ ] มี information messages ใน seeders
- [ ] ทดสอบ `php artisan migrate:fresh --seed` สำเร็จ
- [ ] รัน `./vendor/bin/pint` แล้ว (code style)

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

## 🚫 Cross-Database Compatibility (กฎบังคับ — ป้องกัน CI ล่ม)

> **⚠️ CRITICAL**: Migration ต้องรองรับทั้ง MySQL และ SQLite (ที่ใช้ในการทดสอบ)
> การใช้ MySQL-specific SQL จะทำให้ CI Tests ล่มทุกครั้ง

### ห้ามใช้ MySQL-specific SQL โดยตรง

```php
// ❌ ห้ามทำ — ใช้ได้เฉพาะ MySQL, SQLite จะ error
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::statement('SET FOREIGN_KEY_CHECKS=1');

// ✅ ใช้ Laravel Schema methods แทน — รองรับทุก database driver
Schema::disableForeignKeyConstraints();
Schema::enableForeignKeyConstraints();
```

```php
// ❌ ห้ามทำ — INFORMATION_SCHEMA ไม่มีใน SQLite
$indexes = DB::select('SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE ...');
$fks = DB::select('SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE ...');

// ✅ ใช้ Laravel 11 Schema methods แทน
$indexes = Schema::getIndexes('table_name');
$foreignKeys = Schema::getForeignKeys('table_name');
```

```php
// ❌ ห้ามทำ — ALTER TABLE ... MODIFY COLUMN เป็น MySQL-specific
DB::statement('ALTER TABLE xxx MODIFY COLUMN yyy VARCHAR(255)');

// ✅ ใช้ Laravel Schema แทน
Schema::table('xxx', function (Blueprint $table) {
    $table->string('yyy', 255)->nullable()->change();
});
```

### ใช้ SafeMigration Trait เสมอ (แนะนำ)

```php
use Database\Migrations\Concerns\SafeMigration;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('table_name', function (Blueprint $table) {
            // ✅ SafeMigration methods — ปลอดภัย รองรับทุก driver
            $this->safeAddColumn($table, 'table_name', 'new_col', function ($t) {
                $t->string('new_col')->nullable();
            });
        });

        // ✅ เช็ค index อย่างปลอดภัย
        $this->safeAddIndex('table_name', 'new_col');
    }
};
```

### สรุปสิ่งที่ห้ามใช้ใน Migrations

| ❌ ห้ามใช้ (MySQL-specific) | ✅ ใช้แทน (Cross-DB) |
|---------------------------|---------------------|
| `DB::statement('SET FOREIGN_KEY_CHECKS=0')` | `Schema::disableForeignKeyConstraints()` |
| `DB::statement('SET FOREIGN_KEY_CHECKS=1')` | `Schema::enableForeignKeyConstraints()` |
| `INFORMATION_SCHEMA.STATISTICS` | `Schema::getIndexes()` |
| `INFORMATION_SCHEMA.TABLE_CONSTRAINTS` | `Schema::getForeignKeys()` |
| `ALTER TABLE ... MODIFY COLUMN ...` | `$table->...->change()` |
| `DB::unprepared('ALTER TABLE ...')` | Laravel Blueprint methods |

---

## 🏭 Model Factories (กฎบังคับ — ป้องกัน CI Tests ล่ม)

> **⚠️ CRITICAL**: ทุก Model ที่ใช้ `::factory()` ในเทสต์ต้องมี Factory class

### กฎ: สร้าง Factory ทุกครั้งที่สร้าง Model ใหม่ที่มีเทสต์

```bash
# สร้าง Factory พร้อม Model
php artisan make:model Feature -mf  # -f = สร้าง Factory ด้วย

# หรือสร้าง Factory แยก
php artisan make:factory FeatureFactory --model=Feature
```

### ถ้าไม่มี Factory จะเกิด Error นี้ใน CI:

```
BadMethodCallException: Call to undefined method App\Models\Feature::factory()
```

### Factory files ต้องอยู่ใน `database/factories/`

```
database/factories/
├── UserFactory.php
├── GameFactory.php
├── WalletFactory.php
└── [Model]Factory.php  ← เพิ่มทุกครั้งที่สร้าง Model ใหม่
```

---

## 🎯 Summary

**กฎทองสำหรับ Database Management:**

1. ✅ **ทุก Migration** = ต้องมี `Schema::hasTable()` check
2. ✅ **ทุก Seeder** = ต้องอยู่ใน `DatabaseSeeder.php`
3. ✅ **ทุก Index** = ชื่อไม่เกิน 50 characters (กำหนดชื่อเองถ้ายาว)
4. ✅ **ทุก Foreign Key** = ตรวจสอบลำดับการสร้างตารางก่อน!
   - ถ้าตารางที่อ้างอิงมีอยู่แล้ว → ใช้ `foreignId()->constrained()`
   - ถ้าตารางที่อ้างอิงสร้างทีหลัง → ใช้ `unsignedBigInteger` + conditional FK
5. ✅ **ทุกอย่าง** = ต้อง Idempotent (รันซ้ำได้)
6. ✅ **ห้ามใช้ MySQL-specific SQL** = ใช้ Laravel Schema methods เท่านั้น (ป้องกัน CI ล่ม)
7. ✅ **ทุก Model ที่มีเทสต์** = ต้องมี Factory class ใน `database/factories/`

**⚠️ Checklist ก่อนสร้าง Migration:**

```
□ ตรวจสอบลำดับการสร้างตารางแล้ว (ไม่อ้างอิงตารางที่ยังไม่มี)
□ มี Schema::hasTable() check แล้ว
□ Index ที่มีชื่อยาว > 50 chars ได้กำหนดชื่อสั้นแล้ว
□ FK ที่อ้างอิงตารางสร้างทีหลังใช้ unsignedBigInteger แล้ว
□ Comment เป็นภาษาไทยแล้ว
```

**Remember:** Database integrity is critical. Always follow these guidelines!

---

*Last Updated: 2025-11-30*
*Version: 2.0*
