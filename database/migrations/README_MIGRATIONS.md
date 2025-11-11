# Migration Best Practices

คู่มือการเขียน Migration ที่ปลอดภัยสำหรับ Production

## ปัญหาที่พบบ่อย

### 1. Duplicate Column Error
เกิดเมื่อ migration run ไปบางส่วนแล้วล้มเหลว แล้วพยายาม run ซ้ำ

```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'xxx'
```

### 2. Missing Column in after() Clause
เกิดเมื่ออ้างอิง column ที่ไม่มีจริงใน `after()`

### 3. Invalid Default Value for Timestamp
เกิดเมื่อสร้าง timestamp column ที่เป็น NOT NULL แต่ไม่มี default value

```
SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'column_name'
```

**สาเหตุ:** MySQL ต้องการให้ timestamp columns ที่เป็น NOT NULL มี default value

### 4. Foreign Key Constraint Incorrectly Formed
เกิดเมื่อ Laravel auto-pluralization สร้างชื่อตารางผิด

```
SQLSTATE[HY000]: General error: 1005 Can't create table (errno: 150 "Foreign key constraint is incorrectly formed")
```

**สาเหตุ:** Laravel pluralize ชื่อตารางอัตโนมัติ เช่น `trend_data` กลายเป็น `trend_datas`

## วิธีแก้ปัญหา: ใช้ SafeMigration Trait

### Basic Usage

```php
<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เพิ่ม column อย่างปลอดภัย
            $this->safeAddColumn($table, 'users', 'phone', function($table) {
                $table->string('phone')->nullable();
            });

            // เพิ่ม column พร้อม position
            $this->safeAddColumn($table, 'users', 'verified', function($table) {
                $table->boolean('verified')->default(false)->after('email');
            });
        });
    }

    public function down(): void
    {
        // ลบ columns อย่างปลอดภัย
        $this->safeDropColumn('users', ['phone', 'verified']);
    }
};
```

## Available Methods

### 1. Column Operations

#### `safeAddColumn()`
เพิ่ม column โดยเช็คว่ามีอยู่แล้วหรือไม่

```php
$this->safeAddColumn($table, 'table_name', 'column_name', function($table) {
    $table->string('column_name')->nullable();
});
```

#### `safeDropColumn()`
ลบ column(s) โดยเช็คว่ามีอยู่หรือไม่

```php
$this->safeDropColumn('table_name', 'column_name');
$this->safeDropColumn('table_name', ['col1', 'col2', 'col3']);
```

#### `safeRenameColumn()`
เปลี่ยนชื่อ column อย่างปลอดภัย

```php
$this->safeRenameColumn('table_name', 'old_name', 'new_name');
```

#### `safeAddColumns()`
เพิ่มหลาย columns พร้อมกัน

```php
$this->safeAddColumns($table, 'users', [
    'first_name' => fn($t) => $t->string('first_name')->nullable(),
    'last_name' => fn($t) => $t->string('last_name')->nullable(),
    'age' => fn($t) => $t->integer('age')->default(0),
]);
```

### 2. Index Operations

#### `safeAddIndex()`
เพิ่ม index อย่างปลอดภัย

```php
// Simple index
$this->safeAddIndex('users', 'email');

// Unique index
$this->safeAddIndex('users', 'email', null, 'unique');

// Composite index
$this->safeAddIndex('users', ['first_name', 'last_name']);

// Custom index name
$this->safeAddIndex('users', 'email', 'custom_email_idx');

// Fulltext index
$this->safeAddIndex('posts', ['title', 'content'], null, 'fulltext');
```

#### `safeDropIndex()`
ลบ index อย่างปลอดภัย

```php
$this->safeDropIndex('users', 'users_email_index');
```

### 3. Foreign Key Operations

#### `safeAddForeign()`
เพิ่ม foreign key อย่างปลอดภัย

```php
// Basic foreign key
$this->safeAddForeign('posts', 'user_id', 'users');

// Custom foreign column
$this->safeAddForeign('posts', 'author_id', 'users', 'id');

// Custom constraint name
$this->safeAddForeign('posts', 'user_id', 'users', 'id', 'posts_author_fk');
```

#### `safeDropForeign()`
ลบ foreign key อย่างปลอดภัย

```php
$this->safeDropForeign('posts', 'posts_user_id_foreign');
```

### 4. Table Operations

#### `safeCreateTable()`
สร้างตารางถ้ายังไม่มี

