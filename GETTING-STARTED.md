# 🚀 Getting Started - TP-Affiliate

## เริ่มต้นใช้งานภายใน 2 นาที!

---

## 📋 สิ่งที่ต้องเตรียม

### ความต้องการของระบบ
- ✅ PHP 8.1 หรือสูงกว่า
- ✅ Composer
- ✅ SQLite หรือ MySQL
- ✅ Node.js & NPM (optional - สำหรับ frontend)

---

## ⚡ ติดตั้งอย่างรวดเร็ว

### ขั้นตอนการติดตั้ง

> **⚠️ สำคัญ:** Repository นี้เป็น private คุณต้อง authenticate ก่อน clone
>
> **วิธีการ:**
> 1. สร้าง Personal Access Token: https://github.com/settings/tokens
> 2. เลือก scope: `repo` (Full control of private repositories)
> 3. ใช้ token แทนรหัสผ่านเมื่อ git clone
>
> 📖 **คู่มือละเอียด:** [AUTHENTICATION.md](AUTHENTICATION.md)

```bash
# 1. Clone project (จะถาม username/token)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Create database (SQLite)
touch database/database.sqlite

# 5. Run migrations
php artisan migrate

# 6. Set permissions
chmod -R 775 storage bootstrap/cache

# 7. Start server
php artisan serve
```

**เปิด browser ไปที่:** `http://localhost:8000`

### ติดตั้งด้วย MySQL (ถ้าต้องการ)

```bash
# 1. สร้าง database
mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate;"

# 2. แก้ไขไฟล์ .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password

# 3. Run migrations
php artisan migrate

# 4. Start server
php artisan serve
```

---

## 🎯 ขั้นตอนแรกหลังติดตั้ง

### 1. สร้าง Super User Account

เมื่อเปิด browser ครั้งแรก คุณจะเห็นหน้า Installation Wizard:

1. คลิก **"เริ่มต้นใช้งาน"**
2. กรอกข้อมูล:
   ```
   ชื่อ:           Admin
   อีเมล:          admin@example.com
   รหัสผ่าน:        อย่างน้อย 8 ตัวอักษร
   ยืนยันรหัสผ่าน:  [ซ้ำกับด้านบน]
   ```
3. คลิก **"สร้างบัญชีและเข้าสู่ระบบ"**
4. เสร็จสิ้น! คุณจะเข้าสู่ Admin Dashboard

---

## 🎨 ทัวร์ระบบ

### Admin Dashboard
```
http://localhost:8000/admin/dashboard
```

คุณสมบัติ:
- 📊 Real-time Statistics & Charts
- 👥 User Management
- 💰 Commission Tracking
- 📈 Performance Analytics
- ⚙️ System Settings

### Frontend (Public Site)
```
http://localhost:8000
```

คุณสมบัติ:
- 🎨 Beautiful GSAP Animations
- 📱 Fully Responsive
- ⚡ Fast & Optimized
- 🌐 Multi-language Support (Coming soon)

---

## 🛠️ คำสั่งที่ใช้บ่อย

### Development

```bash
# Start development server
php artisan serve

# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# View logs
tail -f storage/logs/laravel.log
```

---

## 📁 โครงสร้างโปรเจกต์

```
Thaiprompt-Affiliate/
│
├── app/                      # Application logic
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/       # Admin controllers
│   │   │   ├── Frontend/    # Public controllers
│   │   │   └── Auth/        # Authentication
│   │   └── Middleware/
│   └── Models/              # Database models
│
├── resources/
│   ├── views/
│   │   ├── admin/           # Admin views
│   │   ├── frontend/        # Public views
│   │   └── layouts/         # Layout templates
│   ├── js/
│   │   ├── admin.js        # Admin JavaScript
│   │   └── frontend.js      # Frontend + GSAP
│   └── css/
│       └── app.css         # Tailwind CSS
│
├── routes/
│   ├── web.php             # Web routes
│   ├── admin.php           # Admin routes
│   └── api.php             # API routes
│
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/           # Data seeders
│
├── public/                 # Public assets
├── storage/               # Storage & logs
└── tests/                 # Tests
```

---

## 🎓 เรียนรู้เพิ่มเติม

### Documentation
- [README.md](README.md) - ภาพรวมโปรเจกต์
- [API Documentation](docs/API.md) - API endpoints (Coming soon)
- [Deployment Guide](docs/DEPLOYMENT.md) - วิธี deploy (Coming soon)

### ฟีเจอร์หลัก
- [Authentication System](docs/AUTH.md) (Coming soon)
- [Admin Dashboard](docs/ADMIN.md) (Coming soon)
- [Frontend Customization](docs/FRONTEND.md) (Coming soon)

---

## 🐛 แก้ไขปัญหา

### ปัญหาที่พบบ่อย

#### 1. Error: "Please provide a valid app key"
```bash
php artisan key:generate
php artisan config:clear
```

#### 2. Database connection error
```bash
# ตรวจสอบว่าไฟล์ database.sqlite มีหรือไม่
ls -la database/database.sqlite

# ถ้าไม่มี สร้างใหม่
touch database/database.sqlite
php artisan migrate
```

#### 3. Permission denied errors
```bash
chmod -R 775 storage bootstrap/cache
```

#### 4. Port already in use
```bash
# เปลี่ยน port
php artisan serve --port=8080

# หรือ kill process ที่ใช้ port
lsof -ti:8000 | xargs kill -9
```

---

## 💡 เคล็ดลับ

### การพัฒนา
1. ใช้ `php artisan serve` สำหรับ backend
2. ใช้ `npm run dev` ในอีก terminal สำหรับ hot reload frontend
3. ติดตั้ง Laravel Debugbar สำหรับ debugging

### Performance
1. Enable caching:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
2. Optimize composer:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

---

## 🎉 พร้อมแล้ว!

คุณพร้อมเริ่มต้นใช้งาน TP-Affiliate แล้ว!

หากมีคำถามหรือต้องการความช่วยเหลือ:
- 📖 อ่าน [README.md](README.md)
- 🐛 เปิด [Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- 💬 ติดต่อ: support@thaiprompt.com

**Happy coding! 🚀**
