# 🎫 ระบบ Ticket Support - คู่มือการใช้งาน

## 📋 ภาพรวมระบบ

ระบบ Ticket Support ที่สมบูรณ์แบบสำหรับการจัดการคำขอความช่วยเหลือจากผู้ใช้ ออกแบบมาให้สวยงาม ทันสมัย และใช้งานง่าย

## ✨ คุณสมบัติหลัก

### 🎨 สำหรับผู้ใช้งานทั่วไป (User)
- ✅ สร้าง Ticket ใหม่พร้อมเลือกหมวดหมู่
- ✅ ดูรายการ Ticket ทั้งหมดของตัวเอง
- ✅ ติดตามสถานะ Ticket แบบ Real-time
- ✅ ตอบกลับ Ticket และสื่อสารกับทีมงาน
- ✅ ปิด Ticket เมื่อแก้ไขปัญหาเสร็จสิ้น
- ✅ แสดงจำนวน Ticket ที่เปิดอยู่ใน Sidebar

### 👨‍💼 สำหรับ Admin/Support Team
- ✅ ดูรายการ Ticket ทั้งหมดพร้อมระบบกรองข้อมูล
- ✅ มอบหมาย Ticket ให้พนักงานที่เหมาะสม
- ✅ เปลี่ยนสถานะ Ticket (เปิด, กำลังดำเนินการ, รอลูกค้า, แก้ไขแล้ว, ปิด)
- ✅ ปรับความสำคัญ (ต่ำ, ปานกลาง, สูง, วิกฤต)
- ✅ บันทึกภายใน (Internal Notes) สำหรับทีมงานเท่านั้น
- ✅ ตอบกลับ Ticket แบบ Public และ Private
- ✅ จัดการหมวดหมู่ Ticket
- ✅ Dashboard สถิติแบบ Real-time
- ✅ แสดงจำนวน Ticket ที่เปิดอยู่ใน Sidebar

## 🗂️ โครงสร้างไฟล์

### Database Migrations
```
database/migrations/
├── 2025_11_07_210000_create_ticket_categories_table.php
├── 2025_11_07_210001_create_tickets_table.php
├── 2025_11_07_210002_create_ticket_replies_table.php
└── 2025_11_07_210003_seed_default_ticket_categories.php
```

### Models
```
app/Models/
├── Ticket.php          # Model หลักของระบบ Ticket
├── TicketReply.php     # Model สำหรับการตอบกลับ
└── TicketCategory.php  # Model สำหรับหมวดหมู่
```

### Controllers
```
app/Http/Controllers/
├── Admin/
│   └── TicketController.php    # Admin CRUD operations
└── User/
    └── TicketController.php    # User ticket management
```

### Services
```
app/Services/
└── TicketService.php   # Business logic layer
```

### Views
```
resources/views/
├── admin/tickets/
│   ├── index.blade.php     # รายการ Ticket ทั้งหมด
│   └── show.blade.php      # รายละเอียด Ticket
└── user/tickets/
    ├── index.blade.php     # Ticket ของผู้ใช้
    ├── create.blade.php    # สร้าง Ticket ใหม่
    └── show.blade.php      # รายละเอียด Ticket
```

### Routes
```
routes/
├── admin.php    # Admin ticket routes
└── user.php     # User ticket routes
```

## 📊 โครงสร้างฐานข้อมูล

### ตาราง `ticket_categories`
| Field        | Type         | Description                      |
|--------------|--------------|----------------------------------|
| id           | bigint       | Primary Key                      |
| name         | varchar(255) | ชื่อหมวดหมู่                    |
| icon         | varchar(255) | FontAwesome icon class           |
| description  | text         | คำอธิบายหมวดหมู่                |
| color        | varchar(255) | สีของหมวดหมู่ (HEX)             |
| sort_order   | int          | ลำดับการแสดงผล                  |
| is_active    | boolean      | สถานะเปิดใช้งาน                 |

### ตาราง `tickets`
| Field           | Type         | Description                      |
|-----------------|--------------|----------------------------------|
| id              | bigint       | Primary Key                      |
| ticket_number   | varchar(255) | หมายเลข Ticket (unique)         |
| user_id         | bigint       | FK -> users                      |
| assigned_to     | bigint       | FK -> users (nullable)           |
| category_id     | bigint       | FK -> ticket_categories          |
| priority        | enum         | low, medium, high, critical      |
| status          | enum         | open, in_progress, waiting_customer, resolved, closed |
| subject         | varchar(255) | หัวข้อ Ticket                    |
| description     | text         | รายละเอียด                       |
| resolution_notes| text         | บันทึกการแก้ไข (nullable)        |
| last_reply_at   | timestamp    | วันที่ตอบกลับล่าสุด              |
| resolved_at     | timestamp    | วันที่แก้ไขเสร็จ                 |
| closed_at       | timestamp    | วันที่ปิด Ticket                 |