```php
$this->safeCreateTable('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

#### `safeDropTable()`
ลบตารางถ้ามีอยู่

```php
$this->safeDropTable('posts');
```

## ตัวอย่างการใช้งานจริง

### Example 1: เพิ่ม Columns แบบ Complex

```php
<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เพิ่ม columns ทีละตัว
            $this->safeAddColumn($table, 'users', 'first_name', function($table) {
                $table->string('first_name')->nullable()->after('name');
            });

            $this->safeAddColumn($table, 'users', 'last_name', function($table) {
                $table->string('last_name')->nullable()->after('first_name');
            });

            $this->safeAddColumn($table, 'users', 'phone', function($table) {
                $table->string('phone')->nullable()->after('email');
            });

            $this->safeAddColumn($table, 'users', 'birth_date', function($table) {
                $table->date('birth_date')->nullable()->after('phone');
            });
        });

        // เพิ่ม indexes
        $this->safeAddIndex('users', 'phone', null, 'unique');
        $this->safeAddIndex('users', ['first_name', 'last_name']);
    }

    public function down(): void
    {
        // ลบ indexes ก่อน
        $this->safeDropIndex('users', 'users_phone_unique');
        $this->safeDropIndex('users', 'users_first_name_last_name_index');

        // แล้วลบ columns
        $this->safeDropColumn('users', [
            'first_name',
            'last_name',
            'phone',
            'birth_date',
        ]);
    }
};
```

### Example 2: เพิ่ม Foreign Keys

```php
<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        // สร้างตาราง posts
        $this->safeCreateTable('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });

        // เพิ่ม foreign keys
        $this->safeAddForeign('posts', 'user_id', 'users');
        $this->safeAddForeign('posts', 'category_id', 'categories');

        // เพิ่ม indexes
        $this->safeAddIndex('posts', 'user_id');
        $this->safeAddIndex('posts', 'category_id');
        $this->safeAddIndex('posts', 'title');
    }

    public function down(): void
    {
        // ลบ foreign keys ก่อน
        $this->safeDropForeign('posts', 'posts_user_id_foreign');
        $this->safeDropForeign('posts', 'posts_category_id_foreign');

        // แล้วลบตาราง
        $this->safeDropTable('posts');
    }
};
```

### Example 3: เพิ่ม Columns แบบ Batch

```php
<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // เพิ่มหลาย columns พร้อมกัน
            $this->safeAddColumns($table, 'products', [
                'sku' => fn($t) => $t->string('sku')->unique()->after('name'),
                'barcode' => fn($t) => $t->string('barcode')->nullable()->after('sku'),
                'weight' => fn($t) => $t->decimal('weight', 8, 2)->default(0)->after('barcode'),
                'dimensions' => fn($t) => $t->json('dimensions')->nullable()->after('weight'),
                'in_stock' => fn($t) => $t->boolean('in_stock')->default(true)->after('dimensions'),
                'stock_quantity' => fn($t) => $t->integer('stock_quantity')->default(0)->after('in_stock'),
            ]);
        });

        // เพิ่ม indexes
        $this->safeAddIndex('products', 'sku', null, 'unique');
        $this->safeAddIndex('products', 'barcode');
        $this->safeAddIndex('products', 'in_stock');
    }

    public function down(): void
    {
        // ลบ indexes
        $this->safeDropIndex('products', 'products_sku_unique');
        $this->safeDropIndex('products', 'products_barcode_index');
        $this->safeDropIndex('products', 'products_in_stock_index');

        // ลบ columns
        $this->safeDropColumn('products', [
            'sku',
            'barcode',
            'weight',
            'dimensions',
            'in_stock',
            'stock_quantity',
        ]);
    }
};
```

## กฎเกณฑ์สำคัญในการสร้าง Migration

### 1. Timestamp Columns - ต้องมี Default Value เสมอ

✅ **ถูกต้อง:**
```php
// ใช้ useCurrent() สำหรับ timestamp NOT NULL
$table->timestamp('started_at')->useCurrent();
$table->timestamp('created_at')->useCurrent();
$table->timestamp('published_at')->useCurrent();

// หรือใช้ nullable() ถ้าอนุญาตให้เป็น NULL
$table->timestamp('completed_at')->nullable();
$table->timestamp('deleted_at')->nullable();
```

❌ **ผิด - จะเกิด Error:**
```php
// ห้ามสร้าง timestamp NOT NULL โดยไม่มี default
$table->timestamp('started_at'); // Error: Invalid default value
$table->timestamp('created_at'); // Error: Invalid default value
```

**สรุป:**
- timestamp NOT NULL → ใช้ `->useCurrent()`
- timestamp ที่อนุญาต NULL → ใช้ `->nullable()`

### 2. Foreign Keys - ระบุชื่อตารางชัดเจน

Laravel มีระบบ auto-pluralization ที่อาจสร้างชื่อตารางผิด ดังนั้นควรระบุชื่อตารางชัดเจนเสมอ

✅ **ถูกต้อง:**
```php
// ระบุชื่อตารางชัดเจนใน constrained()
$table->foreignId('trend_data_id')
    ->constrained('trend_data')  // ระบุชัดเจนว่าเป็นตาราง trend_data
    ->onDelete('cascade');

