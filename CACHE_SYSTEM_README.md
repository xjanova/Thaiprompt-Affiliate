# ระบบจัดการแคช (Cache Management System)

> **เวอร์ชัน**: 1.0.0
> **อัพเดทล่าสุด**: 2025-11-21
> **รองรับ Laravel**: 11.x

---

## 📋 สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [Cache Drivers ที่รองรับ](#cache-drivers-ที่รองรับ)
3. [การติดตั้งและตั้งค่า](#การติดตั้งและตั้งค่า)
4. [การใช้งานผ่าน Admin Panel](#การใช้งานผ่าน-admin-panel)
5. [API Endpoints](#api-endpoints)
6. [คำแนะนำการเลือก Cache Driver](#คำแนะนำการเลือก-cache-driver)
7. [Troubleshooting](#troubleshooting)

---

## ภาพรวมระบบ

ระบบจัดการแคชของ TP-Affiliate ช่วยเพิ่มประสิทธิภาพเว็บไซต์โดยการเก็บข้อมูลที่ใช้บ่อยในหน่วยความจำ ลดการ query ฐานข้อมูล และเพิ่มความเร็วในการโหลดหน้าเว็บ

### ✨ คุณสมบัติหลัก

- ✅ รองรับ 4 Cache Drivers: **File**, **Database**, **Redis**, **Memcached**
- ✅ ระบบทดสอบการเชื่อมต่ออัตโนมัติ (Connection Testing)
- ✅ แสดงสถานะ Real-time ด้วยสีเขียว (ใช้ได้) / สีแดง (ใช้ไม่ได้)
- ✅ คำแนะนำการติดตั้งแต่ละ Driver แบบครบถ้วน
- ✅ เปลี่ยน Cache Driver ผ่าน UI โดยไม่ต้องแก้ไขโค้ด
- ✅ ล้างแคชแบบเลือกได้ (Config, Route, View, All)
- ✅ ปรับแต่งแคช (Optimize) ด้วยคลิกเดียว
- ✅ UI ใช้ V3 Coding Guidelines (Tailwind CSS + Alpine.js)
- ✅ รองรับ Dark Mode อัตโนมัติ

---

## Cache Drivers ที่รองรับ

### 1. 📁 File Cache

**คำอธิบาย**: เก็บแคชในไฟล์ที่ `storage/framework/cache/data`

**ข้อดี**:
- ✅ ไม่ต้องติดตั้งอะไรเพิ่ม (ใช้งานได้ทันที)
- ✅ ใช้งานง่าย
- ✅ เหมาะสำหรับเว็บไซต์ขนาดเล็ก-กลาง

**ข้อควรระวัง**:
- ⚠️ ช้ากว่า Redis/Memcached
- ⚠️ ใช้ disk I/O
- ⚠️ ไม่เหมาะกับ high traffic

**แนะนำ**: เหมาะสำหรับเว็บไซต์ทั่วไป Traffic ไม่สูงมาก

---

### 2. 🗄️ Database Cache

**คำอธิบาย**: เก็บแคชในฐานข้อมูล MySQL (ตาราง `cache`)

**ข้อดี**:
- ✅ ใช้ database ที่มีอยู่แล้ว
- ✅ จัดการง่าย
- ✅ Persistent cache

**ข้อควรระวัง**:
- ⚠️ ช้ากว่า File cache
- ⚠️ เพิ่มโหลด database
- ⚠️ ไม่เหมาะกับ high traffic

**แนะนำ**: เหมาะสำหรับระบบที่ต้องการ persistent cache แต่ไม่แนะนำ

---

### 3. ⚡ Redis Cache (แนะนำ)

**คำอธิบาย**: เก็บแคชใน Redis (in-memory database) - เร็วมาก

**ข้อดี**:
- 🚀 เร็วที่สุด (in-memory)
- ✅ รองรับ clustering
- ✅ รองรับ data structures มากมาย
- ✅ เหมาะสำหรับ high traffic

**ข้อควรระวัง**:
- ⚠️ ต้องติดตั้ง Redis server
- ⚠️ ใช้ RAM
- ⚠️ ต้องมีความรู้ในการดูแล

**แนะนำ**: ⭐ **แนะนำสำหรับ Production (high traffic)**

---

### 4. 🚀 Memcached (แนะนำ)

**คำอธิบาย**: เก็บแคชใน Memcached (in-memory) - เร็วมาก

**ข้อดี**:
- 🚀 เร็วมาก (in-memory)
- ✅ Simple and fast
- ✅ รองรับ distributed caching
- ✅ น้อย overhead กว่า Redis

**ข้อควรระวัง**:
- ⚠️ ต้องติดตั้ง Memcached server
- ⚠️ ใช้ RAM
- ⚠️ Feature น้อยกว่า Redis

**แนะนำ**: ⭐ **แนะนำสำหรับ Production (high traffic)**

---

## การติดตั้งและตั้งค่า

### ขั้นตอนที่ 1: เลือก Cache Driver

แก้ไขไฟล์ `.env`:

```env
# เลือก driver (file, database, redis, memcached)
CACHE_DRIVER=file
CACHE_PREFIX=tp_affiliate_cache
```

### ขั้นตอนที่ 2: ตั้งค่าตาม Driver ที่เลือก

#### 📁 File Cache (ค่าเริ่มต้น)

ไม่ต้องตั้งค่าอะไรเพิ่ม เพียงแค่:

```bash
# ตรวจสอบ permissions
chmod -R 775 storage/framework/cache
chown -R www-data:www-data storage
```

#### 🗄️ Database Cache

```bash
# 1. รัน migration
php artisan migrate

# 2. ตั้งค่าใน .env
CACHE_DRIVER=database
```

#### ⚡ Redis Cache

```bash
# 1. ติดตั้ง Redis Server (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# 2. ติดตั้ง PHP Redis extension
sudo apt-get install php-redis
sudo systemctl restart php8.1-fpm  # หรือ php-fpm

# 3. ตั้งค่าใน .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1

# 4. ทดสอบ Redis
redis-cli ping
# ควรได้ PONG
```

#### 🚀 Memcached

```bash
# 1. ติดตั้ง Memcached Server (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install memcached
sudo systemctl enable memcached
sudo systemctl start memcached

# 2. ติดตั้ง PHP Memcached extension
sudo apt-get install php-memcached
sudo systemctl restart php8.1-fpm  # หรือ php-fpm

# 3. ตั้งค่าใน .env
CACHE_DRIVER=memcached
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
MEMCACHED_USERNAME=null
MEMCACHED_PASSWORD=null

# 4. ทดสอบ Memcached
echo "stats" | nc 127.0.0.1 11211
# ควรเห็นสถิติต่างๆ
```

### ขั้นตอนที่ 3: Clear Config Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## การใช้งานผ่าน Admin Panel

### เข้าถึงหน้า Cache Settings

1. เข้าสู่ระบบ Admin Panel
2. ไปที่เมนู **"ตั้งค่า"** > **"ระบบแคช"**
3. หรือเข้าผ่าน URL: `https://yourdomain.com/admin/cache`

### ฟีเจอร์ใน UI

#### 1. สถิติแคชปัจจุบัน

- แสดง Cache Driver ที่ใช้งานอยู่
- แสดงขนาดแคช / RAM ที่ใช้
- แสดงจำนวน entries (สำหรับ Database/Memcached)

#### 2. เลือก Cache Driver

- แสดงสถานะของแต่ละ driver (สีเขียว = ใช้ได้ | สีแดง = ใช้ไม่ได้)
- แสดงข้อดี/ข้อเสีย ของแต่ละ driver
- ปุ่ม "ใช้ Driver นี้" (เปลี่ยน driver)
- ปุ่ม "ทดสอบการเชื่อมต่อ"
- ปุ่ม "คู่มือติดตั้ง"

#### 3. เครื่องมือจัดการแคช

- **ล้าง Config Cache**: ล้างแคชของไฟล์ config
- **ล้าง Route Cache**: ล้างแคชของ routes
- **ล้าง View Cache**: ล้างแคชของ Blade views
- **ล้างทั้งหมด**: ล้างแคชทุกประเภท
- **ปรับแต่งแคช**: รัน `config:cache`, `route:cache`, `view:cache`

---

## API Endpoints

### GET `/admin/cache`

แสดงหน้า Cache Settings

### GET `/admin/cache/status`

ดึงสถานะของ cache drivers ทั้งหมด

**Response**:
```json
{
  "success": true,
  "data": {
    "file": {
      "name": "File Cache",
      "status": true,
      "message": "✅ File Cache ใช้งานได้ปกติ",
      "is_current": true
    },
    "redis": {
      "name": "Redis Cache",
      "status": false,
      "message": "❌ ไม่สามารถเชื่อมต่อ Redis",
      "is_current": false
    }
  }
}
```

### POST `/admin/cache/test`

ทดสอบการเชื่อมต่อ cache driver

**Request**:
```json
{
  "driver": "redis"
}
```

**Response**:
```json
{
  "status": true,
  "message": "✅ Redis Cache ใช้งานได้ปกติ",
  "details": {
    "version": "7.0.12",
    "uptime_days": "5 วัน",
    "used_memory": "2.5M"
  }
}
```

### POST `/admin/cache/change-driver`

เปลี่ยน cache driver

**Request**:
```json
{
  "driver": "redis"
}
```

### POST `/admin/cache/clear-specific`

ล้างแคชเฉพาะส่วน

**Request**:
```json
{
  "type": "config"  // config, route, view, all
}
```

### POST `/admin/cache/optimize`

ปรับแต่งแคช (cache config, routes, views)

---

## คำแนะนำการเลือก Cache Driver

### 🏢 Production Environment (Traffic สูง)

**แนะนำ**: Redis หรือ Memcached

- ใช้ Redis หากต้องการ features มากกว่า (data structures, pub/sub, etc.)
- ใช้ Memcached หากต้องการความเร็วสูงสุดและ simple

**ตั้งค่า**:
```env
CACHE_DRIVER=redis
```

### 🧪 Development Environment

**แนะนำ**: File Cache

- ใช้งานง่าย ไม่ต้องติดตั้งอะไรเพิ่ม
- เหมาะสำหรับการพัฒนา

**ตั้งค่า**:
```env
CACHE_DRIVER=file
```

### 🏪 Small-Medium Website (Traffic ปานกลาง)

**แนะนำ**: File Cache หรือ Redis

- เริ่มต้นด้วย File Cache
- เปลี่ยนเป็น Redis เมื่อ traffic เพิ่มขึ้น

### 📊 ตารางเปรียบเทียบประสิทธิภาพ

| Driver | ความเร็ว | RAM Usage | Disk I/O | Setup Difficulty |
|--------|----------|-----------|----------|------------------|
| File | ⭐⭐ | ต่ำ | สูง | ง่าย |
| Database | ⭐ | ต่ำ | สูง | ง่าย |
| Redis | ⭐⭐⭐⭐⭐ | สูง | ต่ำ | ปานกลาง |
| Memcached | ⭐⭐⭐⭐⭐ | สูง | ต่ำ | ปานกลาง |

---

## Troubleshooting

### ❌ Redis ไม่สามารถเชื่อมต่อได้

**สาเหตุ**:
1. Redis server ไม่ได้รัน
2. PHP Redis extension ไม่ได้ติดตั้ง
3. การตั้งค่า connection ผิด

**แก้ไข**:
```bash
# เช็ค Redis service
sudo systemctl status redis-server

# รีสตาร์ท Redis
sudo systemctl restart redis-server

# เช็ค extension
php -m | grep redis

# ติดตั้ง extension (ถ้ายังไม่มี)
sudo apt-get install php-redis
sudo systemctl restart php8.1-fpm
```

### ❌ Memcached ไม่สามารถเชื่อมต่อได้

**สาเหตุ**:
1. Memcached server ไม่ได้รัน
2. PHP Memcached extension ไม่ได้ติดตั้ง

**แก้ไข**:
```bash
# เช็ค Memcached service
sudo systemctl status memcached

# รีสตาร์ท Memcached
sudo systemctl restart memcached

# เช็ค extension
php -m | grep memcached

# ติดตั้ง extension (ถ้ายังไม่มี)
sudo apt-get install php-memcached
sudo systemctl restart php8.1-fpm
```

### ❌ File Cache ใช้งานไม่ได้

**สาเหตุ**: Directory `storage/framework/cache/data` ไม่มี write permission

**แก้ไข**:
```bash
chmod -R 775 storage/framework/cache
chown -R www-data:www-data storage
```

### ❌ Database Cache ใช้งานไม่ได้

**สาเหตุ**: ตาราง `cache` ยังไม่ได้สร้าง

**แก้ไข**:
```bash
php artisan migrate
```

---

## 🔧 การบำรุงรักษา

### ล้างแคชเป็นประจำ

```bash
# ล้างแคชทั้งหมด
php artisan cache:clear

# ล้าง config cache
php artisan config:clear

# ล้าง route cache
php artisan route:clear

# ล้าง view cache
php artisan view:clear
```

### ปรับแต่งแคชสำหรับ Production

```bash
# Cache config, routes, views เพื่อเพิ่มความเร็ว
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 เอกสารเพิ่มเติม

- [Laravel Cache Documentation](https://laravel.com/docs/11.x/cache)
- [Redis Documentation](https://redis.io/documentation)
- [Memcached Wiki](https://github.com/memcached/memcached/wiki)

---

## 📝 การอัพเดท

### Version 1.0.0 (2025-11-21)

- ✅ เพิ่มระบบจัดการแคชแบบครบวงจร
- ✅ รองรับ 4 Cache Drivers: File, Database, Redis, Memcached
- ✅ UI ใหม่ทั้งหมด (V3 Coding Guidelines)
- ✅ ระบบทดสอบการเชื่อมต่ออัตโนมัติ
- ✅ คำแนะนำการติดตั้งแบบละเอียด
- ✅ แสดงสถานะด้วยสี (เขียว/แดง)

---

## 💡 Tips & Best Practices

1. **Production**: ใช้ Redis หรือ Memcached เสมอ
2. **Development**: ใช้ File Cache เพื่อความสะดวก
3. **ล้างแคช**: ล้างแคชหลังจาก deploy หรืออัพเดทโค้ด
4. **Monitor**: ติดตาม RAM usage เมื่อใช้ Redis/Memcached
5. **Backup**: Redis รองรับ persistence, Memcached ไม่รองรับ
6. **Security**: ตั้งรหัสผ่านสำหรับ Redis ใน production

---

**Developed with ❤️ by TP-Affiliate Team**

**Support**: ติดต่อทีมพัฒนาผ่าน GitHub Issues
