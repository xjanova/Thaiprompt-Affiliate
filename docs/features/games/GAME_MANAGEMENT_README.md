# 🎮 Game Management System - คู่มือการใช้งาน

ระบบจัดการเกม 3D Gallery แบบ Dynamic ที่สามารถควบคุมและปรับแต่งได้ผ่าน Admin Panel

## 🌟 Features

- ✅ **Admin Panel** - จัดการเกมผ่านหน้า Admin
- ✅ **Dynamic Content** - เพิ่ม/แก้ไข/ลบเกมได้ตามต้องการ
- ✅ **Customizable** - ปรับแต่งสี, ไอคอน, รูปภาพ, และสไตล์ของการ์ด
- ✅ **Multi-language** - รองรับ 2 ภาษา (ไทย/อังกฤษ)
- ✅ **3D Effects** - เอฟเฟกต์ 3D OpenGL ที่สวยงาม
- ✅ **Responsive** - ใช้งานได้ทุกขนาดหน้าจอ
- ✅ **Order Management** - จัดลำดับการแสดงผลเกมได้

## 📦 Installation

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Run Seeder (ตัวอย่างเกม)

```bash
php artisan db:seed --class=GameSeeder
```

## 🚀 Usage

### Frontend - หน้าเลือกเกม

เข้าถึงหน้าเลือกเกมที่:
```
/demo/game-selector
```

### Admin Panel - จัดการเกม

เข้าถึงหน้าจัดการเกมที่:
```
/admin/games
```

## 🎨 การจัดการเกม

### เพิ่มเกมใหม่

1. เข้าไปที่ `/admin/games`
2. คลิก "เพิ่มเกมใหม่"
3. กรอกข้อมูล:
   - **ชื่อเกม** (ไทย/อังกฤษ)
   - **คำอธิบาย** (ไทย/อังกฤษ)
   - **ไอคอน** (Emoji เช่น 🎮, 🚀, ⚡)
   - **รูปภาพ** (Optional)
   - **URL** (ลิงก์ไปยังเกม)
   - **สีหลัก** (Primary Color)
   - **สีรอง** (Secondary Color)
   - **สี Glow** (เช่น rgba(0, 255, 255, 0.8))
   - **รูปแบบการ์ด** (Default, Gradient, Glass, Neon)
   - **ลำดับการแสดงผล**
   - **สถานะ** (เปิด/ปิดใช้งาน)
4. คลิก "บันทึกเกม"

### แก้ไขเกม

1. เข้าไปที่ `/admin/games`
2. คลิก "แก้ไข" ที่เกมที่ต้องการ
3. แก้ไขข้อมูลที่ต้องการ
4. คลิก "บันทึกการเปลี่ยนแปลง"

### ลบเกม

1. เข้าไปที่ `/admin/games`
2. คลิก "ลบ" ที่เกมที่ต้องการ
3. ยืนยันการลบ

### เปิด/ปิดการใช้งาน

- คลิกที่สถานะของเกมเพื่อเปิด/ปิดการใช้งาน
- เกมที่ปิดใช้งานจะไม่แสดงในหน้า game selector

## 🎨 รูปแบบการ์ด (Card Styles)

### 1. Default
การ์ดแบบมาตรฐานพร้อม background blur

### 2. Gradient
การ์ดแบบ gradient สีสวยงาม

### 3. Glass
การ์ดแบบกระจกใสทันสมัย (Glassmorphism)

### 4. Neon
การ์ดแบบ neon สะดุดตา

## 📸 การจัดการรูปภาพ

### อัปโหลดรูปภาพ

1. รองรับไฟล์: JPG, PNG, GIF, WebP
2. ขนาดสูงสุด: 2MB
3. รูปภาพจะถูกเก็บใน: `public/images/games/`

### แนะนำขนาดรูปภาพ

- ขนาดที่แนะนำ: 800x600 px หรือ 16:9 ratio
- รูปภาพจะแสดงเป็น background blur ของการ์ด

## 🎯 การตั้งค่าสี

