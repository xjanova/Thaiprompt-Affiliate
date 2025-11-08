# 🎫 Ticket System - Quick Start Guide

## ⚡ การติดตั้งแบบด่วน (5 นาที)

### 1. Deploy ด้วย Script อัตโนมัติ (แนะนำ)

```bash
# ให้สิทธิ์ execute script
chmod +x deploy-ticket-system.sh

# รัน deployment script
./deploy-ticket-system.sh
```

Script จะทำการ:
- ✅ ตรวจสอบ PHP version และ Database
- ✅ Backup database อัตโนมัติ
- ✅ Run migrations
- ✅ Clear และ optimize caches
- ✅ Verify routes
- ✅ Run tests (optional)

### 2. Deploy แบบ Manual

```bash
# 1. Run Migrations
php artisan migrate

# 2. Clear Caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 3. Optimize (Production only)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Verify Routes
php artisan route:list | grep ticket
```

## 🎯 การทดสอบระบบ

### ทดสอบด้วย Browser

1. **Login เป็น Admin**
   ```
   URL: /admin/tickets
   ```
   - ควรเห็นหน้า Dashboard
   - ควรเห็น badge counter ใน sidebar
   - ควรเห็น stats cards

2. **Login เป็น User**
   ```
   URL: /user/tickets
   ```
   - คลิก "สร้าง Ticket ใหม่"
   - เลือกหมวดหมู่
   - กรอกฟอร์มและส่ง
   - ควรเห็น Ticket ที่สร้าง

3. **ทดสอบ Workflow**
   ```
   Admin → Assign Ticket → Change Status → Reply
   User → View Reply → Reply Back
   ```

### ทดสอบด้วย Automated Tests

```bash
# Run all ticket tests
php artisan test --filter=TicketSystemTest

# Run specific test
php artisan test --filter=user_can_create_ticket

# Run with coverage (if configured)
php artisan test --filter=TicketSystemTest --coverage
```

## 📊 ตรวจสอบ Database

```sql
-- ตรวจสอบตารางที่สร้าง
SHOW TABLES LIKE 'ticket%';

-- ดูหมวดหมู่เริ่มต้น
SELECT * FROM ticket_categories;

-- ดู Ticket ที่มีอยู่
SELECT ticket_number, subject, status, priority
FROM tickets
ORDER BY created_at DESC
LIMIT 10;
```

## 🔍 Troubleshooting

### ปัญหา: ไม่เห็นเมนู Ticket

**สาเหตุ:** Cache ยังไม่ clear

```bash
php artisan view:clear
php artisan config:clear
# Refresh browser (Ctrl+Shift+R)
```

### ปัญหา: 404 Not Found

**สาเหตุ:** Routes ไม่ถูก register

```bash
php artisan route:clear
php artisan route:cache

# ตรวจสอบ routes
php artisan route:list | grep ticket
```

### ปัญหา: Migration Failed

**สาเหตุ:** Table อาจมีอยู่แล้ว

```bash
# ดู migration status
php artisan migrate:status

# Rollback และ migrate ใหม่
php artisan migrate:rollback --step=4
php artisan migrate
```

### ปัญหา: Class Not Found

**สาเหตุ:** Autoload ยังไม่ update

```bash
composer dump-autoload
php artisan clear-compiled
```

## 🎨 การปรับแต่ง

### เปลี่ยนสี Priority/Status

แก้ไขใน `app/Models/Ticket.php`:

```php
public function getPriorityColorAttribute()
{
    return match($this->priority) {
        'low' => 'gray',      // เปลี่ยนเป็น 'blue'
        'medium' => 'blue',   // เปลี่ยนเป็น 'green'
        'high' => 'orange',   // เปลี่ยนเป็น 'yellow'
        'critical' => 'red',  // คงเดิม
    };
}
```

### เพิ่มหมวดหมู่ใหม่

```php
// Via Admin UI
/admin/tickets/categories

// Via Seeder
php artisan make:seeder AdditionalTicketCategoriesSeeder
```

### เปลี่ยนภาษา

แก้ไขใน `app/Models/Ticket.php`:

```php
public function getStatusLabelAttribute()
{
    return match($this->status) {
        'open' => 'Open',          // เดิม: 'เปิด'
        'in_progress' => 'In Progress',
        'waiting_customer' => 'Waiting for Customer',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    };
}
```

## 📈 Monitoring

### ดูสถิติ Ticket

```bash
# Via Tinker
php artisan tinker

>>> use App\Services\TicketService;
>>> $service = app(TicketService::class);
>>> $stats = $service->getStatistics();
>>> print_r($stats);
```

### ดู Active Tickets

```sql
SELECT
    COUNT(*) as total,
    status,
    priority
FROM tickets
WHERE status IN ('open', 'in_progress')
GROUP BY status, priority;
```

## 🚀 Production Checklist

- [ ] Backup database ก่อน deploy
- [ ] Test บน staging environment
- [ ] Run migrations
- [ ] Clear caches
- [ ] Optimize application
- [ ] Test routes
- [ ] ทดสอบสร้าง Ticket
- [ ] ทดสอบ permissions
- [ ] ตรวจสอบ email notifications (ถ้ามี)
- [ ] Monitor error logs

## 📞 การขอความช่วยเหลือ

หากพบปัญหา:

1. ตรวจสอบ logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. ดู error messages ใน browser console

3. ตรวจสอบเอกสาร:
   - `TICKET_SYSTEM_README.md`
   - `TICKET_SYSTEM_CHECKLIST.md`

4. Run diagnostic:
   ```bash
   php artisan about
   php artisan route:list
   php artisan migrate:status
   ```

## 💡 Tips & Best Practices

### สำหรับ Admins
- ✅ ตั้ง priority ให้เหมาะสม
- ✅ มอบหมาย Ticket โดยเร็ว
- ✅ ใช้ Internal Notes สำหรับการสื่อสารภายในทีม
- ✅ อัพเดท status ให้เป็นปัจจุบัน
- ✅ ตอบกลับภายใน 24 ชั่วโมง

### สำหรับ Users
- ✅ เลือกหมวดหมู่ที่ถูกต้อง
- ✅ เขียนหัวข้อที่ชัดเจน
- ✅ อธิบายรายละเอียดให้ครบถ้วน
- ✅ ตอบกลับทีมงานเมื่อได้รับการติดต่อ
- ✅ ปิด Ticket เมื่อแก้ไขเสร็จ

## 🎯 Next Steps

หลังจาก deploy แล้ว:

1. สร้าง test tickets
2. ทดสอบ workflow ทั้งหมด
3. ฝึกทีมงานใช้งาน
4. Setup email notifications (optional)
5. กำหนด SLA (Service Level Agreement)
6. Monitor และปรับปรุง

---

**สนุกกับการใช้งาน Ticket System! 🎉**

Version: 1.0.0
Updated: 2025-11-07
