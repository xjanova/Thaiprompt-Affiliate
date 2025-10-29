# 🚀 TP-Affiliate - Thai Prompt Affiliate System

**Modern, Professional, Ready-to-Use Affiliate Marketing Platform**

## ✨ คุณสมบัติเด่น

### 🎯 ระบบติดตั้งแบบง่าย (One-Click Installation)
- ติดตั้งได้ภายใน 2 นาที
- ไม่ต้องแก้ไขโค้ด
- รองรับ Docker และ Manual Installation

### 🎨 UI/UX ระดับมืออาชีพ
- **Admin Dashboard** - กราฟ, widgets, real-time statistics
- **Frontend** - GSAP animations, responsive, สวยงามทุกอุปกรณ์
- แยกชัดเจน: หน้าบ้าน/หลังบ้าน

### 👑 Super User System
- ระบบ Authentication ที่ปลอดภัย
- Role-based Access Control
- Activity Logging

### 📱 Responsive Design
- รองรับทุกอุปกรณ์ (Desktop, Tablet, Mobile)
- Mobile-first approach
- Fast loading & optimized

---

## 🛠️ เทคโนโลยีที่ใช้

### Backend
- **Laravel 11** - PHP Framework
- **MySQL/SQLite** - Database
- **Redis** - Caching (optional)

### Frontend
- **Tailwind CSS** - UI Framework
- **Alpine.js** - Lightweight JS Framework
- **GSAP** - Smooth Animations
- **Chart.js** - Data Visualization

### DevOps
- **Docker** - Containerization
- **Nginx** - Web Server
- **PHP-FPM** - Process Manager

---

## ⚡ การติดตั้งอย่างรวดเร็ว

### ความต้องการของระบบ

- PHP 8.1 หรือสูงกว่า
- Composer
- SQLite หรือ MySQL
- Node.js & NPM (ถ้าต้องการ build frontend assets)

### การติดตั้ง

> **⚠️ สำคัญ:** Repository นี้เป็น private คุณต้อง authenticate ก่อน clone
> 📖 ดูวิธีการ: [AUTHENTICATION.md](AUTHENTICATION.md)

```bash
# 1. Clone repository (จะถาม username และ Personal Access Token)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. ติดตั้ง dependencies
composer install

# 3. ตั้งค่า environment
cp .env.example .env
php artisan key:generate

# 4. สร้างฐานข้อมูล (SQLite)
touch database/database.sqlite

# 5. รัน migrations
php artisan migrate

# 6. ตั้งค่า permissions
chmod -R 775 storage bootstrap/cache

# 7. รันเซิร์ฟเวอร์
php artisan serve
```

**เปิด browser ไปที่:** `http://localhost:8000`

### การติดตั้งด้วย MySQL (Optional)

ถ้าต้องการใช้ MySQL แทน SQLite:

```bash
# แก้ไขไฟล์ .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password

# สร้าง database
mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate;"

# รัน migrations
php artisan migrate
```

---

## 📖 คู่มือการใช้งาน

### เอกสารสำคัญ

- 📘 [README.md](README.md) - ภาพรวมโปรเจกต์
- 🚀 [GETTING-STARTED.md](GETTING-STARTED.md) - คู่มือเริ่มต้นใช้งาน
- 💻 [DEVELOPMENT.md](DEVELOPMENT.md) - คู่มือสำหรับนักพัฒนา
- 🌐 [DEPLOYMENT.md](DEPLOYMENT.md) - คู่มือการ Deploy สู่ Production
- 🔐 [AUTHENTICATION.md](AUTHENTICATION.md) - คู่มือการ Clone Private Repository

---

### การเข้าสู่ระบบครั้งแรก

หลังจากติดตั้งและรัน `php artisan serve`:

1. เปิด browser ไปที่ `http://localhost:8000`
2. ระบบจะพาไปหน้า **Setup Wizard** อัตโนมัติ
3. กรอกข้อมูล Super Admin:
   - **ชื่อ**: ชื่อของคุณ
   - **อีเมล**: อีเมลสำหรับเข้าสู่ระบบ
   - **รหัสผ่าน**: อย่างน้อย 8 ตัวอักษร
   - **ยืนยันรหัสผ่าน**: ใส่รหัสผ่านอีกครั้ง
4. คลิก **"สร้างบัญชี Super Admin"**
5. เข้าสู่ระบบสำเร็จ! คุณจะถูกนำไปที่ **Admin Dashboard**

