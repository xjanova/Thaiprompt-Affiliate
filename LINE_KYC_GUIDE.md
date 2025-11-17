# LINE KYC System - คู่มือการใช้งาน

> **ระบบยืนยันตัวตน (KYC) ผ่าน LINE Messaging API พร้อม Google Cloud Vision OCR**
>
> Version: 1.0.0 | Created: 2025-11-17 | Framework: Laravel 11 + LINE API + Google Cloud Vision

---

## 🎯 ภาพรวมระบบ

ระบบ KYC ผ่าน LINE ช่วยให้ผู้ใช้สามารถยืนยันตัวตนได้สะดวกและรวดเร็วผ่าน LINE Chat โดย:

1. **ส่งรูปบัตรประชาชน** → OCR อ่านข้อมูลอัตโนมัติ
2. **ส่งรูปถ่ายตัวเอง (Selfie)** → ยืนยันตัวบุคคล
3. **รอ Admin ตรวจสอบ** → 1-3 วันทำการ
4. **รับการแจ้งเตือนผล** → ทาง LINE Chat

---

## ✨ Features หลัก

### 1. Google Cloud Vision OCR
- ✅ อ่านข้อมูลจากบัตรประชาชนไทยได้อัตโนมัติ
- ✅ รองรับบัตรประชาชนและใบขับขี่
- ✅ แยก extract ข้อมูล: เลขบัตร, ชื่อ-นามสกุล, วันเกิด, วันหมดอายุ
- ✅ Thai date parsing (พ.ศ. → ค.ศ.)
- ✅ Image preprocessing สำหรับความแม่นยำสูง

### 2. LINE Integration
- ✅ รับรูปภาพจาก LINE Messaging API
- ✅ ส่ง notifications แบบ real-time
- ✅ รองรับ commands: 'KYC', 'ยืนยันตัวตน', 'verify'
- ✅ Auto-detect image type (บัตรประชาชน หรือ Selfie)

### 3. Admin Dashboard
- ✅ ตรวจสอบและอนุมัติ KYC
- ✅ ดูข้อมูล OCR ที่ extracted
- ✅ Auto-fill profile จาก OCR data
- ✅ Reject พร้อมระบุเหตุผล

### 4. User Experience
- ✅ คำแนะนำชัดเจนภาษาไทย
- ✅ Error messages แบบเป็นมิตร
- ✅ Progress tracking ทุกขั้นตอน
- ✅ Notification ผลการตรวจสอบ

---

## 📋 ขั้นตอนการใช้งาน (สำหรับ Users)

### Step 1: เริ่มต้น KYC

พิมพ์คำสั่งใดคำสั่งหนึ่งใน LINE Chat:
- `KYC`
- `ยืนยันตัวตน`
- `verify`

**Response:**
```
🔐 ยืนยันตัวตน (KYC)

เพื่อความปลอดภัยและเพิ่มความน่าเชื่อถือของบัญชี กรุณาทำตามขั้นตอนต่อไปนี้:

📋 ขั้นตอนการยืนยันตัวตน:

1️⃣ ส่งรูปบัตรประชาชน (ด้านหน้า)
   • ถ่ายรูปให้ชัดเจน
   • เห็นข้อความทั้งหมด
   • ไม่มีแสงสะท้อน

2️⃣ ส่งรูปถ่ายตัวเอง (Selfie)
   • ถือบัตรประชาชนข้างใบหน้า
   • เห็นใบหน้าชัดเจน
   • แสงสว่างเพียงพอ

⏱️ ระยะเวลาตรวจสอบ: 1-3 วันทำการ

กรุณาส่งรูปบัตรประชาชนมาเลยครับ 👇
```

### Step 2: ส่งรูปบัตรประชาชน

ถ่ายรูปบัตรประชาชน (ด้านหน้า) และส่งผ่าน LINE Chat

**Tips สำหรับรูปที่ดี:**
- ✅ แสงสว่างเพียงพอ
- ✅ ไม่มีแสงสะท้อน/กาแล็ก
- ✅ เห็นข้อความทั้งหมดชัดเจน
- ✅ ไม่เอียง/บิดเบี้ยว
- ✅ ไม่มีนิ้วบัง