### ตาราง `ticket_replies`
| Field            | Type      | Description                      |
|------------------|-----------|----------------------------------|
| id               | bigint    | Primary Key                      |
| ticket_id        | bigint    | FK -> tickets                    |
| user_id          | bigint    | FK -> users                      |
| message          | text      | ข้อความตอบกลับ                   |
| is_internal_note | boolean   | บันทึกภายใน (staff only)         |
| attachments      | json      | ไฟล์แนบ (future feature)         |
| read_at          | timestamp | วันที่อ่านข้อความ                |

## 🎯 หมวดหมู่เริ่มต้น (Seeded Categories)

1. **การสนับสนุนทางเทคนิค** 🔧 (สีฟ้า)
   - ปัญหาทางเทคนิค, บั๊ก, หรือข้อผิดพลาดของระบบ

2. **การเงินและการชำระเงิน** 💵 (สีเขียว)
   - คำถามเกี่ยวกับการชำระเงิน, ค่าคอมมิชชั่น, การถอนเงิน

3. **บัญชีผู้ใช้** 👤 (สีม่วง)
   - ปัญหาเกี่ยวกับบัญชี, การเข้าสู่ระบบ, หรือข้อมูลส่วนตัว

4. **ระบบแอฟฟิลิเอท** 🌐 (สีส้ม)
   - คำถามเกี่ยวกับโครงสร้างแอฟฟิลิเอท, การเชิญชวน, หรือดาวน์ไลน์

5. **ผลิตภัณฑ์และบริการ** 📦 (สีชมพู)
   - คำถามเกี่ยวกับผลิตภัณฑ์, บริการ, หรือคุณสมบัติ

6. **ข้อเสนอแนะ** 💡 (สีฟ้าเขียว)
   - ข้อเสนอแนะ, คำขอคุณสมบัติใหม่, หรือการปรับปรุง

7. **อื่นๆ** ❓ (สีเทา)
   - คำถามทั่วไปหรือเรื่องอื่นๆ

## 🚀 การติดตั้ง

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Clear Cache (Optional)
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 3. เข้าถึงระบบ

#### สำหรับผู้ใช้:
```
/user/tickets
/user/tickets/create
/user/tickets/{id}
```

#### สำหรับ Admin:
```
/admin/tickets
/admin/tickets/{id}
/admin/tickets/categories
```

## 🎨 คุณสมบัติการออกแบบ

### สไตล์ที่ใช้
- ✅ Tailwind CSS 3.4
- ✅ Alpine.js 3.x (สำหรับ interactivity)
- ✅ FontAwesome 6.4 Icons
- ✅ Gradient Cards & Buttons
- ✅ Dark Mode Support
- ✅ Responsive Design (Mobile-first)
- ✅ Smooth Animations & Transitions

### สีหลัก
- Primary: Indigo & Purple Gradients
- Success: Green
- Warning: Yellow & Orange
- Danger: Red & Pink
- Info: Blue

## 📱 Responsive Design

ระบบรองรับการแสดงผลบนทุกอุปกรณ์:
- 📱 Mobile (< 768px)
- 💻 Tablet (768px - 1024px)
- 🖥️ Desktop (> 1024px)

## 🔐 Security Features

- ✅ CSRF Protection
- ✅ User Authentication Required
- ✅ Authorization Checks
- ✅ SQL Injection Prevention (Eloquent ORM)
- ✅ XSS Protection (Blade Templating)

## 🎯 Workflow ของ Ticket

```
┌─────────────┐
│   ผู้ใช้     │
│ สร้าง Ticket │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Status: OPEN│ ← สถานะเริ่มต้น
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│ Admin มอบหมาย    │
│ ให้พนักงาน       │
└──────┬───────────┘
       │
       ▼
┌────────────────────┐
│ Status: IN_PROGRESS│
└──────┬─────────────┘
       │
       ▼
┌──────────────────────┐
│ พนักงานตอบกลับ       │
│ หรือขอข้อมูลเพิ่ม     │
└──────┬───────────────┘
       │
       ├─────────────────┐
       ▼                 ▼
┌──────────────┐  ┌──────────────────┐
│Status:       │  │Status:           │
│WAITING_      │  │RESOLVED          │
│CUSTOMER      │  └──────┬───────────┘
└──────┬───────┘         │
       │                 │
       │ ลูกค้าตอบกลับ    │
       └─────────┬───────┘
                 ▼
          ┌─────────────┐
          │Status: CLOSED│
          └─────────────┘
```

