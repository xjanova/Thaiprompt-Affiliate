# 📦 WordPress Plugin - TP-Affiliate License Manager

WordPress Plugin สำหรับ License Server อยู่ใน directory นี้

---

## 📂 ตำแหน่ง

```
Thaiprompt-Affiliate/
└── wordpress-plugin/          ← WordPress Plugin อยู่ที่นี่!
    ├── tp-affiliate-license-manager.php
    ├── api/
    ├── includes/
    ├── README.md
    └── INTEGRATION.md
```

---

## 🚀 วิธีติดตั้ง WordPress Plugin

### Option 1: Copy ทั้ง Directory

```bash
# Clone main repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# Copy ไป WordPress
cp -r wordpress-plugin /path/to/wordpress/wp-content/plugins/tp-affiliate-license-manager

# Activate plugin ใน WordPress Admin
# WP Admin → Plugins → Activate
```

### Option 2: Symlink (สำหรับ Development)

```bash
cd Thaiprompt-Affiliate

# สร้าง symlink
ln -s "$(pwd)/wordpress-plugin" /path/to/wordpress/wp-content/plugins/tp-affiliate-license-manager

# Activate plugin ใน WordPress Admin
```

### Option 3: Zip และ Upload

```bash
cd Thaiprompt-Affiliate/wordpress-plugin
zip -r tp-affiliate-license-manager.zip .

# Upload ผ่าน WordPress Admin
# WP Admin → Plugins → Add New → Upload Plugin
```

---

## 🔗 การเชื่อมต่อกับ Laravel

WordPress Plugin นี้จะให้บริการ REST API ให้ Laravel Client เรียกใช้

### Laravel Configuration

```env
# .env
LICENSE_API_URL=https://your-wordpress-site.com/wp-json/tp-license/v1
LICENSE_KEY=YOUR-LICENSE-KEY
LICENSE_DEVELOPER_MODE=false
```

### Laravel Usage

```bash
# Activate license
php artisan license:activate YOUR-LICENSE-KEY

# Check status
php artisan license:status

# Enable add-on
php artisan addon:enable mlm ADDON-LICENSE-KEY
```

---

## 📖 เอกสาร

- **README.md** - API Documentation
- **INTEGRATION.md** - คู่มือเชื่อมต่อกับ Laravel
- **HOW_TO_USE.md** - ไฟล์นี้

---

## 🏗️ โครงสร้าง Repository

```
Repository: https://github.com/xjanova/Thaiprompt-Affiliate
Branch: claude/add-versioning-system-011CUfZ26Kb3iopWSvDzXxku

Thaiprompt-Affiliate/
├── app/                        # Laravel Application
│   ├── Services/
│   │   ├── LicenseService.php  # เรียก WordPress API
│   │   └── AddonService.php    # จัดการ Add-ons
│   └── Console/Commands/
│       ├── License*.php        # License commands
│       └── Addon*.php          # Add-on commands
│
├── wordpress-plugin/           # WordPress Plugin (License Server)
│   ├── api/                    # REST API
│   ├── includes/               # Core classes
│   ├── README.md               # API Documentation
│   ├── INTEGRATION.md          # Integration guide
│   └── HOW_TO_USE.md           # This file
│
├── config/
│   └── license.php             # License configuration
│
└── COMMANDS.md                 # คู่มือคำสั่งทั้งหมด
```

---

## 💡 สำหรับ Claude / AI Assistants

เมื่อพัฒนา License System:

**อ่านไฟล์เหล่านี้:**
1. `wordpress-plugin/README.md` - API endpoints
2. `wordpress-plugin/INTEGRATION.md` - การเชื่อมต่อ
3. `config/license.php` - Laravel config
4. `app/Services/LicenseService.php` - Laravel implementation
5. `COMMANDS.md` - คำสั่งที่ใช้ได้

**ไฟล์สำคัญ:**
- WordPress API: `wordpress-plugin/api/class-api.php`
- Laravel Client: `app/Services/LicenseService.php`
- Configuration: `config/license.php`

---

**Developer:** Xman Enterprise Co., Ltd.  
**Copyright © 2025 All rights reserved.**
