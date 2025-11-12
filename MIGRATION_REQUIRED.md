# ⚠️ ต้องรัน Migration ก่อน Seeder

## ปัญหา

```
SQLSTATE[42S02]: Base table or view not found: 1146
Table 'admin_mlmtestthai.line_signup_templates' doesn't exist
```

## สาเหตุ

Seeder ทำงานได้ แต่**ตาราง line_signup_templates ยังไม่ได้ถูกสร้าง** เพราะ **migration ยังไม่ได้รัน**

## วิธีแก้ไข (เรียงตามลำดับ)

### ขั้นตอนที่ 1: รัน Migration ทั้งหมด

```bash
php artisan migrate --force
```

Migration file ที่จะสร้างตารางสำหรับระบบ LINE Signup:
- `database/migrations/2025_11_12_000001_create_line_membership_signup_system.php`

ตารางที่จะถูกสร้าง (8 ตาราง):
1. ✅ `line_signup_sessions` - เก็บ session การสมัคร
2. ✅ `line_signup_step_logs` - log แต่ละขั้นตอน
3. ✅ `line_signup_conversations` - บันทึกการสนทนากับ AI
4. ✅ `line_signup_templates` - Flex Message templates
5. ✅ `line_signup_rewards` - รางวัลการสมัคร
6. ✅ `line_signup_invitations` - ลิงก์เชิญชวน
7. ✅ `line_signup_analytics` - ข้อมูลวิเคราะห์
8. ✅ `line_signup_webhook_logs` - log webhook จาก LINE

### ขั้นตอนที่ 2: ตรวจสอบว่า Migration สำเร็จ

```bash
php artisan migrate:status | grep line_signup
```

ต้องเห็น status "Ran" สำหรับ:
```
Ran | 2025_11_12_000001_create_line_membership_signup_system
```

### ขั้นตอนที่ 3: รัน Seeder

เมื่อ migration เสร็จแล้ว ถึงจะรัน seeder ได้:

```bash
php artisan db:seed --class=LineSignupTemplateSeeder --force
```

หรือรันทั้งหมดพร้อมกัน:

```bash
php artisan db:seed --force
```

### ขั้นตอนที่ 4: Setup Rich Menu

```bash
php artisan line:setup-signup-richmenu --set-default
```

## วิธีรันทั้งหมดในคำสั่งเดียว

```bash
# รัน migration + seed ทั้งหมด
php artisan migrate --seed --force
```

## ตรวจสอบว่าระบบพร้อมใช้งาน

```bash
# 1. เช็คว่าตารางถูกสร้างแล้ว
php artisan db:show

# 2. เช็คว่า templates ถูก seed แล้ว
php artisan tinker
>>> \App\Models\LineSignupTemplate::count()
# ควรได้ 5 templates

# 3. ทดสอบ Admin Dashboard
php artisan serve
# เข้า: http://localhost:8000/admin/line-membership-signup
```

## หมายเหตุสำคัญ

⚠️ **ลำดับการรันต้องถูกต้อง**:
1. ✅ Migration ก่อน (สร้างตาราง)
2. ✅ Seeder ทีหลัง (ใส่ข้อมูล)
3. ✅ Rich Menu Setup สุดท้าย (เชื่อมต่อ LINE)

❌ **ห้าม** รัน Seeder ก่อน Migration เพราะตารางยังไม่มี!

## ถ้ายังมีปัญหา

### ปัญหา: Connection Refused

```bash
# เช็คว่า MySQL ทำงานหรือไม่
sudo systemctl status mysql
# หรือ
sudo service mysql status

# Start MySQL
sudo systemctl start mysql
# หรือ
sudo service mysql start
```

### ปัญหา: Database Not Found

```bash
# สร้าง database
mysql -u root -p -e "CREATE DATABASE admin_mlmtestthai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### ปัญหา: Access Denied

```bash
# ตรวจสอบ username/password ใน .env
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

## Summary

```bash
# คำสั่งเดียวจบ (ถ้า MySQL พร้อมแล้ว)
php artisan migrate --seed --force && \
php artisan line:setup-signup-richmenu --set-default && \
echo "✅ ระบบ LINE Membership Signup พร้อมใช้งาน!"
```

---

**หลังจากรัน migration + seed เสร็จแล้ว** ระบบจะพร้อมใช้งาน 100% ทันที! 🚀
