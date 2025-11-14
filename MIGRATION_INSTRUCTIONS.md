# คำแนะนำการรัน Migration สำหรับแก้ไขปัญหา currency_id column

## ปัญหาที่พบ

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'currency_id' in 'WHERE'
```

ตาราง `crypto_wallets` ไม่มีคอลัมน์ `currency_id`, `balance`, และ `address` ที่ `TPIXWalletController` ต้องการใช้งาน

## การแก้ไขที่ทำ

### 1. สร้าง Migration File
- ไฟล์: `database/migrations/2025_11_14_000002_add_currency_id_balance_address_to_crypto_wallets_table.php`
- เพิ่มคอลัมน์:
  - `currency_id` - Foreign key เชื่อมกับตาราง `crypto_currencies`
  - `balance` - เก็บยอดคงเหลือ (decimal 36,18)
  - `address` - ที่อยู่ wallet สำหรับ custodial wallet
  - Index สำหรับเพิ่มประสิทธิภาพ query

### 2. อัปเดต Model
- ไฟล์: `app/Models/CryptoWallet.php`
- เพิ่ม `currency_id`, `balance`, `address` ใน `$fillable`
- เพิ่ม `balance` cast เป็น `decimal:18`
- เพิ่ม relationship `currency()` เชื่อมกับ `CryptoCurrency`

## วิธีการรัน Migration

### บนเซิร์ฟเวอร์ Production/Staging

```bash
# 1. Pull changes ล่าสุด
git pull origin claude/fix-currency-id-column-014PErJ1HKSjuqpeMy35Y5vK

# 2. รัน migration
php artisan migrate

# 3. ตรวจสอบผลลัพธ์
php artisan migrate:status
```

### บน Local Development

```bash
# 1. ติดตั้ง dependencies (ถ้ายังไม่ได้ติดตั้ง)
composer install

# 2. รัน migration
php artisan migrate

# 3. ตรวจสอบว่าคอลัมน์ถูกสร้างแล้ว
php artisan tinker
>>> Schema::hasColumn('crypto_wallets', 'currency_id')
>>> Schema::hasColumn('crypto_wallets', 'balance')
>>> Schema::hasColumn('crypto_wallets', 'address')
```

## การทดสอบหลังรัน Migration

### 1. ตรวจสอบโครงสร้างตาราง

```bash
mysql -u root -p
USE thaiprompt_affiliate;
DESCRIBE crypto_wallets;
```

คอลัมน์ที่ควรจะเห็น:
- `currency_id` - bigint unsigned, nullable
- `balance` - decimal(36,18), default 0
- `address` - varchar(255), nullable, unique

### 2. ทดสอบการทำงานของ TPIXWalletController

เข้าถึง URL: `/tpix/wallet` และตรวจสอบว่า:
- ✅ ไม่มี error "Column not found: currency_id"
- ✅ Wallet ถูกสร้างสำเร็จ
- ✅ Balance แสดงผลถูกต้อง
- ✅ Address ถูกสร้างและแสดงผล

## Rollback (ถ้าจำเป็น)

```bash
# Rollback migration ล่าสุด
php artisan migrate:rollback --step=1

# ตรวจสอบว่า rollback สำเร็จ
php artisan migrate:status
```

## หมายเหตุ

- Migration นี้ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือไม่ก่อนเพิ่ม (idempotent)
- สามารถรันซ้ำได้โดยไม่เกิดข้อผิดพลาด
- Foreign key constraint จะป้องกันการลบ currency ที่มี wallet อ้างอิง
- Index `user_currency_idx` จะช่วยเพิ่มประสิทธิภาพ query ที่ค้นหาตาม user_id และ currency_id

## ติดต่อ

หากพบปัญหาในการรัน migration กรุณาตรวจสอบ:
1. Database connection ใน `.env`
2. สิทธิ์การเข้าถึง database
3. Version ของ Laravel และ PHP
4. Log file ที่ `storage/logs/laravel.log`