$table->foreignId('trend_keyword_id')
    ->constrained('trend_keywords')  // ระบุชัดเจน
    ->onDelete('cascade');

$table->foreignId('user_id')
    ->constrained('users')  // ระบุชัดเจน
    ->onDelete('cascade');
```

❌ **ผิด - อาจเกิด Error:**
```php
// Laravel จะ pluralize อัตโนมัติและอาจผิด
$table->foreignId('trend_data_id')
    ->constrained()  // Laravel อาจเปลี่ยนเป็น trend_datas (ผิด!)
    ->onDelete('cascade');
```

**ตัวอย่างที่มีปัญหา:**
- `trend_data` → Laravel pluralize เป็น `trend_datas` ❌
- `video_content` → Laravel pluralize เป็น `video_contents` ✅ (ถ้าชื่อตารางเป็น contents)
- `user_data` → Laravel pluralize เป็น `user_datas` ❌

**กฎทอง:** ระบุชื่อตารางใน `constrained('table_name')` เสมอ เพื่อหลีกเลี่ยงปัญหา

### 3. ตัวอย่างการสร้างตาราง Pivot/Relation

✅ **ถูกต้อง - ตัวอย่างเต็มรูปแบบ:**
```php
Schema::create('trend_data_keyword', function (Blueprint $table) {
    $table->id();

    // Foreign keys - ระบุชื่อตารางชัดเจน
    $table->foreignId('trend_data_id')
        ->constrained('trend_data')  // ไม่ใช่ trend_datas
        ->onDelete('cascade');

    $table->foreignId('trend_keyword_id')
        ->constrained('trend_keywords')
        ->onDelete('cascade');

    // Timestamp columns - มี default value
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent();

    // หรือใช้ timestamps() ซึ่งจะสร้าง nullable columns
    // $table->timestamps();

    // Indexes
    $table->unique(['trend_data_id', 'trend_keyword_id']);
    $table->index('trend_data_id');
});
```

### 4. การใช้ timestamps() vs สร้าง timestamp เอง

Laravel's `timestamps()` method จะสร้าง columns แบบ nullable:

```php
$table->timestamps();
// สร้าง: created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
```

ถ้าต้องการ NOT NULL ให้สร้างเอง:

```php
$table->timestamp('created_at')->useCurrent();
$table->timestamp('updated_at')->useCurrent();
// สร้าง: created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

## Best Practices สำหรับ Production

### 1. เสมอใช้ Safe Methods
✅ **ถูกต้อง:**
```php
$this->safeAddColumn($table, 'users', 'email', function($table) {
    $table->string('email')->nullable();
});
```

❌ **ไม่แนะนำ:**
```php
$table->string('email')->nullable(); // ไม่มีการเช็ค
```

### 2. เช็ค Column Reference ใน after()
✅ **ถูกต้อง:**
```php
// เช็คว่า 'name' มีอยู่จริงในตาราง
$table->string('first_name')->after('name');
```

❌ **ไม่แนะนำ:**
```php
// อ้างอิง column ที่ไม่มี
$table->string('first_name')->after('nonexistent_column');
```

### 3. ลำดับการลบ Foreign Keys และ Indexes
✅ **ถูกต้อง:**
```php
public function down(): void
{
    // 1. ลบ foreign keys ก่อน
    $this->safeDropForeign('posts', 'posts_user_id_foreign');

    // 2. แล้วลบ indexes
    $this->safeDropIndex('posts', 'posts_user_id_index');

    // 3. สุดท้ายลบ columns หรือตาราง
    $this->safeDropColumn('posts', 'user_id');
}
```

### 4. ทดสอบ Migration บน Local ก่อน Deploy

```bash
# 1. Migrate ขึ้น
php artisan migrate

# 2. Rollback
php artisan migrate:rollback

# 3. Migrate ขึ้นอีกครั้ง (ทดสอบ idempotency)
php artisan migrate

# 4. ถ้าผ่านหมดก็ปลอดภัยสำหรับ production
```

