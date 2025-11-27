# 🔍 Schema Verification System Guide

## ภาพรวม

Schema Verification System เป็นระบบตรวจสอบความถูกต้องของโครงสร้าง Database อัตโนมัติ ป้องกันปัญหา **Schema Drift** ที่เกิดจาก:

- การ import SQL file จากแหล่งอื่น
- การแก้ไข database manual ผ่าน phpMyAdmin
- Migration ที่รันไม่ครบ หรือรันบางส่วน
- Database ถูก restore จาก backup เก่า

---

## 🎯 ปัญหาที่ Schema Verification แก้ไข

### ปัญหาที่พบบ่อย:

**1. Table มีอยู่แต่ Laravel ไม่รู้**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'line_avatars' already exists
```
**สาเหตุ:** Import SQL แล้ว แต่ตาราง `migrations` ไม่ได้ถูก update

**2. Column หายหรือไม่ตรงกับ Code**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'field list'
```
**สาเหตุ:** Database ถูกแก้ไข manual หรือใช้ backup เก่า

**3. Migration ไม่รู้ว่าต้องทำอะไร**
- Laravel เช็คแค่ตาราง `migrations` ไม่ได้เช็ค schema จริง
- ถึงแม้ schema จะผิด แต่ migration status จะบอกว่า "Ran" ✅

---

## 🚀 วิธีใช้งาน

### วิธีที่ 1: ใช้ Helper Script (แนะนำสำหรับผู้เริ่มต้น)

```bash
./verify-schema.sh
```

เมนูที่มี:
1. 🔍 Verify Schema (ตรวจสอบปัญหา)
2. 🔧 Verify & Generate Fix (แสดง SQL แก้ไข)
3. 📸 Create Schema Snapshot (สร้าง snapshot)
4. 📋 Check Specific Table (เช็คตารางเฉพาะ)
5. ℹ️ Show Help (ดูคำอธิบาย)

### วิธีที่ 2: ใช้ Artisan Command โดยตรง

#### ตรวจสอบ Schema พื้นฐาน
```bash
php artisan schema:verify
```

**Output ตัวอย่าง (ถ้าถูกต้อง):**
```
🔍 Schema Verification System

→ Checking database schema integrity...

Checking table: line_bot_ai_settings
  ✓ Schema is correct

Checking table: line_bot_knowledge_bases
  ✓ Schema is correct

...

═══════════════════════════════════════
  ✅ All Schema Checks Passed!
═══════════════════════════════════════
```

**Output ตัวอย่าง (ถ้ามีปัญหา):**
```
🔍 Schema Verification System

→ Checking database schema integrity...

Checking table: line_bot_ai_settings
  ⚠ Missing column: enable_conversation_history (boolean)
  ⚠ Missing column: conversation_memory_limit (integer)

...

═══════════════════════════════════════
  ⚠️  Schema Issues Detected!
═══════════════════════════════════════

💡 Recommendations:
  1. Review the issues above
  2. Run: php artisan schema:verify --fix
  3. Or manually fix the schema
  4. Then run: php artisan migrate --force
```

#### ตรวจสอบและสร้าง SQL Fix Statements
```bash
php artisan schema:verify --fix
```

**Output:**
```
🔧 Generated SQL Fix Statements:

ALTER TABLE `line_bot_ai_settings` ADD COLUMN `enable_conversation_history` TINYINT(1);
ALTER TABLE `line_bot_ai_settings` ADD COLUMN `conversation_memory_limit` INT;
```

คัดลอก SQL statements เหล่านี้ไปรันใน phpMyAdmin หรือ MySQL client

#### ⚡ Auto-Fix: ซ่อมแซม Schema อัตโนมัติ (ใหม่!)
```bash
php artisan schema:verify --auto-fix
```

**Output:**
```
🔧 Auto-Fix Mode: Repairing Database Schema
═══════════════════════════════════════

Found 2 fixable issue(s):

  • line_bot_ai_settings.enable_conversation_history (boolean)
  • line_bot_ai_settings.conversation_memory_limit (integer)

📋 SQL Statements to execute:

  ALTER TABLE `line_bot_ai_settings` ADD COLUMN `enable_conversation_history` TINYINT(1);
  ALTER TABLE `line_bot_ai_settings` ADD COLUMN `conversation_memory_limit` INT;

⚠️  Do you want to execute these ALTER TABLE statements? (yes/no):
> yes

⚡ Executing auto-fix...

  [1/2] Executing...
  ✓ Success: ALTER TABLE `line_bot_ai_settings` ADD COLUMN `enable_conversation_history` TINYINT(1)

  [2/2] Executing...
  ✓ Success: ALTER TABLE `line_bot_ai_settings` ADD COLUMN `conversation_memory_limit` INT

═══════════════════════════════════════
  ✅ Auto-Fix Completed Successfully!
     Fixed 2 issue(s)
═══════════════════════════════════════

🔍 Verifying schema after auto-fix...

✅ All schema issues have been resolved!
```

**สำหรับ Automation (ไม่ต้องถาม):**
```bash
php artisan schema:verify --auto-fix --force
```

