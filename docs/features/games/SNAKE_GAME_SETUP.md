# 🐍 Snake.io Game Setup Guide

## 🔴 ปัญหาที่พบ: "Unexpected token '<', "<!DOCTYPE "... is not valid JSON"

### สาเหตุ:
API endpoint คืนค่า HTML error page แทน JSON เพราะ **MySQL service ไม่ได้รัน**

```
Error: SQLSTATE[HY000] [2002] Connection refused
```

---

## ✅ วิธีแก้ปัญหา

### ขั้นตอนที่ 1: เริ่ม MySQL Service

**Linux/Ubuntu:**
```bash
sudo systemctl start mysql
# หรือ
sudo service mysql start

# ตรวจสอบสถานะ
sudo systemctl status mysql
```

**MacOS:**
```bash
brew services start mysql
# หรือ
mysql.server start

# ตรวจสอบ
brew services list | grep mysql
```

**Windows (XAMPP):**
1. เปิด **XAMPP Control Panel**
2. กดปุ่ม **Start** ที่ MySQL

**Windows (WAMP):**
1. เปิด **WAMP Server**
2. คลิกขวาที่ไอคอน WAMP → **MySQL** → **Service** → **Start**

---

### ขั้นตอนที่ 2: สร้าง Database

```bash
# เข้า MySQL
mysql -u root -p

# สร้าง database
CREATE DATABASE IF NOT EXISTS thaiprompt_affiliate
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

# ออกจาก MySQL
exit;
```

---

### ขั้นตอนที่ 3: ตั้งค่า `.env`

สร้างหรือแก้ไขไฟล์ `.env`:

```env
APP_NAME="TP-Affiliate"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password_here

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=1
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

### ขั้นตอนที่ 4: รัน Migrations

```bash
# สร้างตารางในฐานข้อมูล
php artisan migrate

# (ถ้าต้องการ) เพิ่มข้อมูลตัวอย่าง
php artisan db:seed
```

---

### ขั้นตอนที่ 5: เริ่มระบบทั้งหมด

เปิด **4 Terminal** แยกกัน:

**Terminal 1: Laravel Application**
```bash
php artisan serve
# → http://localhost:8000
```

**Terminal 2: WebSocket Server (Reverb)**
```bash
php artisan reverb:start
# → WebSocket running on port 8080
```

**Terminal 3: Queue Worker (Item Spawning)**
```bash
php artisan queue:work
# → Processing background jobs
```

**Terminal 4: Task Scheduler**
```bash
php artisan schedule:work
# → Running scheduled tasks (item spawning every minute)
```

---

## 🎮 ทดสอบเกม

1. เปิด browser: **http://localhost:8000/games/snake-io**
2. กด F12 เปิด Console
3. ควรเห็นข้อความ:
   ```
   [Multiplayer] เข้าร่วมห้อง: ROOM-XXXXX Player ID: 1
   [Multiplayer] เชื่อมต่อ WebSocket: snake-room.1
   ```

4. หาก**ไม่มี error** → ระบบทำงานปกติ! 🎉

---

## 🔍 การตรวจสอบปัญหา

### ตรวจสอบ MySQL:
```bash
# เช็คว่า MySQL รันหรือไม่
sudo systemctl status mysql

# เช็คว่าเชื่อมต่อได้หรือไม่
php artisan db:show
```

### ตรวจสอบ Migrations:
```bash
# ดูว่า migrations รันครบหรือไม่
php artisan migrate:status

# ถ้ามี pending migrations
php artisan migrate
```

### ตรวจสอบ API Endpoint:
```bash
# ทดสอบ API โดยตรง
curl -X POST http://localhost:8000/api/games/snake-io/join \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"player_name":"TestPlayer","skin_slug":"classic"}'

# ถ้าได้ JSON response → API ทำงานถูกต้อง
# ถ้าได้ HTML <!DOCTYPE...> → ยังมีปัญหา
```

---

## 🎯 ฟีเจอร์ที่พร้อมใช้งาน

- ✅ **WebSocket Real-time** - ผู้เล่นเห็นกันแบบ real-time
- ✅ **30 Bots Auto-reduce** - บอทลดอัตโนมัติเมื่อมีผู้เล่นจริง
- ✅ **Server-side Item Spawning** - ไอเทมเกิดจาก server ทุก 1 นาที
- ✅ **Anti-cheat System** - ตรวจสอบการโกง 5 ประเภท
- ✅ **3-Color Customization** - เลือกสี 3 สีจากพาเลต 256 สี
- ✅ **Zoom Powerup** - กล้องเริ่มใกล้ ซูมออกเมื่อเก็บไอเทม 🔍
- ✅ **Wallet Integration** - บันทึกคะแนน (ใช้ 1 แต้ม)
- ✅ **Version Display** - แสดงเวอร์ชั่นที่มุมล่างซ้าย

---

## 📞 หากยังมีปัญหา

1. ตรวจสอบ **MySQL service รันหรือไม่**: `sudo systemctl status mysql`
2. ตรวจสอบ **database มีหรือไม่**: `mysql -u root -p` → `SHOW DATABASES;`
3. ตรวจสอบ **migrations รันหรือไม่**: `php artisan migrate:status`
4. ตรวจสอบ **error logs**: `tail -f storage/logs/laravel.log`
5. ตรวจสอบ **browser console** มี error อะไร

---

**เวอร์ชั่น:** Snake.io v2.5.0-ws
**Features:** WebSocket + Anti-cheat + Server-side Spawning
**Last Updated:** 2025-11-14
