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

### วิธีที่ 1: Docker (แนะนำ)

```bash
# 1. Clone repository
git clone https://github.com/xjanova/TP-Affiliate.git
cd TP-Affiliate

# 2. Run installation script
./install.sh

# 3. เปิดเบราว์เซอร์
# http://localhost
```

### วิธีที่ 2: Manual Installation

```bash
# 1. Clone และติดตั้ง dependencies
git clone https://github.com/xjanova/TP-Affiliate.git
cd TP-Affiliate
composer install
npm install

# 2. ตั้งค่า environment
cp .env.example .env
php artisan key:generate

# 3. ติดตั้งระบบ
php artisan install

# 4. รันเซิร์ฟเวอร์
php artisan serve
```

---

## 📖 คู่มือการใช้งาน

### การเข้าสู่ระบบครั้งแรก

หลังจากติดตั้งเสร็จ:

1. เปิด browser ไปที่ `http://localhost`
2. คลิก "เริ่มต้นใช้งาน" เพื่อสร้าง Super User
3. กรอกข้อมูล:
   - ชื่อ
   - อีเมล
   - รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
4. คลิก "สร้างบัญชี" เพื่อเข้าสู่ระบบ

### โครงสร้างโปรเจกต์

```
TP-Affiliate/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin Controllers
│   │   │   ├── Frontend/       # Frontend Controllers
│   │   │   └── Auth/           # Authentication
│   │   └── Middleware/
│   └── Models/
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin Views
│   │   ├── frontend/           # Frontend Views
│   │   └── layouts/
│   ├── js/
│   │   ├── admin.js           # Admin Scripts
│   │   └── frontend.js         # Frontend Scripts (GSAP)
│   └── css/
├── routes/
│   ├── web.php                 # Web Routes
│   ├── admin.php               # Admin Routes
│   └── api.php                 # API Routes
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
└── docker/
```

---

## 🎨 Screenshots

### Admin Dashboard
*Coming soon...*

### Frontend
*Coming soon...*

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
1. เปิด [Issue](https://github.com/xjanova/TP-Affiliate/issues)
2. อธิบายปัญหาอย่างละเอียด
3. แนบ screenshots (ถ้ามี)

### Feature Requests
ต้องการฟีเจอร์ใหม่? เปิด [Issue](https://github.com/xjanova/TP-Affiliate/issues) พร้อม label "feature request"

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
