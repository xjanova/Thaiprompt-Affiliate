# TP-Affiliate License Manager

WordPress Plugin สำหรับจัดการ License, Installation Authorization และ Update Management สำหรับระบบ TP-Affiliate Pro

## คุณสมบัติหลัก

### 🔐 License Management
- ✅ สร้างและจัดการ License Keys  
- ✅ รองรับ Multiple License Types (Personal, Business, Developer, Enterprise)
- ✅ Package/Plan Management System
- ✅ Activation Tracking
- ✅ Expiry Management

### 📦 Installation Authorization
- ✅ ตรวจสอบสิทธิ์การติดตั้งครั้งแรก
- ✅ ติดตาม Installations แบบ Real-time
- ✅ จำกัดจำนวนการติดตั้งตาม Package
- ✅ บันทึก Server Information
- ✅ Installation Activity Logs

### 🔄 Update Management
- ✅ Release Management System
- ✅ Update Authorization ตาม Package
- ✅ Version Control และ Changelog
- ✅ Critical Update Flagging
- ✅ Download Tracking

### 🛍️ WooCommerce Integration
- ✅ สร้าง License อัตโนมัติเมื่อซื้อสินค้า
- ✅ Custom Product Type: "License"
- ✅ Order Management
- ✅ Customer Email Notifications

## การติดตั้ง

### ข้อกำหนดระบบ
- WordPress 6.0 หรือสูงกว่า
- PHP 8.0 หรือสูงกว่า
- MySQL 5.7 หรือสูงกว่า
- WooCommerce 7.0+ (ถ้าต้องการ WooCommerce Integration)

### วิธีติดตั้ง

1. **คัดลอกไฟล์ Plugin:**
   - คัดลอก folder `wordpress-plugin` ทั้งหมดไปยัง WordPress plugins directory
   - หรือ zip folder และ upload ผ่าน WordPress Admin

2. **Activate Plugin:**
   - เข้าสู่ WordPress Admin
   - ไปที่ Plugins → Installed Plugins
   - Activate "TP-Affiliate License Manager"

3. **ตั้งค่าเบื้องต้น:**
   - ไปที่ TP License → Settings
   - ตั้งค่า Email Notifications
   - ตรวจสอบ Package Settings

## API Endpoints

Plugin นี้มี REST API สำหรับเชื่อมต่อกับ TP-Affiliate Laravel App:

- `POST /wp-json/tp-license/v1/installation/authorize` - Authorization สำหรับ Install/Update
- `POST /wp-json/tp-license/v1/updates/available` - ตรวจสอบ Updates ที่มี
- `POST /wp-json/tp-license/v1/package/info` - ข้อมูล Package
- `POST /wp-json/tp-license/v1/validate` - Validate License
- `POST /wp-json/tp-license/v1/activate` - Activate License
- `POST /wp-json/tp-license/v1/deactivate` - Deactivate License

## Package Types

1. **Personal License** (2,999 บาท) - 1 Installation, 1 Year Updates
2. **Business License** (9,999 บาท) - 5 Installations, 1 Year Updates
3. **Developer License** (19,999 บาท) - Unlimited, Lifetime Updates
4. **Enterprise License** (49,999 บาท) - Unlimited, SLA, Dedicated Support

## การใช้งาน

Plugin นี้ทำหน้าที่เป็น **License Server** และเชื่อมต่อกับ:
- TP-Affiliate Laravel App (ลูกค้าติดตั้ง)
- Dev Release Manager (Developer ปล่อย Updates)

สำหรับคำแนะนำโดยละเอียด ดูที่ documentation

## License

GPL v2 or later

## Credits

Developed by Xman Enterprise Co., Ltd.
- Website: https://xman4289.com