**Response เมื่อสำเร็จ:**
```
✅ รับรูปบัตรประชาชนเรียบร้อยแล้ว!

📝 ข้อมูลที่อ่านได้:
🆔 เลขบัตร: 1234567890123
👤 ชื่อ (ไทย): นายทดสอบ ระบบ
👤 ชื่อ (อังกฤษ): Mr. Thadsob Rabob
🎂 วันเกิด: 1990-01-01
📅 วันออกบัตร: 2020-01-01
📅 วันหมดอายุ: 2030-01-01

📸 ขั้นตอนต่อไป:
กรุณาส่งรูปถ่ายตัวเอง (Selfie) ถือบัตรประชาชน
```

**Response เมื่อ OCR อ่านไม่ได้:**
```
❌ ไม่สามารถอ่านข้อมูลจากบัตรประชาชนได้

สาเหตุ: รูปภาพมืดเกินไป ไม่สามารถอ่านข้อความได้

คำแนะนำ: กรุณาถ่ายรูปใหม่ในที่แสงสว่างเพียงพอ หรือเปิดแฟลช
```

### Step 3: ส่งรูปถ่ายตัวเอง (Selfie)

ถ่ายรูปตัวเองพร้อมถือบัตรประชาชนข้างใบหน้า

**Tips สำหรับ Selfie ที่ดี:**
- ✅ เห็นใบหน้าชัดเจน
- ✅ ถือบัตรประชาชนข้างหน้า
- ✅ เห็นข้อความบนบัตรชัดเจน
- ✅ ไม่ใส่แว่น/หมวก (ถ้าเป็นไปได้)
- ✅ แสงสว่างเพียงพอ

**Response เมื่อสำเร็จ:**
```
🎉 ส่ง KYC เรียบร้อยแล้ว!

✅ รูปบัตรประชาชน: อัพโหลดแล้ว
✅ รูปถ่ายตัวเอง: อัพโหลดแล้ว

⏳ สถานะ: รอการตรวจสอบ

แอดมินจะตรวจสอบภายใน 1-3 วันทำการ
เราจะแจ้งเตือนคุณเมื่อมีผลการตรวจสอบ
```

### Step 4: รอผลการตรวจสอบ

Admin จะตรวจสอบและแจ้งผลทาง LINE Chat ภายใน 1-3 วันทำการ

**Response เมื่อผ่าน:**
```
🎉 ยินดีด้วย! KYC ผ่านการตรวจสอบ

✅ บัญชีของคุณได้รับการยืนยันตัวตนแล้ว
✨ ตอนนี้คุณสามารถใช้งานฟีเจอร์ครบทุกอย่างได้แล้ว
```

**Response เมื่อไม่ผ่าน:**
```
❌ KYC ไม่ผ่านการตรวจสอบ

สาเหตุ: รูปบัตรประชาชนไม่ชัดเจน กรุณาถ่ายใหม่

📝 กรุณาทำ KYC ใหม่อีกครั้ง
พิมพ์ 'KYC' เพื่อเริ่มต้นใหม่
```

---

## 🔧 ขั้นตอนการใช้งาน (สำหรับ Admin)

### 1. เข้าสู่หน้า KYC Verification

1. เข้าสู่ Admin Dashboard
2. คลิกเมนู **"KYC Verification"** ใน sidebar (ไอคอน ID Card สีม่วง)

### 2. ดูรายการ KYC ที่รอตรวจสอบ

**หน้า KYC Index จะแสดง:**
- รายการ KYC ทั้งหมด (Pending, Approved, Rejected)
- ฟิลเตอร์ตาม status
- ค้นหาตาม ชื่อ/อีเมล/เลขบัตร
- Sort ตาม วันที่ส่ง

**Columns:**
- ชื่อผู้ใช้
- อีเมล
- เลขบัตรประชาชน (จาก OCR)
- สถานะ (Pending/Approved/Rejected)
- วันที่ส่ง
- Actions (ดู/อนุมัติ/ปฏิเสธ)

### 3. ตรวจสอบรายละเอียด KYC

คลิก **"ดูรายละเอียด"** เพื่อดู:

**รูปภาพ:**
- 🖼️ รูปบัตรประชาชน (Zoom ได้)
- 🖼️ รูป Selfie (Zoom ได้)

**ข้อมูลจาก OCR:**
```json
{
  "id_number": "1234567890123",
  "thai_name": "นายทดสอบ ระบบ",
  "english_name": "Mr. Thadsob Rabob",
  "birth_date": "1990-01-01",
  "issue_date": "2020-01-01",
  "expiry_date": "2030-01-01",
  "religion": "พุทธ",
  "address": "123/45 ถ.สุขุมวิท..."
}
```

