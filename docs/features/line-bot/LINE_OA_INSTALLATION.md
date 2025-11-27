# LINE OA Installation Guide

คู่มือการติดตั้งระบบ LINE Official Account Integration

## ขั้นตอนการติดตั้ง

### 1. Pull Code Changes

```bash
git pull origin claude/add-line-oa-registration-011CUj8tXSgNMC7uuoojLvVP
```

### 2. Install Dependencies (ถ้ายังไม่ได้ทำ)

```bash
composer install
npm install
```

### 3. Run Migrations

```bash
php artisan migrate
```

Migrations ที่จะถูก run:
- `2025_11_02_000001_create_line_oa_settings_table` - Table สำหรับเก็บ LINE OA settings
- `2025_11_02_000002_add_line_fields_to_users_table` - เพิ่ม columns ใน users table
- `2025_11_02_000003_create_line_login_logs_table` - Table สำหรับเก็บ logs

### 4. Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. ตั้งค่า LINE OA ใน Admin Panel

1. เข้าสู่ระบบ Admin
2. ไปที่ `/admin/line-oa`
3. กรอกข้อมูล LINE Channel
4. บันทึกการตั้งค่า

## ไฟล์ที่เพิ่ม/แก้ไข

### Database Migrations
- `database/migrations/2025_11_02_000001_create_line_oa_settings_table.php`
- `database/migrations/2025_11_02_000002_add_line_fields_to_users_table.php`
- `database/migrations/2025_11_02_000003_create_line_login_logs_table.php`

### Models
- `app/Models/LineOaSetting.php`
- `app/Models/LineLoginLog.php`

### Services
- `app/Services/LineService.php`

### Controllers
- `app/Http/Controllers/Auth/LineLoginController.php` - LINE Login & OAuth
- `app/Http/Controllers/Admin/LineOaController.php` - Admin management
- `app/Http/Controllers/LineWebhookController.php` - LINE Webhook handler
- `app/Http/Controllers/Auth/RegisterController.php` - ✏️ Updated (LINE integration)

### Routes
- `routes/web.php` - ✏️ Updated (LINE routes)
- `routes/admin.php` - ✏️ Updated (admin routes)

### Views
- `resources/views/admin/line-oa/index.blade.php` - LINE OA settings page
- `resources/views/admin/line-oa/logs.blade.php` - LINE logs page

### Documentation
- `docs/LINE_OA_SETUP.md` - คู่มือการตั้งค่า LINE Developers
- `docs/LINE_OA_INSTALLATION.md` - คู่มือการติดตั้ง (ไฟล์นี้)

## Routes ที่เพิ่ม

### Public Routes
```
GET  /auth/line                    - Redirect to LINE Login
GET  /auth/line/callback           - LINE OAuth callback
POST /webhook/line                 - LINE Webhook endpoint
```

### Authenticated Routes
```
GET  /auth/line/link               - Link LINE account
GET  /auth/line/link/callback      - LINE linking callback
POST /auth/line/unlink             - Unlink LINE account
```

### Admin Routes
```
GET  /admin/line-oa                - LINE OA settings
PUT  /admin/line-oa/update         - Update settings
POST /admin/line-oa/test-message   - Test messaging
GET  /admin/line-oa/logs           - View logs
```

## Environment Variables (Optional)

แม้ว่าไม่จำเป็นต้องตั้งค่าใน .env (เพราะใช้ database) แต่สามารถเพิ่มได้เพื่อความสะดวก:

```env
LINE_CHANNEL_ID=
LINE_CHANNEL_SECRET=
LINE_CHANNEL_ACCESS_TOKEN=
LINE_LIFF_ID=
```

## ตรวจสอบการติดตั้ง

### 1. ตรวจสอบ Tables

```bash
php artisan tinker
```

```php
// Check tables exist
Schema::hasTable('line_oa_settings');  // should return true
Schema::hasTable('line_login_logs');   // should return true

// Check users table columns
Schema::hasColumn('users', 'line_user_id');  // should return true
```

### 2. ตรวจสอบ Routes

```bash
php artisan route:list | grep line
```

ควรเห็น routes ที่เกี่ยวข้องกับ LINE ทั้งหมด

### 3. ทดสอบ Admin Page

เข้าสู่ `/admin/line-oa` และตรวจสอบว่าหน้าแสดงผลถูกต้อง

## การ Rollback

ถ้าต้องการ rollback migrations:

```bash
php artisan migrate:rollback --step=3
```

จะ rollback migrations 3 ไฟล์ล่าสุด (LINE OA migrations)

## Next Steps

หลังจากติดตั้งเรียบร้อยแล้ว:

1. ✅ อ่านคู่มือ `docs/LINE_OA_SETUP.md`
2. ✅ ตั้งค่า LINE Developers Console
3. ✅ ตั้งค่าใน Admin Panel
4. ✅ ทดสอบระบบ

## การอัพเดทในอนาคต

ถ้ามีการอัพเดท LINE OA features:

```bash
git pull origin main
php artisan migrate
php artisan cache:clear
```

## ปัญหาที่อาจพบ

### Migration ล้มเหลว

**Error**: Column already exists
**แก้ไข**: ตรวจสอบว่าไม่มี columns ซ้ำใน users table

```sql
-- Check existing columns
DESCRIBE users;
```

### Routes ไม่ทำงาน

**Error**: Route not found
**แก้ไข**: Clear route cache

```bash
php artisan route:clear
php artisan route:cache
```

### Views ไม่แสดงผล

**Error**: View not found
**แก้ไข**: Clear view cache

```bash
php artisan view:clear
```

## Support

หากพบปัญหาหรือมีคำถาม:
1. ตรวจสอบ logs: `storage/logs/laravel.log`
2. ตรวจสอบ LINE Developers Console
3. อ่านคู่มือ `docs/LINE_OA_SETUP.md`
