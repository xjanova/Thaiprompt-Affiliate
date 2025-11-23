# แก้ไขปัญหาตาราง line_signup_rewards

## ปัญหา

```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'session_id' cannot be null
```

**สาเหตุ:**
- ตาราง `line_signup_rewards` มี columns พิเศษที่ไม่ควรมี (user_id, session_id, reward_name, etc.)
- Columns เหล่านี้เป็น transaction data ควรอยู่ในตาราง `line_signup_reward_claims` แทน
- Seeder พยายาม seed ข้อมูล template แต่ตารางยังมี columns ที่เป็น NOT NULL

---

## วิธีแก้ (เลือก 1 วิธี)

### วิธีที่ 1: ให้ Migration ลบ Columns พิเศษอัตโนมัติ (แนะนำ)

```bash
# 1. Run migration cleanup
php artisan migrate --force

# 2. ตรวจสอบว่า columns พิเศษถูกลบแล้ว
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
\$cols = Schema::getColumnListing('line_signup_rewards');
echo 'Columns: ' . implode(', ', \$cols);
"

# 3. Run seeder
php artisan db:seed --force
```

**Migration ที่จะทำงาน:**
- `2025_11_23_173000_cleanup_line_signup_rewards_table.php`
- จะลบ columns: user_id, session_id, reward_name, reward_amount, reward_description, status, granted_at, claimed_at, expires_at, claim_id

---

### วิธีที่ 2: ลบ Columns ด้วย SQL โดยตรง

**⚠️ แนะนำ: Backup database ก่อน!**

```bash
# Backup database
mysqldump -u root -p thaiprompt_affiliate > backup_before_cleanup.sql
```

**SQL Commands:**

```sql
-- เชื่อมต่อ MySQL
mysql -u root -p thaiprompt_affiliate

-- ลบ columns พิเศษ
ALTER TABLE line_signup_rewards
  DROP COLUMN IF EXISTS user_id,
  DROP COLUMN IF EXISTS session_id,
  DROP COLUMN IF EXISTS reward_name,
  DROP COLUMN IF EXISTS reward_amount,
  DROP COLUMN IF EXISTS reward_description,
  DROP COLUMN IF EXISTS status,
  DROP COLUMN IF EXISTS granted_at,
  DROP COLUMN IF EXISTS claimed_at,
  DROP COLUMN IF EXISTS expires_at,
  DROP COLUMN IF EXISTS claim_id;

-- ตรวจสอบ columns ที่เหลือ
SHOW COLUMNS FROM line_signup_rewards;
```

**Columns ที่ควรเหลืออยู่:**
```
id, name, description, signup_type, package_ids, reward_type,
amount, coupon_code, coupon_template_id, benefit_data, product_id,
icon, badge_color, display_order, is_time_limited, start_date, end_date,
is_active, is_stackable, notify_user, notification_message,
total_claimed, max_claims, created_at, updated_at, deleted_at
```

---

### วิธีที่ 3: Drop และ Recreate ตาราง (Fresh Start)

**⚠️ WARNING: จะลบข้อมูลทั้งหมดในตาราง!**

```sql
-- Backup ก่อน!
mysqldump -u root -p thaiprompt_affiliate line_signup_rewards > backup_line_signup_rewards.sql

-- Drop table
DROP TABLE IF EXISTS line_signup_rewards;

-- Recreate table ด้วย migration
php artisan migrate --path=database/migrations/2025_11_23_120002_create_line_signup_rewards_table.php --force

-- Run seeder
php artisan db:seed --class=LineSignupRewardSeeder --force
```

---

## การตรวจสอบหลัง Fix

```bash
# 1. ตรวจสอบ columns
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
\$cols = Schema::getColumnListing('line_signup_rewards');
echo 'Total columns: ' . count(\$cols) . PHP_EOL;
echo 'Columns: ' . PHP_EOL;
foreach (\$cols as \$col) {
    echo '  - ' . \$col . PHP_EOL;
}
"

# 2. นับจำนวน columns
# ควรได้ 26 columns (ไม่รวม columns พิเศษ)

# 3. Run seeder test
php artisan db:seed --class=LineSignupRewardSeeder --force

# 4. ตรวจสอบข้อมูล
php artisan tinker --execute="
echo 'Line Signup Rewards count: ' . \App\Models\LineSignupReward::count();
"
```

---

## การป้องกันปัญหาในอนาคต

1. **อย่าสร้าง transaction columns ในตาราง template**
   - ตาราง `line_signup_rewards` = Master/Template data
   - ตาราง `line_signup_reward_claims` = Transaction data

2. **ใช้ Migration Cleanup Pattern**
   - สร้าง migration cleanup ทุกครั้งที่แก้ schema ผิด
   - ใช้ `Schema::hasColumn()` check ก่อนลบ

3. **ใช้ Seeder Safety Check**
   - Seeder ควรตรวจสอบ schema ก่อน seed
   - ใช้ `detectExtraColumns()` เพื่อป้องกันปัญหา

4. **Deploy Process**
   - Migration ต้อง run ก่อน Seeder เสมอ
   - Deploy script ของเราทำถูกต้องแล้ว (มี Step 10 → Step 11)

---

## ติดต่อทีมพัฒนา

ถ้ายังแก้ไม่ได้ กรุณาติดต่อทีมพัฒนาพร้อมข้อมูลนี้:

```bash
# 1. Database schema
php artisan schema:dump --table=line_signup_rewards > line_signup_rewards_schema.txt

# 2. Migration status
php artisan migrate:status > migration_status.txt

# 3. Error log
tail -n 100 storage/logs/laravel.log > error_log.txt
```

---

**อัพเดท:** 2025-11-23
**ผู้แก้ไข:** Claude AI Assistant