**ข้อมูลผู้ใช้:**
- ชื่อ-นามสกุล
- อีเมล
- เบอร์โทร
- วันที่สมัครสมาชิก

### 4. อนุมัติ KYC

1. คลิกปุ่ม **"อนุมัติ"** (สีเขียว)
2. ระบบจะ:
   - อัพเดทสถานะ User เป็น 'approved'
   - Auto-fill ข้อมูล profile จาก OCR
   - ส่ง notification ไปยัง LINE User
   - บันทึก log การอนุมัติ

**ข้อมูลที่ Auto-fill:**
- เลขบัตรประชาชน
- ชื่อ-นามสกุล (ไทย)
- ชื่อ-นามสกุล (อังกฤษ)
- วันเกิด
- ที่อยู่

### 5. ปฏิเสธ KYC

1. คลิกปุ่ม **"ปฏิเสธ"** (สีแดง)
2. ระบุเหตุผล (บังคับ):
   - รูปบัตรประชาชนไม่ชัดเจน
   - รูป Selfie ไม่ตรงกับบัตร
   - บัตรหมดอายุ
   - ข้อมูลไม่ครบถ้วน
   - อื่นๆ (ระบุ)
3. ระบบจะ:
   - อัพเดทสถานะเป็น 'rejected'
   - ส่ง notification พร้อมเหตุผลไปยัง LINE User
   - User สามารถทำ KYC ใหม่ได้

---

## ⚙️ การตั้งค่าระบบ (Setup)

### 1. Google Cloud Vision API

**สร้าง Service Account:**