## 📈 สถิติที่แสดง (Dashboard)

### Admin Dashboard
- ✅ Ticket ทั้งหมด
- ✅ Ticket เปิดอยู่
- ✅ Ticket กำลังดำเนินการ
- ✅ Ticket วิกฤต
- ✅ Ticket สูง
- ✅ Ticket ที่ยังไม่ได้มอบหมาย
- ✅ Ticket ของฉัน

### User Dashboard
- ✅ Ticket ทั้งหมดของฉัน
- ✅ Ticket เปิดอยู่
- ✅ Ticket ปิดแล้ว

## 🔧 การปรับแต่ง

### เพิ่มหมวดหมู่ใหม่
1. เข้า `/admin/tickets/categories`
2. คลิก "เพิ่มหมวดหมู่"
3. กรอกข้อมูล: ชื่อ, ไอคอน, คำอธิบาย, สี
4. บันทึก

### เปลี่ยนสี Priority/Status
แก้ไขใน Model: `app/Models/Ticket.php`
```php
public function getPriorityColorAttribute()
{
    return match($this->priority) {
        'low' => 'gray',
        'medium' => 'blue',
        'high' => 'orange',
        'critical' => 'red',
    };
}
```

## 🎓 คำแนะนำการใช้งาน

### สำหรับผู้ใช้
1. เลือกหมวดหมู่ที่ตรงกับปัญหา
2. เขียนหัวข้อที่ชัดเจนและกระชับ
3. อธิบายรายละเอียดให้ละเอียดที่สุด
4. ระบุขั้นตอนการเกิดปัญหา (ถ้ามี)
5. ติดตามและตอบกลับทีมงานเมื่อได้รับการติดต่อ

### สำหรับ Admin/Support
1. ตรวจสอบ Ticket ใหม่ทุกวัน
2. มอบหมาย Ticket ให้ทีมที่เหมาะสม
3. ตั้ง Priority ตามความเร่งด่วน
4. ตอบกลับภายใน 24 ชั่วโมง
5. ใช้ Internal Notes สำหรับการสื่อสารภายในทีม
6. อัปเดตสถานะให้เป็นปัจจุบัน
7. ปิด Ticket เมื่อแก้ไขปัญหาเสร็จสิ้น

## 🐛 Troubleshooting

### ปัญหา: ไม่เห็นเมนู Ticket ใน Sidebar
**แก้ไข:** Clear cache และ refresh browser
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### ปัญหา: Error เมื่อสร้าง Ticket
**แก้ไข:** ตรวจสอบว่า migrations ทำงานสำเร็จแล้ว
```bash
php artisan migrate:status
```

### ปัญหา: ไม่เห็นหมวดหมู่
**แก้ไข:** Run migration seeder อีกครั้ง
```bash
php artisan migrate:refresh --path=/database/migrations/2025_11_07_210003_seed_default_ticket_categories.php
```

## 📝 Future Enhancements (แนวคิดสำหรับอนาคต)

- [ ] ระบบแนบไฟล์ (File Attachments)
- [ ] Email Notifications
- [ ] Push Notifications
- [ ] SLA (Service Level Agreement) Tracking
- [ ] Ticket Templates
- [ ] Canned Responses (คำตอบสำเร็จรูป)
- [ ] Ticket Merging
- [ ] Knowledge Base Integration
- [ ] AI-powered Auto Response
- [ ] Customer Satisfaction Survey
- [ ] Multi-language Support
- [ ] Export Reports (PDF, Excel)

## 👨‍💻 Developer Notes

### API Endpoints (สำหรับอนาคต)
ระบบสามารถขยายเป็น REST API ได้โดยการสร้าง:
```
POST   /api/v1/tickets
GET    /api/v1/tickets
GET    /api/v1/tickets/{id}
POST   /api/v1/tickets/{id}/replies
PUT    /api/v1/tickets/{id}/status
```

### Event & Listeners
สามารถเพิ่ม Laravel Events สำหรับ:
- `TicketCreated`
- `TicketAssigned`
- `TicketReplied`
- `TicketStatusChanged`
- `TicketClosed`

### Queue Jobs
สำหรับการส่ง notifications:
- `SendTicketCreatedNotification`
- `SendTicketReplyNotification`
- `SendTicketAssignedNotification`

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ กรุณาสร้าง Ticket ในระบบ! 😊

---

**Created with ❤️ by Claude AI Assistant**
**Version: 1.0.0**
**Date: November 7, 2025**
