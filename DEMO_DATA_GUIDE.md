# 🧹 คู่มือการจัดการข้อมูลทดสอบ (Demo Data Management Guide)

> **สำหรับลูกค้าที่ต้องการเตรียมระบบสำหรับใช้งานจริง (Production)**

เอกสารฉบับนี้อธิบายวิธีการจัดการข้อมูลทดสอบ (Demo Data) ในระบบ TP-Affiliate เพื่อให้คุณสามารถเตรียมระบบสำหรับใช้งานจริงได้อย่างถูกต้องและปลอดภัย

---

## 📋 สารบัญ

1. [ข้อมูลทดสอบคืออะไร?](#ข้อมูลทดสอบคืออะไร)
2. [ทำไมต้องลบข้อมูลทดสอบ?](#ทำไมต้องลบข้อมูลทดสอบ)
3. [วิธีการลบข้อมูลทดสอบ](#วิธีการลบข้อมูลทดสอบ)
   - [วิธีที่ 1: ใช้ Script (แนะนำสำหรับผู้เริ่มต้น)](#วิธีที่-1-ใช้-script-แนะนำสำหรับผู้เริ่มต้น)
   - [วิธีที่ 2: ใช้ Laravel Artisan Command](#วิธีที่-2-ใช้-laravel-artisan-command)
4. [ข้อมูลทดสอบที่สามารถลบได้](#ข้อมูลทดสอบที่สามารถลบได้)
5. [ข้อควรระวัง](#ข้อควรระวัง)
6. [คำถามที่พบบ่อย (FAQ)](#คำถามที่พบบ่อย-faq)

---

## ข้อมูลทดสอบคืออะไร?

**ข้อมูลทดสอบ (Demo Data)** คือข้อมูลตัวอย่างที่ถูกสร้างขึ้นมาโดยอัตโนมัติเมื่อติดตั้งระบบครั้งแรก เพื่อให้คุณสามารถทดลองใช้งานระบบได้ทันที โดยไม่ต้องสร้างข้อมูลเองทั้งหมด

### ข้อมูลทดสอบที่ระบบสร้างให้ประกอบด้วย:

| หมวดหมู่ | รายละเอียด | ตัวอย่าง |
|----------|-----------|---------|
| **👥 ผู้ใช้ทดสอบ** | บัญชีผู้ใช้งานตัวอย่าง | superadmin@thaiprompt.com, admin@thaiprompt.com, user1@example.com |
| **📄 หน้าเพจตัวอย่าง** | หน้าเว็บต่างๆ ที่เป็นเทมเพลต | About Us, FAQ, Contact, Terms of Service, Privacy Policy |
| **✅ KYC ตัวอย่าง** | ข้อมูล KYC verification ทดสอบ | KYC pending, approved, rejected |
| **📱 LINE Sessions** | LINE signup sessions ตัวอย่าง | LINE bot profiles, signup flows |
| **💰 ข้อมูลบัญชี** | รายการบัญชีตัวอย่าง | Journal entries, transactions ที่มีคำว่า "demo" |

---

## ทำไมต้องลบข้อมูลทดสอบ?

### เหตุผลสำคัญ:

1. **🔒 ความปลอดภัย (Security)**
   - ผู้ใช้ทดสอบมีรหัสผ่านที่ทุกคนรู้ (`password123`)
   - อาจถูกใช้เป็นช่องทางโจมตีระบบได้

2. **📊 ข้อมูลที่ถูกต้อง (Data Integrity)**
   - ข้อมูลทดสอบอาจสร้างความสับสนกับข้อมูลจริง
   - รายงานและสถิติจะถูกต้องมากขึ้นเมื่อไม่มีข้อมูลทดสอบ

3. **⚡ ประสิทธิภาพ (Performance)**
   - ลดขนาดฐานข้อมูล
   - เพิ่มความเร็วในการค้นหาและประมวลผล

4. **💼 ความเป็นมืออาชีพ (Professionalism)**
   - ระบบที่พร้อมใช้งานจริงไม่ควรมีข้อมูลทดสอบ
   - สร้างความเชื่อมั่นให้กับลูกค้า

---

## วิธีการลบข้อมูลทดสอบ

มี 2 วิธีหลักในการลบข้อมูลทดสอบ:

### วิธีที่ 1: ใช้ Script (แนะนำสำหรับผู้เริ่มต้น)

เราได้สร้าง script ที่ใช้งานง่ายสำหรับคุณแล้ว!

#### ขั้นตอน:

1. **เปิด Terminal** และเข้าไปที่โฟลเดอร์โปรเจค:
   ```bash
   cd /path/to/Thaiprompt-Affiliate
   ```

2. **รัน script**:
   ```bash
   ./clean-demo-data.sh
   ```

3. **เลือกประเภทข้อมูลที่ต้องการลบ**:
   ```
   ╔════════════════════════════════════════════════════════════════╗
   ║                                                                ║
   ║      🧹 ระบบลบข้อมูลทดสอบ (Demo Data Cleanup)                ║
   ║      เตรียมระบบสำหรับใช้งานจริง (Production-Ready)            ║
   ║                                                                ║
   ╚════════════════════════════════════════════════════════════════╝

   คุณสามารถเลือกได้ว่าจะลบข้อมูลประเภทไหน:

     1) ลบผู้ใช้ทดสอบ (Demo Users)
        ลบบัญชีผู้ใช้ที่มี email @example.com, @thaiprompt.com

     2) ลบหน้าเพจตัวอย่าง (Demo Pages)
        ลบหน้า About, FAQ, Contact, Terms, Privacy

     3) ลบข้อมูล KYC ตัวอย่าง (Demo KYC)
        ลบข้อมูล KYC verifications ทั้งหมด

     4) ลบ LINE Sessions ตัวอย่าง (Demo LINE)
        ลบ LINE signup sessions และ bot profiles

     5) ลบข้อมูลบัญชีตัวอย่าง (Demo Accounting)
        ลบรายการบัญชีที่มีคำว่า "demo" หรือ "ทดสอบ"

     6) ลบทั้งหมด (All Demo Data)
        ลบข้อมูลทดสอบทุกประเภท

     7) Interactive Mode (เลือกแบบ Interactive)
        ใช้เมนูแบบโต้ตอบของ Laravel Artisan

     0) ยกเลิก (Cancel)

   กรุณาเลือก (0-7) [7]:
   ```

4. **ยืนยันการดำเนินการ**:
   - Script จะถามคุณอีกครั้งก่อนลบข้อมูล
   - พิมพ์ `yes` เพื่อยืนยัน หรือ `no` เพื่อยกเลิก

#### ตัวอย่างการใช้งาน:

```bash
# ลบข้อมูลทดสอบทั้งหมด
./clean-demo-data.sh
# แล้วเลือก 6 (ลบทั้งหมด)

# ลบเฉพาะผู้ใช้ทดสอบ
./clean-demo-data.sh
# แล้วเลือก 1 (ลบผู้ใช้ทดสอบ)
```

---

### วิธีที่ 2: ใช้ Laravel Artisan Command

สำหรับผู้ใช้ที่คุ้นเคยกับ Laravel Artisan

#### คำสั่งพื้นฐาน:

```bash
# แสดงเมนูแบบ interactive
php artisan demo:reset

# ลบข้อมูลทดสอบทั้งหมด
php artisan demo:reset --all

# ลบเฉพาะผู้ใช้ทดสอบ
php artisan demo:reset --users

# ลบเฉพาะหน้าเพจตัวอย่าง
php artisan demo:reset --pages

# ลบเฉพาะ KYC ตัวอย่าง
php artisan demo:reset --kyc

# ลบเฉพาะ LINE sessions
php artisan demo:reset --line

# ลบเฉพาะข้อมูลบัญชีตัวอย่าง
php artisan demo:reset --accounting
```

#### Options พิเศษ:

```bash
# ลบข้อมูลทั้งหมดและ migrate:fresh (ระวัง! จะลบข้อมูลทั้งหมด)
php artisan demo:reset --fresh

# ข้ามการถามยืนยัน (ใช้ระวัง!)
php artisan demo:reset --all --force
```

#### ตัวอย่างการใช้งาน:

```bash
# 1. แสดงเมนู interactive
php artisan demo:reset

# 2. ลบผู้ใช้ทดสอบและหน้าเพจตัวอย่าง
php artisan demo:reset --users
php artisan demo:reset --pages

# 3. ลบข้อมูลทดสอบทั้งหมดแบบไม่ถามยืนยัน (production)
php artisan demo:reset --all --force
```

---

## ข้อมูลทดสอบที่สามารถลบได้

### 1. 👥 ผู้ใช้ทดสอบ (Demo Users)

**รายการที่จะถูกลบ:**

| Email | Role | Password |
|-------|------|----------|
| superadmin@thaiprompt.com | Super Admin | password123 |
| admin@thaiprompt.com | Admin | password123 |
| manager@thaiprompt.com | Manager | password123 |
| affiliate1-5@example.com | Affiliate | password123 |
| user1-10@example.com | User | password123 |

**ตารางที่เกี่ยวข้อง:**
- `users` - บัญชีผู้ใช้
- `affiliates` - ข้อมูล affiliate
- `commissions` - ค่าคอมมิชชั่น

**เงื่อนไข:** ลบเฉพาะผู้ใช้ที่มี email ลงท้ายด้วย `@example.com` หรือ `@thaiprompt.com`

⚠️ **หมายเหตุ:** บัญชี Super Admin ที่คุณสร้างตอนติดตั้งจะ**ไม่ถูกลบ**!

---

### 2. 📄 หน้าเพจตัวอย่าง (Demo Pages)

**รายการที่จะถูกลบ:**

- 📖 About Us (เกี่ยวกับเรา)
- ❓ FAQ (คำถามที่พบบ่อย)
- 📞 Contact (ติดต่อเรา)
- 📋 Terms of Service (ข้อกำหนดการใช้งาน)
- 🔒 Privacy Policy (นโยบายความเป็นส่วนตัว)
- 🍪 Cookie Policy (นโยบายคุ๊กกี้)

**ตารางที่เกี่ยวข้อง:**
- `pages`

**เงื่อนไข:** ลบเฉพาะหน้าที่มี type เป็น `about`, `faq`, `contact`, `terms`, `privacy`, `custom`

---

### 3. ✅ KYC ตัวอย่าง (Demo KYC)

**รายการที่จะถูกลบ:**

- KYC pending (รอตรวจสอบ)
- KYC approved (อนุมัติแล้ว)
- KYC rejected (ปฏิเสธ)

**ตารางที่เกี่ยวข้อง:**
- `kyc_verifications`

**เงื่อนไข:** ลบ KYC verification ทั้งหมดที่มี status เป็น `pending`, `approved`, `rejected`

---

### 4. 📱 LINE Sessions ตัวอย่าง (Demo LINE)

**รายการที่จะถูกลบ:**

- LINE signup sessions (new, in_progress, completed)
- LINE bot AI profiles (Affiliate Bot, Support Bot, Sales Bot)

**ตารางที่เกี่ยวข้อง:**
- `line_signup_sessions`
- `line_bot_ai_profiles`

**เงื่อนไข:** ลบทั้งหมด (ไม่มีเงื่อนไข)

---

### 5. 💰 ข้อมูลบัญชีตัวอย่าง (Demo Accounting)

**รายการที่จะถูกลบ:**

- Journal entries ที่มีคำว่า "demo" หรือ "ทดสอบ"
- Transactions ที่มีคำว่า "demo" หรือ "ทดสอบ"
- Accounts ที่มีคำว่า "demo" หรือ "ทดสอบ"

**ตารางที่เกี่ยวข้อง:**
- `accounting_journal_entries`
- `accounting_transactions`
- `accounting_accounts`

**เงื่อนไข:** ลบเฉพาะรายการที่มีคำว่า "demo" หรือ "ทดสอบ" ใน description

---

## ข้อควรระวัง

### ⚠️ สิ่งที่ควรรู้ก่อนลบข้อมูล:

1. **สำรองข้อมูลก่อน (Backup First)**
   ```bash
   # สำรองฐานข้อมูล
   mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **ตรวจสอบ Environment**
   - ถ้าอยู่ใน `APP_ENV=production` script จะถามยืนยันอีกครั้ง
   - แนะนำให้ทดสอบใน development ก่อน

3. **บัญชี Super Admin ที่คุณสร้าง = ปลอดภัย**
   - บัญชี Super Admin ที่สร้างตอนติดตั้ง (email ที่คุณกำหนดเอง) จะ**ไม่ถูกลบ**
   - ลบเฉพาะบัญชีที่มี email @example.com, @thaiprompt.com เท่านั้น

4. **ข้อมูลจำเป็นจะไม่ถูกลบ (Essential Data Safe)**

   ข้อมูลเหล่านี้จะ**ไม่ถูกลบ** แม้ใช้ `--all`:
   - Settings (การตั้งค่าระบบ)
   - MLM Plans & Packages (แผน MLM)
   - Ranks (ระบบยศ)
   - Payment Gateways (ช่องทางชำระเงิน)
   - AI Providers (ผู้ให้บริการ AI)
   - Email Templates (เทมเพลตอีเมล)
   - Product Categories (หมวดหมู่สินค้า)
   - Theme Presets (ธีม)

5. **Foreign Key Constraints**
   - Script จะปิด foreign key checks ชั่วคราวเพื่อความปลอดภัย
   - หลังจากลบเสร็จจะเปิดกลับอัตโนมัติ

---

## คำถามที่พบบ่อย (FAQ)

### ❓ Q1: ถ้าลบผิดพลาดจะทำอย่างไร?

**A:** ถ้าคุณสำรองข้อมูลไว้:
```bash
# Restore จาก backup
mysql -u root -p database_name < backup_20250118_143000.sql

# หรือรัน migrate:fresh และ seed ใหม่
php artisan migrate:fresh --seed
```

---

### ❓ Q2: ต้องลบข้อมูลทดสอบทุกครั้งหรือไม่?

**A:** ไม่จำเป็น! ขึ้นอยู่กับสถานการณ์:

| สถานการณ์ | คำแนะนำ |
|----------|---------|
| 🧪 Development/Testing | **ไม่ต้องลบ** - ให้ไว้สำหรับทดสอบ |
| 🚀 Staging/UAT | **ควรลบ** - ทดสอบกับข้อมูลจริง |
| 💼 Production | **ต้องลบ!** - ห้ามมีข้อมูลทดสอบใน production |

---

### ❓ Q3: ลบข้อมูลทดสอบแล้วจะมีผลกับการทำงานของระบบหรือไม่?

**A:** **ไม่มีผล!** ระบบจะทำงานได้ปกติ เพราะ:
- ข้อมูลจำเป็น (Settings, Configurations) ยังคงอยู่
- แค่ลบข้อมูลตัวอย่างที่ไม่จำเป็นออก
- ระบบพร้อมให้คุณสร้างข้อมูลจริงของคุณเอง

---

### ❓ Q4: ลบข้อมูลทดสอบแล้วสามารถสร้างกลับมาได้หรือไม่?

**A:** ได้! แต่จะเป็นข้อมูลใหม่ทั้งหมด:
```bash
# วิธีที่ 1: รัน seeders อีกครั้ง
php artisan db:seed

# วิธีที่ 2: migrate:fresh (ลบทุกอย่างและสร้างใหม่)
php artisan migrate:fresh --seed
```

⚠️ **คำเตือน:** วิธีที่ 2 จะ**ลบข้อมูลทั้งหมด**! ใช้ระวัง

---

### ❓ Q5: ตอนติดตั้งสามารถเลือกไม่ติดตั้งข้อมูลทดสอบได้หรือไม่?

**A:** **ได้!** ตอน `install.sh` จะถามว่า:
```bash
ต้องการติดตั้งข้อมูลทดสอบหรือไม่? (y/n) [y]:
```

- ตอบ `n` = ติดตั้งเฉพาะข้อมูลจำเป็น (ไม่มี demo data)
- ตอบ `y` = ติดตั้งพร้อมข้อมูลทดสอบ (แนะนำสำหรับการทดลองใช้)

---

### ❓ Q6: สามารถลบข้อมูลทดสอบบางส่วนได้หรือไม่?

**A:** **ได้!** คุณสามารถเลือกได้ว่าจะลบอะไร:

```bash
# ตัวอย่าง: ลบเฉพาะผู้ใช้ทดสอบ แต่เก็บหน้าเพจตัวอย่างไว้
php artisan demo:reset --users

# ตัวอย่าง: ลบทั้งผู้ใช้และหน้าเพจ
php artisan demo:reset --users
php artisan demo:reset --pages
```

---

### ❓ Q7: Production environment จะมีการป้องกันพิเศษหรือไม่?

**A:** **ใช่!** มีการป้องกัน 2 ชั้น:

1. **ตรวจสอบ APP_ENV**:
   ```
   🚫 คุณกำลังอยู่ใน Production environment!
      ใช้ --force ถ้าต้องการดำเนินการจริงๆ
   ```

2. **ถามยืนยันอีกครั้ง**:
   ```
   คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ? (yes/no):
   ```

---

## 📖 เอกสารเพิ่มเติม

- [Installation Guide](INSTALLATION.md) - คู่มือติดตั้งระบบ
- [Database Guidelines](.claude/DATABASE_GUIDELINES.md) - แนวทางการจัดการฐานข้อมูล
- [Seeder Guidelines](.claude/seeder-guidelines.md) - คู่มือ Seeders

---

## 🆘 ต้องการความช่วยเหลือ?

ถ้าคุณมีปัญหาหรือคำถาม:

1. **อ่านเอกสาร FAQ** ด้านบน
2. **ตรวจสอบ log files**: `storage/logs/laravel.log`
3. **ติดต่อทีมสนับสนุน**:
   - Email: support@thaiprompt.com
   - Line: @thaiprompt
   - GitHub Issues: [Report Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

---

## ✅ Checklist สำหรับการเตรียมระบบใช้งานจริง

ก่อน deploy ระบบไป production ให้ตรวจสอบดังนี้:

- [ ] **สำรองข้อมูล** (`mysqldump` หรือ backup script)
- [ ] **ลบข้อมูลทดสอบ** (`./clean-demo-data.sh` หรือ `php artisan demo:reset --all`)
- [ ] **ตรวจสอบ .env**:
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_URL` ถูกต้อง
  - [ ] Database credentials ถูกต้อง
- [ ] **ตรวจสอบ permissions**:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```
- [ ] **Clear และ rebuild caches**:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] **ตรวจสอบ storage link**:
  ```bash
  php artisan storage:link
  ```
- [ ] **ทดสอบระบบ**:
  - [ ] Login ด้วยบัญชี Super Admin ของคุณได้
  - [ ] สร้างผู้ใช้ใหม่ได้
  - [ ] ระบบทำงานปกติ

---

**🎉 ขอให้ใช้งาน TP-Affiliate อย่างมีความสุข!**

*เอกสารนี้อัปเดตล่าสุด: 18 มกราคม 2025*