### เส้นทางหลัก

- **หน้าแรก**: `http://localhost:8000`
- **เข้าสู่ระบบ**: `http://localhost:8000/login`
- **สมัครสมาชิก**: `http://localhost:8000/register`
- **Admin Dashboard**: `http://localhost:8000/admin/dashboard`

### โครงสร้างโปรเจกต์

```
Thaiprompt-Affiliate/
├── app/
│   ├── Console/              # Artisan Commands
│   ├── Exceptions/           # Exception Handler
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/        # Admin Controllers
│   │   │   ├── Frontend/     # Frontend Controllers
│   │   │   └── Auth/         # Authentication
│   │   └── Middleware/       # Middlewares
│   ├── Models/               # Eloquent Models
│   └── Providers/            # Service Providers
├── bootstrap/
│   ├── app.php              # Application Bootstrap
│   └── cache/               # Cached Files
├── config/                  # Configuration Files
├── database/
│   ├── migrations/          # Database Migrations
│   ├── seeders/             # Database Seeders
│   └── database.sqlite      # SQLite Database
├── public/                  # Public Assets
│   ├── index.php           # Entry Point
│   └── .htaccess
├── resources/
│   └── views/
│       ├── admin/           # Admin Views
│       ├── auth/            # Auth Views
│       ├── frontend/        # Frontend Views
│       └── layouts/         # Layout Templates
├── routes/
│   ├── web.php             # Web Routes
│   ├── admin.php           # Admin Routes
│   ├── api.php             # API Routes
│   └── console.php         # Console Routes
├── storage/                # Storage Files
├── .env                    # Environment Config
├── .env.example           # Example Environment
├── artisan                # Artisan CLI
├── composer.json          # Composer Dependencies
└── README.md             # Documentation
```

---

## 🎨 Screenshots

### Admin Dashboard
*Coming soon...*

### Frontend
*Coming soon...*

---

## 🚀 การ Deploy สู่ Production

### Deploy ในคำสั่งเดียว

```bash
# วิธีที่ 1: ใช้ Shell Script
./deploy.sh

# วิธีที่ 2: ใช้ Artisan Command
php artisan deploy

# วิธีที่ 3: Optimize เท่านั้น
php artisan app:optimize --clear
```

### Deploy Checklist

- ✅ ตั้งค่า `APP_ENV=production`
- ✅ ตั้งค่า `APP_DEBUG=false`
- ✅ กำหนด `APP_KEY`
- ✅ ตั้งค่า Database credentials
- ✅ ตั้งค่า Cache driver (แนะนำ Redis)
- ✅ ตั้งค่า SSL Certificate
- ✅ Backup database ก่อน deploy

📖 **อ่านคู่มือฉบับเต็ม:** [DEPLOYMENT.md](DEPLOYMENT.md)

---

## 🔐 ความปลอดภัย

- **CSRF Protection** - ป้องกัน Cross-Site Request Forgery
- **XSS Prevention** - ป้องกัน Cross-Site Scripting
- **SQL Injection Prevention** - ใช้ Eloquent ORM
- **Password Hashing** - bcrypt algorithm
- **Rate Limiting** - จำกัดจำนวน requests
- **HTTPS Ready** - รองรับ SSL/TLS

---

## 📊 ฟีเจอร์ที่จะมาในเวอร์ชันถัดไป

- [ ] ระบบ MLM/Network Marketing
- [ ] Payment Gateway Integration
- [ ] E-commerce Module
- [ ] Advanced Analytics
- [ ] API Documentation
- [ ] Mobile App (React Native)

---

## 🤝 การสนับสนุน

### Bug Reports
หากพบบั๊กหรือปัญหา กรุณา:
1. เปิด [Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
2. อธิบายปัญหาอย่างละเอียด
3. แนบ screenshots (ถ้ามี)

### Feature Requests
ต้องการฟีเจอร์ใหม่? เปิด [Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues) พร้อม label "feature request"

---

## 📝 License

MIT License - ใช้งานได้อย่างอิสระ

---

## 🙏 Credits

Developed with ❤️ by [xjanova](https://github.com/xjanova)

Powered by:
- Laravel
- Tailwind CSS
- GSAP
- Alpine.js

---

## 📞 ติดต่อ

- **GitHub**: [@xjanova](https://github.com/xjanova)
- **Email**: support@thaiprompt.com

---

**⭐ ถ้าชอบโปรเจกต์นี้ กรุณากด Star ให้ด้วยนะครับ!**