**ฟีเจอร์ Auto-Fix:**
- ✅ ตรวจจับ missing columns อัตโนมัติ
- ✅ สร้างและรัน ALTER TABLE statements
- ✅ ถามยืนยันก่อนทำการเปลี่ยนแปลง (ยกเว้น --force)
- ✅ ตรวจสอบผลลัพธ์หลังซ่อมแซม
- ✅ ปลอดภัย: เพิ่ม columns เท่านั้น ไม่ลบ
- ✅ แสดงสถิติการซ่อมแซม (success/fail)

**⚠️ Safety Notes:**
- Auto-fix จะทำการ **เพิ่ม columns เท่านั้น** (ไม่ลบหรือแก้ไข)
- ควร backup database ก่อนใช้ auto-fix
- ระบบ deploy.sh จะ backup อัตโนมัติก่อนรัน auto-fix

#### ตรวจสอบตารางเฉพาะ
```bash
php artisan schema:verify --table=line_bot_ai_settings
```

#### สร้าง Schema Snapshot
```bash
php artisan schema:verify --snapshot
```

จะสร้างไฟล์: `database/schema_snapshot.json` เก็บ schema ปัจจุบัน

---

## 🔧 การทำงานของระบบ

### 1. Schema Definition
ระบบมี "Expected Schema" ที่กำหนดไว้ใน `SchemaVerifyCommand.php`:

```php
protected function getExpectedSchema(string $table): array
{
    $schemas = [
        'line_bot_ai_settings' => [
            'id' => 'bigint',
            'name' => 'string',
            'provider' => 'string',
            // ... columns อื่นๆ
        ],
        // ... ตารางอื่นๆ
    ];
}
```

### 2. Schema Inspection
ใช้ Laravel Schema Builder ดึงข้อมูลจาก database จริง:

```php
$actualColumns = Schema::getColumnListing($table);
$columnType = Schema::getColumnType($table, $columnName);
```

### 3. Comparison & Diff
เปรียบเทียบ Expected vs Actual:

```php
// Check missing columns
foreach ($expectedSchema as $columnName => $expectedType) {
    if (!isset($actualColumns[$columnName])) {
        $issues[] = "Missing column: {$columnName}";
    }
}

// Check extra columns
$extraColumns = array_diff($actualKeys, $expectedKeys);
```

### 4. Fix Generation
สร้าง ALTER TABLE statements:

```php
ALTER TABLE `table_name` ADD COLUMN `column_name` VARCHAR(255);
```

---

## 📋 Integration กับ Deploy Script

ระบบ `deploy.sh` จะรัน **Schema Verification & Auto-Repair** อัตโนมัติก่อน Migration:

```bash
# Step 10.2: Schema Verification & Auto-Repair System
print_info "→ Verifying database schema integrity..."
if php artisan schema:verify >/dev/null 2>&1; then
    print_success "✓ Database schema is correct"
else
    print_warning "⚠ Schema issues detected"

    # Backup database ก่อน auto-repair
    print_info "→ Creating safety backup before auto-repair..."
    mysqldump ... > pre_autofix_backup.sql

    # Attempt auto-repair
    print_info "🔧 Attempting automatic schema repair..."
    if php artisan schema:verify --auto-fix --force; then
        print_success "✓ Schema auto-repair completed successfully!"
    else
        print_error "✗ Auto-repair failed"
        # ถามว่าจะ continue หรือไม่
    fi
fi
```

**ผลลัพธ์:**
- ✅ ถ้า schema ถูกต้อง → ดำเนินการต่อเลย
- ⚠️ ถ้ามีปัญหา → **ซ่อมแซมอัตโนมัติ!** (พร้อม backup)
- ✅ ถ้าซ่อมสำเร็จ → ดำเนินการต่อ
- ❌ ถ้าซ่อมไม่สำเร็จ → ถามก่อนดำเนินการต่อ

**Safety Features:**
- ✅ สร้าง database backup ก่อน auto-repair ทุกครั้ง
- ✅ ใช้ --force เพื่อไม่ต้องถามระหว่าง deployment
- ✅ แสดง rollback information ถ้าเกิดปัญหา
- ✅ Log ทุกการเปลี่ยนแปลงใน deployment.log

---

## 🎓 Use Cases & Examples

### Case 1: หลังจาก Import SQL File

**สถานการณ์:**
คุณ import `line_bot_schema.sql` เข้า database ผ่าน phpMyAdmin

**ปัญหา:**
- ตารางถูกสร้างแล้ว
- แต่ Laravel ไม่รู้ (ตาราง `migrations` ว่างเปล่า)
- รัน `php artisan migrate` → Error: Table already exists

**วิธีแก้:**
```bash
# 1. ตรวจสอบ schema
php artisan schema:verify

# 2. ถ้า schema ถูกต้อง แสดงว่า import สำเร็จ
#    → ไม่ต้องรัน migrate เพิ่ม

# 3. ถ้า schema ไม่ครบ
php artisan schema:verify --fix
# → คัดลอก SQL ไปรันใน phpMyAdmin
```

