# 🔧 คู่มือแก้ปัญหาโลโก้ไม่แสดง

## ปัญหา: อัพโหลดโลโก้แล้ว แต่โลโก้ไม่เปลี่ยน

### สาเหตุที่เป็นไปได้

1. **Storage Symlink ยังไม่ถูกสร้าง** (สาเหตุหลัก)
2. Cache ของ Browser
3. Permission ของโฟลเดอร์

---

## วิธีแก้ไข

### 1. ตรวจสอบและสร้าง Storage Symlink

#### วิธีที่ 1: ใช้ Artisan Command (แนะนำ)

```bash
php artisan storage:fix
```

คำสั่งนี้จะ:
- ตรวจสอบว่า `storage/app/public` มีอยู่หรือไม่ ถ้าไม่มีจะสร้างให้
- ตรวจสอบว่า `public/storage` symlink ถูกต้องหรือไม่
- แก้ไข symlink ที่ผิดพลาด
- แสดงข้อมูล storage และไฟล์ที่มีอยู่

#### วิธีที่ 2: ใช้ Laravel Standard Command

```bash
php artisan storage:link
```

#### วิธีที่ 3: สร้าง Symlink แบบ Manual (สำหรับกรณีที่ artisan ใช้ไม่ได้)

**Linux/Mac:**
```bash
ln -sf "$(pwd)/storage/app/public" "$(pwd)/public/storage"
```

**Windows (Command Prompt - Run as Administrator):**
```cmd
mklink /D "public\storage" "storage\app\public"
```

**Windows (PowerShell - Run as Administrator):**
```powershell
New-Item -ItemType SymbolicLink -Path "public\storage" -Target "storage\app\public"
```

### 2. ตรวจสอบว่า Symlink ทำงานถูกต้อง

```bash
# ตรวจสอบว่า symlink มีอยู่
ls -la public/storage

# ควรเห็นผลลัพธ์คล้ายๆ นี้:
# lrwxrwxrwx 1 user user 50 Nov 7 12:00 public/storage -> /path/to/project/storage/app/public
```

### 3. เคลียร์ Cache

#### เคลียร์ Laravel Cache:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

#### เคลียร์ Browser Cache:
- **Chrome/Edge**: กด `Ctrl + F5` (Windows) หรือ `Cmd + Shift + R` (Mac)
- **Firefox**: กด `Ctrl + Shift + R` (Windows) หรือ `Cmd + Shift + R` (Mac)
- **Safari**: กด `Cmd + Option + E` แล้วกด `Cmd + R`

### 4. ตรวจสอบ Permission (สำหรับ Linux/Mac)

```bash
# ตั้งค่า permission สำหรับ storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# ถ้าใช้ web server ให้เปลี่ยน owner
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache
```

---

## วิธีการอัพโหลดโลโก้ที่ถูกต้อง

1. ไปที่ **Admin Panel > ตั้งค่าระบบ > โลโก้ & Favicon**
2. เลือกไฟล์โลโก้ (รองรับ PNG, JPG, SVG - สูงสุด 2MB)
3. ดูตัวอย่างโลโก้ก่อนอัพโหลด
4. คลิกปุ่ม **"อัพโหลด"**
5. รอจนกว่าระบบจะบันทึกเสร็จ
6. ถ้าโลโก้ไม่เปลี่ยน กด `Ctrl + F5` เพื่อรีเฟรชแบบเคลียร์ cache

---

## ข้อมูลเทคนิค

### โครงสร้างไฟล์:

```
project/
├── storage/
│   └── app/
│       └── public/
│           └── branding/          # โลโก้ถูกบันทึกที่นี่
│               ├── logo-xxxxx.webp
│               └── favicon-xxxxx.webp
└── public/
    └── storage -> ../storage/app/public  # Symlink
```

### URL Path:
- **Storage Path**: `storage/app/public/branding/logo.webp`
- **Public URL**: `/storage/branding/logo.webp`
- **Full URL**: `https://yourdomain.com/storage/branding/logo.webp`

### Database:
- **Table**: `settings`
- **Key**: `logo`
- **Value**: `/storage/branding/filename.webp`
- **Type**: `string`
- **Group**: `branding`

---

## คำถามที่พบบ่อย (FAQ)

### Q: ทำไมต้องใช้ Symlink?
**A:** Laravel เก็บไฟล์ที่อัพโหลดใน `storage/app/public` เพื่อความปลอดภัย (ไม่อยู่ใน public root) แต่เพื่อให้เว็บเข้าถึงได้ เราต้องสร้าง symlink จาก `public/storage` ไปยัง `storage/app/public`

### Q: Symlink คืออะไร?
**A:** Symlink (Symbolic Link) คือไฟล์พิเศษที่ทำหน้าที่เป็น "ทางลัด" ชี้ไปยังโฟลเดอร์หรือไฟล์อื่น เหมือนกับ Shortcut ใน Windows

### Q: รูปภาพจะถูกแปลงเป็น WebP หรือไม่?
**A:** ใช่ ระบบจะแปลงรูป PNG, JPG เป็น WebP โดยอัตโนมัติเพื่อลดขนาดไฟล์ (ยกเว้น SVG จะเก็บแบบเดิม)

### Q: อัพโหลดแล้วหาไฟล์โลโก้ไม่เจอ?
**A:** ตรวจสอบที่ `storage/app/public/branding/` หรือรันคำสั่ง:
```bash
php artisan storage:fix
```
จะแสดงรายการไฟล์ทั้งหมดในโฟลเดอร์ branding

### Q: สามารถใช้โลโก้แบบ SVG ได้หรือไม่?
**A:** ได้ ระบบรองรับไฟล์ SVG และจะไม่แปลงเป็น WebP

### Q: ขนาดโลโก้ที่เหมาะสม?
**A:** แนะนำ:
- **ความกว้าง**: 200-400px
- **ความสูง**: 60-100px
- **ขนาดไฟล์**: ไม่เกิน 2MB

---

## การแก้ปัญหาขั้นสูง

### ตรวจสอบว่าโลโก้ถูกบันทึกในฐานข้อมูลหรือไม่:

```bash
php artisan tinker
>>> App\Models\Setting::get('logo')
```

### ตรวจสอบไฟล์ในโฟลเดอร์ branding:

```bash
ls -lah storage/app/public/branding/
```

### ทดสอบว่า Web Server เข้าถึง symlink ได้หรือไม่:

เปิดเบราว์เซอร์ไปที่:
```
http://yourdomain.com/storage/branding/
```

ถ้าเห็น 403 Forbidden = symlink ทำงาน แต่ไม่มีไฟล์
ถ้าเห็น 404 Not Found = symlink ไม่ทำงาน

---

## สรุป

ปัญหาโลโก้ไม่แสดงมักเกิดจาก **Storage Symlink ยังไม่ถูกสร้าง**

**วิธีแก้ที่เร็วที่สุด:**
```bash
php artisan storage:fix
```

หรือ
```bash
ln -sf "$(pwd)/storage/app/public" "$(pwd)/public/storage"
```

จากนั้นกด `Ctrl + F5` บนเบราว์เซอร์เพื่อเคลียร์ cache

---

📝 **หมายเหตุ**: ถ้ายังแก้ไม่ได้ ตรวจสอบ error log ที่ `storage/logs/laravel.log`
