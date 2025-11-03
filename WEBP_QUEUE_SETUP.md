# คำแนะนำการ Setup Queue สำหรับ WebP Conversion

## ⚠️ สำคัญ: ต้องเปิด Queue Worker

ระบบแปลง WebP ใช้ Laravel Queue เพื่อประมวลผลแบบ background และแสดง progress แบบ real-time ดังนั้น **ต้องรัน queue worker** ก่อนใช้งาน

---

## 🚀 วิธีการ Setup

### Option 1: Development (Local)

#### ใช้ Sync Driver (ไม่แนะนำสำหรับ production)

แก้ไขไฟล์ `.env`:
```env
QUEUE_CONNECTION=sync
```

**หมายเหตุ:** Sync จะทำงานแบบ synchronous ไม่มี progress bar แบบ real-time

---

#### ใช้ Database Driver (แนะนำสำหรับ local)

1. แก้ไข `.env`:
```env
QUEUE_CONNECTION=database
```

2. สร้าง jobs table:
```bash
php artisan queue:table
php artisan migrate
```

3. รัน queue worker:
```bash
php artisan queue:work --tries=1 --timeout=3600
```

หรือใช้ `--daemon` mode:
```bash
php artisan queue:work --daemon --tries=1 --timeout=3600
```

---

### Option 2: Production (Server)

#### ใช้ Redis (แนะนำ - เร็วที่สุด)

1. ติดตั้ง Redis:
```bash
# Ubuntu/Debian
sudo apt-get install redis-server

# macOS
brew install redis
```

2. ติดตั้ง predis package:
```bash
composer require predis/predis
```

3. แก้ไข `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

4. รัน queue worker:
```bash
php artisan queue:work redis --tries=1 --timeout=3600
```

---

### Option 3: Supervisor (Production - Auto Restart)

สำหรับ production server ควรใช้ Supervisor เพื่อให้ queue worker ทำงานตลอดเวลาและ restart อัตโนมัติเมื่อล้ม

#### 1. ติดตั้ง Supervisor

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

#### 2. สร้างไฟล์ config

สร้างไฟล์ `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=1 --max-time=3600 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

**ปรับค่าตามระบบของคุณ:**
- `/path/to/your/project` - path โปรเจค Laravel
- `user=www-data` - user ที่รัน (เช่น `nginx`, `apache`, `ubuntu`)
- `numprocs=2` - จำนวน worker (ขึ้นอยู่กับ CPU cores)

#### 3. รัน Supervisor

```bash
# อ่าน config ใหม่
sudo supervisorctl reread

# อัพเดต supervisor
sudo supervisorctl update

# เริ่ม worker
sudo supervisorctl start laravel-worker:*

# ตรวจสอบสถานะ
sudo supervisorctl status laravel-worker:*
```

#### 4. คำสั่งจัดการ Supervisor

```bash
# Restart workers (เมื่อ deploy code ใหม่)
sudo supervisorctl restart laravel-worker:*

# Stop workers
sudo supervisorctl stop laravel-worker:*

# ดู log
sudo supervisorctl tail -f laravel-worker:00 stdout
```

---

## 📋 Cache Driver Setup

ระบบใช้ Cache เก็บ progress data ควรใช้:

### Development:
```env
CACHE_DRIVER=file
```

### Production:
```env
CACHE_DRIVER=redis
```

---

## ⚡ Queue Configuration Best Practices

### สำหรับ WebP Conversion

เพิ่มใน `config/queue.php`:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 3700, // 1+ hour
        'block_for' => null,
    ],
],
```

### Failed Jobs Table

สร้าง table เก็บ failed jobs:

```bash
php artisan queue:failed-table
php artisan migrate
```

ดู failed jobs:
```bash
php artisan queue:failed
```

Retry failed job:
```bash
php artisan queue:retry <job-id>
```

Retry all:
```bash
php artisan queue:retry all
```

---

## 🔧 Troubleshooting

### Queue Worker ไม่ทำงาน

```bash
# ตรวจสอบว่า worker กำลังรันอยู่หรือไม่
ps aux | grep "queue:work"

# ดู log
tail -f storage/logs/laravel.log

# ลอง restart worker
sudo supervisorctl restart laravel-worker:*
```

### Progress Bar ไม่เคลื่อนไหว

1. ตรวจสอบว่า Queue Worker รันอยู่
2. ตรวจสอบ `CACHE_DRIVER` ใน `.env`
3. ลองเคลียร์ cache:
```bash
php artisan cache:clear
```

### Job Timeout

หากมีรูปภาพจำนวนมาก อาจต้องเพิ่ม timeout:

```bash
# เพิ่ม timeout เป็น 2 ชั่วโมง
php artisan queue:work --timeout=7200
```

หรือใน Supervisor config:
```ini
stopwaitsecs=7200
```

---

## 🚀 Deployment (Production)

### ขั้นตอนการ Deploy

1. **Pull code ใหม่:**
```bash
git pull origin main
```

2. **อัพเดต dependencies:**
```bash
composer install --no-dev --optimize-autoloader
```

3. **รัน migrations:**
```bash
php artisan migrate --force
```

4. **เคลียร์ cache:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. **Restart queue workers:**
```bash
sudo supervisorctl restart laravel-worker:*
```

6. **ตรวจสอบสถานะ:**
```bash
sudo supervisorctl status laravel-worker:*
```

---

## 📊 Monitoring

### ตรวจสอบ Queue

```bash
# ดูจำนวน jobs ใน queue
php artisan queue:monitor redis:default

# ดู failed jobs
php artisan queue:failed
```

### ตรวจสอบ Redis

```bash
# เข้า redis-cli
redis-cli

# ดู queues
KEYS queues:*

# ดูจำนวน jobs
LLEN queues:default
```

---

## 💡 Tips

1. **ใช้ `--sleep=3`** เพื่อลด CPU usage เมื่อไม่มี jobs
2. **ตั้ง `numprocs` ตาม CPU cores** (แนะนำ 2-4 workers)
3. **เช็ค logs ใน `/storage/logs/worker.log`** เป็นประจำ
4. **Restart workers หลัง deploy** เสมอ
5. **ใช้ Horizon** สำหรับ monitoring ที่ดีกว่า (optional)

---

## 🎉 เสร็จสิ้น!

หลังจาก setup แล้ว ระบบแปลง WebP จะทำงานแบบ background พร้อม progress bar แบบ real-time! 🚀