### 5. สร้าง Migration แยกตาราง

✅ **ถูกต้อง:** แยกเป็นหลาย migrations
```
2025_01_01_000001_create_users_table.php
2025_01_01_000002_add_profile_fields_to_users.php
2025_01_01_000003_add_indexes_to_users.php
```

❌ **ไม่แนะนำ:** รวมทุกอย่างใน migration เดียว

## Troubleshooting

### ถ้า Migration ล้มเหลวแล้ว

1. **เช็คสถานะ:**
```bash
php artisan migrate:status
```

2. **Rollback migration ที่ล้มเหลว:**
```bash
php artisan migrate:rollback --step=1
```

3. **Run migration ใหม่:**
```bash
php artisan migrate
```

### ถ้า Rollback ไม่ได้

1. **เช็คว่า column มีอยู่จริง:**
```php
// ใช้ safe methods ใน down() ด้วย
public function down(): void
{
    $this->safeDropColumn('users', ['phone', 'verified']);
}
```

2. **หรือ manually drop columns:**
```sql
ALTER TABLE users DROP COLUMN phone;
ALTER TABLE users DROP COLUMN verified;
```

## Checklist ก่อนสร้าง Migration ใหม่

ก่อนสร้างหรือรัน migration ใหม่ ให้ตรวจสอบรายการต่อไปนี้:

### ✅ Timestamp Columns
- [ ] timestamp NOT NULL ทุกตัวมี `->useCurrent()`
- [ ] timestamp ที่เป็น NULL มี `->nullable()`
- [ ] ไม่มี `$table->timestamp('xxx')` ที่ไม่มีทั้ง useCurrent() และ nullable()

### ✅ Foreign Keys
- [ ] ระบุชื่อตารางชัดเจนใน `->constrained('table_name')`
- [ ] ไม่ใช้ `->constrained()` แบบไม่มีพารามิเตอร์
- [ ] ตรวจสอบว่าตารางที่อ้างอิงมีอยู่จริง
- [ ] มี `onDelete()` และ `onUpdate()` ที่เหมาะสม (cascade, set null, restrict)

### ✅ Table Structure
- [ ] ชื่อตารางเป็น snake_case และเป็นพหูพจน์ (users, products, trend_keywords)
- [ ] มี primary key (ใช้ `$table->id()`)
- [ ] มี indexes ที่จำเป็น (foreign keys, unique constraints)
- [ ] มี timestamps ถ้าต้องการ track การเปลี่ยนแปลง

### ✅ Column Definitions
- [ ] ตั้งชื่อ column เป็น snake_case
- [ ] ใช้ data types ที่เหมาะสม (string, text, integer, decimal, boolean, json)
- [ ] ตั้ง default values ที่เหมาะสม
- [ ] ใช้ nullable() เฉพาะ columns ที่อนุญาตให้เป็น null

### ✅ Migration Order
- [ ] migrations ที่สร้างตารางต้นทาง (parent) มาก่อนตารางปลายทาง (child)
- [ ] ตัวอย่าง: users → posts → comments (ตามลำดับ foreign key)
- [ ] ตรวจสอบ timestamp ในชื่อไฟล์ให้ถูกต้อง

### ✅ Testing
- [ ] ทดสอบ `php artisan migrate` (ขึ้น)
- [ ] ทดสอบ `php artisan migrate:rollback` (ลง)
- [ ] ทดสอบ `php artisan migrate` อีกครั้ง (idempotency)
- [ ] เช็ค migration status: `php artisan migrate:status`

### ✅ Rollback Safety
- [ ] เขียน `down()` method ให้ครบถ้วน
- [ ] ลบ foreign keys ก่อนลบ indexes
- [ ] ลบ indexes ก่อนลบ columns
- [ ] ลบ columns ก่อนลบตาราง

## ตัวอย่าง Migration Template ที่สมบูรณ์

```php
<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::create('example_table', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Foreign Keys - ระบุชื่อตารางชัดเจน
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Regular Columns - กำหนด type และ constraints
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);

            // Custom Timestamp Columns - ใช้ useCurrent()
            $table->timestamp('published_at')->useCurrent();
            $table->timestamp('expired_at')->nullable();

            // Laravel Timestamps - nullable by default
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->index(['user_id', 'is_active']);
            $table->unique('title');
        });

        // Foreign Keys (alternative method)
        // $this->safeAddForeign('example_table', 'user_id', 'users');
    }

    public function down(): void
    {
        // ลบตารางพร้อม foreign keys
        Schema::dropIfExists('example_table');

        // หรือลบทีละส่วนถ้าต้องการ
        // $this->safeDropForeign('example_table', 'example_table_user_id_foreign');
        // $this->safeDropTable('example_table');
    }
};
```