### สีหลัก (Primary Color)
- ใช้สำหรับ: ไอคอน, ชื่อเกม, ปุ่ม
- รูปแบบ: Hex Color (#00ffff)

### สีรอง (Secondary Color)
- ใช้สำหรับ: Gradient ของปุ่ม
- รูปแบบ: Hex Color (#0080ff)

### สี Glow
- ใช้สำหรับ: เอฟเฟกต์เรืองแสง
- รูปแบบ: RGBA (rgba(0, 255, 255, 0.8))

## 🌍 Multi-language Support

ระบบรองรับ 2 ภาษา:
- **ไทย** (th) - ค่าเริ่มต้น
- **อังกฤษ** (en)

เมื่อเปลี่ยนภาษา ระบบจะแสดง:
- `title_en` และ `description_en` สำหรับภาษาอังกฤษ
- `title` และ `description` สำหรับภาษาไทย

## 📊 Database Structure

### ตาราง: `games`

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary Key |
| title | string | ชื่อเกม (ไทย) |
| title_en | string | ชื่อเกม (อังกฤษ) |
| description | text | คำอธิบาย (ไทย) |
| description_en | text | คำอธิบาย (อังกฤษ) |
| icon | string | Emoji ไอคอน |
| image | string | path รูปภาพ |
| url | string | URL เกม |
| primary_color | string | สีหลัก |
| secondary_color | string | สีรอง |
| glow_color | string | สี glow |
| order | integer | ลำดับการแสดงผล |
| is_active | boolean | สถานะเปิด/ปิด |
| card_style | string | รูปแบบการ์ด |
| meta_data | json | ข้อมูลเพิ่มเติม |
| created_at | timestamp | วันที่สร้าง |
| updated_at | timestamp | วันที่อัปเดต |
| deleted_at | timestamp | วันที่ลบ (soft delete) |

## 🔗 API Endpoints

### Frontend
- `GET /demo/game-selector` - หน้าเลือกเกม
- `GET /api/games` - ดึงข้อมูลเกมทั้งหมด (JSON)

### Admin
- `GET /admin/games` - รายการเกมทั้งหมด
- `GET /admin/games/create` - ฟอร์มเพิ่มเกม
- `POST /admin/games` - บันทึกเกมใหม่
- `GET /admin/games/{id}/edit` - ฟอร์มแก้ไขเกม
- `PUT /admin/games/{id}` - อัปเดตเกม
- `DELETE /admin/games/{id}` - ลบเกม
- `PATCH /admin/games/{id}/toggle-active` - เปิด/ปิดการใช้งาน
- `POST /admin/games/update-order` - อัปเดตลำดับ

## 🛠️ Files Created

### Models
- `app/Models/Game.php`

### Controllers
- `app/Http/Controllers/Admin/GameController.php`
- `app/Http/Controllers/Frontend/GameController.php`

### Views
- `resources/views/admin/games/index.blade.php`
- `resources/views/admin/games/create.blade.php`
- `resources/views/admin/games/edit.blade.php`
- `resources/views/demo-game-selector.blade.php`

### Migrations
- `database/migrations/2025_11_13_121744_create_games_table.php`

### Seeders
- `database/seeders/GameSeeder.php`

### Routes
- `routes/web.php` - Frontend routes
- `routes/admin.php` - Admin routes

## 🎮 ตัวอย่างเกมที่มีใน Seeder

1. **3D Navigation** 🧭
   - สีฟ้าน้ำทะเล (#00ffff)
   - URL: /demo/3d-navigation

2. **Space Shooter** 🚀
   - สีชมพู/ม่วง (#ff00ff)
   - URL: /demo/space-shooter

3. **Loading Demo** ⚡
   - สีเหลือง (#ffff00)
   - URL: /demo/loading

## 💡 Tips

1. **เลือกสีที่เข้ากัน** - ใช้ color picker เพื่อเลือกสีที่เข้ากันสวยงาม
2. **ใช้ไอคอนที่เหมาะสม** - เลือก Emoji ที่เหมาะกับประเภทเกม
3. **เพิ่มรูปภาพคุณภาพดี** - ใช้รูปภาพความละเอียดสูงเพื่อประสบการณ์ที่ดี
4. **จัดลำดับที่เหมาะสม** - ใส่เกมยอดนิยมไว้ลำดับต้นๆ
5. **ทดสอบทุกภาษา** - ตรวจสอบว่าข้อความทั้ง 2 ภาษาแสดงผลถูกต้อง

## 🐛 Troubleshooting

### เกมไม่แสดงในหน้า game selector
- ตรวจสอบว่าเกมนั้นมีสถานะ "เปิดใช้งาน" (is_active = true)
- เช็คว่า migration และ seeder ทำงานสำเร็จ

### รูปภาพไม่แสดง
- ตรวจสอบว่าไดเรกทอรี `public/images/games/` มีอยู่
- ตรวจสอบ permissions ของไดเรกทอรี
- ตรวจสอบว่า path ของรูปภาพถูกต้อง

### สีไม่แสดงตามที่ตั้งค่า
- ตรวจสอบรูปแบบสีว่าถูกต้อง (Hex หรือ RGBA)
- ล้าง cache ของเบราว์เซอร์

## 📝 License

This game management system is part of Thai Prompt Affiliate project.

## 👨‍💻 Developer

Created with ❤️ by Claude AI Assistant

---

🎮 **Happy Gaming!** 🚀
