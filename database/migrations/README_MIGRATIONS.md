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

## Summary

- ✅ ใช้ `SafeMigration` trait กับ migration ทุกตัว
- ✅ ใช้ `safeAddColumn()`, `safeDropColumn()` แทนการเรียกตรงๆ
- ✅ เช็คว่า column ที่อ้างอิงใน `after()` มีอยู่จริง
- ✅ ทดสอบ migrate และ rollback บน local ก่อน deploy
- ✅ เขียน `down()` method ที่ปลอดภัยด้วย

ด้วยวิธีนี้จะทำให้ migration ของคุณปลอดภัยและสามารถ deploy production ได้อย่างมั่นใจ! 🚀
