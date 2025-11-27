# แก้ไขปัญหา: Table 'cookie_settings' doesn't exist

## 🔍 ปัญหา

เมื่อรัน `php artisan db:seed` พบข้อผิดพลาด:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'admin_thaiprompt888.cookie_settings' doesn't exist
```

## ✅ สาเหตุ

Migration สำหรับสร้างตาราง `cookie_settings` มีอยู่แล้วในระบบ แต่ยังไม่ได้ถูกรันบน production database

**Migration ที่เกี่ยวข้อง:**
- `database/migrations/2025_11_08_000001_create_cookie_consents_table.php`

Migration นี้สร้างตารางทั้งหมด:
1. ✅ `cookie_consents` - เก็บความยินยอมของผู้ใช้
2. ✅ `cookie_tracking` - ติดตามพฤติกรรมผู้ใช้
3. ✅ `cookie_analytics_keywords` - วิเคราะห์คำค้นหา
4. ❌ `cookie_settings` - **ตารางนี้หายไป!**

## 🛠️ วิธีแก้ไข

### วิธีที่ 1: รัน Migration ทั้งหมด (แนะนำ)

รันคำสั่งนี้บน **production server** ที่เชื่อมต่อกับ database `admin_thaiprompt888`:

```bash
php artisan migrate --force
```

หรือรันเฉพาะ migration ที่เกี่ยวข้อง:

```bash
php artisan migrate --path=database/migrations/2025_11_08_000001_create_cookie_consents_table.php --force
```

### วิธีที่ 2: สร้างตารางด้วย SQL โดยตรง

เข้าสู่ MySQL และรันคำสั่ง SQL นี้:

```sql
USE admin_thaiprompt888;

CREATE TABLE IF NOT EXISTS `cookie_settings` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` text,
    `type` varchar(255) NOT NULL DEFAULT 'text',
    `description` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cookie_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### วิธีที่ 3: ใช้ Deployment Script

ถ้ามี deployment script ให้รัน:

```bash
./deploy.sh
```

Script จะรัน migration อัตโนมัติ

## 📝 หลังจากรัน Migration สำเร็จ

รัน seeder อีกครั้ง:

```bash
php artisan db:seed --class=CookieSettingsSeeder
```

หรือรัน seeder ทั้งหมด:

```bash
php artisan db:seed --force
```

## ✨ ตรวจสอบว่าตารางสร้างสำเร็จ

```bash
php artisan tinker
```

จากนั้นรันคำสั่ง:

```php
\Illuminate\Support\Facades\Schema::hasTable('cookie_settings')
// ควรได้ผลลัพธ์: true
```

หรือตรวจสอบใน MySQL:

```sql
SHOW TABLES LIKE 'cookie_settings';
```

## 🎯 ข้อมูล Default ที่ Seeder จะสร้าง

เมื่อรัน `CookieSettingsSeeder` จะสร้างข้อมูลเริ่มต้น:

| Key | Value | Type | Description |
|-----|-------|------|-------------|
| `cookie_banner_enabled` | `1` | boolean | เปิดใช้งานแบนเนอร์คุกกี้ |
| `cookie_banner_title` | เราใช้คุกกี้... | text | หัวข้อแบนเนอร์ |
| `cookie_banner_description` | คำอธิบายแบนเนอร์ | text | คำอธิบาย PDPA |
| `cookie_policy_url` | `/cookie-policy` | text | URL นโยบายคุกกี้ |
| `auto_block_without_consent` | `1` | boolean | บล็อกการติดตาม |
| `cookie_expiry_days` | `365` | integer | จำนวนวันหมดอายุ |

## 🔗 ไฟล์ที่เกี่ยวข้อง

- Migration: `database/migrations/2025_11_08_000001_create_cookie_consents_table.php`
- Seeder: `database/seeders/CookieSettingsSeeder.php`
- Model: `app/Models/CookieSetting.php` (ถ้ามี)

## ⚠️ หมายเหตุสำคัญ

1. **ห้ามรัน `php artisan migrate:fresh`** บน production - จะลบข้อมูลทั้งหมด!
2. ใช้ `--force` flag เมื่อรันบน production environment
3. Backup database ก่อนรัน migration เสมอ

---

**วันที่สร้างเอกสาร:** 2025-11-20
**สถานะ:** ✅ พร้อมใช้งาน
