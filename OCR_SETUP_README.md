# คู่มือการติดตั้งและตั้งค่า Google Cloud Vision OCR

## สำหรับระบบ KYC (Know Your Customer) - อ่านบัตรประชาชนและใบขับขี่

---

## 📋 สารบัญ

1. [ข้อกำหนดเบื้องต้น](#ข้อกำหนดเบื้องต้น)
2. [ขั้นตอนการติดตั้ง](#ขั้นตอนการติดตั้ง)
3. [การตั้งค่า Google Cloud](#การตั้งค่า-google-cloud)
4. [การตั้งค่าในระบบ](#การตั้งค่าในระบบ)
5. [การทดสอบ](#การทดสอบ)
6. [Troubleshooting](#troubleshooting)

---

## ข้อกำหนดเบื้องต้น

- ✅ PHP 8.1 หรือสูงกว่า
- ✅ Laravel 11.x
- ✅ Composer
- ✅ Google Cloud Account (ต้องมี billing enabled)
- ✅ บัญชี Google Cloud Project

---

## ขั้นตอนการติดตั้ง

### 1. ติดตั้ง Composer Dependencies

```bash
# ถ้ายังไม่ได้ pull code ล่าสุด
git pull origin claude/setup-google-ocr-api-011CUnVwmYYKGparJkfMV2Fb

# ติดตั้ง dependencies
composer install

# หรือเฉพาะ google/cloud-vision
composer require google/cloud-vision
```

### 2. ตรวจสอบว่าติดตั้งสำเร็จ

```bash
composer show google/cloud-vision
```

คุณควรเห็นข้อมูลแบบนี้:
```
name     : google/cloud-vision
version  : v1.7.x
...
```

---

## การตั้งค่า Google Cloud

### ขั้นตอนที่ 1: สร้าง Google Cloud Project

1. เข้า [Google Cloud Console](https://console.cloud.google.com/)
2. สร้าง Project ใหม่หรือเลือก Project ที่มีอยู่
3. จดชื่อ Project ID ไว้

### ขั้นตอนที่ 2: เปิดใช้งาน Cloud Vision API

1. ใน Google Cloud Console ไปที่ **APIs & Services** → **Library**
2. ค้นหา "Cloud Vision API"
3. คลิก **ENABLE**

### ขั้นตอนที่ 3: สร้าง Service Account

1. ไปที่ **IAM & Admin** → **Service Accounts**
2. คลิก **+ CREATE SERVICE ACCOUNT**
3. กรอกข้อมูล:
   - **Service account name**: `ocr-service` (หรือชื่ือที่ต้องการ)
   - **Description**: `OCR for KYC document reading`
4. คลิก **CREATE AND CONTINUE**

### ขั้นตอนที่ 4: กำหนด Permissions

1. เลือก Role: **Cloud Vision AI Service Agent**
2. คลิก **CONTINUE**
3. คลิก **DONE**

### ขั้นตอนที่ 5: สร้างและดาวน์โหลด Key File

1. คลิกที่ Service Account ที่สร้างไว้
2. ไปที่แท็บ **KEYS**
3. คลิก **ADD KEY** → **Create new key**
4. เลือก **JSON**
5. คลิก **CREATE**
6. ไฟล์ JSON จะถูกดาวน์โหลดอัตโนมัติ → **เก็บไฟล์นี้ไว้อย่างปลอดภัย!**

### ⚠️ คำเตือนด้านความปลอดภัย

- **อย่า commit** ไฟล์ key (JSON) เข้า git
- **อย่าแชร์** ไฟล์นี้กับใคร
- **เก็บไว้** ในที่ปลอดภัย
- ไฟล์นี้มีสิทธิ์เข้าถึง Google Cloud Project ของคุณ!

---

## การตั้งค่าในระบบ

### วิธีที่ 1: ผ่าน Admin Panel (แนะนำ)

1. เข้าสู่ระบบด้วยบัญชี Admin
2. ไปที่ **Settings** (`/admin/settings`)
3. คลิกแท็บ **"OCR / KYC"** (ไอคอน 📸)
4. หรือเข้าตรงไปที่ `/admin/settings/ocr`

#### ในหน้าตั้งค่า:

1. ✅ **เปิดใช้งาน Google Cloud Vision API** (check box)
2. 📤 **อัปโหลดไฟล์ Service Account Key (JSON)**
   - คลิก "Choose File"
   - เลือกไฟล์ JSON ที่ดาวน์โหลดมา
   - ระบบจะตรวจสอบความถูกต้องอัตโนมัติ
3. 💾 **บันทึกการตั้งค่า**
4. 🔌 **ทดสอบการเชื่อมต่อ** (กดปุ่ม "ทดสอบการเชื่อมต่อ")

### วิธีที่ 2: ผ่าน Environment Variables (ทางเลือก)

แก้ไขไฟล์ `.env`:

```env
# Google Cloud Vision OCR
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
```

จากนั้นคัดลอกไฟล์ JSON ไปที่:
```bash
cp /path/to/downloaded-key.json storage/app/google-credentials.json
chmod 600 storage/app/google-credentials.json
```

---

## การทดสอบ

### ทดสอบการเชื่อมต่อ API

1. เข้า `/admin/settings/ocr`
2. กดปุ่ม **"ทดสอบการเชื่อมต่อ"**
3. ควรเห็นข้อความ: **"เชื่อมต่อ Google Cloud Vision API สำเร็จ! Project: xxx"**

### ทดสอบการอ่านบัตร

1. เข้าหน้า KYC: `/admin/kyc`
2. เลือกผู้ใช้ที่ต้องการตรวจสอบ
3. อัปโหลดรูปบัตรประชาชนหรือใบขับขี่
4. ระบบจะอ่านข้อมูลอัตโนมัติ
5. ตรวจสอบความถูกต้องของข้อมูลที่อ่านได้

### ฟีเจอร์ที่รองรับ

**การอ่านข้อมูล:**
- ✅ อ่านเลขบัตรประชาชน 13 หลัก
- ✅ อ่านชื่อ-นามสกุล (ทั้งภาษาไทยและอังกฤษ)
- ✅ อ่านวันเกิด
- ✅ อ่านที่อยู่
- ✅ อ่านเลขใบขับขี่
- ✅ อ่านประเภทรถที่ขับได้
- ✅ อ่านวันหมดอายุ
- ✅ ตรวจจับประเภทเอกสารอัตโนมัติ

**ฟีเจอร์เพิ่มเติม (v2.0 - Latest):**
- 🎥 **ถ่ายรูปด้วยกล้อง** พร้อมกรอบเล็งบัตรแบบ real-time
- 🖼️ **ตรวจสอบคุณภาพรูป** อัตโนมัติก่อน OCR
- ✨ **ปรับปรุงรูป** อัตโนมัติ (brightness, contrast, sharpness)
- 💬 **แจ้งข้อผิดพลาดละเอียด** พร้อมคำแนะนำแก้ไข
- 📊 **Partial data extraction** เก็บข้อมูลบางส่วนที่อ่านได้
- 🔄 **Switch กล้อง** หน้า/หลัง
- 📐 **Card frame overlay** ช่วยวางตำแหน่งบัตร
- 🛡️ **Image validation** (file size, resolution, format)

📖 **ดูคู่มือผู้ใช้:** [OCR_USER_GUIDE.md](./OCR_USER_GUIDE.md)

---

## Troubleshooting

### ❌ "ไม่พบ Google Cloud Vision library"

**วิธีแก้:**
```bash
composer require google/cloud-vision
```

### ❌ "ไม่พบไฟล์ credentials"

**วิธีแก้:**
1. ตรวจสอบว่าอัปโหลดไฟล์ JSON แล้ว
2. ตรวจสอบว่าไฟล์อยู่ที่ `storage/app/google-credentials.json`
3. ตรวจสอบ permissions: `chmod 600 storage/app/google-credentials.json`

### ❌ "ไฟล์ credentials หายหลัง deploy"

**สาเหตุ:**
- ระบบ deploy script มีการ backup และ restore อัตโนมัติแล้ว (ตั้งแต่ version ล่าสุด)
- แต่ถ้าไม่มีไฟล์เดิมก่อน deploy ครั้งแรก ระบบจะไม่มีอะไรให้ restore

**วิธีแก้:**
1. **สำหรับครั้งแรก:** อัปโหลดไฟล์ผ่าน Admin Panel (`/admin/settings/ocr`)
2. **หรือ** คัดลอกไฟล์โดยตรง:
   ```bash
   # SSH เข้า production server
   cd /path/to/your/project

   # คัดลอกไฟล์ credentials
   cp /path/to/your-service-account-key.json storage/app/google-credentials.json

   # ตั้งค่า permissions
   chmod 600 storage/app/google-credentials.json
   chown www-data:www-data storage/app/google-credentials.json
   ```
3. **หลังจากนั้น:** Deploy script จะ backup และ restore ไฟล์นี้อัตโนมัติทุกครั้ง

**การตรวจสอบว่า backup ทำงาน:**
```bash
# ดู deployment logs
tail -f storage/logs/deployment.log

# ควรเห็นข้อความ:
# ✓ Backed up Google credentials
# ✓ Restored Google credentials
```

### ❌ "ไฟล์นี้ไม่ใช่ Service Account Key ที่ถูกต้อง"

**วิธีแก้:**
1. ตรวจสอบว่าดาวน์โหลดไฟล์ถูกต้อง (ต้องเป็นไฟล์ JSON)
2. เปิดไฟล์ดูว่ามี `"type": "service_account"` หรือไม่
3. ถ้าไม่ใช่ ให้สร้าง Service Account Key ใหม่

### ❌ "Permission denied" หรือ "Forbidden"

**สาเหตุ:**
- Service Account ไม่มีสิทธิ์เพียงพอ

**วิธีแก้:**
1. ไปที่ Google Cloud Console
2. **IAM & Admin** → **Service Accounts**
3. เลือก Service Account ที่ใช้
4. เพิ่ม Role: **Cloud Vision AI Service Agent**

### ❌ "Quota exceeded" หรือ "Rate limit"

**สาเหตุ:**
- ใช้งาน API เกิน quota ที่กำหนด

**วิธีแก้:**
1. ตรวจสอบ quota ที่ [Google Cloud Console](https://console.cloud.google.com/apis/api/vision.googleapis.com/quotas)
2. พิจารณาเพิ่ม quota หรือรอให้ quota reset

### ❌ "Billing not enabled"

**สาเหตุ:**
- Google Cloud Project ยังไม่ได้เปิดใช้ billing

**วิธีแก้:**
1. ไปที่ [Billing](https://console.cloud.google.com/billing)
2. เปิดใช้งาน billing สำหรับ project
3. เพิ่มบัตรเครดิตเพื่อชำระค่าบริการ

### ⚠️ ราคาและ Quota

**Google Cloud Vision API Pricing:**
- **Free tier**: 1,000 หน่วยต่อเดือน
- **After free tier**: $1.50 ต่อ 1,000 หน่วย

**การคำนวณ:**
- 1 รูปบัตรประชาชน = 1 หน่วย
- 1,000 บัตร/เดือน = ฟรี
- 10,000 บัตร/เดือน ≈ $13.50

ดูข้อมูลเพิ่มเติม: https://cloud.google.com/vision/pricing

---

## ไฟล์ที่เกี่ยวข้อง

```
app/
├── Http/Controllers/Admin/
│   ├── SettingsController.php       # OCR settings management
│   └── KycController.php             # KYC document verification
├── Services/OCR/
│   └── ThaiIdCardOcrService.php     # OCR processing service
resources/views/admin/
├── settings/
│   ├── index.blade.php              # Settings with OCR tab
│   ├── ocr.blade.php                # OCR dedicated settings page
│   └── setup-guide.blade.php        # Setup guide
routes/
└── admin.php                        # OCR routes
database/migrations/
└── 2025_11_04_100000_add_ocr_settings.php
```

---

## สนับสนุน

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- 📧 GitHub Issues: [Report Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- 📖 Documentation: [See DEPLOYMENT_INSTRUCTIONS.md](./DEPLOYMENT_INSTRUCTIONS.md)

---

## License

MIT License - Free to use
