# Theme Preference Testing Guide

## คำสั่งทดสอบระบบธีมต่อ User

### 1. ทดสอบระบบทั้งหมด
```bash
php artisan theme:test
# หรือทดสอบกับ user เฉพาะ
php artisan theme:test 1
```

**ผลลัพธ์ที่ควรเห็น:**
```
=== Theme Preference Per-User Test ===

1. Checking database column...
✅ Column "menu_theme_preference" exists

2. Checking User model fillable...
✅ "menu_theme_preference" is in User fillable array

3. Testing with User ID: 1 (Admin)
   Current theme: millennium

4. Testing save to "classic_x"...
✅ Successfully saved "classic_x"
   Verified value: classic_x

5. Testing save to "millennium"...
✅ Successfully saved "millennium"
   Verified value: millennium

6. Testing multiple users (per-user isolation)...
+----+-------+---------------------------+
| ID | Name  | Current Theme             |
+----+-------+---------------------------+
| 1  | Admin | millennium                |
| 2  | User1 | classic_x                 |
| 3  | User2 | millennium (default)      |
+----+-------+---------------------------+

=== All Tests Passed! ✅ ===
```

### 2. ตั้งธีมให้ User เฉพาะ
```bash
# ตั้งให้ user ID 1 ใช้ Classic X
php artisan theme:set 1 classic_x

# ตั้งให้ user ID 2 ใช้ Millennium
php artisan theme:set 2 millennium
```

### 3. ดูธีมของ Users ทั้งหมด
```bash
# ดู 10 users แรก
php artisan theme:show

# ดู users ทั้งหมด
php artisan theme:show --all
```

**ตัวอย่างผลลัพธ์:**
```
=== User Theme Preferences ===

+----+-------+-------------------+-----------+------+
| ID | Name  | Email             | Theme     | Icon |
+----+-------+-------------------+-----------+------+
| 1  | Admin | admin@example.com | classic_x | 📐   |
| 2  | John  | john@example.com  | millennium| ⚡   |
| 3  | Jane  | jane@example.com  | classic_x | 📐   |
+----+-------+-------------------+-----------+------+

Statistics:
  ⚡ Millennium: 1 users
  📐 Classic X: 2 users
  ❓ NULL/Default: 0 users
```

## การทดสอบผ่าน Tinker

```bash
php artisan tinker
```

```php
// ตรวจสอบ column
Schema::hasColumn('users', 'menu_theme_preference')

// หา user
$user1 = User::find(1);
$user2 = User::find(2);

// ตรวจสอบธีมปัจจุบัน
$user1->menu_theme_preference  // millennium or classic_x
$user2->menu_theme_preference

// เปลี่ยนธีมของ user 1
$user1->menu_theme_preference = 'classic_x';
$user1->save();

// เปลี่ยนธีมของ user 2
$user2->menu_theme_preference = 'millennium';
$user2->save();

// ตรวจสอบอีกครั้ง
$user1->refresh();
$user2->refresh();

echo "User 1: " . $user1->menu_theme_preference . "\n";
echo "User 2: " . $user2->menu_theme_preference . "\n";

// ควรได้ผลลัพธ์แยกกัน:
// User 1: classic_x
// User 2: millennium
```

## การทดสอบผ่าน Web UI

1. **Login เป็น User 1**
   - คลิกปุ่ม "ธีมเมนู" 🎨
   - เลือก "Classic X Theme"
   - คลิก "ใช้ธีมนี้"
   - หน้า reload → เห็น Classic X sidebar

2. **Logout และ Login เป็น User 2**
   - คลิกปุ่ม "ธีมเมนู" 🎨
   - เลือก "Millennium Theme"
   - คลิก "ใช้ธีมนี้"
   - หน้า reload → เห็น Millennium taskbar

3. **Login กลับเป็น User 1**
   - ควรเห็น Classic X sidebar (ไม่ใช่ Millennium!)
   - นี่แสดงว่าแต่ละคนมีธีมของตัวเอง ✅

## การตรวจสอบใน Database

```bash
# SQLite
sqlite3 database/database.sqlite "SELECT id, name, menu_theme_preference FROM users LIMIT 10;"

# MySQL
mysql -u root -p database_name -e "SELECT id, name, menu_theme_preference FROM users LIMIT 10;"
```

**ควรเห็นผลลัพธ์แบบนี้:**
```
id|name|menu_theme_preference
1|Admin|classic_x
2|User1|millennium
3|User2|classic_x
4|User3|NULL
```

## Troubleshooting

### ถ้าทุกคนใช้ธีมเดียวกัน:
```bash
# 1. ตรวจสอบว่า column มีจริง
php artisan theme:test

# 2. ตรวจสอบว่า save ได้จริง
php artisan tinker
>>> $user = User::find(1);
>>> $user->menu_theme_preference = 'classic_x';
>>> $user->save(); // ต้องได้ true
>>> $user->refresh();
>>> $user->menu_theme_preference; // ต้องได้ 'classic_x'

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 4. ดู logs
tail -f storage/logs/laravel.log
```

### ถ้าธีมไม่บันทึก:
1. ตรวจสอบ permissions: `chmod -R 775 storage`
2. ตรวจสอบ database writable
3. ดู Laravel logs หา error
4. เปิด browser console หา JavaScript errors

## ยืนยันว่าทำงานถูกต้อง

✅ **ระบบทำงานถูกต้องเมื่อ:**
- User A เลือก Classic X → เห็น sidebar
- User B เลือก Millennium → เห็น taskbar
- User A login อีกครั้ง → ยังเห็น sidebar (ไม่เปลี่ยน!)
- ค่าใน database แยกกันต่อ user
- `php artisan theme:show` แสดงธีมที่แตกต่างกัน
