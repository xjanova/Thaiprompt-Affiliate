# 📱 คู่มือการตั้งค่า LINE Official Account สำหรับระบบ KYC

## สารบัญ

1. [ภาพรวม](#ภาพรวม)
2. [การสร้าง LINE Official Account](#การสร้าง-line-official-account)
3. [การสร้าง Messaging API Channel](#การสร้าง-messaging-api-channel)
4. [การตั้งค่า Webhook](#การตั้งค่า-webhook)
5. [การกำหนดค่าในระบบ](#การกำหนดค่าในระบบ)
6. [การทดสอบระบบ](#การทดสอบระบบ)
7. [คำถามที่พบบ่อย (FAQ)](#คำถามที่พบบ่อย)

---

## ภาพรวม

ระบบนี้ใช้ LINE Official Account (LINE OA) สำหรับการยืนยันตัวตน (KYC) ของผู้ใช้ โดยผู้ใช้จะต้องเพิ่ม LINE OA เป็นเพื่อนและทำการยืนยันตัวตนก่อนที่จะสามารถถอนเงินจากระบบได้

### คุณสมบัติหลัก

- ✅ ยืนยันตัวตนอัตโนมัติผ่าน LINE OA
- ✅ บังคับให้ผู้ใช้เพิ่มเพื่อน LINE OA ก่อนถอนเงิน
- ✅ ส่งการแจ้งเตือนผ่าน LINE
- ✅ ตั้งค่าวงเงินขั้นต่ำที่ต้อง KYC ได้
- ✅ บันทึกประวัติการสนทนาทั้งหมด

---

## การสร้าง LINE Official Account

### ขั้นตอนที่ 1: เข้าสู่ LINE Official Account Manager

1. เข้าไปที่ [LINE Official Account Manager](https://manager.line.biz/)
2. Login ด้วยบัญชี LINE ของคุณ
3. คลิก **"Create a LINE Official Account"** หรือ **"สร้างบัญชีทางการ"**

### ขั้นตอนที่ 2: กรอกข้อมูลบัญชี

1. **Account name (ชื่อบัญชี)**: ชื่อที่จะแสดงให้ผู้ใช้เห็น
   - ตัวอย่าง: "ThaiPrompt Marketplace"

2. **Industry (ประเภทธุรกิจ)**: เลือกประเภทธุรกิจของคุณ
   - แนะนำ: "E-commerce" หรือ "Retail"

3. **Company/Owner name**: ชื่อบริษัทหรือเจ้าของ

4. **Email address**: อีเมลสำหรับติดต่อ

5. กด **"Create"** เพื่อสร้างบัญชี

### ขั้นตอนที่ 3: ตั้งค่าพื้นฐาน

1. อัปโหลด **Profile picture** (รูปโปรไฟล์)
2. อัปโหลด **Cover image** (รูป Cover)
3. เขียน **Status message** (ข้อความสถานะ)
4. เขียน **Introduction** (คำแนะนำตัว)

---

## การสร้าง Messaging API Channel

### ขั้นตอนที่ 1: เข้าสู่ LINE Developers Console

1. เข้าไปที่ [LINE Developers Console](https://developers.line.biz/console/)
2. Login ด้วยบัญชี LINE เดียวกับที่สร้าง LINE OA
3. คลิก **"Create a new provider"** หรือเลือก Provider ที่มีอยู่แล้ว

### ขั้นตอนที่ 2: สร้าง Provider (ถ้ายังไม่มี)

1. กรอก **Provider name**: ชื่อของบริษัทหรือองค์กร
   - ตัวอย่าง: "ThaiPrompt"
2. คลิก **"Create"**

### ขั้นตอนที่ 3: สร้าง Messaging API Channel

1. ภายใน Provider คลิก **"Create a new channel"**
2. เลือก **"Messaging API"**
3. กรอกข้อมูลต่อไปนี้:

   - **Channel type**: Messaging API
   - **Provider**: เลือก Provider ที่สร้างไว้
   - **Channel name**: ชื่อ Channel (ควรตรงกับชื่อ LINE OA)
   - **Channel description**: คำอธิบาย Channel
   - **Category**: เลือกหมวดหมู่ที่เหมาะสม (E-commerce)
   - **Subcategory**: เลือกหมวดหมู่ย่อย

4. อ่านและยอมรับข้อตกลง
5. คลิก **"Create"**

### ขั้นตอนที่ 4: เชื่อมโยงกับ LINE Official Account

1. ในหน้า Channel settings ให้คลิก **"Link a LINE Official Account"**
2. เลือก LINE Official Account ที่สร้างไว้
3. คลิก **"Link"**

---

## การตั้งค่า Webhook

### ขั้นตอนที่ 1: เปิดใช้งาน Webhook

1. ในหน้า **Messaging API** tab
2. ค้นหา **"Webhook settings"**
3. กด **"Edit"** ที่ **Webhook URL**
4. กรอก URL: `https://yourdomain.com/api/webhooks/line`
   - แทนที่ `yourdomain.com` ด้วยโดเมนจริงของคุณ
   - ⚠️ **สำคัญ**: ต้องเป็น HTTPS เท่านั้น!
5. เปิด **"Use webhook"** (เลื่อนปุ่มให้เป็นสีเขียว)
6. คลิก **"Verify"** เพื่อทดสอบ Webhook

### ขั้นตอนที่ 2: ตั้งค่า Auto-reply messages

1. ไปที่ **LINE Official Account Manager**
2. เลือกบัญชีของคุณ
3. ไปที่ **Settings** > **Response settings**
4. ตั้งค่าดังนี้:
   - **Chat**: เปิดใช้งาน
   - **Greeting message**: ปิดใช้งาน (ระบบจะส่งเอง)
   - **Auto-response**: ปิดใช้งาน
   - **Webhook**: เปิดใช้งาน

### ขั้นตอนที่ 3: รับ Channel Access Token

1. กลับไปที่ **LINE Developers Console**
2. เลือก Channel ที่สร้างไว้
3. ไปที่ **Messaging API** tab
4. ที่ส่วน **Channel access token (long-lived)** คลิก **"Issue"**
5. คัดลอก **Channel access token** เก็บไว้

### ขั้นตอนที่ 4: รับ Channel Secret

1. ในหน้า **Channel settings** ที่ส่วน **Basic settings**
2. ค้นหา **Channel secret**
3. คลิก **"Show"** แล้วคัดลอกเก็บไว้

### ขั้นตอนที่ 5: รับ Channel ID

1. ในหน้า **Channel settings** ที่ส่วน **Basic settings**
2. ค้นหา **Channel ID**
3. คัดลอกเก็บไว้

---

## การกำหนดค่าในระบบ

### ขั้นตอนที่ 1: อัพเดทไฟล์ .env

เปิดไฟล์ `.env` และเพิ่ม/แก้ไขค่าต่อไปนี้:

```env
# LINE Official Account Configuration
LINE_CHANNEL_ID=YOUR_CHANNEL_ID
LINE_CHANNEL_SECRET=YOUR_CHANNEL_SECRET
LINE_CHANNEL_ACCESS_TOKEN=YOUR_CHANNEL_ACCESS_TOKEN

# KYC Settings
LINE_REQUIRE_KYC_FOR_WITHDRAWAL=true
LINE_MIN_WITHDRAWAL_WITHOUT_KYC=1000
```

แทนที่:
- `YOUR_CHANNEL_ID` ด้วย Channel ID ที่คัดลอกไว้
- `YOUR_CHANNEL_SECRET` ด้วย Channel Secret ที่คัดลอกไว้
- `YOUR_CHANNEL_ACCESS_TOKEN` ด้วย Channel Access Token ที่คัดลอกไว้

### ขั้นตอนที่ 2: รันคำสั่ง Seeder

```bash
php artisan db:seed --class=LineOaConfigSeeder
```

### ขั้นตอนที่ 3: เปิดใช้งานการตั้งค่า LINE OA ในระบบ

1. Login เข้าสู่ระบบในฐานะ **Super Admin**
2. ไปที่ **Settings** > **LINE OA Configuration**
3. แก้ไขการตั้งค่า LINE OA:
   - **Name**: ชื่อการตั้งค่า
   - **Channel ID**: (จะถูกกรอกอัตโนมัติจาก .env)
   - **Channel Secret**: (จะถูกกรอกอัตโนมัติจาก .env)
   - **Channel Access Token**: (จะถูกกรอกอัตโนมัติจาก .env)
   - **Webhook URL**: `https://yourdomain.com/api/webhooks/line`
   - **Is Active**: เปิดใช้งาน (✓)
   - **Require KYC for Withdrawal**: เปิดใช้งาน (✓)
   - **Minimum Withdrawal without KYC**: 1000 (หรือตามต้องการ)
4. คลิก **"Save"**

---

## การทดสอบระบบ

### ทดสอบการเชื่อมต่อ Webhook

1. ใช้ LINE ของคุณเอง เพิ่ม LINE Official Account เป็นเพื่อน
2. ส่งข้อความว่า `สวัสดี` หรือ `Hello`
3. ตรวจสอบใน **Database** ที่ตาราง `line_oa_webhooks` ว่ามีข้อมูลเข้ามาหรือไม่
4. ตรวจสอบใน **Logs** ที่ `storage/logs/laravel.log`

### ทดสอบการยืนยันตัวตน (KYC)

1. สร้างบัญชีผู้ใช้ทดสอบในระบบ
2. ในหน้าโปรไฟล์ ให้คลิก **"Verify with LINE"**
3. ระบบจะแสดง QR Code หรือ Link สำหรับเพิ่มเพื่อน LINE OA
4. เพิ่มเพื่อนและส่งข้อความว่า `ยืนยันตัวตน` หรือ `verify`
5. ระบบจะตอบกลับว่า "✅ ยืนยันตัวตนสำเร็จ!"
6. ตรวจสอบในฐานข้อมูล ตาราง `user_kyc_verifications` ว่าสถานะเป็น `verified`

### ทดสอบการถอนเงิน

1. ลองถอนเงินจำนวนน้อยกว่า Minimum (ไม่ต้อง KYC)
   - ควรสำเร็จ
2. ลองถอนเงินจำนวนมากกว่า Minimum โดยไม่มี KYC
   - ควรถูกปฏิเสธพร้อมข้อความแจ้งให้ทำ KYC
3. ลองถอนเงินจำนวนมากกว่า Minimum โดยมี KYC แล้ว
   - ควรสำเร็จ
4. ตรวจสอบว่ามีการส่งการแจ้งเตือนผ่าน LINE หรือไม่

---

## Rich Menu (ทางเลือก - ขั้นสูง)

หากต้องการเพิ่ม Rich Menu ให้กับ LINE OA:

### สร้าง Rich Menu

```bash
curl -X POST https://api.line.me/v2/bot/richmenu \
-H 'Authorization: Bearer YOUR_CHANNEL_ACCESS_TOKEN' \
-H 'Content-Type: application/json' \
-d '{
  "size": {
    "width": 2500,
    "height": 1686
  },
  "selected": true,
  "name": "Main Menu",
  "chatBarText": "Menu",
  "areas": [
    {
      "bounds": {
        "x": 0,
        "y": 0,
        "width": 1250,
        "height": 843
      },
      "action": {
        "type": "postback",
        "data": "action=verify_kyc"
      }
    },
    {
      "bounds": {
        "x": 1250,
        "y": 0,
        "width": 1250,
        "height": 843
      },
      "action": {
        "type": "uri",
        "uri": "https://yourdomain.com/dashboard"
      }
    },
    {
      "bounds": {
        "x": 0,
        "y": 843,
        "width": 1250,
        "height": 843
      },
      "action": {
        "type": "uri",
        "uri": "https://yourdomain.com/wallet"
      }
    },
    {
      "bounds": {
        "x": 1250,
        "y": 843,
        "width": 1250,
        "height": 843
      },
      "action": {
        "type": "message",
        "text": "ติดต่อเรา"
      }
    }
  ]
}'
```

---

## คำถามที่พบบ่อย (FAQ)

### Q1: Webhook ไม่ทำงาน

**A:** ตรวจสอบดังนี้:
1. URL ต้องเป็น HTTPS
2. Server ต้องเข้าถึงได้จากภายนอก (ไม่ใช่ localhost)
3. ตรวจสอบ Firewall และ SSL Certificate
4. ดูใน `storage/logs/laravel.log` ว่ามี error อะไร

### Q2: Channel Access Token หมดอายุ

**A:** Channel Access Token แบบ Long-lived ไม่หมดอายุ แต่ถ้า:
- มีการ Revoke
- มีการออก Token ใหม่
- Channel ถูกลบ

ให้ออก Token ใหม่และอัพเดทใน `.env`

### Q3: ผู้ใช้ส่งข้อความ "ยืนยันตัวตน" แล้วไม่มีการตอบกลับ

**A:** ตรวจสอบ:
1. ผู้ใช้เชื่อมโยงบัญชีกับระบบแล้วหรือยัง (มี `line_user_id` ใน database)
2. Webhook ทำงานหรือไม่ (ดูใน `line_oa_webhooks` table)
3. ดู error logs

### Q4: ต้องการเปลี่ยน Minimum Withdrawal Amount

**A:** แก้ไขได้ที่:
1. Admin Panel > Settings > LINE OA Configuration
2. แก้ไขค่า "Minimum Withdrawal without KYC"
3. หรือแก้ไขใน `.env`: `LINE_MIN_WITHDRAWAL_WITHOUT_KYC=จำนวนเงิน`

### Q5: ต้องการปิดการบังคับ KYC

**A:**
1. Admin Panel > Settings > LINE OA Configuration
2. ปิด "Require KYC for Withdrawal"
3. หรือตั้งค่าใน `.env`: `LINE_REQUIRE_KYC_FOR_WITHDRAWAL=false`

### Q6: สามารถใช้ LINE OA หลายบัญชีได้หรือไม่

**A:** ปัจจุบันรองรับเพียง 1 บัญชี (Active) แต่สามารถเก็บการตั้งค่าหลายบัญชีไว้และสลับ Active ได้

---

## การดูแลรักษา

### ตรวจสอบ Webhook Logs

```bash
php artisan tinker
>>> App\Models\LineOaWebhook::latest()->limit(10)->get();
```

### ตรวจสอบ KYC Verifications

```bash
php artisan tinker
>>> App\Models\UserKycVerification::where('verification_status', 'verified')->count();
```

### ลบ Webhook Logs เก่า (เกิน 30 วัน)

```bash
php artisan tinker
>>> App\Models\LineOaWebhook::where('created_at', '<', now()->subDays(30))->delete();
```

---

## ข้อควรระวัง

⚠️ **Security**
- เก็บ Channel Secret และ Access Token ให้ปลอดภัย
- ไม่ควร commit ไฟล์ `.env` ลง Git
- ใช้ HTTPS เท่านั้นสำหรับ Webhook

⚠️ **Privacy**
- ข้อมูลจาก LINE (display name, picture) จะถูกเก็บในระบบ
- ต้องมี Privacy Policy ที่ชัดเจน
- ต้องได้รับความยินยอมจากผู้ใช้

⚠️ **Rate Limits**
- LINE API มี Rate Limit
- ไม่ควรส่งข้อความมากเกินไป (Spam)

---

## ทรัพยากรเพิ่มเติม

- [LINE Messaging API Documentation](https://developers.line.biz/en/docs/messaging-api/)
- [LINE Developers Console](https://developers.line.biz/console/)
- [LINE Official Account Manager](https://manager.line.biz/)
- [LINE API Reference](https://developers.line.biz/en/reference/messaging-api/)

---

Made with ❤️ by ThaiPrompt Team
