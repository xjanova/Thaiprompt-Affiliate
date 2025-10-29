# 🚀 Getting Started - TP-Affiliate

## เริ่มต้นใช้งานภายใน 2 นาที!

---

## 📋 สิ่งที่ต้องเตรียม

เลือกวิธีใดวิธีหนึ่ง:

### ตัวเลือกที่ 1: Docker (แนะนำ - ง่ายที่สุด)
- ✅ Docker Desktop
- ✅ แค่นี้! ไม่ต้องติดตั้งอะไรเพิ่ม

### ตัวเลือกที่ 2: Manual Installation
- ✅ PHP 8.1 หรือสูงกว่า
- ✅ Composer
- ✅ Node.js & NPM (optional - สำหรับ frontend)

---

## ⚡ ติดตั้งอย่างรวดเร็ว

### 🐳 Docker Installation (แนะนำ)

```bash
# 1. Clone project
git clone https://github.com/xjanova/TP-Affiliate.git
cd TP-Affiliate

# 2. รัน installation script
./install.sh

# 3. เสร็จแล้ว! เปิด browser
http://localhost
```

### 🔧 Manual Installation

```bash
# 1. Clone project
git clone https://github.com/xjanova/TP-Affiliate.git
cd TP-Affiliate

# 2. รัน installation script
./install.sh

# หรือทำทีละขั้นตอน:

# 2a. Install dependencies
composer install
npm install

# 2b. Setup environment
cp .env.example .env
php artisan key:generate

# 2c. Create database
mkdir -p database
touch database/database.sqlite

# 2d. Run migrations
php artisan migrate

# 2e. Build frontend (optional)
npm run build

# 3. Start server
php artisan serve

# 4. เปิด browser
http://localhost:8000
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
http://localhost/admin
```

คุณสมบัติ:
- 📊 Real-time Statistics & Charts
- 👥 User Management
- 💰 Commission Tracking
- 📈 Performance Analytics
- ⚙️ System Settings

### Frontend (Public Site)
```
http://localhost
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

# Watch for file changes (frontend)
npm run dev

# Clear all caches
php artisan optimize:clear

# Create new admin user
php artisan make:admin

# View logs
tail -f storage/logs/laravel.log
```

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Execute command in container
docker-compose exec app php artisan [command]

# Rebuild containers
docker-compose build --no-cache
```

---

## 📁 โครงสร้างโปรเจกต์

```
TP-Affiliate/
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
- 🐛 เปิด [Issue](https://github.com/xjanova/TP-Affiliate/issues)
- 💬 ติดต่อ: support@thaiprompt.com

**Happy coding! 🚀**