### Case 2: Database ถูกแก้ไข Manual

**สถานการณ์:**
มีคนเข้า phpMyAdmin แก้ไขตาราง `line_bot_ai_settings`:
- เพิ่ม column `test_column`
- ลบ column `enable_conversation_history`

**ปัญหา:**
- Code ยังคาดหวังว่ามี `enable_conversation_history`
- Laravel ไม่รู้ว่า schema เปลี่ยน
- Migration status ยังบอกว่า "Ran" ✅

**วิธีแก้:**
```bash
# 1. ตรวจสอบ
php artisan schema:verify

# Output:
# ⚠ Missing column: enable_conversation_history (boolean)
# (Extra column 'test_column' found - informational)

# 2. แก้ไข
php artisan schema:verify --fix

# 3. รัน SQL ที่ได้
ALTER TABLE `line_bot_ai_settings`
ADD COLUMN `enable_conversation_history` TINYINT(1);

# 4. ลบ column ที่ไม่ต้องการ manual
ALTER TABLE `line_bot_ai_settings` DROP COLUMN `test_column`;
```

### Case 3: ก่อน Deploy Production

**สถานการณ์:**
ต้องการ deploy code ใหม่ที่มี migration เพิ่มเติม

**Best Practice:**
```bash
# ก่อน deploy ให้ตรวจสอบ schema ก่อน
./verify-schema.sh

# ถ้ามีปัญหาให้แก้ไขก่อน
# จากนั้นค่อย deploy
./deploy.sh
```

ระบบ deploy.sh จะตรวจสอบอัตโนมัติ และแจ้งเตือนถ้ามีปัญหา

---

## ⚙️ Configuration

### เพิ่มตารางใหม่เข้าระบบ Verification

แก้ไขไฟล์: `app/Console/Commands/SchemaVerifyCommand.php`

```php
protected $tablesToVerify = [
    'line_bot_ai_settings',
    'line_bot_knowledge_bases',
    // ... existing tables

    'your_new_table',  // เพิ่มตารางใหม่
];

protected function getExpectedSchema(string $table): array
{
    $schemas = [
        // ... existing schemas

        'your_new_table' => [
            'id' => 'bigint',
            'name' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ],
    ];
}
```

---

## 🐛 Troubleshooting

### ปัญหา: Command ไม่ทำงาน
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# ลองอีกครั้ง
php artisan schema:verify
```

### ปัญหา: Database Connection Failed
```bash
# ตรวจสอบ .env
cat .env | grep DB_

# ทดสอบการเชื่อมต่อ
php artisan db:show
```

### ปัญหา: Permission Denied (verify-schema.sh)
```bash
# ให้สิทธิ์ execute
chmod +x verify-schema.sh

# ลองอีกครั้ง
./verify-schema.sh
```

---

## 📊 Exit Codes

- `0` (SUCCESS) - Schema ถูกต้องทั้งหมด
- `1` (FAILURE) - พบปัญหา schema

ใช้ใน CI/CD:
```bash
if php artisan schema:verify; then
    echo "Schema OK, proceeding with deployment"
else
    echo "Schema issues detected, aborting"
    exit 1
fi
```

---

## 🔒 Security Notes

- Schema snapshot files **ไม่มี sensitive data** (แค่ structure ไม่มีข้อมูลจริง)
- ไฟล์ `schema_snapshot.json` สามารถ commit เข้า git ได้ปลอดภัย
- Command นี้ **read-only** ไม่แก้ไข database

---

## 📚 Related Documentation

- [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) - คู่มือติดตั้งระบบ
- [LINE_BOT_AI_IMPLEMENTATION.md](LINE_BOT_AI_IMPLEMENTATION.md) - เอกสารทางเทคนิค
- [deploy.sh](deploy.sh) - Deploy script หลัก

---

## 💡 Tips & Best Practices

1. **รัน verification ก่อน deploy เสมอ**
   ```bash
   ./verify-schema.sh
   # ตรวจสอบผลก่อน deploy
   ./deploy.sh
   ```

2. **สร้าง snapshot หลังจาก deploy สำเร็จ**
   ```bash
   php artisan schema:verify --snapshot
   git add database/schema_snapshot.json
   git commit -m "chore: Update schema snapshot"
   ```

3. **ใช้ --fix เพื่อ generate SQL แทนเขียนเอง**
   - ลด human error
   - รวดเร็วกว่า
   - ถูกต้องแน่นอน

4. **เช็คตารางเฉพาะเมื่อ debug**
   ```bash
   php artisan schema:verify --table=line_bot_ai_settings
   ```

---

## 🎯 Summary

Schema Verification System ช่วย:
- ✅ ป้องกัน Schema Drift
- ✅ ตรวจจับปัญหาก่อน deploy
- ✅ สร้าง fix statements อัตโนมัติ
- ✅ บูรณาการกับ deploy.sh
- ✅ ใช้งานง่าย มี helper script

**คำแนะนำสุดท้าย:** รัน `./verify-schema.sh` ทุกครั้งก่อน deploy หรือเมื่อสงสัยว่า database schema อาจไม่ตรง!
