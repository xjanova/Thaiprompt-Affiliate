# แก้ไข: Table 'membership_retention_settings' doesn't exist

## ปัญหา

เกิด error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'admin_mlmtestthai.membership_retention_settings' doesn't exist
```

## สาเหตุ

Migration สำหรับระบบรักษายอด (Membership Retention System) ยังไม่ได้รันบน production server แม้ว่าโค้ดได้ถูก deploy ไปแล้ว

## วิธีแก้ไข

### วิธีที่ 1: ใช้ Migration Helper Script (แนะนำ)

```bash
./run-migrations.sh
```

Script นี้จะ:
- ตรวจสอบการเชื่อมต่อฐานข้อมูล
- แสดง migration ที่รอการรัน
- สร้าง backup ฐานข้อมูลก่อนรัน migration
- รัน migration
- แสดงสถานะหลังรัน

### วิธีที่ 2: รัน Migration แบบ Manual

```bash
# 1. ตรวจสอบ migration ที่รอการรัน
php artisan migrate:status

# 2. รัน migration
php artisan migrate --force

# 3. ตรวจสอบอีกครั้ง
php artisan migrate:status
```

### วิธีที่ 3: ใช้ Deploy Script

```bash
./deploy.sh
```

Deploy script จะรัน migration อัตโนมัติในขั้นตอนที่ 10

## ตาราง Migration ที่จะถูกสร้าง

Migration `2025_11_01_000001_create_membership_retention_system.php` จะสร้างตารางดังนี้:

1. **membership_retention_status** - สถานะการรักษายอดของสมาชิก
2. **membership_retention_history** - ประวัติการรักษายอดแต่ละเดือน
3. **membership_retention_transactions** - รายการซื้อขายที่นับเข้าระบบรักษายอด
4. **membership_retention_repairs** - การซื้อซ่อมสิทธิ์
5. **membership_retention_advance_renewals** - การเติมวันล่วงหน้า
6. **membership_retention_settings** - การตั้งค่าระบบ ⭐ (ตารางที่หาย)

พร้อมข้อมูลเริ่มต้น (default settings) ในตาราง `membership_retention_settings`

## การยืนยันว่าแก้ไขสำเร็จ

ตรวจสอบว่าตารางถูกสร้างแล้ว:

```bash
php artisan tinker --execute="echo DB::table('membership_retention_settings')->count();"
```

ควรได้ผลลัพธ์ `7` (มี 7 settings เริ่มต้น)

## Rollback (หากมีปัญหา)

หากเกิดปัญหาหลังรัน migration:

```bash
# Rollback migration ล่าสุด
php artisan migrate:rollback

# หรือ restore จาก backup
mysql -u DB_USERNAME -p DB_DATABASE < backups/pre_migration_TIMESTAMP.sql
```

## สำหรับ Production Server

หากรัน migration บน production ควร:
1. แจ้งเตือนผู้ใช้ก่อน
2. เปิด maintenance mode: `php artisan down`
3. รัน migration: `php artisan migrate --force`
4. ปิด maintenance mode: `php artisan up`

หรือใช้ `./deploy.sh` ซึ่งจะจัดการทุกอย่างอัตโนมัติ
