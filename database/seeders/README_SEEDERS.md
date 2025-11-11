# Seeder Best Practices

คู่มือการเขียน Seeder ที่ปลอดภัยและสามารถรันซ้ำได้ (Idempotent)

## 🚨 ปัญหาที่พบบ่อย

### 1. Duplicate Entry Error

เกิดเมื่อ seeder พยายาม insert ข้อมูลที่มีอยู่แล้ว

```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'X' for key 'table_column_unique'
```

**สาเหตุ:** ใช้ `Model::create()` ซึ่งจะ insert ข้อมูลใหม่ทุกครั้ง

### 2. Foreign Key Constraint Error

เกิดเมื่อ seeder insert ข้อมูลโดยไม่เช็คว่าตาราง parent มีข้อมูลหรือไม่

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails
```

**สาเหตุ:** ลำดับการ seed ไม่ถูกต้อง หรือข้อมูล parent ยังไม่ได้ seed

## ✅ กฎทองสำหรับการเขียน Seeder

### กฎที่ 1: ใช้ updateOrCreate() แทน create()

**ห้ามใช้:** ❌
```php
foreach ($data as $item) {
    Model::create($item);  // จะ error ถ้าข้อมูลซ้ำ
}
```

**ใช้แทน:** ✅
```php
foreach ($data as $item) {
    Model::updateOrCreate(
        ['unique_column' => $item['unique_column']],  // เงื่อนไขค้นหา
        $item  // ข้อมูลที่จะ update/create
    );
}
```

### กฎที่ 2: เลือก Unique Key ที่เหมาะสม

เลือก column(s) ที่ใช้เป็น unique identifier:

```php
// ตัวอย่างที่ดี
VideoLevel::updateOrCreate(
    ['level' => $data['level']],  // level เป็น unique
    $data
);

VideoChannel::updateOrCreate(
    ['name' => $data['name']],  // name เป็น unique
    $data
);

VideoContent::updateOrCreate(
    ['video_id' => $data['video_id']],  // video_id เป็น unique
    $data
);

VideoQuest::updateOrCreate(
    [
        'name' => $data['name'],
        'frequency' => $data['frequency']
    ],  // composite key
    $data
);
```

### กฎที่ 3: Seed ตามลำดับ Dependencies

Parent tables ต้อง seed ก่อน child tables:

```php
public function run(): void
{
    // 1. Parent tables (ไม่มี foreign keys)
    $this->seedVideoLevels();
    $this->seedVideoChannels();

    // 2. Child tables (มี foreign keys)
    $this->seedVideoContents();  // depends on channels
    $this->seedVideoQuests();    // depends on channels

    // 3. Relation tables (ถ้ามี)
    $this->seedUserVideoLevels(); // depends on users & levels
}
```

### กฎที่ 4: ใช้ firstOrCreate() สำหรับ Lookup Tables

สำหรับตารางที่เก็บค่าคงที่ (lookup/reference tables):

```php
$educationChannel = VideoChannel::firstOrCreate(
    ['name' => 'ช่องการศึกษา'],
    [
        'description' => 'คลิปความรู้และการเรียนรู้',
        'is_active' => true,
        'reward_enabled' => true,
    ]
);
```

### กฎที่ 5: ตรวจสอบข้อมูลก่อน Insert

สำหรับ foreign keys:

```php
// ตรวจสอบว่า channel มีอยู่จริง
if (VideoChannel::where('id', 1)->exists()) {
    VideoContent::updateOrCreate(
        ['video_id' => 'example1'],
        [
            'channel_id' => 1,
            'title' => 'วิดีโอตัวอย่าง',
            // ...
        ]
    );
}
```

## 📋 Template Seeder ที่ถูกต้อง

```php
<?php

namespace Database\Seeders;

use App\Models\Example;
use Illuminate\Database\Seeder;

class ExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedExamples();
        $this->command->info('✅ Examples seeded successfully!');
    }

    /**
     * Seed examples with idempotent approach
     */
    protected function seedExamples()
    {
        $examples = [
            [
                'code' => 'EX001',  // unique identifier
                'name' => 'Example 1',
                'description' => 'First example',
                'is_active' => true,
            ],
            [
                'code' => 'EX002',
                'name' => 'Example 2',
                'description' => 'Second example',
                'is_active' => true,
            ],
        ];

        foreach ($examples as $exampleData) {
            Example::updateOrCreate(
                ['code' => $exampleData['code']],  // unique key
                $exampleData  // all data
            );
        }

        $this->command->info('📦 Examples seeded');
    }
}
```

## 🔧 วิธีแก้ Seeder ที่มีอยู่แล้ว

### ขั้นตอนที่ 1: ระบุ Unique Key

ดูโครงสร้างตารางและหา unique columns:

```bash
php artisan db:show table_name
```

หรือดูใน migration file:

```php
$table->unique('column_name');
$table->unique(['col1', 'col2']);  // composite unique
```

### ขั้นตอนที่ 2: แก้ไข Seeder

เปลี่ยนจาก `create()` เป็น `updateOrCreate()`:

**Before:**
```php
VideoLevel::create([
    'level' => 1,
    'name' => 'มือใหม่',
    // ...
]);
```

**After:**
```php
VideoLevel::updateOrCreate(
    ['level' => 1],  // unique key
    [
        'level' => 1,
        'name' => 'มือใหม่',
        // ...
    ]
);
```

### ขั้นตอนที่ 3: ทดสอบ Seeder

```bash
# รัน seeder ครั้งแรก
php artisan db:seed --class=VideoRewardSystemSeeder

# รันอีกครั้ง (ต้องไม่ error)
php artisan db:seed --class=VideoRewardSystemSeeder

# ตรวจสอบข้อมูล
php artisan tinker
>>> App\Models\VideoLevel::count()
```

## 🎯 Checklist สำหรับ Seeder ที่ดี

- [ ] ใช้ `updateOrCreate()` หรือ `firstOrCreate()` แทน `create()`
- [ ] ระบุ unique key ที่ชัดเจน
- [ ] เรียง seeder ตามลำดับ dependencies
- [ ] ตรวจสอบ foreign keys ก่อน insert
- [ ] มี error handling ที่เหมาะสม
- [ ] แสดง progress/status messages
- [ ] ทดสอบรันซ้ำได้โดยไม่ error

## 📊 Comparison Table

| Method | Behavior | Use Case |
|--------|----------|----------|
| `create()` | ❌ Insert ใหม่ทุกครั้ง | **ไม่แนะนำ** - จะ duplicate |
| `updateOrCreate()` | ✅ Update ถ้ามี, Create ถ้าไม่มี | **แนะนำ** - Safe & Idempotent |
| `firstOrCreate()` | ✅ หาข้อมูล, Create ถ้าไม่มี | **แนะนำ** - สำหรับ lookup tables |
| `insert()` | ❌ Raw insert ไม่มี validation | **ไม่แนะนำ** - No protection |

## 🔍 ตัวอย่างการใช้ updateOrCreate() ในกรณีต่างๆ

### กรณีที่ 1: Single Unique Column

```php
User::updateOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]
);
```

### กรณีที่ 2: Composite Unique Key

```php
UserRole::updateOrCreate(
    [
        'user_id' => 1,
        'role_id' => 2
    ],
    [
        'user_id' => 1,
        'role_id' => 2,
        'assigned_at' => now(),
    ]
);
```

### กรณีที่ 3: With Timestamps

```php
Setting::updateOrCreate(
    ['key' => 'site_name'],
    [
        'key' => 'site_name',
        'value' => 'My Site',
        'updated_at' => now(),
    ]
);
```

### กรณีที่ 4: With Foreign Keys

```php
// ต้องแน่ใจว่า parent มีอยู่ก่อน
$channel = VideoChannel::where('name', 'ช่องการศึกษา')->first();

if ($channel) {
    VideoContent::updateOrCreate(
        ['video_id' => 'example1'],
        [
            'channel_id' => $channel->id,
            'title' => 'วิดีโอตัวอย่าง',
            'video_id' => 'example1',
        ]
    );
}
```

## 🚀 Advanced: Dynamic Seeding

สำหรับข้อมูลจำนวนมาก:

```php
protected function seedBulkData()
{
    $data = [
        // ... 100+ records
    ];

    // Use chunk to avoid memory issues
    collect($data)->chunk(100)->each(function ($chunk) {
        foreach ($chunk as $item) {
            Model::updateOrCreate(
                ['unique_key' => $item['unique_key']],
                $item
            );
        }
    });
}
```

## 📝 Summary

### ✅ DO:
- ใช้ `updateOrCreate()` เสมอ
- ระบุ unique key ชัดเจน
- Seed ตามลำดับ dependencies
- ทดสอบรันซ้ำได้
- แสดง status messages

### ❌ DON'T:
- ใช้ `create()` โดยตรง
- ข้าม foreign key validation
- Seed child ก่อน parent
- ลืมทดสอบ idempotency

ด้วยการทำตามกฎเหล่านี้ Seeder ของคุณจะ:
- ✅ ปลอดภัย (Safe)
- ✅ รันซ้ำได้ (Idempotent)
- ✅ ไม่ duplicate ข้อมูล
- ✅ พร้อม production

Happy Seeding! 🌱