1. ไปที่ [Google Cloud Console](https://console.cloud.google.com/)
2. สร้าง Project ใหม่หรือเลือก Project ที่มีอยู่
3. Enable **Cloud Vision API**
4. สร้าง Service Account:
   - IAM & Admin → Service Accounts → Create Service Account
   - Role: **Cloud Vision API User**
   - Create Key (JSON)

**ตั้งค่าใน Laravel:**

1. เก็บ JSON key ใน `storage/app/google-credentials.json`
2. ตั้งค่าใน `.env`:

```env
# Google Cloud Vision API
GOOGLE_APPLICATION_CREDENTIALS=/absolute/path/to/storage/app/google-credentials.json
GOOGLE_CLOUD_PROJECT_ID=your-project-id
```

3. ตั้งค่าใน Admin Dashboard:
   - ตั้งค่า → OCR Settings
   - เปิดใช้งาน Google Vision API
   - ระบุ path ของ credentials

### 2. LINE Messaging API

**สร้าง LINE Channel:**

1. ไปที่ [LINE Developers Console](https://developers.line.biz/)
2. สร้าง Provider และ Messaging API Channel
3. ตั้งค่า Webhook URL: `https://yourdomain.com/line/webhook`

**ตั้งค่าใน `.env`:**

```env
# LINE Messaging API
LINE_CHANNEL_ID=your-channel-id
LINE_CHANNEL_SECRET=your-channel-secret
LINE_CHANNEL_ACCESS_TOKEN=your-access-token
```

**ตั้งค่า Webhook:**
- Webhook URL: `https://yourdomain.com/line/webhook`
- เปิดใช้งาน Webhook
- ปิด Auto-reply messages (ถ้าต้องการ)

### 3. Database Migration

```bash
# Run migrations
php artisan migrate

# Migrations ที่เกี่ยวข้อง:
# - 2025_11_03_200010_create_kyc_verifications_table.php
# - 2025_11_04_100000_add_ocr_settings.php
# - users table (มี kyc_status, kyc_verified_at, etc.)
```

### 4. Storage Setup

```bash
# สร้าง symbolic link
php artisan storage:link

# สร้าง directories
mkdir -p storage/app/public/kyc
chmod -R 775 storage/app/public/kyc
```

### 5. Queue Configuration (แนะนำ)

เนื่องจาก OCR processing ใช้เวลา ควรใช้ Queue:

```env
QUEUE_CONNECTION=redis
```

```bash
# Run queue worker
php artisan queue:work --queue=default,kyc
```

---

## 🔒 Security & Privacy

### 1. ข้อมูลที่เก็บ

**ข้อมูลที่เก็บในฐานข้อมูล:**
- รูปบัตรประชาชน (เข้ารหัสและจำกัดการเข้าถึง)
- รูป Selfie (เข้ารหัสและจำกัดการเข้าถึง)
- ข้อมูลจาก OCR (JSON format)
- สถานะ KYC
- วันที่ส่งและตรวจสอบ
- ผู้ตรวจสอบและเหตุผล (ถ้า rejected)

**ข้อมูลที่ไม่เก็บ:**
- รูปภาพต้นฉบับจาก LINE (ดาวน์โหลดแล้วแปลงเป็น WebP)
- ข้อมูลที่ไม่จำเป็น

### 2. การเข้าถึงข้อมูล

**Permissions:**
- ✅ Admin: ดูและจัดการ KYC ทั้งหมด
- ✅ User: ดูเฉพาะ KYC ของตัวเอง
- ❌ Guest: ไม่สามารถเข้าถึง

**File Storage:**
- รูปภาพเก็บใน `storage/app/public/kyc/{user_id}/`
- URL ถูก signed สำหรับความปลอดภัย
- Middleware ตรวจสอบ ownership

### 3. GDPR Compliance

**สิทธิของผู้ใช้:**
- ✅ ขอดูข้อมูล (Data Access)
- ✅ ขอลบข้อมูล (Right to be Forgotten)
- ✅ แก้ไขข้อมูล (Data Rectification)

**วิธีลบข้อมูล:**

```bash
# ลบ KYC ของ user
php artisan kyc:delete {user_id}

# ลบ KYC ที่เก่ากว่า 7 ปี (ตาม PDPA)
php artisan kyc:cleanup --older-than=7years
```

---

## 📊 Monitoring & Logging

### 1. Logs

**Log Files:**
```
storage/logs/laravel.log - Main log
storage/logs/kyc.log - KYC specific
storage/logs/ocr.log - OCR processing
```

**Log Entries:**
```
[2025-11-17 10:30:45] LINE KYC: Start process
  - line_user_id: U1234567890
  - user_id: 123

[2025-11-17 10:31:02] LINE KYC: ID card processed
  - user_id: 123
  - ocr_success: true
  - kyc_id: 456

[2025-11-17 10:31:45] LINE KYC: Selfie processed
  - user_id: 123
  - kyc_id: 456

[2025-11-17 11:15:30] LINE KYC: Admin approved
  - kyc_id: 456
  - admin_id: 1
```

### 2. Metrics

**KPIs ที่ควร Track:**
- จำนวน KYC ที่ส่งต่อวัน
- เวลาเฉลี่ยในการตรวจสอบ
- อัตราการอนุมัติ/ปฏิเสธ
- Accuracy ของ OCR
- Error rate

**Dashboard Metrics:**
```php
// Total KYC submissions today
KycVerification::whereDate('submitted_at', today())->count()

// Pending reviews
KycVerification::where('status', 'pending')->count()

// Approval rate
KycVerification::whereNotNull('reviewed_at')
    ->where('status', 'approved')
    ->count() / KycVerification::whereNotNull('reviewed_at')->count()

// Average review time
KycVerification::whereNotNull('reviewed_at')
    ->avg(DB::raw('TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at)'))
```

---

## 🐛 Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. OCR อ่านข้อมูลไม่ได้

**สาเหตุ:**
- รูปภาพมืดเกินไป
- แสงสะท้อน/กาแล็ก
- รูปเอียง/บิดเบี้ยว
- ความละเอียดต่ำ

**วิธีแก้:**
- ถ่ายรูปใหม่ในที่แสงสว่าง
- ไม่มีแสงสะท้อน
- วางบัตรเรียบ ไม่เอียง
- ใช้กล้องความละเอียดสูง

#### 2. LINE Webhook ไม่ทำงาน

**ตรวจสอบ:**
```bash
# ดู logs
tail -f storage/logs/laravel.log | grep "LINE webhook"

# ทดสอบ Webhook
curl -X POST https://yourdomain.com/line/webhook \
  -H "Content-Type: application/json" \
  -H "X-Line-Signature: test" \
  -d '{"events":[]}'
```

**สาเหตุที่พบบ่อย:**
- Webhook URL ไม่ถูกต้อง
- SSL Certificate หมดอายุ
- Firewall block
- LINE Channel Secret ผิด

#### 3. รูปภาพ Download ไม่ได้

**ตรวจสอบ:**
```bash
# ทดสอบ LINE Messaging API
curl -X GET "https://api-data.line.me/v2/bot/message/{messageId}/content" \
  -H "Authorization: Bearer {access_token}"
```

**สาเหตุ:**
- LINE Access Token หมดอายุ
- Message ID ผิด
- Network issue

#### 4. Google Cloud Vision Error

**ตรวจสอบ:**
```bash
# Verify credentials
cat storage/app/google-credentials.json

# Test API
php artisan tinker
>>> $service = new App\Services\OCR\ThaiIdCardOcrService();
>>> $service->extractData('path/to/test-image.jpg');
```

**สาเหตุ:**
- Credentials ผิด/หมดอายุ
- API ไม่ถูก enable
- Quota หมด

---

## 📖 API Reference

### LineKycService Methods

#### `startKycProcess(string $lineUserId, User $user): array`

เริ่มกระบวนการ KYC

**Parameters:**
- `$lineUserId` - LINE User ID
- `$user` - User model

**Returns:**
```php
[
    'success' => true,
    'message' => 'ส่งคำแนะนำ KYC ไปยัง LINE แล้ว',
]
```

#### `processImageFromLine(string $messageId, string $lineUserId, User $user, string $imageType): array`

ประมวลผลรูปภาพจาก LINE

**Parameters:**
- `$messageId` - LINE Message ID
- `$lineUserId` - LINE User ID
- `$user` - User model
- `$imageType` - 'id_card' หรือ 'selfie'

**Returns:**
```php
[
    'success' => true,
    'message' => 'ประมวลผลรูปบัตรประชาชนสำเร็จ',
    'kyc_id' => 123,
    'ocr_data' => [...]
]
```

#### `notifyKycResult(User $user, KycVerification $kyc, string $status): bool`

ส่งการแจ้งเตือนผลการตรวจสอบ

**Parameters:**
- `$user` - User model
- `$kyc` - KycVerification model
- `$status` - 'approved' หรือ 'rejected'

**Returns:** `bool`

---

## 🔄 Update & Maintenance

### การอัพเดทระบบ

```bash
# Pull latest code
git pull origin main

# Run migrations
php artisan migrate

# Clear cache
php artisan optimize:clear

# Restart queue workers
php artisan queue:restart
```

### Backup & Recovery

```bash
# Backup database
php artisan backup:run --only-db

# Backup files
tar -czf kyc-backup-$(date +%Y%m%d).tar.gz storage/app/public/kyc

# Recovery
php artisan backup:restore {backup-name}
```

### Performance Optimization

```bash
# Optimize images
php artisan kyc:optimize-images

# Clean old KYC (older than 7 years)
php artisan kyc:cleanup --older-than=7years

# Reindex database
php artisan db:reindex kyc_verifications
```

---

## 📞 Support & Contact

**Documentation:**
- KYC System Analysis: `KYC_SYSTEM_ANALYSIS.md`
- KYC Quick Reference: `KYC_QUICK_REFERENCE.md`
- V3 Coding Guidelines: `.claude/V3_CODING_GUIDELINES.md`

**Support:**
- Email: support@thaiprompt.com
- LINE: @thaiprompt
- GitHub Issues: [Repository Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

**Developers:**
- LINE API Docs: https://developers.line.biz/en/docs/messaging-api/
- Google Cloud Vision: https://cloud.google.com/vision/docs
- Laravel Docs: https://laravel.com/docs/11.x

---

## 📝 Changelog

### Version 1.0.0 (2025-11-17)

**✨ Features:**
- เพิ่มระบบ KYC ผ่าน LINE Messaging API
- Google Cloud Vision OCR integration
- Auto-extract ข้อมูลจากบัตรประชาชน
- LINE notifications ทุกขั้นตอน
- Admin approval/rejection workflow
- KYC menu ใน Admin Sidebar

**🔧 Technical:**
- `LineKycService` (682 lines)
- `LineWebhookController` updates
- `ThaiIdCardOcrService` (599 lines)
- V3 UI standards compliance

**📦 Files:**
- `app/Services/LineKycService.php`
- `app/Http/Controllers/LineWebhookController.php`
- `resources/views/components/arrow-x/sidebar-v3.blade.php`

---

**Last Updated:** 2025-11-17
**Version:** 1.0.0
**Maintained By:** Development Team

---

*"Excellence is not an act, but a habit" - Make every verification count.* ✨