## 🚀 Smart Migration System

ระบบนี้จะช่วยจัดการกรณีที่ตารางมีอยู่แล้วแต่โครงสร้างไม่ตรงกับ migration

### การทำงาน

1. ✅ **ตรวจสอบ** - เช็คว่าตารางมีอยู่หรือยัง
2. 🔍 **เปรียบเทียบ** - เปรียบเทียบ schema ของตารางกับ migration
3. ➕ **เพิ่มคอลัมน์** - เพิ่มคอลัมน์ที่ยังไม่มีอัตโนมัติ
4. ⏭️ **ข้าม** - ข้ามถ้า schema ตรงกันแล้ว

### การใช้งาน

```bash
# รัน Smart Migration
php artisan migrate:smart --force

# ระบบจะแสดงผลการทำงาน:
# ✓ Tables created: X
# ✓ Tables updated: Y
# ✓ Columns added: Z
```

### ตัวอย่างการทำงาน

**กรณีที่ 1: ตารางยังไม่มี**
```
→ Processing: 2025_01_08_000003_create_trend_keywords_table
  → Creating new table 'trend_keywords'...
  ✓ Table created successfully
```

**กรณีที่ 2: ตารางมีแล้วแต่ขาดบางคอลัมน์**
```
→ Processing: 2025_01_08_000003_create_trend_keywords_table
  → Table 'trend_keywords' exists, checking schema...
  → Adding 2 missing column(s)...
    ✓ Added: last_seen_at
    ✓ Added: metadata
```

**กรณีที่ 3: Schema ตรงกันแล้ว**
```
→ Processing: 2025_01_08_000003_create_trend_keywords_table
  → Table 'trend_keywords' exists, checking schema...
  ✓ Schema up to date (skipped)
```

### ความสามารถ

Smart Migration สามารถจัดการ:

- ✅ เพิ่มคอลัมน์ใหม่ที่ยังไม่มี
- ✅ สร้างตารางใหม่ถ้ายังไม่มี
- ✅ ตรวจสอบและข้ามถ้า schema ตรงกันแล้ว
- ✅ รองรับ data types ทั่วไป (string, text, integer, decimal, boolean, json, timestamp, date)
- ✅ รองรับ foreign keys
- ✅ รองรับ timestamps(), softDeletes(), id()

### ข้อจำกัด

- ⚠️ ไม่สามารถแก้ไข data type ของคอลัมน์ที่มีอยู่แล้ว
- ⚠️ ไม่สามารถลบคอลัมน์
- ⚠️ ไม่สามารถเปลี่ยนชื่อคอลัมน์
- ⚠️ Indexes และ foreign keys อาจต้องสร้างด้วยตนเอง

สำหรับกรณีข้างต้น ให้ใช้ migration ปกติ หรือ alter table ด้วยตนเอง

## คำสั่ง Artisan ที่เป็นประโยชน์

```bash
# สร้าง migration ใหม่
php artisan make:migration create_example_table

# ตรวจสอบสถานะ migrations
php artisan migrate:status

# รัน Smart Migration (แนะนำ)
php artisan migrate:smart --force

# รัน migrations แบบปกติ
php artisan migrate

# Rollback batch ล่าสุด
php artisan migrate:rollback

# Rollback migration จำนวนที่กำหนด
php artisan migrate:rollback --step=2

# Rollback ทั้งหมดแล้ว migrate ใหม่
php artisan migrate:refresh

# Rollback ทั้งหมด
php artisan migrate:reset

# ดู SQL queries โดยไม่รัน migration
php artisan migrate --pretend
```

## Summary

- ✅ ใช้ `SafeMigration` trait กับ migration ทุกตัว
- ✅ timestamp NOT NULL ต้องมี `->useCurrent()`
- ✅ Foreign keys ต้องระบุ `->constrained('table_name')` ชัดเจน
- ✅ ใช้ `safeAddColumn()`, `safeDropColumn()` แทนการเรียกตรงๆ
- ✅ เช็คว่า column ที่อ้างอิงใน `after()` มีอยู่จริง
- ✅ ทดสอบ migrate และ rollback บน local ก่อน deploy
- ✅ เขียน `down()` method ที่ปลอดภัยด้วย

ด้วยวิธีนี้จะทำให้ migration ของคุณปลอดภัยและสามารถ deploy production ได้อย่างมั่นใจ! 🚀
