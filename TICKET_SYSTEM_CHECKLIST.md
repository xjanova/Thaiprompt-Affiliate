# 🔍 Ticket System - Pre-Launch Checklist

## ✅ การตรวจสอบก่อนเปิดใช้งาน

### 1. Database & Models
- [x] Migration files สร้างถูกต้อง (4 files)
- [x] Models มี relationships ครบถ้วน
- [x] User model มี role field และ methods ที่จำเป็น
  - `is_super_admin` (field)
  - `role` (field)
  - `isSuperAdmin()` (method)
  - `hasPermission()` (method)

### 2. Controllers
- [x] Admin\TicketController - ใช้งานได้
- [x] User\TicketController - ใช้งานได้
- [x] Service Layer (TicketService) - พร้อมใช้งาน

### 3. Routes
- [x] Admin routes (/admin/tickets/*)
  - GET  /admin/tickets (index)
  - GET  /admin/tickets/{id} (show)
  - POST /admin/tickets/{id}/reply
  - POST /admin/tickets/{id}/assign
  - PUT  /admin/tickets/{id}/status
  - PUT  /admin/tickets/{id}/priority
  - PUT  /admin/tickets/{id}/category
  - DELETE /admin/tickets/{id}

- [x] User routes (/user/tickets/*)
  - GET  /user/tickets (index)
  - GET  /user/tickets/create
  - POST /user/tickets (store)
  - GET  /user/tickets/{id} (show)
  - POST /user/tickets/{id}/reply
  - POST /user/tickets/{id}/close

### 4. Views
- [x] Admin Views
  - admin/tickets/index.blade.php ✓
  - admin/tickets/show.blade.php ✓

- [x] User Views
  - user/tickets/index.blade.php ✓
  - user/tickets/create.blade.php ✓
  - user/tickets/show.blade.php ✓

### 5. Layouts & Scripts
- [x] Alpine.js - โหลดใน layout (ไม่ต้อง include ซ้ำ)
- [x] FontAwesome Icons - พร้อมใช้งาน
- [x] Tailwind CSS - พร้อมใช้งาน
- [x] Dark Mode - รองรับเต็มรูปแบบ

### 6. Navigation
- [x] Admin Sidebar - มีเมนู Ticket พร้อม badge counter
- [x] User Sidebar - มีเมนู Ticket พร้อม badge counter

### 7. Permissions
- [x] User authentication required (middleware)
- [x] Admin role check
- [x] User can only view own tickets

## 🚀 ขั้นตอนการเปิดใช้งาน

### 1. Run Migrations
```bash
php artisan migrate
```

**ผลลัพธ์ที่คาดหวัง:**
```
Migrating: 2025_11_07_210000_create_ticket_categories_table
Migrated:  2025_11_07_210000_create_ticket_categories_table (xx.xx ms)

Migrating: 2025_11_07_210001_create_tickets_table
Migrated:  2025_11_07_210001_create_tickets_table (xx.xx ms)

Migrating: 2025_11_07_210002_create_ticket_replies_table
Migrated:  2025_11_07_210002_create_ticket_replies_table (xx.xx ms)

Migrating: 2025_11_07_210003_seed_default_ticket_categories
Migrated:  2025_11_07_210003_seed_default_ticket_categories (xx.xx ms)
```

### 2. Clear Cache (Optional but Recommended)
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 3. ตรวจสอบ Routes
```bash
php artisan route:list | grep ticket
```

**ผลลัพธ์ที่คาดหวัง:**
```
GET|HEAD  admin/tickets ........................ admin.tickets.index
GET|HEAD  admin/tickets/{ticket} ............... admin.tickets.show
POST      admin/tickets/{ticket}/assign ........ admin.tickets.assign
POST      admin/tickets/{ticket}/reply ......... admin.tickets.reply
...
GET|HEAD  user/tickets ......................... user.tickets.index
GET|HEAD  user/tickets/create .................. user.tickets.create
POST      user/tickets ......................... user.tickets.store
...
```

### 4. ทดสอบการเข้าถึง

#### Admin Access:
1. เข้าสู่ระบบด้วยบัญชี Admin
2. ไปที่ `/admin/dashboard`
3. คลิกเมนู "🎫 Ticket Support" ใน Sidebar
4. ควรเห็นหน้า Ticket List

#### User Access:
1. เข้าสู่ระบบด้วยบัญชี User
2. ไปที่ `/user/dashboard`
3. คลิกเมนู "🎫 Ticket Support" ใน Sidebar
4. ควรเห็นหน้า Ticket List ของตัวเอง
5. คลิก "สร้าง Ticket ใหม่"
6. กรอกฟอร์มและส่ง

## 🐛 การแก้ปัญหา

### ปัญหา: ไม่เห็นเมนู Ticket
**สาเหตุ:** Cache ยังไม่ clear
**แก้ไข:**
```bash
php artisan view:clear
php artisan config:clear
```

### ปัญหา: Error "Class 'Ticket' not found"
**สาเหตุ:** Autoload ยังไม่ update
**แก้ไข:**
```bash
composer dump-autoload
```

### ปัญหา: Migration ไม่ทำงาน
**สาเหตุ:** Database connection ไม่ถูกต้อง
**แก้ไข:** ตรวจสอบ .env file
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### ปัญหา: 404 Not Found เมื่อเข้า /admin/tickets
**สาเหตุ:** Routes ไม่ถูก register
**แก้ไข:**
```bash
php artisan route:clear
php artisan config:clear
```

### ปัญหา: Badge counter ไม่แสดง
**สาเหตุ:** ยังไม่มี Ticket ในระบบ
**แก้ไข:** สร้าง Ticket ทดสอบผ่าน User interface

## 📊 ทดสอบ Workflow

### Test Case 1: User สร้าง Ticket
1. Login เป็น User
2. ไปที่ `/user/tickets/create`
3. เลือกหมวดหมู่: "การสนับสนุนทางเทคนิค"
4. หัวข้อ: "ทดสอบระบบ Ticket"
5. รายละเอียด: "นี่คือการทดสอบระบบ Ticket"
6. ความสำคัญ: "ปานกลาง"
7. คลิก "สร้าง Ticket"
8. ✅ ควรเห็นหน้า Ticket detail
9. ✅ Badge counter ใน Sidebar ควรแสดง "1"

### Test Case 2: Admin ดู Ticket
1. Login เป็น Admin
2. ไปที่ `/admin/tickets`
3. ✅ ควรเห็น Ticket ที่ User สร้าง
4. คลิก "ดู"
5. ✅ ควรเห็นรายละเอียด Ticket

### Test Case 3: Admin มอบหมาย Ticket
1. ในหน้า Ticket detail (Admin)
2. เลือก "มอบหมายให้" dropdown
3. เลือกพนักงาน
4. ✅ ระบบควร auto-save
5. ✅ ควรเห็นชื่อพนักงานที่ได้รับมอบหมาย

### Test Case 4: Admin ตอบกลับ Ticket
1. ในหน้า Ticket detail (Admin)
2. พิมพ์ข้อความใน textarea
3. คลิก "ส่งข้อความ"
4. ✅ ควรเห็นข้อความตอบกลับ
5. ✅ Badge "ทีมงาน" ควรปรากฏ

### Test Case 5: User ตอบกลับ Ticket
1. Login เป็น User
2. ไปที่ Ticket ที่สร้างไว้
3. ✅ ควรเห็นข้อความจาก Admin
4. พิมพ์ข้อความตอบกลับ
5. คลิก "ส่งข้อความ"
6. ✅ ควรเห็นข้อความตอบกลับ

### Test Case 6: Admin ปิด Ticket
1. Login เป็น Admin
2. ไปที่ Ticket detail
3. เปลี่ยน Status dropdown เป็น "ปิด"
4. ✅ ระบบควร auto-save
5. ✅ Status badge ควรเปลี่ยนเป็น "ปิด"
6. ✅ ไม่สามารถตอบกลับได้อีก

## ✅ Checklist สำหรับ Production

- [ ] ทดสอบ Migration บน production database
- [ ] ตรวจสอบ permissions ของแต่ละ role
- [ ] ทดสอบ responsive design บนอุปกรณ์ต่างๆ
- [ ] ทดสอบ dark mode
- [ ] ตั้งค่า notification (ถ้าต้องการ)
- [ ] ตั้งค่า email alerts (ถ้าต้องการ)
- [ ] สำรองข้อมูล database
- [ ] Document สำหรับทีม support

## 📝 Notes

- ระบบใช้ Alpine.js ที่มีอยู่ใน layout แล้ว (ไม่ต้อง include ซ้ำ)
- Badge counter จะอัพเดทเมื่อ refresh หน้า
- Internal notes จะแสดงเฉพาะ staff เท่านั้น
- User สามารถดูได้เฉพาะ public replies
- Ticket number สร้างอัตโนมัติในรูปแบบ: TKT-XXX-YYYYMMDD-9999

## 🎯 Success Criteria

ระบบถือว่าพร้อมใช้งานเมื่อ:
- ✅ Migration ทำงานสำเร็จ
- ✅ สามารถสร้าง Ticket ได้
- ✅ สามารถดู Ticket list ได้
- ✅ สามารถตอบกลับ Ticket ได้
- ✅ สามารถมอบหมาย Ticket ได้
- ✅ สามารถเปลี่ยนสถานะได้
- ✅ Badge counter แสดงถูกต้อง
- ✅ Dark mode ทำงานได้
- ✅ Responsive ทุกขนาดหน้าจอ

---

**Last Updated:** 2025-11-07
**Status:** ✅ Ready for Testing
